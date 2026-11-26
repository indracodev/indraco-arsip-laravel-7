# Performance baseline

## Fase 1 (2026-11-24) — fondasi, route bench, salinan DB asli
Laptop 16 core (lebih cepat per core dari AMD A9; perkirakan 2–4× lebih lambat di server asli). CPU dibatasi dengan affinity. Skrip `tests/k6/load_test.js`.

| Core × worker | office p50/p95 ms | mixed30 (30 VU) p50/p95 ms, req/s, error | slow (3 req 3 dtk): baca p95, gagal | RAM private idle→puncak MB |
|---|---|---|---|---|
| 2 × 2 | 7,7 / 30,8 | 11,5 / 47,6, 62,9, 0 | timeout 30 dtk, 18 gagal | 101 → 107 |
| 2 × 3 | 8,2 / 34,0 | 11,9 / 39,3, 63,5, 0 | ±21 dtk, 4 gagal | 120 → 126 |
| **2 × 4** | 7,6 / 31,5 | 12,5 / 37,4, 62,2, 0 | **113,8, 0** | 140 → 149 |
| 1 × 2 | 8,5 / 36,3 | 14,3 / 55,0, 62,6, 0 | timeout, 38 gagal | 100 → 105 |
| 1 × 3 | 8,3 / 21,5 | 14,5 / 55,4, 62,8, 0 | 26 gagal | 120 → 126 |
| **1 × 4** | 7,7 / 28,0 | 14,4 / 46,6, 63,0, 0 | **79,5, 0** | 139 → 149 |
| 16 × 6 | 11,5 / 33,7 | 14,6 / 33,1, 62,9, 0 | 40,8, 0 | — |
| `artisan serve` (L7 cara lama) | 6,6 / 25,0 | 10,2 / 35,9, 63,3, 0 | **semua request ±9 dtk** | — |

- Ping (Caddy): 0,3–0,7 ms rata-rata, p95 ≤ 1,7 ms (1 core 30 VU: p95 ±18 ms).
- HTTPS vs HTTP (2×4, mixed30): p95 34,1 vs 28,7 ms; 0 error.
- 0 `database is locked` di semua skenario.
- START→SIAP (PHP membalas, semua worker hangat): 1,6–3,0 dtk (16 core), 2,1–2,8 dtk (2 core). Koordinator: 1,5 dtk.
- Idle: CPU 0 ms/20 dtk; 6 worker ≈ 137 MB private + 25 MB loop cmd + Caddy ≈ 60 MB.
- **Wajib diulang di PC server asli** sebelum cutover.

## Fase 6 / rute nyata (2026-11-25)

**Mesin uji:** laptop i7-13620H (10 core fisik = 6 P + 4 E, 16 logical, P-core ±2× lebih cepat per thread dari E-core: loop PHP 30 jt iterasi 0,39–0,42 dtk P vs 0,74–0,80 dtk E), RAM 24 GB, Windows 11, power plan *Balanced*. k6 1.7.1 jalan di laptop yang sama (ikut makan CPU). Laptop juga dipakai IDE/Chrome → angka bisa bergeser ±30 % antar-run.

**Skrip:** `tests/k6/route_matrix.js` (hanya GET, aman untuk server live) + `tests/k6/sample-resources.ps1` (RAM/CPU Caddy + php-cgi tiap detik). Login sekali per peran di `setup()` (admin, gudang, fin = PIC FAT), 3 sesi untuk semua VU, tanpa login per VU.
- Mode default: tiap VU memutar semua rute perannya **tanpa jeda** → "N VU" = N request bersamaan terus-menerus (jauh lebih berat dari N orang).
- `MODE=human`: 1 VU = 1 orang dengan aplikasi terbuka: ping LAN tiap 5 dtk (`lan.js`), poll notifikasi tiap `POLL_S` dtk ± 2 dtk dengan jeda awal acak (`notifications.js`, default 15; angka di bawah bagian ini diukur saat poll masih 5 dtk serentak), rata-rata 1 halaman tiap 20 dtk.
- Rute: dashboard admin/gudang (PIC Departemen di-redirect ke katalog), `/archives` (polos, `search=box`, `status+sort+per_page=50`), `/archives/117`, `/borrowings`, `/destructions` tab h30/extensions/destructions, `/api/realtime/check` (`since_seq`/`initial`), `/api/warehouse/layout-search?q=IND` (admin/gudang), `/files/archive/117/scan_input_form` (X-Accel, Caddy yang streaming), `/api/health/ping` (Caddy).
- Data: salinan data asli (68 arsip, 943 item, 8.001 slot rak). Instans uji terpisah memakai salinan `VACUUM INTO` di scratchpad, port 8100, worker 9101+, cache config/route sendiri (`APP_CONFIG_CACHE` dll. **harus path relatif**: path absolut Windows dianggap relatif oleh Laravel), affinity diset per proses. Server live :8000 tidak diubah/di-restart.

