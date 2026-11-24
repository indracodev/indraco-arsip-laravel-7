---
trigger: always_on
---

# DMS PT INDRACO — Performance Implementation Rules & Standards (Bagian 1/3) — Laravel 13

> **Status Dokumen**: Mandatory Technical Contract
> **Target Audience**: AI Agents & Software Engineers
> **Tujuan**: Mencegah regresi performa pada setiap fitur, controller, query, atau perubahan konfigurasi.
> **Baseline Hardware (server)**: PC low-spec — RAM 4–8 GB, CPU dual-core atau lebih lemah (uji dengan 1 dan 2 core), **tanpa GPU**, Windows 10/11 64-bit, Offline LAN Host. Acuan lama: AMD A9 dual-core 8 GB. Klien: PC kantor low-spec.
> **Cara jalan**: cukup klik `START-DMS-INDRACO.bat` sekali; tanpa install apa pun.
> **Versi**: diperbarui untuk rebuild Laravel 13 (branch `laravel-13-rebuild`). Versi Laravel 7 tetap ada di branch `danu-revisi-4`.

## Struktur Dokumen

| Bagian | File | Cakupan |
|---|---|---|
| 1/3 | `PROJECT_RULES.md` (file ini) | §1 SLA, §2 PHP Runtime/OPcache/JIT, §9 Performance Gate Checklist |
| 2/3 | [`PROJECT_RULES_SQLITE.md`](PROJECT_RULES_SQLITE.md) | §3 SQLite Concurrency, I/O, Integritas Data & Aturan Kerja Agent |
| 3/3 | [`PROJECT_RULES_LAN_FRONTEND_OPS.md`](PROJECT_RULES_LAN_FRONTEND_OPS.md) | §4–§8 Network/Web Server, Lifecycle, Frontend Offline, Logging, k6 |

Aturan pendukung: `00-project-scope.md` (lingkup, UX tetap), `10-safety.md`, `20-architecture.md`, `50-frontend-blade.md` (desain UI), `60-security.md`, `engineering_guidelines.md`, `code_file_size_limits.md`.

Seluruh bagian mengikat (**Mandatory**); agent yang mengubah sebuah layer **WAJIB** membaca bagian yang sesuai.

---

## 1. Prinsip Utama & Performance Target (SLA)

Aplikasi berjalan sebagai **Desktop Offline Server** di satu PC Windows dan melayani banyak klien di LAN lewat **Caddy + pool php-cgi** (bukan lagi `php artisan serve`, yang di Windows hanya memproses satu request sekali jalan) dengan database SQLite.

### 1.1. Target SLA

| Metrik | Ambang Batas | Kondisi Pengujian |
|---|---|---|
| **LAN Heartbeat Ping** (`/api/health/ping`) | **< 2 ms** avg, **< 10 ms** p95 | 5–10 VUs |
| **API / JSON Response** | **< 150 ms** p95 | Single client |
| **Blade Page Render** (Dashboard/Index) | **< 350 ms** p95 | Cache produksi aktif |
| **Concurrent LAN Users** (Office Profile) | **p95 < 1500 ms**, failure < 5% | 3–5 VUs (k6) |
| **Multi-user rebuild** (mix 85% baca / 15% tulis) | **p95 < 500 ms**, 0 error, 0 `database is locked` | 30 VUs, 60 dtk |
| **Request lambat tidak memblok user lain** | Halaman ringan tetap < 350 ms saat ada request 3 dtk berjalan | uji khusus |
| **Host Memory Ceiling** | **< 256 MB** per worker php-cgi; **total server ≤ 300 MB idle, ≤ 600 MB saat beban** (Caddy + semua php-cgi + pengawas) | diukur dengan WorkingSet/Private bytes |
| **Host CPU Ceiling** | **< 15%** saat idle di dual-core | idle ~0–2% (tanpa polling sibuk) |
| **Uji low-spec** | Semua target di atas diuji dengan CPU dibatasi **1 core dan 2 core** (affinity) | jumlah worker default dipilih dari hasil 2 core |
| **Aset awal halaman** (gzip) | JS < 150 KB, CSS < 60 KB | halaman role departemen |

