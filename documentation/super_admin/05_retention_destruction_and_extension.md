# Modul Retensi, Pemusnahan & Perpanjangan Arsip (Super Admin)

## 1. Deskripsi Fitur
Modul Retensi, Pemusnahan & Perpanjangan Arsip mengelola akhir masa pakai (*end-of-life disposal*) dari dokumen fisik PT Indraco sesuai kepatuhan hukum dan tata kelola kearsipan nasional.

Fokus Utama Fitur:
1. **Mesin Jadwal Retensi Arsip (JRA):** Menghitung tanggal kedaluwarsa secara otomatis berdasarkan tanggal dokumen ditambah masa retensi kategori master arsip.
2. **Penegakan Aturan FAT 10 Tahun (*FAT 10-Year Rule*):** Arsip yang termasuk divisi *Finance, Accounting, dan Tax* diwajibkan memiliki masa retensi minimal 10 tahun sesuai UU Dokumen Perusahaan dan Ketentuan Umum Perpajakan.
3. **Peringatan Otomatis H-30 Jatuh Tempo:** Menyaring seluruh berkas yang berada pada rentang $\le$ 30 hari sebelum tanggal expired.
4. **Penerbitan Multi-Box BAP (Berita Acara Pemusnahan):** Menggabungkan banyak box arsip sekaligus ke dalam satu lembar dokumen Berita Acara resmi berkop surat Indraco lengkap dengan nomor register dan daftar saksi.
5. **Eksekusi Pemusnahan Fisik & Pembebasan Slot Rak:** Mengesahkan pemusnahan (pencacahan / peleburan kimia), mengunggah scan BAP bertandatangan direksi, dan secara otomatis membebaskan slot rak gudang (*auto-free slot*) agar bisa diisi box baru.
6. **Alur Perpanjangan Retensi (*Retention Extension*):** Memproses usulan perpanjangan masa simpan bagi dokumen yang masih dibutuhkan untuk proses sengketa hukum atau audit pajak lanjutan.

---

## 2. Hak Akses & Role Permission
- **Role:** `admin` (Super Admin)
- **Middleware:** `auth`, `role:admin`
- **Wewenang Khusus:** Menandatangani persetujuan akhir BAP pemusnahan dan persetujuan perpanjangan masa retensi.

---

## 3. Komponen Teknis & Skema Database

### File Terkait:
- Controller: [DestructionController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/DestructionController.php)
  - `index()`: Antarmuka terpadu 3 Sub-Tabs (H-30 Jatuh Tempo, Riwayat Perpanjangan, Riwayat Pemusnahan).
  - `propose()`: Mengajukan usulan pemusnahan satu atau banyak box.
  - `showBap()`: Menampilkan cetak Berita Acara Pemusnahan (BAP) formal.
  - `approve()` & `complete()`: Menyetujui dan menyelesaikan eksekusi fisik.
  - `uploadBap()`: Mengunggah file PDF BAP yang telah ditandatangani saksi.
  - `extendStore()` & `approveExtension()`: Mengajukan dan menyetujui perpanjangan masa simpan.
- Model:
  - [DestructionLog.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/DestructionLog.php)
  - [ArchiveExtensionLog.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/ArchiveExtensionLog.php)
- View: `resources/views/destructions/index.blade.php`, `resources/views/destructions/bap.blade.php`, `resources/views/destructions/partials/`
- Route:
  - `GET /destructions` (`name('destructions.index')`)
  - `GET /destructions/bap/{destructionLog}` (`name('destructions.bap')`)
  - `POST /destructions/{destructionLog}/approve` (`name('destructions.approve')`)
  - `POST /destructions/{destructionLog}/complete` (`name('destructions.complete')`)
  - `POST /destructions/{destructionLog}/upload-bap` (`name('destructions.upload_bap')`)
  - `POST /destructions/extensions/{extensionLog}/approve` (`name('destructions.extensions.approve')`)

---

## 4. Flowchart Alur Kerja Pemusnahan & Perpanjangan Retensi

