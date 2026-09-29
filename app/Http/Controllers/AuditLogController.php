<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Models\User;
use App\Models\WarehouseEntryLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'activity'); // 'activity', 'entry', 'borrowing', 'destruction'
        $search = $request->query('search');
        $deptId = $request->query('department_id');
        $userId = $request->query('user_id');
        $module = $request->query('module');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $activityLogs = collect();
        $entryLogs = collect();
        $borrowingLogs = collect();
        $destructionLogs = collect();

        // 1. Activity Log (All system & user events)
        if ($tab === 'activity' || empty($tab)) {
            $query = ActivityLog::with(['user', 'department'])->latest();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('action', 'like', "%{$search}%")
                      ->orWhere('user_name', 'like', "%{$search}%")
                      ->orWhere('reference_id', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%");
                });
            }

            if ($module && $module !== 'all') {
                $query->where('module', $module);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            if ($deptId) {
                $query->where('department_id', $deptId);
            }

            if ($dateFrom) {
                $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
            }

            if ($dateTo) {
                $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
            }

            $activityLogs = $query->paginate(20)->withQueryString();
        }

        // 2. Warehouse Entry Logs
        elseif ($tab === 'entry') {
            $query = WarehouseEntryLog::with(['archive.department', 'picGudang', 'location.warehouse']);
            if ($search) {
                $query->whereHas('archive', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")->orWhere('box_number', 'like', "%{$search}%");
                });
            }
            if ($deptId) {
                $query->whereHas('archive', function ($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
            }
            if ($dateFrom) {
                $query->whereDate('entry_date', '>=', Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->whereDate('entry_date', '<=', Carbon::parse($dateTo)->endOfDay());
            }
            $entryLogs = $query->latest('entry_date')->paginate(15)->withQueryString();
        }

        // 3. Borrowing Logs
        elseif ($tab === 'borrowing') {
            $query = BorrowingLog::with(['archive.department', 'borrower', 'picGudang']);
            if ($search) {
                $query->whereHas('archive', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")->orWhere('box_number', 'like', "%{$search}%");
                });
            }
            if ($deptId) {
                $query->whereHas('archive', function ($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
            }
            if ($dateFrom) {
                $query->whereDate('request_date', '>=', Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->whereDate('request_date', '<=', Carbon::parse($dateTo)->endOfDay());
            }
            $borrowingLogs = $query->latest('request_date')->paginate(15)->withQueryString();
        }

        // 4. Destruction Logs
        elseif ($tab === 'destruction') {
            $query = DestructionLog::with(['archive.department', 'proposedBy', 'approvedBy']);
            if ($search) {
                $query->whereHas('archive', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")->orWhere('box_number', 'like', "%{$search}%");
                })->orWhere('bap_number', 'like', "%{$search}%");
            }
            if ($deptId) {
                $query->whereHas('archive', function ($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
            }
            if ($dateFrom) {
                $query->whereDate('destruction_date', '>=', Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->whereDate('destruction_date', '<=', Carbon::parse($dateTo)->endOfDay());
            }
            $destructionLogs = $query->latest('destruction_date')->paginate(15)->withQueryString();
        }

        // Overall statistics for KPI cards
        $stats = [
            'total_activity_logs' => ActivityLog::count(),
            'today_activity_logs' => ActivityLog::whereDate('created_at', Carbon::today())->count(),
            'total_users' => User::count(),
            'modules_count' => ActivityLog::distinct('module')->count('module'),
        ];

        $departments = Department::orderBy('code')->get();
        $users = User::orderBy('name')->get();

        $modulesList = [
            'all' => 'Semua Modul',
            'auth' => 'Otentikasi & Sesi (Login/Logout)',
            'archive' => 'Dokumen & Arsip',
            'borrowing' => 'Peminjaman Dokumen',
            'destruction' => 'Pemusnahan & Retensi',
            'warehouse' => 'Gudang & Rak Fisik',
            'layout' => 'Layout Gudang 2D',
            'user' => 'Manajemen Pengguna',
            'master' => 'Master Data Sistem',
            'system' => 'Sistem & API',
        ];

        return view('logs.index', compact(
            'tab',
            'activityLogs',
            'entryLogs',
            'borrowingLogs',
            'destructionLogs',
            'departments',
            'users',
            'modulesList',
            'stats'
        ));
    }
}
