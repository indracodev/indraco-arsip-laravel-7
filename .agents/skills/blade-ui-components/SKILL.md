---
name: blade-ui-components
description: Design system UI baru DMS Indraco (Blade component + Alpine + CSS murni tanpa build tool) — daftar komponen, token warna/ukuran, pola halaman daftar/form/detail, modal konfirmasi, responsif, ringan untuk PC low-spec. Pakai saat membuat atau mengubah view.
---

# Komponen UI

Aturan: `.agents/rules/50-frontend-blade.md`, `PROJECT_RULES_LAN_FRONTEND_OPS.md` §6.

## Token (variabel CSS di `public/css/tokens.css`; aturan CSS di `rules/50-frontend-blade.md`)
- Warna: netral bertint slate, primer slate gelap `--color-accent` (#262f3b), sekunder oranye logo `--color-brand` (#fd4f00) hanya untuk indikator aktif/cincin fokus (`--color-focus`)/highlight kecil, latar area konten (`.main`, juga `.guest`) satu gradien ambient statis (`--ambient`, 2 glow, darkest point dicek); kontras AA dicek `tests/Unit/ContrastTest.php`; status: hijau=selesai/aktif, kuning=menunggu, merah=ditolak/dimusnahkan, biru=dipinjam/info. Tidak ada warna lain. **Tema gelap** = hanya override token di `:root[data-theme="dark"]` (`tokens.css`, `@media screen`; cetak tetap terang) — jangan menulis warna/aturan khusus gelap di komponen; warna baru = token baru di kedua blok + pasangan di `ContrastTest::PAIRS`.
- Ukuran dalam rem/em (px hanya border/garis rambut); breakpoint = kelas `.bp-lg/.bp-lt-lg/.bp-md/.bp-lt-md/.bp-lt-sm` di `<html>` (dari `prefs.js`, ikut ukuran font), **bukan** `@media (min|max-width)`.
- Teks 14px dasar, 12px meta; kontrol tinggi 36px; radius kecil; shadow tipis satu level; tanpa blur/gradient.

## Komponen (`resources/views/components/`)
`button`, `link-button`, `input`, `select`, `textarea`, `checkbox`, `file-input`, `field` (label + kontrol + error + hint), `table` (+ mode kartu di < 768px), `badge-status` (peta status arsip → warna/label), `modal`, `confirm-modal`, `card`, `toast`, `empty-state`, `pagination`, `page-header` (judul + aksi), `filter-bar`.

Status → label/warna: satu peta di PHP (`App\Support\StatusLabel` atau enum) dipakai oleh `badge-status`; tidak ada peta kedua di JS.

## Pola halaman
- **Daftar**: `page-header` → `filter-bar` → `table` → `pagination`. Aksi baris maksimal 2 tombol terlihat, sisanya menu.
- **Tombol**: aksi berteks selalu tombol bergaris (`<x-button>` default / `primary` / `danger`); aksi hapus sekunder = default + `class="tone--danger"` (teks + garis merah). `variant="ghost"` **hanya** untuk tombol ikon (`btn--icon`: tutup ×, menu, tema) dan judul kolom tabel yang bisa diurutkan. Item menu klik-kanan/daftar hasil cari boleh tanpa garis (memang menu).
- **Form**: satu kolom di mobile, dua kolom di ≥ 1024px; urutan dan jumlah field **sama dengan L7**; tombol simpan di bawah + disable saat submit.
- **Detail**: ringkasan (badge status + data kunci) → tab/seksi → riwayat log.
- Modal konfirmasi: atribut `data-confirm*` / `window.showConfirmModal()`; tanpa `confirm()` native.

## Ringan
- Alpine hanya untuk interaksi (toggle, modal, form dinamis). Data dirender server.
- Ikon: SVG inline dari set kecil (hanya yang dipakai), bukan library ikon penuh (L7 memuat Lucide 350 KB).
- Tanpa Chart.js kecuali halaman yang memang menampilkan grafik (dimuat hanya di sana).

## Verifikasi
- Uji browser di 360/768/1366 px; keyboard bisa mengoperasikan form dan modal; console bersih.
- Ukuran: JS < 150 KB, CSS < 60 KB (gzip); `tests/Feature/Shell/AssetsTest.php` mengecek fitur CSS terlarang dan bundle mutakhir.
- Mockup halaman pertama disetujui pemilik sebelum komponen dipakai luas (Fase 3).
