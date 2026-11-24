{{-- Laravel 7 inspector drawer: selected object, lock, allowed departments, geometry, capacity, slot
     counts, booking and actions; batch card when several objects are selected. Filled by
     warehouse-layout-panel.js ([data-f] text, [data-show] visibility, [data-act] buttons). --}}
<aside class="card wl-panel" id="wl-panel" aria-live="polite">
    <div class="wl-panel__empty" data-show="none">
        <strong>Pilih Object Gudang / Rak</strong>
        <p class="text-muted text-sm">Klik pada object Gudang atau Rak di canvas untuk melihat rincian, menggeser posisi, mengubah ukuran, warna, atau aksi lainnya.</p>
    </div>

    <div class="stack" data-show="one" hidden>
        <div class="wl-batch stack" data-show="many" hidden>
            <div class="cluster"><strong>Batch Setup (Multi-Select)</strong><span class="badge badge--info" data-f="batch-count"></span></div>
            <p class="text-sm text-muted">Ubah properti seluruh objek terpilih serentak:</p>
            <div class="wl-actions wl-actions--2">
                <x-button size="sm" data-act="batch-unlock">Buka Kunci</x-button>
                <x-button size="sm" data-act="batch-lock">Kunci Semua</x-button>
                <x-button size="sm" data-act="batch-resize">Ubah Ukuran</x-button>
                @if ($isAdmin)
                    <x-button size="sm" data-act="batch-dept">Set Dept</x-button>
                @endif
                <x-button size="sm" data-act="batch-color">Set Warna Massal</x-button>
                <x-button size="sm" data-act="align-h">Rapatkan Baris</x-button>
                <x-button size="sm" data-act="align-v">Rapatkan Kolom</x-button>
            </div>
        </div>

        <div class="wl-panel__head">
            <div>
                <div class="cluster"><span class="badge badge--outline" data-f="type"></span><i class="wl-swatch" data-f="swatch"></i></div>
                <h2 class="wl-panel__title mono" data-f="code"></h2>
                <span class="text-sm text-muted" data-f="sector"></span>
            </div>
            <div class="cluster">
                <x-button size="sm" data-act="edit" title="Edit Detail Object (Nama, Sektor, Warna, Kapasitas, Geometri)"><x-icon name="pencil" />Edit</x-button>
                <x-button variant="ghost" size="sm" class="btn--icon" data-act="deselect" aria-label="Tutup"><x-icon name="x" /></x-button>
            </div>
        </div>

        <div class="wl-row">
            <span><span class="text-muted" data-f="lock-label"></span> <strong data-f="lock"></strong></span>
            <x-button size="sm" data-act="lock"><span data-f="lock-btn"></span></x-button>
        </div>

        <div class="wl-row">
            <span class="text-muted">Izin Penempatan Box:</span>
            <span class="wl-chips" data-f="allowed"></span>
            @if ($isAdmin)
                <x-button size="sm" variant="ghost" data-act="edit">Atur Dept</x-button>
            @endif
        </div>

        <div class="wl-row text-sm">
            <span>Posisi: <span class="mono" data-f="pos"></span></span>
            <span>Ukuran: <span class="mono" data-f="size"></span></span>
        </div>

        <div class="stack" data-show="rack">
            <div>
                <div class="wl-row text-sm"><span class="text-muted">Kapasitas Terisi:</span><strong data-f="capacity"></strong></div>
                <div class="meter"><div class="meter__bar" data-f="meter"></div></div>
            </div>
            <div class="wl-slotcard">
                <div class="wl-row text-sm"><strong>Visualisasi 100 Slot Box (TB 30g)</strong><span class="badge badge--outline">5 LVL × 20</span></div>
                <div class="wl-stats">
                    <span class="tone--success">Kosong <strong data-f="st-empty"></strong></span>
                    <span class="tone--warning">Terisi <strong data-f="st-filled"></strong></span>
                    <span class="tone--danger">Expired <strong data-f="st-expired"></strong></span>
                </div>
                <x-button variant="primary" size="sm" data-act="grid">Buka Grid Rak 5 LVL (100 Box)</x-button>
            </div>
        </div>

        <div class="wl-row text-sm">
            <span class="text-muted">Alokasi Departemen:</span>
            <strong data-f="alloc"></strong>
        </div>
        <div class="notice notice--warning wl-booked text-sm" data-show="booked" hidden>
            <strong>Status: Reserved Booking</strong>
            <span data-f="booking-notes"></span>
            <span class="text-muted" data-f="booked-by"></span>
        </div>

        <div class="wl-actions">
            <x-button size="sm" data-act="grid" data-show="rack">Visualisasi 100 Slot Rak (5 LVL)</x-button>
            <x-button size="sm" data-act="add-rack-in-room" data-show="room">+ Tambah Rak di Sektor Ini</x-button>
            <x-button size="sm" data-act="duplicate"><span data-f="duplicate"></span></x-button>
            <x-button size="sm" data-act="move" data-show="rack">Pindahkan Rak Ke Gudang...</x-button>
            <x-button size="sm" data-act="book" data-show="bookable">Booking Slot Tempat Arsip</x-button>
            <x-button size="sm" data-act="unbook" data-show="booked">Lepas Status Booking</x-button>
            <x-button size="sm" data-act="rotate">Putar Object 90° (Key: R)</x-button>
            <x-button size="sm" data-act="edit">Edit Nama, Warna &amp; Geometri</x-button>
            <x-button size="sm" variant="danger" data-act="delete">Hapus Object Dari Canvas (Key: Del)</x-button>
        </div>
    </div>
</aside>
