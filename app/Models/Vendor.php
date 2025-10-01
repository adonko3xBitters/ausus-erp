<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_number',
        'name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'tax_number',
        'trade_register',
        'address',
        'city',
        'postal_code',
        'country_id',
        'currency_id',
        'account_id',
        'payment_terms',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'payment_terms' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relations
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    // Générer le prochain numéro fournisseur
    public static function generateNumber(): string
    {
        $lastVendor = static::orderByDesc('id')->first();
        $number = $lastVendor ? intval(substr($lastVendor->vendor_number, 4)) + 1 : 1;
        return sprintf('FRS-%03d', $number);
    }

    // Calculer le solde fournisseur
    public function getBalanceAttribute(): float
    {
        return $this->bills()
            ->whereIn('status', ['received', 'partial', 'overdue'])
            ->sum('amount_due');
    }

    // Nom complet
    public function getFullNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }
}
