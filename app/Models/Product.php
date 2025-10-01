<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'description',
        'type',
        'category_id',
        'brand_id',
        'unit_id',
        'purchase_price',
        'sale_price',
        'cost_price',
        'sale_tax_id',
        'purchase_tax_id',
        'sales_account_id',
        'purchase_account_id',
        'inventory_account_id',
        'track_inventory',
        'cost_method',
        'alert_quantity',
        'images',
        'featured_image',
        'has_variants',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'alert_quantity' => 'integer',
        'images' => 'array',
        'track_inventory' => 'boolean',
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function saleTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'sale_tax_id');
    }

    public function purchaseTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'purchase_tax_id');
    }

    public function salesAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function purchaseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // Générer SKU et slug
    protected static function booted()
    {
        static::creating(function ($product) {
            if (empty($product->sku)) {
                $product->sku = 'PRD-' . strtoupper(Str::random(8));
            }
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    // Obtenir le stock total tous entrepôts confondus
    public function getTotalStockAttribute(): float
    {
        return $this->stocks()->sum('quantity');
    }

    // Obtenir le stock disponible total
    public function getAvailableStockAttribute(): float
    {
        return $this->stocks()->sum('available_quantity');
    }

    // Vérifier si le stock est bas
    public function isLowStock(): bool
    {
        return $this->available_stock <= $this->alert_quantity;
    }
}