### Ringkasan (closed loop, tanpa jeda; p50/p95 ms; req/s total semua rute)

| Konfigurasi | 1 VU: katalog / poll | 10 VU: katalog / poll | 30 VU: katalog / poll | jenuh req/s | error |
|---|---|---|---|---|---|
| A2 live :8000 — 16 logical, 6 worker, **tanpa** cache config/route | 77/85 · 36/42 | 87/475 · 39/232 | 161/1.459 · 43/971 | 104–112 | 0 |
| A live (run 14:51, sebelum commit keamanan `efcd5d8` 15:10: AuthenticateSession, trustHosts; A2 sesudahnya ±15:20) | 36/41 · 19/20 | 53/139 · 24/200 | 74/1.216 · 33/798 | 150–154 | 0 |
| B 1 P-core + HT (mask 0x3), 4 worker, cache | 41/46 · 13/15 | 125/980 · 38/475 | 136/3.469 · 41/2.301 | 58–66 | 0 |
| **C 2 E-core (mask 0x3000), 4 worker, cache** | 67/73 · 23/27 | 212/1.102 · 72/902 | 178/3.525 · 70/2.713 | **45** | 0 |
| D 1 E-core (mask 0x1000), 4 worker, cache | 51/59 · 17/18 | 328/1.741 · 83/736 | 432/4.491 · 24/1.434 | 33 | 0 |
| E 2 E-core, 4 worker, **tanpa** cache | 82/102 · 42/48 | 243/1.561 · 142/1.316 | — | 32 | 0 |

Per rute pada C (2 E-core, cache), 1 VU p50 ms: dashboard gudang 52, katalog 67, cari 69, filter 50 baris 80, detail 50, penarikan 72, retensi 52–60, poll 21–23, preview file 20, quick search 27, ping 0,5. 50 VU closed loop: tetap 0 error, p95 4–15 dtk (antrean Caddy `lb_try_duration 30s`). Tabel lengkap tiap level/rute (p50/p95/p99/max, req/s, error) ada di output k6 (`OUT=…json/.md`); hasil mentah di scratchpad sesi ini, tidak di-commit.

### Mode human (realistis) — p95 ms

| Konfigurasi | 10 orang | 30 orang | 50 orang | req/s total (PHP) | CPU pool |
|---|---|---|---|---|---|
| **C 2 E-core, 4 worker** | halaman ≤ 64 · poll 89 | halaman ≤ 234 · poll 301 | halaman ≤ 107 · poll 409 | 4,8 / 13,9 / 23,2 (≈ separuh ping Caddy) | rata 16 %, puncak 133 % dari 1 core |
| D 1 E-core, 4 worker | — | halaman ≤ 322 · poll **8.034** | halaman ≤ 1.464 · poll 987 | 12,2 / 23,1 | puncak ±100 % |

- Ekor poll: semua VU mulai serentak → poll datang bergerombol tiap 5 dtk; worker yang sibuk membuat request menunggu dengan retry 25 ms (bukan antrean FIFO), sehingga sebagian kecil request menunggu lama. Di 1 core gerombolan pertama 30 orang = p95 8 dtk.

### Sumber daya server
- Live (6 worker, 16 logical, closed loop 1→50 VU): RAM private Caddy+php-cgi 166 → 178 MB, working set 248 → 278 MB, CPU puncak 623 % (= 6 worker penuh: pool yang membatasi, bukan CPU).
- 4 worker + Caddy (C/D/H): private 110 → 131 MB, working set ±255 MB. CPU C puncak 203 % (2 core jenuh), D 103 %.
- **SQLite:** WAL 0 byte sebelum/sesudah (semua GET; file `-wal` hilang saat koneksi terakhir tutup). **0 `database is locked` / SQLITE_BUSY** di `storage/logs`. Satu-satunya error baru di log hari ini = 5× `tempnam()` dari percobaan `config:cache` instans uji (path absolut, lihat atas), bukan dari aplikasi.

