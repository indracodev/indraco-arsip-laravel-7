<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\DestructionLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DestructionController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Expired or expiring soon archives
        $expiredArchives = Archive::whereNotNull('retention_expiry_date')
            ->where('status', '!=', 'destroyed')
            ->when($user->isPicDept(), fn($q) => $q->where('department_id', $user->department_id))
            ->with(['department', 'location.warehouse'])
            ->orderBy('retention_expiry_date', 'asc')
            ->get();

        // Destruction Logs history
        $destructionLogs = DestructionLog::with(['archive.department', 'proposedBy', 'approvedBy'])
            ->latest()
            ->paginate(10);

        return view('destructions.index', compact('expiredArchives', 'destructionLogs'));
    }

    public function proposeForm(Archive $archive)
    {
        $autoBapNumber = 'BAP/IND/' . date('Y') . '/' . str_pad($archive->id, 5, '0', STR_PAD_LEFT);
        return view('destructions.propose', compact('archive', 'autoBapNumber'));
    }

    public function propose(Request $request, Archive $archive)
    {
        $user = auth()->user();

        if (!$user->isPicGudang() && !$user->isSuperAdmin() && !$user->isPicDept()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'bap_number' => 'required|string|max:100',
            'destruction_date' => 'required|date',
            'method' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'scan_approval_destruction' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $certPath = null;
        if ($request->hasFile('certificate_file')) {
            $certPath = $request->file('certificate_file')->store('bap_certificates', 'public');
        }

        $scanApprovalPath = null;
        if ($request->hasFile('scan_approval_destruction')) {
            $scanApprovalPath = $request->file('scan_approval_destruction')->store('destruction_scans', 'public');
        }

        $archive->update([
            'status' => 'destroyed',
        ]);

        // Decrement capacity count of warehouse location if assigned
        if ($archive->warehouse_location_id) {
            $archive->location()->decrement('current_box_count');
        }

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
            'scan_approval_destruction' => $scanApprovalPath,
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('destructions.index')
            ->with('success', "Proses pemusnahan berkas ({$archive->title}) telah disahkan dengan No. BAP {$validated['bap_number']}.");
    }

    public function showBap(DestructionLog $destructionLog)
    {
        $destructionLog->load(['archive.department', 'proposedBy', 'approvedBy', 'departmentApprovedBy', 'archive.location']);
        return view('destructions.bap', compact('destructionLog'));
    }

    public function extendForm(Archive $archive)
    {
        return view('destructions.extend', compact('archive'));
    }

    public function extendStore(Request $request, Archive $archive)
    {
        $validated = $request->validate([
            'additional_years' => 'required|integer|min:1|max:5',
            'extension_reason' => 'required|string|max:1000',
            'scan_extension_form' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $scanExtensionPath = $archive->scan_extension_form;
        if ($request->hasFile('scan_extension_form')) {
            $scanExtensionPath = $request->file('scan_extension_form')->store('archive_extensions', 'public');
        }

        $newRetentionYears = min(5, $archive->retention_years + (int)$validated['additional_years']);
        $endDate = Carbon::parse($archive->period_end_date);
        $newExpiryDate = $endDate->copy()->addYears($newRetentionYears);

        $archive->update([
            'retention_years' => $newRetentionYears,
            'retention_expiry_date' => $newExpiryDate,
            'extension_reason' => $validated['extension_reason'],
            'scan_extension_form' => $scanExtensionPath,
            'status' => $archive->status === 'pending_destruction' ? 'in_warehouse' : $archive->status,
        ]);

        return redirect()->route('destructions.index')
            ->with('success', "Masa simpan berkas '{$archive->title}' berhasil diperpanjang (+{$validated['additional_years']} tahun). Expiry baru: {$newExpiryDate->format('d M Y')}.");
    }

    public function extendPrint(Archive $archive)
    {
        $archive->load(['department', 'location.warehouse', 'creator']);
        return view('destructions.print_extension', compact('archive'));
    }
}
