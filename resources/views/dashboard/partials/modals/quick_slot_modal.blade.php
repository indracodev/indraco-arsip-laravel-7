    <!-- ========================================================================= -->
    <!-- MODAL 2D QUICK SLOT ALLOCATION (RUANG -> RAK -> 100 SLOT GRID -> SAVE)    -->
    <!-- ========================================================================= -->
    <div x-show="quickSlotModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto"
         style="display: none; z-index: 9990 !important;"
         @keydown.escape.window="if(!confirmModalOpen) closeQuickSlotModal()">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl max-w-5xl w-full p-4 sm:p-6 shadow-2xl space-y-4 font-mono text-xs max-h-[92vh] flex flex-col my-auto"
             @click.away="if(!confirmModalOpen) closeQuickSlotModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-xl shrink-0">
                        <i data-lucide="layout-grid" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                Quick Layout 2D Alokasi Slot Rak
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40">
                                2D Grid Interaktif
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                            Pilih Ruang Gudang &rarr; Pilih Rak &rarr; Double Click Slot Kosong untuk Menyimpan.
                        </p>
                    </div>
                </div>

                <button type="button" @click="closeQuickSlotModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Target Archive Summary Ribbon & Navigation Breadcrumbs -->
            <div class="bg-slate-100 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shrink-0 space-y-2">
                <!-- Info Berkas Target -->
                <div class="flex flex-wrap items-center justify-between gap-2 text-[11px]">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-slate-500 font-bold uppercase shrink-0">Berkas Target:</span>
                        <span class="px-2 py-0.5 rounded bg-amber-500 text-slate-950 font-black shrink-0" x-text="targetArchive ? targetArchive.box_number : '-'"></span>
                        <span class="font-bold text-slate-800 dark:text-slate-100 truncate" x-text="targetArchive ? targetArchive.title : '-'"></span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 font-mono">
                        <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/30 font-bold" x-text="'Dept: ' + (targetArchive ? targetArchive.department_code : '-')"></span>
                    </div>
                </div>

                <!-- Breadcrumb Stepper Navigation -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800/80">
                    <div class="flex items-center gap-1.5 sm:gap-2 text-[11px] overflow-x-auto">
                        <!-- Step 1: Ruang -->
                        <button type="button" 
                                @click="goToStep('room')" 
                                class="px-2.5 py-1 rounded-lg font-bold flex items-center gap-1 transition"
                                :class="currentStep === 'room' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-700'">
                            <i data-lucide="warehouse" class="w-3.5 h-3.5"></i>
                            <span>1. Ruang Gudang</span>
                        </button>

                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>

                        <!-- Step 2: Rak -->
                        <button type="button" 
                                @click="if(selectedRoom) goToStep('rack')" 
                                :disabled="!selectedRoom"
                                class="px-2.5 py-1 rounded-lg font-bold flex items-center gap-1 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="currentStep === 'rack' ? 'bg-amber-500 text-slate-950 shadow-sm' : (selectedRoom ? 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-700' : 'text-slate-400')">
                            <i data-lucide="columns-3" class="w-3.5 h-3.5"></i>
                            <span x-text="selectedRoom ? ('2. ' + selectedRoom) : '2. Pilih Rak'"></span>
                        </button>

                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>

                        <!-- Step 3: Slot -->
                        <button type="button" 
                                :disabled="!selectedRack"
                                class="px-2.5 py-1 rounded-lg font-bold flex items-center gap-1 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="currentStep === 'slot' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-slate-400'">
                            <i data-lucide="grid" class="w-3.5 h-3.5"></i>
                            <span x-text="selectedRack ? ('3. Rak ' + selectedRack.rack_code + ' (100 Slot)') : '3. Denah Slot'"></span>
                        </button>
                    </div>

                    <!-- Tombol Kembali jika di step 2 atau 3 -->
                    <template x-if="currentStep !== 'room'">
                        <button type="button" 
                                @click="goBackStep()"
                                class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-[11px] font-bold transition flex items-center gap-1 shrink-0">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>Kembali</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Modal Body (Scrollable Container) -->
            <div class="flex-1 overflow-y-auto pr-1 space-y-4 min-h-[340px]">
                
                <!-- Loading State -->
                <div x-show="loadingLocations" class="py-16 text-center space-y-3 font-mono">
                    <div class="w-10 h-10 border-4 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-400">Memuat data denah 2D gudang & rak...</p>
                </div>

                <!-- STEP 1: PILIH RUANG GUDANG (2D ROOM CARDS) -->
                <div x-show="!loadingLocations && currentStep === 'room'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center gap-1.5">
                            <i data-lucide="map" class="w-4 h-4 text-amber-500"></i>
                            <span>Pilih Ruangan / Sektor Gudang 2D</span>
                        </span>
                        <span class="text-[11px] text-slate-500" x-text="'Total Ruang: ' + roomList.length"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <template x-for="room in roomList" :key="room.name">
                            <div class="p-4 rounded-xl border transition relative flex flex-col justify-between group cursor-pointer"
                                 :class="isRoomDisabled(room) 
                                    ? 'bg-slate-100 dark:bg-slate-900/50 border-slate-300 dark:border-slate-800 opacity-60 cursor-not-allowed' 
                                    : 'bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 hover:border-amber-500 hover:shadow-lg dark:hover:border-amber-500'"
                                 @click="if(!isRoomDisabled(room)) selectRoom(room.name)">
                                
                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="p-2 rounded-lg" :class="room.isFatLocked ? 'bg-purple-500/20 text-purple-600 dark:text-purple-400' : 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400'">
                                                <i data-lucide="warehouse" class="w-4 h-4"></i>
                                            </span>
                                            <div>
                                                <h4 class="font-extrabold text-sm text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition" x-text="room.name"></h4>
                                                <span class="text-[10px] text-slate-500" x-text="room.racks.length + ' Rak Fisik'"></span>
                                            </div>
                                        </div>

                                        <!-- Badge FAT / Umum -->
                                        <template x-if="room.isFatLocked">
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/40 whitespace-nowrap">
                                                🔒 FAT ONLY
                                            </span>
                                        </template>
                                        <template x-if="!room.isFatLocked">
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40 whitespace-nowrap">
                                                UMUM
                                            </span>
                                        </template>
                                    </div>

                                    <!-- Warning jika Dept bukan FAT -->
                                    <template x-if="isRoomDisabled(room)">
                                        <div class="p-2 rounded bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-[10px] font-bold flex items-center gap-1.5">
                                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                                            <span>Ruang ini dikunci khusus departemen FAT / Keuangan.</span>
                                        </div>
                                    </template>

                                    <!-- Kapasitas Ruang -->
                                    <div class="space-y-1 text-[10px]">
                                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                            <span>Total Terisi:</span>
                                            <span class="font-black text-slate-900 dark:text-white" x-text="room.usedBoxes + ' / ' + room.totalCapacity + ' Box (' + room.percent + '%)'"></span>
                                        </div>
                                        <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 transition-all duration-500"
                                                 :class="room.percent > 90 ? 'bg-rose-500' : (room.percent > 60 ? 'bg-amber-500' : 'bg-emerald-500')"
                                                 :style="'width: ' + room.percent + '%'"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Button Card -->
                                <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] font-bold" :class="room.availableSlots > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500'" x-text="room.availableSlots + ' Slot Tersedia'"></span>
                                    <button type="button" 
                                            :disabled="isRoomDisabled(room)"
                                            class="px-2.5 py-1 rounded bg-amber-500 group-hover:bg-amber-400 text-slate-950 font-black text-[10px] transition flex items-center gap-1 disabled:opacity-40">
                                        <span>Buka Ruang</span>
                                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- STEP 2: PILIH RAK GUDANG 2D (RACK CARDS) -->
                <div x-show="!loadingLocations && currentStep === 'rack'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center gap-1.5">
                            <i data-lucide="columns-3" class="w-4 h-4 text-amber-500"></i>
                            <span>Daftar Rak 2D di <strong class="text-amber-600 dark:text-amber-400" x-text="selectedRoom"></strong></span>
                        </span>
                        <span class="text-[11px] text-slate-500" x-text="'Total Rak: ' + (filteredRacks ? filteredRacks.length : 0)"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <template x-for="rack in filteredRacks" :key="rack.id">
                            <div class="p-3.5 rounded-xl border bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 hover:border-amber-500 hover:shadow-lg dark:hover:border-amber-500 transition relative flex flex-col justify-between group cursor-pointer"
                                 @click="selectRack(rack)">
                                
                                <div class="space-y-2">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="p-1.5 rounded-lg bg-blue-500/20 text-blue-600 dark:text-blue-400">
                                                <i data-lucide="archive" class="w-4 h-4"></i>
                                            </span>
                                            <div>
                                                <h4 class="font-black text-sm text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition" x-text="'Rak ' + rack.rack_code"></h4>
                                                <span class="text-[10px] text-slate-400 font-mono" x-text="(rack.total_sap || 5) + ' Sap × 20 Slot'"></span>
                                            </div>
                                        </div>

                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold"
                                              :class="getRackAvailableSlots(rack) > 0 ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30'"
                                              x-text="getRackAvailableSlots(rack) + ' Kosong'">
                                        </span>
                                    </div>

                                    <!-- Kapasitas Rak -->
                                    <div class="space-y-1 text-[10px]">
                                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                            <span>Terisi:</span>
                                            <span class="font-black text-slate-900 dark:text-white" x-text="(rack.current_box_count || 0) + ' / ' + (rack.box_capacity || 100) + ' Box'"></span>
                                        </div>
                                        <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 transition-all duration-500"
                                                 :class="((rack.current_box_count || 0) / (rack.box_capacity || 100) * 100) > 90 ? 'bg-rose-500' : (((rack.current_box_count || 0) / (rack.box_capacity || 100) * 100) > 60 ? 'bg-amber-500' : 'bg-emerald-500')"
                                                 :style="'width: ' + ((rack.current_box_count || 0) / (rack.box_capacity || 100) * 100) + '%'"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-2 mt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-400 font-mono">100 Slot Grid</span>
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-black flex items-center gap-1 group-hover:translate-x-0.5 transition">
                                        <span>Buka Denah</span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- STEP 3: DENAH 100 SLOT RAK 2D (5 SAP x 2 LAYER x 10 SLOTS) -->
                <div x-show="!loadingLocations && currentStep === 'slot' && selectedRack" class="space-y-3">
                    
                    <!-- Rack Detail Banner & Legend -->
                    <div class="bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl p-3 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-black text-slate-900 dark:text-white" x-text="'DENAH 2D RAK ' + (selectedRack ? selectedRack.rack_code : '')"></span>
                                <span class="text-[11px] text-slate-500 font-bold" x-text="'(' + selectedRoom + ')'"></span>
                            </div>
                            <p class="text-[10px] text-slate-500">
                                5 Tingkat Sap &times; 2 Layer (Atas & Bawah) &times; 10 Slot = 100 Slot Box Arsip
                            </p>
                        </div>

                        <!-- Legend & Status Indicators -->
                        <!-- Legend & Status Indicators -->
                        <div class="flex items-center gap-2 flex-wrap text-[10px]">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40 font-bold">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Slot Kosong (Bisa Dipilih)</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/40 font-bold">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>Terisi Berkas</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500 font-bold">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span>Mendekati Exp (H-30)</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-600 text-white border border-rose-400 font-bold shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-white"></span>
                                <span>Expired</span>
                            </span>
                        </div>
                    </div>

                    <!-- Guidance Tip Alert -->
                    <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200 text-[11px] font-bold flex items-center justify-between gap-2 shadow-xs">
                        <div class="flex items-center gap-2">
                            <i data-lucide="mouse-pointer-click" class="w-4 h-4 text-amber-500 shrink-0"></i>
                            <span>PETUNJUK: <strong class="underline decoration-amber-500 underline-offset-2">DOUBLE CLICK (Klik 2x Cepat)</strong> pada kotak slot hijau untuk menyimpan arsip ini ke slot tersebut!</span>
                        </div>
                        <template x-if="selectedSlot && selectedSlot.status === 'empty'">
                            <button type="button" 
                                    @click="onSlotDoubleClick(selectedSlot)" 
                                    class="px-3 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs transition shadow flex items-center gap-1 shrink-0 animate-bounce">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Simpan di <span x-text="selectedSlot.slot_code"></span></span>
                            </button>
                        </template>
                    </div>

                    <!-- 100 SLOTS GRID CONTAINER (Sap 5 down to Sap 1) -->
                    <div class="space-y-3 bg-slate-900 p-3 sm:p-4 rounded-2xl border-2 border-slate-700 shadow-inner">
                        <template x-for="sap in [5, 4, 3, 2, 1]" :key="'sap-' + sap">
                            <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-2.5 space-y-2">
                                <!-- Sap Level Header -->
                                <div class="flex items-center justify-between border-b border-slate-800/80 pb-1 text-[10px] font-bold text-slate-400">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-400 font-mono font-black" x-text="'SAP 0' + sap"></span>
                                        <span x-text="sap === 5 ? 'Tingkat 5 (Teratas)' : (sap === 1 ? 'Tingkat 1 (Terbawah)' : 'Tingkat ' + sap)"></span>
                                    </div>
                                    <span class="text-slate-500 font-mono">20 Slot Box (Layer Atas: 10, Layer Bawah: 10)</span>
                                </div>

                                <!-- Layer 1: Top Layer (Slot 11-20) -->
                                <div class="space-y-1">
                                    <div class="text-[9px] text-slate-500 font-mono uppercase tracking-wider flex items-center gap-1">
                                        <i data-lucide="arrow-up" class="w-2.5 h-2.5 text-blue-400"></i>
                                        <span>Baris Atas (Layer Top: 11 - 20)</span>
                                    </div>
                                    <div class="grid grid-cols-5 sm:grid-cols-10 gap-1.5">
                                        <template x-for="slot in getSlotsForSapAndLayer(sap, 'top')" :key="slot.id || (slot.slot_code)">
                                            <div @click="selectedSlot = slot"
                                                 @dblclick="onSlotDoubleClick(slot)"
                                                 class="p-1.5 rounded-lg border text-center transition select-none relative group"
                                                 :class="{
                                                     'bg-emerald-500/15 border-emerald-500/50 hover:bg-emerald-500 hover:text-slate-950 hover:shadow-lg hover:scale-105 cursor-pointer text-emerald-400': slot.status === 'empty' && selectedSlot?.id !== slot.id,
                                                     'bg-emerald-400 text-slate-950 ring-2 ring-amber-400 font-black scale-105 cursor-pointer': slot.status === 'empty' && selectedSlot?.id === slot.id,
                                                     'bg-amber-500/20 border-amber-500/40 text-amber-300 cursor-not-allowed opacity-90': (slot.status === 'filled' || slot.archive) && !isSlotNearExp(slot) && !isSlotExpired(slot),
                                                     'bg-rose-500/20 border border-rose-500 text-rose-300 cursor-not-allowed opacity-95': isSlotNearExp(slot) && !isSlotExpired(slot),
                                                     'bg-rose-600 border border-rose-400 text-white cursor-not-allowed font-black shadow-md': isSlotExpired(slot),
                                                     'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed': slot.status === 'inactive'
                                                 }"
                                                 :title="slot.status === 'empty' ? ('Slot ' + slot.slot_code + ' (Kosong) - Double click untuk simpan!') : (isSlotExpired(slot) ? ('EXPIRED: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Expired')) : (isSlotNearExp(slot) ? ('MENDEKATI EXP: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Mendekati Exp')) : ('Terisi: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Terisi'))))">
                                                <div class="font-mono font-black text-[11px] truncate" 
                                                     :class="isSlotExpired(slot) ? '!text-white' : (isSlotNearExp(slot) ? 'text-rose-300' : (slot.status === 'empty' ? 'text-emerald-400' : 'text-amber-300'))" 
                                                     x-text="slot.slot_code"></div>
                                                <div class="text-[9px] truncate font-sans">
                                                    <template x-if="slot.status === 'empty'">
                                                        <span class="text-emerald-500 group-hover:text-slate-950 font-bold">KOSONG</span>
                                                    </template>
                                                    <template x-if="!isSlotExpired(slot) && !isSlotNearExp(slot) && (slot.status === 'filled' || slot.archive)">
                                                        <span class="text-amber-400 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="!isSlotExpired(slot) && isSlotNearExp(slot)">
                                                        <span class="text-rose-300 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="isSlotExpired(slot)">
                                                        <span class="!text-white font-black tracking-wider">EXPIRED</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Layer 2: Bottom Layer (Slot 01-10) -->
                                <div class="space-y-1 pt-1">
                                    <div class="text-[9px] text-slate-500 font-mono uppercase tracking-wider flex items-center gap-1">
                                        <i data-lucide="arrow-down" class="w-2.5 h-2.5 text-amber-400"></i>
                                        <span>Baris Bawah (Layer Bottom: 01 - 10)</span>
                                    </div>
                                    <div class="grid grid-cols-5 sm:grid-cols-10 gap-1.5">
                                        <template x-for="slot in getSlotsForSapAndLayer(sap, 'bottom')" :key="slot.id || (slot.slot_code)">
                                            <div @click="selectedSlot = slot"
                                                 @dblclick="onSlotDoubleClick(slot)"
                                                 class="p-1.5 rounded-lg border text-center transition select-none relative group"
                                                 :class="{
                                                     'bg-emerald-500/15 border-emerald-500/50 hover:bg-emerald-500 hover:text-slate-950 hover:shadow-lg hover:scale-105 cursor-pointer text-emerald-400': slot.status === 'empty' && selectedSlot?.id !== slot.id,
                                                     'bg-emerald-400 text-slate-950 ring-2 ring-amber-400 font-black scale-105 cursor-pointer': slot.status === 'empty' && selectedSlot?.id === slot.id,
                                                     'bg-amber-500/20 border-amber-500/40 text-amber-300 cursor-not-allowed opacity-90': (slot.status === 'filled' || slot.archive) && !isSlotNearExp(slot) && !isSlotExpired(slot),
                                                     'bg-rose-500/20 border border-rose-500 text-rose-300 cursor-not-allowed opacity-95': isSlotNearExp(slot) && !isSlotExpired(slot),
                                                     'bg-rose-600 border border-rose-400 text-white cursor-not-allowed font-black shadow-md': isSlotExpired(slot),
                                                     'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed': slot.status === 'inactive'
                                                 }"
                                                 :title="slot.status === 'empty' ? ('Slot ' + slot.slot_code + ' (Kosong) - Double click untuk simpan!') : (isSlotExpired(slot) ? ('EXPIRED: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Expired')) : (isSlotNearExp(slot) ? ('MENDEKATI EXP: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Mendekati Exp')) : ('Terisi: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Terisi'))))">
                                                <div class="font-mono font-black text-[11px] truncate" 
                                                     :class="isSlotExpired(slot) ? '!text-white' : (isSlotNearExp(slot) ? 'text-rose-300' : (slot.status === 'empty' ? 'text-emerald-400' : 'text-amber-300'))" 
                                                     x-text="slot.slot_code"></div>
                                                <div class="text-[9px] truncate font-sans">
                                                    <template x-if="slot.status === 'empty'">
                                                        <span class="text-emerald-500 group-hover:text-slate-950 font-bold">KOSONG</span>
                                                    </template>
                                                    <template x-if="!isSlotExpired(slot) && !isSlotNearExp(slot) && (slot.status === 'filled' || slot.archive)">
                                                        <span class="text-amber-400 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="!isSlotExpired(slot) && isSlotNearExp(slot)">
                                                        <span class="text-rose-300 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="isSlotExpired(slot)">
                                                        <span class="!text-white font-black tracking-wider">EXPIRED</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer Controls -->
            <div class="border-t border-slate-200 dark:border-slate-800 pt-3 flex items-center justify-between gap-2 shrink-0">
                <div class="text-[11px] text-slate-500 font-mono">
                    <template x-if="selectedSlot && selectedSlot.status === 'empty'">
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="'Slot Terpilih: ' + selectedSlot.slot_code + ' (' + (selectedSlot.layer === 'top' ? 'Baris Atas' : 'Baris Bawah') + ', Sap ' + selectedSlot.sap_level + ')'"></span>
                    </template>
                    <template x-if="!selectedSlot || selectedSlot.status !== 'empty'">
                        <span>Tekan <strong class="text-amber-500">Esc</strong> atau klik Batal untuk menutup.</span>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="closeQuickSlotModal()" 
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                        Batal
                    </button>
                    
                    <template x-if="currentStep === 'slot' && selectedSlot && selectedSlot.status === 'empty'">
                        <button type="button" 
                                @click="onSlotDoubleClick(selectedSlot)" 
                                class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-black text-xs shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            <span>Simpan di Slot Ini</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- POP UP KONFIRMASI PENYIMPANAN SLOT (TRIGGERED ON DBLCLICK) -->
    <div x-show="confirmModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md"
         style="display: none; z-index: 99999 !important;"
         @keydown.escape.window="if(confirmModalOpen) confirmModalOpen = false">
        
        <div class="bg-white dark:bg-slate-900 border-2 border-emerald-500 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 font-mono text-xs animate-in fade-in zoom-in-95 duration-200 relative"
             style="z-index: 100000 !important;"
             @click.away="if(!savingSlot) confirmModalOpen = false">
            
            <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="p-3 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl shrink-0">
                    <i data-lucide="help-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                        Konfirmasi Penempatan Slot
                    </h3>
                    <p class="text-[11px] text-slate-500">Simpan berkas arsip ke slot gudang fisik.</p>
                </div>
            </div>

            <!-- Detail Konfirmasi Box & Slot -->
            <div class="p-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2.5">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">BERKAS ARSIP:</span>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-amber-500 text-slate-950 font-black text-xs" x-text="targetArchive ? targetArchive.box_number : '-'"></span>
                        <span class="font-bold text-slate-900 dark:text-white text-xs truncate" x-text="targetArchive ? targetArchive.title : '-'"></span>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">LOKASI SLOT YANG DIPILIH:</span>
                    <div class="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 font-mono space-y-1">
                        <div class="text-base font-black flex items-center gap-1.5">
                            <i data-lucide="map-pin" class="w-4 h-4 text-emerald-500"></i>
                            <span x-text="selectedRoom + ' - ' + (slotToSave ? slotToSave.slot_code : '')"></span>
                        </div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-400 font-sans">
                            <span x-text="'Rak: ' + (selectedRack ? selectedRack.rack_code : '')"></span> &bull; 
                            <span x-text="'Sap ' + (slotToSave ? slotToSave.sap_level : '')"></span> &bull; 
                            <span x-text="slotToSave ? (slotToSave.layer === 'top' ? 'Baris Atas (Top)' : 'Baris Bawah (Bottom)') : ''"></span>
                        </div>
                    </div>
                </div>

                <div class="p-2.5 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-700 dark:text-blue-300 text-[11px] font-sans">
                    Apakah Anda yakin akan menyimpan berkas arsip ini pada slot <strong><span x-text="slotToSave ? slotToSave.slot_code : ''"></span></strong>?
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" 
                        @click="confirmModalOpen = false" 
                        :disabled="savingSlot"
                        class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition disabled:opacity-50">
                    Batal
                </button>
                
                <button type="button" 
                        @click="saveSlotAllocation()" 
                        :disabled="savingSlot"
                        class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-black text-xs shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                    <template x-if="savingSlot">
                        <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    </template>
                    <template x-if="!savingSlot">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </template>
                    <span x-text="savingSlot ? 'Menyimpan...' : 'Ya, Simpan di Slot Ini'"></span>
                </button>
            </div>
        </div>
    </div>


