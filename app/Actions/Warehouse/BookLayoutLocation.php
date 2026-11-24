<?php

namespace App\Actions\Warehouse;

use App\Models\Department;
use App\Models\User;
use App\Models\WarehouseLocation;
use App\Services\ActivityLogger;
use App\Support\WarehouseLayout;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * "Booking Slot Tempat Arsip" (Laravel 7 bookLocation): reserves a rack for a department with a
 * note. The rack's allowed departments (else its room's) must include the department.
 *
 * Fixes vs Laravel 7: rooms cannot be booked, and a rack already booked is refused inside the
 * transaction (Laravel 7 overwrote another user's booking when two booked at once).
 */
final class BookLayoutLocation
{
    public function handle(User $user, WarehouseLocation $location, int $departmentId, string $notes): string
    {
        if ($location->location_type === 'room') {
            throw new DomainException('Booking hanya untuk object rak.');
        }
        $department = Department::query()->findOrFail($departmentId);
        $allowed = WarehouseLayout::allowedIds($location);
        if ($allowed === [] && ($room = WarehouseLayout::parentRoom($location))) {
            $allowed = WarehouseLayout::allowedIds($room);
        }
        if ($allowed !== [] && ! in_array($department->id, $allowed, true)) {
            $codes = Department::query()->whereIn('id', $allowed)->pluck('code')->implode(', ');
            throw new DomainException("Akses Ditolak: Lokasi ini dikhususkan untuk [{$codes}]. {$department->code} ({$department->name}) tidak diizinkan mem-booking lokasi ini.");
        }

        $booked = DB::transaction(fn (): int => WarehouseLocation::query()->whereKey($location->id)->where('is_booked', false)->update([
            'is_booked' => true, 'assigned_department_id' => $department->id, 'booked_by_user_id' => $user->id,
            'booking_notes' => $notes, 'updated_at' => now(),
        ]));
        if ($booked !== 1) {
            throw new DomainException("Rak '{$location->rack_code}' sudah di-booking user lain. Muat ulang halaman.");
        }

        ActivityLogger::log('LAYOUT_SLOT_BOOK', "Booking slot rak '{$location->rack_code}' untuk alokasi departemen {$department->code} ({$department->name}). Catatan: {$notes}", 'LAYOUT_GUDANG',
            ['location_id' => $location->id, 'rack_code' => $location->rack_code, 'department_id' => $department->id, 'booking_notes' => $notes], $location->rack_code, $user);

        return "Slot rak '{$location->rack_code}' berhasil di-booking untuk departemen {$department->code}.";
    }
}
