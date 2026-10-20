<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use SimpleXMLElement;

class MultiBoxExcelService
{
    /**
     * Generate template XLSX or CSV with sample multi-box data and reference guide.
     */
    public function generateTemplate(string $format = 'xlsx', ?User $user = null)
    {
        $isPicDept = ($user && $user->isPicDept());

        if ($isPicDept) {
            // Tampilan / Hak Akses Departemen: Pure data box, isi berkas, dan kode dept (Tanpa kolom rak gudang)
            $headers = [
                'NO_BOX_SEMENTARA',
                'JUDUL_BOX',
                'KODE_DEPARTEMEN',
                'KODE_SUB_DEPARTEMEN',
                'NAMA_DOKUMEN',
                'PERIODE_DOK_MULAI',
                'PERIODE_DOK_SELESAI',
                'MASA_SIMPAN_BULAN',
                'KONDISI_FISIK',
                'NO_BUTIR',
                'KETERANGAN_BUTIR',
            ];
        } else {
            // Tampilan Gudang / Admin: Lengkap dengan kolom alokasi rak fisik gudang
            $headers = [
                'NO_BOX_SEMENTARA',
                'JUDUL_BOX',
                'KODE_DEPARTEMEN',
                'KODE_SUB_DEPARTEMEN',
                'NAMA_DOKUMEN',
                'PERIODE_DOK_MULAI',
                'PERIODE_DOK_SELESAI',
                'MASA_SIMPAN_BULAN',
                'KONDISI_FISIK',
                'NO_BUTIR',
                'LOKASI_RUANG',
                'LOKASI_RAK',
                'NO_SLOT',
                'KETERANGAN_BUTIR',
            ];
        }

        // Scope-aware sample rows: Identitas box cukup diisi satu kali pada baris pertama box. Baris butir berikutnya cukup nama dokumen. No butir auto jika kosong.
        if ($isPicDept && $user->department_id) {
            $userDept = $user->department ?: Department::find($user->department_id);
            $userDeptCode = $userDept ? $userDept->code : 'DEPT';
            $userDeptName = $userDept ? $userDept->name : 'Departemen';
            $userSubDepts = SubDepartment::where('department_id', $user->department_id)->where('is_active', true)->orderBy('code')->get();
            $subDeptList = $userSubDepts->pluck('code')->toArray();
            $firstSubDeptCode = $subDeptList[0] ?? ($userDeptCode === 'HRD' ? 'GA' : 'GEN');
            $secondSubDeptCode = $subDeptList[1] ?? ($userDeptCode === 'HRD' ? 'LGL' : $firstSubDeptCode);

            $sampleRows = [
                // Box 1: Baris Pertama - Mengisi Identitas Box Lengkap (Masa simpan angka bulan murni: 60)
                [
                    'BOX-01',
                    "Berkas Operasional {$userDeptName} Jan 2024",
                    $userDeptCode,
                    $firstSubDeptCode,
                    'Dokumen Administrasi Utama Bagian 01',
                    '2024-01',
                    '2024-01',
                    '60',
                    'Baik / Box Karton Standar TB 30g',
                    '1',
                    'Asli bertandatangan',
                ],
                // Box 1: Baris Kedua - Identitas Box Dikosongkan (Cukup isi sekali per box) & No Butir Auto
                [
                    '', // NO_BOX_SEMENTARA (Otomatis mewarisi BOX-01)
                    '', // JUDUL_BOX (Otomatis mewarisi judul box di atas)
                    '', // KODE_DEPARTEMEN
                    '', // KODE_SUB_DEPARTEMEN
                    'Dokumen Administrasi Utama Bagian 02',
                    '2024-01',
                    '2024-01',
                    '', // MASA_SIMPAN_BULAN (Otomatis mewarisi 60)
                    '', // KONDISI_FISIK
                    '', // NO_BUTIR (Dikosongkan: otomatis di-generate 2 oleh sistem)
                    'Asli bertandatangan',
                ],

                // Box 2: Baris Pertama - Box Baru (Masa simpan 12 bulan)
                [
                    'BOX-02',
                    "Laporan & Formulir {$userDeptName} Periode 2025",
                    $userDeptCode,
                    $secondSubDeptCode,
                    'Formulir Kegiatan & Laporan Rekapitulasi',
                    'Jan 25',
                    'Des 25',
                    '12',
                    'Baik / Box Karton Standar TB 30g',
                    '', // NO_BUTIR (Dikosongkan: otomatis di-generate 1 oleh sistem)
                    'Lengkap',
                ],
                // Box 2: Baris Kedua - Identitas Box Dikosongkan & No Butir Auto
                [
                    '', // NO_BOX_SEMENTARA (Otomatis mewarisi BOX-02)
                    '', // JUDUL_BOX
                    '', // KODE_DEPARTEMEN
                    '', // KODE_SUB_DEPARTEMEN
                    'Daftar Checklist & Lampiran Pendukung',
                    'Jan 25',
                    'Des 25',
                    '', // MASA_SIMPAN_BULAN
                    '', // KONDISI_FISIK
                    '', // NO_BUTIR (Dikosongkan: otomatis di-generate 2 oleh sistem)
                    'Lengkap',
                ],
            ];
        } else {
            $sampleRows = [
                // Box 1: Keuangan (Finance) - Baris Pertama (Masa simpan angka bulan murni: 60)
                [
                    'BOX-01',
                    'Voucher Pengeluaran Kas & Bank Jan 2024',
                    'FIN',
                    'KAS',
                    'Kwitansi & Bukti Kas Keluar No. 001 - 050',
                    '2024-01',
                    '2024-01',
                    '60',
                    'Baik / Box Karton Standar TB 30g',
                    '1',
                    'R5',
                    'R5-AS',
                    '1',
                    'Asli bertandatangan Kasir & Mgr',
                ],
                // Box 1: Baris Kedua - Identitas Box Cukup Sekali & No Butir Dikosongkan (Auto Urut)
                [
                    '', // NO_BOX_SEMENTARA (Mewarisi BOX-01)
                    '', // JUDUL_BOX
                    '', // KODE_DEPARTEMEN
                    '', // KODE_SUB_DEPARTEMEN
                    'Kwitansi & Bukti Kas Keluar No. 051 - 100',
                    '2024-01',
                    '2024-01',
                    '', // MASA_SIMPAN_BULAN
                    '', // KONDISI_FISIK
                    '', // NO_BUTIR (Auto urut: 2)
                    '', // RUANG (Mewarisi R5)
                    '', // RAK (Mewarisi R5-AS)
                    '', // SLOT (Mewarisi 1)
                    'Asli bertandatangan Kasir & Mgr',
                ],

                // Box 2: HCS / GA - Baris Pertama (Masa simpan angka bulan murni: 12)
                [
                    'BOX-02',
                    'Berkas Formulir Utilitas & Maintenance GA 2025',
                    'HCS',
                    'GA',
                    'Form Utilitas & Perawatan AC',
                    'Jan 25',
                    'Des 25',
                    '12',
                    'Baik / Box Karton Standar TB 30g',
                    '', // NO_BUTIR (Auto urut: 1)
                    'R5',
                    'R5-AT',
                    '2',
                    'Karyawan Kantor Pusat',
                ],
                // Box 2: Baris Kedua - Identitas Box Cukup Sekali & No Butir Dikosongkan
                [
                    '', // NO_BOX_SEMENTARA (Mewarisi BOX-02)
                    '', // JUDUL_BOX
                    '', // KODE_DEPARTEMEN
                    '', // KODE_SUB_DEPARTEMEN
                    'Data Maintenance Kendaraan Operasional',
                    'Jan 25',
                    'Des 25',
                    '', // MASA_SIMPAN_BULAN
                    '', // KONDISI_FISIK
                    '', // NO_BUTIR (Auto urut: 2)
                    '', // RUANG
                    '', // RAK
                    '', // SLOT
                    'Armada Operasional Logistik',
                ],

                // Box 3: Akuntansi (Accounting) - Baris Pertama (Masa simpan angka bulan murni: 120 = 10 Tahun)
                [
                    'BOX-03',
                    'Faktur Pajak Masukan & Keluaran Masa Pajak 2024',
                    'ACC',
                    'TAX',
                    'Faktur Pajak Keluaran Masa Jan - Jun 2024',
                    '2024-01',
                    '2024-06',
                    '120',
                    'Baik / Box Karton Standar TB 30g',
                    '', // NO_BUTIR (Auto urut: 1)
                    'R5',
                    'R5-AU',
                    '1',
                    'Sudah divalidasi DJP e-Faktur',
                ],
            ];
        }

        if (strtolower($format) === 'csv') {
            return $this->buildCsvResponse($headers, $sampleRows, 'template_arsip_multi_box.csv');
        }

        return $this->buildXlsxResponse($headers, $sampleRows, 'template_arsip_multi_box.xlsx', $user);
    }

