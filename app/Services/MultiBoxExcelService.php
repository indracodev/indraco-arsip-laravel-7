<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use SimpleXMLElement;

class MultiBoxExcelService
{
    /**
     * Generate template XLSX or CSV with sample multi-box data and department reference guide.
     */
    public function generateTemplate(string $format = 'xlsx')
    {
        $departments = Department::where('is_active', true)->orderBy('code')->get();
        $deptGuide = $departments->map(function ($d) {
            return "{$d->code}: {$d->name}";
        })->implode(' | ');

        $headers = [
            'NO_BOX_SEMENTARA',
            'JUDUL_BOX',
            'KODE_DEPARTEMEN',
            'KODE_SUB_DEPARTEMEN',
            'PERIODE_BOX',
            'MASA_SIMPAN_TAHUN',
            'KONDISI_FISIK',
            'NO_BUTIR',
            'NAMA_DOKUMEN',
            'PERIODE_DOK_MULAI',
            'PERIODE_DOK_SELESAI',
            'KETERANGAN_BUTIR',
        ];

        $sampleRows = [
            // Box 1: Keuangan (Finance) - 2 Butir Dokumen
            [
                'BOX-01',
                'Voucher Pengeluaran Kas & Bank Jan 2024',
                'FIN',
                '',
                '1 Bulan',
                '5',
                'Baik / Box Karton Standar TB 30g',
                '1',
                'Kwitansi & Bukti Kas Keluar No. 001 - 050',
                '2024-01',
                '2024-01',
                'Asli bertandatangan Kasir & Mgr',
            ],
            [
                'BOX-01',
                'Voucher Pengeluaran Kas & Bank Jan 2024',
                'FIN',
                '',
                '',
                '',
                '',
                '2',
                'Kwitansi & Bukti Kas Keluar No. 051 - 100',
                '2024-01',
                '2024-01',
                'Asli bertandatangan Kasir & Mgr',
            ],

            // Box 2: HRD & Personalia - 3 Butir Dokumen (Periode otomatis terhitung 3 Bulan)
            [
                'BOX-02',
                'Berkas Klaim Asuransi & Rawat Jalan Q1 2024',
                'HRD',
                '',
                '', // Kosong -> akan otomatis dihitung 3 Bulan (Jan s/d Mar 2024)
                '5',
                'Baik / Box Karton Standar TB 30g',
                '1',
                'Formulir Klaim Medis & Bukti RS Januari 2024',
                '2024-01',
                '2024-01',
                'Karyawan Kantor Pusat',
            ],
            [
                'BOX-02',
                'Berkas Klaim Asuransi & Rawat Jalan Q1 2024',
                'HRD',
                '',
                '',
                '',
                '',
                '2',
                'Formulir Klaim Medis & Bukti RS Februari 2024',
                '2024-02',
                '2024-02',
                'Karyawan Kantor Pusat & Pabrik',
            ],
            [
                'BOX-02',
                'Berkas Klaim Asuransi & Rawat Jalan Q1 2024',
                'HRD',
                '',
                '',
                '',
                '',
                '3',
                'Formulir Klaim Medis & Bukti RS Maret 2024',
                '2024-03',
                '2024-03',
                'Karyawan Kantor Pusat & Cabang',
            ],

            // Box 3: Akuntansi (Accounting) - Multi-Bulan Faktur Pajak
            [
                'BOX-03',
                'Faktur Pajak Masukan & Keluaran Masa Pajak 2024',
                'ACC',
                'TAX',
                '6 Bulan',
                '10',
                'Baik / Box Karton Standar TB 30g',
                '1',
                'Faktur Pajak Keluaran Masa Jan - Jun 2024',
                '2024-01',
                '2024-06',
                'Sudah divalidasi DJP e-Faktur',
            ],
        ];

        if (strtolower($format) === 'csv') {
            return $this->buildCsvResponse($headers, $sampleRows, 'template_arsip_multi_box.csv');
        }

        return $this->buildXlsxResponse($headers, $sampleRows, 'template_arsip_multi_box.xlsx', $deptGuide);
    }

    /**
     * Parse uploaded file (XLSX or CSV).
     */
    public function parseFile($file, ?User $user = null): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        $rows = [];
        if (in_array($extension, ['xlsx', 'xlsm'])) {
            $rows = $this->parseXlsx($realPath);
        } else {
            $rows = $this->parseCsv($realPath);
        }

