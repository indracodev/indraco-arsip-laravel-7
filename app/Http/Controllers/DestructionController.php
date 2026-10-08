<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\DestructionLog;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DestructionController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1. Data berkas H-30: dihitung dari akhir bulan bulan terakhir periode + masa retensi
        $archivesQuery = Archive::where('status', '!=', 'destroyed')
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

        // 2. Log Riwayat Perpanjangan (Tabel Perpanjangan - default kosong jika belum ada)
        $extensionLogsQuery = \App\Models\ActivityLog::where('action', 'RETENTION_EXTEND');
        if ($user->isPicDept()) {
            $extensionLogsQuery->where('department_id', $user->department_id);
        }
        $extensionLogs = $extensionLogsQuery->latest()->get()->map(function ($log) {
            $props = is_array($log->properties) ? $log->properties : (json_decode($log->properties, true) ?? []);
            $archive = null;
            if (!empty($props['archive_id'])) {
                $archive = Archive::with('department')->find($props['archive_id']);
            }
            return [
                'id' => $log->id,
                'box_number' => $log->reference_id ?? ($archive->box_number ?? '-'),
                'archive_title' => $archive ? ($archive->effective_title ?? $archive->title) : ($log->description),
                'department_code' => $archive && $archive->department ? $archive->department->code : ($log->user_role ?? '-'),
                'additional_years' => $props['additional_years'] ?? ($props['additional_months'] ? ($props['additional_months'] . ' Bulan') : '-'),
                'new_expiry_date' => !empty($props['new_expiry_date']) ? Carbon::parse($props['new_expiry_date'])->format('M Y') : '-',
                'reason' => $props['extension_reason'] ?? '-',
                'user_name' => $log->user_name ?? ($log->user ? $log->user->name : 'PIC'),
                'date' => $log->created_at ? $log->created_at->format('d M Y H:i') : '-',
                'scan_file' => !empty($props['scan_extension_form']) ? app_storage_url($props['scan_extension_form']) : ($archive && $archive->scan_extension_form ? app_storage_url($archive->scan_extension_form) : null),
                'scan_file_stream' => !empty($props['scan_extension_form']) ? app_preview_stream_url($props['scan_extension_form']) : ($archive && $archive->scan_extension_form ? app_preview_stream_url($archive->scan_extension_form) : null),
                'scan_file_raw' => !empty($props['scan_extension_form']) ? $props['scan_extension_form'] : ($archive && $archive->scan_extension_form ? $archive->scan_extension_form : null),
                'archive_id' => $props['archive_id'] ?? null,
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
                        'additional_years' => ($arc->retention_display ?: ($arc->retention_years ? $arc->retention_years . ' Tahun' : '-')),
                        'new_expiry_date' => $arc->retention_expiry_date ? Carbon::parse($arc->retention_expiry_date)->format('M Y') : '-',
                        'reason' => $arc->extension_reason ?: '-',
                        'user_name' => 'PIC Dept',
                        'date' => $arc->updated_at ? $arc->updated_at->format('d M Y H:i') : '-',
                        'scan_file' => $arc->scan_extension_form ? app_storage_url($arc->scan_extension_form) : null,
                        'scan_file_stream' => $arc->scan_extension_form ? app_preview_stream_url($arc->scan_extension_form) : null,
                        'scan_file_raw' => $arc->scan_extension_form,
                        'archive_id' => $arc->id,
                    ];
                });
            if ($extendedArchives->isNotEmpty()) {
                $extensionLogs = $extendedArchives;
            }
        }

        // 3. Log Riwayat Pemusnahan (Tabel Pemusnahan - default kosong jika belum ada)
        $destructionLogsQuery = DestructionLog::with(['archive.department', 'proposedBy', 'approvedBy']);
        if ($user->isPicDept()) {
            $destructionLogsQuery->whereHas('archive', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }
        $destructionLogs = $destructionLogsQuery->latest()->get()->map(function ($dLog) {
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
            ];
        });

        return view('destructions.index', compact('expiredArchives', 'extensionLogs', 'destructionLogs'));
    }

    public function proposeForm(Request $request, $archive = null)
    {
        $user = auth()->user();

        if (is_numeric($archive)) {
            $archive = Archive::find($archive);
        } elseif (!$archive && $request->filled('archive_id')) {
            $archive = Archive::find($request->archive_id);
        }

        // Fetch candidate archives for search autocomplete (status tersimpan di gudang / antrean pemusnahan)
        $archivesQuery = Archive::select(
            'id', 'archive_code', 'title', 'box_number', 'department_id',
            'sub_department_id', 'warehouse_location_id', 'warehouse_rack_slot_id',
            'retention_expiry_date', 'status', 'periode_doc', 'retention_years'
        )->with([
            'department:id,name,code',
            'subDepartment:id,name',
            'location:id,name,warehouse_id',
            'location.warehouse:id,name',
            'rackSlot:id,slot_name'
        ])->where('status', '!=', 'destroyed');

        if ($user->isPicDept()) {
            $archivesQuery->where('department_id', $user->department_id);
        }

        $archives = $archivesQuery->get();
        $selectedArchiveId = $archive ? $archive->id : ($request->query('archive_id') ?? old('archive_id'));
        $autoBapNumber = old('bap_number') ?: ('BAP/IND/' . date('Y') . '/' . str_pad($selectedArchiveId ?: rand(100, 999), 5, '0', STR_PAD_LEFT));

        return view('destructions.propose', compact('archive', 'archives', 'selectedArchiveId', 'autoBapNumber'));
    }

    public function propose(Request $request, $archive = null)
    {
        $user = auth()->user();

        if (!$user->isPicGudang() && !$user->isSuperAdmin() && !$user->isPicDept()) {
            abort(403, 'Akses ditolak.');
        }

        if ($archive instanceof Archive) {
            // Already resolved
        } elseif (is_numeric($archive)) {
            $archive = Archive::findOrFail($archive);
        } elseif (!$archive && $request->filled('archive_id')) {
            $archive = Archive::findOrFail($request->archive_id);
        } elseif (!$archive) {
            $validatedArchive = $request->validate([
                'archive_id' => 'required|exists:archives,id',
            ], [
                'archive_id.required' => 'Pilih berkas arsip yang akan dimusnahkan terlebih dahulu.',
                'archive_id.exists' => 'Berkas arsip yang dipilih tidak valid atau tidak ditemukan.',
            ]);
            $archive = Archive::findOrFail($validatedArchive['archive_id']);
        }

        if ($user->isPicDept() && $archive->department_id !== $user->department_id) {
            abort(403, 'Anda hanya dapat mengajukan pemusnahan berkas milik departemen Anda.');
        }

        $validated = $request->validate([
            'bap_number' => 'required|string|max:100',
            'destruction_date' => 'required|date',
            'method' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'approval_file' => 'required_without:scan_approval_destruction|nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'scan_approval_destruction' => 'required_without:approval_file|nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'bap_number.required' => 'Nomor BAP wajib diisi.',
            'destruction_date.required' => 'Tanggal pelaksanaan pemusnahan wajib diisi.',
            'method.required' => 'Metode fisik pemusnahan wajib dipilih.',
            'approval_file.required_without' => 'Wajib mengunggah berkas surat persetujuan (approval) pemusnahan.',
            'approval_file.max' => 'Ukuran file surat persetujuan maksimal 2 MB.',
            'scan_approval_destruction.max' => 'Ukuran file persetujuan maksimal 2 MB.',
            'certificate_file.max' => 'Ukuran file sertifikat BAP maksimal 2 MB.',
        ]);

        $certPath = null;
        if ($request->hasFile('certificate_file')) {
            $certPath = $request->file('certificate_file')->store('bap_certificates', 'public');
        }

        $scanApprovalPath = null;
        if ($request->hasFile('approval_file')) {
            $scanApprovalPath = $request->file('approval_file')->store('destruction_approvals', 'public');
        } elseif ($request->hasFile('scan_approval_destruction')) {
            $scanApprovalPath = $request->file('scan_approval_destruction')->store('destruction_approvals', 'public');
        } elseif ($archive->scan_approval_destruction) {
            $scanApprovalPath = $archive->scan_approval_destruction;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($archive, $user, $validated, $certPath, $scanApprovalPath) {
            // Release slot if mapped
            if ($archive->warehouse_rack_slot_id) {
                $slot = $archive->rackSlot;
                if ($slot) {
                    $slot->update([
                        'archive_id' => null,
                        'status' => 'empty',
                    ]);
                }
            }

            // Decrement capacity count of warehouse location if assigned
            if ($archive->warehouse_location_id) {
                $loc = $archive->location;
                if ($loc && $loc->current_box_count > 0) {
                    $loc->decrement('current_box_count');
                }
            }

            $archive->update([
                'status' => 'destroyed',
                'warehouse_location_id' => null,
                'warehouse_rack_slot_id' => null,
            ]);

            DestructionLog::create([
                'archive_id' => $archive->id,
                'proposed_by_user_id' => $user->id,
                'department_approval_by' => $user->id,
                'department_approved_at' => now(),
                'approved_by_dept_pic_id' => $user->id,
                'bap_number' => $validated['bap_number'],
                'destruction_date' => $validated['destruction_date'],
                'method' => $validated['method'],
                'certificate_file' => $certPath,
                'approval_file' => $scanApprovalPath,
                'scan_approval_destruction' => $scanApprovalPath,
                'is_approval_uploaded' => !empty($scanApprovalPath),
                'approval_status' => 'approved',
                'notes' => $validated['notes'],
            ]);

            ActivityLogger::log(
                'DESTRUCTION_PROPOSE',
                "Pemusnahan berkas kadaluwarsa '{$archive->title}' (Box: {$archive->box_number}) dengan No. BAP {$validated['bap_number']} via metode {$validated['method']}",
                'PEMUSNAHAN_RETENSI',
                [
                    'archive_id' => $archive->id,
                    'bap_number' => $validated['bap_number'],
                    'method' => $validated['method'],
                    'destruction_date' => $validated['destruction_date'],
                ],
                $validated['bap_number']
            );
        });

        $redirectParams = [];
        if ($request->filled('tab')) {
            $redirectParams['tab'] = $request->input('tab');
        }
        if ($request->filled('embed') || $request->has('embed')) {
            $redirectParams['embed'] = 1;
        }

        return redirect()->route('destructions.index', $redirectParams)
            ->with('active_tab', 'destructions')
            ->with('success', "Proses pemusnahan berkas ({$archive->title}) dengan lampiran persetujuan telah disahkan dengan No. BAP {$validated['bap_number']}.");
    }

    public function showBap(DestructionLog $destructionLog)
    {
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

    public function extendForm(Request $request, $archive = null)
    {
        $user = auth()->user();

        if (is_numeric($archive)) {
            $archive = Archive::find($archive);
        } elseif (!$archive && $request->filled('archive_id')) {
            $archive = Archive::find($request->archive_id);
        }

        // Fetch candidate archives for search autocomplete (status tersimpan di gudang / antrean pemusnahan)
        $archivesQuery = Archive::select(
            'id', 'archive_code', 'title', 'box_number', 'department_id',
            'sub_department_id', 'warehouse_location_id', 'warehouse_rack_slot_id',
            'retention_expiry_date', 'status', 'periode_doc', 'retention_years'
        )->with([
            'department:id,name,code',
            'subDepartment:id,name',
            'location:id,name,warehouse_id',
            'location.warehouse:id,name',
            'rackSlot:id,slot_name'
        ])->where('status', '!=', 'destroyed');

        if ($user->isPicDept()) {
            $archivesQuery->where('department_id', $user->department_id);
        }

        $archives = $archivesQuery->get();
        $selectedArchiveId = $archive ? $archive->id : ($request->query('archive_id') ?? old('archive_id'));

        return view('destructions.extend', compact('archive', 'archives', 'selectedArchiveId'));
    }

    public function extendStore(Request $request, $archive = null)
    {
        $user = auth()->user();

        if ($archive instanceof Archive) {
            // Already resolved
        } elseif (is_numeric($archive)) {
            $archive = Archive::findOrFail($archive);
        } elseif (!$archive && $request->filled('archive_id')) {
            $archive = Archive::findOrFail($request->archive_id);
        } elseif (!$archive) {
            $validatedArchive = $request->validate([
                'archive_id' => 'required|exists:archives,id',
            ], [
                'archive_id.required' => 'Pilih berkas arsip yang akan diperpanjang masa simpannya terlebih dahulu.',
                'archive_id.exists' => 'Berkas arsip yang dipilih tidak ditemukan dalam sistem.',
            ]);
            $archive = Archive::findOrFail($validatedArchive['archive_id']);
        }

        if ($user->isPicDept() && $archive->department_id !== $user->department_id) {
            abort(403, 'Anda hanya dapat memperpanjang masa simpan berkas milik departemen Anda.');
        }

        $validated = $request->validate([
            'additional_months' => 'nullable|integer|min:1|max:600',
            'additional_years' => 'nullable|integer|min:1|max:50',
            'extension_reason' => 'required|string|max:1000',
            'scan_extension_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'extension_reason.required' => 'Alasan perpanjangan masa simpan wajib diisi.',
            'scan_extension_form.max' => 'Ukuran file scan formulir perpanjangan maksimal 2 MB.',
        ]);

        $months = 0;
        if ($request->filled('additional_months')) {
            $months = (int)$request->input('additional_months');
        } elseif ($request->filled('additional_years')) {
            $months = (int)$request->input('additional_years') * 12;
        }

        if ($months < 1) {
            return back()->withErrors(['additional_months' => 'Tambahan masa simpan wajib diisi minimal 1 bulan atau 1 tahun.'])->withInput();
        }

        $addedYears = max(1, (int)round($months / 12));

        $scanExtensionPath = $archive->scan_extension_form;
        if ($request->hasFile('scan_extension_form')) {
            $scanExtensionPath = $request->file('scan_extension_form')->store('archive_extensions', 'public');
        }

        $newRetentionYears = ($archive->retention_years ?: 5) + $addedYears;
        $baseDate = $archive->retention_expiry_date 
            ? Carbon::parse($archive->retention_expiry_date) 
            : ($archive->period_end_date ? Carbon::parse($archive->period_end_date) : Carbon::now());
        
        $newExpiryDate = $baseDate->copy()->addMonths($months)->endOfMonth();

        \Illuminate\Support\Facades\DB::transaction(function () use ($archive, $newRetentionYears, $newExpiryDate, $validated, $scanExtensionPath, $months, $addedYears) {
            $archive->update([
                'retention_years' => $newRetentionYears,
                'retention_expiry_date' => $newExpiryDate,
                'extension_reason' => $validated['extension_reason'],
                'scan_extension_form' => $scanExtensionPath,
                'status' => $archive->status === 'pending_destruction' ? 'in_warehouse' : $archive->status,
            ]);

            ActivityLogger::log(
                'RETENTION_EXTEND',
                "Perpanjangan masa retensi berkas '{$archive->title}' (Box: {$archive->box_number}) sebanyak +{$months} bulan (+{$addedYears} tahun) hingga {$newExpiryDate->format('d M Y')}.",
                'PEMUSNAHAN_RETENSI',
                [
                    'archive_id' => $archive->id,
                    'additional_months' => $months,
                    'additional_years' => $addedYears,
                    'new_expiry_date' => $newExpiryDate->format('Y-m-d'),
                    'extension_reason' => $validated['extension_reason'],
                    'scan_extension_form' => $scanExtensionPath,
                ],
                $archive->box_number
            );
        });

        $redirectParams = [];
        if ($request->filled('tab')) {
            $redirectParams['tab'] = $request->input('tab');
        }
        if ($request->filled('embed') || $request->has('embed')) {
            $redirectParams['embed'] = 1;
        }

        return redirect()->route('destructions.index', $redirectParams)
            ->with('active_tab', 'extensions')
            ->with('success', "Masa simpan berkas '{$archive->title}' berhasil diperpanjang (+{$months} bulan / +{$addedYears} tahun). Expiry baru: {$newExpiryDate->format('d M Y')}.");
    }

    public function extendPrint(Archive $archive)
    {
        $archive->load(['department', 'subDepartment', 'location.warehouse', 'creator']);
        return view('destructions.print_extension', compact('archive'));
    }
}
