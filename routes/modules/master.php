<?php

use App\Http\Controllers\DepartmentApiController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentPicController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\MasterArchiveController;
use App\Http\Controllers\MasterDataImportController;
use App\Http\Controllers\NumberingFormatController;
use App\Http\Controllers\SubDepartmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseLocationController;
use Illuminate\Support\Facades\Route;

// Loaded inside the `auth` group of routes/web.php.
// MASTER & USER (Laravel 7 URLs and names). Admin only (`role:` lets admin through), except
// Master Gudang & Rak: PIC Gudang may open it and add a gudang / rak, like the Laravel 7 page
// (edit, delete and the active switch were admin-only buttons there; now also on the server).
// The Layout Gudang 2D route stays in routes/web.php (Round F).

// Drill-down / manage data of Master Departemen: admin only (findings #5: no other role may read
// another department's boxes or the user list with e-mails).
Route::middleware('role:admin')->group(function () {
    Route::get('/api/departments/{department}/manage-data', [DepartmentApiController::class, 'manageData'])->name('api.departments.manage_data')->whereNumber('department');
    Route::get('/api/departments/{department}/archives', [DepartmentApiController::class, 'departmentArchives'])->name('api.departments.archives')->whereNumber('department');
    Route::get('/api/sub-departments/{subDepartment}/archives', [DepartmentApiController::class, 'subDepartmentArchives'])->name('api.sub_departments.archives')->whereNumber('subDepartment');
});

Route::prefix('master')->name('master.')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/import/template', [MasterDataImportController::class, 'template'])->name('departments.import.template');
        Route::post('/departments/import/preview', [MasterDataImportController::class, 'preview'])->name('departments.import.preview');
        Route::post('/departments/import/commit', [MasterDataImportController::class, 'commit'])->name('departments.import.commit');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update')->whereNumber('department');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy')->whereNumber('department');
        Route::post('/departments/{department}/toggle-active', [DepartmentController::class, 'toggleActive'])->name('departments.toggle_active')->whereNumber('department');

        Route::post('/departments/{department}/pic/assign', [DepartmentPicController::class, 'assign'])->name('departments.pic.assign')->whereNumber('department');
        Route::delete('/departments/{department}/pic/{user}', [DepartmentPicController::class, 'remove'])->name('departments.pic.remove')->whereNumber(['department', 'user']);

        Route::post('/departments/{department}/master-archives', [MasterArchiveController::class, 'store'])->name('departments.master_archives.store')->whereNumber('department');
        Route::post('/departments/{department}/master-archives/batch', [MasterArchiveController::class, 'storeBatch'])->name('departments.master_archives.batch')->whereNumber('department');
        Route::put('/departments/{department}/master-archives/{masterArchive}', [MasterArchiveController::class, 'update'])->name('departments.master_archives.update')->whereNumber(['department', 'masterArchive'])->scopeBindings();
        Route::delete('/departments/{department}/master-archives/{masterArchive}', [MasterArchiveController::class, 'destroy'])->name('departments.master_archives.destroy')->whereNumber(['department', 'masterArchive'])->scopeBindings();

        Route::post('/sub-departments', [SubDepartmentController::class, 'store'])->name('sub_departments.store');
        Route::put('/sub-departments/{subDepartment}', [SubDepartmentController::class, 'update'])->name('sub_departments.update')->whereNumber('subDepartment');
        Route::delete('/sub-departments/{subDepartment}', [SubDepartmentController::class, 'destroy'])->name('sub_departments.destroy')->whereNumber('subDepartment');
        Route::post('/sub-departments/{subDepartment}/toggle-active', [SubDepartmentController::class, 'toggleActive'])->name('sub_departments.toggle_active')->whereNumber('subDepartment');

        Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update')->whereNumber('warehouse');
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy')->whereNumber('warehouse');
        Route::post('/warehouses/{warehouse}/toggle-active', [WarehouseController::class, 'toggleActive'])->name('warehouses.toggle_active')->whereNumber('warehouse');
        Route::put('/warehouses/locations/{location}', [WarehouseLocationController::class, 'update'])->name('warehouses.locations.update')->whereNumber('location');
        Route::delete('/warehouses/locations/{location}', [WarehouseLocationController::class, 'destroy'])->name('warehouses.locations.destroy')->whereNumber('location');

        Route::get('/numbering', [NumberingFormatController::class, 'index'])->name('numbering');
        Route::post('/numbering', [NumberingFormatController::class, 'store'])->name('numbering.store');
        Route::put('/numbering/{numberingFormat}', [NumberingFormatController::class, 'update'])->name('numbering.update')->whereNumber('numberingFormat');

        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->whereNumber('user');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->whereNumber('user');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset_password')->whereNumber('user');
        Route::post('/users/{user}/impersonate', [ImpersonateController::class, 'start'])->name('users.impersonate')->whereNumber('user');
    });

    Route::middleware('role:pic_gudang')->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::post('/warehouses/locations', [WarehouseLocationController::class, 'store'])->name('warehouses.locations.store');
    });
});

// Any signed-in user: only acts when the session holds an impersonation started by an admin.
Route::post('/impersonate/leave', [ImpersonateController::class, 'leave'])->name('impersonate.leave');
