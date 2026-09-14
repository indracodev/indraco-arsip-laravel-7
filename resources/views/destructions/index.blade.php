@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Retention & Pemusnahan Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-8" x-data="{
    searchExpired: '',
    sortExpiredCol: 'retention_expiry_date',
    sortExpiredDir: 'asc',
    expiredArchives: {{ json_encode($expiredArchives) }},

    searchBap: '',
    bapLogs: {{ json_encode($destructionLogs->items()) }},

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

    get filteredBapLogs() {
        if (this.searchBap.trim() === '') return this.bapLogs;
        const q = this.searchBap.toLowerCase();
        return this.bapLogs.filter(d => 
            (d.bap_number && d.bap_number.toLowerCase().includes(q)) ||
            (d.archive && d.archive.title && d.archive.title.toLowerCase().includes(q)) ||
            (d.archive && d.archive.box_number && d.archive.box_number.toLowerCase().includes(q)) ||
            (d.method && d.method.toLowerCase().includes(q))
        );
    },

    sortExpired(col) {
        if (this.sortExpiredCol === col) {
            this.sortExpiredDir = this.sortExpiredDir === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortExpiredCol = col;
            this.sortExpiredDir = 'asc';
        }
        setTimeout(() => { lucide.createIcons(); }, 50);
    }
}">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <i data-lucide="shield-alert" class="w-7 h-7 text-rose-600 dark:text-rose-400"></i>
            Manajemen Retention & Pemusnahan Dokumen (BAP)
        </h1>
        <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm font-medium">Pengawasan masa simpan dokumen kadaluarsa, usulan pemusnahan, dan pencetakan Berita Acara Pemusnahan (BAP).</p>
    </div>

    <!-- Expired or Expiring Archives Panel -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="hourglass" class="w-5 h-5 text-amber-600 dark:text-amber-400"></i>
                    Daftar Berkas Mendekati / Lewat Masa Simpan (Retention Expiry)
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Arsip yang perlu diproses Berita Acara Pemusnahan (BAP)</p>
            </div>

            <!-- Search Input -->
            <div class="relative w-full sm:w-72">
                <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-3 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchExpired" 
                    placeholder="Cari No. Box, judul, atau dept..." 
                    class="w-full pl-10 pr-9 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition font-medium"
                >
                <button x-show="searchExpired" @click="searchExpired = ''" type="button" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 select-none">
                        <th @click="sortExpired('box_number')" class="py-3.5 px-4 cursor-pointer hover:text-rose-500 transition">
                            <div class="flex items-center gap-1.5">
                                No. Box Arsip
                                <i data-lucide="arrow-up-down" class="w-3.5 h-3.5 opacity-40" x-show="sortExpiredCol !== 'box_number'"></i>
                                <i data-lucide="arrow-up" class="w-3.5 h-3.5 text-rose-500" x-show="sortExpiredCol === 'box_number' && sortExpiredDir === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3.5 h-3.5 text-rose-500" x-show="sortExpiredCol === 'box_number' && sortExpiredDir === 'desc'"></i>
                            </div>
                        </th>
                        <th @click="sortExpired('title')" class="py-3.5 px-4 cursor-pointer hover:text-rose-500 transition">
                            <div class="flex items-center gap-1.5">
                                Judul Berkas & Dept
                                <i data-lucide="arrow-up-down" class="w-3.5 h-3.5 opacity-40" x-show="sortExpiredCol !== 'title'"></i>
                                <i data-lucide="arrow-up" class="w-3.5 h-3.5 text-rose-500" x-show="sortExpiredCol === 'title' && sortExpiredDir === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3.5 h-3.5 text-rose-500" x-show="sortExpiredCol === 'title' && sortExpiredDir === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-3.5 px-4">Lokasi Fisik</th>
                        <th @click="sortExpired('retention_expiry_date')" class="py-3.5 px-4 cursor-pointer hover:text-rose-500 transition">
                            <div class="flex items-center gap-1.5">
                                Tgl Expiry Retention
                                <i data-lucide="arrow-up-down" class="w-3.5 h-3.5 opacity-40" x-show="sortExpiredCol !== 'retention_expiry_date'"></i>
                                <i data-lucide="arrow-up" class="w-3.5 h-3.5 text-rose-500" x-show="sortExpiredCol === 'retention_expiry_date' && sortExpiredDir === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3.5 h-3.5 text-rose-500" x-show="sortExpiredCol === 'retention_expiry_date' && sortExpiredDir === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-3.5 px-4">Status Simpan</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">Eksekusi BAP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                    <template x-for="arc in filteredExpired" :key="arc.id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="py-4 px-4 font-mono text-xs text-amber-600 dark:text-amber-400 font-extrabold" x-text="arc.box_number || '-'"></td>
                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-900 dark:text-white block" x-text="arc.title"></span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium" x-text="(arc.department ? arc.department.code : 'Dept') + ' | ' + (arc.period_text || '-')"></span>
                            </td>
                            <td class="py-4 px-4 text-xs text-slate-700 dark:text-slate-300 font-medium" x-text="arc.location ? arc.location.full_location : 'Gudang'"></td>
                            <td class="py-4 px-4 text-xs">
                                <span class="font-extrabold text-rose-600 dark:text-rose-400" x-text="arc.retention_expiry_date"></span>
                                <span class="text-[10px] text-slate-500 block" x-text="'(' + (arc.retention_years || 0) + ' Thn Retention)'"></span>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span x-show="arc.status === 'destroyed'" class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30">Dimusnahkan</span>
                                <span x-show="arc.status !== 'destroyed'" class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/30">Jatuh Tempo</span>
                            </td>
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <template x-if="arc.status !== 'destroyed'">
                                    <div class="flex items-center justify-end gap-2">
                                        <a :href="'{{ url('/destructions/extend') }}/' + arc.id" class="px-3 py-1.5 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30 font-bold text-xs transition inline-flex items-center gap-1.5 whitespace-nowrap">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            <span>Perpanjang Masa Simpan</span>
                                        </a>
                                        <a :href="'{{ url('/destructions/propose') }}/' + arc.id" class="px-3.5 py-1.5 rounded-xl bg-rose-600 text-white hover:bg-rose-500 font-black text-xs shadow-md transition inline-flex items-center gap-1.5 whitespace-nowrap">
                                            <i data-lucide="file-x" class="w-4 h-4"></i>
                                            <span>Proses BAP</span>
                                        </a>
                                    </div>
                                </template>
                                <template x-if="arc.status === 'destroyed'">
                                    <span class="text-xs text-slate-500 font-bold whitespace-nowrap">BAP Prosedur Selesai</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredExpired.length === 0">
                        <td colspan="6" class="py-8 text-center text-slate-500 text-xs font-medium">Tidak ada berkas jatuh tempo yang cocok dengan kata kunci pencarian.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Completed Destruction History Panel -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="check-check" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                    Riwayat Berita Acara Pemusnahan (BAP) Disahkan
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Arsip yang telah resmi dimusnahkan beserta dokumen bukti fisik BAP</p>
            </div>

            <!-- Search Input for BAP -->
            <div class="relative w-full sm:w-72">
                <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-3 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchBap" 
                    placeholder="Cari BAP / No. Box..." 
                    class="w-full pl-10 pr-9 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 transition font-medium"
                >
                <button x-show="searchBap" @click="searchBap = ''" type="button" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="py-3.5 px-4">Nomor BAP</th>
                        <th class="py-3.5 px-4">Judul Berkas Arsip</th>
                        <th class="py-3.5 px-4">Tanggal Eksekusi</th>
                        <th class="py-3.5 px-4">Metode Pemusnahan</th>
                        <th class="py-3.5 px-4">Eksekutor Gudang</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">Cetak BAP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                    <template x-for="dLog in filteredBapLogs" :key="dLog.id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="py-4 px-4 font-mono text-xs text-amber-600 dark:text-amber-400 font-extrabold" x-text="dLog.bap_number"></td>
                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-900 dark:text-white block" x-text="dLog.archive ? dLog.archive.title : '-'"></span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium" x-text="'Box Code: ' + (dLog.archive ? dLog.archive.box_number : '-')"></span>
                            </td>
                            <td class="py-4 px-4 text-xs text-slate-700 dark:text-slate-300 font-medium" x-text="dLog.destruction_date"></td>
                            <td class="py-4 px-4 text-xs text-slate-700 dark:text-slate-300 font-medium" x-text="dLog.method"></td>
                            <td class="py-4 px-4 text-xs text-slate-700 dark:text-slate-300 font-medium" x-text="dLog.proposed_by ? dLog.proposed_by.name : 'Gudang Specialist'"></td>
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <a :href="'{{ url('/destructions/bap') }}/' + dLog.id" target="_blank" class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-amber-600 dark:text-amber-400 font-extrabold text-xs transition inline-flex items-center gap-1.5 whitespace-nowrap">
                                    <i data-lucide="printer" class="w-4 h-4"></i>
                                    <span>Cetak BAP</span>
                                </a>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredBapLogs.length === 0">
                        <td colspan="6" class="py-8 text-center text-slate-500 text-xs font-medium">Belum ada riwayat pemusnahan dokumen yang dicatat.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

