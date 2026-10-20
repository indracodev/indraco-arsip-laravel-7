    <!-- Modal 2: Booking Slot Rak -->
    <div x-show="bookingModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 font-mono">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-5 max-w-md w-full space-y-3 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="bookmark-plus" class="w-4 h-4 text-amber-500"></i>
                    Form Booking Tempat Arsip
                </h3>
                <button @click="bookingModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form @submit.prevent="submitBooking()" class="space-y-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Pilih Departemen Pemohon <span class="text-rose-500">*</span></label>
                    <select x-model="bookingForm.department_id" required class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white font-medium">
                        <option value="">-- Pilih Departemen --</option>
                        <template x-for="dept in departments" :key="dept.id">
                            <option :value="dept.id" x-text="dept.code + ' - ' + dept.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-1">Catatan & Rencana Pengiriman Box <span class="text-rose-500">*</span></label>
                    <textarea x-model="bookingForm.booking_notes" rows="3" required placeholder="Contoh: Booking slot untuk pengiriman 15 Box Faktur Pajak Q1 2026 Dept Keuangan..." class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white placeholder-slate-400 font-sans"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="bookingModalOpen = false" class="px-3.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-300 text-xs rounded font-bold border border-slate-300 dark:border-slate-700 cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-1 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded border border-amber-700 shadow-xs cursor-pointer">Submit Booking</button>
                </div>
            </form>
        </div>
    </div>