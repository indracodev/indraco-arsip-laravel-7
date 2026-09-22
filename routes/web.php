<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - DMS PT Indraco (Laravel 7 Edition)
|--------------------------------------------------------------------------
*/

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', 'AuthController@showLogin')->name('login');
    Route::post('/login', 'AuthController@login')->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', 'AuthController@logout')->name('logout');

    // Dashboard & Live Search
    Route::get('/', 'DashboardController@index')->name('dashboard');
    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/api/search-archives', 'DashboardController@searchApi')->name('archives.search_api');
    Route::get('/api/departments/{department}/sub-departments', 'ArchiveController@apiGetSubDepartments')->name('api.departments.sub_departments');
    Route::get('/api/archives/calculate-retention', 'ArchiveController@apiCalculateRetention')->name('api.archives.calculate_retention');

    // Archives Management
    Route::get('/archives', 'ArchiveController@index')->name('archives.index');
    Route::get('/archives/create', 'ArchiveController@create')->name('archives.create');
    Route::post('/archives', 'ArchiveController@store')->name('archives.store');
    Route::get('/archives/print-labels', 'ArchiveController@printLabels')->name('archives.print_labels');
    Route::post('/archives/print-labels', 'ArchiveController@printLabels')->name('archives.print_labels_post');
    Route::get('/archives/{archive}', 'ArchiveController@show')->name('archives.show');
    Route::get('/archives/{archive}/print-sticker', 'ArchiveController@printSticker')->name('archives.print_sticker');
    Route::post('/archives/{archive}/verify', 'ArchiveController@verify')->name('archives.verify');
    Route::post('/archives/{archive}/checkin', 'ArchiveController@checkin')->name('archives.checkin');

    // Borrowing Workflow
    Route::get('/borrowings', 'BorrowingController@index')->name('borrowings.index');
    Route::get('/borrowings/create', 'BorrowingController@create')->name('borrowings.create');
    Route::post('/borrowings', 'BorrowingController@store')->name('borrowings.store');
    Route::post('/borrowings/{borrowing}/dept-approve', 'BorrowingController@deptApprove')->name('borrowings.dept_approve');
    Route::post('/borrowings/{borrowing}/approve', 'BorrowingController@approve')->name('borrowings.approve');
    Route::post('/borrowings/{borrowing}/dispatch', 'BorrowingController@dispatch')->name('borrowings.dispatch');
    Route::post('/borrowings/{borrowing}/return', 'BorrowingController@returnArchive')->name('borrowings.return');

    // Retention Expiry & Destruction Workflow
    Route::get('/destructions', 'DestructionController@index')->name('destructions.index');
    Route::get('/destructions/propose/{archive}', 'DestructionController@proposeForm')->name('destructions.propose');
    Route::post('/destructions/propose/{archive}', 'DestructionController@propose')->name('destructions.store');
    Route::get('/destructions/bap/{destructionLog}', 'DestructionController@showBap')->name('destructions.bap');
    Route::get('/destructions/extend/{archive}', 'DestructionController@extendForm')->name('destructions.extend_form');
    Route::post('/destructions/extend/{archive}', 'DestructionController@extendStore')->name('destructions.extend_store');
    Route::get('/destructions/extend-print/{archive}', 'DestructionController@extendPrint')->name('destructions.extend_print');

    // Global Audit Trail Logs
    Route::get('/logs', 'AuditLogController@index')->name('logs.index');

    // Layout Gudang Interactive Canvas & API
    Route::get('/master/warehouses/layout', 'WarehouseLayoutController@index')->name('master.warehouses.layout');
    Route::get('/api/warehouse/layout-data', 'WarehouseLayoutController@apiLayoutData')->name('api.warehouse.layout_data');
    Route::post('/api/warehouse/locations/store', 'WarehouseLayoutController@storeLocation')->name('api.warehouse.locations.store');
    Route::post('/api/warehouse/locations/{location}/book', 'WarehouseLayoutController@bookLocation')->name('api.warehouse.locations.book');
    Route::post('/api/warehouse/locations/{location}/unbook', 'WarehouseLayoutController@unbookLocation')->name('api.warehouse.locations.unbook');
    Route::post('/api/warehouse/locations/{location}/update', 'WarehouseLayoutController@updateLocation')->name('api.warehouse.locations.update');
    Route::post('/api/warehouse/locations/{location}/delete', 'WarehouseLayoutController@destroyLocation')->name('api.warehouse.locations.delete');
    Route::post('/api/warehouse/locations/{location}/slots/assign', 'WarehouseLayoutController@assignSlotArchive')->name('api.warehouse.locations.slots.assign');
    Route::post('/api/warehouse/locations/{location}/slots/unassign', 'WarehouseLayoutController@unassignSlotArchive')->name('api.warehouse.locations.slots.unassign');

    // Master Data Management (Admin & PIC Gudang)
    Route::middleware('role:admin,pic_gudang')->prefix('master')->name('master.')->group(function () {
        Route::get('/departments', 'DepartmentController@index')->name('departments');
        Route::post('/departments', 'DepartmentController@store')->name('departments.store');
        Route::put('/departments/{department}', 'DepartmentController@update')->name('departments.update');
        Route::delete('/departments/{department}', 'DepartmentController@destroy')->name('departments.destroy');

        Route::get('/warehouses', 'WarehouseController@index')->name('warehouses');
        Route::post('/warehouses', 'WarehouseController@storeWarehouse')->name('warehouses.store');
        Route::put('/warehouses/{warehouse}', 'WarehouseController@updateWarehouse')->name('warehouses.update');
        Route::delete('/warehouses/{warehouse}', 'WarehouseController@destroyWarehouse')->name('warehouses.destroy');

        Route::post('/warehouses/locations', 'WarehouseController@storeLocation')->name('warehouses.locations.store');
        Route::delete('/warehouses/locations/{location}', 'WarehouseController@destroyLocation')->name('warehouses.locations.destroy');

        Route::get('/numbering', 'NumberingFormatController@index')->name('numbering')->middleware('role:admin');
        Route::post('/numbering', 'NumberingFormatController@store')->name('numbering.store')->middleware('role:admin');
        Route::put('/numbering/{numberingFormat}', 'NumberingFormatController@update')->name('numbering.update')->middleware('role:admin');

        Route::get('/users', 'UserController@index')->name('users');
        Route::post('/users', 'UserController@store')->name('users.store');
        Route::put('/users/{user}', 'UserController@update')->name('users.update');
        Route::delete('/users/{user}', 'UserController@destroy')->name('users.destroy');
        Route::post('/users/{user}/impersonate', 'UserController@impersonate')->name('users.impersonate');
    });

    // Leave Impersonate Route
    Route::post('/impersonate/leave', 'UserController@leaveImpersonate')->name('impersonate.leave');
});
