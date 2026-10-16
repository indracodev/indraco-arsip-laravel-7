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

### A. Di Komputer Windows:
1. **Aplikasi Native (.exe):**
   Cukup klik 2x:
   ```
   desktop/DMS-Indraco-Client.exe
   ```
   *(Bisa juga diletakkan shortcut di Desktop komputer).*

2. **Launcher Skrip Cadangan (.bat):**
   ```
   START-CLIENT-WORKSTATION.bat
   ```

---

### B. Di Komputer Linux (Ubuntu, Debian, Fedora, Mint, Arch):
1. **Launcher Skrip Portabel (Langsung Jalan Tanpa Compile):**
   Jalankan skrip bash:
   ```bash
   chmod +x START-CLIENT-WORKSTATION.sh
   ./START-CLIENT-WORKSTATION.sh
   ```
   *Skrip ini otomatis mendeteksi Chrome/Chromium/Brave/Edge/Firefox dan membukanya dalam **Standalone App Window** (tanpa address bar/tab, seperti native app), serta menyediakan opsi pembuatan shortcut `.desktop` ke menu aplikasi Linux.*

2. **Native Standalone Linux (AppImage / .deb):**
   Untuk mengompilasi binary native Linux dari source code ini:
   ```bash
   # Di komputer Linux:
   sudo apt-get install -y libwebkit2gtk-4.1-dev build-essential curl wget file libxdo-dev libssl-dev libayatana-appindicator3-dev librsvg2-dev
   cd desktop
   npm install
   npm run build
   ```
   Hasil build `.AppImage` dan `.deb` akan terbentuk di direktori `desktop/src-tauri/target/release/bundle/`.

3. **Build Otomatis via GitHub Actions:**
   Alur CI telah disiapkan di `.github/workflows/build-clients.yml`. Setiap pembuatan tag rilis di GitHub, installer Windows (.exe) dan Linux (.AppImage & .deb) akan di-build dan siap diunduh otomatis.

