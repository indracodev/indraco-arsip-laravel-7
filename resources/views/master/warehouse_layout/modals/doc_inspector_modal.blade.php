    <!-- Modal 1: Document Detail Inspector -->
    <div x-show="docModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 font-mono">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-5 max-w-lg w-full space-y-3 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                    <i data-lucide="file-text" class="w-4 h-4"></i> Detail Berkas Dokumen
                </span>
                <button @click="docModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-2.5 text-xs">
                <div>
                    <span class="text-slate-500 text-[10px] uppercase font-bold block mb-0.5">Judul Dokumen:</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white font-sans" x-text="selectedDoc?.title"></h3>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 text-[10px] uppercase font-bold block">Kode Box:</span>
                        <span class="font-mono font-bold text-amber-600 dark:text-amber-400 text-xs" x-text="selectedDoc?.box_number || 'DRAFT'"></span>
                    </div>

                    <div class="p-2 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 text-[10px] uppercase font-bold block">Perusahaan:</span>
                        <span class="font-bold text-slate-900 dark:text-white text-xs" x-text="selectedDoc?.company_name"></span>
                    </div>

                    <div class="p-2 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 text-[10px] uppercase font-bold block">Jenis Dokumen:</span>
                        <span class="font-bold text-slate-900 dark:text-white text-xs" x-text="selectedDoc?.document_type"></span>
                    </div>

                    <div class="p-2 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 text-[10px] uppercase font-bold block">Periode (YY-MM):</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400 text-xs" x-text="selectedDoc?.period_yy_mm"></span>
                    </div>
                </div>

                <div class="p-2 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Masa Simpan Expiry:</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400 text-xs" x-text="(selectedDoc?.formatted_expiry_date || formatDate(selectedDoc?.retention_expiry_date)) + ' (' + (selectedDoc?.retention_display || (selectedDoc?.retention_years ? selectedDoc.retention_years + ' Thn' : '5 Thn')) + ')'"></span>
                </div>

                <!-- Scan Buttons -->
                <div class="flex items-center gap-2 pt-1">
                    <template x-if="selectedDoc?.scan_input_form">
                        <button type="button" 
                                @click="dmsPreviewFile({
                                    url: selectedDoc.scan_input_form,
                                    stream_url: selectedDoc.scan_input_form_stream,
                                    raw_path: selectedDoc.scan_input_form_raw,
                                    name: 'Scan Formulir Input - ' + (selectedDoc.box_number || selectedDoc.title),
                                    ext: (selectedDoc.scan_input_form_raw || selectedDoc.scan_input_form || '').split('.').pop().toLowerCase()
                                })" 
                                class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded border border-amber-600 text-xs shadow-xs cursor-pointer">
                            Scan Form Input
                        </button>
                    </template>
                    <template x-if="selectedDoc?.scan_approval_input">
                        <button type="button" 
                                @click="dmsPreviewFile({
                                    url: selectedDoc.scan_approval_input,
                                    stream_url: selectedDoc.scan_approval_input_stream,
                                    raw_path: selectedDoc.scan_approval_input_raw,
                                    name: 'Scan Approval Input - ' + (selectedDoc.box_number || selectedDoc.title),
                                    ext: (selectedDoc.scan_approval_input_raw || selectedDoc.scan_approval_input || '').split('.').pop().toLowerCase()
                                })" 
                                class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded border border-blue-700 text-xs shadow-xs cursor-pointer">
                            Scan Approval
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-200 dark:border-slate-800">
                <button @click="docModalOpen = false" type="button" class="px-4 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs rounded border border-slate-300 dark:border-slate-700 cursor-pointer">Tutup</button>
            </div>
        </div>
    </div>