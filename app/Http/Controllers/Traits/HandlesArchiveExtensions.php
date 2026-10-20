<?php

namespace App\Http\Controllers\Traits;

use App\Models\Archive;
use App\Models\ArchiveExtensionLog;
use App\Models\Department;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait HandlesArchiveExtensions
{
    public function extendForm(Request $request, $archive = null)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            abort(403, 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk mengajukan perpanjangan masa simpan.');
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
        ])->whereNotIn('status', ['destroyed', 'draft']);

        if ($user->isPicDept()) {
            $archivesQuery->where('department_id', $user->department_id);
        }

        $archives = $archivesQuery->get();
        $archive = !empty($selectedArchiveIds) ? $archives->firstWhere('id', $selectedArchiveIds[0]) : null;
        $selectedArchiveId = $archive ? $archive->id : null;

        return view('destructions.extend', compact('archive', 'archives', 'selectedArchiveIds', 'selectedArchiveId'));
    }

    public function extendStore(Request $request, $archive = null)
    {
        $user = auth()->user();
        if ($user && $user->isPicGudang()) {
            abort(403, 'Akses Ditolak: PIC Gudang tidak memiliki hak untuk mengajukan perpanjangan masa simpan.');
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
                'archive_ids' => 'Minimal satu berkas arsip wajib dipilih untuk perpanjangan masa simpan.',
                'archive_id' => 'Berkas arsip wajib dipilih.',
            ]);
        }

        $isFat = false;
        if ($user) {
            $userDept = $user->department;
            if ($userDept && in_array(strtoupper($userDept->code), ['FAT', 'FIN', 'FATCLM'])) {
                $isFat = true;
            }
            $userName = strtoupper($user->name ?? '');
            if (str_contains($userName, 'FAT') || str_contains($userName, 'FIN')) {
                $isFat = true;
            }
        }
        if (!$isFat && !empty($archiveIds)) {
            $deptCodes = Department::whereIn('id', Archive::whereIn('id', $archiveIds)->pluck('department_id'))->pluck('code')->map(fn($c) => strtoupper($c))->toArray();
            foreach ($deptCodes as $c) {
                if (in_array($c, ['FAT', 'FIN', 'FATCLM'])) {
                    $isFat = true;
                    break;
                }
            }
        }
        $maxMonths = $isFat ? 120 : 60;
        $maxYears = $isFat ? 10 : 5;

        $validated = $request->validate([
            'additional_months' => "nullable|integer|min:1|max:{$maxMonths}",
            'additional_years' => "nullable|integer|min:1|max:{$maxYears}",
            'extension_reason' => 'required|string|max:1000',
            'scan_extension_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
        ], [
            'additional_months.max' => "Tambahan masa simpan maksimal {$maxMonths} bulan ({$maxYears} tahun).",
            'additional_years.max' => "Tambahan masa simpan maksimal {$maxYears} tahun ({$maxMonths} bulan).",
            'extension_reason.required' => 'Alasan perpanjangan masa simpan wajib diisi.',
            'scan_extension_form.max' => 'Ukuran file formulir perpanjangan maksimal 500 KB.',
            'scan_extension_form.mimes' => 'Format file formulir perpanjangan harus berupa PDF, JPG, JPEG, atau PNG.',
        ]);

        $months = 0;
        if ($request->filled('additional_months')) {
            $months = min((int)$request->input('additional_months'), $maxMonths);
        } elseif ($request->filled('additional_years')) {
            $months = min((int)$request->input('additional_years') * 12, $maxMonths);
        }

        if ($months < 1 || $months > $maxMonths) {
            return back()->withErrors(['additional_months' => "Tambahan masa simpan harus antara 1 sampai {$maxMonths} bulan."])->withInput();
        }

        $addedYears = round($months / 12, 1);
        $durationLabel = ($months % 12 === 0 && $months > 0)
            ? ($months / 12 . ' Tahun')
            : ($months . ' Bulan');

        $archives = Archive::whereIn('id', $archiveIds)->get();

        if ($user->isPicDept()) {
            foreach ($archives as $arc) {
                if ($arc->department_id !== $user->department_id) {
                    abort(403, 'Anda hanya dapat memperpanjang masa simpan berkas milik departemen Anda.');
                }
            }
        }

        $scanExtensionPath = null;
        if ($request->hasFile('scan_extension_form')) {
            $scanExtensionPath = $request->file('scan_extension_form')->store('archive_extensions', 'public');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($archives, $months, $addedYears, $durationLabel, $validated, $scanExtensionPath, $user) {
            foreach ($archives as $arc) {
                $baseDate = $arc->retention_expiry_date 
                    ? Carbon::parse($arc->retention_expiry_date) 
                    : ($arc->period_end_date ? Carbon::parse($arc->period_end_date) : Carbon::now());

                $newExpiryDate = $baseDate->copy()->addMonths($months)->endOfMonth();
                $filePath = $scanExtensionPath ?: $arc->scan_extension_form;

                // 1. Simpan pengajuan ke ArchiveExtensionLog (Status Pending)
                ArchiveExtensionLog::create([
                    'archive_id' => $arc->id,
                    'proposed_by_user_id' => $user->id,
                    'additional_months' => $months,
                    'additional_years' => $addedYears,
                    'previous_expiry_date' => $baseDate->format('Y-m-d'),
                    'new_expiry_date' => $newExpiryDate->format('Y-m-d'),
                    'extension_reason' => $validated['extension_reason'],
                    'scan_extension_form' => $filePath,
                    'approval_status' => 'pending',
                ]);

                // 2. Tandai status arsip sebagai 'pending_extension'
                $arc->update([
                    'extension_reason' => $validated['extension_reason'],
                    'scan_extension_form' => $filePath,
                    'status' => 'pending_extension',
                ]);

                ActivityLogger::log(
                    'RETENTION_EXTEND_PROPOSED',
                    "Pengajuan perpanjangan masa retensi berkas '{$arc->title}' (Box: {$arc->box_number}) sebanyak +{$durationLabel} diajukan oleh {$user->name} dan menunggu verifikasi/ACC Gudang.",
                    'PEMUSNAHAN_RETENSI',
                    [
                        'archive_id' => $arc->id,
                        'additional_months' => $months,
                        'additional_years' => $addedYears,
                        'new_expiry_date' => $newExpiryDate->format('Y-m-d'),
                        'extension_reason' => $validated['extension_reason'],
                        'scan_extension_form' => $filePath,
                        'approval_status' => 'pending',
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

        return redirect()->route('destructions.index', $redirectParams)
            ->with('active_tab', 'extensions')
            ->with('success', "Pengajuan perpanjangan masa simpan {$count} box berkas ({$boxTitles}) sebanyak +{$durationLabel} berhasil dikirim. Menunggu pengecekan dokumen & persetujuan (ACC) dari Admin Gudang.");
    }

    /**
     * ACC / Persetujuan Perpanjangan Masa Simpan oleh Gudang / Super Admin
     */
    public function approveExtension(ArchiveExtensionLog $extensionLog, Request $request)
    {
        $user = auth()->user();
        if (!$user->isPicGudang() && !$user->isSuperAdmin()) {
            abort(403, 'Hanya PIC Gudang atau Super Admin yang berwenang menyetujui (ACC) perpanjangan masa simpan.');
        }

        $archive = $extensionLog->archive;
        if (!$archive) {
            return back()->with('error', 'Data arsip perpanjangan tidak ditemukan.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($extensionLog, $archive, $user) {
            $months = (int) $extensionLog->additional_months;
            $newExpiry = $extensionLog->new_expiry_date ? Carbon::parse($extensionLog->new_expiry_date) : now();

            // Hitung total masa simpan baru (akumulasi periode awal + perpanjangan)
            $currentPeriodStr = $archive->periode ?? $archive->periode_doc ?? '';
            $baseMonths = 0;
            if (preg_match('/^(\d+)\s*(?:bulan|bln)/i', trim($currentPeriodStr), $matches)) {
                $baseMonths = (int) $matches[1];
            } elseif (!empty($archive->masa_simpan_custom) && (int)$archive->masa_simpan_custom > 0) {
                $baseMonths = (int)$archive->masa_simpan_custom * 12;
            } elseif (isset($archive->retention_years) && (int)$archive->retention_years > 0) {
                $baseMonths = (int)$archive->retention_years * 12;
            } else {
                $baseMonths = 60; // 5 tahun default
            }

            $totalMonths = $baseMonths + $months;
            $newRetentionYears = (int) floor($totalMonths / 12);
            $newPeriode = ($totalMonths % 12 === 0 && $totalMonths >= 12)
                ? (($totalMonths / 12) . ' Tahun')
                : ($totalMonths . ' Bulan');

            $archive->update([
                'retention_expiry_date' => $newExpiry,
                'retention_years' => $newRetentionYears,
                'periode' => $newPeriode,
                'extension_reason' => $extensionLog->extension_reason,
                'scan_extension_form' => $extensionLog->scan_extension_form ?: $archive->scan_extension_form,
                'status' => 'in_warehouse', // Status kembali aktif normal di gudang
            ]);

            $extensionLog->update([
                'approval_status' => 'approved',
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
            ]);

            $durationLabel = ($months % 12 === 0 && $months > 0)
                ? ($months / 12 . ' Tahun')
                : ($months . ' Bulan');

            ActivityLogger::log(
                'RETENTION_EXTEND_APPROVED',
                "Perpanjangan masa retensi berkas '{$archive->title}' (Box: {$archive->box_number}) sebanyak +{$durationLabel} disetujui (ACC) oleh {$user->name}. Masa simpan baru: {$newExpiry->format('M Y')}.",
                'PEMUSNAHAN_RETENSI',
                [
                    'extension_log_id' => $extensionLog->id,
                    'archive_id' => $archive->id,
                    'additional_months' => $months,
                    'new_expiry_date' => $newExpiry->format('Y-m-d'),
                ],
                $archive->box_number ?: "ID: {$archive->id}"
            );
        });

        return redirect()->back()
            ->with('active_tab', 'extensions')
            ->with('success', "Pengajuan perpanjangan berkas '{$archive->title}' berhasil di-ACC oleh Gudang. Masa simpan resmi diperpanjang hingga " . ($extensionLog->new_expiry_date ? $extensionLog->new_expiry_date->format('M Y') : '') . " dan status berkas kembali aktif di gudang.");
    }

    /**
     * Penolakan Perpanjangan Masa Simpan oleh Gudang / Super Admin
     */
    public function rejectExtension(ArchiveExtensionLog $extensionLog, Request $request)
    {
        $user = auth()->user();
        if (!$user->isPicGudang() && !$user->isSuperAdmin()) {
            abort(403, 'Hanya PIC Gudang atau Super Admin yang berwenang menolak perpanjangan masa simpan.');
        }

        $archive = $extensionLog->archive;
        $reason = $request->input('rejection_note', 'Dokumen / surat permohonan perpanjangan tidak memenuhi syarat.');

        \Illuminate\Support\Facades\DB::transaction(function () use ($extensionLog, $archive, $user, $reason) {
            if ($archive && $archive->status === 'pending_extension') {
                $archive->update([
                    'status' => 'in_warehouse',
                ]);
            }

            $extensionLog->update([
                'approval_status' => 'rejected',
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
                'rejection_note' => $reason,
            ]);

            ActivityLogger::log(
                'RETENTION_EXTEND_REJECTED',
                "Pengajuan perpanjangan masa retensi berkas '{$archive->title}' ditolak oleh {$user->name}. Alasan: {$reason}",
                'PEMUSNAHAN_RETENSI',
                [
                    'extension_log_id' => $extensionLog->id,
                    'archive_id' => $archive ? $archive->id : null,
                    'rejection_note' => $reason,
                ],
                $archive ? $archive->box_number : '-'
            );
        });

        return redirect()->back()
            ->with('active_tab', 'extensions')
            ->with('info', "Pengajuan perpanjangan berkas ditolak. Status berkas kembali normal di gudang.");
    }

    public function extendPrint(Archive $archive)
    {
        $archive->load(['department', 'subDepartment', 'location.warehouse', 'creator']);
        return view('destructions.print_extension', compact('archive'));
    }
}
