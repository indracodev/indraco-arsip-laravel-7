@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Detail Arsip - ' . $archive->title)

@section('content')
@php
    // Prepare all available digital files for multi-file viewer capability
    $archiveFiles = [];
    if (!empty($archive->scan_input_form)) {
        $archiveFiles[] = [
            'name' => 'Scan Formulir Input',
            'url' => app_storage_url($archive->scan_input_form),
            'stream_url' => app_preview_stream_url($archive->scan_input_form),
            'raw_path' => $archive->scan_input_form,
            'ext' => strtolower(pathinfo($archive->scan_input_form, PATHINFO_EXTENSION)),
        ];
    }
    if (!empty($archive->scan_approval_input)) {
        $archiveFiles[] = [
            'name' => 'Scan Approval Input',
            'url' => app_storage_url($archive->scan_approval_input),
            'stream_url' => app_preview_stream_url($archive->scan_approval_input),
            'raw_path' => $archive->scan_approval_input,
            'ext' => strtolower(pathinfo($archive->scan_approval_input, PATHINFO_EXTENSION)),
        ];
    }
    if (!empty($archive->scan_extension_form)) {
        $archiveFiles[] = [
            'name' => 'Scan Form Perpanjangan',
            'url' => app_storage_url($archive->scan_extension_form),
            'stream_url' => app_preview_stream_url($archive->scan_extension_form),
            'raw_path' => $archive->scan_extension_form,
            'ext' => strtolower(pathinfo($archive->scan_extension_form, PATHINFO_EXTENSION)),
        ];
    }
    if (!empty($archive->file_path)) {
        $archiveFiles[] = [
            'name' => 'Lampiran Digital',
            'url' => app_storage_url($archive->file_path),
            'stream_url' => app_preview_stream_url($archive->file_path),
            'raw_path' => $archive->file_path,
            'ext' => strtolower(pathinfo($archive->file_path, PATHINFO_EXTENSION)),
        ];
    }
@endphp

