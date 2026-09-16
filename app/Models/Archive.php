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
        'company_name',
        'document_type',
        'created_by_user_id',
        'title',
        'period_start_date',
        'period_end_date',
        'period_text',
        'period_yy_mm',
        'content_description',
        'retention_years',
        'retention_expiry_date',
        'physical_condition',
        'file_path',
        'scan_input_form',
        'scan_approval_input',
        'warehouse_location_id',
        'status',
        'rejection_note',
        'extension_reason',
        'scan_extension_form',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'retention_expiry_date' => 'date',
        'retention_years' => 'integer',
    ];

    public function getDocumentTypesAttribute(): array
    {
        $raw = $this->attributes['document_type'] ?? null;
        if (empty($raw)) {
            return ['UMUM'];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return !empty($decoded) ? $decoded : ['UMUM'];
        }

        if (strpos($raw, ',') !== false) {
            $parts = array_filter(array_map('trim', explode(',', $raw)));
            return !empty($parts) ? $parts : ['UMUM'];
        }

        return [$raw];
    }

    public function getDocumentTypeFormattedAttribute(): string
    {
        $types = $this->document_types;
        if (empty($types)) {
            return 'UMUM';
        }
        return implode(', ', $types);
    }

    public function getDocumentTypeAttribute($value): ?string
    {
        if (empty($value)) {
            return 'UMUM';
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return implode(', ', $decoded);
        }

        return $value;
    }

    public function setDocumentTypeAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['document_type'] = json_encode(array_values(array_filter($value)));
        } else {
            $this->attributes['document_type'] = $value;
        }
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
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
