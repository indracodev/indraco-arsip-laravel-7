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

            <!-- Cetak Custom Label (F9) -->
            <a href="{{ route('archives.print_labels') }}" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm shrink-0" title="Cetak Custom Label Box (F9)">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Cetak Label (F9)</span>
            </a>

            <!-- Buat Draft Pengajuan Arsip (F2) -->
            <a href="{{ route('archives.create') }}" class="px-3 py-1 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5 shrink-0" title="Buat Draft Pengajuan Arsip Baru (F2)">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Buat Draft (F2)</span>
            </a>
        </div>
    </div>

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
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (Revisi)</option>
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
                    <tr class="hover:bg-amber-500/5 dark:hover:bg-amber-500/10 transition">
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
                            {{ $archive->period_text ?? $archive->period_start_date->format('M Y') }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->physical_condition }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->location)
                                <div class="flex items-center gap-1 text-emerald-700 dark:text-emerald-400 font-bold">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    <span>{{ $archive->location->full_location }}</span>
                                </div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic font-normal">Belum Check-in</span>
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
                            @if($archive->status === 'draft')
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

                        <td class="py-2.5 px-3 text-right font-mono whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <a href="{{ route('archives.print_sticker', $archive) }}" target="_blank" title="Cetak Label Box Container" class="px-2 py-0.5 rounded bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs">
                                    <i data-lucide="printer" class="w-3 h-3 text-amber-500"></i>
                                    <span>Label</span>
                                </a>
                                <a href="{{ route('archives.show', $archive) }}" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-amber-500 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs">
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
                {{ $archives->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

