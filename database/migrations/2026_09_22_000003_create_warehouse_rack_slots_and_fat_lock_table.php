<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseRackSlotsAndFatLockTable extends Migration
{
    public function up()
    {
        // 1. Add FAT locking to warehouses
        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('is_fat_locked')->default(false)->after('address');
        });

        // 2. Add rack specifications and FAT locking to warehouse_locations
        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->boolean('is_fat_locked')->default(false)->after('is_locked');
            $table->integer('total_sap')->default(5)->after('box_capacity');
            $table->integer('boxes_per_sap')->default(20)->after('total_sap');
            $table->string('box_type', 50)->default('TB 30g')->after('boxes_per_sap');
        });

        // 3. Create warehouse_rack_slots table for 100-box visual layout (5 sap x 20 box)
        Schema::create('warehouse_rack_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->integer('sap_level'); // 1 (bawah) s/d 5 (atas)
            $table->string('layer', 10); // 'bottom' (bawah) atau 'top' (atas)
            $table->integer('slot_number'); // 1 s/d 10
            $table->string('slot_code', 30); // Contoh: SAP1-B01, SAP5-T10
            $table->foreignId('archive_id')->nullable()->constrained('archives')->nullOnDelete();
            $table->string('status', 20)->default('empty'); // 'empty', 'filled', 'expired'
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_location_id', 'sap_level', 'layer', 'slot_number'], 'idx_rack_slot_position');
        });

        // 4. Add slot reference to archives
        Schema::table('archives', function (Blueprint $table) {
            $table->foreignId('warehouse_rack_slot_id')->nullable()->after('warehouse_location_id')->constrained('warehouse_rack_slots')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('archives', function (Blueprint $table) {
            $table->dropForeign(['warehouse_rack_slot_id']);
            $table->dropColumn('warehouse_rack_slot_id');
        });

        Schema::dropIfExists('warehouse_rack_slots');

        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->dropColumn([
                'is_fat_locked',
                'total_sap',
                'boxes_per_sap',
                'box_type',
            ]);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('is_fat_locked');
        });
    }
}
