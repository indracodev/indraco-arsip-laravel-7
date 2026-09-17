<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubDepartment extends Model
{
    protected $fillable = [
        'department_id',
        'code',
        'name',
        'description',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class, 'sub_department_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'sub_department_id');
    }
}