        if (empty($rows)) {
            return [
                'success' => false,
                'errors' => ['File kosong atau format spreadsheet tidak terbaca.'],
                'boxes' => [],
                'total_boxes' => 0,
                'total_items' => 0,
            ];
        }

        // 1. Identify header row
        $headerRowIdx = null;
        $colMap = [];
        foreach ($rows as $idx => $row) {
            $norm = array_map(function ($c) {
                return strtoupper(trim((string)$c));
            }, $row);

            if (in_array('NO_BOX_SEMENTARA', $norm) || in_array('NAMA_DOKUMEN', $norm)) {
                $headerRowIdx = $idx;
                foreach ($norm as $cIdx => $name) {
                    $colMap[$name] = $cIdx;
                }
                break;
            }
        }

        if ($headerRowIdx === null) {
            return [
                'success' => false,
                'errors' => ['Kolom header tidak ditemukan. Pastikan menggunakan template resmi dengan kolom NO_BOX_SEMENTARA dan NAMA_DOKUMEN.'],
                'boxes' => [],
                'total_boxes' => 0,
                'total_items' => 0,
            ];
        }

        // Required headers mapping
        $getVal = function ($row, $headerName, $default = '') use ($colMap) {
            if (isset($colMap[$headerName]) && isset($row[$colMap[$headerName]])) {
                return trim((string)$row[$colMap[$headerName]]);
            }
            return $default;
        };

        // Cache Master Departments & Sub-Departments
        $departmentsByCode = Department::all()->keyBy(function ($d) {
            return strtoupper(trim($d->code));
        });
        $subDeptsByCode = SubDepartment::all()->keyBy(function ($sd) {
            return strtoupper(trim($sd->code));
        });

        $userDeptId = ($user && $user->department_id) ? $user->department_id : null;
        $isPicDept = ($user && $user->isPicDept());

        $grouped = [];
        $errors = [];
        $warnings = [];

        $dataRows = array_slice($rows, $headerRowIdx + 1);
        $rowNum = $headerRowIdx + 1; // 1-indexed Excel row

