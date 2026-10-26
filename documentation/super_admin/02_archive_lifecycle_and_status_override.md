# Modul Tata Kelola Siklus Hidup Arsip & Status Override (Super Admin)

## 1. Deskripsi Fitur
Modul Tata Kelola Siklus Hidup Arsip memberikan kendali penuh kepada Super Admin untuk mengelola berkas arsip dari seluruh 29 departemen Indraco. Fitur utama mencakup:
1. **Pengawasan Status Siklus Hidup:** Memantau perubahan status box arsip dari status awal hingga akhir:
   - `draft`: Pengajuan awal oleh PIC Departemen.
   - `verified`: Berkas telah disetujui secara administratif.
   - `stored`: Box telah ditempatkan secara fisik pada slot rak gudang.
   - `borrowed`: Berkas sedang dipinjam oleh staf internal.
   - `checkout`: Box dipinjam keluar gedung gudang sementara waktu (audit eksternal / pengadilan).
   - `expired`: Masa retensi arsip telah habis dan siap diproses musnah/perpanjang.
   - `destroyed`: Arsip telah dimusnahkan secara fisik dan diterbitkan BAP.
2. **Superadmin Status Override:** Wewenang eksklusif untuk mengubah status arsip secara langsung dalam kondisi khusus (misal: koreksi human-error atau sinkronisasi darurat data fisik gudang).
3. **Pembersihan Massal Draft Kosong (*Batch Delete Drafts*):** Menghapus pengajuan draft yang terbengkalai secara massal.

---

## 2. Hak Akses & Role Permission
- **Role:** `admin` (Super Admin)
- **Middleware:** `auth`, `role:admin`
- **Wewenang:** Akses lintas departemen untuk operasi Create, Read, Update, Delete, dan Override Status.

---

## 3. Komponen Teknis & Skema Database

### File Terkait:
- Controller Arsip: [ArchiveController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/ArchiveController.php)
- Controller Status: [ArchiveStatusController.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/ArchiveStatusController.php) (Metode `superAdminUpdateStatus()`)
- Model: [Archive.php](file:///C:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Archive.php)
- View: `resources/views/archives/index.blade.php`, `resources/views/archives/show.blade.php`, dan modal `superadmin_status_modal.blade.php`
- Route:
  - `GET /archives` (`name('archives.index')`)
  - `GET /archives/{archive}` (`name('archives.show')`)
  - `PUT /archives/{archive}` (`name('archives.update')`)
  - `DELETE /archives/{archive}` (`name('archives.destroy')`)
  - `POST /archives/batch-delete-drafts` (`name('archives.batch_delete_drafts')`)
  - `POST /archives/{archive}/superadmin-status` (`name('archives.superadmin_status')`)

---

## 4. Flowchart Siklus Hidup Arsip & Alur Status Override

```mermaid
flowchart TD
    subgraph StandardLifecycle [Siklus Hidup Arsip Standar]
        DraftState[1. Status: draft - Dibuat oleh PIC Dept] --> VerifyState[2. Status: verified - Disetujui Administratif]
        VerifyState --> StoredState[3. Status: stored - Ditaruh di Rak Gudang oleh PIC Gudang]
        StoredState --> BorrowedState[4. Status: borrowed - Dipinjam Staf]
        BorrowedState --> StoredState
        StoredState --> CheckoutState[5. Status: checkout - Keluar Gudang Sementara]
        CheckoutState --> StoredState
        StoredState --> ExpiredState[6. Status: expired - Masa Retensi Habis H-30]
        ExpiredState --> DestroyedState[7. Status: destroyed - Fisik Dimusnahkan & Terbit BAP]
    end

    subgraph SuperAdminIntervention [Intervensi Status Override Super Admin]
        SelectArchive[Super Admin Buka Detail Box Arsip /archives/{id}] --> ClickOverrideBtn[Klik Tombol 'Superadmin Status Override']
        ClickOverrideBtn --> OpenStatusModal[Buka Modal Pilihan Status & Alasan Perubahan]
        OpenStatusModal --> InputReason[Pilih Status Target Baru & Wajib Ketik Alasan Override]
        InputReason --> SubmitOverride[Kirim POST ke /archives/{archive}/superadmin-status]
        
        SubmitOverride --> ValidateStatus{Apakah Alasan Terisi & Status Valid?}
        ValidateStatus -- Gagal --> ShowErrorToast[Tolak: Alasan Override Wajib Diisi]
        ValidateStatus -- Valid --> UpdateDBRecord[(Update Kolom status di Tabel archives)]
        
        UpdateDBRecord --> LogAuditOverride[Catat ke activity_logs: archive.status_override_by_superadmin]
        LogAuditOverride --> SyncSlotStatus[Sesuaikan Status Slot Rak Terkait Jika Berubah ke Stored / Destroyed]
        SyncSlotStatus --> RefreshUI[Perbarui Badge Status & Riwayat Log Arsip]
    end

    StoredState -. Koreksi Data .-> SelectArchive
    RefreshUI --> End([Selesai])
```

---

## 5. Rincian Antarmuka & Langkah Kerja Super Admin

1. Buka menu **Katalog Arsip** (`/archives`).
2. **Memfilter Berkas:**
   - Gunakan filter departemen di bagian atas tabel untuk melihat arsip dari divisi tertentu (misal: *Finance* atau *Human Resources*).
   - Filter berdasarkan status (misal: mencari seluruh box yang berstatus `expired`).
3. **Melakukan Status Override:**
   - Buka rincian arsip dengan mengklik nomor barcode box (misal: `ARC/FIN/2026/X/0001`).
   - Klik tombol **Superadmin Status** di panel toolbar atas.
   - Pilih status baru (contoh: mengembalikan status dari `borrowed` ke `stored` karena berkas telah dikembalikan namun lupa di-scan oleh operator).
   - Masukkan alasan formal (misal: *"Koreksi data fisik berkas telah kembali ke rak"*).
   - Klik **Simpan Perubahan Status**. Log audit akan mencatat identitas Super Admin dan alasan perubahan tersebut.
