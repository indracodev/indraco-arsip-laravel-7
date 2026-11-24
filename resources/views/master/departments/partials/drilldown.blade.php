{{-- Laravel 7 "LIST BERKAS ARSIP (DRILL-DOWN)" and "frmArchiveDocDetail": boxes of a department / sub-department /
     induk (admin-only JSON, DepartmentApiController) and the documents inside one box. --}}
<x-modal id="archive-list" title="Daftar Berkas Arsip" data-wide>
    <div class="stack">
        <div>
            <strong x-text="list.title"></strong>
            <p class="text-sm text-muted" x-text="list.subtitle"></p>
        </div>
        <div class="cluster">
            <label class="sr-only" for="archive-list-search">Filter</label>
            <x-input name="archive_list_search" id="archive-list-search" type="search" x-model="list.search" placeholder="Filter no box, nama dokumen, periode..." autocomplete="off" />
            <span class="text-sm">Total: <strong x-text="listRows().length"></strong> Box Arsip</span>
        </div>
        <p x-show="list.loading" class="text-muted">Memuat data berkas arsip...</p>
        <p x-show="!list.loading && !listRows().length" class="text-muted">Tidak ada data berkas arsip yang terdaftar.</p>
        <div x-show="!list.loading && listRows().length">
            <x-table>
                <thead><tr><th>#</th><th>NO. BOX</th><th>SUB-UNIT</th><th>PERIODE / TGL SERAH</th><th>LOKASI GUDANG &amp; RAK</th><th>BERKAS DOKUMEN</th><th>STATUS</th><th class="table__actions">AKSI</th></tr></thead>
                <tbody>
                    <template x-for="(arc, idx) in listRows()" :key="arc.id">
                        <tr>
                            <td data-label="#" x-text="idx + 1"></td>
                            <td data-label="NO. BOX" class="mono table__title" x-text="arc.box_number"></td>
                            <td data-label="SUB-UNIT" x-text="arc.sub_department"></td>
                            <td data-label="PERIODE"><span x-text="arc.periode_doc"></span><span class="table__meta" x-text="'Serah: ' + arc.tgl_penyerahan"></span></td>
                            <td data-label="LOKASI"><span x-text="arc.location"></span><span class="table__meta" x-text="'Rak: ' + arc.rack_code + ' | Slot: ' + arc.slot_code"></span></td>
                            <td data-label="BERKAS"><span class="badge" x-text="arc.items_count + ' Arsip'"></span></td>
                            <td data-label="STATUS"><span class="badge" :class="'badge--' + arc.status_tone" x-text="arc.status_label"></span></td>
                            <td data-label="" class="table__actions"><x-button size="sm" @click="detail = arc; $nextTick(() => window.dmsModal.open('archive-detail'))"><x-icon name="eye" />Detail</x-button></td>
                        </tr>
                    </template>
                </tbody>
            </x-table>
        </div>
    </div>
    <x-slot:footer>
        <span class="text-sm text-muted">Klik tombol Detail untuk membuka berkas-berkas dokumen di dalam box.</span>
        <x-button data-modal-close>Tutup (Esc)</x-button>
    </x-slot:footer>
</x-modal>

<x-modal id="archive-detail" title="Rincian Butir Dokumen Box" data-wide>
    <template x-if="detail">
        <div class="stack">
            <dl class="meta-grid">
                <div><dt class="text-sm text-muted">NOMOR IDENTITAS BOX</dt><dd class="mono table__title" x-text="detail.box_number"></dd></div>
                <div><dt class="text-sm text-muted">STATUS GUDANG</dt><dd><span class="badge" :class="'badge--' + detail.status_tone" x-text="detail.status_label"></span></dd></div>
                <div><dt class="text-sm text-muted">SUB-DEPARTEMEN</dt><dd x-text="detail.sub_department"></dd></div>
                <div><dt class="text-sm text-muted">PERIODE PENGISIAN</dt><dd x-text="detail.periode_doc"></dd></div>
                <div><dt class="text-sm text-muted">TGL. PENYERAHAN</dt><dd x-text="detail.tgl_penyerahan"></dd></div>
                <div><dt class="text-sm text-muted">LOKASI PENYIMPANAN</dt><dd x-text="detail.location"></dd></div>
                <div><dt class="text-sm text-muted">RAK &amp; SLOT</dt><dd x-text="'Rak: ' + detail.rack_code + ' | Slot: ' + detail.slot_code"></dd></div>
                <div><dt class="text-sm text-muted">KONDISI WADAH</dt><dd x-text="detail.physical_condition"></dd></div>
            </dl>
            <strong>Rincian Berkas Dokumen di Dalam Box (<span x-text="detail.items.length"></span> Berkas)</strong>
            <x-table>
                <thead><tr><th>NO</th><th>NAMA BERKAS / DOKUMEN ARSIP</th><th>PERIODE (KAPAN S/D KAPAN)</th><th>KETERANGAN</th></tr></thead>
                <tbody>
                    <template x-for="it in detail.items" :key="it.item_number">
                        <tr>
                            <td data-label="NO" x-text="it.item_number"></td>
                            <td data-label="NAMA BERKAS" x-text="it.document_name"></td>
                            <td data-label="PERIODE" x-text="it.period_text || '-'"></td>
                            <td data-label="KETERANGAN" x-text="it.notes || '-'"></td>
                        </tr>
                    </template>
                    <tr x-show="!detail.items.length"><td colspan="4" data-label="">Belum ada berkas dokumen terdaftar.</td></tr>
                </tbody>
            </x-table>
        </div>
    </template>
    <x-slot:footer>
        <a class="btn btn--primary" :href="detail ? detail.url : '#'">Buka Halaman Lengkap Arsip</a>
        <x-button data-modal-close>Tutup (Esc)</x-button>
    </x-slot:footer>
</x-modal>
