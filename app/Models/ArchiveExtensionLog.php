<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perpanjangan masa simpan. `approval_status` is not fillable: set explicitly by Actions.
 */
class ArchiveExtensionLog extends Model
{
    protected $table = 'archive_extension_logs';

    protected $fillable = [
        'archive_id', 'proposed_by_user_id', 'additional_months', 'additional_years',
        'previous_expiry_date', 'new_expiry_date', 'extension_reason', 'scan_extension_form',
        'approved_by_user_id', 'approved_at', 'rejection_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'additional_months' => 'integer',
            'additional_years' => 'float',
            'previous_expiry_date' => 'date',
            'new_expiry_date' => 'date',
            'approved_at' => 'datetime',
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
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
