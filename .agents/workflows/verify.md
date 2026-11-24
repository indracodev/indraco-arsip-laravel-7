---
description: Verifikasi sebelum commit modul atau STOP
---

1. `composer validate` dan `composer audit` (0 high/critical).
2. `php artisan route:list` (tanpa closure), lalu `php artisan optimize` dan `php artisan optimize:clear` sukses.
3. `php artisan test` lulus (lampirkan output asli), termasuk tes negatif security modul terkait.
4. Parity: bandingkan perilaku dengan Laravel 7 (status, data tersimpan, nomor box, pesan) dan **komposisi form** (jumlah input, wajib/opsional, pilihan, default).
5. Uji di browser (Chrome lewat Claude in Chrome, server dari launcher): jalankan alur modul sampai selesai, lebar 360/768/1366 px, console bersih.
6. `storage/logs` bersih setelah pengujian.
7. Modul yang menulis database: uji beban singkat (k6 office/multiuser), catat di `context/performance-baseline.md`.
8. Performance Gate Checklist `rules/PROJECT_RULES.md` §9 lulus.
9. Semua temuan dicatat di `context/findings.md` dan diperbaiki (atau alasan tertunda di `context/decisions.md`).
