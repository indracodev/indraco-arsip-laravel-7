# Modul: FILE (pratinjau / unduh file upload)
Status: dibangun (putaran A2) — menutup temuan #2, #3, B11 (menunggu verifikasi koordinator)

## Route lama → baru
| L7 | L13 | Akses |
|---|---|---|
| `GET files/stream/{path}` `files.stream` (tanpa login, path mentah, fallback PDF lain) | DROP (404) | – |
| `GET/POST files/preview-stream?token=` `files.preview_stream` (tanpa login, base64 JSON) | DROP (404) | – |
| `GET storage/files/{path}` `storage.files` | DROP (404) | – |
| `public/storage` (symlink/folder nyata, tanpa login) | DROP: launcher melepas link, `filesystems.links = []`, Caddy `respond /storage/* 404` | – |
| – | `GET /files/{source}/{id}/{field}` `files.preview` (inline) | login + `ArchivePolicy@view` arsip induk |
| – | `GET /files/{source}/{id}/{field}/download` `files.download` (attachment) | idem |
| `files/preview-stream` (base64 JSON, alasan IDM) | `GET /files/{source}/{id}/{field}/preview-data` `files.preview_data` (JSON base64 untuk viewer, R27) | idem |
Tidak ada view/klien (Tauri `desktop/`, JS) yang memakai nama route L7 → tidak dibuat redirect (redirect akan menghidupkan lagi input path mentah).

`source` → kolom yang boleh (`ArchiveFileController::SOURCES`):
| source | Model | field |
|---|---|---|
| archive | Archive | scan_input_form, file_path, scan_approval_input, scan_extension_form |
| borrowing | BorrowingLog | approval_file, scan_approval_borrow |
| destruction | DestructionLog | approval_file, scan_approval_destruction, certificate_file, scan_extension_form |
| extension | ArchiveExtensionLog | scan_extension_form |
Field lain / source lain / id bukan angka → 404.

