# Inventaris Role PIC Departemen (`pic_dept`) — Laravel 7

Sumber: `C:\laragon\www\indraco-arsip-laravel-7` branch `danu-revisi-4` (dibaca saja). DB nyata `database/database.sqlite` dibuka read-only pada 2026-11-24.
Dasar untuk membangun ulang UI PIC Dept di Laravel 13. Triage semua role: `context/triage.md`. Temuan keamanan/bug: `context/findings.md`.

---

## 0. Ringkasan cepat

- Ada 30 user `pic_dept` (1 per departemen), tapi **baru 4 yang pernah login**: FAT (u3), HRD (u4), DM (u7), HCS (u13). Log dimulai 2026-10-05, karena admin mengosongkan log hari itu.
- Yang benar-benar dipakai: **login, membuat/draft/mengajukan pengajuan box (multi-box), melihat katalog**. Penarikan (2), usulan pemusnahan (2), perpanjangan (1) dan penyimpanan kembali (1) **jarang**. Impor Excel, ganti password, dan hapus draft **tidak ada bukti dipakai** oleh PIC Dept.
- UI PIC = shell "desktop MDI" (`layouts/desktop_pic`). Shell ini membuka 3 "Form" dalam iframe `?embed=1`: [Form 1] Katalog Arsip, [Form 2] Penarikan Berkas, [Form 3] Perpanjangan & Pemusnahan, ditambah jendela "Draft Pengajuan Box Baru". Isi halaman `dashboard.index` tidak pernah dirender untuk PIC Dept, jadi `/` hanya berfungsi sebagai shell.
- Hak akses ditentukan oleh `role` + `department_id`. Middleware `role:` (`CheckRole`) **selalu meloloskan admin**. Hampir semua rute PIC tidak memakai middleware role; pembatasan dilakukan di controller dengan `isPicGudang()` → 403 dan filter `department_id`. Ada beberapa celah IDOR (lihat §6 dan findings).

---

## 1. Rute yang bisa dijangkau `pic_dept`

Keterangan: **UI** = dipakai dari layar PIC. **Tanpa UI** = bisa diakses (tidak 403) tetapi tidak ada tombol/menu di UI PIC. Di rebuild, rute Tanpa UI sebaiknya ditutup untuk PIC Dept.

### 1a. Publik (tanpa login)
| Method | URI | Nama | Controller@method | Dipakai PIC |
|---|---|---|---|---|
| GET | /api/health/ping | api.health.ping | HealthController@ping | UI (widget LAN, klien desktop Tauri) |
| GET/POST | /files/preview-stream?token= | files.preview_stream | FileStreamController@previewStream | UI (modal pratinjau file) |
| GET | /files/stream/{path} | files.stream | FileStreamController@stream | UI (fallback `app_storage_url()`) |
| GET | /storage/files/{path} | storage.files | FileStreamController@stream | Tanpa UI |
| GET | /manifest.json | manifest.json | DashboardController@manifestJson | UI (layout) |
| GET/POST | /quick-notes, /quick-notes/api, /quick-notes/download, /quick-notes/clear | quick_notes.* | QuickNotesController | Tanpa menu (URL langsung, lintas role) |
| GET/POST | /login | login, login.post | AuthController@showLogin/login | UI |

