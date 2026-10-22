# Tasks & Lessons Learned

## 2026-10-14: Multi-Box Scope Discipline & Form Simplicity

### Context
When implementing Point 1 (locking document type per department/sub-department), two speculative UI additions were made:
1. Adding an extra "TIPE DOKUMEN" column in the items repeater table.
2. Adding a per-box "SUB-DEPARTEMEN" selector inside Section 2 (Multi-Box management cards).

### User Correction
1. **No Speculative Columns in Items Table (Gambar 1)**:
   - Do NOT add a "TIPE DOKUMEN" column or badges into the items repeater table. The items table must remain minimal (NO, Nama Dokumen, Periode Mulai, Periode Selesai, Keterangan, Aksi).
2. **Unified Sub-Department in Multi-Box (Gambar 2)**:
   - In Multi-Box creation, all boxes belong to the **same Sub-Departemen** defined globally at Section 1 (Header/Batch level) to prevent input errors and ensure consistency.
   - Do NOT provide a separate sub-department dropdown inside individual box cards. All boxes cleanly inherit the single `selectedSubDeptId` from Section 1.
   - *Feature Note for Future*: If individual per-box sub-department assignment is ever requested, re-enable it as a dedicated configuration toggle rather than a default.

### Rules Established
- **Rule 1 (Strict Request Fidelity)**: Never add additional columns to tables or forms unless explicitly described in the user prompt.
- **Rule 2 (Single Sub-Department Hierarchy)**: In multi-box workflows, keep department and sub-department selection strictly at Section 1. Boxes inherit the parent batch's sub-department automatically.

---

## 2026-10-15: Zero Native Browser Dialogs (`confirm`/`alert`) & Full-Screen Preservation

### Context
The DMS application runs on client desktop browsers that frequently operate in Full-Screen mode (F11/Kiosk mode) across office and warehouse terminals.

### User Correction
Native browser OS dialogs (`confirm()`, `alert()`, `prompt()`) interrupt the browser rendering context, causing Windows and Chromium-based browsers to drop out of full-screen mode back to windowed mode.
The user's directive is to eliminate all native `confirm()` popups and replace them exclusively with in-page HTML/Tailwind modals (`confirm_modal.blade.php`).

### Rules Established
- **Rule 1 (Zero Native Browser Dialogs)**: DILARANG KERAS menggunakan `confirm(...)` atau `window.confirm(...)` bawaan browser.
- **Rule 2 (In-Page Modal Delivery)**: Seluruh konfirmasi aksi wajib menggunakan in-page HTML modal via atribut deklaratif `data-confirm` atau `window.showConfirmModal()`, sehingga status full-screen browser tetap terjaga tanpa interupsi OS.

---

## 2026-10-15: Retention Extension Workflow, Post-BAP Upload & Blank Signature Dates

### Context
1. Pengajuan perpanjangan masa retensi sebelumnya langsung menambah durasi retensi secara otomatis tanpa persetujuan (ACC) dari pihak gudang.
2. Form cetak perpanjangan menampilkan tanggal akhir periode yang lama dan belum menambahkan durasi perpanjangan (misal +6 bulan).
3. Setelah eksekusi pemusnahan di-ACC oleh gudang, jika file BAP terlewat diunggah karena opsional, tidak ada cara untuk mengunggah BAP susulan.
4. Tanggal di bawah tanda tangan cetak formulir sebelumnya terisi otomatis.

### User Correction & Solution
1. **Approval Workflow Perpanjangan**:
   - Pengajuan dari departemen mengubah status arsip menjadi `pending_extension` ("Pengajuan Perpanjangan") dan mencatat log dengan status `pending`.
   - Durasi retensi (`retention_years` & `retention_expiry_date`) baru bertambah setelah Gudang/Admin melakukan **ACC**.
   - Jika ditolak oleh Gudang, status kembali ke `in_warehouse` tanpa perubahan retensi.
2. **Kalkulasi Periode Akhir pada Form Cetak**:
   - Periode Akhir pada form cetak perpanjangan dihitung dinamis (`baseExpiry + additionalMonths`) menghasilkan format `MM-YYYY` yang akurat (contoh: `04-2027` untuk +6 bulan dari `10-2026`).
