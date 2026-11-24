# Modul: LAYOUT GUDANG 2D (putaran F)
Status: dibangun — menunggu verifikasi koordinator (uji browser) + ACC pemilik.

## Route (`routes/modules/warehouse-layout.php`, semua `role:admin,pic_gudang`)
| L7 | L13 | Catatan |
|---|---|---|
| GET /master/warehouses/layout | WarehouseLayoutController@index | menggantikan Placeholder |
| GET /api/warehouse/layout-data | @data | L7 terbuka untuk semua user login → kini admin+gudang; **tanpa slot** (hitungan slot agregat) |
| – | GET /api/warehouse/locations/{id}/slots | slot 1 rak + antrean box (baru); rak tanpa slot dibuatkan 100 slot (L7 melakukannya di layout-data) |
| – | GET /api/warehouse/layout-search?q= | cari slot/no. box/judul di server, maks 15 (L7 di browser atas 8.000 slot) |
| POST locations/store, {id}/update, {id}/delete, {id}/book, {id}/unbook | WarehouseLayoutObjectController | JSON `{success,message}`; aturan bisnis ditolak = 422 |
| – | POST locations/positions | geometri banyak objek, 1 request/1 transaksi (L7: 1 request per objek per drag) |
| POST {id}/slots/assign, unassign, toggle-active | WarehouseLayoutSlotController | slot dialamatkan `slot_id` (L7: sap/layer/nomor) |

## Aturan
- Objek Gudang GA/IT (`rack_code`/`room_sector` ∈ WarehouseMaster::ADMIN_ONLY) hanya admin: data disaring, slot/ubah/hapus/booking 403 untuk gudang.
- Batas departemen admin saja (`assigned_department_ids`, id pertama → `assigned_department_id`), seperti L7.
- Tambah rak → 100 slot standar (`WarehouseLayout::ensureSlots`, sama dengan seeder). Tambah ruang → gudang kode sama (firstOrCreate) dan ruang memakai gudang itu (fix: L7 memakai gudang pertama).
- Hapus rak berisi box ditolak (fix); hapus ruang yang punya master gudang = `Actions/Master/DeleteWarehouse` (raknya ikut terhapus FK cascade, box dijaga) dan hanya admin (fix: L7 gudang boleh).
- Booking: hanya rak; departemen harus diizinkan (rak, lalu ruang); UPDATE bersyarat `is_booked = 0` (fix: L7 menimpa booking orang lain). Booking mengisi `assigned_department_id` seperti L7.
- Penempatan slot (mode "Pilih Antrean Arsip"): Menunggu Verifikasi → `VerifyArchive` + `CheckinArchive` (slot dipilih); Booking Disetujui → `CheckinArchive`; Di Gudang → pindah slot (slot lama dikosongkan, counter dua rak — fix). Aturan dept/FAT = `CheckinArchive::ensureAllowed` (dibuat public static; `handle()` dapat parameter `?int $slotId`).
- Lepas slot: slot kosong, box tetap `in_warehouse` di rak (L7), counter dihitung ulang dari slot terisi.
- Saklar aktif slot: admin saja (L7).

## Konkurensi
- Geometri (drag/nudge/rotate/resize/rapatkan/ubah ukuran massal): **last-write per objek** seperti L7; nilai absolut, satu transaksi pendek untuk semua objek yang digeser.
- Form Edit: optimistic lock `expected_updated_at` (resolusi 1 detik) → 422 "sudah diubah user lain". Token diperbarui di klien setelah setiap simpan posisi.
- Slot & booking: UPDATE bersyarat di dalam transaksi IMMEDIATE.

## Front-end
`resources/views/master/warehouse-layout/{index + partials/toolbar,inspector,context-menu,object-modals,batch-modals,rack-modal}`; `public/css/pages/warehouse-layout.css` (link ke-5, hanya halaman ini); `public/js/pages/warehouse-layout{,-render,-pointer,-panel,-forms,-batch,-rack}.js` (≤ 200 baris, vanilla, tanpa Alpine pada data kanvas). Satu `<canvas>`, render sekali per requestAnimationFrame, pointermove diproses sekali per frame, simpan posisi di-debounce 350 ms. Tanpa shadow/blur/animasi: kedip pencarian L7 diganti garis tebal statis 3 dtk. Ukuran kanvas per browser (`wh_canvas_w/h`, kunci L7); default = cukup untuk semua objek (L7 950×750 memotong denah produksi 1170×957).

## Tidak dibangun / beda (usulan ASK)
- Mode slot "Input Dokumen Baru" (admin membuat arsip langsung di slot tanpa pengajuan/butir/scan) — melewati alur pengajuan & verifikasi; tunggu keputusan pemilik.
- Modal verifikasi di layout (tolak pengajuan): penolakan lewat halaman detail arsip; penempatan box "Menunggu ACC" = setujui + tempatkan dengan konfirmasi.
- Daftar box di panel rak + modal inspektor dokumen: diganti grid slot + tautan detail arsip.
- Snap saat resize, ?archive_id highlight (L7 dari pencarian desktop lama). `?rack_id=` / `?rack_code=` didukung.

## Tes
`tests/Feature/WarehouseLayout/{LayoutObjectTest,LayoutSlotTest}.php` (15 tes).
