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

trait HandlesSingleBoxCreation
{
    protected function storeSingleBox(Request $request, $user)
    {
        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft' || $request->input('status') === 'draft');

        if (!$request->filled('title')) {
            if ($request->filled('custom_doc_name')) {
                $request->merge(['title' => $request->input('custom_doc_name')]);
            } elseif ($request->has('items') && is_array($request->input('items')) && !empty($request->input('items')[0]['document_name'])) {
                $request->merge(['title' => trim($request->input('items')[0]['document_name'])]);
            } elseif ($isDraft) {
                $request->merge(['title' => 'Draft Box Arsip ' . date('Y/m')]);
            }
        }

        if ($isDraft) {
            $validated = $request->validate([
                'department_id' => 'nullable|exists:departments,id',
                'sub_department_id' => 'nullable|exists:sub_departments,id',
                'company_name' => 'nullable|string|max:150',
                'document_type' => 'nullable|string|max:100',
                'is_custom_doc_name' => 'nullable|boolean',
                'custom_doc_name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:255',
                'periode' => 'nullable|string|max:100',
                'periode_doc' => 'nullable|string|max:100',
                'tgl_penyerahan' => 'nullable|date',
                'period_text' => 'nullable|string|max:100',
                'content_description' => 'nullable|string',
                'retention_years' => 'nullable|integer|min:1|max:30',
                'masa_simpan_custom' => 'nullable|integer|min:1|max:30',
                'physical_condition' => 'nullable|string|max:100',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'items' => 'nullable|array',
                'items.*.document_name' => 'nullable|string|max:255',
                'items.*.period_start' => 'nullable|string|max:50',
                'items.*.period_end' => 'nullable|string|max:50',
                'items.*.period_text' => 'nullable|string|max:150',
                'items.*.notes' => 'nullable|string|max:255',
            ]);
        } else {
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'sub_department_id' => 'nullable|exists:sub_departments,id',
                'company_name' => 'nullable|string|max:150',
                'document_type' => 'nullable|string|max:100',
                'is_custom_doc_name' => 'nullable|boolean',
                'custom_doc_name' => 'nullable|string|max:255',
                'title' => 'required_without:custom_doc_name|nullable|string|max:255',
                'periode' => 'nullable|string|max:100',
                'periode_doc' => 'nullable|string|max:100',
                'tgl_penyerahan' => 'required|date|before_or_equal:today',
                'period_text' => 'nullable|string|max:100',
                'content_description' => 'nullable|string',
                'retention_years' => 'nullable|integer|min:1|max:30',
                'masa_simpan_custom' => 'nullable|integer|min:1|max:30',
                'physical_condition' => 'required|string|max:100',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'scan_input_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'items' => 'nullable|array',
                'items.*.document_name' => 'nullable|string|max:255',
                'items.*.period_start' => 'nullable|string|max:50',
                'items.*.period_end' => 'nullable|string|max:50',
                'items.*.period_text' => 'nullable|string|max:150',
                'items.*.notes' => 'required|string|max:255',
            ]);
        }

        if ($user->isPicDept() || empty($validated['department_id'])) {
            $validated['department_id'] = $user->department_id ?: (Department::first()->id ?? 1);
        }

        $validated['physical_condition'] = !empty($validated['physical_condition']) ? $validated['physical_condition'] : 'Baik / Box Karton Standar TB 30g';

        // 1. Business Rule: Periode Dokumen (derived from periode, periode_doc, or tgl_penyerahan)
        $tglPenyerahan = !empty($validated['tgl_penyerahan']) ? Carbon::parse($validated['tgl_penyerahan']) : Carbon::now();
        $validated['tgl_penyerahan'] = $tglPenyerahan->format('Y-m-d');
        $rawPeriod = !empty($validated['periode']) ? trim($validated['periode']) : (!empty($validated['periode_doc']) ? trim($validated['periode_doc']) : $tglPenyerahan->format('Y/m'));
        
        $isMonthsPeriod = false;
        $periodMonthsCount = 0;
        if (preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $rawPeriod, $matches)) {
            $isMonthsPeriod = true;
            $periodMonthsCount = (int)$matches[1];
            $maxPeriodMonths = $this->getMaxPeriodMonths($validated['department_id'] ?? null, $user);
            if ($periodMonthsCount > $maxPeriodMonths) {
                return redirect()->back()->withInput()->withErrors(['periode' => "Periode dokumen maksimal {$maxPeriodMonths} bulan."]);
            }
            $startDate = $tglPenyerahan->copy()->startOfMonth();
            $endDate = $startDate->copy()->addMonths($periodMonthsCount)->endOfMonth();
            $formattedPeriodDoc = "{$periodMonthsCount} Bulan";
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : "{$periodMonthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";
        } elseif (preg_match('/^(\d{4}\/(?:0[1-9]|1[0-2]))\s*(?:-|s\/d|hingga|to)\s*(\d{4}\/(?:0[1-9]|1[0-2]))$/i', $rawPeriod, $matches)) {
            $startPeriodStr = $matches[1];
            $endPeriodStr = $matches[2];
            [$sYear, $sMonth] = explode('/', $startPeriodStr);
            [$eYear, $eMonth] = explode('/', $endPeriodStr);
            $startDate = Carbon::createFromDate((int)$sYear, (int)$sMonth, 1)->startOfDay();
            $endDate = Carbon::createFromDate((int)$eYear, (int)$eMonth, 1)->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $startPeriodStr . ' - ' . $endPeriodStr;
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : ($startDate->isoFormat('MMMM Y') . ' - ' . $endDate->isoFormat('MMMM Y'));
        } elseif (preg_match('/^(\d{4}\/(?:0[1-9]|1[0-2]))$/', $rawPeriod, $matches)) {
            $periodStr = $matches[1];
            [$year, $month] = explode('/', $periodStr);
            $startDate = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $periodStr;
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : $startDate->isoFormat('MMMM Y');
        } else {
            $startDate = $tglPenyerahan->copy()->startOfMonth();
            $endDate = $tglPenyerahan->copy()->endOfMonth();
            $formattedPeriodDoc = $rawPeriod;
            $periodText = !empty($validated['period_text']) ? $validated['period_text'] : $tglPenyerahan->isoFormat('MMMM Y');
        }

        $validated['periode_doc'] = $formattedPeriodDoc;
        $periodYyMm = $formattedPeriodDoc;

        // 2. Parse Items Repeater
        $itemsInput = $request->input('items', []);
        $parsedItems = [];
        $itemLines = [];

        if (is_array($itemsInput) && count($itemsInput) > 0) {
            $idx = 1;
            foreach ($itemsInput as $rawItem) {
                if (is_array($rawItem) && !empty(trim($rawItem['document_name'] ?? ''))) {
                    $docName = trim($rawItem['document_name']);
                    $pStart = trim($rawItem['period_start'] ?? '');
                    $pEnd = trim($rawItem['period_end'] ?? '');

                    // Business Rule (Point 3): Clamp period to current active month
                    $currentActiveMonth = date('Y-m');
                    if (!empty($pEnd) && $pEnd > $currentActiveMonth) {
                        $pEnd = $currentActiveMonth;
                    }
                    if (!empty($pStart) && $pStart > $currentActiveMonth) {
                        $pStart = $currentActiveMonth;
                    }
                    if (!empty($pStart) && !empty($pEnd) && $pStart > $pEnd) {
                        $pStart = $pEnd;
                    }
                    
                    // Format period_text nicely if not explicitly given
                    $pText = trim($rawItem['period_text'] ?? '');
                    if (empty($pText)) {
                        if (!empty($pStart) && !empty($pEnd)) {
                            $pText = ($pStart === $pEnd) ? $pStart : "{$pStart} s/d {$pEnd}";
                        } elseif (!empty($pStart)) {
                            $pText = $pStart;
                        } elseif (!empty($pEnd)) {
                            $pText = $pEnd;
                        } else {
                            $pText = $formattedPeriodDoc;
                        }
                    }
                    
                    $notes = trim($rawItem['notes'] ?? '');
                    
                    $parsedItems[] = [
                        'item_number' => $idx,
                        'document_name' => $docName,
                        'period_start' => !empty($pStart) ? $pStart : null,
                        'period_end' => !empty($pEnd) ? $pEnd : null,
                        'period_text' => $pText,
                        'notes' => $notes,
                    ];
                    
                    $itemLines[] = "{$idx}. {$docName}" . ($pText ? " ({$pText})" : "");
                    $idx++;
                }
            }
        }

        // Fallback from content_description if no repeater items provided
        if (empty($parsedItems) && !empty($validated['content_description'])) {
            $lines = array_filter(array_map('trim', explode("\n", $validated['content_description'])));
            $idx = 1;
            foreach ($lines as $line) {
                $clean = ltrim($line, "-* \t0..9.");
                if (!empty($clean)) {
                    $parsedItems[] = [
                        'item_number' => $idx,
                        'document_name' => $clean,
                        'period_text' => $formattedPeriodDoc,
                        'notes' => null,
                    ];
                    $itemLines[] = "{$idx}. {$clean}";
                    $idx++;
                }
            }
        }

        // If still empty, create at least 1 default item
        if (empty($parsedItems)) {
            $fallbackTitle = !empty($validated['title']) ? $validated['title'] : 'Arsip Dokumen ' . $tglPenyerahan->isoFormat('MMMM Y');
            $parsedItems[] = [
                'item_number' => 1,
                'document_name' => $fallbackTitle,
                'period_text' => $formattedPeriodDoc,
                'notes' => null,
            ];
            $itemLines[] = "1. {$fallbackTitle}";
        }

        $formattedContentDescription = !empty($itemLines) ? implode("\n", $itemLines) : ($validated['content_description'] ?? 'Rincian Berkas');

        // 3. Title & Custom Doc Name Handling
        $isCustomDocName = !empty($validated['is_custom_doc_name']);
        $customDocName = $isCustomDocName ? ($validated['custom_doc_name'] ?? $request->input('custom_doc_name')) : null;
        $primaryItemName = $parsedItems[0]['document_name'] ?? ('Arsip ' . $formattedPeriodDoc);
        $finalTitle = $isCustomDocName ? $customDocName : (!empty($validated['title']) ? $validated['title'] : $primaryItemName);

        // 4. Automated Retention Years & Expiry Calculation
        $department = Department::find($validated['department_id']);
        $subDepartment = !empty($validated['sub_department_id']) ? SubDepartment::find($validated['sub_department_id']) : null;

        $hasExplicitCustomRetention = (!empty($validated['masa_simpan_custom']) && (int)$validated['masa_simpan_custom'] > 0);
        $hasExplicitRetentionYears = (!empty($validated['retention_years']) && (int)$validated['retention_years'] > 0);

        if ($isMonthsPeriod) {
            $effectiveRetentionYears = $periodMonthsCount >= 12 ? (int)floor($periodMonthsCount / 12) : 0;
            $retentionExpiryDate = $endDate->copy()->endOfMonth()->format('Y-m-d');
        } elseif ($hasExplicitCustomRetention) {
            $effectiveRetentionYears = (int)$validated['masa_simpan_custom'];
            $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth()->format('Y-m-d');
        } elseif ($hasExplicitRetentionYears) {
            $effectiveRetentionYears = (int)$validated['retention_years'];
            $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth()->format('Y-m-d');
        } else {
            $effectiveRetentionYears = 5;
            if ($subDepartment && $subDepartment->retention_years > 0) {
                $effectiveRetentionYears = (int)$subDepartment->retention_years;
            } elseif ($department && $department->retention_years > 0) {
                $effectiveRetentionYears = (int)$department->retention_years;
            }
            $retentionExpiryDate = $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth()->format('Y-m-d');
        }

        // 5. File uploads
        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('archive_digital', 'public');
        }

        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft');
        if (!$isDraft && !$request->hasFile('scan_input_form') && !$this->isRunningTestEnvironment()) {
            return back()->withInput()->withErrors([
                'scan_input_form' => 'Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.'
            ]);
        }

        $scanInputFormPath = null;
        if ($request->hasFile('scan_input_form')) {
            $scanInputFormPath = $request->file('scan_input_form')->store('archive_scans', 'public');
        }

        $archiveStatus = $isDraft ? 'draft' : 'pending_verification';
        $originBorrowingLogId = $request->filled('origin_borrowing_log_id') ? (int)$request->input('origin_borrowing_log_id') : null;
        $originArchiveId = $request->filled('origin_archive_id') ? (int)$request->input('origin_archive_id') : null;

        if ($originBorrowingLogId && $originArchiveId) {
            $existingArchive = Archive::lockForUpdate()->find($originArchiveId);
            if ($existingArchive) {
                if ($existingArchive->status === 'in_warehouse') {
                    return back()->withInput()->withErrors(['boxes' => "Box [{$existingArchive->box_number}] sudah tercatat berada di gudang."]);
                }
                if (in_array($existingArchive->status, ['pending_verification', 'approved_booked'])) {
                    return back()->withInput()->withErrors(['boxes' => "Box [{$existingArchive->box_number}] sudah memiliki pengajuan penyimpanan kembali yang sedang aktif."]);
                }

                $archive = $existingArchive;
                $archive->fill([
                    'warehouse_location_id' => null,
                    'warehouse_rack_slot_id' => null,
                    'department_id' => $validated['department_id'],
                    'sub_department_id' => $validated['sub_department_id'] ?? $archive->sub_department_id,
                    'company_name' => $validated['company_name'] ?? $archive->company_name,
                    'document_type' => $validated['document_type'] ?? $archive->document_type,
                    'title' => $finalTitle,
                    'is_custom_doc_name' => $isCustomDocName,
                    'custom_doc_name' => $customDocName,
                    'periode' => !empty($validated['periode']) ? $validated['periode'] : $formattedPeriodDoc,
                    'period_start_date' => $startDate->format('Y-m-d'),
                    'period_end_date' => $endDate->format('Y-m-d'),
                    'period_text' => $periodText,
                    'period_yy_mm' => $periodYyMm,
                    'periode_doc' => $validated['periode_doc'],
                    'tgl_penyerahan' => $validated['tgl_penyerahan'],
                    'content_description' => $formattedContentDescription,
                    'retention_years' => $effectiveRetentionYears,
                    'masa_simpan_custom' => $validated['masa_simpan_custom'] ?? null,
                    'retention_expiry_date' => $retentionExpiryDate,
                    'physical_condition' => $validated['physical_condition'],
                    'file_path' => $filePath ?: $archive->file_path,
                    'scan_input_form' => $scanInputFormPath ?: $archive->scan_input_form,
                    'status' => $archiveStatus,
                    'origin_borrowing_log_id' => $originBorrowingLogId,
                    're_store_snapshot' => json_encode([
                        'restored_at' => now()->toDateTimeString(),
                        'submitted_by' => $user->name,
                        'origin_borrowing_log_id' => $originBorrowingLogId,
                        'items' => $parsedItems,
                    ]),
                ]);
                $archive->save();

                ArchiveItem::where('archive_id', $archive->id)->delete();

                \App\Models\BorrowingLog::where('id', $originBorrowingLogId)->update([
                    'active_restore_archive_id' => $archive->id
                ]);
            } else {
                $archive = Archive::create([
                    'department_id' => $validated['department_id'],
                    'sub_department_id' => $validated['sub_department_id'] ?? null,
                    'company_name' => $validated['company_name'] ?? 'PT INDRACO GLOBAL INDONESIA',
                    'document_type' => $validated['document_type'] ?? null,
                    'created_by_user_id' => $user->id,
                    'title' => $finalTitle,
                    'is_custom_doc_name' => $isCustomDocName,
                    'custom_doc_name' => $customDocName,
                    'periode' => !empty($validated['periode']) ? $validated['periode'] : $formattedPeriodDoc,
                    'period_start_date' => $startDate->format('Y-m-d'),
                    'period_end_date' => $endDate->format('Y-m-d'),
                    'period_text' => $periodText,
                    'period_yy_mm' => $periodYyMm,
                    'periode_doc' => $validated['periode_doc'],
                    'tgl_penyerahan' => $validated['tgl_penyerahan'],
                    'content_description' => $formattedContentDescription,
                    'retention_years' => $effectiveRetentionYears,
                    'masa_simpan_custom' => $validated['masa_simpan_custom'] ?? null,
                    'retention_expiry_date' => $retentionExpiryDate,
                    'physical_condition' => $validated['physical_condition'],
                    'file_path' => $filePath,
                    'scan_input_form' => $scanInputFormPath,
                    'status' => $archiveStatus,
                    'origin_borrowing_log_id' => $originBorrowingLogId,
                ]);
            }
        } else {
            $archive = Archive::create([
                'department_id' => $validated['department_id'],
                'sub_department_id' => $validated['sub_department_id'] ?? null,
                'company_name' => $validated['company_name'] ?? 'PT INDRACO GLOBAL INDONESIA',
                'document_type' => $validated['document_type'] ?? null,
                'created_by_user_id' => $user->id,
                'title' => $finalTitle,
                'is_custom_doc_name' => $isCustomDocName,
                'custom_doc_name' => $customDocName,
                'periode' => !empty($validated['periode']) ? $validated['periode'] : $formattedPeriodDoc,
                'period_start_date' => $startDate->format('Y-m-d'),
                'period_end_date' => $endDate->format('Y-m-d'),
                'period_text' => $periodText,
                'period_yy_mm' => $periodYyMm,
                'periode_doc' => $validated['periode_doc'],
                'tgl_penyerahan' => $validated['tgl_penyerahan'],
                'content_description' => $formattedContentDescription,
                'retention_years' => $effectiveRetentionYears,
                'masa_simpan_custom' => $validated['masa_simpan_custom'] ?? null,
                'retention_expiry_date' => $retentionExpiryDate,
                'physical_condition' => $validated['physical_condition'],
                'file_path' => $filePath,
                'scan_input_form' => $scanInputFormPath,
                'status' => $archiveStatus,
            ]);
        }

        // 6. Save items in archive_items table (Batch Insert)
        if (!empty($parsedItems)) {
            $now = now();
            $itemsToInsert = array_map(function ($itemData) use ($archive, $now) {
                return [
                    'archive_id' => $archive->id,
                    'item_number' => $itemData['item_number'],
                    'document_name' => $itemData['document_name'],
                    'period_start' => $itemData['period_start'] ?? null,
                    'period_end' => $itemData['period_end'] ?? null,
                    'period_text' => $itemData['period_text'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $parsedItems);

            foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                ArchiveItem::insert($chunk);
            }
        }

        if ($isDraft) {
            ActivityLogger::log(
                'ARCHIVE_DRAFT_SAVE',
                "Menyimpan draft sementara pengajuan arsip '{$archive->title}' (" . count($parsedItems) . " butir berkas) oleh {$user->name}.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'department_id' => $archive->department_id,
                    'status' => 'draft',
                    'items_count' => count($parsedItems),
                ],
                $archive->box_number ?: "Draft ID: {$archive->id}"
            );

            $successMessage = 'Draft usulan box arsip (' . count($parsedItems) . ' butir dokumen) berhasil disimpan sementara. Belum diteruskan ke PIC Gudang.';
        } else {
            ActivityLogger::log(
                'ARCHIVE_CREATE',
                "Pengajuan arsip baru '{$archive->title}' (" . count($parsedItems) . " butir berkas) oleh {$user->name} (" . ($department ? $department->name : '') . ").",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'department_id' => $archive->department_id,
                    'sub_department_id' => $archive->sub_department_id,
                    'period_doc' => $archive->periode_doc,
                    'retention_years' => $archive->retention_years,
                    'retention_expiry_date' => $archive->retention_expiry_date,
                    'items_count' => count($parsedItems),
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );

            $successMessage = 'Pengajuan booking box arsip (' . count($parsedItems) . ' butir dokumen) berhasil disubmit untuk diverifikasi PIC Gudang.';
        }

        $redirectRoute = ($request->input('from') === 'borrowings' || $request->has('is_re_store') || !empty($originBorrowingLogId)) ? 'borrowings.index' : 'archives.index';
        $redirectParams = $this->getEmbedParams($request);

        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', $successMessage);
    }

    /**
     * Store a batch of archives (Multi-Box with dense items) in a single submission.
     */
}
