<?php

namespace App\Actions\Retention;

use App\Models\DestructionLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\RetentionList;
use App\Services\SystemEventStream;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Gudang/admin "Setujui" (Laravel 7 DestructionController@approve, texts kept): the proposal
 * joins the Antrean Pemusnahan; the box stays `pending_destruction` in its rack.
 * Fix: only a `pending` proposal (Laravel 7 approved any state, even a completed one).
 */
final class ApproveDestruction
{
    public function handle(User $user, DestructionLog $destruction): string
    {
        $archive = DB::transaction(function () use ($user, $destruction) {
            $log = DestructionLog::query()->with('archive.department')->findOrFail($destruction->id);
            self::ensureStatus($log, ['pending']);
            $log->approved_by_dept_pic_id = $user->id;
            $log->approval_status = 'approved';
            $log->save();
            if ($log->archive->status !== 'pending_destruction') {
                $log->archive->forceFill(['status' => 'pending_destruction'])->save();
                SystemEventStream::archives('archive_updated', [$log->archive], ['changes' => ['status', 'updated_at']]);
            }

            return $log->archive;
        });

        ActivityLogger::log('DESTRUCTION_APPROVE', "Pengajuan pemusnahan berkas '{$archive->title}' disetujui oleh {$user->name} dan dimasukkan ke Antrean Pemusnahan.", 'PEMUSNAHAN_RETENSI',
            ['destruction_log_id' => $destruction->id, 'archive_id' => $archive->id], $archive->box_number ?: "ID: {$archive->id}", $user);

        return "Pengajuan pemusnahan berkas ({$archive->title}) disetujui dan telah masuk ke Antrean Pemusnahan Gudang.";
    }

    /**
     * Source-status check shared by the destruction actions (inside their transaction).
     *
     * @param  list<string>  $allowed
     */
    public static function ensureStatus(DestructionLog $log, array $allowed): void
    {
        if (! $log->archive) {
            throw new DomainException('Data arsip pengajuan pemusnahan tidak ditemukan.');
        }
        if (! in_array($log->approval_status, $allowed, true)) {
            throw new DomainException('Pengajuan pemusnahan ini sudah diproses (status: '.(RetentionList::DESTRUCTION[$log->approval_status][0] ?? $log->approval_status).'). Muat ulang halaman.');
        }
    }
}
