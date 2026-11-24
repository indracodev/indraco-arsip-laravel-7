# Temuan Laravel 7 (keamanan, bug, data janggal)

Sumber: audit read-only `C:\laragon\www\indraco-arsip-laravel-7` (`danu-revisi-4`) + DB nyata, 2026-11-24. Setiap temuan wajib diperbaiki di modul rebuild terkait.
Tingkat: K = kritis, T = tinggi, S = sedang, R = rendah. Kolom Status: `terbuka` | `diperbaiki (commit)` | `ditunda (lihat decisions)`.

Temuan #1 dan #2 dicek ulang oleh koordinator: 32/32 akun cocok dengan password `password`; route `files/stream` & `files/preview-stream` (routes/web.php:34-35) di luar grup auth; 21 file `*.sqlite` di `storage/app`.

## Keamanan
| # | Tingkat | Temuan | Lokasi | Perbaikan di rebuild | Modul | Status |
|---|---|---|---|---|---|---|
| 1 | K | Semua 32 akun (termasuk admin) memakai password `password`; form login sudah terisi `admin@indraco.com`/`password` | `auth/login.blade.php:323,356`; `database/seeds/DatabaseSeeder.php` | Hapus prefill; seeder memakai password acak/ENV; paksa ganti password default (ASK) | AUTH | terbuka |
| 2 | K | `/files/stream/{path}` & `/files/preview-stream` tanpa login dan juga mencari di `storage/app/` & `public/` → 21 `backup_indraco_*.sqlite`, `quick_notes.json`, semua scan bisa diunduh siapa pun di LAN | `routes/web.php:33-35`; `FileStreamController::resolveCandidates` | Wajib login; whitelist folder upload; cek dept per arsip; backup di luar web | FILE | terbuka |
| 3 | T | Fallback stream mengirim PDF arsip nyata lain bila file tidak ada | FileStreamController@stream | 404 yang jelas | FILE | terbuka |
| 4 | T | IDOR update: `PUT /archives/{id}` multi-box (jalur UI) tanpa cek dept/status → PIC bisa menimpa arsip dept lain/non-draft | Traits/HandlesMultiBoxUpdate | Policy dept + status draft (kecuali admin) | ARSIP | terbuka |
| 5 | T | IDOR baca: archives.show, destructions.bap, destructions.extend_print, api/departments/{d}/archives, api/sub-departments/{s}/archives, api/departments/{d}/manage-data (user+email) | ArchiveController@show, DestructionController@showBap, HandlesArchiveExtensions@extendPrint, DepartmentController api* | Policy per arsip/dept; api master admin-only | ARSIP/RETENSI/MASTER | terbuka |
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
| 19 | S | Middleware menulis DB di setiap request (`UpdateUserPresence`) membebani SQLite multi-user | app/Http/Middleware | Throttle/tulis ke file | SHELL | terbuka |

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
