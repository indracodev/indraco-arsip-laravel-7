# Runbook: uji beban DMS L13 di PC server asli (baca + tulis)

Untuk pemilik. Tujuan: mengukur L13 di server sebenarnya (Windows 10, 2 core, 4–8 GB, IP `192.168.2.180`, port `8000`) dengan beban baca **dan** tulis, lalu mengembalikan database seperti semula. Server L7 produksi juga memakai port 8000 → lakukan di **jam pemeliharaan** (tidak ada pengguna).

> **Peringatan**
> - Folder yang disalin dari laptop membawa **database uji laptop** (`database\database.sqlite`), bukan data produksi terbaru. Itu sengaja untuk uji beban. Untuk **cutover sungguhan** pakai database L7 terbaru sesuai `cutover-plan.md`, jangan database hasil uji ini.
> - Uji tulis **membuat data** (judul berawalan `LOADTEST-<RUN_ID>-`, file PDF uji, sesi login). Jangan mulai sebelum langkah SNAPSHOT berhasil, dan selalu akhiri dengan RESTORE.
> - Jangan jalankan k6 di PC server: k6 ikut makan CPU dan hasilnya tidak berarti. k6 dijalankan dari laptop.
> - Folder L7 dan database L7 tidak disentuh sama sekali oleh langkah di bawah.

## Persiapan (sebelum hari H)
1. Salin seluruh folder `indraco-arsip-laravel-13` ke server (mis. `D:\indraco-arsip-laravel-13`). Jangan menimpa folder L7.
2. Periksa `.env` di folder salinan:
   - `SERVER_LAN_IP=192.168.2.180`
   - `DMS_ALLOWED_HOSTS=` boleh kosong. Bila k6/browser memakai alamat lain (nama PC lain, IP kedua), tambahkan di sini (koma). Tanpa itu server menjawab **400** dan seluruh uji gagal.
