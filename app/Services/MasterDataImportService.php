<?php

namespace App\Services;

use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Impor Data Master (Departemen -> Sub Departemen -> Type Berkas) dari template
 * template-import-master/template_import_data_master.xlsx (sheet "Data Master").
 * Dipakai untuk pratinjau ($commit = false) dan simpan ($commit = true); keduanya parse ulang file di server.
 */
class MasterDataImportService
{
    const SHEET_NAME = 'Data Master';

    const HEADERS = [
        'kode departemen' => 'dept_code',
        'departemen' => 'dept_name',
        'kode sub departemen' => 'sub_code',
        'sub departemen' => 'sub_name',
        'nama dokumen' => 'doc_name',
        'retensi (tahun)' => 'retention',
        'retensi' => 'retention',
    ];

    const REQUIRED = ['dept_code' => 'Kode Departemen', 'dept_name' => 'Departemen', 'doc_name' => 'Nama Dokumen'];

    public function process(UploadedFile $file, bool $commit, User $user): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $rows = $ext === 'csv'
            ? SpreadsheetReader::csv($file->getRealPath())
            : SpreadsheetReader::xlsx($file->getRealPath(), self::SHEET_NAME);

        if ($rows === null) {
            return $this->fail("Sheet '" . self::SHEET_NAME . "' tidak ditemukan. Gunakan template resmi.");
        }

        // Baris header = baris pertama yang berisi data
        $colMap = null;
        $dataRows = [];
        foreach ($rows as $rowNum => $cells) {
            if (implode('', $cells) === '') continue;
            if ($colMap === null) {
                $colMap = [];
                foreach ($cells as $idx => $header) {
                    $key = self::HEADERS[$this->normalizeHeader($header)] ?? null;
                    if ($key && !isset($colMap[$key])) $colMap[$key] = $idx;
                }
                continue;
            }
            $dataRows[$rowNum] = $cells;
        }

        if ($colMap === null) {
            return $this->fail('File kosong atau format spreadsheet tidak terbaca.');
        }
        $missing = array_diff_key(self::REQUIRED, $colMap);
        if ($missing) {
            return $this->fail('Kolom header tidak ditemukan: ' . implode(', ', $missing) . '. Gunakan template resmi.');
        }

        // Data aplikasi saat ini
        $depts = Department::all()->keyBy(fn ($d) => strtoupper(trim($d->code)));
        $subs = SubDepartment::all()->keyBy(fn ($s) => $s->department_id . '|' . strtoupper(trim($s->code)));
        $docKeys = [];
        foreach (MasterArchive::select('department_id', 'sub_department_id', 'name')->cursor() as $ma) {
            $docKeys[$ma->department_id . '|' . ($ma->sub_department_id ?? '') . '|' . $this->nameKey($ma->name)] = true;
        }

        $errors = [];
        $warnings = [];
        $resultRows = [];
        $fileDeptNames = [];   // KODE => [nama, baris]
        $fileSubNames = [];    // KODE|SUB => [nama, baris]
        $newDepts = [];        // KODE => nama
        $newSubs = [];         // KODE|SUB => [kode dept, kode sub, nama]
        $newDocs = [];         // [kode dept, kode sub|null, id sub lama|null, nama, retensi]
        $existingDocCount = 0;

