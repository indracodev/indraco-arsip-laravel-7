# Triage artefak Laravel 7 → Laravel 13 (semua role)

Sumber: `C:\laragon\www\indraco-arsip-laravel-7` (`danu-revisi-4`). DB nyata dibuka read-only 2026-11-24. Detail role PIC Dept: `../INVENTARIS-PIC-DEPT.md`. Temuan: `findings.md`.

Label:
- **KEEP**: dipakai, dibawa dengan sedikit perubahan.
- **REWRITE**: perilakunya dibutuhkan, implementasinya dibuat baru.
- **DROP**: terbukti tidak dipakai atau sudah digantikan.
- **ASK**: bukti kurang, perlu keputusan pemilik.

Catatan umum:
- Semua view/JS di-**REWRITE**, karena UI dibangun ulang sementara UX, alur, dan form tetap sama. Label KEEP pada view berarti "alur dan komposisi form-nya dibawa apa adanya".
- "Dipakai UI" = ada pemanggil `route()`/URL di Blade/JS. Angka log = `activity_logs` 2026-10-05 s/d 2026-11-17, setelah log dikosongkan admin 2026-10-05.

Modul usulan: **AUTH**, **SHELL** (layout, header, notifikasi, pencarian, pengaturan), **ARSIP** (katalog, pengajuan, detail, impor), **VERIFIKASI** (gudang: verify, checkin, slot, checkout, status), **PENARIKAN**, **RETENSI** (perpanjangan, pemusnahan, BAP), **LABEL**, **MASTER** (dept/sub-dept/master berkas/impor master/PIC), **GUDANG** (gudang/rak/layout 2D), **USER**, **PENOMORAN**, **LOG**, **LAPORAN**, **SISTEM** (setting, backup, health, diagnostik, quick notes, update), **FILE**, **OPS** (launcher, klien desktop), **DATA** (tabel/migrasi).

## 1. Route & controller

