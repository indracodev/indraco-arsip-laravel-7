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

<div class="space-y-6" x-data="{
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
    }
}">

    <!-- Header Section with Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 bg-gradient-to-br from-amber-500 to-rose-600 rounded-xl text-white shadow-xs">
                    <i data-lucide="shield-alert" class="w-5 h-5"></i>
                </div>
                <span>Pengajuan Perpanjangan & Pemusnahan Berkas</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs mt-1 font-medium">
                Pusat terpadu monitoring berkas jatuh tempo (H-30), log pengajuan perpanjangan masa simpan, dan riwayat Berita Acara Pemusnahan (BAP).
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Tombol Pengajuan Perpanjangan Mandiri -->
            <a href="{{ route('destructions.extend_form', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-extrabold text-xs shadow-md shadow-purple-600/20 transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                <i data-lucide="clock" class="w-4 h-4"></i>
                <span>+ Ajukan Perpanjangan</span>
            </a>
            <!-- Tombol Pengajuan Pemusnahan Mandiri -->
            <a href="{{ route('destructions.propose', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-extrabold text-xs shadow-md shadow-rose-600/20 transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>+ Ajukan Pemusnahan</span>
            </a>
        </div>
    </div>

    <!-- 3 SUB-TABS NAVIGATION BAR -->
    <div class="flex items-center gap-2 p-1.5 bg-slate-100 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl w-full sm:w-fit text-xs font-bold font-mono">
        <!-- TAB 1: BERKAS H-30 -->
        <button 
            type="button" 
            @click="activeTab = 'h30'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" 
            :class="activeTab === 'h30' 
                ? 'bg-white dark:bg-slate-800 text-amber-600 dark:text-amber-400 shadow-xs border border-slate-200/80 dark:border-slate-700 font-black' 
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer">
            <i data-lucide="hourglass" class="w-3.5 h-3.5" :class="activeTab === 'h30' ? 'text-amber-500' : 'text-slate-400'"></i>
            <span>Berkas Jatuh Tempo (H-30)</span>
            <span 
                class="px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold" 
                :class="expiredArchives.length > 0 ? 'bg-rose-500 text-white animate-pulse' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'" 
                x-text="expiredArchives.length">
            </span>
        </button>

        <!-- TAB 2: RIWAYAT PERPANJANGAN -->
        <button 
            type="button" 
            @click="activeTab = 'extensions'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" 
            :class="activeTab === 'extensions' 
                ? 'bg-white dark:bg-slate-800 text-purple-600 dark:text-purple-400 shadow-xs border border-slate-200/80 dark:border-slate-700 font-black' 
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer">
            <i data-lucide="clock" class="w-3.5 h-3.5" :class="activeTab === 'extensions' ? 'text-purple-500' : 'text-slate-400'"></i>
            <span>Riwayat Perpanjangan</span>
            <span 
                class="px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300" 
                x-text="extensionLogs.length">
            </span>
        </button>

        <!-- TAB 3: RIWAYAT PEMUSNAHAN -->
        <button 
            type="button" 
            @click="activeTab = 'destructions'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" 
            :class="activeTab === 'destructions' 
                ? 'bg-white dark:bg-slate-800 text-rose-600 dark:text-rose-400 shadow-xs border border-slate-200/80 dark:border-slate-700 font-black' 
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer">
            <i data-lucide="trash-2" class="w-3.5 h-3.5" :class="activeTab === 'destructions' ? 'text-rose-500' : 'text-slate-400'"></i>
            <span>Riwayat Pemusnahan</span>
            <span 
                class="px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300" 
                x-text="destructionLogs.length">
            </span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB CONTENT 1: BERKAS JATUH TEMPO (H-30) -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'h30'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="hourglass" class="w-4 h-4 text-amber-500"></i>
                    <span>Daftar Berkas Mendekati Jatuh Tempo (Batas Waktu $\le$ 30 Hari)</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Hanya menampilkan arsip yang telah mencapai atau berada dalam rentang H-30 sebelum masa simpan habis.
                </p>
            </div>

            <!-- Search Input H-30 -->
            <div class="relative w-full sm:w-72">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchExpired" 
                    placeholder="Cari No. Box, judul berkas..." 
                    class="w-full pl-9 pr-8 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition font-mono"
                >
                <button x-show="searchExpired" @click="searchExpired = ''" type="button" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        </div>

        <!-- Tabel Berkas H-30 -->
        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-[11px] font-extrabold uppercase font-mono text-slate-600 dark:text-slate-400 select-none">
                        <th @click="sortExpired('box_number')" class="py-3 px-3.5 cursor-pointer hover:text-amber-500 transition">
                            <div class="flex items-center gap-1">
                                No. Box Arsip
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortExpiredCol !== 'box_number'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-amber-500" x-show="sortExpiredCol === 'box_number' && sortExpiredDir === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-amber-500" x-show="sortExpiredCol === 'box_number' && sortExpiredDir === 'desc'"></i>
                            </div>
                        </th>
                        <th @click="sortExpired('title')" class="py-3 px-3.5 cursor-pointer hover:text-amber-500 transition">
                            <div class="flex items-center gap-1">
                                Judul Berkas & Dept
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortExpiredCol !== 'title'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-amber-500" x-show="sortExpiredCol === 'title' && sortExpiredDir === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-amber-500" x-show="sortExpiredCol === 'title' && sortExpiredDir === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-3 px-3.5">Lokasi Gudang</th>
                        <th @click="sortExpired('retention_expiry_date')" class="py-3 px-3.5 cursor-pointer hover:text-amber-500 transition">
                            <div class="flex items-center gap-1">
                                Tgl Jatuh Tempo
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortExpiredCol !== 'retention_expiry_date'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-amber-500" x-show="sortExpiredCol === 'retention_expiry_date' && sortExpiredDir === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-amber-500" x-show="sortExpiredCol === 'retention_expiry_date' && sortExpiredDir === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-3 px-3.5">Status Retensi</th>
                        <th class="py-3 px-3.5 text-right whitespace-nowrap">Aksi Permintaan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    <template x-for="arc in filteredExpired" :key="arc.id">
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-3.5 font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap" x-text="arc.box_number || '-'"></td>
                            <td class="py-3.5 px-3.5">
                                <span class="font-bold text-slate-900 dark:text-white block" x-text="arc.title"></span>
                                <span class="text-[11px] text-slate-500 font-mono block mt-0.5" x-text="(arc.department ? arc.department.code : 'Dept') + ' • Periode: ' + (arc.period_text || '-')"></span>
                            </td>
                            <td class="py-3.5 px-3.5 text-slate-700 dark:text-slate-300 font-mono text-[11px]" x-text="arc.location_text"></td>
                            <td class="py-3.5 px-3.5 font-mono">
                                <span class="font-extrabold" :class="arc.is_expired ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400'" x-text="arc.formatted_expiry_date"></span>
                                <span class="text-[10px] text-slate-400 block font-sans" x-text="'Retensi: ' + (arc.retention_display || (arc.retention_years ? arc.retention_years + ' Thn' : '-'))"></span>
                            </td>
                            <td class="py-3.5 px-3.5 whitespace-nowrap">
                                <span 
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-bold border" 
                                    :class="arc.is_expired 
                                        ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/30' 
                                        : 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30'" 
                                    x-text="arc.badge_text">
                                </span>
                            </td>
                            <td class="py-3.5 px-3.5 text-right whitespace-nowrap font-mono">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Tombol 1: Perpanjangan -->
                                    <a 
                                        :href="'{{ url('/destructions/extend') }}/' + arc.id + '{{ request()->has('embed') ? '?embed=1' : '' }}'" 
                                        class="px-2.5 py-1 rounded-lg bg-purple-500/10 hover:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30 font-bold text-[11px] transition inline-flex items-center gap-1 cursor-pointer"
                                        title="Ajukan Perpanjangan Masa Simpan Berkas"
                                    >
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-purple-600"></i>
                                        <span>Perpanjangan</span>
                                    </a>
                                    <!-- Tombol 2: Pemusnahan -->
                                    <a 
                                        :href="'{{ url('/destructions/propose') }}/' + arc.id + '{{ request()->has('embed') ? '?embed=1' : '' }}'" 
                                        class="px-2.5 py-1 rounded-lg bg-rose-600 text-white hover:bg-rose-500 font-bold text-[11px] shadow-xs transition inline-flex items-center gap-1 cursor-pointer"
                                        title="Ajukan Pemusnahan Berkas (BAP)"
                                    >
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        <span>Pemusnahan</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- EMPTY STATE JIKA TIDAK ADA DATA H-30 -->
                    <tr x-show="filteredExpired.length === 0">
                        <td colspan="6" class="py-12 px-4 text-center">
                            <div class="max-w-md mx-auto space-y-2.5">
                                <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                                    <i data-lucide="shield-check" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">Tidak Ada Berkas Mendekati Masa Expiry (H-30)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Semua berkas arsip aktif saat ini memiliki masa simpan lebih dari 30 hari kedepan. Jika ingin mengajukan perpanjangan atau pemusnahan berkas tertentu, silakan gunakan tombol aksi di atas.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB CONTENT 2: RIWAYAT / LOG PERPANJANGAN -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'extensions'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4 text-purple-500"></i>
                    <span>Riwayat & Log Pengajuan Perpanjangan Masa Simpan</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Catatan arsip yang telah disetujui perpanjangan masa retensinya beserta lampiran dokumen pendukung.
                </p>
            </div>

            <!-- Search Input Extensions -->
            <div class="relative w-full sm:w-72">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchExt" 
                    placeholder="Cari Box, judul, pemohon..." 
                    class="w-full pl-9 pr-8 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 transition font-mono"
                >
                <button x-show="searchExt" @click="searchExt = ''" type="button" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-[11px] font-extrabold uppercase font-mono text-slate-600 dark:text-slate-400 select-none">
                        <th class="py-3 px-3.5">No. Box Arsip</th>
                        <th class="py-3 px-3.5">Judul Berkas & Dept</th>
                        <th class="py-3 px-3.5">Waktu Pengajuan</th>
                        <th class="py-3 px-3.5">Tambahan Retensi</th>
                        <th class="py-3 px-3.5">Expiry Baru</th>
                        <th class="py-3 px-3.5">Alasan Perpanjangan</th>
                        <th class="py-3 px-3.5">Pemohon</th>
                        <th class="py-3 px-3.5 text-right whitespace-nowrap">Dokumen Bukti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    <template x-for="ext in filteredExtLogs" :key="ext.id">
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-3.5 font-mono font-bold text-purple-600 dark:text-purple-400 whitespace-nowrap" x-text="ext.box_number"></td>
                            <td class="py-3.5 px-3.5">
                                <span class="font-bold text-slate-900 dark:text-white block" x-text="ext.archive_title"></span>
                                <span class="text-[11px] text-slate-500 font-mono block mt-0.5" x-text="'Dept: ' + ext.department_code"></span>
                            </td>
                            <td class="py-3.5 px-3.5 text-slate-600 dark:text-slate-400 font-mono text-[11px] whitespace-nowrap" x-text="ext.date"></td>
                            <td class="py-3.5 px-3.5 font-mono font-bold text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full bg-purple-500/10 border border-purple-500/30" x-text="'+' + ext.additional_years + ' Thn'"></span>
                            </td>
                            <td class="py-3.5 px-3.5 font-mono font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap" x-text="ext.new_expiry_date"></td>
                            <td class="py-3.5 px-3.5 max-w-xs truncate text-slate-600 dark:text-slate-400 text-[11px]" :title="ext.reason" x-text="ext.reason"></td>
                            <td class="py-3.5 px-3.5 text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap text-[11px]" x-text="ext.user_name"></td>
                            <td class="py-3.5 px-3.5 text-right whitespace-nowrap font-mono">
                                <template x-if="ext.scan_file">
                                    <a :href="ext.scan_file" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-purple-600 dark:text-purple-400 border border-slate-300 dark:border-slate-700 text-[11px] font-bold inline-flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                        <span>Lihat Bukti</span>
                                    </a>
                                </template>
                                <template x-if="!ext.scan_file && ext.archive_id">
                                    <a :href="'{{ url('/destructions/extend-print') }}/' + ext.archive_id" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-700 text-[11px] font-bold inline-flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        <span>Cetak Form</span>
                                    </a>
                                </template>
                            </td>
                        </tr>
                    </template>

                    <!-- EMPTY STATE JIKA BELUM ADA LOG PERPANJANGAN -->
                    <tr x-show="filteredExtLogs.length === 0">
                        <td colspan="8" class="py-12 px-4 text-center">
                            <div class="max-w-md mx-auto space-y-2.5">
                                <div class="w-11 h-11 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center mx-auto">
                                    <i data-lucide="clock" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">Belum Ada Riwayat Perpanjangan</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Belum ada berkas arsip yang diajukan atau disetujui perpanjangan masa simpannya.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB CONTENT 3: RIWAYAT / LOG PEMUSNAHAN (BAP) -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'destructions'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4 text-rose-500"></i>
                    <span>Riwayat Berita Acara Pemusnahan (BAP) Disahkan</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Daftar arsip yang telah resmi dimusnahkan beserta dokumen bukti fisik Berita Acara Pemusnahan.
                </p>
            </div>

            <!-- Search Input Destructions -->
            <div class="relative w-full sm:w-72">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchDest" 
                    placeholder="Cari BAP, judul, metode..." 
                    class="w-full pl-9 pr-8 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition font-mono"
                >
                <button x-show="searchDest" @click="searchDest = ''" type="button" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-[11px] font-extrabold uppercase font-mono text-slate-600 dark:text-slate-400 select-none">
                        <th class="py-3 px-3.5">Nomor BAP</th>
                        <th class="py-3 px-3.5">Judul Berkas Arsip</th>
                        <th class="py-3 px-3.5">Tanggal Eksekusi</th>
                        <th class="py-3 px-3.5">Metode Pemusnahan</th>
                        <th class="py-3 px-3.5">Eksekutor / Pemohon</th>
                        <th class="py-3 px-3.5 text-right whitespace-nowrap">Dokumen BAP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    <template x-for="dLog in filteredDestLogs" :key="dLog.id">
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-3.5 font-mono font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap" x-text="dLog.bap_number"></td>
                            <td class="py-3.5 px-3.5">
                                <span class="font-bold text-slate-900 dark:text-white block" x-text="dLog.archive_title"></span>
                                <span class="text-[11px] text-slate-500 font-mono block mt-0.5" x-text="'Box: ' + dLog.box_number + ' • Dept: ' + dLog.department_code"></span>
                            </td>
                            <td class="py-3.5 px-3.5 text-slate-600 dark:text-slate-400 font-mono text-[11px] whitespace-nowrap" x-text="dLog.destruction_date"></td>
                            <td class="py-3.5 px-3.5 font-mono text-slate-700 dark:text-slate-300 text-[11px]" x-text="dLog.method"></td>
                            <td class="py-3.5 px-3.5 text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap text-[11px]" x-text="dLog.executor"></td>
                            <td class="py-3.5 px-3.5 text-right whitespace-nowrap font-mono">
                                <div class="flex items-center justify-end gap-1.5">
                                    <template x-if="dLog.approval_file">
                                        <a :href="dLog.approval_file" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-rose-600 dark:text-rose-400 border border-slate-300 dark:border-slate-700 text-[11px] font-bold inline-flex items-center gap-1 cursor-pointer">
                                            <i data-lucide="file-check" class="w-3.5 h-3.5"></i>
                                            <span>Approval</span>
                                        </a>
                                    </template>
                                    <a :href="'{{ url('/destructions/bap') }}/' + dLog.id" target="_blank" class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold text-[11px] shadow-xs inline-flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        <span>Cetak BAP</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- EMPTY STATE JIKA BELUM ADA LOG PEMUSNAHAN -->
                    <tr x-show="filteredDestLogs.length === 0">
                        <td colspan="6" class="py-12 px-4 text-center">
                            <div class="max-w-md mx-auto space-y-2.5">
                                <div class="w-11 h-11 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                                    <i data-lucide="trash-2" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">Belum Ada Riwayat Pemusnahan Berkas</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Belum ada berkas yang diajukan atau dimusnahkan.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
