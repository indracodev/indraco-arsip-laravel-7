<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$racks = App\Models\WarehouseLocation::where('room_sector', 'R7')->where('location_type', 'rack')->get(['rack_code', 'box_capacity', 'total_sap', 'boxes_per_sap']);
echo "Total Racks in R7: " . $racks->count() . PHP_EOL;
foreach ($racks as $r) {
    echo $r->rack_code . " (Cap: {$r->box_capacity}, Sap: {$r->total_sap})" . PHP_EOL;
}
$slotCount = App\Models\WarehouseRackSlot::whereHas('rackLocation', function($q) {
    $q->where('room_sector', 'R7');
})->count();
echo "Total Generated Slots for R7: " . $slotCount . PHP_EOL;
unlink(__FILE__);
