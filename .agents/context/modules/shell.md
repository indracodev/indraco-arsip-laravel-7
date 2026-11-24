# Modul: SHELL (layout, menu, login, PWA)
Status: dibangun (Fase 3) — menunggu ACC pemilik

## Route lama → baru
| L7 | L13 | Catatan |
|---|---|---|
| `/` shell MDI (`layouts/app`, `layouts/desktop_pic`) | `/` `dashboard` | PIC Departemen → redirect `archives.index` ([Form 1], jendela pertama shell PIC L7); admin/gudang → "Dashboard Overview" (placeholder) |
| modal Ganti Password | `GET /profile/password` `profile.password` (halaman) + `POST /profile/change-password` (tetap) | field/label/pesan sama |
| `/manifest.json` (rusak di L7: method tidak ada) | `/manifest.webmanifest` (file statis) | |
| menu MDI (iframe `?embed=1`) | halaman biasa dengan URL & nama route L7 | ASK 1 |
| `archives.index` | `ArchiveCatalogController@index` (mockup hanya-baca) | lihat `ui-design-system.md` |
| `archives.create` | `ArchiveCreateMockupController` (mockup statis, tanpa submit) | `role:pic_dept` (L7: gudang 403) |
| `archives.show`, `archives.edit` | `ArchiveCatalogController@show` → placeholder + `ArchivePolicy@view` | IDOR ditutup sekarang |
| `borrowings.index/create`, `destructions.index`, `logs.index`, `reports.index`, `diagnostics.index`, `master.departments/warehouses/warehouses.layout/numbering/users` | `PlaceholderController` "Sedang dibangun ulang" | URL, nama, dan middleware role sama dengan L7; modul nanti mengganti route-nya |

## Menu per role (`App\Support\Menu`, satu-satunya definisi)
| Role | Item (urutan) | Sumber L7 |
|---|---|---|
| pic_dept | [Form 1] Katalog Arsip {KODE}, [Form 2] Penarikan Berkas, [Form 3] Pengajuan Perpanjangan & Pemusnahan + tombol topbar **Baru (F2)** → `archives.create` | `layouts/partials/pic_desktop_scripts.blade.php:47-75`, `pic_desktop_header.blade.php:20-23` |
| admin | Dashboard Overview, Master Departemen, Master Gudang & Rak, Layout Gudang 2D, Format Penomoran Box, Kelola User & Hak Akses, Katalog Arsip, Penarikan Berkas, Pemusnahan & Retensi, Log History, Report, Live Monitor LAN & User | `layouts/partials/desktop_layout_scripts.blade.php:56-128` |
| pic_gudang | Dashboard Overview, Master Gudang & Rak, Layout Gudang 2D, Katalog Arsip, Penarikan Berkas, Pemusnahan & Retensi | idem (item `isSuperAdmin()` dibuang) |
`layouts/partials/admin-sidebar-content.blade.php` L7 = kode mati dari proyek lain (Master Merek/Produk/Banner…), tidak dipakai.

## Shell (`resources/views/layouts/app.blade.php`)
- Topbar: logo + nama dari `app_settings` (`App\Support\AppSettings`, 1 query/request via `once()`), tombol Baru (F2) untuk PIC, titik status LAN, nama + role, Pengaturan (ukuran font), Ganti Password, Logout.
- Menu per role, **hanya menu atas** (keputusan pemilik, revisi R19; opsi sidebar kiri & `dms_layout` dihapus, `prefs.js` menghapus nilai lama di localStorage): `<nav class="topnav" id="main-nav">` = bar menu slate gelap di bawah topbar dengan dropdown grup (admin: Dashboard Overview · Master [Master Departemen, Master Gudang & Rak, Layout Gudang 2D, Format Penomoran Box, Kelola User & Hak Akses] · Arsip [Katalog Arsip, Penarikan Berkas, Pemusnahan & Retensi] · Laporan & Sistem [Log History, Report, Live Monitor LAN & User]; gudang: Dashboard · Master [2] · Arsip [3]; PIC: 3 item sejajar). Grup = potongan berurutan dari urutan L7 (urutan item tetap). Dropdown: tombol `aria-expanded`, klik/Enter/Spasi, Esc menutup & mengembalikan fokus, klik di luar menutup; tanpa menu hover. < 1024px = drawer daftar vertikal (Alpine `shell`).
- Status LAN (`public/js/lan.js`): `GET /api/health/ping` (dijawab Caddy) tiap 5 dtk, timeout 4 dtk, berhenti saat tab tersembunyi; hijau/kuning (> `ping_alert_threshold_ms`)/merah "Terputus".
- Ukuran font per user (ASK 13): sementara per browser di `localStorage['app_font_size']` (kunci sama dengan L7), preset 10/14/19/21/23 + slider 10–26 px, diterapkan sebelum render (`public/js/prefs.js`). Default = `app_settings.app_font_size`. Usulan migrasi kolom `users.font_size` ada di laporan Fase 3.
- Flash `success|info|status|warning|error` → `<x-toast>`; modal konfirmasi tunggal `<x-confirm-modal>`.
- Shortcut: `data-shortcut="F2"` (Baru), `data-shortcut="Escape"` (Kembali di form). F5 = refresh bawaan browser.

