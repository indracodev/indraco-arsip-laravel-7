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
    Route::get('/api/realtime/check-new-archives', 'DashboardController@realtimeCheck')->name('api.realtime.check');
    Route::get('/api/departments/{department}/sub-departments', 'ArchiveController@apiGetSubDepartments')->name('api.departments.sub_departments');
    Route::get('/api/departments/{department}/archives', 'DepartmentController@apiGetDepartmentArchives')->name('api.departments.archives');
    Route::get('/api/sub-departments/{subDepartment}/archives', 'DepartmentController@apiGetSubDepartmentArchives')->name('api.sub_departments.archives');
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

    // Pusat Laporan & Dokumen PDF
    Route::get('/reports', 'ReportController@index')->name('reports.index');
    Route::get('/reports/data/{type}', 'ReportController@data')->name('reports.data');
    Route::get('/reports/print/{type}', 'ReportController@print')->name('reports.print');
    Route::get('/reports/export-csv/{type}', 'ReportController@exportCsv')->name('reports.export_csv');

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
    Route::post('/api/warehouse/locations/{location}/slots/toggle-active', 'WarehouseLayoutController@toggleSlotActive')->name('api.warehouse.locations.slots.toggle_active');

    // Master Department & Sub-Department Archives Drill-Down API
    Route::get('/api/departments/{department}/archives', 'DepartmentController@apiGetDepartmentArchives')->name('api.departments.archives');
    Route::get('/api/sub-departments/{subDepartment}/archives', 'DepartmentController@apiGetSubDepartmentArchives')->name('api.sub_departments.archives');
    Route::get('/api/departments/{department}/manage-data', 'DepartmentController@apiGetManageData')->name('api.departments.manage_data');
    Route::get('/api/departments/{department}/master-archives', 'MasterArchiveController@apiGetByDepartment')->name('api.departments.master_archives');
    Route::get('/api/settings/current', 'SettingController@getSettings')->name('api.settings.current');

    // Master Data Management (Admin & PIC Gudang)
    Route::middleware('role:admin,pic_gudang')->prefix('master')->name('master.')->group(function () {
        Route::get('/departments', 'DepartmentController@index')->name('departments');
        Route::post('/departments', 'DepartmentController@store')->name('departments.store');
        Route::put('/departments/{department}', 'DepartmentController@update')->name('departments.update');
        Route::delete('/departments/{department}', 'DepartmentController@destroy')->name('departments.destroy');

        // Department Manage PIC
        Route::post('/departments/{department}/pic/assign', 'DepartmentController@assignPic')->name('departments.pic.assign');
        Route::delete('/departments/{department}/pic/{user}', 'DepartmentController@removePic')->name('departments.pic.remove');

        // Department Master Archives CRUD
        Route::post('/departments/{department}/master-archives', 'MasterArchiveController@store')->name('departments.master_archives.store');
        Route::post('/departments/{department}/master-archives/batch', 'MasterArchiveController@storeBatch')->name('departments.master_archives.batch');
        Route::put('/departments/{department}/master-archives/{masterArchive}', 'MasterArchiveController@update')->name('departments.master_archives.update');
        Route::delete('/departments/{department}/master-archives/{masterArchive}', 'MasterArchiveController@destroy')->name('departments.master_archives.destroy');

        // System Settings & Appearance & Database Maintenance
        Route::post('/settings/update', 'SettingController@updateSettings')->name('settings.update');
        Route::post('/settings/reset-logo', 'SettingController@resetLogo')->name('settings.reset_logo');
        Route::get('/settings/backup-database', 'SettingController@backupDatabase')->name('settings.backup_database');
        Route::post('/settings/backup-database', 'SettingController@backupDatabase');
        Route::post('/settings/clear-logs', 'SettingController@clearLogs')->name('settings.clear_logs');
        Route::post('/settings/clear-box-allocations', 'SettingController@clearBoxAllocations')->name('settings.clear_box_allocations');
        Route::post('/settings/clear-archives', 'SettingController@clearArchives')->name('settings.clear_archives');

        Route::post('/sub-departments', 'DepartmentController@storeSubDepartment')->name('sub_departments.store');
        Route::put('/sub-departments/{subDepartment}', 'DepartmentController@updateSubDepartment')->name('sub_departments.update');
        Route::delete('/sub-departments/{subDepartment}', 'DepartmentController@destroySubDepartment')->name('sub_departments.destroy');

        Route::get('/warehouses', 'WarehouseController@index')->name('warehouses');
        Route::post('/warehouses', 'WarehouseController@storeWarehouse')->name('warehouses.store');
        Route::put('/warehouses/{warehouse}', 'WarehouseController@updateWarehouse')->name('warehouses.update');
        Route::delete('/warehouses/{warehouse}', 'WarehouseController@destroyWarehouse')->name('warehouses.destroy');
        Route::post('/warehouses/{warehouse}/toggle-active', 'WarehouseController@toggleWarehouseActive')->name('warehouses.toggle_active');

        Route::post('/warehouses/locations', 'WarehouseController@storeLocation')->name('warehouses.locations.store');
        Route::put('/warehouses/locations/{location}', 'WarehouseController@updateLocation')->name('warehouses.locations.update');
        Route::delete('/warehouses/locations/{location}', 'WarehouseController@destroyLocation')->name('warehouses.locations.destroy');

        Route::get('/numbering', 'NumberingFormatController@index')->name('numbering')->middleware('role:admin');
        Route::post('/numbering', 'NumberingFormatController@store')->name('numbering.store')->middleware('role:admin');
        Route::put('/numbering/{numberingFormat}', 'NumberingFormatController@update')->name('numbering.update')->middleware('role:admin');

        Route::get('/users', 'UserController@index')->name('users')->middleware('role:admin');
        Route::post('/users', 'UserController@store')->name('users.store')->middleware('role:admin');
        Route::put('/users/{user}', 'UserController@update')->name('users.update')->middleware('role:admin');
        Route::delete('/users/{user}', 'UserController@destroy')->name('users.destroy')->middleware('role:admin');
        Route::post('/users/{user}/impersonate', 'UserController@impersonate')->name('users.impersonate')->middleware('role:admin');
    });

    // Leave Impersonate Route
    Route::post('/impersonate/leave', 'UserController@leaveImpersonate')->name('impersonate.leave');
});
