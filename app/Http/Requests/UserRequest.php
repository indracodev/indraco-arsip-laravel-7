<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Tambah / Edit User (Laravel 7 UserController@store / @update) and the "Buat Akun PIC Baru"
 * form of Kelola Departemen (DepartmentController@assignPic). E-mail is unique after
 * User::normalizeEmail(), the same rule as the UNIQUE index lower(trim(email)) (decisions R4).
 * Edit: an empty password keeps the old one.
 */
class UserRequest extends FormRequest
{
    public const ROLES = [
        User::ROLE_PIC_DEPT => 'PIC Departemen Client',
        User::ROLE_PIC_GUDANG => 'PIC Gudang Arsip (Curator)',
        User::ROLE_ADMIN => 'Super Admin / Management',
    ];

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => User::normalizeEmail($this->input('email'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $ignoreId = $user instanceof User ? $user->id : 0;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', function (string $attribute, mixed $value, Closure $fail) use ($ignoreId): void {
                if (User::query()->whereRaw('lower(trim(email)) = ?', [$value])->whereKeyNot($ignoreId)->exists()) {
                    $fail('validation.unique')->translate();
                }
            }],
            'password' => [$user instanceof User ? 'nullable' : 'required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        if ($this->routeIs('master.departments.pic.assign')) {
            return $rules;
        }

        return $rules + [
            'role' => ['required', 'in:'.implode(',', array_keys(self::ROLES))],
            'department_id' => ['nullable', 'required_if:role,'.User::ROLE_PIC_DEPT, 'integer', 'exists:departments,id'],
        ];
    }
}
