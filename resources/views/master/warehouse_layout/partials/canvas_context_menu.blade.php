<!-- Floating Right-Click Context Menu (Delphi & Win32 Workstation Style) -->
            <div 
                x-show="contextMenuOpen" 
                x-cloak 
                @click.away="contextMenuOpen = false"
                :style="{ top: contextMenuY + 'px', left: contextMenuX + 'px' }"
                class="fixed z-50 bg-slate-900/98 dark:bg-slate-950/98 border border-slate-700 shadow-2xl rounded p-1.5 w-72 text-xs font-mono text-slate-200 select-none space-y-0.5"
            >
                <!-- Context Menu Title Header -->
                <div class="px-2.5 py-1 mb-1 bg-slate-800 dark:bg-slate-900 border-b border-slate-700 rounded flex items-center justify-between gap-2 select-none">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="w-2 h-2 rounded-none shrink-0" 
                              :class="contextMenuTarget?.is_locked ? 'bg-amber-400 animate-pulse' : (contextMenuTarget?.is_fat_locked ? 'bg-purple-400' : 'bg-emerald-400')"></span>
                        <span class="font-mono font-bold text-xs text-amber-400 truncate uppercase" 
                              x-text="selectedLocations.length > 1 
                                  ? 'MULTI-SELECT (' + selectedLocations.length + ')' 
                                  : (contextMenuTarget 
                                      ? (contextMenuTarget.location_type === 'room' ? 'GUDANG: ' + contextMenuTarget.rack_code : 'RAK: ' + contextMenuTarget.rack_code) 
                                      : 'CANVAS MENU')">
                        </span>
                    </div>
                    <span class="font-mono text-[10px] text-slate-400 shrink-0 bg-slate-950 px-1 py-0.2 rounded border border-slate-800" 
                          x-text="'X:' + clickedCanvasX + ' Y:' + clickedCanvasY">
                    </span>
                </div>
                
                <!-- Actions when MULTIPLE objects are selected (Multi-Selection Batch Actions) -->
                <template x-if="selectedLocations.length > 1">
                    <div class="space-y-0.5">
                        <div class="px-2 py-1 bg-indigo-500/15 border border-indigo-500/30 rounded text-[10px] font-bold text-indigo-300 mb-1 flex items-center justify-between">
                            <span class="flex items-center gap-1"><i data-lucide="layers" class="w-3 h-3 text-indigo-400"></i> BATCH SETUP</span>
                            <span class="font-mono" x-text="selectedLocations.length + ' OBJEK'"></span>
                        </div>

                        <button 
                            @click="bulkToggleLockLocations(false); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-emerald-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="unlock" class="w-3.5 h-3.5 text-emerald-400 shrink-0"></i>
                                <span class="truncate">Buka Kunci (Unlock) Kelompok</span>
                            </div>
                        </button>

                        <button 
                            @click="bulkToggleLockLocations(true); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-amber-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="lock" class="w-3.5 h-3.5 text-amber-400 shrink-0"></i>
                                <span class="truncate">Kunci (Lock) Kelompok</span>
                            </div>
                        </button>

                        <div class="border-t border-slate-800 my-1"></div>

                        <button 
                            @click="openBatchResizeModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-blue-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="scaling" class="w-3.5 h-3.5 text-blue-400 shrink-0"></i>
                                <span class="truncate">Ubah Ukuran Massal (W & H)...</span>
                            </div>
                        </button>

                        @if (auth()->check() && auth()->user()->isSuperAdmin())
                        <button 
                            @click="openBatchDeptModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-purple-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="building-2" class="w-3.5 h-3.5 text-purple-400 shrink-0"></i>
                                <span class="truncate">Set Alokasi Departemen...</span>
                            </div>
                        </button>
                        @endif

                        <button 
                            @click="openBatchColorModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-pink-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="palette" class="w-3.5 h-3.5 text-pink-400 shrink-0"></i>
                                <span class="truncate">Set Warna Custom Massal...</span>
                            </div>
                        </button>

                        <button 
                            @click="rotateLocation(null, 90); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-cyan-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-cyan-400 shrink-0"></i>
                                <span class="truncate">Putar Kelompok 90°</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">R</kbd>
                        </button>

                        <div class="border-t border-slate-800 my-1"></div>

                        <button 
                            @click="alignGroupHorizontally(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-teal-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="align-horizontal-space-around" class="w-3.5 h-3.5 text-teal-400 shrink-0"></i>
                                <span class="truncate">Rapatkan Horizontal (Baris)</span>
                            </div>
                        </button>

                        <button 
                            @click="alignGroupVertically(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-teal-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="align-vertical-space-around" class="w-3.5 h-3.5 text-teal-400 shrink-0"></i>
                                <span class="truncate">Rapatkan Vertikal (Kolom)</span>
                            </div>
                        </button>

                        <div class="border-t border-slate-800 my-1"></div>

                        <button 
                            @click="deleteSelectedLocation(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-rose-900/40 text-rose-400 hover:text-rose-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i>
                                <span class="truncate">Hapus Kelompok Objek</span>
                            </div>
                            <kbd class="text-[10px] text-rose-500/70 font-mono shrink-0">Del</kbd>
                        </button>
                    </div>
                </template>

                <!-- Actions when Right Clicked on a SINGLE GUDANG / ROOM object -->
                <template x-if="selectedLocations.length <= 1 && contextMenuTarget && contextMenuTarget.location_type === 'room'">
                    <div class="space-y-0.5">
                        <button 
                            @click="addRackInsideRoom(contextMenuTarget, clickedCanvasX, clickedCanvasY, 1)" 
                            type="button" 
                            class="w-full text-left px-2 py-1 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 rounded border border-emerald-500/40 transition flex items-center justify-between font-bold shadow-xs cursor-pointer mb-1"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="plus-square" class="w-3.5 h-3.5 text-emerald-400 shrink-0"></i>
                                <span class="truncate">Tambah Rak ke Gudang...</span>
                            </div>
                            <span class="text-xs px-1.5 py-0.5 bg-emerald-500/20 text-emerald-300 rounded font-mono font-bold border border-emerald-500/30 shrink-0">1 - 50 Rak</span>
                        </button>

                        <div class="border-t border-slate-800 my-1"></div>

                        <button 
                            @click="toggleLockLocation(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-amber-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i :data-lucide="contextMenuTarget?.is_locked !== false ? 'unlock' : 'lock'" 
                                   class="w-3.5 h-3.5" 
                                   :class="contextMenuTarget?.is_locked !== false ? 'text-amber-400' : 'text-slate-400'"></i>
                                <span class="truncate" x-text="contextMenuTarget?.is_locked !== false ? 'Buka Kunci (Unlock) Gudang' : 'Kunci (Lock) Posisi Gudang'"></span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">L</kbd>
                        </button>

                        <button 
                            @click="rotateLocation(contextMenuTarget, 90); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-cyan-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-cyan-400 shrink-0"></i>
                                <span class="truncate">Putar Gudang 90°</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">R</kbd>
                        </button>

                        <button 
                            @click="duplicateRack(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-purple-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="copy" class="w-3.5 h-3.5 text-purple-400 shrink-0"></i>
                                <span class="truncate">Duplikat Gudang (Copy)</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">Ctrl+D</kbd>
                        </button>

                        <div class="border-t border-slate-800 my-1"></div>

                        <button 
                            @click="openEditModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-sky-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="sliders" class="w-3.5 h-3.5 text-sky-400 shrink-0"></i>
                                <span class="truncate">Properti & Detail Gudang...</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">F2</kbd>
                        </button>

                        <button 
                            @click="deleteSelectedLocation(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-rose-900/40 text-rose-400 hover:text-rose-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i>
                                <span class="truncate">Hapus Gudang dari Canvas</span>
                            </div>
                            <kbd class="text-[10px] text-rose-500/70 font-mono shrink-0">Del</kbd>
                        </button>
                    </div>
                </template>

                <!-- Actions when Right Clicked on a SINGLE RAK object -->
                <template x-if="selectedLocations.length <= 1 && contextMenuTarget && contextMenuTarget.location_type !== 'room'">
                    <div class="space-y-0.5">
                        <!-- Primary Action: Visualisasi 100 Slot Rak -->
                        <button 
                            @click="openRackGridModal(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 rounded border border-amber-500/40 transition flex items-center justify-between font-bold shadow-xs cursor-pointer mb-1"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-amber-400 shrink-0"></i>
                                <span class="truncate">Visualisasi 100 Slot Rak</span>
                            </div>
                            <span class="text-xs px-1.5 py-0.5 bg-amber-500/20 text-amber-300 rounded font-mono font-bold border border-amber-500/30 shrink-0">5 LVL</span>
                        </button>

                        <div class="border-t border-slate-800 my-1"></div>

                        <!-- Kunci / Buka Kunci Posisi -->
                        <button 
                            @click="toggleLockLocation(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-amber-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i :data-lucide="contextMenuTarget?.is_locked !== false ? 'unlock' : 'lock'" 
                                   class="w-3.5 h-3.5" 
                                   :class="contextMenuTarget?.is_locked !== false ? 'text-amber-400' : 'text-slate-400'"></i>
                                <span class="truncate" x-text="contextMenuTarget?.is_locked !== false ? 'Buka Kunci (Unlock) Rak' : 'Kunci (Lock) Posisi Rak'"></span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">L</kbd>
                        </button>

                        <!-- Putar Rak 90° -->
                        <button 
                            @click="rotateLocation(contextMenuTarget, 90); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-cyan-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-cyan-400 shrink-0"></i>
                                <span class="truncate">Putar Posisi Rak 90°</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">R</kbd>
                        </button>

                        <!-- Duplikat Rak -->
                        <button 
                            @click="duplicateRack(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-purple-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="copy" class="w-3.5 h-3.5 text-purple-400 shrink-0"></i>
                                <span class="truncate">Duplikat Rak (Copy)</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">Ctrl+D</kbd>
                        </button>

                        <!-- Pindahkan ke Gudang Lain -->
                        <button 
                            @click="openMoveModal(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-amber-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="truck" class="w-3.5 h-3.5 text-amber-400 shrink-0"></i>
                                <span class="truncate">Pindahkan ke Gudang...</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">M</kbd>
                        </button>

                        <div class="border-t border-slate-800 my-1"></div>

                        <!-- Properti & Edit Details -->
                        <button 
                            @click="openEditModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-sky-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="sliders" class="w-3.5 h-3.5 text-sky-400 shrink-0"></i>
                                <span class="truncate">Properti, Nama & Warna...</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">F2</kbd>
                        </button>

                        <!-- Hapus Rak -->
                        <button 
                            @click="deleteSelectedLocation(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-rose-900/40 text-rose-400 hover:text-rose-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i>
                                <span class="truncate">Hapus Rak dari Canvas</span>
                            </div>
                            <kbd class="text-[10px] text-rose-500/70 font-mono shrink-0">Del</kbd>
                        </button>
                    </div>
                </template>

                <!-- Actions when Right Clicked on Empty Canvas Space -->
                <template x-if="selectedLocations.length <= 1 && !contextMenuTarget">
                    <div class="space-y-0.5">
                        <button 
                            @click="openAddRoomModal(clickedCanvasX, clickedCanvasY); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-emerald-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="square" class="w-3.5 h-3.5 text-emerald-400 shrink-0"></i>
                                <span class="truncate">Tambah Gudang (Square)</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">G</kbd>
                        </button>
                        
                        <button 
                            @click="openAddRackModal(clickedCanvasX, clickedCanvasY); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-2 py-1 hover:bg-slate-800 text-slate-200 hover:text-blue-300 rounded transition flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="rectangle-horizontal" class="w-3.5 h-3.5 text-blue-400 shrink-0"></i>
                                <span class="truncate">Tambah Rak (Rectangle)</span>
                            </div>
                            <kbd class="text-[10px] text-slate-500 font-mono shrink-0">R</kbd>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        