@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Permintaan Penarikan Arsip - DMS PT Indraco')

@section('content')
@php
    $from = $from ?? request('from', 'borrowings');
    $backRoute = ($from === 'archives') ? 'archives.index' : 'borrowings.index';
@endphp
<div class="max-w-5xl mx-auto space-y-3 font-sans pb-10" x-data="borrowingCreateApp()" @keydown.escape.window="if(!formModalOpen) window.location.href = '{{ route($backRoute, request()->has('embed') ? ['embed' => 1] : []) }}'">

    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="p-2 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded shrink-0">
                <i data-lucide="file-symlink" class="w-4 h-4"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate">Formulir Permintaan Penarikan Berkas Arsip [Form 2]</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">Pengajuan Penarikan Dokumen Arsip Tersimpan • Dukungan Multi-Box Penarikan • Wajib Dokumen Persetujuan (Approval)</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-end shrink-0">
            <!-- Counter Chip -->
            <div class="px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/30 rounded text-xs font-mono font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5 whitespace-nowrap shrink-0">
                <i data-lucide="boxes" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                <span x-text="selectedBoxesCount + ' Box Dipilih'"></span>
            </div>

            <!-- Tombol Cetak Form Pengajuan Penarikan -->
            <button 
                type="button" 
                @click="openFormPengajuanModal()" 
                class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm whitespace-nowrap shrink-0 cursor-pointer"
                title="Buka Pratinjau & Cetak Form Pengajuan Dokumen Penarikan (Standar PDF/A4)"
            >
                <i data-lucide="printer" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                <span>Form Penarikan</span>
            </button>

            <a href="{{ route($backRoute, request()->has('embed') ? ['embed' => 1] : []) }}" 
               onclick="if (window.history.length > 1 && document.referrer && document.referrer !== window.location.href) { window.history.back(); return false; }"
               class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm whitespace-nowrap shrink-0 cursor-pointer">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                <span>Kembali (Esc)</span>
            </a>
        </div>
    </div>

    <!-- Pass archives data safely via JavaScript -->
    <script>
        window.borrowingArchives = @json($archives);
    </script>

    <!-- Scope Security Alert (Jika PIC Dept) -->
    @if(auth()->user()->isPicDept())
    <div class="p-2.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs font-mono flex items-center gap-2.5 shadow-2xs">
        <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0"></i>
        <div>
            <strong class="uppercase font-bold">Filter Keamanan Departemen Terkunci:</strong>
            Menampilkan berkas arsip milik <span class="font-bold underline">{{ auth()->user()->department->name ?? 'Departemen Anda' }} ({{ auth()->user()->department->code ?? 'DEPT' }})</span> dengan status tersimpan di gudang.
        </div>
    </div>
    @endif

    {{-- Validation Errors Alert Card --}}
    @if(isset($errors) && $errors->any())
    <div class="p-3 rounded bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs font-mono space-y-1 shadow-sm">
        <div class="font-bold flex items-center gap-1.5 text-rose-700 dark:text-rose-300">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0"></i>
            <span>Pengajuan Penarikan Gagal Diproses. Silakan periksa isian formulir:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-600 dark:text-rose-400 pl-4">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- MAIN FORM WINDOW CARD -->
    <form @submit="clearDraft()" action="{{ route('borrowings.store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif
        <input type="hidden" name="from" value="{{ $from }}">

        <!-- Hidden Archive IDs (Multi-Box Support & Single Fallback) -->
        <template x-for="arc in selectedArchives" :key="arc.id">
            <input type="hidden" name="archive_ids[]" :value="arc.id">
        </template>
        <input type="hidden" name="archive_id" :value="selectedArchives[0]?.id || ''">
        <input type="hidden" name="is_permanent" value="1">

        <!-- SECTION 1: DAFTAR TARGET PENARIKAN ARSIP (SEARCH & ACCORDION LIST) -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl p-3.5 shadow-sm space-y-3 font-mono">
            
            <!-- Section Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            1. Daftar Box Target Penarikan Arsip
                        </h2>
                        <p class="text-[10px] text-slate-500 font-sans">
                            Cari dan tambahkan box arsip yang ingin ditarik. Klik baris box untuk membuka rincian dokumen (buka bergantian).
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
                            placeholder="Ketik Nomor Box (misal: IND/DM/..., BOX-...) atau Judul Berkas arsip..." 
                            class="w-full pl-8 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition shadow-2xs"
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
                            <div @click="!isAlreadySelected(arc.id) && !arc.has_active_borrowing && addArchiveToSelection(arc)" 
                                 class="p-2.5 transition flex items-center justify-between gap-3"
                                 :class="arc.has_active_borrowing 
                                    ? 'opacity-40 bg-purple-50 dark:bg-purple-950/40 cursor-not-allowed select-none' 
                                    : (isAlreadySelected(arc.id) 
                                        ? 'opacity-40 bg-slate-50 dark:bg-slate-950 cursor-not-allowed' 
                                        : 'hover:bg-emerald-500/10 dark:hover:bg-emerald-500/20 cursor-pointer')">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-1.5 py-0.2 rounded border border-amber-500/20 text-[10px]" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.2 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                        <span x-show="arc.has_active_borrowing" class="text-[9px] font-bold text-purple-700 dark:text-purple-300 bg-purple-500/20 px-1.5 py-0.2 rounded border border-purple-500/40 flex items-center gap-1">
                                            <i data-lucide="clock" class="w-2.5 h-2.5"></i> Sedang Diajukan Penarikan
                                        </span>
                                        <span x-show="!arc.has_active_borrowing && arc.has_rejected_borrowing" class="text-[9px] font-bold text-rose-700 dark:text-rose-300 bg-rose-500/15 px-1.5 py-0.2 rounded border border-rose-500/30 flex items-center gap-1">
                                            <i data-lucide="rotate-ccw" class="w-2.5 h-2.5 text-rose-500"></i> Pernah Ditolak
                                        </span>
                                        <span x-show="!arc.has_active_borrowing && isAlreadySelected(arc.id)" class="text-[9px] font-bold text-emerald-600 bg-emerald-500/10 px-1.5 py-0.2 rounded border border-emerald-500/20 flex items-center gap-1">
                                            <i data-lucide="check" class="w-2.5 h-2.5"></i> Sudah di List
                                        </span>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="arc.title"></h4>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400" x-text="arc.period_text || arc.period_start_date"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        <span x-text="arc.short_location || arc.full_slot_location || 'Gudang'"></span>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty Search Result State -->
                    <div x-show="searchDropdownOpen && filteredArchives.length === 0" x-cloak class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xl p-3 text-center text-slate-500 text-xs font-mono z-50">
                        Tidak ada berkas arsip yang cocok dengan pencarian di departemen ini.
                    </div>
                </div>
            </div>

            <!-- LIST DAFTAR BOX TERPILIH (ACCORDION BERGANTIAN - DEFAULT SEMUA MENUTUP) -->
            <div class="space-y-2 pt-1">
                <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 dark:text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="list" class="w-3.5 h-3.5 text-emerald-500"></i>
                        <span>Daftar Box Terpilih (<span x-text="selectedArchives.length"></span> Box)</span>
                    </span>
                    <span class="text-[10px] text-slate-400 italic">Klik baris box untuk melihat rincian dokumen (terbuka satu per satu)</span>
                </div>

                <!-- Empty State -->
                <div x-show="selectedArchives.length === 0" class="p-6 text-center rounded-lg border border-dashed border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40 font-mono text-xs space-y-1.5">
                    <i data-lucide="package-open" class="w-8 h-8 text-slate-400 mx-auto"></i>
                    <p class="font-bold text-slate-700 dark:text-slate-300">Belum Ada Box Arsip yang Dipilih</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Gunakan kolom pencarian di atas untuk menambahkan box target penarikan.</p>
                </div>

                <!-- Accordion Items -->
                <template x-for="(arc, aIdx) in selectedArchives" :key="arc.id">
                    <div class="rounded-lg border transition duration-150 shadow-2xs overflow-hidden"
                         :class="openAccordionIndex === aIdx ? 'border-emerald-500 bg-white dark:bg-slate-900 ring-1 ring-emerald-500/20' : 'border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 hover:border-slate-400 dark:hover:border-slate-700'">
                        
                        <!-- Accordion Header Row: Judul, Lokasi, Periode, No Box Arsip -->
                        <div class="p-2.5 sm:p-3 flex items-center justify-between gap-3 cursor-pointer select-none"
                             @click="toggleAccordion(aIdx)">
                            
                            <div class="flex flex-wrap items-center gap-2 min-w-0 flex-1">
                                <!-- Box Number Badge -->
                                <span class="px-2 py-0.5 rounded text-[10px] font-black font-mono shrink-0"
                                      :class="openAccordionIndex === aIdx ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                      x-text="aIdx + 1"></span>

                                <!-- Judul Berkas -->
                                <span class="font-bold text-xs text-slate-900 dark:text-white truncate max-w-[200px] sm:max-w-[260px] md:max-w-[320px]" 
                                      :title="arc.title"
                                      x-text="arc.title"></span>

                                <!-- No Box Arsip -->
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 shrink-0"
                                      :title="'Nomor Box: ' + (arc.box_number || '-')"
                                      x-text="arc.box_number || 'PENOMORAN PENDING'"></span>

                                <!-- Lokasi Rak Gudang -->
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1 shrink-0"
                                      :title="'Lokasi: ' + (arc.short_location || arc.full_slot_location || 'Gudang')">
                                    <i data-lucide="map-pin" class="w-3 h-3 text-emerald-500"></i>
                                    <span x-text="arc.short_location || arc.full_slot_location || 'Gudang'"></span>
                                </span>

                                <!-- Periode Dokumen -->
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
                                        title="Hapus box ini dari daftar penarikan">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <span class="p-1 rounded text-slate-400 transition-transform duration-200"
                                      :class="{ 'rotate-180 text-emerald-600 dark:text-emerald-400': openAccordionIndex === aIdx }">
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Accordion Content: Document List & Tata Rapi -->
                        <div x-show="openAccordionIndex === aIdx" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 space-y-3 font-mono">
                            
                            <!-- Alert Catatan Penolakan Pengajuan Sebelumnya -->
                            <template x-if="arc.has_rejected_borrowing && arc.rejected_note">
                                <div class="p-2.5 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs font-mono flex items-start gap-2 shadow-2xs">
                                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500 shrink-0 mt-0.5"></i>
                                    <div>
                                        <strong class="font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300 block">Catatan Penolakan Pengajuan Sebelumnya:</strong>
                                        <span class="text-[11px] text-slate-700 dark:text-slate-300" x-text="arc.rejected_note"></span>
                                        <p class="text-[10px] text-rose-600 dark:text-rose-400 mt-1 italic font-sans">*Pastikan surat permohonan dan kelengkapan dokumen telah diperbaiki sebelum mengirimkan ulang pengajuan ini.</p>
                                    </div>
                                </div>
                            </template>

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
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase">PERIODE DOKUMEN</span>
                                    <span class="font-bold text-amber-600 dark:text-amber-400 truncate block text-[11px]" x-text="arc.period_text || arc.periode_doc || '-'"></span>
                                </div>
                                <div class="p-2 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase">KONDISI FISIK</span>
                                    <span class="font-bold text-slate-800 dark:text-white truncate block text-[11px]" x-text="arc.physical_condition || 'Baik'"></span>
                                </div>
                            </div>

                            <!-- Document List Table (Ditata Rapi) -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-500"></i>
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

        <!-- SECTION 2: MAKSUD / ALASAN PENARIKAN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-amber-500"></i>
                2. Maksud & Alasan Keperluan Penarikan Berkas
            </legend>

            <div>
                <label for="purpose" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                    ALASAN LENGKAP PENARIKAN <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="purpose" 
                    id="purpose" 
                    x-model="purposeText"
                    rows="3" 
                    required 
                    placeholder="Contoh: Diperlukan untuk keperluan verifikasi audit internal perpajakan tahunan..." 
                    class="w-full px-2.5 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition shadow-2xs leading-relaxed"
                >{{ old('purpose') }}</textarea>
                @error('purpose') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </fieldset>

        <!-- SECTION 3: UPLOAD DOKUMEN PERSETUJUAN (APPROVAL) -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-purple-500"></i>
                3. Upload Dokumen Persetujuan (Approval) Penarikan
            </legend>

            <!-- Fast Print Action Banner -->
            <div class="p-2.5 rounded-lg bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs font-mono">
                <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                    <i data-lucide="info" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span>Belum memiliki lembar formulir fisik resmi untuk ditandatangani sebelum diunggah?</span>
                </div>
                <button 
                    type="button" 
                    @click="openFormPengajuanModal()" 
                    class="px-3 py-1 bg-white dark:bg-slate-900 hover:bg-emerald-50 text-emerald-700 dark:text-emerald-300 font-bold text-xs rounded border border-emerald-300 dark:border-emerald-700 shadow-2xs flex items-center gap-1.5 transition cursor-pointer shrink-0"
                >
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Cetak Form Penarikan</span>
                </button>
            </div>

            <div @file-change="hasApprovalFile = $event.detail.hasFile && !$event.detail.isOverLimit">
                <x-file-uploader 
                    name="approval_file" 
                    id="approval_file" 
                    label="Berkas Dokumen Persetujuan (Approval)" 
                    :required="true" 
                    badge="WAJIB UNTUK PENARIKAN" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeKB="500"
                    :maxSizeMB="0.5"
                    helperText="Wajib melampirkan berkas scan approval penarikan bertandatangan PIC/Manager (Format: PDF, JPG, PNG Maks. 500 KB)."
                />
                @error('approval_file') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </fieldset>

        <!-- DELPHI FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-2 font-mono">
            <!-- Left status hint -->
            <div class="text-xs">
                <span x-show="selectedArchives.length === 0" class="text-slate-500 font-bold">• Silakan pilih minimal 1 berkas arsip gudang di atas</span>
                <span x-show="selectedArchives.length > 0 && !hasApprovalFile" class="text-rose-500 font-bold">• Unggah berkas dokumen approval untuk mengaktifkan tombol pengajuan</span>
                <span x-show="canSubmit" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> 
                    <span x-text="selectedArchives.length + ' box berkas & approval lengkap, siap diajukan'"></span>
                </span>
            </div>

            <!-- Right Buttons -->
            <div class="flex items-center gap-2">
                <a href="{{ route($backRoute, request()->has('embed') ? ['embed' => 1] : []) }}" 
                   onclick="if (window.history.length > 1 && document.referrer && document.referrer !== window.location.href) { window.history.back(); return false; }"
                   class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="!canSubmit" 
                        :class="canSubmit ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-black border-emerald-700 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-not-allowed font-bold'"
                        class="px-5 py-2 text-xs rounded border transition flex items-center gap-1.5">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span x-show="selectedArchives.length <= 1">Ajukan Penarikan Arsip</span>
                    <span x-show="selectedArchives.length > 1" x-text="'Ajukan Penarikan (' + selectedArchives.length + ' Box Arsip)'"></span>
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
function borrowingCreateApp() {
    return {
        selectedArchives: [],
        globalSearch: '',
        purposeText: @json(old('purpose', '')),
        searchDropdownOpen: false,
        openAccordionIndex: null, // null = default semua menutup
        hasApprovalFile: false,
        archives: window.borrowingArchives || [],

        saveDraft() {
            if (window.formSubmitted) return;
            try {
                if (this.selectedArchives.length === 0 && !this.purposeText.trim()) return;
                const draft = {
                    userId: {{ auth()->id() ?? 0 }},
                    archiveIds: this.selectedArchives.map(a => a.id),
                    purpose: this.purposeText
                };
                localStorage.setItem('dms_draft_borrowing_create', JSON.stringify(draft));
            } catch(e) {}
        },

        clearDraft() {
            try {
                window.formSubmitted = true;
                localStorage.removeItem('dms_draft_borrowing_create');
            } catch(e) {}
        },

        init() {
            const initialIds = @json($selectedArchiveIds ?? ($selectedArchiveId ? [(int)$selectedArchiveId] : []));
            if (Array.isArray(initialIds) && initialIds.length > 0) {
                initialIds.forEach(id => {
                    const found = this.archives.find(a => a.id == id);
                    if (found && !found.has_active_borrowing) {
                        this.selectedArchives.push(found);
                    }
                });
            } else {
                // Restore draft selection from localStorage across refresh
                try {
                    const raw = localStorage.getItem('dms_draft_borrowing_create');
                    if (raw) {
                        const draft = JSON.parse(raw);
                        if (draft && Number(draft.userId) === Number({{ auth()->id() ?? 0 }})) {
                            if (Array.isArray(draft.archiveIds)) {
                                draft.archiveIds.forEach(id => {
                                    const found = this.archives.find(a => a.id == id);
                                    if (found && !found.has_active_borrowing && !this.selectedArchives.some(x => x.id == id)) {
                                        this.selectedArchives.push(found);
                                    }
                                });
                            }
                            if (draft.purpose && !this.purposeText) {
                                this.purposeText = draft.purpose;
                            }
                        }
                    }
                } catch(e) {}
            }

            this.$watch('selectedArchives', () => this.saveDraft());
            this.$watch('purposeText', () => this.saveDraft());
            window.addEventListener('beforeunload', () => {
                if (!window.formSubmitted) this.saveDraft();
            });

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
            if (this.isAlreadySelected(arc.id) || arc.has_active_borrowing) return;
            this.selectedArchives.push(arc);
            this.globalSearch = '';
            this.searchDropdownOpen = false;
            // Default tetap menutup sesuai instruksi user
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        async removeArchive(index) {
            if (window.showConfirmModal) {
                const arc = this.selectedArchives[index];
                const ok = await window.showConfirmModal({
                    title: 'Hapus Box Penarikan',
                    message: `Apakah Anda yakin ingin membatalkan pilihan Box "${arc.box_number || arc.title}" dari daftar penarikan?`,
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

        // Form Pengajuan Dokumen (Penarikan) State
        formModalOpen: false,
        formPengajuanData: {
            no_form: '',
            tanggal_pengajuan: '{{ date("Y-m-d") }}',
            dept_display: @json(auth()->user()->department ? auth()->user()->department->name : ''),
            pic_pemohon: @json(auth()->user()->name ?? ''),
            tipe: 'Penarikan',
            masa_simpan: 1,
            catatan: 'Penarikan fisik box dan dokumen arsip untuk keperluan unit kerja.',
            approval_pemohon: @json(auth()->user()->name ?? ''),
            approval_atasan: '',
            approval_pimpinan: '',
            approval_admin_gudang: 'Admin Gudang',
            approval_tgl: ''
        },
        formPengajuanRows: [],

        openFormPengajuanModal() {
            if (!this.formPengajuanData.approval_pemohon) {
                this.formPengajuanData.approval_pemohon = this.formPengajuanData.pic_pemohon || @json(auth()->user()->name ?? '');
            }
            if (!this.formPengajuanData.pic_pemohon) {
                this.formPengajuanData.pic_pemohon = this.formPengajuanData.approval_pemohon || @json(auth()->user()->name ?? '');
            }

            const selected = this.selectedArchives;
            if (selected.length > 0) {
                const dept = selected[0].department;
                const deptName = dept ? (dept.name || dept.code) : (@json(auth()->user()->department ? auth()->user()->department->name : ''));

                const distinctSubDepts = new Set();
                selected.forEach(arc => {
                    const subId = arc.sub_department_id || (arc.sub_department ? arc.sub_department.id : '');
                    distinctSubDepts.add(String(subId || ''));
                });

                if (distinctSubDepts.size === 1) {
                    const singleSub = selected[0].sub_department;
                    if (singleSub && singleSub.name) {
                        this.formPengajuanData.dept_display = `${deptName} / ${singleSub.name}`;
                    } else {
                        this.formPengajuanData.dept_display = deptName;
                    }
                } else {
                    // Multi sub-departemen terdeteksi: Cukup nama departemen saja
                    this.formPengajuanData.dept_display = deptName;
                }
            } else if (!this.formPengajuanData.dept_display) {
                this.formPengajuanData.dept_display = @json(auth()->user()->department ? auth()->user()->department->name : '');
            }

            const purposeEl = document.getElementById('purpose');
            if (purposeEl && purposeEl.value && purposeEl.value.trim()) {
                this.formPengajuanData.catatan = purposeEl.value.trim();
            }

            this.populateFormRows();
            this.formModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        populateFormRows() {
            const rows = [];
            const selected = this.selectedArchives;
            const count = Math.max(10, selected.length);

            for (let i = 0; i < count; i++) {
                if (i < selected.length) {
                    const arc = selected[i];
                    let end = '';
                    if (arc.period_end_date) {
                        const parts = arc.period_end_date.split('-');
                        end = parts.length >= 2 ? `${parts[1]}-${parts[0]}` : arc.period_end_date;
                    } else if (arc.period_text) {
                        end = arc.period_text;
                    }

                    let masaSimpan = '-';
                    if (arc.periode) {
                        const m = arc.periode.match(/\d+/);
                        masaSimpan = m ? m[0] : arc.periode;
                    }

                    const rackLoc = arc.rack_label || (arc.short_location && arc.short_location !== 'Belum Ada Lokasi' ? arc.short_location : '') || '';

                    const subDeptName = arc.sub_department ? (arc.sub_department.code || arc.sub_department.name) : (arc.sub_department_id ? 'Inti Dept' : '-');

                    rows.push({
                        no: i + 1,
                        title: arc.title || '',
                        sub_dept: subDeptName,
                        period_end: end,
                        masa_simpan: masaSimpan,
                        label_kardus: rackLoc || arc.box_number || arc.archive_code || '',
                        label_box: rackLoc || arc.box_number || arc.archive_code || ''
                    });
                } else {
                    rows.push({
                        no: i + 1,
                        title: '',
                        sub_dept: '',
                        period_end: '',
                        masa_simpan: '',
                        label_kardus: '',
                        label_box: ''
                    });
                }
            }
            this.formPengajuanRows = rows;
        },

        getDeptSubDeptDisplay() {
            return this.formPengajuanData.dept_display || '';
        },

        formatDateDisplay(dateStr) {
            if (!dateStr) return '';
            if (typeof dateStr !== 'string') return '';
            try {
                const parts = dateStr.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
            } catch (e) {}
            return dateStr;
        },

        printFormPengajuan() {
            if (window.dmsPrint) {
                window.dmsPrint('form-pengajuan-print-document');
            } else {
                window.print();
            }
        }
    };
}
</script>
@endpush