3. Di laptop penguji: k6 terpasang (`k6 version`), folder project ada (berisi `tests\k6\`). Laptop dan server di jaringan yang sama: `ping 192.168.2.180`.

## Langkah di jam pemeliharaan

| # | Di mana | Langkah |
|---|---|---|
| 1 | Server | Hentikan L7: jalankan `STOP-DMS-INDRACO.bat` **di folder L7**. Pastikan `http://192.168.2.180:8000` tidak menjawab. |
| 2 | Server | Jalankan `START-DMS-INDRACO.bat` **di folder L13 salinan**. Tunggu banner SIAP. Buka `http://192.168.2.180:8000` dari laptop → halaman login. |
| 3 | Server | Jalankan `UJI-BEBAN-1-SNAPSHOT.bat` (folder L13). Harus muncul `Snapshot dibuat: ...storage\app\snapshots\<tanggal_jam>` dan jumlah baris. Bila gagal: **jangan** lanjut ke uji tulis. |
| 4 | Server | Mulai pencatat CPU/RAM (PowerShell di folder L13, biarkan jalan selama uji): lihat perintah di bawah. |
| 5 | Laptop | Uji baca (2 perintah, lihat di bawah). |
| 6 | Laptop | Uji tulis (lihat di bawah). Catat `RUN_ID=...` yang dicetak di awal dan akhir. |
| 7 | Server | `UJI-BEBAN-2-VERIFIKASI.bat` → isi RUN ID (kosong = semua run). Harus berakhir `OK: nomor box unik tanpa lompatan, tidak ada baris yatim.` |
| 8 | Server | Kumpulkan hasil (lihat "Yang dikirim balik"). |
| 9 | Server | `STOP-DMS-INDRACO.bat` **di folder L13**. |
| 10 | Server | `UJI-BEBAN-3-RESTORE.bat`, ketik `YA`. Harus muncul `Restore selesai, jumlah baris semua tabel sama dengan snapshot.` + jumlah file upload uji yang dipindahkan dan sesi yang dihapus. |
| 11 | Server | (opsional, bukti) `UJI-BEBAN-2-VERIFIKASI.bat` lagi → `Arsip LOADTEST-%: 0`. |
| 12 | Server | Jalankan lagi L7: `START-DMS-INDRACO.bat` **di folder L7**. Cek dari satu PC klien bahwa L7 terbuka seperti biasa. |

### Perintah langkah 4 (server, PowerShell di folder L13)
```powershell
$ids = (Get-CimInstance Win32_Process | Where-Object { $_.Name -in 'php-cgi.exe','caddy.exe' -and $_.ExecutablePath -like "$PWD\evironment\*" }).ProcessId -join ','
powershell -NoProfile -ExecutionPolicy Bypass -File tests\k6\sample-resources.ps1 -Pids $ids -Seconds 1500 -Out uji-server-sumberdaya.csv
```
Kolom `cpu_pct` = persen dari **satu** core (200 = 2 core penuh).

### Perintah langkah 5 (laptop, di folder project)
```bat
k6 run -e TARGET_URL=http://192.168.2.180:8000 -e LEVELS=1,10,30 -e HOLD=25 -e OUT=baca-closed-PC1.json tests\k6\route_matrix.js
k6 run -e TARGET_URL=http://192.168.2.180:8000 -e MODE=human -e LEVELS=10,30,50 -e HOLD=40 -e OUT=baca-human-PC1.json tests\k6\route_matrix.js
```
Hanya GET. ±2 + ±3 menit.

### Perintah langkah 6 (laptop)
```bat
k6 run -e TARGET_URL=http://192.168.2.180:8000 -e LEVELS=5,10,20 -e HOLD=45 -e OUT=tulis-PC1.json --summary-export=tulis-PC1-summary.json tests\k6\write_flow.js
```
- ±5 menit (tiap level 45 dtk + jeda sampai iterasi yang sedang jalan selesai).
- Akun: PIC `fin, hrd, hcs, dm, mkt` (`@indraco.com`) + `gudang@indraco.com`, password default seeder. Bila password berbeda: `-e PASSWORD=... -e PIC_PASSWORD=...`; akun lain: `-e PICS=fin,hrd -e GUDANG=...`.
- Tiap VU = 1 PIC + 1 PIC Gudang dengan sesi sendiri: ajukan 2 box (+ PDF 50–200 KB) → verifikasi + nomor box → check-in rak → penarikan (dikeluarkan / 1 dari 3 ditolak) → perpanjangan + ACC → usul pemusnahan + setujui + eksekusi BAP.
- Hasil baik: kolom `sukses %` 100, `HTTP 5xx: 0`, `HTTP 419: 0`.

## Uji dari 2 PC
Bila satu laptop tidak cukup kuat (CPU laptop > ±70 % selama uji = laptop yang jadi batas, bukan server):
1. Salin folder `tests\k6\` ke PC kedua; pasang k6 di sana (`k6 version`). Jangan pernah pakai PC server sebagai PC penguji.
2. Bagi VU: tiap PC setengah. Contoh tulis: kedua PC `-e LEVELS=10,15,25` → total 20 / 30 / 50. Baca mode human: kedua PC `-e LEVELS=5,15,25` → total 10 / 30 / 50.
3. Tiap PC memakai nama file sendiri dan RUN_ID sendiri (otomatis acak; boleh diberi `-e RUN_ID=pc1` / `pc2`):
   ```bat
   :: PC 1
   k6 run -e TARGET_URL=http://192.168.2.180:8000 -e LEVELS=10,15,25 -e HOLD=45 -e RUN_ID=pc1 -e OUT=tulis-PC1.json --summary-export=tulis-PC1-summary.json tests\k6\write_flow.js
   :: PC 2
   k6 run -e TARGET_URL=http://192.168.2.180:8000 -e LEVELS=10,15,25 -e HOLD=45 -e RUN_ID=pc2 -e OUT=tulis-PC2.json --summary-export=tulis-PC2-summary.json tests\k6\write_flow.js
   ```
4. Mulai kedua PC dalam selisih beberapa detik (level berikutnya berjalan berdasarkan waktu sejak start). Login kedua PC berdiri sendiri; akun yang sama boleh dipakai bersamaan.
5. Selama uji, buka Task Manager di kedua PC penguji: CPU harus di bawah ±70 %.
6. Menggabungkan hasil per level: **jumlahkan** request/s, jumlah dibuat, 5xx dan 419; ambil **p95 yang paling buruk** dari kedua PC (jangan dirata-rata).
7. Verifikasi: `UJI-BEBAN-2-VERIFIKASI.bat` dengan RUN ID kosong → memeriksa kedua run sekaligus (nomor box unik dan tanpa lompatan di seluruh run). RESTORE langkah 10 menghapus hasil kedua PC sekaligus.

## Yang dikirim balik
- Dari laptop/PC penguji: semua `baca-*.json/.md`, `tulis-*.json/.md`, `*-summary.json`, dan teks layar k6 (salin semua isi jendela).
- Dari server: `uji-server-sumberdaya.csv`, folder `storage\logs\` (file hari itu: `laravel-*.log`, `php-cgi-*.log`, `caddy*.log`), layar hasil langkah 3, 7, 10, 11, dan spesifikasi server (CPU, RAM: `systeminfo`).
- Catatan: jam mulai/selesai tiap perintah, apakah ada pesan merah, CPU laptop saat uji.

## Bila terjadi masalah
- **Restore menolak "port 8000 masih dipakai" / "proses server DMS masih berjalan"**: jalankan `STOP-DMS-INDRACO.bat` di folder L13, tunggu 5 dtk, ulangi.
- **Restore salah snapshot / ingin membatalkan restore**: kondisi sebelum restore tersimpan di `storage\app\snapshots-sebelum-restore\<tanggal_jam>\` (database + `removed-files\` berisi file upload yang dipindahkan). Kembalikan dengan `php artisan dms:restore-snapshot --snapshot="<folder itu>"` lalu salin balik isi `removed-files\` ke `storage\app\`.
- **400 Bad Request** di semua halaman: alamat yang dipakai k6/browser tidak ada di `SERVER_LAN_IP` / `DMS_ALLOWED_HOSTS` `.env` → perbaiki, STOP, START.
- **L7 tidak mau start**: pastikan L13 sudah STOP (port 8000 bebas: `netstat -ano | findstr :8000`).
- Sesudah selesai, folder `storage\app\snapshots*` di salinan L13 boleh dihapus (berisi salinan database uji).
