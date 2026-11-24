---
trigger: model_decision
description: Saat menulis route, controller, request, upload/stream file, auth, output HTML, ekspor, atau konfigurasi server
---

# Security (OWASP Top 10, internal LAN)

Setiap kelas di bawah punya status dan bukti di `context/security-checklist.md`, walaupun hasilnya "tidak berlaku". Temuan dicatat di `context/findings.md` dan diperbaiki.

| Kelas | Aturan |
|---|---|
| SQL injection | Binding/Eloquent; daftar kolom yang diizinkan untuk sort/filter |
| XSS | `{{ }}` default, `@js()` untuk data ke JS, tanpa `innerHTML` dari input |
| CSRF | Semua POST/PUT/PATCH/DELETE pakai token |
| IDOR | PIC Departemen hanya bisa membaca/mengubah data departemennya; cek di Policy, bukan di view |
| Privilege escalation | Middleware role + Policy di setiap route admin/gudang |
| Mass assignment | Hanya data tervalidasi; `role`, `status`, `department_id`, nomor box tidak bisa diisi bebas dari form |
| Upload | Satu service upload: cek MIME dari isi, batas ukuran, whitelist ekstensi, nama file dari server, disimpan di luar `public/` dan disajikan lewat controller yang memeriksa hak akses |
| Path traversal | Path file tidak dibentuk dari input tanpa validasi (perhatikan route `files/stream/{path}` lama) |
| Brute force | Rate limit login dan ganti password; pesan error tidak membedakan akun ada/tidak |
| Session | Regenerate saat login; cookie `httponly`, `samesite=lax` (LAN http, jadi tanpa `secure`) |
| CSV/Excel formula injection | Escape nilai berawalan `=`, `+`, `-`, `@` pada ekspor |
| Kebocoran info | `APP_DEBUG=false`, route debug/diagnostik hanya admin, header versi disembunyikan (`expose_php=Off`, header `Server` Caddy) |
| File sensitif | Hanya `public/` yang disajikan Caddy; `.env`, `.git`, `storage`, `vendor`, `evironment`, `.agents` tidak bisa diakses lewat HTTP (dibuktikan dengan request) |
| Header | `X-Content-Type-Options`, `X-Frame-Options`/frame-ancestors, `Referrer-Policy`, `Permissions-Policy`, CSP (mulai Report-Only) |
| Komponen rentan | `composer audit` dan `npm audit` bersih high/critical |
| Logging | Log kejadian keamanan tanpa password/token |
| Impersonate (L7 punya `impersonate/leave`) | Hanya admin, tercatat di log, tidak bisa dipakai naik hak akses |

Sebelum cutover: audit keamanan menyeluruh, temuan critical/high = 0.
