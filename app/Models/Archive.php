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

    public function destructionLog(): HasOne
    {
        return $this->hasOne(DestructionLog::class);
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
            case 'pending_destruction':
                return 'Antrean Pemusnahan';
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
            case 'pending_destruction':
                return 'bg-orange-100 text-orange-800 border-orange-300';
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

        $now = Carbon::now();
        $expiry = Carbon::parse($this->retention_expiry_date);

        return $expiry->diffInDays($now, false) >= -90;
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->retention_expiry_date || $this->status === 'destroyed') {
            return false;
        }

        return Carbon::parse($this->retention_expiry_date)->isPast();
    }
}
