    <!-- MODAL VERIFIKASI PENGAJUAN ARSIP & GENERATE NOMOR BOX (PIC GUDANG) -->
    <div x-show="verifyModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm overflow-y-auto"
         style="display: none; z-index: 9996 !important;"
         @keydown.escape.window="if(!verifyingArchive) closeVerifyModal()">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 font-mono text-xs my-auto"
             @click.away="if(!verifyingArchive) closeVerifyModal()">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-xl">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                            Verifikasi Pengajuan Box Arsip
                        </h3>
                        <p class="text-[11px] text-slate-500">Persetujuan PIC Gudang & generate Nomor Box resmi.</p>
                    </div>
                </div>
                <button type="button" @click="closeVerifyModal()" :disabled="verifyingArchive" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <template x-if="verifyModalData">
                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Departemen:</span>
                            <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/30 font-bold" x-text="verifyModalData.department_code + ' - ' + verifyModalData.department"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Pengaju:</span>
                            <span class="font-bold text-slate-900 dark:text-white" x-text="verifyModalData.creator"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Periode Arsip:</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400" x-text="verifyModalData.periode || '-'"></span>
                        </div>
                        <div class="pt-1 border-t border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block mb-0.5">Judul Berkas:</span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs" x-text="verifyModalData.title"></h4>
                        </div>
                    </div>

                    <!-- Status Berkas Lampiran / Scan -->
                    <div class="p-3 rounded-xl border" :class="verifyModalData.has_files ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-900 dark:text-emerald-200' : 'bg-rose-500/10 border-rose-500/30 text-rose-900 dark:text-rose-200'">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-[11px] flex items-center gap-1.5">
                                <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                <span>Kelengkapan Berkas Scan:</span>
                            </span>
                            <template x-if="verifyModalData.has_files">
                                <span class="px-2 py-0.5 rounded bg-emerald-600 text-white font-black text-[10px]" x-text="verifyModalData.files.length + ' Berkas Terlampir'"></span>
                            </template>
                            <template x-if="!verifyModalData.has_files">
                                <span class="px-2 py-0.5 rounded bg-rose-600 text-white font-black text-[10px]">Belum Ada Berkas</span>
                            </template>
                        </div>

                        <!-- Daftar Berkas Lampiran dengan Tombol Preview Langsung -->
                        <template x-if="verifyModalData.has_files && verifyModalData.files.length > 0">
                            <div class="space-y-1.5 pt-1.5 border-t border-emerald-500/20">
                                <template x-for="(f, fIdx) in verifyModalData.files" :key="fIdx">
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-white/80 dark:bg-slate-900/80 border border-emerald-500/30 gap-2">
                                        <div class="min-w-0 flex items-center gap-2">
                                            <span class="p-1 rounded bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 shrink-0">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                            </span>
                                            <div class="truncate">
                                                <span class="font-bold text-xs text-slate-900 dark:text-white block truncate" x-text="f.name"></span>
                                                <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate" x-text="(f.category || '') + ' (' + (f.ext ? f.ext.toUpperCase() : 'FILE') + ')'"></span>
                                            </div>
                                        </div>
                                        <button type="button"
                                                @click="dmsPreviewFile({
                                                    box_number: verifyModalData.box_number || '',
                                                    title: verifyModalData.title,
                                                    department: verifyModalData.department_code,
                                                    files: verifyModalData.files,
                                                    raw_path: f.raw_path,
                                                    url: f.url,
                                                    stream_url: f.stream_url,
                                                    name: f.name,
                                                    ext: f.ext
                                                })"
                                                class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer shrink-0 shadow-xs">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>Lihat Berkas</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Rejection Form Input (If rejecting) -->
                    <div x-show="showRejectInput" x-transition class="space-y-1.5 p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl">
                        <label class="block text-[11px] font-bold text-rose-700 dark:text-rose-300 uppercase">Alasan / Catatan Penolakan <span class="text-rose-500">*</span></label>
                        <textarea x-model="rejectionNote" rows="2" placeholder="Tuliskan alasan penolakan untuk PIC Departemen..." class="w-full p-2 bg-white dark:bg-slate-900 border border-rose-400 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-600"></textarea>
                    </div>

                    <!-- Notice -->
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed font-sans">
                        Persetujuan verifikasi akan secara otomatis men-generate <strong>Nomor Box Arsip</strong> resmi dan mengubah status menjadi <strong>Approved / Booked</strong>.
                    </p>

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeVerifyModal()" :disabled="verifyingArchive" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                            Batal
                        </button>

                        <template x-if="!showRejectInput">
                            <button type="button" @click="showRejectInput = true" :disabled="verifyingArchive" class="px-3.5 py-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded-xl font-bold text-xs transition flex items-center gap-1.5">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                <span>Tolak & Revisi</span>
                            </button>
                        </template>

                        <template x-if="showRejectInput">
                            <button type="button" @click="submitVerification('reject')" :disabled="verifyingArchive || !rejectionNote.trim()" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl font-bold text-xs shadow-md transition flex items-center gap-1.5 disabled:opacity-50">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>Kirim Penolakan</span>
                            </button>
                        </template>

                        <button type="button" 
                                @click="submitVerification('approve')" 
                                :disabled="verifyingArchive"
                                class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-black text-xs shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                            <template x-if="verifyingArchive">
                                <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            </template>
                            <template x-if="!verifyingArchive">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                            </template>
                            <span x-text="verifyingArchive ? 'Memverifikasi...' : 'Setujui & Generate Box Code'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
