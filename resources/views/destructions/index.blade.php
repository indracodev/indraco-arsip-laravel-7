@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Pengajuan Perpanjangan & Pemusnahan - DMS PT Indraco')

@section('content')
@php
    $defaultTab = request('tab', session('active_tab', 'h30'));
    if (request('view') === 'destruction') {
        $defaultTab = 'destructions';
    } elseif (request('view') === 'expiry') {
        $defaultTab = 'h30';
    }
@endphp

<div class="space-y-3" x-data="{
    activeTab: '{{ $defaultTab }}',

    // Tab 1: Berkas H-30
    searchExpired: '',
    sortExpiredCol: 'retention_expiry_date',
    sortExpiredDir: 'asc',
    expiredArchives: {{ json_encode($expiredArchives) }},

    // Tab 2: Log Perpanjangan
    searchExt: '',
    extensionLogs: {{ json_encode($extensionLogs) }},

    // Tab 3: Log Pemusnahan
    searchDest: '',
    destructionLogs: {{ json_encode($destructionLogs) }},

    get filteredExpired() {
        let res = [...this.expiredArchives];
        if (this.searchExpired.trim() !== '') {
            const q = this.searchExpired.toLowerCase();
            res = res.filter(a => 
                (a.box_number && a.box_number.toLowerCase().includes(q)) ||
                (a.title && a.title.toLowerCase().includes(q)) ||
                (a.department && a.department.code && a.department.code.toLowerCase().includes(q))
            );
        }
        res.sort((a, b) => {
            let valA = a[this.sortExpiredCol] ?? '';
            let valB = b[this.sortExpiredCol] ?? '';
            if (typeof valA === 'string') valA = valA.toLowerCase();
            if (typeof valB === 'string') valB = valB.toLowerCase();
            if (valA < valB) return this.sortExpiredDir === 'asc' ? -1 : 1;
            if (valA > valB) return this.sortExpiredDir === 'asc' ? 1 : -1;
            return 0;
        });
        return res;
    },

    get filteredExtLogs() {
        if (this.searchExt.trim() === '') return this.extensionLogs;
        const q = this.searchExt.toLowerCase();
        return this.extensionLogs.filter(e => 
            (e.box_number && e.box_number.toLowerCase().includes(q)) ||
            (e.archive_title && e.archive_title.toLowerCase().includes(q)) ||
            (e.user_name && e.user_name.toLowerCase().includes(q)) ||
            (e.reason && e.reason.toLowerCase().includes(q))
        );
    },

    get filteredDestLogs() {
        if (this.searchDest.trim() === '') return this.destructionLogs;
        const q = this.searchDest.toLowerCase();
        return this.destructionLogs.filter(d => 
            (d.bap_number && d.bap_number.toLowerCase().includes(q)) ||
            (d.archive_title && d.archive_title.toLowerCase().includes(q)) ||
            (d.box_number && d.box_number.toLowerCase().includes(q)) ||
            (d.method && d.method.toLowerCase().includes(q)) ||
            (d.executor && d.executor.toLowerCase().includes(q))
        );
    },

    sortExpired(col) {
        if (this.sortExpiredCol === col) {
            this.sortExpiredDir = this.sortExpiredDir === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortExpiredCol = col;
            this.sortExpiredDir = 'asc';
        }
        setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 50);
    },

    init() {
        try {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({
                    type: 'UPDATE_FORM_BADGE',
                    formId: 'destructions',
                    count: this.expiredArchives.length
                }, '*');
            }
        } catch(e) {}
    }
}">

    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER (TPanel / TToolBar) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-1.5 bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 rounded">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    PENGAJUAN PERPANJANGAN & PEMUSNAHAN BERKAS
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Monitoring berkas jatuh tempo (H-30), log perpanjangan masa simpan, dan riwayat BAP pemusnahan.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Refresh (F5) -->
            <button onclick="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Refresh Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-500"></i>
                <span>Refresh (F5)</span>
            </button>

            <!-- Tombol Pengajuan Perpanjangan Mandiri -->
            <a href="{{ route('destructions.extend_form', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3 py-1 bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-500 hover:to-purple-400 text-white font-mono font-bold text-xs rounded border border-purple-700 shadow-sm transition flex items-center gap-1.5 shrink-0" title="Ajukan Perpanjangan Masa Simpan Berkas">
                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                <span>+ Perpanjangan</span>
            </a>

            <!-- Tombol Pengajuan Pemusnahan Mandiri -->
            <a href="{{ route('destructions.propose', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3 py-1 bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white font-mono font-bold text-xs rounded border border-rose-700 shadow-sm transition flex items-center gap-1.5 shrink-0" title="Ajukan Pemusnahan Berkas (BAP)">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                <span>+ Pemusnahan</span>
            </a>
        </div>
    </div>

    @if (session('success'))
    <div class="p-3 rounded bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5 text-xs font-mono shadow-sm">
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5"></i>
        <div class="space-y-0.5">
            <span class="font-bold block uppercase">BERHASIL DIPROSES</span>
            <p class="text-[11px]">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <!-- DELPHI PAGECONTROL SUB-TABS NAVIGATION BAR -->
    <div class="flex items-center gap-1.5 p-1 bg-slate-200/70 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded w-full sm:w-fit text-xs font-mono font-bold">
        <!-- TAB 1: BERKAS H-30 -->
        <button 
            type="button" 
            @click="activeTab = 'h30'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" 
            :class="activeTab === 'h30' 
                ? 'bg-white dark:bg-slate-800 text-amber-600 dark:text-amber-400 shadow-xs border border-slate-300 dark:border-slate-700 font-bold' 
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
            class="px-3 py-1.5 rounded transition flex items-center gap-1.5 cursor-pointer">
            <i data-lucide="hourglass" class="w-3.5 h-3.5" :class="activeTab === 'h30' ? 'text-amber-500' : 'text-slate-400'"></i>
            <span>Berkas Jatuh Tempo (H-30)</span>
            <span 
                class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold" 
                :class="expiredArchives.length > 0 ? 'bg-rose-500 text-white animate-pulse' : 'bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300'" 
                x-text="expiredArchives.length">
            </span>
        </button>

        <!-- TAB 2: RIWAYAT PERPANJANGAN -->
        <button 
            type="button" 
            @click="activeTab = 'extensions'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" 
            :class="activeTab === 'extensions' 
                ? 'bg-white dark:bg-slate-800 text-purple-600 dark:text-purple-400 shadow-xs border border-slate-300 dark:border-slate-700 font-bold' 
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
            class="px-3 py-1.5 rounded transition flex items-center gap-1.5 cursor-pointer">
            <i data-lucide="clock" class="w-3.5 h-3.5" :class="activeTab === 'extensions' ? 'text-purple-500' : 'text-slate-400'"></i>
            <span>Riwayat Perpanjangan</span>
            <span 
                class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300" 
                x-text="extensionLogs.length">
            </span>
        </button>

        <!-- TAB 3: RIWAYAT PEMUSNAHAN -->
        <button 
            type="button" 
            @click="activeTab = 'destructions'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" 
            :class="activeTab === 'destructions' 
                ? 'bg-white dark:bg-slate-800 text-rose-600 dark:text-rose-400 shadow-xs border border-slate-300 dark:border-slate-700 font-bold' 
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
            class="px-3 py-1.5 rounded transition flex items-center gap-1.5 cursor-pointer">
            <i data-lucide="trash-2" class="w-3.5 h-3.5" :class="activeTab === 'destructions' ? 'text-rose-500' : 'text-slate-400'"></i>
            <span>Riwayat Pemusnahan</span>
            <span 
                class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300" 
                x-text="destructionLogs.length">
            </span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB CONTENT 1: BERKAS JATUH TEMPO (H-30) -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'h30'" class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm space-y-3 font-mono">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase flex items-center gap-1.5">
                    <i data-lucide="hourglass" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span>Daftar Berkas Mendekati Jatuh Tempo (Batas Waktu &le; 30 Hari)</span>
                </h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                    Hanya menampilkan arsip yang telah mencapai atau berada dalam rentang H-30 sebelum masa simpan habis.
                </p>
            </div>

            <!-- Search Input H-30 -->
            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchExpired" 
                    placeholder="Cari No. Box, judul berkas..." 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                >
                <button x-show="searchExpired" @click="searchExpired = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        </div>

        <!-- Tabel TDBGrid Berkas H-30 -->
        <div class="overflow-x-auto border border-slate-300 dark:border-slate-800 rounded">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                        <th @click="sortExpired('box_number')" class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700 cursor-pointer hover:text-amber-500 transition">
                            <div class="flex items-center gap-1">
                                <span>NO. BOX ARSIP</span>
                                <span class="text-amber-500 font-bold" x-show="sortExpiredCol === 'box_number'" x-text="sortExpiredDir === 'asc' ? '▲' : '▼'"></span>
                            </div>
                        </th>
                        <th @click="sortExpired('title')" class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700 cursor-pointer hover:text-amber-500 transition">
                            <div class="flex items-center gap-1">
                                <span>JUDUL BERKAS & DEPT</span>
                                <span class="text-amber-500 font-bold" x-show="sortExpiredCol === 'title'" x-text="sortExpiredDir === 'asc' ? '▲' : '▼'"></span>
                            </div>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <span>LOKASI GUDANG</span>
                        </th>
                        <th @click="sortExpired('retention_expiry_date')" class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700 cursor-pointer hover:text-amber-500 transition">
                            <div class="flex items-center gap-1">
                                <span>TGL JATUH TEMPO</span>
                                <span class="text-amber-500 font-bold" x-show="sortExpiredCol === 'retention_expiry_date'" x-text="sortExpiredDir === 'asc' ? '▲' : '▼'"></span>
                            </div>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <span>STATUS RETENSI</span>
                        </th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">
                            <span>AKSI PERMINTAAN</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-xs font-mono">
                    <template x-for="arc in filteredExpired" :key="arc.id">
                        <tr class="hover:bg-amber-500/5 dark:hover:bg-slate-900/50 transition">
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap" x-text="arc.box_number || '-'"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <a :href="'{{ url('/archives') }}/' + arc.id" class="font-sans font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition truncate max-w-sm" :title="arc.title" x-text="arc.title"></a>
                                        <!-- Tombol Preview Lampiran Berkas -->
                                        <template x-if="arc.file_path">
                                            <button type="button" 
                                                    @click="dmsPreviewFile({
                                                        url: arc.file_path,
                                                        stream_url: arc.preview_stream_url,
                                                        raw_path: arc.raw_file_path,
                                                        name: arc.title,
                                                        ext: arc.file_extension
                                                    })"
                                                    class="text-amber-600 hover:text-amber-500 p-0.5 rounded cursor-pointer shrink-0" 
                                                    title="Pratinjau Berkas Digital">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </template>
                                    </div>
                                    <span class="text-[10px] text-slate-500 block" x-text="(arc.department ? arc.department.code : 'Dept') + ' • Periode: ' + (arc.period_text || '-')"></span>
                                </div>
                            </td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 text-[11px]" x-text="arc.location_text"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <span class="font-bold" :class="arc.is_expired ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400'" x-text="arc.formatted_expiry_date"></span>
                                <span class="text-[10px] text-slate-400 block font-sans" x-text="'Retensi: ' + (arc.retention_display || (arc.retention_years ? arc.retention_years + ' Thn' : '-'))"></span>
                            </td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <span 
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border" 
                                    :class="arc.is_expired 
                                        ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/30 animate-warning-blink' 
                                        : 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30'" 
                                    x-text="arc.badge_text">
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Tombol 1: Perpanjangan -->
                                    <a 
                                        :href="'{{ url('/destructions/extend') }}/' + arc.id + '{{ request()->has('embed') ? '?embed=1' : '' }}'" 
                                        class="px-2.5 py-1 rounded bg-purple-500/10 hover:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30 font-bold text-[11px] transition inline-flex items-center gap-1 cursor-pointer"
                                        title="Ajukan Perpanjangan Masa Simpan Berkas"
                                    >
                                        <i data-lucide="clock" class="w-3 h-3 text-purple-600"></i>
                                        <span>Perpanjangan</span>
                                    </a>
                                    <!-- Tombol 2: Pemusnahan -->
                                    <a 
                                        :href="'{{ url('/destructions/propose') }}/' + arc.id + '{{ request()->has('embed') ? '?embed=1' : '' }}'" 
                                        class="px-2.5 py-1 rounded bg-rose-600 text-white hover:bg-rose-500 border border-rose-700 font-bold text-[11px] shadow-sm transition inline-flex items-center gap-1 cursor-pointer"
                                        title="Ajukan Pemusnahan Berkas (BAP)"
                                    >
                                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                                        <span>Pemusnahan</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- EMPTY STATE JIKA TIDAK ADA DATA H-30 -->
                    <tr x-show="filteredExpired.length === 0">
                        <td colspan="6" class="py-8 px-4 text-center text-slate-500 dark:text-slate-400">
                            <i data-lucide="shield-check" class="w-8 h-8 text-emerald-500 mx-auto mb-2 opacity-80"></i>
                            <div class="font-bold text-slate-800 dark:text-slate-200">Tidak Ada Berkas Mendekati Masa Expiry (H-30)</div>
                            <p class="text-[11px] text-slate-400 mt-0.5">Semua berkas arsip aktif saat ini memiliki masa simpan lebih dari 30 hari kedepan.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB CONTENT 2: RIWAYAT / LOG PERPANJANGAN -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'extensions'" class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm space-y-3 font-mono">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase flex items-center gap-1.5">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-purple-500"></i>
                    <span>Riwayat & Log Pengajuan Perpanjangan Masa Simpan</span>
                </h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                    Catatan arsip yang telah disetujui perpanjangan masa retensinya beserta lampiran dokumen pendukung.
                </p>
            </div>

            <!-- Search Input Extensions -->
            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchExt" 
                    placeholder="Cari Box, judul, pemohon..." 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition"
                >
                <button x-show="searchExt" @click="searchExt = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        </div>

        <!-- Tabel TDBGrid Log Perpanjangan -->
        <div class="overflow-x-auto border border-slate-300 dark:border-slate-800 rounded">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">NO. BOX ARSIP</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">JUDUL BERKAS & DEPT</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">WAKTU PENGAJUAN</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">TAMBAHAN RETENSI</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">EXPIRY BARU</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">ALASAN PERPANJANGAN</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">PEMOHON</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">DOKUMEN BUKTI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-xs font-mono">
                    <template x-for="ext in filteredExtLogs" :key="ext.id">
                        <tr class="hover:bg-amber-500/5 dark:hover:bg-slate-900/50 transition">
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 font-bold text-purple-600 dark:text-purple-400 whitespace-nowrap" x-text="ext.box_number"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                                <span class="font-sans font-bold text-slate-900 dark:text-white block truncate max-w-sm" :title="ext.archive_title" x-text="ext.archive_title"></span>
                                <span class="text-[10px] text-slate-500 block mt-0.5" x-text="'Dept: ' + ext.department_code"></span>
                            </td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 text-[11px] whitespace-nowrap" x-text="ext.date"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 font-bold text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-purple-500/10 border border-purple-500/30 text-[10px]" x-text="'+' + ext.additional_years + ' Thn'"></span>
                            </td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap" x-text="ext.new_expiry_date"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 max-w-xs truncate font-sans text-slate-600 dark:text-slate-400 text-[11px]" :title="ext.reason" x-text="ext.reason"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 whitespace-nowrap text-[11px]" x-text="ext.user_name"></td>
                            <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                <template x-if="ext.scan_file">
                                    <button type="button" 
                                            @click="dmsPreviewFile({
                                                url: ext.scan_file,
                                                stream_url: ext.scan_file_stream,
                                                raw_path: ext.scan_file_raw,
                                                name: 'Form Perpanjangan - ' + (ext.box_number || ext.archive_title),
                                                ext: (ext.scan_file_raw || ext.scan_file || '').split('.').pop().toLowerCase()
                                            })"
                                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-purple-600 dark:text-purple-400 border border-slate-300 dark:border-slate-700 text-[10px] font-bold inline-flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="file-text" class="w-3 h-3"></i>
                                        <span>Lihat Bukti</span>
                                    </button>
                                </template>
                                <template x-if="!ext.scan_file && ext.archive_id">
                                    <a :href="'{{ url('/destructions/extend-print') }}/' + ext.archive_id" target="_blank" class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-700 text-[10px] font-bold inline-flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="printer" class="w-3 h-3"></i>
                                        <span>Cetak Form</span>
                                    </a>
                                </template>
                            </td>
                        </tr>
                    </template>

                    <!-- EMPTY STATE JIKA BELUM ADA LOG PERPANJANGAN -->
                    <tr x-show="filteredExtLogs.length === 0">
                        <td colspan="8" class="py-8 px-4 text-center text-slate-500 dark:text-slate-400">
                            <i data-lucide="clock" class="w-8 h-8 text-purple-400 mx-auto mb-2 opacity-60"></i>
                            <div class="font-bold text-slate-800 dark:text-slate-200">Belum Ada Riwayat Perpanjangan</div>
                            <p class="text-[11px] text-slate-400 mt-0.5">Belum ada berkas arsip yang diajukan atau disetujui perpanjangan masa simpannya.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB CONTENT 3: RIWAYAT / LOG PEMUSNAHAN (BAP) -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'destructions'" class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm space-y-3 font-mono">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase flex items-center gap-1.5">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                    <span>Riwayat Berita Acara Pemusnahan (BAP) Disahkan</span>
                </h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                    Daftar arsip yang telah resmi dimusnahkan beserta dokumen bukti fisik Berita Acara Pemusnahan.
                </p>
            </div>

            <!-- Search Input Destructions -->
            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchDest" 
                    placeholder="Cari BAP, judul, metode..." 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition"
                >
                <button x-show="searchDest" @click="searchDest = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        </div>

        <!-- Tabel TDBGrid Log Pemusnahan -->
        <div class="overflow-x-auto border border-slate-300 dark:border-slate-800 rounded">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">NOMOR BAP</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">JUDUL BERKAS ARSIP</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">TANGGAL EKSEKUSI</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">METODE PEMUSNAHAN</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">EKSEKUTOR / PEMOHON</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">DOKUMEN BAP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-xs font-mono">
                    <template x-for="dLog in filteredDestLogs" :key="dLog.id">
                        <tr class="hover:bg-amber-500/5 dark:hover:bg-slate-900/50 transition">
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap" x-text="dLog.bap_number"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                                <span class="font-sans font-bold text-slate-900 dark:text-white block truncate max-w-sm" :title="dLog.archive_title" x-text="dLog.archive_title"></span>
                                <span class="text-[10px] text-slate-500 block mt-0.5" x-text="'Box: ' + dLog.box_number + ' • Dept: ' + dLog.department_code"></span>
                            </td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 text-[11px] whitespace-nowrap" x-text="dLog.destruction_date"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 text-[11px]" x-text="dLog.method"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 whitespace-nowrap text-[11px]" x-text="dLog.executor"></td>
                            <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <template x-if="dLog.approval_file">
                                        <button type="button" 
                                                @click="dmsPreviewFile({
                                                    url: dLog.approval_file,
                                                    stream_url: dLog.approval_file_stream,
                                                    raw_path: dLog.approval_file_raw,
                                                    name: 'Approval Pemusnahan - ' + (dLog.bap_number || dLog.archive_title),
                                                    ext: (dLog.approval_file_raw || dLog.approval_file || '').split('.').pop().toLowerCase()
                                                })"
                                                class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-rose-600 dark:text-rose-400 border border-slate-300 dark:border-slate-700 text-[10px] font-bold inline-flex items-center gap-1 cursor-pointer">
                                            <i data-lucide="file-check" class="w-3 h-3"></i>
                                            <span>Approval</span>
                                        </button>
                                    </template>
                                    <a :href="'{{ url('/destructions/bap') }}/' + dLog.id" target="_blank" class="px-2 py-0.5 rounded bg-rose-600 hover:bg-rose-500 text-white font-bold text-[10px] border border-rose-700 shadow-sm inline-flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="printer" class="w-3 h-3"></i>
                                        <span>Cetak BAP</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- EMPTY STATE JIKA BELUM ADA LOG PEMUSNAHAN -->
                    <tr x-show="filteredDestLogs.length === 0">
                        <td colspan="6" class="py-8 px-4 text-center text-slate-500 dark:text-slate-400">
                            <i data-lucide="trash-2" class="w-8 h-8 text-rose-400 mx-auto mb-2 opacity-60"></i>
                            <div class="font-bold text-slate-800 dark:text-slate-200">Belum Ada Riwayat Pemusnahan Berkas</div>
                            <p class="text-[11px] text-slate-400 mt-0.5">Belum ada berkas yang diajukan atau dimusnahkan.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
