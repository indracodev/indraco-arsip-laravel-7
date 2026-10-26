# Dokumentasi Fitur: Visualisasi Layout Gudang & Manajemen Slot Rak

Dokumen ini menjelaskan penggunaan visualisasi peta gudang 2D Canvas oleh PIC Gudang untuk memantau kapasitas rak secara real-time, menata penempatan box arsip, mengubah status ketersediaan slot (booking/maintenance), dan mengoptimalkan densitas penyimpanan.

---

## 1. Ringkasan Fitur
- **Role Pengguna**: `pic_gudang` (Warehouse Operator / Staff Gudang)
- **Menu Akses**: `Menu Utama` > `Layout Gudang & Rak` (`/warehouse/layout`)
- **Tujuan**:
  1. Menampilkan denah lorong gudang arsip dan baris rak (Blok A, B, C, dst.) secara grafis interaktif.
  2. Memberikan visualisasi matriks slot rak dengan standar **5 Sap (Tingkat) $\times$ 20 Box = 100 Slot per Rak**.
  3. Menampilkan status slot berdasarkan kode warna:
     - 🟢 **Hijau (Available)**: Slot kosong dan siap dialokasikan.
     - 🔴 **Merah (Occupied)**: Slot telah terisi box arsip aktif.
     - 🟡 **Kuning (Booked/Reserved)**: Slot dipesan untuk alokasi pengajuan batch yang sedang diverifikasi.
     - ⚪ **Abu-abu (Inactive/Maintenance)**: Slot rusak atau tidak dapat digunakan.
  4. Menyediakan fitur *hover info* dan *click detail* untuk melihat nomor box, judul arsip, departemen pemilik, dan tanggal jatuh tempo retensi.

---

## 2. Alur Kerja (Flowchart Manajemen Slot & Inspeksi Rak)

```mermaid
flowchart TD
    Start([Akses Modul Layout Gudang]) --> SelectWarehouse[Pilih Gudang / Lantai / Zona]
    SelectWarehouse --> RenderFloorMap[Render Denah 2D Lorong & Baris Rak]
    
    RenderFloorMap --> ClickRack[Pilih Satu Baris Rak, misal: Rak B-02]
    ClickRack --> RenderMatrix[Render Matriks Grid 5 Sap x 20 Kolom = 100 Slot]
    
    RenderMatrix --> UserAction{Pilihan Tindakan Operator?}
    
    UserAction -- Klik Slot Terisi (Merah) --> ShowBoxDetail[Tampilkan Popover/Modal Detail Arsip:
      - Nomor Box & Barcode
      - Departemen & Sub-Dept
      - Judul Berkas & Jumlah Item
      - Masa Retensi (Tahun Jatuh Tempo)
      - Tombol Relokasi Box]
      
    UserAction -- Klik Slot Kosong (Hijau) --> SlotAction{Aksi Slot Kosong?}
    SlotAction -- Manual Assign Box --> AssignBoxModal[Pilih Box Menunggu Slot & Konfirmasi Simpan]
    SlotAction -- Set Maintenance --> ToggleSlotStatus[Ubah Status Menjadi Inactive/Rusak]
    
    UserAction -- Relokasi Box --> SelectTargetSlot[Pilih Slot Tujuan Kosong]
    SelectTargetSlot --> ConfirmRelocation[Konfirmasi Pemindahan Fisik & Update Database]
    
    AssignBoxModal --> UpdateDB[Update WarehouseSlot & Archive]
    ToggleSlotStatus --> UpdateDB
    ConfirmRelocation --> UpdateDB
    
    UpdateDB --> RefreshCanvas[Refresh Tampilan Grid Slot Rak]
    RefreshCanvas --> Done([Selesai])
```

---

## 3. Komponen Teknis (Backend & Database)

### 3.1 Controller & Routing
- **Controller**: [WarehouseLayoutController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/Warehouse/WarehouseLayoutController.php) atau [WarehouseRackController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/WarehouseRackController.php)
- **Route Endpoint**:
  - `GET /warehouse/layout`: Antarmuka utama denah visual gudang.
  - `GET /warehouse/layout/racks/{rack}/matrix`: JSON endpoint untuk mendapatkan status 100 slot pada satu rak.
  - `POST /warehouse/layout/slots/{slot}/toggle-status`: Mengubah status slot (aktif / perbaikan).
  - `POST /warehouse/layout/relocate`: Memindahkan box dari slot sumber ke slot target.

### 3.2 Eloquent Models Terkait
- [WarehouseRack.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseRack.php)
  - Atribut: `rack_code`, `zone`, `total_levels` (5 sap), `slots_per_level` (20), `status`.
- [WarehouseSlot.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseSlot.php)
  - Atribut: `rack_id`, `sap_number` (1-5), `box_number` (1-20), `is_occupied`, `is_active`, `archive_id`.
- [Archive.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Archive.php)
  - Relasi: `slot()`, `rack()`, `department()`.

---

## 4. Langkah Operasional PIC Gudang

1. **Navigasi ke Rak Target**:
   - Buka menu **Layout Gudang & Rak**.
   - Klik rak fisik yang akan diinspeksi dari denah denah 2D.
2. **Inspeksi Slot**:
   - Sistem membuka drawer matriks 100 slot rak.
   - Amati persebaran box arsip di tiap sap untuk memastikan beban berat rak merata (sap bawah untuk arsip berat / tebal).
3. **Pemeriksaan Isi Box**:
   - Klik salah satu slot merah untuk memeriksa identitas box yang tersimpan.
   - Periksa apakah ada box yang telah melewati masa retensi (ikon peringatan kuning/merah).
4. **Relokasi Box (Jika Diperlukan Penataan Ulang)**:
   - Klik opsi **Pindahkan Box (Relocate)**.
   - Pilih rak dan slot target yang masih kosong.
   - Pindahkan fisik kardus box di rak gudang fisik sesuai nomor sap/kolom baru, lalu tekan **Simpan Relokasi**.
