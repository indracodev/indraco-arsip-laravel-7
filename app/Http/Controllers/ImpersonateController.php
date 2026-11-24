<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Login As" (Laravel 7 UserController@impersonate / @leaveImpersonate, decisions ASK 6):
 * admin only (route), logged as IMPERSONATE_START / IMPERSONATE_LEAVE. The admin id is kept in
 * the session (`impersonator_id`); the banner in the layout offers "Kembali ke SuperAdmin".
 * No escalation: the target is never an admin (Laravel 7 allowed another admin account), so an
 * impersonated session never reaches the admin routes, and leave only returns to an account
 * that is still an admin. The session id is regenerated on every switch.
 */
class ImpersonateController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, User $user): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        if ($admin->is($user)) {
            return back()->with('error', 'Anda tidak dapat meng-impersonasi akun Anda sendiri.');
        }
        if ($user->isAdmin()) {
            return back()->with('error', 'Akun Super Admin lain tidak dapat di-impersonasi.');
        }

        ActivityLogger::log('IMPERSONATE_START', "Super Admin '{$admin->name}' memulai impersonasi sebagai pengguna '{$user->name}' ({$user->roleLabel()}).", 'AUTH',
            ['admin_id' => $admin->id, 'target_user_id' => $user->id, 'target_role' => $user->role], $user->name, $admin);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $admin->id);

        return redirect()->route('dashboard')
            ->with('success', "Mode Impersonasi Aktif: Anda sekarang berinteraksi sebagai {$user->name} ({$user->roleLabel()}).");
    }

    public function leave(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull(self::SESSION_KEY);
        if ($adminId === null) {
            return redirect()->route('dashboard');
        }
        /** @var User $current */
        $current = $request->user();
        $admin = User::query()->find($adminId);
        if (! $admin instanceof User || ! $admin->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        ActivityLogger::log('IMPERSONATE_LEAVE', "Super Admin '{$admin->name}' mengakhiri impersonasi dari pengguna '{$current->name}'.", 'AUTH',
            ['admin_id' => $admin->id, 'previous_user_id' => $current->id], $admin->name, $admin);

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('master.users')->with('success', "Selesai Impersonasi: Anda telah kembali ke akun SuperAdmin {$admin->name}.");
    }
}
