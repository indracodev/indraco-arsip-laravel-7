# Modul Manajemen Departemen, Sub-Dept & Kategori Arsip (Super Admin)

## 1. Deskripsi Fitur
Modul ini mengelola struktur organisasi kearsipan seluruh unit bisnis PT Indraco Global Indonesia. Super Admin memiliki wewenang penuh untuk:
1. **Mengelola 29 Departemen Resmi:** Meliputi Divisi *Finance, Accounting, Tax, HRD, General Affairs, Marketing, Sales, Export, Production, Quality Control, Supply Chain, Logistics, Legal, IT/Webdev*, dll.
2. **Sub-Departemen:** Membagi departemen menjadi unit kerja yang lebih spesifik (contoh: Finance terbagi atas *Accounts Payable (AP)*, *Accounts Receivable (AR)*, *Treasury*).
3. **Penetapan Penanggung Jawab (*PIC Assignment*):** Menugaskan staf pemegang hak kelola arsip departemen (*PIC Departemen*).
4. **Kategori Master Arsip (*Master Archive Categories*):** Menentukan jenis-jenis dokumen baku di tiap departemen lengkap dengan masa retensi default dalam satuan tahun (misal: *Faktur Pajak Standar* = 10 Tahun, *Surat Jalan* = 5 Tahun, *Form Lamaran Kerja* = 2 Tahun).

---

## 2. Hak Akses & Role Permission
- **Role:** `admin` (Super Admin)
- **Middleware:** `auth`, `role:admin`
- **Fokus Kebijakan:** Standardisasi tata kelola nomenklatur dokumen perusahaan.

---

## 3. Komponen Teknis & Skema Database

### File Terkait:
- Controller Departemen: [DepartmentController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/DepartmentController.php)
- Controller Kategori: [MasterArchiveController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/MasterArchiveController.php)
- Model:
  - [Department.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Department.php)
  - [SubDepartment.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/SubDepartment.php)
  - [MasterArchive.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/MasterArchive.php)
- View: `resources/views/master/departments/index.blade.php`, `modals/department_modals.blade.php`, `modals/manage_department_modal.blade.php`
- Route:
  - `GET /master/departments` (`name('master.departments')`)
  - `POST /master/departments` (`name('master.departments.store')`)
  - `POST /master/departments/{department}/pic/assign` (`name('master.departments.pic.assign')`)
  - `POST /master/departments/{department}/master-archives` (`name('master.departments.master_archives.store')`)
  - `POST /master/sub-departments` (`name('master.sub_departments.store')`)

---

## 4. Flowchart Alur Pengelolaan Departemen & Kategori Arsip

```mermaid
flowchart TD
    Start([Super Admin Buka Master Departemen /master/departments]) --> LoadDeptList[Tampilkan Daftar 29 Departemen Indraco dengan Badge Status]
    
    LoadDeptList --> SelectDeptAction{Pilih Tindakan Manajemen:}
    
    %% Alur Tambah / Edit Departemen
    SelectDeptAction -- 1. Buat / Edit Departemen --> FormDept[Buka Modal Departemen: Nama Divisi & Kode Unik cth: 'TAX']
    FormDept --> SubmitDept[Simpan ke Tabel departments]
    SubmitDept --> RefreshDeptList[Refresh Daftar Departemen]

    %% Alur Kelola Sub-Departemen
    SelectDeptAction -- 2. Kelola Sub-Departemen --> OpenSubDeptModal[Buka Tab Sub-Departemen pada Departemen Terpilih]
    OpenSubDeptModal --> InputSubDept[Masukkan Nama Sub-Unit cth: 'Pajak Pertambahan Nilai (PPN)']
    InputSubDept --> SaveSubDept[(Simpan ke Tabel sub_departments)]
    SaveSubDept --> RefreshDeptList

    %% Alur Penetapan PIC Staf
    SelectDeptAction -- 3. Tetapkan Staf PIC Departemen --> OpenAssignPicModal[Buka Modal 'Assign PIC Staf']
    OpenAssignPicModal --> SelectUserDropdown[Pilih Akun Staf Pengguna]
    SelectUserDropdown --> SubmitAssignPic[Update users set department_id = dept.id & role = 'pic_dept']
    SubmitAssignPic --> RefreshDeptList

    %% Alur Kategori Master Dokumen & JRA
    SelectDeptAction -- 4. Atur Kategori Arsip & Masa Retensi --> OpenMasterArchiveModal[Buka Modal 'Master Kategori Dokumen']
    OpenMasterArchiveModal --> InputCategoryData[Ketik Nama Dokumen cth: 'Bukti Potong PPh 23' & Masa Retensi: 10 Tahun]
    InputCategoryData --> SaveMasterArchiveDB[(Simpan ke Tabel master_archives)]
    SaveMasterArchiveDB --> End([Kategori Siap Digunakan Saat Pembuatan Box Baru])
```

---

## 5. Rincian Antarmuka & Langkah Kerja Super Admin

1. Buka menu **Master Data** > **Departemen** (`/master/departments`).
2. **Menugaskan PIC Departemen:**
   - Klik tombol **Kelola PIC** pada baris departemen yang dituju (misal: *Finance & Accounting*).
   - Pilih nama staf pengguna dari dropdown. Staf tersebut kini memiliki wewenang untuk membuat box dan menyetujui penarikan berkas divisi Finance.
3. **Menambahkan Kategori Dokumen Baru:**
   - Klik tombol **Kelola Kategori Arsip**.
   - Masukkan nama jenis berkas (contoh: *Faktur Penjualan Lokal*).
   - Masukkan masa retensi (contoh: `10` tahun).
   - Simpan data. Saat staf membuat box pengajuan baru, opsi kategori ini akan langsung muncul dan otomatis menghitung tanggal kedaluwarsa 10 tahun dari tahun dokumen.
