    <!-- MODAL DISPATCH / KELUARKAN ARSIP UNTUK PIC GUDANG -->
    <div x-show="dispatchModalLog" 
         x-transition.opacity 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 font-mono text-xs"
             @click.away="dispatchModalLog = null">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <div class="p-2 bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-xl">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">Pengeluaran Berkas Fisik Arsip</h3>
                        <p class="text-[11px] text-slate-500">Konfirmasi pengeluaran dokumen berdasarkan ajuan penarikan PIC Dept.</p>
                    </div>
                </div>
                <button type="button" @click="dispatchModalLog = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Detail Penarikan & Arsip -->
            <template x-if="dispatchModalLog">
                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">NO. BOX / KODE:</span>
                            <span class="font-black text-amber-600 dark:text-amber-400 text-sm" x-text="dispatchModalLog.box_number"></span>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-xs" x-text="dispatchModalLog.archive_title"></h4>
                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold" x-text="'📍 Lokasi Rak: ' + dispatchModalLog.location"></p>
                    </div>

                    <div class="p-3 bg-purple-500/10 border border-purple-500/30 rounded-xl space-y-1.5 text-purple-900 dark:text-purple-200">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold">Pemohon Penarikan:</span>
                            <span class="font-black" x-text="dispatchModalLog.borrower_name + ' (' + dispatchModalLog.borrower_dept + ')'"></span>
                        </div>
                        <template x-if="dispatchModalLog.expected_return_date && dispatchModalLog.expected_return_date !== 'Hanya Diambil (Permanen)'">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold">Estimasi Pengembalian:</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400" x-text="dispatchModalLog.expected_return_date"></span>
                            </div>
                        </template>
                        <template x-if="!dispatchModalLog.expected_return_date || dispatchModalLog.expected_return_date === 'Hanya Diambil (Permanen)'">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold">Sifat Penarikan:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">Penarikan Berkas (Tanpa Pengembalian)</span>
                            </div>
                        </template>
                        <div class="pt-1 text-[11px] border-t border-purple-500/20">
                            <span class="font-bold block">Keperluan / Alasan:</span>
                            <p class="italic text-slate-700 dark:text-slate-300" x-text="dispatchModalLog.purpose"></p>
                        </div>
                    </div>

                    <!-- Link Preview Approval File -->
                    <template x-if="dispatchModalLog.approval_url">
                        <div class="flex items-center justify-between p-2.5 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                            <span class="font-bold text-[11px] text-amber-900 dark:text-amber-200 flex items-center gap-1.5">
                                <i data-lucide="file-check" class="w-4 h-4 text-amber-500"></i>
                                Berkas Approval Terlampir
                            </span>
                            <button type="button" 
                                    @click="dmsPreviewFile({ 
                                        url: dispatchModalLog.approval_url, 
                                        stream_url: dispatchModalLog.approval_stream,
                                        raw_path: dispatchModalLog.approval_raw,
                                        ext: dispatchModalLog.approval_ext,
                                        name: 'Berkas Approval Penarikan' 
                                    })" 
                                    class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded text-[10px] transition inline-flex items-center gap-1 cursor-pointer">
                                <span>Lihat Berkas</span>
                                <i data-lucide="eye" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </template>

                    <!-- Form Action -->
                    <form :action="'/borrowings/' + dispatchModalLog.id + '/dispatch'" method="POST" class="pt-2">
                        @csrf
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" @click="dispatchModalLog = null" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl font-black text-xs shadow-lg shadow-purple-500/20 transition flex items-center gap-1.5">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                <span>Disahkan & Keluarkan Berkas</span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </div>
    </div>