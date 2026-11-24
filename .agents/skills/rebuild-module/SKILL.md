---
name: rebuild-module
description: Prosedur membangun satu modul DMS Arsip Indraco di Laravel 13 dari hasil triage Laravel 7 — struktur file, urutan kerja, komposisi form tetap, uji browser, dan dokumentasi agar bisa dilanjutkan agent lain.
---

# Rebuild satu modul

Laravel 7 = sumber aturan bisnis, alur, dan komposisi form. **Bukan** acuan cara implementasi atau keamanan.

## Kapan dipakai
Saat mulai membangun modul (setelah triage modul itu disetujui pemilik).

## Rujukan Laravel 7
- Route: `indraco-arsip-laravel-7/routes/web.php`
- Controller: `app/Http/Controllers/*`, trait alur panjang di `app/Http/Controllers/Traits/Handles*.php`
- Service: `app/Services/*` (NumberingService, ActivityLogger, MultiBoxExcelService, SpreadsheetReader, MasterDataImportService)
- View: `resources/views/<fitur>/` + `partials/` + `modals/`
- Inventaris: `.agents/INVENTARIS-PIC-DEPT.md`, `.agents/context/triage.md`

## Susunan standar satu modul (contoh: peminjaman)
```text
app/Http/Controllers/BorrowingController.php        # index/create/store/show (tipis)
app/Http/Controllers/BorrowingApprovalController.php # approve/reject/dispatch/return
app/Http/Requests/StoreBorrowingRequest.php
app/Actions/Borrowing/SubmitBorrowing.php            # 1 use case = 1 Action (handle())
app/Actions/Borrowing/ApproveBorrowing.php
app/Policies/BorrowingLogPolicy.php
app/Models/BorrowingLog.php                          # $table eksplisit
resources/views/borrowings/index.blade.php           # ≤ 250 baris
resources/views/borrowings/partials/*.blade.php
resources/js/pages/borrowings.js                     # Alpine.data(...)
tests/Feature/Borrowing/SubmitBorrowingTest.php
.agents/context/modules/borrowing.md                 # catatan modul (wajib)
```

## Langkah
1. Ambil item KEEP/REWRITE modul dari `context/triage.md`.
2. Baca kode L7 sampai jelas: aturan bisnis, status asal→tujuan, siapa boleh, kasus tepi, **komposisi setiap form** (field, label, wajib/opsional, pilihan, default, pesan validasi). Tulis ke `context/modules/<modul>.md` (format di bawah).
3. Untuk setiap route yang mengubah data, jawab: siapa yang boleh dan di mana dicek; input apa yang tidak dipercaya; apa yang terjadi jika dikirim dua kali atau dengan ID departemen lain.
4. Tulis tes dulu: alur normal, validasi (wajib/opsional sama dengan L7), otorisasi (role + departemen lain → 403), double-submit, status tidak sah.
5. Bangun: Form Request → Action (transaksi pendek, lihat skill `sqlite-concurrency`) → Controller tipis → Blade dari komponen (`blade-ui-components`).
6. Jalankan workflow `verify` (termasuk uji browser dan Performance Gate).
7. Commit kecil per modul. Perbarui `progress.md`, `findings.md`, `decisions.md`. STOP untuk ACC.

## Format `context/modules/<modul>.md`
```text
# Modul: <nama>
Status: dibangun | diuji | di-ACC
Route lama → baru: tabel
Aturan bisnis: daftar
Status & transisi: tabel
Form: tabel per form (field | label | wajib | tipe/pilihan | default | pesan)
File L13: daftar file + tanggung jawab
Tes: daftar file tes + skenario
Temuan & keputusan: rujukan ke findings/decisions
Sisa pekerjaan: daftar
```

## Perangkap
- Trait `Handles*` L7 berisi alur panjang (multi-box create/edit): jangan disalin, pecah jadi Action.
- Middleware role L7 meloloskan `admin` ke semua route role (`CheckRole`): pertahankan perilaku ini secara eksplisit di Policy/Gate.
- Pesan sukses/error L7 dipakai user sehari-hari: pertahankan teksnya kecuali tidak jelas.
