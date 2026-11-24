<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\SubDepartment;
use App\Models\User;

/**
 * Data for the box submission form (create + edit): departments with their active
 * sub-departments and "Master Berkas" (PIC Departemen: only their own department), and the
 * boxes to start with, in the shape the Alpine module `archiveForm` uses.
 */
final class ArchiveFormData
{
    /**
     * @param  array<int, mixed>|null  $oldBoxes  boxes of a submission that failed validation
     * @return array<string, mixed>
     */
    public function for(User $user, ?Archive $archive, ?array $oldBoxes): array
    {
        $departments = Department::query()->where('is_active', true)
            ->when($user->role === User::ROLE_PIC_DEPT, fn ($query) => $query->whereKey($user->department_id))
            ->with([
                'subDepartments' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'masterArchives' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            ])
            ->orderBy('id')->get();

        $boxes = match (true) {
            $oldBoxes !== null => array_values($oldBoxes),
            $archive !== null => $this->archiveBoxes($archive),
            default => null,
        };

        return [
            'departments' => $departments->map(fn (Department $d): array => [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'sub_departments' => $d->subDepartments->map(fn (SubDepartment $s): array => ['id' => $s->id, 'code' => $s->code, 'name' => $s->name])->all(),
                'master_archives' => $d->masterArchives->map(fn (MasterArchive $m): array => [
                    'id' => $m->id, 'code' => $m->code, 'name' => $m->name,
                    'document_type' => $m->document_type, 'sub_department_id' => $m->sub_department_id,
                ])->all(),
            ])->all(),
            'boxes' => $boxes === null ? null : array_map(fn ($box): array => $this->formBox((array) $box), $boxes),
        ];
    }

    /**
     * The saved boxes of an archive in form shape (print "Form Pengajuan").
     *
     * @return list<array<string, mixed>>
     */
    public function boxesOf(Archive $archive): array
    {
        return array_map(fn ($box): array => $this->formBox((array) $box), $this->archiveBoxes($archive));
    }

    /**
     * A multi-box draft keeps its boxes as JSON; any other archive (a single box, or a draft
     * returned by the warehouse) is one box with its document lines (Laravel 7 edit).
     *
     * @return list<array<string, mixed>>
     */
    private function archiveBoxes(Archive $archive): array
    {
        $decoded = $archive->status === 'draft' ? json_decode((string) $archive->content_description, true) : null;
        if (is_array($decoded) && ! empty($decoded['is_multi_box_draft']) && ! empty($decoded['boxes']) && is_array($decoded['boxes'])) {
            return array_values($decoded['boxes']);
        }

        return [[
            'title' => $archive->title,
            'sub_department_id' => $archive->sub_department_id,
            'document_type' => $archive->document_type,
            'physical_condition' => $archive->physical_condition,
            'periode' => $archive->periode ?: $archive->periode_doc,
            'items' => $archive->items->map->only(['document_name', 'period_start', 'period_end', 'notes'])->all(),
        ]];
    }

    /**
     * @param  array<string, mixed>  $box
     * @return array<string, mixed>
     */
    private function formBox(array $box): array
    {
        $month = now()->format('Y-m');
        $items = array_map(fn ($item): array => [
            'document_name' => (string) ($item['document_name'] ?? ''),
            'period_start' => (string) ($item['period_start'] ?? ''),
            'period_end' => (string) (($item['period_end'] ?? '') ?: ($item['period_start'] ?? '')),
            'notes' => (string) ($item['notes'] ?? ''),
        ], array_values(array_filter((array) ($box['items'] ?? []), 'is_array')));

        return [
            'title' => (string) ($box['title'] ?? ''),
            'sub_department_id' => (string) ($box['sub_department_id'] ?? ''),
            'document_type' => (string) ($box['document_type'] ?? ''),
            'physical_condition' => (string) (($box['physical_condition'] ?? '') ?: ArchiveBoxBuilder::DEFAULT_CONDITION),
            'periode_bulan' => preg_match('/\d+/', (string) ($box['periode'] ?? ''), $m) ? $m[0] : '1',
            'items' => $items ?: [['document_name' => '', 'period_start' => $month, 'period_end' => $month, 'notes' => '']],
        ];
    }
}
