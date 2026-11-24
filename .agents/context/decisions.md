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
2026-11-24 | **Risiko diterima:** password default `password` pada akun lama dibiarkan; tidak ada paksaan ganti password (ASK 17 = tidak). Mitigasi di rebuild: rate limit login, log login gagal, form login tidak diisi otomatis, menu ganti password mudah dijangkau | user mengganti sendiri nanti | pemilik
2026-11-24 | **Risiko diterima:** Laravel 7 tidak di-hotfix (termasuk temuan #2 file/backup bisa diunduh tanpa login); semua perbaikan di rebuild. Risiko tetap ada sampai cutover | pemilik ingin fokus rebuild yang portable | pemilik
2026-11-24 | Arsitektur dan security dirapikan di rebuild; Claude wajib proaktif menyampaikan temuan dan risiko dari setiap permintaan | permintaan pemilik | pemilik
2026-11-24 | ASK 1: tampilan MDI iframe gaya Delphi diganti halaman biasa dengan menu yang sama | sumber utama UI berat/membingungkan | pemilik (ikut saran)
2026-11-24 | ASK 2–3: penarikan tetap permanen; satu jalur keluar box (`borrowings/dispatch`) | sesuai perilaku yang berjalan | pemilik (ikut saran)
2026-11-24 | ASK 4: persetujuan departemen untuk penarikan (`dept-approve`) tidak dibawa | 0 pemakaian, tombol hanya admin | pemilik (ikut saran)
2026-11-24 | ASK 5: impor Excel multi-box PIC dibawa, dikerjakan paling akhir | belum dipakai PIC, tetapi berguna | pemilik (ikut saran)
2026-11-24 | ASK 6: impersonate dibawa, khusus admin, tercatat di log | | pemilik (ikut saran)
2026-11-24 | ASK 7: hapus massal (log/box/arsip) dibawa, khusus admin, konfirmasi + backup otomatis sebelum hapus | | pemilik (ikut saran)
2026-11-24 | ASK 8, 16: update dari web (git pull + restart) dan `UPDATE-AND-RESTART.bat` dibawa, khusus admin | | pemilik (ikut saran)
2026-11-24 | ASK 9: Quick Notes publik dibuang | tanpa login, tidak dipakai sebagai fitur | pemilik (ikut saran)
2026-11-24 | ASK 10: 21 backup lama di `storage/app` dipindah ke folder backup di luar jangkauan web saat cutover | | pemilik (ikut saran)
2026-11-24 | ASK 11: data uji `BOX-TEST-*` dibersihkan saat cutover, setelah backup | | pemilik (ikut saran)
2026-11-24 | ASK 12: aturan FAT cukup berdasarkan kode departemen | nama user tidak andal | pemilik (ikut saran)
2026-11-24 | ASK 13: ukuran font per user | | pemilik (ikut saran)
2026-11-24 | ASK 14: kolom DB tak terpakai dibiarkan | kompatibilitas database lama | pemilik (ikut saran)
2026-11-24 | ASK 15: alat k6 dibawa (lokal); `.exe` hanya jika masih dipakai klien | | pemilik (ikut saran)
2026-11-24 | Klien membuka DMS dengan **dua cara yang sama-sama didukung**: (a) aplikasi `DMS-Indraco-Client.exe` (Tauri, folder `desktop/`, WebView2) dan (b) langsung lewat browser (`http://<ip>:8000`, Chrome/Edge/Firefox). Aplikasi exe dibawa (KEEP). Kontrak yang wajib dijaga: `GET /api/health/ping`, `server_url.txt`, command `reset_app_cache_and_restart`. Klien dan server sama-sama low-spec (RAM 4–8 GB, ≤ 2 core, tanpa GPU) | aplikasi exe dibuat untuk PC klien low-spec | pemilik
2026-11-24 | **Semua tampilan** (±139 view L7, ketiga role) dibangun ulang dengan design system baru: PIC Departemen (Fase 4), PIC Gudang & Admin (Fase 5) | permintaan pemilik | pemilik
2026-11-24 | **Hasil cetak** (label/stiker box, BAP, form perpanjangan, laporan cetak): ukuran, tata letak, dan isi tetap sama persis dengan L7; kode dirapikan; dibandingkan berdampingan dengan versi lama | dokumen resmi/ukuran label fisik | default Claude — ubah jika pemilik minta desain cetak baru
2026-11-24 | Denah gudang 2D dibangun ulang dengan fitur & alur sama, wajib lancar di klien tanpa GPU (CPU throttling 4×) | tampilan terberat | default Claude
2026-11-24 | **HTTPS di LAN + PWA**: Caddy `tls internal` di `:8443` (sertifikat untuk localhost, IP LAN, hostname), `http://:8000` tetap sebagai cadangan selama transisi. Root CA disimpan di folder aplikasi (persisten, di-ignore git) dan dipasang sekali per klien lewat `INSTALL-SERTIFIKAT-KLIEN.bat`. PWA (manifest + service worker yang hanya meng-cache aset statis build, tidak meng-cache data/halaman dinamis) dibangun di modul SHELL. Paksa HTTPS (redirect + cookie `secure`) baru setelah semua klien memercayai sertifikat | bisa diinstal/di-pin sebagai web app, aset di-cache di klien low-spec, password/data tidak lagi terkirim tanpa enkripsi di LAN | pemilik
2026-11-24 | IP server tetap `192.168.2.180` (sudah di-set pemilik) — selalu masuk SAN sertifikat, disimpan di satu tempat konfigurasi. Default URL klien exe nanti diganti ke `https://192.168.2.180:8443` (perlu build ulang exe lewat `.github/workflows/build-clients.yml`; klien lama tetap bisa pakai http:8000 atau ubah alamat lewat modal) | | pemilik
2026-11-24 | Risiko HTTPS LAN dicatat: tiap klien harus memercayai root CA sekali (Firefox perlu langkah terpisah); root CA ikut folder aplikasi — jika folder diinstal ulang tanpa `storage/caddy`, klien harus memasang ulang sertifikat (backup folder itu) | | pemilik
2026-11-24 | Target render UI = WebView2 (aplikasi exe) **dan** browser Chrome/Edge/Firefox di PC klien. Versi minimum harus dicek sebelum Fase 3 (WebView2 + browser yang dipakai); jika ada yang di bawah Chrome/Edge 111 atau Firefox 128, CSS dibuat kompatibel (Tailwind 3.4 atau target build lebih lama) | Tailwind 4 butuh Chromium ≥ 111 / Firefox ≥ 128; PC offline bisa punya browser lama | menunggu data versi dari pemilik
2026-11-24 | ASK lain di `triage.md` (tujuan `app/Bootstrap/HandleExceptions`, test lama yang menulis DB nyata): diputuskan saat triage modul terkait, default tidak dibawa | | pemilik (ikut saran)
