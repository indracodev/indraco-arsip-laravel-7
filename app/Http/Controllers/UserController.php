<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
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

        User::create($validated);

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

        $user->update($validated);

        return redirect()->route('master.users')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('master.users')
            ->with('success', "Pengguna {$user->name} berhasil dihapus.");
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

        // Clear impersonator session
        session()->forget('impersonator_id');

        // Log back in as Super Admin
        auth()->login($admin);

        return redirect()->route('master.users')
            ->with('success', "Selesai Impersonasi: Anda telah kembali ke akun SuperAdmin {$admin->name}.");
    }
}
