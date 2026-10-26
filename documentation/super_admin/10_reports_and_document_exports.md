# Modul Pusat Laporan & Ekspor Dokumen (Super Admin)

## 1. Deskripsi Fitur
Modul Pusat Laporan (*Report Center*) menyajikan sarana pembuatan laporan analitik kearsipan yang komprehensif bagi jajaran manajemen dan auditor eksternal PT Indraco. 

Super Admin dapat menyusun, memfilter, mencetak ke format PDF, atau mengekspor data ke file CSV untuk 5 domain kearsipan utama:
1. **Laporan Penerimaan Arsip Masuk (*Archive Ingestion Report*):** Rekapitulasi pendaftaran box arsip baru per periode, per departemen, dan kategori dokumen.
2. **Laporan Peminjaman Berkas (*Borrowing & Circulation Report*):** Rekam jejak berkas keluar, nama peminjam, durasi peminjaman, dan status pengembalian.
3. **Laporan Pemusnahan Arsip (*Destruction & BAP Register*):** Dokumen inventaris arsip yang telah dihancurkan lengkap dengan nomor BAP dan nama saksi.
4. **Laporan Okupansi Gudang (*Warehouse Occupancy Report*):** Ringkasan pemanfaatan kapasitas rak fisik, slot terisi, slot booking, dan slot kosong per gudang.
5. **Laporan Audit Log Aktivitas (*System Audit Trail Report*):** Log rekam jejak pengguna, alamat IP, dan waktu eksekusi perubahan data.

---

## 2. Hak Akses & Role Permission
- **Role:** `admin` (Super Admin)
- **Middleware:** `auth`, `role:admin`
- **Output:** Tampilan interaktif tabel di layar, format cetak PDF siap tandatangan (*Print View*), dan file spreadsheet CSV (*Comma Separated Values*).

---

## 3. Komponen Teknis & Skema Database

### File Terkait:
- Controller: [ReportController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/ReportController.php)
  - `index()`: Antarmuka utama pusat laporan dengan pemilih jenis laporan.
  - `data()`: Panggilan asinkron AJAX untuk memuat data tabel terfilter.
  - `print()`: Menghasilkan layout cetak formal ramah printer (*Print-Optimized Layout*).
  - `exportCsv()`: Mengalirkan stream file download CSV instan.
- View: `resources/views/reports/index.blade.php`, `resources/views/reports/print.blade.php`
- Route:
  - `GET /reports` (`name('reports.index')`)
  - `GET /reports/data/{type}` (`name('reports.data')`)
  - `GET /reports/print/{type}` (`name('reports.print')`)
  - `GET /reports/export-csv/{type}` (`name('reports.export_csv')`)

---

## 4. Flowchart Alur Pembuatan & Ekspor Laporan

```mermaid
flowchart TD
    Start([Super Admin Buka Pusat Laporan /reports]) --> SelectReportType{Pilih Jenis Laporan:}
    
    SelectReportType --> TypeArchives[1. Laporan Arsip Masuk]
    SelectReportType --> TypeBorrowings[2. Laporan Peminjaman Berkas]
    SelectReportType --> TypeDestructions[3. Laporan Pemusnahan Arsip & BAP]
    SelectReportType --> TypeOccupancy[4. Laporan Okupansi & Kapasitas Rak]
    SelectReportType --> TypeAuditLogs[5. Laporan Log Audit Aktivitas]

    TypeArchives & TypeBorrowings & TypeDestructions & TypeOccupancy & TypeAuditLogs --> ApplyFilters[Tentukan Parameter Filter:]
    ApplyFilters --> SetDept[Filter Departemen / Seluruh Departemen]
    ApplyFilters --> SetDateRange[Filter Rentang Tanggal Mulai s/d Tanggal Selesai]
    ApplyFilters --> SetStatus[Filter Status Spesifik cth: Overdue / Verified / Stored]

    SetDept & SetDateRange & SetStatus --> FetchData[ReportController::data Kueri Database dengan Eager Loading]
    FetchData --> RenderReportTable[Tampilkan Data pada Tabel Laporan Interaktif di Layar]

    RenderReportTable --> ExportChoice{Pilih Bentuk Output:}
    
    %% Alur Cetak PDF
    ExportChoice -- Cetak / Simpan PDF Formal --> ClickPrint[Klik Tombol 'Cetak Laporan']
    ClickPrint --> OpenPrintRoute[Buka /reports/print/{type} dengan Kop Surat Resmi Indraco]
    OpenPrintRoute --> BrowserPrint[Otomatis window.print: Simpan ke PDF / Cetak Fisik]

    %% Alur Unduh CSV
    ExportChoice -- Unduh File Spreadsheet CSV --> ClickExportCsv[Klik Tombol 'Ekspor CSV']
    ClickExportCsv --> StreamCsvRoute[Buka /reports/export-csv/{type}]
    StreamCsvRoute --> StreamDownload[Server Mengalirkan File CSV: report-{type}-YYYY-MM-DD.csv]

    BrowserPrint & StreamDownload --> End([Selesai])
```

---

## 5. Rincian Antarmuka & Langkah Kerja Super Admin

1. Buka menu **Laporan & Dokumen** (`/reports`).
2. Pilih jenis laporan yang dibutuhkan pada kartu menu atas (misal: *Laporan Peminjaman Berkas*).
3. Tentukan rentang tanggal (misal: *1 Januari 2026 s/d 31 Desember 2026*).
4. Pilih departemen atau biarkan *Semua Departemen*.
5. Klik **Terapkan Filter**.
6. **Mencetak Laporan:** Klik tombol **Cetak Laporan**. Tampilan akan menyesuaikan dengan format kop surat resmi PT Indraco lengkap dengan kolom tanda tangan Kepala Divisi dan Manager Umum.
7. **Mengunduh CSV:** Klik tombol **Ekspor CSV** untuk membuka data di Microsoft Excel guna analisis mendalam.
