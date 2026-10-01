<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterArchive extends Model
{
    protected $fillable = [
        'department_id',
        'sub_department_id',
        'code',
        'name',
        'document_type',
        'retention_years',
        'description',
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

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }
}
