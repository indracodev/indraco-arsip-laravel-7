{{--
    Font size per user (ASK 13) and menu layout. Laravel 7 stored the font size globally in
    app_settings; here both are kept per browser (localStorage "app_font_size" - same key as
    Laravel 7 - and "dms_layout") until a users column exists.
    Logo / LAN alert settings (admin) come with the SISTEM module.
--}}
<x-modal id="settings-modal" title="Pengaturan Sistem & Tampilan" x-data="displaySettings" @modal-closed="revert()">
    <div class="stack">
        <div class="field">
            <span class="field__label">Ukuran Font Tampilan Antarmuka (Font Scaling)</span>
            <span class="text-sm">Ukuran Font Saat Ini: <strong x-text="size + 'px'"></strong></span>
        </div>
        <div class="field">
            <span class="field__label">Pilihan Skala Cepat (Preset):</span>
            <div class="cluster">
                <template x-for="preset in presets" :key="preset[0]">
                    <button type="button" class="btn btn--sm" :class="{ 'btn--primary': size === preset[0] }" @click="apply(preset[0])" x-text="preset[1] + ' (' + preset[0] + 'px)'"></button>
                </template>
            </div>
        </div>
        <label class="field">
            <span class="field__label">Slider Presisi (10px - 26px)</span>
            <input type="range" min="10" max="26" step="1" :value="size" @input="apply($event.target.value)">
        </label>
        <fieldset class="field">
            <legend class="field__label">Tata Letak Menu</legend>
            <div class="cluster">
                <label class="checkbox"><input type="radio" name="dms_layout" value="side" :checked="layout === 'side'" @change="setLayout('side')"> <span>Menu Samping (kiri)</span></label>
                <label class="checkbox"><input type="radio" name="dms_layout" value="top" :checked="layout === 'top'" @change="setLayout('top')"> <span>Menu Atas</span></label>
            </div>
            <span class="field__hint">Di layar kecil (&lt; 1024 px) menu selalu tampil sebagai laci (drawer).</span>
        </fieldset>
    </div>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" @click="save()">Simpan</x-button>
    </x-slot:footer>
</x-modal>
