# 📋 PANDUAN & CHECKLIST LENGKAP UJI COBA SISTEM ARSIP (DMS)
**Dokumen Skenario Pengujian Berdasarkan Catatan Temuan Server Laptop (`catatan.txt`)**  
*Aplikasi: DMS PT Indraco Global Indonesia (Laravel 7 + Client Desktop / Web)*

---

## 📌 INFORMASI AKUN & PERSIAPAN UJI COBA

Sebelum memulai skenario, pastikan server lokal sudah berjalan (`START-DMS-INDRACO.bat` atau `php artisan serve`).

| Peran (Role) | Akses URL / Menu | Kredensial Uji Coba | Fungsi Utama yang Diuji |
|:---|:---|:---|:---|
| **PIC Departemen** | Login sebagai User Departemen (misal: HRD / Finance / Marketing) | User departemen masing-masing | Pengajuan simpan, revisi penolakan, cetak formulir, penarikan dokumen, perpanjangan/pemusnahan retensi. |
| **Admin Gudang** | Login sebagai Admin Gudang Arsip | User gudang arsip | Verifikasi box, penolakan & catatan revisi, approval denah 2D, pratinjau lampiran (rotasi 180°), cetak label dokumen, monitoring retensi & perpanjangan. |
| **Tool LAN** | Akses URL: `http://[IP-SERVER]:8000/quick-notes` | Bebas (dalam 1 jaringan WiFi/LAN) | Transfer teks catatan & kirim file `.txt` antar-laptop tanpa WA/Telegram. |

---

## 📝 SKENARIO 1: MODUL PEGAJUAN SIMPAN (PIC DEPARTEMEN)

### Skenario 1.1: Cetak Form Pengajuan Simpan (Data Tidak Kosong)
*Tujuan: Memastikan data isian nomor berkas, judul, masa retensi, dan daftar dokumen tercetak lengkap di formulir PDF/Print, bukan lembar kosong.*

- [x] **Langkah Pengujian:**
  1. Login sebagai **PIC Departemen**.
  2. Masuk ke menu **Pengajuan Simpan / Buat Arsip Baru**.
  3. Isi data pengajuan:
     - Nomor Box / Nama Box (contoh: `BOX-HRD-2026-001`)
     - Periode dokumen & masa retensi
     - Tambahkan minimal 2 butir dokumen pada tabel daftar isi berkas (contoh: *Slip Gaji Januari*, *Surat Kontrak Kerja*).
  4. Klik tombol **Cetak Formulir Pengajuan (Form 1)** atau icon printer.
  5. Perhatikan jendela pratinjau cetak yang muncul.
- [x] **Hasil yang Diharapkan:**
  - Lembar formulir menampilkan data lengkap (Judul Box, Departemen, Periode, serta tabel butir dokumen terisi rapi 100%, bukan baris kosong atau kode template mentah).
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Hasil cetak form pengajuan simpan rapi, terisi lengkap dan kolom Label Box kosong sesuai aturan pengajuan.*

---

### Skenario 1.2: Navigasi Tombol Kembali Setelah Cetak Form
*Tujuan: Memastikan jendela tidak macet dan tombol navigasi/shortcut kembali berfungsi normal setelah membuka dialog cetak.*

- [x] **Langkah Pengujian:**
  1. Pada jendela pratinjau cetak dari Skenario 1.1, lakukan pembatalan cetak (klik *Cancel* pada dialog printer).
  2. Klik tombol **Tutup / Selesai Cetak** atau tekan tombol keyboard **`ESC`**.
  3. Tekan tombol **Kembali** pada sudut kiri atas formulir pengajuan.
- [x] **Hasil yang Diharapkan:**
  - Jendela pratinjau cetak langsung tertutup tanpa menahan fokus kursor.
  - Halaman dapat kembali ke menu **Katalog Arsip** dengan lancar tanpa perlu refresh browser paksa.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Navigasi kembali dan tombol ESC berfungsi lancar tanpa trapping fokus.*

---

### Skenario 1.3: Notifikasi & Pesan Revisi Penolakan dari Admin Gudang
*Tujuan: Memastikan PIC mengetahui bahwa pengajuannya ditolak gudang beserta alasan/catatan perbaikannya.*

