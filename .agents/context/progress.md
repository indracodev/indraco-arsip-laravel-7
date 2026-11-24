# Progres

## Fase
- [x] Rencana (`../PLAN-REBUILD-LARAVEL-13.md`) dan aturan `.agents/`
- [x] Fase 0: inventaris PIC Departemen + triage seluruh project — **ACC pemilik** (ASK diputuskan, lihat decisions)
- [x] Fase 1: fondasi Laravel 13 + runtime portable + uji beban — selesai & diverifikasi koordinator (commit a4f3abf) — **ACC pemilik 2026-11-24**
- [x] Fase 2: skema baseline (identik L7), 17 model, seeder instalasi baru, AUTH (UI sementara), Laravel Boost — diverifikasi koordinator + uji browser — **ACC pemilik 2026-11-24**
- [ ] Fase 3: design system CSS murni, layout/shell, login, mockup (+ R4, R8) — **sedang dikerjakan** — STOP
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
1. R4 (UNIQUE email) dan R8 (seeder layout produksi) dikerjakan di awal Fase 3.
2. Fase 3: design system CSS murni + layout + login/shell baru + mockup. Bersihkan sisa Tailwind/Vite dari skeleton: `package.json`, `vite.config.js`, `resources/css/app.css`, `resources/js/app.js` (pindah ke `public/`), skill Boost `tailwindcss-development` (+ keluarkan guideline Tailwind/Vite Boost lewat `config/boost.php`), catatan Vite di blok Boost `AGENTS.md`.
