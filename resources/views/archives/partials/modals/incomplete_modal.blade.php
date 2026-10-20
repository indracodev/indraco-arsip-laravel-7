    <!-- MODAL POPUP LENGKAPI BERKAS TERLEBIH DAHULU -->
    <div x-show="incompleteFileModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         style="display: none; z-index: 9996 !important;"
         @keydown.escape.window="incompleteFileModalOpen = false">
        
        <div class="bg-white dark:bg-slate-900 border border-amber-500/40 dark:border-amber-500/30 rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 font-mono text-xs"
             @click.away="incompleteFileModalOpen = false">
            
            <div class="flex items-start gap-3">
                <div class="p-2.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-xl shrink-0">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                        Lengkapi Berkas Terlebih Dahulu
                    </h3>
                    <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed font-sans">
                        Berkas digital & scan formulir untuk box arsip ini belum diunggah. Silakan lengkapi berkas digital & scan formulir terlebih dahulu pada form pengajuan (Bagian 5) sebelum mencetak label box.
                    </p>
                </div>
            </div>

            <template x-if="incompleteFileArchive">
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl space-y-1 text-[11px]">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>No. Box / Kode:</span>
                        <strong class="text-amber-600 dark:text-amber-400 font-mono" x-text="incompleteFileArchive.box_number"></strong>
                    </div>
                    <div class="flex items-center justify-between text-slate-700 dark:text-slate-200">
                        <span>Judul Berkas:</span>
                        <strong class="truncate max-w-[200px]" x-text="incompleteFileArchive.title"></strong>
                    </div>
                </div>
            </template>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="incompleteFileModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                    Tutup
                </button>
                <template x-if="incompleteFileArchive && incompleteFileArchive.edit_url">
                    <a :href="incompleteFileArchive.edit_url" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl font-black text-xs shadow-md transition inline-flex items-center gap-1.5">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-slate-950"></i>
                        <span>Lengkapi Berkas Sekarang</span>
                    </a>
                </template>
            </div>
        </div>
    </div>