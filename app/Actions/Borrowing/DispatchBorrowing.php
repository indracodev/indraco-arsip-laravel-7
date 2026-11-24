<?php

namespace App\Actions\Borrowing;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\User;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use App\Services\BorrowingList;
use App\Services\FileStorage;
use App\Services\SystemEventStream;
use App\Support\StatusLabel;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Gudang "Keluarkan Arsip" (Laravel 7 BorrowingController@dispatch): the request is approved and
 * the box leaves the rack in one step (Laravel 7 had no separate approve button). Penarikan is
 * permanent, so the archive becomes `taken` (decisions ASK 2-3: the one exit path).
 *
 * Differences from Laravel 7 (fixes):
 * - source status checked: only an active request (requested / dept_approved / approved) and a box
 *   still `in_warehouse`; a second click or a rejected request is refused;
 * - an optional gudang receipt scan goes to `scan_approval_borrow` only, so the PIC approval form
 *   in `approval_file` is kept (Laravel 7 overwrote both columns);
 * - the rack location box counter is really decremented (Laravel 7 read the location after
 *   clearing it, so it never was).
 */
final class DispatchBorrowing
{
    public function __construct(private FileStorage $files) {}

    public function handle(User $user, BorrowingLog $borrowing, ?UploadedFile $receipt): string
    {
        $path = $receipt ? $this->files->store($receipt, 'borrowing_approvals') : null;
        try {
            DB::transaction(function () use ($user, $borrowing, $path): void {
                $log = BorrowingLog::query()->with(['borrower', 'archive.department', 'archive.items'])->findOrFail($borrowing->id);
                $archive = $log->archive;
                if (! in_array($log->status, BorrowingList::ACTIVE, true)) {
                    throw new DomainException('Pengajuan penarikan ini sudah diproses (status: '.BorrowingList::label($log->status).'). Muat ulang halaman.');
                }
                if (! $archive || $archive->status !== 'in_warehouse') {
                    throw new DomainException('Box arsip tidak sedang tersimpan di gudang (status: '.($archive ? StatusLabel::archive($archive->status)['label'] : '-').'). Tolak pengajuan ini bila box tidak dapat dikeluarkan.');
                }

                $log->fill([
                    'borrow_date' => now(), 'pic_gudang_id' => $user->id,
                    'department_approved_at' => $log->department_approved_at ?? now(),
                    'department_approval_by' => $log->department_approval_by ?? $user->id,
                    'snapshot_box_number' => $archive->box_number, 'snapshot_title' => $archive->title,
                    'snapshot_department' => $archive->department->code ?? 'DEPT',
                    'snapshot_items' => $archive->items->map(fn ($item): array => [
                        'number' => $item->item_number, 'name' => $item->document_name, 'period' => $item->period_text, 'notes' => $item->notes,
                    ])->all(),
                ] + ($path ? ['scan_approval_borrow' => $path] : []));
                $log->status = 'dispatched';
                $log->approval_status = 'approved';
                $log->save();

                $this->vacate($archive, $log->expected_return_date ? 'borrowed' : 'taken');
                SystemEventStream::borrowings('borrowing_updated', [$log], $user->id);
                SystemEventStream::archives('archive_updated', [$archive], ['changes' => array_keys($archive->getChanges())]);
                $borrowing->setRawAttributes($log->getAttributes(), true)->setRelation('archive', $archive);
            });
        } catch (Throwable $e) {
            $this->files->delete([$path]);
            throw $e;
        }

        $archive = $borrowing->archive;
        ActivityLogger::log('BORROW_DISPATCH', "Pengeluaran berkas fisik arsip '{$archive->title}' (Box: {$archive->box_number}) oleh PIC Gudang {$user->name}", 'PENARIKAN_BERKAS',
            ['borrowing_id' => $borrowing->id, 'archive_id' => $archive->id, 'box_number' => $archive->box_number, 'status' => 'dispatched'], $archive->box_number, $user);

        return 'Pengeluaran berkas disahkan! Status penarikan berubah menjadi "Sudah Ditarik" dan slot rak telah dikosongkan.';
    }

    /** Archive out of the rack: status, location cleared, slot emptied, rack counter down. */
    private function vacate(Archive $archive, string $status): void
    {
        $locationId = $archive->warehouse_location_id;
        $slotId = $archive->warehouse_rack_slot_id;
        $archive->status = $status;
        $archive->warehouse_location_id = null;
        $archive->warehouse_rack_slot_id = null;
        $archive->save();

        WarehouseRackSlot::query()->where(fn ($q) => $q->where('archive_id', $archive->id)->when($slotId, fn ($q) => $q->orWhere('id', $slotId)))
            ->update(['status' => 'empty', 'archive_id' => null, 'updated_at' => now()]);
        if ($locationId) {
            WarehouseLocation::query()->whereKey($locationId)->where('current_box_count', '>', 0)->decrement('current_box_count');
        }
    }
}
