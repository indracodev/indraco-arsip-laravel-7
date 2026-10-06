# DMS PT INDRACO — Project Implementation Rules

> Dokumen ini adalah kontrak teknis yang WAJIB diikuti oleh seluruh implementasi fitur baru.
> Setiap penambahan fitur, modifikasi controller, atau perubahan konfigurasi **harus mengacu dan tidak boleh melanggar** rules di bawah ini.

---

## 1. Arsitektur Deployment: Desktop-First Offline Application

### 1.1. Mode Operasi
- Aplikasi ini adalah **Desktop Application** yang dijalankan via `START-DMS-INDRACO.bat`.
- **TIDAK** menggunakan Nginx, Apache, atau web server eksternal. Server adalah `php artisan serve`.
- Harus bisa berjalan **100% offline** tanpa koneksi internet.

### 1.2. PHP Runtime Portable
- PHP runtime disimpan lokal di folder `evironment/php-8.2.29-Win32-vs16-x64/` (perhatikan typo `evironment` — ini disengaja, JANGAN diubah).
- Urutan prioritas deteksi PHP di `START-DMS-INDRACO.bat`:
  1. `evironment/php*` (folder project internal — prioritas utama)
  2. `environment/php*` (alternatif tanpa typo)
  3. Laragon `C:\laragon\bin\php\` atau `D:\laragon\bin\php\`
  4. XAMPP `C:\xampp\php\` atau `D:\xampp\php\`
  5. System PATH (fallback)

### 1.3. Database: SQLite Only
- Database: `database/database.sqlite`
- Path resolution di `config/database.php` menggunakan `base_path(env('DB_DATABASE'))` untuk absolute path.
- **DILARANG** menambahkan dependensi MySQL/PostgreSQL untuk fitur baru.

---

## 2. Akses Jaringan LAN

### 2.1. Server Binding
- `php artisan serve` WAJIB menggunakan `--host=0.0.0.0 --port=8000`.
- Binding `0.0.0.0` memastikan perangkat lain di LAN (HP, laptop lain) dapat mengakses server.
- **DILARANG** menggunakan `--host=127.0.0.1` atau `--host=localhost` — ini memblokir akses LAN.

### 2.2. IP Detection
- IP lokal dideteksi otomatis oleh `START-DMS-INDRACO.bat` via `ipconfig | findstr IPv4`.
- Ditampilkan di terminal banner saat startup.

### 2.3. LAN Latency Monitor (Navbar Component)
- File: `resources/views/layouts/partials/lan_monitor.blade.php`
- Di-include di **semua layout**: `app.blade.php`, `desktop_pic.blade.php`, `login.blade.php`
- Menggunakan endpoint **ultra-lightweight**: `GET /api/health/ping`
- Endpoint ping:
  - **TIDAK** melakukan query database
  - **TIDAK** membaca session
  - Response target: **< 2ms**
  - Return: `{ status, server_time, server_ip, server_name, client_ip }`
  - Headers: `Cache-Control: no-cache, no-store, must-revalidate`

### 2.4. Diagnostics & Telemetry (Super Admin Only)
- Route group: `Route::middleware('diagnostics.auth')` 
- Middleware: `App\Http\Middleware\EnsureDiagnosticsAuthorized`
- Endpoint yang dilindungi:
  - `GET /api/health/metrics` — RAM, OPcache, JIT, SQLite WAL stats
  - `GET /api/health/probe-ip` — Client IP probe
  - `GET /api/health/logs` — Server log viewer
  - `GET /diagnostics` — Dashboard diagnostik visual
- **Hanya Super Admin** yang bisa mengakses diagnostics.
- Endpoint `GET /api/health/ping` **terbuka untuk semua** (lightweight heartbeat).

---

## 3. Optimasi Performa (Hardware Profile: AMD A9 Dual-Core, 8GB RAM)

### 3.1. PHP OPcache + JIT (`evironment/php-8.2.29-Win32-vs16-x64/php.ini`)
```ini
zend_extension=opcache
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.validate_timestamps=1
opcache.save_comments=1
opcache.jit=1255
opcache.jit_buffer_size=64M
memory_limit=256M
```

**DILARANG** mengubah nilai-nilai ini tanpa benchmark evidence.

### 3.2. PHP Extensions Aktif (Wajib)
```
extension=curl, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, pdo_sqlite, sqlite3, zip
```
Jika menambahkan fitur yang butuh extension baru, pastikan:
- Extension tersedia di Windows PHP portable build
- Ditambahkan juga di auto-setup section `START-DMS-INDRACO.bat`

### 3.3. SQLite Performance PRAGMAs (`AppServiceProvider::boot()`)
Dijalankan otomatis setiap kali koneksi SQLite terbuka via `ConnectionEstablished` event:
```php
PRAGMA journal_mode = WAL;       // Write-Ahead Logging untuk multi-client LAN
PRAGMA busy_timeout = 5000;      // Toleransi 5 detik jika database terkunci
PRAGMA synchronous = NORMAL;     // Keseimbangan antara performa dan safety
PRAGMA temp_store = MEMORY;      // Temp tables di RAM
PRAGMA cache_size = -32000;      // 32MB cache di RAM
PRAGMA mmap_size = 67108864;     // 64MB memory-mapped I/O
```

**DILARANG** mengubah `journal_mode` dari WAL — ini kritikal untuk multi-client LAN.

### 3.4. Laravel Cache Pre-compilation (Startup via BAT)
Dijalankan saat startup oleh `START-DMS-INDRACO.bat`:
```bat
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
- `config:cache`: Compile semua config ke satu file PHP.
- `route:cache`: Pre-compile routes menjadi PHP array.
- `view:cache`: Pre-compile semua Blade template.

