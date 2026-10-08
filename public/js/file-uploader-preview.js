/**
 * DMS PT INDRACO — File Upload, Validation, Live Preview & Smart Compression Engine
 * Features:
 * 1. Client-side file size validation (Standard 2MB limit).
 * 2. High-fidelity image compression (Canvas 2D max 2400px, 0.88 quality, preserving sharp text & stamps).
 * 3. Transparent replacement of input.files via DataTransfer API.
 * 4. Live interactive preview (Image lightbox thumbnail, PDF modal viewer, Office/ZIP badges).
 */

window.DmsFileOptimizer = {
    // Standard size limit (2 MB = 2,097,152 bytes)
    DEFAULT_MAX_BYTES: 2 * 1024 * 1024,

    formatBytes: function(bytes, decimals = 1) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    },

    getFileCategory: function(fileName, mimeType) {
        const ext = (fileName || '').split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'webp', 'bmp'].includes(ext) || (mimeType && mimeType.startsWith('image/'))) {
            return 'image';
        }
        if (ext === 'pdf' || mimeType === 'application/pdf') {
            return 'pdf';
        }
        if (['doc', 'docx'].includes(ext)) {
            return 'doc';
        }
        if (['xls', 'xlsx', 'csv'].includes(ext)) {
            return 'xls';
        }
        if (['zip', 'rar', '7z'].includes(ext)) {
            return 'zip';
        }
        return 'other';
    },

    /**
     * Smart High-Fidelity Image Compression
     * Prioritizes pin-sharp text clarity while drastically reducing file size.
     */
    compressImage: async function(file, options = {}) {
        const maxDimension = options.maxDimension || 2400; // 2400px is ample for crisp A4 documents (300 DPI)
        const quality = options.quality || 0.88;          // 88% JPEG quality preserves fine edges & high contrast

        return new Promise((resolve) => {
            // If browser doesn't support FileReader or Canvas, return original
            if (!window.FileReader || !window.HTMLCanvasElement) {
                return resolve({ file: file, wasCompressed: false });
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    let width = img.naturalWidth || img.width;
                    let height = img.naturalHeight || img.height;

                    // Calculate downscaled dimensions only if larger than maxDimension
                    if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = Math.round((height * maxDimension) / width);
                            width = maxDimension;
                        } else {
                            width = Math.round((width * maxDimension) / height);
                            height = Math.round(maxDimension);
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');

                    // High-quality image rendering settings for sharp document text
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';

                    // Solid white canvas background (prevents transparent PNGs turning pitch black in JPEG)
                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillRect(0, 0, width, height);

                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            return resolve({ file: file, wasCompressed: false });
                        }

                        // Only replace file if the compressed blob is actually smaller
                        if (blob.size < file.size) {
                            const newFileName = file.name.replace(/\.[^.]+$/, '.jpg');
                            const compressedFile = new File([blob], newFileName, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });

                            resolve({
                                file: compressedFile,
                                originalSize: file.size,
                                compressedSize: blob.size,
                                wasCompressed: true,
                                width: width,
                                height: height
                            });
                        } else {
                            resolve({
                                file: file,
                                originalSize: file.size,
                                compressedSize: file.size,
                                wasCompressed: false,
                                width: width,
                                height: height
                            });
                        }
                    }, 'image/jpeg', quality);
                };

                img.onerror = function() {
                    resolve({ file: file, wasCompressed: false });
                };

                img.src = event.target.result;
            };

            reader.onerror = function() {
                resolve({ file: file, wasCompressed: false });
            };

            reader.readAsDataURL(file);
        });
    }
};

/**
 * Alpine.js File Uploader Component
 */
