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
    ];

    protected $casts = [
        'is_booked' => 'boolean',
        'is_locked' => 'boolean',
        'canvas_x' => 'integer',
        'canvas_y' => 'integer',
        'canvas_width' => 'integer',
        'canvas_height' => 'integer',
        'rotation_angle' => 'integer',
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

    public function getFullLocationAttribute(): string
    {
        $whName = $this->warehouse ? $this->warehouse->name : 'Gudang';
        $sector = $this->room_sector ? "[{$this->room_sector}] " : '';
        return "{$whName} - {$sector}{$this->rack_code} / {$this->shelf_code}";
    }

    public function getCapacityPercentageAttribute(): float
    {
        if ($this->box_capacity <= 0) return 0;
        return round(($this->current_box_count / $this->box_capacity) * 100, 1);
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
