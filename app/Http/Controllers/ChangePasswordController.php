<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Self-service password change for any logged-in user (Laravel 7 profile.change_password).
 */
class ChangePasswordController extends Controller
{
    public function __invoke(ChangePasswordRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($request->string('current_password')->value(), $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.'])
                ->withInput($request->except('current_password', 'password', 'password_confirmation'));
        }

        // The `hashed` cast on User hashes the new password.
        $user->update(['password' => $request->string('password')->value()]);

        ActivityLogger::log(
            'PASSWORD_CHANGE',
            "Pengguna '{$user->name}' ({$user->roleLabel()}) berhasil memperbarui password akunnya secara mandiri.",
            'AUTH',
            ['user_id' => $user->id, 'email' => $user->email],
            $user->name,
            $user,
        );

        $message = 'Password Anda berhasil diperbarui.';

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return back()->with('success', $message)->with('password_success', $message);
    }
}
