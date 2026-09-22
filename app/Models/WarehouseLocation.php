<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLocation extends Model
{
    protected $fillable = [
        'warehouse_id',
        'room_sector',
        'location_type',
        'rack_code',
        'shelf_code',
        'box_capacity',
        'total_sap',
        'boxes_per_sap',
        'box_type',
        'current_box_count',
        'canvas_x',
        'canvas_y',
        'canvas_width',
        'canvas_height',
        'orientation',
        'rotation_angle',
        'custom_color',
        'assigned_department_id',
        'is_booked',
        'booked_by_user_id',
        'booking_notes',
        'is_locked',
        'is_fat_locked',
    ];

    protected $casts = [
        'is_booked' => 'boolean',
        'is_locked' => 'boolean',
        'is_fat_locked' => 'boolean',
        'canvas_x' => 'integer',
        'canvas_y' => 'integer',
        'canvas_width' => 'integer',
        'canvas_height' => 'integer',
        'rotation_angle' => 'integer',
        'box_capacity' => 'integer',
        'total_sap' => 'integer',
        'boxes_per_sap' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(WarehouseRackSlot::class, 'warehouse_location_id')->orderBy('sap_level', 'desc')->orderBy('layer', 'asc')->orderBy('slot_number', 'asc');
    }

    /**
     * Generate 100 standard slots for TB 30g box (5 sap x 20 box: 10 atas, 10 bawah)
     */
    public function generateStandardSlots(): void
    {
        if ($this->location_type !== 'rack') {
            return;
        }

        $existingCount = $this->slots()->count();
        if ($existingCount >= 100) {
            return;
        }

        $slotsToInsert = [];
        $now = now();

        // 5 Sap (Sap 5 di atas s/d Sap 1 di bawah)
        for ($sap = 1; $sap <= 5; $sap++) {
            // Layer Top (10 slot)
            for ($slot = 1; $slot <= 10; $slot++) {
                $padSlot = str_pad($slot, 2, '0', STR_PAD_LEFT);
                $slotsToInsert[] = [
                    'warehouse_location_id' => $this->id,
                    'sap_level' => $sap,
                    'layer' => 'top',
                    'slot_number' => $slot,
                    'slot_code' => "SAP-{$sap}-T{$padSlot}",
                    'status' => 'empty',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            // Layer Bottom (10 slot)
            for ($slot = 1; $slot <= 10; $slot++) {
                $padSlot = str_pad($slot, 2, '0', STR_PAD_LEFT);
                $slotsToInsert[] = [
                    'warehouse_location_id' => $this->id,
                    'sap_level' => $sap,
                    'layer' => 'bottom',
                    'slot_number' => $slot,
                    'slot_code' => "SAP-{$sap}-B{$padSlot}",
                    'status' => 'empty',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        WarehouseRackSlot::insert($slotsToInsert);
    }

    public function getFullLocationAttribute(): string
    {
        $whName = $this->warehouse ? $this->warehouse->name : 'Gudang';
        $sector = $this->room_sector ? "[{$this->room_sector}] " : '';
        return "{$whName} - {$sector}{$this->rack_code} / {$this->shelf_code}";
    }

    public function getCapacityPercentageAttribute(): float
    {
        $capacity = $this->box_capacity ?: 100;
        if ($capacity <= 0) return 0;
        return round(($this->current_box_count / $capacity) * 100, 1);
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->custom_color) {
            return $this->custom_color;
        }
        $pct = $this->capacity_percentage;
        if ($pct > 90) return '#ef4444'; // Merah (91% - 100%)
        if ($pct > 80) return '#f97316'; // Orange (81% - 90%)
        if ($pct > 50) return '#eab308'; // Kuning (51% - 80%)
        return '#10b981'; // Hijau (0% - 50%)
    }
}
