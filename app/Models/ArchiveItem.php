<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document line inside a box. Editing an item must touch() its archive (optimistic lock).
 *
 * @property int $item_number
 * @property string $document_name
 * @property string|null $period_start
 * @property string|null $period_end
 * @property string|null $period_text
 * @property string|null $notes
 */
class ArchiveItem extends Model
{
    protected $table = 'archive_items';

    protected $fillable = ['archive_id', 'item_number', 'document_name', 'period_start', 'period_end', 'period_text', 'notes'];

    /** @var list<string> */
    protected $touches = ['archive'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_number' => 'integer',
        ];
    }

    /** @return BelongsTo<Archive, $this> */
    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }
}
