<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Static mockup of "Draft Pengajuan Box Baru" (Laravel 7 archives.create) for owner approval:
 * the Laravel 7 field composition, no submit route. Replaced by the ARSIP module (Phase 4).
 */
class ArchiveCreateMockupController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $department = Department::query()
            ->with(['subDepartments' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->find($user->department_id);

        return view('archives.create-mockup', [
            'department' => $department,
            'masterArchives' => MasterArchive::query()->where('is_active', true)
                ->where('department_id', $user->department_id)->orderBy('name')->pluck('name'),
        ]);
    }
}
