# Plan Implementation: Downgrade & Porting DMS PT Indraco ke Laravel 7

Dokumen ini berisi rencana kerja teknis, penyesuaian arsitektur framework, rancangan skema database, pemetaan controller & route, serta panduan langkah demi langkah untuk melakukan **Downgrade / Porting Aplikasi Document Management System (DMS) / Gudang Arsip PT Indraco** dari **Laravel 10** (`indraco-arsip-laravel-10`) ke **Laravel 7** (`indraco-arsip-laravel-7`).

---

## 1. Ringkasan Proyek & Tujuan

- **Nama Aplikasi:** Document Management System (DMS) / Sistem Manajemen & Gudang Arsip PT Indraco
- **Direktori Asal (Laravel 10):** `C:\laragon\www\#Project2026\indraco-arsip-laravel-10`
- **Direktori Target (Laravel 7):** `C:\laragon\www\#Project2026\indraco-arsip-laravel-7`
- **Tujuan Utama:** Memastikan seluruh fitur bisnis, alur kerja operasional gudang, hak akses role, *Dynamic Custom Box Code Engine*, *Interactive 2D Warehouse Layout Canvas*, *Desktop UI Edition (Delphi/VB Style)* untuk PIC Departemen, *Global Audit Trail Logs*, *Retention Expiry & BAP Destruction*, serta *Dynamic Font Scaling System* berjalan **100% identik dan tanpa pengurangan fitur (100% Feature Parity)** pada lingkungan **Laravel 7 (PHP 7.4 / 8.0)**.

---

## 2. Matriks Perbedaan Framework & Technical Downgrade

| Komponen Teknis | Laravel 10 (`indraco-arsip-laravel-10`) | Laravel 7 (`indraco-arsip-laravel-7`) | Strategi Downgrade / Penyesuaian |
| :--- | :--- | :--- | :--- |
| **PHP Version** | `^8.1` | `^7.2.5` \|\| `^7.4` \|\| `^8.0` | Menggunakan syntax PHP 7.4 / 8.0 kompatibel (menghindari typed properties pada arrow function atau union types yang tidak didukung PHP <8.0). |
| **Framework Core** | `laravel/framework: ^10.0` | `laravel/framework: ^7.0` | Mengganti dependensi `composer.json` ke versi Laravel 7.x. |
| **Error Handler / Ignition** | `spatie/laravel-ignition: ^2.0` | `facade/ignition: ^2.0` | Menggunakan `facade/ignition` sebagai exception handler development. |
| **Database Migrations** | Anonymous Class (`return new class extends Migration`) | Named Class (`class CreateArchivesTable extends Migration`) | Mengubah seluruh file migrasi dari anonymous class ke named class bertipe `extends Migration`. |
| **Model Factories** | Class-based Factories (`ArchiveFactory`) | Closure-based Factories (`$factory->define(Archive::class, ...)`)| Menyesuaikan file seeder dan factory ke syntax Laravel 7 `$factory->define`. |
| **Model Namespace** | `App\Models\*` | `App\*` atau `App\Models\*` (dengan autoload) | Mempertahankan namespace `App\Models\*` dengan memastikan autoloader PSR-4 composer mendukung folder `app/Models`. |
| **Route Declarations** | Class Tuple (`[ArchiveController::class, 'index']`) | Action String (`'ArchiveController@index'`) atau Class Array dengan Namespace Import | Menggunakan format string `'ArchiveController@index'` atau menambahkan `$namespace = 'App\Http\Controllers'` pada `RouteServiceProvider`. |
| **Middleware Role** | Closure / Custom Middleware | Custom Middleware (`CheckRole`) registered in `Kernel.php` | Mendaftarkan `'role' => \App\Http\Middleware\CheckRole::class` di `$routeMiddleware` pada `app/Http/Kernel.php`. |
| **Asset Bundling** | Vite (`@vite(...)`) | Laravel Mix (`mix('css/app.css')`) / Direct CDN | Menggunakan CDN Tailwind CSS + Alpine.js + Lucide Icons atau Laravel Mix (`webpack.mix.js`). |

---

## 3. Rencana Detail Modifikasi & Porting Berkas (Proposed Changes)

---

### A. Environment & Configuration Files

