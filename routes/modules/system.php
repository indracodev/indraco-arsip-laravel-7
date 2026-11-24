<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SettingMaintenanceController;
use Illuminate\Support\Facades\Route;

// Loaded inside the `auth` group of routes/web.php.
// SISTEM (Laravel 7 URLs and names). "/" + "/dashboard" stay in routes/web.php (DashboardController).
// Everything except the realtime poll and the expiry list is admin only (findings #6, #10, #11).
// Not rebuilt: /reports/data/{type} (report page renders on the server), /api/settings/current and
// /api/health/connected-users (0 callers, triage), the .sql dump (only the VACUUM INTO snapshot),
// /master/settings/git-pull-log (the settings page shows the log), the diagnostics `?token=` key.

// Realtime notifications + menu badges (all roles; events filtered per department).
Route::get('/api/realtime/check', [NotificationController::class, 'poll'])->name('api.realtime.check');
Route::get('/api/archives/expiring-list', [NotificationController::class, 'expiring'])->name('api.archives.expiring_list');

Route::middleware('role:admin')->group(function () {
    Route::get('/logs', AuditLogController::class)->name('logs.index');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/print/{type}', [ReportController::class, 'print'])->name('reports.print');
    Route::get('/reports/export-csv/{type}', [ReportController::class, 'exportCsv'])->name('reports.export_csv');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/update', [SettingController::class, 'update'])->name('master.settings.update');
    Route::post('/settings/reset-logo', [SettingController::class, 'resetLogo'])->name('master.settings.reset_logo');
    Route::prefix('master/settings')->name('master.settings.')->controller(SettingMaintenanceController::class)->group(function () {
        Route::post('/backup-database', 'backup')->name('backup_database');
        Route::post('/clear-logs', 'clear')->name('clear_logs')->defaults('target', 'logs');
        Route::post('/clear-box-allocations', 'clear')->name('clear_box_allocations')->defaults('target', 'box_allocations');
        Route::post('/clear-archives', 'clear')->name('clear_archives')->defaults('target', 'archives');
        Route::post('/git-pull-restart', 'gitPull')->name('git_pull_restart');
    });

    // "Live Monitor LAN & User" + telemetry, polled only by this page (PROJECT_RULES_LAN_FRONTEND_OPS §4.3).
    Route::get('/diagnostics', [DiagnosticsController::class, 'index'])->name('diagnostics.index');
    Route::get('/api/health/metrics', [DiagnosticsController::class, 'metrics'])->name('api.health.metrics');
    Route::get('/api/health/probe-ip', [DiagnosticsController::class, 'probeIp'])->name('api.health.probe_ip')->middleware('throttle:30,1');
    Route::get('/api/health/logs', [DiagnosticsController::class, 'logs'])->name('api.health.logs');
    Route::post('/api/health/wal-checkpoint', [DiagnosticsController::class, 'walCheckpoint'])->name('api.health.wal_checkpoint');
});
