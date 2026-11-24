<?php

namespace App\Actions\Archive;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ArchiveAttachments;
use App\Services\ArchiveBoxBuilder;
use App\Services\SystemEventStream;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Simpan Sementara (Draft)": the whole multi-box form becomes ONE `archives` row with status
 * `draft` and every box as JSON in content_description; all document lines go to that row
 * (Laravel 7 HandlesMultiBoxCreation/Update, draft branch). No required fields.
 */
final class SaveArchiveDraft
{
    public function __construct(private ArchiveBoxBuilder $builder, private ArchiveAttachments $attachments) {}

    /**
     * @param  array<string, mixed>  $data  validated input
     * @param  array{scan: UploadedFile|null, file: UploadedFile|null, remove_scan: bool, remove_file: bool}  $uploads
     * @return string success message
     */
    public function handle(User $user, Department $department, array $data, array $uploads, ?Archive $draft = null, ?string $originalUpdatedAt = null): string
    {
        $boxes = array_values($data['boxes'] ?? []) ?: [[
            'title' => $draft ? ($draft->title ?: 'Draft Box 1') : 'Draft Box 1',
            'items' => [['document_name' => 'Draft Dokumen', 'notes' => 'Draft']],
        ]];
        $handover = ! empty($data['tgl_penyerahan']) ? Carbon::parse($data['tgl_penyerahan'])
            : ($draft->tgl_penyerahan ?? now());
        $fallbackTitle = ($draft?->title) ?: 'Draft Pengajuan Arsip '.now()->format('Y/m');
        $attributes = $this->builder->draftAttributes($boxes, $department, $handover, $fallbackTitle) + [
            'company_name' => ($data['company_name'] ?? null) ?: ArchiveBoxBuilder::DEFAULT_COMPANY,
            'tgl_penyerahan' => $handover->format('Y-m-d'),
        ];
        $items = $this->builder->items(array_merge(...array_map(fn (array $box): array => array_values($box['items'] ?? []), $boxes)), false);

        $files = $this->attachments->prepare($draft, $uploads['scan'], $uploads['file'], $uploads['remove_scan'], $uploads['remove_file']);
        try {
            $archive = DB::transaction(function () use ($user, $department, $draft, $originalUpdatedAt, $attributes, $files, $items): Archive {
                if ($draft) {
                    EnsureArchiveUnchanged::check($draft, $originalUpdatedAt);
                }
                $archive = $draft ?? new Archive;
                $archive->fill($attributes + ['scan_input_form' => $files['scan_input_form'], 'file_path' => $files['file_path']]);
                $archive->department_id = $department->id;
                $archive->status = 'draft';
                if (! $draft) {
                    $archive->created_by_user_id = $user->id;
                }
                $archive->save();
                $archive->setRelation('department', $department);

                ArchiveItem::query()->where('archive_id', $archive->id)->delete();
                $now = now();
                foreach (array_chunk($items, 250) as $chunk) {
                    ArchiveItem::insert(array_map(fn (array $item): array => $item + ['archive_id' => $archive->id, 'created_at' => $now, 'updated_at' => $now], $chunk));
                }
                $draft
                    ? SystemEventStream::archives('archive_updated', [$archive], ['changes' => array_keys($archive->getChanges())])
                    : SystemEventStream::archives('archive_draft_created', [$archive]);

                return $archive;
            });
        } catch (Throwable $e) {
            $this->attachments->discard($files['new']);
            throw $e;
        }
        $this->attachments->deleteUnreferenced($files['replaced']);

        $count = count($boxes);
        if ($draft) {
            ActivityLogger::log('ARCHIVE_DRAFT_UPDATE', "Draft pengajuan {$count} box arsip diperbarui oleh {$user->name}.", 'DOKUMEN_ARSIP',
                ['archive_id' => $archive->id, 'boxes_count' => $count], $user->name, $user);

            return "Berhasil memperbarui draft pengajuan ({$count} Box) sebagai 1 data draft.";
        }
        ActivityLogger::log('ARCHIVE_DRAFT_SAVE', "Draft pengajuan {$count} box arsip disimpan sebagai 1 data oleh {$user->name}.", 'DOKUMEN_ARSIP',
            ['archive_id' => $archive->id, 'boxes_count' => $count, 'items_count' => count($items), 'department_id' => $department->id], $user->name, $user);

        return "Berhasil menyimpan {$count} box arsip sebagai 1 data draft sementara.";
    }
}
