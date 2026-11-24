<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penarikan (withdrawal) request. `status` and `approval_status` are not fillable:
 * transitions are set explicitly by Actions.
 */
class BorrowingLog extends Model
{
    protected $table = 'borrowing_logs';

    protected $fillable = [
        'archive_id', 'borrower_user_id', 'department_approval_by', 'department_approved_at',
        'pic_gudang_id', 'request_date', 'borrow_date', 'expected_return_date', 'actual_return_date',
        'purpose', 'notes', 'scan_approval_borrow', 'approval_file', 'is_approval_uploaded',
        'approval_notes', 'snapshot_box_number', 'snapshot_title', 'snapshot_department',
        'snapshot_items', 'active_restore_archive_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_date' => 'datetime',
            'department_approved_at' => 'datetime',
            'borrow_date' => 'datetime',
            'expected_return_date' => 'date',
            'actual_return_date' => 'datetime',
            'is_approval_uploaded' => 'boolean',
            'snapshot_items' => 'array',
        ];
    }

    /** @return BelongsTo<Archive, $this> */
    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    /** @return BelongsTo<User, $this> */
    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function departmentApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_approval_by');
    }

    /** @return BelongsTo<User, $this> */
    public function picGudang(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_gudang_id');
    }

    /** @return BelongsTo<Archive, $this> */
    public function activeRestoreArchive(): BelongsTo
    {
        return $this->belongsTo(Archive::class, 'active_restore_archive_id');
    }
}
