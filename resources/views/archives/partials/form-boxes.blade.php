{{--
    Section 2 (Laravel 7 create.blade.php "2. Pengelolaan Multi-Box Arsip"): box tabs, box identity and
    document lines. Every box stays in the DOM (x-show) so all of them are submitted.
--}}
<x-card :title="$isEdit ? '2. Pengelolaan Box & Berkas Dokumen Arsip' : '2. Pengelolaan Multi-Box Arsip'">
    @if ($isDraft)
        <x-slot:actions>
            <x-button size="sm" variant="primary" @click="addBox()" title="Tambah Box Arsip Baru"><x-icon name="plus" />Tambah Box</x-button>
            <x-button size="sm" @click="openDuplicate(activeBox)" title="Duplikasi Box dengan Pilihan Granular (Semua Berkas / Metadata Saja / Berkas Terpilih)">Duplikat Box...</x-button>
            <x-button size="sm" variant="ghost" class="tone--danger" x-show="boxes.length > 1" @click="removeBox(activeBox)" title="Hapus Box yang Sedang Aktif">Hapus Box</x-button>
        </x-slot:actions>
    @endif

    <div class="stack">
        <p class="text-sm text-muted">{{ $isDraft ? 'Klik tab box untuk beralih. Anda dapat menambah atau menduplikat box secara instan.' : 'Perbarui rincian metadata box dan rincian berkas dokumen di dalamnya.' }}</p>
        <div class="cluster" role="tablist" aria-label="Daftar box" x-show="boxes.length > 1 || {{ $isEdit ? 'false' : 'true' }}">
            <template x-for="(b, bIdx) in boxes" :key="b.id">
                <button type="button" role="tab" class="btn btn--sm" :class="{ 'btn--primary': activeBox === bIdx }" :aria-selected="activeBox === bIdx"
                    @click="activeBox = bIdx" x-text="'Box ' + (bIdx + 1) + (b.title ? ' · ' + b.title.substring(0, 24) : '') + ' (' + b.items.length + ' Dokumen)'"></button>
            </template>
        </div>

        <template x-for="(box, bIdx) in boxes" :key="box.id">
            <div class="stack" x-show="activeBox === bIdx" role="tabpanel">
                <x-card class="card--muted">
                    <x-slot:title><span x-text="'BOX #' + (bIdx + 1)"></span> — Pengaturan Identitas &amp; Wadah Box</x-slot:title>
                    @if ($isDraft)
                        <x-slot:actions>
                            <x-button size="sm" x-show="bIdx > 0" @click="applyPreviousPreset(bIdx)" title="Salin sub-dept, jenis dokumen, dan kondisi fisik dari Box sebelumnya">Gunakan Pola Box Sebelumnya</x-button>
                        </x-slot:actions>
                    @endif
                    @include('archives.partials.form-box-fields')
                </x-card>
                @include('archives.partials.form-box-items')
            </div>
        </template>
    </div>
</x-card>
