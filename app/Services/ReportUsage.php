<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Occupancy reports 5-7 of ReportBuilder (Laravel 7 texts and thresholds). "Box Expired" = boxes in
 * the rack whose retention ended (end of the expiry month passed); Laravel 7 read a missing
 * `retention_date` attribute, so the column was always 0. Counted with one grouped query.
 */
final class ReportUsage
{
    /**
     * @param  array<string, mixed>  $f
     * @return Builder<WarehouseLocation>
     */
    public static function rackQuery(array $f): Builder
    {
        return WarehouseLocation::query()->where('location_type', 'rack')->orderBy('warehouse_id')->orderBy('rack_code')
            ->when($f['warehouse_id'] ?? null, fn (Builder $q, $id) => $q->where('warehouse_id', $id));
    }

    /** @return Generator<int, list<string|int>, mixed, list<array{0: string, 1: string}>> */
    public function warehouses(): Generator
    {
        $expired = $this->expiredPerRack();
        $racks = WarehouseLocation::query()->where('location_type', 'rack')->get(['id', 'warehouse_id', 'box_capacity', 'current_box_count'])->groupBy('warehouse_id');
        [$n, $capTotal, $usedTotal] = [0, 0, 0];
        foreach (Warehouse::query()->orderBy('code')->get() as $wh) {
            $whRacks = $racks->get($wh->id, collect());
            $cap = (int) $whRacks->sum('box_capacity') ?: $whRacks->count() * 100;
            $used = (int) $whRacks->sum('current_box_count');
            $pct = $cap > 0 ? round($used / $cap * 100, 1) : 0;
            [$n, $capTotal, $usedTotal] = [$n + 1, $capTotal + $cap, $usedTotal + $used];
            $status = match (true) {
                $pct >= 95 => 'Kritis (>=95%)',
                $pct >= 80 => 'Padat (80-94%)',
                $pct >= 50 => 'Sedang (50-79%)',
                default => 'Rendah (<50%)',
            };
            yield [$n, $wh->code, $wh->name, $whRacks->count().' Rak', number_format($cap).' Box', number_format($used).' Box',
                number_format($whRacks->sum(fn ($r) => $expired[$r->id] ?? 0)).' Box', number_format(max(0, $cap - $used)).' Box', $pct.'%', $status];
        }

        return [['Total Kapasitas Nasional', number_format($capTotal).' Box'], ['Total Box Terisi', number_format($usedTotal).' Box'],
            ['Sisa Slot Kosong', number_format(max(0, $capTotal - $usedTotal)).' Box'], ['Rata-rata Okupansi', ($capTotal > 0 ? round($usedTotal / $capTotal * 100, 1) : 0).'%']];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return Generator<int, list<string|int>, mixed, list<array{0: string, 1: string}>>
     */
    public function racks(array $f): Generator
    {
        $expired = $this->expiredPerRack();
        $query = self::rackQuery($f)->with(['warehouse:id,code,name', 'assignedDepartment:id,code,name'])->withCount('archives')
            ->when($f['department_id'] ?? null, fn (Builder $q, $id) => $q->where('assigned_department_id', $id))
            ->when($f['q'] ?? null, fn (Builder $q, $s) => $q->where('rack_code', 'like', "%{$s}%"));
        [$n, $boxes, $expiredTotal, $full] = [0, 0, 0, 0];
        foreach ($query->lazy(200) as $r) {
            [$cap, $used, $pct] = $this->fill($r);
            $exp = $expired[$r->id] ?? 0;
            [$n, $boxes, $expiredTotal, $full] = [$n + 1, $boxes + $used, $expiredTotal + $exp, $full + ($pct >= 95 ? 1 : 0)];
            $status = match (true) {
                $pct >= 95 => 'Penuh (100%)',
                $pct >= 80 => 'Hampir Penuh (>80%)',
                $pct > 0 => 'Terisi Sebagian',
                default => 'Kosong (0%)',
            };
            yield [$n, $r->rack_code, $r->warehouse->name ?? '-', $r->assignedDepartment->code ?? 'Umum', "{$cap} Box", "{$used} Box", "{$exp} Box", max(0, $cap - $used).' Slot', $pct.'%', $status];
        }

        return [['Total Rak Diinspeksi', "{$n} Unit"], ['Total Box Tersimpan', number_format($boxes).' Box'], ['Box Retensi Habis', number_format($expiredTotal).' Box'], ['Rak Status Penuh', "{$full} Unit"]];
    }

    /**
     * Racks >= 80% (or >= 80 boxes); none -> the 10 fullest (Laravel 7).
     *
     * @param  array<string, mixed>  $f
     * @return Generator<int, list<string|int>, mixed, list<array{0: string, 1: string}>>
     */
    public function fullRacks(array $f): Generator
    {
        $all = self::rackQuery($f)->with(['warehouse:id,code,name', 'assignedDepartment:id,code,name'])->withCount('archives')->get();
        $full = $all->filter(fn (WarehouseLocation $r): bool => $this->fill($r)[2] >= 80 || $this->fill($r)[1] >= 80)->values();
        if ($full->isEmpty()) {
            $full = $all->sortByDesc(fn (WarehouseLocation $r): int => $this->fill($r)[1])->take(10)->values();
        }
        $pctSum = 0;
        foreach ($full as $i => $r) {
            [$cap, $used, $pct] = $this->fill($r);
            $pctSum += $pct;
            $advice = match (true) {
                $pct >= 95 => '⚠️ Kritis: Segera tambah unit rak baru atau musnahkan arsip expired',
                $pct >= 80 => '⚡ Perhatian: Jadwalkan mutasi box arsip ke rak sektor sekunder',
                default => 'Kapasitas Masih Memadai',
            };
            yield [$i + 1, $r->rack_code, $r->warehouse->name ?? '-', $r->room_sector ?: ($r->warehouse->code ?? '-'),
                'X: '.($r->canvas_x ?: 0).', Y: '.($r->canvas_y ?: 0).' (Rotasi '.($r->rotation_angle ?: 0).'°)',
                $r->assignedDepartment->name ?? 'Umum / Public', "{$cap} Box", "{$used} Box", max(0, $cap - $used).' Slot', $pct.'%', $advice];
        }
        $count = $full->count();

        return [['Total Rak Kritis / Penuh', "{$count} Unit"], ['Total Rak Terpetakan 2D', $all->count().' Unit'],
            ['Rata-rata Keterisian', ($count ? round($pctSum / $count, 1) : 0).'%'], ['Status Gudang', $count > 0 ? 'Perlu Pemantauan' : 'Optimal']];
    }

    /** @return array{0: int, 1: int, 2: float|int} capacity, used boxes, occupancy % */
    private function fill(WarehouseLocation $rack): array
    {
        $cap = (int) ($rack->box_capacity ?: 100);
        $used = (int) ($rack->current_box_count ?: $rack->archives_count);

        return [$cap, $used, $cap > 0 ? round($used / $cap * 100, 1) : 0];
    }

    /** @return array<int, int> rack id => boxes whose retention ended */
    private function expiredPerRack(): array
    {
        return Archive::query()->whereNotNull('warehouse_location_id')->whereNotIn('status', ['destroyed'])
            ->whereRaw("date(retention_expiry_date, 'start of month', '+1 month', '-1 day') < ?", [Carbon::today()->toDateString()])
            ->groupBy('warehouse_location_id')->selectRaw('warehouse_location_id, COUNT(*) as total')
            ->pluck('total', 'warehouse_location_id')->map(fn ($v): int => (int) $v)->all();
    }
}
