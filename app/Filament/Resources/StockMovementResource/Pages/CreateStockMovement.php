<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use App\Services\StockService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $stockService = app(StockService::class);
        $movement = $this->record;

        try {
            // Traiter le mouvement selon le type
            switch ($movement->type) {
                case 'in':
                    // Déjà créé, juste mettre à jour le stock
                    $stock = $stockService->getOrCreateStock(
                        $movement->warehouse_id,
                        $movement->product_id,
                        $movement->product_variant_id
                    );
                    $stock->quantity += $movement->quantity;
                    $stock->save();
                    break;

                case 'out':
                    $stock = $stockService->getOrCreateStock(
                        $movement->warehouse_id,
                        $movement->product_id,
                        $movement->product_variant_id
                    );

                    if ($stock->available_quantity < $movement->quantity) {
                        throw new \Exception('Stock insuffisant');
                    }

                    $stock->quantity -= $movement->quantity;
                    $stock->save();
                    break;

                case 'adjustment':
                    // L'ajustement nécessite la nouvelle quantité
                    // Ce cas devrait être géré différemment
                    break;

                case 'transfer':
                    // Sortie de l'entrepôt source
                    $stockFrom = $stockService->getOrCreateStock(
                        $movement->from_warehouse_id,
                        $movement->product_id,
                        $movement->product_variant_id
                    );

                    if ($stockFrom->available_quantity < $movement->quantity) {
                        throw new \Exception('Stock insuffisant dans l\'entrepôt source');
                    }

                    $stockFrom->quantity -= $movement->quantity;
                    $stockFrom->save();

                    // Entrée dans l'entrepôt destination
                    $stockTo = $stockService->getOrCreateStock(
                        $movement->to_warehouse_id,
                        $movement->product_id,
                        $movement->product_variant_id
                    );
                    $stockTo->quantity += $movement->quantity;
                    $stockTo->save();
                    break;
            }

            Notification::make()
                ->title('Mouvement de stock enregistré')
                ->success()
                ->send();

        } catch (\Exception $e) {
            // Supprimer le mouvement si erreur
            $movement->delete();

            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
