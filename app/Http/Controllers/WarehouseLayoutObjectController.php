<?php

namespace App\Http\Controllers;

use App\Actions\Warehouse\BookLayoutLocation;
use App\Actions\Warehouse\DeleteLayoutObject;
use App\Actions\Warehouse\SaveLayoutObject;
use App\Actions\Warehouse\SaveLayoutPositions;
use App\Http\Requests\LayoutObjectRequest;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use App\Support\WarehouseLayout;
use Closure;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Layout Gudang 2D, objects (Laravel 7 URLs: store / update / delete / book / unbook, JSON
 * {success, message}). `positions` saves every object of one drag / nudge / rotation at once.
 * A business rule refusal is 422 with the message; Gudang GA / IT objects are admin-only (403).
 */
class WarehouseLayoutObjectController extends Controller
{
    public function store(LayoutObjectRequest $request, SaveLayoutObject $save): JsonResponse
    {
        return $this->attempt(function () use ($request, $save): JsonResponse {
            $location = $save->handle($request->user(), null, $request->validated());

            return response()->json(['success' => true, 'message' => "Object '{$location->rack_code}' berhasil ditambahkan ke canvas.", 'id' => $location->id]);
        });
    }

    public function update(LayoutObjectRequest $request, WarehouseLocation $location, SaveLayoutObject $save): JsonResponse
    {
        $this->guard($request, $location);

        return $this->attempt(function () use ($request, $location, $save): JsonResponse {
            $saved = $save->handle($request->user(), $location, $request->validated());

            return response()->json(['success' => true, 'message' => "Data object '{$saved->rack_code}' berhasil diperbarui.", 'updated_at' => (string) $saved->getAttribute('updated_at')]);
        });
    }

    public function positions(Request $request, SaveLayoutPositions $save): JsonResponse
    {
        $items = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:300'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.canvas_x' => ['required', 'integer', 'min:0', 'max:10000'],
            'items.*.canvas_y' => ['required', 'integer', 'min:0', 'max:10000'],
            'items.*.canvas_width' => ['required', 'integer', 'min:4', 'max:4000'],
            'items.*.canvas_height' => ['required', 'integer', 'min:4', 'max:4000'],
            'items.*.rotation_angle' => ['nullable', 'integer', 'min:0', 'max:359'],
            'items.*.orientation' => ['nullable', 'string', 'in:horizontal,vertical'],
        ])['items'];
        $locations = WarehouseLocation::query()->whereKey(array_column($items, 'id'))->get();
        abort_if($locations->count() !== count($items), 404);
        $locations->each(fn (WarehouseLocation $location) => $this->guard($request, $location));

        return response()->json(['success' => true, 'updated_at' => $save->handle($items)]);
    }

    public function destroy(Request $request, WarehouseLocation $location, DeleteLayoutObject $delete): JsonResponse
    {
        $this->guard($request, $location);

        return $this->attempt(fn (): JsonResponse => response()->json(['success' => true, 'message' => $delete->handle($request->user(), $location)]));
    }

    public function book(Request $request, WarehouseLocation $location, BookLayoutLocation $book): JsonResponse
    {
        $this->guard($request, $location);
        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'booking_notes' => ['required', 'string', 'max:500'],
        ]);

        return $this->attempt(fn (): JsonResponse => response()->json([
            'success' => true, 'message' => $book->handle($request->user(), $location, (int) $data['department_id'], $data['booking_notes']),
        ]));
    }

    public function unbook(Request $request, WarehouseLocation $location): JsonResponse
    {
        $this->guard($request, $location);
        WarehouseLocation::query()->whereKey($location->id)->update(['is_booked' => false, 'booked_by_user_id' => null, 'booking_notes' => null, 'updated_at' => now()]);
        ActivityLogger::log('LAYOUT_SLOT_UNBOOK', "Melepas status booking pada slot rak '{$location->rack_code}'.", 'LAYOUT_GUDANG',
            ['location_id' => $location->id, 'rack_code' => $location->rack_code], $location->rack_code, $request->user());

        return response()->json(['success' => true, 'message' => "Status booking slot rak '{$location->rack_code}' berhasil dilepas."]);
    }

    private function guard(Request $request, WarehouseLocation $location): void
    {
        abort_if(! $request->user()->isAdmin() && WarehouseLayout::isAdminOnly($location), 403, 'Akses Ditolak: object ini khusus Super Admin.');
    }

    /** @param  Closure(): JsonResponse  $action */
    private function attempt(Closure $action): JsonResponse
    {
        try {
            return $action();
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
