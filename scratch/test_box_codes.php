<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Archive;

$archives = Archive::with(['location', 'rackSlot'])->get();
foreach ($archives as $item) {
    $rackLetter = '';
    if ($item->location && !empty($item->location->rack_code)) {
        if (preg_match('/([A-Za-z]+)$/i', $item->location->rack_code, $m)) {
            $rackLetter = strtoupper($m[1]);
        }
    }

    $slotNo = '';
    if ($item->rackSlot) {
        $slotNo = str_pad($item->rackSlot->slot_number, 2, '0', STR_PAD_LEFT);
    } elseif ($item->location && !empty($item->location->shelf_code) && preg_match('/(\d+)/', $item->location->shelf_code, $m)) {
        $slotNo = str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }

    if (empty($rackLetter)) {
        if (!empty($item->box_number) && preg_match('/([A-Za-z]{1,3})\d+/i', $item->box_number, $bm)) {
            $rackLetter = strtoupper($bm[1]);
        } else {
            $letterIdx = intval(($item->id - 1) / 20) % 26;
            $rackLetter = chr(65 + $letterIdx);
        }
    }

    if (empty($slotNo)) {
        if (!empty($item->box_number) && preg_match('/(\d{1,3})$/', $item->box_number, $nm)) {
            $slotNo = str_pad(intval($nm[1]) % 100 ?: 1, 2, '0', STR_PAD_LEFT);
        } else {
            $slotNo = str_pad((($item->id - 1) % 20) + 1, 2, '0', STR_PAD_LEFT);
        }
    }

    $boxCode = $rackLetter . $slotNo;
    echo "Archive ID #{$item->id} | Rack: " . ($item->location->rack_code ?? 'None') . " | Slot: " . ($item->rackSlot->slot_number ?? 'None') . " => BOX CODE: {$boxCode}\n";
}
