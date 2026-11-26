# Temuan Laravel 7 (keamanan, bug, data janggal)

Sumber: audit read-only `C:\laragon\www\indraco-arsip-laravel-7` (`danu-revisi-4`) + DB nyata, 2026-11-24. Setiap temuan wajib diperbaiki di modul rebuild terkait.
Tingkat: K = kritis, T = tinggi, S = sedang, R = rendah. Kolom Status: `terbuka` | `diperbaiki (commit)` | `ditunda (lihat decisions)`.

Temuan #1 dan #2 dicek ulang oleh koordinator: 32/32 akun cocok dengan password `password`; route `files/stream` & `files/preview-stream` (routes/web.php:34-35) di luar grup auth; 21 file `*.sqlite` di `storage/app`.

## Keamanan
| # | Tingkat | Temuan | Lokasi | Perbaikan di rebuild | Modul | Status |
|---|---|---|---|---|---|---|
| 1 | K | Semua 32 akun (termasuk admin) memakai password `password`; form login sudah terisi `admin@indraco.com`/`password` | `auth/login.blade.php:323,356`; `database/seeds/DatabaseSeeder.php` | Hapus prefill; seeder memakai password acak/ENV; paksa ganti password default (ASK) | AUTH | terbuka |
| 2 | K | `/files/stream/{path}` & `/files/preview-stream` tanpa login dan juga mencari di `storage/app/` & `public/` → 21 `backup_indraco_*.sqlite`, `quick_notes.json`, semua scan bisa diunduh siapa pun di LAN | `routes/web.php:33-35`; `FileStreamController::resolveCandidates` | Wajib login; whitelist folder upload; cek dept per arsip; backup di luar web | FILE | terbuka — diperbaiki di rebuild; L7 tidak di-hotfix (risiko diterima sampai cutover) |
| 3 | T | Fallback stream mengirim PDF arsip nyata lain bila file tidak ada | FileStreamController@stream | 404 yang jelas | FILE | terbuka |
| 4 | T | IDOR update: `PUT /archives/{id}` multi-box (jalur UI) tanpa cek dept/status → PIC bisa menimpa arsip dept lain/non-draft | Traits/HandlesMultiBoxUpdate | Policy dept + status draft (kecuali admin) | ARSIP | terbuka |
| 5 | T | IDOR baca (archives.show/edit sudah ditutup ArchivePolicy di Fase 3; sisanya menyusul per modul): archives.show, destructions.bap, destructions.extend_print, api/departments/{d}/archives, api/sub-departments/{s}/archives, api/departments/{d}/manage-data (user+email) | ArchiveController@show, DestructionController@showBap, HandlesArchiveExtensions@extendPrint, DepartmentController api* | Policy per arsip/dept; api master admin-only | ARSIP/RETENSI/MASTER | terbuka |
| 6 | T | `/reports/*` tanpa cek role | ReportController | Admin-only | LAPORAN | terbuka |
| 7 | T | `backup_env.txt` di git berisi APP_KEY + APP_DEBUG=true | root | Jangan dibawa; APP_KEY baru | SISTEM | terbuka |
| 8 | T | `.env` server APP_DEBUG=true, APP_ENV=local | .env | Launcher default debug=false | SISTEM | terbuka |
| 9 | T | Git pull + restart dari web (`popen` → UPDATE-AND-RESTART.bat dengan `migrate --force`) | SettingController@gitPullAndRestart | ASK; bila dipertahankan: admin-only + konfirmasi + log | SISTEM | terbuka |
| 10 | S | Token diagnostik default = sha256(APP_KEY)[0:16], diterima lewat `?token=` | EnsureDiagnosticsAuthorized | Token dari ENV, header saja | SISTEM | terbuka |
| 11 | S | `/settings/reset-logo` tanpa cek role; font global bisa diubah semua role | SettingController | Admin-only; font per user (ASK) | SISTEM | terbuka |
| 12 | S | Upload logo menerima svg ke `public/uploads/logo` (stored XSS) | SettingController@updateSettings | Tolak svg | SISTEM | terbuka |
| 13 | S | Quick Notes publik tanpa login (baca/tulis/hapus) | QuickNotesController | ASK | SISTEM | terbuka |
| 14 | S | Rute tanpa UI tetap terbuka untuk PIC: print-labels/sticker, dept-approve, reject penarikan, borrowings.update | routes/web.php | Policy/role middleware | PENARIKAN/LABEL | terbuka |
| 15 | R | Tidak ada policy terpusat; `role:` selalu meloloskan admin | CheckRole | Gate/Policy per modul | AUTH | terbuka |
| 16 | R | Raw SQL hanya `VACUUM INTO` (path internal) dan SHOW CREATE TABLE (cabang MySQL); tidak ada injeksi dari input | SettingController | Tetap binding | SISTEM | terbuka |
| 17 | R | `{!! json_encode(...) !!}` dalam `<script>` (tidak escape `</script>`) | pic_desktop_scripts:161-163, desktop_layout_scripts:237-239 | `@js()` | SHELL | terbuka |
| 18 | R | probe-ip `exec(ping)` aman tapi bisa dipakai memindai LAN | HealthController@probeIp | Admin-only + rate limit | SISTEM | terbuka |
| 19 | S | Presence user (`UpdateUserPresence`) ditulis terlalu sering — ternyata ke file cache (bukan SQLite), 3 file tiap 15 dtk | app/Http/Middleware | Throttle 1×/60 dtk | AUTH | diperbaiki (Fase 2) |

