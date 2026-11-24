<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Type berkas" per department. `department_id` is not fillable: set it explicitly in code.
 *
 * @property int $id
 * @property int $department_id
 * @property int|null $sub_department_id
 * @property string|null $code
 * @property string $name
 * @property string|null $document_type
 * @property int|null $retention_years
 */
class MasterArchive extends Model
{
    protected $table = 'master_archives';

    protected $fillable = ['sub_department_id', 'code', 'name', 'document_type', 'retention_years', 'description', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'retention_years' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<SubDepartment, $this> */
    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }
}
