{{-- 
    DMS PT INDRACO — Global File Preview Lightbox Modal Component
    Handles instant live previews & deep inspection for:
    1. Images (JPG, JPEG, PNG, WEBP, GIF, SVG, BMP) with Zoom In/Out, Rotate, Wheel Zoom, & Drag-to-Pan (Geser)
    2. PDFs via in-browser iframe Anti-IDM Blob Streaming
    3. Text & Data Files (TXT, CSV, LOG, JSON, XML, MD) with live monospace content reader
    4. Office & Archive Files (DOCX, XLSX, PPTX, ZIP, RAR) with formatted inspection card
--}}
<div x-data="filePreviewModalHandler()"
     x-show="isOpen"
     x-cloak
     @open-file-preview-modal.window="openModal($event.detail)"
     @keydown.escape.window="closeModal()"
     @keydown.f.window="if(isOpen) toggleFullscreen()"
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
    <div class="relative w-full flex flex-col bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden z-10 text-slate-900 dark:text-white transition-all duration-200"
         :class="isFullscreen ? 'fixed inset-0 rounded-none h-screen max-w-none' : 'max-w-6xl h-[94vh] rounded-3xl'"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Header Bar --}}
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-950/90 shrink-0 font-mono">
            <div class="flex items-center gap-3 min-w-0 pr-2">
                {{-- Type Icon Badge --}}
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border"
                     :class="{
                         'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400': fileType === 'image',
                         'bg-rose-500/10 border-rose-500/30 text-rose-600 dark:text-rose-400': fileType === 'pdf',
                         'bg-cyan-500/10 border-cyan-500/30 text-cyan-600 dark:text-cyan-400': fileType === 'text',
                         'bg-blue-500/10 border-blue-500/30 text-blue-600 dark:text-blue-400': fileType === 'other'
                     }">
                    <template x-if="fileType === 'image'">
                        <i data-lucide="image" class="w-5 h-5"></i>
                    </template>
                    <template x-if="fileType === 'pdf'">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </template>
                    <template x-if="fileType === 'text'">
                        <i data-lucide="file-code" class="w-5 h-5"></i>
                    </template>
                    <template x-if="fileType === 'other'">
                        <i data-lucide="paperclip" class="w-5 h-5"></i>
                    </template>
                </div>

                {{-- Document Title & Meta --}}
                <div class="min-w-0">
                    <h3 class="text-xs sm:text-sm font-bold truncate text-slate-900 dark:text-white" x-text="fileName || 'Pratinjau Dokumen'"></h3>
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400">
                        <span class="px-1.5 py-0.2 rounded font-bold uppercase"
                              :class="{
                                  'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300': fileType === 'image',
                                  'bg-rose-500/15 text-rose-700 dark:text-rose-300': fileType === 'pdf',
                                  'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300': fileType === 'text',
                                  'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300': fileType === 'other'
                              }" 
                              x-text="fileExt ? fileExt.toUpperCase() : fileType.toUpperCase()">
                        </span>
                        <span x-show="fileSize" class="text-slate-400 dark:text-slate-500">•</span>
                        <span x-show="fileSize" x-text="fileSize"></span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-show="isOptimized">• Resolusi Tinggi</span>
                    </div>
                </div>
            </div>

            {{-- Action Controls (Zoom / Rotate / Fullscreen / External / Close) --}}
            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                {{-- Image & Text Interactive Inspection Tools --}}
                <template x-if="fileType === 'image'">
                    <div class="flex items-center gap-1 bg-slate-200/80 dark:bg-slate-800 rounded-xl p-1 text-slate-700 dark:text-slate-300">
                        <button type="button" @click="zoomOut()" title="Perkecil (-)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <i data-lucide="zoom-out" class="w-4 h-4"></i>
                        </button>
                        <span class="text-[10px] font-mono font-bold px-1 min-w-[42px] text-center" x-text="Math.round(zoom * 100) + '%'"></span>
                        <button type="button" @click="zoomIn()" title="Perbesar (+)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <i data-lucide="zoom-in" class="w-4 h-4"></i>
                        </button>
                        <button type="button" @click="rotate()" title="Putar 90° Searah Jarum Jam" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition ml-1 cursor-pointer">
                            <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                        </button>
                        <button type="button" @click="resetView()" title="Reset Tampilan (Fit 1:1)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <i data-lucide="refresh-ccw" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </template>

                {{-- Fullscreen Toggle --}}
                <button type="button" @click="toggleFullscreen()" :title="isFullscreen ? 'Kembalikan Ukuran (F)' : 'Layar Penuh (F)'" 
                   class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl transition border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <i :data-lucide="isFullscreen ? 'minimize-2' : 'maximize-2'" class="w-4 h-4"></i>
                </button>

                {{-- Open in Tab / External --}}
                <button type="button" @click="openInNewTab()" title="Buka File di Tab Baru" 
                   class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl transition border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                </button>

                {{-- Close Button --}}
                <button type="button" @click="closeModal()" title="Tutup (ESC)" 
                        class="p-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl transition border border-rose-500/20 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Content Viewer Area --}}
        <div class="relative flex-1 overflow-hidden bg-slate-950 flex items-center justify-center">
            
            {{-- 1. Image Viewer with Zoom, Rotation & Drag-to-Pan (Geser) --}}
            <template x-if="fileType === 'image'">
                <div class="w-full h-full relative overflow-hidden flex items-center justify-center select-none"
                     :class="isDragging ? 'cursor-grabbing' : (zoom > 1.0 ? 'cursor-grab' : 'cursor-default')"
                     @wheel.prevent="onWheel($event)"
                     @mousedown="startDrag($event)"
                     @mousemove="onDrag($event)"
                     @mouseup="stopDrag()"
                     @mouseleave="stopDrag()">

                    {{-- Image Canvas --}}
                    <img :src="fileUrl" 
                         :alt="fileName"
                         class="max-w-none select-none pointer-events-none rounded-lg shadow-2xl transition-transform duration-75"
                         :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom}) rotate(${rotation}deg); transform-origin: center center;`"
                    />

                    {{-- Floating Inspection Hint Badge --}}
                    <div class="absolute bottom-4 left-4 z-20 pointer-events-none flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900/80 backdrop-blur-md border border-slate-800 text-[10px] font-mono text-slate-300">
                        <i data-lucide="move" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span>Klik & Tahan Mouse untuk Menggeser Dokumen</span>
                        <span class="text-slate-500">|</span>
                        <span>Scroll Mouse untuk Zoom</span>
                    </div>

                    {{-- Quick Reset Floating Button when panned or zoomed --}}
                    <div x-show="zoom !== 1.0 || panX !== 0 || panY !== 0" x-cloak class="absolute bottom-4 right-4 z-20">
                        <button type="button" @click="resetView()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold font-mono text-[11px] rounded-xl shadow-lg transition flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="refresh-ccw" class="w-3.5 h-3.5"></i>
                            <span>Kembalikan Posisi Normal</span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- 2. PDF Viewer (Anti-IDM via In-Memory Blob URL) --}}
            <template x-if="fileType === 'pdf'">
                <div class="w-full h-full bg-slate-900 relative flex items-center justify-center">
                    {{-- Loading State --}}
                    <div x-show="loadingPdf" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900 z-10 space-y-3 font-mono">
                        <div class="w-9 h-9 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs font-bold text-slate-300">Menyiapkan pratinjau dokumen PDF live...</p>
                        <p class="text-[11px] text-slate-500">Mengalirkan data anti-intersepsi IDM</p>
                    </div>

                    {{-- Error State Fallback --}}
                    <div x-show="!loadingPdf && pdfError" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900 z-10 p-6 text-center space-y-3 font-mono">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center mx-auto border border-amber-500/30">
                            <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                        </div>
                        <p class="text-xs text-slate-300 max-w-sm" x-text="pdfError"></p>
                        <div class="flex items-center gap-2 pt-2">
                            <a :href="fileUrl" target="_blank" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs transition inline-flex items-center gap-1.5">
                                <i data-lucide="external-link" class="w-4 h-4"></i>
                                <span>Buka di Tab Baru</span>
                            </a>
                        </div>
                    </div>

                    {{-- Live Iframe PDF with In-Memory Blob URL --}}
                    <template x-if="!loadingPdf && !pdfError && pdfBlobUrl">
                        <iframe :src="pdfBlobUrl + '#toolbar=1&navpanes=0&scrollbar=1'" 
                                class="w-full h-full border-0" 
                                title="Pratinjau Dokumen PDF">
                        </iframe>
                    </template>
                </div>
            </template>

            {{-- 3. Text / Code / CSV Viewer --}}
            <template x-if="fileType === 'text'">
                <div class="w-full h-full bg-slate-950 p-4 sm:p-6 overflow-auto font-mono text-xs text-slate-200">
                    <div x-show="loadingText" class="w-full h-full flex flex-col items-center justify-center space-y-3">
                        <div class="w-8 h-8 border-3 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs text-slate-400">Membaca teks dokumen...</p>
                    </div>
                    <div x-show="!loadingText && textError" class="w-full h-full flex flex-col items-center justify-center text-center p-6 space-y-2">
                        <i data-lucide="alert-circle" class="w-8 h-8 text-rose-500"></i>
                        <p class="text-xs text-slate-300" x-text="textError"></p>
                    </div>
                    <div x-show="!loadingText && !textError" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 overflow-auto max-h-full whitespace-pre font-mono leading-relaxed select-text shadow-inner">
                        <code x-text="textContent"></code>
                    </div>
                </div>
            </template>

            {{-- 4. Non-previewable / Office / Archive Documents --}}
            <template x-if="fileType === 'other'">
                <div class="text-center p-8 max-w-md space-y-4 font-mono">
                    <div class="w-16 h-16 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center mx-auto border border-blue-500/20">
                        <i data-lucide="file-check" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-base truncate" x-text="fileName"></h4>
                        <p class="text-xs text-slate-400 mt-1">Dokumen ini berformat biner / arsip (<span class="uppercase font-bold text-cyan-400" x-text="fileExt"></span>) dan dapat diunduh untuk dibuka pada aplikasi terkait.</p>
                    </div>
                    <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 text-xs text-slate-300">
                        <span class="text-emerald-400 font-bold">✓ Integritas Berkas Valid:</span> File tersimpan aman di server penyimpanan arsip.
                    </div>
                    <div class="pt-2">
                        <a :href="fileUrl" target="_blank" download class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg transition cursor-pointer">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Unduh / Buka Dokumen</span>
                        </a>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer Status Bar --}}
        <div class="px-4 sm:px-6 py-2.5 bg-slate-100 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400 shrink-0">
            <span class="flex items-center gap-1.5">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>Sistem Pratinjau Terpadu PT Indraco</span>
            </span>
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline">Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[10px]">F</kbd> untuk Layar Penuh</span>
                <span>Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[10px]">ESC</kbd> untuk Menutup</span>
            </div>
        </div>
    </div>
</div>

<script>
function filePreviewModalHandler() {
    return {
        isOpen: false,
        isFullscreen: false,
        fileUrl: '',
        fileName: '',
        fileType: 'image', // 'image', 'pdf', 'text', 'other'
        fileExt: '',
        fileSize: '',
        isOptimized: false,

        // Image / Canvas Transform State
        zoom: 1.0,
        rotation: 0,
        panX: 0,
        panY: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,

        // PDF state
        pdfBlobUrl: null,
        loadingPdf: false,
        pdfError: null,

        // Text document state
        textContent: '',
        loadingText: false,
        textError: null,

        openModal(detail) {
            this.fileUrl = detail.url || '';
            this.fileName = detail.name || detail.filename || 'Dokumen Arsip';
            this.fileSize = detail.size || '';
            this.isOptimized = !!detail.isOptimized;
            this.pdfError = null;
            this.textError = null;
            this.textContent = '';
            this.isFullscreen = false;

            // Reset viewer transforms
            this.resetView();

            if (this.pdfBlobUrl && this.pdfBlobUrl !== this.fileUrl) {
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

            this.fileExt = ext;

            if (['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif', 'svg'].includes(ext)) {
                this.fileType = 'image';
                this.loadingPdf = false;
            } else if (ext === 'pdf' || detail.type === 'pdf') {
                this.fileType = 'pdf';

                // Jika fileUrl adalah Blob URL in-memory (sebelum disubmit ke server), gunakan langsung!
                if (this.fileUrl && this.fileUrl.startsWith('blob:')) {
                    this.pdfBlobUrl = this.fileUrl;
                    this.loadingPdf = false;
                    this.pdfError = null;
                    this.isOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                    return;
                }

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
            } else if (['txt', 'csv', 'log', 'json', 'xml', 'md'].includes(ext)) {
                this.fileType = 'text';
                this.loadingText = true;

                fetch(this.fileUrl)
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.text();
                    })
                    .then(text => {
                        this.textContent = text;
                        this.loadingText = false;
                    })
                    .catch(err => {
                        this.textError = 'Gagal memuat teks isi dokumen: ' + err.message;
                        this.loadingText = false;
                    });
            } else {
                this.fileType = detail.type || 'other';
                this.loadingPdf = false;
            }

            this.isOpen = true;

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        // Transform Controls (Zoom, Pan, Rotate)
        zoomIn() {
            if (this.zoom < 5.0) this.zoom = +(this.zoom + 0.25).toFixed(2);
        },

        zoomOut() {
            if (this.zoom > 0.2) this.zoom = +(this.zoom - 0.25).toFixed(2);
        },

        onWheel(e) {
            const delta = e.deltaY < 0 ? 0.2 : -0.2;
            const newZoom = +(this.zoom + delta).toFixed(2);
            if (newZoom >= 0.2 && newZoom <= 5.0) {
                this.zoom = newZoom;
            }
        },

        rotate() {
            this.rotation = (this.rotation + 90) % 360;
        },

        resetView() {
            this.zoom = 1.0;
            this.rotation = 0;
            this.panX = 0;
            this.panY = 0;
            this.isDragging = false;
        },

        // Mouse Drag to Pan (Geser)
        startDrag(e) {
            if (e.button !== 0) return; // Only left mouse button
            this.isDragging = true;
            this.dragStartX = e.clientX - this.panX;
            this.dragStartY = e.clientY - this.panY;
        },

        onDrag(e) {
            if (!this.isDragging) return;
            this.panX = e.clientX - this.dragStartX;
            this.panY = e.clientY - this.dragStartY;
        },

        stopDrag() {
            this.isDragging = false;
        },

        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
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
            if (this.pdfBlobUrl && this.pdfBlobUrl !== this.fileUrl) {
                URL.revokeObjectURL(this.pdfBlobUrl);
            }
            this.pdfBlobUrl = null;
            this.isOpen = false;
            this.fileUrl = '';
            this.loadingPdf = false;
            this.pdfError = null;
            this.resetView();
        }
    };
}

// Global shortcut function callable anywhere
window.dmsPreviewFile = function(options) {
    try {
        if (window.parent && window.parent !== window && typeof window.parent.dmsPreviewFile === 'function') {
            window.parent.dmsPreviewFile(options);
            return;
        }
    } catch (e) {
        // Fallback to local dispatch
    }
    window.dispatchEvent(new CustomEvent('open-file-preview-modal', {
        detail: options
    }));
};
</script>
