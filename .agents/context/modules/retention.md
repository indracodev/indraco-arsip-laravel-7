# Modul: RETENSI ([Form 3] perpanjangan masa simpan, usulan pemusnahan, BAP)
Status: dibangun (putaran C) — menunggu verifikasi koordinator + ACC pemilik. Menutup temuan #5 (destructions.bap, extend_print), B2, B6, B9 (rute retensi), B12 (retensi).

## Route lama → baru (URL & nama L7 sama; file `routes/modules/retention.php`, satu `require` di `routes/web.php`)
| L7 | L13 | Akses |
|---|---|---|
| GET /destructions `destructions.index` | DestructionController@index (`?tab=h30|extensions|destructions`, `?view=destruction|expiry`, `?q=`, `?page=`; P1: hanya tab aktif, 50/halaman, badge = COUNT, H-30 difilter di SQL `RetentionList::due`) | login; isi lewat `Archive::visibleTo` |
| GET /destructions/propose[/{a}] `destructions.propose(.archive)` | @create | `DestructionLogPolicy@create` (admin + PIC Dept; gudang 403 seperti L7) |
| POST /destructions/propose[/{a}] `destructions.store(.archive)` (URI ganda B9) | POST /destructions/propose `destructions.store` (@store) | create + per box dept → 403 |
| – (modal L7 `x-form-pengajuan-modal` tipe Pemusnahan) | GET /destructions/form-pemusnahan `destructions.form_print` | create; hanya box terlihat |
| GET /destructions/bap/{log} `destructions.bap` (tanpa cek: #5) | @bap | `DestructionLogPolicy@view` = `ArchivePolicy@view` arsip |
| POST /destructions/{log}/approve, /reject, /complete, /upload-bap | DestructionApprovalController | `process`: gudang + admin |
| GET /destructions/extend[/{a}] `destructions.extend_form(.archive)` | ExtensionController@create | `ArchiveExtensionLogPolicy@create` (= di atas) |
| POST /destructions/extend[/{a}] (URI ganda) | POST /destructions/extend `destructions.extend_store` | create + per box dept → 403 |
| – (modal tipe Perpanjangan) | GET /destructions/form-perpanjangan `destructions.extend_form_print` | create |
| GET /destructions/extend-print/{a} (tanpa cek: #5) | @print | `ArchivePolicy@view` |
| POST /destructions/extensions/{log}/approve, /reject | @approve / @reject | `process`: gudang + admin |

## Aturan bisnis
- **Satu RetentionCalculator** (`app/Services/RetentionCalculator.php`): `expiryEnd()` (akhir hari, akhir bulan expiry; tanpa expiry: periode akhir + retensi (≠ "N bulan") seperti L7 index), `daysLeft()`, `isDue()` (H-30), `extendedExpiry()` (dasar = expiry ?? periode akhir ?? hari ini, + bulan **tanpa overflow**, akhir bulan), `approvedRetention()` (bulan dasar = periode "N bulan" / masa_simpan_custom / retention_years ×12 / 60 + tambahan → `retention_years`, `periode` "N Tahun"/"N Bulan"), `maxExtensionMonths()` (120 bila dept user atau dept box FAT/FIN/FATCLM via `ArchiveBoxBuilder::maxPeriodMonths`, selain itu 60 — ASK 12, nama user tidak dipakai: B12). Penomoran/expiry saat pengajuan tetap di `ArchiveBoxBuilder`.
- **Kandidat** (`RetentionList::candidates`, dipakai daftar H-30, form, dan aksi): status `in_warehouse`, tanpa log pemusnahan pending/approved/completed, tanpa perpanjangan pending. Dicek sebelum upload dan lagi di dalam transaksi IMMEDIATE (`RetentionList::pick`, anti double submit). L7 hanya mengecualikan destroyed/draft/pending_destruction/pending_extension (B2).
- **Usulan pemusnahan** (`ProposeDestruction`): 1 log per box, satu scan approval (`destruction_approvals/`), `bap_number` "(Menunggu Penerbitan BAP)", `department_approval_by/at` = pengusul, arsip `pending_destruction` (rak tetap). Admin dengan No. BAP (bukan kosong/DRAFT…/placeholder) = langsung `completed` + arsip `destroyed` (L7 "Sahkan Pemusnahan"). Sertifikat opsional → `bap_certificates/`.
- **Setujui** (pending → approved, `approved_by_dept_pic_id`), **Tolak** (pending/approved → rejected, alasan wajib ≤500, arsip kembali `in_warehouse`), **Eksekusi BAP** (approved → completed: No. BAP wajib ≤100, tanggal wajib, sertifikat opsional, catatan ≤1000; arsip `destroyed`, lokasi/slot dikosongkan, `current_box_count` −1), **Upload BAP** (completed saja, file wajib; file lama tetap di disk). Metode & eksekutor ditentukan saat usulan (L7: modal eksekusi tidak punya isian metode/eksekutor; kolom "EKSEKUTOR / PEMOHON" = pengusul).
- **Perpanjangan** (`RequestExtension`): 1 log per box `pending` (prev/new expiry dari kalkulator, `additional_years` = round(bln/12,1)), arsip `pending_extension` + `extension_reason` + `scan_extension_form` (`archive_extensions/`, opsional). **ACC** (`ApproveExtension`): arsip expiry baru, `retention_years`, `periode`, `in_warehouse`; log approved + `approved_by_user_id/at`. **Tolak** (`RejectExtension`): alasan opsional (default L7 "Dokumen / surat permohonan perpanjangan tidak memenuhi syarat."), arsip `in_warehouse`.
- Pesan sukses/error, nama aksi `activity_logs` (modul `PEMUSNAHAN_RETENSI`: DESTRUCTION_PROPOSE / _COMPLETE / _APPROVE / _REJECTED / _EXECUTE / _BAP_UPLOADED / _BAP_PRINT, RETENTION_EXTEND_PROPOSED / _APPROVED / _REJECTED) dan teksnya = L7. `system_events`: `destruction_created` (payload L7) + `archive_updated` (L7 Archive model event) per perubahan arsip.
- Upload lewat `FileStorage::store()` sebelum transaksi; dihapus bila transaksi gagal. Berkas disajikan lewat `files.preview_data` / `files.download` source `destruction` (approval_file|scan_approval_destruction|certificate_file) dan `extension` (scan_extension_form) — sudah ada di `ArchiveFileController::SOURCES`, tidak ada perubahan di sana.
- Riwayat perpanjangan lama (activity log `RETENTION_EXTEND` dari kode L7 lama) tampil bila belum ada `archive_extension_logs` (fallback L7, dibatasi dept untuk PIC).

## Status & transisi
| Objek | Dari | Aksi | Ke | Siapa |
|---|---|---|---|---|
| arsip | `in_warehouse` (kandidat) | Ajukan Pemusnahan | `pending_destruction`; log `pending` | PIC dept sendiri, admin |
| arsip | `in_warehouse` (kandidat) | Sahkan Pemusnahan (No. BAP) | `destroyed`; log `completed` | admin |
| log pemusnahan | `pending` | Setujui | `approved` (arsip tetap `pending_destruction`, label "Antrean Pemusnahan") | gudang, admin |
| log pemusnahan | `pending`, `approved` | Tolak (alasan) | `rejected`; arsip `in_warehouse` | gudang, admin |
| log pemusnahan | `approved` | Eksekusi BAP | `completed`; arsip `destroyed`, keluar rak | gudang, admin |
| log pemusnahan | `completed` | Upload / Re-upload BAP | `completed` (certificate_file baru) | gudang, admin |
| arsip | `in_warehouse` (kandidat) | Ajukan Perpanjangan | `pending_extension`; log `pending` | PIC dept sendiri, admin |
| log perpanjangan | `pending` + arsip `pending_extension` | ACC | `approved`; arsip `in_warehouse` + expiry/retensi baru | gudang, admin |
| log perpanjangan | `pending` + arsip `pending_extension` | Tolak | `rejected`; arsip `in_warehouse` | gudang, admin |
| – | status lain | aksi apa pun | ditolak: "Pengajuan … ini sudah diproses (status: …). Muat ulang halaman." / pesan kandidat | – |
Label: `RetentionList::DESTRUCTION` (Pengajuan Pemusnahan kuning, Antrean Pemusnahan biru, Ditolak merah, Telah Dimusnahkan hijau), `RetentionList::EXTENSION` (Menunggu ACC Gudang, Disetujui Gudang, Ditolak).

## Form (komposisi = L7)
Pemusnahan: Section 1 pencarian box + akordeon (DEPARTEMEN / LOKASI RAK GUDANG / PERIODE BERKAS / STATUS / EXPIRY + rincian dokumen); Section 2 (admin: NOMOR BERITA ACARA (BAP) * auto `BAP/IND/{tahun}/{id 5 digit}`), TANGGAL PELAKSANAAN PEMUSNAHAN * (default hari ini), METODE FISIK PEMUSNAHAN * (3 opsi L7, nilai sama), CATATAN / ALASAN (Opsional); Section 3 Dokumen Persetujuan (Approval) * (WAJIB UNTUK PEMUSNAHAN) + (admin) Sertifikat / Berkas BAP (Opsional); tombol aktif bila ≥1 box + file valid. Perpanjangan: Section 1 (MASA SIMPAN SAAT INI / JATUH TEMPO); TAMBAHAN MASA SIMPAN (BULAN) * default 60 + preset 6/12/24/36/60(/120) + estimasi "MM-YYYY"; ALASAN * ≤1000; Scan Formulir (Opsional); hidden `additional_years`. Cetak "Form Pemusnahan/Perpanjangan" = kertas `archives/form-print` (tipe, catatan, baris: Habis + expiry "M Y" / "N Bln" + akhir baru "MM-YYYY").
Cetak BAP & form perpanjangan: halaman berdiri sendiri, terang, `public/css/pages/retention-print.css` (pt), teks/tanda tangan L7. Perbedaan: tanggal BAP memakai locale `id` ("Selasa, 24 November 2026"; L7 `en`), footer abu slate-500 (L7 slate-400, kontras < 3:1), tautan berkas di BAP membuka `files.preview` tab baru (L7 modal pdf.js).

## File L13
`app/Services/{RetentionCalculator,RetentionList,RetentionFormPrint}.php`, `SystemEventStream::destructions()`; `app/Actions/Retention/{ProposeDestruction,ApproveDestruction,RejectDestruction,CompleteDestruction,UploadDestructionCertificate,RequestExtension,ApproveExtension,RejectExtension}.php`; `app/Http/Controllers/{DestructionController,DestructionApprovalController,ExtensionController}.php`; `app/Http/Requests/{DestructionProposalRequest,ExtensionRequest}.php`; `app/Policies/{DestructionLogPolicy,ArchiveExtensionLogPolicy}.php`; docblock `@property` DestructionLog/ArchiveExtensionLog; `routes/modules/retention.php`; `resources/views/destructions/{index,propose,extend,bap,extend-print}.blade.php` + `partials/{tab-h30,tab-extensions,tab-destructions,search-form,form-boxes,form-notices,reject-modal,complete-modal}.blade.php`; `public/js/pages/{destruction-index,destruction-form}.js`; `public/css/pages/retention-print.css`.

## Tes
`tests/Feature/Retention/{DestructionProposalTest,DestructionProcessTest,ExtensionTest,RetentionListTest,RetentionDueListTest}.php` + `RetentionFixtures.php` (43 tes): nilai kolom/log/event, pesan validasi L7, upload berbahaya/>500 KB tanpa sisa file, kandidat (B2) per status, double submit, dept lain/gudang 403, admin langsung selesai, transisi + status asal salah, PIC tidak bisa proses, BAP cetak per dept (FAT/admin/gudang 200, HCS 403), extend-print per dept, aturan FAT per kode dept (B12), ACC sekali, matematika retensi dari baris L7 (#34, #36, #38, #41, #42, #94), daftar H-30 + tab per role, riwayat perpanjangan lama; P1: paritas filter H-30 SQL vs PHP (akhir bulan, Feb/kabisat, hari ini, batas H-30, baris tanpa expiry), paginasi + `tab` di link halaman, cari server `?q=`.

## Sisa
- Badge menu [Form 3] (L7 `getForm3PendingCount`) dan popup jatuh tempo → modul SHELL/notifikasi (G). Auto-reload realtime (`dms:realtime-sync`) → G.
- Tombol "Proses Pemusnahan" di detail arsip juga tampil untuk PIC Gudang padahal propose 403 untuk gudang (sama L7) — ASK.