- [x] **Langkah Pengujian:**
  1. Buat pengajuan arsip baru dan ajukan (kirim verifikasi ke Gudang).
  2. Buka akun **Admin Gudang**, lakukan penolakan pengajuan dengan mengisi catatan: *"Lampiran formulir belum ditandatangani Manajer HRD, mohon lampirkan ulang"*.
  3. Kembali login ke akun **PIC Departemen** dan buka menu **Katalog Arsip**.
  4. Periksa baris box arsip yang baru saja ditolak oleh gudang.
- [x] **Hasil yang Diharapkan:**
  - Baris arsip memiliki penanda visual warna tegas (Rose / Amber) dengan badge status **"Perlu Revisi"** (bukan sekadar status "Draft" abu-abu biasa).
  - Terdapat kotak peringatan / alert berisi pesan penolakan dari admin gudang secara transparan.
  - Tombol aksi menampilkan **"Revisi & Ajukan"** untuk langsung membuka form koreksi.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Notifikasi dan pesan penolakan tampil jelas, badge status Perlu Revisi aktif.*

---

### Skenario 1.4: Hapus / Ganti File Lampiran saat Mode Revisi / Edit Draft
*Tujuan: Memastikan PIC dapat menghapus atau mengganti file scan formulir input dan file digital yang sudah terunggah sebelumnya ketika sedang merevisi atau mengedit draft box arsip.*

- [ ] **Langkah Pengujian:**
  1. Login sebagai **PIC Departemen**.
  2. Buka arsip berstatus **Draft** atau arsip yang berstatus **Perlu Revisi** yang sudah memiliki lampiran file.
  3. Klik tombol **Edit / Revisi**.
  4. Gulir ke **Bagian 3: Unggah Berkas & Formulir Pengajuan**.
  5. Pada kartu berkas yang sudah ada, perhatikan tombol **"Hapus / Ganti"**.
  6. Klik tombol **"Hapus / Ganti"**.
  7. Pilih file baru sebagai pengganti (atau biarkan kosong jika ingin menghapus).
  8. Klik tombol **Simpan Sementara (Draft)** atau **Simpan & Ajukan**.
- [ ] **Hasil yang Diharapkan:**
  - File lama berhasil dilepas dan digantikan dengan file yang baru (atau dikosongkan jika memang dihapus).
  - Terdapat tombol **"Batal Hapus"** jika pengguna salah klik sebelum menyimpan.
  - File fisik lama di storage sistem terhapus rapi tanpa meninggalkan file sampah yatim (orphan file).
- **Status:** `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

### Skenario 1.5: Validasi Keunikan Nama Box (Pencegahan Duplikat & Pengecualian Box Penarikan)
*Tujuan: Memastikan nama/judul box wajib unik di seluruh departemen dan tidak boleh ada 2 box dengan nama yang sama, KECUALI jika box tersebut diajukan kembali dari transaksi penarikan (re-store box).*

- [ ] **Langkah Pengujian Bagian A (Penolakan Duplikat Box Biasa):**
  1. Login sebagai **PIC Departemen**.
  2. Masuk ke **Pengajuan Simpan / Buat Arsip Baru**.
  3. Masukkan nama box yang sudah pernah ada dan aktif di sistem (misal: `BOX-HRD-2026-001`).
  4. Perhatikan banner live checking nama box di bawah input title.
  5. Coba klik tombol **Simpan & Ajukan Permanen ke Gudang**.
  6. Ulangi dengan membuat 2 box dalam form pengajuan yang sama (Multi-Box) dengan nama yang identik sama persis.
- [ ] **Hasil yang Diharapkan (Bagian A):**
  - Banner peringatan langsung berubah merah menyala dengan status **"DUPLIKAT (DITOLAK)"**.
  - Sistem menampilkan alert peringatan dan menolak pengajuan simpan baik pada sisi browser (JavaScript) maupun validasi server (Controller).
- [ ] **Langkah Pengujian Bagian B (Penerimaan Box yang Sah dari Penarikan):**
  1. Masuk ke menu **Riwayat Penarikan / Pengembalian Arsip**.
  2. Klik tombol **"Ajukan Simpan Kembali"** pada box yang sedang ditarik.
  3. Form pembuatan arsip baru akan otomatis terisi dengan nama box asal penarikan (misal: `BOX-HRD-2026-001`).
  4. Perhatikan banner validasi nama box.
  5. Klik tombol **Simpan & Ajukan Permanen ke Gudang**.
- [ ] **Hasil yang Diharapkan (Bagian B):**
  - Banner menampilkan status hijau **"BOX PENARIKAN (VALID)"** ("Nama box sama diperbolehkan karena merupakan transaksi penyimpanan kembali dari box ditarik yang sah").
  - Pengajuan berhasil disimpan dan diteruskan ke antrean verifikasi gudang tanpa tertolak.
- **Status:** `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

