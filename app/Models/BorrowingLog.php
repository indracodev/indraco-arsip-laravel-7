<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Penarikan (withdrawal) request. `status` and `approval_status` are not fillable:
 * transitions are set explicitly by Actions.
 *
 * @property int $id
 * @property int|null $archive_id
 * @property int $borrower_user_id
 * @property int|null $pic_gudang_id
 * @property int|null $department_approval_by
 * @property int|null $active_restore_archive_id
 * @property string $status
 * @property string $approval_status
 * @property string|null $purpose
 * @property string|null $notes
 * @property string|null $approval_file
 * @property string|null $scan_approval_borrow
 * @property string|null $approval_notes
 * @property string|null $snapshot_box_number
 * @property string|null $snapshot_title
 * @property string|null $snapshot_department
 * @property array<int, array<string, mixed>>|null $snapshot_items
 * @property Carbon|null $request_date
 * @property Carbon|null $borrow_date
 * @property Carbon|null $department_approved_at
 * @property Carbon|null $expected_return_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
