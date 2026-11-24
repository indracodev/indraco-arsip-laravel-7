<?php

namespace App\Services;

use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Reads a multi-box spreadsheet (Laravel 7 MultiBoxExcelService::parseFile) into boxes in the
 * shape of the submission form (`boxes[i][title|sub_department_id|document_type|physical_condition|
 * periode|items[j][document_name|period_start|period_end|notes]]`), so the form rules
 * (ArchiveSubmissionRequest + ArchiveBoxBuilder) are the only rules applied afterwards.
 *
 * Grouping as Laravel 7: a new box starts at a new NO_BOX value, or a new JUDUL_BOX when there is
 * no box column; box columns (dept, sub-dept, retention, condition) carry over to the rows below.
 * MASA_SIMPAN_BULAN becomes the form's "PERIODE DOKUMEN (BULAN)" (Laravel 7 load-to-form:
 * retention years x 12, empty = 60). Errors and warnings name the Excel row.
 */
final class ArchiveImportParser
{
    public function __construct(private ArchiveImportValues $values) {}

    /**
     * Each box also carries `temp_box_id`, `department` (model) and `row` (first Excel row);
     * each item its `row`.
     *
     * @return array{errors: list<string>, warnings: list<string>, boxes: list<array<string, mixed>>}
     */
    public function parse(UploadedFile $file, User $user): array
    {
        $path = (string) $file->getRealPath();
        $byLine = strtolower($file->getClientOriginalExtension()) === 'csv' ? SpreadsheetReader::csv($path) : (SpreadsheetReader::xlsx($path) ?? []);
        if ($byLine === []) {
            return $this->failed('File kosong atau format spreadsheet tidak terbaca.');
        }
        [$map, $start] = $this->values->header(array_values($byLine));
        if (! isset($map['doc_name']) && ! isset($map['box'])) {
            return $this->failed('Kolom header tidak ditemukan. Pastikan menggunakan template resmi atau file Excel yang memiliki kolom Box / List Dokumen / Departemen.');
        }

        $context = [
            'map' => $map,
            'user' => $user,
            'departments' => Department::query()->where('is_active', true)->get(),
            'subs' => SubDepartment::query()->where('is_active', true)->get(),
            'masters' => MasterArchive::query()->where('is_active', true)->get(),
        ];
        $errors = $warnings = $boxes = [];
        $key = null;
        $sequence = 0;
        // Keys are Excel row numbers (empty rows are absent from an XLSX sheet).
        foreach (array_slice($byLine, $start, null, true) as $line => $row) {
            $cell = fn (string $column): string => isset($map[$column]) ? trim((string) ($row[$map[$column]] ?? '')) : '';
            if ($cell('doc_name') === '') {
                continue; // blank or comment row (Laravel 7)
            }
            $boxId = $cell('box');
            $title = $cell('title');
            $isNew = $key === null
                || ($boxId !== '' && $boxId !== $key)
                || ($boxId === '' && $title !== '' && $boxes[$key]['raw_title'] !== null && $title !== $boxes[$key]['raw_title']);
            if ($isNew) {
                $key = $boxId !== '' ? $boxId : 'BOX-'.str_pad((string) ++$sequence, 2, '0', STR_PAD_LEFT);
                $boxes[$key] ??= $this->newBox($key, $line); // a NO_BOX seen before continues that box (Laravel 7)
            }
            $box = &$boxes[$key];
            $this->carryOver($box, $cell);
            $this->resolveDepartment($box, $context, $line, $errors);
            $box['items'][] = $this->item($cell, $box, $context, $line, $warnings);
            unset($box);
        }

        $boxes = $this->finish(array_values($boxes), $context, $errors, $warnings);

        return ['errors' => $errors, 'warnings' => $warnings, 'boxes' => $boxes];
    }

    /** @return array<string, mixed> */
    private function newBox(string $tempId, int $line): array
    {
        return ['temp_box_id' => $tempId, 'row' => $line, 'raw_title' => null, 'raw' => [], 'department' => null, 'sub_department' => null, 'items' => []];
    }

    /**
     * Box columns keep the last non-empty value of the box.
     *
     * @param  array<string, mixed>  $box
     */
    private function carryOver(array &$box, callable $cell): void
    {
        if ($cell('title') !== '') {
            $box['raw_title'] = $cell('title');
        }
        foreach (['dept', 'sub_dept', 'retention', 'condition', 'room', 'rack', 'slot'] as $key) {
            if ($cell($key) !== '') {
                $box['raw'][$key] = $cell($key);
            }
        }
    }

    /**
     * Department: exact code, exact name, then partial name (>= 4 chars); empty = the user's own.
     * The department and sub-department of a box are those of its first row (Laravel 7).
     * Sub-department within it the same way (it can also tell the department). PIC Departemen may
     * only import for their own department (Laravel 7 message, per row).
     *
     * @param  array<string, mixed>  $box
     * @param  array<string, mixed>  $context
     * @param  list<string>  $errors
     */
    private function resolveDepartment(array &$box, array $context, int $line, array &$errors): void
    {
        /** @var Collection<int, Department> $departments */
        $departments = $context['departments'];
        $raw = strtoupper(trim($box['raw']['dept'] ?? ''));
        $dept = $raw === '' ? $departments->firstWhere('id', $context['user']->department_id)
            : $this->find($departments, $raw, fn ($d): bool => true);

        $sub = null;
        $rawSub = strtoupper(trim($box['raw']['sub_dept'] ?? ''));
        if ($rawSub !== '') {
            $sub = $this->find($context['subs'], $rawSub, fn ($s): bool => ! $dept || $s->department_id === $dept->id);
            $dept ??= $sub ? $departments->firstWhere('id', $sub->department_id) : null;
        }
        // The box keeps the department of its first row (Laravel 7); later rows are still checked.
        if ($box['department'] === null) {
            [$box['department'], $box['sub_department']] = [$dept, $sub];
        }

        $user = $context['user'];
        if ($user->role === User::ROLE_PIC_DEPT && $dept && $dept->id !== $user->department_id) {
            $errors[] = "Baris {$line}: Sebagai PIC Departemen, Anda hanya diizinkan mengimpor arsip untuk departemen Anda ({$user->department?->name}).";
        }
    }

