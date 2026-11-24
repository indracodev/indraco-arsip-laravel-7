# Runbook: ganti IP server

IP server saat ini: **`192.168.2.180`** (tetap, di-set pemilik). Jika pemilik meminta ganti IP, ubah **semua** tempat di bawah, lalu catat di `decisions.md`.

## Server (Laravel 13, folder ini)
| Tempat | Isi | Catatan |
|---|---|---|
| Konfigurasi IP server (dibuat Fase 1, mis. `SERVER_LAN_IP` di file konfigurasi launcher / `.env`) | IP baru | TODO Fase 1: tulis path pastinya di sini |
| Sertifikat HTTPS | otomatis dibuat ulang saat `START-DMS-INDRACO.bat` dijalankan (SAN memuat IP baru) | Root CA di `storage/caddy/` **tidak berubah**, jadi klien **tidak perlu** memasang ulang sertifikat |
| `APP_URL` di `.env` | `https://<ip-baru>:8443` jika dipakai | URL di halaman mengikuti request, jadi ini hanya untuk link di luar request (mis. log) |
| `.agents/context/project-overview.md`, file ini | IP baru | dokumentasi |

## Klien
| Tempat | Isi | Catatan |
|---|---|---|
| `desktop/src/app.js` | `DEFAULT_SERVER_URL`, label "Server Pusat Kantor" di `getRouteLabel()` | butuh build ulang exe (`.github/workflows/build-clients.yml`) |
| `desktop/README.md` | contoh alamat | dokumentasi |
| `desktop/START-CLIENT-WORKSTATION.bat` | `SERVER_URL=` default | |
| `server_url.txt` / `desktop/server_url.txt` | alamat server | dibaca launcher klien |
| `INSTALL-SERTIFIKAT-KLIEN.bat` | alamat unduh sertifikat (jika default di-hardcode) | |
| Klien yang sudah terpasang | menu **Ubah Alamat Server** di aplikasi exe, atau ganti pintasan/PWA ke alamat baru | PWA terpasang terikat ke alamat lama → instal ulang dari alamat baru |

## Setelah mengganti
1. Jalankan `START-DMS-INDRACO.bat` di server, cek banner menampilkan IP baru.
2. Dari PC klien: buka `https://<ip-baru>:8443` (sertifikat valid, tanpa peringatan) dan `http://<ip-baru>:8000`.
3. Uji aplikasi exe dengan alamat baru (ping hijau).
