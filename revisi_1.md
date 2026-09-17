ADMIN USER
==============
1. ukuran box TB 30g
2. ada tanggal periode document dan tanggal penyerahan document (box) 
3. 1 box isi satu periode deocument tidak boleh campur
4. masa simpan diambil dari periode document
5. format tanggal YYYY/MM
6. sortcut dashbioard admin ke input gudang layout (sinkron dengan PIC Gudang) : posisi dibawah search
7. penamaan/ ID departemen diambil dari SIDAR
8. Departemen ditambahkan sub-departemen
9. masa simpan custom
10. menu layout 2D waktu di klik rak>modal muncul layout rak berisi box
11. control/admin di webdev

DEPARTEMEN USER
===============
1. tambahkan pilihan custom nama document pada user departemen
2. Format label box form A5 (nomor rak kosong)
3. input/cari/isi dokumen dalam gudang

GUDANG USER
=============
1. Format label box ukuran form A5 (nomor rak kosong) di isi oleh pic gudang
2. format layout box/rak paten
3. satu gondola terdapat beberapa rak, satu rak terdapat beberapa
4. indikator untuk box (merah expired/ kuning terisi / hijau kosong)
5. ploting dalam 1 rak baris rak sudah fixed (box terisi masing2 barus tergantung)
6. Fitur layout gudang 2D bisa di akses PIC gudang




RULES
======
ISTILAH PINJAM (KELUAR BARANG)
upload approval > jika sudah terupload (lengkap) maka > tombol simpan/pinjam/pemusnahan muncul
kusus dua ruangan di lock untuk FAT yang lain kroyokan
kata kunci search/ sugesstion terdapat pada label
ukuran font dinamis



FORMAT LABEL
============
---------------------------------------------------------
| Dept :         |    |
| tgl. Penyerahan :      |    |
| Periode :        | nomor gudang  |
| Masa Simpan :        |    |
| Isi dokumen :        |---------------|
|    - sisiisis        |
|    - sisisis        |
|    - askdasbd        |
|    - mlasdhjkadh        |
|-------------------------------------------------------|


=========================================================
PEMETAAN UPDATE FITUR BERDASARKAN ROLE (ROLE-BASED MATRIX)
=========================================================

1. ADMIN USER (Role: `admin` / Superadmin Webdev)
---------------------------------------------------------
A. Manajemen Master Data & Konfigurasi:
   - [ ] Integrasi SIDAR: Penamaan dan ID Departemen disinkronkan langsung dari SIDAR.
   - [ ] Sub-Departemen: Penambahan entitas data Sub-Departemen berelasi dengan Departemen.
   - [ ] Standarisasi Box: Pengaturan ukuran box standar gudang (TB 30g).
   - [ ] Manajemen Masa Simpan:
         * Otomatisasi masa simpan yang dihitung dari tanggal periode dokumen (format YYYY/MM).
         * Pengaturan opsi masa simpan custom (penyesuaian masa simpan fleksibel).
   - [ ] Kontrol Webdev: Kendali konfigurasi, perizinan, dan pemeliharaan sistem terpusat pada webdev/superadmin.

B. Dashboard & Navigasi:
   - [ ] Shortcut Dashboard: Tombol cepat di bawah bar pencarian (*search bar*) dashboard admin yang langsung terhubung ke modul Input Gudang Layout (sinkron dengan PIC Gudang).

C. Visualisasi & Manajemen Layout Gudang 2D:
   - [ ] Akses Modul 2D: Monitoring dan pengelolaan visual layout gudang 2D.
   - [ ] Modal Interaktif Rak: Saat rak diklik, menampilkan pop-up modal layout rak beserta visualisasi box di dalamnya.
   - [ ] Proteksi Ruangan (Room Locking): Penguncian 2 ruangan khusus untuk arsip FAT (Finansial, Akuntansi & Perpajakan), sedangkan ruangan lain bersifat terbuka/keroyokan untuk semua departemen.


2. DEPARTEMEN USER (Role: `pic_dept` / User Departemen)
---------------------------------------------------------
A. Penginputan & Pendataan Arsip:
   - [ ] Form Input Arsip:
         * Input tanggal periode dokumen (format YYYY/MM).
         * Input tanggal penyerahan dokumen/box ke gudang.
         * Validasi: 1 box hanya untuk 1 periode dokumen (tidak boleh campur periode).
         * Pilihan Custom Nama Dokumen jika nama dokumen belum terdaftar di master data.
         * Penentuan masa simpan (otomatis dari periode atau custom).
   - [ ] Pencarian & Penelusuran: Fitur input, pencarian, dan penelusuran isi dokumen di dalam gudang dengan fitur auto-suggestion berbasis kata kunci pada label.

