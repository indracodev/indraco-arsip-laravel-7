<?php

use App\Http\Controllers\ArchiveCatalogController;
use App\Http\Controllers\ArchiveCreateMockupController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaceholderController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard', DashboardController::class);
    Route::view('/profile/password', 'auth.change-password')->name('profile.password');
    Route::post('/profile/change-password', ChangePasswordController::class)->name('profile.change_password');

    // Katalog Arsip: read-only list (Phase 3 mockup). Same URL/name as Laravel 7.
    Route::get('/archives', [ArchiveCatalogController::class, 'index'])->name('archives.index');
    // Mockup of the box submission form; Laravel 7 refused PIC Gudang (403).
    Route::get('/archives/create', ArchiveCreateMockupController::class)->name('archives.create')->middleware('role:pic_dept');
    Route::get('/archives/{archive}', [ArchiveCatalogController::class, 'show'])->name('archives.show')->whereNumber('archive');
    Route::get('/archives/{archive}/edit', [ArchiveCatalogController::class, 'show'])->name('archives.edit')->whereNumber('archive');

    // Modules not rebuilt yet: Laravel 7 URL, name and role check, "Sedang dibangun ulang" page.
    Route::get('/borrowings', PlaceholderController::class)->name('borrowings.index')->defaults('title', 'Penarikan Berkas');
    Route::get('/borrowings/create', PlaceholderController::class)->name('borrowings.create')->defaults('title', 'Penarikan Berkas')->middleware('role:pic_dept');
    Route::get('/destructions', PlaceholderController::class)->name('destructions.index')->defaults('title', 'Pemusnahan & Retensi');
    Route::get('/logs', PlaceholderController::class)->name('logs.index')->defaults('title', 'Log History')->middleware('role:admin');
    Route::get('/reports', PlaceholderController::class)->name('reports.index')->defaults('title', 'Report')->middleware('role:admin');
    Route::get('/diagnostics', PlaceholderController::class)->name('diagnostics.index')->defaults('title', 'Live Monitor LAN & User')->middleware('role:admin');

    Route::middleware('role:admin,pic_gudang')->prefix('master')->name('master.')->group(function () {
        Route::get('/departments', PlaceholderController::class)->name('departments')->defaults('title', 'Master Departemen')->middleware('role:admin');
        Route::get('/warehouses', PlaceholderController::class)->name('warehouses')->defaults('title', 'Master Gudang & Rak');
        Route::get('/warehouses/layout', PlaceholderController::class)->name('warehouses.layout')->defaults('title', 'Layout Gudang 2D');
        Route::get('/numbering', PlaceholderController::class)->name('numbering')->defaults('title', 'Format Penomoran Box')->middleware('role:admin');
        Route::get('/users', PlaceholderController::class)->name('users')->defaults('title', 'Kelola User & Hak Akses')->middleware('role:admin');
    });
});
