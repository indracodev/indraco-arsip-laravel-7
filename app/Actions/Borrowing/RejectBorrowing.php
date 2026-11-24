<?php

namespace App\Actions\Borrowing;

use App\Models\BorrowingLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\BorrowingList;
use App\Services\SystemEventStream;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Gudang "Tolak" with a reason (Laravel 7 BorrowingController@reject, texts kept). The archive
 * stays `in_warehouse`; the PIC sees "Tarik Ulang". Fix: only an active request can be rejected
 * (Laravel 7 also rejected an already dispatched one).
 */
final class RejectBorrowing
{
    public function handle(User $user, BorrowingLog $borrowing, string $reason): string
    {
        DB::transaction(function () use ($user, $borrowing, $reason): void {
            $log = BorrowingLog::query()->with(['borrower', 'archive.department'])->findOrFail($borrowing->id);
            if (! in_array($log->status, BorrowingList::ACTIVE, true)) {
                throw new DomainException('Pengajuan penarikan ini sudah diproses (status: '.BorrowingList::label($log->status).'). Muat ulang halaman.');
            }
            $log->approval_notes = $reason;
            if ($user->role === User::ROLE_PIC_GUDANG) {
                $log->pic_gudang_id = $user->id;
            }
            $log->status = 'rejected';
            $log->approval_status = 'rejected';
            $log->save();
            SystemEventStream::borrowings('borrowing_updated', [$log], $user->id);
            $borrowing->setRawAttributes($log->getAttributes(), true)->setRelation('archive', $log->archive);
        });

        $archive = $borrowing->archive;
        ActivityLogger::log('BORROW_REJECT', "Penolakan pengajuan penarikan arsip '".($archive->title ?? '')."' (Box: ".($archive->box_number ?? '').") oleh {$user->name}. Alasan: {$reason}", 'PENARIKAN_BERKAS',
            ['borrowing_id' => $borrowing->id, 'archive_id' => $borrowing->archive_id, 'box_number' => $archive?->box_number, 'reason' => $reason, 'rejected_by' => $user->id],
            $archive ? $archive->box_number : 'Pengajuan Penarikan', $user);

        return 'Pengajuan penarikan berkas arsip (Box: '.($archive->box_number ?? '-').') berhasil ditolak.';
    }
}
