<?php

namespace App\Actions\Master;

use App\Models\NumberingFormat;
use App\Models\User;
use App\Services\ActivityLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Tambah / simpan Format Penomoran Box (Laravel 7 NumberingFormatController@store / @update):
 * one active format at a time; the admin may set the counter.
 * New guard: the counter moves while boxes are numbered (NumberingService::generate), so an
 * edit form opened before that (`original_counter` differs from the database) is refused
 * instead of rolling the counter back and re-issuing box numbers.
 */
class SaveNumberingFormat
{
    /**
     * @param  array{name: string, pattern: string, current_counter: int|string, padding: int|string, is_active?: bool|string|null, original_counter?: int|string|null}  $data
     */
    public function handle(User $user, array $data, ?NumberingFormat $format = null): NumberingFormat
    {
        $active = filter_var($data['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $values = ['name' => $data['name'], 'pattern' => $data['pattern'], 'current_counter' => (int) $data['current_counter'], 'padding' => (int) $data['padding'], 'is_active' => $active];
        $old = $format?->only(['name', 'pattern', 'current_counter', 'padding', 'is_active']);

        $format = DB::transaction(function () use ($format, $values, $data, $active): NumberingFormat {
            $format ??= new NumberingFormat;
            $current = $format->exists ? (int) NumberingFormat::query()->whereKey($format->id)->value('current_counter') : null;
            if ($current !== null && isset($data['original_counter']) && $current !== (int) $data['original_counter'] && $current !== $values['current_counter']) {
                throw new DomainException('Konflik Perubahan: nilai counter sudah berubah (box baru telah diberi nomor) sejak halaman dibuka. Muat ulang halaman lalu simpan lagi.');
            }
            if ($active) {
                NumberingFormat::query()->when($format->exists, fn ($q) => $q->whereKeyNot($format->id))->update(['is_active' => false]);
            }
            $format->forceFill($values)->save();

            return $format;
        });

        if ($old === null) {
            ActivityLogger::log('MASTER_NUMBERING_CREATE', "Menambahkan pola format penomoran: {$format->name} ({$format->pattern})", 'master',
                $format->only(['id', 'pattern', 'padding', 'is_active']), (string) $format->id, $user);
        } else {
            ActivityLogger::log('MASTER_NUMBERING_UPDATE', "Memperbarui format penomoran: {$format->name} ({$format->pattern})", 'master',
                ['id' => $format->id, 'old' => $old, 'new' => $values], (string) $format->id, $user);
        }

        return $format;
    }
}
