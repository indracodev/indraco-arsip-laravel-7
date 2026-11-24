<?php

namespace App\Services;

use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\SubDepartment;

/**
 * Row-by-row analysis of an Excel Data Master import against the current database
 * (Laravel 7 MasterDataImportService::process, same messages): per-row errors, name warnings,
 * what is new, and save() for the new departments / sub departments / type berkas.
 */
class MasterDataImportPlan
{
    /** @var list<string> */
    public array $errors = [];

    /** @var array<string, string> */
    private array $warnings = [];

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    /** @var array<string, string> KODE => nama */
    private array $newDepts = [];

    /** @var array<string, array{0: string, 1: string, 2: string}> KODE|SUB => [kode dept, kode sub, nama] */
    private array $newSubs = [];

    /** @var list<array{0: string, 1: string|null, 2: int|null, 3: string, 4: int}> */
    private array $newDocs = [];

    private int $existingDocs = 0;

    /** @var array<string, Department> */
    private array $depts;

    /** @var array<string, SubDepartment> */
    private array $subs;

    /** @var array<string, true> */
    private array $docKeys = [];

    /** @var array<string, array{0: string, 1: int}> */
    private array $fileDeptNames = [];

    /** @var array<string, array{0: string, 1: int}> */
    private array $fileSubNames = [];

    /**
     * @param  array<int, array<string, string>>  $records  keyed by Excel row number
     */
    public function __construct(private array $records)
    {
        $this->depts = Department::all()->keyBy(fn (Department $d): string => strtoupper(trim($d->code)))->all();
        $this->subs = SubDepartment::all()->keyBy(fn (SubDepartment $s): string => $s->department_id.'|'.strtoupper(trim((string) $s->code)))->all();
        foreach (MasterArchive::query()->select(['department_id', 'sub_department_id', 'name'])->cursor() as $doc) {
            $this->docKeys[$doc->department_id.'|'.($doc->sub_department_id ?? '').'|'.MasterDataImportService::nameKey($doc->name)] = true;
        }
        foreach ($records as $rowNum => $record) {
            $this->analyse($rowNum, $record);
        }
    }

    public function hasNewData(): bool
    {
        return $this->newDepts !== [] || $this->newSubs !== [] || $this->newDocs !== [];
    }

    /**
     * @return array{success: bool, errors: list<string>, warnings: list<string>, summary: array<string, int>, rows: list<array<string, mixed>>}
     */
    public function result(): array
    {
        return [
            'success' => $this->errors === [],
            'errors' => $this->errors,
            'warnings' => array_values($this->warnings),
            'summary' => [
                'departemen_baru' => count($this->newDepts),
                'sub_departemen_baru' => count($this->newSubs),
                'dokumen_baru' => count($this->newDocs),
                'dokumen_sudah_ada' => $this->existingDocs,
                'total_baris' => count($this->records),
            ],
            'rows' => $this->rows,
        ];
    }

