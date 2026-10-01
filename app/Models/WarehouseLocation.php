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
        'is_active',
    ];

    protected $casts = [
        'is_booked' => 'boolean',
        'is_locked' => 'boolean',
        'is_fat_locked' => 'boolean',
        'is_active' => 'boolean',
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

    public function getRackIdentifierAttribute(): string
    {
        // Extracts 'B' from 'RAK-R1-B', 'J' from 'RAK-R2-J', 'BI' from 'RAK-R7-BI', etc.
        $clean = preg_replace('/^RAK-(?:R\d+-)?/i', '', $this->rack_code);
        return $clean ?: $this->rack_code;
    }

    /**
     * Generate 100 standard slots for TB 30g box (5 LVL x 20 box: 10 atas, 10 bawah)
     * Penomoran sesuai standar urutan kardus denah (1 s/d 100):
     * LVL 1: Bawah 1..10, Atas 11..20
     * LVL 2: Bawah 21..30, Atas 31..40
     * LVL 3: Bawah 41..50, Atas 51..60
     * LVL 4: Bawah 61..70, Atas 71..80
     * LVL 5: Bawah 81..90, Atas 91..100
     * Contoh Kode Slot: Rak B box 11 => 'B11'
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
        $rackId = $this->rack_identifier;

        // 5 LVL (LVL 1 di bawah s/d LVL 5 di atas)
        for ($sap = 1; $sap <= 5; $sap++) {
            // Layer Bottom (10 slot: LVL 1 = 1..10, LVL 2 = 21..30, LVL 3 = 41..50, LVL 4 = 61..70, LVL 5 = 81..90)
            for ($slot = 1; $slot <= 10; $slot++) {
                $boxNum = ($sap - 1) * 20 + $slot;
                $slotsToInsert[] = [
                    'warehouse_location_id' => $this->id,
                    'sap_level' => $sap,
                    'layer' => 'bottom',
                    'slot_number' => $boxNum,
                    'slot_code' => "{$rackId}{$boxNum}",
                    'status' => 'empty',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Layer Top (10 slot: LVL 1 = 11..20, LVL 2 = 31..40, LVL 3 = 51..60, LVL 4 = 71..80, LVL 5 = 91..100)
            for ($slot = 1; $slot <= 10; $slot++) {
                $boxNum = ($sap - 1) * 20 + 10 + $slot;
                $slotsToInsert[] = [
                    'warehouse_location_id' => $this->id,
                    'sap_level' => $sap,
                    'layer' => 'top',
                    'slot_number' => $boxNum,
                    'slot_code' => "{$rackId}{$boxNum}",
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
        $whRaw = $this->warehouse ? trim($this->warehouse->name) : 'Gudang';
        $cleanWh = preg_replace('/^(Gudang\s+)+/i', '', $whRaw);
        $whName = 'Gudang ' . trim($cleanWh);

        $sector = $this->room_sector ? "[{$this->room_sector}] " : '';
        $shelf = $this->shelf_code ? " / {$this->shelf_code}" : '';
        return "{$whName} - {$sector}{$this->rack_code}{$shelf}";
    }

    /**
     * Accurately calculate actual filled box count in this rack
     */
    public function getCurrentBoxCountAttribute($value): int
    {
        if ($this->relationLoaded('slots')) {
            return $this->slots->filter(function ($s) {
                return !empty($s->archive_id) || $s->status === 'filled';
            })->count();
        }

        if ($this->relationLoaded('archives')) {
            return $this->archives->where('status', '!=', 'destroyed')->count();
        }

        $filledSlots = $this->slots()->where(function ($q) {
            $q->whereNotNull('archive_id')->orWhere('status', 'filled');
        })->count();

        $actualArchives = $this->archives()->where('status', '!=', 'destroyed')->count();

        return max($filledSlots, $actualArchives);
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
