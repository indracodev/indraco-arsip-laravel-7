# Modul: ARSIP — pengajuan box multi-box (PIC Departemen)
Status: dibangun (Fase 4b) — menunggu ACC pemilik. Katalog (Fase 3) tetap; detail arsip, impor Excel, penyimpanan kembali menyusul.

## Route lama → baru (URL & nama L7 sama)
| L7 | L13 | Akses |
|---|---|---|
| GET /archives/create `archives.create` (ArchiveController@create) | ArchiveSubmissionController@create | `role:pic_dept` (admin lolos) + Policy create; gudang 403 |
| POST /archives `archives.store` (storeMultiBox) | @store | idem |
| GET /archives/{a}/edit `archives.edit` | @edit | Policy update |
| PUT /archives/{a} `archives.update` (updateMultiBox) | @update | Policy update (+ `original_updated_at` wajib) |
| DELETE /archives/{a} `archives.destroy` | @destroy | Policy delete + harus draft |
| POST /archives/batch-delete-drafts `archives.batch_delete_drafts` | @batchDestroy | `role:pic_dept` (L7: tanpa cek role → gudang bisa hapus draft semua dept) |
| POST /api/archives/check-similarity `api.archives.check_similarity` | ArchiveSimilarityController | `role:pic_dept`; PIC dipaksa dept sendiri |
| jalur single-box, `api/departments/{d}/master-archives` (fallback JS), `api/archives/calculate-retention` | DROP | data Master Berkas ikut dirender di halaman |

