# Modul Cetak Massal Label Barcode Stiker (Super Admin)

## 1. Deskripsi Fitur
Modul Cetak Massal Label Barcode Stiker mengatur pembuatan label fisik berbarcode resolusi tinggi untuk ditempelkan pada fisik box arsip (Standar Box TB 30g). 

Sistem mendukung 2 mode pencetakan:
1. **Cetak Lembar Stiker Kertas A4 (Format Standar 2 Kolom $\times$ 7 Baris = 14 Label per Lembar):** Menggabungkan banyak box sekaligus ke dalam satu lembar cetak stiker tanpa ada bagian label yang terpotong margin printer.
2. **Cetak Satuan (*Single Sticker Preview*):** Mencetak satu label khusus untuk penggantian stiker rusak atau pencetakan pada printer label thermal (*Direct Thermal / Thermal Transfer*).
3. **Validasi Status Kearsipan:** Sesuai aturan kearsipan Indraco, arsip yang masih berstatus `draft` dilarang mencetak stiker barcode gudang resmi sebelum diverifikasi oleh petugas.

---

## 2. Hak Akses & Role Permission
- **Role:** `admin` (Super Admin) dan `pic_gudang` (staf pelaksana gudang).
- **Middleware:** `auth`
- **Spesifikasi Barcode:** Format barcode Code 128 standar industri logistik yang dapat dibaca oleh seluruh *handheld barcode scanner* nirkabel USB / Bluetooth.

---

## 3. Komponen Teknis & Skema Database

### File Terkait:
- Controller: [ArchiveLabelController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/ArchiveLabelController.php)
  - `printLabels()`: Memproses cetak massal lembar A4 (2x7).
  - `printSticker()`: Menampilkan preview stiker tunggal untuk satu box.
- View: `resources/views/archives/print_labels.blade.php`, `resources/views/archives/print_sticker.blade.php`, dan modal `resources/views/layouts/partials/label_print_modal.blade.php`
- Route:
  - `GET /archives/print-labels` (`name('archives.print_labels')`)
  - `POST /archives/print-labels` (`name('archives.print_labels_post')`)
  - `GET /archives/{archive}/print-sticker` (`name('archives.print_sticker')`)

### Data yang Tertera pada Label Stiker Box:
1. Logo Resmi PT Indraco Global Indonesia.
2. Nomor Barcode Box Resmi (cth: `INDRACO/TAX/2026/X/0012`).
3. Gambar Barcode Code128 / QR Code beresolusi tajam.
4. Nama Departemen & Sub-Departemen (cth: *Finance - Tax*).
5. Judul / Klasifikasi Berkas Dokumen.
6. Tahun Dokumen & Tanggal Jatuh Tempo Retensi (*Expiry Date*).
7. Koordinat Lokasi Fisik Rak Gudang (cth: `RAK-R1-A-SAP-2-SLOT-08`).

---

## 4. Flowchart Alur Pencetakan Label Massal Format A4 (2x7)

```mermaid
flowchart TD
    Start([Super Admin Buka Katalog Arsip /archives]) --> SelectArchives[Tandai Checklist Beberapa Box Arsip yang Akan Dicetak]
    SelectArchives --> ClickPrintBatchBtn[Klik Tombol 'Cetak Label Terpilih (A4 2x7)']
    
    ClickPrintBatchBtn --> SendPrintRequest[Kirim POST /archives/print-labels dengan Array archive_ids]
    SendPrintRequest --> FilterVerifiedArchives{Apakah Seluruh Arsip Terpilih Berstatus Lolos Verifikasi?}
    
    FilterVerifiedArchives -- Ada yang Masih Draft --> BlockDraft[Sistem Beri Peringatan: Arsip Draft Tidak Dapat Mencetak Label Resmi Gudang]
    BlockDraft --> ExcludeDrafts[Keluarkan Box Draft & Lanjutkan Box yang Sah]

    FilterVerifiedArchives -- Semua Sah --> ChunkIntoPages[Bagi Data Arsip Menjadi Kelipatan 14 Label per Lembar A4]
    ExcludeDrafts --> ChunkIntoPages

    ChunkIntoPages --> RenderPrintLayout[Render View resources/views/archives/print_labels.blade.php]
    RenderPrintLayout --> ApplyCSSPrint[Terapkan CSS @media print: Margin 0, Grid 2x7, Ukuran Label Presisi]
    
    ApplyCSSPrint --> OpenBrowserPrintDialog[Eksekusi Otomatis: window.print]
    OpenBrowserPrintDialog --> OutputPrinter[Printer Menghasilkan Lembar Stiker A4 Siap Tempel]
    OutputPrinter --> End([Selesai])
```

---

## 5. Rincian Antarmuka & Langkah Kerja Super Admin

1. Buka menu **Katalog Arsip**.
2. Centang kotak pilihan pada 14 atau 28 box yang siap dipasang label stiker fisik.
3. Klik tombol **Cetak Label (A4 2x7)** pada bilah aksi atas.
4. Jendela baru akan terbuka menyajikan lembar cetak yang sudah diformat khusus untuk kertas label stiker model Tom & Jerry / label sheet A4 2 kolom $\times$ 7 baris.
5. Pada dialog printer browser:
   - Pilih ukuran kertas **A4**.
   - Setel Margin ke **None / Minimum**.
   - Aktifkan opsi **Background Graphics** agar garis pembatas stiker tercetak jelas.
6. Klik **Print**. Tempelkan stiker pada sisi muka dan samping kardus box arsip.
