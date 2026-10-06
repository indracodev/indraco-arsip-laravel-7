@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Detail Berkas Arsip - ' . $archive->title)

@section('content')
<div class="w-full space-y-4">
    <!-- Top Action Toolbar & Breadcrumb -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-3 shadow-xs">
        <div class="flex items-center gap-2">
            <a href="{{ route('archives.index') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded border border-slate-300 dark:border-slate-700 transition flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali ke Katalog</span>
            </a>
            <span class="text-slate-300 dark:text-slate-700">|</span>
            <div class="flex items-center gap-1.5 text-xs">
                <span class="text-slate-500 font-medium">ID Arsip:</span>
                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">#{{ $archive->id }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || (auth()->user()->isPicDept() && $archive->department_id === auth()->user()->department_id) || in_array($archive->status, ['draft', 'pending_verification', 'approved_booked']))
            <a href="{{ route('archives.edit', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded border border-amber-600 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                <span>{{ $archive->status === 'draft' ? 'Edit & Ajukan Draft' : 'Edit Data Berkas' }}</span>
            </a>
            @endif

            @if(!auth()->user()->isPicDept())
            <a href="{{ route('archives.print_sticker', $archive) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs rounded border border-slate-300 dark:border-slate-700 transition flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Cetak Label Box (TB 30g)</span>
            </a>
            @endif

            @if($archive->status === 'in_warehouse')
            <a href="{{ route('borrowings.create', ['archive_id' => $archive->id]) }}" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded border border-purple-700 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="file-symlink" class="w-3.5 h-3.5"></i>
                <span>Ajukan Pinjam Berkas</span>
            </a>
            @endif

            @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                @if($archive->status === 'in_warehouse' && $archive->retention_expiry_date && \Carbon\Carbon::parse($archive->retention_expiry_date)->diffInDays(now()) <= 90)
                <a href="{{ route('destructions.propose', $archive) }}" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded border border-rose-700 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Proses BAP Pemusnahan</span>
                </a>
                @endif
            @endif
        </div>
    </div>

    <!-- Main Header Card: Title, Box Code, Department & Workflow Status -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-4 sm:p-5 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400 font-mono font-bold text-[11px] rounded">
                        {{ $archive->department ? $archive->department->code : 'DEPT' }}
                    </span>
                    @if($archive->subDepartment)
                    <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-mono text-[11px] rounded">
                        Sub: {{ $archive->subDepartment->code }}
                    </span>
                    @endif
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span class="text-xs text-slate-500 font-medium">Tipe: <strong class="text-slate-700 dark:text-slate-300">{{ $archive->document_type ?? 'UMUM' }}</strong></span>
                </div>

                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <i data-lucide="archive" class="w-6 h-6 text-amber-500 shrink-0"></i>
                    <span>{{ $archive->title }}</span>
                </h1>

                <!-- Subtitle Pills -->
                <div class="flex flex-wrap items-center gap-2 text-xs pt-0.5">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded font-mono">
                        <i data-lucide="package" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span class="text-slate-500">No. Box:</span>
                        <strong class="text-amber-700 dark:text-amber-400">{{ $archive->box_number ?? 'Belum ter-generate (Draft / Pending)' }}</strong>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-indigo-500"></i>
                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $archive->department ? $archive->department->name : '-' }}</span>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span class="text-slate-500">Penyerahan:</span>
                        <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $archive->tgl_penyerahan ? $archive->tgl_penyerahan->format('d/m/Y') : '-' }}</span>
                    </div>

                    @if($archive->effective_periode && $archive->effective_periode !== '-')
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded font-mono">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                        <span class="text-amber-800 dark:text-amber-300 font-bold">Periode: {{ $archive->effective_periode }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Workflow Status Badge -->
            <div class="flex flex-col md:items-end justify-center shrink-0 border-t md:border-t-0 pt-3 md:pt-0 border-slate-200 dark:border-slate-800">
                <span class="text-[10px] font-mono uppercase font-bold text-slate-400 mb-1">Status Workflow Berkas:</span>
                @if($archive->status === 'draft')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded font-bold text-xs bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        Draft / Perlu Revisi
                    </span>
                @elseif($archive->status === 'pending_verification')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded font-bold text-xs bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Menunggu Verifikasi PIC Gudang
                    </span>
                @elseif($archive->status === 'approved_booked')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded font-bold text-xs bg-blue-50 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Approved / Booking Tempat Fix
                    </span>
                @elseif($archive->status === 'in_warehouse')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded font-bold text-xs bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Tersimpan di Gudang Arsip
                    </span>
                @elseif($archive->status === 'borrowed')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded font-bold text-xs bg-purple-50 text-purple-800 dark:bg-purple-950/50 dark:text-purple-300 border border-purple-300 dark:border-purple-700">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        Sedang Dipinjam
                    </span>
                @elseif($archive->status === 'taken')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded font-bold text-xs bg-indigo-50 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-700">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Telah Diambil (Permanen)
                    </span>
                @elseif($archive->status === 'destroyed')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded font-bold text-xs bg-rose-50 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-300 dark:border-rose-700">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Telah Dimusnahkan (BAP)
                    </span>
                @endif
            </div>
        </div>

        @if($archive->rejection_note)
        <div class="mt-3 p-3 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs space-y-1">
            <span class="font-bold flex items-center gap-1.5">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600"></i>
                Catatan Penolakan PIC Gudang:
            </span>
            <p class="pl-5.5 font-medium">{{ $archive->rejection_note }}</p>
        </div>
        @endif
    </div>

    <!-- Draft Notice Banner (PIC Dept & Super Admin) -->
    @if($archive->status === 'draft' && (auth()->user()->isPicDept() || auth()->user()->isSuperAdmin()))
    <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/80 rounded-lg p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-300 dark:border-amber-700">
                <i data-lucide="file-edit" class="w-4 h-4"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-amber-900 dark:text-amber-200">Dokumen Masih Berupa Draft Sementara</h4>
                <p class="text-[11px] text-amber-700 dark:text-amber-300/80">Draft ini belum diajukan ke PIC Gudang. Anda dapat melengkapi atau mengubah rincian berkas kapan saja.</p>
            </div>
        </div>
        <a href="{{ route('archives.edit', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded border border-amber-600 shadow-xs transition flex items-center justify-center gap-1.5 shrink-0 cursor-pointer">
            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
            <span>Edit & Simpan Permanen</span>
        </a>
    </div>
    @endif

    <!-- Verification Action Panel (PIC Gudang Only) -->
    @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $archive->status === 'pending_verification')
    <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-lg p-4 shadow-xs space-y-3">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-300 dark:border-amber-700">
                <i data-lucide="shield-check" class="w-4.5 h-4.5"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Hub Verifikasi & Penomoran Box (PIC Gudang)</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400">Periksa kesesuaian berkas fisik. Klik Setujui untuk melakukan generate Nomor Box otomatis sesuai format custom PT Indraco.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-amber-200/60 dark:border-amber-800/60">
            <form action="{{ route('archives.verify', $archive) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="action" value="approve">
                <button type="submit" onclick="return confirm('Setujui pengajuan arsip dan generate nomor box otomatis?')" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded border border-emerald-700 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    <span>Setujui & Generate Box Code</span>
                </button>
            </form>

            <button onclick="document.getElementById('rejectModal').classList.remove('hidden')" type="button" class="px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800 font-bold text-xs rounded transition flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                <span>Tolak & Minta Revisi</span>
            </button>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-lg p-5 max-w-md w-full space-y-3 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>
                    <span>Tolak Pengajuan Arsip</span>
                </h3>
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <form action="{{ route('archives.verify', $archive) }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alasan / Catatan Penolakan <span class="text-rose-500">*</span></label>
                    <textarea name="rejection_note" rows="3" required placeholder="Tuliskan butir atau dokumen yang perlu diperbaiki oleh PIC Dept..." class="w-full p-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500 font-medium"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-xs rounded border border-slate-300 dark:border-slate-700 font-bold cursor-pointer">Batal</button>
                    <button type="submit" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs rounded border border-rose-700 font-bold cursor-pointer">Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Check-in Warehouse Placement Panel (PIC Gudang Only) -->
    @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $archive->status === 'approved_booked')
    <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-lg p-4 shadow-xs space-y-3">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-300 dark:border-blue-700">
                <i data-lucide="warehouse" class="w-4.5 h-4.5"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Check-in Fisik & Penempatan Rak Gudang</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400">Pengajuan telah disetujui dengan Box Code <span class="font-mono text-amber-700 dark:text-amber-400 font-bold">{{ $archive->box_number }}</span>. Tentukan lokasi fisik penyimpanan rak gudang.</p>
            </div>
        </div>

        <form action="{{ route('archives.checkin', $archive) }}" method="POST" class="space-y-3 pt-2 border-t border-blue-200/60 dark:border-blue-800/60">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Pilih Lokasi Rak Gudang <span class="text-rose-500">*</span></label>
                    <select name="warehouse_location_id" required class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-medium">
                        <option value="">-- Pilih Slot Rak Gudang Available --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">
                            {{ $loc->full_location }} (Terisi {{ $loc->current_box_count }}/{{ $loc->box_capacity }} Box)
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Catatan Penerimaan (Opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Fisik diterima segel utuh" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-medium">
                </div>
            </div>

            <button type="submit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded border border-blue-700 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                <span>Konfirmasi Check-in Ke Gudang</span>
            </button>
        </form>
    </div>
    @endif

    <!-- Main Detail Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left: Information Cards (2 Cols) -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Metadata Card -->
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-4 sm:p-5 shadow-xs space-y-4">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-4 h-4 text-indigo-500"></i>
                        Informasi Dokumen & Wadah Box
                    </span>
                    <span class="text-[11px] font-mono text-slate-400">Metadata Header</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900/60 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block mb-0.5 text-[11px]">Perusahaan / Entitas:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->company_name ?? 'PT Indraco' }}</span>
                    </div>

                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900/60 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block mb-0.5 text-[11px]">Departemen / Sub-Unit:</span>
                        <span class="font-bold text-slate-900 dark:text-white">
                            {{ $archive->department ? $archive->department->name : '-' }} ({{ $archive->department ? $archive->department->code : '-' }})
                            @if($archive->subDepartment)
                                <span class="block text-[11px] text-indigo-600 dark:text-indigo-400 font-mono mt-0.5">↳ Sub: {{ $archive->subDepartment->name }} ({{ $archive->subDepartment->code }})</span>
                            @endif
                        </span>
                    </div>

                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900/60 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block mb-0.5 text-[11px]">Jenis Dokumen:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->document_type ?? 'UMUM' }}</span>
                    </div>

                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900/60 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block mb-0.5 text-[11px]">Pengaju / Creator:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->creator ? $archive->creator->name : '-' }}</span>
                    </div>

                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900/60 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block mb-0.5 text-[11px]">Tgl. Penyerahan & Periode:</span>
                        <span class="font-bold text-amber-700 dark:text-amber-400">
                            {{ $archive->tgl_penyerahan ? $archive->tgl_penyerahan->format('d/m/Y') : '-' }}
                            @if($archive->effective_periode && $archive->effective_periode !== '-')
                                <span class="font-mono text-[11px] bg-amber-500/20 px-1.5 py-0.5 rounded ml-1">({{ $archive->effective_periode }})</span>
                            @endif
                        </span>
                    </div>

                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900/60 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 block mb-0.5 text-[11px]">Kondisi Wadah Fisik:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->physical_condition ?? 'Baik / Standar TB 30g' }}</span>
                    </div>
                </div>

                <!-- Structured Items Table (1 Box -> Banyak Dokumen Arsip) -->
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <i data-lucide="list-checks" class="w-4 h-4 text-emerald-500"></i>
                            Rincian Butir Dokumen Arsip Dalam Box ({{ $archive->items->count() }} Berkas):
                        </span>
                        <span class="text-[11px] font-mono text-slate-500">Standar Box TB 30g</span>
                    </div>

                    @if($archive->items->isNotEmpty())
                    <div class="border border-slate-200 dark:border-slate-800 rounded overflow-hidden shadow-xs">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                                    <th class="py-2 px-3 w-12 text-center">NO</th>
                                    <th class="py-2 px-3">NAMA DOKUMEN / BERKAS ARSIP</th>
                                    <th class="py-2 px-3 w-48">PERIODE</th>
                                    <th class="py-2 px-3 w-48">KETERANGAN</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                @foreach($archive->items as $it)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50">
                                    <td class="py-2.5 px-3 text-center font-mono font-bold text-purple-600 dark:text-purple-400 align-top">{{ $it->item_number }}</td>
                                    <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-200 align-top">{{ $it->document_name }}</td>
                                    <td class="py-2.5 px-3 font-mono text-amber-700 dark:text-amber-300 font-semibold align-top">{{ $it->period_text ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 align-top break-words whitespace-pre-line leading-relaxed">{{ $it->notes ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-3 bg-slate-50 dark:bg-slate-900/90 rounded border border-slate-200 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-200 whitespace-pre-line leading-relaxed font-medium">
                        {{ $archive->content_description ?? 'Tidak ada rincian butir dokumen.' }}
                    </div>
                    @endif
                </div>

                <!-- Digital Attachments & Scans -->
                @if($archive->scan_input_form || $archive->scan_approval_input || $archive->scan_extension_form || $archive->file_path)
                <div class="space-y-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 block">Dokumentasi Scan & Lampiran Digital:</span>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @if($archive->scan_input_form)
                        <div class="p-2.5 rounded bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file-check" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Scan Formulir Input</span>
                                    <span class="text-[10px] text-slate-500">Form pendaftaran fisik</span>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $archive->scan_input_form) }}" target="_blank" class="px-2.5 py-1 bg-amber-500 text-slate-950 text-[11px] font-bold rounded hover:bg-amber-400 transition cursor-pointer">
                                Lihat Scan
                            </a>
                        </div>
                        @endif

                        @if($archive->scan_approval_input)
                        <div class="p-2.5 rounded bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="check-square" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Scan Approval Input</span>
                                    <span class="text-[10px] text-slate-500">Bukti persetujuan PIC</span>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $archive->scan_approval_input) }}" target="_blank" class="px-2.5 py-1 bg-blue-600 text-white text-[11px] font-bold rounded hover:bg-blue-500 transition cursor-pointer">
                                Lihat Scan
                            </a>
                        </div>
                        @endif

                        @if($archive->scan_extension_form)
                        <div class="p-2.5 rounded bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="w-4 h-4 text-purple-600 dark:text-purple-400"></i>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Scan Form Perpanjangan</span>
                                    <span class="text-[10px] text-slate-500">Perpanjangan masa simpan</span>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $archive->scan_extension_form) }}" target="_blank" class="px-2.5 py-1 bg-purple-600 text-white text-[11px] font-bold rounded hover:bg-purple-500 transition cursor-pointer">
                                Lihat Form
                            </a>
                        </div>
                        @endif

                        @if($archive->file_path)
                        <div class="p-2.5 rounded bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="paperclip" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Lampiran Digital</span>
                                    <span class="text-[10px] text-slate-500">File softcopy</span>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $archive->file_path) }}" target="_blank" class="px-2.5 py-1 bg-emerald-600 text-white text-[11px] font-bold rounded hover:bg-emerald-500 transition cursor-pointer">
                                Unduh File
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Right: Storage & Retention Sidebar (1 Col) -->
        <div class="space-y-4">
            <!-- Physical Location Card -->
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-xs space-y-3">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-4 h-4 text-emerald-500"></i>
                        Lokasi Fisik Gudang
                    </h3>
                </div>

                @if($archive->location)
                <div class="space-y-2">
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded text-xs">
                        <span class="text-slate-500 dark:text-slate-400 block font-medium text-[11px]">Gudang & Slot Rak:</span>
                        <span class="font-mono font-black text-emerald-700 dark:text-emerald-300 text-sm block mt-0.5">{{ $archive->full_slot_location }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium">
                        {{ $archive->location->warehouse->name ?? '' }} ({{ $archive->location->warehouse->address ?? '' }})
                    </p>
                </div>
                @else
                <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded border border-slate-200 dark:border-slate-800 text-center text-xs text-slate-500 font-medium">
                    Belum dilakukan penempatan slot rak gudang.
                </div>
                @endif
            </div>

            <!-- Retention & Expiry Info -->
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-xs space-y-3">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-4 h-4 text-amber-500"></i>
                        Masa Simpan & Expiry
                    </h3>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1.5 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500">Durasi Retention:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->retention_years }} Tahun</span>
                    </div>

                    <div class="flex justify-between py-1.5 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500">Tanggal Pemusnahan:</span>
                        <span class="font-bold text-amber-700 dark:text-amber-400">
                            {{ $archive->retention_expiry_date ? \Carbon\Carbon::parse($archive->retention_expiry_date)->format('d M Y') : '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom: Timeline Logs -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-4 sm:p-5 shadow-xs space-y-3">
        <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                <i data-lucide="history" class="w-4 h-4 text-cyan-500"></i>
                Riwayat Activity Log (Audit Trail)
            </h3>
        </div>

        <div class="space-y-2.5">
            @if($archive->entryLogs->isNotEmpty())
                @foreach($archive->entryLogs as $log)
                <div class="p-3 rounded bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 text-xs flex items-start gap-2.5">
                    <div class="p-1.5 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Log Masuk Gudang - Check-in</span>
                        <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-medium">{{ $log->notes }}</p>
                        <span class="text-[10px] text-slate-500 block mt-1">Diproses oleh: {{ $log->picGudang->name ?? 'PIC Gudang' }} | {{ $log->entry_date->format('d M Y H:i') }}</span>
                    </div>
                </div>
                @endforeach
            @endif

            @if($archive->borrowingLogs->isNotEmpty())
                @foreach($archive->borrowingLogs as $bLog)
                <div class="p-3 rounded bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 text-xs flex items-start gap-2.5">
                    <div class="p-1.5 bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded">
                        <i data-lucide="file-symlink" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Log Peminjaman Dokumen (Status: {{ strtoupper($bLog->status) }})</span>
                        <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-medium">Tujuan: {{ $bLog->purpose }}</p>
                        <span class="text-[10px] text-slate-500 block mt-1">Peminjam: {{ $bLog->borrower->name ?? 'User' }} | Est. Kembali: {{ $bLog->expected_return_date ? \Carbon\Carbon::parse($bLog->expected_return_date)->format('d M Y') : 'Hanya Diambil (Permanen)' }}</span>
                    </div>
                </div>
                @endforeach
            @endif

            @if($archive->destructionLog)
                <div class="p-3 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs flex items-start gap-2.5">
                    <div class="p-1.5 bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded">
                        <i data-lucide="file-x" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Log Pemusnahan Dokumen (No. BAP: {{ $archive->destructionLog->bap_number }})</span>
                        <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-medium">Metode: {{ $archive->destructionLog->method }} | {{ $archive->destructionLog->notes }}</p>
                        <a href="{{ route('destructions.bap', $archive->destructionLog) }}" class="text-[11px] text-amber-600 dark:text-amber-400 font-bold hover:underline block mt-1">
                            Lihat Cetak Berita Acara Pemusnahan (BAP) &rarr;
                        </a>
                    </div>
                </div>
            @endif

            @if($archive->entryLogs->isEmpty() && $archive->borrowingLogs->isEmpty() && !$archive->destructionLog)
                <div class="p-3 bg-slate-50 dark:bg-slate-900/40 rounded border border-slate-200 dark:border-slate-800 text-slate-500 text-xs text-center font-medium">
                    Belum ada riwayat aktivitas log untuk berkas arsip ini.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
