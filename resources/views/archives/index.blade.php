@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Katalog & Booking Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="archiveCatalog()">
    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER (TPanel / TToolBar) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="folder-archive" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    KATALOG ARSIP & PENGAJUAN STORAGE
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
            <button type="button" @click="
                if (selected && selected.length) {
                    if (selectedPrintable.length > 0) {
                        window.openLabelModal(selectedPrintable);
                    } else {
                        alert('Tidak ada box terpilih yang memenuhi syarat cetak label (label dinonaktifkan untuk status antrian, ditarik, dan dimusnahkan).');
                    }
                } else {
                    window.openLabelModal(null);
                }
            " class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm shrink-0 cursor-pointer" title="Cetak Custom Label Box (F9)">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Cetak Label (F9)</span>
            </button>
            @endif

            @if(!auth()->user()->isPicGudang())
            <!-- Impor Multi-Box Excel (Ctrl+I) -->
            <button type="button" 
                    @click="openImportModal = true; $nextTick(() => { if(window.lucide) window.lucide.createIcons(); });" 
                    class="px-3 py-1 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-mono font-black text-xs rounded border border-emerald-700 shadow transition flex items-center gap-1.5 shrink-0 cursor-pointer" 
                    title="Impor Massal Multi-Box & Butir Dokumen via Excel / CSV">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                <span>Impor Multi-Box (Excel)</span>
            </button>

            <!-- Buat Draft Pengajuan Arsip (F2) -->
            <a href="{{ route('archives.create') }}" class="px-3 py-1 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5 shrink-0" title="Buat Draft Pengajuan Arsip Baru (F2)">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Buat Draft (F2)</span>
            </a>
            @endif
        </div>
    </div>

    <!-- PENDING BORROWING REQUESTS NOTIFICATION BANNER (For PIC Gudang & Admin) -->
    @if(isset($pendingBorrowingRequestsCount) && $pendingBorrowingRequestsCount > 0 && (auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()))
    <div class="p-3.5 rounded bg-purple-500/10 border border-purple-500/40 text-purple-900 dark:text-purple-200 text-xs font-mono flex items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-2">
            <i data-lucide="bell-ring" class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0 animate-bounce"></i>
            <div>
                <strong class="font-extrabold text-purple-950 dark:text-purple-100 uppercase">Ajuan Penarikan Berkas Membutuhkan Pengeluaran:</strong>
                Terdapat <span class="font-black underline text-purple-700 dark:text-purple-300">{{ $pendingBorrowingRequestsCount }} ajuan penarikan berkas</span> dari PIC Departemen yang menunggu pengeluaran fisik dokumen dari gudang.
            </div>
        </div>
        <a href="{{ route('archives.index', ['status' => 'borrow_requested']) }}" class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded font-bold text-xs shadow transition flex items-center gap-1 shrink-0">
            <i data-lucide="arrow-right-circle" class="w-3.5 h-3.5"></i>
            <span>Filter Ajuan Penarikan</span>
        </a>
    </div>
    @endif

    <!-- DELPHI GROUPBOX FILTER PANEL (TGroupBox Delphi Desktop Style) -->
    <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm" x-data="{ submitting: false }">
        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
            <i data-lucide="filter" class="w-3.5 h-3.5 text-amber-500"></i>
            Filter & Pencarian Data Katalog
        </legend>

        <form action="{{ route('archives.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 pt-1" @submit="submitting = true">
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
            <div class="space-y-1" style="display: none !important;">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block flex items-center justify-between">
                    <span>DEPARTEMEN</span>
                    @if(auth()->user()->isPicDept())
                        <span class="text-amber-600 dark:text-amber-400 font-bold text-[9px]">[Terkunci]</span>
                    @endif
                </label>
                @if(auth()->user()->isPicDept())
                    @php $myDept = $departments->first(); @endphp
                    <input type="hidden" name="department_id" value="{{ $myDept->id ?? auth()->user()->department_id }}">
                    <select disabled class="w-full py-1 px-2.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-700 dark:text-slate-300 cursor-not-allowed">
                        <option value="{{ $myDept->id ?? auth()->user()->department_id }}" selected>
                            {{ $myDept ? ($myDept->code . ' - ' . $myDept->name) : (auth()->user()->department->name ?? 'Departemen Anda') }}
                        </option>
                    </select>
                @else
                    <select name="department_id" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="">-- Semua Departemen --</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->code }} - {{ $dept->name }}
                        </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- Sub-Department Filter -->
            @php
                $availableSubDepts = collect();
                if (auth()->user()->isPicDept()) {
                    $myDept = $departments->first();
                    $availableSubDepts = $myDept && $myDept->subDepartments ? $myDept->subDepartments : collect();
                } else {
                    foreach($departments as $dept) {
                        if ($dept->subDepartments) {
                            $availableSubDepts = $availableSubDepts->concat($dept->subDepartments);
                        }
                    }
                }
            @endphp
            @if($availableSubDepts->isNotEmpty())
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">SUB-DEPARTEMEN</label>
                <select name="sub_department_id" x-model="filterSubDept" @change="onFilterSubDeptChange()" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Sub-Bagian --</option>
                    @foreach($availableSubDepts as $sdept)
                    <option value="{{ $sdept->id }}" {{ request('sub_department_id') == $sdept->id ? 'selected' : '' }}>
                        {{ $sdept->name }} ({{ $sdept->code }})
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Document Type Filter (Cascading by Sub-Department) -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block flex items-center justify-between">
                    <span>TIPE DOKUMEN</span>
                    <span x-show="filterSubDept" class="text-amber-500 text-[9px] font-mono font-bold" style="display:none;">Filtered</span>
                </label>
                <select name="document_type" x-model="filterDocType" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Tipe Dokumen --</option>
                    <template x-for="t in availableDocTypes" :key="t">
                        <option :value="t" x-text="t" :selected="t === filterDocType"></option>
                    </template>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">STATUS WORKFLOW</label>
                <select name="status" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Status Workflow --</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (Simpan Sementara / Revisi)</option>
                    <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Antrean Verifikasi</option>
                    <option value="approved_booked" {{ request('status') == 'approved_booked' ? 'selected' : '' }}>Approved / Booked</option>
                    <option value="in_warehouse" {{ request('status') == 'in_warehouse' ? 'selected' : '' }}>Di Gudang</option>
                    <option value="pending_extension" {{ request('status') == 'pending_extension' ? 'selected' : '' }}>Pengajuan Perpanjangan</option>
                    <option value="out" {{ request('status') == 'out' || request('status') == 'borrowed' || request('status') == 'taken' ? 'selected' : '' }}>Keluar (Out)</option>
                    <option value="pending_destruction" {{ request('status') == 'pending_destruction' ? 'selected' : '' }}>Antrean Pemusnahan</option>
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

                @if(request()->hasAny(['search', 'department_id', 'sub_department_id', 'document_type', 'status', 'expiry_filter']))
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
                Terpilih: <strong class="text-amber-600 dark:text-amber-400 font-extrabold" x-text="selected.length"></strong> box berkas arsip.
                <template x-if="selectedDrafts.length > 0 && selectedNonDrafts.length > 0">
                    <span class="text-slate-500 dark:text-slate-400">
                        (<span x-text="selectedDrafts.length"></span> draft, <span x-text="selectedNonDrafts.length"></span> non-draft)
                    </span>
                </template>
            </span>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            @if(!auth()->user()->isPicGudang())
            <!-- Batch Borrowing Action (Tarik Berkas) — hanya muncul untuk non-gudang jika ada box yang MEMENUHI SYARAT TARIK (tersimpan di gudang & belum ditarik) -->
            <template x-if="selectedBorrowable.length > 0">
                <button 
                    type="button" 
                    @click="window.location.href = '{{ route('borrowings.create') }}?archive_ids=' + selectedBorrowable.join(',') + '&from=archives{{ request()->has('embed') ? '&embed=1' : '' }}'"
                    class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded border border-purple-700 shadow transition flex items-center gap-1.5 cursor-pointer"
                    title="Ajukan penarikan untuk box arsip yang memenuhi syarat penarikan (tersimpan di gudang & belum ditarik)"
                >
                    <i data-lucide="corner-up-right" class="w-3.5 h-3.5 text-white"></i>
                    <span>Tarik Berkas (<span x-text="selectedBorrowable.length"></span> Box)</span>
                </button>
            </template>
            @endif

            <!-- Batch Delete Drafts Action — hanya muncul jika ada draft terpilih -->
            <template x-if="selectedDrafts.length > 0">
                <form action="{{ route('archives.batch_delete_drafts') }}" method="POST" class="inline" @submit.prevent="
                    const draftCount = selectedDrafts.length;
                    const ok = await window.showConfirmModal({
                        title: 'Hapus Draft Arsip Terpilih',
                        message: 'Apakah Anda yakin ingin menghapus ' + draftCount + ' data draft arsip yang dipilih?' + (selectedNonDrafts.length > 0 ? ' (' + selectedNonDrafts.length + ' berkas non-draft akan diabaikan)' : ''),
                        type: 'danger',
                        confirmText: 'Ya, Hapus ' + draftCount + ' Draft'
                    });
                    if (ok) $el.submit();
                ">
                    @csrf
                    <input type="hidden" name="ids" :value="selectedDrafts.join(',')">
                    @if(request()->has('embed'))
                        <input type="hidden" name="embed" value="1">
                    @endif
                    <button type="submit" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded border border-rose-700 shadow transition flex items-center gap-1.5 cursor-pointer" title="Hapus draft yang dipilih">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-white"></i>
                        <span>Hapus Draft (<span x-text="selectedDrafts.length"></span>)</span>
                    </button>
                </form>
            </template>

            @if(!auth()->user()->isPicDept())
            <!-- Batch Print Labels (Warehouse / Admin) -->
            <button 
                type="button" 
                @click="selectedPrintable.length > 0 ? window.openLabelModal(selectedPrintable) : alert('Tidak ada box terpilih yang memenuhi syarat cetak label (label hanya untuk arsip di gudang atau perpanjangan).')" 
                :disabled="selectedPrintable.length === 0"
                :class="selectedPrintable.length === 0 ? 'opacity-40 cursor-not-allowed bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700' : 'bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 border-amber-600 cursor-pointer shadow'"
                class="px-3 py-1 font-black text-xs rounded border transition flex items-center gap-1.5"
                title="Cetak label box untuk arsip berstatus di gudang atau perpanjangan"
            >
                <i data-lucide="printer" class="w-3.5 h-3.5" :class="selectedPrintable.length === 0 ? 'text-slate-400 dark:text-slate-500' : 'text-slate-950'"></i>
                <span>Cetak Label (<span x-text="selectedPrintable.length"></span>)</span>
            </button>
            @endif
        </div>
    </div>

    <!-- GLOBAL WORKFLOW COLOR LEGEND BAR -->
    <div class="flex items-center flex-wrap gap-2 text-[11px] font-mono py-1.5 px-3 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded select-none shadow-xs">
        <span class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider mr-1 text-[10px] flex items-center gap-1">
            <i data-lucide="palette" class="w-3.5 h-3.5 text-slate-400"></i>
            <span>Pembeda Workflow:</span>
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border-l-4 border-l-amber-500 font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pengajuan (Data Baru)
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-purple-500/15 text-purple-800 dark:text-purple-300 border-l-4 border-l-purple-500 font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Penarikan (Out)
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-500/15 text-blue-800 dark:text-blue-300 border-l-4 border-l-blue-500 font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Perpanjangan
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-rose-500/15 text-rose-800 dark:text-rose-300 border-l-4 border-l-rose-500 font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Pemusnahan
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border-l-2 border-l-emerald-500 font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Di Gudang
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-l-2 border-l-slate-400 font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Draft (Netral)
        </span>
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
                            $curSort = request('sort', '');
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
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700 text-center">
                            <span>BERKAS</span>
                        </th>
                        <th class="py-2.5 px-3 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-xs">
                    <!-- LIVE REALTIME PREPENDED ARCHIVES (Point 4: Auto-sorted to top) -->
                    <template x-for="item in liveNewArchives" :key="'live-arc-' + item.id">
                        <tr class="bg-amber-500/10 dark:bg-amber-500/15 hover:bg-amber-500/20 transition duration-300 border-l-4 border-l-amber-500">
                            <td class="py-2.5 px-3 text-center border-r border-slate-200 dark:border-slate-800">
                                <input type="checkbox" :value="item.id" x-model="selected" class="rounded border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-0">
                            </td>

                            <td class="py-2.5 px-3 font-mono text-amber-700 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 font-black text-[9px] uppercase tracking-wider animate-pulse">BARU</span>
                                    <span x-text="item.box_number || '- Belum ada code -'"></span>
                                </div>
                            </td>

                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                                <div class="space-y-0.5">
                                    <a :href="item.url" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block font-mono" x-text="item.title"></a>
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-mono flex-wrap">
                                        <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold" x-text="item.dept_code || 'GEN'"></span>
                                        <template x-if="item.sub_dept_name">
                                            <span class="px-1.5 py-0.2 rounded bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30 font-bold text-[10px]" x-text="item.sub_dept_name"></span>
                                        </template>
                                        <template x-if="item.document_type">
                                            <span class="px-1.5 py-0.2 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 font-bold text-[10px]" x-text="item.document_type"></span>
                                        </template>
                                        <span x-text="'by ' + (item.creator_name || 'User')"></span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap" x-text="item.periode_doc || '-'"></td>

                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">Baik / Terawat</td>

                            <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <span class="text-slate-600 dark:text-slate-300 font-bold" x-text="item.location || 'Belum Ditentukan'"></span>
                            </td>

                            <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap" x-text="item.retention_expiry || '-'"></td>

                            <td class="py-2.5 px-3 font-mono border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border"
                                      :class="{
                                          'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300': item.status === 'draft',
                                          'bg-amber-500/20 text-amber-800 dark:text-amber-300 border-amber-500/50': item.status === 'pending_verification',
                                          'bg-blue-500/20 text-blue-800 dark:text-blue-300 border-blue-500/50': item.status === 'approved_booked',
                                          'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border-emerald-500/50': item.status === 'in_warehouse'
                                      }"
                                      x-text="item.status_label || item.status">
                                </span>
                            </td>

                            <td class="py-2.5 px-3 font-mono text-center border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <template x-if="item.has_files">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40">
                                        <i data-lucide="paperclip" class="w-3 h-3 text-emerald-600 dark:text-emerald-400"></i>
                                        <span>Ada</span>
                                    </span>
                                </template>
                                <template x-if="!item.has_files">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-900 text-slate-400 border border-slate-200 dark:border-slate-800">
                                        <span>-</span>
                                    </span>
                                </template>
                            </td>

                            <td class="py-2.5 px-3 text-right font-mono whitespace-nowrap">
                                <a :href="item.url" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-amber-500 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs">
                                    <span>Detail</span>
                                    <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </a>
                            </td>
                        </tr>
                    </template>

                    @forelse($archives as $archive)
                    @php
                        $activeBorrowing = $archive->borrowingLogs ? $archive->borrowingLogs->whereIn('status', ['requested', 'dept_approved', 'approved'])->first() : null;
                        $rejectedBorrowing = (!$activeBorrowing && $archive->borrowingLogs) ? $archive->borrowingLogs->where('status', 'rejected')->first() : null;
                        $archiveFiles = [];
                        if ($archive->scan_input_form) {
                            $archiveFiles[] = [
                                'name' => 'Scan Formulir Input',
                                'category' => 'Formulir Pendaftaran Fisik',
                                'url' => app_storage_url($archive->scan_input_form),
                                'stream_url' => app_preview_stream_url($archive->scan_input_form),
                                'raw_path' => $archive->scan_input_form,
                                'filename' => basename($archive->scan_input_form),
                                'ext' => strtolower(pathinfo($archive->scan_input_form, PATHINFO_EXTENSION)),
                            ];
                        }
                        if ($archive->file_path) {
                            $archiveFiles[] = [
                                'name' => 'Lampiran Digital Dokumen',
                                'category' => 'Softcopy Dokumen',
                                'url' => app_storage_url($archive->file_path),
                                'stream_url' => app_preview_stream_url($archive->file_path),
                                'raw_path' => $archive->file_path,
                                'filename' => basename($archive->file_path),
                                'ext' => strtolower(pathinfo($archive->file_path, PATHINFO_EXTENSION)),
                            ];
                        }
                        if ($archive->scan_approval_input) {
                            $archiveFiles[] = [
                                'name' => 'Scan Approval Input',
                                'category' => 'Bukti Persetujuan PIC',
                                'url' => app_storage_url($archive->scan_approval_input),
                                'stream_url' => app_preview_stream_url($archive->scan_approval_input),
                                'raw_path' => $archive->scan_approval_input,
                                'filename' => basename($archive->scan_approval_input),
                                'ext' => strtolower(pathinfo($archive->scan_approval_input, PATHINFO_EXTENSION)),
                            ];
                        }
                        if ($archive->scan_extension_form) {
                            $archiveFiles[] = [
                                'name' => 'Scan Form Perpanjangan',
                                'category' => 'Perpanjangan Masa Simpan',
                                'url' => app_storage_url($archive->scan_extension_form),
                                'stream_url' => app_preview_stream_url($archive->scan_extension_form),
                                'raw_path' => $archive->scan_extension_form,
                                'filename' => basename($archive->scan_extension_form),
                                'ext' => strtolower(pathinfo($archive->scan_extension_form, PATHINFO_EXTENSION)),
                            ];
                        }
                        // Lampiran berkas formulir permohonan / persetujuan penarikan
                        if ($archive->borrowingLogs && $archive->borrowingLogs->isNotEmpty()) {
                            $existingPaths = array_column($archiveFiles, 'raw_path');
                            foreach ($archive->borrowingLogs as $bLogItem) {
                                if (!empty($bLogItem->effective_approval_file) && !in_array($bLogItem->effective_approval_file, $existingPaths)) {
                                    $existingPaths[] = $bLogItem->effective_approval_file;
                                    $archiveFiles[] = [
                                        'name' => 'Scan Formulir Approval Penarikan',
                                        'category' => 'Formulir Persetujuan Penarikan',
                                        'url' => app_storage_url($bLogItem->effective_approval_file),
                                        'stream_url' => app_preview_stream_url($bLogItem->effective_approval_file),
                                        'raw_path' => $bLogItem->effective_approval_file,
                                        'filename' => basename($bLogItem->effective_approval_file),
                                        'ext' => strtolower(pathinfo($bLogItem->effective_approval_file, PATHINFO_EXTENSION)),
                                    ];
                                    break;
                                }
                            }
                        }
                        $hasFiles = count($archiveFiles) > 0;

                        // Determine Global Workflow Row Styles & Badges
                        $rowColorClass = '';
                        $isNewSubmission = false;

                        if ($archive->status === 'draft') {
                            if (!empty($archive->rejection_note)) {
                                $rowColorClass = 'border-l-4 border-l-rose-500 bg-rose-500/[0.08] dark:bg-rose-500/15 hover:bg-rose-500/20';
                            } else {
                                $rowColorClass = 'border-l-2 border-l-slate-300 dark:border-l-slate-700 bg-slate-50/40 dark:bg-slate-900/20 hover:bg-slate-100/60';
                            }
                        } elseif ($archive->status === 'pending_verification') {
                            $rowColorClass = 'border-l-4 border-l-amber-500 bg-amber-500/[0.07] dark:bg-amber-500/15 hover:bg-amber-500/20';
                            $isNewSubmission = true;
                        } elseif ($activeBorrowing || in_array($archive->status, ['borrowed', 'taken', 'out'])) {
                            $rowColorClass = 'border-l-4 border-l-purple-500 bg-purple-500/[0.07] dark:bg-purple-500/15 hover:bg-purple-500/20';
                        } elseif ($rejectedBorrowing && $archive->status === 'in_warehouse') {
                            $rowColorClass = 'border-l-4 border-l-rose-500 bg-rose-500/[0.05] dark:bg-rose-500/10 hover:bg-rose-500/15';
                        } elseif ($archive->status === 'pending_extension') {
                            $rowColorClass = 'border-l-4 border-l-blue-500 bg-blue-500/[0.07] dark:bg-blue-500/15 hover:bg-blue-500/20';
                        } elseif (in_array($archive->status, ['pending_destruction', 'destroyed'])) {
                            $rowColorClass = 'border-l-4 border-l-rose-500 bg-rose-500/[0.07] dark:bg-rose-500/15 hover:bg-rose-500/20';
                        } elseif ($archive->status === 'in_warehouse') {
                            $rowColorClass = 'border-l-2 border-l-emerald-500/50 hover:bg-emerald-500/5';
                        } else {
                            $rowColorClass = 'hover:bg-slate-50 dark:hover:bg-slate-900/30';
                        }
                    @endphp
                    <tr class="transition duration-150 {{ $rowColorClass }}">
                        <td class="py-2.5 px-3 text-center border-r border-slate-200 dark:border-slate-800">
                            @php
                                $isPulledOrActive = $activeBorrowing || in_array($archive->status, ['borrowed', 'taken', 'out']);
                            @endphp
                            <input type="checkbox" :value="{{ $archive->id }}" x-model="selected" class="rounded border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-0"
                                   @if($isPulledOrActive) title="Arsip sedang ditarik / keluar dari gudang (tidak dapat ditarik kembali)" @endif>
                        </td>

                        <td class="py-2.5 px-3 font-mono text-amber-700 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($isNewSubmission)
                                    <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 font-black text-[9px] uppercase tracking-wider animate-pulse shadow-xs" title="Pengajuan Arsip Baru Menunggu Verifikasi">BARU</span>
                                @elseif($activeBorrowing || in_array($archive->status, ['borrowed', 'taken', 'out']))
                                    <span class="px-1.5 py-0.2 rounded bg-purple-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Berkas Sedang Ditarik">DITARIK</span>
                                @elseif($rejectedBorrowing && $archive->status === 'in_warehouse')
                                    <span class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[9px] uppercase tracking-wider shadow-xs animate-pulse" title="Pengajuan Penarikan Berkas Ditolak Gudang">TOLAK TARIK</span>
                                @elseif($archive->status === 'pending_extension')
                                    <span class="px-1.5 py-0.2 rounded bg-blue-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Menunggu Konfirmasi Perpanjangan">PERPANJANG</span>
                                @elseif(in_array($archive->status, ['pending_destruction', 'destroyed']))
                                    <span class="px-1.5 py-0.2 rounded bg-rose-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Pemusnahan Berkas">MUSNAH</span>
                                @endif

                                @if($archive->box_number)
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="qr-code" class="w-3.5 h-3.5 text-amber-500"></i>
                                        <span>{{ $archive->box_number }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 italic font-normal text-[11px]">- Belum ada box code -</span>
                                @endif
                            </div>
                        </td>

                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                            <div class="space-y-0.5">
                                <a href="{{ route('archives.show', $archive) }}" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block font-mono">
                                    {{ $archive->title }}
                                </a>
                                @if($archive->status === 'draft' && !empty($archive->rejection_note))
                                    <div class="mt-1 p-1.5 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-[10px] font-mono flex items-start gap-1.5 shadow-2xs">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5"></i>
                                        <div>
                                            <span class="font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300">Catatan Revisi Gudang:</span>
                                            <span class="block text-slate-700 dark:text-slate-300">{{ $archive->rejection_note }}</span>
                                        </div>
                                    </div>
                                @endif
                                @if($rejectedBorrowing && $archive->status === 'in_warehouse')
                                    <div class="mt-1 p-1.5 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-[10px] font-mono flex items-start gap-1.5 shadow-2xs">
                                        <i data-lucide="ban" class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5"></i>
                                        <div>
                                            <span class="font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300">Penarikan Ditolak Gudang:</span>
                                            <span class="block text-slate-700 dark:text-slate-300">{{ $rejectedBorrowing->approval_notes ?: 'Formulir atau berkas permohonan penarikan tidak memenuhi syarat.' }}</span>
                                        </div>
                                    </div>
                                @endif
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-mono flex-wrap">
                                    <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold">
                                        {{ $archive->department->code ?? 'GEN' }}
                                    </span>
                                    @if($archive->subDepartment)
                                        <span class="px-1.5 py-0.2 rounded bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30 font-bold text-[10px]" title="Sub-Departemen: {{ $archive->subDepartment->name }}">
                                            {{ $archive->subDepartment->code ?? $archive->subDepartment->name }}
                                        </span>
                                    @endif
                                    @if($archive->document_type)
                                        <span class="px-1.5 py-0.2 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 font-bold text-[10px]" title="Tipe Dokumen">
                                            {{ $archive->document_type }}
                                        </span>
                                    @endif
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
                            @if(in_array($archive->status, ['taken', 'borrowed', 'out']))
                                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 font-bold text-left">
                                    <i data-lucide="log-out" class="w-3.5 h-3.5 text-purple-500 shrink-0"></i>
                                    <span>- (Sudah Ditarik)</span>
                                </div>
                            @elseif($archive->location)
                                @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                                <button 
                                    type="button"
                                    @click="openQuickSlotModal({
                                        id: {{ $archive->id }},
                                        title: {{ json_encode($archive->title) }},
                                        box_number: {{ json_encode($archive->box_number ?: 'Belum ada code') }},
                                        department_id: {{ $archive->department_id ?? 0 }},
                                        department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                        department_name: {{ json_encode($archive->department->name ?? '') }}
                                    })"
                                    class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold hover:underline cursor-pointer text-left"
                                    title="Klik untuk ubah / lihat penempatan slot rak 2D"
                                >
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                                    <span>{{ $archive->display_location }}</span>
                                </button>
                                @else
                                <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold text-left">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                                    <span>{{ $archive->display_location }}</span>
                                </div>
                                @endif
                            @else
                                @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                                    @if(in_array($archive->status, ['approved_booked', 'in_warehouse', 'pending_extension']))
                                    <button 
                                        type="button" 
                                        @click="openQuickSlotModal({
                                            id: {{ $archive->id }},
                                            title: {{ json_encode($archive->title) }},
                                            box_number: {{ json_encode($archive->box_number ?: 'Belum ada code') }},
                                            department_id: {{ $archive->department_id ?? 0 }},
                                            department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                            department_name: {{ json_encode($archive->department->name ?? '') }}
                                        })"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/40 animate-pulse hover:bg-rose-500/25 transition shadow-xs cursor-pointer" 
                                        title="Lokasi belum ditentukan! Klik untuk buka Alokasi Slot Rak 2D Cepat"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                        <span class="underline decoration-dotted underline-offset-2">Belum Ditentukan</span>
                                        <i data-lucide="map-pin" class="w-3 h-3 text-rose-500"></i>
                                    </button>
                                    @else
                                    <button 
                                        type="button" 
                                        disabled
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500 border border-slate-200 dark:border-slate-700 opacity-60 cursor-not-allowed select-none" 
                                        title="Lokasi belum dapat ditentukan: Harap verifikasi & setujui pengajuan arsip terlebih dahulu pada tombol Antrean Verifikasi"
                                    >
                                        <i data-lucide="lock" class="w-3 h-3 text-slate-400"></i>
                                        <span>Belum Ditentukan</span>
                                    </button>
                                    @endif
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/30 select-none" title="Lokasi rak gudang belum ditentukan">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    <span>Belum Ditentukan</span>
                                    <i data-lucide="map-pin" class="w-3 h-3 text-rose-400"></i>
                                </span>
                                @endif
                            @endif
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->retention_expiry_date)
                                @php
                                    $expiryEndOfMonth = \Carbon\Carbon::parse($archive->retention_expiry_date)->endOfMonth()->endOfDay();
                                    $isExpired = now()->gt($expiryEndOfMonth);
                                    $daysToExpiry = \Carbon\Carbon::today()->diffInDays($expiryEndOfMonth, false);
                                    $isSameMonth = !$isExpired && ($daysToExpiry <= 30);
                                    $isNear = !$isExpired && ($daysToExpiry <= 90);
                                    $approvedExt = $archive->latest_approved_extension;
                                @endphp
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if($isExpired)
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 font-extrabold animate-warning-blink">
                                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i>
                                                <span>{{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') }}</span>
                                                <span class="text-[10px] font-bold text-rose-700 dark:text-rose-300">(Lewat {{ abs($daysToExpiry) }} Hari)</span>
                                            </div>
                                        @elseif($isSameMonth)
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-400 font-bold">
                                                <i data-lucide="clock" class="w-3 h-3 text-amber-500 shrink-0"></i>
                                                <span>{{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') }}</span>
                                                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-300">(H-{{ $daysToExpiry }})</span>
                                            </div>
                                        @elseif($isNear)
                                            <span class="text-amber-600 dark:text-amber-400 font-semibold">
                                                {{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') }}
                                                ({{ $archive->retention_display }})
                                            </span>
                                        @else
                                            <span class="text-slate-700 dark:text-slate-300 font-medium">
                                                {{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') }}
                                                ({{ $archive->retention_display }})
                                            </span>
                                        @endif

                                        @if($approvedExt)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30" title="Masa simpan telah diperpanjang {{ $approvedExt->duration_label }}">
                                                <i data-lucide="clock" class="w-2.5 h-2.5 text-purple-500"></i>
                                                <span>{{ $approvedExt->duration_label }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500">-</span>
                            @endif
                        </td>

                        <td class="py-2.5 px-3 font-mono border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if(auth()->user()->isSuperAdmin())
                                <button 
                                    type="button" 
                                    @click="openSuperAdminStatusModal({
                                        id: {{ $archive->id }},
                                        box_number: {{ json_encode($archive->box_number ?: 'Pending') }},
                                        raw_box_number: {{ json_encode($archive->box_number) }},
                                        title: {{ json_encode($archive->title) }},
                                        department: {{ json_encode($archive->department->name ?? 'Dept') }},
                                        department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                        status: {{ json_encode($archive->status) }},
                                        status_label: {{ json_encode($archive->status_label ?? $archive->status) }},
                                        location: {{ json_encode($archive->display_location) }},
                                        has_scan_input: {{ !empty($archive->scan_input_form) ? 'true' : 'false' }},
                                        has_file_path: {{ !empty($archive->file_path) ? 'true' : 'false' }},
                                        has_approval: {{ !empty($archive->scan_approval_input) ? 'true' : 'false' }},
                                        files: {{ json_encode($archiveFiles) }},
                                        scan_input_form_url: {{ json_encode(app_storage_url($archive->scan_input_form)) }},
                                        file_path_url: {{ json_encode(app_storage_url($archive->file_path)) }}
                                    })"
                                    class="px-2 py-0.5 rounded text-[10px] font-bold inline-flex items-center gap-1 transition shadow-xs cursor-pointer hover:scale-105 group border"
                                    :class="{
                                        'bg-slate-200 hover:bg-slate-300 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700': '{{ $archive->status }}' === 'draft',
                                        'bg-amber-500/20 hover:bg-amber-500/35 text-amber-800 dark:text-amber-300 border-amber-500/50': '{{ $archive->status }}' === 'pending_verification',
                                        'bg-blue-500/20 hover:bg-blue-500/35 text-blue-800 dark:text-blue-300 border-blue-500/50': '{{ $archive->status }}' === 'approved_booked',
                                        'bg-emerald-500/20 hover:bg-emerald-500/35 text-emerald-800 dark:text-emerald-300 border-emerald-500/50': '{{ $archive->status }}' === 'in_warehouse',
                                        'bg-purple-500/20 hover:bg-purple-500/35 text-purple-800 dark:text-purple-300 border-purple-500/50': '{{ $archive->status }}' === 'pending_extension',
                                        'bg-indigo-500/20 hover:bg-indigo-500/35 text-indigo-800 dark:text-indigo-300 border-indigo-500/50': '{{ $archive->status }}' === 'borrowed' || '{{ $archive->status }}' === 'taken',
                                        'bg-orange-500/20 hover:bg-orange-500/35 text-orange-800 dark:text-orange-300 border-orange-500/50': '{{ $archive->status }}' === 'pending_destruction',
                                        'bg-rose-500/20 hover:bg-rose-500/35 text-rose-800 dark:text-rose-300 border-rose-500/50': '{{ $archive->status }}' === 'destroyed'
                                    }"
                                    title="Super Admin: Klik untuk ubah status workflow bebas"
                                >
                                    @if($archive->status === 'draft')
                                        <i data-lucide="edit-3" class="w-3 h-3 text-slate-600 dark:text-slate-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Draft</span>
                                    @elseif($archive->status === 'pending_verification')
                                        <i data-lucide="shield-check" class="w-3 h-3 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Antrean Verifikasi</span>
                                    @elseif($archive->status === 'approved_booked')
                                        <i data-lucide="check" class="w-3 h-3 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Approved / Booked</span>
                                    @elseif($archive->status === 'in_warehouse')
                                        <i data-lucide="archive" class="w-3 h-3 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Di Gudang</span>
                                    @elseif($archive->status === 'pending_extension')
                                        <i data-lucide="clock" class="w-3 h-3 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Pengajuan Perpanjangan</span>
                                    @elseif($archive->status === 'borrowed' || $archive->status === 'taken')
                                        <i data-lucide="log-out" class="w-3 h-3 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Keluar (Out)</span>
                                    @elseif($archive->status === 'pending_destruction')
                                        <i data-lucide="shield-alert" class="w-3 h-3 text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">{{ $archive->status_label }}</span>
                                    @elseif($archive->status === 'destroyed')
                                        <i data-lucide="trash-2" class="w-3 h-3 text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Dimusnahkan</span>
                                    @endif
                                    <i data-lucide="sliders" class="w-2.5 h-2.5 opacity-60 ml-0.5 text-amber-500"></i>
                                </button>
                            @else
                                @if($archive->status === 'draft')
                                    @if(!empty($archive->rejection_note))
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-300 dark:border-rose-700 inline-flex items-center gap-1 shadow-xs" title="Catatan Revisi Gudang: {{ $archive->rejection_note }}">
                                            <i data-lucide="alert-circle" class="w-3 h-3 text-rose-600 dark:text-rose-400"></i>
                                            <span>Perlu Revisi</span>
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">Draft</span>
                                    @endif
                                @elseif($archive->status === 'pending_verification')
                                    @if(auth()->user()->isPicGudang())
                                        <button 
                                            type="button" 
                                            @click="openVerifyModal({
                                                id: {{ $archive->id }},
                                                title: {{ json_encode($archive->title) }},
                                                department_id: {{ $archive->department_id ?? 0 }},
                                                department: {{ json_encode($archive->department->name ?? 'Dept') }},
                                                department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                                creator: {{ json_encode($archive->creator->name ?? 'User') }},
                                                periode: {{ json_encode($archive->effective_periode) }},
                                                condition: {{ json_encode($archive->physical_condition) }},
                                                files: {{ json_encode($archiveFiles) }},
                                                has_files: {{ $hasFiles ? 'true' : 'false' }},
                                                edit_url: {{ json_encode(route('archives.edit', ['archive' => $archive->id])) }}
                                            })"
                                            class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 hover:bg-amber-500/35 text-amber-800 dark:text-amber-300 border border-amber-500/50 inline-flex items-center gap-1 transition shadow-xs cursor-pointer hover:scale-105 group"
                                            title="Klik untuk verifikasi pengajuan & generate Nomor Box Arsip"
                                        >
                                            <i data-lucide="shield-check" class="w-3 h-3 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform"></i>
                                            <span class="underline decoration-dotted underline-offset-2">Antrean Verifikasi</span>
                                        </button>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/40">Antrean Verifikasi</span>
                                    @endif
                                @elseif($archive->status === 'approved_booked')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/40">Approved / Booked</span>
                                @elseif($archive->status === 'in_warehouse')
                                    @if(auth()->user()->isPicGudang())
                                        <button 
                                            type="button" 
                                            @click="openCheckoutModal({
                                                id: {{ $archive->id }},
                                                box_number: {{ json_encode($archive->box_number ?: 'Pending') }},
                                                title: {{ json_encode($archive->title) }},
                                                department: {{ json_encode($archive->department->name ?? 'Dept') }},
                                                department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                                location: {{ json_encode($archive->display_location) }}
                                            })"
                                            class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 hover:bg-emerald-500/35 text-emerald-800 dark:text-emerald-300 border border-emerald-500/50 inline-flex items-center gap-1 transition shadow-xs cursor-pointer hover:scale-105 group"
                                            title="Klik untuk proses pengeluaran berkas (Out / Keluar)"
                                        >
                                            <i data-lucide="log-out" class="w-3 h-3 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform"></i>
                                            <span class="underline decoration-dotted underline-offset-2">Di Gudang</span>
                                        </button>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/40">Di Gudang</span>
                                    @endif
                                @elseif($archive->status === 'pending_extension')
                                    <a href="{{ route('destructions.index', ['view' => 'extensions']) }}" class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/40 inline-flex items-center gap-1 hover:bg-purple-500/20 transition" title="Lihat di Antrean Perpanjangan Gudang">
                                        <i data-lucide="clock" class="w-3 h-3 text-purple-500"></i>
                                        <span>Pengajuan Perpanjangan</span>
                                    </a>
                                @elseif($archive->status === 'borrowed' || $archive->status === 'taken')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/40 inline-flex items-center gap-1">
                                        <i data-lucide="log-out" class="w-3 h-3 text-purple-500"></i>
                                        <span>Keluar (Out)</span>
                                    </span>
                                @elseif($archive->status === 'pending_destruction')
                                    @if(auth()->user()->isPicGudang())
                                            <button 
                                                type="button" 
                                                @click="openSuperAdminStatusModal({
                                                    id: {{ $archive->id }},
                                                    box_number: {{ json_encode($archive->box_number ?: 'Pending') }},
                                                    raw_box_number: {{ json_encode($archive->box_number) }},
                                                    title: {{ json_encode($archive->title) }},
                                                    department: {{ json_encode($archive->department->name ?? 'Dept') }},
                                                    department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                                    status: {{ json_encode($archive->status) }},
                                                    status_label: {{ json_encode($archive->status_label ?? $archive->status) }},
                                                    location: {{ json_encode($archive->display_location) }},
                                                    has_scan_input: {{ !empty($archive->scan_input_form) ? 'true' : 'false' }},
                                                    has_file_path: {{ !empty($archive->file_path) ? 'true' : 'false' }},
                                                    has_approval: {{ !empty($archive->scan_approval_input) ? 'true' : 'false' }}
                                                })"
                                            class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-500/20 hover:bg-orange-500/35 text-orange-800 dark:text-orange-300 border border-orange-500/50 inline-flex items-center gap-1 transition shadow-xs cursor-pointer hover:scale-105 group"
                                            title="Klik untuk proses / ubah status Pengajuan Pemusnahan"
                                        >
                                            <i data-lucide="shield-alert" class="w-3 h-3 text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform"></i>
                                            <span class="underline decoration-dotted underline-offset-2">{{ $archive->status_label }}</span>
                                        </button>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-500/10 text-orange-800 dark:bg-orange-500/20 dark:text-orange-300 border border-orange-500/40 inline-flex items-center gap-1">
                                            <i data-lucide="shield-alert" class="w-3 h-3 text-orange-500"></i>
                                            <span>{{ $archive->status_label }}</span>
                                        </span>
                                    @endif
                                @elseif($archive->status === 'destroyed')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/40">Dimusnahkan</span>
                                @endif
                            @endif
                        </td>

                        <!-- BERKAS DIGITAL & SCAN PREVIEW COLUMN -->
                        <td class="py-2.5 px-3 font-mono text-center border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($hasFiles)
                                <button 
                                    type="button" 
                                    @click="dmsPreviewFile({
                                        box_number: {{ json_encode($archive->box_number ?: 'DRAFT-BOX') }},
                                        title: {{ json_encode($archive->title) }},
                                        department: {{ json_encode($archive->department->code ?? 'GEN') }},
                                        files: {{ json_encode($archiveFiles) }}
                                    })"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40 transition shadow-xs cursor-pointer group"
                                    title="Klik untuk preview berkas digital & scan formulir ({{ count($archiveFiles) }} berkas)"
                                >
                                    <i data-lucide="paperclip" class="w-3 h-3 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform"></i>
                                    <span class="underline decoration-dotted underline-offset-2">Ada</span>
                                    <span class="text-[9px] px-1 py-0.2 rounded-full bg-emerald-600 text-white font-black leading-none ml-0.5">{{ count($archiveFiles) }}</span>
                                </button>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-900 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-800 select-none">
                                    <i data-lucide="minus-circle" class="w-3 h-3 text-slate-400 dark:text-slate-600"></i>
                                    <span>Tidak Ada</span>
                                </span>
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
                                                location: {{ json_encode($archive->display_location) }},
                                                borrower_name: {{ json_encode($activeBorrowing->borrower->name ?? 'User') }},
                                                borrower_dept: {{ json_encode($activeBorrowing->borrower->department->name ?? 'Dept') }},
                                                purpose: {{ json_encode($activeBorrowing->purpose) }},
                                                expected_return_date: {{ json_encode($activeBorrowing->expected_return_date ? \Carbon\Carbon::parse($activeBorrowing->expected_return_date)->format('d/m/Y') : 'Hanya Diambil (Permanen)') }},
                                                approval_url: {{ json_encode($activeBorrowing->effective_approval_file ? app_storage_url($activeBorrowing->effective_approval_file) : null) }},
                                                approval_stream: {{ json_encode($activeBorrowing->effective_approval_file ? app_preview_stream_url($activeBorrowing->effective_approval_file) : null) }},
                                                approval_raw: {{ json_encode($activeBorrowing->effective_approval_file) }},
                                                approval_ext: {{ json_encode($activeBorrowing->effective_approval_file ? strtolower(pathinfo($activeBorrowing->effective_approval_file, PATHINFO_EXTENSION)) : 'pdf') }}
                                            }"
                                            title="Keluarkan Berkas Fisik Berdasarkan Ajuan Penarikan PIC Dept" 
                                            class="px-2.5 py-1 rounded bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white border border-purple-400 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-md">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-white"></i>
                                        <span>Keluarkan Arsip</span>
                                    </button>
                                @endif

                                {{-- Tombol Penarikan Box di Gudang (Hanya jika tersimpan di gudang & tidak sedang ditarik) --}}
                                @if(!$activeBorrowing && $archive->status === 'in_warehouse' && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || (auth()->user()->isPicDept() && (int)$archive->department_id === (int)auth()->user()->department_id)))
                                    @if($rejectedBorrowing)
                                    <a href="{{ route('borrowings.create', array_merge(['archive_ids' => $archive->id, 'from' => 'archives'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded bg-amber-500 hover:bg-amber-400 text-slate-950 border border-amber-600 font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs" title="Penarikan sebelumnya ditolak. Klik untuk ajukan penarikan ulang dengan melengkapi syarat">
                                        <i data-lucide="rotate-ccw" class="w-3 h-3 text-slate-950"></i>
                                        <span>Tarik Ulang</span>
                                    </a>
                                    @else
                                    <a href="{{ route('borrowings.create', array_merge(['archive_ids' => $archive->id, 'from' => 'archives'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded bg-purple-500/15 hover:bg-purple-500/25 text-purple-700 dark:text-purple-300 border border-purple-500/40 font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs" title="Ajukan Penarikan Box Ini">
                                        <i data-lucide="corner-up-right" class="w-3 h-3 text-purple-600 dark:text-purple-400"></i>
                                        <span>Tarik</span>
                                    </a>
                                    @endif
                                @elseif($activeBorrowing && (auth()->user()->isPicDept() && (int)$archive->department_id === (int)auth()->user()->department_id))
                                    <span class="px-2 py-0.5 rounded bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-500/30 font-bold text-[10px] inline-flex items-center gap-1 select-none" title="Sedang dalam proses penarikan">
                                        <i data-lucide="clock" class="w-2.5 h-2.5 text-purple-500"></i>
                                        <span>Sedang Ditarik</span>
                                    </span>
                                @endif

                                @if(!auth()->user()->isPicGudang() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || (auth()->user()->isPicDept() && (int)$archive->department_id === (int)auth()->user()->department_id && $archive->status === 'draft')))
                                <a href="{{ route('archives.edit', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded {{ !empty($archive->rejection_note) ? 'bg-rose-600 hover:bg-rose-500 text-white border border-rose-700 animate-pulse' : 'bg-amber-500 hover:bg-amber-400 text-slate-950 border border-amber-600' }} font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs" title="{{ !empty($archive->rejection_note) ? 'Perbaiki Pengajuan Sesuai Catatan Revisi Gudang' : 'Edit Data Berkas Arsip' }}">
                                    <i data-lucide="{{ !empty($archive->rejection_note) ? 'alert-circle' : 'edit-3' }}" class="w-3 h-3"></i>
                                    <span>{{ !empty($archive->rejection_note) ? 'Revisi & Ajukan' : 'Edit' }}</span>
                                </a>

                                {{-- Tombol Hapus Draft Arsip (Poin 3) --}}
                                <form action="{{ route('archives.destroy', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            data-confirm="Apakah Anda yakin ingin menghapus data draft arsip [{{ $archive->title }}]?"
                                            data-confirm-title="Hapus Draft Berkas Arsip"
                                            data-confirm-type="danger"
                                            data-confirm-btn="Ya, Hapus"
                                            class="px-2 py-0.5 rounded bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs cursor-pointer" title="Hapus Draft Berkas Arsip">
                                        <i data-lucide="trash-2" class="w-3 h-3 text-rose-500"></i>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                                @endif

                                @if(!auth()->user()->isPicDept())
                                    @php
                                        $hasRackLocation = !empty($archive->warehouse_location_id);
                                        $canPrintLabel = in_array($archive->status, ['approved_booked', 'in_warehouse', 'pending_extension']) && $hasRackLocation;
                                    @endphp
                                    @if(!$canPrintLabel)
                                        <button 
                                            type="button" 
                                            disabled 
                                            title="{{ !$hasRackLocation ? 'Label dinonaktifkan: Lokasi rak gudang belum ditentukan (tahap verifikasi/approval atau belum check-in ke rak)' : 'Label dinonaktifkan: Hanya dapat dicetak untuk arsip yang tersimpan di rak gudang atau perpanjangan (status saat ini: ' . $archive->status_label . ')' }}" 
                                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 border border-slate-200 dark:border-slate-800 text-[11px] font-bold inline-flex items-center gap-1 shadow-2xs opacity-40 cursor-not-allowed"
                                        >
                                            <i data-lucide="printer" class="w-3 h-3 text-slate-400 dark:text-slate-600"></i>
                                            <span>Label</span>
                                        </button>
                                    @elseif($hasFiles)
                                        <button type="button" @click="window.openLabelModal({{ $archive->id }})" title="Cetak Label Box Container" class="px-2 py-0.5 rounded bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs cursor-pointer">
                                            <i data-lucide="printer" class="w-3 h-3 text-amber-500"></i>
                                            <span>Label</span>
                                        </button>
                                    @else
                                        <button 
                                            type="button" 
                                            @click="openIncompleteFileAlert({
                                                title: {{ json_encode($archive->title) }},
                                                box_number: {{ json_encode($archive->box_number ?: 'Draft Pengajuan') }},
                                                edit_url: {{ json_encode(route('archives.edit', ['archive' => $archive->id])) }}
                                            })" 
                                            title="Label Tidak Dapat Dicetak: Berkas belum lengkap!" 
                                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-300 dark:border-slate-700 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs cursor-pointer hover:bg-rose-500/10 hover:text-rose-600 hover:border-rose-500/30 opacity-70"
                                        >
                                            <i data-lucide="printer" class="w-3 h-3 text-slate-400 dark:text-slate-500"></i>
                                            <span>Label</span>
                                        </button>
                                    @endif
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
                        <td colspan="10" class="py-10 text-center font-mono text-slate-500 dark:text-slate-400 space-y-2">
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
                        <p class="text-[11px] text-slate-500">Konfirmasi pengeluaran dokumen berdasarkan ajuan penarikan PIC Dept.</p>
                    </div>
                </div>
                <button type="button" @click="dispatchModalLog = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Detail Penarikan & Arsip -->
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
                            <span class="font-bold">Pemohon Penarikan:</span>
                            <span class="font-black" x-text="dispatchModalLog.borrower_name + ' (' + dispatchModalLog.borrower_dept + ')'"></span>
                        </div>
                        <template x-if="dispatchModalLog.expected_return_date && dispatchModalLog.expected_return_date !== 'Hanya Diambil (Permanen)'">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold">Estimasi Pengembalian:</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400" x-text="dispatchModalLog.expected_return_date"></span>
                            </div>
                        </template>
                        <template x-if="!dispatchModalLog.expected_return_date || dispatchModalLog.expected_return_date === 'Hanya Diambil (Permanen)'">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold">Sifat Penarikan:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">Penarikan Berkas (Tanpa Pengembalian)</span>
                            </div>
                        </template>
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
                            <button type="button" 
                                    @click="dmsPreviewFile({ 
                                        url: dispatchModalLog.approval_url, 
                                        stream_url: dispatchModalLog.approval_stream,
                                        raw_path: dispatchModalLog.approval_raw,
                                        ext: dispatchModalLog.approval_ext,
                                        name: 'Berkas Approval Penarikan' 
                                    })" 
                                    class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded text-[10px] transition inline-flex items-center gap-1 cursor-pointer">
                                <span>Lihat Berkas</span>
                                <i data-lucide="eye" class="w-3 h-3"></i>
                            </button>
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

    <!-- MODAL 2D QUICK SLOT ALLOCATION (RUANG -> RAK -> 100 SLOT GRID -> DBLCLICK SAVE) -->
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
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500 font-bold">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span>Mendekati Exp (H-30)</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-600 text-white border border-rose-400 font-bold shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-white"></span>
                                <span>Expired</span>
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
                                                     'bg-amber-500/20 border-amber-500/40 text-amber-300 cursor-not-allowed opacity-90': (slot.status === 'filled' || slot.archive) && !isSlotNearExp(slot) && !isSlotExpired(slot),
                                                     'bg-rose-500/20 border-rose-500 text-rose-300 cursor-not-allowed opacity-95': isSlotNearExp(slot) && !isSlotExpired(slot),
                                                     'bg-rose-600 border-rose-400 text-white cursor-not-allowed font-black shadow-md': isSlotExpired(slot),
                                                     'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed': slot.status === 'inactive'
                                                 }"
                                                 :title="slot.status === 'empty' ? ('Slot ' + slot.slot_code + ' (Kosong) - Double click untuk simpan!') : (isSlotExpired(slot) ? ('EXPIRED: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Expired')) : (isSlotNearExp(slot) ? ('MENDEKATI EXP: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Mendekati Exp')) : ('Terisi: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Terisi'))))">
                                                <div class="font-mono font-black text-[11px] truncate" 
                                                     :class="isSlotExpired(slot) ? '!text-white' : (isSlotNearExp(slot) ? 'text-rose-300' : (slot.status === 'empty' ? 'text-emerald-400' : 'text-amber-300'))" 
                                                     x-text="slot.slot_code"></div>
                                                <div class="text-[9px] truncate font-sans">
                                                    <template x-if="slot.status === 'empty'">
                                                        <span class="text-emerald-500 group-hover:text-slate-950 font-bold">KOSONG</span>
                                                    </template>
                                                    <template x-if="!isSlotExpired(slot) && !isSlotNearExp(slot) && (slot.status === 'filled' || slot.archive)">
                                                        <span class="text-amber-400 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="!isSlotExpired(slot) && isSlotNearExp(slot)">
                                                        <span class="text-rose-300 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="isSlotExpired(slot)">
                                                        <span class="text-white font-black tracking-wider">EXPIRED</span>
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
                                                     'bg-amber-500/20 border-amber-500/40 text-amber-300 cursor-not-allowed opacity-90': (slot.status === 'filled' || slot.archive) && !isSlotNearExp(slot) && !isSlotExpired(slot),
                                                     'bg-rose-500/20 border-rose-500 text-rose-300 cursor-not-allowed opacity-95': isSlotNearExp(slot) && !isSlotExpired(slot),
                                                     'bg-rose-600 border-rose-400 text-white cursor-not-allowed font-black shadow-md': isSlotExpired(slot),
                                                     'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed': slot.status === 'inactive'
                                                 }"
                                                 :title="slot.status === 'empty' ? ('Slot ' + slot.slot_code + ' (Kosong) - Double click untuk simpan!') : (isSlotExpired(slot) ? ('EXPIRED: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Expired')) : (isSlotNearExp(slot) ? ('MENDEKATI EXP: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Mendekati Exp')) : ('Terisi: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Terisi'))))">
                                                <div class="font-mono font-black text-[11px] truncate" 
                                                     :class="isSlotExpired(slot) ? '!text-white' : (isSlotNearExp(slot) ? 'text-rose-300' : (slot.status === 'empty' ? 'text-emerald-400' : 'text-amber-300'))" 
                                                     x-text="slot.slot_code"></div>
                                                <div class="text-[9px] truncate font-sans">
                                                    <template x-if="slot.status === 'empty'">
                                                        <span class="text-emerald-500 group-hover:text-slate-950 font-bold">KOSONG</span>
                                                    </template>
                                                    <template x-if="!isSlotExpired(slot) && !isSlotNearExp(slot) && (slot.status === 'filled' || slot.archive)">
                                                        <span class="text-amber-400 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="!isSlotExpired(slot) && isSlotNearExp(slot)">
                                                        <span class="text-rose-300 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="isSlotExpired(slot)">
                                                        <span class="text-white font-black tracking-wider">EXPIRED</span>
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

    <!-- MODAL POPUP LENGKAPI BERKAS TERLEBIH DAHULU -->
    <div x-show="incompleteFileModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         style="display: none; z-index: 9996 !important;"
         @keydown.escape.window="incompleteFileModalOpen = false">
        
        <div class="bg-white dark:bg-slate-900 border border-amber-500/40 dark:border-amber-500/30 rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 font-mono text-xs"
             @click.away="incompleteFileModalOpen = false">
            
            <div class="flex items-start gap-3">
                <div class="p-2.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-xl shrink-0">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                        Lengkapi Berkas Terlebih Dahulu
                    </h3>
                    <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed font-sans">
                        Berkas digital & scan formulir untuk box arsip ini belum diunggah. Silakan lengkapi berkas digital & scan formulir terlebih dahulu pada form pengajuan (Bagian 5) sebelum mencetak label box.
                    </p>
                </div>
            </div>

            <template x-if="incompleteFileArchive">
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl space-y-1 text-[11px]">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>No. Box / Kode:</span>
                        <strong class="text-amber-600 dark:text-amber-400 font-mono" x-text="incompleteFileArchive.box_number"></strong>
                    </div>
                    <div class="flex items-center justify-between text-slate-700 dark:text-slate-200">
                        <span>Judul Berkas:</span>
                        <strong class="truncate max-w-[200px]" x-text="incompleteFileArchive.title"></strong>
                    </div>
                </div>
            </template>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="incompleteFileModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                    Tutup
                </button>
                <template x-if="incompleteFileArchive && incompleteFileArchive.edit_url">
                    <a :href="incompleteFileArchive.edit_url" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl font-black text-xs shadow-md transition inline-flex items-center gap-1.5">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-slate-950"></i>
                        <span>Lengkapi Berkas Sekarang</span>
                    </a>
                </template>
            </div>
        </div>
    </div>

    <!-- MODAL VERIFIKASI PENGAJUAN ARSIP & GENERATE NOMOR BOX (PIC GUDANG) -->
    <div x-show="verifyModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm overflow-y-auto"
         style="display: none; z-index: 9996 !important;"
         @keydown.escape.window="if(!verifyingArchive) closeVerifyModal()">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 font-mono text-xs my-auto"
             @click.away="if(!verifyingArchive) closeVerifyModal()">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-xl">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                            Verifikasi Pengajuan Box Arsip
                        </h3>
                        <p class="text-[11px] text-slate-500">Persetujuan PIC Gudang & generate Nomor Box resmi.</p>
                    </div>
                </div>
                <button type="button" @click="closeVerifyModal()" :disabled="verifyingArchive" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <template x-if="verifyModalData">
                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Departemen:</span>
                            <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/30 font-bold" x-text="verifyModalData.department_code + ' - ' + verifyModalData.department"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Pengaju:</span>
                            <span class="font-bold text-slate-900 dark:text-white" x-text="verifyModalData.creator"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Periode Arsip:</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400" x-text="verifyModalData.periode || '-'"></span>
                        </div>
                        <div class="pt-1 border-t border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block mb-0.5">Judul Berkas:</span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs" x-text="verifyModalData.title"></h4>
                        </div>
                    </div>

                    <!-- Status Berkas Lampiran / Scan -->
                    <div class="p-3 rounded-xl border" :class="verifyModalData.has_files ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-900 dark:text-emerald-200' : 'bg-rose-500/10 border-rose-500/30 text-rose-900 dark:text-rose-200'">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-[11px] flex items-center gap-1.5">
                                <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                <span>Kelengkapan Berkas Scan:</span>
                            </span>
                            <template x-if="verifyModalData.has_files">
                                <span class="px-2 py-0.5 rounded bg-emerald-600 text-white font-black text-[10px]" x-text="verifyModalData.files.length + ' Berkas Terlampir'"></span>
                            </template>
                            <template x-if="!verifyModalData.has_files">
                                <span class="px-2 py-0.5 rounded bg-rose-600 text-white font-black text-[10px]">Belum Ada Berkas</span>
                            </template>
                        </div>

                        <!-- Daftar Berkas Lampiran dengan Tombol Preview Langsung -->
                        <template x-if="verifyModalData.has_files && verifyModalData.files.length > 0">
                            <div class="space-y-1.5 pt-1.5 border-t border-emerald-500/20">
                                <template x-for="(f, fIdx) in verifyModalData.files" :key="fIdx">
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-white/80 dark:bg-slate-900/80 border border-emerald-500/30 gap-2">
                                        <div class="min-w-0 flex items-center gap-2">
                                            <span class="p-1 rounded bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 shrink-0">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                            </span>
                                            <div class="truncate">
                                                <span class="font-bold text-xs text-slate-900 dark:text-white block truncate" x-text="f.name"></span>
                                                <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate" x-text="(f.category || '') + ' (' + (f.ext ? f.ext.toUpperCase() : 'FILE') + ')'"></span>
                                            </div>
                                        </div>
                                        <button type="button"
                                                @click="dmsPreviewFile({
                                                    box_number: verifyModalData.box_number || '',
                                                    title: verifyModalData.title,
                                                    department: verifyModalData.department_code,
                                                    files: verifyModalData.files,
                                                    raw_path: f.raw_path,
                                                    url: f.url,
                                                    stream_url: f.stream_url,
                                                    name: f.name,
                                                    ext: f.ext
                                                })"
                                                class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer shrink-0 shadow-xs">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>Lihat Berkas</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Rejection Form Input (If rejecting) -->
                    <div x-show="showRejectInput" x-transition class="space-y-1.5 p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl">
                        <label class="block text-[11px] font-bold text-rose-700 dark:text-rose-300 uppercase">Alasan / Catatan Penolakan <span class="text-rose-500">*</span></label>
                        <textarea x-model="rejectionNote" rows="2" placeholder="Tuliskan alasan penolakan untuk PIC Departemen..." class="w-full p-2 bg-white dark:bg-slate-900 border border-rose-400 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-600"></textarea>
                    </div>

                    <!-- Notice -->
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed font-sans">
                        Persetujuan verifikasi akan secara otomatis men-generate <strong>Nomor Box Arsip</strong> resmi dan mengubah status menjadi <strong>Approved / Booked</strong>.
                    </p>

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeVerifyModal()" :disabled="verifyingArchive" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                            Batal
                        </button>

                        <template x-if="!showRejectInput">
                            <button type="button" @click="showRejectInput = true" :disabled="verifyingArchive" class="px-3.5 py-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded-xl font-bold text-xs transition flex items-center gap-1.5">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                <span>Tolak & Revisi</span>
                            </button>
                        </template>

                        <template x-if="showRejectInput">
                            <button type="button" @click="submitVerification('reject')" :disabled="verifyingArchive || !rejectionNote.trim()" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl font-bold text-xs shadow-md transition flex items-center gap-1.5 disabled:opacity-50">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>Kirim Penolakan</span>
                            </button>
                        </template>

                        <button type="button" 
                                @click="submitVerification('approve')" 
                                :disabled="verifyingArchive"
                                class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-black text-xs shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                            <template x-if="verifyingArchive">
                                <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            </template>
                            <template x-if="!verifyingArchive">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                            </template>
                            <span x-text="verifyingArchive ? 'Memverifikasi...' : 'Setujui & Generate Box Code'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- MODAL PENGELUARAN & PEMUSNAHAN BERKAS (OUT / DESTROY) -->
    <div x-show="checkoutModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm overflow-y-auto"
         style="display: none; z-index: 9997 !important;"
         @keydown.escape.window="if(!checkingOut) closeCheckoutModal()">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-4 font-mono text-xs my-auto"
             @click.away="if(!checkingOut) closeCheckoutModal()">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl" :class="checkoutActionType === 'destroy' ? 'bg-rose-500/20 text-rose-600 dark:text-rose-400' : 'bg-purple-500/20 text-purple-600 dark:text-purple-400'">
                        <i :data-lucide="checkoutActionType === 'destroy' ? 'trash-2' : 'log-out'" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                            Proses Status Berkas Gudang
                        </h3>
                        <p class="text-[11px] text-slate-500">Pilih opsi Pengeluaran Berkas (Out) atau Pemusnahan Berkas (BAP).</p>
                    </div>
                </div>
                <button type="button" @click="closeCheckoutModal()" :disabled="checkingOut" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <template x-if="checkoutModalData">
                <div class="space-y-3.5">
                    <!-- Detail Berkas Header Card -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">No. Box Arsip:</span>
                            <span class="font-black text-amber-600 dark:text-amber-400" x-text="checkoutModalData.box_number"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Departemen:</span>
                            <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold" x-text="checkoutModalData.department"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Posisi Rak Fisik:</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="checkoutModalData.location || 'Gudang'"></span>
                        </div>
                        <div class="pt-1 border-t border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block mb-0.5">Judul Dokumen:</span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs truncate" x-text="checkoutModalData.title"></h4>
                        </div>
                    </div>

                    <!-- Pilihan Opsi Aksi: Pengeluaran vs Pemusnahan -->
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Pilih Tindakan Status <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" 
                                    @click="checkoutActionType = 'out'"
                                    :class="checkoutActionType === 'out' ? 'bg-purple-500/20 border-purple-500 text-purple-900 dark:text-purple-200 ring-2 ring-purple-500/30 font-black' : 'bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-bold'"
                                    class="p-2.5 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs flex items-center gap-1.5">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-purple-500"></i>
                                        Pengeluaran Berkas (Out)
                                    </span>
                                    <input type="radio" name="cat_action_choice" value="out" :checked="checkoutActionType === 'out'" class="text-purple-600 pointer-events-none">
                                </div>
                                <p class="text-[10px] text-slate-500 leading-tight">Otorisasi fisik berkas keluar dari gudang.</p>
                            </button>

                            <button type="button" 
                                    @click="checkoutActionType = 'destroy'"
                                    :class="checkoutActionType === 'destroy' ? 'bg-rose-500/20 border-rose-500 text-rose-900 dark:text-rose-200 ring-2 ring-rose-500/30 font-black' : 'bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-bold'"
                                    class="p-2.5 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs flex items-center gap-1.5">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                        Pemusnahan Berkas (BAP)
                                    </span>
                                    <input type="radio" name="cat_action_choice" value="destroy" :checked="checkoutActionType === 'destroy'" class="text-rose-600 pointer-events-none">
                                </div>
                                <p class="text-[10px] text-slate-500 leading-tight">Eksekusi pemusnahan resmi & catat No. BAP.</p>
                            </button>
                        </div>
                    </div>

                    <!-- FORM MODE 1: PENGELUARAN BERKAS (OUT) -->
                    <template x-if="checkoutActionType === 'out'">
                        <div class="space-y-2.5 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Nama Penerima / Pengambil <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" x-model="checkoutBorrowerName" placeholder="Contoh: Surya Atmojo" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500">
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Departemen Tujuan
                                    </label>
                                    <div class="relative" x-data="{
                                        open: false,
                                        search: '',
                                        get filteredDepts() {
                                            if (!this.search) return departments;
                                            const q = this.search.toLowerCase();
                                            return departments.filter(d => (d.code && d.code.toLowerCase().includes(q)) || (d.name && d.name.toLowerCase().includes(q)));
                                        },
                                        select(d) {
                                            checkoutDepartmentName = d ? (d.code ? d.code + ' - ' + d.name : d.name) : '';
                                            this.open = false;
                                            this.search = '';
                                        }
                                    }" @click.outside="open = false">
                                        <!-- Trigger Button -->
                                        <button 
                                            type="button" 
                                            @click="open = !open" 
                                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-left text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 transition flex items-center justify-between shadow-2xs cursor-pointer h-[32px]"
                                        >
                                            <span class="truncate font-semibold" x-text="checkoutDepartmentName || '-- Pilih Departemen --'"></span>
                                            <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div 
                                            x-show="open" 
                                            x-cloak 
                                            x-transition
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-xl overflow-hidden font-mono text-xs"
                                        >
                                            <div class="p-1.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                                <div class="relative">
                                                    <i data-lucide="search" class="w-3 h-3 absolute left-2 top-2 text-slate-400"></i>
                                                    <input 
                                                        type="text" 
                                                        x-model="search" 
                                                        @keydown.escape="open = false" 
                                                        placeholder="Cari kode / nama departemen..." 
                                                        class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"
                                                        x-ref="searchDeptInput"
                                                        x-init="$watch('open', value => { if(value) { setTimeout(() => $refs.searchDeptInput?.focus(), 50); if(window.lucide) lucide.createIcons(); } })"
                                                    >
                                                </div>
                                            </div>
                                            <ul class="max-h-40 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                                <template x-for="d in filteredDepts" :key="d.id">
                                                    <li 
                                                        @click="select(d)" 
                                                        class="px-2.5 py-1.5 hover:bg-purple-500/15 dark:hover:bg-purple-500/20 cursor-pointer flex items-center justify-between transition"
                                                        :class="checkoutDepartmentName === (d.name) || checkoutDepartmentName === (d.code + ' - ' + d.name) ? 'bg-purple-500/20 font-bold text-purple-700 dark:text-purple-400' : 'text-slate-800 dark:text-slate-200'"
                                                    >
                                                        <span x-text="(d.code ? d.code + ' - ' : '') + d.name"></span>
                                                        <i data-lucide="check" class="w-3.5 h-3.5 text-purple-600" x-show="checkoutDepartmentName === (d.name) || checkoutDepartmentName === (d.code + ' - ' + d.name)"></i>
                                                    </li>
                                                </template>
                                                <li x-show="filteredDepts.length === 0" class="p-2 text-center text-slate-400 text-[11px]">
                                                    Tidak ada departemen yang cocok
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Keperluan / Alasan Pengeluaran <span class="text-rose-500">*</span>
                                </label>
                                <textarea x-model="checkoutPurpose" rows="2" placeholder="Contoh: Audit Eksternal Pajak, Pengambilan Berkas Resmi Departemen..." class="w-full p-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"></textarea>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Bukti / Form Serah Terima (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="cat_checkout_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>

                            <div class="p-2.5 bg-purple-500/10 border border-purple-500/30 rounded-xl text-[11px] text-purple-900 dark:text-purple-200 flex items-start gap-2">
                                <i data-lucide="info" class="w-4 h-4 text-purple-600 shrink-0 mt-0.5"></i>
                                <span>Pengeluaran berkas akan secara otomatis mengubah status arsip menjadi <strong>Keluar (Out)</strong> dan mengosongkan slot rak di gudang.</span>
                            </div>
                        </div>
                    </template>

                    <!-- FORM MODE 2: PEMUSNAHAN BERKAS (DESTROY / BAP) -->
                    <template x-if="checkoutActionType === 'destroy'">
                        <div class="space-y-2.5 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Nomor BAP Pemusnahan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" x-model="destroyBapNumber" placeholder="BAP/IND/2026/00001" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Tanggal Pemusnahan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" x-model="destroyDate" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Metode Pemusnahan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="destroyMethod" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                    <option value="Shredding (Pencacahan Fisik)">Shredding (Pencacahan Fisik Mesin Penghancur)</option>
                                    <option value="Incineration (Pembakaran Bersertifikat)">Incineration (Pembakaran Suhu Tinggi Bersertifikat)</option>
                                    <option value="Chemical Recycling (Peleburan Kimia)">Chemical Recycling (Peleburan Kimia & Daur Ulang Industri)</option>
                                    <option value="Digital Purge & Shred">Digital Purge & Shred (Pemusnahan Total Fisik & Digital)</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Catatan / Keterangan Berita Acara (BAP)
                                </label>
                                <textarea x-model="destroyNotes" rows="2" placeholder="Keterangan saksi, kondisi berkas yang dimusnahkan..." class="w-full p-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500"></textarea>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Scan Form Persetujuan / Bukti BAP (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="cat_destroy_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>

                            <div class="p-2.5 bg-rose-500/10 border border-rose-500/30 rounded-xl text-[11px] text-rose-900 dark:text-rose-200 flex items-start gap-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 shrink-0 mt-0.5"></i>
                                <span>Pemusnahan berkas bersifat permanen. Status arsip akan diubah menjadi <strong>Dimusnahkan</strong> dan slot rak gudang akan dibebaskan.</span>
                            </div>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeCheckoutModal()" :disabled="checkingOut" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                            Batal
                        </button>
                        
                        <template x-if="checkoutActionType === 'out'">
                            <button type="button" 
                                    @click="submitCheckout()" 
                                    :disabled="checkingOut || !checkoutPurpose.trim()" 
                                    class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl font-black text-xs shadow-lg shadow-purple-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                                <template x-if="checkingOut">
                                    <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                </template>
                                <template x-if="!checkingOut">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                </template>
                                <span x-text="checkingOut ? 'Memproses...' : 'Konfirmasi Pengeluaran Berkas (Out)'"></span>
                            </button>
                        </template>

                        <template x-if="checkoutActionType === 'destroy'">
                            <button type="button" 
                                    @click="submitCheckout()" 
                                    :disabled="checkingOut || !destroyBapNumber.trim()" 
                                    class="px-4 py-2 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white rounded-xl font-black text-xs shadow-lg shadow-rose-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                                <template x-if="checkingOut">
                                    <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                </template>
                                <template x-if="!checkingOut">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </template>
                                <span x-text="checkingOut ? 'Memproses...' : 'Sahkan Pemusnahan Berkas (BAP)'"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- MODAL SUPER ADMIN UBAH STATUS WORKFLOW BEBAS -->
    <div x-show="superAdminStatusModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm overflow-y-auto"
         style="display: none; z-index: 9998 !important;"
         @keydown.escape.window="if(!updatingStatus) closeSuperAdminStatusModal()">
        
        <div class="bg-white dark:bg-slate-900 border-2 border-amber-500/60 dark:border-amber-500/40 rounded-2xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-4 font-mono text-xs my-auto"
             @click.away="if(!updatingStatus) closeSuperAdminStatusModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-gradient-to-br from-amber-500 to-amber-600 text-slate-950 rounded-xl font-bold shadow">
                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                Super Admin: Ubah Status Workflow
                            </h3>
                            <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-700 dark:text-amber-400 text-[10px] font-black border border-amber-500/30">
                                Global Override
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500">Pilih status tujuan untuk memindahkan alur berkas secara bebas.</p>
                    </div>
                </div>
                <button type="button" @click="closeSuperAdminStatusModal()" :disabled="updatingStatus" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <template x-if="superAdminStatusData">
                <div class="space-y-4">
                    <!-- Detail Berkas Info Card -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">No. Box Arsip:</span>
                            <span class="font-black text-amber-600 dark:text-amber-400" x-text="superAdminStatusData.box_number || 'Pending'"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Departemen:</span>
                            <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold" x-text="superAdminStatusData.department"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Status Saat Ini:</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black border" 
                                  :class="{
                                      'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300': superAdminStatusData.status === 'draft',
                                      'bg-amber-500/20 text-amber-800 dark:text-amber-300 border-amber-500/40': superAdminStatusData.status === 'pending_verification',
                                      'bg-blue-500/20 text-blue-800 dark:text-blue-300 border-blue-500/40': superAdminStatusData.status === 'approved_booked',
                                      'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border-emerald-500/40': superAdminStatusData.status === 'in_warehouse',
                                      'bg-purple-500/20 text-purple-800 dark:text-purple-300 border-purple-500/40': superAdminStatusData.status === 'borrowed' || superAdminStatusData.status === 'taken',
                                      'bg-rose-500/20 text-rose-800 dark:text-rose-300 border-rose-500/40': superAdminStatusData.status === 'destroyed'
                                  }"
                                  x-text="superAdminStatusData.status_label || superAdminStatusData.status">
                            </span>
                        </div>
                        <div class="pt-1 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <span class="text-[10px] text-slate-400 uppercase font-bold block mb-0.5">Judul Dokumen:</span>
                                <h4 class="font-bold text-slate-900 dark:text-white text-xs truncate" x-text="superAdminStatusData.title"></h4>
                            </div>
                            <template x-if="superAdminStatusData.files && superAdminStatusData.files.length > 0">
                                <button type="button"
                                        @click="dmsPreviewFile({
                                            box_number: superAdminStatusData.box_number,
                                            title: superAdminStatusData.title,
                                            department: superAdminStatusData.department_code,
                                            files: superAdminStatusData.files
                                        })"
                                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer shrink-0 shadow-xs">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Pratinjau Berkas</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Target Status Selection (List of Options) -->
                    <div class="space-y-2">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Pilih Status Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <!-- Draft -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'draft' ? 'bg-slate-200/80 dark:bg-slate-800 border-slate-500 ring-2 ring-slate-400/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-slate-400'">
                                <input type="radio" name="cat_super_status_opt" value="draft" x-model="targetStatusChoice" class="mt-0.5 text-slate-700">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <i data-lucide="file-edit" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span>Draft (Kembalikan ke Draft)</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Wajib isi alasan. PIC Dept dapat merevisi. Slot rak dibebaskan.</p>
                                </div>
                            </label>

                            <!-- Antrean Verifikasi -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'pending_verification' ? 'bg-amber-500/20 border-amber-500 ring-2 ring-amber-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-amber-400'">
                                <input type="radio" name="cat_super_status_opt" value="pending_verification" x-model="targetStatusChoice" class="mt-0.5 text-amber-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-amber-700 dark:text-amber-300 flex items-center gap-1.5">
                                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-500"></i>
                                        <span>Antrean Verifikasi</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Menunggu verifikasi PIC Gudang. Lengkapi berkas persyaratan.</p>
                                </div>
                            </label>

                            <!-- Tersimpan di Gudang -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'in_warehouse' ? 'bg-emerald-500/20 border-emerald-500 ring-2 ring-emerald-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-emerald-400'">
                                <input type="radio" name="cat_super_status_opt" value="in_warehouse" x-model="targetStatusChoice" class="mt-0.5 text-emerald-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                                        <i data-lucide="archive" class="w-3.5 h-3.5 text-emerald-500"></i>
                                        <span>Tersimpan di Gudang</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Status aktif di gudang. No. Box & berkas serah terima.</p>
                                </div>
                            </label>

                            <!-- Out / Keluar -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'taken' ? 'bg-purple-500/20 border-purple-500 ring-2 ring-purple-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-purple-400'">
                                <input type="radio" name="cat_super_status_opt" value="taken" x-model="targetStatusChoice" class="mt-0.5 text-purple-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-purple-500"></i>
                                        <span>Keluar (Out)</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Berkas keluar dari gudang fisik. Slot rak dibebaskan.</p>
                                </div>
                            </label>

                            <!-- Antrean Pemusnahan -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'pending_destruction' ? 'bg-orange-500/20 border-orange-500 ring-2 ring-orange-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-orange-400'">
                                <input type="radio" name="cat_super_status_opt" value="pending_destruction" x-model="targetStatusChoice" class="mt-0.5 text-orange-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-orange-700 dark:text-orange-300 flex items-center gap-1.5">
                                        <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-orange-500"></i>
                                        <span>Antrean Pemusnahan</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Usulan/antrean pemusnahan berkas sebelum eksekusi fisik BAP.</p>
                                </div>
                            </label>

                            <!-- Dimusnahkan -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'destroyed' ? 'bg-rose-500/20 border-rose-500 ring-2 ring-rose-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-rose-400'">
                                <input type="radio" name="cat_super_status_opt" value="destroyed" x-model="targetStatusChoice" class="mt-0.5 text-rose-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-rose-700 dark:text-rose-300 flex items-center gap-1.5">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                        <span>Dimusnahkan (BAP)</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Pemusnahan permanen dengan Berita Acara (BAP). Slot rak dibebaskan.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- DYNAMIC SUB-FORMS BERDASARKAN STATUS YANG DIPILIH -->

                    <!-- 1. FORM MODE: DRAFT -->
                    <template x-if="targetStatusChoice === 'draft'">
                        <div class="space-y-2 p-3.5 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                            <div class="flex items-start gap-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                                <div class="text-[11px] text-amber-900 dark:text-amber-200 leading-tight">
                                    <strong class="font-bold">Pengembalian ke Status Draft:</strong>
                                    <p class="mt-0.5 text-slate-600 dark:text-slate-400">Berkas akan dikembalikan statusnya ke Draft Usulan sehingga PIC Departemen terkait dapat melakukan revisi judul, butir berkas, maupun dokumen. Alokasi slot rak gudang (jika ada) akan otomatis dibebaskan.</p>
                                </div>
                            </div>
                            <div class="space-y-1 pt-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Alasan / Keterangan Pengembalian ke Draft <span class="text-rose-500">* (Wajib Diisi)</span>
                                </label>
                                <textarea x-model="superAdminStatusReason" rows="2" placeholder="Tuliskan alasan mengapa berkas dikembalikan ke draft (misal: dokumen perlu revisi rincian butir arsip)..." class="w-full p-2 bg-white dark:bg-slate-900 border border-amber-400 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-600"></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- 2. FORM MODE: ANTREAN VERIFIKASI -->
                    <template x-if="targetStatusChoice === 'pending_verification'">
                        <div class="space-y-2.5 p-3.5 bg-amber-500/5 border border-amber-500/20 rounded-xl">
                            <div class="flex items-center justify-between border-b border-amber-500/20 pb-1.5">
                                <span class="text-[11px] font-bold text-amber-800 dark:text-amber-300 uppercase flex items-center gap-1.5">
                                    <i data-lucide="files" class="w-3.5 h-3.5"></i> Kelengkapan Berkas Antrean Verifikasi (2 Berkas)
                                </span>
                                <span class="text-[10px] text-slate-500">Upload berkas baru jika diperlukan</span>
                            </div>
                            <div class="space-y-2">
                                <!-- File 1: Scan Formulir Input Fisik -->
                                <div class="p-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">1. Scan Formulir Input Fisik</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.2 rounded text-[10px] font-bold" :class="superAdminStatusData.has_scan_input ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/20 text-rose-700 dark:text-rose-300'" x-text="superAdminStatusData.has_scan_input ? 'Sudah Ada' : 'Belum Ada'"></span>
                                            <template x-if="superAdminStatusData.has_scan_input">
                                                <button type="button"
                                                        @click="const sf = (superAdminStatusData.files || []).find(f => f.name && (f.name.includes('Formulir') || f.category === 'Formulir Pendaftaran Fisik')); dmsPreviewFile(sf || {
                                                            url: superAdminStatusData.scan_input_form_url,
                                                            name: 'Scan Formulir Input - ' + superAdminStatusData.title,
                                                            box_number: superAdminStatusData.box_number
                                                        })"
                                                        class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] inline-flex items-center gap-1 cursor-pointer transition shadow-2xs">
                                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                                    <span>Lihat</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <input type="file" id="sa_cat_scan_input_form" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-[10px] font-mono text-slate-600 dark:text-slate-400 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-amber-500/20 file:text-amber-800 dark:file:text-amber-200 hover:file:bg-amber-500/30">
                                </div>

                                <!-- File 2: Lampiran Softcopy Dokumen -->
                                <div class="p-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">2. Lampiran Digital Dokumen</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.2 rounded text-[10px] font-bold" :class="superAdminStatusData.has_file_path ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/20 text-rose-700 dark:text-rose-300'" x-text="superAdminStatusData.has_file_path ? 'Sudah Ada' : 'Belum Ada'"></span>
                                            <template x-if="superAdminStatusData.has_file_path">
                                                <button type="button"
                                                        @click="const df = (superAdminStatusData.files || []).find(f => f.name && (f.name.includes('Digital') || f.category === 'Softcopy Dokumen')); dmsPreviewFile(df || {
                                                            url: superAdminStatusData.file_path_url,
                                                            name: 'Lampiran Digital - ' + superAdminStatusData.title,
                                                            box_number: superAdminStatusData.box_number
                                                        })"
                                                        class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] inline-flex items-center gap-1 cursor-pointer transition shadow-2xs">
                                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                                    <span>Lihat</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <input type="file" id="sa_cat_file_path" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.zip" class="w-full text-[10px] font-mono text-slate-600 dark:text-slate-400 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-amber-500/20 file:text-amber-800 dark:file:text-amber-200 hover:file:bg-amber-500/30">
                                </div>
                            </div>
                            <div class="space-y-1 pt-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Catatan / Keterangan (Opsional)
                                </label>
                                <textarea x-model="superAdminStatusReason" rows="2" placeholder="Catatan kelengkapan berkas untuk PIC Gudang..." class="w-full p-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- 3. FORM MODE: TERSIMPAN DI GUDANG -->
                    <template x-if="targetStatusChoice === 'in_warehouse'">
                        <div class="space-y-2.5 p-3.5 bg-emerald-500/5 border border-emerald-500/20 rounded-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        No. Box Arsip
                                    </label>
                                    <input type="text" x-model="superAdminBoxNumber" placeholder="Auto Generate Box Code" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono font-bold text-emerald-700 dark:text-emerald-300 focus:outline-none focus:border-emerald-500">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Lokasi Fisik
                                    </label>
                                    <div class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500"></i>
                                        <span x-text="superAdminStatusData.location || 'Belum Ditentukan'"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Berkas Serah Terima Masuk Gudang (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="sa_cat_warehouse_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Catatan / Keterangan (Opsional)
                                </label>
                                <textarea x-model="superAdminStatusReason" rows="2" placeholder="Catatan penempatan box gudang..." class="w-full p-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- 4. FORM MODE: KELUAR (OUT) -->
                    <template x-if="targetStatusChoice === 'taken'">
                        <div class="space-y-2.5 p-3.5 bg-purple-500/5 border border-purple-500/20 rounded-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Nama Penerima / Pengambil
                                    </label>
                                    <input type="text" x-model="superAdminBorrowerName" placeholder="Nama PIC..." class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500">
                                </div>
                                
                                <!-- Searchable Dropdown Departemen Tujuan -->
                                <div class="space-y-1" x-data="{
                                    open: false,
                                    search: '',
                                    departments: {{ json_encode($departments->map(fn($d) => ['id' => $d->id, 'code' => $d->code, 'name' => $d->name])) }},
                                    get filteredDepts() {
                                        if (!this.search.trim()) return this.departments;
                                        return this.departments.filter(d => 
                                            (d.name && d.name.toLowerCase().includes(this.search.toLowerCase())) ||
                                            (d.code && d.code.toLowerCase().includes(this.search.toLowerCase()))
                                        );
                                    },
                                    select(dept) {
                                        $data.superAdminDeptName = (dept.code ? dept.code + ' - ' : '') + dept.name;
                                        this.open = false;
                                        this.search = '';
                                    }
                                }" @click.outside="open = false">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                        <span>Departemen Tujuan</span>
                                        <span class="text-[9px] text-purple-600 dark:text-purple-400 font-normal">Pilih Departemen</span>
                                    </label>
                                    <div class="relative">
                                        <button 
                                            type="button" 
                                            @click="open = !open" 
                                            class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-left flex items-center justify-between focus:outline-none focus:border-purple-500"
                                        >
                                            <span class="truncate" :class="superAdminDeptName ? 'text-slate-900 dark:text-white font-bold' : 'text-slate-400'" x-text="superAdminDeptName || '-- Pilih Departemen Tujuan --'"></span>
                                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                                        </button>
                                        <div 
                                            x-show="open" 
                                            x-cloak 
                                            x-transition
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-xl overflow-hidden font-mono text-xs"
                                        >
                                            <div class="p-1.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                                <div class="relative">
                                                    <i data-lucide="search" class="w-3 h-3 absolute left-2 top-2 text-slate-400"></i>
                                                    <input 
                                                        type="text" 
                                                        x-model="search" 
                                                        placeholder="Cari kode / nama departemen..." 
                                                        class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"
                                                    >
                                                </div>
                                            </div>
                                            <ul class="max-h-40 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                                <template x-for="d in filteredDepts" :key="d.id">
                                                    <li 
                                                        @click="select(d)" 
                                                        class="px-2.5 py-1.5 hover:bg-purple-500/15 cursor-pointer flex items-center justify-between transition"
                                                        :class="superAdminDeptName === (d.code ? d.code + ' - ' : '') + d.name ? 'bg-purple-500/20 font-bold text-purple-700 dark:text-purple-400' : 'text-slate-800 dark:text-slate-200'"
                                                    >
                                                        <span x-text="(d.code ? d.code + ' - ' : '') + d.name"></span>
                                                        <i data-lucide="check" class="w-3.5 h-3.5 text-purple-600" x-show="superAdminDeptName === (d.code ? d.code + ' - ' : '') + d.name"></i>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Keperluan / Alasan Pengeluaran <span class="text-rose-500">* (Wajib Diisi)</span>
                                </label>
                                <textarea x-model="superAdminPurpose" rows="2" placeholder="Contoh: Audit Eksternal Pajak, Pengambilan Berkas Resmi Departemen..." class="w-full p-2 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Bukti / Form Serah Terima Keluar (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="sa_cat_taken_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>
                            <div class="p-2 bg-purple-500/10 border border-purple-500/20 rounded-lg text-[10px] text-purple-800 dark:text-purple-300">
                                <span>Status berkas akan diubah ke <strong>Keluar (Out)</strong> dan slot rak gudang akan dibebaskan.</span>
                            </div>
                        </div>
                    </template>

                    <!-- 5. FORM MODE: DIMUSNAHKAN -->
                    <template x-if="targetStatusChoice === 'destroyed'">
                        <div class="space-y-2.5 p-3.5 bg-rose-500/5 border border-rose-500/20 rounded-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Nomor BAP Pemusnahan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" x-model="superAdminBapNumber" placeholder="BAP/IND/2026/00001" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Tanggal Pemusnahan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" x-model="superAdminDestroyDate" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Metode Pemusnahan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="superAdminDestroyMethod" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                    <option value="Shredding (Pencacahan Fisik)">Shredding (Pencacahan Fisik Mesin Penghancur)</option>
                                    <option value="Incineration (Pembakaran Bersertifikat)">Incineration (Pembakaran Suhu Tinggi Bersertifikat)</option>
                                    <option value="Chemical Recycling (Peleburan Kimia)">Chemical Recycling (Peleburan Kimia & Daur Ulang)</option>
                                    <option value="Digital Purge & Shred">Digital Purge & Shred (Pemusnahan Total Fisik & Digital)</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Keterangan Berita Acara (BAP) / Saksi
                                </label>
                                <textarea x-model="superAdminDestroyNotes" rows="2" placeholder="Keterangan saksi, kondisi berkas yang dimusnahkan..." class="w-full p-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500"></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Scan Form / Dokumen BAP Pemusnahan (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="sa_cat_destroy_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>
                            <div class="p-2 bg-rose-500/10 border border-rose-500/20 rounded-lg text-[10px] text-rose-800 dark:text-rose-300">
                                <span>Pemusnahan bersifat permanen. Status arsip akan diubah ke <strong>Dimusnahkan</strong> dan slot rak dibebaskan.</span>
                            </div>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeSuperAdminStatusModal()" :disabled="updatingStatus" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                            Batal
                        </button>
                        <button type="button" 
                                @click="submitSuperAdminStatusChange()" 
                                :disabled="updatingStatus || !targetStatusChoice || (targetStatusChoice === 'draft' && !superAdminStatusReason.trim()) || (targetStatusChoice === 'taken' && !superAdminPurpose.trim()) || (targetStatusChoice === 'destroyed' && !superAdminBapNumber.trim())" 
                                class="px-5 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs rounded-xl shadow-lg shadow-amber-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                            <template x-if="updatingStatus">
                                <span class="w-3.5 h-3.5 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
                            </template>
                            <template x-if="!updatingStatus">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-slate-950"></i>
                            </template>
                            <span x-text="updatingStatus ? 'Menyimpan...' : (targetStatusChoice === 'draft' ? 'Kembalikan ke Draft' : (targetStatusChoice === 'pending_verification' ? 'Ubah ke Antrean Verifikasi' : (targetStatusChoice === 'in_warehouse' ? 'Simpan di Gudang' : (targetStatusChoice === 'taken' ? 'Keluarkan Berkas (Out)' : (targetStatusChoice === 'pending_destruction' ? 'Pindah ke Antrean Pemusnahan' : (targetStatusChoice === 'destroyed' ? 'Sahkan Pemusnahan (BAP)' : 'Terapkan Perubahan Status'))))))"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- MODAL IMPOR MULTI-BOX & BUTIR ARSIP (EXCEL / CSV) -->
    <div x-show="openImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-import-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="if(!importSubmitting) openImportModal = false"></div>
        <div class="flex min-h-screen items-center justify-center p-3 sm:p-4">
            <div class="relative w-full max-w-4xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden font-sans text-slate-800 dark:text-slate-100 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-5 py-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <span class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-xl">
                            <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                        </span>
                        <div>
                            <h3 id="modal-import-title" class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider font-mono">
                                Impor Multi-Box & Butir Dokumen (Excel / CSV)
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Unggah ratusan berkas dalam banyak box sekaligus dengan otomasi perhitungan periode & retensi.
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="if(!importSubmitting) openImportModal = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-5 space-y-5 overflow-y-auto flex-1">
                    <!-- Instruction & Template Download -->
                    <div class="p-4 bg-gradient-to-r from-blue-50/70 to-indigo-50/70 dark:from-blue-950/20 dark:to-indigo-950/20 border border-blue-200 dark:border-blue-900/40 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                        <div class="space-y-1">
                            <div class="font-bold text-blue-900 dark:text-blue-200 flex items-center gap-1.5 font-mono">
                                <i data-lucide="info" class="w-4 h-4 text-blue-500"></i>
                                Petunjuk Pengisian Multi-Box:
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 text-[11px] leading-relaxed">
                                @if(Auth::user() && Auth::user()->isPicDept())
                                    Identitas box (<span class="font-mono font-bold text-blue-600 dark:text-blue-400">NO_BOX_SEMENTARA</span>, Judul, Dept) cukup diisi <strong>1 kali</strong> pada baris pertama box. Baris butir dokumen berikutnya cukup isi Nama Dokumen &amp; Periode. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">NO_BUTIR</span> otomatis urut (1, 2, 3...) jika dikosongkan. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">MASA_SIMPAN_BULAN</span> diisi angka bulan murni (contoh: <code>60</code> untuk 60 bulan/5 tahun, <code>12</code> untuk 12 bulan). Tab <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">REFERENSI_KODE</span> tersedia sebagai panduan kode departemen.
                                @else
                                    Identitas box (<span class="font-mono font-bold text-blue-600 dark:text-blue-400">NO_BOX_SEMENTARA</span>, Judul, Dept, Rak) cukup diisi <strong>1 kali</strong> pada baris pertama box. Baris butir berikutnya dalam box yang sama cukup isi Nama Dokumen &amp; Periode. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">NO_BUTIR</span> otomatis urut jika dikosongkan. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">MASA_SIMPAN_BULAN</span> diisi angka bulan murni (contoh: <code>60</code>). Tab <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">REFERENSI_KODE</span> untuk panduan kode departemen &amp; rak gudang.
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('archives.import.template', ['format' => 'xlsx']) }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs shadow-sm flex items-center gap-1.5 transition">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span>Template Excel (.xlsx)</span>
                            </a>
                            <a href="{{ route('archives.import.template', ['format' => 'csv']) }}" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg font-bold text-xs transition">
                                <span>CSV</span>
                            </a>
                        </div>
                    </div>

                    <!-- File Dropzone -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider font-mono">
                            Pilih File Spreadsheet (.xlsx / .csv)
                        </label>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <input type="file" 
                                   id="multi_box_excel_file_input" 
                                   accept=".xlsx,.xls,.csv" 
                                   @change="handleImportFileSelect($event)" 
                                   class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-950/40 dark:file:text-blue-300 border border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50/50 dark:bg-slate-900 p-1">
                            
                            <button type="button" 
                                    @click="submitImportPreview()" 
                                    :disabled="!importFile || importPreviewing" 
                                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold text-xs shadow-sm transition flex items-center justify-center gap-1.5 shrink-0 disabled:opacity-50">
                                <span x-show="importPreviewing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <svg x-show="!importPreviewing" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 7V5a2 2 0 0 1 2-2h2"/>
                                    <path d="M17 3h2a2 2 0 0 1 2 2v2"/>
                                    <path d="M21 17v2a2 2 0 0 1-2 2h-2"/>
                                    <path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                                </svg>
                                <span x-text="importPreviewing ? 'Menganalisis...' : 'Validasi File'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Error Alert List -->
                    <template x-if="importErrors && importErrors.length > 0">
                        <div class="p-3.5 bg-rose-500/10 border border-rose-500/20 rounded-xl space-y-1.5">
                            <div class="font-bold text-xs text-rose-700 dark:text-rose-400 flex items-center gap-1.5">
                                <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                                <span>Ditemukan Kesalahan Validasi:</span>
                            </div>
                            <ul class="list-disc list-inside text-xs text-rose-600 dark:text-rose-300 space-y-1 font-mono">
                                <template x-for="(err, eIdx) in importErrors" :key="eIdx">
                                    <li x-text="err"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <!-- Warning Alert List -->
                    <template x-if="importWarnings && importWarnings.length > 0">
                        <div class="p-3.5 bg-amber-500/10 border border-amber-500/20 rounded-xl space-y-1.5">
                            <div class="font-bold text-xs text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                                <span>Peringatan & Catatan Penyesuaian:</span>
                            </div>
                            <ul class="list-disc list-inside text-xs text-amber-600 dark:text-amber-300 space-y-1 font-mono">
                                <template x-for="(warn, wIdx) in importWarnings" :key="wIdx">
                                    <li x-text="warn"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <!-- Preview Content -->
                    <template x-if="importPreviewResult">
                        <div class="space-y-4">
                            <!-- Stats & Target Status Radio -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="p-3.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center gap-3">
                                    <div class="p-2 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-lg">
                                        <i data-lucide="package" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider font-mono">Total Box Terdeteksi</div>
                                        <div class="text-base font-black text-slate-900 dark:text-white" x-text="importPreviewResult.total_boxes + ' Box'"></div>
                                    </div>
                                </div>
                                <div class="p-3.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center gap-3">
                                    <div class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg">
                                        <i data-lucide="file-text" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider font-mono">Total Butir Dokumen</div>
                                        <div class="text-base font-black text-slate-900 dark:text-white" x-text="importPreviewResult.total_items + ' Butir'"></div>
                                    </div>
                                </div>
                                <div class="p-3.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                                    <div class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider font-mono">
                                        Status Pasca-Impor:
                                    </div>
                                    <div class="space-y-1">
                                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                                            <input type="radio" value="draft" x-model="importTargetStatus" class="text-blue-600">
                                            <span>Draft Sementara</span>
                                        </label>
                                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                                            <input type="radio" value="pending_verification" x-model="importTargetStatus" class="text-blue-600">
                                            <span class="font-bold text-amber-600 dark:text-amber-400">Verifikasi Gudang</span>
                                        </label>
                                        @if(Auth::check() && !Auth::user()->isPicDept())
                                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                                            <input type="radio" value="in_warehouse" x-model="importTargetStatus" class="text-blue-600">
                                            <span class="font-bold text-emerald-600 dark:text-emerald-400">Tersimpan di Gudang (Data Existing)</span>
                                        </label>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Box Cards Accordion / List -->
                            <div class="space-y-3">
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider font-mono flex items-center justify-between">
                                    <span>Pratinjau Struktur Box & Dokumen:</span>
                                    <span class="text-[11px] text-slate-400 font-normal">Semua periode & retensi dihitung otomatis</span>
                                </div>

                                <div class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
                                    <template x-for="(box, bIdx) in importPreviewResult.boxes" :key="bIdx">
                                        <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2.5">
                                            <!-- Box Header -->
                                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800/80 pb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 rounded font-mono font-bold text-xs" x-text="box.temp_box_id"></span>
                                                    <h4 class="font-bold text-xs text-slate-900 dark:text-white" x-text="box.title || box.name"></h4>
                                                </div>
                                                <div class="flex items-center gap-1.5 text-[11px] font-mono">
                                                    <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 rounded text-slate-700 dark:text-slate-300" x-text="'Dept: ' + box.department_code + (box.sub_department_code ? ' / ' + box.sub_department_code : '')"></span>
                                                    <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 rounded font-bold" x-text="box.items.length + ' Berkas'"></span>
                                                </div>
                                            </div>

                                            <!-- Box Calculated Metrics -->
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] font-mono bg-white dark:bg-slate-950/50 p-2 rounded-lg border border-slate-200 dark:border-slate-800">
                                                <div>
                                                    <span class="text-slate-400 block text-[10px]">Periode Arsip:</span>
                                                    <span class="font-bold text-slate-700 dark:text-slate-300" x-text="box.periode_doc || '-'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-400 block text-[10px]">Rentang Tanggal:</span>
                                                    <span class="font-bold text-slate-700 dark:text-slate-300" x-text="(box.period_start_date || '?') + ' s/d ' + (box.period_end_date || '?')"></span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-400 block text-[10px]">Retensi (Kedaluwarsa):</span>
                                                    <span class="font-bold text-amber-600 dark:text-amber-400" x-text="(box.retention_years || 5) + ' Thn (' + (box.retention_expiry_date || '-') + ')'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-400 block text-[10px]">Kondisi Fisik:</span>
                                                    <span class="font-bold text-slate-700 dark:text-slate-300" x-text="box.physical_condition || 'Baik'"></span>
                                                </div>
                                            </div>

                                            <!-- Box Item Table -->
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-[11px] font-mono">
                                                    <thead class="bg-slate-200/50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400">
                                                        <tr>
                                                            <th class="py-1 px-2 w-12">No</th>
                                                            <th class="py-1 px-2">Nama Dokumen / Berkas</th>
                                                            <th class="py-1 px-2 w-48">Periode Dokumen</th>
                                                            <th class="py-1 px-2">Keterangan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40">
                                                        <template x-for="(itm, iIdx) in box.items" :key="iIdx">
                                                             <tr class="hover:bg-white/50 dark:hover:bg-slate-800/30">
                                                                <td class="py-1 px-2 font-bold text-slate-500" x-text="itm.item_number || (iIdx + 1)"></td>
                                                                <td class="py-1 px-2 font-semibold text-slate-900 dark:text-slate-100" x-text="itm.document_name || itm.name || '-'"></td>
                                                                <td class="py-1 px-2 text-slate-600 dark:text-slate-400" x-text="itm.period_text || ((itm.period_start || '-') + (itm.period_end && itm.period_end !== itm.period_start ? ' s/d ' + itm.period_end : ''))"></td>
                                                                <td class="py-1 px-2 text-slate-500 italic" x-text="itm.notes || itm.description || '-'"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
                    <div class="text-[11px] text-slate-500 font-mono">
                        <template x-if="importPreviewResult">
                            <span>Siap diproses: <strong class="text-slate-900 dark:text-white" x-text="importPreviewResult.total_boxes"></strong> box (<strong x-text="importPreviewResult.total_items"></strong> berkas).</span>
                        </template>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 justify-end">
                        <button type="button" 
                                @click="openImportModal = false" 
                                :disabled="importSubmitting || importLoadingToForm" 
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition cursor-pointer">
                            Tutup
                        </button>

                        <!-- ALUR 1: Lanjutkan ke Formulir Pengajuan (Alur Biasa Pengajuan Arsip) -->
                        <button type="button" 
                                @click="continueToCreateForm()" 
                                :disabled="!importPreviewResult || importPreviewResult.boxes.length === 0 || importSubmitting || importLoadingToForm" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-500/20 transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                                title="Bawa data dari Excel ini ke Formulir Pengajuan Dokumen Arsip untuk alur pengajuan biasa">
                            <span x-show="importLoadingToForm" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <svg x-show="!importLoadingToForm" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="12" y1="18" x2="12" y2="12"/>
                                <line x1="9" y1="15" x2="15" y2="15"/>
                            </svg>
                            <span x-text="importLoadingToForm ? 'Memuat Form...' : 'Lanjutkan ke Form Pengajuan'"></span>
                        </button>

                        <!-- ALUR 2: Simpan Langsung ke Gudang (Khusus Data yang Sudah Ada di Gudang Sebelumnya) -->
                        @if(Auth::check() && !Auth::user()->isPicDept())
                        <button type="button" 
                                @click="submitImportCommitDirect()" 
                                :disabled="!importPreviewResult || importPreviewResult.boxes.length === 0 || importSubmitting || importLoadingToForm" 
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                                title="Khusus data arsip fisik yang sudah ada di rak gudang sebelumnya (tanpa alur pengajuan baru)">
                            <span x-show="importSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <svg x-show="!importSubmitting" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <ellipse cx="12" cy="5" rx="9" ry="3"/>
                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                            </svg>
                            <span x-text="importSubmitting ? 'Mengimpor...' : 'Simpan Langsung ke Gudang'"></span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $borrowableArchiveIds = [];
    $printableArchiveIds = [];
    foreach ($archives as $arc) {
        $hasActiveBorrowing = $arc->borrowingLogs && $arc->borrowingLogs->whereIn('status', ['requested', 'dept_approved', 'approved'])->isNotEmpty();
        $isStoredInWh = $arc->status === 'in_warehouse';
        $isDeptAllowed = !auth()->user()->isPicDept() || ((int)$arc->department_id === (int)auth()->user()->department_id);
        if ($isStoredInWh && !$hasActiveBorrowing && $isDeptAllowed) {
            $borrowableArchiveIds[] = (int) $arc->id;
        }

        $hasRackLocation = !empty($arc->warehouse_location_id);
        if (in_array($arc->status, ['approved_booked', 'in_warehouse', 'pending_extension']) && $hasRackLocation) {
            $printableArchiveIds[] = (int) $arc->id;
        }
    }
