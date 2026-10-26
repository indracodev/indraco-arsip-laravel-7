# Modul Visual 2D Canvas Layout Gudang & Slotting (Super Admin)

## 1. Deskripsi Fitur
Modul Visual 2D Canvas Layout Gudang adalah instrumen grafis interaktif (*Interactive 2D Warehouse CAD/Canvas Builder*) untuk merancang, mengonfigurasi, dan memetakan denah fisik fasilitas gudang arsip PT Indraco (cth: Gudang Dokumen G.K 678).

Super Admin memiliki keleluasaan penuh untuk:
1. **Menggambar Denah Fasilitas:** Menempatkan objek rak penyimpanan (*racks*), dinding pembatas (*walls*), pintu masuk/keluar (*doors*), pilar penopang gedung (*pillars*), dan meja kerja inspeksi (*tables*).
2. **Standardisasi Rak & Slotting:** Setiap unit rak otomatis dihitung dan dibagi menjadi **5 Sap (Tingkat Vertikal) $\times$ 20 Slot Box per Sap = 100 Slot Box per Rak (Standar Box TB 30g)**.
3. **Alat Manajemen Massal (*Batch Operations*):**
   - *Batch Color Modal*: Memberi kode warna rak berdasarkan departemen pemilik (misal: Finance = Biru, HRD = Ungu, Marketing = Emas).
   - *Batch Resize & Move*: Menyesuaikan ukuran dan menggeser baris rak di atas grid kanvas.
   - *Booking / Unbooking Slot*: Mengalokasikan blok slot tertentu untuk persiapan kedatangan dokumen tahunan departemen.
   - *Assign / Unassign Box*: Menghubungkan nomor barcode box arsip ke koordinat slot kanvas secara visual.

---

## 2. Hak Akses & Role Permission
- **Role:** `admin` (Super Admin) dan `pic_gudang` (akses terbatas operasional).
- **Middleware:** `auth`, `role:admin,pic_gudang`
- **Wewenang Khusus Super Admin:** Mengubah ukuran kanvas, menambah/menghapus rak fisik, memindahkan posisi objek gedung, dan mengubah batasan konfigurasi sistem rak.

---

## 3. Komponen Teknis & Skema Database

### File Terkait:
- Controller: [WarehouseLayoutController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/WarehouseLayoutController.php)
- Model:
  - [Warehouse.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Warehouse.php)
  - [WarehouseLocation.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseLocation.php) (Data objek rak & posisi koordinat X, Y, Width, Height, Rotation)
  - [WarehouseRackSlot.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseRackSlot.php) (Data 100 slot individual per rak: `sap_number`, `slot_number`, `status`, `archive_id`)
- View: Direktori `resources/views/master/warehouse_layout/`
  - `partials/canvas_engine.blade.php`: Mesin rendering HTML5 Canvas / SVG dengan dukungan Zoom, Pan, dan Drag & Drop.
  - `partials/canvas_toolbar.blade.php`: Toolbar alat gambar (Cursor, Pan, Add Rack, Wall, Door).
  - `modals/batch_color_modal.blade.php`, `modals/rack_visualizer_modal.blade.php`, `modals/move_rack_modal.blade.php`.
- Route:
  - `GET /master/warehouses/layout` (`name('master.warehouses.layout')`)
  - `GET /api/warehouse/layout-data` (`name('api.warehouse.layout_data')`)
  - `POST /api/warehouse/locations/store` (`name('api.warehouse.locations.store')`)
  - `POST /api/warehouse/locations/{location}/slots/assign` (`name('api.warehouse.locations.slots.assign')`)

---

## 4. Flowchart Alur Kerja Perancangan Layout & Penempatan Slotting

```mermaid
flowchart TD
    Start([Super Admin Buka Menu Canvas Layout /master/warehouses/layout]) --> LoadLayoutAPI[Panggil GET /api/warehouse/layout-data]
    LoadLayoutAPI --> RenderCanvasEngine[Canvas Engine Render Denah Gedung, Garis Grid, & Objek Rak]
    
    RenderCanvasEngine --> SelectTool{Pilih Alat pada Toolbar Canvas:}
    
    %% Alur Tambah Rak Baru
    SelectTool -- 1. Tambah Rak Baru --> ClickAddRack[Klik Ikon 'Tambah Rak' & Tentukan Titik di Kanvas]
    ClickAddRack --> OpenRackModal[Buka Modal Tambah Rak: Nama Rak, Baris, Kode Lorong]
    OpenRackModal --> SubmitRack[POST ke /api/warehouse/locations/store]
    SubmitRack --> Generate100Slots[Sistem Otomatis Generate 5 Sap x 20 Slot = 100 Record WarehouseRackSlot]
    Generate100Slots --> RedrawCanvas[Gambar Objek Rak Baru di Kanvas dengan Status Kosong]

    %% Alur Visualizer & Slotting
    SelectTool -- 2. Visualizer & Slotting Rak --> ClickSpecificRack[Klik Dua Kali / Klik Kanan pada Objek Rak]
    ClickSpecificRack --> OpenRackVisualizerModal[Buka Modal Visualizer Rak 5 Sap x 20 Kotak Slot]
    OpenRackVisualizerModal --> InspectSlotGrid[Tinjau Grid Slot: Warna Hijau = Kosong, Biru = Terisi, Kuning = Booking]
    
    InspectSlotGrid --> SlotAction{Pilih Aksi Slot:}
    SlotAction -- Booking Blok Slot --> SubmitBook[Panggil API /locations/{id}/book: Tandai untuk Departemen Tertentu]
    SlotAction -- Masukkan Box Arsip --> SubmitAssignSlot[Panggil API /slots/assign: Kaitkan Barcode Box Arsip ke Slot]
    SlotAction -- Batalkan Slot --> SubmitUnassign[Panggil API /slots/unassign: Bebaskan Slot Menjadi Kosong Kembali]

    %% Alur Batch Color Coding
    SelectTool -- 3. Batch Color Departemen --> SelectMultiRacks[Pilih Beberapa Rak & Buka Modal Batch Color]
    SelectMultiRacks --> PickDeptColor[Pilih Warna Departemen Pemilik]
    PickDeptColor --> UpdateBatchColor[(Update Kolom color di Tabel warehouse_locations)]

    RedrawCanvas & SubmitBook & SubmitAssignSlot & SubmitUnassign & UpdateBatchColor --> End([Selesai / Denah Terbarui Real-time])
```

---

## 5. Rincian Antarmuka & Langkah Perancangan Layout

1. **Navigasi Kanvas:**
   - Gunakan scroll mouse untuk melakukan **Zoom In / Zoom Out**.
   - Tahan tombol spasi atau klik kanan untuk melakukan **Pan (Geser Tampilan Denah)**.
2. **Membuat Rak Baru:**
   - Klik ikon **Rak (+)** pada toolbar atas.
   - Klik pada area kosong kanvas.
   - Masukkan kode rak (contoh: `RAK-R2-A`) dan nomor lorong.
   - Sistem seketika membuat rak lengkap dengan 100 slot penyimpanan siap pakai.
3. **Pemberian Warna Departemen (*Color Coding*):**
   - Blok rak yang diperuntukkan bagi departemen tertentu (contoh: seluruh rak di lorong barat untuk *Accounting & Tax*).
   - Klik tombol **Batch Color** dan pilih warna kuning emas. Seluruh rak yang dipilih akan seragam berwarna emas untuk mempermudah identifikasi operator di lapangan.
