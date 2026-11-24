{{-- "Duplikasi Box #n" (Laravel 7 duplicate_box_modal): copy everything, metadata only, or chosen document lines. --}}
<x-modal id="duplicate-box-modal" title="Duplikasi Box">
    <div class="stack">
        <p><strong x-text="'Duplikasi Box #' + (duplicate.source + 1)"></strong> — Pilih bagian yang ingin Anda salin ke Box baru:</p>
        <label class="checkbox">
            <input type="radio" value="full" x-model="duplicate.mode">
            <span><strong>Salin Lengkap (Wadah &amp; Seluruh Berkas Dokumen)</strong><br><span class="text-sm text-muted">Menduplikasi seluruh identitas box dan menyalin semua <span x-text="boxes[duplicate.source] ? boxes[duplicate.source].items.length : 0"></span> berkas dokumen.</span></span>
        </label>
        <label class="checkbox">
            <input type="radio" value="metadata_only" x-model="duplicate.mode">
            <span><strong>Salin Metadata Saja (Wadah Saja, Kosongkan Berkas)</strong><br><span class="text-sm text-muted">Menyalin sub-dept, jenis dokumen, dan kondisi fisik, tetapi mengosongkan berkas dokumen untuk diisi baru.</span></span>
        </label>
        <label class="checkbox">
            <input type="radio" value="selected_items" x-model="duplicate.mode">
            <span><strong>Pilih Berkas Dokumen Tertentu</strong><br><span class="text-sm text-muted">Pilih checklist berkas mana saja yang ingin disertakan ke box baru.</span></span>
        </label>
        <div class="stack" x-show="duplicate.mode === 'selected_items'">
            <template x-for="(it, idx) in (boxes[duplicate.source] ? boxes[duplicate.source].items : [])" :key="it.id">
                <label class="checkbox">
                    <input type="checkbox" :value="it.id" x-model.number="duplicate.selected">
                    <span x-text="(idx + 1) + '. ' + (it.document_name || 'Dokumen Tanpa Nama')"></span>
                </label>
            </template>
        </div>
    </div>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" @click="confirmDuplicate()"><x-icon name="check" />Duplikasi Sekarang</x-button>
    </x-slot:footer>
</x-modal>
