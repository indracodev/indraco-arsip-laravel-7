<?php

namespace App\Support;

/**
 * Laravel 7 ActivityLog module_label / action_label / action badge colour, for Log History.
 */
final class ActivityLogLabel
{
    /** Filter "Filter Modul" (Laravel 7 AuditLogController $modulesList). */
    public const MODULE_FILTER = [
        'all' => 'Semua Modul', 'auth' => 'Otentikasi & Sesi (Login/Logout)', 'archive' => 'Dokumen & Arsip',
        'borrowing' => 'Peminjaman Dokumen', 'destruction' => 'Pemusnahan & Retensi', 'warehouse' => 'Gudang & Rak Fisik',
        'layout' => 'Layout Gudang 2D', 'user' => 'Manajemen Pengguna', 'master' => 'Master Data Sistem', 'system' => 'Sistem & API',
    ];

    /**
     * Stored `module` values per filter key. Laravel 7 compared the lowercase key with the stored
     * uppercase value (`AUTH`, `DOKUMEN_ARSIP`, ...), so the module filter never matched anything.
     */
    public const MODULE_VALUES = [
        'auth' => ['AUTH'], 'archive' => ['DOKUMEN_ARSIP', 'ARCHIVE', 'IMPORT_EXCEL'],
        'borrowing' => ['PENARIKAN_BERKAS', 'BORROWING', 'PEMINJAMAN'], 'destruction' => ['PEMUSNAHAN_RETENSI', 'DESTRUCTION'],
        'warehouse' => ['GUDANG', 'WAREHOUSE', 'OVERRIDE'], 'layout' => ['LAYOUT_GUDANG', 'LAYOUT'],
        'user' => ['USER_MANAGEMENT', 'USER'], 'master' => ['MASTER', 'MASTER_DATA', 'MASTER_DEPT'], 'system' => ['SETTINGS', 'SYSTEM'],
    ];

    private const MODULES = [
        'penarikan_berkas' => 'Peminjaman', 'settings' => 'Sistem', 'master_dept' => 'Master Data', 'import_excel' => 'Katalog Arsip',
        'auth' => 'Otentikasi & Sesi', 'archive' => 'Katalog Arsip', 'dokumen_arsip' => 'Katalog Arsip',
        'borrowing' => 'Peminjaman', 'peminjaman' => 'Peminjaman', 'destruction' => 'Pemusnahan & Retensi',
        'pemusnahan_retensi' => 'Pemusnahan & Retensi', 'warehouse' => 'Gudang Fisik', 'gudang' => 'Gudang Fisik',
        'layout' => 'Layout Gudang 2D', 'layout_gudang' => 'Layout Gudang 2D', 'user' => 'Kelola Pengguna',
        'user_management' => 'Kelola Pengguna', 'master' => 'Master Data', 'master_data' => 'Master Data', 'system' => 'Sistem',
    ];

    private const ACTIONS = [
        'LOGIN' => 'Login Masuk', 'LOGIN_FAILED' => 'Gagal Login', 'LOGOUT' => 'Logout Keluar',
        'IMPERSONATE_START' => 'Mulai Impersonasi', 'IMPERSONATE_LEAVE' => 'Selesai Impersonasi',
        'USER_CREATE' => 'Tambah Pengguna', 'USER_UPDATE' => 'Ubah Pengguna', 'USER_DELETE' => 'Hapus Pengguna',
        'ARCHIVE_CREATE' => 'Registrasi Berkas Baru', 'ARCHIVE_PRINT' => 'Cetak Stiker Barcode', 'ARCHIVE_VERIFY' => 'Verifikasi Fisik Gudang',
        'ARCHIVE_REJECT' => 'Penolakan Berkas', 'ARCHIVE_CHECKIN' => 'Check-in Rak Gudang', 'BORROW_CREATE' => 'Pengajuan Pinjam',
        'BORROW_DEPT_APPROVE' => 'Approval Head Dept', 'BORROW_APPROVE' => 'Approval Gudang', 'BORROW_DISPATCH' => 'Serah Terima Berkas',
        'BORROW_RETURN' => 'Pengembalian Berkas', 'RETENTION_EXTEND' => 'Perpanjang Retensi', 'DESTRUCTION_PROPOSE' => 'Pengajuan Musnah',
        'DESTRUCTION_BAP_PRINT' => 'Cetak BAP Musnah', 'LAYOUT_OBJECT_CREATE' => 'Tambah Objek 2D', 'LAYOUT_OBJECT_UPDATE' => 'Ubah Objek 2D',
        'LAYOUT_OBJECT_DELETE' => 'Hapus Objek 2D', 'LAYOUT_SLOT_BOOK' => 'Booking Slot Rak', 'LAYOUT_SLOT_UNBOOK' => 'Batal Booking Rak',
        'SLOT_ASSIGN' => 'Alokasi Slot Arsip', 'SLOT_UNASSIGN' => 'Pelepasan Slot Arsip', 'SLOT_STATUS_TOGGLE' => 'Ubah Status Aktif Slot',
        'MASTER_DEPT_CREATE' => 'Tambah Departemen', 'MASTER_DEPT_UPDATE' => 'Ubah Departemen', 'MASTER_DEPT_DELETE' => 'Hapus Departemen',
        'MASTER_SUBDEPT_CREATE' => 'Tambah Sub-Dept', 'MASTER_SUBDEPT_UPDATE' => 'Ubah Sub-Dept', 'MASTER_SUBDEPT_DELETE' => 'Hapus Sub-Dept',
        'MASTER_WAREHOUSE_CREATE' => 'Tambah Gudang', 'MASTER_WAREHOUSE_UPDATE' => 'Ubah Gudang', 'MASTER_WAREHOUSE_DELETE' => 'Hapus Gudang',
        'MASTER_WAREHOUSE_STATUS_TOGGLE' => 'Ubah Status Aktif Gudang', 'MASTER_RACK_CREATE' => 'Tambah Rak Fisik',
        'MASTER_RACK_DELETE' => 'Hapus Rak Fisik', 'MASTER_NUMBERING_CREATE' => 'Tambah Format No.', 'MASTER_NUMBERING_UPDATE' => 'Ubah Format No.',
    ];

    public static function module(?string $module): string
    {
        return self::MODULES[strtolower((string) $module)] ?? strtoupper($module ?: 'UMUM');
    }

    public static function action(string $action): string
    {
        return self::ACTIONS[$action] ?? ucwords(strtolower(str_replace('_', ' ', $action)));
    }

    /** Badge tone (Laravel 7 action_badge_class colours mapped to the status tones). */
    public static function tone(string $action): string
    {
        return match (true) {
            (bool) preg_match('/DELETE|DESTROY|UNASSIGN|REJECT|FAILED|CLEARED/', $action) => 'danger',
            (bool) preg_match('/CREATE|CHECKIN|ASSIGN/', $action), $action === 'LOGIN' => 'success',
            (bool) preg_match('/UPDATE|APPROVE|VERIFY|EXTEND/', $action) => 'info',
            (bool) preg_match('/IMPERSONATE|BOOK/', $action) => 'warning',
            default => 'neutral',
        };
    }
}
