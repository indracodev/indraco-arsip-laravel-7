    <!-- Modal 5: Batch Resize Object (Width & Height Massal) -->
    <div x-show="batchResizeModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 font-mono">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-5 max-w-md w-full space-y-3 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="scaling" class="w-4 h-4 text-blue-500"></i>
                    <span>Ubah Ukuran Massal (W & H)</span>
                </h3>
                <button @click="batchResizeModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium font-sans">
                Ubah ukuran serentak untuk <span class="font-bold text-indigo-400 font-mono" x-text="selectedLocations.length + ' objek terpilih'"></span>:
            </p>

            <form @submit.prevent="submitBatchResize()" class="space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Lebar Baru (W px) <span class="text-rose-500">*</span></label>
                        <input type="number" x-model="batchResizeForm.canvas_width" required min="20" max="800" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Tinggi Baru (H px) <span class="text-rose-500">*</span></label>
                        <input type="number" x-model="batchResizeForm.canvas_height" required min="20" max="800" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- Quick Preset Sizes (Square Desktop Buttons) -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Preset Ukuran:</label>
                    <div class="flex flex-wrap gap-1">
                        <button type="button" @click="batchResizeForm.canvas_width = 25; batchResizeForm.canvas_height = 140" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-900 hover:bg-blue-600 hover:text-white rounded text-[10px] font-mono font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer">25x140 (V-Std)</button>
                        <button type="button" @click="batchResizeForm.canvas_width = 140; batchResizeForm.canvas_height = 25" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-900 hover:bg-blue-600 hover:text-white rounded text-[10px] font-mono font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer">140x25 (H-Std)</button>
                        <button type="button" @click="batchResizeForm.canvas_width = 25; batchResizeForm.canvas_height = 180" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-900 hover:bg-blue-600 hover:text-white rounded text-[10px] font-mono font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer">25x180 (V-Pjg)</button>
                        <button type="button" @click="batchResizeForm.canvas_width = 180; batchResizeForm.canvas_height = 25" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-900 hover:bg-blue-600 hover:text-white rounded text-[10px] font-mono font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer">180x25 (H-Pjg)</button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="batchResizeModalOpen = false" class="px-3.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-300 text-xs rounded font-bold border border-slate-300 dark:border-slate-700 cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-1 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded border border-blue-700 shadow-xs flex items-center gap-1 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Terapkan Ukuran
                    </button>
                </div>
            </form>
        </div>
    </div>