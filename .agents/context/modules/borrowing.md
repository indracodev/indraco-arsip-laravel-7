# Modul: PENARIKAN (penarikan berkas / borrowings)
Status: dibangun (putaran B) — menunggu verifikasi koordinator + ACC pemilik. Penyimpanan kembali (re-store) belum (lihat Sisa).

## Route lama → baru (URL & nama L7 sama)
| L7 | L13 | Akses |
|---|---|---|
| GET /borrowings `borrowings.index` | BorrowingController@index | login; daftar lewat `Archive::visibleTo` (PIC hanya dept sendiri) |
| GET /borrowings/create `borrowings.create` | @create | Policy `create` (admin + PIC Dept; gudang 403 seperti L7) |
| POST /borrowings `borrowings.store` | @store (`BorrowingRequest`, `SubmitBorrowing`) | Policy `create` + per box `create [BorrowingLog, $archive]` → dept lain **403** |
| – (L7: modal `x-form-pengajuan-modal` tipe Penarikan) | GET /borrowings/form-penarikan `borrowings.form_print` (@printForm) | Policy `create`; hanya box yang terlihat user |
| POST /borrowings/{b}/dispatch `borrowings.dispatch` | BorrowingApprovalController@dispatch (`DispatchBorrowing`) | Policy `process` (gudang + admin) |
| POST /borrowings/{b}/reject `borrowings.reject` | @reject (`RejectBorrowing`) | Policy `process` (L7 juga meloloskan PIC Dept: temuan #14) |
| POST /borrowings/{b}/dept-approve | tidak dibangun (404) | ASK 4 |
| POST /borrowings/{b}/approve, /return, PUT /borrowings/{b} | tidak dibangun (404/405) | triage: 0 pemanggil UI; ASK 2 (selalu permanen) |

## Aturan bisnis
- Satu baris `borrowing_logs` per box, status `requested`, `approval_status` `pending`, `expected_return_date` NULL (**selalu permanen**, ASK 2; `is_permanent`/`expected_return_date` dari form diabaikan), `approval_file` = `scan_approval_borrow` = satu scan untuk semua box, `is_approval_uploaded` 1, `request_date` now. Box di-`touch()` (naik ke atas katalog, L7).
- Box harus `in_warehouse` dan tanpa penarikan aktif (`requested|dept_approved|approved`); dicek sebelum dan **di dalam transaksi IMMEDIATE** (guard double-submit/dua sesi, DomainException). Urutan cek L7: tidak ditemukan → tidak di gudang → sedang aktif. Dept dicek lebih dulu (403) agar judul box dept lain tidak bocor lewat pesan.
- Upload `FileStorage::store()` → `borrowing_approvals/` (disk privat) sebelum transaksi; dihapus bila transaksi gagal. Disajikan hanya lewat `files.preview_data|download` (viewer JSON base64, R27) (`borrowing`, `approval_file|scan_approval_borrow`) + `ArchivePolicy@view` arsip induk.
- Dispatch ("Keluarkan Arsip", gudang/admin): status asal harus aktif + box `in_warehouse` → log `dispatched`, `approval_status` approved, `borrow_date`, `pic_gudang_id`, `department_approved_at/by` diisi bila kosong (L7: pengisi = gudang), snapshot box/judul/dept/butir; arsip `taken` (`borrowed` hanya bila ada `expected_return_date`, data lama), lokasi & slot dikosongkan, `current_box_count` lokasi −1. Tidak ada tahap approve terpisah (L7 UI langsung dispatch dari `requested`).
- Reject ("Tolak", gudang/admin): alasan wajib max 500; status asal harus aktif; `approval_notes`; `pic_gudang_id` = user bila gudang. Arsip tetap `in_warehouse`; PIC melihat "Tarik Ulang".
- Log `activity_logs` modul `PENARIKAN_BERKAS`: `BORROW_CREATE` (ref = 2 nomor box pertama / "Batch n Box"), `BORROW_DISPATCH`, `BORROW_REJECT` — teks L7. `system_events`: `borrowing_created` / `borrowing_updated` (payload L7 BorrowingLog) + `archive_updated` (touch / dispatch). **Titik panggil notifikasi realtime tunggal**: `SystemEventStream::borrowings()`.
- Beda dari L7 (perbaikan): status asal dicek (L7 bisa dispatch log ditolak / reject log yang sudah ditarik); scan tanda terima gudang disimpan di `scan_approval_borrow` saja (L7 menimpa `approval_file` PIC); `current_box_count` benar-benar turun (L7 membaca lokasi setelah di-null-kan); dispatch ditolak bila box bukan `in_warehouse` (L7 data: log #3 `requested` untuk arsip 34 `pending_destruction`); reject hanya gudang/admin; PIC dept lain 403 (L7: kembali dengan pesan); daftar PIC = box dept sendiri (L7 juga log buatan user sendiri di dept lain).

## Status & transisi
| Dari | Aksi | Ke (log / arsip) | Siapa |
|---|---|---|---|
| – (arsip `in_warehouse`, tanpa log aktif) | Ajukan Penarikan | `requested` / tetap `in_warehouse` | PIC dept sendiri, admin |
| `requested`, `dept_approved`, `approved` (+ arsip `in_warehouse`) | Keluarkan Arsip | `dispatched` / `taken` | gudang, admin |
| `requested`, `dept_approved`, `approved` | Tolak (alasan) | `rejected` / tetap | gudang, admin |
| `dispatched`, `rejected`, `returned` | dispatch / reject | ditolak: "Pengajuan penarikan ini sudah diproses (status: …). Muat ulang halaman." | – |
| `rejected` (arsip `in_warehouse`) | Tarik Ulang → form baru | `requested` (log baru) | PIC dept sendiri, admin |
Label/warna: `BorrowingList::STATUSES` (Diajukan User/BARU kuning, Disetujui Dept/VERIF DEPT, Disetujui Gudang/SIAP AMBIL, Sudah Ditarik/DITARIK biru, Dikembalikan/KEMBALI hijau, Ditolak/DITOLAK merah).

## Form "Formulir Permintaan Penarikan Berkas Arsip [Form 2]"
| Bagian | Field (name) | Label | Wajib | Aturan / default |
|---|---|---|---|---|
| Header | – | "n Box Dipilih", **Form Penarikan** (cetak), **Kembali (Esc)** | – | – |
| Info (PIC) | – | Filter Keamanan Departemen Terkunci … | – | – |
| 1 | cari (tanpa name) | PENCARIAN BERKAS GUDANG UNTUK DITAMBAHKAN * | – | 15 box pertama tanpa kata kunci; cari no box/judul/periode/kode dept/lokasi; penanda Sedang Diajukan Penarikan (tidak bisa dipilih), Pernah Ditolak, Sudah di List |
| 1 | archive_ids[] (hidden, per box) / archive_id (fallback L7) | Daftar Box Terpilih (n Box) | min 1 → "Minimal satu berkas arsip wajib dipilih untuk pengajuan penarikan." | box `in_warehouse` dept sendiri; akordeon satu terbuka: catatan penolakan sebelumnya, DEPARTEMEN / LOKASI RAK GUDANG / PERIODE DOKUMEN / KONDISI FISIK, tabel rincian dokumen; hapus dengan konfirmasi |
| 2 | purpose (textarea) | ALASAN LENGKAP PENARIKAN * | ya, max 500 | placeholder L7 |
| 3 | approval_file | Berkas Dokumen Persetujuan (Approval) * badge WAJIB UNTUK PENARIKAN | ya; pdf/jpg/jpeg/png, 500 KB, isi dicek | banner "Cetak Form Penarikan" |
| Hidden | from, is_permanent=1 | – | – | – |
| Bar | – | Batal / Ajukan Penarikan Arsip / Ajukan Penarikan (n Box Arsip) | tombol aktif bila ≥1 box + file valid | petunjuk status L7 |
Pesan validasi: bawaan Laravel (Inggris, sama L7) kecuali pesan box di atas. Autosave `localStorage dms_draft_borrowing_create` (per user, seperti L7). Pratinjau "Form Penarikan" = kertas `archives/form-print` dengan tipe **Penarikan**, catatan = alasan (atau teks default L7), baris dari box terpilih (data tersimpan); dibuka di tab baru. Kolom Label Box kosong (L7 mengisi lokasi rak).

## Modal di daftar (per baris, dirender server)
- **Detail**: "DETAIL PENARIKAN: {box}", badge status + Pernah Ditolak; Data Pengajuan & Fisik (Pemohon+email, Departemen Box, Lokasi Rak Gudang, Tgl Diajukan, Tgl Keluar Fisik, Estimasi Kembali "Diambil Permanen (Tidak Dikembalikan)"), Keperluan, Catatan Tambahan; Berkas Dokumen Dalam Box (disembunyikan untuk gudang, L7); Berkas Terlampir; Jejak Persetujuan & Penyerahan; Catatan Penolakan; Log Riwayat Penolakan; "Buka Detail Master Arsip", Tutup.
- **Keluarkan Arsip** (gudang/admin): Informasi Box & Lokasi Fisik; Pemeriksaan Dokumen Pengajuan + checkbox wajib "Saya telah memeriksa …" (klien saja, seperti L7); "Upload Scan Bukti Tanda Terima Fisik (Opsional)" `scan_approval_borrow`; Tolak Pengajuan / Batal / Keluarkan Arsip.
- **Tolak**: ringkasan, "Alasan Penolakan *" (`reason`, max 500), peringatan; Batal / Konfirmasi Tolak Pengajuan.
- Berkas: pratinjau di modal `#file-viewer` (`archive-show.js`, JSON base64 `files.preview_data` → blob, R27 IDM) / Unduh.

## Katalog
- Tombol baris "Tarik"/"Tarik Ulang" → `borrowings.create?archive_ids=&from=archives` (sudah ada). Baru: ribbon "Tarik Berkas (n Box)" (L7 `catalog_batch_ribbon`) untuk box tercentang `in_warehouse` tanpa penarikan aktif milik dept sendiri/admin.
- Penanda: DITARIK saat penarikan aktif atau arsip `borrowed/taken`; lokasi "- (Sudah Ditarik)"; status "Keluar (Out)"; "Sedang Ditarik" untuk PIC.

## File L13
- `app/Http/Controllers/BorrowingController.php` (index, create, store, printForm), `BorrowingApprovalController.php` (dispatch, reject)
- `app/Http/Requests/BorrowingRequest.php`, `app/Policies/BorrowingLogPolicy.php` (create, process)
- `app/Actions/Borrowing/{SubmitBorrowing,DispatchBorrowing,RejectBorrowing}.php`
- `app/Services/BorrowingList.php` (daftar, kandidat form, berkas per baris, peta status), `BorrowingFormPrint.php`, `SystemEventStream::borrowings()`
- `resources/views/borrowings/{index,create}.blade.php`, `partials/{row,row-modals,file-cards,create-boxes}.blade.php`; `public/js/pages/borrowing-create.js`
- Katalog: `archives/index.blade.php` (form `batch-borrow`), `archives/partials/catalog-row.blade.php` (checkbox `data-borrowable`)
- Model: hanya docblock `@property` (BorrowingLog, ArchiveItem, Archive lokasi) untuk Larastan.

## Tes
`tests/Feature/Borrowing/{BorrowingRequestTest,BorrowingProcessTest}.php` + `BorrowingFixtures.php`: komposisi form + hanya box dept sendiri `in_warehouse`, gudang 403, 1 log permanen per box + nilai kolom + log + event payload, pesan sukses 1/n box + redirect `from`, wajib (box/alasan/file) + max 500, pesan box kosong, box bukan di gudang, double submit ditolak, dept lain 403 (admin boleh), upload berbahaya/ >500 KB ditolak tanpa sisa file, cetak Form Penarikan (tipe, dept lain disaring), dispatch (log, arsip taken, slot, counter, snapshot, file PIC tetap), dispatch/reject kedua ditolak, dispatch setelah reject ditolak, box keluar gudang ditolak, reject wajib alasan + Tarik Ulang + PERNAH DITOLAK, PIC/dept lain 403 proses, daftar & file per dept, 1 baris per box + filter status, rute tanpa UI 404/405.

## Temuan & keputusan
- Menutup temuan #14 bagian penarikan (reject/dept-approve/update tidak terbuka untuk PIC). B4 dipertahankan (selalu permanen). B5: belum (re-store).
- Usulan keputusan: lihat laporan putaran B.

## Sisa pekerjaan
- **Penyimpanan kembali (B5)**: checkbox per log `dispatched` yang `restore_eligibility` + tombol "Ajukan Penyimpanan Kembali (n Box)" → `archives.create?re_store=1&borrowing_ids[]` (box terisi dari snapshot), simpan = arsip baru `pending_verification` + `origin_borrowing_log_id` + `re_store_snapshot`, `borrowing_logs.active_restore_archive_id`; arsip lama tetap `taken`. Butuh perubahan form arsip (`archives/form*`, `archive-form*.js`, `SubmitArchiveBoxes`) — ditunda karena sedang dipegang agen bugfix. Catatan: `restore_eligibility` L7 membaca status arsip **lama** (selalu `taken`) sehingga satu log bisa disimpan kembali berkali-kali; pakai `active_restore_archive_id` sebagai guard.
- Banner/auto-reload realtime (`dms:realtime-sync`) → modul notifikasi (G); event sudah ditulis.
- Kolom Label Box di Form Penarikan; shortcut F2 "Buat Pengajuan" (bentrok dengan F2 global topbar).