## 🏭 SKENARIO 2: MODUL PEGAJUAN SIMPAN (ADMIN GUDANG)

### Skenario 2.1: Pratinjau Dokumen Lampiran Universal (Fitur Global Flip 180° & Rotate 90° CW/CCW)
*Tujuan: Memastikan opsi Rotate 90° (CCW/CW), Flip 180°, dan Reset selalu muncul dengan jelas di header toolbar modal pratinjau dokumen (PDF maupun Gambar) secara universal di seluruh modul aplikasi.*

- [ ] **Langkah Pengujian:**
  1. Buka berkas atau pratinjau dokumen apa saja di sistem (misal: dari menu Katalog Arsip, Verifikasi Gudang, atau Form Pengajuan).
  2. Buka lampiran berformat **PDF**:
     - Perhatikan header toolbar bagian atas: Tersedia tombol **Putar 90° Kiri (CCW)**, **Putar 90° Kanan (CW)**, **Flip 180°** (dengan badge `180°`), dan **Reset Fit**.
     - Klik tombol **Putar 90° Kanan** & **Putar 90° Kiri** untuk memutar orientasi landscape/portrait.
     - Klik tombol **Flip 180°** untuk membalik dokumen yang ter-scan terbalik secara cepat.
     - Pindah halaman (Halaman 1 ke Halaman 2) untuk memastikan orientasi tetap rapi dan kanvas PDF.js ter-render tajam.
     - Klik tombol **Reset Orientasi** untuk mengembalikan ke tampilan normal (0°).
  3. Buka lampiran berformat **Gambar (JPG / PNG)**:
     - Uji tombol **Putar 90°**, **Flip 180°**, dan **Reset** pada header.
     - Uji zoom in/out dan geser (drag-to-pan) saat gambar dalam keadaan berotasi.
- [ ] **Hasil yang Diharapkan:**
  - Toolbar header pratinjau selalu menampilkan tombol putar/flip secara konsisten tanpa ada tombol yang hilang/kosong.
  - Dokumen PDF dan Gambar dapat diputar (90° kanan/kiri) dan dibalik 180° dengan cepat tanpa lag atau glitch rendering.
  - Tampilan zoom in/out dan fungsi scroll tetap sinkron sempurna pada posisi orientasi yang baru.