### 1b. Butuh login (`auth`), tanpa middleware role
| Method | URI | Nama | Controller@method | Cek di controller | PIC |
|---|---|---|---|---|---|
| POST | /logout | logout | AuthController@logout | – | UI |
| GET | / , /dashboard | dashboard | DashboardController@index | filter dept | UI (shell MDI) |
| GET | /api/search-archives | archives.search_api | DashboardController@searchApi | filter dept | UI (Ctrl+F di header) |
| GET | /api/realtime/check | api.realtime.check | DashboardController@realtimeCheck (trait HandlesDashboardRealtime) | filter dept | UI (polling notifikasi/badge) |
| GET | /api/archives/expiring-list | api.archives.expiring_list | DashboardController@apiExpiringList | filter dept | UI (popup jatuh tempo) |
| POST | /api/archives/check-similarity | api.archives.check_similarity | ArchiveController@apiCheckSimilarity | dept dipaksa milik user | UI (form box) |
| GET | /api/archives/calculate-retention | api.archives.calculate_retention | ArchiveController@apiCalculateRetention | – | Tanpa UI (tidak dipanggil) |
| GET | /api/departments/{d}/sub-departments | api.departments.sub_departments | ArchiveController@apiGetSubDepartments | – | Tanpa UI |
| GET | /api/departments/{d}/archives | api.departments.archives | DepartmentController@apiGetDepartmentArchives | **tidak ada** | Tanpa UI (IDOR: arsip dept lain) |
| GET | /api/sub-departments/{s}/archives | api.sub_departments.archives | DepartmentController@apiGetSubDepartmentArchives | **tidak ada** | Tanpa UI (IDOR) |
| GET | /api/departments/{d}/manage-data | api.departments.manage_data | DepartmentController@apiGetManageData | **tidak ada** | Tanpa UI (bocor daftar user) |
| GET | /api/departments/{d}/master-archives | api.departments.master_archives | MasterArchiveController@apiGetByDepartment | – | UI (dropdown "Master Berkas" di form box) |
| GET | /api/settings/current | api.settings.current | SettingController@getSettings | – | Tanpa UI |
| POST | /settings/update | master.settings.update | SettingController@updateSettings | logo/nama/ping hanya admin+gudang; **font_size boleh siapa saja (global)** | UI (modal Pengaturan → ukuran font) |
| POST | /settings/reset-logo | master.settings.reset_logo | SettingController@resetLogo | **tidak ada** | Tanpa UI |
| GET | /archives | archives.index | ArchiveController@index | filter dept | UI [Form 1] |
| GET | /archives/create | archives.create | ArchiveController@create | 403 hanya gudang | UI (F2) |
| POST | /archives | archives.store | ArchiveController@store → storeMultiBox/storeSingleBox | dept dipaksa | UI |
| GET | /archives/{a}/edit | archives.edit | ArchiveController@edit | dept | UI (hanya draft) |
| PUT | /archives/{a} | archives.update | ArchiveController@update → updateMultiBox/updateSingleBox | **multi-box tanpa cek dept** | UI |
| DELETE | /archives/{a} | archives.destroy | ArchiveController@destroy | dept + harus draft | UI |
| POST | /archives/batch-delete-drafts | archives.batch_delete_drafts | ArchiveController@batchDeleteDrafts | dept + draft | UI |
| GET | /archives/{a} | archives.show | ArchiveController@show | **tidak ada (IDOR)** | UI |
| GET/POST | /archives/print-labels | archives.print_labels(_post) | ArchiveLabelController@printLabels | filter dept | Tanpa UI (tombol disembunyikan untuk PIC) |
| GET | /archives/{a}/print-sticker | archives.print_sticker | ArchiveLabelController@printSticker | dept | Tanpa UI |
| GET | /archives/import/template | archives.import.template | ArchiveImportController@downloadImportTemplate | 403 gudang | UI (modal impor) |
| POST | /archives/import/preview | archives.import.preview | ArchiveImportController@previewImport | 403 gudang | UI |
| POST | /archives/import/commit | archives.import.commit | ArchiveImportController@commitImport | dept per box; status `in_warehouse` diturunkan ke `pending_verification` | UI |
| POST | /archives/import/load-to-form | archives.import.load_to_form | ArchiveImportController@loadImportToCreateForm | 403 gudang | UI |
| POST | /archives/{a}/verify, /checkin, /checkout, /superadmin-status | archives.* | ArchiveVerification/StatusController | 403 untuk PIC | – |
| GET | /borrowings | borrowings.index | BorrowingController@index | milik sendiri atau dept | UI [Form 2] |
| GET | /borrowings/create | borrowings.create | BorrowingController@create | 403 gudang; filter dept | UI |
| POST | /borrowings | borrowings.store | BorrowingController@store | dept | UI |
| POST | /borrowings/{b}/dept-approve | borrowings.dept_approve | BorrowingController@deptApprove | PIC dept pemilik / admin | Tanpa UI (tombol hanya untuk admin) |
| POST | /borrowings/{b}/reject | borrowings.reject | BorrowingController@reject | PIC dept pemilik boleh | Tanpa UI (tombol hanya gudang/admin) |
| PUT | /borrowings/{b} | borrowings.update | BorrowingController@update | peminjam sendiri | Tanpa UI (tidak dipanggil) |
| POST | /borrowings/{b}/approve, /dispatch, /return | – | – | 403 untuk PIC | – |
| GET | /destructions | destructions.index | DestructionController@index | filter dept | UI [Form 3] |
| GET | /destructions/propose[/{a}] | destructions.propose(.archive) | DestructionController@proposeForm | 403 gudang; filter dept | UI |
| POST | /destructions/propose[/{a}] | destructions.store(.archive) | DestructionController@propose | dept | UI |
| GET | /destructions/bap/{log} | destructions.bap | DestructionController@showBap | **tidak ada** | UI (link di detail arsip) |
| GET | /destructions/extend[/{a}] | destructions.extend_form(.archive) | DestructionController@extendForm (trait HandlesArchiveExtensions) | 403 gudang | UI |
| POST | /destructions/extend[/{a}] | destructions.extend_store(.archive) | …@extendStore | dept | UI |
| GET | /destructions/extend-print/{a} | destructions.extend_print | …@extendPrint | **tidak ada** | UI (tab perpanjangan) |
| POST | approve/reject/complete/upload-bap/extensions approve-reject | – | – | 403 untuk PIC | – |
| GET | /reports, /reports/data/{t}, /reports/print/{t}, /reports/export-csv/{t} | reports.* | ReportController | **tidak ada cek role** | Tanpa UI (bocor master user/gudang) |
| GET | /api/warehouse/layout-data | api.warehouse.layout_data | WarehouseLayoutController@apiLayoutData | non-admin difilter | Tanpa UI |
| POST | /profile/change-password | profile.change_password | UserController@changePassword | – | UI (modal Ganti Password) |
| POST | /impersonate/leave | impersonate.leave | UserController@leaveImpersonate | butuh session `impersonator_id` | UI (banner saat admin impersonate) |

Semua rute `role:admin` / `role:admin,pic_gudang` (master, layout gudang, logs, numbering, users, backup, dan lain-lain) → **403** untuk PIC Dept.

---

## 2. Fitur PIC Dept (alur, aturan, status, tabel, view)

Status bukti: **Dipakai**, **Jarang** (1–5 kejadian), **Tidak ada bukti** (0 di log/DB). Angka diambil dari `activity_logs` dengan `user_role='pic_dept'`. Tidak ada log PIC Dept yang berasal dari test runner (UA `Symfony`).

