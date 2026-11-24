<?php

namespace App\Actions\Warehouse;

use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

/**
 * Geometry of the objects moved / resized / rotated on the canvas in one short IMMEDIATE transaction
 * (Laravel 7 sent one `update` request per object after every drag). Last write per object wins, as
 * in Laravel 7: every item carries absolute values, so two users moving different objects never
 * overwrite each other; the same object moved by both ends where the last drop put it.
 */
final class SaveLayoutPositions
{
    private const FIELDS = ['canvas_x', 'canvas_y', 'canvas_width', 'canvas_height', 'rotation_angle', 'orientation'];

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<int, string> new `updated_at` per id (the edit modal's optimistic lock token)
     */
    public function handle(array $items): array
    {
        $stamps = DB::transaction(function () use ($items): array {
            $now = now();
            $stamps = [];
            foreach ($items as $item) {
                $values = array_filter(array_intersect_key($item, array_flip(self::FIELDS)), fn ($v): bool => $v !== null);
                $updated = WarehouseLocation::query()->whereKey($item['id'])->update($values + ['updated_at' => $now]);
                if ($updated === 1) {
                    $stamps[(int) $item['id']] = $now->toDateTimeString();
                }
            }

            return $stamps;
        });

        $codes = WarehouseLocation::query()->whereKey(array_keys($stamps))->pluck('rack_code')->implode(', ');
        ActivityLogger::log('LAYOUT_OBJECT_UPDATE', 'Memperbarui posisi / geometri '.count($stamps)." objek pada denah: {$codes}.", 'LAYOUT_GUDANG',
            ['items' => $items], count($stamps) === 1 ? $codes : null);

        return $stamps;
    }
}
