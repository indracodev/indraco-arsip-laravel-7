<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * `role` and `department_id` are not fillable: set them explicitly in code.
 * (Columns are listed here because the baseline migration is raw SQL Larastan cannot read.)
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role admin | pic_dept | pic_gudang (CHECK constraint)
 * @property int|null $department_id
 * @property string|null $phone
 * @property string|null $remember_token
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PIC_DEPT = 'pic_dept';

    public const ROLE_PIC_GUDANG = 'pic_gudang';

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password', 'phone'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return HasMany<Archive, $this> */
    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class, 'created_by_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Label shown to users and written to activity logs (same text as Laravel 7). */
    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Super Admin',
            self::ROLE_PIC_GUDANG => 'PIC Gudang',
            default => 'PIC Departemen',
        };
    }
}
