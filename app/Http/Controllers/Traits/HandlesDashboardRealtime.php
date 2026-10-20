<?php

namespace App\Http\Controllers\Traits;

use App\Models\Archive;
use App\Services\SystemEventStream;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait HandlesDashboardRealtime
{
    public function realtimeCheck(Request $request)
    {
        $user = auth()->user();
        $lastId = (int) $request->get('last_id', 0);
        $sinceSeq = (int) $request->get('since_seq', 0);
        $initial = (bool) $request->get('initial', false);

        $latestArchive = Archive::latest('id')->first();
        $maxId = $latestArchive ? (int) $latestArchive->id : 0;
        $maxSeq = SystemEventStream::getLatestSeq();

        // 1. Ultra-fast idle check: if client has latest sequence and not initial load, return sub-ms response
        if (!$initial && $sinceSeq > 0 && $sinceSeq >= $maxSeq) {
            return response()->json([
                'has_new' => false,
                'has_changes' => false,
                'latest_id' => max($maxId, $lastId),
                'latest_seq' => $maxSeq,
                'events' => [],
                'new_archives' => [],
                'count' => 0,
                'stats' => null, // Omit stats on idle to eliminate 4 count queries
            ]);
        }

        // Base query for current user permissions
        $baseQuery = Archive::query();
        if ($user && $user->isPicDept()) {
            $baseQuery->where('department_id', $user->department_id);
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => Archive::where('status', 'pending_verification')
                ->when($user && $user->isPicDept(), function ($q) use ($user) {
                    return $q->where('department_id', $user->department_id);
                })->count(),
            'in_warehouse' => (clone $baseQuery)->where('status', 'in_warehouse')->count(),
            'borrowed' => (clone $baseQuery)->where('status', 'borrowed')->count(),
        ];

        // Dynamic Badge Counts for Sidebar / Tabs
        $isPicGudangOrAdmin = $user && ($user->isPicGudang() || $user->isSuperAdmin());
        if ($isPicGudangOrAdmin) {
            $borrowingsBadge = \App\Models\BorrowingLog::whereIn('status', ['requested', 'dept_approved', 'approved'])->count();
            $submissionsBadge = Archive::where('status', 'pending_verification')->count();
        } else {
            $deptId = $user ? $user->department_id : 0;
            $borrowingsBadge = \App\Models\BorrowingLog::where(function ($q) use ($user, $deptId) {
                $q->where('borrower_user_id', $user ? $user->id : 0)
                  ->orWhereHas('archive', function ($q2) use ($deptId) {
                      $q2->where('department_id', $deptId);
                  });
            })->whereIn('status', ['requested', 'approved'])->count();

            $submissionsBadge = Archive::where('department_id', $deptId)
                ->where('status', 'pending_verification')
                ->count();
        }
        $destructionsBadge = Archive::getForm3PendingCount($user);

        $badges = [
            'borrowings' => (int) $borrowingsBadge,
            'submissions' => (int) $submissionsBadge,
            'destructions' => (int) $destructionsBadge,
        ];

        // 2. Initial load baseline
        if ($initial || ($lastId <= 0 && $sinceSeq <= 0)) {
            return response()->json([
                'has_new' => false,
                'has_changes' => false,
                'latest_id' => $maxId,
                'latest_seq' => $maxSeq,
                'events' => [],
                'new_archives' => [],
                'count' => 0,
                'stats' => $stats,
                'badges' => $badges,
            ]);
        }

        // 3. Query events since last seen sequence
        $eventsQuery = DB::table('system_events')
            ->where('id', '>', $sinceSeq);

        if ($user && $user->isPicDept()) {
            $eventsQuery->where(function ($q) use ($user) {
                $q->whereNull('department_id')
                  ->orWhere('department_id', $user->department_id);
            });
        }

        $recentEvents = $eventsQuery->orderBy('id', 'asc')->limit(50)->get();
        $hasChanges = $recentEvents->isNotEmpty();

        // Query new archives created after lastId (EXCLUDE drafts from incoming alerts)
        $newArchivesQuery = Archive::with(['department', 'creator', 'location', 'subDepartment'])
            ->where('id', '>', $lastId)
            ->where('status', '!=', 'draft');

        if ($user && $user->isPicDept()) {
            $newArchivesQuery->where('department_id', $user->department_id);
        }

        $newArchives = $newArchivesQuery->orderBy('id', 'desc')->take(10)->get();

        // Also detect archives promoted from draft to pending_verification from recent event stream
        $submittedEntityIds = $recentEvents->where('event_type', 'archive_submitted')
            ->pluck('entity_id')
            ->filter()
            ->unique()
            ->all();

        $submittedArchives = collect();
        if (!empty($submittedEntityIds)) {
            $submittedArchivesQuery = Archive::with(['department', 'creator', 'location', 'subDepartment'])
                ->whereIn('id', $submittedEntityIds)
                ->where('status', '!=', 'draft');

            if ($user && $user->isPicDept()) {
                $submittedArchivesQuery->where('department_id', $user->department_id);
            }
            $submittedArchives = $submittedArchivesQuery->get();
        }

        $mergedArchives = $newArchives->concat($submittedArchives)->unique('id');
        $hasNew = $mergedArchives->isNotEmpty();

        $items = $mergedArchives->map(function ($archive) {
            return [
                'id' => $archive->id,
                'title' => $archive->title,
                'box_number' => $archive->box_number ?? 'Penomoran Pending',
                'department_id' => $archive->department_id,
                'dept_name' => $archive->department->name ?? 'Semua Dept',
                'dept_code' => $archive->department->code ?? 'GEN',
                'sub_department_id' => $archive->sub_department_id,
                'sub_dept_name' => $archive->subDepartment->name ?? null,
                'document_type' => $archive->document_type,
                'created_by_user_id' => (int) $archive->created_by_user_id,
                'creator_name' => $archive->creator->name ?? 'User Client',
                'creator_role' => $archive->creator->role_label ?? 'Client',
                'periode_doc' => $archive->periode_doc ?? $archive->period_text ?? '-',
                'location' => $archive->display_location,
                'retention_expiry' => $archive->retention_expiry_date ? Carbon::parse($archive->retention_expiry_date)->format('M Y') : '-',
                'has_files' => (bool) ($archive->scan_input_form || $archive->file_path || $archive->scan_approval_input || $archive->scan_extension_form),
                'status' => $archive->status,
                'status_label' => $this->getStatusLabel($archive->status),
                'created_at_human' => $archive->created_at ? $archive->created_at->diffForHumans() : 'Baru saja',
                'created_at_time' => $archive->created_at ? $archive->created_at->format('H:i:s') : '',
                'url' => route('archives.show', $archive->id) . (request()->has('embed') ? '?embed=1' : ''),
            ];
        })->values();

        return response()->json([
            'has_new' => $hasNew,
            'has_changes' => $hasChanges || $hasNew,
            'latest_id' => max($maxId, $lastId),
            'latest_seq' => $maxSeq,
            'events' => $recentEvents,
            'new_archives' => $items,
            'count' => $newArchives->count(),
            'stats' => $stats,
            'badges' => $badges,
        ]);
    }

    private function getStatusLabel($status)
    {
        switch ($status) {
            case 'draft':
                return 'Draft';
            case 'pending_verification':
                return 'Antrean Verifikasi';
            case 'approved_booked':
                return 'Approved / Booking';
            case 'in_warehouse':
                return 'Di Gudang';
            case 'borrowed':
                return 'Dipinjam';
            case 'destroyed':
                return 'Dimusnahkan';
            default:
                return ucfirst($status);
        }
    }

    public function apiExpiringList(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        $today = Carbon::today();

        $archives = Archive::with(['department', 'subDepartment', 'location.warehouse', 'rackSlot'])
            ->whereNotIn('status', ['destroyed', 'draft'])
            ->whereNotNull('retention_expiry_date')
            ->when($user->isPicDept(), function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
            ->get();

        $expiringItems = [];

        foreach ($archives as $arc) {
            $expiryEndOfMonth = Carbon::parse($arc->retention_expiry_date)->endOfMonth()->endOfDay();
            $h30Threshold = $expiryEndOfMonth->copy()->subDays(30)->startOfDay();

            if ($today->gte($h30Threshold)) {
                $isExpired = $today->gt($expiryEndOfMonth);
                $daysDiff = $today->diffInDays($expiryEndOfMonth, false);

                $expiringItems[] = [
                    'id' => $arc->id,
                    'box_number' => $arc->box_number ?: 'Belum Ada No. Box',
                    'title' => $arc->effective_title ?? $arc->title,
                    'dept_code' => $arc->department->code ?? 'GEN',
                    'dept_name' => $arc->department->name ?? '',
                    'expiry_date' => Carbon::parse($arc->retention_expiry_date)->format('d M Y'),
                    'expiry_month' => Carbon::parse($arc->retention_expiry_date)->format('M Y'),
                    'is_expired' => $isExpired,
                    'days_diff' => abs($daysDiff),
                    'badge_text' => $isExpired ? 'LEWAT ' . abs($daysDiff) . ' HARI' : 'H-' . abs($daysDiff) . ' HARI',
                    'badge_type' => $isExpired ? 'danger' : 'warning',
                    'location' => $arc->display_location ?: 'Gudang Utama',
                ];
            }
        }

        usort($expiringItems, function ($a, $b) {
            if ($a['is_expired'] && !$b['is_expired']) return -1;
            if (!$a['is_expired'] && $b['is_expired']) return 1;
            return $b['days_diff'] <=> $a['days_diff'];
        });

        return response()->json([
            'count' => count($expiringItems),
            'items' => $expiringItems,
        ]);
    }
}
