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
| Tampilan | `resources/views/<modul>/`, `components/` | Blade + komponen |
| JS | `resources/js/pages/<modul>.js` | Alpine.data per halaman |
| Tes | `tests/Feature/<Modul>/` | perilaku + security + parity |

## Modul
| Modul | Status | Catatan modul | File utama |
|---|---|---|---|
| (diisi mulai Fase 2) | | `context/modules/<modul>.md` | |
