<!-- MODAL DIALOG 1: HELP & HOTKEYS GUIDE (F1) -->
    <div 
        x-show="helpModal" 
        x-cloak 
        x-transition
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
        <div 
            @click.away="helpModal = false"
            class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono"
        >
            <div class="bg-slate-800 text-white px-3 py-2 flex items-center justify-between font-bold text-xs border-b border-slate-700">
                <span class="flex items-center gap-1.5"><i data-lucide="help-circle" class="w-4 h-4 text-blue-400"></i> Bantuan & Pintasan Keyboard (F1)</span>
                <button @click="helpModal = false" type="button" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <div class="p-4 space-y-3 text-xs text-slate-700 dark:text-slate-300 font-sans">
                <p class="font-bold text-amber-600 dark:text-amber-400 font-mono">Daftar Pintasan Keyboard (Hotkeys Desktop):</p>
                <ul class="space-y-1.5 font-mono text-[11px]">
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[Enter]</span> <strong class="text-slate-900 dark:text-slate-100">Kirim Form / Masuk System</strong>
                    </li>
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[Esc]</span> <strong class="text-slate-900 dark:text-slate-100">Reset Form / Tutup Modal</strong>
                    </li>
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[F1]</span> <strong class="text-slate-900 dark:text-slate-100">Buka Panduan Bantuan Ini</strong>
                    </li>
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[Alt + T]</span> <strong class="text-slate-900 dark:text-slate-100">Ganti Theme (Dark / Light)</strong>
                    </li>
                </ul>
            </div>

            <div class="p-3 bg-slate-200 dark:bg-slate-800 border-t border-slate-300 dark:border-slate-700 flex justify-end">
                <button @click="helpModal = false" type="button" class="px-4 py-1.5 bg-slate-700 text-white rounded text-xs font-mono font-bold hover:bg-slate-600">
                    Tutup (Esc)
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DIALOG 2: EXIT APPLICATION CONFIRMATION -->
    <div 
        x-show="closeConfirmModal" 
        x-cloak 
        x-transition
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
        <div 
            class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-sm w-full shadow-2xl overflow-hidden font-mono"
        >
            <div class="bg-rose-700 text-white px-3 py-2 flex items-center justify-between font-bold text-xs border-b border-rose-800">
                <span class="flex items-center gap-1.5"><i data-lucide="alert-circle" class="w-4 h-4"></i> Konfirmasi Keluar Application</span>
                <button @click="closeConfirmModal = false" type="button" class="text-white hover:text-rose-200">✕</button>
            </div>

            <div class="p-4 space-y-2 text-xs text-slate-700 dark:text-slate-300 font-sans">
                <p class="font-bold text-slate-900 dark:text-white">Apakah Anda yakin ingin membatalkan login?</p>
                <p class="text-slate-500 text-[11px]">Memilih Ya akan menyembunyikan jendela login ke Taskbar Desktop.</p>
            </div>

            <div class="p-3 bg-slate-200 dark:bg-slate-800 border-t border-slate-300 dark:border-slate-700 flex justify-end gap-2 font-mono">
                <button @click="closeConfirmModal = false" type="button" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400 dark:hover:bg-slate-600">
                    Batal
                </button>
                <button @click="minimized = true; closeConfirmModal = false" type="button" class="px-3 py-1.5 bg-rose-600 text-white rounded text-xs font-bold hover:bg-rose-500">
                    Ya, Minimize App
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DIALOG 3: FONT SIZE SETTINGS -->
    <div 
        x-show="fontSizeModal" 
        x-cloak 
        x-transition
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
        <div 
            @click.away="fontSizeModal = false"
            class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-sm w-full shadow-2xl overflow-hidden font-mono"
        >
            <div class="bg-slate-800 text-white px-3 py-2 flex items-center justify-between font-bold text-xs border-b border-slate-700">
                <span class="flex items-center gap-1.5">
                    <i data-lucide="type" class="w-4 h-4 text-amber-400"></i>
                    Pengaturan Ukuran Font
                </span>
                <button @click="fontSizeModal = false" type="button" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <div class="p-4 space-y-3.5 text-xs text-slate-700 dark:text-slate-300 font-sans">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="text-slate-700 dark:text-slate-300 font-bold">Ukuran Font Aktif:</span>
                    <span class="px-2.5 py-0.5 bg-amber-500 text-slate-950 rounded font-bold text-xs shadow-sm" x-text="getFontSizeLabel(currentFontSize)"></span>
                </div>

                <!-- Presets Buttons -->
                <div>
                    <span class="block text-[10px] text-slate-500 font-mono font-bold uppercase mb-2">Pilihan Skala Preset:</span>
                    <div class="grid grid-cols-2 gap-2 font-mono">
                        <button 
                            @click="setFontSize('16px')" 
                            type="button" 
                            class="px-2.5 py-2 rounded border text-xs font-bold transition flex flex-col items-center justify-center text-center"
                            :class="currentFontSize === '16px' ? 'bg-amber-500 text-slate-950 border-amber-600 shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-amber-500/10'"
                        >
                            <span>Kecil (16px)</span>
                            <span class="text-xs opacity-75 font-normal">Compact / 85%</span>
                        </button>
                        <button 
                            @click="setFontSize('19px')" 
                            type="button" 
                            class="px-2.5 py-2 rounded border text-xs font-bold transition flex flex-col items-center justify-center text-center"
                            :class="(currentFontSize === '19px' || currentFontSize === '100%') ? 'bg-amber-500 text-slate-950 border-amber-600 shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-amber-500/10'"
                        >
                            <span>Standar (19px)</span>
                            <span class="text-xs opacity-75 font-normal">Default / 100%</span>
                        </button>
                        <button 
                            @click="setFontSize('21px')" 
                            type="button" 
                            class="px-2.5 py-2 rounded border text-xs font-bold transition flex flex-col items-center justify-center text-center"
                            :class="currentFontSize === '21px' ? 'bg-amber-500 text-slate-950 border-amber-600 shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-amber-500/10'"
                        >
                            <span>Besar (21px)</span>
                            <span class="text-xs opacity-75 font-normal">Large / 110%</span>
                        </button>
                        <button 
                            @click="setFontSize('23px')" 
                            type="button" 
                            class="px-2.5 py-2 rounded border text-xs font-bold transition flex flex-col items-center justify-center text-center"
                            :class="currentFontSize === '23px' ? 'bg-amber-500 text-slate-950 border-amber-600 shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-amber-500/10'"
                        >
                            <span>X-Large (23px)</span>
                            <span class="text-xs opacity-75 font-normal">Ekstra / 120%</span>
                        </button>
                    </div>
                </div>

                <!-- Slider Control -->
                <div class="space-y-1.5 pt-1">
                    <div class="flex justify-between text-[10px] text-slate-500 font-mono font-bold">
                        <span>14px (Kecil)</span>
                        <span>Slider Presisi (14px - 24px)</span>
                        <span>24px (Besar)</span>
                    </div>
                    <input 
                        type="range" 
                        min="14" 
                        max="24" 
                        step="1" 
                        :value="parseInt(currentFontSize) || 19" 
                        @input="setFontSize($event.target.value + 'px')"
                        class="w-full accent-amber-500 cursor-pointer h-2 bg-slate-300 dark:bg-slate-700 rounded-lg appearance-none"
                    >
                </div>
            </div>

            <div class="p-3 bg-slate-200 dark:bg-slate-800 border-t border-slate-300 dark:border-slate-700 flex justify-end font-mono">
                <button @click="fontSizeModal = false; playClickSound()" type="button" class="px-4 py-1.5 bg-slate-700 text-white rounded text-xs font-bold hover:bg-slate-600">
                    Selesai
                </button>
            </div>
        </div>
    </div>

    