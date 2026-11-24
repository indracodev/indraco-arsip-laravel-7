<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\User;
use App\Models\WarehouseLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * "/" : PIC Departemen land on their catalog ([Form 1], the first window of the Laravel 7 PIC
 * shell); admin and PIC Gudang on "Dashboard Overview" (Laravel 7 DashboardController@index).
 * Every count goes through Archive::visibleTo (B7: Laravel 7 counted some lists over all
 * departments). Laravel 7 also loaded pendingBookings / pendingBorrowings / deptBreakdown /
 * warehouses that the page never showed: not loaded here.
 */
class DashboardController extends Controller
{
    private const PER_PAGE = [5, 10, 15, 20];

    public function __invoke(Request $request): RedirectResponse|View
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->role === User::ROLE_PIC_DEPT) {
            return redirect()->route('archives.index');
        }

        $archives = fn () => Archive::query()->visibleTo($user);
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE, true) ? (int) $request->query('per_page') : 10;
        $capacity = WarehouseLocation::query()->where('is_active', true)
            ->selectRaw('COALESCE(SUM(box_capacity), 0) as total, COALESCE(SUM(current_box_count), 0) as used')
            ->toBase()->first();
        $total = (int) ($capacity->total ?? 0);
        $used = (int) ($capacity->used ?? 0);

        return view('dashboard.index', [
            'user' => $user,
            'stats' => [
                'total' => $archives()->count(),
                'in_warehouse' => $archives()->where('status', 'in_warehouse')->count(),
                'pending' => $archives()->where('status', 'pending_verification')->count(),
                'out' => $archives()->whereIn('status', ['borrowed', 'taken'])->count(),
                // Laravel 7: expiry date within 90 days (or passed), not draft/destroyed.
                'expiring' => $archives()->whereNotNull('retention_expiry_date')->whereNotIn('status', ['destroyed', 'draft'])
                    ->whereDate('retention_expiry_date', '<=', Carbon::now()->addDays(90))->count(),
            ],
            'capacity' => ['total' => $total, 'used' => $used, 'percent' => $total > 0 ? round($used / $total * 100, 1) : 0],
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE,
            'recentArchives' => $archives()->with(['department:id,code,name', 'location.warehouse', 'rackSlot'])
                ->latest()->latest('id')->paginate($perPage)->withQueryString(),
        ]);
    }
}