        foreach ($dataRows as $rIdx => $row) {
            $currentExcelLine = $rowNum + $rIdx + 1;

            // Skip empty rows
            $hasAnyContent = false;
            foreach ($row as $val) {
                if (!empty(trim((string)$val))) {
                    $hasAnyContent = true;
                    break;
                }
            }
            if (!$hasAnyContent) {
                continue;
            }

            $tempBoxId = $getVal($row, 'NO_BOX_SEMENTARA');
            $docName = $getVal($row, 'NAMA_DOKUMEN');

            if (empty($tempBoxId)) {
                $errors[] = "Baris {$currentExcelLine}: 'NO_BOX_SEMENTARA' wajib diisi untuk mengelompokkan dokumen ke dalam kardus.";
                continue;
            }

            if (empty($docName)) {
                $errors[] = "Baris {$currentExcelLine}: 'NAMA_DOKUMEN' tidak boleh kosong.";
                continue;
            }

            $boxTitle = $getVal($row, 'JUDUL_BOX');
            $deptCode = strtoupper($getVal($row, 'KODE_DEPARTEMEN'));
            $subDeptCode = strtoupper($getVal($row, 'KODE_SUB_DEPARTEMEN'));
            $periodeBox = $getVal($row, 'PERIODE_BOX');
            $masaSimpan = $getVal($row, 'MASA_SIMPAN_TAHUN');
            $kondisi = $getVal($row, 'KONDISI_FISIK');
            $itemNum = $getVal($row, 'NO_BUTIR');
            $periodeMulai = $this->normalizeYearMonth($getVal($row, 'PERIODE_DOK_MULAI'));
            $periodeSelesai = $this->normalizeYearMonth($getVal($row, 'PERIODE_DOK_SELESAI'));
            $keterangan = $getVal($row, 'KETERANGAN_BUTIR');

            if (empty($periodeMulai)) {
                $errors[] = "Baris {$currentExcelLine}: 'PERIODE_DOK_MULAI' pada dokumen '{$docName}' tidak valid. Gunakan format YYYY-MM (contoh: 2024-01).";
                continue;
            }
            if (empty($periodeSelesai)) {
                $periodeSelesai = $periodeMulai;
            }

            if (!isset($grouped[$tempBoxId])) {
                // Initialize new box
                $effectiveDept = null;
                if (!empty($deptCode)) {
                    if (isset($departmentsByCode[$deptCode])) {
                        $effectiveDept = $departmentsByCode[$deptCode];
                    } else {
                        $errors[] = "Baris {$currentExcelLine}: Kode Departemen '{$deptCode}' tidak ditemukan di sistem.";
                    }
                } elseif ($userDeptId && isset($departmentsByCode[strtoupper($user->department->code ?? '')])) {
                    $effectiveDept = $departmentsByCode[strtoupper($user->department->code ?? '')];
                }

                if ($isPicDept && $effectiveDept && $effectiveDept->id !== $userDeptId) {
                    $errors[] = "Baris {$currentExcelLine}: Sebagai PIC Departemen, Anda hanya diizinkan mengimpor arsip untuk departemen Anda ({$user->department->name}).";
                }

                $effectiveSubDept = null;
                if (!empty($subDeptCode) && isset($subDeptsByCode[$subDeptCode])) {
                    $effectiveSubDept = $subDeptsByCode[$subDeptCode];
                }

                $grouped[$tempBoxId] = [
                    'temp_box_id' => $tempBoxId,
                    'title' => $boxTitle ?: $docName,
                    'department' => $effectiveDept,
                    'sub_department' => $effectiveSubDept,
                    'periode_box_raw' => $periodeBox,
                    'retention_years_raw' => (int)$masaSimpan > 0 ? (int)$masaSimpan : null,
                    'physical_condition' => $kondisi ?: 'Baik / Box Karton Standar TB 30g',
                    'items' => [],
                    'min_period' => $periodeMulai,
                    'max_period' => $periodeSelesai,
                ];
            } else {
                // Update title if previously empty
                if (empty($grouped[$tempBoxId]['title']) && !empty($boxTitle)) {
                    $grouped[$tempBoxId]['title'] = $boxTitle;
                }
                // Track min and max period
                if ($periodeMulai < $grouped[$tempBoxId]['min_period']) {
                    $grouped[$tempBoxId]['min_period'] = $periodeMulai;
                }
                if ($periodeSelesai > $grouped[$tempBoxId]['max_period']) {
                    $grouped[$tempBoxId]['max_period'] = $periodeSelesai;
                }
            }

            $currentItemsCount = count($grouped[$tempBoxId]['items']) + 1;
            $finalItemNum = (!empty($itemNum) && is_numeric($itemNum)) ? (int)$itemNum : $currentItemsCount;

            $periodText = ($periodeMulai === $periodeSelesai) ? $periodeMulai : "{$periodeMulai} s/d {$periodeSelesai}";

            $grouped[$tempBoxId]['items'][] = [
                'item_number' => $finalItemNum,
                'document_name' => $docName,
                'period_start' => $periodeMulai,
                'period_end' => $periodeSelesai,
                'period_text' => $periodText,
                'notes' => $keterangan ?: null,
            ];
        }

        // 2. Finalize & Calculate Box Metadata
        $finalBoxes = [];
        $totalItems = 0;

