<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rooms = App\Models\WarehouseLocation::where('location_type', 'room')->get();
foreach ($rooms as $r) {
    echo "ID: {$r->id}, Code: {$r->rack_code}, Color: '{$r->custom_color}', Active: " . var_export($r->is_active, true) . "\n";
}