    /** Inserts everything new; call inside a transaction. */
    public function save(): void
    {
        foreach ($this->newDepts as $code => $name) {
            $this->depts[$code] = Department::create(['code' => $code, 'name' => $name, 'retention_years' => 5, 'is_active' => true]);
        }
        $subIds = [];
        foreach ($this->newSubs as $key => [$deptCode, $subCode, $subName]) {
            $sub = new SubDepartment(['code' => $subCode, 'name' => $subName, 'retention_years' => null, 'is_active' => true]);
            $sub->department_id = $this->depts[$deptCode]->id;
            $sub->save();
            $subIds[$key] = $sub->id;
        }

        $now = now();
        $insert = [];
        foreach ($this->newDocs as [$deptCode, $subCode, $subId, $docName, $retention]) {
            $insert[] = [
                'department_id' => $this->depts[$deptCode]->id,
                'sub_department_id' => $subId ?? ($subCode === null ? null : $subIds[$deptCode.'|'.$subCode]),
                'code' => null, 'name' => $docName, 'document_type' => 'UMUM', 'retention_years' => $retention,
                'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($insert, 100) as $chunk) {
            MasterArchive::insert($chunk);
        }
    }

    /** @param array<string, string> $r */
    private function analyse(int $rowNum, array $r): void
    {
        $rowErrors = $this->rowErrors($rowNum, $r);
        $retention = self::validRetention($r['retention']) ? (int) $r['retention'] : null;
        $row = ['row' => $rowNum] + $r;
        $row['retention'] = $retention;
        if ($rowErrors !== []) {
            foreach ($rowErrors as $message) {
                $this->errors[] = "Baris {$rowNum}: {$message}.";
            }
            $this->rows[] = $row + ['status' => 'Error', 'message' => implode('; ', $rowErrors)];

            return;
        }

        $notes = [];
        $deptCode = $r['dept_code'];
        $subCode = $r['sub_code'];
        $dept = $this->depts[$deptCode] ?? null;
        if ($dept !== null) {
            $deptKey = (string) $dept->id;
            if (MasterDataImportService::nameKey($dept->name) !== MasterDataImportService::nameKey($r['dept_name'])) {
                $this->warnings["dept:{$deptCode}"] ??= "Baris {$rowNum}: Departemen {$deptCode} - nama di file ('{$r['dept_name']}') berbeda dengan data aplikasi ('{$dept->name}'); nama aplikasi tetap dipakai.";
            }
        } else {
            $deptKey = 'new:'.$deptCode;
            if (! isset($this->newDepts[$deptCode])) {
                $this->newDepts[$deptCode] = $r['dept_name'];
                $notes[] = 'Departemen baru';
            }
        }

        $subIdKey = '';
        $sub = null;
        if ($subCode !== '') {
            $subKey = $deptCode.'|'.$subCode;
            $sub = $dept !== null ? ($this->subs[$dept->id.'|'.$subCode] ?? null) : null;
            if ($sub !== null) {
                $subIdKey = (string) $sub->id;
                if (MasterDataImportService::nameKey($sub->name) !== MasterDataImportService::nameKey($r['sub_name'])) {
                    $this->warnings["sub:{$subKey}"] ??= "Baris {$rowNum}: Sub Departemen {$subCode} ({$deptCode}) - nama di file ('{$r['sub_name']}') berbeda dengan data aplikasi ('{$sub->name}'); nama aplikasi tetap dipakai.";
                }
            } else {
                $subIdKey = 'new:'.$subCode;
                if (! isset($this->newSubs[$subKey])) {
                    $this->newSubs[$subKey] = [$deptCode, $subCode, $r['sub_name']];
                    $notes[] = 'Sub departemen baru';
                }
            }
        }

        $status = $notes !== [] ? 'Baru' : 'Sudah ada';
        if ($r['doc_name'] !== '') {
            $docKey = $deptKey.'|'.$subIdKey.'|'.MasterDataImportService::nameKey($r['doc_name']);
            if (isset($this->docKeys[$docKey])) {
                $this->existingDocs++;
                $notes[] = 'Dokumen sudah ada';
            } else {
                $this->docKeys[$docKey] = true; // a later duplicate row in the file is skipped too
                $this->newDocs[] = [$deptCode, $subCode !== '' ? $subCode : null, $sub?->id, $r['doc_name'], $retention ?: ($dept?->retention_years ?: 5)];
                $notes[] = 'Dokumen baru';
                $status = 'Baru';
            }
        }

        $this->rows[] = $row + ['status' => $status, 'message' => implode(', ', $notes)];
    }

    /** Whole number 1-100 ("5" or Excel's "5.0"). */
    private static function validRetention(string $value): bool
    {
        return preg_match('/^\d+(\.0+)?$/', $value) === 1 && (int) $value >= 1 && (int) $value <= 100;
    }

    /**
     * @param  array<string, string>  $r
     * @return list<string>
     */
    private function rowErrors(int $rowNum, array $r): array
    {
        $checks = [
            'Kode Departemen wajib diisi' => $r['dept_code'] === '',
            'Departemen wajib diisi' => $r['dept_name'] === '',
            'Kode Departemen maksimal 10 karakter' => mb_strlen($r['dept_code']) > 10,
            'Departemen maksimal 100 karakter' => mb_strlen($r['dept_name']) > 100,
            'Kode Sub Departemen maksimal 20 karakter' => mb_strlen($r['sub_code']) > 20,
            'Sub Departemen maksimal 100 karakter' => mb_strlen($r['sub_name']) > 100,
            'Sub Departemen wajib diisi jika Kode Sub Departemen diisi' => $r['sub_code'] !== '' && $r['sub_name'] === '',
            'Kode Sub Departemen wajib diisi jika Sub Departemen diisi' => $r['sub_code'] === '' && $r['sub_name'] !== '',
            'Nama Dokumen maksimal 255 karakter' => mb_strlen($r['doc_name']) > 255,
            "Retensi (Tahun) harus angka bulat 1-100 (isi: {$r['retention']})" => $r['retention'] !== '' && ! self::validRetention($r['retention']),
        ];
        $errors = array_keys(array_filter($checks));

        // Same code with another name inside the file.
        $deptCode = $r['dept_code'];
        if ($deptCode !== '' && $r['dept_name'] !== '') {
            [$name, $firstRow] = $this->fileDeptNames[$deptCode] ??= [$r['dept_name'], $rowNum];
            if (MasterDataImportService::nameKey($name) !== MasterDataImportService::nameKey($r['dept_name'])) {
                $errors[] = "Kode Departemen {$deptCode} memakai nama berbeda dengan baris {$firstRow} ('{$name}')";
            }
        }
        $subCode = $r['sub_code'];
        if ($deptCode !== '' && $subCode !== '' && $r['sub_name'] !== '') {
            [$name, $firstRow] = $this->fileSubNames[$deptCode.'|'.$subCode] ??= [$r['sub_name'], $rowNum];
            if (MasterDataImportService::nameKey($name) !== MasterDataImportService::nameKey($r['sub_name'])) {
                $errors[] = "Kode Sub Departemen {$subCode} ({$deptCode}) memakai nama berbeda dengan baris {$firstRow} ('{$name}')";
            }
        }

        return $errors;
    }
}
