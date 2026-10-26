# Dokumentasi Fitur: Laporan Okupansi Gudang & Stok Rak (Warehouse Occupancy & Stock Reports)

Dokumen ini menjelaskan tata cara PIC Gudang dalam menghasilkan laporan kapasitas ruang gudang, okupansi slot rak per blok/lantai, perputaran sirkulasi keluar-masuk box (*inbound/outbound*), serta analisis slot kosong untuk perencanaan penerimaan arsip baru.

---

## 1. Ringkasan Fitur
- **Role Pengguna**: `pic_gudang` (Warehouse Operator / Supervisor Gudang)
- **Menu Akses**: `Menu Utama` > `Laporan Gudang` (`/warehouse/reports`)
- **Tujuan**:
  1. Memantau utilisasi kapasitas fisik gudang secara komprehensif (% terisi vs % kosong).
  2. Menyajikan rekapitulasi jumlah box tersimpan per rak, per blok, dan per departemen.
  3. Memantau tren mutasi masuk (*inflow check-in*) dan keluar (*outflow destruction / checkout*).
  4. Menyediakan fitur ekspor laporan ke format PDF dan Excel (XLSX) untuk laporan bulanan ke Manajemen Operasional & Fasilitas.

---

## 2. Alur Kerja (Flowchart Generate Laporan Okupansi & Stok)

```mermaid
flowchart TD
    Start([Akses Modul Laporan Gudang]) --> FilterScreen[Pilih Parameter Laporan:
      - Jenis Laporan: Okupansi Rak / Mutasi Masuk-Keluar / Laporan Slot Kosong
      - Rentang Tanggal / Bulan
      - Filter Zona / Blok Rak (Opsional)
      - Filter Departemen Pemilik (Opsional)]
      
    FilterScreen --> FetchData[Sistem Query Database:
      - Total Slot Terdaftar vs Terisi
      - Group by Rak, Sap, & Departemen
      - Hitung Persentase Okupansi]
      
    FetchData --> RenderDashboardTable[Tampilkan Matriks Rekapitulasi di Layar:
      - Total Rak Aktif
      - Total Kapasitas Slot (e.g. 10.000 Slot)
      - Slot Terisi (e.g. 7.850 Slot = 78.5%)
      - Slot Kosong Siap Pakai (e.g. 2.150 Slot)
      - Slot Maintenance/Rusak]
      
    RenderDashboardTable --> ExportChoice{Pilihan Ekspor?}
    
    ExportChoice -- Ekspor Excel --> GenExcel[Generate Spreadsheet .xlsx:
      - Sheet 1: Summary Okupansi per Rak
      - Sheet 2: Detail Lokasi Slot Kosong
      - Sheet 3: Breakdown per Departemen]
      
    ExportChoice -- Ekspor PDF --> GenPDF[Generate Dokumen Resmi PDF:
      - Kop Surat PT Indraco Global Indonesia
      - Grafik Batang Okupansi Rak
      - Tabel Rekapitulasi & Kolom Tanda Tangan Supervisor]
      
    ExportChoice -- Preview Saja --> EndPreview[Analisis Data di Browser]
    GenExcel --> DownloadFile([Unduh Berkas Excel])
    GenPDF --> DownloadFile([Unduh Berkas PDF])
    DownloadFile --> Finish([Selesai])
```

---

## 3. Komponen Teknis (Backend & Database)

### 3.1 Controller & Routing
- **Controller**: [WarehouseReportController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/Warehouse/WarehouseReportController.php) atau [ReportController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/ReportController.php)
- **Route Endpoint**:
  - `GET /warehouse/reports/occupancy`: Halaman laporan okupansi rak interaktif.
  - `GET /warehouse/reports/empty-slots`: Laporan daftar slot rak kosong yang siap dialokasikan.
  - `GET /warehouse/reports/occupancy/export-excel`: Unduh file rekapitulasi Excel.
  - `GET /warehouse/reports/occupancy/export-pdf`: Unduh dokumen laporan resmi format PDF.

### 3.2 Eloquent Models & Query Penting
- [WarehouseRack.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseRack.php)
- [WarehouseSlot.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseSlot.php)
- [Archive.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Archive.php)
- Query agregasi:
  ```php
  $occupancy = WarehouseSlot::selectRaw('rack_id, count(*) as total_slots, sum(case when is_occupied = 1 then 1 else 0 end) as occupied_slots')
      ->groupBy('rack_id')
      ->with('rack')
      ->get();
  ```

---

## 4. Langkah Operasional PIC Gudang

1. **Buka Menu Laporan**:
   - Dari navigasi samping, klik **Laporan Gudang** > **Okupansi & Stok Rak**.
2. **Atur Filter Analisis**:
   - Tentukan zona rak yang ingin dianalisis (misal: *Lantai 1 - Zona A*).
   - Klik tombol **Terapkan Filter**.
3. **Analisis Ketersediaan Slot**:
   - Periksa rak-rak yang memiliki persentase okupansi di atas 90% (ditandai dengan warna merah).
   - Catat rak-rak dengan sisa slot terbanyak untuk prioritas penempatan box baru minggu depan.
4. **Cetak / Ekspor Laporan**:
   - Klik **Ekspor PDF** untuk keperluan rapat koordinasi mingguan atau pelaporan berkala ke General Affairs / Direksi.
   - Klik **Ekspor Excel** apabila ingin melakukan pivot tabel berdasarkan departemen pemilik arsip.