## Pemanasan & view (temuan R14)
- Windows: `view:cache` mengompilasi `resources\views\x.blade.php` sedangkan request mencari `resources\views/x.blade.php` → hash beda → setiap view dikompilasi ulang saat request pertama (0,8–4,3 dtk), dan worker paralel yang mengompilasi view yang sama bisa gagal `rename(): Access is denied` (HTTP 500, terlihat di log).
- Perbaikan: `php artisan views:warm` (`App\Console\Commands\ViewsWarm`, ikut `optimize`) mengompilasi semua view dengan path dari view finder; `evironment/dms-ready.ps1` juga me-render `/login` di setiap worker (2 putaran × N paralel) sebelum SIAP.
- Hasil (laptop 16 core, START setelah file berubah → optimize, 6 request paralel per halaman): `/login` 0,81–1,14 dtk → 0,022–0,036; `/archives` 0,06–4,30 → 0,07–0,12; `/archives/create` 2,56–2,60 → 0,05–0,08.

## Login (`auth/login.blade.php`, layout `guest`)
Field/label sama dengan Fase 2/L7 (NAMA PENGGUNA / EMAIL, KATA SANDI / PASSWORD, tombol Login, Alt+E/Alt+P via `accesskey`, tombol lihat password). Email dinormalkan (trim + huruf kecil) sama dengan UNIQUE index (R4).

## PWA
- `public/manifest.webmanifest` (nama, ikon 192/512, start_url `/`, standalone).
- `public/sw.js`: hanya meng-cache file statis ber-versi (`/css|js|images/*?v=`) cache-first + `offline.html`; navigasi selalu ke server, offline → "Server DMS tidak terjangkau". Didaftarkan hanya di secure context (https:8443 / localhost).
- Caddy: aset `?v=` di-cache 1 tahun `immutable`; `sw.js`, manifest, offline `no-cache`.

## File L13
`app/Support/{Menu,AppSettings,Asset,StatusLabel,ArchiveExpiry}.php`, `app/Console/Commands/Assets{Cache,Clear}.php`, `app/Http/Controllers/{Dashboard,Placeholder,ArchiveCatalog,ArchiveCreateMockup}Controller.php`, `app/Policies/ArchivePolicy.php`, `app/Services/ArchiveCatalog.php`, `resources/views/layouts/{app,guest}.blade.php`, `layouts/partials/{head-assets,menu,settings-modal}.blade.php`, `app/Console/Commands/ViewsWarm.php`, `placeholder.blade.php`, `public/js/*`, `public/{sw.js,offline.html,manifest.webmanifest}`, `public/images/{logo-indraco,icon-192,icon-512}.png`.

## Tes
`tests/Feature/Shell/LayoutTest.php` (menu per role & urutan, role check placeholder, confirm modal, aset lokal ber-versi, tanpa @vite, login/ganti password), `tests/Feature/Shell/AssetsTest.php` (hash versi + manifest, bundle CSS ter-commit mutakhir, CSS tanpa fitur terlarang, budget), `tests/Feature/Shell/ViewsWarmTest.php`, `tests/Unit/ContrastTest.php` (WCAG AA token), `tests/Feature/Archive/CatalogTest.php` (scoping dept/IDOR, kolom & label L7, filter, DOM ≤ 1.500 untuk 50 baris, komposisi mockup form).

## Sisa pekerjaan
- Badge jumlah di menu (pending verifikasi, penarikan, Form 3), notifikasi realtime, pencarian header (Ctrl+F), popup jatuh tempo: dibangun bersama modulnya (Fase 4).
- Pengaturan logo / alert ping (admin) → modul SISTEM. Tema gelap & suara L7 tidak dibawa (tema gelap sudah disembunyikan di header PIC L7).
- Alpine build standar memakai `new Function` → melanggar CSP `script-src 'self'` (masih Report-Only). Saat CSP ditegakkan: ganti ke build `@alpinejs/csp` (ekspresi template sudah ditulis sederhana).
