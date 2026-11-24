{{-- Section 1 of the penarikan form: search boxes in the warehouse, chosen boxes as an accordion (one open at a time). --}}
<x-card title="1. Daftar Box Target Penarikan Arsip">
    <p class="text-sm text-muted">Cari dan tambahkan box arsip yang ingin ditarik. Klik baris box untuk membuka rincian dokumen (buka bergantian).</p>

    <x-field label="PENCARIAN BERKAS GUDANG UNTUK DITAMBAHKAN" for="archiveSearchInput" required error="archive_ids">
        <input class="control" id="archiveSearchInput" type="search" autocomplete="off" x-model="search" @keydown.enter.prevent @focus="searchOpen = true" @input="searchOpen = true"
            placeholder="Ketik Nomor Box (misal: IND/DM/..., BOX-...) atau Judul Berkas arsip...">
    </x-field>

    <div class="stack" x-show="searchOpen" x-cloak @click.outside="searchOpen = false">
        <template x-for="arc in results()" :key="arc.id">
            <div class="file-card">
                <div>
                    <div class="cluster text-sm">
                        <span class="mono" x-text="arc.box_number || 'NO-BOX'"></span>
                        <span class="badge badge--outline" x-text="arc.dept_code"></span>
                        <span class="badge badge--info" x-show="arc.busy">Sedang Diajukan Penarikan</span>
                        <span class="badge badge--danger" x-show="!arc.busy && arc.rejected">Pernah Ditolak</span>
                        <span class="badge badge--success" x-show="!arc.busy && isSelected(arc.id)">Sudah di List</span>
                    </div>
                    <div class="file-card__name" x-text="arc.title"></div>
                    <div class="text-sm text-muted" x-text="arc.period + ' • ' + arc.location"></div>
                </div>
                <x-button size="sm" variant="primary" @click="add(arc)" ::disabled="arc.busy || isSelected(arc.id)">Tambah</x-button>
            </div>
        </template>
        <p class="text-sm text-muted" x-show="results().length === 0">Tidak ada berkas arsip yang cocok dengan pencarian di departemen ini.</p>
    </div>

    <p class="text-sm"><strong>Daftar Box Terpilih (<span x-text="selected.length">0</span> Box)</strong> <span class="text-muted">Klik baris box untuk melihat rincian dokumen (terbuka satu per satu)</span></p>

    <x-empty-state title="Belum Ada Box Arsip yang Dipilih" message="Gunakan kolom pencarian di atas untuk menambahkan box target penarikan." icon="folder" x-show="selected.length === 0" />

    <template x-for="(arc, idx) in selected" :key="arc.id">
        <div class="file-card">
            <div class="cluster">
                <x-button size="sm" variant="ghost" @click="openIndex = openIndex === idx ? null : idx" ::aria-expanded="openIndex === idx">
                    <span class="badge" x-text="idx + 1"></span>
                    <strong x-text="arc.title"></strong>
                </x-button>
                <span class="mono text-sm" x-text="arc.box_number || 'PENOMORAN PENDING'"></span>
                <span class="text-sm text-muted" x-text="arc.location"></span>
                <span class="text-sm text-muted" x-text="arc.period"></span>
            </div>
            <x-button size="sm" variant="ghost" class="tone--danger" @click="remove(idx)" title="Hapus box ini dari daftar penarikan">Hapus</x-button>

            <div class="stack" x-show="openIndex === idx" x-cloak>
                <div class="notice notice--danger" x-show="arc.rejected && arc.rejected_note">
                    <div class="notice__body text-sm">
                        <strong>Catatan Penolakan Pengajuan Sebelumnya:</strong> <span x-text="arc.rejected_note"></span>
                        <p>*Pastikan surat permohonan dan kelengkapan dokumen telah diperbaiki sebelum mengirimkan ulang pengajuan ini.</p>
                    </div>
                </div>
                <dl class="meta-grid">
                    <div><dt>DEPARTEMEN</dt><dd x-text="arc.dept"></dd></div>
                    <div><dt>LOKASI RAK GUDANG</dt><dd x-text="arc.location"></dd></div>
                    <div><dt>PERIODE DOKUMEN</dt><dd x-text="arc.period"></dd></div>
                    <div><dt>KONDISI FISIK</dt><dd x-text="arc.condition"></dd></div>
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
