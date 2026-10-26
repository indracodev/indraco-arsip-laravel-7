# Dokumentasi Fitur: Eksekusi Pemusnahan Fisik Arsip (Physical Destruction Execution)

Dokumen ini menjelaskan alur kerja operasional PIC Gudang dalam mengeksekusi pemusnahan fisik arsip yang telah habis masa retensinya, diverifikasi oleh Komite Pemusnahan, dan diterbitkan Berita Acara Pemusnahan (BAP) multi-box. Termasuk di dalamnya adalah pelepasan otomatis slot rak gudang menjadi kosong kembali.

---

## 1. Ringkasan Fitur
- **Role Pengguna**: `pic_gudang` (Warehouse Operator / Tim Pelaksana Pemusnahan)
- **Menu Akses**: `Menu Utama` > `Pemusnahan Arsip` > `Eksekusi Pemusnahan` (`/warehouse/destructions`)
- **Tujuan**:
  1. Menampilkan daftar box arsip yang telah memperoleh status persetujuan final pemusnahan (`approved_for_destruction`).
  2. Menghasilkan *Destruction Pulling List* (daftar nomor rak dan slot dari seluruh box yang akan dimusnahkan).
  3. Mengambil kardus fisik dari rak dan memindahkannya ke area karantina pemusnahan / mesin penghancur (*industrial shredder* atau pembakaran ramah lingkungan/daur ulang peleburan).
  4. Mendokumentasikan pelaksanaan fisik (foto dokumentasi proses penghancuran dan Berita Acara yang telah ditandatangani para saksi).
  5. Mengonfirmasi penyelesaian pemusnahan pada sistem, yang secara otomatis:
     - Mengubah status arsip menjadi `destroyed`.
     - Mengosongkan slot rak gudang terkait (`is_occupied = false`, `archive_id = null`).

---

## 2. Alur Kerja (Flowchart Eksekusi Pemusnahan Fisik)

```mermaid
flowchart TD
    Start([Super Admin/Legal Menyetujui BAP Pemusnahan]) --> TicketReady[Tiket Pemusnahan Berstatus 'Ready to Destroy']
    TicketReady --> WarehouseView[PIC Gudang Buka Antrean Eksekusi Pemusnahan]
    
    WarehouseView --> GeneratePullingList[Cetak Daftar Penarikan Box:
      - Daftar No Box & Barcode
      - Lokasi Rak, Sap, & Slot masing-masing box]
      
    GeneratePullingList --> PullBoxes[PIC Gudang Mengambil Box dari Rak Fisik]
    PullBoxes --> QuarantineArea[Kumpulkan Box di Area Karantina Pemusnahan]
    
    QuarantineArea --> WitnessVerification[Verifikasi Bersama Tim Saksi:
      - PIC Departemen Pemilik
      - Staff Legal / Compliance
      - PIC Gudang]
      
    WitnessVerification --> ExecuteDestruction[Eksekusi Fisik Penghancuran:
      - Mesin Pencacah (Industrial Shredder) / Peleburan Kimia / Daur Ulang Pulp]
      
    ExecuteDestruction --> SignBAP[Penandatanganan Fisik Dokumen BAP oleh Seluruh Saksi]
    SignBAP --> ScanUploadBAP[PIC Gudang Scan Dokumen BAP & Foto Dokumentasi]
    
    ScanUploadBAP --> OpenExecutionForm[Buka Form Konfirmasi Pemusnahan di Sistem]
    OpenExecutionForm --> UploadEvidence[Upload Scan BAP & Foto Pelaksanaan]
    UploadEvidence --> SubmitConfirmation[Klik Konfirmasi Pemusnahan Selesai]
    
    SubmitConfirmation --> DBTransaction[Database Transaction:
      1. Update DestructionBatch status = 'completed'
      2. Update All Related Archives status = 'destroyed', destroyed_at = now()
      3. For Each Slot:
         - Set is_occupied = false
         - Set archive_id = null
      4. Log System Audit Trail]
      
    DBTransaction --> SlotsFreed[Slot Rak Otomatis Hijau / Kosong Kembali]
    SlotsFreed --> Finish([Pemusnahan Selesai])
```

---

## 3. Komponen Teknis (Backend & Database)

### 3.1 Controller & Routing
- **Controller**: [DestructionController.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Http/Controllers/DestructionController.php)
- **Route Endpoint**:
  - `GET /destructions?filter=ready_to_destroy`: Daftar batch pemusnahan yang siap dieksekusi secara fisik.
  - `GET /destructions/{batch}/pulling-list`: Cetak daftar lokasi rak seluruh box dalam batch BAP.
  - `POST /destructions/{batch}/execute`: Upload bukti pelaksanaan dan konfirmasi selesai pemusnahan fisik.

### 3.2 Eloquent Models Terkait
- [DestructionBatch.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/DestructionBatch.php)
  - Atribut: `bap_number`, `status` (`draft`, `approved`, `completed`), `destruction_date`, `executed_by`, `evidence_file_path`.
- [Archive.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/Archive.php)
  - Atribut: `status = 'destroyed'`, `destroyed_at = now()`.
- [WarehouseSlot.php](file:///c:/laragon/www/%23Project2026/indraco-arsip-laravel-7/app/Models/WarehouseSlot.php)
  - Atribut: `is_occupied = false`, `archive_id = null`.

---

## 4. Langkah Operasional PIC Gudang

1. **Persiapan Penarikan**:
   - Buka menu **Pemusnahan Arsip** > **Siap Eksekusi**.
   - Klik nomor batch BAP untuk mencetak lembar penarikan (*Pulling List*).
2. **Penarikan Box dari Rak**:
   - Tarik seluruh kardus box yang tertera pada *pulling list* dari rak gudang.
   - Pindahkan seluruh box ke area karantina pemusnahan.
3. **Penyaksian Bersama**:
   - Hadiri proses pemusnahan bersama saksi perwakilan Departemen pemilik arsip dan tim Legal.
   - Masukkan berkas dan kardus ke dalam mesin pencacah dokumen industri.
4. **Penandatanganan BAP**:
   - Pastikan seluruh saksi menandatangani fisik dokumen BAP bermaterai.
5. **Konfirmasi di Sistem & Pengosongan Rak**:
   - Scan dokumen BAP basah dan ambil foto dokumentasi cacahan dokumen.
   - Buka form konfirmasi di sistem, unggah berkas PDF BAP dan foto.
   - Masukkan tanggal & waktu riil pemusnahan fisik, lalu klik **Konfirmasi Pemusnahan Selesai**.
   - Sistem secara otomatis melepaskan slot rak yang sebelumnya ditempati, sehingga rak langsung tersedia kembali untuk box baru.
