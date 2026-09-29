<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubDepartment extends Model
{
    protected $fillable = [
        'sidar_id',
        'department_id',
        'code',
        'name',
        'description',
        'retention_years',
        'is_active',
    ];

    protected $casts = [
        'retention_years' => 'integer',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
