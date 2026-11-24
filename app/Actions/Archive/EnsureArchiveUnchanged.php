<?php

namespace App\Actions\Archive;

use App\Models\Archive;
use DomainException;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Optimistic lock for the edit form (PROJECT_RULES_SQLITE §3.5.f), run inside the write
 * transaction: the row must not have changed since the form was opened (`original_updated_at`),
 * and a draft must still be a draft (a second "Ajukan" of the same draft is refused).
 */
final class EnsureArchiveUnchanged
{
    public const CONFLICT = 'Konflik Perubahan: Data arsip ini baru saja diubah oleh pengguna lain saat Anda sedang membuka form. Silakan muat ulang (refresh) halaman untuk melihat versi terbaru sebelum menyimpan kembali.';

    public const NOT_DRAFT = 'Draft ini sudah diajukan atau tidak lagi berstatus draft. Silakan muat ulang (refresh) halaman.';

    public static function check(Archive $archive, ?string $originalUpdatedAt): void
    {
        $current = Archive::query()->whereKey($archive->id)->first(['id', 'status', 'updated_at']);
        if (! $current) {
            throw new DomainException('Data arsip tidak ditemukan (mungkin sudah dihapus).');
        }
        try {
            $changed = $current->updated_at && Carbon::parse((string) $originalUpdatedAt)->lt($current->updated_at);
        } catch (Throwable) {
            $changed = true;
        }
        if ($changed) {
            throw new DomainException(self::CONFLICT);
        }
        if ($current->status !== $archive->status) {
            throw new DomainException(self::NOT_DRAFT);
        }
    }
}
