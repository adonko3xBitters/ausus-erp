<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'movement_number',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'type',
        'quantity',
        'cost_per_unit',
        'total_cost',
        'movement_date',
        'reference',
        'notes',
        'movable_type',
        'movable_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    // Relations
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function movable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Générer le numéro de mouvement
    public static function generateNumber(): string
    {
        $year = now()->year;
        $lastMovement = static::where('movement_number', 'like', "MVT-{$year}-%")
            ->orderByDesc('movement_number')
            ->first();

        $number = $lastMovement ? intval(substr($lastMovement->movement_number, -4)) + 1 : 1;
        return sprintf('MVT-%s-%04d', $year, $number);
    }

    // Générer le numéro automatiquement
    protected static function booted()
    {
        static::creating(function ($movement) {
            if (empty($movement->movement_number)) {
                $movement->movement_number = static::generateNumber();
            }
            if (empty($movement->created_by)) {
                $movement->created_by = auth()->id();
            }
            // Calculer le coût total
            $movement->total_cost = $movement->quantity * $movement->cost_per_unit;
        });
    }

    // Libellés
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'in' => 'Entrée',
            'out' => 'Sortie',
            'adjustment' => 'Ajustement',
            'transfer' => 'Transfert',
            default => $this->type,
        };
    }
}
