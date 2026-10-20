<!-- MODAL POPUP PERINGATAN BOX JATUH TEMPO & MENDEKATI EXP (SETIAP LOGIN & SETIAP 1 JAM) -->
<div x-data="expiryWarningModal()" 
     x-init="init()"
     @keydown.escape.window="if (isOpen) close()"
     x-cloak>
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-sm"
         style="display: none;">
        
        <div @click.away="close()"
             x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="bg-white dark:bg-slate-900 border-2 border-rose-500/50 dark:border-rose-500/60 rounded-2xl max-w-2xl w-full p-4 sm:p-6 shadow-2xl space-y-4 font-mono text-xs relative overflow-hidden">
            
            <!-- Top Highlight Accent Bar -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-rose-500 via-amber-500 to-rose-600"></div>

            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3 pt-1">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 rounded-xl shrink-0 animate-pulse">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                                Peringatan Masa Simpan Berkas
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white animate-pulse" x-text="count + ' Box Jatuh Tempo'"></span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Terdapat box berkas arsip yang telah melewati batas retensi atau mendekati jatuh tempo (H-30).
                        </p>
                    </div>
                </div>
                <button type="button" @click="close()" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition shrink-0" title="Tutup Modal (Esc)">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Table of Expired / Near-Expired Boxes -->
            <div class="max-h-64 sm:max-h-80 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-950/50 divide-y divide-slate-200 dark:divide-slate-800">
                <template x-for="item in items" :key="'exp-item-' + item.id">
                    <div class="p-3 hover:bg-white dark:hover:bg-slate-900/80 transition flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/40 text-[10px] font-bold" x-text="item.dept_code"></span>
                                <span class="font-extrabold text-slate-900 dark:text-white text-xs truncate" x-text="item.box_number"></span>
                                <span class="text-[10px] text-slate-400" x-text="'📍 ' + item.location"></span>
                            </div>
                            <div class="font-bold text-slate-700 dark:text-slate-300 text-xs truncate" x-text="item.title"></div>
                        </div>

                        <div class="flex items-center sm:flex-col sm:items-end justify-between sm:justify-center gap-1 shrink-0">
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono" x-text="'Exp: ' + item.expiry_date"></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider shadow-xs"
                                  :class="{
                                      'bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/40 animate-pulse': item.is_expired,
                                      'bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/40': !item.is_expired
                                  }"
                                  x-text="item.badge_text">
                            </span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2.5 pt-2 border-t border-slate-200 dark:border-slate-800">
                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                    💡 Pengajuan perpanjangan atau pemusnahan dapat dilakukan di <strong>[Form 3]</strong>.
                </span>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button type="button" 
                            @click="close()" 
                            class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-xs transition">
                        Tutup (Esc)
                    </button>
                    <button type="button" 
                            @click="goToJatuhTempoTab()" 
                            class="px-4 py-1.5 bg-gradient-to-r from-rose-600 via-amber-600 to-rose-600 hover:from-rose-500 hover:to-amber-500 text-white font-extrabold text-xs rounded-xl shadow-md border border-rose-500 transition flex items-center gap-1.5 cursor-pointer">
                        <span>Lihat Berkas Jatuh Tempo</span>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function expiryWarningModal() {
    return {
        isOpen: false,
        items: [],
        count: 0,
        loading: false,

        async checkAndShow(force = false) {
            try {
                const now = Date.now();
                const oneHourMs = 3600 * 1000;
                const lastTs = localStorage.getItem('dms_last_expiry_popup_ts');
                const sessionShown = sessionStorage.getItem('dms_session_expiry_shown');

                if (!force) {
                    // Jika sesi login ini sudah pernah memunculkan popup dan belum lewat 1 jam, lewati
                    if (sessionShown && lastTs && (now - Number(lastTs) < oneHourMs)) {
                        return;
                    }
                }

                this.loading = true;
                const res = await fetch('{{ route("api.archives.expiring_list") }}');
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.count > 0 && Array.isArray(data.items)) {
                        this.items = data.items;
                        this.count = data.count;
                        this.isOpen = true;
                        sessionStorage.setItem('dms_session_expiry_shown', '1');
                        localStorage.setItem('dms_last_expiry_popup_ts', String(now));
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    }
                }
            } catch (err) {
                console.warn('Gagal memuat data popup jatuh tempo:', err);
            } finally {
                this.loading = false;
            }
        },

        close() {
            this.isOpen = false;
            sessionStorage.setItem('dms_session_expiry_shown', '1');
            localStorage.setItem('dms_last_expiry_popup_ts', String(Date.now()));
        },

        goToJatuhTempoTab() {
            this.close();
            const targetUrl = '{{ route("destructions.index") }}?view=expiry&tab=h30&embed=1';

            // 1. Cek desktop MDI window PIC atau Admin di window lokal
            if (typeof this.openFormWindowWithCustom === 'function') {
                this.openFormWindowWithCustom('destructions', '[Form 3] Perpanjangan & Pemusnahan', 'trash-2', targetUrl);
                return;
            }
            if (window.openFormWindowWithCustom) {
                window.openFormWindowWithCustom('destructions', '[Form 3] Perpanjangan & Pemusnahan', 'trash-2', targetUrl);
                return;
            }
            // 2. Cek jika berada di dalam iframe MDI desktop
            if (window.parent && window.parent.openFormWindowWithCustom) {
                window.parent.openFormWindowWithCustom('destructions', '[Form 3] Perpanjangan & Pemusnahan', 'trash-2', targetUrl);
                return;
            }
            if (window.top && window.top.openFormWindowWithCustom) {
                window.top.openFormWindowWithCustom('destructions', '[Form 3] Perpanjangan & Pemusnahan', 'trash-2', targetUrl);
                return;
            }

            // 3. Fallback standalone URL redirect
            window.location.href = '{{ route("destructions.index", ["view" => "expiry", "tab" => "h30"]) }}';
        },

        init() {
            // Jalankan pengecekan pertama saat login / refresh
            setTimeout(() => {
                this.checkAndShow();
            }, 800);

            // Interval pengecekan berkala (setiap 60 detik mengecek apakah sudah mencapai interval 1 jam)
            setInterval(() => {
                this.checkAndShow();
            }, 60 * 1000);

            window.addEventListener('dms:show-expiry-popup', () => {
                this.checkAndShow(true);
            });
        }
    };
}
</script>
