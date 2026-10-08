<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Get current system settings.
     */
    public function getSettings()
    {
        $logo = AppSetting::get('app_logo', 'images/logo_indraco.png');
        $fontSize = AppSetting::get('app_font_size', '14px');
        $appName = AppSetting::get('app_name', 'DMS PT INDRACO');
        $pingAlertEnabled = in_array(AppSetting::get('ping_alert_enabled', '0'), [1, '1', true, 'true'], true);
        $pingAlertThresholdMs = (int) AppSetting::get('ping_alert_threshold_ms', 500);

        return response()->json([
            'status' => 'success',
            'settings' => [
                'app_logo' => asset($logo),
                'app_logo_raw' => $logo,
                'app_font_size' => $fontSize,
                'app_name' => $appName,
                'ping_alert_enabled' => $pingAlertEnabled,
                'ping_alert_threshold_ms' => $pingAlertThresholdMs,
            ],
        ]);
    }

    /**
     * Update application settings (Logo upload, Font size & LAN Ping alert).
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'font_size' => 'nullable|string|max:20',
            'app_name' => 'nullable|string|max:100',
            'ping_alert_enabled' => 'nullable|in:0,1,true,false',
            'ping_alert_threshold_ms' => 'nullable|integer|min:50|max:10000',
        ]);

        $updated = [];

        // Handle Logo Upload
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            
            $destinationPath = public_path('uploads/logo');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            $file->move($destinationPath, $filename);
            $logoPath = 'uploads/logo/' . $filename;

            AppSetting::set('app_logo', $logoPath, 'appearance', 'image', 'Logo kustom aplikasi');
            $updated['app_logo'] = asset($logoPath);
        }

        // Handle Font Size
        if ($request->filled('font_size')) {
            $fontSize = trim($request->input('font_size'));
            AppSetting::set('app_font_size', $fontSize, 'appearance', 'string', 'Ukuran font global antarmuka');
            $updated['app_font_size'] = $fontSize;
        }

        // Handle App Name
        if ($request->filled('app_name')) {
            $appName = trim($request->input('app_name'));
            AppSetting::set('app_name', $appName, 'general', 'string', 'Nama aplikasi');
            $updated['app_name'] = $appName;
        }

        // Handle LAN Ping Alert Notification Settings
        if ($request->has('ping_alert_enabled')) {
            $pingAlertVal = in_array($request->input('ping_alert_enabled'), [1, '1', true, 'true'], true) ? '1' : '0';
            AppSetting::set('ping_alert_enabled', $pingAlertVal, 'system', 'boolean', 'Status aktifkan notifikasi peringatan latensi LAN');
            $updated['ping_alert_enabled'] = $pingAlertVal === '1';
        }

        if ($request->filled('ping_alert_threshold_ms')) {
            $threshold = (int) $request->input('ping_alert_threshold_ms');
            AppSetting::set('ping_alert_threshold_ms', (string) $threshold, 'system', 'integer', 'Ambang batas latensi LAN (ms) untuk memicu peringatan');
            $updated['ping_alert_threshold_ms'] = $threshold;
        }

        ActivityLogger::log(
            'SYSTEM_SETTINGS_UPDATE',
            "Memperbarui pengaturan sistem tampilan dan jaringan.",
            'SETTINGS',
            $updated
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengaturan sistem berhasil disimpan.',
                'settings' => [
                    'app_logo' => asset(AppSetting::get('app_logo', 'images/logo_indraco.png')),
                    'app_font_size' => AppSetting::get('app_font_size', '14px'),
                    'app_name' => AppSetting::get('app_name', 'DMS PT INDRACO'),
                    'ping_alert_enabled' => in_array(AppSetting::get('ping_alert_enabled', '0'), [1, '1', true, 'true'], true),
                    'ping_alert_threshold_ms' => (int) AppSetting::get('ping_alert_threshold_ms', 500),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }

    /**
     * Reset logo to default.
     */
    public function resetLogo(Request $request)
    {
        $defaultLogo = 'images/logo_indraco.png';
        AppSetting::set('app_logo', $defaultLogo, 'appearance', 'image', 'Logo default PT Indraco');

        ActivityLogger::log(
            'SYSTEM_LOGO_RESET',
            "Mengembalikan logo aplikasi ke default PT Indraco.",
            'SETTINGS'
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Logo berhasil dikembalikan ke default.',
                'app_logo' => asset($defaultLogo),
            ]);
        }

        return redirect()->back()->with('success', 'Logo berhasil dikembalikan ke default.');
    }

    /**
     * Clear all system log history (Activity Logs & Warehouse Entry Logs).
     */
    public function clearLogs(Request $request)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                \App\Models\ActivityLog::query()->delete();
                \App\Models\WarehouseEntryLog::query()->delete();
            });

            ActivityLogger::log(
                'SYSTEM_LOGS_CLEARED',
                "Mengosongkan seluruh riwayat log aktivitas dan log gudang.",
                'SETTINGS'
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Seluruh riwayat log aktivitas & riwayat gudang berhasil dikosongkan.',
                ]);
            }

            return redirect()->back()->with('success', 'Seluruh riwayat log aktivitas & riwayat gudang berhasil dikosongkan.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengosongkan log history: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Gagal mengosongkan log history: ' . $e->getMessage());
        }
    }

    /**
     * Clear / unassign all archive boxes from warehouse rack slots and reset rack allocations.
     */
    public function clearBoxAllocations(Request $request)
    {
        try {
            $affectedSlots = 0;
            $affectedArchives = 0;

            \Illuminate\Support\Facades\DB::transaction(function () use (&$affectedSlots, &$affectedArchives) {
                // Reset all rack slots that were filled
                $affectedSlots = \App\Models\WarehouseRackSlot::whereNotNull('archive_id')
                    ->orWhere('status', '!=', 'empty')
                    ->update([
                        'archive_id' => null,
                        'status' => 'empty',
                    ]);

                // Reset all rack locations box count
                \App\Models\WarehouseLocation::query()->update([
                    'current_box_count' => 0,
                ]);

                // Detach warehouse slot & location from archives
                $affectedArchives = \App\Models\Archive::whereNotNull('warehouse_rack_slot_id')
                    ->orWhereNotNull('warehouse_location_id')
                    ->update([
                        'warehouse_location_id' => null,
                        'warehouse_rack_slot_id' => null,
                        'status' => 'approved_booked',
                    ]);
            });

            ActivityLogger::log(
                'SYSTEM_BOX_ALLOCATIONS_CLEARED',
                "Mengosongkan penempatan box di rak gudang ({$affectedSlots} slot rak direset, {$affectedArchives} box arsip dikosongkan dari rak).",
                'SETTINGS',
                ['affected_slots' => $affectedSlots, 'affected_archives' => $affectedArchives]
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Semua box di rak gudang berhasil dikosongkan ({$affectedSlots} slot rak direset, {$affectedArchives} box arsip dikembalikan ke status belum ditempatkan).",
                ]);
            }

            return redirect()->back()->with('success', 'Semua penempatan box di rak gudang berhasil dikosongkan.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengosongkan penempatan box: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Gagal mengosongkan penempatan box: ' . $e->getMessage());
        }
    }

    /**
     * Clear all archive submissions (Pengajuan Box Arsip) and related transactional records.
     */
    public function clearArchives(Request $request)
    {
        try {
            $totalArchives = 0;

            \Illuminate\Support\Facades\DB::transaction(function () use (&$totalArchives) {
                $totalArchives = \App\Models\Archive::count();

                // Delete child records first
                \App\Models\BorrowingLog::query()->delete();
                \App\Models\DestructionLog::query()->delete();
                \App\Models\WarehouseEntryLog::query()->delete();
                \App\Models\ArchiveItem::query()->delete();

                // Reset rack slots & warehouse locations
                \App\Models\WarehouseRackSlot::query()->update([
                    'archive_id' => null,
                    'status' => 'empty',
                ]);
                \App\Models\WarehouseLocation::query()->update([
                    'current_box_count' => 0,
                ]);

                // Delete all archives
                \App\Models\Archive::query()->delete();
            });

            ActivityLogger::log(
                'SYSTEM_ARCHIVES_CLEARED',
                "Mengosongkan seluruh data pengajuan box arsip ({$totalArchives} data arsip beserta item & log transaksi dihapus).",
                'SETTINGS',
                ['deleted_archives' => $totalArchives]
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Seluruh data pengajuan box arsip ({$totalArchives} arsip) berhasil dikosongkan.",
                ]);
            }

            return redirect()->back()->with('success', 'Seluruh data pengajuan box arsip berhasil dikosongkan.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengosongkan data pengajuan arsip: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Gagal mengosongkan data pengajuan arsip: ' . $e->getMessage());
        }
    }

    /**
     * Generate and download a complete SQL database backup (SQLite & MySQL compatible).
     */
    public function backupDatabase(Request $request)
    {
        try {
            // Increase time and memory limits for backup execution
            @ini_set('memory_limit', '512M');
            @set_time_limit(300);

            $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
            $dbConfig = config("database.connections." . config('database.default', 'mysql'), []);
            $dbName = $dbConfig['database'] ?? env('DB_DATABASE', 'indraco_arsip');

            $currentUser = auth()->user() ? auth()->user()->name : 'Super Admin';
            $now = now()->format('Y-m-d H:i:s');

            $sql = "-- ======================================================\n";
            $sql .= "-- DMS PT INDRACO - DATABASE BACKUP DUMP\n";
            $sql .= "-- Application: Document Management System Desktop Edition\n";
            $sql .= "-- Engine Driver: " . strtoupper($driver) . "\n";
            $sql .= "-- Database: " . basename($dbName) . "\n";
            $sql .= "-- Generated At: {$now}\n";
            $sql .= "-- Exported By: {$currentUser}\n";
            $sql .= "-- ======================================================\n\n";

            $totalTables = 0;
            $totalRows = 0;

            if ($driver === 'sqlite') {
                $sql .= "PRAGMA foreign_keys = OFF;\n\n";

                $tables = \Illuminate\Support\Facades\DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name;");

                foreach ($tables as $t) {
                    $tableName = $t->name;
                    $tableSql = $t->sql;

                    if (empty($tableName) || empty($tableSql)) continue;

                    $totalTables++;

                    $sql .= "-- --------------------------------------------------------\n";
                    $sql .= "-- Table structure for table `{$tableName}`\n";
                    $sql .= "-- --------------------------------------------------------\n";
                    $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                    $sql .= $tableSql . ";\n\n";

                    $rows = \Illuminate\Support\Facades\DB::table($tableName)->get();
                    $rowCount = $rows->count();
                    $totalRows += $rowCount;

                    if ($rowCount > 0) {
                        $sql .= "-- Dumping data for table `{$tableName}` ({$rowCount} rows)\n";
                        $chunks = $rows->chunk(100);
                        foreach ($chunks as $chunk) {
                            $firstRow = (array) $chunk->first();
                            $columnNames = array_map(function ($col) {
                                return "`" . str_replace("`", "``", $col) . "`";
                            }, array_keys($firstRow));

                            $sql .= "INSERT INTO `{$tableName}` (" . implode(', ', $columnNames) . ") VALUES \n";

                            $valuesArr = [];
                            foreach ($chunk as $row) {
                                $rowArr = (array) $row;
                                $escapedValues = array_map(function ($value) {
                                    if (is_null($value)) {
                                        return 'NULL';
                                    }
                                    if (is_numeric($value) && !is_string($value)) {
                                        return $value;
                                    }
                                    return "'" . addslashes((string)$value) . "'";
                                }, array_values($rowArr));

                                $valuesArr[] = "(" . implode(", ", $escapedValues) . ")";
                            }
                            $sql .= implode(",\n", $valuesArr) . ";\n";
                        }
                        $sql .= "\n";
                    }
                }

                $sql .= "PRAGMA foreign_keys = ON;\n";
            } else {
                // MySQL / MariaDB
                $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
                $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
                $sql .= "SET AUTOCOMMIT = 0;\n";
                $sql .= "START TRANSACTION;\n";
                $sql .= "SET time_zone = '+00:00';\n\n";

                $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');

                foreach ($tables as $tableObj) {
                    $tableObjArr = (array) $tableObj;
                    $tableName = reset($tableObjArr);

                    if (empty($tableName)) continue;

                    $totalTables++;

                    $sql .= "-- --------------------------------------------------------\n";
                    $sql .= "-- Table structure for table `{$tableName}`\n";
                    $sql .= "-- --------------------------------------------------------\n";
                    $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

                    $createTable = \Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `{$tableName}`");
                    if (!empty($createTable)) {
                        $createArr = (array) $createTable[0];
                        $createKey = isset($createArr['Create Table']) ? 'Create Table' : array_keys($createArr)[1];
                        $sql .= $createArr[$createKey] . ";\n\n";
                    }

                    $rows = \Illuminate\Support\Facades\DB::table($tableName)->get();
                    $rowCount = $rows->count();
                    $totalRows += $rowCount;

                    if ($rowCount > 0) {
                        $sql .= "-- Dumping data for table `{$tableName}` ({$rowCount} rows)\n";
                        $chunks = $rows->chunk(100);
                        foreach ($chunks as $chunk) {
                            $firstRow = (array) $chunk->first();
                            $columnNames = array_map(function ($col) {
                                return "`" . str_replace("`", "``", $col) . "`";
                            }, array_keys($firstRow));

                            $sql .= "INSERT INTO `{$tableName}` (" . implode(', ', $columnNames) . ") VALUES \n";

                            $valuesArr = [];
                            foreach ($chunk as $row) {
                                $rowArr = (array) $row;
                                $escapedValues = array_map(function ($value) {
                                    if (is_null($value)) {
                                        return 'NULL';
                                    }
                                    if (is_numeric($value) && !is_string($value)) {
                                        return $value;
                                    }
                                    return "'" . addslashes((string)$value) . "'";
                                }, array_values($rowArr));

                                $valuesArr[] = "(" . implode(", ", $escapedValues) . ")";
                            }
                            $sql .= implode(",\n", $valuesArr) . ";\n";
                        }
                        $sql .= "\n";
                    }
                }

                $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
                $sql .= "COMMIT;\n";
            }

            $filename = 'backup_indraco_dms_' . date('Y_m_d_His') . '.sql';

            ActivityLogger::log(
                'SYSTEM_DATABASE_BACKUP',
                "Membuat dan mengunduh backup database '{$filename}' ({$totalTables} tabel, {$totalRows} baris data).",
                'SETTINGS',
                ['filename' => $filename, 'driver' => $driver, 'tables_count' => $totalTables, 'rows_count' => $totalRows]
            );

            return response($sql, 200, [
                'Content-Type' => 'application/sql; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Pragma' => 'public',
                'Expires' => '0',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Backup Database Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat backup database: ' . $e->getMessage());
        }
    }
}
