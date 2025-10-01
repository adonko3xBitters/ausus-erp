<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'rate',
        'description',
        'tax_account_id',
        'is_compound',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function taxAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'tax_account_id');
    }

    // Calculer le montant de la taxe
    public function calculate($amount): float
    {
        if ($this->type === 'percentage') {
            return round($amount * ($this->rate / 100), 2);
        }

        return (float) $this->rate;
    }
}
