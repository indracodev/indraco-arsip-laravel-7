{{-- Document lines of one box (inside x-for "box, bIdx"), Laravel 7 "Rincian Berkas Dokumen dalam Box #n". Ctrl+V of Excel rows adds lines. --}}
<div class="stack">
    <div class="cluster">
        <h3>Rincian Berkas Dokumen dalam Box #<span x-text="bIdx + 1"></span></h3>
        <span class="topbar__spacer"></span>
        <x-button size="sm" variant="primary" @click="addItems(bIdx, 1)" title="Tambah 1 Baris Dokumen"><x-icon name="plus" />Tambah Berkas</x-button>
        <x-button size="sm" @click="addItems(bIdx, 5)" title="Tambah 5 Baris Dokumen Sekaligus untuk Entri Cepat">Tambah 5 Baris</x-button>
    </div>
    <p class="text-sm text-muted">{{ $isEdit ? 'Setiap box berisi banyak berkas arsip. Anda dapat menyalin data baris dari Excel dan langsung menempelnya (Ctrl+V) di tabel.' : 'Masukkan berkas fisik di dalam box ini. Periode box dihitung otomatis dari rentang berkas di bawah.' }}</p>
    <div @paste="pasteRows($event, bIdx)">
        <x-table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA DOKUMEN / BERKAS ARSIP <span class="field__required">*</span></th>
                    <th>PERIODE MULAI <span class="field__required">*</span></th>
                    <th>PERIODE SELESAI <span class="field__required">*</span></th>
                    <th>KETERANGAN <span class="field__required">*</span></th>
                    <th class="table__actions">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(item, iIdx) in box.items" :key="item.id">
                    <tr :class="{ 'table__row--danger': duplicateInfo(box, item, iIdx) }">
                        <td data-label="NO"><span x-text="iIdx + 1"></span> <span class="badge badge--danger" x-show="duplicateInfo(box, item, iIdx)" title="Data kembar identik dengan baris lain di box ini">DUPLIKAT</span></td>
                        <td data-label="NAMA DOKUMEN" class="table__wide">
                            <select class="control" :id="'item_name_' + bIdx + '_' + iIdx" :name="'boxes[' + bIdx + '][items][' + iIdx + '][document_name]'"
                                x-model="item.document_name" @change="onMasterDocChange(box, item)" aria-label="Nama dokumen">
                                <option value="" x-text="masterFor(box).length ? '-- Pilih dari Master Berkas Arsip --' : '-- Tidak ada master berkas untuk sub-departemen ini --'"></option>
                                <template x-for="m in masterFor(box)" :key="m.id">
                                    <option :value="m.name" :selected="m.name === item.document_name" x-text="masterLabel(m)"></option>
                                </template>
                                <template x-if="isCustomName(box, item)">
                                    <option :value="item.document_name" selected x-text="item.document_name"></option>
                                </template>
                            </select>
                        </td>
                        <td data-label="PERIODE MULAI"><input type="month" class="control" :id="'item_start_' + bIdx + '_' + iIdx" :name="'boxes[' + bIdx + '][items][' + iIdx + '][period_start]'" x-model="item.period_start" :max="currentMonth" @change="validatePeriod(item)" aria-label="Periode mulai"></td>
                        <td data-label="PERIODE SELESAI"><input type="month" class="control" :id="'item_end_' + bIdx + '_' + iIdx" :name="'boxes[' + bIdx + '][items][' + iIdx + '][period_end]'" x-model="item.period_end" :max="currentMonth" :min="item.period_start || null" @change="validatePeriod(item)" aria-label="Periode selesai"></td>
                        <td data-label="KETERANGAN">
                            <textarea class="control" rows="1" maxlength="255" :id="'item_notes_' + bIdx + '_' + iIdx" :name="'boxes[' + bIdx + '][items][' + iIdx + '][notes]'" x-model="item.notes" placeholder="No. berkas fisik / catatan" aria-label="Keterangan"></textarea>
                            <span class="field__error" x-show="duplicateInfo(box, item, iIdx)" x-text="'Kembar identik dgn ' + duplicateInfo(box, item, iIdx) + ' (Nama, Periode & Ket. sama)'"></span>
                        </td>
                        <td data-label="" class="table__actions">
                            <x-button size="sm" variant="ghost" @click="duplicateItem(bIdx, iIdx)" title="Salin baris ini">Salin</x-button>
                            <x-button size="sm" variant="ghost" class="tone--danger" @click="removeItem(bIdx, iIdx)" title="Hapus baris dokumen ini">Hapus</x-button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-table>
    </div>
    <div class="cluster text-sm">
        <span>Total Berkas di Box #<span x-text="bIdx + 1"></span>: <strong x-text="box.items.length"></strong> Dokumen</span>
        <span class="topbar__spacer"></span>
        <span class="text-muted">Rentang Periode Terhitung: <strong x-text="calculatedRange(box)"></strong></span>
    </div>
</div>
