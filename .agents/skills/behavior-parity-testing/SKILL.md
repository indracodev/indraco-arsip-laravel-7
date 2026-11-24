---
name: behavior-parity-testing
description: Memastikan perilaku Laravel 13 sama dengan Laravel 7 DMS Indraco — menjalankan L7 dengan PHP portable 8.2, salinan database, membandingkan status, data tersimpan, nomor box, pesan, dan komposisi form. Pakai sebelum STOP setiap modul.
---

# Parity L7 ↔ L13

## Menjalankan L7 sebagai acuan
- Folder `C:\laragon\www\indraco-arsip-laravel-7`, PHP `evironment\php-8.2.29-Win32-vs16-x64\php.exe` (set `PHPRC` ke folder itu).
- **Selalu** salin `database\database.sqlite` ke lokasi uji; jangan menulis ke DB asli.
- Jalankan di port berbeda (mis. `artisan serve --port=8010`) agar tidak bentrok dengan L13 di 8000.

## Yang dibandingkan per modul
1. **Komposisi form**: daftar field, label, wajib/opsional, pilihan, default, pesan validasi (ambil dari Form Request/`validate()` dan Blade L7).
2. **Hasil simpan**: baris DB yang berubah (tabel, kolom, nilai) untuk input yang sama.
3. **Status & transisi**: status sebelum/sesudah aksi.
4. **Nomor box** dan tanggal kedaluwarsa retensi untuk contoh yang sama.
5. **Pesan** sukses/error yang dilihat user.
6. **Hak akses**: role/departemen yang boleh dan yang ditolak.

## Cara
- Tes L13 memuat contoh nilai dari L7 sebagai fixture (tanpa data pribadi).
- Untuk alur UI: jalankan keduanya di browser, catat perbedaan di `context/modules/<modul>.md`. Perbedaan yang disengaja (perbaikan bug/keamanan) dicatat di `decisions.md`.

## Perangkap
- Tes L7 memakai `database/testing.sqlite`; jangan tertukar dengan DB asli.
- Perbedaan zona waktu: `Asia/Jakarta` di kedua sisi.