        foreach ($grouped as $tempBoxId => $boxData) {
            $dept = $boxData['department'];
            if (!$dept) {
                $errors[] = "Box '{$tempBoxId}': Tidak memiliki informasi Departemen yang valid.";
                continue;
            }

            $minPeriod = $boxData['min_period'];
            $maxPeriod = $boxData['max_period'];

            // Calculate Period Dates
            [$minY, $minM] = explode('-', $minPeriod);
            [$maxY, $maxM] = explode('-', $maxPeriod);

            $startDate = Carbon::createFromDate((int)$minY, (int)$minM, 1)->startOfDay();
            $endDate = Carbon::createFromDate((int)$maxY, (int)$maxM, 1)->endOfMonth()->endOfDay();

            // Calculate total months diff
            $monthsCount = $startDate->diffInMonths($endDate) + 1;
            if ($monthsCount > 60) {
                $warnings[] = "Box '{$tempBoxId}': Rentang periode terdeteksi {$monthsCount} bulan. Dibatasi otomatis ke batas maksimal 60 bulan sesuai standar kearsipan.";
                $monthsCount = 60;
                $endDate = $startDate->copy()->addMonths(60)->endOfMonth()->endOfDay();
            }

            // Determine Box Period Label
            $boxPeriodLabel = $boxData['periode_box_raw'];
            if (empty($boxPeriodLabel)) {
                $boxPeriodLabel = "{$monthsCount} Bulan";
            } elseif (is_numeric($boxPeriodLabel)) {
                $boxPeriodLabel = "{$boxPeriodLabel} Bulan";
            }

            $periodeDoc = ($minPeriod === $maxPeriod) ? $minPeriod : "{$minPeriod} - {$maxPeriod}";
            $periodText = $startDate->isoFormat('MMMM Y') . ($minPeriod !== $maxPeriod ? (' - ' . $endDate->isoFormat('MMMM Y')) : '');

            // Retention calculation
            $retentionYears = $boxData['retention_years_raw'];
            if (!$retentionYears) {
                if ($boxData['sub_department'] && $boxData['sub_department']->retention_years > 0) {
                    $retentionYears = (int)$boxData['sub_department']->retention_years;
                } elseif ($dept->retention_years > 0) {
                    $retentionYears = (int)$dept->retention_years;
                } else {
                    $retentionYears = 5;
                }
            }
            $expiryDate = $endDate->copy()->addYears($retentionYears)->endOfMonth()->format('Y-m-d');

            $finalBoxes[] = [
                'temp_box_id' => $tempBoxId,
                'title' => $boxData['title'],
                'department_id' => $dept->id,
                'department_code' => $dept->code,
                'department_name' => $dept->name,
                'sub_department_id' => $boxData['sub_department'] ? $boxData['sub_department']->id : null,
                'sub_department_code' => $boxData['sub_department'] ? $boxData['sub_department']->code : null,
                'periode' => $boxPeriodLabel,
                'periode_doc' => $periodeDoc,
                'period_text' => $periodText,
                'period_start_date' => $startDate->format('Y-m-d'),
                'period_end_date' => $endDate->format('Y-m-d'),
                'retention_years' => $retentionYears,
                'retention_expiry_date' => $expiryDate,
                'physical_condition' => $boxData['physical_condition'],
                'items_count' => count($boxData['items']),
                'items' => $boxData['items'],
            ];

            $totalItems += count($boxData['items']);
        }

