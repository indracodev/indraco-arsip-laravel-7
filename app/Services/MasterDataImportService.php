<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Import Excel Data Master (Departemen -> Sub Departemen -> Type Berkas) from
 * template-import-master/template_import_data_master.xlsx, sheet "Data Master" (Laravel 7
 * MasterDataImportService, same rules and messages). Preview ($commit = false) and commit
 * both parse the file again on the server. Rows are matched by code (department code, sub code
 * within the department) and document name; existing data is never changed or duplicated.
 * Commit re-checks inside one IMMEDIATE transaction, so two admins importing at once cannot
 * create the same department twice.
 */
class MasterDataImportService
{
    public const SHEET_NAME = 'Data Master';

    private const HEADERS = [
        'kode departemen' => 'dept_code',
        'departemen' => 'dept_name',
        'kode sub departemen' => 'sub_code',
        'sub departemen' => 'sub_name',
        'nama dokumen' => 'doc_name',
        'retensi (tahun)' => 'retention',
        'retensi' => 'retention',
    ];

    private const REQUIRED = ['dept_code' => 'Kode Departemen', 'dept_name' => 'Departemen', 'doc_name' => 'Nama Dokumen'];

    /**
     * @return array{success: bool, errors: list<string>, warnings: list<string>, summary: array<string, int>, rows: list<array<string, mixed>>, message?: string}
     */
    public function process(UploadedFile $file, bool $commit, User $user): array
    {
        $rows = strtolower($file->getClientOriginalExtension()) === 'csv'
            ? SpreadsheetReader::csv((string) $file->getRealPath())
            : SpreadsheetReader::xlsx((string) $file->getRealPath(), self::SHEET_NAME);
        if ($rows === null) {
            return $this->fail("Sheet '".self::SHEET_NAME."' tidak ditemukan. Gunakan template resmi.");
        }

        [$colMap, $dataRows] = $this->split($rows);
        if ($colMap === null) {
            return $this->fail('File kosong atau format spreadsheet tidak terbaca.');
        }
        $missing = array_diff_key(self::REQUIRED, $colMap);
        if ($missing !== []) {
            return $this->fail('Kolom header tidak ditemukan: '.implode(', ', $missing).'. Gunakan template resmi.');
        }

        $records = array_map(fn (array $cells): array => $this->record($cells, $colMap), $dataRows);
        if (! $commit) {
            return (new MasterDataImportPlan($records))->result();
        }

        $plan = DB::transaction(function () use ($records): MasterDataImportPlan {
            $plan = new MasterDataImportPlan($records);
            if ($plan->errors === [] && $plan->hasNewData()) {
                $plan->save();
            }

            return $plan;
        });

        return $this->commitResult($plan, $file, $user);
    }

    /**
     * @return array{success: bool, errors: list<string>, warnings: list<string>, summary: array<string, int>, rows: list<array<string, mixed>>, message: string}
     */
    private function commitResult(MasterDataImportPlan $plan, UploadedFile $file, User $user): array
    {
        $result = $plan->result();
        $summary = $result['summary'];
        if ($plan->errors !== []) {
            return $result + ['message' => 'Import dibatalkan: perbaiki '.count($plan->errors).' error pada file lalu unggah ulang.'];
        }
        if (! $plan->hasNewData()) {
            return $result + ['message' => 'Tidak ada data baru untuk diimpor; semua data di file sudah ada.'];
        }

        ActivityLogger::log(
            'MASTER_DATA_IMPORT',
            "Import Excel Data Master: {$summary['departemen_baru']} departemen, {$summary['sub_departemen_baru']} sub departemen, {$summary['dokumen_baru']} type berkas baru ({$summary['dokumen_sudah_ada']} sudah ada dilewati).",
            'master',
            $summary + ['file' => $file->getClientOriginalName()],
            null,
            $user,
        );

        return $result + ['message' => "Import berhasil: {$summary['departemen_baru']} departemen, {$summary['sub_departemen_baru']} sub departemen, dan {$summary['dokumen_baru']} type berkas baru ditambahkan."];
    }

    /**
     * Header row = the first non-empty row; the rest are data rows (keyed by Excel row number).
     *
     * @param  array<int, list<string>>  $rows
     * @return array{0: array<string, int>|null, 1: array<int, list<string>>}
     */
    private function split(array $rows): array
    {
        $colMap = null;
        $dataRows = [];
        foreach ($rows as $rowNum => $cells) {
            if (implode('', $cells) === '') {
                continue;
            }
            if ($colMap === null) {
                $colMap = [];
                foreach ($cells as $index => $header) {
                    $key = self::HEADERS[self::normalizeHeader($header)] ?? null;
                    if ($key !== null && ! isset($colMap[$key])) {
                        $colMap[$key] = $index;
                    }
                }

                continue;
            }
            $dataRows[$rowNum] = $cells;
        }

        return [$colMap, $dataRows];
    }

    /**
     * @param  list<string>  $cells
     * @param  array<string, int>  $colMap
     * @return array<string, string>
     */
    private function record(array $cells, array $colMap): array
    {
        $get = fn (string $key): string => isset($colMap[$key]) ? self::clean($cells[$colMap[$key]] ?? '') : '';

        return [
            'dept_code' => strtoupper($get('dept_code')),
            'dept_name' => $get('dept_name'),
            'sub_code' => strtoupper($get('sub_code')),
            'sub_name' => $get('sub_name'),
            'doc_name' => $get('doc_name'),
            'retention' => $get('retention'),
        ];
    }

    /**
     * @return array{success: false, errors: list<string>, warnings: list<string>, summary: array<string, int>, rows: list<array<string, mixed>>}
     */
    private function fail(string $message): array
    {
        return [
            'success' => false,
            'errors' => [$message],
            'warnings' => [],
            'summary' => ['departemen_baru' => 0, 'sub_departemen_baru' => 0, 'dokumen_baru' => 0, 'dokumen_sudah_ada' => 0, 'total_baris' => 0],
            'rows' => [],
        ];
    }

    public static function clean(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    public static function nameKey(string $name): string
    {
        return mb_strtolower(self::clean($name));
    }

    private static function normalizeHeader(string $header): string
    {
        return mb_strtolower(self::clean(str_replace('*', '', $header)));
    }
}