### SQL per halaman (in-process, salinan data asli & salinan ×150 = 10.200 arsip / 141.450 item)
- Semua halaman 1–15 query, tanpa N+1 (duplikat yang terlihat = eager-load relasi berbeda dengan `IN (...)`). Waktu SQL data asli 0,3–1,5 ms per halaman; render PHP/Blade 20–60 ms (E-core lebih lama) → **CPU PHP adalah bottleneck, bukan SQLite**.
- Data ×150: SQL dashboard 12–14 ms (count `strftime(retention_expiry_date)` SCAN 8,8 ms; `latest()` SCAN + temp B-tree 2–7 ms), katalog 6–12 ms (urutan default `CASE status='draft'` SCAN + sort 4–10 ms), poll 4,5 ms. **Retensi 264–721 ms dan HTML tab H-30 1,39 MB** → temuan P1.

### TTFB + berat halaman pertama (instans C 2 E-core, cache, admin, median 5×)

| Halaman | TTFB ms | HTML KB (gzip) | CSS/JS: file, KB (gzip) |
|---|---|---|---|
| `/` dashboard | 81 | 41,1 (6,4) | 11, 128 (43) |
| `/archives` | 78 | 56,9 (11,0) | 12, 134 (45) |
| `/archives/117` | 56 | 31,9 (7,2) | 13, 133 (45) |
| `/borrowings` | 87 | 72,2 (10,0) | 12, 132 (45) |
| `/destructions` | 56 | 27,6 (5,6) | 13, 133 (45) |

Muat pertama ±50–56 KB terkompresi (aset `?v=` di-cache 1 tahun → kunjungan berikut hanya HTML 6–11 KB). Ringan untuk klien 4 GB; beban klien lebih ke rendering tabel, bukan jaringan.

### Kesimpulan untuk server 2 core / 4 GB
- **Nyaman: ±40–50 pengguna bersamaan** dengan aplikasi terbuka (2 E-core: 50 orang = ±13 request PHP/dtk ≈ 30 % kapasitas jenuh 45 req/s, halaman p95 ≤ 110–230 ms). CPU server asli (kelas AMD A9/Celeron) mungkin setara atau lebih lambat dari E-core ini → anggap 40 sebagai batas nyaman, ±80–100 batas atas sebelum antre terasa. 1 core: 30 orang masih jalan tetapi ekor poll 1–8 dtk.
- RAM bukan masalah: 4 worker + Caddy ±130 MB private / ±255 MB working set.
- Bottleneck: (1) CPU boot framework per request (poll 20 ms CPU hanya untuk JSON kecil), (2) poll 5 dtk per tab = beban dominan saat ramai, (3) halaman retensi memuat semua kandidat (P1), (4) antrean Caddy tidak adil saat jenuh (p99 ≫ p50).

### P1/P3 sesudah perbaikan (2026-11-25)

Salinan data ×150 (`VACUUM INTO` + skrip skala, 10.200 arsip), instans uji terpisah :8100 (2 E-core mask 0x3000, 4 worker, cache config/route), dimulai/dihentikan sendiri lewat PID; live :8000 tidak diukur/di-restart. Sebelum = kode `67d41a2`, sesudah = commit ini; data & mesin sama.

**In-process (render penuh 1 request, admin / gudang / PIC FAT):**

| Rute | sebelum ms · query · HTML | sesudah ms · query · HTML |
|---|---|---|
| `/destructions?tab=h30` admin | 653 · 14 q · 1.393.973 B | **71 · 11 q · 111.493 B** (50 baris) |
| `?tab=h30` gudang | 295 | 48 |
| `?tab=extensions` admin/gudang | 207 / 212 | 25 / 24 |
| `?tab=destructions` admin/gudang | 210 / 231 | 34 / 32 |
| PIC FAT (3 tab) | 34–38 | 15–17 |
| `?tab=h30&page=2`, `&q=HRD` admin | — | 67–69 |

SQL tab H-30 sesudah: 7–8 ms (COUNT + 1 halaman + kandidat tanpa expiry tersimpan).

**k6 `route_matrix.js` closed loop (semua rute peran, p50/p95 ms):**

