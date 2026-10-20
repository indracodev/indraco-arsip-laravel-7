@extends('layouts.app')

@section('title', 'Master Departemen & Sub-Departemen - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="masterDepartmentsManager()">

    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm font-mono">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2.5">
            <!-- Left Header Title Area -->
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="p-2 bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30 rounded shrink-0">
                    <i data-lucide="building-2" class="w-4 h-4"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate">Master Departemen & Sub-Departemen</h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Hierarki Unit Kerja & Manajemen Berkas Arsip </p>
                </div>
            </div>

            <!-- Right Action Controls -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Search Input -->
                <div class="relative flex-1 sm:w-60 min-w-[140px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                    <input 
                        type="search" 
                        x-model="searchQuery" 
                        placeholder="Cari Dept / Sub-Dept..." 
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                        name="dept_table_search_filter"
                        id="deptTableSearchFilter"
                        class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 transition"
                    >
                    <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>

                <!-- Expand / Collapse All -->
                <button @click="expandAll()" type="button" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0 cursor-pointer" title="Buka Semua Sub-Departemen">
                    <i data-lucide="chevrons-down" class="w-3 h-3"></i>
                    <span>Expand All</span>
                </button>
                <button @click="collapseAll()" type="button" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0 cursor-pointer" title="Tutup Semua Sub-Departemen">
                    <i data-lucide="chevrons-up" class="w-3 h-3"></i>
                    <span>Collapse</span>
                </button>

                <!-- Refresh Button (F5) -->
                <button @click="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0 cursor-pointer" title="Segarkan Data (F5)">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    <span>Refresh</span>
                </button>

                @if(!auth()->user()->isPicGudang())
                <!-- Add Sub-Dept Button -->
                <button @click="openCreateSubDept('')" type="button" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white font-mono font-bold text-xs rounded border border-amber-700 shadow transition flex items-center gap-1.5 shrink-0 cursor-pointer" title="Tambah Sub-Departemen Baru">
                    <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                    <span>+ Sub-Dept</span>
                </button>

                <!-- Add Department (F2) -->
                <button @click="openAddModal()" type="button" class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white font-mono font-bold text-xs rounded border border-purple-700 shadow transition flex items-center gap-1.5 shrink-0 cursor-pointer">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>+ Dept (F2)</span>
                </button>
                @endif
            </div>
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
                        <tr :class="[expandedDepts[dept.id] ? 'bg-purple-50/50 dark:bg-purple-950/20 font-semibold' : 'hover:bg-amber-500/10 dark:hover:bg-amber-500/20', dept.is_active === false ? 'opacity-65 bg-rose-50/30 dark:bg-rose-950/20' : '']" class="transition">
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
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span :class="dept.is_active === false ? 'line-through text-slate-500' : ''" x-text="dept.name"></span>
                                    <span x-show="dept.code === 'FIN'" class="px-1.5 py-0.5 bg-rose-500/20 text-rose-700 dark:text-rose-300 text-xs font-mono font-bold border border-rose-500/30 rounded uppercase">Ruang FAT Locked</span>
                                    <span x-show="dept.is_active === false" class="px-1.5 py-0.5 bg-rose-500/20 text-rose-700 dark:text-rose-400 text-xs font-mono font-bold border border-rose-500/30 rounded uppercase">Nonaktif</span>
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
                                    @if(auth()->user()->isSuperAdmin())
                                    <!-- Toggle Active Dept (Superadmin Only) -->
                                    <button 
                                        @click.stop="toggleDeptActive(dept)" 
                                        type="button" 
                                        :class="dept.is_active !== false ? 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border-rose-500/30'" 
                                        class="px-2 py-1 border rounded text-[11px] font-bold transition flex items-center gap-1 shadow-xs cursor-pointer" 
                                        :title="dept.is_active !== false ? 'Klik untuk Nonaktifkan Departemen (Hanya Superadmin)' : 'Klik untuk Aktifkan Departemen (Hanya Superadmin)'"
                                    >
                                        <i :data-lucide="dept.is_active !== false ? 'check-circle' : 'power-off'" class="w-3 h-3" :class="dept.is_active !== false ? 'text-emerald-500' : 'text-rose-500'"></i>
                                        <span x-text="dept.is_active !== false ? 'Aktif' : 'Nonaktif'"></span>
                                    </button>
                                    @endif
                                    <!-- Kelola Dept (PIC & Master Arsip) -->
                                    <button @click.stop="openManageDept(dept)" type="button" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-[11px] font-bold transition flex items-center gap-1 shadow-xs border border-indigo-700 cursor-pointer" title="Kelola User PIC dan Master Dokumen Arsip Departemen ini">
                                        <i data-lucide="sliders" class="w-3 h-3 text-amber-300"></i>
                                        <span>Kelola</span>
                                    </button>
                                    @if(!auth()->user()->isPicGudang())
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
                                        <button type="submit" 
                                                :data-confirm="'Hapus departemen ' + dept.name + ' beserta seluruh konfigurasinya?'"
                                                data-confirm-title="Hapus Departemen"
                                                data-confirm-type="danger"
                                                data-confirm-btn="Ya, Hapus"
                                                class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[11px] font-bold transition flex items-center gap-1" title="Hapus Departemen">
                                            <i data-lucide="trash-2" class="w-3 h-3 text-rose-500"></i> Hapus
                                        </button>
                                    </form>
                                    @endif
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
                                        @if(!auth()->user()->isPicGudang())
                                        <button @click="openCreateSubDept(dept.id)" type="button" class="px-2.5 py-0.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] rounded transition flex items-center gap-1 shadow-sm">
                                            <i data-lucide="plus-circle" class="w-3 h-3"></i>
                                            <span>Tambah Sub-Departemen</span>
                                        </button>
                                        @endif
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
                                                    <tr :class="sub.is_active === false ? 'opacity-65 bg-rose-50/20 dark:bg-rose-950/20' : 'hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30'" class="transition">
                                                        <td class="py-1.5 px-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="dept.code + '-' + sub.code"></td>
                                                        <td class="py-1.5 px-2.5 font-bold text-slate-800 dark:text-slate-200">
                                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                                <span :class="sub.is_active === false ? 'line-through text-slate-500' : ''" x-text="sub.name"></span>
                                                                <span x-show="sub.is_active === false" class="px-1.5 py-0.5 bg-rose-500/20 text-rose-700 dark:text-rose-400 text-xs font-mono font-bold border border-rose-500/30 rounded uppercase">Nonaktif</span>
                                                            </div>
                                                        </td>
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
                                                                @if(auth()->user()->isSuperAdmin())
                                                                <!-- Toggle Active Sub-Dept (Superadmin Only) -->
                                                                <button 
                                                                    @click.stop="toggleSubDeptActive(sub, dept)" 
                                                                    type="button" 
                                                                    :class="sub.is_active !== false ? 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border-rose-500/30'" 
                                                                    class="px-1.5 py-0.5 border rounded text-[10px] font-bold transition flex items-center gap-1 shadow-xs cursor-pointer" 
                                                                    :title="sub.is_active !== false ? 'Klik untuk Nonaktifkan Sub-Dept (Hanya Superadmin)' : 'Klik untuk Aktifkan Sub-Dept (Hanya Superadmin)'"
                                                                >
                                                                    <i :data-lucide="sub.is_active !== false ? 'check-circle' : 'power-off'" class="w-2.5 h-2.5" :class="sub.is_active !== false ? 'text-emerald-500' : 'text-rose-500'"></i>
                                                                    <span x-text="sub.is_active !== false ? 'Aktif' : 'Nonaktif'"></span>
                                                                </button>
                                                                @endif
                                                                <button @click.stop="openManageSubDept(dept, sub)" type="button" class="px-1.5 py-0.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-[10px] font-bold transition flex items-center gap-1 shadow-xs border border-indigo-700 cursor-pointer" title="Kelola Master Dokumen Sub-Departemen ini">
                                                                    <i data-lucide="sliders" class="w-2.5 h-2.5 text-amber-300"></i>
                                                                    <span>Kelola</span>
                                                                </button>
                                                                @if(!auth()->user()->isPicGudang())
                                                                <button @click="openEditSubDept(sub, dept.id)" class="px-1.5 py-0.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-[10px] font-bold transition flex items-center gap-1">
                                                                    <i data-lucide="edit-2" class="w-2.5 h-2.5 text-amber-500"></i> Edit
                                                                </button>
                                                                <form :action="'{{ url('/master/sub-departments') }}/' + sub.id" method="POST" class="inline" @submit="submitting = true">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" 
                                                                            :data-confirm="'Hapus sub-departemen ' + sub.name + '?'"
                                                                            data-confirm-title="Hapus Sub-Departemen"
                                                                            data-confirm-type="danger"
                                                                            data-confirm-btn="Ya, Hapus"
                                                                            class="px-1.5 py-0.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[10px] font-bold transition flex items-center gap-1">
                                                                        <i data-lucide="trash-2" class="w-2.5 h-2.5 text-rose-500"></i> Hapus
                                                                    </button>
                                                                </form>
                                                                @endif
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
                                                                <span class="px-1.5 py-0.5 rounded text-xs font-mono font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40">Induk / General</span>
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
                                                        <td class="py-1.5 px-2.5 text-right font-mono">
                                                            <button @click.stop="openManageSubDept(dept, { id: '', code: 'GEN', name: 'Arsip Induk & Umum' })" type="button" class="px-1.5 py-0.5 bg-amber-600 hover:bg-amber-500 text-white rounded text-[10px] font-bold transition inline-flex items-center gap-1 shadow-xs border border-amber-700 cursor-pointer" title="Kelola Master Dokumen Arsip Induk">
                                                                <i data-lucide="sliders" class="w-2.5 h-2.5 text-amber-200"></i>
                                                                <span>Kelola</span>
                                                            </button>
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
            <span></span>
        </div>
    </div>


    <!-- MODULAR DEPARTMENT MANAGEMENT MODALS -->
    @include('master.departments.modals.department_modals')
    @include('master.departments.modals.subdepartment_modals')
    @include('master.departments.modals.archive_drilldown_modals')
    @include('master.departments.modals.manage_department_modal')
</div>
@endsection

@push('scripts')
@include('master.departments.partials.departments_scripts')
@endpush