    /**
     * Parse uploaded file (XLSX, XLSM, or CSV) with smart multi-header and format tolerance.
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

        // 1. Identify header row with smart alias mapping
        [$colMap, $dataStartIdx] = $this->detectHeaderAndCols($rows);

        if (!isset($colMap['doc_name']) && !isset($colMap['box'])) {
            return [
                'success' => false,
                'errors' => ['Kolom header tidak ditemukan. Pastikan menggunakan template resmi atau file Excel yang memiliki kolom Box / List Dokumen / Departemen.'],
                'boxes' => [],
                'total_boxes' => 0,
                'total_items' => 0,
            ];
        }

        // Value extractor helper
        $getVal = function ($row, $key, $default = '') use ($colMap) {
            if (isset($colMap[$key]) && isset($row[$colMap[$key]])) {
                return trim((string)$row[$colMap[$key]]);
            }
            return $default;
        };

        // Cache Master Departments & Sub-Departments
        $departments = Department::where('is_active', true)->get();
        $subDepts = SubDepartment::where('is_active', true)->get();

        $userDeptId = ($user && $user->department_id) ? $user->department_id : null;
        $isPicDept = ($user && $user->isPicDept());

        $grouped = [];
        $errors = [];
        $warnings = [];

        $dataRows = array_slice($rows, $dataStartIdx);
        $rowNum = $dataStartIdx; // 0-indexed row offset

        $currentBoxId = null;
        $currentDept = null;
        $currentSubDept = null;
        $currentTitle = null;
        $currentPeriodBox = null;
        $currentRetention = null;
        $currentCondition = null;
        $currentRoom = null;
        $currentRack = null;
        $currentSlot = null;

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

            $docName = $getVal($row, 'doc_name');
            if (empty($docName)) {
                // If this row has no document name, ignore as blank / commentary row
                continue;
            }

            // Extract box ID or carry-over from preceding row in same box
            $rawBoxId = $getVal($row, 'box');
            if (!empty($rawBoxId)) {
                // Ketika beralih ke Box Baru, reset metadata box carry-over yang lama
                if ($currentBoxId !== null && $rawBoxId !== $currentBoxId) {
                    $currentDept = null;
                    $currentSubDept = null;
                    $currentTitle = null;
                    $currentPeriodBox = null;
                    $currentRetention = null;
                    $currentCondition = null;
                    $currentRoom = null;
                    $currentRack = null;
                    $currentSlot = null;
                }
                $currentBoxId = $rawBoxId;
            }
            if (empty($currentBoxId)) {
                // Default fallback box ID if completely omitted on row 1
                $currentBoxId = 'BOX-01';
            }

            // Extract metadata or carry-over
            $rawBoxTitle = $getVal($row, 'title');
            if (!empty($rawBoxTitle)) {
                $currentTitle = $rawBoxTitle;
            }

            $rawDept = $getVal($row, 'dept');
            if (!empty($rawDept)) {
                $currentDept = $rawDept;
            }

            $rawSubDept = $getVal($row, 'sub_dept');
            if (!empty($rawSubDept)) {
                $currentSubDept = $rawSubDept;
            }

            $rawPeriodBox = $getVal($row, 'period_box');
            if (!empty($rawPeriodBox)) {
                $currentPeriodBox = $rawPeriodBox;
            }

            $rawRetention = $getVal($row, 'retention');
            if (!empty($rawRetention)) {
                $currentRetention = $rawRetention;
            }

            $rawCondition = $getVal($row, 'condition');
            if (!empty($rawCondition)) {
                $currentCondition = $rawCondition;
            }

            $rawRoom = $getVal($row, 'room');
            if (!empty($rawRoom)) {
                $currentRoom = $rawRoom;
            }

            $rawRack = $getVal($row, 'rack');
            if (!empty($rawRack)) {
                $currentRack = $rawRack;
            }

            $rawSlot = $getVal($row, 'slot');
            if (!empty($rawSlot)) {
                $currentSlot = $rawSlot;
            }

            $itemNum = $getVal($row, 'item_num');
            $keterangan = $getVal($row, 'notes');

            // Parse document period
            $periodeMulai = null;
            $periodeSelesai = null;

            if (isset($colMap['period_combined']) && !empty($getVal($row, 'period_combined'))) {
                [$periodeMulai, $periodeSelesai] = $this->parsePeriodRange($getVal($row, 'period_combined'));
            } else {
                $rawStart = $getVal($row, 'period_start');
                $rawEnd = $getVal($row, 'period_end');
                $periodeMulai = $this->normalizeYearMonth($rawStart);
                $periodeSelesai = $this->normalizeYearMonth($rawEnd);
            }

            if (empty($periodeMulai)) {
                // Fallback to current year-month if unparseable or omitted
                $periodeMulai = now()->format('Y-m');
            }
            if (empty($periodeSelesai)) {
                $periodeSelesai = $periodeMulai;
            }

            // Resolve Department & Sub-Department
            $effectiveDept = null;
            if (!empty($currentDept)) {
                $effectiveDept = $departments->first(function ($d) use ($currentDept) {
                    return strtoupper(trim($d->code)) === strtoupper(trim($currentDept))
                        || strcasecmp(trim($d->name), trim($currentDept)) === 0
                        || stripos($d->name, trim($currentDept)) !== false;
                });
            } elseif ($userDeptId) {
                $effectiveDept = $departments->firstWhere('id', $userDeptId);
            }

            $effectiveSubDept = null;
            if (!empty($currentSubDept)) {
                $effectiveSubDept = $subDepts->first(function ($sd) use ($currentSubDept, $effectiveDept) {
                    $codeOrNameMatch = (strtoupper(trim($sd->code)) === strtoupper(trim($currentSubDept))
                        || strcasecmp(trim($sd->name), trim($currentSubDept)) === 0
                        || stripos($sd->name, trim($currentSubDept)) !== false);

                    if ($effectiveDept) {
                        return $codeOrNameMatch && $sd->department_id === $effectiveDept->id;
                    }
                    return $codeOrNameMatch;
                });

                // Infer department from sub-department if department was null
                if ($effectiveSubDept && !$effectiveDept) {
                    $effectiveDept = $departments->firstWhere('id', $effectiveSubDept->department_id);
                }
            }

            // Enforce PIC Department security
            if ($isPicDept && $effectiveDept && $effectiveDept->id !== $userDeptId) {
                $errors[] = "Baris {$currentExcelLine}: Sebagai PIC Departemen, Anda hanya diizinkan mengimpor arsip untuk departemen Anda ({$user->department->name}).";
            }

            // Parse retention
            $parsedRetentionYears = $this->parseRetentionYears($currentRetention, 5);

            // Initialize new Box if not already tracked
            if (!isset($grouped[$currentBoxId])) {
                $autoTitle = !empty($currentTitle) ? $currentTitle : ("Arsip {$currentBoxId} " . ($effectiveDept ? $effectiveDept->name : '') . " - {$docName}");

                $grouped[$currentBoxId] = [
                    'temp_box_id' => $currentBoxId,
                    'title' => $autoTitle,
                    'department' => $effectiveDept,
                    'sub_department' => $effectiveSubDept,
                    'periode_box_raw' => $currentPeriodBox,
                    'retention_years_raw' => $parsedRetentionYears,
                    'physical_condition' => $currentCondition ?: 'Baik / Box Karton Standar TB 30g',
                    'location_room' => $currentRoom,
                    'location_rack' => $currentRack,
                    'location_slot' => $currentSlot,
                    'items' => [],
                    'min_period' => $periodeMulai,
                    'max_period' => $periodeSelesai,
                ];
            } else {
                // Update title if previously default and row has specific title
                if (!empty($rawBoxTitle)) {
                    $grouped[$currentBoxId]['title'] = $rawBoxTitle;
                }
                // Track min and max period
                if ($periodeMulai < $grouped[$currentBoxId]['min_period']) {
                    $grouped[$currentBoxId]['min_period'] = $periodeMulai;
                }
                if ($periodeSelesai > $grouped[$currentBoxId]['max_period']) {
                    $grouped[$currentBoxId]['max_period'] = $periodeSelesai;
                }
            }

            $currentItemsCount = count($grouped[$currentBoxId]['items']) + 1;
            $finalItemNum = (!empty($itemNum) && is_numeric($itemNum)) ? (int)$itemNum : $currentItemsCount;
            $periodText = ($periodeMulai === $periodeSelesai) ? $periodeMulai : "{$periodeMulai} s/d {$periodeSelesai}";

            $grouped[$currentBoxId]['items'][] = [
                'item_number' => $finalItemNum,
                'document_name' => $docName,
                'name' => $docName,
                'period_start' => $periodeMulai,
                'period_end' => $periodeSelesai,
                'period_text' => $periodText,
                'notes' => $keterangan ?: null,
                'description' => $keterangan ?: null,
            ];
        }

        // 2. Finalize & Calculate Box Metadata
        $finalBoxes = [];
        $totalItems = 0;

        foreach ($grouped as $tempBoxId => $boxData) {
            $dept = $boxData['department'];
            if (!$dept) {
                $errors[] = "Box '{$tempBoxId}': Tidak memiliki informasi Departemen yang valid. Harap periksa kolom DEPARTEMEN.";
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
            $isFat = in_array(strtoupper($dept->code ?? ''), ['FAT', 'FIN', 'FATCLM']);
            $maxPeriodMonths = $isFat ? 120 : 60;
            $monthsCount = $startDate->diffInMonths($endDate) + 1;
            if ($monthsCount > $maxPeriodMonths) {
                $warnings[] = "Box '{$tempBoxId}': Rentang periode terdeteksi {$monthsCount} bulan. Dibatasi otomatis ke batas maksimal {$maxPeriodMonths} bulan sesuai standar kearsipan.";
                $monthsCount = $maxPeriodMonths;
                $endDate = $startDate->copy()->addMonths($maxPeriodMonths)->endOfMonth()->endOfDay();
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
            if (!$retentionYears || $retentionYears <= 0) {
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
                'name' => $boxData['title'],
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
                'location_room' => $boxData['location_room'],
                'location_rack' => $boxData['location_rack'],
                'location_slot' => $boxData['location_slot'],
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
        $status = in_array($status, ['draft', 'pending_verification', 'in_warehouse']) ? $status : 'draft';

        return DB::transaction(function () use ($boxes, $status, $user) {
            $createdArchives = [];
            $totalItemsCreated = 0;
            $now = now();

            foreach ($boxes as $box) {
                // 1. Prepare item lines summary
                $itemLines = [];
                foreach ($box['items'] as $it) {
                    $itemDocName = $it['document_name'] ?? $it['name'] ?? '';
                    $itemLines[] = "{$it['item_number']}. {$itemDocName}" . (!empty($it['period_text']) ? " ({$it['period_text']})" : '');
                }
                $contentDesc = implode("\n", $itemLines);

                // 2. Resolve warehouse location if room/rack provided
                $locationId = null;
                $slotId = null;

                if (!empty($box['location_room']) || !empty($box['location_rack'])) {
                    $room = trim($box['location_room'] ?? '');
                    $rack = trim($box['location_rack'] ?? '');
                    $slotNum = trim($box['location_slot'] ?? '');

                    $loc = WarehouseLocation::where(function ($q) use ($room, $rack) {
                        if ($room && $rack) {
                            $q->where(function ($sub) use ($room, $rack) {
                                $sub->where('room_sector', $room)
                                    ->where(function ($rSub) use ($rack, $room) {
                                        $rSub->where('rack_code', $rack)
                                             ->orWhere('rack_code', "{$room}-{$rack}")
                                             ->orWhere('rack_code', "RAK-{$rack}")
                                             ->orWhere('rack_code', "RAK-{$room}-{$rack}");
                                    });
                            })->orWhere('rack_code', "{$room}-{$rack}")
                              ->orWhere('rack_code', $rack);
                        } elseif ($rack) {
                            $q->where('rack_code', $rack)
                              ->orWhere('rack_code', 'LIKE', "%{$rack}%");
                        }
                    })->first();

                    if ($loc) {
                        $locationId = $loc->id;
                        if (!empty($slotNum) && is_numeric($slotNum)) {
                            $slotObj = WarehouseRackSlot::where('warehouse_location_id', $loc->id)
                                ->where('slot_number', (int)$slotNum)
                                ->first();
                            if ($slotObj) {
                                $slotId = $slotObj->id;
                                if ($status === 'in_warehouse') {
                                    $slotObj->update([
                                        'archive_id' => null, // Will be set after archive is created
                                        'status' => 'filled',
                                    ]);
                                    $loc->increment('current_box_count');
                                }
                            }
                        }
                    }
                }

                // 3. Create Archive
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
                    'warehouse_location_id' => $locationId,
                    'warehouse_rack_slot_id' => $slotId,
                    'file_path' => null,
                    'scan_input_form' => null,
                    'status' => $status,
                ]);

                // Link slot to archive if assigned
                if ($slotId) {
                    WarehouseRackSlot::where('id', $slotId)->update(['archive_id' => $archive->id]);
                }

                // 4. Batch insert ArchiveItems
                $itemsToInsert = array_map(function ($it) use ($archive, $now) {
                    return [
                        'archive_id' => $archive->id,
                        'item_number' => $it['item_number'],
                        'document_name' => $it['document_name'] ?? $it['name'] ?? '',
                        'period_start' => $it['period_start'] ?? null,
                        'period_end' => $it['period_end'] ?? null,
                        'period_text' => $it['period_text'] ?? null,
                        'notes' => $it['notes'] ?? $it['description'] ?? null,
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

            // 5. Activity Log
            $statusLabel = ($status === 'draft') ? 'Draft Sementara' : (($status === 'in_warehouse') ? 'Tersimpan di Rak Gudang (Data Existing)' : 'Antrean Verifikasi Gudang');
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
     * Smart Header and Column Detection.
     */
    protected function detectHeaderAndCols(array $rows): array
    {
        $headerMap = [];
        $dataStartIdx = 1;

        $knownAliases = [
            'box' => ['NO_BOX_SEMENTARA', 'NO_BOX', 'BOX', 'KODE_BOX', 'NOMOR_BOX', 'NO BOX'],
            'title' => ['JUDUL_BOX', 'NAMA_BOX', 'DESKRIPSI_BOX', 'TITLE', 'JUDUL'],
            'dept' => ['KODE_DEPARTEMEN', 'DEPARTEMEN', 'DEPT', 'DEPARTMENT', 'DEPARTEMENT'],
            'sub_dept' => ['KODE_SUB_DEPARTEMEN', 'SUB_DEPARTEMEN', 'SUB_DEPT', 'SUB_DEPT.', 'SUB DEPT', 'SUB DEPT.'],
            'doc_name' => ['NAMA_DOKUMEN', 'LIST_DOKUMEN', 'LIST DOKUMEN', 'DOKUMEN', 'JUDUL_DOKUMEN', 'NAMA DOKUMEN'],
            'item_num' => ['NO_BUTIR', 'NO.', 'NO', 'NOMOR', 'ITEM_NO'],
            'period_combined' => ['PERIODE_DOKUMEN', 'PERIODE DOKUMEN', 'PERIODE', 'PERIODE_BOX', 'PERIODE DOK'],
            'period_start' => ['PERIODE_DOK_MULAI', 'PERIODE_MULAI', 'MULAI', 'START_DATE'],
            'period_end' => ['PERIODE_DOK_SELESAI', 'PERIODE_SELESAI', 'SELESAI', 'END_DATE'],
            'retention' => ['MASA_SIMPAN_BULAN', 'MASA_SIMPAN', 'RETENSI_BULAN', 'MASA SIMPAN BULAN', 'MASA_SIMPAN_TAHUN', 'MASA SIMPAN', 'RETENSI', 'RETENTION'],
            'condition' => ['KONDISI_FISIK', 'KONDISI', 'KONDISI FISIK', 'PHYSICAL_CONDITION'],
            'room' => ['RUANG', 'GUDANG', 'LOKASI_RUANG', 'ROOM'],
            'rack' => ['RAK', 'LOKASI_RAK', 'RACK'],
            'slot' => ['NO_SLOT', 'SLOT', 'NOMOR_SLOT'],
            'notes' => ['KETERANGAN_BUTIR', 'KETERANGAN', 'CATATAN', 'NOTES'],
        ];

        // Scan first 5 rows for main header
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $row = $rows[$i];

            foreach ($row as $colIdx => $cell) {
                $clean = strtoupper(trim(preg_replace('/[_\s]+/', ' ', (string)$cell)));
                if (empty($clean)) {
                    continue;
                }

                foreach ($knownAliases as $key => $aliases) {
                    foreach ($aliases as $alias) {
                        $normAlias = strtoupper(trim(preg_replace('/[_\s]+/', ' ', $alias)));
                        if ($clean === $normAlias) {
                            $headerMap[$key] = $colIdx;
                            break 2;
                        }
                    }
                }
            }

            // If we found essential keys (e.g. doc_name or box or dept)
            if (isset($headerMap['doc_name']) || isset($headerMap['box'])) {
                $dataStartIdx = $i + 1;

                // Check if next row has sub-headers (like Ruang, Rak, No under Lokasi)
                if (isset($rows[$i + 1])) {
                    $nextRow = $rows[$i + 1];
                    $hasSubHeaders = false;
                    foreach ($nextRow as $colIdx => $cell) {
                        $clean = strtoupper(trim((string)$cell));
                        if (in_array($clean, ['RUANG', 'GUDANG', 'ROOM'])) {
                            $headerMap['room'] = $colIdx;
                            $hasSubHeaders = true;
                        } elseif (in_array($clean, ['RAK', 'RACK'])) {
                            $headerMap['rack'] = $colIdx;
                            $hasSubHeaders = true;
                        } elseif (in_array($clean, ['NO', 'SLOT', 'NO.']) && isset($headerMap['rack']) && $colIdx > $headerMap['rack']) {
                            $headerMap['slot'] = $colIdx;
                            $hasSubHeaders = true;
                        }
                    }
                    if ($hasSubHeaders) {
                        $dataStartIdx = $i + 2;
                    }
                }
                break;
            }
        }

