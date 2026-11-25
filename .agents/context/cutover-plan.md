# Rencana Cutover DMS Arsip PT Indraco (Laravel 7 → Laravel 13)

Dokumen ini adalah panduan pelaksanaan migrasi dari `indraco-arsip-laravel-7` ke `indraco-arsip-laravel-13` (tanpa mengubah atau menghapus folder L7 asli sampai masa transisi selesai).

---

## 1. Pra-Cutover & Backup (Di Server)
1. Pastikan seluruh pengguna sudah menyimpan draft/transaksi dan keluar dari aplikasi.
2. Hentikan server Laravel 7 menggunakan `STOP-DMS-INDRACO.bat`.
3. Buat folder backup di **luar** jangkauan web (misalnya `D:\Backup-DMS-Cutover-YYYYMMDD\`).
4. Salin seluruh file database dan upload dari L7 ke folder backup tersebut:
   - `indraco-arsip-laravel-7\database\database.sqlite` (beserta `-wal` / `-shm` jika ada).
   - `indraco-arsip-laravel-7\storage\app\public\` dan `indraco-arsip-laravel-7\public\storage\`.
5. **ASK 10**: Pindahkan 21 file `backup_indraco_*.sqlite` dari `indraco-arsip-laravel-7\storage\app\` ke folder backup eksternal di atas (jangan dibawa masuk ke `storage/app/` L13).

## 2. Salin Database & File Upload ke Laravel 13
1. Salin `database.sqlite` hasil backup ke `indraco-arsip-laravel-13\database\database.sqlite`.
2. Salin isi folder upload L7:
   - `storage/app/public/*` → `indraco-arsip-laravel-13\storage\app\public\` (folder allow-list: `archive_scans`, `archive_scan_inputs`, `archive_files`, `archive_approvals`, `archive_extensions`, `borrowing_approvals`, `destruction_approvals`, `bap_certificates`).
   - `public/storage/*` → `indraco-arsip-laravel-13\public\storage\` (disk `legacy_public` tetap terbaca otomatis oleh `FileStorage::locate()` sesuai temuan B11).
3. Pastikan file `.env` di `indraco-arsip-laravel-13` sudah siap:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY` baru (di-generate via `php artisan key:generate` sebelum cutover — jangan bawa `APP_KEY` L7 dari `backup_env.txt`).
   - `APP_URL=http://192.168.2.180:8000` (atau ikuti `.agents/context/runbook-ganti-ip.md` jika IP berubah).
   - `SESSION_SECURE_COOKIE=false` (selama masih menggunakan HTTP `:8000`; ubah ke `true` hanya setelah seluruh klien memasang sertifikat `:8443`).

## 3. Migrasi Database & Pembersihan Data Uji
1. Pastikan tidak ada duplikat pada `users.email` (`LOWER(TRIM(email))`) dan `app_settings.key` sebelum migrasi.
2. Jalankan migrasi secara aman (menggunakan PHP portable):
   ```bat
   set PHPRC=evironment\php-8.4.28-nts-Win32-vs17-x64
   evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan migrate --force
   ```
   Migrasi yang akan berjalan:
   - `0001_01_01_000000_baseline_dms_schema` (otomatis dilewati karena 17 tabel L7 sudah ada).
   - `2026_11_24_000001_add_unique_email_and_setting_key` (menambahkan UNIQUE index pada `users.email` & `app_settings.key`, temuan R4).
   - `2026_11_24_000002_add_font_size_to_users` (menambahkan kolom `users.font_size`).
3. **ASK 11 / Temuan B1 & R9**: Setelah DB terbackup dan migrasi sukses, bersihkan data sisa uji L7 (`BOX-TEST-*`, `TEST_DEPT`, user `pic_test_scope_check`) secara terverifikasi oleh Admin.

## 4. Menyalakan Server Laravel 13
1. Jalankan `START-DMS-INDRACO.bat` di folder `indraco-arsip-laravel-13`.
2. Pastikan output startup menampilkan:
   - `optimize` & `assets:cache` & `views:warm` selesai tanpa error.
   - Caddy + pool `php-cgi` siap dan `/api/health/ready` merespons OK.
3. Jika Windows Firewall muncul untuk `caddy.exe`, klik **Allow access (Private/Domain network)**.

## 5. Verifikasi Pasca-Cutover (Checklist Lapangan)
- [ ] `http://127.0.0.1:8000/api/health/ping` dan `http://192.168.2.180:8000/api/health/ping` mengembalikan `200 OK` dengan header `Access-Control-Allow-Origin: *`.
- [ ] Login sebagai **Admin**, **PIC Gudang**, dan **PIC Departemen** menggunakan akun produksi berjalan normal.
- [ ] Buka arsip lama dari L7 dan uji pratinjau/unduh file scan (`archive_scans`, `borrowing_approvals`, `bap_certificates`) — tidak ada `404` dan tidak bisa diakses tanpa login (`302 /login`).
- [ ] Akses `/storage/...` secara langsung mengembalikan `404` (diblokir Caddy).
- [ ] Uji aplikasi desktop `DMS-Indraco-Client.exe` dari salah satu PC klien LAN.
- [ ] (Opsional transisi HTTPS) Jalankan `INSTALL-SERTIFIKAT-KLIEN.bat` di PC klien jika ingin mengaktifkan PWA & HTTPS `:8443`.

## 6. Rencana Rollback (Jika Terjadi Kendala Kritis)
1. Jalankan `STOP-DMS-INDRACO.bat` di folder `indraco-arsip-laravel-13`.
2. Jalankan kembali `START-DMS-INDRACO.bat` di folder `indraco-arsip-laravel-7` (folder dan `database.sqlite` L7 asli tidak diubah selama proses cutover).