    /**
     * @param  Collection<int, covariant Department|SubDepartment>  $pool
     * @param  callable(Department|SubDepartment): bool  $scope
     */
    private function find(Collection $pool, string $raw, callable $scope): Department|SubDepartment|null
    {
        $pool = $pool->filter($scope);

        return $pool->first(fn ($m): bool => strtoupper(trim((string) $m->code)) === $raw)
            ?? $pool->first(fn ($m): bool => strcasecmp(trim($m->name), $raw) === 0)
            ?? (strlen($raw) >= 4 ? $pool->first(fn ($m): bool => stripos($m->name, $raw) !== false) : null);
    }

    /**
     * One document line; the name is replaced by the matched Master Berkas name (Laravel 7).
     *
     * @param  array<string, mixed>  $box
     * @param  array<string, mixed>  $context
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function item(callable $cell, array &$box, array $context, int $line, array &$warnings): array
    {
        $rawStart = $rawEnd = '';
        if (isset($context['map']['period_combined']) && $cell('period_combined') !== '') {
            $rawStart = $cell('period_combined');
            [$start, $end] = $this->values->periodRange($rawStart);
        } else {
            [$rawStart, $rawEnd] = [$cell('period_start'), $cell('period_end')];
            [$start, $end] = [$this->values->yearMonth($rawStart), $this->values->yearMonth($rawEnd)];
        }
        foreach ([[$rawStart, $start], [$rawEnd, $end]] as [$raw, $parsed]) {
            if ($raw !== '' && $parsed === null) {
                $warnings[] = "Baris {$line}: Periode \"{$raw}\" tidak dikenali, diisi bulan berjalan.";
            }
        }
        $start ??= now()->format('Y-m');

        $name = $cell('doc_name');
        $master = $this->values->matchMaster($name, $box['department']?->id, $context['masters']);
        if ($master && $master->name !== $name) {
            $warnings[] = "Baris {$line}: \"{$name}\" dicocokkan ke Master Berkas \"{$master->name}\".";
        } elseif (! $master) {
            $warnings[] = "Baris {$line}: \"{$name}\" tidak ditemukan di Master Berkas (disimpan sesuai teks file).";
        }
        $box['document_type'] ??= $master?->document_type ?: null;

        return ['document_name' => $master->name ?? $name, 'period_start' => $start, 'period_end' => $end ?? $start, 'notes' => $cell('notes'), 'row' => $line];
    }

    /**
     * Final box values in form shape; a box without a valid department is left out with an error.
     *
     * @param  list<array<string, mixed>>  $boxes
     * @param  array<string, mixed>  $context
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @return list<array<string, mixed>>
     */
    private function finish(array $boxes, array $context, array &$errors, array &$warnings): array
    {
        $result = [];
        foreach ($boxes as $box) {
            $dept = $box['department'];
            if (! $dept) {
                $errors[] = "Box '{$box['temp_box_id']}': Tidak memiliki informasi Departemen yang valid. Harap periksa kolom DEPARTEMEN.";

                continue;
            }
            $months = $this->values->retentionYears($box['raw']['retention'] ?? null) * 12;
            $limit = ArchiveBoxBuilder::maxPeriodMonths($dept);
            if ($months > $limit) {
                $warnings[] = "Box '{$box['temp_box_id']}': Masa simpan {$months} bulan. Dibatasi otomatis ke batas maksimal {$limit} bulan sesuai standar kearsipan.";
                $months = $limit;
            }
            if (array_intersect_key($box['raw'], array_flip(['room', 'rack', 'slot']))) {
                $warnings[] = "Box '{$box['temp_box_id']}': Kolom lokasi rak diabaikan; penempatan rak dilakukan PIC Gudang saat verifikasi.";
            }
            $firstName = $box['items'][0]['document_name'];
            $result[] = [
                'temp_box_id' => $box['temp_box_id'],
                'row' => $box['row'],
                'department' => $dept,
                'title' => $box['raw_title'] ?? "Arsip {$box['temp_box_id']} {$dept->name} - {$firstName}",
                'sub_department_id' => $box['sub_department']?->id,
                'sub_department_code' => $box['sub_department']?->code,
                'document_type' => $this->values->matchMaster($firstName, $dept->id, $context['masters'])->document_type ?? $box['document_type'] ?? null,
                'physical_condition' => $box['raw']['condition'] ?? ArchiveBoxBuilder::DEFAULT_CONDITION,
                'periode' => "{$months} Bulan",
                'items' => $box['items'],
            ];
        }

        return $result;
    }

    /** @return array{errors: list<string>, warnings: list<string>, boxes: list<array<string, mixed>>} */
    private function failed(string $message): array
    {
        return ['errors' => [$message], 'warnings' => [], 'boxes' => []];
    }
}
