<?php

namespace App\Actions\Archive;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ArchiveAttachments;
use App\Services\ArchiveBoxBuilder;
use App\Services\SystemEventStream;
use App\Support\StatusLabel;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Simpan & Ajukan": one `archives` row per box with status `pending_verification` plus its
 * document lines (Laravel 7 HandlesMultiBoxCreation/Update, submit branch). From a draft the
 * draft row becomes box 1 and the other boxes are new rows. No box number yet: the warehouse
 * assigns it at verification. An admin editing a non-draft archive keeps its status (one box).
 *
 * Double submit: box titles must be unique per department (not destroyed), checked inside the
 * transaction, so a repeated request is refused instead of creating the boxes twice.
 */
final class SubmitArchiveBoxes
{
    public function __construct(private ArchiveBoxBuilder $builder, private ArchiveAttachments $attachments) {}

    /**
     * @param  array<string, mixed>  $data  validated input
     * @param  array{scan: UploadedFile|null, file: UploadedFile|null, remove_scan: bool, remove_file: bool}  $uploads
     * @return string success message
     */
    public function handle(User $user, Department $department, array $data, array $uploads, ?Archive $archive = null, ?string $originalUpdatedAt = null): string
    {
        $saved = $this->createAndReturn($user, $department, $data, $uploads, $archive, $originalUpdatedAt);
        $count = count($saved);
        $itemCount = array_sum(array_map(fn (Archive $box): int => $box->items()->count(), $saved));

        return "Pengajuan {$count} box arsip ({$itemCount} butir dokumen) berhasil disubmit untuk diverifikasi PIC Gudang.";
    }

    /**
     * @param  array<string, mixed>  $data  validated input
     * @param  array{scan: UploadedFile|null, file: UploadedFile|null, remove_scan: bool, remove_file: bool}  $uploads
     * @return list<Archive>
     */
    public function createAndReturn(User $user, Department $department, array $data, array $uploads, ?Archive $archive = null, ?string $originalUpdatedAt = null): array
    {
        $keepStatus = $archive && $archive->status !== 'draft';
        $boxes = $this->boxes(array_values($data['boxes']), $department, Carbon::parse($data['tgl_penyerahan']), $data);

        $files = $this->attachments->prepare($archive, $uploads['scan'], $uploads['file'], $uploads['remove_scan'], $uploads['remove_file']);
        try {
            $saved = DB::transaction(function () use ($user, $department, $archive, $originalUpdatedAt, $boxes, $files, $keepStatus): array {
                if ($archive) {
                    EnsureArchiveUnchanged::check($archive, $originalUpdatedAt);
                }
                if ($keepStatus && count($boxes) > 1) {
                    throw new DomainException('Arsip yang sudah diajukan hanya dapat berisi 1 box.');
                }
                $this->ensureUniqueTitles($boxes, $department, $archive);

                return $this->save($user, $department, $archive, $boxes, $files, $keepStatus);
            });
        } catch (Throwable $e) {
            $this->attachments->discard($files['new']);
            throw $e;
        }
        $this->attachments->deleteUnreferenced($files['replaced']);

        $count = count($saved);
        $itemCount = array_sum(array_map(fn (array $box): int => count($box['items']), $boxes));
        if ($keepStatus) {
            ActivityLogger::log('ARCHIVE_UPDATE', "Memperbarui data rincian berkas arsip '{$saved[0]->title}' ({$itemCount} butir berkas) [Status: ".StatusLabel::archive($saved[0]->status)['label']."] oleh {$user->name}.",
                'DOKUMEN_ARSIP', ['archive_id' => $saved[0]->id, 'title' => $saved[0]->title, 'status' => $saved[0]->status], $saved[0]->box_number ?: "ID: {$saved[0]->id}", $user);
        } else {
            $properties = ['boxes_count' => $count, 'items_count' => $itemCount, 'status' => 'pending_verification'] + ($archive ? [] : ['department_id' => $department->id]);
            ActivityLogger::log('ARCHIVE_CREATE', "Pengajuan batch {$count} box arsip ({$itemCount} butir berkas) ".($archive ? 'dari draft ' : '')."oleh {$user->name}.",
                'DOKUMEN_ARSIP', $properties, $user->name, $user);
        }

        return $saved;
    }

