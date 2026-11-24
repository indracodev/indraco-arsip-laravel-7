# Peta arsitektur (diperbarui setiap modul selesai)

Agent yang menambah/memindah file wajib memperbarui tabel ini.

## Lapisan
| Lapisan | Folder | Isi |
|---|---|---|
| Route | `routes/web.php` (+ `routes/web/<modul>.php` bila besar) | tanpa closure |
| Controller | `app/Http/Controllers/` | tipis, per resource/sub-fitur |
| Validasi | `app/Http/Requests/` | satu Form Request per form |
| Use case | `app/Actions/<Modul>/` | satu Action = satu aksi bisnis |
| Logika bersama | `app/Services/` | penomoran, retensi, file storage, spreadsheet |
| Otorisasi | `app/Policies/` | role + kepemilikan departemen |
| Data | `app/Models/` | `$table` eksplisit, nama tabel L7 |
| Tampilan | `resources/views/<modul>/`, `components/` | Blade + komponen (`context/modules/ui-design-system.md`) |
| CSS | `public/css/` (`tokens`, `base`, `layout`, `components/*.css` → `components.css`; `pages/*.css` hanya untuk halaman khusus seperti cetak) | CSS murni, tanpa build tool |
| JS | `public/js/` (+ `public/js/pages/<modul>.js` bila perlu) | vanilla + `Alpine.data` (Alpine lokal di `public/js/vendor/`) |
| Tampilan pendukung | `app/Support/` | menu, setting per request, versi aset, peta status |
| Tes | `tests/Feature/<Modul>/` | perilaku + security + parity |

## Modul
| Modul | Status | Catatan modul | File utama |
|---|---|---|---|
| DATA | dibangun (Fase 2) | `context/modules/database.md` | `database/migrations/0001_01_01_000000_baseline_dms_schema.php`, `app/Models/*` (17 model), `database/seeders/*` |
| AUTH | dibangun (Fase 2; UI login/ganti password Fase 3) | `context/modules/auth.md` | `AuthController`, `ChangePasswordController`, `EnsureUserHasRole` (`role:`), `UpdateUserPresence`, `Services/ActivityLogger`, `Services/UserPresenceService` |
| SHELL + UI | dibangun (Fase 3, menunggu ACC) | `context/modules/shell.md`, `context/modules/ui-design-system.md` | `FontSizeController` + migrasi `users.font_size` (A1), `layouts/app.blade.php`, `components/*`, `public/css/*`, `public/js/*`, `Support/{Menu,Asset,AppSettings,StatusLabel}`, `Commands/Assets{Cache,Clear}` |
| ARSIP (katalog + pengajuan multi-box) | katalog Fase 3; pengajuan/draft/edit/hapus dibangun Fase 4b (menunggu ACC) | `context/modules/archive.md` | `ArchiveCatalogController`, `ArchiveSubmissionController`, `ArchiveSimilarityController`, `ArchiveFormPrintController` (cetak Form Pengajuan, A1), `Requests/ArchiveSubmissionRequest`, `Actions/Archive/*`, `Services/{ArchiveCatalog,ArchiveBoxBuilder,ArchiveAttachments,ArchiveFormData,ArchiveFormPrint,ArchiveSimilarity,FileStorage,SystemEventStream}`, `Policies/ArchivePolicy`, `views/archives/*`, `public/js/pages/archive-form*.js`, `views/archives/form-print.blade.php` + `public/css/pages/form-print.css`; detail: `Support/{ArchiveDetail,ArchiveFileList}`, `views/archives/show*`, `public/js/pages/archive-show.js` |
| FILE | dibangun (A2) | `context/modules/file.md` | `ArchiveFileController` (`files.preview`/`files.download`), `Services/FileStorage::locate()` (allow-list folder, disk private → public → legacy_public), `config/filesystems.php` (`x_accel`, `links=[]`), `Caddyfile` (X-Accel `handle_response`, `/storage/*` 404), `views/errors/404.blade.php` |
| PENARIKAN | dibangun (putaran B, menunggu ACC; re-store menyusul) | `context/modules/borrowing.md` | `BorrowingController`, `BorrowingApprovalController`, `Requests/BorrowingRequest`, `Policies/BorrowingLogPolicy`, `Actions/Borrowing/{SubmitBorrowing,DispatchBorrowing,RejectBorrowing}`, `Services/{BorrowingList,BorrowingFormPrint}`, `SystemEventStream::borrowings()`, `views/borrowings/*`, `public/js/pages/borrowing-create.js` |
| RETENSI ([Form 3] perpanjangan, pemusnahan, BAP) | dibangun (putaran C, menunggu ACC) | `context/modules/retention.md` | `routes/modules/retention.php`, `DestructionController`, `DestructionApprovalController`, `ExtensionController`, `Requests/{DestructionProposalRequest,ExtensionRequest}`, `Policies/{DestructionLogPolicy,ArchiveExtensionLogPolicy}`, `Actions/Retention/*`, `Services/{RetentionCalculator,RetentionList,RetentionFormPrint}`, `SystemEventStream::destructions()`, `views/destructions/*`, `public/js/pages/destruction-{index,form}.js`, `public/css/pages/retention-print.css` |