B. Pencetakan Label Box:
   - [ ] Cetak Label Ukuran A5: Generate cetak label box siap tempel berukuran form A5.
   - [ ] Nomor Rak/Gudang Kosong: Bagian nomor rak dan nomor gudang dikosongkan pada cetakan dari departemen (akan dialokasikan oleh PIC Gudang).
   - [ ] Ukuran Font Dinamis: Tipografi font dinamis yang otomatis menyesuaikan panjang teks rincian isi dokumen agar pas di kertas A5.

C. Transaksi Peminjaman (Keluar Barang) & Pemusnahan:
   - [ ] Upload Berkas Approval: Form wajib unggah bukti approval/persetujuan keluar barang (pinjam) atau pemusnahan dokumen.
   - [ ] Kondisional Tombol Aksi: Tombol Simpan / Ajukan Pinjam / Pemusnahan baru aktif/muncul setelah file approval terunggah lengkap.


3. GUDANG USER (Role: `pic_gudang` / PIC Gudang)
---------------------------------------------------------
A. Penempatan & Alokasi Fisik (Plotting Gudang):
   - [ ] Labeling Gudang: Mengisi nomor gudang dan nomor rak pada label A5 yang diterima dari departemen.
   - [ ] Standar Layout Paten: Penerapan layout fisik paten (1 Gondola terdiri dari beberapa rak; 1 rak memiliki baris/slot tetap).
   - [ ] Ploting Baris Fixed: Penempatan box ke dalam slot rak sesuai kapasitas baris yang sudah ditentukan.
   - [ ] Pengendalian Akses Ruangan: Memastikan 2 ruangan terkunci hanya dialokasikan untuk dokumen FAT, serta ruangan lainnya untuk umum/lintas departemen.

B. Modul Layout Gudang 2D (Akses PIC Gudang):
   - [ ] Akses Fitur Layout 2D: PIC Gudang memiliki hak akses langsung ke fitur denah layout gudang 2D.
   - [ ] Modal Interaktif Rak: Klik rak menampilkan modal detail isi rak beserta box di tiap slot.
   - [ ] Indikator Status Warna Box:
         * 🔴 Merah  : Box Expired (masa simpan habis / siap retensi atau musnah).
         * 🟡 Kuning : Box Terisi (slot aktif berisi arsip).
         * 🟢 Hijau  : Slot Kosong (tersedia untuk diisi box baru).

C. Eksekusi Pengeluaran & Pemusnahan Barang:
   - [ ] Validasi Dokumen Approval: Memproses pengeluaran barang pinjaman atau pemusnahan hanya jika dokumen approval dari departemen sudah terunggah lengkap dan valid.


=========================================================
MATRIKS PERBANDINGAN FITUR ANTAR ROLE (ROLE PERMISSION MATRIX)
=========================================================
| Fitur / Kebutuhan Sistem                 | Admin (`admin`) | PIC Dept (`pic_dept`) | PIC Gudang (`pic_gudang`) |
|:-----------------------------------------|:---------------:|:---------------------:|:--------------------------:|
| Master Dept (SIDAR) & Sub-Departemen     |        ✅        |           ❌           |             ❌              |
| Konfigurasi Ukuran Box (TB 30g)           |        ✅        |           ❌           |             ❌              |
| Input Box & Dokumen Baru                 |        ✅        |           ✅           |             ❌              |
| Opsi Custom Nama Dokumen                 |        ✅        |           ✅           |             ❌              |
| Validasi 1 Box 1 Periode (YYYY/MM)       |        ✅        |           ✅           |             ✅              |
| Masa Simpan (Otomatis / Custom)          |        ✅        |           ✅           |             ❌              |
| Cetak Label Form A5 (No Rak Kosong)      |        ✅        |           ✅           |             ✅              |
| Pengisian No. Gudang & No. Rak pada Label|        ✅        |           ❌           |             ✅              |
| Shortcut Dashboard ke Input Layout 2D    |        ✅        |           ❌           |             ✅              |
| Akses Modul Layout Gudang 2D             |        ✅        |           ❌           |             ✅              |
| Interaksi Klik Rak -> Modal Detail Box   |        ✅        |           ❌           |             ✅              |
| Visual Indikator Box (Merah/Kuning/Hijau)|        ✅        |           ❌           |             ✅              |
| Proteksi 2 Ruangan Khusus FAT (Lock)     |        ✅        |     ✅ (Hanya FAT)     |             ✅              |
| Upload Approval Pinjam / Musnah          |        ✅        |           ✅           |             ❌              |
| Validasi Muncul Tombol setelah Upload    |        ✅        |           ✅           |             ❌              |
| Eksekusi Pinjam (Keluar Barang) & Musnah |        ✅        |           ❌           |             ✅              |
| Cari / Sugesti Dokumen via Kata Kunci    |        ✅        |           ✅           |             ✅              |
| Ukuran Font Dinamis pada Label           |        ✅        |           ✅           |             ✅              |

