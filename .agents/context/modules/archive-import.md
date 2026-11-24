# Modul: IMPOR MULTI-BOX EXCEL (putaran H, keputusan ASK 5)
Status: dibangun — menunggu ACC pemilik.

## Route lama → baru (URL & nama L7 sama, `routes/modules/archive-import.php`)
| L7 | L13 | Akses |
|---|---|---|
| GET /archives/import/template `archives.import.template` (?format=xlsx\|csv) | ArchiveImportController@template | `role:pic_dept` (admin lolos) + Policy create; gudang 403 |
| POST /archives/import/preview `archives.import.preview` | @preview | idem |
| POST /archives/import/commit `archives.import.commit` | @commit | idem |
| POST /archives/import/load-to-form `archives.import.load_to_form` | @loadToForm | idem |

## Aturan
- Upload: wajib, maks 5 MB, ekstensi xlsx/xlsm/csv (pesan L7 "Format file tidak didukung…"), **isi dicek**: xlsx/xlsm = zip berisi `xl/workbook.xml`; csv = MIME teks. Bagian XML > 30 MB setelah unzip ditolak (`SpreadsheetReader`, dibuat putaran E). Error JSON `{success:false, errors:[…]}` (422).
- Pembacaan (port L7 `MultiBoxExcelService::parseFile`, `ArchiveImportParser` + `ArchiveImportValues`): header di 5 baris pertama (alias L7), sub-header Ruang/Rak/No; box baru = NO_BOX baru, atau JUDUL_BOX baru bila tanpa kolom box; NO_BOX yang muncul lagi melanjutkan box itu; kolom box terbawa ke baris bawahnya; dept/sub-dept box = baris pertamanya (kode → nama → sebagian ≥ 4 huruf; kosong = dept user); periode toleran (YYYY-MM, YYYY/MM, MM/YYYY, YYYY, serial Excel, "Jan 25", rentang "jan 25 - Des 25"); nama dokumen dicocokkan fuzzy ke Master Berkas (nama diganti nama master, kategori dari master butir pertama); judul kosong → "Arsip {BOX} {Dept} - {dokumen}".
- **Satu aturan dengan form**: box hasil baca berbentuk input form (`boxes[i][…]`); `MASA_SIMPAN_BULAN` → "PERIODE DOKUMEN (BULAN)" (L7 load-to-form: tahun × 12, kosong = 60, dibatasi 60/120 bulan + peringatan). Validasi = `ArchiveSubmissionRequest::rules()/after()` (mode draft atau ajukan); simpan = `SaveArchiveDraft` / `SubmitArchiveBoxes`; nilai pratinjau = `ArchiveBoxBuilder`. Tidak ada rumus periode/retensi kedua.
- Pratinjau: `errors` (memblokir semua: dept tidak valid, PIC dept lain per baris, validasi draft) dan `warnings` (cocok/tidak cocok Master Berkas, periode tak dikenali, masa simpan dibatasi, kolom lokasi diabaikan, "Belum lengkap untuk Verifikasi Gudang: Baris N: …"). Nomor baris = nomor baris Excel asli.
- Simpan (`commit`), file **dibaca ulang di server** (L7 mempercayai JSON pratinjau dari browser, termasuk department_id):
  - `draft` → 1 baris draft multi-box per departemen (sama dengan form), status `draft`.
  - `pending_verification` → 1 baris per box, `box_number` NULL; **scan Formulir wajib** (aturan form).
  - `in_warehouse` ditolak (422); admin melihat opsi/tombol "Tersimpan di Gudang (Data Existing)" / "Simpan Langsung ke Gudang" nonaktif.
  - Semua departemen dalam satu transaksi (gagal satu = batal semua). Pesan L7 "Berhasil mengimpor N box arsip dengan M butir dokumen."; log Action + `ARCHIVE_BATCH_IMPORT` modul `IMPORT_EXCEL` (teks L7). Butir di-insert per 250 (Action).
  - PIC: dept file ≠ dept akun → 422 dengan baris; departemen yang dipakai selalu dept akun.
