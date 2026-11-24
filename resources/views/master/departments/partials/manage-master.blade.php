{{-- Kelola Departemen, tab 2: Master Berkas & Dokumen Baku (search, sub-unit filter, batch add, single add, list). --}}
<div class="stack">
    <div class="cluster">
        <label class="sr-only" for="master-search">Cari Master Berkas</label>
        <x-input name="master_search" id="master-search" type="search" x-model="masterSearch" placeholder="Cari Master Berkas..." autocomplete="off" />
        <label class="sr-only" for="master-sub-filter">Sub-Unit</label>
        <select id="master-sub-filter" class="control" x-model="subFilter">
            <option value="">Semua Sub-Unit</option>
            <option value="induk_only">Hanya Induk / General</option>
            <template x-for="s in subs" :key="s.id"><option :value="String(s.id)" x-text="s.code + ' - ' + s.name"></option></template>
        </select>
        <x-button @click="showBatch = !showBatch; showSingle = false" title="Tambah banyak master arsip sekaligus dengan menekan Enter per baris"><span x-text="showBatch ? 'Tutup Batch' : '+ Tambah Batch (Multi-Line)'"></span></x-button>
        <x-button variant="primary" @click="showSingle = !showSingle; showBatch = false"><span x-text="showSingle ? 'Tutup Satuan' : '+ Tambah Satuan'"></span></x-button>
    </div>

    <form class="stack card card__body" x-show="showBatch" @submit.prevent="saveBatch()" data-no-lock>
        <strong class="text-sm">Form Input Batch Master Berkas (1 Baris / Enter = 1 Master Berkas)</strong>
        <x-field label="DAFTAR NAMA BERKAS ARSIP (Ketik atau Tempel / Paste di bawah ini):" for="master-batch-text" hint="Setiap baris teks di atas akan langsung disimpan otomatis sebagai master berkas baku.">
            <textarea id="master-batch-text" class="control" rows="7" x-model="batch.text" placeholder="Contoh:&#10;PL, DO, GDO, GI, DIT, SIK&#10;Deklarasi Insurance, IM&#10;Dokumen umum logistik"></textarea>
        </x-field>
        <span class="badge badge--info" x-text="batchLines().length + ' Berkas Terdeteksi'"></span>
        <div class="form-grid form-grid--2">
            <x-field label="SUB-DEPARTEMEN TUJUAN" for="master-batch-sub">
                <select id="master-batch-sub" class="control" x-model="batch.sub">
                    <option value="">-- Berlaku untuk Semua (Induk) --</option>
                    <template x-for="s in subs" :key="s.id"><option :value="String(s.id)" x-text="s.code + ' - ' + s.name"></option></template>
                </select>
            </x-field>
            <x-field label="KATEGORI DOKUMEN" for="master-batch-type"><x-select name="batch_type" id="master-batch-type" :options="array_merge(['UMUM' => $docTypes['UMUM']], $docTypes)" x-model="batch.type" /></x-field>
            <x-field label="MASA SIMPAN STANDAR (TAHUN)" for="master-batch-retention"><x-input name="batch_retention" id="master-batch-retention" type="number" min="1" max="100" x-model="batch.retention" /></x-field>
        </div>
        <div class="cluster">
            <x-button @click="showBatch = false">Batal</x-button>
            <x-button type="submit" variant="primary" ::disabled="busy || !batchLines().length"><span x-text="'Simpan ' + batchLines().length + ' Master Berkas (Batch)'"></span></x-button>
        </div>
    </form>

    <form class="stack card card__body" x-show="showSingle" @submit.prevent="saveMaster()" data-no-lock>
        <strong class="text-sm">Form Tambah Master Dokumen / Berkas Arsip Tunggal <span class="text-muted">— Standar berkas yang akan muncul di dropdown Katalog Arsip</span></strong>
        <div class="form-grid form-grid--2">
            <x-field label="NAMA DOKUMEN / BERKAS ARSIP" for="master-new-name" required><x-input name="new_name" id="master-new-name" x-model="single.name" placeholder="Contoh: Faktur Pajak Masukan, Rekap Kasir, Surat Jalan" required maxlength="255" /></x-field>
            <x-field label="KODE DOKUMEN" for="master-new-code" optional><x-input name="new_code" id="master-new-code" x-model="single.code" placeholder="Contoh: DOC-FIN-01" maxlength="50" /></x-field>
            <x-field label="KATEGORI DOKUMEN" for="master-new-type"><x-select name="new_type" id="master-new-type" :options="$docTypes" x-model="single.type" /></x-field>
            <x-field label="SUB-DEPARTEMEN" for="master-new-sub" optional>
                <select id="master-new-sub" class="control" x-model="single.sub">
                    <option value="">-- Berlaku untuk Semua (Induk) --</option>
                    <template x-for="s in subs" :key="s.id"><option :value="String(s.id)" x-text="s.code + ' - ' + s.name"></option></template>
                </select>
            </x-field>
            <x-field label="MASA SIMPAN STANDAR (TAHUN)" for="master-new-retention"><x-input name="new_retention" id="master-new-retention" type="number" min="1" max="100" x-model="single.retention" /></x-field>
            <x-field label="KETERANGAN / DESKRIPSI" for="master-new-description"><x-input name="new_description" id="master-new-description" x-model="single.description" placeholder="Deskripsi atau petunjuk isi dokumen arsip..." /></x-field>
        </div>
        <div class="cluster">
            <x-button @click="showSingle = false">Batal</x-button>
            <x-button type="submit" variant="primary" ::disabled="busy">Simpan Master Berkas</x-button>
        </div>
    </form>

    <x-table>
        <thead><tr><th>#</th><th>KODE</th><th>NAMA BERKAS / DOKUMEN ARSIP</th><th>KATEGORI</th><th>SUB-UNIT</th><th>MASA SIMPAN</th><th>KETERANGAN</th><th class="table__actions">AKSI</th></tr></thead>
        <tbody>
            <template x-for="(m, idx) in masterRows()" :key="m.id">
                <tr>
                    <td data-label="#" x-text="idx + 1"></td>
                    <td data-label="KODE" class="mono" x-text="m.code || '-'"></td>
                    <td data-label="NAMA BERKAS" class="table__title" x-text="m.name"></td>
                    <td data-label="KATEGORI" x-text="m.document_type || '-'"></td>
                    <td data-label="SUB-UNIT" x-text="m.sub_department ? m.sub_department.name : 'Induk / Semua'"></td>
                    <td data-label="MASA SIMPAN" x-text="(m.retention_years || 5) + ' Thn'"></td>
                    <td data-label="KETERANGAN" class="text-sm text-muted" x-text="m.description || '-'"></td>
                    <td data-label="" class="table__actions">
                        <x-button size="sm" @click="editMaster(m)" title="Edit master berkas ini"><x-icon name="pencil" />Edit</x-button>
                        <x-button size="sm" variant="danger" @click="deleteMaster(m)" title="Hapus master berkas ini">Hapus</x-button>
                    </td>
                </tr>
            </template>
            <tr x-show="!masterRows().length"><td colspan="8" data-label="" class="text-muted">Belum ada master dokumen / berkas arsip yang terdaftar.</td></tr>
        </tbody>
    </x-table>
</div>
