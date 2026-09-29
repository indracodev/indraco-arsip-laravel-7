<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'department_id',
        'action',
        'module',
        'description',
        'reference_id',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get Human-Readable Module Label
     */
    public function getModuleLabelAttribute(): string
    {
        $m = strtolower($this->module ?? '');
        switch ($m) {
            case 'auth':
                return 'Otentikasi & Sesi';
            case 'archive':
            case 'dokumen_arsip':
                return 'Katalog Arsip';
            case 'borrowing':
            case 'peminjaman':
                return 'Peminjaman';
            case 'destruction':
            case 'pemusnahan_retensi':
                return 'Pemusnahan & Retensi';
            case 'warehouse':
            case 'gudang':
                return 'Gudang Fisik';
            case 'layout':
            case 'layout_gudang':
                return 'Layout Gudang 2D';
            case 'user':
            case 'user_management':
                return 'Kelola Pengguna';
            case 'master':
            case 'master_data':
                return 'Master Data';
            case 'system':
                return 'Sistem';
            default:
                return strtoupper($this->module ?? 'UMUM');
        }
    }

    /**
     * Get Lucide Icon for Module
     */
    public function getModuleIconAttribute(): string
    {
        $m = strtolower($this->module ?? '');
        switch ($m) {
            case 'auth':
                return 'shield';
            case 'archive':
            case 'dokumen_arsip':
                return 'folder-archive';
            case 'borrowing':
            case 'peminjaman':
                return 'file-symlink';
            case 'destruction':
            case 'pemusnahan_retensi':
                return 'file-x';
            case 'warehouse':
            case 'gudang':
                return 'warehouse';
            case 'layout':
            case 'layout_gudang':
                return 'map';
            case 'user':
            case 'user_management':
                return 'users';
            case 'master':
            case 'master_data':
                return 'database';
            default:
                return 'activity';
        }
    }

    /**
     * Get CSS Badge Class for Module
     */
    public function getModuleBadgeClassAttribute(): string
    {
        $m = strtolower($this->module ?? '');
        switch ($m) {
            case 'auth':
                return 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800';
            case 'archive':
            case 'dokumen_arsip':
                return 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800';
            case 'borrowing':
            case 'peminjaman':
                return 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800';
            case 'destruction':
            case 'pemusnahan_retensi':
                return 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800';
            case 'warehouse':
            case 'gudang':
                return 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800';
            case 'layout':
            case 'layout_gudang':
                return 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
            case 'user':
            case 'user_management':
                return 'bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800';
            case 'master':
            case 'master_data':
                return 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700';
            default:
                return 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700';
        }
    }

    /**
     * Get Human-Readable Action Label
     */
    public function getActionLabelAttribute(): string
    {
        $map = [
            'LOGIN' => 'Login Masuk',
            'LOGIN_FAILED' => 'Gagal Login',
            'LOGOUT' => 'Logout Keluar',
            'IMPERSONATE_START' => 'Mulai Impersonasi',
            'IMPERSONATE_LEAVE' => 'Selesai Impersonasi',
            'USER_CREATE' => 'Tambah Pengguna',
            'USER_UPDATE' => 'Ubah Pengguna',
            'USER_DELETE' => 'Hapus Pengguna',
            'ARCHIVE_CREATE' => 'Registrasi Berkas Baru',
            'ARCHIVE_PRINT' => 'Cetak Stiker Barcode',
            'ARCHIVE_VERIFY' => 'Verifikasi Fisik Gudang',
            'ARCHIVE_REJECT' => 'Penolakan Berkas',
            'ARCHIVE_CHECKIN' => 'Check-in Rak Gudang',
            'BORROW_CREATE' => 'Pengajuan Pinjam',
            'BORROW_DEPT_APPROVE' => 'Approval Head Dept',
            'BORROW_APPROVE' => 'Approval Gudang',
            'BORROW_DISPATCH' => 'Serah Terima Berkas',
            'BORROW_RETURN' => 'Pengembalian Berkas',
            'RETENTION_EXTEND' => 'Perpanjang Retensi',
            'DESTRUCTION_PROPOSE' => 'Pengajuan Musnah',
            'DESTRUCTION_BAP_PRINT' => 'Cetak BAP Musnah',
            'LAYOUT_OBJECT_CREATE' => 'Tambah Objek 2D',
            'LAYOUT_OBJECT_UPDATE' => 'Ubah Objek 2D',
            'LAYOUT_OBJECT_DELETE' => 'Hapus Objek 2D',
            'LAYOUT_SLOT_BOOK' => 'Booking Slot Rak',
            'LAYOUT_SLOT_UNBOOK' => 'Batal Booking Rak',
            'SLOT_ASSIGN' => 'Alokasi Slot Arsip',
            'SLOT_UNASSIGN' => 'Pelepasan Slot Arsip',
            'SLOT_STATUS_TOGGLE' => 'Ubah Status Aktif Slot',
            'MASTER_DEPT_CREATE' => 'Tambah Departemen',
            'MASTER_DEPT_UPDATE' => 'Ubah Departemen',
            'MASTER_DEPT_DELETE' => 'Hapus Departemen',
            'MASTER_SUBDEPT_CREATE' => 'Tambah Sub-Dept',
            'MASTER_SUBDEPT_UPDATE' => 'Ubah Sub-Dept',
            'MASTER_SUBDEPT_DELETE' => 'Hapus Sub-Dept',
            'MASTER_WAREHOUSE_CREATE' => 'Tambah Gudang',
            'MASTER_WAREHOUSE_UPDATE' => 'Ubah Gudang',
            'MASTER_WAREHOUSE_DELETE' => 'Hapus Gudang',
            'MASTER_WAREHOUSE_STATUS_TOGGLE' => 'Ubah Status Aktif Gudang',
            'MASTER_RACK_CREATE' => 'Tambah Rak Fisik',
            'MASTER_RACK_DELETE' => 'Hapus Rak Fisik',
            'MASTER_NUMBERING_CREATE' => 'Tambah Format No.',
            'MASTER_NUMBERING_UPDATE' => 'Ubah Format No.',
        ];

        return $map[$this->action] ?? ucwords(strtolower(str_replace('_', ' ', $this->action)));
    }

    /**
     * Get CSS Badge Class for Action
     */
    public function getActionBadgeClassAttribute(): string
    {
        $act = $this->action;

        if (strpos($act, 'CREATE') !== false || strpos($act, 'CHECKIN') !== false || strpos($act, 'ASSIGN') !== false && strpos($act, 'UNASSIGN') === false) {
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
        }
        if (strpos($act, 'UPDATE') !== false || strpos($act, 'APPROVE') !== false || strpos($act, 'VERIFY') !== false || strpos($act, 'EXTEND') !== false) {
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-500/30';
        }
        if (strpos($act, 'DELETE') !== false || strpos($act, 'DESTROY') !== false || strpos($act, 'UNASSIGN') !== false || strpos($act, 'REJECT') !== false || strpos($act, 'FAILED') !== false) {
            return 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/30';
        }
        if ($act === 'LOGIN') {
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
        }
        if (strpos($act, 'IMPERSONATE') !== false) {
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30';
        }
        if ($act === 'LOGOUT') {
            return 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border-slate-500/30';
        }
        if (strpos($act, 'PRINT') !== false) {
            return 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/30';
        }
        if (strpos($act, 'BOOK') !== false) {
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30';
        }

        return 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border-slate-500/30';
    }
}
