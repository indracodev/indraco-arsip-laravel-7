<?php

namespace App\Support;

use App\Models\User;

/**
 * Sidebar menu per role: exactly the Laravel 7 MDI form list (labels and order), now as
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
     * @return list<array{route: string, label: string}>
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
            $admin ? ['route' => 'master.departments', 'label' => 'Master Departemen'] : null,
            ['route' => 'master.warehouses', 'label' => 'Master Gudang & Rak'],
            ['route' => 'master.warehouses.layout', 'label' => 'Layout Gudang 2D'],
            $admin ? ['route' => 'master.numbering', 'label' => 'Format Penomoran Box'] : null,
            $admin ? ['route' => 'master.users', 'label' => 'Kelola User & Hak Akses'] : null,
            ['route' => 'archives.index', 'label' => 'Katalog Arsip'],
            ['route' => 'borrowings.index', 'label' => 'Penarikan Berkas'],
            ['route' => 'destructions.index', 'label' => 'Pemusnahan & Retensi'],
            $admin ? ['route' => 'logs.index', 'label' => 'Log History'] : null,
            $admin ? ['route' => 'reports.index', 'label' => 'Report'] : null,
            $admin ? ['route' => 'diagnostics.index', 'label' => 'Live Monitor LAN & User'] : null,
        ]));
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
