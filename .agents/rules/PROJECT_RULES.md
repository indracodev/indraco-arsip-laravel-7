# DMS PT INDRACO — Performance Implementation Rules & Standards

> **Status Dokumen**: Mandatory Technical Contract  
> **Target Audience**: AI Agents & Software Engineers  
> **Tujuan**: Mencegah regresi performa pada setiap penambahan fitur baru, controller baru, query database, atau perubahan konfigurasi.  
> **Baseline Hardware**: AMD A9 Dual-Core Processor, 8GB RAM, Windows OS, Offline LAN Host.

---

## 1. Prinsip Utama & Performance Target (SLA)

Aplikasi DMS PT Indraco beroperasi sebagai **Desktop Offline Server** yang melayani multiple client di jaringan lokal (LAN) menggunakan built-in PHP server (`php artisan serve`) dan database SQLite.

### 1.1. Target SLA (Service Level Agreement)
| Metrik | Ambang Batas (SLA) | Kondisi Pengujian |
|---|---|---|
| **LAN Heartbeat Ping** (`/api/health/ping`) | **< 2 ms** (avg), **< 10 ms** (p95) | LAN Concurrency 5-10 VUs |
| **API / JSON Response** | **< 150 ms** (p95) | Single Client |
| **Blade Page Render** (Dashboard/Index) | **< 350 ms** (p95) | Cold/Warm cache |
| **Concurrent LAN Users** (Office Profile) | **p(95) < 1500 ms**, **Failure Rate < 5%** | 3 - 5 Concurrent VUs (k6) |
| **Host Memory Ceiling** | **< 256 MB** per PHP worker process | Idle memory ~25-45 MB |
| **Host CPU Ceiling** | **< 15%** saat idle di AMD A9 Dual-Core | Idle CPU ~0-2% |

### 1.2. The Zero-Regression Rule
Setiap fitur baru yang ditambahkan **DILARANG** menyebabkan peningkatan latency pada endpoint existing atau membebani CPU host secara kontinu.

---

## 2. Layer 1: PHP Runtime, OPcache & JIT Optimization

PHP dijalankan via portable runtime di `evironment/php-8.2.29-Win32-vs16-x64/` (perhatikan typo `evironment` — ini path baku project).

### 2.1. Konfigurasi `php.ini` Wajib
File: `evironment/php-8.2.29-Win32-vs16-x64/php.ini`
```ini
[PHP]
memory_limit = 256M
max_execution_time = 60

[opcache]
zend_extension = opcache
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.validate_timestamps = 1
opcache.save_comments = 1

; JIT Mode: 1255 = CRTO (Tracing JIT, loop & hot-path optimization)
opcache.jit = 1255
opcache.jit_buffer_size = 64M
```

### 2.2. Rationale & Aturan Developer
- **`opcache.enable_cli = 1` WAJIB**: `php artisan serve` berjalan di bawah PHP CLI SAPI. Tanpa opsi ini, OPcache dan JIT **tidak akan pernah aktif**.
- **JIT `1255`**: Mengoptimalkan hot functions and loops pada CPU berdaya rendah (AMD A9).
- **Interned Strings (`16MB`)**: Mencegah duplikasi string konstan Laravel di memori.
- **DILARANG** menggunakan `eval()`, nested dynamic variable variable (`$$var`), atau regex recursive tak terbatas yang mematikan efektivitas tracing JIT.

---

## 3. Layer 2: SQLite Concurrency & I/O Performance

Aplikasi menggunakan SQLite (`database/database.sqlite`) untuk portabilitas tanpa dependensi database server eksternal. Karena SQLite menggunakan file-level locking, optimasi concurrency dan I/O adalah prioritas mutlak.

