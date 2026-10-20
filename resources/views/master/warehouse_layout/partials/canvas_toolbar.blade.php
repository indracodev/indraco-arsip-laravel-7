<!-- Canvas Unified Toolbar (Square Desktop Style) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 shadow-xs space-y-2 font-mono">
        <!-- Row 1: Search Bar + Department Filter + Fullscreen Toggle -->
        <div class="flex flex-wrap items-center justify-between gap-2">
            <!-- Search Input & Live Autocomplete Dropdown -->
            <div class="relative flex-1 min-w-[200px]" @click.away="searchDropdownOpen = false">
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </div>
                    <input 
                        type="text" 
                        x-model="searchQuery" 
                        @input="handleSearchInput()" 
                        @focus="if(searchQuery.trim().length > 0) searchDropdownOpen = true"
                        @keydown.enter="selectFirstSearchResult()"
                        @keydown.escape="searchDropdownOpen = false"
                        placeholder="Cari Gudang, Rak, Box, atau No. Arsip... (Ctrl+F)" 
                        class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                    >
                    <button 
                        x-show="searchQuery" 
                        @click="searchQuery = ''; searchDropdownOpen = false; clearBlink()" 
                        type="button" 
                        class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                    >
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <!-- Live Search Categorized Dropdown -->
                <div 
                    x-show="searchDropdownOpen && (searchResults.rooms.length > 0 || searchResults.racks.length > 0 || searchResults.boxes.length > 0)" 
                    x-cloak 
                    class="absolute left-0 right-0 top-full mt-1 z-50 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded shadow-xl overflow-hidden max-h-96 overflow-y-auto"
                >
                    <!-- Category: Gudang (Rooms) -->
                    <template x-if="searchResults.rooms.length > 0">
                        <div class="p-1.5 border-b border-slate-200 dark:border-slate-800">
                            <div class="text-[10px] font-bold font-mono uppercase text-emerald-600 dark:text-emerald-400 px-2 py-0.5 flex items-center gap-1.5">
                                <i data-lucide="square" class="w-3 h-3"></i> Gudang / Sektor (<span x-text="searchResults.rooms.length"></span>)
                            </div>
                            <template x-for="room in searchResults.rooms" :key="'room-' + room.id">
                                <button 
                                    @click="selectSearchResult('room', room)" 
                                    type="button" 
                                    class="w-full text-left px-2 py-1 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded transition flex items-center justify-between group cursor-pointer text-xs"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-none bg-emerald-500"></span>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400" x-text="room.rack_code || room.room_sector"></span>
                                    </div>
                                    <span class="text-[10px] font-mono text-slate-400 group-hover:text-emerald-500">Layout Berkedip ➔</span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <!-- Category: Rak (Racks) -->
                    <template x-if="searchResults.racks.length > 0">
                        <div class="p-1.5 border-b border-slate-200 dark:border-slate-800">
                            <div class="text-[10px] font-bold font-mono uppercase text-blue-600 dark:text-blue-400 px-2 py-0.5 flex items-center gap-1.5">
                                <i data-lucide="rectangle-horizontal" class="w-3 h-3"></i> Rak Penyimpanan (<span x-text="searchResults.racks.length"></span>)
                            </div>
                            <template x-for="rack in searchResults.racks" :key="'rack-' + rack.id">
                                <button 
                                    @click="selectSearchResult('rack', rack)" 
                                    type="button" 
                                    class="w-full text-left px-2 py-1 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded transition flex items-center justify-between group cursor-pointer text-xs"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-none" :style="{ backgroundColor: rack.custom_color || rack.status_color || '#3b82f6' }"></span>
                                        <div>
                                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400" x-text="rack.rack_code"></span>
                                            <span class="text-[10px] text-slate-400 font-mono ml-1" x-text="'(' + (rack.room_sector || 'Umum') + ' • ' + (rack.current_box_count || 0) + '/' + (rack.box_capacity || 100) + ')'"></span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-mono text-slate-400 group-hover:text-blue-500">Rak Berkedip ➔</span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <!-- Category: Box / Slot / Dokumen Arsip -->
                    <template x-if="searchResults.boxes.length > 0">
                        <div class="p-1.5">
                            <div class="text-[10px] font-bold font-mono uppercase text-amber-600 dark:text-amber-400 px-2 py-0.5 flex items-center gap-1.5">
                                <i data-lucide="package" class="w-3 h-3"></i> Box / Slot Arsip (<span x-text="searchResults.boxes.length"></span>)
                            </div>
                            <template x-for="box in searchResults.boxes" :key="'box-' + box.rack.id + '-' + box.slot_code">
                                <button 
                                    @click="selectSearchResult('box', box)" 
                                    type="button" 
                                    class="w-full text-left px-2 py-1.5 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded transition flex items-center justify-between group border-b border-slate-100 dark:border-slate-800/40 last:border-0 cursor-pointer text-xs"
                                >
                                    <div class="flex items-start gap-2 min-w-0">
                                        <span class="mt-0.5 w-2 h-2 rounded-none shrink-0" :class="box.archive ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-xs font-mono font-black text-amber-700 dark:text-amber-400" x-text="box.slot_code"></span>
                                                <span class="text-[10px] font-mono px-1.5 py-0.2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded" x-text="'Rak: ' + box.rack.rack_code"></span>
                                                <span class="text-[10px] font-mono text-slate-400" x-text="'(LVL ' + box.sap_level + ')'"></span>
                                            </div>
                                            <template x-if="box.archive">
                                                <div class="text-[11px] text-slate-600 dark:text-slate-300 truncate font-medium mt-0.5">
                                                    <span class="font-bold text-slate-800 dark:text-white" x-text="box.archive.box_number ? '[' + box.archive.box_number + '] ' : ''"></span>
                                                    <span x-text="box.archive.title"></span>
                                                </div>
                                            </template>
                                            <template x-if="!box.archive">
                                                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 italic">Slot Kosong Siap Pakai</div>
                                            </template>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-mono text-amber-600 dark:text-amber-400 shrink-0 font-bold ml-2">Buka Denah ➔</span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Filter Department -->
            <div class="shrink-0">
                <select x-model="filterDepartment" @change="renderCanvas()" class="px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:border-amber-500 shadow-xs">
                    <option value="">-- Semua Departemen --</option>
                    <template x-for="dept in departments" :key="dept.id">
                        <option :value="dept.id" x-text="dept.code + ' - ' + dept.name"></option>
                    </template>
                </select>
            </div>

            <!-- Fullscreen Mode Toggle Button -->
            <button 
                @click="toggleFullscreen()" 
                type="button" 
                class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-mono font-bold text-xs rounded border border-indigo-700 shadow-xs transition flex items-center gap-1.5 active:scale-95 whitespace-nowrap cursor-pointer shrink-0"
                :title="isFullscreen ? 'Keluar Mode Fullscreen (Esc)' : 'Tampilkan Canvas 1 Layar Penuh (Fullscreen)'"
            >
                <i data-lucide="maximize-2" x-show="!isFullscreen" class="w-3.5 h-3.5"></i>
                <i data-lucide="minimize-2" x-show="isFullscreen" class="w-3.5 h-3.5" x-cloak></i>
                <span x-text="isFullscreen ? 'Keluar Fullscreen (Esc)' : '1 Layar Penuh (Fullscreen)'"></span>
            </button>
        </div>

        <!-- Row 2: Add Object Actions + Canvas Dimension + Zoom Controls -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-1.5 border-t border-slate-200 dark:border-slate-800">
            <!-- Add Object Action Group -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <button @click="openAddRoomModal()" type="button" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-mono font-bold text-xs rounded border border-emerald-700 shadow-xs transition flex items-center gap-1.5 active:scale-95 whitespace-nowrap cursor-pointer">
                    <i data-lucide="square" class="w-3.5 h-3.5"></i>
                    <span>+ Tambah Gudang (Square)</span>
                </button>
                <button @click="openAddRackModal()" type="button" class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white font-mono font-bold text-xs rounded border border-blue-700 shadow-xs transition flex items-center gap-1.5 active:scale-95 whitespace-nowrap cursor-pointer">
                    <i data-lucide="rectangle-horizontal" class="w-3.5 h-3.5"></i>
                    <span>+ Tambah Rak (Rectangle)</span>
                </button>
            </div>

            <!-- Canvas Dimension & Zoom Controls (Right) -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Canvas Dimension / Resizer Button -->
                <button 
                    @click="openCanvasResizeModal()" 
                    type="button" 
                    class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-mono font-bold text-xs rounded border border-slate-400 dark:border-slate-600 shadow-xs transition flex items-center gap-1.5 active:scale-95 whitespace-nowrap cursor-pointer"
                    title="Ubah Dimensi (Lebar & Tinggi) Canvas Workspace Layout"
                >
                    <i data-lucide="scaling" class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400"></i>
                    <span>Canvas: <strong class="text-cyan-700 dark:text-cyan-300 font-mono" x-text="canvasWidth + 'x' + canvasHeight + 'px'"></strong></span>
                </button>

                <!-- Zoom Controls -->
                <div class="flex items-center bg-slate-200 dark:bg-slate-800 border border-slate-400 dark:border-slate-600 rounded p-0.5 shadow-xs">
                    <button @click="zoomOut()" type="button" class="p-1 hover:bg-white dark:hover:bg-slate-700 rounded text-slate-700 dark:text-slate-300 transition cursor-pointer" title="Zoom Out">
                        <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                    </button>
                    <span class="px-2 text-xs font-mono font-bold text-amber-600 dark:text-amber-400 select-none" x-text="Math.round(scale * 100) + '%'"></span>
                    <button @click="zoomIn()" type="button" class="p-1 hover:bg-white dark:hover:bg-slate-700 rounded text-slate-700 dark:text-slate-300 transition cursor-pointer" title="Zoom In">
                        <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i>
                    </button>
                    <button @click="resetZoom()" type="button" class="p-1 hover:bg-white dark:hover:bg-slate-700 rounded text-slate-700 dark:text-slate-300 transition cursor-pointer" title="Reset Scale">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend & Mode Bar (Square Desktop Style) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 shadow-xs flex flex-wrap items-center justify-between gap-3 text-xs font-mono">
        <div class="flex flex-wrap items-center gap-3">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px]">Indikator Warna Rak (Default Kapasitas):</span>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-none bg-emerald-500 border border-emerald-600"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Hijau (0% - 50%)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-none bg-yellow-500 border border-yellow-600"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Kuning (51% - 80%)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-none bg-orange-500 border border-orange-600"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Orange (81% - 90%)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-none bg-rose-500 border border-rose-600"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Merah (91% - 100%)</span>
            </div>
            <div class="flex items-center gap-1.5 border-l border-slate-300 dark:border-slate-800 pl-3">
                <span class="w-3 h-3 rounded-none bg-purple-500 border border-purple-600"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Custom Color</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium">Petunjuk:</span>
            <span class="px-2 py-0.5 bg-amber-500/15 text-amber-700 dark:text-amber-300 rounded border border-amber-500/30 font-bold font-mono text-[11px]">
                Mouse Drag / Panah (&uarr; &darr; &larr; &rarr;) | Shift + Klik: Multi-Select | Rotate (Key: R) | Hapus (Key: Delete) | Copy & Paste (Ctrl+C / Ctrl+V)
            </span>
        </div>
    </div>

    