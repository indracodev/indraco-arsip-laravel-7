{{-- Laravel 7 rack_visualizer_modal: 5 LVL x (top 10 + bottom 10) slots of ONE rack (built by
     warehouse-layout-rack.js when opened), filter / search, slot inspector with archive link, label,
     release, slot active switch (Super Admin) and "Pilih Antrean Arsip" placement. --}}
<x-modal id="wl-rack" title="Denah Rak">
    <div class="stack">
        <div class="wl-row text-sm">
            <span><span data-f="rack-sector"></span> • <span data-f="rack-alloc"></span></span>
            <span class="wl-stats">
                <span class="tone--success">Kosong (<strong data-f="rk-empty"></strong>)</span>
                <span class="tone--warning">Terisi (<strong data-f="rk-filled"></strong>)</span>
                <span class="tone--danger">Expired (<strong data-f="rk-expired"></strong>)</span>
            </span>
        </div>
        <div class="cluster">
            <div class="cluster" role="group" aria-label="Filter slot">
                <x-button size="sm" data-filter="all" aria-pressed="true">Semua (100)</x-button>
                <x-button size="sm" data-filter="empty" aria-pressed="false">Kosong</x-button>
                <x-button size="sm" data-filter="filled" aria-pressed="false">Terisi</x-button>
                <x-button size="sm" data-filter="expired" aria-pressed="false">Expired</x-button>
            </div>
            <label class="sr-only" for="wl-slot-search">Cari slot</label>
            <input class="control wl-slot-search" id="wl-slot-search" type="search" placeholder="Cari No. Box / Judul / Slot..." autocomplete="off">
        </div>
        <div class="wl-rack">
            <div class="wl-levels" id="wl-levels"><p class="text-muted" role="status">Memuat slot rak...</p></div>
            <div class="wl-slot card card--muted" id="wl-slot">
                <div data-slot="none">
                    <strong>Inspector Slot Rak</strong>
                    <p class="text-sm text-muted">Klik salah satu dari 100 slot box (TB 30g) pada denah di sebelah kiri untuk melihat rincian dokumen dan opsi cetak label.</p>
                </div>
                <div class="stack" data-slot="any" hidden>
                    <span class="text-sm text-muted" data-f="slot-where"></span>
                    <div class="wl-row"><strong class="mono" data-f="slot-title"></strong><span class="badge" data-f="slot-badge"></span></div>
                    <dl class="wl-dl text-sm" data-slot="filled" hidden>
                        <dt>No. Box:</dt><dd class="mono" data-f="slot-box"></dd>
                        <dt>Judul Dokumen:</dt><dd data-f="slot-doc"></dd>
                        <dt>Periode:</dt><dd data-f="slot-period"></dd>
                        <dt>Departemen:</dt><dd data-f="slot-dept"></dd>
                        <dt data-slot="sub">Sub-Dept:</dt><dd data-slot="sub" data-f="slot-sub"></dd>
                        <dt>Masa Simpan:</dt><dd data-f="slot-expiry"></dd>
                    </dl>
                    <p class="notice notice--danger text-sm" data-slot="expired" hidden><strong>Masa Simpan Kedaluwarsa</strong> Arsip pada box ini telah melewati masa retensi dan dapat diproses untuk pemusnahan.</p>
                    <div class="stack" data-slot="filled" hidden>
                        <a class="btn btn--sm" data-f="slot-link" href="#" target="_blank" rel="noopener">Buka Detail Halaman Arsip</a>
                        <a class="btn btn--sm" data-f="slot-label" href="#" target="_blank" rel="noopener">Cetak Label Box Form A5 (TB 30g)</a>
                        <x-button size="sm" variant="danger" data-act="slot-release">Kosongkan / Lepas Box Dari Slot Ini</x-button>
                    </div>
                    <div class="stack" data-slot="empty" hidden>
                        <strong class="text-sm">Isi Dokumen ke Slot Ini</strong>
                        <p class="notice notice--warning text-sm" data-f="slot-only" hidden></p>
                        <x-field label="Pilih Dokumen Arsip Terdaftar:" for="wl-queue">
                            <select class="control" id="wl-queue"><option value="">-- Pilih Dari Antrean Arsip --</option></select>
                        </x-field>
                        <div class="text-sm wl-queue-info" data-f="queue-info" hidden></div>
                        <p class="text-sm text-muted" data-f="queue-empty" hidden>Tidak ada antrean arsip yang belum memiliki rak.</p>
                        <x-button variant="primary" size="sm" data-act="slot-assign" disabled><span data-f="assign-label">Alokasikan Box ke Slot</span></x-button>
                    </div>
                    @if ($isAdmin)
                        <x-button size="sm" data-act="slot-toggle"><span data-f="toggle-label"></span></x-button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <x-slot:footer><x-button data-modal-close>Tutup Modal Denah Rak</x-button></x-slot:footer>
</x-modal>