#### [NEW] [composer.json](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/composer.json)
- Mengonfigurasi dependensi versi Laravel 7:
  ```json
  {
      "name": "laravel/laravel",
      "type": "project",
      "description": "The Laravel Framework - DMS PT Indraco (Laravel 7 Edition)",
      "require": {
          "php": "^7.2.5|^7.4|^8.0",
          "fideloper/proxy": "^4.2",
          "guzzlehttp/guzzle": "^6.3.1|^7.0.1",
          "laravel/framework": "^7.0",
          "laravel/tinker": "^2.0"
      },
      "require-dev": {
          "facade/ignition": "^2.0",
          "fzaninotto/faker": "^1.9.1",
          "mockery/mockery": "^1.3.1",
          "nunomaduro/collision": "^4.1",
          "phpunit/phpunit": "^8.5|^9.0"
      },
      "autoload": {
          "psr-4": {
              "App\\": "app/"
          },
          "classmap": [
              "database/seeds",
              "database/factories"
          ]
      }
  }
  ```

#### [NEW] [.env.example](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/.env.example) & [.env](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/.env)
- Menyusun file `.env` dengan dukungan parameter font scaling `APP_FONT_SIZE`:
  ```env
  APP_NAME="INDRACO DMS"
  APP_ENV=local
  APP_KEY=
  APP_DEBUG=true
  APP_URL=http://localhost:8000

  LOG_CHANNEL=stack

  DB_CONNECTION=sqlite
  # Jika menggunakan MySQL/MariaDB:
  # DB_HOST=127.0.0.1
  # DB_PORT=3306
  # DB_DATABASE=indraco_arsip_l7
  # DB_USERNAME=root
  # DB_PASSWORD=

  # Pengaturan Skala Ukuran Font Aplikasi DMS (small, medium, large, xlarge, 15px)
  APP_FONT_SIZE=medium
  ```

---

### B. Database Migrations & Seeders (`database/`)

#### [NEW] Migrations (Format Named Class Laravel 7)
Porting seluruh 9 tabel migrasi dari Laravel 10 ke Laravel 7:
1. `database/migrations/2026_01_01_000001_create_departments_table.php` &rarr; `class CreateDepartmentsTable extends Migration`
2. `database/migrations/2026_01_01_000002_create_users_table.php` &rarr; `class CreateUsersTable extends Migration` (kolom `department_id`, `role`, `phone`)
3. `database/migrations/2026_01_01_000003_create_warehouses_table.php` &rarr; `class CreateWarehousesTable extends Migration`
4. `database/migrations/2026_01_01_000004_create_warehouse_locations_table.php` &rarr; `class CreateWarehouseLocationsTable extends Migration` (kolom `room_sector`, `canvas_x`, `canvas_y`, `canvas_width`, `canvas_height`, `orientation`, `assigned_department_id`, `is_booked`, `booked_by_user_id`, `booking_notes`)
5. `database/migrations/2026_01_01_000005_create_numbering_formats_table.php` &rarr; `class CreateNumberingFormatsTable extends Migration` (`pattern` default: `{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}`)
6. `database/migrations/2026_01_01_000006_create_archives_table.php` &rarr; `class CreateArchivesTable extends Migration`
7. `database/migrations/2026_01_01_000007_create_warehouse_entry_logs_table.php` &rarr; `class CreateWarehouseEntryLogsTable extends Migration`
8. `database/migrations/2026_01_01_000008_create_borrowing_logs_table.php` &rarr; `class CreateBorrowingLogsTable extends Migration`
9. `database/migrations/2026_01_01_000009_create_destruction_logs_table.php` &rarr; `class CreateDestructionLogsTable extends Migration`

#### [NEW] Seeders (`database/seeds/`)
1. `DatabaseSeeder.php`: Memanggil seluruh seeder utama.
2. `DepartmentSeeder.php`: Mengisi departemen `FIN` (Keuangan), `HRD` (HR & Legal), `MKT` (Marketing), `LOG` (Logistik), `GA` (General Affairs), `IT` (Teknologi Informasi).
3. `UserSeeder.php`: Mengisi akun demo siap pakai:
   - `admin@indraco.com` (Super Admin)
   - `gudang@indraco.com` (PIC Gudang Specialist)
   - `fin@indraco.com` (PIC Dept Keuangan)
   - `hrd@indraco.com` (PIC Dept HRD)
   - `mkt@indraco.com` (PIC Dept Marketing)
