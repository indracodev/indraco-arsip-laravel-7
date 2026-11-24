<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Content of the printed "Form Pengajuan" (Laravel 7 components/form-pengajuan-modal +
 * create/edit_modal_scripts: openFormPengajuanModal, populateFormRows, getDeptSubDeptDisplay,
 * getBoxPeriodEndFormatted), built on the server from the saved archive so the paper always
 * shows the saved data (Laravel 7 UAT note: "print form pengajuan simpan data tidak muncul").
 */
final class ArchiveFormPrint
{
    public const MIN_ROWS = 10;

    public const DEFAULT_NOTE = 'Kondisi fisik box standar TB 30g dalam keadaan baik.';

    public function __construct(private readonly ArchiveFormData $form) {}

    /**
     * @return array{fields: array<string, string>, rows: list<array{no: int, title: string, sub_dept: string, masa_simpan: string, period_end: string}>}
     */
    public function for(User $user, Archive $archive): array
    {
        $boxes = $this->form->boxesOf($archive);
        $headerSub = (string) ($archive->sub_department_id ?? '');
        $subIds = array_map(fn (array $box): string => (string) ($box['sub_department_id'] ?: $headerSub), $boxes);
        $subs = SubDepartment::query()->whereIn('id', array_filter($subIds))->get()->keyBy('id');
        $handover = $archive->tgl_penyerahan ?? null;

        $rows = [];
        foreach ($boxes as $i => $box) {
            $sub = $subs->get((int) $subIds[$i]);
            $names = array_filter(array_map(fn (array $item): string => trim($item['document_name']), $box['items']));
            $rows[] = [
                'no' => $i + 1,
                'title' => trim($box['title']) ?: (reset($names) ?: 'Box '.($i + 1)),
                'sub_dept' => $sub ? ($sub->code ?: $sub->name) : '-',
                'masa_simpan' => (string) ($box['periode_bulan'] ?: '1'),
                'period_end' => $this->periodEnd($handover, $box),
            ];
        }
        for ($no = count($rows) + 1; $no <= self::MIN_ROWS; $no++) {
            $rows[] = ['no' => $no, 'title' => '', 'sub_dept' => '', 'masa_simpan' => '', 'period_end' => ''];
        }

        return [
            'fields' => [
                'no_form' => '',
                'tanggal_pengajuan' => ($handover ?? now())->format('Y-m-d'),
                'dept_display' => $this->deptDisplay($archive, $subIds, $subs->get((int) ($subIds[0] ?? 0))),
                'pic_pemohon' => $user->name,
                'tipe' => 'Simpan',
                'catatan' => self::DEFAULT_NOTE,
                'approval_atasan' => '',
                'approval_pimpinan' => '',
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Department name, plus " / sub-department" when every box uses the same one.
     *
     * @param  list<string>  $subIds
     */
    private function deptDisplay(Archive $archive, array $subIds, ?SubDepartment $onlySub): string
    {
        $dept = $archive->department;
        $deptName = $dept ? ($dept->name ?: $dept->code) : '';
        if (count(array_unique($subIds)) === 1 && $onlySub !== null && $deptName !== '') {
            return $deptName.' / '.($onlySub->name ?: $onlySub->code);
        }

        return $deptName ?: '-';
    }

    /**
     * "Periode Akhir (MM-YYYY)": handover month + N months; without a handover date the last
     * document month.
     *
     * @param  array<string, mixed>  $box
     */
    private function periodEnd(?Carbon $handover, array $box): string
    {
        if ($handover !== null) {
            return $handover->copy()->startOfMonth()->addMonthsNoOverflow((int) ($box['periode_bulan'] ?: 1))->format('m-Y');
        }
        $ends = array_filter(array_column($box['items'], 'period_end'));
        if ($ends === []) {
            return '';
        }
        $last = max($ends);

        return preg_match('/^(\d{4})-(\d{2})$/', $last, $m) ? "{$m[2]}-{$m[1]}" : $last;
    }
}