| jenis | item | label | bukti | modul | risiko bila salah |
|---|---|---|---|---|---|
| route | `POST /login`, `GET /login`, `POST /logout` (AuthController) | REWRITE | LOGIN 153 (PIC 54); hapus prefill kredensial | AUTH | tidak bisa login |
| route | `POST /profile/change-password` (UserController@changePassword) | KEEP | dipakai UI (modal); 0 log PASSWORD_CHANGE | AUTH | user tidak bisa ganti password |
| route | `POST /master/users/{u}/impersonate`, `POST /impersonate/leave` | ASK | UI ada di master/users; 0 log IMPERSONATE | USER | fitur dukungan admin hilang |
| route | `GET /`, `/dashboard` (DashboardController@index) | REWRITE | shell semua role; isi dashboard hanya admin/gudang | SHELL | halaman awal rusak |
| route | `GET /api/search-archives` | KEEP | header search semua role | SHELL | pencarian hilang |
| route | `GET /api/realtime/check` (HandlesDashboardRealtime) | REWRITE | polling notifikasi/badge; system_events 221; ada di test k6 | SHELL | notifikasi/badge mati, beban LAN |
| route | `GET /api/archives/expiring-list` | KEEP | popup jatuh tempo | SHELL | peringatan retensi hilang |
| route | `POST /api/archives/check-similarity` | KEEP | form create/edit | ARSIP | duplikasi judul box |
| route | `GET /api/archives/calculate-retention` | DROP | 0 pemanggil di Blade/JS/klien; logika ada di service | ARSIP | – |
| route | `GET /api/departments/{d}/sub-departments` | DROP | 0 pemanggil (create memuat sub-dept dari server) | ARSIP | – |
| route | `GET /api/departments/{d}/archives`, `/api/sub-departments/{s}/archives` (didefinisikan 2×) | REWRITE | dipakai drill-down master dept; tanpa cek role (IDOR) | MASTER | bocor data antar dept |
| route | `GET /api/departments/{d}/manage-data` | REWRITE | dipakai master dept; tanpa cek role | MASTER | bocor daftar user |
| route | `GET /api/departments/{d}/master-archives` | KEEP | dropdown Master Berkas di create/edit | ARSIP | dokumen tidak bisa dipilih |
| route | `GET /archives` (index) | KEEP | katalog semua role | ARSIP | inti aplikasi |
| route | `GET/POST /archives/create`, `/archives` (store → storeMultiBox) | REWRITE | CREATE 35, SUBMIT 40, DRAFT_SAVE 11 | ARSIP | pengajuan gagal / data salah |
| route | `GET /archives/{a}/edit`, `PUT /archives/{a}` (updateMultiBox) | REWRITE | DRAFT_UPDATE 28; multi-box tanpa cek dept/status | ARSIP | IDOR, menimpa arsip lain |
| route | jalur single-box (`storeSingleBox`, `updateSingleBox`) | DROP | UI selalu kirim `boxes[]`; hanya dipakai test | ARSIP | – (pastikan klien lain tidak kirim tanpa boxes) |
| route | `DELETE /archives/{a}`, `POST /archives/batch-delete-drafts` | KEEP | dipakai UI; 0 log hapus | ARSIP | draft tidak bisa dibersihkan |
| route | `GET /archives/{a}` (show) | REWRITE | dipakai; tanpa cek dept | ARSIP | IDOR |
| route | `GET /archives/import/template`, `POST /archives/import/{preview,commit,load-to-form}` | ASK | UI ada; 4 impor (admin/anonim, uji 2026-10-21); PIC 0 | ARSIP | fitur impor massal hilang |
| route | `POST /archives/{a}/verify` | KEEP | ARCHIVE_VERIFY 9 | VERIFIKASI | nomor box tidak terbit |
| route | `POST /archives/{a}/checkin` | KEEP | ARCHIVE_CHECKIN 1 + modal | VERIFIKASI | box tidak masuk rak |
| route | `POST /archives/{a}/checkout` | ASK | modal checkout (catalog/dashboard); 0 log khusus; tumpang tindih dengan borrowings.dispatch | VERIFIKASI | dua jalur keluar box |
| route | `POST /archives/{a}/superadmin-status` | KEEP | SUPERADMIN_STATUS_WAREHOUSE 1 | VERIFIKASI | koreksi status manual hilang |
| route | `GET/POST /archives/print-labels`, `GET /archives/{a}/print-sticker` | KEEP | ARCHIVE_PRINT 16 (gudang 12, admin 4) | LABEL | label box tidak tercetak |
| route | `GET /borrowings`, `/borrowings/create`, `POST /borrowings` | KEEP | BORROW_CREATE 2, borrowing_logs 3 | PENARIKAN | alur penarikan putus |
| route | `POST /borrowings/{b}/dispatch` | KEEP | BORROW_DISPATCH 1 | PENARIKAN | box tidak bisa dikeluarkan |
| route | `POST /borrowings/{b}/reject` | KEEP | BORROW_REJECT 1 | PENARIKAN | – |
| route | `POST /borrowings/{b}/dept-approve` | ASK | tombol hanya admin; 0 log; status `dept_approved` belum pernah terjadi | PENARIKAN | tahap persetujuan dept hilang |
| route | `POST /borrowings/{b}/approve` | DROP | 0 pemanggil UI; dispatch langsung dari requested | PENARIKAN | – |
| route | `POST /borrowings/{b}/return` | ASK | 0 pemanggil UI; semua penarikan permanen (`is_permanent=1`) | PENARIKAN | jika pinjam sementara dibutuhkan, tidak ada cara mengembalikan |
| route | `PUT /borrowings/{b}` (update) | DROP | 0 pemanggil UI | PENARIKAN | – |
| route | re-store: `GET /archives/create?re_store=1&borrowing_ids[]` | KEEP | 1 arsip `origin_borrowing_log_id` | PENARIKAN | box tidak bisa disimpan kembali |
| route | `GET /destructions`, `/destructions/propose[/{a}]`, `POST /destructions/propose[/{a}]` (nama ganda `.archive`) | REWRITE | DESTRUCTION_PROPOSE 2; rapikan nama rute ganda | RETENSI | usulan pemusnahan putus |
| route | `POST /destructions/{l}/approve`, `/reject`, `/complete`, `/upload-bap` | KEEP | UI ada (destruction_modals); 0 log (2 log masih pending) | RETENSI | BAP tidak bisa diselesaikan |
| route | `GET /destructions/bap/{l}` | REWRITE | dipakai; tanpa cek dept | RETENSI | IDOR |
| route | `GET/POST /destructions/extend[/{a}]`, `GET /destructions/extend-print/{a}`, `POST /destructions/extensions/{l}/{approve,reject}` | KEEP | UI ada; RETENTION_EXTEND 1 (kode lama), extension_logs 0 | RETENSI | perpanjangan masa simpan hilang |
| route | `GET /logs` (role:admin) | KEEP | menu admin "Log History" | LOG | audit tidak terbaca |
| route | `GET /reports`, `/reports/{data,print,export-csv}/{type}` | REWRITE | menu admin "Report"; 0 log; tanpa cek role | LAPORAN | bocor data ke PIC |
| route | `GET /master/warehouses/layout`, `GET /api/warehouse/layout-data`, `POST /api/warehouse/locations/*` (book/unbook/update/delete/store, slots assign/unassign/toggle) | KEEP | LAYOUT_OBJECT_UPDATE 6, SLOT_ASSIGN 11 | GUDANG | penempatan rak putus |
| route | `/master/warehouses` CRUD + `/master/warehouses/locations` CRUD (WarehouseController) | REWRITE | dipakai UI; tumpang tindih dengan layout API | GUDANG | dua sumber kebenaran lokasi |
| route | `/master/departments` CRUD, toggle, PIC assign/remove, sub-departments CRUD | KEEP | MASTER_DEPT_UPDATE 2 | MASTER | master tidak bisa dikelola |
| route | `/master/departments/{d}/master-archives` CRUD + batch | KEEP | MASTER_ARCHIVE_BATCH_CREATE 3 | MASTER | dropdown dokumen kosong |
| route | `/master/departments/import/{template,preview,commit}` | KEEP | commit terbaru `8fd0fa1` (fitur baru, belum ada log) | MASTER | impor master hilang |
| route | `/master/numbering` (index/store/update) | KEEP | format aktif dipakai verify | PENOMORAN | nomor box salah |
| route | `/master/users` CRUD + reset-password | KEEP | USER_UPDATE 1 | USER | user tidak bisa dikelola |
| route | `POST /settings/update`, `POST /settings/reset-logo` | REWRITE | SYSTEM_SETTINGS_UPDATE 2; font global bisa diubah semua role; reset-logo tanpa cek | SISTEM | user biasa mengubah tampilan global |
| route | `GET /api/settings/current` | DROP | 0 pemanggil | SISTEM | – |
| route | `/master/settings/backup-database` (GET/POST) | KEEP | SYSTEM_DATABASE_BACKUP 22 (21 dari test) | SISTEM | tidak ada backup |
| route | `/master/settings/clear-logs`, `clear-box-allocations`, `clear-archives` | ASK | masing-masing 1× pada 2026-10-05 (reset awal) | SISTEM | hapus data massal tidak sengaja |
| route | `/master/settings/git-pull-restart`, `git-pull-log` | ASK | UI ada di modal settings; menjalankan `popen` + UPDATE-AND-RESTART.bat | SISTEM | RCE-ish dari web, server mati |
| route | `GET /api/health/ping` (tanpa session) | KEEP | lan_monitor + klien Tauri `desktop/src/app.js` | SISTEM | klien desktop menganggap server mati |
| route | `/api/health/{metrics,logs,probe-ip,wal-checkpoint}`, `/diagnostics` (diagnostics.auth) | KEEP | dipakai halaman diagnostik admin | SISTEM | monitor LAN hilang |
| route | `GET /api/health/connected-users` | DROP | 0 pemanggil (data sudah ada di metrics) | SISTEM | – |
| route | `/quick-notes*` (publik) | ASK | quick_notes.json diubah 2026-10-19 (catatan bug); tanpa login | SISTEM | bocor catatan / tidak berguna |
| route | `/files/preview-stream`, `/files/stream/{path}` | REWRITE | dipakai pratinjau; tanpa login, bisa unduh backup DB | FILE | kebocoran data total |
| route | `/storage/files/{path}` | DROP | 0 pemanggil | FILE | – |
| route | `/manifest.json` | KEEP | layout app/desktop_pic | SHELL | – |
| route | `routes/api.php`, `routes/console.php` (inspire), `routes/channels.php` | DROP | kosong/boilerplate | – | – |
| controller | ArchiveStatusController (434) | REWRITE | superadmin-status dipakai | VERIFIKASI | – |
| controller | ArchiveVerificationController (393) | REWRITE | verify/checkin dipakai | VERIFIKASI | – |
| controller | traits HandlesMultiBoxCreation/Update (586/522) | REWRITE | jalur utama pengajuan | ARSIP | – |
| controller | traits HandlesSingleBoxCreation/Update (455/417) | DROP | UI tidak memakai | ARSIP | – |
| controller | HandlesWarehouseLayoutSlots (452), FormatsArchiveLabels (82), HandlesArchiveExtensions (356), HandlesDashboardRealtime (256) | REWRITE | dipakai | GUDANG/LABEL/RETENSI/SHELL | – |
| middleware | CheckRole (`role:`, admin selalu lolos) | REWRITE | dipakai | AUTH | eskalasi/akses salah |
| middleware | EnsureDiagnosticsAuthorized (token = sha256(APP_KEY)[0:16] jika `app.diagnostic_key` kosong) | REWRITE | dipakai | SISTEM | token mudah diturunkan dari APP_KEY |
| middleware | UpdateUserPresence (UserPresenceService → presence LAN) | KEEP | dipakai metrics/diagnostik | SISTEM | – |
| middleware | TrackPageVisits | DROP | tidak terdaftar di Kernel; model `MasterLogKunjungan` tidak ada | – | – |
| middleware | TrustHosts, CheckForMaintenanceMode, dll (bawaan) | DROP | diganti bawaan L13 | – | – |
| class | `app/Bootstrap/HandleExceptions` | ASK | dipasang di Http & Console Kernel (override bawaan); perlu dicek tujuannya (PHP 8.2 deprecation?) | OPS | error tersembunyi |

