<!-- ========================================================= -->
<!-- MODAL PRATINJAU & CETAK STIKER LABEL BOX (DMS PT INDRACO) -->
<!-- ========================================================= -->
<div 
    id="label-print-modal-wrapper"
    x-data="labelPrintModalComponent()" 
    x-show="isOpen" 
    x-cloak 
    @open-label-modal.window="open($event.detail)"
    @keydown.escape.window="close()" 
    class="fixed inset-0 z-[99999] flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-sm select-none"
    style="display: none;"
>
    <!-- Modal Dialog Window -->
    <div class="bg-white dark:bg-slate-900 border-2 border-amber-500/60 dark:border-amber-500/40 rounded-xl shadow-2xl max-w-4xl w-full h-[90vh] flex flex-col overflow-hidden text-slate-800 dark:text-slate-100">
        
        <!-- Modal Header Toolbar -->
        <div class="px-4 py-2.5 bg-gradient-to-r from-slate-900 via-slate-850 to-indigo-950 text-white border-b border-amber-500/40 flex items-center justify-between shrink-0 font-mono">
            <div class="flex items-center gap-2.5">
                <span class="p-1.5 bg-amber-500/20 text-amber-400 border border-amber-500/40 rounded">
                    <i data-lucide="tag" class="w-4 h-4"></i>
                </span>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white">
                        Pratinjau & Cetak Label Box (10x10 cm)
                    </h3>
                    <p class="text-[10px] text-slate-300 font-sans">
                        Pratinjau stiker kardus arsip. Tekan tombol cetak untuk mencetak langsung ke printer.
                    </p>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="printIframe()" 
                    :disabled="isLoading"
                    class="px-3.5 py-1.5 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded border border-amber-600 shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer disabled:opacity-50"
                    title="Cetak langsung stiker label ke printer"
                >
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-950"></i>
                    <span>Cetak Label Sekarang</span>
                </button>

                <button 
                    type="button" 
                    @click="close()" 
                    class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded border border-rose-700 shadow-sm flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
                    title="Tutup Modal Pratinjau Label"
                >
                    <i data-lucide="x" class="w-3.5 h-3.5 text-white"></i>
                    <span>Tutup</span>
                </button>
            </div>
        </div>

        <!-- Modal Body: Iframe that directly renders the existing print_sticker template -->
        <div class="flex-1 bg-slate-200 dark:bg-slate-950 p-3 overflow-hidden flex items-center justify-center relative">
            <div x-show="isLoading" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-slate-900/60 backdrop-blur-xs text-white z-10 font-mono text-xs">
                <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-amber-500"></i>
                <span>Menyiapkan label box...</span>
            </div>
            
            <iframe 
                id="label-print-iframe" 
                :src="iframeUrl" 
                @load="onIframeLoad()"
                class="w-full h-full rounded border border-slate-300 dark:border-slate-800 bg-white shadow-inner"
                style="background: #ffffff;"
            ></iframe>
        </div>
    </div>
</div>

<!-- Script Modal Label -->
<script>
    // Global Javascript Helper callable from anywhere (table row, toolbar, batch, or iframe)
    window.openLabelModal = function(archiveIdOrIds) {
        var payload = { ids: archiveIdOrIds };
        try {
            if (window.parent && window.parent !== window && typeof window.parent.openLabelModal === 'function') {
                window.parent.openLabelModal(archiveIdOrIds);
                return;
            }
        } catch (e) {}

        window.dispatchEvent(new CustomEvent('open-label-modal', { detail: payload }));
    };

    function labelPrintModalComponent() {
        return {
            isOpen: false,
            isLoading: false,
            iframeUrl: 'about:blank',

            init() {
                window.addEventListener('open-label-modal', (e) => {
                    var target = e.detail ? (e.detail.ids !== undefined ? e.detail.ids : (e.detail.id || e.detail)) : null;
                    this.open(target);
                });
            },

            open(ids) {
                if (ids && typeof ids === 'object' && !Array.isArray(ids)) {
                    ids = (ids.ids !== undefined) ? ids.ids : (ids.id || null);
                }

                let url = '';
                if (Array.isArray(ids)) {
                    url = '{{ route("archives.print_labels") }}?embed=1&ids=' + ids.join(',');
                } else if (ids) {
                    url = '{{ url("/archives") }}/' + ids + '/print-sticker?embed=1';
                } else {
                    url = '{{ route("archives.print_labels") }}?embed=1';
                }

                this.isLoading = true;
                this.iframeUrl = url;
                this.isOpen = true;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            },

            onIframeLoad() {
                this.isLoading = false;
                if (window.lucide) lucide.createIcons();
            },

            close() {
                this.isOpen = false;
                this.iframeUrl = 'about:blank';
            },

            printIframe() {
                const iframe = document.getElementById('label-print-iframe');
                if (!iframe || !iframe.contentWindow) return;
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }
        };
    }
</script>
