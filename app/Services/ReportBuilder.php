<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * The 7 reports of "Pusat Laporan & Dokumen PDF" (Laravel 7 ReportController::getReportData: same
 * titles, columns, cell texts and summary cards). Each report is a generator: rows are yielded one
 * by one (CSV export streams them; master tables use lazy()) and the summary cards are its return
 * value (Generator::getReturn() after the rows were read). Rack occupancy: ReportUsage.
 */
final class ReportBuilder
{
    /** type => [tab name, title, subtitle, columns] (Laravel 7 order and texts). */
    public const TYPES = [
        'master_departments' => ['Laporan Departemen Master', 'Laporan Departemen Master', 'Daftar Seluruh Unit Kerja Departemen Induk, Sub-Unit, PIC Resmi, dan Standar Retensi Arsip',
            ['No', 'Kode Dept', 'Nama Departemen', 'Deskripsi / Fungsi Unit', 'Standar Retensi', 'Sub-Unit', 'PIC Terdaftar', 'Status']],
        'users' => ['Laporan User', 'Laporan Pengguna & Hak Akses (User Master)', 'Daftar Kredensial Pengguna Sistem DMS, Peran Akses (Role), dan Penugasan Departemen',
            ['No', 'Nama Pengguna', 'Alamat Email', 'Peran / Role', 'Departemen Terkait', 'No Telepon', 'Terdaftar', 'Status Akun']],
        'master_warehouses' => ['Laporan Master Gudang', 'Laporan Master Gedung Gudang Depo', 'Data Induk Gedung Penyimpanan Arsip Fisik PT Indraco, Lokasi Sektor, dan Kapasitas Terpasang',
            ['No', 'Kode Gudang', 'Nama Gedung Gudang', 'Lokasi / Alamat Fisik', 'Total Rak Terpasang', 'Kapasitas Penyimpanan', 'Status Operasional']],
        'master_racks' => ['Laporan Master Rak', 'Laporan Master Unit Rak Storage', 'Daftar Spesifikasi Teknis Rak Arsip, Dimensi Kanvas 2D, Alokasi Departemen, dan Konfigurasi Slot',
            ['No', 'Kode Rak', 'Gedung Gudang', 'Sektor', 'Alokasi Departemen', 'Kapasitas', 'Tipe Box', 'Dimensi 2D', 'Posisi (X,Y)', 'Status Rak']],
        'warehouse_usage' => ['Laporan Penggunaan Gudang', 'Laporan Penggunaan & Utilisasi Gedung Gudang', 'Analisis Tingkat Keterisian Ruang Depo Gudang, Kapasitas Terisi, Box Expired, dan Slot Kosong',
            ['No', 'Kode Gudang', 'Nama Gedung', 'Total Rak', 'Kapasitas Maks', 'Box Terisi', 'Box Expired', 'Sisa Slot Kosong', 'Okupansi (%)', 'Status Utilisasi']],
        'rack_usage' => ['Laporan Penggunaan Rak', 'Laporan Penggunaan & Kepadatan Rak Storage', 'Detail Utilisasi Tiap Unit Rak (100 Slot Box), Keterisian Slot, Box Expired, dan Sisa Kapasitas',
            ['No', 'Kode Rak', 'Gedung Gudang', 'Departemen', 'Kapasitas', 'Box Terisi', 'Box Expired', 'Sisa Slot Kosong', 'Okupansi (%)', 'Status Kepadatan']],
        'full_racks_2d' => ['Laporan Rak Penuh (Layout 2D)', 'Laporan Rak Penuh Berdasarkan Layout 2D', 'Identifikasi Visual Rak Kritis (>80% Kapasitas), Pemetaan Koordinat Kanvas 2D, dan Rekomendasi Alokasi',
            ['No', 'Kode Rak Penuh', 'Gedung Gudang', 'Sektor', 'Koordinat Layout 2D', 'Alokasi Departemen', 'Kapasitas', 'Terisi', 'Sisa Slot', 'Okupansi (%)', 'Rekomendasi Tindakan']],
    ];

