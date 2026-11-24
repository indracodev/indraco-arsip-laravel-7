{{--
    Display settings (Alpine "displaySettings", public/js/app.js):
    - font size (ASK 13): per account (users.font_size, POST profile.font_size on "Simpan") and
      per browser (localStorage "app_font_size", same key as Laravel 7; L7 kept it globally in
      app_settings);
    - theme: "dms_theme" light | dark | system (default), public/js/prefs.js;
    - notification sound: "dms_sound" (default on, like L7), public/js/sound.js.
    Logo / LAN alert / maintenance (admin): link to the "Pengaturan Sistem" page (settings.index).
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
            <legend class="field__label">Tema Tampilan</legend>
            <div class="cluster">
                <template x-for="option in themes" :key="option[0]">
                    <label class="checkbox">
                        <input type="radio" name="theme" :value="option[0]" :checked="theme === option[0]" @change="setTheme(option[0])">
                        <span x-text="option[1]"></span>
                    </label>
                </template>
            </div>
        </fieldset>
        <div class="field">
            <span class="field__label">Suara Notifikasi</span>
            <div class="cluster">
                <x-checkbox name="sound" label="Bunyikan suara saat ada notifikasi baru" x-model="sound" />
                <x-button size="sm" @click="testSound()">Tes suara</x-button>
            </div>
        </div>
        @if (auth()->user()?->isAdmin())
            {{-- Laravel 7 sections 1, 3, 4 of this modal (logo, LAN alert, maintenance): admin page. --}}
            <div class="field">
                <span class="field__label">Pengaturan Sistem (Super Admin)</span>
                <x-link-button :href="route('settings.index')" size="sm">Logo, Notifikasi LAN, Backup &amp; Pemeliharaan Database</x-link-button>
            </div>
        @endif
    </div>
    <x-slot:footer>
        <x-button data-modal-close>Batal</x-button>
        <x-button variant="primary" @click="save()">Simpan</x-button>
    </x-slot:footer>
</x-modal>
