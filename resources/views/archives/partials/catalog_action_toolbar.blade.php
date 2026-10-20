    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER (TPanel / TToolBar) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="folder-archive" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    KATALOG ARSIP & PENGAJUAN STORAGE
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Cari, ajukan booking gudang, dan kelola masa simpan dokumen fisik & digital PT Indraco.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Refresh (F5) -->
            <button onclick="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Refresh Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-500"></i>
                <span>Refresh (F5)</span>
            </button>

            @if(!auth()->user()->isPicDept())
            <!-- Cetak Custom Label (F9) -->
            <button type="button" @click="
                if (selected && selected.length) {
                    if (selectedPrintable.length > 0) {
                        window.openLabelModal(selectedPrintable);
                    } else {
                        alert('Tidak ada box terpilih yang memenuhi syarat cetak label (label dinonaktifkan untuk status antrian, ditarik, dan dimusnahkan).');
                    }
                } else {
                    window.openLabelModal(null);
                }
            " class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm shrink-0 cursor-pointer" title="Cetak Custom Label Box (F9)">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Cetak Label (F9)</span>
            </button>
            @endif

            @if(!auth()->user()->isPicGudang())
            <!-- Impor Multi-Box Excel (Ctrl+I) -->
            <button type="button" 
                    @click="openImportModal = true; $nextTick(() => { if(window.lucide) window.lucide.createIcons(); });" 
                    class="px-3 py-1 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-mono font-black text-xs rounded border border-emerald-700 shadow transition flex items-center gap-1.5 shrink-0 cursor-pointer" 
                    title="Impor Massal Multi-Box & Butir Dokumen via Excel / CSV">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                <span>Impor Multi-Box (Excel)</span>
            </button>

            <!-- Buat Draft Pengajuan Arsip (F2) -->
            <a href="{{ route('archives.create') }}" class="px-3 py-1 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5 shrink-0" title="Buat Draft Pengajuan Arsip Baru (F2)">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Buat Draft (F2)</span>
            </a>
            @endif
        </div>
    </div>

    <!-- PENDING BORROWING REQUESTS NOTIFICATION BANNER (For PIC Gudang & Admin) -->
    @if(isset($pendingBorrowingRequestsCount) && $pendingBorrowingRequestsCount > 0 && (auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()))
    <div class="p-3.5 rounded bg-purple-500/10 border border-purple-500/40 text-purple-900 dark:text-purple-200 text-xs font-mono flex items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-2">
            <i data-lucide="bell-ring" class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0 animate-bounce"></i>
            <div>
                <strong class="font-extrabold text-purple-950 dark:text-purple-100 uppercase">Ajuan Penarikan Berkas Membutuhkan Pengeluaran:</strong>
                Terdapat <span class="font-black underline text-purple-700 dark:text-purple-300">{{ $pendingBorrowingRequestsCount }} ajuan penarikan berkas</span> dari PIC Departemen yang menunggu pengeluaran fisik dokumen dari gudang.
            </div>
        </div>
        <a href="{{ route('archives.index', ['status' => 'borrow_requested']) }}" class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded font-bold text-xs shadow transition flex items-center gap-1 shrink-0">
            <i data-lucide="arrow-right-circle" class="w-3.5 h-3.5"></i>
            <span>Filter Ajuan Penarikan</span>
        </a>
    </div>
    @endif
