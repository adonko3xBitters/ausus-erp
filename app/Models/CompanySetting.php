<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'legal_form',
        'tax_number',
        'trade_register',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country_id',
        'currency_id',
        'logo',
        'fiscal_regime',
        'fiscal_year_end',
    ];

    protected $casts = [
        'fiscal_year_end' => 'date',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    // Obtenir les paramètres de l'entreprise (singleton)
    public static function get()
    {
        return static::first() ?? static::create([
            'company_name' => config('app.name'),
            'country_id' => 1, // Par défaut Côte d'Ivoire
            'currency_id' => 1, // Par défaut FCFA
        ]);
    }
}