### F1. Login / Logout — **Dipakai** (LOGIN 54, LOGOUT 40)
- Form `auth/login.blade.php` (509 baris + `auth/partials/login_modals` 168 + `login_scripts` 240).
  | Field | Tipe | Wajib | Default | Validasi |
  |---|---|---|---|---|
  | email (label "NAMA PENGGUNA / EMAIL [Alt+E]") | email | ya | **`admin@indraco.com` (prefill!)** | required, email |
  | password ("KATA SANDI / PASSWORD [Alt+P]") | password + toggle lihat | ya | **`password` (prefill!)** | required |
  | remember | – | – | – | form tidak punya checkbox; dibaca `$request->remember` |
- Gagal login: pesan "Kredensial email atau password yang dimasukkan salah." dan log `LOGIN_FAILED`. Berhasil: `session()->regenerate()`, lalu `redirect()->intended('/')`, lalu log `LOGIN`.
- Logout: invalidate session dan regenerate token, lalu log `LOGOUT`.
- Tabel: `users` (baca), `activity_logs` (tulis).

### F2. Ganti password sendiri — **Tidak ada bukti** (0 `PASSWORD_CHANGE`; 32/32 user masih memakai password default)
- Modal `layouts/partials/change_password_modal.blade.php` (203). Tombolnya ada di header PIC.
  | Field | Label | Wajib | Aturan |
  |---|---|---|---|
  | current_password | PASSWORD SAAT INI * | ya | harus cocok (`Hash::check`), pesan "Password saat ini yang Anda masukkan salah." |
  | password | PASSWORD BARU * (Min. 6 Karakter) | ya | min:6, confirmed |
  | password_confirmation | ULANGI PASSWORD BARU * | ya | – |
- Pesan sukses: "Password Anda berhasil diperbarui." (JSON atau redirect). Tulis `users.password` (bcrypt), log `PASSWORD_CHANGE`.

### F3. Shell desktop PIC (pengganti dashboard) — **Dipakai**
- `layouts/desktop_pic.blade.php` (526) + `pic_desktop_header` (404) + `pic_desktop_scripts` (501) + `pic/pic_realtime_scripts` (311) + `pic/pic_search_shortcuts` (180) + `pic/pic_settings_scripts` (301) + `pic_system_settings_modal` (395) + modal bersama (confirm 227, file_preview 366+486, label_print 144, change_password 203, expiry_warning 204) + `head_assets` (495). Total sekitar **4.900 baris hanya untuk shell**.
- Tab MDI: `[Form 1] Katalog Arsip {KODE}` (badge = jumlah `pending_verification` dept), `[Form 2] Penarikan Berkas` (badge), `[Form 3] Pengajuan Perpanjangan & Pemusnahan` (badge = `Archive::getForm3PendingCount` = arsip H-30 atau lewat masa simpan). Ada juga jendela tersembunyi `archives_create`.
- Tombol/shortcut: **Baru (F2)** membuka form box baru. **Refresh (F5 / Ctrl+R)** memuat ulang jendela aktif. **F8** dipakai di `pic_search_shortcuts` (perlu dicek di UI). **Ctrl+F** membuka pencarian. Tersedia juga mode gelap (Alt+T), layar penuh (F11), suara notifikasi on/off, dropdown notifikasi, Pengaturan (ukuran font), Ganti Password, Logout. Tombol "Cetak (F9)" disembunyikan untuk PIC.
- Pencarian header: `GET /api/search-archives?q=` mencari arsip dept sendiri (nama dokumen, no. box, rak), lalu klik hasil membuka detail di jendela MDI.
- Notifikasi realtime: polling `GET /api/realtime/check?since_seq=&last_id=&initial=` membaca `system_events` (event `archive_*`, `borrowing_*`, `destruction_created`, `setting_updated`) yang difilter per dept. Hasilnya dipakai untuk toast, badge, dan bunyi.
- Popup jatuh tempo (`expiry_warning_modal`): `GET /api/archives/expiring-list`, dengan tombol "Lihat Berkas Jatuh Tempo".
- Iframe: semua halaman PIC dirender dengan `?embed=1` memakai `pic_desktop_embed` (155). Halaman yang dibuka langsung di iframe tanpa `embed=` akan otomatis reload dengan `embed=1`.

### F4. Katalog arsip [Form 1] — **Dipakai**
- `archives/index.blade.php` (789) + `catalog_action_toolbar` (74) + `catalog_batch_ribbon` (73) + `catalog_filter_panel` (142) + `catalog_legend_bar` (26) + `catalog_modal_handlers` (436) + `catalog_scripts` (537) + 7 modal (1.950 baris; checkout/dispatch/quick_slot/verify/superadmin_status hanya untuk gudang/admin tetapi tetap di-include untuk PIC). Total sekitar **4.000 baris**.
- Query: `archives` dengan `department_id = user.department_id`, paginasi `per_page` (default 10). Urutan default: non-draft di atas, lalu `updated_at desc`. Sort yang diizinkan: box_number, title, created_at, updated_at, retention_expiry_date, status.
- Filter: `search` (title, custom_doc_name, box_number, periode_doc, period_text, content_description, rack_code), `sub_department_id`, `document_type` (gabungan master_archives + archives), `status` (termasuk pseudo `borrow_requested`, `borrowed`/`out` = borrowed+taken), `expiry_filter` (`expiring_soon` ≤90 hari, `expired`).
- Aksi per baris (PIC, dept sendiri):
  - **Edit** / **Revisi & Ajukan** (merah jika ada `rejection_note`), hanya untuk status `draft`.
  - **Hapus** draft dengan konfirmasi.
  - **Tarik** / **Tarik Ulang** (jika penarikan terakhir ditolak) bila status `in_warehouse` dan tidak ada penarikan aktif. Badge "Sedang Ditarik" jika penarikan aktif.
  - Klik baris membuka detail.
