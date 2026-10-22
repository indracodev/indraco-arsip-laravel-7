# 📋 PANDUAN & CHECKLIST LENGKAP UJI COBA SISTEM ARSIP (DMS)
**Dokumen Skenario Pengujian Berdasarkan Catatan Temuan Server Laptop (`catatan.txt`) & Revisi Terbaru Excel/Gudang**  
*Aplikasi: DMS PT Indraco Global Indonesia (Laravel 7 + Client Desktop / Web)*

---

## 📌 INFORMASI AKUN & PERSIAPAN UJI COBA

Sebelum memulai skenario, pastikan server lokal sudah berjalan (`START-DMS-INDRACO.bat` atau `php artisan serve`).

| Peran (Role) | Akses URL / Menu | Kredensial Uji Coba | Fungsi Utama yang Diuji |
|:---|:---|:---|:---|
| **Super Admin / Admin Gudang** | Login sebagai Super Admin / Admin Gudang Arsip | Akun Superadmin / Gudang | Verifikasi box, denah 2D, cetak label, simpan langsung gudang, impor Excel multi-box. |
| **PIC Departemen** | Login sebagai User Departemen (misal: HRD / FIN / MKT) | User departemen masing-masing | Pengajuan simpan, template excel pic, penarikan berkas, perpanjangan/pemusnahan retensi. |
| **Tool LAN** | Akses URL: `http://[IP-SERVER]:8000/quick-notes` | Bebas (dalam 1 jaringan WiFi/LAN) | Transfer teks catatan & kirim file `.txt` antar-laptop tanpa WA/Telegram. |

---

## 🧭 RINGKASAN STATUS PENGUJIAN

* **Total Skenario Sistem**: 22 Poin
* **Belum Diuji (FITUR TERBARU REVISI EXCEL & GUDANG)**: **6 Poin** (Siap diuji oleh user di Bagian I)
* **Sudah Diuji & Lulus oleh Pengguna (SKENARIO LAMA)**: **16 Poin** (Tercakup di Bagian II)

```
[=============================== 73% Sudah Diuji ====================>       ]
16 Lolos (Lama) / 6 Belum Diuji (Fitur Baru Excel & Gudang)
```

---

# ⏳ BAGIAN I: DAFTAR CHECKLIST YANG BELUM DIUJI (FITUR BARU EXCEL & GUDANG)
*Daftar 6 poin di bawah ini adalah fitur dan perbaikan terbaru dari sesi ini yang siap diuji langsung oleh pengguna.*

---

### 🟡 [BELUM] Skenario Baru 1: Simpan Langsung ke Gudang (Status `in_warehouse`, Auto Generate Nomor Box & Log Mutasi)
*Tujuan: Memastikan tombol "Simpan Langsung ke Gudang" saat impor Excel otomatis menetapkan status box menjadi `in_warehouse` (bukan pengajuan baru/draft), men-generate nomor box resmi (`box_number`), menambah kapasitas rak, dan mencatat riwayat mutasi `WarehouseEntryLog`.*

- [ ] **Langkah Pengujian:**
  1. Login sebagai **Super Admin** atau **Admin Gudang**.
  2. Buka menu **Katalog Arsip**, klik tombol **Impor Excel (Multi-Box)**.
  3. Unggah file Excel yang berisi alokasi gudang dan rak (misal: Ruang `GUDANG R1`, Rak `R1-A`, Slot `1`).
  4. Perhatikan footer modal pratinjau: Terdapat tombol hijau **"Simpan Langsung ke Gudang"**.
  5. Klik tombol hijau **Simpan Langsung ke Gudang**.
  6. Periksa tabel Katalog Arsip pada baris box yang baru dibuat.
- [ ] **Hasil yang Diharapkan:**
  - Status box langsung **"Di Gudang"** (`in_warehouse`), **bukan** "Pengajuan Baru" atau "Draft".
  - Kolom **Nomor Box** langsung terisi nomor resmi (misal: `BX-2610-0001` via `NumberingService`), **tidak kosong/NULL**.
  - Riwayat penempatan tercatat di log gudang (`WarehouseEntryLog`) dan kapasitas rak gudang ter-update.
