<?php

namespace App\Actions\Retention;

use App\Models\ArchiveExtensionLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\RetentionCalculator;
use App\Services\RetentionList;
use App\Services\SystemEventStream;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Gudang/admin "ACC" of an extension (Laravel 7 HandlesArchiveExtensions@approveExtension, texts
 * kept): the archive takes the new expiry of the log and the retention from
 * RetentionCalculator::approvedRetention(), and is back to `in_warehouse`. Fix: only a `pending`
 * log whose box is `pending_extension` (Laravel 7 approved any state, so a second click extended twice).
 */
final class ApproveExtension
{
    public function __construct(private RetentionCalculator $calculator) {}

    public function handle(User $user, ArchiveExtensionLog $extension): string
    {
        $log = DB::transaction(function () use ($user, $extension) {
            $log = self::pending($extension);
            $archive = $log->archive;
            $archive->fill($this->calculator->approvedRetention($archive, $log->additional_months) + [
                'retention_expiry_date' => $log->new_expiry_date, 'extension_reason' => $log->extension_reason,
                'scan_extension_form' => $log->scan_extension_form ?: $archive->scan_extension_form,
            ])->forceFill(['status' => 'in_warehouse'])->save();
            self::close($log, $user, 'approved');
            SystemEventStream::archives('archive_updated', [$archive], ['changes' => array_keys($archive->getChanges())]);

            return $log;
        });

        $archive = $log->archive;
        $label = RetentionCalculator::durationLabel($log->additional_months);
        $expiry = $log->new_expiry_date?->format('M Y') ?? '';
        ActivityLogger::log('RETENTION_EXTEND_APPROVED', "Perpanjangan masa retensi berkas '{$archive->title}' (Box: {$archive->box_number}) sebanyak +{$label} disetujui (ACC) oleh {$user->name}. Masa simpan baru: {$expiry}.", 'PEMUSNAHAN_RETENSI',
            ['extension_log_id' => $log->id, 'archive_id' => $archive->id, 'additional_months' => $log->additional_months, 'new_expiry_date' => $log->new_expiry_date?->format('Y-m-d')],
            $archive->box_number ?: "ID: {$archive->id}", $user);

        return "Pengajuan perpanjangan berkas '{$archive->title}' berhasil di-ACC oleh Gudang. Masa simpan resmi diperpanjang hingga {$expiry} dan status berkas kembali aktif di gudang.";
    }

    /** Fresh log inside the transaction; source status `pending` + box `pending_extension`. */
    public static function pending(ArchiveExtensionLog $extension): ArchiveExtensionLog
    {
        $log = ArchiveExtensionLog::query()->with('archive.department')->findOrFail($extension->id);
        if (! $log->archive) {
            throw new DomainException('Data arsip perpanjangan tidak ditemukan.');
        }
        if ($log->approval_status !== 'pending' || $log->archive->status !== 'pending_extension') {
            throw new DomainException('Pengajuan perpanjangan ini sudah diproses (status: '.(RetentionList::EXTENSION[$log->approval_status][0] ?? $log->approval_status).'). Muat ulang halaman.');
        }

        return $log;
    }

    public static function close(ArchiveExtensionLog $log, User $user, string $status): void
    {
        $log->approved_by_user_id = $user->id;
        $log->approved_at = now();
        $log->approval_status = $status;
        $log->save();
    }
}
