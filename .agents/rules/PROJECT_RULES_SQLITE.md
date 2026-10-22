# DMS PT INDRACO — Project Rules (Bagian 2/3): SQLite Concurrency, Integritas Data & Aturan Kerja Agent

> Bagian dari [`PROJECT_RULES.md`](PROJECT_RULES.md) — indeks dokumen & Performance Gate Checklist (§9) ada di file utama.

---

## 3. Layer 2: SQLite Concurrency & I/O Performance

Aplikasi menggunakan SQLite (`database/database.sqlite`) untuk portabilitas tanpa dependensi database server eksternal. Karena SQLite menggunakan file-level locking, optimasi concurrency dan I/O adalah prioritas mutlak.

### 3.1. PRAGMAs Wajib pada Booting Koneksi
File: [`app/Providers/AppServiceProvider.php`](file:///c:/laragon/www/indraco-arsip-laravel-7/app/Providers/AppServiceProvider.php#L31-L45)  
Event: `ConnectionEstablished`

```php
Event::listen(ConnectionEstablished::class, function ($event) {
    if ($event->connection->getDriverName() === 'sqlite') {
        try {
            $event->connection->statement('PRAGMA journal_mode = WAL;');
            $event->connection->statement('PRAGMA busy_timeout = 5000;');
            $event->connection->statement('PRAGMA synchronous = NORMAL;');
            $event->connection->statement('PRAGMA temp_store = MEMORY;');
            $event->connection->statement('PRAGMA cache_size = -32000;');
            $event->connection->statement('PRAGMA mmap_size = 67108864;');
        } catch (\Exception $e) {
            // Fallback gracefully
        }
    }
});
```

### 3.2. Penjelasan Teknis PRAGMA
1. **`journal_mode = WAL` (Write-Ahead Logging)**:
   - Mengubah mekanisme locking dari rollback journal ke WAL.
   - **Multi-Client Concurrency**: Readers tidak memblokir writers, dan writer tidak memblokir readers. Ini esensial saat banyak user LAN mengakses arsip bersamaan.
2. **`busy_timeout = 5000`**:
   - Jika write lock sedang digunakan transaksi lain, SQLite menunggu hingga 5.000 ms sebelum melemparkan error `SQLITE_BUSY` (500 Server Error).
   - **Pengecualian**: `busy_timeout` **TIDAK berlaku** saat transaksi `DEFERRED` (default Laravel `DB::transaction()`) yang diawali query baca mencoba upgrade ke tulis di mode WAL — langsung gagal `SQLITE_BUSY` tanpa menunggu. Lihat 3.5.d.
3. **`synchronous = NORMAL`**:
   - Hanya melakukan disk sync (`fsync`) pada titik WAL checkpoint. Menghasilkan peningkatan kecepatan write 10x-50x dibanding mode `FULL` tanpa risiko korupsi database pada aplikasi desktop.
4. **`temp_store = MEMORY`**:
   - Temporary tables, indeks sementara, dan sorting (`ORDER BY`, `GROUP BY`) disimpan di RAM host, bukan di disk. Mengurangi disk I/O hingga 80%.
5. **`cache_size = -32000`**:
   - Mengalokasikan 32 MB SQLite page cache di RAM host.
6. **`mmap_size = 67108864`**:
   - Mengalokasikan 64 MB memory-mapped I/O. Pembacaan halaman database langsung melewati buffer kernel OS tanpa overhead `read()` syscall.

### 3.3. Aturan Query & Eloquent untuk Fitur Baru
- **Wajib Eager Loading**: **DILARANG KERAS** membiarkan N+1 queries. Selalu gunakan `Model::with(['relasi1', 'relasi2'])` pada data relasional.
- **Indexing Mandatory**: Setiap foreign key (`arsip_id`, `user_id`, `kategori_id`) dan kolom filter/pencarian (`nomor_surat`, `tgl_terima`, `status`) **WAJIB** memiliki index pada migrasi database.
- **Dilarang Long-Running DB Transaction**: Blok `DB::transaction(...)` HANYA boleh berisi query database esensial. **DILARANG** melakukan pemrosesan file, pembuatan PDF, atau komputasi berat di dalam blok transaksi karena akan menahan write lock SQLite.
- **Paging / Chunking**: Dilarang memanggil `->get()` tanpa batas pada tabel transaksi. Gunakan `paginate(20)` atau `chunk(100)` untuk background processing.
- **Global View Composer Caching**: Jika sebuah data dibagikan ke banyak Blade view (seperti `AppSetting`), data **WAJIB di-cache di in-memory static variable** (lihat implementasi di [`AppServiceProvider.php`](file:///c:/laragon/www/indraco-arsip-laravel-7/app/Providers/AppServiceProvider.php#L48-L75)) agar tidak query ke database setiap kali Blade me-render partial view.

### 3.4. Standar Batch Insert & Chunking (SQLite Limit Safety)
SQLite versi 3.32+ memiliki limit bind parameter sebesar 32.766 (`SQLITE_LIMIT_VARIABLE_NUMBER`).
1. **Wajib Batch Insert**: Setiap operasi yang menyimpan banyak baris relasional (seperti butir berkas arsip `ArchiveItem`, master arsip batch `MasterArchive`, atau slot rak gudang `WarehouseRackSlot`) **WAJIB** menggunakan `Model::insert($array)` — **DILARANG** melakukan loop `create()` berulang.
2. **Aturan Chunking**: Jika data berpotensi lebih dari 200 baris, bagi ke dalam chunk 200-300 baris sebelum insert:
   ```php
   foreach (array_chunk($rowsToInsert, 250) as $chunk) {
       Model::insert($chunk);
   }
   ```
3. **High-Performance Batch Logger**:
   Untuk pencatatan log aktivitas dalam jumlah banyak, gunakan `ActivityLogger::logBatch($entries)` yang otomatis mengeksekusi chunk insert dan json serialization tanpa membebani disk sync.

### 3.5. Integritas Data & Konkurensi Multi-User (Multi-Device LAN)
Beberapa PC klien menulis ke satu file SQLite melalui 4 PHP worker. Setiap pola "baca di PHP → tulis balik" **WAJIB** dianggap race condition.

**a. Counter / Penomoran WAJIB Atomik**
- **DILARANG** membaca `$model->counter + 1` lalu `increment()`/`update()` secara terpisah.
- **DILARANG** memakai `Model::count() + 1` untuk nomor unik.
- **DILARANG** menyimpan counter dengan nilai absolut dari PHP (`update(['current_counter' => $n])`), karena counter bisa mundur saat request lain sudah menaikkannya.
- Gunakan satu statement atomik (SQLite ≥ 3.35, runtime saat ini 3.53). Referensi: [`NumberingService::generateBoxCode`](file:///c:/laragon/www/indraco-arsip-laravel-7/app/Services/NumberingService.php).
  ```php
  $counter = (int) DB::selectOne(
      'UPDATE numbering_formats SET current_counter = current_counter + 1 WHERE id = ? RETURNING current_counter',
      [$format->id]
  )->current_counter;
  ```

**b. Preview DILARANG Punya Side-Effect**
- Fungsi penghasil nomor yang dipanggil hanya untuk tampilan **WAJIB** memakai mode preview: `generateBoxCode($archive, true)`.
- *Pelajaran*: halaman Master Penomoran sempat membakar satu nomor counter setiap kali dibuka.

**c. UNIQUE Index di DB + Validasi Aplikasi Berpasangan**
- Kolom yang harus unik **WAJIB** punya UNIQUE index. Gunakan partial index jika keunikan hanya berlaku untuk data aktif, contoh `idx_archives_box_number_active ... WHERE status NOT IN ('taken','destroyed','borrowed')`.
- Setiap input manual kolom tersebut **WAJIB** divalidasi dengan daftar status yang **sama**, supaya user menerima pesan 422/validasi, bukan 500 dari constraint. Titik input manual `box_number`: `ArchiveStatusController::superAdminUpdateStatus` dan `HandlesWarehouseLayoutSlots`.
- Sebelum memasang UNIQUE index, **WAJIB** cek duplikat yang sudah ada (`SELECT col, COUNT(*) c FROM t GROUP BY col HAVING c > 1`). Migrasi **DILARANG** gagal diam-diam, dan dedupe **DILARANG** dilakukan otomatis tanpa keputusan user.

**d. Transaksi Baca→Tulis Pakai `db_immediate_transaction()`**
- Gunakan helper di [`app/helpers.php`](file:///c:/laragon/www/indraco-arsip-laravel-7/app/helpers.php), yang langsung mengambil write lock lewat `BEGIN IMMEDIATE` sehingga `busy_timeout` dipatuhi.
- **DILARANG** memanggil `DB::transaction()`/`DB::beginTransaction()` atau service yang membuka transaksi (mis. `MultiBoxExcelService`) di dalam callback-nya. Helper memakai raw `BEGIN`, jadi Laravel tidak tahu ada transaksi aktif dan nested transaction akan error.
- **DILARANG** menulis `DB::statement('BEGIN ...')` manual di tempat lain.
- `lockForUpdate()` tidak berefek di SQLite — **DILARANG** dijadikan satu-satunya proteksi.

**e. Anti Double-Submit**
- Aksi pengajuan (peminjaman/penarikan, pemusnahan) **WAJIB** punya:
  1. Guard server-side: cek status aktif + insert di dalam satu `db_immediate_transaction()`.
  2. Tombol submit yang di-disable dan menampilkan spinner saat diproses (frontend).
- Guard melempar exception khusus (`\DomainException`), dan hanya exception itu yang di-catch.
- **DILARANG** memakai `catch (\Exception)` generik yang meneruskan pesan error DB/SQL ke user.

**f. Optimistic Locking pada Form Edit**
- Form edit record yang bisa diedit beberapa user **WAJIB** mengirim `<input type="hidden" name="original_updated_at">`.
- Controller menolak jika `updated_at` di DB lebih baru, dengan `back()->withInput()->with('error', ...)` agar isian tidak hilang. Tidak ada merge otomatis.
- Batasan yang diketahui:
  - Presisi hanya per detik.
  - Edit yang hanya mengubah child (`archive_items`) tanpa menyentuh induk tidak terdeteksi, kecuali induknya di-`touch()`.

**g. Backup WAJIB Snapshot Konsisten**
- Gunakan `VACUUM INTO '<path>'`. Path target **belum boleh ada**, dan file dikirim dengan `response()->download(...)->deleteFileAfterSend(true)`.
- Alternatif: dump SQL di dalam **satu** transaksi baca.
- **DILARANG** dump tabel satu per satu tanpa snapshot.

### 3.6. Aturan Kerja Agent (Line Ending & Verifikasi)
- **DILARANG** mengonversi line ending satu file penuh (mis. skrip Python CRLF→LF).
  - Cek dulu dengan `git ls-files --eol <file>`; file `-text`/CRLF **WAJIB** tetap CRLF.
  - Sebelum commit, `git diff --stat` hanya boleh menunjukkan baris yang benar-benar diubah.
- Klaim "test lulus" **WAJIB** disertai output asli `vendor/bin/phpunit`. Baseline saat ini: **93 tests, 616 assertions**.
- Fitur yang dalam plan sudah diputuskan **tidak** dibuat **DILARANG** ditambahkan tanpa persetujuan user (contoh kasus: tombol WAL checkpoint).

