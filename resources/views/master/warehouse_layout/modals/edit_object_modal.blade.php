    <!-- Modal 4: Edit Object (Gudang / Rak) -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 font-mono">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-5 max-w-md w-full space-y-3 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="edit-3" class="w-4 h-4 text-blue-500"></i>
                    Edit Details, Nama & Warna Object
                </h3>
                <button @click="editModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form @submit.prevent="submitEdit()" class="space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Nama / Kode Object</label>
                        <input type="text" x-model="editForm.rack_code" required class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Kode Sektor Ruangan</label>
                        <input type="text" x-model="editForm.room_sector" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Kode Baris / Shelf</label>
                        <input type="text" x-model="editForm.shelf_code" placeholder="e.g. BARIS-01" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-medium">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Kapasitas Box</label>
                        <input type="number" x-model="editForm.box_capacity" min="1" max="5000" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-bold">
                    </div>
                </div>

                <!-- Dimensions & Orientation -->
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Lebar (W)</label>
                        <input type="number" x-model="editForm.canvas_width" required min="20" max="800" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Tinggi (H)</label>
                        <input type="number" x-model="editForm.canvas_height" required min="20" max="800" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Orientasi</label>
                        <select x-model="editForm.orientation" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-medium">
                            <option value="horizontal">Horizontal</option>
                            <option value="vertical">Vertical</option>
                        </select>
                    </div>
                </div>

                <!-- Custom Color Picker (Square Swatches) -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1.5">Warna Custom Object</label>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button @click="editForm.custom_color = ''" type="button" class="px-2 py-0.5 text-[10px] rounded font-bold border cursor-pointer" :class="!editForm.custom_color ? 'bg-slate-800 text-white border-amber-400' : 'bg-slate-100 dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-700'">Auto (Status)</button>
                        <template x-for="c in colorPresets" :key="c">
                            <button @click="editForm.custom_color = c" type="button" class="w-6 h-6 rounded-none border-2 transition cursor-pointer" :class="editForm.custom_color === c ? 'border-white scale-110 shadow-xs ring-1 ring-amber-400' : 'border-slate-500 opacity-80 hover:opacity-100'" :style="{ backgroundColor: c }"></button>
                        </template>
                    </div>
                </div>

                <!-- Fitur Lama: Kunci Posisi Object Canvas -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Status Kunci Posisi Object (Canvas)</label>
                    <select x-model="editForm.is_locked" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-bold">
                        <option :value="false">🔓 Terbuka (Unlocked - Posisi dapat digeser / diatur)</option>
                        <option :value="true">🔒 Terkunci (Locked - Posisi terkunci anti geser)</option>
                    </select>
                </div>

                @if (auth()->check() && auth()->user()->isSuperAdmin())
                <!-- FITUR BARU: Pembatasan Penempatan Box Berdasarkan Departemen (Khusus Superadmin) -->
                <div class="p-3 bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800/70 rounded space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-[10px] font-bold uppercase text-indigo-950 dark:text-indigo-300">
                            Departemen yang Diizinkan Menempatkan Box
                        </label>
                        <span class="text-[9px] font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/60 px-1.5 py-0.5 rounded border border-indigo-300 dark:border-indigo-700 font-mono">Multi-Dept Superadmin</span>
                    </div>

                    <!-- Checklist Multi-Departemen -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-44 overflow-y-auto p-2 bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-800 rounded">
                        <template x-for="dept in departments" :key="dept.id">
                            <label class="flex items-center gap-2 p-1.5 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded cursor-pointer transition select-none text-xs border border-transparent hover:border-indigo-200 dark:hover:border-indigo-900"
                                :class="isDeptSelected(dept.id) ? 'bg-indigo-50/60 dark:bg-indigo-950/40 border-indigo-300 dark:border-indigo-700' : ''">
                                <input type="checkbox" :value="dept.id" 
                                    :checked="isDeptSelected(dept.id)" 
                                    @change="toggleDeptSelection(dept.id)" 
                                    class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-700">
                                <div class="flex flex-col min-w-0">
                                    <span class="font-bold font-mono text-indigo-700 dark:text-indigo-300 text-[11px]" x-text="dept.code"></span>
                                    <span class="text-slate-500 dark:text-slate-400 text-[10px] truncate" x-text="dept.name"></span>
                                </div>
                            </label>
                        </template>
                    </div>

                    <div class="flex items-center justify-between text-[11px] pt-1">
                        <span class="text-slate-600 dark:text-slate-400">
                            Terpilih: <strong class="text-indigo-600 dark:text-indigo-400" x-text="(editForm.assigned_department_ids && editForm.assigned_department_ids.length > 0) ? (editForm.assigned_department_ids.length + ' Departemen') : 'Semua Departemen (Bebas)'"></strong>
                        </span>
                        <button type="button" @click="editForm.assigned_department_ids = []; editForm.assigned_department_id = null;" class="text-rose-600 hover:underline font-bold text-[10px] cursor-pointer" x-show="editForm.assigned_department_ids && editForm.assigned_department_ids.length > 0">
                            Reset ke Bebas (Semua Dept)
                        </button>
                    </div>

                    <p class="text-[10px] text-slate-600 dark:text-slate-400 leading-tight">
                        Pilih satu atau beberapa departemen jika rak ini dikhususkan (misal: FAT dan HRD). Jika tidak ada departemen yang dipilih, secara default <strong>box dari semua departemen boleh ditempatkan</strong> di sini.
                    </p>
                </div>
                @endif

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="px-3.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-300 text-xs rounded font-bold border border-slate-300 dark:border-slate-700 cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-1 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded border border-blue-700 shadow-xs cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>