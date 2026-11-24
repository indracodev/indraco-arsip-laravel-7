# Progres

## Fase
- [x] Rencana (`../PLAN-REBUILD-LARAVEL-13.md`) dan aturan `.agents/`
- [ ] Fase 0: inventaris PIC Departemen + triage seluruh project (`INVENTARIS-PIC-DEPT.md`, `context/triage.md`, `context/findings.md`) — STOP
- [ ] Fase 1: fondasi Laravel 13 + runtime portable (PHP 8.4.28 NTS, Caddy 2.11.9, pool php-cgi, launcher) + uji beban — STOP
- [ ] Fase 2: skema baseline, model, login user lama — STOP
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
Tunggu hasil Fase 0 dan Fase 1, laporkan ke pemilik.