- **Status:** `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

### Skenario 2.2: Fitur Baru Pembatasan Penempatan Box pada Gudang/Rak Berdasarkan Departemen
*Tujuan: Memastikan pembatasan penempatan box bekerja dinamis pada proses penyimpanan box (bukan memblokir hak akses melihat/membuka denah atau rak). Fitur Kunci Object (Lama) tetap murni untuk mengunci posisi canvas.*

- [ ] **Langkah Pengujian:**
  1. Login sebagai **Admin Gudang** dan buka menu **Master Lokasi / Denah Gudang 2D**.
  2. Klik salah satu Gudang (Ruangan) atau Rak, lalu buka popup **Edit**:
     - Status Kunci Object (Fitur Lama): Digunakan murni untuk mengunci/membuka posisi objek pada canvas denah.
     - Departemen yang Diizinkan (Fitur Baru): Pilih departemen tertentu (misal: GA / IT) atau biarkan default **"-- Semua Departemen Diizinkan --"**.
  3. Simpan perubahan.
  4. Pengujian Hak Akses Melihat:
     - Admin Gudang dan user tetap bebas membuka denah, melihat rak, dan membuka modal grid 100-box tanpa diblokir.
  5. Pengujian Proses Penempatan Box (Proses Simpan):
     - **Jika Gudang/Rak dikhususkan untuk Departemen Tertentu (misal: GA)**:
       - Pada tab "Input Baru", departemen terkunci ke departemen yang diizinkan (GA).
       - Pada tab "Dari Antrean", hanya box dari departemen tersebut (GA) yang dapat dipilih. Box dari departemen lain tidak diizinkan disimpan di lokasi ini.
       - Pada verifikasi check-in gudang, penempatan box departemen lain ke lokasi ini ditolak oleh sistem.
     - **Jika Tidak Ada Departemen yang Dipilih (Default: Umum / Bebas)**:
       - Box dari **semua departemen boleh dan bebas ditempatkan** di gudang atau rak tersebut.
- [ ] **Hasil yang Diharapkan:**
  - Fitur lama (kunci objek canvas) kembali seperti semula (Gambar 1).
  - Fitur baru pembatasan penempatan box departemen bekerja akurat hanya pada proses penempatan box, tanpa menghalangi hak akses melihat/membuka rak.
- **Status:** `[ ] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji:* ____________________________________________________

---

### Skenario 2.3: Tombol Kembali Setelah Verifikasi Box & Cetak Label
*Tujuan: Memastikan admin tidak terjebak di halaman detail setelah mencetak barcode/label boks.*

- [x] **Langkah Pengujian:**
  1. Buka salah satu arsip yang sudah disetujui, lalu klik **Cetak Label Box / Barcode**.
  2. Setelah label selesai dicetak atau ditutup, klik tombol **"Kembali"** di bagian atas halaman detail arsip.
- [x] **Hasil yang Diharapkan:**
  - Tombol kembali langsung mengarahkan Admin Gudang ke halaman **Daftar Arsip / Verifikasi Gudang** secara mulus tanpa error riwayat form POST atau iframe blank.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Tombol kembali setelah cetak label barcode berjalan mulus dan kembali ke daftar verifikasi.*

---

### Skenario 2.4: Approval via Denah 2D Gudang (Anti-Koneksi Terputus)
*Tujuan: Memastikan pembukaan denah 2D tidak membebani server dan tidak memicu popup "Koneksi Terputus".*

- [x] **Langkah Pengujian:**
  1. Pada menu verifikasi pengajuan simpan, pilih opsi verifikasi menggunakan **Denah Visual 2D Gudang**.
  2. Klik salah satu rak / slot kosong untuk menempatkan boks.
  3. Perhatikan indikator koneksi di pojok aplikasi dan waktu muat denah.
- [x] **Hasil yang Diharapkan:**
  - Denah 2D terbuka cepat (di bawah 1 detik).
  - Tidak muncul peringatan timeout atau banner merah *"Koneksi Terputus"*.
  - Pemilihan slot berhasil dan penempatan tersimpan dengan lancar.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Approval denah visual 2D responsif, lancar, dan tidak ada lagi kendala koneksi terputus.*

---

## 📤 SKENARIO 3: MODUL PENGAJUAN PENARIKAN (DEPARTEMEN)

### Skenario 3.1: Pencarian & Filter Konten Dokumen di Dalam Box
*Tujuan: Memastikan PIC dapat menemukan boks arsip dengan mencari kata kunci isi surat/nama dokumen, bukan hanya judul boks.*

- [x] **Langkah Pengujian:**
  1. Login sebagai **PIC Departemen**, masuk ke menu **Pengajuan Tarik Dokumen**.
  2. Klik tombol **Buat Pengajuan Penarikan**.
  3. Pada kolom pencarian dokumen/boks, ketikkan **nama dokumen spesifik** yang ada di dalam boks (contoh: *"Perjanjian Sewa"*, *"Invoice Vendor ABC"*).
