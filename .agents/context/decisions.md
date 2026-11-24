# Keputusan

tanggal | keputusan | alasan | disetujui oleh
---|---|---|---
2026-11-24 | Rebuild ke Laravel 13 di branch `laravel-13-rebuild` (worktree `indraco-arsip-laravel-13`) | UI lama ramai/tidak konsisten/tidak responsif; Laravel 7 tidak lagi mendapat update keamanan | pemilik
2026-11-24 | Caddy + pool php-cgi menggantikan `artisan serve` | `artisan serve` di Windows memproses satu request sekali jalan; diukur: request 3 dtk membuat user lain menunggu 3 dtk | pemilik ("yang penting aman diakses banyak komputer")
2026-11-24 | UI: Blade + Alpine.js + Tailwind 4 (Vite, build lokal), tanpa SPA | paling ringan untuk PC low-spec, tanpa Node di server | default Claude, belum dikonfirmasi eksplisit
2026-11-24 | Skema database tetap sama persis | `database.sqlite` lama langsung dipakai | default Claude, belum dikonfirmasi eksplisit
2026-11-24 | UX tetap sama: alur, langkah, fitur, **komposisi form** (jumlah input, wajib/opsional) | permintaan pemilik | pemilik
2026-11-24 | UI baru: sederhana, modern, ringan untuk PC low-spec, 100% lokal tanpa CDN | permintaan pemilik | pemilik
2026-11-24 | Tanpa paket SEO; paket Security & Performance tetap wajib | aplikasi internal LAN | pemilik
2026-11-24 | Launcher tetap `START-DMS-INDRACO.bat` dkk (nama sama) | user sudah terbiasa | pemilik
2026-11-24 | Aturan `PROJECT_RULES*.md` lama tetap jadi kontrak teknis, diperbarui untuk L13 | aturan yang sudah disepakati sebelumnya | pemilik
2026-11-24 | Temuan wajib dicatat dan diperbaiki; setiap modul diuji di browser (remote) | permintaan pemilik | pemilik
