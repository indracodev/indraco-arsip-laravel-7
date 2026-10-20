<script>
// Keep raw PDF Document instance OUTSIDE Alpine.js reactive scope.
// Prevents "TypeError: Cannot read private member #i from an object whose class did not declare it"
let _dmsActivePdfDoc = null;

function filePreviewModalHandler() {
    return {
        isOpen: false,
        isFullscreen: false,
        fileUrl: '',
        fileName: '',
        fileType: '', // Initialized to empty string to prevent premature image rendering
        fileExt: '',
        fileSize: '',
        isOptimized: false,

        // Multi-file & Archive metadata
        fileList: [],
        activeFileIndex: 0,
        archiveBox: '',
        archiveTitle: '',
        archiveDept: '',

        // Image / Canvas Transform State
        zoom: 1.0,
        rotation: 0,
        panX: 0,
        panY: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,

        // PDF state (Mozilla PDF.js)
        pdfPageNum: 1,
        pdfTotalPages: 1,
        pdfScale: 1.25,
        pdfRotation: 0,
        pdfRendering: false,
        pdfPendingPage: null,
        loadingPdf: false,
        pdfError: null,

        // Text document state
        textContent: '',
        loadingText: false,
        textError: null,

        openModal(detail) {
            if (!detail) return;
            this.isFullscreen = false;
            this.archiveBox = detail.box_number || '';
            this.archiveTitle = detail.title || '';
            this.archiveDept = detail.department || '';

            // Clean up previous raw PDF doc if any
            if (_dmsActivePdfDoc) {
                try { _dmsActivePdfDoc.destroy(); } catch (e) {}
                _dmsActivePdfDoc = null;
            }

            // Handle multi-file array
            if (detail.files && Array.isArray(detail.files) && detail.files.length > 0) {
                this.fileList = detail.files;
                let targetIdx = 0;
                if (typeof detail.initialIndex === 'number' && detail.initialIndex >= 0 && detail.initialIndex < detail.files.length) {
                    targetIdx = detail.initialIndex;
                } else if (detail.raw_path || detail.url || detail.name) {
                    const found = detail.files.findIndex(f => 
                        (detail.raw_path && f.raw_path === detail.raw_path) ||
                        (detail.url && f.url === detail.url) ||
                        (detail.name && f.name === detail.name)
                    );
                    if (found !== -1) targetIdx = found;
                }
                this.activeFileIndex = targetIdx;
                this.loadFile(this.fileList[targetIdx]);
            } else {
                this.fileList = [detail];
                this.activeFileIndex = 0;
                this.loadFile(detail);
            }

            this.isOpen = true;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        selectFileIndex(idx) {
            if (idx === this.activeFileIndex || !this.fileList[idx]) return;
            this.activeFileIndex = idx;
            this.loadFile(this.fileList[idx]);
        },

        loadFile(fileItem) {
            if (!fileItem) return;
            this.fileUrl = fileItem.url || '';
            this.fileName = fileItem.name || fileItem.filename || 'Dokumen Arsip';
            this.fileSize = fileItem.size || '';
            this.isOptimized = !!fileItem.isOptimized;
            this.pdfError = null;
            this.textError = null;
            this.textContent = '';

            // Reset viewer transforms
            this.pdfRotation = 0;
            this.resetView();

            // Destroy previous raw PDF document instance
            if (_dmsActivePdfDoc) {
                try { _dmsActivePdfDoc.destroy(); } catch (e) {}
                _dmsActivePdfDoc = null;
            }

            // Extract file extension
            const extractExt = (val) => {
                if (!val || typeof val !== 'string') return '';
                const clean = val.split('?')[0].split('#')[0];
                const parts = clean.split('.');
                return parts.length > 1 ? parts.pop().toLowerCase() : '';
            };

            const ext = (
                fileItem.ext || 
                extractExt(fileItem.filename) || 
                extractExt(fileItem.raw_path) || 
                extractExt(fileItem.url) || 
                (fileItem.name && fileItem.name.includes('.') ? extractExt(fileItem.name) : '')
            ).toLowerCase();

            this.fileExt = ext;

            if (['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif', 'svg'].includes(ext)) {
                this.fileType = 'image';
                this.loadingPdf = false;
            } else if (ext === 'pdf' || fileItem.type === 'pdf') {
                this.fileType = 'pdf';
                this.loadPdf(fileItem);
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
                this.fileType = fileItem.type || 'other';
                this.loadingPdf = false;
            }

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        // --- Mozilla PDF.js Engine Methods ---
        async loadPdf(detail) {
            this.loadingPdf = true;
            this.pdfError = null;
            this.pdfPageNum = 1;
            this.pdfTotalPages = 1;
            this.pdfScale = 1.0;

            try {
                if (!window.pdfjsLib) {
                    throw new Error('Pustaka PDF.js belum siap dimuat.');
                }

                let loadingTask = null;

                if (detail.file && typeof detail.file.arrayBuffer === 'function') {
                    // Local input file (e.g. from file-uploader before submission)
                    const buffer = await detail.file.arrayBuffer();
                    loadingTask = window.pdfjsLib.getDocument({ data: buffer });
                } else if (this.fileUrl && this.fileUrl.startsWith('blob:')) {
                    // In-memory Blob URL
                    const res = await fetch(this.fileUrl);
                    const buffer = await res.arrayBuffer();
                    loadingTask = window.pdfjsLib.getDocument({ data: buffer });
                } else {
                    // Remote / Stored file on server — POST request anti-IDM
                    const rawPath = detail.raw_path || detail.url || this.fileUrl || '';
                    const cleanPath = rawPath
                        .replace(/^https?:\/\/[^\/]+/, '')
                        .replace(/^\/(public\/storage|storage|public|files\/stream|files\/preview-stream)\//i, '')
                        .replace(/^\//, '');

                    if (!cleanPath) {
                        throw new Error('Jalur berkas fisik tidak ditemukan.');
                    }

                    const token = btoa(unescape(encodeURIComponent(cleanPath)));

                    const formData = new FormData();
                    formData.append('token', token);

                    const res = await fetch('/files/preview-stream', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status + ' — gagal mengambil file dari server');
                    const json = await res.json();
                    if (!json.success || !json.data) {
                        throw new Error(json.message || 'Gagal memuat isi berkas dari server');
                    }
                    const binaryString = atob(json.data);
                    const len = binaryString.length;
                    const bytes = new Uint8Array(len);
                    for (let i = 0; i < len; i++) {
                        bytes[i] = binaryString.charCodeAt(i);
                    }
                    loadingTask = window.pdfjsLib.getDocument({ data: bytes });
                }

                const doc = await loadingTask.promise;
                _dmsActivePdfDoc = doc;
                this.pdfTotalPages = doc.numPages;
                this.loadingPdf = false;

                this.$nextTick(() => {
                    setTimeout(() => {
                        this.renderPdfPage(this.pdfPageNum, true);
                        if (window.lucide) lucide.createIcons();
                    }, 50);
                });
            } catch (err) {
                console.error('PDF.js loading error:', err);
                this.loadingPdf = false;
                this.pdfError = 'Gagal memuat pratinjau dokumen PDF: ' + (err.message || 'Format tidak valid');
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        },


        renderPdfPage(num, autoFit = false) {
            if (!_dmsActivePdfDoc) return;
            this.pdfRendering = true;

            _dmsActivePdfDoc.getPage(num).then(page => {
                const canvas = document.getElementById('dms-pdf-canvas');
                const scrollArea = document.getElementById('dms-pdf-scroll-area');
                if (!canvas) {
                    this.pdfRendering = false;
                    return;
                }

                const baseRotation = (page.rotate || 0);
                const totalRotation = (baseRotation + this.pdfRotation) % 360;

                if (autoFit && scrollArea) {
                    const unscaled = page.getViewport({ scale: 1.0, rotation: totalRotation });
                    // Stretch horizontal (kanan-kiri) menyesuaikan lebar modal
                    // Jika tinggi dokumen melebihi modal, dokumen dapat di-scroll vertikal secara penuh
                    const padding = window.innerWidth < 640 ? 32 : 48;
                    const availW = Math.max(280, scrollArea.clientWidth - padding);
                    const fitW = availW / unscaled.width;
                    this.pdfScale = +(Math.max(0.4, fitW)).toFixed(2);
                }

                const ctx = canvas.getContext('2d');
                const viewport = page.getViewport({ scale: this.pdfScale, rotation: totalRotation });
                const outputScale = window.devicePixelRatio || 1;

                canvas.width = Math.floor(viewport.width * outputScale);
                canvas.height = Math.floor(viewport.height * outputScale);
                canvas.style.width = Math.floor(viewport.width) + 'px';
                canvas.style.height = Math.floor(viewport.height) + 'px';

                const transform = outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null;

                const renderContext = {
                    canvasContext: ctx,
                    transform: transform,
                    viewport: viewport
                };

                const renderTask = page.render(renderContext);
                renderTask.promise.then(() => {
                    this.pdfRendering = false;
                    if (this.pdfPendingPage !== null) {
                        const pending = this.pdfPendingPage;
                        this.pdfPendingPage = null;
                        this.renderPdfPage(pending);
                    }
                }).catch(err => {
                    this.pdfRendering = false;
                    console.warn('PDF.js page render warning:', err);
                });
            }).catch(err => {
                this.pdfRendering = false;
                console.error('PDF.js getPage error:', err);
            });
        },

        pdfQueueRenderPage(num) {
            if (this.pdfRendering) {
                this.pdfPendingPage = num;
            } else {
                this.renderPdfPage(num);
            }
        },

        pdfPrevPage() {
            if (this.pdfPageNum <= 1) return;
            this.pdfPageNum--;
            this.pdfQueueRenderPage(this.pdfPageNum);
        },

        pdfNextPage() {
            if (this.pdfPageNum >= this.pdfTotalPages) return;
            this.pdfPageNum++;
            this.pdfQueueRenderPage(this.pdfPageNum);
        },

        pdfZoomIn() {
            if (this.pdfScale < 4.0) {
                this.pdfScale = +(this.pdfScale + 0.2).toFixed(2);
                this.pdfQueueRenderPage(this.pdfPageNum);
            }
        },

        pdfZoomOut() {
            if (this.pdfScale > 0.4) {
                this.pdfScale = +(this.pdfScale - 0.2).toFixed(2);
                this.pdfQueueRenderPage(this.pdfPageNum);
            }
        },

        pdfResetZoom() {
            this.pdfRotation = 0;
            this.renderPdfPage(this.pdfPageNum, true);
        },

        rotatePdfCw() {
            this.pdfRotation = (this.pdfRotation + 90) % 360;
            this.pdfQueueRenderPage(this.pdfPageNum);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        rotatePdfCcw() {
            this.pdfRotation = (this.pdfRotation + 270) % 360;
            this.pdfQueueRenderPage(this.pdfPageNum);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        flipPdf180() {
            this.pdfRotation = (this.pdfRotation + 180) % 360;
            this.pdfQueueRenderPage(this.pdfPageNum);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        rotatePdf() {
            this.rotatePdfCw();
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

        rotateCw() {
            this.rotation = (this.rotation + 90) % 360;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        rotateCcw() {
            this.rotation = (this.rotation + 270) % 360;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        flip180() {
            this.rotation = (this.rotation + 180) % 360;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        rotate() {
            this.rotateCw();
        },

        resetView() {
            this.zoom = 1.0;
            this.rotation = 0;
            this.panX = 0;
            this.panY = 0;
            this.isDragging = false;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
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
            if (this.fileUrl) {
                window.open(this.fileUrl, '_blank');
            }
        },

        downloadCurrentFile() {
            if (!this.fileUrl) return;
            const a = document.createElement('a');
            a.href = this.fileUrl;
            a.download = this.fileName || 'dokumen';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        },

        closeModal() {
            if (_dmsActivePdfDoc) {
                try { _dmsActivePdfDoc.destroy(); } catch (e) {}
                _dmsActivePdfDoc = null;
            }
            this.isOpen = false;
            this.fileUrl = '';
            this.fileType = '';
            this.loadingPdf = false;
            this.pdfError = null;
            this.pdfRotation = 0;
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
window.dmsPreviewArchive = window.dmsPreviewFile;
</script>
