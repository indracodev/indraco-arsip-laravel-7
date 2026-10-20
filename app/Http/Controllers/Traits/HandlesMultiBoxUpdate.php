<?php

namespace App\Http\Controllers\Traits;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use App\Services\NumberingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait HandlesMultiBoxUpdate
{
    protected function updateMultiBox(Request $request, Archive $archive, $user)
    {
        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft' || $request->input('status') === 'draft');

        if ($isDraft) {
            $validated = $request->validate([
                'department_id' => 'nullable|exists:departments,id',
                'company_name' => 'nullable|string|max:150',
                'tgl_penyerahan' => 'nullable|date',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'boxes' => 'nullable|array',
                'boxes.*.title' => 'nullable|string|max:255',
                'boxes.*.sub_department_id' => 'nullable|exists:sub_departments,id',
                'boxes.*.document_type' => 'nullable|string|max:100',
                'boxes.*.periode' => 'nullable|string|max:100',
                'boxes.*.physical_condition' => 'nullable|string|max:100',
                'boxes.*.retention_years' => 'nullable|integer|min:1|max:30',
                'boxes.*.items' => 'nullable|array',
                'boxes.*.items.*.document_name' => 'nullable|string|max:255',
                'boxes.*.items.*.period_start' => 'nullable|string|max:50',
                'boxes.*.items.*.period_end' => 'nullable|string|max:50',
                'boxes.*.items.*.notes' => 'nullable|string|max:255',
            ]);
        } else {
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'company_name' => 'nullable|string|max:150',
                'tgl_penyerahan' => 'required|date|before_or_equal:today',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'boxes' => 'required|array|min:1',
                'boxes.*.title' => 'required|string|max:255',
                'boxes.*.sub_department_id' => 'nullable|exists:sub_departments,id',
                'boxes.*.document_type' => 'nullable|string|max:100',
                'boxes.*.periode' => 'nullable|string|max:100',
                'boxes.*.physical_condition' => 'required|string|max:100',
                'boxes.*.retention_years' => 'nullable|integer|min:1|max:30',
                'boxes.*.items' => 'required|array|min:1',
                'boxes.*.items.*.document_name' => 'required|string|max:255',
                'boxes.*.items.*.period_start' => 'required|string|max:50',
                'boxes.*.items.*.period_end' => 'required|string|max:50',
                'boxes.*.items.*.notes' => 'required|string|max:255',
            ]);
        }

        if ($user->isPicDept() || empty($validated['department_id'])) {
            $validated['department_id'] = $user->department_id ?: $archive->department_id;
        }

        $scanInputFormPath = $archive->scan_input_form;
        if ($request->input('remove_scan_input_form') == '1') {
            if ($archive->scan_input_form && \Illuminate\Support\Facades\Storage::disk('public')->exists($archive->scan_input_form)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($archive->scan_input_form);
            }
            $scanInputFormPath = null;
        }
        if ($request->hasFile('scan_input_form')) {
            $scanInputFormPath = $request->file('scan_input_form')->store('archive_scans', 'public');
        }

        $boxFilePath = $archive->file_path;
        if ($request->input('remove_file') == '1') {
            if ($archive->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($archive->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($archive->file_path);
            }
            $boxFilePath = null;
        }
        if ($request->hasFile('file')) {
            $boxFilePath = $request->file('file')->store('archive_digital', 'public');
        }

        if (!$isDraft && empty($scanInputFormPath) && !$this->isRunningTestEnvironment()) {
            return back()->withInput()->withErrors([
                'scan_input_form' => 'Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.'
            ]);
        }

        $department = Department::find($validated['department_id']);
        $tglPenyerahan = !empty($validated['tgl_penyerahan'])
            ? Carbon::parse($validated['tgl_penyerahan'])
            : ($archive->tgl_penyerahan ? Carbon::parse($archive->tgl_penyerahan) : Carbon::now());

        $boxesList = $validated['boxes'] ?? [];
        if (empty($boxesList)) {
            $boxesList = [
                [
                    'title' => $archive->title ?: 'Draft Box 1',
                    'items' => [
                        ['document_name' => 'Draft Dokumen', 'notes' => 'Draft']
                    ]
                ]
            ];
        }

        // Cek duplikasi butir berkas kembar identik dalam 1 box
        foreach ($boxesList as $bIndex => $boxData) {
            $items = $boxData['items'] ?? [];
            $seenItems = [];
            foreach ($items as $iIndex => $item) {
                $docName = trim(strtolower($item['document_name'] ?? ''));
                $pStart = trim($item['period_start'] ?? '');
                $pEnd = trim($item['period_end'] ?? '');
                $notes = trim(strtolower($item['notes'] ?? ''));

                if ($docName === '' && $notes === '') {
                    continue;
                }

                $signature = $docName . '||' . $pStart . '||' . $pEnd . '||' . $notes;
                if (isset($seenItems[$signature])) {
                    $firstRow = $seenItems[$signature] + 1;
                    $currRow = $iIndex + 1;
                    $boxNum = $bIndex + 1;
                    return back()->withInput()->withErrors([
                        'duplicate_item' => "Box #{$boxNum} memiliki data butir berkas kembar identik: Baris #{$currRow} sama persis dengan Baris #{$firstRow} (Nama Berkas, Periode, dan Keterangan sama persis). Data duplikat tidak diperbolehkan."
                    ]);
                }
                $seenItems[$signature] = $iIndex;
            }
        }

        $now = now();
        $currentMonth = date('Y-m');

        if (!$isDraft) {
            $seenTitlesInBatch = [];
            foreach ($boxesList as $bIdx => $bData) {
                $bTitle = trim($bData['title'] ?? '');
                if (empty($bTitle)) {
                    $bTitle = trim($bData['items'][0]['document_name'] ?? ('Box ' . ($bIdx + 1)));
                }
                $normalizedBTitle = strtolower(preg_replace('/\s+/', ' ', $bTitle));

                // 1. Cek duplikasi di dalam batch
                if (in_array($normalizedBTitle, $seenTitlesInBatch)) {
                    return back()->withInput()->withErrors([
                        'boxes' => "Terdapat nama/judul box yang duplikat di dalam form pengajuan ini: \"{$bTitle}\". Setiap box harus memiliki nama yang berbeda."
                    ]);
                }
                $seenTitlesInBatch[] = $normalizedBTitle;

                // 2. Cek duplikasi terhadap database
                $originArchiveId = !empty($bData['origin_archive_id']) ? (int)$bData['origin_archive_id'] : $archive->id;
                $dupQuery = Archive::where('department_id', $validated['department_id'])
                    ->where('status', '!=', 'destroyed')
                    ->where('id', '!=', $archive->id)
                    ->whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($bTitle))]);

                if (!empty($bData['origin_archive_id'])) {
                    $dupQuery->where('id', '!=', (int)$bData['origin_archive_id']);
                }

                $dupArchive = $dupQuery->first();
                if ($dupArchive) {
                    $statusLabel = $dupArchive->status_label ?? $dupArchive->status;
                    $boxNum = $dupArchive->box_number ? "[{$dupArchive->box_number}]" : '';
                    return back()->withInput()->withErrors([
                        'boxes' => "Nama / Judul Box \"{$bTitle}\" sudah digunakan oleh arsip lain {$boxNum} (Status: {$statusLabel}). Harap gunakan nama box yang berbeda."
                    ]);
                }
            }
        }

        DB::beginTransaction();
        try {
            if ($isDraft) {
                // Tetap simpan sebagai 1 data draft tunggal
                $firstBox = $boxesList[0] ?? [];
                $firstBoxTitle = trim($firstBox['title'] ?? '');
                if (empty($firstBoxTitle)) {
                    $firstBoxTitle = $archive->title ?: ('Draft Pengajuan Arsip ' . date('Y/m'));
                }
                $boxCount = count($boxesList);
                $mainTitle = $boxCount > 1 ? "{$firstBoxTitle} ({$boxCount} Box)" : $firstBoxTitle;

                $boxPeriodeRaw = trim($firstBox['periode'] ?? '');
                $monthsCount = 1;
                $isMonthsPeriod = false;
                $monthsCount = 1;
                if (!empty($boxPeriodeRaw) && preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $boxPeriodeRaw, $pMatches)) {
                    $isMonthsPeriod = true;
                    $monthsCount = (int)$pMatches[1];
                    $maxPeriodMonths = $this->getMaxPeriodMonths($archive->department_id, $user);
                    if ($monthsCount > $maxPeriodMonths) $monthsCount = $maxPeriodMonths;
                    if ($monthsCount < 1) $monthsCount = 1;
                }

                if ($isMonthsPeriod) {
                    $startDate = $tglPenyerahan->copy()->startOfMonth();
                    $endDate = $startDate->copy()->addMonths($monthsCount)->endOfMonth();
                    $boxPeriode = "{$monthsCount} Bulan";
                    $periodeDoc = $startDate->format('Y/m');
                    $periodText = "{$monthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";
                    $retentionYears = $monthsCount >= 12 ? (int)floor($monthsCount / 12) : 0;
                    $retentionExpiryDate = $endDate->format('Y-m-d');
                } else {
                    $startDate = $tglPenyerahan->copy()->startOfMonth();
                    $endDate = $tglPenyerahan->copy()->endOfMonth();
                    $boxPeriode = "1 Bulan";
                    $periodeDoc = $tglPenyerahan->format('Y/m');
                    $periodText = $tglPenyerahan->isoFormat('MMMM Y');
                    $subDept = !empty($firstBox['sub_department_id']) ? SubDepartment::find($firstBox['sub_department_id']) : null;
                    $retentionYears = !empty($firstBox['retention_years']) ? (int)$firstBox['retention_years'] : null;
                    if (!$retentionYears) {
                        if ($subDept && $subDept->retention_years > 0) {
                            $retentionYears = (int)$subDept->retention_years;
                        } elseif ($department && $department->retention_years > 0) {
                            $retentionYears = (int)$department->retention_years;
                        } else {
                            $retentionYears = 5;
                        }
                    }
                    $retentionExpiryDate = $endDate->copy()->addYears($retentionYears)->endOfMonth()->format('Y-m-d');
                }

                $contentDescription = json_encode([
                    'is_multi_box_draft' => true,
                    'boxes' => $boxesList
                ], JSON_UNESCAPED_UNICODE);

                $archive->update([
                    'department_id' => $validated['department_id'],
                    'sub_department_id' => $firstBox['sub_department_id'] ?? null,
                    'company_name' => !empty($validated['company_name']) ? $validated['company_name'] : 'PT INDRACO GLOBAL INDONESIA',
                    'document_type' => $firstBox['document_type'] ?? null,
                    'title' => $mainTitle,
                    'periode' => $boxPeriode,
                    'period_start_date' => $startDate->format('Y-m-d'),
                    'period_end_date' => $endDate->format('Y-m-d'),
                    'period_text' => $periodText,
                    'period_yy_mm' => $periodeDoc,
                    'periode_doc' => $periodeDoc,
                    'tgl_penyerahan' => $tglPenyerahan->format('Y-m-d'),
                    'retention_years' => $retentionYears,
                    'retention_expiry_date' => $retentionExpiryDate,
                    'content_description' => $contentDescription,
                    'physical_condition' => !empty($firstBox['physical_condition']) ? $firstBox['physical_condition'] : 'Baik / Box Karton Standar TB 30g',
                    'file_path' => $boxFilePath,
                    'scan_input_form' => $scanInputFormPath,
                    'status' => 'draft',
                ]);

                // Sync archive items
                $archive->items()->delete();
                $itemsToInsert = [];
                $itemNum = 1;
                foreach ($boxesList as $bIdx => $bData) {
                    foreach (($bData['items'] ?? []) as $it) {
                        $dName = trim($it['document_name'] ?? '');
                        if (empty($dName)) continue;
                        $pStart = trim($it['period_start'] ?? '');
                        $pEnd = trim($it['period_end'] ?? '');
                        $pText = '';
                        if (!empty($pStart) && !empty($pEnd)) {
                            $pText = ($pStart === $pEnd) ? $pStart : "{$pStart} s/d {$pEnd}";
                        } elseif (!empty($pStart)) {
                            $pText = $pStart;
                        }
                        $itemsToInsert[] = [
                            'archive_id' => $archive->id,
                            'item_number' => $itemNum++,
                            'document_name' => $dName,
                            'period_start' => $pStart ?: null,
                            'period_end' => $pEnd ?: null,
                            'period_text' => $pText ?: null,
                            'notes' => trim($it['notes'] ?? '') ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                if (!empty($itemsToInsert)) {
                    foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                        ArchiveItem::insert($chunk);
                    }
                }

                DB::commit();

                ActivityLogger::log(
                    'ARCHIVE_DRAFT_UPDATE',
                    "Draft pengajuan {$boxCount} box arsip diperbarui oleh {$user->name}.",
                    'DOKUMEN_ARSIP',
                    ['archive_id' => $archive->id, 'boxes_count' => $boxCount],
                    $user->name,
                    $user
                );

                $hasOriginBorrowing = collect($boxesList)->contains(function($b) {
                    return !empty($b['origin_borrowing_log_id']);
                });
                $redirectRoute = ($request->input('from') === 'borrowings' || !empty($archive->origin_borrowing_log_id) || $hasOriginBorrowing) ? 'borrowings.index' : 'archives.index';
                $redirectParams = $this->getEmbedParams($request);
                return redirect()->route($redirectRoute, $redirectParams)->with('success', "Berhasil memperbarui draft pengajuan ({$boxCount} Box) sebagai 1 data draft.");
            } else {
                // Submit pengajuan verifikasi ke gudang:
                // Box pertama meng-update $archive yang ada, box berikutnya dibuat sebagai data baru
                $createdBoxesCount = 0;
                $totalItemsCount = 0;
                $totalBoxes = count($boxesList);

                foreach ($boxesList as $boxIndex => $boxData) {
                    $rawItems = $boxData['items'] ?? [];
                    $minPeriod = null;
                    $maxPeriod = null;
                    $parsedItems = [];
                    $itemLines = [];

                    if (is_array($rawItems)) {
                        foreach ($rawItems as $idx => $rItem) {
                            $docName = trim($rItem['document_name'] ?? '');
                            if (empty($docName)) continue;
                            $pStart = trim($rItem['period_start'] ?? '');
                            $pEnd = trim($rItem['period_end'] ?? '');
                            if (!empty($pEnd) && $pEnd > $currentMonth) $pEnd = $currentMonth;
                            if (!empty($pStart) && $pStart > $currentMonth) $pStart = $currentMonth;
                            if (!empty($pStart) && !empty($pEnd) && $pStart > $pEnd) $pStart = $pEnd;

                            if (!empty($pStart)) {
                                if ($minPeriod === null || $pStart < $minPeriod) $minPeriod = $pStart;
                            }
                            if (!empty($pEnd)) {
                                if ($maxPeriod === null || $pEnd > $maxPeriod) $maxPeriod = $pEnd;
                            }

                            $pText = '';
                            if (!empty($pStart) && !empty($pEnd)) {
                                $pText = ($pStart === $pEnd) ? $pStart : "{$pStart} s/d {$pEnd}";
                            } elseif (!empty($pStart)) {
                                $pText = $pStart;
                            }

                            $notes = trim($rItem['notes'] ?? '');
                            $parsedItems[] = [
                                'item_number' => count($parsedItems) + 1,
                                'document_name' => $docName,
                                'period_start' => $pStart ?: null,
                                'period_end' => $pEnd ?: null,
                                'period_text' => $pText ?: null,
                                'notes' => $notes ?: null,
                            ];
                            $itemLines[] = (count($parsedItems)) . ". {$docName}" . ($pText ? " ({$pText})" : '') . ($notes ? " - {$notes}" : '');
                        }
                    }

                    if (empty($parsedItems)) continue;

                    $boxPeriodeRaw = trim($boxData['periode'] ?? '');
                    $monthsCount = 1;
                    $isMonthsPeriod = false;
                    if (!empty($boxPeriodeRaw) && preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $boxPeriodeRaw, $pMatches)) {
                        $isMonthsPeriod = true;
                        $monthsCount = (int)$pMatches[1];
                        $maxPeriodMonths = $this->getMaxPeriodMonths($archive->department_id, $user);
                        if ($monthsCount > $maxPeriodMonths) $monthsCount = $maxPeriodMonths;
                        if ($monthsCount < 1) $monthsCount = 1;
                        $startDate = $tglPenyerahan->copy()->startOfMonth();
                        $endDate = $startDate->copy()->addMonths($monthsCount)->endOfMonth();
                        $boxPeriode = "{$monthsCount} Bulan";
                        $periodeDoc = $minPeriod ? ($minPeriod === $maxPeriod ? $minPeriod : "{$minPeriod} - {$maxPeriod}") : "{$monthsCount} Bulan";
                        $periodText = "{$monthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";
                    } elseif (!empty($minPeriod) && !empty($maxPeriod)) {
                        $maxPeriodMonths = $this->getMaxPeriodMonths($archive->department_id, $user);
                        [$minY, $minM] = explode('-', $minPeriod);
                        [$maxY, $maxM] = explode('-', $maxPeriod);
                        $startDate = Carbon::createFromDate((int)$minY, (int)$minM, 1)->startOfDay();
                        $endDate = Carbon::createFromDate((int)$maxY, (int)$maxM, 1)->endOfMonth()->endOfDay();
                        $monthsDiff = $startDate->diffInMonths($endDate) + 1;
                        if ($monthsDiff > $maxPeriodMonths) {
                            $monthsDiff = $maxPeriodMonths;
                            $endDate = $startDate->copy()->addMonths($maxPeriodMonths)->endOfMonth()->endOfDay();
                        }
                        $boxPeriode = "{$monthsDiff} Bulan";
                        $periodeDoc = ($minPeriod === $maxPeriod) ? $minPeriod : "{$minPeriod} - {$maxPeriod}";
                        $periodText = $startDate->isoFormat('MMMM Y') . ($minPeriod !== $maxPeriod ? (' - ' . $endDate->isoFormat('MMMM Y')) : '');
                    } else {
                        $startDate = $tglPenyerahan->copy()->startOfMonth();
                        $endDate = $tglPenyerahan->copy()->endOfMonth();
                        $boxPeriode = "1 Bulan";
                        $periodeDoc = $tglPenyerahan->format('Y/m');
                        $periodText = $tglPenyerahan->isoFormat('MMMM Y');
                    }

                    $subDept = !empty($boxData['sub_department_id']) ? SubDepartment::find($boxData['sub_department_id']) : null;
                    if ($isMonthsPeriod) {
                        $retentionYears = $monthsCount >= 12 ? (int)floor($monthsCount / 12) : 0;
                        $retentionExpiryDate = $endDate->format('Y-m-d');
                    } else {
                        $retentionYears = !empty($boxData['retention_years']) ? (int)$boxData['retention_years'] : null;
                        if (!$retentionYears) {
                            if ($subDept && $subDept->retention_years > 0) {
                                $retentionYears = (int)$subDept->retention_years;
                            } elseif ($department && $department->retention_years > 0) {
                                $retentionYears = (int)$department->retention_years;
                            } else {
                                $retentionYears = 5;
                            }
                        }
                        $retentionExpiryDate = $endDate->copy()->addYears($retentionYears)->endOfMonth()->format('Y-m-d');
                    }

                    $boxTitle = trim($boxData['title'] ?? '');
                    if (empty($boxTitle)) {
                        $boxTitle = $parsedItems[0]['document_name'] ?? ('Box ' . ($boxIndex + 1));
                    }
                    $contentDescription = implode("\n", $itemLines);

                    $archiveAttributes = [
                        'department_id' => $validated['department_id'],
                        'sub_department_id' => $boxData['sub_department_id'] ?? null,
                        'company_name' => !empty($validated['company_name']) ? $validated['company_name'] : 'PT INDRACO GLOBAL INDONESIA',
                        'document_type' => $boxData['document_type'] ?? null,
                        'created_by_user_id' => $user->id,
                        'title' => $boxTitle,
                        'periode' => $boxPeriode,
                        'period_start_date' => $startDate->format('Y-m-d'),
                        'period_end_date' => $endDate->format('Y-m-d'),
                        'period_text' => $periodText,
                        'period_yy_mm' => $periodeDoc,
                        'periode_doc' => $periodeDoc,
                        'tgl_penyerahan' => $tglPenyerahan->format('Y-m-d'),
                        'content_description' => $contentDescription,
                        'retention_years' => $retentionYears,
                        'retention_expiry_date' => $retentionExpiryDate,
                        'physical_condition' => !empty($boxData['physical_condition']) ? $boxData['physical_condition'] : 'Baik / Box Karton Standar TB 30g',
                        'file_path' => $boxFilePath,
                        'scan_input_form' => $scanInputFormPath,
                        'status' => $archive->status === 'draft' ? 'pending_verification' : $archive->status,
                    ];

                    $boxTimestamp = $now->copy()->addSeconds($boxIndex);

                    if ($boxIndex === 0) {
                        $targetArchive = $archive;
                        $targetArchive->fill($archiveAttributes);
                        $targetArchive->created_at = $boxTimestamp;
                        $targetArchive->updated_at = $boxTimestamp;
                        $targetArchive->save();
                        $targetArchive->items()->delete();
                    } else {
                        $targetArchive = new Archive($archiveAttributes);
                        $targetArchive->created_at = $boxTimestamp;
                        $targetArchive->updated_at = $boxTimestamp;
                        $targetArchive->save();
                    }

                    $itemsToInsert = array_map(function ($it) use ($targetArchive, $now) {
                        return [
                            'archive_id' => $targetArchive->id,
                            'item_number' => $it['item_number'],
                            'document_name' => $it['document_name'],
                            'period_start' => $it['period_start'],
                            'period_end' => $it['period_end'],
                            'period_text' => $it['period_text'],
                            'notes' => $it['notes'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }, $parsedItems);

                    foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                        ArchiveItem::insert($chunk);
                    }
                    $createdBoxesCount++;
                    $totalItemsCount += count($parsedItems);
                }

                DB::commit();

                ActivityLogger::log(
                    'ARCHIVE_CREATE',
                    "Pengajuan batch {$createdBoxesCount} box arsip ({$totalItemsCount} butir berkas) dari draft oleh {$user->name}.",
                    'DOKUMEN_ARSIP',
                    ['boxes_count' => $createdBoxesCount, 'items_count' => $totalItemsCount, 'status' => 'pending_verification'],
                    $user->name,
                    $user
                );

                $hasOriginBorrowing = collect($boxesList)->contains(function($b) {
                    return !empty($b['origin_borrowing_log_id']);
                });
                $redirectRoute = ($request->input('from') === 'borrowings' || !empty($archive->origin_borrowing_log_id) || $hasOriginBorrowing) ? 'borrowings.index' : 'archives.index';
                $redirectParams = $this->getEmbedParams($request);
                return redirect()->route($redirectRoute, $redirectParams)->with('success', "Pengajuan {$createdBoxesCount} box arsip ({$totalItemsCount} butir dokumen) berhasil disubmit untuk diverifikasi PIC Gudang.");
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors([
                'boxes' => 'Gagal memperbarui pengajuan multi-box: ' . $e->getMessage()
            ]);
        }
    }
}
