    @if (auth()->check() && auth()->user()->isSuperAdmin())
    <!-- Modal 6: Batch Department Assignment (Alokasi Departemen Massal - Khusus Superadmin) -->
    <div x-show="batchDeptModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 font-mono">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-5 max-w-md w-full space-y-3 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="building-2" class="w-4 h-4 text-purple-500"></i>
                    <span>Set Alokasi Departemen Massal</span>
                </h3>
                <button @click="batchDeptModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium font-sans">
                Ubah alokasi departemen secara serentak untuk <span class="font-bold text-indigo-400 font-mono" x-text="selectedLocations.length + ' objek terpilih'"></span>:
            </p>

            <form @submit.prevent="submitBatchDepartment()" class="space-y-3">
                <div class="space-y-1.5">
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">
                        Pilih Satu atau Lebih Departemen yang Diizinkan:
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-44 overflow-y-auto p-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded">
                        <template x-for="dept in departments" :key="dept.id">
                            <label class="flex items-center gap-2 p-1.5 hover:bg-purple-50 dark:hover:bg-slate-800 rounded cursor-pointer transition select-none text-xs border border-transparent"
                                :class="isBatchDeptSelected(dept.id) ? 'bg-purple-50 dark:bg-purple-950/40 border-purple-300 dark:border-purple-700' : ''">
                                <input type="checkbox" :value="dept.id" 
                                    :checked="isBatchDeptSelected(dept.id)" 
                                    @change="toggleBatchDeptSelection(dept.id)" 
                                    class="rounded text-purple-600 focus:ring-purple-500 border-slate-300 dark:border-slate-700">
                                <div class="flex flex-col min-w-0">
                                    <span class="font-bold font-mono text-purple-700 dark:text-purple-300 text-[11px]" x-text="dept.code"></span>
                                    <span class="text-slate-500 dark:text-slate-400 text-[10px] truncate" x-text="dept.name"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                    <div class="flex items-center justify-between text-[11px] pt-0.5">
                        <span class="text-slate-500 text-[10px]">
                            Terpilih: <strong class="text-purple-600 dark:text-purple-400" x-text="(batchDeptForm.assigned_department_ids && batchDeptForm.assigned_department_ids.length > 0) ? (batchDeptForm.assigned_department_ids.length + ' Departemen') : 'Semua Departemen (Bebas)'"></strong>
                        </span>
                        <button type="button" @click="batchDeptForm.assigned_department_ids = []; batchDeptForm.assigned_department_id = null;" class="text-rose-600 hover:underline font-bold text-[10px] cursor-pointer" x-show="batchDeptForm.assigned_department_ids && batchDeptForm.assigned_department_ids.length > 0">
                            Reset ke Bebas
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="batchDeptModalOpen = false" class="px-3.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-300 text-xs rounded font-bold border border-slate-300 dark:border-slate-700 cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-1 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded border border-purple-700 shadow-xs flex items-center gap-1 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Terapkan Alokasi
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif