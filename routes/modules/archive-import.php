<?php

use App\Http\Controllers\ArchiveImportController;
use Illuminate\Support\Facades\Route;

// Loaded inside the `auth` group of routes/web.php.
// IMPOR MULTI-BOX EXCEL (Laravel 7 URLs and names). Laravel 7 refused PIC Gudang (403); admin
// passes every role check. PIC Departemen: own department only (App\Services\ArchiveImportParser).
Route::middleware('role:pic_dept')->prefix('archives/import')->name('archives.import.')->group(function () {
    Route::get('/template', [ArchiveImportController::class, 'template'])->name('template');
    Route::post('/preview', [ArchiveImportController::class, 'preview'])->name('preview');
    Route::post('/commit', [ArchiveImportController::class, 'commit'])->name('commit');
    Route::post('/load-to-form', [ArchiveImportController::class, 'loadToForm'])->name('load_to_form');
});
