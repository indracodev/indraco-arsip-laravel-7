<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One archive box. Not fillable (set explicitly in Actions): `status`, `box_number`,
 * `department_id`, `created_by_user_id`.
 *
 * `content_description` stays plain text: it is free text, and Laravel 7 stores a JSON
 * draft in it only for multi-box drafts (decoded where that draft is read).
 * (Columns used by code are listed here: the baseline migration is raw SQL Larastan cannot read.)
 *
 * @property int $id
 * @property int $department_id
 * @property int|null $sub_department_id
 * @property int $created_by_user_id
 * @property string $title
 * @property string $status
 * @property string|null $box_number
 * @property string|null $document_type
 * @property string|null $periode
 * @property string|null $periode_doc
 * @property string|null $period_text
 * @property Carbon|null $period_start_date
 * @property Carbon|null $period_end_date
 * @property Carbon|null $retention_expiry_date
 * @property int|null $retention_years
 * @property int|null $masa_simpan_custom
 * @property string|null $physical_condition
 * @property string|null $rejection_note
 * @property int|null $warehouse_location_id
 * @property int|null $warehouse_rack_slot_id
 * @property string|null $file_path
 * @property string|null $scan_input_form
 * @property string|null $scan_approval_input
 * @property string|null $scan_extension_form
 * @property string|null $company_name
 * @property string|null $content_description
 * @property Carbon|null $tgl_penyerahan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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

    /**
     * Archives a user may see in lists (IDOR rule): PIC Departemen only their own department;
     * admin and PIC Gudang everything (Laravel 7 ArchiveController@index).
     *
     * @param  Builder<Archive>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->role === User::ROLE_PIC_DEPT) {
            $query->where('department_id', $user->department_id);
        }
    }

    /** Laravel 7 effective_periode. */
    public function effectivePeriode(): string
    {
        foreach ([$this->periode, $this->periode_doc, $this->period_text] as $text) {
            if (! empty($text)) {
                return (string) $text;
            }
        }
        if ($this->period_start_date && $this->period_end_date) {
            $start = $this->period_start_date->format('Y/m');
            $end = $this->period_end_date->format('Y/m');

            return $start === $end ? $start : "{$start} - {$end}";
        }

        return '-';
    }

    /** Laravel 7 display_location (needs location.warehouse and rackSlot loaded). */
    public function displayLocation(): string
    {
        if (! $this->location) {
            return 'Belum Ditentukan';
        }
        $warehouse = $this->location->warehouse ? trim($this->location->warehouse->name) : ($this->location->room_sector ?? 'Gudang');
        $name = 'Gudang '.trim((string) preg_replace('/^(Gudang\s+)+/i', '', $warehouse));
        if ($this->rackSlot) {
            return $name.' - '.($this->rackSlot->slot_code ?: 'S'.$this->rackSlot->slot_number);
        }

        return "{$name} - {$this->location->rack_code}";
    }

    /** Laravel 7 retention_display. */
    public function retentionDisplay(): string
    {
        if (preg_match('/^(\d+)\s*(?:bulan|bln)/i', trim((string) ($this->periode ?? $this->periode_doc ?? '')), $m)) {
            $months = (int) $m[1];

            return $months >= 12 && $months % 12 === 0 ? ($months / 12).' Thn' : $months.' Bulan';
        }
        foreach ([$this->masa_simpan_custom, $this->retention_years] as $years) {
            if ((int) $years > 0) {
                return $years.' Thn';
            }
        }

        return '-';
    }

    /** Number of files attached to the box (Laravel 7 catalog column BERKAS; needs borrowingLogs). */
    public function fileCount(): int
    {
        $paths = array_filter([$this->scan_input_form, $this->file_path, $this->scan_approval_input, $this->scan_extension_form]);
        foreach ($this->borrowingLogs as $log) {
            $borrowFile = $log->approval_file ?: $log->scan_approval_borrow;
            if ($borrowFile && ! in_array($borrowFile, $paths, true)) {
                return count($paths) + 1;
            }
        }

        return count($paths);
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
