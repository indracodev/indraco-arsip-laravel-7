@extends('layouts.app')

@section('title', 'Master Gudang & Lokasi Rak - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="masterWarehousesManager()">

    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm font-mono">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2.5">
            <!-- Left Header Title Area -->
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded shrink-0">
                    <i data-lucide="warehouse" class="w-4 h-4"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate">Master Gudang & Slot Rak Storage</h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Pengelolaan Gedung Depo Gudang & Alokasi Kapasitas Slot Rak</p>
                </div>
            </div>

            <!-- Right Action Controls (Search & Action Buttons) -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Search Input -->
                <div class="relative flex-1 sm:w-56 min-w-[140px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                    <input 
                        type="text" 
                        x-model="searchQuery" 
                        placeholder="Cari gudang/rak... (Ctrl+F)" 
                        class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                    <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>

                <!-- Refresh Button (F5) -->
                <button @click="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0 cursor-pointer" title="Segarkan Data (F5)">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    <span>Refresh (F5)</span>
                </button>

                <!-- Add Warehouse (F2) -->
                <button @click="openAddWarehouse = true" type="button" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-white font-mono font-bold text-xs rounded border border-slate-900 shadow transition flex items-center gap-1 shrink-0 cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>+ Gudang (F2)</span>
                </button>

                <!-- Add Location Rak (F3) -->
                <button @click="openAddLocation = true" type="button" class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1 shrink-0 cursor-pointer">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>+ Slot Rak (F3)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- DELPHI WAREHOUSES & RACKS TDBGRID SPREADSHEET CONTAINER -->
    <div class="relative min-h-[200px]">
        <!-- Loading Overlay -->
        <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/70 dark:bg-slate-950/70 backdrop-blur-xs rounded z-10 flex items-center justify-center font-mono">
            <div class="flex items-center gap-2 text-xs font-bold text-amber-600 dark:text-amber-400">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Loading Warehouses...
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 font-sans">
            <template x-for="wh in filteredWarehouses" :key="wh.id">
                <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm space-y-3 flex flex-col justify-between">
                    <div>
                        <!-- Warehouse Header Panel -->
                        <div class="flex flex-wrap sm:flex-nowrap items-start justify-between bg-slate-100 dark:bg-slate-900 p-2.5 rounded border border-slate-200 dark:border-slate-800 font-mono gap-2">
                            <div class="space-y-1 min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 shrink-0" x-text="wh.code"></span>
                                    <h2 class="text-xs font-extrabold text-slate-900 dark:text-white truncate" :class="wh.is_active === false ? 'opacity-60 line-through' : ''" x-text="wh.name"></h2>
                                    
                                    <!-- Active / Inactive Toggle Switch Button -->
                                    <div class="inline-flex items-center gap-2 ml-1.5 shrink-0">
                                        @if(auth()->user()->isSuperAdmin())
                                        <button 
                                            type="button" 
                                            @click="toggleWarehouseActiveStatus(wh)" 
                                            :disabled="warehouseToggleLoading === wh.id"
                                            class="relative inline-flex items-center h-5 w-9 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors duration-200 ease-in-out focus:outline-none shadow-xs"
                                            :class="(wh.is_active !== false) ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-slate-300 dark:bg-slate-700 hover:bg-slate-400'"
                                            :title="(wh.is_active !== false) ? 'Gudang Aktif (Klik untuk Non-aktifkan)' : 'Gudang Non-Aktif (Klik untuk Aktifkan)'"
                                        >
                                            <span 
                                                class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                                :class="(wh.is_active !== false) ? 'translate-x-4' : 'translate-x-0'"
                                            ></span>
                                        </button>
                                        @else
                                        <div 
                                            class="relative inline-flex items-center h-5 w-9 shrink-0 opacity-60 cursor-not-allowed rounded-full p-0.5 transition-colors duration-200 ease-in-out shadow-xs"
                                            :class="(wh.is_active !== false) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"
                                            :title="(wh.is_active !== false) ? 'Gudang Aktif' : 'Gudang Non-Aktif'"
                                        >
                                            <span 
                                                class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                                :class="(wh.is_active !== false) ? 'translate-x-4' : 'translate-x-0'"
                                            ></span>
                                        </div>
                                        @endif
                                        <span 
                                            class="inline-block text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded font-mono border whitespace-nowrap select-none"
                                            :class="(wh.is_active !== false) ? 'text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-500/15 border-emerald-300 dark:border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 bg-slate-200 dark:bg-slate-800 border-slate-300 dark:border-slate-700'"
                                            x-text="(wh.is_active !== false) ? 'Aktif' : 'Non-Aktif'"
                                        ></span>
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1 font-medium mt-0.5 truncate">
                                    <i data-lucide="map-pin" class="w-3 h-3 text-slate-400 shrink-0"></i> <span class="truncate" x-text="wh.address || 'Alamat lokasi belum diisi'"></span>
                                </p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button @click="editWarehouseItem = Object.assign({}, wh)" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-bold transition flex items-center gap-1" title="Edit Gudang">
                                    <i data-lucide="edit-3" class="w-3 h-3 text-amber-500"></i> Edit
                                </button>
                                <form :action="'{{ url('/master/warehouses') }}/' + wh.id" method="POST" class="inline" @submit="submitting = true">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            data-confirm="Hapus gudang ini beserta seluruh datanya?" 
                                            data-confirm-title="Hapus Gudang"
                                            data-confirm-type="danger"
                                            data-confirm-btn="Ya, Hapus"
                                            class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[11px] font-bold transition flex items-center gap-1" title="Hapus Gudang">
                                        <i data-lucide="trash-2" class="w-3 h-3 text-rose-500"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Locations Table inside Warehouse -->
                        <div class="pt-2 space-y-2">
                            <div class="flex items-center justify-between font-mono text-[11px]">
                                <span class="font-bold text-slate-600 dark:text-slate-400 uppercase">Daftar Slot Rak & Baris:</span>
                                <span class="font-bold text-amber-600 dark:text-amber-400" x-text="(wh.locations ? wh.locations.length : 0) + ' Slot Terdaftar'"></span>
                            </div>
                            
                            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded">
                                <table class="w-full text-left border-collapse font-sans text-xs min-w-[340px]">
                                    <thead>
                                        <tr class="font-mono text-[10px] select-none bg-slate-100 dark:bg-slate-900 whitespace-nowrap">
                                            <th class="py-1.5 px-2">LOKASI RAK</th>
                                            <th class="py-1.5 px-2">KAPASITAS</th>
                                            <th class="py-1.5 px-2 text-right">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                        <template x-for="loc in (wh.locations || [])" :key="loc.id">
                                            <tr class="hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition group">
                                                <!-- Clickable Rack Location Code -->
                                                <td class="py-1.5 px-2 whitespace-nowrap">
                                                    <button 
                                                        type="button" 
                                                        @click="openRackSlotModal(loc)" 
                                                        class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-1.5 cursor-pointer text-left"
                                                        title="Klik untuk Buka Visualisasi Denah 100 Slot Rak"
                                                    >
                                                        <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-500 transition shrink-0"></i>
                                                        <span x-text="loc.full_location || (loc.rack_code + ' - ' + loc.shelf_code)"></span>
                                                    </button>
                                                </td>

                                                <!-- Clickable Capacity Badge (Opens 100-Slot Denah Rak Modal) -->
                                                <td class="py-1.5 px-2 font-mono text-[11px] whitespace-nowrap">
                                                    <button 
                                                        type="button" 
                                                        @click="openRackSlotModal(loc)" 
                                                        class="px-2 py-0.5 rounded bg-slate-100 hover:bg-indigo-500/15 dark:bg-slate-900 dark:hover:bg-indigo-500/25 border border-slate-300 hover:border-indigo-400 dark:border-slate-800 dark:hover:border-indigo-500/50 font-bold text-slate-800 hover:text-indigo-700 dark:text-slate-200 dark:hover:text-indigo-300 transition flex items-center gap-1.5 cursor-pointer shadow-2xs group/btn"
                                                        title="Klik untuk Buka Visualisasi Denah 100 Slot Rak"
                                                    >
                                                        <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="(loc.current_box_count > 0) ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                                                        <span x-text="(loc.current_box_count || 0) + ' / ' + loc.box_capacity + ' Box'"></span>
                                                        <i data-lucide="external-link" class="w-2.5 h-2.5 opacity-40 group-hover/btn:opacity-100 text-indigo-500 transition shrink-0"></i>
                                                    </button>
                                                </td>

                                                <!-- Action Buttons -->
                                                <td class="py-1.5 px-2 text-right font-mono whitespace-nowrap">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <!-- Tombol Edit Lokasi Rak -->
                                                        <button 
                                                            type="button" 
                                                            @click="editLocationItem = Object.assign({}, loc)" 
                                                            class="p-1 text-amber-600 hover:text-amber-800 hover:bg-amber-500/10 dark:text-amber-400 dark:hover:text-amber-300 rounded transition" 
                                                            title="Edit Data Rak"
                                                        >
                                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                        </button>

                                                        <!-- Tombol Buka Visualisasi Denah 100 Slot Rak -->
                                                        <button 
                                                            type="button" 
                                                            @click="openRackSlotModal(loc)" 
                                                            class="p-1 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-500/10 dark:text-indigo-400 dark:hover:text-indigo-300 rounded transition" 
                                                            title="Buka Visualisasi Denah Rak"
                                                        >
                                                            <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                                                        </button>

                                                        <!-- Tombol Hapus Lokasi Rak -->
                                                        <form :action="'{{ url('/master/warehouses/locations') }}/' + loc.id" method="POST" class="inline" @submit="submitting = true">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    data-confirm="Hapus lokasi rak ini?" 
                                                                    data-confirm-title="Hapus Lokasi Rak"
                                                                    data-confirm-type="danger"
                                                                    data-confirm-btn="Ya, Hapus"
                                                                    class="p-1 text-rose-500 hover:bg-rose-500/10 rounded transition" title="Hapus Rak">
                                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="!wh.locations || wh.locations.length === 0">
                                            <td colspan="3" class="py-3 text-center text-slate-500 font-mono text-[11px]">Belum ada lokasi rak terdaftar.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="filteredWarehouses.length === 0" class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-6 text-center text-slate-500 font-mono text-xs">
            Tidak ada data gudang atau lokasi rak yang cocok dengan kata kunci pencarian.
        </div>
    </div>

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
                            <template x-if="selectedRackForModal?.is_fat_locked">
                                <span class="px-2 py-0.5 bg-rose-100 dark:bg-rose-500/20 border border-rose-200 dark:border-rose-500/40 text-rose-700 dark:text-rose-300 text-[10px] font-bold rounded flex items-center gap-1">
                                    <i data-lucide="lock" class="w-3 h-3 text-rose-500 dark:text-rose-400"></i> RUANGAN KHUSUS FAT
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
                                                                <span class="font-mono font-bold text-[10px] leading-tight block truncate text-amber-700 dark:text-amber-300 max-w-[80px] mx-auto" x-text="slot.archive.box_number || 'TERISI'"></span>
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
                                                                <span class="font-mono font-bold text-[10px] leading-tight block truncate text-amber-700 dark:text-amber-300 max-w-[80px] mx-auto" x-text="slot.archive.box_number || 'TERISI'"></span>
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
                            Klik salah satu dari 100 kotak slot kardus (TB 30g) pada denah di sebelah kiri untuk melihat rincian dokumen dan opsi cetak label.
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
                                        <span class="font-mono text-xs" :class="(selectedSlotDetail.status === 'expired' || selectedSlotDetail.archive.is_expired) ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-800 dark:text-slate-200'" x-text="selectedSlotDetail.archive.retention_expiry_date || '-'"></span>
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

                                    <a 
                                        :href="'/archives/print-labels?archive_id=' + selectedSlotDetail.archive.id" 
                                        target="_blank"
                                        class="w-full py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-amber-700 dark:text-amber-300 font-bold text-xs rounded-lg transition flex items-center justify-center gap-1.5 border border-slate-300 dark:border-slate-700"
                                    >
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        <span>Cetak Label Box Form A5 (TB 30g)</span>
                                    </a>

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

    <!-- WINDOWS FORM DIALOG MODAL 1: ADD WAREHOUSE -->
    <div x-show="openAddWarehouse" x-cloak 
         x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
         @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
            <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="plus" class="w-3.5 h-3.5 text-amber-400"></i> frmWarehouseAdd : Tambah Gedung Gudang Baru</span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAddWarehouse = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.warehouses.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-xs">Data Form Gudang</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE GUDANG (CTH: GUDANG-C)</label>
                        <input type="text" name="code" required placeholder="GUDANG-C" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA GUDANG</label>
                        <input type="text" name="name" required placeholder="Gudang Depo Gedangan Blok C" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ALAMAT LOKASI GUDANG</label>
                        <textarea name="address" rows="2" placeholder="Kawasan Industri Indraco..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAddWarehouse = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Memproses...' : 'Simpan Gudang (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 2: EDIT WAREHOUSE -->
    <template x-if="editWarehouseItem">
        <div x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
             @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
                <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmWarehouseEdit : Edit Data Gudang</span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editWarehouseItem = null; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/warehouses') }}/' + editWarehouseItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-xs">Form Perubahan Gudang</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE GUDANG</label>
                            <input type="text" name="code" :value="editWarehouseItem.code" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA GUDANG</label>
                            <input type="text" name="name" :value="editWarehouseItem.name" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ALAMAT LOKASI</label>
                            <textarea name="address" rows="2" x-text="editWarehouseItem.address" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editWarehouseItem = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Gudang (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- WINDOWS FORM DIALOG MODAL 3: ADD RACK LOCATION -->
    <div x-show="openAddLocation" x-cloak 
         x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
         @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
            <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="plus-circle" class="w-3.5 h-3.5 text-emerald-400"></i> frmLocationAdd : Tambah Slot Rak Gudang</span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAddLocation = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.warehouses.locations.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-xs">Data Form Slot Rak</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">PILIH GUDANG TARGET</label>
                        <select name="warehouse_id" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-bold">
                            <template x-for="wh in warehouses" :key="wh.id">
                                <option :value="wh.id" x-text="wh.code + ' - ' + wh.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE RAK</label>
                            <input type="text" name="rack_code" required placeholder="RAK-A3" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white uppercase focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE BARIS/SHELF</label>
                            <input type="text" name="shelf_code" required placeholder="BARIS-01" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white uppercase focus:outline-none focus:border-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KAPASITAS MAKSIMAL BOX</label>
                        <input type="number" name="box_capacity" value="100" min="1" max="1000" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAddLocation = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Menambahkan...' : 'Tambah Rak (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 4: EDIT RACK LOCATION -->
    <template x-if="editLocationItem">
        <div x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
             @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
                <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmLocationEdit : Edit Data Slot Rak</span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editLocationItem = null; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/warehouses/locations') }}/' + editLocationItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-xs">Form Perubahan Slot Rak</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">GUDANG / RUANGAN TARGET</label>
                            <select name="warehouse_id" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-bold">
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" :selected="wh.id == editLocationItem.warehouse_id" x-text="wh.code + ' - ' + wh.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE RAK</label>
                                <input type="text" name="rack_code" :value="editLocationItem.rack_code" required placeholder="RAK-A1" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white uppercase focus:outline-none focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE BARIS/SHELF</label>
                                <input type="text" name="shelf_code" :value="editLocationItem.shelf_code" required placeholder="BARIS-01" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white uppercase focus:outline-none focus:border-amber-500">
                            </div>
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KAPASITAS MAKSIMAL BOX</label>
                            <input type="number" name="box_capacity" :value="editLocationItem.box_capacity" min="1" max="5000" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editLocationItem = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Rak (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
function masterWarehousesManager() {
    return {
        openAddWarehouse: false,
        openAddLocation: false,
        editWarehouseItem: null,
        editLocationItem: null,
        searchQuery: '',
        sortByField: 'code',
        sortDirection: 'asc',
        submitting: false,
        isLoading: false,
        warehouses: @json($warehouses),

        init() {
            this.$watch('editLocationItem', (val) => {
                if (val) {
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                }
            });
            this.$watch('editWarehouseItem', (val) => {
                if (val) {
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                }
            });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        // 100-Box Denah Rak Modal State
        rackGridModalOpen: false,
        selectedRackForModal: null,
        selectedSlotDetail: null,
        slotFilterStatus: 'all',
        slotSearchQuery: '',
        slotDataLoading: false,
        slotAssignLoading: false,
        warehouseToggleLoading: null,
        cachedLayoutData: null,

        get filteredWarehouses() {
            let res = [...this.warehouses];
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(wh => {
                    const matchWh = (wh.code && wh.code.toLowerCase().includes(q)) ||
                                    (wh.name && wh.name.toLowerCase().includes(q)) ||
                                    (wh.address && wh.address.toLowerCase().includes(q));
                    const matchLoc = wh.locations && wh.locations.some(loc => 
                        (loc.rack_code && loc.rack_code.toLowerCase().includes(q)) ||
                        (loc.shelf_code && loc.shelf_code.toLowerCase().includes(q)) ||
                        (loc.full_location && loc.full_location.toLowerCase().includes(q))
                    );
                    return matchWh || matchLoc;
                });
            }
            res.sort((a, b) => {
                let valA = a[this.sortByField] ?? '';
                let valB = b[this.sortByField] ?? '';
                if (this.sortByField === 'locations_count') {
                    valA = a.locations ? a.locations.length : 0;
                    valB = b.locations ? b.locations.length : 0;
                    return this.sortDirection === 'asc' ? valA - valB : valB - valA;
                }
                if (typeof valA === 'string') valA = valA.toLowerCase();
                if (typeof valB === 'string') valB = valB.toLowerCase();
                if (valA < valB) return this.sortDirection === 'asc' ? -1 : 1;
                if (valA > valB) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
            return res;
        },

        async openRackSlotModal(loc) {
            if (!loc) return;
            this.rackGridModalOpen = true;
            this.selectedSlotDetail = null;
            this.slotFilterStatus = 'all';
            this.slotSearchQuery = '';
            this.slotDataLoading = true;

            // Set preliminary info
            this.selectedRackForModal = {
                id: loc.id,
                rack_code: loc.rack_code,
                shelf_code: loc.shelf_code,
                room_sector: loc.room_sector || loc.rack_code.split('-')[1] || 'Umum',
                box_capacity: loc.box_capacity || 100,
                current_box_count: loc.current_box_count || 0,
                slots: []
            };

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });

            try {
                // Fetch live slots data from API
                const res = await fetch('{{ route("api.warehouse.layout_data") }}');
                const data = await res.json();
                this.cachedLayoutData = data.locations || [];

                // Find matching location by ID or rack code
                const matched = this.cachedLayoutData.find(l => l.id === loc.id || l.rack_code === loc.rack_code);
                if (matched) {
                    this.selectedRackForModal = matched;
                }
            } catch (err) {
                console.error('Failed to load rack slot data', err);
            } finally {
                this.slotDataLoading = false;

                // Auto-select first occupied slot or first top slot
                const slots = this.selectedRackForModal?.slots || [];
                const firstOccupied = slots.find(s => s.archive || s.status === 'filled');
                if (firstOccupied) {
                    this.selectedSlotDetail = firstOccupied;
                } else if (slots.length > 0) {
                    this.selectedSlotDetail = slots[0];
                }

                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        slotToggleLoading: false,

        getRackIdentifier(rack) {
            if (!rack || !rack.rack_code) return 'RAK';
            const code = rack.rack_code;
            const clean = code.replace(/^RAK-(?:R\d+-)?/i, '');
            return clean || code;
        },

        getSapSlots(sapLevel, layer) {
            if (!this.selectedRackForModal) return [];
            const allSlots = this.selectedRackForModal.slots || [];
            const sap = parseInt(sapLevel);
            const matching = allSlots.filter(s => parseInt(s.sap_level) === sap && s.layer === layer);
            const rackId = this.getRackIdentifier(this.selectedRackForModal);

            const result = [];
            for (let i = 1; i <= 10; i++) {
                // Sesuai Denah Gambar 2:
                // LVL 1: Bawah 1..10, Atas 11..20
                // LVL 2: Bawah 21..30, Atas 31..40
                // LVL 3: Bawah 41..50, Atas 51..60
                // LVL 4: Bawah 61..70, Atas 71..80
                // LVL 5: Bawah 81..90, Atas 91..100
                const boxNum = layer === 'bottom' ? ((sap - 1) * 20 + i) : ((sap - 1) * 20 + 10 + i);
                const expectedSlotCode = `${rackId}${boxNum}`;
                const found = matching.find(s => parseInt(s.slot_number) === boxNum || parseInt(s.slot_number) === i);
                if (found) {
                    result.push({
                        ...found,
                        slot_code: expectedSlotCode,
                        box_number_display: boxNum
                    });
                } else {
                    result.push({
                        id: `synth-${sapLevel}-${layer}-${i}`,
                        sap_level: sapLevel,
                        layer: layer,
                        layer_label: layer === 'top' ? 'Baris Atas' : 'Baris Bawah',
                        slot_number: boxNum,
                        box_number_display: boxNum,
                        slot_code: expectedSlotCode,
                        status: 'empty',
                        is_active: true,
                        archive: null
                    });
                }
            }
            return result;
        },

        getRackSlotStats(rack) {
            if (!rack) return { total: 100, empty: 100, filled: 0, expired: 0, inactive: 0 };
            const slots = rack.slots || [];
            let empty = 0, filled = 0, expired = 0, inactive = 0;

            if (slots.length > 0) {
                slots.forEach(s => {
                    if (s.is_active === false || s.status === 'inactive') {
                        inactive++;
                    } else if (s.status === 'expired' || s.archive?.is_expired) {
                        expired++;
                    } else if (s.status === 'filled' || s.archive) {
                        filled++;
                    } else {
                        empty++;
                    }
                });
                const unrecorded = Math.max(0, 100 - slots.length);
                empty += unrecorded;
            } else {
                empty = rack.box_capacity || 100;
            }

            return {
                total: 100,
                empty: empty,
                filled: filled,
                expired: expired,
                inactive: inactive
            };
        },

        getSlotStyleClasses(slot) {
            if (!slot) return 'bg-slate-100 dark:bg-slate-950 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-800';
            if (slot.is_active === false || slot.status === 'inactive') {
                return 'bg-slate-100/90 text-slate-500 border-slate-300 dark:bg-slate-900/60 dark:text-slate-400 dark:border-slate-800 opacity-60 hover:opacity-100 transition shadow-xs';
            }
            const isExpired = slot.status === 'expired' || slot.archive?.is_expired;
            const isFilled = (slot.status === 'filled' || slot.archive) && !isExpired;

            if (isExpired) {
                return 'bg-rose-50 text-rose-800 border-rose-300 hover:bg-rose-100 hover:border-rose-400 dark:bg-rose-950/40 dark:text-rose-200 dark:border-rose-600/60 dark:hover:bg-rose-900/50 dark:hover:border-rose-400 shadow-xs';
            }
            if (isFilled) {
                return 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100 hover:border-amber-400 dark:bg-amber-950/35 dark:text-amber-200 dark:border-amber-600/50 dark:hover:bg-amber-900/40 dark:hover:border-amber-400 shadow-xs';
            }
            return 'bg-emerald-50/70 text-emerald-800 border-emerald-200 hover:border-emerald-400 hover:bg-emerald-100/70 dark:bg-slate-950/80 dark:text-emerald-400 dark:border-slate-800 dark:hover:border-emerald-500/60 dark:hover:bg-emerald-950/25 shadow-xs';
        },

        isSlotMatchFilter(slot) {
            if (!slot) return false;
            const isInactive = slot.is_active === false || slot.status === 'inactive';
            const isExpired = !isInactive && (slot.status === 'expired' || slot.archive?.is_expired);
            const isFilled = !isInactive && !isExpired && (slot.status === 'filled' || slot.archive);
            const isEmpty = !isInactive && !isExpired && !isFilled;

            if (this.slotFilterStatus === 'empty' && !isEmpty) return false;
            if (this.slotFilterStatus === 'filled' && !isFilled) return false;
            if (this.slotFilterStatus === 'expired' && !isExpired) return false;
            if (this.slotFilterStatus === 'inactive' && !isInactive) return false;

            if (this.slotSearchQuery && this.slotSearchQuery.trim() !== '') {
                const q = this.slotSearchQuery.toLowerCase().trim();
                const slotCode = (slot.slot_code || '').toLowerCase();
                const boxNum = (slot.archive?.box_number || '').toLowerCase();
                const title = (slot.archive?.title || '').toLowerCase();
                const period = (slot.archive?.periode_doc || '').toLowerCase();
                const dept = (slot.archive?.department || '').toLowerCase();

                return slotCode.includes(q) || boxNum.includes(q) || title.includes(q) || period.includes(q) || dept.includes(q);
            }

            return true;
        },

        selectSlotForDetail(slot) {
            this.selectedSlotDetail = slot;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        async toggleSlotActiveStatus(slot) {
            if (!slot || !this.selectedRackForModal) return;
            const newActive = slot.is_active === false ? true : false;

            this.slotToggleLoading = true;
            try {
                const res = await fetch(`/api/warehouse/locations/${this.selectedRackForModal.id}/slots/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        sap_level: slot.sap_level,
                        layer: slot.layer,
                        slot_number: slot.slot_number,
                        is_active: newActive
                    })
                });

                const data = await res.json();
                if (data.success) {
                    slot.is_active = data.is_active;
                    slot.status = data.status;

                    if (!this.selectedRackForModal.slots) {
                        this.selectedRackForModal.slots = [];
                    }
                    const idx = this.selectedRackForModal.slots.findIndex(s => s.sap_level == slot.sap_level && s.layer == slot.layer && s.slot_number == slot.slot_number);
                    if (idx !== -1) {
                        this.selectedRackForModal.slots[idx].is_active = data.is_active;
                        this.selectedRackForModal.slots[idx].status = data.status;
                    } else {
                        this.selectedRackForModal.slots.push({
                            id: data.slot.id,
                            sap_level: slot.sap_level,
                            layer: slot.layer,
                            layer_label: slot.layer_label,
                            slot_number: slot.slot_number,
                            slot_code: slot.slot_code,
                            status: data.status,
                            is_active: data.is_active,
                            archive: slot.archive || null
                        });
                    }

                    if (this.selectedSlotDetail && this.selectedSlotDetail.slot_code === slot.slot_code) {
                        this.selectedSlotDetail.is_active = data.is_active;
                        this.selectedSlotDetail.status = data.status;
                    }

                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                } else {
                    alert(data.message || 'Gagal mengubah status slot rak.');
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan koneksi saat mengubah status slot rak.');
            } finally {
                this.slotToggleLoading = false;
            }
        },

        async unassignCurrentSlot() {
            if (!this.selectedSlotDetail || !this.selectedSlotDetail.archive || !this.selectedRackForModal) return;
            const boxNum = this.selectedSlotDetail.archive.box_number || 'Box';
            const ok = await window.showConfirmModal({
                title: 'Lepas Arsip dari Slot',
                message: `Lepas arsip [${boxNum}] dari slot ${this.selectedSlotDetail.slot_code}?`,
                type: 'warning',
                confirmText: 'Ya, Lepaskan'
            });
            if (!ok) return;

            this.slotAssignLoading = true;
            try {
                const res = await fetch(`/api/warehouse/locations/${this.selectedRackForModal.id}/slots/unassign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        sap_level: this.selectedSlotDetail.sap_level,
                        layer: this.selectedSlotDetail.layer,
                        slot_number: this.selectedSlotDetail.slot_number,
                        slot_id: this.selectedSlotDetail.id,
                        slot_code: this.selectedSlotDetail.slot_code
                    })
                });

                const data = await res.json();
                if (data.success) {
                    // Update local slot status
                    this.selectedSlotDetail.archive = null;
                    this.selectedSlotDetail.status = 'empty';

                    // Update parent rack count
                    if (this.selectedRackForModal.current_box_count > 0) {
                        this.selectedRackForModal.current_box_count--;
                    }

                    // Update warehouse locations list
                    const wh = this.warehouses.find(w => w.id === this.selectedRackForModal.warehouse_id);
                    if (wh && wh.locations) {
                        const locItem = wh.locations.find(l => l.id === this.selectedRackForModal.id);
                        if (locItem && locItem.current_box_count > 0) {
                            locItem.current_box_count--;
                        }
                    }

                    alert(data.message || 'Box berhasil dilepas dari slot rak.');
                } else {
                    alert(data.message || 'Gagal melepaskan box dari slot rak.');
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan koneksi saat melepaskan box arsip.');
            } finally {
                this.slotAssignLoading = false;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        async toggleWarehouseActiveStatus(wh) {
            if (!wh) return;
            const newActive = (wh.is_active === false) ? true : false;
            this.warehouseToggleLoading = wh.id;

            try {
                const res = await fetch(`/master/warehouses/${wh.id}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        is_active: newActive
                    })
                });

                const data = await res.json();
                if (data.success) {
                    wh.is_active = data.is_active;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                } else {
                    alert(data.message || 'Gagal mengubah status aktif gudang.');
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan jaringan saat mengubah status gudang.');
            } finally {
                this.warehouseToggleLoading = null;
            }
        }
    };
}
</script>
@endsection
