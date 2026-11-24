---
trigger: glob
globs: database/**, app/Models/**, app/Actions/**, app/Services/**, config/database.php
---

# DMS PT INDRACO — Project Rules (Bagian 2/3): SQLite Concurrency, Integritas Data & Aturan Kerja Agent — Laravel 13

> Bagian dari [`PROJECT_RULES.md`](PROJECT_RULES.md).

## 3. Layer 2: SQLite Concurrency & I/O Performance

SQLite (`database/database.sqlite`) dipakai untuk portabilitas. Locking level file, jadi konkurensi dan I/O adalah prioritas mutlak. Beberapa PC klien menulis ke satu file lewat **beberapa worker php-cgi bersamaan**.

### 3.1. Konfigurasi koneksi wajib (bawaan Laravel 13)
File: `config/database.php` (menggantikan listener `ConnectionEstablished` di `AppServiceProvider` versi Laravel 7).

```php
'sqlite' => [
    'driver' => 'sqlite',
    'database' => env('DB_DATABASE', database_path('database.sqlite')),
    'foreign_key_constraints' => true,
    'busy_timeout' => 10000,
    'journal_mode' => 'wal',
    'synchronous' => 'normal',
    'transaction_mode' => 'IMMEDIATE',
    'pragmas' => [
        'temp_store' => 'memory',
        'cache_size' => -32000,     // 32 MB page cache
        'mmap_size' => 67108864,    // 64 MB memory-mapped I/O
    ],
],
```

### 3.2. Penjelasan
1. **WAL**: pembaca tidak memblok penulis dan sebaliknya.
2. **`busy_timeout`**: penulis menunggu kunci sampai 10 dtk sebelum `SQLITE_BUSY`.
3. **`transaction_mode = IMMEDIATE`**: `DB::transaction()` langsung mengambil kunci tulis (`BEGIN IMMEDIATE`), sehingga `busy_timeout` dipatuhi. Ini menggantikan helper `db_immediate_transaction()` Laravel 7. Tanpa ini, transaksi DEFERRED yang diawali baca lalu menulis bisa langsung gagal `SQLITE_BUSY` di mode WAL.
4. **`synchronous = NORMAL`**: sync hanya di checkpoint WAL; aman untuk WAL dan jauh lebih cepat.
5. **`temp_store`, `cache_size`, `mmap_size`**: sorting/temp di RAM, 32 MB cache, 64 MB mmap.

### 3.3. Aturan Query & Eloquent
- **Wajib eager loading** (`with(...)`); `Model::preventLazyLoading()` di luar production.
- **Index wajib** untuk setiap foreign key dan kolom filter/sort/pencarian (`status`, `department_id`, `box_number`, tanggal).
- **Transaksi pendek**: hanya query DB esensial. **DILARANG** file, PDF, gambar, HTTP, atau komputasi berat di dalam transaksi.
- **Paging/chunking**: tidak ada `->get()` tanpa batas pada tabel transaksi; `paginate()` atau `chunk()`.
- **Data global untuk banyak view** (mis. `AppSetting`): di-cache per request (static/`once()`), bukan query di setiap partial.
- Session dan cache memakai driver `file`, bukan `database`.

### 3.4. Batch Insert & Chunking
1. Banyak baris (`ArchiveItem`, `MasterArchive`, `WarehouseRackSlot`, log batch) **WAJIB** `Model::insert($array)`, bukan loop `create()`.
2. Lebih dari 200 baris: `array_chunk($rows, 250)`.
3. Log aktivitas massal lewat satu fungsi batch logger.

### 3.5. Integritas Data & Konkurensi Multi-User
Setiap pola "baca di PHP → tulis balik" **WAJIB** dianggap race condition.

**a. Counter/penomoran atomik**: **DILARANG** `counter + 1` dari PHP, `count() + 1`, atau `update(['current_counter' => $n])`. Gunakan:
```php
$counter = (int) DB::selectOne(
    'UPDATE numbering_formats SET current_counter = current_counter + 1 WHERE id = ? RETURNING current_counter',
    [$format->id]
)->current_counter;
```
**b. Preview tanpa side-effect**: fungsi penghasil nomor untuk tampilan memakai mode preview (pelajaran L7: halaman Master Penomoran pernah membakar counter setiap dibuka).
**c. UNIQUE index + validasi berpasangan**: kolom unik punya UNIQUE index (partial bila hanya untuk data aktif, mis. `box_number` aktif `WHERE status NOT IN ('taken','destroyed','borrowed')`); validasi aplikasi memakai kondisi yang **sama** agar user dapat pesan validasi, bukan 500. Cek duplikat sebelum memasang index; dedupe tidak otomatis tanpa keputusan user.
**d. Transaksi baca→tulis**: `DB::transaction()` (mode IMMEDIATE). Nested transaction menjadi savepoint — hindari kecuali perlu. **DILARANG** `DB::statement('BEGIN ...')` manual. `lockForUpdate()` tidak berefek di SQLite dan **DILARANG** jadi satu-satunya proteksi.
**e. Anti double-submit**: aksi pengajuan (peminjaman/penarikan, pemusnahan, perpanjangan, submit box) wajib guard server (cek status aktif + insert dalam satu transaksi) **dan** tombol disable + spinner. Guard melempar `\DomainException`; hanya itu yang di-catch. **DILARANG** `catch (\Exception)` generik yang meneruskan pesan SQL ke user.
**f. Optimistic locking**: form edit record bersama mengirim `original_updated_at`; tolak jika DB lebih baru dengan `back()->withInput()->with('error', ...)`. Edit child (`archive_items`) wajib `touch()` induknya.
**g. Backup**: `VACUUM INTO '<path baru>'`, kirim dengan `download()->deleteFileAfterSend(true)`. **DILARANG** dump tabel satu per satu tanpa snapshot.

### 3.6. Aturan Kerja Agent
- **DILARANG** mengonversi line ending satu file penuh. Cek `git ls-files --eol <file>`; `git diff --stat` hanya baris yang benar-benar diubah. File `.bat` wajib CRLF.
- Klaim "test lulus" **WAJIB** disertai output asli `php artisan test`.
- Fitur yang di plan diputuskan **tidak** dibuat **DILARANG** ditambahkan tanpa persetujuan user.
