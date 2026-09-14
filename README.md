# Document Management System (DMS) / Gudang Arsip PT Indraco (Laravel 7 Edition)

<p align="center">
  <img src="logo-indraco-est.png" alt="PT Indraco Logo" width="220">
</p>

Aplikasi **Document Management System (DMS) / Sistem Manajemen & Gudang Arsip PT Indraco (Laravel 7 Edition)** dirancang untuk mengelola siklus hidup fisik dan digital dokumen/arsip perusahaan secara terpusat, aman, dan teratur. 

Sistem ini memfasilitasi setiap departemen di PT Indraco dalam pencatatan draft dokumen, pemesanan (booking) lokasi penyimpanan di gudang arsip, penomoran box otomatis berformat custom, peminjaman dokumen, hingga pengawasan masa simpan (*retention expiry*) dan alur berita acara pemusnahan dokumen (BAP).

---

## 🌟 Fitur Utama & Keunggulan

### 1. Dynamic Custom Box Code Engine
Engine generator nomor box arsip otomatis berbasis format template yang dapat disesuaikan tanpa perlu mengubah kode program.
- **Pattern Default:** `{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}` &rarr; `IND/FIN/2026/III/0001`
- **Placeholder Terdaftar:** `{COMPANY}`, `{DEPT}`, `{YEAR}`, `{ROMAN_MONTH}`, `{COUNTER}`, `{COUNTER_BOX}`.

### 2. Workflow Alur Operasional End-to-End
- **Pengajuan & Booking Storage (PIC Departemen):** Penginputan metadata berkas, range tanggal periode (e.g. *Januari - Maret 2026*), masa simpan retention (tahun), kondisi fisik wadah, rincian dokumen, dan upload lampiran berkas digital.
- **Hub Verifikasi & Penomoran (PIC Gudang):** Pengecekan kesesuaian berkas fisik oleh kurator gudang, penolakan revisi, atau persetujuan yang secara otomatis men-generate Nomor Box Custom.
- **Check-in & Penempatan Rak Gudang:** Penempatan fisik box ke slot rak gudang (`RAK-A1 BARIS-01`), pemantauan kapasitas rak, dan pencatatan **Log Masuk Gudang**.
- **Alur Peminjaman & Pengembalian Dokumen:** Pengajuan pinjam berkas, approval kurator, pengeluaran fisik (*dispatch*), dan konfirmasi pengembalian berkas ke gudang.
- **Retention Expiry & Berita Acara Pemusnahan (BAP):** Alert notifikasi otomatis untuk dokumen yang mendekati/melewati masa simpan (&le; 90 hari), pencatatan BAP, upload sertifikat pemusnahan, dan pencetakan dokumen resmi BAP dengan blok tanda tangan 3-kolom.

### 3. Interactive 2D Warehouse Layout Canvas
Visualisasi denah fisik 2D gudang arsip interaktif pada menu `/master/warehouses/layout` untuk inspeksi kapasitas rak, alokasi departemen, dan booking slot tempat arsip.

### 4. Desktop UI Edition untuk PIC Departemen (Delphi / VB Style)
Antarmuka khusus role PIC Departemen bergaya **Desktop Workstation Form App** dengan *MDI Tabbed Workspace*, *Delphi Action Ribbon*, *DBGrid Compact Spreadsheet*, dan integrasi pintasan keyboard (`F2`, `F5`, `F8`, `F9`, `Ctrl+F`).

### 5. Global Audit Trail Log System
Pencatatan riwayat aktivitas secara transparan:
- **Log Masuk Gudang:** Mencatat tgl masuk, PIC Gudang penerima, lokasi rak, & catatan reception.
- **Log Peminjaman:** Mencatat peminjam, alasan pinjam, tgl pinjam, estimasi kembali, realisasi pengembalian, & verifikator.
- **Log Pemusnahan:** Mencatat nomor BAP, tanggal eksekusi, metode fisik (incinerator/shredder), dan eksekutor.

---

## 👥 Hak Akses & Peran Pengguna (Role & Access Control)

