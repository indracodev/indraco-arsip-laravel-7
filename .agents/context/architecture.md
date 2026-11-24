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
| CSS | `public/css/` (`tokens`, `base`, `layout`, `components/*.css` → `components.css`) | CSS murni, tanpa build tool |
| JS | `public/js/` (+ `public/js/pages/<modul>.js` bila perlu) | vanilla + `Alpine.data` (Alpine lokal di `public/js/vendor/`) |
| Tampilan pendukung | `app/Support/` | menu, setting per request, versi aset, peta status |
| Tes | `tests/Feature/<Modul>/` | perilaku + security + parity |

## Modul
| Modul | Status | Catatan modul | File utama |
|---|---|---|---|
| DATA | dibangun (Fase 2) | `context/modules/database.md` | `database/migrations/0001_01_01_000000_baseline_dms_schema.php`, `app/Models/*` (17 model), `database/seeders/*` |
| AUTH | dibangun (Fase 2; UI login/ganti password Fase 3) | `context/modules/auth.md` | `AuthController`, `ChangePasswordController`, `EnsureUserHasRole` (`role:`), `UpdateUserPresence`, `Services/ActivityLogger`, `Services/UserPresenceService` |
| SHELL + UI | dibangun (Fase 3, menunggu ACC) | `context/modules/shell.md`, `context/modules/ui-design-system.md` | `layouts/app.blade.php`, `components/*`, `public/css/*`, `public/js/*`, `Support/{Menu,Asset,AppSettings,StatusLabel}`, `Commands/Assets{Cache,Clear}` |
| ARSIP (katalog) | mockup hanya-baca (Fase 3) | `context/modules/shell.md` | `ArchiveCatalogController`, `Services/ArchiveCatalog`, `Policies/ArchivePolicy`, `ArchiveCreateMockupController`, `views/archives/*` |
