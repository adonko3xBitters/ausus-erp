<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'values',
        'is_active',
    ];

    protected $casts = [
        'values' => 'array',
        'is_active' => 'boolean',
    ];
}