@endphp
<script>
function archiveCatalog() {
    return {
        departments: @json($departments ?? []),

        // Cascading Document Types Filter
        documentTypes: @json($documentTypes ?? []),
        filterSubDept: '{{ request('sub_department_id', '') }}',
        filterDocType: '{{ request('document_type', '') }}',

        get availableDocTypes() {
            let list = this.documentTypes;
            if (this.filterSubDept) {
                list = list.filter(d => String(d.sub_department_id) === String(this.filterSubDept) || !d.sub_department_id);
            }
            return [...new Set(list.map(d => d.document_type))].filter(Boolean).sort();
        },

        onFilterSubDeptChange() {
            if (this.filterDocType && !this.availableDocTypes.includes(this.filterDocType)) {
                this.filterDocType = '';
            }
        },
        selected: [],
        selectAll: false,
        dispatchModalLog: null,

        // Live Realtime State (Point 4: Auto-sorting Data Baru)
        liveNewArchives: [],
        currentFilters: {
            deptId: '{{ request('department_id', '') }}',
            subDeptId: '{{ request('sub_department_id', '') }}',
            docType: '{{ request('document_type', '') }}',
            status: '{{ request('status', '') }}',
        },

        init() {
            const onRealtimeSync = (detail) => {
                if (!detail) return;
                // If there are new archives or status changes
                if (detail.has_new || detail.has_changes) {
                    // Check if user is currently interacting with any modal or form
                    const isAnyModalOpen = this.verifyModalOpen || 
                                           this.quickSlotModalOpen || 
                                           this.checkoutModalOpen || 
                                           this.superAdminStatusModalOpen || 
                                           this.openImportModal ||
                                           (this.dispatchModalLog !== null);
                    
                    const isTyping = document.activeElement && 
                                     ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);
                    
                    const hasSelected = this.selected && this.selected.length > 0;

                    // If user is idle (not typing, no modal open, no bulk items selected):
                    // Auto-refresh table seamlessly so newly submitted archives appear immediately
                    if (!isAnyModalOpen && !isTyping && !hasSelected) {
                        window.location.reload();
                    } else if (detail.new_archives && detail.new_archives.length > 0) {
                        // If user is busy typing or has modal open, fall back to handleIncomingArchives without disturbing them
                        this.handleIncomingArchives(detail.new_archives);
                    }
                }
            };

            window.addEventListener('dms:realtime-sync', (e) => onRealtimeSync(e.detail));
            window.addEventListener('message', (e) => {
                if (e.data && e.data.type === 'dms:realtime-sync') {
                    onRealtimeSync(e.data.detail);
                }
            });
            if (window.parent && window.parent !== window) {
                try {
                    window.parent.addEventListener('dms:realtime-sync', (e) => onRealtimeSync(e.detail));
                } catch(err) {}
            }
        },

        handleIncomingArchives(newItems) {
            let addedCount = 0;
            newItems.forEach(item => {
                // Draft items must never be auto-sorted to top (only real submissions)
                if (item.status === 'draft') {
                    return;
                }
                // If item already rendered in server table or live list, skip
                if (this.archiveStatuses[item.id] !== undefined) {
                    return;
                }
                // Check if active filter excludes this item
                if (this.currentFilters.deptId && String(item.department_id) !== String(this.currentFilters.deptId)) {
                    return;
                }
                if (this.currentFilters.subDeptId && String(item.sub_department_id) !== String(this.currentFilters.subDeptId)) {
                    return;
                }
                if (this.currentFilters.docType && String(item.document_type) !== String(this.currentFilters.docType)) {
                    return;
                }
                if (this.currentFilters.status && String(item.status) !== String(this.currentFilters.status)) {
                    return;
                }

                // Auto-sort to top
                this.liveNewArchives.unshift(item);
                this.archiveStatuses[item.id] = item.status;
                addedCount++;
            });

            if (addedCount > 0) {
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        // Archive status map for batch action filtering (id -> status)
        archiveStatuses: @json($archives->pluck('status', 'id')),
        borrowableArchiveIds: @json($borrowableArchiveIds),
        printableArchiveIds: @json($printableArchiveIds),

        get selectedBorrowable() {
            return this.selected.filter(id => this.borrowableArchiveIds.includes(Number(id)));
        },
        get selectedDrafts() {
            return this.selected.filter(id => this.archiveStatuses[id] === 'draft');
        },
        get selectedNonDrafts() {
            return this.selected.filter(id => this.archiveStatuses[id] !== 'draft');
        },
        get selectedAlreadyPulled() {
            return this.selected.filter(id => !this.borrowableArchiveIds.includes(Number(id)) && this.archiveStatuses[id] !== 'draft');
        },
        get selectedPrintable() {
            return this.selected.filter(id => this.printableArchiveIds.includes(Number(id)));
        },

        // Super Admin Status Override State
        superAdminStatusModalOpen: false,
        superAdminStatusData: null,
        targetStatusChoice: 'draft',
        superAdminStatusReason: '',
        superAdminBorrowerName: '{{ auth()->user()->name }}',
        superAdminDeptName: '',
        superAdminPurpose: '',
        superAdminBapNumber: '',
        superAdminDestroyDate: new Date().toISOString().split('T')[0],
        superAdminDestroyMethod: 'Shredding (Pencacahan Fisik)',
        superAdminDestroyNotes: '',
        superAdminBoxNumber: '',
        updatingStatus: false,

        // Multi-Box Excel Import State
        openImportModal: {{ request('open_import') ? 'true' : 'false' }},
        importFile: null,
        importFileName: '',
        importPreviewing: false,
        importPreviewResult: null,
        importTargetStatus: 'draft',
        importSubmitting: false,
        importErrors: [],
        importWarnings: [],

        handleImportFileSelect(e) {
            const files = e.target.files;
            if (files && files[0]) {
                this.importFile = files[0];
                this.importFileName = files[0].name;
                this.importPreviewResult = null;
                this.importErrors = [];
                this.importWarnings = [];
            }
        },

        async submitImportPreview() {
            if (!this.importFile) {
                alert('Pilih file Excel (.xlsx) atau CSV terlebih dahulu.');
                return;
            }

            this.importPreviewing = true;
            this.importErrors = [];
            this.importWarnings = [];

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const formData = new FormData();
                formData.append('file', this.importFile);

                const res = await fetch('{{ route("archives.import.preview") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const json = await res.json();
                if (res.ok && json.success) {
                    this.importPreviewResult = json;
                    this.importWarnings = json.warnings || [];
                    this.importErrors = [];
                } else {
                    this.importPreviewResult = null;
                    this.importErrors = json.errors || [json.message || 'Gagal memproses file template spreadsheet.'];
                }
            } catch (err) {
                console.error('Import preview error:', err);
                this.importErrors = ['Terjadi kesalahan koneksi saat membaca file Excel: ' + err.message];
            } finally {
                this.importPreviewing = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        importLoadingToForm: false,

        async continueToCreateForm() {
            if (!this.importPreviewResult || !this.importPreviewResult.boxes || this.importPreviewResult.boxes.length === 0) {
                alert('Silakan pilih dan validasi file Excel terlebih dahulu.');
                return;
            }

            this.importLoadingToForm = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const res = await fetch('{{ route("archives.import.load_to_form") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        boxes: this.importPreviewResult.boxes
                    })
                });

                const json = await res.json();
                if (res.ok && json.success && json.redirect_url) {
                    window.location.href = json.redirect_url;
                } else {
                    alert(json.message || 'Gagal memuat data ke formulir pengajuan.');
                    this.importLoadingToForm = false;
                }
            } catch (err) {
                console.error('Continue to create form error:', err);
                alert('Terjadi kesalahan koneksi saat memuat formulir: ' + err.message);
                this.importLoadingToForm = false;
            }
        },

        async submitImportCommitDirect() {
            if (!this.importPreviewResult || !this.importPreviewResult.boxes || this.importPreviewResult.boxes.length === 0) {
                return;
            }

            const boxCount = this.importPreviewResult.total_boxes;
            const itemCount = this.importPreviewResult.total_items;
            const statusLabels = {
                'draft': 'Draft Sementara',
                'pending_verification': 'Antrean Verifikasi Gudang',
                'in_warehouse': 'Tersimpan di Rak Gudang (Data Existing)'
            };
            const currentLabel = statusLabels[this.importTargetStatus] || this.importTargetStatus;

            const ok = await window.showConfirmModal({
                title: 'Konfirmasi Impor Langsung ke Gudang',
                message: `Data ini akan langsung disimpan ke database gudang sebagai data fisik yang sudah ada di gudang sebelumnya (${boxCount} Box, ${itemCount} Butir Dokumen, Status: "${currentLabel}"). Lanjutkan?`,
                type: 'info',
                confirmText: 'Ya, Simpan ke Gudang'
            });
            if (!ok) return;

            this.importSubmitting = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const res = await fetch('{{ route("archives.import.commit") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        boxes: this.importPreviewResult.boxes,
                        status: this.importTargetStatus
                    })
                });

                const json = await res.json();
                if (res.ok && json.success) {
                    this.openImportModal = false;
                    if (window.showToast) {
                        window.showToast(json.message, 'success');
                    } else {
                        alert(json.message);
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 600);
                } else {
                    await window.showNotificationModal(json.message || 'Gagal mengeksekusi impor data box arsip.', 'Gagal Impor', 'danger');
                }
            } catch (err) {
                console.error('Import commit error:', err);
                await window.showNotificationModal('Terjadi kesalahan saat menyimpan data impor.', 'Kesalahan Server', 'danger');
            } finally {
                this.importSubmitting = false;
            }
        },

        async submitImportCommit() {
            return this.submitImportCommitDirect();
        },

        resetImport() {
            this.importFile = null;
            this.importFileName = '';
            this.importPreviewResult = null;
            this.importErrors = [];
            this.importWarnings = [];
            this.importPreviewing = false;
            this.importSubmitting = false;
            const input = document.getElementById('excel_import_file_input');
            if (input) input.value = '';
        },

        openSuperAdminStatusModal(archiveData) {
            this.superAdminStatusData = archiveData;
            this.targetStatusChoice = (archiveData.status === 'borrowed' ? 'taken' : archiveData.status) || 'draft';
            this.superAdminStatusReason = '';
            this.superAdminBorrowerName = '{{ auth()->user()->name }}';
            this.superAdminDeptName = archiveData.department || '';
            this.superAdminPurpose = '';
            this.superAdminBapNumber = 'BAP/IND/' + new Date().getFullYear() + '/' + String(archiveData.id || 1).padStart(5, '0');
            this.superAdminDestroyDate = new Date().toISOString().split('T')[0];
            this.superAdminDestroyMethod = 'Shredding (Pencacahan Fisik)';
            this.superAdminDestroyNotes = '';
            this.superAdminBoxNumber = archiveData.raw_box_number || (archiveData.box_number !== 'Pending' ? archiveData.box_number : '') || '';
            this.superAdminStatusModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closeSuperAdminStatusModal() {
            this.superAdminStatusModalOpen = false;
            this.superAdminStatusData = null;
            this.updatingStatus = false;
        },

        async submitSuperAdminStatusChange() {
            if (!this.superAdminStatusData || !this.targetStatusChoice) return;

            // Validations
            if (this.targetStatusChoice === 'draft' && this.superAdminStatusData.status !== 'draft') {
                if (!this.superAdminStatusReason.trim()) {
                    alert('Mohon isi alasan / keterangan pengembalian status ke Draft.');
                    return;
                }
            } else if (this.targetStatusChoice === 'taken') {
                if (!this.superAdminPurpose.trim()) {
                    alert('Mohon isi keperluan / alasan pengeluaran berkas.');
                    return;
                }
            } else if (this.targetStatusChoice === 'destroyed') {
                if (!this.superAdminBapNumber.trim()) {
                    await window.showNotificationModal('Mohon isi nomor Berita Acara Pemusnahan (BAP).', 'Validasi BAP Diperlukan', 'warning');
                    return;
                }
                const ok = await window.showConfirmModal({
                    title: 'Konfirmasi Pemusnahan Berkas',
                    message: `Apakah Anda yakin ingin mengesahkan pemusnahan berkas "${this.superAdminStatusData.title}" dengan No. BAP ${this.superAdminBapNumber}? Status akan dimusnahkan secara permanen.`,
                    type: 'danger',
                    confirmText: 'Ya, Musnahkan Permanen'
                });
                if (!ok) return;
            }

            this.updatingStatus = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const formData = new FormData();
                formData.append('target_status', this.targetStatusChoice);
                formData.append('reason', this.superAdminStatusReason);

                if (this.targetStatusChoice === 'pending_verification') {
                    const f1 = document.getElementById('sa_cat_scan_input_form');
                    if (f1 && f1.files && f1.files[0]) formData.append('scan_input_form', f1.files[0]);
                    const f2 = document.getElementById('sa_cat_file_path');
                    if (f2 && f2.files && f2.files[0]) formData.append('file_path', f2.files[0]);
                } else if (this.targetStatusChoice === 'in_warehouse') {
                    if (this.superAdminBoxNumber.trim()) {
                        formData.append('box_number', this.superAdminBoxNumber.trim());
                    }
                    const fWh = document.getElementById('sa_cat_warehouse_approval_file');
                    if (fWh && fWh.files && fWh.files[0]) formData.append('scan_approval_input', fWh.files[0]);
                } else if (this.targetStatusChoice === 'taken') {
                    formData.append('borrower_name', this.superAdminBorrowerName);
                    formData.append('department_name', this.superAdminDeptName);
                    formData.append('purpose', this.superAdminPurpose);
                    const fOut = document.getElementById('sa_cat_taken_approval_file');
                    if (fOut && fOut.files && fOut.files[0]) formData.append('approval_file', fOut.files[0]);
                } else if (this.targetStatusChoice === 'destroyed') {
                    formData.append('bap_number', this.superAdminBapNumber);
                    formData.append('destruction_date', this.superAdminDestroyDate);
                    formData.append('method', this.superAdminDestroyMethod);
                    formData.append('notes', this.superAdminDestroyNotes);
                    const fDes = document.getElementById('sa_cat_destroy_approval_file');
                    if (fDes && fDes.files && fDes.files[0]) formData.append('approval_file', fDes.files[0]);
                }

                const response = await fetch(`/archives/${this.superAdminStatusData.id}/superadmin-status`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    this.superAdminStatusModalOpen = false;
                    if (window.showToast) {
                        window.showToast(result.message, 'success');
                    } else {
                        alert(result.message);
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    let errMsg = result.message || 'Gagal mengubah status arsip.';
                    if (result.errors) {
                        const firstKey = Object.keys(result.errors)[0];
                        if (firstKey && result.errors[firstKey][0]) {
                            errMsg = result.errors[firstKey][0];
                        }
                    }
                    alert(errMsg);
                }
            } catch (e) {
                console.error('SuperAdmin Status Change Error:', e);
                alert('Terjadi kesalahan saat mengubah status arsip.');
            } finally {
                this.updatingStatus = false;
            }
        },
        
        // Incomplete File Modal State
        incompleteFileModalOpen: false,
        incompleteFileArchive: null,

        openIncompleteFileAlert(archiveData) {
            this.incompleteFileArchive = archiveData;
            this.incompleteFileModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        // Checkout / Pengeluaran & Pemusnahan Berkas Modal State
        checkoutModalOpen: false,
        checkoutModalData: null,
        checkoutActionType: 'out', // 'out' or 'destroy'
        checkoutBorrowerName: '{{ auth()->user()->name }}',
        checkoutDepartmentName: '',
        checkoutPurpose: '',
        checkoutNotes: '',
        destroyBapNumber: '',
        destroyDate: new Date().toISOString().split('T')[0],
        destroyMethod: 'Shredding (Pencacahan Fisik)',
        destroyNotes: '',
        checkingOut: false,

        openCheckoutModal(archiveData) {
            this.checkoutModalData = archiveData;
            this.checkoutActionType = 'out';
            this.checkoutBorrowerName = '{{ auth()->user()->name }}';
            this.checkoutDepartmentName = archiveData.department || '';
            this.checkoutPurpose = '';
            this.checkoutNotes = '';
            this.destroyBapNumber = 'BAP/IND/' + new Date().getFullYear() + '/' + String(archiveData.id || 1).padStart(5, '0');
            this.destroyDate = new Date().toISOString().split('T')[0];
            this.destroyMethod = 'Shredding (Pencacahan Fisik)';
            this.destroyNotes = '';
            this.checkoutModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closeCheckoutModal() {
            this.checkoutModalOpen = false;
            this.checkoutModalData = null;
            this.checkingOut = false;
        },

        async submitCheckout() {
            if (!this.checkoutModalData) return;

            if (this.checkoutActionType === 'out') {
                if (!this.checkoutPurpose.trim()) {
                    alert('Mohon isi keperluan / alasan pengeluaran berkas.');
                    return;
                }
            } else if (this.checkoutActionType === 'destroy') {
                if (!this.destroyBapNumber.trim()) {
                    await window.showNotificationModal('Mohon isi nomor Berita Acara Pemusnahan (BAP).', 'Validasi BAP Diperlukan', 'warning');
                    return;
                }
                const ok = await window.showConfirmModal({
                    title: 'Konfirmasi Pemusnahan Berkas',
                    message: `Apakah Anda yakin ingin mengesahkan pemusnahan berkas "${this.checkoutModalData.title}" dengan No. BAP ${this.destroyBapNumber}? Tindakan ini permanen.`,
                    type: 'danger',
                    confirmText: 'Ya, Musnahkan Permanen'
                });
                if (!ok) return;
            }

            this.checkingOut = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const formData = new FormData();
                formData.append('action_type', this.checkoutActionType);

                if (this.checkoutActionType === 'out') {
                    formData.append('borrower_name', this.checkoutBorrowerName);
                    formData.append('department_name', this.checkoutDepartmentName);
                    formData.append('purpose', this.checkoutPurpose);
                    formData.append('notes', this.checkoutNotes);

                    const fileInput = document.getElementById('cat_checkout_approval_file');
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        formData.append('approval_file', fileInput.files[0]);
                    }
                } else if (this.checkoutActionType === 'destroy') {
                    formData.append('bap_number', this.destroyBapNumber);
                    formData.append('destruction_date', this.destroyDate);
                    formData.append('method', this.destroyMethod);
                    formData.append('notes', this.destroyNotes);

                    const fileInput = document.getElementById('cat_destroy_approval_file');
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        formData.append('approval_file', fileInput.files[0]);
                    }
                }

                const response = await fetch(`/archives/${this.checkoutModalData.id}/checkout`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    this.checkoutModalOpen = false;
                    if (window.showToast) {
                        window.showToast(result.message, 'success');
                    } else {
                        alert(result.message);
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    await window.showNotificationModal(result.message || 'Gagal memproses tindakan arsip.', 'Gagal Proses Tindakan', 'danger');
                }
            } catch (e) {
                console.error('Action error:', e);
                await window.showNotificationModal('Terjadi kesalahan saat memproses status arsip.', 'Kesalahan Sistem', 'danger');
            } finally {
                this.checkingOut = false;
            }
        },

        // Verification Modal State
        verifyModalOpen: false,
        verifyModalData: null,
        verifyingArchive: false,
        showRejectInput: false,
        rejectionNote: '',

        openVerifyModal(archiveData) {
            this.verifyModalData = archiveData;
            this.showRejectInput = false;
            this.rejectionNote = '';
            this.verifyModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closeVerifyModal() {
            this.verifyModalOpen = false;
            this.verifyModalData = null;
            this.showRejectInput = false;
            this.rejectionNote = '';
        },

        async submitVerification(action) {
            if (!this.verifyModalData) return;
            if (action === 'reject' && !this.rejectionNote.trim()) {
                await window.showNotificationModal('Mohon masukkan alasan penolakan.', 'Alasan Penolakan Wajib Diisi', 'warning');
                return;
            }

            this.verifyingArchive = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch(`/archives/${this.verifyModalData.id}/verify`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: action,
                        rejection_note: this.rejectionNote
                    })
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    const currentData = this.verifyModalData;
                    this.verifyModalOpen = false;
                    this.showRejectInput = false;
                    this.rejectionNote = '';

                    if (action === 'approve') {
                        if (window.showToast) {
                            window.showToast(result.message || 'Pengajuan arsip disetujui! Silakan tentukan slot rak pada denah 2D.', 'success');
                        }
                        const targetData = {
                            id: currentData.id,
                            title: currentData.title,
                            box_number: result.box_number || 'Belum ada code',
                            department_id: currentData.department_id || 0,
                            department_code: currentData.department_code || 'GEN',
                            department_name: currentData.department || ''
                        };
                        this.$nextTick(() => {
                            this.openQuickSlotModal(targetData);
                        });
                    } else {
                        if (window.showToast) {
                            window.showToast(result.message, 'success');
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                } else {
                    await window.showNotificationModal(result.message || 'Gagal memproses verifikasi.', 'Gagal Verifikasi', 'danger');
                }
            } catch (e) {
                console.error('Verification error:', e);
                await window.showNotificationModal('Terjadi kesalahan saat memproses verifikasi arsip.', 'Kesalahan Sistem', 'danger');
            } finally {
                this.verifyingArchive = false;
            }
        },

        openFilePreviewModal(archiveData) {
            if (typeof window.dmsPreviewFile === 'function') {
                window.dmsPreviewFile(archiveData);
            }
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

        toggleAll() {
            if (this.selectAll) {
                const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
                this.selected = Array.from(checkboxes).map(cb => parseInt(cb.value)).filter(v => !isNaN(v));
            } else {
                this.selected = [];
            }
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

        isSlotExpired(slot) {
            if (!slot) return false;
            if (slot.status === 'inactive' || slot.is_active === false) return false;
            return slot.status === 'expired' || slot.is_expired === true || !!(slot.archive?.is_expired);
        },

        isSlotNearExp(slot) {
            if (!slot) return false;
            if (slot.status === 'inactive' || slot.is_active === false) return false;
            if (this.isSlotExpired(slot)) return false;
            return slot.status === 'near_exp' || slot.is_near_exp === true || !!(slot.archive?.is_near_exp);
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
                    
                    // Show success alert and reload table
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
