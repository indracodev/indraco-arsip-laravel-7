<?php

namespace App\Support;

use App\Models\Archive;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * Display values of the archive detail page that Laravel 7 computed in archives/show.blade.php
 * and Archive accessors (full_slot_location, retention_duration_label, expiry badge, back link).
 */
final class ArchiveDetail
{
    /** Laravel 7 Archive::full_slot_location (needs location.warehouse and rackSlot loaded). */
    public static function slotLocation(Archive $archive): string
    {
        $location = $archive->location;
        if (! $location) {
            return 'Belum Ada Lokasi';
        }
        $prefix = ($location->warehouse->name ?? 'Gudang').' - '.($location->room_sector ? "[{$location->room_sector}] " : '').$location->rack_code;
        $slot = $archive->rackSlot;
        if (! $slot) {
            return $prefix.' / '.$location->getAttribute('shelf_code');
        }
        $sap = (int) $slot->getAttribute('sap_level');
        $top = $slot->getAttribute('layer') === 'top';
        $box = ($sap - 1) * 20 + ($top ? 10 : 0) + $slot->slot_number;

        return sprintf('%s / %s (Sap %d, %s - Slot %02d / Box #%d)', $prefix, $slot->slot_code, $sap, $top ? 'Baris Atas' : 'Baris Bawah', $slot->slot_number, $box);
    }

    /** Laravel 7 Archive::retention_duration_label ("N Tahun" / "N Bulan"). */
    public static function retentionLabel(Archive $archive): string
    {
        if (preg_match('/^(\d+)\s*(?:bulan|bln)/i', trim((string) ($archive->periode ?? $archive->periode_doc ?? '')), $m)) {
            $months = (int) $m[1];

            return $months >= 12 && $months % 12 === 0 ? ($months / 12).' Tahun' : $months.' Bulan';
        }
        foreach ([$archive->masa_simpan_custom, $archive->retention_years] as $years) {
            if ((int) $years > 0) {
                return $years.' Tahun';
            }
        }

        return '-';
    }

    /**
     * Laravel 7 retention badge under "Jatuh Tempo (Expiry)".
     *
     * @return array{text: string, tone: string}|null
     */
    public static function expiryBadge(Archive $archive): ?array
    {
        if (! $archive->retention_expiry_date) {
            return null;
        }
        $end = Carbon::parse($archive->retention_expiry_date)->endOfMonth()->endOfDay();
        $days = (int) Carbon::today()->diffInDays($end, false);

        return match (true) {
            now()->gt($end) => ['text' => 'Sudah Kadaluarsa (Lewat '.abs($days).' Hari)', 'tone' => 'danger'],
            $days <= 30 => ['text' => "Jatuh Tempo Bulan Ini (Sisa {$days} Hari)", 'tone' => 'warning'],
            default => ['text' => "Masa Simpan Aktif ({$days} Hari)", 'tone' => 'success'],
        };
    }

    /**
     * Laravel 7 "Proses Pemusnahan": in the warehouse and the expiry date within 90 days of today
     * either way (Carbon 2 diffInDays was absolute).
     */
    public static function canProposeDestruction(Archive $archive): bool
    {
        return $archive->status === 'in_warehouse' && $archive->retention_expiry_date
            && abs(Carbon::parse($archive->retention_expiry_date)->diffInDays(now())) <= 90;
    }

    /** Laravel 7 "Kembali" target from ?from=. */
    public static function backUrl(?string $from, User $user): string
    {
        $isPic = $user->role === User::ROLE_PIC_DEPT;
        $name = match ($from) {
            'borrowings' => 'borrowings.index',
            'destructions' => 'destructions.index',
            'warehouse', 'layout' => $isPic ? 'archives.index' : 'master.warehouses.layout',
            'warehouses' => $isPic ? 'archives.index' : 'master.warehouses',
            'dashboard' => 'dashboard',
            default => 'archives.index',
        };

        return route(Route::has($name) ? $name : 'archives.index');
    }

    /** Link of a module not rebuilt yet: its Laravel 7 route when it exists, else its placeholder page. */
    public static function moduleUrl(string $name, mixed $parameters, string $placeholder): string
    {
        return Route::has($name) ? route($name, $parameters) : route($placeholder);
    }
}
