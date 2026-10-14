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
