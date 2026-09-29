<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('department')->latest()->get();
        $departments = Department::all();

        return view('master.users', compact('users', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,pic_dept,pic_gudang',
            'department_id' => 'nullable|required_if:role,pic_dept|exists:departments,id',
            'phone' => 'nullable|string|max:20',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $newUser = User::create($validated);

        ActivityLogger::log(
            'USER_CREATE',
            "Menambahkan pengguna baru '{$newUser->name}' dengan hak akses role {$newUser->role_label}.",
            'USER_MANAGEMENT',
            [
                'user_id' => $newUser->id,
                'email' => $newUser->email,
                'role' => $newUser->role,
                'department_id' => $newUser->department_id,
            ],
            $newUser->name
        );

        return redirect()->route('master.users')
            ->with('success', "Pengguna {$validated['name']} berhasil ditambahkan.");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,pic_dept,pic_gudang',
            'department_id' => 'nullable|required_if:role,pic_dept|exists:departments,id',
            'phone' => 'nullable|string|max:20',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $oldRole = $user->role;
        $user->update($validated);

        ActivityLogger::log(
            'USER_UPDATE',
            "Memperbarui data pengguna '{$user->name}' (Role: {$user->role_label}).",
            'USER_MANAGEMENT',
            [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_role' => $oldRole,
                'new_role' => $user->role,
                'department_id' => $user->department_id,
            ],
            $user->name
        );

        return redirect()->route('master.users')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $userRole = $user->role_label;

        $user->delete();

        ActivityLogger::log(
            'USER_DELETE',
            "Menghapus akun pengguna '{$userName}' ({$userRole}, Email: {$userEmail}).",
            'USER_MANAGEMENT',
            ['deleted_user_email' => $userEmail, 'deleted_user_name' => $userName],
            $userName
        );

        return redirect()->route('master.users')
            ->with('success', "Pengguna {$userName} berhasil dihapus.");
    }

    public function impersonate(User $user)
    {
        $admin = auth()->user();

        if (!$admin->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat melakukan impersonasi user.');
        }

        if ($admin->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat meng-impersonasi akun Anda sendiri.');
        }

        // Store original admin ID in session if not already in impersonation mode
        if (!session()->has('impersonator_id')) {
            session(['impersonator_id' => $admin->id]);
        }

        ActivityLogger::log(
            'IMPERSONATE_START',
            "Super Admin '{$admin->name}' memulai impersonasi sebagai pengguna '{$user->name}' ({$user->role_label}).",
            'AUTH',
            [
                'admin_id' => $admin->id,
                'target_user_id' => $user->id,
                'target_role' => $user->role,
            ],
            $user->name,
            $admin
        );

        // Log in as target user
        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', "Mode Impersonasi Aktif: Anda sekarang berinteraksi sebagai {$user->name} ({$user->role_label}).");
    }

    public function leaveImpersonate()
    {
        if (!session()->has('impersonator_id')) {
            return redirect()->route('dashboard');
        }

        $adminId = session('impersonator_id');
        $admin = User::findOrFail($adminId);
        $currentUser = auth()->user();

        ActivityLogger::log(
            'IMPERSONATE_LEAVE',
            "Super Admin '{$admin->name}' mengakhiri impersonasi dari pengguna '{$currentUser->name}'.",
            'AUTH',
            [
                'admin_id' => $admin->id,
                'previous_user_id' => $currentUser->id,
            ],
            $admin->name,
            $admin
        );

        // Clear impersonator session
        session()->forget('impersonator_id');

        // Log back in as Super Admin
        auth()->login($admin);

        return redirect()->route('master.users')
            ->with('success', "Selesai Impersonasi: Anda telah kembali ke akun SuperAdmin {$admin->name}.");
    }
}
