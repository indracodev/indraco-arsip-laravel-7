---
trigger: always_on
---

# Keamanan kerja

- Folder Laravel 7 hanya dibaca. Database aslinya (`indraco-arsip-laravel-7\database\database.sqlite`) hanya dibuka read-only; untuk uji selalu pakai **salinan**.
- Jangan push, force-push, atau mengubah remote tanpa izin eksplisit. Jangan menampilkan URL remote.
- Jangan commit: `.env`, file `*.sqlite`, dump, backup `.env` (Laravel 7 pernah meng-commit `backup_env.txt` berisi `APP_KEY`), data pengguna, file upload.
- `migrate:fresh`, `db:wipe`, `DROP` hanya pada database uji di dalam folder ini, tidak pernah pada salinan data asli yang sedang dipakai untuk parity.
- Rahasia hanya di `.env`, dibaca lewat `config()`. Tidak ada `env()` di luar folder `config/`.
- `.agents/` ikut di-commit dan tidak boleh berisi rahasia.

## Menghapus file

- **DILARANG** menghapus massal dengan loop dari output `ls` (Git Bash menambahkan `/` di nama folder sehingga daftar pengecualian meleset — pernah menghapus `.agents/` yang belum di-commit).
- Hapus hanya nama yang disebut eksplisit atau dari `git ls-files`. `.agents/`, `.git/`, `template-import-master/` tidak pernah ikut dihapus atau dipindah.
- Commit file `.agents/` segera setelah ditulis agar tidak hilang.

# Batasan runtime (portable Windows)

- Tidak ada install di PC tujuan: PHP, Caddy, DLL VC++ ikut di folder `evironment\`.
- Tidak ada layanan yang harus jalan terus selain Caddy + pool php-cgi yang dijalankan launcher. Tidak ada Redis, Horizon, Octane, Supervisor, Node di server.
- Queue `sync`. Pekerjaan terjadwal (backup, pembersihan) lewat launcher atau tombol admin, bukan cron.
- Aset front-end di-build di laptop developer (Vite) dan hasil build ikut di-commit.
- PC bisa offline: tidak ada CDN. Semua font, ikon, dan library JS disajikan lokal.