### 1.2. The Zero-Regression Rule
Setiap fitur baru **DILARANG** menaikkan latency endpoint yang sudah ada atau membebani CPU host secara kontinu.

---

## 2. Layer 1: PHP Runtime, OPcache & JIT

PHP portable di `evironment/php-8.4.*-nts-Win32-vs17-x64/` (perhatikan typo `evironment` — path baku project). Build **NTS** karena dijalankan sebagai php-cgi (FastCGI). DLL VC++ (`vcruntime140.dll`, `vcruntime140_1.dll`, `msvcp140.dll`) ikut di folder yang sama.

### 2.1. `php.ini` wajib

```ini
[PHP]
memory_limit = 256M
max_execution_time = 120
expose_php = Off
display_errors = Off
log_errors = On
upload_max_filesize = 64M
post_max_size = 64M
date.timezone = Asia/Jakarta

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
; Windows: hindari fatal "Opcode handlers are unusable due to ASLR"
opcache.file_cache = "${TEMP}"
opcache.file_cache_fallback = 1
; JIT 1255 (tracing) bila stabil dengan php-cgi NTS; hasil uji dicatat di context/decisions.md
opcache.jit = 1255
opcache.jit_buffer_size = 64M
```

### 2.2. Aturan developer
- OPcache wajib aktif di php-cgi (web) dan CLI (artisan).
- Setelah update kode (`UPDATE-AND-RESTART.bat`), pool php-cgi di-restart agar OPcache memuat kode baru.
- **DILARANG** `eval()`, variable variable (`$$var`), atau regex rekursif tak terbatas.

---

## 9. Performance Gate Checklist (wajib lulus sebelum commit modul)

- [ ] **100% Offline Integrity**: tidak ada URL eksternal (CDN font/ikon/script/API) di kode.
- [ ] **Asset Budget Compliant**: aset lewat Vite build lokal; JS/CSS awal sesuai SLA §1.1.
- [ ] **Zero N+1**: `Model::preventLazyLoading()` aktif di luar production dan tes tidak memicu lazy loading.
- [ ] **SQLite Config Intact**: kunci SQLite di `config/database.php` tidak diubah tanpa uji beban ulang.
- [ ] **Short Transaction Scope**: transaksi hanya membungkus tulis DB (tanpa file/PDF/HTTP di dalamnya).
- [ ] **Lightweight Ping Intact**: `/api/health/ping` tanpa DB dan tanpa session.
- [ ] **Diagnostics Protected**: endpoint diagnostik hanya untuk admin.
- [ ] **Pre-compile Verification**: `php artisan optimize` (config/route/view/event cache) sukses.
- [ ] **Terminal Zero-Scroll Preserved**: log server ke `storage/logs`, banner IP tetap terlihat.
- [ ] **LAN Accessibility Verified**: `127.0.0.1:8000` dan `http://<IP>:8000` bisa dibuka.
- [ ] **Atomic Counter**: penomoran memakai `UPDATE ... RETURNING`; preview tanpa side-effect.
- [ ] **Unique + Validasi Berpasangan** (§3.5.c).
- [ ] **Immediate Transaction Bersih** (§3.5.d).
- [ ] **Double-Submit & Optimistic Lock** (§3.5.e–f).
- [ ] **Diuji di browser** (remote, Chrome): halaman yang diubah dibuka, alur dicoba, console bersih, lebar 360/768/1366 px.
- [ ] **Temuan dicatat dan diperbaiki**: setiap temuan masuk `context/findings.md` dengan status; yang belum diperbaiki punya alasan di `context/decisions.md`.
- [ ] **Clean Diff & Bukti Test**: line ending tidak berubah, output asli `php artisan test` dilampirkan.
