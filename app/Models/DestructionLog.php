<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pemusnahan (BAP). `approval_status` is not fillable: set explicitly by Actions.
 *
 * @property int $id
 * @property int $archive_id
 * @property int $proposed_by_user_id
 * @property int|null $approved_by_dept_pic_id
 * @property int|null $department_approval_by
 * @property string $bap_number
 * @property Carbon|null $destruction_date
 * @property string $method
 * @property string|null $certificate_file
 * @property string|null $approval_file
 * @property string|null $scan_approval_destruction
 * @property string $approval_status
 * @property string|null $approval_notes
 * @property string|null $notes
 * @property Carbon|null $created_at
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
