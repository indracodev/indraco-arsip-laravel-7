@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Katalog & Booking Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="{ 
    selected: [], 
    selectAll: false, 
    dispatchModalLog: null,
    allIds: [{{ $archives->pluck('id')->join(',') }}],
    toggleAll() { 
        this.selected = this.selectAll ? [...this.allIds] : []; 
    } 
}">
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
            <button onclick="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Refresh Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-500"></i>
                <span>Refresh (F5)</span>
            </button>

            @if(!auth()->user()->isPicDept())
            <!-- Cetak Custom Label (F9) -->
            <a href="{{ route('archives.print_labels') }}" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm shrink-0" title="Cetak Custom Label Box (F9)">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Cetak Label (F9)</span>
            </a>
            @endif

            <!-- Buat Draft Pengajuan Arsip (F2) -->
            <a href="{{ route('archives.create') }}" class="px-3 py-1 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5 shrink-0" title="Buat Draft Pengajuan Arsip Baru (F2)">
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
                <button type="submit" :disabled="submitting" class="flex-1 py-1 px-3 bg-slate-800 hover:bg-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded text-xs font-mono font-bold border border-slate-700 transition flex items-center justify-center gap-1.5 disabled:opacity-50 h-[30px] shadow-sm">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                    <i data-lucide="filter" class="w-3.5 h-3.5 text-amber-400" x-show="!submitting"></i>
                    <span x-text="submitting ? 'Memuat...' : 'Filter'"></span>
                </button>

                @if(request()->hasAny(['search', 'department_id', 'status', 'expiry_filter']))
                <a href="{{ route('archives.index') }}" class="py-1 px-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-mono font-bold border border-slate-300 dark:border-slate-700 transition flex items-center justify-center h-[30px]" title="Reset Filter">
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
            <button type="submit" class="px-3 py-1 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Cetak Label Terpilih (<span x-text="selected.length"></span>)</span>
            </button>
        </form>
    </div>
    @endif

    <!-- DELPHI TDBGRID SPREADSHEET TABLE CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                        <th class="py-2.5 px-3 w-10 text-center border-r border-slate-300 dark:border-slate-700">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-slate-400 dark:border-slate-700 text-amber-500 focus:ring-0">
                        </th>
                        @php
                            $curSort = request('sort', 'created_at');
                            $curDir = request('direction', 'desc');
                            $nextDir = $curDir === 'asc' ? 'desc' : 'asc';
                        @endphp
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'box_number', 'direction' => $curSort === 'box_number' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>NO. BOX ARSIP</span>
                                @if($curSort === 'box_number')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'title', 'direction' => $curSort === 'title' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>JUDUL BERKAS & DEPT</span>
                                @if($curSort === 'title')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">PERIODE</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">KONDISI FISIK</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">LOKASI RAK GUDANG</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'retention_expiry_date', 'direction' => $curSort === 'retention_expiry_date' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>MASA SIMPAN (EXPIRY)</span>
                                @if($curSort === 'retention_expiry_date')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $curSort === 'status' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>STATUS</span>
                                @if($curSort === 'status')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-xs">
                    @forelse($archives as $archive)
                    @php
                        $activeBorrowing = $archive->borrowingLogs ? $archive->borrowingLogs->whereIn('status', ['requested', 'dept_approved', 'approved'])->first() : null;
                    @endphp
                    <tr class="hover:bg-amber-500/5 dark:hover:bg-amber-500/10 transition {{ $activeBorrowing ? 'bg-purple-500/5 dark:bg-purple-500/10' : '' }}">
                        <td class="py-2.5 px-3 text-center border-r border-slate-200 dark:border-slate-800">
                            <input type="checkbox" :value="{{ $archive->id }}" x-model="selected" class="rounded border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-0">
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
                            {{ $archive->period_text ?? ($archive->period_start_date ? $archive->period_start_date->format('M Y') : '-') }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->physical_condition }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->location)
                                <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    <span>{{ $archive->location->full_location }}</span>
                                    @if($archive->rackSlot)
                                        <span class="px-1.5 py-0.2 bg-emerald-500/15 border border-emerald-500/30 rounded text-[10px]">
                                            {{ $archive->rackSlot->slot_code ?? ('S' . $archive->rackSlot->slot_number) }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <a href="{{ route('master.warehouses.layout', ['archive_id' => $archive->id, 'embed' => 1]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/40 animate-pulse hover:bg-rose-500/25 transition shadow-xs" title="Lokasi belum ditentukan! Klik untuk buka Layout Gudang 2D">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                    <span class="underline decoration-dotted underline-offset-2">Belum Ditentukan</span>
                                    <i data-lucide="map-pin" class="w-3 h-3 text-rose-500"></i>
                                </a>
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
                            @elseif($archive->status === 'taken')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/10 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300 border border-indigo-500/40">Diambil (Permanen)</span>
                            @elseif($archive->status === 'destroyed')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/40">Dimusnahkan</span>
                            @endif
                        </td>

                        <td class="py-2.5 px-3 text-right font-mono whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $activeBorrowing)
                                    <button type="button" 
                                            @click="dispatchModalLog = {
                                                id: {{ $activeBorrowing->id }},
                                                archive_title: {{ json_encode($archive->title) }},
                                                box_number: {{ json_encode($archive->box_number ?: 'DRAFT-BOX') }},
                                                location: {{ json_encode($archive->location ? $archive->location->full_location : 'Gudang') }},
                                                borrower_name: {{ json_encode($activeBorrowing->borrower->name ?? 'User') }},
                                                borrower_dept: {{ json_encode($activeBorrowing->borrower->department->name ?? 'Dept') }},
                                                purpose: {{ json_encode($activeBorrowing->purpose) }},
                                                expected_return_date: {{ json_encode($activeBorrowing->expected_return_date ? \Carbon\Carbon::parse($activeBorrowing->expected_return_date)->format('d/m/Y') : 'Hanya Diambil (Permanen)') }},
                                                approval_url: {{ json_encode(($activeBorrowing->approval_file || $activeBorrowing->scan_approval_borrow) ? asset('storage/' . ($activeBorrowing->approval_file ?? $activeBorrowing->scan_approval_borrow)) : null) }}
                                            }"
                                            title="Keluarkan Berkas Fisik Berdasarkan Ajuan Peminjaman PIC Dept" 
                                            class="px-2.5 py-1 rounded bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white border border-purple-400 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-md">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-white"></i>
                                        <span>Keluarkan Arsip</span>
                                    </button>
                                @endif

                                @if($archive->status === 'draft' && (auth()->user()->isPicDept() || auth()->user()->isSuperAdmin()))
                                <a href="{{ route('archives.edit', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded bg-amber-500 hover:bg-amber-400 text-slate-950 border border-amber-600 font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs" title="Edit Draft & Simpan Permanen">
                                    <i data-lucide="edit-3" class="w-3 h-3 text-slate-950"></i>
                                    <span>Edit</span>
                                </a>
                                @endif

                                @if(!auth()->user()->isPicDept())
                                <a href="{{ route('archives.print_sticker', $archive) }}" target="_blank" title="Cetak Label Box Container" class="px-2 py-0.5 rounded bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs">
                                    <i data-lucide="printer" class="w-3 h-3 text-amber-500"></i>
                                    <span>Label</span>
                                </a>
                                @endif
                                <a href="{{ route('archives.show', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-amber-500 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs">
                                    <span>Detail</span>
                                    <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-10 text-center font-mono text-slate-500 dark:text-slate-400 space-y-2">
                            <i data-lucide="folder-search" class="w-10 h-10 mx-auto text-slate-400 dark:text-slate-600"></i>
                            <p class="text-xs font-bold">Tidak ada berkas arsip yang ditemukan berdasarkan kriteria pencarian ini.</p>
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
@endsection