- Toolbar: Refresh (F5), **Impor Multi-Box (Excel)**, **Buat Draft (F2)**. Ribbon multi-pilih: **Tarik Berkas (n Box)**, **Hapus Draft (n)**. Tombol Cetak Label disembunyikan untuk PIC.

### F5. Pengajuan box baru (multi-box, draft/ajukan) — **Dipakai** (CREATE 35, SUBMIT 40, DRAFT_SAVE 11, DRAFT_UPDATE 28)
- View `archives/create.blade.php` (745) + `create_unit_section` (140) + `create_box_operations` (337) + `create_scripts` (530) + `create_modal_scripts` (413) + `duplicate_box_modal` (73) + `<x-file-uploader>` (224, kompres gambar di klien) + `<x-form-pengajuan-modal>` (578, pratinjau cetak "Form Pengajuan").
- **UI selalu mengirim `boxes[]`**, sehingga yang berjalan adalah `HandlesMultiBoxCreation::storeMultiBox`. Jalur single-box (`storeSingleBox`) hanya dipakai test atau klien lama.
- Alur: isi header unit → tambah/duplikat box → isi butir dokumen per box → (opsional) pratinjau "Form Pengajuan", cetak, tanda tangan, scan → unggah Scan Formulir Input → **Simpan Sementara (Draft)** atau **Simpan & Ajukan n Box Arsip**.
- Komposisi form:
  | Bagian | Field (name) | Label | Wajib (ajukan / draft) | Default / pilihan |
  |---|---|---|---|---|
  | Unit | company_name (hidden, pemilih) | PERUSAHAAN ENTITAS * | ya / tidak | `PT INDRACO GLOBAL INDONESIA` |
  | Unit | department_id (hidden) + teks readonly | DEPARTEMEN PEMILIK ARSIP * | dipaksa = dept user | – |
  | Unit | sub_department_id (select) | SUB-DEPARTEMEN (Opsional) | tidak | "-- Pilih Sub-Departemen --", "Inti Departemen" (=`inti` → null), sub-dept aktif |
  | Unit | tgl_penyerahan (date, max=hari ini) | TGL. SERAH TERIMA FISIK KE GUDANG * | ya, `before_or_equal:today` / tidak | – |
  | Box[i] | boxes[i][title] | NAMA / JUDUL BOX * (cek kemiripan otomatis) | ya, max 255, **unik per dept (case-insensitive, status ≠ destroyed)** / tidak | placeholder "Contoh: Voucher Kas Kecil & Pengeluaran Q1" |
  | Box[i] | boxes[i][sub_department_id] | SUB-DEPARTEMEN (Opsional) | tidak | "-- Ikuti Sub-Dept Induk / Semua --", "Inti Departemen", daftar |
  | Box[i] | boxes[i][document_type] | KATEGORI DOKUMEN (Terkunci) | tidak, max 100 | otomatis dari Master Berkas |
  | Box[i] | boxes[i][physical_condition] | KONDISI FISIK WADAH BOX * | ya, max 100 | `Baik / Box Karton Standar TB 30g` |
  | Box[i] | periode_bulan (number, min 1) + checkbox follow_previous → hidden boxes[i][periode] | PERIODE DOKUMEN (BULAN) * | – | 1; maks **60 bulan, atau 120 bulan untuk dept FAT/FIN/FATCLM** (atau nama user mengandung FAT/FIN) |
  | Item[j] | boxes[i][items][j][document_name] (select dari Master Berkas dept) | Nama dokumen | ya, max 255 | "-- Pilih dari Master Berkas Arsip --" |
  | Item[j] | …[period_start] / …[period_end] (type=month) | Periode mulai/selesai | ya, max 50 | bulan ini |
  | Item[j] | …[notes] (textarea) | "No. berkas fisik / catatan" | **ya** (max 255) / tidak | – |
  | File | scan_input_form | Scan Formulir Input | **wajib saat ajukan** (pdf/jpg/jpeg/png, max 500 KB) | – |
  | File | file | Berkas digital (opsional) | tidak (pdf/jpg/png, 500 KB) | – |
  | Hidden | embed, from, is_re_store, boxes[i][origin_borrowing_log_id], boxes[i][origin_archive_id] | – | – | – |
- Aturan bisnis:
  - Masa simpan: `retention_years` box → sub-dept → dept → 5. `retention_expiry_date` = akhir bulan (`period_end_date` + tahun). Jika periode berbentuk "N bulan": `retention_years = floor(N/12)`, expiry = akhir periode.
  - Cek kemiripan judul (`ArchiveSimilarityService`, `similar_text` ≥70% = "similar", 100% = "identical") ditampilkan secara live. Kode menolak judul yang persis sama.
  - **Draft multi-box disimpan sebagai 1 baris `archives` status `draft`** dengan `content_description` berisi JSON `{is_multi_box_draft:true, boxes:[...]}`. Saat diajukan, baris pertama dipakai ulang dan box lain dibuat sebagai baris baru `pending_verification`, lalu `archive_items` diisi.
  - **Nomor box belum dibuat saat pengajuan**. Nomor box dibuat oleh PIC Gudang saat verify (lihat §5).
  - Ditulis dengan `db_immediate_transaction`. Log `ARCHIVE_DRAFT_SAVE` / `ARCHIVE_CREATE`. `SystemEventStream` mengeluarkan event `archive_draft_created` / `archive_submitted`.
- Tabel: tulis `archives`, `archive_items`, `activity_logs`, `system_events`. Baca `departments`, `sub_departments`, `master_archives`.
- File: `storage/app/public/archive_scans/` (scan_input_form), `archive_digital/` (file).

