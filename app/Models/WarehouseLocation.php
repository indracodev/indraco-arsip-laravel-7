<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A room (`location_type = room`) or rack on the 2D warehouse layout.
 */
class WarehouseLocation extends Model
{
    protected $table = 'warehouse_locations';

    protected $fillable = [
        'warehouse_id', 'room_sector', 'location_type', 'rack_code', 'shelf_code', 'box_capacity',
        'total_sap', 'boxes_per_sap', 'box_type', 'current_box_count', 'canvas_x', 'canvas_y',
        'canvas_width', 'canvas_height', 'orientation', 'rotation_angle', 'custom_color',
        'assigned_department_id', 'assigned_department_ids', 'is_booked', 'booked_by_user_id',
        'booking_notes', 'is_locked', 'is_fat_locked', 'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_booked' => 'boolean',
            'is_locked' => 'boolean',
            'is_fat_locked' => 'boolean',
            'is_active' => 'boolean',
            'assigned_department_ids' => 'array',
            'canvas_x' => 'integer',
            'canvas_y' => 'integer',
            'canvas_width' => 'integer',
            'canvas_height' => 'integer',
            'rotation_angle' => 'integer',
            'box_capacity' => 'integer',
            'current_box_count' => 'integer',
            'total_sap' => 'integer',
            'boxes_per_sap' => 'integer',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    /** @return BelongsTo<User, $this> */
    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    /** @return HasMany<WarehouseRackSlot, $this> */
    public function slots(): HasMany
    {
        return $this->hasMany(WarehouseRackSlot::class)
            ->orderBy('sap_level')->orderBy('layer')->orderBy('slot_number');
    }

    /** @return HasMany<Archive, $this> */
    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }
}
