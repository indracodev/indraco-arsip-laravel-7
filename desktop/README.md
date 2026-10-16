# DMS PT Indraco - Desktop Client (Thin Client)

Aplikasi desktop native Windows (.exe) berbasis **Tauri v2** untuk mengakses Sistem Manajemen Arsip Digital (DMS) PT Indraco di jaringan LAN.

---

## 🚀 Fitur Utama

1. **Akses Server LAN Otomatis:**
   - Secara default terhubung ke server utama kantor: `http://192.168.2.180:8000`.
   - Menggunakan endpoint heartbeat cepat `<2ms`: `/api/health/ping`.

2. **Graceful Offline & Cached Shell:**
   - UI shell (logo, ikon, layout, font, dan dialog) tersimpan langsung di dalam file `.exe`.
   - Jika kabel LAN terputus atau server sedang mati, aplikasi **tidak crash atau menampilkan error browser**, melainkan menampilkan layar penanganan koneksi elegan dengan:
     - Indikator status server offline.
     - Auto-retry mundur 5 detik.
     - Tombol *Hubungkan Sekarang*.
     - Modal *Ubah Alamat Server* dengan fitur live ping tester.

3. **Preset Alamat Server:**
   - **LAN Kantor:** `http://192.168.2.180:8000`
   - **PC Ini:** `http://192.168.3.199:8000`
   - **Localhost:** `http://127.0.0.1:8000`
   - Disimpan di `localStorage` klien sehingga tidak hilang saat aplikasi ditutup.

4. **Pembaruan Otomatis via GitHub Releases:**
   - Terintegrasi dengan repositori: `https://github.com/indracodev/indraco-arsip-laravel-7`
   - Tombol *Cek Pembaruan GitHub* siap mendeteksi rilis versi terbaru jika ada update client `.exe`.

---

## 📂 Menjalankan Aplikasi

File executable siap pakai:
```
desktop/DMS-Indraco-Client.exe
```

Untuk mode development:
```bash
cd desktop
npm run dev
```

Untuk build installer release (.msi / .exe):
```bash
cd desktop
npm run build
```
