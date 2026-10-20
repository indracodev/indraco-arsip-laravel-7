    <!-- DELPHI MODAL: PENOLAKAN PENGAJUAN PENARIKAN BERKAS (REJECT) -->
    <div x-show="rejectModalData" 
         x-transition.opacity
         style="display: none;"
         class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto">
        <div class="bg-white dark:bg-slate-950 border border-rose-500/50 rounded shadow-2xl max-w-lg w-full flex flex-col font-mono text-xs" 
             @click.away="closeBorrowingReject()">
            
            <!-- Modal Header -->
            <div class="p-3 bg-gradient-to-r from-rose-950/60 to-slate-900 border-b border-rose-500/40 flex items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="p-1.5 bg-rose-500/20 text-rose-400 border border-rose-500/40 rounded shrink-0">
                        <i data-lucide="ban" class="w-4 h-4"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-white uppercase tracking-wider truncate flex items-center gap-1.5">
                            <span>PENOLAKAN PENGAJUAN PENARIKAN</span>
                        </h3>
                        <p class="text-[11px] text-rose-200/80 font-sans truncate">
                            Batalkan permohonan penarikan berkas dengan alasan resmi
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeBorrowingReject()" class="p-1 text-slate-400 hover:text-white rounded transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Form Reject -->
            <form :action="rejectModalData ? rejectModalData.reject_url : '#'" 
                  method="POST" 
                  @submit="submitting = true" 
                  class="flex flex-col flex-1 min-h-0">
                @csrf

                <!-- Modal Body -->
                <div class="p-4 space-y-3.5" x-show="rejectModalData">
                    
                    <!-- Ringkasan Box -->
                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded space-y-1 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 uppercase text-[10px] font-bold">No. Box Arsip:</span>
                            <span class="font-bold font-mono text-amber-500 text-xs" x-text="rejectModalData.box_number"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 uppercase text-[10px] font-bold">Judul Berkas:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 font-sans text-xs truncate max-w-[280px]" x-text="rejectModalData.archive_title"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 uppercase text-[10px] font-bold">Pemohon:</span>
                            <span class="text-slate-700 dark:text-slate-300 font-sans text-xs" x-text="rejectModalData.borrower_name + ' (' + rejectModalData.department_code + ')'"></span>
                        </div>
                    </div>

                    <!-- Input Alasan Penolakan -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                            Alasan Penolakan <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                            Jelaskan alasan penolakan secara jelas agar pemohon dapat memperbaiki dokumen formulir atau prosedur permohonan.
                        </p>
                        <textarea 
                            name="reason" 
                            required 
                            rows="3" 
                            placeholder="Contoh: Dokumen formulir permohonan belum ditandatangani Kepala Departemen, atau berkas pengajuan tidak lengkap..." 
                            class="w-full p-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded text-xs text-slate-900 dark:text-white font-sans placeholder-slate-400 resize-none transition"
                        ></textarea>
                    </div>

                    <div class="p-2 bg-rose-500/10 border border-rose-500/30 rounded text-[11px] text-rose-300 font-sans flex items-start gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-400 shrink-0 mt-0.5"></i>
                        <span>Status pengajuan akan diubah menjadi <strong>Ditolak</strong> dan tercatat di jejak audit aktivitas sistem.</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-end gap-2 shrink-0">
                    <button type="button" 
                            @click="closeBorrowingReject()" 
                            class="px-3.5 py-1.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            :disabled="submitting" 
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white border border-rose-700 rounded text-xs font-bold transition flex items-center gap-1.5 shadow-sm disabled:opacity-50 cursor-pointer">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <i data-lucide="ban" class="w-3.5 h-3.5" x-show="!submitting"></i>
                        <span>Konfirmasi Tolak Pengajuan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>