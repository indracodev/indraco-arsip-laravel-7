<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `department_id` is not fillable: set it explicitly in code.
 */
class SubDepartment extends Model
{
    protected $table = 'sub_departments';

    protected $fillable = ['sidar_id', 'code', 'name', 'description', 'retention_years', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'retention_years' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return HasMany<MasterArchive, $this> */
    public function masterArchives(): HasMany
    {
        return $this->hasMany(MasterArchive::class);
    }

    /** @return HasMany<Archive, $this> */
    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }
}