    /**
     * Column values and document lines per box (computed before the transaction).
     *
     * @param  list<array<string, mixed>>  $rawBoxes
     * @param  array<string, mixed>  $data
     * @return list<array{attributes: array<string, mixed>, items: list<array<string, mixed>>}>
     */
    private function boxes(array $rawBoxes, Department $department, Carbon $handover, array $data): array
    {
        $boxes = [];
        foreach ($rawBoxes as $box) {
            $items = $this->builder->items(array_values($box['items'] ?? []), true);
            if ($items === []) {
                continue;
            }
            $boxes[] = ['items' => $items, 'attributes' => $this->builder->boxAttributes($box, $items, $department, $handover) + [
                'title' => trim((string) ($box['title'] ?? '')) ?: $items[0]['document_name'],
                'content_description' => $this->builder->contentLines($items),
                'sub_department_id' => $box['sub_department_id'] ?? null,
                'document_type' => $box['document_type'] ?? null,
                'physical_condition' => ($box['physical_condition'] ?? null) ?: ArchiveBoxBuilder::DEFAULT_CONDITION,
                'company_name' => ($data['company_name'] ?? null) ?: ArchiveBoxBuilder::DEFAULT_COMPANY,
                'tgl_penyerahan' => $handover->format('Y-m-d'),
                'origin_borrowing_log_id' => ! empty($box['origin_borrowing_log_id']) ? (int) $box['origin_borrowing_log_id'] : null,
            ]];
        }

        return $boxes;
    }

    /** @param list<array{attributes: array<string, mixed>}> $boxes */
    private function ensureUniqueTitles(array $boxes, Department $department, ?Archive $archive): void
    {
        $seen = [];
        foreach ($boxes as $box) {
            $title = $box['attributes']['title'];
            $normalized = strtolower((string) preg_replace('/\s+/', ' ', $title));
            if (in_array($normalized, $seen, true)) {
                throw new DomainException("Terdapat nama/judul box yang duplikat di dalam form pengajuan ini: \"{$title}\". Setiap box harus memiliki nama yang berbeda.");
            }
            $seen[] = $normalized;

            $duplicate = Archive::query()->where('department_id', $department->id)->where('status', '!=', 'destroyed')
                ->when($archive, fn ($query) => $query->whereKeyNot($archive->id))
                ->whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($title))])->first(['id', 'box_number', 'status']);
            if ($duplicate) {
                $boxNumber = $duplicate->box_number ? "[{$duplicate->box_number}]" : '';
                throw new DomainException("Nama / Judul Box \"{$title}\" sudah digunakan oleh arsip lain {$boxNumber} (Status: ".StatusLabel::archive($duplicate->status)['label'].'). Harap gunakan nama box yang berbeda.');
            }
        }
    }

    /**
     * @param  list<array{attributes: array<string, mixed>, items: list<array<string, mixed>>}>  $boxes
     * @param  array{scan_input_form: string|null, file_path: string|null}  $files
     * @return list<Archive>
     */
    private function save(User $user, Department $department, ?Archive $archive, array $boxes, array $files, bool $keepStatus): array
    {
        $now = now();
        $saved = [];
        foreach ($boxes as $index => $box) {
            $originLog = null;
            $originId = $box['attributes']['origin_borrowing_log_id'] ?? null;
            if ($originId !== null) {
                $originLog = BorrowingLog::query()->with('archive')->findOrFail($originId);
                if ($originLog->status !== 'dispatched' || $originLog->active_restore_archive_id !== null || (int) $originLog->archive?->department_id !== (int) $department->id) {
                    throw new DomainException('Box penarikan ini sudah memiliki pengajuan penyimpanan kembali yang aktif atau tidak valid.');
                }
            }

            $row = $index === 0 && $archive ? $archive : new Archive;
            $row->fill($box['attributes'] + ['scan_input_form' => $files['scan_input_form'], 'file_path' => $files['file_path']]);
            $row->department_id = $department->id;
            $row->created_by_user_id = $user->id;
            $row->status = $keepStatus ? $row->status : 'pending_verification';
            if ($originLog) {
                $row->re_store_snapshot = json_encode([
                    'borrowing_log_id' => $originLog->id,
                    'box_number' => $originLog->snapshot_box_number,
                    'title' => $originLog->snapshot_title,
                ]);
            }
            $row->created_at = $row->updated_at = $now->copy()->addSeconds($index);
            $isNew = ! $row->exists;
            $row->save();
            if ($originLog) {
                $originLog->active_restore_archive_id = $row->id;
                $originLog->save();
            }
            $row->setRelation('department', $department);
            ArchiveItem::query()->where('archive_id', $row->id)->delete();
            foreach (array_chunk($box['items'], 250) as $chunk) {
                ArchiveItem::insert(array_map(fn (array $item): array => $item + ['archive_id' => $row->id, 'created_at' => $now, 'updated_at' => $now], $chunk));
            }
            $event = $isNew || $row->wasChanged('status') ? 'archive_submitted' : 'archive_updated';
            SystemEventStream::archives($event, [$row], $isNew ? [] : ['changes' => array_keys($row->getChanges())]);
            $saved[] = $row;
        }

        return $saved;
    }
}
