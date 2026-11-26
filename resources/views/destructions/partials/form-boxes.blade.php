{{-- Section 1 of the propose / extend forms (Laravel 7 destructions/propose + extend): search candidate
     boxes, chosen boxes as an accordion (one open at a time). $kind = destruction | extension. --}}
@php($isExtension = $kind === 'extension')
<x-card :title="$isExtension ? '1. Daftar Box Target Perpanjangan Masa Simpan' : '1. Daftar Box Target Pemusnahan Berkas Fisik'">
    <p class="text-sm text-muted">Cari dan tambahkan box arsip yang ingin {{ $isExtension ? 'diperpanjang' : 'dimusnahkan' }}. Klik baris box untuk membuka rincian dokumen (buka bergantian).</p>

    <x-field label="PENCARIAN BERKAS GUDANG UNTUK DITAMBAHKAN" for="archiveSearchInput" required error="archive_ids">
        <input class="control" id="archiveSearchInput" type="search" autocomplete="off" x-model="search" @keydown.enter.prevent @focus="searchOpen = true" @input="searchOpen = true"
            placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas untuk {{ $isExtension ? 'perpanjangan masa simpan' : 'dimusnahkan' }}...">
    </x-field>

    <div class="stack" x-show="searchOpen" x-cloak @click.outside="searchOpen = false">
        <template x-for="arc in results()" :key="arc.id">
            <div class="file-card">
                <div>
                    <div class="cluster text-sm">
                        <span class="mono" x-text="arc.box_number || 'NO-BOX'"></span>
                        <span class="badge badge--outline" x-text="arc.dept_code"></span>
                        <span class="badge badge--success" x-show="isSelected(arc.id)">Sudah di List</span>
                        @unless ($isExtension)<span class="badge badge--warning" x-show="arc.early" x-text="arc.early"></span>@endunless
                    </div>
                    <div class="file-card__name" x-text="arc.title"></div>
                    <div class="text-sm text-muted" x-text="'Masa Simpan: ' + arc.retention + ' (Expiry: ' + arc.expiry + ') • ' + arc.location"></div>
                </div>
                <x-button size="sm" variant="primary" @click="add(arc)" ::disabled="isSelected(arc.id)">Tambah</x-button>
            </div>
        </template>
        <p class="text-sm text-muted" x-show="results().length === 0">Tidak ada berkas arsip yang cocok dengan pencarian Anda.</p>
    </div>

    <p class="text-sm"><strong>Daftar Box Terpilih (<span x-text="selected.length">0</span> Box)</strong> <span class="text-muted">Klik baris box untuk melihat rincian dokumen (terbuka satu per satu)</span></p>

    <x-empty-state title="Belum Ada Box Arsip yang Dipilih" :message="'Gunakan kolom pencarian di atas untuk menambahkan box target '.($isExtension ? 'perpanjangan.' : 'pemusnahan.')" x-show="selected.length === 0" />

    <template x-for="(arc, idx) in selected" :key="arc.id">
        <div class="file-card">
            <div class="cluster">
                <x-button size="sm" @click="openIndex = openIndex === idx ? null : idx" ::aria-expanded="openIndex === idx">
                    <span class="badge" x-text="idx + 1"></span>
                    <strong x-text="arc.title"></strong>
                </x-button>
                <span class="mono text-sm" x-text="arc.box_number || 'PENOMORAN PENDING'"></span>
                @unless ($isExtension)<span class="badge badge--warning" x-show="arc.early" x-text="arc.early"></span>@endunless
                <span class="text-sm text-muted" x-text="arc.location"></span>
                <span class="text-sm text-muted" x-text="{{ $isExtension ? "'Expiry: ' + arc.expiry" : 'arc.period' }}"></span>
            </div>
            <x-button size="sm" class="tone--danger" @click="remove(idx)" :title="'Hapus box ini dari daftar '.($isExtension ? 'perpanjangan' : 'pemusnahan')">Hapus</x-button>

            <div class="stack" x-show="openIndex === idx" x-cloak>
                <dl class="meta-grid">
                    <div><dt>DEPARTEMEN</dt><dd x-text="arc.dept"></dd></div>
                    <div><dt>LOKASI RAK GUDANG</dt><dd x-text="arc.location"></dd></div>
                    @if ($isExtension)
                        <div><dt>MASA SIMPAN SAAT INI</dt><dd x-text="arc.retention"></dd></div>
                        <div><dt>JATUH TEMPO (EXPIRY)</dt><dd x-text="arc.expiry"></dd></div>
                    @else
                        <div><dt>PERIODE BERKAS</dt><dd x-text="arc.period"></dd></div>
                        <div><dt>STATUS / EXPIRY</dt><dd x-text="arc.expiry"></dd></div>
                    @endif
                </dl>
                <p class="text-sm"><strong>Rincian Berkas Dokumen di Dalam Box (<span x-text="arc.items.length"></span> Dokumen)</strong></p>
                <x-table>
                    <thead><tr><th>No</th><th>Nama Berkas / Dokumen</th><th>Periode</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        <template x-for="item in arc.items" :key="item.number + item.name">
                            <tr><td data-label="No" x-text="item.number"></td><td data-label="Nama Berkas / Dokumen" x-text="item.name"></td>
                                <td data-label="Periode" x-text="item.period"></td><td data-label="Keterangan" x-text="item.notes"></td></tr>
                        </template>
                        <tr x-show="arc.items.length === 0"><td colspan="4" class="text-muted">Tidak ada rincian dokumen tersimpan untuk box arsip ini.</td></tr>
                    </tbody>
                </x-table>
            </div>
        </div>
    </template>
</x-card>
