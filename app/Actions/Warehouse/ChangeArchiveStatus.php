<?php

namespace App\Actions\Warehouse;

use App\Models\Archive;
use App\Models\DestructionLog;
use App\Models\User;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use App\Services\ActivityLogger;
use App\Services\FileStorage;
use App\Services\NumberingService;
use App\Services\SystemEventStream;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Super Admin: Ubah Status Workflow" (Laravel 7 ArchiveStatusController@superAdminUpdateStatus)
 * for the targets Draft, Antrean Verifikasi, Approved / Booked and Tersimpan di Gudang, with the
 * Laravel 7 side effects, notes and log texts:
 * - draft: rack slot freed, rack counter -1 (from in_warehouse / approved_booked), location
 *   cleared, `rejection_note` = reason;
 * - pending_verification: optional new scan form / digital attachment;
 * - approved_booked / in_warehouse: manual box number (checked against active boxes, the partial
 *   UNIQUE index condition) or a generated one when empty; optional "serah terima" scan; an open
 *   destruction request is rejected when the box comes back from pending_destruction.
 * Keluar (Out) / Antrean Pemusnahan / Dimusnahkan are not offered here: penarikan dispatch is
 * the one exit path (decisions ASK 2-3) and pemusnahan has its own flow (RETENSI).
 */
final class ChangeArchiveStatus
{
    public const TARGETS = [
        'draft' => 'Draft',
        'pending_verification' => 'Antrean Verifikasi',
        'approved_booked' => 'Approved / Booked',
        'in_warehouse' => 'Tersimpan di Gudang',
    ];

    /** Upload field (= archive column) => [folder, log text]. */
    private const FILES = [
        'scan_input_form' => ['archive_scans', 'Scan Formulir Input'],
        'file_path' => ['archive_digital', 'Lampiran Digital Dokumen'],
        'scan_approval_input' => ['archive_approvals', 'Scan Form Persetujuan'],
    ];

    public function __construct(private NumberingService $numbering, private FileStorage $files) {}

    /**
     * @param  array{target_status: string, reason?: string|null, box_number?: string|null}  $data
     * @param  array<string, UploadedFile|null>  $uploads
     */
    public function handle(User $user, Archive $archive, array $data, array $uploads): string
    {
        $stored = [];
        try {
            foreach (self::FILES as $field => [$folder]) {
                if ($uploads[$field] ?? null) {
                    $stored[$field] = $this->files->store($uploads[$field], $folder);
                }
            }
            [$old, $fresh] = DB::transaction(fn (): array => $this->apply($archive, $data, $stored, $user));
        } catch (Throwable $e) {
            $this->files->delete(array_values($stored));
            throw $e;
        }
        $archive->setRawAttributes($fresh->getAttributes(), true);
        $this->log($user, $archive, $old, $data, array_map(fn (string $field) => self::FILES[$field][1], array_keys($stored)));

        return "Status arsip box {$archive->box_number} berhasil diubah menjadi '".self::TARGETS[$data['target_status']]."' oleh Super Admin.";
    }

