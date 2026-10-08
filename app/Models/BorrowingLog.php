<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowingLog extends Model
{
    protected $fillable = [
        'archive_id',
        'borrower_user_id',
        'department_approval_by',
        'department_approved_at',
        'pic_gudang_id',
        'request_date',
        'borrow_date',
        'expected_return_date',
        'actual_return_date',
        'purpose',
        'status',
        'notes',
        'scan_approval_borrow',
        'approval_file',
        'is_approval_uploaded',
        'approval_status',
        'approval_notes',
    ];

    protected $casts = [
        'request_date' => 'datetime',
        'department_approved_at' => 'datetime',
        'borrow_date' => 'datetime',
        'expected_return_date' => 'date',
        'actual_return_date' => 'datetime',
        'is_approval_uploaded' => 'boolean',
    ];

    protected static function booted()
    {
        static::created(function ($log) {
            \App\Services\SystemEventStream::emit(
                'borrowing_created',
                'BorrowingLog',
                $log->id,
                $log->archive ? $log->archive->department_id : null,
                ['status' => $log->status]
            );
        });

        static::updated(function ($log) {
            \App\Services\SystemEventStream::emit(
                'borrowing_updated',
                'BorrowingLog',
                $log->id,
                $log->archive ? $log->archive->department_id : null,
                ['status' => $log->status]
            );
        });
    }

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    public function departmentApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_approval_by');
    }

    public function picGudang(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_gudang_id');
    }

    public function getEffectiveApprovalFileAttribute(): ?string
    {
        return $this->approval_file ?: $this->scan_approval_borrow;
    }

    public function getEffectiveApprovalFileUrlAttribute(): ?string
    {
        return app_storage_url($this->effective_approval_file);
    }

    public function getStatusLabelAttribute(): string
    {
        switch ($this->status) {
            case 'requested':
                return 'Diajukan User';
            case 'dept_approved':
                return 'Disetujui Dept';
            case 'approved':
                return 'Disetujui Gudang';
            case 'dispatched':
                return empty($this->expected_return_date) ? 'Diambil (Permanen)' : 'Sedang Dipinjam';
            case 'returned':
                return 'Dikembalikan';
            case 'rejected':
                return 'Ditolak';
            default:
                return 'Unknown';
        }
    }

    public function getStatusBadgeAttribute(): string
    {
        switch ($this->status) {
            case 'requested':
                return 'bg-amber-100 text-amber-800 border-amber-200';
            case 'dept_approved':
                return 'bg-cyan-100 text-cyan-800 border-cyan-200';
            case 'approved':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'dispatched':
                return 'bg-purple-100 text-purple-800 border-purple-200';
            case 'returned':
                return 'bg-emerald-100 text-emerald-800 border-emerald-200';
            case 'rejected':
                return 'bg-rose-100 text-rose-800 border-rose-200';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    public function getApprovalFileUrlAttribute(): ?string
    {
        return app_storage_url($this->approval_file ?: $this->scan_approval_borrow);
    }
}
