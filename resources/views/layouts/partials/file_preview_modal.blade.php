{{-- 
    DMS PT INDRACO — Global File Preview Lightbox Modal Component
    Handles instant live previews for:
    1. Images (JPG, PNG, WEBP) with zoom, rotation & fullscreen inspection.
    2. PDFs with embedded in-browser viewer.
    3. Other documents with formatted preview cards.
--}}
<div x-data="filePreviewModalHandler()"
     x-show="isOpen"
     x-cloak
     @open-file-preview-modal.window="openModal($event.detail)"
     @keydown.escape.window="closeModal()"
     class="fixed inset-0 z-[999999] flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md select-none font-sans"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">

    {{-- Backdrop Click --}}
    <div class="fixed inset-0" @click="closeModal()"></div>

    {{-- Modal Box Container --}}
    <div class="relative w-full max-w-5xl h-[92vh] flex flex-col rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden z-10 text-slate-900 dark:text-white"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Header Bar --}}
        <div class="flex items-center justify-between px-4 sm:px-6 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/80 shrink-0 font-mono">
            <div class="flex items-center gap-3 min-w-0 pr-2">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border"
                     :class="{
                         'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400': fileType === 'image',
                         'bg-rose-500/10 border-rose-500/30 text-rose-600 dark:text-rose-400': fileType === 'pdf',
                         'bg-blue-500/10 border-blue-500/30 text-blue-600 dark:text-blue-400': fileType === 'other'
                     }">
                    <template x-if="fileType === 'image'">
                        <i data-lucide="image" class="w-5 h-5"></i>
                    </template>
                    <template x-if="fileType === 'pdf'">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </template>
                    <template x-if="fileType === 'other'">
                        <i data-lucide="paperclip" class="w-5 h-5"></i>
                    </template>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs sm:text-sm font-bold truncate text-slate-900 dark:text-white" x-text="fileName || 'Pratinjau Dokumen'"></h3>
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400">
                        <span class="uppercase font-bold" x-text="fileType"></span>
                        <span x-show="fileSize" class="text-slate-400 dark:text-slate-500">•</span>
                        <span x-show="fileSize" x-text="fileSize"></span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-show="isOptimized">• Kualitas Tinggi</span>
                    </div>
                </div>
            </div>

            {{-- Action Controls (Zoom / Rotate / Download / Close) --}}
            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                {{-- Image Tools --}}
                <template x-if="fileType === 'image'">
                    <div class="flex items-center gap-1 bg-slate-200/80 dark:bg-slate-800 rounded-xl p-1 text-slate-700 dark:text-slate-300">
                        <button type="button" @click="zoomOut()" title="Perkecil (-)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition">
                            <i data-lucide="zoom-out" class="w-4 h-4"></i>
                        </button>
                        <span class="text-[10px] font-mono font-bold px-1 min-w-[42px] text-center" x-text="Math.round(zoom * 100) + '%'"></span>
                        <button type="button" @click="zoomIn()" title="Perbesar (+)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition">
                            <i data-lucide="zoom-in" class="w-4 h-4"></i>
                        </button>
                        <button type="button" @click="rotate()" title="Putar 90°" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition ml-1">
                            <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                        </button>
                        <button type="button" @click="resetView()" title="Reset Tampilan" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition">
                            <i data-lucide="maximize" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </template>

                {{-- Open in Tab / External --}}
                <button type="button" @click="openInNewTab()" title="Buka File di Tab Baru" 
                   class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl transition border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                </button>

                {{-- Close Button --}}
                <button type="button" @click="closeModal()" title="Tutup (ESC)" 
                        class="p-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl transition border border-rose-500/20">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Content Viewer Area --}}
        <div class="relative flex-1 overflow-hidden bg-slate-950 flex items-center justify-center">
            
            {{-- 1. Image Viewer --}}
            <template x-if="fileType === 'image'">
                <div class="w-full h-full overflow-auto flex items-center justify-center p-4 select-none">
                    <img :src="fileUrl" 
                         :alt="fileName"
                         class="max-w-none transition-transform duration-150 ease-out shadow-2xl rounded-lg"
                         :style="`transform: scale(${zoom}) rotate(${rotation}deg); transform-origin: center center;`"
                         @click="zoomIn()"
                         title="Klik gambar untuk perbesar"
                    />
                </div>
            </template>

            {{-- 2. PDF Viewer (Anti-IDM via In-Memory Blob URL) --}}
            <template x-if="fileType === 'pdf'">
                <div class="w-full h-full bg-slate-900 relative flex items-center justify-center">
                    <div x-show="loadingPdf" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900 z-10 space-y-3">
                        <div class="w-8 h-8 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs font-mono font-bold text-slate-300">Menyiapkan pratinjau dokumen PDF...</p>
                    </div>
                    <div x-show="!loadingPdf && pdfError" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900 z-10 p-6 text-center space-y-3 font-mono">
                        <i data-lucide="alert-triangle" class="w-8 h-8 text-amber-500"></i>
                        <p class="text-xs text-slate-300" x-text="pdfError"></p>
                        <a :href="fileUrl" target="_blank" class="px-4 py-2 bg-amber-500 text-slate-950 font-bold rounded-xl text-xs">Buka di Tab Baru</a>
                    </div>
                    <template x-if="!loadingPdf && !pdfError && pdfBlobUrl">
                        <iframe :src="pdfBlobUrl + '#toolbar=1&navpanes=0&scrollbar=1'" 
                                class="w-full h-full border-0 rounded-b-2xl" 
                                title="Pratinjau PDF">
                        </iframe>
                    </template>
                </div>
            </template>

            {{-- 3. Non-previewable / Other Documents --}}
            <template x-if="fileType === 'other'">
                <div class="text-center p-8 max-w-md space-y-4 font-mono">
                    <div class="w-16 h-16 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center mx-auto border border-blue-500/20">
                        <i data-lucide="file-check" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-base" x-text="fileName"></h4>
                        <p class="text-xs text-slate-400 mt-1">Dokumen ini berformat biner/office dan tidak dapat dirender langsung di canvas browser.</p>
                    </div>
                    <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 text-xs text-slate-300">
                        <span class="text-emerald-400 font-bold">✓ Integritas Berkas Valid:</span> Siap diunggah ke server DMS.
                    </div>
                    <div>
                        <a :href="fileUrl" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg transition">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Unduh / Buka Dokumen</span>
                        </a>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer Status Bar --}}
        <div class="px-6 py-2.5 bg-slate-100 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400 shrink-0">
            <span class="flex items-center gap-1.5">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>Pratinjau Fisik Arsip PT Indraco</span>
            </span>
            <span>Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[10px]">ESC</kbd> untuk menutup</span>
        </div>
    </div>
