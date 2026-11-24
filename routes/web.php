<?php

use App\Http\Controllers\ArchiveCatalogController;
use App\Http\Controllers\ArchiveFileController;
use App\Http\Controllers\ArchiveFormPrintController;
use App\Http\Controllers\ArchiveSimilarityController;
use App\Http\Controllers\ArchiveSubmissionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BorrowingApprovalController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FontSizeController;
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
    Route::post('/profile/font-size', FontSizeController::class)->name('profile.font_size');

    // ARSIP: catalog + box submission (Laravel 7 URLs and names). Laravel 7 refused PIC Gudang
    // on create/store (admin passes every role check); edit/update/delete use ArchivePolicy.
    Route::get('/archives', [ArchiveCatalogController::class, 'index'])->name('archives.index');
    Route::middleware('role:pic_dept')->group(function () {
        Route::get('/archives/create', [ArchiveSubmissionController::class, 'create'])->name('archives.create');
        Route::post('/archives', [ArchiveSubmissionController::class, 'store'])->name('archives.store');
        Route::post('/archives/batch-delete-drafts', [ArchiveSubmissionController::class, 'batchDestroy'])->name('archives.batch_delete_drafts');
        Route::post('/api/archives/check-similarity', ArchiveSimilarityController::class)->name('api.archives.check_similarity');
    });
    Route::get('/archives/{archive}', [ArchiveCatalogController::class, 'show'])->name('archives.show')->whereNumber('archive');
    Route::get('/archives/{archive}/edit', [ArchiveSubmissionController::class, 'edit'])->name('archives.edit')->whereNumber('archive');
    Route::put('/archives/{archive}', [ArchiveSubmissionController::class, 'update'])->name('archives.update')->whereNumber('archive');
    Route::delete('/archives/{archive}', [ArchiveSubmissionController::class, 'destroy'])->name('archives.destroy')->whereNumber('archive');
    Route::get('/archives/{archive}/form-pengajuan', ArchiveFormPrintController::class)->name('archives.form_print')->whereNumber('archive');

    // FILE: preview (inline) / download (attachment) of a record's file column; never a raw path.
    // Replaces Laravel 7 files/stream/{path} + files/preview-stream (no login: findings #2).
    $fileRoute = ['source' => implode('|', array_keys(ArchiveFileController::SOURCES)), 'id' => '[0-9]+', 'field' => '[a-z_]+'];
    Route::get('/files/{source}/{id}/{field}', ArchiveFileController::class)->name('files.preview')->where($fileRoute);
    Route::get('/files/{source}/{id}/{field}/download', ArchiveFileController::class)->name('files.download')->where($fileRoute);
    // base64 JSON for the page viewer: IDM grabs PDF responses, even fetch() (finding R27).
    Route::get('/files/{source}/{id}/{field}/preview-data', ArchiveFileController::class)->name('files.preview_data')->where($fileRoute);

    // PENARIKAN (Laravel 7 URLs and names). Access: BorrowingLogPolicy (create: admin + PIC Departemen
    // of the box's department; dispatch/reject: PIC Gudang + admin). Not built (decisions ASK 2-4,
    // triage): dept-approve, approve, return, update.
    Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::get('/borrowings/create', [BorrowingController::class, 'create'])->name('borrowings.create');
    Route::post('/borrowings', [BorrowingController::class, 'store'])->name('borrowings.store');
    Route::get('/borrowings/form-penarikan', [BorrowingController::class, 'printForm'])->name('borrowings.form_print');
    Route::post('/borrowings/{borrowing}/dispatch', [BorrowingApprovalController::class, 'dispatch'])->name('borrowings.dispatch')->whereNumber('borrowing');
    Route::post('/borrowings/{borrowing}/reject', [BorrowingApprovalController::class, 'reject'])->name('borrowings.reject')->whereNumber('borrowing');

    require __DIR__.'/modules/warehouse.php'; // GUDANG: verifikasi + nomor box, check-in rak, ubah status, cetak label

    require __DIR__.'/modules/retention.php'; // RETENSI: [Form 3] perpanjangan & pemusnahan

    require __DIR__.'/modules/archive-import.php'; // IMPOR multi-box Excel: template, pratinjau, simpan, isi form

    require __DIR__.'/modules/system.php'; // SISTEM: log, laporan, pengaturan, backup, diagnostik, notifikasi

    require __DIR__.'/modules/warehouse-layout.php'; // LAYOUT GUDANG 2D: kanvas, objek, booking, slot rak

    require __DIR__.'/modules/master.php'; // MASTER & USER: departemen, gudang & rak, penomoran, user, impersonate
});
