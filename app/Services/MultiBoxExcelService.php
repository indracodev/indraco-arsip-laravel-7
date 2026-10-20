<?php

namespace App\Services;

use App\Services\Traits\NormalizesExcelArchiveData;
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
    use NormalizesExcelArchiveData;

    /**
     * Generate template XLSX or CSV with sample multi-box data and reference guide.
     */

    public function generateTemplate(string $format = 'xlsx', ?User $user = null)
    {
        return (new MultiBoxTemplateService())->generateTemplate($format, $user);
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
}


