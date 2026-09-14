@extends('layouts.app')

@section('title', 'Interactive Layout Gudang 2D - DMS PT Indraco')

@section('content')
<div class="space-y-6" x-data="warehouseCanvasApp()">
    <!-- Header Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <i data-lucide="map" class="w-7 h-7 text-emerald-600 dark:text-emerald-400"></i>
                Interactive Layout Gudang (2D Canvas)
            </h1>
            <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm font-medium">
                Peta visual denah gudang fisik PT Indraco. Anda dapat menambah object Gudang/Sektor (Square) & Rak (Rectangle), mengubah ukuran (Resize), menggeser posisi (Drag), serta mengatur warna dan nama.
            </p>
        </div>

        <!-- Canvas Toolbar -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Add Object Action Group -->
            <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-900 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                <button @click="openAddRoomModal()" type="button" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow transition flex items-center gap-1.5">
                    <i data-lucide="square" class="w-4 h-4"></i> + Tambah Gudang (Square)
                </button>
                <button @click="openAddRackModal()" type="button" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow transition flex items-center gap-1.5">
                    <i data-lucide="rectangle-horizontal" class="w-4 h-4"></i> + Tambah Rak (Rectangle)
                </button>
            </div>

            <!-- Filter Department -->
            <div class="flex items-center gap-2">
                <select x-model="filterDepartment" @change="renderCanvas()" class="px-3 py-2 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:border-amber-500">
                    <option value="">-- Semua Departemen --</option>
                    <template x-for="dept in departments" :key="dept.id">
                        <option :value="dept.id" x-text="dept.code + ' - ' + dept.name"></option>
                    </template>
                </select>
            </div>

            <!-- Zoom Controls -->
            <div class="flex items-center bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-1 shadow-sm">
                <button @click="zoomOut()" type="button" class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-300 transition" title="Zoom Out">
                    <i data-lucide="zoom-out" class="w-4 h-4"></i>
                </button>
                <span class="px-2 text-xs font-mono font-bold text-amber-600 dark:text-amber-400" x-text="Math.round(scale * 100) + '%'"></span>
                <button @click="zoomIn()" type="button" class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-300 transition" title="Zoom In">
                    <i data-lucide="zoom-in" class="w-4 h-4"></i>
                </button>
                <button @click="resetZoom()" type="button" class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-300 transition ml-1" title="Reset Scale">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Canvas Dimension / Resizer Button -->
            <button 
                @click="openCanvasResizeModal()" 
                type="button" 
                class="px-3.5 py-2 bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs rounded-xl border border-slate-300 dark:border-slate-800 shadow-sm transition flex items-center gap-1.5"
                title="Ubah Dimensi (Lebar & Tinggi) Canvas Workspace Layout"
            >
                <i data-lucide="scaling" class="w-4 h-4 text-cyan-500"></i>
                <span>📐 Canvas: <strong class="text-cyan-600 dark:text-cyan-400 font-mono" x-text="canvasWidth + 'x' + canvasHeight + 'px'"></strong></span>
            </button>

            <!-- Fullscreen Mode Toggle Button -->
            <button 
                @click="toggleFullscreen()" 
                type="button" 
                class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs rounded-xl shadow transition flex items-center gap-2 border border-indigo-500/30"
                :title="isFullscreen ? 'Keluar Mode Fullscreen (Esc)' : 'Tampilkan Canvas 1 Layar Penuh (Fullscreen)'"
            >
                <i data-lucide="maximize-2" x-show="!isFullscreen" class="w-4 h-4"></i>
                <i data-lucide="minimize-2" x-show="isFullscreen" class="w-4 h-4"></i>
                <span x-text="isFullscreen ? 'Keluar Fullscreen (Esc)' : '1 Layar Penuh (Fullscreen)'"></span>
            </button>
        </div>
    </div>

    <!-- Legend & Mode Bar -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-wrap items-center justify-between gap-4 text-xs">
        <div class="flex flex-wrap items-center gap-4">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px]">Indikator Warna Rak (Default Kapasitas):</span>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Hijau (0% - 50%)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Kuning (51% - 80%)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Orange (81% - 90%)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Merah (91% - 100%)</span>
            </div>
            <div class="flex items-center gap-1.5 border-l border-slate-300 dark:border-slate-800 pl-4">
                <span class="w-3 h-3 rounded bg-purple-500"></span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">Custom Color</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium">Petunjuk:</span>
            <span class="px-2.5 py-1 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-lg font-bold">
                Mouse Drag / Panah (&uarr; &darr; &larr; &rarr;) | Shift + Klik: Multi-Select | Rotate (Key: R) | Hapus (Key: Delete) | Copy & Paste (Ctrl+C / Ctrl+V)
            </span>
        </div>
    </div>

    <!-- Main Workspace Container: Canvas + Inspector Drawer -->
    <div 
        id="canvasWorkspaceWrapper"
        :class="isFullscreen ? 'fixed inset-0 z-[9999] bg-slate-950 p-4 sm:p-6 overflow-hidden flex flex-col lg:flex-row gap-6 w-screen h-screen' : 'grid grid-cols-1 lg:grid-cols-3 gap-6'"
    >
        <!-- Canvas Visualizer Container (2 Cols in normal, Flex-1 in Fullscreen) -->
        <div 
            :class="isFullscreen ? 'flex-1 bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-2xl relative overflow-auto flex flex-col items-center justify-center h-full min-h-0' : 'lg:col-span-2 bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-xl relative overflow-hidden flex flex-col items-center justify-center min-h-[600px]'"
        >
            <!-- Processing Indicator -->
            <div x-show="loading" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm z-30 flex items-center justify-center gap-3 text-white">
                <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-amber-400"></i>
                <span class="text-xs font-bold">Memuat Layout Canvas Gudang...</span>
            </div>

            <!-- Floating Action Buttons inside Canvas Container -->
            <div class="absolute top-4 right-4 z-20 flex items-center gap-2">
                <template x-if="isFullscreen">
                    <div class="flex items-center gap-2 bg-slate-900/90 p-1 rounded-xl border border-slate-700/80 backdrop-blur-md shadow-xl">
                        <button 
                            @click="openAddRoomModal()" 
                            type="button" 
                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-lg transition flex items-center gap-1.5 shadow"
                            title="Tambah Object Gudang Baru (Square)"
                        >
                            <i data-lucide="square" class="w-3.5 h-3.5"></i> + Gudang
                        </button>
                        <button 
                            @click="openAddRackModal()" 
                            type="button" 
                            class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-lg transition flex items-center gap-1.5 shadow"
                            title="Tambah Object Rak Baru (Rectangle)"
                        >
                            <i data-lucide="rectangle-horizontal" class="w-3.5 h-3.5"></i> + Rak
                        </button>
                        <button 
                            @click="openCanvasResizeModal()" 
                            type="button" 
                            class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold text-xs rounded-lg transition flex items-center gap-1.5 border border-slate-700/80"
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
                    class="px-3.5 py-2 bg-slate-900/90 hover:bg-indigo-600 text-white border border-slate-700/80 rounded-xl shadow-xl transition flex items-center gap-2 text-xs font-extrabold backdrop-blur-md group"
                >
                    <i data-lucide="maximize-2" x-show="!isFullscreen" class="w-4 h-4 text-indigo-400 group-hover:text-white transition"></i>
                    <i data-lucide="minimize-2" x-show="isFullscreen" class="w-4 h-4 text-amber-400 group-hover:text-white transition"></i>
                    <span x-text="isFullscreen ? 'Keluar Fullscreen (Esc)' : '1 Layar Penuh ⛶'"></span>
                </button>
            </div>

            <div class="relative max-w-full max-h-full overflow-auto p-2">
                <canvas 
                    id="warehouseCanvas" 
                    :width="canvasWidth" 
                    :height="canvasHeight" 
                    @mousedown="handleCanvasMouseDown($event)"
                    @mousemove="handleCanvasMouseMove($event)"
                    @mouseup="handleCanvasMouseUp($event)"
                    @contextmenu.prevent="handleCanvasContextMenu($event)"
                    class="cursor-crosshair border border-slate-800/80 rounded-2xl shadow-inner bg-[#0b1120] transition-transform duration-75 block"
                ></canvas>

                <!-- Bottom-Right Interactive Canvas Drag Resizer Handle -->
                <div 
                    @mousedown.stop.prevent="startCanvasResize($event)"
                    @dblclick="openCanvasResizeModal()"
                    class="absolute bottom-4 right-4 z-20 bg-slate-900/90 hover:bg-cyan-600 text-slate-300 hover:text-white border border-slate-700 hover:border-cyan-400 rounded-xl px-2.5 py-1 text-[10px] font-mono font-bold shadow-xl cursor-se-resize select-none backdrop-blur-md transition flex items-center gap-1.5 group"
                    title="Tarik / Drag sudut ini untuk me-resize ukuran canvas, atau Klik 2x untuk buka modal setup ukuran."
                >
                    <i data-lucide="scaling" class="w-3.5 h-3.5 text-cyan-400 group-hover:text-white"></i>
                    <span x-text="canvasWidth + ' × ' + canvasHeight + ' px'"></span>
                    <span class="text-[9px] opacity-75">⇲</span>
                </div>
            </div>

            <!-- Floating Right-Click Context Menu -->
            <div 
                x-show="contextMenuOpen" 
                x-cloak 
                @click.away="contextMenuOpen = false"
                :style="{ top: contextMenuY + 'px', left: contextMenuX + 'px' }"
                class="fixed z-50 bg-slate-900 border border-slate-700/80 shadow-2xl rounded-2xl p-2 w-64 text-xs font-semibold text-slate-200 space-y-1 backdrop-blur-md"
            >
                <div class="px-3 py-1.5 border-b border-slate-800 text-[11px] font-bold text-amber-400 uppercase tracking-wider flex items-center justify-between">
                    <span x-text="selectedLocations.length > 1 ? '✨ KELOMPOK (' + selectedLocations.length + ' OBJEK)' : (contextMenuTarget ? (contextMenuTarget.location_type === 'room' ? 'GUDANG: ' + contextMenuTarget.rack_code : 'RAK: ' + contextMenuTarget.rack_code) : '+ TAMBAH OBJECT LAYOUT')"></span>
                    <span class="font-mono text-slate-400 text-[10px]" x-text="'X:' + clickedCanvasX + ' Y:' + clickedCanvasY"></span>
                </div>
                
                <!-- Actions when MULTIPLE objects are selected (Multi-Selection Batch Actions) -->
                <template x-if="selectedLocations.length > 1">
                    <div class="space-y-1 border-b border-slate-800 pb-1 mb-1">
                        <div class="px-2 py-1 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-[10px] font-bold text-indigo-400 mb-1 flex items-center justify-between">
                            <span>✨ BATCH SETUP (MULTI-SELECT)</span>
                            <span class="font-mono" x-text="selectedLocations.length + ' OBJEK'"></span>
                        </div>

                        <button 
                            @click="bulkToggleLockLocations(false); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="unlock" class="w-4 h-4 text-emerald-400"></i>
                            <span>🔓 Buka Kunci (Unlock) Kelompok</span>
                        </button>

                        <button 
                            @click="bulkToggleLockLocations(true); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="lock" class="w-4 h-4 text-amber-400"></i>
                            <span>🔒 Kunci (Lock) Kelompok</span>
                        </button>

                        <button 
                            @click="openBatchResizeModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-blue-600/10 hover:bg-blue-600/20 text-blue-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="scaling" class="w-4 h-4 text-blue-400"></i>
                            <span>📐 Ubah Ukuran Massal (W & H)...</span>
                        </button>

                        <button 
                            @click="openBatchDeptModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-purple-600/10 hover:bg-purple-600/20 text-purple-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="building-2" class="w-4 h-4 text-purple-400"></i>
                            <span>🏢 Set Alokasi Dept Massal...</span>
                        </button>

                        <button 
                            @click="openBatchColorModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-pink-600/10 hover:bg-pink-600/20 text-pink-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="palette" class="w-4 h-4 text-pink-400"></i>
                            <span>🎨 Set Warna Custom Massal...</span>
                        </button>

                        <button 
                            @click="rotateLocation(null, 90); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="rotate-cw" class="w-4 h-4 text-indigo-400"></i>
                            <span>🔄 Putar (Rotate) Kelompok 90°</span>
                        </button>

                        <button 
                            @click="alignGroupHorizontally(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-teal-600/10 hover:bg-teal-600/20 text-teal-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="align-horizontal-space-around" class="w-4 h-4 text-teal-400"></i>
                            <span>↔️ Rapatkan Sisi Lebar (Horizontal Row)</span>
                        </button>

                        <button 
                            @click="alignGroupVertically(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-cyan-600/10 hover:bg-cyan-600/20 text-cyan-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="align-vertical-space-around" class="w-4 h-4 text-cyan-400"></i>
                            <span>↕️ Rapatkan Sisi Panjang (Vertical Column)</span>
                        </button>

                        <button 
                            @click="deleteSelectedLocation(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 hover:bg-rose-600/20 hover:text-rose-400 rounded-xl transition flex items-center gap-2.5 font-bold text-rose-400"
                        >
                            <i data-lucide="trash-2" class="w-4 h-4 text-rose-500"></i>
                            <span>Hapus Kelompok Object (Delete)</span>
                        </button>
                    </div>
                </template>

                <!-- Actions when Right Clicked on a SINGLE GUDANG / ROOM object -->
                <template x-if="selectedLocations.length <= 1 && contextMenuTarget && contextMenuTarget.location_type === 'room'">
                    <div class="space-y-1 border-b border-slate-800 pb-1 mb-1">
                        <button 
                            @click="addRackInsideRoom(contextMenuTarget, clickedCanvasX, clickedCanvasY, 1)" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 rounded-xl transition flex items-center justify-between font-bold"
                        >
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="plus-square" class="w-4 h-4 text-emerald-500"></i>
                                <span>+ Tambah Rak (Single / Multi)</span>
                            </div>
                            <span class="text-[10px] px-1.5 py-0.5 bg-emerald-500/20 text-emerald-300 rounded font-mono font-black">1 - 50 Rak</span>
                        </button>
                        <button 
                            @click="toggleLockLocation(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-amber-500/10 hover:bg-amber-500/20 rounded-xl transition flex items-center gap-2.5 font-bold"
                            :class="contextMenuTarget?.is_locked !== false ? 'text-amber-400' : 'text-emerald-400'"
                        >
                            <i data-lucide="lock" class="w-4 h-4"></i>
                            <span x-text="contextMenuTarget?.is_locked !== false ? '🔓 Buka Kunci (Unlock) Gudang' : '🔒 Kunci (Lock) Gudang'"></span>
                        </button>
                        <button 
                            @click="rotateLocation(contextMenuTarget, 90); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="rotate-cw" class="w-4 h-4 text-indigo-400"></i>
                            <span>🔄 Putar (Rotate) Gudang 90°</span>
                        </button>
                        <button 
                            @click="duplicateRack(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-purple-600/10 hover:bg-purple-600/20 text-purple-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="copy" class="w-4 h-4 text-purple-400"></i>
                            <span>📋 Duplikat (Copy) Gudang Ini</span>
                        </button>
                        <button 
                            @click="openEditModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 hover:bg-blue-600/20 hover:text-blue-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="edit-3" class="w-4 h-4 text-blue-500"></i>
                            <span>Edit Details Gudang</span>
                        </button>
                        <button 
                            @click="deleteSelectedLocation(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 hover:bg-rose-600/20 hover:text-rose-400 rounded-xl transition flex items-center gap-2.5 font-bold text-rose-400"
                        >
                            <i data-lucide="trash-2" class="w-4 h-4 text-rose-500"></i>
                            <span>Hapus Gudang dari Canvas</span>
                        </button>
                    </div>
                </template>

                <!-- Actions when Right Clicked on a SINGLE RAK object -->
                <template x-if="selectedLocations.length <= 1 && contextMenuTarget && contextMenuTarget.location_type !== 'room'">
                    <div class="space-y-1 border-b border-slate-800 pb-1 mb-1">
                        <button 
                            @click="toggleLockLocation(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-amber-500/10 hover:bg-amber-500/20 rounded-xl transition flex items-center gap-2.5 font-bold"
                            :class="contextMenuTarget?.is_locked !== false ? 'text-amber-400' : 'text-emerald-400'"
                        >
                            <i data-lucide="lock" class="w-4 h-4"></i>
                            <span x-text="contextMenuTarget?.is_locked !== false ? '🔓 Buka Kunci (Unlock) Rak' : '🔒 Kunci (Lock) Rak'"></span>
                        </button>
                        <button 
                            @click="rotateLocation(contextMenuTarget, 90); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="rotate-cw" class="w-4 h-4 text-indigo-400"></i>
                            <span>🔄 Putar (Rotate) Rak 90°</span>
                        </button>
                        <button 
                            @click="duplicateRack(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-purple-600/10 hover:bg-purple-600/20 text-purple-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="copy" class="w-4 h-4 text-purple-400"></i>
                            <span>📋 Duplikat (Copy) Rak Ini</span>
                        </button>
                        <button 
                            @click="openMoveModal(contextMenuTarget); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="truck" class="w-4 h-4 text-amber-500"></i>
                            <span>🚚 Move / Pindahkan Rak Ke Gudang...</span>
                        </button>
                        <button 
                            @click="openEditModal(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 hover:bg-blue-600/20 hover:text-blue-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="edit-3" class="w-4 h-4 text-blue-500"></i>
                            <span>Edit Details, Nama & Warna</span>
                        </button>
                        <button 
                            @click="deleteSelectedLocation(); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 hover:bg-rose-600/20 hover:text-rose-400 rounded-xl transition flex items-center gap-2.5 font-bold text-rose-400"
                        >
                            <i data-lucide="trash-2" class="w-4 h-4 text-rose-500"></i>
                            <span>Hapus Rak dari Canvas</span>
                        </button>
                    </div>
                </template>

                <!-- Actions when Right Clicked on Empty Canvas Space -->
                <template x-if="selectedLocations.length <= 1 && !contextMenuTarget">
                    <div class="space-y-1">
                        <button 
                            @click="openAddRoomModal(clickedCanvasX, clickedCanvasY); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 hover:bg-emerald-600/20 hover:text-emerald-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="square" class="w-4 h-4 text-emerald-500"></i>
                            <span>Tambah Gudang (Square)</span>
                        </button>
                        
                        <button 
                            @click="openAddRackModal(clickedCanvasX, clickedCanvasY); contextMenuOpen = false;" 
                            type="button" 
                            class="w-full text-left px-3 py-2 hover:bg-blue-600/20 hover:text-blue-400 rounded-xl transition flex items-center gap-2.5 font-bold"
                        >
                            <i data-lucide="rectangle-horizontal" class="w-4 h-4 text-blue-500"></i>
                            <span>Tambah Rak (Rectangle)</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Drawer Inspector Side Panel (1 Col) -->
        <div :class="isFullscreen ? 'space-y-6 w-full lg:w-96 flex-shrink-0 max-h-full overflow-y-auto pr-1' : 'space-y-6'">
            <!-- Selected Object Inspector Card -->
            <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-6">
                <!-- If No Location Selected -->
                <div x-show="!selectedLocation" class="py-12 text-center space-y-3">
                    <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-2xl w-12 h-12 mx-auto flex items-center justify-center">
                        <i data-lucide="mouse-pointer-click" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Pilih Object Gudang / Rak Pada Canvas</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                        Klik pada object Gudang atau Rak untuk melihat detail, menggeser posisi, mengubah ukuran (resize), mengganti nama & warna, atau menghapus object.
                    </p>
                </div>

                <!-- If Location Selected -->
                <div x-show="selectedLocation" class="space-y-6" x-cloak>
                    <!-- If MULTIPLE Locations Selected (Multi-Selection Batch Actions Card) -->
                    <template x-if="selectedLocations.length > 1">
                        <div class="p-4 bg-indigo-500/10 border border-indigo-500/30 rounded-2xl space-y-3 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-indigo-400 flex items-center gap-1.5">
                                    <i data-lucide="layers" class="w-4 h-4 text-indigo-400"></i>
                                    ✨ Batch Setup (Multi-Select)
                                </span>
                                <span class="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 text-[10px] font-mono font-bold rounded-lg" x-text="selectedLocations.length + ' Objek'"></span>
                            </div>
                            <p class="text-[11px] text-slate-400 font-medium">
                                Ubah properti seluruh objek yang terpilih secara bersamaan:
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <button @click="bulkToggleLockLocations(false)" type="button" class="px-3 py-2 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 text-xs font-bold rounded-xl border border-emerald-500/30 transition flex items-center justify-center gap-1.5">
                                    <i data-lucide="unlock" class="w-3.5 h-3.5"></i> 🔓 Buka Kunci
                                </button>
                                <button @click="bulkToggleLockLocations(true)" type="button" class="px-3 py-2 bg-amber-500/20 hover:bg-amber-500/30 text-amber-400 text-xs font-bold rounded-xl border border-amber-500/30 transition flex items-center justify-center gap-1.5">
                                    <i data-lucide="lock" class="w-3.5 h-3.5"></i> 🔒 Kunci Semua
                                </button>
                                <button @click="openBatchResizeModal()" type="button" class="px-3 py-2 bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 text-xs font-bold rounded-xl border border-blue-500/30 transition flex items-center justify-center gap-1.5">
                                    <i data-lucide="scaling" class="w-3.5 h-3.5"></i> 📐 Ubah Ukuran
                                </button>
                                <button @click="openBatchDeptModal()" type="button" class="px-3 py-2 bg-purple-600/20 hover:bg-purple-600/30 text-purple-400 text-xs font-bold rounded-xl border border-purple-500/30 transition flex items-center justify-center gap-1.5">
                                    <i data-lucide="building-2" class="w-3.5 h-3.5"></i> 🏢 Set Dept
                                </button>
                                <button @click="openBatchColorModal()" type="button" class="px-3 py-2 bg-pink-600/20 hover:bg-pink-600/30 text-pink-400 text-xs font-bold rounded-xl border border-pink-500/30 transition flex items-center justify-center gap-1.5 col-span-2">
                                    <i data-lucide="palette" class="w-3.5 h-3.5"></i> 🎨 Set Warna Massal
                                </button>
                                <button @click="alignGroupHorizontally()" type="button" class="px-3 py-2 bg-teal-600/20 hover:bg-teal-600/30 text-teal-400 text-xs font-bold rounded-xl border border-teal-500/30 transition flex items-center justify-center gap-1.5">
                                    <i data-lucide="align-horizontal-space-around" class="w-3.5 h-3.5"></i> ↔️ Rapatkan Sisi Lebar
                                </button>
                                <button @click="alignGroupVertically()" type="button" class="px-3 py-2 bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-400 text-xs font-bold rounded-xl border border-cyan-500/30 transition flex items-center justify-center gap-1.5">
                                    <i data-lucide="align-vertical-space-around" class="w-3.5 h-3.5"></i> ↕️ Rapatkan Sisi Panjang
                                </button>
                            </div>
                        </div>
                    </template>
                    <!-- Header & Type Badge -->
                    <div class="flex items-start justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-md tracking-wider text-white" :class="selectedLocation?.location_type === 'room' ? 'bg-emerald-600' : 'bg-blue-600'" x-text="selectedLocation?.location_type === 'room' ? 'OBJECT GUDANG / SEKTOR' : 'OBJECT RAK'"></span>
                                <template x-if="selectedLocation?.custom_color">
                                    <span class="w-3 h-3 rounded-full border border-white/50" :style="{ backgroundColor: selectedLocation?.custom_color }"></span>
                                </template>
                            </div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white" x-text="selectedLocation?.rack_code"></h2>
                                <!-- Edit Pencil Icon Button next to title -->
                                <button @click="openEditModal()" type="button" title="Edit Detail Object (Nama, Sektor, Warna, Kapasitas, Geometri)" class="px-2 py-1 bg-blue-600/10 hover:bg-blue-600/20 text-blue-600 dark:text-blue-400 rounded-lg transition flex items-center gap-1 text-xs font-bold border border-blue-500/20 group">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5 text-blue-500 group-hover:scale-110 transition"></i>
                                    <span>Edit</span>
                                </button>
                            </div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium" x-text="'Sektor: ' + (selectedLocation?.room_sector || 'Umum')"></span>
                            </div>
                        </div>
                        <button @click="selectedLocation = null; renderCanvas()" type="button" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Lock Status Card (For Gudang Room & Rak) -->
                    <template x-if="selectedLocation">
                        <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="text-slate-500 text-[11px] font-medium block" x-text="selectedLocation?.location_type === 'room' ? 'Status Gembok Gudang:' : 'Status Gembok Rak:'"></span>
                                <span class="font-extrabold text-xs flex items-center gap-1 mt-0.5" :class="selectedLocation?.is_locked !== false ? 'text-rose-500' : 'text-emerald-500'">
                                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                    <span x-text="selectedLocation?.is_locked !== false ? 'Terkunci (Locked 🔒)' : 'Terbuka (Unlocked 🔓)'"></span>
                                </span>
                            </div>
                            <button @click="toggleLockLocation(selectedLocation)" type="button" class="px-3 py-1.5 rounded-xl font-bold text-xs shadow transition text-white" :class="selectedLocation?.is_locked !== false ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-rose-600 hover:bg-rose-500'">
                                <span x-text="selectedLocation?.is_locked !== false ? '🔓 Buka Kunci' : '🔒 Kunci Object'"></span>
                            </button>
                        </div>
                    </template>

                    <!-- Geometri Canvas Info -->
                    <div class="relative grid grid-cols-2 gap-2 text-xs p-3 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800 font-mono group">
                        <div><span class="text-slate-400">Posisi X,Y:</span> <span class="font-bold text-slate-900 dark:text-white" x-text="selectedLocation?.canvas_x + ', ' + selectedLocation?.canvas_y"></span></div>
                        <div class="flex items-center justify-between">
                            <div><span class="text-slate-400">Ukuran WxH:</span> <span class="font-bold text-amber-600 dark:text-amber-400" x-text="selectedLocation?.canvas_width + ' x ' + selectedLocation?.canvas_height + ' px'"></span></div>
                            <button @click="openEditModal()" type="button" title="Edit Dimensi & Posisi Geometri" class="p-1 text-slate-400 hover:text-blue-500 transition">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Capacity Progress Bar (If Rack) -->
                    <template x-if="selectedLocation?.location_type !== 'room'">
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center text-xs font-bold">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-slate-600 dark:text-slate-400">Kapasitas Terisi:</span>
                                    <button @click="openEditModal()" type="button" title="Edit Kapasitas Box" class="p-0.5 text-slate-400 hover:text-blue-500 transition">
                                        <i data-lucide="pencil" class="w-3 h-3"></i>
                                    </button>
                                </div>
                                <span :style="{ color: selectedLocation?.status_color }" x-text="(selectedLocation?.current_box_count || 0) + ' / ' + (selectedLocation?.box_capacity || 0) + ' Box (' + (selectedLocation?.capacity_percentage || 0) + '%)'"></span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-900 rounded-full h-3 overflow-hidden border border-slate-200 dark:border-slate-800">
                                <div class="h-full transition-all duration-300 rounded-full" :style="{ width: (selectedLocation?.capacity_percentage || 0) + '%', backgroundColor: selectedLocation?.status_color }"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Department Allocation & Booking Info -->
                    <div class="space-y-3 text-xs">
                        <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800">
                            <div class="flex items-center justify-between mb-0.5">
                                <span class="text-slate-500 dark:text-slate-400 font-medium">Alokasi Departemen:</span>
                                <button @click="openEditModal()" type="button" title="Edit Alokasi Departemen" class="p-1 text-slate-400 hover:text-blue-500 transition">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            <span class="font-extrabold text-slate-900 dark:text-white text-sm" x-text="selectedLocation?.assigned_department ? (selectedLocation?.assigned_department.code + ' - ' + selectedLocation?.assigned_department.name) : 'Umum (Bebas)'"></span>
                        </div>

                        <template x-if="selectedLocation?.is_booked">
                            <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-2xl space-y-1">
                                <span class="text-amber-700 dark:text-amber-400 font-extrabold block">Status: Reserved Booking</span>
                                <p class="text-slate-700 dark:text-slate-300 font-medium" x-text="selectedLocation?.booking_notes || 'Alokasi booking berkas'"></p>
                                <span class="text-[10px] text-slate-500 block" x-text="'Oleh: ' + (selectedLocation?.booked_by_user || 'User')"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Archives List inside Selected Location -->
                    <template x-if="selectedLocation?.location_type !== 'room'">
                        <div class="space-y-3 pt-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block" x-text="'Daftar Box Arsip Tersimpan (' + (selectedLocation?.archives?.length || 0) + '):'"></span>
                            
                            <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                                <template x-for="arc in selectedLocation?.archives || []" :key="arc.id">
                                    <div @click="openDocDetail(arc)" class="p-3 bg-slate-50 dark:bg-slate-900/80 hover:bg-amber-500/10 dark:hover:bg-amber-500/20 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer transition space-y-1">
                                        <span class="font-mono text-xs text-amber-600 dark:text-amber-400 font-extrabold block" x-text="arc.box_number || 'DRAFT'"></span>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-xs line-clamp-1" x-text="arc.title"></h4>
                                        <div class="flex justify-between text-[10px] text-slate-500 font-medium">
                                            <span x-text="'Dept: ' + arc.department"></span>
                                            <span x-text="'Exp: ' + arc.retention_expiry_date"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!selectedLocation?.archives || selectedLocation?.archives.length === 0">
                                    <div class="p-4 text-center text-xs text-slate-400 font-medium bg-slate-50 dark:bg-slate-900/50 rounded-xl">
                                        Belum ada box arsip yang dimasukkan ke rak ini.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-2">
                        <template x-if="selectedLocation?.location_type === 'room'">
                            <button @click="addRackInsideRoom(selectedLocation, selectedLocation.canvas_x + 20, selectedLocation.canvas_y + 40)" type="button" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center gap-2">
                                <i data-lucide="plus-square" class="w-4 h-4"></i> + Tambah Rak di Dalam Sektor Ini
                            </button>
                        </template>

                        <template x-if="selectedLocation?.location_type === 'room'">
                            <button @click="duplicateRack(selectedLocation)" type="button" class="w-full py-2.5 bg-purple-600/20 hover:bg-purple-600/30 text-purple-700 dark:text-purple-300 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2 border border-purple-500/30">
                                <i data-lucide="copy" class="w-4 h-4 text-purple-500"></i> 📋 Duplikat (Copy) Gudang Ini
                            </button>
                        </template>

                        <template x-if="selectedLocation?.location_type !== 'room'">
                            <button @click="duplicateRack(selectedLocation)" type="button" class="w-full py-2.5 bg-purple-600/20 hover:bg-purple-600/30 text-purple-700 dark:text-purple-300 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2 border border-purple-500/30">
                                <i data-lucide="copy" class="w-4 h-4 text-purple-500"></i> 📋 Duplikat (Copy) Rak Ini
                            </button>
                        </template>

                        <template x-if="selectedLocation?.location_type !== 'room'">
                            <button @click="openMoveModal(selectedLocation)" type="button" class="w-full py-2.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-700 dark:text-amber-400 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2 border border-amber-500/30">
                                <i data-lucide="truck" class="w-4 h-4 text-amber-500"></i> 🚚 Pindahkan (Move) Rak Ke Gudang...
                            </button>
                        </template>

                        <template x-if="selectedLocation?.location_type !== 'room' && !selectedLocation?.is_booked">
                            <button @click="openBookingModal()" type="button" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow transition flex items-center justify-center gap-2">
                                <i data-lucide="bookmark-plus" class="w-4 h-4"></i> Booking Slot Tempat Arsip
                            </button>
                        </template>

                        <template x-if="selectedLocation?.is_booked">
                            <button @click="unbookLocation()" type="button" class="w-full py-2.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-700 dark:text-rose-300 border border-rose-500/30 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2">
                                <i data-lucide="bookmark-x" class="w-4 h-4"></i> Lepas Status Booking
                            </button>
                        </template>

                        <button @click="rotateLocation(selectedLocation, 90)" type="button" class="w-full py-2.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2 border border-indigo-500/30">
                            <i data-lucide="rotate-cw" class="w-4 h-4 text-indigo-500"></i> 🔄 Putar (Rotate) Object 90° <span class="text-[10px] opacity-75 font-mono">(Key: R)</span>
                        </button>

                        <button @click="openEditModal()" type="button" class="w-full py-2.5 bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2">
                            <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Nama, Warna & Geometri Object
                        </button>

                        <button @click="deleteSelectedLocation()" type="button" class="w-full py-2 bg-rose-600/10 hover:bg-rose-600/20 text-rose-600 dark:text-rose-400 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Object Dari Canvas <span class="text-[10px] opacity-75 font-mono">(Key: Delete)</span>
                        </button>
                    </div>
                </div>
            </div>

    <!-- Modal 1: Document Detail Inspector -->
    <div x-show="docModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Detail Berkas Dokumen</span>
                <button @click="docModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-500 block mb-0.5">Judul Dokumen:</span>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white" x-text="selectedDoc?.title"></h3>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block">Kode Box:</span>
                        <span class="font-mono font-bold text-amber-600 dark:text-amber-400 text-sm" x-text="selectedDoc?.box_number || 'DRAFT'"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block">Perusahaan:</span>
                        <span class="font-bold text-slate-900 dark:text-white" x-text="selectedDoc?.company_name"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block">Jenis Dokumen:</span>
                        <span class="font-bold text-slate-900 dark:text-white" x-text="selectedDoc?.document_type"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block">Periode (YY-MM):</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400" x-text="selectedDoc?.period_yy_mm"></span>
                    </div>
                </div>

                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-500 block">Masa Simpan Expiry:</span>
                    <span class="font-extrabold text-rose-600 dark:text-rose-400" x-text="selectedDoc?.retention_expiry_date + ' (' + selectedDoc?.retention_years + ' Tahun Retention)'"></span>
                </div>

                <!-- Scan Buttons -->
                <div class="flex items-center gap-2 pt-2">
                    <template x-if="selectedDoc?.scan_input_form">
                        <a :href="selectedDoc.scan_input_form" target="_blank" class="px-3 py-1.5 bg-amber-500 text-slate-950 font-bold rounded-lg text-xs">Scan Form Input</a>
                    </template>
                    <template x-if="selectedDoc?.scan_approval_input">
                        <a :href="selectedDoc.scan_approval_input" target="_blank" class="px-3 py-1.5 bg-blue-600 text-white font-bold rounded-lg text-xs">Scan Approval</a>
                    </template>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button @click="docModalOpen = false" type="button" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Booking Slot Rak -->
    <div x-show="bookingModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="bookmark-plus" class="w-5 h-5 text-amber-500"></i>
                Form Booking Tempat Arsip
            </h3>

            <form @submit.prevent="submitBooking()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Pilih Departemen Pemohon <span class="text-rose-500">*</span></label>
                    <select x-model="bookingForm.department_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-medium">
                        <option value="">-- Pilih Departemen --</option>
                        <template x-for="dept in departments" :key="dept.id">
                            <option :value="dept.id" x-text="dept.code + ' - ' + dept.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Catatan & Rencana Pengiriman Box <span class="text-rose-500">*</span></label>
                    <textarea x-model="bookingForm.booking_notes" rows="3" required placeholder="Contoh: Booking slot untuk pengiriman 15 Box Faktur Pajak Q1 2026 Dept Keuangan..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 font-medium"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="bookingModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-400 text-xs rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded-xl shadow-lg">Submit Booking</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Insert New Object (Gudang Square / Rak Rectangle) -->
    <div x-show="objectModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-5 h-5 text-emerald-500"></i>
                <span x-text="objectForm.location_type === 'room' ? 'Tambah Object Gudang Baru (Square)' : 'Tambah Object Rak Baru (Rectangle)'"></span>
            </h3>

            <form @submit.prevent="submitCreateObject()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">
                        <span x-text="objectForm.location_type === 'room' ? 'Nama Gudang / Sektor' : 'Nomor / Awalan Kode Rak'"></span> <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" x-model="objectForm.rack_code" required placeholder="e.g. GUDANG PRODUKSI atau RAK-R7" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-bold">
                </div>

                <template x-if="objectForm.location_type === 'rack'">
                    <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl space-y-1">
                        <label class="block text-xs font-bold uppercase text-emerald-700 dark:text-emerald-400 mb-1 flex items-center justify-between">
                            <span>Jumlah Rak Yang Dibuat (Batch Multi-Add)</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="number" x-model="objectForm.quantity" min="1" max="50" required class="w-24 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-mono font-bold">
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Buat 1 s/d 50 rak sekaligus secara otomatis tersusun rapi.</span>
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Kode Sektor Ruangan</label>
                        <input type="text" x-model="objectForm.room_sector" placeholder="e.g. R7, GA, IT" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-bold">
                    </div>

                    <template x-if="objectForm.location_type === 'rack'">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Kapasitas Box / Rak</label>
                            <input type="number" x-model="objectForm.box_capacity" min="1" max="5000" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-bold">
                        </div>
                    </template>
                </div>

                <!-- Geometri Dimensions -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Lebar (Width px)</label>
                        <input type="number" x-model="objectForm.canvas_width" required min="20" max="800" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Tinggi (Height px)</label>
                        <input type="number" x-model="objectForm.canvas_height" required min="20" max="800" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-mono font-bold">
                    </div>
                </div>

                <!-- Color Preset Picker -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Pilih Warna Object</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <template x-for="c in colorPresets" :key="c">
                            <button @click="objectForm.custom_color = c" type="button" class="w-7 h-7 rounded-xl border-2 transition" :class="objectForm.custom_color === c ? 'border-white scale-110 shadow-lg' : 'border-transparent opacity-80 hover:opacity-100'" :style="{ backgroundColor: c }"></button>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="objectModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-400 text-xs rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow-lg">Tambah Object Ke Canvas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4: Edit Object (Gudang / Rak) -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="edit-3" class="w-5 h-5 text-blue-500"></i>
                Edit Details, Nama & Warna Object
            </h3>

            <form @submit.prevent="submitEdit()" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Nama / Kode Object</label>
                        <input type="text" x-model="editForm.rack_code" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Kode Sektor Ruangan</label>
                        <input type="text" x-model="editForm.room_sector" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Kode Baris / Shelf</label>
                        <input type="text" x-model="editForm.shelf_code" placeholder="e.g. BARIS-01" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-medium">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Kapasitas Maksimal Box</label>
                        <input type="number" x-model="editForm.box_capacity" min="1" max="5000" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-bold">
                    </div>
                </div>

                <!-- Dimensions & Orientation -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Lebar (W px)</label>
                        <input type="number" x-model="editForm.canvas_width" required min="20" max="800" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Tinggi (H px)</label>
                        <input type="number" x-model="editForm.canvas_height" required min="20" max="800" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Orientasi</label>
                        <select x-model="editForm.orientation" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-medium">
                            <option value="horizontal">Horizontal</option>
                            <option value="vertical">Vertical</option>
                        </select>
                    </div>
                </div>

                <!-- Custom Color Picker -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Warna Custom Object (Klik untuk pilih)</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <button @click="editForm.custom_color = ''" type="button" class="px-2.5 py-1 text-[11px] rounded-lg font-bold border" :class="!editForm.custom_color ? 'bg-slate-800 text-white border-amber-400' : 'bg-slate-100 dark:bg-slate-900 text-slate-500 border-slate-300'">Auto (Status)</button>
                        <template x-for="c in colorPresets" :key="c">
                            <button @click="editForm.custom_color = c" type="button" class="w-7 h-7 rounded-xl border-2 transition" :class="editForm.custom_color === c ? 'border-white scale-110 shadow-lg' : 'border-transparent opacity-80 hover:opacity-100'" :style="{ backgroundColor: c }"></button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Alokasi Khusus Departemen (Opsional)</label>
                    <select x-model="editForm.assigned_department_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-medium">
                        <option value="">-- Umum (Bebas) --</option>
                        <template x-for="dept in departments" :key="dept.id">
                            <option :value="dept.id" x-text="dept.code + ' - ' + dept.name"></option>
                        </template>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-400 text-xs rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-black rounded-xl shadow-lg">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 5: Move Rak Ke Gudang / Sektor -->
    <div x-show="moveModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="truck" class="w-5 h-5 text-amber-500"></i>
                    Pindahkan Rak Ke Gudang / Sektor
                </h3>
                <button @click="moveModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Pilih Gudang / Sektor tujuan untuk memindahkan posisi rak <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="selectedLocation?.rack_code"></span>:
            </p>

            <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                <template x-for="room in roomObjects" :key="room.id">
                    <div 
                        @click="moveRackToRoom(selectedLocation, room)" 
                        class="p-3 bg-slate-50 dark:bg-slate-900/80 hover:bg-amber-500/10 dark:hover:bg-amber-500/20 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer transition flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-2.5">
                            <span class="w-4 h-4 rounded-lg flex-shrink-0" :style="{ backgroundColor: room.custom_color || '#3b82f6' }"></span>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-xs group-hover:text-amber-500 transition" x-text="room.rack_code"></h4>
                                <span class="text-[10px] text-slate-500 block" x-text="'Sektor: ' + (room.room_sector || room.rack_code) + ' (' + room.canvas_width + 'x' + room.canvas_height + ' px)'"></span>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-extrabold text-[11px] rounded-lg border border-amber-500/20 group-hover:bg-amber-500 group-hover:text-slate-950 transition">
                            Pindahkan &rarr;
                        </span>
                    </div>
                </template>
                <template x-if="roomObjects.length === 0">
                    <div class="p-4 text-center text-xs text-slate-400 font-medium bg-slate-50 dark:bg-slate-900/50 rounded-xl">
                        Belum ada objek Gudang/Sektor (Square). Silakan tambah Gudang terlebih dahulu.
                    </div>
                </template>
            </div>

            <div class="flex justify-end pt-2">
                <button @click="moveModalOpen = false" type="button" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-400 text-xs rounded-xl font-bold">Batal</button>
            </div>
        </div>
    </div>

    <!-- Modal 5: Batch Resize Object (Width & Height Massal) -->
    <div x-show="batchResizeModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="scaling" class="w-5 h-5 text-blue-500"></i>
                <span>Ubah Ukuran Massal (Width & Height)</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Mengubah ukuran lebar dan tinggi secara serentak untuk <span class="font-bold text-indigo-400 font-mono" x-text="selectedLocations.length + ' object terseleksi'"></span>:
            </p>

            <form @submit.prevent="submitBatchResize()" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Lebar Baru (Width px) <span class="text-rose-500">*</span></label>
                        <input type="number" x-model="batchResizeForm.canvas_width" required min="20" max="800" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-mono font-bold text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Tinggi Baru (Height px) <span class="text-rose-500">*</span></label>
                        <input type="number" x-model="batchResizeForm.canvas_height" required min="20" max="800" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-mono font-bold text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- Quick Preset Sizes -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Preset Ukuran Standar:</label>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" @click="batchResizeForm.canvas_width = 25; batchResizeForm.canvas_height = 140" class="px-2.5 py-1 bg-slate-100 dark:bg-slate-900 hover:bg-blue-600 hover:text-white rounded-lg text-[11px] font-mono font-bold transition">25 x 140 (Vertikal Std)</button>
                        <button type="button" @click="batchResizeForm.canvas_width = 140; batchResizeForm.canvas_height = 25" class="px-2.5 py-1 bg-slate-100 dark:bg-slate-900 hover:bg-blue-600 hover:text-white rounded-lg text-[11px] font-mono font-bold transition">140 x 25 (Horizontal Std)</button>
                        <button type="button" @click="batchResizeForm.canvas_width = 25; batchResizeForm.canvas_height = 180" class="px-2.5 py-1 bg-slate-100 dark:bg-slate-900 hover:bg-blue-600 hover:text-white rounded-lg text-[11px] font-mono font-bold transition">25 x 180 (Vertikal Pjg)</button>
                        <button type="button" @click="batchResizeForm.canvas_width = 180; batchResizeForm.canvas_height = 25" class="px-2.5 py-1 bg-slate-100 dark:bg-slate-900 hover:bg-blue-600 hover:text-white rounded-lg text-[11px] font-mono font-bold transition">180 x 25 (Horizontal Pjg)</button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="batchResizeModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-400 text-xs rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-extrabold rounded-xl shadow-lg flex items-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i> Terapkan Ke Semua Object
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 6: Batch Department Assignment (Alokasi Departemen Massal) -->
    <div x-show="batchDeptModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="building-2" class="w-5 h-5 text-purple-500"></i>
                <span>Set Alokasi Departemen Massal</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Mengubah alokasi departemen secara serentak untuk <span class="font-bold text-indigo-400 font-mono" x-text="selectedLocations.length + ' object terseleksi'"></span>:
            </p>

            <form @submit.prevent="submitBatchDepartment()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Pilih Departemen <span class="text-rose-500">*</span></label>
                    <select x-model="batchDeptForm.assigned_department_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white font-medium">
                        <option value="">-- Umum / Bebas (Tidak ada alokasi khusus) --</option>
                        <template x-for="dept in departments" :key="dept.id">
                            <option :value="dept.id" x-text="dept.code + ' - ' + dept.name"></option>
                        </template>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="batchDeptModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-400 text-xs rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-extrabold rounded-xl shadow-lg flex items-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i> Terapkan Alokasi Dept
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 7: Batch Custom Color (Warna Massal) -->
    <div x-show="batchColorModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="palette" class="w-5 h-5 text-pink-500"></i>
                <span>Set Warna Custom Massal</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Pilih warna custom secara serentak untuk <span class="font-bold text-indigo-400 font-mono" x-text="selectedLocations.length + ' object terseleksi'"></span>:
            </p>

            <form @submit.prevent="submitBatchColor()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-2">Pilih Preset Warna:</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="color in colorPresets" :key="color">
                            <button 
                                type="button" 
                                @click="batchColorForm.custom_color = color" 
                                class="w-8 h-8 rounded-xl border-2 transition transform hover:scale-110" 
                                :class="batchColorForm.custom_color === color ? 'border-white ring-2 ring-indigo-500 scale-110' : 'border-transparent'" 
                                :style="{ backgroundColor: color }"
                            ></button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Atau Kode Hex Warna Custom:</label>
                    <input type="text" x-model="batchColorForm.custom_color" placeholder="#3b82f6" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-mono font-bold text-slate-900 dark:text-white">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="batchColorModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-400 text-xs rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-pink-600 hover:bg-pink-500 text-white text-xs font-extrabold rounded-xl shadow-lg flex items-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i> Terapkan Warna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Setup Dimensi Canvas Workspace -->
    <div x-show="canvasResizeModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="canvasResizeModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                    <i data-lucide="scaling" class="w-5 h-5 text-cyan-400"></i>
                    📐 Ubah Dimensi Canvas Workspace
                </h3>
                <button @click="canvasResizeModalOpen = false" type="button" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="submitCanvasResize()" class="space-y-4 text-xs font-semibold text-slate-300">
                <p class="text-slate-400 text-xs font-medium">
                    Atur ukuran area lembar kerja (Workspace Canvas 2D) sesuai luas denah fisik gudang Anda:
                </p>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-bold mb-1">Lebar Canvas (Width px):</label>
                        <input type="number" min="600" max="4000" step="50" x-model.number="canvasResizeForm.width" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl font-mono text-white text-xs font-bold focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-bold mb-1">Tinggi Canvas (Height px):</label>
                        <input type="number" min="500" max="3000" step="50" x-model.number="canvasResizeForm.height" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl font-mono text-white text-xs font-bold focus:border-cyan-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 font-bold mb-1.5">Preset Ukuran Standar:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="setPresetCanvasSize(950, 750)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl font-mono font-bold transition text-center border border-slate-700/60">
                            950 × 750 px (Standar)
                        </button>
                        <button type="button" @click="setPresetCanvasSize(1200, 900)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl font-mono font-bold transition text-center border border-slate-700/60">
                            1200 × 900 px (Sedang)
                        </button>
                        <button type="button" @click="setPresetCanvasSize(1600, 1000)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl font-mono font-bold transition text-center border border-slate-700/60">
                            1600 × 1000 px (Luas)
                        </button>
                        <button type="button" @click="setPresetCanvasSize(2000, 1400)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl font-mono font-bold transition text-center border border-slate-700/60">
                            2000 × 1400 px (XL Extra)
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="canvasResizeModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-400 hover:text-white rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-extrabold rounded-xl shadow-lg flex items-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i> Terapkan Dimensi Canvas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Toast Notification Banner -->
    <div 
        x-show="toastOpen" 
        x-cloak 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-6 right-6 z-[99999] bg-slate-900/95 border border-amber-500/40 text-slate-100 font-bold text-xs px-4 py-3 rounded-2xl shadow-2xl flex items-center gap-2.5 backdrop-blur-md"
    >
        <i data-lucide="info" class="w-4 h-4 text-amber-400"></i>
        <span x-text="toastMessage"></span>
    </div>
    </div>
