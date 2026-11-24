<?php

use App\Http\Controllers\ArchiveLabelController;
use App\Http\Controllers\ArchiveStatusController;
use App\Http\Controllers\ArchiveVerificationController;
use Illuminate\Support\Facades\Route;

// Loaded inside the `auth` group of routes/web.php.
// GUDANG: verification + box numbering, rack check-in, status change, label / sticker printing
// (Laravel 7 URLs and names). Access: ArchivePolicy@processWarehouse (PIC Gudang + admin).
// Not rebuilt: POST /archives/{a}/checkout (404; decisions ASK 2-3, one exit path = borrowings dispatch).
Route::get('/archives/print-labels', [ArchiveLabelController::class, 'batch'])->name('archives.print_labels');
Route::post('/archives/print-labels', [ArchiveLabelController::class, 'batch'])->name('archives.print_labels_post');
Route::get('/archives/{archive}/print-sticker', [ArchiveLabelController::class, 'sticker'])->name('archives.print_sticker')->whereNumber('archive');
Route::post('/archives/{archive}/verify', [ArchiveVerificationController::class, 'verify'])->name('archives.verify')->whereNumber('archive');
Route::post('/archives/{archive}/checkin', [ArchiveVerificationController::class, 'checkin'])->name('archives.checkin')->whereNumber('archive');
Route::post('/archives/{archive}/superadmin-status', ArchiveStatusController::class)->name('archives.superadmin_status')->whereNumber('archive');
