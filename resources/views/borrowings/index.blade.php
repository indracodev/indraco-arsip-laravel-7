@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Peminjaman Dokumen Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-[10px]" x-data="{ submitting: false }">
    <!-- DELPHI TOP TITLE PANEL & RECORD COUNTER -->
    <div class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-[8px]">
            <span class="p-[4px] bg-emerald-600/10 text-emerald-600 dark:text-emerald-400 rounded-[3px] border border-emerald-600/20">
                <i data-lucide="file-check-2" class="w-[16px] h-[16px]"></i>
            </span>
            <div>
                <h1 class="text-[13px] font-mono font-black uppercase text-slate-900 dark:text-white tracking-wide flex items-center gap-[6px]">
                    <span>Peminjaman Dokumen Arsip</span>
                    <span class="text-[10px] font-bold text-slate-500 font-sans tracking-normal">(Form F8 / Warehouse Dispatch)</span>
                </h1>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-mono">
                    Tracking pengajuan berkas, persetujuan kurator, pengeluaran fisik, dan estimasi kembali.
                </p>
            </div>
        </div>

        @if(!auth()->user()->isPicDept())
        <div class="flex items-center gap-[6px]">
            <a href="{{ route('borrowings.create') }}" class="px-[10px] py-[4px] bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 hover:to-emerald-300 text-slate-950 font-mono font-black text-[11px] rounded-[3px] border border-emerald-600 shadow-2xs transition flex items-center gap-[6px]">
                <i data-lucide="plus-circle" class="w-[13px] h-[13px]"></i>
                <span>Ajukan Pinjam</span>
            </a>
        </div>
        @endif
    </div>

    <!-- DELPHI FILTER CRITERIA (TGroupBox) -->
    <fieldset class="border border-slate-300 dark:border-slate-800 rounded-[4px] bg-slate-100/70 dark:bg-slate-900/60 p-[10px] text-[11px] font-mono shadow-2xs">
        <legend class="px-[6px] text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[2px]">
            Filter Parameter Peminjaman
        </legend>

        <form action="{{ route('borrowings.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-[8px]" @submit="submitting = true">
            <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
            <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

            <!-- Search Keyword -->
            <div class="md:col-span-6 flex flex-col gap-[3px]">
                <label class="text-[10px] font-bold uppercase text-slate-600 dark:text-slate-400">Kata Kunci Pencarian</label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="No. Box, Judul Berkas, Nama Peminjam, Maksud..." 
                        class="w-full pl-[26px] pr-[8px] h-[28px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                    >
                    <i data-lucide="search" class="w-[13px] h-[13px] text-slate-400 absolute left-[7px] top-[7px]"></i>
                </div>
            </div>

            <!-- Filter Status -->
            <div class="md:col-span-4 flex flex-col gap-[3px]">
                <label class="text-[10px] font-bold uppercase text-slate-600 dark:text-slate-400">Status Peminjaman</label>
                <select name="status" class="w-full px-[8px] h-[28px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 transition">
                    <option value="">-- Semua Status Peminjaman --</option>
                    <option value="requested" {{ request('status') == 'requested' ? 'selected' : '' }}>Diajukan (Requested)</option>
                    <option value="dept_approved" {{ request('status') == 'dept_approved' ? 'selected' : '' }}>Disetujui Dept (Approved Dept)</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui Gudang (Approved Warehouse)</option>
                    <option value="dispatched" {{ request('status') == 'dispatched' ? 'selected' : '' }}>Sedang Dipinjam (Dispatched)</option>
                    <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Selesai / Dikembalikan (Returned)</option>
                </select>
            </div>

            <!-- Submit Filter Button -->
            <div class="md:col-span-2 flex items-end">
                <button type="submit" :disabled="submitting" class="w-full h-[28px] px-[10px] bg-slate-800 hover:bg-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded-[3px] text-[11px] font-mono font-bold transition flex items-center justify-center gap-[6px] border border-slate-700 disabled:opacity-50 shadow-2xs">
                    <i data-lucide="loader-2" class="w-[13px] h-[13px] animate-spin" x-show="submitting"></i>
                    <i data-lucide="filter" class="w-[13px] h-[13px]" x-show="!submitting"></i>
                    <span x-text="submitting ? 'Cari...' : 'Cari Data'"></span>
                </button>
            </div>
        </form>
    </fieldset>

    <!-- DELPHI TDBGRID TABLE CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] shadow-xs relative overflow-hidden font-sans">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                        @php
                            $curSort = request('sort', 'created_at');
                            $curDir = request('direction', 'desc');
                            $nextDir = $curDir === 'asc' ? 'desc' : 'asc';
                        @endphp
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">NO. BOX & JUDUL BERKAS</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">PEMINJAM</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'borrow_date', 'direction' => $curSort === 'borrow_date' ? $nextDir : 'asc']) }}" class="flex items-center gap-[4px] hover:text-emerald-500 transition">
                                <span>TGL PINJAM / EST. KEMBALI</span>
                                @if($curSort === 'borrow_date')
                                    <span class="text-emerald-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">MAKSUD / TUJUAN</th>
                        <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $curSort === 'status' ? $nextDir : 'asc']) }}" class="flex items-center gap-[4px] hover:text-emerald-500 transition">
                                <span>STATUS</span>
                                @if($curSort === 'status')
                                    <span class="text-emerald-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-[6px] px-[10px] text-right">AKSI GUDANG</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-[11px]">
                    @forelse($borrowings as $bLog)
                    <tr class="hover:bg-emerald-500/5 dark:hover:bg-emerald-500/10 transition">
                        <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800">
                            <div class="space-y-[2px]">
                                <span class="font-mono text-[11px] text-amber-600 dark:text-amber-400 font-extrabold block">{{ $bLog->archive->box_number ?? 'DRAFT' }}</span>
                                <a href="{{ route('archives.show', $bLog->archive) }}" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition font-mono">
                                    {{ $bLog->archive->title }}
                                </a>
                            </div>
                        </td>

                        <td class="py-[6px] px-[10px] font-mono border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <span class="font-bold text-slate-900 dark:text-slate-200 block">{{ $bLog->borrower->name }}</span>
                            <span class="text-slate-500 dark:text-slate-400 text-[10px] font-bold">{{ $bLog->archive->department->code ?? 'Dept' }}</span>
                        </td>

                        <td class="py-[6px] px-[10px] font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <div class="text-slate-700 dark:text-slate-300">Pinjam: {{ $bLog->borrow_date ? $bLog->borrow_date->format('d M Y') : 'Menunggu Dispatch' }}</div>
                            <div class="text-amber-600 dark:text-amber-400 font-extrabold">Est. Kembali: {{ \Carbon\Carbon::parse($bLog->expected_return_date)->format('d M Y') }}</div>
                        </td>

                        <td class="py-[6px] px-[10px] font-mono text-[11px] text-slate-600 dark:text-slate-300 max-w-xs truncate border-r border-slate-200 dark:border-slate-800">
                            {{ $bLog->purpose }}
                        </td>

                        <td class="py-[6px] px-[10px] font-mono border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($bLog->status === 'requested')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-amber-500/10 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/30">Diajukan User</span>
                            @elseif($bLog->status === 'dept_approved')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-cyan-500/10 text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300 border border-cyan-500/30">Disetujui Dept</span>
                            @elseif($bLog->status === 'approved')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-blue-500/10 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/30">Disetujui Gudang</span>
                            @elseif($bLog->status === 'dispatched')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-purple-500/10 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/30">Sedang Dipinjam</span>
                            @elseif($bLog->status === 'returned')
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-emerald-500/10 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/30">Dikembalikan</span>
                            @endif

                            @if($bLog->scan_approval_borrow)
                                <a href="{{ asset('storage/' . $bLog->scan_approval_borrow) }}" target="_blank" class="block text-[10px] text-amber-600 dark:text-amber-400 font-bold hover:underline mt-[2px]">
                                    <i data-lucide="file-check" class="w-[11px] h-[11px] inline"></i> Scan Approval
                                </a>
                            @endif
                        </td>

                        <td class="py-[6px] px-[10px] text-right font-mono whitespace-nowrap">
                            <!-- Step 1 Approval: PIC Departemen / Admin -->
                            @if($bLog->status === 'requested' && (auth()->user()->isSuperAdmin() || (auth()->user()->isPicDept() && auth()->user()->department_id === $bLog->archive->department_id)))
                                <button onclick="document.getElementById('deptApproveModal-{{ $bLog->id }}').classList.remove('hidden')" type="button" class="px-[8px] py-[2px] bg-cyan-600 hover:bg-cyan-500 text-white text-[11px] font-bold rounded-[3px] transition shadow-2xs inline-flex items-center gap-[4px]">
                                    <span>Approve Dept</span>
                                </button>

                                <!-- Modal Dept Approve -->
                                <div id="deptApproveModal-{{ $bLog->id }}" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-[16px] text-left">
                                    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[16px] max-w-md w-full space-y-[12px] shadow-2xl font-mono text-[11px]">
                                        <h3 class="text-[12px] font-bold text-slate-900 dark:text-white uppercase">Persetujuan Peminjaman Departemen</h3>
                                        <form action="{{ route('borrowings.dept_approve', $bLog) }}" method="POST" enctype="multipart/form-data" class="space-y-[10px]">
                                            @csrf
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-[4px]">Upload Scan Bukti Approval (Opsional)</label>
                                                <input type="file" name="scan_approval_borrow" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-[8px] py-[4px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] text-slate-900 dark:text-white">
                                            </div>
                                            <div class="flex justify-end gap-[6px] pt-[8px] border-t border-slate-200 dark:border-slate-800">
                                                <button type="button" onclick="document.getElementById('deptApproveModal-{{ $bLog->id }}').classList.add('hidden')" class="px-[10px] py-[3px] bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-[3px] font-bold">Batal</button>
                                                <button type="submit" class="px-[10px] py-[3px] bg-cyan-600 text-white rounded-[3px] font-bold">Setujui Peminjaman</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif

                            <!-- Step 2 Approval & Output: PIC Gudang / Admin -->
                            @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                                @if($bLog->status === 'requested' && !auth()->user()->isPicDept())
                                    <span class="text-[10px] text-amber-600 font-bold block">Menunggu Approval Dept</span>
                                @elseif($bLog->status === 'dept_approved' || $bLog->status === 'approved')
                                    <button onclick="document.getElementById('dispatchModal-{{ $bLog->id }}').classList.remove('hidden')" type="button" class="px-[8px] py-[2px] bg-purple-600 hover:bg-purple-500 text-white text-[11px] font-bold rounded-[3px] transition shadow-2xs inline-flex items-center gap-[4px]">
                                        <span>Dispatch Berkas</span>
                                    </button>

                                    <!-- Modal Dispatch Gudang -->
                                    <div id="dispatchModal-{{ $bLog->id }}" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-[16px] text-left">
                                        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[16px] max-w-md w-full space-y-[12px] shadow-2xl font-mono text-[11px]">
                                            <h3 class="text-[12px] font-bold text-slate-900 dark:text-white uppercase">Konfirmasi Pengeluaran Berkas Fisik</h3>
                                            <form action="{{ route('borrowings.dispatch', $bLog) }}" method="POST" enctype="multipart/form-data" class="space-y-[10px]">
                                                @csrf
                                                <div>
                                                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-[4px]">Upload Scan Bukti / Tanda Terima (Opsional)</label>
                                                    <input type="file" name="scan_approval_borrow" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-[8px] py-[4px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] text-slate-900 dark:text-white">
                                                </div>
                                                <div class="flex justify-end gap-[6px] pt-[8px] border-t border-slate-200 dark:border-slate-800">
                                                    <button type="button" onclick="document.getElementById('dispatchModal-{{ $bLog->id }}').classList.add('hidden')" class="px-[10px] py-[3px] bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-[3px] font-bold">Batal</button>
                                                    <button type="submit" class="px-[10px] py-[3px] bg-purple-600 text-white rounded-[3px] font-bold">Sahkan & Keluarkan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @elseif($bLog->status === 'dispatched')
                                    <form action="{{ route('borrowings.return', $bLog) }}" method="POST" class="inline" @submit="submitting = true">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Konfirmasi pengembalian berkas fisik ke gudang?')" :disabled="submitting" class="px-[8px] py-[2px] bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold rounded-[3px] transition shadow-2xs inline-flex items-center gap-[4px] disabled:opacity-50">
                                            <i data-lucide="loader-2" class="w-[12px] h-[12px] animate-spin" x-show="submitting"></i>
                                            <span>Konfirmasi Kembali</span>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-[32px] text-center font-mono text-slate-500 text-[11px]">
                            Belum ada riwayat pengajuan peminjaman dokumen arsip.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- DELPHI DBNAVIGATOR / TSTATUSBAR PAGINATION FOOTER -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-[10px] py-[6px] flex items-center justify-between gap-[8px] font-mono text-[11px] text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-[6px] text-[11px]">
                <i data-lucide="database" class="w-[13px] h-[13px] text-emerald-500"></i>
                <span>Menampilkan <strong>{{ $borrowings->firstItem() ?? 0 }}</strong> - <strong>{{ $borrowings->lastItem() ?? 0 }}</strong> dari <strong>{{ $borrowings->total() }}</strong> transaksi pinjam</span>
            </div>

            <div>
                {{ $borrowings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
