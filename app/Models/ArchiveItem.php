<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveItem extends Model
{
    protected $fillable = [
        'archive_id',
        'item_number',
        'document_name',
        'period_start',
        'period_end',
        'period_text',
        'notes',
    ];

    protected $casts = [
        'item_number' => 'integer',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }
}
