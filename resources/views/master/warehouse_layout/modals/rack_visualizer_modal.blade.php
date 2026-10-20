    <!-- Modal: 100-Box Rack Visualizer (5 Sap x 20 Box) - Desktop Square Edition -->
    <div x-show="rackGridModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/60 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4 overflow-hidden font-mono">
        <div @click.away="rackGridModalOpen = false" class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 rounded max-w-6xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden">
            <!-- Modal Header (Delphi Window Titlebar) -->
            <div class="px-4 py-2.5 bg-slate-100 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded bg-indigo-100 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-xs">
                        <i data-lucide="layout-grid" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-xs font-bold text-slate-900 dark:text-white tracking-wide flex items-center gap-1.5 uppercase">
                                <span x-text="'Denah Rak: ' + (selectedRackForModal?.rack_code || 'RAK')"></span>
                            </h2>
                            <span class="px-1.5 py-0.2 bg-indigo-100 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold rounded">
                                100 Box (TB 30g)
                            </span>
                            <!-- Department Restriction Badge (Fitur Baru) -->
                            <template x-if="getCurrentRackAllowedDeptIds().length > 0">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <template x-for="deptId in getCurrentRackAllowedDeptIds()" :key="deptId">
                                        <span class="px-2 py-0.5 bg-indigo-500/15 border border-indigo-500/30 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold rounded flex items-center gap-1 font-mono">
                                            <i data-lucide="building" class="w-3 h-3 text-indigo-500"></i>
                                            <span x-text="'KHUSUS ' + getDeptCode(deptId)"></span>
                                        </span>
                                    </template>
                                </div>
                            </template>
                            <template x-if="getCurrentRackAllowedDeptIds().length === 0">
                                <span class="px-2 py-0.5 bg-slate-500/15 border border-slate-500/30 text-slate-700 dark:text-slate-300 text-[10px] font-bold rounded flex items-center gap-1 font-mono">
                                    <i data-lucide="globe" class="w-3 h-3 text-slate-400"></i>
                                    <span>SEMUA DEPT (BEBAS)</span>
                                </span>
                            </template>
                        </div>
                        <p class="text-[11px] text-slate-500 font-sans flex items-center gap-1.5 flex-wrap">
                            <span x-text="'Sektor: ' + (selectedRackForModal?.room_sector || 'Umum')"></span>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <span :class="getCurrentRackAllowedDeptIds().length > 0 ? 'font-bold text-purple-600 dark:text-purple-400' : 'text-slate-600 dark:text-slate-400'" 
                                  x-text="getCurrentRackAllowedDeptIds().length > 0 ? ('Khusus: ' + getCurrentRackCustomDeptNames()) : 'Alokasi: Umum (Semua Departemen)'">
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Stats & Close Button -->
                <div class="flex items-center gap-2">
                    <!-- Status Legends -->
                    <div class="hidden sm:flex items-center gap-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-2.5 py-1 rounded text-xs font-medium text-slate-700 dark:text-slate-300 shadow-xs">
                        <div class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-none bg-emerald-500 border border-emerald-600"></span>
                            <span class="text-[10px]">Kosong (<strong class="font-mono text-emerald-600 dark:text-emerald-400" x-text="getRackSlotStats(selectedRackForModal).empty"></strong>)</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-none bg-yellow-500 border border-yellow-600"></span>
                            <span class="text-[10px]">Terisi (<strong class="font-mono text-yellow-600 dark:text-yellow-400" x-text="getRackSlotStats(selectedRackForModal).filled"></strong>)</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-none bg-rose-500 border border-rose-600"></span>
                            <span class="text-[10px]">Expired (<strong class="font-mono text-rose-600 dark:text-rose-400" x-text="getRackSlotStats(selectedRackForModal).expired"></strong>)</span>
                        </div>
                    </div>

                    <!-- Delphi Style Close Button -->
                    <button @click="rackGridModalOpen = false" type="button" class="w-7 h-7 flex items-center justify-center text-slate-500 hover:text-white bg-slate-200 dark:bg-slate-800 hover:bg-rose-600 dark:hover:bg-rose-600 rounded border border-slate-300 dark:border-slate-700 transition cursor-pointer" title="Tutup Jendela (Esc)">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

            <!-- Filter Toolbar (Delphi TToolBar in Square Style) -->
            <div class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs shrink-0">
                <!-- Status Filter Segmented Buttons (Square Desktop Style) -->
                <div class="flex items-center gap-0.5 bg-slate-200 dark:bg-slate-800 p-0.5 rounded border border-slate-300 dark:border-slate-700">
                    <button 
                        @click="slotFilterStatus = 'all'" 
                        type="button" 
                        class="px-2.5 py-1 rounded font-bold transition text-xs flex items-center gap-1 cursor-pointer" 
                        :class="slotFilterStatus === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700'"
                    >
                        <span>Semua</span>
                        <span class="text-[10px] font-mono opacity-80">(100)</span>
                    </button>
                    <button 
                        @click="slotFilterStatus = 'empty'" 
                        type="button" 
                        class="px-2 py-1 rounded font-bold transition text-xs flex items-center gap-1 cursor-pointer" 
                        :class="slotFilterStatus === 'empty' ? 'bg-emerald-600 text-white shadow-xs' : 'text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-950/50'"
                    >
                        <span class="w-2 h-2 rounded-none bg-emerald-500"></span>
                        <span>Kosong</span>
                        <span class="text-[10px] font-mono" x-text="'(' + getRackSlotStats(selectedRackForModal).empty + ')'"></span>
                    </button>
                    <button 
                        @click="slotFilterStatus = 'filled'" 
                        type="button" 
                        class="px-2 py-1 rounded font-bold transition text-xs flex items-center gap-1 cursor-pointer" 
                        :class="slotFilterStatus === 'filled' ? 'bg-amber-600 text-white shadow-xs' : 'text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-950/50'"
                    >
                        <span class="w-2 h-2 rounded-none bg-amber-500"></span>
                        <span>Terisi</span>
                        <span class="text-[10px] font-mono" x-text="'(' + getRackSlotStats(selectedRackForModal).filled + ')'"></span>
                    </button>
                    <button 
                        @click="slotFilterStatus = 'expired'" 
                        type="button" 
                        class="px-2 py-1 rounded font-bold transition text-xs flex items-center gap-1 cursor-pointer" 
                        :class="slotFilterStatus === 'expired' ? 'bg-rose-600 text-white shadow-xs' : 'text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-950/50'"
                    >
                        <span class="w-2 h-2 rounded-none bg-rose-500"></span>
                        <span>Expired</span>
                        <span class="text-[10px] font-mono" x-text="'(' + getRackSlotStats(selectedRackForModal).expired + ')'"></span>
                    </button>
                </div>

                <!-- Search Input Box -->
                <div class="relative w-full sm:w-72">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2"></i>
                    <input 
                        type="text" 
                        x-model="slotSearchQuery" 
                        placeholder="Cari No. Box / Judul / Slot..." 
                        class="w-full pl-8 pr-2.5 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white placeholder-slate-400 font-medium focus:outline-none focus:border-indigo-500 transition"
                    >
                </div>
            </div>

            <!-- Modal Content Layout: 5 Saps Grid (Left) + Detail Inspector (Right) -->
            <div class="flex-1 flex flex-col lg:flex-row overflow-hidden min-h-0">
                <!-- Left: 5 Saps Scrollable Area -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2.5 bg-slate-100/60 dark:bg-slate-900/60">
                    <template x-for="sapNum in [5, 4, 3, 2, 1]" :key="sapNum">
                        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded p-3 shadow-xs space-y-2 hover:border-slate-300 transition">
                            <!-- LVL Shelf Level Header -->
                            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-1.5">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-5 h-5 rounded bg-indigo-100 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400 flex items-center justify-center font-mono font-bold text-xs">
                                        <span x-text="sapNum"></span>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide font-mono" x-text="'LVL ' + sapNum"></h3>
                                </div>
                                <span class="text-[11px] font-mono text-slate-400" x-text="'20 Box (Nomor ' + ((sapNum-1)*20 + 1) + ' — ' + ((sapNum-1)*20 + 20) + ')'"></span>
                            </div>

                            <!-- Row 1: Baris Atas (Layer Top - 10 Slots) -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[10px] font-semibold text-slate-600 dark:text-slate-400">
                                    <span class="flex items-center gap-1 text-slate-700 dark:text-slate-300">
                                        <i data-lucide="arrow-up" class="w-3 h-3 text-indigo-600"></i>
                                        <span>Baris Atas (10 Box TB 30g)</span>
                                    </span>
                                    <span class="text-[10px] text-slate-500 font-mono" x-text="((sapNum-1)*20 + 11) + ' — ' + ((sapNum-1)*20 + 20)"></span>
                                </div>
                                <div class="grid grid-cols-5 sm:grid-cols-10 gap-1">
                                    <template x-for="slot in getSapSlots(sapNum, 'top')" :key="slot.slot_code || slot.id">
                                        <div 
                                            @click="selectSlotForDetail(slot)"
                                            class="p-1 rounded border transition cursor-pointer flex flex-col justify-between items-center text-center select-none min-h-[58px] relative"
                                            :class="[
                                                getSlotStyleClasses(slot),
                                                selectedSlotDetail?.slot_code === slot.slot_code ? 'ring-2 ring-indigo-500 scale-[1.03] shadow-md !border-indigo-500 !bg-indigo-50 dark:!bg-indigo-950/60' : '',
                                                (slotBlinkActive && highlightedSlotCode === slot.slot_code) ? '!ring-4 !ring-amber-500 !bg-amber-300 !text-slate-950 font-black scale-110 shadow-2xl z-30 animate-pulse border-amber-600 ring-offset-2' : '',
                                                !isSlotMatchFilter(slot) ? 'opacity-20 grayscale' : 'opacity-100'
                                            ]"
                                            :title="slot.archive ? ('Box #' + (slot.box_number_display || slot.slot_number) + ': ' + (slot.archive.box_number || 'Box') + ' - ' + slot.archive.title) : ('Box #' + (slot.box_number_display || slot.slot_number) + ': Slot Kosong')"
                                        >
                                            <div class="w-full flex items-center justify-between text-[9px] font-mono font-bold opacity-90 mb-0.5">
                                                <span 
                                                    class="font-black text-[11px]" 
                                                    :class="isSlotExpired(slot) ? '!text-white' : (isSlotNearExp(slot) ? 'text-rose-600 dark:text-rose-400' : 'text-amber-500 dark:text-amber-400')" 
                                                    x-text="'#' + (slot.box_number_display || slot.slot_number)"
                                                ></span>
                                                <template x-if="isSlotExpired(slot)">
                                                    <span class="w-1.5 h-1.5 rounded-none bg-white shadow-xs"></span>
                                                </template>
                                                <template x-if="isSlotNearExp(slot)">
                                                    <span class="w-1.5 h-1.5 rounded-none bg-rose-500 animate-pulse"></span>
                                                </template>
                                                <template x-if="isSlotFilled(slot)">
                                                    <span class="w-1.5 h-1.5 rounded-none bg-amber-500"></span>
                                                </template>
                                                <template x-if="!slot.archive && slot.status === 'empty'">
                                                    <span class="w-1.5 h-1.5 rounded-none bg-emerald-500"></span>
                                                </template>
                                            </div>

                                            <!-- Content / Box Title -->
                                            <div class="w-full flex-1 flex flex-col items-center justify-center">
                                                <template x-if="slot.archive">
                                                    <div class="space-y-0.5 w-full">
                                                        <span 
                                                            class="font-mono font-bold text-[9px] leading-tight block truncate max-w-[75px] mx-auto uppercase" 
                                                            :class="isSlotExpired(slot) ? '!text-white font-black' : (isSlotNearExp(slot) ? 'text-rose-700 dark:text-rose-300 font-bold' : ((slotBlinkActive && highlightedSlotCode === slot.slot_code) ? '!text-slate-950 font-black' : 'text-amber-800 dark:text-amber-400 font-bold'))" 
                                                            x-text="'TERISI'"
                                                        ></span>
                                                        <span 
                                                            class="text-[7px] font-mono block truncate max-w-[75px] mx-auto" 
                                                            :class="isSlotExpired(slot) ? '!text-white/95 font-semibold' : (isSlotNearExp(slot) ? 'text-rose-700/80 dark:text-rose-300/80 font-medium' : 'text-slate-500 dark:text-slate-400')" 
                                                            x-text="slot.archive.retention_duration_label || slot.archive.periode_doc || slot.archive.department || ''"
                                                        ></span>
                                                    </div>
                                                </template>
                                                <template x-if="!slot.archive">
                                                    <span class="text-[9px] font-bold font-mono" :class="(slotBlinkActive && highlightedSlotCode === slot.slot_code) ? 'text-slate-950 font-black' : 'text-emerald-600 dark:text-emerald-400'">Kosong</span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Shelf Separator Beam -->
                            <div class="border-t border-slate-200 dark:border-slate-800"></div>

                            <!-- Row 2: Baris Bawah (Layer Bottom - 10 Slots) -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[10px] font-semibold text-slate-600 dark:text-slate-400">
                                    <span class="flex items-center gap-1 text-slate-700 dark:text-slate-300">
                                        <i data-lucide="arrow-down" class="w-3 h-3 text-blue-600"></i>
                                        <span>Baris Bawah (10 Box TB 30g)</span>
                                    </span>
                                    <span class="text-[10px] text-slate-500 font-mono" x-text="((sapNum-1)*20 + 1) + ' — ' + ((sapNum-1)*20 + 10)"></span>
                                </div>
                                <div class="grid grid-cols-5 sm:grid-cols-10 gap-1">
                                    <template x-for="slot in getSapSlots(sapNum, 'bottom')" :key="slot.slot_code || slot.id">
                                        <div 
                                            @click="selectSlotForDetail(slot)"
                                            class="p-1 rounded border transition cursor-pointer flex flex-col justify-between items-center text-center select-none min-h-[58px] relative"
                                            :class="[
                                                getSlotStyleClasses(slot),
                                                selectedSlotDetail?.slot_code === slot.slot_code ? 'ring-2 ring-indigo-500 scale-[1.03] shadow-md !border-indigo-500 !bg-indigo-50 dark:!bg-indigo-950/60' : '',
                                                (slotBlinkActive && highlightedSlotCode === slot.slot_code) ? '!ring-4 !ring-amber-500 !bg-amber-300 !text-slate-950 font-black scale-110 shadow-2xl z-30 animate-pulse border-amber-600 ring-offset-2' : '',
                                                !isSlotMatchFilter(slot) ? 'opacity-20 grayscale' : 'opacity-100'
                                            ]"
                                            :title="slot.archive ? ('Box #' + (slot.box_number_display || slot.slot_number) + ': ' + (slot.archive.box_number || 'Box') + ' - ' + slot.archive.title) : ('Box #' + (slot.box_number_display || slot.slot_number) + ': Slot Kosong')"
                                        >
                                            <div class="w-full flex items-center justify-between text-[9px] font-mono font-bold opacity-90 mb-0.5">
                                                <span 
                                                    class="font-black text-[11px]" 
                                                    :class="isSlotExpired(slot) ? '!text-white' : (isSlotNearExp(slot) ? 'text-rose-600 dark:text-rose-400' : 'text-amber-500 dark:text-amber-400')" 
                                                    x-text="'#' + (slot.box_number_display || slot.slot_number)"
                                                ></span>
                                                <template x-if="isSlotExpired(slot)">
                                                    <span class="w-1.5 h-1.5 rounded-none bg-white shadow-xs"></span>
                                                </template>
                                                <template x-if="isSlotNearExp(slot)">
                                                    <span class="w-1.5 h-1.5 rounded-none bg-rose-500 animate-pulse"></span>
                                                </template>
                                                <template x-if="isSlotFilled(slot)">
                                                    <span class="w-1.5 h-1.5 rounded-none bg-amber-500"></span>
                                                </template>
                                                <template x-if="!slot.archive && slot.status === 'empty'">
                                                    <span class="w-1.5 h-1.5 rounded-none bg-emerald-500"></span>
                                                </template>
                                            </div>

                                            <!-- Content / Box Title -->
                                            <div class="w-full flex-1 flex flex-col items-center justify-center">
                                                <template x-if="slot.archive">
                                                    <div class="space-y-0.5 w-full">
                                                        <span 
                                                            class="font-mono font-bold text-[9px] leading-tight block truncate max-w-[75px] mx-auto uppercase" 
                                                            :class="isSlotExpired(slot) ? '!text-white font-black' : (isSlotNearExp(slot) ? 'text-rose-700 dark:text-rose-300 font-bold' : ((slotBlinkActive && highlightedSlotCode === slot.slot_code) ? '!text-slate-950 font-black' : 'text-amber-800 dark:text-amber-400 font-bold'))" 
                                                            x-text="'TERISI'"
                                                        ></span>
                                                        <span 
                                                            class="text-[7px] font-mono block truncate max-w-[75px] mx-auto" 
                                                            :class="isSlotExpired(slot) ? '!text-white/95 font-semibold' : (isSlotNearExp(slot) ? 'text-rose-700/80 dark:text-rose-300/80 font-medium' : 'text-slate-500 dark:text-slate-400')" 
                                                            x-text="slot.archive.retention_duration_label || slot.archive.periode_doc || slot.archive.department || ''"
                                                        ></span>
                                                    </div>
                                                </template>
                                                <template x-if="!slot.archive">
                                                    <span class="text-[9px] font-bold font-mono" :class="(slotBlinkActive && highlightedSlotCode === slot.slot_code) ? 'text-slate-950 font-black' : 'text-emerald-600 dark:text-emerald-400'">Kosong</span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Right: Slot Detail Inspector (Delphi Property Inspector in Desktop Square Theme) -->
                <div class="w-full lg:w-96 bg-white dark:bg-slate-950 border-t lg:border-t-0 lg:border-l border-slate-200 dark:border-slate-800 p-3.5 overflow-y-auto flex flex-col justify-between space-y-3 shadow-xl">
                    <!-- If Slot NOT Selected -->
                    <div x-show="!selectedSlotDetail" class="py-8 text-center space-y-2 my-auto">
                        <div class="w-10 h-10 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center border border-indigo-200 dark:border-indigo-800">
                            <i data-lucide="mouse-pointer-click" class="w-5 h-5"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider font-mono">Inspector Slot Rak</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 max-w-xs mx-auto leading-relaxed font-sans">
                            Klik salah satu dari 100 slot box (TB 30g) pada denah di sebelah kiri untuk melihat rincian dokumen dan opsi cetak label.
                        </p>
                    </div>

                    <!-- If Slot IS Selected -->
                    <div x-show="selectedSlotDetail" class="space-y-3" x-cloak>
                        <!-- Inspector Header -->
                        <div class="border-b border-slate-200 dark:border-slate-800 pb-2.5 flex items-start justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-indigo-400 tracking-wider block font-mono" x-text="'Sap ' + (selectedSlotDetail?.sap_level || '') + ' • ' + (selectedSlotDetail?.layer_label || '')"></span>
                                <h3 class="text-base font-bold text-white font-mono" x-text="'SLOT BOX #' + (selectedSlotDetail?.box_number_display || selectedSlotDetail?.slot_number || getBoxIndex(selectedSlotDetail?.sap_level, selectedSlotDetail?.layer, selectedSlotDetail?.slot_number))"></h3>
                            </div>
                            <span 
                                class="px-1.5 py-0.5 text-[10px] font-bold uppercase rounded tracking-wider"
                                :class="isSlotExpired(selectedSlotDetail) ? 'bg-rose-600 text-white border border-rose-700 shadow-xs font-black' : (isSlotNearExp(selectedSlotDetail) ? 'bg-rose-100 text-rose-800 border border-rose-300' : (selectedSlotDetail?.archive ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'))"
                                x-text="isSlotExpired(selectedSlotDetail) ? 'EXPIRED' : (isSlotNearExp(selectedSlotDetail) ? 'MENDEKATI EXP' : (selectedSlotDetail?.archive ? 'TERISI' : 'KOSONG'))"
                            ></span>
                        </div>

                        <!-- Delphi Property Table for Occupied Archive -->
                        <template x-if="selectedSlotDetail?.archive">
                            <div class="space-y-2.5 text-xs">
                                <!-- Property Sheet Table -->
                                <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded divide-y divide-slate-200 dark:divide-slate-800 overflow-hidden text-xs">
                                    <div class="p-2 flex items-center justify-between">
                                        <span class="text-slate-500 text-[10px] font-mono uppercase font-bold">No. Box:</span>
                                        <span class="font-mono text-xs font-bold text-amber-700 dark:text-amber-400" x-text="selectedSlotDetail.archive.box_number || '-'"></span>
                                    </div>
                                    <div class="p-2 space-y-0.5">
                                        <span class="text-slate-500 text-[10px] font-mono uppercase font-bold block">Judul Dokumen:</span>
                                        <h4 class="font-semibold text-slate-900 dark:text-white text-xs leading-snug font-sans" x-text="selectedSlotDetail.archive.title"></h4>
                                    </div>
                                    <div class="p-2 flex items-center justify-between">
                                        <span class="text-slate-500 text-[10px] font-mono uppercase font-bold">Periode:</span>
                                        <span class="font-mono text-xs text-slate-800 dark:text-slate-200" x-text="selectedSlotDetail.archive.periode_doc || '-'"></span>
                                    </div>
                                    <div class="p-2 flex items-center justify-between">
                                        <span class="text-slate-500 text-[10px] font-mono uppercase font-bold">Departemen:</span>
                                        <span class="text-xs text-slate-800 dark:text-slate-200 font-medium" x-text="selectedSlotDetail.archive.department_name ? (selectedSlotDetail.archive.department + ' - ' + selectedSlotDetail.archive.department_name) : (selectedSlotDetail.archive.department || '-')"></span>
                                    </div>
                                    <template x-if="selectedSlotDetail.archive.sub_department || selectedSlotDetail.archive.sub_department_name">
                                        <div class="p-2 flex items-center justify-between">
                                            <span class="text-slate-500 text-[10px] font-mono uppercase font-bold">Sub-Dept:</span>
                                            <span class="text-xs text-indigo-700 dark:text-indigo-400 font-medium" x-text="selectedSlotDetail.archive.sub_department_name ? (selectedSlotDetail.archive.sub_department + ' - ' + selectedSlotDetail.archive.sub_department_name) : selectedSlotDetail.archive.sub_department"></span>
                                        </div>
                                    </template>
                                    <div class="p-2 flex items-center justify-between">
                                        <span class="text-slate-500 text-[10px] font-mono uppercase font-bold">Masa Simpan:</span>
                                        <span class="font-mono text-xs font-semibold" :class="(selectedSlotDetail.status === 'expired' || selectedSlotDetail.archive?.is_expired) ? 'text-rose-600 font-bold' : 'text-slate-800 dark:text-slate-200'" x-text="selectedSlotDetail.archive.formatted_expiry_date || formatDate(selectedSlotDetail.archive.retention_expiry_date) || '-'"></span>
                                    </div>
                                </div>

                                <!-- Expired Warning Alert -->
                                <template x-if="selectedSlotDetail.status === 'expired' || selectedSlotDetail.archive?.is_expired">
                                    <div class="p-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded space-y-0.5">
                                        <div class="flex items-center gap-1 text-rose-700 dark:text-rose-400 font-bold text-xs">
                                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-600"></i>
                                            <span>Masa Simpan Kedaluwarsa</span>
                                        </div>
                                        <p class="text-[10px] text-rose-800 dark:text-rose-300 font-sans">
                                            Arsip pada box ini telah melewati masa retensi dan dapat diproses untuk pemusnahan.
                                        </p>
                                    </div>
                                </template>

                                <!-- Action Buttons (Desktop Push Buttons) -->
                                <div class="pt-1 space-y-1.5">
                                    <a 
                                        :href="'/archives/' + selectedSlotDetail.archive.id" 
                                        target="_blank"
                                        class="w-full py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded border border-indigo-700 shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                                    >
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        <span>Buka Detail Halaman Arsip</span>
                                    </a>

                                    <button 
                                        type="button"
                                        @click="window.openLabelModal(selectedSlotDetail.archive.id)" 
                                        :disabled="!selectedSlotDetail.archive || !['approved_booked', 'in_warehouse', 'pending_extension'].includes(selectedSlotDetail.archive.status)"
                                        :class="!selectedSlotDetail.archive || !['approved_booked', 'in_warehouse', 'pending_extension'].includes(selectedSlotDetail.archive.status) ? 'opacity-40 cursor-not-allowed bg-slate-100 dark:bg-slate-900 text-slate-400 dark:text-slate-600 border-slate-200 dark:border-slate-800' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 border-slate-300 dark:border-slate-700 cursor-pointer'"
                                        class="w-full py-1.5 font-bold text-xs rounded transition flex items-center justify-center gap-1.5 border shadow-2xs"
                                        :title="!selectedSlotDetail.archive || !['approved_booked', 'in_warehouse', 'pending_extension'].includes(selectedSlotDetail.archive.status) ? 'Label dinonaktifkan: Hanya untuk arsip yang disetujui (Approved / Booked), di gudang, atau perpanjangan' : 'Cetak Label Box Form A5 (TB 30g)'"
                                    >
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        <span>Cetak Label Box Form A5 (TB 30g)</span>
                                    </button>

                                    <button 
                                        type="button" 
                                        @click="unassignCurrentSlot()"
                                        :disabled="slotAssignLoading"
                                        class="w-full py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/30 dark:hover:bg-rose-950/50 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800 font-bold text-xs rounded transition flex items-center justify-center gap-1.5 cursor-pointer"
                                    >
                                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                                        <span x-text="slotAssignLoading ? 'Memproses...' : 'Kosongkan / Lepas Box Dari Slot Ini'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- If Empty Slot: Form Pengisian Dokumen / Alokasi Box -->
                        <template x-if="!selectedSlotDetail?.archive">
                            <div class="space-y-2.5 text-xs">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold">
                                        <i data-lucide="inbox" class="w-3.5 h-3.5"></i>
                                        <span>Isi Dokumen ke Slot Ini</span>
                                    </div>
                                    <span class="text-[10px] font-mono text-emerald-800 dark:text-emerald-300 font-bold">Slot Kosong</span>
                                </div>

                                <!-- Notice Departemen yang Diizinkan (Fitur Baru) -->
                                <template x-if="getCurrentRackAllowedDeptIds().length > 0">
                                    <div class="p-2 bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 rounded flex items-center gap-1.5 text-[11px] text-indigo-700 dark:text-indigo-300 font-medium font-sans">
                                        <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/></svg>
                                        <span>Penempatan Dikhususkan untuk: <strong class="font-bold font-mono" x-text="getCurrentRackCustomDeptNames()"></strong></span>
                                    </div>
                                </template>

                                <!-- Tab Switcher: Input Baru vs Dari Antrean -->
                                <div class="flex items-center p-0.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded text-xs font-mono">
                                    <button 
                                        type="button" 
                                        @click="slotAssignMode = 'create_new'"
                                        class="flex-1 py-1 rounded font-bold text-[11px] transition text-center cursor-pointer"
                                        :class="slotAssignMode === 'create_new' ? 'bg-white dark:bg-slate-800 text-indigo-700 dark:text-indigo-400 shadow-xs border border-slate-200 dark:border-slate-700' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                                    >
                                        Input Dokumen Baru
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="slotAssignMode = 'existing_archive'"
                                        class="flex-1 py-1 rounded font-bold text-[11px] transition text-center cursor-pointer"
                                        :class="slotAssignMode === 'existing_archive' ? 'bg-white dark:bg-slate-800 text-indigo-700 dark:text-indigo-400 shadow-xs border border-slate-200 dark:border-slate-700' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                                    >
                                        Pilih Antrean Arsip
                                    </button>
                                </div>

                                <!-- Mode 1: Form Input Dokumen Baru -->
                                <div x-show="slotAssignMode === 'create_new'" class="space-y-2">
                                    <!-- Departemen Dropdown -->
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-mono font-bold uppercase text-slate-700 dark:text-slate-300 block">Departemen: <span class="text-rose-500">*</span></label>
                                        <select 
                                            x-model="slotAssignForm.department_id" 
                                            @change="onDepartmentChange()"
                                            class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-medium"
                                        >
                                            <option value="">-- Pilih Departemen --</option>
                                            <template x-for="dept in departments" :key="dept.id">
                                                <option :value="dept.id" x-text="dept.code + ' - ' + dept.name" :disabled="getCurrentRackAllowedDeptIds().length > 0 && !getCurrentRackAllowedDeptIds().includes(Number(dept.id))"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Sub Departemen Dropdown -->
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-mono font-bold uppercase text-slate-700 dark:text-slate-300 block">Sub-Departemen: <span class="text-slate-500 font-normal font-sans">(Opsional)</span></label>
                                        <select 
                                            x-model="slotAssignForm.sub_department_id" 
                                            @change="onSubDepartmentChange()"
                                            :disabled="!slotAssignForm.department_id || getAvailableSubDepartments().length === 0"
                                            class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-medium disabled:opacity-50"
                                        >
                                            <option value="" x-text="getAvailableSubDepartments().length === 0 ? '-- Tidak Ada Sub-Dept --' : '-- Pilih Sub-Dept (Opsional) --'"></option>
                                            <template x-for="sub in getAvailableSubDepartments()" :key="sub.id">
                                                <option :value="sub.id" x-text="sub.code + ' - ' + sub.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Judul Dokumen -->
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-mono font-bold uppercase text-slate-700 dark:text-slate-300 block">Judul / Uraian Dokumen: <span class="text-rose-500">*</span></label>
                                        <input 
                                            type="text" 
                                            x-model="slotAssignForm.title"
                                            placeholder="Contoh: Faktur Pajak Masukan & Keluaran"
                                            class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-sans"
                                        >
                                    </div>

                                    <!-- Grid 2 Kolom: No. Box & Periode -->
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <div class="space-y-0.5">
                                            <label class="text-[10px] font-mono font-bold uppercase text-slate-700 dark:text-slate-300 block">No. Box:</label>
                                            <input 
                                                type="text" 
                                                x-model="slotAssignForm.box_number"
                                                placeholder="Otomatis..."
                                                class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs text-amber-700 dark:text-amber-400 placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-mono font-bold"
                                            >
                                        </div>
                                        <div class="space-y-0.5">
                                            <label class="text-[10px] font-mono font-bold uppercase text-slate-700 dark:text-slate-300 block">Periode (YYYY/MM):</label>
                                            <input 
                                                type="text" 
                                                x-model="slotAssignForm.periode_doc"
                                                placeholder="2024/01"
                                                class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-mono"
                                            >
                                        </div>
                                    </div>

                                    <!-- Grid 2 Kolom: Tipe Dokumen & Masa Retensi -->
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <div class="space-y-0.5">
                                            <label class="text-[10px] font-mono font-bold uppercase text-slate-700 dark:text-slate-300 block">Tipe Dokumen:</label>
                                            <select 
                                                x-model="slotAssignForm.document_type"
                                                class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-medium"
                                            >
                                                <option value="UMUM">UMUM</option>
                                                <option value="KEUANGAN">KEUANGAN</option>
                                                <option value="PAJAK">PAJAK</option>
                                                <option value="LEGAL">LEGAL</option>
                                                <option value="SDM">SDM</option>
                                            </select>
                                        </div>
                                        <div class="space-y-0.5">
                                            <label class="text-[10px] font-mono font-bold uppercase text-slate-700 dark:text-slate-300 block">Retensi (Thn):</label>
                                            <input 
                                                type="number" 
                                                x-model="slotAssignForm.retention_years"
                                                min="1" max="50"
                                                class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                                            >
                                        </div>
                                    </div>

                                    <!-- Submit Button (Square Desktop) -->
                                    <div class="pt-1">
                                        <button 
                                            type="button" 
                                            @click="submitAssignSlot()"
                                            :disabled="slotAssignLoading"
                                            class="w-full py-1.5 font-bold text-xs rounded border shadow-xs transition flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 text-white border-emerald-700 cursor-pointer disabled:opacity-50"
                                        >
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span x-text="slotAssignLoading ? 'Menyimpan...' : 'Simpan & Tempatkan di Slot'"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Mode 2: Form Pilih Dari Antrean Arsip -->
                                <div x-show="slotAssignMode === 'existing_archive'" class="space-y-2">
                                    <template x-if="getCurrentRackCustomDept()">
                                        <div class="p-1.5 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/70 rounded flex items-center gap-1.5 text-[10px] text-indigo-700 dark:text-indigo-300 font-bold">
                                            <svg class="w-3 h-3 text-indigo-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/></svg>
                                            <span>Khusus Departemen <strong x-text="getCurrentRackCustomDept().code"></strong> (Hanya antrean departemen ini yang dapat dipilih).</span>
                                        </div>
                                    </template>

                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-mono font-bold uppercase text-slate-700 dark:text-slate-300 block">Pilih Dokumen Arsip Terdaftar:</label>
                                        <select 
                                            x-model="slotAssignForm.archive_id"
                                            @change="onQueueArchiveSelected($event.target.value)"
                                            class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-medium"
                                        >
                                            <option value="">-- Pilih Dari Antrean Arsip --</option>
                                            <template x-for="arc in getFilteredUnassignedArchives()" :key="arc.id">
                                                <option :value="arc.id" x-text="(arc.is_approved ? '✅ [Disetujui] ' : '⏳ [Menunggu ACC] ') + '[' + (arc.department_code || 'Dept') + '] ' + (arc.box_number ? arc.box_number + ' - ' : '') + arc.title"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Card Ringkasan & Status Dokumen Terpilih -->
                                    <template x-if="getSelectedQueueArchive()">
                                        <div class="p-2 rounded border text-xs space-y-1.5 transition-all"
                                             :class="!getSelectedQueueArchive().is_approved 
                                                ? 'bg-amber-50 dark:bg-amber-950/30 border-amber-300 dark:border-amber-700/60 text-amber-900 dark:text-amber-200' 
                                                : 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-700/60 text-emerald-900 dark:text-emerald-200'">
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="font-bold flex items-center gap-1">
                                                    <template x-if="!getSelectedQueueArchive().is_approved">
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100">
                                                            ⏳ Antrean Verifikasi (Belum ACC)
                                                        </span>
                                                    </template>
                                                    <template x-if="getSelectedQueueArchive().is_approved">
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-200 dark:bg-emerald-800 text-emerald-900 dark:text-emerald-100">
                                                            ✅ Sudah Disetujui (Siap Alokasi)
                                                        </span>
                                                    </template>
                                                </span>
                                                <span class="font-mono text-[10px] font-semibold opacity-80" x-text="getSelectedQueueArchive().department_code"></span>
                                            </div>
                                            <div class="text-[11px] leading-tight">
                                                <div class="font-bold truncate" x-text="getSelectedQueueArchive().title"></div>
                                                <div class="text-[10px] opacity-80 mt-0.5">
                                                    Pengaju: <strong x-text="getSelectedQueueArchive().creator || '-'"></strong> | Periode: <span x-text="getSelectedQueueArchive().periode || getSelectedQueueArchive().periode_doc || '-'"></span>
                                                </div>
                                                <template x-if="getSelectedQueueArchive().box_number">
                                                    <div class="text-[10px] font-mono text-emerald-700 dark:text-emerald-300 font-bold mt-0.5">
                                                        Nomor Box: <span x-text="getSelectedQueueArchive().box_number"></span>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- Tombol Buka Verifikasi Jika Belum di-ACC -->
                                            <template x-if="!getSelectedQueueArchive().is_approved">
                                                <div class="pt-1 border-t border-amber-200 dark:border-amber-800/60 flex items-center justify-between gap-2">
                                                    <span class="text-[10px] text-amber-700 dark:text-amber-300 italic">Perlu verifikasi pengajuan:</span>
                                                    <button type="button" 
                                                            @click="openVerifyModal(getSelectedQueueArchive())" 
                                                            class="px-2 py-0.5 bg-amber-600 hover:bg-amber-500 text-white rounded text-[10px] font-bold flex items-center gap-1 shadow-xs cursor-pointer">
                                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                                                        <span>Buka Verifikasi</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="getFilteredUnassignedArchives().length === 0">
                                        <p class="text-[10px] text-slate-500 italic pt-0.5 font-sans">Tidak ada antrean arsip yang belum memiliki rak. Gunakan tab 'Input Dokumen Baru'.</p>
                                    </template>
                                </div>

                                <!-- Submit Button Existing Archive -->
                                <div class="pt-1">
                                    <button 
                                        type="button" 
                                        @click="submitAssignSlot()"
                                        :disabled="slotAssignLoading || !slotAssignForm.archive_id"
                                        class="w-full py-1.5 font-bold text-xs rounded border shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                                        :class="getSelectedQueueArchive() && !getSelectedQueueArchive().is_approved 
                                            ? 'bg-amber-600 hover:bg-amber-500 text-white border-amber-700' 
                                            : 'bg-indigo-600 hover:bg-indigo-500 text-white border-indigo-700'"
                                    >
                                        <template x-if="slotAssignLoading">
                                            <span>Memproses...</span>
                                        </template>
                                        <template x-if="!slotAssignLoading">
                                            <span class="flex items-center gap-1.5">
                                                <template x-if="getSelectedQueueArchive() && !getSelectedQueueArchive().is_approved">
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                                                        <span>Verifikasi & Setujui (Approve)</span>
                                                    </span>
                                                </template>
                                                <template x-if="!getSelectedQueueArchive() || getSelectedQueueArchive().is_approved">
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 8 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/><polyline points="9 13 12 16 16 11"/></svg>
                                                        <span>Alokasikan Box ke Slot</span>
                                                    </span>
                                                </template>
                                            </span>
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                    </div>

                    <!-- Footer Close Button -->
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button 
                            @click="rackGridModalOpen = false" 
                            type="button" 
                            class="w-full py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold text-xs rounded transition text-center border border-slate-300 dark:border-slate-700 cursor-pointer"
                        >
                            Tutup Modal Denah Rak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>