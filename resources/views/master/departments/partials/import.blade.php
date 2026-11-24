{{-- Laravel 7 frmMasterImport "Import Excel Data Master": template download, preview (nothing saved), commit.
     Alpine `masterImport` (public/js/pages/master-import.js). --}}
<div x-data="masterImport" data-preview-url="{{ route('master.departments.import.preview') }}" data-commit-url="{{ route('master.departments.import.commit') }}">
    <x-modal id="import-master" title="Import Excel Data Master" data-wide>
        <div class="stack">
            <p class="text-sm">Isi sheet <strong>Data Master</strong> pada template (satu baris = satu type berkas). Departemen / sub departemen / type berkas yang sudah ada tidak diubah dan tidak diduplikasi.</p>
            <div class="cluster">
                <x-link-button :href="route('master.departments.import.template')">Download Template</x-link-button>
                <label class="sr-only" for="import-master-file">File Excel</label>
                <input type="file" id="import-master-file" class="control" accept=".xlsx,.csv" x-ref="file" @change="pick($event)">
                <x-button variant="primary" @click="send(false)" ::disabled="!file || busy"><span x-text="busy === 'preview' ? 'Memproses...' : 'Pratinjau'"></span></x-button>
            </div>

            <div class="notice notice--success" role="status" x-show="successMessage"><div class="notice__body" x-text="successMessage"></div></div>
            <div class="notice notice--danger" role="alert" x-show="errors.length">
                <div class="notice__body">
                    <strong>Error (<span x-text="errors.length"></span>) &mdash; perbaiki file lalu pratinjau ulang:</strong>
                    <ul><template x-for="(e, i) in errors" :key="'e' + i"><li x-text="e"></li></template></ul>
                </div>
            </div>
            <div class="notice notice--warning" x-show="warnings.length">
                <div class="notice__body">
                    <strong>Peringatan (<span x-text="warnings.length"></span>):</strong>
                    <ul><template x-for="(w, i) in warnings" :key="'w' + i"><li x-text="w"></li></template></ul>
                </div>
            </div>

            <template x-if="result">
                <div class="stack">
                    <div class="cluster text-sm">
                        <span class="badge">Total Baris: <span x-text="result.summary.total_baris"></span></span>
                        <span class="badge badge--success">Departemen Baru: <span x-text="result.summary.departemen_baru"></span></span>
                        <span class="badge badge--success">Sub Departemen Baru: <span x-text="result.summary.sub_departemen_baru"></span></span>
                        <span class="badge badge--success">Type Berkas Baru: <span x-text="result.summary.dokumen_baru"></span></span>
                        <span class="badge">Sudah Ada (dilewati): <span x-text="result.summary.dokumen_sudah_ada"></span></span>
                    </div>
                    <x-table>
                        <thead><tr><th>Baris</th><th>Departemen</th><th>Sub Departemen</th><th>Nama Dokumen</th><th>Retensi</th><th>Status</th></tr></thead>
                        <tbody>
                            <template x-for="r in result.rows" :key="r.row">
                                <tr :class="r.status === 'Error' ? 'table__row--danger' : (r.status === 'Baru' ? 'table__row--success' : '')">
                                    <td data-label="Baris" class="mono" x-text="r.row"></td>
                                    <td data-label="Departemen"><span class="mono" x-text="r.dept_code"></span> <span x-text="r.dept_name"></span></td>
                                    <td data-label="Sub Departemen"><span class="mono" x-text="r.sub_code"></span> <span x-text="r.sub_name || (r.sub_code ? '' : '-')"></span></td>
                                    <td data-label="Nama Dokumen" x-text="r.doc_name || '-'"></td>
                                    <td data-label="Retensi" x-text="r.retention || '-'"></td>
                                    <td data-label="Status">
                                        <span class="badge" :class="r.status === 'Error' ? 'badge--danger' : (r.status === 'Baru' ? 'badge--success' : '')" x-text="r.status"></span>
                                        <span class="table__meta" x-text="r.message"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </x-table>
                </div>
            </template>
        </div>
        <x-slot:footer>
            <x-button data-modal-close ::disabled="busy">Tutup (Esc)</x-button>
            <x-button variant="primary" @click="send(true)" ::disabled="!canCommit() || busy"><span x-text="busy === 'commit' ? 'Menyimpan...' : 'Simpan Import'"></span></x-button>
        </x-slot:footer>
    </x-modal>
</div>
