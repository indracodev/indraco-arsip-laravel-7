<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseRackSlot extends Model
{
    protected $fillable = [
        'warehouse_location_id',
        'sap_level',
        'layer',
        'slot_number',
        'slot_code',
        'archive_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'sap_level' => 'integer',
        'slot_number' => 'integer',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    public function getLayerLabelAttribute(): string
    {
        return $this->layer === 'top' ? 'Baris Atas' : 'Baris Bawah';
    }

    public function getFullSlotNameAttribute(): string
    {
        $layerName = $this->layer === 'top' ? 'Atas' : 'Bawah';
        return "Sap {$this->sap_level} - {$layerName} [Slot {$this->slot_number}]";
    }

    public function getStatusBadgeAttribute(): string
    {
        switch ($this->status) {
            case 'filled':
                return 'bg-amber-400 text-slate-900'; // Kuning (Terisi)
            case 'expired':
                return 'bg-red-500 text-white'; // Merah (Expired)
            case 'empty':
            default:
                return 'bg-emerald-500 text-white'; // Hijau (Kosong)
        }
    }
}
