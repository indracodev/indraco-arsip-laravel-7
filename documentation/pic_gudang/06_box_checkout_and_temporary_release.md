# Dokumentasi Fitur: Pengeluaran Sementara & Check-Out Box (Box Checkout & Temporary Release)

Dokumen ini menjelaskan alur kerja pengeluaran box arsip utuh ke luar lingkungan gudang sentral Indraco untuk kebutuhan khusus seperti audit eksternal kantor akuntan publik (KAP), pemeriksaan pajak, proses pengadilan, atau review manajemen direksi.

---

## 1. Ringkasan Fitur
- **Role Pengguna**: `pic_gudang` (Warehouse Operator)
- **Menu Akses**: `Menu Utama` > `Pengeluaran Box (Check-Out)` (`/warehouse/checkout`)
- **Tujuan**:
  1. Melacak status box yang dibawa keluar dari gedung arsip fisik dengan status khusus `checkout`.
  2. Berbeda dengan peminjaman berkas harian biasa, fitur ini menangani pelepasan box utuh dengan jaminan surat tugas resmi / *gate pass* sekuriti.
  3. Mengunci slot rak agar tetap berstatus *reserved/booked* bagi box tersebut (slot tidak boleh diisi box lain selama checkout sementara).
  4. Menerima kembali box yang telah selesai digunakan di luar (`check-in return`), melakukan audit segel fisik, dan mengembalikan box ke slot aslinya.

---

## 2. Alur Kerja (Flowchart Pengeluaran Box Sementara & Pengembalian)

```mermaid
flowchart TD
    Start([Permohonan Checkout Resmi dari Dept/Direksi]) --> VerifyGatePass[Periksa Surat Izin / Gate Pass Pengeluaran]
    VerifyGatePass --> OpenCheckoutModule[PIC Gudang Buka Modul Checkout Box]
    
    OpenCheckoutModule --> InputBoxNumber[Scan Barcode / Input Nomor Box]
    InputBoxNumber --> ValidateBox[Sistem Validasi Box:
      - Harus berstatus 'stored'
      - Memiliki slot rak aktif]
      
    ValidateBox --> InputDetails[Input Data Pengeluaran:
      - Nama Instansi / Pihak Pembawa
      - Keperluan: Audit Eksternal / Pajak / Legal
      - Rencana Tanggal Pengembalian
      - Nomor Surat Tugas / Gate Pass]
      
    InputDetails --> ConfirmRelease[Konfirmasi Pengeluaran Box]
    ConfirmRelease --> UpdateCheckoutDB[Database Transaction:
      1. Update Archive status = 'checkout'
      2. Set Archive checked_out_at, checked_out_to
      3. WarehouseSlot tetap terikat archive_id (reserved)
      4. Cetak Lembar Izin Keluar (Gate Pass)]
      
    UpdateCheckoutDB --> BoxOut([Box Dibawa Keluar dari Gudang])
    BoxOut --> AuditPeriod[Periode Penggunaan di Luar Gudang]
    
    AuditPeriod --> ReturnProcess([Box Dikembalikan ke Gudang])
    ReturnProcess --> PhysicalCheck[Inspeksi Fisik Box & Segel Berkas]
    PhysicalCheck --> OpenCheckInReturn[Buka Modul Return Check-Out]
    
    OpenCheckInReturn --> ScanReturnedBox[Scan Barcode Box]
    ScanReturnedBox --> ConfirmReturn[Konfirmasi Penerimaan Kembali]
    ConfirmReturn --> RestoreDB[Database Transaction:
      1. Update Archive status = 'stored'
      2. Clear checked_out_at, checked_out_to
      3. Insert Log Check-In Kembali]
      
    RestoreDB --> PutBackToSlot[Kembalikan Box ke Slot Rak Semula]
    PutBackToSlot --> Done([Selesai])
```

---

## 3. Komponen Teknis (Backend & Database)

### 3.1 Controller & Routing
- **Controller**: [ArchiveCheckoutController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/Warehouse/ArchiveCheckoutController.php) atau [ArchiveController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/ArchiveController.php)
- **Route Endpoint**:
  - `GET /warehouse/checkout`: Daftar box yang sedang dalam status keluar (`status = checkout`).
  - `POST /warehouse/checkout/release`: Memproses pengeluaran box dan pencetakan gate pass.
  - `POST /warehouse/checkout/{archive}/return`: Memproses pengembalian box dan pengembalian status ke `stored`.

### 3.2 Eloquent Models Terkait
- [Archive.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Archive.php)
  - Atribut: `status` (`checkout`), `checked_out_at`, `checked_out_to`, `checkout_notes`.
- [WarehouseSlot.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseSlot.php)
  - Slot tetap mengait ke ID arsip agar posisi fisik di rak tidak tertukar dengan pengajuan baru.
- [ArchiveLog.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/ArchiveLog.php)

---

## 4. Langkah Operasional PIC Gudang

1. **Penerimaan Surat Izin**:
   - Terima berkas permohonan pengeluaran box yang telah ditandatangani minimal oleh General Manager / Direksi dan Kepala Departemen.
2. **Proses Checkout**:
   - Akses modul **Pengeluaran Box (Check-Out)**.
   - Scan barcode box yang akan dikeluarkan.
   - Masukkan tujuan pengeluaran, nama instansi penerima (misal: *KAP Ernst & Young* atau *KPP Pajak Pratama*), serta estimasi tanggal kembali.
   - Klik **Konfirmasi Pengeluaran**. Cetak 2 rangkap Lembar *Gate Pass* (1 untuk arsip sekuriti pos gerbang, 1 untuk arsip gudang).
3. **Pengambilan & Penyerahan**:
   - Ambil box dari rak dan serahkan kepada pihak pemohon resmi.
4. **Penerimaan Pengembalian**:
   - Saat box dikembalikan, periksa nomor segel kardus dan periksa daftar berkas.
   - Buka menu **Pengembalian Box Keluar**, scan barcode box, lalu klik **Konfirmasi Box Diterima Kembali**.
   - Letakkan kembali box ke slot rak asalnya.
