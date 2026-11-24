<?php

namespace App\Support;

use App\Models\Department;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data of the Layout Gudang 2D page (Laravel 7 WarehouseLayoutController@apiLayoutData), split so the
 * page never ships the 8,000 slots: the canvas gets the objects with slot counts, a rack's slots are
 * loaded when its grid opens. Gudang GA / IT objects stay admin-only (Laravel 7 filter).
 */
final class WarehouseLayout
{
    /** Archive statuses that occupy a slot (Laravel 7 `hasActiveArchive`). */
    public const ACTIVE = ['in_warehouse', 'approved_booked', 'pending_verification'];

    /** Laravel 7 FAT room sectors (also via the `is_fat_locked` flags). */
    private const FAT_SECTORS = ['R1', 'R2', 'RUANG 1', 'RUANG 2', 'SEKTOR R1', 'SEKTOR R2'];

    /** @param  Builder<WarehouseLocation>  $query */
    public static function visibleTo(Builder $query, bool $isAdmin): void
    {
        $query->when(! $isAdmin, fn ($q) => $q->whereNotIn('rack_code', WarehouseMaster::ADMIN_ONLY)
            ->where(fn ($q) => $q->whereNull('room_sector')->orWhereNotIn('room_sector', WarehouseMaster::ADMIN_ONLY)));
    }

    public static function isAdminOnly(WarehouseLocation $location): bool
    {
        return in_array($location->rack_code, WarehouseMaster::ADMIN_ONLY, true)
            || in_array($location->room_sector, WarehouseMaster::ADMIN_ONLY, true);
    }

    /**
     * Every object of the canvas with capacity and slot counts (no slots, no archives).
     *
     * @return list<array<string, mixed>>
     */
    public static function locations(bool $isAdmin): array
    {
        $query = WarehouseMaster::withBoxCounts(WarehouseLocation::query())
            ->withCount(['slots as slots_total', 'slots as slots_inactive' => fn ($q) => $q->where('is_active', false)])
            ->with(['warehouse:id,is_fat_locked', 'bookedBy:id,name'])->orderBy('id');
        self::visibleTo($query, $isAdmin);
        $states = self::slotStates();

        return $query->get()->map(function (WarehouseLocation $loc) use ($states): array {
            $count = WarehouseMaster::boxCount($loc);
            $capacity = (int) ($loc->box_capacity ?: 100);
            $state = $states[$loc->id] ?? ['filled' => 0, 'expired' => 0, 'near_exp' => 0];
            $total = max(100, (int) $loc->getAttribute('slots_total'));

            return $loc->only(['id', 'warehouse_id', 'rack_code', 'shelf_code', 'room_sector', 'custom_color', 'canvas_x', 'canvas_y',
                'canvas_width', 'canvas_height', 'orientation', 'booking_notes', 'assigned_department_id']) + [
                    'location_type' => $loc->getAttribute('location_type') ?? 'rack',
                    'box_capacity' => $capacity,
                    'current_box_count' => $count,
                    'capacity_percentage' => $capacity > 0 ? round($count / $capacity * 100, 1) : 0,
                    'rotation_angle' => (int) $loc->rotation_angle,
                    'is_locked' => (bool) ($loc->is_locked ?? true),
                    'is_fat_locked' => self::isFatLocked($loc),
                    'is_booked' => (bool) $loc->is_booked,
                    'booked_by_user' => $loc->bookedBy?->name,
                    'assigned_department_ids' => self::allowedIds($loc),
                    'stats' => $state + ['empty' => max(0, $total - (int) $loc->getAttribute('slots_inactive') - $state['filled'] - $state['expired'])],
                    'updated_at' => (string) $loc->getAttribute('updated_at'),
                ];
        })->all();
    }

    /** @return list<array{id: int, code: string, name: string}> */
    public static function departments(): array
    {
        return Department::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name'])
            ->map(fn (Department $d): array => ['id' => $d->id, 'code' => $d->code, 'name' => $d->name])->all();
    }

    /**
     * Laravel 7 WarehouseLocation::allowed_department_ids.
     *
     * @return list<int>
     */
    public static function allowedIds(WarehouseLocation $location): array
    {
        $ids = $location->getAttribute('assigned_department_ids');
        if (is_array($ids) && $ids !== []) {
            return array_values(array_filter(array_map('intval', $ids)));
        }

        return $location->assigned_department_id ? [(int) $location->assigned_department_id] : [];
    }

    /** Room of a rack: same sector (Laravel 7 parent room lookup). */
    public static function parentRoom(WarehouseLocation $location): ?WarehouseLocation
    {
        if ($location->location_type === 'room' || blank($location->room_sector)) {
            return null;
        }

        return WarehouseLocation::query()->where('location_type', 'room')->where('room_sector', $location->room_sector)->first();
    }

    /** 'expired' | 'near_exp' | null, Laravel 7 Archive::is_expired / is_near_exp (end of the expiry month, H-30). */
    public static function expiryState(mixed $expiry): ?string
    {
        if (! $expiry) {
            return null;
        }
        $end = Carbon::parse($expiry)->endOfMonth()->endOfDay();

        return match (true) {
            now()->gt($end) => 'expired',
            Carbon::today()->gte($end->copy()->subDays(30)->startOfDay()) => 'near_exp',
            default => null,
        };
    }

    /**
     * 100 standard slots (5 LVL x 20 box: per level bottom row then top row, code = rack letter + box
     * number), Laravel 7 WarehouseLocation::generateStandardSlots; only for a rack without slots.
     */
    public static function ensureSlots(WarehouseLocation $location): void
    {
        if ($location->location_type === 'room' || $location->slots()->exists()) {
            return;
        }
        $prefix = (string) preg_replace('/^(?:RAK-)?(?:R\d+-)?/i', '', $location->rack_code) ?: $location->rack_code;
        $now = now();
        $rows = [];
        for ($level = 1; $level <= 5; $level++) {
            foreach (['bottom' => 0, 'top' => 10] as $layer => $offset) {
                for ($i = 1; $i <= 10; $i++) {
                    $number = ($level - 1) * 20 + $offset + $i;
                    $rows[] = ['warehouse_location_id' => $location->id, 'sap_level' => $level, 'layer' => $layer, 'slot_number' => $number,
                        'slot_code' => $prefix.$number, 'status' => 'empty', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
                }
            }
        }
        WarehouseRackSlot::query()->insert($rows);
    }

    private static function isFatLocked(WarehouseLocation $loc): bool
    {
        $sector = strtoupper(trim((string) $loc->room_sector));

        return (bool) $loc->is_fat_locked || (bool) $loc->warehouse?->getAttribute('is_fat_locked') || in_array($sector, self::FAT_SECTORS, true);
    }

    /**
     * Filled / expired / near-expiry counts per rack from the occupied active slots only.
     *
     * @return array<int, array{filled: int, expired: int, near_exp: int}>
     */
    private static function slotStates(): array
    {
        $states = [];
        $rows = DB::table('warehouse_rack_slots as s')->join('archives as a', 'a.id', '=', 's.archive_id')
            ->where('s.is_active', true)->whereIn('a.status', self::ACTIVE)
            ->get(['s.warehouse_location_id as location_id', 'a.retention_expiry_date as expiry']);
        foreach ($rows as $row) {
            $state = self::expiryState($row->expiry);
            $states[$row->location_id] ??= ['filled' => 0, 'expired' => 0, 'near_exp' => 0];
            $states[$row->location_id][$state === 'expired' ? 'expired' : 'filled']++;
            if ($state === 'near_exp') {
                $states[$row->location_id]['near_exp']++;
            }
        }

        return $states;
    }
}
