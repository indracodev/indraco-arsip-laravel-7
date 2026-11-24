# Modul: USER (kelola user & hak akses, reset password, impersonate)
Status: dibangun (putaran E), menunggu verifikasi koordinator

## Route (URL & nama L7; `routes/modules/master.php`)
| Route | Controller | Akses |
|---|---|---|
| `GET/POST /master/users`, `PUT/DELETE /master/users/{u}` | `UserController` | admin |
| `POST /master/users/{u}/reset-password` (form & JSON dari Kelola Departemen) | `UserController@resetPassword` | admin |
| `POST /master/users/{u}/impersonate` | `ImpersonateController@start` | admin |
| `POST /impersonate/leave` | `ImpersonateController@leave` | siapa saja yang login; hanya bertindak bila sesi punya `impersonator_id` |

## Aturan
- Form sama L7: nama, email, password (tambah: default `password` terisi seperti L7; edit: kosong = tidak diubah), role (3 pilihan), departemen (wajib bila PIC Departemen), telepon. Min password 6.
- Email dinormalisasi `User::normalizeEmail()`; unik dicek `lower(trim(email))` (sama dengan UNIQUE index R4).
- `role`/`department_id` di-set eksplisit (bukan mass assignment); hash password tidak pernah dikirim ke halaman/JSON.
- Reset password: preset `indraco123` + acak 8 karakter (crypto.getRandomValues); log `USER_PASSWORD_RESET` tanpa password.
- Beda (perbaikan): admin tidak bisa mengubah role akunnya sendiri; hapus user ditolak bila user punya arsip/penarikan/pemusnahan/perpanjangan/entri gudang (skema `ON DELETE CASCADE` akan menghapus data arsip — L7 menghapusnya diam-diam).
- Impersonate (ASK 6): admin saja, tidak untuk diri sendiri dan **tidak untuk akun admin lain** (beda L7), log `IMPERSONATE_START/LEAVE`, session id diregenerasi tiap pindah akun, banner "Mode Impersonasi Aktif … Kembali ke SuperAdmin" di layout (`users/partials/impersonation-banner`), leave hanya kembali ke akun yang masih admin (bila bukan: logout).

## File
`app/Http/Controllers/{User,Impersonate}Controller.php`, `app/Http/Requests/UserRequest.php`, `app/Actions/Master/DeleteUser.php`, `resources/views/users/{index,partials/forms,partials/impersonation-banner}.blade.php`, `public/js/pages/user-manager.js`; satu baris include di `resources/views/layouts/app.blade.php`.

## Tes
`tests/Feature/User/{UserManagementTest,ImpersonateTest}.php`.
