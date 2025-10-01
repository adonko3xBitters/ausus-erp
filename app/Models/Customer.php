<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_number',
        'name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'tax_number',
        'trade_register',
        'billing_address',
        'billing_city',
        'billing_postal_code',
        'billing_country_id',
        'shipping_address',
        'shipping_city',
        'shipping_postal_code',
        'shipping_country_id',
        'currency_id',
        'account_id',
        'payment_terms',
        'credit_limit',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'payment_terms' => 'integer',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function billingCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'billing_country_id');
    }

    public function shippingCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'shipping_country_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    // Générer le prochain numéro client
    public static function generateNumber(): string
    {
        $lastCustomer = static::orderByDesc('id')->first();
        $number = $lastCustomer ? intval(substr($lastCustomer->customer_number, 4)) + 1 : 1;
        return sprintf('CLI-%03d', $number);
    }

    // Calculer le solde client
    public function getBalanceAttribute(): float
    {
        return $this->invoices()
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->sum('amount_due');
    }

    // Nom complet
    public function getFullNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }
}
