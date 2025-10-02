<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_base',
        'is_active',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    // Obtenir le taux de change pour une date donnée
    public function getExchangeRate($date = null)
    {
        if ($this->is_base) {
            return 1;
        }

        $date = $date ?? now();

        return $this->exchangeRates()
            ->whereDate('date', '<=', $date)
            ->orderByDesc('date')
            ->first()?->rate ?? 1;
    }

    public function scopeDefault($query)
    {
        return $query->where('is_base', true)->first();
    }
}