## 2. Model, service, helper

| jenis | item | label | bukti | modul | risiko bila salah |
|---|---|---|---|---|---|
| model | Archive (562; accessor lokasi/label status/retensi, getForm3PendingCount) | REWRITE | inti | ARSIP | label/status tampil salah |
| model | ArchiveItem, BorrowingLog (restore_eligibility, snapshot), DestructionLog, ArchiveExtensionLog, Department, SubDepartment, MasterArchive, NumberingFormat, Warehouse, WarehouseLocation, WarehouseRackSlot, WarehouseEntryLog, ActivityLog, AppSetting, User | KEEP | semua tabelnya berisi data | DATA | kompatibilitas DB |
| model | `app/User.php` (bawaan) | DROP | auth memakai `App\Models\User`; 0 rujukan | – | – |
| service | NumberingService (counter atomik RETURNING) | KEEP | dipakai verify | PENOMORAN | nomor ganda |
| service | ArchiveRetentionService, ArchiveSimilarityService | KEEP | dipakai create/edit | ARSIP | retensi/kemiripan salah |
| service | ActivityLogger, SystemEventStream | KEEP | activity_logs 436, system_events 221 | LOG/SHELL | audit/notifikasi hilang |
| service | UserPresenceService | KEEP | metrics/diagnostik | SISTEM | – |
| service | MultiBoxExcelService, MultiBoxTemplateService, SpreadsheetReader, Traits/NormalizesExcelArchiveData | ASK | ikut status impor multi-box | ARSIP | – |
| service | MasterDataImportService | KEEP | impor master (commit terbaru) | MASTER | – |
| class | `app/Imports/NewsImport.php`, `app/Exports/NewsTemplateExport.php` | DROP | 0 rujukan; memakai Maatwebsite + model MasterNews yang tidak ada | – | – |
| helper | `db_immediate_transaction`, `app_storage_url`, `app_preview_stream_url` (`app/helpers.php`) | KEEP | 92 pemakaian di view/app | DATA/FILE | SQLITE_BUSY, link file rusak |
| config | AppServiceProvider: PRAGMA WAL, busy_timeout 5000, synchronous NORMAL | KEEP | dipakai | DATA | lock/korupsi saat banyak klien |

