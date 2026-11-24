{{-- Section 1 (Laravel 7 create_unit_section / edit_unit_section): company, department, sub-department, handover date. --}}
@php($isPic = $user->role === \App\Models\User::ROLE_PIC_DEPT)
<x-card :title="$isEdit ? '1. Identitas Unit Kerja & Penyerahan Dokumen' : '1. Identitas Unit Kerja & Tanggal Serah Terima Pengajuan'">
    <div class="form-grid form-grid--2">
        <x-field label="PERUSAHAAN ENTITAS" for="company_display" required>
            <input type="hidden" name="company_name" value="{{ old('company_name', $archive->company_name ?? \App\Services\ArchiveBoxBuilder::DEFAULT_COMPANY) }}">
            <x-input name="company_display" :value="old('company_name', $archive->company_name ?? \App\Services\ArchiveBoxBuilder::DEFAULT_COMPANY)" disabled title="Perusahaan entitas terkunci" />
        </x-field>

        <x-field :label="$isEdit ? 'DEPARTEMEN PEMILIK' : 'DEPARTEMEN PEMILIK ARSIP'" for="department_id" required error="department_id">
            @if ($isPic)
                {{-- PIC Departemen: department locked to the account (the server ignores any department_id). --}}
                <x-input name="department_display" :value="$user->department ? $user->department->code.' - '.$user->department->name : 'Departemen'" disabled title="Departemen dikunci sesuai akun Anda" />
            @else
                <select name="department_id" id="department_id" class="control" x-model="deptId" @change="onDeptChange()">
                    <option value="">-- Pilih Departemen --</option>
                    <template x-for="d in departments" :key="d.id">
                        <option :value="String(d.id)" :selected="String(d.id) === deptId" x-text="d.code + ' - ' + d.name"></option>
                    </template>
                </select>
            @endif
        </x-field>

        <x-field label="SUB-DEPARTEMEN" for="sub_department_id" optional>
            <input type="hidden" name="sub_department_id" :value="subDeptId === 'inti' ? '' : subDeptId">
            <select id="sub_department_id" class="control" x-model="subDeptId" @change="onHeaderSubDeptChange()">
                <option value="">-- Pilih Sub-Departemen --</option>
                <option value="inti">Inti Departemen</option>
                <template x-for="s in subDepartments()" :key="s.id">
                    <option :value="String(s.id)" :selected="String(s.id) === subDeptId" x-text="s.code + ' - ' + s.name"></option>
                </template>
            </select>
        </x-field>

        <x-field :label="$isEdit ? 'TGL. PENYERAHAN DOKUMEN' : 'TGL. SERAH TERIMA FISIK KE GUDANG'" for="tgl_penyerahan" required error="tgl_penyerahan"
            hint="Tanggal serah terima box fisik ke Gudang (maksimal hari ini)">
            <x-input name="tgl_penyerahan" type="date" :max="now()->toDateString()" x-model="tglPenyerahan" required />
        </x-field>
    </div>
</x-card>