| Peran (Role) | Kode System | Deskripsi & Hak Akses |
| :--- | :--- | :--- |
| **Super Admin / Management** | `admin` | Pengelolaan Master Data (Departemen, Gudang, Rak, Format Box, User & Role), melihat analisis global & seluruh audit log. |
| **PIC Gudang (Warehouse Curator)** | `pic_gudang` | Dashboard gudang, verifikasi pengajuan, penomoran box, check-in rak, dispatch peminjaman, & eksekusi BAP pemusnahan. |
| **PIC Departemen Client** | `pic_dept` | Input draft arsip departemennya, pengajuan booking storage gudang, peminjaman dokumen, & katalog khusus departemen. |

---

## 🛠️ Tech Stack & Dependensi

* **Framework Core:** Laravel 7 (PHP 7.4 / 8.0 / 8.2)
* **UI & Frontend Layout:** Tailwind CSS v3 (Corporate Navy `#0F172A` & Gold `#D4AF37`), Alpine.js, Lucide Icons
* **Database:** SQLite (Local Dev) / MySQL / MariaDB
* **Branding Assets:** Logo Resmi PT Indraco (`logo-indraco-est.png`)

---

## ⚙️ Konfigurasi Environment & Skala Ukuran Font (`.env`)

Aplikasi DMS PT Indraco mendukung pengaturan skala ukuran font secara dinamis di seluruh halaman melalui parameter `APP_FONT_SIZE` pada file `.env`.

```env
# Pengaturan Skala Ukuran Font Aplikasi
# Pilihan opsi: small, medium, large, xlarge, atau spesifik (misal: 15px, 95%)
APP_FONT_SIZE=medium
```

---

## 🔑 Kredensial Pengguna Demo (Default Seeders)

Halaman login dilengkapi dengan **Tombol Pintasan Login Cepat (One-Click Demo Login)** untuk kemudahan pengujian:

| Role Account | Email Login | Password | Link Departemen |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@indraco.com` | `password` | Global / Management |
| **PIC Gudang (Specialist)** | `gudang@indraco.com` | `password` | Gudang Arsip Utama |
| **PIC Dept Keuangan** | `fin@indraco.com` | `password` | Keuangan & Akuntansi (`FIN`) |
| **PIC Dept HRD** | `hrd@indraco.com` | `password` | Human Resources & Legal (`HRD`) |
| **PIC Dept Marketing** | `mkt@indraco.com` | `password` | Marketing & Sales (`MKT`) |

---

## 🚀 Panduan Memulai (Quick Start Installation)

1. **Clone Repository:**
   ```bash
   git clone https://github.com/indracodev/indraco-arsip-laravel-7.git
   cd indraco-arsip-laravel-7
   ```

2. **Install Dependensi PHP:**
   ```bash
   composer install
   ```

3. **Konfigurasi Environment File:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migrasi Database & Seeder:**
   ```bash
   # Membuat file database sqlite jika belum ada
   php -r "if(!file_exists('database/database.sqlite')) touch('database/database.sqlite');"
   
   # Jalankan migrasi dan seeder data awal
   php artisan migrate:fresh --seed
   ```

5. **Jalankan Development Server:**
   ```bash
   php artisan serve --port=8001
   ```
   Buka browser di `http://127.0.0.1:8001`

---

## 📄 Struktur Rute Utama (Application Routes)

- `/login` &harr; Halaman login corporate dengan pintasan akun demo.
- `/dashboard` &harr; Overview statistik, kapasitas gudang, alert expiry, & arsip terbaru.
- `/archives` &harr; Katalog arsip, pengajuan booking draft, & detail timeline.
- `/borrowings` &harr; Pengajuan pinjam, approval, dispatch berkas, & konfirmasi kembali.
- `/destructions` &harr; Monitoring retention expiry, eksekusi BAP, & cetak dokumen resmi BAP.
- `/logs` &harr; Global Audit Trail Logs (Log Masuk, Log Pinjam, Log Pemusnahan).
- `/master/*` &harr; Master Departemen, Gudang & Rak, Format Custom Box, & User Management.

---

&copy; 2026 **PT Indraco**. All rights reserved.
