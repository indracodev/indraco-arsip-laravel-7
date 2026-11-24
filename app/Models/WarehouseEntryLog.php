<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseEntryLog extends Model
{
    protected $table = 'warehouse_entry_logs';

    protected $fillable = ['archive_id', 'pic_gudang_id', 'location_id', 'entry_date', 'notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'datetime',
        ];
    }

    /** @return BelongsTo<Archive, $this> */
    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    /** @return BelongsTo<User, $this> */
    public function picGudang(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_gudang_id');
    }

    /** @return BelongsTo<WarehouseLocation, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }
}
