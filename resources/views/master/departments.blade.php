@extends('layouts.app')

@section('title', 'Master Departemen & Sub-Departemen - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="{
    openAddDept: false, 
    editDeptItem: null,
    openAddSubDept: false,
    selectedDeptForSub: '',
    editSubDeptItem: null,
    searchQuery: '',
    sortColumn: 'code',
    sortDirection: 'asc',
    submitting: false,
    isLoading: false,
    expandedDepts: {},
    items: {{ json_encode($departments) }},

    toggleExpand(deptId) {
        this.expandedDepts[deptId] = !this.expandedDepts[deptId];
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    expandAll() {
        this.items.forEach(d => { this.expandedDepts[d.id] = true; });
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    collapseAll() {
        this.expandedDepts = {};
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    openCreateSubDept(deptId) {
        this.selectedDeptForSub = deptId || (this.items.length > 0 ? this.items[0].id : '');
        this.openAddSubDept = true;
    },

    openEditSubDept(sub, deptId) {
        this.editSubDeptItem = Object.assign({}, sub, { department_id: deptId || sub.department_id });
    },

    get filteredItems() {
        let res = [...this.items];
        if (this.searchQuery.trim() !== '') {
            const q = this.searchQuery.toLowerCase();
            res = res.filter(dept => {
                const matchDept = (dept.code && dept.code.toLowerCase().includes(q)) ||
                                  (dept.name && dept.name.toLowerCase().includes(q)) ||
                                  (dept.description && dept.description.toLowerCase().includes(q));
                
                const matchSub = dept.sub_departments && dept.sub_departments.some(sub => 
                    (sub.code && sub.code.toLowerCase().includes(q)) ||
                    (sub.name && sub.name.toLowerCase().includes(q)) ||
                    (sub.description && sub.description.toLowerCase().includes(q))
                );

                return matchDept || matchSub;
            });
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
                <i data-lucide="building-2" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Master Departemen & Sub-Departemen</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Hierarki Unit Kerja & Konfigurasi Retensi Masa Simpan (TDBGrid Master-Detail Engine)</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Search Input -->
            <div class="relative w-full sm:w-60">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari Dept / Sub-Dept..." 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 transition"
                >
                <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>

            <!-- Expand / Collapse All -->
            <button @click="expandAll()" type="button" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-mono font-bold transition flex items-center gap-1 shadow-sm" title="Buka Semua Sub-Departemen">
                <i data-lucide="chevrons-down" class="w-3 h-3"></i>
                <span>Expand All</span>
            </button>
            <button @click="collapseAll()" type="button" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-mono font-bold transition flex items-center gap-1 shadow-sm" title="Tutup Semua Sub-Departemen">
                <i data-lucide="chevrons-up" class="w-3 h-3"></i>
                <span>Collapse</span>
            </button>

            <!-- Refresh Button (F5) -->
            <button @click="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Segarkan Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Refresh</span>
            </button>

            <!-- Add Sub-Dept Button -->
            <button @click="openCreateSubDept('')" type="button" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white font-mono font-bold text-xs rounded border border-amber-700 shadow transition flex items-center gap-1.5 shrink-0" title="Tambah Sub-Departemen Baru">
                <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                <span>+ Sub-Dept</span>
            </button>

            <!-- Add Dept Button (F2) -->
            <button @click="openAddDept = true" type="button" class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white font-mono font-bold text-xs rounded border border-purple-700 shadow transition flex items-center gap-1.5 shrink-0" title="Tambah Departemen Baru (F2)">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                <span>+ Dept (F2)</span>
            </button>
        </div>
    </div>

    <!-- DELPHI MASTER-DETAIL DBGRID CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        
        <!-- Loading Overlay -->
        <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/70 dark:bg-slate-950/70 backdrop-blur-xs z-10 flex items-center justify-center font-mono">
            <div class="flex items-center gap-2 text-xs font-bold text-purple-600 dark:text-purple-400">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Loading Data Grid...
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-sans text-xs">
                <thead>
                    <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 font-mono text-[11px] select-none text-slate-700 dark:text-slate-300">
                        <th class="py-2 px-2.5 w-8 text-center">#</th>
                        <th @click="sortBy('code')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            <div class="flex items-center gap-1">
                                KODE DEPT
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'code'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'code' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'code' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th @click="sortBy('name')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            <div class="flex items-center gap-1">
                                NAMA DEPARTEMEN
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'name'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'name' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'name' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-2 px-3">DESKRIPSI / RUANG LINGKUP</th>
                        <th @click="sortBy('retention_years')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            <div class="flex items-center gap-1">
                                STANDAR RETENSI
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'retention_years'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'retention_years' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'retention_years' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-2 px-3 text-center">SUB-DEPARTEMEN</th>
                        <th @click="sortBy('archives_count')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            <div class="flex items-center gap-1">
                                TOTAL BERKAS
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'archives_count'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'archives_count' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-purple-600" x-show="sortColumn === 'archives_count' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-2 px-3 text-right">AKSI</th>
                    </tr>
                </thead>
                <template x-for="dept in filteredItems" :key="dept.id">
                    <tbody class="border-b border-slate-200 dark:border-slate-800">
                        <!-- Parent Department Row -->
                        <tr :class="expandedDepts[dept.id] ? 'bg-purple-50/50 dark:bg-purple-950/20 font-semibold' : 'hover:bg-amber-500/10 dark:hover:bg-amber-500/20'" class="transition">
                            <td class="py-2 px-2 text-center">
                                <button 
                                    @click="toggleExpand(dept.id)" 
                                    type="button" 
                                    class="w-5 h-5 inline-flex items-center justify-center rounded bg-slate-200 hover:bg-purple-600 hover:text-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono font-bold text-xs transition"
                                    :title="expandedDepts[dept.id] ? 'Tutup Sub-Departemen' : 'Buka Sub-Departemen'"
                                >
                                    <span x-text="expandedDepts[dept.id] ? '−' : '+'"></span>
                                </button>
                            </td>
                            <td class="py-2 px-3 font-mono text-xs text-amber-600 dark:text-amber-400 font-bold" x-text="dept.code"></td>
                            <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-1.5">
                                    <span x-text="dept.name"></span>
                                    <span x-show="dept.code === 'FIN'" class="px-1.5 py-0.2 bg-rose-500/20 text-rose-700 dark:text-rose-300 text-[9px] font-mono font-bold border border-rose-500/30 rounded uppercase">Ruang FAT Locked</span>
                                </div>
                            </td>
                            <td class="py-2 px-3 text-xs text-slate-600 dark:text-slate-300 font-medium" x-text="dept.description || '-'"></td>
                            <td class="py-2 px-3 font-mono text-xs">
                                <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 rounded font-bold" x-text="(dept.retention_years || 5) + ' Tahun'"></span>
                            </td>
                            <td class="py-2 px-3 text-center font-mono">
                                <button 
                                    @click="toggleExpand(dept.id)" 
                                    type="button" 
                                    class="px-2 py-0.5 rounded text-[11px] font-bold border transition inline-flex items-center gap-1"
                                    :class="(dept.sub_departments && dept.sub_departments.length > 0) ? 'bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border-indigo-500/30' : 'bg-slate-100 dark:bg-slate-900 text-slate-400 border-slate-300 dark:border-slate-800'"
                                >
                                    <i data-lucide="layers" class="w-3 h-3"></i>
                                    <span x-text="(dept.sub_departments ? dept.sub_departments.length : 0) + ' Sub-Unit'"></span>
                                </button>
                            </td>
                            <td class="py-2 px-3 text-xs font-mono font-bold text-purple-700 dark:text-purple-300" x-text="(dept.archives_count || 0) + ' Box/Berkas'"></td>
                            <td class="py-2 px-3 text-right">
                                <div class="flex items-center justify-end gap-1 font-mono">
                                    <!-- Add Sub-Dept Shortcut -->
                                    <button @click="openCreateSubDept(dept.id)" class="px-2 py-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 rounded text-[11px] font-bold transition flex items-center gap-1" title="Tambah Sub-Departemen untuk departemen ini">
                                        <i data-lucide="plus" class="w-3 h-3 text-amber-500"></i> +Sub
                                    </button>
                                    <!-- Edit Dept -->
                                    <button @click="editDeptItem = Object.assign({}, dept)" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-bold transition flex items-center gap-1" title="Edit Departemen">
                                        <i data-lucide="edit-3" class="w-3 h-3 text-amber-500"></i> Edit
                                    </button>
                                    <!-- Delete Dept -->
                                    <form :action="'{{ url('/master/departments') }}/' + dept.id" method="POST" class="inline" @submit="submitting = true">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Hapus departemen ini beserta seluruh konfigurasinya?')" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[11px] font-bold transition flex items-center gap-1" title="Hapus Departemen">
                                            <i data-lucide="trash-2" class="w-3 h-3 text-rose-500"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Nested Detail Sub-Departments Grid (Expandable) -->
                        <tr x-show="expandedDepts[dept.id]" class="bg-slate-50/80 dark:bg-slate-900/80 border-b-2 border-purple-500/30">
                            <td colspan="8" class="p-3 pl-8">
                                <div class="border-2 border-indigo-300 dark:border-indigo-900/60 rounded bg-white dark:bg-slate-950 p-3 shadow-inner space-y-2">
                                    <!-- Sub-grid Header Bar -->
                                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2 font-mono">
                                        <div class="flex items-center gap-2">
                                            <span class="p-1 bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded">
                                                <i data-lucide="subtitles" class="w-3.5 h-3.5"></i>
                                            </span>
                                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase">
                                                Daftar Sub-Departemen di bawah: <span class="text-purple-600 dark:text-purple-400" x-text="dept.name + ' (' + dept.code + ')'"></span>
                                            </span>
                                        </div>
                                        <button @click="openCreateSubDept(dept.id)" type="button" class="px-2.5 py-0.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] rounded transition flex items-center gap-1 shadow-sm">
                                            <i data-lucide="plus-circle" class="w-3 h-3"></i>
                                            <span>Tambah Sub-Departemen</span>
                                        </button>
                                    </div>

                                    <!-- Sub-grid Table -->
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse text-[11px]">
                                            <thead>
                                                <tr class="bg-slate-100 dark:bg-slate-900/90 font-mono text-[10px] text-slate-600 dark:text-slate-400 select-none border-b border-slate-200 dark:border-slate-800">
                                                    <th class="py-1.5 px-2.5">KODE SUB-DEPT</th>
                                                    <th class="py-1.5 px-2.5">NAMA SUB-DEPARTEMEN</th>
                                                    <th class="py-1.5 px-2.5">DESKRIPSI / KEWENANGAN</th>
                                                    <th class="py-1.5 px-2.5">CUSTOM RETENSI</th>
                                                    <th class="py-1.5 px-2.5">BERKAS ARSIP</th>
                                                    <th class="py-1.5 px-2.5 text-right">AKSI</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                                <template x-for="sub in (dept.sub_departments || [])" :key="sub.id">
                                                    <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30 transition">
                                                        <td class="py-1.5 px-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="dept.code + '-' + sub.code"></td>
                                                        <td class="py-1.5 px-2.5 font-bold text-slate-800 dark:text-slate-200" x-text="sub.name"></td>
                                                        <td class="py-1.5 px-2.5 text-slate-600 dark:text-slate-400" x-text="sub.description || '-'"></td>
                                                        <td class="py-1.5 px-2.5 font-mono">
                                                            <template x-if="sub.retention_years">
                                                                <span class="px-1.5 py-0.5 bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30 rounded font-bold text-[10px]" x-text="'Custom: ' + sub.retention_years + ' Thn'"></span>
                                                            </template>
                                                            <template x-if="!sub.retention_years">
                                                                <span class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded text-[10px]" x-text="'Inherit Dept (' + (dept.retention_years || 5) + ' Thn)'"></span>
                                                            </template>
                                                        </td>
                                                        <td class="py-1.5 px-2.5 font-mono font-bold text-purple-700 dark:text-purple-300" x-text="(sub.archives_count || 0) + ' Berkas'"></td>
                                                        <td class="py-1.5 px-2.5 text-right">
                                                            <div class="flex items-center justify-end gap-1 font-mono">
                                                                <button @click="openEditSubDept(sub, dept.id)" class="px-1.5 py-0.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-[10px] font-bold transition flex items-center gap-1">
                                                                    <i data-lucide="edit-2" class="w-2.5 h-2.5 text-amber-500"></i> Edit
                                                                </button>
                                                                <form :action="'{{ url('/master/sub-departments') }}/' + sub.id" method="POST" class="inline" @submit="submitting = true">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" onclick="return confirm('Hapus sub-departemen ini?')" class="px-1.5 py-0.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[10px] font-bold transition flex items-center gap-1">
                                                                        <i data-lucide="trash-2" class="w-2.5 h-2.5 text-rose-500"></i> Hapus
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <tr x-show="!dept.sub_departments || dept.sub_departments.length === 0">
                                                    <td colspan="6" class="py-3 text-center text-slate-500 font-mono text-[11px]">
                                                        Belum ada sub-departemen terdaftar untuk departemen ini.
                                                        <button @click="openCreateSubDept(dept.id)" class="ml-2 text-indigo-600 dark:text-indigo-400 font-bold underline hover:no-underline">+ Tambah Sekarang</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </template>
                <tbody x-show="filteredItems.length === 0">
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-500 font-mono text-xs">Tidak ada data departemen yang cocok dengan filter pencarian.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Count Bar -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-3 py-1.5 font-mono text-[11px] flex items-center justify-between text-slate-600 dark:text-slate-400">
            <span>Menampilkan <strong class="text-purple-600 dark:text-purple-400" x-text="filteredItems.length"></strong> dari <strong x-text="items.length"></strong> departemen utama</span>
            <span>TDBGrid Master-Detail Engine</span>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 1: ADD DEPARTMENT -->
    <div x-show="openAddDept" 
         x-cloak 
         x-data="{ 
             posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
             startDrag(e) { 
                 if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select')) return; 
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
                 title="Klik & tahan untuk menggeser/reposisi posisi form (Drag to move)"
                 class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-purple-400"></i> frmDepartmentAdd : Tambah Departemen Baru
                </span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAddDept = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.departments.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Data Induk Departemen</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE DEPARTEMEN (CTH: FIN, HRD, LOG, MKT, PROD)</label>
                        <input type="text" name="code" required maxlength="10" placeholder="FIN" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-purple-500">
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA DEPARTEMEN</label>
                        <input type="text" name="name" required placeholder="Keuangan & Akuntansi (FAT)" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 font-semibold">
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">STANDAR MASA SIMPAN / RETENSI (TAHUN)</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="retention_years" min="1" max="100" value="5" required class="w-24 p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono font-bold text-emerald-600 dark:text-emerald-400 text-center focus:outline-none focus:border-purple-500">
                            <span class="text-slate-500 text-xs font-mono">Tahun (Default jika sub-dept tidak di-custom)</span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI / RUANG LINGKUP</label>
                        <textarea name="description" rows="2" placeholder="Uraian ruang lingkup dokumen & divisi..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 font-medium"></textarea>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAddDept = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 text-white text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan Departemen (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 2: EDIT DEPARTMENT -->
    <template x-if="editDeptItem">
        <div x-data="{ 
                 posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
                 startDrag(e) { 
                     if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select')) return; 
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
                     title="Klik & tahan untuk menggeser/reposisi posisi form (Drag to move)"
                     class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmDepartmentEdit : Edit Data Departemen
                    </span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editDeptItem = null" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/departments') }}/' + editDeptItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Form Perubahan Departemen</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE DEPARTEMEN</label>
                            <input type="text" name="code" :value="editDeptItem.code" required maxlength="10" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA DEPARTEMEN</label>
                            <input type="text" name="name" :value="editDeptItem.name" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold">
                        </div>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">STANDAR MASA SIMPAN / RETENSI (TAHUN)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="retention_years" min="1" max="100" :value="editDeptItem.retention_years || 5" required class="w-24 p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono font-bold text-emerald-600 dark:text-emerald-400 text-center focus:outline-none focus:border-amber-500">
                                <span class="text-slate-500 text-xs font-mono">Tahun</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI</label>
                            <textarea name="description" rows="2" x-text="editDeptItem.description" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editDeptItem = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Data (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- WINDOWS FORM DIALOG MODAL 3: ADD SUB-DEPARTMENT -->
    <div x-show="openAddSubDept" 
         x-cloak 
         x-data="{ 
             posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
             startDrag(e) { 
                 if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select')) return; 
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
                 title="Klik & tahan untuk menggeser/reposisi posisi form (Drag to move)"
                 class="bg-gradient-to-r from-slate-800 via-indigo-900 to-slate-900 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                    <i data-lucide="layers" class="w-3.5 h-3.5 text-amber-400"></i> frmSubDepartmentAdd : Tambah Sub-Departemen
                </span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAddSubDept = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.sub_departments.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-indigo-700 dark:text-indigo-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Data Sub-Departemen</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DEPARTEMEN INDUK (PARENT)</label>
                        <select name="department_id" x-model="selectedDeptForSub" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white font-bold focus:outline-none focus:border-indigo-500 font-mono">
                            <option value="">-- Pilih Departemen Induk --</option>
                            <template x-for="dept in items" :key="dept.id">
                                <option :value="dept.id" x-text="dept.code + ' - ' + dept.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE SUB-DEPT (CTH: ACC, TAX, REC, LGL, QC)</label>
                        <input type="text" name="code" required maxlength="20" placeholder="ACC" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA SUB-DEPARTEMEN</label>
                        <input type="text" name="name" required placeholder="Akuntansi & Pembukuan" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-semibold">
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">CUSTOM MASA SIMPAN / RETENSI (TAHUN)</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="retention_years" min="1" max="100" placeholder="Kosongkan jika ikut standar dept" class="w-48 p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono font-bold text-amber-600 dark:text-amber-400 text-center focus:outline-none focus:border-indigo-500">
                            <span class="text-slate-500 text-xs font-mono">Tahun (Opsional override)</span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI / KEWENANGAN</label>
                        <textarea name="description" rows="2" placeholder="Uraian dokumen sub-departemen..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-medium"></textarea>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAddSubDept = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan Sub-Dept (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 4: EDIT SUB-DEPARTMENT -->
    <template x-if="editSubDeptItem">
        <div x-data="{ 
                 posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
                 startDrag(e) { 
                     if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select')) return; 
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
                     title="Klik & tahan untuk menggeser/reposisi posisi form (Drag to move)"
                     class="bg-gradient-to-r from-slate-800 via-indigo-900 to-slate-900 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmSubDepartmentEdit : Edit Data Sub-Departemen
                    </span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editSubDeptItem = null" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/sub-departments') }}/' + editSubDeptItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Form Perubahan Sub-Departemen</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DEPARTEMEN INDUK</label>
                            <select name="department_id" :value="editSubDeptItem.department_id" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 font-mono">
                                <template x-for="dept in items" :key="dept.id">
                                    <option :value="dept.id" :selected="dept.id == editSubDeptItem.department_id" x-text="dept.code + ' - ' + dept.name"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE SUB-DEPT</label>
                            <input type="text" name="code" :value="editSubDeptItem.code" required maxlength="20" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA SUB-DEPARTEMEN</label>
                            <input type="text" name="name" :value="editSubDeptItem.name" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold">
                        </div>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">CUSTOM MASA SIMPAN / RETENSI (TAHUN)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="retention_years" min="1" max="100" :value="editSubDeptItem.retention_years" placeholder="Kosongkan jika ikut standar dept" class="w-48 p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono font-bold text-amber-600 dark:text-amber-400 text-center focus:outline-none focus:border-amber-500">
                                <span class="text-slate-500 text-xs font-mono">Tahun (Opsional)</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI / KEWENANGAN</label>
                            <textarea name="description" rows="2" x-text="editSubDeptItem.description" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editSubDeptItem = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Data Sub-Dept (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