## 3. View (per folder)

| jenis | item | label | bukti | modul | risiko bila salah |
|---|---|---|---|---|---|
| view | `auth/*` (login 509 + modals + scripts), `layouts/partials/lan_monitor` | REWRITE | dipakai | AUTH | – |
| view | `layouts/desktop_pic` + `pic_desktop_*`, `pic/*`, `pic_system_settings_modal` (~4.900 baris, MDI iframe) | REWRITE | shell PIC | SHELL | UX PIC berubah tanpa persetujuan |
| view | `layouts/app` + `app_desktop_header`, `app_embed_shell`, `desktop_layout_scripts`, `desktop/*`, `system_settings_modal` | REWRITE | shell admin/gudang (MDI) | SHELL | – |
| view | `layouts/partials/{confirm_modal,file_preview_modal,file_preview_scripts,label_print_modal,change_password_modal,expiry_warning_modal,head_assets}` | REWRITE | dipakai kedua shell | SHELL | – |
| view | `archives/*` (index, create, edit, show, print_sticker, partials, modals) | REWRITE | dipakai | ARSIP/VERIFIKASI/LABEL | – |
| view | `dashboard/index` + `dashboard/partials/*` (modal salinan dari archives) | REWRITE | dashboard admin/gudang; modal ganda | SHELL/VERIFIKASI | – |
| view | `borrowings/*` | REWRITE | dipakai | PENARIKAN | – |
| view | `destructions/*` (index, propose, extend, bap, print_extension, partials) | REWRITE | dipakai | RETENSI | – |
| view | `master/*` (departments+modals, numbering, users, warehouses+modals, warehouse_layout + 13 partial canvas + 11 modal) | REWRITE | dipakai admin/gudang | MASTER/GUDANG | – |
| view | `logs/index`, `reports/{index,print}`, `diagnostics/*` | REWRITE | menu admin | LOG/LAPORAN/SISTEM | `reports/print` merujuk `css/app.css` yang tidak ada |
| view | `quick_notes/index` | ASK | ikut route quick-notes | SISTEM | – |
| view | `components/{file-uploader,form-pengajuan-modal}` | REWRITE | create/edit/borrowings/propose/extend | ARSIP | kompres/cetak form hilang |
| view | `components/{navbar,footer,admin-sidebar,admin-bottom-nav,bottom-nav,card-product,modal-search,sosmed,toggle-language}`, `layouts/admin`, `layouts/partials/admin-sidebar-content`, `welcome` | DROP | 0 rujukan (sisa template Bootstrap) | – | – |
| view | `vendor/pagination/tailwind` | REWRITE | dipakai `->links()` | SHELL | – |
| view | `vendor/pagination/{custom,default}` | DROP | 0 rujukan | – | – |
| view | `errors/{403,404,419,500}` | REWRITE | dipakai otomatis oleh framework | SHELL | – |

