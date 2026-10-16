@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Perpanjangan Masa Simpan Box - DMS PT Indraco')

@section('content')
<div class="max-w-5xl mx-auto space-y-3 font-sans pb-10" x-data="extendCreateApp()">

    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="p-2 bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30 rounded shrink-0">
                <i data-lucide="clock" class="w-4 h-4"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate">Formulir Pengajuan Perpanjangan Masa Simpan Box Arsip [Form Expiry]</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">Perpanjangan Masa Retensi Berkas • Dukungan Multi-Box Perpanjangan • Pratinjau & Cetak Form</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-end shrink-0">
            <!-- Counter Chip -->
            <div class="px-2.5 py-1 bg-purple-500/10 border border-purple-500/30 rounded text-xs font-mono font-bold text-purple-700 dark:text-purple-300 flex items-center gap-1.5 whitespace-nowrap shrink-0">
                <i data-lucide="boxes" class="w-3.5 h-3.5 text-purple-500 shrink-0"></i>
                <span x-text="selectedBoxesCount + ' Box Dipilih'"></span>
            </div>

            <!-- Tombol Cetak Form Pengajuan Perpanjangan -->
            <button 
                type="button" 
                @click="openFormPengajuanModal()" 
                class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/50 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-300 border border-purple-300 dark:border-purple-700/60 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm whitespace-nowrap shrink-0 cursor-pointer"
                title="Buka Pratinjau & Cetak Form Pengajuan Dokumen Perpanjangan (Standar PDF/A4)"
            >
                <i data-lucide="printer" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400 shrink-0"></i>
                <span>Form Perpanjangan</span>
            </button>

            <a href="{{ route('destructions.index', array_merge(['view' => 'expiry'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm whitespace-nowrap shrink-0">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                <span>Kembali ke Expiry (Esc)</span>
            </a>
        </div>
    </div>

    <!-- Pass archives data safely via JavaScript -->
    <script>
        window.extendArchives = @json($archives ?? []);
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
            <span>Pengajuan Perpanjangan Gagal Diproses. Silakan periksa formulir berikut:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-600 dark:text-rose-400 pl-4">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- MAIN FORM WINDOW CARD -->
    <form action="{{ route('destructions.extend_store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <!-- Hidden Archive IDs (Multi-Box Support & Single Fallback) -->
        <template x-for="arc in selectedArchives" :key="arc.id">
            <input type="hidden" name="archive_ids[]" :value="arc.id">
        </template>
        <input type="hidden" name="archive_id" :value="selectedArchives[0]?.id || ''">
        <input type="hidden" name="additional_years" :value="Math.max(1, Math.round(additionalMonths / 12))">

        <!-- SECTION 1: DAFTAR TARGET PERPANJANGAN MASA SIMPAN (SEARCH & ACCORDION LIST) -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl p-3.5 shadow-sm space-y-3 font-mono">
            
            <!-- Section Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            1. Daftar Box Target Perpanjangan Masa Simpan
                        </h2>
                        <p class="text-[10px] text-slate-500 font-sans">
                            Cari dan tambahkan box arsip yang ingin diperpanjang. Klik baris box untuk membuka rincian dokumen (buka bergantian).
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
                            placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas untuk perpanjangan masa simpan..." 
                            class="w-full pl-8 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition shadow-2xs"
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
                                    : 'hover:bg-purple-500/10 dark:hover:bg-purple-500/20 cursor-pointer'">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-purple-600 dark:text-purple-400 bg-purple-500/10 px-1.5 py-0.2 rounded border border-purple-500/20 text-[10px]" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.2 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                        <span x-show="isAlreadySelected(arc.id)" class="text-[9px] font-bold text-purple-600 bg-purple-500/10 px-1.5 py-0.2 rounded border border-purple-500/20 flex items-center gap-1">
                                            <i data-lucide="check" class="w-2.5 h-2.5"></i> Sudah di List
                                        </span>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="arc.title"></h4>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400" x-text="'Masa Simpan: ' + (arc.retention_display || (arc.retention_years ? arc.retention_years + ' Thn' : '-')) + ' (Expiry: ' + (arc.formatted_expiry_date || arc.retention_expiry_date || '-') + ')'"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        <span x-text="arc.formatted_expiry_date || arc.retention_expiry_date || 'Aktif'"></span>
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
                        <i data-lucide="list" class="w-3.5 h-3.5 text-purple-500"></i>
                        <span>Daftar Box Terpilih (<span x-text="selectedArchives.length"></span> Box)</span>
                    </span>
                    <span class="text-[10px] text-slate-400 italic">Klik baris box untuk melihat rincian dokumen (terbuka satu per satu)</span>
                </div>

                <!-- Empty State -->
                <div x-show="selectedArchives.length === 0" class="p-6 text-center rounded-lg border border-dashed border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40 font-mono text-xs space-y-1.5">
                    <i data-lucide="package-open" class="w-8 h-8 text-slate-400 mx-auto"></i>
                    <p class="font-bold text-slate-700 dark:text-slate-300">Belum Ada Box Arsip yang Dipilih</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Gunakan kolom pencarian di atas untuk menambahkan box target perpanjangan.</p>
                </div>

                <!-- Accordion Items -->
                <template x-for="(arc, aIdx) in selectedArchives" :key="arc.id">
                    <div class="rounded-lg border transition duration-150 shadow-2xs overflow-hidden"
                         :class="openAccordionIndex === aIdx ? 'border-purple-500 bg-white dark:bg-slate-900 ring-1 ring-purple-500/20' : 'border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 hover:border-slate-400 dark:hover:border-slate-700'">
                        
                        <!-- Accordion Header Row: Judul, Lokasi, Periode, No Box Arsip -->
                        <div class="p-2.5 sm:p-3 flex items-center justify-between gap-3 cursor-pointer select-none"
                             @click="toggleAccordion(aIdx)">
                            
                            <div class="flex flex-wrap items-center gap-2 min-w-0 flex-1">
                                <!-- Box Number Badge -->
                                <span class="px-2 py-0.5 rounded text-[10px] font-black font-mono shrink-0"
                                      :class="openAccordionIndex === aIdx ? 'bg-purple-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                      x-text="aIdx + 1"></span>

                                <!-- Judul Berkas -->
                                <span class="font-bold text-xs text-slate-900 dark:text-white truncate max-w-[200px] sm:max-w-[260px] md:max-w-[320px]" 
                                      :title="arc.title"
                                      x-text="arc.title"></span>

                                <!-- No Box Arsip -->
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20 shrink-0"
                                      :title="'Nomor Box: ' + (arc.box_number || '-')"
                                      x-text="arc.box_number || 'PENOMORAN PENDING'"></span>

                                <!-- Lokasi Rak Gudang -->
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1 shrink-0"
                                      :title="'Lokasi: ' + (arc.short_location || arc.full_slot_location || 'Gudang')">
                                    <i data-lucide="map-pin" class="w-3 h-3 text-emerald-500"></i>
                                    <span x-text="arc.short_location || arc.full_slot_location || 'Gudang'"></span>
                                </span>

                                <!-- Expiry Saat Ini -->
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20 flex items-center gap-1 shrink-0"
                                      :title="'Jatuh Tempo: ' + (arc.formatted_expiry_date || arc.retention_expiry_date || '-')">
                                    <i data-lucide="clock" class="w-3 h-3 text-rose-500"></i>
                                    <span x-text="'Expiry: ' + (arc.formatted_expiry_date || arc.retention_expiry_date || '-')"></span>
                                </span>
                            </div>

                            <!-- Right Actions: Remove & Chevron Toggle -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" 
                                        @click.stop="removeArchive(aIdx)" 
                                        class="p-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded transition cursor-pointer"
                                        title="Hapus box ini dari daftar perpanjangan">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <span class="p-1 rounded text-slate-400 transition-transform duration-200"
                                      :class="{ 'rotate-180 text-purple-600 dark:text-purple-400': openAccordionIndex === aIdx }">
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
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase">MASA SIMPAN SAAT INI</span>
                                    <span class="font-bold text-purple-600 dark:text-purple-400 truncate block text-[11px]" x-text="arc.retention_duration_label || ((arc.retention_years || '5') + ' Tahun')"></span>
                                </div>
                                <div class="p-2 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase">JATUH TEMPO (EXPIRY)</span>
                                    <span class="font-bold text-rose-600 dark:text-rose-400 truncate block text-[11px]" x-text="arc.formatted_expiry_date || arc.retention_expiry_date || '-'"></span>
                                </div>
                            </div>

                            <!-- Document List Table (Ditata Rapi) -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-purple-500"></i>
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

        <!-- SECTION 2: PARAMETER PERPANJANGAN MASA SIMPAN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3 font-mono">
            <legend class="px-2 font-mono text-[11px] font-bold text-indigo-700 dark:text-indigo-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="calendar-plus" class="w-3.5 h-3.5 text-indigo-500"></i>
                2. Parameter Perpanjangan Masa Simpan Dokumen
            </legend>

            <!-- Additional Months Input & Preset Buttons -->
            <div class="space-y-1.5">
                <label for="additional_months" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">
                    TAMBAHAN MASA SIMPAN (BULAN) <span class="text-rose-500">*</span>
                </label>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex items-center w-40">
                        <input 
                            type="number" 
                            name="additional_months" 
                            id="additional_months" 
                            x-model="additionalMonths"
                            @input="if (additionalMonths > getMaxMonths()) additionalMonths = getMaxMonths(); if (additionalMonths < 1 && additionalMonths !== '') additionalMonths = 1;"
                            min="1" 
                            :max="getMaxMonths()"
                            required 
                            :placeholder="'Misal: 6, 12, ' + getMaxMonths() + '...'" 
                            class="w-full pl-2.5 pr-14 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-purple-700 dark:text-purple-300 placeholder-slate-400 focus:outline-none focus:border-purple-500 transition shadow-2xs"
                        >
                        <span class="absolute right-2.5 text-[11px] font-mono font-bold text-slate-400 pointer-events-none select-none">Bulan</span>
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="flex flex-wrap items-center gap-1 font-mono text-xs">
                        <span class="text-slate-400 text-[10px] mr-0.5">Preset:</span>
                        <template x-for="preset in getPresetMonths()" :key="preset">
                            <button 
                                type="button" 
                                @click="additionalMonths = preset" 
                                class="px-2 py-0.5 rounded border transition cursor-pointer text-[11px]"
                                :class="additionalMonths == preset 
                                    ? 'bg-purple-600 text-white border-purple-600 font-black shadow-xs' 
                                    : 'bg-slate-100 dark:bg-slate-800 hover:bg-purple-500/10 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 font-semibold'"
                                x-text="preset + ' Bln' + (preset == 120 ? ' (10 Thn)' : (preset == 60 ? ' (5 Thn)' : (preset == 12 ? ' (1 Thn)' : '')))"
                            ></button>
                        </template>
                    </div>
                </div>
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block">
                    * Maksimal perpanjangan masa simpan adalah <span x-text="getMaxMonths() + ' Bulan (' + (getMaxMonths() / 12) + ' Tahun)'"></span>. Penambahan dihitung dari akhir bulan expiry sebelumnya.
                </span>

                <!-- Live Estimasi Expiry Baru -->
                <div x-show="selectedArchives.length > 0 && additionalMonths" class="p-2.5 rounded bg-purple-500/10 border border-purple-500/30 text-[11px] text-purple-900 dark:text-purple-300 flex items-center justify-between gap-2 shadow-2xs font-mono">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400 shrink-0"></i>
                        <span>Estimasi Periode Akhir Baru: <strong class="font-bold underline text-purple-700 dark:text-purple-300" x-text="getExtendedPeriodEndFormatted(selectedArchives[0])"></strong> <span class="text-slate-500 text-[10px]" x-show="selectedArchives.length > 1" x-text="'(untuk ' + selectedArchives.length + ' box terpilih)'"></span></span>
                    </span>
                    <span class="px-2 py-0.5 rounded bg-purple-600 text-white font-bold text-[10px] shrink-0" x-text="'+' + additionalMonths + ' Bulan'"></span>
                </div>
            </div>

            <!-- Extension Reason -->
            <div class="space-y-1">
                <label for="extension_reason" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">
                    ALASAN PERPANJANGAN MASA SIMPAN DOKUMEN <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="extension_reason" 
                    id="extension_reason" 
                    rows="3" 
                    required 
                    placeholder="Jelaskan secara rinci alasan dokumen ini masih harus disimpan (misal: proses audit perpajakan, audit eksternal ISO/BPOM, proses legal berkas, atau kebutuhan referensi operasional)..." 
                    class="w-full px-2.5 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-purple-500 transition shadow-2xs leading-relaxed"
                >{{ old('extension_reason') }}</textarea>
                @error('extension_reason') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </fieldset>

        <!-- SECTION 3: UPLOAD SCAN FORMULIR PERPANJANGAN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3 font-mono">
            <legend class="px-2 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                3. Upload Scan Formulir Perpanjangan Masa Simpan (Opsional)
            </legend>

            <!-- Fast Print Action Banner -->
            <div class="p-2.5 rounded-lg bg-purple-50/80 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs font-mono">
                <div class="flex items-center gap-2 text-purple-800 dark:text-purple-300">
                    <i data-lucide="info" class="w-4 h-4 text-purple-600 shrink-0"></i>
                    <span>Belum memiliki lembar formulir fisik resmi untuk ditandatangani sebelum diunggah?</span>
                </div>
                <button 
                    type="button" 
                    @click="openFormPengajuanModal()" 
                    class="px-3 py-1 bg-white dark:bg-slate-900 hover:bg-purple-50 text-purple-700 dark:text-purple-300 font-bold text-xs rounded border border-purple-300 dark:border-purple-700 shadow-2xs flex items-center gap-1.5 transition cursor-pointer shrink-0"
                >
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-purple-600"></i>
                    <span>Cetak Form Perpanjangan</span>
                </button>
            </div>

            <div @file-change="hasScanFile = $event.detail.hasFile && !$event.detail.isOverLimit">
                <x-file-uploader 
                    name="scan_extension_form" 
                    id="scan_extension_form" 
                    label="Scan Formulir Perpanjangan Masa Simpan" 
                    :required="false" 
                    badge="OPSIONAL" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeKB="500"
                    helperText="Scan permohonan perpanjangan yang disetujui PIC / Manager (Format: PDF, JPG, PNG Maks. 500 KB)."
                />
            </div>
        </fieldset>

        <!-- DELPHI FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-2 font-mono">
            <!-- Left status hint -->
            <div class="text-xs">
                <span x-show="selectedArchives.length === 0" class="text-slate-500 font-bold">• Silakan pilih minimal 1 berkas arsip gudang di atas</span>
                <span x-show="selectedArchives.length > 0" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> 
                    <span x-text="selectedArchives.length + ' box berkas terpilih, siap diajukan perpanjangan'"></span>
                </span>
            </div>

            <!-- Right Buttons -->
            <div class="flex items-center gap-2">
                <a href="{{ route('destructions.index', array_merge(['view' => 'expiry'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="!canSubmit" 
                        :class="canSubmit ? 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black border-purple-700 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-not-allowed font-bold'"
                        class="px-5 py-2 text-xs rounded border transition flex items-center gap-1.5">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    <span x-show="selectedArchives.length <= 1">Ajukan Perpanjangan Masa Simpan</span>
                    <span x-show="selectedArchives.length > 1" x-text="'Ajukan Perpanjangan (' + selectedArchives.length + ' Box Arsip)'"></span>
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
function extendCreateApp() {
    return {
        selectedArchives: [],
        globalSearch: '',
        searchDropdownOpen: false,
        openAccordionIndex: null, // null = default semua menutup
        additionalMonths: {{ old('additional_months', 60) }},
        hasScanFile: false,
        archives: window.extendArchives || [],

        isFat() {
            @if(auth()->check() && (
                (auth()->user()->department && in_array(strtoupper(auth()->user()->department->code), ['FAT', 'FIN', 'FATCLM'])) ||
                str_contains(strtoupper(auth()->user()->name ?? ''), 'FAT') ||
                str_contains(strtoupper(auth()->user()->name ?? ''), 'FIN')
            ))
                return true;
            @else
                return this.selectedArchives.some(a => a.department && ['FAT', 'FIN', 'FATCLM'].includes(String(a.department.code).toUpperCase()));
            @endif
        },

        getMaxMonths() {
            return this.isFat() ? 120 : 60;
        },

        getPresetMonths() {
            return this.isFat() ? [6, 12, 24, 36, 60, 120] : [6, 12, 24, 36, 60];
        },

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
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        async removeArchive(index) {
            if (window.showConfirmModal) {
                const arc = this.selectedArchives[index];
                const ok = await window.showConfirmModal({
                    title: 'Hapus Box Perpanjangan',
                    message: `Apakah Anda yakin ingin membatalkan pilihan Box "${arc.box_number || arc.title}" dari daftar perpanjangan?`,
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
            return this.selectedArchives.length > 0;
        },

        // Form Pengajuan Dokumen (Perpanjangan) State
        formModalOpen: false,
        formPengajuanData: {
            no_form: '',
            tanggal_pengajuan: '{{ date("Y-m-d") }}',
            dept_display: @json(auth()->user()->department ? auth()->user()->department->name : ''),
            pic_pemohon: @json(auth()->user()->name ?? ''),
            tipe: 'Perpanjangan',
            masa_simpan: 60,
            catatan: 'Permohonan perpanjangan masa simpan dokumen arsip.',
            approval_pemohon: @json(auth()->user()->name ?? ''),
            approval_atasan: '',
            approval_pimpinan: '',
            approval_admin_gudang: 'Admin Gudang',
            approval_tgl: ''
        },
        formPengajuanRows: [],

        openFormPengajuanModal() {
            this.populateFormRows();
            this.formPengajuanData.tipe = 'Perpanjangan';
            this.formPengajuanData.catatan = 'Perpanjangan masa simpan (' + this.additionalMonths + ' Bulan). Alasan: ' + (document.getElementById('extension_reason')?.value || '');
            this.formPengajuanData.approval_pemohon = this.formPengajuanData.pic_pemohon;
            this.formModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        getExtendedPeriodEndFormatted(arc) {
            if (!arc) return '-';
            const monthsToAdd = parseInt(this.additionalMonths, 10) || 0;
            let baseYear = null;
            let baseMonth = null;

            // 1. Try parsing retention_expiry_date (e.g. "2026-10-31" or "2026-10-31T...")
            if (arc.retention_expiry_date && typeof arc.retention_expiry_date === 'string') {
                const match = arc.retention_expiry_date.match(/^(\d{4})-(\d{1,2})/);
                if (match) {
                    baseYear = parseInt(match[1], 10);
                    baseMonth = parseInt(match[2], 10);
                }
            }

            // 2. Try parsing period_end_date (e.g. "2026-10-31")
            if ((!baseYear || !baseMonth) && arc.period_end_date && typeof arc.period_end_date === 'string') {
                const match = arc.period_end_date.match(/^(\d{4})-(\d{1,2})/);
                if (match) {
                    baseYear = parseInt(match[1], 10);
                    baseMonth = parseInt(match[2], 10);
                }
            }

            // 3. Try parsing formatted_expiry_date (e.g. "Oct 2026" or "10-2026")
            if ((!baseYear || !baseMonth) && arc.formatted_expiry_date && typeof arc.formatted_expiry_date === 'string') {
                const mYMatch = arc.formatted_expiry_date.match(/^([A-Za-z]+)\s+(\d{4})$/);
                if (mYMatch) {
                    const monthNames = {
                        jan: 1, feb: 2, mar: 3, apr: 4, may: 5, mei: 5, jun: 6,
                        jul: 7, aug: 8, agu: 8, ags: 8, sep: 9, oct: 10, okt: 10, nov: 11, dec: 12, des: 12
                    };
                    const mKey = mYMatch[1].toLowerCase().slice(0, 3);
                    if (monthNames[mKey]) {
                        baseMonth = monthNames[mKey];
                        baseYear = parseInt(mYMatch[2], 10);
                    }
                }
            }

            // 4. Fallback to current date
            if (!baseYear || !baseMonth) {
                const now = new Date();
                baseYear = now.getFullYear();
                baseMonth = now.getMonth() + 1;
            }

            // Target extended date (month is 0-indexed in JS Date)
            const targetDate = new Date(baseYear, (baseMonth - 1) + monthsToAdd, 1);
            const endM = String(targetDate.getMonth() + 1).padStart(2, '0');
            const endY = targetDate.getFullYear();
            return `${endM}-${endY}`;
        },

        populateFormRows() {
            const rows = [];
            const padTo = 12;

            this.selectedArchives.forEach((arc, idx) => {
                const rackLoc = arc.rack_label || (arc.short_location && arc.short_location !== 'Belum Ada Lokasi' ? arc.short_location : '') || '';
                const boxLabel = rackLoc || arc.box_number || arc.archive_code || '';

                rows.push({
                    no: idx + 1,
                    title: arc.title || ('Box ' + (arc.box_number || (idx + 1))),
                    masa_simpan: this.additionalMonths ? (this.additionalMonths + ' Bln') : (this.getMaxMonths() + ' Bln'),
                    period_end: this.getExtendedPeriodEndFormatted(arc),
                    label_kardus: boxLabel,
                    label_box: boxLabel
                });
            });

            const currentCount = rows.length;
            for (let i = currentCount + 1; i <= Math.max(padTo, currentCount); i++) {
                rows.push({
                    no: i,
                    title: '',
                    masa_simpan: '',
                    period_end: '',
                    label_box: ''
                });
            }

            this.formPengajuanRows = rows;
        },

        printFormPengajuan() {
            window.print();
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
