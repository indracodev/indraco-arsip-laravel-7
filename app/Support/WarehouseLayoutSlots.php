<?php

namespace App\Support;

use App\Models\Archive;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use Illuminate\Support\Facades\DB;

/**
 * Rack grid of the Layout Gudang 2D page (Laravel 7 "Denah Rak" modal): the 100 slots of ONE rack
 * with their box, the queue of boxes without slot ("Pilih Antrean Arsip") and the box search.
 */
final class WarehouseLayoutSlots
{
    /** Boxes that may be printed a label for (Laravel 7 rack inspector button). */
    private const LABEL_STATUSES = ['approved_booked', 'in_warehouse', 'pending_extension'];

    /** @return list<array<string, mixed>> */
    public static function slots(WarehouseLocation $rack): array
    {
        WarehouseLayout::ensureSlots($rack);

        return $rack->slots()->with(['archive' => fn ($q) => $q->with(['department:id,code,name', 'subDepartment:id,code,name'])])->get()
            ->map(function (WarehouseRackSlot $slot): array {
                $archive = $slot->archive && in_array($slot->archive->status, WarehouseLayout::ACTIVE, true) ? $slot->archive : null;
                $expiry = $archive ? WarehouseLayout::expiryState($archive->retention_expiry_date) : null;
                $status = match (true) {
                    $slot->getAttribute('is_active') === false => 'inactive',
                    $archive === null => 'empty',
                    default => $expiry ?? 'filled',
                };

                return $slot->only(['id', 'sap_level', 'layer', 'slot_number', 'slot_code']) + [
                    'is_active' => $slot->getAttribute('is_active') !== false,
                    'status' => $status,
                    'archive' => $archive ? self::archive($archive) : null,
                ];
            })->all();
    }

    /**
     * Boxes waiting for a slot (Laravel 7 `unassigned_archives`), GA / IT rule of the rack's gudang.
     *
     * @return list<array<string, mixed>>
     */
    public static function queue(WarehouseLocation $rack): array
    {
        $code = strtoupper(trim((string) Warehouse::query()->whereKey($rack->warehouse_id)->value('code')));
        $deptRule = match (true) {
            in_array($code, ['GUDANG GA', 'GA'], true) => fn ($q) => $q->where('code', 'GA'),
            in_array($code, ['GUDANG IT', 'IT'], true) => fn ($q) => $q->whereIn('code', ['IT', 'MIS', 'EDP']),
            default => fn ($q) => $q->whereNotIn('code', ['GA', 'IT']),
        };

        return Archive::query()->with(['department:id,code,name', 'creator:id,name'])
            ->whereIn('status', WarehouseLayout::ACTIVE)->whereNull('warehouse_rack_slot_id')
            ->whereHas('department', $deptRule)->orderByDesc('id')->limit(300)->get()
            ->map(fn (Archive $a): array => [
                'id' => $a->id,
                'box_number' => $a->box_number,
                'title' => $a->title,
                'department_id' => $a->department_id,
                'department_code' => $a->department->code ?? 'Dept',
                'creator' => $a->creator->name ?? '-',
                'periode' => $a->effectivePeriode(),
                'status' => $a->status,
                'is_approved' => $a->status !== 'pending_verification',
            ])->all();
    }

    /**
     * Slot codes, box numbers and titles (Laravel 7 search "Box / Slot Arsip"), max 15.
     *
     * @return list<array<string, mixed>>
     */
    public static function search(string $term, bool $isAdmin): array
    {
        $like = '%'.addcslashes($term, '%_\\').'%';
        $query = DB::table('warehouse_rack_slots as s')
            ->join('warehouse_locations as l', 'l.id', '=', 's.warehouse_location_id')
            ->leftJoin('archives as a', fn ($j) => $j->on('a.id', '=', 's.archive_id')->whereIn('a.status', WarehouseLayout::ACTIVE))
            ->where(fn ($q) => $q->where('s.slot_code', 'like', $like)->orWhere('a.box_number', 'like', $like)->orWhere('a.title', 'like', $like))
            ->when(! $isAdmin, fn ($q) => $q->whereNotIn('l.rack_code', WarehouseMaster::ADMIN_ONLY)
                ->where(fn ($q) => $q->whereNull('l.room_sector')->orWhereNotIn('l.room_sector', WarehouseMaster::ADMIN_ONLY)))
            ->orderByRaw('a.id is null')->orderBy('s.id')->limit(15);

        return $query->get(['s.id', 's.slot_code', 's.sap_level', 'l.id as rack_id', 'l.rack_code', 'a.id as archive_id', 'a.box_number', 'a.title'])
            ->map(fn (object $row): array => (array) $row)->all();
    }

    /** @return array<string, mixed> */
    private static function archive(Archive $archive): array
    {
        return [
            'id' => $archive->id,
            'box_number' => $archive->box_number,
            'title' => $archive->title,
            'status' => $archive->status,
            'periode' => $archive->effectivePeriode(),
            'retention' => $archive->retentionDisplay(),
            'expiry' => $archive->retention_expiry_date ? $archive->retention_expiry_date->format('M Y') : '-',
            'department' => $archive->department->code ?? 'Dept',
            'department_name' => $archive->department->name ?? '',
            'sub_department' => $archive->subDepartment ? $archive->subDepartment->code.' - '.$archive->subDepartment->name : null,
            'can_label' => in_array($archive->status, self::LABEL_STATUSES, true),
        ];
    }
}
