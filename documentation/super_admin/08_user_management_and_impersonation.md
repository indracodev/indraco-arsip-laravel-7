# Modul Manajemen Pengguna & Impersonasi Akun (Super Admin)

## 1. Deskripsi Fitur
Modul Manajemen Pengguna memberikan wewenang penuh kepada Super Admin untuk mengelola hak akses seluruh personel yang berinteraksi dengan DMS PT Indraco. Sistem ini mendukung:
1. **Manajemen Akun Staf (User CRUD):** Mendaftarkan akun baru, menetapkan peran kerja (`admin`, `pic_gudang`, atau `pic_dept`), mengaitkan pengguna dengan departemen asalnya, dan mengupdate nomor kontak WhatsApp/telepon.
2. **Reset Kata Sandi Cepat (*Password Reset*):** Mereset kata sandi staf yang lupa password menjadi kata sandi default atau acak yang aman.
3. **Impersonasi Akun (*Login-As Feature*):** Super Admin dapat masuk dan bertindak sebagai staf departemen atau staf gudang tanpa memerlukan kata sandi mereka guna memverifikasi alur kerja, membantu proses input berkas, atau menyelidiki kendala teknis lapangan.
4. **Pemulihan Sesi Mandiri (*Leave Impersonation*):** Tombol banner mengambang untuk mengembalikan sesi aktif seketika ke akun Super Admin semula.

---

## 2. Hak Akses & Role Permission
- **Role:** `admin` (Super Admin)
- **Middleware:** `auth`, `role:admin`
- **Aturan Proteksi:** Super Admin tidak diizinkan untuk menghapus atau mengimpersonasi akunnya sendiri.

---

## 3. Komponen Teknis & Skema Database

### File Terkait:
- Controller: [UserController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/UserController.php)
  - `index()`: Menampilkan daftar seluruh akun staf.
  - `store()`: Membuat akun baru.
  - `update()`: Mengubah informasi nama, email, departemen, dan peran.
  - `destroy()`: Menghapus akun pengguna.
  - `resetPassword()`: Mereset password akun tertentu.
  - `impersonate()`: Memulai sesi login-as.
  - `leaveImpersonate()`: Mengakhiri sesi login-as dan memulihkan sesi Super Admin asli.
- Model: [User.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/User.php)
- View: `resources/views/master/users/index.blade.php`
- Route:
  - `GET /master/users` (`name('master.users')`)
  - `POST /master/users` (`name('master.users.store')`)
  - `PUT /master/users/{user}` (`name('master.users.update')`)
  - `DELETE /master/users/{user}` (`name('master.users.destroy')`)
  - `POST /master/users/{user}/reset-password` (`name('master.users.reset_password')`)
  - `POST /master/users/{user}/impersonate` (`name('master.users.impersonate')`)
  - `POST /impersonate/leave` (`name('impersonate.leave')`)

---

## 4. Flowchart Alur Manajemen User & Mekanisme Impersonasi

```mermaid
flowchart TD
    Start([Super Admin Buka Menu Manajemen Pengguna /master/users]) --> ViewUsersTable[Tampilkan Tabel Pengguna Lengkap dengan Badge Role & Departemen]
    
    ViewUsersTable --> SelectOperation{Pilih Tindakan Super Admin:}
    
    %% Alur Tambah Akun
    SelectOperation -- 1. Buat Pengguna Baru --> OpenUserModal[Buka Form Tambah User]
    OpenUserModal --> InputUserData[Isi: Nama, Email, Password, Peran: admin/pic_gudang/pic_dept, Departemen]
    InputUserData --> SubmitNewUser[POST ke /master/users]
    SubmitNewUser --> HashPassword[Hash Bcrypt Password & Simpan ke Tabel users]
    HashPassword --> RefreshUserTable[Perbarui Daftar Pengguna]

    %% Alur Reset Kata Sandi
    SelectOperation -- 2. Reset Kata Sandi Staf --> ClickResetPass[Klik Tombol 'Reset Password' pada Baris User]
    ClickResetPass --> ConfirmReset[Konfirmasi Reset: Password Diubah Menjadi 'password']
    ConfirmReset --> PostResetPass[POST ke /master/users/{id}/reset-password]
    PostResetPass --> ShowNewPassNotice[Pesan Sukses: Password Akun Berhasil Direset]

    %% Alur Impersonasi (Login-As)
    SelectOperation -- 3. Impersonasi (Login-As) --> ClickImpersonate[Klik Tombol 'Login As' pada Staf Terpilih]
    ClickImpersonate --> CheckSelf{Apakah User Tersebut Adalah Diri Sendiri?}
    CheckSelf -- Ya --> PreventAction[Tolak: Tidak Dapat Mengimpersonasi Diri Sendiri]
    CheckSelf -- Bukan Diri Sendiri --> SaveOriginalAdminSession[Simpan ID Super Admin ke Session 'impersonated_by']
    SaveOriginalAdminSession --> SwitchAuth[Auth::loginUsingId ke ID Staf Target]
    SwitchAuth --> LogAuditImpersonate[Catat ke activity_logs: auth.impersonate_started]
    LogAuditImpersonate --> OpenTargetDesktop[Tampilkan Layar Workstation dengan Hak Akses Staf Target]

    %% Alur Kembali ke Akun Asli
    OpenTargetDesktop --> ClickLeaveBanner[Super Admin Klik Tombol 'Kembalikan Akun Super Admin' di Header]
    ClickLeaveBanner --> PostLeaveImpersonate[POST ke /impersonate/leave]
    PostLeaveImpersonate --> ReadOriginalAdminId[Baca ID dari Session 'impersonated_by']
    ReadOriginalAdminId --> RestoreOriginalAuth[Auth::loginUsingId ke Akun Super Admin Semula]
    RestoreOriginalAuth --> ClearImpersonateSession[Hapus Session 'impersonated_by']
    ClearImpersonateSession --> ReturnAdminDashboard[Kembali ke Halaman Admin Utama]

    RefreshUserTable & ShowNewPassNotice & ReturnAdminDashboard --> End([Selesai])
```

---

## 5. Rincian Antarmuka & Langkah Kerja Super Admin

1. Buka menu **Master Data** > **Pengguna** (`/master/users`).
2. **Menambahkan PIC Departemen Baru:**
   - Klik **Tambah Pengguna Baru**.
   - Masukkan Nama (misal: *Danu Setiawan*), Email, dan pilih Role **PIC Departemen**.
   - Pilih Departemen (misal: *Marketing & Branding*).
   - Simpan data akun.
3. **Menggunakan Fitur Login-As (Impersonasi):**
   - Pada baris staf yang ingin diperiksa, klik tombol **Login As**.
   - Sistem seketika mengalihkan sesi aktif Anda ke akun staf tersebut. Di sudut atas layar muncul pita kuning peringatan: *"Mode Impersonasi: Anda sedang login sebagai [Nama Staf]"*.
   - Anda dapat memeriksa pengajuan draft box mereka atau menyimulasikan pengalaman kerja mereka.
   - Klik **Kembalikan Akun Super Admin** untuk kembali ke sesi asal.