- **Status:** `[ ] BELUM DIUJI` / `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

### 🟡 [BELUM] Skenario Baru 2: Format & Urutan Kolom Template Excel Baru (Tanpa Kolom `NO_BUTIR`)
*Tujuan: Memastikan urutan kolom template Excel yang diunduh sudah disesuaikan agar logis dan mudah diisi (Box -> Berkas Dokumen -> Periode -> Keterangan -> Retensi/Kondisi -> Rak/Slot), serta kolom `NO_BUTIR` sudah ditiadakan.*

- [ ] **Langkah Pengujian:**
  1. Buka modal **Impor Excel (Multi-Box)** di Katalog Arsip.
  2. Klik tombol **Unduh Template (XLSX)** atau **Unduh Template (CSV)**.
  3. Buka file hasil unduhan menggunakan Microsoft Excel / Spreadsheet.
  4. Periksa header baris pertama pada Sheet 1 (`DATA_ARSIP`).
- [ ] **Hasil yang Diharapkan:**
  - Kolom `NO_BUTIR` **sudah tidak ada**.
  - Urutan kolom baru tersusun logis:
    - **PIC Template**: `NO_BOX_SEMENTARA`, `JUDUL_BOX`, `KODE_DEPARTEMEN`, `KODE_SUB_DEPARTEMEN`, `NAMA_DOKUMEN`, `PERIODE_DOK_MULAI`, `PERIODE_DOK_SELESAI`, `KETERANGAN_DOKUMEN`, `MASA_SIMPAN_BULAN`, `KONDISI_FISIK`
    - **Admin Template**: Ditambahkan di kolom paling belakang: `LOKASI_RUANG`, `LOKASI_RAK`, `NO_SLOT`
  - Kolom keterangan berkas bernama `KETERANGAN_DOKUMEN` (bukan `KETERANGAN_BUTIR`).
- **Status:** `[ ] BELUM DIUJI` / `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

### 🟡 [BELUM] Skenario Baru 3: Penomoran Berkas Otomatis dari Baris Atas ke Bawah
*Tujuan: Memastikan pengguna tidak perlu repot mengetik nomor urut berkas dokumen di Excel, sistem otomatis mengurutkan 1, 2, 3... per box.*

- [ ] **Langkah Pengujian:**
  1. Gunakan template Excel urutan baru (tanpa kolom nomor butir).
  2. Masukkan data 1 box dengan 3 atau 4 baris berkas dokumen berbeda.
  3. Biarkan tanpa ada kolom nomor urut.
  4. Unggah file ke modal **Impor Excel (Multi-Box)**.
  5. Periksa pratinjau tabel berkas pada modal dan klik simpan.
- [ ] **Hasil yang Diharapkan:**
  - File terbaca sukses tanpa peringatan kolom hilang.
  - Setiap berkas dokumen otomatis memiliki nomor urut urut dari atas ke bawah (`1`, `2`, `3`, `4`) di dalam box tersebut.
- **Status:** `[ ] BELUM DIUJI` / `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

### 🟡 [BELUM] Skenario Baru 4: Tabel Referensi Master Nama Berkas / Dokumen Resmi di Sheet 2 (`REFERENSI_KODE`)
*Tujuan: Memudahkan pengguna melihat, memilih, atau menyalin nama dokumen resmi langsung dari Sheet 2 template Excel lengkap dengan masa retensi standar.*

- [ ] **Langkah Pengujian:**
  1. Login sebagai **PIC Departemen** (misal: Keuangan / FIN), unduh template XLSX.
  2. Buka **Sheet 2 (`REFERENSI_KODE`)** di file Excel.
  3. Periksa tabel baru bertajuk **`[DAFTAR MASTER NAMA BERKAS / DOKUMEN RESMI]`**.
  4. Ulangi unduh template menggunakan akun **Super Admin / Admin Gudang**.
- [ ] **Hasil yang Diharapkan:**
  - Sheet 2 memuat tabel referensi master: `NAMA_DOKUMEN_RESMI`, `KODE_DEPARTEMEN`, `KODE_SUB_DEPARTEMEN`, `KATEGORI_DOKUMEN`, dan `STANDAR_RETENSI_BULAN`.
  - Untuk akun PIC Departemen: daftar otomatis difilter hanya menampilkan dokumen resmi milik departemennya sendiri.
  - Untuk akun Admin/Superadmin: daftar menampilkan master dokumen seluruh departemen.
- **Status:** `[ ] BELUM DIUJI` / `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

### 🟡 [BELUM] Skenario Baru 5: Multi-Tier Smart & Fuzzy Matching Nama Berkas di Excel
*Tujuan: Memastikan sistem cerdas dan toleran terhadap kesalahan manusia saat mengetik nama dokumen di Excel (salah urutan kata, typo huruf, kurang kata, kelebihan kata) serta tetap aman jika dokumen kustom di luar master.*

