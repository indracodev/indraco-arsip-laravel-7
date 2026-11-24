<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One box position in a rack (5 levels x 20 boxes, codes like `B11`).
 * `status` is not fillable: set explicitly by Actions.
 *
 * @property int $id
 * @property int $slot_number
 * @property string $slot_code
 * @property string $status
 */
class WarehouseRackSlot extends Model
{
    protected $table = 'warehouse_rack_slots';

    protected $fillable = ['warehouse_location_id', 'sap_level', 'layer', 'slot_number', 'slot_code', 'archive_id', 'is_active', 'notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sap_level' => 'integer',
            'slot_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<WarehouseLocation, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    /** @return BelongsTo<Archive, $this> */
    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }
}
