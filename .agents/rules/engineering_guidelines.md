# Engineering Change & Code Review Standards

> **Understand → Simplify → Smallest Correct Diff → Validate**

## 1. Understand Before Touching Code
- **Minimal code ≠ minimal reasoning**: Pahami purpose, lifecycle, timing, concurrency, call chain, data flow, dan side effects sebelum mengubah kode.
- **Root cause over symptom**: Jangan membuat patch hanya untuk menghilangkan error/warning tanpa memahami penyebabnya.
- **End-to-End awareness**: Evaluasi alur `Source → Processing → Storage → Event → Consumer → UI`.

## 2. Evidence-Based Optimization
- Jangan menambahkan cache, worker, batching, debounce, memoization, queue, atau optimasi arsitektur tanpa bukti bottleneck nyata.
- Gunakan: **Problem → Evidence/Benchmark → Root Cause → Solution → Measurement**.
- Optimasi harus menyelesaikan masalah yang terukur, bukan sekadar terlihat lebih advanced.

## 3. Cohesion & File Structure
- File/function besar **tidak otomatis harus dipecah** hanya karena jumlah baris.
- Pecah hanya jika pemisahan memperjelas responsibility, menurunkan complexity, atau meningkatkan maintainability.
- Hindari over-fragmentation dan file mikro yang tidak diperlukan.
- Jangan mengubah struktur hanya demi memenuhi aturan ukuran file.

## 4. Preserve Behavior & Scope
- Perubahan internal tidak boleh mengubah behavior, timing, ordering, error handling, data flow, atau UI existing tanpa alasan yang jelas.
- **Shortest correct diff wins.**
- Jangan melakukan refactor, redesign, cleanup, atau architecture change di luar scope.
- Hindari **"sekalian saya rapikan semuanya."**

## 5. Side Effects & System Impact
Setiap perubahan harus diperiksa terhadap kemungkinan:
- duplicate execution,
- race condition,
- stale data,
- memory leak,
- event cascade,
- excessive render,
- excessive IPC/API/database calls,
- background process buildup,
- queue/retry cascade,
- atau resource yang tidak ter-cleanup.
Jika perubahan berada dalam satu bagian sistem, periksa dampaknya terhadap bagian lain.

## 6. Review Diff & Validate
Setelah perubahan:
- Review seluruh diff.
- Pastikan tidak ada behavior yang berubah tanpa sengaja.
- Pastikan tidak ada perubahan di luar scope.
- Jalankan validation yang relevan: typecheck, lint, test, build, database/realtime check, performance check, atau manual check.
- Validation harus proporsional terhadap risiko perubahan.
- Laporkan error yang tersisa dan potensi regresi secara jujur.

## 7. Phase-Gate Protocol
Setiap fase harus:
1. Memiliki scope yang jelas.
2. Selesai diimplementasikan.
3. Diverifikasi secara empiris.
4. Direview hasilnya.

**DILARANG melanjutkan ke fase berikutnya sebelum user memberikan approval (ACC).**

---

## Golden Rule
> **Pahami sebelum mengubah. Cari root cause. Pertahankan behavior. Hindari perubahan yang tidak perlu. Optimalkan berdasarkan evidence. Buat diff sekecil mungkin. Validate sebelum lanjut.**
