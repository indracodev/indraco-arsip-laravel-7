# Progres

## Fase
- [x] Rencana (`../PLAN-REBUILD-LARAVEL-13.md`) dan aturan `.agents/`
- [x] Fase 0: inventaris PIC Departemen + triage seluruh project — **ACC pemilik** (ASK diputuskan, lihat decisions)
- [x] Fase 1: fondasi Laravel 13 + runtime portable + uji beban — selesai & diverifikasi koordinator (commit a4f3abf) — **ACC pemilik 2026-11-24**
- [ ] Fase 2: skema baseline, model, login user lama — **sedang dikerjakan** — STOP
- [ ] Fase 3: design system, layout, mockup — STOP
- [ ] Fase 4: modul PIC Departemen (STOP per modul)
- [ ] Fase 5: modul PIC Gudang dan Admin (STOP per modul)
- [ ] Fase 6: verifikasi menyeluruh (security, performance, parity)
- [ ] Fase 7: cutover

## Catatan
- 2026-11-24: subagent Fase 1 tidak sengaja menghapus `.agents/` dan `template-import-master/` (loop `rm` dari output `ls`). File ter-commit dipulihkan dari git, aturan baru ditulis ulang. Aturan pencegahan ditambahkan di `rules/10-safety.md`.

## Catatan tambahan
- Folder `desktop/` (klien Tauri) dan `.github/workflows/build-clients.yml` belum ada di worktree L13 (ikut terhapus saat skeleton dipasang). Bawa di modul OPS dari branch `danu-revisi-4`; pastikan klien lama tetap bisa tersambung ke server L13.
- Cek versi WebView2 dan versi browser (Chrome/Edge/Firefox) yang dipakai di PC klien sebelum Fase 3.

## Langkah berikutnya
2. Fase 2: hapus `routes/bench.php` + loader-nya di `bootstrap/app.php`; jalankan `php artisan boost:install`; migrasi baseline yang sama persis dengan skema L7 (migrasi skeleton bentrok dengan tabel lama, mis. `users`); model + login user lama (tanpa rate limit, login gagal dicatat).
3. Cek versi WebView2/browser klien sebelum Fase 3.
