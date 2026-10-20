    <!-- ========================================================================= -->
    <!-- MODAL EKSEKUSI FISIK & PENERBITAN BAP (KHUSUS PIC GUDANG / SUPERADMIN) -->
    <!-- ========================================================================= -->
    @if(auth()->check() && (auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()))
    <div x-show="completeModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs font-mono"
         @keydown.escape.window="closeCompleteModal()">
        <div @click.away="closeCompleteModal()" 
             class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-lg shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto space-y-4 p-5">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Eksekusi Pemusnahan & Penerbitan BAP Resmi
                        </h3>
                        <p class="text-[10px] text-slate-500 font-sans">
                            Tahap 3: Gudang mengeksekusi fisik pemusnahan berkas, menerbitkan nomor BAP resmi, dan mengosongkan rak.
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeCompleteModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Target Berkas Info Card -->
            <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500 uppercase">NO. BOX ARSIP</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400" x-text="selectedLogToComplete?.box_number || '-'"></span>
                </div>
                <div>
                    <span class="font-sans font-bold text-slate-800 dark:text-slate-200 block truncate" x-text="selectedLogToComplete?.archive_title"></span>
                    <span class="text-[10px] text-slate-500" x-text="'Dept: ' + (selectedLogToComplete?.department_code || '-')"></span>
                </div>
                <template x-if="selectedLogToComplete && selectedLogToComplete.approval_file">
                    <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-[11px] text-slate-600 dark:text-slate-300 flex items-center gap-1 font-bold">
                            <i data-lucide="file-check" class="w-3.5 h-3.5 text-rose-500"></i>
                            <span>Surat Usulan / Persetujuan PIC Dept:</span>
                        </span>
                        <button type="button" 
                                @click="dmsPreviewFile({
                                    url: selectedLogToComplete.approval_file,
                                    stream_url: selectedLogToComplete.approval_file_stream,
                                    raw_path: selectedLogToComplete.approval_file_raw,
                                    name: 'Approval Pemusnahan - ' + (selectedLogToComplete.bap_number || selectedLogToComplete.archive_title),
                                    ext: (selectedLogToComplete.approval_file_raw || selectedLogToComplete.approval_file || '').split('.').pop().toLowerCase()
                                })"
                                class="px-2.5 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white font-bold text-[10px] inline-flex items-center gap-1 cursor-pointer transition shadow-xs">
                            <i data-lucide="eye" class="w-3 h-3"></i>
                            <span>Pratinjau Surat Usulan</span>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Form Eksekusi BAP -->
            <form :action="selectedLogToComplete ? '{{ url('/destructions') }}/' + selectedLogToComplete.id + '/complete' : '#'" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  class="space-y-3.5 text-xs">
                @csrf

                <!-- 1. Nomor BAP Resmi -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        NOMOR BERITA ACARA (BAP) RESMI <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="bap_number" 
                           x-model="completeBapNumber" 
                           required 
                           placeholder="Contoh: BAP/IND/2026/00012"
                           class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold text-rose-600 dark:text-rose-400 focus:outline-none focus:border-rose-500 transition">
                    <span class="text-[10px] text-slate-400 block mt-0.5 font-sans">
                        Nomor resmi Berita Acara Pemusnahan yang diterbitkan oleh bagian Gudang / Kearsipan.
                    </span>
                </div>

                <!-- 2. Tanggal Pelaksanaan Pemusnahan -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        TANGGAL EKSEKUSI PEMUSNAHAN FISIK <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" 
                           name="destruction_date" 
                           x-model="completeDestructionDate" 
                           required 
                           class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- 3. Upload Sertifikat / Berkas Fisik BAP (Maks 500 KB) -->
                <div>
                    <x-file-uploader 
                        name="certificate_file" 
                        id="complete_certificate_file" 
                        label="Upload Berkas / Sertifikat BAP Fisik" 
                        :required="false" 
                        badge="OPSIONAL" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        :maxSizeKB="500"
                        :maxSizeMB="0.5"
                        helperText="Scan surat berita acara fisik bertandatangan (Format: PDF, JPG, PNG Maks. 500 KB)."
                    />
                </div>

                <!-- 4. Catatan Tambahan -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        CATATAN PELAKSANAAN (OPSIONAL)
                    </label>
                    <textarea name="notes" 
                              x-model="completeNotes" 
                              rows="2" 
                              placeholder="Catatan pelaksanaan pemusnahan fisik, saksi, atau vendor pelaksana..."
                              class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-rose-500 transition"></textarea>
                </div>

                <!-- Alert Notice Slot Rak Dikosongkan -->
                <div class="p-2.5 bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 rounded text-[11px] flex items-start gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
                    <div>
                        <strong class="font-bold uppercase">Perhatian:</strong>
                        Pengesahan ini akan mengubah status arsip menjadi <strong class="underline">Telah Dimusnahkan</strong>, mengosongkan slot rak penyimpanan gudang, dan mengaktifkan cetak BAP resmi.
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="closeCompleteModal()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded shadow transition flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Sahkan Pemusnahan & Terbitkan BAP</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL UPLOAD BAP SUSULAN (PASCA EKSEKUSI PEMUSNAHAN) -->
    <div x-show="uploadBapModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs font-mono"
         @keydown.escape.window="closeUploadBapModal()">
        <div @click.away="closeUploadBapModal()" 
             class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-lg shadow-2xl w-full max-w-lg space-y-4 p-5">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30 rounded">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Upload Berita Acara Pemusnahan (BAP)
                        </h3>
                        <p class="text-[10px] text-slate-500 font-sans">
                            Unggah scan fisik dokumen BAP resmi bertandatangan agar departemen dapat mencetak dan mengunduh berkas.
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeUploadBapModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500">NO. BAP:</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400" x-text="selectedLogToUploadBap?.bap_number || '-'"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500">BERKAS ARSIP:</span>
                    <span class="font-bold text-slate-800 dark:text-white truncate max-w-[280px]" x-text="selectedLogToUploadBap?.archive_title || '-'"></span>
                </div>
            </div>

            <form :action="selectedLogToUploadBap ? '{{ url('/destructions') }}/' + selectedLogToUploadBap.id + '/upload-bap' : '#'" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  class="space-y-3.5 text-xs">
                @csrf

                <div>
                    <x-file-uploader 
                        name="certificate_file" 
                        id="post_certificate_file" 
                        label="Pilih Berkas Scan BAP Resmi Bertandatangan" 
                        :required="true" 
                        badge="WAJIB" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        :maxSizeKB="500"
                        :maxSizeMB="0.5"
                        helperText="Scan surat berita acara fisik bertandatangan (Format: PDF, JPG, PNG Maks. 500 KB)."
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="closeUploadBapModal()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded shadow transition flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Simpan & Upload BAP</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TOLAK PENGAJUAN PERPANJANGAN & PEMUSNAHAN (GUDANG / SUPERADMIN) -->
    <div x-show="rejectModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs font-mono"
         @keydown.escape.window="closeRejectModal()">
        <div @click.away="closeRejectModal()" 
             class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-lg shadow-2xl w-full max-w-md space-y-4 p-5">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 rounded">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider" 
                            x-text="rejectType === 'destruction' ? 'Tolak Pengajuan Pemusnahan' : 'Tolak Pengajuan Perpanjangan'">
                        </h3>
                        <p class="text-[10px] text-slate-500 font-sans" 
                            x-text="rejectType === 'destruction' ? 'Berikan alasan penolakan usulan pemusnahan berkas arsip.' : 'Berikan alasan penolakan perpanjangan masa retensi berkas arsip.'">
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeRejectModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500">NO. BOX:</span>
                    <span class="font-bold text-purple-600 dark:text-purple-400" x-text="selectedLogToReject?.box_number || '-'"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500">BERKAS ARSIP:</span>
                    <span class="font-bold text-slate-800 dark:text-white truncate max-w-[240px]" x-text="selectedLogToReject?.archive_title || '-'"></span>
                </div>
            </div>

            <form :action="selectedLogToReject ? (rejectType === 'destruction' ? '{{ url('/destructions') }}/' + selectedLogToReject.id + '/reject' : '{{ url('/destructions/extensions') }}/' + selectedLogToReject.id + '/reject') : '#'" 
                  method="POST" 
                  class="space-y-3.5 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        ALASAN PENOLAKAN <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="rejection_note" 
                              x-model="rejectReason" 
                              rows="3" 
                              required
                              :placeholder="rejectType === 'destruction' ? 'Jelaskan alasan pengajuan pemusnahan ditolak...' : 'Jelaskan alasan pengajuan perpanjangan ditolak...'"
                              class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-rose-500 transition"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="closeRejectModal()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded shadow transition flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>Tolak Pengajuan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