        return [$headerMap, $dataStartIdx];
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
     * Smart Period Range parser (e.g. "jan 25- Des 25" -> ["2025-01", "2025-12"]).
     */
    protected function parsePeriodRange(string $raw): array
    {
        $raw = trim($raw);
        if (empty($raw)) {
            return [null, null];
        }

        $parts = preg_split('/\s*(?:-|s\/d|sd|to|\/)\s*/i', $raw);
        if (count($parts) >= 2) {
            $start = $this->normalizeYearMonth($parts[0]);
            $end = $this->normalizeYearMonth($parts[count($parts) - 1]);
            if ($start && !$end) {
                $end = $start;
            }
            if (!$start && $end) {
                $start = $end;
            }
            return [$start, $end];
        }

        $single = $this->normalizeYearMonth($raw);
        return [$single, $single];
    }

    /**
     * Normalize date string to YYYY-MM with 2-digit and 4-digit year support.
     */
    protected function normalizeYearMonth(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = trim($raw);
        if (empty($raw)) {
            return null;
        }

        // 1. Already YYYY-MM
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $raw, $m)) {
            return "{$m[1]}-{$m[2]}";
        }

        // 2. YYYY/MM
        if (preg_match('/^(\d{4})\/(0[1-9]|1[0-2])$/', $raw, $m)) {
            return "{$m[1]}-{$m[2]}";
        }

        // 3. MM-YYYY or MM/YYYY
        if (preg_match('/^(0[1-9]|1[0-2])[\/-](\d{4})$/', $raw, $m)) {
            return "{$m[2]}-{$m[1]}";
        }

        // 4. Year only: YYYY -> YYYY-01
        if (preg_match('/^(\d{4})$/', $raw, $m)) {
            return "{$m[1]}-01";
        }

        // 5. Excel serial date number
        if (is_numeric($raw) && (int)$raw > 30000 && (int)$raw < 60000) {
            $unixTimestamp = ((int)$raw - 25569) * 86400;
            return date('Y-m', $unixTimestamp);
        }

        // 6. Indonesian / English Month Name: e.g. "Jan 25", "Januari 2024", "Sept 25"
        $monthMap = [
            'jan' => '01', 'januari' => '01', 'january' => '01',
            'feb' => '02', 'februari' => '02', 'february' => '02',
            'mar' => '03', 'maret' => '03', 'march' => '03',
            'apr' => '04', 'april' => '04',
            'mei' => '05', 'may' => '05',
            'jun' => '06', 'juni' => '06', 'june' => '06',
            'jul' => '07', 'juli' => '07', 'july' => '07',
            'agu' => '08', 'agustus' => '08', 'aug' => '08', 'august' => '08',
            'sep' => '09', 'sept' => '09', 'september' => '09',
            'okt' => '10', 'oktober' => '10', 'oct' => '10', 'october' => '10',
            'nov' => '11', 'november' => '11',
            'des' => '12', 'desember' => '12', 'dec' => '12', 'december' => '12',
        ];

        // Format: MonthName + 2 or 4 digit year: "Jan 25", "Sept 25", "Maret 2024"
        if (preg_match('/([a-z]+)\s*(\d{2,4})/i', $raw, $m)) {
            $mName = strtolower($m[1]);
            $year = $m[2];
            if (strlen($year) === 2) {
                $year = '20' . $year;
            }
            if (isset($monthMap[$mName])) {
                return "{$year}-{$monthMap[$mName]}";
            }
        }

        return null;
    }

    /**
     * Smart Retention Parser.
     * Murni bulan jika angka (misal: 60 -> 5 tahun, 12 -> 1 tahun).
     * Jika format teks lama (misal: "5 Tahun" -> 5 tahun, "12 Bulan" -> 1 tahun).
     */
    protected function parseRetentionYears(?string $raw, int $default = 5): int
    {
        if ($raw === null) {
            return $default;
        }
        $raw = trim(strtolower($raw));
        if (empty($raw)) {
            return $default;
        }

        // 1. Text Format Years: "5 Tahun", "10 Thn"
        if (preg_match('/(\d+)\s*(?:tahun|thn)/i', $raw, $m)) {
            return max(1, (int)$m[1]);
        }

        // 2. Text Format Months: "12 Bulan", "24 Bulan", "60 Bulan"
        if (preg_match('/(\d+)\s*bulan/i', $raw, $m)) {
            $months = (int)$m[1];
            return max(1, (int)round($months / 12));
        }

        // 3. Plain integer / numeric: Kolom MASA_SIMPAN_BULAN (pure hitungan bulan tanpa keterangan)
        // Contoh: 60 -> 5 tahun, 36 -> 3 tahun, 12 -> 1 tahun, 120 -> 10 tahun
        if (is_numeric($raw)) {
            $val = (float)$raw;
            if ($val >= 12) {
                return max(1, (int)round($val / 12));
            }
            // Jika di bawah 12 bulan (misal 6 bulan), retensi tahunan minimum arsip adalah 1 tahun
            return 1;
        }

        return $default;
    }

    /**
     * Build enhanced multi-sheet XLSX template.
     */
    protected function buildXlsxResponse(array $headers, array $rows, string $filename, ?User $user = null)
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
            '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
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
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets>' .
            '<sheet name="DATA_ARSIP" sheetId="1" r:id="rId1"/>' .
            '<sheet name="REFERENSI_KODE" sheetId="2" r:id="rId2"/>' .
            '</sheets>' .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $wb);

        // 5. xl/worksheets/sheet1.xml (DATA_ARSIP)
        // Hitung lebar kolom optimal agar header dan teks contoh tidak terpotong (sesuai standar tampilan rapi)
        $defaultWidths = [
            'NO_BOX_SEMENTARA' => 22,
            'JUDUL_BOX' => 48,
            'KODE_DEPARTEMEN' => 22,
            'KODE_SUB_DEPARTEMEN' => 26,
            'NAMA_DOKUMEN' => 46,
            'PERIODE_DOK_MULAI' => 24,
            'PERIODE_DOK_SELESAI' => 24,
            'MASA_SIMPAN_BULAN' => 22,
            'KONDISI_FISIK' => 36,
            'NO_BUTIR' => 14,
            'LOKASI_RUANG' => 18,
            'LOKASI_RAK' => 18,
            'NO_SLOT' => 14,
            'KETERANGAN_BUTIR' => 32,
        ];

        $colsXml = '<cols>';
        foreach ($headers as $cIdx => $hdr) {
            $colNum = $cIdx + 1;
            $maxLen = strlen((string)$hdr);
            foreach ($rows as $r) {
                if (isset($r[$cIdx])) {
                    $len = strlen((string)$r[$cIdx]);
                    if ($len > $maxLen) {
                        $maxLen = $len;
                    }
                }
            }
            $baseWidth = $defaultWidths[$hdr] ?? 20;
            $width = max($baseWidth, $maxLen + 4);
            $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            $colsXml .
            '<sheetData>';

        $allData = array_merge([$headers], $rows);
        $colLetters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P'];

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

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        // 6. xl/worksheets/sheet2.xml (REFERENSI_KODE) - Scoped by User Department
        $isPicDept = ($user && $user->isPicDept() && $user->department_id);
        $userDept = $isPicDept ? ($user->department ?: Department::find($user->department_id)) : null;

        if ($isPicDept && $userDept) {
            $departments = Department::where('id', $userDept->id)->get();
            $subDepts = SubDepartment::where('department_id', $userDept->id)->where('is_active', true)->with('department')->orderBy('code')->get();
            $locations = WarehouseLocation::where('is_active', true)
                ->where(function ($q) use ($userDept) {
                    $q->where('assigned_department_id', $userDept->id)
                      ->orWhereNull('assigned_department_id');
                })
                ->orderBy('rack_code')
                ->take(30)
                ->get();
        } else {
            $departments = Department::where('is_active', true)->orderBy('code')->get();
            $subDepts = SubDepartment::where('is_active', true)->with('department')->orderBy('code')->get();
            $locations = WarehouseLocation::where('is_active', true)->orderBy('rack_code')->take(30)->get();
        }

        $sheet2Cols = '<cols>' .
            '<col min="1" max="1" width="28" customWidth="1"/>' .
            '<col min="2" max="2" width="42" customWidth="1"/>' .
            '<col min="3" max="3" width="58" customWidth="1"/>' .
            '<col min="4" max="4" width="26" customWidth="1"/>' .
            '</cols>';

        $sheet2Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            $sheet2Cols .
            '<sheetData>';

        $r2 = 1;
        if ($isPicDept && $userDept) {
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>BUKU PANDUAN REFERENSI KODE RESMI - DEPARTEMEN ' . htmlspecialchars($userDept->name, ENT_XML1, 'UTF-8') . ' (' . htmlspecialchars($userDept->code, ENT_XML1, 'UTF-8') . ')</t></is></c></row>';
            $r2++;
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>Akun Anda memiliki hak akses PIC Departemen ' . htmlspecialchars($userDept->name, ENT_XML1, 'UTF-8') . '. Sesuai batasan otoritas akun, tabel di bawah HANYA menampilkan kode resmi departemen Anda agar terhindar dari salah pilih kode departemen lain.</t></is></c></row>';
        } else {
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>BUKU PANDUAN REFERENSI KODE RESMI &amp; MASTER DATA DMS INDRACO</t></is></c></row>';
            $r2++;
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>Gunakan kode-kode resmi di bawah ini saat mengisi sheet DATA_ARSIP agar proses impor berjalan lancar 100%.</t></is></c></row>';
        }
        $r2 += 2;

        // Sub-table: Departemen
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>[1. KODE DEPARTEMEN RESMI ANDA]</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '">' .
            '<c r="A' . $r2 . '" t="inlineStr"><is><t>KODE_DEPARTEMEN</t></is></c>' .
            '<c r="B' . $r2 . '" t="inlineStr"><is><t>NAMA_DEPARTEMEN</t></is></c>' .
            '<c r="C' . $r2 . '" t="inlineStr"><is><t>DEFAULT_RETENSI_TAHUN</t></is></c>' .
            '</row>';
        $r2++;

        foreach ($departments as $d) {
            $sheet2Xml .= '<row r="' . $r2 . '">' .
                '<c r="A' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($d->code, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '<c r="B' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($d->name, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '<c r="C' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars((string)($d->retention_years ?: 5), ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '</row>';
            $r2++;
        }

        $r2++;
        // Sub-table: Sub-Departemen
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>[2. DAFTAR KODE SUB-DEPARTEMEN RESMI]</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '">' .
            '<c r="A' . $r2 . '" t="inlineStr"><is><t>KODE_SUB_DEPARTEMEN</t></is></c>' .
            '<c r="B' . $r2 . '" t="inlineStr"><is><t>NAMA_SUB_DEPARTEMEN</t></is></c>' .
            '<c r="C' . $r2 . '" t="inlineStr"><is><t>DEPARTEMEN_INDUK</t></is></c>' .
            '</row>';
        $r2++;

        if ($subDepts->isEmpty()) {
            $sheet2Xml .= '<row r="' . $r2 . '">' .
                '<c r="A' . $r2 . '" t="inlineStr"><is><t>-(TIDAK ADA)-</t></is></c>' .
                '<c r="B' . $r2 . '" t="inlineStr"><is><t>Departemen ini tidak memiliki sub-departemen. Kolom KODE_SUB_DEPARTEMEN boleh dikosongkan.</t></is></c>' .
                '<c r="C' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($userDept ? $userDept->code : '-', ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '</row>';
            $r2++;
        } else {
            foreach ($subDepts as $sd) {
                $sheet2Xml .= '<row r="' . $r2 . '">' .
                    '<c r="A' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($sd->code, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '<c r="B' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($sd->name, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '<c r="C' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($sd->department->code ?? '', ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '</row>';
                $r2++;
            }
        }

        $r2++;
        if (!$isPicDept) {
            // Sub-table: Gudang & Rak (Hanya untuk Admin / Gudang)
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>[3. DAFTAR RUANG &amp; RAK GUDANG AKTIF (CONTOH)]</t></is></c></row>';
            $r2++;
            $sheet2Xml .= '<row r="' . $r2 . '">' .
                '<c r="A' . $r2 . '" t="inlineStr"><is><t>RUANG / SEKTOR</t></is></c>' .
                '<c r="B' . $r2 . '" t="inlineStr"><is><t>KODE_RAK</t></is></c>' .
                '<c r="C' . $r2 . '" t="inlineStr"><is><t>KETERANGAN_LOKASI</t></is></c>' .
                '</row>';
            $r2++;

            foreach ($locations as $loc) {
                $sheet2Xml .= '<row r="' . $r2 . '">' .
                    '<c r="A' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($loc->room_sector ?? 'GUDANG', ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '<c r="B' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($loc->clean_rack_code ?? $loc->rack_code, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '<c r="C' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($loc->full_location ?? $loc->rack_code, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '</row>';
                $r2++;
            }
            $r2++;
        }

        // Sub-table: Petunjuk Pengisian
        $guideHeader = $isPicDept ? '[3. PETUNJUK PENGISIAN PENGAJUAN ARSIP]' : '[4. PETUNJUK PENGISIAN AGAR MINIM ERROR]';
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>' . $guideHeader . '</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>1. Identitas Box (NO_BOX_SEMENTARA, JUDUL_BOX, DEPARTEMEN) cukup diisi 1 kali pada baris pertama box. Baris butir berikutnya dalam box yang sama cukup dikosongkan.</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>2. Kolom NO_BUTIR boleh dikosongkan jika ingin nomor urut dibuat otomatis (1, 2, 3...) oleh sistem.</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>3. Kolom MASA_SIMPAN_BULAN murni hitungan bulan (contoh: 60 untuk 60 bulan/5 tahun, 12 untuk 12 bulan/1 tahun).</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>4. Format Periode Mulai &amp; Selesai disarankan YYYY-MM (contoh: 2024-01) atau format bulan natural (contoh: Jan 25 s/d Des 25).</t></is></c></row>';
        $r2++;
        if ($isPicDept) {
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>5. Penentuan lokasi rak dan slot gudang fisik tidak perlu diisi oleh departemen. Alokasi rak akan diverifikasi dan diplot langsung oleh Tim Gudang saat proses persetujuan (approval).</t></is></c></row>';
        } else {
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>5. Kolom Lokasi (RUANG, RAK, SLOT) bersifat opsional. Diisi jika berkas merupakan arsip lama yang sudah tersusun di rak fisik gudang.</t></is></c></row>';
        }

        $sheet2Xml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);

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
