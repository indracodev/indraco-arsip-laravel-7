---
trigger: model_decision
description: Saat mengubah launcher .bat/.vbs, Caddyfile, php-cgi pool, endpoint health/diagnostik, layout/aset front-end, modal konfirmasi, logging, atau uji beban k6
---

# DMS PT INDRACO — Project Rules (Bagian 3/3): Network, Lifecycle, Frontend Offline, Logging & Benchmark — Laravel 13

> Bagian dari [`PROJECT_RULES.md`](PROJECT_RULES.md).

## 4. Layer 3: Network, Web Server & LAN

### 4.1. Web server
- **Caddy** (`evironment/caddy/caddy.exe`) listen `:8000` di semua interface (LAN), `root` = `public/`, `encode gzip`, `php_fastcgi` ke **pool php-cgi** di `127.0.0.1:9001..900N`.
- Jumlah worker di satu tempat konfigurasi; default dipilih dari uji beban di AMD A9.
- Worker php-cgi diawasi dan dinyalakan ulang otomatis (php-cgi Windows berhenti setelah `PHP_FCGI_MAX_REQUESTS`). Pengawas **tidak boleh** polling sibuk (CPU idle ~0%).
- Menutup jendela launcher atau `STOP-DMS-INDRACO.bat` mematikan Caddy, semua php-cgi, dan pengawasnya.
- **DILARANG** bind ke `127.0.0.1` saja di launcher produksi.
- **HTTPS LAN**: `https://:8443` dengan `tls internal` Caddy (SAN: localhost, 127.0.0.1, hostname, IP LAN saat start); `http://:8000` tetap ada sampai semua klien memercayai root CA. Root CA di `storage/caddy/` (persisten, di-ignore git, ikut di-backup), sertifikat publiknya bisa diunduh klien; kunci privat tidak pernah disajikan. Klien memasang lewat `INSTALL-SERTIFIKAT-KLIEN.bat`.
- URL dan aset mengikuti skema/host request (tanpa mixed content).

### 4.2. Kontrak `GET /api/health/ping`
- Target **< 2 ms**. **Zero-DB**, **zero-session** (tanpa middleware session/cookie).
- Header wajib: `Cache-Control: no-cache, no-store, must-revalidate`, `Pragma: no-cache`, `Expires: 0`, `X-Content-Type-Options: nosniff`.
- Dipakai widget LAN monitor di navbar (polling 3–5 dtk).

### 4.3. Endpoint diagnostik/telemetry
- `/api/health/metrics`, `/api/health/probe-ip`, `/api/health/logs`, `/diagnostics`: hanya admin (middleware khusus diagnostik).
- **DILARANG** polling metrics dari browser klien biasa; hanya di halaman Diagnostics.

## 5. Layer 4: Framework Lifecycle & Pre-Compilation
- Launcher menjalankan `php artisan optimize` (config, route, view, event cache) saat start dan setelah update.
- Development: `php artisan optimize:clear` setelah mengubah route/.env/config.
- Tidak ada closure di route; tidak ada `env()` di luar `config/`.

## 6. Layer 5: Frontend — Zero CDN & 100% Offline
- Aplikasi **wajib** jalan tanpa internet. CDN di jaringan tertutup membuat browser menunggu DNS timeout 3–10 dtk.
- **DILARANG** `<script src="https://...">`, `fonts.googleapis.com`, ikon/font dari CDN.
- **CSS murni + Alpine.js lokal, tanpa build tool** (bukan Tailwind Play JS 440 KB seperti L7, bukan Vite). File di `public/css`, `public/js`, ikut di-commit; versi aset lewat `?v=<hash isi file>`. Font dan ikon lokal (SVG, hanya ikon yang dipakai).
- Satu titik penyertaan aset di layout (partial `head-assets`). Aset statis di-cache panjang oleh Caddy karena URL ber-versi.
- Library JS hanya yang lolos triage; target ukuran di `PROJECT_RULES.md` §1.1. Ringan untuk PC klien low-spec: tanpa animasi berat, tanpa library besar untuk hal yang bisa dikerjakan HTML/CSS/Alpine.

### 6.2. PWA
- `manifest.webmanifest` + ikon lokal; service worker **hanya** meng-cache aset statis ber-hash dari `public/build` dan ikon. **DILARANG** meng-cache halaman dinamis, respons API, data arsip, atau file scan.
- Versi cache mengikuti hash build; cache lama dihapus saat aktivasi.
- Halaman offline sederhana: "Server DMS tidak terjangkau" (sama tujuan dengan pengecekan ping di klien exe).

### 6.3. Modal konfirmasi (tanpa popup native)
- **DILARANG** `confirm()`/`window.confirm()`/`alert()`.
- Satu komponen modal konfirmasi terpusat, dipakai lewat atribut deklaratif (`data-confirm`, `data-confirm-title`, `data-confirm-type="danger|warning|success|info"`, `data-confirm-btn`) dan fungsi promise `window.showConfirmModal({...})`, sama seperti kontrak Laravel 7.

## 7. Layer 6: Zero-Scroll Terminal Logging
- Output Caddy dan php-cgi dialihkan ke `storage/logs/` (mis. `http-access.log`, `php-cgi-<n>.log`). Terminal launcher hanya menampilkan banner (IP LAN, URL, status) dan tidak bergulir.

## 8. Layer 7: Benchmark k6
- Skrip `tests/k6/load_test.js` (lokal, di-ignore git) + `RUN-K6-BENCHMARK.bat`.
- Profil: **quick** (2 VUs, 6 dtk), **office** (3→5 VUs), **stress** (sampai 12 VUs), **multiuser** (30 VUs, 85% baca/15% tulis, 60 dtk), **slow-request** (1 request 3 dtk + user lain).
- Fitur berat baru (pencarian, upload massal, laporan, impor) wajib ditambahkan sebagai skenario dan lulus SLA `PROJECT_RULES.md` §1.1.
