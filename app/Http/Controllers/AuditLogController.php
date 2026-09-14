<?php

namespace App\Http\Controllers;

use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Models\WarehouseEntryLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'entry'); // 'entry', 'borrowing', 'destruction'
        $search = $request->query('search');
        $deptId = $request->query('department_id');

        $entryLogs = collect();
        $borrowingLogs = collect();
        $destructionLogs = collect();

        if ($tab === 'entry') {
            $query = WarehouseEntryLog::with(['archive.department', 'picGudang', 'location.warehouse']);
            if ($search) {
                $query->whereHas('archive', fn($q) => $q->where('title', 'like', "%{$search}%")->orWhere('box_number', 'like', "%{$search}%"));
            }
            if ($deptId) {
                $query->whereHas('archive', fn($q) => $q->where('department_id', $deptId));
            }
            $entryLogs = $query->latest()->paginate(10)->withQueryString();
        } elseif ($tab === 'borrowing') {
            $query = BorrowingLog::with(['archive.department', 'borrower', 'picGudang']);
            if ($search) {
                $query->whereHas('archive', fn($q) => $q->where('title', 'like', "%{$search}%")->orWhere('box_number', 'like', "%{$search}%"));
            }
            if ($deptId) {
                $query->whereHas('archive', fn($q) => $q->where('department_id', $deptId));
            }
            $borrowingLogs = $query->latest()->paginate(10)->withQueryString();
        } elseif ($tab === 'destruction') {
            $query = DestructionLog::with(['archive.department', 'proposedBy', 'approvedBy']);
            if ($search) {
                $query->whereHas('archive', fn($q) => $q->where('title', 'like', "%{$search}%")->orWhere('box_number', 'like', "%{$search}%"))
                      ->orWhere('bap_number', 'like', "%{$search}%");
            }
            if ($deptId) {
                $query->whereHas('archive', fn($q) => $q->where('department_id', $deptId));
            }
            $destructionLogs = $query->latest()->paginate(10)->withQueryString();
        }

        $departments = Department::all();

        return view('logs.index', compact('tab', 'entryLogs', 'borrowingLogs', 'destructionLogs', 'departments'));
    }
}
