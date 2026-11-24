# Design system UI (Fase 3) — CSS murni + Alpine.js lokal
Kontrak: `.agents/rules/50-frontend-blade.md`. Tanpa Tailwind/Vite/Node/CDN. Target Chrome/Edge ≥ 90, Firefox ≥ 88, klien tanpa GPU.

## File & pemuatan
| File | Isi |
|---|---|
| `public/css/tokens.css` | semua token (tabel di bawah) |
| `public/css/base.css` | reset ringan, tipografi, `.icon`, `.stack`, `.cluster`, `.sr-only`, prefers-reduced-motion |
| `public/css/layout.css` | shell (topbar, menu atas/drawer, main + ambient), halaman guest |
| `public/css/components/*.css` | satu file per komponen (sumber yang diedit) |
| `public/css/components.css` | **hasil gabungan** komponen (di-commit) — `php artisan assets:cache` (otomatis di `optimize`) |
| `public/js/prefs.js` | preferensi per browser sebelum render (head, tidak defer): ukuran font (`app_font_size`), tema (`dms_theme`, fallback kunci L7 `theme`, default Terang bila kosong — R26 → `data-theme`), kelas breakpoint rem `bp-*`; `window.dmsPrefs`; menghapus kunci lama `dms_layout` |
| `public/js/sound.js` | `window.dmsSound.play()` chime notifikasi (WebAudio, tanpa file), pengaturan `dms_sound` (hanya layout app, sebelum Alpine) |
| `public/js/modal.js` | modal + konfirmasi (`showConfirmModal`, `showAlertModal`, `data-confirm*`) |
| `public/js/app.js` | anti double-submit (menyalin name/value tombol yang diklik ke input hidden `data-submitter-copy` sebelum men-disable tombol), simpan ukuran font ke akun, toast, toggle password, shortcut, dropdown menu atas (klik/Enter/Spasi, Esc), registrasi SW, `Alpine.data('shell'|'displaySettings')` |
| `public/js/lan.js` | titik status LAN (hanya layout app) |
| `public/js/vendor/alpine.min.js` | Alpine.js **3.17.5** (`dist/cdn.min.js` dari tarball npm; sha1 `85027aff…1687` & integrity sha512 cocok dengan registry; terbit 2026-10-20) |

- Satu partial `layouts/partials/head-assets.blade.php`: tepat 4 `<link rel="stylesheet">` + ikon/manifest; URL `?v=<md5 isi, 10 char>` dari `App\Support\Asset::url()`.
- Versi di-cache di `bootstrap/cache/assets.php` (`assets:cache`, ikut `optimize`; hapus via `optimize:clear`/`assets:clear`); OPcache membuatnya gratis. Tanpa cache: hash dihitung sekali per request.
- Launcher menjalankan `optimize` bila ada file lebih baru di `app, config, routes, resources\views, public\css, public\js`.
- Setelah mengedit `components/*.css`: `php artisan assets:cache` lalu commit `components.css` (tes `AssetsTest` gagal bila lupa).

