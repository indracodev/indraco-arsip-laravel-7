# Progres

## Fase
- [x] Rencana (`../PLAN-REBUILD-LARAVEL-13.md`) dan aturan `.agents/`
- [x] Fase 0: inventaris PIC Departemen + triage seluruh project — **ACC pemilik** (ASK diputuskan, lihat decisions)
- [x] Fase 1: fondasi Laravel 13 + runtime portable + uji beban — selesai & diverifikasi koordinator (commit a4f3abf) — **ACC pemilik 2026-11-24**
- [x] Fase 2: skema baseline (identik L7), 17 model, seeder instalasi baru, AUTH (UI sementara), Laravel Boost — diverifikasi koordinator + uji browser — **ACC pemilik 2026-11-24**
- [x] Fase 3: design system CSS murni, shell/menu per role, login & ganti password, PWA dasar, mockup Katalog (data nyata) + Form Pengajuan (statis), R4, R8 — diverifikasi koordinator + uji browser (tangkapan layar di `context/mockup-fase-3/`), revisi warna slate + ambient terlihat + **menu atas saja** + R14–R16, R19, R20 selesai (uji browser koordinator, tangkapan 07–12), **ACC pemilik 2026-11-24**
- [x] Fase 4/5 modul: ARSIP (pengajuan, detail, file aman, cetak), PENARIKAN, RETENSI, GUDANG, MASTER & USER, DENAH 2D, SISTEM & notifikasi, IMPOR, OPS — dibangun paralel, diverifikasi koordinator (276 tes, uji browser admin, notifikasi lintas akun, matriks akses), di-commit 2026-11-24
- [ ] Perapian (P): R32, R33, R41–R44, tombol gudang di katalog, impor admin langsung ke gudang, simpan kembali (re-store) penarikan + guard B12, pencarian cepat bar atas
- [ ] Fase 6: verifikasi menyeluruh (security, performance, parity)
- [ ] Fase 7: cutover

## Catatan
- 2026-11-24: subagent Fase 1 tidak sengaja menghapus `.agents/` dan `template-import-master/` (loop `rm` dari output `ls`). File ter-commit dipulihkan dari git, aturan baru ditulis ulang. Aturan pencegahan ditambahkan di `rules/10-safety.md`.

## Catatan tambahan
- Folder `desktop/` (klien Tauri) dan `.github/workflows/build-clients.yml` belum ada di worktree L13 (ikut terhapus saat skeleton dipasang). Bawa di modul OPS dari branch `danu-revisi-4`; pastikan klien lama tetap bisa tersambung ke server L13.
- Cek versi WebView2 dan versi browser (Chrome/Edge/Firefox) yang dipakai di PC klien sebelum Fase 3.

## Langkah berikutnya
1. Perapian (P) — paralel.
2. J verifikasi menyeluruh: audit security, uji beban k6 rute nyata, uji browser semua role + lebar 360/768/1366, ukuran file.
3. K rencana cutover (tanpa deploy).
