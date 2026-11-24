# Modul: OPS (launcher server, klien desktop)
Status: putaran I — menunggu ACC pemilik

## Launcher server (Windows, portable)
| File | Fungsi |
|---|---|
| `START-DMS-INDRACO.bat` | cek PHP/VC++/.env/vendor/cache/migrasi → cek port 8000 → bersihkan sisa launcher lama (`dms-stop.ps1 -Leftovers`) → banner → `dms-ready.ps1` (latar) + `dms-server.cmd` (pool php-cgi + Caddy, jendela ini) |
| `STOP-DMS-INDRACO.bat` | memanggil `evironment\dms-stop.ps1` |
| `evironment\dms-stop.ps1` | **R18**: memilih proses sekali (snapshot) lalu hanya membunuh & menunggu proses itu (handle proses, kebal PID dipakai ulang): jendela launcher folder ini (`cmd` yang menjalankan `<folder>\START-DMS-INDRACO.bat`, termasuk mode silent), loop php-cgi, php-cgi & Caddy dari `<folder>\evironment`, `dms-ready.ps1`, pemegang port 8000 saat STOP dimulai, dan turunan (hanya cmd/conhost/php/php-cgi/caddy/powershell/ping/timeout). Tidak membunuh diri sendiri / pemanggilnya (UPDATE-AND-RESTART). Tanpa `taskkill` per judul jendela. `-Leftovers`: hanya loop/php-cgi/dms-ready sisa (dipakai START) |
| `evironment\dms-ready.ps1` | tunggu PHP + panaskan worker → "SIAP" / "GAGAL: PHP tidak merespons" (startup.log) |
| `UPDATE-AND-RESTART.bat` | git pull → composer → optimize:clear → migrate → STOP → START |
| `tests\ops\dms-stop.check.ps1` | cek mandiri dms-stop (folder temp + exe palsu; aman saat server asli jalan) |

### R18 (STOP → START cepat gagal)
- Akar: STOP lama mencari ulang proses berdasarkan judul jendela / path / port **berkali-kali** (taskkill judul `/t`, 2 putaran pembunuhan, loop tunggu "masih ada php-cgi/caddy folder ini" sampai 15 dtk, lalu bunuh pemegang port 8000). Proses server baru yang muncul selama STOP masih berjalan ikut dihitung: loop tunggu menunggu proses **baru** sampai 15 dtk lalu membunuh Caddy baru di port 8000 → jendela START baru kehilangan server, `dms-ready` → "GAGAL". `taskkill` per judul juga menyasar jendela DMS folder lain / Windows Terminal. Selain itu `dms-ready.ps1` lama yang selamat (mode silent: judul tidak cocok) bisa menulis "GAGAL" ke `startup.log` milik START baru.
- Perbaikan: snapshot sekali + handle proses (`dms-stop.ps1`); START membersihkan sisa pool/`dms-ready` lama setelah port 8000 dipastikan kosong.

## Klien desktop (KEEP, keputusan 2026-11-24)
| File | Isi |
|---|---|
| `desktop/` | Tauri v2 (`src/index.html`, `src/app.js`, `src-tauri/`), `server_url.txt` (`http://192.168.2.180:8000`), `README.md` (build, distribusi, sertifikat) |
| `DMS-Indraco-Client.exe` (root, build 16-10-2026) dan `desktop/DMS-Indraco-Client.exe` (build 21-10-2026, + menu Update/Reset) | biner dari L7; keduanya default `http://192.168.2.180:8000` |
| `START-CLIENT-WORKSTATION.bat/.sh` (root) | buka `desktop/src/index.html` di Chrome/Edge `--app` (tanpa exe) + pintasan desktop ke `DMS-Indraco-Client.exe` root. Salinan di `desktop/` dari L7 **tidak dibawa** (path `desktop/desktop/src` rusak) |
| `.github/workflows/build-clients.yml` | build exe Windows + deb/AppImage (manual / tag `v*`) |
| `INSTALL-SERTIFIKAT-KLIEN.bat` | pasang root CA HTTPS per user |

Kontrak server: `GET /api/health/ping` 200 JSON `status, server_time (ms; L7 = detik float), server_ip, client_ip, app` + `Access-Control-Allow-Origin: *` (Caddy & Laravel; dites `tests/Feature/Ops/PingContractTest.php`). Klien hanya memakai `response.ok` + JSON valid; `server_name`/`version` L7 tidak dipakai. Rust `lib.rs` memantau server dengan koneksi TCP ke host:port halaman aktif (6 dtk × 4 gagal → layar sambung ulang).

Tidak dibawa: `START-DMS-INDRACO.exe` (L7, `scratch/Launcher.cs`: hanya `cmd /c START-DMS-INDRACO.bat`) — pakai `.bat`/`.vbs`.

## Sisa
- Build ulang exe (versi naik) agar preset HTTPS + pemeriksaan update tanpa error ikut; default URL ke `https://192.168.2.180:8443` setelah semua klien memasang sertifikat (keputusan 2026-11-24).
- Satu lokasi exe saja (root atau `desktop/`) setelah build berikutnya.
