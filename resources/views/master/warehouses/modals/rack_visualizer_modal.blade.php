    <!-- MODAL: 100-BOX RACK VISUALIZER (5 SAP x 20 BOX: 10 ATAS + 10 BAWAH TB 30g) -->
    <div x-show="rackGridModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/60 dark:bg-black/80 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4 md:p-6 overflow-hidden">
        <div @click.away="rackGridModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/90 text-slate-800 dark:text-slate-100 rounded-xl max-w-6xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden">
            <!-- Modal Header (Delphi Window Titlebar) -->
            <div class="px-5 py-3.5 bg-slate-100 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-100 dark:bg-indigo-600/30 border border-indigo-200 dark:border-indigo-500/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-xs">
                        <i data-lucide="layout-grid" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white tracking-wide flex items-center gap-2 font-mono">
                                <span x-text="'Denah Rak: ' + (selectedRackForModal?.rack_code || 'RAK')"></span>
                            </h2>
                            <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-500/15 border border-indigo-200 dark:border-indigo-500/30 text-indigo-700 dark:text-indigo-300 font-mono text-[11px] font-semibold rounded">
                                100 Box (TB 30g)
                            </span>
                            <template x-if="selectedRackForModal?.assigned_department">
                                <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-500/20 border border-purple-200 dark:border-purple-500/40 text-purple-700 dark:text-purple-300 text-[10px] font-bold rounded flex items-center gap-1 font-mono">
                                    <i data-lucide="building" class="w-3 h-3 text-purple-500"></i>
                                    <span x-text="'KHUSUS ' + selectedRackForModal?.assigned_department?.code"></span>
                                </span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <span x-text="'Sektor: ' + (selectedRackForModal?.room_sector || 'Umum')"></span>
                            <span class="text-slate-300 dark:text-slate-600 mx-1.5">•</span>
                            <span x-text="'Alokasi Dept: ' + (selectedRackForModal?.assigned_department ? (selectedRackForModal?.assigned_department.code + ' - ' + selectedRackForModal?.assigned_department.name) : 'Umum (Bebas)')"></span>
                        </p>
                    </div>
                </div>

                <!-- Stats & Close Button -->
                <div class="flex items-center gap-3">
                    <!-- Status Legends -->
                    <div class="hidden sm:flex items-center gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-300 shadow-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                            <span class="text-[11px]">Kosong (<strong class="font-mono text-emerald-600 dark:text-emerald-400" x-text="getRackSlotStats(selectedRackForModal).empty"></strong>)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                            <span class="text-[11px]">Terisi (<strong class="font-mono text-amber-600 dark:text-amber-300" x-text="getRackSlotStats(selectedRackForModal).filled"></strong>)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span class="text-[11px]">Expired (<strong class="font-mono text-rose-600 dark:text-rose-300" x-text="getRackSlotStats(selectedRackForModal).expired"></strong>)</span>
                        </div>
                        <template x-if="getRackSlotStats(selectedRackForModal).inactive > 0">
                            <div class="flex items-center gap-1.5 border-l border-slate-200 dark:border-slate-800 pl-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-400 dark:bg-slate-600"></span>
                                <span class="text-[11px]">Non-Aktif (<strong class="font-mono text-slate-500 dark:text-slate-400" x-text="getRackSlotStats(selectedRackForModal).inactive"></strong>)</span>
                            </div>
                        </template>
                    </div>

                    <!-- Close Button -->
                    <button @click="rackGridModalOpen = false" type="button" class="w-8 h-8 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-white bg-slate-200 dark:bg-slate-800/80 hover:bg-rose-600 dark:hover:bg-rose-600/80 rounded-lg transition cursor-pointer" title="Tutup Jendela (Esc)">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="px-5 py-2.5 bg-slate-50/80 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs shrink-0">
                <!-- Status Filter Buttons -->
                <div class="flex items-center gap-1 bg-slate-200/80 dark:bg-slate-950 p-1 rounded-lg border border-slate-300 dark:border-slate-800">
                    <button 
                        @click="slotFilterStatus = 'all'" 
                        type="button" 
                        class="px-3 py-1.5 rounded font-semibold transition text-xs flex items-center gap-1.5 cursor-pointer" 
                        :class="slotFilterStatus === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-300/60 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'"
                    >
                        <span>Semua Slot</span>
                        <span class="text-[10px] font-mono opacity-80">(100)</span>
                    </button>
                    <button 
                        @click="slotFilterStatus = 'empty'" 
                        type="button" 
                        class="px-2.5 py-1.5 rounded font-semibold transition text-xs flex items-center gap-1.5 cursor-pointer" 
                        :class="slotFilterStatus === 'empty' ? 'bg-emerald-600 text-white shadow-xs' : 'text-emerald-700 hover:text-emerald-900 hover:bg-emerald-100/60 dark:text-emerald-400 dark:hover:text-emerald-300 dark:hover:bg-emerald-950/30'"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                        <span>Kosong</span>
                        <span class="text-[10px] font-mono" x-text="'(' + getRackSlotStats(selectedRackForModal).empty + ')'"></span>
                    </button>
                    <button 
                        @click="slotFilterStatus = 'filled'" 
                        type="button" 
                        class="px-2.5 py-1.5 rounded font-semibold transition text-xs flex items-center gap-1.5 cursor-pointer" 
                        :class="slotFilterStatus === 'filled' ? 'bg-amber-600 text-white shadow-xs' : 'text-amber-700 hover:text-amber-900 hover:bg-amber-100/60 dark:text-amber-400 dark:hover:text-amber-300 dark:hover:bg-amber-950/30'"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                        <span>Terisi</span>
                        <span class="text-[10px] font-mono" x-text="'(' + getRackSlotStats(selectedRackForModal).filled + ')'"></span>
                    </button>
                    <button 
                        @click="slotFilterStatus = 'expired'" 
                        type="button" 
                        class="px-2.5 py-1.5 rounded font-semibold transition text-xs flex items-center gap-1.5 cursor-pointer" 
                        :class="slotFilterStatus === 'expired' ? 'bg-rose-600 text-white shadow-xs' : 'text-rose-700 hover:text-rose-900 hover:bg-rose-100/60 dark:text-rose-400 dark:hover:text-rose-300 dark:hover:bg-rose-950/30'"
                    >
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>Expired</span>
                        <span class="text-[10px] font-mono" x-text="'(' + getRackSlotStats(selectedRackForModal).expired + ')'"></span>
                    </button>
                    <template x-if="getRackSlotStats(selectedRackForModal).inactive > 0">
                        <button 
                            @click="slotFilterStatus = 'inactive'" 
                            type="button" 
                            class="px-2.5 py-1.5 rounded font-semibold transition text-xs flex items-center gap-1.5 cursor-pointer" 
                            :class="slotFilterStatus === 'inactive' ? 'bg-slate-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'"
                        >
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            <span>Non-Aktif</span>
                            <span class="text-[10px] font-mono" x-text="'(' + getRackSlotStats(selectedRackForModal).inactive + ')'"></span>
                        </button>
                    </template>
                </div>

                <!-- Search Input Box -->
                <div class="relative w-full sm:w-80">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                    <input 
                        type="text" 
                        x-model="slotSearchQuery" 
                        placeholder="Cari No. Box / Judul / Periode / Slot..." 
                        class="w-full pl-9 pr-3 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-lg text-xs text-slate-900 dark:text-white placeholder-slate-400 font-medium focus:outline-none focus:border-indigo-500 transition"
                    >
                </div>
            </div>

            <!-- Modal Content Layout: 5 Saps Grid (Left) + Detail Inspector (Right) -->
            <div class="flex-1 flex flex-col lg:flex-row overflow-hidden min-h-0">
                <!-- Left: 5 Saps Scrollable Area -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-slate-100/60 dark:bg-slate-950/40">
                    <!-- Loading Rack Slots State -->
                    <div x-show="slotDataLoading" class="p-12 text-center text-slate-500 space-y-2">
                        <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-indigo-500 mx-auto"></i>
                        <p class="font-bold text-xs">Memuat Data Slot Rak...</p>
                    </div>

                    <template x-if="!slotDataLoading">
                        <div class="space-y-3.5">
                            <template x-for="sapNum in [5, 4, 3, 2, 1]" :key="sapNum">
                                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3.5 shadow-xs space-y-3 hover:border-slate-300 dark:hover:border-slate-700 transition">
                                    <!-- Sap Shelf Level Header -->
                                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded bg-indigo-100 dark:bg-indigo-600/30 border border-indigo-200 dark:border-indigo-500/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-mono font-bold text-xs">
                                                <span x-text="sapNum"></span>
                                            </div>
                                            <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide font-mono" x-text="'LVL ' + sapNum"></h3>
                                        </div>
                                        <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">20 Box (10 Atas + 10 Bawah)</span>
                                    </div>

                                     <!-- Row 1: Baris Atas (Layer Top - 10 Slots) -->
                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                                                <i data-lucide="arrow-up" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                                <span>Baris Atas (10 Box TB 30g)</span>
                                            </span>
                                            <span class="text-[11px] text-slate-600 dark:text-slate-300 font-mono font-bold bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700" x-text="((sapNum - 1) * 20 + 11) + ' — ' + ((sapNum - 1) * 20 + 20)"></span>
                                        </div>
                                        <div class="grid grid-cols-5 sm:grid-cols-10 gap-1.5">
                                            <template x-for="slot in getSapSlots(sapNum, 'top')" :key="slot.slot_code || slot.id">
                                                <div 
                                                    @click="selectSlotForDetail(slot)"
                                                    class="p-1.5 rounded-lg border transition cursor-pointer flex flex-col justify-between items-center text-center select-none min-h-[62px]"
                                                    :class="[
                                                        getSlotStyleClasses(slot),
                                                        selectedSlotDetail?.slot_code === slot.slot_code ? 'ring-2 ring-indigo-500 scale-[1.03] shadow-md !border-indigo-500 !bg-indigo-50 dark:!bg-indigo-950/40' : '',
                                                        !isSlotMatchFilter(slot) ? 'opacity-20 grayscale' : 'opacity-100'
                                                    ]"
                                                    :title="slot.archive ? (slot.slot_code + ' [No. ' + (slot.box_number_display || slot.slot_number) + ']: ' + (slot.archive.box_number || 'Box') + ' - ' + slot.archive.title) : (slot.is_active === false ? (slot.slot_code + ': Non-Aktif') : (slot.slot_code + ' [No. ' + (slot.box_number_display || slot.slot_number) + ']: Slot Kosong'))"
                                                >
                                                    <div class="w-full flex items-center justify-between text-[9px] font-mono font-bold opacity-90 mb-0.5">
                                                        <span class="text-slate-700 dark:text-slate-300 font-bold" x-text="slot.box_number_display || slot.slot_number"></span>
                                                        <template x-if="slot.is_active === false || slot.status === 'inactive'">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-600" title="Non-Aktif"></span>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && (slot.status === 'expired' || slot.archive?.is_expired)">
                                                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && (slot.status === 'filled' || slot.archive) && !(slot.status === 'expired' || slot.archive?.is_expired)">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && !slot.archive && slot.status === 'empty'">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400/80"></span>
                                                        </template>
                                                    </div>

                                                    <!-- Content / Box Title -->
                                                    <div class="w-full flex-1 flex flex-col items-center justify-center">
                                                        <template x-if="slot.is_active === false || slot.status === 'inactive'">
                                                            <span class="text-[9px] font-semibold text-slate-400 dark:text-slate-500 font-mono italic">Non-Aktif</span>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && slot.archive">
                                                            <div class="space-y-0.5 w-full">
                                                                <span class="font-mono font-bold text-[10px] leading-tight block truncate max-w-[80px] mx-auto" :class="(slot.status === 'expired' || slot.archive?.is_expired) ? '!text-white font-black' : ((slot.status === 'near_exp' || slot.is_near_exp || slot.archive?.is_near_exp) ? 'text-rose-700 dark:text-rose-300' : 'text-amber-700 dark:text-amber-300')" x-text="slot.archive.box_number || 'TERISI'"></span>
                                                                <span class="text-[8px] text-slate-500 dark:text-slate-400 font-mono block truncate max-w-[80px] mx-auto" x-text="slot.archive.periode_doc || slot.archive.department || ''"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && !slot.archive">
                                                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 font-mono">Kosong</span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Shelf Separator Beam -->
                                    <div class="border-t border-slate-200 dark:border-slate-800/80"></div>

                                    <!-- Row 2: Baris Bawah (Layer Bottom - 10 Slots) -->
                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                                                <i data-lucide="arrow-down" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"></i>
                                                <span>Baris Bawah (10 Box TB 30g)</span>
                                            </span>
                                            <span class="text-[11px] text-slate-600 dark:text-slate-300 font-mono font-bold bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700" x-text="((sapNum - 1) * 20 + 1) + ' — ' + ((sapNum - 1) * 20 + 10)"></span>
                                        </div>
                                        <div class="grid grid-cols-5 sm:grid-cols-10 gap-1.5">
                                            <template x-for="slot in getSapSlots(sapNum, 'bottom')" :key="slot.slot_code || slot.id">
                                                <div 
                                                    @click="selectSlotForDetail(slot)"
                                                    class="p-1.5 rounded-lg border transition cursor-pointer flex flex-col justify-between items-center text-center select-none min-h-[62px]"
                                                    :class="[
                                                        getSlotStyleClasses(slot),
                                                        selectedSlotDetail?.slot_code === slot.slot_code ? 'ring-2 ring-indigo-500 scale-[1.03] shadow-md !border-indigo-500 !bg-indigo-50 dark:!bg-indigo-950/40' : '',
                                                        !isSlotMatchFilter(slot) ? 'opacity-20 grayscale' : 'opacity-100'
                                                    ]"
                                                    :title="slot.archive ? (slot.slot_code + ' [No. ' + (slot.box_number_display || slot.slot_number) + ']: ' + (slot.archive.box_number || 'Box') + ' - ' + slot.archive.title) : (slot.is_active === false ? (slot.slot_code + ': Non-Aktif') : (slot.slot_code + ' [No. ' + (slot.box_number_display || slot.slot_number) + ']: Slot Kosong'))"
                                                >
                                                    <div class="w-full flex items-center justify-between text-[9px] font-mono font-bold opacity-90 mb-0.5">
                                                        <span class="text-slate-700 dark:text-slate-300 font-bold" x-text="slot.box_number_display || slot.slot_number"></span>
                                                        <template x-if="slot.is_active === false || slot.status === 'inactive'">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-600" title="Non-Aktif"></span>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && (slot.status === 'expired' || slot.archive?.is_expired)">
                                                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && (slot.status === 'filled' || slot.archive) && !(slot.status === 'expired' || slot.archive?.is_expired)">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && !slot.archive && slot.status === 'empty'">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400/80"></span>
                                                        </template>
                                                    </div>

                                                    <!-- Content / Box Title -->
                                                    <div class="w-full flex-1 flex flex-col items-center justify-center">
                                                        <template x-if="slot.is_active === false || slot.status === 'inactive'">
                                                            <span class="text-[9px] font-semibold text-slate-400 dark:text-slate-500 font-mono italic">Non-Aktif</span>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && slot.archive">
                                                            <div class="space-y-0.5 w-full">
                                                                <span class="font-mono font-bold text-[10px] leading-tight block truncate max-w-[80px] mx-auto" :class="(slot.status === 'expired' || slot.archive?.is_expired) ? '!text-white font-black' : ((slot.status === 'near_exp' || slot.is_near_exp || slot.archive?.is_near_exp) ? 'text-rose-700 dark:text-rose-300' : 'text-amber-700 dark:text-amber-300')" x-text="slot.archive.box_number || 'TERISI'"></span>
                                                                <span class="text-[8px] text-slate-500 dark:text-slate-400 font-mono block truncate max-w-[80px] mx-auto" x-text="slot.archive.periode_doc || slot.archive.department || ''"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && !slot.archive">
                                                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 font-mono">Kosong</span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                                        <template x-if="slot.is_active === false || slot.status === 'inactive'">
                                                            <span class="text-[9px] font-semibold text-slate-400 dark:text-slate-500 font-mono italic">Non-Aktif</span>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && slot.archive">
                                                            <div class="space-y-0.5 w-full">
                                                                <span class="font-mono font-bold text-[10px] leading-tight block truncate text-amber-700 dark:text-amber-300 max-w-[80px] mx-auto" x-text="slot.archive.box_number || 'TERISI'"></span>
                                                                <span class="text-[8px] text-slate-500 dark:text-slate-400 font-mono block truncate max-w-[80px] mx-auto" x-text="slot.archive.periode_doc || slot.archive.department || ''"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="slot.is_active !== false && slot.status !== 'inactive' && !slot.archive">
                                                            <span class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400 font-mono">Kosong</span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Right: Slot Detail Inspector -->
                <div class="w-full lg:w-96 bg-slate-50 dark:bg-slate-950 border-t lg:border-t-0 lg:border-l border-slate-200 dark:border-slate-800 p-4 overflow-y-auto flex flex-col justify-between space-y-4 shadow-xl shrink-0">
                    <!-- If Slot NOT Selected -->
                    <div x-show="!selectedSlotDetail" class="py-12 text-center space-y-3 my-auto">
                        <div class="w-12 h-12 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center border border-indigo-200 dark:border-indigo-500/20">
                            <i data-lucide="mouse-pointer-click" class="w-6 h-6"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider font-mono">Inspector Slot Rak</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto leading-relaxed">
                            Klik salah satu dari 100 kotak slot box (TB 30g) pada denah di sebelah kiri untuk melihat rincian dokumen dan opsi cetak label.
                        </p>
                    </div>

                    <!-- If Slot IS Selected -->
                    <div x-show="selectedSlotDetail" class="space-y-3.5" x-cloak>
                        <!-- Inspector Header -->
                        <div class="border-b border-slate-200 dark:border-slate-800 pb-3 flex items-start justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-indigo-600 dark:text-indigo-400 tracking-wider block font-mono" x-text="'LVL ' + (selectedSlotDetail?.sap_level || '') + ' • ' + (selectedSlotDetail?.layer_label || '')"></span>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white font-mono flex items-center gap-2">
                                    <span x-text="selectedSlotDetail?.slot_code"></span>
                                    <span class="text-xs text-slate-500 font-sans font-medium" x-text="'(Box #' + (selectedSlotDetail?.box_number_display || selectedSlotDetail?.slot_number || '') + ')'"></span>
                                </h3>
                            </div>
                            <span 
                                class="px-2 py-0.5 text-[10px] font-bold uppercase rounded tracking-wider"
                                :class="selectedSlotDetail?.is_active === false ? 'bg-slate-200 text-slate-700 border border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' : ((selectedSlotDetail?.status === 'expired' || selectedSlotDetail?.archive?.is_expired) ? 'bg-rose-100 text-rose-800 border border-rose-300 dark:bg-rose-500/20 dark:text-rose-300 dark:border-rose-500/40' : (selectedSlotDetail?.archive ? 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-400/20 dark:text-amber-300 dark:border-amber-400/40' : 'bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/40'))"
                                x-text="selectedSlotDetail?.is_active === false ? 'NON-AKTIF' : ((selectedSlotDetail?.status === 'expired' || selectedSlotDetail?.archive?.is_expired) ? 'EXPIRED' : (selectedSlotDetail?.archive ? 'TERISI' : 'KOSONG'))"
                            ></span>
                        </div>

                        <!-- Property Table for Occupied Archive -->
                        <template x-if="selectedSlotDetail?.archive">
                            <div class="space-y-3 text-xs">
                                <!-- Property Sheet Table -->
                                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg divide-y divide-slate-200 dark:divide-slate-800 overflow-hidden text-xs">
                                    <div class="p-2.5 flex items-center justify-between">
                                        <span class="text-slate-500 dark:text-slate-400 text-[10px] font-mono uppercase font-bold">No. Box:</span>
                                        <span class="font-mono text-xs font-bold text-amber-600 dark:text-amber-300" x-text="selectedSlotDetail.archive.box_number || '-'"></span>
                                    </div>
                                    <div class="p-2.5 space-y-0.5">
                                        <span class="text-slate-500 dark:text-slate-400 text-[10px] font-mono uppercase font-bold block">Judul Dokumen:</span>
                                        <h4 class="font-medium text-slate-900 dark:text-white text-xs leading-snug" x-text="selectedSlotDetail.archive.title"></h4>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between">
                                        <span class="text-slate-500 dark:text-slate-400 text-[10px] font-mono uppercase font-bold">Periode:</span>
                                        <span class="font-mono text-xs text-slate-800 dark:text-slate-200" x-text="selectedSlotDetail.archive.periode_doc || '-'"></span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between">
                                        <span class="text-slate-500 dark:text-slate-400 text-[10px] font-mono uppercase font-bold">Departemen:</span>
                                        <span class="text-xs text-slate-800 dark:text-slate-200 font-medium" x-text="selectedSlotDetail.archive.department_name ? (selectedSlotDetail.archive.department + ' - ' + selectedSlotDetail.archive.department_name) : (selectedSlotDetail.archive.department || '-')"></span>
                                    </div>
                                    <template x-if="selectedSlotDetail.archive.sub_department || selectedSlotDetail.archive.sub_department_name">
                                        <div class="p-2.5 flex items-center justify-between">
                                            <span class="text-slate-500 dark:text-slate-400 text-[10px] font-mono uppercase font-bold">Sub-Dept:</span>
                                            <span class="text-xs text-indigo-600 dark:text-indigo-300 font-medium" x-text="selectedSlotDetail.archive.sub_department_name ? (selectedSlotDetail.archive.sub_department + ' - ' + selectedSlotDetail.archive.sub_department_name) : selectedSlotDetail.archive.sub_department"></span>
                                        </div>
                                    </template>
                                    <div class="p-2.5 flex items-center justify-between">
                                        <span class="text-slate-500 dark:text-slate-400 text-[10px] font-mono uppercase font-bold">Masa Simpan:</span>
                                        <span class="font-mono text-xs" :class="(selectedSlotDetail.status === 'expired' || selectedSlotDetail.archive.is_expired) ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-800 dark:text-slate-200'" x-text="selectedSlotDetail.archive.formatted_expiry_date || formatDate(selectedSlotDetail.archive.retention_expiry_date) || '-'"></span>
                                    </div>
                                </div>

                                <!-- Expired Warning Alert -->
                                <template x-if="selectedSlotDetail.status === 'expired' || selectedSlotDetail.archive.is_expired">
                                    <div class="p-2.5 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 rounded-lg space-y-1">
                                        <div class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400 font-bold text-xs">
                                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400"></i>
                                            <span>Masa Simpan Kedaluwarsa</span>
                                        </div>
                                        <p class="text-[11px] text-slate-600 dark:text-slate-300">
                                            Arsip pada box ini telah melewati masa retensi dan dapat diproses untuk pemusnahan dokumen.
                                        </p>
                                    </div>
                                </template>

                                <!-- Action Buttons -->
                                <div class="pt-1 space-y-1.5">
                                    <a 
                                        :href="'/archives/' + selectedSlotDetail.archive.id" 
                                        target="_blank"
                                        class="w-full py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5"
                                    >
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        <span>Buka Detail Halaman Arsip</span>
                                    </a>

                                    <button 
                                        type="button"
                                        @click="window.openLabelModal(selectedSlotDetail.archive.id)" 
                                        :disabled="!selectedSlotDetail.archive || !['approved_booked', 'in_warehouse', 'pending_extension'].includes(selectedSlotDetail.archive.status)"
                                        :class="!selectedSlotDetail.archive || !['approved_booked', 'in_warehouse', 'pending_extension'].includes(selectedSlotDetail.archive.status) ? 'opacity-40 cursor-not-allowed bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 border-slate-200 dark:border-slate-800' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-amber-700 dark:text-amber-300 border-slate-300 dark:border-slate-700 cursor-pointer'"
                                        class="w-full py-2 font-bold text-xs rounded-lg transition flex items-center justify-center gap-1.5 border"
                                        :title="!selectedSlotDetail.archive || !['approved_booked', 'in_warehouse', 'pending_extension'].includes(selectedSlotDetail.archive.status) ? 'Label dinonaktifkan: Hanya untuk arsip yang disetujui (Approved / Booked), di gudang, atau perpanjangan' : 'Cetak Label Box Form A5 (TB 30g)'"
                                    >
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        <span>Cetak Label Box Form A5 (TB 30g)</span>
                                    </button>

                                    <button 
                                        type="button" 
                                        @click="unassignCurrentSlot()" 
                                        :disabled="slotAssignLoading"
                                        class="w-full py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 text-rose-700 hover:text-rose-800 dark:text-rose-300 dark:hover:text-rose-200 border border-rose-300 dark:border-rose-600/50 font-bold text-xs rounded-lg transition flex items-center justify-center gap-1.5 cursor-pointer"
                                    >
                                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                                        <span x-text="slotAssignLoading ? 'Memproses...' : 'Kosongkan / Lepas Box Dari Slot Ini'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- If Empty Slot -->
                        <template x-if="!selectedSlotDetail?.archive">
                            <div class="space-y-3 text-xs">
                                <div 
                                    class="p-3 rounded-lg flex items-center justify-between border"
                                    :class="selectedSlotDetail?.is_active === false ? 'bg-slate-100 dark:bg-slate-900 border-slate-300 dark:border-slate-800' : 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30'"
                                >
                                    <div class="flex items-center gap-1.5 font-bold" :class="selectedSlotDetail?.is_active === false ? 'text-slate-700 dark:text-slate-300' : 'text-emerald-700 dark:text-emerald-400'">
                                        <i :data-lucide="selectedSlotDetail?.is_active === false ? 'power-off' : 'inbox'" class="w-4 h-4"></i>
                                        <span x-text="selectedSlotDetail?.is_active === false ? 'Status Slot Rak' : 'Slot Siap Digunakan'"></span>
                                    </div>
                                    <span class="text-[10px] font-mono font-bold" :class="selectedSlotDetail?.is_active === false ? 'text-slate-700 dark:text-slate-400' : 'text-emerald-800 dark:text-emerald-300'" x-text="selectedSlotDetail?.is_active === false ? 'NON-AKTIF' : 'KOSONG'"></span>
                                </div>

                                <!-- SuperAdmin Toggle Switch Button -->
                                @if(auth()->user()->isSuperAdmin())
                                <div class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2 shadow-xs">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <i data-lucide="power" class="w-3.5 h-3.5" :class="(selectedSlotDetail?.is_active !== false) ? 'text-emerald-500' : 'text-slate-400'"></i>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Status Slot (SuperAdmin)</span>
                                            </div>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                                Aktifkan / non-aktifkan slot rak
                                            </p>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="toggleSlotActiveStatus(selectedSlotDetail)"
                                            :disabled="slotToggleLoading"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                            :class="(selectedSlotDetail?.is_active !== false) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"
                                            role="switch"
                                            :aria-checked="selectedSlotDetail?.is_active !== false"
                                            :title="selectedSlotDetail?.is_active !== false ? 'Klik untuk non-aktifkan slot' : 'Klik untuk aktifkan slot'"
                                        >
                                            <span class="sr-only">Toggle Status Slot</span>
                                            <span 
                                                aria-hidden="true" 
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="(selectedSlotDetail?.is_active !== false) ? 'translate-x-5' : 'translate-x-0'"
                                            ></span>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-slate-100 dark:border-slate-800">
                                        <span class="text-slate-500 dark:text-slate-400">Status Operasional:</span>
                                        <span 
                                            class="font-mono font-bold text-xs flex items-center gap-1"
                                            :class="(selectedSlotDetail?.is_active !== false) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'"
                                        >
                                            <span class="w-2 h-2 rounded-full" :class="(selectedSlotDetail?.is_active !== false) ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                                            <span x-text="(selectedSlotDetail?.is_active !== false) ? 'ACTIVE (Aktif)' : 'INACTIVE (Non-Aktif)'"></span>
                                        </span>
                                    </div>
                                </div>
                                @endif

                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed" x-text="selectedSlotDetail?.is_active === false ? 'Slot rak ini sedang dinonaktifkan oleh SuperAdmin dan tidak dapat dialokasikan untuk penyimpanan arsip.' : 'Slot rak ini kosong dan dapat dialokasikan untuk penyimpanan box arsip baru melalui halaman Layout Gudang 2D atau Katalog Arsip saat check-in.'">
                                </p>
                            </div>
                        </template>
                    </div>

                    <!-- Bottom Close Button in Inspector -->
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="rackGridModalOpen = false" 
                            class="w-full py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg transition cursor-pointer"
                        >
                            Tutup Modal Denah Rak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>