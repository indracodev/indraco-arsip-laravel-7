        <!-- SECTION 1: UNIT & DEPARTEMEN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="building-2" class="w-3.5 h-3.5 text-purple-500"></i>
                1. Identitas Unit Kerja & Penyerahan Dokumen
            </legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Company Entity -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PERUSAHAAN ENTITAS <span class="text-rose-500">*</span>
                    </label>
                    <input type="hidden" name="company_name" :value="selectedCompany">
                    <button 
                        type="button" 
                        disabled
                        class="w-full px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left font-bold text-slate-600 dark:text-slate-300 cursor-not-allowed select-none transition flex items-center justify-between opacity-90"
                    >
                        <span class="truncate" x-text="selectedCompany"></span>
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                    </button>
                </div>

                <!-- Department Selection -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        DEPARTEMEN PEMILIK <span class="text-rose-500">*</span>
                    </label>
                    @if(auth()->user()->isPicDept())
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <button 
                            type="button" 
                            disabled 
                            class="w-full px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left font-bold text-slate-600 dark:text-slate-300 cursor-not-allowed select-none transition flex items-center justify-between opacity-90"
                            title="Departemen dikunci sesuai akun Anda"
                        >
                            <span class="truncate">{{ auth()->user()->department ? auth()->user()->department->code . ' - ' . auth()->user()->department->name : 'Departemen' }}</span>
                            <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                        </button>
                    @else
                        <div class="relative" x-data="{
                            open: false,
                            search: '',
                            get filteredDepts() {
                                if (!this.search) return departments;
                                const q = this.search.toLowerCase();
                                return departments.filter(d => (d.code && d.code.toLowerCase().includes(q)) || (d.name && d.name.toLowerCase().includes(q)));
                            },
                            get selectedDeptLabel() {
                                const d = departments.find(item => item.id == selectedDeptId);
                                return d ? (d.code + ' - ' + d.name) : '-- Pilih Departemen --';
                            },
                            select(d) {
                                selectedDeptId = d ? d.id : '';
                                updateSubDepartments();
                                this.open = false;
                                this.search = '';
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="department_id" :value="selectedDeptId" required>
                            <button 
                                type="button" 
                                @click="open = !open" 
                                class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition flex items-center justify-between cursor-pointer"
                            >
                                <span class="truncate font-semibold" x-text="selectedDeptLabel"></span>
                                <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                            </button>
                            <div 
                                x-show="open" 
                                x-cloak 
                                x-transition
                                class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded shadow-lg overflow-hidden font-mono text-xs"
                            >
                                <div class="p-1.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                    <div class="relative">
                                        <i data-lucide="search" class="w-3 h-3 absolute left-2 top-2 text-slate-400"></i>
                                        <input 
                                            type="text" 
                                            x-model="search" 
                                            @keydown.escape="open = false" 
                                            placeholder="Cari kode / nama departemen..." 
                                            class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                                            x-ref="searchDeptInput"
                                            x-init="$watch('open', value => { if(value) { setTimeout(() => $refs.searchDeptInput?.focus(), 50); if(window.lucide) lucide.createIcons(); } })"
                                        >
                                    </div>
                                </div>
                                <ul class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                    <template x-for="d in filteredDepts" :key="d.id">
                                        <li 
                                            @click="select(d)" 
                                            class="px-2.5 py-1.5 hover:bg-purple-500/15 dark:hover:bg-purple-500/20 cursor-pointer flex items-center justify-between transition"
                                            :class="selectedDeptId == d.id ? 'bg-purple-500/20 font-bold text-purple-700 dark:text-purple-400' : 'text-slate-800 dark:text-slate-200'"
                                        >
                                            <span x-text="d.code + ' - ' + d.name"></span>
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-purple-600" x-show="selectedDeptId == d.id"></i>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sub-Departemen (Induk / Default Batch) -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        SUB-DEPARTEMEN <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input type="hidden" name="sub_department_id" :value="selectedSubDeptId === 'inti' ? '' : selectedSubDeptId">
                    <select 
                        x-model="selectedSubDeptId"
                        @change="onSectionSubDeptChange()"
                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition shadow-2xs"
                    >
                        <option value="">-- Pilih Sub-Departemen --</option>
                        <option value="inti">Inti Departemen</option>
                        <template x-for="s in subDepartments" :key="s.id">
                            <option :value="s.id" x-text="s.code + ' - ' + s.name" :selected="selectedSubDeptId == s.id"></option>
                        </template>
                    </select>
                </div>

                <!-- Tgl Penyerahan -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        TGL. PENYERAHAN DOKUMEN <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="tgl_penyerahan" 
                        id="tgl_penyerahan" 
                        x-model="tglPenyerahan"
                        max="{{ date('Y-m-d') }}"
                        required 
                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-sans block mt-0.5">Tanggal serah terima box fisik ke Gudang (maksimal hari ini)</span>
                </div>
            </div>
        </fieldset>
