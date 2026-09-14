@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Dashboard Overview - DMS PT Indraco')

@section('content')
<div class="space-y-3">
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

            <!-- Quick Filter Badges -->
            <div class="flex flex-wrap items-center gap-1.5 text-[11px] pt-1">
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
                        Saran Kata Kunci Popular
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="kw in keywords" :key="kw">
                            <button type="button" @click="selectKeyword(kw)" class="px-2.5 py-1 rounded bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:border-amber-500 text-xs font-bold text-slate-800 dark:text-slate-200 hover:text-amber-500 transition shadow-xs flex items-center gap-1">
                                <i data-lucide="search" class="w-3 h-3 text-slate-400"></i>
                                <span x-text="kw"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="max-h-64 overflow-y-auto">
                    <div class="px-3 py-1.5 bg-slate-100 dark:bg-slate-900 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center justify-between">
                        <span>Rekomendasi Dokumen Terbaru</span>
                        <span>Akses Cepat</span>
                    </div>
                    <template x-for="item in recentDocs" :key="item.id">
                        <a :href="item.url" class="p-2.5 flex items-center justify-between gap-3 hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition font-sans border-b border-slate-100 dark:border-slate-900">
                            <div class="space-y-0.5 min-w-0 font-mono">
                                <div class="flex items-center gap-2">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="item.dept_code"></span>
                                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="item.box_number"></span>
                                </div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="item.title"></h4>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold shrink-0 font-mono" 
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
                <div x-show="results.length === 0" class="p-4 text-center text-xs font-bold text-slate-500">
                    Tidak ada dokumen ditemukan untuk kata kunci ini.
                </div>
                <div x-show="results.length > 0" class="divide-y divide-slate-200 dark:divide-slate-800 max-h-64 overflow-y-auto">
                    <template x-for="item in results" :key="item.id">
                        <a :href="item.url" class="p-2.5 flex items-center justify-between gap-3 hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition font-mono border-b border-slate-100 dark:border-slate-900">
                            <div class="space-y-0.5 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="item.dept_code"></span>
                                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="item.box_number"></span>
                                </div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="item.title"></h4>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold shrink-0 font-mono" x-text="item.status_label"></span>
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

        <!-- Borrowed -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm hover:border-purple-500 transition">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px] font-bold">
                <span>SEDANG DIPINJAM</span>
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

    <!-- MIDDLE GRID: WAREHOUSE METER & EXPIRY ALERTS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 font-mono">
        <!-- Warehouse Capacity Meter Box -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 shadow-sm flex flex-col justify-between">
            <legend class="px-2 font-mono text-xs font-bold text-amber-600 dark:text-amber-400 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded shadow-xs flex items-center gap-1.5">
                <i data-lucide="boxes" class="w-3.5 h-3.5"></i>
                KAPASITAS GUDANG ARSIP
            </legend>

            <div class="space-y-3">
                <div class="flex items-end justify-between">
                    <div>
                        <span class="text-[11px] text-slate-500 font-bold block">Box Terisi / Kapasitas Total</span>
                        <span class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $usedCapacity }} / {{ $totalCapacity }}</span>
                        <span class="text-xs text-slate-500"> Box</span>
                    </div>
                    <span class="text-lg font-black text-amber-600 dark:text-amber-400">{{ $capacityPercent }}%</span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full h-2.5 bg-slate-200 dark:bg-slate-900 rounded-full overflow-hidden border border-slate-300 dark:border-slate-700">
                    <div class="h-full bg-gradient-to-r from-emerald-500 via-amber-400 to-rose-500 transition-all duration-300" style="width: {{ min($capacityPercent, 100) }}%"></div>
                </div>

                <!-- Available Locations Breakdown -->
                <div class="space-y-1.5 pt-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase block">Lokasi Rak Terisi:</span>
                    @foreach($warehouseLocations->take(3) as $loc)
                    <div class="flex items-center justify-between p-1.5 rounded bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[11px]">
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $loc->full_location }}</span>
                        <span class="text-slate-500 font-bold">{{ $loc->current_box_count }}/{{ $loc->box_capacity }} Box</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-3 pt-2 border-t border-slate-200 dark:border-slate-800 text-right">
                <a href="{{ route('master.warehouses') }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline inline-flex items-center gap-1">
                    Kelola Lokasi Gudang <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </fieldset>

        <!-- Retention Expiry Alert Box -->
        <fieldset class="lg:col-span-2 border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 shadow-sm flex flex-col justify-between">
            <legend class="px-2 font-mono text-xs font-bold text-rose-600 dark:text-rose-400 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded shadow-xs flex items-center gap-1.5">
                <i data-lucide="hourglass" class="w-3.5 h-3.5"></i>
                PEMBERITAHUAN RETENTION EXPIRY (MASA SIMPAN)
            </legend>

            @if($expiringArchives->isEmpty())
            <div class="text-center py-6 text-slate-500 font-mono text-xs space-y-1">
                <i data-lucide="shield-check" class="w-8 h-8 mx-auto text-emerald-500 mb-1"></i>
                <p class="font-bold">Tidak ada berkas yang mendekati masa pemusnahan (90 Hari).</p>
            </div>
            @else
            <div class="space-y-2">
                @foreach($expiringArchives->take(3) as $exp)
                <div class="p-2 rounded bg-rose-500/10 border border-rose-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs font-mono">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-300">{{ $exp->department->code ?? 'GEN' }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $exp->title }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Box: <strong class="text-amber-600 dark:text-amber-400">{{ $exp->box_number ?? '-' }}</strong> | Expiry: <strong class="text-rose-600 dark:text-rose-400">{{ \Carbon\Carbon::parse($exp->retention_expiry_date)->format('d M Y') }}</strong></p>
                    </div>

                    <a href="{{ route('archives.show', $exp) }}" class="px-2.5 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white text-[11px] font-bold transition">
                        Proses Pemusnahan
                    </a>
                </div>
                @endforeach
            </div>
            @endif

            <div class="mt-3 pt-2 border-t border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 flex items-center justify-between">
                <span>Pemusnahan berkas memerlukan Berita Acara Pemusnahan (BAP)</span>
                <a href="{{ route('destructions.index') }}" class="font-bold text-rose-600 dark:text-rose-400 hover:underline">Lihat Semua Expiry &rarr;</a>
            </div>
        </fieldset>
    </div>

    <!-- BOTTOM DELPHI TDBGRID SPREADSHEET TABLE: RECENT ARCHIVES -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm overflow-hidden font-sans">
        <div class="p-2.5 bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between font-mono">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                <i data-lucide="folder-git-2" class="w-4 h-4 text-blue-500"></i>
                BERKAS ARSIP TERBARU (TDBGrid View)
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
                            {{ $archive->box_number ?? 'Penomoran Pending' }}
                        </td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">
                            {{ Str::limit($archive->title, 40) }}
                        </td>
                        <td class="py-2 px-3 font-mono">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $archive->department->code ?? 'GEN' }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-xs text-slate-600 dark:text-slate-400 font-mono">
                            {{ $archive->period_text ?? $archive->period_start_date->format('M Y') }}
                        </td>
                        <td class="py-2 px-3 text-xs text-slate-700 dark:text-slate-300 font-mono">
                            {{ $archive->location->full_location ?? 'Belum Ditentukan' }}
                        </td>
                        <td class="py-2 px-3 whitespace-nowrap font-mono">
                            @if($archive->status === 'draft')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 dark:border-slate-700">Draft</span>
                            @elseif($archive->status === 'pending_verification')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30">Antrean Verifikasi</span>
                            @elseif($archive->status === 'approved_booked')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30">Approved / Booking</span>
                            @elseif($archive->status === 'in_warehouse')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">Di Gudang</span>
                            @elseif($archive->status === 'borrowed')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30">Dipinjam</span>
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
    </div>
</div>
@endsection

