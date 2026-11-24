---
description: Bangun satu modul dari triage sampai commit
---

1. Baca `context/progress.md`, pilih modul berikutnya.
2. Ambil item KEEP/REWRITE modul itu dari `context/triage.md` dan inventaris role.
3. Baca kode Laravel 7 sampai aturan bisnis, kasus tepi, dan komposisi form jelas; tulis ringkasannya di `context/modules/<modul>.md`.
4. Tulis tes perilaku (status, redirect, validasi, perubahan DB, otorisasi, tes negatif security).
5. Bangun sesuai `rules/20-architecture.md` dan `rules/50-frontend-blade.md`.
6. Jalankan workflow `verify`.
7. Commit lokal kecil (Conventional Commits). Perbarui `context/progress.md`, `decisions.md`, `findings.md`.
8. **STOP**: laporkan hasil uji browser + tes + temuan, tunggu ACC pemilik.
