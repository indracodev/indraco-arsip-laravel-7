@extends('layouts.app')

@section('title', 'Master Gudang & Lokasi Rak - DMS PT Indraco')

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
                                    <template x-if="['GUDANG GA', 'GUDANG IT', 'GA', 'IT'].includes(wh.code)">
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30 shadow-2xs select-none">Khusus Super Admin</span>
                                    </template>
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
                            @if(!auth()->user()->isPicGudang())
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
                            @endif
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
                                                        @if(!auth()->user()->isPicGudang())
                                                        <!-- Tombol Edit Lokasi Rak -->
                                                        <button 
                                                            type="button" 
                                                            @click="editLocationItem = Object.assign({}, loc)" 
                                                            class="p-1 text-amber-600 hover:text-amber-800 hover:bg-amber-500/10 dark:text-amber-400 dark:hover:text-amber-300 rounded transition" 
                                                            title="Edit Data Rak"
                                                        >
                                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                        @endif

                                                        <!-- Tombol Buka Visualisasi Denah 100 Slot Rak -->
                                                        <button 
                                                            type="button" 
                                                            @click="openRackSlotModal(loc)" 
                                                            class="p-1 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-500/10 dark:text-indigo-400 dark:hover:text-indigo-300 rounded transition" 
                                                            title="Buka Visualisasi Denah Rak"
                                                        >
                                                            <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                                                        </button>

                                                        @if(!auth()->user()->isPicGudang())
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
                                                        @endif
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


    <!-- MODULAR WAREHOUSE & RACK MODALS -->
    @include('master.warehouses.modals.rack_visualizer_modal')
    @include('master.warehouses.modals.crud_modals')
</div>

@push('scripts')
@include('master.warehouses.partials.warehouses_scripts')
@endpush
@endsection
