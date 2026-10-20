    <!-- MODAL PENGELUARAN & PEMUSNAHAN BERKAS (OUT / DESTROY) -->
    <div x-show="checkoutModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm overflow-y-auto"
         style="display: none; z-index: 9997 !important;"
         @keydown.escape.window="if(!checkingOut) closeCheckoutModal()">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-4 font-mono text-xs my-auto"
             @click.away="if(!checkingOut) closeCheckoutModal()">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl" :class="checkoutActionType === 'destroy' ? 'bg-rose-500/20 text-rose-600 dark:text-rose-400' : 'bg-purple-500/20 text-purple-600 dark:text-purple-400'">
                        <i :data-lucide="checkoutActionType === 'destroy' ? 'trash-2' : 'log-out'" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                            Proses Status Berkas Gudang
                        </h3>
                        <p class="text-[11px] text-slate-500">Pilih opsi Pengeluaran Berkas (Out) atau Pemusnahan Berkas (BAP).</p>
                    </div>
                </div>
                <button type="button" @click="closeCheckoutModal()" :disabled="checkingOut" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <template x-if="checkoutModalData">
                <div class="space-y-3.5">
                    <!-- Detail Berkas Header Card -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">No. Box Arsip:</span>
                            <span class="font-black text-amber-600 dark:text-amber-400" x-text="checkoutModalData.box_number"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Departemen:</span>
                            <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold" x-text="checkoutModalData.department"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 uppercase font-bold">Posisi Rak Fisik:</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="checkoutModalData.location || 'Gudang'"></span>
                        </div>
                        <div class="pt-1 border-t border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block mb-0.5">Judul Dokumen:</span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs truncate" x-text="checkoutModalData.title"></h4>
                        </div>
                    </div>

                    <!-- Pilihan Opsi Aksi: Pengeluaran vs Pemusnahan -->
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Pilih Tindakan Status <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" 
                                    @click="checkoutActionType = 'out'"
                                    :class="checkoutActionType === 'out' ? 'bg-purple-500/20 border-purple-500 text-purple-900 dark:text-purple-200 ring-2 ring-purple-500/30 font-black' : 'bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-bold'"
                                    class="p-2.5 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs flex items-center gap-1.5">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-purple-500"></i>
                                        Pengeluaran Berkas (Out)
                                    </span>
                                    <input type="radio" name="cat_action_choice" value="out" :checked="checkoutActionType === 'out'" class="text-purple-600 pointer-events-none">
                                </div>
                                <p class="text-[10px] text-slate-500 leading-tight">Otorisasi fisik berkas keluar dari gudang.</p>
                            </button>

                            <button type="button" 
                                    @click="checkoutActionType = 'destroy'"
                                    :class="checkoutActionType === 'destroy' ? 'bg-rose-500/20 border-rose-500 text-rose-900 dark:text-rose-200 ring-2 ring-rose-500/30 font-black' : 'bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-bold'"
                                    class="p-2.5 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs flex items-center gap-1.5">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                        Pemusnahan Berkas (BAP)
                                    </span>
                                    <input type="radio" name="cat_action_choice" value="destroy" :checked="checkoutActionType === 'destroy'" class="text-rose-600 pointer-events-none">
                                </div>
                                <p class="text-[10px] text-slate-500 leading-tight">Eksekusi pemusnahan resmi & catat No. BAP.</p>
                            </button>
                        </div>
                    </div>

                    <!-- FORM MODE 1: PENGELUARAN BERKAS (OUT) -->
                    <template x-if="checkoutActionType === 'out'">
                        <div class="space-y-2.5 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Nama Penerima / Pengambil <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" x-model="checkoutBorrowerName" placeholder="Contoh: Surya Atmojo" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500">
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Departemen Tujuan
                                    </label>
                                    <div class="relative" x-data="{
                                        open: false,
                                        search: '',
                                        get filteredDepts() {
                                            if (!this.search) return departments;
                                            const q = this.search.toLowerCase();
                                            return departments.filter(d => (d.code && d.code.toLowerCase().includes(q)) || (d.name && d.name.toLowerCase().includes(q)));
                                        },
                                        select(d) {
                                            checkoutDepartmentName = d ? (d.code ? d.code + ' - ' + d.name : d.name) : '';
                                            this.open = false;
                                            this.search = '';
                                        }
                                    }" @click.outside="open = false">
                                        <!-- Trigger Button -->
                                        <button 
                                            type="button" 
                                            @click="open = !open" 
                                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-left text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 transition flex items-center justify-between shadow-2xs cursor-pointer h-[32px]"
                                        >
                                            <span class="truncate font-semibold" x-text="checkoutDepartmentName || '-- Pilih Departemen --'"></span>
                                            <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                                        </button>

                                        <!-- Dropdown Menu -->
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
                                                        @keydown.escape="open = false" 
                                                        placeholder="Cari kode / nama departemen..." 
                                                        class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"
                                                        x-ref="searchDeptInput"
                                                        x-init="$watch('open', value => { if(value) { setTimeout(() => $refs.searchDeptInput?.focus(), 50); if(window.lucide) lucide.createIcons(); } })"
                                                    >
                                                </div>
                                            </div>
                                            <ul class="max-h-40 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                                <template x-for="d in filteredDepts" :key="d.id">
                                                    <li 
                                                        @click="select(d)" 
                                                        class="px-2.5 py-1.5 hover:bg-purple-500/15 dark:hover:bg-purple-500/20 cursor-pointer flex items-center justify-between transition"
                                                        :class="checkoutDepartmentName === (d.name) || checkoutDepartmentName === (d.code + ' - ' + d.name) ? 'bg-purple-500/20 font-bold text-purple-700 dark:text-purple-400' : 'text-slate-800 dark:text-slate-200'"
                                                    >
                                                        <span x-text="(d.code ? d.code + ' - ' : '') + d.name"></span>
                                                        <i data-lucide="check" class="w-3.5 h-3.5 text-purple-600" x-show="checkoutDepartmentName === (d.name) || checkoutDepartmentName === (d.code + ' - ' + d.name)"></i>
                                                    </li>
                                                </template>
                                                <li x-show="filteredDepts.length === 0" class="p-2 text-center text-slate-400 text-[11px]">
                                                    Tidak ada departemen yang cocok
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Keperluan / Alasan Pengeluaran <span class="text-rose-500">*</span>
                                </label>
                                <textarea x-model="checkoutPurpose" rows="2" placeholder="Contoh: Audit Eksternal Pajak, Pengambilan Berkas Resmi Departemen..." class="w-full p-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"></textarea>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Bukti / Form Serah Terima (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="cat_checkout_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>

                            <div class="p-2.5 bg-purple-500/10 border border-purple-500/30 rounded-xl text-[11px] text-purple-900 dark:text-purple-200 flex items-start gap-2">
                                <i data-lucide="info" class="w-4 h-4 text-purple-600 shrink-0 mt-0.5"></i>
                                <span>Pengeluaran berkas akan secara otomatis mengubah status arsip menjadi <strong>Keluar (Out)</strong> dan mengosongkan slot rak di gudang.</span>
                            </div>
                        </div>
                    </template>

                    <!-- FORM MODE 2: PEMUSNAHAN BERKAS (DESTROY / BAP) -->
                    <template x-if="checkoutActionType === 'destroy'">
                        <div class="space-y-2.5 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Nomor BAP Pemusnahan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" x-model="destroyBapNumber" placeholder="BAP/IND/2026/00001" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                        Tanggal Pemusnahan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" x-model="destroyDate" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Metode Pemusnahan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="destroyMethod" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500">
                                    <option value="Shredding (Pencacahan Fisik)">Shredding (Pencacahan Fisik Mesin Penghancur)</option>
                                    <option value="Incineration (Pembakaran Bersertifikat)">Incineration (Pembakaran Suhu Tinggi Bersertifikat)</option>
                                    <option value="Chemical Recycling (Peleburan Kimia)">Chemical Recycling (Peleburan Kimia & Daur Ulang Industri)</option>
                                    <option value="Digital Purge & Shred">Digital Purge & Shred (Pemusnahan Total Fisik & Digital)</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Catatan / Keterangan Berita Acara (BAP)
                                </label>
                                <textarea x-model="destroyNotes" rows="2" placeholder="Keterangan saksi, kondisi berkas yang dimusnahkan..." class="w-full p-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500"></textarea>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase flex items-center justify-between">
                                    <span>Upload Scan Form Persetujuan / Bukti BAP (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">PDF/JPG Maks. 500 KB</span>
                                </label>
                                <input type="file" id="cat_destroy_approval_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] font-mono text-slate-700 dark:text-slate-300">
                            </div>

                            <div class="p-2.5 bg-rose-500/10 border border-rose-500/30 rounded-xl text-[11px] text-rose-900 dark:text-rose-200 flex items-start gap-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 shrink-0 mt-0.5"></i>
                                <span>Pemusnahan berkas bersifat permanen. Status arsip akan diubah menjadi <strong>Dimusnahkan</strong> dan slot rak gudang akan dibebaskan.</span>
                            </div>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeCheckoutModal()" :disabled="checkingOut" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition">
                            Batal
                        </button>
                        
                        <template x-if="checkoutActionType === 'out'">
                            <button type="button" 
                                    @click="submitCheckout()" 
                                    :disabled="checkingOut || !checkoutPurpose.trim()" 
                                    class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl font-black text-xs shadow-lg shadow-purple-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                                <template x-if="checkingOut">
                                    <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                </template>
                                <template x-if="!checkingOut">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                </template>
                                <span x-text="checkingOut ? 'Memproses...' : 'Konfirmasi Pengeluaran Berkas (Out)'"></span>
                            </button>
                        </template>

                        <template x-if="checkoutActionType === 'destroy'">
                            <button type="button" 
                                    @click="submitCheckout()" 
                                    :disabled="checkingOut || !destroyBapNumber.trim()" 
                                    class="px-4 py-2 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white rounded-xl font-black text-xs shadow-lg shadow-rose-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                                <template x-if="checkingOut">
                                    <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                </template>
                                <template x-if="!checkingOut">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </template>
                                <span x-text="checkingOut ? 'Memproses...' : 'Sahkan Pemusnahan Berkas (BAP)'"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>