## 4. Aset & library

| jenis | item | label | bukti | modul | risiko bila salah |
|---|---|---|---|---|---|
| library | `public/js/vendor/tailwindcss.js` (JIT runtime di browser) | REWRITE | dimuat di head_assets setiap halaman/iframe | SHELL | lambat di PC low-spec → ganti CSS hasil build |
| library | `alpine.min.js` | KEEP | 45 view memakai `x-data` | SHELL | – |
| library | `lucide.min.js` | KEEP | 118 file | SHELL | ikon hilang |
| library | `pdf.min.js` + `pdf.worker.min.js` | KEEP | file_preview_scripts | FILE | pratinjau PDF mati |
| library | `qrcode.min.js` | KEEP | print_sticker | LABEL | QR label hilang |
| library | `chart.min.js` | DROP | 0 rujukan di view | – | – |
| aset | `public/js/{file-uploader-preview,dms-button-indicator}.js`, `public/css/{fonts,dms-button-indicator}.css` | REWRITE | head_assets | SHELL | – |
| aset | `public/images/{logo-indraco*.png,logo_indraco.png,icon*}` | KEEP | layout/login/label | SHELL | – |
| aset | `public/storage/*` (folder nyata, 15 MB) + `storage/app/public/archive_scans` | KEEP | path file di DB | FILE | file arsip hilang |
| aset | `public/fonts`, `public/css/vendor` (kosong) | DROP | kosong; fonts.css hanya fallback sistem | – | – |
| aset | `resources/js`, `resources/sass`, `webpack.mix.js`, `package.json` (laravel-mix) | DROP | `public/css/app.css` tidak pernah dibuild; tidak ada node_modules | – | – |
| library | composer: laravel/framework 7, fideloper/proxy, fruitcake/laravel-cors, guzzle, tinker | REWRITE | diganti L13 bawaan | – | – |
| aset | `storage/app/backup_indraco_*.sqlite` (21 file) | ASK | hasil backup; dapat diunduh publik via /files/stream | SISTEM | kebocoran data |

