# Design system UI (Fase 3) — CSS murni + Alpine.js lokal
Kontrak: `.agents/rules/50-frontend-blade.md`. Tanpa Tailwind/Vite/Node/CDN. Target Chrome/Edge ≥ 90, Firefox ≥ 88, klien tanpa GPU.

## File & pemuatan
| File | Isi |
|---|---|
| `public/css/tokens.css` | semua token (tabel di bawah) |
| `public/css/base.css` | reset ringan, tipografi, `.icon`, `.stack`, `.cluster`, `.sr-only`, prefers-reduced-motion |
| `public/css/layout.css` | shell (topbar, sidebar/drawer, main), halaman guest |
| `public/css/components/*.css` | satu file per komponen (sumber yang diedit) |
| `public/css/components.css` | **hasil gabungan** komponen (di-commit) — `php artisan assets:cache` (otomatis di `optimize`) |
| `public/js/prefs.js` | preferensi per browser sebelum render (head, tidak defer): ukuran font (`app_font_size`) + tata letak menu (`dms_layout` = `side`/`top` → `html[data-layout]`) |
| `public/js/modal.js` | modal + konfirmasi (`showConfirmModal`, `showAlertModal`, `data-confirm*`) |
| `public/js/app.js` | anti double-submit, form mockup, toast, toggle password, shortcut, dropdown menu atas (klik/Enter/Spasi, Esc), registrasi SW, `Alpine.data('shell'|'displaySettings')` |
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
| Sekunder (oranye logo) | `--color-brand` #fd4f00 (indikator menu aktif, garis atas topbar), `--color-focus` #e04600 (cincin fokus; satu tingkat lebih gelap agar ≥ 3:1 di bg/surface/nav) | hemat, tidak untuk bidang besar |
| Navigasi (sidebar / bar menu atas) | `--color-nav-bg` #222a35, `-nav-hover` #2d3643, `-nav-active` #36404e, `-nav-text` #e6e9ed, `-nav-text-muted` #aab2bd, `-nav-border` #3a4452 | |
| Status (hanya status) | `--color-success|warning|danger|info` + `-soft` | hijau/kuning/merah/biru |
| Ambient | `--ambient`: 2 radial-gradient statis (slate 9% kiri atas, oranye 4,5% kanan bawah) di atas `--color-bg`, hanya di `body` | tanpa animasi, tanpa `background-attachment: fixed`, tanpa blur |
| Spasi | `--space-1..6, 8` | 4, 8, 12, 16, 20, 24, 32 px (rem) |
| Radius | `--radius-sm|radius|radius-lg|radius-pill` | 4/6/8/999 px |
| Tipe | `--font-sans` (system stack), `--font-mono`, `--text-xs|sm|base|lg|xl` | 12/13/14/16/18 px |
| Kontrol | `--size-control` 36px, `--size-control-sm` 28px, `--size-topbar` 48px, `--size-topnav` 40px, `--size-sidebar` | |
| Lain | `--shadow-1` (tipis), `--shadow-modal`, `--duration` 120ms, `--z-*` | |

Kontras WCAG AA semua pasangan teks/latar & UI dicek `tests/Unit/ContrastTest.php` (daftar = `ContrastTest::PAIRS`).
Ukuran dalam rem → ukuran font per user (html font-size) menskalakan seluruh UI.

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

## Tata letak menu
- `side` (default): sidebar slate gelap kiri. `top`: bar menu slate gelap di bawah topbar; grup admin/gudang = dropdown (tombol `data-nav-group`, `aria-expanded`), PIC 3 item sejajar. < 1024px keduanya = drawer yang sama. Satu markup (`layouts/partials/menu.blade.php`) dari `App\Support\Menu::grouped()`.
- Diubah di modal Pengaturan ("Tata Letak Menu"), disimpan per browser, diterapkan sebelum render (`prefs.js`).

## Pola halaman
- **Daftar**: `page-header` (+ aksi) → `filter-bar` → `table` (atau `empty-state`) → `pagination`. Tabel jadi kartu di < 1024px (768–1023: sel 2 kolom; `td.table__wide` dan aksi selebar kartu; checkbox `td.table__select` di pojok kanan atas; isi sel menumpuk di bawah label sehingga tag ada di bawah judul). Maks 50 baris/halaman, filter dikirim ke server.
- **Form**: `card` per bagian (judul bernomor seperti L7) → `form-grid--2` di ≥ 1024px → `field` + kontrol; bar aksi di bawah (Batal, Draft, Ajukan). Submit otomatis dikunci + spinner. Mockup memakai `<form data-mockup>`.
- **Placeholder**: `page-header` + `empty-state` "Sedang dibangun ulang".

## Larangan (dicek `AssetsTest`)
`:has(`, nesting `&`, `@container`, `color-mix(`, `@layer`, `@property`, `oklch(`/`lab(`, `backdrop-filter`/`blur(`, `@import`, URL eksternal; animasi hanya opacity/transform ≤ 150 ms (spinner tombol pengecualian fungsional); hormati `prefers-reduced-motion`.

## Ukuran terukur (Fase 3)
CSS 4 file 26,8 KB mentah / 7,4 KB gzip; JS awal 69,1 KB / 24,5 KB gzip (Alpine ±56 KB). DOM: katalog admin 50 baris 1.429, 10 baris 536, katalog PIC 174, mockup form 256, dashboard 142, login 54.
