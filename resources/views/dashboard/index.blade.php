@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Dashboard Overview - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="dashboardOverviewApp()">
    <!-- DELPHI ACTION RIBBON TOOLBAR & WORKSTATION HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2">
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Selamat Datang, <span class="text-amber-600 dark:text-amber-400">{{ $user->name }}</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    @if($user->isSuperAdmin())
                        Workstation Super Admin (Akses Penuh Master Data & Seluruh Dokumen Perusahaan)
                    @elseif($user->isPicGudang())
                        Workstation Kurator Gudang (Verifikasi Arsip, Slot Rak, & Peminjaman)
                    @else
                        Workstation Departemen {{ $user->department->name ?? '' }}
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <!-- Refresh (F5) Button -->
            <button @click="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Segarkan Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Refresh (F5)</span>
            </button>

            <!-- Create Draft Button -->
            <a href="{{ route('archives.create') }}" class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5 shrink-0">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                <span>Buat Draft Arsip</span>
            </a>
        </div>
    </div>

    <!-- QUICK SEARCH TGROUPBOX CARD (Interactive Live Auto-complete & Suggestions) -->
    <div x-data="{
            searchQuery: '',
            results: [],
            keywords: [],
            recentDocs: [],
            isSuggestion: true,
            loading: false,
            showDropdown: false,
            debounceTimer: null,
            fetchResults() {
                this.loading = true;
                this.showDropdown = true;
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    fetch(`/api/search-archives?q=${encodeURIComponent(this.searchQuery)}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.type === 'suggestions') {
                                this.isSuggestion = true;
                                this.keywords = data.keywords || [];
                                this.recentDocs = data.recent || [];
                                this.results = [];
                            } else {
                                this.isSuggestion = false;
                                this.results = data.items || [];
                            }
                            this.loading = false;
                            this.$nextTick(() => lucide.createIcons());
                        })
                        .catch(() => {
                            this.results = [];
                            this.loading = false;
                        });
                }, 150);
            },
            selectKeyword(kw) {
                this.searchQuery = kw;
                this.fetchResults();
            }
         }" 
         @click.outside="showDropdown = false"
         class="relative z-30 font-mono">

        <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 shadow-sm space-y-2">
            <legend class="px-2 font-mono text-xs font-bold text-amber-600 dark:text-amber-400 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded shadow-xs flex items-center gap-1.5">
                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                PENCARIAN CEPAT DOKUMEN & KATALOG ARSIP
            </legend>

            <form action="{{ route('archives.index') }}" method="GET" class="relative">
                <div class="relative flex items-center">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 text-slate-400"></i>
                    <input 
                        type="text" 
                        name="search" 
                        x-model="searchQuery" 
                        @input="fetchResults()"
                        @focus="fetchResults(); showDropdown = true"
                        placeholder="Ketik kata kunci dokumen (contoh: BOX-FIN-2024, Pajak, HRD)... (Ctrl+F)" 
                        class="w-full pl-9 pr-28 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 font-semibold transition"
                    >
                    <div class="absolute right-1.5 flex items-center gap-1">
                        <template x-if="searchQuery">
                            <button type="button" @click="searchQuery = ''; fetchResults()" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-white" title="Clear">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                        </template>
                        <button type="submit" class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-bold text-xs rounded border border-amber-600 transition flex items-center gap-1">
                            <i data-lucide="search" class="w-3 h-3"></i>
                            <span>Cari</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Quick Filter Badges & Admin Shortcut -->
            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-slate-200 dark:border-slate-800/80">
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="text-slate-500 dark:text-slate-400 font-bold">Shortcut Filter:</span>
                    <a href="{{ route('archives.index', ['status' => 'in_warehouse']) }}" class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/20 border border-emerald-500/30 font-bold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Di Gudang
                    </a>
                    <a href="{{ route('archives.index', ['status' => 'pending_verification']) }}" class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-700 dark:text-amber-300 hover:bg-amber-500/20 border border-amber-500/30 font-bold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Antrean Verifikasi
                    </a>
                    <a href="{{ route('archives.index', ['status' => 'borrowed']) }}" class="px-2 py-0.5 rounded bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20 border border-purple-500/30 font-bold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span> Sedang Dipinjam
                    </a>
                    <a href="{{ route('archives.index', ['expiry_filter' => 'expiring_soon']) }}" class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-700 dark:text-rose-300 hover:bg-rose-500/20 border border-rose-500/30 font-bold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Expiring Soon
                    </a>
                </div>

                @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->isPicGudang()))
                <!-- Shortcut Khusus Admin Webdev & PIC Gudang -->
                <div class="flex items-center gap-2">
                    <a href="{{ route('logs.index') }}" class="px-2.5 py-1 bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-700 dark:text-cyan-300 font-mono font-bold text-xs rounded border border-cyan-500/40 shadow transition flex items-center gap-1.5 shrink-0">
                        <i data-lucide="history" class="w-3.5 h-3.5 text-cyan-500"></i>
                        <span>Log History</span>
                    </a>
                    <a href="{{ route('master.warehouses.layout') }}" class="px-3 py-1 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-mono font-bold text-xs rounded border border-emerald-500/40 shadow transition flex items-center gap-1.5 group shrink-0">
                        <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-emerald-200 group-hover:scale-110 transition"></i>
                        <span>Input & Layout Gudang (2D)</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-emerald-200 group-hover:translate-x-0.5 transition"></i>
                    </a>
                </div>
                @endif
            </div>
        </fieldset>

        <!-- Live Results & Suggestions Dropdown Card -->
        <div x-show="showDropdown" x-cloak x-transition.opacity.duration.150ms class="absolute left-0 right-0 mt-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-2xl overflow-hidden z-50 text-slate-900 dark:text-slate-100">
            
            <!-- Loading State -->
            <div x-show="loading" class="p-4 text-center text-xs font-bold text-slate-500 dark:text-slate-400 flex items-center justify-center gap-2">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-amber-500"></i> Memuat saran pencarian...
            </div>

            <!-- Mode 1: Initial Suggestions -->
            <div x-show="!loading && isSuggestion" class="divide-y divide-slate-200 dark:divide-slate-800">
                <div class="p-3 bg-slate-50 dark:bg-slate-900/60">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase mb-2">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        Saran Kata Kunci & Metadata Label Box Populer
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="kw in keywords" :key="kw">
                            <button type="button" @click="selectKeyword(kw)" class="px-2.5 py-1 rounded bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:border-amber-500 text-xs font-bold text-slate-800 dark:text-slate-200 hover:text-amber-500 transition shadow-xs flex items-center gap-1">
                                <i data-lucide="tag" class="w-3 h-3 text-slate-400"></i>
                                <span x-text="kw"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="max-h-72 overflow-y-auto">
                    <div class="px-3 py-1.5 bg-slate-100 dark:bg-slate-900 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center justify-between">
                        <span>Rekomendasi Berkas Label Terkini</span>
                        <span>Akses Cepat</span>
                    </div>
                    <template x-for="item in recentDocs" :key="item.id">
                        <a :href="item.url" class="p-2.5 flex items-center justify-between gap-3 hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition font-sans border-b border-slate-100 dark:border-slate-900">
                            <div class="space-y-0.5 min-w-0 font-mono">
                                <div class="flex items-center gap-2">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="item.dept_code"></span>
                                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="item.box_number"></span>
                                    <template x-if="item.periode_doc && item.periode_doc !== '-'">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/20" x-text="'Periode: ' + item.periode_doc"></span>
                                    </template>
                                </div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="item.title"></h4>
                                <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400">
                                    <span x-text="'Lokasi: ' + item.location"></span>
                                    <template x-if="item.sub_dept">
                                        <span x-text="'• ' + item.sub_dept"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0 font-mono">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold" 
                                      :class="{
                                          'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400': item.status === 'draft',
                                          'bg-amber-500/20 text-amber-700 dark:text-amber-300': item.status === 'pending_verification',
                                          'bg-blue-500/20 text-blue-700 dark:text-blue-300': item.status === 'approved_booked',
                                          'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300': item.status === 'in_warehouse',
                                          'bg-purple-500/20 text-purple-700 dark:text-purple-300': item.status === 'borrowed'
                                      }" 
                                      x-text="item.status_label">
                                </span>
                                <template x-if="item.is_expired">
                                    <span class="px-1.5 py-0.5 rounded text-xs font-bold bg-rose-500 text-white">EXPIRED</span>
                                </template>
                            </div>
                        </a>
                    </template>
                </div>
            </div>

            <!-- Mode 2: Live Query Search Results -->
            <div x-show="!loading && !isSuggestion">
                <div x-show="results.length === 0" class="p-4 text-center text-xs font-bold text-slate-500">
                    Tidak ada dokumen ditemukan untuk kata kunci ini.
                </div>
                <div x-show="results.length > 0" class="divide-y divide-slate-200 dark:divide-slate-800 max-h-72 overflow-y-auto">
                    <template x-for="item in results" :key="item.id">
                        <a :href="item.url" class="p-2.5 flex items-center justify-between gap-3 hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition font-mono border-b border-slate-100 dark:border-slate-900">
                            <div class="space-y-0.5 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="item.dept_code"></span>
                                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="item.box_number"></span>
                                    <template x-if="item.periode_doc && item.periode_doc !== '-'">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/20" x-text="'Periode: ' + item.periode_doc"></span>
                                    </template>
                                </div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="item.title"></h4>
                                <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400">
                                    <span x-text="'Lokasi: ' + item.location"></span>
                                    <template x-if="item.slot_code">
                                        <span class="text-amber-500" x-text="'[' + item.slot_code + ']'"></span>
                                    </template>
                                    <template x-if="item.sub_dept">
                                        <span x-text="'• ' + item.sub_dept"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0 font-mono">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold" 
                                      :class="{
                                          'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400': item.status === 'draft',
                                          'bg-amber-500/20 text-amber-700 dark:text-amber-300': item.status === 'pending_verification',
                                          'bg-blue-500/20 text-blue-700 dark:text-blue-300': item.status === 'approved_booked',
                                          'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300': item.status === 'in_warehouse',
                                          'bg-purple-500/20 text-purple-700 dark:text-purple-300': item.status === 'borrowed'
                                      }" 
                                      x-text="item.status_label">
                                </span>
                                <template x-if="item.is_expired">
                                    <span class="px-1.5 py-0.5 rounded text-xs font-bold bg-rose-500 text-white">EXPIRED</span>
                                </template>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- STAT CARDS GRID (DELPHI TSTATISTICS PANELS) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 font-mono">
        <!-- Total Active Archives -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm hover:border-blue-500 transition">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px] font-bold">
                <span>TOTAL ARSIP</span>
                <i data-lucide="archive" class="w-4 h-4 text-blue-500"></i>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-2xl font-black text-slate-900 dark:text-white">{{ $totalArchives }}</span>
                <span class="text-[10px] text-slate-500">Box</span>
            </div>
        </div>

        <!-- In Warehouse -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm hover:border-emerald-500 transition">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px] font-bold">
                <span>TERSIMPAN GUDANG</span>
                <i data-lucide="warehouse" class="w-4 h-4 text-emerald-500"></i>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $inWarehouseCount }}</span>
                <span class="text-[10px] text-slate-500">Slot</span>
            </div>
        </div>

        <!-- Pending Verification Queue -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm hover:border-amber-500 transition">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px] font-bold">
                <span>ANTEAN BOOKING</span>
                <i data-lucide="clock" class="w-4 h-4 text-amber-500"></i>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ $pendingVerificationCount }}</span>
                <span class="text-[10px] text-slate-500">Pengajuan</span>
            </div>
        </div>

        <!-- Borrowed / Dokumen Keluar -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm hover:border-purple-500 transition">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px] font-bold">
                <span>DOKUMEN KELUAR</span>
                <i data-lucide="file-symlink" class="w-4 h-4 text-purple-500"></i>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-2xl font-black text-purple-600 dark:text-purple-400">{{ $borrowedCount }}</span>
                <span class="text-[10px] text-slate-500">Out</span>
            </div>
        </div>

        <!-- Retention Expiry Alert -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm hover:border-rose-500 transition col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px] font-bold">
                <span>ALERT EXPIRED</span>
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500"></i>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ $expiringCount }}</span>
                <span class="text-[10px] text-slate-500">Berkas</span>
            </div>
        </div>
    </div>

    <!-- COMPACT CAPACITY & RETENTION STATUS RIBBON -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 font-mono text-xs">
        <!-- Compact Kapasitas Gudang Arsip -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 shadow-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="p-1.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 shrink-0">
                    <i data-lucide="boxes" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-slate-500 font-bold uppercase">Kapasitas Rak Gudang:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $usedCapacity }} / {{ $totalCapacity }} Box</span>
                        <span class="px-1.5 py-0.2 rounded bg-amber-500/15 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-[10px] font-black">{{ $capacityPercent }}%</span>
                    </div>
                    <div class="w-40 sm:w-56 h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden mt-1 border border-slate-300/60 dark:border-slate-700">
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-amber-500 transition-all duration-300" style="width: {{ min($capacityPercent, 100) }}%"></div>
                    </div>
                </div>
            </div>
            <a href="{{ route('master.warehouses') }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-[11px] font-bold text-amber-600 dark:text-amber-400 transition inline-flex items-center gap-1 shrink-0 shadow-xs">
                <span>Kelola Lokasi</span>
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
            </a>
        </div>

        <!-- Compact Pemberitahuan Retensi & Expiry -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 shadow-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="p-1.5 rounded {{ $expiringArchives->isEmpty() ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-400 animate-pulse' }} border shrink-0">
                    <i data-lucide="{{ $expiringArchives->isEmpty() ? 'shield-check' : 'hourglass' }}" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Status Masa Retensi (90 Hari):</span>
                    @if($expiringArchives->isEmpty())
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold text-[11px] flex items-center gap-1">
                            <span>Semua berkas aman (Belum ada yang expired)</span>
                        </span>
                    @else
                        <span class="text-rose-600 dark:text-rose-400 font-bold text-[11px] flex items-center gap-1">
                            <span class="font-black">{{ $expiringArchives->count() }} berkas</span>
                            <span>mendekati masa pemusnahan</span>
                        </span>
                    @endif
                </div>
            </div>
            <a href="{{ route('destructions.index') }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-[11px] font-bold {{ $expiringArchives->isEmpty() ? 'text-slate-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400' }} transition inline-flex items-center gap-1 shrink-0 shadow-xs">
                <span>Pemusnahan BAP</span>
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
            </a>
        </div>
    </div>

    <!-- BOTTOM DELPHI TDBGRID SPREADSHEET TABLE: RECENT ARCHIVES -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm overflow-hidden font-sans">
        <div class="p-2.5 bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between font-mono">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                <i data-lucide="folder-git-2" class="w-4 h-4 text-blue-500"></i>
                BERKAS ARSIP TERBARU
            </div>
            <a href="{{ route('archives.index') }}" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition">
                Buka Katalog Utama
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="font-mono text-[11px] select-none">
                        <th class="py-2 px-3">NO. BOX ARSIP</th>
                        <th class="py-2 px-3">JUDUL BERKAS</th>
                        <th class="py-2 px-3">DEPARTEMEN</th>
                        <th class="py-2 px-3">PERIODE</th>
                        <th class="py-2 px-3">LOKASI FISIK</th>
                        <th class="py-2 px-3">STATUS WORKFLOW</th>
                        <th class="py-2 px-3 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($recentArchives as $archive)
                        <tr class="hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition">
                            <td class="py-2 px-3 font-mono text-xs text-amber-600 dark:text-amber-400 font-bold whitespace-nowrap">
                                @if($archive->box_number)
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="qr-code" class="w-3.5 h-3.5 text-amber-500"></i>
                                        <span>{{ $archive->box_number }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 italic font-normal text-[11px]">- Penomoran Pending -</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 font-bold text-slate-900 dark:text-white font-mono">
                                <a href="{{ route('archives.show', $archive) }}" class="hover:text-amber-600 dark:hover:text-amber-400 hover:underline transition">
                                    {{ $archive->effective_title ?? $archive->title }}
                                </a>
                            </td>
                            <td class="py-2 px-3 font-mono">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $archive->department->code ?? 'GEN' }}
                                </span>
                            </td>
                            <td class="py-2 px-3 text-xs text-slate-600 dark:text-slate-400 font-mono">
                                {{ $archive->effective_periode ?? ($archive->period_start_date ? $archive->period_start_date->format('M Y') : '-') }}
                            </td>
                            <td class="py-2 px-3 text-xs font-mono whitespace-nowrap">
                                @if($archive->location)
                                    <div class="flex items-center gap-1.5 font-bold text-emerald-700 dark:text-emerald-400">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                                        <span>{{ $archive->display_location }}</span>
                                    </div>
                                @else
                                    <button 
                                        type="button" 
                                        @click="openQuickSlotModal({
                                            id: {{ $archive->id }},
                                            title: {{ json_encode($archive->effective_title ?? $archive->title) }},
                                            box_number: {{ json_encode($archive->box_number ?: 'Penomoran Pending') }},
                                            department_id: {{ $archive->department_id ?? 0 }},
                                            department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                            department_name: {{ json_encode($archive->department->name ?? '') }}
                                        })"
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold bg-rose-500/15 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-500/40 hover:bg-rose-500/25 transition shadow-xs animate-pulse cursor-pointer group select-none"
                                        title="Lokasi belum ditentukan! Klik untuk menentukan Gudang, Rak, dan Box"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                        <span class="underline decoration-dotted underline-offset-2">Belum Ditentukan</span>
                                        <i data-lucide="map-pin" class="w-3 h-3 text-rose-500 group-hover:scale-110 transition"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="py-2 px-3 whitespace-nowrap font-mono">
                                @if($archive->status === 'draft')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 dark:border-slate-700">Draft</span>
                                @elseif($archive->status === 'pending_verification')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30">Antrean Verifikasi</span>
                                @elseif($archive->status === 'approved_booked')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30">Approved / Booked</span>
                                @elseif($archive->status === 'in_warehouse')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">Tersimpan di Gudang</span>
                                @elseif($archive->status === 'borrowed')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30">Sedang Dipinjam</span>
                                @elseif($archive->status === 'destroyed')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30">Dimusnahkan</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right font-mono">
                                <a href="{{ route('archives.show', $archive) }}" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-bold transition inline-flex items-center gap-1">
                                    <i data-lucide="eye" class="w-3 h-3 text-blue-500"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-500 font-mono text-xs">Belum ada data arsip tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- PAGINATION & PER PAGE CONTROL RIBBON -->
        <div class="p-3 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-300 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 font-mono text-xs select-none">
            <!-- Left: Per-Page Selector & Info -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-bold">Tampilkan:</span>
                    <div class="inline-flex rounded-md shadow-xs" role="group">
                        @foreach([5, 10, 15, 20] as $opt)
                            <a 
                                href="{{ request()->fullUrlWithQuery(['per_page' => $opt, 'page' => 1]) }}"
                                class="px-2.5 py-1 text-xs font-bold transition border {{ (int)$perPage === $opt ? 'bg-amber-500 text-slate-950 border-amber-600 font-black shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700' }} {{ $loop->first ? 'rounded-l-md' : '' }} {{ $loop->last ? 'rounded-r-md' : '' }} {{ !$loop->first ? '-ml-px' : '' }}"
                            >
                                {{ $opt }}
                            </a>
                        @endforeach
                    </div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">baris</span>
                </div>

                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                    Menampilkan <strong>{{ $recentArchives->firstItem() ?? 0 }}</strong> - <strong>{{ $recentArchives->lastItem() ?? 0 }}</strong> dari <strong>{{ $recentArchives->total() }}</strong> total berkas
                </div>
            </div>

            <!-- Right: Pagination Links -->
            <div class="w-full sm:w-auto flex justify-center sm:justify-end">
                {{ $recentArchives->links('vendor.pagination.tailwind') }}
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2D QUICK SLOT ALLOCATION (RUANG -> RAK -> 100 SLOT GRID -> SAVE)    -->
    <!-- ========================================================================= -->
    <div x-show="quickSlotModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto"
         style="display: none; z-index: 9990 !important;"
         @keydown.escape.window="if(!confirmModalOpen) closeQuickSlotModal()">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl max-w-5xl w-full p-4 sm:p-6 shadow-2xl space-y-4 font-mono text-xs max-h-[92vh] flex flex-col my-auto"
             @click.away="if(!confirmModalOpen) closeQuickSlotModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-xl shrink-0">
                        <i data-lucide="layout-grid" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                Quick Layout 2D Alokasi Slot Rak
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40">
                                2D Grid Interaktif
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                            Pilih Ruang Gudang &rarr; Pilih Rak &rarr; Double Click Slot Kosong untuk Menyimpan.
                        </p>
                    </div>
                </div>

                <button type="button" @click="closeQuickSlotModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Target Archive Summary Ribbon & Navigation Breadcrumbs -->
            <div class="bg-slate-100 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shrink-0 space-y-2">
                <!-- Info Berkas Target -->
                <div class="flex flex-wrap items-center justify-between gap-2 text-[11px]">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-slate-500 font-bold uppercase shrink-0">Berkas Target:</span>
                        <span class="px-2 py-0.5 rounded bg-amber-500 text-slate-950 font-black shrink-0" x-text="targetArchive ? targetArchive.box_number : '-'"></span>
                        <span class="font-bold text-slate-800 dark:text-slate-100 truncate" x-text="targetArchive ? targetArchive.title : '-'"></span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 font-mono">
                        <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/30 font-bold" x-text="'Dept: ' + (targetArchive ? targetArchive.department_code : '-')"></span>
                    </div>
                </div>

                <!-- Breadcrumb Stepper Navigation -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800/80">
                    <div class="flex items-center gap-1.5 sm:gap-2 text-[11px] overflow-x-auto">
                        <!-- Step 1: Ruang -->
                        <button type="button" 
                                @click="goToStep('room')" 
                                class="px-2.5 py-1 rounded-lg font-bold flex items-center gap-1 transition"
                                :class="currentStep === 'room' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-700'">
                            <i data-lucide="warehouse" class="w-3.5 h-3.5"></i>
                            <span>1. Ruang Gudang</span>
                        </button>

                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>

                        <!-- Step 2: Rak -->
                        <button type="button" 
                                @click="if(selectedRoom) goToStep('rack')" 
                                :disabled="!selectedRoom"
                                class="px-2.5 py-1 rounded-lg font-bold flex items-center gap-1 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="currentStep === 'rack' ? 'bg-amber-500 text-slate-950 shadow-sm' : (selectedRoom ? 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-700' : 'text-slate-400')">
                            <i data-lucide="columns-3" class="w-3.5 h-3.5"></i>
                            <span x-text="selectedRoom ? ('2. ' + selectedRoom) : '2. Pilih Rak'"></span>
                        </button>

                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>

                        <!-- Step 3: Slot -->
                        <button type="button" 
                                :disabled="!selectedRack"
                                class="px-2.5 py-1 rounded-lg font-bold flex items-center gap-1 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="currentStep === 'slot' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-slate-400'">
                            <i data-lucide="grid" class="w-3.5 h-3.5"></i>
                            <span x-text="selectedRack ? ('3. Rak ' + selectedRack.rack_code + ' (100 Slot)') : '3. Denah Slot'"></span>
                        </button>
                    </div>

                    <!-- Tombol Kembali jika di step 2 atau 3 -->
                    <template x-if="currentStep !== 'room'">
                        <button type="button" 
                                @click="goBackStep()"
                                class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-[11px] font-bold transition flex items-center gap-1 shrink-0">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>Kembali</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Modal Body (Scrollable Container) -->
            <div class="flex-1 overflow-y-auto pr-1 space-y-4 min-h-[340px]">
                
                <!-- Loading State -->
                <div x-show="loadingLocations" class="py-16 text-center space-y-3 font-mono">
                    <div class="w-10 h-10 border-4 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-400">Memuat data denah 2D gudang & rak...</p>
                </div>

                <!-- STEP 1: PILIH RUANG GUDANG (2D ROOM CARDS) -->
                <div x-show="!loadingLocations && currentStep === 'room'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center gap-1.5">
                            <i data-lucide="map" class="w-4 h-4 text-amber-500"></i>
                            <span>Pilih Ruangan / Sektor Gudang 2D</span>
                        </span>
                        <span class="text-[11px] text-slate-500" x-text="'Total Ruang: ' + roomList.length"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <template x-for="room in roomList" :key="room.name">
                            <div class="p-4 rounded-xl border transition relative flex flex-col justify-between group cursor-pointer"
                                 :class="isRoomDisabled(room) 
                                    ? 'bg-slate-100 dark:bg-slate-900/50 border-slate-300 dark:border-slate-800 opacity-60 cursor-not-allowed' 
                                    : 'bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 hover:border-amber-500 hover:shadow-lg dark:hover:border-amber-500'"
                                 @click="if(!isRoomDisabled(room)) selectRoom(room.name)">
                                
                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="p-2 rounded-lg" :class="room.isFatLocked ? 'bg-purple-500/20 text-purple-600 dark:text-purple-400' : 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400'">
                                                <i data-lucide="warehouse" class="w-4 h-4"></i>
                                            </span>
                                            <div>
                                                <h4 class="font-extrabold text-sm text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition" x-text="room.name"></h4>
                                                <span class="text-[10px] text-slate-500" x-text="room.racks.length + ' Rak Fisik'"></span>
                                            </div>
                                        </div>

                                        <!-- Badge FAT / Umum -->
                                        <template x-if="room.isFatLocked">
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/40 whitespace-nowrap">
                                                🔒 FAT ONLY
                                            </span>
                                        </template>
                                        <template x-if="!room.isFatLocked">
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40 whitespace-nowrap">
                                                UMUM
                                            </span>
                                        </template>
                                    </div>

                                    <!-- Warning jika Dept bukan FAT -->
                                    <template x-if="isRoomDisabled(room)">
                                        <div class="p-2 rounded bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-[10px] font-bold flex items-center gap-1.5">
                                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                                            <span>Ruang ini dikunci khusus departemen FAT / Keuangan.</span>
                                        </div>
                                    </template>

                                    <!-- Kapasitas Ruang -->
                                    <div class="space-y-1 text-[10px]">
                                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                            <span>Total Terisi:</span>
                                            <span class="font-black text-slate-900 dark:text-white" x-text="room.usedBoxes + ' / ' + room.totalCapacity + ' Box (' + room.percent + '%)'"></span>
                                        </div>
                                        <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 transition-all duration-500"
                                                 :class="room.percent > 90 ? 'bg-rose-500' : (room.percent > 60 ? 'bg-amber-500' : 'bg-emerald-500')"
                                                 :style="'width: ' + room.percent + '%'"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Button Card -->
                                <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] font-bold" :class="room.availableSlots > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500'" x-text="room.availableSlots + ' Slot Tersedia'"></span>
                                    <button type="button" 
                                            :disabled="isRoomDisabled(room)"
                                            class="px-2.5 py-1 rounded bg-amber-500 group-hover:bg-amber-400 text-slate-950 font-black text-[10px] transition flex items-center gap-1 disabled:opacity-40">
                                        <span>Buka Ruang</span>
                                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- STEP 2: PILIH RAK GUDANG 2D (RACK CARDS) -->
                <div x-show="!loadingLocations && currentStep === 'rack'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center gap-1.5">
                            <i data-lucide="columns-3" class="w-4 h-4 text-amber-500"></i>
                            <span>Daftar Rak 2D di <strong class="text-amber-600 dark:text-amber-400" x-text="selectedRoom"></strong></span>
                        </span>
                        <span class="text-[11px] text-slate-500" x-text="'Total Rak: ' + (filteredRacks ? filteredRacks.length : 0)"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <template x-for="rack in filteredRacks" :key="rack.id">
                            <div class="p-3.5 rounded-xl border bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 hover:border-amber-500 hover:shadow-lg dark:hover:border-amber-500 transition relative flex flex-col justify-between group cursor-pointer"
                                 @click="selectRack(rack)">
                                
                                <div class="space-y-2">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="p-1.5 rounded-lg bg-blue-500/20 text-blue-600 dark:text-blue-400">
                                                <i data-lucide="archive" class="w-4 h-4"></i>
                                            </span>
                                            <div>
                                                <h4 class="font-black text-sm text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition" x-text="'Rak ' + rack.rack_code"></h4>
                                                <span class="text-[10px] text-slate-400 font-mono" x-text="(rack.total_sap || 5) + ' Sap × 20 Slot'"></span>
                                            </div>
                                        </div>

                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold"
                                              :class="getRackAvailableSlots(rack) > 0 ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30'"
                                              x-text="getRackAvailableSlots(rack) + ' Kosong'">
                                        </span>
                                    </div>

                                    <!-- Kapasitas Rak -->
                                    <div class="space-y-1 text-[10px]">
                                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                            <span>Terisi:</span>
                                            <span class="font-black text-slate-900 dark:text-white" x-text="(rack.current_box_count || 0) + ' / ' + (rack.box_capacity || 100) + ' Box'"></span>
                                        </div>
                                        <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 transition-all duration-500"
                                                 :class="((rack.current_box_count || 0) / (rack.box_capacity || 100) * 100) > 90 ? 'bg-rose-500' : (((rack.current_box_count || 0) / (rack.box_capacity || 100) * 100) > 60 ? 'bg-amber-500' : 'bg-emerald-500')"
                                                 :style="'width: ' + ((rack.current_box_count || 0) / (rack.box_capacity || 100) * 100) + '%'"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-2 mt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-400 font-mono">100 Slot Grid</span>
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-black flex items-center gap-1 group-hover:translate-x-0.5 transition">
                                        <span>Buka Denah</span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- STEP 3: DENAH 100 SLOT RAK 2D (5 SAP x 2 LAYER x 10 SLOTS) -->
                <div x-show="!loadingLocations && currentStep === 'slot' && selectedRack" class="space-y-3">
                    
                    <!-- Rack Detail Banner & Legend -->
                    <div class="bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl p-3 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-black text-slate-900 dark:text-white" x-text="'DENAH 2D RAK ' + (selectedRack ? selectedRack.rack_code : '')"></span>
                                <span class="text-[11px] text-slate-500 font-bold" x-text="'(' + selectedRoom + ')'"></span>
                            </div>
                            <p class="text-[10px] text-slate-500">
                                5 Tingkat Sap &times; 2 Layer (Atas & Bawah) &times; 10 Slot = 100 Slot Box Arsip
                            </p>
                        </div>

                        <!-- Legend & Status Indicators -->
                        <div class="flex items-center gap-2 flex-wrap text-[10px]">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40 font-bold">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Slot Kosong (Bisa Dipilih)</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/40 font-bold">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>Terisi Berkas</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/40 font-bold">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span>Expired / Rusak</span>
                            </span>
                        </div>
                    </div>

                    <!-- Guidance Tip Alert -->
                    <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200 text-[11px] font-bold flex items-center justify-between gap-2 shadow-xs">
                        <div class="flex items-center gap-2">
                            <i data-lucide="mouse-pointer-click" class="w-4 h-4 text-amber-500 shrink-0"></i>
                            <span>PETUNJUK: <strong class="underline decoration-amber-500 underline-offset-2">DOUBLE CLICK (Klik 2x Cepat)</strong> pada kotak slot hijau untuk menyimpan arsip ini ke slot tersebut!</span>
                        </div>
                        <template x-if="selectedSlot && selectedSlot.status === 'empty'">
                            <button type="button" 
                                    @click="onSlotDoubleClick(selectedSlot)" 
                                    class="px-3 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs transition shadow flex items-center gap-1 shrink-0 animate-bounce">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Simpan di <span x-text="selectedSlot.slot_code"></span></span>
                            </button>
                        </template>
                    </div>

                    <!-- 100 SLOTS GRID CONTAINER (Sap 5 down to Sap 1) -->
                    <div class="space-y-3 bg-slate-900 p-3 sm:p-4 rounded-2xl border-2 border-slate-700 shadow-inner">
                        <template x-for="sap in [5, 4, 3, 2, 1]" :key="'sap-' + sap">
                            <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-2.5 space-y-2">
                                <!-- Sap Level Header -->
                                <div class="flex items-center justify-between border-b border-slate-800/80 pb-1 text-[10px] font-bold text-slate-400">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-400 font-mono font-black" x-text="'SAP 0' + sap"></span>
                                        <span x-text="sap === 5 ? 'Tingkat 5 (Teratas)' : (sap === 1 ? 'Tingkat 1 (Terbawah)' : 'Tingkat ' + sap)"></span>
                                    </div>
                                    <span class="text-slate-500 font-mono">20 Slot Box (Layer Atas: 10, Layer Bawah: 10)</span>
                                </div>

                                <!-- Layer 1: Top Layer (Slot 11-20) -->
                                <div class="space-y-1">
                                    <div class="text-[9px] text-slate-500 font-mono uppercase tracking-wider flex items-center gap-1">
                                        <i data-lucide="arrow-up" class="w-2.5 h-2.5 text-blue-400"></i>
                                        <span>Baris Atas (Layer Top: 11 - 20)</span>
                                    </div>
                                    <div class="grid grid-cols-5 sm:grid-cols-10 gap-1.5">
                                        <template x-for="slot in getSlotsForSapAndLayer(sap, 'top')" :key="slot.id || (slot.slot_code)">
                                            <div @click="selectedSlot = slot"
                                                 @dblclick="onSlotDoubleClick(slot)"
                                                 class="p-1.5 rounded-lg border text-center transition select-none relative group"
                                                 :class="{
                                                     'bg-emerald-500/15 border-emerald-500/50 hover:bg-emerald-500 hover:text-slate-950 hover:shadow-lg hover:scale-105 cursor-pointer text-emerald-400': slot.status === 'empty' && selectedSlot?.id !== slot.id,
                                                     'bg-emerald-400 text-slate-950 ring-2 ring-amber-400 font-black scale-105 cursor-pointer': slot.status === 'empty' && selectedSlot?.id === slot.id,
                                                     'bg-amber-500/20 border-amber-500/40 text-amber-300 cursor-not-allowed opacity-90': slot.status === 'filled',
                                                     'bg-rose-500/20 border-rose-500/40 text-rose-300 cursor-not-allowed': slot.status === 'expired',
                                                     'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed': slot.status === 'inactive'
                                                 }"
                                                 :title="slot.status === 'empty' ? ('Slot ' + slot.slot_code + ' (Kosong) - Double click untuk simpan!') : ('Terisi: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Terisi'))">
                                                <div class="font-mono font-black text-[11px] truncate" x-text="slot.slot_code"></div>
                                                <div class="text-[9px] truncate font-sans">
                                                    <template x-if="slot.status === 'empty'">
                                                        <span class="text-emerald-500 group-hover:text-slate-950 font-bold">KOSONG</span>
                                                    </template>
                                                    <template x-if="slot.status === 'filled'">
                                                        <span class="text-amber-400 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="slot.status === 'expired'">
                                                        <span class="text-rose-400 font-bold">EXPIRED</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Layer 2: Bottom Layer (Slot 01-10) -->
                                <div class="space-y-1 pt-1">
                                    <div class="text-[9px] text-slate-500 font-mono uppercase tracking-wider flex items-center gap-1">
                                        <i data-lucide="arrow-down" class="w-2.5 h-2.5 text-amber-400"></i>
                                        <span>Baris Bawah (Layer Bottom: 01 - 10)</span>
                                    </div>
                                    <div class="grid grid-cols-5 sm:grid-cols-10 gap-1.5">
                                        <template x-for="slot in getSlotsForSapAndLayer(sap, 'bottom')" :key="slot.id || (slot.slot_code)">
                                            <div @click="selectedSlot = slot"
                                                 @dblclick="onSlotDoubleClick(slot)"
                                                 class="p-1.5 rounded-lg border text-center transition select-none relative group"
                                                 :class="{
                                                     'bg-emerald-500/15 border-emerald-500/50 hover:bg-emerald-500 hover:text-slate-950 hover:shadow-lg hover:scale-105 cursor-pointer text-emerald-400': slot.status === 'empty' && selectedSlot?.id !== slot.id,
                                                     'bg-emerald-400 text-slate-950 ring-2 ring-amber-400 font-black scale-105 cursor-pointer': slot.status === 'empty' && selectedSlot?.id === slot.id,
                                                     'bg-amber-500/20 border-amber-500/40 text-amber-300 cursor-not-allowed opacity-90': slot.status === 'filled',
                                                     'bg-rose-500/20 border-rose-500/40 text-rose-300 cursor-not-allowed': slot.status === 'expired',
                                                     'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed': slot.status === 'inactive'
                                                 }"
                                                 :title="slot.status === 'empty' ? ('Slot ' + slot.slot_code + ' (Kosong) - Double click untuk simpan!') : ('Terisi: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Terisi'))">
                                                <div class="font-mono font-black text-[11px] truncate" x-text="slot.slot_code"></div>
                                                <div class="text-[9px] truncate font-sans">
                                                    <template x-if="slot.status === 'empty'">
                                                        <span class="text-emerald-500 group-hover:text-slate-950 font-bold">KOSONG</span>
                                                    </template>
                                                    <template x-if="slot.status === 'filled'">
                                                        <span class="text-amber-400 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="slot.status === 'expired'">
                                                        <span class="text-rose-400 font-bold">EXPIRED</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Modal Footer Controls -->
            <div class="border-t border-slate-200 dark:border-slate-800 pt-3 flex items-center justify-between gap-2 shrink-0">
                <div class="text-[11px] text-slate-500 font-mono">
                    <template x-if="selectedSlot && selectedSlot.status === 'empty'">
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="'Slot Terpilih: ' + selectedSlot.slot_code + ' (' + (selectedSlot.layer === 'top' ? 'Baris Atas' : 'Baris Bawah') + ', Sap ' + selectedSlot.sap_level + ')'"></span>
                    </template>
                    <template x-if="!selectedSlot || selectedSlot.status !== 'empty'">
                        <span>Tekan <strong class="text-amber-500">Esc</strong> atau klik Batal untuk menutup.</span>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="closeQuickSlotModal()" 
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                        Batal
                    </button>
                    
                    <template x-if="currentStep === 'slot' && selectedSlot && selectedSlot.status === 'empty'">
                        <button type="button" 
                                @click="onSlotDoubleClick(selectedSlot)" 
                                class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-black text-xs shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            <span>Simpan di Slot Ini</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- POP UP KONFIRMASI PENYIMPANAN SLOT (TRIGGERED ON DBLCLICK) -->
    <div x-show="confirmModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md"
         style="display: none; z-index: 99999 !important;"
         @keydown.escape.window="if(confirmModalOpen) confirmModalOpen = false">
        
        <div class="bg-white dark:bg-slate-900 border-2 border-emerald-500 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 font-mono text-xs animate-in fade-in zoom-in-95 duration-200 relative"
             style="z-index: 100000 !important;"
             @click.away="if(!savingSlot) confirmModalOpen = false">
            
            <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="p-3 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl shrink-0">
                    <i data-lucide="help-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                        Konfirmasi Penempatan Slot
                    </h3>
                    <p class="text-[11px] text-slate-500">Simpan berkas arsip ke slot gudang fisik.</p>
                </div>
            </div>

            <!-- Detail Konfirmasi Box & Slot -->
            <div class="p-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2.5">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">BERKAS ARSIP:</span>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-amber-500 text-slate-950 font-black text-xs" x-text="targetArchive ? targetArchive.box_number : '-'"></span>
                        <span class="font-bold text-slate-900 dark:text-white text-xs truncate" x-text="targetArchive ? targetArchive.title : '-'"></span>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">LOKASI SLOT YANG DIPILIH:</span>
                    <div class="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 font-mono space-y-1">
                        <div class="text-base font-black flex items-center gap-1.5">
                            <i data-lucide="map-pin" class="w-4 h-4 text-emerald-500"></i>
                            <span x-text="selectedRoom + ' - ' + (slotToSave ? slotToSave.slot_code : '')"></span>
                        </div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-400 font-sans">
                            <span x-text="'Rak: ' + (selectedRack ? selectedRack.rack_code : '')"></span> &bull; 
                            <span x-text="'Sap ' + (slotToSave ? slotToSave.sap_level : '')"></span> &bull; 
                            <span x-text="slotToSave ? (slotToSave.layer === 'top' ? 'Baris Atas (Top)' : 'Baris Bawah (Bottom)') : ''"></span>
                        </div>
                    </div>
                </div>

                <div class="p-2.5 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-700 dark:text-blue-300 text-[11px] font-sans">
                    Apakah Anda yakin akan menyimpan berkas arsip ini pada slot <strong><span x-text="slotToSave ? slotToSave.slot_code : ''"></span></strong>?
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" 
                        @click="confirmModalOpen = false" 
                        :disabled="savingSlot"
                        class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition disabled:opacity-50">
                    Batal
                </button>
                
                <button type="button" 
                        @click="saveSlotAllocation()" 
                        :disabled="savingSlot"
                        class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-black text-xs shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                    <template x-if="savingSlot">
                        <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    </template>
                    <template x-if="!savingSlot">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </template>
                    <span x-text="savingSlot ? 'Menyimpan...' : 'Ya, Simpan di Slot Ini'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboardOverviewApp() {
    return {
        warehouses: @json($warehouses),
        warehouseLocations: @json($warehouseLocations),
        stats: {
            total: {{ (int) $totalArchives }},
            inWarehouse: {{ (int) $inWarehouseCount }},
            pendingVerification: {{ (int) $pendingVerificationCount }},
            borrowed: {{ (int) $borrowedCount }},
            expiring: {{ (int) $expiringCount }},
            usedCapacity: {{ (int) $usedCapacity }},
            totalCapacity: {{ (int) $totalCapacity }},
        },
        
        // 2D Quick Slot Allocation State
        quickSlotModalOpen: false,
        confirmModalOpen: false,
        loadingLocations: false,
        savingSlot: false,
        currentStep: 'room', // 'room', 'rack', 'slot'
        targetArchive: null,
        locationsData: [],
        roomList: [],
        selectedRoom: null,
        selectedRack: null,
        selectedSlot: null,
        slotToSave: null,

        init() {
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async openQuickSlotModal(archive) {
            this.targetArchive = archive;
            this.currentStep = 'room';
            this.selectedRoom = null;
            this.selectedRack = null;
            this.selectedSlot = null;
            this.slotToSave = null;
            this.quickSlotModalOpen = true;

            if (this.locationsData.length === 0) {
                await this.loadLocationsData();
            } else {
                this.processRoomList();
            }

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closeQuickSlotModal() {
            this.quickSlotModalOpen = false;
            this.confirmModalOpen = false;
            this.selectedSlot = null;
            this.slotToSave = null;
        },

        async loadLocationsData() {
            this.loadingLocations = true;
            try {
                const response = await fetch('/api/warehouse/layout-data', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                this.locationsData = data.locations || [];
                this.processRoomList();
            } catch (error) {
                console.error('Failed to load layout data:', error);
                alert('Gagal memuat data layout 2D gudang.');
            } finally {
                this.loadingLocations = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        processRoomList() {
            const roomsMap = {};
            
            this.locationsData.forEach(loc => {
                const roomName = (loc.room_sector && loc.room_sector.trim()) ? loc.room_sector.trim() : 'Umum';
                const formattedRoom = roomName.toLowerCase().startsWith('gudang') ? roomName : ('Gudang ' + roomName);

                if (!roomsMap[formattedRoom]) {
                    roomsMap[formattedRoom] = {
                        name: formattedRoom,
                        rawName: roomName,
                        isFatLocked: Boolean(loc.is_fat_locked || roomName.toUpperCase().includes('R1') || roomName.toUpperCase().includes('R2')),
                        racks: [],
                        totalCapacity: 0,
                        usedBoxes: 0,
                        availableSlots: 0,
                        percent: 0
                    };
                }

                roomsMap[formattedRoom].racks.push(loc);
                const cap = loc.box_capacity || 100;
                const used = loc.current_box_count || (loc.slots ? loc.slots.filter(s => s.status === 'filled').length : 0);
                roomsMap[formattedRoom].totalCapacity += cap;
                roomsMap[formattedRoom].usedBoxes += used;
            });

            this.roomList = Object.values(roomsMap).map(r => {
                r.availableSlots = Math.max(0, r.totalCapacity - r.usedBoxes);
                r.percent = r.totalCapacity > 0 ? Math.round((r.usedBoxes / r.totalCapacity) * 100) : 0;
                return r;
            });
        },

        isRoomDisabled(room) {
            if (!this.targetArchive) return false;
            if (room.isFatLocked) {
                const code = (this.targetArchive.department_code || '').toUpperCase();
                const name = (this.targetArchive.department_name || '').toUpperCase();
                const isFatDept = code.includes('FAT') || code.includes('FIN') || name.includes('FAT') || name.includes('FIN');
                return !isFatDept;
            }
            return false;
        },

        selectRoom(roomName) {
            this.selectedRoom = roomName;
            this.currentStep = 'rack';
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        get filteredRacks() {
            if (!this.selectedRoom) return [];
            return this.locationsData.filter(loc => {
                const roomName = (loc.room_sector && loc.room_sector.trim()) ? loc.room_sector.trim() : 'Umum';
                const formattedRoom = roomName.toLowerCase().startsWith('gudang') ? roomName : ('Gudang ' + roomName);
                return formattedRoom === this.selectedRoom;
            });
        },

        getRackAvailableSlots(rack) {
            const cap = rack.box_capacity || 100;
            const used = rack.current_box_count || (rack.slots ? rack.slots.filter(s => s.status === 'filled').length : 0);
            return Math.max(0, cap - used);
        },

        selectRack(rack) {
            this.selectedRack = rack;
            this.currentStep = 'slot';
            this.selectedSlot = null;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        getSlotsForSapAndLayer(sapLevel, layer) {
            if (!this.selectedRack || !this.selectedRack.slots) return [];
            return this.selectedRack.slots.filter(slot => {
                return parseInt(slot.sap_level) === parseInt(sapLevel) && String(slot.layer).toLowerCase() === String(layer).toLowerCase();
            }).sort((a, b) => parseInt(a.slot_number) - parseInt(b.slot_number));
        },

        goToStep(step) {
            if (step === 'room') {
                this.currentStep = 'room';
                this.selectedRoom = null;
                this.selectedRack = null;
                this.selectedSlot = null;
            } else if (step === 'rack' && this.selectedRoom) {
                this.currentStep = 'rack';
                this.selectedRack = null;
                this.selectedSlot = null;
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        goBackStep() {
            if (this.currentStep === 'slot') {
                this.currentStep = 'rack';
                this.selectedRack = null;
                this.selectedSlot = null;
            } else if (this.currentStep === 'rack') {
                this.currentStep = 'room';
                this.selectedRoom = null;
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        onSlotDoubleClick(slot) {
            if (!slot || slot.status !== 'empty') {
                return;
            }
            this.slotToSave = slot;
            this.confirmModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async saveSlotAllocation() {
            if (!this.slotToSave || !this.selectedRack || !this.targetArchive) return;
            
            this.savingSlot = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                                || '{{ csrf_token() }}';

                const response = await fetch(`/api/warehouse/locations/${this.selectedRack.id}/slots/assign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        mode: 'existing_archive',
                        archive_id: parseInt(this.targetArchive.id),
                        sap_level: parseInt(this.slotToSave.sap_level),
                        layer: String(this.slotToSave.layer).toLowerCase(),
                        slot_number: parseInt(this.slotToSave.slot_number),
                        slot_code: this.slotToSave.slot_code
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.confirmModalOpen = false;
                    this.quickSlotModalOpen = false;
                    
                    if (window.showToast) {
                        window.showToast(result.message || 'Lokasi slot arsip berhasil disimpan!', 'success');
                    } else {
                        alert(result.message || 'Lokasi slot arsip berhasil disimpan!');
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 400);
                } else {
                    let errMsg = result.message || 'Gagal menyimpan penempatan slot.';
                    if (result.errors) {
                        const firstKey = Object.keys(result.errors)[0];
                        if (firstKey && result.errors[firstKey][0]) {
                            errMsg = result.errors[firstKey][0];
                        }
                    }
                    alert(errMsg);
                }
            } catch (error) {
                console.error('Error saving slot assignment:', error);
                alert('Terjadi kesalahan saat menyimpan slot ke server.');
            } finally {
                this.savingSlot = false;
            }
        }
    }
}
</script>
@endpush
@endsection

