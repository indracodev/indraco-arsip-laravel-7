@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Katalog & Booking Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="katalogArsipApp()">
    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER (TPanel / TToolBar) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="folder-archive" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    KATALOG ARSIP & PENGAJUAN STORAGE
                    <span class="px-1.5 py-0.2 bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[10px] rounded border border-amber-400/30 font-mono">TDBGrid Engine</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Cari, ajukan booking gudang, dan kelola masa simpan dokumen fisik & digital PT Indraco.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Refresh (F5) -->
            <button onclick="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0 cursor-pointer" title="Refresh Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-500"></i>
                <span>Refresh (F5)</span>
            </button>

            @if(!auth()->user()->isPicDept())
            <!-- Cetak Custom Label (F9) -->
            <a href="{{ route('archives.print_labels') }}" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm shrink-0 cursor-pointer" title="Cetak Custom Label Box (F9)">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Cetak Label (F9)</span>
            </a>
            @endif

            <!-- Buat Draft Pengajuan Arsip (F2) -->
            <a href="{{ route('archives.create') }}" class="px-3 py-1 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5 shrink-0 cursor-pointer" title="Buat Draft Pengajuan Arsip Baru (F2)">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Buat Draft (F2)</span>
            </a>
        </div>
    </div>

    <!-- PENDING BORROWING REQUESTS NOTIFICATION BANNER (For PIC Gudang & Admin) -->
    @if(isset($pendingBorrowingRequestsCount) && $pendingBorrowingRequestsCount > 0 && (auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()))
    <div class="p-3.5 rounded bg-purple-500/10 border border-purple-500/40 text-purple-900 dark:text-purple-200 text-xs font-mono flex items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-2">
            <i data-lucide="bell-ring" class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0 animate-bounce"></i>
            <div>
                <strong class="font-extrabold text-purple-950 dark:text-purple-100 uppercase">Ajuan Peminjaman Berkas Membutuhkan Pengeluaran:</strong>
                Terdapat <span class="font-black underline text-purple-700 dark:text-purple-300">{{ $pendingBorrowingRequestsCount }} ajuan peminjaman berkas</span> dari PIC Departemen yang menunggu pengeluaran fisik dokumen dari gudang.
            </div>
        </div>
        <a href="{{ route('archives.index', ['status' => 'borrow_requested']) }}" class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded font-bold text-xs shadow transition flex items-center gap-1 shrink-0">
            <i data-lucide="arrow-right-circle" class="w-3.5 h-3.5"></i>
            <span>Filter Ajuan Peminjaman</span>
        </a>
    </div>
    @endif

    <!-- DELPHI GROUPBOX FILTER PANEL (TGroupBox Delphi Desktop Style) -->
    <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm" x-data="{ submitting: false }">
        <legend class="px-2 font-mono text-[11px] font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
            <i data-lucide="filter" class="w-3.5 h-3.5 text-amber-500"></i>
            Filter & Pencarian Data Katalog (TSpeedFilter)
        </legend>

        <form action="{{ route('archives.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-1" @submit="submitting = true">
            <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
            <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

            <!-- Search Keyword -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block flex items-center justify-between">
                    <span>CARI KEYWORD</span>
                    <span class="text-amber-600 dark:text-amber-400">[Ctrl+F]</span>
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Judul, No. Box, Isi Berkas..." 
                        class="w-full pl-8 pr-3 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                    >
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1.5"></i>
                </div>
            </div>

            <!-- Department Filter -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">DEPARTEMEN</label>
                <select name="department_id" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Departemen --</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->code }} - {{ $dept->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">STATUS WORKFLOW</label>
                <select name="status" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Status Workflow --</option>
                    <option value="borrow_requested" {{ request('status') == 'borrow_requested' ? 'selected' : '' }}>📌 Ajuan Peminjaman (PIC Dept)</option>
                    @if(!auth()->user()->isPicGudang())
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (Simpan Sementara / Revisi)</option>
                    @endif
                    <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Antrean Verifikasi</option>
                    <option value="approved_booked" {{ request('status') == 'approved_booked' ? 'selected' : '' }}>Approved / Booked</option>
                    <option value="in_warehouse" {{ request('status') == 'in_warehouse' ? 'selected' : '' }}>Di Gudang</option>
                    <option value="borrowed" {{ request('status') == 'borrowed' ? 'selected' : '' }}>Sedang Dipinjam</option>
                    <option value="destroyed" {{ request('status') == 'destroyed' ? 'selected' : '' }}>Dimusnahkan</option>
                </select>
            </div>

            <!-- Expiry Alert Filter -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">EXPIRY MASA SIMPAN</label>
                <select name="expiry_filter" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Expiry --</option>
                    <option value="expiring_soon" {{ request('expiry_filter') == 'expiring_soon' ? 'selected' : '' }}>Mendekati Expiry (&le; 90 Hari)</option>
                    <option value="expired" {{ request('expiry_filter') == 'expired' ? 'selected' : '' }}>Sudah Kadaluarsa</option>
                </select>
            </div>

            <!-- Submit & Reset Filter Buttons -->
            <div class="space-y-1 flex items-end gap-1.5">
                <button type="submit" :disabled="submitting" class="flex-1 py-1 px-3 bg-slate-800 hover:bg-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded text-xs font-mono font-bold border border-slate-700 transition flex items-center justify-center gap-1.5 disabled:opacity-50 h-[30px] shadow-sm cursor-pointer">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                    <i data-lucide="filter" class="w-3.5 h-3.5 text-amber-400" x-show="!submitting"></i>
                    <span x-text="submitting ? 'Memuat...' : 'Filter'"></span>
                </button>

                @if(request()->hasAny(['search', 'department_id', 'status', 'expiry_filter']))
                <a href="{{ route('archives.index') }}" class="py-1 px-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-mono font-bold border border-slate-300 dark:border-slate-700 transition flex items-center justify-center h-[30px] cursor-pointer" title="Reset Filter">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </a>
                @endif
            </div>
        </form>
    </fieldset>

    @if(!auth()->user()->isPicDept())
    <!-- BATCH ACTIONS RIBBON (When items checked) -->
    <div x-show="selected.length > 0" x-transition class="bg-amber-500/10 border border-amber-500/40 rounded p-2.5 flex flex-wrap items-center justify-between gap-2 font-mono text-xs shadow-sm">
        <div class="flex items-center gap-2">
            <span class="p-1 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded">
                <i data-lucide="check-square" class="w-4 h-4"></i>
            </span>
            <span class="text-slate-800 dark:text-slate-200">
                Terpilih: <strong class="text-amber-600 dark:text-amber-400 font-extrabold" x-text="selected.length"></strong> berkas arsip untuk pencetakan label box.
            </span>
        </div>

        <form action="{{ route('archives.print_labels') }}" method="GET" target="_blank" class="inline">
            <input type="hidden" name="ids" :value="selected.join(',')">
            <button type="submit" class="px-3 py-1 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Cetak Label Terpilih (<span x-text="selected.length"></span>)</span>
            </button>
        </form>
    </div>
    @endif

    <!-- DELPHI TDBGRID SPREADSHEET TABLE CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 font-mono text-[11px] text-slate-700 dark:text-slate-300 select-none">
                        <th class="py-2 px-2.5 w-8 text-center border-r border-slate-200 dark:border-slate-800">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded text-amber-600 focus:ring-0 cursor-pointer">
                        </th>

                        <!-- NO. BOX ARSIP -->
                        <th class="py-2 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'box_number', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 transition">
                                <span>NO. BOX ARSIP</span>
                                @if(request('sort') == 'box_number')
                                    <i data-lucide="{{ request('direction') == 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-3 h-3 text-amber-500"></i>
                                @endif
                            </a>
                        </th>

                        <!-- JUDUL BERKAS & DEPT -->
                        <th class="py-2 px-3 border-r border-slate-200 dark:border-slate-800 min-w-[220px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'title', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 transition">
                                <span>JUDUL BERKAS & DEPT</span>
                                @if(request('sort') == 'title')
                                    <i data-lucide="{{ request('direction') == 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-3 h-3 text-amber-500"></i>
                                @endif
                            </a>
                        </th>

                        <!-- PERIODE -->
                        <th class="py-2 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <span>PERIODE</span>
                        </th>

                        <!-- KONDISI FISIK -->
                        <th class="py-2 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <span>KONDISI FISIK</span>
                        </th>

                        <!-- LOKASI RAK GUDANG -->
                        <th class="py-2 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <span>LOKASI RAK GUDANG</span>
                        </th>

                        <!-- MASA SIMPAN (EXPIRY) -->
                        <th class="py-2 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'retention_expiry_date', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 transition">
                                <span>MASA SIMPAN (EXPIRY)</span>
                                @if(request('sort') == 'retention_expiry_date')
                                    <i data-lucide="{{ request('direction') == 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-3 h-3 text-amber-500"></i>
                                @endif
                            </a>
                        </th>

                        <!-- STATUS -->
                        <th class="py-2 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 transition">
                                <span>STATUS</span>
                                @if(request('sort') == 'status')
                                    <i data-lucide="{{ request('direction') == 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-3 h-3 text-amber-500"></i>
                                @endif
                            </a>
                        </th>

                        <!-- AKSI -->
                        <th class="py-2 px-3 text-center whitespace-nowrap">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($archives as $archive)
                    @php
                        $activeBorrowing = $archive->borrowingLogs ? $archive->borrowingLogs->whereIn('status', ['requested', 'dept_approved', 'approved', 'dispatched', 'borrowed'])->first() : null;
                    @endphp
                    <tr class="hover:bg-amber-50/40 dark:hover:bg-slate-900/60 transition {{ in_array($archive->id, request('highlight_ids', [])) ? 'bg-amber-100/60 dark:bg-amber-950/30' : '' }}">
                        <td class="py-2.5 px-2.5 text-center border-r border-slate-200 dark:border-slate-800">
                            <input type="checkbox" value="{{ $archive->id }}" x-model="selected" class="rounded text-amber-600 focus:ring-0 cursor-pointer">
                        </td>

                        <td class="py-2.5 px-3 font-mono text-amber-700 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->box_number)
                                <div class="flex items-center gap-1">
                                    <i data-lucide="qr-code" class="w-3.5 h-3.5 text-amber-500"></i>
                                    <span>{{ $archive->box_number }}</span>
                                </div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic font-normal text-[11px]">- Belum ada box code -</span>
                            @endif
                        </td>

                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                            <div class="space-y-0.5">
                                <a href="{{ route('archives.show', $archive) }}" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block font-mono">
                                    {{ $archive->title }}
                                </a>
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-mono">
                                    <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold">
                                        {{ $archive->department->code ?? 'GEN' }}
                                    </span>
                                    <span>by {{ $archive->creator->name ?? 'User' }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->effective_periode }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->physical_condition }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->location)
                                <button 
                                    type="button"
                                    @click="openQuickSlotPicker({
                                        id: {{ $archive->id }},
                                        box_number: '{{ addslashes($archive->box_number ?? '') }}',
                                        title: '{{ addslashes($archive->title) }}',
                                        department_id: {{ $archive->department_id ?? 0 }},
                                        department_code: '{{ addslashes($archive->department->code ?? 'GEN') }}',
                                        department_name: '{{ addslashes($archive->department->name ?? '') }}'
                                    })"
                                    class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold hover:underline cursor-pointer text-left"
                                    title="Klik untuk ubah / lihat penempatan slot rak 2D"
                                >
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                                    <span>{{ $archive->display_location }}</span>
                                </button>
                            @else
                                <button 
                                    type="button" 
                                    @click="openQuickSlotPicker({
                                        id: {{ $archive->id }},
                                        box_number: '{{ addslashes($archive->box_number ?? 'Belum ada code') }}',
                                        title: '{{ addslashes($archive->title) }}',
                                        department_id: {{ $archive->department_id ?? 0 }},
                                        department_code: '{{ addslashes($archive->department->code ?? 'GEN') }}',
                                        department_name: '{{ addslashes($archive->department->name ?? '') }}'
                                    })"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/40 animate-pulse hover:bg-rose-500/25 transition shadow-xs cursor-pointer" 
                                    title="Lokasi belum ditentukan! Klik untuk buka Alokasi Slot 2D"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                    <span class="underline decoration-dotted underline-offset-2">Belum Ditentukan</span>
                                    <i data-lucide="map-pin" class="w-3 h-3 text-rose-500"></i>
                                </button>
                            @endif
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
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

                        <td class="py-2.5 px-3 font-mono border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($activeBorrowing)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-900 dark:text-purple-200 border border-purple-500/40 inline-flex items-center gap-1 animate-pulse" title="Peminjam: {{ $activeBorrowing->borrower->name ?? 'User' }}">
                                    <i data-lucide="file-symlink" class="w-3 h-3 text-purple-500"></i>
                                    Ajuan Pinjam ({{ $activeBorrowing->borrower->department->code ?? 'DEPT' }})
                                </span>
                            @elseif($archive->status === 'draft')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">Draft</span>
                            @elseif($archive->status === 'pending_verification')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/40">Antrean Verifikasi</span>
                            @elseif($archive->status === 'approved_booked')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/40">Approved / Booked</span>
                            @elseif($archive->status === 'in_warehouse')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/40">Di Gudang</span>
                            @elseif($archive->status === 'borrowed')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/40">Dipinjam</span>
                            @elseif($archive->status === 'destroyed')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/40">Dimusnahkan</span>
                            @endif
                        </td>

                        <!-- AKSI BUTTONS -->
                        <td class="py-2.5 px-3 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1 font-mono">
                                @if($activeBorrowing && (auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $activeBorrowing->status === 'requested')
                                <button 
                                    type="button" 
                                    @click="dispatchModalLog = {
                                        id: {{ $activeBorrowing->id }},
                                        box_number: '{{ $archive->box_number }}',
                                        archive_title: '{{ addslashes($archive->title) }}',
                                        location: '{{ $archive->location ? $archive->location->full_location : '-' }}',
                                        borrower_name: '{{ addslashes($activeBorrowing->borrower->name ?? 'User') }}',
                                        borrower_dept: '{{ $activeBorrowing->borrower->department->code ?? 'DEPT' }}',
                                        expected_return_date: '{{ $activeBorrowing->expected_return_date ? \Carbon\Carbon::parse($activeBorrowing->expected_return_date)->format('d M Y') : '-' }}',
                                        purpose: '{{ addslashes($activeBorrowing->purpose) }}',
                                        approval_url: '{{ $activeBorrowing->approval_letter_file ? asset('storage/' . $activeBorrowing->approval_letter_file) : '' }}'
                                    }"
                                    class="px-2 py-0.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded text-[10px] font-bold transition flex items-center gap-1 shadow-sm" 
                                    title="Keluarkan Berkas Fisik"
                                >
                                    <i data-lucide="log-out" class="w-3 h-3"></i>
                                    <span>Keluarkan</span>
                                </button>
                                @endif

                                @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || (auth()->user()->isPicDept() && $archive->department_id === auth()->user()->department_id) || in_array($archive->status, ['draft', 'pending_verification', 'approved_booked']))
                                <a href="{{ route('archives.edit', $archive) }}" class="px-2 py-0.5 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded text-[10px] font-bold border border-amber-600 transition flex items-center gap-1 cursor-pointer" title="Edit Data Arsip">
                                    <i data-lucide="edit" class="w-3 h-3"></i>
                                    <span>Edit</span>
                                </a>
                                @endif

                                @if(!auth()->user()->isPicDept())
                                <a href="{{ route('archives.print_sticker', $archive) }}" target="_blank" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[10px] font-bold border border-slate-300 dark:border-slate-700 transition flex items-center gap-1" title="Cetak Stiker Label">
                                    <i data-lucide="printer" class="w-3 h-3 text-amber-500"></i>
                                    <span>Label</span>
                                </a>
                                @endif

                                <a href="{{ route('archives.show', $archive) }}" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[10px] font-bold border border-slate-300 dark:border-slate-700 transition flex items-center gap-1" title="Lihat Detail Berkas">
                                    <span>Detail</span>
                                    <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400 font-mono text-xs">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                            Tidak ada data arsip yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- DELPHI DBNAVIGATOR / TSTATUSBAR PAGINATION FOOTER -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-3 py-2 flex flex-col sm:flex-row items-center justify-between gap-2 font-mono text-xs text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-2 text-[11px]">
                <i data-lucide="database" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Menampilkan <strong>{{ $archives->firstItem() ?? 0 }}</strong> - <strong>{{ $archives->lastItem() ?? 0 }}</strong> dari <strong>{{ $archives->total() }}</strong> total berkas arsip</span>
            </div>

            <div>
                {{ $archives->links('vendor.pagination.tailwind') }}
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- QUICK SLOT 2D ALLOCATOR MODAL (Simpel: Ruang -> Rak -> Slot -> DblClick)   -->
    <!-- ========================================================================= -->
    <div 
        x-show="quickSlotModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-950/80 backdrop-blur-xs font-sans text-xs select-none"
        @keydown.escape.window="if (!confirmModalOpen) quickSlotModalOpen = false"
    >
        <div 
            class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-lg max-w-5xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden"
            @click.away="if (!confirmModalOpen) quickSlotModalOpen = false"
        >
            <!-- Modal Header -->
            <div class="px-4 py-3 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center justify-center">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-mono font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                            <span>ALOKASI SLOT RAK 2D</span>
                            <span class="px-1.5 py-0.2 bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 text-[10px] rounded font-mono">Quick 2D Picker</span>
                        </h3>
                        <p class="text-[11px] text-slate-400">Pilih Ruang &rarr; Klik Rak &rarr; Double-Click Slot Kosong untuk otomatis simpan.</p>
                    </div>
                </div>

                <!-- Close Button -->
                <button 
                    @click="quickSlotModalOpen = false" 
                    type="button" 
                    class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-white bg-slate-800 hover:bg-rose-600 rounded transition cursor-pointer"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Target Archive Info Bar -->
            <template x-if="targetArchive">
                <div class="px-4 py-2 bg-amber-500/10 border-b border-amber-500/30 flex flex-wrap items-center justify-between gap-2 text-xs font-mono shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 uppercase font-bold text-[10px]">DOKUMEN TARGET:</span>
                        <span class="px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold rounded border border-indigo-300 dark:border-indigo-800 text-[11px]" x-text="targetArchive.department_code"></span>
                        <span class="font-bold text-slate-900 dark:text-white truncate max-w-sm sm:max-w-md" x-text="targetArchive.title"></span>
                        <span class="text-amber-700 dark:text-amber-400 font-bold" x-text="targetArchive.box_number ? '(' + targetArchive.box_number + ')' : '(Draft)'"></span>
                    </div>

                    <!-- Breadcrumbs Nav -->
                    <div class="flex items-center gap-1.5 text-[11px]">
                        <button 
                            type="button" 
                            @click="quickSlotStep = 'rooms'; selectedRoom = null; selectedRack = null; selectedSlot = null;"
                            class="px-2 py-0.5 rounded transition cursor-pointer font-bold"
                            :class="quickSlotStep === 'rooms' ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300'"
                        >
                            1. Ruangan
                        </button>
                        <span class="text-slate-400">&rarr;</span>
                        <button 
                            type="button" 
                            @click="if (selectedRoom) { quickSlotStep = 'racks'; selectedRack = null; selectedSlot = null; }"
                            :disabled="!selectedRoom"
                            class="px-2 py-0.5 rounded transition cursor-pointer font-bold disabled:opacity-40"
                            :class="quickSlotStep === 'racks' ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300'"
                            x-text="selectedRoom ? ('2. Rak (' + (selectedRoom.rack_code || selectedRoom.room_sector) + ')') : '2. Rak'"
                        ></button>
                        <span class="text-slate-400">&rarr;</span>
                        <button 
                            type="button" 
                            :disabled="!selectedRack"
                            class="px-2 py-0.5 rounded transition font-bold disabled:opacity-40"
                            :class="quickSlotStep === 'slots' ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                            x-text="selectedRack ? ('3. Slot (' + selectedRack.rack_code + ')') : '3. Slot'"
                        ></button>
                    </div>
                </div>
            </template>

            <!-- Modal Content Body -->
            <div class="flex-1 overflow-y-auto p-4 bg-slate-50 dark:bg-slate-900/40">
                <!-- Loading State -->
                <template x-if="loadingLocations">
                    <div class="py-16 text-center space-y-2 font-mono">
                        <i data-lucide="loader-2" class="w-8 h-8 mx-auto animate-spin text-indigo-500"></i>
                        <p class="text-xs text-slate-500 font-bold">Memuat layout 2D denah gudang...</p>
                    </div>
                </template>

                <!-- STEP 1: PILIH RUANGAN / GUDANG -->
                <div x-show="!loadingLocations && quickSlotStep === 'rooms'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="font-mono font-bold text-xs uppercase text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <i data-lucide="layout-grid" class="w-4 h-4 text-indigo-500"></i>
                            Pilih Ruang / Sektor Gudang:
                        </h4>
                        <span class="text-[11px] font-mono text-slate-500">Klik salah satu ruangan untuk melihat rak di dalamnya</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <template x-for="room in getRooms()" :key="room.id">
                            <div 
                                @click="selectRoom(room)"
                                class="p-3.5 rounded-lg border transition cursor-pointer flex flex-col justify-between space-y-3 relative group"
                                :class="isRoomDisabledForTargetArchive(room) ? 'bg-slate-100/70 dark:bg-slate-900/30 border-slate-200 dark:border-slate-800 opacity-60 hover:opacity-100' : 'bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-indigo-500 hover:shadow-md'"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="space-y-0.5">
                                        <span class="text-[10px] font-mono uppercase font-bold text-slate-400 block" x-text="'SEKTOR ' + (room.room_sector || room.rack_code)"></span>
                                        <h5 class="text-sm font-bold font-mono text-slate-900 dark:text-white flex items-center gap-1.5">
                                            <i data-lucide="warehouse" class="w-4 h-4 text-indigo-500"></i>
                                            <span x-text="room.rack_code"></span>
                                        </h5>
                                    </div>
                                    <template x-if="isFatRoom(room)">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-800 flex items-center gap-1">
                                            <i data-lucide="lock" class="w-3 h-3"></i>
                                            Khusus FAT
                                        </span>
                                    </template>
                                </div>

                                <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] font-mono">
                                    <span class="text-slate-500" x-text="getRacksInRoom(room).length + ' Rak Tersedia'"></span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400 group-hover:underline flex items-center gap-1">
                                        Buka Ruang &rarr;
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- STEP 2: PILIH RAK DALAM RUANGAN -->
                <div x-show="!loadingLocations && quickSlotStep === 'racks'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="quickSlotStep = 'rooms'" 
                                class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded border border-slate-300 dark:border-slate-700 font-mono text-[11px] font-bold flex items-center gap-1 text-slate-700 dark:text-slate-300 cursor-pointer"
                            >
                                <i data-lucide="arrow-left" class="w-3 h-3"></i>
                                Kembali ke Ruangan
                            </button>
                            <h4 class="font-mono font-bold text-xs uppercase text-slate-700 dark:text-slate-300" x-text="'Rak pada ' + (selectedRoom ? (selectedRoom.rack_code || selectedRoom.room_sector) : '') + ':'"></h4>
                        </div>
                        <span class="text-[11px] font-mono text-slate-500">Klik pada salah satu rak untuk membuka 100 slot kardus</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <template x-for="rack in getRacksInRoom(selectedRoom)" :key="rack.id">
                            <div 
                                @click="selectRack(rack)"
                                class="p-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:border-indigo-500 rounded-lg transition cursor-pointer shadow-xs hover:shadow-md space-y-2.5 group"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <span class="text-[10px] font-mono uppercase text-slate-400 font-bold block" x-text="'Sektor: ' + (rack.room_sector || 'Umum')"></span>
                                        <h5 class="font-mono font-bold text-xs text-slate-900 dark:text-white flex items-center gap-1.5 mt-0.5">
                                            <i data-lucide="server" class="w-3.5 h-3.5 text-amber-500"></i>
                                            <span x-text="rack.rack_code"></span>
                                        </h5>
                                    </div>
                                    <span class="font-mono font-bold text-[10px] px-1.5 py-0.5 bg-slate-100 dark:bg-slate-900 rounded text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800">
                                        100 Box
                                    </span>
                                </div>

                                <!-- Mini Progress Bar -->
                                <div class="space-y-1">
                                    <div class="flex justify-between text-[10px] font-mono">
                                        <span class="text-slate-500">Terisi: <strong class="text-amber-600 dark:text-amber-400" x-text="getRackStats(rack).filled"></strong></span>
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="getRackStats(rack).empty + ' Kosong'"></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden flex">
                                        <div class="bg-amber-500 h-full" :style="'width: ' + ((getRackStats(rack).filled / 100) * 100) + '%'"></div>
                                        <div class="bg-rose-500 h-full" :style="'width: ' + ((getRackStats(rack).expired / 100) * 100) + '%'"></div>
                                    </div>
                                </div>

                                <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[10px] font-mono">
                                    <span class="text-slate-400" x-text="'Kapasitas ' + (rack.box_capacity || 100)"></span>
                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold group-hover:underline flex items-center gap-0.5">
                                        Buka Slot &rarr;
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-if="getRacksInRoom(selectedRoom).length === 0">
                        <div class="p-8 text-center bg-white dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 font-mono text-xs">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                            Belum ada rak fisik yang terdaftar di dalam ruangan ini.
                        </div>
                    </template>
                </div>

                <!-- STEP 3: DENAH 100 SLOT RAK (DOUBLE CLICK UNTUK SIMPAN) -->
                <div x-show="!loadingLocations && quickSlotStep === 'slots'" class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="quickSlotStep = 'racks'" 
                                class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded border border-slate-300 dark:border-slate-700 font-mono text-[11px] font-bold flex items-center gap-1 text-slate-700 dark:text-slate-300 cursor-pointer"
                            >
                                <i data-lucide="arrow-left" class="w-3 h-3"></i>
                                Daftar Rak
                            </button>
                            <h4 class="font-mono font-bold text-xs uppercase text-slate-700 dark:text-slate-300" x-text="'Denah Slot: ' + (selectedRack ? selectedRack.rack_code : '')"></h4>
                        </div>

                        <!-- Instructions / Hint Pill -->
                        <div class="px-2.5 py-1 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 rounded font-mono text-[11px] font-bold flex items-center gap-1.5">
                            <i data-lucide="mouse-pointer" class="w-3.5 h-3.5 text-emerald-500"></i>
                            <span>💡 Klik ganda (Double-Click) pada slot KOSONG untuk langsung simpan arsip.</span>
                        </div>
                    </div>

                    <!-- 5 Saps Rack Grid (100 Slots) -->
                    <div class="space-y-2.5 max-h-[58vh] overflow-y-auto pr-1">
                        <template x-for="sapNum in [5, 4, 3, 2, 1]" :key="sapNum">
                            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded p-2.5 space-y-2 shadow-xs">
                                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-1 font-mono text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-4 h-4 rounded bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold text-[10px] flex items-center justify-center border border-indigo-200 dark:border-indigo-800" x-text="sapNum"></span>
                                        <strong class="text-slate-800 dark:text-slate-200" x-text="'LVL ' + sapNum"></strong>
                                    </div>
                                    <span class="text-[10px] text-slate-400" x-text="'20 Box (Nomor ' + ((sapNum-1)*20 + 1) + ' — ' + ((sapNum-1)*20 + 20) + ')'"></span>
                                </div>

                                <!-- Baris Atas (10 Slots) -->
                                <div class="space-y-0.5">
                                    <span class="text-[9px] font-mono text-slate-500 font-bold block">Baris Atas (10 Box):</span>
                                    <div class="grid grid-cols-5 sm:grid-cols-10 gap-1 font-mono">
                                        <template x-for="slot in getSapSlots(sapNum, 'top')" :key="slot.slot_code || slot.id">
                                            <div 
                                                @click="handleSlotClick(slot)"
                                                @dblclick="handleSlotDblClick(slot)"
                                                class="p-1 rounded border transition cursor-pointer flex flex-col justify-between items-center text-center select-none min-h-[50px] relative"
                                                :class="[
                                                    (slot.status === 'filled' || slot.archive) ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border-amber-300 dark:border-amber-800/80 cursor-not-allowed opacity-85' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30',
                                                    selectedSlot?.slot_code === slot.slot_code ? 'ring-2 ring-indigo-500 !border-indigo-500 !bg-indigo-50 dark:!bg-indigo-950/60' : ''
                                                ]"
                                                :title="slot.archive ? ('Box #' + (slot.box_number_display || slot.slot_number) + ': ' + (slot.archive.box_number || 'Terisi')) : ('Box #' + (slot.box_number_display || slot.slot_number) + ': Slot Kosong (Double click untuk pilih)')"
                                            >
                                                <div class="w-full flex items-center justify-between text-[9px] font-bold opacity-90">
                                                    <span class="text-amber-600 dark:text-amber-400 font-black" x-text="'#' + (slot.box_number_display || slot.slot_number)"></span>
                                                    <span class="w-1.5 h-1.5 rounded-none" :class="(slot.status === 'filled' || slot.archive) ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                                                </div>
                                                <div class="w-full my-auto text-center">
                                                    <template x-if="slot.archive">
                                                        <span class="text-[8px] font-bold block truncate max-w-[65px] mx-auto text-amber-700 dark:text-amber-400" x-text="slot.archive.box_number || 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="!slot.archive">
                                                        <span class="text-[8px] font-bold text-emerald-600 dark:text-emerald-400">Kosong</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Baris Bawah (10 Slots) -->
                                <div class="space-y-0.5 pt-1">
                                    <span class="text-[9px] font-mono text-slate-500 font-bold block">Baris Bawah (10 Box):</span>
                                    <div class="grid grid-cols-5 sm:grid-cols-10 gap-1 font-mono">
                                        <template x-for="slot in getSapSlots(sapNum, 'bottom')" :key="slot.slot_code || slot.id">
                                            <div 
                                                @click="handleSlotClick(slot)"
                                                @dblclick="handleSlotDblClick(slot)"
                                                class="p-1 rounded border transition cursor-pointer flex flex-col justify-between items-center text-center select-none min-h-[50px] relative"
                                                :class="[
                                                    (slot.status === 'filled' || slot.archive) ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border-amber-300 dark:border-amber-800/80 cursor-not-allowed opacity-85' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30',
                                                    selectedSlot?.slot_code === slot.slot_code ? 'ring-2 ring-indigo-500 !border-indigo-500 !bg-indigo-50 dark:!bg-indigo-950/60' : ''
                                                ]"
                                                :title="slot.archive ? ('Box #' + (slot.box_number_display || slot.slot_number) + ': ' + (slot.archive.box_number || 'Terisi')) : ('Box #' + (slot.box_number_display || slot.slot_number) + ': Slot Kosong (Double click untuk pilih)')"
                                            >
                                                <div class="w-full flex items-center justify-between text-[9px] font-bold opacity-90">
                                                    <span class="text-amber-600 dark:text-amber-400 font-black" x-text="'#' + (slot.box_number_display || slot.slot_number)"></span>
                                                    <span class="w-1.5 h-1.5 rounded-none" :class="(slot.status === 'filled' || slot.archive) ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                                                </div>
                                                <div class="w-full my-auto text-center">
                                                    <template x-if="slot.archive">
                                                        <span class="text-[8px] font-bold block truncate max-w-[65px] mx-auto text-amber-700 dark:text-amber-400" x-text="slot.archive.box_number || 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="!slot.archive">
                                                        <span class="text-[8px] font-bold text-emerald-600 dark:text-emerald-400">Kosong</span>
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

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between shrink-0 font-mono text-xs">
                <div class="flex items-center gap-2">
                    <template x-if="selectedSlot">
                        <div class="flex items-center gap-1.5 text-[11px] text-slate-700 dark:text-slate-300">
                            <span>Slot Terpilih:</span>
                            <strong class="text-amber-600 dark:text-amber-400" x-text="'#' + (selectedSlot.box_number_display || selectedSlot.slot_number) + ' (' + selectedSlot.slot_code + ')'"></strong>
                            <span class="text-slate-400">•</span>
                            <span class="text-emerald-600 font-bold" x-text="(!selectedSlot.archive && selectedSlot.status === 'empty') ? 'Siap Ditempati (Double-Click untuk simpan)' : 'Sudah Terisi'"></span>
                        </div>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        @click="quickSlotModalOpen = false" 
                        class="px-3 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 rounded border border-slate-300 dark:border-slate-700 font-bold text-slate-700 dark:text-slate-300 cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- POPUP KONFIRMASI PENEMPATAN SLOT ARSIP (Triggered on Double-Click)         -->
    <!-- ========================================================================= -->
    <div 
        x-show="confirmModalOpen" 
        x-cloak 
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-xs font-sans text-xs select-none"
        @keydown.escape.window="confirmModalOpen = false"
    >
        <div 
            class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4"
            @click.away="confirmModalOpen = false"
        >
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/40">
                    <i data-lucide="help-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-mono font-bold text-sm text-slate-900 dark:text-white uppercase tracking-wider">
                        Konfirmasi Penempatan Slot
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Apakah Anda yakin ingin menempatkan dokumen ini ke slot rak yang dipilih?
                    </p>
                </div>
            </div>

            <!-- Info Summary Box -->
            <div class="p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded font-mono text-xs space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-400">Dokumen:</span>
                    <strong class="text-slate-900 dark:text-white truncate max-w-[200px]" x-text="targetArchive?.title"></strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Departemen:</span>
                    <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="targetArchive?.department_code + ' - ' + targetArchive?.department_name"></span>
                </div>
                <div class="flex justify-between pt-1 border-t border-slate-200 dark:border-slate-800">
                    <span class="text-slate-400">Target Lokasi:</span>
                    <strong class="text-emerald-600 dark:text-emerald-400" x-text="'Slot #' + (selectedSlot?.box_number_display || selectedSlot?.slot_number) + ' (' + selectedSlot?.slot_code + ')'"></strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Rak & Ruang:</span>
                    <strong class="text-amber-600 dark:text-amber-400" x-text="selectedRack?.rack_code + ' • Sektor ' + (selectedRack?.room_sector || 'Umum')"></strong>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-2 pt-1">
                <button 
                    type="button" 
                    @click="confirmModalOpen = false" 
                    :disabled="assignLoading"
                    class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded border border-slate-300 dark:border-slate-700 font-mono font-bold text-xs cursor-pointer"
                >
                    Batal
                </button>
                <button 
                    type="button" 
                    @click="executeSaveToSlot()" 
                    :disabled="assignLoading"
                    class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded border border-emerald-700 font-mono font-bold text-xs shadow transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                >
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="assignLoading"></i>
                    <i data-lucide="check" class="w-3.5 h-3.5" x-show="!assignLoading"></i>
                    <span x-text="assignLoading ? 'Menyimpan...' : 'Ya, Simpan ke Slot Ini'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DISPATCH / KELUARKAN ARSIP UNTUK PIC GUDANG -->
    <div x-show="dispatchModalLog" 
         x-transition.opacity 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 font-mono text-xs"
             @click.away="dispatchModalLog = null">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <div class="p-2 bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-xl">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">Pengeluaran Berkas Fisik Arsip</h3>
                        <p class="text-[11px] text-slate-500">Konfirmasi pengeluaran dokumen berdasarkan ajuan peminjaman PIC Dept.</p>
                    </div>
                </div>
                <button type="button" @click="dispatchModalLog = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Detail Peminjaman & Arsip -->
            <template x-if="dispatchModalLog">
                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">NO. BOX / KODE:</span>
                            <span class="font-black text-amber-600 dark:text-amber-400 text-sm" x-text="dispatchModalLog.box_number"></span>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-xs" x-text="dispatchModalLog.archive_title"></h4>
                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold" x-text="'📍 Lokasi Rak: ' + dispatchModalLog.location"></p>
                    </div>

                    <div class="p-3 bg-purple-500/10 border border-purple-500/30 rounded-xl space-y-1.5 text-purple-900 dark:text-purple-200">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold">Pemohon Peminjaman:</span>
                            <span class="font-black" x-text="dispatchModalLog.borrower_name + ' (' + dispatchModalLog.borrower_dept + ')'"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold">Estimasi Pengembalian:</span>
                            <span class="font-bold text-rose-600 dark:text-rose-400" x-text="dispatchModalLog.expected_return_date"></span>
                        </div>
                        <div class="pt-1 text-[11px] border-t border-purple-500/20">
                            <span class="font-bold block">Keperluan / Alasan:</span>
                            <p class="italic text-slate-700 dark:text-slate-300" x-text="dispatchModalLog.purpose"></p>
                        </div>
                    </div>

                    <!-- Link Preview Approval File -->
                    <template x-if="dispatchModalLog.approval_url">
                        <div class="flex items-center justify-between p-2.5 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                            <span class="font-bold text-[11px] text-amber-900 dark:text-amber-200 flex items-center gap-1.5">
                                <i data-lucide="file-check" class="w-4 h-4 text-amber-500"></i>
                                Berkas Approval Terlampir
                            </span>
                            <a :href="dispatchModalLog.approval_url" target="_blank" class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded text-[10px] transition inline-flex items-center gap-1">
                                <span>Lihat Berkas</span>
                                <i data-lucide="external-link" class="w-3 h-3"></i>
                            </a>
                        </div>
                    </template>

                    <!-- Form Action -->
                    <form :action="'/borrowings/' + dispatchModalLog.id + '/dispatch'" method="POST" class="pt-2">
                        @csrf
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" @click="dispatchModalLog = null" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl font-black text-xs shadow-lg shadow-purple-500/20 transition flex items-center gap-1.5">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                <span>Disahkan & Keluarkan Berkas</span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function katalogArsipApp() {
    return {
        selected: [],
        selectAll: false,
        dispatchModalLog: null,
        allIds: [{{ $archives->pluck('id')->join(',') }}],
        toggleAll() {
            this.selected = this.selectAll ? [...this.allIds] : [];
        },

        // 2D Quick Slot Allocator State
        quickSlotModalOpen: false,
        quickSlotStep: 'rooms', // 'rooms' | 'racks' | 'slots'
        targetArchive: null,
        locations: [],
        loadingLocations: false,
        selectedRoom: null,
        selectedRack: null,
        selectedSlot: null,
        confirmModalOpen: false,
        assignLoading: false,

        async openQuickSlotPicker(archiveData) {
            this.targetArchive = archiveData;
            this.selectedRoom = null;
            this.selectedRack = null;
            this.selectedSlot = null;
            this.quickSlotStep = 'rooms';
            this.quickSlotModalOpen = true;

            if (this.locations.length === 0) {
                await this.fetchWarehouseData();
            }

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        async fetchWarehouseData() {
            this.loadingLocations = true;
            try {
                const res = await fetch('{{ route("api.warehouse.layout_data") }}');
                const data = await res.json();
                if (data.success) {
                    this.locations = data.locations || [];
                }
            } catch (err) {
                console.error('Error fetching layout data:', err);
                alert('Gagal memuat data denah gudang.');
            } finally {
                this.loadingLocations = false;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        getRooms() {
            return this.locations.filter(l => l.location_type === 'room');
        },

        isFatRoom(room) {
            if (!room) return false;
            const sector = String(room.room_sector || room.rack_code || '').trim().toUpperCase();
            return !!room.is_fat_locked || ['R1', 'R2', 'RUANG 1', 'RUANG 2', 'SEKTOR R1', 'SEKTOR R2'].includes(sector) || /^R[12]$/i.test(sector);
        },

        isArchiveFat(arc) {
            if (!arc) return false;
            const code = String(arc.department_code || '').trim().toUpperCase();
            const name = String(arc.department_name || '').trim().toUpperCase();
            const fatCodes = ['FAT', 'FIN', 'ACC', 'TAX', 'FATCLM', 'FATCLAIM'];
            return fatCodes.includes(code) || name.includes('FAT') || name.includes('KEUANGAN') || name.includes('AKUNTANSI') || name.includes('PAJAK') || name.includes('FIN, ACC');
        },

        isRoomDisabledForTargetArchive(room) {
            if (!this.targetArchive) return false;
            if (this.isFatRoom(room)) {
                return !this.isArchiveFat(this.targetArchive);
            }
            return false;
        },

        selectRoom(room) {
            if (this.isRoomDisabledForTargetArchive(room)) {
                alert(`Akses Ditolak: Ruang ${room.rack_code || room.room_sector} hanya diperuntukkan untuk Departemen FAT (FIN, ACC & TAX / FATCLAIM). Dokumen Departemen ${this.targetArchive.department_code} tidak dapat dialokasikan ke ruangan ini.`);
                return;
            }
            this.selectedRoom = room;
            this.selectedRack = null;
            this.selectedSlot = null;
            this.quickSlotStep = 'racks';
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        getRacksInRoom(room) {
            if (!room) return [];
            const roomSector = room.room_sector || room.rack_code;
            return this.locations.filter(l => l.location_type !== 'room' && (
                l.room_sector === roomSector || 
                l.room_sector === room.rack_code ||
                (l.canvas_x >= room.canvas_x && (l.canvas_x + l.canvas_width) <= (room.canvas_x + room.canvas_width) &&
                 l.canvas_y >= room.canvas_y && (l.canvas_y + l.canvas_height) <= (room.canvas_y + room.canvas_height))
            ));
        },

        getRackStats(rack) {
            if (!rack) return { total: 100, empty: 100, filled: 0, expired: 0 };
            const slots = rack.slots || [];
            let empty = 0, filled = 0, expired = 0;
            if (slots.length > 0) {
                slots.forEach(s => {
                    if (s.status === 'expired' || s.archive?.is_expired) expired++;
                    else if (s.status === 'filled' || s.archive) filled++;
                    else empty++;
                });
                const unrecorded = Math.max(0, 100 - slots.length);
                empty += unrecorded;
            } else {
                empty = rack.box_capacity || 100;
            }
            return { total: 100, empty, filled, expired };
        },

        selectRack(rack) {
            this.selectedRack = rack;
            this.selectedSlot = null;
            this.quickSlotStep = 'slots';
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        getRackIdentifier(rack) {
            if (!rack || !rack.rack_code) return 'RAK';
            const code = rack.rack_code;
            const clean = code.replace(/^RAK-(?:R\d+-)?/i, '');
            return clean || code;
        },

        getSapSlots(sapLevel, layer) {
            if (!this.selectedRack) return [];
            const allSlots = this.selectedRack.slots || [];
            const sap = parseInt(sapLevel);
            const matching = allSlots.filter(s => parseInt(s.sap_level) === sap && s.layer === layer);
            const rackId = this.getRackIdentifier(this.selectedRack);

            const result = [];
            for (let i = 1; i <= 10; i++) {
                const boxNum = layer === 'bottom' ? ((sap - 1) * 20 + i) : ((sap - 1) * 20 + 10 + i);
                const expectedSlotCode = `${rackId}${boxNum}`;
                const found = matching.find(s => parseInt(s.slot_number) === boxNum || parseInt(s.slot_number) === i);
                if (found) {
                    result.push({
                        ...found,
                        slot_code: expectedSlotCode,
                        box_number_display: boxNum
                    });
                } else {
                    result.push({
                        id: `synth-${sapLevel}-${layer}-${i}`,
                        sap_level: sapLevel,
                        layer: layer,
                        layer_label: layer === 'top' ? 'Baris Atas' : 'Baris Bawah',
                        slot_number: boxNum,
                        box_number_display: boxNum,
                        slot_code: expectedSlotCode,
                        status: 'empty',
                        archive: null
                    });
                }
            }
            return result;
        },

        handleSlotClick(slot) {
            this.selectedSlot = slot;
        },

        handleSlotDblClick(slot) {
            if (slot.status === 'filled' || slot.archive) {
                alert(`Slot #${slot.box_number_display || slot.slot_number} (${slot.slot_code}) sudah terisi oleh Box ${slot.archive?.box_number || 'lain'}. Silakan pilih slot yang Kosong.`);
                return;
            }
            if (slot.status === 'expired' || slot.archive?.is_expired) {
                alert(`Slot #${slot.box_number_display || slot.slot_number} berstatus Expired. Silakan pilih slot yang Kosong.`);
                return;
            }
            this.selectedSlot = slot;
            this.confirmModalOpen = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        async executeSaveToSlot() {
            if (!this.selectedSlot || !this.selectedRack || !this.targetArchive) return;

            // Enforce FAT room restriction
            if (this.isFatRoom(this.selectedRoom || this.selectedRack) && !this.isArchiveFat(this.targetArchive)) {
                alert(`Akses Ditolak: Ruang ${this.selectedRack.room_sector || 'R1/R2'} hanya diperuntukkan untuk Departemen FAT.`);
                return;
            }

            this.assignLoading = true;
            try {
                const payload = {
                    sap_level: this.selectedSlot.sap_level,
                    layer: this.selectedSlot.layer,
                    slot_number: this.selectedSlot.slot_number,
                    slot_code: this.selectedSlot.slot_code,
                    mode: 'existing_archive',
                    archive_id: this.targetArchive.id
                };

                const res = await fetch(`/api/warehouse/locations/${this.selectedRack.id}/slots/assign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    this.confirmModalOpen = false;
                    this.quickSlotModalOpen = false;
                    alert(`✅ Berhasil! ${data.message}`);
                    window.location.reload();
                } else {
                    alert(data.message || 'Gagal menyimpan arsip ke slot.');
                }
            } catch (err) {
                console.error('Error assigning slot:', err);
                alert('Terjadi kesalahan jaringan saat menyimpan arsip.');
            } finally {
                this.assignLoading = false;
            }
        }
    };
}
</script>
@endpush
@endsection
