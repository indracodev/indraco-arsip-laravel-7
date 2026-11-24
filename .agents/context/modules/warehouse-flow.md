# Modul: GUDANG (putaran D) — verifikasi, penomoran box, check-in rak, ubah status, label
Status: dibangun (putaran D) — menunggu verifikasi koordinator (uji browser) + ACC pemilik. Dashboard gudang dan layout 2D (slot klik) belum (putaran G/F).

## Route lama → baru (URL & nama L7 sama, `routes/modules/warehouse.php`)
| L7 | L13 | Akses |
|---|---|---|
| POST /archives/{a}/verify `archives.verify` | ArchiveVerificationController@verify (`Actions/Warehouse/VerifyArchive`) | `ArchivePolicy@processWarehouse` (gudang + admin); PIC 403 |
| POST /archives/{a}/checkin `archives.checkin` | @checkin (`CheckinArchive`) | idem |
| POST /archives/{a}/superadmin-status `archives.superadmin_status` | ArchiveStatusController (`ArchiveStatusRequest`, `ChangeArchiveStatus`) | idem (UI: admin semua status, gudang hanya baris pending_destruction, seperti L7) |
| GET /archives/{a}/print-sticker `archives.print_sticker` | ArchiveLabelController@sticker | idem (L7: PIC dept sendiri tanpa tombol — temuan #14) |
| GET/POST /archives/print-labels `archives.print_labels(_post)` | @batch (`ids`=1,2,3 / array / `archive_id`; tanpa ids: filter search/department_id/status) | idem |
| POST /archives/{a}/checkout `archives.checkout` | **tidak dibangun (404)** | ASK 2–3: satu jalur keluar = borrowings dispatch; 0 log checkout di L7 |

## Transisi
| Dari | Aksi | Ke | Efek |
|---|---|---|---|
| pending_verification | Setujui & Generate Box | approved_booked | nomor box (counter +1, di transaksi yang sama), `rejection_note` null, log ARCHIVE_VERIFY |
| pending_verification | Tolak & Minta Revisi (catatan wajib) | draft | `rejection_note`; PIC melihat "Perlu Revisi" + "Revisi & Ajukan"; log ARCHIVE_REJECT |
| selain pending_verification | verify | ditolak: "Pengajuan arsip ini sudah diproses (status: …). Muat ulang halaman." | counter tidak bergerak |
| approved_booked | Konfirmasi Check-in (rak wajib) | in_warehouse | slot kosong pertama → filled (UPDATE bersyarat), `current_box_count` +1, `warehouse_entry_logs`, nomor dibuat bila kosong, log ARCHIVE_CHECKIN |
| selain approved_booked | checkin | ditolak (L7 tidak cek status) | – |
| apa pun | Ubah Status → Draft (alasan wajib bila bukan draft) | draft | slot dibebaskan, count −1 (dari in_warehouse/approved_booked), lokasi dikosongkan, `rejection_note` = alasan, catatan "[d/m/Y H:i] …" di content_description |
| apa pun | → Antrean Verifikasi | pending_verification | ganti scan form / lampiran digital (opsional) |
| apa pun | → Tersimpan di Gudang (approved_booked juga diterima server) | in_warehouse | nomor manual (unik di box aktif) atau dibuat bila kosong; scan serah terima → `scan_approval_input`; dari pending_destruction: usulan pemusnahan terbuka → rejected |
| – | → Keluar / Antrean Pemusnahan / Dimusnahkan | ditolak validasi | lewat modul Penarikan / Pemusnahan (ASK 2–3) |

## Penomoran (`Services/NumberingService`)
- `generate()`: `UPDATE numbering_formats … RETURNING current_counter`, dipanggil di dalam transaksi IMMEDIATE penyimpanan; nomor yang dipegang box aktif dilewati (kondisi sama dengan partial UNIQUE `idx_archives_box_number_active`). `preview()` tanpa tulis (dipakai di tooltip tombol Setujui). `holder()` = validasi berpasangan untuk nomor manual.
- Bukti: 6 proses paralel × 10 nomor ke salinan DB → 60 nomor unik, berurutan 0014–0073, counter +60.

## Tampilan
- Panel di detail arsip: `views/archives/partials/show-side-gudang.blade.php` (Hub Verifikasi + modal tolak, Check-in + pilihan rak L7 "[Umum]/[Diizinkan ✓]/[Khusus: …]/[PENUH ⚠️] (Terisi n/kapasitas Box)", kartu "Ubah Status Workflow" + modal). Modal terbuka lagi bila validasi gagal (`public/js/pages/verify-panel.js`).
- Label: `views/archives/labels.blade.php` + `public/css/pages/labels.css` (mm, px L7 × 0,75 = pt) + `public/js/pages/label-print.js` (QR `js/vendor/qrcode.min.js` lokal, sama dengan L7, 76 px level M; tombol Format Stiker 10x10 / Form A5, Cetak Label, Kembali). Dibuka sebagai halaman (L7: modal iframe `?embed=1`; `?json` tidak dibangun).
- Belum: tombol Antrean Verifikasi / Di Gudang / ubah status / Cetak Label (batch) di **katalog** dan dashboard gudang (di luar lingkup putaran D; route sudah ada).

## Tes
`tests/Feature/Warehouse/{VerificationTest,CheckinTest,StatusAndLabelTest}.php` + `WarehouseFixtures.php` (19 tes).