4. `WarehouseSeeder.php`: Mengisi data Gudang Utama PT Indraco dan layout koordinat canvas Sektor R0, R1 (A-F FAT), R2, R3, R4, R5, R6, Gudang GA, Gudang IT.
5. `NumberingFormatSeeder.php`: Mengisi template penomoran box default `{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}`.

---

### C. Models & Business Logic Services (`app/`)

#### [NEW] Models (`app/Models/`)
Porting seluruh model Eloquent dengan penyesuaian Laravel 7:
- [Archive.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Archive.php): Model utama arsip, relasi ke `Department`, `User`, `WarehouseLocation`, scope status, helper `isNearingExpiry()`, `isExpired()`.
- [BorrowingLog.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/BorrowingLog.php): Model alur peminjaman, relasi ke `Archive`, borrower `User`, dan verifikator gudang.
- [Department.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Department.php): Model master departemen.
- [DestructionLog.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/DestructionLog.php): Model BAP pemusnahan, relasi ke `Archive` dan `User`.
- [NumberingFormat.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/NumberingFormat.php): Model format custom nomor box.
- [User.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/User.php): Model pengguna, helper role check (`isAdmin()`, `isPicGudang()`, `isPicDept()`).
- [Warehouse.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Warehouse.php): Model master gudang.
- [WarehouseEntryLog.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseEntryLog.php): Model audit log penerimaan berkas di rak.
- [WarehouseLocation.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseLocation.php): Model lokasi rak & koordinat canvas.

#### [NEW] Service: `app/Services/NumberingService.php`
Porting **Dynamic Custom Box Code Engine**:
- Parser placeholder: `{COMPANY}`, `{DEPT}`, `{YEAR}`, `{ROMAN_MONTH}`, `{COUNTER}`, `{COUNTER_BOX}`.
- Metode generator nomor box otomatis saat PIC Gudang menyetujui pengajuan arsip.

---

### D. Middleware & HTTP Kernel (`app/Http/`)

#### [NEW] [app/Http/Middleware/CheckRole.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Middleware/CheckRole.php)
- Custom middleware untuk memeriksa peran pengguna (`admin`, `pic_gudang`, `pic_dept`).

#### [MODIFY] [app/Http/Kernel.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Kernel.php)
- Mendaftarkan route middleware:
  ```php
  protected $routeMiddleware = [
      // ...
      'auth' => \App\Http\Middleware\Authenticate::class,
      'role' => \App\Http\Middleware\CheckRole::class,
  ];
  ```

---

### E. Controllers (`app/Http/Controllers/`)

Porting 12 controller utama dari Laravel 10 ke Laravel 7:

1. **`AuthController.php`**:
   - `showLogin()`: Halaman login corporate lengkap dengan tombol pintasan akun demo.
   - `login()`: Otentikasi credential.
   - `logout()`: Logout session.
2. **`DashboardController.php`**:
   - `index()`: Overview statistik, kapasitas gudang, alert expiry, & arsip terbaru.
   - `searchApi()`: API live search pencarian arsip instan.
3. **`ArchiveController.php`**:
   - `index()`, `create()`, `store()`, `show()`, `verify()`, `checkin()`, `printLabels()`, `printSticker()`.
4. **`BorrowingController.php`**:
   - `index()`, `create()`, `store()`, `deptApprove()`, `approve()`, `dispatch()`, `returnArchive()`.
5. **`DestructionController.php`**:
   - `index()`, `proposeForm()`, `propose()`, `showBap()`, `extendForm()`, `extendStore()`, `extendPrint()`.
6. **`AuditLogController.php`**:
   - `index()`: Tabbed Audit Log (Log Masuk Gudang, Log Pinjam, Log Pemusnahan).
7. **`WarehouseLayoutController.php`**:
   - `index()`: Interactive 2D Warehouse Canvas.
   - API endpoints: `apiLayoutData()`, `storeLocation()`, `bookLocation()`, `unbookLocation()`, `updateLocation()`, `destroyLocation()`.
8. **`DepartmentController.php`**, **`WarehouseController.php`**, **`NumberingFormatController.php`**, **`UserController.php`**:
   - Master data CRUD & fitur **User Impersonation** (`impersonate()`, `leaveImpersonate()`).

---

### F. Web Routes (`routes/web.php`)

