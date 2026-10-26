# Dokumentasi Sistem Manajemen Arsip Fisik & Digital (DMS) PT Indraco

Selamat datang di repositori dokumentasi teknis dan operasional **Document Management System (DMS) PT Indraco** (*indraco-arsip-laravel-7*). Dokumentasi ini dirancang secara komprehensif berbasis peran pengguna (*role-based*), mencakup arsitektur sistem MDI Desktop Workstation, tata letak 2D Canvas gudang fisik, alur hidup arsip dari penerimaan (*ingestion*) hingga pemusnahan (*destruction*), skema database, rute controller, serta **Flowchart alur kerja lengkap menggunakan Mermaid diagram**.

---

## 1. Ikhtisar Sistem & Standar Logistik Pergudangan

DMS PT Indraco adalah sistem manajemen arsip fisik dan rekaman digital terintegrasi yang melayani **29 Departemen** di seluruh unit bisnis Indraco Group. Sistem ini menghubungkan unit kerja departemen (*document creators*), spesialis gudang fisik (*warehouse operators*), dan administrator sistem dalam satu alur terstandardisasi.

### Prinsip Operasional Inti:
1. **Arsitektur MDI Desktop Workstation:**
   - Menyediakan pengalaman kerja gaya aplikasi desktop multi-tab (MDI - *Multiple Document Interface*) yang cepat, terisolasi, dan dilengkapi tombol pintas keyboard (*F1 s/d F12*).
2. **Kapasitas Rak Terstandarisasi:**
   - 1 Unit Rak Fisik berdimensi standar **5 Sap (Tingkat) $\times$ 20 Slot Box (TB 30g)** = **100 Slot Box per Rak**.
   - Setiap slot memiliki kode koordinat unik (cth: `RAK-R1-A-SAP-3-SLOT-12`).
3. **Mesin Jadwal Retensi Arsip (JRA):**
   - Perhitungan masa simpan otomatis berdasarkan kategori master arsip.
   - Penegakan aturan khusus **FAT (Finance, Accounting, Tax)** dengan retensi minimal **10 Tahun** sesuai regulasi perpajakan dan hukum perseroan.
   - Peringatan jatuh tempo otomatis saat arsip memasuki batas **H-30 hari** sebelum masa simpan habis.
4. **Alur Hidup Arsip Lengkap (*End-to-End Lifecycle*):**
   ```
   [PIC Dept] Draft Box Baru ──► [PIC Gudang] Verifikasi Fisik & Penempatan Slot Rak (Stored)
                                           │
       ┌───────────────────────────────────┼───────────────────────────────────┐
       ▼                                   ▼                                   ▼
   [Penarikan Berkas]             [Perpanjangan Retensi]              [Pemusnahan Berkas]
   Peminjaman ke Dept /          Pengajuan Justifikasi Bisnis       Usulan H-30 ──► Multi-Box BAP
   Serah Terima Dokumen (Loan)   & Approval Super Admin             ──► Eksekusi Pemusnahan Fisik
   ```

---

## 2. Matriks Hak Akses Berdasarkan Peran (Role-Based Access Control)

Sistem mendefinisikan 3 peran pengguna (*roles*) dengan batasan wewenang terstruktur:

| Modul & Fungsionalitas | Super Admin (`admin`) | PIC Gudang (`pic_gudang`) | PIC Departemen (`pic_dept`) |
| :--- | :---: | :---: | :---: |
| **Dashboard & Telemetri LAN** | Akses Penuh (Global KPI & Telemetri Server) | Dashboard Operasional Gudang | Dashboard Khusus Departemennya |
| **Katalog Arsip Global** | Akses Penuh (29 Departemen) | Akses Penuh (Lokasi Rak & Box) | Terbatas Arsip Departemen Sendiri |
| **Registrasi Box Baru (Draft)** | Buat & Edit Arsip Bebas | Lihat Pengajuan Masuk | Buat Draft & Unggah Berkas Unit |
| **Verifikasi & Check-In Rak** | Verifikasi & Override Status Bebas | Eksekusi Fisik & Penempatan Slot Rak | Tidak Memiliki Akses |
| **Cetak Label Barcode & Stiker** | Cetak Stiker Satuan & Lembar A4 (2x7) | Cetak Label Stiker Box Gudang | Cetak Draft Stiker Sementara |
| **Import Massal Excel / CSV** | Import Seluruh Departemen | Verifikasi Box Hasil Import | Import Data Dokumen Departemennya |
| **Penarikan / Peminjaman Berkas** | Otorisasi Global & Pengembalian | Eksekusi Pengambilan Rak & Dispatch | Ajukan Peminjaman & Approval Dept |
| **Pemusnahan & Berita Acara (BAP)**| Otorisasi BAP Multi-Box & Arsip | Ambil Box Rak & Unggah Dokumen BAP | Tinjau Usulan H-30 & Approval Dept |
| **Perpanjangan Retensi Arsip** | Persetujuan Akhir Perpanjangan | Memperbarui Label Expired Baru | Ajukan Justifikasi Perpanjangan |
| **2D Interactive Warehouse Canvas**| Akses Penuh Builder & Konfigurasi Rak | Inspeksi Slot & Booking/Unbooking | Tidak Memiliki Akses |
| **Master Data (Dept, Format No, User)**| CRUD Penuh & Impersonasi Akun | Read Only Data Gudang | Read Only Kategori Arsip |
| **Pusat Laporan & Ekspor Data** | Cetak Laporan & Unduh CSV Global | Laporan Okupansi & Stok Fisik | Laporan Katalog Arsip Departemen |
| **Audit Trail & Maintenance DB** | Backup SQLite & Log Aktivitas | Tidak Memiliki Akses | Tidak Memiliki Akses |

