@extends('layouts.app')

@section('title', 'Master Gudang & Lokasi Rak - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="{ 
    openAddWarehouse: false, 
    openAddLocation: false,
    editWarehouseItem: null,
    searchQuery: '',
    sortByField: 'code',
    sortDirection: 'asc',
    submitting: false,
    isLoading: false,
    warehouses: {{ json_encode($warehouses) }},

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

    toggleSort(field) {
        this.isLoading = true;
        if (this.sortByField === field) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortByField = field;
            this.sortDirection = 'asc';
        }
        setTimeout(() => { this.isLoading = false; lucide.createIcons(); }, 80);
    }
}">

    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2">
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="warehouse" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Master Gudang & Slot Rak Storage</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengelolaan Gedung Depo Gudang & Alokasi Kapasitas Slot Rak (TDBGrid Engine)</p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Search Input -->
            <div class="relative w-full sm:w-56">
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
            <button @click="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Segarkan Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Refresh (F5)</span>
            </button>

            <!-- Add Warehouse (F2) -->
            <button @click="openAddWarehouse = true" type="button" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-white font-mono font-bold text-xs rounded border border-slate-900 shadow transition flex items-center gap-1 shrink-0">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>+ Gudang (F2)</span>
            </button>

            <!-- Add Location Rak (F3) -->
            <button @click="openAddLocation = true" type="button" class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1 shrink-0">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                <span>+ Slot Rak (F3)</span>
            </button>
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
                        <div class="flex items-start justify-between bg-slate-100 dark:bg-slate-900 p-2 rounded border border-slate-200 dark:border-slate-800 font-mono">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30" x-text="wh.code"></span>
                                <h2 class="text-xs font-extrabold text-slate-900 dark:text-white mt-1" x-text="wh.name"></h2>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1 font-medium mt-0.5">
                                    <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i> <span x-text="wh.address || 'Alamat lokasi belum diisi'"></span>
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="editWarehouseItem = Object.assign({}, wh)" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-bold transition flex items-center gap-1" title="Edit Gudang">
                                    <i data-lucide="edit-3" class="w-3 h-3 text-amber-500"></i> Edit
                                </button>
                                <form :action="'{{ url('/master/warehouses') }}/' + wh.id" method="POST" class="inline" @submit="submitting = true">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Hapus gudang ini?')" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[11px] font-bold transition flex items-center gap-1" title="Hapus Gudang">
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
                                <table class="w-full text-left border-collapse font-sans text-xs">
                                    <thead>
                                        <tr class="font-mono text-[10px] select-none bg-slate-100 dark:bg-slate-900">
                                            <th class="py-1.5 px-2">LOKASI RAK</th>
                                            <th class="py-1.5 px-2">KAPASITAS</th>
                                            <th class="py-1.5 px-2 text-right">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                        <template x-for="loc in (wh.locations || [])" :key="loc.id">
                                            <tr class="hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition">
                                                <td class="py-1.5 px-2 font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400" x-text="loc.full_location || (loc.rack_code + ' - ' + loc.shelf_code)"></td>
                                                <td class="py-1.5 px-2 font-mono text-[11px]">
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 font-bold text-slate-800 dark:text-slate-200" x-text="(loc.current_box_count || 0) + ' / ' + loc.box_capacity + ' Box'"></span>
                                                </td>
                                                <td class="py-1.5 px-2 text-right font-mono">
                                                    <form :action="'{{ url('/master/warehouses/locations') }}/' + loc.id" method="POST" class="inline" @submit="submitting = true">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Hapus lokasi rak ini?')" class="p-1 text-rose-500 hover:bg-rose-500/10 rounded transition" title="Hapus Rak">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </form>
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
                    <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Data Form Gudang</legend>

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
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Form Perubahan Gudang</legend>

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
                    <legend class="px-2 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Data Form Slot Rak</legend>

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
                        <input type="number" name="box_capacity" value="50" min="1" max="1000" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
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
</div>
@endsection