#### [NEW] [routes/web.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/routes/web.php)
Route diformat untuk kompatibilitas Laravel 7:

```php
<?php

use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', 'AuthController@showLogin')->name('login');
    Route::post('/login', 'AuthController@login')->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', 'AuthController@logout')->name('logout');

    // Dashboard & Live Search
    Route::get('/', 'DashboardController@index')->name('dashboard');
    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/api/search-archives', 'DashboardController@searchApi')->name('archives.search_api');

    // Archives Management
    Route::get('/archives', 'ArchiveController@index')->name('archives.index');
    Route::get('/archives/create', 'ArchiveController@create')->name('archives.create');
    Route::post('/archives', 'ArchiveController@store')->name('archives.store');
    Route::get('/archives/print-labels', 'ArchiveController@printLabels')->name('archives.print_labels');
    Route::post('/archives/print-labels', 'ArchiveController@printLabels')->name('archives.print_labels_post');
    Route::get('/archives/{archive}', 'ArchiveController@show')->name('archives.show');
    Route::get('/archives/{archive}/print-sticker', 'ArchiveController@printSticker')->name('archives.print_sticker');
    Route::post('/archives/{archive}/verify', 'ArchiveController@verify')->name('archives.verify');
    Route::post('/archives/{archive}/checkin', 'ArchiveController@checkin')->name('archives.checkin');

    // Borrowing Workflow
    Route::get('/borrowings', 'BorrowingController@index')->name('borrowings.index');
    Route::get('/borrowings/create', 'BorrowingController@create')->name('borrowings.create');
    Route::post('/borrowings', 'BorrowingController@store')->name('borrowings.store');
    Route::post('/borrowings/{borrowing}/dept-approve', 'BorrowingController@deptApprove')->name('borrowings.dept_approve');
    Route::post('/borrowings/{borrowing}/approve', 'BorrowingController@approve')->name('borrowings.approve');
    Route::post('/borrowings/{borrowing}/dispatch', 'BorrowingController@dispatch')->name('borrowings.dispatch');
    Route::post('/borrowings/{borrowing}/return', 'BorrowingController@returnArchive')->name('borrowings.return');

    // Retention Expiry & Destruction Workflow
    Route::get('/destructions', 'DestructionController@index')->name('destructions.index');
    Route::get('/destructions/propose/{archive}', 'DestructionController@proposeForm')->name('destructions.propose');
    Route::post('/destructions/propose/{archive}', 'DestructionController@propose')->name('destructions.store');
    Route::get('/destructions/bap/{destructionLog}', 'DestructionController@showBap')->name('destructions.bap');
    Route::get('/destructions/extend/{archive}', 'DestructionController@extendForm')->name('destructions.extend_form');
    Route::post('/destructions/extend/{archive}', 'DestructionController@extendStore')->name('destructions.extend_store');
    Route::get('/destructions/extend-print/{archive}', 'DestructionController@extendPrint')->name('destructions.extend_print');

    // Global Audit Trail Logs
    Route::get('/logs', 'AuditLogController@index')->name('logs.index');

    // Layout Gudang Interactive Canvas & API
    Route::get('/master/warehouses/layout', 'WarehouseLayoutController@index')->name('master.warehouses.layout');
    Route::get('/api/warehouse/layout-data', 'WarehouseLayoutController@apiLayoutData')->name('api.warehouse.layout_data');
    Route::post('/api/warehouse/locations/store', 'WarehouseLayoutController@storeLocation')->name('api.warehouse.locations.store');
    Route::post('/api/warehouse/locations/{location}/book', 'WarehouseLayoutController@bookLocation')->name('api.warehouse.locations.book');
    Route::post('/api/warehouse/locations/{location}/unbook', 'WarehouseLayoutController@unbookLocation')->name('api.warehouse.locations.unbook');
    Route::post('/api/warehouse/locations/{location}/update', 'WarehouseLayoutController@updateLocation')->name('api.warehouse.locations.update');
    Route::post('/api/warehouse/locations/{location}/delete', 'WarehouseLayoutController@destroyLocation')->name('api.warehouse.locations.delete');

    // Master Data Management (Admin & PIC Gudang)
    Route::middleware('role:admin,pic_gudang')->prefix('master')->name('master.')->group(function () {
        Route::get('/departments', 'DepartmentController@index')->name('departments');
        Route::post('/departments', 'DepartmentController@store')->name('departments.store');
        Route::put('/departments/{department}', 'DepartmentController@update')->name('departments.update');
        Route::delete('/departments/{department}', 'DepartmentController@destroy')->name('departments.destroy');

        Route::get('/warehouses', 'WarehouseController@index')->name('warehouses');
        Route::post('/warehouses', 'WarehouseController@storeWarehouse')->name('warehouses.store');
        Route::put('/warehouses/{warehouse}', 'WarehouseController@updateWarehouse')->name('warehouses.update');
        Route::delete('/warehouses/{warehouse}', 'WarehouseController@destroyWarehouse')->name('warehouses.destroy');

        Route::post('/warehouses/locations', 'WarehouseController@storeLocation')->name('warehouses.locations.store');
        Route::delete('/warehouses/locations/{location}', 'WarehouseController@destroyLocation')->name('warehouses.locations.destroy');

        Route::get('/numbering', 'NumberingFormatController@index')->name('numbering')->middleware('role:admin');
        Route::post('/numbering', 'NumberingFormatController@store')->name('numbering.store')->middleware('role:admin');
        Route::put('/numbering/{numberingFormat}', 'NumberingFormatController@update')->name('numbering.update')->middleware('role:admin');

        Route::get('/users', 'UserController@index')->name('users');
        Route::post('/users', 'UserController@store')->name('users.store');
        Route::put('/users/{user}', 'UserController@update')->name('users.update');
        Route::delete('/users/{user}', 'UserController@destroy')->name('users.destroy');
        Route::post('/users/{user}/impersonate', 'UserController@impersonate')->name('users.impersonate');
    });

    // Leave Impersonate Route
    Route::post('/impersonate/leave', 'UserController@leaveImpersonate')->name('impersonate.leave');
});
```

