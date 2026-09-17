@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Master Perusahaan Entitas - DMS PT Indraco')

@section('content')
<div class="space-y-3 font-mono" x-data="{
    openAdd: false, 
    editItem: null,
    searchQuery: '',
    sortColumn: 'name',
    sortDirection: 'asc',
    submitting: false,
    isLoading: false,
    items: {{ json_encode($companies) }},

    get filteredItems() {
        let res = [...this.items];
        if (this.searchQuery.trim() !== '') {
            const q = this.searchQuery.toLowerCase();
            res = res.filter(i => 
                (i.code && i.code.toLowerCase().includes(q)) ||
                (i.name && i.name.toLowerCase().includes(q)) ||
                (i.description && i.description.toLowerCase().includes(q))
            );
        }
        res.sort((a, b) => {
            let valA = a[this.sortColumn] ?? '';
            let valB = b[this.sortColumn] ?? '';
            valA = valA.toString().toLowerCase();
            valB = valB.toString().toLowerCase();
            if (valA < valB) return this.sortDirection === 'asc' ? -1 : 1;
            if (valA > valB) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
        return res;
    },

    sortBy(col) {
        this.isLoading = true;
        if (this.sortColumn === col) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = col;
            this.sortDirection = 'asc';
        }
        setTimeout(() => { this.isLoading = false; if (window.lucide) lucide.createIcons(); }, 80);
    }
}">

    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="building-2" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Katalog Perusahaan Entitas PT Indraco</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengelolaan Daftar Entitas & Korporasi Perusahaan (PIC Departemen & Super Admin Full Control)</p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari entitas... (Ctrl+F)" 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                >
                <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>

            <!-- Refresh Button -->
            <button @click="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Segarkan Data">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Refresh</span>
            </button>

            <!-- Add Company Button -->
            <button @click="openAdd = true" type="button" class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow-sm transition flex items-center gap-1.5 shrink-0">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Tambah Perusahaan Entitas</span>
            </button>
        </div>
    </div>

    <!-- DELPHI DBGRID TABLE -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        
        <!-- Loading Overlay -->
        <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/70 dark:bg-slate-950/70 backdrop-blur-xs z-10 flex items-center justify-center font-mono">
            <div class="flex items-center gap-2 text-xs font-bold text-amber-600 dark:text-amber-400">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Loading Data...
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-sans text-xs">
                <thead>
                    <tr class="font-mono text-[11px] select-none">
                        <th @click="sortBy('code')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition w-24">
                            <div class="flex items-center gap-1">
                                KODE
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'code'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-amber-600" x-show="sortColumn === 'code' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-amber-600" x-show="sortColumn === 'code' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th @click="sortBy('name')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            <div class="flex items-center gap-1">
                                NAMA PERUSAHAAN ENTITAS
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'name'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-amber-600" x-show="sortColumn === 'name' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-amber-600" x-show="sortColumn === 'name' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-2 px-3">KETERANGAN / DESKRIPSI</th>
                        <th class="py-2 px-3 w-28 text-center">STATUS</th>
                        <th class="py-2 px-3 text-right w-40 font-mono">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <template x-for="comp in filteredItems" :key="comp.id">
                        <tr class="hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition">
                            <td class="py-2 px-3 font-mono text-xs text-amber-600 dark:text-amber-400 font-bold" x-text="comp.code || '-'"></td>
                            <td class="py-2 px-3 font-bold text-slate-900 dark:text-white" x-text="comp.name"></td>
                            <td class="py-2 px-3 text-xs text-slate-600 dark:text-slate-300 font-medium" x-text="comp.description || '-'"></td>
                            <td class="py-2 px-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                    Aktif
                                </span>
                            </td>
                            <td class="py-2 px-3 text-right flex items-center justify-end gap-1 font-mono">
                                <button @click="editItem = Object.assign({}, comp)" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-bold transition flex items-center gap-1" title="Edit Perusahaan">
                                    <i data-lucide="edit-3" class="w-3 h-3 text-amber-500"></i> Edit
                                </button>
                                <form :action="'{{ url('/master/companies') }}/' + comp.id" method="POST" class="inline" @submit="submitting = true">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Hapus perusahaan entitas ini?')" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[11px] font-bold transition flex items-center gap-1" title="Hapus Perusahaan">
                                        <i data-lucide="trash-2" class="w-3 h-3 text-rose-500"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredItems.length === 0">
                        <td colspan="5" class="py-6 text-center text-slate-500 font-mono text-xs">Tidak ada data perusahaan entitas yang cocok dengan pencarian.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Count Bar -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-3 py-1 font-mono text-[11px] flex items-center justify-between text-slate-600 dark:text-slate-400">
            <span>Menampilkan <strong class="text-amber-600 dark:text-amber-400" x-text="filteredItems.length"></strong> dari <strong x-text="items.length"></strong> perusahaan entitas</span>
            <span>TDBGrid Entity Catalog</span>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL: ADD COMPANY -->
    <div x-show="openAdd" 
         x-cloak 
         x-data="{ 
             posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
             startDrag(e) { 
                 if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea')) return; 
                 this.isDragging = true; 
                 this.startX = e.clientX - this.posX; 
                 this.startY = e.clientY - this.posY; 
             }, 
             onDrag(e) { 
                 if (!this.isDragging) return; 
                 this.posX = e.clientX - this.startX; 
                 this.posY = e.clientY - this.startY; 
             }, 
             stopDrag() { this.isDragging = false; }, 
             resetPos() { this.posX = 0; this.posY = 0; } 
         }"
         @mousemove.window="onDrag($event)" 
         @mouseup.window="stopDrag()"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 font-mono">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
             class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono text-xs">
            <!-- Window Title Bar -->
            <div @mousedown="startDrag($event)" 
                 :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                 title="Klik & tahan untuk menggeser form"
                 class="bg-gradient-to-r from-slate-800 via-slate-700 to-amber-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-amber-400"></i> frmCompanyAdd : Tambah Entitas Baru
                </span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAdd = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.companies.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Data Perusahaan Entitas</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA PERUSAHAAN ENTITAS <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: PT Indraco Niaga Makmur" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE SINGKATAN (CTH: IND, TRD, RTL)</label>
                        <input type="text" name="code" maxlength="20" placeholder="INM" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono uppercase font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI / KETERANGAN</label>
                        <textarea name="description" rows="2" placeholder="Fungsi dan lini bisnis entitas..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAdd = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1 shadow-sm">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan Data (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL: EDIT COMPANY -->
    <template x-if="editItem">
        <div x-data="{ 
                 posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
                 startDrag(e) { 
                     if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea')) return; 
                     this.isDragging = true; 
                     this.startX = e.clientX - this.posX; 
                     this.startY = e.clientY - this.posY; 
                 }, 
                 onDrag(e) { 
                     if (!this.isDragging) return; 
                     this.posX = e.clientX - this.startX; 
                     this.posY = e.clientY - this.startY; 
                 }, 
                 stopDrag() { this.isDragging = false; }, 
                 resetPos() { this.posX = 0; this.posY = 0; } 
             }"
             @mousemove.window="onDrag($event)" 
             @mouseup.window="stopDrag()"
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 font-mono">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
                 class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono text-xs">
                <!-- Window Title Bar -->
                <div @mousedown="startDrag($event)" 
                     :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                     title="Klik & tahan untuk menggeser form"
                     class="bg-gradient-to-r from-slate-800 via-slate-700 to-amber-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmCompanyEdit : Edit Data Perusahaan
                    </span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editItem = null" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/companies') }}/' + editItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Form Perubahan Data</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA PERUSAHAAN ENTITAS</label>
                            <input type="text" name="name" :value="editItem.name" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE SINGKATAN</label>
                            <input type="text" name="code" :value="editItem.code" maxlength="20" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono uppercase font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI</label>
                            <textarea name="description" rows="2" x-text="editItem.description" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editItem = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1 shadow-sm">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Data (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