- [x] **Hasil yang Diharapkan:**
  - Sistem menampilkan boks yang memuat dokumen tersebut, meskipun kata kunci tidak ada pada nama/judul boks luar.
  - PIC dapat memilih butir dokumen spesifik yang ingin ditarik/dipinjam.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Pencarian konten internal dokumen box bekerja akurat.*

---

### Skenario 3.2: Navigasi Tombol Kembali & Batal Form Penarikan Berkas (Anti Salah Pintu MDI Desktop)
*Tujuan: Memastikan penekanan tombol Kembali / Batal / shortcut ESC dari form child selalu kembali ke modul asal pemanggilnya ("tidak salah pintu" di jendela MDI tab desktop).*

- [x] **Langkah Pengujian Bagian A (Alur dari Katalog Arsip):**
  1. Login sebagai **PIC Departemen**, buka menu **Katalog Arsip** (`[Form 1]`).
  2. Klik tombol **Tarik** pada salah satu baris box arsip (atau centang beberapa box lalu klik **Tarik Terpilih**).
  3. Halaman berpindah ke formulir **Pengajuan Penarikan Berkas Arsip**.
  4. Klik tombol **Kembali (Esc)** di header form, tombol **Batal** di action bar bawah, atau tekan tombol **`ESC`** pada keyboard.
  5. Periksa halaman tujuan:
     - **Harus kembali ke menu Katalog Arsip (`archives.index`)**.
     - Tab aktif MDI tetap `[Form 1] Katalog Arsip` dan isinya tetap tabel Katalog Arsip (bukan tabel riwayat penarikan).
- [x] **Langkah Pengujian Bagian B (Alur dari Riwayat Penarikan):**
  1. Buka menu **Riwayat Penarikan** (`[Form 2]`).
  2. Klik tombol **Buat Pengajuan (F2)**.
  3. Pada form penarikan, klik tombol **Kembali (Esc)** atau tombol **Batal**.
  4. Periksa halaman tujuan:
     - **Harus kembali ke menu Riwayat Penarikan (`borrowings.index`)**.
- [x] **Langkah Pengujian Bagian C (Alur Simpan Kembali dari Penarikan / Re-store):**
  1. Pada menu **Riwayat Penarikan**, klik tombol **Ajukan Penyimpanan Kembali** pada berkas yang berstatus *Keluar / Dipinjam*.
  2. Pengguna diarahkan ke form multi-box pengajuan simpan kembali (`archives.create?re_store=1`).
  3. Klik tombol **Kembali (Esc)** atau **Batal** pada form tersebut.
  4. Periksa halaman tujuan:
     - **Harus kembali ke menu Riwayat Penarikan (`borrowings.index`)**.
- [x] **Hasil yang Diharapkan:**
  - Alur navigasi presisi 100% sesuai pintu asal: jika dibuka dari Katalog Arsip, kembali ke Katalog Arsip; jika dibuka dari Penarikan, kembali ke Penarikan.
  - Tidak ada lagi tab MDI yang menampilkan isi menu yang salah ("salah pintu").
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Navigasi kembali dan tombol batal presisi sesuai asal pemanggil.*

---

### Skenario 3.3: Proteksi Arsip yang Sedang Ditarik di Katalog (Single & Multi-Select)
*Tujuan: Memastikan arsip yang sudah ditarik tidak dapat diajukan tarikan ganda dan checkbox pilihannya tidak memicu tombol aksi penarikan massal.*

- [x] **Langkah Pengujian:**
  1. Buka menu **Katalog Arsip**.
  2. Cari box arsip yang statusnya sedang **Ditarik / Keluar Gudang** (memiliki transaksi penarikan aktif).
  3. Periksa kolom **AKSI** pada baris arsip tersebut:
     - Tombol "Tarik" individual tidak muncul pada baris tersebut.
  4. Centang checkbox pada baris box yang sedang ditarik:
     - Tombol multi-select **"Tarik Berkas (N)"** pada floating ribbon bar bawah **tidak terpicu / tidak muncul**.
     - Terdapat tooltip/keterangan bahwa arsip sedang ditarik.
  5. Centang kombinasi antara box yang di gudang dan box yang ditarik:
     - Counter tombol "Tarik Berkas" hanya menghitung box yang valid di gudang.
