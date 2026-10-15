<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveExtensionLog extends Model
{
    protected $table = 'archive_extension_logs';

    protected $fillable = [
        'archive_id',
        'proposed_by_user_id',
        'additional_months',
        'additional_years',
        'previous_expiry_date',
        'new_expiry_date',
        'extension_reason',
        'scan_extension_form',
        'approval_status',
        'approved_by_user_id',
        'approved_at',
        'rejection_note',
    ];

    protected $casts = [
        'additional_months' => 'integer',
        'additional_years' => 'float',
        'previous_expiry_date' => 'date',
        'new_expiry_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class, 'archive_id');
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function getDurationLabelAttribute(): string
    {
        $months = (int) $this->additional_months;
        if ($months % 12 === 0 && $months > 0) {
            $years = $months / 12;
            return "+{$years} Tahun";
        }
        return "+{$months} Bulan";
    }

    public function getStatusLabelAttribute(): string
    {
        switch ($this->approval_status) {
            case 'approved':
                return 'Disetujui Gudang';
            case 'rejected':
                return 'Ditolak';
            case 'pending':
            default:
                return 'Menunggu ACC Gudang';
        }
    }

    public function getStatusBadgeAttribute(): string
    {
        switch ($this->approval_status) {
            case 'approved':
                return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700';
            case 'rejected':
                return 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 border-rose-300 dark:border-rose-700';
            case 'pending':
            default:
                return 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border-amber-300 dark:border-amber-700 animate-pulse';
        }
    }
}
