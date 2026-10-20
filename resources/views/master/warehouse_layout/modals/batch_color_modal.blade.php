    <!-- Modal 7: Batch Custom Color (Warna Massal) -->
    <div x-show="batchColorModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 font-mono">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-5 max-w-md w-full space-y-3 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="palette" class="w-4 h-4 text-pink-500"></i>
                    <span>Set Warna Custom Massal</span>
                </h3>
                <button @click="batchColorModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium font-sans">
                Pilih warna custom secara serentak untuk <span class="font-bold text-indigo-400 font-mono" x-text="selectedLocations.length + ' objek terpilih'"></span>:
            </p>

            <form @submit.prevent="submitBatchColor()" class="space-y-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1.5">Pilih Preset Warna:</label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="color in colorPresets" :key="color">
                            <button 
                                type="button" 
                                @click="batchColorForm.custom_color = color" 
                                class="w-7 h-7 rounded-none border-2 transition cursor-pointer" 
                                :class="batchColorForm.custom_color === color ? 'border-white scale-110 shadow-xs ring-1 ring-amber-400' : 'border-slate-500 opacity-80 hover:opacity-100'" 
                                :style="{ backgroundColor: color }"
                            ></button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Kode Hex Warna:</label>
                    <input type="text" x-model="batchColorForm.custom_color" placeholder="#3b82f6" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white">
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="batchColorModalOpen = false" class="px-3.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-300 text-xs rounded font-bold border border-slate-300 dark:border-slate-700 cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-1 bg-pink-600 hover:bg-pink-500 text-white text-xs font-bold rounded border border-pink-700 shadow-xs flex items-center gap-1 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Terapkan Warna
                    </button>
                </div>
            </form>
        </div>
    </div>