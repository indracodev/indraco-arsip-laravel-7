<?php

use App\Http\Controllers\DestructionApprovalController;
use App\Http\Controllers\DestructionController;
use App\Http\Controllers\ExtensionController;
use Illuminate\Support\Facades\Route;

// Loaded inside the `auth` group of routes/web.php.
// RETENSI: [Form 3] perpanjangan & pemusnahan (Laravel 7 URLs and names, one definition each:
// B9). Access: DestructionLogPolicy / ArchiveExtensionLogPolicy (create: admin + PIC Departemen
// of the box's department; process: PIC Gudang + admin; BAP / extension print: ArchivePolicy@view).
Route::prefix('destructions')->name('destructions.')->group(function () {
    Route::get('/', [DestructionController::class, 'index'])->name('index');
    Route::get('/propose', [DestructionController::class, 'create'])->name('propose');
    Route::get('/propose/{archive}', [DestructionController::class, 'create'])->name('propose.archive')->whereNumber('archive');
    Route::post('/propose', [DestructionController::class, 'store'])->name('store');
    Route::get('/form-pemusnahan', [DestructionController::class, 'printForm'])->name('form_print');
    Route::get('/bap/{destruction}', [DestructionController::class, 'bap'])->name('bap')->whereNumber('destruction');
    Route::post('/{destruction}/approve', [DestructionApprovalController::class, 'approve'])->name('approve')->whereNumber('destruction');
    Route::post('/{destruction}/reject', [DestructionApprovalController::class, 'reject'])->name('reject')->whereNumber('destruction');
    Route::post('/{destruction}/complete', [DestructionApprovalController::class, 'complete'])->name('complete')->whereNumber('destruction');
    Route::post('/{destruction}/upload-bap', [DestructionApprovalController::class, 'uploadBap'])->name('upload_bap')->whereNumber('destruction');
    Route::get('/extend', [ExtensionController::class, 'create'])->name('extend_form');
    Route::get('/extend/{archive}', [ExtensionController::class, 'create'])->name('extend_form.archive')->whereNumber('archive');
    Route::post('/extend', [ExtensionController::class, 'store'])->name('extend_store');
    Route::get('/form-perpanjangan', [ExtensionController::class, 'printForm'])->name('extend_form_print');
    Route::get('/extend-print/{archive}', [ExtensionController::class, 'print'])->name('extend_print')->whereNumber('archive');
    Route::post('/extensions/{extension}/approve', [ExtensionController::class, 'approve'])->name('extensions.approve')->whereNumber('extension');
    Route::post('/extensions/{extension}/reject', [ExtensionController::class, 'reject'])->name('extensions.reject')->whereNumber('extension');
});
