    <!-- DELPHI MODAL: DETAIL PENARIKAN & KONTEN ARSIP -->
    <div x-show="detailModalData" 
         x-transition.opacity
         style="display: none;"
         class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col font-mono text-xs" 
             @click.away="closeBorrowingDetail()">
            
            <!-- Modal Delphi Ribbon Header -->
            <div class="p-3 bg-gradient-to-r from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="p-1 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded shrink-0">
                        <i data-lucide="file-symlink" class="w-4 h-4"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate flex items-center gap-1.5">
                            <span>DETAIL PENARIKAN:</span>
                            <span class="text-amber-600 dark:text-amber-400 font-extrabold" x-text="detailModalData ? detailModalData.box_number : ''"></span>
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans truncate" x-text="detailModalData ? detailModalData.archive_title : ''"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                          :class="{
                              'bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30': detailModalData && detailModalData.status === 'requested',
                              'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30': detailModalData && detailModalData.status === 'dept_approved',
                              'bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/30': detailModalData && detailModalData.status === 'approved',
                              'bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-500/30': detailModalData && detailModalData.status === 'dispatched',
                              'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30': detailModalData && detailModalData.status === 'returned',
                              'bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/30': detailModalData && detailModalData.status === 'rejected'
                          }"
                          x-text="detailModalData ? detailModalData.status_label : ''">
                    </span>

                    <template x-if="detailModalData && detailModalData.has_previous_rejections">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30 flex items-center gap-1 font-mono" title="Box ini memiliki riwayat penolakan sebelumnya">
                            <i data-lucide="history" class="w-3 h-3 text-rose-500"></i>
                            <span>Pernah Ditolak</span>
                        </span>
                    </template>
                    <button type="button" @click="closeBorrowingDetail()" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded transition">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-4 space-y-3 overflow-y-auto max-h-[calc(90vh-110px)]" x-show="detailModalData">
                <!-- GroupBox 1: Informasi Penarikan Berkas -->
                <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-slate-50 dark:bg-slate-900/40 space-y-2">
                    <legend class="px-1.5 font-bold text-emerald-700 dark:text-emerald-400 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="info" class="w-3 h-3 text-emerald-500"></i>
                        Data Pengajuan & Fisik
                    </legend>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Pemohon</span>
                            <span class="font-bold text-slate-900 dark:text-white truncate block" x-text="detailModalData.borrower_name"></span>
                            <span class="text-[10px] text-slate-500 truncate block" x-text="detailModalData.borrower_email"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Departemen Box</span>
                            <span class="font-bold text-slate-900 dark:text-white truncate block" x-text="detailModalData.department + ' (' + detailModalData.department_code + ')'"></span>
                            <span class="text-[10px] text-slate-500 truncate block" x-text="detailModalData.sub_department"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Lokasi Rak Gudang</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 text-[11px] block" x-text="detailModalData.location"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Tgl Diajukan</span>
                            <span class="font-bold text-slate-900 dark:text-white block" x-text="detailModalData.request_date"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Tgl Keluar Fisik</span>
                            <span class="font-bold text-slate-900 dark:text-white block" x-text="detailModalData.borrow_date"></span>
                        </div>
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Estimasi Kembali</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400 block" x-text="detailModalData.expected_return_date"></span>
                        </div>
                    </div>

                    <!-- Keperluan Penarikan -->
                    <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                        <span class="text-[10px] text-slate-400 block font-bold uppercase">Keperluan / Tujuan Penarikan:</span>
                        <p class="font-sans text-xs text-slate-800 dark:text-slate-200 pt-0.5 whitespace-pre-wrap" x-text="detailModalData.purpose"></p>
                    </div>

                    <template x-if="detailModalData.notes && detailModalData.notes !== '-'">
                        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Catatan Tambahan:</span>
                            <p class="font-sans text-xs text-slate-800 dark:text-slate-200 pt-0.5" x-text="detailModalData.notes"></p>
                        </div>
                    </template>
                </fieldset>

                @if(!auth()->user()->isPicGudang())
                <!-- GroupBox 2: Konten / Rincian Butir Dokumen Dalam Box -->
                <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 space-y-2">
                    <legend class="px-1.5 font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="list-checks" class="w-3 h-3 text-emerald-500"></i>
                        <span>Butir Dokumen Dalam Box (<span x-text="detailModalData.items ? detailModalData.items.length : 0"></span> Berkas)</span>
                    </legend>
                    <template x-if="detailModalData.items && detailModalData.items.length > 0">
                        <div class="border border-slate-300 dark:border-slate-800 rounded overflow-hidden">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 dark:bg-slate-900 text-[10px] font-bold uppercase text-slate-600 dark:text-slate-400 border-b border-slate-300 dark:border-slate-700">
                                        <th class="py-1.5 px-2 w-10 text-center border-r border-slate-300 dark:border-slate-700">No</th>
                                        <th class="py-1.5 px-2 border-r border-slate-300 dark:border-slate-700">Nama Dokumen</th>
                                        <th class="py-1.5 px-2 border-r border-slate-300 dark:border-slate-700 w-32">Periode</th>
                                        <th class="py-1.5 px-2">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-[11px]">
                                    <template x-for="item in detailModalData.items" :key="item.number">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/60">
                                            <td class="py-1 px-2 text-center font-bold text-purple-600 dark:text-purple-400 border-r border-slate-200 dark:border-slate-800" x-text="item.number"></td>
                                            <td class="py-1 px-2 font-bold font-sans text-slate-900 dark:text-white border-r border-slate-200 dark:border-slate-800" x-text="item.name"></td>
                                            <td class="py-1 px-2 text-amber-600 dark:text-amber-400 border-r border-slate-200 dark:border-slate-800" x-text="item.period || '-'"></td>
                                            <td class="py-1 px-2 font-sans text-slate-500" x-text="item.notes || '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!detailModalData.items || detailModalData.items.length === 0">
                        <p class="text-slate-400 italic text-[11px] py-1 text-center font-sans">Tidak ada rincian butir dokumen spesifik untuk box ini.</p>
                    </template>
                </fieldset>
                @endif

                <!-- GroupBox 3: Berkas Dokumen & Form Approval -->
                <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 space-y-2">
                    <legend class="px-1.5 font-bold text-blue-700 dark:text-blue-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="paperclip" class="w-3 h-3 text-blue-500"></i>
                        Berkas Terlampir
                    </legend>
                    <template x-if="detailModalData.has_files">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="(file, idx) in detailModalData.files" :key="idx">
                                <div class="p-2 rounded bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 dark:text-white text-xs block truncate" x-text="file.name"></span>
                                        <span class="text-[10px] text-slate-400 block truncate" x-text="file.category + ' (' + file.ext.toUpperCase() + ')'"></span>
                                    </div>
                                    <button type="button" 
                                            @click="dmsPreviewFile({
                                                box_number: detailModalData.box_number,
                                                title: detailModalData.archive_title,
                                                department: detailModalData.department_code,
                                                files: detailModalData.files,
                                                raw_path: file.raw_path,
                                                url: file.url,
                                                stream_url: file.stream_url,
                                                name: file.name,
                                                ext: file.ext
                                            })"
                                            class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[10px] font-bold transition flex items-center gap-1 cursor-pointer shrink-0">
                                        <i data-lucide="eye" class="w-3 h-3"></i>
                                        <span>Lihat</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!detailModalData.has_files">
                        <div class="p-3 text-center text-rose-500 bg-rose-500/10 border border-rose-500/30 rounded text-[11px]">
                            <i data-lucide="alert-circle" class="w-4 h-4 mx-auto mb-1"></i>
                            Berkas surat permohonan / persetujuan belum diunggah.
                        </div>
                    </template>
                </fieldset>

                <!-- GroupBox 4: Audit Trail Persetujuan -->
                <fieldset class="border border-slate-300 dark:border-slate-800 p-2.5 rounded bg-slate-50 dark:bg-slate-900/40 text-[11px] space-y-1">
                    <legend class="px-1.5 font-bold text-cyan-700 dark:text-cyan-400 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="shield-check" class="w-3 h-3 text-cyan-500"></i>
                        Jejak Persetujuan & Penyerahan
                    </legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-0.5">
                        <div class="p-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Approval Dept:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="detailModalData.dept_approver"></span>
                            <span class="text-[10px] text-slate-400 block" x-text="detailModalData.dept_approved_at"></span>
                        </div>
                        <div class="p-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Pengeluaran Gudang:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="detailModalData.pic_gudang"></span>
                            <span class="text-[10px] text-slate-400 block" x-text="detailModalData.borrow_date"></span>
                        </div>
                    </div>
                </fieldset>

                <!-- GroupBox 5: Alasan Penolakan (Jika status rejected) -->
                <template x-if="detailModalData.status === 'rejected'">
                    <fieldset class="border border-rose-500/40 p-2.5 rounded bg-rose-500/10 text-[11px] space-y-1 font-sans">
                        <legend class="px-1.5 font-bold text-rose-600 dark:text-rose-400 bg-white dark:bg-slate-900 border border-rose-500/40 rounded text-[10px] uppercase tracking-wider flex items-center gap-1 font-mono">
                            <i data-lucide="ban" class="w-3 h-3 text-rose-500"></i>
                            Catatan Penolakan Pengajuan
                        </legend>
                        <p class="text-rose-700 dark:text-rose-300 font-semibold" x-text="detailModalData.approval_notes || 'Pengajuan penarikan berkas ditolak oleh petugas.'"></p>
                    </fieldset>
                </template>

                <!-- GroupBox 6: Log Riwayat Penolakan Sebelumnya -->
                <template x-if="detailModalData.rejection_history && detailModalData.rejection_history.length > 0">
                    <fieldset class="border border-rose-500/30 p-2.5 rounded bg-rose-500/5 text-[11px] space-y-2 font-sans">
                        <legend class="px-1.5 font-bold text-rose-600 dark:text-rose-400 bg-white dark:bg-slate-900 border border-rose-500/30 rounded text-[10px] uppercase tracking-wider flex items-center gap-1 font-mono">
                            <i data-lucide="history" class="w-3 h-3 text-rose-500"></i>
                            <span>Log Riwayat Penolakan (<span x-text="detailModalData.rejection_history.length"></span>x Pernah Ditolak)</span>
                        </legend>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Riwayat catatan dan alasan penolakan pada permohonan penarikan sebelumnya untuk berkas ini:
                        </p>
                        <div class="space-y-1.5">
                            <template x-for="(rej, idx) in detailModalData.rejection_history" :key="rej.id">
                                <div class="p-2.5 rounded bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/60 shadow-xs space-y-1">
                                    <div class="flex items-center justify-between text-[10px] font-mono text-slate-500 border-b border-slate-100 dark:border-slate-800 pb-1">
                                        <span class="font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                            <i data-lucide="x-circle" class="w-3 h-3 text-rose-500"></i>
                                            <span>Penolakan #<span x-text="idx + 1"></span></span>
                                        </span>
                                        <span x-text="'Waktu: ' + rej.date"></span>
                                    </div>
                                    <div class="pt-0.5">
                                        <span class="text-[9px] uppercase font-bold text-slate-400 block font-mono">Catatan / Alasan Penolakan:</span>
                                        <div class="text-rose-700 dark:text-rose-300 font-sans font-medium bg-rose-50 dark:bg-rose-950/40 p-1.5 rounded border border-rose-200 dark:border-rose-900/50 text-[11px] mt-0.5 leading-snug" x-text="rej.notes"></div>
                                    </div>
                                    <template x-if="rej.purpose">
                                        <div class="text-[10px] text-slate-500 font-sans pt-0.5">
                                            <span class="font-mono text-slate-400">Keperluan Diajukan:</span> <span x-text="rej.purpose"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </fieldset>
                </template>
            </div>

            <!-- Modal Footer Ribbon -->
            <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between gap-2 shrink-0">
                <a :href="detailModalData ? detailModalData.archive_url : '#'" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold transition flex items-center gap-1">
                    <i data-lucide="folder-open" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span>Buka Detail Master Arsip</span>
                </a>

                <div class="flex items-center gap-2">
                    <button type="button" @click="closeBorrowingDetail()" class="px-3 py-1 bg-slate-300 dark:bg-slate-800 hover:bg-slate-400 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>