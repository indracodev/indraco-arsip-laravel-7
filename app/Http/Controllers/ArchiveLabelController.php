<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Services\ActivityLogger;
use App\Http\Controllers\Traits\FormatsArchiveLabels;
use Illuminate\Http\Request;

class ArchiveLabelController extends Controller
{
    use FormatsArchiveLabels;

    /**
     * Print single box sticker.
     */
    public function printSticker(Archive $archive, Request $request)
    {
        $user = auth()->user();
        if ($user->isPicDept() && (int)$archive->department_id !== (int)$user->department_id) {
            if ($request->wantsJson() || $request->has('json') || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Anda tidak memiliki akses ke label arsip departemen lain.'], 403);
            }
            abort(403, 'Anda tidak memiliki akses ke label arsip departemen lain.');
        }

        // Label box boleh dicetak untuk arsip yang sudah disetujui/booking, tersimpan di gudang, atau perpanjangan
        if (!in_array($archive->status, ['approved_booked', 'in_warehouse', 'pending_extension'])) {
            $msg = 'Label box hanya dapat dicetak untuk arsip yang telah disetujui (Approved / Booked), tersimpan di gudang, atau dalam perpanjangan (status saat ini: ' . $archive->status_label . ').';
            if ($request->wantsJson() || $request->has('json') || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $msg], 403);
            }
            if ($request->has('embed')) {
                return response("<div style='font-family: monospace; padding: 24px; text-align: center; color: #dc2626;'><strong>Peringatan:</strong> {$msg}</div>", 403);
            }
            return redirect()->route('archives.show', $archive)->with('error', $msg);
        }

        ActivityLogger::log(
            'ARCHIVE_PRINT',
            "Mencetak label / barcode box arsip '{$archive->box_number}' - {$archive->title}.",
            'DOKUMEN_ARSIP',
            [
                'archive_id' => $archive->id,
                'box_number' => $archive->box_number,
                'title' => $archive->title,
            ],
            $archive->box_number
        );

        $archive->load(['department', 'subDepartment', 'items', 'location.warehouse', 'rackSlot', 'creator']);
        $archives = collect([$archive]);

        if ($request->wantsJson() || $request->has('json') || $request->ajax()) {
            return response()->json([
                'success' => true,
                'labels' => [$this->formatArchiveForLabel($archive)]
            ]);
        }

        return view('archives.print_sticker', compact('archives', 'archive'));
    }

    /**
     * Batch print archive box labels.
     */
    public function printLabels(Request $request)
    {
        $user = auth()->user();
        $ids = $request->input('ids') ?: $request->input('archive_id');

        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        } elseif (is_numeric($ids)) {
            $ids = [(int)$ids];
        }

        $query = Archive::with(['department', 'subDepartment', 'items', 'location.warehouse', 'rackSlot', 'creator']);

        if ($user->isPicDept()) {
            $query->where('department_id', $user->department_id);
        }

        // Label box boleh dicetak untuk arsip disetujui/booking, di gudang, atau dalam perpanjangan
        $query->whereIn('status', ['approved_booked', 'in_warehouse', 'pending_extension']);

        if (!empty($ids) && is_array($ids)) {
            $query->whereIn('id', $ids);
        } else {
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('box_number', 'like', "%{$search}%")
                      ->orWhere('periode_doc', 'like', "%{$search}%")
                      ->orWhere('period_text', 'like', "%{$search}%");
                });
            }
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
        }

        $archives = $query->get();

        if ($archives->isEmpty()) {
            if ($request->wantsJson() || $request->has('json') || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tidak ada data arsip yang memenuhi syarat untuk dicetak label (label hanya untuk arsip yang disetujui, di gudang, atau perpanjangan).'
                ], 404);
            }
            if ($request->has('embed')) {
                return response("<div style='font-family: monospace; padding: 24px; text-align: center; color: #dc2626;'><strong>Peringatan:</strong> Tidak ada berkas arsip yang memenuhi syarat untuk dicetak label.<br><small style='color: #64748b;'>Label hanya dapat dicetak untuk arsip yang disetujui (Approved / Booked), tersimpan di gudang, atau dalam perpanjangan.</small></div>", 404);
            }
            return redirect()->route('archives.index')
                ->with('warning', 'Tidak ada data arsip yang memenuhi syarat untuk dicetak label.');
        }

        ActivityLogger::log(
            'ARCHIVE_PRINT',
            "Mencetak batch label box arsip sebanyak " . $archives->count() . " berkas.",
            'DOKUMEN_ARSIP',
            [
                'count' => $archives->count(),
                'archive_ids' => $archives->pluck('id')->toArray(),
            ],
            "Batch: {$archives->count()} Box"
        );

        if ($request->wantsJson() || $request->has('json') || $request->ajax()) {
            return response()->json([
                'success' => true,
                'labels' => $archives->map(function($arc) {
                    return $this->formatArchiveForLabel($arc);
                })->values()
            ]);
        }

        $archive = $archives->first();

        return view('archives.print_sticker', compact('archives', 'archive'));
    }
}
