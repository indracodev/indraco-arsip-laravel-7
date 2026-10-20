    <!-- MODAL SUPER ADMIN UBAH STATUS WORKFLOW BEBAS -->
    <div x-show="superAdminStatusModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm overflow-y-auto"
         style="display: none; z-index: 9998 !important;"
         @keydown.escape.window="if(!updatingStatus) closeSuperAdminStatusModal()">
        
        <div class="bg-white dark:bg-slate-900 border-2 border-amber-500/60 dark:border-amber-500/40 rounded-2xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-4 font-mono text-xs my-auto"
             @click.away="if(!updatingStatus) closeSuperAdminStatusModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-gradient-to-br from-amber-500 to-amber-600 text-slate-950 rounded-xl font-bold shadow">
                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                Super Admin: Ubah Status Workflow
                            </h3>
                            <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-700 dark:text-amber-400 text-[10px] font-black border border-amber-500/30">
                                Global Override
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500">Pilih status tujuan untuk memindahkan alur berkas secara bebas.</p>
                    </div>
                </div>
                <button type="button" @click="closeSuperAdminStatusModal()" :disabled="updatingStatus" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <template x-if="superAdminStatusData">
                <div class="space-y-4">
                    <!-- Detail Berkas Info Card -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">No. Box Arsip:</span>
                            <span class="font-black text-amber-600 dark:text-amber-400" x-text="superAdminStatusData.box_number || 'Pending'"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Departemen:</span>
                            <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold" x-text="superAdminStatusData.department"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Status Saat Ini:</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black border" 
                                  :class="{
                                      'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300': superAdminStatusData.status === 'draft',
                                      'bg-amber-500/20 text-amber-800 dark:text-amber-300 border-amber-500/40': superAdminStatusData.status === 'pending_verification',
                                      'bg-blue-500/20 text-blue-800 dark:text-blue-300 border-blue-500/40': superAdminStatusData.status === 'approved_booked',
                                      'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border-emerald-500/40': superAdminStatusData.status === 'in_warehouse',
                                      'bg-purple-500/20 text-purple-800 dark:text-purple-300 border-purple-500/40': superAdminStatusData.status === 'borrowed' || superAdminStatusData.status === 'taken',
                                      'bg-rose-500/20 text-rose-800 dark:text-rose-300 border-rose-500/40': superAdminStatusData.status === 'destroyed'
                                  }"
                                  x-text="superAdminStatusData.status_label || superAdminStatusData.status">
                            </span>
                        </div>
                        <div class="pt-1 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <span class="text-[10px] text-slate-400 uppercase font-bold block mb-0.5">Judul Dokumen:</span>
                                <h4 class="font-bold text-slate-900 dark:text-white text-xs truncate" x-text="superAdminStatusData.title"></h4>
                            </div>
                            <template x-if="superAdminStatusData.files && superAdminStatusData.files.length > 0">
                                <button type="button"
                                        @click="dmsPreviewFile({
                                            box_number: superAdminStatusData.box_number,
                                            title: superAdminStatusData.title,
                                            department: superAdminStatusData.department_code,
                                            files: superAdminStatusData.files
                                        })"
                                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer shrink-0 shadow-xs">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Pratinjau Berkas</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Target Status Selection (List of Options) -->
                    <div class="space-y-2">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Pilih Status Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <!-- Draft -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'draft' ? 'bg-slate-200/80 dark:bg-slate-800 border-slate-500 ring-2 ring-slate-400/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-slate-400'">
                                <input type="radio" name="dash_super_status_opt" value="draft" x-model="targetStatusChoice" class="mt-0.5 text-slate-700">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <i data-lucide="file-edit" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span>Draft (Kembalikan ke Draft)</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Wajib isi alasan. PIC Dept dapat merevisi. Slot rak dibebaskan.</p>
                                </div>
                            </label>

                            <!-- Antrean Verifikasi -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'pending_verification' ? 'bg-amber-500/20 border-amber-500 ring-2 ring-amber-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-amber-400'">
                                <input type="radio" name="dash_super_status_opt" value="pending_verification" x-model="targetStatusChoice" class="mt-0.5 text-amber-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-amber-700 dark:text-amber-300 flex items-center gap-1.5">
                                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-500"></i>
                                        <span>Antrean Verifikasi</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Menunggu verifikasi PIC Gudang. Lengkapi berkas persyaratan.</p>
                                </div>
                            </label>

                            <!-- Tersimpan di Gudang -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'in_warehouse' ? 'bg-emerald-500/20 border-emerald-500 ring-2 ring-emerald-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-emerald-400'">
                                <input type="radio" name="dash_super_status_opt" value="in_warehouse" x-model="targetStatusChoice" class="mt-0.5 text-emerald-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                                        <i data-lucide="archive" class="w-3.5 h-3.5 text-emerald-500"></i>
                                        <span>Tersimpan di Gudang</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Status aktif gudang. No. Box & berkas serah terima.</p>
                                </div>
                            </label>

                            <!-- Out / Keluar -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'taken' ? 'bg-purple-500/20 border-purple-500 ring-2 ring-purple-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-purple-400'">
                                <input type="radio" name="dash_super_status_opt" value="taken" x-model="targetStatusChoice" class="mt-0.5 text-purple-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-purple-500"></i>
                                        <span>Keluar (Out)</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Berkas keluar dari gudang. Slot rak gudang dibebaskan.</p>
                                </div>
                            </label>

                            <!-- Antrean Pemusnahan -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'pending_destruction' ? 'bg-orange-500/20 border-orange-500 ring-2 ring-orange-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-orange-400'">
                                <input type="radio" name="dash_super_status_opt" value="pending_destruction" x-model="targetStatusChoice" class="mt-0.5 text-orange-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-orange-700 dark:text-orange-300 flex items-center gap-1.5">
                                        <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-orange-500"></i>
                                        <span>Antrean Pemusnahan</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Usulan/antrean pemusnahan berkas sebelum eksekusi fisik BAP.</p>
                                </div>
                            </label>

                            <!-- Dimusnahkan -->
                            <label class="p-2.5 rounded-xl border cursor-pointer transition flex items-start gap-2.5"
                                   :class="targetStatusChoice === 'destroyed' ? 'bg-rose-500/20 border-rose-500 ring-2 ring-rose-500/40 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-rose-400'">
                                <input type="radio" name="dash_super_status_opt" value="destroyed" x-model="targetStatusChoice" class="mt-0.5 text-rose-600">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-rose-700 dark:text-rose-300 flex items-center gap-1.5">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                        <span>Dimusnahkan (BAP)</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-tight mt-0.5">Pemusnahan permanen dengan Berita Acara (BAP). Slot rak dibebaskan.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- DYNAMIC SUB-FORMS BERDASARKAN STATUS YANG DIPILIH -->

                    <!-- 1. FORM MODE: DRAFT -->
                    <template x-if="targetStatusChoice === 'draft'">
                        <div class="space-y-2 p-3.5 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                            <div class="flex items-start gap-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                                <div class="text-[11px] text-amber-900 dark:text-amber-200 leading-tight">
                                    <strong class="font-bold">Pengembalian ke Status Draft:</strong>
                                    <p class="mt-0.5 text-slate-600 dark:text-slate-400">Berkas akan dikembalikan statusnya ke Draft Usulan sehingga PIC Departemen terkait dapat melakukan revisi judul, butir berkas, maupun dokumen. Alokasi slot rak gudang (jika ada) akan otomatis dibebaskan.</p>
                                </div>
                            </div>
                            <div class="space-y-1 pt-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Alasan / Keterangan Pengembalian ke Draft <span class="text-rose-500">* (Wajib Diisi)</span>
                                </label>
                                <textarea x-model="superAdminStatusReason" rows="2" placeholder="Tuliskan alasan mengapa berkas dikembalikan ke draft (misal: dokumen perlu revisi rincian butir arsip)..." class="w-full p-2 bg-white dark:bg-slate-900 border border-amber-400 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-600"></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- 2. FORM MODE: ANTREAN VERIFIKASI -->
                    <template x-if="targetStatusChoice === 'pending_verification'">
                        <div class="space-y-2.5 p-3.5 bg-amber-500/5 border border-amber-500/20 rounded-xl">
                            <div class="flex items-center justify-between border-b border-amber-500/20 pb-1.5">
                                <span class="text-[11px] font-bold text-amber-800 dark:text-amber-300 uppercase flex items-center gap-1.5">
                                    <i data-lucide="files" class="w-3.5 h-3.5"></i> Kelengkapan Berkas Antrean Verifikasi (2 Berkas)
                                </span>
                                <span class="text-[10px] text-slate-500">Upload berkas baru jika diperlukan</span>
                            </div>
                            <div class="space-y-2">
                                <!-- File 1: Scan Formulir Input Fisik -->
                                <div class="p-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">1. Scan Formulir Input Fisik</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.2 rounded text-[10px] font-bold" :class="superAdminStatusData.has_scan_input ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/20 text-rose-700 dark:text-rose-300'" x-text="superAdminStatusData.has_scan_input ? 'Sudah Ada' : 'Belum Ada'"></span>
                                            <template x-if="superAdminStatusData.has_scan_input">
                                                <button type="button"
                                                        @click="const sf = (superAdminStatusData.files || []).find(f => f.name && (f.name.includes('Formulir') || f.category === 'Formulir Pendaftaran Fisik')); dmsPreviewFile(sf || {
                                                            url: superAdminStatusData.scan_input_form_url,
                                                            name: 'Scan Formulir Input - ' + superAdminStatusData.title,
                                                            box_number: superAdminStatusData.box_number
                                                        })"
                                                        class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] inline-flex items-center gap-1 cursor-pointer transition shadow-2xs">
                                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                                    <span>Lihat</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <input type="file" id="sa_scan_input_form" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-[10px] font-mono text-slate-600 dark:text-slate-400 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-amber-500/20 file:text-amber-800 dark:file:text-amber-200 hover:file:bg-amber-500/30">
                                </div>

                                <!-- File 2: Lampiran Softcopy Dokumen -->
                                <div class="p-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">2. Lampiran Digital Dokumen</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.2 rounded text-[10px] font-bold" :class="superAdminStatusData.has_file_path ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/20 text-rose-700 dark:text-rose-300'" x-text="superAdminStatusData.has_file_path ? 'Sudah Ada' : 'Belum Ada'"></span>
                                            <template x-if="superAdminStatusData.has_file_path">
                                                <button type="button"
                                                        @click="const df = (superAdminStatusData.files || []).find(f => f.name && (f.name.includes('Digital') || f.category === 'Softcopy Dokumen')); dmsPreviewFile(df || {
                                                            url: superAdminStatusData.file_path_url,
                                                            name: 'Lampiran Digital - ' + superAdminStatusData.title,
                                                            box_number: superAdminStatusData.box_number
                                                        })"
                                                        class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] inline-flex items-center gap-1 cursor-pointer transition shadow-2xs">
                                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                                    <span>Lihat</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <input type="file" id="sa_file_path" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.zip" class="w-full text-[10px] font-mono text-slate-600 dark:text-slate-400 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-amber-500/20 file:text-amber-800 dark:file:text-amber-200 hover:file:bg-amber-500/30">
                                </div>
                            </div>
                            <div class="space-y-1 pt-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Catatan / Keterangan (Opsional)
                                </label>
                                <textarea x-model="superAdminStatusReason" rows="2" placeholder="Catatan kelengkapan berkas untuk PIC Gudang..." class="w-full p-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- 3. FORM MODE: TERSIMPAN DI GUDANG -->
                    <template x-if="targetStatusChoice === 'in_warehouse'">
                        <div class="space-y-2.5 p-3.5 bg-emerald-500/5 border border-emerald-500/20 rounded-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        No. Box Arsip
                                    </label>
                                    <input type="text" x-model="superAdminBoxNumber" placeholder="Auto Generate Box Code" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono font-bold text-emerald-700 dark:text-emerald-300 focus:outline-none focus:border-emerald-500">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Lokasi Fisik
                                    </label>
                                    <div class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500"></i>
                                        <span x-text="superAdminStatusData.location || 'Belum Ditentukan'"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Berkas Serah Terima Masuk Gudang (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="sa_warehouse_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Catatan / Keterangan (Opsional)
                                </label>
                                <textarea x-model="superAdminStatusReason" rows="2" placeholder="Catatan penempatan box gudang..." class="w-full p-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- 4. FORM MODE: KELUAR (OUT) -->
                    <template x-if="targetStatusChoice === 'taken'">
                        <div class="space-y-2.5 p-3.5 bg-purple-500/5 border border-purple-500/20 rounded-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Nama Penerima / Pengambil
                                    </label>
                                    <input type="text" x-model="superAdminBorrowerName" placeholder="Nama PIC..." class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500">
                                </div>
                                
                                <!-- Searchable Dropdown Departemen Tujuan -->
                                <div class="space-y-1" x-data="{
                                    open: false,
                                    search: '',
                                    departments: {{ json_encode($departments->map(fn($d) => ['id' => $d->id, 'code' => $d->code, 'name' => $d->name])) }},
                                    get filteredDepts() {
                                        if (!this.search.trim()) return this.departments;
                                        return this.departments.filter(d => 
                                            (d.name && d.name.toLowerCase().includes(this.search.toLowerCase())) ||
                                            (d.code && d.code.toLowerCase().includes(this.search.toLowerCase()))
                                        );
                                    },
                                    select(dept) {
                                        $data.superAdminDeptName = (dept.code ? dept.code + ' - ' : '') + dept.name;
                                        this.open = false;
                                        this.search = '';
                                    }
                                }" @click.outside="open = false">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                        <span>Departemen Tujuan</span>
                                        <span class="text-[9px] text-purple-600 dark:text-purple-400 font-normal">Pilih Departemen</span>
                                    </label>
                                    <div class="relative">
                                        <button 
                                            type="button" 
                                            @click="open = !open" 
                                            class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-left flex items-center justify-between focus:outline-none focus:border-purple-500"
                                        >
                                            <span class="truncate" :class="superAdminDeptName ? 'text-slate-900 dark:text-white font-bold' : 'text-slate-400'" x-text="superAdminDeptName || '-- Pilih Departemen Tujuan --'"></span>
                                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                                        </button>
                                        <div 
                                            x-show="open" 
                                            x-cloak 
                                            x-transition
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-xl overflow-hidden font-mono text-xs"
                                        >
                                            <div class="p-1.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                                <div class="relative">
                                                    <i data-lucide="search" class="w-3 h-3 absolute left-2 top-2 text-slate-400"></i>
                                                    <input 
                                                        type="text" 
                                                        x-model="search" 
                                                        placeholder="Cari kode / nama departemen..." 
                                                        class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"
                                                    >
                                                </div>
                                            </div>
                                            <ul class="max-h-40 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                                <template x-for="d in filteredDepts" :key="d.id">
                                                    <li 
                                                        @click="select(d)" 
                                                        class="px-2.5 py-1.5 hover:bg-purple-500/15 cursor-pointer flex items-center justify-between transition"
                                                        :class="superAdminDeptName === (d.code ? d.code + ' - ' : '') + d.name ? 'bg-purple-500/20 font-bold text-purple-700 dark:text-purple-400' : 'text-slate-800 dark:text-slate-200'"
                                                    >
                                                        <span x-text="(d.code ? d.code + ' - ' : '') + d.name"></span>
                                                        <i data-lucide="check" class="w-3.5 h-3.5 text-purple-600" x-show="superAdminDeptName === (d.code ? d.code + ' - ' : '') + d.name"></i>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Keperluan / Alasan Pengeluaran <span class="text-rose-500">* (Wajib Diisi)</span>
                                </label>
                                <textarea x-model="superAdminPurpose" rows="2" placeholder="Contoh: Audit Eksternal Pajak, Pengambilan Berkas Resmi Departemen..." class="w-full p-2 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Bukti / Form Serah Terima Keluar (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="sa_taken_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>
                            <div class="p-2 bg-purple-500/10 border border-purple-500/20 rounded-lg text-[10px] text-purple-800 dark:text-purple-300">
                                <span>Status berkas akan diubah ke <strong>Keluar (Out)</strong> dan slot rak gudang akan dibebaskan.</span>
                            </div>
                        </div>
                    </template>

                    <!-- 5. FORM MODE: DIMUSNAHKAN -->
                    <template x-if="targetStatusChoice === 'destroyed'">
                        <div class="space-y-2.5 p-3.5 bg-rose-500/5 border border-rose-500/20 rounded-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Nomor BAP Pemusnahan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" x-model="superAdminBapNumber" placeholder="BAP/IND/2026/00001" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Tanggal Pemusnahan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" x-model="superAdminDestroyDate" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Metode Pemusnahan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="superAdminDestroyMethod" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                    <option value="Shredding (Pencacahan Fisik)">Shredding (Pencacahan Fisik Mesin Penghancur)</option>
                                    <option value="Incineration (Pembakaran Bersertifikat)">Incineration (Pembakaran Suhu Tinggi Bersertifikat)</option>
                                    <option value="Chemical Recycling (Peleburan Kimia)">Chemical Recycling (Peleburan Kimia & Daur Ulang)</option>
                                    <option value="Digital Purge & Shred">Digital Purge & Shred (Pemusnahan Total Fisik & Digital)</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Keterangan Berita Acara (BAP) / Saksi
                                </label>
                                <textarea x-model="superAdminDestroyNotes" rows="2" placeholder="Keterangan saksi, kondisi berkas yang dimusnahkan..." class="w-full p-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500"></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Scan Form / Dokumen BAP Pemusnahan (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="sa_destroy_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>
                            <div class="p-2 bg-rose-500/10 border border-rose-500/20 rounded-lg text-[10px] text-rose-800 dark:text-rose-300">
                                <span>Pemusnahan bersifat permanen. Status arsip akan diubah ke <strong>Dimusnahkan</strong> dan slot rak dibebaskan.</span>
                            </div>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeSuperAdminStatusModal()" :disabled="updatingStatus" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                            Batal
                        </button>
                        <button type="button" 
                                @click="submitSuperAdminStatusChange()" 
                                :disabled="updatingStatus || !targetStatusChoice || (targetStatusChoice === 'draft' && !superAdminStatusReason.trim()) || (targetStatusChoice === 'taken' && !superAdminPurpose.trim()) || (targetStatusChoice === 'destroyed' && !superAdminBapNumber.trim())" 
                                class="px-5 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs rounded-xl shadow-lg shadow-amber-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                            <template x-if="updatingStatus">
                                <span class="w-3.5 h-3.5 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
                            </template>
                            <template x-if="!updatingStatus">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-slate-950"></i>
                            </template>
                            <span x-text="updatingStatus ? 'Menyimpan...' : (targetStatusChoice === 'draft' ? 'Kembalikan ke Draft' : (targetStatusChoice === 'pending_verification' ? 'Ubah ke Antrean Verifikasi' : (targetStatusChoice === 'in_warehouse' ? 'Simpan di Gudang' : (targetStatusChoice === 'taken' ? 'Keluarkan Berkas (Out)' : (targetStatusChoice === 'pending_destruction' ? 'Pindah ke Antrean Pemusnahan' : (targetStatusChoice === 'destroyed' ? 'Sahkan Pemusnahan (BAP)' : 'Terapkan Perubahan Status'))))))"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>