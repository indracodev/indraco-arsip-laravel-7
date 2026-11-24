<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $table = 'warehouses';

    protected $fillable = ['code', 'name', 'address', 'is_fat_locked', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_fat_locked' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<WarehouseLocation, $this> */
    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }
}
