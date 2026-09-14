<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'role',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class, 'created_by_user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPicDept(): bool
    {
        return $this->role === 'pic_dept';
    }

    public function isPicGudang(): bool
    {
        return $this->role === 'pic_gudang';
    }

    public function getRoleBadgeClassAttribute(): string
    {
        switch ($this->role) {
            case 'admin':
                return 'bg-purple-100 text-purple-800 border-purple-200';
            case 'pic_gudang':
                return 'bg-amber-100 text-amber-800 border-amber-200';
            default:
                return 'bg-blue-100 text-blue-800 border-blue-200';
        }
    }

    public function getRoleLabelAttribute(): string
    {
        switch ($this->role) {
            case 'admin':
                return 'Super Admin';
            case 'pic_gudang':
                return 'PIC Gudang';
            default:
                return 'PIC Departemen';
        }
    }
}
