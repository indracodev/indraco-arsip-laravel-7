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

    // Archive list & detail modals
    openArchiveListModal: false,
    archiveListTitle: '',
    archiveListSubtitle: '',
    archiveListItems: [],
    archiveListLoading: false,
    archiveListSearch: '',

    openArchiveDetailModal: false,
    selectedArchiveDetail: null,

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

    // Fetch and open archives list modal for Department
    openDeptArchives(dept) {
        this.archiveListTitle = 'Daftar Berkas Arsip: ' + dept.name + ' (' + dept.code + ')';
        this.archiveListSubtitle = 'Seluruh Box & Dokumen Fisik Terdaftar di Unit ' + dept.name;
        this.archiveListItems = [];
        this.archiveListSearch = '';
        this.archiveListLoading = true;
        this.openArchiveListModal = true;

        fetch('{{ url('/api/departments') }}/' + dept.id + '/archives')
            .then(res => res.json())
            .then(data => {
                this.archiveListItems = data.archives || [];
                this.archiveListLoading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            })
            .catch(err => {
                console.error('Error fetching archives:', err);
                this.archiveListLoading = false;
            });
    },

    // Fetch and open archives list modal for Sub-Department
    openSubDeptArchives(sub, dept) {
        this.archiveListTitle = 'Daftar Berkas Arsip: ' + dept.code + '-' + sub.code + ' (' + sub.name + ')';
        this.archiveListSubtitle = 'Sub-Departemen ' + sub.name + ' di bawah ' + dept.name;
        this.archiveListItems = [];
        this.archiveListSearch = '';
        this.archiveListLoading = true;
        this.openArchiveListModal = true;

        fetch('{{ url('/api/sub-departments') }}/' + sub.id + '/archives')
            .then(res => res.json())
            .then(data => {
                this.archiveListItems = data.archives || [];
                this.archiveListLoading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            })
            .catch(err => {
                console.error('Error fetching archives:', err);
                this.archiveListLoading = false;
            });
    },

    // Fetch and open archives list modal for Unassigned / General archives in Department
    openUnassignedDeptArchives(dept) {
        this.archiveListTitle = 'Daftar Berkas Arsip Induk: ' + dept.name + ' (' + dept.code + ')';
        this.archiveListSubtitle = 'Berkas arsip tingkat departemen induk (belum dialokasikan ke sub-unit tertentu)';
        this.archiveListItems = [];
        this.archiveListSearch = '';
        this.archiveListLoading = true;
        this.openArchiveListModal = true;

        fetch('{{ url('/api/departments') }}/' + dept.id + '/archives?filter=unassigned')
            .then(res => res.json())
            .then(data => {
                this.archiveListItems = data.archives || [];
                this.archiveListLoading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            })
            .catch(err => {
                console.error('Error fetching unassigned archives:', err);
                this.archiveListLoading = false;
            });
    },

    // Open detail modal for specific archive
    showArchiveDetail(archive) {
        this.selectedArchiveDetail = Object.assign({}, archive);
        this.openArchiveDetailModal = true;
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    get filteredArchiveList() {
        if (!this.archiveListSearch || this.archiveListSearch.trim() === '') {
            return this.archiveListItems;
        }
        const q = this.archiveListSearch.toLowerCase();
        return this.archiveListItems.filter(arc => {
            const matchBox = arc.box_number && arc.box_number.toLowerCase().includes(q);
            const matchTitle = arc.title && arc.title.toLowerCase().includes(q);
            const matchPeriod = arc.periode_doc && arc.periode_doc.toLowerCase().includes(q);
            const matchLoc = arc.location && arc.location.toLowerCase().includes(q);
            const matchItems = arc.items && arc.items.some(it => it.document_name && it.document_name.toLowerCase().includes(q));
            return matchBox || matchTitle || matchPeriod || matchLoc || matchItems;
        });
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
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Hierarki Unit Kerja & Manajemen Berkas Arsip (TDBGrid Master-Detail Engine)</p>
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
                        <th class="py-2 px-3 text-center">SUB-DEPARTEMEN</th>
                        <th @click="sortBy('archives_count')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition text-center">
                            <div class="flex items-center justify-center gap-1">
                                TOTAL BERKAS ARSIP
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
                            <td class="py-2 px-3 text-center font-mono">
                                <!-- Interactive Total Berkas Badge (Click to open list modal) -->
                                <button 
                                    @click="openDeptArchives(dept)" 
                                    type="button"
                                    class="px-2.5 py-1 rounded bg-purple-500/15 hover:bg-purple-600 hover:text-white text-purple-700 dark:text-purple-300 border border-purple-500/30 text-xs font-bold transition inline-flex items-center gap-1.5 shadow-xs cursor-pointer"
                                    title="Klik untuk melihat daftar seluruh berkas arsip departemen ini"
                                >
                                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                    <span x-text="(dept.archives_count || 0) + ' Box/Berkas'"></span>
                                </button>
                            </td>
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
                            <td colspan="7" class="p-3 pl-8">
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
                                                    <th class="py-1.5 px-2.5 text-center">BERKAS ARSIP</th>
                                                    <th class="py-1.5 px-2.5 text-right">AKSI</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                                <template x-for="sub in (dept.sub_departments || [])" :key="sub.id">
                                                    <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30 transition">
                                                        <td class="py-1.5 px-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="dept.code + '-' + sub.code"></td>
                                                        <td class="py-1.5 px-2.5 font-bold text-slate-800 dark:text-slate-200" x-text="sub.name"></td>
                                                        <td class="py-1.5 px-2.5 text-slate-600 dark:text-slate-400" x-text="sub.description || '-'"></td>
                                                        <td class="py-1.5 px-2.5 font-mono text-center">
                                                            <!-- Clickable Sub-Dept Berkas Arsip Badge -->
                                                            <button 
                                                                @click="openSubDeptArchives(sub, dept)" 
                                                                type="button"
                                                                class="px-2 py-0.5 rounded bg-indigo-500/15 hover:bg-indigo-600 hover:text-white text-indigo-700 dark:text-indigo-300 border border-indigo-500/30 text-[11px] font-bold transition inline-flex items-center gap-1 cursor-pointer"
                                                                title="Klik untuk melihat berkas arsip sub-departemen ini"
                                                            >
                                                                <i data-lucide="folder-archive" class="w-3 h-3"></i>
                                                                <span x-text="(sub.archives_count || 0) + ' Berkas'"></span>
                                                            </button>
                                                        </td>
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

                                                <!-- Dedicated Row for Unassigned / Induk Archives if present -->
                                                <template x-if="dept.unassigned_archives_count && dept.unassigned_archives_count > 0">
                                                    <tr class="bg-amber-50/70 dark:bg-amber-950/20 hover:bg-amber-100/60 dark:hover:bg-amber-950/40 border-t-2 border-dashed border-amber-300 dark:border-amber-700/60 transition">
                                                        <td class="py-1.5 px-2.5 font-mono font-bold text-amber-700 dark:text-amber-400 flex items-center gap-1">
                                                            <i data-lucide="corner-down-right" class="w-3 h-3 text-amber-500"></i>
                                                            <span x-text="dept.code + '-GEN'"></span>
                                                        </td>
                                                        <td class="py-1.5 px-2.5 font-bold text-slate-800 dark:text-slate-200">
                                                            <div class="flex items-center gap-1.5">
                                                                <span>Arsip Induk & Umum (Non Sub-Unit)</span>
                                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40">Induk / General</span>
                                                            </div>
                                                        </td>
                                                        <td class="py-1.5 px-2.5 text-slate-500 dark:text-slate-400 italic">
                                                            Berkas arsip tingkat induk departemen yang belum dialokasikan ke sub-unit tertentu
                                                        </td>
                                                        <td class="py-1.5 px-2.5 font-mono text-center">
                                                            <button 
                                                                @click="openUnassignedDeptArchives(dept)" 
                                                                type="button"
                                                                class="px-2 py-0.5 rounded bg-amber-500/20 hover:bg-amber-600 hover:text-white text-amber-800 dark:text-amber-300 border border-amber-500/40 text-[11px] font-bold transition inline-flex items-center gap-1 cursor-pointer shadow-xs"
                                                                title="Klik untuk melihat berkas arsip induk/umum departemen ini"
                                                            >
                                                                <i data-lucide="archive" class="w-3 h-3 text-amber-600 dark:text-amber-400"></i>
                                                                <span x-text="dept.unassigned_archives_count + ' Berkas'"></span>
                                                            </button>
                                                        </td>
                                                        <td class="py-1.5 px-2.5 text-right font-mono text-slate-400 text-[10px] italic">
                                                            Unit Induk
                                                        </td>
                                                    </tr>
                                                </template>

                                                <tr x-show="(!dept.sub_departments || dept.sub_departments.length === 0) && (!dept.unassigned_archives_count || dept.unassigned_archives_count === 0)">
                                                    <td colspan="5" class="py-3 text-center text-slate-500 font-mono text-[11px]">
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
                        <td colspan="7" class="py-8 text-center text-slate-500 font-mono text-xs">Tidak ada data departemen yang cocok dengan filter pencarian.</td>
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

    <!-- WINDOWS FORM DIALOG MODAL 5: LIST BERKAS ARSIP (DRILL-DOWN) -->
    <div x-show="openArchiveListModal" 
         x-cloak 
         x-data="{ 
             posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
             startDrag(e) { 
                 if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select') || e.target.closest('table')) return; 
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
         class="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-xs flex items-center justify-center p-4">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
             class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-4xl w-full shadow-2xl overflow-hidden font-mono max-h-[90vh] flex flex-col">
            <!-- Window Title Bar (Draggable) -->
            <div @mousedown="startDrag($event)" 
                 :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                 title="Klik & tahan untuk menggeser/reposisi posisi form (Drag to move)"
                 class="bg-gradient-to-r from-purple-900 via-slate-800 to-indigo-950 text-white px-3 py-2 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none shrink-0">
                <div class="flex items-center gap-2 font-bold pointer-events-none">
                    <i data-lucide="archive" class="w-4 h-4 text-purple-400"></i>
                    <div>
                        <span x-text="archiveListTitle"></span>
                        <p class="text-[10px] text-slate-300 font-normal font-sans" x-text="archiveListSubtitle"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openArchiveListModal = false; resetPos()" type="button" class="text-slate-400 hover:text-white text-sm font-bold px-1.5 py-0.5 rounded hover:bg-rose-600">✕</button>
                </div>
            </div>

            <!-- Toolbar Search inside Modal -->
            <div class="p-3 bg-white dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between gap-3 shrink-0">
                <div class="relative flex-1 max-w-xs">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400"></i>
                    <input 
                        type="text" 
                        x-model="archiveListSearch" 
                        placeholder="Filter no box, nama dokumen, periode..." 
                        class="w-full pl-8 pr-3 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"
                    >
                </div>
                <div class="text-[11px] font-mono text-slate-600 dark:text-slate-400">
                    Total: <strong class="text-purple-600 dark:text-purple-400" x-text="filteredArchiveList.length"></strong> Box Arsip
                </div>
            </div>

            <!-- Modal Content Table -->
            <div class="p-3 overflow-y-auto flex-1 font-sans text-xs">
                <!-- Loading State -->
                <div x-show="archiveListLoading" class="py-12 text-center text-purple-600 dark:text-purple-400 font-mono">
                    <i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto mb-2"></i>
                    <span>Memuat data berkas arsip...</span>
                </div>

                <!-- Empty State -->
                <div x-show="!archiveListLoading && filteredArchiveList.length === 0" class="py-12 text-center text-slate-500 font-mono">
                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                    <p>Tidak ada data berkas arsip yang terdaftar.</p>
                </div>

                <!-- Table of Archives -->
                <div x-show="!archiveListLoading && filteredArchiveList.length > 0" class="border border-slate-300 dark:border-slate-800 rounded overflow-hidden">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                                <th class="py-2 px-2.5 text-center">#</th>
                                <th class="py-2 px-3">NO. BOX KARDUS</th>
                                <th class="py-2 px-3">SUB-UNIT</th>
                                <th class="py-2 px-3">PERIODE / TGL SERAH</th>
                                <th class="py-2 px-3">LOKASI GUDANG & RAK</th>
                                <th class="py-2 px-3 text-center">BUTIR DOKUMEN</th>
                                <th class="py-2 px-3 text-center">STATUS</th>
                                <th class="py-2 px-3 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <template x-for="(arc, idx) in filteredArchiveList" :key="arc.id">
                                <tr @click="showArchiveDetail(arc)" class="hover:bg-purple-500/10 cursor-pointer transition">
                                    <td class="py-2 px-2.5 text-center font-mono font-bold text-slate-500" x-text="idx + 1"></td>
                                    <td class="py-2 px-3 font-mono font-bold text-amber-600 dark:text-amber-400" x-text="arc.box_number"></td>
                                    <td class="py-2 px-3 font-semibold text-slate-800 dark:text-slate-200" x-text="arc.sub_department"></td>
                                    <td class="py-2 px-3 font-mono text-[11px] text-slate-600 dark:text-slate-400">
                                        <div x-text="arc.periode_doc"></div>
                                        <div class="text-[10px] text-slate-500" x-text="'Serah: ' + arc.tgl_penyerahan"></div>
                                    </td>
                                    <td class="py-2 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                                        <div class="font-bold text-emerald-600 dark:text-emerald-400" x-text="arc.location"></div>
                                        <div class="text-[10px] text-slate-500" x-text="'Rak: ' + arc.rack_code + ' | Slot: ' + arc.slot_code"></div>
                                    </td>
                                    <td class="py-2 px-3 text-center font-mono">
                                        <span class="px-2 py-0.5 bg-blue-500/15 text-blue-700 dark:text-blue-300 border border-blue-500/30 rounded font-bold text-[11px]" x-text="arc.items_count + ' Arsip'"></span>
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border font-mono uppercase" :class="arc.status_badge" x-text="arc.status_label"></span>
                                    </td>
                                    <td class="py-2 px-3 text-right">
                                        <button 
                                            @click.stop="showArchiveDetail(arc)" 
                                            type="button" 
                                            class="px-2.5 py-1 bg-purple-600 hover:bg-purple-500 text-white rounded text-[11px] font-mono font-bold shadow-xs transition inline-flex items-center gap-1"
                                        >
                                            <i data-lucide="eye" class="w-3 h-3"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between font-mono text-xs shrink-0">
                <span class="text-slate-500 text-[11px]">Klik baris atau tombol Detail untuk membuka butir-butir dokumen di dalam box.</span>
                <button type="button" @click="openArchiveListModal = false; resetPos()" class="px-4 py-1.5 bg-slate-300 hover:bg-slate-400 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold rounded">
                    Tutup (Esc)
                </button>
            </div>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 6: DETAIL BERKAS DOKUMEN (MASTER-DETAIL VIEW) -->
    <template x-if="selectedArchiveDetail">
        <div x-data="{ 
                 posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
                 startDrag(e) { 
                     if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select') || e.target.closest('table')) return; 
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
             @keydown.escape.window="selectedArchiveDetail = null"
             class="fixed inset-0 z-[70] bg-slate-950/85 backdrop-blur-xs flex items-center justify-center p-4"
             style="z-index: 70;">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
                 class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-2xl w-full shadow-2xl overflow-hidden font-mono max-h-[92vh] flex flex-col">
                <!-- Window Title Bar (Draggable) -->
                <div @mousedown="startDrag($event)" 
                     :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                     title="Klik & tahan untuk menggeser/reposisi posisi form (Drag to move)"
                     class="bg-gradient-to-r from-amber-950 via-slate-800 to-purple-950 text-white px-3 py-2 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none shrink-0">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                        <i data-lucide="file-check" class="w-4 h-4 text-amber-400"></i> frmArchiveDocDetail : Rincian Butir Dokumen Box
                    </span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="selectedArchiveDetail = null; resetPos()" type="button" class="text-slate-400 hover:text-white text-sm font-bold px-1.5 py-0.5 rounded hover:bg-rose-600">✕</button>
                    </div>
                </div>

                <!-- Modal Body Content -->
                <div class="p-4 space-y-3 overflow-y-auto flex-1 font-sans text-xs">
                    <!-- Box Header Card -->
                    <div class="p-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-xs space-y-2 font-mono">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase block">NOMOR IDENTITAS BOX</span>
                                <span class="text-sm font-black text-amber-600 dark:text-amber-400" x-text="selectedArchiveDetail.box_number"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase block">STATUS GUDANG</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase" :class="selectedArchiveDetail.status_badge" x-text="selectedArchiveDetail.status_label"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-[11px]">
                            <div>
                                <span class="text-slate-500 block text-[10px]">SUB-DEPARTEMEN:</span>
                                <strong class="text-slate-900 dark:text-white" x-text="selectedArchiveDetail.sub_department"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">PERIODE PENGISIAN:</span>
                                <strong class="text-purple-700 dark:text-purple-300" x-text="selectedArchiveDetail.periode_doc"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">TGL. PENYERAHAN:</span>
                                <strong class="text-slate-800 dark:text-slate-200" x-text="selectedArchiveDetail.tgl_penyerahan"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">LOKASI PENYIMPANAN:</span>
                                <strong class="text-emerald-600 dark:text-emerald-400" x-text="selectedArchiveDetail.location"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">RAK & SLOT:</span>
                                <strong class="text-slate-800 dark:text-slate-200" x-text="'Rak: ' + selectedArchiveDetail.rack_code + ' | Slot: ' + selectedArchiveDetail.slot_code"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">KONDISI WADAH:</span>
                                <strong class="text-slate-800 dark:text-slate-200" x-text="selectedArchiveDetail.physical_condition"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Items Detail Table (1 Box -> Banyak Dokumen Arsip) -->
                    <div class="border border-slate-300 dark:border-slate-800 rounded bg-white dark:bg-slate-950 overflow-hidden shadow-xs">
                        <div class="bg-slate-100 dark:bg-slate-900 px-3 py-2 border-b border-slate-300 dark:border-slate-800 font-mono text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="list-checks" class="w-3.5 h-3.5 text-emerald-500"></i>
                                Rincian Butir Dokumen di Dalam Kardus (<span x-text="selectedArchiveDetail.items ? selectedArchiveDetail.items.length : 0"></span> Berkas)
                            </span>
                            <span class="text-[10px] text-slate-500 font-normal">Format Standar Box TB 30g</span>
                        </div>

                        <div class="overflow-x-auto max-h-60">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 font-mono text-[10px] text-slate-600 dark:text-slate-400 select-none">
                                        <th class="py-1.5 px-2.5 w-10 text-center">NO</th>
                                        <th class="py-1.5 px-3">NAMA BERKAS / DOKUMEN ARSIP</th>
                                        <th class="py-1.5 px-3">PERIODE (KAPAN S/D KAPAN)</th>
                                        <th class="py-1.5 px-3">KETERANGAN</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <template x-for="it in (selectedArchiveDetail.items || [])" :key="it.item_number">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50">
                                            <td class="py-2 px-2.5 text-center font-mono font-bold text-purple-600 dark:text-purple-400" x-text="it.item_number"></td>
                                            <td class="py-2 px-3 font-bold text-slate-800 dark:text-slate-200" x-text="it.document_name"></td>
                                            <td class="py-2 px-3 font-mono text-amber-700 dark:text-amber-300 font-semibold" x-text="it.period_text || '-'"></td>
                                            <td class="py-2 px-3 text-slate-600 dark:text-slate-400" x-text="it.notes || '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!selectedArchiveDetail.items || selectedArchiveDetail.items.length === 0">
                                        <td colspan="4" class="py-4 text-center text-slate-500 font-mono text-xs">
                                            Belum ada butir dokumen terdaftar.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Actions -->
                <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between font-mono text-xs shrink-0">
                    <a :href="selectedArchiveDetail.url" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded shadow-xs inline-flex items-center gap-1">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Buka Halaman Lengkap Arsip</span>
                    </a>
                    <button type="button" @click="selectedArchiveDetail = null; resetPos()" class="px-4 py-1.5 bg-slate-300 hover:bg-slate-400 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold rounded">
                        Tutup (Esc)
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
