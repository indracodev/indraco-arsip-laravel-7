@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Penarikan Dokumen Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-3" 
     x-data="{ 
         submitting: false,
         detailModalData: null,
         dispatchModalData: null,
         rejectModalData: null,
         openBorrowingDetail(data) {
             this.detailModalData = data;
             this.$nextTick(() => {
                 if (window.lucide) lucide.createIcons();
             });
         },
         closeBorrowingDetail() {
             this.detailModalData = null;
         },
         openBorrowingDispatch(data) {
             this.dispatchModalData = data;
             this.$nextTick(() => {
                 if (window.lucide) lucide.createIcons();
             });
         },
         closeBorrowingDispatch() {
             this.dispatchModalData = null;
         },
         openBorrowingReject(data) {
             this.rejectModalData = data;
             this.$nextTick(() => {
                 if (window.lucide) lucide.createIcons();
             });
         },
         closeBorrowingReject() {
             this.rejectModalData = null;
         },
         selectedBorrowingIds: [],
         toggleSelectAll() {
             const eligible = @json($borrowings->filter(fn($b) => $b->restore_eligibility['eligible'])->pluck('id')->values()->toArray());
             if (this.selectedBorrowingIds.length === eligible.length) {
                 this.selectedBorrowingIds = [];
             } else {
                 this.selectedBorrowingIds = [...eligible];
             }
         },
         submitRestoring() {
             if (this.selectedBorrowingIds.length === 0) return;
             const params = new URLSearchParams();
             params.append('re_store', '1');
             params.append('from', 'borrowings');
             this.selectedBorrowingIds.forEach(id => params.append('borrowing_ids[]', id));
             @if(request()->has('embed'))
                 params.append('embed', '1');
             @endif
             window.location.href = '{{ route('archives.create') }}?' + params.toString();
         }
     }"
     @open-borrowing-detail-modal.window="openBorrowingDetail($event.detail)"
     @open-borrowing-dispatch-modal.window="openBorrowingDispatch($event.detail)"
     @open-borrowing-reject-modal.window="openBorrowingReject($event.detail)"
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

            @if(!auth()->user()->isPicGudang())
            <!-- Tombol Aksi Simpan Kembali (Multi-Select) -->
            <button 
                type="button" 
                x-show="selectedBorrowingIds.length > 0"
                x-transition
                @click="submitRestoring()"
                class="px-3 py-1 bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-500 hover:to-purple-400 text-white font-mono font-bold text-xs rounded border border-purple-700 shadow-sm transition flex items-center gap-1.5 shrink-0 cursor-pointer animate-pulse"
                title="Ajukan penyimpanan kembali untuk seluruh box yang dipilih"
            >
                <i data-lucide="package-plus" class="w-3.5 h-3.5 text-white"></i>
                <span>Ajukan Penyimpanan Kembali (<span x-text="selectedBorrowingIds.length"></span> Box)</span>
            </button>

            <!-- Pengajuan Penarikan Berkas (F2) -->
            <a href="{{ route('borrowings.create', array_merge(['from' => 'borrowings'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3 py-1 bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 hover:to-emerald-300 text-slate-950 font-mono font-black text-xs rounded border border-emerald-600 shadow transition flex items-center gap-1.5 shrink-0" title="Ajukan Penarikan Berkas Baru">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Buat Pengajuan (F2)</span>
            </a>
            @endif
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
                    <option value="dispatched" {{ request('status') == 'dispatched' ? 'selected' : '' }}>Sudah Ditarik</option>
                    <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Dikembalikan ke Gudang</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
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

    <!-- WORKFLOW COLOR LEGEND BAR (PENARIKAN BERKAS) -->
    <div class="flex items-center flex-wrap gap-2 text-[11px] font-mono py-1.5 px-3 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded select-none shadow-xs">
        <span class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider mr-1 text-[10px] flex items-center gap-1">
            <i data-lucide="palette" class="w-3.5 h-3.5 text-slate-400"></i>
            <span>Pembeda Workflow:</span>
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border-l-4 border-l-amber-500 font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Diajukan User (Data Baru)
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-500/15 text-blue-800 dark:text-blue-300 border-l-4 border-l-blue-500 font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Disetujui Gudang (Siap Ambil)
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-purple-500/15 text-purple-800 dark:text-purple-300 border-l-4 border-l-purple-500 font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Sudah Ditarik (Fisik Berkas Out)
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border-l-2 border-l-emerald-500 font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Dikembalikan (Selesai)
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-rose-500/15 text-rose-800 dark:text-rose-300 border-l-2 border-l-rose-500 font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Ditolak
        </span>
    </div>

    <!-- REALTIME NOTIFICATION BANNER (PENARIKAN BERKAS) -->
    <div id="borrowing_realtime_alert" style="display:none;" class="bg-amber-500/15 border border-amber-500/40 rounded p-2.5 flex items-center justify-between gap-2 text-xs font-mono text-amber-900 dark:text-amber-300 shadow-sm animate-pulse">
        <div class="flex items-center gap-2">
            <i data-lucide="bell-ring" class="w-4 h-4 text-amber-500 shrink-0"></i>
            <span>Ada permohonan penarikan berkas baru masuk atau status penarikan telah diperbarui.</span>
        </div>
        <button type="button" onclick="window.location.reload()" class="px-2.5 py-1 bg-amber-500 text-slate-950 font-bold rounded hover:bg-amber-400 transition cursor-pointer shrink-0">
            Muat Ulang
        </button>
    </div>

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
                        @php
                            $eligibleBorrowingIds = $borrowings->filter(fn($b) => $b->restore_eligibility['eligible'])->pluck('id')->values()->toArray();
                        @endphp
                        <th class="py-2.5 px-2.5 border-r border-slate-300 dark:border-slate-700 w-10 text-center">
                            @if(!auth()->user()->isPicGudang() && count($eligibleBorrowingIds) > 0)
                                <input 
                                    type="checkbox" 
                                    :checked="selectedBorrowingIds.length > 0 && selectedBorrowingIds.length === {{ count($eligibleBorrowingIds) }}"
                                    @change="toggleSelectAll()"
                                    class="w-3.5 h-3.5 rounded border-slate-300 text-purple-600 focus:ring-purple-500 cursor-pointer"
                                    title="Pilih Semua Box Berstatus Sudah Ditarik"
                                >
                            @else
                                <span class="text-slate-400 dark:text-slate-600 font-normal text-xs">&bull;</span>
                            @endif
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <span>NO. BOX & JUDUL BERKAS</span>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700 whitespace-nowrap">
                            <span>LOKASI POSISI RAK</span>
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

                        $rejectionHistory = [];
                        if ($bLog->archive && $bLog->archive->borrowingLogs) {
                            $rejectionHistory = $bLog->archive->borrowingLogs
                                ->where('status', 'rejected')
                                ->where('id', '!=', $bLog->id)
                                ->map(function ($rej) {
                                    return [
                                        'id' => $rej->id,
                                        'date' => $rej->updated_at ? $rej->updated_at->format('d/m/Y H:i') : ($rej->created_at ? $rej->created_at->format('d/m/Y H:i') : '-'),
                                        'request_date' => $rej->request_date ? $rej->request_date->format('d/m/Y H:i') : ($rej->created_at ? $rej->created_at->format('d/m/Y H:i') : '-'),
                                        'notes' => $rej->approval_notes ?: 'Pengajuan penarikan berkas ditolak oleh petugas.',
                                        'borrower' => $rej->borrower ? $rej->borrower->name : 'User',
                                        'purpose' => $rej->purpose,
                                    ];
                                })->values()->toArray();
                        }

                        $detailPayload = [
                            'id' => $bLog->id,
                            'box_number' => $bLog->archive->box_number ?? 'DRAFT',
                            'archive_id' => $bLog->archive->id ?? null,
                            'archive_url' => $bLog->archive ? route('archives.show', array_merge(['archive' => $bLog->archive->id], request()->has('embed') ? ['embed' => 1] : [])) : '#',
                            'archive_title' => $bLog->archive->title ?? 'Arsip',
                            'department' => $bLog->archive->department->name ?? '-',
                            'department_code' => $bLog->archive->department->code ?? 'DEPT',
                            'sub_department' => $bLog->archive->subDepartment->name ?? '-',
                            'location' => $bLog->archive && $bLog->archive->display_location ? $bLog->archive->display_location : ($bLog->archive && $bLog->archive->location ? ($bLog->archive->location->full_location . ' (' . ($bLog->archive->location->warehouse->name ?? '') . ')') : 'Belum ditentukan'),
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
                            'dispatch_url' => \Illuminate\Support\Facades\Route::has('borrowings.dispatch') ? route('borrowings.dispatch', $bLog) : url("/borrowings/{$bLog->id}/dispatch"),
                            'reject_url' => \Illuminate\Support\Facades\Route::has('borrowings.reject') ? route('borrowings.reject', $bLog) : url("/borrowings/{$bLog->id}/reject"),
                            'approval_notes' => $bLog->approval_notes,
                            'rejection_history' => $rejectionHistory,
                            'has_previous_rejections' => count($rejectionHistory) > 0,
                            'items' => (!auth()->user()->isPicGudang() && $bLog->archive && $bLog->archive->items) ? $bLog->archive->items->map(function($it) {
                                return [
                                    'number' => $it->item_number,
                                    'name' => $it->document_name,
                                    'period' => $it->period_text,
                                    'notes' => $it->notes
                                ];
                            })->values()->toArray() : []
                        ];
                        $borrowingDetailsMap[$bLog->id] = $detailPayload;
                        // Determine Borrowing Workflow Row Styles & Badges
                        $rowColorClass = '';
                        $isNewRequest = false;

                        if ($bLog->status === 'requested') {
                            $rowColorClass = 'border-l-4 border-l-amber-500 bg-amber-500/[0.07] dark:bg-amber-500/15 hover:bg-amber-500/20';
                            $isNewRequest = true;
                        } elseif ($bLog->status === 'dept_approved') {
                            $rowColorClass = 'border-l-4 border-l-cyan-500 bg-cyan-500/[0.07] dark:bg-cyan-500/15 hover:bg-cyan-500/20';
                        } elseif ($bLog->status === 'approved') {
                            $rowColorClass = 'border-l-4 border-l-blue-500 bg-blue-500/[0.07] dark:bg-blue-500/15 hover:bg-blue-500/20';
                        } elseif ($bLog->status === 'dispatched') {
                            $rowColorClass = 'border-l-4 border-l-purple-500 bg-purple-500/[0.07] dark:bg-purple-500/15 hover:bg-purple-500/20';
                        } elseif ($bLog->status === 'returned') {
                            $rowColorClass = 'border-l-2 border-l-emerald-500/50 hover:bg-emerald-500/5';
                        } elseif ($bLog->status === 'rejected') {
                            $rowColorClass = 'border-l-2 border-l-rose-500/50 hover:bg-rose-500/5 opacity-80';
                        } else {
                            $rowColorClass = 'hover:bg-slate-50 dark:hover:bg-slate-900/30';
                        }
                    @endphp
                    <tr class="transition duration-150 {{ $rowColorClass }}">
                        @php
                            $eligibility = $bLog->restore_eligibility;
                        @endphp
                        <!-- Kolom Checkbox Simpan Kembali -->
                        <td class="py-2.5 px-2.5 border-r border-slate-200 dark:border-slate-800 text-center">
                            @if(!auth()->user()->isPicGudang() && $bLog->status === 'dispatched')
                                @if($eligibility['eligible'])
                                    <input 
                                        type="checkbox" 
                                        value="{{ $bLog->id }}" 
                                        x-model="selectedBorrowingIds" 
                                        class="w-3.5 h-3.5 rounded border-slate-300 text-purple-600 focus:ring-purple-500 cursor-pointer"
                                        title="Centang untuk simpan kembali box ini"
                                    >
                                @else
                                    <span class="inline-block" title="{{ $eligibility['reason'] }}">
                                        <input type="checkbox" disabled class="w-3.5 h-3.5 rounded border-slate-200 opacity-25 cursor-not-allowed">
                                    </span>
                                @endif
                            @else
                                <span class="text-slate-300 dark:text-slate-700 text-xs font-mono">&bull;</span>
                            @endif
                        </td>

                        <!-- Kolom No Box & Judul -->
                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($isNewRequest)
                                        <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 font-black text-[9px] uppercase tracking-wider animate-pulse shadow-xs" title="Permohonan Penarikan Baru Menunggu Persetujuan">BARU</span>
                                    @elseif($bLog->status === 'dept_approved')
                                        <span class="px-1.5 py-0.2 rounded bg-cyan-500 text-slate-950 font-black text-[9px] uppercase tracking-wider shadow-xs" title="Telah Disetujui Departemen">VERIF DEPT</span>
                                    @elseif($bLog->status === 'approved')
                                        <span class="px-1.5 py-0.2 rounded bg-blue-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Disetujui Gudang, Siap Diambil">SIAP AMBIL</span>
                                    @elseif($bLog->status === 'dispatched')
                                        <span class="px-1.5 py-0.2 rounded bg-purple-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Berkas Sudah Ditarik Fisik">DITARIK</span>
                                    @elseif($bLog->status === 'returned')
                                        <span class="px-1.5 py-0.2 rounded bg-emerald-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Telah Dikembalikan ke Gudang">KEMBALI</span>
                                    @elseif($bLog->status === 'rejected')
                                        <span class="px-1.5 py-0.2 rounded bg-rose-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Permohonan Ditolak">DITOLAK</span>
                                    @endif

                                    @if($bLog->status !== 'rejected' && count($rejectionHistory) > 0)
                                        <span class="px-1.5 py-0.2 rounded bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30 font-black text-[9px] uppercase tracking-wider shadow-xs" title="Box ini pernah ditolak pada pengajuan sebelumnya. Klik Detail untuk melihat log riwayat penolakan.">PERNAH DITOLAK</span>
                                    @endif

                                    <span class="font-mono text-xs text-amber-600 dark:text-amber-400 font-extrabold">{{ $bLog->effective_box_number }}</span>
                                </div>
                                <a href="{{ route('archives.show', array_merge(['archive' => $bLog->archive ? $bLog->archive->id : '#'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="font-sans font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block truncate max-w-sm" title="{{ $bLog->effective_title }}">
                                    {{ $bLog->effective_title }}
                                </a>
                            </div>
                        </td>

                        <!-- Kolom Lokasi Posisi / Rak Gudang -->
                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($bLog->archive && ($bLog->archive->display_location || $bLog->archive->location))
                                <div class="flex items-center gap-1.5 font-bold text-emerald-600 dark:text-emerald-400 font-mono text-xs" title="{{ $bLog->archive->full_slot_location ?? $bLog->archive->display_location }}">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                                    <span>{{ $bLog->archive->display_location ?? ($bLog->archive->location ? $bLog->archive->location->full_location : 'Gudang') }}</span>
                                </div>
                                @if($bLog->archive->rackSlot)
                                    <span class="text-[10px] text-slate-400 block font-mono pl-5">Sap {{ $bLog->archive->rackSlot->sap_level }} &bull; Slot {{ str_pad($bLog->archive->rackSlot->slot_number, 2, '0', STR_PAD_LEFT) }}</span>
                                @endif
                            @else
                                <span class="text-slate-400 dark:text-slate-500 text-[11px] italic font-mono flex items-center gap-1">
                                    <i data-lucide="help-circle" class="w-3.5 h-3.5 text-slate-500 shrink-0"></i>
                                    <span>Belum ditentukan</span>
                                </span>
                            @endif
                        </td>

                        <!-- Kolom Pemohon -->
                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                            <span class="font-bold text-slate-900 dark:text-slate-200 block">{{ $bLog->borrower->name ?? 'User' }}</span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase">{{ $bLog->effective_department_code }}</span>
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
                                <div class="space-y-0.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-500/30">Sudah Ditarik</span>
                                    @if($bLog->archive && $bLog->archive->status === 'in_warehouse')
                                        <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold block leading-tight">&bull; Sudah di Gudang</span>
                                    @elseif($bLog->archive && in_array($bLog->archive->status, ['pending_verification', 'approved_booked']))
                                        <span class="text-[9px] text-amber-600 dark:text-amber-400 font-bold block leading-tight animate-pulse">&bull; Menunggu Simpan</span>
                                    @endif
                                </div>
                            @elseif($bLog->status === 'returned')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">Dikembalikan</span>
                            @elseif($bLog->status === 'rejected')
                                <div class="space-y-1">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30">
                                        <i data-lucide="x-circle" class="w-3 h-3 text-rose-500"></i>
                                        <span>Ditolak</span>
                                    </span>
                                    @if($bLog->approval_notes)
                                        <div class="text-[10px] text-rose-700 dark:text-rose-300 font-sans max-w-[220px] bg-rose-50 dark:bg-rose-950/40 p-1.5 rounded border border-rose-200 dark:border-rose-900/50 leading-tight">
                                            <span class="font-bold block text-[9px] uppercase font-mono text-rose-600 dark:text-rose-400">Catatan Penolakan:</span>
                                            {{ $bLog->approval_notes }}
                                        </div>
                                    @endif
                                </div>
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

                                    @include('borrowings.partials.dept_approve_modal')
                                @endif

                                <!-- Step 2 Approval & Output: PIC Gudang / Admin -->
                                @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                                    @if(in_array($bLog->status, ['requested', 'dept_approved', 'approved']))
                                        <button type="button" 
                                                onclick="window.openBorrowingDispatchModal({{ $bLog->id }})" 
                                                class="px-2.5 py-1 bg-purple-600 hover:bg-purple-500 text-white text-[11px] font-bold rounded border border-purple-700 transition shadow-sm inline-flex items-center gap-1 cursor-pointer" 
                                                title="Periksa berkas & keluarkan arsip fisik">
                                            <i data-lucide="package-check" class="w-3.5 h-3.5"></i>
                                            <span>Keluarkan Arsip</span>
                                        </button>

                                        <button type="button" 
                                                onclick="window.openBorrowingRejectModal({{ $bLog->id }})" 
                                                class="px-2.5 py-1 bg-rose-600 hover:bg-rose-500 text-white text-[11px] font-bold rounded border border-rose-700 transition shadow-sm inline-flex items-center gap-1 cursor-pointer" 
                                                title="Tolak pengajuan penarikan berkas arsip">
                                            <i data-lucide="ban" class="w-3.5 h-3.5"></i>
                                            <span>Tolak</span>
                                        </button>
                                    @endif
                                @endif

                                <!-- Tombol Tarik Ulang untuk pengajuan yang ditolak -->
                                @if($bLog->status === 'rejected' && $bLog->archive && $bLog->archive->status === 'in_warehouse' && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || (auth()->user()->isPicDept() && (int)$bLog->archive->department_id === (int)auth()->user()->department_id)))
                                    <a href="{{ route('borrowings.create', array_merge(['archive_id' => $bLog->archive_id, 'from' => 'borrowings'], request()->has('embed') ? ['embed' => 1] : [])) }}" 
                                       class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold rounded border border-amber-700 transition shadow-sm inline-flex items-center gap-1 cursor-pointer"
                                       title="Ajukan penarikan ulang untuk box ini">
                                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                        <span>Tarik Ulang</span>
                                    </a>
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
                        <td colspan="9" class="py-8 px-4 text-center font-mono text-xs text-slate-500 dark:text-slate-400">
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


    <!-- MODULAR BORROWING ACTION MODALS -->
    @include('borrowings.modals.detail_modal')
    @include('borrowings.modals.dispatch_modal')
    @include('borrowings.modals.reject_modal')
</div>

@push('scripts')
@include('borrowings.partials.borrowing_scripts')
@endpush
@endsection
