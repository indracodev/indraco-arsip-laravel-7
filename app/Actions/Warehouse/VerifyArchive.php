<?php

namespace App\Actions\Warehouse;

use App\Models\Archive;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NumberingService;
use App\Services\SystemEventStream;
use App\Support\StatusLabel;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Gudang "Hub Verifikasi" (Laravel 7 ArchiveVerificationController@verify).
 * - approve: `pending_verification` -> `approved_booked`, box number issued in the same
 *   transaction (counter moves only here), rejection note cleared;
 * - reject: `pending_verification` -> `draft` + `rejection_note` (PIC sees "Revisi & Ajukan").
 * Fix vs Laravel 7: the source status is checked inside the IMMEDIATE transaction, so a second
 * click or a second warehouse user cannot approve twice (Laravel 7 re-saved any status).
 */
final class VerifyArchive
{
    public function __construct(private NumberingService $numbering) {}

    public function handle(User $user, Archive $archive, bool $approve, ?string $note): string
    {
        DB::transaction(function () use ($archive, $approve, $note): void {
            $fresh = Archive::query()->with('department')->findOrFail($archive->id);
            if ($fresh->status !== 'pending_verification') {
                throw new DomainException('Pengajuan arsip ini sudah diproses (status: '.StatusLabel::archive($fresh->status)['label'].'). Muat ulang halaman.');
            }
            if ($approve) {
                $fresh->box_number = $fresh->box_number ?: $this->numbering->generate($fresh);
                $fresh->status = 'approved_booked';
                $fresh->rejection_note = null;
            } else {
                $fresh->status = 'draft';
                $fresh->rejection_note = $note;
            }
            $fresh->save();
            SystemEventStream::archives('archive_updated', [$fresh], ['changes' => array_keys($fresh->getChanges())]);
            $archive->setRawAttributes($fresh->getAttributes(), true);
        });

        if ($approve) {
            ActivityLogger::log('ARCHIVE_VERIFY', "Verifikasi persetujuan arsip '{$archive->title}' & alokasi nomor box generated '{$archive->box_number}'.", 'DOKUMEN_ARSIP',
                ['archive_id' => $archive->id, 'box_number' => $archive->box_number, 'status' => $archive->status], $archive->box_number, $user);

            return "Pengajuan arsip disetujui! Nomor Box Generated: {$archive->box_number}";
        }

        ActivityLogger::log('ARCHIVE_REJECT', "Pengajuan arsip '{$archive->title}' ditolak oleh PIC Gudang dengan catatan: {$note}", 'DOKUMEN_ARSIP',
            ['archive_id' => $archive->id, 'rejection_note' => $note], $archive->box_number ?: "ID: {$archive->id}", $user);

        return 'Pengajuan arsip ditolak dan dikembalikan ke PIC Departemen.';
    }
}