## 5. Script, ops, klien, dokumen

| jenis | item | label | bukti | modul | risiko bila salah |
|---|---|---|---|---|---|
| script | `START-DMS-INDRACO.bat` (cari PHP portable, vendor, migrate, storage:link, `artisan serve` 0.0.0.0:8000, `PHP_CLI_SERVER_WORKERS=4` di .env) | REWRITE | cara jalan resmi (nama wajib tetap) | OPS | server tidak bisa dijalankan |
| script | `STOP-DMS-INDRACO.bat`, `START-SILENT-DESKTOP.vbs`, `START-CLIENT-WORKSTATION.bat/.sh` | KEEP | launcher | OPS | – |
| script | `UPDATE-AND-RESTART.bat` (git pull + migrate) | ASK | dipanggil fitur git-pull di web | OPS | update jarak jauh |
| script | `START-DMS-INDRACO.exe`, `DMS-Indraco-Client.exe` (biner di git) | ASK | dilacak git; `scratch/Launcher.cs` sumbernya? | OPS | launcher exe tidak cocok |
| script | `evironment/php-8.2.29…` (PHP portable, typo "evironment") | KEEP | launcher & vbs mencarinya (perlu PHP 8.4 untuk L13) | OPS | server tidak start di PC tanpa PHP |
| klien | `desktop/` (Tauri: `src/app.js` memanggil `/api/health/ping`, GitHub releases; `server_url.txt`; command `reset_app_cache_and_restart`) + `.github/workflows/build-clients.yml` | KEEP | klien workstation | OPS | klien desktop putus |
| script | `RUN-K6-BENCHMARK.bat`, `run-k6-benchmark.sh`, `K6-CLIENT-PACKAGE/` (+zip, k6.exe), `benchmark_*` | ASK | alat uji beban (k6 memanggil realtime.check) | OPS | – |
| script | `scan_lines.php`, `scratch_check_*.php`, `test_r7.php`, `scratch/*` | DROP | skrip debug sekali pakai | – | – |
| dokumen | `*.md` rencana/audit revisi, `catatan.txt`, `tasks/lessons.md`, `graphify-out/` | DROP (sebagai kode) | referensi historis saja | – | – |
| dokumen | `Form Pengajuan Dokumen.pdf`, `Form Pengendalian Dokumen.docx`, `layout*.png/pdf`, `data_kategori_dokumen.*`, `template-import-master/*.xlsx` | KEEP (referensi) | acuan form cetak, master, dan layout rak | ARSIP/MASTER | form cetak tidak sesuai |
| secret | `backup_env.txt` (dilacak git; APP_KEY, APP_DEBUG=true) | DROP | rahasia di repo | SISTEM | kebocoran APP_KEY |
| config | `.env` APP_DEBUG=true, APP_ENV=local | REWRITE | produksi LAN | SISTEM | stack trace bocor |
| seed | `database/seeds/*` (semua user `Hash::make('password')`) | REWRITE | dipakai saat DB baru | DATA | akun default lemah |
| test | `tests/Feature/*` (19 file) | ASK | sebagian menulis ke DB nyata (BOX-TEST-*, login Symfony 29) | – | test merusak data produksi |