- [ ] **Langkah Pengujian:**
  1. Siapkan file Excel yang memuat beberapa variasi penulisan terhadap master resmi `Faktur Pajak Masukan & Keluaran`:
     - **Variasi A (Salah Urutan Kata / Anagram)**: `Pajak Masukan & Keluaran Faktur`
     - **Variasi B (Typo Huruf Tertukar/Salah)**: `Faktur Pajak Maskuan & Keluran`
     - **Variasi C (Kurang Kata)**: `Faktur Pajak Masukan`
     - **Variasi D (Kelebihan Kata)**: `Faktur Pajak Masukan & Keluaran Asli 2024`
     - **Variasi E (Dokumen Kustom / Di Luar Master)**: `Perjanjian Sewa Kantor Baru 2024`
  2. Unggah file tersebut melalui modal Impor Excel.
  3. Periksa hasil penyesuaian di formulir dan pratinjau.
- [ ] **Hasil yang Diharapkan:**
  - Variasi A, B, C, dan D **otomatis diselaraskan** ke nama master resmi `Faktur Pajak Masukan & Keluaran` dan kategori dokumen box otomatis terisi `Keuangan & Pajak`.
  - Variasi E (Dokumen Kustom) **tidak menyebabkan error/crash**. Sistem menyimpannya sebagai berkas kustom dan membawanya ke opsi pilihan formulir web.
- **Status:** `[ ] BELUM DIUJI` / `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

### 🟡 [BELUM] Skenario Baru 6: Pembersihan & Penggantian Istilah "Butir" Menjadi "Berkas / Dokumen"
*Tujuan: Memastikan seluruh antarmuka pengguna (web & modal) sudah bersih dari istilah kaku "Butir", digantikan dengan istilah yang lebih ramah dan familiar ("Berkas" atau "Dokumen") tanpa merusak program maupun database.*

- [ ] **Langkah Pengujian:**
  1. Buka formulir **Buat Pengajuan Arsip Baru (Form 1)**:
     - Periksa label tombol: tertulis **"Tambah Berkas"** (bukan "Tambah Butir").
     - Periksa judul tabel rincian: tertulis **"Rincian Berkas Dokumen dalam Box"**.
     - Periksa badge ringkasan: tertulis **"X Berkas Dokumen"**.
  2. Buka formulir **Edit / Revisi Arsip**:
     - Periksa label judul tabel dan tombol penambahan berkas.
  3. Buka **Modal Impor Excel**:
     - Periksa ringkasan counter: tertulis **"Total Berkas Dokumen"**.
     - Periksa teks dialog konfirmasi simpan: tertulis **"Berkas Dokumen"**.
- [ ] **Hasil yang Diharapkan:**
  - Seluruh teks pada tombol, judul tabel, counter, dan modal menggunakan istilah "Berkas" atau "Dokumen".
  - Aplikasi berjalan lancar, form bisa disimpan dan diedit normal tanpa ada error JavaScript maupun server.
- **Status:** `[ ] BELUM DIUJI` / `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

# ✅ BAGIAN II: DAFTAR CHECKLIST YANG SUDAH DIUJI & LOLOS (SKENARIO LAMA)
*Seluruh 16 skenario di bawah ini telah selesai diuji sebelumnya oleh pengguna dan dinyatakan LULUS.*

---

### 🟢 [SUDAH LULUS] Skenario 1.1: Cetak Form Pengajuan Simpan (Data Tidak Kosong)
- **Status:** `[X] LULUS` — Data lengkap, tidak ada baris kosong.

### 🟢 [SUDAH LULUS] Skenario 1.2: Navigasi Tombol Kembali Setelah Cetak Form
- **Status:** `[X] LULUS` — Tombol kembali dan ESC menutup dialog cetak tanpa trapping fokus.

### 🟢 [SUDAH LULUS] Skenario 1.3: Notifikasi & Pesan Revisi Penolakan dari Admin Gudang
- **Status:** `[X] LULUS` — Badge "Perlu Revisi" muncul disertai alasan penolakan dari admin gudang.

### 🟢 [SUDAH LULUS] Skenario 1.4: Hapus / Ganti File Lampiran saat Mode Revisi / Edit Draft
- **Status:** `[X] LULUS` — Pengguna dapat mengganti atau membatalkan hapus file lampiran.

### 🟢 [SUDAH LULUS] Skenario 1.5: Validasi Keunikan Nama Box (Pencegahan Duplikat & Pengecualian Re-store)
- **Status:** `[X] LULUS` — Nama box ganda ditolak merah; jika dari transaksi penarikan (re-store) diizinkan hijau.

### 🟢 [SUDAH LULUS] Skenario 2.1: Pratinjau Dokumen Universal (Global Flip 180° & Rotate 90°)
- **Status:** `[X] LULUS` — Toolbar pratinjau PDF dan Gambar menyediakan tombol putar 90° dan flip 180° secara konsisten.

