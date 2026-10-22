    <!-- WINDOWS FORM DIALOG MODAL 5: LIST BERKAS ARSIP (DRILL-DOWN) -->
    <div x-show="openArchiveListModal" 
         x-cloak 
         x-data="{ 
             posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
             startDrag(e) { 
                 if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select') || e.target.closest('table')) return; 
                 this.isDragging = true; 
                 this.startX = e.clientX - this.posX; 
                 this.startY = e.clientY - this.posY; 
             }, 
             onDrag(e) { 
                 if (!this.isDragging) return; 
                 this.posX = e.clientX - this.startX; 
                 this.posY = e.clientY - this.startY; 
             }, 
             stopDrag() { this.isDragging = false; }, 
             resetPos() { this.posX = 0; this.posY = 0; } 
         }"
         @mousemove.window="onDrag($event)" 
         @mouseup.window="stopDrag()"
         class="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-xs flex items-center justify-center p-4">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
             class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-4xl w-full shadow-2xl overflow-hidden font-mono max-h-[90vh] flex flex-col">
            <!-- Window Title Bar (Draggable) -->
            <div @mousedown="startDrag($event)" 
                 :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                 title="Klik & tahan untuk menggeser/reposisi posisi form (Drag to move)"
                 class="bg-gradient-to-r from-purple-900 via-slate-800 to-indigo-950 text-white px-3 py-2 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none shrink-0">
                <div class="flex items-center gap-2 font-bold pointer-events-none">
                    <i data-lucide="archive" class="w-4 h-4 text-purple-400"></i>
                    <div>
                        <span x-text="archiveListTitle"></span>
                        <p class="text-[10px] text-slate-300 font-normal font-sans" x-text="archiveListSubtitle"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openArchiveListModal = false; resetPos()" type="button" class="text-slate-400 hover:text-white text-sm font-bold px-1.5 py-0.5 rounded hover:bg-rose-600">✕</button>
                </div>
            </div>

            <!-- Toolbar Search inside Modal -->
            <div class="p-3 bg-white dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between gap-3 shrink-0">
                <div class="relative flex-1 max-w-xs">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400"></i>
                    <input 
                        type="text" 
                        x-model="archiveListSearch" 
                        placeholder="Filter no box, nama dokumen, periode..." 
                        class="w-full pl-8 pr-3 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"
                    >
                </div>
                <div class="text-[11px] font-mono text-slate-600 dark:text-slate-400">
                    Total: <strong class="text-purple-600 dark:text-purple-400" x-text="filteredArchiveList.length"></strong> Box Arsip
                </div>
            </div>

            <!-- Modal Content Table -->
            <div class="p-3 overflow-y-auto flex-1 font-sans text-xs">
                <!-- Loading State -->
                <div x-show="archiveListLoading" class="py-12 text-center text-purple-600 dark:text-purple-400 font-mono">
                    <i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto mb-2"></i>
                    <span>Memuat data berkas arsip...</span>
                </div>

                <!-- Empty State -->
                <div x-show="!archiveListLoading && filteredArchiveList.length === 0" class="py-12 text-center text-slate-500 font-mono">
                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                    <p>Tidak ada data berkas arsip yang terdaftar.</p>
                </div>

                <!-- Table of Archives -->
                <div x-show="!archiveListLoading && filteredArchiveList.length > 0" class="border border-slate-300 dark:border-slate-800 rounded overflow-hidden">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                                <th class="py-2 px-2.5 text-center">#</th>
                                <th class="py-2 px-3">NO. BOX</th>
                                <th class="py-2 px-3">SUB-UNIT</th>
                                <th class="py-2 px-3">PERIODE / TGL SERAH</th>
                                <th class="py-2 px-3">LOKASI GUDANG & RAK</th>
                                <th class="py-2 px-3 text-center">BERKAS DOKUMEN</th>
                                <th class="py-2 px-3 text-center">STATUS</th>
                                <th class="py-2 px-3 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <template x-for="(arc, idx) in filteredArchiveList" :key="arc.id">
                                <tr @click="showArchiveDetail(arc)" class="hover:bg-purple-500/10 cursor-pointer transition">
                                    <td class="py-2 px-2.5 text-center font-mono font-bold text-slate-500" x-text="idx + 1"></td>
                                    <td class="py-2 px-3 font-mono font-bold text-amber-600 dark:text-amber-400" x-text="arc.box_number"></td>
                                    <td class="py-2 px-3 font-semibold text-slate-800 dark:text-slate-200" x-text="arc.sub_department"></td>
                                    <td class="py-2 px-3 font-mono text-[11px] text-slate-600 dark:text-slate-400">
                                        <div x-text="arc.periode_doc"></div>
                                        <div class="text-[10px] text-slate-500" x-text="'Serah: ' + arc.tgl_penyerahan"></div>
                                    </td>
                                    <td class="py-2 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                                        <div class="font-bold text-emerald-600 dark:text-emerald-400" x-text="arc.location"></div>
                                        <div class="text-[10px] text-slate-500" x-text="'Rak: ' + arc.rack_code + ' | Slot: ' + arc.slot_code"></div>
                                    </td>
                                    <td class="py-2 px-3 text-center font-mono">
                                        <span class="px-2 py-0.5 bg-blue-500/15 text-blue-700 dark:text-blue-300 border border-blue-500/30 rounded font-bold text-[11px]" x-text="arc.items_count + ' Arsip'"></span>
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border font-mono uppercase" :class="arc.status_badge" x-text="arc.status_label"></span>
                                    </td>
                                    <td class="py-2 px-3 text-right">
                                        <button 
                                            @click.stop="showArchiveDetail(arc)" 
                                            type="button" 
                                            class="px-2.5 py-1 bg-purple-600 hover:bg-purple-500 text-white rounded text-[11px] font-mono font-bold shadow-xs transition inline-flex items-center gap-1"
                                        >
                                            <i data-lucide="eye" class="w-3 h-3"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between font-mono text-xs shrink-0">
                <span class="text-slate-500 text-[11px]">Klik baris atau tombol Detail untuk membuka berkas-berkas dokumen di dalam box.</span>
                <button type="button" @click="openArchiveListModal = false; resetPos()" class="px-4 py-1.5 bg-slate-300 hover:bg-slate-400 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold rounded">
                    Tutup (Esc)
                </button>
            </div>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 6: DETAIL BERKAS DOKUMEN (MASTER-DETAIL VIEW) -->
    <template x-if="selectedArchiveDetail">
        <div x-data="{ 
                 posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
                 startDrag(e) { 
                     if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select') || e.target.closest('table')) return; 
                     this.isDragging = true; 
                     this.startX = e.clientX - this.posX; 
                     this.startY = e.clientY - this.posY; 
                 }, 
                 onDrag(e) { 
                     if (!this.isDragging) return; 
                     this.posX = e.clientX - this.startX; 
                     this.posY = e.clientY - this.startY; 
                 }, 
                 stopDrag() { this.isDragging = false; }, 
                 resetPos() { this.posX = 0; this.posY = 0; } 
             }"
             @mousemove.window="onDrag($event)" 
             @mouseup.window="stopDrag()"
             @keydown.escape.window="selectedArchiveDetail = null"
             class="fixed inset-0 z-[70] bg-slate-950/85 backdrop-blur-xs flex items-center justify-center p-4"
             style="z-index: 70;">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
                 class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-2xl w-full shadow-2xl overflow-hidden font-mono max-h-[92vh] flex flex-col">
                <!-- Window Title Bar (Draggable) -->
                <div @mousedown="startDrag($event)" 
                     :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                     title="Klik & tahan untuk menggeser/reposisi posisi form (Drag to move)"
                     class="bg-gradient-to-r from-amber-950 via-slate-800 to-purple-950 text-white px-3 py-2 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none shrink-0">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                        <i data-lucide="file-check" class="w-4 h-4 text-amber-400"></i> frmArchiveDocDetail : Rincian Butir Dokumen Box
                    </span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="selectedArchiveDetail = null; resetPos()" type="button" class="text-slate-400 hover:text-white text-sm font-bold px-1.5 py-0.5 rounded hover:bg-rose-600">✕</button>
                    </div>
                </div>

                <!-- Modal Body Content -->
                <div class="p-4 space-y-3 overflow-y-auto flex-1 font-sans text-xs">
                    <!-- Box Header Card -->
                    <div class="p-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-xs space-y-2 font-mono">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase block">NOMOR IDENTITAS BOX</span>
                                <span class="text-sm font-black text-amber-600 dark:text-amber-400" x-text="selectedArchiveDetail.box_number"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase block">STATUS GUDANG</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase" :class="selectedArchiveDetail.status_badge" x-text="selectedArchiveDetail.status_label"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-[11px]">
                            <div>
                                <span class="text-slate-500 block text-[10px]">SUB-DEPARTEMEN:</span>
                                <strong class="text-slate-900 dark:text-white" x-text="selectedArchiveDetail.sub_department"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">PERIODE PENGISIAN:</span>
                                <strong class="text-purple-700 dark:text-purple-300" x-text="selectedArchiveDetail.periode_doc"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">TGL. PENYERAHAN:</span>
                                <strong class="text-slate-800 dark:text-slate-200" x-text="selectedArchiveDetail.tgl_penyerahan"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">LOKASI PENYIMPANAN:</span>
                                <strong class="text-emerald-600 dark:text-emerald-400" x-text="selectedArchiveDetail.location"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">RAK & SLOT:</span>
                                <strong class="text-slate-800 dark:text-slate-200" x-text="'Rak: ' + selectedArchiveDetail.rack_code + ' | Slot: ' + selectedArchiveDetail.slot_code"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">KONDISI WADAH:</span>
                                <strong class="text-slate-800 dark:text-slate-200" x-text="selectedArchiveDetail.physical_condition"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Items Detail Table (1 Box -> Banyak Dokumen Arsip) -->
                    <div class="border border-slate-300 dark:border-slate-800 rounded bg-white dark:bg-slate-950 overflow-hidden shadow-xs">
                        <div class="bg-slate-100 dark:bg-slate-900 px-3 py-2 border-b border-slate-300 dark:border-slate-800 font-mono text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="list-checks" class="w-3.5 h-3.5 text-emerald-500"></i>
                                Rincian Berkas Dokumen di Dalam Box (<span x-text="selectedArchiveDetail.items ? selectedArchiveDetail.items.length : 0"></span> Berkas)
                            </span>
                            <span class="text-[10px] text-slate-500 font-normal">Format Standar Box TB 30g</span>
                        </div>

                        <div class="overflow-x-auto max-h-60">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 font-mono text-[10px] text-slate-600 dark:text-slate-400 select-none">
                                        <th class="py-1.5 px-2.5 w-10 text-center">NO</th>
                                        <th class="py-1.5 px-3">NAMA BERKAS / DOKUMEN ARSIP</th>
                                        <th class="py-1.5 px-3">PERIODE (KAPAN S/D KAPAN)</th>
                                        <th class="py-1.5 px-3">KETERANGAN</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <template x-for="it in (selectedArchiveDetail.items || [])" :key="it.item_number">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50">
                                            <td class="py-2 px-2.5 text-center font-mono font-bold text-purple-600 dark:text-purple-400" x-text="it.item_number"></td>
                                            <td class="py-2 px-3 font-bold text-slate-800 dark:text-slate-200" x-text="it.document_name"></td>
                                            <td class="py-2 px-3 font-mono text-amber-700 dark:text-amber-300 font-semibold" x-text="it.period_text || '-'"></td>
                                            <td class="py-2 px-3 text-slate-600 dark:text-slate-400" x-text="it.notes || '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!selectedArchiveDetail.items || selectedArchiveDetail.items.length === 0">
                                        <td colspan="4" class="py-4 text-center text-slate-500 font-mono text-xs">
                                            Belum ada berkas dokumen terdaftar.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Actions -->
                <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between font-mono text-xs shrink-0">
                    <a :href="selectedArchiveDetail.url" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded shadow-xs inline-flex items-center gap-1">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Buka Halaman Lengkap Arsip</span>
                    </a>
                    <button type="button" @click="selectedArchiveDetail = null; resetPos()" class="px-4 py-1.5 bg-slate-300 hover:bg-slate-400 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold rounded">
                        Tutup (Esc)
                    </button>
                </div>
            </div>
        </div>
    </template>