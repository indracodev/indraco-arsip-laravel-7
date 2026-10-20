        <!-- MODAL DUPLIKASI BOX (Granular Copy) -->
        <div 
            x-show="duplicateModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div 
                @click.outside="duplicateModalOpen = false" 
                class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl max-w-lg w-full p-4 space-y-3.5 shadow-xl font-mono text-xs"
            >
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-amber-500/20 text-amber-600 rounded">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Duplikasi Box Arsip</h3>
                            <p class="text-[10px] text-slate-400 font-sans">Sumber: Box #<span x-text="duplicateSourceIndex + 1"></span></p>
                        </div>
                    </div>
                    <button type="button" @click="duplicateModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
                </div>

                <div class="space-y-2 font-sans">
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih mode duplikasi:</p>
                    <div class="space-y-1.5">
                        <label class="flex items-start gap-2 p-2 rounded border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer">
                            <input type="radio" value="full" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-xs text-slate-900 dark:text-white">Duplikat Lengkap (Seluruh Butir Dokumen)</span>
                                <p class="text-[10px] text-slate-500">Menyalin pengaturan box beserta seluruh butir berkas di dalamnya.</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 p-2 rounded border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer">
                            <input type="radio" value="metadata_only" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-xs text-slate-900 dark:text-white">Hanya Pengaturan Identitas (Kosongkan Butir)</span>
                                <p class="text-[10px] text-slate-500">Menyalin sub-dept, tipe dokumen, dan kondisi fisik. Butir berkas dimulai baru.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-2.5 font-mono">
                    <button type="button" @click="duplicateModalOpen = false" class="px-3 py-1.5 rounded border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer">Batal</button>
                    <button type="button" @click="confirmDuplicateBox()" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Duplikat Sekarang</span>
                    </button>
                </div>
            </div>
        </div>