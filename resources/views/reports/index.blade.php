@extends('layouts.app')

@section('title', 'Pusat Laporan & Dokumen PDF - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="reportCenterApp()">

    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm font-mono">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2.5">
            <!-- Left Header Title Area -->
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="p-2 bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 rounded shrink-0">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate">Pusat Laporan & Dokumen PDF</h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Generator Laporan Master, Utilisasi Depo Gudang & Analisis Rak 2D</p>
                </div>
            </div>

            <!-- Right Action Controls -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Refresh Button (F5) -->
                <button @click="loadReportData()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0 cursor-pointer" title="Segarkan Data Laporan">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="isLoading ? 'animate-spin' : ''"></i>
                    <span>Refresh</span>
                </button>

                <!-- Export CSV / Excel -->
                <a :href="getExportCsvUrl()" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-mono font-bold text-xs rounded border border-emerald-700 shadow transition flex items-center gap-1 shrink-0" title="Download Format CSV Spreadsheet">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                    <span>Export CSV</span>
                </a>

                <!-- Print / Open PDF Preview Window (F4) -->
                <button @click="openPrintPreview(true)" type="button" class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5 shrink-0 cursor-pointer" title="Cetak atau Simpan PDF (Ctrl+P / F4)">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Cetak PDF</span>
                </button>

                <!-- Open Standalone Tab -->
                <button @click="openPrintPreview(false)" type="button" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-white font-mono font-bold text-xs rounded border border-slate-900 shadow transition flex items-center gap-1 shrink-0 cursor-pointer" title="Buka Pratinjau di Tab Baru">
                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Preview Tab</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 7 REPORT TYPES NAVIGATION TABS (DELPHI TPAGEPAGE CONTROL) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-1.5 shadow-sm font-mono overflow-x-auto no-scrollbar">
        <div class="flex items-center gap-1.5 min-w-max">
            <template x-for="tab in reportTabs" :key="tab.id">
                <button 
                    @click="switchTab(tab.id)"
                    type="button"
                    :class="activeType === tab.id 
                        ? 'bg-amber-500 text-slate-950 font-black border-amber-600 shadow-sm' 
                        : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 font-bold'"
                    class="px-2.5 py-1.5 rounded border text-[11px] transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
                >
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-xs font-bold" 
                          :class="activeType === tab.id ? 'bg-slate-950 text-amber-400' : 'bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300'" 
                          x-text="tab.number"></span>
                    <i :data-lucide="tab.icon" class="w-3.5 h-3.5"></i>
                    <span x-text="tab.name"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- LIVE FILTER & SEARCH BAR -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 shadow-sm font-mono flex flex-wrap items-center justify-between gap-2.5">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[200px]">
            <!-- Search in Report Table -->
            <div class="relative flex-1 min-w-[160px]">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari baris data laporan..." 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition"
                >
                <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>

            <!-- Filter Department (Show for users, master_racks, rack_usage) -->
            <template x-if="['users', 'master_racks', 'rack_usage'].includes(activeType)">
                <div class="w-44 sm:w-52">
                    <select 
                        x-model="selectedDepartmentId" 
                        @change="loadReportData()"
                        class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                    >
                        <option value="">-- Semua Departemen --</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </template>

            <!-- Filter Warehouse (Show for master_racks, rack_usage, full_racks_2d) -->
            <template x-if="['master_racks', 'rack_usage', 'full_racks_2d'].includes(activeType)">
                <div class="w-40 sm:w-48">
                    <select 
                        x-model="selectedWarehouseId" 
                        @change="loadReportData()"
                        class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                    >
                        <option value="">-- Semua Gudang --</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>
            </template>
        </div>

        <div class="text-[11px] text-slate-500 dark:text-slate-400 font-bold flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Menampilkan <strong class="text-indigo-600 dark:text-indigo-400" x-text="filteredRows.length"></strong> baris</span>
        </div>
    </div>

    <!-- DYNAMIC EXECUTIVE SUMMARY KPI STATS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 font-mono" x-show="reportData && reportData.summary">
        <template x-for="(sum, sIdx) in (reportData ? reportData.summary : [])" :key="sIdx">
            <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider block" x-text="sum.label"></span>
                    <span class="text-sm sm:text-base font-black text-slate-900 dark:text-white mt-0.5 block" x-text="sum.value"></span>
                </div>
                <div class="p-2 rounded bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-indigo-600 dark:text-indigo-400 shrink-0">
                    <i :data-lucide="sum.icon || 'activity'" class="w-4 h-4"></i>
                </div>
            </div>
        </template>
    </div>

    <!-- DELPHI TDBGRID REPORT TABLE CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        
        <!-- Loading Overlay -->
        <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/75 dark:bg-slate-950/75 backdrop-blur-xs z-20 flex flex-col items-center justify-center font-mono">
            <div class="w-10 h-10 rounded-full border-3 border-slate-300 dark:border-slate-800 border-t-indigo-500 animate-spin mb-2"></div>
            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Mengambil Data Laporan...</span>
        </div>

        <div class="overflow-x-auto min-h-[260px] max-h-[58vh]">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="font-mono text-[10px] uppercase select-none sticky top-0 z-10">
                        <template x-for="(col, cIdx) in (reportData ? reportData.columns : [])" :key="cIdx">
                            <th class="py-2 px-3 whitespace-nowrap" :class="cIdx === 0 ? 'text-center w-12' : ''" x-text="col"></th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-sans">
                    <template x-for="(row, rIdx) in filteredRows" :key="rIdx">
                        <tr class="hover:bg-indigo-500/10 dark:hover:bg-indigo-500/20 transition">
                            <template x-for="(val, key) in row" :key="key">
                                <td class="py-2 px-3 whitespace-nowrap" :class="key === 'no' ? 'text-center font-mono font-bold text-slate-500' : ''">
                                    <template x-if="['Aktif', 'Kosong (0%)', 'Optimal'].includes(val)">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30" x-text="val"></span>
                                    </template>
                                    <template x-if="['Penuh (100%)', 'Kritis (>=95%)', 'Terkunci (Lock)'].includes(val)">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30" x-text="val"></span>
                                    </template>
                                    <template x-if="['Hampir Penuh (>80%)', 'Padat (80-94%)', 'Perlu Pemantauan'].includes(val)">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/30" x-text="val"></span>
                                    </template>
                                    <template x-if="!['Aktif', 'Kosong (0%)', 'Optimal', 'Penuh (100%)', 'Kritis (>=95%)', 'Terkunci (Lock)', 'Hampir Penuh (>80%)', 'Padat (80-94%)', 'Perlu Pemantauan'].includes(val)">
                                        <span :class="key.includes('code') || key.includes('capacity') || key.includes('used') || key.includes('occupancy') || key.includes('phone') ? 'font-mono' : ''" x-text="val"></span>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="filteredRows.length === 0 && !isLoading">
                        <td :colspan="reportData ? reportData.columns.length : 8" class="py-12 text-center text-slate-500 font-mono text-xs">
                            <i data-lucide="file-x" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                            <div>Tidak ada data yang cocok dengan kriteria filter laporan.</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Bar -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-3 py-1.5 font-mono text-[11px] flex items-center justify-between text-slate-600 dark:text-slate-400">
            <span>Menampilkan <strong class="text-indigo-600 dark:text-indigo-400" x-text="filteredRows.length"></strong> dari <strong x-text="reportData && reportData.rows ? reportData.rows.length : 0"></strong> total entri</span>
            <span class="flex items-center gap-1.5">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Format Standar ISO/DMS PT INDRACO</span>
            </span>
        </div>
    </div>
</div>

<script>
    function reportCenterApp() {
        return {
            activeType: '{{ $activeType ?? "master_departments" }}',
            searchQuery: '',
            selectedDepartmentId: '',
            selectedWarehouseId: '',
            isLoading: false,
            reportData: null,

            reportTabs: [
                { id: 'master_departments', number: 1, name: 'Laporan Departemen Master', icon: 'building-2' },
                { id: 'users', number: 2, name: 'Laporan User', icon: 'users' },
                { id: 'master_warehouses', number: 3, name: 'Laporan Master Gudang', icon: 'warehouse' },
                { id: 'master_racks', number: 4, name: 'Laporan Master Rak', icon: 'grid' },
                { id: 'warehouse_usage', number: 5, name: 'Laporan Penggunaan Gudang', icon: 'pie-chart' },
                { id: 'rack_usage', number: 6, name: 'Laporan Penggunaan Rak', icon: 'layers' },
                { id: 'full_racks_2d', number: 7, name: 'Laporan Rak Penuh (Layout 2D)', icon: 'map' },
            ],

            init() {
                this.loadReportData();
            },

            switchTab(type) {
                this.activeType = type;
                this.searchQuery = '';
                this.loadReportData();
            },

            loadReportData() {
                this.isLoading = true;
                const params = new URLSearchParams();
                if (this.selectedDepartmentId) params.append('department_id', this.selectedDepartmentId);
                if (this.selectedWarehouseId) params.append('warehouse_id', this.selectedWarehouseId);

                fetch(`/reports/data/${this.activeType}?${params.toString()}`)
                    .then(res => res.json())
                    .then(data => {
                        this.reportData = data;
                        this.isLoading = false;
                        this.$nextTick(() => lucide.createIcons());
                    })
                    .catch(err => {
                        console.error('Error loading report:', err);
                        this.isLoading = false;
                    });
            },

            get filteredRows() {
                if (!this.reportData || !this.reportData.rows) return [];
                if (!this.searchQuery.trim()) return this.reportData.rows;
                
                const q = this.searchQuery.toLowerCase();
                return this.reportData.rows.filter(row => {
                    return Object.values(row).some(val => 
                        String(val).toLowerCase().includes(q)
                    );
                });
            },

            getExportCsvUrl() {
                const params = new URLSearchParams();
                if (this.selectedDepartmentId) params.append('department_id', this.selectedDepartmentId);
                if (this.selectedWarehouseId) params.append('warehouse_id', this.selectedWarehouseId);
                return `/reports/export-csv/${this.activeType}?${params.toString()}`;
            },

            openPrintPreview(autoPrint = false) {
                const params = new URLSearchParams();
                if (this.selectedDepartmentId) params.append('department_id', this.selectedDepartmentId);
                if (this.selectedWarehouseId) params.append('warehouse_id', this.selectedWarehouseId);
                if (autoPrint) params.append('auto_print', '1');

                const url = `/reports/print/${this.activeType}?${params.toString()}`;
                window.open(url, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
            }
        };
    }
</script>
@endsection
