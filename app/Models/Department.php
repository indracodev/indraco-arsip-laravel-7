<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * (Columns used by code; the baseline migration is raw SQL Larastan cannot read.)
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int|null $retention_years
 * @property bool $is_active
 */
class Department extends Model
{
    protected $table = 'departments';

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

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<User, $this> */
    public function picUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_PIC_DEPT);
    }

    /** @return HasMany<SubDepartment, $this> */
    public function subDepartments(): HasMany
    {
        return $this->hasMany(SubDepartment::class);
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
