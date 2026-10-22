        <!-- MODAL DUPLIKASI BOX -->
        <div 
            x-show="duplicateModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div 
                @click.outside="duplicateModalOpen = false" 
                class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl max-w-lg w-full p-4 space-y-4 shadow-xl font-mono text-xs"
            >
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-amber-500/20 text-amber-600 rounded">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                        </span>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">Duplikasi Box #<span x-text="duplicateSourceIndex + 1"></span></h3>
                    </div>
                    <button type="button" @click="duplicateModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
                </div>

                <div class="space-y-3 font-sans text-xs">
                    <p class="text-slate-600 dark:text-slate-300">
                        Pilih bagian yang ingin Anda salin ke Box baru:
                    </p>

                    <div class="space-y-2">
                        <!-- Mode Full -->
                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" value="full" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-100">Salin Lengkap (Wadah & Seluruh Berkas Dokumen)</span>
                                <p class="text-[11px] text-slate-500">Menduplikasi seluruh identitas box dan menyalin semua <span x-text="boxes[duplicateSourceIndex] ? boxes[duplicateSourceIndex].items.length : 0"></span> berkas dokumen.</p>
                            </div>
                        </label>

                        <!-- Mode Metadata Saja -->
                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" value="metadata_only" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-100">Salin Metadata Saja (Wadah Saja, Kosongkan Berkas)</span>
                                <p class="text-[11px] text-slate-500">Menyalin sub-dept, jenis dokumen, dan kondisi fisik, tetapi mengosongkan berkas dokumen untuk diisi baru.</p>
                            </div>
                        </label>

                        <!-- Mode Selected Items -->
                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" value="selected_items" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-100">Pilih Berkas Dokumen Tertentu</span>
                                <p class="text-[11px] text-slate-500">Pilih checklist berkas mana saja yang ingin disertakan ke box baru.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Checklist items if selected_items mode -->
                    <div x-show="duplicateMode === 'selected_items'" class="max-h-48 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded p-2 space-y-1 font-mono text-[11px]">
                        <template x-for="(it, idx) in (boxes[duplicateSourceIndex] ? boxes[duplicateSourceIndex].items : [])" :key="it.id">
                            <label class="flex items-center gap-2 p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded cursor-pointer">
                                <input type="checkbox" :value="it.id" x-model="duplicateSelectedItems" class="text-amber-500">
                                <span x-text="(idx + 1) + '. ' + (it.document_name || 'Dokumen Tanpa Nama')"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-2.5">
                    <button type="button" @click="duplicateModalOpen = false" class="px-3 py-1.5 rounded border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer">Batal</button>
                    <button type="button" @click="confirmDuplicateBox()" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Duplikasi Sekarang</span>
                    </button>
                </div>
            </div>
        </div>