---
name: portable-runtime
description: Runtime portable Windows DMS Indraco — PHP 8.4 NTS + DLL VC++ di evironment/, Caddy + pool php-cgi, launcher START/STOP/UPDATE-AND-RESTART.bat dan START-SILENT-DESKTOP.vbs, update lewat git pull, uji di PC bersih. Pakai saat mengubah launcher, Caddyfile, php.ini, atau cara deploy.
---

# Runtime portable

Aturan: `PROJECT_RULES.md` §2, `PROJECT_RULES_LAN_FRONTEND_OPS.md` §4–§7, `10-safety.md`.

## Isi (setelah Fase 1 — lengkapi detail dari hasil Fase 1)
- `evironment/php-8.4.28-nts-Win32-vs17-x64/` + `php.ini` + `vcruntime140.dll`, `vcruntime140_1.dll`, `msvcp140.dll` (tanpa install VC++).
- `evironment/caddy/caddy.exe` (2.11.9) + `Caddyfile`.
- Pool php-cgi `127.0.0.1:9001..900N` dengan pengawas restart otomatis.
- Launcher: `START-DMS-INDRACO.bat` (utama), `STOP-DMS-INDRACO.bat`, `UPDATE-AND-RESTART.bat`, `START-SILENT-DESKTOP.vbs`, `START-CLIENT-WORKSTATION.bat` (klien).

## Pelajaran dari Laravel 7 (jangan diulang)
- Server tanpa VC++ Redistributable → `VCRUNTIME140.dll not found`; `intl` butuh `msvcp140.dll`. Cek dependensi dengan `deplister.exe` di folder PHP.
- `.env.example` pernah MySQL → migrasi gagal di PC baru. Default wajib SQLite.
- Skrip yang memanggil `php` dari PATH gagal di PC tanpa Laragon. Semua skrip pakai PHP dari `evironment`.
- File `.bat` wajib CRLF (`.gitattributes`: `*.bat text eol=crlf`).
- OPcache di Windows bisa fatal "ASLR" saat beberapa proses: `opcache.file_cache` + `file_cache_fallback=1`.
- File DLL yang sedang dipakai php tidak bisa dihapus (`git pull` gagal) → STOP server dulu.

## Uji wajib setelah mengubah runtime
1. Clone bersih ke folder baru, PATH hanya `C:\Windows\System32` → jalankan launcher → `vendor` terpasang, `.env` SQLite, migrasi, `http://127.0.0.1:8000` dan `http://<IP>:8000` membalas 200.
2. STOP → tidak ada `caddy.exe`/`php-cgi.exe` tersisa.
3. Uji beban k6 `office` dan `multiuser`, bandingkan dengan `context/performance-baseline.md`.
4. Uji di PC/VM Windows 10 tanpa VC++ bila tersedia.