    /**
     * @param  array{target_status: string, reason?: string|null, box_number?: string|null}  $data
     * @param  array<string, string>  $stored
     * @return array{0: string, 1: Archive}
     */
    private function apply(Archive $archive, array $data, array $stored, User $user): array
    {
        $fresh = Archive::query()->with('department')->findOrFail($archive->id);
        $old = $fresh->status;
        $target = $data['target_status'];
        $reason = filled($data['reason'] ?? null) ? (string) $data['reason'] : null;
        $fresh->fill($stored);

        if ($target === 'draft') {
            $this->vacate($fresh, in_array($old, ['in_warehouse', 'approved_booked'], true));
            $fresh->rejection_note = $reason;
            $this->note($fresh, "SuperAdmin Kembalikan ke DRAFT oleh {$user->name}: {$reason}");
        } elseif ($target === 'pending_verification') {
            $reason && $this->note($fresh, "SuperAdmin Override ke Antrean Verifikasi oleh {$user->name}: {$reason}");
        } else {
            $manual = trim((string) ($data['box_number'] ?? ''));
            $fresh->box_number = $manual !== '' ? $manual : ($fresh->box_number ?: $this->numbering->generate($fresh));
            if ($old === 'pending_destruction') {
                DestructionLog::query()->where('archive_id', $fresh->id)->where('approval_status', '!=', 'completed')
                    ->update(['approval_status' => 'rejected', 'notes' => $reason ?? 'Dibatalkan / dikembalikan ke status Tersimpan di Gudang.', 'updated_at' => now()]);
            }
            $reason && $this->note($fresh, "Update status ke {$target} oleh {$user->name}: {$reason}");
        }
        $fresh->status = $target;

        // Same condition as idx_archives_box_number_active: a message instead of a constraint error.
        $holder = filled($fresh->box_number) ? NumberingService::holder((string) $fresh->box_number, $fresh->id) : null;
        if ($holder) {
            throw new DomainException("Nomor Box '{$fresh->box_number}' sudah digunakan oleh arsip aktif lain (#{$holder->id} - {$holder->title}). Harap gunakan nomor box lain.");
        }
        $fresh->save();
        SystemEventStream::archives('archive_updated', [$fresh], ['changes' => array_keys($fresh->getChanges())]);

        return [$old, $fresh];
    }

    /** Laravel 7 appends "[d/m/Y H:i] ..." to the description. */
    private function note(Archive $archive, string $text): void
    {
        $entry = '['.now()->format('d/m/Y H:i')."] {$text}";
        $archive->content_description = $archive->content_description ? $archive->content_description."\n".$entry : $entry;
    }

    private function vacate(Archive $archive, bool $countDown): void
    {
        WarehouseRackSlot::query()->where(fn ($q) => $q->where('archive_id', $archive->id)->when($archive->warehouse_rack_slot_id, fn ($q, $id) => $q->orWhere('id', $id)))
            ->update(['status' => 'empty', 'archive_id' => null, 'updated_at' => now()]);
        if ($countDown && $archive->warehouse_location_id) {
            WarehouseLocation::query()->whereKey($archive->warehouse_location_id)->where('current_box_count', '>', 0)->decrement('current_box_count');
        }
        $archive->warehouse_location_id = null;
        $archive->warehouse_rack_slot_id = null;
    }

    /**
     * @param  array{target_status: string, reason?: string|null, box_number?: string|null}  $data
     * @param  list<string>  $files
     */
    private function log(User $user, Archive $archive, string $old, array $data, array $files): void
    {
        $target = $data['target_status'];
        $reason = filled($data['reason'] ?? null) ? (string) $data['reason'] : null;
        $ref = $archive->box_number ?: "ID: {$archive->id}";
        $base = ['archive_id' => $archive->id, 'box_number' => $archive->box_number, 'old_status' => $old, 'new_status' => $target];

        match ($target) {
            'draft' => ActivityLogger::log('SUPERADMIN_STATUS_DRAFT', "Super Admin {$user->name} mengembalikan status arsip '{$archive->title}' (No. Box: {$archive->box_number}) dari '{$old}' menjadi DRAFT. Alasan: {$reason}", 'OVERRIDE',
                $base + ['reason' => $reason, 'superadmin_id' => $user->id], $ref, $user),
            'pending_verification' => ActivityLogger::log('SUPERADMIN_STATUS_VERIFY', "Super Admin {$user->name} mengubah status arsip '{$archive->title}' (No. Box: {$archive->box_number}) menjadi Antrean Verifikasi"
                .($files ? ' dengan berkas: '.implode(', ', $files) : '').($reason ? ". Catatan: {$reason}" : ''), 'OVERRIDE',
                $base + ['files_uploaded' => $files, 'reason' => $reason, 'superadmin_id' => $user->id], $ref, $user),
            default => ActivityLogger::log('STATUS_UPDATE_WAREHOUSE', "Pengguna {$user->name} mengubah status arsip '{$archive->title}' menjadi Tersimpan di Gudang (No. Box: {$archive->box_number})"
                .($reason ? ". Catatan: {$reason}" : ''), 'OVERRIDE', $base + ['reason' => $reason, 'user_id' => $user->id], $archive->box_number, $user),
        };
    }
}
