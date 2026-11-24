<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\SubDepartment;
use App\Models\User;
use App\Support\StatusLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON for the Master Departemen page (Laravel 7 DepartmentController: apiGetManageData,
 * apiGetDepartmentArchives, apiGetSubDepartmentArchives). Admin only (routes): Laravel 7
 * served them to every signed-in user (findings #5). Users are sent with the fields the page
 * shows only (id, name, email, phone, role), never the whole model.
 */
class DepartmentApiController extends Controller
{
    /** ponytail: one drill-down list is capped; paginate if a department ever holds more boxes. */
    private const MAX_BOXES = 500;

    public function manageData(Department $department): JsonResponse
    {
        $users = User::query()->where('role', '!=', User::ROLE_ADMIN)->orderBy('name')->get(['id', 'name', 'email', 'phone', 'role', 'department_id']);
        $row = fn (User $u): array => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'role' => $u->role];

        return response()->json([
            'status' => 'success',
            'department' => $department->only(['id', 'code', 'name', 'description', 'retention_years']),
            'sub_departments' => $department->subDepartments()->orderBy('code')->get(['id', 'department_id', 'code', 'name', 'retention_years', 'is_active']),
            'pic_users' => $users->filter(fn (User $u): bool => (int) $u->department_id === $department->id && $u->role === User::ROLE_PIC_DEPT)->map($row)->values(),
            'available_users' => $users->filter(fn (User $u): bool => (int) $u->department_id !== $department->id)->map($row)->values(),
            'master_archives' => $department->masterArchives()->with('subDepartment:id,code,name')->orderBy('name')->get()
                ->map(fn (MasterArchive $m): array => $m->only(['id', 'sub_department_id', 'code', 'name', 'document_type', 'retention_years', 'description'])
                    + ['sub_department' => $m->subDepartment?->only(['id', 'code', 'name'])]),
        ]);
    }

    public function departmentArchives(Request $request, Department $department): JsonResponse
    {
        $filter = $request->query('filter') === 'unassigned' ? 'unassigned' : null;
        $archives = $this->list(Archive::query()->where('department_id', $department->id)->when($filter, fn ($q) => $q->whereNull('sub_department_id')));

        return response()->json([
            'department' => $department->only(['id', 'code', 'name']) + ['total_box' => count($archives), 'total_items' => array_sum(array_column($archives, 'items_count')), 'filter' => $filter],
            'archives' => $archives,
        ]);
    }

    public function subDepartmentArchives(SubDepartment $subDepartment): JsonResponse
    {
        $archives = $this->list(Archive::query()->where('sub_department_id', $subDepartment->id));
        $department = $subDepartment->loadMissing('department')->department;

        return response()->json([
            'sub_department' => $subDepartment->only(['id', 'code', 'name']) + [
                'department_code' => $department->code ?? '', 'department_name' => $department->name ?? '',
                'total_box' => count($archives), 'total_items' => array_sum(array_column($archives, 'items_count')),
            ],
            'archives' => $archives,
        ]);
    }

    /**
     * @param  Builder<Archive>  $query
     * @return list<array<string, mixed>>
     */
    private function list(Builder $query): array
    {
        return $query->with(['subDepartment:id,code,name', 'items', 'location.warehouse', 'rackSlot'])
            ->latest()->limit(self::MAX_BOXES)->get()
            ->map(fn (Archive $archive): array => [
                'id' => $archive->id,
                'box_number' => $archive->box_number ?? 'Penomoran Pending',
                'title' => $archive->is_custom_doc_name && $archive->custom_doc_name ? $archive->custom_doc_name : $archive->title,
                'sub_department' => $archive->subDepartment->name ?? 'Induk / Umum',
                'sub_department_code' => $archive->subDepartment->code ?? '-',
                'periode_doc' => $archive->periode_doc ?? $archive->period_text ?? '-',
                'tgl_penyerahan' => $archive->tgl_penyerahan?->format('d/m/Y') ?? '-',
                'physical_condition' => $archive->physical_condition ?? 'Baik',
                'location' => $archive->displayLocation(),
                'rack_code' => $archive->location->rack_code ?? '-',
                'slot_code' => $archive->rackSlot->slot_code ?? '-',
                'status' => $archive->status,
                'status_label' => StatusLabel::archive($archive->status, filled($archive->rejection_note))['label'],
                'status_tone' => StatusLabel::archive($archive->status, filled($archive->rejection_note))['tone'],
                'items_count' => $archive->items->count(),
                'items' => $archive->items->map(fn (ArchiveItem $item): array => [
                    'item_number' => $item->item_number, 'document_name' => $item->document_name,
                    'period_text' => $item->period_text ?? '-', 'notes' => $item->notes ?? '',
                ])->all(),
                'url' => route('archives.show', $archive->id),
            ])->all();
    }
}
