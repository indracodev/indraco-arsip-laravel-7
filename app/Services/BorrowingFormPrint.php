<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\User;

/**
 * Printed "Form Penarikan" of the create form (Laravel 7 borrowings/create openFormPengajuanModal
 * + populateFormRows), on the shared A4 paper of archives/form-print.blade.php. Rows come from
 * the chosen boxes (saved archives), so nothing depends on unsaved form data.
 * Not carried: the Label Box column (Laravel 7 filled it with the rack location; the paper view
 * has no such cell yet).
 */
final class BorrowingFormPrint
{
    public const DEFAULT_NOTE = 'Penarikan fisik box dan dokumen arsip untuk keperluan unit kerja.';

    /**
     * @param  list<Archive>  $archives  boxes the user may see, in the chosen order
     * @return array{fields: array<string, string>, rows: list<array{no: int, title: string, sub_dept: string, masa_simpan: string, period_end: string}>}
     */
    public function for(User $user, array $archives, ?string $purpose): array
    {
        $rows = [];
        foreach ($archives as $i => $archive) {
            $end = $archive->period_end_date?->format('m-Y') ?? (string) $archive->period_text;
            $rows[] = [
                'no' => $i + 1,
                'title' => (string) $archive->title,
                'sub_dept' => $archive->subDepartment ? ($archive->subDepartment->code ?: $archive->subDepartment->name) : '-',
                'masa_simpan' => preg_match('/\d+/', (string) $archive->periode, $m) ? $m[0] : ($archive->periode ?: '-'),
                'period_end' => $end,
            ];
        }
        for ($no = count($rows) + 1; $no <= ArchiveFormPrint::MIN_ROWS; $no++) {
            $rows[] = ['no' => $no, 'title' => '', 'sub_dept' => '', 'masa_simpan' => '', 'period_end' => ''];
        }

        return [
            'fields' => [
                'no_form' => '',
                'tanggal_pengajuan' => now()->format('Y-m-d'),
                'dept_display' => $this->deptDisplay($user, $archives),
                'pic_pemohon' => $user->name,
                'tipe' => 'Penarikan',
                'catatan' => trim((string) $purpose) ?: self::DEFAULT_NOTE,
                'approval_atasan' => '',
                'approval_pimpinan' => '',
            ],
            'rows' => $rows,
        ];
    }

    /** @param list<Archive> $archives */
    private function deptDisplay(User $user, array $archives): string
    {
        $first = $archives[0] ?? null;
        $dept = $first ? $first->department : $user->department;
        $name = $dept ? ($dept->name ?: $dept->code) : '';
        $subs = array_unique(array_map(fn (Archive $a): string => (string) $a->sub_department_id, $archives));
        if ($first?->subDepartment && count($subs) === 1) {
            return $name.' / '.$first->subDepartment->name;
        }

        return $name ?: '-';
    }
}
