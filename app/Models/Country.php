<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'currency_code',
        'phone_code',
        'is_ohada',
        'is_active',
    ];

    protected $casts = [
        'is_ohada' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function companySettings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }
}
