<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pemusnahan (BAP). `approval_status` is not fillable: set explicitly by Actions.
 */
class DestructionLog extends Model
{
    protected $table = 'destruction_logs';

    protected $fillable = [
        'archive_id', 'proposed_by_user_id', 'department_approval_by', 'department_approved_at',
        'approved_by_dept_pic_id', 'bap_number', 'destruction_date', 'method', 'certificate_file',
        'scan_approval_destruction', 'approval_file', 'is_approval_uploaded', 'approval_notes',
        'extension_reason', 'scan_extension_form', 'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'destruction_date' => 'date',
            'department_approved_at' => 'datetime',
            'is_approval_uploaded' => 'boolean',
        ];
    }

    /** @return BelongsTo<Archive, $this> */
    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    /** @return BelongsTo<User, $this> */
    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function departmentApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_approval_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_dept_pic_id');
    }
}
