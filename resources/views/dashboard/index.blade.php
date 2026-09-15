@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Dashboard Overview - DMS PT Indraco')

@section('content')
<div class="space-y-[10px]">
    <!-- DELPHI WORKSTATION STATUS HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] shadow-xs flex items-center justify-between gap-[8px] font-mono">
        <div class="flex items-center gap-[8px]">
            <span class="p-[4px] bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded-[3px]">
                <i data-lucide="layout-dashboard" class="w-[15px] h-[15px]"></i>
            </span>
            <div>
                <h1 class="text-[12px] font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Workstation Dashboard: <span class="text-amber-600 dark:text-amber-400 font-extrabold">{{ $user->name }}</span>
                </h1>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">
                    @if($user->isSuperAdmin())
                        Super Admin Access (Master Data & Dokumen Seluruh Entitas PT Indraco)
                    @elseif($user->isPicGudang())
                        Kurator Gudang (Verifikasi Box Code, Slot Rak Gudang, & Dispatch)
                    @else
                        Departemen {{ $user->department->name ?? 'Operasional' }} ({{ $user->department->code ?? 'DEPT' }})
                    @endif
                </p>
            </div>
        </div>

        @if(!auth()->user()->isPicDept())
        <div class="flex items-center gap-[6px]">
            <a href="{{ route('archives.create') }}" class="px-[10px] py-[3px] bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-black text-[11px] rounded-[3px] border border-amber-600 shadow-2xs transition flex items-center gap-[4px]">
                <i data-lucide="plus-circle" class="w-[12px] h-[12px]"></i>
                <span>Draft Arsip</span>
            </a>
        </div>
        @endif
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

        <fieldset class="border border-slate-300 dark:border-slate-800 p-[10px] rounded-[4px] bg-white dark:bg-slate-950 shadow-xs space-y-[6px]">
            <legend class="px-[6px] font-mono text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-[2px] shadow-2xs flex items-center gap-[4px]">
                <i data-lucide="zap" class="w-[12px] h-[12px]"></i>
                PENCARIAN CEPAT DOKUMEN & KATALOG ARSIP
            </legend>

            <form action="{{ route('archives.index') }}" method="GET" class="relative">
                <div class="relative flex items-center">
                    <i data-lucide="search" class="w-[13px] h-[13px] absolute left-[8px] text-slate-400"></i>
                    <input 
                        type="text" 
                        name="search" 
                        x-model="searchQuery" 
                        @input="fetchResults()"
                        @focus="fetchResults(); showDropdown = true"
                        placeholder="Ketik kata kunci dokumen (contoh: BOX-FIN-2024, Pajak, HRD)..." 
                        class="w-full pl-[28px] pr-[80px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 font-semibold transition"
                    >
                    <div class="absolute right-[4px] flex items-center gap-[4px]">
                        <template x-if="searchQuery">
                            <button type="button" @click="searchQuery = ''; fetchResults()" class="p-[2px] text-slate-400 hover:text-slate-600 dark:hover:text-white" title="Clear">
                                <i data-lucide="x" class="w-[12px] h-[12px]"></i>
                            </button>
                        </template>
                        <button type="submit" class="px-[8px] py-[2px] bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-bold text-[10px] rounded-[2px] border border-amber-600 transition flex items-center gap-[3px]">
                            <i data-lucide="search" class="w-[10px] h-[10px]"></i>
                            <span>Cari</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Quick Filter Badges -->
            <div class="flex flex-wrap items-center gap-[6px] text-[10px] pt-[2px]">
                <span class="text-slate-500 dark:text-slate-400 font-bold uppercase">Shortcut Filter:</span>
                <a href="{{ route('archives.index', ['status' => 'in_warehouse']) }}" class="px-[6px] py-[1px] rounded-[2px] bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/20 border border-emerald-500/30 font-bold flex items-center gap-[4px]">
                    <span class="w-[5px] h-[5px] rounded-full bg-emerald-400"></span> Di Gudang
                </a>
                <a href="{{ route('archives.index', ['status' => 'pending_verification']) }}" class="px-[6px] py-[1px] rounded-[2px] bg-amber-500/10 text-amber-700 dark:text-amber-300 hover:bg-amber-500/20 border border-amber-500/30 font-bold flex items-center gap-[4px]">
                    <span class="w-[5px] h-[5px] rounded-full bg-amber-400"></span> Antrean Verifikasi
                </a>
                <a href="{{ route('archives.index', ['status' => 'borrowed']) }}" class="px-[6px] py-[1px] rounded-[2px] bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20 border border-purple-500/30 font-bold flex items-center gap-[4px]">
                    <span class="w-[5px] h-[5px] rounded-full bg-purple-400"></span> Sedang Dipinjam
                </a>
                <a href="{{ route('archives.index', ['expiry_filter' => 'expiring_soon']) }}" class="px-[6px] py-[1px] rounded-[2px] bg-rose-500/10 text-rose-700 dark:text-rose-300 hover:bg-rose-500/20 border border-rose-500/30 font-bold flex items-center gap-[4px]">
                    <span class="w-[5px] h-[5px] rounded-full bg-rose-400"></span> Expiring Soon
                </a>
            </div>
        </fieldset>

        <!-- Live Results Dropdown -->
        <div x-show="showDropdown" x-cloak x-transition.opacity.duration.150ms class="absolute left-0 right-0 mt-[2px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[3px] shadow-xl overflow-hidden z-50 text-slate-900 dark:text-slate-100 font-mono text-[11px]">
            <!-- Loading State -->
            <div x-show="loading" class="p-[10px] text-center font-bold text-slate-500 flex items-center justify-center gap-[6px]">
                <i data-lucide="loader-2" class="w-[13px] h-[13px] animate-spin text-amber-500"></i> Memuat saran pencarian...
            </div>

            <!-- Mode 1: Initial Suggestions -->
            <div x-show="!loading && isSuggestion" class="divide-y divide-slate-200 dark:divide-slate-800">
                <div class="p-[8px] bg-slate-50 dark:bg-slate-900/60">
                    <div class="flex items-center gap-[4px] text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase mb-[4px]">
                        <i data-lucide="sparkles" class="w-[11px] h-[11px]"></i>
                        Saran Kata Kunci Populer
                    </div>
                    <div class="flex flex-wrap gap-[4px]">
                        <template x-for="kw in keywords" :key="kw">
                            <button type="button" @click="selectKeyword(kw)" class="px-[6px] py-[2px] rounded-[2px] bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:border-amber-500 text-[10px] font-bold text-slate-800 dark:text-slate-200 hover:text-amber-500 transition shadow-2xs flex items-center gap-[3px]">
                                <i data-lucide="search" class="w-[9px] h-[9px] text-slate-400"></i>
                                <span x-text="kw"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="max-h-[180px] overflow-y-auto">
                    <div class="px-[8px] py-[3px] bg-slate-100 dark:bg-slate-900 text-[9px] font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                        <span>Rekomendasi Dokumen Terbaru</span>
                        <span>Akses Cepat</span>
                    </div>
                    <template x-for="item in recentDocs" :key="item.id">
                        <a :href="item.url" class="p-[6px] flex items-center justify-between gap-[6px] hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition border-b border-slate-100 dark:border-slate-900">
                            <div class="space-y-[1px] min-w-0">
                                <div class="flex items-center gap-[4px]">
                                    <span class="px-[4px] py-[1px] rounded-[2px] text-[9px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="item.dept_code"></span>
                                    <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400" x-text="item.box_number"></span>
                                </div>
                                <h4 class="text-[11px] font-bold text-slate-900 dark:text-white truncate" x-text="item.title"></h4>
                            </div>
                            <span class="px-[6px] py-[1px] rounded-[2px] text-[9px] font-bold shrink-0" 
                                  :class="{
                                      'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400': item.status === 'draft',
                                      'bg-amber-500/20 text-amber-700 dark:text-amber-300': item.status === 'pending_verification',
                                      'bg-blue-500/20 text-blue-700 dark:text-blue-300': item.status === 'approved_booked',
                                      'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300': item.status === 'in_warehouse',
                                      'bg-purple-500/20 text-purple-700 dark:text-purple-300': item.status === 'borrowed'
                                  }" 
                                  x-text="item.status_label">
                            </span>
                        </a>
                    </template>
                </div>
            </div>

            <!-- Mode 2: Live Query Search Results -->
            <div x-show="!loading && !isSuggestion">
                <div x-show="results.length === 0" class="p-[10px] text-center font-bold text-slate-500">
                    Tidak ada dokumen ditemukan untuk kata kunci ini.
                </div>
                <div x-show="results.length > 0" class="divide-y divide-slate-200 dark:divide-slate-800 max-h-[180px] overflow-y-auto">
                    <template x-for="item in results" :key="item.id">
                        <a :href="item.url" class="p-[6px] flex items-center justify-between gap-[6px] hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition border-b border-slate-100 dark:border-slate-900">
                            <div class="space-y-[1px] min-w-0">
                                <div class="flex items-center gap-[4px]">
                                    <span class="px-[4px] py-[1px] rounded-[2px] text-[9px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="item.dept_code"></span>
                                    <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400" x-text="item.box_number"></span>
                                </div>
                                <h4 class="text-[11px] font-bold text-slate-900 dark:text-white truncate" x-text="item.title"></h4>
                            </div>
                            <span class="px-[6px] py-[1px] rounded-[2px] text-[9px] font-bold shrink-0" x-text="item.status_label"></span>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- STAT CARDS GRID (DELPHI TSTATISTICS PANELS) -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-[8px] font-mono">
        <!-- Total Active Archives -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[8px] shadow-xs hover:border-blue-500 transition">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase">
                <span>TOTAL ARSIP</span>
                <i data-lucide="archive" class="w-[13px] h-[13px] text-blue-500"></i>
            </div>
            <div class="mt-[4px] flex items-baseline gap-[4px]">
                <span class="text-[18px] font-black text-slate-900 dark:text-white">{{ $totalArchives }}</span>
                <span class="text-[10px] text-slate-500">Box</span>
            </div>
        </div>

        <!-- In Warehouse -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[8px] shadow-xs hover:border-emerald-500 transition">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase">
                <span>TERSIMPAN GUDANG</span>
                <i data-lucide="warehouse" class="w-[13px] h-[13px] text-emerald-500"></i>
            </div>
            <div class="mt-[4px] flex items-baseline gap-[4px]">
                <span class="text-[18px] font-black text-emerald-600 dark:text-emerald-400">{{ $inWarehouseCount }}</span>
                <span class="text-[10px] text-slate-500">Slot</span>
            </div>
        </div>

        <!-- Pending Verification Queue -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[8px] shadow-xs hover:border-amber-500 transition">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase">
                <span>ANTREAN BOOKING</span>
                <i data-lucide="clock" class="w-[13px] h-[13px] text-amber-500"></i>
            </div>
            <div class="mt-[4px] flex items-baseline gap-[4px]">
                <span class="text-[18px] font-black text-amber-600 dark:text-amber-400">{{ $pendingVerificationCount }}</span>
                <span class="text-[10px] text-slate-500">Berkas</span>
            </div>
        </div>

        <!-- Borrowed -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[8px] shadow-xs hover:border-purple-500 transition">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase">
                <span>SEDANG DIPINJAM</span>
                <i data-lucide="file-symlink" class="w-[13px] h-[13px] text-purple-500"></i>
            </div>
            <div class="mt-[4px] flex items-baseline gap-[4px]">
                <span class="text-[18px] font-black text-purple-600 dark:text-purple-400">{{ $borrowedCount }}</span>
                <span class="text-[10px] text-slate-500">Out</span>
            </div>
        </div>

        <!-- Retention Expiry Alert -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[8px] shadow-xs hover:border-rose-500 transition col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase">
                <span>ALERT RETENSI</span>
                <i data-lucide="alert-circle" class="w-[13px] h-[13px] text-rose-500"></i>
            </div>
            <div class="mt-[4px] flex items-baseline gap-[4px]">
                <span class="text-[18px] font-black text-rose-600 dark:text-rose-400">{{ $expiringCount }}</span>
                <span class="text-[10px] text-slate-500">Jatuh Tempo</span>
            </div>
        </div>
    </div>

    <!-- MIDDLE GRID: WAREHOUSE METER & EXPIRY ALERTS -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-[8px] font-mono">
        <!-- Warehouse Capacity Meter Box (4 Columns) -->
        <fieldset class="md:col-span-4 border border-slate-300 dark:border-slate-800 p-[10px] rounded-[4px] bg-white dark:bg-slate-950 shadow-xs flex flex-col justify-between">
            <legend class="px-[6px] font-mono text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-[2px] shadow-2xs flex items-center gap-[4px]">
                <i data-lucide="boxes" class="w-[12px] h-[12px]"></i>
                KAPASITAS GUDANG ARSIP
            </legend>

            <div class="space-y-[8px]">
                <div class="flex items-end justify-between">
                    <div>
                        <span class="text-[10px] text-slate-500 font-bold block uppercase">Box Terisi / Total Kapasitas</span>
                        <span class="text-[16px] font-extrabold text-slate-900 dark:text-white">{{ $usedCapacity }} / {{ $totalCapacity }}</span>
                        <span class="text-[10px] text-slate-500"> Box</span>
                    </div>
                    <span class="text-[16px] font-black text-amber-600 dark:text-amber-400">{{ $capacityPercent }}%</span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full h-[6px] bg-slate-200 dark:bg-slate-900 rounded-full overflow-hidden border border-slate-300 dark:border-slate-700">
                    <div class="h-full bg-gradient-to-r from-emerald-500 via-amber-400 to-rose-500 transition-all duration-300" style="width: {{ min($capacityPercent, 100) }}%"></div>
                </div>

                <!-- Available Locations Breakdown -->
                <div class="space-y-[4px] pt-[2px]">
                    <span class="text-[9px] font-bold text-slate-500 uppercase block">Lokasi Rak Terisi:</span>
                    @foreach($warehouseLocations->take(3) as $loc)
                    <div class="flex items-center justify-between p-[4px] rounded-[2px] bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[10px]">
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $loc->full_location }}</span>
                        <span class="text-slate-500 font-bold">{{ $loc->current_box_count }}/{{ $loc->box_capacity }} Box</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-[8px] pt-[4px] border-t border-slate-200 dark:border-slate-800 text-right">
                <a href="{{ route('master.warehouses') }}" class="text-[10px] font-bold text-amber-600 dark:text-amber-400 hover:underline inline-flex items-center gap-[3px]">
                    Kelola Lokasi Gudang <i data-lucide="chevron-right" class="w-[11px] h-[11px]"></i>
                </a>
            </div>
        </fieldset>

        <!-- Retention Expiry Alert Box (8 Columns) -->
        <fieldset class="md:col-span-8 border border-slate-300 dark:border-slate-800 p-[10px] rounded-[4px] bg-white dark:bg-slate-950 shadow-xs flex flex-col justify-between">
            <legend class="px-[6px] font-mono text-[10px] font-bold text-rose-600 dark:text-rose-400 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-[2px] shadow-2xs flex items-center gap-[4px]">
                <i data-lucide="hourglass" class="w-[12px] h-[12px]"></i>
                PEMBERITAHUAN RETENTION EXPIRY (MASA SIMPAN)
            </legend>

            @if($expiringArchives->isEmpty())
            <div class="text-center py-[20px] text-slate-500 font-mono text-[11px] space-y-[4px]">
                <i data-lucide="shield-check" class="w-[24px] h-[24px] mx-auto text-emerald-500"></i>
                <p class="font-bold">Tidak ada berkas yang mendekati masa pemusnahan (90 Hari).</p>
            </div>
            @else
            <div class="space-y-[4px]">
                @foreach($expiringArchives->take(3) as $exp)
                <div class="p-[6px] rounded-[3px] bg-rose-500/10 border border-rose-500/20 flex items-center justify-between gap-[6px] text-[11px] font-mono">
                    <div>
                        <div class="flex items-center gap-[4px]">
                            <span class="px-[4px] py-[1px] rounded-[2px] text-[9px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-300">{{ $exp->department->code ?? 'GEN' }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $exp->title }}</span>
                        </div>
                        <p class="text-[10px] text-slate-500">Box: <strong class="text-amber-600 dark:text-amber-400">{{ $exp->box_number ?? '-' }}</strong> | Expiry: <strong class="text-rose-600 dark:text-rose-400">{{ \Carbon\Carbon::parse($exp->retention_expiry_date)->format('d M Y') }}</strong></p>
                    </div>

                    <a href="{{ route('archives.show', $exp) }}" class="px-[8px] py-[2px] rounded-[2px] bg-rose-600 hover:bg-rose-500 text-white text-[10px] font-bold transition whitespace-nowrap">
                        Proses Pemusnahan
                    </a>
                </div>
                @endforeach
            </div>
            @endif

            <div class="mt-[8px] pt-[4px] border-t border-slate-200 dark:border-slate-800 text-[10px] text-slate-500 flex items-center justify-between">
                <span>Pemusnahan berkas wajib menyertakan Berita Acara Pemusnahan (BAP).</span>
                <a href="{{ route('destructions.index') }}" class="font-bold text-rose-600 dark:text-rose-400 hover:underline">Lihat Semua Expiry &rarr;</a>
            </div>
        </fieldset>
    </div>

    <!-- BOTTOM DELPHI TDBGRID: RECENT ARCHIVES -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] shadow-xs overflow-hidden font-sans">
        <div class="px-[10px] py-[6px] bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between font-mono text-[11px]">
            <div class="flex items-center gap-[6px] font-bold text-slate-900 dark:text-white">
                <i data-lucide="folder-git-2" class="w-[13px] h-[13px] text-blue-500"></i>
                <span>BERKAS ARSIP TERBARU (TDBGrid View)</span>
            </div>
            <a href="{{ route('archives.index') }}" class="px-[8px] py-[2px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded-[2px] font-bold transition">
                Buka Katalog Utama
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900 text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b border-slate-300 dark:border-slate-700">
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">NO. BOX ARSIP</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">JUDUL BERKAS</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">DEPARTEMEN</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">PERIODE</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">LOKASI FISIK</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">STATUS</th>
                        <th class="py-[6px] px-[10px] text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-[11px] font-mono">
                    @forelse($recentArchives as $archive)
                    <tr class="hover:bg-amber-500/5 dark:hover:bg-amber-500/10 transition">
                        <td class="py-[6px] px-[10px] text-amber-700 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->box_number ?? 'Penomoran Pending' }}
                        </td>
                        <td class="py-[6px] px-[10px] font-bold text-slate-900 dark:text-white border-r border-slate-200 dark:border-slate-800">
                            {{ Str::limit($archive->title, 45) }}
                        </td>
                        <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <span class="px-[6px] py-[1px] rounded-[2px] text-[10px] font-bold bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $archive->department->code ?? 'GEN' }}
                            </span>
                        </td>
                        <td class="py-[6px] px-[10px] text-slate-600 dark:text-slate-400 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->period_text ?? $archive->period_start_date->format('M Y') }}
                        </td>
                        <td class="py-[6px] px-[10px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->location->full_location ?? 'Belum Ditentukan' }}
                        </td>
                        <td class="py-[6px] px-[10px] whitespace-nowrap border-r border-slate-200 dark:border-slate-800">
                            @if($archive->status === 'draft')
                                <span class="px-[6px] py-[1px] rounded-[2px] text-[9px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 dark:border-slate-700">Draft</span>
                            @elseif($archive->status === 'pending_verification')
                                <span class="px-[6px] py-[1px] rounded-[2px] text-[9px] font-bold bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/30">Antrean</span>
                            @elseif($archive->status === 'approved_booked')
                                <span class="px-[6px] py-[1px] rounded-[2px] text-[9px] font-bold bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/30">Approved</span>
                            @elseif($archive->status === 'in_warehouse')
                                <span class="px-[6px] py-[1px] rounded-[2px] text-[9px] font-bold bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/30">Gudang</span>
                            @elseif($archive->status === 'borrowed')
                                <span class="px-[6px] py-[1px] rounded-[2px] text-[9px] font-bold bg-purple-500/10 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/30">Dipinjam</span>
                            @elseif($archive->status === 'destroyed')
                                <span class="px-[6px] py-[1px] rounded-[2px] text-[9px] font-bold bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30">Dimusnahkan</span>
                            @endif
                        </td>
                        <td class="py-[6px] px-[10px] text-right">
                            <a href="{{ route('archives.show', $archive) }}" class="px-[6px] py-[2px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded-[2px] text-[10px] font-bold transition inline-flex items-center gap-[3px]">
                                <i data-lucide="eye" class="w-[10px] h-[10px] text-blue-500"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-[20px] text-center text-slate-500 text-[11px]">Belum ada data arsip tercatat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
