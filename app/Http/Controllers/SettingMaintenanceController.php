<?php

namespace App\Http\Controllers;

use App\Actions\System\ClearSystemData;
use App\Services\ActivityLogger;
use App\Services\DatabaseBackup;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * "Pemeliharaan & Reset Database" of Pengaturan Sistem (admin only, Laravel 7 SettingController):
 * backup download (VACUUM INTO), hapus massal (ASK 7: confirm + automatic backup first) and
 * "Git Pull & Restart Server" (ASK 8: confirm + activity log; UPDATE-AND-RESTART.bat).
 */
class SettingMaintenanceController extends Controller
{
    public function backup(DatabaseBackup $backup): BinaryFileResponse
    {
        $name = 'backup_indraco_dms_'.date('Y_m_d_His').'.sqlite';
        $path = $backup->temporary();
        ActivityLogger::log('SYSTEM_DATABASE_BACKUP', "Membuat dan mengunduh snapshot database konsisten '{$name}'.", 'SETTINGS', ['filename' => $name, 'driver' => 'sqlite']);

        return response()->download($path, $name, ['Content-Type' => 'application/x-sqlite3', 'Cache-Control' => 'no-store'])->deleteFileAfterSend();
    }

    /**
     * Laravel 7 asked a confirm modal for every target and, for "Kosongkan Semua Arsip", the text
     * RESET (prompt()); both are checked here too, so a forged request cannot skip them.
     */
    public function clear(Request $request, string $target, ClearSystemData $clear): RedirectResponse
    {
        abort_unless(in_array($target, ClearSystemData::TARGETS, true), 404);
        $request->validate([
            'confirmed' => ['accepted'],
            'confirmation_text' => [Rule::requiredIf($target === 'archives'), 'nullable', Rule::in(['RESET'])],
        ], ['confirmation_text.*' => 'Penghapusan dibatalkan karena konfirmasi teks tidak sesuai (ketik RESET dengan huruf besar).']);

        try {
            $message = $clear->handle($target);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }

    /** Starts UPDATE-AND-RESTART.bat in its own window (it waits 2 s so this response is sent first). */
    public function gitPull(DatabaseBackup $backup): RedirectResponse
    {
        $script = base_path('UPDATE-AND-RESTART.bat');
        if (! is_file($script)) {
            return back()->with('error', 'File UPDATE-AND-RESTART.bat tidak ditemukan di root project.');
        }
        // The script runs `migrate --force`: keep a snapshot of the data first.
        $file = basename($backup->keep('sebelum_update'));
        ActivityLogger::log('SYSTEM_GIT_PULL_RESTART', 'Memicu proses Git Pull dan Restart Server di background.', 'SETTINGS', ['backup_file' => $file]);
        // `start` returns at once (measured 0.2 s); the script keeps running in its own window.
        Process::path(base_path())->run('start "" /min "'.$script.'"');

        return back()->with('success', 'Perintah Git Pull & Restart Server telah berhasil dipicu. Server sedang memperbarui kode dan akan restart otomatis dalam 5-10 detik.');
    }
}
