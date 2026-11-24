{{-- Laravel 7 batch_resize / batch_dept (Super Admin) / batch_color modals for a multi-selection. --}}
<x-modal id="wl-batch-resize" title="Ubah Ukuran Massal (W & H)">
    <form class="stack" id="wl-batch-resize-form" data-no-lock>
        <p class="text-sm">Ubah ukuran serentak untuk <strong data-f="batch-count"></strong>:</p>
        <div class="form-grid--2">
            <x-field label="Lebar Baru (W px)" for="wl-br-w" required><input class="control" id="wl-br-w" name="canvas_width" type="number" min="20" max="800" required></x-field>
            <x-field label="Tinggi Baru (H px)" for="wl-br-h" required><input class="control" id="wl-br-h" name="canvas_height" type="number" min="20" max="800" required></x-field>
        </div>
        <x-field label="Preset Ukuran:">
            <div class="cluster">
                <x-button size="sm" data-size="25x140">25x140 (V-Std)</x-button>
                <x-button size="sm" data-size="140x25">140x25 (H-Std)</x-button>
                <x-button size="sm" data-size="25x180">25x180 (V-Pjg)</x-button>
                <x-button size="sm" data-size="180x25">180x25 (H-Pjg)</x-button>
            </div>
        </x-field>
    </form>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" type="submit" form="wl-batch-resize-form">Terapkan Ukuran</x-button>
    </x-slot:footer>
</x-modal>

@if ($isAdmin)
    <x-modal id="wl-batch-dept" title="Set Alokasi Departemen Massal">
        <form class="stack" id="wl-batch-dept-form" data-no-lock>
            <p class="text-sm">Ubah alokasi departemen secara serentak untuk <strong data-f="batch-count"></strong>:</p>
            <x-field label="Pilih Satu atau Lebih Departemen yang Diizinkan:">
                <div class="wl-depts" data-depts="batch"></div>
                <span class="text-sm">Terpilih: <strong data-f="batch-dept-count"></strong> <button type="button" class="btn btn--ghost btn--sm" data-act="batch-dept-reset">Reset ke Bebas</button></span>
            </x-field>
        </form>
        <x-slot:footer>
            <x-button data-modal-close>Batal</x-button>
            <x-button variant="primary" type="submit" form="wl-batch-dept-form">Terapkan Alokasi</x-button>
        </x-slot:footer>
    </x-modal>
@endif

<x-modal id="wl-batch-color" title="Set Warna Custom Massal">
    <form class="stack" id="wl-batch-color-form" data-no-lock>
        <p class="text-sm">Pilih warna custom secara serentak untuk <strong data-f="batch-count"></strong>:</p>
        <x-field label="Pilih Preset Warna:"><div class="wl-colors" data-colors="batch"></div></x-field>
        <x-field label="Kode Hex Warna:" for="wl-bc-hex"><input class="control mono" id="wl-bc-hex" name="custom_color" pattern="#[0-9a-fA-F]{3,8}" placeholder="#3b82f6" required></x-field>
    </form>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" type="submit" form="wl-batch-color-form">Terapkan Warna</x-button>
    </x-slot:footer>
</x-modal>
