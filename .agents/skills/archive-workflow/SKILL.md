---
name: archive-workflow
description: Aturan bisnis inti DMS Indraco — status arsip/box, transisi yang sah per role, penomoran box atomik dengan mode preview, retensi dan tanggal kedaluwarsa, peminjaman, perpanjangan, pemusnahan BAP. Pakai saat menyentuh Archive, BorrowingLog, DestructionLog, ArchiveExtensionLog, NumberingFormat.
---

# Alur arsip & penomoran

## Status arsip (`archives.status`, CHECK constraint di SQLite)
`draft` → `pending_verification` → `approved_booked` → `in_warehouse` → (`borrowed` ⇄ `in_warehouse`) / `taken` / `pending_destruction` → `destroyed`.

Nilai harus persis sama (data lama). Setiap transisi = satu Action yang memeriksa status asal **di dalam** transaksi IMMEDIATE.

| Dari (arsip) | Aksi | Ke (arsip) | Log | Siapa | Action L13 |
|---|---|---|---|---|---|
| – / `draft` | Simpan draft / Ajukan | `draft` / `pending_verification` | – | PIC dept sendiri, admin | `Actions/Archive/*` |
| `pending_verification` | Verifikasi + nomor box / tolak | `approved_booked` / `draft` + `rejection_note` | – | gudang, admin | modul GUDANG (D) |
| `approved_booked` | Check-in rak | `in_warehouse` | – | gudang, admin | modul GUDANG (D) |
| `in_warehouse` | Ajukan penarikan | tetap | borrowing `requested` | PIC dept sendiri, admin | `SubmitBorrowing` |
| `in_warehouse` + penarikan aktif | Keluarkan arsip | `taken` (permanen) | `dispatched` | gudang, admin | `DispatchBorrowing` |
| `in_warehouse` + penarikan aktif | Tolak penarikan | tetap | `rejected` | gudang, admin | `RejectBorrowing` |
| `in_warehouse` (kandidat retensi) | Ajukan pemusnahan | `pending_destruction` | destruction `pending` | PIC dept sendiri, admin | `ProposeDestruction` |
| `in_warehouse` (kandidat retensi) | Sahkan pemusnahan (admin isi No. BAP) | `destroyed`, keluar rak | `completed` | admin | `ProposeDestruction` |
| `pending_destruction` | Setujui | tetap (label Antrean Pemusnahan) | `pending` → `approved` | gudang, admin | `ApproveDestruction` |
| `pending_destruction` | Tolak (alasan) | `in_warehouse` | `pending`/`approved` → `rejected` | gudang, admin | `RejectDestruction` |
| `pending_destruction` | Eksekusi BAP | `destroyed`, keluar rak | `approved` → `completed` | gudang, admin | `CompleteDestruction` |
| `destroyed` | Upload BAP | tetap | `completed` (sertifikat) | gudang, admin | `UploadDestructionCertificate` |
| `in_warehouse` (kandidat retensi) | Ajukan perpanjangan | `pending_extension` | extension `pending` | PIC dept sendiri, admin | `RequestExtension` |
| `pending_extension` | ACC | `in_warehouse` + expiry/retensi baru | `approved` | gudang, admin | `ApproveExtension` |
| `pending_extension` | Tolak | `in_warehouse` | `rejected` | gudang, admin | `RejectExtension` |
Kandidat retensi = `in_warehouse`, tanpa log pemusnahan pending/approved/completed, tanpa perpanjangan pending (`RetentionList::candidates`, B2). Detail: `context/modules/{borrowing,retention}.md`.

## Penomoran box
Rujukan: `indraco-arsip-laravel-7/app/Services/NumberingService.php` (`generateBoxCode(Archive $archive, bool $preview = false)`, `toRoman()`).
- Pola dari `numbering_formats.pattern`, placeholder `{COMPANY}`, `{DEPT}`, `{YEAR}`, `{ROMAN_MONTH}`, `{COUNTER}`, `{COUNTER_BOX}`; `padding` default 4.
- Counter **atomik**: `UPDATE numbering_formats SET current_counter = current_counter + 1 WHERE id = ? RETURNING current_counter` di dalam transaksi yang sama dengan penyimpanan arsip.
- `preview = true` tidak boleh menaikkan counter (pernah jadi bug L7).
- `box_number` unik untuk arsip aktif: partial UNIQUE index `WHERE status NOT IN ('taken','destroyed','borrowed')` + validasi aplikasi dengan kondisi sama.

## Retensi
- Sumber masa simpan: type berkas (`master_archives.retention_years`) → departemen (`departments.retention_years`) → 5 tahun.
- Tanggal kedaluwarsa, H-30, perpanjangan, label durasi: `app/Services/RetentionCalculator.php` (satu implementasi, dites dengan baris L7 di `tests/Feature/Retention/RetentionListTest.php`); expiry saat pengajuan box: `ArchiveBoxBuilder`. Aturan FAT (120 bulan) = `ArchiveBoxBuilder::maxPeriodMonths` (kode dept saja, ASK 12).
- Peringatan masa simpan: katalog/detail ≤ 90 hari (`ArchiveExpiry`); daftar [Form 3] H-30 (30 hari sebelum akhir bulan expiry, L7).

## Peminjaman / pemusnahan / perpanjangan
- Ada persetujuan departemen (`department_approval_by`, `department_approved_at`) dan file scan persetujuan. Detail alur: **TODO Fase 0**.
- Anti double-submit wajib (`PROJECT_RULES_SQLITE.md` §3.5.e).

## Verifikasi
- Tes per transisi: status sah lolos, status tidak sah ditolak, role/departemen salah 403.
- Tes konkurensi penomoran: beberapa request paralel → nomor unik berurutan tanpa lompat (kecuali gagal simpan).
