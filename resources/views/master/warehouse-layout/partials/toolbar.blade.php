{{-- Laravel 7 canvas_toolbar: search (objects in the browser, boxes / slots on the server), department
     filter, full screen; add objects, canvas size, zoom; colour legend and keyboard help. --}}
<div class="card wl-toolbar">
    <div class="wl-toolbar__row">
        <div class="wl-search">
            <label class="sr-only" for="wl-search">Cari Gudang, Rak, Box, atau No. Arsip</label>
            <input class="control" id="wl-search" type="search" autocomplete="off" placeholder="Cari Gudang, Rak, Box, atau No. Arsip... (Ctrl+F)">
            <div class="wl-search__list" id="wl-search-list" role="listbox" hidden></div>
        </div>
        <label class="sr-only" for="wl-filter">Filter departemen</label>
        <select class="control wl-filter" id="wl-filter">
            <option value="">-- Semua Departemen --</option>
        </select>
        <x-button data-act="fullscreen"><span data-f="fullscreen">1 Layar Penuh (Fullscreen)</span></x-button>
    </div>
    <div class="wl-toolbar__row">
        <x-button variant="primary" data-act="add-room"><x-icon name="plus" />Tambah Gudang (Square)</x-button>
        <x-button variant="primary" data-act="add-rack"><x-icon name="plus" />Tambah Rak (Rectangle)</x-button>
        <span class="push-end"></span>
        <x-button data-act="canvas-size" title="Ubah Dimensi (Lebar & Tinggi) Canvas Workspace Layout">Canvas: <strong class="mono" data-f="canvas-size">950x750px</strong></x-button>
        <div class="cluster wl-zoom">
            <x-button size="sm" data-act="zoom-out" title="Zoom Out" aria-label="Zoom Out">−</x-button>
            <span class="mono" data-f="zoom">100%</span>
            <x-button size="sm" data-act="zoom-in" title="Zoom In" aria-label="Zoom In">+</x-button>
            <x-button size="sm" data-act="zoom-reset" title="Reset Scale">1:1</x-button>
        </div>
    </div>
    <div class="wl-legend text-sm">
        <span class="text-muted">Indikator Warna Rak (Default Kapasitas):</span>
        <span><i class="wl-dot wl-dot--0"></i>Hijau (0% - 50%)</span>
        <span><i class="wl-dot wl-dot--1"></i>Kuning (51% - 80%)</span>
        <span><i class="wl-dot wl-dot--2"></i>Orange (81% - 90%)</span>
        <span><i class="wl-dot wl-dot--3"></i>Merah (91% - 100%)</span>
        <span><i class="wl-dot wl-dot--custom"></i>Custom Color</span>
        <span class="text-muted">Petunjuk: Mouse Drag / Panah (↑ ↓ ← →) | Shift + Klik: Multi-Select | Rotate (Key: R) | Hapus (Key: Delete) | Copy &amp; Paste (Ctrl+C / Ctrl+V) | Klik 2x Rak: Denah Slot</span>
    </div>
</div>
