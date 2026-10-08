# IMPLEMENTATION PLAN REVISI 4: PEMBARUAN & PENYERAGAMAN FLOW WORKSTATION DMS PT INDRACO
**File:** `implementation_plan_revisi_4.md`  
**Tanggal:** 7 Oktober 2026  
**Status:** Fase 1 Aktif (Pengerjaan Point 1 — Menu Tabs & Flow Penarikan, Pemusnahan, Expiry)

---

## DAFTAR ISI
1. [Ringkasan Eksekutif & Arsitektur](#1-ringkasan-eksekutif--arsitektur)
2. [Point 1: Perubahan Menu Tabs & Flow Terpadu (Fokus Saat Ini)](#2-point-1-perubahan-menu-tabs--flow-terpadu-fokus-saat-ini)
3. [Daftar Rencana Poin 2 s/d 11](#3-daftar-rencana-poin-2-sd-11)
4. [Identifikasi File & Kode Terdampak](#4-identifikasi-file--kode-terdampak)
5. [Langkah-Langkah Eksekusi Point 1](#5-langkah-langkah-eksekusi-point-1)

---

## 1. RINGKASAN EKSEKUTIF & ARSITEKTUR

Aplikasi Document Management System (DMS) PT Indraco berbasis Laravel 7 dengan mode desktop workstation style MDI (Multiple Document Interface). Sesuai arahan pengguna, implementasi dilakukan secara bertahap dan ketat:
- **Aturan Eksekusi:** Hanya kerjakan **Point 1** terlebih dahulu. Jangan lanjut ke poin berikutnya sebelum Point 1 selesai dan diverifikasi.
- **Prinsip:** Simplicity first, reuse existing pattern, zero broken routes/features.

---

## 2. POINT 1: PERUBAHAN MENU TABS & FLOW TERPADU (FOKUS SAAT INI)

### A. Perubahan Menu Tabs (MDI Navigation Header)
Pada layout desktop PIC Departemen (`resources/views/layouts/desktop_pic.blade.php`), urutan dan nama tabs diatur sebagai berikut:

1. **Tab 1: Katalog Arsip**
   - Title: `[Form 1] Katalog Arsip {{ auth()->user()->department->code ?? "" }}`
   - ID: `archives`
   - URL: `{{ route('archives.index') }}?embed=1`
   - Icon: `folder-archive`

2. **Tab 2: Penarikan Berkas** *(Perubahan dari "Peminjaman Berkas")*
   - Title: `[Form 2] Penarikan Berkas`
   - ID: `borrowings`
   - URL: `{{ route('borrowings.index') }}?embed=1`
   - Icon: `file-symlink`
   - Tombol Toolbar & Shortcut: Diperbarui menjadi `Penarikan (F8)`

3. **Tab 3: Pengajuan Perpanjangan & Pemusnahan** *(Penggabungan menu Perpanjangan & Pemusnahan)*
   - Title: `[Form 3] Pengajuan Perpanjangan & Pemusnahan`
   - ID: `destructions`
   - URL: `{{ route('destructions.index') }}?embed=1`
   - Icon: `shield-alert`
   - **Struktur Konten (3 Sub-Tabs):**
     1. **Sub-Tab 1: Berkas Jatuh Tempo (H-30):** Tabel arsip yang berada pada batas $\le$ 30 hari dari tanggal expiry retention (atau telah lewat). Jika tidak ada data yang masuk rentang H-30, tabel bersih/empty state. Tiap baris data memiliki 2 tombol aksi langsung: `Perpanjangan` dan `Pemusnahan`.
     2. **Sub-Tab 2: Riwayat Perpanjangan:** Default kosong, mencatat log riwayat arsip yang telah disetujui perpanjangan retensinya.
     3. **Sub-Tab 3: Riwayat Pemusnahan:** Default kosong, mencatat log riwayat arsip yang telah resmi dimusnahkan dan diterbitkan Berita Acara Pemusnahan (BAP).

4. **Tab 4: Draft Pengajuan Box Baru**
   - Title: `Draft Pengajuan Box Baru`
   - ID: `archives_create`
   - URL: `{{ route('archives.create') }}?embed=1`
   - Icon: `plus-circle`

---

### B. Standardisasi Flow Terpadu (Penarikan, Pemusnahan, Expiry)
Ketiga modul transaksi tersebut menggunakan alur kerja (workflow) yang identik:

```
[ Masuk Halaman / Tab ]
          │
          ▼
[ Klik Tombol Aksi di Header / Toolbar ]
  (e.g., "Ajukan Penarikan Berkas", "Ajukan Pemusnahan Berkas", "Ajukan Perpanjangan Expiry")
          │
          ▼
[ Buka Formulir Permintaan Berkas (Format Peminjaman Berkas) ]
  • Kolom Pencarian Autocomplete Live (No. Box / Judul / Periode)
  • Kartu Detail Berkas Terpilih (Kondisi, Lokasi Gudang, Status)
  • Parameter Input Khusus:
      - Penarikan: Maksud/Alasan Penarikan, Upload Bukti Approval (Murni Penarikan / Tanpa Estimasi Pengembalian)
      - Pemusnahan: No. BAP, Metode Pemusnahan, Tanggal, Alasan, Upload Approval
      - Expiry: Tambahan Tahun (+1 s/d +10 Thn), Alasan Perpanjangan, Upload Approval
          │
          ▼
[ Submit Pengajuan & Validasi File <= 2MB ]
          │
          ▼
[ Berkas Masuk ke Log & Antrean Verifikasi ]
```

---

## 3. DAFTAR RENCANA POIN 2 S/D 11

Berikut status dan catatan poin lainnya:

- **Point 2 (SELESAI):** Tab peminjaman mandiri telah dihilangkan dan digantikan sepenuhnya oleh tab Penarikan Berkas pada Point 1.
- **Pembaruan Tab Pemusnahan & Expiry:** Konten awal tab Pemusnahan disamakan dengan Penarikan (awal kosong riwayat pemusnahan departemen), input tambahan masa simpan expiry diselaraskan per bulan (seperti 60 bulan di draft arsip), dan format tanggal ISO diperbaiki menjadi standar `d M Y`.
- **Point 3 (SELESAI):** Periode arsip (Periode Mulai & Periode Selesai) dibatasi dan dikunci maksimal bulan aktif saat ini (`max="{{ date('Y-m') }}"`), bulan di masa depan di-disable pada datepicker, serta dilengkapi validasi otomatis di Alpine.js dan backend controller.
- **Point 4 (SELESAI):** Pratinjau visual (Live Preview) saat upload dokumen (Image thumbnail lightbox zoom/rotate & PDF modal preview), smart client-side compression (canvas max 2400px, JPEG quality 0.88 teks tajam).
- **Point 5 & 6 (SELESAI - DIGABUNG):**
  - Bug storage link offline diatasi secara tuntas: menggunakan direktori fisik asli `public/storage/` (zero symlink dependency) dan konfigurasi disk `'public' => public_path('storage')`.
  - Path upload dan helper `app_storage_url()` mengarah langsung ke static asset server dengan fallback streaming `FileStreamController`.
  - Berkas fisik demo sample arsip yang sudah ada di database disediakan lengkap di `public/storage/archive_scans/`.
  - Batas maksimal upload file gambar / dokumen penyerta diturunkan menjadi 2 MB (`max:2048`) baik pada validasi client-side Alpine/JS maupun backend controller (`ArchiveController`, `BorrowingController`, `DestructionController`).
- **Point 7 (SELESAI):** Master Departemen di PIC Gudang disembunyikan (khusus Super Admin):
  - Definisi tab `departments` pada `resources/views/layouts/app.blade.php` diisolasi dengan `@if(auth()->check() && auth()->user()->isSuperAdmin())`. PIC Gudang tidak lagi melihat tab atau tombol Master Departemen di MDI.
  - Seluruh rute `/master/departments` dan `/master/sub-departments` di [routes/web.php](file:///c:/laragon/www/indraco-arsip-laravel-7/routes/web.php) dilindungi ketat middleware `role:admin`.
  - Pengecekan otorisasi ganda pada [app/Http/Controllers/DepartmentController.php](file:///c:/laragon/www/indraco-arsip-laravel-7/app/Http/Controllers/DepartmentController.php) (`isSuperAdmin()` guard) untuk memblokir akses langsung URL dengan HTTP 403.
  - Endpoint API dropdown/filter departemen yang dibutuhkan PIC Gudang tetap berjalan normal tanpa gangguan.
- **Point 8:** Gudang GA dan Gudang IT disembunyikan dari canvas 2D, master lokasi, dan dropdown checkin.
- **Point 9:** Custom kunci ruangan dua arah khusus FAT (departemen non-FAT dilarang masuk FAT, berkas FAT dilarang masuk ruangan lain).
- **Point 10:** Status expired dibuat merah warning dan animasi berkedip (`animate-warning-blink`).
- **Point 11:** Kustomisasi & Opsi Nonaktif Notifikasi Ping / Latensi LAN (Pengaturan Super Admin):
  - **Latar Belakang:** Popup toast "Koneksi LAN Lemot / Lambat" di pojok kanan bawah sering muncul dan dinilai mengganggu / spam dalam pemakaian operasional harian.
  - **Fitur Baru di Menu Pengaturan (Super Admin):**
    - **Toggle Notifikasi Ping:** Switch untuk Mengaktifkan atau Menonaktifkan seluruh notifikasi popup latensi secara global.
    - **Ambang Batas Latensi (Threshold ms):** Input angka kustom batas minimal latensi dalam satuan ms agar notifikasi muncul (misal: hanya muncul jika ping >= 500 ms, 1000 ms, atau nilai yang ditentukan Super Admin).
  - **Implementasi Komponen:**
    - Tambahkan konfigurasi `AppSetting` (`ping_alert_enabled`, `ping_alert_threshold_ms`) melalui `SettingController.php`.
    - Tambahkan input form di modal pengaturan Super Admin (`resources/views/layouts/app.blade.php`).
    - Sinkronisasi nilai setting ke `resources/views/layouts/partials/lan_monitor.blade.php` via `AppServiceProvider` sehingga Alpine.js `pingServer()` menaati status aktif/nonaktif dan threshold kustom yang telah ditentukan.

---

## 4. IDENTIFIKASI FILE & KODE TERDAMPAK (UNTUK POINT 1)

1. **`resources/views/layouts/desktop_pic.blade.php`**
   - Baris 442–451: Perbarui tombol toolbar `Pinjam (F8)` menjadi `Penarikan (F8)` atau sesuai menu.
   - Baris 1265–1290: Update `availableForms` untuk mendaftarkan 4 tab utama + 1 draft tab.
   - Baris 2101–2108: Update shortcut key handler (F8).

2. **`resources/views/borrowings/index.blade.php`**
   - Ganti judul dan label teks "Peminjaman Dokumen Arsip" menjadi "Penarikan Berkas Arsip".
   - Tombol utama: "Pengajuan Penarikan Berkas".

3. **`resources/views/borrowings/create.blade.php`**
   - Update label dan teks header menjadi "Formulir Permintaan Penarikan Berkas Arsip".
   - Pastikan komponen pencarian dokumen autocomplete tetap solid dan reusable.

4. **`resources/views/destructions/index.blade.php`**
   - Dukung view tab/filter: Pemusnahan Berkas vs Expiry Retention.
   - Tambahkan tombol aksi: "Pengajuan Pemusnahan Berkas" dan "Pengajuan Perpanjangan Expiry".

5. **`resources/views/destructions/propose.blade.php` & `extend.blade.php`**
   - Standarisasi form agar memiliki header, live search autocomplete berkas (jika belum memilih arsip), kartu detail berkas, dan tombol submit terpadu seperti format `borrowings/create.blade.php`.

6. **Controller & Routes (`routes/web.php`, `DestructionController.php`, `BorrowingController.php`)**
   - Pastikan rute create untuk propose (`/destructions/propose`) dan extend (`/destructions/extend`) dapat diakses tanpa parameter wajib jika diakses dari tombol utama, dengan opsi memilih arsip via search bar.

---

## 5. LANGKAH-LANGKAH EKSEKUSI POINT 1

- [x] **Langkah 1:** Perbarui definisi tabs di `resources/views/layouts/desktop_pic.blade.php` (Penarikan Berkas, Pemusnahan Berkas, Expiry, Katalog Arsip).
- [x] **Langkah 2:** Sesuaikan antarmuka `borrowings/index.blade.php` dan `borrowings/create.blade.php` dengan terminologi "Penarikan Berkas".
- [x] **Langkah 3:** Tambahkan rute dan view form pengajuan pemusnahan berkas mandiri (`/destructions/propose` dengan pencarian berkas mandiri format form peminjaman).
- [x] **Langkah 4:** Tambahkan rute dan view form pengajuan perpanjangan expiry mandiri (`/destructions/extend` dengan pencarian berkas mandiri format form peminjaman).
- [x] **Langkah 5:** Sesuaikan `destructions/index.blade.php` agar mendukung tombol aksi langsung untuk memulai pemusnahan dan expiry dengan alur yang seragam.
- [x] **Langkah 6:** Validasi dan uji alur ketiga tabs.