- [x] **Hasil yang Diharapkan:**
  - Box yang sedang berstatus ditarik tidak bisa diajukan penarikan kembali.
  - Seleksi multi data tidak memicu tombol penarikan jika seluruh data yang dicentang berstatus sudah ditarik.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Validasi filter single dan multi-select penarikan bekerja sempurna.*

---

## ⏳ SKENARIO 4: MODUL PEMUSNAHAN & RETENSI ARSIP

### Skenario 4.1: Pemisahan Box yang Sudah Diajukan Pemusnahan dari Kolom Jatuh Tempo
*Tujuan: Mencegah duplikasi data: boks yang sudah diajukan musnah tidak boleh tetap muncul di daftar berkas yang menunggu tindakan.*

- [x] **Langkah Pengujian:**
  1. Login sebagai **PIC Departemen**, buka menu **Perpanjangan & Pemusnahan (Form 3)**.
  2. Pilih salah satu boks arsip yang masa retensinya telah habis / jatuh tempo.
  3. Klik tombol **Ajukan Pemusnahan** dan konfirmasi pengajuan.
  4. Periksa kembali tab **"Daftar Arsip Jatuh Tempo"** dan tab **"Riwayat / Antrean Pemusnahan"**.
- [x] **Hasil yang Diharapkan:**
  - Boks tersebut **otomatis hilang** dari tabel "Daftar Jatuh Tempo".
  - Boks berpindah secara tepat ke tabel **"Antrean Pengajuan Pemusnahan"** dengan status *Menunggu Approval Gudang*.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Pemisahan data jatuh tempo dan antrean pemusnahan bekerja rapi tanpa duplikasi.*

---

### Skenario 4.2: Peringatan Jatuh Tempo & Modal Popup Berkala (Login & Setiap 1 Jam)
*Tujuan: Memberikan tanda peringatan angka (badge) di tab/sidebar dan modal dialog peringatan interaktif saat login serta setiap jam bagi boks yang expired atau mendekati expired (H-30).*

- [x] **Langkah Pengujian:**
  1. Pastikan terdapat arsip dengan masa retensi H-30 hari atau lewat jatuh tempo.
  2. Login ke sistem sebagai **PIC Departemen** atau **Admin Gudang**.
  3. Sesaat setelah masuk, perhatikan kemunculan modal popup **"Peringatan Berkas Jatuh Tempo"**.
  4. Periksa daftar box yang ditampilkan (nomor box, judul, masa retensi, lokasi gudang, dan badge status).
  5. Klik tombol **"Lihat Berkas Jatuh Tempo"**:
     - Modal otomatis menutup.
     - Sistem langsung mengarahkan dan membuka menu **Form 3 (Pemusnahan & Retensi)** pada tab **Jatuh Tempo (H-30)**.
  6. Biarkan aplikasi aktif atau simulasikan interval 1 jam:
     - Popup peringatan muncul kembali secara otomatis setiap 1 jam untuk mengingatkan PIC/Admin.
- [x] **Hasil yang Diharapkan:**
  - Modal peringatan informatif, dapat ditutup kapan saja, dan tombol "Lihat Berkas" langsung mengarahkan pengguna ke tab jatuh tempo Form 3 secara presisi.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Modal popup peringatan berkala 1 jam dan saat login berfungsi prima dengan direct link ke Form 3 tab Jatuh Tempo.*

---

### Skenario 4.3: Notifikasi Realtime Pengajuan Perpanjangan Retensi di Gudang
*Tujuan: Memberitahu Admin Gudang secara langsung jika ada Departemen yang mengajukan perpanjangan masa simpan dokumen.*

- [x] **Langkah Pengujian:**
  1. Buka akun **PIC Departemen**, ajukan **Perpanjangan Masa Retensi** untuk 1 boks arsip (tambah masa simpan 1 tahun).
  2. Buka akun **Admin Gudang** di browser/layar lain (tanpa melakukan refresh manual).
