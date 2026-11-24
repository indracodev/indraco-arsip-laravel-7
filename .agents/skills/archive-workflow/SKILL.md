---
name: archive-workflow
description: Aturan bisnis inti DMS Indraco — status arsip/box, transisi yang sah per role, penomoran box atomik dengan mode preview, retensi dan tanggal kedaluwarsa, peminjaman, perpanjangan, pemusnahan BAP. Pakai saat menyentuh Archive, BorrowingLog, DestructionLog, ArchiveExtensionLog, NumberingFormat.
---

# Alur arsip & penomoran

## Status arsip (`archives.status`, CHECK constraint di SQLite)
`draft` → `pending_verification` → `approved_booked` → `in_warehouse` → (`borrowed` ⇄ `in_warehouse`) / `taken` / `pending_destruction` → `destroyed`.

Nilai harus persis sama (data lama). Transisi lengkap + role yang boleh: **TODO Fase 0** (isi dari `INVENTARIS-PIC-DEPT.md` dan `ArchiveStatusController`, `ArchiveVerificationController`, `BorrowingController`, `DestructionController` L7). Setiap transisi = satu Action yang memeriksa status asal.

## Penomoran box
Rujukan: `indraco-arsip-laravel-7/app/Services/NumberingService.php` (`generateBoxCode(Archive $archive, bool $preview = false)`, `toRoman()`).
- Pola dari `numbering_formats.pattern`, placeholder `{COMPANY}`, `{DEPT}`, `{YEAR}`, `{ROMAN_MONTH}`, `{COUNTER}`, `{COUNTER_BOX}`; `padding` default 4.
- Counter **atomik**: `UPDATE numbering_formats SET current_counter = current_counter + 1 WHERE id = ? RETURNING current_counter` di dalam transaksi yang sama dengan penyimpanan arsip.
- `preview = true` tidak boleh menaikkan counter (pernah jadi bug L7).
- `box_number` unik untuk arsip aktif: partial UNIQUE index `WHERE status NOT IN ('taken','destroyed','borrowed')` + validasi aplikasi dengan kondisi sama.

## Retensi
- Sumber masa simpan: type berkas (`master_archives.retention_years`) → departemen (`departments.retention_years`) → 5 tahun.
- Tanggal kedaluwarsa & label durasi: rujukan `app/Http/Controllers/Traits/FormatsArchiveLabels.php` (period_end_date + retention_years, atau end_year + retention_years) dan `HandlesArchiveExtensions.php`. Satu implementasi di L13 (mis. `Services/RetentionCalculator`), dites dengan contoh nilai dari L7.
- Peringatan masa simpan ≤ 90 hari.

## Peminjaman / pemusnahan / perpanjangan
- Ada persetujuan departemen (`department_approval_by`, `department_approved_at`) dan file scan persetujuan. Detail alur: **TODO Fase 0**.
- Anti double-submit wajib (`PROJECT_RULES_SQLITE.md` §3.5.e).

## Verifikasi
- Tes per transisi: status sah lolos, status tidak sah ditolak, role/departemen salah 403.
- Tes konkurensi penomoran: beberapa request paralel → nomor unik berurutan tanpa lompat (kecuali gagal simpan).
