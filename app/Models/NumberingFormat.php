<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberingFormat extends Model
{
    protected $fillable = [
        'name',
        'pattern',
        'current_counter',
        'padding',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_counter' => 'integer',
        'padding' => 'integer',
    ];
}
