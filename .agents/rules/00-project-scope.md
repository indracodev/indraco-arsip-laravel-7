---
trigger: always_on
---

# Lingkup proyek

- Target: rebuild **bersih** DMS Arsip PT Indraco di Laravel 13 (PHP 8.4), folder ini (`indraco-arsip-laravel-13`, branch `laravel-13-rebuild`).
- Acuan: `C:\laragon\www\indraco-arsip-laravel-7` (branch `danu-revisi-4`) = **sumber aturan bisnis dan data, bukan kode yang dipindah**. Folder itu hanya dibaca, tidak pernah diubah.
- Sebelum membawa apa pun dari Laravel 7, cek labelnya di `context/triage.md` (KEEP / REWRITE / DROP / ASK). Item ASK tidak dibangun sebelum diputuskan pemilik.
- Aplikasi **internal LAN**. Tidak ada pekerjaan SEO (sitemap, meta, schema, robots, Search Console).
- Aturan teknis utama: `PROJECT_RULES.md`, `PROJECT_RULES_SQLITE.md`, `PROJECT_RULES_LAN_FRONTEND_OPS.md`, `engineering_guidelines.md`, `code_file_size_limits.md`.

## UX tetap sama, UI dibangun ulang

- **Tetap sama:** alur kerja, urutan langkah, fitur, aturan bisnis, status, hak akses per role, istilah yang dipakai user (Box, Berkas, Pengajuan, BAP, dll).
- **Komposisi form tetap sama:** jumlah input, nama/label field, mana yang wajib dan mana yang opsional, pilihan dropdown, nilai default, dan pesan validasi mengikuti Laravel 7. Catat setiap form di inventaris sebelum dibangun ulang, lalu bandingkan setelahnya.
- **Dibangun ulang:** tampilan. Sederhana, modern, ringan untuk PC klien low-spec, warna terbatas, komponen konsisten, responsif, 100% lokal tanpa CDN. Lihat `50-frontend-blade.md` dan `PROJECT_RULES_LAN_FRONTEND_OPS.md` §6.
- Perubahan alur hanya dengan persetujuan pemilik, dicatat di `context/decisions.md`.

## Yang harus tetap kompatibel

- `database.sqlite` lama bisa dibuka langsung: nama tabel dan kolom tetap.
- Hash password user lama tetap valid.
- Path file upload yang tersimpan di database tetap bisa dibuka.
- Format dan urutan nomor box yang sudah berjalan.
- Nilai status arsip, peminjaman, perpanjangan, pemusnahan (BAP).
- Endpoint yang dipakai klien desktop (`desktop/`, Tauri) dan widget LAN monitor.
- Cara menjalankan: salin folder, klik `START-DMS-INDRACO.bat`. Nama file launcher tetap sama.

## Temuan

- Setiap temuan (bug, celah keamanan, data janggal, perilaku L7 yang salah) **dicatat** di `context/findings.md` lalu **diperbaiki** di modul yang sedang dibangun.
- Jika perbaikannya mengubah alur yang dilihat user atau data lama, tanya pemilik dulu dan catat keputusannya di `context/decisions.md`.

## Pengujian di browser

- Setiap halaman/modul diuji langsung di browser (Chrome lewat Claude in Chrome) pada server yang dijalankan launcher: alur dicoba sampai selesai, komposisi form dibandingkan dengan L7, console bersih, lebar 360/768/1366 px. Hasilnya dilaporkan bersama STOP modul.

## Prioritas

1. Aman diakses banyak komputer bersamaan.
2. Portable dan stabil di Windows 10/11 tanpa install.
3. UI role PIC Departemen, lalu PIC Gudang, lalu Admin.