        return [
            'success' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
            'boxes' => $finalBoxes,
            'total_boxes' => count($finalBoxes),
            'total_items' => $totalItems,
        ];
    }

    /**
     * Batch commit archives and items into database via DB transaction.
     */
    public function commitImport(array $boxes, string $status, User $user): array
    {
        $status = in_array($status, ['draft', 'pending_verification']) ? $status : 'draft';

        return DB::transaction(function () use ($boxes, $status, $user) {
            $createdArchives = [];
            $totalItemsCreated = 0;
            $now = now();

            foreach ($boxes as $box) {
                // 1. Prepare item lines summary
                $itemLines = [];
                foreach ($box['items'] as $it) {
                    $itemLines[] = "{$it['item_number']}. {$it['document_name']}" . ($it['period_text'] ? " ({$it['period_text']})" : '');
                }
                $contentDesc = implode("\n", $itemLines);

                // 2. Create Archive
                $archive = Archive::create([
                    'department_id' => $box['department_id'],
                    'sub_department_id' => $box['sub_department_id'] ?? null,
                    'company_name' => 'PT INDRACO GLOBAL INDONESIA',
                    'document_type' => null,
                    'created_by_user_id' => $user->id,
                    'title' => $box['title'],
                    'is_custom_doc_name' => false,
                    'custom_doc_name' => null,
                    'periode' => $box['periode'],
                    'period_start_date' => $box['period_start_date'],
                    'period_end_date' => $box['period_end_date'],
                    'period_text' => $box['period_text'],
                    'period_yy_mm' => $box['periode_doc'],
                    'periode_doc' => $box['periode_doc'],
                    'tgl_penyerahan' => now()->format('Y-m-d'),
                    'content_description' => $contentDesc,
                    'retention_years' => $box['retention_years'],
                    'masa_simpan_custom' => null,
                    'retention_expiry_date' => $box['retention_expiry_date'],
                    'physical_condition' => $box['physical_condition'],
                    'file_path' => null,
                    'scan_input_form' => null,
                    'status' => $status,
                ]);

                // 3. Batch insert ArchiveItems
                $itemsToInsert = array_map(function ($it) use ($archive, $now) {
                    return [
                        'archive_id' => $archive->id,
                        'item_number' => $it['item_number'],
                        'document_name' => $it['document_name'],
                        'period_start' => $it['period_start'] ?? null,
                        'period_end' => $it['period_end'] ?? null,
                        'period_text' => $it['period_text'] ?? null,
                        'notes' => $it['notes'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $box['items']);

                foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                    ArchiveItem::insert($chunk);
                }

                $createdArchives[] = $archive;
                $totalItemsCreated += count($itemsToInsert);
            }

            // 4. Activity Log
            $statusLabel = ($status === 'draft') ? 'Draft Sementara' : 'Antrean Verifikasi Gudang';
            ActivityLogger::log(
                'ARCHIVE_BATCH_IMPORT',
                "Mengimpor massal {$totalItemsCreated} butir dokumen ke dalam " . count($createdArchives) . " box arsip (Status: {$statusLabel}) oleh {$user->name}.",
                'IMPORT_EXCEL',
                [
                    'boxes_count' => count($createdArchives),
                    'items_count' => $totalItemsCreated,
                    'status' => $status,
                ],
                count($createdArchives) . " Box Arsip"
            );

            return [
                'boxes_count' => count($createdArchives),
                'items_count' => $totalItemsCreated,
                'status' => $status,
                'archives' => $createdArchives,
            ];
        });
    }

    /**
     * Native XLSX Parser using ZipArchive and SimpleXML.
     */
    protected function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Shared Strings Table
        $sharedStrings = [];
        $ssContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssContent) {
            $ssXml = @simplexml_load_string($ssContent);
            if ($ssXml && isset($ssXml->si)) {
                foreach ($ssXml->si as $si) {
                    $val = '';
                    if (isset($si->t)) {
                        $val = (string)$si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $val .= (string)$r->t;
                        }
                    }
                    $sharedStrings[] = $val;
                }
            }
        }

        // 2. Worksheet Sheet1
        $sheetContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetContent) {
            // Check for any sheet in xl/worksheets/
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (strpos($entry, 'xl/worksheets/') === 0 && substr($entry, -4) === '.xml') {
                    $sheetContent = $zip->getFromName($entry);
                    break;
                }
            }
        }

        $rows = [];
        if ($sheetContent) {
            $sheetXml = @simplexml_load_string($sheetContent);
            if ($sheetXml && isset($sheetXml->sheetData->row)) {
                foreach ($sheetXml->sheetData->row as $row) {
                    $rowCells = [];
                    foreach ($row->c as $c) {
                        $attr = $c->attributes();
                        $r = isset($attr['r']) ? (string)$attr['r'] : '';
                        $colIdx = $this->colLetterToIndex($r);

                        $t = isset($attr['t']) ? (string)$attr['t'] : '';
                        $val = '';
                        if ($t === 's') {
                            $sIdx = (int)$c->v;
                            $val = $sharedStrings[$sIdx] ?? '';
                        } elseif ($t === 'inlineStr') {
                            $val = (string)$c->is->t;
                        } else {
                            $val = (string)$c->v;
                        }

                        $rowCells[$colIdx] = trim($val);
                    }

                    // Fill gaps up to max col index
                    if (!empty($rowCells)) {
                        $maxCol = max(array_keys($rowCells));
                        $denseRow = [];
                        for ($c = 0; $c <= $maxCol; $c++) {
                            $denseRow[] = $rowCells[$c] ?? '';
                        }
                        $rows[] = $denseRow;
                    }
                }
            }
        }

        $zip->close();
        return $rows;
    }

    /**
     * Parse CSV with auto-detection of delimiter and UTF-8 BOM removal.
     */
    protected function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $rows = [];
        $isFirst = true;
        while (($data = fgetcsv($handle, 10000, $delimiter)) !== false) {
            if ($isFirst && !empty($data)) {
                // Strip UTF-8 BOM if present
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                $isFirst = false;
            }
            $rows[] = array_map('trim', $data);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Convert cell coordinate (e.g. "A1", "B2", "AA10") to 0-indexed column integer.
     */
    protected function colLetterToIndex(string $cellRef): int
    {
        if (preg_match('/^([A-Z]+)/i', $cellRef, $matches)) {
            $letters = strtoupper($matches[1]);
            $len = strlen($letters);
            $num = 0;
            for ($i = 0; $i < $len; $i++) {
                $num = $num * 26 + (ord($letters[$i]) - ord('A') + 1);
            }
            return $num - 1;
        }
        return 0;
    }

    /**
     * Normalize date string to YYYY-MM.
     */
    protected function normalizeYearMonth(string $raw): ?string
    {
        $raw = trim($raw);
        if (empty($raw)) {
            return null;
        }

        // Already YYYY-MM
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $raw, $m)) {
            return "{$m[1]}-{$m[2]}";
        }

        // YYYY/MM
        if (preg_match('/^(\d{4})\/(0[1-9]|1[0-2])$/', $raw, $m)) {
            return "{$m[1]}-{$m[2]}";
        }

        // MM-YYYY or MM/YYYY
        if (preg_match('/^(0[1-9]|1[0-2])[\/-](\d{4})$/', $raw, $m)) {
            return "{$m[2]}-{$m[1]}";
        }

        // Year only: YYYY -> YYYY-01
        if (preg_match('/^(\d{4})$/', $raw, $m)) {
            return "{$m[1]}-01";
        }

        // Excel serial date number
        if (is_numeric($raw) && (int)$raw > 30000 && (int)$raw < 60000) {
            $unixTimestamp = ((int)$raw - 25569) * 86400;
            return date('Y-m', $unixTimestamp);
        }

        // Indonesian Month Name: e.g. "Januari 2024" or "Jan 2024"
        $monthMap = [
            'jan' => '01', 'januari' => '01',
            'feb' => '02', 'februari' => '02',
            'mar' => '03', 'maret' => '03',
            'apr' => '04', 'april' => '04',
            'mei' => '05', 'may' => '05',
            'jun' => '06', 'juni' => '06',
            'jul' => '07', 'juli' => '07',
            'agu' => '08', 'agustus' => '08', 'aug' => '08',
            'sep' => '09', 'september' => '09',
            'okt' => '10', 'oktober' => '10', 'oct' => '10',
            'nov' => '11', 'november' => '11',
            'des' => '12', 'desember' => '12', 'dec' => '12',
        ];

        $lower = strtolower($raw);
        foreach ($monthMap as $monthName => $num) {
            if (strpos($lower, $monthName) !== false) {
                if (preg_match('/(\d{4})/', $raw, $yMatches)) {
                    return "{$yMatches[1]}-{$num}";
                }
            }
        }

        return null;
    }

    /**
     * Build clean XLSX file response.
     */
    protected function buildXlsxResponse(array $headers, array $rows, string $filename, string $deptGuide)
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'dms_xlsx_');
        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="Template Multi Box" sheetId="1" r:id="rId1"/></sheets>' .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $wb);

        // 5. xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetData>';

        $allData = array_merge([$headers], $rows);
        $colLetters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N'];

        foreach ($allData as $rIdx => $row) {
            $rNum = $rIdx + 1;
            $sheetXml .= '<row r="' . $rNum . '">';
            foreach ($row as $cIdx => $val) {
                $col = $colLetters[$cIdx] ?? 'A';
                $escaped = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
                $sheetXml .= '<c r="' . $col . $rNum . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetXml .= '</row>';
        }

        // Add 2 blank rows and Department Code Guide
        $guideRow1 = count($allData) + 2;
        $guideRow2 = count($allData) + 3;
        $sheetXml .= '<row r="' . $guideRow1 . '"><c r="A' . $guideRow1 . '" t="inlineStr"><is><t>[PANDUAN REFERENSI KODE DEPARTEMEN RESMI]</t></is></c></row>';
        $sheetXml .= '<row r="' . $guideRow2 . '"><c r="A' . $guideRow2 . '" t="inlineStr"><is><t>' . htmlspecialchars($deptGuide, ENT_XML1, 'UTF-8') . '</t></is></c></row>';

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build clean CSV file response with UTF-8 BOM.
     */
    protected function buildCsvResponse(array $headers, array $rows, string $filename)
    {
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $f = fopen('php://memory', 'r+');
        fputcsv($f, $headers);
        foreach ($rows as $row) {
            fputcsv($f, $row);
        }
        rewind($f);
        $output .= stream_get_contents($f);
        fclose($f);

        return response($output, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