## 6. Tabel database (DB nyata)

| jenis | item | label | bukti (baris / terakhir) | modul | risiko bila salah |
|---|---|---|---|---|---|
| tabel | archives | KEEP | 57 / 2026-11-17 | ARSIP | – |
| tabel | archive_items | KEEP | 923 / 2026-11-17 | ARSIP | – |
| tabel | borrowing_logs | KEEP | 3 / 2026-10-20 | PENARIKAN | – |
| tabel | destruction_logs | KEEP | 2 / 2026-10-21 | RETENSI | – |
| tabel | archive_extension_logs | KEEP | 0 (alur baru belum dipakai) | RETENSI | – |
| tabel | departments / sub_departments / master_archives | KEEP | 30 / 14 / 75 | MASTER | – |
| tabel | users | KEEP | 32 (bcrypt) | USER | login gagal |
| tabel | numbering_formats | KEEP | 1 (counter 12) | PENOMORAN | nomor ganda |
| tabel | warehouses / warehouse_locations / warehouse_rack_slots / warehouse_entry_logs | KEEP | 9 / 89 / 8.001 / 7 | GUDANG | – |
| tabel | activity_logs | KEEP | 436 / 2026-11-17 | LOG | – |
| tabel | system_events | KEEP | 221 (sumber notifikasi; tanpa pembersihan otomatis) | SHELL | tabel membengkak |
| tabel | app_settings | KEEP | 5 | SISTEM | – |
| tabel | migrations | KEEP | 31 | DATA | migrasi L13 bentrok |
| kolom | `borrowing_logs.active_restore_archive_id`, `destruction_logs.extension_reason/scan_extension_form`, `archives.scan_approval_input` | ASK | ada di DB; `scan_approval_input` 0 terisi | DATA | – |
| kolom | `archives.archive_code` (dirujuk proposeForm) | DROP | tidak ada di DB; SQLite menganggapnya string literal | RETENSI | – |
| tabel | sessions/cache/jobs | – | tidak ada (session & cache driver `file`) | – | – |

## Rekap label

| Label | Jumlah baris |
|---|---|
| KEEP | 60 |
| REWRITE | 40 |
| DROP | 23 |
| ASK | 17 |

## Pertanyaan ASK untuk pemilik

1. Impor multi-box Excel (arsip): pertahankan? Selama ini hanya dipakai admin untuk uji.
2. Persetujuan departemen pada penarikan (`dept-approve`, hanya admin): masih diperlukan?
3. Pengembalian box (`borrowings.return`): semua penarikan saat ini permanen. Apakah pinjam sementara diperlukan?
4. `archives/checkout` (keluar box dari katalog) vs `borrowings/dispatch`: cukup satu jalur?
5. Impersonate user oleh admin: dipertahankan?
6. Hapus massal (clear logs/box allocations/archives) di pengaturan: dipertahankan, dibatasi, atau dibuang?
7. Git pull + restart dari web: dipertahankan?
8. Quick Notes publik tanpa login: dibuang, atau dipindah ke dalam login?
9. 21 file backup lama di `storage/app`: boleh dibersihkan/dipindah ke luar web?
10. Biner `.exe` di repo dan K6 benchmark: dibawa?
11. `UPDATE-AND-RESTART.bat`: tetap disediakan?
12. Kolom tak terpakai (`scan_approval_input`, `active_restore_archive_id`, kolom extension di destruction_logs): biarkan apa adanya?
13. Test suite lama yang menulis ke DB nyata: dibuang dan diganti test baru di DB terpisah?
14. `app/Bootstrap/HandleExceptions` (override bootstrap error handler): apa tujuannya?
15. Shell MDI iframe (gaya Delphi): boleh diganti halaman biasa dengan menu/tab yang sama?
16. Ukuran font: tetap global (semua user), atau preferensi per user?
