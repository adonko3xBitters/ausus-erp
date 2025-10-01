<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'product_id',
        'item_type',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_id',
        'tax_amount',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // Relations
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    // Calculer le montant
    protected static function booted()
    {
        static::saving(function ($item) {
            // Calculer la remise
            if ($item->discount_percent > 0) {
                $item->discount_amount = ($item->quantity * $item->unit_price) * ($item->discount_percent / 100);
            }

            // Calculer le montant HT
            $subtotal = ($item->quantity * $item->unit_price) - $item->discount_amount;

            // Calculer la taxe
            if ($item->tax_id) {
                $tax = Tax::find($item->tax_id);
                $item->tax_amount = $tax->calculate($subtotal);
            }

            // Montant TTC
            $item->amount = $subtotal + $item->tax_amount;
        });
    }
}