---

## 3. Struktur Direktori Dokumentasi

Dokumentasi rinci disusun secara modular ke dalam folder masing-masing peran:

```
documentation/
├── README.md                                          # Master Panduan, Arsitektur & Matriks RBAC
├── super_admin/                                       # Dokumentasi Role Super Admin (Webdev / Head Administrator)
│   ├── 01_dashboard_and_health_telemetry.md          # Dashboard Global, Telemetri LAN Ping & Diagnostik
│   ├── 02_archive_lifecycle_and_status_override.md   # Tata Kelola Siklus Hidup Arsip & Status Override
│   ├── 03_warehouse_layout_2d_canvas.md              # Visual 2D Canvas Warehouse Builder & Slotting
│   ├── 04_borrowing_and_retrieval_oversight.md       # Pengawasan Penarikan Berkas & Pelacakan Keterlambatan
│   ├── 05_retention_destruction_and_extension.md     # Mesin JRA, Aturan FAT 10 Tahun & Multi-Box BAP
│   ├── 06_master_departments_and_categories.md       # Manajemen 29 Departemen, Sub-Dept & Kategori Arsip
│   ├── 07_dynamic_numbering_formats.md               # Format Penomoran Barcode Otomatis & Pola Dinamis
│   ├── 08_user_management_and_impersonation.md       # Manajemen Akun Pengguna & Fitur Login-As (Impersonasi)
│   ├── 09_mass_label_printing_and_barcode.md         # Pencetakan Label Stiker Barcode Lembar A4 (Format 2x7)
│   ├── 10_reports_and_document_exports.md            # Pusat Cetak Laporan (PDF), Filter & Ekspor CSV
│   └── 11_audit_trail_and_database_maintenance.md    # Log Audit Global, Backup SQLite & Hot Git Pull Restart
├── pic_gudang/                                        # Dokumentasi Role PIC Gudang (Warehouse Archive Specialist)
│   ├── 01_warehouse_operator_dashboard.md            # Dashboard Gudang, Antrean Verifikasi & Alert Box Masuk
│   ├── 02_physical_archive_verification_and_checkin.md# Verifikasi Fisik, Penempatan Slot Rak & Scan Barcode
│   ├── 03_warehouse_layout_and_slot_management.md    # Inspeksi Slotting Canvas, Booking & Toggle Aktif Slot
│   ├── 04_borrowing_fulfillment_and_dispatch.md      # Pengambilan Berkas Rak, Dispatch & Penerimaan Kembali
│   ├── 05_physical_destruction_execution.md          # Pengambilan Box Fisik, Pemusnahan & Unggah Bukti BAP
│   ├── 06_box_checkout_and_temporary_release.md      # Peminjaman Box Keluar Sementara (Checkout/Check-In)
│   └── 07_warehouse_occupancy_and_stock_reports.md   # Laporan Okupansi Rak Terisi/Kosong & Entry Logs
└── pic_dept/                                          # Dokumentasi Role PIC Departemen (Department Archive Owner)
    ├── 01_department_desktop_dashboard.md            # Workstation Desktop MDI, Navigasi Tab & Alert Expiry
    ├── 02_archive_creation_and_registration.md       # Form Pengajuan Box Baru, Lampiran Dokumen & Deteksi Duplikat
    ├── 03_retention_calculation_and_jra.md           # Perhitungan Retensi JRA Otomatis & Cetak Stiker Draft
    ├── 04_document_retrieval_and_borrowing.md        # Form Penarikan Berkas, Persetujuan Dept & Tracking Pinjam
    ├── 05_retention_extension_and_destruction.md     # Form Perpanjangan & Pemusnahan: H-30 Alert, Extend & BAP
    ├── 06_bulk_archive_excel_import.md               # Import Massal Excel/CSV Dokumen Departemen
    └── 07_department_archive_catalog.md              # Form Katalog Arsip Departemen & Drilldown Berkas Unit
```

---

## 4. Panduan Navigasi Cepat

Untuk mempelajari fungsi spesifik masing-masing peran, silakan buka dokumen terkait:
- [Dokumentasi Lengkap Super Admin](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/documentation/super_admin/01_dashboard_and_health_telemetry.md)
- [Dokumentasi Lengkap PIC Gudang](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/documentation/pic_gudang/01_warehouse_operator_dashboard.md)
- [Dokumentasi Lengkap PIC Departemen](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/documentation/pic_dept/01_department_desktop_dashboard.md)
