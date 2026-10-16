@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Pengajuan Pemusnahan Box - DMS PT Indraco')

@section('content')
<div class="max-w-5xl mx-auto space-y-3 font-sans pb-10" x-data="proposeCreateApp()">

    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="p-2 bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 rounded shrink-0">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate">
                    {{ auth()->user()->isPicDept() ? 'Formulir Pengajuan Usulan Pemusnahan Box Arsip' : 'Formulir Pengesahan / Pemusnahan Dokumen Arsip (BAP)' }}
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">
                    {{ auth()->user()->isPicDept() ? 'Usulan Pemusnahan dari Departemen • Dukungan Multi-Box Pemusnahan • Pratinjau & Cetak Form' : 'Pemusnahan Berkas Fisik • Berita Acara Pelaksanaan & Approval Pengesahan' }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-end shrink-0">
            <!-- Counter Chip -->
            <div class="px-2.5 py-1 bg-rose-500/10 border border-rose-500/30 rounded text-xs font-mono font-bold text-rose-700 dark:text-rose-300 flex items-center gap-1.5 whitespace-nowrap shrink-0">
                <i data-lucide="boxes" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i>
                <span x-text="selectedBoxesCount + ' Box Dipilih'"></span>
            </div>

            <!-- Tombol Cetak Form Pengajuan Pemusnahan -->
            <button 
                type="button" 
                @click="openFormPengajuanModal()" 
                class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700/60 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm whitespace-nowrap shrink-0 cursor-pointer"
                title="Buka Pratinjau & Cetak Form Pengajuan Dokumen Pemusnahan (Standar PDF/A4)"
            >
                <i data-lucide="printer" class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400 shrink-0"></i>
                <span>Form Pemusnahan</span>
            </button>

            <a href="{{ route('destructions.index', array_merge(['view' => 'destruction'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm whitespace-nowrap shrink-0">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                <span>Kembali ke Pemusnahan (Esc)</span>
            </a>
        </div>
    </div>

    <!-- Pass archives data safely via JavaScript -->
    <script>
        window.destructionArchives = @json($archives ?? []);
    </script>

    <!-- Scope Security Alert (Jika PIC Dept) -->
    @if(auth()->user()->isPicDept())
    <div class="p-2.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs font-mono flex items-center gap-2.5 shadow-2xs">
        <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0"></i>
        <div>
            <strong class="uppercase font-bold">Filter Keamanan Departemen Terkunci:</strong>
            Menampilkan berkas arsip milik <span class="font-bold underline">{{ auth()->user()->department->name ?? 'Departemen Anda' }} ({{ auth()->user()->department->code ?? 'DEPT' }})</span>.
        </div>
    </div>
    @endif

    {{-- Validation Errors Alert Card --}}
    @if(isset($errors) && $errors->any())
    <div class="p-3 rounded bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs font-mono space-y-1 shadow-sm">
        <div class="font-bold flex items-center gap-1.5 text-rose-700 dark:text-rose-300">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0"></i>
            <span>Pengajuan Pemusnahan Gagal Diproses. Silakan periksa formulir berikut:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-600 dark:text-rose-400 pl-4">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- MAIN FORM WINDOW CARD -->
    <form action="{{ route('destructions.store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <!-- Hidden Archive IDs (Multi-Box Support & Single Fallback) -->
        <template x-for="arc in selectedArchives" :key="arc.id">
            <input type="hidden" name="archive_ids[]" :value="arc.id">
        </template>
        <input type="hidden" name="archive_id" :value="selectedArchives[0]?.id || ''">

        <!-- SECTION 1: DAFTAR TARGET PEMUSNAHAN BERKAS (SEARCH & ACCORDION LIST) -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl p-3.5 shadow-sm space-y-3 font-mono">
            
            <!-- Section Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            1. Daftar Box Target Pemusnahan Berkas Fisik
                        </h2>
                        <p class="text-[10px] text-slate-500 font-sans">
                            Cari dan tambahkan box arsip yang ingin dimusnahkan. Klik baris box untuk membuka rincian dokumen (buka bergantian).
                        </p>
                    </div>
                </div>
            </div>

            <!-- SEARCH BAR BOX GUDANG (Langsung buka pencarian & tambahkan ke list di bawahnya) -->
            <div class="space-y-1.5">
                <label for="archiveSearchInput" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">
                    PENCARIAN BERKAS GUDANG UNTUK DITAMBAHKAN <span class="text-rose-500">*</span>
                </label>
                <div class="relative" @click.away="searchDropdownOpen = false">
                    <div class="relative">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 absolute left-2.5 top-2.5"></i>
                        <input 
                            type="text" 
                            id="archiveSearchInput"
                            x-ref="searchInput"
                            x-model="globalSearch"
                            @focus="searchDropdownOpen = true"
                            @input="searchDropdownOpen = true"
                            placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas untuk dimusnahkan..." 
                            class="w-full pl-8 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500 transition shadow-2xs"
                        >
                        <!-- Clear Search Icon -->
                        <button type="button" x-show="globalSearch.length > 0" @click="globalSearch = ''; searchDropdownOpen = false;" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-rose-500 cursor-pointer">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Autocomplete Dropdown List -->
                    <div x-show="searchDropdownOpen && filteredArchives.length > 0" 
                         x-cloak
                         x-transition 
                         class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xl z-50 max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
                        <template x-for="arc in filteredArchives" :key="arc.id">
                            <div @click="!isAlreadySelected(arc.id) && addArchiveToSelection(arc)" 
                                 class="p-2.5 transition flex items-center justify-between gap-3"
                                 :class="isAlreadySelected(arc.id) 
                                    ? 'opacity-40 bg-slate-50 dark:bg-slate-950 cursor-not-allowed' 
                                    : 'hover:bg-rose-500/10 dark:hover:bg-rose-500/20 cursor-pointer'">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 px-1.5 py-0.2 rounded border border-rose-500/20 text-[10px]" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.2 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                        <span x-show="isAlreadySelected(arc.id)" class="text-[9px] font-bold text-rose-600 bg-rose-500/10 px-1.5 py-0.2 rounded border border-rose-500/20 flex items-center gap-1">
                                            <i data-lucide="check" class="w-2.5 h-2.5"></i> Sudah di List
                                        </span>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="arc.title"></h4>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400" x-text="'Masa Simpan: ' + (arc.retention_display || (arc.retention_years ? arc.retention_years + ' Thn' : '-')) + ' (Expiry: ' + (arc.formatted_expiry_date || arc.retention_expiry_date || '-') + ')'"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        <span x-text="arc.short_location || arc.full_slot_location || 'Gudang'"></span>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty Search Result State -->
                    <div x-show="searchDropdownOpen && filteredArchives.length === 0" x-cloak class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xl p-3 text-center text-slate-500 text-xs font-mono z-50">
                        Tidak ada berkas arsip yang cocok dengan pencarian Anda.
                    </div>
                </div>
                @error('archive_ids') <span class="text-rose-500 font-mono text-[11px] font-bold block">{{ $message }}</span> @enderror
            </div>

            <!-- LIST DAFTAR BOX TERPILIH (ACCORDION BERGANTIAN - DEFAULT SEMUA MENUTUP) -->
            <div class="space-y-2 pt-1">
                <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 dark:text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="list" class="w-3.5 h-3.5 text-rose-500"></i>
                        <span>Daftar Box Terpilih (<span x-text="selectedArchives.length"></span> Box)</span>
                    </span>
                    <span class="text-[10px] text-slate-400 italic">Klik baris box untuk melihat rincian dokumen (terbuka satu per satu)</span>
                </div>

                <!-- Empty State -->
                <div x-show="selectedArchives.length === 0" class="p-6 text-center rounded-lg border border-dashed border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40 font-mono text-xs space-y-1.5">
                    <i data-lucide="package-open" class="w-8 h-8 text-slate-400 mx-auto"></i>
                    <p class="font-bold text-slate-700 dark:text-slate-300">Belum Ada Box Arsip yang Dipilih</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Gunakan kolom pencarian di atas untuk menambahkan box target pemusnahan.</p>
                </div>

                <!-- Accordion Items -->
                <template x-for="(arc, aIdx) in selectedArchives" :key="arc.id">
                    <div class="rounded-lg border transition duration-150 shadow-2xs overflow-hidden"
                         :class="openAccordionIndex === aIdx ? 'border-rose-500 bg-white dark:bg-slate-900 ring-1 ring-rose-500/20' : 'border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 hover:border-slate-400 dark:hover:border-slate-700'">
                        
                        <!-- Accordion Header Row: Judul, Lokasi, Periode, No Box Arsip -->
                        <div class="p-2.5 sm:p-3 flex items-center justify-between gap-3 cursor-pointer select-none"
                             @click="toggleAccordion(aIdx)">
                            
                            <div class="flex flex-wrap items-center gap-2 min-w-0 flex-1">
                                <!-- Box Number Badge -->
                                <span class="px-2 py-0.5 rounded text-[10px] font-black font-mono shrink-0"
                                      :class="openAccordionIndex === aIdx ? 'bg-rose-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                      x-text="aIdx + 1"></span>

                                <!-- Judul Berkas -->
                                <span class="font-bold text-xs text-slate-900 dark:text-white truncate max-w-[200px] sm:max-w-[260px] md:max-w-[320px]" 
                                      :title="arc.title"
                                      x-text="arc.title"></span>

                                <!-- No Box Arsip -->
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20 shrink-0"
                                      :title="'Nomor Box: ' + (arc.box_number || '-')"
                                      x-text="arc.box_number || 'PENOMORAN PENDING'"></span>

                                <!-- Lokasi Rak Gudang -->
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1 shrink-0"
                                      :title="'Lokasi: ' + (arc.short_location || arc.full_slot_location || 'Gudang')">
                                    <i data-lucide="map-pin" class="w-3 h-3 text-emerald-500"></i>
                                    <span x-text="arc.short_location || arc.full_slot_location || 'Gudang'"></span>
                                </span>

                                <!-- Periode / Expiry -->
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center gap-1 shrink-0"
                                      :title="'Periode: ' + (arc.period_text || arc.periode_doc || '-')">
                                    <i data-lucide="calendar" class="w-3 h-3 text-slate-500"></i>
                                    <span x-text="arc.period_text || arc.periode_doc || '-'"></span>
                                </span>
                            </div>

                            <!-- Right Actions: Remove & Chevron Toggle -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" 
                                        @click.stop="removeArchive(aIdx)" 
                                        class="p-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded transition cursor-pointer"
                                        title="Hapus box ini dari daftar pemusnahan">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <span class="p-1 rounded text-slate-400 transition-transform duration-200"
                                      :class="{ 'rotate-180 text-rose-600 dark:text-rose-400': openAccordionIndex === aIdx }">
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Accordion Content: Metadata & Document List -->
                        <div x-show="openAccordionIndex === aIdx" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 space-y-3 font-mono">
                            
                            <!-- Metadata Mini Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                <div class="p-2 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase">DEPARTEMEN</span>
                                    <span class="font-bold text-slate-800 dark:text-white truncate block text-[11px]" x-text="arc.department ? (arc.department.code + ' - ' + arc.department.name) : 'UMUM'"></span>
                                </div>
                                <div class="p-2 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase">LOKASI RAK GUDANG</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400 truncate block text-[11px]" x-text="arc.short_location || arc.full_slot_location || 'Gudang'"></span>
                                </div>
                                <div class="p-2 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase">PERIODE BERKAS</span>
                                    <span class="font-bold text-amber-600 dark:text-amber-400 truncate block text-[11px]" x-text="arc.period_text || arc.period_start_date || '-'"></span>
                                </div>
                                <div class="p-2 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase">STATUS / EXPIRY</span>
                                    <span class="font-bold text-rose-600 dark:text-rose-400 truncate block text-[11px]" x-text="arc.formatted_expiry_date || arc.retention_expiry_date || 'Status: ' + (arc.status || '-')"></span>
                                </div>
                            </div>

                            <!-- Document List Table (Ditata Rapi) -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-500"></i>
                                        <span>Rincian Berkas Dokumen di Dalam Box (<span x-text="getArchiveItems(arc).length"></span> Dokumen)</span>
                                    </span>
                                </div>

                                <div class="rounded border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-slate-900 shadow-2xs">
                                    <table class="w-full text-left text-xs divide-y divide-slate-200 dark:divide-slate-800">
                                        <thead class="bg-slate-100 dark:bg-slate-800/70 text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase">
                                            <tr>
                                                <th class="py-1.5 px-2.5 w-10 text-center">No</th>
                                                <th class="py-1.5 px-3">Nama Berkas / Dokumen</th>
                                                <th class="py-1.5 px-3 w-44">Periode</th>
                                                <th class="py-1.5 px-3">Keterangan / Butir</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-[11px]">
                                            <template x-for="item in getArchiveItems(arc)" :key="item.number">
                                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                                    <td class="py-1.5 px-2.5 text-center font-bold text-slate-500 dark:text-slate-400" x-text="item.number"></td>
                                                    <td class="py-1.5 px-3 font-semibold text-slate-900 dark:text-white" x-text="item.name"></td>
                                                    <td class="py-1.5 px-3 text-amber-700 dark:text-amber-400 font-mono text-[10px]" x-text="item.period"></td>
                                                    <td class="py-1.5 px-3 text-slate-600 dark:text-slate-400 text-[10px]" x-text="item.notes || '-'"></td>
                                                </tr>
                                            </template>
                                            <template x-if="getArchiveItems(arc).length === 0">
                                                <tr>
                                                    <td colspan="4" class="py-3 px-3 text-center text-slate-400 italic text-[11px]">
                                                        Tidak ada rincian dokumen tersimpan untuk box arsip ini.
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- SECTION 2: PARAMETER BERITA ACARA PEMUSNAHAN (BAP) -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3 font-mono">
            <legend class="px-2 font-mono text-[11px] font-bold text-rose-700 dark:text-rose-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="clipboard-list" class="w-3.5 h-3.5 text-rose-500"></i>
                2. Parameter Berita Acara Pemusnahan (BAP)
            </legend>

            <div class="grid grid-cols-1 {{ !auth()->user()->isPicDept() ? 'sm:grid-cols-2' : '' }} gap-3">
                @if(!auth()->user()->isPicDept())
                <!-- BAP Number (Khusus Gudang / Super Admin) -->
                <div>
                    <label for="bap_number" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        NOMOR BERITA ACARA (BAP) <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="bap_number" 
                        id="bap_number" 
                        x-model="autoBap"
                        required 
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-rose-600 dark:text-rose-400 focus:outline-none focus:border-rose-500 transition shadow-2xs"
                    >
                    @error('bap_number') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
                @else
                <input type="hidden" name="bap_number" :value="autoBap">
                @endif

                <!-- Destruction Date -->
                <div>
                    <label for="destruction_date" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        TANGGAL PELAKSANAAN PEMUSNAHAN <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="destruction_date" 
                        id="destruction_date" 
                        value="{{ old('destruction_date', date('Y-m-d')) }}" 
                        required 
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition shadow-2xs"
                    >
                    @error('destruction_date') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Method -->
            <div>
                <label for="method" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                    METODE FISIK PEMUSNAHAN <span class="text-rose-500">*</span>
                </label>
                <select name="method" id="method" required class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition shadow-2xs cursor-pointer">
                    <option value="Pencacahan Mesin Industrial Paper Shredder" {{ old('method') == 'Pencacahan Mesin Industrial Paper Shredder' ? 'selected' : '' }}>Pencacahan Mesin Industrial Paper Shredder</option>
                    <option value="Pembakaran Standard Suhu Tinggi" {{ old('method') == 'Pembakaran Standard Suhu Tinggi' ? 'selected' : '' }}>Pembakaran Standard Suhu Tinggi (Incinerator)</option>
                    <option value="Peleburan Bahan Kimia & Daur Ulang" {{ old('method') == 'Peleburan Bahan Kimia & Daur Ulang' ? 'selected' : '' }}>Peleburan Bahan Kimia & Daur Ulang Industri</option>
                </select>
                @error('method') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Notes / Reason -->
            <div>
                <label for="notes" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                    CATATAN / ALASAN PEMUSNAHAN BERKAS <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <textarea 
                    name="notes" 
                    id="notes" 
                    rows="3" 
                    placeholder="Catatan tambahan mengenai kondisi fisik berkas atau tim pelaksana pemusnahan..." 
                    class="w-full px-2.5 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-rose-500 transition shadow-2xs leading-relaxed"
                >{{ old('notes') }}</textarea>
                @error('notes') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </fieldset>

        <!-- SECTION 3: UPLOAD DOKUMEN PERSETUJUAN & SERTIFIKAT BAP -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3 font-mono">
            <legend class="px-2 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                3. Upload Dokumen Persetujuan (Approval) & Berkas BAP
            </legend>

            <!-- Fast Print Action Banner -->
            <div class="p-2.5 rounded-lg bg-rose-50/80 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs font-mono">
                <div class="flex items-center gap-2 text-rose-800 dark:text-rose-300">
                    <i data-lucide="info" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span>Belum memiliki lembar formulir fisik resmi untuk ditandatangani sebelum diunggah?</span>
                </div>
                <button 
                    type="button" 
                    @click="openFormPengajuanModal()" 
                    class="px-3 py-1 bg-white dark:bg-slate-900 hover:bg-rose-50 text-rose-700 dark:text-rose-300 font-bold text-xs rounded border border-rose-300 dark:border-rose-700 shadow-2xs flex items-center gap-1.5 transition cursor-pointer shrink-0"
                >
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-rose-600"></i>
                    <span>Cetak Form Pemusnahan</span>
                </button>
            </div>

            <div class="grid grid-cols-1 {{ !auth()->user()->isPicDept() ? 'md:grid-cols-2' : '' }} gap-4 font-mono text-xs"
                 @file-change="if($event.detail.name === 'approval_file') { hasApprovalFile = $event.detail.hasFile && !$event.detail.isOverLimit; }">
                <!-- 1. Dokumen Approval (Wajib) -->
                <div>
                    <x-file-uploader 
                        name="approval_file" 
                        id="approval_file" 
                        label="Dokumen Persetujuan (Approval)" 
                        :required="true" 
                        badge="WAJIB UNTUK PEMUSNAHAN" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        :maxSizeKB="500"
                        :maxSizeMB="0.5"
                        helperText="Wajib upload scan persetujuan pemusnahan bertandatangan (Format: PDF, JPG, PNG Maks. 500 KB)."
                    />
                    @error('approval_file') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                @if(!auth()->user()->isPicDept())
                <!-- 2. Sertifikat / Berkas BAP (Opsional untuk selain Departemen) -->
                <div>
                    <x-file-uploader 
                        name="certificate_file" 
                        id="certificate_file" 
                        label="Sertifikat / Berkas BAP (Opsional)" 
                        :required="false" 
                        badge="OPSIONAL" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        :maxSizeKB="500"
                        :maxSizeMB="0.5"
                        helperText="Scan sertifikat atau berita acara pelaksanaan jika sudah tersedia (Format: PDF, JPG, PNG Maks. 500 KB)."
                    />
                </div>
                @endif
            </div>
        </fieldset>

        <!-- DELPHI FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-2 font-mono">
            <!-- Left status hint -->
            <div class="text-xs">
                <span x-show="selectedArchives.length === 0" class="text-slate-500 font-bold">• Silakan pilih minimal 1 berkas arsip gudang di atas</span>
                <span x-show="selectedArchives.length > 0 && !hasApprovalFile" class="text-rose-500 font-bold">• Unggah dokumen approval untuk {{ auth()->user()->isPicDept() ? 'mengajukan usulan pemusnahan ke Gudang' : 'mengaktifkan tombol pengesahan' }}</span>
                <span x-show="canSubmit" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> 
                    <span x-text="selectedArchives.length + ' box berkas & approval lengkap, siap ' + (isPicDept ? 'diajukan ke Gudang' : 'disahkan')"></span>
                </span>
            </div>

            <!-- Right Buttons -->
            <div class="flex items-center gap-2">
                <a href="{{ route('destructions.index', array_merge(['view' => 'destruction'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="!canSubmit" 
                        :class="canSubmit ? 'bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white font-black border-rose-700 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-not-allowed font-bold'"
                        class="px-5 py-2 text-xs rounded border transition flex items-center gap-1.5">
                    <i data-lucide="{{ auth()->user()->isPicDept() ? 'send' : 'shield-alert' }}" class="w-3.5 h-3.5"></i>
                    <span x-show="selectedArchives.length <= 1">{{ auth()->user()->isPicDept() ? 'Kirim Pengajuan Pemusnahan' : 'Sahkan Pemusnahan Berkas' }}</span>
                    <span x-show="selectedArchives.length > 1" x-text="(isPicDept ? 'Kirim Pengajuan (' : 'Sahkan Pemusnahan (') + selectedArchives.length + ' Box Arsip)'"></span>
                </button>
            </div>
        </div>
    </form>

    {{-- Komponen Modal Pratinjau & Cetak Form Pengajuan Dokumen Standar PT Indraco --}}
    <x-form-pengajuan-modal />
</div>
@endsection

@push('scripts')
<script>
function proposeCreateApp() {
    return {
        selectedArchives: [],
        globalSearch: '',
        searchDropdownOpen: false,
        openAccordionIndex: null, // null = default semua menutup
        hasApprovalFile: false,
        autoBap: '{{ old('bap_number', $autoBapNumber ?? '') }}',
        isPicDept: {{ auth()->user()->isPicDept() ? 'true' : 'false' }},
        archives: window.destructionArchives || [],

        init() {
            const initialIds = @json($selectedArchiveIds ?? ($selectedArchiveId ? [$selectedArchiveId] : []));
            if (Array.isArray(initialIds) && initialIds.length > 0) {
                initialIds.forEach(id => {
                    const found = this.archives.find(a => a.id == id);
                    if (found && !this.isAlreadySelected(found.id)) {
                        this.selectedArchives.push(found);
                    }
                });
            }
            if (this.selectedArchives.length > 0 && !this.autoBap) {
                this.autoBap = 'BAP/IND/' + new Date().getFullYear() + '/' + String(this.selectedArchives[0].id).padStart(5, '0');
            }
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        focusSearch() {
            this.searchDropdownOpen = true;
            this.$nextTick(() => {
                const el = document.getElementById('archiveSearchInput');
                if (el) el.focus();
            });
        },

        get filteredArchives() {
            if (!this.globalSearch.trim()) return this.archives.slice(0, 15);
            const q = this.globalSearch.toLowerCase();
            return this.archives.filter(a => 
                (a.box_number && a.box_number.toLowerCase().includes(q)) ||
                (a.title && a.title.toLowerCase().includes(q)) ||
                (a.period_text && a.period_text.toLowerCase().includes(q)) ||
                (a.department && a.department.code && a.department.code.toLowerCase().includes(q)) ||
                (a.rack_label && a.rack_label.toLowerCase().includes(q)) ||
                (a.short_location && a.short_location.toLowerCase().includes(q))
            );
        },

        isAlreadySelected(arcId) {
            return this.selectedArchives.some(a => a.id === arcId);
        },

        addArchiveToSelection(arc) {
            if (this.isAlreadySelected(arc.id)) return;
            this.selectedArchives.push(arc);
            this.globalSearch = '';
            this.searchDropdownOpen = false;
            if (!this.autoBap) {
                this.autoBap = 'BAP/IND/' + new Date().getFullYear() + '/' + String(arc.id).padStart(5, '0');
            }
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        async removeArchive(index) {
            if (window.showConfirmModal) {
                const arc = this.selectedArchives[index];
                const ok = await window.showConfirmModal({
                    title: 'Hapus Box Pemusnahan',
                    message: `Apakah Anda yakin ingin membatalkan pilihan Box "${arc.box_number || arc.title}" dari daftar pemusnahan?`,
                    type: 'danger',
                    confirmText: 'Ya, Hapus'
                });
                if (!ok) return;
            }

            if (this.openAccordionIndex === index) {
                this.openAccordionIndex = null;
            } else if (this.openAccordionIndex > index) {
                this.openAccordionIndex--;
            }

            this.selectedArchives.splice(index, 1);
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        // Accordion: buka bergantian (single open) agar tidak terlalu ke bawah
        toggleAccordion(index) {
            if (this.openAccordionIndex === index) {
                this.openAccordionIndex = null;
            } else {
                this.openAccordionIndex = index;
            }
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        // Rincian dokumen dalam box ditata rapi
        getArchiveItems(arc) {
            if (!arc) return [];
            if (arc.items && Array.isArray(arc.items) && arc.items.length > 0) {
                return arc.items.map((it, idx) => ({
                    number: it.item_number || (idx + 1),
                    name: it.document_name || '-',
                    period: it.period_text || (it.period_start ? `${it.period_start} s/d ${it.period_end || ''}` : '-'),
                    notes: it.notes || '-'
                }));
            }
            if (arc.content_description && arc.content_description.trim()) {
                const desc = arc.content_description.trim();
                const parts = desc.split(/(?=\b\d+\.\s*)/g).filter(p => p.trim());
                if (parts.length > 0) {
                    return parts.map((p, idx) => {
                        const cleaned = p.replace(/^\d+\.\s*/, '').trim();
                        let period = '-';
                        let notes = '-';
                        let name = cleaned;
                        const mPeriod = cleaned.match(/\(([^)]+)\)/);
                        if (mPeriod) {
                            period = mPeriod[1];
                            name = cleaned.replace(/\([^)]+\)/, '').trim();
                        }
                        const mNotes = name.split(/\s*-\s*(.+)/);
                        if (mNotes.length > 1) {
                            name = mNotes[0].trim();
                            notes = mNotes[1].trim();
                        }
                        return {
                            number: idx + 1,
                            name: name || cleaned,
                            period: period,
                            notes: notes
                        };
                    });
                }
            }
            return [];
        },

        get selectedBoxesCount() {
            return this.selectedArchives.length;
        },

        get canSubmit() {
            return this.selectedArchives.length > 0 && this.hasApprovalFile;
        },

        // Form Pengajuan Dokumen (Pemusnahan) State
        formModalOpen: false,
        formPengajuanData: {
            no_form: '',
            tanggal_pengajuan: '{{ date("Y-m-d") }}',
            dept_display: @json(auth()->user()->department ? auth()->user()->department->name : ''),
            pic_pemohon: @json(auth()->user()->name ?? ''),
            tipe: 'Pemusnahan',
            masa_simpan: 0,
            catatan: 'Permohonan pemusnahan berkas arsip yang telah habis masa simpannya.',
            approval_pemohon: @json(auth()->user()->name ?? ''),
            approval_atasan: '',
            approval_pimpinan: '',
            approval_admin_gudang: 'Admin Gudang',
            approval_tgl: ''
        },
        formPengajuanRows: [],

        openFormPengajuanModal() {
            this.populateFormRows();
            this.formPengajuanData.tipe = 'Pemusnahan';
            this.formPengajuanData.catatan = 'Usulan pemusnahan fisik berkas arsip. Catatan: ' + (document.getElementById('notes')?.value || '');
            this.formPengajuanData.approval_pemohon = this.formPengajuanData.pic_pemohon;
            this.formModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        populateFormRows() {
            const rows = [];
            const padTo = 12;

            this.selectedArchives.forEach((arc, idx) => {
                const rackLoc = arc.rack_label || (arc.short_location && arc.short_location !== 'Belum Ada Lokasi' ? arc.short_location : '') || '';
                const boxLabel = rackLoc || arc.box_number || arc.archive_code || '';

                const subDeptName = arc.sub_department ? (arc.sub_department.code || arc.sub_department.name) : (arc.sub_department_id ? 'Inti Dept' : '-');

                rows.push({
                    no: idx + 1,
                    title: arc.title || ('Box ' + (arc.box_number || (idx + 1))),
                    sub_dept: subDeptName,
                    masa_simpan: 'Habis',
                    period_end: arc.formatted_expiry_date || arc.retention_expiry_date || '-',
                    label_kardus: boxLabel,
                    label_box: boxLabel
                });
            });

            const currentCount = rows.length;
            for (let i = currentCount + 1; i <= Math.max(padTo, currentCount); i++) {
                rows.push({
                    no: i,
                    title: '',
                    sub_dept: '',
                    masa_simpan: '',
                    period_end: '',
                    label_box: '',
                    label_kardus: ''
                });
            }

            this.formPengajuanRows = rows;
        },

        printFormPengajuan() {
            if (window.dmsPrint) {
                window.dmsPrint('form-pengajuan-print-document');
            } else {
                window.print();
            }
        },

        formatDateDisplay(dateStr) {
            if (!dateStr) return '';
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
            } catch(e) {
                return dateStr;
            }
        },

        getDeptSubDeptDisplay() {
            return this.formPengajuanData.dept_display || '';
        }
    };
}
</script>
@endpush