- Lanjutkan ke Form Pengajuan: file dibaca ulang → old input (`boxes`, `department_id`, `tgl_penyerahan`) + flash `excel_import` → `archives.create?from=excel_import`; banner L7 "Data Pengajuan Berhasil Dimuat dari File Excel!" (`partials/import-banner`).
- Template: sheet DATA_ARSIP (PIC 9 kolom; admin + LOKASI_RUANG/LOKASI_RAK/NO_SLOT) dengan contoh dari master nyata, sheet REFERENSI_KODE (dept, sub-dept, rak [admin], Master Berkas, petunjuk) — teks L7. CSV: BOM UTF-8, nilai berawalan `= + - @` diberi `'`.

## UI
- Katalog: tombol "Impor Multi-Box (Excel)" (bukan gudang) → modal `archive-import-modal` (`partials/import-modal` + `import-preview`), `?open_import=1` membuka otomatis. Form create: "Impor Excel" → katalog `?open_import=1` (L7).
- Modal: petunjuk per role, Template Excel (.xlsx) / CSV + notifikasi "Berkas Template Berhasil Diunduh!", pilih file, Validasi File, kesalahan/peringatan, total, Status Pasca-Impor (Draft Sementara / Verifikasi Gudang [+ scan wajib] / admin: Tersimpan di Gudang nonaktif), kartu box + tabel butir, footer Tutup / Lanjutkan ke Form Pengajuan / Simpan sebagai Draft | Simpan & Ajukan ke Gudang (konfirmasi modal) / admin: Simpan Langsung ke Gudang (nonaktif).
- Lebar modal 64rem (`#archive-import-modal .modal__dialog`, modal.css). DOM katalog 50 baris tetap ≤ 1.500 (modal 90 elemen).

## File L13
`app/Services/{ArchiveImport (pratinjau/simpan/isi form), ArchiveImportParser, ArchiveImportValues, ArchiveImportTemplate}.php`, `app/Http/Controllers/ArchiveImportController.php`, `app/Http/Requests/ArchiveImportRequest.php`, `routes/modules/archive-import.php`, `resources/views/archives/partials/import-{modal,preview,banner}.blade.php`, `public/js/pages/archive-import.js` (Alpine `archiveImport`). Memakai `App\Services\SpreadsheetReader` (putaran E).

## Tes
`tests/Feature/ArchiveImport/{ImportPreviewTest,ImportCommitTest,ImportTemplateTest}.php` + `ImportFixtures` (xlsx/csv kecil hasil generate): pengelompokan & carry-over, fuzzy, format periode, nomor baris, PIC dept lain, admin tanpa dept, file rusak (ekstensi, PHP berkedok xlsx, zip berkedok csv, > 5 MB, kosong, tanpa header), gudang 403, draft = 1 baris JSON, ajukan wajib scan & identik dengan hasil form, in_warehouse ditolak, multi-dept all-or-nothing, 600 butir → insert 250/250/100, load-to-form, template PIC/admin/CSV escape, tombol katalog/form.

## Beda dengan L7 (usulan keputusan)
1. Radio "Status Pasca-Impor" L7 tidak berpengaruh (PIC hanya bisa "Lanjutkan ke Form"); L13 tombol simpan mengikuti radio (draft / verifikasi).
2. Nilai box = aturan form (MASA_SIMPAN sebagai periode bulan form), bukan rumus rentang-butir impor L7; draft = 1 baris multi-box seperti form (L7 impor: 1 baris draft per box).
3. Verifikasi Gudang via impor wajib scan Formulir (L7 impor tanpa scan).
4. MASA_SIMPAN kosong = 60 bulan (L7 load-to-form); L7 commit memakai default 5 tahun juga sehingga fallback sub-dept/dept tidak pernah terpakai.
5. Butir diajukan di-clamp ke bulan berjalan (aturan form).
6. `in_warehouse` (data existing langsung ke rak: nomor box, slot, entry log) ditunda sampai jalur check-in GUDANG bisa dipakai ulang.

## Sisa
- `in_warehouse` admin (pakai Action GUDANG verifikasi/check-in, bukan logika baru).
- localStorage autosave form lama tidak dihapus saat form diisi dari Excel (L7 menghapus); autosave memang tidak dipulihkan selama ada old input.
