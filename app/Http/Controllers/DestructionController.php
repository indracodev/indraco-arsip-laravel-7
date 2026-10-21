<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesArchiveExtensions;
use App\Models\Archive;
use App\Models\ArchiveExtensionLog;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DestructionController extends Controller
{
    use HandlesArchiveExtensions;

    public function index()

    {
        $user = auth()->user();

        // 1. Data berkas H-30: berkas aktif di gudang yang belum diajukan pemusnahan atau perpanjangan
        $archivesQuery = Archive::whereNotIn('status', ['destroyed', 'draft', 'pending_destruction', 'pending_extension'])
            ->whereDoesntHave('destructionLogs', function ($q) {
                $q->whereIn('approval_status', ['pending', 'approved', 'completed']);
            })
            ->whereDoesntHave('extensionLogs', function ($q) {
                $q->where('approval_status', 'pending');
            })
            ->when($user->isPicDept(), function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->with(['department', 'subDepartment', 'location.warehouse', 'rackSlot']);

        $allArchives = $archivesQuery->get();

        $expiredArchives = $allArchives->filter(function ($arc) {
            // Tentukan akhir bulan dari bulan terakhir periode
            $periodEnd = null;
            if ($arc->period_end_date) {
                $periodEnd = Carbon::parse($arc->period_end_date)->endOfMonth();
            } elseif (!empty($arc->periode_doc) && preg_match('/^(\d+)\s*(?:bulan|bln)/i', $arc->periode_doc, $m)) {
                $months = (int)$m[1];
                $base = $arc->tgl_penyerahan ? Carbon::parse($arc->tgl_penyerahan) : Carbon::parse($arc->created_at);
                $periodEnd = $base->copy()->startOfMonth()->addMonths($months)->endOfMonth();
            } elseif (!empty($arc->periode_doc) && preg_match('/(\d{4})[\/\-](\d{1,2})/i', $arc->periode_doc, $m)) {
                $periodEnd = Carbon::createFromDate((int)$m[1], (int)$m[2], 1)->endOfMonth();
            } elseif ($arc->retention_expiry_date) {
                $periodEnd = Carbon::parse($arc->retention_expiry_date)->endOfMonth();
            }

            if (!$periodEnd && !$arc->retention_expiry_date) {
                return false;
            }

            // Expiry retention selalu jatuh pada tanggal terakhir di bulan expiry
            if ($arc->retention_expiry_date) {
                $expiryEndOfMonth = Carbon::parse($arc->retention_expiry_date)->endOfMonth()->endOfDay();
            } else {
                $isMonthly = (!empty($arc->periode_doc) && preg_match('/^(\d+)\s*(?:bulan|bln)/i', $arc->periode_doc));
                if ($isMonthly) {
                    $expiryEndOfMonth = $periodEnd->copy()->endOfMonth()->endOfDay();
                } else {
                    $retentionYears = $arc->retention_years ?: 5;
                    $expiryEndOfMonth = $periodEnd->copy()->addYears($retentionYears)->endOfMonth()->endOfDay();
                }
            }

            // Batas H-30: 30 hari sebelum tanggal terakhir di bulan expiry
            $h30Threshold = $expiryEndOfMonth->copy()->subDays(30)->startOfDay();

            // Berkas masuk daftar H-30 jika hari ini sudah mencapai atau melewati H-30
            return Carbon::today()->gte($h30Threshold);
        })->map(function ($arc) {
            $periodEnd = null;
            if ($arc->period_end_date) {
                $periodEnd = Carbon::parse($arc->period_end_date)->endOfMonth();
            } elseif (!empty($arc->periode_doc) && preg_match('/^(\d+)\s*(?:bulan|bln)/i', $arc->periode_doc, $m)) {
                $months = (int)$m[1];
                $base = $arc->tgl_penyerahan ? Carbon::parse($arc->tgl_penyerahan) : Carbon::parse($arc->created_at);
                $periodEnd = $base->copy()->startOfMonth()->addMonths($months)->endOfMonth();
            } elseif (!empty($arc->periode_doc) && preg_match('/(\d{4})[\/\-](\d{1,2})/i', $arc->periode_doc, $m)) {
                $periodEnd = Carbon::createFromDate((int)$m[1], (int)$m[2], 1)->endOfMonth();
            } elseif ($arc->retention_expiry_date) {
                $periodEnd = Carbon::parse($arc->retention_expiry_date)->endOfMonth();
            }

            if ($arc->retention_expiry_date) {
                $expiryEndOfMonth = Carbon::parse($arc->retention_expiry_date)->endOfMonth()->endOfDay();
            } else {
                $isMonthly = (!empty($arc->periode_doc) && preg_match('/^(\d+)\s*(?:bulan|bln)/i', $arc->periode_doc));
                if ($isMonthly) {
                    $expiryEndOfMonth = $periodEnd->copy()->endOfMonth()->endOfDay();
                } else {
                    $retentionYears = $arc->retention_years ?: 5;
                    $expiryEndOfMonth = $periodEnd->copy()->addYears($retentionYears)->endOfMonth()->endOfDay();
                }
            }

            $diffDays = Carbon::today()->diffInDays($expiryEndOfMonth, false);

            return [
                'id' => $arc->id,
                'box_number' => $arc->box_number,
                'title' => $arc->effective_title ?? $arc->title,
                'file_path' => $arc->file_path ? app_storage_url($arc->file_path) : null,
                'preview_stream_url' => $arc->file_path ? app_preview_stream_url($arc->file_path) : null,
                'raw_file_path' => $arc->file_path,
                'file_extension' => $arc->file_path ? strtolower(pathinfo($arc->file_path, PATHINFO_EXTENSION)) : null,
                'department' => $arc->department,
                'sub_department' => $arc->subDepartment,
                'location' => $arc->location,
                'location_text' => $arc->display_location ?? ($arc->location ? $arc->location->full_location : 'Gudang'),
                'period_text' => $arc->period_text ?? ($arc->periode_doc ?? '-'),
                'retention_expiry_date' => $expiryEndOfMonth->format('M Y'),
                'formatted_expiry_date' => $expiryEndOfMonth->format('M Y'),
                'raw_expiry_date' => $expiryEndOfMonth->format('Y-m-d'),
                'retention_years' => $arc->retention_years,
                'retention_display' => $arc->retention_display,
                'retention_duration_label' => $arc->retention_duration_label,
                'status' => $arc->status,
                'diff_days' => $diffDays,
                'is_expired' => $diffDays < 0,
                'badge_text' => $diffDays < 0 ? 'Lewat ' . abs($diffDays) . ' Hari' : ($diffDays === 0 ? 'Hari Ini Jatuh Tempo' : 'Jatuh Tempo Bulan Ini (Sisa ' . $diffDays . ' Hari)'),
            ];
        })->sortBy('diff_days')->values();

        // 2. Log Riwayat & Antrean Perpanjangan (ArchiveExtensionLog)
        $extQuery = ArchiveExtensionLog::with(['archive.department', 'proposedBy', 'approvedBy']);
        if ($user->isPicDept()) {
            $extQuery->whereHas('archive', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }
        $dbExtLogs = $extQuery->latest()->get();

        if ($dbExtLogs->isNotEmpty()) {
            $extensionLogs = $dbExtLogs->map(function ($log) use ($user) {
                $arc = $log->archive;
                $months = (int) $log->additional_months;
                $durationLabel = ($months % 12 === 0 && $months > 0)
                    ? ('+' . ($months / 12) . ' Tahun')
                    : ('+' . $months . ' Bulan');

                return [
                    'id' => $log->id,
                    'box_number' => $arc ? $arc->box_number : ($log->archive_id ?? '-'),
                    'archive_title' => $arc ? ($arc->effective_title ?? $arc->title) : "Arsip ID {$log->archive_id}",
                    'department_code' => $arc && $arc->department ? $arc->department->code : '-',
                    'additional_months' => $months,
                    'additional_years' => $log->additional_years,
                    'duration_label' => $durationLabel,
                    'new_expiry_date' => $log->new_expiry_date ? $log->new_expiry_date->format('M Y') : '-',
                    'reason' => $log->extension_reason ?: '-',
                    'user_name' => $log->proposedBy ? $log->proposedBy->name : 'PIC Dept',
                    'date' => $log->created_at ? $log->created_at->format('d M Y H:i') : '-',
                    'scan_file' => $log->scan_extension_form ? app_storage_url($log->scan_extension_form) : null,
                    'scan_file_stream' => $log->scan_extension_form ? app_preview_stream_url($log->scan_extension_form) : null,
                    'scan_file_raw' => $log->scan_extension_form,
                    'archive_id' => $log->archive_id,
                    'approval_status' => $log->approval_status,
                    'status_label' => $log->status_label,
                    'status_badge' => $log->status_badge,
                    'approver_name' => $log->approvedBy ? $log->approvedBy->name : null,
                    'rejection_note' => $log->rejection_note,
                    'can_approve' => ($user->isPicGudang() || $user->isSuperAdmin()) && $log->approval_status === 'pending',
                ];
            });
        } else {
            // Fallback for legacy logs in ActivityLog
            $extensionLogsQuery = \App\Models\ActivityLog::where('action', 'like', '%EXTEND%');
            if ($user->isPicDept()) {
                $extensionLogsQuery->where('department_id', $user->department_id);
            }
            $rawLogs = $extensionLogsQuery->latest()->get();
            $targetArchiveIds = [];
            foreach ($rawLogs as $log) {
                $props = is_array($log->properties) ? $log->properties : (json_decode($log->properties, true) ?? []);
                if (!empty($props['archive_id'])) {
                    $targetArchiveIds[] = (int)$props['archive_id'];
                }
            }
            $archivesMap = !empty($targetArchiveIds)
                ? Archive::with('department')->whereIn('id', array_unique($targetArchiveIds))->get()->keyBy('id')
                : collect();

            $extensionLogs = $rawLogs->map(function ($log) use ($archivesMap) {
                $props = is_array($log->properties) ? $log->properties : (json_decode($log->properties, true) ?? []);
                $archive = !empty($props['archive_id']) ? $archivesMap->get($props['archive_id']) : null;
                $months = isset($props['additional_months']) ? (int)$props['additional_months'] : 0;
                $durationLabel = ($months > 0)
                    ? (($months % 12 === 0) ? ('+' . ($months / 12) . ' Tahun') : ('+' . $months . ' Bulan'))
                    : (!empty($props['additional_years']) ? ('+' . $props['additional_years'] . ' Tahun') : '-');

                return [
                    'id' => $log->id,
                    'box_number' => $log->reference_id ?? ($archive->box_number ?? '-'),
                    'archive_title' => $archive ? ($archive->effective_title ?? $archive->title) : ($log->description),
                    'department_code' => $archive && $archive->department ? $archive->department->code : ($log->user_role ?? '-'),
                    'additional_months' => $months,
                    'additional_years' => $props['additional_years'] ?? null,
                    'duration_label' => $durationLabel,
                    'new_expiry_date' => !empty($props['new_expiry_date']) ? Carbon::parse($props['new_expiry_date'])->format('M Y') : '-',
                    'reason' => $props['extension_reason'] ?? '-',
                    'user_name' => $log->user_name ?? ($log->user ? $log->user->name : 'PIC'),
                    'date' => $log->created_at ? $log->created_at->format('d M Y H:i') : '-',
                    'scan_file' => !empty($props['scan_extension_form']) ? app_storage_url($props['scan_extension_form']) : ($archive && $archive->scan_extension_form ? app_storage_url($archive->scan_extension_form) : null),
                    'scan_file_stream' => !empty($props['scan_extension_form']) ? app_preview_stream_url($props['scan_extension_form']) : ($archive && $archive->scan_extension_form ? app_preview_stream_url($archive->scan_extension_form) : null),
                    'scan_file_raw' => !empty($props['scan_extension_form']) ? $props['scan_extension_form'] : ($archive && $archive->scan_extension_form ? $archive->scan_extension_form : null),
                    'archive_id' => $props['archive_id'] ?? null,
                    'approval_status' => 'approved',
                    'status_label' => 'Disetujui Gudang',
                    'status_badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
                    'approver_name' => null,
                    'rejection_note' => null,
                    'can_approve' => false,
                ];
            });

            if ($extensionLogs->isEmpty()) {
                $extendedArchives = Archive::whereNotNull('extension_reason')
                    ->when($user->isPicDept(), function ($q) use ($user) {
                        return $q->where('department_id', $user->department_id);
                    })
                    ->with('department')
                    ->latest('updated_at')
                    ->get()
                    ->map(function ($arc) {
                        return [
                            'id' => $arc->id,
                            'box_number' => $arc->box_number,
                            'archive_title' => $arc->effective_title ?? $arc->title,
                            'department_code' => $arc->department ? $arc->department->code : '-',
                            'additional_months' => 0,
                            'additional_years' => $arc->retention_years,
                            'duration_label' => ($arc->retention_display ?: ($arc->retention_years ? $arc->retention_years . ' Tahun' : '-')),
                            'new_expiry_date' => $arc->retention_expiry_date ? Carbon::parse($arc->retention_expiry_date)->format('M Y') : '-',
                            'reason' => $arc->extension_reason ?: '-',
                            'user_name' => 'PIC Dept',
                            'date' => $arc->updated_at ? $arc->updated_at->format('d M Y H:i') : '-',
                            'scan_file' => $arc->scan_extension_form ? app_storage_url($arc->scan_extension_form) : null,
                            'scan_file_stream' => $arc->scan_extension_form ? app_preview_stream_url($arc->scan_extension_form) : null,
                            'scan_file_raw' => $arc->scan_extension_form,
                            'archive_id' => $arc->id,
                            'approval_status' => 'approved',
                            'status_label' => 'Disetujui Gudang',
                            'status_badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
                            'approver_name' => null,
                            'rejection_note' => null,
                            'can_approve' => false,
                        ];
                    });
                if ($extendedArchives->isNotEmpty()) {
                    $extensionLogs = $extendedArchives;
                }
            }
        }

        // 3. Log Riwayat Pemusnahan (Tabel Pemusnahan - default kosong jika belum ada)
        $destructionLogsQuery = DestructionLog::with(['archive.department', 'proposedBy', 'approvedBy']);
        if ($user->isPicDept()) {
            $destructionLogsQuery->whereHas('archive', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }
        $destructionLogs = $destructionLogsQuery->latest()->get()->map(function ($dLog) use ($user) {
            $status = $dLog->approval_status ?? ($dLog->archive && $dLog->archive->status === 'destroyed' ? 'completed' : 'pending');
            $canPrintBap = !empty($dLog->certificate_file);
            
            $statusLabel = 'Pengajuan Pemusnahan';
            $statusBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border-amber-300 dark:border-amber-700';
            if ($status === 'approved') {
                $statusLabel = 'Antrean Pemusnahan';
                $statusBadge = 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 border-blue-300 dark:border-blue-700';
            } elseif ($status === 'rejected') {
                $statusLabel = 'Ditolak';
                $statusBadge = 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 border-rose-300 dark:border-rose-700';
            } elseif ($status === 'completed' || ($dLog->archive && $dLog->archive->status === 'destroyed')) {
                $statusLabel = 'Telah Dimusnahkan';
                $statusBadge = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700';
            }

            return [
                'id' => $dLog->id,
                'bap_number' => $dLog->bap_number,
                'archive_id' => $dLog->archive_id,
                'archive_title' => $dLog->archive ? ($dLog->archive->effective_title ?? $dLog->archive->title) : '-',
                'box_number' => $dLog->archive ? $dLog->archive->box_number : '-',
                'department_code' => $dLog->archive && $dLog->archive->department ? $dLog->archive->department->code : '-',
                'destruction_date' => $dLog->destruction_date ? $dLog->destruction_date->format('d M Y') : '-',
                'method' => $dLog->method,
                'executor' => $dLog->proposedBy ? $dLog->proposedBy->name : 'Gudang Specialist',
                'approval_file' => $dLog->effective_approval_file ? app_storage_url($dLog->effective_approval_file) : null,
                'approval_file_stream' => $dLog->effective_approval_file ? app_preview_stream_url($dLog->effective_approval_file) : null,
                'approval_file_raw' => $dLog->effective_approval_file,
                'certificate_file' => $dLog->certificate_file ? app_storage_url($dLog->certificate_file) : null,
                'certificate_file_stream' => $dLog->certificate_file ? app_preview_stream_url($dLog->certificate_file) : null,
                'certificate_file_raw' => $dLog->certificate_file,
                'approval_status' => $status,
                'status_label' => $statusLabel,
                'status_badge' => $statusBadge,
                'rejection_note' => $dLog->approval_notes,
                'can_print_bap' => $canPrintBap,
                'can_approve' => ($user->isPicGudang() || $user->isSuperAdmin()) && $status === 'pending',
                'can_reject' => ($user->isPicGudang() || $user->isSuperAdmin()) && in_array($status, ['pending', 'approved']),
                'can_complete' => ($user->isPicGudang() || $user->isSuperAdmin()) && $status === 'approved',
            ];
        });

        return view('destructions.index', compact('expiredArchives', 'extensionLogs', 'destructionLogs'));
    }

    public function proposeForm(Request $request, $archive = null)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            abort(403, 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk mengajukan pemusnahan berkas.');
        }

        $selectedArchiveIds = [];
        if ($archive instanceof Archive) {
            $selectedArchiveIds[] = $archive->id;
        } elseif (is_numeric($archive)) {
            $selectedArchiveIds[] = (int)$archive;
        } elseif ($request->has('archive_ids') && is_array($request->archive_ids)) {
            $selectedArchiveIds = array_map('intval', $request->archive_ids);
        } elseif ($request->filled('archive_id')) {
            $selectedArchiveIds[] = (int)$request->archive_id;
        }
        $selectedArchiveIds = array_values(array_unique(array_filter($selectedArchiveIds)));

        // Fetch candidate archives for search autocomplete & accordion display
        $archivesQuery = Archive::select(
            'id', 'archive_code', 'title', 'box_number', 'department_id',
            'sub_department_id', 'warehouse_location_id', 'warehouse_rack_slot_id',
            'retention_expiry_date', 'status', 'periode_doc', 'retention_years',
            'period_start_date', 'period_end_date', 'period_text', 'physical_condition', 'content_description'
        )->with([
            'department:id,name,code',
            'subDepartment:id,name',
            'location:id,warehouse_id,room_sector,rack_code,shelf_code',
            'location.warehouse:id,name,code',
            'rackSlot:id,warehouse_location_id,sap_level,layer,slot_number,slot_code',
            'items:id,archive_id,item_number,document_name,period_start,period_end,period_text,notes'
        ])->whereNotIn('status', ['destroyed', 'draft', 'pending_destruction', 'pending_extension'])
        ->whereDoesntHave('destructionLogs', function ($q) {
            $q->whereIn('approval_status', ['pending', 'approved', 'completed']);
        })
        ->whereDoesntHave('extensionLogs', function ($q) {
            $q->where('approval_status', 'pending');
        });

        if ($user->isPicDept()) {
            $archivesQuery->where('department_id', $user->department_id);
        }

        $archives = $archivesQuery->get();
        $archive = !empty($selectedArchiveIds) ? $archives->firstWhere('id', $selectedArchiveIds[0]) : null;
        $selectedArchiveId = $archive ? $archive->id : null;
        $autoBapNumber = old('bap_number') ?: ('BAP/IND/' . date('Y') . '/' . str_pad($selectedArchiveId ?: rand(100, 999), 5, '0', STR_PAD_LEFT));

        return view('destructions.propose', compact('archive', 'archives', 'selectedArchiveIds', 'selectedArchiveId', 'autoBapNumber'));
    }

    public function propose(Request $request, $archive = null)
    {
        $user = auth()->user();

        if ($user && $user->isPicGudang()) {
            abort(403, 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk mengajukan pemusnahan berkas.');
        }

        if (!$user->isSuperAdmin() && !$user->isPicDept()) {
            abort(403, 'Akses ditolak.');
        }

        $rawArchiveIds = [];
        if ($request->has('archive_ids') && is_array($request->input('archive_ids'))) {
            $rawArchiveIds = array_filter(array_map('intval', $request->input('archive_ids')));
        } elseif ($request->filled('archive_id')) {
            $rawArchiveIds = [(int)$request->input('archive_id')];
        } elseif ($archive instanceof Archive) {
            $rawArchiveIds = [$archive->id];
        } elseif (is_numeric($archive)) {
            $rawArchiveIds = [(int)$archive];
        }

        $archiveIds = array_values(array_unique($rawArchiveIds));

        if (empty($archiveIds)) {
            return back()->withInput()->withErrors([
                'archive_ids' => 'Pilih minimal satu berkas arsip yang akan dimusnahkan terlebih dahulu.',
                'archive_id' => 'Berkas arsip yang dipilih tidak valid atau tidak ditemukan.',
            ]);
        }

        $archives = Archive::whereIn('id', $archiveIds)->get();

        foreach ($archives as $arc) {
            if (in_array($arc->status, ['pending_destruction', 'destroyed', 'pending_extension'])) {
                return back()->withInput()->withErrors([
                    'archive_ids' => "Berkas '{$arc->title}' sedang dalam proses pengajuan pemusnahan/perpanjangan atau telah dimusnahkan.",
                ]);
            }
        }

        if ($user->isPicDept()) {
            foreach ($archives as $arc) {
                if ($arc->department_id !== $user->department_id) {
                    abort(403, 'Anda hanya dapat mengajukan pemusnahan berkas milik departemen Anda.');
                }
            }
        }

        $isPicDept = $user->isPicDept();

        // Validasi input
        $rules = [
            'destruction_date' => 'required|date',
            'method' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'approval_file' => 'required_without:scan_approval_destruction|nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'scan_approval_destruction' => 'required_without:approval_file|nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
        ];

        if (!$isPicDept) {
            $rules['bap_number'] = 'nullable|string|max:100';
        }

        $validated = $request->validate($rules, [
            'destruction_date.required' => 'Tanggal pelaksanaan pemusnahan wajib diisi.',
            'method.required' => 'Metode fisik pemusnahan wajib dipilih.',
            'approval_file.required_without' => 'Wajib mengunggah berkas surat persetujuan (approval) pemusnahan.',
            'approval_file.max' => 'Ukuran file surat persetujuan maksimal 500 KB.',
            'scan_approval_destruction.max' => 'Ukuran file persetujuan maksimal 500 KB.',
            'certificate_file.max' => 'Ukuran file sertifikat BAP maksimal 500 KB.',
        ]);

        $scanApprovalPath = null;
        if ($request->hasFile('approval_file')) {
            $scanApprovalPath = $request->file('approval_file')->store('destruction_approvals', 'public');
        } elseif ($request->hasFile('scan_approval_destruction')) {
            $scanApprovalPath = $request->file('scan_approval_destruction')->store('destruction_approvals', 'public');
        }

        $certPath = null;
        if ($request->hasFile('certificate_file')) {
            $certPath = $request->file('certificate_file')->store('bap_certificates', 'public');
        }

        $isImmediateCompletion = !$isPicDept && !empty($validated['bap_number']) && !str_starts_with($validated['bap_number'], 'DRAFT') && $validated['bap_number'] !== '(Menunggu Penerbitan BAP)';

        $bapNumber = $isImmediateCompletion 
            ? $validated['bap_number'] 
            : '(Menunggu Penerbitan BAP)';

        $approvalStatus = $isImmediateCompletion ? 'completed' : 'pending';

        \Illuminate\Support\Facades\DB::transaction(function () use ($archives, $user, $validated, $certPath, $scanApprovalPath, $isImmediateCompletion, $bapNumber, $approvalStatus) {
            foreach ($archives as $arc) {
                $filePath = $scanApprovalPath ?: $arc->scan_approval_destruction;

                if ($isImmediateCompletion) {
                    // Release slot rak hanya jika langsung selesai
                    if ($arc->warehouse_rack_slot_id) {
                        $slot = $arc->rackSlot;
                        if ($slot) {
                            $slot->update([
                                'archive_id' => null,
                                'status' => 'empty',
                            ]);
                        }
                    }
                    if ($arc->warehouse_location_id) {
                        $loc = $arc->location;
                        if ($loc && $loc->current_box_count > 0) {
                            $loc->decrement('current_box_count');
                        }
                    }

                    $arc->update([
                        'status' => 'destroyed',
                        'warehouse_location_id' => null,
                        'warehouse_rack_slot_id' => null,
                    ]);
                } else {
                    // Pengajuan Pemusnahan (Tahap 1) - Rak tetap terisi
                    $arc->update([
                        'status' => 'pending_destruction',
                    ]);
                }

                DestructionLog::create([
                    'archive_id' => $arc->id,
                    'proposed_by_user_id' => $user->id,
                    'department_approval_by' => $user->id,
                    'department_approved_at' => now(),
                    'approved_by_dept_pic_id' => $isImmediateCompletion ? $user->id : null,
                    'bap_number' => $bapNumber,
                    'destruction_date' => $validated['destruction_date'],
                    'method' => $validated['method'],
                    'certificate_file' => $certPath,
                    'approval_file' => $filePath,
                    'scan_approval_destruction' => $filePath,
                    'is_approval_uploaded' => !empty($filePath),
                    'approval_status' => $approvalStatus,
                    'notes' => $validated['notes'] ?? null,
                ]);

                ActivityLogger::log(
                    $isImmediateCompletion ? 'DESTRUCTION_COMPLETE' : 'DESTRUCTION_PROPOSE',
                    $isImmediateCompletion 
                        ? "Pemusnahan berkas '{$arc->title}' (Box: {$arc->box_number}) disahkan dengan No. BAP {$bapNumber}" 
                        : "Pengajuan usulan pemusnahan berkas '{$arc->title}' (Box: {$arc->box_number}) oleh {$user->name}",
                    'PEMUSNAHAN_RETENSI',
                    [
                        'archive_id' => $arc->id,
                        'bap_number' => $bapNumber,
                        'method' => $validated['method'],
                        'destruction_date' => $validated['destruction_date'],
                        'status' => $approvalStatus,
                    ],
                    $arc->box_number ?: "Arsip ID: {$arc->id}"
                );
            }
        });

        $redirectParams = [];
        if ($request->filled('tab')) {
            $redirectParams['tab'] = $request->input('tab');
        }
        if ($request->filled('embed') || $request->has('embed')) {
            $redirectParams['embed'] = 1;
        }

        $count = $archives->count();
        $boxTitles = $archives->pluck('box_number')->filter()->implode(', ') ?: ($count . ' Box');

        $successMessage = $isImmediateCompletion 
            ? "Proses pemusnahan {$count} box berkas ({$boxTitles}) berhasil disahkan dengan No. BAP {$bapNumber}."
            : "Pengajuan pemusnahan {$count} box berkas ({$boxTitles}) berhasil dikirim. Menunggu verifikasi dan persetujuan dari PIC Gudang.";

        return redirect()->route('destructions.index', $redirectParams)
            ->with('active_tab', 'destructions')
            ->with('success', $successMessage);
    }

    /**
     * TAHAP 2: Gudang / Superadmin menyetujui pengajuan pemusnahan (masuk antrean).
     */
    public function approve(DestructionLog $destructionLog, Request $request)
    {
        $user = auth()->user();

        if (!$user->isPicGudang() && !$user->isSuperAdmin()) {
            abort(403, 'Hanya PIC Gudang atau Super Admin yang berwenang menyetujui pengajuan pemusnahan.');
        }

        $destructionLog->update([
            'approval_status' => 'approved',
            'approved_by_dept_pic_id' => $user->id,
        ]);

        $archive = $destructionLog->archive;
        if ($archive && $archive->status !== 'pending_destruction') {
            $archive->update(['status' => 'pending_destruction']);
        }

        ActivityLogger::log(
            'DESTRUCTION_APPROVE',
            "Pengajuan pemusnahan berkas '{$archive->title}' disetujui oleh {$user->name} dan dimasukkan ke Antrean Pemusnahan.",
            'PEMUSNAHAN_RETENSI',
            [
                'destruction_log_id' => $destructionLog->id,
                'archive_id' => $destructionLog->archive_id,
            ],
            $archive->box_number ?: "ID: {$archive->id}"
        );

        return redirect()->back()
            ->with('success', "Pengajuan pemusnahan berkas ({$archive->title}) disetujui dan telah masuk ke Antrean Pemusnahan Gudang.");
    }

    /**
     * Penolakan Pengajuan Pemusnahan oleh Gudang / Super Admin
     */
    public function reject(DestructionLog $destructionLog, Request $request)
    {
        $user = auth()->user();
        if (!$user->isPicGudang() && !$user->isSuperAdmin()) {
            abort(403, 'Hanya PIC Gudang atau Super Admin yang berwenang menolak pengajuan pemusnahan.');
        }

        $request->validate([
            'rejection_note' => 'required|string|max:500',
        ], [
            'rejection_note.required' => 'Alasan penolakan pemusnahan wajib diisi.',
            'rejection_note.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $archive = $destructionLog->archive;
        $reason = $request->input('rejection_note');

        \Illuminate\Support\Facades\DB::transaction(function () use ($destructionLog, $archive, $user, $reason) {
            if ($archive && in_array($archive->status, ['pending_destruction'])) {
                $archive->update([
                    'status' => 'in_warehouse',
                ]);
            }

            $destructionLog->update([
                'approval_status' => 'rejected',
                'approval_notes' => $reason,
                'approved_by_dept_pic_id' => $user->id,
            ]);

            ActivityLogger::log(
                'DESTRUCTION_REJECTED',
                "Pengajuan pemusnahan berkas '" . ($archive ? $archive->title : "ID #{$destructionLog->archive_id}") . "' ditolak oleh {$user->name}. Alasan: {$reason}",
                'PEMUSNAHAN_RETENSI',
                [
                    'destruction_log_id' => $destructionLog->id,
                    'archive_id' => $archive ? $archive->id : null,
                    'rejection_note' => $reason,
                    'rejected_by' => $user->id,
                ],
                $archive ? $archive->box_number : '-'
            );
        });

        return redirect()->back()
            ->with('active_tab', 'destructions')
            ->with('info', "Pengajuan pemusnahan berkas berhasil ditolak. Status berkas dikembalikan normal di gudang.");
    }

    /**
     * TAHAP 3: Gudang / Superadmin menyelesaikan eksekusi fisik & menerbitkan nomor BAP resmi.
     */
    public function complete(DestructionLog $destructionLog, Request $request)
    {
        $user = auth()->user();

        if (!$user->isPicGudang() && !$user->isSuperAdmin()) {
            abort(403, 'Hanya PIC Gudang atau Super Admin yang berwenang menyelesaikan pemusnahan berkas.');
        }

        $validated = $request->validate([
            'bap_number' => 'required|string|max:100',
            'destruction_date' => 'required|date',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'notes' => 'nullable|string|max:1000',
        ], [
            'bap_number.required' => 'Nomor BAP resmi wajib diisi oleh Gudang.',
            'destruction_date.required' => 'Tanggal pelaksanaan fisik pemusnahan wajib diisi.',
            'certificate_file.max' => 'Ukuran berkas sertifikat BAP maksimal 500 KB.',
        ]);

        $archive = $destructionLog->archive;

        $certPath = $destructionLog->certificate_file;
        if ($request->hasFile('certificate_file')) {
            $certPath = $request->file('certificate_file')->store('bap_certificates', 'public');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($destructionLog, $archive, $user, $validated, $certPath) {
            // Kosongkan slot rak gudang arsip
            if ($archive && $archive->warehouse_rack_slot_id) {
                $slot = $archive->rackSlot;
                if ($slot) {
                    $slot->update([
                        'archive_id' => null,
                        'status' => 'empty',
                    ]);
                }
            }

            // Kurangi kuota box lokasi rak
            if ($archive && $archive->warehouse_location_id) {
                $loc = $archive->location;
                if ($loc && $loc->current_box_count > 0) {
                    $loc->decrement('current_box_count');
                }
            }

            // Update status arsip resmi menjadi 'destroyed'
            if ($archive) {
                $archive->update([
                    'status' => 'destroyed',
                    'warehouse_location_id' => null,
                    'warehouse_rack_slot_id' => null,
                ]);
            }

            // Update log pemusnahan menjadi selesai
            $destructionLog->update([
                'bap_number' => $validated['bap_number'],
                'destruction_date' => $validated['destruction_date'],
                'certificate_file' => $certPath,
                'approval_status' => 'completed',
                'notes' => $validated['notes'] ?? $destructionLog->notes,
            ]);

            ActivityLogger::log(
                'DESTRUCTION_EXECUTE',
                "Pemusnahan berkas fisik '{$archive->title}' (Box: {$archive->box_number}) selesai dieksekusi dengan No. BAP {$validated['bap_number']}.",
                'PEMUSNAHAN_RETENSI',
                [
                    'destruction_log_id' => $destructionLog->id,
                    'archive_id' => $destructionLog->archive_id,
                    'bap_number' => $validated['bap_number'],
                ],
                $validated['bap_number']
            );
        });

        return redirect()->back()
            ->with('success', "Pemusnahan berkas ({$archive->title}) telah selesai dieksekusi dengan No. BAP {$validated['bap_number']}. Slot rak telah dikosongkan dan BAP siap dicetak.");
    }

    public function showBap(DestructionLog $destructionLog)
    {
        if (empty($destructionLog->certificate_file)) {
            return redirect()->back()->with('error', 'File Berita Acara Pemusnahan (BAP) belum diunggah oleh Gudang.');
        }

        $destructionLog->load(['archive.department', 'proposedBy', 'approvedBy', 'departmentApprovedBy', 'archive.location']);

        ActivityLogger::log(
            'DESTRUCTION_BAP_PRINT',
            "Mencetak Berita Acara Pemusnahan (BAP) No: {$destructionLog->bap_number} untuk arsip '{$destructionLog->archive->title}'",
            'PEMUSNAHAN_RETENSI',
            [
                'bap_number' => $destructionLog->bap_number,
                'archive_id' => $destructionLog->archive_id,
            ],
            $destructionLog->bap_number
        );

        return view('destructions.bap', compact('destructionLog'));
    }

    /**
     * Upload / Perbarui Berkas Sertifikat BAP Resmi setelah Pemusnahan Selesai
     */
    public function uploadBap(DestructionLog $destructionLog, Request $request)
    {
        $user = auth()->user();
        if (!$user->isPicGudang() && !$user->isSuperAdmin()) {
            abort(403, 'Hanya PIC Gudang atau Super Admin yang berwenang mengunggah berkas sertifikat BAP resmi.');
        }

        $request->validate([
            'certificate_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:500',
        ], [
            'certificate_file.required' => 'Wajib memilih file sertifikat BAP resmi.',
            'certificate_file.max' => 'Ukuran file sertifikat BAP maksimal 500 KB.',
            'certificate_file.mimes' => 'Format file sertifikat BAP harus berupa PDF, JPG, JPEG, atau PNG.',
        ]);

        $certPath = $request->file('certificate_file')->store('bap_certificates', 'public');

        $destructionLog->update([
            'certificate_file' => $certPath,
        ]);

        $archive = $destructionLog->archive;

        ActivityLogger::log(
            'DESTRUCTION_BAP_UPLOADED',
            "Berkas fisik sertifikat BAP resmi (No: {$destructionLog->bap_number}) untuk arsip '" . ($archive->title ?? '-') . "' berhasil diunggah oleh {$user->name}.",
            'PEMUSNAHAN_RETENSI',
            [
                'destruction_log_id' => $destructionLog->id,
                'archive_id' => $destructionLog->archive_id,
                'bap_number' => $destructionLog->bap_number,
                'certificate_file' => $certPath,
            ],
            $destructionLog->bap_number
        );

        return redirect()->back()
            ->with('active_tab', 'destructions')
            ->with('success', "Berkas sertifikat BAP resmi (No: {$destructionLog->bap_number}) berhasil diunggah. Departemen kini dapat mengunduh dan mencetak dokumen BAP.");
    }
}


