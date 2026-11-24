<?php

namespace App\Http\Controllers;

use App\Actions\Master\DeleteUser;
use App\Http\Requests\UserRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola User & Hak Akses (Laravel 7 UserController: index / store / update / destroy /
 * resetPassword, same messages and logs). Admin only (routes). `role` and `department_id` are
 * set explicitly (not mass assignable); passwords are hashed by the model cast and never sent
 * to the page. Impersonation: ImpersonateController.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()->with('department:id,code,name')->latest()->get()
            ->map(fn (User $u): array => $u->only(['id', 'name', 'email', 'role', 'department_id', 'phone']) + [
                'department' => $u->department?->only(['code', 'name']),
            ])->all();

        return view('users.index', [
            'users' => $users,
            'departments' => Department::query()->orderBy('code')->get(['id', 'code', 'name']),
            'currentUserId' => $request->user()?->id,
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = new User($data);
        $user->forceFill(['role' => $data['role'], 'department_id' => $data['department_id'] ?? null])->save();

        ActivityLogger::log('USER_CREATE', "Menambahkan pengguna baru '{$user->name}' dengan hak akses role {$user->roleLabel()}.", 'USER_MANAGEMENT',
            ['user_id' => $user->id] + $user->only(['email', 'role', 'department_id']), $user->name);

        return redirect()->route('master.users')->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        if ($request->user()?->is($user) && $data['role'] !== $user->role) {
            return back()->with('error', 'Anda tidak dapat mengubah peran (role) akun Anda sendiri.');
        }
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $oldRole = $user->role;
        $user->fill($data)->forceFill(['role' => $data['role'], 'department_id' => $data['department_id'] ?? null])->save();

        ActivityLogger::log('USER_UPDATE', "Memperbarui data pengguna '{$user->name}' (Role: {$user->roleLabel()}).", 'USER_MANAGEMENT',
            ['user_id' => $user->id, 'email' => $user->email, 'old_role' => $oldRole, 'new_role' => $user->role, 'department_id' => $user->department_id], $user->name);

        return redirect()->route('master.users')->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user, DeleteUser $action): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        try {
            return redirect()->route('master.users')->with('success', $action->handle($actor, $user));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** "Reset Pass" (users page) and "Ganti Password" of a PIC (Kelola Departemen, JSON). */
    public function resetPassword(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'min:6']], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 6 karakter.',
        ]);
        $user->forceFill(['password' => $request->string('password')->toString()])->save();
        /** @var User $admin */
        $admin = $request->user();

        ActivityLogger::log('USER_PASSWORD_RESET', "Super Admin '{$admin->name}' mereset password untuk pengguna '{$user->name}' ({$user->email}).", 'USER_MANAGEMENT',
            ['admin_id' => $admin->id, 'user_id' => $user->id, 'email' => $user->email], $user->name, $admin);

        $message = "Password untuk pengguna {$user->name} ({$user->email}) berhasil di-reset.";

        return $request->expectsJson() ? response()->json(['status' => 'success', 'message' => $message]) : back()->with('success', $message);
    }
}