```mermaid
flowchart TD
    Start([Sistem Menghitung Tanggal Jatuh Tempo Arsip H-30]) --> ExpiringList[Arsip Muncul di Tab: 'Berkas Jatuh Tempo (H-30)']
    
    ExpiringList --> ReviewDecision{Keputusan Kepala Departemen & Super Admin:}
    
    %% Alur 1: Perpanjangan Retensi
    ReviewDecision -- Dokumen Masih Dibutuhkan (Perpanjangan) --> ProposeExtension[Klik Tombol 'Perpanjangan' & Masukkan Justifikasi Bisnis]
    ProposeExtension --> InputExtensionYears[Tentukan Tambahan Masa Simpan cth: +2 Tahun]
    InputExtensionYears --> SubmitExtend[Kirim POST ke /destructions/extend]
    SubmitExtend --> CreateExtendLog[(Simpan ke archive_extension_logs status = 'pending')]
    
    CreateExtendLog --> SuperAdminApproveExtend{Super Admin Review Usulan Perpanjangan}
    SuperAdminApproveExtend -- Ditolak --> RejectExtend[Status = 'rejected' & Arsip Tetap pada Tanggal Asal]
    SuperAdminApproveExtend -- Disetujui --> ApproveExtend[Status = 'approved' & Update expired_date di Tabel archives]
    ApproveExtend --> PrintNewSticker[Cetak Label Stiker Baru dengan Tanggal Expired Diperbarui]

    %% Alur 2: Pemusnahan Berkas
    ReviewDecision -- Dokumen Boleh Dimusnahkan --> SelectBoxesForDestruction[Pilih Satu / Banyak Box yang Siap Dimusnahkan]
    SelectBoxesForDestruction --> SubmitProposeDestruction[Kirim POST ke /destructions/propose]
    SubmitProposeDestruction --> GenerateBAPNumber[Generate Nomor BAP Resmi & Simpan ke destruction_logs]
    
    GenerateBAPNumber --> PrintBAPDocument[Cetak Dokumen Fisik BAP /destructions/bap/{id}]
    PrintBAPDocument --> WitnessSigning[Penandatanganan Dokumen Fisik oleh Saksi, Legal & Super Admin]
    
    WitnessSigning --> PhysicalDestruction[Eksekusi Fisik: Pencacahan / Peleburan Berkas oleh Tim Gudang]
    PhysicalDestruction --> CompleteDestructionAPI[Super Admin / Gudang Klik 'Konfirmasi Pemusnahan Fisik Selesai']
    
    CompleteDestructionAPI --> UpdateArchiveDestroyed[Update Status Box: status = 'destroyed']
    UpdateArchiveDestroyed --> AutoFreeWarehouseSlot[Sistem Otomatis Lepaskan Box dari Slot Rak (Slot Menjadi Kosong Kembali)]
    AutoFreeWarehouseSlot --> UploadBAPScan[Unggah Scan PDF BAP Resmi Bertandatangan via /upload-bap]
    UploadBAPScan --> ArchiveHistoricRecord[Catat Riwayat Pemusnahan Permanen di Tab 3 Riwayat Pemusnahan]
    
    PrintNewSticker & ArchiveHistoricRecord --> End([Selesai])
```

---

## 5. Rincian Antarmuka & Langkah Kerja Super Admin

1. Buka menu **Pengajuan Perpanjangan & Pemusnahan** (`/destructions`).
2. **Sub-Tab 1: Berkas Jatuh Tempo (H-30):**
   - Menampilkan daftar arsip yang telah mencapai masa akhir simpan.
   - Jika dokumen masih diperlukan untuk audit pajak, klik tombol **Perpanjangan**.
   - Jika dokumen telah aman untuk dihancurkan, pilih kotak centang pada box tersebut dan klik **Ajukan Pemusnahan Multi-Box**.
3. **Penerbitan Berita Acara Pemusnahan (BAP):**
   - Klik nomor BAP yang dihasilkan (misal: `BAP/2026/X/0002`).
   - Halaman cetak formal BAP akan terbuka dengan tata letak kop surat resmi, tabel inventaris box, serta kolom tanda tangan:
     1. Pihak yang Menyerahkan (Kepala Departemen).
     2. Pihak yang Memusnahkan (PIC Gudang).
     3. Saksi Hukum & Pemeriksa (Super Admin / Legal Indraco).
4. **Penyelesaian & Upload Scan:**
   - Setelah berkas dicacah, klik tombol **Selesaikan Pemusnahan**.
   - Unggah scan dokumen BAP bertandatangan. Status arsip berubah menjadi `destroyed` dan slot rak fisik otomatis kosong kembali!
