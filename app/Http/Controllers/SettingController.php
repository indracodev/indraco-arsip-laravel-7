<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\ActivityLogger;
use App\Support\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * "Pengaturan Sistem" (admin only, finding #11): the admin sections of the Laravel 7 settings modal
 * (logo, LAN ping alert, maintenance) as a page. Font size is per user (Pengaturan Tampilan, ASK 13),
 * so the global font setting is gone. Logo: png/jpg only (finding #12: svg = stored XSS), type from
 * the file content, random name, public/uploads/logo (same folder as Laravel 7).
 */
class SettingController extends Controller
{
    private const LOGO_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    public function index(): View
    {
        $log = storage_path('logs/git-update.log');

        return view('settings.index', [
            'appName' => AppSettings::get('app_name'),
            'logo' => AppSettings::get('app_logo'),
            'pingAlertEnabled' => AppSettings::get('ping_alert_enabled', '0') === '1',
            'pingAlertThreshold' => (int) AppSettings::get('ping_alert_threshold_ms', '500'),
            'gitLog' => is_file($log) ? mb_substr((string) file_get_contents($log), -20000) : 'Belum ada log git-update yang tercatat.',
            'hasUpdateScript' => is_file(base_path('UPDATE-AND-RESTART.bat')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $valid = $request->validate([
            'logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'app_name' => ['nullable', 'string', 'max:100'],
            'ping_alert_enabled' => ['nullable', 'boolean'],
            'ping_alert_threshold_ms' => ['nullable', 'integer', 'min:50', 'max:10000'],
        ], ['logo.image' => 'Logo harus berupa gambar PNG atau JPG.', 'logo.mimes' => 'Logo harus berupa gambar PNG atau JPG.']);

        $updated = [];
        if ($file = $request->file('logo')) {
            $extension = self::LOGO_TYPES[(string) $file->getMimeType()] ?? null;
            if ($extension === null) {
                return back()->withErrors(['logo' => 'Logo harus berupa gambar PNG atau JPG.']);
            }
            File::ensureDirectoryExists(public_path('uploads/logo'));
            $name = 'logo_'.time().'_'.Str::lower(Str::random(6)).'.'.$extension;
            $file->move(public_path('uploads/logo'), $name);
            $updated['app_logo'] = $this->put('app_logo', 'uploads/logo/'.$name, 'appearance', 'image', 'Logo kustom aplikasi');
        }
        if (filled($valid['app_name'] ?? null)) {
            $updated['app_name'] = $this->put('app_name', trim($valid['app_name']), 'general', 'string', 'Nama aplikasi');
        }
        $updated['ping_alert_enabled'] = $this->put('ping_alert_enabled', $request->boolean('ping_alert_enabled') ? '1' : '0', 'system', 'boolean', 'Status aktifkan notifikasi peringatan latensi LAN');
        if (isset($valid['ping_alert_threshold_ms'])) {
            $updated['ping_alert_threshold_ms'] = $this->put('ping_alert_threshold_ms', (string) $valid['ping_alert_threshold_ms'], 'system', 'integer', 'Ambang batas latensi LAN (ms) untuk memicu peringatan');
        }
        ActivityLogger::log('SYSTEM_SETTINGS_UPDATE', 'Memperbarui pengaturan sistem tampilan dan jaringan.', 'SETTINGS', $updated);

        return redirect()->route('settings.index')->with('success', 'Pengaturan sistem berhasil disimpan.');
    }

    /** Back to the bundled logo (the row is removed, AppSettings falls back to the default). */
    public function resetLogo(): RedirectResponse
    {
        AppSetting::query()->where('key', 'app_logo')->delete();
        ActivityLogger::log('SYSTEM_LOGO_RESET', 'Mengembalikan logo aplikasi ke default PT Indraco.', 'SETTINGS');

        return redirect()->route('settings.index')->with('success', 'Logo berhasil dikembalikan ke default.');
    }

    private function put(string $key, string $value, string $group, string $type, string $description): string
    {
        AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group, 'type' => $type, 'description' => $description]);

        return $value;
    }
}
