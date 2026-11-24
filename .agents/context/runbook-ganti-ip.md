# Runbook: ganti IP server

IP server saat ini: **`192.168.2.180`** (tetap, di-set pemilik). Jika pemilik meminta ganti IP, ubah **semua** tempat di bawah, lalu catat di `decisions.md`.

## Server (Laravel 13, folder ini)
| Tempat | Isi | Catatan |
|---|---|---|
| `SERVER_LAN_IP` di `.env` (default di `.env.example`) | IP baru | dibaca `evironment/dms-server.cmd` → `DMS_TLS_SITES` di Caddyfile |
| Sertifikat HTTPS | otomatis dibuat ulang saat `START-DMS-INDRACO.bat` dijalankan (SAN memuat IP baru) | Root CA di `storage/caddy/` **tidak berubah**, jadi klien **tidak perlu** memasang ulang sertifikat |
| `APP_URL` di `.env` | `https://<ip-baru>:8443` jika dipakai | URL di halaman mengikuti request, jadi ini hanya untuk link di luar request (mis. log) |
| `.agents/context/project-overview.md`, file ini | IP baru | dokumentasi |

## Klien
| Tempat | Isi | Catatan |
|---|---|---|
| `desktop/src/app.js` | `DEFAULT_SERVER_URL`, label "Server Pusat Kantor" di `getRouteLabel()` | butuh build ulang exe (`.github/workflows/build-clients.yml`) |
| `desktop/src/index.html` | `data-url` preset "Server Pusat Kantor" (`http://<ip>:8000`) dan "(Aman / HTTPS)" (`https://<ip>:8443`), placeholder input | ikut build ulang exe; `START-CLIENT-WORKSTATION.bat` (mode browser) memakai file ini langsung |
| `desktop/README.md` | contoh alamat | dokumentasi |
| `START-CLIENT-WORKSTATION.bat` (root) | `SERVER_URL=` default | variabel ini belum dipakai UI (UI = `desktop/src/index.html` + `app.js`/localStorage) — ganti agar konsisten; `.sh` tidak menyimpan IP |
| `server_url.txt` / `desktop/server_url.txt` | alamat server (`desktop/server_url.txt` = `http://192.168.2.180:8000`) | dibaca `START-CLIENT-WORKSTATION.bat` dan `INSTALL-SERTIFIKAT-KLIEN.bat` |
| `INSTALL-SERTIFIKAT-KLIEN.bat` | tidak ada IP tetap: dari `server_url.txt`, atau ditanyakan | |
| Klien yang sudah terpasang | menu **Ubah Alamat Server** di aplikasi exe, atau ganti pintasan/PWA ke alamat baru | PWA terpasang terikat ke alamat lama → instal ulang dari alamat baru |

## Setelah mengganti
1. Jalankan `START-DMS-INDRACO.bat` di server, cek banner menampilkan IP baru.
2. Dari PC klien: buka `https://<ip-baru>:8443` (sertifikat valid, tanpa peringatan) dan `http://<ip-baru>:8000`.
3. Uji aplikasi exe dengan alamat baru (ping hijau).
