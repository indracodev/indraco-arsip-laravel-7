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
        $logo = AppSetting::get('app_logo', 'images/logo-indraco.png');
        $fontSize = AppSetting::get('app_font_size', '19px');
        $appName = AppSetting::get('app_name', 'DMS PT INDRACO');

        return response()->json([
            'status' => 'success',
            'settings' => [
                'app_logo' => asset($logo),
                'app_logo_raw' => $logo,
                'app_font_size' => $fontSize,
                'app_name' => $appName,
            ],
        ]);
    }

    /**
     * Update application settings (Logo upload & Font size).
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'font_size' => 'nullable|string|max:20',
            'app_name' => 'nullable|string|max:100',
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

        ActivityLogger::log(
            'SYSTEM_SETTINGS_UPDATE',
            "Memperbarui pengaturan sistem tampilan (Logo / Font Size).",
            'SETTINGS',
            $updated
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengaturan sistem berhasil disimpan.',
                'settings' => [
                    'app_logo' => asset(AppSetting::get('app_logo', 'images/logo-indraco.png')),
                    'app_font_size' => AppSetting::get('app_font_size', '19px'),
                    'app_name' => AppSetting::get('app_name', 'DMS PT INDRACO'),
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
        $defaultLogo = 'images/logo-indraco.png';
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
}
