# Gambaran proyek

DMS (Document Management System) / Gudang Arsip PT Indraco. Mengelola siklus hidup box arsip fisik dan digital per departemen: pengajuan box, verifikasi dan penomoran oleh gudang, penempatan di rak, peminjaman, perpanjangan masa simpan, pemusnahan (BAP), audit log.

- Role: `admin`, `pic_gudang`, `pic_dept` (satu akun per departemen; sub departemen dikelola akun departemennya).
- Server: satu PC Windows 10 di kantor dengan **IP tetap `192.168.2.180`** (sudah di-set pemilik; default di klien exe `desktop/src/app.js` = "Server Pusat Kantor"), diakses komputer lain lewat LAN: `http://192.168.2.180:8000` (cadangan) dan `https://192.168.2.180:8443` (utama setelah sertifikat dipasang). Klien juga lewat aplikasi desktop di `desktop/` (Tauri) / `START-CLIENT-WORKSTATION.bat`.
- Data: SQLite (`database/database.sqlite`), file upload di storage lokal.
- Update: `git pull` lewat `UPDATE-AND-RESTART.bat` / tombol admin.
- Hardware acuan: AMD A9 dual-core, 8 GB RAM (server); klien PC kantor low-spec.

Tujuan rebuild dan fase: `../PLAN-REBUILD-LARAVEL-13.md`.
