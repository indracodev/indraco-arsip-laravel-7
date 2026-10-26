# Modul Dashboard Operasional Gudang (PIC Gudang)

## 1. Deskripsi Fitur
Modul Dashboard Operasional Gudang dirancang khusus untuk memfasilitasi tugas harian staf spesialis arsip gudang (*Warehouse Archive Specialist*). Antarmuka memprioritaskan tindakan cepat (*actionable operations*):
1. **Antrean Verifikasi Box Masuk (*Pending Verification Counter*):** Menampilkan jumlah box yang baru diajukan oleh PIC Departemen dan sedang menunggu kedatangan fisik di gudang.
2. **Indikator Keterisian Rak Fisik (*Live Rack Occupancy*):** Memantau utilisasi ruang simpan (jumlah slot terisi vs slot kosong) untuk memandu penempatan box baru.
3. **Pintasan Cepat Operasional (*Quick Action Shortcuts*):** Tombol akses instan untuk verifikasi fisik, penugasan slot rak cepat (*quick slotting*), pemindaian barcode penerimaan, dan serah terima peminjaman berkas.
4. **Pemberitahuan Waktu Nyata (*Realtime Check Ingest Alerts*):** Notifikasi otomatis saat departemen menerbitkan draft box baru tanpa perlu me-refresh browser.

---

## 2. Hak Akses & Role Permission
- **Role:** `pic_gudang` (Warehouse Archive Specialist)
- **Middleware:** `auth`, `role:admin,pic_gudang`
- **Fokus Kerja:** Menjamin akurasi fisik penempatan box di rak, kecepatan pelayanan peminjaman berkas, dan ketertiban administrasi penerimaan.

---

## 3. Komponen Teknis & Skema Database

### File Terkait:
- Controller: [DashboardController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/DashboardController.php) (Metode `index()`, `realtimeCheck()`)
- View: `resources/views/dashboard/index.blade.php`, `resources/views/dashboard/partials/dashboard_scripts.blade.php`
- Route:
  - `GET /` atau `GET /dashboard` (`name('dashboard')`)
  - `GET /api/realtime/check-new-archives` (`name('api.realtime.check')`)

---

## 4. Flowchart Alur Kerja Harian PIC Gudang

```mermaid
flowchart TD
    Start([PIC Gudang Membuka Dashboard Gudang /dashboard]) --> CheckPendingIngest[Sistem Tampilkan Angka: 'Pending Verifikasi Fisik']
    
    CheckPendingIngest --> StartRealtimePolling[Jalankan Polling Realtime /api/realtime/check-new-archives Setiap 15 Detik]
    StartRealtimePolling --> DetectNewBox{Apakah Ada Box Baru Dikirim Departemen?}
    DetectNewBox -- Ya --> PlayChime[Bunyikan Audio Lonceng & Munculkan Notifikasi Pop-up Box Masuk]
    DetectNewBox -- Tidak --> Standby[Standby di Meja Kerja Gudang]

    PlayChime --> PhysicalArrival[Kurir / Staf Departemen Menyerahkan Fisik Box ke Gudang]
    PhysicalArrival --> SelectWorkflow{Pilih Tindakan Kerja:}
    
    SelectWorkflow -- 1. Verifikasi Box Masuk --> OpenVerifyFlow[Buka Form Verifikasi & Alokasikan Slot Rak /archives/{id}/verify]
    SelectWorkflow -- 2. Layani Peminjaman Berkas --> OpenDispatchFlow[Buka Menu Penarikan & Ambil Berkas dari Rak /borrowings]
    SelectWorkflow -- 3. Terima Pengembalian Berkas --> OpenReturnFlow[Verifikasi Kelengkapan & Masukkan Kembali ke Slot Rak]
    SelectWorkflow -- 4. Cek Denah & Slot Kosong --> OpenCanvasLayout[Buka 2D Canvas Layout Gudang /master/warehouses/layout]

    OpenVerifyFlow & OpenDispatchFlow & OpenReturnFlow & OpenCanvasLayout --> UpdateDashboardMetrics[Metrik Dashboard Terbarui Real-time]
    UpdateDashboardMetrics --> End([Siap Melayani Tugas Berikutnya])
```

---

## 5. Rincian Antarmuka & Langkah Kerja PIC Gudang

1. Masuk ke aplikasi menggunakan akun peran **PIC Gudang** (contoh: `gudang@indraco.com`).
2. **Memeriksa Box yang Akan Masuk:**
   - Perhatikan kartu **Menunggu Verifikasi Fisik**. Klik kartu tersebut untuk melihat daftar berkas yang sedang dikirim oleh departemen.
3. **Menggunakan Tombol Pintasan Cepat:**
   - Gunakan tombol pintasan **Verifikasi Box Baru (F2)** untuk langsung membuka modal verifikasi dan menghubungkan barcode scanner USB.
   - Gunakan tombol **Cek Slot Kosong** untuk melihat sap rak mana yang masih memiliki ruang luang 20 slot box.