### 🟢 [SUDAH LULUS] Skenario 2.2: Pembatasan Penempatan Box pada Denah 2D Berdasarkan Departemen
- **Status:** `[X] LULUS` — Pembatasan penempatan box bekerja pada aksi simpan tanpa memblokir hak akses melihat denah.

### 🟢 [SUDAH LULUS] Skenario 2.3: Tombol Kembali Setelah Verifikasi Box & Cetak Label
- **Status:** `[X] LULUS` — Kembali ke halaman verifikasi gudang dengan lancar setelah cetak label barcode.

### 🟢 [SUDAH LULUS] Skenario 2.4: Approval via Denah Visual 2D Gudang (Anti-Koneksi Terputus)
- **Status:** `[X] LULUS` — Denah 2D terbuka cepat (< 1 detik) tanpa pesan timeout atau koneksi terputus.

### 🟢 [SUDAH LULUS] Skenario 3.1: Pencarian & Filter Konten Dokumen di Dalam Box
- **Status:** `[X] LULUS` — Box dapat dicari berdasarkan kata kunci nama dokumen berkas di dalam box.

### 🟢 [SUDAH LULUS] Skenario 3.2: Navigasi Tombol Kembali & Batal Form Penarikan Berkas (Anti Salah Pintu MDI)
- **Status:** `[X] LULUS` — Alur navigasi presisi: dari Katalog kembali ke Katalog, dari Penarikan kembali ke Penarikan.

### 🟢 [SUDAH LULUS] Skenario 3.3: Proteksi Arsip yang Sedang Ditarik di Katalog (Single & Multi-Select)
- **Status:** `[X] LULUS` — Box yang sedang berstatus ditarik tidak dapat ditarik ganda.

### 🟢 [SUDAH LULUS] Skenario 4.1: Pemisahan Box Diajukan Musnah dari Kolom Jatuh Tempo
- **Status:** `[X] LULUS` — Box berpindah tepat ke antrean pemusnahan tanpa duplikasi data.

### 🟢 [SUDAH LULUS] Skenario 4.2: Peringatan Jatuh Tempo & Modal Popup Berkala (Login & Setiap 1 Jam)
- **Status:** `[X] LULUS` — Modal berkala muncul dengan tombol direct link menuju tab Jatuh Tempo Form 3.

### 🟢 [SUDAH LULUS] Skenario 4.3: Notifikasi Realtime Pengajuan Perpanjangan Retensi di Gudang
- **Status:** `[X] LULUS` — Toast notifikasi realtime dan update badge counter masuk ke Admin Gudang.

### 🟢 [SUDAH LULUS] Skenario 5.1: Quick-Notes LAN (Transfer Catatan Server ↔ Laptop Client)
- **Status:** `[X] LULUS` — Kirim teks dan unduh file `.txt` via LAN berjalan lancar.

### 🟢 [SUDAH LULUS] Skenario 5.2: Uji Daya Tahan Form Input & Pembersihan Pasca-Submit
- **Status:** `[X] LULUS` — Draft pulih saat browser ter-refresh, dan bersih total setelah submit berhasil.

---

## 📋 REKAPITULASI STATUS PENGUJIAN

| No | Modul Pengujian | Total Poin | Status SUDAH (Lulus) | Status BELUM (Fitur Baru) |
|:---:|:---|:---:|:---:|:---:|
| 1 | Modul Pengajuan Simpan (Departemen) | 5 | 5 | 0 |
| 2 | Modul Pengajuan Simpan (Gudang) | 4 | 4 | 0 |
| 3 | Modul Pengajuan Penarikan (Departemen) | 3 | 3 | 0 |
| 4 | Modul Pemusnahan & Retensi (Dep & Gudang) | 3 | 3 | 0 |
| 5 | Utilitas LAN & Reliabilitas Sistem | 2 | 2 | 0 |
| 6 | **Modul Baru: Impor Excel & Fitur Gudang Terbaru** | **6** | **0** | **6 (Siap Diuji)** |
| **TOTAL** | **Seluruh Skenario Sistem** | **23** | **17 (74%)** | **6 (26%)** |

**Kesimpulan / Tindak Lanjut Penguji:**
> Seluruh 17 skenario sebelumnya telah dinyatakan **LULUS** oleh penguji.
> Penguji dapat langsung fokus menguji **6 Fitur Baru pada Bagian I** (Simpan Langsung Gudang, Template Baru tanpa No Butir, Penomoran Berkas Otomatis, Sheet 2 Master Berkas, Multi-Tier Fuzzy Matching, dan Pembersihan Istilah Butir).
