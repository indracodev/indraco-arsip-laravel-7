<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseEntryLog extends Model
{
    protected $fillable = [
        'archive_id',
        'pic_gudang_id',
        'location_id',
        'entry_date',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    public function picGudang(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_gudang_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }
}
