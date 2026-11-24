---
name: sqlite-concurrency
description: Cara menulis kode tulis-database yang aman untuk banyak user bersamaan di SQLite DMS Indraco (WAL, transaksi IMMEDIATE pendek, counter atomik, anti double-submit, optimistic lock, batch insert, backup VACUUM INTO). Pakai di setiap Action/Service yang INSERT/UPDATE/DELETE.
---

# SQLite multi-user

Kontrak lengkap: `.agents/rules/PROJECT_RULES_SQLITE.md`. Skill ini = cara menerapkannya.

## Pola Action tulis
```php
public function handle(User $user, array $data, ?UploadedFile $scan): Archive
{
    // 1. Di LUAR transaksi: validasi lanjutan, baca referensi, simpan file ke disk
    $scanPath = $scan ? $this->files->store($scan, 'archive_scan_inputs') : null;

    try {
        // 2. Transaksi pendek (BEGIN IMMEDIATE via config transaction_mode)
        return DB::transaction(function () use ($user, $data, $scanPath) {
            // guard status/duplikat di DALAM transaksi
            // insert/update saja; counter pakai UPDATE ... RETURNING
        });
    } catch (\Throwable $e) {
        if ($scanPath) { $this->files->delete($scanPath); } // bersihkan file yatim
        throw $e;
    }
}
```

## Aturan cepat
- Tidak ada HTTP call, PDF, resize gambar, `sleep` di dalam transaksi.
- Guard bisnis melempar `\DomainException`; controller hanya menangkap itu dan menampilkan pesannya.
- Banyak baris: `Model::insert()` per chunk 250.
- Form edit: hidden `original_updated_at`; tolak jika DB lebih baru (`back()->withInput()`).
- Middleware yang menulis DB di setiap request (L7: `UpdateUserPresence`, `TrackPageVisits`) **jangan dibawa apa adanya** — throttle (mis. paling sering sekali/menit per user) atau tulis ke file. Lihat `context/findings.md`.
- Backup: `VACUUM INTO` ke path baru.

## Verifikasi
- Tes paralel (k6 profil `multiuser`) untuk modul yang menulis: 0 `database is locked`, p95 sesuai SLA.
- Cek tidak ada `DB::statement('BEGIN')` manual dan tidak ada `catch (\Exception)` generik yang menampilkan pesan SQL.
