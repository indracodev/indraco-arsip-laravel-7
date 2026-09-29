<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            $user = Auth::user();
            $request->session()->regenerate();

            ActivityLogger::log(
                'LOGIN',
                "Pengguna '{$user->name}' ({$user->role_label}) berhasil masuk ke sistem.",
                'AUTH',
                [
                    'email' => $user->email,
                    'role' => $user->role,
                    'department' => $user->department ? $user->department->name : 'Semua',
                ],
                $user->name,
                $user
            );

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . $user->name);
        }

        ActivityLogger::log(
            'LOGIN_FAILED',
            "Percobaan login gagal dengan email '{$request->email}'.",
            'AUTH',
            ['attempted_email' => $request->email]
        );

        return back()->withErrors([
            'email' => 'Kredensial email atau password yang dimasukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            ActivityLogger::log(
                'LOGOUT',
                "Pengguna '{$user->name}' ({$user->role_label}) keluar dari sistem.",
                'AUTH',
                ['email' => $user->email, 'role' => $user->role],
                $user->name,
                $user
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }
}
