<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'is_fat_locked',
        'is_active',
    ];

    protected $casts = [
        'is_fat_locked' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }
}
