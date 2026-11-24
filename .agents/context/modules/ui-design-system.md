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
| `public/js/font-size.js` | ukuran font per user sebelum render (head, tidak defer) |
| `public/js/modal.js` | modal + konfirmasi (`showConfirmModal`, `showAlertModal`, `data-confirm*`) |
| `public/js/app.js` | anti double-submit, form mockup, toast, toggle password, shortcut, registrasi SW, `Alpine.data('shell'|'fontSize')` |
| `public/js/lan.js` | titik status LAN (hanya layout app) |
| `public/js/vendor/alpine.min.js` | Alpine.js **3.17.5** (`dist/cdn.min.js` dari tarball npm; sha1 `85027aff…1687` & integrity sha512 cocok dengan registry; terbit 2026-10-20) |

- Satu partial `layouts/partials/head-assets.blade.php`: tepat 4 `<link rel="stylesheet">` + ikon/manifest; URL `?v=<md5 isi, 10 char>` dari `App\Support\Asset::url()`.
- Versi di-cache di `bootstrap/cache/assets.php` (`assets:cache`, ikut `optimize`; hapus via `optimize:clear`/`assets:clear`); OPcache membuatnya gratis. Tanpa cache: hash dihitung sekali per request.
- Launcher menjalankan `optimize` bila ada file lebih baru di `app, config, routes, resources\views, public\css, public\js`.
- Setelah mengedit `components/*.css`: `php artisan assets:cache` lalu commit `components.css` (tes `AssetsTest` gagal bila lupa).

## Token (`tokens.css`)
| Grup | Token | Nilai |
|---|---|---|
| Netral | `--color-bg`, `-surface`, `-surface-alt`, `-hover`, `-border`, `-border-strong`, `-text`, `-text-muted`, `-text-subtle`, `-overlay` | #f4f5f7 … #1f2633 |
| Aksen navy | `--color-accent` #1e3a6b, `-accent-hover`, `-accent-soft`, `-on-accent`, `--color-focus` | |
| Status (hanya status) | `--color-success|warning|danger|info` + `-soft` | hijau/kuning/merah/biru |
| Spasi | `--space-1..6, 8` | 4, 8, 12, 16, 20, 24, 32 px (rem) |
| Radius | `--radius-sm|radius|radius-lg|radius-pill` | 4/6/8/999 px |
| Tipe | `--font-sans` (system stack), `--font-mono`, `--text-xs|sm|base|lg|xl` | 12/13/14/16/18 px |
| Kontrol | `--size-control` 36px, `--size-control-sm` 28px, `--size-topbar` 48px, `--size-sidebar` | |
| Lain | `--shadow-1` (tipis), `--shadow-modal`, `--duration` 120ms, `--z-*` | |
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

## Pola halaman
- **Daftar**: `page-header` (+ aksi) → `filter-bar` → `table` (atau `empty-state`) → `pagination`. Maks 50 baris/halaman, filter dikirim ke server.
- **Form**: `card` per bagian (judul bernomor seperti L7) → `form-grid--2` di ≥ 1024px → `field` + kontrol; bar aksi di bawah (Batal, Draft, Ajukan). Submit otomatis dikunci + spinner. Mockup memakai `<form data-mockup>`.
- **Placeholder**: `page-header` + `empty-state` "Sedang dibangun ulang".

## Larangan (dicek `AssetsTest`)
`:has(`, nesting `&`, `@container`, `color-mix(`, `@layer`, `@property`, `oklch(`/`lab(`, `backdrop-filter`/`blur(`, `@import`, URL eksternal; animasi hanya opacity/transform ≤ 150 ms (spinner tombol pengecualian fungsional); hormati `prefers-reduced-motion`.

## Ukuran terukur (Fase 3)
CSS 4 file ±22 KB mentah / ±6 KB gzip; JS awal ±67 KB / ±24 KB gzip (Alpine ±56 KB). Lihat laporan Fase 3 untuk DOM per halaman.
