# Modul: MASTER (departemen, sub-departemen, master berkas, impor Excel data master, gudang & rak, format penomoran)
Status: dibangun (putaran E), menunggu verifikasi koordinator

## Route lama → baru (URL & nama L7 tetap; file `routes/modules/master.php`)
| Route | Controller | Akses |
|---|---|---|
| `GET/POST /master/departments`, `PUT/DELETE /master/departments/{d}`, `POST …/{d}/toggle-active` | `DepartmentController` | admin |
| `GET …/departments/import/template`, `POST …/import/{preview,commit}` | `MasterDataImportController` | admin |
| `POST …/{d}/pic/assign`, `DELETE …/{d}/pic/{user}` | `DepartmentPicController` | admin |
| `POST …/{d}/master-archives(/batch)`, `PUT/DELETE …/{d}/master-archives/{m}` (scoped binding) | `MasterArchiveController` | admin |
| `POST/PUT/DELETE /master/sub-departments…`, toggle | `SubDepartmentController` | admin |
| `GET /api/departments/{d}/{manage-data,archives}`, `GET /api/sub-departments/{s}/archives` | `DepartmentApiController` | admin (temuan #5 ditutup) |
| `GET/POST /master/warehouses`, `POST /master/warehouses/locations` | `WarehouseController`, `WarehouseLocationController` | admin + pic_gudang |
| `PUT/DELETE /master/warehouses/{w}`, toggle, `PUT/DELETE /master/warehouses/locations/{l}` | idem | admin |
| `GET/POST /master/numbering`, `PUT /master/numbering/{f}` | `NumberingFormatController` | admin |
| `GET /api/departments/{d}/master-archives` (L7 dropdown arsip) | tidak dibuat di sini (ARSIP menyematkan data di form) | – |

## Aturan bisnis (sama L7, kecuali "Beda")
- Dept: kode wajib ≤10, unik, disimpan huruf besar; retensi kosong = 5. Hapus ditolak bila dept/sub punya arsip.
- Sub: kode ≤20 unik per departemen ("Kode sub-departemen X sudah digunakan pada departemen ini.").
- Master berkas: tunggal / batch (1 baris = 1 berkas, `insert` per 100), retensi default sub→dept→5, kategori 7 pilihan L7.
- Impor Excel: template `template-import-master/template_import_data_master.xlsx` sheet "Data Master", header dicocokkan nama; cocok lewat kode dept, kode sub dalam dept, nama dokumen (case/spasi diabaikan); yang sudah ada tidak diubah/diduplikasi; error per baris "Baris N: …" memblokir commit; peringatan nama beda; log `MASTER_DATA_IMPORT`. File hanya dibaca di memori (tidak disimpan).
- Gudang: buat gudang = buat objek `room` di layout 2D; edit kode menyinkronkan room; toggle menyinkronkan room. GA/IT hanya terlihat admin.
- Penomoran: satu format aktif; pratinjau `NumberingService::preview()` (counter tidak bergerak saat halaman dibuka).
- Beda (perbaikan): edit dept tidak lagi mereset retensi ke 5 (form tidak punya field retensi); hapus dept melepas `users.department_id`; sub dept master berkas harus milik dept di URL; hapus gudang ditolak bila raknya masih berisi box (FK cascade menghapus rak+slot); edit format penomoran dengan counter basi (`original_counter`) ditolak; GET `/master/warehouses` tidak lagi menyinkronkan room→gudang (semua 9 room produksi sudah punya gudang; pembuatan room di Layout 2D = Round F wajib membuat gudang); pencarian di halaman penomoran L7 tidak berfungsi → tidak dibuat; modal visualisasi 100 slot rak → tautan ke Layout Gudang 2D (Round F).

## File L13
`app/Http/Controllers/{Department,DepartmentApi,DepartmentPic,SubDepartment,MasterArchive,MasterDataImport,Warehouse,WarehouseLocation,NumberingFormat}Controller.php`, `app/Http/Requests/{Department,SubDepartment,MasterArchive,Warehouse,WarehouseLocation,NumberingFormat}Request.php`, `app/Actions/Master/{DeleteDepartment,AssignDepartmentPic,DeleteWarehouse,SaveNumberingFormat}.php`, `app/Services/{MasterDataImportService,MasterDataImportPlan,SpreadsheetReader}.php`, `app/Support/WarehouseMaster.php`, `resources/views/master/{departments,warehouses,numbering}/**`, `resources/views/master/partials/errors.blade.php`, `public/js/pages/{master-departments,master-department-manage,master-import,master-warehouses,master-reopen}.js`, `public/css/components/master.css`.

## Tes
`tests/Feature/Master/{DepartmentTest,DepartmentManageTest,MasterDataImportTest,WarehouseMasterTest,NumberingFormatTest}.php`.

## Sisa
- UNIQUE index `departments.code` / `warehouses.code` (0 duplikat di DB produksi) — usulan, menunggu pemilik.
- Pesan validasi bawaan masih bahasa Inggris (tidak ada folder `lang/`).
