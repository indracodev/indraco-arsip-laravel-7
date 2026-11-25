<?php

namespace App\Actions\Retention;

use App\Models\Archive;
use App\Models\DestructionLog;
use App\Models\User;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use App\Services\FileStorage;
use App\Services\SystemEventStream;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Gudang/admin "Sahkan Pemusnahan & Terbitkan BAP" (Laravel 7 DestructionController@complete,
 * texts kept): official BAP number + date (+ optional certificate scan, notes); the box becomes
 * `destroyed` and leaves its rack. Source status: only an `approved` (Antrean) proposal.
 */
final class CompleteDestruction
{
    public function __construct(private FileStorage $files) {}

    /** @param array{bap_number: string, destruction_date: string, notes?: string|null} $data */
    public function handle(User $user, DestructionLog $destruction, array $data, ?UploadedFile $certificate): string
    {
        $path = $certificate ? $this->files->store($certificate, 'bap_certificates') : null;
        try {
            [$archive, $note] = DB::transaction(function () use ($destruction, $data, $path): array {
                $log = DestructionLog::query()->with('archive.department')->findOrFail($destruction->id);
                ApproveDestruction::ensureStatus($log, ['approved']);
                $note = self::checkBapNumber($data['bap_number'], [$log->archive->department_id], $log->id);
                $log->fill([
                    'bap_number' => $data['bap_number'], 'destruction_date' => $data['destruction_date'],
                    'certificate_file' => $path ?? $log->certificate_file, 'notes' => $data['notes'] ?? $log->notes,
                ]);
                $log->approval_status = 'completed';
                $log->save();
                self::destroyArchive($log->archive);
                SystemEventStream::archives('archive_updated', [$log->archive], ['changes' => ['status', 'warehouse_location_id', 'warehouse_rack_slot_id', 'updated_at']]);

                return [$log->archive, $note];
            });
        } catch (Throwable $e) {
            $this->files->delete([$path]);
            throw $e;
        }

        ActivityLogger::log('DESTRUCTION_EXECUTE', "Pemusnahan berkas fisik '{$archive->title}' (Box: {$archive->box_number}) selesai dieksekusi dengan No. BAP {$data['bap_number']}.", 'PEMUSNAHAN_RETENSI',
            ['destruction_log_id' => $destruction->id, 'archive_id' => $archive->id, 'bap_number' => $data['bap_number']], $data['bap_number'], $user);

        return "Pemusnahan berkas ({$archive->title}) telah selesai dieksekusi dengan No. BAP {$data['bap_number']}. Slot rak telah dikosongkan dan BAP siap dicetak.{$note}";
    }

    /**
     * Q1 (owner 2026-11-25): one BAP number may cover several boxes, but of one department only
     * (trim + case-insensitive; the "(Menunggu Penerbitan BAP)" placeholder is never checked).
     * Returns the flash note for a reuse in the same department ('' when the number is new).
     *
     * @param  list<int>  $departmentIds  departments of the boxes being completed
     */
    public static function checkBapNumber(string $bap, array $departmentIds, ?int $exceptLogId = null): string
    {
        $key = mb_strtolower(trim($bap));
        if ($key === '' || $key === mb_strtolower(ProposeDestruction::PENDING_BAP)) {
            return '';
        }
        $departmentIds = array_values(array_unique($departmentIds));
        if (count($departmentIds) > 1) {
            throw new DomainException('Satu No. BAP hanya boleh dipakai untuk box dari satu departemen. Pisahkan pengesahan per departemen.');
        }
        $used = DestructionLog::query()->with('archive.department:id,code,name')->whereRaw('lower(trim(bap_number)) = ?', [$key])
            ->when($exceptLogId, fn ($q) => $q->whereKeyNot($exceptLogId))->get();
        $other = $used->first(fn (DestructionLog $log): bool => $log->archive?->department_id !== $departmentIds[0]);
        if ($other) {
            $department = $other->archive?->department;
            throw new DomainException('No. BAP '.trim($bap).' sudah dipakai oleh departemen '.($department ? "{$department->code} - {$department->name}" : 'lain').'. Gunakan nomor BAP lain.');
        }

        return $used->isEmpty() ? '' : " No. BAP ini juga dipakai untuk {$used->count()} box lain di departemen yang sama.";
    }

    /**
     * Box destroyed: status, location cleared, rack slot emptied, rack counter down.
     * ponytail: same steps as DispatchBorrowing::vacate(); move both to one place when a third flow needs it.
     */
    public static function destroyArchive(Archive $archive): void
    {
        $locationId = $archive->warehouse_location_id;
        $slotId = $archive->warehouse_rack_slot_id;
        $archive->forceFill(['status' => 'destroyed', 'warehouse_location_id' => null, 'warehouse_rack_slot_id' => null])->save();

        WarehouseRackSlot::query()->where(fn ($q) => $q->where('archive_id', $archive->id)->when($slotId, fn ($q) => $q->orWhere('id', $slotId)))
            ->update(['status' => 'empty', 'archive_id' => null, 'updated_at' => now()]);
        if ($locationId) {
            WarehouseLocation::query()->whereKey($locationId)->where('current_box_count', '>', 0)->decrement('current_box_count');
        }
    }
}
