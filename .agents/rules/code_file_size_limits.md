# Code File Size Limits & Modularity Guidelines

## 1. Absolute Maximum File Size (< 800 Lines)
- **Hard Limit**: Setiap file kode aplikasi (`app/`, `resources/views/`, `routes/`, `tests/`, `config/`, `database/`) **TIDAK BOLEH** melebihi 800 baris kode (harus strictly `< 800 lines`).
- Pengecualian hanya untuk vendor packages, node modules, generated storage files, public compiled assets, dan binary/sqlite files.

## 2. Extraction & Refactoring Standards

Pemisahan file harus dilakukan dengan memegang prinsip **Cohesion & Single Responsibility**, bukan sekadar memotong sembarangan:

### A. Controllers (`app/Http/Controllers`)
- **Domain Sub-Controllers**: Jika sebuah controller membengkak karena menangani banyak sub-fitur (misal: import/export Excel, batch label printing, verification/check-in flow, status workflow), pecah controller tersebut menjadi sub-controller yang terfokus (contoh: `ArchiveLabelController`, `ArchiveVerificationController`, `ArchiveStatusController`, `ArchiveImportController`).
- **Traits / Action Classes**: Gunakan Traits terstruktur di folder `app/Http/Controllers/Traits/` untuk alur logika orkestrasi form yang panjang (contoh: `HandlesMultiBoxCreation`, `HandlesMultiBoxEditing`).
- Pertahankan route naming, middleware, dan parameter bindings yang sudah ada.

### B. Blade Views & Alpine.js (`resources/views/`)
- **Partial Directory Hierarchy**: Simpan sub-partial di subfolder spesifik yang mencerminkan konteks domainnya (misal: `resources/views/<feature>/partials/` atau `modals/`).
- **Modals & Drawers**: Pisahkan setiap modal besar, flyout drawer, atau preview panel ke dalam file partial tersendiri dan sertakan menggunakan `@include('feature.partials.modal_name')`.
- **Alpine.js & JavaScript Blocks**:
  - Untuk logic UI yang rumit (seperti Warehouse Canvas 2D/3D, Desktop Workspace, Multi-box dynamic form), pisahkan script ke dalam modul-modul fungsional yang kohesif (misal: `canvas_engine`, `canvas_tools`, `canvas_events`, `canvas_slots`).
  - Pastikan state Alpine.js (`x-data`) tetap sinkron melalui window helper objects, Alpine mixins, atau Custom Event dispatching (`window.dispatchEvent` / `$dispatch`).

### C. Services (`app/Services/`)
- Pecah service monolitik menjadi sub-service independen berdasarkan kapabilitas (contoh: pemisahan template generation `MultiBoxTemplateService`, kalkulasi retensi `ArchiveRetentionService`, atau semantic similarity `ArchiveSimilarityService`).
- Gunakan traits untuk pembagian helper kalkulasi internal yang panjang.

### D. Automated Tests (`tests/`)
- Jika sebuah Test Case membengkak karena cakupan skenario yang sangat luas, pisahkan ke dalam file Test terpisah berdasarkan fase pengujian (misal: `Revisi1UatPart2Test`).
- Simpan shared setup, mock fixtures, dan assertions bantuan ke dalam reusable Trait di `tests/Feature/Traits/`.

## 3. Verification Protocol
- Sebelum menyelesaikan fitur atau refactoring, jalankan pemeriksaan jumlah baris:
  ```bash
  php scan_lines.php
  ```
- Pastikan:
  1. `Total code files >= 800: 0`
  2. Linter PHP bersih: `php -l <modified_files>`
  3. Seluruh unit/feature test tetap lulus: `vendor/bin/phpunit`