## Aturan bisnis (sama dengan L7 kecuali ditandai)
- Draft ("Simpan Sementara (Draft)", `submit_action=draft`): tanpa field wajib; seluruh form = **1 baris `archives` status `draft`**, kolom dari box pertama, `title` = judul box 1 (+ " (N Box)" bila >1), `content_description` = JSON `{is_multi_box_draft:true, boxes:[...]}` (box tervalidasi, `inti`→null), semua butir semua box ke baris itu (nomor urut lanjut). Form kosong → "Draft Box 1" + butir "Draft Dokumen".
- Ajukan: 1 baris per box status `pending_verification`, `box_number` NULL (nomor dibuat gudang saat verify), `created_at` box ke-i = now + i dtk, satu scan/lampiran dipakai semua box. Dari draft: baris draft dipakai jadi box 1, box lain baris baru.
- Periode: form selalu kirim `periode` "N Bulan" → start = awal bulan tgl serah terima, end = akhir bulan (start + N bulan); `retention_years` = floor(N/12); expiry = end. `periode_doc`/`period_yy_mm` = rentang bulan butir (`min - max`) atau "N Bulan". `period_text` "N Bulan (s/d <Month YYYY>)" **bulan bahasa Inggris** (L7 locale `en`). Draft: `periode_doc` = `Y/m` tgl serah terima. Format lain (fallback L7) di `ArchiveBoxBuilder`.
- Maks periode 60 bulan, **120 untuk kode dept FAT/FIN/FATCLM** (ASK 12: tanpa cek nama user). Butir diajukan di-clamp ke bulan berjalan, start ≤ end.
- Judul box unik per dept (status ≠ destroyed, LOWER(TRIM)), juga di dalam satu pengajuan — dicek **di dalam transaksi** (sekaligus guard double-submit). Butir kembar identik (nama+periode+keterangan) ditolak (`duplicate_item`).
- Scan Formulir Input wajib untuk ajukan (atau file lama tidak dihapus saat edit).
- Edit: PIC hanya draft dept sendiri (403 selain itu — **perbaikan finding #4/B3**); admin semua arsip (status non-draft dipertahankan, 1 box, log `ARCHIVE_UPDATE`); gudang 403. Optimistic lock `original_updated_at` (wajib) + status harus sama, dicek di dalam transaksi → pesan "Konflik Perubahan: …".
- Hapus: hanya draft; PIC hanya dept sendiri (baris dept lain diabaikan diam-diam seperti L7). File dihapus setelah commit dan hanya bila tak dirujuk arsip lain.
- Log (`activity_logs`, modul `DOKUMEN_ARSIP`, ref = nama user): `ARCHIVE_DRAFT_SAVE`, `ARCHIVE_DRAFT_UPDATE`, `ARCHIVE_CREATE` (juga "… dari draft …"; `ARCHIVE_SUBMIT` hanya dari jalur single-box lama 2026-10-06), `ARCHIVE_DRAFT_DELETE`, `ARCHIVE_DRAFT_BATCH_DELETE`. `system_events`: `archive_draft_created`, `archive_submitted`, `archive_updated` (+`changes`), `archive_deleted` (payload L7).

## Form (create / edit)
| Bagian | Field (name) | Label | Wajib ajukan / draft | Default / pilihan |
|---|---|---|---|---|
| 1 | company_name (hidden, tampilan terkunci) | PERUSAHAAN ENTITAS * | – | PT INDRACO GLOBAL INDONESIA |
| 1 | department_id | DEPARTEMEN PEMILIK ARSIP * (edit: DEPARTEMEN PEMILIK *) | PIC: terkunci, tidak dikirim; admin: ya / tidak | dept akun |
| 1 | sub_department_id (header, hidden `inti`→'') | SUB-DEPARTEMEN (Opsional) | tidak | -- Pilih Sub-Departemen --, Inti Departemen, sub aktif |
| 1 | tgl_penyerahan (date, max hari ini) | TGL. SERAH TERIMA FISIK KE GUDANG * (edit: TGL. PENYERAHAN DOKUMEN *) | ya, ≤ hari ini / tidak | hari ini |
| Box | boxes[i][title] | NAMA / JUDUL BOX * + cek kemiripan | ya, max 255 / tidak | placeholder L7 |
| Box | boxes[i][sub_department_id] | SUB-DEPARTEMEN (Opsional) | tidak (harus sub dept ini) | -- Ikuti Sub-Dept Induk / Semua --, Inti Departemen |
| Box | boxes[i][document_type] (readonly) | KATEGORI DOKUMEN (Terkunci) | tidak, max 100 | dari Master Berkas |
| Box | boxes[i][physical_condition] | KONDISI FISIK WADAH BOX * | ya, max 100 / tidak | Baik / Box Karton Standar TB 30g |
| Box | periode_bulan + "Ikuti Periode Box Sebelumnya" → hidden boxes[i][periode] | PERIODE DOKUMEN (BULAN) * | – | 1; preset 1,3,4,6,12,15,24,60(,120) |
| Butir | …[document_name] (select Master Berkas) | NAMA DOKUMEN / BERKAS ARSIP * | ya, max 255 / tidak | -- Pilih dari Master Berkas Arsip -- |
| Butir | …[period_start] / …[period_end] (month) | PERIODE MULAI * / PERIODE SELESAI * | ya, max 50 / tidak | bulan ini |
| Butir | …[notes] | KETERANGAN * ("No. berkas fisik / catatan") | ya, max 255 / tidak | – |
| 3 | scan_input_form | 1. Scan / Berkas Formulir Pengajuan Fisik * (edit: Scan Formulir Input) | wajib ajukan; pdf/jpg/jpeg/png 500 KB | badge WAJIB UNTUK PENGAJUAN |
| 3 | file | 2. Foto Fisik Box / Lampiran Digital | tidak; 500 KB | badge OPSIONAL |
| Edit | original_updated_at, remove_scan_input_form, remove_file (hidden) | – | – | – |
Pesan validasi field: bawaan Laravel (bahasa Inggris, sama dengan L7). Pesan JS sebelum kirim: teks L7 di modal (bukan `alert()`).

## File L13
- `app/Http/Controllers/ArchiveSubmissionController.php`, `ArchiveSimilarityController.php`, `app/Http/Requests/ArchiveSubmissionRequest.php`, `app/Policies/ArchivePolicy.php` (create/update/delete)
- `app/Actions/Archive/{SaveArchiveDraft,SubmitArchiveBoxes,DeleteArchiveDrafts,EnsureArchiveUnchanged}.php`
- `app/Services/{ArchiveBoxBuilder (periode/retensi/butir), ArchiveAttachments, FileStorage, ArchiveSimilarity, ArchiveFormData, SystemEventStream}.php`
- `resources/views/archives/form.blade.php` + `partials/{form-unit,form-boxes,form-box-fields,form-box-items,form-files,duplicate-box-modal}.blade.php`; katalog: `index.blade.php`, `partials/catalog-row.blade.php` (Edit/Revisi & Ajukan, Hapus, ribbon "Hapus Draft (n)")
- `public/js/pages/archive-form.js` (Alpine `archiveForm`: box, butir, periode, Master Berkas), `archive-form-submit.js` (cek sebelum kirim, kemiripan, 500 KB, tempel Excel, autosave localStorage `dms_draft_archive_create`)
- Upload: disk `local` (`storage/app/private`), path DB tetap `archive_scans/…` / `archive_digital/…`, nama acak 40 karakter + ekstensi dari isi file.

## Tes
`tests/Feature/Archive/{SubmissionTest,SubmissionAccessTest,SubmissionUploadTest}.php` + `ArchiveFixtures.php` (trait): komposisi form, draft 1 baris + JSON, draft tanpa wajib, nilai baris ajukan vs L7, validasi wajib/opsional, butir kembar, aturan 120/60 bulan, edit→ajukan memakai baris draft, gudang 403, sub-dept dept lain, edit dept lain/non-draft 403, double submit, optimistic lock, hapus batch hanya draft sendiri + file, hapus satuan, kemiripan per dept, upload ditolak (.php, ekstensi ganda, PHP berkedok PDF, HTML berkedok PNG, > 500 KB), disimpan privat.

## Temuan & keputusan
- Perbaikan: #4/B3 (edit tanpa cek dept/status), batch delete tanpa cek role, file dihapus sebelum transaksi sukses, `withInput` box hilang saat error server (L13 memulihkan semua box dari old input).
- Usulan keputusan: lihat laporan Fase 4b (disk privat, log `ARCHIVE_UPDATE` untuk edit admin non-draft, bulan Inggris di `period_text`).

## Sisa pekerjaan
- Pratinjau/cetak "Form Pengajuan" (tombol nonaktif), Impor Excel (ASK 5, terakhir), penyimpanan kembali (`origin_borrowing_log_id`, modul PENARIKAN), nama dokumen kustom admin (L7 `prompt()`), pratinjau file tersimpan di edit (modul FILE), kompres gambar di klien (L7 uploader).
