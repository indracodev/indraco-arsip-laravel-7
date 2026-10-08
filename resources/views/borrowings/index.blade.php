@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Penarikan Dokumen Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-3" 
     x-data="{ 
         submitting: false,
         detailModalData: null,
         openBorrowingDetail(data) {
             this.detailModalData = data;
             this.$nextTick(() => {
                 if (window.lucide) lucide.createIcons();
             });
         },
         closeBorrowingDetail() {
             this.detailModalData = null;
         }
     }"
     @open-borrowing-detail-modal.window="openBorrowingDetail($event.detail)"
>
    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER (TPanel / TToolBar) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-1.5 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded">
                <i data-lucide="file-symlink" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    MANAJEMEN PENARIKAN BERKAS ARSIP
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengajuan penarikan berkas, persetujuan kurator gudang, pengeluaran fisik, dan tracking pengembalian.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Refresh (F5) -->
            <button onclick="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Refresh Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-500"></i>
                <span>Refresh (F5)</span>
            </button>

            <!-- Pengajuan Penarikan Berkas (F2) -->
            <a href="{{ route('borrowings.create') }}" class="px-3 py-1 bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 hover:to-emerald-300 text-slate-950 font-mono font-black text-xs rounded border border-emerald-600 shadow transition flex items-center gap-1.5 shrink-0" title="Ajukan Penarikan Berkas Baru">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Buat Pengajuan (F2)</span>
            </a>
        </div>
    </div>

    @if (session('success'))
    <div class="p-3 rounded bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5 text-xs font-mono shadow-sm">
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5"></i>
        <div class="space-y-0.5">
            <span class="font-bold block uppercase">BERHASIL TERSIMPAN</span>
            <p class="text-[11px]">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <!-- DELPHI GROUPBOX FILTER PANEL (TGroupBox Delphi Desktop Style) -->
    <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm">
        <legend class="px-2 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
            <i data-lucide="filter" class="w-3.5 h-3.5 text-emerald-500"></i>
            Filter & Pencarian Penarikan Berkas
        </legend>

        <form action="{{ route('borrowings.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1" @submit="submitting = true">
            <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
            <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

            <!-- Search Keyword -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block flex items-center justify-between">
                    <span>CARI KEYWORD</span>
                    <span class="text-emerald-600 dark:text-emerald-400">[Ctrl+F]</span>
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="No. Box, Judul, Peminjam, Tujuan..." 
                        class="w-full pl-8 pr-3 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                    >
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1.5"></i>
                </div>
            </div>

            <!-- Filter Status -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">STATUS WORKFLOW</label>
                <select name="status" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 transition">
                    <option value="">-- Semua Status --</option>
                    <option value="requested" {{ request('status') == 'requested' ? 'selected' : '' }}>Diajukan User</option>
                    <option value="dept_approved" {{ request('status') == 'dept_approved' ? 'selected' : '' }}>Disetujui Dept</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui Gudang</option>
                    <option value="dispatched" {{ request('status') == 'dispatched' ? 'selected' : '' }}>Sedang Ditarik / Keluar</option>
                    <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Dikembalikan ke Gudang</option>
                </select>
            </div>

            <!-- Submit Filter Button -->
            <div class="space-y-1 flex items-end gap-1.5">
                <button type="submit" :disabled="submitting" class="flex-1 py-1 px-3 bg-slate-800 hover:bg-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded text-xs font-mono font-bold border border-slate-700 transition flex items-center justify-center gap-1.5 disabled:opacity-50 h-[30px] shadow-sm">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                    <i data-lucide="filter" class="w-3.5 h-3.5 text-emerald-400" x-show="!submitting"></i>
                    <span x-text="submitting ? 'Memuat...' : 'Filter'"></span>
                </button>

                @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('borrowings.index') }}" class="py-1 px-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-mono font-bold border border-slate-300 dark:border-slate-700 transition flex items-center justify-center h-[30px]" title="Reset Filter">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </a>
                @endif
            </div>
        </form>
    </fieldset>

    <!-- DELPHI TDBGRID SPREADSHEET TABLE CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                        @php
                            $curSort = request('sort', 'created_at');
                            $curDir = request('direction', 'desc');
                            $nextDir = $curDir === 'asc' ? 'desc' : 'asc';
                        @endphp
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <span>NO. BOX & JUDUL BERKAS</span>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <span>PEMOHON PENARIKAN</span>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'borrow_date', 'direction' => $curSort === 'borrow_date' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                                <span>TGL PENARIKAN</span>
                                @if($curSort === 'borrow_date')
                                    <span class="text-emerald-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <span>TUJUAN PENARIKAN</span>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $curSort === 'status' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                                <span>STATUS</span>
                                @if($curSort === 'status')
                                    <span class="text-emerald-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700 text-center">
                            <span>BERKAS</span>
                        </th>
                        <th class="py-2.5 px-3 text-right">
                            <span>AKSI</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-xs font-mono">
                    @php
                        $borrowingDetailsMap = [];
                        $borrowingFilesMap = [];
                    @endphp
                    @forelse($borrowings as $bLog)
                    @php
                        $borrowingFiles = [];
                        if ($bLog->effective_approval_file) {
                            $borrowingFiles[] = [
                                'name' => 'Scan Formulir Approval Penarikan',
                                'category' => 'Formulir Persetujuan Penarikan',
                                'url' => app_storage_url($bLog->effective_approval_file),
                                'stream_url' => app_preview_stream_url($bLog->effective_approval_file),
                                'raw_path' => $bLog->effective_approval_file,
                                'filename' => basename($bLog->effective_approval_file),
                                'ext' => strtolower(pathinfo($bLog->effective_approval_file, PATHINFO_EXTENSION)),
                            ];
                        }
                        if ($bLog->archive && $bLog->archive->file_path) {
                            $borrowingFiles[] = [
                                'name' => 'Lampiran Softcopy Arsip',
                                'category' => 'Softcopy Dokumen Box',
                                'url' => app_storage_url($bLog->archive->file_path),
                                'stream_url' => app_preview_stream_url($bLog->archive->file_path),
                                'raw_path' => $bLog->archive->file_path,
                                'filename' => basename($bLog->archive->file_path),
                                'ext' => strtolower(pathinfo($bLog->archive->file_path, PATHINFO_EXTENSION)),
                            ];
                        }
                        if ($bLog->archive && $bLog->archive->scan_input_form) {
                            $borrowingFiles[] = [
                                'name' => 'Scan Formulir Input Box',
                                'category' => 'Pendaftaran Awal Box',
                                'url' => app_storage_url($bLog->archive->scan_input_form),
                                'stream_url' => app_preview_stream_url($bLog->archive->scan_input_form),
                                'raw_path' => $bLog->archive->scan_input_form,
                                'filename' => basename($bLog->archive->scan_input_form),
                                'ext' => strtolower(pathinfo($bLog->archive->scan_input_form, PATHINFO_EXTENSION)),
                            ];
                        }
                        $hasBorrowingFiles = count($borrowingFiles) > 0;
                        $borrowingFilesPayload = [
                            'box_number' => $bLog->archive->box_number ?? 'DRAFT',
                            'title' => $bLog->archive->title ?? 'Penarikan Berkas',
                            'department' => $bLog->archive->department->code ?? '',
                            'files' => $borrowingFiles,
                            'initialIndex' => 0
                        ];
                        $borrowingFilesMap[$bLog->id] = $borrowingFilesPayload;

                        $detailPayload = [
                            'id' => $bLog->id,
                            'box_number' => $bLog->archive->box_number ?? 'DRAFT',
                            'archive_id' => $bLog->archive->id ?? null,
                            'archive_url' => $bLog->archive ? route('archives.show', array_merge(['archive' => $bLog->archive->id], request()->has('embed') ? ['embed' => 1] : [])) : '#',
                            'archive_title' => $bLog->archive->title ?? 'Arsip',
                            'department' => $bLog->archive->department->name ?? '-',
                            'department_code' => $bLog->archive->department->code ?? 'DEPT',
                            'sub_department' => $bLog->archive->subDepartment->name ?? '-',
                            'location' => $bLog->archive && $bLog->archive->location ? ($bLog->archive->location->full_location . ' (' . ($bLog->archive->location->warehouse->name ?? '') . ')') : 'Belum ditentukan',
                            'borrower_name' => $bLog->borrower->name ?? 'User',
                            'borrower_email' => $bLog->borrower->email ?? '-',
                            'request_date' => $bLog->request_date ? $bLog->request_date->format('d/m/Y H:i') : ($bLog->created_at ? $bLog->created_at->format('d/m/Y H:i') : '-'),
                            'borrow_date' => $bLog->borrow_date ? $bLog->borrow_date->format('d/m/Y H:i') : 'Belum dikeluarkan fisik',
                            'expected_return_date' => $bLog->expected_return_date ? $bLog->expected_return_date->format('d/m/Y') : 'Diambil Permanen (Tidak Dikembalikan)',
                            'actual_return_date' => $bLog->actual_return_date ? $bLog->actual_return_date->format('d/m/Y H:i') : ($bLog->status === 'returned' ? 'Selesai' : 'Belum dikembalikan'),
                            'status' => $bLog->status,
                            'status_label' => $bLog->status_label,
                            'purpose' => $bLog->purpose,
                            'notes' => $bLog->notes ?? '-',
                            'dept_approver' => $bLog->departmentApprovedBy->name ?? ($bLog->department_approval_by ? 'PIC Departemen' : '-'),
                            'dept_approved_at' => $bLog->department_approved_at ? $bLog->department_approved_at->format('d/m/Y H:i') : '-',
                            'pic_gudang' => $bLog->picGudang->name ?? ($bLog->pic_gudang_id ? 'PIC Gudang' : '-'),
                            'files' => $borrowingFiles,
                            'has_files' => $hasBorrowingFiles,
                            'items' => ($bLog->archive && $bLog->archive->items) ? $bLog->archive->items->map(function($it) {
                                return [
                                    'number' => $it->item_number,
                                    'name' => $it->document_name,
                                    'period' => $it->period_text,
                                    'notes' => $it->notes
                                ];
                            })->values()->toArray() : []
                        ];
                        $borrowingDetailsMap[$bLog->id] = $detailPayload;
                    @endphp
                    <tr class="hover:bg-amber-500/5 dark:hover:bg-slate-900/50 transition">
                        <!-- Kolom No Box & Judul -->
                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                            <div class="space-y-0.5">
                                <span class="font-mono text-xs text-amber-600 dark:text-amber-400 font-extrabold block">{{ $bLog->archive->box_number }}</span>
                                <a href="{{ route('archives.show', array_merge(['archive' => $bLog->archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="font-sans font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block truncate max-w-sm" title="{{ $bLog->archive->title }}">
                                    {{ $bLog->archive->title }}
                                </a>
                            </div>
                        </td>

                        <!-- Kolom Pemohon -->
                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                            <span class="font-bold text-slate-900 dark:text-slate-200 block">{{ $bLog->borrower->name ?? 'User' }}</span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase">{{ $bLog->archive->department->code ?? 'DEPT' }}</span>
                        </td>

                        <!-- Kolom Tanggal Penarikan -->
                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <div class="text-slate-800 dark:text-slate-200 font-bold">
                                {{ $bLog->borrow_date ? $bLog->borrow_date->format('d/m/Y') : ($bLog->request_date ? \Carbon\Carbon::parse($bLog->request_date)->format('d/m/Y') : 'Menunggu Dispatch') }}
                            </div>
                            <span class="text-[10px] text-slate-400 block font-normal">Diajukan: {{ $bLog->created_at->format('d/m/Y') }}</span>
                        </td>

                        <!-- Kolom Tujuan Penarikan -->
                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 max-w-xs font-sans text-slate-600 dark:text-slate-300 truncate" title="{{ $bLog->purpose }}">
                            {{ $bLog->purpose }}
                        </td>

                        <!-- Kolom Status Workflow (Mandiri / Terpisah) -->
                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($bLog->status === 'requested')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30">Diajukan User</span>
                            @elseif($bLog->status === 'dept_approved')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30">Disetujui Dept</span>
                            @elseif($bLog->status === 'approved')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/30">Disetujui Gudang</span>
                            @elseif($bLog->status === 'dispatched')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-500/30">Ditarik / Keluar</span>
                            @elseif($bLog->status === 'returned')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">Dikembalikan</span>
                            @endif
                        </td>

                        <!-- Kolom Berkas Terkait Penarikan (Format Sama Persis Katalog Arsip) -->
                        <td class="py-2.5 px-3 font-mono text-center border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($hasBorrowingFiles)
                                <button 
                                    type="button" 
                                    onclick="window.openBorrowingFilePreview({{ $bLog->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40 transition shadow-xs cursor-pointer group"
                                    title="Klik untuk preview berkas formulir & dokumen penarikan ({{ count($borrowingFiles) }} berkas)"
                                >
                                    <i data-lucide="paperclip" class="w-3 h-3 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform"></i>
                                    <span class="underline decoration-dotted underline-offset-2">Ada</span>
                                    <span class="text-[9px] px-1 py-0.2 rounded-full bg-emerald-600 text-white font-black leading-none ml-0.5">{{ count($borrowingFiles) }}</span>
                                </button>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-900 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-800 select-none">
                                    <i data-lucide="minus-circle" class="w-3 h-3 text-slate-400 dark:text-slate-600"></i>
                                    <span>Tidak Ada</span>
                                </span>
                            @endif
                        </td>

                        <!-- Kolom Aksi & Workflow -->
                        <td class="py-2.5 px-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <!-- Step 1 Approval: Admin Only (Disembunyikan untuk PIC Departemen) -->
                                @if($bLog->status === 'requested' && !auth()->user()->isPicDept() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()))
                                    <button onclick="document.getElementById('deptApproveModal-{{ $bLog->id }}').classList.remove('hidden')" type="button" class="px-2.5 py-1 bg-cyan-600 hover:bg-cyan-500 text-white text-[11px] font-bold rounded border border-cyan-700 transition shadow-sm inline-flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        <span>Approve Dept</span>
                                    </button>

                                    <!-- Modal Dept Approve -->
                                    <div id="deptApproveModal-{{ $bLog->id }}" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 text-left">
                                        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded shadow-2xl p-5 max-w-md w-full space-y-4 font-mono text-xs">
                                            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                                                <h3 class="font-bold text-slate-900 dark:text-white uppercase flex items-center gap-1.5">
                                                    <i data-lucide="shield-check" class="w-4 h-4 text-cyan-600"></i>
                                                    <span>Persetujuan Penarikan Departemen</span>
                                                </h3>
                                                <button type="button" onclick="document.getElementById('deptApproveModal-{{ $bLog->id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </button>
                                            </div>

                                            @if($bLog->effective_approval_file)
                                            <div class="p-2.5 bg-emerald-500/10 border border-emerald-500/30 rounded flex items-center justify-between">
                                                <div class="flex items-center gap-1.5 text-emerald-800 dark:text-emerald-300 font-bold text-[11px]">
                                                    <i data-lucide="file-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                                    <span>Surat Permohonan Terlampir</span>
                                                </div>
                                                <button type="button" 
                                                        onclick="window.openBorrowingFilePreview({{ $bLog->id }})"
                                                        class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[10px] font-bold transition flex items-center gap-1 cursor-pointer">
                                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                                    <span>Lihat Berkas</span>
                                                </button>
                                            </div>
                                            @endif

                                            <form action="{{ route('borrowings.dept_approve', $bLog) }}" method="POST" enctype="multipart/form-data" class="space-y-3 font-sans">
                                                @csrf
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Unggah Berkas Tambahan / Pengganti (Opsional)</label>
                                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1">Surat permohonan sudah ada di atas. Anda tidak perlu mengunggah ulang jika berkas sudah sesuai.</p>
                                                    <input type="file" name="scan_approval_borrow" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white">
                                                </div>
                                                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800 font-mono">
                                                    <button type="button" onclick="document.getElementById('deptApproveModal-{{ $bLog->id }}').classList.add('hidden')" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold">Batal</button>
                                                    <button type="submit" class="px-3 py-1 bg-cyan-600 hover:bg-cyan-500 text-white border border-cyan-700 rounded text-xs font-bold transition">Sahkan & Teruskan ke Gudang</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif

                                <!-- Step 2 Approval & Output: PIC Gudang / Admin -->
                                @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                                    @if($bLog->status === 'requested' && !auth()->user()->isPicDept())
                                        <span class="text-[11px] text-amber-600 font-bold block">Menunggu Approval Dept</span>
                                    @elseif($bLog->status === 'dept_approved' || $bLog->status === 'approved')
                                        <button onclick="document.getElementById('dispatchModal-{{ $bLog->id }}').classList.remove('hidden')" type="button" class="px-2.5 py-1 bg-purple-600 hover:bg-purple-500 text-white text-[11px] font-bold rounded border border-purple-700 transition shadow-sm inline-flex items-center gap-1 cursor-pointer">
                                            <i data-lucide="package" class="w-3 h-3"></i>
                                            <span>Dispatch Berkas</span>
                                        </button>

                                        <!-- Modal Dispatch Gudang -->
                                        <div id="dispatchModal-{{ $bLog->id }}" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 text-left">
                                            <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded shadow-2xl p-5 max-w-md w-full space-y-4 font-mono text-xs">
                                                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                                                    <h3 class="font-bold text-slate-900 dark:text-white uppercase flex items-center gap-1.5">
                                                        <i data-lucide="package-check" class="w-4 h-4 text-purple-600"></i>
                                                        <span>Pengeluaran Berkas Fisik Gudang</span>
                                                    </h3>
                                                    <button type="button" onclick="document.getElementById('dispatchModal-{{ $bLog->id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                                                        <i data-lucide="x" class="w-4 h-4"></i>
                                                    </button>
                                                </div>

                                                <form action="{{ route('borrowings.dispatch', $bLog) }}" method="POST" enctype="multipart/form-data" class="space-y-3 font-sans">
                                                    @csrf
                                                    @if($bLog->effective_approval_file)
                                                    <div class="p-2.5 bg-emerald-500/10 border border-emerald-500/30 rounded flex items-center justify-between font-mono">
                                                        <div class="flex items-center gap-1.5 text-emerald-800 dark:text-emerald-300 font-bold text-[11px]">
                                                            <i data-lucide="file-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                                            <span>Surat Approval Terlampir</span>
                                                        </div>
                                                        <button type="button" 
                                                                onclick="window.openBorrowingFilePreview({{ $bLog->id }})"
                                                                class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[10px] font-bold transition flex items-center gap-1 cursor-pointer">
                                                            <i data-lucide="eye" class="w-3 h-3"></i>
                                                            <span>Lihat Berkas</span>
                                                        </button>
                                                    </div>
                                                    @endif
                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Upload Scan Bukti Tanda Terima Fisik (Opsional)</label>
                                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1">Berkas approval sudah tersedia di atas. Bagian ini hanya diisi jika ada berkas tanda terima fisik terpisah saat penyerahan box.</p>
                                                        <input type="file" name="scan_approval_borrow" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white">
                                                    </div>
                                                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800 font-mono">
                                                        <button type="button" onclick="document.getElementById('dispatchModal-{{ $bLog->id }}').classList.add('hidden')" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold">Batal</button>
                                                        <button type="submit" class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white border border-purple-700 rounded text-xs font-bold">Sahkan & Keluarkan Berkas</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @elseif($bLog->status === 'dispatched')
                                        <form action="{{ route('borrowings.return', $bLog) }}" method="POST" class="inline" @submit="submitting = true">
                                            @csrf
                                            <button type="submit" 
                                                    data-confirm="Konfirmasi pengembalian berkas fisik ke gudang?" 
                                                    data-confirm-title="Pengembalian Berkas Fisik"
                                                    data-confirm-type="success"
                                                    data-confirm-btn="Ya, Konfirmasi Kembali"
                                                    :disabled="submitting" 
                                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold rounded border border-emerald-700 transition shadow-sm inline-flex items-center gap-1 disabled:opacity-50 cursor-pointer">
                                                <i data-lucide="loader-2" class="w-3 h-3 animate-spin" x-show="submitting"></i>
                                                <i data-lucide="rotate-ccw" class="w-3 h-3" x-show="!submitting"></i>
                                                <span>Konfirmasi Kembali</span>
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                <!-- Edit Action Button (Lengkapi file jika kosong / Edit form) -->
                                <!-- Detail Action Button (Sama seperti katalog arsip) -->
                                <button type="button" 
                                        onclick="window.openBorrowingDetailModal({{ $bLog->id }})" 
                                        class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-emerald-500 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                        title="Lihat Detail Penarikan & Konten Box Arsip">
                                    <span>Detail</span>
                                    <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 px-4 text-center font-mono text-xs text-slate-500 dark:text-slate-400">
                            <i data-lucide="inbox" class="w-8 h-8 text-slate-400 mx-auto mb-2 opacity-50"></i>
                            Belum ada riwayat pengajuan penarikan berkas arsip.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-2.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 font-mono text-xs">
            {{ $borrowings->links('vendor.pagination.tailwind') }}
        </div>
    </div>

    <!-- DELPHI MODAL: DETAIL PENARIKAN & KONTEN ARSIP -->
    <div x-show="detailModalData" 
         x-transition.opacity
         style="display: none;"
         class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col font-mono text-xs" 
             @click.away="closeBorrowingDetail()">
            
            <!-- Modal Delphi Ribbon Header -->
            <div class="p-3 bg-gradient-to-r from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="p-1 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded shrink-0">
                        <i data-lucide="file-symlink" class="w-4 h-4"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate flex items-center gap-1.5">
                            <span>DETAIL PENARIKAN:</span>
                            <span class="text-amber-600 dark:text-amber-400 font-extrabold" x-text="detailModalData ? detailModalData.box_number : ''"></span>
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans truncate" x-text="detailModalData ? detailModalData.archive_title : ''"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                          :class="{
                              'bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30': detailModalData && detailModalData.status === 'requested',
                              'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30': detailModalData && detailModalData.status === 'dept_approved',
                              'bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/30': detailModalData && detailModalData.status === 'approved',
                              'bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-500/30': detailModalData && detailModalData.status === 'dispatched',
                              'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30': detailModalData && detailModalData.status === 'returned'
                          }"
                          x-text="detailModalData ? detailModalData.status_label : ''">
                    </span>
                    <button type="button" @click="closeBorrowingDetail()" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded transition">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-4 space-y-3 overflow-y-auto max-h-[calc(90vh-110px)]" x-show="detailModalData">
                <!-- GroupBox 1: Informasi Penarikan Berkas -->
                <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-slate-50 dark:bg-slate-900/40 space-y-2">
                    <legend class="px-1.5 font-bold text-emerald-700 dark:text-emerald-400 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="info" class="w-3 h-3 text-emerald-500"></i>
                        Data Pengajuan & Fisik
                    </legend>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Pemohon</span>
                            <span class="font-bold text-slate-900 dark:text-white truncate block" x-text="detailModalData.borrower_name"></span>
                            <span class="text-[10px] text-slate-500 truncate block" x-text="detailModalData.borrower_email"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Departemen Box</span>
                            <span class="font-bold text-slate-900 dark:text-white truncate block" x-text="detailModalData.department + ' (' + detailModalData.department_code + ')'"></span>
                            <span class="text-[10px] text-slate-500 truncate block" x-text="detailModalData.sub_department"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Lokasi Rak Gudang</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 text-[11px] block" x-text="detailModalData.location"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Tgl Diajukan</span>
                            <span class="font-bold text-slate-900 dark:text-white block" x-text="detailModalData.request_date"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Tgl Keluar Fisik</span>
                            <span class="font-bold text-slate-900 dark:text-white block" x-text="detailModalData.borrow_date"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Estimasi Kembali</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400 block" x-text="detailModalData.expected_return_date"></span>
                        </div>
                    </div>

                    <!-- Keperluan Penarikan -->
                    <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                        <span class="text-[10px] text-slate-400 block font-bold uppercase">Keperluan / Tujuan Penarikan:</span>
                        <p class="font-sans text-xs text-slate-800 dark:text-slate-200 pt-0.5 whitespace-pre-wrap" x-text="detailModalData.purpose"></p>
                    </div>

                    <template x-if="detailModalData.notes && detailModalData.notes !== '-'">
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Catatan Tambahan:</span>
                            <p class="font-sans text-xs text-slate-800 dark:text-slate-200 pt-0.5" x-text="detailModalData.notes"></p>
                        </div>
                    </template>
                </fieldset>

                <!-- GroupBox 2: Konten / Rincian Butir Dokumen Dalam Box -->
                <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 space-y-2">
                    <legend class="px-1.5 font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="list-checks" class="w-3 h-3 text-emerald-500"></i>
                        <span>Butir Dokumen Dalam Box (<span x-text="detailModalData.items ? detailModalData.items.length : 0"></span> Berkas)</span>
                    </legend>
                    <template x-if="detailModalData.items && detailModalData.items.length > 0">
                        <div class="border border-slate-300 dark:border-slate-800 rounded overflow-hidden">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 dark:bg-slate-900 text-[10px] font-bold uppercase text-slate-600 dark:text-slate-400 border-b border-slate-300 dark:border-slate-700">
                                        <th class="py-1.5 px-2 w-10 text-center border-r border-slate-300 dark:border-slate-700">No</th>
                                        <th class="py-1.5 px-2 border-r border-slate-300 dark:border-slate-700">Nama Dokumen</th>
                                        <th class="py-1.5 px-2 border-r border-slate-300 dark:border-slate-700 w-32">Periode</th>
                                        <th class="py-1.5 px-2">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-[11px]">
                                    <template x-for="item in detailModalData.items" :key="item.number">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/60">
                                            <td class="py-1 px-2 text-center font-bold text-purple-600 dark:text-purple-400 border-r border-slate-200 dark:border-slate-800" x-text="item.number"></td>
                                            <td class="py-1 px-2 font-bold font-sans text-slate-900 dark:text-white border-r border-slate-200 dark:border-slate-800" x-text="item.name"></td>
                                            <td class="py-1 px-2 text-amber-600 dark:text-amber-400 border-r border-slate-200 dark:border-slate-800" x-text="item.period || '-'"></td>
                                            <td class="py-1 px-2 font-sans text-slate-500" x-text="item.notes || '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!detailModalData.items || detailModalData.items.length === 0">
                        <p class="text-slate-400 italic text-[11px] py-1 text-center font-sans">Tidak ada rincian butir dokumen spesifik untuk box ini.</p>
                    </template>
                </fieldset>

                <!-- GroupBox 3: Berkas Dokumen & Form Approval -->
                <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 space-y-2">
                    <legend class="px-1.5 font-bold text-blue-700 dark:text-blue-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="paperclip" class="w-3 h-3 text-blue-500"></i>
                        Berkas Terlampir
                    </legend>
                    <template x-if="detailModalData.has_files">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="(file, idx) in detailModalData.files" :key="idx">
                                <div class="p-2 rounded bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 dark:text-white text-xs block truncate" x-text="file.name"></span>
                                        <span class="text-[10px] text-slate-400 block truncate" x-text="file.category + ' (' + file.ext.toUpperCase() + ')'"></span>
                                    </div>
                                    <button type="button" 
                                            @click="dmsPreviewFile({
                                                box_number: detailModalData.box_number,
                                                title: detailModalData.archive_title,
                                                department: detailModalData.department_code,
                                                files: detailModalData.files,
                                                raw_path: file.raw_path,
                                                url: file.url,
                                                stream_url: file.stream_url,
                                                name: file.name,
                                                ext: file.ext
                                            })"
                                            class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[10px] font-bold transition flex items-center gap-1 cursor-pointer shrink-0">
                                        <i data-lucide="eye" class="w-3 h-3"></i>
                                        <span>Lihat</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!detailModalData.has_files">
                        <div class="p-3 text-center text-rose-500 bg-rose-500/10 border border-rose-500/30 rounded text-[11px]">
                            <i data-lucide="alert-circle" class="w-4 h-4 mx-auto mb-1"></i>
                            Berkas surat permohonan / persetujuan belum diunggah.
                        </div>
                    </template>
                </fieldset>

                <!-- GroupBox 4: Audit Trail Persetujuan -->
                <fieldset class="border border-slate-300 dark:border-slate-800 p-2.5 rounded bg-slate-50 dark:bg-slate-900/40 text-[11px] space-y-1">
                    <legend class="px-1.5 font-bold text-cyan-700 dark:text-cyan-400 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="shield-check" class="w-3 h-3 text-cyan-500"></i>
                        Jejak Persetujuan & Penyerahan
                    </legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-0.5">
                        <div class="p-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Approval Dept:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="detailModalData.dept_approver"></span>
                            <span class="text-[10px] text-slate-400 block" x-text="detailModalData.dept_approved_at"></span>
                        </div>
                        <div class="p-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Pengeluaran Gudang:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="detailModalData.pic_gudang"></span>
                            <span class="text-[10px] text-slate-400 block" x-text="detailModalData.borrow_date"></span>
                        </div>
                    </div>
                </fieldset>
            </div>

            <!-- Modal Footer Ribbon -->
            <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between gap-2 shrink-0">
                <a :href="detailModalData ? detailModalData.archive_url : '#'" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold transition flex items-center gap-1">
                    <i data-lucide="folder-open" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span>Buka Detail Master Arsip</span>
                </a>

                <div class="flex items-center gap-2">
                    <button type="button" @click="closeBorrowingDetail()" class="px-3 py-1 bg-slate-300 dark:bg-slate-800 hover:bg-slate-400 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.borrowingDetailsMap = @json($borrowingDetailsMap ?? []);
    window.borrowingFilesMap = @json($borrowingFilesMap ?? []);

    window.openBorrowingDetailModal = function(id) {
        if (window.borrowingDetailsMap && window.borrowingDetailsMap[id]) {
            window.dispatchEvent(new CustomEvent('open-borrowing-detail-modal', {
                detail: window.borrowingDetailsMap[id]
            }));
        }
    };

    window.openBorrowingFilePreview = function(id) {
        if (window.borrowingFilesMap && window.borrowingFilesMap[id]) {
            window.dmsPreviewFile(window.borrowingFilesMap[id]);
        }
    };
</script>
@endsection