<div class="space-y-3" x-data="{}">
    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER (TPanel / TToolBar) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('archives.index', request()->has('embed') ? ['embed' => 1] : []) }}" onclick="if(window.history.length > 1) { event.preventDefault(); window.history.back(); }" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Kembali">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded shrink-0">
                <i data-lucide="folder-open" class="w-4 h-4"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5 truncate">
                    <span>DETAIL BERKAS:</span>
                    <span class="text-amber-600 dark:text-amber-400 font-extrabold">{{ $archive->box_number ?? 'DRAFT (NO BOX PENDING)' }}</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-md">{{ $archive->title }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto justify-end">
            <!-- Refresh (F5) -->
            <button onclick="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Refresh Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-500"></i>
                <span>Refresh (F5)</span>
            </button>

            <!-- Edit Button (PIC Dept draft or Admin) -->
            @if(!auth()->user()->isPicGudang() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || ($archive->status === 'draft' && auth()->user()->isPicDept() && (int)$archive->department_id === (int)auth()->user()->department_id)))
            <a href="{{ route('archives.edit', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3 py-1 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5 shrink-0">
                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                <span>{{ $archive->status === 'draft' ? 'Edit & Ajukan (F2)' : 'Edit Data' }}</span>
            </a>
            @endif

            <!-- Cetak Label Box -->
            @if(!auth()->user()->isPicDept())
                @if($archive->status === 'in_warehouse')
                <a href="{{ route('archives.print_sticker', $archive) }}" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Cetak Stiker Label Box">
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span>Cetak Label</span>
                </a>
                @endif
            @endif


            <!-- Proses Pemusnahan (BAP) jika mendekati jatuh tempo -->
            @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                @if($archive->status === 'in_warehouse' && $archive->retention_expiry_date && \Carbon\Carbon::parse($archive->retention_expiry_date)->diffInDays(now()) <= 90)
                <a href="{{ route('destructions.propose', $archive) }}" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-500 text-white rounded text-xs font-mono font-bold border border-rose-700 shadow-sm transition flex items-center gap-1 shrink-0">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Proses Pemusnahan</span>
                </a>
                @endif
            @endif
        </div>
    </div>

    <!-- DELPHI STATUS & WORKFLOW BANNER -->
    @if($archive->status === 'draft' && (auth()->user()->isPicDept() || auth()->user()->isSuperAdmin()))
    <div class="p-3 rounded bg-amber-500/10 border border-amber-500/40 text-amber-900 dark:text-amber-200 text-xs font-mono flex items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-2">
            <i data-lucide="file-edit" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0"></i>
            <div>
                <strong class="font-extrabold uppercase">Dokumen Masih Berupa Draft Sementara:</strong>
                Draft ini belum diajukan ke antrean kurator gudang. Silakan lengkapi data dan lampiran dokumen, lalu simpan permanen.
            </div>
        </div>
        <a href="{{ route('archives.edit', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded border border-amber-600 shadow transition flex items-center gap-1 shrink-0">
            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
            <span>Edit & Simpan Permanen</span>
        </a>
    </div>
    @endif

    @if($archive->rejection_note)
    <div class="p-3 rounded bg-rose-500/10 border border-rose-500/40 text-rose-900 dark:text-rose-200 text-xs font-mono flex items-start gap-2.5 shadow-sm">
        <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5"></i>
        <div>
            <strong class="font-extrabold uppercase block">Catatan Penolakan PIC Gudang:</strong>
            <p class="mt-0.5 font-sans">{{ $archive->rejection_note }}</p>
        </div>
    </div>
    @endif

    <!-- VERIFICATION ACTION PANEL (PIC Gudang Only when pending_verification) -->
    @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $archive->status === 'pending_verification')
    <fieldset class="border border-amber-500/40 p-3 rounded bg-amber-500/5 font-mono text-xs shadow-sm">
        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-amber-500/40 rounded shadow-sm flex items-center gap-1.5">
            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-500"></i>
            Hub Verifikasi & Penomoran Box Gudang
        </legend>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
            <p class="text-[11px] text-slate-600 dark:text-slate-400 font-sans">
                Periksa kesesuaian fisik dan lampiran berkas. Setujui untuk menghasilkan Nomor Box resmi secara otomatis.
            </p>
            <div class="flex items-center gap-2 shrink-0">
                <form action="{{ route('archives.verify', $archive) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" 
                            data-confirm="Setujui pengajuan arsip dan generate nomor box otomatis?" 
                            data-confirm-title="Persetujuan Arsip"
                            data-confirm-type="success"
                            data-confirm-btn="Ya, Setujui"
                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded border border-emerald-700 shadow transition flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                        <span>Setujui & Generate Box</span>
                    </button>
                </form>

                <button onclick="document.getElementById('rejectModal').classList.remove('hidden')" type="button" class="px-3 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 font-bold rounded transition flex items-center gap-1 cursor-pointer">
                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                    <span>Tolak & Minta Revisi</span>
                </button>
            </div>
        </div>
    </fieldset>

    <!-- Modal Rejection -->
    <div id="rejectModal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 text-left">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded shadow-2xl p-5 max-w-md w-full space-y-3 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                <h3 class="font-bold text-slate-900 dark:text-white uppercase flex items-center gap-1.5">
                    <i data-lucide="x-circle" class="w-4 h-4 text-rose-500"></i>
                    <span>Tolak Pengajuan Arsip</span>
                </h3>
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <form action="{{ route('archives.verify', $archive) }}" method="POST" class="space-y-3 font-sans">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alasan / Catatan Penolakan</label>
                    <textarea name="rejection_note" rows="3" required placeholder="Tuliskan alasan penolakan secara jelas..." class="w-full p-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-rose-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800 font-mono">
                    <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold">Batal</button>
                    <button type="submit" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white border border-rose-700 rounded text-xs font-bold transition">Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- CHECK-IN PHYSICAL PLACEMENT PANEL (PIC Gudang when approved_booked) -->
    @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $archive->status === 'approved_booked')
    <fieldset class="border border-blue-500/40 p-3 rounded bg-blue-500/5 font-mono text-xs shadow-sm">
        <legend class="px-2 font-mono text-xs font-bold text-blue-700 dark:text-blue-400 bg-slate-100 dark:bg-slate-800 border border-blue-500/40 rounded shadow-sm flex items-center gap-1.5">
            <i data-lucide="warehouse" class="w-3.5 h-3.5 text-blue-500"></i>
            Check-in Fisik & Penempatan Rak Gudang
        </legend>
        <form action="{{ route('archives.checkin', $archive) }}" method="POST" class="space-y-3 pt-1 font-sans">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Pilih Lokasi Rak Gudang Available <span class="text-rose-500">*</span></label>
                    <select name="warehouse_location_id" required class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 transition">
                        <option value="">-- Pilih Slot Rak Gudang Available --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">
                            {{ $loc->full_location }} (Terisi {{ $loc->current_box_count }}/{{ $loc->box_capacity }} Box)
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Catatan Penerimaan (Opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Fisik diterima segel utuh" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 transition">
                </div>
            </div>

            <div class="flex justify-end font-mono">
                <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-bold border border-blue-700 shadow transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Konfirmasi Check-in Ke Gudang</span>
                </button>
            </div>
        </form>
    </fieldset>
    @endif

    <!-- MAIN TWO-COLUMN CONTENT GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <!-- LEFT 2 COLS: METADATA & DOCUMENT DETAILS -->
        <div class="lg:col-span-2 space-y-3">
            <!-- GROUPBOX: INFORMASI UTAMA & METADATA -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm">
                <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-amber-500"></i>
                    Informasi Utama Berkas
                </legend>

                <div class="pt-1 space-y-3">
                    <!-- Title Bar -->
                    <div class="border-b border-slate-200 dark:border-slate-800 pb-2.5 flex items-start justify-between gap-3">
                        <div>
                            <span class="text-[10px] text-slate-500 uppercase tracking-wider block font-bold">Judul / Uraian Berkas</span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white font-sans mt-0.5 leading-snug">{{ $archive->title }}</h2>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono uppercase shrink-0 border {{ 
                            $archive->status === 'draft' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30' :
                            ($archive->status === 'pending_verification' ? 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/30' :
                            ($archive->status === 'approved_booked' ? 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-500/30' :
                            ($archive->status === 'in_warehouse' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' :
                            ($archive->status === 'borrowed' || $archive->status === 'out' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30' :
                            'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/30'))))
                        }}">
                            {{ $archive->status_label }}
                        </span>
                    </div>

                    <!-- Delphi Desktop Metadata Grid Table -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Departemen</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs truncate block" title="{{ $archive->department->name ?? '-' }}">{{ $archive->department->name ?? '-' }} ({{ $archive->department->code ?? '-' }})</span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Sub Bagian / Unit</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs truncate block" title="{{ $archive->subDepartment->name ?? '-' }}">{{ $archive->subDepartment->name ?? '-' }}</span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Kode Klasifikasi</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400 text-xs block">{{ $archive->classification_code ?? '-' }}</span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Periode Dokumen</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400 text-xs block">{{ $archive->effective_periode }}</span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Tingkat Perkembangan</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs block">{{ $archive->development_stage }}</span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Media Simpan</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs block">{{ $archive->media_type }}</span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Tgl. Penyerahan</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs block">{{ $archive->tgl_penyerahan ? \Carbon\Carbon::parse($archive->tgl_penyerahan)->format('d/m/Y') : '-' }}</span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Diserahkan Oleh</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs truncate block" title="{{ $archive->creator->name ?? 'User' }}">{{ $archive->creator->name ?? 'User' }}</span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Status Keaslian</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs block">{{ $archive->vital_status ? 'Vital Perusahaan' : 'Reguler' }}</span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Tingkat Akses</span>
                            <span class="font-bold text-xs block {{ $archive->access_level === 'strictly_confidential' ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                                {{ $archive->access_level === 'strictly_confidential' ? 'Strictly Confidential' : ($archive->access_level === 'confidential' ? 'Confidential' : 'Internal') }}
                            </span>
                        </div>

                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded sm:col-span-2">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Kondisi Wadah Fisik</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs block">{{ $archive->physical_condition }}</span>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- GROUPBOX: RINCIAN BUTIR DOKUMEN ARSIP (TDBGrid Spreadsheet) -->
            @if(!auth()->user()->isPicGudang() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || (auth()->user()->isPicDept() && (int)$archive->department_id === (int)auth()->user()->department_id)))
            <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm">
                <legend class="px-2 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                    <i data-lucide="list-checks" class="w-3.5 h-3.5 text-emerald-500"></i>
                    Rincian Butir Dokumen Dalam Box ({{ $archive->items->count() }} Berkas)
                </legend>

                <div class="pt-1">
                    @if($archive->items->isNotEmpty())
                    <div class="border border-slate-300 dark:border-slate-800 rounded overflow-hidden">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                                    <th class="py-2 px-2.5 w-10 text-center border-r border-slate-300 dark:border-slate-700">NO</th>
                                    <th class="py-2 px-3 border-r border-slate-300 dark:border-slate-700">NAMA DOKUMEN / BERKAS ARSIP</th>
                                    <th class="py-2 px-3 border-r border-slate-300 dark:border-slate-700 w-40">PERIODE</th>
                                    <th class="py-2 px-3">KETERANGAN</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-mono text-xs">
                                @foreach($archive->items as $it)
                                <tr class="hover:bg-amber-500/5 dark:hover:bg-slate-900/50 transition">
                                    <td class="py-2 px-2.5 text-center font-bold text-purple-600 dark:text-purple-400 border-r border-slate-200 dark:border-slate-800">{{ $it->item_number }}</td>
                                    <td class="py-2 px-3 font-bold font-sans text-slate-900 dark:text-white border-r border-slate-200 dark:border-slate-800">{{ $it->document_name }}</td>
                                    <td class="py-2 px-3 text-amber-700 dark:text-amber-400 font-bold border-r border-slate-200 dark:border-slate-800">{{ $it->period_text ?? '-' }}</td>
                                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400 font-sans text-[11px]">{{ $it->notes ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 whitespace-pre-line font-sans">
                        {{ $archive->content_description ?: 'Tidak ada rincian butir berkas.' }}
                    </div>
                    @endif
                </div>
            </fieldset>
            @endif

            <!-- GROUPBOX: DOKUMENTASI SCAN & LAMPIRAN DIGITAL -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm">
                <legend class="px-2 font-mono text-xs font-bold text-blue-700 dark:text-blue-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                    <i data-lucide="paperclip" class="w-3.5 h-3.5 text-blue-500"></i>
                    Dokumentasi Scan & Lampiran Digital
                </legend>

                <div class="pt-1">
                    @if(count($archiveFiles) > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- 1. Scan Formulir Input -->
                        @if($archive->scan_input_form)
                        @php
                            $scanInputPayload = [
                                'box_number' => $archive->box_number,
                                'title' => $archive->title,
                                'department' => $archive->department->code ?? '',
                                'name' => 'Scan Formulir Input',
                                'url' => app_storage_url($archive->scan_input_form),
                                'stream_url' => app_preview_stream_url($archive->scan_input_form),
                                'raw_path' => $archive->scan_input_form,
                                'ext' => strtolower(pathinfo($archive->scan_input_form, PATHINFO_EXTENSION)),
                                'files' => $archiveFiles,
                            ];
                        @endphp
                        <div class="p-2.5 rounded bg-amber-500/10 border border-amber-500/30 flex items-center justify-between gap-2 shadow-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="p-1 bg-amber-500/20 text-amber-700 dark:text-amber-300 rounded shrink-0">
                                    <i data-lucide="file-check" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block truncate">Scan Formulir Input</span>
                                    <span class="text-[10px] text-slate-500 block truncate">Form pendaftaran fisik ({{ strtoupper(pathinfo($archive->scan_input_form, PATHINFO_EXTENSION)) }})</span>
                                </div>
                            </div>
                            <button type="button" 
                                    onclick='window.dmsPreviewFile(@json($scanInputPayload))'
                                    @click='dmsPreviewFile(@json($scanInputPayload))'
                                    class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 text-[11px] font-bold rounded border border-amber-600 transition cursor-pointer flex items-center gap-1 shrink-0 shadow-sm">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                <span>Lihat Scan</span>
                            </button>
                        </div>
                        @endif

                        <!-- 2. Scan Approval Input -->
                        @if($archive->scan_approval_input)
                        @php
                            $scanApprovalPayload = [
                                'box_number' => $archive->box_number,
                                'title' => $archive->title,
                                'department' => $archive->department->code ?? '',
                                'name' => 'Scan Approval Input',
                                'url' => app_storage_url($archive->scan_approval_input),
                                'stream_url' => app_preview_stream_url($archive->scan_approval_input),
                                'raw_path' => $archive->scan_approval_input,
                                'ext' => strtolower(pathinfo($archive->scan_approval_input, PATHINFO_EXTENSION)),
                                'files' => $archiveFiles,
                            ];
                        @endphp
                        <div class="p-2.5 rounded bg-blue-500/10 border border-blue-500/30 flex items-center justify-between gap-2 shadow-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="p-1 bg-blue-500/20 text-blue-700 dark:text-blue-300 rounded shrink-0">
                                    <i data-lucide="check-square" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block truncate">Scan Approval Input</span>
                                    <span class="text-[10px] text-slate-500 block truncate">Bukti persetujuan PIC ({{ strtoupper(pathinfo($archive->scan_approval_input, PATHINFO_EXTENSION)) }})</span>
                                </div>
                            </div>
                            <button type="button" 
                                    onclick='window.dmsPreviewFile(@json($scanApprovalPayload))'
                                    @click='dmsPreviewFile(@json($scanApprovalPayload))'
                                    class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-bold rounded border border-blue-700 transition cursor-pointer flex items-center gap-1 shrink-0 shadow-sm">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                <span>Lihat Scan</span>
                            </button>
                        </div>
                        @endif

                        <!-- 3. Scan Form Perpanjangan -->
                        @if($archive->scan_extension_form)
                        @php
                            $scanExtensionPayload = [
                                'box_number' => $archive->box_number,
                                'title' => $archive->title,
                                'department' => $archive->department->code ?? '',
                                'name' => 'Scan Form Perpanjangan',
                                'url' => app_storage_url($archive->scan_extension_form),
                                'stream_url' => app_preview_stream_url($archive->scan_extension_form),
                                'raw_path' => $archive->scan_extension_form,
                                'ext' => strtolower(pathinfo($archive->scan_extension_form, PATHINFO_EXTENSION)),
                                'files' => $archiveFiles,
                            ];
                        @endphp
                        <div class="p-2.5 rounded bg-purple-500/10 border border-purple-500/30 flex items-center justify-between gap-2 shadow-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="p-1 bg-purple-500/20 text-purple-700 dark:text-purple-300 rounded shrink-0">
                                    <i data-lucide="clock" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block truncate">Scan Form Perpanjangan</span>
                                    <span class="text-[10px] text-slate-500 block truncate">Perpanjangan masa simpan ({{ strtoupper(pathinfo($archive->scan_extension_form, PATHINFO_EXTENSION)) }})</span>
                                </div>
                            </div>
                            <button type="button" 
                                    onclick='window.dmsPreviewFile(@json($scanExtensionPayload))'
                                    @click='dmsPreviewFile(@json($scanExtensionPayload))'
                                    class="px-2.5 py-1 bg-purple-600 hover:bg-purple-500 text-white text-[11px] font-bold rounded border border-purple-700 transition cursor-pointer flex items-center gap-1 shrink-0 shadow-sm">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                <span>Lihat Form</span>
                            </button>
                        </div>
                        @endif

                        <!-- 4. Lampiran Digital Utama -->
                        @if($archive->file_path)
                        @php
                            $digitalPayload = [
                                'box_number' => $archive->box_number,
                                'title' => $archive->title,
                                'department' => $archive->department->code ?? '',
                                'name' => 'Lampiran Digital',
                                'url' => app_storage_url($archive->file_path),
                                'stream_url' => app_preview_stream_url($archive->file_path),
                                'raw_path' => $archive->file_path,
                                'ext' => strtolower(pathinfo($archive->file_path, PATHINFO_EXTENSION)),
                                'files' => $archiveFiles,
                            ];
                        @endphp
                        <div class="p-2.5 rounded bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-between gap-2 shadow-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="p-1 bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 rounded shrink-0">
                                    <i data-lucide="paperclip" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block truncate">Lampiran Digital</span>
                                    <span class="text-[10px] text-slate-500 block truncate">Softcopy arsip ({{ strtoupper(pathinfo($archive->file_path, PATHINFO_EXTENSION)) }})</span>
                                </div>
                            </div>
                            <button type="button" 
                                    onclick='window.dmsPreviewFile(@json($digitalPayload))'
                                    @click='dmsPreviewFile(@json($digitalPayload))'
                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold rounded border border-emerald-700 transition cursor-pointer flex items-center gap-1 shrink-0 shadow-sm">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                <span>Pratinjau Berkas</span>
                            </button>
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="p-4 text-center text-slate-400 font-mono text-xs">
                        <i data-lucide="file-x" class="w-6 h-6 mx-auto mb-1 opacity-50"></i>
                        Belum ada scan formulir atau lampiran berkas digital yang diunggah.
                    </div>
                    @endif
                </div>
            </fieldset>
        </div>

        <!-- RIGHT 1 COL: LOCATION, RETENTION & WORKFLOW SIDEBAR -->
        <div class="space-y-3">
            <!-- GROUPBOX: LOKASI FISIK GUDANG -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm">
                <legend class="px-2 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500"></i>
                    Lokasi Fisik Gudang
                </legend>

                <div class="pt-1">
                    @if($archive->location)
                    <div class="p-2.5 bg-emerald-500/10 border border-emerald-500/30 rounded space-y-1">
                        <span class="text-[10px] text-slate-500 uppercase block font-bold">Gudang & Slot Rak:</span>
                        <span class="font-extrabold text-emerald-700 dark:text-emerald-300 text-sm block">{{ $archive->full_slot_location }}</span>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 font-sans pt-1 border-t border-emerald-500/20">
                            {{ $archive->location->warehouse->name ?? '' }} ({{ $archive->location->warehouse->address ?? '' }})
                        </p>
                    </div>
                    @else
                    <div class="p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded text-center text-[11px] text-slate-500">
                        Belum dilakukan penempatan slot rak gudang.
                    </div>
                    @endif
                </div>
            </fieldset>

            <!-- GROUPBOX: MASA SIMPAN & EXPIRY RETENSI -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm">
                <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-500"></i>
                    Masa Simpan & Expiry Retensi
                </legend>

                <div class="pt-1 space-y-2">
                    <div class="flex justify-between items-center py-1.5 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500">Durasi Retensi:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->retention_duration_label }}</span>
                    </div>

                    <div class="flex justify-between items-center py-1.5 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500">Jatuh Tempo (Expiry):</span>
                        <span class="font-extrabold text-amber-600 dark:text-amber-400">
                            {{ $archive->retention_expiry_date ? \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') : '-' }}
                        </span>
                    </div>

                    @if($archive->retention_expiry_date)
                    @php
                        $expiryEndOfMonth = \Carbon\Carbon::parse($archive->retention_expiry_date)->endOfMonth()->endOfDay();
                        $isExpired = now()->gt($expiryEndOfMonth);
                        $daysToExpiry = \Carbon\Carbon::today()->diffInDays($expiryEndOfMonth, false);
                    @endphp
                    <div class="pt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $isExpired ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/30 animate-warning-blink' : ($daysToExpiry <= 30 ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30' : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30') }}">
                            {{ $isExpired ? 'Sudah Kadaluarsa (Lewat ' . abs($daysToExpiry) . ' Hari)' : ($daysToExpiry <= 30 ? 'Jatuh Tempo Bulan Ini (Sisa ' . $daysToExpiry . ' Hari)' : 'Masa Simpan Aktif (' . $daysToExpiry . ' Hari)') }}
                        </span>
                    </div>
                    @endif
                </div>
            </fieldset>

            <!-- GROUPBOX: AUDIT TRAIL & LOG AKTIVITAS -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm">
                <legend class="px-2 font-mono text-xs font-bold text-cyan-700 dark:text-cyan-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                    <i data-lucide="history" class="w-3.5 h-3.5 text-cyan-500"></i>
                    Audit Trail & Riwayat Berkas
                </legend>

                <div class="pt-1 space-y-2">
                    @if($archive->entryLogs->isNotEmpty())
                        @foreach($archive->entryLogs as $log)
                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded text-xs space-y-0.5">
                            <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                <i data-lucide="log-in" class="w-3 h-3 text-emerald-500"></i>
                                <span>Check-in Gudang</span>
                            </span>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 font-sans">{{ $log->notes }}</p>
                            <span class="text-[10px] text-slate-400 block">{{ $log->picGudang->name ?? 'PIC Gudang' }} • {{ $log->entry_date->format('d/m/Y H:i') }}</span>
                        </div>
                        @endforeach
                    @endif

                    @if($archive->borrowingLogs->isNotEmpty())
                        @foreach($archive->borrowingLogs as $bLog)
                        <div class="p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded text-xs space-y-0.5">
                            <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                <i data-lucide="file-symlink" class="w-3 h-3 text-purple-500"></i>
                                <span>Penarikan ({{ strtoupper($bLog->status) }})</span>
                            </span>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 font-sans">Tujuan: {{ $bLog->purpose }}</p>
                            <span class="text-[10px] text-slate-400 block">{{ $bLog->borrower->name ?? 'User' }} • {{ $bLog->created_at->format('d/m/Y') }}</span>
                        </div>
                        @endforeach
                    @endif

                    @if($archive->destructionLog)
                        <div class="p-2 bg-rose-500/10 border border-rose-500/30 rounded text-xs space-y-0.5">
                            <span class="font-bold text-rose-700 dark:text-rose-300 flex items-center gap-1">
                                <i data-lucide="file-x" class="w-3 h-3 text-rose-500"></i>
                                <span>Pemusnahan (BAP: {{ $archive->destructionLog->bap_number }})</span>
                            </span>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 font-sans">Metode: {{ $archive->destructionLog->method }}</p>
                            <a href="{{ route('destructions.bap', $archive->destructionLog) }}" class="text-[10px] text-amber-600 dark:text-amber-400 font-bold hover:underline block pt-0.5">
                                Lihat Berita Acara Pemusnahan (BAP) &rarr;
                            </a>
                        </div>
                    @endif

                    @if($archive->entryLogs->isEmpty() && $archive->borrowingLogs->isEmpty() && !$archive->destructionLog)
                        <p class="text-slate-400 text-center py-2 text-[11px]">Belum ada riwayat aktivitas.</p>
                    @endif
                </div>
            </fieldset>
        </div>
    </div>
</div>
@endsection