        foreach ($dataRows as $rowNum => $cells) {
            $get = fn ($key) => isset($colMap[$key]) ? $this->clean($cells[$colMap[$key]] ?? '') : '';
            $deptCode = strtoupper($get('dept_code'));
            $deptName = $get('dept_name');
            $subCode = strtoupper($get('sub_code'));
            $subName = $get('sub_name');
            $docName = $get('doc_name');
            $retRaw = $get('retention');

            $rowErrors = [];
            if ($deptCode === '') $rowErrors[] = 'Kode Departemen wajib diisi';
            if ($deptName === '') $rowErrors[] = 'Departemen wajib diisi';
            if (mb_strlen($deptCode) > 10) $rowErrors[] = 'Kode Departemen maksimal 10 karakter';
            if (mb_strlen($deptName) > 100) $rowErrors[] = 'Departemen maksimal 100 karakter';
            if (mb_strlen($subCode) > 20) $rowErrors[] = 'Kode Sub Departemen maksimal 20 karakter';
            if (mb_strlen($subName) > 100) $rowErrors[] = 'Sub Departemen maksimal 100 karakter';
            if ($subCode !== '' && $subName === '') $rowErrors[] = 'Sub Departemen wajib diisi jika Kode Sub Departemen diisi';
            if ($subCode === '' && $subName !== '') $rowErrors[] = 'Kode Sub Departemen wajib diisi jika Sub Departemen diisi';
            if (mb_strlen($docName) > 255) $rowErrors[] = 'Nama Dokumen maksimal 255 karakter';

            $retention = null;
            if ($retRaw !== '') {
                if (preg_match('/^\d+(\.0+)?$/', $retRaw) && (int)$retRaw >= 1 && (int)$retRaw <= 100) {
                    $retention = (int)$retRaw;
                } else {
                    $rowErrors[] = "Retensi (Tahun) harus angka bulat 1-100 (isi: {$retRaw})";
                }
            }

            // Konsistensi nama di dalam file
            if ($deptCode !== '' && $deptName !== '') {
                if (!isset($fileDeptNames[$deptCode])) {
                    $fileDeptNames[$deptCode] = [$deptName, $rowNum];
                } elseif ($this->nameKey($fileDeptNames[$deptCode][0]) !== $this->nameKey($deptName)) {
                    $rowErrors[] = "Kode Departemen {$deptCode} memakai nama berbeda dengan baris {$fileDeptNames[$deptCode][1]} ('{$fileDeptNames[$deptCode][0]}')";
                }
            }
            $subKey = $deptCode . '|' . $subCode;
            if ($deptCode !== '' && $subCode !== '' && $subName !== '') {
                if (!isset($fileSubNames[$subKey])) {
                    $fileSubNames[$subKey] = [$subName, $rowNum];
                } elseif ($this->nameKey($fileSubNames[$subKey][0]) !== $this->nameKey($subName)) {
                    $rowErrors[] = "Kode Sub Departemen {$subCode} ({$deptCode}) memakai nama berbeda dengan baris {$fileSubNames[$subKey][1]} ('{$fileSubNames[$subKey][0]}')";
                }
            }

            $row = [
                'row' => $rowNum,
                'dept_code' => $deptCode,
                'dept_name' => $deptName,
                'sub_code' => $subCode,
                'sub_name' => $subName,
                'doc_name' => $docName,
                'retention' => $retention,
            ];

            if ($rowErrors) {
                foreach ($rowErrors as $msg) $errors[] = "Baris {$rowNum}: {$msg}.";
                $resultRows[] = $row + ['status' => 'Error', 'message' => implode('; ', $rowErrors)];
                continue;
            }

            $notes = [];

            // 1. Departemen
            $dept = $depts[$deptCode] ?? null;
            if ($dept) {
                $deptKey = $dept->id;
                if ($this->nameKey($dept->name) !== $this->nameKey($deptName)) {
                    $warnings["dept:{$deptCode}"] ??= "Baris {$rowNum}: Departemen {$deptCode} - nama di file ('{$deptName}') berbeda dengan data aplikasi ('{$dept->name}'); nama aplikasi tetap dipakai.";
                }
            } else {
                $deptKey = 'new:' . $deptCode;
                if (!isset($newDepts[$deptCode])) {
                    $newDepts[$deptCode] = $deptName;
                    $notes[] = 'Departemen baru';
                }
            }

            // 2. Sub Departemen
            $subIdKey = '';
            $sub = null;
            if ($subCode !== '') {
                $sub = $dept ? ($subs[$dept->id . '|' . $subCode] ?? null) : null;
                if ($sub) {
                    $subIdKey = $sub->id;
                    if ($this->nameKey($sub->name) !== $this->nameKey($subName)) {
                        $warnings["sub:{$subKey}"] ??= "Baris {$rowNum}: Sub Departemen {$subCode} ({$deptCode}) - nama di file ('{$subName}') berbeda dengan data aplikasi ('{$sub->name}'); nama aplikasi tetap dipakai.";
                    }
                } else {
                    $subIdKey = 'new:' . $subCode;
                    if (!isset($newSubs[$subKey])) {
                        $newSubs[$subKey] = [$deptCode, $subCode, $subName];
                        $notes[] = 'Sub departemen baru';
                    }
                }
            }

            // 3. Dokumen (type berkas)
            $status = $notes ? 'Baru' : 'Sudah ada';
            if ($docName !== '') {
                $docKey = $deptKey . '|' . $subIdKey . '|' . $this->nameKey($docName);
                if (isset($docKeys[$docKey])) {
                    $existingDocCount++;
                    $notes[] = 'Dokumen sudah ada';
                } else {
                    $docKeys[$docKey] = true; // duplikat berikutnya di file ikut dilewati
                    $newDocs[] = [$deptCode, $subCode ?: null, $sub->id ?? null, $docName, $retention ?: ($dept->retention_years ?? null) ?: 5];
                    $notes[] = 'Dokumen baru';
                    $status = 'Baru';
                }
            }

            $resultRows[] = $row + ['status' => $status, 'message' => implode(', ', $notes)];
        }