### F6. Edit draft / revisi setelah ditolak — **Dipakai** (DRAFT_UPDATE 28)
- `archives/edit.blade.php` (668) + `edit_unit_section` (143) + `edit_scripts` (680) + `edit_modal_scripts` (381) + `edit_duplicate_box_modal` (51). Field sama dengan F5, ditambah hidden `original_updated_at` (optimistic lock, pesan "Konflik Perubahan: …") dan `remove_scan_input_form`. Scan wajib jika belum ada file dan status ≠ draft.
- Tombol: **Simpan Sementara (Draft)** dan **Simpan & Ajukan Permanen ke Gudang**.
- Penolakan oleh gudang (`verify action=reject`) mengembalikan status ke `draft` dengan `rejection_note`, lalu PIC melihat tombol "Revisi & Ajukan".
- UI hanya menampilkan Edit untuk status `draft`. Server tidak membatasi status, dan multi-box update tidak mengecek dept (finding).

### F7. Hapus draft (satuan & batch) — **Tidak ada bukti** (0 `ARCHIVE_DRAFT_DELETE`/`_BATCH_DELETE`; 1 event `archive_deleted`)
- Hanya status `draft` dan dept sendiri. Item dan file scan ikut dihapus.

### F8. Impor multi-box dari Excel — **Tidak ada bukti oleh PIC** (4× `ARCHIVE_BATCH_IMPORT`, semuanya admin/anonim saat uji)
- Modal `archives/partials/modals/import_modal.blade.php` (422). Isinya: unduh template (xlsx, 9 kolom untuk PIC: JUDUL_BOX, KODE_DEPARTEMEN, KODE_SUB_DEPARTEMEN, NAMA_DOKUMEN, PERIODE_DOK_MULAI, PERIODE_DOK_SELESAI, KETERANGAN_DOKUMEN, MASA_SIMPAN_BULAN, KONDISI_FISIK), lalu pilih file `.xlsx/.xls/.csv` (max 5 MB), lalu preview, lalu pilih target (radio Draft / Ajukan Verifikasi; opsi `in_warehouse` diturunkan ke `pending_verification` untuk PIC), lalu **commit** atau **Muat ke Form** (via session → `archives.create?from=excel_import`).
- Service: `MultiBoxExcelService` (741), `MultiBoxTemplateService` (563), `SpreadsheetReader` (133, pembaca xlsx tanpa library). Setiap box harus milik dept user.

### F9. Detail arsip — **Dipakai** (dibuka dari katalog/pencarian/notifikasi; tidak dilog)
- `archives/show.blade.php` (718). Menampilkan data box, butir dokumen, lokasi rak, riwayat masuk gudang, riwayat penarikan, info pemusnahan + link BAP, pratinjau file (scan input, approval, extension).
- Aksi PIC: Edit (jika draft dan dept sendiri) dan Ajukan Pemusnahan (`destructions.propose`).

### F10. Penarikan berkas [Form 2] — **Jarang** (BORROW_CREATE 2; borrowing_logs 3: requested 1, dispatched 1, rejected 1)
- Index `borrowings/index.blade.php` (635) + modals detail (258) / dispatch (174, gudang) / reject (94, gudang) + `borrowing_scripts` (68) + `dept_approve_modal` (41, admin). Satu baris per box (log terbaru). Filter status: requested, dept_approved, approved, dispatched, returned, rejected.
- Create `borrowings/create.blade.php` (797): cari box `in_warehouse` milik dept, lalu tambah ke daftar (multi-box), lalu isi alasan, lalu unggah form persetujuan.
  | Field | Label | Wajib | Aturan |
  |---|---|---|---|
  | archive_ids[] / archive_id (hidden) | PENCARIAN BERKAS GUDANG UNTUK DITAMBAHKAN * | min. 1 | harus `in_warehouse`, tidak sedang dalam penarikan aktif, dept sendiri |
  | purpose (textarea) | ALASAN LENGKAP PENARIKAN * | ya | max 500 |
  | approval_file | (uploader) form persetujuan | ya | pdf/jpg/png, max 500 KB → `borrowing_approvals/` |
  | is_permanent (hidden) | – | – | **selalu `1`**: semua penarikan bersifat permanen (`expected_return_date` null). Saat dispatch, arsip menjadi `taken` |
- Status `borrowing_logs.status`: `requested` → (`dept_approved` oleh admin) → (`approved`, tanpa UI) → `dispatched` (gudang) → `returned` (rute tanpa UI). Status lain: `rejected`.
- Aksi PIC di index: **Tarik Ulang** (jika ditolak dan box masih di gudang), **Detail**, **Ajukan Penyimpanan Kembali (n Box)** (multi-pilih log `dispatched` yang memenuhi `restore_eligibility`), yang membuka `archives.create?re_store=1&borrowing_ids[]=…` dengan box sudah terisi.
- Penyimpanan kembali: **Jarang** (1 arsip dengan `origin_borrowing_log_id`). Jalur multi-box membuat arsip baru `pending_verification` yang mencatat `origin_borrowing_log_id` dan `re_store_snapshot`, sedangkan arsip lama tetap `taken`.