| Rute | 1 VU sebelum → sesudah | 10 VU sebelum → sesudah |
|---|---|---|
| destr_h30 | 936/959 → **129/148** | 1.225/2.363 → **229/1.822** |
| destr_ext | 525/549 → **73/76** | 633/1.936 → **147/702** |
| destr_bap | 536/579 → **82/102** | 564/1.257 → **152/779** |
| poll | 40/44 → 41/44 | 151/2.451 → 100/744 |
| req/s total | 5,6 → **15,4** | 20,0 → **36,0** |

**k6 `MODE=human`, 50 orang, 40 dtk** (skrip kini: ping 5 dtk, poll `POLL_S` dtk ± 2 dtk dengan jeda awal acak, default 15):

| Varian | poll req/s · p95 ms | tab H-30 p95 | req/s total (PHP tanpa ping) |
|---|---|---|---|
| sebelum (poll 5 dtk serentak, P1 belum) | 9,8 · 3.504 | 2.330 | 22,9 (13,1) |
| P1 saja (`POLL_S=5` + jitter) | 6,3 · 69 | 361 | 19,0 (8,8) |
| **P1 + P3 (15 dtk)** | **2,8 · 68** | **206** | **15,9 (5,3)** |

Catatan: 40 dtk termasuk jeda awal acak, jadi poll 15 dtk tunak = 50/15 ≈ 3,3 req/s (−67 % dari 10). Ekor poll lama terutama karena request retensi yang berat + gerombolan serentak; P1 sudah memangkas p95, P3 memangkas jumlah request PHP. Hasil mentah (`P1-*.md/json`, `P3-*.md/json`, `p1-inproc-*.txt`) di scratchpad sesi, tidak di-commit.

### Rekomendasi (belum dikerjakan)
1. **Pastikan cache produksi aktif**: saat diukur, live :8000 jalan **tanpa** `bootstrap/cache/config.php` + `routes-v7.php` (file tidak ada saat diukur; kemungkinan terhapus oleh tes/`optimize:clear` sesudah START). Tanpa cache: poll 42 vs 21 ms, jenuh 32 vs 45 req/s (−30 %). Setelah `php artisan test`/`optimize:clear` di PC server, jalankan `php artisan optimize` atau START ulang.
2. ~~Poll notifikasi 5 dtk → 15 dtk + jitter~~ **dikerjakan (P3)**; sisa opsi: atau jitter awal acak 0–5 dtk; paling hemat: Caddy menyajikan file `seq` statis (tanpa PHP) dan PHP hanya dipanggil bila seq berubah.
3. ~~P1 retensi~~ **dikerjakan**: hitung hanya tab aktif, saring jatuh tempo di SQL (`COALESCE(retention_expiry_date, …)`), paginasi.
4. Index kecil bila data tumbuh: `archives(created_at, id)` (dashboard `latest()`), `archive_extension_logs(archive_id, approval_status)` (lihat P2). Dampak sekarang < 10 ms pada 10 rb arsip.
5. Worker 4 untuk 2 core tetap (Fase 1); jangan naikkan worker di 1–2 core (hanya menambah antrean CPU).
6. **Ulangi di PC server asli** (`k6 run -e TARGET_URL=http://<ip>:8000 -e MODE=human -e LEVELS=10,30,50 -e HOLD=40 tests/k6/route_matrix.js`).
- Beban tulis (submit arsip, verifikasi nomor box bersamaan) **tidak** diukur ulang di fase ini; acuan tetap Fase 1 mixed30 (15 % tulis, 0 locked) + tes konkurensi `sqlite-concurrency`.

## Uji beban tulis (2026-11-26)

**Skrip:** `tests/k6/write_flow.js` (MENULIS data, judul `LOADTEST-<RUN_ID>-...`) + `php artisan dms:loadtest-verify [run]` + snapshot/restore `dms:snapshot` / `dms:restore-snapshot` (`UJI-BEBAN-1/2/3-*.bat`). Runbook server: `runbook-uji-beban-server.md` (termasuk uji dari 2 PC).
- 1 VU = 1 PIC Departemen (fin, hrd, hcs, dm, mkt bergiliran) + 1 PIC Gudang, masing-masing login sendiri (sesi per VU, bukan sesi bersama: flash/toast per sesi). Per iterasi: ajukan 2 box × 2 butir + scan PDF 50–200 KB → verifikasi + nomor box (counter global, rebutan) → check-in rak acak yang tersedia → box A: penarikan → keluarkan (2/3) / tolak (1/3) → box B: perpanjangan + ACC → usul pemusnahan + setujui + eksekusi BAP (+ sertifikat PDF). ±32 request/iterasi. Waktu langkah = POST saja (`redirects: 0`); sukses = halaman tujuan redirect menampilkan toast sukses.
- Instans uji terpisah :8100 (bukan live :8000): salinan `VACUUM INTO` data laptop (69 arsip) **dan** salinan `storage/` sendiri (`LARAVEL_STORAGE_PATH` + cache config/route sendiri → upload, sesi, log uji tidak menyentuh storage project), 2 E-core (mask 0x3000), 4 worker, cache config/route/view. k6 di laptop yang sama.

