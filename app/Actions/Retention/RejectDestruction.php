<?php

namespace App\Actions\Retention;

use App\Models\DestructionLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SystemEventStream;
use Illuminate\Support\Facades\DB;

/**
 * Gudang/admin "Tolak" with a reason (Laravel 7 DestructionController@reject, texts kept): the
 * box goes back to `in_warehouse`. Source status: `pending` or `approved` (Laravel 7 buttons).
 */
final class RejectDestruction
{
    public function handle(User $user, DestructionLog $destruction, string $reason): string
    {
        $archive = DB::transaction(function () use ($user, $destruction, $reason) {
            $log = DestructionLog::query()->with('archive.department')->findOrFail($destruction->id);
            ApproveDestruction::ensureStatus($log, ['pending', 'approved']);
            $log->approval_notes = $reason;
            $log->approved_by_dept_pic_id = $user->id;
            $log->approval_status = 'rejected';
            $log->save();
            if ($log->archive->status === 'pending_destruction') {
                $log->archive->forceFill(['status' => 'in_warehouse'])->save();
                SystemEventStream::archives('archive_updated', [$log->archive], ['changes' => ['status', 'updated_at']]);
            }

            return $log->archive;
        });

        ActivityLogger::log('DESTRUCTION_REJECTED', "Pengajuan pemusnahan berkas '{$archive->title}' ditolak oleh {$user->name}. Alasan: {$reason}", 'PEMUSNAHAN_RETENSI',
            ['destruction_log_id' => $destruction->id, 'archive_id' => $archive->id, 'rejection_note' => $reason, 'rejected_by' => $user->id], $archive->box_number ?: '-', $user);

        return 'Pengajuan pemusnahan berkas berhasil ditolak. Status berkas dikembalikan normal di gudang.';
    }
}
