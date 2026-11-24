# Modul SISTEM (putaran G)

Dashboard admin/gudang, Log History, Report + CSV, Pengaturan Sistem (logo, alert LAN, backup, hapus massal, git pull), Live Monitor LAN & User (diagnostik), notifikasi realtime + badge menu + popup jatuh tempo.

## Route (`routes/modules/system.php`, URL & nama L7)
| URL | Nama | Akses |
|---|---|---|
| `GET /`, `/dashboard` (web.php) | dashboard | admin/gudang (PIC → katalog) |
| `GET /api/realtime/check?since_seq=` / `?initial=1` | api.realtime.check | semua role |
| `GET /api/archives/expiring-list` | api.archives.expiring_list | semua role (PIC: dept sendiri) |
| `GET /logs` (?tab=activity/entry/borrowing/destruction) | logs.index | admin |
| `GET /reports?type=`, `/reports/print/{type}`, `/reports/export-csv/{type}` | reports.* | admin (temuan #6) |
| `GET /settings`, `POST /settings/update`, `POST /settings/reset-logo` | settings.index, master.settings.update/reset_logo | admin (#11) |
| `POST /master/settings/{backup-database,clear-logs,clear-box-allocations,clear-archives,git-pull-restart}` | master.settings.* | admin |
| `GET /diagnostics`, `/api/health/{metrics,probe-ip,logs}`, `POST /api/health/wal-checkpoint` | diagnostics.index, api.health.* | admin (#10: tanpa token `?token=`); probe-ip throttle 30/menit (#18) |

Tidak dibangun: `/reports/data/{type}` (halaman dirender server), `/api/settings/current` & `/api/health/connected-users` (0 pemanggil), dump `.sql`, `/master/settings/git-pull-log` (log tampil di halaman Pengaturan), live search dropdown dashboard (`/api/search-archives`, SHELL; form cari dashboard submit ke katalog). Modal baris dashboard L7 (verify/quick slot/checkout/status bebas) → modul GUDANG; baris dashboard link ke Detail.

## File
- Controller: `Dashboard`, `AuditLog`, `Report`, `Setting`, `SettingMaintenance`, `Diagnostics`, `Notification`.
- `Services/NotificationFeed` (aturan notifikasi L7, badge, daftar jatuh tempo), `SystemEventStream::latestId()/visibleTo()` (baca), `Services/ReportBuilder` + `ReportUsage` (7 laporan sebagai generator: baris di-yield, ringkasan = `getReturn()`), `Services/DatabaseBackup` (VACUUM INTO), `Services/LiveMonitor` (metrics + user online), `Actions/System/ClearSystemData`, `Support/ActivityLogLabel`.
- View: `dashboard/`, `logs/`, `reports/` (+ `public/css/pages/report-print.css`), `settings/`, `diagnostics/`, `layouts/partials/{notifications,expiry-modal}`; menu badge = atribut `data-nav-badge`/`data-count` (CSS `::after`).
- JS: `public/js/pages/notifications.js` (semua halaman app), `diagnostics.js`, `reports-print.js`. CSS: `components/stat.css`, `components/notify.css`.

## Aturan
- Notifikasi: poll 5 dtk, berhenti saat tab tersembunyi; kursor + daftar bel di `sessionStorage` per user (`dms_notify_<userId>_*`). Server yang memfilter: PIC hanya event dept sendiri (event tanpa dept tidak dikirim ke PIC); tidak pernah untuk aksi sendiri; `borrowing_created` hanya gudang/admin; `archive_submitted` (bukan draft) dari user lain. Poll idle = 1 query `MAX(id)`. Event `dms:realtime-sync` di window saat ada perubahan.
- Badge: `archives.index` = pending_verification (scope dept PIC), `borrowings.index` = requested/approved (+dept_approved gudang), `destructions.index` = jumlah Form 3 H-30.
- Popup jatuh tempo: aturan L7 **H-30 dari akhir bulan expiry** (bukan 90 hari), sekali per sesi lalu tiap 1 jam.
- B10: `system_events` dipangkas di `SystemEventStream::insert` (simpan 2000 terakhir, tiap kelipatan 200) — diuji.
- Backup unduhan: `storage/app/backup-tmp`, dihapus setelah dikirim. Backup otomatis (sebelum hapus massal dan sebelum git pull): `storage/app/backups/` (tidak disajikan web; tanpa pembersihan otomatis).
- Hapus massal: modal konfirmasi + `confirmed=1`; arsip juga wajib ketik `RESET` (pengganti `prompt()` L7); backup gagal → tidak ada yang dihapus.
- Logo: png/jpg saja (tipe dari isi file), nama acak, `public/uploads/logo`; reset = hapus baris `app_logo` (default `images/logo-indraco.png`). Font global dihapus (font per user).
- Alert LAN: `ping_alert_enabled` → atribut `data-alert` di titik LAN; `notifications.js` memunculkan toast saat status jadi lambat/terputus.
- CSV: BOM UTF-8, sel berawalan `= + - @ \t \r` diberi `'` (kecuali "-" tunggal), streaming per baris (`lazy()`).