## Aturan
- URL tidak pernah berisi path. Path diambil dari kolom DB, lalu `FileStorage::locate()`:
  - harus `<folder>/<nama>` dengan folder di allow-list `FileStorage::FOLDERS` = archive_scans, archive_scan_inputs, archive_digital, archive_files, archive_approvals, archive_extensions, borrowing_approvals, destruction_approvals, bap_certificates (dari controller L7 + DB nyata); nama `[A-Za-z0-9][A-Za-z0-9._-]*`, tanpa sub-folder; ekstensi pdf/jpg/jpeg/png.
  - urutan cari disk: `local` = `storage/app/private` (upload L13) → `public` = `storage/app/public` (disk public L7) → `legacy_public` = `storage/app/legacy-public` (isi folder nyata L7 `public/storage`, B11). File pertama yang ada dipakai; `realpath` harus tetap di bawah root disk.
  - tidak ketemu → 404 "Berkas tidak ditemukan di server penyimpanan. Unggah ulang berkas melalui form edit arsip." (tanpa fallback, temuan #3). Kolom kosong → 404 "Berkas tidak dilampirkan pada arsip ini."
- Header: Content-Type dari ekstensi (whitelist), `Content-Disposition` inline (pratinjau) / attachment (unduh), nama `scan-input-form-archive-117.pdf`, `X-Content-Type-Options: nosniff`, `Cache-Control: private, no-cache`.
- `filesystems.x_accel` (env `DMS_FILE_X_ACCEL`, default true): PHP menjawab body kosong + `X-Accel-Redirect: /private|/public|/legacy-public/<folder>/<file>`; Caddy `handle_response` (root `storage/app`, `copy_response_headers` Content-Type/Content-Disposition/Cache-Control/X-Content-Type-Options) menyajikan file (Range, ETag) — worker PHP langsung bebas. `false` = PHP menyajikan sendiri (`php artisan serve`).
- Lokasi internal tidak bisa diminta langsung: root situs `public/`; `/private/…`, `/public/…`, `/legacy-public/…`, `/storage/…` = 404; header `X-Accel-Redirect` dari klien diabaikan (hanya header respons upstream yang memicu).
- Backup `storage/app/*.sqlite` dan `quick_notes.json` tidak di folder allow-list → tidak pernah tersaji (diuji dengan file tiruan).
- 404 dengan pesan: `resources/views/errors/404.blade.php` menampilkan pesan `abort(404, …)` saja (bukan teks framework yang menyebut nama kelas); JSON bila `Accept: application/json`.

## Pratinjau di halaman (detail arsip + modal Detail/Keluarkan penarikan)
Modal `#file-viewer` (`archives/partials/show-files.blade.php`, `borrowings/index.blade.php`; JS bersama `public/js/pages/archive-show.js`). Daftar berkas (`ArchiveFileList`, `BorrowingList::files`) berisi `data` (= `files.preview_data`) dan `download`.
- **Kenapa JSON base64 (R27):** Internet Download Manager (terpasang di PC kantor) mencegat respons PDF di Chrome, **termasuk `fetch()`** — server log 200, halaman menerima **204 kosong**, modal kosong. L7 sengaja memakai `files/preview-stream` (base64 JSON) karena ini. Respons `application/json` tidak disambar IDM.
- `files.preview_data` = otorisasi/allow-list/`locate()` yang sama dengan `files.preview` (satu controller, cabang `routeIs`); balasan `{name, mime, size, data}`; header `Cache-Control: no-store, private`, `nosniff`; tanpa X-Accel/Content-Disposition. Batas `ArchiveFileController::PREVIEW_MAX_BYTES` = 5 MB (upload ≤ 500 KB, file lama terlihat ≤ ~430 KB) → di atasnya 413 JSON "Berkas terlalu besar untuk pratinjau … Gunakan tombol "Unduh"."
- **Biaya:** disajikan PHP (bukan Caddy X-Accel): file dibaca utuh ke memori + base64 (+33 %) selama satu request — untuk ≤ 500 KB hanya milidetik per worker; batas 5 MB mencegah worker tertahan oleh file besar.
- JS: JSON → `Blob` (mime dari server) → blob URL; PDF di `<iframe>` (viewer bawaan browser/WebView2), gambar `<img>`; tanpa PDF.js. Pesan 403/404/413 dari server tampil di modal; 2xx tanpa `data` (mis. 204 dari IDM) → "Gunakan tombol Unduh". "Buka di Tab Baru" = blob URL yang sama (aktif setelah berkas dimuat); "Unduh" tetap `files.download` attachment (IDM boleh menyambar unduhan). Blob dicabut saat modal ditutup (tab baru yang sudah terbuka tetap tampil, tetapi tidak bisa di-reload). CSP Report-Only `frame-src 'self' blob:`.
- `files.preview` (inline, X-Accel) tetap ada untuk akses langsung, tidak lagi dipakai halaman.

## File L13
`app/Http/Controllers/ArchiveFileController.php`, `app/Services/FileStorage.php` (FOLDERS, MIME, SEARCH_DISKS, `locate()`), `app/Support/ArchiveFileList.php` (daftar berkas di detail), `config/filesystems.php` (disk `legacy_public`, `links = []`, `x_accel`), `Caddyfile`, `START-DMS-INDRACO.bat` (CHECK 5), `resources/views/errors/404.blade.php`.

## Tes
`tests/Feature/File/ArchiveFileTest.php`: preview-data (JSON exact name/mime/size/base64, header no-store/nosniff tanpa X-Accel, tamu → login/401, dept lain 403, path berbahaya & file hilang 404, field asing 404, > 5 MB → 413), X-Accel + header inline/attachment, tanpa X-Accel PHP menyajikan isi, tamu → login, dept lain 403, gudang/admin 200, file log penarikan via arsip induk, urutan disk private → public → legacy, file hilang 404 tanpa fallback, 13 path DB berbahaya (backup, `..`, `..\`, encoded, absolut, UNC, sub-folder, `.txt`, titik akhir, `::$DATA`) → 404, URL dimanipulasi + route L7 → 404.

## Cutover (pindah file L7)
1. Salin `L7\storage\app\public\*` → `L13\storage\app\public\` (nama folder sama).
2. Salin isi folder nyata `L7\public\storage\*` → `L13\storage\app\legacy-public\` (BUKAN ke `L13\public\`). Bila sama nama dengan langkah 1, isinya identik (nama acak Laravel) — tidak perlu digabung.
3. Jangan jalankan `storage:link`; launcher melepas `public\storage` bila masih ada (rmdir tanpa /s: link saja, folder berisi file tidak dihapus).
4. 21 `backup_indraco_*.sqlite` + `quick_notes.json`: pindah ke folder backup di luar `storage/app` (ASK 10); walau tertinggal tetap tidak tersaji.
5. Cek: arsip dengan file lama terbuka (200), `GET /storage/...` 404.
Data 2026-11-24: 18 scan `archive_scans` (4 di kedua lokasi, 14 hanya di `public/storage`), 1 `archive_extensions`, 3 `borrowing_approvals`, 2 `destruction_approvals`; semua path DB polos `<folder>/<nama>` (tanpa awalan `storage/`/`public/`).

## Sisa
- Upload baru modul lain (penarikan/pemusnahan/perpanjangan) memakai `FileStorage::store()` ke folder di allow-list dan link `files.preview`/`files.download`.
- Pratinjau file tersimpan di form edit (pakai `ArchiveFileList`/route yang sama).
