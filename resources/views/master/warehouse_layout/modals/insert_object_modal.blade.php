    <!-- Modal 3: Insert New Object (Gudang Square / Rak Rectangle) -->
    <div x-show="objectModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 font-mono">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-5 max-w-md w-full space-y-3 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-500"></i>
                    <span x-text="objectForm.location_type === 'room' ? 'Tambah Object Gudang (Square)' : 'Tambah Object Rak (Rectangle)'"></span>
                </h3>
                <button @click="objectModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form @submit.prevent="submitCreateObject()" class="space-y-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">
                        <span x-text="objectForm.location_type === 'room' ? 'Nama Gudang / Sektor' : 'Nomor / Awalan Kode Rak'"></span> <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" x-model="objectForm.rack_code" required placeholder="e.g. GUDANG PRODUKSI atau RAK-R7" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-bold">
                </div>

                <template x-if="objectForm.location_type === 'rack'">
                    <div class="p-2.5 bg-emerald-500/10 border border-emerald-500/30 rounded space-y-1">
                        <label class="block text-[10px] font-bold uppercase text-emerald-700 dark:text-emerald-400 mb-1 flex items-center justify-between">
                            <span>Jumlah Rak Yang Dibuat (Batch Multi-Add)</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" x-model="objectForm.quantity" min="1" max="50" required class="w-20 px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-mono font-bold">
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium font-sans">Buat 1 s/d 50 rak sekaligus berurutan rapi.</span>
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Kode Sektor Ruangan</label>
                        <input type="text" x-model="objectForm.room_sector" placeholder="e.g. R7, GA, IT" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-bold">
                    </div>

                    <template x-if="objectForm.location_type === 'rack'">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Kapasitas Box</label>
                            <input type="number" x-model="objectForm.box_capacity" min="1" max="5000" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-bold">
                        </div>
                    </template>
                </div>

                <!-- Geometri Dimensions -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Lebar (Width px)</label>
                        <input type="number" x-model="objectForm.canvas_width" required min="20" max="800" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Tinggi (Height px)</label>
                        <input type="number" x-model="objectForm.canvas_height" required min="20" max="800" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-mono font-bold">
                    </div>
                </div>

                <!-- Color Preset Picker (Square Swatches) -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1.5">Pilih Warna Object</label>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template x-for="c in colorPresets" :key="c">
                            <button @click="objectForm.custom_color = c" type="button" class="w-6 h-6 rounded-none border-2 transition cursor-pointer" :class="objectForm.custom_color === c ? 'border-white scale-110 shadow-xs ring-1 ring-amber-400' : 'border-slate-500 opacity-80 hover:opacity-100'" :style="{ backgroundColor: c }"></button>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="objectModalOpen = false" class="px-3.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-300 text-xs rounded font-bold border border-slate-300 dark:border-slate-700 cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded border border-emerald-700 shadow-xs cursor-pointer">Tambah Object</button>
                </div>
            </form>
        </div>
    </div>