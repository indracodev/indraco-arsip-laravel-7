@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Retention & Pemusnahan Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-[10px]" x-data="{
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
    <!-- DELPHI TOP TITLE PANEL -->
    <div class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-[8px]">
            <span class="p-[4px] bg-rose-600/10 text-rose-600 dark:text-rose-400 rounded-[3px] border border-rose-600/20">
                <i data-lucide="shield-alert" class="w-[16px] h-[16px]"></i>
            </span>
            <div>
                <h1 class="text-[13px] font-mono font-black uppercase text-slate-900 dark:text-white tracking-wide flex items-center gap-[6px]">
                    <span>Manajemen Retention & Pemusnahan Dokumen (BAP)</span>
                </h1>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-mono">
                    Pengawasan masa retensi arsip kadaluarsa, perpanjangan simpan, dan Berita Acara Pemusnahan.
                </p>
            </div>
        </div>
    </div>

    <!-- DELPHI TDBGRID: Expired or Expiring Archives -->
    <fieldset class="border border-slate-300 dark:border-slate-800 rounded-[4px] bg-white dark:bg-slate-950 p-[10px] space-y-[8px] shadow-xs">
        <legend class="px-[6px] text-[10px] font-mono font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[2px] flex items-center gap-[4px]">
            <i data-lucide="hourglass" class="w-[12px] h-[12px] text-amber-500"></i>
            <span>Berkas Mendekati / Lewat Masa Simpan (Retention Expiry)</span>
        </legend>

        <div class="flex items-center justify-between gap-[8px] font-mono text-[11px]">
            <span class="text-slate-500">Daftar arsip yang perlu tindakan perpanjangan masa simpan atau eksekusi BAP:</span>
            
            <!-- Quick Search Input -->
            <div class="relative w-[260px]">
                <input 
                    type="text" 
                    x-model="searchExpired" 
                    placeholder="Filter No. Box, judul, dept..." 
                    class="w-full pl-[26px] pr-[24px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500 transition"
                >
                <i data-lucide="search" class="w-[12px] h-[12px] absolute left-[7px] top-[8px] text-slate-400"></i>
                <button x-show="searchExpired" @click="searchExpired = ''" type="button" class="absolute right-[6px] top-[7px] text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <i data-lucide="x" class="w-[12px] h-[12px]"></i>
                </button>
            </div>
        </div>

        <div class="border border-slate-200 dark:border-slate-800 rounded-[3px] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b border-slate-300 dark:border-slate-700">
                            <th @click="sortExpired('box_number')" class="py-[6px] px-[10px] cursor-pointer hover:text-rose-500 transition border-r border-slate-300 dark:border-slate-700">
                                <div class="flex items-center gap-[4px]">
                                    <span>NO. BOX ARSIP</span>
                                    <span class="text-rose-500 font-black" x-show="sortExpiredCol === 'box_number'" x-text="sortExpiredDir === 'asc' ? '▲' : '▼'"></span>
                                </div>
                            </th>
                            <th @click="sortExpired('title')" class="py-[6px] px-[10px] cursor-pointer hover:text-rose-500 transition border-r border-slate-300 dark:border-slate-700">
                                <div class="flex items-center gap-[4px]">
                                    <span>JUDUL BERKAS & DEPT</span>
                                    <span class="text-rose-500 font-black" x-show="sortExpiredCol === 'title'" x-text="sortExpiredDir === 'asc' ? '▲' : '▼'"></span>
                                </div>
                            </th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">LOKASI RAK</th>
                            <th @click="sortExpired('retention_expiry_date')" class="py-[6px] px-[10px] cursor-pointer hover:text-rose-500 transition border-r border-slate-300 dark:border-slate-700">
                                <div class="flex items-center gap-[4px]">
                                    <span>EXPIRY RETENSI</span>
                                    <span class="text-rose-500 font-black" x-show="sortExpiredCol === 'retention_expiry_date'" x-text="sortExpiredDir === 'asc' ? '▲' : '▼'"></span>
                                </div>
                            </th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">STATUS</th>
                            <th class="py-[6px] px-[10px] text-right">AKSI RETENSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-[11px] font-mono">
                        <template x-for="arc in filteredExpired" :key="arc.id">
                            <tr class="hover:bg-rose-500/5 dark:hover:bg-rose-500/10 transition">
                                <td class="py-[6px] px-[10px] text-amber-700 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap" x-text="arc.box_number || '-'"></td>
                                <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800">
                                    <span class="font-bold text-slate-900 dark:text-white block" x-text="arc.title"></span>
                                    <span class="text-[10px] text-slate-500" x-text="(arc.department ? arc.department.code : 'Dept') + ' | ' + (arc.period_text || '-')"></span>
                                </td>
                                <td class="py-[6px] px-[10px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap" x-text="arc.location ? arc.location.full_location : 'Gudang'"></td>
                                <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                    <span class="font-black text-rose-600 dark:text-rose-400" x-text="arc.retention_expiry_date"></span>
                                    <span class="text-[10px] text-slate-500 block" x-text="'(' + (arc.retention_years || 0) + ' Thn Retensi)'"></span>
                                </td>
                                <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                    <span x-show="arc.status === 'destroyed'" class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30">Dimusnahkan</span>
                                    <span x-show="arc.status !== 'destroyed'" class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/30">Jatuh Tempo</span>
                                </td>
                                <td class="py-[6px] px-[10px] text-right whitespace-nowrap">
                                    <template x-if="arc.status !== 'destroyed'">
                                        <div class="inline-flex items-center justify-end gap-[4px]">
                                            <a :href="'{{ url('/destructions/extend') }}/' + arc.id" class="px-[6px] py-[2px] rounded-[3px] bg-purple-500/10 hover:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30 font-bold text-[10px] transition inline-flex items-center gap-[3px]">
                                                <i data-lucide="clock" class="w-[11px] h-[11px]"></i>
                                                <span>Perpanjang</span>
                                            </a>
                                            <a :href="'{{ url('/destructions/propose') }}/' + arc.id" class="px-[6px] py-[2px] rounded-[3px] bg-rose-600 hover:bg-rose-500 text-white font-bold text-[10px] transition inline-flex items-center gap-[3px] shadow-2xs">
                                                <i data-lucide="file-x" class="w-[11px] h-[11px]"></i>
                                                <span>Proses BAP</span>
                                            </a>
                                        </div>
                                    </template>
                                    <template x-if="arc.status === 'destroyed'">
                                        <span class="text-[10px] text-slate-500 font-bold">BAP Selesai</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredExpired.length === 0">
                            <td colspan="6" class="py-[24px] text-center text-slate-500 text-[11px]">Tidak ada berkas jatuh tempo yang cocok dengan kata kunci pencarian.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </fieldset>

    <!-- DELPHI TDBGRID: Completed Destruction History -->
    <fieldset class="border border-slate-300 dark:border-slate-800 rounded-[4px] bg-white dark:bg-slate-950 p-[10px] space-y-[8px] shadow-xs">
        <legend class="px-[6px] text-[10px] font-mono font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[2px] flex items-center gap-[4px]">
            <i data-lucide="check-check" class="w-[12px] h-[12px] text-emerald-500"></i>
            <span>Riwayat Berita Acara Pemusnahan (BAP) Disahkan</span>
        </legend>

        <div class="flex items-center justify-between gap-[8px] font-mono text-[11px]">
            <span class="text-slate-500">Daftar arsip yang telah resmi dimusnahkan beserta dokumen bukti fisik BAP:</span>
            
            <!-- Quick Search Input for BAP -->
            <div class="relative w-[260px]">
                <input 
                    type="text" 
                    x-model="searchBap" 
                    placeholder="Filter BAP / No. Box..." 
                    class="w-full pl-[26px] pr-[24px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                >
                <i data-lucide="search" class="w-[12px] h-[12px] absolute left-[7px] top-[8px] text-slate-400"></i>
                <button x-show="searchBap" @click="searchBap = ''" type="button" class="absolute right-[6px] top-[7px] text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <i data-lucide="x" class="w-[12px] h-[12px]"></i>
                </button>
            </div>
        </div>

        <div class="border border-slate-200 dark:border-slate-800 rounded-[3px] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b border-slate-300 dark:border-slate-700">
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">NOMOR BAP</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">JUDUL BERKAS ARSIP</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">TGL EKSEKUSI</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">METODE PEMUSNAHAN</th>
                            <th class="py-[6px] px-[10px] border-r border-slate-300 dark:border-slate-700">EKSEKUTOR GUDANG</th>
                            <th class="py-[6px] px-[10px] text-right">CETAK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-[11px] font-mono">
                        <template x-for="dLog in filteredBapLogs" :key="dLog.id">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                                <td class="py-[6px] px-[10px] text-amber-600 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap" x-text="dLog.bap_number"></td>
                                <td class="py-[6px] px-[10px] border-r border-slate-200 dark:border-slate-800">
                                    <span class="font-bold text-slate-900 dark:text-white block" x-text="dLog.archive ? dLog.archive.title : '-'"></span>
                                    <span class="text-[10px] text-slate-500" x-text="'Box: ' + (dLog.archive ? dLog.archive.box_number : '-')"></span>
                                </td>
                                <td class="py-[6px] px-[10px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap" x-text="dLog.destruction_date"></td>
                                <td class="py-[6px] px-[10px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800" x-text="dLog.method"></td>
                                <td class="py-[6px] px-[10px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap" x-text="dLog.proposed_by ? dLog.proposed_by.name : 'Warehouse Staff'"></td>
                                <td class="py-[6px] px-[10px] text-right whitespace-nowrap">
                                    <a :href="'{{ url('/destructions/bap') }}/' + dLog.id" target="_blank" class="px-[8px] py-[2px] rounded-[3px] bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 text-amber-700 dark:text-amber-400 font-bold text-[10px] transition inline-flex items-center gap-[4px] shadow-2xs">
                                        <i data-lucide="printer" class="w-[11px] h-[11px]"></i>
                                        <span>BAP (F9)</span>
                                    </a>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredBapLogs.length === 0">
                            <td colspan="6" class="py-[24px] text-center text-slate-500 text-[11px]">Belum ada riwayat pemusnahan dokumen yang dicatat.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </fieldset>
</div>
@endsection
