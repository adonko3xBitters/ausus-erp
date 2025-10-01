<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Créer ou obtenir un enregistrement de stock
     */
    protected function getOrCreateStock(
        int $warehouseId,
        int $productId,
        ?int $productVariantId = null
    ): Stock {
        return Stock::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
            ]
        );
    }

    /**
     * Entrée de stock
     */
    public function stockIn(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $stock = $this->getOrCreateStock(
                $data['warehouse_id'],
                $data['product_id'],
                $data['product_variant_id'] ?? null
            );

            // Augmenter le stock
            $stock->quantity += $data['quantity'];
            $stock->save();

            // Créer le mouvement
            $movement = StockMovement::create([
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'type' => 'in',
                'quantity' => $data['quantity'],
                'cost_per_unit' => $data['cost_per_unit'] ?? 0,
                'movement_date' => $data['movement_date'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'movable_type' => $data['movable_type'] ?? null,
                'movable_id' => $data['movable_id'] ?? null,
            ]);

            // Mettre à jour le coût moyen pondéré (CMP)
            $this->updateAverageCost($data['product_id'], $data['product_variant_id'] ?? null);

            return $movement;
        });
    }

    /**
     * Sortie de stock
     */
    public function stockOut(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $stock = $this->getOrCreateStock(
                $data['warehouse_id'],
                $data['product_id'],
                $data['product_variant_id'] ?? null
            );

            // Vérifier le stock disponible
            if ($stock->available_quantity < $data['quantity']) {
                throw new \Exception(
                    "Stock insuffisant. Disponible: {$stock->available_quantity}, Demandé: {$data['quantity']}"
                );
            }

            // Diminuer le stock
            $stock->quantity -= $data['quantity'];
            $stock->save();

            // Créer le mouvement
            $movement = StockMovement::create([
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'type' => 'out',
                'quantity' => $data['quantity'],
                'cost_per_unit' => $data['cost_per_unit'] ?? $this->getProductCost($data['product_id']),
                'movement_date' => $data['movement_date'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'movable_type' => $data['movable_type'] ?? null,
                'movable_id' => $data['movable_id'] ?? null,
            ]);

            return $movement;
        });
    }

    /**
     * Ajustement de stock
     */
    public function adjustStock(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $stock = $this->getOrCreateStock(
                $data['warehouse_id'],
                $data['product_id'],
                $data['product_variant_id'] ?? null
            );

            $oldQuantity = $stock->quantity;
            $newQuantity = $data['new_quantity'];
            $difference = $newQuantity - $oldQuantity;

            // Mettre à jour le stock
            $stock->quantity = $newQuantity;
            $stock->save();

            // Créer le mouvement
            $movement = StockMovement::create([
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'type' => 'adjustment',
                'quantity' => abs($difference),
                'cost_per_unit' => $data['cost_per_unit'] ?? $this->getProductCost($data['product_id']),
                'movement_date' => $data['movement_date'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? "Ajustement: {$oldQuantity} → {$newQuantity}",
            ]);

            return $movement;
        });
    }

    /**
     * Transfert entre entrepôts
     */
    public function transferStock(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Sortie de l'entrepôt source
            $stockOut = $this->stockOut([
                'warehouse_id' => $data['from_warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'quantity' => $data['quantity'],
                'cost_per_unit' => $data['cost_per_unit'] ?? $this->getProductCost($data['product_id']),
                'movement_date' => $data['movement_date'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'notes' => "Transfert vers entrepôt #{$data['to_warehouse_id']}",
            ]);

            // Entrée dans l'entrepôt destination
            $stockIn = $this->stockIn([
                'warehouse_id' => $data['to_warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'quantity' => $data['quantity'],
                'cost_per_unit' => $data['cost_per_unit'] ?? $this->getProductCost($data['product_id']),
                'movement_date' => $data['movement_date'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'notes' => "Transfert depuis entrepôt #{$data['from_warehouse_id']}",
            ]);

            // Marquer comme transfert
            $stockOut->update([
                'type' => 'transfer',
                'to_warehouse_id' => $data['to_warehouse_id'],
            ]);

            $stockIn->update([
                'type' => 'transfer',
                'from_warehouse_id' => $data['from_warehouse_id'],
            ]);

            return [
                'out' => $stockOut,
                'in' => $stockIn,
            ];
        });
    }

    /**
     * Réserver du stock (pour les commandes)
     */
    public function reserveStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $productVariantId = null
    ): void {
        DB::transaction(function () use ($warehouseId, $productId, $quantity, $productVariantId) {
            $stock = $this->getOrCreateStock($warehouseId, $productId, $productVariantId);

            if ($stock->available_quantity < $quantity) {
                throw new \Exception("Stock disponible insuffisant pour la réservation");
            }

            $stock->reserved_quantity += $quantity;
            $stock->save();
        });
    }

    /**
     * Libérer une réservation de stock
     */
    public function releaseReservedStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $productVariantId = null
    ): void {
        DB::transaction(function () use ($warehouseId, $productId, $quantity, $productVariantId) {
            $stock = $this->getOrCreateStock($warehouseId, $productId, $productVariantId);

            $stock->reserved_quantity -= $quantity;
            if ($stock->reserved_quantity < 0) {
                $stock->reserved_quantity = 0;
            }
            $stock->save();
        });
    }

    /**
     * Calculer le coût moyen pondéré (CMP)
     */
    protected function updateAverageCost(int $productId, ?int $productVariantId = null): void
    {
        $product = Product::find($productId);

        if ($product->cost_method !== 'average') {
            return;
        }

        // Récupérer tous les mouvements d'entrée
        $movements = StockMovement::where('product_id', $productId)
            ->where('type', 'in')
            ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId))
            ->orderBy('movement_date', 'desc')
            ->take(100) // Limiter aux 100 dernières entrées
            ->get();

        if ($movements->isEmpty()) {
            return;
        }

        $totalCost = $movements->sum('total_cost');
        $totalQuantity = $movements->sum('quantity');

        if ($totalQuantity > 0) {
            $averageCost = $totalCost / $totalQuantity;
            $product->cost_price = $averageCost;
            $product->save();
        }
    }

    /**
     * Obtenir le coût d'un produit selon la méthode
     */
    public function getProductCost(int $productId, ?int $productVariantId = null): float
    {
        $product = Product::find($productId);

        switch ($product->cost_method) {
            case 'fifo':
                return $this->getFifoCost($productId, $productVariantId);

            case 'lifo':
                return $this->getLifoCost($productId, $productVariantId);

            case 'average':
            default:
                return (float) $product->cost_price;
        }
    }

    /**
     * Coût FIFO (Premier Entré, Premier Sorti)
     */
    protected function getFifoCost(int $productId, ?int $productVariantId = null): float
    {
        $movement = StockMovement::where('product_id', $productId)
            ->where('type', 'in')
            ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId))
            ->orderBy('movement_date', 'asc')
            ->first();

        return $movement ? (float) $movement->cost_per_unit : 0;
    }

    /**
     * Coût LIFO (Dernier Entré, Premier Sorti)
     */
    protected function getLifoCost(int $productId, ?int $productVariantId = null): float
    {
        $movement = StockMovement::where('product_id', $productId)
            ->where('type', 'in')
            ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId))
            ->orderBy('movement_date', 'desc')
            ->first();

        return $movement ? (float) $movement->cost_per_unit : 0;
    }

    /**
     * Obtenir le stock d'un produit dans un entrepôt
     */
    public function getStock(
        int $warehouseId,
        int $productId,
        ?int $productVariantId = null
    ): ?Stock {
        return Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId))
            ->first();
    }

    /**
     * Vérifier la disponibilité du stock
     */
    public function checkAvailability(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $productVariantId = null
    ): bool {
        $stock = $this->getStock($warehouseId, $productId, $productVariantId);

        if (!$stock) {
            return false;
        }

        return $stock->available_quantity >= $quantity;
    }
}
