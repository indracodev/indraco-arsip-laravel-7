<?php

namespace App\Http\Controllers\Traits;

use App\Models\Archive;
use Carbon\Carbon;

trait FormatsArchiveLabels
{
    /**
     * Format entitas Archive ke struktur standar data label / stiker barcode.
     */
    public function formatArchiveForLabel(Archive $archive): array
    {
        $expDate = 'MM-YYYY';
        if (!empty($archive->retention_expiry_date)) {
            $expDate = Carbon::parse($archive->retention_expiry_date)->format('m-Y');
        } elseif (!empty($archive->period_end_date) && !empty($archive->retention_years)) {
            $expDate = Carbon::parse($archive->period_end_date)
                ->addYears($archive->retention_years)
                ->format('m-Y');
        } elseif (!empty($archive->end_year) && !empty($archive->retention_years)) {
            $expDate = '12-' . ($archive->end_year + $archive->retention_years);
        }

        $isAllocated = !empty($archive->warehouse_location_id) || !empty($archive->warehouse_rack_slot_id);

        $rackLetter = '-';
        if ($archive->location && !empty($archive->location->rack_code)) {
            $rackLetter = strtoupper(trim($archive->location->rack_code));
        } elseif ($archive->rackSlot && !empty($archive->rackSlot->slot_code) && preg_match('/^([A-Za-z]+)/', $archive->rackSlot->slot_code, $m)) {
            $rackLetter = strtoupper($m[1]);
        }

        $slotNo = '-';
        if ($archive->rackSlot && !empty($archive->rackSlot->slot_number)) {
            $slotNo = str_pad($archive->rackSlot->slot_number, 2, '0', STR_PAD_LEFT);
        } elseif ($archive->location && !empty($archive->location->shelf_code) && preg_match('/(\d+)/', $archive->location->shelf_code, $m)) {
            $slotNo = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }

        if ($isAllocated && $rackLetter !== '-' && $slotNo !== '-') {
            $boxCode = $rackLetter . ' / ' . $slotNo;
        } elseif ($isAllocated && $rackLetter !== '-') {
            $boxCode = $rackLetter;
        } else {
            $boxCode = '-';
            $rackLetter = '-';
            $slotNo = '-';
        }

        $whName = $archive->location && $archive->location->warehouse 
            ? ($archive->location->warehouse->name ?: $archive->location->warehouse->code) 
            : ($archive->location->room_sector ?? null);

        $rackCode = $archive->location ? $archive->location->rack_code : null;
        $slotLabel = $archive->rackSlot 
            ? ("Sap {$archive->rackSlot->sap_level}, {$archive->rackSlot->layer_label} Slot {$archive->rackSlot->slot_number}") 
            : null;
        $effectivePeriod = $archive->effective_periode ?? ($archive->periode_doc ?? ($archive->periode ?? ''));

        return [
            'id' => $archive->id,
            'title' => $archive->title,
            'box_number' => $archive->box_number ?: 'DRAFT-BOX',
            'department_name' => $archive->department ? $archive->department->name : 'Departemen',
            'sub_department_name' => $archive->subDepartment ? $archive->subDepartment->name : null,
            'exp_date' => $expDate,
            'rack_letter' => $rackLetter,
            'slot_no' => $slotNo,
            'box_code' => $boxCode,
            'is_allocated' => $isAllocated,
            'wh_name' => $whName,
            'rack_code' => $rackCode,
            'slot_label' => $slotLabel,
            'effective_period' => $effectivePeriod,
            'retention_duration_label' => $archive->retention_duration_label ?: ($archive->retention_years ? $archive->retention_years . ' Tahun' : '-'),
            'tgl_penyerahan' => $archive->tgl_penyerahan ? Carbon::parse($archive->tgl_penyerahan)->format('d/m/Y') : date('d/m/Y'),
            'content_description' => $archive->content_description ?: $archive->title,
        ];
    }
}
