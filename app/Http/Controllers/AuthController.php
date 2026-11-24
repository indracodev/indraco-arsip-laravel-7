<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login/logout with the Laravel 7 behaviour and messages. No rate limiting (owner decision);
 * every failed attempt is written to activity_logs (email, IP, user agent; never the password).
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            ActivityLogger::log(
                'LOGIN_FAILED',
                "Percobaan login gagal dengan email '{$credentials['email']}'.",
                'AUTH',
                ['attempted_email' => $credentials['email']],
            );

            // Same message whether or not the email exists.
            return back()
                ->withErrors(['email' => 'Kredensial email atau password yang dimasukkan salah.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();
        $user->loadMissing('department');

        ActivityLogger::log(
            'LOGIN',
            "Pengguna '{$user->name}' ({$user->roleLabel()}) berhasil masuk ke sistem.",
            'AUTH',
            ['email' => $user->email, 'role' => $user->role, 'department' => $user->department->name ?? 'Semua'],
            $user->name,
            $user,
        );

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Selamat datang kembali, '.$user->name);
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user instanceof User) {
            ActivityLogger::log(
                'LOGOUT',
                "Pengguna '{$user->name}' ({$user->roleLabel()}) keluar dari sistem.",
                'AUTH',
                ['email' => $user->email, 'role' => $user->role],
                $user->name,
                $user,
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }
}
