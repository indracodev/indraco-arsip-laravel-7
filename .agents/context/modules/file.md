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

## Pratinjau di halaman detail
Modal `#file-viewer` (`archives/partials/show-files.blade.php`, `public/js/pages/archive-show.js`): file di-`fetch` lalu ditampilkan dari blob URL — PDF di `<iframe>` (viewer bawaan browser/WebView2), gambar `<img>`; tanpa PDF.js. Alasan fetch+blob: IDM tidak menyambar PDF (L7 memakai JSON base64 + PDF.js untuk itu) dan pesan 403/404 tampil di modal. Tombol berkas lain di modal (pengganti multi-file viewer L7), "Buka di Tab Baru", "Unduh". Blob dicabut saat modal ditutup. CSP Report-Only ditambah `frame-src 'self' blob:`.

## File L13
`app/Http/Controllers/ArchiveFileController.php`, `app/Services/FileStorage.php` (FOLDERS, MIME, SEARCH_DISKS, `locate()`), `app/Support/ArchiveFileList.php` (daftar berkas di detail), `config/filesystems.php` (disk `legacy_public`, `links = []`, `x_accel`), `Caddyfile`, `START-DMS-INDRACO.bat` (CHECK 5), `resources/views/errors/404.blade.php`.

## Tes
`tests/Feature/File/ArchiveFileTest.php`: X-Accel + header inline/attachment, tanpa X-Accel PHP menyajikan isi, tamu → login, dept lain 403, gudang/admin 200, file log penarikan via arsip induk, urutan disk private → public → legacy, file hilang 404 tanpa fallback, 13 path DB berbahaya (backup, `..`, `..\`, encoded, absolut, UNC, sub-folder, `.txt`, titik akhir, `::$DATA`) → 404, URL dimanipulasi + route L7 → 404.

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
