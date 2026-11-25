# Security checklist (OWASP Top 10 2021/2025) — DMS PT Indraco L13

Audit: 2026-11-25, branch `laravel-13-rebuild`, server hidup `http://127.0.0.1:8000` / `https://127.0.0.1:8443` (Caddy 2.11.9, PHP 8.4.28, Laravel 13.43.0).
Status: **OK** | **temuan Sx** (lihat `findings.md` → "Audit keamanan (2026-11-25)") | **diterima pemilik** | **ditunda (decisions)**.
Temuan critical/high: **0**. Temuan baru: S1, S2, S3, S5 (diperbaiki), S4 (ditunda).

## Risiko diterima pemilik (tidak diubah)
| Risiko | Sumber |
|---|---|
| Password default/mirip (`password`) dibiarkan, tanpa paksaan ganti; minimal 6 karakter (paritas L7) | decisions 2026-11-24 |
| Tanpa rate limit login (login gagal tetap dicatat `LOGIN_FAILED`, tanpa password) | decisions 2026-11-24 |
| Laravel 7 tidak di-hotfix sampai cutover (termasuk findings #2) | decisions 2026-11-24 |
| LAN-only, tanpa SEO | decisions 2026-11-24 |

## A01 Broken Access Control — OK (S1 diperbaiki)
Bukti otomatis: `SecurityAuditTest::test_every_route_except_login_and_health_requires_login` — dari 127 route hanya `login`, `login.post`, `health.ping`, `health.ready` tanpa `auth`. Tanpa route vendor (`route:list --only-vendor` kosong; disk `serve => false`, `links => []`).

| Kelompok route (file) | Penjaga | Bukti tes |
|---|---|---|
| `/login` GET/POST (web.php) | `guest` | `LoginTest` |
| `/api/health/ping`, `/ready` (api.php) | publik (kontrak launcher/klien exe; ping dijawab Caddy; `ready` hanya `{status,pid}`) | `PingContractTest` |
| `/`, `/dashboard` | `auth`; semua hitungan lewat `Archive::visibleTo` | `SystemAccessTest` |
| `/profile/*` | `auth`, hanya akun sendiri | `ChangePasswordTest`, `FontSizeTest` |
| `/archives` index, `{a}` show/edit/update/delete, form-pengajuan | `auth` + `visibleTo` / `ArchivePolicy` view·update·delete (dept + status draft) | `CatalogTest`, `SubmissionAccessTest`, `FormPrintTest` |
| `/archives/create`, store, batch-delete-drafts, check-similarity | `role:pic_dept` + `ArchivePolicy@create`; dept PIC dipaksa di server | `SubmissionAccessTest` |
| `/files/{source}/{id}/{field}[/download|/preview-data]` | `auth` + `ArchivePolicy@view` arsip induk; field whitelist; path dari DB divalidasi `FileStorage::locate` | `ArchiveFileTest` (11 tes) |
| `/borrowings*` | `BorrowingLogPolicy` create (dept) / process (gudang+admin); list `visibleTo` | `BorrowingRequestTest`, `BorrowingProcessTest` |
| warehouse.php (verify, checkin, superadmin-status, label/sticker) | `ArchivePolicy@processWarehouse` (FormRequest `authorize` untuk status) | `VerificationTest`, `CheckinTest`, `StatusAndLabelTest` |
| retention.php (propose, extend, BAP, print, approve/reject/complete/upload-bap) | `DestructionLogPolicy` / `ArchiveExtensionLogPolicy` + cek dept per box; BAP & extend-print = `ArchivePolicy@view` | `DestructionProposalTest`, `DestructionProcessTest`, `ExtensionTest` |
| archive-import.php | `role:pic_dept` + `create`; dept dari server (parse ulang file) | `ImportPreviewTest`, `ImportCommitTest` |
| system.php: logs, reports, settings, backup, clear, git-pull, diagnostics, metrics, probe-ip (`throttle:30,1`), WAL | `role:admin` | `SystemAccessTest`, `MaintenanceTest` |
| system.php: `/api/realtime/check`, `/api/archives/expiring-list` | `auth`, difilter per dept (`SystemEventStream::visibleTo`, `visibleTo`) | `NotificationTest` |
| warehouse-layout.php | `role:admin,pic_gudang` + objek admin-only dicek per objek | `LayoutObjectTest`, `LayoutSlotTest` |
| master.php (departemen, sub, master-archives `scopeBindings`, numbering, users, reset-password, impersonate, import master, api/departments/*) | `role:admin` | `DepartmentTest`, `UserManagementTest`, `MasterDataImportTest` |
| master.php gudang & rak (index/store) | `role:pic_gudang`; edit/hapus/aktif admin | `WarehouseMasterTest` |
| `/impersonate/leave` | `auth` + `impersonator_id` di sesi, kembali hanya ke akun yang masih admin; regenerate sesi | `ImpersonateTest` |

- Mass assignment: `role`, `department_id`, `status`, `box_number`, `approval_status`, `current_counter` tidak ada di `$fillable` (User.php:43, Archive.php:59, *Log.php); diisi `forceFill` eksplisit. OK.
- **S1** (diperbaiki): PIC tanpa departemen (setelah "Lepas PIC" / hapus departemen) — `where('department_id', null)` menjadi `whereNull` → event realtime tanpa departemen ikut terlihat. Perbaikan `?? 0` di `Archive::visibleTo` dan `SystemEventStream::visibleTo`; tes `SecurityAuditTest::test_pic_without_department_sees_no_archive_and_no_department_less_event` (gagal tanpa perbaikan).
- Open redirect: hanya `redirect()->intended()` (URL sesi, host sama) dan `route()`; `from` di penarikan whitelist (`BorrowingController.php:46,66`). OK.

## A02 Cryptographic Failures — OK / ditunda (decisions)
- `APP_KEY` terisi, `APP_ENV=production`, `BCRYPT_ROUNDS=12`, cast `hashed` di User. OK.
- Cookie sesi: `httponly; samesite=lax` (curl `Set-Cookie` di :8000 dan :8443); `XSRF-TOKEN` tanpa httponly (memang dibaca JS). OK.
- HTTPS: Caddy `tls internal` :8443 (sertifikat hanya nama di `DMS_TLS_SITES`, tanpa on-demand). Cookie `secure`, redirect ke HTTPS dan HSTS **ditunda (decisions 2026-11-24 HTTPS LAN)** sampai semua klien memercayai root CA; HSTS juga diabaikan browser untuk host berupa IP.

## A03 Injection — OK
- SQL: semua `whereRaw/selectRaw/orderByRaw/DB::statement` memakai literal atau binding (`?`): SubmitArchiveBoxes:135, CompleteDestruction:75, ArchiveImport:184-198, ArchiveSimilarity:30, NotificationFeed:106, ReportUsage:127, DatabaseBackup:31 (`VACUUM INTO ?`, path internal). Sort katalog whitelist `ArchiveCatalog::SORTS` + `Rule::in` (ArchiveCatalogRequest:27-28), arah `asc|desc`.
- Blade `{!! !!}`: hanya `components/icon.blade.php:26` (path SVG statis) dan `destructions/bap.blade.php:85` (caption literal). Tanpa `x-html`, `innerHTML`, `insertAdjacentHTML`, `document.write` di `resources/views` dan `public/js` (selain vendor).
- Perintah OS: `probe-ip` `filter_var(FILTER_VALIDATE_IP)` + `escapeshellarg` (admin); git-pull memanggil path tetap.
- CSV/Excel formula injection: `ReportBuilder::csvCell` (awalan `= + - @ \t \r`) — `ReportTest::test_csv_cells_starting_with_a_formula_character_are_escaped`; template impor CSV `ArchiveImportTemplate:47`. Tidak ada ekspor XLSX berisi data user.

## A04 Insecure Design — OK
- Status workflow dicek di dalam `DB::transaction` (28 Action), optimistic lock form, nomor box atomik. Tes: `SubmissionAccessTest::test_the_same_submission_sent_twice_creates_the_boxes_once`, `…draft_twice_is_refused`, `…optimistic_lock_refuses_a_stale_form`, `BorrowingProcessTest::test_dispatch_is_refused_twice_and_after_reject`, `DestructionProposalTest::test_double_submit_creates_one_log`, `CheckinTest::test_slots_are_never_double_booked` (R34, R35).

## A05 Security Misconfiguration — OK (S3, S5 diperbaiki; S4 ditunda)
- `.env`: `APP_DEBUG=false`; `php-dms.ini`: `expose_php=Off`, `display_errors=Off`. 404 memakai halaman aplikasi tanpa trace; tanpa `X-Powered-By`/`Server`.
- Header (curl `/login` :8000 dan :8443): `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()`, `Cache-Control: no-store, private` (R50), CSP **Report-Only** (S4).
- File sensitif lewat HTTP (curl, semua **404**): `/.env`, `/.git/HEAD`, `/.git/config`, `/composer.json`, `/composer.lock`, `/artisan`, `/storage`, `/storage/logs/laravel.log`, `/storage/app/private`, `/vendor/autoload.php`, `/database/database.sqlite`, `/evironment/php-dms.ini`, `/.agents/AGENTS.md`, `/AGENTS.md`, `/Caddyfile`, `/routes/web.php`, `/server.php`, `/phpinfo.php`, `/.htaccess`, `/private/archive_scans/x.pdf`, `/legacy-public/x.pdf`, `/%2e%2e/.env`, `/css/..%2f..%2f.env`, `/index.php/../.env`. Directory listing `/css/`, `/js/`, `/images/` → 404.
- PHP hanya `public/index.php` (Caddy root `public`, `php_fastcgi` → index.php). Upload di `storage/app/private`, nama acak + ekstensi dari isi; disajikan Caddy `X-Accel-Redirect` (hanya header respons upstream) dengan `Content-Type` dari whitelist, `nosniff`, `Content-Disposition` inline/attachment (`ArchiveFileController`). Logo di `public/uploads/logo` hanya png/jpg dari isi file (#12).
- **S3** (diperbaiki): Host divalidasi `trustHosts` (bootstrap/app.php, `config('app.allowed_hosts')`: localhost, 127.0.0.1, [::1], nama PC, `SERVER_LAN_IP`, `DMS_ALLOWED_HOSTS`). curl :8000 `-H "Host: evil.example:8000" /` → **400** (sebelumnya `302 http://evil.example:8000/login`); `127.0.0.1`, `192.168.3.49` (DMS_ALLOWED_HOSTS laptop dev) → 302 ke `/login` host sendiri; `/api/health/ping` dengan Host asing → 200 (dijawab Caddy). Tes `SessionSecurityTest::test_only_the_servers_own_host_names_are_answered`.
- **S5** (diperbaiki): body terbesar sah = impor Excel 5 MB + scan 500 KB. `post_max_size`/`upload_max_filesize=20M` (php-dms.ini, PROJECT_RULES) → Laravel `PostTooLargeException` = halaman `errors/413` / JSON `message` bahasa Indonesia; Caddy `request_body max_size 24MB` + `handle_errors 413` halaman HTML Indonesia. Diuji Caddy+php-cgi terpisah (port uji): POST 22 MB → 413 halaman Laravel / JSON, 30 MB → 413 halaman Caddy, kecil → 419 (CSRF, normal). Tes `SessionSecurityTest::test_body_above_post_max_size_gets_an_indonesian_413`.

## A06 Vulnerable Components — OK
- `composer audit` (evironment/composer.phar): "No security vulnerability advisories found". Laravel 13.43.0.
- Alpine.js 3.17.5 lokal (`public/js/vendor/alpine.min.js`), qrcode lokal; tanpa CDN, tanpa npm di server. Caddy v2.11.9, PHP 8.4.28.

## A07 Identification & Authentication Failures — OK (S2 diperbaiki)
- Session fixation: `session()->regenerate()` saat login (`LoginTest::test_laravel7_user_can_log_in_and_the_session_is_regenerated`) dan setiap impersonate/leave. Logout: `invalidate()` + `regenerateToken()` (`LoginTest::test_logout_ends_the_session`).
- Pesan login sama untuk email ada/tidak (`…unknown_email_gets_the_same_error`); form tanpa prefill (`…no_prefilled_credentials`). Tanpa remember-me.
- CSRF: Laravel 13 `PreventRequestForgery` di grup web; curl `POST /login` tanpa token → **419**, `Sec-Fetch-Site: cross-site` / `same-site` → **419**, `same-origin` → lolos (header hanya diset browser). Semua POST ada di grup web (api.php hanya GET health).
- Sesi: file, `SESSION_LIFETIME=120` menit (geser), cookie terenkripsi.
- **S2** (diperbaiki): `AuthenticateSession` di grup web (sesi file; hash password di sesi). Ganti password sendiri → sesi itu tetap masuk, sesi lain akun itu ke `/login` pada request berikutnya; reset admin → semua sesi akun itu berakhir. Presence (`UpdateUserPresence`) berjalan sesudahnya, tidak berubah (`PresenceThrottleTest`). Tes `SessionSecurityTest` (3 tes S2, gagal tanpa middleware).

## A08 Software & Data Integrity Failures — OK
- Upload: `FileStorage::rules()` `mimes` + `extensions` pdf/jpg/jpeg/png + `max:500`; `store()` cek ulang MIME dari isi (finfo), ekstensi klien, ukuran; nama server acak (`SubmissionUploadTest`: php, `scan.pdf.php`, php sebagai pdf, html sebagai png ditolak; > 500 KB ditolak). Tanpa SVG/HTML.
- Pratinjau base64 JSON maks 5 MB (`…preview_data_refuses_files_above_the_cap`).
- Impor: maks 5 MB, isi harus cocok ekstensi (`ArchiveImportRequest`), XML part maks 30 MB tak terkompres (`SpreadsheetReader::MAX_PART_BYTES`, anti zip bomb), SimpleXML tanpa `LIBXML_NOENT` (tanpa XXE); server parse ulang file saat commit (R37). Impor master: admin, 5 MB, xlsx/csv.
- Update dari web (git pull): admin + konfirmasi + backup + log (#9).

## A09 Logging & Monitoring — OK
- `activity_logs` mencatat LOGIN, LOGIN_FAILED (email, IP, UA; tanpa password), LOGOUT, PASSWORD_CHANGE, USER_* (termasuk reset password, tanpa nilai password), IMPERSONATE_START/LEAVE, semua aksi arsip/penarikan/retensi/master, backup/clear/git-pull/pengaturan (±147 titik `ActivityLogger::log`). `ActivityLogger` melarang password/token di properties.
- Log Laravel `daily` 14 hari, level warning; Caddy `roll_size` 10/20 MiB × 3/5; `system_events` dipangkas ke 2000 (B10). Penolakan 403 tidak dicatat (cukup untuk LAN).

## A10 SSRF — OK (tidak berlaku)
- Tanpa HTTP keluar dari server (grep `Http::`, `curl_init`, `file_get_contents('http`, Guzzle: kosong). `probe-ip` hanya admin, IP tervalidasi, port tetap, throttle. Cek rilis GitHub ada di klien Tauri (R40).

## Lain-lain
| Butir | Status | Bukti |
|---|---|---|
| Path traversal file | OK | `FileStorage::locate` regex `<folder whitelist>/<nama polos>`, `realpath` + prefix; `ArchiveFileTest::test_stored_paths_outside_the_upload_folders_are_never_served`, `…tampered_urls_are_404` |
| Clickjacking | OK | `X-Frame-Options: SAMEORIGIN` (dipaksa); `frame-ancestors 'self'` di CSP Report-Only |
| Upload SVG/HTML | OK | ditolak (`SubmissionUploadTest`, `MaintenanceTest::test_logo_accepts_png_and_rejects_svg`) |
| Laju penyajian file | OK | wajib login; Caddy X-Accel melepas worker; preview-data ≤ 5 MB |
| PWA service worker | OK | `public/sw.js` scope `/`, hanya cache `/css|js|images` ber-`?v=` + `offline.html`, tidak pernah HTML/API/scan; navigasi selalu ke server |
| Impersonate | OK | admin-only, bukan admin lain, tercatat, regenerate sesi (`ImpersonateTest`) |
| Diagnostik | OK | admin-only, token `?token=` dihapus (#10) |

## Tindak lanjut
- S2/S3/S5 diperbaiki 2026-11-25. Perubahan `Caddyfile` dan `php-dms.ini` (S5) baru berlaku setelah server di-restart (`START-DMS-INDRACO.bat`); S2/S3 (PHP + .env) langsung berlaku. S4 ditunda (decisions).
- Catatan uji: probe CSRF `POST /login` dengan `Sec-Fetch-Site: same-origin` menulis satu baris `LOGIN_FAILED` (email `x@x`) di DB server uji.
