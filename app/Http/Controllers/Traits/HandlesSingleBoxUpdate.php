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

trait HandlesSingleBoxUpdate
{
    protected function updateSingleBox(Request $request, Archive $archive, $user)
    {
        if ($user->isPicDept() && (int)$archive->department_id !== (int)$user->department_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit arsip departemen ini.');
        }

        // Concurrency Guard (Optimistic Locking): deteksi modifikasi oleh user lain
        if ($request->filled('original_updated_at') && $archive->updated_at) {
            try {
                $clientTime = Carbon::parse($request->input('original_updated_at'))->timestamp;
                $dbTime = $archive->updated_at->timestamp;
                if ($dbTime > $clientTime) {
                    return back()->withInput()->with('error', 'Konflik Perubahan: Data arsip ini baru saja diubah oleh pengguna lain saat Anda sedang membuka form. Silakan muat ulang (refresh) halaman untuk melihat versi terbaru sebelum menyimpan kembali.');
                }
            } catch (\Throwable $e) {}
        }

        // Tangani update pengajuan multi-box
        if ($request->has('boxes') && is_array($request->input('boxes')) && count($request->input('boxes')) > 0) {
            return $this->updateMultiBox($request, $archive, $user);
        }

        $isDraft = ($request->input('submit_action') === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft');

        if (!$request->filled('title')) {
            if ($request->filled('custom_doc_name')) {
                $request->merge(['title' => $request->input('custom_doc_name')]);
            } elseif ($request->has('items') && is_array($request->input('items')) && !empty($request->input('items')[0]['document_name'])) {
                $request->merge(['title' => trim($request->input('items')[0]['document_name'])]);
            } elseif ($isDraft) {
                $request->merge(['title' => $archive->title ?: ('Draft Box Arsip ' . date('Y/m'))]);
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
            $validated['department_id'] = $user->department_id ?: $archive->department_id;
        }

        $validated['physical_condition'] = !empty($validated['physical_condition']) ? $validated['physical_condition'] : ($archive->physical_condition ?: 'Baik / Box Karton Standar TB 30g');

        // 1. Business Rule: Periode Dokumen (derived from periode, periode_doc, or tgl_penyerahan)
        $tglPenyerahan = !empty($validated['tgl_penyerahan']) ? Carbon::parse($validated['tgl_penyerahan']) : ($archive->tgl_penyerahan ? Carbon::parse($archive->tgl_penyerahan) : Carbon::now());
        $validated['tgl_penyerahan'] = $tglPenyerahan->format('Y-m-d');
        $rawPeriod = !empty($validated['periode']) ? trim($validated['periode']) : (!empty($validated['periode_doc']) ? trim($validated['periode_doc']) : $tglPenyerahan->format('Y/m'));
        
        $isMonthsPeriod = false;
        $periodMonthsCount = 0;
        if (preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $rawPeriod, $matches)) {
            $isMonthsPeriod = true;
            $periodMonthsCount = (int)$matches[1];
            $maxPeriodMonths = $this->getMaxPeriodMonths($archive->department_id, $user);
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

        // If empty, create at least 1 default item
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

        // Validasi keunikan nama box terhadap database
        $dupQuery = Archive::where('department_id', $validated['department_id'])
            ->where('status', '!=', 'destroyed')
            ->where('id', '!=', $archive->id)
            ->whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($finalTitle))]);
        if (!empty($archive->origin_archive_id)) {
            $dupQuery->where('id', '!=', (int)$archive->origin_archive_id);
        }
        if ($dupArchive = $dupQuery->first()) {
            return back()->withInput()->withErrors([
                'title' => "Nama / Judul Box \"{$finalTitle}\" sudah digunakan oleh arsip lain [{$dupArchive->box_number}]. Harap gunakan nama box yang berbeda."
            ]);
        }

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

        // 5. File uploads (only update if new files provided, or remove if requested)
        $filePath = $archive->file_path;
        if ($request->input('remove_file') == '1') {
            if ($archive->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($archive->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($archive->file_path);
            }
            $filePath = null;
        }
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('archive_digital', 'public');
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

        $submitAction = $request->input('submit_action');
        $isDraft = ($submitAction === 'draft' || $request->has('save_draft') || $request->input('action') === 'draft');
        if (!$isDraft && empty($scanInputFormPath) && !$this->isRunningTestEnvironment()) {
            return back()->withInput()->withErrors([
                'scan_input_form' => 'Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.'
            ]);
        }

        if ($archive->status === 'draft') {
            $archiveStatus = $isDraft ? 'draft' : 'pending_verification';
        } else {
            // If already beyond draft (pending_verification, approved_booked, in_warehouse, taken, destroyed), retain status unless explicit
            if ($submitAction === 'draft') {
                $archiveStatus = 'draft';
            } elseif ($submitAction === 'submit' && $archive->status === 'draft') {
                $archiveStatus = 'pending_verification';
            } else {
                $archiveStatus = $archive->status;
            }
        }

        $archive->update([
            'department_id' => $validated['department_id'],
            'sub_department_id' => $validated['sub_department_id'] ?? null,
            'company_name' => $validated['company_name'] ?? $archive->company_name,
            'document_type' => array_key_exists('document_type', $validated) ? $validated['document_type'] : $archive->document_type,
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

        // Sync items (Batch Insert)
        $archive->items()->delete();
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

        if ($archiveStatus === 'draft') {
            ActivityLogger::log(
                'ARCHIVE_DRAFT_UPDATE',
                "Memperbarui draft sementara pengajuan arsip '{$archive->title}' (" . count($parsedItems) . " butir berkas) oleh {$user->name}.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'status' => 'draft',
                ],
                $archive->box_number ?: "Draft ID: {$archive->id}"
            );

            $successMessage = 'Draft usulan box arsip (' . count($parsedItems) . ' butir dokumen) berhasil diperbarui dan tersimpan sementara.';
        } elseif ($archiveStatus === 'pending_verification' && $isDraft === false && $archive->wasChanged('status')) {
            ActivityLogger::log(
                'ARCHIVE_SUBMIT',
                "Mengajukan pengajuan box arsip '{$archive->title}' (" . count($parsedItems) . " butir berkas) ke PIC Gudang oleh {$user->name}.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'status' => 'pending_verification',
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );

            $successMessage = 'Pengajuan booking box arsip (' . count($parsedItems) . ' butir dokumen) berhasil diajukan dan diteruskan ke PIC Gudang untuk diverifikasi.';
        } else {
            ActivityLogger::log(
                'ARCHIVE_UPDATE',
                "Memperbarui data rincian berkas arsip '{$archive->title}' (" . count($parsedItems) . " butir berkas) [Status: {$archive->status_label}] oleh {$user->name}.",
                'DOKUMEN_ARSIP',
                [
                    'archive_id' => $archive->id,
                    'title' => $archive->title,
                    'status' => $archive->status,
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );

            $successMessage = 'Data rincian berkas arsip (' . count($parsedItems) . ' butir dokumen) berhasil diperbarui.';
        }

        $redirectRoute = ($request->input('from') === 'borrowings' || !empty($archive->origin_borrowing_log_id)) ? 'borrowings.index' : 'archives.index';
        $redirectParams = $this->getEmbedParams($request);

        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', $successMessage);
    }

    /**
     * Update a batch/multi-box archive submission.
     */
}
