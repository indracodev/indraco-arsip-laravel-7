---
trigger: glob
globs: app/**, resources/**, routes/**, tests/**, config/**, database/**
---

# Ukuran File & Modularitas (Laravel 13) — tidak ada file gemuk

## 1. Batas

**Hard limit**: tidak ada file kode aplikasi ≥ 800 baris (dicek otomatis, gagal = tidak boleh commit).

**Target** per jenis file (melebihi target wajib diberi alasan di PR/commit, atau dipecah):

| Jenis | Target | Catatan |
|---|---|---|
| Controller | ≤ 150 baris, ≤ 7 method publik | resource controller; aksi khusus → controller terpisah (single action `__invoke`) |
| Action | ≤ 120 baris, 1 method publik (`handle`) | satu use case |
| Service | ≤ 250 baris | satu kapabilitas |
| Form Request | ≤ 100 baris | |
| Model | ≤ 200 baris | relasi, cast, scope; logika bisnis ke Action |
| Policy | ≤ 120 baris | |
| Blade halaman | ≤ 250 baris | sisanya ke partial/komponen |
| Blade komponen | ≤ 120 baris | |
| JS modul (`resources/js`) | ≤ 200 baris | satu modul per fitur halaman |
| Test file | ≤ 400 baris | per fitur; helper ke trait `tests/Concerns` |
| Method/fungsi | ≤ 40 baris | lebih dari itu pecah ke method privat bernama jelas |

Pengecualian: `vendor/`, `node_modules/`, `public/build/`, migrasi baseline hasil generate, file binary/sqlite.

## 2. Cara memecah (kohesi, bukan potong asal)

- **Controller** gemuk → pisah per sub-fitur (mis. `ArchiveController`, `ArchiveSubmissionController`, `ArchiveLabelController`, `ArchiveImportController`); logika ke `Actions/`.
- **Trait di controller tidak dipakai** di L13 (L7 memakai `Controllers/Traits/Handles*`); gantinya Action/Service yang bisa dites sendiri.
- **Blade** gemuk → layout + partial per bagian (`<fitur>/partials/`, `<fitur>/modals/`) + Blade component untuk elemen berulang.
- **Alpine/JS** panjang → modul di `resources/js/pages/<fitur>.js` (`Alpine.data('namaFitur', ...)`), tidak inline di Blade.
- **Service** monolitik → sub-service per kapabilitas.
- **Test** besar → file per skenario/fitur, setup bersama di trait.
- Jangan memecah berlebihan: file mikro tanpa tanggung jawab jelas juga dilarang (`engineering_guidelines.md` §3).

## 3. Verifikasi

- `php artisan app:scan-lines` (atau `php tools/scan_lines.php` sampai command tersedia) → `files >= 800: 0`, dan daftar file yang melewati target.
- `vendor/bin/pint --test` bersih.
- `vendor/bin/phpstan analyse` (Larastan) tanpa error baru dibanding baseline.
- `php artisan test` lulus.
