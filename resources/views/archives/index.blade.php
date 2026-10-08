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
        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
            <i data-lucide="filter" class="w-3.5 h-3.5 text-amber-500"></i>
            Filter & Pencarian Data Katalog
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
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (Simpan Sementara / Revisi)</option>
                    <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Antrean Verifikasi</option>
                    <option value="approved_booked" {{ request('status') == 'approved_booked' ? 'selected' : '' }}>Approved / Booked</option>
                    <option value="in_warehouse" {{ request('status') == 'in_warehouse' ? 'selected' : '' }}>Di Gudang</option>
                    <option value="out" {{ request('status') == 'out' || request('status') == 'borrowed' || request('status') == 'taken' ? 'selected' : '' }}>Keluar (Out)</option>
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
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700 text-center">
                            <span>BERKAS</span>
                        </th>
                        <th class="py-2.5 px-3 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-xs">
                    @forelse($archives as $archive)
                    @php
                        $activeBorrowing = $archive->borrowingLogs ? $archive->borrowingLogs->whereIn('status', ['requested', 'dept_approved', 'approved'])->first() : null;
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
                        $hasFiles = count($archiveFiles) > 0;
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
                            {{ $archive->effective_periode }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->physical_condition }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->location)
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
                                    $isExpired = \Carbon\Carbon::parse($archive->retention_expiry_date)->isPast();
                                    $isNear = \Carbon\Carbon::parse($archive->retention_expiry_date)->diffInDays(now()) <= 90;
                                @endphp
                                <span class="{{ $isExpired ? 'text-rose-600 dark:text-rose-400 font-extrabold' : ($isNear ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-700 dark:text-slate-300 font-medium') }}">
                                    {{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') }}
                                    ({{ $archive->retention_display }})
                                </span>
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
                                        has_approval: {{ !empty($archive->scan_approval_input) ? 'true' : 'false' }}
                                    })"
                                    class="px-2 py-0.5 rounded text-[10px] font-bold inline-flex items-center gap-1 transition shadow-xs cursor-pointer hover:scale-105 group border"
                                    :class="{
                                        'bg-slate-200 hover:bg-slate-300 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700': '{{ $archive->status }}' === 'draft',
                                        'bg-amber-500/20 hover:bg-amber-500/35 text-amber-800 dark:text-amber-300 border-amber-500/50': '{{ $archive->status }}' === 'pending_verification',
                                        'bg-blue-500/20 hover:bg-blue-500/35 text-blue-800 dark:text-blue-300 border-blue-500/50': '{{ $archive->status }}' === 'approved_booked',
                                        'bg-emerald-500/20 hover:bg-emerald-500/35 text-emerald-800 dark:text-emerald-300 border-emerald-500/50': '{{ $archive->status }}' === 'in_warehouse',
                                        'bg-purple-500/20 hover:bg-purple-500/35 text-purple-800 dark:text-purple-300 border-purple-500/50': '{{ $archive->status }}' === 'borrowed' || '{{ $archive->status }}' === 'taken',
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
                                    @elseif($archive->status === 'borrowed' || $archive->status === 'taken')
                                        <i data-lucide="log-out" class="w-3 h-3 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Keluar (Out)</span>
                                    @elseif($archive->status === 'destroyed')
                                        <i data-lucide="trash-2" class="w-3 h-3 text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Dimusnahkan</span>
                                    @endif
                                    <i data-lucide="sliders" class="w-2.5 h-2.5 opacity-60 ml-0.5 text-amber-500"></i>
                                </button>
                            @else
                                @if($archive->status === 'draft')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">Draft</span>
                                @elseif($archive->status === 'pending_verification')
                                    @if(auth()->user()->isPicGudang())
                                        <button 
                                            type="button" 
                                            @click="openVerifyModal({
                                                id: {{ $archive->id }},
                                                title: {{ json_encode($archive->title) }},
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
                                @elseif($archive->status === 'borrowed' || $archive->status === 'taken')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/40 inline-flex items-center gap-1">
                                        <i data-lucide="log-out" class="w-3 h-3 text-purple-500"></i>
                                        <span>Keluar (Out)</span>
                                    </span>
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
                                    @click="openFilePreviewModal({
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
                                                approval_url: {{ json_encode($activeBorrowing->effective_approval_file ? app_storage_url($activeBorrowing->effective_approval_file) : null) }}
                                            }"
                                            title="Keluarkan Berkas Fisik Berdasarkan Ajuan Peminjaman PIC Dept" 
                                            class="px-2.5 py-1 rounded bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white border border-purple-400 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-md">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-white"></i>
                                        <span>Keluarkan Arsip</span>
                                    </button>
                                @endif

                                @if(!auth()->user()->isPicGudang() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || (auth()->user()->isPicDept() && (int)$archive->department_id === (int)auth()->user()->department_id && $archive->status === 'draft')))
                                <a href="{{ route('archives.edit', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded bg-amber-500 hover:bg-amber-400 text-slate-950 border border-amber-600 font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs" title="Edit Data Berkas Arsip">
                                    <i data-lucide="edit-3" class="w-3 h-3 text-slate-950"></i>
                                    <span>Edit</span>
                                </a>
                                @endif

                                @if(!auth()->user()->isPicDept())
                                    @if($hasFiles)
                                        <a href="{{ route('archives.print_sticker', $archive) }}" target="_blank" title="Cetak Label Box Container" class="px-2 py-0.5 rounded bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs">
                                            <i data-lucide="printer" class="w-3 h-3 text-amber-500"></i>
                                            <span>Label</span>
                                        </a>
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
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/40 font-bold">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span>Expired / Rusak</span>
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
                                                     'bg-amber-500/20 border-amber-500/40 text-amber-300 cursor-not-allowed opacity-90': slot.status === 'filled',
                                                     'bg-rose-500/20 border-rose-500/40 text-rose-300 cursor-not-allowed': slot.status === 'expired',
                                                     'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed': slot.status === 'inactive'
                                                 }"
                                                 :title="slot.status === 'empty' ? ('Slot ' + slot.slot_code + ' (Kosong) - Double click untuk simpan!') : ('Terisi: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Terisi'))">
                                                <div class="font-mono font-black text-[11px] truncate" x-text="slot.slot_code"></div>
                                                <div class="text-[9px] truncate font-sans">
                                                    <template x-if="slot.status === 'empty'">
                                                        <span class="text-emerald-500 group-hover:text-slate-950 font-bold">KOSONG</span>
                                                    </template>
                                                    <template x-if="slot.status === 'filled'">
                                                        <span class="text-amber-400 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="slot.status === 'expired'">
                                                        <span class="text-rose-400 font-bold">EXPIRED</span>
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
                                                     'bg-amber-500/20 border-amber-500/40 text-amber-300 cursor-not-allowed opacity-90': slot.status === 'filled',
                                                     'bg-rose-500/20 border-rose-500/40 text-rose-300 cursor-not-allowed': slot.status === 'expired',
                                                     'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed': slot.status === 'inactive'
                                                 }"
                                                 :title="slot.status === 'empty' ? ('Slot ' + slot.slot_code + ' (Kosong) - Double click untuk simpan!') : ('Terisi: ' + (slot.archive ? (slot.archive.box_number + ' - ' + slot.archive.title) : 'Terisi'))">
                                                <div class="font-mono font-black text-[11px] truncate" x-text="slot.slot_code"></div>
                                                <div class="text-[9px] truncate font-sans">
                                                    <template x-if="slot.status === 'empty'">
                                                        <span class="text-emerald-500 group-hover:text-slate-950 font-bold">KOSONG</span>
                                                    </template>
                                                    <template x-if="slot.status === 'filled'">
                                                        <span class="text-amber-400 font-bold" x-text="slot.archive ? (slot.archive.box_number || 'TERISI') : 'TERISI'"></span>
                                                    </template>
                                                    <template x-if="slot.status === 'expired'">
                                                        <span class="text-rose-400 font-bold">EXPIRED</span>
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

    <!-- MODAL PREVIEW BERKAS DIGITAL & SCAN FORMULIR -->
    <div x-show="filePreviewModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto"
         style="display: none; z-index: 9995 !important;"
         @keydown.escape.window="closeFilePreviewModal()">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl space-y-4 font-mono text-xs max-h-[92vh] flex flex-col my-auto"
             @click.away="closeFilePreviewModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="p-2 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl shrink-0">
                        <i data-lucide="file-search" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                Preview Berkas Digital & Scan Formulir
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40">
                                <span x-text="previewArchiveData ? previewArchiveData.files.length : 0"></span> Berkas
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                            <span class="font-bold text-amber-600 dark:text-amber-400" x-text="previewArchiveData ? previewArchiveData.box_number : ''"></span> &mdash; 
                            <span x-text="previewArchiveData ? previewArchiveData.title : ''"></span> 
                            (<span x-text="previewArchiveData ? previewArchiveData.department : ''"></span>)
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <template x-if="currentPreviewFile">
                        <button type="button" @click="openInNewTab()" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold border border-slate-300 dark:border-slate-700 transition flex items-center gap-1 cursor-pointer" title="Buka di Tab Baru">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span class="hidden sm:inline">Buka Tab Baru</span>
                        </button>
                    </template>
                    <button type="button" @click="closeFilePreviewModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Tab / File Selection Ribbon (if multiple files) -->
            <template x-if="previewArchiveData && previewArchiveData.files.length > 1">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 shrink-0 border-b border-slate-200 dark:border-slate-800">
                    <template x-for="(file, idx) in previewArchiveData.files" :key="idx">
                        <button 
                            type="button" 
                            @click="selectPreviewFile(idx)" 
                            :class="selectedFileIndex === idx ? 'bg-amber-500 text-slate-950 font-black shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-bold'"
                            class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 shrink-0"
                        >
                            <i data-lucide="file-check" class="w-3.5 h-3.5" x-show="file.name.toLowerCase().includes('formulir')"></i>
                            <i data-lucide="paperclip" class="w-3.5 h-3.5" x-show="!file.name.toLowerCase().includes('formulir')"></i>
                            <span x-text="file.name"></span>
                            <span class="text-[10px] uppercase opacity-75 font-mono" x-text="'(' + file.ext + ')'"></span>
                        </button>
                    </template>
                </div>
            </template>

            <!-- Modal Body / File Preview Content -->
            <div class="flex-1 overflow-y-auto min-h-[360px] max-h-[62vh] bg-slate-50 dark:bg-slate-950 rounded-xl p-3 border border-slate-200 dark:border-slate-800 flex flex-col justify-center items-center">
                <template x-if="currentPreviewFile">
                    <div class="w-full h-full flex flex-col items-center justify-center">
                        <!-- Preview PDF (Anti-IDM via In-Memory Blob URL) -->
                        <template x-if="currentPreviewFile.ext === 'pdf'">
                            <div class="w-full h-full min-h-[480px] flex flex-col relative">
                                <!-- Loading State -->
                                <div x-show="loadingPdfBlob" class="w-full h-full min-h-[480px] flex flex-col items-center justify-center bg-slate-100/70 dark:bg-slate-900/70 rounded-lg space-y-3">
                                    <div class="w-8 h-8 border-3 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
                                    <p class="text-xs font-mono font-bold text-slate-600 dark:text-slate-300">Menyiapkan live preview dokumen PDF...</p>
                                </div>

                                <!-- Error State Fallback -->
                                <div x-show="!loadingPdfBlob && pdfError" class="w-full h-full min-h-[480px] flex flex-col items-center justify-center p-6 text-center space-y-3 font-mono">
                                    <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
                                        <i data-lucide="file-text" class="w-6 h-6"></i>
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 max-w-sm" x-text="pdfError"></p>
                                    <a :href="currentPreviewFile.url" target="_blank" class="px-3.5 py-1.5 bg-amber-500 text-slate-950 font-bold rounded-lg text-xs shadow hover:bg-amber-400 transition inline-flex items-center gap-1.5">
                                        <i data-lucide="external-link" class="w-4 h-4"></i>
                                        <span>Buka Berkas di Tab Baru</span>
                                    </a>
                                </div>

                                <!-- Live Preview Iframe with In-Memory Blob URL -->
                                <template x-if="!loadingPdfBlob && !pdfError && previewPdfBlobUrl">
                                    <iframe :src="previewPdfBlobUrl + '#toolbar=1&navpanes=0&scrollbar=1'" class="w-full flex-1 min-h-[480px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white shadow-sm" frameborder="0"></iframe>
                                </template>
                            </div>
                        </template>

                        <!-- Preview Images (JPG, JPEG, PNG, GIF, WEBP) -->
                        <template x-if="['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(currentPreviewFile.ext)">
                            <div class="w-full h-full flex flex-col items-center justify-center p-2">
                                <img :src="currentPreviewFile.url" :alt="currentPreviewFile.name" class="max-h-[500px] max-w-full object-contain rounded-lg shadow-lg border border-slate-300 dark:border-slate-700" />
                            </div>
                        </template>

                        <!-- Non-previewable Files (DOCX, ZIP, etc) -->
                        <template x-if="!['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'].includes(currentPreviewFile.ext)">
                            <div class="text-center py-12 space-y-4 font-mono">
                                <div class="w-16 h-16 mx-auto bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center border border-amber-500/30">
                                    <i data-lucide="file" class="w-8 h-8"></i>
                                </div>
                                <div class="space-y-1">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white" x-text="currentPreviewFile.name"></h4>
                                    <p class="text-xs text-slate-500" x-text="currentPreviewFile.filename"></p>
                                    <p class="text-[11px] text-slate-400">Tipe file ini (<span class="uppercase font-bold" x-text="currentPreviewFile.ext"></span>) tidak mendukung preview langsung pada browser.</p>
                                </div>
                                <div>
                                    <a :href="currentPreviewFile.url" target="_blank" download class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-xs shadow-md transition">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                        <span>Unduh Berkas</span>
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 pt-1 border-t border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 shrink-0">
                <template x-if="currentPreviewFile">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold" x-text="currentPreviewFile.category"></span>
                        <span class="truncate max-w-xs" x-text="currentPreviewFile.filename"></span>
                    </div>
                </template>
                <div class="flex items-center gap-2 self-end sm:self-auto">
                    <button type="button" @click="closeFilePreviewModal()" class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                        Tutup
                    </button>
                    <template x-if="currentPreviewFile">
                        <button type="button" @click="openInNewTab()" class="px-4 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-black text-xs shadow-md transition inline-flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span>Buka di Tab Baru</span>
                        </button>
                    </template>
                </div>
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
                        <div class="flex items-center justify-between">
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
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Max 10MB</span>
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
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Max 10MB</span>
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
                        <div class="pt-1 border-t border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block mb-0.5">Judul Dokumen:</span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs truncate" x-text="superAdminStatusData.title"></h4>
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

                            <!-- Dimusnahkan -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5 sm:col-span-2"
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
                                        <span class="px-2 py-0.2 rounded text-[10px] font-bold" :class="superAdminStatusData.has_scan_input ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/20 text-rose-700 dark:text-rose-300'" x-text="superAdminStatusData.has_scan_input ? 'Sudah Ada' : 'Belum Ada'"></span>
                                    </div>
                                    <input type="file" id="sa_cat_scan_input_form" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-[10px] font-mono text-slate-600 dark:text-slate-400 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-amber-500/20 file:text-amber-800 dark:file:text-amber-200 hover:file:bg-amber-500/30">
                                </div>

                                <!-- File 2: Lampiran Softcopy Dokumen -->
                                <div class="p-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">2. Lampiran Digital Dokumen</span>
                                        <span class="px-2 py-0.2 rounded text-[10px] font-bold" :class="superAdminStatusData.has_file_path ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/20 text-rose-700 dark:text-rose-300'" x-text="superAdminStatusData.has_file_path ? 'Sudah Ada' : 'Belum Ada'"></span>
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
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Max 10MB</span>
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
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Max 10MB</span>
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
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Max 10MB</span>
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
                            <span x-text="updatingStatus ? 'Menyimpan...' : (targetStatusChoice === 'draft' ? 'Kembalikan ke Draft' : (targetStatusChoice === 'pending_verification' ? 'Ubah ke Antrean Verifikasi' : (targetStatusChoice === 'in_warehouse' ? 'Simpan di Gudang' : (targetStatusChoice === 'taken' ? 'Keluarkan Berkas (Out)' : (targetStatusChoice === 'destroyed' ? 'Sahkan Pemusnahan (BAP)' : 'Terapkan Perubahan Status')))))"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function archiveCatalog() {
    return {
        departments: @json($departments ?? []),
        selected: [],
        selectAll: false,
        dispatchModalLog: null,

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
                    alert('Mohon isi nomor Berita Acara Pemusnahan (BAP).');
                    return;
                }
                if (typeof window.showConfirmModal === 'function') {
                    const ok = await window.showConfirmModal({
                        title: 'Konfirmasi Pemusnahan Berkas',
                        message: `Apakah Anda yakin ingin mengesahkan pemusnahan berkas "${this.superAdminStatusData.title}" dengan No. BAP ${this.superAdminBapNumber}? Status akan dimusnahkan secara permanen.`,
                        type: 'danger',
                        confirmText: 'Ya, Musnahkan Permanen'
                    });
                    if (!ok) return;
                } else if (!confirm(`Apakah Anda yakin ingin mengesahkan pemusnahan berkas "${this.superAdminStatusData.title}" dengan No. BAP ${this.superAdminBapNumber}? Status akan dimusnahkan secara permanen.`)) {
                    return;
                }
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
                    alert('Mohon isi nomor Berita Acara Pemusnahan (BAP).');
                    return;
                }
                if (typeof window.showConfirmModal === 'function') {
                    const ok = await window.showConfirmModal({
                        title: 'Konfirmasi Pemusnahan Berkas',
                        message: `Apakah Anda yakin ingin mengesahkan pemusnahan berkas "${this.checkoutModalData.title}" dengan No. BAP ${this.destroyBapNumber}? Tindakan ini permanen.`,
                        type: 'danger',
                        confirmText: 'Ya, Musnahkan Permanen'
                    });
                    if (!ok) return;
                } else if (!confirm(`Apakah Anda yakin ingin mengesahkan pemusnahan berkas "${this.checkoutModalData.title}" dengan No. BAP ${this.destroyBapNumber}? Tindakan ini permanen.`)) {
                    return;
                }
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
                    alert(result.message || 'Gagal memproses tindakan arsip.');
                }
            } catch (e) {
                console.error('Action error:', e);
                alert('Terjadi kesalahan saat memproses status arsip.');
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
                alert('Mohon masukkan alasan penolakan.');
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
                    this.verifyModalOpen = false;
                    if (window.showToast) {
                        window.showToast(result.message, 'success');
                    } else {
                        alert(result.message);
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    alert(result.message || 'Gagal memproses verifikasi.');
                }
            } catch (e) {
                console.error('Verification error:', e);
                alert('Terjadi kesalahan saat memproses verifikasi arsip.');
            } finally {
                this.verifyingArchive = false;
            }
        },

        // File Preview Modal State (Anti-IDM Blob URL Renderer)
        filePreviewModalOpen: false,
        previewArchiveData: null,
        selectedFileIndex: 0,
        previewPdfBlobUrl: null,
        loadingPdfBlob: false,
        pdfError: null,

        openFilePreviewModal(archiveData) {
            this.previewArchiveData = archiveData;
            this.selectedFileIndex = 0;
            this.filePreviewModalOpen = true;
            this.loadCurrentPreviewFile();
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        selectPreviewFile(idx) {
            if (this.selectedFileIndex === idx) return;
            this.selectedFileIndex = idx;
            this.loadCurrentPreviewFile();
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        loadCurrentPreviewFile() {
            if (this.previewPdfBlobUrl) {
                URL.revokeObjectURL(this.previewPdfBlobUrl);
                this.previewPdfBlobUrl = null;
            }
            this.pdfError = null;

            const file = this.currentPreviewFile;
            if (!file) return;

            if (file.ext === 'pdf') {
                this.loadingPdfBlob = true;
                
                // Anti-IDM Stream Endpoint: Prevents IDM extension from sniffing or intercepting PDF download
                let targetUrl = file.stream_url;
                if (!targetUrl) {
                    const raw = file.raw_path || file.url || '';
                    const clean = raw
                        .replace(/^https?:\/\/[^\/]+/, '')
                        .replace(/^\/(public\/storage|storage|public|files\/stream|files\/preview-stream)\//i, '')
                        .replace(/^\//, '');
                    targetUrl = '/files/preview-stream?token=' + encodeURIComponent(btoa(unescape(encodeURIComponent(clean))));
                }

                fetch(targetUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/octet-stream, application/pdf, */*'
                    }
                })
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status + ' (' + res.statusText + ')');
                        return res.blob();
                    })
                    .then(blob => {
                        const pdfBlob = new Blob([blob], { type: 'application/pdf' });
                        this.previewPdfBlobUrl = URL.createObjectURL(pdfBlob);
                        this.loadingPdfBlob = false;
                        this.$nextTick(() => {
                            if (window.lucide) window.lucide.createIcons();
                        });
                    })
                    .catch(err => {
                        console.warn('Gagal memuat PDF via anti-IDM blob stream:', err);
                        this.loadingPdfBlob = false;
                        this.pdfError = 'Gagal memuat pratinjau dokumen PDF secara live.';
                        this.$nextTick(() => {
                            if (window.lucide) window.lucide.createIcons();
                        });
                    });
            } else {
                this.loadingPdfBlob = false;
            }
        },

        openInNewTab() {
            if (this.previewPdfBlobUrl) {
                window.open(this.previewPdfBlobUrl, '_blank');
            } else if (this.currentPreviewFile) {
                window.open(this.currentPreviewFile.url, '_blank');
            }
        },

        closeFilePreviewModal() {
            if (this.previewPdfBlobUrl) {
                URL.revokeObjectURL(this.previewPdfBlobUrl);
                this.previewPdfBlobUrl = null;
            }
            this.filePreviewModalOpen = false;
            this.previewArchiveData = null;
            this.selectedFileIndex = 0;
            this.loadingPdfBlob = false;
            this.pdfError = null;
        },

        get currentPreviewFile() {
            if (!this.previewArchiveData || !this.previewArchiveData.files || !this.previewArchiveData.files.length) return null;
            return this.previewArchiveData.files[this.selectedFileIndex] || this.previewArchiveData.files[0];
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