---

### G. Blade Views & UI Layout Systems (`resources/views/`)

#### 1. Dual Layout System (Corporate Web + Desktop Edition)
- **`layouts/app.blade.php`**: Master layout Corporate Navy & White Clean untuk role `admin` dan `pic_gudang`. Mendukung pengontrol skala font dinamis via `APP_FONT_SIZE` di `.env`.
- **`layouts/desktop_pic.blade.php`**: Master layout **Desktop Form Application Style (Delphi / VB Style)** khusus untuk role `pic_dept`. Dilengkapi dengan:
  - Top Title Bar & Classic Main Menu.
  - Delphi Action Ribbon (`[+] Baru (F2)`, `[🔍] Cari (Ctrl+F)`, `[🖨️] Cetak Label (F9)`, `[🔄] Refresh (F5)`, `[📝] Pinjam (F8)`).
  - MDI Tabbed Workspace Manager.
  - Enterprise DBGrid View (Compact Spreadsheet Style, Zebra Striping, Gridlines).
  - Bottom Status Bar (Status, Records Count, Hotkeys Indicator, Server Status).
  - Hotkeys JS Engine (`F2`, `F5`, `F8`, `F9`, `Ctrl+F`, `Esc`).

#### 2. Public Branding & Media Assets (`public/`)
Copy seluruh aset gambar branding ke `indraco-arsip-laravel-7/public/`:
- `logo-indraco-est.png` &rarr; Logo resmi PT Indraco pada header navbar & dokumen cetak BAP.
- `image.png` &rarr; Acuan sketsa denah fisik gudang arsip.

#### 3. Complete Module Views Porting
- **`auth/login.blade.php`**: Login corporate dengan Fast Demo Login Buttons (`admin`, `gudang`, `fin`, `hrd`, `mkt`).
- **`dashboard.blade.php`**: Ringkasan statistik, alert expiry, & search bar.
- **`archives/`**: `index.blade.php`, `create.blade.php`, `show.blade.php`, `print_labels.blade.php`, `print_sticker.blade.php`.
- **`borrowings/`**: `index.blade.php`, `create.blade.php`.
- **`destructions/`**: `index.blade.php`, `propose.blade.php`, `bap.blade.php` (Cetak PDF/HTML BAP 3-kolom TTD), `extend_form.blade.php`.
- **`logs/index.blade.php`**: Global Audit Trail (Log Masuk Gudang, Log Pinjam, Log Pemusnahan).
- **`master/`**: `departments.blade.php`, `warehouses.blade.php`, `numbering.blade.php`, `users.blade.php`, dan `warehouses/layout.blade.php` (Interactive 2D Canvas).