**Run w2 (closed loop, tiap level 30 dtk; p50 / p95 ms per langkah tulis):**

| Langkah | 5 VU | 10 VU | 20 VU |
|---|---|---|---|
| ajukan 2 box + PDF | 88 / 171 | 126 / 868 | 153 / 2.707 |
| verifikasi + nomor box | 59 / 145 | 67 / 411 | 94 / 3.310 |
| check-in rak | 65 / 156 | 90 / 665 | 92 / 2.376 |
| ajukan penarikan | 64 / 193 | 101 / 421 | 152 / 1.859 |
| keluarkan / tolak penarikan | 71 / 150 · 64 / 194 | 77 / 250 · 97 / 554 | 167 / 1.881 · 192 / 1.952 |
| ajukan / ACC perpanjangan | 74 / 138 · 60 / 142 | 114 / 825 · 71 / 592 | 127 / 1.067 · 76 / 675 |
| usul / setujui / eksekusi BAP | 78 / 193 · 58 / 148 · 73 / 159 | 113 / 976 · 80 / 576 · 81 / 639 | 117 / 1.032 · 118 / 1.999 · 85 / 1.089 |
| iterasi selesai (×2 box) | 33 | 33 | 40 |

- Total w2: 4.085 request, 530 baris dibuat (box + log), **sukses 100 % semua langkah, 0 HTTP 5xx, 0 HTTP 419, 0 cek gagal**. Run w1 (sama, waktu = halaman sesudah redirect) dan 2 proses k6 bersamaan (`RUN_ID=pc1` + `pc2`, 5 VU masing-masing, simulasi 2 PC) juga 100 % / 0 error.
- `dms:loadtest-verify` (semua run: smoke1, smoke2, w1, w2, pc1, pc2): 512 box uji, counter 15–526 **unik dan tanpa lompatan**, 0 baris yatim, 0 slot ganda, box `destroyed`/`taken` tidak memegang slot.
- `storage/logs` instans uji: **kosong** (0 error, 0 `database is locked` / SQLITE_BUSY). Tidak ada file `-wal` tersisa sesudah run.
- Sumber daya (Caddy + 4 php-cgi): CPU rata 86 %, puncak 200 % (2 core jenuh di 20 VU), RAM private 129–139 MB, working set ≤ 274 MB.
- Bacaan: 5 VU tulis tanpa jeda (≈ 25–50 orang yang benar-benar menulis) p95 ≤ 200 ms. 20 VU tanpa jeda menjenuhkan 2 core: p50 tetap < 200 ms, ekor p95 1–3 dtk = antre CPU (bukan kunci SQLite; tidak ada error/timeout). Tulis tidak lebih berat dari baca: CPU render halaman tetap bottleneck.

**Restore (bukti):** `dms:restore-snapshot --port=8100` saat instans masih jalan → ditolak (`port 8100 masih dipakai`, exit 1). Sesudah STOP: selesai 2 dtk, 1.278 file upload uji dipindah ke `storage/app/snapshots-sebelum-restore/<waktu>/removed-files`, 142 sesi uji dihapus, jumlah baris **semua** tabel = manifest (archives 69, archive_items 946, borrowing_logs 7, destruction_logs 3, archive_extension_logs 1, activity_logs 593), counter penomoran kembali 14, slot terisi 18, `dms:loadtest-verify` → `Arsip LOADTEST-%: 0`, 16 file upload asli tetap. SHA-256 file database dicek sama dengan snapshot sebelum dibuka pertama kali (sesudah dibuka Laravel, header berubah ke mode WAL → hash file berubah, isi sama).
