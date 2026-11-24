<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Change feed for notifications/badges. The table has `created_at` only (DB default).
 */
class SystemEvent extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'system_events';

    protected $fillable = ['event_type', 'entity_type', 'entity_id', 'department_id', 'payload'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
