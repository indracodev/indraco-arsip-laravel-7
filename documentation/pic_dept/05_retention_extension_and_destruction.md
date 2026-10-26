# Dokumentasi Fitur: Pengajuan Perpanjangan Retensi & Pemusnahan (Retention Extension & Destruction Request)

Dokumen ini menjelaskan alur operasional PIC Departemen pada **Form 3: Pengajuan Perpanjangan & Pemusnahan** (`/destructions?embed=1`), yang memiliki 3 sub-tab fungsional: **H-30 Expiry Alert**, **Riwayat Perpanjangan Retensi**, dan **Riwayat Pemusnahan Dokumen**.

---

## 1. Ringkasan Fitur
- **Role Pengguna**: `pic_dept` (PIC Departemen)
- **Menu Akses**: `Workstation Form 3` > `Pengajuan Perpanjangan & Pemusnahan` (`/destructions?embed=1` atau Hotkey **F4**)
- **Tujuan**:
  1. Memantau box arsip milik departemen yang akan kedaluwarsa dalam 30 hari ke depan (*H-30 Alert*).
  2. Memberikan wewenang kepada pemilik arsip (departemen) untuk menentukan apakah box arsip:
     - **Disetujui untuk Dimusnahkan** (*Agreed for Destruction*).
     - **Diajukan Perpanjangan Retensi** (*Request Extension*) jika dokumen masih memiliki nilai guna legal/operasional.
  3. Memasukkan alasan dan jumlah tahun perpanjangan retensi tambahan (misal: diperpanjang +2 atau +5 tahun).
  4. Mengunggah surat rekomendasi perpanjangan jika disyaratkan.
  5. Menelusuri seluruh riwayat perpanjangan dan pemusnahan yang pernah dilakukan beserta dokumen BAP digitalnya.

---

## 2. Alur Kerja (Flowchart Pengajuan Perpanjangan vs Pemusnahan)

```mermaid
flowchart TD
    Start([PIC Dept Membuka Form 3 di Workstation]) --> ViewSubTabs{Pilih Sub-Tab Form 3}
    
    ViewSubTabs -- Sub-Tab 1: Peringatan H-30 --> ListExpiring[Daftar Box Mendekati Jatuh Tempo Retensi]
    ViewSubTabs -- Sub-Tab 2: Riwayat Perpanjangan --> ViewExtensionHistory[Lihat Daftar Arsip yang Diperpanjang & Justifikasinya]
    ViewSubTabs -- Sub-Tab 3: Riwayat Pemusnahan --> ViewDestructionHistory[Lihat Daftar Arsip yang Telah Dimusnahkan & Download BAP]
    
    ListExpiring --> ReviewBox[PIC Dept Melakukan Telaah Nilai Guna Dokumen]
    ReviewBox --> Decision{Keputusan Departemen?}
    
    Decision -- Setujui Pemusnahan --> ConfirmDestruction[Klik Setujui Pemusnahan:
      - Konfirmasi bahwa dokumen sudah tidak diperlukan
      - Status Arsip = 'ready_for_destruction'
      - Tiket masuk ke Komite Pemusnahan & Super Admin]
      
    Decision -- Ajukan Perpanjangan Retensi --> OpenExtensionModal[Buka Form Pengajuan Perpanjangan:
      - Pilih Tambahan Tahun (e.g. +2, +3, +5 Tahun)
      - Tulis Alasan / Justifikasi Bisnis/Hukum
      - Upload Dokumen Persetujuan Internal Dept]
      
    OpenExtensionModal --> SubmitExtension[Submit Permohonan Perpanjangan]
    SubmitExtension --> AdminReview[Menunggu Review Super Admin & Legal]
    
    AdminReview --> AdminDecision{Keputusan Super Admin?}
    AdminDecision -- Ditolak --> ExtensionRejected[Status = 'extension_rejected': Tetap Ikuti Jadwal Pemusnahan]
    AdminDecision -- Disetujui --> ExtensionApproved[Status = 'extended':
      - Hitung ulang expiry_year = expiry_year + extension_years
      - Pindahkan ke Sub-Tab Riwayat Perpanjangan
      - Kirim notifikasi konfirmasi ke PIC Dept]
      
    ConfirmDestruction --> MultiBoxBAP[Super Admin Bundel ke BAP Pemusnahan Multi-Box]
    MultiBoxBAP --> DestructionFinished[Eksekusi Fisik Gudang Selesai:
      - Box berstatus 'destroyed'
      - Muncul di Sub-Tab Riwayat Pemusnahan beserta PDF BAP]
      
    ExtensionApproved --> Finish([Selesai])
    DestructionFinished --> Finish
```

---

## 3. Komponen Teknis (Backend & Database)

### 3.1 Controller & Routing
- **Controller**: [DestructionController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/DestructionController.php) atau [ArchiveExtensionController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/ArchiveExtensionController.php)
- **Route Endpoint**:
  - `GET /destructions`: Halaman utama Form 3 dengan 3 sub-tab (`?embed=1`).
  - `POST /destructions/{archive}/approve`: Menyetujui box dimusnahkan oleh departemen.
  - `POST /destructions/{archive}/extend`: Mengajukan perpanjangan masa simpan arsip.
  - `GET /destructions/history/extensions`: Riwayat perpanjangan masa retensi.
  - `GET /destructions/history/destroyed`: Riwayat box yang telah dimusnahkan.

### 3.2 Eloquent Models Terkait
- [Archive.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Archive.php)
  - Atribut: `expiry_year`, `extension_count`, `retention_status`.
- [ArchiveExtension.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/ArchiveExtension.php)
  - Atribut: `archive_id`, `requested_years`, `reason`, `status` (`pending`, `approved`, `rejected`), `approved_by`, `attachment_path`.
- [DestructionBatch.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/DestructionBatch.php)

---

## 4. Langkah Operasional PIC Departemen

1. **Buka Formulir**:
   - Di workstation MDI, tekan **F4** atau klik tab **Pengajuan Perpanjangan & Pemusnahan**.
2. **Periksa Sub-Tab 1 (Peringatan H-30)**:
   - Amati daftar box departemen Anda yang masa retensinya akan berakhir tahun ini.
   - Klik baris box untuk melihat daftar berkas ordner di dalamnya.
3. **Mengajukan Perpanjangan (Jika Berkas Masih Dibutuhkan)**:
   - Klik tombol **Ajukan Perpanjangan**.
   - Masukkan durasi penambahan (misal: *3 Tahun*).
   - Masukkan alasan (misal: *Perkara sengketa tanah dengan pihak ketiga masih berjalan di tingkat kasasi MA*).
   - Unggah surat rekomendasi Legal, lalu klik **Kirim Pengajuan**.
4. **Menyetujui Pemusnahan (Jika Berkas Sudah Tidak Memiliki Nilai Guna)**:
   - Klik tombol hijau **Setujui Pemusnahan**.
   - Berkas akan masuk ke daftar usulan BAP Komite Pemusnahan Super Admin.
5. **Memeriksa Riwayat & Download BAP**:
   - Klik **Sub-Tab 3 (Riwayat Pemusnahan)** untuk mengunduh salinan Berita Acara Pemusnahan (BAP) bertanda tangan resmi bila sewaktu-waktu dilakukan audit kepatuhan ISO/SOP.
