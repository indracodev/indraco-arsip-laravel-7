<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * `current_counter` is not fillable: it only moves through the atomic
 * `UPDATE ... RETURNING` of the numbering service (PROJECT_RULES_SQLITE §3.5.a).
 *
 * @property int $id
 * @property string $name
 * @property string $pattern
 * @property int $current_counter
 * @property int $padding
 * @property bool $is_active
 */
class NumberingFormat extends Model
{
    protected $table = 'numbering_formats';

    protected $fillable = ['name', 'pattern', 'padding', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_counter' => 'integer',
            'padding' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
