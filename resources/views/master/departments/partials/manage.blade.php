{{-- Laravel 7 frmDeptManager "Kelola Unit & Master Dokumen": tab 1 User PIC Departemen, tab 2 Master Berkas & Dokumen
     Baku. Alpine `deptManage` (public/js/pages/master-department-manage.js), opened by the `dept-manage` window event. --}}
@php($docTypes = \App\Http\Requests\MasterArchiveRequest::DOCUMENT_TYPES)
<div x-data="deptManage" @dept-manage.window="open($event.detail)">
    <x-modal id="dept-manage" title="Kelola Unit & Master Dokumen" data-wide>
        <div class="stack" x-show="dept">
            <div class="cluster text-sm">
                <span>Unit Kerja: <strong x-text="dept ? dept.code + ' - ' + dept.name : ''"></strong></span>
                <span x-show="sub" class="badge badge--info" x-text="sub ? 'Sub: ' + sub.name : ''"></span>
                <span>Masa Simpan Default: <strong x-text="defaultRetention() + ' Tahun'"></strong></span>
                <span class="badge" x-text="pics.length + ' User PIC'"></span>
                <span class="badge" x-text="masters.length + ' Master Berkas'"></span>
            </div>
            <div class="cluster" role="tablist">
                <x-button role="tab" ::class="tab === 'pic' && 'btn--primary'" ::aria-selected="tab === 'pic'" @click="tab = 'pic'">1. User PIC Departemen <span class="badge" x-text="pics.length"></span></x-button>
                <x-button role="tab" ::class="tab === 'master' && 'btn--primary'" ::aria-selected="tab === 'master'" @click="tab = 'master'">2. Master Berkas &amp; Dokumen Baku <span class="badge" x-text="masters.length"></span></x-button>
            </div>
            <p x-show="loading" class="text-muted">Memuat Data Departemen...</p>
            <div x-show="tab === 'pic'">@include('master.departments.partials.manage-pic')</div>
            <div x-show="tab === 'master'">@include('master.departments.partials.manage-master', ['docTypes' => $docTypes])</div>
        </div>
        <x-slot:footer>
            <span class="text-sm text-muted">Perubahan data PIC &amp; Master Berkas langsung aktif realtime di seluruh sistem.</span>
            <x-button variant="primary" data-modal-close>Selesai &amp; Tutup</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal id="pic-password" title="Ganti Password PIC">
        <form class="stack" @submit.prevent="savePassword()" data-no-lock>
            <p class="text-sm" x-text="passwordUser ? passwordUser.name + ' (' + passwordUser.email + ')' : ''"></p>
            <x-field label="PASSWORD BARU" for="pic-new-password" required hint="Minimal 6 karakter">
                <x-input name="pic_new_password" id="pic-new-password" type="password" x-model="newPassword" minlength="6" required autocomplete="new-password" />
            </x-field>
            <div class="modal__footer">
                <x-button data-modal-close>Batal</x-button>
                <x-button type="submit" variant="primary" ::disabled="busy">Simpan Password</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal id="master-edit" title="Edit Master Dokumen">
        <form class="stack" @submit.prevent="updateMaster()" data-no-lock>
            <template x-if="editing">
                <div class="stack">
                    <x-field label="NAMA DOKUMEN / BERKAS ARSIP" for="master-edit-name" required>
                        <x-input name="edit_name" id="master-edit-name" x-model="editing.name" required maxlength="255" />
                    </x-field>
                    <div class="form-grid form-grid--2">
                        <x-field label="KODE DOKUMEN" for="master-edit-code"><x-input name="edit_code" id="master-edit-code" x-model="editing.code" maxlength="50" /></x-field>
                        <x-field label="MASA SIMPAN (THN)" for="master-edit-retention"><x-input name="edit_retention" id="master-edit-retention" type="number" min="1" max="100" x-model="editing.retention_years" /></x-field>
                    </div>
                    <x-field label="KATEGORI DOKUMEN" for="master-edit-type"><x-select name="edit_type" id="master-edit-type" :options="$docTypes" x-model="editing.document_type" /></x-field>
                    <x-field label="KETERANGAN" for="master-edit-description"><x-input name="edit_description" id="master-edit-description" x-model="editing.description" /></x-field>
                </div>
            </template>
            <div class="modal__footer">
                <x-button data-modal-close>Batal</x-button>
                <x-button type="submit" variant="primary" ::disabled="busy">Simpan Perubahan</x-button>
            </div>
        </form>
    </x-modal>
</div>
