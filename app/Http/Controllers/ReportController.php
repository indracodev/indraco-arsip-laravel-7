<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    /**
     * Display the main Report Center dashboard.
     */
    public function index(Request $request)
    {
        $activeType = $request->query('type', 'master_departments');
        $departments = Department::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('reports.index', compact('activeType', 'departments', 'warehouses'));
    }

    /**
     * Fetch report dataset in JSON format for live preview.
     */
    public function data(Request $request, $type)
    {
        $data = $this->getReportData($type, $request);
        return response()->json($data);
    }

    /**
     * Display printable / PDF-ready view.
     */
    public function print(Request $request, $type)
    {
        $report = $this->getReportData($type, $request);
        $autoPrint = $request->boolean('auto_print', false);
        
        return view('reports.print', [
            'type' => $type,
            'report' => $report,
            'autoPrint' => $autoPrint,
            'printedAt' => Carbon::now()->isoFormat('D MMMM Y, HH:mm') . ' WIB',
            'printedBy' => auth()->user() ? auth()->user()->name : 'System Administrator',
        ]);
    }

    /**
     * Export report data as CSV spreadsheet.
     */
    public function exportCsv(Request $request, $type)
    {
        $report = $this->getReportData($type, $request);
        $filename = 'Laporan_' . str_replace(' ', '_', $report['title']) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($report) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // Title & Metadata
            fputcsv($handle, ['PT INDRACO - DOCUMENT MANAGEMENT SYSTEM']);
            fputcsv($handle, [strtoupper($report['title'])]);
            fputcsv($handle, ['Dicetak Pada:', Carbon::now()->isoFormat('D MMMM Y, HH:mm') . ' WIB']);
            fputcsv($handle, ['Dicetak Oleh:', auth()->user() ? auth()->user()->name : 'System Administrator']);
            fputcsv($handle, []); // Empty row

            // Table Headers
            fputcsv($handle, $report['columns']);

            // Table Rows
            foreach ($report['rows'] as $index => $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Core Data Aggregator for all 7 DMS Reports.
     */
    protected function getReportData($type, Request $request)
    {
        $deptFilter = $request->query('department_id');
        $whFilter = $request->query('warehouse_id');
        $search = $request->query('q');

        switch ($type) {
            // 1. LAPORAN DEPARTEMEN MASTER
            case 'master_departments':
                $query = Department::with(['subDepartments', 'picUsers'])->orderBy('code');
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('code', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                    });
                }
                $depts = $query->get();

                $rows = [];
                $totalSub = 0;
                $totalPic = 0;

                foreach ($depts as $idx => $d) {
                    $pics = $d->picUsers->map(function($u) {
                        return $u->name . ' (' . $u->email . ')';
                    })->implode('; ');

                    $subCount = $d->subDepartments->count();
                    $picCount = $d->picUsers->count();
                    $totalSub += $subCount;
                    $totalPic += $picCount;

                    $rows[] = [
                        'no' => $idx + 1,
                        'code' => $d->code,
                        'name' => $d->name,
                        'description' => $d->description ?: '-',
                        'retention_years' => $d->retention_years . ' Tahun',
                        'sub_count' => $subCount . ' Unit',
                        'pic_list' => $pics ?: 'Belum Ada PIC',
                        'status' => $d->is_active ? 'Aktif' : 'Non-Aktif',
                    ];
                }

                return [
                    'id' => 'master_departments',
                    'title' => 'Laporan Departemen Master',
                    'subtitle' => 'Daftar Seluruh Unit Kerja Departemen Induk, Sub-Unit, PIC Resmi, dan Standar Retensi Arsip',
                    'columns' => ['No', 'Kode Dept', 'Nama Departemen', 'Deskripsi / Fungsi Unit', 'Standar Retensi', 'Sub-Unit', 'PIC Terdaftar', 'Status'],
                    'rows' => $rows,
                    'summary' => [
                        ['label' => 'Total Departemen', 'value' => count($depts) . ' Dept', 'icon' => 'building-2', 'color' => 'purple'],
                        ['label' => 'Total Sub-Departemen', 'value' => $totalSub . ' Unit', 'icon' => 'layers', 'color' => 'blue'],
                        ['label' => 'Total PIC Akun', 'value' => $totalPic . ' Akun', 'icon' => 'user-check', 'color' => 'emerald'],
                        ['label' => 'Rata-rata Retensi', 'value' => ($depts->count() ? round($depts->avg('retention_years'), 1) : 0) . ' Tahun', 'icon' => 'clock', 'color' => 'amber'],
                    ],
                ];

            // 2. LAPORAN USER
            case 'users':
                $query = User::with('department')->orderBy('role')->orderBy('name');
                if ($deptFilter) {
                    $query->where('department_id', $deptFilter);
                }
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                    });
                }
                $users = $query->get();

                $rows = [];
                $adminCount = 0;
                $gudangCount = 0;
                $picCount = 0;

                foreach ($users as $idx => $u) {
                    if ($u->isSuperAdmin()) $adminCount++;
                    elseif ($u->isPicGudang()) $gudangCount++;
                    else $picCount++;

                    $rows[] = [
                        'no' => $idx + 1,
                        'name' => $u->name,
                        'email' => $u->email,
                        'role' => $u->role_label,
                        'department' => $u->department ? ($u->department->code . ' - ' . $u->department->name) : 'Semua Departemen (Global)',
                        'phone' => $u->phone ?: '-',
                        'created_at' => $u->created_at ? $u->created_at->format('d/m/Y') : '-',
                        'status' => 'Aktif',
                    ];
                }

                return [
                    'id' => 'users',
                    'title' => 'Laporan Pengguna & Hak Akses (User Master)',
                    'subtitle' => 'Daftar Kredensial Pengguna Sistem DMS, Peran Akses (Role), dan Penugasan Departemen',
                    'columns' => ['No', 'Nama Pengguna', 'Alamat Email', 'Peran / Role', 'Departemen Terkait', 'No Telepon', 'Terdaftar', 'Status Akun'],
                    'rows' => $rows,
                    'summary' => [
                        ['label' => 'Total Pengguna', 'value' => count($users) . ' User', 'icon' => 'users', 'color' => 'blue'],
                        ['label' => 'Super Admin', 'value' => $adminCount . ' Akun', 'icon' => 'shield-check', 'color' => 'purple'],
                        ['label' => 'PIC Gudang Arsip', 'value' => $gudangCount . ' Akun', 'icon' => 'warehouse', 'color' => 'amber'],
                        ['label' => 'PIC Departemen', 'value' => $picCount . ' Akun', 'icon' => 'briefcase', 'color' => 'emerald'],
                    ],
                ];

            // 3. LAPORAN MASTER GUDANG
            case 'master_warehouses':
                $query = Warehouse::with(['locations' => function($q) {
                    $q->where('location_type', 'rack');
                }])->orderBy('code');

                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('code', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%")
                          ->orWhere('address', 'like', "%{$search}%");
                    });
                }
                $warehouses = $query->get();

                $rows = [];
                $totalRacks = 0;
                $totalCapacity = 0;

                foreach ($warehouses as $idx => $wh) {
                    $rackCount = $wh->locations->count();
                    $whCapacity = $wh->locations->sum('box_capacity') ?: ($rackCount * 100);
                    $totalRacks += $rackCount;
                    $totalCapacity += $whCapacity;

                    $rows[] = [
                        'no' => $idx + 1,
                        'code' => $wh->code,
                        'name' => $wh->name,
                        'address' => $wh->address ?: 'Kawasan Industri Indraco',
                        'rack_count' => $rackCount . ' Unit Rak',
                        'total_capacity' => number_format($whCapacity) . ' Box',
                        'status' => ($wh->is_active !== false) ? 'Aktif' : 'Non-Aktif',
                    ];
                }

                return [
                    'id' => 'master_warehouses',
                    'title' => 'Laporan Master Gedung Gudang Depo',
                    'subtitle' => 'Data Induk Gedung Penyimpanan Arsip Fisik PT Indraco, Lokasi Sektor, dan Kapasitas Terpasang',
                    'columns' => ['No', 'Kode Gudang', 'Nama Gedung Gudang', 'Lokasi / Alamat Fisik', 'Total Rak Terpasang', 'Kapasitas Penyimpanan', 'Status Operasional'],
                    'rows' => $rows,
                    'summary' => [
                        ['label' => 'Total Gedung Gudang', 'value' => count($warehouses) . ' Gedung', 'icon' => 'warehouse', 'color' => 'amber'],
                        ['label' => 'Total Rak Terpasang', 'value' => $totalRacks . ' Unit Rak', 'icon' => 'grid', 'color' => 'blue'],
                        ['label' => 'Total Kapasitas Box', 'value' => number_format($totalCapacity) . ' Box', 'icon' => 'package', 'color' => 'emerald'],
                        ['label' => 'Gudang Aktif', 'value' => $warehouses->where('is_active', '!==', false)->count() . ' Gedung', 'icon' => 'check-circle-2', 'color' => 'purple'],
                    ],
                ];

            // 4. LAPORAN MASTER RAK
            case 'master_racks':
                $query = WarehouseLocation::where('location_type', 'rack')
                    ->with(['warehouse', 'assignedDepartment', 'bookedBy'])
                    ->orderBy('warehouse_id')
                    ->orderBy('rack_code');

                if ($whFilter) {
                    $query->where('warehouse_id', $whFilter);
                }
                if ($deptFilter) {
                    $query->where('assigned_department_id', $deptFilter);
                }
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('rack_code', 'like', "%{$search}%")
                          ->orWhere('room_sector', 'like', "%{$search}%");
                    });
                }
                $racks = $query->get();

                $rows = [];
                $assignedCount = 0;
                $lockedCount = 0;

                foreach ($racks as $idx => $r) {
                    if ($r->assigned_department_id) $assignedCount++;
                    if ($r->is_locked || $r->is_fat_locked) $lockedCount++;

                    $rows[] = [
                        'no' => $idx + 1,
                        'rack_code' => $r->rack_code,
                        'warehouse' => $r->warehouse ? $r->warehouse->name : '-',
                        'sector' => $r->room_sector ?: ($r->warehouse ? $r->warehouse->code : '-'),
                        'assigned_dept' => $r->assignedDepartment ? ($r->assignedDepartment->code . ' - ' . $r->assignedDepartment->name) : 'Umum / Public Slot',
                        'capacity' => ($r->box_capacity ?: 100) . ' Box (5 LVL)',
                        'box_type' => $r->box_type ?: 'TB 30g Standar',
                        'dimensions' => ($r->canvas_width ?: 16) . ' x ' . ($r->canvas_height ?: 85) . ' px',
                        'coordinates' => 'X:' . ($r->canvas_x ?: 0) . ', Y:' . ($r->canvas_y ?: 0),
                        'status' => ($r->is_locked || $r->is_fat_locked) ? 'Terkunci (Lock)' : ($r->is_booked ? 'Dibooking' : 'Normal / Aktif'),
                    ];
                }

                return [
                    'id' => 'master_racks',
                    'title' => 'Laporan Master Unit Rak Storage',
                    'subtitle' => 'Daftar Spesifikasi Teknis Rak Arsip, Dimensi Kanvas 2D, Alokasi Departemen, dan Konfigurasi Slot',
                    'columns' => ['No', 'Kode Rak', 'Gedung Gudang', 'Sektor', 'Alokasi Departemen', 'Kapasitas', 'Tipe Box', 'Dimensi 2D', 'Posisi (X,Y)', 'Status Rak'],
                    'rows' => $rows,
                    'summary' => [
                        ['label' => 'Total Master Rak', 'value' => count($racks) . ' Unit Rak', 'icon' => 'layers', 'color' => 'blue'],
                        ['label' => 'Rak Teralokasi', 'value' => $assignedCount . ' Unit', 'icon' => 'building-2', 'color' => 'purple'],
                        ['label' => 'Rak Umum / Bebas', 'value' => (count($racks) - $assignedCount) . ' Unit', 'icon' => 'unlock', 'color' => 'emerald'],
                        ['label' => 'Rak Terkunci / FAT', 'value' => $lockedCount . ' Unit', 'icon' => 'lock', 'color' => 'amber'],
                    ],
                ];

            // 5. LAPORAN PENGGUNAAN GUDANG
            case 'warehouse_usage':
                $warehouses = Warehouse::with(['locations' => function($q) {
                    $q->where('location_type', 'rack')->with('archives');
                }])->orderBy('code')->get();

                $rows = [];
                $grandTotalCap = 0;
                $grandTotalUsed = 0;
                $grandTotalExpired = 0;

                foreach ($warehouses as $idx => $wh) {
                    $whRacks = $wh->locations;
                    $whCap = $whRacks->sum('box_capacity') ?: ($whRacks->count() * 100);
                    
                    // Count archives in this warehouse
                    $whUsed = $whRacks->sum('current_box_count');
                    
                    // Check expired archives
                    $expiredCount = 0;
                    foreach ($whRacks as $rk) {
                        $expiredCount += $rk->archives->filter(function($a) {
                            return $a->retention_date && Carbon::parse($a->retention_date)->isPast();
                        })->count();
                    }

                    $emptySlots = max(0, $whCap - $whUsed);
                    $occupancy = $whCap > 0 ? round(($whUsed / $whCap) * 100, 1) : 0;

                    $grandTotalCap += $whCap;
                    $grandTotalUsed += $whUsed;
                    $grandTotalExpired += $expiredCount;

                    $statusBadge = 'Rendah (<50%)';
                    if ($occupancy >= 95) $statusBadge = 'Kritis (>=95%)';
                    elseif ($occupancy >= 80) $statusBadge = 'Padat (80-94%)';
                    elseif ($occupancy >= 50) $statusBadge = 'Sedang (50-79%)';

                    $rows[] = [
                        'no' => $idx + 1,
                        'code' => $wh->code,
                        'name' => $wh->name,
                        'rack_count' => $whRacks->count() . ' Rak',
                        'capacity' => number_format($whCap) . ' Box',
                        'used' => number_format($whUsed) . ' Box',
                        'expired' => number_format($expiredCount) . ' Box',
                        'empty' => number_format($emptySlots) . ' Box',
                        'occupancy' => $occupancy . '%',
                        'status' => $statusBadge,
                    ];
                }

                $grandOccupancy = $grandTotalCap > 0 ? round(($grandTotalUsed / $grandTotalCap) * 100, 1) : 0;

                return [
                    'id' => 'warehouse_usage',
                    'title' => 'Laporan Penggunaan & Utilisasi Gedung Gudang',
                    'subtitle' => 'Analisis Tingkat Keterisian Ruang Depo Gudang, Kapasitas Terisi, Box Expired, dan Slot Kosong',
                    'columns' => ['No', 'Kode Gudang', 'Nama Gedung', 'Total Rak', 'Kapasitas Maks', 'Box Terisi', 'Box Expired', 'Sisa Slot Kosong', 'Okupansi (%)', 'Status Utilisasi'],
                    'rows' => $rows,
                    'summary' => [
                        ['label' => 'Total Kapasitas Nasional', 'value' => number_format($grandTotalCap) . ' Box', 'icon' => 'database', 'color' => 'blue'],
                        ['label' => 'Total Box Terisi', 'value' => number_format($grandTotalUsed) . ' Box', 'icon' => 'package-check', 'color' => 'emerald'],
                        ['label' => 'Sisa Slot Kosong', 'value' => number_format(max(0, $grandTotalCap - $grandTotalUsed)) . ' Box', 'icon' => 'inbox', 'color' => 'purple'],
                        ['label' => 'Rata-rata Okupansi', 'value' => $grandOccupancy . '%', 'icon' => 'pie-chart', 'color' => 'amber'],
                    ],
                ];

            // 6. LAPORAN PENGGUNAAN RAK
            case 'rack_usage':
                $query = WarehouseLocation::where('location_type', 'rack')
                    ->with(['warehouse', 'assignedDepartment', 'archives'])
                    ->orderBy('warehouse_id')
                    ->orderBy('rack_code');

                if ($whFilter) {
                    $query->where('warehouse_id', $whFilter);
                }
                if ($deptFilter) {
                    $query->where('assigned_department_id', $deptFilter);
                }
                if ($search) {
                    $query->where('rack_code', 'like', "%{$search}%");
                }
                $racks = $query->get();

                $rows = [];
                $totalBoxCount = 0;
                $totalExpiredBox = 0;
                $fullRackCount = 0;

                foreach ($racks as $idx => $r) {
                    $cap = $r->box_capacity ?: 100;
                    $used = $r->current_box_count ?: $r->archives->count();
                    $expired = $r->archives->filter(function($a) {
                        return $a->retention_date && Carbon::parse($a->retention_date)->isPast();
                    })->count();

                    $empty = max(0, $cap - $used);
                    $occupancy = $cap > 0 ? round(($used / $cap) * 100, 1) : 0;

                    $totalBoxCount += $used;
                    $totalExpiredBox += $expired;
                    if ($occupancy >= 95) $fullRackCount++;

                    $statusKepadatan = 'Kosong (0%)';
                    if ($occupancy >= 95) $statusKepadatan = 'Penuh (100%)';
                    elseif ($occupancy >= 80) $statusKepadatan = 'Hampir Penuh (>80%)';
                    elseif ($occupancy > 0) $statusKepadatan = 'Terisi Sebagian';

                    $rows[] = [
                        'no' => $idx + 1,
                        'rack_code' => $r->rack_code,
                        'warehouse' => $r->warehouse ? $r->warehouse->name : '-',
                        'department' => $r->assignedDepartment ? $r->assignedDepartment->code : 'Umum',
                        'capacity' => $cap . ' Box',
                        'used' => $used . ' Box',
                        'expired' => $expired . ' Box',
                        'empty' => $empty . ' Slot',
                        'occupancy' => $occupancy . '%',
                        'status' => $statusKepadatan,
                    ];
                }

                return [
                    'id' => 'rack_usage',
                    'title' => 'Laporan Penggunaan & Kepadatan Rak Storage',
                    'subtitle' => 'Detail Utilisasi Tiap Unit Rak (100 Slot Box), Keterisian Slot, Box Expired, dan Sisa Kapasitas',
                    'columns' => ['No', 'Kode Rak', 'Gedung Gudang', 'Departemen', 'Kapasitas', 'Box Terisi', 'Box Expired', 'Sisa Slot Kosong', 'Okupansi (%)', 'Status Kepadatan'],
                    'rows' => $rows,
                    'summary' => [
                        ['label' => 'Total Rak Diinspeksi', 'value' => count($racks) . ' Unit', 'icon' => 'grid', 'color' => 'blue'],
                        ['label' => 'Total Box Tersimpan', 'value' => number_format($totalBoxCount) . ' Box', 'icon' => 'package', 'color' => 'emerald'],
                        ['label' => 'Box Retensi Habis', 'value' => number_format($totalExpiredBox) . ' Box', 'icon' => 'alert-triangle', 'color' => 'rose'],
                        ['label' => 'Rak Status Penuh', 'value' => $fullRackCount . ' Unit', 'icon' => 'alert-circle', 'color' => 'amber'],
                    ],
                ];

            // 7. LAPORAN RAK PENUH BERDASARKAN LAYOUT 2D
            case 'full_racks_2d':
                $query = WarehouseLocation::where('location_type', 'rack')
                    ->with(['warehouse', 'assignedDepartment', 'archives']);

                if ($whFilter) {
                    $query->where('warehouse_id', $whFilter);
                }
                $allRacks = $query->get();

                // Identify racks with high occupancy (>= 80% or >= 80 boxes out of 100) or full
                $fullRacks = $allRacks->filter(function($r) {
                    $cap = $r->box_capacity ?: 100;
                    $used = $r->current_box_count ?: $r->archives->count();
                    $pct = $cap > 0 ? ($used / $cap) * 100 : 0;
                    return $pct >= 80 || $used >= 80;
                })->values();

                // If no racks meet >=80%, show top fullest racks for actionable intelligence
                if ($fullRacks->isEmpty()) {
                    $fullRacks = $allRacks->sortByDesc(function($r) {
                        return $r->current_box_count ?: $r->archives->count();
                    })->take(10)->values();
                }

                $rows = [];
                foreach ($fullRacks as $idx => $r) {
                    $cap = $r->box_capacity ?: 100;
                    $used = $r->current_box_count ?: $r->archives->count();
                    $empty = max(0, $cap - $used);
                    $pct = $cap > 0 ? round(($used / $cap) * 100, 1) : 0;

                    $recommendation = 'Kapasitas Masih Memadai';
                    if ($pct >= 95) {
                        $recommendation = '⚠️ Kritis: Segera tambah unit rak baru atau musnahkan arsip expired';
                    } elseif ($pct >= 80) {
                        $recommendation = '⚡ Perhatian: Jadwalkan mutasi box arsip ke rak sektor sekunder';
                    }

                    $rows[] = [
                        'no' => $idx + 1,
                        'rack_code' => $r->rack_code,
                        'warehouse' => $r->warehouse ? $r->warehouse->name : '-',
                        'sector' => $r->room_sector ?: ($r->warehouse ? $r->warehouse->code : '-'),
                        'coordinates_2d' => 'X: ' . ($r->canvas_x ?: 0) . ', Y: ' . ($r->canvas_y ?: 0) . ' (Rotasi ' . ($r->rotation_angle ?: 0) . '°)',
                        'department' => $r->assignedDepartment ? $r->assignedDepartment->name : 'Umum / Public',
                        'capacity' => $cap . ' Box',
                        'used' => $used . ' Box',
                        'empty' => $empty . ' Slot',
                        'occupancy' => $pct . '%',
                        'action_recommendation' => $recommendation,
                    ];
                }

                return [
                    'id' => 'full_racks_2d',
                    'title' => 'Laporan Rak Penuh Berdasarkan Layout 2D',
                    'subtitle' => 'Identifikasi Visual Rak Kritis (>80% Kapasitas), Pemetaan Koordinat Kanvas 2D, dan Rekomendasi Alokasi',
                    'columns' => ['No', 'Kode Rak Penuh', 'Gedung Gudang', 'Sektor', 'Koordinat Layout 2D', 'Alokasi Departemen', 'Kapasitas', 'Terisi', 'Sisa Slot', 'Okupansi (%)', 'Rekomendasi Tindakan'],
                    'rows' => $rows,
                    'summary' => [
                        ['label' => 'Total Rak Kritis / Penuh', 'value' => count($rows) . ' Unit', 'icon' => 'alert-octagon', 'color' => 'rose'],
                        ['label' => 'Total Rak Terpetakan 2D', 'value' => $allRacks->count() . ' Unit', 'icon' => 'map-pin', 'color' => 'blue'],
                        ['label' => 'Rata-rata Keterisian', 'value' => (count($rows) ? round(collect($rows)->avg(function($r){ return floatval($r['occupancy']); }), 1) : 0) . '%', 'icon' => 'activity', 'color' => 'amber'],
                        ['label' => 'Status Gudang', 'value' => count($rows) > 0 ? 'Perlu Pemantauan' : 'Optimal', 'icon' => 'shield-alert', 'color' => 'emerald'],
                    ],
                ];

            default:
                abort(404, 'Tipe laporan tidak ditemukan');
        }
    }
}
