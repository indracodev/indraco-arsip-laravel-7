<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\WarehouseLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Base Query
        $archivesQuery = Archive::query();

        if ($user->isPicDept()) {
            $archivesQuery->where('department_id', $user->department_id);
        }

        // Stats calculation
        $totalArchives = (clone $archivesQuery)->count();
        $inWarehouseCount = (clone $archivesQuery)->where('status', 'in_warehouse')->count();
        $pendingVerificationCount = Archive::where('status', 'pending_verification')
            ->when($user->isPicDept(), fn($q) => $q->where('department_id', $user->department_id))
            ->count();
        $borrowedCount = (clone $archivesQuery)->where('status', 'borrowed')->count();

        // Expiry alerts (archives nearing expiration within 90 days or passed)
        $expiringArchives = (clone $archivesQuery)
            ->whereNotNull('retention_expiry_date')
            ->where('status', '!=', 'destroyed')
            ->whereDate('retention_expiry_date', '<=', Carbon::now()->addDays(90))
            ->with(['department', 'location'])
            ->orderBy('retention_expiry_date', 'asc')
            ->get();

        $expiringCount = $expiringArchives->count();

        // Recent Activity / Pending Requests
        $pendingBookings = Archive::where('status', 'pending_verification')
            ->with(['department', 'creator'])
            ->latest()
            ->take(5)
            ->get();

        $pendingBorrowings = BorrowingLog::where('status', 'requested')
            ->with(['archive', 'borrower'])
            ->latest()
            ->take(5)
            ->get();

        $recentArchives = (clone $archivesQuery)
            ->with(['department', 'location', 'creator'])
            ->latest()
            ->take(6)
            ->get();

        // Warehouse capacity stats (for admin & pic gudang)
        $warehouseLocations = WarehouseLocation::with('warehouse')->get();
        $totalCapacity = $warehouseLocations->sum('box_capacity');
        $usedCapacity = $warehouseLocations->sum('current_box_count');
        $capacityPercent = $totalCapacity > 0 ? round(($usedCapacity / $totalCapacity) * 100, 1) : 0;

        // Department Breakdown chart data
        $deptBreakdown = Department::withCount('archives')->get();

        return view('dashboard.index', compact(
            'user',
            'totalArchives',
            'inWarehouseCount',
            'pendingVerificationCount',
            'borrowedCount',
            'expiringArchives',
            'expiringCount',
            'pendingBookings',
            'pendingBorrowings',
            'recentArchives',
            'warehouseLocations',
            'totalCapacity',
            'usedCapacity',
            'capacityPercent',
            'deptBreakdown'
        ));
    }

    public function searchApi(Request $request)
    {
        $user = auth()->user();
        $search = trim($request->get('q', ''));

        $query = Archive::with(['department', 'location.warehouse']);

        if ($user->isPicDept()) {
            $query->where('department_id', $user->department_id);
        }

        if (empty($search)) {
            // Get recent documents as default suggestions
            $recent = (clone $query)->latest()->take(5)->get()->map(function ($archive) {
                return [
                    'id' => $archive->id,
                    'title' => $archive->title,
                    'box_number' => $archive->box_number ?? 'Penomoran Pending',
                    'dept_code' => $archive->department->code ?? 'GEN',
                    'location' => $archive->location->full_location ?? 'Belum Ditentukan',
                    'status' => $archive->status,
                    'status_label' => match($archive->status) {
                        'draft' => 'Draft',
                        'pending_verification' => 'Antrean Verifikasi',
                        'approved_booked' => 'Approved / Booking',
                        'in_warehouse' => 'Di Gudang',
                        'borrowed' => 'Dipinjam',
                        'destroyed' => 'Dimusnahkan',
                        default => ucfirst($archive->status)
                    },
                    'url' => route('archives.show', $archive->id),
                ];
            });

            // Get dynamic keyword suggestions from departments & common archive topics
            $deptCodes = Department::pluck('code')->toArray();
            $suggestedKeywords = array_unique(array_merge(
                ['Laporan Pajak', 'Faktur Pembelian', 'Surat Perjanjian', 'Berkas HRD', 'Laporan Keuangan', 'Audit'],
                $deptCodes
            ));

            return response()->json([
                'type' => 'suggestions',
                'keywords' => array_values($suggestedKeywords),
                'recent' => $recent
            ]);
        }

        $archives = $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('box_number', 'like', "%{$search}%")
                  ->orWhere('period_text', 'like', "%{$search}%")
                  ->orWhere('content_description', 'like', "%{$search}%");
            })
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($archive) {
                return [
                    'id' => $archive->id,
                    'title' => $archive->title,
                    'box_number' => $archive->box_number ?? 'Penomoran Pending',
                    'dept_code' => $archive->department->code ?? 'GEN',
                    'location' => $archive->location->full_location ?? 'Belum Ditentukan',
                    'status' => $archive->status,
                    'status_label' => match($archive->status) {
                        'draft' => 'Draft',
                        'pending_verification' => 'Antrean Verifikasi',
                        'approved_booked' => 'Approved / Booking',
                        'in_warehouse' => 'Di Gudang',
                        'borrowed' => 'Dipinjam',
                        'destroyed' => 'Dimusnahkan',
                        default => ucfirst($archive->status)
                    },
                    'url' => route('archives.show', $archive->id),
                ];
            });

        return response()->json([
            'type' => 'results',
            'items' => $archives
        ]);
    }
}
