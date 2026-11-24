---
name: file-upload-storage
description: Upload dan penyajian file scan/lampiran DMS Indraco — folder lama di disk public, satu service upload yang aman, streaming lewat controller dengan cek hak akses, kompatibel dengan path yang sudah tersimpan di database. Pakai saat menyentuh upload, preview, download file.
---

# File upload & streaming

Catatan modul lengkap: `context/modules/file.md`.

## Fakta Laravel 7 (dicek di kode + DB, putaran A2)
- Disk `public` (`storage/app/public`) + folder nyata `public/storage` (15 MB, B11); path relatif polos di DB:

| Folder | Kolom |
|---|---|
| `archive_scans` (form L13 juga) | `archives.scan_input_form` |
| `archive_scan_inputs` | `archives.scan_input_form` (jalur lama) |
| `archive_digital`, `archive_files` | `archives.file_path` |
| `archive_approvals` | `archives.scan_approval_input` |
| `archive_extensions` | `archives.scan_extension_form`, `archive_extension_logs.scan_extension_form` |
| `borrowing_approvals` | `borrowing_logs.approval_file`, `scan_approval_borrow` |
| `destruction_approvals` | `destruction_logs.approval_file`, `scan_approval_destruction` |
| `bap_certificates` | `destruction_logs.certificate_file` |

- Batas upload L7: `mimes:pdf,jpg,jpeg,png|max:500` (500 KB).
- `FileStreamController` (`files/stream/{path}`, `files/preview-stream`) tanpa login + fallback PDF lain (temuan #2, #3) — **tidak dibawa**.

## Aturan L13
- Satu service `App\Services\FileStorage`: `store()` (whitelist pdf/jpg/jpeg/png, MIME dari isi, 500 KB, nama acak, disk privat `storage/app/private`, simpan **sebelum** transaksi DB; hapus hanya setelah commit) dan `locate()` (allow-list folder `FOLDERS`, nama polos, disk `local` → `public` → `legacy_public`, realpath tetap di root).
- Penyajian hanya lewat `ArchiveFileController`: `files.preview` / `files.download` = `/files/{source}/{id}/{field}[/download]`, login + `ArchivePolicy@view` arsip induk; tidak ada path di URL; file hilang = 404 tanpa fallback.
- **Pratinjau di halaman = `files.preview_data` (JSON base64), bukan fetch PDF.** IDM di PC kantor mencegat respons PDF di Chrome, termasuk `fetch()`, dan halaman menerima 204 kosong (R27; L7 `preview-stream` memakai JSON karena alasan ini). Viewer membangun Blob dari base64. Batas 5 MB (`PREVIEW_MAX_BYTES`, 413 + saran "Unduh"); disajikan PHP, jadi jangan naikkan batas tanpa alasan. Viewer baru pakai `public/js/pages/archive-show.js` + kunci `data`/`download` dari `ArchiveFileList`/`BorrowingList::files`.
- Caddy menyajikan file lewat `X-Accel-Redirect` (`filesystems.x_accel`, root `storage/app`); `/storage/*` selalu 404; tidak ada `public/storage` (`links = []`, launcher melepas link lama).
- Upload modul baru: folder harus ada di `FileStorage::FOLDERS`, link selalu lewat route `files.*` (tambahkan kolom ke `ArchiveFileController::SOURCES`).

## Verifikasi
- Tes: `tests/Feature/File/ArchiveFileTest.php` (preview-data JSON/413, 403 dept lain, tamu → login, path DB berbahaya → 404, urutan disk, header X-Accel) + `tests/Feature/Archive/SubmissionUploadTest.php` (upload ditolak).
- Uji langsung: `/storage/...`, `/private/...`, backup `.sqlite` → 404; file lama dari `storage/app/public` & `legacy-public` → 200 lewat Caddy (ada `Etag`/`Accept-Ranges`, tanpa `X-Accel-Redirect` di respons).
