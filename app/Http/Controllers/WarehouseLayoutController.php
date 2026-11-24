<?php

namespace App\Http\Controllers;

use App\Models\WarehouseLocation;
use App\Support\WarehouseLayout;
use App\Support\WarehouseLayoutSlots;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layout Gudang 2D, read side (Laravel 7 WarehouseLayoutController index and apiLayoutData).
 * Admin + PIC Gudang (routes); Gudang GA / IT objects are admin-only. The canvas data carries no
 * slots: a rack's 100 slots come from `slots()` when its grid opens.
 */
class WarehouseLayoutController extends Controller
{
    public function index(Request $request): View
    {
        return view('master.warehouse-layout.index', [
            'isAdmin' => $request->user()->isAdmin(),
            'colors' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6', '#f97316', '#334155'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $isAdmin = $request->user()->isAdmin();

        return response()->json([
            'success' => true,
            'locations' => WarehouseLayout::locations($isAdmin),
            'departments' => WarehouseLayout::departments(),
        ]);
    }

    public function slots(Request $request, WarehouseLocation $location): JsonResponse
    {
        abort_if(! $request->user()->isAdmin() && WarehouseLayout::isAdminOnly($location), 403);
        abort_if($location->location_type === 'room', 404);

        return response()->json([
            'success' => true,
            'slots' => WarehouseLayoutSlots::slots($location),
            'queue' => WarehouseLayoutSlots::queue($location),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        return response()->json([
            'success' => true,
            'boxes' => mb_strlen($term) < 2 ? [] : WarehouseLayoutSlots::search(mb_substr($term, 0, 100), $request->user()->isAdmin()),
        ]);
    }
}