        $summary = [
            'departemen_baru' => count($newDepts),
            'sub_departemen_baru' => count($newSubs),
            'dokumen_baru' => count($newDocs),
            'dokumen_sudah_ada' => $existingDocCount,
            'total_baris' => count($dataRows),
        ];

        $result = [
            'success' => empty($errors),
            'errors' => $errors,
            'warnings' => array_values($warnings),
            'summary' => $summary,
            'rows' => $resultRows,
        ];

        if (!$commit) return $result;

        if ($errors) {
            $result['message'] = 'Import dibatalkan: perbaiki ' . count($errors) . ' error pada file lalu unggah ulang.';
            return $result;
        }

        if ($summary['departemen_baru'] + $summary['sub_departemen_baru'] + $summary['dokumen_baru'] === 0) {
            $result['message'] = 'Tidak ada data baru untuk diimpor; semua data di file sudah ada.';
            return $result;
        }

        db_immediate_transaction(function () use ($newDepts, $newSubs, $newDocs, $depts) {
            foreach ($newDepts as $code => $name) {
                $depts[$code] = Department::create([
                    'code' => $code,
                    'name' => $name,
                    'retention_years' => 5,
                    'is_active' => true,
                ]);
            }

            $subIds = [];
            foreach ($newSubs as $key => [$deptCode, $subCode, $subName]) {
                $subIds[$key] = SubDepartment::create([
                    'department_id' => $depts[$deptCode]->id,
                    'code' => $subCode,
                    'name' => $subName,
                    'retention_years' => null,
                    'is_active' => true,
                ])->id;
            }

            $now = now();
            $insert = [];
            foreach ($newDocs as [$deptCode, $subCode, $subId, $docName, $retention]) {
                $insert[] = [
                    'department_id' => $depts[$deptCode]->id,
                    'sub_department_id' => $subId ?? ($subCode === null ? null : $subIds[$deptCode . '|' . $subCode]),
                    'code' => null,
                    'name' => $docName,
                    'document_type' => 'UMUM',
                    'retention_years' => $retention,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($insert, 100) as $chunk) {
                MasterArchive::insert($chunk);
            }
        });

        ActivityLogger::log(
            'MASTER_DATA_IMPORT',
            "Import Excel Data Master: {$summary['departemen_baru']} departemen, {$summary['sub_departemen_baru']} sub departemen, {$summary['dokumen_baru']} type berkas baru ({$summary['dokumen_sudah_ada']} sudah ada dilewati).",
            'master',
            $summary + ['file' => $file->getClientOriginalName()],
            null,
            $user
        );

        $result['message'] = "Import berhasil: {$summary['departemen_baru']} departemen, {$summary['sub_departemen_baru']} sub departemen, dan {$summary['dokumen_baru']} type berkas baru ditambahkan.";
        return $result;
    }

    protected function fail(string $message): array
    {
        return [
            'success' => false,
            'errors' => [$message],
            'warnings' => [],
            'summary' => ['departemen_baru' => 0, 'sub_departemen_baru' => 0, 'dokumen_baru' => 0, 'dokumen_sudah_ada' => 0, 'total_baris' => 0],
            'rows' => [],
        ];
    }

    protected function clean($value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string)$value));
    }

    protected function normalizeHeader($header): string
    {
        return mb_strtolower($this->clean(str_replace('*', '', (string)$header)));
    }

    protected function nameKey(string $name): string
    {
        return mb_strtolower($this->clean($name));
    }
}