## Bug / data janggal
| # | Temuan | Bukti | Tindakan | Modul | Status |
|---|---|---|---|---|---|
| B1 | Test suite pernah dijalankan ke DB produksi | 3 arsip BOX-TEST-*, 29 LOGIN + 20 DRAFT_UPDATE, 21 backup dengan UA Symfony | Test L13 di DB terpisah; ASK bersihkan data uji | DATA | terbuka |
| B2 | Propose pemusnahan tidak membatasi status di server | DestructionController@propose | Validasi kandidat sama dengan proposeForm | RETENSI | terbuka |
| B3 | Edit di server tidak membatasi status (UI hanya draft) | HandlesMultiBoxUpdate | Policy status | ARSIP | terbuka |
| B4 | Semua penarikan permanen (`is_permanent` hidden=1) → dispatch selalu `taken`; return tidak terpakai | borrowings/create.blade.php:95 | Pertahankan; ASK return | PENARIKAN | terbuka |
| B5 | Re-store tidak konsisten: single-box pakai ulang arsip lama, multi-box membuat arsip baru (nomor box sama di id 40 & 41) | Traits Handles*BoxCreation | Satu aturan (arsip baru + origin_borrowing_log_id) | PENARIKAN | terbuka |
| B6 | proposeForm memilih kolom `archive_code` yang tidak ada | DestructionController@proposeForm | Hapus | RETENSI | terbuka |
| B7 | Dashboard admin/gudang: pendingBookings/pendingBorrowings/deptBreakdown tidak difilter dept | DashboardController@index | Filter di rebuild | SHELL | terbuka |
| B8 | reports/print memuat `css/app.css` yang tidak ada | view | – | LAPORAN | terbuka |
| B9 | Rute ganda (api.departments.archives & api.sub_departments.archives 2×; URI POST ganda destructions/extend) | routes/web.php | Satu definisi | – | terbuka |
| B10 | system_events tidak pernah dipangkas | 221 baris | Pruning berkala | SHELL | terbuka |
| B11 | public/storage folder nyata (15 MB) vs storage/app/public (1,5 MB): file di dua tempat | filesystem | Satu lokasi; path relatif di DB tetap | FILE | terbuka |
| B12 | Aturan FAT juga berdasarkan nama user mengandung "FAT"/"FIN" | ArchiveRetentionService, HandlesArchiveExtensions | ASK: cukup kode dept | ARSIP/RETENSI | terbuka |
| B13 | TrackPageVisits merujuk model MasterLogKunjungan yang tidak ada | middleware | Buang | – | terbuka |
| B14 | quick_notes.json berisi bug UAT: "print form pengajuan simpan data tidak muncul", "penolakan pengajuan tidak ada notifikasi" | storage/app/quick_notes.json | Masukkan ke checklist uji ARSIP/SHELL | ARSIP | terbuka |

