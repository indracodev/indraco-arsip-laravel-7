# DMS PT INDRACO — Project Rules (Bagian 3/3): Network, Framework Lifecycle, Frontend Offline, Logging & Benchmark

> Bagian dari [`PROJECT_RULES.md`](PROJECT_RULES.md) — indeks dokumen & Performance Gate Checklist (§9) ada di file utama.

---

## 4. Layer 3: Network & LAN Access Latency

### 4.1. Server Binding Specification
- `php artisan serve` **WAJIB** dijalankan dengan `--host=0.0.0.0 --port=8000`.
- Binding `0.0.0.0` mengizinkan seluruh interface network lokal (WiFi / Ethernet) menerima request dari PC/HP klien lain di LAN.
- **DILARANG** mengubah host ke `127.0.0.1` atau `localhost` di production launcher.

### 4.2. Kontrak Endpoint LAN Ping (`GET /api/health/ping`)
File: [`app/Http/Controllers/HealthController.php`](file:///c:/laragon/www/indraco-arsip-laravel-7/app/Http/Controllers/HealthController.php#L16-L37)
- **Target Latency**: **< 2 ms** (Ultra-lightweight).
- **Zero-DB**: Dilarang menyentuh database sama sekali di endpoint ini.
- **Zero-Session**: Dilarang membaca, membuat, atau mengunci session Laravel.
- **Cache-Control Headers**: Wajib menyertakan:
  ```http
  Cache-Control: no-cache, no-store, must-revalidate
  Pragma: no-cache
  Expires: 0
  X-Content-Type-Options: nosniff
  ```
- **Fungsi**: Digunakan oleh LAN Monitor widget di navbar ([`lan_monitor.blade.php`](file:///c:/laragon/www/indraco-arsip-laravel-7/resources/views/layouts/partials/lan_monitor.blade.php)) untuk mendeteksi latency dan kestabilan koneksi client-to-host setiap 3-5 detik tanpa membebani server host.

### 4.3. Isolasi Telemetry & Diagnostic Endpoints
File: [`routes/web.php`](file:///c:/laragon/www/indraco-arsip-laravel-7/routes/web.php#L14-L20)
- Endpoint kalkulasi berat:
  - `GET /api/health/metrics` (inspeksi RAM, disk size WAL, OPcache statistics)
  - `GET /api/health/probe-ip`
  - `GET /api/health/logs` (membaca file disk log)
  - `GET /diagnostics` (dashboard visual)
- **Aturan Keamanan & Performa**: Endpoint di atas **WAJIB** dilindungi oleh middleware `diagnostics.auth`.
- **DILARANG** melakukan polling berkala ke `/api/health/metrics` dari browser klien biasa. Polling metrics hanya diizinkan di halaman Diagnostics oleh Super Admin.

---

## 5. Layer 4: Laravel Framework Lifecycle & Pre-Compilation

Cold-start overhead framework dihilangkan sepenuhnya melalui pre-compilation saat startup.

### 5.1. Startup Pre-compilation Sequence
File: [`START-DMS-INDRACO.bat`](file:///c:/laragon/www/indraco-arsip-laravel-7/START-DMS-INDRACO.bat#L220-L227)
```bat
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
1. **`config:cache`**: Menggabungkan puluhan file konfig ke satu flat PHP array di `bootstrap/cache/config.php`. Menghemat puluhan disk reads per request.
2. **`route:cache`**: Mengubah route tree dan middleware stack menjadi pre-compiled closure/array. Mengurangi overhead regex parsing route hingga 85%.
3. **`view:cache`**: Mengompilasi seluruh file Blade template ke PHP murni saat startup. Mengeliminasi kompilasi Blade yang lambat saat user pertama kali membuka halaman.

### 5.2. Aturan Saat Development
Setiap kali menambah route baru, mengubah file `.env`, atau memperbarui file config di `config/`, cache wajib dibersihkan:
```bash
php artisan optimize:clear
```
Sebelum rilis/merge, jalankan kembali ketiga perintah pre-compile di atas untuk memverifikasi tidak ada closure route atau circular dependency yang merusak cache.

---

## 6. Layer 5: Frontend Asset Delivery — Zero CDN & 100% Offline

Aplikasi diwajibkan berjalan **100% offline tanpa koneksi internet**. Kegagalan memuat CDN di jaringan tertutup akan menyebabkan browser freeze atau menunggu DNS timeout (3-10 detik blocking render).

### 6.1. Asset Budget & Vendor Inventory
Seluruh library frontend disimpan secara lokal di `public/`:

| Nama Library | Path File Lokal | Ukuran File | Status |
|---|---|---|---|
| **Tailwind CSS** (Play Build) | `public/js/vendor/tailwindcss.js` | 440.6 KB | Lokal (Offline) |
| **Lucide Icons** | `public/js/vendor/lucide.min.js` | 349.7 KB | Lokal (Offline) |
| **Chart.js** | `public/js/vendor/chart.min.js` | 194.9 KB | Lokal (Offline) |
| **Alpine.js** | `public/js/vendor/alpine.min.js` | 43.7 KB | Lokal (Offline) |
| **QRCode.js** | `public/js/vendor/qrcode.min.js` | 19.5 KB | Lokal (Offline) |
| **Custom Fonts CSS** | `public/css/fonts.css` | 0.5 KB | Lokal (Offline) |
| **TOTAL ASSET BUDGET** | | **~1.05 MB** | **Zero External HTTP** |

### 6.2. Aturan Penyertaan Aset (Asset Inclusion Rules)
1. **DILARANG KERAS** menggunakan CDN eksternal:
   - ❌ `<script src="https://cdn.jsdelivr.net/..."></script>`
   - ❌ `<link href="https://fonts.googleapis.com/..." rel="stylesheet">`
2. **Sentralisasi di `head_assets.blade.php`**:
   - Seluruh layout template (`app.blade.php`, `login.blade.php`, `desktop_pic.blade.php`) **WAJIB** menyertakan:
     ```blade
     @include('layouts.partials.head_assets')
     ```
3. **Wajib Menggunakan Helper `asset(...)`**:
   - Semua path aset statis harus dibungkus helper: `{{ asset('js/vendor/alpine.min.js') }}`.
4. **Document Root Server**:
   - Server `php artisan serve` secara otomatis menetapkan direktori `public/` sebagai document root.
   - **DILARANG** menjalankan manual `php -S` dari root project tanpa parameter `-t public`, karena akan menyebabkan seluruh CSS dan JS menghasilkan HTTP 404.

### 6.3. Standar Modal Konfirmasi UI (Zero Native Browser Popups)
Untuk menjaga konsistensi tema (Tailwind Dark Mode / Slate / Amber / Rose) dan mencegah tampilan primitif browser OS:
- **DILARANG KERAS** menggunakan `confirm(...)` atau `window.confirm(...)` bawaan browser.
- **Gunakan Centralized Modal**: [`layouts/partials/confirm_modal.blade.php`](file:///c:/laragon/www/indraco-arsip-laravel-7/resources/views/layouts/partials/confirm_modal.blade.php).
- **Form HTML Declarative**:
  ```html
  <button type="submit" 
          data-confirm="Pesan konfirmasi..." 
          data-confirm-title="Judul Modal" 
          data-confirm-type="danger|warning|success|info" 
          data-confirm-btn="Label Tombol">
  ```
- **JavaScript / Alpine.js (Promise-based)**:
  ```javascript
  const ok = await window.showConfirmModal({
      title: 'Judul Konfirmasi',
      message: 'Pesan penjelasan...',
      type: 'danger', // danger | warning | success | info
      confirmText: 'Ya, Lanjutkan'
  });
  if (!ok) return;
  ```

---

## 7. Layer 6: Host OS Process & Zero-Scroll Terminal Logging

### 7.1. Terminal Buffer Bottleneck pada Windows
Pada Windows (`cmd.exe` / `conhost.exe`), setiap baris HTTP request yang dicetak ke console terminal memicu:
1. Console rendering redraw lock pada CPU host.
2. Peningkatan beban CPU drastis saat request rate tinggi (misal 5 klien me-refresh halaman bersamaan).
3. Banner petunjuk IP server tergulung (scrolled out of view).

### 7.2. Implementasi Zero-Scroll I/O Redirection
File: [`START-DMS-INDRACO.bat`](file:///c:/laragon/www/indraco-arsip-laravel-7/START-DMS-INDRACO.bat#L265-L275)
```bat
php artisan serve --host=0.0.0.0 --port=8000 > "storage\logs\http-access.log" 2>&1
```
- Seluruh output stdout/stderr dialihkan langsung ke file log di disk.
- Terminal host tetap bersih, menampilkan banner informasi IP lokal, dan **terkunci pada posisi zero-scroll**.
- CPU host terbebas dari overhead rendering console buffer.
- Log akses tetap dapat dipantau oleh admin via `storage/logs/http-access.log` atau melalui dashboard `/diagnostics`.

---

## 8. Layer 7: Benchmarking & Load Testing Standards (k6)

Untuk membuktikan performa secara empiris, pengujian beban menggunakan Grafana k6 telah disiapkan.

### 8.1. Konfigurasi Benchmark
File: `tests/k6/load_test.js` (lokal, diabaikan oleh `.gitignore`)  
Launcher: `RUN-K6-BENCHMARK.bat` (lokal)

### 8.2. Profile Beban Kerja (Workload Profiles)
1. **Quick Check** (`MODE=quick`):
   - 2 Virtual Users (VUs), durasi 6 detik.
   - Threshold: `http_req_duration p(95) < 1500ms`, `failed_requests_rate < 0.05`.
2. **Realistic Office LAN** (`MODE=office` - Default):
   - Ramp-up 3 VUs (3s) -> Peak 5 VUs (8s) -> Ramp-down (3s).
   - Menguji interaksi nyata: Ping, Login, Dashboard query, Arsip Search, Detail view.
   - Threshold: `http_req_duration p(95) < 1500ms`, `failed_requests_rate < 0.05`.
3. **Stress Test** (`MODE=stress`):
   - Ramp-up hingga 12 VUs.
   - Threshold: `http_req_duration p(95) < 2500ms`, `failed_requests_rate < 0.10`.

### 8.3. Aturan Benchmarking untuk Fitur Baru
Jika menambahkan fitur berat (misal pencarian full-text arsip, upload berkas masal, atau laporan rekapitulasi):
1. Tambahkan scenario endpoint ke `tests/k6/load_test.js`.
2. Jalankan benchmark `MODE=office`.
3. Pastikan `p(95)` tetap berada di bawah 1500 ms dan error rate 0%.

