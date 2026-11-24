{{-- Laravel 7 modals: insert_object, edit_object, booking_slot, move_rack, setup_canvas.
     Fields, labels, required marks and limits as Laravel 7; submitted by warehouse-layout-forms.js. --}}
<x-modal id="wl-add" title="Tambah Object">
    <form class="stack" id="wl-add-form" data-no-lock>
        <x-field label="Nama / Kode" for="wl-add-code" required><input class="control" id="wl-add-code" name="rack_code" required maxlength="100" placeholder="e.g. GUDANG PRODUKSI atau RAK-R7"></x-field>
        <x-field label="Jumlah Rak Yang Dibuat (Batch Multi-Add)" for="wl-add-qty" required hint="Buat 1 s/d 50 rak sekaligus berurutan rapi." data-show="rack"><input class="control" id="wl-add-qty" name="quantity" type="number" min="1" max="50" value="1" required></x-field>
        <x-field label="Kode Sektor Ruangan" for="wl-add-sector"><input class="control" id="wl-add-sector" name="room_sector" maxlength="50" placeholder="e.g. R7, GA, IT"></x-field>
        <x-field label="Kapasitas Box" for="wl-add-cap" data-show="rack"><input class="control" id="wl-add-cap" name="box_capacity" type="number" min="1" max="5000" value="100"></x-field>
        <div class="form-grid--2">
            <x-field label="Lebar (Width px)" for="wl-add-w" required><input class="control" id="wl-add-w" name="canvas_width" type="number" min="20" max="800" required></x-field>
            <x-field label="Tinggi (Height px)" for="wl-add-h" required><input class="control" id="wl-add-h" name="canvas_height" type="number" min="20" max="800" required></x-field>
        </div>
        <x-field label="Pilih Warna Object"><div class="wl-colors" data-colors="add"></div></x-field>
    </form>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" type="submit" form="wl-add-form">Tambah Object</x-button>
    </x-slot:footer>
</x-modal>

<x-modal id="wl-edit" title="Edit Details, Nama & Warna Object">
    <form class="stack" id="wl-edit-form" data-no-lock>
        <x-field label="Nama / Kode Object" for="wl-edit-code" required><input class="control" id="wl-edit-code" name="rack_code" required maxlength="100"></x-field>
        <div class="form-grid--2">
            <x-field label="Kode Sektor Ruangan" for="wl-edit-sector"><input class="control" id="wl-edit-sector" name="room_sector" maxlength="50"></x-field>
            <x-field label="Kode Baris / Shelf" for="wl-edit-shelf"><input class="control" id="wl-edit-shelf" name="shelf_code" maxlength="50" placeholder="e.g. BARIS-01"></x-field>
            <x-field label="Kapasitas Box" for="wl-edit-cap"><input class="control" id="wl-edit-cap" name="box_capacity" type="number" min="1" max="5000"></x-field>
            <x-field label="Orientasi" for="wl-edit-orient"><select class="control" id="wl-edit-orient" name="orientation"><option value="horizontal">Horizontal</option><option value="vertical">Vertical</option></select></x-field>
            <x-field label="Lebar (W)" for="wl-edit-w" required><input class="control" id="wl-edit-w" name="canvas_width" type="number" min="20" max="800" required></x-field>
            <x-field label="Tinggi (H)" for="wl-edit-h" required><input class="control" id="wl-edit-h" name="canvas_height" type="number" min="20" max="800" required></x-field>
        </div>
        <x-field label="Warna Custom Object"><div class="wl-colors" data-colors="edit" data-auto="Auto (Status)"></div></x-field>
        <x-field label="Status Kunci Posisi Object (Canvas)" for="wl-edit-lock">
            <select class="control" id="wl-edit-lock" name="is_locked">
                <option value="0">🔓 Terbuka (Unlocked - Posisi dapat digeser / diatur)</option>
                <option value="1">🔒 Terkunci (Locked - Posisi terkunci anti geser)</option>
            </select>
        </x-field>
        @if ($isAdmin)
            <x-field label="Departemen yang Diizinkan Menempatkan Box" hint="Pilih satu atau beberapa departemen jika rak ini dikhususkan (misal: FAT dan HRD). Jika tidak ada departemen yang dipilih, box dari semua departemen boleh ditempatkan di sini.">
                <div class="wl-depts" data-depts="edit"></div>
                <span class="text-sm">Terpilih: <strong data-f="edit-dept-count"></strong> <button type="button" class="btn btn--ghost btn--sm" data-act="edit-dept-reset">Reset ke Bebas (Semua Dept)</button></span>
            </x-field>
        @endif
    </form>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" type="submit" form="wl-edit-form">Simpan Perubahan</x-button>
    </x-slot:footer>
</x-modal>

<x-modal id="wl-book" title="Form Booking Tempat Arsip">
    <form class="stack" id="wl-book-form" data-no-lock>
        <x-field label="Pilih Departemen Pemohon" for="wl-book-dept" required><select class="control" id="wl-book-dept" name="department_id" required><option value="">-- Pilih Departemen --</option></select></x-field>
        <x-field label="Catatan & Rencana Pengiriman Box" for="wl-book-notes" required><textarea class="control" id="wl-book-notes" name="booking_notes" rows="3" maxlength="500" required placeholder="Contoh: Booking slot untuk pengiriman 15 Box Faktur Pajak Q1 2026 Dept Keuangan..."></textarea></x-field>
    </form>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" type="submit" form="wl-book-form">Submit Booking</x-button>
    </x-slot:footer>
</x-modal>

<x-modal id="wl-move" title="Pindahkan Rak Ke Gudang">
    <p class="text-sm">Pilih Gudang / Sektor tujuan untuk rak <strong class="mono" data-f="move-code"></strong>:</p>
    <div class="stack wl-rooms" id="wl-move-list"></div>
    <x-slot:footer><x-button data-modal-close>Batal</x-button></x-slot:footer>
</x-modal>

<x-modal id="wl-size" title="Ubah Dimensi Canvas Workspace">
    <form class="stack" id="wl-size-form" data-no-lock>
        <p class="text-sm text-muted">Atur ukuran area lembar kerja (Workspace Canvas 2D) sesuai luas denah fisik gudang:</p>
        <div class="form-grid--2">
            <x-field label="Lebar Canvas (W px):" for="wl-size-w"><input class="control" id="wl-size-w" name="width" type="number" min="600" max="4000" step="50" required></x-field>
            <x-field label="Tinggi Canvas (H px):" for="wl-size-h"><input class="control" id="wl-size-h" name="height" type="number" min="500" max="3000" step="50" required></x-field>
        </div>
        <x-field label="Preset Ukuran Standar:">
            <div class="cluster">
                <x-button size="sm" data-size="950x750">950 × 750 px (Std)</x-button>
                <x-button size="sm" data-size="1200x900">1200 × 900 px (Med)</x-button>
                <x-button size="sm" data-size="1600x1000">1600 × 1000 px (Luas)</x-button>
                <x-button size="sm" data-size="2000x1400">2000 × 1400 px (XL)</x-button>
            </div>
        </x-field>
    </form>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" type="submit" form="wl-size-form">Terapkan Dimensi</x-button>
    </x-slot:footer>
</x-modal>
