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
            ->when($user->isPicDept(), function ($q) use ($user) {
                return $q->where('department_id', $user->department_id);
            })
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
        $deptId = $request->get('department_id');
        $subDeptId = $request->get('sub_department_id');
        $periodFilter = trim($request->get('period', ''));

        // Prepare filter metadata (Departments with Sub-Departments)
        $departmentsQuery = Department::with(['subDepartments' => function ($sq) {
            $sq->where('is_active', true)->orderBy('name');
        }])->where('is_active', true)->orderBy('name');

        if ($user->isPicDept()) {
            $departmentsQuery->where('id', $user->department_id);
            $deptId = $user->department_id; // lock for PIC Dept
        }

        $filterDepartments = $departmentsQuery->get()->map(function ($d) {
            return [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'sub_departments' => $d->subDepartments->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'code' => $s->code,
                        'name' => $s->name,
                    ];
                })
            ];
        });

        // Available distinct periods from database
        $availablePeriods = Archive::when($user->isPicDept(), function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            })
            ->whereNotNull('periode_doc')
            ->latest()
            ->take(30)
            ->pluck('periode_doc')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $hasFilter = !empty($search) || !empty($deptId && !$user->isPicDept()) || !empty($subDeptId) || !empty($periodFilter);

        if (!$hasFilter && empty($search)) {
            // Get recent archives with items or standalone as default suggestions
            $recentArchives = Archive::with(['department', 'subDepartment', 'location.warehouse', 'rackSlot', 'items'])
                ->when($user->isPicDept(), function ($q) use ($user) {
                    $q->where('department_id', $user->department_id);
                })
                ->latest()
                ->take(10)
                ->get();

            $recent = [];
            foreach ($recentArchives as $archive) {
                if ($archive->items && $archive->items->count() > 0) {
                    foreach ($archive->items as $item) {
                        $recent[] = [
                            'id' => 'item_' . $item->id,
                            'item_id' => $item->id,
                            'archive_id' => $archive->id,
                            'item_number' => $item->item_number,
                            'title' => $item->document_name ?: $archive->title,
                            'document_name' => $item->document_name ?: $archive->title,
                            'archive_title' => $archive->title,
                            'box_number' => $archive->box_number ?? 'Penomoran Pending',
                            'periode_doc' => $item->period_text ?? ($archive->periode_doc ?? '-'),
                            'dept_code' => $archive->department->code ?? 'GEN',
                            'dept_name' => $archive->department->name ?? '',
                            'sub_dept' => $archive->subDepartment ? $archive->subDepartment->name : null,
                            'location' => $archive->location ? $archive->location->full_location : 'Belum Dialokasikan',
                            'rack_code' => $archive->location ? $archive->location->rack_code : '-',
                            'slot_code' => $archive->rackSlot ? $archive->rackSlot->slot_code : '-',
                            'content_description' => $item->notes ?? ($archive->content_description ? \Illuminate\Support\Str::limit($archive->content_description, 80) : null),
                            'status' => $archive->status ?? 'in_warehouse',
                            'is_expired' => $archive->is_expired ?? false,
                            'status_label' => $this->getStatusLabel($archive->status ?? 'in_warehouse'),
                            'url' => route('archives.show', $archive->id),
                        ];
                    }
                } else {
                    $recent[] = [
                        'id' => 'arch_' . $archive->id,
                        'item_id' => null,
                        'archive_id' => $archive->id,
                        'item_number' => 1,
                        'title' => $archive->title,
                        'document_name' => $archive->title,
                        'archive_title' => $archive->title,
                        'box_number' => $archive->box_number ?? 'Penomoran Pending',
                        'periode_doc' => $archive->periode_doc ?? '-',
                        'dept_code' => $archive->department->code ?? 'GEN',
                        'dept_name' => $archive->department->name ?? '',
                        'sub_dept' => $archive->subDepartment ? $archive->subDepartment->name : null,
                        'location' => $archive->location ? $archive->location->full_location : 'Belum Dialokasikan',
                        'rack_code' => $archive->location ? $archive->location->rack_code : '-',
                        'slot_code' => $archive->rackSlot ? $archive->rackSlot->slot_code : '-',
                        'content_description' => $archive->content_description ? \Illuminate\Support\Str::limit($archive->content_description, 80) : null,
                        'status' => $archive->status ?? 'in_warehouse',
                        'is_expired' => $archive->is_expired ?? false,
                        'status_label' => $this->getStatusLabel($archive->status ?? 'in_warehouse'),
                        'url' => route('archives.show', $archive->id),
                    ];
                }
            }

            // Extract smart keywords from active archive items, departments & periods
            $deptKeywords = $user->isPicDept() && $user->department 
                ? [$user->department->name, $user->department->code] 
                : Department::pluck('code')->take(5)->toArray();

            $itemKeywords = \App\Models\ArchiveItem::latest()->take(15)->pluck('document_name')->filter()->unique()->take(8)->toArray();
            $recentPeriods = Archive::whereNotNull('periode_doc')->latest()->take(10)->pluck('periode_doc')->unique()->take(4)->toArray();

            $suggestedKeywords = array_unique(array_filter(array_merge(
                $deptKeywords,
                $itemKeywords,
                $recentPeriods,
                ['Faktur Pajak', 'Maintenance', 'Laporan Keuangan', 'Surat Perjanjian', 'Bukti Kas']
            )));

            return response()->json([
                'type' => 'suggestions',
                'keywords' => array_values($suggestedKeywords),
                'recent' => array_slice($recent, 0, 15),
                'departments' => $filterDepartments,
                'periods' => $availablePeriods
            ]);
        }

        // Filter and search archives with matching criteria
        $archives = Archive::with(['department', 'subDepartment', 'location.warehouse', 'rackSlot', 'items'])
            ->when($user->isPicDept(), function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            })
            ->when($deptId, function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            })
            ->when($subDeptId, function ($q) use ($subDeptId) {
                $q->where('sub_department_id', $subDeptId);
            })
            ->when($periodFilter, function ($q) use ($periodFilter) {
                $q->where(function ($pq) use ($periodFilter) {
                    $pq->where('periode_doc', 'like', "%{$periodFilter}%")
                       ->orWhere('period_text', 'like', "%{$periodFilter}%")
                       ->orWhereHas('items', function ($iq) use ($periodFilter) {
                           $iq->where('period_text', 'like', "%{$periodFilter}%");
                       });
                });
            })
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%")
                      ->orWhere('box_number', 'like', "%{$search}%")
                      ->orWhere('custom_doc_name', 'like', "%{$search}%")
                      ->orWhere('periode_doc', 'like', "%{$search}%")
                      ->orWhere('content_description', 'like', "%{$search}%")
                      ->orWhereHas('items', function ($iq) use ($search) {
                          $iq->where('document_name', 'like', "%{$search}%")
                             ->orWhere('period_text', 'like', "%{$search}%")
                             ->orWhere('notes', 'like', "%{$search}%");
                      })
                      ->orWhereHas('department', function ($dq) use ($search) {
                          $dq->where('name', 'like', "%{$search}%")
                             ->orWhere('code', 'like', "%{$search}%");
                      })
                      ->orWhereHas('subDepartment', function ($sdq) use ($search) {
                          $sdq->where('name', 'like', "%{$search}%")
                             ->orWhere('code', 'like', "%{$search}%");
                      })
                      ->orWhereHas('location', function ($lq) use ($search) {
                          $lq->where('rack_code', 'like', "%{$search}%")
                             ->orWhere('room_sector', 'like', "%{$search}%");
                      });
                });
            })
            ->latest()
            ->take(30)
            ->get();

        $results = [];
        foreach ($archives as $archive) {
            $matchingItems = $archive->items->filter(function ($item) use ($search, $periodFilter) {
                $matchSearch = empty($search) || (
                    stripos($item->document_name, $search) !== false
                    || stripos($item->period_text, $search) !== false
                    || stripos($item->notes, $search) !== false
                );
                $matchPeriod = empty($periodFilter) || (
                    stripos($item->period_text, $periodFilter) !== false
                );
                return $matchSearch && $matchPeriod;
            });

            if ($matchingItems->count() > 0) {
                foreach ($matchingItems as $item) {
                    $results[] = [
                        'id' => 'item_' . $item->id,
                        'item_id' => $item->id,
                        'archive_id' => $archive->id,
                        'item_number' => $item->item_number,
                        'title' => $item->document_name ?: $archive->title,
                        'document_name' => $item->document_name ?: $archive->title,
                        'archive_title' => $archive->title,
                        'box_number' => $archive->box_number ?? 'Penomoran Pending',
                        'periode_doc' => $item->period_text ?? ($archive->periode_doc ?? '-'),
                        'dept_code' => $archive->department->code ?? 'GEN',
                        'dept_name' => $archive->department->name ?? '',
                        'sub_dept' => $archive->subDepartment ? $archive->subDepartment->name : null,
                        'location' => $archive->location ? $archive->location->full_location : 'Belum Dialokasikan',
                        'rack_code' => $archive->location ? $archive->location->rack_code : '-',
                        'slot_code' => $archive->rackSlot ? $archive->rackSlot->slot_code : '-',
                        'content_description' => $item->notes ?? ($archive->content_description ? \Illuminate\Support\Str::limit($archive->content_description, 100) : null),
                        'status' => $archive->status ?? 'in_warehouse',
                        'is_expired' => $archive->is_expired ?? false,
                        'status_label' => $this->getStatusLabel($archive->status ?? 'in_warehouse'),
                        'url' => route('archives.show', $archive->id),
                    ];
                }
            } else {
                $results[] = [
                    'id' => 'arch_' . $archive->id,
                    'item_id' => null,
                    'archive_id' => $archive->id,
                    'item_number' => 1,
                    'title' => $archive->title,
                    'document_name' => $archive->title,
                    'archive_title' => $archive->title,
                    'box_number' => $archive->box_number ?? 'Penomoran Pending',
                    'periode_doc' => $archive->periode_doc ?? '-',
                    'dept_code' => $archive->department->code ?? 'GEN',
                    'dept_name' => $archive->department->name ?? '',
                    'sub_dept' => $archive->subDepartment ? $archive->subDepartment->name : null,
                    'location' => $archive->location ? $archive->location->full_location : 'Belum Dialokasikan',
                    'rack_code' => $archive->location ? $archive->location->rack_code : '-',
                    'slot_code' => $archive->rackSlot ? $archive->rackSlot->slot_code : '-',
                    'content_description' => $archive->content_description ? \Illuminate\Support\Str::limit($archive->content_description, 100) : null,
                    'status' => $archive->status ?? 'in_warehouse',
                    'is_expired' => $archive->is_expired ?? false,
                    'status_label' => $this->getStatusLabel($archive->status ?? 'in_warehouse'),
                    'url' => route('archives.show', $archive->id),
                ];
            }
        }

        return response()->json([
            'type' => 'results',
            'items' => array_slice($results, 0, 30),
            'departments' => $filterDepartments,
            'periods' => $availablePeriods
        ]);
    }

    public function realtimeCheck(Request $request)
    {
        $user = auth()->user();
        $lastId = (int) $request->get('last_id', 0);
        $initial = (bool) $request->get('initial', false);

        $latestArchive = Archive::latest('id')->first();
        $maxId = $latestArchive ? $latestArchive->id : 0;

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

        // If initial load or lastId is 0 or not provided, return the baseline without triggering alert
        if ($initial || $lastId <= 0) {
            return response()->json([
                'has_new' => false,
                'latest_id' => $maxId,
                'new_archives' => [],
                'count' => 0,
                'stats' => $stats,
            ]);
        }

        // Query new archives created after lastId
        $newArchivesQuery = Archive::with(['department', 'creator', 'location', 'subDepartment'])
            ->where('id', '>', $lastId);

        if ($user && $user->isPicDept()) {
            $newArchivesQuery->where('department_id', $user->department_id);
        }

        $newArchives = $newArchivesQuery->orderBy('id', 'desc')->take(10)->get();
        $hasNew = $newArchives->isNotEmpty();

        $items = $newArchives->map(function ($archive) {
            return [
                'id' => $archive->id,
                'title' => $archive->title,
                'box_number' => $archive->box_number ?? 'Penomoran Pending',
                'dept_name' => $archive->department->name ?? 'Semua Dept',
                'dept_code' => $archive->department->code ?? 'GEN',
                'creator_name' => $archive->creator->name ?? 'User Client',
                'creator_role' => $archive->creator->role_label ?? 'Client',
                'periode_doc' => $archive->periode_doc ?? $archive->period_text ?? '-',
                'location' => $archive->location ? $archive->location->full_location : 'Belum Dialokasikan',
                'status' => $archive->status,
                'status_label' => $this->getStatusLabel($archive->status),
                'created_at_human' => $archive->created_at ? $archive->created_at->diffForHumans() : 'Baru saja',
                'created_at_time' => $archive->created_at ? $archive->created_at->format('H:i:s') : '',
                'url' => route('archives.show', $archive->id) . '?embed=1',
            ];
        });

        return response()->json([
            'has_new' => $hasNew,
            'latest_id' => max($maxId, $lastId),
            'new_archives' => $items,
            'count' => $newArchives->count(),
            'stats' => $stats,
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
}
