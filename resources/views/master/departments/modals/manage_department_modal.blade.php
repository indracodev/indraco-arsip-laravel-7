    <!-- ========================================================================= -->
    <!-- MODAL FORM KELOLA DEPARTEMEN (PIC USERS & MASTER ARSIP DOKUMEN)           -->
    <!-- ========================================================================= -->
    <div x-show="openManageDeptModal" x-cloak 
         @keydown.escape.window="openManageDeptModal = false"
         class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
         style="z-index: 65;">
        <div @click.stop
             class="delphi-window bg-white dark:bg-slate-950 border-2 border-indigo-500/60 dark:border-indigo-700 rounded-lg max-w-4xl w-full shadow-2xl overflow-hidden font-mono max-h-[92vh] flex flex-col my-auto">
            
            <!-- Window Title Bar -->
            <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white px-3.5 py-2.5 flex items-center justify-between border-b border-indigo-700/80 select-none shrink-0">
                <div class="flex items-center gap-2 font-bold text-xs">
                    <span class="p-1 bg-amber-400/20 text-amber-300 border border-amber-400/40 rounded">
                        <i data-lucide="sliders" class="w-4 h-4"></i>
                    </span>
                    <span>frmDeptManager : Kelola Unit & Master Dokumen</span>
                    <span class="text-amber-300 font-black px-2 py-0.5 bg-indigo-950/70 rounded border border-indigo-600/50 flex items-center gap-1">
                        <span x-text="selectedManageDept ? (selectedManageDept.code + ' - ' + selectedManageDept.name) : ''"></span>
                        <template x-if="selectedManageSubDept">
                            <span class="flex items-center gap-1">
                                <span class="text-slate-400 font-normal">/</span>
                                <span class="text-emerald-300" x-text="selectedManageSubDept.code + ' ' + selectedManageSubDept.name"></span>
                            </span>
                        </template>
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <button @click="openManageDeptModal = false" type="button" class="text-slate-300 hover:text-white text-xs font-bold px-2 py-1 rounded hover:bg-rose-600 transition" title="Tutup Jendela (Esc)">✕</button>
                </div>
            </div>

            <!-- Dept Quick Status Bar -->
            <div class="bg-indigo-50 dark:bg-indigo-950/40 px-4 py-2 border-b border-indigo-200 dark:border-indigo-900/60 flex flex-wrap items-center justify-between gap-2 text-xs font-mono">
                <div class="flex items-center gap-3">
                    <div>
                        <span class="text-slate-500 text-[10px] uppercase">Unit Kerja:</span>
                        <strong class="text-indigo-950 dark:text-indigo-200 text-xs" x-text="selectedManageDept ? selectedManageDept.name : ''"></strong>
                        <template x-if="selectedManageSubDept">
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] border border-emerald-500/30" x-text="'Sub: ' + selectedManageSubDept.name"></span>
                        </template>
                    </div>
                    <div class="hidden sm:block border-l border-indigo-300 dark:border-indigo-800 pl-3">
                        <span class="text-slate-500 text-[10px] uppercase">Masa Simpan Default:</span>
                        <strong class="text-purple-700 dark:text-purple-300 text-xs" x-text="(selectedManageSubDept ? (selectedManageSubDept.retention_years || selectedManageDept.retention_years || 5) : (selectedManageDept ? selectedManageDept.retention_years : 5)) + ' Tahun'"></strong>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded bg-blue-500/15 text-blue-700 dark:text-blue-300 text-[11px] font-bold border border-blue-500/30">
                        <i data-lucide="users" class="w-3 h-3 inline mr-0.5"></i>
                        <span x-text="managePicUsers.length + ' User PIC'"></span>
                    </span>
                    <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold border border-emerald-500/30">
                        <i data-lucide="folder-check" class="w-3 h-3 inline mr-0.5"></i>
                        <span x-text="manageMasterArchives.length + ' Master Berkas'"></span>
                    </span>
                </div>
            </div>

            <!-- Segmented Tab Navigation -->
            <div class="bg-slate-100 dark:bg-slate-900 px-4 pt-2 border-b border-slate-300 dark:border-slate-800 flex items-center gap-1 font-mono text-xs shrink-0">
                <button 
                    @click="manageTab = 'pic'; $nextTick(() => lucide.createIcons())" 
                    type="button" 
                    class="px-3.5 py-1.5 font-bold rounded-t border-t border-x transition flex items-center gap-1.5"
                    :class="manageTab === 'pic' ? 'bg-white dark:bg-slate-950 text-indigo-700 dark:text-indigo-400 border-slate-300 dark:border-slate-800 border-b-transparent shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border-transparent'"
                >
                    <i data-lucide="user-check" class="w-3.5 h-3.5 text-blue-500"></i>
                    <span>1. User PIC Departemen</span>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="manageTab === 'pic' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : 'bg-slate-200 dark:bg-slate-800 text-slate-600'" x-text="managePicUsers.length"></span>
                </button>

                <button 
                    @click="manageTab = 'master_archives'; $nextTick(() => lucide.createIcons())" 
                    type="button" 
                    class="px-3.5 py-1.5 font-bold rounded-t border-t border-x transition flex items-center gap-1.5"
                    :class="manageTab === 'master_archives' ? 'bg-white dark:bg-slate-950 text-emerald-700 dark:text-emerald-400 border-slate-300 dark:border-slate-800 border-b-transparent shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border-transparent'"
                >
                    <i data-lucide="folder-plus" class="w-3.5 h-3.5 text-emerald-500"></i>
                    <span>2. Master Berkas & Dokumen Baku</span>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="manageTab === 'master_archives' ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300' : 'bg-slate-200 dark:bg-slate-800 text-slate-600'" x-text="manageMasterArchives.length"></span>
                </button>
            </div>

            <!-- Tab Content Body Area -->
            <div class="p-4 space-y-3 overflow-y-auto flex-1 font-sans text-xs relative">
                
                <!-- Loading Overlay inside Modal -->
                <div x-show="manageLoading" x-cloak class="absolute inset-0 bg-white/70 dark:bg-slate-950/70 backdrop-blur-xs z-20 flex items-center justify-center font-mono">
                    <div class="flex items-center gap-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-900 px-3 py-2 rounded shadow border border-slate-200 dark:border-slate-800">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Memuat Data Departemen...
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- TAB 1: MANAJEMEN USER PIC DEPARTEMEN                      -->
                <!-- ========================================================= -->
                <div x-show="manageTab === 'pic'" class="space-y-4 font-sans">
                    
                    <!-- Top Action Card for Adding / Assigning PIC -->
                    <div class="bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/60 rounded p-3 font-mono space-y-3 shadow-xs">
                        <div class="flex items-center justify-between border-b border-indigo-200 dark:border-indigo-900/60 pb-2">
                            <span class="font-bold text-xs text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5">
                                <i data-lucide="user-plus" class="w-3.5 h-3.5 text-indigo-600"></i>
                                Penugasan PIC untuk Departemen Ini
                            </span>
                            <button 
                                @click="showCreatePicForm = !showCreatePicForm; $nextTick(() => lucide.createIcons())" 
                                type="button" 
                                class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold flex items-center gap-1"
                            >
                                <span x-text="showCreatePicForm ? 'Tutup Form Buat User' : '+ Buat Akun PIC Baru'"></span>
                            </button>
                        </div>

                        <!-- 1. Assign Existing User Form -->
                        <div x-show="!showCreatePicForm" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <div class="flex-1">
                                <label class="block text-[10px] text-slate-500 font-bold uppercase mb-0.5">Pilih Pengguna Yang Sudah Ada:</label>
                                <select x-model="selectedAssignUserId" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:border-indigo-500">
                                    <option value="">-- Pilih User untuk ditugaskan sebagai PIC --</option>
                                    <template x-for="usr in availableUsers" :key="usr.id">
                                        <option :value="usr.id" x-text="usr.name + ' (' + usr.email + ') - ' + (usr.role || 'user')"></option>
                                    </template>
                                </select>
                            </div>
                            <button 
                                @click="assignExistingPic()" 
                                type="button" 
                                :disabled="!selectedAssignUserId || manageLoading"
                                class="sm:self-end px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs rounded transition flex items-center justify-center gap-1 shrink-0 shadow-xs"
                            >
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Tugaskan Sebagai PIC</span>
                            </button>
                        </div>

                        <!-- 2. Create & Assign New User Form -->
                        <div x-show="showCreatePicForm" x-cloak class="p-3 bg-white dark:bg-slate-900 rounded border border-indigo-300 dark:border-indigo-800 space-y-3">
                            <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300 border-b pb-1">
                                Formulir Registrasi Akun PIC Baru:
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold mb-0.5">NAMA LENGKAP <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="newPicName" autocomplete="off" placeholder="Nama Lengkap User" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold mb-0.5">EMAIL LOGIN <span class="text-rose-500">*</span></label>
                                    <input type="email" x-model="newPicEmail" autocomplete="off" placeholder="nama@indraco.com" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold mb-0.5">PASSWORD <span class="text-rose-500">*</span></label>
                                    <input type="password" x-model="newPicPassword" autocomplete="new-password" placeholder="Min. 6 karakter" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold mb-0.5">NO. TELEPON / WHATSAPP</label>
                                    <input type="text" x-model="newPicPhone" autocomplete="off" placeholder="0812xxxx" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs">
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-2 pt-1">
                                <button @click="showCreatePicForm = false" type="button" class="px-2.5 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded text-xs">Batal</button>
                                <button @click="createAndAssignPic()" type="button" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded text-xs transition flex items-center gap-1 shadow-xs">
                                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                    <span>Simpan & Buat Akun PIC</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table of Assigned PIC Users -->
                    <div class="border border-slate-300 dark:border-slate-800 rounded bg-white dark:bg-slate-950 overflow-hidden shadow-xs">
                        <div class="bg-slate-100 dark:bg-slate-900 px-3 py-2 border-b border-slate-300 dark:border-slate-800 font-mono text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-500"></i>
                                Daftar User PIC Terdaftar (<span x-text="managePicUsers.length"></span> Pengguna)
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 font-mono text-[10px] text-slate-600 dark:text-slate-400 select-none">
                                        <th class="py-2 px-3">NAMA PENGGUNA</th>
                                        <th class="py-2 px-3">EMAIL</th>
                                        <th class="py-2 px-3">NO. TELEPON</th>
                                        <th class="py-2 px-3 text-center">ROLE</th>
                                        <th class="py-2 px-3 text-right">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <template x-for="u in managePicUsers" :key="u.id">
                                        <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition">
                                            <td class="py-2 px-3 font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-blue-500/20 text-blue-600 dark:text-blue-400 font-mono font-bold text-[10px] flex items-center justify-center" x-text="u.name.charAt(0).toUpperCase()"></div>
                                                <span x-text="u.name"></span>
                                            </td>
                                            <td class="py-2 px-3 font-mono text-slate-600 dark:text-slate-400" x-text="u.email"></td>
                                            <td class="py-2 px-3 font-mono text-slate-600 dark:text-slate-400" x-text="u.phone || '-'"></td>
                                            <td class="py-2 px-3 text-center font-mono">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 border border-blue-200">PIC DEPT</span>
                                            </td>
                                            <td class="py-2 px-3 text-right font-mono whitespace-nowrap">
                                                <button 
                                                    @click="resetPicPassword(u)" 
                                                    type="button" 
                                                    class="px-2 py-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 rounded text-[11px] font-bold transition inline-flex items-center gap-1 mr-1"
                                                    title="Ganti / Reset password user PIC ini"
                                                >
                                                    <i data-lucide="key" class="w-3 h-3 text-amber-500"></i>
                                                    <span>Ganti Password</span>
                                                </button>
                                                <button 
                                                    @click="removePicUser(u)" 
                                                    type="button" 
                                                    class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[11px] font-bold transition inline-flex items-center gap-1"
                                                    title="Lepaskan user ini dari PIC departemen"
                                                >
                                                    <i data-lucide="user-x" class="w-3 h-3 text-rose-500"></i>
                                                    <span>Hapus PIC</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="managePicUsers.length === 0">
                                        <td colspan="5" class="py-6 text-center text-slate-400 font-mono text-xs">
                                            Belum ada user PIC yang ditugaskan untuk departemen ini.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- TAB 2: MASTER BERKAS & DOKUMEN ARSIP BAKU                 -->
                <!-- ========================================================= -->
                <div x-show="manageTab === 'master_archives'" class="space-y-4 font-sans">
                    
                    <!-- Search and Action Ribbon -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 font-mono">
                        <div class="flex items-center gap-2 flex-1 max-w-md">
                            <div class="relative flex-1">
                                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                                <input 
                                    type="text" 
                                    x-model="manageSearchArchive" 
                                    placeholder="Cari Master Berkas..." 
                                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono"
                                >
                            </div>
                            <select x-model="selectedSubDeptFilter" class="px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500">
                                <option value="">Semua Sub-Unit</option>
                                <option value="induk_only">Hanya Induk / General</option>
                                <template x-for="sub in manageSubDepts" :key="sub.id">
                                    <option :value="sub.id.toString()" x-text="sub.code + ' - ' + sub.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <!-- Batch Add Button -->
                            <button 
                                @click="showBatchAddForm = !showBatchAddForm; if(showBatchAddForm) { showAddMasterArchiveForm = false; } $nextTick(() => lucide.createIcons())" 
                                type="button" 
                                class="px-3 py-1.5 bg-purple-700 hover:bg-purple-600 text-white font-bold rounded text-xs shadow-xs transition flex items-center justify-center gap-1.5"
                                :class="showBatchAddForm ? 'ring-2 ring-purple-400' : ''"
                                title="Tambah banyak master arsip sekaligus dengan menekan Enter per baris"
                            >
                                <i data-lucide="layers" class="w-3.5 h-3.5 text-amber-300"></i>
                                <span x-text="showBatchAddForm ? 'Tutup Batch' : '+ Tambah Batch (Multi-Line)'"></span>
                            </button>

                            <!-- Single Add Button -->
                            <button 
                                @click="showAddMasterArchiveForm = !showAddMasterArchiveForm; if(showAddMasterArchiveForm) { showBatchAddForm = false; } $nextTick(() => lucide.createIcons())" 
                                type="button" 
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded text-xs shadow-xs transition flex items-center justify-center gap-1.5"
                                :class="showAddMasterArchiveForm ? 'ring-2 ring-emerald-400' : ''"
                            >
                                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                                <span x-text="showAddMasterArchiveForm ? 'Tutup Satuan' : '+ Tambah Satuan'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- 1. BATCH ADD MASTER ARSIP FORM (MULTI-LINE ENTER) -->
                    <div x-show="showBatchAddForm" x-cloak class="p-3.5 bg-purple-50/80 dark:bg-purple-950/30 rounded border-2 border-purple-500/50 font-mono space-y-3 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between border-b border-purple-500/30 pb-2 gap-2">
                            <span class="text-xs font-bold text-purple-900 dark:text-purple-300 flex items-center gap-1.5">
                                <i data-lucide="list-plus" class="w-4 h-4 text-purple-600"></i>
                                Form Input Batch Master Berkas (1 Baris / Enter = 1 Master Berkas)
                            </span>
                            <span class="text-[10px] text-purple-700 dark:text-purple-300 font-bold bg-purple-200/70 dark:bg-purple-900/60 px-2 py-0.5 rounded border border-purple-400/40">
                                Tekan Enter untuk membuat berkas arsip berikutnya
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-[11px] font-bold text-slate-800 dark:text-slate-200">
                                        DAFTAR NAMA BERKAS ARSIP (Ketik atau Tempel / Paste di bawah ini):
                                    </label>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-purple-600 text-white shadow-xs" x-text="getBatchLinesCount() + ' Berkas Terdeteksi'"></span>
                                </div>
                                <textarea 
                                    x-model="batchArchiveText" 
                                    rows="7" 
                                    placeholder="Contoh:&#10;PL, DO, GDO, GI, DIT, SIK&#10;Deklarasi Insurance, IM&#10;PR Draff, IM&#10;DIP, BPBJ, GRPO, IM, TT, Klaim&#10;Sarmut, Penilaian trasporter, evaluasi&#10;Dokumen umum expedisi&#10;GI, Sales retur, IM&#10;IT, ITR&#10;Picklist&#10;BPBJ, Receipt&#10;Dokumen umum logistik"
                                    class="w-full p-2.5 bg-white dark:bg-slate-900 border-2 border-purple-300 dark:border-purple-800 focus:border-purple-600 rounded text-xs font-mono font-semibold text-slate-900 dark:text-slate-100 placeholder-slate-400 leading-relaxed shadow-inner"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        SUB-DEPARTEMEN TUJUAN
                                    </label>
                                    <select x-model="batchArchiveSubDeptId" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono focus:border-purple-500">
                                        <option value="">-- Berlaku untuk Semua (Induk) --</option>
                                        <template x-for="sub in manageSubDepts" :key="sub.id">
                                            <option :value="sub.id.toString()" x-text="sub.code + ' - ' + sub.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        KATEGORI DOKUMEN
                                    </label>
                                    <select x-model="batchArchiveDocType" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono focus:border-purple-500">
                                        <option value="UMUM">UMUM / LAIN-LAIN</option>
                                        <option value="SURAT_JALAN">SURAT JALAN & LOGISTIK</option>
                                        <option value="FAKTUR_PAJAK">FAKTUR PAJAK & KEUANGAN</option>
                                        <option value="KONTRAK_KERJA">KONTRAK KERJA / SDM</option>
                                        <option value="MOU_SPONSOR">MOU / KERJASAMA / SALES</option>
                                        <option value="PRODUKSI_QC">PRODUKSI & QUALITY CONTROL</option>
                                        <option value="MAINTENANCE">MAINTENANCE & FASILITAS</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        MASA SIMPAN STANDAR (TAHUN)
                                    </label>
                                    <input 
                                        type="number" 
                                        min="1" 
                                        max="100" 
                                        x-model="batchArchiveRetentionYears" 
                                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold focus:border-purple-500"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-purple-500/20">
                            <div class="text-[11px] text-slate-600 dark:text-slate-400 font-sans">
                                Setiap baris teks di atas akan langsung disimpan otomatis sebagai master berkas baku.
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="showBatchAddForm = false" type="button" class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded text-xs">Batal</button>
                                <button 
                                    @click="saveBatchMasterArchives()" 
                                    type="button" 
                                    :disabled="manageLoading || getBatchLinesCount() === 0"
                                    class="px-4 py-1.5 bg-purple-700 hover:bg-purple-600 disabled:opacity-50 text-white font-black rounded text-xs transition flex items-center gap-1.5 shadow-xs"
                                >
                                    <i data-lucide="check-check" class="w-3.5 h-3.5 text-amber-300"></i>
                                    <span x-text="'Simpan ' + getBatchLinesCount() + ' Master Berkas (Batch)'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. SINGLE ADD MASTER ARSIP FORM (Collapsible) -->
                    <div x-show="showAddMasterArchiveForm" x-cloak class="p-3.5 bg-emerald-50/60 dark:bg-emerald-950/20 rounded border-2 border-emerald-500/40 font-mono space-y-3">
                        <div class="flex items-center justify-between border-b border-emerald-500/30 pb-2">
                            <span class="text-xs font-bold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5">
                                <i data-lucide="file-plus" class="w-4 h-4 text-emerald-600"></i>
                                Form Tambah Master Dokumen / Berkas Arsip Tunggal
                            </span>
                            <span class="text-[10px] text-slate-500">Standar berkas yang akan muncul di dropdown Katalog Arsip</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    NAMA DOKUMEN / BERKAS ARSIP <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    x-model="newArchiveName" 
                                    placeholder="Contoh: Faktur Pajak Masukan, Rekap Kasir, Surat Jalan" 
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold font-mono focus:border-emerald-500"
                                >
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    KODE DOKUMEN <span class="text-slate-400">(Opsional)</span>
                                </label>
                                <input 
                                    type="text" 
                                    x-model="newArchiveCode" 
                                    placeholder="Contoh: DOC-FIN-01" 
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono uppercase focus:border-emerald-500"
                                >
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    KATEGORI DOKUMEN
                                </label>
                                <select x-model="newArchiveDocType" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono focus:border-emerald-500">
                                    <option value="FAKTUR_PAJAK">FAKTUR PAJAK & KEUANGAN</option>
                                    <option value="KONTRAK_KERJA">KONTRAK KERJA / SDM</option>
                                    <option value="MOU_SPONSOR">MOU / KERJASAMA / SALES</option>
                                    <option value="SURAT_JALAN">SURAT JALAN & LOGISTIK</option>
                                    <option value="PRODUKSI_QC">PRODUKSI & QUALITY CONTROL</option>
                                    <option value="MAINTENANCE">MAINTENANCE & FASILITAS</option>
                                    <option value="UMUM">UMUM / LAIN-LAIN</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    SUB-DEPARTEMEN <span class="text-slate-400">(Opsional)</span>
                                </label>
                                <select x-model="newArchiveSubDeptId" class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono focus:border-emerald-500">
                                    <option value="">-- Berlaku untuk Semua (Induk) --</option>
                                    <template x-for="sub in manageSubDepts" :key="sub.id">
                                        <option :value="sub.id" x-text="sub.code + ' - ' + sub.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    MASA SIMPAN STANDAR (TAHUN)
                                </label>
                                <input 
                                    type="number" 
                                    min="1" 
                                    max="100" 
                                    x-model="newArchiveRetentionYears" 
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold focus:border-emerald-500"
                                >
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    KETERANGAN / DESKRIPSI
                                </label>
                                <input 
                                    type="text" 
                                    x-model="newArchiveDescription" 
                                    placeholder="Deskripsi atau petunjuk isi dokumen arsip..." 
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono focus:border-emerald-500"
                                >
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-emerald-500/20">
                            <button @click="showAddMasterArchiveForm = false" type="button" class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded text-xs">Batal</button>
                            <button @click="saveMasterArchive()" type="button" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded text-xs transition flex items-center gap-1 shadow-xs">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Simpan Master Berkas</span>
                            </button>
                        </div>
                    </div>

                    <!-- Master Archives Table -->
                    <div class="border border-slate-300 dark:border-slate-800 rounded bg-white dark:bg-slate-950 overflow-hidden shadow-xs">
                        <div class="overflow-x-auto max-h-72">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 font-mono text-[10px] text-slate-700 dark:text-slate-300 select-none">
                                        <th class="py-2 px-2.5 w-8 text-center">#</th>
                                        <th class="py-2 px-3">KODE</th>
                                        <th class="py-2 px-3">NAMA BERKAS / DOKUMEN ARSIP</th>
                                        <th class="py-2 px-3">KATEGORI</th>
                                        <th class="py-2 px-3">SUB-UNIT</th>
                                        <th class="py-2 px-2.5 text-center">MASA SIMPAN</th>
                                        <th class="py-2 px-3">KETERANGAN</th>
                                        <th class="py-2 px-3 text-right">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-sans">
                                    <template x-for="(arc, idx) in filteredManageMasterArchives" :key="arc.id">
                                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-emerald-950/20 transition">
                                            <td class="py-2 px-2.5 text-center font-mono font-bold text-slate-500" x-text="idx + 1"></td>
                                            <td class="py-2 px-3 font-mono font-bold text-amber-600 dark:text-amber-400" x-text="arc.code || '-'"></td>
                                            <td class="py-2 px-3 font-bold text-slate-900 dark:text-white" x-text="arc.name"></td>
                                            <td class="py-2 px-3 font-mono text-[11px] text-slate-600 dark:text-slate-400" x-text="arc.document_type || '-'"></td>
                                            <td class="py-2 px-3 font-mono text-[11px] text-slate-600 dark:text-slate-400" x-text="arc.sub_department ? arc.sub_department.name : 'Induk / Semua'"></td>
                                            <td class="py-2 px-2.5 text-center font-mono font-bold text-purple-700 dark:text-purple-300" x-text="(arc.retention_years || 5) + ' Thn'"></td>
                                            <td class="py-2 px-3 text-slate-600 dark:text-slate-400 text-[11px]" x-text="arc.description || '-'"></td>
                                            <td class="py-2 px-3 text-right font-mono">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button 
                                                        @click="editMasterArchiveItem = Object.assign({}, arc); $nextTick(() => lucide.createIcons())" 
                                                        type="button" 
                                                        class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-[10px] font-bold"
                                                        title="Edit master berkas ini"
                                                    >
                                                        <i data-lucide="edit-2" class="w-2.5 h-2.5 inline text-amber-500"></i> Edit
                                                    </button>
                                                    <button 
                                                        @click="deleteMasterArchive(arc)" 
                                                        type="button" 
                                                        class="px-2 py-0.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[10px] font-bold"
                                                        title="Hapus master berkas ini"
                                                    >
                                                        <i data-lucide="trash-2" class="w-2.5 h-2.5 inline text-rose-500"></i> Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredManageMasterArchives.length === 0">
                                        <td colspan="8" class="py-6 text-center text-slate-400 font-mono text-xs">
                                            Belum ada master dokumen / berkas arsip yang terdaftar.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Toolbar -->
            <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between font-mono text-xs shrink-0">
                <span class="text-[11px] text-slate-500">Perubahan data PIC & Master Berkas langsung aktif realtime di seluruh sistem.</span>
                <button type="button" @click="openManageDeptModal = false" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded shadow-xs">
                    Selesai & Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SUB-MODAL: EDIT MASTER ARSIP                                              -->
    <!-- ========================================================================= -->
    <div x-show="editMasterArchiveItem" x-cloak 
         class="fixed inset-0 z-[75] bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3"
         style="z-index: 75;">
        <div class="delphi-window bg-white dark:bg-slate-950 border-2 border-amber-500 rounded-lg max-w-lg w-full shadow-2xl overflow-hidden font-mono flex flex-col">
            <div class="bg-gradient-to-r from-amber-700 via-amber-600 to-amber-800 text-slate-950 px-3 py-2 flex items-center justify-between border-b border-amber-500 font-bold text-xs">
                <span>frmEditMasterArchive : Edit Master Dokumen</span>
                <button @click="editMasterArchiveItem = null" type="button" class="text-slate-900 hover:text-white text-xs font-bold px-1.5 py-0.5 rounded">✕</button>
            </div>
            <div class="p-4 space-y-3 font-sans text-xs">
                <div>
                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA DOKUMEN / BERKAS ARSIP <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="editMasterArchiveItem.name" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold font-mono">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE DOKUMEN</label>
                        <input type="text" x-model="editMasterArchiveItem.code" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono uppercase">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">MASA SIMPAN (THN)</label>
                        <input type="number" min="1" max="100" x-model="editMasterArchiveItem.retention_years" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">KATEGORI DOKUMEN</label>
                    <select x-model="editMasterArchiveItem.document_type" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono">
                        <option value="FAKTUR_PAJAK">FAKTUR PAJAK & KEUANGAN</option>
                        <option value="KONTRAK_KERJA">KONTRAK KERJA / SDM</option>
                        <option value="MOU_SPONSOR">MOU / KERJASAMA / SALES</option>
                        <option value="SURAT_JALAN">SURAT JALAN & LOGISTIK</option>
                        <option value="PRODUKSI_QC">PRODUKSI & QUALITY CONTROL</option>
                        <option value="MAINTENANCE">MAINTENANCE & FASILITAS</option>
                        <option value="UMUM">UMUM / LAIN-LAIN</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">KETERANGAN</label>
                    <input type="text" x-model="editMasterArchiveItem.description" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono">
                </div>
            </div>
            <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-end gap-2 font-mono text-xs">
                <button @click="editMasterArchiveItem = null" type="button" class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded">Batal</button>
                <button @click="updateMasterArchiveSubmit()" type="button" class="px-4 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded">Simpan Perubahan</button>
            </div>
        </div>
    </div>