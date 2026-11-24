---
trigger: glob
globs: app/**/*.php, routes/**/*.php
---

# Arsitektur

```text
app/
├── Actions/            # satu use case bisnis (mis. SubmitBoxPengajuan, ApproveBorrowing)
├── Http/Controllers/   # tipis: terima input, panggil Action, kembalikan response
├── Http/Requests/      # validasi input
├── Http/Middleware/
├── Models/
├── Policies/           # otorisasi per model (kepemilikan departemen)
├── Services/           # logika bersama non-use-case (penomoran box, spreadsheet, file storage)
└── View/Components/
```

- Controller tidak berisi logika bisnis atau query kompleks. Validasi di Form Request.
- **Satu aturan bisnis, satu implementasi.** Mis. penomoran box, perhitungan tanggal kedaluwarsa retensi, transisi status arsip: masing-masing satu tempat, dipakai web dan impor Excel.
- Otorisasi di server untuk setiap aksi: role **dan** kepemilikan departemen (Policy). Jangan mengandalkan tombol yang disembunyikan.
- Transisi status hanya lewat Action yang memeriksa status asal yang sah.
- Tanpa Repository Pattern, DTO, interface, atau event bus kecuali ada kebutuhan nyata (dua implementasi yang benar-benar dipakai).
- Gabungkan controller/route lama yang berulang hanya jika memang satu aturan bisnis. URL lama yang masih dipakai klien desktop (`desktop/`, Tauri) tetap dilayani atau diberi redirect.
- Route tanpa closure (agar `route:cache` jalan).
- Kode baru mengikuti konvensi Laravel 13 dari dokumentasi resmi dan `vendor/`, bukan gaya Laravel 7.
- File < 800 baris (`code_file_size_limits.md`), tanpa memecah berlebihan (`engineering_guidelines.md` §3).
