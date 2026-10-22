# DMS PT INDRACO — Performance Implementation Rules & Standards (Bagian 1/3)

> **Status Dokumen**: Mandatory Technical Contract  
> **Target Audience**: AI Agents & Software Engineers  
> **Tujuan**: Mencegah regresi performa pada setiap penambahan fitur baru, controller baru, query database, atau perubahan konfigurasi.  
> **Baseline Hardware**: AMD A9 Dual-Core Processor, 8GB RAM, Windows OS, Offline LAN Host.

---

## Struktur Dokumen

| Bagian | File | Cakupan |
|---|---|---|
| 1/3 | `PROJECT_RULES.md` (file ini) | §1 SLA, §2 PHP Runtime/OPcache/JIT, §9 Performance Gate Checklist |
| 2/3 | [`PROJECT_RULES_SQLITE.md`](PROJECT_RULES_SQLITE.md) | §3 SQLite Concurrency, I/O, Integritas Data & Aturan Kerja Agent |
| 3/3 | [`PROJECT_RULES_LAN_FRONTEND_OPS.md`](PROJECT_RULES_LAN_FRONTEND_OPS.md) | §4–§8 Network, Lifecycle, Frontend Offline, Terminal Logging, k6 Benchmark |

Seluruh bagian sama-sama mengikat (**Mandatory**); agent yang mengubah sebuah layer **WAJIB** membaca bagian yang sesuai.

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

## 9. Performance Gate Checklist (Wajib Lulus Sebelum Merge)

Sebelum melakukan commit atau merge fitur baru pada branch `danu-revisi-4`, pastikan seluruh checklist performa berikut terpenuhi:

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
- [ ] **Atomic Counter**: Penomoran/counter baru memakai `UPDATE ... RETURNING`, bukan baca lalu tulis di PHP; preview tanpa side-effect (3.5.a–b, `PROJECT_RULES_SQLITE.md`).
- [ ] **Unique + Validasi Berpasangan**: Kolom unik punya UNIQUE index, dan setiap input manualnya divalidasi dengan kondisi yang sama (3.5.c).
- [ ] **Immediate Transaction Bersih**: Transaksi baca→tulis memakai `db_immediate_transaction()` tanpa nested `DB::transaction()` di dalamnya (3.5.d).
- [ ] **Double-Submit & Optimistic Lock**: Aksi pengajuan punya guard server + tombol disable; form edit baru mengirim `original_updated_at` (3.5.e–f).
- [ ] **Clean Diff & Bukti Test**: Line ending tidak berubah (`git diff --stat` bersih) dan output asli `vendor/bin/phpunit` dilampirkan (3.6).

---

> **Revisi Terakhir**: 21 Oktober 2026 (aturan konkurensi SQLite multi-user, §3.5–3.6)  
> **Target Branch**: `danu-revisi-4`  
> **Baseline Commit**: `82d8aa7`
