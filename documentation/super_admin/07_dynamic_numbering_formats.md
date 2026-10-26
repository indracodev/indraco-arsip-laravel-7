# Modul Format Penomoran Dinamis Barcode Box (Super Admin)

## 1. Deskripsi Fitur
Modul Format Penomoran Dinamis memberikan fleksibilitas kepada Super Admin untuk merancang pola kode barcode dan label identifikasi box arsip secara otomatis (*Dynamic Pattern Numbering Generator*). 

Format penomoran ini memastikan setiap box yang dicetak memiliki identitas tunggal, mudah dilacak, dan memuat informasi asal perusahaan, divisi pemilik, tahun penciptaan, serta angka urut digital tanpa risiko kode duplikat.

---

## 2. Hak Akses & Role Permission
- **Role:** `admin` (Super Admin)
- **Middleware:** `auth`, `role:admin`
- **Fungsi Utama:** Mencegah terjadinya tabrakan nomor identitas barcode (*barcode collision*) lintas divisi.

---

## 3. Komponen Teknis & Skema Database

### File Terkait:
- Controller: [NumberingFormatController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/NumberingFormatController.php)
- Model: [NumberingFormat.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/NumberingFormat.php)
- View: `resources/views/master/numbering/index.blade.php`
- Route:
  - `GET /master/numbering` (`name('master.numbering')`)
  - `POST /master/numbering` (`name('master.numbering.store')`)
  - `PUT /master/numbering/{numberingFormat}` (`name('master.numbering.update')`)

### Variabel Pola yang Didukung (*Token Placeholders*):
| Token Placeholder | Penjelasan & Contoh Nilai |
| :--- | :--- |
| `{COMPANY}` | Inisial entitas perusahaan (cth: `INDRACO` / `ARC`) |
| `{DEPT}` | Kode resmi departemen pemilik (cth: `FIN`, `HRD`, `MKT`) |
| `{YEAR}` | 4 digit tahun kalender berjalan (cth: `2026`) |
| `{ROMAN_MONTH}` | Angka bulan dalam format Romawi (cth: `I`, `II`, ..., `X`, `XII`) |
| `{COUNTER}` | Angka urut ber-padding (cth: padding 4 = `0001`, `0002`) |

### Contoh Hasil Pola Standar:
$$\text{Pola: } \{COMPANY\}/\{DEPT\}/\{YEAR\}/\{ROMAN\_MONTH\}/\{COUNTER\}$$
$$\text{Output Barcode: } \mathbf{INDRACO/FIN/2026/X/0001}$$

---

## 4. Flowchart Alur Pembuatan & Eksekusi Generator Penomoran

```mermaid
flowchart TD
    Start([Super Admin Buka Menu Format Penomoran /master/numbering]) --> ViewCurrentFormat[Tampilkan Format Penomoran Aktif & Pratinjau Output]
    
    ViewCurrentFormat --> EditAction[Super Admin Mengubah Pola Template]
    EditAction --> DefineTokens[Kombinasikan Token: {COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}]
    DefineTokens --> SetPadding[Tentukan Jumlah Digit Counter cth: Padding = 4]
    
    SetPadding --> SavePatternDB[(Simpan ke Tabel numbering_formats dengan is_active = true)]
    SavePatternDB --> PatternReady[Pola Siap Digunakan Secara Global]

    %% Alur saat Pengguna Membuat Box Baru
    subgraph ArchiveCreationRuntime [Eksekusi Penomoran Otomatis saat Registrasi Box]
        UserSubmitsArchive[PIC Dept / Admin Menyimpan Pengajuan Box Baru] --> LockCounter[Lock Record NumberingFormat FOR UPDATE]
        LockCounter --> ReadNextCounter[Ambil current_counter & Increment + 1]
        ReadNextCounter --> SubstituteTokens[Substitusi Token: Masukkan Nama Dept, Tahun, Bulan Romawi, & Counter Terpadding]
        SubstituteTokens --> ResultingBarcode[Hasilkan String Final: INDRACO/MKT/2026/X/0045]
        ResultingBarcode --> SaveArchiveBarcode[(Simpan Barcode ke Tabel archives kolom barcode_number)]
        SaveArchiveBarcode --> ReleaseLock[Commit Transaksi Database]
    end

    PatternReady --> UserSubmitsArchive
    ReleaseLock --> End([Box Memiliki Nomor Barcode Resmi Unik])
```

---

## 5. Rincian Antarmuka & Langkah Kerja Super Admin

1. Akses menu **Master Data** > **Format Penomoran** (`/master/numbering`).
2. Masukkan nama format (misal: *Format Standar Box Arsip PT Indraco*).
3. Susun pola pada kolom template (contoh: `{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}`).
4. Tentukan panjang digit angka urut (misal: `4` digit untuk format `0001` s/d `9999`).
5. Periksa kotak **Pratinjau Hasil (Preview)** di bawah formulir untuk memastikan hasil akhir penomoran sudah rapi dan sesuai standar kearsipan.
6. Klik **Simpan Format Penomoran**.
