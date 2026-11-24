<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One archive box. Not fillable (set explicitly in Actions): `status`, `box_number`,
 * `department_id`, `created_by_user_id`.
 *
 * `content_description` stays plain text: it is free text, and Laravel 7 stores a JSON
 * draft in it only for multi-box drafts (decoded where that draft is read).
 */
class Archive extends Model
{
    protected $table = 'archives';

    protected $fillable = [
        'sub_department_id', 'company_name', 'document_type', 'title',
        'is_custom_doc_name', 'custom_doc_name', 'periode', 'period_start_date', 'period_end_date',
        'period_text', 'period_yy_mm', 'periode_doc', 'tgl_penyerahan', 'content_description',
        'retention_years', 'masa_simpan_custom', 'retention_expiry_date', 'physical_condition',
        'file_path', 'scan_input_form', 'scan_approval_input', 'warehouse_location_id',
        'warehouse_rack_slot_id', 'rejection_note', 'extension_reason', 'scan_extension_form',
        'origin_borrowing_log_id', 're_store_snapshot',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start_date' => 'date',
            'period_end_date' => 'date',
            'tgl_penyerahan' => 'date',
            'retention_expiry_date' => 'date',
            'retention_years' => 'integer',
            'masa_simpan_custom' => 'integer',
            'is_custom_doc_name' => 'boolean',
            're_store_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<SubDepartment, $this> */
    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<WarehouseLocation, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    /** @return BelongsTo<WarehouseRackSlot, $this> */
    public function rackSlot(): BelongsTo
    {
        return $this->belongsTo(WarehouseRackSlot::class, 'warehouse_rack_slot_id');
    }

    /** @return HasMany<ArchiveItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ArchiveItem::class)->orderBy('item_number');
    }

    /** @return HasMany<WarehouseEntryLog, $this> */
    public function entryLogs(): HasMany
    {
        return $this->hasMany(WarehouseEntryLog::class);
    }

    /** @return HasMany<BorrowingLog, $this> */
    public function borrowingLogs(): HasMany
    {
        return $this->hasMany(BorrowingLog::class);
    }

    /** @return HasOne<BorrowingLog, $this> */
    public function latestBorrowingLog(): HasOne
    {
        return $this->hasOne(BorrowingLog::class)->latestOfMany();
    }

    /** @return BelongsTo<BorrowingLog, $this> */
    public function originBorrowingLog(): BelongsTo
    {
        return $this->belongsTo(BorrowingLog::class, 'origin_borrowing_log_id');
    }

    /** @return HasMany<DestructionLog, $this> */
    public function destructionLogs(): HasMany
    {
        return $this->hasMany(DestructionLog::class);
    }

    /** @return HasOne<DestructionLog, $this> */
    public function destructionLog(): HasOne
    {
        return $this->hasOne(DestructionLog::class);
    }

    /** @return HasMany<ArchiveExtensionLog, $this> */
    public function extensionLogs(): HasMany
    {
        return $this->hasMany(ArchiveExtensionLog::class);
    }

    /** @return HasOne<ArchiveExtensionLog, $this> */
    public function extensionLog(): HasOne
    {
        return $this->hasOne(ArchiveExtensionLog::class)->latestOfMany();
    }
}
