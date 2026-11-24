<?php

namespace App\Actions\Retention;

use App\Models\ArchiveExtensionLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SystemEventStream;
use Illuminate\Support\Facades\DB;

/**
 * Gudang/admin "Tolak" of an extension with a reason (Laravel 7 HandlesArchiveExtensions@rejectExtension,
 * texts kept): the box is back to `in_warehouse`. Source status as ApproveExtension::pending().
 */
final class RejectExtension
{
    public function handle(User $user, ArchiveExtensionLog $extension, string $reason): string
    {
        $log = DB::transaction(function () use ($user, $extension, $reason) {
            $log = ApproveExtension::pending($extension);
            $log->rejection_note = $reason;
            ApproveExtension::close($log, $user, 'rejected');
            $log->archive->forceFill(['status' => 'in_warehouse'])->save();
            SystemEventStream::archives('archive_updated', [$log->archive], ['changes' => ['status', 'updated_at']]);

            return $log;
        });

        $archive = $log->archive;
        ActivityLogger::log('RETENTION_EXTEND_REJECTED', "Pengajuan perpanjangan masa retensi berkas '{$archive->title}' ditolak oleh {$user->name}. Alasan: {$reason}", 'PEMUSNAHAN_RETENSI',
            ['extension_log_id' => $log->id, 'archive_id' => $archive->id, 'rejection_note' => $reason], $archive->box_number ?: '-', $user);

        return 'Pengajuan perpanjangan berkas ditolak. Status berkas kembali normal di gudang.';
    }
}
