{{-- Laravel 7 frmWarehouseAdd / frmWarehouseEdit and frmLocationAdd / frmLocationEdit (one modal each, add or edit). --}}
<x-modal id="warehouse-form" title="Gudang">
    <form method="POST" :action="whForm.id ? base + '/' + whForm.id : base" class="stack">
        @csrf
        <input type="hidden" name="_method" :value="whForm.id ? 'PUT' : 'POST'">
        <input type="hidden" name="_form" value="warehouse">
        <input type="hidden" name="_id" :value="whForm.id || ''">
        <p class="field__label" x-text="whForm.id ? 'Form Perubahan Gudang' : 'Data Form Gudang'"></p>
        <x-field label="KODE GUDANG" for="wh-code" required error="code" hint="CTH: GUDANG-C">
            <x-input name="code" id="wh-code" x-model="whForm.code" required maxlength="20" placeholder="GUDANG-C" />
        </x-field>
        <x-field label="NAMA GUDANG" for="wh-name" required error="name">
            <x-input name="name" id="wh-name" x-model="whForm.name" required maxlength="100" placeholder="Gudang Depo Gedangan Blok C" />
        </x-field>
        <x-field label="ALAMAT LOKASI GUDANG" for="wh-address" error="address">
            <x-textarea name="address" id="wh-address" x-model="whForm.address" rows="2" placeholder="Kawasan Industri Indraco..." />
        </x-field>
        <div class="modal__footer">
            <x-button data-modal-close>Batal (Esc)</x-button>
            <x-button type="submit" variant="primary"><span x-text="whForm.id ? 'Update Gudang (Enter)' : 'Simpan Gudang (Enter)'"></span></x-button>
        </div>
    </form>
</x-modal>

<x-modal id="location-form" title="Slot Rak Gudang">
    <form method="POST" :action="locForm.id ? base + '/locations/' + locForm.id : base + '/locations'" class="stack">
        @csrf
        <input type="hidden" name="_method" :value="locForm.id ? 'PUT' : 'POST'">
        <input type="hidden" name="_form" value="location">
        <input type="hidden" name="_id" :value="locForm.id || ''">
        <p class="field__label" x-text="locForm.id ? 'Form Perubahan Slot Rak' : 'Data Form Slot Rak'"></p>
        <x-field label="PILIH GUDANG TARGET" for="loc-warehouse" required error="warehouse_id">
            <select name="warehouse_id" id="loc-warehouse" class="control" x-model="locForm.warehouse_id" required>
                <template x-for="wh in items" :key="wh.id">
                    <option :value="String(wh.id)" x-text="wh.code + ' - ' + wh.name" :selected="String(wh.id) === String(locForm.warehouse_id)"></option>
                </template>
            </select>
        </x-field>
        <div class="form-grid form-grid--2">
            <x-field label="KODE RAK" for="loc-rack" required error="rack_code">
                <x-input name="rack_code" id="loc-rack" x-model="locForm.rack_code" required maxlength="50" placeholder="RAK-A3" />
            </x-field>
            <x-field label="KODE BARIS/SHELF" for="loc-shelf" required error="shelf_code">
                <x-input name="shelf_code" id="loc-shelf" x-model="locForm.shelf_code" required maxlength="50" placeholder="BARIS-01" />
            </x-field>
        </div>
        <x-field label="KAPASITAS MAKSIMAL BOX" for="loc-capacity" required error="box_capacity">
            <x-input name="box_capacity" id="loc-capacity" type="number" x-model="locForm.box_capacity" min="1" ::max="locForm.id ? 5000 : 1000" required />
        </x-field>
        <div class="modal__footer">
            <x-button data-modal-close>Batal (Esc)</x-button>
            <x-button type="submit" variant="primary"><span x-text="locForm.id ? 'Update Rak (Enter)' : 'Tambah Rak (Enter)'"></span></x-button>
        </div>
    </form>
</x-modal>
