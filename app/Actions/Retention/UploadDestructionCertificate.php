<?php

namespace App\Actions\Retention;

use App\Models\DestructionLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\FileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Gudang/admin "Upload BAP" / "Re-upload BAP" after the destruction (Laravel 7
 * DestructionController@uploadBap, texts kept). Source status: `completed` only (Laravel 7 showed
 * the button only there). The previous scan file is kept on disk (no data loss).
 */
final class UploadDestructionCertificate
{
    public function __construct(private FileStorage $files) {}

    public function handle(User $user, DestructionLog $destruction, UploadedFile $certificate): string
    {
        $path = $this->files->store($certificate, 'bap_certificates');
        try {
            $log = DB::transaction(function () use ($destruction, $path) {
                $log = DestructionLog::query()->with('archive')->findOrFail($destruction->id);
                ApproveDestruction::ensureStatus($log, ['completed']);
                $log->certificate_file = $path;
                $log->save();

                return $log;
            });
        } catch (Throwable $e) {
            $this->files->delete([$path]);
            throw $e;
        }

        ActivityLogger::log('DESTRUCTION_BAP_UPLOADED', "Berkas fisik sertifikat BAP resmi (No: {$log->bap_number}) untuk arsip '".($log->archive->title ?? '-')."' berhasil diunggah oleh {$user->name}.", 'PEMUSNAHAN_RETENSI',
            ['destruction_log_id' => $log->id, 'archive_id' => $log->archive_id, 'bap_number' => $log->bap_number, 'certificate_file' => $path], $log->bap_number, $user);

        return "Berkas sertifikat BAP resmi (No: {$log->bap_number}) berhasil diunggah. Departemen kini dapat mengunduh dan mencetak dokumen BAP.";
    }
}
