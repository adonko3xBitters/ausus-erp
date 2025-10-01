<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Vérifier si une date est dans cet exercice
    public function containsDate($date): bool
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;

        return $date->between($this->start_date, $this->end_date);
    }

    // Obtenir l'exercice actif
    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }
}
