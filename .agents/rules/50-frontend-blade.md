---
trigger: glob
globs: resources/views/**, resources/css/**, resources/js/**, public/**
---

# Front-end (Blade + Alpine.js + CSS murni, tanpa build tool)

## Arsitektur CSS
- **Tanpa Tailwind, tanpa Vite/Node.** CSS murni di `public/css/`, JS di `public/js/` (Alpine.js versi terbaru disimpan lokal di `public/js/vendor/`).
- `public/css/tokens.css`: semua design token sebagai variabel CSS (`--color-*`, `--space-*`, `--radius-*`, `--font-*`, `--size-control`). Nilai warna/ukuran **hanya** boleh dipakai lewat token.
- `public/css/base.css` (reset ringan + tipografi), `public/css/components/<komponen>.css` (satu file per komponen), `public/css/pages/<modul>.css` (hanya bila perlu, kecil).
- Dimuat dari satu partial `head-assets` di layout: **maksimal 4 file `<link>`**, tanpa `@import` berantai; URL ber-versi `?v=<hash isi file>` (helper Blade) supaya browser memuat versi baru setelah update dan Caddy bisa meng-cache panjang.
- Penamaan kelas konsisten gaya BEM: `.btn`, `.btn--primary`, `.btn--sm`, `.table`, `.table__row`, `.badge`, `.badge--menunggu`. Kelas dipakai lewat Blade component, bukan ditulis bebas di halaman.
- **Kompatibilitas** (klien low-spec, browser/WebView2 bisa lama): target Chrome/Edge ≥ 90, Firefox ≥ 88. **Dilarang**: `:has()`, CSS nesting, `@container`, `color-mix()`, `@layer`, `@property`, `oklch()`/`lab()`. Boleh: variabel CSS, flexbox, grid, `gap`, `clamp()`, `:focus-visible`, `aspect-ratio`.
- Ukuran: total CSS ≤ 60 KB (sebelum gzip ≤ 80 KB); tidak ada CSS yang tidak dipakai.
- Tanpa `style=""` inline kecuali nilai dinamis (mis. posisi di denah gudang 2D).


- **UX sama, tampilan baru.** Setiap halaman yang dibangun ulang punya langkah, isian (jumlah input, wajib/opsional, pilihan, default), aksi, dan pesan yang sama dengan Laravel 7 (lihat `context/triage.md` dan inventaris role).
- **Dua jalur klien, keduanya wajib jalan:** aplikasi `DMS-Indraco-Client.exe` (Tauri → WebView2) dan browser langsung (Chrome/Edge/Firefox ke `http://<ip>:8000`). Jangan memakai fitur yang tidak didukung versi minimum di `context/decisions.md`; uji di keduanya. Endpoint `/api/health/ping` dan alur `server_url.txt` milik klien tidak boleh rusak.
- **Ringan untuk PC klien low-spec** (RAM 4–8 GB, dual-core atau lebih lemah, **tanpa GPU** → browser merender grafis dengan CPU):
  - HTML dirender server; Alpine hanya untuk interaksi kecil. Tidak ada iframe (L7 memakai MDI iframe yang memuat ulang aset di setiap jendela).
  - **Dilarang**: `backdrop-filter`/blur, shadow besar/berlapis, gradient besar, animasi/transisi selain opacity/transform singkat (≤ 150 ms), elemen `position: fixed` bertumpuk, video/GIF dekoratif.
  - Pengecualian: **satu latar ambient statis pada area konten** (`--ambient` di `.main` dan halaman guest `.guest`; `body` tetap `--color-bg` polos): maks. 3 `radial-gradient` warna token, opasitas tiap glow ≤ 15% untuk slate/netral dan ≤ 12% untuk oranye brand, glow tidak bertumpuk, dan titik tergelapnya (token `ambient` di `tests/Unit/ContrastTest.php`) tetap WCAG AA untuk teks/`text-muted`/`accent` (4,5:1) dan cincin fokus (3:1). Tanpa animasi, tanpa `background-attachment: fixed`, tanpa blur/backdrop-filter, tanpa pseudo-element/lapisan tambahan per kartu atau komponen. Gradien lain tetap dilarang.
  - Warna: primer **slate gelap** `#262f3b`, aksen **oranye logo** `#fd4f00` hanya untuk penanda menu aktif/garis atas/sorotan kecil, fokus `#d04100`; netral bernuansa slate. Semua pasangan token wajib WCAG AA (dijaga `tests/Unit/ContrastTest.php`).
  - Font sistem (`system-ui, Segoe UI, ...`), tanpa web font. Ikon SVG kecil, hanya yang dipakai.
  - Anggaran per halaman: JS ≤ 150 KB, CSS ≤ 60 KB (gzip), total transfer awal ≤ 300 KB, elemen DOM ≤ 1.500, tabel maksimal 50 baris per halaman (pagination).
  - Polling (widget LAN ping) berhenti saat tab tidak terlihat (`visibilitychange`), interval ≥ 5 dtk; tidak ada polling lain di halaman biasa.
  - Gambar/scan: thumbnail kecil atau ikon di daftar; file asli hanya saat dibuka. `loading="lazy"`.
  - Pencarian/filter dikirim ke server (debounce ≥ 300 ms), bukan menyaring ribuan baris di browser.
  - Uji di browser dengan CPU throttling 4× (Chrome DevTools) dan lebar 1366 px: interaksi (klik, buka modal, ketik) terasa < 200 ms.
- Semua elemen UI lewat **Blade component** di `resources/views/components/` (masing-masing berpasangan dengan file CSS komponennya): button, input, select, textarea, checkbox, table, badge status, modal, modal konfirmasi, card, toast, empty-state, pagination. Tidak ada styling ad-hoc per halaman.
- Palet: netral (abu) + satu aksen (navy) + warna status (hijau/kuning/merah/biru) **hanya** untuk status. Teks dasar 14px, kontrol 36px, tabel compact.
- Teks minimal: label singkat; penjelasan panjang di tooltip/help, bukan paragraf di halaman.
- Responsif mobile-first: tabel jadi kartu di bawah 1024 px, **menu atas** (top nav, satu-satunya tata letak) jadi drawer di bawah 1024 px. Diuji di lebar 360px, 768px, 1366px.
- Aksesibilitas dasar: label untuk setiap input, fokus terlihat, kontras cukup, tombol bisa dipakai keyboard.
- Keamanan output: `{{ }}` sebagai default; `{!! !!}` hanya untuk HTML yang disanitasi, dengan alasan tertulis. Data ke JS lewat `@js()`/`Js::from`. Tidak memakai `innerHTML` dengan data pengguna.
- Form: CSRF, method spoofing, `old()`, pesan validasi per field, tombol submit disable + spinner (anti double-submit).
- JS per halaman dimuat hanya di halaman itu. Tidak ada blok `<script>` inline panjang; pindahkan ke `resources/js`.
- Semua aset lokal (tanpa CDN): font, ikon, library. Library JS hanya yang lolos triage, versi terbaru yang dirawat. Aturan aset dan modal konfirmasi: `PROJECT_RULES_LAN_FRONTEND_OPS.md` §6.
