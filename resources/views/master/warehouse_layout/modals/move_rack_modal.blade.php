    <!-- Modal 5: Move Rak Ke Gudang / Sektor -->
    <div x-show="moveModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 font-mono">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-5 max-w-md w-full space-y-3 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="truck" class="w-4 h-4 text-amber-500"></i>
                    Pindahkan Rak Ke Gudang
                </h3>
                <button @click="moveModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium font-sans">
                Pilih Gudang / Sektor tujuan untuk rak <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="selectedLocation?.rack_code"></span>:
            </p>

            <div class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
                <template x-for="room in roomObjects" :key="room.id">
                    <div 
                        @click="moveRackToRoom(selectedLocation, room)" 
                        class="p-2.5 bg-slate-50 dark:bg-slate-900 hover:bg-amber-500/10 dark:hover:bg-amber-500/20 border border-slate-200 dark:border-slate-800 rounded cursor-pointer transition flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-3.5 h-3.5 rounded-none flex-shrink-0 border border-slate-400" :style="{ backgroundColor: room.custom_color || '#3b82f6' }"></span>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-xs group-hover:text-amber-500 transition" x-text="room.rack_code"></h4>
                                <span class="text-[10px] text-slate-500 block" x-text="'Sektor: ' + (room.room_sector || room.rack_code) + ' (' + room.canvas_width + 'x' + room.canvas_height + ' px)'"></span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-[11px] rounded border border-amber-500/20 group-hover:bg-amber-500 group-hover:text-slate-950 transition">
                            Pindahkan &rarr;
                        </span>
                    </div>
                </template>
                <template x-if="roomObjects.length === 0">
                    <div class="p-3 text-center text-xs text-slate-400 font-medium bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                        Belum ada objek Gudang/Sektor (Square). Silakan tambah Gudang terlebih dahulu.
                    </div>
                </template>
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-200 dark:border-slate-800">
                <button @click="moveModalOpen = false" type="button" class="px-3.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-300 text-xs rounded font-bold border border-slate-300 dark:border-slate-700 cursor-pointer">Batal</button>
            </div>
        </div>
    </div>