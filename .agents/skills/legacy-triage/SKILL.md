---
name: legacy-triage
description: Cara menilai kode, view, tabel, aset, library, dan skrip Laravel 7 DMS Indraco sebelum dibawa ke Laravel 13 — label KEEP/REWRITE/DROP/ASK dengan bukti, sumber pemanggil di luar route (klien desktop Tauri, launcher, widget LAN). Pakai sebelum membawa apa pun dari L7.
---

# Triage Laravel 7

## Label
| Label | Arti |
|---|---|
| KEEP | Dipakai, dibawa dengan sedikit perubahan |
| REWRITE | Perilaku dibutuhkan, implementasi baru |
| DROP | **Terbukti** tidak dipakai/digantikan (wajib bukti) |
| ASK | Bukti kurang → pertanyaan untuk pemilik |

Format baris `context/triage.md`:
`jenis | item | label | bukti | modul | risiko bila salah`

## Langkah
1. Route → middleware → controller → service/model → view → partial/JS/aset (`php artisan route:list` dengan PHP 8.2 portable L7).
2. Pemanggil front-end: `fetch(`, `axios`, `action=`, `href=`, `route('...')` di Blade/JS.
3. Pemanggil di luar route: klien desktop `desktop/` (Tauri — endpoint heartbeat/URL server), `START-CLIENT-WORKSTATION.bat`, launcher `.bat`, widget `lan_monitor`, command artisan, impersonate.
4. Bukti pemakaian nyata dari DB asli (read-only): `activity_logs` per action, jumlah & tanggal terakhir per tabel.
5. Library: `composer.json`, `package.json`, `public/js/vendor/*` — hitung pemakaian.

## Perangkap
- "Tidak dipanggil dari kode" ≠ mati: klien desktop dan launcher memanggil endpoint dari luar.
- Banyak dokumen `implementation_*.md`/`revisi_*.md` di root L7: berguna sebagai riwayat keputusan, bukan kode.
- Tes L7 (`tests/Feature/*`, ±93 tes) = sumber skenario parity.

## Verifikasi
- Setiap DROP punya bukti; setiap ASK menjadi pertanyaan di laporan STOP.