## Token (`tokens.css`) — revisi pemilik: slate gelap + oranye logo
| Grup | Token | Nilai |
|---|---|---|
| Netral (tint slate, tanpa putih murni di halaman) | `--color-bg` #e7eaee, `-surface` #fcfcfd, `-surface-alt` #f2f4f6, `-hover` #e5e8ec, `-border` #d3d7dd, `-border-strong` #79828f (border kontrol ≥ 3:1), `-text` #1c222b, `-text-muted` #49515d, `-text-subtle` #5d6571, `-overlay` | |
| Primer (slate gelap) | `--color-accent` #262f3b, `-accent-hover` #1a2029, `-accent-soft` #e3e6eb, `-on-accent` #fff | tombol primer, nama app |
| Sekunder (oranye logo) | `--color-brand` #fd4f00 (indikator menu aktif, garis atas topbar), `--color-focus` #d04100 (cincin fokus; lebih gelap agar ≥ 3:1 di bg/ambient/surface/nav) | hemat, tidak untuk bidang besar |
| Navigasi (bar menu atas / drawer) | `--color-nav-bg` #222a35, `-nav-hover` #2d3643, `-nav-active` #36404e, `-nav-text` #e6e9ed, `-nav-text-muted` #aab2bd, `-nav-border` #3a4452 | |
| Status (hanya status) | `--color-success|warning|danger|info` + `-soft` | hijau/kuning/merah/biru |
| Ambient | `--ambient`: 2 radial-gradient statis di `.main` (area konten, ≥ tinggi viewport di bawah menu) dan `.guest`: slate 14% kanan atas (`ellipse 60% 30rem at 100% 0%`), oranye 10% kiri bawah (`ellipse 55% 26rem at 0% 100%`), keduanya pudar ke 0 di 70% sehingga tidak bertumpuk; `body` = `--color-bg` polos. Titik tergelap (#ccd0d5) dicek `ContrastTest` (`ambient`) | tanpa animasi, tanpa `background-attachment: fixed`, tanpa blur |
| Spasi | `--space-1..6, 8` | 4, 8, 12, 16, 20, 24, 32 px (rem) |
| Radius | `--radius-sm|radius|radius-lg|radius-pill` | 4/6/8/999 px |
| Tipe | `--font-sans` (system stack), `--font-mono`, `--text-xs|sm|base|lg|xl` | 12/13/14/16/18 px |
| Kontrol | `--size-control` 36px, `--size-control-sm` 28px, `--size-topbar` 48px, `--size-topnav` 40px, `--size-drawer` 16rem | |
| Lain | `--shadow-1` (tipis), `--shadow-modal`, `--duration` 120ms, `--z-*` | |

Kontras WCAG AA semua pasangan teks/latar & UI dicek `tests/Unit/ContrastTest.php` untuk **kedua tema** (daftar = `ContrastTest::PAIRS`; `ambient` = setiap pusat glow, jadi titik tergelap tema terang dan titik terterang tema gelap ikut dicek).

## Tema gelap (`:root[data-theme="dark"]` di `tokens.css`, di dalam `@media screen` → cetak selalu token terang)
Dipasang `prefs.js` sebelum render (Terang / Gelap / Ikuti Sistem). Hanya token yang ditimpa; komponen tidak punya aturan khusus gelap. `color-scheme: dark` agar scrollbar/kontrol bawaan ikut gelap.

| Token | Terang | Gelap |
|---|---|---|
| bg / surface / surface-alt / hover | #e7eaee / #fcfcfd / #f2f4f6 / #e5e8ec | #12171e / #1a212b / #212934 / #29323e |
| border / border-strong | #d3d7dd / #79828f | #343e4b / #6c7787 |
| text / text-muted / text-subtle | #1c222b / #49515d / #5d6571 | #e4e8ec / #aab3be / #8f99a5 |
| accent / accent-hover / accent-soft / on-accent | #262f3b / #1a2029 / #e3e6eb / #fff | #d9dfe6 / #c3cbd5 / #2c3542 / #151b23 (tombol primer jadi slate terang, teks gelap) |
| brand / focus | #fd4f00 / #d04100 | #fd4f00 / #ff7a33 |
| nav-bg / nav-hover / nav-active / nav-text / nav-text-muted / nav-border | #222a35 / #2d3643 / #36404e / #e6e9ed / #aab2bd / #3a4452 | #0c1016 / #1c232d / #262f3b / #e4e8ec / #9aa3ae / #2b3441 |
| success / warning / danger / info | #166534 / #8a5a00 / #b42318 / #1d4ed8 | #4cc38a / #e5b143 / #f28b80 / #86aef5 — 4 rona tetap hijau/kuning/merah/biru |
| …-soft | #e3f2e7 / #fbf0d6 / #fbe9e7 / #e5ecfc | #14301f / #33270c / #3b1915 / #162847 |
| logo-chip | transparent | #e4e8ec (chip terang di belakang logo; tanpa `filter`) |
| ambient | slate 14% + oranye 10% | slate terang `rgba(148,163,184,.10)` + oranye 8% |
| `--icon-chevron` (panah `<select>`) | stroke #5a6372 | stroke #aab3be |
| shadow | rgba slate | rgba(0,0,0,.4 / .5) |

## Perbesar teks (font 10–26 px) — aturan
- Semua ukuran yang harus ikut membesar (teks, kontrol, spasi, lebar laci/modal/dropdown) dalam **rem/em**. `px` hanya untuk garis rambut ≤ 4 px, border, outline, shadow, radius, dan `html { font-size: 14px }`. Dicek `AssetsTest::test_css_sizes_scale_with_the_font_size`.
- **Dilarang media query lebar** (`@media (min|max-width)`), karena px/em di media query tidak ikut ukuran font user. Pakai kelas di `<html>` dari `prefs.js`: `.bp-lg` / `.bp-lt-lg` (1024), `.bp-md` / `.bp-lt-md` (768), `.bp-sm` / `.bp-lt-sm` (320), dihitung dari `innerWidth × 14 / font` (dicek tes yang sama). Contoh: `.bp-lg .form-grid--2 {…}`, `.bp-lt-lg .table--cards td {…}`.
- Komponen tahan sempit: topbar boleh membungkus ke baris kedua (logo mengecil dulu), tombol di footer modal/kartu/page-header membungkus teks, `filter-bar` kolom `min(12rem, 100%)`, sel kartu tabel `overflow-wrap: anywhere`, di `.bp-lt-sm` label sel kartu di atas nilai dan badge boleh membungkus, dropdown grup terakhir menu atas membuka ke kiri.
- Uji manual (2026-11-24, Chrome, iframe 360/768/1024/1366 × font 14/19/23/26 × shell + sampel filter/tabel kartu/form/page-header/pagination/modal/toast/dropdown): `scrollWidth` dokumen ≤ lebar, tidak ada elemen di luar viewport, tidak ada label tombol/badge terpotong. Ulangi saat menambah komponen/halaman: Pengaturan Tampilan → slider 26 → sempitkan jendela ke 360/768, cek tanpa geser horizontal dan tanpa teks terpotong.

## Komponen (`resources/views/components/`)
| Komponen | Pakai | CSS |
|---|---|---|
| `button` | `<x-button variant="primary|secondary|danger|ghost" size="sm" type="submit">` | button.css |
| `link-button` | `<x-link-button :href="..." variant size>` | button.css |
| `input` | `<x-input name type :value>` (id = name, merah bila error) | input.css (`.control`) |
| `select` | `<x-select name :options :selected placeholder>` | select.css |
| `textarea` | `<x-textarea name :value rows>` | textarea.css |
| `checkbox` | `<x-checkbox name label :checked>` | checkbox.css |
| `file-input` | `<x-file-input name accept badge>` | file-input.css |
| `field` | `<x-field label for required optional hint error="name">` kontrol di slot; `*` merah, "(Opsional)" | field.css (+ `.form-grid--2/4`) |
| `table` | `<x-table>` thead/tbody; `<td data-label="KOLOM">`; < 768px jadi kartu | table.css (`.table__row--{tone}`, `__title`, `__meta`, `__note`, `__actions`) |
| `badge-status` | `<x-badge-status :status :rejected :destruction short>` → `App\Support\StatusLabel` (label L7, teks pendek katalog, warna) | badge.css |
| `modal` | `<x-modal id title>` + `<x-slot:footer>`; buka `data-modal-open="id"` | modal.css |
| `confirm-modal` | sekali di layout; `data-confirm`, `data-confirm-title`, `data-confirm-type`, `data-confirm-btn`, `data-confirm-cancel`, atau `await window.showConfirmModal({...})` | modal.css |
| `card` | `<x-card title>` + slot `actions`, `footer`; `.card--muted` | card.css |
| `toast` | sekali di layout; flash `success/info/status/warning/error`, hilang 6 dtk; `.notice--{tone}` untuk banner inline | toast.css |
| `empty-state` | `<x-empty-state title message icon>` | empty-state.css |
| `pagination` | `<x-pagination :paginator noun>` ("Menampilkan a - b dari n total …") | pagination.css |
| `page-header` | `<x-page-header title subtitle>` + slot `actions` | page-header.css |
| `filter-bar` | `<x-filter-bar :action :active>` (GET, tombol "Filter", reset bila aktif) | filter-bar.css |
| `icon` | `<x-icon name>` set SVG kecil (hanya ikon terpakai) | base.css `.icon` |

## Menu
- Hanya menu atas (keputusan pemilik): bar slate gelap di bawah topbar (`.topnav`); grup admin/gudang = dropdown (tombol `data-nav-group`, `aria-expanded`), PIC 3 item sejajar. < 1024px = drawer daftar vertikal. Satu markup (`layouts/partials/menu.blade.php`) dari `App\Support\Menu::grouped()`. Tidak ada pilihan tata letak.

## Pola halaman
- **Daftar**: `page-header` (+ aksi) → `filter-bar` → `table` (atau `empty-state`) → `pagination`. Tabel jadi kartu di < 1024px (768–1023: sel 2 kolom; `td.table__wide` dan aksi selebar kartu; checkbox `td.table__select` di pojok kanan atas; isi sel menumpuk di bawah label sehingga tag ada di bawah judul; nilai/badge selebar isinya (`justify-self: start`, R19); kolom label sel lebar 17% agar sejajar sel setengah). Maks 50 baris/halaman, filter dikirim ke server.
- **Form**: `card` per bagian (judul bernomor seperti L7) → `form-grid--2` di ≥ 1024px → `field` + kontrol; bar aksi di bawah (Batal, Draft, Ajukan). Submit otomatis dikunci + spinner; tombol submit boleh membawa `name`/`value` (nilai tetap terkirim). `<form data-no-lock>` = tanpa kunci.
- **Cetak** (dokumen kertas, mis. `archives/form-print`): halaman berdiri sendiri, `data-theme="light"` tanpa `prefs.js`, CSS halaman di `public/css/pages/*.css` (dimuat hanya di halaman itu, total ≤ 4 `<link>`). Ukuran kertas memakai mm/pt (bukan rem) agar cetakan sama dengan L7 berapa pun ukuran font user; warna kertas lewat token `--color-paper`, `--color-paper-head`, `--color-ink` (dicek `ContrastTest`). Panel kontrol layar diberi `.no-print`.
- **Aksi baris tabel**: `.table__actions` satu baris; tombol yang perlu `<form>` sendiri (Hapus) tetap sebaris (`.table__actions form { display: inline-block }`), juga di mode kartu.
- **Placeholder**: `page-header` + `empty-state` "Sedang dibangun ulang".

## Larangan (dicek `AssetsTest`)
`:has(`, nesting `&`, `@container`, `color-mix(`, `@layer`, `@property`, `oklch(`/`lab(`, `backdrop-filter`/`blur(`, `@import`, URL eksternal; animasi hanya opacity/transform ≤ 150 ms (spinner tombol pengecualian fungsional); hormati `prefers-reduced-motion`.

## Ukuran terukur (Fase 3)
A1 (2026-11-24): CSS 4 file 34,9 KB mentah / 9,8 KB gzip (+ `pages/form-print.css` 3,9 / 1,4 KB hanya di halaman cetak); JS awal (prefs+sound+modal+app+lan+alpine) 76,7 KB / 27,4 KB gzip. Dark mode + perbesar teks + suara (2026-11-24): CSS 4 file 32,0 KB mentah / 8,0 KB gzip; JS awal (prefs+sound+modal+app+lan+alpine) 74,9 KB / 25,5 KB gzip (sound.js 2,0 KB / 0,9 KB). Sebelumnya: CSS 4 file 26,8 KB mentah / 7,4 KB gzip; JS awal 69,1 KB / 24,5 KB gzip (Alpine ±56 KB). Revisi menu atas + R19 + ambient: CSS 26,8 KB / 7,6 KB gzip; JS awal (prefs+modal+app+alpine+lan) 68,4 KB / 24,4 KB gzip; DOM `/archives` admin 526, PIC (fin) 229, `/` admin 138 (DB salinan). DOM: katalog admin 50 baris 1.429, 10 baris 536, katalog PIC 174, mockup form 256, dashboard 142, login 54.
