<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\ActivityLogger;
use App\Support\AppSettings;
use App\Support\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

    private const DEFAULT_LIGHT_LOGO = 'images/logo-indraco.png';

    private const DEFAULT_DARK_LOGO = 'images/logo-indraco-invert.png';

    public function index(): View
    {
        $log = storage_path('logs/git-update.log');
        $logo = ltrim((string) AppSettings::get('app_logo'), '/');
        $logoDark = ltrim((string) AppSettings::get('app_logo_dark'), '/');
        $isDefaultLight = in_array($logo, [self::DEFAULT_LIGHT_LOGO, 'images/logo_indraco.png'], true);
        $isDefaultDark = in_array($logoDark, [self::DEFAULT_DARK_LOGO, 'images/logo_indraco_invert.png'], true);
        $darkFallsBackToLight = ! $isDefaultLight && $isDefaultDark;

        return view('settings.index', [
            'appName' => AppSettings::get('app_name'),
            'logoUrl' => $isDefaultLight ? Asset::url(self::DEFAULT_LIGHT_LOGO) : asset($logo),
            'logoDarkUrl' => $darkFallsBackToLight
                ? asset($logo)
                : ($isDefaultDark ? Asset::url(self::DEFAULT_DARK_LOGO) : asset($logoDark)),
            'darkFallsBackToLight' => $darkFallsBackToLight,
            'pingAlertEnabled' => AppSettings::get('ping_alert_enabled', '0') === '1',
            'pingAlertThreshold' => (int) AppSettings::get('ping_alert_threshold_ms', '500'),
            'gitLog' => is_file($log) ? mb_substr((string) file_get_contents($log), -20000) : 'Belum ada log git-update yang tercatat.',
            'hasUpdateScript' => is_file(base_path('UPDATE-AND-RESTART.bat')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $logoMsg = 'Logo harus berupa gambar PNG atau JPG.';
        $valid = $request->validate([
            'logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'logo_dark' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'app_name' => ['nullable', 'string', 'max:100'],
            'ping_alert_enabled' => ['nullable', 'boolean'],
            'ping_alert_threshold_ms' => ['nullable', 'integer', 'min:50', 'max:10000'],
        ], [
            'logo.image' => $logoMsg,
            'logo.mimes' => $logoMsg,
            'logo_dark.image' => $logoMsg,
            'logo_dark.mimes' => $logoMsg,
        ]);

        $updated = [];
        foreach ([
            ['field' => 'logo', 'key' => 'app_logo', 'prefix' => 'logo', 'desc' => 'Logo kustom aplikasi (mode terang)'],
            ['field' => 'logo_dark', 'key' => 'app_logo_dark', 'prefix' => 'logo_dark', 'desc' => 'Logo kustom aplikasi (mode gelap)'],
        ] as $item) {
            if ($file = $request->file($item['field'])) {
                $stored = $this->storeLogo($file, $item['prefix']);
                if ($stored === null) {
                    return back()->withErrors([$item['field'] => $logoMsg]);
                }
                $updated[$item['key']] = $this->put($item['key'], $stored, 'appearance', 'image', $item['desc']);
            }
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

    /** Back to the bundled light & dark logos (the rows are removed, AppSettings falls back to defaults). */
    public function resetLogo(): RedirectResponse
    {
        AppSetting::query()->whereIn('key', ['app_logo', 'app_logo_dark'])->delete();
        ActivityLogger::log('SYSTEM_LOGO_RESET', 'Mengembalikan logo aplikasi ke default PT Indraco.', 'SETTINGS');

        return redirect()->route('settings.index')->with('success', 'Logo berhasil dikembalikan ke default.');
    }

    private function storeLogo(UploadedFile $file, string $prefix): ?string
    {
        $extension = self::LOGO_TYPES[(string) $file->getMimeType()] ?? null;
        if ($extension === null) {
            return null;
        }
        File::ensureDirectoryExists(public_path('uploads/logo'));
        $name = $prefix.'_'.time().'_'.Str::lower(Str::random(6)).'.'.$extension;
        $file->move(public_path('uploads/logo'), $name);

        return 'uploads/logo/'.$name;
    }

    private function put(string $key, string $value, string $group, string $type, string $description): string
    {
        AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group, 'type' => $type, 'description' => $description]);

        return $value;
    }
}
