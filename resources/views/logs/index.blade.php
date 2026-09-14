@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Log & Audit Trail - DMS PT Indraco')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <i data-lucide="history" class="w-7 h-7 text-cyan-600 dark:text-cyan-400"></i>
                Global Log Audit Trail System
            </h1>
            <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm font-medium">Pencatatan riwayat otomatis untuk aktivitas penerimaan fisik gudang, peminjaman, dan pemusnahan dokumen.</p>
        </div>
    </div>

    <!-- Navigation Tabs & Filter Bar -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-4">
        <!-- Tabs -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 gap-2">
            <a href="{{ route('logs.index', ['tab' => 'entry']) }}" class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition flex items-center gap-2 {{ $tab === 'entry' ? 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border-b-2 border-cyan-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="log-in" class="w-4 h-4"></i> Log Masuk Gudang
            </a>

            <a href="{{ route('logs.index', ['tab' => 'borrowing']) }}" class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition flex items-center gap-2 {{ $tab === 'borrowing' ? 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-b-2 border-purple-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="file-symlink" class="w-4 h-4"></i> Log Peminjaman
            </a>

            <a href="{{ route('logs.index', ['tab' => 'destruction']) }}" class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition flex items-center gap-2 {{ $tab === 'destruction' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-b-2 border-rose-500' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="file-x" class="w-4 h-4"></i> Log Pemusnahan (BAP)
            </a>
        </div>

        <!-- Filter Form -->
        <form action="{{ route('logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2" x-data="{ submitting: false }" @submit="submitting = true">
            <input type="hidden" name="tab" value="{{ $tab }}">
            
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Judul Arsip / No. Box / No. BAP..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-amber-500 font-medium">
            </div>

            <div>
                <select name="department_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium">
                    <option value="">-- Semua Departemen --</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->code }} - {{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" :disabled="submitting" class="w-full py-2 bg-slate-800 dark:bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 disabled:opacity-50 shadow-sm">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                    <i data-lucide="filter" class="w-3.5 h-3.5" x-show="!submitting"></i>
                    <span x-text="submitting ? 'Memuat Log...' : 'Terapkan Filter Log'"></span>
                </button>
            </div>
        </form>
    </div>

    <!-- Log Table Content -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-6">
        @if($tab === 'entry')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-3 px-4">Tgl Masuk Gudang</th>
                            <th class="py-3 px-4">No. Box Arsip</th>
                            <th class="py-3 px-4">Judul Berkas & Dept</th>
                            <th class="py-3 px-4">Lokasi Fisik Slot Rak</th>
                            <th class="py-3 px-4">PIC Gudang Penerima</th>
                            <th class="py-3 px-4">Catatan Reception</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs">
                        @forelse($entryLogs as $eLog)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition font-medium">
                            <td class="py-3.5 px-4 font-bold text-cyan-700 dark:text-cyan-300">
                                {{ $eLog->entry_date->format('d M Y H:i') }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-extrabold text-amber-600 dark:text-amber-400">
                                {{ $eLog->archive->box_number }}
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="{{ route('archives.show', $eLog->archive) }}" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block">
                                    {{ $eLog->archive->title }}
                                </a>
                                <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ $eLog->archive->department->name ?? '' }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-emerald-700 dark:text-emerald-400">
                                {{ $eLog->location->full_location ?? '' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                {{ $eLog->picGudang->name ?? 'Gudang Specialist' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400">
                                {{ $eLog->notes }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada data log masuk gudang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $entryLogs->links() }}</div>

        @elseif($tab === 'borrowing')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-3 px-4">Tgl Pinjam / Pengembalian</th>
                            <th class="py-3 px-4">No. Box & Judul Berkas</th>
                            <th class="py-3 px-4">Peminjam</th>
                            <th class="py-3 px-4">Tujuan Peminjaman</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Verifikasi Gudang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs font-medium">
                        @forelse($borrowingLogs as $bLog)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="py-3.5 px-4 space-y-0.5">
                                <span class="text-purple-700 dark:text-purple-300 font-bold block">Req: {{ \Carbon\Carbon::parse($bLog->request_date)->format('d M Y') }}</span>
                                <span class="text-slate-500 dark:text-slate-400 block">Est: {{ \Carbon\Carbon::parse($bLog->expected_return_date)->format('d M Y') }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-extrabold text-amber-600 dark:text-amber-400 text-[11px] block">{{ $bLog->archive->box_number }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $bLog->archive->title }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-900 dark:text-slate-200 font-bold">
                                {{ $bLog->borrower->name }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 max-w-xs truncate">
                                {{ $bLog->purpose }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-purple-700 dark:text-purple-300">
                                    {{ $bLog->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400">
                                {{ $bLog->picGudang->name ?? 'System' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada data log peminjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $borrowingLogs->links() }}</div>

        @elseif($tab === 'destruction')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-3 px-4">Nomor BAP</th>
                            <th class="py-3 px-4">Judul Berkas & Box</th>
                            <th class="py-3 px-4">Tgl Pemusnahan</th>
                            <th class="py-3 px-4">Metode Execution</th>
                            <th class="py-3 px-4">Pengaju / Approval</th>
                            <th class="py-3 px-4 text-right">Lampiran BAP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs font-medium">
                        @forelse($destructionLogs as $dLog)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="py-3.5 px-4 font-mono font-extrabold text-amber-600 dark:text-amber-400">
                                {{ $dLog->bap_number }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $dLog->archive->title }}</span>
                                <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ $dLog->archive->box_number }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-rose-700 dark:text-rose-400 font-bold">
                                {{ \Carbon\Carbon::parse($dLog->destruction_date)->format('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                {{ $dLog->method }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                {{ $dLog->proposedBy->name ?? 'Gudang Specialist' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('destructions.bap', $dLog) }}" target="_blank" class="px-2.5 py-1 bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-500/30 rounded font-bold hover:bg-amber-500/30">
                                    Cetak BAP
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada log pemusnahan dokumen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $destructionLogs->links() }}</div>
        @endif
    </div>
</div>
@endsection