- [x] **Hasil yang Diharapkan:**
  - Pada layar Admin Gudang, muncul toast pop-up notifikasi realtime: *"Ada pengajuan perpanjangan masa retensi baru dari Departemen [Nama Dep]"*.
  - Badge counter pada menu Gudang bertambah secara otomatis.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Notifikasi realtime dan update counter perpanjangan retensi bekerja baik.*

---

## 🛠️ SKENARIO 5: UTILITAS LAN & RELIABILITAS SISTEM

### Skenario 5.1: Quick-Notes LAN (Transfer Catatan Server ↔ Laptop Client)
*Tujuan: Memastikan catatan temuan pengujian dapat dikirim dan diunduh langsung via jaringan lokal tanpa internet/aplikasi chat.*

- [x] **Langkah Pengujian:**
  1. Di laptop client/HP, buka browser dan ketik alamat: `http://[IP-LAPTOP-SERVER]:8000/quick-notes`
  2. Ketikkan catatan pengujian pada kolom teks, lalu klik **"Simpan Catatan"**.
  3. Buka halaman yang sama di laptop server.
  4. Klik tombol **"Download .txt"**.
- [x] **Hasil yang Diharapkan:**
  - Teks catatan yang diketik dari laptop client langsung muncul di laptop server.
  - File `.txt` berhasil terunduh dengan isi catatan yang sama persis.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Transfer data dan unduh file catatan LAN berfungsi normal.*

---

### Skenario 5.2: Uji Daya Tahan Form Input & Pembersihan Otomatis Pasca-Submit
*Tujuan: Memastikan data isian yang sedang diketik tidak hilang jika browser ter-refresh mendadak, serta data draft tersimpan otomatis dibersihkan bersih 100% setelah tombol Submit berhasil ditekan.*

- [x] **Langkah Pengujian Bagian A (Autosave & Pemulihan Draft):**
  1. Buka form **Pengajuan Simpan (Buat Arsip Baru)**.
  2. Ketik nama box dan tambahkan butir nama dokumen, **jangan klik simpan**.
  3. Tekan tombol **`F5`** atau **`Ctrl + R`** (Refresh halaman).
  4. Periksa pemulihan data: Seluruh isian pulih kembali dari draft lokal (`Autosave Draft Restored`).
- [x] **Langkah Pengujian Bagian B (Pembersihan Pasca-Submit):**
  1. Klik tombol **Simpan & Ajukan Permanen ke Gudang**.
  2. Tunggu hingga data berhasil disimpan ke server dan dialihkan kembali ke Katalog Arsip.
  3. Buka kembali form **Buat Arsip Baru**:
     - Form tampil bersih/kosong (data yang baru saja disubmit tidak muncul kembali).
- [x] **Hasil yang Diharapkan:**
  - Formulir memulihkan draft saat ter-refresh sebelum submit, dan membersihkan seluruh draft lokal segera setelah formulir disubmit ke server.
- **Status:** `[X] LULUS` / `[ ] GAGAL`  
  *Catatan Penguji: Form draft autosave tahan banting dan bersih total setelah submit berhasil.*

---

## 📋 REKAPITULASI HASIL AKHIR UJI COBA

| No | Modul Pengujian | Total Poin | Lolos (PASS) | Gagal (FAIL) |
|:---:|:---|:---:|:---:|:---:|
| 1 | Modul Pengajuan Simpan (Departemen) | 5 | 3 | 0 (2 pending uji) |
| 2 | Modul Pengajuan Simpan (Gudang) | 4 | 2 | 0 (2 pending uji) |
| 3 | Modul Pengajuan Penarikan (Departemen) | 3 | 3 | 0 |
| 4 | Modul Pemusnahan & Retensi (Dep & Gudang) | 3 | 3 | 0 |
| 5 | Utilitas LAN & Reliabilitas Sistem | 2 | 2 | 0 |
| **TOTAL** | **Seluruh Skenario Sistem** | **17** | **13** | **0** |

**Kesimpulan / Tindak Lanjut Penguji:**
> Seluruh skenario inti dan skenario tambahan (proteksi penarikan ganda, popup berkala 1 jam jatuh tempo + direct link ke Form 3, dan auto-clear draft submit form 5.2) telah selesai diperbaiki dan terverifikasi LULUS.

