{{-- Laravel 7 canvas_context_menu (right click): same actions as the inspector, by target. --}}
<div class="wl-menu card" id="wl-menu" role="menu" hidden>
    <div data-menu="object">
        <button type="button" role="menuitem" data-act="grid" data-show="rack">Buka Denah Slot Rak</button>
        <button type="button" role="menuitem" data-act="add-rack-in-room" data-show="room">+ Tambah Rak di Sini</button>
        <button type="button" role="menuitem" data-act="edit">Edit Object</button>
        <button type="button" role="menuitem" data-act="lock"><span data-f="lock-btn"></span></button>
        <button type="button" role="menuitem" data-act="rotate">Putar 90° (R)</button>
        <button type="button" role="menuitem" data-act="duplicate">Duplikat (Copy)</button>
        <button type="button" role="menuitem" data-act="move" data-show="rack">Pindahkan Ke Gudang...</button>
        <div data-show="many">
            <button type="button" role="menuitem" data-act="batch-unlock">Buka Kunci Semua</button>
            <button type="button" role="menuitem" data-act="batch-lock">Kunci Semua</button>
            <button type="button" role="menuitem" data-act="batch-resize">Ubah Ukuran Massal</button>
            @if ($isAdmin)
                <button type="button" role="menuitem" data-act="batch-dept">Set Dept Massal</button>
            @endif
            <button type="button" role="menuitem" data-act="batch-color">Set Warna Massal</button>
            <button type="button" role="menuitem" data-act="align-h">Rapatkan Baris</button>
            <button type="button" role="menuitem" data-act="align-v">Rapatkan Kolom</button>
        </div>
        <button type="button" role="menuitem" class="tone--danger" data-act="delete">Hapus (Del)</button>
    </div>
    <div data-menu="canvas">
        <button type="button" role="menuitem" data-act="add-room-here">+ Tambah Gudang di Sini</button>
        <button type="button" role="menuitem" data-act="add-rack-here">+ Tambah Rak di Sini</button>
    </div>
</div>