## Temuan rebuild Laravel 13 (verifikasi koordinator)
| # | Temuan | Bukti | Tindakan | Fase | Status |
|---|---|---|---|---|---|
| R1 | Launcher menyatakan siap saat ping (dijawab Caddy) hidup, padahal PHP belum membalas → user melihat halaman error beberapa detik | ping siap 18,6 dtk, halaman `/` siap 25,3 dtk (laptop 16 core); Chrome via IP LAN menampilkan error page | Cek kesiapan lewat request PHP + pemanasan semua worker sebelum "SIAP"/buka browser | 1 | diperbaiki (a4f3abf): SIAP 1,5 dtk, 6/6 worker hangat |
| R2 | Log pool php-cgi berisi "The filename, directory name, or volume label syntax is incorrect." | `storage/logs/pool/php-cgi-900N.log` | Perbaiki path/quoting di `evironment/dms-server.cmd` | 1 | diperbaiki (a4f3abf): sisa log dari helper uji lama; log dikosongkan tiap start |
| R3 | Start butuh 18,6 dtk di laptop cepat; di server 1–2 core bisa > 1 menit | pengukuran STOP→START | Profil tiap langkah START, lewati pekerjaan yang tidak perlu setiap start | 1 | diperbaiki (a4f3abf): `optimize` hanya jika berubah, START→SIAP 1,6–3 dtk (estimasi 6–10 dtk di A9); log per langkah `storage/logs/startup.log` |
| R4 | S | `users.email` dan `app_settings.key` tidak punya UNIQUE index di DB L7 (hilang saat L7 membangun ulang tabel) | sqlite_master | Validasi unik di modul USER; migrasi UNIQUE setelah cek duplikat (keputusan pemilik) | 2 | terbuka |
| R5 | T | Config cache produksi membuat `php artisan test` mengabaikan DB `:memory:` → RefreshDatabase bisa menghapus DB asli | phpunit.xml | Path cache tes diarahkan ke file yang tidak ada | 2 | diperbaiki (Fase 2) |
| R6 | R | Middleware guest L7 mengarahkan user yang sudah login ke `/home` (404) | L7 RedirectIfAuthenticated | Arahkan ke `/` | 2 | diperbaiki (Fase 2) |
| R7 | R | 2 baris MasterArchiveSeeder memakai kode departemen `FACTORY` (seharusnya `FCT`) sehingga tidak pernah ter-seed | L7 seeder | Dibiarkan seperti L7 | 2 | dicatat |
| R8 | S | Layout gudang produksi berbeda dari seeder L7 (kode `R1-B`, koordinat, ruang) | DB vs WarehouseLayoutSeeder | ASK pemilik: instalasi baru pakai layout produksi? | 2 | terbuka |
| R9 | R | DB produksi berbeda dari seeder (dept FAT "FIN, ACC & TAX", DM, TEST_DEPT, user pic_test_scope_check) | DB | Bersihkan saat cutover (sama kelompok B1/ASK 11) | 7 | terbuka |
| R10 | R | Saat uji browser, klik tombol submit oleh alat otomasi Chrome kadang tidak mengirim form (dialog simpan password Chrome); `button.click()` di halaman bekerja normal | uji koordinator | Bukan bug aplikasi; ulangi uji klik manual oleh pemilik | 2 | dicatat |
| R11 | R | Slot produksi pada objek ruang "GUDANG R1" (kode `GUDANG R11`, arsip 96) — artefak menaruh box langsung ke ruang; tidak di-seed | DB produksi | Cek di modul GUDANG | 5 | terbuka |
| R12 | R | Route L7 `/manifest.json` menunjuk method yang tidak ada (rusak) | L7 routes | Diganti manifest statis | 3 | diperbaiki (Fase 3) |
| R13 | R | `admin-sidebar-content.blade.php` L7 adalah kode mati dari project lain | L7 views | Tidak dibawa | 3 | dicatat |
| R14 | T | Di Windows `view:cache` mengompilasi view dengan path berbeda dari request (`\` vs `/`) → setiap view dikompilasi ulang saat pertama dibuka (0,8–4,3 dtk), dan kompilasi paralel gagal `rename(): Access is denied` → **HTTP 500** (30 kejadian di log 24 Nov, termasuk saat uji browser) | log laravel, uji koordinator | `views:warm` + pemanasan `/login` | 3 | diperbaiki (revisi Fase 3): login pertama 2.564 → 226 ms, 0 error sejak 15:01 |
| R15 | S | Tablet 768–1023 px: tabel katalog bisa digeser tapi tanpa tanda, kolom Masa Simpan/Status/Berkas dan **tombol aksi tersembunyi** sampai digeser | uji browser koordinator (iframe 768 px) | Mode kartu sampai < 1024 px, atau kolom aksi sticky kanan + tanda geser | 3 | diperbaiki (revisi Fase 3): kartu < 1024 px, aksi terlihat di 768/1000 px |
| R16 | R | Kartu HP 360 px: kolom judul sempit karena tag (FAT/ACC/tipe) di samping judul | uji browser koordinator | Tag di bawah judul pada mode kartu | 3 | diperbaiki (revisi Fase 3) |
| R17 | R | Resize jendela Chrome lewat alat otomasi tidak mengubah viewport saat jendela maximized; uji responsif memakai iframe 360/768 px | uji koordinator | Bukan bug aplikasi | 3 | dicatat |
| R18 | S | STOP lalu START terlalu cepat bisa gagal ("GAGAL: PHP tidak merespons"): pembersihan jendela launcher lama mematikan server baru | laporan subagent | Launcher: tunggu proses lama benar-benar keluar / kunci PID sebelum start | OPS | terbuka |
| R19 | R | Mode kartu: badge (BARU, status, berkas) melebar memenuhi lebar sel; kolom judul di tablet berawal jauh dari labelnya | uji browser koordinator 1000 px | Badge selebar isi (`justify-self:start`/`inline-flex`), rapikan grid sel judul | 3 | diperbaiki (menu atas + R19) |
| R20 | R | Ambient tidak terlihat di area konten (tertutup menu, terlalu tipis 9%/4,5%, glow kanan bawah di luar layar pada halaman pendek) | masukan pemilik | Ambient di `.main` ≥ tinggi layar, slate 14% + oranye 10% | 3 | diperbaiki |
| R21 | S | Windows Defender menandai file sementara tes upload (`%TEMP%\dmsDA17.tmp`, ditulis php.exe portable saat tes) sebagai **Trojan:PHP/Chopper.A!dha** dan mengkarantinanya; fixture tes juga meninggalkan 175 file `dms*.tmp` | Defender 24 Nov 16:24; `tests/Feature/Archive/ArchiveFixtures.php` tempnam tanpa unlink | False positive dari payload uji penolakan upload .php. Payload uji wajib tidak menyerupai webshell (tanpa eval/$_POST/base64 dsb), file uji dibersihkan (UploadedFile::fake atau unlink di tearDown); jangan menambah pengecualian Defender | 4 | terbuka (subagent ARSIP memperbaiki; 171 file sisa dihapus koordinator) |
| R22 | T | **Uji browser**: modal peringatan (`window.showAlertModal`) dipanggil tapi tidak tampil → klik "Simpan Sementara (Draft)" tampak tidak bereaksi (tanpa POST, tanpa pesan). Lolos uji curl karena JS tidak dijalankan | Chrome, /archives/create, fin | Modal sebenarnya tampil (role=alertdialog); bug nyata: fokus tertinggal di belakang modal → diperbaiki fokus + trap | 4 | diperbaiki (uji browser) |
| R23 | S | Draft ditolak di klien bila baris rincian default masih kosong ("Nama Dokumen ... masih kosong") — perlu disamakan dengan aturan draft L7 | Chrome, Alpine `problem(false)` | L7 juga menolak (validateAllFormRequirements(false)) → dipertahankan demi kesamaan; server tetap nullable untuk draft | 4 | dicatat — sesuai L7 |
| R24 | R | Baris rincian kosong diberi label "DUPLIKAT Kembar identik dgn null" | accessibility tree | x-show → x-if | 4 | diperbaiki |
| R25 | S | Uji modul sebelumnya hanya HTTP/curl; bug JS (R22) lolos. Aturan: modul dengan interaksi JS wajib diuji di browser oleh koordinator sebelum dianggap selesai | proses | workflow verify langkah 5 ditegaskan | proses | dicatat |
| R26 | T | Tema default masih "ikut sistem": `prefs.setTheme(stored.theme)` dengan nilai kosong → system; di Windows mode gelap aplikasi tampil gelap | uji browser koordinator (localStorage kosong, OS gelap → data-theme=dark) | `stored.theme || 'light'` + tes | 4 | diperbaiki (uji browser: OS gelap → tema terang) |
| R27 | T | **IDM (Internet Download Manager)** mencegat respons PDF di Chrome — termasuk pratinjau fetch+blob di modal — dan halaman menerima **204 kosong**; gudang tidak bisa melihat scan persetujuan. L7 sengaja mengirim pratinjau sebagai base64 JSON untuk menghindari IDM | uji browser koordinator: server log 200 193 B, browser 204; IDMan.exe berjalan | Pratinjau kembali ke base64 JSON (seperti L7) untuk file ≤ batas; unduhan tetap attachment biasa | 4 | diperbaiki: `files.preview_data` (base64 JSON, maks 5 MB, otorisasi sama); uji browser dengan IDM aktif: PDF tampil di modal |

## Putaran C–I (2026-11-24) — ringkasan status
Ditutup: #5 (semua: archives.show, bap, extend_print, api/departments/*, api/sub-departments/*), #6, #10, #11, #12, #14, #18, B2, B6, B7, B8, B9, B10, B12 (retensi), R18 (STOP aman, `dms-stop.ps1`). #9 dipertahankan: admin-only + konfirmasi + log + backup sebelum update.

| # | Tingkat | Temuan | Status |
|---|---|---|---|
| R28 | T | Hapus user di L7 ikut menghapus arsip & log (ON DELETE CASCADE) | diperbaiki (E): hapus ditolak bila user punya data |
| R29 | S | Edit departemen L7 mereset retensi ke 5 | diperbaiki (E) |
| R30 | S | Hapus gudang menghapus rak & slot berisi box | diperbaiki (E) |
| R31 | R | Assign PIC bisa menurunkan admin; impersonate admin lain | diperbaiki (E) |
| R32 | R | `ArchiveFixtures` jalan dua kali (setUp<Trait> otomatis + manual) | diperbaiki (P): guard idempoten di `ArchiveFixtures` & `BorrowingFixtures` |
| R33 | R | Pesan validasi bawaan berbahasa Inggris (L7 juga `en`) | diperbaiki (P): `lang/id/validation.php` |
| R34 | S | Verifikasi/check-in L7 tidak cek status asal → klik ganda memproses dua kali | diperbaiki (D) |
| R35 | S | ACC perpanjangan/pemusnahan L7 menerima status apa pun → perpanjang dua kali | diperbaiki (C) |
| R36 | R | Perpanjangan L7 `addMonths` overflow (31 Okt + 1 bln → 31 Des) | diperbaiki (C) |
| R37 | T | Impor multi-box L7 mempercayai JSON pratinjau dari browser (termasuk departemen) | diperbaiki (H): server parse ulang file |
| R38 | R | Log History L7: filter modul tak pernah cocok; pencarian pemusnahan `orWhere` mengabaikan filter; laporan "Box Expired" selalu 0 | diperbaiki (G) |
| R39 | T | Ping Caddy tanpa `Access-Control-Allow-Origin` → exe klien lama melihat server offline | diperbaiki (I), diuji: header ada setelah restart |
| R40 | R | Klien Tauri memasukkan data rilis GitHub via innerHTML | diperbaiki di sumber (I), perlu build ulang exe |
| R41 | R | `x-modal class=` menghasilkan dua atribut class (lebar modal tidak berlaku) | diperbaiki (P): `$attributes->class(['modal'])` + `.modal--wide/.modal--lg` |
| R42 | R | Rak baru dari Master Gudang tidak punya slot (L7 via model booted) | diperbaiki (P): `WarehouseLayout::ensureSlots($location)` di `WarehouseLocationController@store` |
| R43 | R | Katalog yang terbuka tidak memuat ulang saat notifikasi masuk | diperbaiki (P): `[data-realtime-reload]` di katalog mendengarkan `dms:realtime-sync` |
| R44 | R | Tombol "Proses Pemusnahan" tampil untuk gudang tapi 403 (sama L7) | diperbaiki (P): tampil hanya untuk admin (`$user->isAdmin()`) |

## E2E skenario PIC+Gudang (2026-11-25)

| # | R | Temuan | Status |
|---|---|---|---|
| R45 | R | Prefill "Ajukan Penyimpanan Kembali" mengosongkan periode berkas (regresi vs L7) | diperbaiki: `ArchiveFormData::reStoreBoxes` ambil `Y-m` dari `period` snapshot (rentang "A s/d B" → awal/akhir), fallback periode box lalu bulan ini (L7) |
| R46 | R | Pesan validasi menampilkan kunci mentah (`boxes.0.title`, `purpose`, `today`, ...) | diperbaiki: `attributes` (termasuk wildcard `boxes.*...`) + `values` `today` → "hari ini" di `lang/id/validation.php`; `reason` penarikan = "Alasan Penolakan" di controller |
| R47 | S | `POST /borrowings/{id}/dispatch` lolos tanpa centang `confirm_file_checked` (hanya `required` HTML, sama L7) | diperbaiki: validasi server `accepted` + pesan Indonesia di `BorrowingApprovalController@dispatch` |
| R48 | S | Penyelesaian pemusnahan menerima tanggal masa depan (sama L7) | diperbaiki: `before_or_equal:today` di `DestructionApprovalController@complete`; pengajuan tetap boleh dijadwalkan ke depan |
| R49 | R | `method` pemusnahan menerima teks bebas (UI select, server `string\|max:100`, output di-escape) | dicatat, sama L7 |
| R50 | S | Halaman HTML login-only dikirim `Cache-Control: no-cache, private`: browser tetap menyimpan (bfcache), setelah logout di PC bersama tombol Back menampilkan data arsip; menu basi setelah deploy | diperbaiki: `SetCacheHeaders::using('no_store;private')` di grup `web` (`bootstrap/app.php`) → semua respons 2xx web `no-store, private` (termasuk login, unduhan file); `sw.js` hanya cache file statis `?v=` + `offline.html`, tidak pernah HTML; tes `Shell/NoStoreCacheTest` |
| Q1 | - | Nomor BAP yang sama diterima untuk log/departemen berbeda (mungkin sengaja: satu BAP untuk beberapa box) | diputuskan pemilik 2026-11-25: tetap seperti L7 + penyesuaian (satu No. BAP boleh untuk banyak box **satu departemen**; dipakai departemen lain → ditolak saat eksekusi BAP gudang/admin dan sahkan langsung admin, trim + tanpa beda huruf, placeholder diabaikan; pakai ulang di departemen sama → catatan di flash; `CompleteDestruction::checkBapNumber`, tes `DestructionProcessTest`) |
| Q2 | - | Pemusnahan bisa diajukan untuk box `in_warehouse` mana pun sebelum masa retensi habis (sama L7) | diputuskan pemilik 2026-11-25: tetap seperti L7 + penyesuaian (tetap boleh, badge "Belum jatuh tempo (sisa X hari)" di form ajukan/sahkan (hasil cari + daftar terpilih) dan daftar gudang baris pending/approved + teks konfirmasi Setujui; `RetentionCalculator::earlyNote`, tes `DestructionProposalTest`) |
| U1 | R | Sesi diakhiri di tab lain → submit berikutnya mendarat di login tanpa pesan "sesi berakhir" | diperbaiki 2026-11-25: render `AuthenticationException` (bootstrap/app.php) → login + toast "Sesi Anda telah berakhir. Silakan login kembali." bila request membawa cookie sesi atau Referer dari halaman aplikasi (kunjungan pertama tanpa pesan); 419 `TokenMismatch` tamu → login + pesan + email dipertahankan, 419 saat masih login (token diganti login/logout tab lain) → kembali + input + "Halaman sudah kedaluwarsa"; JSON tetap 401/419. Sisa: poll notifikasi (`notifications.js`) menelan 401 dan terus polling diam-diam (perlu: berhenti + beri tahu/muat ulang). Tes `Auth/SessionSecurityTest` |
| U2 | R | Approve + reject bersamaan: satu respons tanpa flash message (data tetap benar) | diselidiki 2026-11-25, tidak diubah: jalur "sudah diproses" sudah selalu flash error (`DomainException` → `back()->with('error')`). Hilangnya pesan = race sesi file Laravel (tanpa lock): hanya terjadi bila **satu akun di dua tab pada saat yang sama**; dua pengguna berbeda punya sesi sendiri dan masing-masing mendapat pesan. `Route::block()` pun tidak menuntaskan (flash milik sesi, GET redirect tab mana pun yang lebih dulu mengambil keduanya) |


## Audit keamanan (2026-11-25)

Checklist lengkap + bukti: `security-checklist.md`. Critical/high = 0.

| # | Tingkat | Temuan | Bukti | Status |
|---|---|---|---|---|
| S1 | R | PIC Departemen tanpa departemen (setelah "Lepas PIC" / hapus departemen) melihat event realtime tanpa departemen: `where('department_id', null)` menjadi `whereNull` (komentar `SystemEventStream` justru bermaksud mengecualikannya) | tes gagal sebelum perbaikan | diperbaiki: `?? 0` di `Archive::visibleTo` + `SystemEventStream::visibleTo`; tes `Auth/SecurityAuditTest` (+ tes semua route wajib login kecuali login/health) |
| S2 | R | Ganti password sendiri / reset oleh admin tidak mengakhiri sesi lain akun itu (tanpa `AuthenticateSession`), mis. sesi orang yang sempat menebak password default tetap hidup ≤ 120 menit sejak request terakhir | tidak ada `auth.session`/`logoutOtherDevices` | diperbaiki (ACC pemilik 2026-11-25): `AuthenticateSession` di grup web (bootstrap/app.php) — sesi lain akun itu ter-logout pada request berikutnya setelah ganti password sendiri / reset admin; sesi yang mengganti menyimpan ulang hash sesudah request sehingga tetap masuk. Efek sekali: login pertama akun lama (hash L7 cost 10 di-rehash ke 12) juga mengeluarkan sesi lain akun bersama itu. Tes `Auth/SessionSecurityTest` |
| S3 | R | Header `Host` tidak divalidasi; URL/redirect dibangun dari Host request | `curl -H "Host: evil.example:8000" /` → `302 http://evil.example:8000/login` | diperbaiki (ACC pemilik 2026-11-25): `trustHosts` (bootstrap/app.php) dari `config('app.allowed_hosts')` = localhost, 127.0.0.1, [::1], nama PC, `SERVER_LAN_IP`, `DMS_ALLOWED_HOSTS` (.env, koma); Host lain → 400 dengan halaman statis bahasa Indonesia `errors/400.blade.php` (tanpa helper URL yang bergantung Host). `/api/health/ping` dijawab Caddy (tidak terpengaruh). Ikut `runbook-ganti-ip.md`. Tes `Auth/SessionSecurityTest` |
| S4 | R | CSP hanya Report-Only tanpa endpoint laporan → tidak menghambat XSS (lapisan utama tetap escape Blade) | header `Content-Security-Policy-Report-Only` | ditunda (decisions Fase 3): dipaksa setelah ganti ke build `@alpinejs/csp`; cek dulu `style-src 'unsafe-inline'` dan `blob:` pratinjau |
| S5 | R | `post_max_size`/`upload_max_filesize` 64M tanpa `request_body max_size` di Caddy: klien LAN tanpa login bisa mengirim body 64 MB ke worker php-cgi (DoS ringan) | `evironment/php-dms.ini`, `Caddyfile` | diperbaiki (ACC pemilik 2026-11-25): body terbesar sah = impor Excel 5 MB + scan 500 KB → `post_max_size`/`upload_max_filesize` 20M (php-dms.ini, halaman/JSON 413 bahasa Indonesia dari Laravel) dan Caddy `request_body max_size 24MB` + `handle_errors 413` halaman Indonesia (diuji dengan Caddy+php-cgi terpisah: 22 MB → 413 Laravel, 30 MB → 413 Caddy). Tes `Auth/SessionSecurityTest` |

## Performance (2026-11-25)

Ukur + angka: `performance-baseline.md` bagian "Fase 6 / rute nyata". Tidak ada N+1 (semua halaman 1–15 query); kode aplikasi tidak diubah.

| # | Tingkat | Temuan | Bukti | Status |
|---|---|---|---|---|
| P1 | S | `/destructions` (semua tab) memanggil `RetentionList::expiring()` + `extensions()` + `destructions()` sekaligus: `expiring()` memuat **semua** box `in_warehouse` kandidat (+ relasi) lalu menyaring jatuh tempo di PHP; ketiganya `->get()` tanpa batas (melanggar PROJECT_RULES_SQLITE §3.3) | salinan data ×150 (10.200 arsip): tab H-30 721 ms + HTML 1,39 MB, tab lain 264–287 ms walau HTML 20–35 KB; data asli 21–35 ms | **diperbaiki** (ACC pemilik 2026-11-25): hanya tab aktif, badge = COUNT SQL, filter H-30 di SQL (`RetentionList::due`, tes paritas `RetentionDueListTest`), 50/halaman, cari server `?q=`. Data ×150 admin tab H-30 653 → 71 ms, HTML 1,39 MB → 111 KB; tab lain 205–210 → 25–34 ms (`performance-baseline.md` "P1/P3") |
| P2 | R | Index yang tidak optimal saat data tumbuh: `archive_extension_logs` NOT EXISTS memakai `idx_ext_logs_status` (tanpa ANALYZE) → per box memindai semua log pending; dashboard `latest()` dan count `strftime(retention_expiry_date)` SCAN `archives` | EXPLAIN pada data ×150: SCAN + temp B-tree 2–7 ms, count 8,8–12,5 ms | dicatat: tambah `archive_extension_logs(archive_id, approval_status)` dan `archives(created_at, id)` bila arsip > ±20 rb; sekarang < 10 ms, tidak diubah |
| P3 | R | Poll notifikasi tiap 5 dtk per tab memboot Laravel penuh (20–40 ms CPU untuk JSON ±130 B) = beban dominan saat ramai; mulai serentak → gerombolan request | mode human 50 orang 2 E-core: poll p95 409 ms vs halaman ≤ 107 ms; 1 core 30 orang: poll p95 8 dtk | **diperbaiki** (ACC pemilik 2026-11-25): 15 dtk ± 2 dtk, poll pertama acak 0–15 dtk, tetap berhenti saat tab tersembunyi + langsung poll saat terlihat lagi. Mode human 50 orang: poll 9,8 → 2,8 req/s, request PHP total 13,1 → 5,3 req/s (`seq` statis via Caddy belum, bila masih perlu) |
| P4 | R | Live :8000 berjalan tanpa `bootstrap/cache/config.php` + `routes-v7.php` (launcher hanya `optimize` saat START) | tanpa cache: poll 42 vs 21 ms, jenuh 32 vs 45 req/s (2 E-core) | dicatat (operasional): `php artisan optimize` / START ulang setelah tes atau `optimize:clear` di PC server |

## Uji beban tulis (2026-11-26)

Angka + restore: `performance-baseline.md` "Uji beban tulis (2026-11-26)". 0 bug aplikasi ditemukan (100 % langkah sukses, 0 5xx/419, nomor box unik tanpa lompatan, 0 locked).

| # | Tingkat | Temuan | Bukti | Status |
|---|---|---|---|---|
| W1 | R | Data salinan laptop: arsip 96 (LOGISTIC, `in_warehouse`) memegang **2 slot** rak: 8101 (`A1`, rak R1-A) dan 16001 (`GUDANG R11`, lokasi 91 bertipe `room`); `archives.warehouse_rack_slot_id` = 8101. Diperbarui 2026-10-21 = data era L7, bukan dari uji | query `warehouse_rack_slots GROUP BY archive_id HAVING COUNT(*) > 1` pada snapshot sebelum uji | dicatat: cek di DB L7 terbaru sebelum cutover; perbaikan data (kosongkan slot 16001) hanya dengan keputusan pemilik. `dms:loadtest-verify` hanya menilai slot milik arsip LOADTEST |
| W2 | R | Waktu per langkah k6 bisa menyesatkan bila POST mengikuti redirect: `res.timings` = halaman sesudah redirect, bukan penulisan | run w1 vs w2 | ditangani di skrip: POST `redirects: 0`, halaman tujuan diambil terpisah (`<langkah>_page`) |
| W3 | R | Ekor latensi di 20 VU tulis tanpa jeda (2 core): p95 1–3 dtk, maks 6 dtk | CPU pool 200 % | dicatat: antre CPU, sama sifatnya dengan baca (P4/Fase 6); tidak ada error. Ulangi di server asli (runbook) |


## Uji di PC server asli (2026-11-26)

| # | Tingkat | Temuan | Bukti | Status |
|---|---|---|---|---|
| S1 | S | Folder L13 disalin ke PC lain: `bootstrap/cache/config.php` ikut terbawa dengan path absolut lama (`C:\laragon\...\database.sqlite`); salin folder mempertahankan timestamp → launcher menganggap cache segar, migrate gagal | layar START di server | **diperbaiki** `ca056ef`: launcher juga membuat ulang cache bila config cache tidak berisi folder app sekarang |
| S2 | S | Katalog (`[data-realtime-reload]`) memuat ulang **langsung** pada tiap perubahan realtime; saat ramai (uji k6) halaman refresh sendiri ±15 dtk sekali di tengah membaca/mengetik/scroll | pemilik membuka web saat k6 jalan | **diperbaiki**: reload otomatis hanya bila pengguna diam ≥ 60 dtk (tanpa ketik/klik/scroll/wheel, tanpa field fokus, modal, centang, teks terpilih); selain itu toast tetap "Ada data baru di halaman ini · Muat ulang" (R43 tetap terpenuhi untuk layar yang dibiarkan terbuka) |
| S3 | S | Pindah halaman saat server lambat tanpa tanda apa pun (halaman diam / putih lalu muncul) → pengguna klik sembarangan | p95 1–2 dtk di 30 VU baca | **diperbaiki**: `html.is-navigating` (app.js, `beforeunload`) → garis progres atas + kursor progres + lapisan yang menahan klik (meredup sesudah 0,4 dtk); unduhan (`/download`, `/template`, `/export-csv/`, `backup-database`, `[download]`) dikecualikan; hapus sendiri sesudah 8 dtk / `pageshow`. Tombol submit sudah punya spinner + kunci ganda (lama) |
| S4 | S | Tombol aksi berteks memakai `variant="ghost"` (tanpa garis/latar) sehingga terlihat seperti teks biasa: Salin/Hapus baris di form pengajuan, dan 15 tombol serupa (Kosongkan Form, Hapus Box, Hapus draft katalog, Pilih dari master, ganti/hapus file, Tutup notifikasi impor, buka-tutup & Hapus box di form penarikan/perpanjangan/pemusnahan, Tolak Pengajuan, pratinjau berkas tab H-30, + Tambah Sekarang sub-dept, Atur Dept & Reset dept denah gudang) | screenshot pemilik form pengajuan | **diperbaiki**: semua jadi tombol bergaris; aksi hapus = garis + teks merah (`.btn.tone--danger`). Aturan di skill `blade-ui-components`: `ghost` hanya untuk tombol ikon dan judul kolom urut |