> **PENTING**: Jika menambah route baru atau mengubah config, clear cache dulu saat development:
> ```
> php artisan config:clear && php artisan route:clear && php artisan view:clear
> ```

---

## 4. Asset Delivery — Zero CDN, 100% Offline

### 4.1. Vendor JS/CSS (Lokal di `public/`)
Semua library frontend sudah di-download dan disimpan lokal. **DILARANG** menambahkan CDN external baru.

| File | Path | Size |
|------|------|------|
| Tailwind CSS (Play CDN) | `public/js/vendor/tailwindcss.js` | 440.6 KB |
| Lucide Icons | `public/js/vendor/lucide.min.js` | 349.7 KB |
| Chart.js | `public/js/vendor/chart.min.js` | 194.9 KB |
| Alpine.js | `public/js/vendor/alpine.min.js` | 43.7 KB |
| QRCode.js | `public/js/vendor/qrcode.min.js` | 19.5 KB |
| Custom Fonts CSS | `public/css/fonts.css` | 0.5 KB |
| **TOTAL** | | **~1.05 MB** |

### 4.2. Asset Loading (Centralized)
- File: `resources/views/layouts/partials/head_assets.blade.php`
- Semua layout WAJIB include: `@include('layouts.partials.head_assets')`
- Gunakan `{{ asset('...') }}` untuk semua path aset — JANGAN hardcode URL.

### 4.3. Menambahkan Library Baru
Jika benar-benar perlu menambah library JS/CSS baru:
1. Download file `.min.js` / `.min.css` ke `public/js/vendor/` atau `public/css/`
2. Tambahkan `<script>` / `<link>` di `head_assets.blade.php`
3. **DILARANG** menggunakan `<script src="https://cdn.xxx">` — aplikasi harus tetap 100% offline
4. Update tabel vendor di rules ini

### 4.4. Server untuk Aset Statis
- `php artisan serve` otomatis serve file dari `public/` sebagai document root.
- **DILARANG** menjalankan `php -S 0.0.0.0:8000` secara manual tanpa `-t public` — ini akan 404 semua aset CSS/JS.
- **DILARANG** menjalankan PHP built-in server dari root project (bukan dari `public/`) — ini penyebab CSS 404.

---

## 5. Terminal Launcher (`START-DMS-INDRACO.bat`)

