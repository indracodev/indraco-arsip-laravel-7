<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit trail. Rows are written only by App\Services\ActivityLogger.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string|null $user_role
 * @property int|null $department_id
 * @property string $action
 * @property string $module
 * @property string $description
 * @property string|null $reference_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $properties
 * @property Carbon|null $created_at
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id', 'user_name', 'user_role', 'department_id', 'action', 'module',
        'description', 'reference_id', 'ip_address', 'user_agent', 'properties',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
