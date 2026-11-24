# Modul: AUTH
Status: dibangun (Fase 2) — UI sementara, desain di Fase 3

## Route lama → baru
| L7 | L13 | Catatan |
|---|---|---|
| `GET /login` (guest) `login` | sama | user yang sudah login → `/` (L7: ke `/home` yang tidak ada → 404) |
| `POST /login` `login.post` | sama | `AuthController@login` + `LoginRequest` |
| `POST /logout` `logout` | sama | |
| `GET /`, `/dashboard` `dashboard` | sama | sementara: halaman minimal (nama, role, logout, ganti password) |
| `POST /profile/change-password` `profile.change_password` | sama | `ChangePasswordController` |
| middleware `role:` (CheckRole) | `role:` → `EnsureUserHasRole` | admin selalu lolos |
| middleware web `UpdateUserPresence` | sama, di-throttle | |

## Aturan bisnis
- Login email + password (`required|email`, `required`), tanpa remember-me (L7 tidak punya checkbox). Tanpa rate limit (keputusan pemilik).
- Berhasil: `session()->regenerate()`, log `LOGIN` (AUTH, properties email/role/department), redirect `intended(/)` dengan flash `Selamat datang kembali, <nama>`.
- Gagal: pesan sama untuk email salah/tidak ada: `Kredensial email atau password yang dimasukkan salah.`; hanya email dikembalikan ke form; log `LOGIN_FAILED` (user_name = email, role `guest`, IP, user agent, properties `attempted_email`; password tidak pernah dicatat).
- Logout: log `LOGOUT`, logout, invalidate session, regenerate token, redirect `/login` flash info `Anda telah berhasil keluar dari sistem.`
- Hash bcrypt L7 (cost 10) valid. Laravel 13 me-**rehash** ke cost 12 saat login berhasil (`hashing.rehash_on_login`), 1 UPDATE per user sekali saja. Hash lama dan baru sama-sama dibaca L7.
- Role: `admin` lolos semua `role:` (eksplisit di middleware, bukan Gate::before — Policy per modul menentukan sendiri perlakuan admin, mis. aturan status). Role lain → 403 `Akses tidak diizinkan untuk peran pengguna Anda.`; tamu → `/login`.
- Presence: hanya GET halaman (bukan JSON/`api/*`) user login; tulis cache (file) paling sering 1×/60 dtk per sesi (cap waktu di session). L7: 3 file cache tiap 15 dtk. Data & key cache sama dengan L7 (`dms_presence_user_<id>`, `dms_presence_active_user_ids`) untuk monitor LAN (modul SISTEM).
- `ActivityLogger::log()` / `logBatch()` (chunk 200) — kolom & nilai sama dengan L7; gagal tulis log tidak menggagalkan request.

## Form
| Form | Field | Label | Wajib | Tipe | Default | Pesan |
|---|---|---|---|---|---|---|
| Login | email | NAMA PENGGUNA / EMAIL | ya | email | kosong (L7: `admin@indraco.com`, dihapus — temuan #1) | default Laravel (en, sama dengan L7) |
| Login | password | KATA SANDI / PASSWORD | ya | password | kosong (L7: `password`, dihapus) | |
| Ganti password | current_password | PASSWORD SAAT INI | ya | password | – | `Password saat ini wajib diisi.` / `Password saat ini yang Anda masukkan salah.` |
| Ganti password | password | PASSWORD BARU (Min. 6 Karakter) | ya | password, min 6, confirmed | – | `Password baru wajib diisi.` / `Password baru minimal 6 karakter.` / `Konfirmasi password baru tidak cocok.` |
| Ganti password | password_confirmation | ULANGI PASSWORD BARU | ya | password | – | – |
Sukses ganti password: flash `success` + `password_success` = `Password Anda berhasil diperbarui.`; request JSON → `{"status":"success","message":...}`; log `PASSWORD_CHANGE`.

## File L13
- `app/Http/Controllers/AuthController.php`, `ChangePasswordController.php`, `DashboardController.php` (sementara)
- `app/Http/Requests/LoginRequest.php`, `ChangePasswordRequest.php`
- `app/Http/Middleware/EnsureUserHasRole.php`, `UpdateUserPresence.php` (alias & web group di `bootstrap/app.php`)
- `app/Services/ActivityLogger.php`, `UserPresenceService.php` (`getConnectedUsers` menyusul di modul SISTEM)
- `resources/views/layouts/minimal.blade.php`, `auth/login.blade.php`, `dashboard.blade.php` (tanpa CDN, CSS inline kecil)

## Tes
- `tests/Feature/Auth/LoginTest.php`: tanpa prefill, login user L7 (hash cost 10) + regenerate session + log + rehash, salah password (pesan generik, log tanpa password, IP/UA), email tidak ada, logout, redirect tamu/user.
- `tests/Feature/Auth/ChangePasswordTest.php`: sukses, password lama salah, required/min/confirmed (pesan L7), JSON, tamu.
- `tests/Feature/Auth/RoleMiddlewareTest.php`: admin lolos semua, role tercantum lolos, lainnya 403, tamu → login.
- `tests/Feature/Auth/PresenceThrottleTest.php`: ≤ 1 tulis/60 dtk, JSON/POST tidak dicatat.
- `tests/Unit/UserAgentParserTest.php`: label perangkat/OS/browser sama dengan L7.

## Temuan & keputusan
- Temuan #1 (prefill) diperbaiki; #15 (admin lolos `role:`) dipertahankan eksplisit; #19 dikoreksi: presence L7 menulis **cache file**, bukan SQLite — tetap di-throttle 60 dtk.

## Sisa pekerjaan
- Desain halaman login & menu ganti password (Fase 3). Impersonate (modul USER). Middleware diagnostik (modul SISTEM).
