<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Archive;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;

echo "Fixing archive locations and rack slots...\n";

$archives = Archive::all();
foreach ($archives as $archive) {
    if (!$archive->warehouse_location_id || !$archive->warehouse_rack_slot_id) {
        // Pick a location based on ID modulo available rack locations
        $locations = WarehouseLocation::where('location_type', 'rack')->get();
        if ($locations->isNotEmpty()) {
            $locIndex = ($archive->id - 1) % $locations->count();
            $location = $locations[$locIndex];

            // Find an available slot or any slot for this location
            $slotNumber = (($archive->id - 1) % 20) + 1;
            $slot = WarehouseRackSlot::where('warehouse_location_id', $location->id)
                ->where('slot_number', $slotNumber)
                ->first();

            if (!$slot) {
                $slot = WarehouseRackSlot::where('warehouse_location_id', $location->id)->first();
            }

            if ($location && $slot) {
                $slot->update([
                    'archive_id' => $archive->id,
                    'status' => 'filled',
                ]);
                $archive->update([
                    'warehouse_location_id' => $location->id,
                    'warehouse_rack_slot_id' => $slot->id,
                    'status' => $archive->status === 'draft' ? 'draft' : 'in_warehouse',
                ]);
                echo "Archive #{$archive->id} ({$archive->title}) -> Loc #{$location->id} ({$location->rack_code}), Slot #{$slot->id} (Slot {$slot->slot_number})\n";
            }
        }
    } else {
        echo "Archive #{$archive->id} already has Loc #{$archive->warehouse_location_id}, Slot #{$archive->warehouse_rack_slot_id}\n";
    }
}

echo "Done!\n";
