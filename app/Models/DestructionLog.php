<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestructionLog extends Model
{
    protected $fillable = [
        'archive_id',
        'proposed_by_user_id',
        'department_approval_by',
        'department_approved_at',
        'approved_by_dept_pic_id',
        'bap_number',
        'destruction_date',
        'method',
        'certificate_file',
        'scan_approval_destruction',
        'extension_reason',
        'scan_extension_form',
        'notes',
    ];

    protected $casts = [
        'destruction_date' => 'date',
        'department_approved_at' => 'datetime',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    public function departmentApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_approval_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_dept_pic_id');
    }
}