3. **Upload BAP Susulan**:
   - Menyediakan tombol & modal "Upload BAP" pada tab Pemusnahan untuk arsip yang sudah selesai dimusnahkan (`completed`) namun file BAP-nya belum terunggah.
4. **Format Tanggal Tanda Tangan Cetak**:
   - Seluruh form cetak (pengajuan arsip baru, penarikan, perpanjangan, dan pemusnahan) wajib mengosongkan tanggal di bawah ttd menggunakan titik-titik (`Tgl. ....................`) agar dapat diisi manual secara tertulis.

---

## 2026-10-21: Multi-Box Excel Direct Warehouse Commit, Auto-Numbering & Smart Master Matching

### Context
1. Tombol "Simpan Langsung ke Gudang" pada Super Admin / Admin Gudang sempat berstatus pengajuan baru / draft karena modal frontend mengirimkan nilai mentah `importTargetStatus` dan backend `MultiBoxExcelService::commitImport` tidak otomatis meng-generate `box_number` resmi dan mutasi `WarehouseEntryLog`.
2. Template Excel sebelumnya memiliki kolom `NO_BUTIR` yang membingungkan dan manual diisi oleh pengguna.
3. Urutan kolom sebelumnya kurang natural untuk alur kerja pengguna (identitas box -> berkas dokumen -> periode -> keterangan -> retensi -> lokasi fisik rak).
4. Istilah "butir" dirasa kaku dan tidak familiar bagi pengguna umum.
5. Pengguna rentan melakukan kesalahan ketik (huruf kecil, salah urutan kata, typo huruf, kekurangan/kelebihan kata) saat menulis nama dokumen di Excel.

### User Correction & Solution
1. **Direct Commit Status & Numbering**:
   - `submitImportCommitDirect()` secara eksplisit mengirimkan `status: 'in_warehouse'`.
   - Di backend `MultiBoxExcelService::commitImport`, jika status `in_warehouse`, otomatis men-generate `box_number` via `NumberingService`, mencatat `WarehouseEntryLog`, dan menaikkan kapasitas rak.
2. **Peniadaan Kolom `NO_BUTIR` & Penomoran Otomatis**:
   - Kolom `NO_BUTIR` ditiadakan dari template. Sistem otomatis memberi nomor urut berkas `1, 2, 3...` per box dari baris atas ke bawah, dengan tetap menjaga *backward compatibility* jika file lama masih memiliki kolom nomor.
3. **Urutan Kolom Logis & Tabel Referensi Master**:
   - Urutan kolom disesuaikan: Box -> Dokumen -> Periode -> Keterangan -> Retensi/Kondisi -> Rak/Slot.
   - Sheet 2 (`REFERENSI_KODE`) ditambahkan tabel `[DAFTAR MASTER NAMA BERKAS / DOKUMEN RESMI]`. Khusus PIC Departemen, tabel otomatis difilter hanya untuk departemennya.
4. **Multi-Tier Smart & Fuzzy Matching**:
   - Menangani typo huruf (~94% similarity), salah urutan kata (*anagram kata*), kurang kata (*token subset*), dan kelebihan kata (*token superset*).
   - Jika dokumen baru di luar master, sistem tetap aman (tidak error) dan membawanya sebagai berkas kustom.
5. **Penggantian Istilah "Butir"**:
   - Seluruh antarmuka web (form create, edit, modal import, konfirmasi) diubah menjadi "Berkas / Dokumen" tanpa mengubah skema tabel database `archive_items.item_number`.

### Rules Established
- **Rule 1 (Direct Warehouse Completeness)**: Setiap penyimpanan langsung ke status `in_warehouse` WAJIB meng-generate `box_number` dan mencatat `WarehouseEntryLog` secara atomik.
- **Rule 2 (Graceful Input Tolerance)**: Input teks nama dokumen dari Excel harus selalu diproses dengan layer normalisasi toleran (case, whitespace, token anagram, similar_text) sebelum memutuskan sebagai dokumen kustom.


