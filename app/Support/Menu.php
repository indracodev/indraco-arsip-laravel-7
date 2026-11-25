<?php

namespace App\Support;

use App\Models\User;

/**
 * Main (top) menu per role: exactly the Laravel 7 MDI form list (labels and order), now as
 * normal pages (ASK 1). Sources (Laravel 7, read-only):
 * - pic_dept: resources/views/layouts/partials/pic_desktop_scripts.blade.php:47-75
 *   ("Draft Pengajuan Box Baru" is hidden from the tab bar there and opened with the header
 *   button "Baru (F2)", pic_desktop_header.blade.php:20-23 - here: the topbar button).
 * - admin / pic_gudang: resources/views/layouts/partials/desktop_layout_scripts.blade.php:56-128
 *   (items wrapped in isSuperAdmin() are admin only).
 */
final class Menu
{
    /**
     * @return list<array{route: string, label: string, group?: string}>
     */
    public static function for(User $user): array
    {
        if ($user->role === User::ROLE_PIC_DEPT) {
            $code = $user->loadMissing('department')->department->code ?? '';

            return [
                ['route' => 'archives.index', 'label' => trim("[Form 1] Katalog Arsip {$code}")],
                ['route' => 'borrowings.index', 'label' => '[Form 2] Penarikan Berkas'],
                ['route' => 'destructions.index', 'label' => '[Form 3] Pengajuan Perpanjangan & Pemusnahan'],
            ];
        }

        $admin = $user->isAdmin();

        return array_values(array_filter([
            ['route' => 'dashboard', 'label' => 'Dashboard Overview'],
            $admin ? ['route' => 'master.departments', 'label' => 'Master Departemen', 'group' => 'Master'] : null,
            ['route' => 'master.warehouses', 'label' => 'Master Gudang & Rak', 'group' => 'Master'],
            ['route' => 'master.warehouses.layout', 'label' => 'Layout Gudang 2D', 'group' => 'Master'],
            $admin ? ['route' => 'master.numbering', 'label' => 'Format Penomoran Box', 'group' => 'Master'] : null,
            $admin ? ['route' => 'master.users', 'label' => 'Kelola User & Hak Akses', 'group' => 'Master'] : null,
            ['route' => 'archives.index', 'label' => 'Katalog Arsip', 'group' => 'Arsip'],
            ['route' => 'borrowings.index', 'label' => 'Penarikan Berkas', 'group' => 'Arsip'],
            ['route' => 'destructions.index', 'label' => 'Pemusnahan & Retensi', 'group' => 'Arsip'],
            $admin ? ['route' => 'logs.index', 'label' => 'Log History', 'group' => 'Laporan & Sistem'] : null,
            $admin ? ['route' => 'reports.index', 'label' => 'Report', 'group' => 'Laporan & Sistem'] : null,
            $admin ? ['route' => 'diagnostics.index', 'label' => 'Live Monitor LAN & User', 'group' => 'Laporan & Sistem'] : null,
        ]));
    }

    /**
     * The same items cut into consecutive groups (top bar separators, drawer headings).
     * The groups follow the Laravel 7 order, so flattening gives back exactly self::for().
     * PIC Departemen items have no group (3 items inline).
     *
     * @return list<array{label: string|null, items: list<array{route: string, label: string, group?: string}>}>
     */
    public static function grouped(User $user): array
    {
        $groups = [];
        foreach (self::for($user) as $item) {
            $label = $item['group'] ?? null;
            $last = array_key_last($groups);
            if ($label !== null && $last !== null && $groups[$last]['label'] === $label) {
                $groups[$last]['items'][] = $item;
            } else {
                $groups[] = ['label' => $label, 'items' => [$item]];
            }
        }

        return $groups;
    }

    /** Label of the menu entry for a route (the placeholder page title). */
    public static function labelFor(User $user, ?string $routeName): ?string
    {
        foreach (self::for($user) as $item) {
            if ($item['route'] === $routeName) {
                return $item['label'];
            }
        }

        return null;
    }
}