---

## 4. Panduan Eksekusi Langkah demi Langkah (Step-by-Step Downgrade)

### Langkah 1: Persiapan Repository & Folder Target
1. Pastikan folder `C:\laragon\www\#Project2026\indraco-arsip-laravel-7` siap.
2. Salin seluruh struktur aplikasi dari `indraco-arsip-laravel-10` kecuali folder `vendor`, `node_modules`, `.git`, dan file cache.

### Langkah 2: Konfigurasi `composer.json` & Install Dependensi
1. Buat/perbarui `composer.json` di `indraco-arsip-laravel-7` menggunakan konfigurasi Laravel 7.
2. Jalankan perintah composer:
   ```bash
   cd C:\laragon\www\#Project2026\indraco-arsip-laravel-7
   composer install
   ```

### Langkah 3: Setup File Environment `.env`
1. Salin `.env.example` ke `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
2. Pastikan file database SQLite `database/database.sqlite` dibuat jika menggunakan SQLite:
   ```bash
   php -r "if(!file_exists('database/database.sqlite')) touch('database/database.sqlite');"
   ```

### Langkah 4: Penyesuaian Syntax Code (Laravel 10 &rarr; Laravel 7)
1. Ubah migrasi ke format *Named Class*.
2. Ubah `routes/web.php` menggunakan string action controller or import namespace proper.
3. Daftarkan middleware role di `app/Http/Kernel.php`.

### Langkah 5: Run Migration & Database Seeder
1. Eksekusi migrasi & seeder data demo:
   ```bash
   php artisan migrate:fresh --seed
   ```

### Langkah 6: Verifikasi & Menjalankan Server Local
1. Jalankan development server Laravel 7:
   ```bash
   php artisan serve --port=8001
   ```
2. Akses aplikasi di browser melalui `http://127.0.0.1:8001`.

---

## 5. Rencana Verifikasi & Pengujian (Verification Plan)

### A. Automated / CLI Verifications
- Run `php artisan route:list` untuk memastikan 100% rute terdaftar tanpa error syntax.
- Run `php artisan config:clear` dan `php artisan view:clear`.

### B. Manual Functional Verification
1. **Pintasan Quick Login & Role Access Control:**
   - Uji login 1-click sebagai `Super Admin`, `PIC Gudang`, dan `PIC Dept Keuangan`.
   - Verifikasi impersonation user (`impersonate` dan `leaveImpersonate`).
2. **PIC Departemen Desktop UI Workstation:**
   - Login `fin@indraco.com` &rarr; Pastikan layout berganti otomatis ke tampilan **Desktop Application (Delphi/VB Style)**.
   - Uji hotkeys (`F2`, `F5`, `F8`, `F9`, `Ctrl+F`).
3. **Workflow Booking Storage & Custom Numbering Box Engine:**
   - PIC Dept buat draft baru &rarr; PIC Gudang verifikasi & checkin &rarr; Pastikan Nomor Box otomatis ter-generate sesuai pattern `{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}`.
4. **Interactive 2D Warehouse Layout Canvas:**
   - Akses `/master/warehouses/layout` &rarr; Pastikan denah 2D Sektor R0-R6, Gudang GA, Gudang IT ter-render pada HTML5 canvas beserta fitur inspeksi rak & booking slot.
5. **Workflow Retention Expiry, BAP Destruction, & Audit Trail:**
   - Cek alert expiry (&le; 90 hari), ajukan pemusnahan, cetak dokumen resmi BAP dengan 3-kolom tanda tangan.
   - Cek keutuhan Log Masuk Gudang, Log Pinjam, dan Log Pemusnahan di menu Audit Trail (`/logs`).
6. **Dynamic Font Scaling Test:**
   - Ubah `APP_FONT_SIZE=small` / `xlarge` pada `.env` & clear config &rarr; Pastikan skala font di seluruh halaman berubah dinamis.

---
*Dokumen implementation plan ini dibuat sebagai panduan teknis utama downgrade dan porting aplikasi DMS PT Indraco ke Laravel 7 di `C:\laragon\www\#Project2026\indraco-arsip-laravel-7`.*
