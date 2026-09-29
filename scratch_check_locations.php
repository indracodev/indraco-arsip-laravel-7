<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$locations = App\Models\WarehouseLocation::all();
echo "Total locations: " . $locations->count() . "\n";
foreach ($locations as $loc) {
    echo "ID: {$loc->id}, Type: {$loc->location_type}, Code: {$loc->rack_code}, Sector: {$loc->room_sector}, X: {$loc->canvas_x}, Y: {$loc->canvas_y}, W: {$loc->canvas_width}, H: {$loc->canvas_height}, Active: " . var_export($loc->is_active, true) . "\n";
}
