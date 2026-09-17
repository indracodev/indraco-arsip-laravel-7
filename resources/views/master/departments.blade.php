@extends('layouts.app')

@section('title', 'Master Departemen & Subdepartemen - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="{
    openAdd: false, 
    editItem: null,
    addSubDeptTarget: null,
    editSubDeptTarget: null,
    searchQuery: '',
    sortColumn: 'code',
    sortDirection: 'asc',
    submitting: false,
    isLoading: false,
    items: {{ json_encode($departments) }},

    get filteredItems() {
        let res = [...this.items];
        if (this.searchQuery.trim() !== '') {
            const q = this.searchQuery.toLowerCase();
            res = res.filter(i => 
                (i.code && i.code.toLowerCase().includes(q)) ||
                (i.name && i.name.toLowerCase().includes(q)) ||
                (i.description && i.description.toLowerCase().includes(q)) ||
                (i.sub_departments && i.sub_departments.some(s => s.name.toLowerCase().includes(q) || (s.code && s.code.toLowerCase().includes(q))))
            );
        }
        res.sort((a, b) => {
            let valA = a[this.sortColumn] ?? '';
            let valB = b[this.sortColumn] ?? '';
            if (typeof valA === 'number' && typeof valB === 'number') {
                return this.sortDirection === 'asc' ? valA - valB : valB - valA;
            }
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
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2">
            <span class="p-1.5 bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30 rounded">
                <i data-lucide="network" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Master Departemen & Subdepartemen (Parent > Child)</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengelolaan Hirarki Unit Departemen & Subdepartemen PT Indraco (Full Super Admin Control)</p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari dept / subdept... (Ctrl+F)" 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 transition"
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

            <!-- Add Department Button -->
            <button @click="openAdd = true" type="button" class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white font-mono font-bold text-xs rounded border border-purple-700 shadow transition flex items-center gap-1.5 shrink-0">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                <span>Tambah Departemen (Parent)</span>
            </button>
        </div>
    </div>

    <!-- DELPHI DBGRID SPREADSHEET TABLE CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        
        <!-- Loading Overlay -->
        <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/70 dark:bg-slate-950/70 backdrop-blur-xs z-10 flex items-center justify-center font-mono">
            <div class="flex items-center gap-2 text-xs font-bold text-purple-600 dark:text-purple-400">
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
                                <i data-lucide="arrow-up" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'code' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'code' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th @click="sortBy('name')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition w-56">
                            <div class="flex items-center gap-1">
                                DEPARTEMEN (PARENT)
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'name'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'name' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'name' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-2 px-3">
                            SUBDEPARTEMEN / DIVISI KERJA (CHILDREN)
                        </th>
                        <th @click="sortBy('archives_count')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition w-32">
                            <div class="flex items-center gap-1">
                                TOTAL BERKAS
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'archives_count'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'archives_count' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'archives_count' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-2 px-3 text-right w-44">AKSI DEPT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <template x-for="dept in filteredItems" :key="dept.id">
                        <tr class="hover:bg-amber-500/5 dark:hover:bg-amber-500/10 transition">
                            <!-- Code -->
                            <td class="py-2 px-3 font-mono text-xs text-purple-600 dark:text-purple-400 font-bold align-top" x-text="dept.code"></td>

                            <!-- Parent Dept Name -->
                            <td class="py-2 px-3 align-top">
                                <div class="font-bold text-slate-900 dark:text-white" x-text="dept.name"></div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5" x-text="dept.description || '-'"></div>
                            </td>

                            <!-- Subdepartments List (Parent > Child) -->
                            <td class="py-2 px-3 align-top">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <template x-for="sub in (dept.sub_departments || [])" :key="sub.id">
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-[11px] font-mono group transition hover:border-purple-400">
                                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="sub.name"></span>
                                            <template x-if="sub.code">
                                                <span class="text-[9px] text-amber-600 dark:text-amber-400 font-bold" x-text="'(' + sub.code + ')'"></span>
                                            </template>

                                            <!-- Edit subdept -->
                                            <button 
                                                @click="editSubDeptTarget = Object.assign({}, sub)" 
                                                type="button" 
                                                class="text-slate-400 hover:text-amber-500 ml-1"
                                                title="Edit Subdepartemen"
                                            >
                                                <i data-lucide="edit-2" class="w-3 h-3"></i>
                                            </button>

                                            <!-- Delete subdept -->
                                            <form :action="'{{ url('/master/subdepartments') }}/' + sub.id" method="POST" class="inline" @submit="submitting = true">
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="submit" 
                                                    onclick="return confirm('Hapus subdepartemen ini?')" 
                                                    class="text-slate-400 hover:text-rose-500"
                                                    title="Hapus Subdepartemen"
                                                >
                                                    <i data-lucide="x" class="w-3 h-3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </template>

                                    <!-- Button to add new child subdepartment under this department -->
                                    <button 
                                        @click="addSubDeptTarget = Object.assign({}, dept)" 
                                        type="button" 
                                        class="px-2 py-0.5 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/80 rounded text-[10px] font-mono font-bold flex items-center gap-1 transition"
                                        title="Tambah Subdepartemen untuk unit ini"
                                    >
                                        <i data-lucide="plus" class="w-3 h-3 text-amber-600"></i>
                                        <span>+ Subdept</span>
                                    </button>

                                    <template x-if="!dept.sub_departments || dept.sub_departments.length === 0">
                                        <span class="text-[10px] text-slate-400 italic">Belum ada subdepartemen</span>
                                    </template>
                                </div>
                            </td>

                            <!-- Total Archives -->
                            <td class="py-2 px-3 text-xs font-mono font-bold text-slate-700 dark:text-slate-300 align-top" x-text="(dept.archives_count || 0) + ' Box/Berkas'"></td>

                            <!-- Actions -->
                            <td class="py-2 px-3 text-right align-top font-mono">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="editItem = Object.assign({}, dept)" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-bold transition flex items-center gap-1" title="Edit Departemen">
                                        <i data-lucide="edit-3" class="w-3 h-3 text-amber-500"></i> Edit
                                    </button>
                                    <form :action="'{{ url('/master/departments') }}/' + dept.id" method="POST" class="inline" @submit="submitting = true">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Hapus departemen beserta seluruh subdepartemennya?')" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[11px] font-bold transition flex items-center gap-1" title="Hapus Departemen">
                                            <i data-lucide="trash-2" class="w-3 h-3 text-rose-500"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredItems.length === 0">
                        <td colspan="5" class="py-6 text-center text-slate-500 font-mono text-xs">Tidak ada data departemen yang cocok dengan pencarian.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Count Bar -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-3 py-1 font-mono text-[11px] flex items-center justify-between text-slate-600 dark:text-slate-400">
            <span>Menampilkan <strong class="text-purple-600 dark:text-purple-400" x-text="filteredItems.length"></strong> dari <strong x-text="items.length"></strong> departemen</span>
            <span>Parent-Child Hierarchy Mode</span>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 1: ADD DEPARTMENT (PARENT) -->
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
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
             class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
            <!-- Window Title Bar (Draggable) -->
            <div @mousedown="startDrag($event)" 
                 :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                 title="Klik & tahan untuk menggeser form (Drag to move)"
                 class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-purple-400"></i> frmDepartmentAdd : Tambah Departemen Parent Baru
                </span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAdd = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.departments.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Data Departemen Utama</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE DEPARTEMEN (CTH: FAT, DNM, HRD)</label>
                        <input type="text" name="code" required maxlength="10" placeholder="FAT" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA DEPARTEMEN (PARENT)</label>
                        <input type="text" name="name" required placeholder="Contoh: Keuangan, Akuntansi & Pajak" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 font-semibold">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI / RUANG LINGKUP</label>
                        <textarea name="description" rows="2" placeholder="Ruang lingkup divisi..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 font-medium"></textarea>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAdd = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 text-white text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan Data (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 2: EDIT DEPARTMENT (PARENT) -->
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
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
                 class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
                <!-- Window Title Bar (Draggable) -->
                <div @mousedown="startDrag($event)" 
                     :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                     title="Klik & tahan untuk menggeser form (Drag to move)"
                     class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmDepartmentEdit : Edit Data Departemen
                    </span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editItem = null" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/departments') }}/' + editItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Form Perubahan Departemen</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE DEPARTEMEN</label>
                            <input type="text" name="code" :value="editItem.code" required maxlength="10" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA DEPARTEMEN</label>
                            <input type="text" name="name" :value="editItem.name" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI</label>
                            <textarea name="description" rows="2" x-text="editItem.description" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editItem = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Data (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- WINDOWS FORM DIALOG MODAL 3: ADD SUBDEPARTMENT (CHILD OF SELECTED DEPT) -->
    <template x-if="addSubDeptTarget">
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
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
                 class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
                <!-- Window Title Bar (Draggable) -->
                <div @mousedown="startDrag($event)" 
                     :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                     title="Klik & tahan untuk menggeser form (Drag to move)"
                     class="bg-gradient-to-r from-slate-800 via-slate-700 to-purple-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                        <i data-lucide="git-branch" class="w-3.5 h-3.5 text-purple-400"></i> frmSubDepartmentAdd : Tambah Subdepartemen
                    </span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="addSubDeptTarget = null" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/departments') }}/' + addSubDeptTarget.id + '/subdepartments'" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">
                            Subdepartemen untuk: <span class="text-amber-600 dark:text-amber-400" x-text="addSubDeptTarget.code + ' - ' + addSubDeptTarget.name"></span>
                        </legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA SUBDEPARTEMEN / DIVISI <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" required placeholder="Contoh: Finance, Accounting, Tax, Design..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 font-bold">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE SINGKATAN (CTH: FIN, ACC, TAX, DSG)</label>
                            <input type="text" name="code" maxlength="20" placeholder="FIN" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-purple-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI / TUGAS</label>
                            <textarea name="description" rows="2" placeholder="Fungsi dan ruang lingkup subdepartemen..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 font-medium"></textarea>
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="addSubDeptTarget = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 text-white text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Menyimpan...' : 'Simpan Subdept (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- WINDOWS FORM DIALOG MODAL 4: EDIT SUBDEPARTMENT -->
    <template x-if="editSubDeptTarget">
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
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
                 class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
                <!-- Window Title Bar (Draggable) -->
                <div @mousedown="startDrag($event)" 
                     :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                     title="Klik & tahan untuk menggeser form (Drag to move)"
                     class="bg-gradient-to-r from-slate-800 via-slate-700 to-amber-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmSubDepartmentEdit : Ubah Subdepartemen
                    </span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editSubDeptTarget = null" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/subdepartments') }}/' + editSubDeptTarget.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Form Edit Subdepartemen</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA SUBDEPARTEMEN <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" :value="editSubDeptTarget.name" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-bold">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE SINGKATAN</label>
                            <input type="text" name="code" :value="editSubDeptTarget.code" maxlength="20" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI</label>
                            <textarea name="description" rows="2" x-text="editSubDeptTarget.description" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editSubDeptTarget = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Subdept (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
