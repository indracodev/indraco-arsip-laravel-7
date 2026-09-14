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
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }
}
