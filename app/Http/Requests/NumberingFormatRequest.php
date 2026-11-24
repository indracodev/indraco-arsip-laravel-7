<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Format Penomoran Box (Laravel 7 NumberingFormatController@store / @update).
 * `original_counter` (edit form) is the counter shown when the page was opened: the counter
 * moves while boxes are numbered, so a stale form must not roll it back.
 */
class NumberingFormatRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'pattern' => ['required', 'string', 'max:255'],
            'current_counter' => ['required', 'integer', 'min:0'],
            'padding' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['nullable', 'boolean'],
            'original_counter' => ['nullable', 'integer'],
        ];
    }
}
