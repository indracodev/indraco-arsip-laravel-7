@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Katalog & Booking Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="{ 
    selected: [], 
    selectAll: false, 
    allIds: [{{ $archives->pluck('id')->join(',') }}],
    toggleAll() { 
        this.selected = this.selectAll ? [...this.allIds] : []; 
    } 
}">
    <!-- DATA SUMMARY STATUS STRIP (Delphi TPanel Record Counter - No Duplicate Buttons) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[6px] shadow-xs flex items-center justify-between gap-[8px] font-mono text-[11px]">
        <div class="flex items-center gap-[8px]">
            <span class="flex items-center gap-[6px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                <i data-lucide="folder-archive" class="w-[14px] h-[14px] text-amber-500"></i>
                Katalog Data Arsip
            </span>
            <span class="text-slate-400">|</span>
            <span class="text-slate-500 dark:text-slate-400">Total Berkas: <strong class="text-amber-600 dark:text-amber-400 font-extrabold">{{ $archives->total() }}</strong></span>
            @if(request()->hasAny(['search', 'department_id', 'status', 'expiry_filter']))
            <span class="px-[6px] py-[1px] bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-[3px] border border-amber-500/30 text-[10px] font-bold">Filter Aktif</span>
            @endif
        </div>
        <div class="text-[10px] text-slate-400 font-mono flex items-center gap-[8px]">
            <span>Pencarian & Manajemen Arsip Dokumen</span>
        </div>
    </div>

    <!-- DELPHI GROUPBOX FILTER PANEL (TGroupBox Delphi Desktop Style) -->
    <fieldset class="border border-slate-300 dark:border-slate-800 p-[10px] rounded-[4px] bg-white dark:bg-slate-950 font-mono text-[11px] shadow-2xs" x-data="{ submitting: false }">
        <legend class="px-[8px] font-mono text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-[3px] shadow-2xs flex items-center gap-[4px]">
            <i data-lucide="filter" class="w-[12px] h-[12px] text-amber-500"></i>
            Filter & Pencarian Data Katalog
        </legend>

        <form action="{{ route('archives.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-[8px] pt-[4px]" @submit="submitting = true">
            <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
            <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

            <!-- Search Keyword -->
            <div class="space-y-[4px]">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block flex items-center justify-between">
                    <span>CARI KEYWORD</span>
                    <span class="text-amber-600 dark:text-amber-400">[Ctrl+F]</span>
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Judul, No. Box, Dokumen..." 
                        class="w-full pl-[26px] pr-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[4px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                    >
                    <i data-lucide="search" class="w-[12px] h-[12px] text-slate-400 absolute left-[8px] top-[8px]"></i>
                </div>
            </div>

            <!-- Department Filter -->
            <div class="space-y-[4px]">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">DEPARTEMEN</label>
                <select name="department_id" class="w-full h-[28px] px-[8px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[4px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Dept --</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->code }} - {{ $dept->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Document Type Filter -->
            <div class="space-y-[4px]">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">JENIS DOKUMEN</label>
                <select name="document_type" class="w-full h-[28px] px-[8px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[4px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Dokumen --</option>
                    @if(isset($documentTypes) && count($documentTypes) > 0)
                        @foreach($documentTypes as $doc)
                            <option value="{{ $doc->code }}" {{ request('document_type') == $doc->code ? 'selected' : '' }}>
                                {{ $doc->code }} ({{ $doc->name }})
                            </option>
                        @endforeach
                    @else
                        <option value="PR" {{ request('document_type') == 'PR' ? 'selected' : '' }}>PR (Purchase Requisition)</option>
                        <option value="PO" {{ request('document_type') == 'PO' ? 'selected' : '' }}>PO (Purchase Order)</option>
                        <option value="SURAT JALAN" {{ request('document_type') == 'SURAT JALAN' ? 'selected' : '' }}>Surat Jalan (DO)</option>
                        <option value="FAKTUR" {{ request('document_type') == 'FAKTUR' ? 'selected' : '' }}>Faktur / Invoice</option>
                        <option value="FAKTUR PAJAK" {{ request('document_type') == 'FAKTUR PAJAK' ? 'selected' : '' }}>Faktur Pajak</option>
                        <option value="ABSENSI" {{ request('document_type') == 'ABSENSI' ? 'selected' : '' }}>Absensi / Payroll</option>
                        <option value="KONTRAK" {{ request('document_type') == 'KONTRAK' ? 'selected' : '' }}>Kontrak / SPK</option>
                        <option value="UTILITY" {{ request('document_type') == 'UTILITY' ? 'selected' : '' }}>Utility / Bukti Bayar</option>
                        <option value="DATA SAMPLE" {{ request('document_type') == 'DATA SAMPLE' ? 'selected' : '' }}>Data Sample</option>
                        <option value="LAINNYA" {{ request('document_type') == 'LAINNYA' ? 'selected' : '' }}>Lainnya</option>
                    @endif
                </select>
            </div>

            <!-- Status Filter -->
            <div class="space-y-[4px]">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">STATUS WORKFLOW</label>
                <select name="status" class="w-full h-[28px] px-[8px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[4px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Status --</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (Revisi)</option>
                    <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Antrean Verifikasi</option>
                    <option value="approved_booked" {{ request('status') == 'approved_booked' ? 'selected' : '' }}>Approved / Booked</option>
                    <option value="in_warehouse" {{ request('status') == 'in_warehouse' ? 'selected' : '' }}>Di Gudang</option>
                    <option value="borrowed" {{ request('status') == 'borrowed' ? 'selected' : '' }}>Sedang Dipinjam</option>
                    <option value="destroyed" {{ request('status') == 'destroyed' ? 'selected' : '' }}>Dimusnahkan</option>
                </select>
            </div>

            <!-- Expiry Alert Filter -->
            <div class="space-y-[4px]">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">EXPIRY MASA SIMPAN</label>
                <select name="expiry_filter" class="w-full h-[28px] px-[8px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[4px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Expiry --</option>
                    <option value="expiring_soon" {{ request('expiry_filter') == 'expiring_soon' ? 'selected' : '' }}>Mendekati Expiry (&le; 90 Hari)</option>
                    <option value="expired" {{ request('expiry_filter') == 'expired' ? 'selected' : '' }}>Sudah Kadaluarsa</option>
                </select>
            </div>

            <!-- Submit & Reset Filter Buttons -->
            <div class="space-y-[4px] flex items-end gap-[6px]">
                <button type="submit" :disabled="submitting" class="flex-1 h-[28px] px-[10px] bg-slate-800 hover:bg-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded-[4px] text-[11px] font-mono font-bold border border-slate-700 transition flex items-center justify-center gap-[6px] disabled:opacity-50 shadow-2xs">
                    <i data-lucide="loader-2" class="w-[12px] h-[12px] animate-spin" x-show="submitting"></i>
                    <i data-lucide="filter" class="w-[12px] h-[12px] text-amber-400" x-show="!submitting"></i>
                    <span x-text="submitting ? 'Memuat...' : 'Filter'"></span>
                </button>

                @if(request()->hasAny(['search', 'department_id', 'document_type', 'status', 'expiry_filter']))
                <a href="{{ route('archives.index') }}" class="h-[28px] px-[8px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-[4px] text-[11px] font-mono font-bold border border-slate-300 dark:border-slate-700 transition flex items-center justify-center shadow-2xs" title="Reset Filter">
                    <i data-lucide="x" class="w-[12px] h-[12px]"></i>
                </a>
                @endif
            </div>
        </form>
    </fieldset>

    <!-- BATCH ACTIONS RIBBON (When items checked) -->
    <div x-show="selected.length > 0" x-transition class="bg-amber-500/10 border border-amber-500/40 rounded-[4px] px-[10px] py-[6px] flex items-center justify-between gap-[8px] font-mono text-[11px] shadow-2xs">
        <div class="flex items-center gap-[6px]">
            <span class="p-[3px] bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-[3px]">
                <i data-lucide="check-square" class="w-[13px] h-[13px]"></i>
            </span>
            <span class="text-slate-800 dark:text-slate-200">
                Terpilih: <strong class="text-amber-600 dark:text-amber-400 font-extrabold" x-text="selected.length"></strong> berkas arsip untuk pencetakan label box.
            </span>
        </div>

        <form action="{{ route('archives.print_labels') }}" method="GET" target="_blank" class="inline">
            <input type="hidden" name="ids" :value="selected.join(',')">
            <button type="submit" class="px-[10px] py-[3px] bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-[11px] rounded-[3px] border border-amber-600 shadow-2xs transition flex items-center gap-[6px]">
                <i data-lucide="printer" class="w-[12px] h-[12px] text-slate-950"></i>
                <span>Cetak Label Terpilih (<span x-text="selected.length"></span>)</span>
            </button>
        </form>
    </div>

    <!-- DELPHI TDBGRID SPREADSHEET TABLE CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] shadow-xs relative overflow-hidden font-sans">
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                        <th class="py-[6px] px-[10px] w-[36px] text-center border-r border-slate-300 dark:border-slate-700">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded-[3px] border-slate-400 dark:border-slate-700 text-amber-500 focus:ring-0">
                        </th>
                        @php
                            $curSort = request('sort', 'created_at');
                            $curDir = request('direction', 'desc');
                            $nextDir = $curDir === 'asc' ? 'desc' : 'asc';
                        @endphp
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'box_number', 'direction' => $curSort === 'box_number' ? $nextDir : 'asc']) }}" class="flex items-center gap-[4px] hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>NO. BOX ARSIP</span>
                                @if($curSort === 'box_number')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'title', 'direction' => $curSort === 'title' ? $nextDir : 'asc']) }}" class="flex items-center gap-[4px] hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>JUDUL BERKAS & DEPT</span>
                                @if($curSort === 'title')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">PERIODE</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">KONDISI FISIK</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">LOKASI RAK GUDANG</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'retention_expiry_date', 'direction' => $curSort === 'retention_expiry_date' ? $nextDir : 'asc']) }}" class="flex items-center gap-[4px] hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>MASA SIMPAN (EXPIRY)</span>
                                @if($curSort === 'retention_expiry_date')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $curSort === 'status' ? $nextDir : 'asc']) }}" class="flex items-center gap-[4px] hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>STATUS</span>
                                @if($curSort === 'status')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-[6px] px-[10px] text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-[11px]">
                    @forelse($archives as $archive)
                    <tr class="hover:bg-amber-500/5 dark:hover:bg-amber-500/10 transition">
                        <td class="py-[6px] px-[10px] text-center border-r border-slate-200 dark:border-slate-800">
                            <input type="checkbox" :value="{{ $archive->id }}" x-model="selected" class="rounded-[3px] border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-0">
                        </td>

                        <td class="py-[6px] px-[10px] font-mono text-amber-700 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->box_number)
                                <div class="flex items-center gap-[4px]">
                                    <i data-lucide="qr-code" class="w-[13px] h-[13px] text-amber-500"></i>
                                    <span>{{ $archive->box_number }}</span>
                                </div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic font-normal text-[11px]">- Belum ada box code -</span>
                            @endif
                        </td>

                        <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800">
                            <div class="space-y-[2px]">
                                <a href="{{ route('archives.show', $archive) }}" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block font-mono">
                                    {{ $archive->title }}
                                </a>
                                <div class="flex flex-wrap items-center gap-[6px] text-[11px] text-slate-500 font-mono">
                                    <span class="px-[6px] py-[1px] rounded-[3px] bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold" title="{{ $archive->department->name ?? 'General' }}">
                                        {{ $archive->department->code ?? 'GEN' }}
                                        @if($archive->subDepartment)
                                            <span class="text-amber-600 dark:text-amber-400 font-normal">/ {{ $archive->subDepartment->name }}</span>
                                        @endif
                                    </span>
                                    @if($archive->company_name)
                                        <span class="px-[5px] py-[1px] rounded-[3px] bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 text-blue-700 dark:text-blue-300 text-[10px]">
                                            {{ $archive->company_name }}
                                        </span>
                                    @endif
                                    <span>by {{ $archive->creator->name ?? 'User' }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-[3px] pt-[2px]">
                                    @foreach($archive->document_types as $docType)
                                        <span class="px-[4px] py-[0.5px] rounded-[2px] bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 font-bold text-[9px] font-mono">
                                            {{ $docType }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </td>

                        <td class="py-[6px] px-[10px] font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->period_text ?? $archive->period_start_date->format('M Y') }}
                        </td>

                        <td class="py-[6px] px-[10px] font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->physical_condition }}
                        </td>

                        <td class="py-[6px] px-[10px] font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->location)
                                <div class="flex items-center gap-[4px] text-emerald-700 dark:text-emerald-400 font-bold">
                                    <i data-lucide="map-pin" class="w-[13px] h-[13px] text-emerald-500"></i>
                                    <span>{{ $archive->location->full_location }}</span>
                                </div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic font-normal">Belum Check-in</span>
                            @endif
                        </td>

                        <td class="py-[6px] px-[10px] font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->retention_expiry_date)
                                @php
                                    $isExpired = \Carbon\Carbon::parse($archive->retention_expiry_date)->isPast();
                                    $isNear = \Carbon\Carbon::parse($archive->retention_expiry_date)->diffInDays(now()) <= 90;
                                @endphp
                                <span class="{{ $isExpired ? 'text-rose-600 dark:text-rose-400 font-extrabold' : ($isNear ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-700 dark:text-slate-300 font-medium') }}">
                                    {{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('d M Y') }}
                                    ({{ $archive->retention_years }} Thn)
                                </span>
                            @else
                                <span class="text-slate-400 dark:text-slate-500">-</span>
                            @endif
                        </td>

                        <td class="py-[6px] px-[10px] font-mono border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->status === 'draft')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">Draft</span>
                            @elseif($archive->status === 'pending_verification')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-amber-500/10 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/40">Antrean Verifikasi</span>
                            @elseif($archive->status === 'approved_booked')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-blue-500/10 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/40">Approved / Booked</span>
                            @elseif($archive->status === 'in_warehouse')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-emerald-500/10 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/40">Di Gudang</span>
                            @elseif($archive->status === 'borrowed')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-purple-500/10 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/40">Dipinjam</span>
                            @elseif($archive->status === 'destroyed')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-rose-500/10 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/40">Dimusnahkan</span>
                            @endif
                        </td>

                        <td class="py-[6px] px-[10px] text-right font-mono whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-[6px]">
                                <a href="{{ route('archives.print_sticker', $archive) }}" target="_blank" title="Cetak Label Box Container" class="px-[6px] py-[2px] rounded-[3px] bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40 text-[11px] font-bold transition inline-flex items-center gap-[4px] shadow-2xs">
                                    <i data-lucide="printer" class="w-[12px] h-[12px] text-amber-500"></i>
                                    <span>Label</span>
                                </a>
                                <a href="{{ route('archives.show', $archive) }}" class="px-[6px] py-[2px] rounded-[3px] bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-amber-500 text-[11px] font-bold transition inline-flex items-center gap-[4px] shadow-2xs">
                                    <span>Detail</span>
                                    <i data-lucide="chevron-right" class="w-[12px] h-[12px]"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-[32px] text-center font-mono text-slate-500 dark:text-slate-400 space-y-[6px]">
                            <i data-lucide="folder-search" class="w-[36px] h-[36px] mx-auto text-slate-400 dark:text-slate-600"></i>
                            <p class="text-[11px] font-bold">Tidak ada berkas arsip yang ditemukan berdasarkan kriteria pencarian ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- DELPHI DBNAVIGATOR / TSTATUSBAR PAGINATION FOOTER -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-[10px] py-[6px] flex items-center justify-between gap-[8px] font-mono text-[11px] text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-[6px] text-[11px]">
                <i data-lucide="database" class="w-[13px] h-[13px] text-amber-500"></i>
                <span>Menampilkan <strong>{{ $archives->firstItem() ?? 0 }}</strong> - <strong>{{ $archives->lastItem() ?? 0 }}</strong> dari <strong>{{ $archives->total() }}</strong> total berkas arsip</span>
            </div>

            <div>
                {{ $archives->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

