<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'department_id',
        'is_preset',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_preset' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Scope to fetch document types accessible by a given department id (or global).
     */
    public function scopeForDepartment($query, $departmentId = null)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->whereNull('department_id');
            if (!empty($departmentId)) {
                $q->orWhere('department_id', $departmentId);
            }
        });
    }
}
