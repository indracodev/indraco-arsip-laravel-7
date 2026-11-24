{{-- Department grid (Laravel 7 "DELPHI MASTER-DETAIL DBGRID"): one tbody per department, its sub-departments
     and the "Arsip Induk & Umum" row below it when expanded. Rows come from the page config (Alpine). --}}
<x-table>
    <thead>
        <tr>
            <th>#</th>
            <th><button type="button" class="btn btn--ghost btn--sm" @click="sortBy('code')">KODE DEPT <span x-text="sortMark('code')"></span></button></th>
            <th><button type="button" class="btn btn--ghost btn--sm" @click="sortBy('name')">NAMA DEPARTEMEN <span x-text="sortMark('name')"></span></button></th>
            <th>DESKRIPSI / RUANG LINGKUP</th>
            <th>SUB-DEPARTEMEN</th>
            <th><button type="button" class="btn btn--ghost btn--sm" @click="sortBy('archives_count')">TOTAL BERKAS ARSIP <span x-text="sortMark('archives_count')"></span></button></th>
            <th class="table__actions">AKSI</th>
        </tr>
    </thead>
    <template x-for="dept in rows()" :key="dept.id">
        <tbody>
            <tr :class="dept.is_active === false && 'table__row--warning'">
                <td data-label="">
                    <x-button size="sm" class="btn--icon" @click="toggle(dept.id)" ::aria-expanded="!!expanded[dept.id]" ::title="expanded[dept.id] ? 'Tutup Sub-Departemen' : 'Buka Sub-Departemen'"><span x-text="expanded[dept.id] ? '−' : '+'"></span></x-button>
                </td>
                <td data-label="KODE DEPT" class="mono table__title" x-text="dept.code"></td>
                <td data-label="NAMA DEPARTEMEN">
                    <span class="table__title" x-text="dept.name"></span>
                    <span class="table__meta">
                        <span class="badge badge--warning" x-show="dept.code === 'FIN'">Ruang FAT Locked</span>
                        <span class="badge badge--danger" x-show="dept.is_active === false">Nonaktif</span>
                    </span>
                </td>
                <td data-label="DESKRIPSI" class="text-sm text-muted" x-text="dept.description || '-'"></td>
                <td data-label="SUB-DEPARTEMEN"><x-button size="sm" @click="toggle(dept.id)"><span x-text="dept.sub_departments.length + ' Sub-Unit'"></span></x-button></td>
                <td data-label="TOTAL BERKAS"><x-button size="sm" @click="openArchives('dept', dept)" title="Klik untuk melihat daftar seluruh berkas arsip departemen ini"><x-icon name="folder" /><span x-text="(dept.archives_count || 0) + ' Box/Berkas'"></span></x-button></td>
                <td data-label="" class="table__actions">
                    <x-button size="sm" @click="toggleActive('dept', dept)" ::class="dept.is_active === false ? 'btn--danger' : ''" ::title="dept.is_active !== false ? 'Klik untuk Nonaktifkan Departemen (Hanya Superadmin)' : 'Klik untuk Aktifkan Departemen (Hanya Superadmin)'"><span x-text="dept.is_active !== false ? 'Aktif' : 'Nonaktif'"></span></x-button>
                    <x-button size="sm" @click="manage(dept, null)" title="Kelola User PIC dan Master Dokumen Arsip Departemen ini"><x-icon name="settings" />Kelola</x-button>
                    <x-button size="sm" @click="openSub(null, dept.id)" title="Tambah Sub-Departemen untuk departemen ini"><x-icon name="plus" />Sub</x-button>
                    <x-button size="sm" @click="openDept(dept)" title="Edit Departemen"><x-icon name="pencil" />Edit</x-button>
                    <form method="POST" :action="urls.departments + '/' + dept.id">
                        @csrf @method('DELETE')
                        <x-button type="submit" size="sm" variant="danger" ::data-confirm="'Hapus departemen ' + dept.name + ' beserta seluruh konfigurasinya?'" data-confirm-title="Hapus Departemen" data-confirm-btn="Ya, Hapus" title="Hapus Departemen">Hapus</x-button>
                    </form>
                </td>
            </tr>
            <template x-if="expanded[dept.id]">
                <tr>
                    <td colspan="7" data-label="" class="text-sm">
                        <div class="cluster">
                            <strong>Daftar Sub-Departemen di bawah: <span x-text="dept.name + ' (' + dept.code + ')'"></span></strong>
                            <x-button size="sm" @click="openSub(null, dept.id)"><x-icon name="plus" />Tambah Sub-Departemen</x-button>
                        </div>
                    </td>
                </tr>
            </template>
            <template x-for="sub in (expanded[dept.id] ? dept.sub_departments : [])" :key="'s' + sub.id">
                <tr class="table__row--info">
                    <td data-label="">↳</td>
                    <td data-label="KODE SUB-DEPT" class="mono" x-text="dept.code + '-' + sub.code"></td>
                    <td data-label="NAMA SUB-DEPARTEMEN">
                        <span x-text="sub.name"></span>
                        <span class="badge badge--danger" x-show="sub.is_active === false">Nonaktif</span>
                    </td>
                    <td data-label="DESKRIPSI / KEWENANGAN" class="text-sm text-muted" x-text="sub.description || '-'"></td>
                    <td data-label=""></td>
                    <td data-label="BERKAS ARSIP"><x-button size="sm" @click="openArchives('sub', sub, dept)" title="Klik untuk melihat berkas arsip sub-departemen ini"><span x-text="(sub.archives_count || 0) + ' Berkas'"></span></x-button></td>
                    <td data-label="" class="table__actions">
                        <x-button size="sm" @click="toggleActive('sub', sub)" ::class="sub.is_active === false ? 'btn--danger' : ''" ::title="sub.is_active !== false ? 'Klik untuk Nonaktifkan Sub-Dept (Hanya Superadmin)' : 'Klik untuk Aktifkan Sub-Dept (Hanya Superadmin)'"><span x-text="sub.is_active !== false ? 'Aktif' : 'Nonaktif'"></span></x-button>
                        <x-button size="sm" @click="manage(dept, sub)" title="Kelola Master Dokumen Sub-Departemen ini"><x-icon name="settings" />Kelola</x-button>
                        <x-button size="sm" @click="openSub(sub, dept.id)"><x-icon name="pencil" />Edit</x-button>
                        <form method="POST" :action="urls.subDepartments + '/' + sub.id">
                            @csrf @method('DELETE')
                            <x-button type="submit" size="sm" variant="danger" ::data-confirm="'Hapus sub-departemen ' + sub.name + '?'" data-confirm-title="Hapus Sub-Departemen" data-confirm-btn="Ya, Hapus">Hapus</x-button>
                        </form>
                    </td>
                </tr>
            </template>
            <template x-if="expanded[dept.id] && dept.unassigned_archives_count > 0">
                <tr class="table__row--warning">
                    <td data-label="">↳</td>
                    <td data-label="KODE SUB-DEPT" class="mono" x-text="dept.code + '-GEN'"></td>
                    <td data-label="NAMA SUB-DEPARTEMEN">Arsip Induk &amp; Umum (Non Sub-Unit) <span class="badge">Induk / General</span></td>
                    <td data-label="DESKRIPSI / KEWENANGAN" class="text-sm text-muted">Berkas arsip tingkat induk departemen yang belum dialokasikan ke sub-unit tertentu</td>
                    <td data-label=""></td>
                    <td data-label="BERKAS ARSIP"><x-button size="sm" @click="openArchives('unassigned', dept)" title="Klik untuk melihat berkas arsip induk/umum departemen ini"><span x-text="dept.unassigned_archives_count + ' Berkas'"></span></x-button></td>
                    <td data-label="" class="table__actions"><x-button size="sm" @click="manage(dept, { id: '', code: 'GEN', name: 'Arsip Induk & Umum' })" title="Kelola Master Dokumen Arsip Induk"><x-icon name="settings" />Kelola</x-button></td>
                </tr>
            </template>
            <template x-if="expanded[dept.id] && !dept.sub_departments.length && !(dept.unassigned_archives_count > 0)">
                <tr>
                    <td colspan="7" data-label="" class="text-sm text-muted">Belum ada sub-departemen terdaftar untuk departemen ini. <x-button size="sm" variant="ghost" @click="openSub(null, dept.id)">+ Tambah Sekarang</x-button></td>
                </tr>
            </template>
        </tbody>
    </template>
    <tbody x-show="!rows().length">
        <tr><td colspan="7" data-label=""><x-empty-state title="Tidak ada data departemen yang cocok dengan filter pencarian." /></td></tr>
    </tbody>
</x-table>
<p class="text-sm text-muted">Menampilkan <strong x-text="rows().length"></strong> dari <strong x-text="items.length"></strong> departemen utama</p>
