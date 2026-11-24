<?php

namespace App\Http\Requests;

use App\Services\ArchiveCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query string of the catalog (Laravel 7 archives.index). Only shapes are checked here;
 * what a user may see is decided by Archive::visibleTo().
 */
class ArchiveCatalogRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer'],
            'sub_department_id' => ['nullable', 'integer'],
            'document_type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'expiry_filter' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', Rule::in(ArchiveCatalog::SORTS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer'],
        ];
    }
}
