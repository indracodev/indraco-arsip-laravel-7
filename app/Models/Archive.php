<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Archive extends Model
{
    protected $fillable = [
        'box_number',
        'department_id',
        'sub_department_id',
        'company_name',
        'document_type',
        'created_by_user_id',
        'title',
        'is_custom_doc_name',
        'custom_doc_name',
        'periode',
        'period_start_date',
        'period_end_date',
        'period_text',
        'period_yy_mm',
        'periode_doc',
        'tgl_penyerahan',
        'content_description',
        'retention_years',
        'masa_simpan_custom',
        'retention_expiry_date',
        'physical_condition',
        'file_path',
        'scan_input_form',
        'scan_approval_input',
        'warehouse_location_id',
        'warehouse_rack_slot_id',
        'status',
        'rejection_note',
        'extension_reason',
        'scan_extension_form',
        'origin_borrowing_log_id',
        're_store_snapshot',
    ];

    protected $appends = [
        'full_slot_location',
        'short_location',
        'rack_label',
        'display_location',
        'formatted_expiry_date',
        'formatted_tgl_penyerahan',
        'retention_display',
        'retention_duration_label',
        'scan_input_form_url',
        'file_path_url',
        'scan_approval_input_url',
        'scan_extension_form_url',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'tgl_penyerahan' => 'date',
        'retention_expiry_date' => 'date',
        'retention_years' => 'integer',
        'masa_simpan_custom' => 'integer',
        'is_custom_doc_name' => 'boolean',
    ];

    protected static function booted()
    {
        static::created(function ($archive) {
            \App\Services\SystemEventStream::emit(
                'archive_created',
                'Archive',
                $archive->id,
                $archive->department_id,
                ['box_number' => $archive->box_number, 'status' => $archive->status, 'title' => $archive->title]
            );
        });

        static::updated(function ($archive) {
            \App\Services\SystemEventStream::emit(
                'archive_updated',
                'Archive',
                $archive->id,
                $archive->department_id,
                ['box_number' => $archive->box_number, 'status' => $archive->status, 'changes' => array_keys($archive->getChanges())]
            );
        });

        static::deleted(function ($archive) {
            \App\Services\SystemEventStream::emit(
                'archive_deleted',
                'Archive',
                $archive->id,
                $archive->department_id
            );
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    public function rackSlot(): BelongsTo
    {
        return $this->belongsTo(WarehouseRackSlot::class, 'warehouse_rack_slot_id');
    }

    public function entryLogs(): HasMany
    {
        return $this->hasMany(WarehouseEntryLog::class);
    }

    public function borrowingLogs(): HasMany
    {
        return $this->hasMany(BorrowingLog::class);
    }

    public function latestBorrowingLog(): HasOne
    {
        return $this->hasOne(BorrowingLog::class)->orderBy('id', 'desc');
    }

    public function originBorrowingLog(): BelongsTo
    {
        return $this->belongsTo(BorrowingLog::class, 'origin_borrowing_log_id');
    }

    public function destructionLog(): HasOne
    {
        return $this->hasOne(DestructionLog::class);
    }

    public function destructionLogs(): HasMany
    {
        return $this->hasMany(DestructionLog::class);
    }

    public function extensionLog(): HasOne
    {
        return $this->hasOne(ArchiveExtensionLog::class, 'archive_id')->latest('id');
    }

    public function extensionLogs(): HasMany
    {
        return $this->hasMany(ArchiveExtensionLog::class, 'archive_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ArchiveItem::class)->orderBy('item_number', 'asc');
    }

    public function getFormattedItemsSummaryAttribute(): string
    {
        if ($this->items->isEmpty()) {
            return (string) ($this->content_description ?? '-');
        }
        return (string) $this->items->map(function ($item, $idx) {
            $p = $item->period_text ? " ({$item->period_text})" : "";
            return ($idx + 1) . ". {$item->document_name}{$p}";
        })->implode("\n");
    }

    public function getEffectiveTitleAttribute(): string
    {
        if ($this->is_custom_doc_name && !empty($this->custom_doc_name)) {
            return $this->custom_doc_name;
        }
        return $this->title;
    }

    public function getEffectiveRetentionYearsAttribute(): int
    {
        if ($this->masa_simpan_custom !== null && $this->masa_simpan_custom > 0) {
            return (int) $this->masa_simpan_custom;
        }
        if ($this->retention_years !== null && $this->retention_years > 0) {
            return (int) $this->retention_years;
        }
        if ($this->subDepartment && $this->subDepartment->retention_years) {
            return (int) $this->subDepartment->retention_years;
        }
        if ($this->department && $this->department->retention_years) {
            return (int) $this->department->retention_years;
        }
        return 5;
    }

    public function getFullSlotLocationAttribute(): string
    {
        if (!$this->location) {
            return 'Belum Ada Lokasi';
        }
        $whName = $this->location->warehouse ? $this->location->warehouse->name : 'Gudang';
        $sector = $this->location->room_sector ? "[{$this->location->room_sector}] " : '';
        $rackCode = $this->location->rack_code;

        if ($this->rackSlot) {
            $slotCode = $this->rackSlot->slot_code;
            $sapNum = $this->rackSlot->sap_level;
            $layerLabel = $this->rackSlot->layer === 'top' ? 'Baris Atas' : 'Baris Bawah';
            $slotNo = str_pad($this->rackSlot->slot_number, 2, '0', STR_PAD_LEFT);
            $boxIdx = ($sapNum - 1) * 20 + ($this->rackSlot->layer === 'bottom' ? 0 : 10) + $this->rackSlot->slot_number;

            return "{$whName} - {$sector}{$rackCode} / {$slotCode} (Sap {$sapNum}, {$layerLabel} - Slot {$slotNo} / Box #{$boxIdx})";
        }

        return "{$whName} - {$sector}{$rackCode} / {$this->location->shelf_code}";
    }

    public function getRackLabelAttribute(): string
    {
        if (!$this->location && !$this->rackSlot) {
            return '';
        }

        // Kolom / Huruf Rak (e.g. BW, BK, CB, AS, B, dll)
        $rackCol = '';
        if ($this->rackSlot && !empty($this->rackSlot->slot_code)) {
            if (preg_match('/^([A-Za-z]+)/', $this->rackSlot->slot_code, $m)) {
                $rackCol = strtoupper($m[1]);
            }
        }
        if (!$rackCol && $this->location && !empty($this->location->rack_code)) {
            $clean = preg_replace('/^(?:RAK-)?(?:R\d+-)?/i', '', $this->location->rack_code);
            if (preg_match('/([A-Za-z]+)/', $clean, $m)) {
                $rackCol = strtoupper($m[1]);
            }
        }

        // Slot / Nomor Urut Box (2 digit: 01, 02, ..., 12, etc.)
        $slotNumStr = '';
        if ($this->rackSlot && $this->rackSlot->slot_number !== null) {
            $slotNumStr = str_pad($this->rackSlot->slot_number, 2, '0', STR_PAD_LEFT);
        } elseif ($this->location && !empty($this->location->shelf_code) && preg_match('/(\d+)/', $this->location->shelf_code, $m)) {
            $slotNumStr = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }

        if ($rackCol && $slotNumStr) {
            return "{$rackCol} {$slotNumStr}";
        } elseif ($rackCol) {
            return $rackCol;
        }

        return $slotNumStr;
    }

    public function getShortLocationAttribute(): string
    {
        if (!$this->location) {
            return 'Belum Ada Lokasi';
        }

        $sector = $this->location->room_sector;
        if (!$sector && $this->location->warehouse) {
            if (preg_match('/GUDANG\s+([A-Z0-9]+)/i', $this->location->warehouse->name, $matches)) {
                $sector = strtoupper($matches[1]);
            } else {
                $sector = $this->location->warehouse->code;
            }
        }
        if (!$sector) {
            $sector = 'R1';
        }

        $rackLabel = $this->rack_label;
        if (!empty($rackLabel)) {
            return "{$sector} - {$rackLabel}";
        }

        return $sector;
    }

    public function getFormattedExpiryDateAttribute(): string
    {
        return $this->retention_expiry_date ? Carbon::parse($this->retention_expiry_date)->format('M Y') : '-';
    }

    public function getRetentionDisplayAttribute(): string
    {
        $periodStr = $this->periode ?? $this->periode_doc ?? '';
        if (preg_match('/^(\d+)\s*(?:bulan|bln)/i', trim($periodStr), $matches)) {
            $months = (int) $matches[1];
            if ($months % 12 === 0 && $months >= 12) {
                return ($months / 12) . ' Thn';
            }
            return $months . ' Bulan';
        }

        if (!empty($this->masa_simpan_custom) && (int)$this->masa_simpan_custom > 0) {
            return $this->masa_simpan_custom . ' Thn';
        }

        if (isset($this->retention_years) && (int)$this->retention_years > 0) {
            return $this->retention_years . ' Thn';
        }

        return '-';
    }

    public function getRetentionDurationLabelAttribute(): string
    {
        $periodStr = $this->periode ?? $this->periode_doc ?? '';
        if (preg_match('/^(\d+)\s*(?:bulan|bln)/i', trim($periodStr), $matches)) {
            $months = (int) $matches[1];
            if ($months % 12 === 0 && $months >= 12) {
                return ($months / 12) . ' Tahun';
            }
            return $months . ' Bulan';
        }

        if (!empty($this->masa_simpan_custom) && (int)$this->masa_simpan_custom > 0) {
            return $this->masa_simpan_custom . ' Tahun';
        }

        if (isset($this->retention_years) && (int)$this->retention_years > 0) {
            return $this->retention_years . ' Tahun';
        }

        return '-';
    }

    public function getLatestApprovedExtensionAttribute(): ?ArchiveExtensionLog
    {
        if ($this->relationLoaded('extensionLogs')) {
            return $this->extensionLogs->where('approval_status', 'approved')->sortByDesc('id')->first();
        }

        return $this->extensionLogs()->where('approval_status', 'approved')->latest('id')->first();
    }

    public function getFormattedTglPenyerahanAttribute(): string
    {
        return $this->tgl_penyerahan ? Carbon::parse($this->tgl_penyerahan)->format('d M Y') : '-';
    }

    public function getScanInputFormUrlAttribute(): ?string
    {
        return app_storage_url($this->scan_input_form);
    }

    public function getFilePathUrlAttribute(): ?string
    {
        return app_storage_url($this->file_path);
    }

    public function getScanApprovalInputUrlAttribute(): ?string
    {
        return app_storage_url($this->scan_approval_input);
    }

    public function getScanExtensionFormUrlAttribute(): ?string
    {
        return app_storage_url($this->scan_extension_form);
    }

    public function getEffectivePeriodeAttribute(): string
    {
        if (!empty($this->periode)) {
            return (string) $this->periode;
        }
        if (!empty($this->periode_doc)) {
            return (string) $this->periode_doc;
        }
        if (!empty($this->period_text)) {
            return (string) $this->period_text;
        }
        if ($this->period_start_date && $this->period_end_date) {
            $s = Carbon::parse($this->period_start_date)->format('Y/m');
            $e = Carbon::parse($this->period_end_date)->format('Y/m');
            return $s === $e ? $s : "{$s} - {$e}";
        }
        return '-';
    }

    public function getDisplayLocationAttribute(): string
    {
        if (!$this->location) {
            return 'Belum Ditentukan';
        }

        $whRaw = $this->location->warehouse ? trim($this->location->warehouse->name) : ($this->location->room_sector ?? 'Gudang');
        $cleanWh = preg_replace('/^(Gudang\s+)+/i', '', $whRaw);
        $whName = 'Gudang ' . trim($cleanWh);

        if ($this->rackSlot && !empty($this->rackSlot->slot_code)) {
            return "{$whName} - {$this->rackSlot->slot_code}";
        }

        if ($this->rackSlot) {
            return "{$whName} - S{$this->rackSlot->slot_number}";
        }

        return "{$whName} - {$this->location->rack_code}";
    }

    public function getStatusLabelAttribute(): string
    {
        switch ($this->status) {
            case 'draft':
                return 'Draft Usulan';
            case 'pending_verification':
                return 'Menunggu Verifikasi';
            case 'approved_booked':
                return 'Booking Disetujui';
            case 'in_warehouse':
                return 'Tersimpan di Gudang';
            case 'borrowed':
                return 'Sedang Dipinjam';
            case 'taken':
                return 'Telah Diambil (Permanen)';
            case 'pending_destruction':
                if ($this->relationLoaded('destructionLog') && $this->destructionLog) {
                    return $this->destructionLog->approval_status === 'approved' ? 'Antrean Pemusnahan' : 'Pengajuan Pemusnahan';
                }
                return 'Pengajuan Pemusnahan';
            case 'pending_extension':
                return 'Pengajuan Perpanjangan';
            case 'destroyed':
                return 'Telah Dimusnahkan';
            default:
                return 'Unknown';
        }
    }

    public function getStatusBadgeAttribute(): string
    {
        switch ($this->status) {
            case 'draft':
                return 'bg-gray-100 text-gray-700 border-gray-200';
            case 'pending_verification':
                return 'bg-amber-100 text-amber-800 border-amber-300 animate-pulse';
            case 'approved_booked':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'in_warehouse':
                return 'bg-emerald-100 text-emerald-800 border-emerald-300';
            case 'borrowed':
                return 'bg-indigo-100 text-indigo-800 border-indigo-200';
            case 'taken':
                return 'bg-purple-100 text-purple-800 border-purple-300';
            case 'pending_destruction':
                if ($this->relationLoaded('destructionLog') && $this->destructionLog && $this->destructionLog->approval_status === 'approved') {
                    return 'bg-blue-100 text-blue-800 border-blue-300';
                }
                return 'bg-amber-100 text-amber-800 border-amber-300';
            case 'pending_extension':
                return 'bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300 border-purple-300 dark:border-purple-700 animate-pulse';
            case 'destroyed':
                return 'bg-rose-100 text-rose-800 border-rose-200';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->retention_expiry_date || $this->status === 'destroyed') {
            return false;
        }

        $expiryEndOfMonth = Carbon::parse($this->retention_expiry_date)->endOfMonth()->endOfDay();

        return Carbon::now()->lte($expiryEndOfMonth) && Carbon::today()->diffInDays($expiryEndOfMonth, false) <= 90;
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->retention_expiry_date || $this->status === 'destroyed') {
            return false;
        }

        $expiryEndOfMonth = Carbon::parse($this->retention_expiry_date)->endOfMonth()->endOfDay();

        return Carbon::now()->gt($expiryEndOfMonth);
    }

    public function getIsNearExpAttribute(): bool
    {
        if (!$this->retention_expiry_date || $this->status === 'destroyed' || $this->is_expired) {
            return false;
        }

        $expiryEndOfMonth = Carbon::parse($this->retention_expiry_date)->endOfMonth()->endOfDay();
        $h30Threshold = $expiryEndOfMonth->copy()->subDays(30)->startOfDay();

        return Carbon::today()->gte($h30Threshold);
    }

    /**
     * Hitung jumlah berkas yang memerlukan tindakan perpanjangan / pemusnahan (H-30 & Expired)
     */
    public static function getForm3PendingCount(?User $user = null): int
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return 0;
        }

        $today = Carbon::today();

        return static::query()
            ->select('id', 'retention_expiry_date', 'status', 'department_id')
            ->where('status', '!=', 'destroyed')
            ->whereNotNull('retention_expiry_date')
            ->when($user->isPicDept(), function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->get()
            ->filter(function ($arc) use ($today) {
                $expiryEndOfMonth = Carbon::parse($arc->retention_expiry_date)->endOfMonth()->endOfDay();
                $h30Threshold = $expiryEndOfMonth->copy()->subDays(30)->startOfDay();
                return $today->gte($h30Threshold);
            })
            ->count();
    }
}
