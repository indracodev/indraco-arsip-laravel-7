    <!-- DELPHI MODAL: PENGELUARAN BERKAS FISIK GUDANG (DISPATCH) -->
    <div x-show="dispatchModalData" 
         x-transition.opacity
         style="display: none;"
         class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto">
        <div class="bg-white dark:bg-slate-950 border border-purple-500/40 dark:border-purple-600/50 rounded shadow-2xl max-w-xl w-full max-h-[92vh] flex flex-col font-mono text-xs" 
             @click.away="closeBorrowingDispatch()">
            
            <!-- Modal Header -->
            <div class="p-3 bg-gradient-to-r from-purple-900/40 to-slate-900 border-b border-purple-500/30 flex items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="p-1.5 bg-purple-500/20 text-purple-400 border border-purple-500/40 rounded shrink-0">
                        <i data-lucide="package-check" class="w-4 h-4"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-white uppercase tracking-wider truncate flex items-center gap-1.5">
                            <span>PENGELUARAN BERKAS FISIK GUDANG</span>
                        </h3>
                        <p class="text-[11px] text-purple-200/80 font-sans truncate">
                            Validasi pemeriksaan dokumen pengajuan & serah terima fisik box arsip
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeBorrowingDispatch()" class="p-1 text-slate-400 hover:text-white rounded transition">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Form Dispatch -->
            <form :action="dispatchModalData ? dispatchModalData.dispatch_url : '#'" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  @submit="submitting = true" 
                  class="flex flex-col flex-1 min-h-0">
                @csrf

                <!-- Modal Body (Scrollable) -->
                <div class="p-4 space-y-3.5 overflow-y-auto max-h-[calc(92vh-120px)]" x-show="dispatchModalData">
                    
                    <!-- GroupBox 1: Ringkasan Box & Lokasi Fisik -->
                    <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-slate-50 dark:bg-slate-900/60 space-y-2">
                        <legend class="px-1.5 font-bold text-purple-700 dark:text-purple-400 bg-white dark:bg-slate-900 border border-purple-500/40 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="package" class="w-3 h-3 text-purple-400"></i>
                            Informasi Box & Lokasi Fisik
                        </legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase">No. Box Arsip</span>
                                <span class="font-mono font-extrabold text-amber-600 dark:text-amber-400 text-sm block truncate" x-text="dispatchModalData.box_number"></span>
                            </div>
                            <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase">Lokasi Rak Gudang</span>
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm block truncate" x-text="dispatchModalData.location"></span>
                            </div>
                            <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase">Judul Berkas Arsip</span>
                                <span class="font-bold text-slate-900 dark:text-white font-sans truncate block" x-text="dispatchModalData.archive_title"></span>
                            </div>
                            <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase">Pemohon Penarikan</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200 font-sans truncate block" x-text="dispatchModalData.borrower_name + ' (' + dispatchModalData.department_code + ')'"></span>
                            </div>
                        </div>
                    </fieldset>

                    <!-- GroupBox 2: Pemeriksaan Berkas Pengajuan Penarikan -->
                    <fieldset class="border border-amber-500/40 dark:border-amber-500/30 p-3 rounded bg-amber-500/5 dark:bg-amber-950/20 space-y-3">
                        <legend class="px-1.5 font-bold text-amber-700 dark:text-amber-400 bg-white dark:bg-slate-900 border border-amber-500/40 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="file-search" class="w-3 h-3 text-amber-500"></i>
                            Pemeriksaan Dokumen Pengajuan
                        </legend>

                        <!-- Panel Dokumen Berkas -->
                        <template x-if="dispatchModalData.has_files">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between p-2.5 bg-white dark:bg-slate-900 border border-amber-500/30 rounded">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="file-check-2" class="w-4 h-4 text-emerald-500 shrink-0"></i>
                                        <div>
                                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">Form / Surat Permohonan Penarikan</span>
                                            <span class="text-[10px] text-slate-400" x-text="(dispatchModalData.files ? dispatchModalData.files.length : 0) + ' berkas digital dilampirkan pemohon'"></span>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            @click="openBorrowingFilePreview(dispatchModalData.id)"
                                            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer shrink-0">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>Buka & Periksa Berkas</span>
                                    </button>
                                </div>

                                <!-- List Berkas Terlampir -->
                                <div class="space-y-1">
                                    <template x-for="(file, idx) in (dispatchModalData.files || [])" :key="idx">
                                        <div class="flex items-center justify-between px-2.5 py-1.5 bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded text-[11px]">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                                                <span class="truncate text-slate-800 dark:text-slate-200 font-sans" x-text="file.name"></span>
                                                <span class="text-[10px] text-slate-400 shrink-0 font-mono" x-text="file.size"></span>
                                            </div>
                                            <button type="button" 
                                                    @click="window.dmsPreviewFile({ files: dispatchModalData.files, initialIndex: idx })"
                                                    class="px-2 py-0.5 bg-slate-100 hover:bg-amber-500 hover:text-white dark:bg-slate-800 dark:hover:bg-amber-600 text-amber-600 dark:text-amber-400 rounded text-[10px] font-bold transition">
                                                Lihat
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="!dispatchModalData.has_files">
                            <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded text-rose-400 text-xs flex items-center gap-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-500 shrink-0"></i>
                                <span>Tidak ada berkas digital formulir permohonan yang dilampirkan pada sistem.</span>
                            </div>
                        </template>

                        <!-- Ketentuan & Checkbox Validasi -->
                        <div class="p-2.5 bg-amber-500/10 dark:bg-amber-950/40 border border-amber-500/30 rounded space-y-2">
                            <p class="text-[11px] text-slate-700 dark:text-slate-300 font-sans leading-relaxed">
                                <strong class="text-amber-700 dark:text-amber-400">Instruksi:</strong> PIC Gudang wajib memeriksa keabsahan form/surat permohonan penarikan berkas pemohon sebelum fisik box arsip diserahkan.
                            </p>
                            <label class="flex items-start gap-2.5 cursor-pointer font-sans select-none pt-1 border-t border-amber-500/20">
                                <input type="checkbox" required name="confirm_file_checked" class="mt-0.5 w-4 h-4 rounded border-amber-500 text-purple-600 focus:ring-purple-500 focus:ring-offset-slate-900 cursor-pointer shrink-0">
                                <span class="text-xs font-bold text-slate-900 dark:text-amber-200 leading-snug">
                                    Saya telah memeriksa dan memvalidasi keabsahan berkas form pengajuan ini untuk dikeluarkan dari rak gudang.
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <!-- GroupBox 3: Bukti Fisik Tambahan (Opsional) -->
                    <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-slate-50 dark:bg-slate-900/60 space-y-2">
                        <legend class="px-1.5 font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[10px] uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="upload" class="w-3 h-3 text-cyan-500"></i>
                            Upload Scan Bukti Tanda Terima Fisik (Opsional)
                        </legend>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans leading-relaxed">
                            Bagian ini hanya diisi jika ada berkas tanda terima fisik terpisah dengan tanda tangan basah saat penyerahan box.
                        </p>
                        <input type="file" 
                               name="scan_approval_borrow" 
                               accept=".pdf,.jpg,.jpeg,.png" 
                               class="block w-full text-xs text-slate-700 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-purple-600 file:text-white hover:file:bg-purple-500 file:cursor-pointer bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded p-1">
                    </fieldset>
                </div>

                <!-- Modal Footer Ribbon -->
                <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between gap-2 shrink-0">
                    <button type="button" 
                            @click="const curId = dispatchModalData ? dispatchModalData.id : null; closeBorrowingDispatch(); if (curId) openBorrowingRejectModal(curId);" 
                            class="px-3 py-1.5 bg-rose-500/10 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-500/40 rounded text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="ban" class="w-3.5 h-3.5"></i>
                        <span>Tolak Pengajuan</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="closeBorrowingDispatch()" 
                                class="px-3.5 py-1.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-bold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="submitting" 
                                class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 text-white border border-purple-700 rounded text-xs font-bold transition flex items-center gap-1.5 shadow-sm disabled:opacity-50 cursor-pointer">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <i data-lucide="package-check" class="w-3.5 h-3.5" x-show="!submitting"></i>
                            <span>Keluarkan Arsip</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
