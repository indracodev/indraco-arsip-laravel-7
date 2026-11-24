# Rencana Rebuild DMS Arsip PT Indraco ke Laravel 13

Status: **DRAF, menunggu persetujuan.** Belum ada kode yang diubah.

## 1. Tujuan

1. Aplikasi berjalan di Laravel 13 (PHP 8.4) dan tetap **portable**: salin folder, klik launcher, jalan di Windows 10/11 tanpa install apa pun.
2. **Aman dibuka banyak user bersamaan** di LAN kantor, dengan database tetap SQLite.
3. **UI dibangun ulang**: ringkas, konsisten, responsif. Prioritas pertama: **role PIC Departemen**.
4. Data lama tetap terpakai: `database.sqlite` yang sekarang bisa langsung dibuka aplikasi baru.

## 2. Lokasi

| Hal | Nilai |
|---|---|
| Acuan (hanya dibaca) | `C:\laragon\www\indraco-arsip-laravel-7`, branch `danu-revisi-4` |
| Target | `C:\laragon\www\indraco-arsip-laravel-13`, branch `laravel-13-rebuild` (git worktree dari repo yang sama) |
| Acuan pola rebuild | `C:\laragon\www\upgrade-indracostore-laravel-7-to-13` (Laravel 13.17, PHP 8.4.24, Tailwind 4, Vite 8) |

Laravel 7 adalah **sumber aturan bisnis dan data**, bukan kode yang dipindah semuanya. Setiap fitur dibawa hanya jika terbukti masih dipakai.

## 3. Kenapa lambat saat banyak user, dan solusinya

Hambatan utama sekarang **bukan Laravel 7 dan bukan SQLite**, tapi `php artisan serve`: di Windows ia hanya melayani **satu request dalam satu waktu**. User lain mengantre.

| Lapisan | Sekarang | Target |
|---|---|---|
| Web server | `artisan serve` (1 proses, antre) | **Caddy** (1 file exe) + **pool php-cgi** (mis. 6 proses) = 6 request diproses bersamaan |
| PHP | 8.2 TS portable | 8.4 **NTS** portable (build untuk FastCGI), DLL VC++ ikut dibundel |
| SQLite | WAL + busy_timeout 5000 diset manual di AppServiceProvider | Diset di `config/database.php` (fitur bawaan Laravel 13), lihat bagian 4 |
| Session & cache | file | tetap file (tidak menambah tulis ke SQLite) |

Alternatif Caddy: Nginx for Windows dengan cara yang sama. Keduanya portable, tanpa install.

## 4. Optimasi SQLite untuk banyak user

Prinsipnya sama dengan LexDesk: **banyak pembaca bersamaan, satu penulis bergantian, dan penulis tidak boleh lama memegang kunci.**

```php
'sqlite' => [
    'busy_timeout'     => 10000,       // tunggu kunci maks 10 dtk, bukan langsung "database is locked"
    'journal_mode'     => 'wal',       // pembaca tidak diblok penulis
    'synchronous'      => 'normal',    // aman di WAL, jauh lebih cepat dari FULL
    'transaction_mode' => 'IMMEDIATE', // ambil kunci tulis di awal transaksi -> tidak ada deadlock upgrade kunci
    'foreign_key_constraints' => true,
    'pragmas' => [
        'cache_size'   => -20000,      // ~20 MB cache per koneksi
        'temp_store'   => 'memory',
        'mmap_size'    => 268435456,   // 256 MB
    ],
],
```

Aturan kode:
- Transaksi tulis pendek: validasi dan baca dulu di luar transaksi, tulis di dalam.
- Upload file (scan, lampiran) disimpan ke disk **sebelum** transaksi, bukan di dalamnya.
- Nomor box tetap atomik di dalam transaksi IMMEDIATE (sudah ada di Laravel 7, dibawa).
- Backup harian dengan `VACUUM INTO` (sudah ada, dibawa).

Batas yang jujur: SQLite tetap **satu penulis dalam satu waktu**. Untuk puluhan user LAN yang kebanyakan membaca dan sesekali menyimpan, ini cukup. Ukurannya dibuktikan dengan **uji beban k6** (repo sudah punya `tests/k6`) sebelum lanjut ke UI.

## 5. UI baru

Masalah sekarang: terlalu ramai (banyak teks, banyak warna), komponen besar, tidak responsif, tidak konsisten.

Usulan:
- **Blade + Alpine.js + Tailwind 4**, aset di-build di laptop developer lalu ikut di-commit. Server tidak butuh Node. Tidak memakai SPA.
- **Satu set komponen Blade** dipakai di semua halaman: tombol, input, select, tabel, badge status, modal, kartu, toast. Tidak ada styling ad-hoc per halaman.
- **Palet terbatas**: netral (abu) + satu warna aksen (navy) + warna status (hijau/kuning/merah) hanya untuk status.
- **Padat tapi lega**: ukuran teks 14px, kontrol tinggi 36px, tabel compact.
- **Mobile first**: tabel berubah jadi kartu di layar kecil, menu jadi drawer.
- **Teks minimal**: label singkat, penjelasan panjang dipindah ke tooltip atau halaman bantuan.

Sebelum membangun halaman, dibuat dulu **mockup** layout + 1 halaman contoh untuk disetujui.

## 6. Fase kerja (berhenti di setiap STOP)

| Fase | Isi | Hasil |
|---|---|---|
| 0 | Inventaris fitur role PIC Departemen dari Laravel 7 (route, aturan bisnis, tabel yang disentuh). Tandai fitur yang dipakai / tidak dipakai. | Daftar fitur untuk disetujui. **STOP** |
| 1 | Skeleton Laravel 13 + runtime portable (PHP 8.4 NTS, Caddy, pool php-cgi, launcher baru) + konfigurasi SQLite. Uji beban k6 di LAN. | Angka uji beban. **STOP** |
| 2 | Skema & model: migrasi baseline yang **sama persis** dengan skema sekarang, model, login (hash password lama tetap valid). Buka salinan `database.sqlite` asli. | Login dengan akun lama berhasil. **STOP** |
| 3 | Design system + layout + mockup. | Mockup disetujui. **STOP** |
| 4 | Halaman PIC Departemen, per modul: dashboard → katalog arsip → pengajuan box (+ impor) → peminjaman → perpanjangan / pemusnahan. | **STOP per modul** |
| 5 | Role PIC Gudang dan Admin (termasuk impor data master). | **STOP per modul** |
| 6 | Cutover: uji paritas data, salin database, ganti launcher, panduan update. | Siap dipakai |

Selama Fase 1–5, aplikasi Laravel 7 tetap dipakai di server seperti biasa.

## 7. Yang harus tetap kompatibel

- Nama tabel dan kolom (supaya `database.sqlite` lama bisa dipakai langsung).
- Hash password user.
- Path file upload yang tersimpan di database.
- Format nomor box dan penomoran yang sudah berjalan.
- Alur status arsip, peminjaman, perpanjangan, pemusnahan (BAP).

## 8. Keputusan yang perlu dikonfirmasi

1. Web server: **Caddy** (disarankan, satu file, konfigurasi pendek) atau Nginx?
2. UI: **Blade + Alpine** (disarankan) atau Livewire?
3. Skema database dipertahankan sama persis (disarankan) atau boleh dirapikan dengan migrasi data?
4. Fitur yang tidak dipakai di Laravel 7 (hasil Fase 0) boleh tidak dibawa?
