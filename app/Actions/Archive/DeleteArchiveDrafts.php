<?php

namespace App\Actions\Archive;

use App\Models\Archive;
use App\Models\ArchiveItem;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ArchiveAttachments;
use App\Services\SystemEventStream;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Delete drafts with their document lines and files (Laravel 7 ArchiveController destroy and
 * batchDeleteDrafts). Only status `draft`; PIC Departemen only their own department (rows of
 * other departments are silently left out, as in Laravel 7). Files go after the commit.
 */
final class DeleteArchiveDrafts
{
    public function __construct(private ArchiveAttachments $attachments) {}

    /**
     * @param  list<int>  $ids
     * @return string success message
     */
    public function handle(User $user, array $ids, bool $single = false): string
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            throw new DomainException('Tidak ada data draft yang dipilih untuk dihapus.');
        }

        $drafts = DB::transaction(function () use ($user, $ids) {
            $drafts = Archive::query()->whereIn('id', $ids)->where('status', 'draft')
                ->when(! $user->isAdmin(), fn ($query) => $query->where('department_id', $user->department_id))
                ->get(['id', 'department_id', 'title', 'scan_input_form', 'file_path']);
            if ($drafts->isNotEmpty()) {
                ArchiveItem::query()->whereIn('archive_id', $drafts->modelKeys())->delete();
                Archive::query()->whereIn('id', $drafts->modelKeys())->delete();
                SystemEventStream::archives('archive_deleted', $drafts);
            }

            return $drafts;
        });
        if ($drafts->isEmpty()) {
            throw new DomainException($single ? 'Hanya data berstatus draft yang dapat dihapus.' : 'Tidak ada data draft valid yang dapat dihapus.');
        }
        $this->attachments->deleteUnreferenced($drafts->pluck('scan_input_form')->merge($drafts->pluck('file_path'))->all());

        if ($single) {
            $title = $drafts[0]->title ?: 'Draft Box Arsip';
            ActivityLogger::log('ARCHIVE_DRAFT_DELETE', "Menghapus data draft arsip '{$title}' oleh {$user->name}.", 'DOKUMEN_ARSIP',
                ['archive_id' => $drafts[0]->id, 'title' => $title], $title, $user);

            return "Data draft arsip '{$title}' berhasil dihapus.";
        }
        $count = $drafts->count();
        ActivityLogger::log('ARCHIVE_DRAFT_BATCH_DELETE', "Menghapus {$count} data draft arsip secara batch oleh {$user->name}.", 'DOKUMEN_ARSIP',
            ['deleted_ids' => $ids, 'count' => $count], "Batch {$count} Draft", $user);

        return "Berhasil menghapus {$count} data draft arsip.";
    }
}