### F11. Perpanjangan masa simpan — **Jarang / alur baru tidak ada bukti** (1× `RETENTION_EXTEND` dari kode lama; `archive_extension_logs` = 0)
- `destructions/extend.blade.php` (470) + `extend_scripts` (336) + `tab_extensions` (130). Pilih box (multi), isi tambahan bulan dan alasan, unggah form (opsional).
  | Field | Label | Wajib | Aturan |
  |---|---|---|---|
  | archive_ids[] | PENCARIAN BERKAS … * | min. 1 | dept sendiri; status bukan pending_destruction/destroyed/pending_extension |
  | additional_months (number, min 1, max dinamis) | TAMBAHAN MASA SIMPAN (BULAN) * | ya | max 60, atau 120 untuk FAT/FIN/FATCLM; `additional_years` hidden = round(bln/12) |
  | extension_reason | ALASAN PERPANJANGAN MASA SIMPAN DOKUMEN * | ya | max 1000 |
  | scan_extension_form | form perpanjangan | tidak | pdf/jpg/png 500 KB → `archive_extensions/` |
- Hasil: `archive_extension_logs` (pending), arsip menjadi `pending_extension`. Gudang ACC: expiry baru dan status `in_warehouse`, atau tolak: status `in_warehouse`. Cetak form: `destructions.extend_print` (`print_extension.blade.php` 135).

### F12. Usulan pemusnahan / BAP — **Jarang** (DESTRUCTION_PROPOSE 2; destruction_logs 2, keduanya pending)
- Index `destructions/index.blade.php` (706) + `destruction_modals` (287) + `destruction_scripts` (34). Tab: daftar H-30/lewat masa simpan, pengajuan pemusnahan, perpanjangan.
- Form `destructions/propose.blade.php` (768):
  | Field | Label | Wajib | Pilihan / aturan |
  |---|---|---|---|
  | archive_ids[] | PENCARIAN BERKAS … * | min. 1 | dept sendiri; kandidat = bukan destroyed/draft/pending_*, tanpa log pemusnahan aktif/perpanjangan pending |
  | bap_number | (PIC: hidden, nilai `autoBap`; admin: input "NOMOR BERITA ACARA (BAP) *") | – | untuk PIC diabaikan dan disimpan `(Menunggu Penerbitan BAP)` |
  | destruction_date (date) | TANGGAL PELAKSANAAN PEMUSNAHAN * | ya | default hari ini |
  | method (select) | METODE FISIK PEMUSNAHAN * | ya | "Pencacahan Mesin Industrial Paper Shredder", "Pembakaran Standard Suhu Tinggi (Incinerator)", "Peleburan Bahan Kimia & Daur Ulang Industri" |
  | notes | CATATAN / ALASAN PEMUSNAHAN BERKAS (Opsional) | tidak | – |
  | approval_file | surat persetujuan | ya | pdf/jpg/png 500 KB → `destruction_approvals/` |
  | certificate_file | sertifikat BAP | tidak | → `bap_certificates/` |
- Hasil (PIC): arsip menjadi `pending_destruction`, `destruction_logs.approval_status=pending`. Gudang: approve → reject (arsip kembali `in_warehouse`) → complete (arsip `destroyed`, slot dikosongkan) → upload BAP. PIC melihat BAP di `destructions/bap.blade.php` (185).

### F13. Pratinjau / stream file — **Dipakai** (18 scan input, 1 scan perpanjangan, 11 approval penarikan, 6 approval pemusnahan, 1 BAP di disk)
- `file_preview_modal` + `file_preview_scripts` (pdf.js) memanggil `/files/preview-stream?token=base64(path)`. Helper `app_storage_url()` dan `app_preview_stream_url()`. **Tanpa login** (finding).

### F14. Pengaturan tampilan — **Jarang** (2× `SYSTEM_SETTINGS_UPDATE`, oleh admin)
- PIC hanya bisa mengubah ukuran font (preset 10/14/19/21/23px + slider 10–26px). Nilai disimpan ke `app_settings.app_font_size` yang **berlaku global untuk semua user** dan juga ke localStorage. Tema dan suara disimpan di localStorage.

### F15. Quick Notes (LAN scratchpad) — **Jarang** (`storage/app/quick_notes.json` terakhir diubah 2026-10-19; dipakai untuk mencatat daftar bug)
- Publik tanpa login, bukan fitur khusus PIC. Lihat triage (ASK).

### Tidak tersedia untuk PIC Dept (meski rutenya terbuka)
- Cetak label/stiker: `ARCHIVE_PRINT` 16 (gudang 12, admin 4), PIC 0, dan tombolnya disembunyikan.
- Laporan, layout gudang, master: tidak ada menu.

---

## 3. Bukti pemakaian (DB nyata, read-only)

| Data | Nilai |
|---|---|
| Rentang activity_logs | 2026-10-05 11:01 s/d 2026-11-17 15:05 (436 baris; log dikosongkan admin 2026-10-05) |
| Users | admin 1, pic_gudang 1, pic_dept 30 (4 pernah login) |
| archives (57) | pending_verification 29, in_warehouse 16, draft 8, pending_destruction 2, approved_booked 1, taken 1. `borrowed`/`pending_extension`/`destroyed` = 0 |
| archive_items | 923 |
| borrowing_logs | 3 (requested 1, dispatched 1, rejected 1) |
| destruction_logs | 2 (pending) |
| archive_extension_logs | 0 |
| system_events | 221 (archive_updated 96, draft_created 41, submitted 37, created 35, …) |
| Data uji di DB produksi | 3 arsip `BOX-TEST-*`, 29 LOGIN dan 20 DRAFT_UPDATE ("Concurrency Admin") dari UA `Symfony` → test suite pernah dijalankan ke DB nyata |