</div>

@push('scripts')
<script>
function warehouseCanvasApp() {
    return {
        loading: true,
        locations: [],
        departments: [],
        filterDepartment: '',
        selectedLocations: [],
        copiedLocations: [],
        draggedGroupPositions: [],
        activeGuideLines: [],

        get selectedLocation() {
            return this.selectedLocations.length > 0 ? this.selectedLocations[this.selectedLocations.length - 1] : null;
        },
        set selectedLocation(val) {
            if (!val) {
                this.selectedLocations = [];
            } else {
                if (!this.selectedLocations.some(l => l.id === val.id)) {
                    this.selectedLocations = [val];
                }
            }
        },
        copiedLocation: null,
        toastMessage: '',
        toastOpen: false,
        toastTimer: null,
        scale: 1.0,
        isFullscreen: false,
        canvasWidth: parseInt(localStorage.getItem('wh_canvas_w')) || 950,
        canvasHeight: parseInt(localStorage.getItem('wh_canvas_h')) || 750,
        canvasResizeModalOpen: false,
        canvasResizeForm: { width: 950, height: 750 },
        isResizingCanvas: false,
        resizeCanvasStartX: 0,
        resizeCanvasStartY: 0,
        initialCanvasW: 950,
        initialCanvasH: 750,

        applyCanvasSize(w, h) {
            const validW = Math.max(600, Math.min(4000, parseInt(w) || 950));
            const validH = Math.max(500, Math.min(3000, parseInt(h) || 750));

            this.canvasWidth = validW;
            this.canvasHeight = validH;

            if (this.canvas) {
                this.canvas.width = validW;
                this.canvas.height = validH;
            }

            try {
                localStorage.setItem('wh_canvas_w', validW);
                localStorage.setItem('wh_canvas_h', validH);
            } catch (e) {}

            this.renderCanvas();
        },

        openCanvasResizeModal() {
            this.canvasResizeForm = {
                width: this.canvasWidth,
                height: this.canvasHeight
            };
            this.canvasResizeModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        submitCanvasResize() {
            const w = parseInt(this.canvasResizeForm.width);
            const h = parseInt(this.canvasResizeForm.height);
            if (isNaN(w) || isNaN(h) || w < 600 || h < 500) {
                alert('Perhatian: Ukuran minimal canvas adalah Lebar 600px dan Tinggi 500px!');
                return;
            }
            this.applyCanvasSize(w, h);
            this.canvasResizeModalOpen = false;
            this.showToast(`📐 Ukuran Canvas berhasil diubah menjadi ${w} x ${h} px.`);
        },

        setPresetCanvasSize(w, h) {
            this.canvasResizeForm.width = w;
            this.canvasResizeForm.height = h;
        },

        startCanvasResize(e) {
            this.isResizingCanvas = true;
            this.resizeCanvasStartX = e.clientX;
            this.resizeCanvasStartY = e.clientY;
            this.initialCanvasW = this.canvasWidth;
            this.initialCanvasH = this.canvasHeight;

            const onMouseMove = (moveEv) => {
                if (!this.isResizingCanvas) return;
                const deltaX = Math.round((moveEv.clientX - this.resizeCanvasStartX) / this.scale);
                const deltaY = Math.round((moveEv.clientY - this.resizeCanvasStartY) / this.scale);

                const newW = Math.max(600, Math.min(4000, this.initialCanvasW + deltaX));
                const newH = Math.max(500, Math.min(3000, this.initialCanvasH + deltaY));

                this.applyCanvasSize(newW, newH);
            };

            const onMouseUp = () => {
                if (this.isResizingCanvas) {
                    this.isResizingCanvas = false;
                    window.removeEventListener('mousemove', onMouseMove);
                    window.removeEventListener('mouseup', onMouseUp);
                    this.showToast(`📐 Ukuran Canvas diset ke ${this.canvasWidth} x ${this.canvasHeight} px.`);
                }
            };

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        },

        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
            const container = document.getElementById('canvasWorkspaceWrapper');
            
            if (this.isFullscreen) {
                if (container && container.requestFullscreen) {
                    container.requestFullscreen().catch(err => {
                        console.log("Browser fullscreen error:", err);
                    });
                }
                this.showToast('📺 Mode Canvas 1 Layar Penuh (Fullscreen) Diaktifkan. Tekan Esc untuk Keluar.');
            } else {
                if (document.fullscreenElement && document.exitFullscreen) {
                    document.exitFullscreen().catch(err => {
                        console.log("Browser exit fullscreen error:", err);
                    });
                }
                this.showToast('📺 Keluar dari Mode Layar Penuh');
            }

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                this.renderCanvas();
            });
        },

        showToast(msg) {
            this.toastMessage = msg;
            this.toastOpen = true;
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => {
                this.toastOpen = false;
            }, 3000);
        },
        
        docModalOpen: false,
        selectedDoc: null,

        bookingModalOpen: false,
        bookingForm: { department_id: '', booking_notes: '' },

        moveModalOpen: false,

        batchResizeModalOpen: false,
        batchResizeForm: { canvas_width: 25, canvas_height: 140 },

        batchDeptModalOpen: false,
        batchDeptForm: { assigned_department_id: '' },

        batchColorModalOpen: false,
        batchColorForm: { custom_color: '#3b82f6' },

        openBatchResizeModal() {
            if (this.selectedLocations.length === 0) return;
            const refObj = this.selectedLocation || this.selectedLocations[0];
            this.batchResizeForm = {
                canvas_width: refObj.canvas_width || 25,
                canvas_height: refObj.canvas_height || 140,
            };
            this.batchResizeModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        openBatchDeptModal() {
            if (this.selectedLocations.length === 0) return;
            const refObj = this.selectedLocation || this.selectedLocations[0];
            this.batchDeptForm = {
                assigned_department_id: refObj.assigned_department_id || '',
            };
            this.batchDeptModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        openBatchColorModal() {
            if (this.selectedLocations.length === 0) return;
            const refObj = this.selectedLocation || this.selectedLocations[0];
            this.batchColorForm = {
                custom_color: refObj.custom_color || '#3b82f6',
            };
            this.batchColorModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        async bulkToggleLockLocations(targetLockState) {
            if (this.selectedLocations.length === 0) return;
            const lockBool = !!targetLockState;
            const toUpdate = [...this.selectedLocations];

            for (const loc of toUpdate) {
                loc.is_locked = lockBool;
                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            rack_code: loc.rack_code,
                            location_type: loc.location_type,
                            room_sector: loc.room_sector,
                            canvas_x: loc.canvas_x,
                            canvas_y: loc.canvas_y,
                            canvas_width: loc.canvas_width,
                            canvas_height: loc.canvas_height,
                            is_locked: lockBool,
                        })
                    });
                } catch (err) {
                    console.error('Failed to update lock state for loc:', loc.id, err);
                }
            }

            const actionLabel = lockBool ? '🔒 dikunci' : '🔓 dibuka kuncinya';
            this.showToast(`${actionLabel} secara massal untuk ${toUpdate.length} object terseleksi.`);
            this.renderCanvas();
        },

        async submitBatchResize() {
            if (this.selectedLocations.length === 0) return;
            const newW = parseInt(this.batchResizeForm.canvas_width);
            const newH = parseInt(this.batchResizeForm.canvas_height);

            if (isNaN(newW) || isNaN(newH) || newW < 20 || newH < 20) {
                alert('Perhatian: Ukuran minimal lebar dan tinggi adalah 20px!');
                return;
            }

            const toUpdate = [...this.selectedLocations];
            this.batchResizeModalOpen = false;

            for (const loc of toUpdate) {
                loc.canvas_width = newW;
                loc.canvas_height = newH;

                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            rack_code: loc.rack_code,
                            location_type: loc.location_type,
                            room_sector: loc.room_sector,
                            canvas_x: loc.canvas_x,
                            canvas_y: loc.canvas_y,
                            canvas_width: newW,
                            canvas_height: newH,
                        })
                    });
                } catch (err) {
                    console.error('Failed batch resize for loc:', loc.id, err);
                }
            }

            this.showToast(`📐 Ukuran (Width & Height) ${toUpdate.length} object berhasil diperbarui menjadi ${newW}x${newH}px.`);
            await this.fetchData();
        },

        async submitBatchDepartment() {
            if (this.selectedLocations.length === 0) return;
            const deptId = this.batchDeptForm.assigned_department_id;
            const toUpdate = [...this.selectedLocations];
            this.batchDeptModalOpen = false;

            for (const loc of toUpdate) {
                loc.assigned_department_id = deptId || null;

                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            rack_code: loc.rack_code,
                            location_type: loc.location_type,
                            room_sector: loc.room_sector,
                            canvas_x: loc.canvas_x,
                            canvas_y: loc.canvas_y,
                            canvas_width: loc.canvas_width,
                            canvas_height: loc.canvas_height,
                            assigned_department_id: deptId || null,
                        })
                    });
                } catch (err) {
                    console.error('Failed batch department update for loc:', loc.id, err);
                }
            }

            this.showToast(`🏢 Alokasi Departemen ${toUpdate.length} object berhasil diperbarui.`);
            await this.fetchData();
        },

        async submitBatchColor() {
            if (this.selectedLocations.length === 0) return;
            const newColor = this.batchColorForm.custom_color;
            const toUpdate = [...this.selectedLocations];
            this.batchColorModalOpen = false;

            for (const loc of toUpdate) {
                loc.custom_color = newColor;

                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            rack_code: loc.rack_code,
                            location_type: loc.location_type,
                            room_sector: loc.room_sector,
                            canvas_x: loc.canvas_x,
                            canvas_y: loc.canvas_y,
                            canvas_width: loc.canvas_width,
                            canvas_height: loc.canvas_height,
                            custom_color: newColor,
                        })
                    });
                } catch (err) {
                    console.error('Failed batch color update for loc:', loc.id, err);
                }
            }

            this.showToast(`🎨 Warna custom ${toUpdate.length} object berhasil diperbarui.`);
            await this.fetchData();
        },

        alignGroupHorizontally() {
            if (this.selectedLocations.length < 2) return;

            const lockedTarget = this.selectedLocations.find(t => t.is_locked !== false);
            if (lockedTarget) {
                const typeLabel = lockedTarget.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${lockedTarget.rack_code}' sedang TERKUNCI. Buka kunci terlebih dahulu.`);
                return;
            }

            // Sort selected objects by their current canvas_x coordinate
            const targets = [...this.selectedLocations].sort((a, b) => a.canvas_x - b.canvas_x);

            const startX = targets[0].canvas_x;
            const startY = targets[0].canvas_y;
            const gap = 4; // 4px clean gap between adjacent racks

            let currentX = startX;

            const firstRoomSector = targets[0].room_sector;
            const parentRoom = this.locations.find(l => 
                l.location_type === 'room' && 
                (l.room_sector === firstRoomSector || l.rack_code === firstRoomSector)
            );

            const totalW = targets.reduce((sum, t) => sum + t.canvas_width, 0) + (targets.length - 1) * gap;

            if (parentRoom) {
                const maxX = parentRoom.canvas_x + parentRoom.canvas_width - 8;
                if ((currentX + totalW) > maxX) {
                    currentX = Math.max(parentRoom.canvas_x + 8, maxX - totalW);
                }
            }

            targets.forEach(t => {
                t.canvas_x = currentX;
                t.canvas_y = startY;
                currentX += t.canvas_width + gap;
            });

            this.saveGroupKeyboardNudge(targets);

            this.showToast(`↔️ Kelompok (${targets.length} object) berhasil dirapatkan berjejer sisi lebar (Horizontal Row).`);
            this.renderCanvas();
        },

        alignGroupVertically() {
            if (this.selectedLocations.length < 2) return;

            const lockedTarget = this.selectedLocations.find(t => t.is_locked !== false);
            if (lockedTarget) {
                const typeLabel = lockedTarget.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${lockedTarget.rack_code}' sedang TERKUNCI. Buka kunci terlebih dahulu.`);
                return;
            }

            // Sort selected objects by their current canvas_y coordinate
            const targets = [...this.selectedLocations].sort((a, b) => a.canvas_y - b.canvas_y);

            const startX = targets[0].canvas_x;
            const startY = targets[0].canvas_y;
            const gap = 4; // 4px clean gap between adjacent racks

            let currentY = startY;

            const firstRoomSector = targets[0].room_sector;
            const parentRoom = this.locations.find(l => 
                l.location_type === 'room' && 
                (l.room_sector === firstRoomSector || l.rack_code === firstRoomSector)
            );

            const totalH = targets.reduce((sum, t) => sum + t.canvas_height, 0) + (targets.length - 1) * gap;

            if (parentRoom) {
                const maxY = parentRoom.canvas_y + parentRoom.canvas_height - 8;
                if ((currentY + totalH) > maxY) {
                    currentY = Math.max(parentRoom.canvas_y + 30, maxY - totalH);
                }
            }

            targets.forEach(t => {
                t.canvas_x = startX;
                t.canvas_y = currentY;
                currentY += t.canvas_height + gap;
            });

            this.saveGroupKeyboardNudge(targets);

            this.showToast(`↕️ Kelompok (${targets.length} object) berhasil dirapatkan bertingkat sisi panjang (Vertical Column).`);
            this.renderCanvas();
        },

        // Right-Click Context Menu State
        contextMenuOpen: false,
        contextMenuTarget: null,
        contextMenuX: 0,
        contextMenuY: 0,
        clickedCanvasX: 400,
        clickedCanvasY: 300,

        get roomObjects() {
            return this.locations.filter(l => l.location_type === 'room');
        },

        objectModalOpen: false,
        objectForm: {
            location_type: 'room',
            rack_code: '',
            room_sector: '',
            box_capacity: 100,
            canvas_width: 150,
            canvas_height: 150,
            custom_color: '#3b82f6',
            canvas_x: 400,
            canvas_y: 300,
        },

        editModalOpen: false,
        editForm: { room_sector: '', rack_code: '', shelf_code: '', box_capacity: 50, assigned_department_id: '', custom_color: '', canvas_width: 100, canvas_height: 100, orientation: 'horizontal' },

        colorPresets: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6', '#f97316', '#334155'],

        canvas: null,
        ctx: null,
        hoveredLocation: null,

        // Drag & Resize State
        isDragging: false,
        dragMode: null, // 'move', 'resize-nw', 'resize-ne', 'resize-se', 'resize-sw'
        draggedTarget: null,
        dragStartX: 0,
        dragStartY: 0,
        initialX: 0,
        initialY: 0,
        nudgeTimer: null,

        init() {
            this.canvas = document.getElementById('warehouseCanvas');
            this.ctx = this.canvas.getContext('2d');
            this.applyCanvasSize(this.canvasWidth, this.canvasHeight);
            this.fetchData();

            // Window Keyboard Arrow Listener for Selected Object Repositioning
            window.addEventListener('keydown', (e) => this.handleKeyDown(e));

            // Sync API Fullscreen state changes (e.g. User presses Esc natively)
            document.addEventListener('fullscreenchange', () => {
                const isApiFullscreen = !!document.fullscreenElement;
                if (!isApiFullscreen && this.isFullscreen) {
                    this.isFullscreen = false;
                    this.showToast('📺 Keluar dari Mode Layar Penuh');
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                        this.renderCanvas();
                    });
                }
            });
        },

        hasRackCollision(testRack, testX, testY, testW, testH, excludeIds = []) {
            const margin = 2; // 2px clearance margin between racks
            return this.locations.some(other => {
                if (other.location_type === 'room') return false;
                if (other.id === testRack.id || excludeIds.includes(other.id)) return false;

                return (testX < (other.canvas_x + other.canvas_width - margin)) &&
                       ((testX + testW) > (other.canvas_x + margin)) &&
                       (testY < (other.canvas_y + other.canvas_height - margin)) &&
                       ((testY + testH) > (other.canvas_y + margin));
            });
        },

        rotateLocation(loc = null, angleStep = 90) {
            const targets = loc ? [loc] : [...this.selectedLocations];
            if (targets.length === 0) return;

            const lockedTarget = targets.find(t => t.is_locked !== false);
            if (lockedTarget) {
                const typeLabel = lockedTarget.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${lockedTarget.rack_code}' sedang TERKUNCI. Buka kunci gembok terlebih dahulu.`);
                return;
            }

            if (targets.length === 1) {
                this.rotateSingleLocation(targets[0], angleStep);
            } else {
                this.rotateGroupLocations(targets, angleStep);
            }

            this.renderCanvas();
        },

        rotateGroupLocations(targets, angleStep = 90) {
            if (!targets || targets.length === 0) return;

            // 1. Calculate Group Bounding Box
            let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
            targets.forEach(t => {
                minX = Math.min(minX, t.canvas_x);
                maxX = Math.max(maxX, t.canvas_x + t.canvas_width);
                minY = Math.min(minY, t.canvas_y);
                maxY = Math.max(maxY, t.canvas_y + t.canvas_height);
            });

            const groupCX = (minX + maxX) / 2;
            const groupCY = (minY + maxY) / 2;

            // 2. Compute rotated center for each object around group center
            const proposed = targets.map(t => {
                const objCX = t.canvas_x + (t.canvas_width / 2);
                const objCY = t.canvas_y + (t.canvas_height / 2);

                const relX = objCX - groupCX;
                const relY = objCY - groupCY;

                // +90 deg rotation matrix: (relX, relY) -> (-relY, relX)
                const newRelX = -relY;
                const newRelY = relX;

                const newW = t.canvas_height;
                const newH = t.canvas_width;

                const newObjCX = groupCX + newRelX;
                const newObjCY = groupCY + newRelY;

                const newX = Math.round(newObjCX - (newW / 2));
                const newY = Math.round(newObjCY - (newH / 2));

                const currentAngle = t.rotation_angle || 0;
                const newAngle = (currentAngle + angleStep) % 360;

                return {
                    target: t,
                    newX: newX,
                    newY: newY,
                    newW: newW,
                    newH: newH,
                    newAngle: newAngle,
                    orientation: (newW >= newH) ? 'horizontal' : 'vertical'
                };
            });

            // 3. Find parent Gudang / Room if targets belong to a Gudang sector
            const firstRoomSector = targets[0].room_sector;
            const parentRoom = this.locations.find(l => 
                l.location_type === 'room' && 
                (l.room_sector === firstRoomSector || l.rack_code === firstRoomSector)
            );

            // Calculate new group bounds after rotation
            let pMinX = Infinity, pMaxX = -Infinity, pMinY = Infinity, pMaxY = -Infinity;
            proposed.forEach(p => {
                pMinX = Math.min(pMinX, p.newX);
                pMaxX = Math.max(pMaxX, p.newX + p.newW);
                pMinY = Math.min(pMinY, p.newY);
                pMaxY = Math.max(pMaxY, p.newY + p.newH);
            });

            let offsetX = 0;
            let offsetY = 0;

            if (parentRoom) {
                const roomMinX = parentRoom.canvas_x + 8;
                const roomMaxX = parentRoom.canvas_x + parentRoom.canvas_width - 8;
                const roomMinY = parentRoom.canvas_y + 30;
                const roomMaxY = parentRoom.canvas_y + parentRoom.canvas_height - 8;

                if (pMinX < roomMinX) offsetX = roomMinX - pMinX;
                if (pMaxX + offsetX > roomMaxX) offsetX = roomMaxX - pMaxX;

                if (pMinY < roomMinY) offsetY = roomMinY - pMinY;
                if (pMaxY + offsetY > roomMaxY) offsetY = roomMaxY - pMaxY;
            } else {
                if (pMinX < 10) offsetX = 10 - pMinX;
                if (pMinY < 10) offsetY = 10 - pMinY;
            }

            // 4. Apply updated coordinates and persist to backend
            proposed.forEach(p => {
                const finalX = Math.max(0, p.newX + offsetX);
                const finalY = Math.max(0, p.newY + offsetY);

                p.target.canvas_x = finalX;
                p.target.canvas_y = finalY;
                p.target.canvas_width = p.newW;
                p.target.canvas_height = p.newH;
                p.target.rotation_angle = p.newAngle;
                p.target.orientation = p.orientation;

                this.saveKeyboardNudge(p.target, []);
            });

            this.showToast(`🔄 Kelompok (${targets.length} object) berhasil diputar 90°.`);
        },

        rotateSingleLocation(target, angleStep = 90) {
            if (!target) return;

            if (target.is_locked !== false) {
                const typeLabel = target.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${target.rack_code}' sedang TERKUNCI. Buka kunci gembok terlebih dahulu.`);
                return;
            }

            const currentAngle = target.rotation_angle || 0;
            const newAngle = (currentAngle + angleStep) % 360;

            const cx = target.canvas_x + (target.canvas_width / 2);
            const cy = target.canvas_y + (target.canvas_height / 2);

            const oldW = target.canvas_width;
            const oldH = target.canvas_height;
            const newW = oldH;
            const newH = oldW;

            let newX = Math.round(cx - (newW / 2));
            let newY = Math.round(cy - (newH / 2));

            if (target.location_type !== 'room') {
                const groupIds = this.selectedLocations.map(l => l.id);
                if (this.hasRackCollision(target, newX, newY, newW, newH, groupIds)) {
                    let resolved = false;
                    const shifts = [
                        [0, 0], [0, 10], [0, -10], [10, 0], [-10, 0], 
                        [0, 20], [0, -20], [20, 0], [-20, 0],
                        [0, 30], [0, -30], [30, 0], [-30, 0]
                    ];
                    for (const [sx, sy] of shifts) {
                        const tx = newX + sx;
                        const ty = newY + sy;
                        if (!this.hasRackCollision(target, tx, ty, newW, newH, groupIds)) {
                            newX = tx;
                            newY = ty;
                            resolved = true;
                            break;
                        }
                    }
                    if (!resolved) {
                        this.showToast(`⚠️ Rotasi ${target.rack_code} dibatalkan karena bertabrakan dengan rak lain.`);
                        return;
                    }
                }
            }

            target.rotation_angle = newAngle;
            target.canvas_width = newW;
            target.canvas_height = newH;
            target.canvas_x = Math.max(0, newX);
            target.canvas_y = Math.max(0, newY);
            target.orientation = (newW >= newH) ? 'horizontal' : 'vertical';

            this.saveKeyboardNudge(target, []);
        },

        async toggleLockLocation(loc) {
            if (!loc) return;
            const nextState = (loc.is_locked !== false) ? false : true;
            loc.is_locked = nextState;

            const typeLabel = loc.location_type === 'room' ? 'Gudang' : 'Rak';
            const lockLabel = nextState ? 'TERKUNCI 🔒' : 'DIBUKA 🔓';
            this.showToast(`Status ${typeLabel} '${loc.rack_code}' diset ke ${lockLabel}`);
            this.renderCanvas();

            try {
                await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        rack_code: loc.rack_code,
                        location_type: loc.location_type,
                        room_sector: loc.room_sector,
                        canvas_x: loc.canvas_x,
                        canvas_y: loc.canvas_y,
                        canvas_width: loc.canvas_width,
                        canvas_height: loc.canvas_height,
                        is_locked: loc.is_locked,
                    })
                });
            } catch (err) {
                console.error('Failed to toggle lock state:', err);
            }
        },

        handleKeyDown(e) {
            // Ignore keydown if user is currently typing inside an input/textarea/select field
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;

            // Esc key -> Toggle / Exit Fullscreen mode
            if (e.key === 'Escape' && this.isFullscreen) {
                e.preventDefault();
                this.toggleFullscreen();
                return;
            }

            // Ctrl + C / Cmd + C -> Copy selected objects
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c') {
                if (this.selectedLocations.length > 0) {
                    e.preventDefault();
                    this.copiedLocations = JSON.parse(JSON.stringify(this.selectedLocations));
                    this.showToast(`📋 ${this.copiedLocations.length} object disalin. Tekan Ctrl+V untuk menempel (paste).`);
                }
                return;
            }

            // Ctrl + V / Cmd + V -> Paste copied objects
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'v') {
                if (this.copiedLocations && this.copiedLocations.length > 0) {
                    e.preventDefault();
                    this.pasteCopiedLocations();
                } else {
                    this.showToast(`⚠️ Belum ada objek yang disalin. Pilih objek di canvas lalu tekan Ctrl+C.`);
                }
                return;
            }

            if (this.selectedLocations.length === 0) return;

            if (['Delete', 'Backspace'].includes(e.key)) {
                e.preventDefault();
                this.deleteSelectedLocation();
                return;
            }

            if (e.key.toLowerCase() === 'r') {
                e.preventDefault();
                this.rotateLocation(null, 90);
                return;
            }

            const arrowKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
            if (!arrowKeys.includes(e.key)) return;

            e.preventDefault();

            const step = e.shiftKey ? 5 : 1;
            let deltaX = 0;
            let deltaY = 0;

            if (e.key === 'ArrowUp') deltaY = -step;
            if (e.key === 'ArrowDown') deltaY = step;
            if (e.key === 'ArrowLeft') deltaX = -step;
            if (e.key === 'ArrowRight') deltaX = step;

            const groupIds = this.selectedLocations.map(l => l.id);
            const childRacksToSave = [];

            this.selectedLocations.forEach(target => {
                if (target.is_locked !== false) {
                    const typeLabel = target.location_type === 'room' ? 'Gudang' : 'Rak';
                    this.showToast(`🔒 ${typeLabel} '${target.rack_code}' sedang TERKUNCI. Buka kunci gembok terlebih dahulu.`);
                    return;
                }
                if (target.location_type === 'room') {
                    const roomSectorName = target.room_sector || target.rack_code;
                    const childRacks = this.locations.filter(loc => {
                        if (loc.location_type === 'room' || loc.id === target.id) return false;
                        return loc.room_sector === roomSectorName || loc.room_sector === target.rack_code ||
                               (loc.canvas_x >= target.canvas_x && (loc.canvas_x + loc.canvas_width) <= (target.canvas_x + target.canvas_width) &&
                                loc.canvas_y >= target.canvas_y && (loc.canvas_y + loc.canvas_height) <= (target.canvas_y + target.canvas_height));
                    });

                    target.canvas_x = Math.max(0, target.canvas_x + deltaX);
                    target.canvas_y = Math.max(0, target.canvas_y + deltaY);

                    childRacks.forEach(r => {
                        r.canvas_x = Math.max(0, r.canvas_x + deltaX);
                        r.canvas_y = Math.max(0, r.canvas_y + deltaY);
                        childRacksToSave.push(r);
                    });
                } else {
                    let newX = Math.max(0, target.canvas_x + deltaX);
                    let newY = Math.max(0, target.canvas_y + deltaY);

                    if (!this.hasRackCollision(target, newX, target.canvas_y, target.canvas_width, target.canvas_height, groupIds)) {
                        target.canvas_x = newX;
                    }
                    if (!this.hasRackCollision(target, target.canvas_x, newY, target.canvas_width, target.canvas_height, groupIds)) {
                        target.canvas_y = newY;
                    }
                }
            });

            this.renderCanvas();
            this.saveGroupKeyboardNudge([...this.selectedLocations, ...childRacksToSave]);
        },

        saveGroupKeyboardNudge(locationsToSave) {
            if (this.nudgeTimer) clearTimeout(this.nudgeTimer);
            this.nudgeTimer = setTimeout(async () => {
                const uniqueLocs = Array.from(new Map(locationsToSave.map(item => [item.id, item])).values());
                for (const target of uniqueLocs) {
                    try {
                        await fetch(`/api/warehouse/locations/${target.id}/update`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                rack_code: target.rack_code,
                                location_type: target.location_type,
                                room_sector: target.room_sector,
                                canvas_x: target.canvas_x,
                                canvas_y: target.canvas_y,
                                canvas_width: target.canvas_width,
                                canvas_height: target.canvas_height,
                            })
                        });
                    } catch (err) {
                        console.error('Failed to save group keyboard nudge:', err);
                    }
                }
            }, 350);
        },

        saveKeyboardNudge(target) {
            if (Array.isArray(target)) {
                this.saveGroupKeyboardNudge(target);
            } else if (target) {
                this.saveGroupKeyboardNudge([target]);
            }
        },

        getObjectAtPosition(x, y) {
            // 1. Prioritize Rack objects (top foreground layer)
            let found = this.locations.find(loc => {
                if (loc.location_type === 'room') return false;
                return x >= loc.canvas_x && x <= (loc.canvas_x + loc.canvas_width) &&
                       y >= loc.canvas_y && y <= (loc.canvas_y + loc.canvas_height);
            });

            // 2. Fallback to Room objects (background layer)
            if (!found) {
                found = this.locations.find(loc => {
                    if (loc.location_type !== 'room') return false;
                    return x >= loc.canvas_x && x <= (loc.canvas_x + loc.canvas_width) &&
                           y >= loc.canvas_y && y <= (loc.canvas_y + loc.canvas_height);
                });
            }

            return found;
        },

        handleCanvasContextMenu(e) {
            const rect = this.canvas.getBoundingClientRect();
            this.clickedCanvasX = Math.round((e.clientX - rect.left) / this.scale);
            this.clickedCanvasY = Math.round((e.clientY - rect.top) / this.scale);

            // Detect if right click was performed directly over an existing object (Rack prioritized over Room)
            const targetObj = this.getObjectAtPosition(this.clickedCanvasX, this.clickedCanvasY);

            if (targetObj) {
                this.selectedLocation = targetObj;
                this.contextMenuTarget = targetObj;
            } else {
                this.contextMenuTarget = null;
            }

            this.contextMenuX = e.clientX;
            this.contextMenuY = e.clientY;
            this.contextMenuOpen = true;
            this.renderCanvas();
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        async fetchData() {
            this.loading = true;
            try {
                const res = await fetch('{{ route("api.warehouse.layout_data") }}');
                const data = await res.json();
                if (data.success) {
                    this.locations = data.locations;
                    this.departments = data.departments;
                    this.renderCanvas();
                }
            } catch (err) {
                console.error('Error fetching layout:', err);
            } finally {
                this.loading = false;
            }
        },

        zoomIn() {
            if (this.scale < 2.0) {
                this.scale += 0.15;
                this.renderCanvas();
            }
        },

        zoomOut() {
            if (this.scale > 0.6) {
                this.scale -= 0.15;
                this.renderCanvas();
            }
        },

        resetZoom() {
            this.scale = 1.0;
            this.renderCanvas();
        },

        openAddRoomModal(x = 400, y = 300) {
            this.objectForm = {
                location_type: 'room',
                rack_code: 'GUDANG BARU',
                room_sector: 'SEKTOR BARU',
                box_capacity: 500,
                canvas_width: 160,
                canvas_height: 140,
                custom_color: '#3b82f6',
                canvas_x: Math.max(0, x),
                canvas_y: Math.max(0, y),
            };
            this.objectModalOpen = true;
        },

        openAddRackModal(x = 450, y = 320) {
            this.objectForm = {
                location_type: 'rack',
                rack_code: 'RAK-BARU-01',
                room_sector: 'R1',
                box_capacity: 100,
                canvas_width: 40,
                canvas_height: 120,
                custom_color: '#10b981',
                canvas_x: Math.max(0, x),
                canvas_y: Math.max(0, y),
            };
            this.objectModalOpen = true;
        },

        addRackInsideRoom(room, x, y, defaultQty = 1) {
            const roomSectorName = room.room_sector || room.rack_code;
            let targetX = Math.max(room.canvas_x + 15, x);
            let targetY = Math.max(room.canvas_y + 35, y);
            const rackW = 40;
            const rackH = 120;

            // Auto-find non-colliding spot inside target room
            let collisionTries = 0;
            while (this.hasRackCollision({ id: 0 }, targetX, targetY, rackW, rackH) && collisionTries < 25) {
                targetX += 45;
                if ((targetX + rackW) > (room.canvas_x + room.canvas_width - 10)) {
                    targetX = room.canvas_x + 15;
                    targetY += 35;
                }
                collisionTries++;
            }

            this.objectForm = {
                location_type: 'rack',
                rack_code: 'RAK-' + roomSectorName,
                room_sector: roomSectorName,
                box_capacity: 100,
                canvas_width: rackW,
                canvas_height: rackH,
                custom_color: '#10b981',
                canvas_x: targetX,
                canvas_y: targetY,
                quantity: defaultQty,
            };
            this.contextMenuOpen = false;
            this.objectModalOpen = true;
        },

        openMoveModal(target = null) {
            if (target) this.selectedLocation = target;
            this.moveModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        async moveRackToRoom(rack, room) {
            if (!rack || !room) return;
            let targetX = room.canvas_x + 15;
            let targetY = room.canvas_y + 35;
            const roomSectorName = room.room_sector || room.rack_code;

            // Auto-find non-colliding spot inside target room
            let collisionTries = 0;
            while (this.hasRackCollision(rack, targetX, targetY, rack.canvas_width, rack.canvas_height) && collisionTries < 25) {
                targetX += 45;
                if ((targetX + rack.canvas_width) > (room.canvas_x + room.canvas_width - 10)) {
                    targetX = room.canvas_x + 15;
                    targetY += 35;
                }
                collisionTries++;
            }

            try {
                const res = await fetch(`/api/warehouse/locations/${rack.id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        rack_code: rack.rack_code,
                        location_type: 'rack',
                        room_sector: roomSectorName,
                        canvas_x: targetX,
                        canvas_y: targetY,
                        canvas_width: rack.canvas_width,
                        canvas_height: rack.canvas_height,
                    })
                });
                const data = await res.json();
                if (data.success) {
                    alert(`Rak '${rack.rack_code}' berhasil dipindahkan ke Gudang/Sektor '${room.rack_code}' (${roomSectorName}).`);
                    this.moveModalOpen = false;
                    this.contextMenuOpen = false;
                    await this.fetchData();
                    this.selectedLocation = this.locations.find(l => l.id === rack.id);
                }
            } catch (err) {
                alert('Gagal memindahkan posisi rak ke gudang.');
            }
        },

        async duplicateRack(sourceRack) {
            if (!sourceRack) return;

            // Generate smart auto-incremented duplicate code
            const cleanBase = (sourceRack.rack_code || 'RAK').replace(/ \(COPY\d*\)$/i, '').replace(/ - COPY\d*$/i, '');
            let copyIndex = 1;
            let newCode = `${cleanBase} (COPY)`;
            while (this.locations.some(l => l.rack_code === newCode)) {
                copyIndex++;
                newCode = `${cleanBase} (COPY ${copyIndex})`;
            }

            let newX = sourceRack.canvas_x + 20;
            let newY = sourceRack.canvas_y + 20;

            // Clamp inside parent room if source rack is inside a Gudang
            const parentRoom = this.locations.find(loc => {
                if (loc.location_type !== 'room') return false;
                const isSectorMatch = loc.room_sector === sourceRack.room_sector || loc.rack_code === sourceRack.room_sector;
                const isInside = (sourceRack.canvas_x >= loc.canvas_x) && 
                                 ((sourceRack.canvas_x + sourceRack.canvas_width) <= (loc.canvas_x + loc.canvas_width)) &&
                                 (sourceRack.canvas_y >= loc.canvas_y) && 
                                 ((sourceRack.canvas_y + sourceRack.canvas_height) <= (loc.canvas_y + loc.canvas_height));
                return isSectorMatch || isInside;
            });

            if (parentRoom) {
                const maxX = parentRoom.canvas_x + parentRoom.canvas_width - sourceRack.canvas_width - 4;
                const maxY = parentRoom.canvas_y + parentRoom.canvas_height - sourceRack.canvas_height - 4;
                newX = Math.min(maxX, newX);
                newY = Math.min(maxY, newY);
            }

            // Shift position if colliding with existing racks
            let collisionTries = 0;
            while (this.hasRackCollision({ id: 0 }, newX, newY, sourceRack.canvas_width, sourceRack.canvas_height) && collisionTries < 15) {
                newY += 30;
                if (parentRoom && (newY + sourceRack.canvas_height) > (parentRoom.canvas_y + parentRoom.canvas_height - 4)) {
                    newY = parentRoom.canvas_y + 28;
                    newX += 35;
                }
                collisionTries++;
            }

            const duplicateData = {
                location_type: sourceRack.location_type || 'rack',
                rack_code: newCode,
                room_sector: sourceRack.room_sector || 'Umum',
                shelf_code: sourceRack.shelf_code || 'BARIS-01',
                box_capacity: sourceRack.box_capacity || 100,
                custom_color: sourceRack.custom_color || '',
                canvas_x: newX,
                canvas_y: newY,
                canvas_width: sourceRack.canvas_width || 40,
                canvas_height: sourceRack.canvas_height || 120,
                orientation: sourceRack.orientation || 'horizontal',
                rotation_angle: sourceRack.rotation_angle || 0,
                assigned_department_id: sourceRack.assigned_department_id || null,
            };

            try {
                const res = await fetch('{{ route("api.warehouse.locations.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(duplicateData)
                });
                const data = await res.json();
                if (data.success) {
                    const typeLabel = sourceRack.location_type === 'room' ? 'Gudang' : 'Rak';
                    this.showToast(`📋 ${typeLabel} '${sourceRack.rack_code}' berhasil diduplikat menjadi '${newCode}'.`);
                    await this.fetchData();
                    const newLocationObj = this.locations.find(l => l.id === data.location.id);
                    if (newLocationObj) {
                        this.selectedLocation = newLocationObj;
                        this.copiedLocation = JSON.parse(JSON.stringify(newLocationObj));
                    }
                }
            } catch (err) {
                alert('Gagal menduplikat object.');
            }
        },

        async submitCreateObject() {
            if (this.objectForm.location_type === 'rack') {
                const qty = parseInt(this.objectForm.quantity) || 1;
                const testW = parseInt(this.objectForm.canvas_width);
                const testH = parseInt(this.objectForm.canvas_height);
                const minDim = Math.min(testW, testH);
                const maxDim = Math.max(testW, testH);
                if (minDim < 25 || maxDim < 100) {
                    alert('Perhatian: Ukuran minimal objek Rak adalah Sisi Panjang minimal 100px dan Sisi Tebal/Lebar minimal 25px!');
                    return;
                }

                const parentRoom = this.locations.find(l => l.location_type === 'room' && (l.room_sector === this.objectForm.room_sector || l.rack_code === this.objectForm.room_sector));

                let currentX = parseInt(this.objectForm.canvas_x);
                let currentY = parseInt(this.objectForm.canvas_y);
                const cleanBaseCode = (this.objectForm.rack_code || 'RAK').replace(/[\s-]*\d+$/i, '');

                let createdCount = 0;
                const addedLocations = [];

                for (let i = 0; i < qty; i++) {
                    let indexNum = (i + 1);
                    let rackCode = (qty > 1 || this.locations.some(l => l.rack_code === this.objectForm.rack_code))
                        ? `${cleanBaseCode}-${String(indexNum).padStart(2, '0')}`
                        : this.objectForm.rack_code;

                    let codeSearchIdx = indexNum;
                    while (this.locations.some(l => l.rack_code === rackCode) || addedLocations.some(l => l.rack_code === rackCode)) {
                        codeSearchIdx++;
                        rackCode = `${cleanBaseCode}-${String(codeSearchIdx).padStart(2, '0')}`;
                    }

                    let validX = currentX;
                    let validY = currentY;
                    let tries = 0;

                    const excludeIds = addedLocations.map(l => l.id);

                    while (this.hasRackCollision({ id: 0 }, validX, validY, testW, testH, excludeIds) && tries < 60) {
                        validX += (testW + 8);
                        if (parentRoom && (validX + testW) > (parentRoom.canvas_x + parentRoom.canvas_width - 8)) {
                            validX = parentRoom.canvas_x + 15;
                            validY += (testH + 8);
                        } else if (!parentRoom && (validX + testW) > 920) {
                            validX = 20;
                            validY += (testH + 8);
                        }
                        tries++;
                    }

                    const rackPayload = {
                        location_type: 'rack',
                        rack_code: rackCode,
                        room_sector: this.objectForm.room_sector || 'Umum',
                        box_capacity: this.objectForm.box_capacity || 100,
                        canvas_width: testW,
                        canvas_height: testH,
                        custom_color: this.objectForm.custom_color || '',
                        canvas_x: validX,
                        canvas_y: validY,
                        orientation: (testW >= testH) ? 'horizontal' : 'vertical',
                    };

                    try {
                        const res = await fetch('{{ route("api.warehouse.locations.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(rackPayload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            createdCount++;
                            addedLocations.push(data.location);
                            this.locations.push(data.location);

                            currentX = validX + (testW + 8);
                            if (parentRoom && (currentX + testW) > (parentRoom.canvas_x + parentRoom.canvas_width - 8)) {
                                currentX = parentRoom.canvas_x + 15;
                                currentY = validY + (testH + 8);
                            }
                        }
                    } catch (err) {
                        console.error('Failed to create batch rack:', err);
                    }
                }

                if (createdCount > 0) {
                    this.showToast(`✨ Berhasil membuat ${createdCount} objek Rak baru di canvas!`);
                    this.objectModalOpen = false;
                    await this.fetchData();
                    if (addedLocations.length > 0) {
                        this.selectedLocations = this.locations.filter(l => addedLocations.some(a => a.id === l.id));
                    }
                    this.renderCanvas();
                } else {
                    alert('Gagal membuat objek rak.');
                }
                return;
            }

            try {
                const res = await fetch('{{ route("api.warehouse.locations.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.objectForm)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.objectModalOpen = false;
                    await this.fetchData();
                    this.selectedLocation = this.locations.find(l => l.id === data.location.id);
                    this.renderCanvas();
                }
            } catch (err) {
                alert('Gagal menambahkan object baru.');
            }
        },

        renderCanvas() {
            if (!this.ctx) return;

            const w = this.canvas.width;
            const h = this.canvas.height;

            // Clear Background
            this.ctx.clearRect(0, 0, w, h);

            this.ctx.save();
            this.ctx.scale(this.scale, this.scale);

            // 1. Draw Architectural Floor & Grid
            this.drawFloorGrid(w, h);

            // 2. Draw Architectural Fixed Headers
            this.drawHeaderBanner();

            // 3. Draw All Database Warehouse Objects (Rooms drawn first as background layer, Racks on top)
            const rooms = this.locations.filter(l => l.location_type === 'room');
            const racks = this.locations.filter(l => l.location_type !== 'room');

            rooms.forEach(loc => {
                let isFilteredOut = (this.filterDepartment && loc.assigned_department_id != this.filterDepartment);
                this.drawRoomObject(loc, isFilteredOut);
            });

            racks.forEach(loc => {
                let isFilteredOut = (this.filterDepartment && loc.assigned_department_id != this.filterDepartment);
                this.drawRackObject(loc, isFilteredOut);
            });

            // 4. Draw Selected Handles (Resize Corners & Bounding Box)
            this.selectedLocations.forEach(loc => {
                this.drawSelectionHandles(loc);
            });

            // 5. Draw Multi-Select Badge if more than 1 object selected
            if (this.selectedLocations.length > 1) {
                this.drawMultiSelectBadge();
            }

            // 6. Draw Alignment Guidance Lines (Smart Snap Guides)
            if (this.activeGuideLines && this.activeGuideLines.length > 0) {
                this.drawGuideLines();
            }

            this.ctx.restore();
            if (window.lucide) lucide.createIcons();
        },

        drawGuideLines() {
            const ctx = this.ctx;
            ctx.save();
            ctx.strokeStyle = '#06b6d4'; // Cyan 500 accent guide line
            ctx.lineWidth = 1.5;

            this.activeGuideLines.forEach(guide => {
                ctx.setLineDash([5, 4]);
                ctx.beginPath();
                if (guide.type === 'vertical') {
                    ctx.moveTo(guide.x, guide.minY);
                    ctx.lineTo(guide.x, guide.maxY);
                    ctx.stroke();

                    // Glowing intersection dots
                    ctx.fillStyle = '#06b6d4';
                    ctx.setLineDash([]);
                    ctx.beginPath();
                    ctx.arc(guide.x, guide.minY, 3.5, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.beginPath();
                    ctx.arc(guide.x, guide.maxY, 3.5, 0, Math.PI * 2);
                    ctx.fill();
                } else if (guide.type === 'horizontal') {
                    ctx.moveTo(guide.minX, guide.y);
                    ctx.lineTo(guide.maxX, guide.y);
                    ctx.stroke();

                    // Glowing intersection dots
                    ctx.fillStyle = '#06b6d4';
                    ctx.setLineDash([]);
                    ctx.beginPath();
                    ctx.arc(guide.minX, guide.y, 3.5, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.beginPath();
                    ctx.arc(guide.maxX, guide.y, 3.5, 0, Math.PI * 2);
                    ctx.fill();
                }
            });

            ctx.restore();
        },

        calculateSnapAndGuides(target) {
            this.activeGuideLines = [];
            if (!target) return;

            const SNAP_THRESH = 6;
            const selectedIds = this.selectedLocations.map(l => l.id);

            // Candidates: all other locations not currently in selection
            const candidates = this.locations.filter(l => !selectedIds.includes(l.id));
            if (candidates.length === 0) return;

            const tX = target.canvas_x;
            const tY = target.canvas_y;
            const tW = target.canvas_width;
            const tH = target.canvas_height;

            const tLeft = tX;
            const tRight = tX + tW;
            const tCenterX = tX + (tW / 2);

            const tTop = tY;
            const tBottom = tY + tH;
            const tCenterY = tY + (tH / 2);

            let bestSnapX = null;
            let minDiffX = SNAP_THRESH + 1;
            let guideXObj = null;

            let bestSnapY = null;
            let minDiffY = SNAP_THRESH + 1;
            let guideYObj = null;

            candidates.forEach(other => {
                const oLeft = other.canvas_x;
                const oRight = other.canvas_x + other.canvas_width;
                const oCenterX = other.canvas_x + (other.canvas_width / 2);

                const oTop = other.canvas_y;
                const oBottom = other.canvas_y + other.canvas_height;
                const oCenterY = other.canvas_y + (other.canvas_height / 2);

                // --- X ALIGNMENT CHECKS ---
                const xPairs = [
                    { diff: Math.abs(tLeft - oLeft), newX: oLeft, lineX: oLeft },
                    { diff: Math.abs(tLeft - oRight), newX: oRight, lineX: oRight },
                    { diff: Math.abs(tLeft - (oRight + 4)), newX: oRight + 4, lineX: oRight + 4 },
                    { diff: Math.abs(tRight - oRight), newX: oRight - tW, lineX: oRight },
                    { diff: Math.abs(tRight - oLeft), newX: oLeft - tW, lineX: oLeft },
                    { diff: Math.abs(tRight - (oLeft - 4)), newX: oLeft - tW - 4, lineX: oLeft - 4 },
                    { diff: Math.abs(tCenterX - oCenterX), newX: Math.round(oCenterX - (tW / 2)), lineX: oCenterX }
                ];

                xPairs.forEach(pair => {
                    if (pair.diff <= SNAP_THRESH && pair.diff < minDiffX) {
                        if (target.location_type === 'room' || !this.hasRackCollision(target, pair.newX, tY, tW, tH, selectedIds)) {
                            minDiffX = pair.diff;
                            bestSnapX = pair.newX;
                            guideXObj = {
                                type: 'vertical',
                                x: pair.lineX,
                                minY: Math.min(tTop, oTop) - 15,
                                maxY: Math.max(tBottom, oBottom) + 15
                            };
                        }
                    }
                });

                // --- Y ALIGNMENT CHECKS ---
                const yPairs = [
                    { diff: Math.abs(tTop - oTop), newY: oTop, lineY: oTop },
                    { diff: Math.abs(tTop - oBottom), newY: oBottom, lineY: oBottom },
                    { diff: Math.abs(tTop - (oBottom + 4)), newY: oBottom + 4, lineY: oBottom + 4 },
                    { diff: Math.abs(tBottom - oBottom), newY: oBottom - tH, lineY: oBottom },
                    { diff: Math.abs(tBottom - oTop), newY: oTop - tH, lineY: oTop },
                    { diff: Math.abs(tBottom - (oTop - 4)), newY: oTop - tH - 4, lineY: oTop - 4 },
                    { diff: Math.abs(tCenterY - oCenterY), newY: Math.round(oCenterY - (tH / 2)), lineY: oCenterY }
                ];

                yPairs.forEach(pair => {
                    if (pair.diff <= SNAP_THRESH && pair.diff < minDiffY) {
                        if (target.location_type === 'room' || !this.hasRackCollision(target, tX, pair.newY, tW, tH, selectedIds)) {
                            minDiffY = pair.diff;
                            bestSnapY = pair.newY;
                            guideYObj = {
                                type: 'horizontal',
                                y: pair.lineY,
                                minX: Math.min(tLeft, oLeft) - 15,
                                maxX: Math.max(tRight, oRight) + 15
                            };
                        }
                    }
                });
            });

            if (bestSnapX !== null) {
                target.canvas_x = bestSnapX;
                if (guideXObj) this.activeGuideLines.push(guideXObj);
            }
            if (bestSnapY !== null) {
                target.canvas_y = bestSnapY;
                if (guideYObj) this.activeGuideLines.push(guideYObj);
            }
        },

        calculateResizeSnapAndGuides(target, mode, propX, propY, propW, propH, parentRoom = null) {
            this.activeGuideLines = [];
            if (!target || !mode) {
                return { x: propX, y: propY, w: propW, h: propH, guideX: null, guideY: null };
            }

            const SNAP_THRESH = Math.max(8, Math.round(8 / (this.scale || 1)));
            const isTargetRoom = target.location_type === 'room';

            // Candidates to align with
            let candidates = [];
            if (isTargetRoom) {
                // When resizing a Gudang room, align with other Gudang rooms
                candidates = this.locations.filter(l => l.location_type === 'room' && l.id !== target.id);
            } else {
                // When resizing a Rack, align with other Racks
                candidates = this.locations.filter(l => l.location_type !== 'room' && l.id !== target.id);
            }

            let resultX = propX;
            let resultY = propY;
            let resultW = propW;
            let resultH = propH;

            const tL = propX;
            const tR = propX + propW;
            const tT = propY;
            const tB = propY + propH;

            const activeRight = (mode === 'resize-se' || mode === 'resize-ne');
            const activeLeft = (mode === 'resize-nw' || mode === 'resize-sw');
            const activeBottom = (mode === 'resize-se' || mode === 'resize-sw');
            const activeTop = (mode === 'resize-nw' || mode === 'resize-ne');

            let bestSnapX = null;
            let minDiffX = SNAP_THRESH + 1;
            let guideXObj = null;

            let bestSnapY = null;
            let minDiffY = SNAP_THRESH + 1;
            let guideYObj = null;

            // Include parent room inner borders as an alignment candidate for racks
            if (!isTargetRoom && parentRoom) {
                const pL = parentRoom.canvas_x + 4;
                const pR = parentRoom.canvas_x + parentRoom.canvas_width - 4;
                const pT = parentRoom.canvas_y + 26;
                const pB = parentRoom.canvas_y + parentRoom.canvas_height - 4;

                candidates = [...candidates, {
                    id: -999,
                    canvas_x: pL,
                    canvas_y: pT,
                    canvas_width: Math.max(0, pR - pL),
                    canvas_height: Math.max(0, pB - pT),
                    is_border: true
                }];
            }

            candidates.forEach(other => {
                const oL = other.canvas_x;
                const oR = other.canvas_x + other.canvas_width;
                const oCenterX = other.canvas_x + (other.canvas_width / 2);

                const oT = other.canvas_y;
                const oB = other.canvas_y + other.canvas_height;
                const oCenterY = other.canvas_y + (other.canvas_height / 2);

                // --- HORIZONTAL / X ALIGNMENT ---
                if (activeRight) {
                    const pairs = [
                        { diff: Math.abs(tR - oR), snapVal: oR, lineX: oR },
                        { diff: Math.abs(tR - oL), snapVal: oL, lineX: oL },
                        { diff: Math.abs(tR - (oL - 4)), snapVal: oL - 4, lineX: oL - 4 },
                        { diff: Math.abs(tR - oCenterX), snapVal: Math.round(oCenterX), lineX: oCenterX },
                    ];

                    // Same width match as sibling rack
                    if (!other.is_border) {
                        const targetSameW = target.canvas_x + other.canvas_width;
                        pairs.push({ diff: Math.abs(tR - targetSameW), snapVal: targetSameW, lineX: targetSameW });
                    }

                    pairs.forEach(p => {
                        if (p.diff <= SNAP_THRESH && p.diff < minDiffX) {
                            const newW = p.snapVal - target.canvas_x;
                            if (newW >= 20) {
                                minDiffX = p.diff;
                                bestSnapX = { edge: 'right', w: newW };
                                guideXObj = {
                                    type: 'vertical',
                                    x: p.lineX,
                                    minY: Math.max(0, Math.min(tT, oT) - 15),
                                    maxY: Math.max(tB, oB) + 15
                                };
                            }
                        }
                    });
                } else if (activeLeft) {
                    const fixedRight = this.initialX + this.initialW;
                    const pairs = [
                        { diff: Math.abs(tL - oL), snapVal: oL, lineX: oL },
                        { diff: Math.abs(tL - oR), snapVal: oR, lineX: oR },
                        { diff: Math.abs(tL - (oR + 4)), snapVal: oR + 4, lineX: oR + 4 },
                        { diff: Math.abs(tL - oCenterX), snapVal: Math.round(oCenterX), lineX: oCenterX },
                    ];

                    // Same width match as sibling rack
                    if (!other.is_border) {
                        const targetSameX = fixedRight - other.canvas_width;
                        pairs.push({ diff: Math.abs(tL - targetSameX), snapVal: targetSameX, lineX: targetSameX });
                    }

                    pairs.forEach(p => {
                        if (p.diff <= SNAP_THRESH && p.diff < minDiffX) {
                            const newW = fixedRight - p.snapVal;
                            if (newW >= 20) {
                                minDiffX = p.diff;
                                bestSnapX = { edge: 'left', x: p.snapVal, w: newW };
                                guideXObj = {
                                    type: 'vertical',
                                    x: p.lineX,
                                    minY: Math.max(0, Math.min(tT, oT) - 15),
                                    maxY: Math.max(tB, oB) + 15
                                };
                            }
                        }
                    });
                }

                // --- VERTICAL / Y ALIGNMENT ---
                if (activeBottom) {
                    const pairs = [
                        { diff: Math.abs(tB - oB), snapVal: oB, lineY: oB },
                        { diff: Math.abs(tB - oT), snapVal: oT, lineY: oT },
                        { diff: Math.abs(tB - (oT - 4)), snapVal: oT - 4, lineY: oT - 4 },
                        { diff: Math.abs(tB - oCenterY), snapVal: Math.round(oCenterY), lineY: oCenterY },
                    ];

                    // Same height match as sibling rack
                    if (!other.is_border) {
                        const targetSameH = target.canvas_y + other.canvas_height;
                        pairs.push({ diff: Math.abs(tB - targetSameH), snapVal: targetSameH, lineY: targetSameH });
                    }

                    pairs.forEach(p => {
                        if (p.diff <= SNAP_THRESH && p.diff < minDiffY) {
                            const newH = p.snapVal - target.canvas_y;
                            if (newH >= 20) {
                                minDiffY = p.diff;
                                bestSnapY = { edge: 'bottom', h: newH };
                                guideYObj = {
                                    type: 'horizontal',
                                    y: p.lineY,
                                    minX: Math.max(0, Math.min(tL, oL) - 15),
                                    maxX: Math.max(tR, oR) + 15
                                };
                            }
                        }
                    });
                } else if (activeTop) {
                    const fixedBottom = this.initialY + this.initialH;
                    const pairs = [
                        { diff: Math.abs(tT - oT), snapVal: oT, lineY: oT },
                        { diff: Math.abs(tT - oB), snapVal: oB, lineY: oB },
                        { diff: Math.abs(tT - (oB + 4)), snapVal: oB + 4, lineY: oB + 4 },
                        { diff: Math.abs(tT - oCenterY), snapVal: Math.round(oCenterY), lineY: oCenterY },
                    ];

                    // Same height match as sibling rack
                    if (!other.is_border) {
                        const targetSameY = fixedBottom - other.canvas_height;
                        pairs.push({ diff: Math.abs(tT - targetSameY), snapVal: targetSameY, lineY: targetSameY });
                    }

                    pairs.forEach(p => {
                        if (p.diff <= SNAP_THRESH && p.diff < minDiffY) {
                            const newH = fixedBottom - p.snapVal;
                            if (newH >= 20) {
                                minDiffY = p.diff;
                                bestSnapY = { edge: 'top', y: p.snapVal, h: newH };
                                guideYObj = {
                                    type: 'horizontal',
                                    y: p.lineY,
                                    minX: Math.max(0, Math.min(tL, oL) - 15),
                                    maxX: Math.max(tR, oR) + 15
                                };
                            }
                        }
                    });
                }
            });

            // Apply best snaps
            if (bestSnapX) {
                if (bestSnapX.edge === 'right') {
                    resultW = bestSnapX.w;
                } else if (bestSnapX.edge === 'left') {
                    resultX = bestSnapX.x;
                    resultW = bestSnapX.w;
                }
            }

            if (bestSnapY) {
                if (bestSnapY.edge === 'bottom') {
                    resultH = bestSnapY.h;
                } else if (bestSnapY.edge === 'top') {
                    resultY = bestSnapY.y;
                    resultH = bestSnapY.h;
                }
            }

            return {
                x: resultX,
                y: resultY,
                w: resultW,
                h: resultH,
                guideX: guideXObj,
                guideY: guideYObj
            };
        },

        drawMultiSelectBadge() {
            const ctx = this.ctx;
            ctx.save();
            ctx.fillStyle = '#8b5cf6';
            ctx.fillRect(20, 48, 290, 26);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 11px Inter, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(`✨ ${this.selectedLocations.length} Objek Terseleksi (Shift Multi-Select)`, 30, 65);
            ctx.restore();
        },

        drawFloorGrid(w, h) {
            this.ctx.fillStyle = '#0f172a';
            this.ctx.fillRect(0, 0, w, h);

            this.ctx.strokeStyle = '#1e293b';
            this.ctx.lineWidth = 1;
            for (let x = 0; x < w; x += 40) {
                this.ctx.beginPath();
                this.ctx.moveTo(x, 0);
                this.ctx.lineTo(x, h);
                this.ctx.stroke();
            }
            for (let y = 0; y < h; y += 40) {
                this.ctx.beginPath();
                this.ctx.moveTo(0, y);
                this.ctx.lineTo(w, y);
                this.ctx.stroke();
            }
        },

        drawHeaderBanner() {
            const ctx = this.ctx;
            const bannerW = Math.max(300, (this.canvas ? this.canvas.width : 950) - 40);
            const centerX = (this.canvas ? this.canvas.width : 950) / 2;

            ctx.fillStyle = '#1e293b';
            ctx.fillRect(20, 10, bannerW, 30);
            ctx.fillStyle = '#fbbf24';
            ctx.font = 'bold 14px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('LAYOUT RAK GUDANG DOKUMEN PT INDRACO', centerX, 30);
        },

        drawRoomObject(loc, isFilteredOut) {
            const ctx = this.ctx;
            const x = loc.canvas_x;
            const y = loc.canvas_y;
            const w = loc.canvas_width;
            const h = loc.canvas_height;

            const isSelected = this.selectedLocations.some(l => l.id === loc.id);
            const isHovered = (this.hoveredLocation && this.hoveredLocation.id === loc.id);

            ctx.save();
            // Background fill with custom color or slate
            const roomBg = loc.custom_color || '#1e293b';
            ctx.fillStyle = roomBg;
            ctx.globalAlpha = isFilteredOut ? 0.2 : 0.4;
            ctx.fillRect(x, y, w, h);

            // Wall Border
            ctx.globalAlpha = 1.0;
            ctx.lineWidth = isSelected ? 4 : (isHovered ? 3 : 2);
            ctx.strokeStyle = isSelected ? '#ffffff' : (isHovered ? '#fbbf24' : (loc.custom_color || '#64748b'));
            ctx.strokeRect(x, y, w, h);

            // Room Header Title
            ctx.fillStyle = loc.custom_color || '#334155';
            ctx.fillRect(x, y, w, 24);
            ctx.strokeRect(x, y, w, 24);

            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 11px Inter, sans-serif';
            ctx.textAlign = 'center';
            const lockPrefix = (loc.is_locked !== false) ? '🔒 ' : '🔓 ';
            ctx.fillText(lockPrefix + (loc.rack_code || loc.room_sector), x + (w / 2), y + 16);

            ctx.restore();
        },

        drawRackObject(loc, isFilteredOut) {
            const ctx = this.ctx;

            const x = loc.canvas_x;
            const y = loc.canvas_y;
            const w = loc.canvas_width;
            const h = loc.canvas_height;

            const isSelected = this.selectedLocations.some(l => l.id === loc.id);
            const isHovered = (this.hoveredLocation && this.hoveredLocation.id === loc.id);

            let fillColor = loc.custom_color || loc.status_color || '#10b981';
            if (isFilteredOut) {
                fillColor = '#334155';
            }

            ctx.save();
            ctx.fillStyle = fillColor;
            ctx.globalAlpha = isFilteredOut ? 0.3 : 0.85;
            ctx.fillRect(x, y, w, h);

            ctx.globalAlpha = 1.0;
            if (isSelected) {
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 4;
            } else if (isHovered) {
                ctx.strokeStyle = '#fbbf24';
                ctx.lineWidth = 3;
            } else {
                ctx.strokeStyle = '#0f172a';
                ctx.lineWidth = 2;
            }
            ctx.strokeRect(x, y, w, h);

            if (!isFilteredOut) {
                ctx.save();
                // 1. Strictly clip text inside inner rack boundaries
                ctx.beginPath();
                ctx.rect(x + 2, y + 2, Math.max(1, w - 4), Math.max(1, h - 4));
                ctx.clip();

                // 2. Aspect-ratio based orientation (Horizontal if width >= height, Vertical if height > width)
                const isHorizontal = (w >= h);
                const thickness = isHorizontal ? h : w;
                const length = isHorizontal ? w : h;

                // 3. Dynamic font size scaling based on rack thickness
                let fontSize = 9;
                if (thickness >= 35) fontSize = 11;
                else if (thickness >= 24) fontSize = 9;
                else fontSize = 8;

                ctx.fillStyle = '#ffffff';
                ctx.shadowColor = 'rgba(0, 0, 0, 0.95)';
                ctx.shadowBlur = 4;

                // 4. Smart text formatting based on available length
                let textStr = loc.rack_code || 'RAK';
                if (length >= 95) {
                    textStr += ' (' + loc.current_box_count + '/' + loc.box_capacity + ')';
                } else if (length >= 55) {
                    textStr += ' [' + loc.capacity_percentage + '%]';
                }

                if (isHorizontal) {
                    ctx.font = `bold ${fontSize}px Inter, sans-serif`;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(textStr, x + (w / 2), y + (h / 2));
                } else {
                    ctx.translate(x + (w / 2), y + (h / 2));
                    ctx.rotate(-Math.PI / 2);
                    ctx.font = `bold ${fontSize}px Inter, sans-serif`;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(textStr, 0, 0);
                }

                ctx.restore();
            }

            ctx.restore();
        },

        drawSelectionHandles(loc) {
            const ctx = this.ctx;
            const x = loc.canvas_x;
            const y = loc.canvas_y;
            const w = loc.canvas_width;
            const h = loc.canvas_height;

            ctx.save();
            // Dashed outline box
            ctx.strokeStyle = '#fbbf24';
            ctx.lineWidth = 2;
            ctx.setLineDash([6, 4]);
            ctx.strokeRect(x - 3, y - 3, w + 6, h + 6);

            // 1. Protruding Top Rotation Handle Stem & Circular Dot
            const centerX = x + (w / 2);
            const rotY = y - 24;

            ctx.strokeStyle = '#8b5cf6';
            ctx.lineWidth = 2;
            ctx.setLineDash([]);
            ctx.beginPath();
            ctx.moveTo(centerX, y - 3);
            ctx.lineTo(centerX, rotY);
            ctx.stroke();

            // Circular Rotation Handle Dot
            ctx.fillStyle = '#8b5cf6';
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(centerX, rotY, 7, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();

            // Rotate symbol icon inside handle dot
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 9px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('↻', centerX, rotY + 3);

            // 1b. Protruding Lock / Gembok Handle Dot (for Gudang Room & Rak objects)
            if (loc) {
                const lockX = x + w - 15;
                const lockY = y - 24;
                const isLocked = loc.is_locked !== false;

                // Stem line
                ctx.strokeStyle = isLocked ? '#ef4444' : '#10b981';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(lockX, y - 3);
                ctx.lineTo(lockX, lockY);
                ctx.stroke();

                // Circular Lock Handle Dot
                ctx.fillStyle = isLocked ? '#ef4444' : '#10b981';
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.arc(lockX, lockY, 10, 0, Math.PI * 2);
                ctx.fill();
                ctx.stroke();

                // Lock emoji symbol inside handle dot
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 11px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(isLocked ? '🔒' : '🔓', lockX, lockY + 4);
            }

            // 2. 4 Corner Resize Handles (Square Blue/Amber Dots)
            const handleSize = 8;
            ctx.fillStyle = '#3b82f6';
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;

            const corners = [
                { x: x - 4, y: y - 4 },                 // Top-Left (NW)
                { x: x + w - 4, y: y - 4 },             // Top-Right (NE)
                { x: x + w - 4, y: y + h - 4 },         // Bottom-Right (SE)
                { x: x - 4, y: y + h - 4 }              // Bottom-Left (SW)
            ];

            corners.forEach(c => {
                ctx.fillRect(c.x, c.y, handleSize, handleSize);
                ctx.strokeRect(c.x, c.y, handleSize, handleSize);
            });

            ctx.restore();
        },

        // Canvas Mouse Events: Click, Drag, & Corner Handles Resize
        handleCanvasMouseDown(e) {
            this.contextMenuOpen = false;
            const rect = this.canvas.getBoundingClientRect();
            const mouseX = (e.clientX - rect.left) / this.scale;
            const mouseY = (e.clientY - rect.top) / this.scale;

            if (this.selectedLocation) {
                const loc = this.selectedLocation;
                const x = loc.canvas_x;
                const y = loc.canvas_y;
                const w = loc.canvas_width;
                const h = loc.canvas_height;

                // 0a. Lock Handle Hit Testing (for Gudang Room & Rak objects)
                if (loc) {
                    const lockCenterX = x + w - 15;
                    const lockCenterY = y - 24;
                    if (Math.hypot(mouseX - lockCenterX, mouseY - lockCenterY) <= 12) {
                        this.toggleLockLocation(loc);
                        return;
                    }
                }

                // 0b. Rotation Handle Hit Testing (Protruding 24px above top center)
                const rotCenterX = x + (w / 2);
                const rotCenterY = y - 24;
                if (Math.hypot(mouseX - rotCenterX, mouseY - rotCenterY) <= 12) {
                    this.rotateLocation(loc, 90);
                    return;
                }

                // 1. Corner Hit Testing
                if (Math.abs(mouseX - (x + w)) < 12 && Math.abs(mouseY - (y + h)) < 12) {
                    this.startDrag(loc, 'resize-se', mouseX, mouseY);
                    return;
                }
                if (Math.abs(mouseX - x) < 12 && Math.abs(mouseY - y) < 12) {
                    this.startDrag(loc, 'resize-nw', mouseX, mouseY);
                    return;
                }
                if (Math.abs(mouseX - (x + w)) < 12 && Math.abs(mouseY - y) < 12) {
                    this.startDrag(loc, 'resize-ne', mouseX, mouseY);
                    return;
                }
                if (Math.abs(mouseX - x) < 12 && Math.abs(mouseY - (y + h)) < 12) {
                    this.startDrag(loc, 'resize-sw', mouseX, mouseY);
                    return;
                }
            }

            // 2. Check if user clicked inside body of any location object (Rack prioritized over Room)
            const clicked = this.getObjectAtPosition(mouseX, mouseY);

            if (clicked) {
                if (e.shiftKey) {
                    // Shift + Click: Multi-select toggle
                    const existsIdx = this.selectedLocations.findIndex(l => l.id === clicked.id);
                    if (existsIdx >= 0) {
                        this.selectedLocations.splice(existsIdx, 1);
                    } else {
                        this.selectedLocations.push(clicked);
                    }
                } else {
                    const isAlreadySelected = this.selectedLocations.some(l => l.id === clicked.id);
                    if (!isAlreadySelected || this.selectedLocations.length <= 1) {
                        this.selectedLocations = [clicked];
                    }
                }
                if (this.selectedLocations.some(l => l.id === clicked.id)) {
                    this.startDrag(clicked, 'move', mouseX, mouseY);
                }
            } else {
                if (!e.shiftKey) {
                    this.selectedLocations = [];
                }
            }
            this.renderCanvas();
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        startDrag(target, mode, mouseX, mouseY) {
            if (target.is_locked !== false) {
                const typeLabel = target.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${target.rack_code}' sedang TERKUNCI. Klik logo gembok (🔒) pada canvas untuk membuka kuncinya.`);
                return;
            }

            this.isDragging = true;
            this.dragMode = mode;
            this.draggedTarget = target;
            this.dragStartX = mouseX;
            this.dragStartY = mouseY;
            this.initialX = target.canvas_x;
            this.initialY = target.canvas_y;
            this.initialW = target.canvas_width;
            this.initialH = target.canvas_height;

            if (this.canvas) {
                if (mode === 'resize-se' || mode === 'resize-nw') {
                    this.canvas.style.cursor = 'nwse-resize';
                } else if (mode === 'resize-ne' || mode === 'resize-sw') {
                    this.canvas.style.cursor = 'nesw-resize';
                } else if (mode === 'move') {
                    this.canvas.style.cursor = 'move';
                }
            }

            // Track initial positions for group move
            this.draggedGroupPositions = this.selectedLocations.map(loc => ({
                loc: loc,
                initX: loc.canvas_x,
                initY: loc.canvas_y
            }));

            // If moving a Gudang (room) object, track all child racks inside it
            this.draggedChildRacks = [];
            if (target.location_type === 'room' && mode === 'move') {
                const roomSectorName = target.room_sector || target.rack_code;
                const childRacks = this.locations.filter(loc => {
                    if (loc.location_type === 'room' || loc.id === target.id) return false;
                    const isSectorMatch = loc.room_sector === roomSectorName || loc.room_sector === target.rack_code;
                    const isInside = (loc.canvas_x >= target.canvas_x) && 
                                     ((loc.canvas_x + loc.canvas_width) <= (target.canvas_x + target.canvas_width)) &&
                                     (loc.canvas_y >= target.canvas_y) && 
                                     ((loc.canvas_y + loc.canvas_height) <= (target.canvas_y + target.canvas_height));
                    return isSectorMatch || isInside;
                });

                this.draggedChildRacks = childRacks.map(r => ({
                    rak: r,
                    initX: r.canvas_x,
                    initY: r.canvas_y
                }));
            }
        },

        handleCanvasMouseMove(e) {
            const rect = this.canvas.getBoundingClientRect();
            const mouseX = (e.clientX - rect.left) / this.scale;
            const mouseY = (e.clientY - rect.top) / this.scale;

            if (this.isDragging && this.draggedTarget) {
                const deltaX = Math.round(mouseX - this.dragStartX);
                const deltaY = Math.round(mouseY - this.dragStartY);
                const target = this.draggedTarget;

                // Find parent room if this target is a rack inside a Gudang room
                let parentRoom = null;
                if (target.location_type !== 'room') {
                    parentRoom = this.locations.find(loc => {
                        if (loc.location_type !== 'room') return false;
                        const isSectorMatch = loc.room_sector === target.room_sector || loc.rack_code === target.room_sector;
                        const isInsideInit = (this.initialX >= loc.canvas_x) && 
                                             ((this.initialX + this.initialW) <= (loc.canvas_x + loc.canvas_width)) &&
                                             (this.initialY >= loc.canvas_y) && 
                                             ((this.initialY + this.initialH) <= (loc.canvas_y + loc.canvas_height));
                        return isSectorMatch || isInsideInit;
                    });
                }

                // Parent room boundary limits
                const minX = parentRoom ? parentRoom.canvas_x + 4 : 0;
                const minY = parentRoom ? parentRoom.canvas_y + 26 : 0;
                const maxCanvasW = this.canvas ? (this.canvas.width - target.canvas_width - 4) : 950;
                const maxCanvasH = this.canvas ? (this.canvas.height - target.canvas_height - 4) : 750;
                const maxX = parentRoom ? (parentRoom.canvas_x + parentRoom.canvas_width - target.canvas_width - 4) : maxCanvasW;
                const maxY = parentRoom ? (parentRoom.canvas_y + parentRoom.canvas_height - target.canvas_height - 4) : maxCanvasH;

                const isHorizontalRack = (target.location_type !== 'room') && (target.canvas_width > target.canvas_height || target.orientation === 'horizontal');
                const minAllowedW = (target.location_type === 'room') ? 50 : (isHorizontalRack ? 100 : 25);
                const minAllowedH = (target.location_type === 'room') ? 50 : (isHorizontalRack ? 25 : 100);

                if (this.dragMode === 'move') {
                    if (this.draggedGroupPositions && this.draggedGroupPositions.length > 1) {
                        const groupIds = this.selectedLocations.map(l => l.id);
                        this.draggedGroupPositions.forEach(item => {
                            let newX = Math.max(0, item.initX + deltaX);
                            let newY = Math.max(0, item.initY + deltaY);

                            if (item.loc.location_type !== 'room') {
                                if (!this.hasRackCollision(item.loc, newX, item.loc.canvas_y, item.loc.canvas_width, item.loc.canvas_height, groupIds)) {
                                    item.loc.canvas_x = newX;
                                }
                                if (!this.hasRackCollision(item.loc, item.loc.canvas_x, newY, item.loc.canvas_width, item.loc.canvas_height, groupIds)) {
                                    item.loc.canvas_y = newY;
                                }
                            } else {
                                item.loc.canvas_x = newX;
                                item.loc.canvas_y = newY;
                            }
                        });

                        // Apply snap & alignment guidance for group primary target
                        const origX = target.canvas_x;
                        const origY = target.canvas_y;
                        this.calculateSnapAndGuides(target);
                        const groupSnapShiftX = target.canvas_x - origX;
                        const groupSnapShiftY = target.canvas_y - origY;

                        if (groupSnapShiftX !== 0 || groupSnapShiftY !== 0) {
                            this.draggedGroupPositions.forEach(item => {
                                if (item.loc.id !== target.id) {
                                    item.loc.canvas_x += groupSnapShiftX;
                                    item.loc.canvas_y += groupSnapShiftY;
                                }
                            });
                        }
                    } else {
                        let newX = this.initialX + deltaX;
                        let newY = this.initialY + deltaY;

                        if (parentRoom) {
                            newX = Math.max(minX, Math.min(maxX, newX));
                            newY = Math.max(minY, Math.min(maxY, newY));
                        } else {
                            newX = Math.max(0, newX);
                            newY = Math.max(0, newY);
                        }

                        if (target.location_type !== 'room') {
                            // Test horizontal movement collision
                            if (!this.hasRackCollision(target, newX, target.canvas_y, target.canvas_width, target.canvas_height)) {
                                target.canvas_x = newX;
                            }
                            // Test vertical movement collision
                            if (!this.hasRackCollision(target, target.canvas_x, newY, target.canvas_width, target.canvas_height)) {
                                target.canvas_y = newY;
                            }
                        } else {
                            target.canvas_x = newX;
                            target.canvas_y = newY;

                            // If moving a Gudang room, move all child racks inside it synchronously
                            if (this.draggedChildRacks && this.draggedChildRacks.length > 0) {
                                this.draggedChildRacks.forEach(c => {
                                    c.rak.canvas_x = Math.max(0, c.initX + deltaX);
                                    c.rak.canvas_y = Math.max(0, c.initY + deltaY);
                                });
                            }
                        }

                        // Apply smart snap & alignment guidance for target
                        this.calculateSnapAndGuides(target);
                    }
                } else if (['resize-se', 'resize-nw', 'resize-ne', 'resize-sw'].includes(this.dragMode)) {
                    let propW = this.initialW;
                    let propH = this.initialH;
                    let propX = this.initialX;
                    let propY = this.initialY;

                    const effectiveMinW = (target.location_type === 'room') ? 50 : 20;
                    const effectiveMinH = (target.location_type === 'room') ? 50 : 20;

                    if (this.dragMode === 'resize-se') {
                        propW = Math.max(effectiveMinW, this.initialW + deltaX);
                        propH = Math.max(effectiveMinH, this.initialH + deltaY);
                        propX = this.initialX;
                        propY = this.initialY;
                    } else if (this.dragMode === 'resize-nw') {
                        propW = Math.max(effectiveMinW, this.initialW - deltaX);
                        propH = Math.max(effectiveMinH, this.initialH - deltaY);
                        propX = this.initialX + (this.initialW - propW);
                        propY = this.initialY + (this.initialH - propH);
                    } else if (this.dragMode === 'resize-ne') {
                        propW = Math.max(effectiveMinW, this.initialW + deltaX);
                        propH = Math.max(effectiveMinH, this.initialH - deltaY);
                        propX = this.initialX;
                        propY = this.initialY + (this.initialH - propH);
                    } else if (this.dragMode === 'resize-sw') {
                        propW = Math.max(effectiveMinW, this.initialW - deltaX);
                        propH = Math.max(effectiveMinH, this.initialH + deltaY);
                        propX = this.initialX + (this.initialW - propW);
                        propY = this.initialY;
                    }

                    // Calculate Smart Magnetic Snapping & Guidance Lines
                    const snapRes = this.calculateResizeSnapAndGuides(target, this.dragMode, propX, propY, propW, propH, parentRoom);

                    let finalX = snapRes.x;
                    let finalY = snapRes.y;
                    let finalW = snapRes.w;
                    let finalH = snapRes.h;

                    // Parent Room Boundaries
                    if (parentRoom) {
                        const roomMinX = parentRoom.canvas_x + 4;
                        const roomMinY = parentRoom.canvas_y + 26;
                        const roomMaxRight = parentRoom.canvas_x + parentRoom.canvas_width - 4;
                        const roomMaxBottom = parentRoom.canvas_y + parentRoom.canvas_height - 4;

                        if (finalX < roomMinX) {
                            finalW -= (roomMinX - finalX);
                            finalX = roomMinX;
                        }
                        if (finalY < roomMinY) {
                            finalH -= (roomMinY - finalY);
                            finalY = roomMinY;
                        }
                        if (finalX + finalW > roomMaxRight) {
                            finalW = Math.max(effectiveMinW, roomMaxRight - finalX);
                        }
                        if (finalY + finalH > roomMaxBottom) {
                            finalH = Math.max(effectiveMinH, roomMaxBottom - finalY);
                        }
                    } else {
                        // Canvas Boundaries
                        if (finalX < 0) {
                            finalW += finalX;
                            finalX = 0;
                        }
                        if (finalY < 0) {
                            finalH += finalY;
                            finalY = 0;
                        }
                        const curCanvasW = this.canvas ? this.canvas.width : (this.canvasWidth || 950);
                        const curCanvasH = this.canvas ? this.canvas.height : (this.canvasHeight || 750);
                        if (finalX + finalW > curCanvasW) {
                            finalW = Math.max(effectiveMinW, curCanvasW - finalX);
                        }
                        if (finalY + finalH > curCanvasH) {
                            finalH = Math.max(effectiveMinH, curCanvasH - finalY);
                        }
                    }

                    finalW = Math.max(effectiveMinW, finalW);
                    finalH = Math.max(effectiveMinH, finalH);

                    // Collision Detection (for racks)
                    this.activeGuideLines = [];

                    if (target.location_type !== 'room') {
                        // Horizontal (X & W)
                        if (!this.hasRackCollision(target, finalX, target.canvas_y, finalW, target.canvas_height)) {
                            target.canvas_x = finalX;
                            target.canvas_width = finalW;
                            if (snapRes.guideX) this.activeGuideLines.push(snapRes.guideX);
                        }
                        // Vertical (Y & H)
                        if (!this.hasRackCollision(target, target.canvas_x, finalY, target.canvas_width, finalH)) {
                            target.canvas_y = finalY;
                            target.canvas_height = finalH;
                            if (snapRes.guideY) this.activeGuideLines.push(snapRes.guideY);
                        }
                    } else {
                        // Gudang room
                        target.canvas_x = finalX;
                        target.canvas_y = finalY;
                        target.canvas_width = finalW;
                        target.canvas_height = finalH;
                        if (snapRes.guideX) this.activeGuideLines.push(snapRes.guideX);
                        if (snapRes.guideY) this.activeGuideLines.push(snapRes.guideY);
                    }

                    this.renderCanvas();
                    return;
                }
            }

            // Hover Cursor Management (Handles, Objects & Default)
            const hovered = this.getObjectAtPosition(mouseX, mouseY);

            if (this.canvas) {
                if (this.selectedLocation) {
                    const loc = this.selectedLocation;
                    const x = loc.canvas_x;
                    const y = loc.canvas_y;
                    const w = loc.canvas_width;
                    const h = loc.canvas_height;

                    if (Math.abs(mouseX - (x + w)) < 12 && Math.abs(mouseY - (y + h)) < 12) {
                        this.canvas.style.cursor = 'nwse-resize';
                    } else if (Math.abs(mouseX - x) < 12 && Math.abs(mouseY - y) < 12) {
                        this.canvas.style.cursor = 'nwse-resize';
                    } else if (Math.abs(mouseX - (x + w)) < 12 && Math.abs(mouseY - y) < 12) {
                        this.canvas.style.cursor = 'nesw-resize';
                    } else if (Math.abs(mouseX - x) < 12 && Math.abs(mouseY - (y + h)) < 12) {
                        this.canvas.style.cursor = 'nesw-resize';
                    } else if (Math.hypot(mouseX - (x + w / 2), mouseY - (y - 24)) <= 12) {
                        this.canvas.style.cursor = 'grab';
                    } else if (Math.hypot(mouseX - (x + w - 15), mouseY - (y - 24)) <= 12) {
                        this.canvas.style.cursor = 'pointer';
                    } else if (hovered) {
                        this.canvas.style.cursor = 'move';
                    } else {
                        this.canvas.style.cursor = 'crosshair';
                    }
                } else if (hovered) {
                    this.canvas.style.cursor = 'pointer';
                } else {
                    this.canvas.style.cursor = 'crosshair';
                }
            }

            if (hovered !== this.hoveredLocation) {
                this.hoveredLocation = hovered;
                this.renderCanvas();
            }
        },

        async handleCanvasMouseUp(e) {
            if (this.isDragging && this.draggedTarget) {
                const childRacksToSave = this.draggedChildRacks || [];

                this.isDragging = false;
                this.dragMode = null;
                this.draggedTarget = null;
                this.draggedChildRacks = [];
                this.activeGuideLines = [];
                if (this.canvas) this.canvas.style.cursor = 'crosshair';
                this.renderCanvas();

                // Save positions for all selected locations
                for (const loc of this.selectedLocations) {
                    try {
                        await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                rack_code: loc.rack_code,
                                location_type: loc.location_type,
                                room_sector: loc.room_sector,
                                canvas_x: loc.canvas_x,
                                canvas_y: loc.canvas_y,
                                canvas_width: loc.canvas_width,
                                canvas_height: loc.canvas_height,
                            })
                        });
                    } catch (err) {
                        console.error('Failed to auto-save canvas coordinates:', err);
                    }
                }

                // Save child racks if room was dragged
                if (childRacksToSave.length > 0) {
                    for (const c of childRacksToSave) {
                        try {
                            await fetch(`/api/warehouse/locations/${c.rak.id}/update`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    rack_code: c.rak.rack_code,
                                    location_type: c.rak.location_type,
                                    room_sector: c.rak.room_sector,
                                    canvas_x: c.rak.canvas_x,
                                    canvas_y: c.rak.canvas_y,
                                    canvas_width: c.rak.canvas_width,
                                    canvas_height: c.rak.canvas_height,
                                })
                            });
                        } catch (err) {
                            console.error('Failed to save child rack position:', err);
                        }
                    }
                }
            }
        },

        async pasteCopiedLocations() {
            if (!this.copiedLocations || this.copiedLocations.length === 0) return;

            const newPastedLocations = [];
            for (const sourceObj of this.copiedLocations) {
                const cleanBase = (sourceObj.rack_code || 'OBJECT').replace(/ \(COPY\d*\)$/i, '').replace(/ - COPY\d*$/i, '');
                let copyIndex = 1;
                let newCode = `${cleanBase} (COPY)`;
                while (this.locations.some(l => l.rack_code === newCode) || newPastedLocations.some(l => l.rack_code === newCode)) {
                    copyIndex++;
                    newCode = `${cleanBase} (COPY ${copyIndex})`;
                }

                let newX = sourceObj.canvas_x + 25;
                let newY = sourceObj.canvas_y + 25;

                const duplicateData = {
                    location_type: sourceObj.location_type || 'rack',
                    rack_code: newCode,
                    room_sector: sourceObj.room_sector || 'Umum',
                    shelf_code: sourceObj.shelf_code || 'BARIS-01',
                    box_capacity: sourceObj.box_capacity || 100,
                    custom_color: sourceObj.custom_color || '',
                    canvas_x: newX,
                    canvas_y: newY,
                    canvas_width: sourceObj.canvas_width || 40,
                    canvas_height: sourceObj.canvas_height || 120,
                    orientation: sourceObj.orientation || 'horizontal',
                    rotation_angle: sourceObj.rotation_angle || 0,
                    assigned_department_id: sourceObj.assigned_department_id || null,
                };

                try {
                    const res = await fetch('{{ route("api.warehouse.locations.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(duplicateData)
                    });
                    const data = await res.json();
                    if (data.success) {
                        newPastedLocations.push(data.location);
                    }
                } catch (err) {
                    console.error('Error pasting object:', err);
                }
            }

            if (newPastedLocations.length > 0) {
                this.showToast(`📋 ${newPastedLocations.length} object berhasil diduplikat / ditempel di canvas!`);
                await this.fetchData();
                this.selectedLocations = this.locations.filter(l => newPastedLocations.some(p => p.id === l.id));
                this.copiedLocations = JSON.parse(JSON.stringify(this.selectedLocations));
            }
        },

        openDocDetail(arc) {
            this.selectedDoc = arc;
            this.docModalOpen = true;
        },

        openBookingModal() {
            if (!this.selectedLocation) return;
            this.bookingForm.department_id = this.selectedLocation.assigned_department_id || '';
            this.bookingForm.booking_notes = '';
            this.bookingModalOpen = true;
        },

        async submitBooking() {
            if (!this.selectedLocation) return;
            try {
                const res = await fetch(`/api/warehouse/locations/${this.selectedLocation.id}/book`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.bookingForm)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.bookingModalOpen = false;
                    await this.fetchData();
                    this.selectedLocation = this.locations.find(l => l.id === this.selectedLocation.id);
                }
            } catch (err) {
                alert('Gagal memproses booking slot rak.');
            }
        },

        async unbookLocation() {
            if (!this.selectedLocation) return;
            if (!confirm('Lepas status booking slot rak ini?')) return;
            try {
                const res = await fetch(`/api/warehouse/locations/${this.selectedLocation.id}/unbook`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    await this.fetchData();
                    this.selectedLocation = this.locations.find(l => l.id === this.selectedLocation.id);
                }
            } catch (err) {
                alert('Gagal melepas status booking.');
            }
        },

        openEditModal() {
            if (!this.selectedLocation) return;
            this.editForm = {
                room_sector: this.selectedLocation.room_sector || 'R1',
                rack_code: this.selectedLocation.rack_code,
                shelf_code: this.selectedLocation.shelf_code,
                box_capacity: this.selectedLocation.box_capacity,
                assigned_department_id: this.selectedLocation.assigned_department_id || '',
                custom_color: this.selectedLocation.custom_color || '',
                canvas_width: this.selectedLocation.canvas_width,
                canvas_height: this.selectedLocation.canvas_height,
                orientation: this.selectedLocation.orientation || 'horizontal',
                rotation_angle: this.selectedLocation.rotation_angle || 0,
            };
            this.editModalOpen = true;
        },

        async submitEdit() {
            if (!this.selectedLocation) return;
            if (this.selectedLocation.location_type !== 'room') {
                const testW = parseInt(this.editForm.canvas_width);
                const testH = parseInt(this.editForm.canvas_height);
                const minDim = Math.min(testW, testH);
                const maxDim = Math.max(testW, testH);
                if (minDim < 25 || maxDim < 100) {
                    alert('Perhatian: Ukuran minimal objek Rak adalah Sisi Panjang minimal 100px dan Sisi Tebal/Lebar minimal 25px!');
                    return;
                }
                if (this.hasRackCollision(this.selectedLocation, this.selectedLocation.canvas_x, this.selectedLocation.canvas_y, testW, testH)) {
                    alert('Perhatian: Perubahan dimensi rak akan bertabrakan / tumpang tindih dengan rak lain di sekitarnya!');
                    return;
                }
            }
            try {
                const res = await fetch(`/api/warehouse/locations/${this.selectedLocation.id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.editForm)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.editModalOpen = false;
                    await this.fetchData();
                    this.selectedLocation = this.locations.find(l => l.id === this.selectedLocation.id);
                }
            } catch (err) {
                alert('Gagal mengedit data object.');
            }
        },

        async deleteSelectedLocation() {
            if (this.selectedLocations.length === 0) return;

            let confirmMsg = '';
            if (this.selectedLocations.length === 1) {
                confirmMsg = `Apakah Anda yakin ingin menghapus object '${this.selectedLocations[0].rack_code}' dari canvas layout gudang?`;
            } else {
                confirmMsg = `Apakah Anda yakin ingin menghapus ${this.selectedLocations.length} object terseleksi dari canvas layout gudang?`;
            }

            if (!confirm(confirmMsg)) return;

            const toDelete = [...this.selectedLocations];
            this.selectedLocations = [];

            for (const loc of toDelete) {
                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/delete`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                } catch (err) {
                    console.error('Error deleting location:', err);
                }
            }

            this.showToast(`🗑️ ${toDelete.length} object berhasil dihapus dari canvas layout.`);
            await this.fetchData();
        }
    }
}
</script>
@endpush
@endsection
