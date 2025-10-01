<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'attributes',
        'additional_price',
        'image',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'additional_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // Prix de vente de la variante
    public function getSalePriceAttribute(): float
    {
        return $this->product->sale_price + $this->additional_price;
    }

    // Stock total de la variante
    public function getTotalStockAttribute(): float
    {
        return $this->stocks()->sum('quantity');
    }

    // Stock disponible de la variante
    public function getAvailableStockAttribute(): float
    {
        return $this->stocks()->sum('available_quantity');
    }
}