function dmsFileUploader(config) {
    return {
        inputName: config.name || 'file',
        inputId: config.id || ('dms_file_' + Math.random().toString(36).substr(2, 9)),
        accept: config.accept || '.pdf,.jpg,.jpeg,.png',
        required: !!config.required,
        maxSizeMB: config.maxSizeMB || 2,
        label: config.label || 'Unggah Dokumen',
        helperText: config.helperText || '',
        existingUrl: config.existingUrl || null,
        existingName: config.existingName || 'File Dokumen Tersimpan',

        // Dynamic State
        file: null,
        fileUrl: null,
        fileName: '',
        fileCategory: '',
        fileSizeFormatted: '',
        isOptimizing: false,
        wasOptimized: false,
        originalSizeFormatted: '',
        errorMessage: '',
        isOverLimit: false,
        hasFile: false,

        init() {
            // Re-render lucide icons when state changes
            this.$watch('hasFile', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('isOptimizing', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
        },

        async handleFileInput(event) {
            const input = event.target;
            if (!input.files || !input.files.length) {
                this.clearFile();
                return;
            }

            let selectedFile = input.files[0];
            const maxBytes = this.maxSizeMB * 1024 * 1024;
            const category = window.DmsFileOptimizer.getFileCategory(selectedFile.name, selectedFile.type);

            this.errorMessage = '';
            this.isOverLimit = false;
            this.wasOptimized = false;
            this.fileCategory = category;

            // 1. Process Images with Smart High-Fidelity Compression
            if (category === 'image') {
                const originalBytes = selectedFile.size;

                // Compress if larger than 1.5MB or if explicitly > 2MB
                if (originalBytes > 1.5 * 1024 * 1024) {
                    this.isOptimizing = true;
                    try {
                        const result = await window.DmsFileOptimizer.compressImage(selectedFile, {
                            maxDimension: 2400,
                            quality: 0.88
                        });

                        if (result.wasCompressed) {
                            selectedFile = result.file;
                            this.wasOptimized = true;
                            this.originalSizeFormatted = window.DmsFileOptimizer.formatBytes(result.originalSize);

                            // Transparently update input.files via DataTransfer
                            if (window.DataTransfer) {
                                const dt = new DataTransfer();
                                dt.items.add(result.file);
                                input.files = dt.files;
                            }
                        }
                    } catch (err) {
                        console.warn('Image compression bypassed:', err);
                    } finally {
                        this.isOptimizing = false;
                    }
                }
            }

            // 2. Validate Final File Size
            if (selectedFile.size > maxBytes) {
                this.isOverLimit = true;
                const formattedActual = window.DmsFileOptimizer.formatBytes(selectedFile.size);
                
                if (category === 'pdf') {
                    this.errorMessage = `Ukuran PDF (${formattedActual}) melebihi batas maksimal ${this.maxSizeMB} MB. Silakan kompres berkas PDF Anda terlebih dahulu.`;
                } else if (category === 'image') {
                    this.errorMessage = `Ukuran gambar (${formattedActual}) masih melebihi batas maksimal ${this.maxSizeMB} MB. Silakan gunakan foto yang lebih padat.`;
                } else {
                    this.errorMessage = `Ukuran berkas (${formattedActual}) melebihi batas maksimal ${this.maxSizeMB} MB.`;
                }
            }

            // 3. Set Active File Info & Object URL Preview
            this.file = selectedFile;
            this.fileName = selectedFile.name;
            this.fileSizeFormatted = window.DmsFileOptimizer.formatBytes(selectedFile.size);
            this.hasFile = true;

            // Revoke previous URL to prevent memory leaks
            if (this.fileUrl && this.fileUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.fileUrl);
            }
            this.fileUrl = URL.createObjectURL(selectedFile);

            // Dispatch custom event to notify parent containers
            this.$dispatch('file-change', {
                name: this.inputName,
                hasFile: true,
                isOverLimit: this.isOverLimit,
                file: selectedFile
            });
        },

        clearFile() {
            const input = document.getElementById(this.inputId);
            if (input) input.value = '';

            if (this.fileUrl && this.fileUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.fileUrl);
            }

            this.file = null;
            this.fileUrl = null;
            this.fileName = '';
            this.fileSizeFormatted = '';
            this.fileCategory = '';
            this.errorMessage = '';
            this.isOverLimit = false;
            this.wasOptimized = false;
            this.hasFile = false;

            this.$dispatch('file-change', {
                name: this.inputName,
                hasFile: false,
                isOverLimit: false,
                file: null
            });
        },

        openPreview() {
            if (!this.fileUrl) return;
            const payload = {
                url: this.fileUrl,
                name: this.fileName,
                type: this.fileCategory,
                size: this.fileSizeFormatted,
                isOptimized: this.wasOptimized
            };

            if (typeof window.dmsPreviewFile === 'function') {
                window.dmsPreviewFile(payload);
            } else if (window.parent && typeof window.parent.dmsPreviewFile === 'function') {
                window.parent.dmsPreviewFile(payload);
            } else if (window.top && typeof window.top.dmsPreviewFile === 'function') {
                window.top.dmsPreviewFile(payload);
            } else {
                window.open(this.fileUrl, '_blank');
            }
        },

        openExistingPreview() {
            if (!this.existingUrl) return;
            const payload = {
                url: this.existingUrl,
                name: this.existingName || 'Dokumen Tersimpan',
                type: this.existingUrl.toLowerCase().endsWith('.pdf') ? 'pdf' : 'image'
            };

            if (typeof window.dmsPreviewFile === 'function') {
                window.dmsPreviewFile(payload);
            } else if (window.parent && typeof window.parent.dmsPreviewFile === 'function') {
                window.parent.dmsPreviewFile(payload);
            } else if (window.top && typeof window.top.dmsPreviewFile === 'function') {
                window.top.dmsPreviewFile(payload);
            } else {
                window.open(this.existingUrl, '_blank');
            }
        }
    };
}