### 3.1. PRAGMAs Wajib pada Booting Koneksi
File: [`app/Providers/AppServiceProvider.php`](file:///c:/laragon/www/indraco-arsip-laravel-7/app/Providers/AppServiceProvider.php#L31-L45)  
Event: `ConnectionEstablished`

```php
Event::listen(ConnectionEstablished::class, function ($event) {
    if ($event->connection->getDriverName() === 'sqlite') {
        try {
            $event->connection->statement('PRAGMA journal_mode = WAL;');
            $event->connection->statement('PRAGMA busy_timeout = 5000;');
            $event->connection->statement('PRAGMA synchronous = NORMAL;');
            $event->connection->statement('PRAGMA temp_store = MEMORY;');
            $event->connection->statement('PRAGMA cache_size = -32000;');
            $event->connection->statement('PRAGMA mmap_size = 67108864;');
        } catch (\Exception $e) {
            // Fallback gracefully
        }
    }
});
```

### 3.2. Penjelasan Teknis PRAGMA
1. **`journal_mode = WAL` (Write-Ahead Logging)**:
   - Mengubah mekanisme locking dari rollback journal ke WAL.
   - **Multi-Client Concurrency**: Readers tidak memblokir writers, dan writer tidak memblokir readers. Ini esensial saat banyak user LAN mengakses arsip bersamaan.
2. **`busy_timeout = 5000`**:
   - Jika write lock sedang digunakan transaksi lain, SQLite menunggu hingga 5.000 ms sebelum melemparkan error `SQLITE_BUSY` (500 Server Error).
3. **`synchronous = NORMAL`**:
   - Hanya melakukan disk sync (`fsync`) pada titik WAL checkpoint. Menghasilkan peningkatan kecepatan write 10x-50x dibanding mode `FULL` tanpa risiko korupsi database pada aplikasi desktop.
4. **`temp_store = MEMORY`**:
   - Temporary tables, indeks sementara, dan sorting (`ORDER BY`, `GROUP BY`) disimpan di RAM host, bukan di disk. Mengurangi disk I/O hingga 80%.
5. **`cache_size = -32000`**:
   - Mengalokasikan 32 MB SQLite page cache di RAM host.
6. **`mmap_size = 67108864`**:
   - Mengalokasikan 64 MB memory-mapped I/O. Pembacaan halaman database langsung melewati buffer kernel OS tanpa overhead `read()` syscall.

### 3.3. Aturan Query & Eloquent untuk Fitur Baru
- **Wajib Eager Loading**: **DILARANG KERAS** membiarkan N+1 queries. Selalu gunakan `Model::with(['relasi1', 'relasi2'])` pada data relasional.
- **Indexing Mandatory**: Setiap foreign key (`arsip_id`, `user_id`, `kategori_id`) dan kolom filter/pencarian (`nomor_surat`, `tgl_terima`, `status`) **WAJIB** memiliki index pada migrasi database.
- **Dilarang Long-Running DB Transaction**: Blok `DB::transaction(...)` HANYA boleh berisi query database esensial. **DILARANG** melakukan pemrosesan file, pembuatan PDF, atau komputasi berat di dalam blok transaksi karena akan menahan write lock SQLite.
- **Paging / Chunking**: Dilarang memanggil `->get()` tanpa batas pada tabel transaksi. Gunakan `paginate(20)` atau `chunk(100)` untuk background processing.
- **Global View Composer Caching**: Jika sebuah data dibagikan ke banyak Blade view (seperti `AppSetting`), data **WAJIB di-cache di in-memory static variable** (lihat implementasi di [`AppServiceProvider.php`](file:///c:/laragon/www/indraco-arsip-laravel-7/app/Providers/AppServiceProvider.php#L48-L75)) agar tidak query ke database setiap kali Blade me-render partial view.

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

---

## 9. Performance Gate Checklist (Wajib Lulus Sebelum Merge)

Sebelum melakukan commit atau merge fitur baru pada branch `danu-revisi-3`, pastikan seluruh checklist performa berikut terpenuhi:

- [ ] **100% Offline Integrity**: Tidak ada panggilan URL eksternal (CDN font, icon, script, atau API) di kode baru.
- [ ] **Asset Budget Compliant**: Asset baru (jika ada) disimpan di `public/` dan dimuat via `head_assets.blade.php`.
- [ ] **Zero N+1 Eloquent Queries**: Semua pemanggilan relasi di Controller/Repository menggunakan eager loading (`with(...)`).
- [ ] **SQLite PRAGMAs Intact**: Tidak ada kode yang mengubah konfigurasi WAL atau busy timeout di `AppServiceProvider`.
- [ ] **Short Transaction Scope**: Blok `DB::transaction()` hanya membungkus penulisan database (tidak ada manipulasi gambar/file/PDF di dalam transaksi).
- [ ] **Lightweight Ping Intact**: Endpoint `/api/health/ping` tetap bebas dari pemanggilan database dan session.
- [ ] **Diagnostics Protected**: Endpoint analitik/telemetry baru dilindungi oleh middleware `diagnostics.auth`.
- [ ] **Pre-compile Verification**: Perintah `php artisan optimize` atau `config:cache && route:cache && view:cache` berjalan tanpa error.
- [ ] **Terminal Zero-Scroll Preserved**: Launcher `.bat` tetap mengarahkan log server ke `storage/logs/http-access.log`.
- [ ] **LAN Accessibility Verified**: Server dapat diakses dari browser host (`127.0.0.1:8000`) dan perangkat LAN (`http://<IP>:8000`).

---

> **Revisi Terakhir**: 6 Oktober 2026  
> **Target Branch**: `danu-revisi-3`  
> **Baseline Commit**: `a283eae` (Cleaned & Locked IP zero-scroll launcher)
