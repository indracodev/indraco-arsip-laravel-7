<?php

use App\Http\Controllers\WarehouseLayoutController;
use App\Http\Controllers\WarehouseLayoutObjectController;
use App\Http\Controllers\WarehouseLayoutSlotController;
use Illuminate\Support\Facades\Route;

// Loaded inside the `auth` group of routes/web.php.
// LAYOUT GUDANG 2D (Laravel 7 URLs and names): admin + PIC Gudang. Laravel 7 left
// /api/warehouse/layout-data open to every signed-in user; now the same roles as the page.
// New: /slots (one rack's grid, the canvas data carries no slots), /layout-search (box / slot
// search on the server) and /positions (geometry of many objects in one request).
Route::middleware('role:admin,pic_gudang')->group(function () {
    Route::get('/master/warehouses/layout', [WarehouseLayoutController::class, 'index'])->name('master.warehouses.layout');
    Route::get('/api/warehouse/layout-data', [WarehouseLayoutController::class, 'data'])->name('api.warehouse.layout_data');
    Route::get('/api/warehouse/layout-search', [WarehouseLayoutController::class, 'search'])->name('api.warehouse.layout_search');
    Route::get('/api/warehouse/locations/{location}/slots', [WarehouseLayoutController::class, 'slots'])->name('api.warehouse.locations.slots')->whereNumber('location');

    Route::post('/api/warehouse/locations/store', [WarehouseLayoutObjectController::class, 'store'])->name('api.warehouse.locations.store');
    Route::post('/api/warehouse/locations/positions', [WarehouseLayoutObjectController::class, 'positions'])->name('api.warehouse.locations.positions');
    Route::post('/api/warehouse/locations/{location}/update', [WarehouseLayoutObjectController::class, 'update'])->name('api.warehouse.locations.update')->whereNumber('location');
    Route::post('/api/warehouse/locations/{location}/delete', [WarehouseLayoutObjectController::class, 'destroy'])->name('api.warehouse.locations.delete')->whereNumber('location');
    Route::post('/api/warehouse/locations/{location}/book', [WarehouseLayoutObjectController::class, 'book'])->name('api.warehouse.locations.book')->whereNumber('location');
    Route::post('/api/warehouse/locations/{location}/unbook', [WarehouseLayoutObjectController::class, 'unbook'])->name('api.warehouse.locations.unbook')->whereNumber('location');

    Route::post('/api/warehouse/locations/{location}/slots/assign', [WarehouseLayoutSlotController::class, 'assign'])->name('api.warehouse.locations.slots.assign')->whereNumber('location');
    Route::post('/api/warehouse/locations/{location}/slots/unassign', [WarehouseLayoutSlotController::class, 'unassign'])->name('api.warehouse.locations.slots.unassign')->whereNumber('location');
    Route::post('/api/warehouse/locations/{location}/slots/toggle-active', [WarehouseLayoutSlotController::class, 'toggleActive'])->name('api.warehouse.locations.slots.toggle_active')->whereNumber('location');
});