activity_logs per aksi (PIC Dept): ARCHIVE_SUBMIT 40, ARCHIVE_CREATE 35, ARCHIVE_DRAFT_UPDATE 28, ARCHIVE_DRAFT_SAVE 11, BORROW_CREATE 2, DESTRUCTION_PROPOSE 2, RETENTION_EXTEND 1, LOGIN 54, LOGOUT 40.
Role lain: gudang ARCHIVE_PRINT 12, ARCHIVE_VERIFY 9, SLOT_ASSIGN 8, ARCHIVE_CHECKIN 1, BORROW_DISPATCH 1, BORROW_REJECT 1; admin LOGIN 52, SYSTEM_DATABASE_BACKUP 22, LAYOUT_OBJECT_UPDATE 6, ARCHIVE_PRINT 4.

| Fitur | Status |
|---|---|
| Login/logout, shell MDI, katalog, pengajuan & draft multi-box, edit/revisi draft, detail, pratinjau file, notifikasi | **Dipakai** |
| Penarikan, penyimpanan kembali, usulan pemusnahan, perpanjangan (kode lama), pengaturan font, quick notes | **Jarang** |
| Ganti password, hapus draft, impor Excel oleh PIC, perpanjangan alur baru (extension_logs), dept-approve, borrowings.update/return/approve | **Tidak ada bukti dipakai** |

---

## 4. Catatan UI untuk redesign

- Halaman sangat besar. Katalog sekitar 4.000 baris (7 modal gudang ikut di-include untuk PIC), create sekitar 3.000, edit sekitar 2.700, shell PIC sekitar 4.900, borrowings index sekitar 1.270, destructions index sekitar 1.160. Logika Alpine ditulis inline di Blade (`*_scripts.blade.php`).
- Komponen ganda:
  - `create_*` ↔ `edit_*` (unit section hanya beda 39 baris; scripts 530 vs 680).
  - `layouts/partials/pic/*` ↔ `layouts/partials/desktop/*` (realtime/search/settings disalin lalu menyimpang).
  - `pic_system_settings_modal` ↔ `system_settings_modal`.
  - `pic_desktop_embed` ↔ `app_embed_shell`.
  - `archives/partials/modals/{checkout,quick_slot,superadmin_status,verify}` ↔ `dashboard/partials/modals/*` (beda 3–26 baris).
  - Pencarian box (borrowings/create, propose, extend) diimplementasikan 3 kali.
- Header "gaya Delphi" (`pic_desktop_header.blade.php`, 404 baris): bar hitam font mono, tombol F-key, dropdown pencarian lebar 740px, notifikasi, MDI tab dan jendela iframe yang bisa di-drag. Berat untuk PC low-spec (setiap form = 1 iframe dengan Tailwind JIT runtime `js/vendor/tailwindcss.js` dimuat ulang).
- Tailwind dipakai lewat **runtime JIT di browser** (`tailwindcss.js`) di setiap halaman/iframe. `chart.min.js` tidak dirujuk sama sekali.
- Dead code: `components/{navbar,footer,admin-sidebar,bottom-nav,card-product,modal-search,admin-bottom-nav,sosmed,toggle-language}`, `layouts/admin`, `layouts/partials/admin-sidebar-content`, `welcome`, `vendor/pagination/{custom,default}` tidak dirujuk. Isi `dashboard/index` tidak pernah tampil untuk PIC.

---

## 5. Fakta data/kompatibilitas yang wajib dipertahankan

