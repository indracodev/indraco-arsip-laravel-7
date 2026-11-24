<?php

namespace App\Actions\Warehouse;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use App\Support\WarehouseLayout;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Tambah / Edit object Gudang (room) or Rak on the Layout Gudang 2D (Laravel 7 storeLocation /
 * updateLocation): a new rack gets its 100 slots, a room keeps a gudang with its code; only the admin
 * sets the allowed departments. Edit with `expected_updated_at` is refused when someone else saved
 * the object after it was opened (optimistic lock); canvas moves are last-write per object.
 *
 * Fix vs Laravel 7: a new room belongs to its own gudang and a new rack to its sector's gudang
 * (Laravel 7 put both on the first gudang).
 */
final class SaveLayoutObject
{
    /** Columns that may be cleared; any other null means "not sent". */
    private const NULLABLE = ['custom_color', 'room_sector'];

    /** @param  array<string, mixed>  $data */
    public function handle(User $user, ?WarehouseLocation $location, array $data): WarehouseLocation
    {
        $expected = $data['expected_updated_at'] ?? null;
        unset($data['expected_updated_at']);
        $data = array_filter($data, fn ($value, string $key): bool => $value !== null || in_array($key, self::NULLABLE, true), ARRAY_FILTER_USE_BOTH);
        $data = $this->departments($user, $data);

        $saved = DB::transaction(function () use ($location, $data, $expected): WarehouseLocation {
            if ($location === null) {
                return $this->create($data);
            }
            $fresh = WarehouseLocation::query()->lockForUpdate()->findOrFail($location->id);
            if ($expected !== null && (string) $fresh->getAttribute('updated_at') !== $expected) {
                throw new DomainException("Data object '{$fresh->rack_code}' sudah diubah user lain setelah Anda membukanya. Muat ulang halaman lalu ulangi.");
            }
            $oldCode = $fresh->rack_code;
            $fresh->update($data);
            if ($fresh->location_type === 'room' && $oldCode !== $fresh->rack_code) {
                Warehouse::query()->where('code', $oldCode)->update(['code' => $fresh->rack_code, 'name' => 'Gudang '.$fresh->rack_code]);
            }
            if ($fresh->location_type === 'room' && array_key_exists('is_fat_locked', $data)) {
                Warehouse::query()->where('code', $fresh->rack_code)->update(['is_fat_locked' => $data['is_fat_locked']]);
            }
            WarehouseLayout::ensureSlots($fresh);

            return $fresh;
        });

        $this->log($location === null, $saved);

        return $saved;
    }

    /** @param  array<string, mixed>  $data */
    private function create(array $data): WarehouseLocation
    {
        $data += ['shelf_code' => 'S1', 'box_capacity' => 100, 'total_sap' => 5, 'boxes_per_sap' => 20, 'box_type' => 'TB 30g',
            'is_locked' => true, 'is_fat_locked' => false, 'orientation' => $data['canvas_width'] >= $data['canvas_height'] ? 'horizontal' : 'vertical'];
        $data['current_box_count'] = 0;
        if ($data['location_type'] === 'room') {
            $warehouse = Warehouse::query()->firstOrCreate(['code' => $data['rack_code']], [
                'name' => 'Gudang '.$data['rack_code'],
                'address' => 'Kawasan Industri Indraco - Sektor '.($data['room_sector'] ?? $data['rack_code']),
                'is_fat_locked' => (bool) $data['is_fat_locked'],
            ]);
            $data['warehouse_id'] = $warehouse->id;
        }
        $data['warehouse_id'] ??= WarehouseLocation::query()->where('location_type', 'room')->where('room_sector', $data['room_sector'] ?? '')->value('warehouse_id')
            ?? Warehouse::query()->orderBy('id')->value('id') ?? throw new DomainException('Belum ada master gudang. Tambah gudang terlebih dahulu.');
        $location = WarehouseLocation::create($data);
        WarehouseLayout::ensureSlots($location);

        return $location;
    }

    /**
     * Laravel 7: only the Super Admin sets the department restriction; the first id is mirrored to
     * `assigned_department_id`.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function departments(User $user, array $data): array
    {
        if (! $user->isAdmin()) {
            unset($data['assigned_department_ids']);

            return $data;
        }
        if (array_key_exists('assigned_department_ids', $data)) {
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) $data['assigned_department_ids']))));
            $data['assigned_department_ids'] = $ids;
            $data['assigned_department_id'] = $ids[0] ?? null;
        }

        return $data;
    }

    private function log(bool $created, WarehouseLocation $location): void
    {
        $type = $location->location_type === 'room' ? 'Gudang' : 'Rak';
        $created
            ? ActivityLogger::log('LAYOUT_OBJECT_CREATE', "Menambahkan objek {$type} '{$location->rack_code}' pada denah layout 2D (Ukuran: {$location->canvas_width}x{$location->canvas_height}px).", 'LAYOUT_GUDANG',
                $location->only(['id', 'location_type', 'rack_code', 'room_sector']), $location->rack_code)
            : ActivityLogger::log('LAYOUT_OBJECT_UPDATE', "Memperbarui data / geometri objek '{$location->rack_code}' pada denah (W: {$location->canvas_width}px, H: {$location->canvas_height}px, Lock: ".($location->is_locked ? 'Locked' : 'Unlocked').').', 'LAYOUT_GUDANG',
                $location->only(['id', 'rack_code', 'canvas_x', 'canvas_y', 'canvas_width', 'canvas_height', 'is_locked']), $location->rack_code);
    }
}
