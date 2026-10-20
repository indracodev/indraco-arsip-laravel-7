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
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700 text-center">STATUS</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">DOKUMEN & AKSI</th>
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
                                <span class="px-2 py-0.5 rounded bg-purple-500/10 border border-purple-500/30 text-[10px]" x-text="ext.duration_label || ('+' + (ext.additional_months ? ext.additional_months + ' Bulan' : ext.additional_years + ' Tahun'))"></span>
                            </td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap" x-text="ext.new_expiry_date"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 max-w-xs truncate font-sans text-slate-600 dark:text-slate-400 text-[11px]" :title="ext.reason" x-text="ext.reason"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 whitespace-nowrap text-[11px]" x-text="ext.user_name"></td>
                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border" :class="ext.status_badge || 'bg-slate-100 text-slate-800 border-slate-300'" x-text="ext.status_label || 'Disetujui'"></span>
                                <template x-if="ext.rejection_note">
                                    <p class="text-[9px] text-rose-500 max-w-[120px] truncate mt-0.5" :title="ext.rejection_note" x-text="'Alasan: ' + ext.rejection_note"></p>
                                </template>
                            </td>
                            <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
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

                                    @if(auth()->check() && (auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()))
                                        <template x-if="ext.can_approve">
                                            <div class="inline-flex items-center gap-1">
                                                <form :action="'{{ url('/destructions/extensions') }}/' + ext.id + '/approve'" method="POST" class="inline">
                                                    @csrf
                                                    <button type="button" 
                                                            @click="async () => { const ok = window.showConfirmModal ? await window.showConfirmModal({ title: 'ACC Perpanjangan', message: 'Setujui perpanjangan masa retensi (' + (ext.duration_label || 'perpanjangan') + ') untuk Box ' + (ext.box_number || ext.archive_title) + '?', type: 'primary', confirmText: 'Ya, Setujui ACC' }) : true; if (ok) $el.closest('form').submit(); }"
                                                            class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] border border-emerald-700 shadow-sm inline-flex items-center gap-1 cursor-pointer" 
                                                            title="ACC / Setujui perpanjangan masa retensi berkas">
                                                        <i data-lucide="check" class="w-3 h-3"></i>
                                                        <span>ACC</span>
                                                    </button>
                                                </form>
                                                <button type="button" 
                                                        @click="openRejectModal(ext)"
                                                        class="px-2 py-0.5 rounded bg-rose-600 hover:bg-rose-500 text-white font-bold text-[10px] border border-rose-700 shadow-sm inline-flex items-center gap-1 cursor-pointer" 
                                                        title="Tolak perpanjangan masa retensi berkas">
                                                     <i data-lucide="x" class="w-3 h-3"></i>
                                                    <span>Tolak</span>
                                                </button>
                                            </div>
                                        </template>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- EMPTY STATE JIKA BELUM ADA LOG PERPANJANGAN -->
                    <tr x-show="filteredExtLogs.length === 0">
                        <td colspan="9" class="py-8 px-4 text-center text-slate-500 dark:text-slate-400">
                            <i data-lucide="clock" class="w-8 h-8 text-purple-400 mx-auto mb-2 opacity-60"></i>
                            <div class="font-bold text-slate-800 dark:text-slate-200">Belum Ada Riwayat Perpanjangan</div>
                            <p class="text-[11px] text-slate-400 mt-0.5">Belum ada berkas arsip yang diajukan atau disetujui perpanjangan masa simpannya.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
