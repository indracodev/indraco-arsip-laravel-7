<?php

namespace App\Actions\Borrowing;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\BorrowingList;
use App\Services\FileStorage;
use App\Services\SystemEventStream;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * PIC "Ajukan Penarikan": one `borrowing_logs` row per box, status `requested`, sharing one
 * approval scan (Laravel 7 BorrowingController@store, messages kept). Always permanent
 * (`expected_return_date` NULL, decisions ASK 2). Department ownership is checked by the
 * controller (BorrowingLogPolicy@create) before this runs.
 *
 * Double submit / two sessions: "box in warehouse" and "no active penarikan" are checked again
 * inside the IMMEDIATE transaction, so a second request is refused instead of duplicating rows.
 */
final class SubmitBorrowing
{
    public function __construct(private FileStorage $files) {}

    /**
     * @param  list<int>  $archiveIds
     * @return string success message
     */
    public function handle(User $user, array $archiveIds, string $purpose, UploadedFile $approval): string
    {
        $archives = Archive::query()->with('department')->whereIn('id', $archiveIds)->get();
        if ($archives->count() !== count($archiveIds)) {
            throw new DomainException('Satu atau lebih berkas arsip yang dipilih tidak ditemukan.');
        }
        $this->ensureAvailable($archives->all());

        $path = $this->files->store($approval, 'borrowing_approvals');
        try {
            $logs = DB::transaction(function () use ($user, $archiveIds, $purpose, $path): array {
                $fresh = Archive::query()->with('department')->whereIn('id', $archiveIds)->get();
                $this->ensureAvailable($fresh->all(), concurrent: true);

                $logs = [];
                foreach ($fresh as $archive) {
                    $log = new BorrowingLog([
                        'archive_id' => $archive->id, 'borrower_user_id' => $user->id, 'request_date' => now(),
                        'expected_return_date' => null, 'purpose' => $purpose,
                        'approval_file' => $path, 'scan_approval_borrow' => $path, 'is_approval_uploaded' => true,
                    ]);
                    $log->status = 'requested';
                    $log->approval_status = 'pending';
                    $log->save();
                    $log->setRelation('archive', $archive)->setRelation('borrower', $user);
                    $archive->touch(); // Laravel 7: the box jumps to the top of the catalog
                    $logs[] = $log;
                }
                SystemEventStream::borrowings('borrowing_created', $logs, $user->id);
                SystemEventStream::archives('archive_updated', $fresh, ['changes' => ['updated_at']]);

                return $logs;
            });
        } catch (Throwable $e) {
            $this->files->delete([$path]);
            throw $e;
        }

        $this->log($user, $logs, $archiveIds, $purpose);

        return count($logs) > 1
            ? 'Permintaan penarikan '.count($logs).' box berkas berhasil diajukan dengan lampiran form persetujuan. Menunggu verifikasi Departemen & pengeluaran fisik Gudang.'
            : 'Permintaan penarikan berkas berhasil diajukan dengan lampiran form persetujuan. Menunggu verifikasi Departemen & pengeluaran fisik Gudang.';
    }

    /**
     * Laravel 7 checks, in its order: in the warehouse, then no active penarikan.
     *
     * @param  list<Archive>  $archives
     */
    private function ensureAvailable(array $archives, bool $concurrent = false): void
    {
        $unavailable = array_filter($archives, fn (Archive $a): bool => $a->status !== 'in_warehouse');
        if ($unavailable !== []) {
            throw new DomainException('Berkas arsip berikut tidak tersedia di gudang untuk ditarik: '.$this->labels($unavailable));
        }

        $busyIds = BorrowingLog::query()->whereIn('archive_id', array_map(fn (Archive $a): int => $a->id, $archives))
            ->whereIn('status', BorrowingList::ACTIVE)->pluck('archive_id')->all();
        if ($busyIds !== []) {
            throw new DomainException($concurrent
                ? 'Satu atau lebih berkas arsip yang dipilih telah diajukan penarikan oleh pengguna lain.'
                : 'Gagal mengajukan penarikan: Box arsip berikut sedang dalam proses penarikan aktif: '
                    .$this->labels(array_filter($archives, fn (Archive $a): bool => in_array($a->id, $busyIds))));
        }
    }

    /** @param array<Archive> $archives */
    private function labels(array $archives): string
    {
        return implode(', ', array_map(fn (Archive $a): string => ($a->box_number ? "[{$a->box_number}] " : '').$a->title, $archives));
    }

    /**
     * @param  list<BorrowingLog>  $logs
     * @param  list<int>  $archiveIds
     */
    private function log(User $user, array $logs, array $archiveIds, string $purpose): void
    {
        $boxNumbers = array_values(array_filter(array_map(fn (BorrowingLog $l): ?string => $l->archive->box_number, $logs)));
        $titles = array_map(fn (BorrowingLog $l): string => (string) $l->archive->title, $logs);
        $count = count($logs);
        $list = $boxNumbers !== [] ? implode(', ', $boxNumbers) : implode(', ', array_slice($titles, 0, 3));

        ActivityLogger::log('BORROW_CREATE', "Pengajuan penarikan {$count} box berkas ('{$list}') oleh {$user->name} untuk keperluan: {$purpose}", 'PENARIKAN_BERKAS',
            ['box_count' => $count, 'archive_ids' => $archiveIds, 'box_numbers' => $boxNumbers, 'purpose' => $purpose, 'expected_return_date' => null],
            $boxNumbers !== [] ? implode(', ', array_slice($boxNumbers, 0, 2)) : "Batch {$count} Box", $user);
    }
}
