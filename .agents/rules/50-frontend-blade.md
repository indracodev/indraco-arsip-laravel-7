---
trigger: glob
globs: resources/views/**, resources/css/**, resources/js/**, public/**
---

# Front-end (Blade + Alpine.js + Tailwind 4)

- **UX sama, tampilan baru.** Setiap halaman yang dibangun ulang punya langkah, isian (jumlah input, wajib/opsional, pilihan, default), aksi, dan pesan yang sama dengan Laravel 7 (lihat `context/triage.md` dan inventaris role).
- **Ringan untuk PC low-spec:** HTML yang dirender server + Alpine seperlunya; tanpa animasi/transisi berat, tanpa shadow/blur berlapis, tanpa library besar. Halaman harus tetap responsif di PC kantor lama.
- Semua elemen UI lewat **Blade component** di `resources/views/components/`: button, input, select, textarea, checkbox, table, badge status, modal, modal konfirmasi, card, toast, empty-state, pagination. Tidak ada styling ad-hoc per halaman.
- Palet: netral (abu) + satu aksen (navy) + warna status (hijau/kuning/merah/biru) **hanya** untuk status. Teks dasar 14px, kontrol 36px, tabel compact.
- Teks minimal: label singkat; penjelasan panjang di tooltip/help, bukan paragraf di halaman.
- Responsif mobile-first: tabel jadi kartu di layar kecil, navigasi jadi drawer. Diuji di lebar 360px, 768px, 1366px.
- Aksesibilitas dasar: label untuk setiap input, fokus terlihat, kontras cukup, tombol bisa dipakai keyboard.
- Keamanan output: `{{ }}` sebagai default; `{!! !!}` hanya untuk HTML yang disanitasi, dengan alasan tertulis. Data ke JS lewat `@js()`/`Js::from`. Tidak memakai `innerHTML` dengan data pengguna.
- Form: CSRF, method spoofing, `old()`, pesan validasi per field, tombol submit disable + spinner (anti double-submit).
- JS per halaman dimuat hanya di halaman itu. Tidak ada blok `<script>` inline panjang; pindahkan ke `resources/js`.
- Semua aset lokal (tanpa CDN): font, ikon, library. Library JS hanya yang lolos triage, versi terbaru yang dirawat. Aturan aset dan modal konfirmasi: `PROJECT_RULES_LAN_FRONTEND_OPS.md` §6.
