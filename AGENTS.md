# DMS Arsip PT Indraco — Laravel 13 (rebuild)

Pintu masuk untuk semua agent (Claude Code, Antigravity, Codex, dll). Baca ini dulu, lalu ikuti urutannya.

## Mulai sesi
1. `.agents/context/progress.md` — posisi kerja, fase, langkah berikutnya, catatan handoff.
2. `.agents/context/decisions.md` — keputusan yang sudah disetujui pemilik (jangan dibuka ulang).
   - Ganti IP server: ikuti `.agents/context/runbook-ganti-ip.md` (IP sekarang `192.168.2.180`).
3. `.agents/rules/` — aturan wajib. Utama: `00-project-scope.md`, `10-safety.md`, `PROJECT_RULES.md` (+ `_SQLITE`, `_LAN_FRONTEND_OPS`), `engineering_guidelines.md`, `code_file_size_limits.md`.
4. `.agents/skills/<nama>/SKILL.md` — prosedur khusus project, dimuat sesuai tugas:
   | Skill | Untuk |
   |---|---|
   | `legacy-triage` | menilai apa yang dibawa dari Laravel 7 |
   | `rebuild-module` | membangun satu modul (struktur, langkah, catatan modul) |
   | `archive-workflow` | status arsip, penomoran box, retensi, peminjaman, BAP |
   | `sqlite-concurrency` | kode yang menulis database (banyak user) |
   | `file-upload-storage` | upload, preview, download file scan |
   | `blade-ui-components` | design system dan halaman |
   | `portable-runtime` | launcher, Caddy, php-cgi, PHP portable |
   | `behavior-parity-testing` | membandingkan hasil dengan Laravel 7 |
5. `.agents/workflows/` — `start-session`, `triage`, `rebuild-module`, `verify`, `end-session`.

## Fakta singkat
- Rebuild dari `C:\laragon\www\indraco-arsip-laravel-7` (hanya dibaca). UX dan komposisi form **sama**, tampilan baru.
- Laravel 13, PHP 8.4 NTS portable di `evironment/`, Caddy + pool php-cgi, SQLite (WAL, transaksi IMMEDIATE).
- Jalan portable di Windows lewat `START-DMS-INDRACO.bat`; 100% offline, tanpa CDN; internal LAN, tanpa SEO.
- Setiap fase/modul berhenti untuk **ACC pemilik**. Temuan dicatat dan diperbaiki. Uji di browser.

## Perintah
```sh
php artisan test                 # tes
vendor/bin/pint --test           # gaya kode
vendor/bin/phpstan analyse       # analisis statis (Larastan)
php artisan optimize             # cek cache produksi
```
PHP: pakai `evironment/php-8.4.*/php.exe` (set `PHPRC` ke folder itu) atau PHP 8.4 Laragon.

## Akhiri sesi
Jalankan `.agents/workflows/end-session.md`: perbarui progress/decisions/findings, tulis langkah berikutnya, commit `.agents/`.
