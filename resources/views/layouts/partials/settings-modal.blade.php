{{--
    Font size per user (ASK 13). Laravel 7 stored it globally in app_settings; here it is kept
    per browser (localStorage "app_font_size", same key as Laravel 7) until a users column exists.
    Logo / LAN alert settings (admin) come with the SISTEM module.
--}}
<x-modal id="settings-modal" title="Pengaturan Sistem & Tampilan" x-data="fontSize" @modal-closed="revert()">
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
    </div>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" @click="save()">Simpan</x-button>
    </x-slot:footer>
</x-modal>
