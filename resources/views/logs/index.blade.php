@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Log & Audit Trail - DMS PT Indraco')

@section('content')
<div class="space-y-[10px]">
    <!-- DELPHI TOP TITLE PANEL -->
    <div class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-[8px]">
            <span class="p-[4px] bg-cyan-600/10 text-cyan-600 dark:text-cyan-400 rounded-[3px] border border-cyan-600/20">
                <i data-lucide="history" class="w-[16px] h-[16px]"></i>
            </span>
            <div>
                <h1 class="text-[13px] font-mono font-black uppercase text-slate-900 dark:text-white tracking-wide flex items-center gap-[6px]">
                    <span>Global Log & Audit Trail System</span>
                </h1>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-mono">
                    Audit trail otomatis transaksi arsip gudang, peminjaman fisik, dan eksekusi BAP pemusnahan.
                </p>
            </div>
        </div>
    </div>

    <!-- DELPHI MDI TABS & FILTER FIELDSET -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[10px] shadow-xs space-y-[8px]">
        <!-- Tabs Strip -->
        <div class="flex border-b border-slate-300 dark:border-slate-800 gap-[4px] font-mono text-[11px]">
            <a href="{{ route('logs.index', ['tab' => 'entry']) }}" class="px-[12px] py-[5px] font-bold rounded-t-[3px] transition flex items-center gap-[6px] {{ $tab === 'entry' ? 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border-t-2 border-x border-cyan-500 -mb-[1px] bg-white dark:bg-slate-950' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="log-in" class="w-[13px] h-[13px]"></i>
                <span>Log Penerimaan Gudang</span>
            </a>

            <a href="{{ route('logs.index', ['tab' => 'borrowing']) }}" class="px-[12px] py-[5px] font-bold rounded-t-[3px] transition flex items-center gap-[6px] {{ $tab === 'borrowing' ? 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-t-2 border-x border-purple-500 -mb-[1px] bg-white dark:bg-slate-950' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="file-symlink" class="w-[13px] h-[13px]"></i>
                <span>Log Peminjaman Dokumen</span>
            </a>

            <a href="{{ route('logs.index', ['tab' => 'destruction']) }}" class="px-[12px] py-[5px] font-bold rounded-t-[3px] transition flex items-center gap-[6px] {{ $tab === 'destruction' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-t-2 border-x border-rose-500 -mb-[1px] bg-white dark:bg-slate-950' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="file-x" class="w-[13px] h-[13px]"></i>
                <span>Log Pemusnahan (BAP)</span>
            </a>
        </div>

        <!-- Filter Form -->
        <form action="{{ route('logs.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-[8px]" x-data="{ submitting: false }" @submit="submitting = true">
            <input type="hidden" name="tab" value="{{ $tab }}">
            
            <div class="md:col-span-6">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Judul Arsip / No. Box / No. BAP..." class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500">
            </div>

            <div class="md:col-span-4">
                <select name="department_id" class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="">-- Semua Departemen --</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->code }} - {{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <button type="submit" :disabled="submitting" class="w-full h-[28px] bg-slate-800 hover:bg-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded-[3px] text-[11px] font-mono font-bold transition flex items-center justify-center gap-[6px] disabled:opacity-50 border border-slate-700 shadow-2xs">
                    <i data-lucide="loader-2" class="w-[12px] h-[12px] animate-spin" x-show="submitting"></i>
                    <i data-lucide="filter" class="w-[12px] h-[12px]" x-show="!submitting"></i>
                    <span x-text="submitting ? 'Memuat...' : 'Filter'"></span>
                </button>
            </div>
        </form>
    </div>

    <!-- DELPHI TDBGRID AUDIT LOGS -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] shadow-xs relative overflow-hidden font-sans">
        @if($tab === 'entry')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">TGL MASUK GUDANG</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">NO. BOX ARSIP</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">JUDUL BERKAS & DEPT</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">LOKASI RAK</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">PIC PENERIMA</th>
                            <th class="py-[6px] px-[10px]">CATATAN RECEPTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-[11px] font-mono">
                        @forelse($entryLogs as $eLog)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="py-[6px] px-[10px] font-bold text-cyan-700 dark:text-cyan-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                {{ $eLog->entry_date->format('d M Y H:i') }}
                            </td>
                            <td class="py-[6px] px-[10px] text-amber-700 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                {{ $eLog->archive->box_number ?? '-' }}
                            </td>
                            <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800">
                                <a href="{{ route('archives.show', $eLog->archive) }}" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block">
                                    {{ $eLog->archive->title }}
                                </a>
                                <span class="text-slate-500 text-[10px]">{{ $eLog->archive->department->name ?? '' }}</span>
                            </td>
                            <td class="py-[6px] px-[10px] font-bold text-emerald-700 dark:text-emerald-400 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                {{ $eLog->location->full_location ?? '-' }}
                            </td>
                            <td class="py-[6px] px-[10px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                {{ $eLog->picGudang->name ?? 'Gudang Specialist' }}
                            </td>
                            <td class="py-[6px] px-[10px] text-slate-600 dark:text-slate-400">
                                {{ $eLog->notes ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-[24px] text-center text-slate-500 text-[11px]">Belum ada data log masuk gudang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- STATUSBAR PAGINATION -->
            <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-[10px] py-[6px] flex items-center justify-between gap-[8px] font-mono text-[11px] text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-[6px]">
                    <i data-lucide="database" class="w-[13px] h-[13px] text-cyan-500"></i>
                    <span>Total <strong>{{ $entryLogs->total() }}</strong> entri log</span>
                </div>
                <div>{{ $entryLogs->links() }}</div>
            </div>

        @elseif($tab === 'borrowing')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">TGL PINJAM / EST. KEMBALI</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">NO. BOX & JUDUL BERKAS</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">PEMINJAM</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">TUJUAN PEMINJAMAN</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">STATUS</th>
                            <th class="py-[6px] px-[10px]">VERIFIKATOR GUDANG</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-[11px] font-mono">
                        @forelse($borrowingLogs as $bLog)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="py-[6px] px-[10px] space-y-[2px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <span class="text-purple-700 dark:text-purple-300 font-bold block">Req: {{ \Carbon\Carbon::parse($bLog->request_date)->format('d M Y') }}</span>
                                <span class="text-slate-500 block text-[10px]">Est: {{ \Carbon\Carbon::parse($bLog->expected_return_date)->format('d M Y') }}</span>
                            </td>
                            <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800">
                                <span class="font-extrabold text-amber-600 dark:text-amber-400 text-[11px] block">{{ $bLog->archive->box_number ?? 'DRAFT' }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $bLog->archive->title }}</span>
                            </td>
                            <td class="py-[6px] px-[10px] text-slate-900 dark:text-slate-200 font-bold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                {{ $bLog->borrower->name }}
                            </td>
                            <td class="py-[6px] px-[10px] text-slate-600 dark:text-slate-400 max-w-xs truncate border-r border-slate-200 dark:border-slate-800">
                                {{ $bLog->purpose }}
                            </td>
                            <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-purple-700 dark:text-purple-300">
                                    {{ $bLog->status }}
                                </span>
                            </td>
                            <td class="py-[6px] px-[10px] text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                {{ $bLog->picGudang->name ?? 'System' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-[24px] text-center text-slate-500 text-[11px]">Belum ada data log peminjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- STATUSBAR PAGINATION -->
            <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-[10px] py-[6px] flex items-center justify-between gap-[8px] font-mono text-[11px] text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-[6px]">
                    <i data-lucide="database" class="w-[13px] h-[13px] text-purple-500"></i>
                    <span>Total <strong>{{ $borrowingLogs->total() }}</strong> entri log</span>
                </div>
                <div>{{ $borrowingLogs->links() }}</div>
            </div>

        @elseif($tab === 'destruction')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">NOMOR BAP</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">JUDUL BERKAS & BOX</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">TGL PEMUSNAHAN</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">METODE PEMUSNAHAN</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">PENGAJU / EKSEKUTOR</th>
                            <th class="py-[6px] px-[10px] text-right">LAMPIRAN BAP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-[11px] font-mono">
                        @forelse($destructionLogs as $dLog)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="py-[6px] px-[10px] font-extrabold text-amber-600 dark:text-amber-400 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                {{ $dLog->bap_number }}
                            </td>
                            <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $dLog->archive->title }}</span>
                                <span class="text-slate-500 text-[10px]">{{ $dLog->archive->box_number }}</span>
                            </td>
                            <td class="py-[6px] px-[10px] text-rose-700 dark:text-rose-400 font-bold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($dLog->destruction_date)->format('d M Y') }}
                            </td>
                            <td class="py-[6px] px-[10px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800">
                                {{ $dLog->method }}
                            </td>
                            <td class="py-[6px] px-[10px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                {{ $dLog->proposedBy->name ?? 'Gudang Specialist' }}
                            </td>
                            <td class="py-[6px] px-[10px] text-right whitespace-nowrap">
                                <a href="{{ route('destructions.bap', $dLog) }}" target="_blank" class="px-[8px] py-[2px] bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-500/30 rounded-[3px] font-bold text-[10px] inline-flex items-center gap-[4px] shadow-2xs">
                                    <i data-lucide="printer" class="w-[11px] h-[11px]"></i>
                                    <span>Cetak BAP</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-[24px] text-center text-slate-500 text-[11px]">Belum ada log pemusnahan dokumen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- STATUSBAR PAGINATION -->
            <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-[10px] py-[6px] flex items-center justify-between gap-[8px] font-mono text-[11px] text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-[6px]">
                    <i data-lucide="database" class="w-[13px] h-[13px] text-rose-500"></i>
                    <span>Total <strong>{{ $destructionLogs->total() }}</strong> entri log</span>
                </div>
                <div>{{ $destructionLogs->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