| Hal | Fakta |
|---|---|
| Status `archives.status` | `draft`, `pending_verification`, `approved_booked`, `in_warehouse`, `borrowed`, `taken`, `pending_destruction`, `pending_extension`, `destroyed`. Label UI: Draft Usulan, Menunggu Verifikasi, Booking Disetujui, Tersimpan di Gudang, Sedang Dipinjam, Telah Diambil (Permanen), Pengajuan/Antrean Pemusnahan, Pengajuan Perpanjangan, Telah Dimusnahkan |
| Transisi oleh PIC Dept | (baru) → `draft` / `pending_verification`; `draft` → `pending_verification`; `in_warehouse`/lainnya → `pending_destruction` (propose) / `pending_extension` (extend); re-store: arsip baru `pending_verification` |
| Transisi oleh Gudang/Admin | verify approve: `pending_verification` → `approved_booked` + nomor box; reject: kembali ke `draft` + `rejection_note`. Checkin/assign slot: → `in_warehouse`. Dispatch: → `taken` (permanen) / `borrowed`. Return: → `in_warehouse`. Destruction approve/complete: → `destroyed`; reject → `in_warehouse`. Extension ACC/tolak → `in_warehouse`. Superadmin-status bebas |
| `borrowing_logs.status` | requested, dept_approved, approved, dispatched, returned, rejected; `approval_status` pending/approved |
| `destruction_logs.approval_status` | pending, approved, completed, rejected; `bap_number` `(Menunggu Penerbitan BAP)` sampai diterbitkan |
| `archive_extension_logs.approval_status` | pending, approved, rejected |
| `warehouse_rack_slots.status` | empty, filled, inactive (komentar: expired) |
| Nomor box | Dibuat **saat verify oleh gudang** (`NumberingService::generateBoxCode`). Format aktif `numbering_formats` id 1: pola `{COMPANY}/{DEPT}/{YEAR}/{COUNTER}`, padding 4, counter sekarang 12. Token: `{COMPANY}`=`IND`, `{DEPT}`=kode dept, `{YEAR}`/`{ROMAN_MONTH}` dari `period_end_date`, `{COUNTER}`/`{COUNTER_BOX}`. Counter global (bukan per dept) dinaikkan atomik `UPDATE … SET current_counter=current_counter+1 … RETURNING`. Ada guard tabrakan (loop selama kode dipakai arsip non taken/destroyed/borrowed). Data lama memakai beberapa pola (`IND/DM/2026/XI/0017`, `IND/FAT/2024/0013`, `BOX-TEST-…`); nomor bisa sama untuk arsip re-store (40 & 41). Tanpa format aktif: fallback `count()+1` |
| Upload | Disk `public` = `storage/app/public` (dan `public/storage` berisi **salinan nyata**, bukan symlink). Kolom menyimpan path relatif: `archive_scans/…` (scan_input_form), `archive_digital/…` (file_path), `borrowing_approvals/…`, `destruction_approvals/…`, `bap_certificates/…`, `archive_extensions/…`. Folder kosong: archive_approvals, archive_files, archive_scan_inputs. Nama file hash Laravel (`store()`). Semua max 500 KB pdf/jpg/jpeg/png. Logo: `public/uploads/logo/logo_{time}.{ext}` |
| Resolusi file | `FileStreamController::resolveCandidates`: public/storage/X → public/X → storage/app/public/X → storage/app/X. URL pratinjau: `/files/preview-stream?token=base64(path)` (anti-IDM) |
| Password | bcrypt `$2y$10$` (rounds 10). Semua 32 user saat ini memakai `password` |
| Auth/session | Guard session default, driver `file`, lifetime 120. Impersonate admin → session `impersonator_id`, `POST /impersonate/leave`. Hak akses: `users.role` ∈ {admin, pic_gudang, pic_dept} + `users.department_id` |
| Helper | `db_immediate_transaction()` (BEGIN IMMEDIATE untuk SQLite; jangan nested `DB::transaction`), `app_storage_url()`, `app_preview_stream_url()` (`app/helpers.php`, dimuat di AppServiceProvider). PRAGMA WAL, busy_timeout 5000, synchronous NORMAL. `ActivityLogger::log(action, desc, module, props, ref, user)` → `activity_logs`. `SystemEventStream::emit` → `system_events` (sumber notifikasi realtime) |
| Aturan khusus FAT | dept kode FAT/FIN/FATCLM (atau nama user mengandung FAT/FIN): periode maks 120 bulan, perpanjangan maks 10 tahun (lainnya 60 bulan / 5 tahun) |
| Kemiripan judul | `similar_text` ≥70% peringatan; judul sama persis ditolak (per dept, status ≠ destroyed) |

---

## 6. Masalah yang harus diperbaiki di rebuild (detail di findings.md)

1. `archives.show`, `destructions.bap`, `destructions.extend_print`, `api/departments/*`, `reports/*` tidak membatasi dept/role, sehingga PIC bisa membaca data dept lain dan daftar user.
2. `PUT /archives/{id}` jalur multi-box (satu-satunya jalur yang dipakai UI) tidak mengecek dept dan status. PIC bisa menimpa arsip dept lain/non-draft dan memindahkannya ke dept sendiri.
3. File stream publik tanpa login, termasuk `storage/app/*.sqlite` (backup DB) dan `quick_notes.json`. Fallback mengembalikan PDF arsip nyata lain bila file tidak ada.
4. Login prefilled `admin@indraco.com` / `password`, dan semua akun memakai `password`.
5. `/settings/update` font size global bisa diubah PIC; `/settings/reset-logo` tanpa cek role.
6. Propose pemusnahan di server tidak membatasi status (draft/pending_verification lolos jika dikirim manual).

---

## 7. Usulan cakupan Fase 4 (UI PIC Dept)

Urutan bangun yang disarankan:
1. **Login + logout + ganti password** (hapus prefill, paksa ganti password default → perlu keputusan pemilik).
2. **Shell PIC sederhana** (header + navigasi 3 menu + badge + pencarian + popup jatuh tempo + notifikasi polling `system_events`). Ganti MDI iframe dengan halaman biasa (ASK: pemilik setuju MDI diganti?). Shortcut F2/F5/Ctrl+F dipertahankan.
3. **Katalog arsip** (filter, sort, paginasi, aksi baris, multi-pilih).
4. **Pengajuan box multi-box**: create + draft + ajukan + pratinjau/cetak Form Pengajuan + uploader 500 KB + cek kemiripan.
5. **Edit/revisi draft** (komponen yang sama dengan create) + hapus draft.
6. **Detail arsip** + pratinjau file (dengan auth & cek dept).
7. **Penarikan** (create + index + tarik ulang) dan **penyimpanan kembali**.
8. **Usulan pemusnahan** + lihat BAP.
9. **Perpanjangan masa simpan** + cetak form.
10. **Impor multi-box Excel** (template, preview, commit, muat ke form).

Kandidat drop/tunda (dengan bukti):
- Impor Excel untuk PIC: 0 pemakaian PIC, dan 4 impor hanya uji admin → **tunda ke akhir / ASK**, jangan dibuang tanpa keputusan.
- `borrowings.update`, `borrowings.approve`, `borrowings.return`, `api.archives.calculate_retention`, `api.settings.current`, `api.departments.sub_departments`: tidak dipanggil UI mana pun → **DROP** dari cakupan PIC.
- Akses PIC ke `print-labels`/`print-sticker`, `reports`, `dept-approve`, `reject` penarikan: tidak ada UI → **tutup** untuk PIC.
- Pengaturan font global oleh PIC → jadikan preferensi per-user/localStorage (ASK).
- Jalur single-box (`storeSingleBox`/`updateSingleBox`): UI tidak memakainya → **DROP** (cukup satu jalur multi-box).