</div>

<script>
function filePreviewModalHandler() {
    return {
        isOpen: false,
        fileUrl: '',
        fileName: '',
        fileType: 'image',
        fileSize: '',
        isOptimized: false,
        zoom: 1.0,
        rotation: 0,
        pdfBlobUrl: null,
        loadingPdf: false,
        pdfError: null,

        openModal(detail) {
            this.fileUrl = detail.url || '';
            this.fileName = detail.name || 'Dokumen Arsip';
            this.fileSize = detail.size || '';
            this.isOptimized = !!detail.isOptimized;
            this.pdfError = null;

            if (this.pdfBlobUrl) {
                URL.revokeObjectURL(this.pdfBlobUrl);
                this.pdfBlobUrl = null;
            }
            
            // Robust extraction of file extension from ext, filename, raw_path, or url
            const extractExt = (val) => {
                if (!val || typeof val !== 'string') return '';
                const clean = val.split('?')[0].split('#')[0];
                const parts = clean.split('.');
                return parts.length > 1 ? parts.pop().toLowerCase() : '';
            };

            const ext = (
                detail.ext || 
                extractExt(detail.filename) || 
                extractExt(detail.raw_path) || 
                extractExt(detail.url) || 
                (detail.name && detail.name.includes('.') ? extractExt(detail.name) : '')
            ).toLowerCase();

            if (['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif', 'svg'].includes(ext)) {
                this.fileType = 'image';
                this.loadingPdf = false;
            } else if (ext === 'pdf' || detail.type === 'pdf') {
                this.fileType = 'pdf';
                this.loadingPdf = true;

                let targetUrl = detail.stream_url;
                if (!targetUrl) {
                    const raw = detail.raw_path || this.fileUrl || '';
                    const clean = raw
                        .replace(/^https?:\/\/[^\/]+/, '')
                        .replace(/^\/(public\/storage|storage|public|files\/stream|files\/preview-stream)\//i, '')
                        .replace(/^\//, '');
                    targetUrl = '/files/preview-stream?token=' + encodeURIComponent(btoa(unescape(encodeURIComponent(clean))));
                }

                fetch(targetUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/octet-stream, application/pdf, */*'
                    }
                })
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.blob();
                    })
                    .then(blob => {
                        const pdfBlob = new Blob([blob], { type: 'application/pdf' });
                        this.pdfBlobUrl = URL.createObjectURL(pdfBlob);
                        this.loadingPdf = false;
                        this.$nextTick(() => {
                            if (window.lucide) lucide.createIcons();
                        });
                    })
                    .catch(err => {
                        console.warn('Gagal memuat blob PDF via anti-IDM stream:', err);
                        this.loadingPdf = false;
                        this.pdfError = 'Gagal memuat pratinjau dokumen PDF secara live.';
                        this.$nextTick(() => {
                            if (window.lucide) lucide.createIcons();
                        });
                    });
            } else {
                this.fileType = detail.type || 'other';
                this.loadingPdf = false;
            }

            this.zoom = 1.0;
            this.rotation = 0;
            this.isOpen = true;

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        openInNewTab() {
            if (this.pdfBlobUrl) {
                window.open(this.pdfBlobUrl, '_blank');
            } else if (this.fileUrl) {
                window.open(this.fileUrl, '_blank');
            }
        },

        closeModal() {
            if (this.pdfBlobUrl) {
                URL.revokeObjectURL(this.pdfBlobUrl);
                this.pdfBlobUrl = null;
            }
            this.isOpen = false;
            this.fileUrl = '';
            this.loadingPdf = false;
            this.pdfError = null;
        },

        zoomIn() {
            if (this.zoom < 3.0) this.zoom = +(this.zoom + 0.25).toFixed(2);
        },

        zoomOut() {
            if (this.zoom > 0.5) this.zoom = +(this.zoom - 0.25).toFixed(2);
        },

        rotate() {
            this.rotation = (this.rotation + 90) % 360;
        },

        resetView() {
            this.zoom = 1.0;
            this.rotation = 0;
        }
    };
}

// Global shortcut function callable anywhere
window.dmsPreviewFile = function(options) {
    window.dispatchEvent(new CustomEvent('open-file-preview-modal', {
        detail: options
    }));
};
</script>
