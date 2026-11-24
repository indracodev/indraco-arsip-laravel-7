{{-- Laravel 7 frmDepartmentAdd / frmDepartmentEdit and frmSubDepartmentAdd / frmSubDepartmentEdit: one modal
     each, add or edit by `deptForm.id` / `subForm.id`. `_form` / `_id` let the page reopen a form after a validation error. --}}
<x-modal id="dept-form" title="Departemen">
    <form method="POST" :action="deptForm.id ? urls.departments + '/' + deptForm.id : urls.departments" class="stack">
        @csrf
        <input type="hidden" name="_method" :value="deptForm.id ? 'PUT' : 'POST'">
        <input type="hidden" name="_form" value="dept">
        <input type="hidden" name="_id" :value="deptForm.id || ''">
        <p class="field__label" x-text="deptForm.id ? 'Form Perubahan Departemen' : 'Data Induk Departemen'"></p>
        <x-field label="KODE DEPARTEMEN" for="dept-code" required error="code" hint="CTH: FIN, HRD, LOG, MKT, PROD">
            <x-input name="code" id="dept-code" x-model="deptForm.code" required maxlength="10" placeholder="FIN" />
        </x-field>
        <x-field label="NAMA DEPARTEMEN" for="dept-name" required error="name">
            <x-input name="name" id="dept-name" x-model="deptForm.name" required maxlength="100" placeholder="Keuangan & Akuntansi (FAT)" />
        </x-field>
        <x-field label="DESKRIPSI / RUANG LINGKUP" for="dept-description" error="description">
            <x-textarea name="description" id="dept-description" x-model="deptForm.description" rows="2" placeholder="Uraian ruang lingkup dokumen & divisi..." />
        </x-field>
        <div class="modal__footer">
            <x-button data-modal-close>Batal (Esc)</x-button>
            <x-button type="submit" variant="primary"><span x-text="deptForm.id ? 'Update Data (Enter)' : 'Simpan Departemen (Enter)'"></span></x-button>
        </div>
    </form>
</x-modal>

<x-modal id="sub-form" title="Sub-Departemen">
    <form method="POST" :action="subForm.id ? urls.subDepartments + '/' + subForm.id : urls.subDepartments" class="stack">
        @csrf
        <input type="hidden" name="_method" :value="subForm.id ? 'PUT' : 'POST'">
        <input type="hidden" name="_form" value="sub">
        <input type="hidden" name="_id" :value="subForm.id || ''">
        <p class="field__label" x-text="subForm.id ? 'Form Perubahan Sub-Departemen' : 'Data Sub-Departemen'"></p>
        <x-field label="DEPARTEMEN INDUK (PARENT)" for="sub-department" required error="department_id">
            <select name="department_id" id="sub-department" class="control" x-model="subForm.department_id" required>
                <option value="">-- Pilih Departemen Induk --</option>
                <template x-for="dept in items" :key="dept.id">
                    <option :value="String(dept.id)" x-text="dept.code + ' - ' + dept.name" :selected="String(dept.id) === String(subForm.department_id)"></option>
                </template>
            </select>
        </x-field>
        <x-field label="KODE SUB-DEPT" for="sub-code" required error="code" hint="CTH: ACC, TAX, REC, LGL, QC">
            <x-input name="code" id="sub-code" x-model="subForm.code" required maxlength="20" placeholder="ACC" />
        </x-field>
        <x-field label="NAMA SUB-DEPARTEMEN" for="sub-name" required error="name">
            <x-input name="name" id="sub-name" x-model="subForm.name" required maxlength="100" placeholder="Akuntansi & Pembukuan" />
        </x-field>
        <x-field label="DESKRIPSI / KEWENANGAN" for="sub-description" error="description">
            <x-textarea name="description" id="sub-description" x-model="subForm.description" rows="2" placeholder="Uraian dokumen sub-departemen..." />
        </x-field>
        <div class="modal__footer">
            <x-button data-modal-close>Batal (Esc)</x-button>
            <x-button type="submit" variant="primary"><span x-text="subForm.id ? 'Update Data Sub-Dept (Enter)' : 'Simpan Sub-Dept (Enter)'"></span></x-button>
        </div>
    </form>
</x-modal>
