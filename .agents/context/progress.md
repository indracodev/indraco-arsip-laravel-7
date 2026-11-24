# Progres

## Fase
- [x] Rencana (`../PLAN-REBUILD-LARAVEL-13.md`) dan aturan `.agents/`
- [x] Fase 0: inventaris PIC Departemen + triage seluruh project — **ACC pemilik** (ASK diputuskan, lihat decisions)
- [x] Fase 1: fondasi Laravel 13 + runtime portable + uji beban — selesai & diverifikasi koordinator (commit a4f3abf) — **ACC pemilik 2026-11-24**
- [x] Fase 2: skema baseline (identik L7), 17 model, seeder instalasi baru, AUTH (UI sementara), Laravel Boost — diverifikasi koordinator + uji browser — **ACC pemilik 2026-11-24**
- [x] Fase 3: design system CSS murni, shell/menu per role, login & ganti password, PWA dasar, mockup Katalog (data nyata) + Form Pengajuan (statis), R4, R8 — diverifikasi koordinator + uji browser (tangkapan layar di `context/mockup-fase-3/`), revisi warna slate + ambient terlihat + **menu atas saja** + R14–R16, R19, R20 selesai (uji browser koordinator, tangkapan 07–12), **ACC pemilik 2026-11-24**
- [ ] Fase 4: modul PIC Departemen (STOP per modul) — **sedang dikerjakan**: (a) SHELL+ dark mode/suara/perbesar teks, (b) ARSIP pengajuan multi-box (draft, ajukan, edit, hapus draft)
- [ ] Fase 5: modul PIC Gudang dan Admin (STOP per modul)
- [ ] Fase 6: verifikasi menyeluruh (security, performance, parity)
- [ ] Fase 7: cutover

## Catatan
- 2026-11-24: subagent Fase 1 tidak sengaja menghapus `.agents/` dan `template-import-master/` (loop `rm` dari output `ls`). File ter-commit dipulihkan dari git, aturan baru ditulis ulang. Aturan pencegahan ditambahkan di `rules/10-safety.md`.

## Catatan tambahan
- Folder `desktop/` (klien Tauri) dan `.github/workflows/build-clients.yml` belum ada di worktree L13 (ikut terhapus saat skeleton dipasang). Bawa di modul OPS dari branch `danu-revisi-4`; pastikan klien lama tetap bisa tersambung ke server L13.
- Cek versi WebView2 dan versi browser (Chrome/Edge/Firefox) yang dipakai di PC klien sebelum Fase 3.

## Rencana sampai selesai (urutan putaran)
- **A1 ARSIP polish**: perbaiki kunci submit app.js (nilai tombol hilang), hapus cabang mockup mati, tombol Hapus di katalog tidak turun baris, ukuran teks per akun (`users.font_size`), input nama dokumen kustom admin (ganti `prompt()`), cetak Form Pengajuan, dokumen shell.md.
- **A2 FILE + DETAIL**: halaman detail arsip, pratinjau/unduh file lewat controller ber-otorisasi (X-Accel Caddy), kompatibel path lama (public/storage & storage/app/public), tutup temuan #2/#3.
- **B PENARIKAN**: pengajuan penarikan PIC, approve/reject/dispatch gudang, simpan kembali (re-store), notifikasi.
- **C RETENSI**: perpanjangan masa simpan, usulan pemusnahan, BAP (cetak), approve/complete gudang/admin.
- **D GUDANG**: dashboard gudang, verifikasi + penomoran box atomik, check-in rak/slot, keluarkan arsip, cetak label/stiker.
- **E MASTER & USER**: departemen/sub/master berkas + impor Excel data master, gudang & rak, format penomoran, kelola user/PIC/password, impersonate.
- **F LAYOUT GUDANG 2D** (kanvas, ringan tanpa GPU).
- **G SISTEM**: dashboard admin, log aktivitas, laporan + ekspor CSV aman, pengaturan (logo, font), backup VACUUM INTO, hapus massal (backup dulu), diagnostik/health/live monitor, update dari web, notifikasi realtime + suara.
- **H IMPOR multi-box Excel PIC**.
- **I OPS**: folder klien `desktop/` (Tauri) + workflow build, START-CLIENT-WORKSTATION, R18 STOP→START, runbook.
- **J Verifikasi menyeluruh**: audit security (0 critical/high), uji beban k6 modul nyata, parity, uji browser semua role, ukuran file.
- **K Rencana cutover** (tanpa deploy): salin DB & file upload, bersihkan data uji, panduan.