    /** Reports with the department / warehouse filter (Laravel 7 report page). */
    public const DEPARTMENT_FILTER = ['users', 'master_racks', 'rack_usage'];

    public const WAREHOUSE_FILTER = ['master_racks', 'rack_usage', 'full_racks_2d'];

    public function __construct(private ReportUsage $usage) {}

    /**
     * @param  array{department_id?: int|string|null, warehouse_id?: int|string|null, q?: string|null}  $filters
     * @return Generator<int, list<string|int>, mixed, list<array{0: string, 1: string}>>
     */
    public function rows(string $type, array $filters): Generator
    {
        $filters = array_filter($filters, fn ($value): bool => $value !== null && $value !== '');

        return match ($type) {
            'master_departments' => $this->departments($filters),
            'users' => $this->users($filters),
            'master_warehouses' => $this->warehouses($filters),
            'master_racks' => $this->racks($filters),
            'warehouse_usage' => $this->usage->warehouses(),
            'rack_usage' => $this->usage->racks($filters),
            'full_racks_2d' => $this->usage->fullRacks($filters),
            default => throw new InvalidArgumentException("Tipe laporan tidak dikenal: {$type}"),
        };
    }

    /**
     * @param  array<string, mixed>  $f
     * @return Generator<int, list<string|int>, mixed, list<array{0: string, 1: string}>>
     */
    private function departments(array $f): Generator
    {
        $query = Department::query()->withCount('subDepartments')->with('picUsers:id,name,email,department_id')->orderBy('code')
            ->when($f['q'] ?? null, fn (Builder $q, $s) => $q->where(fn (Builder $q) => $q->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")));
        [$n, $sub, $pic, $years] = [0, 0, 0, 0];
        foreach ($query->lazy(200) as $d) {
            $pics = $d->picUsers->map(fn (User $u): string => "{$u->name} ({$u->email})")->implode('; ');
            [$n, $sub, $pic, $years] = [$n + 1, $sub + $d->sub_departments_count, $pic + $d->picUsers->count(), $years + (int) $d->retention_years];
            yield [$n, $d->code, $d->name, $d->description ?: '-', $d->retention_years.' Tahun', $d->sub_departments_count.' Unit', $pics ?: 'Belum Ada PIC', $d->is_active ? 'Aktif' : 'Non-Aktif'];
        }

        return [['Total Departemen', "{$n} Dept"], ['Total Sub-Departemen', "{$sub} Unit"], ['Total PIC Akun', "{$pic} Akun"], ['Rata-rata Retensi', ($n ? round($years / $n, 1) : 0).' Tahun']];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return Generator<int, list<string|int>, mixed, list<array{0: string, 1: string}>>
     */
    private function users(array $f): Generator
    {
        $query = User::query()->with('department:id,code,name')->orderBy('role')->orderBy('name')
            ->when($f['department_id'] ?? null, fn (Builder $q, $id) => $q->where('department_id', $id))
            ->when($f['q'] ?? null, fn (Builder $q, $s) => $q->where(fn (Builder $q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")));
        $count = ['admin' => 0, 'pic_gudang' => 0, 'pic_dept' => 0];
        $n = 0;
        foreach ($query->lazy(200) as $u) {
            $count[$u->role] = ($count[$u->role] ?? 0) + 1;
            yield [++$n, $u->name, $u->email, $u->roleLabel(), $u->department ? $u->department->code.' - '.$u->department->name : 'Semua Departemen (Global)',
                $u->phone ?: '-', $u->created_at?->format('d/m/Y') ?? '-', 'Aktif'];
        }

        return [['Total Pengguna', "{$n} User"], ['Super Admin', $count['admin'].' Akun'], ['PIC Gudang Arsip', $count['pic_gudang'].' Akun'], ['PIC Departemen', $count['pic_dept'].' Akun']];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return Generator<int, list<string|int>, mixed, list<array{0: string, 1: string}>>
     */
    private function warehouses(array $f): Generator
    {
        $racks = WarehouseLocation::query()->where('location_type', 'rack')->get(['warehouse_id', 'box_capacity'])->groupBy('warehouse_id');
        $query = Warehouse::query()->orderBy('code')
            ->when($f['q'] ?? null, fn (Builder $q, $s) => $q->where(fn (Builder $q) => $q->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")->orWhere('address', 'like', "%{$s}%")));
        [$n, $rackTotal, $capTotal, $active] = [0, 0, 0, 0];
        foreach ($query->lazy(200) as $wh) {
            $whRacks = $racks->get($wh->id, collect());
            $capacity = (int) $whRacks->sum('box_capacity') ?: $whRacks->count() * 100;
            [$n, $rackTotal, $capTotal, $active] = [$n + 1, $rackTotal + $whRacks->count(), $capTotal + $capacity, $active + ($wh->is_active ? 1 : 0)];
            yield [$n, $wh->code, $wh->name, $wh->address ?: 'Kawasan Industri Indraco', $whRacks->count().' Unit Rak', number_format($capacity).' Box', $wh->is_active ? 'Aktif' : 'Non-Aktif'];
        }

        return [['Total Gedung Gudang', "{$n} Gedung"], ['Total Rak Terpasang', "{$rackTotal} Unit Rak"], ['Total Kapasitas Box', number_format($capTotal).' Box'], ['Gudang Aktif', "{$active} Gedung"]];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return Generator<int, list<string|int>, mixed, list<array{0: string, 1: string}>>
     */
    private function racks(array $f): Generator
    {
        $query = ReportUsage::rackQuery($f)->with(['warehouse:id,code,name', 'assignedDepartment:id,code,name'])
            ->when($f['department_id'] ?? null, fn (Builder $q, $id) => $q->where('assigned_department_id', $id))
            ->when($f['q'] ?? null, fn (Builder $q, $s) => $q->where(fn (Builder $q) => $q->where('rack_code', 'like', "%{$s}%")->orWhere('room_sector', 'like', "%{$s}%")));
        [$n, $assigned, $locked] = [0, 0, 0];
        foreach ($query->lazy(200) as $r) {
            /** @var WarehouseLocation $r */
            $isLocked = $r->is_locked || $r->is_fat_locked;
            [$n, $assigned, $locked] = [$n + 1, $assigned + ($r->assigned_department_id ? 1 : 0), $locked + ($isLocked ? 1 : 0)];
            yield [$n, $r->rack_code, $r->warehouse->name ?? '-', $r->room_sector ?: ($r->warehouse->code ?? '-'),
                $r->assignedDepartment ? $r->assignedDepartment->code.' - '.$r->assignedDepartment->name : 'Umum / Public Slot',
                ($r->box_capacity ?: 100).' Box (5 LVL)', $r->box_type ?: 'TB 30g Standar', ($r->canvas_width ?: 16).' x '.($r->canvas_height ?: 85).' px',
                'X:'.($r->canvas_x ?: 0).', Y:'.($r->canvas_y ?: 0), $isLocked ? 'Terkunci (Lock)' : ($r->is_booked ? 'Dibooking' : 'Normal / Aktif')];
        }

        return [['Total Master Rak', "{$n} Unit Rak"], ['Rak Teralokasi', "{$assigned} Unit"], ['Rak Umum / Bebas', ($n - $assigned).' Unit'], ['Rak Terkunci / FAT', "{$locked} Unit"]];
    }

    /**
     * CSV cell safe for Excel (rules/60-security.md): values starting with = + - @ (or tab/CR) are
     * prefixed with '. A lone "-" (the "no value" placeholder) cannot be a formula and stays as is.
     */
    public static function csvCell(string|int|float|null $value): string
    {
        $value = (string) $value;

        return strlen($value) > 1 && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
