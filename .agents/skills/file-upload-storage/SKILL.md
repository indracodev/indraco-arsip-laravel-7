---
name: file-upload-storage
description: Upload dan penyajian file scan/lampiran DMS Indraco — folder lama di disk public, satu service upload yang aman, streaming lewat controller dengan cek hak akses, kompatibel dengan path yang sudah tersimpan di database. Pakai saat menyentuh upload, preview, download file.
---

# File upload & streaming

## Fakta Laravel 7
- Disk `public` (`storage/app/public`), path relatif tersimpan di kolom DB:

| Folder | Kolom / asal |
|---|---|
| `archive_scan_inputs` | `archives.scan_input_form` |
| `archive_files` | `archives.file_path` |
| `archive_approvals` | `archives.scan_approval_input` |
| `borrowing_approvals` | `borrowing_logs.scan_approval_borrow` |
| `destruction_approvals` | `destruction_logs.scan_approval_destruction` |
| `bap_certificates` | `destruction_logs.certificate_file` |

  (Sumber: `ArchiveStatusController.php` baris ~67–329. Kolom lain seperti `scan_extension_form`: **TODO Fase 0**.)
- Disajikan lewat `FileStreamController` (`files/stream/{path}`, `files/preview-stream`) dengan cek `..` dan `sanitizeFilePath()`; ada juga `public/storage` symlink sehingga file **bisa diakses langsung tanpa login** — temuan keamanan (catat di findings).

## Aturan L13
- Path lama di DB tetap valid: file tetap di `storage/app/<folder>` dengan path relatif yang sama.
- **Tidak** ada akses langsung lewat `public/storage` untuk file arsip; semua lewat controller yang memeriksa login + Policy (departemen pemilik / gudang / admin).
- Satu service (`App\Services\FileStorage`): whitelist ekstensi (pdf, jpg, jpeg, png), cek MIME dari isi, batas ukuran (samakan dengan L7 — **TODO Fase 0**), nama file acak dari server, simpan **sebelum** transaksi DB.
- Streaming: `response()->file()`/`download()` langsung dari disk (Caddy tidak memblok worker lain karena pool), header `Content-Disposition` sesuai, `X-Content-Type-Options: nosniff`.
- Hapus file hanya setelah transaksi DB sukses; file yatim dibersihkan jika transaksi gagal.

## Verifikasi
- Tes: upload `.php`, ekstensi ganda, file palsu → ditolak; akses file departemen lain → 403; `../` → 404/403; file lama dengan path dari DB tetap terbuka.
