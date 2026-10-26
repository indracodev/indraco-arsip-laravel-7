# Dokumentasi Fitur: Kalkulasi Retensi Otomatis & Jadwal Retensi Arsip (JRA)

Dokumen ini menjelaskan mekanisme perhitungan masa simpan (retensi) dokumen secara otomatis pada saat pendaftaran box, aturan ketat retensi minimal 10 tahun untuk departemen FAT (Finance, Accounting, Tax), dan pencetakan stiker barcode draft box.

---

## 1. Ringkasan Fitur
- **Role Pengguna**: `pic_dept` (PIC Departemen)
- **Menu Akses**: Terintegrasi pada `Form 4: Draft Pengajuan Box Baru` (`/archives/create?embed=1`)
- **Tujuan**:
  1. Menghitung tahun kedaluwarsa arsip secara otomatis berdasarkan parameter Jadwal Retensi Arsip (JRA) Master Kategori.
  2. Menerapkan **FAT 10-Year Rule**: Setiap arsip dari departemen Keuangan (*Finance*), Akuntansi (*Accounting*), dan Pajak (*Tax*) secara hukum perpajakan dan audit wajib memiliki masa simpan minimum 10 tahun terhitung dari tahun akhir dokumen (`year_end + 10`).
  3. Mencegah kesalahan input manual oleh staf departemen yang dapat menyebabkan pemusnahan prematur dokumen legal/finansial penting.
  4. Menghasilkan kode barcode dan stiker draft box yang memuat informasi tanggal retensi untuk memudahkan kurir gudang.

---

## 2. Alur Kerja (Flowchart Kalkulasi Retensi & Cetak Stiker Draft)

```mermaid
flowchart TD
    Start([User Mengisi Form Draft Box Baru]) --> InputYears[Input Tahun Awal & Tahun Akhir Dokumen]
    InputYears --> SelectCat[Pilih Kategori Arsip Master]
    
    SelectCat --> CheckDept{Apakah Departemen Masuk Kategori FAT?
      - Finance
      - Accounting
      - Tax}
      
    CheckDept -- Ya (FAT Rule Aktif) --> ApplyFATRule[Terapkan FAT 10-Year Rule:
      - Retention Years = MAX(Category Retention, 10 Tahun)
      - Expiry Year = Year End + Retention Years
      - Kunci Input Field Retensi (Readonly)]
      
    CheckDept -- Tidak (Departemen Lain) --> ApplyStandardJRA[Terapkan JRA Master Kategori:
      - Retention Years = Category Standard Years (e.g. 5 Tahun)
      - Expiry Year = Year End + Category Standard Years
      - Opsi Tambahan Perpanjangan Manual jika Diperlukan]
      
    ApplyFATRule --> DisplayRetentionSummary[Tampilkan Ringkasan Retensi di Layar:
      - Masa Simpan: X Tahun
      - Tahun Kedaluwarsa: YYYY
      - Status Jadwal: Aktif]
      
    ApplyStandardJRA --> DisplayRetentionSummary
    
    DisplayRetentionSummary --> SaveBox[Simpan Box ke Database]
    SaveBox --> GenerateDraftBarcode[Sistem Generate Draft Barcode:
      - Format: DRAFT-DEPT-YYYYMM-XXXX
      - Format Code128 / QR Code]
      
    GenerateDraftBarcode --> PrintDraftSticker[Cetak Stiker Draft (Thermal / A4):
      - Barcode & Nomor Box Sementara
      - Departemen & Sub-Departemen
      - Judul Box
      - Rentang Tahun Berkas
      - Tahun Jatuh Tempo Retensi]
      
    PrintDraftSticker --> Finish([Selesai])
```

---

## 3. Komponen Teknis (Backend & Logika Perhitungan)

### 3.1 Controller & Helper
- **File Implementasi**: [ArchiveController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/ArchiveController.php)
- **Logika Perhitungan PHP**:
  ```php
  $isFATDepartment = in_array(strtoupper($department->code), ['FIN', 'ACC', 'TAX', 'FAT']);
  
  $categoryRetention = $category->retention_years ?? 5;
  
  if ($isFATDepartment) {
      // Aturan wajib 10 tahun untuk Finance, Accounting, Tax
      $retentionYears = max($categoryRetention, 10);
  } else {
      $retentionYears = $categoryRetention;
  }
  
  $yearEnd = (int) $request->input('year_end');
  $expiryYear = $yearEnd + $retentionYears;
  ```

### 3.2 Eloquent Models Terkait
- [ArchiveCategory.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/ArchiveCategory.php)
  - Atribut: `name`, `code`, `retention_years`, `description`.
- [Department.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Department.php)
  - Atribut: `code`, `name`, `is_fat`.
- [Archive.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Archive.php)

---

## 4. Langkah Operasional PIC Departemen

1. **Memasukkan Rentang Tahun Berkas**:
   - Isi field **Tahun Akhir Dokumen** (misalnya tahun berkas: `2023`).
2. **Memilih Kategori Dokumen**:
   - Pilih jenis berkas, misalnya *SPT Masa PPN*.
3. **Melihat Hasil Kalkulasi**:
   - Jika akun Anda terdaftar di divisi Finance / Tax, sistem otomatis mengunci nilai retensi menjadi **10 Tahun** dengan keterangan badge *Wajib Retensi FAT 10 Tahun*.
   - Tahun kedaluwarsa otomatis terhitung menjadi: `2023 + 10 = 2033`.
4. **Cetak Stiker Draft**:
   - Setelah formulir disimpan, jendela pop-up stiker draft akan muncul.
   - Klik **Cetak Stiker**, pilih printer barcode/thermal, dan tempelkan stiker pada box sebelum diserahkan ke bagian gudang.