### 5.1. Layar Terkunci (Zero-Scroll)
- Output `php artisan serve` dialihkan ke `storage/logs/http-access.log`.
- Banner IP dan status tetap terlihat di terminal tanpa ter-scroll oleh log request.
- Perintah:
  ```bat
  php artisan serve --host=0.0.0.0 --port=8000 > "storage\logs\http-access.log" 2>&1
  ```

### 5.2. Syntax Batch File Rules
- **DILARANG** menggunakan `(` `)` dalam `echo` — gunakan `[` `]` sebagai pengganti.
  - ❌ `echo [OK] Database siap (WAL aktif)`
  - ✅ `echo [OK] Database siap [WAL aktif]`
- **DILARANG** menggunakan `timeout /t N` di konteks non-interaktif — gunakan `ping 127.0.0.1 -n N >nul`.
- Setiap path yang mengandung `%~dp0` harus diperlakukan hati-hati karena trailing backslash dapat meng-escape closing quote.

### 5.3. Startup Sequence (JANGAN diubah urutannya)
1. `cd /d "%~dp0"` — Pindah ke direktori project
2. CHECK 1: Deteksi PHP runtime (urutan prioritas sesuai §1.2)
3. VERIFY_PHP: Set `PHPRC`, verifikasi extension aktif
4. CHECK 2: `.env` initialization
5. CHECK 2B: `vendor/autoload.php` (composer install jika perlu)
6. CHECK 3: SQLite database creation + migration
7. CHECK 4: `APP_KEY` generation
8. CHECK 5: Storage symlink
9. CHECK 6: Pre-compile cache (`config:cache`, `route:cache`, `view:cache`)
10. IP Detection via `ipconfig`
11. Banner display + `php artisan serve`

### 5.4. Browser Auto-Open
```bat
start /b "" cmd /c "ping 127.0.0.1 -n 3 >nul & start http://127.0.0.1:8000"
```
Delay 3 detik agar server sempat start sebelum browser dibuka.

---

## 6. Git & Repository Rules

### 6.1. Branch Aktif
- Branch development: `danu-revisi-3`

### 6.2. Files yang TIDAK boleh di-push ke GitHub
File berikut ada di `.gitignore` dan HANYA ada di lokal:
```
/tests/k6/                 # Grafana k6 load test scripts
/RUN-K6-BENCHMARK.bat      # k6 benchmark launcher
benchmark_summary.html     # k6 report
benchmark_results.json     # k6 results
```

### 6.3. Folder `evironment/` 
- Berisi PHP portable runtime (~30MB).
- Di-track di git (agar clone langsung bisa jalan tanpa install PHP).
- **JANGAN** rename ke `environment` — batch file bergantung pada nama `evironment`.

---

## 7. Checklist Sebelum Merge Fitur Baru

- [ ] Apakah fitur berjalan **100% offline** tanpa internet?
- [ ] Apakah tidak ada external CDN / API call baru?
- [ ] Apakah SQLite PRAGMAs tidak diubah?
- [ ] Apakah endpoint baru yang sensitif dilindungi `diagnostics.auth` middleware?
- [ ] Apakah endpoint baru yang publik se-lightweight mungkin (< 5ms)?
- [ ] Apakah asset baru sudah di-download ke `public/js/vendor/` atau `public/css/`?
- [ ] Apakah `head_assets.blade.php` sudah di-update jika ada library baru?
- [ ] Apakah `START-DMS-INDRACO.bat` tidak rusak syntaxnya? (Test: double-click `.bat`)
- [ ] Apakah CSS/JS ter-serve dengan HTTP 200? (Test: `curl -I http://127.0.0.1:8000/css/fonts.css`)
- [ ] Apakah server tetap accessible dari LAN? (Test dari HP: `http://<IP>:8000`)
- [ ] Apakah terminal banner tetap terkunci (tidak scroll)?

---

> **Terakhir diperbarui**: 6 Oktober 2026
> **Baseline commit**: `a283eae` (branch `danu-revisi-3`)
