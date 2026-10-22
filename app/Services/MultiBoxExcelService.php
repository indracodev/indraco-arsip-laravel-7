<?php

namespace App\Services;

use App\Services\Traits\NormalizesExcelArchiveData;
use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\MasterArchive;
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

        // Cache Master Departments, Sub-Departments & Master Archives
        $departments = Department::where('is_active', true)->get();
        $subDepts = SubDepartment::where('is_active', true)->get();
        $masterArchives = MasterArchive::where('is_active', true)->get();

        $userDeptId = ($user && $user->department_id) ? $user->department_id : null;
        $isPicDept = ($user && $user->isPicDept());

        $grouped = [];
        $errors = [];
        $warnings = [];

        $dataRows = array_slice($rows, $dataStartIdx);
        $rowNum = $dataStartIdx; // 0-indexed row offset

        $currentBoxId = null;
        $boxSequence = 0;
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

            // Extract box ID or detect new box from JUDUL_BOX
            $rawBoxId = $getVal($row, 'box');
            $rawBoxTitle = $getVal($row, 'title');
            $isNewBox = false;

            if (!empty($rawBoxId)) {
                if ($currentBoxId !== null && $rawBoxId !== $currentBoxId) {
                    $isNewBox = true;
                }
                $currentBoxId = $rawBoxId;
            } elseif (!empty($rawBoxTitle)) {
                if ($currentTitle !== null && $rawBoxTitle !== $currentTitle) {
                    $isNewBox = true;
                }
                if ($currentBoxId === null || $isNewBox) {
                    $boxSequence++;
                    $currentBoxId = 'BOX-' . str_pad($boxSequence, 2, '0', STR_PAD_LEFT);
                }
            }

            if ($isNewBox) {
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

            if (empty($currentBoxId)) {
                $boxSequence++;
                $currentBoxId = 'BOX-' . str_pad($boxSequence, 2, '0', STR_PAD_LEFT);
            }

            if (!empty($rawBoxTitle)) {
                $currentTitle = $rawBoxTitle;
            }

            if (!empty($rawDept = $getVal($row, 'dept'))) $currentDept = $rawDept;
            if (!empty($rawSubDept = $getVal($row, 'sub_dept'))) $currentSubDept = $rawSubDept;
            if (!empty($rawPeriodBox = $getVal($row, 'period_box'))) $currentPeriodBox = $rawPeriodBox;
            if (!empty($rawRetention = $getVal($row, 'retention'))) $currentRetention = $rawRetention;
            if (!empty($rawCondition = $getVal($row, 'condition'))) $currentCondition = $rawCondition;
            if (!empty($rawRoom = $getVal($row, 'room'))) $currentRoom = $rawRoom;
            if (!empty($rawRack = $getVal($row, 'rack'))) $currentRack = $rawRack;
            if (!empty($rawSlot = $getVal($row, 'slot'))) $currentSlot = $rawSlot;

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

            // Resolve Department (Strict Priority: Exact Code -> Exact Name -> Partial only if >= 4 chars)
            $effectiveDept = null;
            if (!empty($currentDept)) {
                $cleanDept = strtoupper(trim($currentDept));
                $effectiveDept = $departments->first(fn($d) => strtoupper(trim($d->code)) === $cleanDept)
                    ?: $departments->first(fn($d) => strcasecmp(trim($d->name), $cleanDept) === 0);
                if (!$effectiveDept && strlen($cleanDept) >= 4) {
                    $effectiveDept = $departments->first(fn($d) => stripos($d->name, $cleanDept) !== false);
                }
            } elseif ($userDeptId) {
                $effectiveDept = $departments->firstWhere('id', $userDeptId);
            }

            // Resolve Sub-Department (Strict Priority: Exact Code in Dept -> Exact Name in Dept -> Cross Dept Exact -> Partial only if >= 4 chars)
            $effectiveSubDept = null;
            if (!empty($currentSubDept)) {
                $cleanSub = strtoupper(trim($currentSubDept));

                $matchSd = fn($filter) => $subDepts->first(fn($sd) => (!$effectiveDept || $sd->department_id === $effectiveDept->id) && $filter($sd));
                $effectiveSubDept = $matchSd(fn($sd) => strtoupper(trim($sd->code)) === $cleanSub)
                    ?: $matchSd(fn($sd) => strcasecmp(trim($sd->name), $cleanSub) === 0)
                    ?: (strlen($cleanSub) >= 4 ? $matchSd(fn($sd) => stripos($sd->name, $cleanSub) !== false) : null);

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

            // Smart Match MasterArchive (Toleransi Typo / Case / Simbol / Urutan Kata / Kurang / Lebih Kata)
            $matchedMaster = $this->matchMasterArchive($docName, $effectiveDept, $masterArchives);
            $finalDocName = $matchedMaster ? $matchedMaster->name : $docName;
            if ($matchedMaster && !empty($matchedMaster->document_type) && empty($grouped[$currentBoxId]['document_type'])) {
                $grouped[$currentBoxId]['document_type'] = $matchedMaster->document_type;
            }

            $currentItemsCount = count($grouped[$currentBoxId]['items']) + 1;
            $finalItemNum = (!empty($itemNum) && is_numeric($itemNum)) ? (int)$itemNum : $currentItemsCount;
            $periodText = ($periodeMulai === $periodeSelesai) ? $periodeMulai : "{$periodeMulai} s/d {$periodeSelesai}";

            $grouped[$currentBoxId]['items'][] = [
                'item_number' => $finalItemNum,
                'document_name' => $finalDocName,
                'name' => $finalDocName,
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

            // Resolve document type from first item or master archive
            $firstDocName = $boxData['items'][0]['document_name'] ?? '';
            $matchedMaster = $this->matchMasterArchive($firstDocName, $dept, $masterArchives);
            $resolvedDocType = $matchedMaster ? $matchedMaster->document_type : ($boxData['document_type'] ?? '');

            $finalBoxes[] = [
                'temp_box_id' => $tempBoxId,
                'title' => $boxData['title'],
                'name' => $boxData['title'],
                'department_id' => $dept->id,
                'department_code' => $dept->code,
                'department_name' => $dept->name,
                'sub_department_id' => $boxData['sub_department'] ? $boxData['sub_department']->id : null,
                'sub_department_code' => $boxData['sub_department'] ? $boxData['sub_department']->code : null,
                'document_type' => $resolvedDocType,
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

                if (!empty($box['location_room']) || !empty($box['location_rack']) || !empty($box['location_slot'])) {
                    $room = trim($box['location_room'] ?? '');
                    $rack = trim($box['location_rack'] ?? '');
                    $slotNum = trim($box['location_slot'] ?? '');
                    $cleanRoom = preg_replace('/^(?:GUDANG\s+)?/i', '', $room);
                    $cleanRack = preg_replace('/^(?:RAK-)?(?:R\d+-)?/i', '', $rack);

                    // 1. Direct match by slot_code (e.g. AS55, BW2)
                    $slotObj = null;
                    if (!empty($slotNum)) {
                        $slotObj = WarehouseRackSlot::where('slot_code', strtoupper($slotNum))->first();
                    }
                    if (!$slotObj && !empty($rack)) {
                        $slotObj = WarehouseRackSlot::where('slot_code', strtoupper($rack))->first();
                    }

                    if ($slotObj) {
                        $slotId = $slotObj->id;
                        $loc = $slotObj->location;
                        $locationId = $slotObj->warehouse_location_id;
                    } else {
                        // 2. Match physical rack first (location_type = 'rack')
                        $rooms = array_filter(array_unique([$room, $cleanRoom]));
                        $loc = WarehouseLocation::where('location_type', 'rack')->where(function ($q) use ($rack, $rooms, $cleanRoom, $cleanRack) {
                            if (!empty($rooms) && $cleanRack) {
                                $q->whereIn('room_sector', $rooms)->where(function ($sq) use ($rack, $cleanRack, $cleanRoom) {
                                    $sq->where('rack_code', $rack)->orWhere('rack_code', "{$cleanRoom}-{$cleanRack}")->orWhere('rack_code', 'LIKE', "%-{$cleanRack}")->orWhere('rack_code', 'LIKE', "%{$cleanRack}%");
                                });
                            } elseif ($cleanRack) {
                                $q->where('rack_code', $rack)->orWhere('rack_code', 'LIKE', "%-{$cleanRack}")->orWhere('rack_code', 'LIKE', "%{$cleanRack}%");
                            } elseif (!empty($rooms)) {
                                $q->whereIn('room_sector', $rooms);
                            }
                        })->first();

                        // Fallback to room only if no physical rack matched
                        if (!$loc && $cleanRoom) {
                            $loc = WarehouseLocation::where('room_sector', $cleanRoom)->orWhere('rack_code', 'LIKE', "%{$cleanRoom}%")->first();
                        }

                        if ($loc) {
                            $locationId = $loc->id;
                            if (!empty($slotNum)) {
                                $num = preg_replace('/\D/', '', $slotNum);
                                $slotObj = WarehouseRackSlot::where('warehouse_location_id', $loc->id)
                                    ->where(function ($sq) use ($slotNum, $num) {
                                        $sq->where('slot_code', strtoupper($slotNum));
                                        if ($num !== '') $sq->orWhere('slot_number', (int)$num);
                                    })->first();
                                if ($slotObj) $slotId = $slotObj->id;
                            }
                        }
                    }

                    if ($slotObj && $status === 'in_warehouse') {
                        $slotObj->update(['status' => 'filled']);
                    }
                    if ($loc && $status === 'in_warehouse') {
                        $loc->increment('current_box_count');
                    }
                }

                // 3. Create Archive
                $archive = Archive::create([
                    'department_id' => $box['department_id'],
                    'sub_department_id' => $box['sub_department_id'] ?? null,
                    'company_name' => 'PT INDRACO GLOBAL INDONESIA',
                    'document_type' => !empty($box['document_type']) ? $box['document_type'] : null,
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

                // If saved directly to warehouse (or if Superadmin imported with resolved warehouse location)
                if ($status === 'in_warehouse') {
                    if (empty($archive->box_number)) {
                        $numberingService = new \App\Services\NumberingService();
                        $archive->box_number = $numberingService->generateBoxCode($archive);
                        $archive->save();
                    }

                    if ($locationId) {
                        \App\Models\WarehouseEntryLog::create([
                            'archive_id' => $archive->id,
                            'pic_gudang_id' => $user->id,
                            'location_id' => $locationId,
                            'entry_date' => now(),
                            'notes' => 'Penerimaan fisik berkas langsung ke gudang via impor spreadsheet.',
                        ]);
                    }
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
            'doc_name' => ['NAMA_DOKUMEN', 'NAMA_BERKAS', 'BERKAS', 'LIST_DOKUMEN', 'LIST DOKUMEN', 'DOKUMEN', 'JUDUL_DOKUMEN', 'NAMA DOKUMEN', 'ITEM_DOKUMEN'],
            'item_num' => ['NO_BUTIR', 'NO_URUT', 'NO.', 'NO', 'NOMOR', 'ITEM_NO', 'NO_BERKAS'],
            'period_combined' => ['PERIODE_DOKUMEN', 'PERIODE DOKUMEN', 'PERIODE', 'PERIODE_BOX', 'PERIODE DOK'],
            'period_start' => ['PERIODE_DOK_MULAI', 'PERIODE_MULAI', 'MULAI', 'START_DATE'],
            'period_end' => ['PERIODE_DOK_SELESAI', 'PERIODE_SELESAI', 'SELESAI', 'END_DATE'],
            'retention' => ['MASA_SIMPAN_BULAN', 'MASA_SIMPAN', 'RETENSI_BULAN', 'MASA SIMPAN BULAN', 'MASA_SIMPAN_TAHUN', 'MASA SIMPAN', 'RETENSI', 'RETENTION'],
            'condition' => ['KONDISI_FISIK', 'KONDISI', 'KONDISI FISIK', 'PHYSICAL_CONDITION'],
            'room' => ['RUANG', 'GUDANG', 'LOKASI_RUANG', 'ROOM'],
            'rack' => ['RAK', 'LOKASI_RAK', 'RACK'],
            'slot' => ['NO_SLOT', 'SLOT', 'NOMOR_SLOT'],
            'notes' => ['KETERANGAN_DOKUMEN', 'KETERANGAN_BERKAS', 'KETERANGAN_BUTIR', 'KETERANGAN', 'CATATAN', 'NOTES', 'KET'],
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
        if ($zip->open($filePath) !== true) return [];

        $sharedStrings = [];
        if ($ssContent = $zip->getFromName('xl/sharedStrings.xml')) {
            $ssXml = @simplexml_load_string($ssContent);
            if ($ssXml && isset($ssXml->si)) {
                foreach ($ssXml->si as $si) {
                    $val = (string)($si->t ?? '');
                    if (!$val && isset($si->r)) {
                        foreach ($si->r as $r) $val .= (string)$r->t;
                    }
                    $sharedStrings[] = $val;
                }
            }
        }

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
        if ($sheetContent && ($sheetXml = @simplexml_load_string($sheetContent)) && isset($sheetXml->sheetData->row)) {
            foreach ($sheetXml->sheetData->row as $row) {
                $rowCells = [];
                foreach ($row->c as $c) {
                    $attr = $c->attributes();
                    $colIdx = $this->colLetterToIndex(isset($attr['r']) ? (string)$attr['r'] : '');
                    $t = isset($attr['t']) ? (string)$attr['t'] : '';
                    $val = ($t === 's') ? ($sharedStrings[(int)$c->v] ?? '') : (($t === 'inlineStr') ? (string)$c->is->t : (string)$c->v);
                    $rowCells[$colIdx] = trim($val);
                }
                if (!empty($rowCells)) {
                    $maxCol = max(array_keys($rowCells));
                    $denseRow = [];
                    for ($c = 0; $c <= $maxCol; $c++) $denseRow[] = $rowCells[$c] ?? '';
                    $rows[] = $denseRow;
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
        if (!($handle = fopen($filePath, 'r'))) return [];
        $firstLine = fgets($handle); rewind($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ((substr_count($firstLine, "\t") > substr_count($firstLine, ',')) ? "\t" : ',');
        $rows = []; $isFirst = true;
        while (($data = fgetcsv($handle, 10000, $delimiter)) !== false) {
            if ($isFirst && !empty($data)) { $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]); $isFirst = false; }
            $rows[] = array_map('trim', $data);
        }
        fclose($handle);
        return $rows;
    }

    /**
     * Smart & Fuzzy Match MasterArchive: toleran typo huruf, urutan kata terbalik,
     * kekurangan/kelebihan kata, root word/prefix (misal: "plast" -> "Plastik & Paper Cup").
     */
    protected function matchMasterArchive(string $rawDocName, $dept, $masterArchives): ?MasterArchive
    {
        $clean = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]/', '', $rawDocName)));
        if ($clean === '' || strlen($clean) < 3) return null;

        $pool = ($dept && $masterArchives->where('department_id', $dept->id)->isNotEmpty())
            ? $masterArchives->where('department_id', $dept->id)
            : $masterArchives;

        // 1. Exact match (case-insensitive & alphanumeric)
        foreach ($pool as $m) {
            if (strcasecmp(trim($m->name), trim($rawDocName)) === 0) return $m;
            if (strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]/', '', $m->name))) === $clean) return $m;
        }

        // 2. Token-set (salah urutan kata / anagram kata)
        $docWords = array_values(array_filter(preg_split('/[^a-zA-Z0-9]+/', strtolower($rawDocName))));
        $s1 = $docWords; sort($s1);
        foreach ($pool as $m) {
            $mWords = array_values(array_filter(preg_split('/[^a-zA-Z0-9]+/', strtolower($m->name))));
            $s2 = $mWords; sort($s2);
            if (!empty($s1) && $s1 === $s2) return $m;
        }

        // 3. Word-level Prefix & Fuzzy Similarity (misal "plast" -> "plastik")
        $bestMatch = null;
        $highestScore = 0;
        foreach ($pool as $m) {
            $mWords = array_values(array_filter(preg_split('/[^a-zA-Z0-9]+/', strtolower($m->name))));
            $mClean = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]/', '', $m->name)));
            if (empty($mWords) || empty($mClean)) continue;

            $matchedCount = 0;
            foreach ($docWords as $dw) {
                foreach ($mWords as $mw) {
                    if ($dw === $mw) { $matchedCount += 1.0; break; }
                    if ((strlen($dw) >= 4 && strpos($mw, $dw) === 0) || (strlen($mw) >= 4 && strpos($dw, $mw) === 0)) {
                        $matchedCount += 0.95; break;
                    }
                    similar_text($dw, $mw, $wSim);
                    if ($wSim >= 75 && (strlen($dw) >= 4 || strlen($mw) >= 4)) {
                        $matchedCount += ($wSim / 100); break;
                    }
                }
            }
            $wordScore = (count($docWords) > 0) ? ($matchedCount / count($docWords)) * 85 : 0;
            // substrScore: hanya ketika INPUT adalah singkatan/prefix dari MASTER (bukan sebaliknya)
            $substrScore = (strlen($clean) >= 4 && strpos($mClean, $clean) !== false)
                ? ((strpos($mClean, $clean) === 0) ? 88 : 78) : 0;

            similar_text($clean, $mClean, $sim);
            // Penalize similar_text ketika panjang string sangat berbeda (mencegah "Form Utilitas" ≈ "Utilitas")
            $lenRatio = min(strlen($clean), strlen($mClean)) / max(strlen($clean), strlen($mClean));
            if ($lenRatio < 0.7) $sim *= $lenRatio;
            $composite = max($wordScore, $substrScore, $sim);
            if ($composite > $highestScore) {
                $highestScore = $composite;
                $bestMatch = $m;
            }
        }

        return ($highestScore >= 68) ? $bestMatch : null;
    }
}
