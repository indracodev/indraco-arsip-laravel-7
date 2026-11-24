# Modul: DATA (skema, model, seeder)
Status: dibangun (Fase 2) — menunggu ACC

## Skema baseline
- Satu migrasi: `database/migrations/0001_01_01_000000_baseline_dms_schema.php` = isi `sqlite_master` DB produksi L7 apa adanya (17 tabel + 31 index, tanpa trigger/view), urutan induk dulu. SQL **tidak boleh diedit**; perubahan skema = migrasi baru.
- DB baru (kosong): baseline membuat semuanya. DB L7 lama (ke-17 tabel ada): baseline tidak melakukan apa-apa, hanya tercatat di `migrations` (batch berikutnya, di samping 31 nama migrasi L7). Sebagian tabel saja: migrate berhenti dengan pesan, tanpa mengubah apa pun.
- `down()` sengaja kosong (rollback baseline = menghapus seluruh data).
- Migrasi skeleton L13 dihapus: `users`/`password_reset_tokens`/`sessions` (bentrok dengan `users` L7; session = file), `cache`/`cache_locks` (cache = file), `jobs`/`job_batches`/`failed_jobs` (queue = sync). L7 tidak punya `password_resets`, `sessions`, `cache`, `jobs`.
- Catatan skema L7 yang dipertahankan apa adanya: `users.email` **tanpa UNIQUE index** (hilang saat tabel dibangun ulang di L7), `app_settings.key` tanpa UNIQUE, partial unique `idx_archives_box_number_active` (box aktif, `status NOT IN ('taken','destroyed','borrowed')`), CHECK `archives.status` (9 nilai) dan `users.role` (3 nilai).
- Koneksi L13 menyetel `journal_mode=wal` (persisten di file). DB L7 asli memakai `delete`; setelah dibuka L13 file menjadi WAL. L7 juga menyetel WAL, jadi tetap bisa dibuka L7.

## Bukti
- `tests/Feature/Database/BaselineSchemaTest.php`: (a) DB baru → `sqlite_master` (whitespace dinormalkan) sama persis dengan `tests/Fixtures/l7_schema.json` (dump `sqlite_master` + daftar migrasi L7 dari DB produksi); (b) DB berbentuk L7 + data → migrate exit 0, skema & isi semua tabel identik, hanya `migrations` +1 baris; (c) skema sebagian → migrate gagal, tabel tidak bertambah.
- Uji manual pada salinan DB produksi (2026-11-24): migrate → `Nothing to migrate` di run kedua; isi 17 tabel identik (md5 per tabel), `migrations` 31 → 32.

## Model (`app/Models`, `$table` eksplisit, tanpa logika bisnis)
| Kelas | Tabel | Tidak fillable (diset eksplisit di kode) | Cast penting |
|---|---|---|---|
| User | users | role, department_id | password hashed, email_verified_at |
| Department | departments | – | is_active, retention_years |
| SubDepartment | sub_departments | department_id | is_active |
| MasterArchive | master_archives | department_id | is_active |
| Archive | archives | status, box_number, department_id, created_by_user_id | tanggal periode/retensi/penyerahan, re_store_snapshot array |
| ArchiveItem | archive_items | – | `$touches = ['archive']` (optimistic lock induk) |
| BorrowingLog | borrowing_logs | status, approval_status | snapshot_items array, tanggal |
| DestructionLog | destruction_logs | approval_status | destruction_date |
| ArchiveExtensionLog | archive_extension_logs | approval_status | tanggal, additional_years float |
| NumberingFormat | numbering_formats | current_counter (hanya `UPDATE … RETURNING`) | – |
| Warehouse | warehouses | – | boolean |
| WarehouseLocation | warehouse_locations | – | assigned_department_ids array, canvas int |
| WarehouseRackSlot | warehouse_rack_slots | status | – |
| WarehouseEntryLog | warehouse_entry_logs | – | entry_date |
| ActivityLog | activity_logs | – (hanya ditulis ActivityLogger) | properties array |
| AppSetting | app_settings | – | – |
| SystemEvent | system_events | – | payload array; tanpa updated_at |

- `archives.content_description` **tidak** di-cast: teks bebas (0 dari 57 baris berisi JSON); L7 menyimpan JSON hanya untuk draft multi-box.
- `App\User` L7 (mati) tidak dibawa.
- Larastan tidak bisa membaca kolom dari migrasi SQL mentah: model yang propertinya dipakai kode diberi `@property` (saat ini `User`).

## Seeder (hanya DB baru)
Launcher: `migrate --force --seed` hanya jika `database.sqlite` baru dibuat; jika gagal, file baru itu dihapus agar start berikutnya mengulang.
| Seeder | Isi |
|---|---|
| DepartmentSeeder | 29 departemen L7 |
| SubDepartmentSeeder | 14 sub-departemen |
| MasterArchiveSeeder | 17 type berkas (2 baris `FACTORY` dilewati seperti L7 — kode dept-nya `FCT`) |
| UserSeeder | admin@indraco.com, gudang@indraco.com, 1 PIC per departemen (`<kode>@indraco.com`); password default L7 |
| WarehouseLayoutSeeder | **layout produksi** (R8) dari `database/seeders/data/warehouse_layout.json`: 9 gudang, 9 ruang + 80 rak (kanvas, warna, departemen ditugaskan dicocokkan lewat **nama** departemen), 8.000 slot dibuat ulang (pola standar terverifikasi saat ekspor), kosong & aktif; tanpa arsip/booking/`current_box_count` |
| DatabaseSeeder | memanggil di atas dalam 1 transaksi + 1 format penomoran (pola produksi `{COMPANY}/{DEPT}/{YEAR}/{COUNTER}`, counter 0) + 5 app_settings default L7 |
Tidak dibawa: arsip/peminjaman/pemusnahan/entry contoh L7 (data uji), `UserSeeder` L7 (SQL MySQL), `SyncArchiveItemsSeeder`.

## Tes
- `tests/Feature/Database/BaselineSchemaTest.php`, `tests/Feature/Database/SeederTest.php`.
- `phpunit.xml` memaksa `APP_CONFIG_CACHE`/`APP_ROUTES_CACHE`/`APP_EVENTS_CACHE` ke file yang tidak ada: tanpa ini, cache konfigurasi produksi dari launcher membuat tes (RefreshDatabase) memakai `database/database.sqlite` asli.

## Sisa pekerjaan
- R8 selesai (Fase 3). Ekspor ulang layout: skrip ekspor di laporan Fase 3 (membaca salinan DB read-only, menolak rak dengan slot non-standar).

## R4 (Fase 3)
- Migrasi `2026_11_24_000001_add_unique_email_and_setting_key`: `users_email_unique` ON `lower(trim(email))` dan `app_settings_key_unique` ON `key`; `IF NOT EXISTS` (aman di DB lama); berhenti dengan daftar nilai ganda bila ada duplikat (tanpa dedupe otomatis). DB produksi (salinan 2026-11-24): 0 duplikat.
- Normalisasi tunggal `User::normalizeEmail()` (trim + huruf kecil): mutator `email`, login. Validasi unik modul USER nanti: normalkan input lalu `Rule::unique('users', 'email')->ignore($id)`.
