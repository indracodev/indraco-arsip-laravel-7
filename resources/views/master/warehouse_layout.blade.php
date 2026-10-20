@extends('layouts.app')

@section('title', 'Interactive Layout Gudang 2D - DMS PT Indraco')

@section('content')
<style>
    @keyframes pulseNearExp {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.55; }
    }
    .animate-pulse-near-exp {
        animation: pulseNearExp 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulseExpired {
        0%, 100% { opacity: 1; filter: brightness(1); }
        50% { opacity: 0.82; filter: brightness(1.22); }
    }
    .animate-pulse-expired {
        animation: pulseExpired 0.9s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>
<div class="space-y-3" x-data="warehouseCanvasApp()">
    <!-- Header Controls -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-2 font-mono">
        <div class="flex items-center gap-2">
            <span class="p-1 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded">
                <i data-lucide="map" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Interactive Layout Gudang (2D Canvas)
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Peta visual denah gudang fisik PT Indraco. Tambah Gudang/Sektor (Square) & Rak (Rectangle), resize, drag, warna & nama.
                </p>
            </div>
        </div>
    </div>

    @include('master.warehouse_layout.partials.canvas_toolbar')

    <!-- Main Workspace Container: Canvas + Inspector Drawer -->
    <div 
        id="canvasWorkspaceWrapper"
        :class="isFullscreen ? 'fixed inset-0 z-[9999] bg-slate-950 p-4 sm:p-6 overflow-hidden flex flex-col lg:flex-row gap-6 w-screen h-screen' : 'grid grid-cols-1 lg:grid-cols-3 gap-6'"
    >
        <!-- Canvas Visualizer Container (2 Cols in normal, Flex-1 in Fullscreen) -->
        <div 
            :class="isFullscreen ? 'flex-1 bg-slate-900 border border-slate-800 rounded p-4 shadow-2xl relative overflow-auto flex flex-col items-center justify-center h-full min-h-0' : 'lg:col-span-2 bg-slate-900 border border-slate-800 rounded p-4 shadow-xl relative overflow-hidden flex flex-col items-center justify-center min-h-[600px]'"
        >
            <!-- Processing Indicator -->
            <div x-show="loading" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm z-30 flex items-center justify-center gap-3 text-white font-mono">
                <i data-lucide="loader-2" class="w-5 h-5 animate-spin text-amber-400"></i>
                <span class="text-xs font-bold uppercase tracking-wider">Memuat Layout Canvas Gudang...</span>
            </div>

            <!-- Floating Action Buttons inside Canvas Container -->
            <div class="absolute top-3 right-3 z-20 flex items-center gap-2">
                <template x-if="isFullscreen">
                    <div class="flex items-center gap-1.5 bg-slate-900/95 p-1 rounded border border-slate-700 shadow-xl font-mono">
                        <button 
                            @click="openAddRoomModal()" 
                            type="button" 
                            class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded border border-emerald-700 transition flex items-center gap-1.5 shadow-xs cursor-pointer"
                            title="Tambah Object Gudang Baru (Square)"
                        >
                            <i data-lucide="square" class="w-3.5 h-3.5"></i> + Gudang
                        </button>
                        <button 
                            @click="openAddRackModal()" 
                            type="button" 
                            class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded border border-blue-700 transition flex items-center gap-1.5 shadow-xs cursor-pointer"
                            title="Tambah Object Rak Baru (Rectangle)"
                        >
                            <i data-lucide="rectangle-horizontal" class="w-3.5 h-3.5"></i> + Rak
                        </button>
                        <button 
                            @click="openCanvasResizeModal()" 
                            type="button" 
                            class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold text-xs rounded border border-slate-700 transition flex items-center gap-1.5 cursor-pointer"
                            title="Ubah Dimensi Ukuran Canvas"
                        >
                            <i data-lucide="scaling" class="w-3.5 h-3.5"></i>
                            <span class="font-mono text-[11px]" x-text="canvasWidth + 'x' + canvasHeight"></span>
                        </button>
                    </div>
                </template>

                <button 
                    @click="toggleFullscreen()" 
                    type="button" 
                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white border border-indigo-700 rounded shadow-md transition flex items-center gap-2 text-xs font-bold font-mono group cursor-pointer"
                >
                    <i data-lucide="maximize-2" x-show="!isFullscreen" class="w-3.5 h-3.5"></i>
                    <i data-lucide="minimize-2" x-show="isFullscreen" class="w-3.5 h-3.5 text-amber-300"></i>
                    <span x-text="isFullscreen ? 'Keluar Fullscreen (Esc)' : '1 Layar Penuh ⛶'"></span>
                </button>
            </div>

            <div id="canvasScrollContainer" class="relative max-w-full max-h-full overflow-auto p-2">
                <canvas 
                    id="warehouseCanvas" 
                    :width="canvasWidth" 
                    :height="canvasHeight" 
                    @mousedown="handleCanvasMouseDown($event)"
                    @mousemove="handleCanvasMouseMove($event)"
                    @mouseup="handleCanvasMouseUp($event)"
                    @dblclick="handleCanvasDoubleClick($event)"
                    @contextmenu.prevent="handleCanvasContextMenu($event)"
                    class="cursor-crosshair border border-slate-800 rounded shadow-inner bg-[#0b1120] transition-transform duration-75 block"
                ></canvas>

                <!-- Bottom-Right Interactive Canvas Drag Resizer Handle -->
                <div 
                    @mousedown.stop.prevent="startCanvasResize($event)"
                    @dblclick="openCanvasResizeModal()"
                    class="absolute bottom-3 right-3 z-20 bg-slate-900/95 hover:bg-cyan-700 text-slate-300 hover:text-white border border-slate-700 hover:border-cyan-500 rounded px-2 py-1 text-[10px] font-mono font-bold shadow-md cursor-se-resize select-none transition flex items-center gap-1.5 group"
                    title="Tarik / Drag sudut ini untuk me-resize ukuran canvas, atau Klik 2x untuk buka modal setup ukuran."
                >
                    <i data-lucide="scaling" class="w-3 h-3 text-cyan-400 group-hover:text-white"></i>
                    <span x-text="canvasWidth + ' × ' + canvasHeight + ' px'"></span>
                    <span class="text-xs opacity-75">⇲</span>
                </div>
            </div>

            @include('master.warehouse_layout.partials.canvas_context_menu')
        <!-- Drawer Inspector Side Panel (1 Col) -->
        <div :class="isFullscreen ? 'space-y-3 w-full lg:w-96 flex-shrink-0 max-h-full overflow-y-auto pr-1' : 'space-y-3'">
            <!-- Selected Object Inspector Card -->
            <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-4 shadow-xs space-y-4 font-mono">
                <!-- If No Location Selected -->
                <div x-show="!selectedLocation" class="py-8 text-center space-y-2">
                    <div class="p-2.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded border border-amber-500/20 w-10 h-10 mx-auto flex items-center justify-center">
                        <i data-lucide="mouse-pointer-click" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Pilih Object Gudang / Rak</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 max-w-xs mx-auto leading-relaxed font-sans">
                        Klik pada object Gudang atau Rak di canvas untuk melihat rincian, menggeser posisi, mengubah ukuran, warna, atau aksi lainnya.
                    </p>
                </div>

                <!-- If Location Selected -->
                <div x-show="selectedLocation" class="space-y-4" x-cloak>
                    <!-- If MULTIPLE Locations Selected (Multi-Selection Batch Actions Card) -->
                    <template x-if="selectedLocations.length > 1">
                        <div class="p-3 bg-indigo-500/10 border border-indigo-500/30 rounded space-y-2.5 mb-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-indigo-400 flex items-center gap-1.5 uppercase">
                                    <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-400"></i>
                                    Batch Setup (Multi-Select)
                                </span>
                                <span class="px-1.5 py-0.5 bg-indigo-500/20 text-indigo-300 text-[10px] font-mono font-bold rounded border border-indigo-500/30" x-text="selectedLocations.length + ' Objek'"></span>
                            </div>
                            <p class="text-[11px] text-slate-400 font-medium font-sans">
                                Ubah properti seluruh objek terpilih serentak:
                            </p>
                            <div class="grid grid-cols-2 gap-1.5">
                                <button @click="bulkToggleLockLocations(false)" type="button" class="px-2 py-1.5 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 text-xs font-bold rounded border border-emerald-500/30 transition flex items-center justify-center gap-1 cursor-pointer">
                                    <i data-lucide="unlock" class="w-3.5 h-3.5"></i> Buka Kunci
                                </button>
                                <button @click="bulkToggleLockLocations(true)" type="button" class="px-2 py-1.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-400 text-xs font-bold rounded border border-amber-500/30 transition flex items-center justify-center gap-1 cursor-pointer">
                                    <i data-lucide="lock" class="w-3.5 h-3.5"></i> Kunci Semua
                                </button>
                                <button @click="openBatchResizeModal()" type="button" class="px-2 py-1.5 bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 text-xs font-bold rounded border border-blue-500/30 transition flex items-center justify-center gap-1 cursor-pointer">
                                    <i data-lucide="scaling" class="w-3.5 h-3.5"></i> Ubah Ukuran
                                </button>
                                @if (auth()->check() && auth()->user()->isSuperAdmin())
                                <button @click="openBatchDeptModal()" type="button" class="px-2 py-1.5 bg-purple-600/20 hover:bg-purple-600/30 text-purple-400 text-xs font-bold rounded border border-purple-500/30 transition flex items-center justify-center gap-1 cursor-pointer">
                                    <i data-lucide="building-2" class="w-3.5 h-3.5"></i> Set Dept
                                </button>
                                @endif
                                <button @click="openBatchColorModal()" type="button" class="px-2 py-1.5 bg-pink-600/20 hover:bg-pink-600/30 text-pink-400 text-xs font-bold rounded border border-pink-500/30 transition flex items-center justify-center gap-1 col-span-2 cursor-pointer">
                                    <i data-lucide="palette" class="w-3.5 h-3.5"></i> Set Warna Massal
                                </button>
                                <button @click="alignGroupHorizontally()" type="button" class="px-2 py-1.5 bg-teal-600/20 hover:bg-teal-600/30 text-teal-400 text-xs font-bold rounded border border-teal-500/30 transition flex items-center justify-center gap-1 cursor-pointer">
                                    <i data-lucide="align-horizontal-space-around" class="w-3.5 h-3.5"></i> Rapatkan Baris
                                </button>
                                <button @click="alignGroupVertically()" type="button" class="px-2 py-1.5 bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-400 text-xs font-bold rounded border border-cyan-500/30 transition flex items-center justify-center gap-1 cursor-pointer">
                                    <i data-lucide="align-vertical-space-around" class="w-3.5 h-3.5"></i> Rapatkan Kolom
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Header & Type Badge -->
                    <div class="flex items-start justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-1.5 py-0.5 text-[10px] font-bold uppercase rounded tracking-wider text-white" :class="selectedLocation?.location_type === 'room' ? 'bg-emerald-600' : 'bg-blue-600'" x-text="selectedLocation?.location_type === 'room' ? 'OBJECT GUDANG / SEKTOR' : 'OBJECT RAK'"></span>
                                <template x-if="selectedLocation?.custom_color">
                                    <span class="w-3 h-3 rounded-none border border-slate-400" :style="{ backgroundColor: selectedLocation?.custom_color }"></span>
                                </template>
                            </div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-900 dark:text-white uppercase" x-text="selectedLocation?.rack_code"></h2>
                                <!-- Edit Pencil Icon Button next to title -->
                                <button @click="openEditModal()" type="button" title="Edit Detail Object (Nama, Sektor, Warna, Kapasitas, Geometri)" class="px-1.5 py-0.5 bg-blue-600/10 hover:bg-blue-600/20 text-blue-600 dark:text-blue-400 rounded transition flex items-center gap-1 text-[11px] font-bold border border-blue-500/20 cursor-pointer">
                                    <i data-lucide="pencil" class="w-3 h-3 text-blue-500"></i>
                                    <span>Edit</span>
                                </button>
                            </div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium font-sans" x-text="'Sektor: ' + (selectedLocation?.room_sector || 'Umum')"></span>
                            </div>
                        </div>
                        <button @click="selectedLocation = null; renderCanvas()" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Lock Status Card (Fitur Lama: Kunci/Buka Posisi Object Canvas) -->
                    <template x-if="selectedLocation">
                        <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase font-bold block" x-text="selectedLocation?.location_type === 'room' ? 'Status Gudang:' : 'Status Rak:'"></span>
                                <span class="font-bold text-xs flex items-center gap-1 mt-0.5" :class="selectedLocation?.is_locked !== false ? 'text-rose-500' : 'text-emerald-500'">
                                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                    <span x-text="selectedLocation?.is_locked !== false ? 'Terkunci (Locked 🔒)' : 'Terbuka (Unlocked 🔓)'"></span>
                                </span>
                            </div>
                            <button @click="toggleLockLocation(selectedLocation)" type="button" class="px-2.5 py-1 rounded font-bold text-xs shadow-xs transition text-white cursor-pointer border" :class="selectedLocation?.is_locked !== false ? 'bg-emerald-600 hover:bg-emerald-500 border-emerald-700' : 'bg-rose-600 hover:bg-rose-500 border-rose-700'">
                                <span x-text="selectedLocation?.is_locked !== false ? '🔓 Buka Kunci' : '🔒 Kunci Object'"></span>
                            </button>
                        </div>
                    </template>

                    <!-- FITUR BARU: Alokasi / Pembatasan Penempatan Box Berdasarkan Departemen -->
                    <template x-if="selectedLocation">
                        <div class="p-2.5 bg-indigo-50/50 dark:bg-slate-900 rounded border border-indigo-200/80 dark:border-slate-800 space-y-1 font-sans">
                            <div class="flex items-center justify-between">
                                <span class="text-indigo-900 dark:text-indigo-300 text-[10px] uppercase font-bold block">Izin Penempatan Box:</span>
                                @if (auth()->check() && auth()->user()->isSuperAdmin())
                                <button @click="openEditModal()" type="button" class="text-indigo-600 dark:text-indigo-400 hover:underline text-[10px] font-bold cursor-pointer">
                                    Atur Dept
                                </button>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 text-xs font-semibold flex-wrap">
                                <div x-show="getAllowedDeptIds(selectedLocation).length > 0" class="flex items-center gap-1 flex-wrap">
                                    <template x-for="deptId in getAllowedDeptIds(selectedLocation)" :key="deptId">
                                        <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-950/70 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded font-mono text-[11px] flex items-center gap-1 truncate max-w-full">
                                            <i data-lucide="building" class="w-3 h-3 text-indigo-500 shrink-0"></i>
                                            <span x-text="getDeptCode(deptId)" class="truncate font-bold"></span>
                                        </span>
                                    </template>
                                </div>
                                <span x-show="getAllowedDeptIds(selectedLocation).length === 0" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded font-mono text-[11px] flex items-center gap-1">
                                    <i data-lucide="globe" class="w-3 h-3 text-slate-400 shrink-0"></i>
                                    <span>Semua Departemen (Bebas)</span>
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Geometri Canvas Info -->
                    <div class="relative grid grid-cols-2 gap-2 text-xs p-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded font-mono">
                        <div><span class="text-slate-400">Posisi:</span> <span class="font-bold text-slate-900 dark:text-white" x-text="'X:' + selectedLocation?.canvas_x + ', Y:' + selectedLocation?.canvas_y"></span></div>
                        <div class="flex items-center justify-between">
                            <div><span class="text-slate-400">Ukuran:</span> <span class="font-bold text-amber-600 dark:text-amber-400" x-text="selectedLocation?.canvas_width + 'x' + selectedLocation?.canvas_height + 'px'"></span></div>
                            <button @click="openEditModal()" type="button" title="Edit Dimensi & Posisi Geometri" class="p-0.5 text-slate-400 hover:text-blue-500 transition cursor-pointer">
                                <i data-lucide="pencil" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Capacity Progress Bar (If Rack) -->
                    <template x-if="selectedLocation?.location_type !== 'room'">
                        <div class="space-y-2">
                            <div class="space-y-1">
                                <div class="flex justify-between items-center text-xs font-bold">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-slate-600 dark:text-slate-400">Kapasitas Terisi:</span>
                                        <button @click="openEditModal()" type="button" title="Edit Kapasitas Box" class="p-0.5 text-slate-400 hover:text-blue-500 transition cursor-pointer">
                                            <i data-lucide="pencil" class="w-3 h-3"></i>
                                        </button>
                                    </div>
                                    <span :style="{ color: selectedLocation?.status_color }" x-text="(selectedLocation?.current_box_count || 0) + ' / ' + (selectedLocation?.box_capacity || 0) + ' Box (' + (selectedLocation?.capacity_percentage || 0) + '%)'"></span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-900 rounded-none h-2.5 overflow-hidden border border-slate-300 dark:border-slate-800">
                                    <div class="h-full transition-all duration-300" :style="{ width: (selectedLocation?.capacity_percentage || 0) + '%', backgroundColor: selectedLocation?.status_color }"></div>
                                </div>
                            </div>

                            <!-- 100-Box Grid Highlight CTA Card -->
                            <div class="p-3 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded space-y-2 shadow-xs font-mono">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide flex items-center gap-1.5">
                                        <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-indigo-500"></i>
                                        Visualisasi 100 Slot Box (TB 30g)
                                    </span>
                                    <span class="px-1.5 py-0.2 bg-indigo-500/20 text-indigo-400 text-[10px] font-bold rounded border border-indigo-500/30">5 LVL × 20</span>
                                </div>

                                <div class="grid grid-cols-3 gap-1.5 text-center text-[10px] font-bold">
                                    <div class="p-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded text-emerald-500">
                                        <span class="block text-[8px] uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Kosong</span>
                                        <span class="text-xs font-mono font-bold" x-text="getRackSlotStats(selectedLocation).empty"></span>
                                    </div>
                                    <div class="p-1.5 bg-amber-500/10 border border-amber-500/20 rounded text-amber-500">
                                        <span class="block text-[8px] uppercase tracking-wider text-amber-600 dark:text-amber-400">Terisi</span>
                                        <span class="text-xs font-mono font-bold" x-text="getRackSlotStats(selectedLocation).filled"></span>
                                    </div>
                                    <div class="p-1.5 bg-rose-500/10 border border-rose-500/20 rounded text-rose-500">
                                        <span class="block text-[8px] uppercase tracking-wider text-rose-600 dark:text-rose-400">Expired</span>
                                        <span class="text-xs font-mono font-bold" x-text="getRackSlotStats(selectedLocation).expired"></span>
                                    </div>
                                </div>

                                <button 
                                    @click="openRackGridModal(selectedLocation)" 
                                    type="button" 
                                    class="w-full py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded border border-indigo-700 shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                                >
                                    <i data-lucide="grid" class="w-3.5 h-3.5"></i>
                                    <span>Buka Grid Rak 5 LVL (100 Box)</span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Department Allocation & Booking Info -->
                    <div class="space-y-2 text-xs">
                        <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                            <div class="flex items-center justify-between mb-0.5">
                                <span class="text-slate-500 dark:text-slate-400 font-bold uppercase text-[10px]">Alokasi Departemen:</span>
                                @if (auth()->check() && auth()->user()->isSuperAdmin())
                                <button @click="openEditModal()" type="button" title="Edit Alokasi Departemen" class="p-0.5 text-slate-400 hover:text-blue-500 transition cursor-pointer">
                                    <i data-lucide="pencil" class="w-3 h-3"></i>
                                </button>
                                @endif
                            </div>
                            <span class="font-bold text-slate-900 dark:text-white text-xs" x-text="getAllowedDeptSummary(selectedLocation)"></span>
                        </div>

                        <template x-if="selectedLocation?.is_booked">
                            <div class="p-2.5 bg-amber-500/10 border border-amber-500/30 rounded space-y-1">
                                <span class="text-amber-700 dark:text-amber-400 font-bold uppercase text-[10px] block">Status: Reserved Booking</span>
                                <p class="text-slate-700 dark:text-slate-300 font-medium font-sans text-[11px]" x-text="selectedLocation?.booking_notes || 'Alokasi booking berkas'"></p>
                                <span class="text-[10px] text-slate-500 block" x-text="'Oleh: ' + (selectedLocation?.booked_by_user || 'User')"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Archives List inside Selected Location -->
                    <template x-if="selectedLocation?.location_type !== 'room'">
                        <div class="space-y-2 pt-1">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block" x-text="'Daftar Box Arsip Tersimpan (' + (selectedLocation?.archives?.length || 0) + '):'"></span>
                            
                            <div class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
                                <template x-for="arc in selectedLocation?.archives || []" :key="arc.id">
                                    <div @click="openDocDetail(arc)" class="p-2 bg-slate-50 dark:bg-slate-900 hover:bg-amber-500/10 dark:hover:bg-amber-500/20 border border-slate-200 dark:border-slate-800 rounded cursor-pointer transition space-y-0.5">
                                        <span class="font-mono text-xs text-amber-600 dark:text-amber-400 font-bold block" x-text="arc.box_number || 'DRAFT'"></span>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-xs line-clamp-1 font-sans" x-text="arc.title"></h4>
                                        <div class="flex justify-between text-[10px] text-slate-500 font-medium">
                                            <span x-text="'Dept: ' + arc.department"></span>
                                            <span x-text="'Exp: ' + (arc.formatted_expiry_date || formatDate(arc.retention_expiry_date))"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!selectedLocation?.archives || selectedLocation?.archives.length === 0">
                                    <div class="p-3 text-center text-xs text-slate-400 font-medium bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                                        Belum ada box arsip di dalam rak ini.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-1.5 font-mono">
                        <template x-if="selectedLocation?.location_type !== 'room'">
                            <button @click="openRackGridModal(selectedLocation)" type="button" class="w-full py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded border border-indigo-700 shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i> Visualisasi 100 Slot Rak (5 LVL)
                            </button>
                        </template>

                        <template x-if="selectedLocation?.location_type === 'room'">
                            <button @click="addRackInsideRoom(selectedLocation, selectedLocation.canvas_x + 20, selectedLocation.canvas_y + 40)" type="button" class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded border border-emerald-700 shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="plus-square" class="w-3.5 h-3.5"></i> + Tambah Rak di Sektor Ini
                            </button>
                        </template>

                        <template x-if="selectedLocation?.location_type === 'room'">
                            <button @click="duplicateRack(selectedLocation)" type="button" class="w-full py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs rounded border border-slate-300 dark:border-slate-700 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="copy" class="w-3.5 h-3.5 text-purple-500"></i> Duplikat (Copy) Gudang Ini
                            </button>
                        </template>

                        <template x-if="selectedLocation?.location_type !== 'room'">
                            <button @click="duplicateRack(selectedLocation)" type="button" class="w-full py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs rounded border border-slate-300 dark:border-slate-700 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="copy" class="w-3.5 h-3.5 text-purple-500"></i> Duplikat (Copy) Rak Ini
                            </button>
                        </template>

                        <template x-if="selectedLocation?.location_type !== 'room'">
                            <button @click="openMoveModal(selectedLocation)" type="button" class="w-full py-1.5 bg-amber-500/15 hover:bg-amber-500/25 text-amber-800 dark:text-amber-300 font-bold text-xs rounded border border-amber-500/30 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="truck" class="w-3.5 h-3.5 text-amber-500"></i> Pindahkan Rak Ke Gudang...
                            </button>
                        </template>

                        <template x-if="selectedLocation?.location_type !== 'room' && !selectedLocation?.is_booked">
                            <button @click="openBookingModal()" type="button" class="w-full py-1.5 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs rounded border border-amber-700 shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="bookmark-plus" class="w-3.5 h-3.5"></i> Booking Slot Tempat Arsip
                            </button>
                        </template>

                        <template x-if="selectedLocation?.is_booked">
                            <button @click="unbookLocation()" type="button" class="w-full py-1.5 bg-rose-500/15 hover:bg-rose-500/25 text-rose-700 dark:text-rose-300 border border-rose-500/30 font-bold text-xs rounded transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="bookmark-x" class="w-3.5 h-3.5"></i> Lepas Status Booking
                            </button>
                        </template>

                        <button @click="rotateLocation(selectedLocation, 90)" type="button" class="w-full py-1.5 bg-cyan-600/15 hover:bg-cyan-600/25 text-cyan-800 dark:text-cyan-300 font-bold text-xs rounded border border-cyan-500/30 transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-cyan-500"></i> Putar Object 90° <span class="text-[10px] opacity-75">(Key: R)</span>
                        </button>

                        <button @click="openEditModal()" type="button" class="w-full py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs rounded border border-slate-300 dark:border-slate-700 transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit Nama, Warna & Geometri
                        </button>

                        <button @click="deleteSelectedLocation()" type="button" class="w-full py-1.5 bg-rose-600/15 hover:bg-rose-600/25 text-rose-600 dark:text-rose-400 font-bold text-xs rounded border border-rose-500/30 transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus Object Dari Canvas <span class="text-[10px] opacity-75">(Key: Del)</span>
                        </button>
                    </div>
                </div>
            </div>


    <!-- MODULAR 2D WAREHOUSE MODALS -->
    @include('master.warehouse_layout.modals.doc_inspector_modal')
    @include('master.warehouse_layout.modals.booking_slot_modal')
    @include('master.warehouse_layout.modals.insert_object_modal')
    @include('master.warehouse_layout.modals.edit_object_modal')
    @include('master.warehouse_layout.modals.move_rack_modal')
    @include('master.warehouse_layout.modals.batch_resize_modal')
    @include('master.warehouse_layout.modals.batch_dept_modal')
    @include('master.warehouse_layout.modals.batch_color_modal')
    @include('master.warehouse_layout.modals.setup_canvas_modal')
    @include('master.warehouse_layout.modals.rack_visualizer_modal')
    @include('master.warehouse_layout.modals.verify_archive_modal')

    <!-- Floating Toast Notification Banner (Square Desktop Style) -->
    <div 
        x-show="toastOpen" 
        x-cloak 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-4 right-4 z-[99999] bg-slate-900 text-slate-100 font-mono font-bold text-xs px-3.5 py-2.5 rounded border border-amber-500/60 shadow-2xl flex items-center gap-2"
    >
        <i data-lucide="info" class="w-4 h-4 text-amber-400"></i>
        <span x-text="toastMessage"></span>
    </div>
</div>

@push('scripts')
@include('master.warehouse_layout.partials.canvas_scripts')
@endpush
@endsection
