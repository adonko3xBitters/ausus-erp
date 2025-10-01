<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'category',
        'parent_id',
        'currency_id',
        'description',
        'is_sub_account',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'is_sub_account' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    // Obtenir le nom complet (avec hiérarchie)
    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_name . ' > ' . $this->name;
        }

        return $this->name;
    }

    // Obtenir le code avec le nom
    public function getDisplayNameAttribute(): string
    {
        return $this->code . ' - ' . $this->name;
    }
}
