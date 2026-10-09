/**
 * DMS PT INDRACO — File Upload, Validation, Live Preview & Smart Compression Engine
 * Features:
 * 1. Client-side file size validation (Standard 2MB limit).
 * 2. High-fidelity image compression (Canvas 2D max 2400px, 0.88 quality, preserving sharp text & stamps).
 * 3. Transparent replacement of input.files via DataTransfer API.
 * 4. Live interactive preview (Image lightbox thumbnail, PDF modal viewer, Office/ZIP badges).
 */

window.DmsFileOptimizer = {
    // Standard size limit (500 KB = 512,000 bytes)
    DEFAULT_MAX_BYTES: 500 * 1024,
    DEFAULT_MAX_KB: 500,

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
        return 'other';
    },

    /**
     * Smart High-Fidelity Image Compression
     * Prioritizes pin-sharp text clarity while drastically reducing file size.
     */
    compressImage: async function(file, options = {}) {
        const maxDimension = options.maxDimension || 2000;
        const quality = options.quality || 0.82;

        return new Promise((resolve) => {
            if (!window.FileReader || !window.HTMLCanvasElement) {
                return resolve({ file: file, wasCompressed: false });
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    let width = img.naturalWidth || img.width;
                    let height = img.naturalHeight || img.height;

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

                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillRect(0, 0, width, height);
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            return resolve({ file: file, wasCompressed: false });
                        }

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
        maxSizeKB: config.maxSizeKB || 500,
        label: config.label || 'Unggah Dokumen',
        helperText: config.helperText || 'Format: PDF, JPG, PNG (Maksimal 500 KB).',
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
            const maxBytes = this.maxSizeKB * 1024;
            const ext = (selectedFile.name || '').split('.').pop().toLowerCase();
            const allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];

            // 1. Ekstensi Wajib PDF & Gambar
            if (!allowedExts.includes(ext)) {
                input.value = '';
                this.clearFile();
                const rejectMsg = `Format file .${ext.toUpperCase() || 'tidak valid'} tidak didukung. Harap pilih berkas dengan format PDF, JPG, JPEG, atau PNG.`;
                if (typeof window.showNotificationModal === 'function') {
                    window.showNotificationModal(rejectMsg, 'Format Berkas Ditolak', 'warning');
                } else {
                    alert(rejectMsg);
                }
                return;
            }

            const category = window.DmsFileOptimizer.getFileCategory(selectedFile.name, selectedFile.type);
            this.errorMessage = '';
            this.isOverLimit = false;
            this.wasOptimized = false;
            this.fileCategory = category;

            // 2. Kompresi Cerdas untuk Gambar jika > 400KB
            if (category === 'image' && selectedFile.size > 400 * 1024) {
                this.isOptimizing = true;
                try {
                    const result = await window.DmsFileOptimizer.compressImage(selectedFile, {
                        maxDimension: 2000,
                        quality: 0.82
                    });

                    if (result.wasCompressed) {
                        selectedFile = result.file;
                        this.wasOptimized = true;
                        this.originalSizeFormatted = window.DmsFileOptimizer.formatBytes(result.originalSize);

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

            // 3. Validasi Ukuran Maksimal 500 KB: Jika gagal langsung BLENG (clear) dan muncul notifikasi alert!
            if (selectedFile.size > maxBytes) {
                input.value = '';
                this.clearFile();
                const formattedActual = window.DmsFileOptimizer.formatBytes(selectedFile.size);
                const sizeMsg = `Ukuran berkas (${formattedActual}) melebihi batas maksimal ${this.maxSizeKB} KB. Berkas otomatis dibatalkan. Silakan pilih berkas dengan ukuran maksimal ${this.maxSizeKB} KB.`;
                if (typeof window.showNotificationModal === 'function') {
                    window.showNotificationModal(sizeMsg, 'Ukuran Berkas Melebihi Batas', 'warning');
                } else {
                    alert(sizeMsg);
                }
                return;
            }

            // 4. File Valid & Aman: Set Active File Info & Object URL Preview
            this.file = selectedFile;
            this.fileName = selectedFile.name;
            this.fileSizeFormatted = window.DmsFileOptimizer.formatBytes(selectedFile.size);
            this.hasFile = true;

            if (this.fileUrl && this.fileUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.fileUrl);
            }
            this.fileUrl = URL.createObjectURL(selectedFile);

            this.$dispatch('file-change', {
                name: this.inputName,
                hasFile: true,
                isOverLimit: false,
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
            const isPdf = this.fileCategory === 'pdf' || (this.fileName && this.fileName.toLowerCase().endsWith('.pdf'));
            const payload = {
                url: this.fileUrl,
                file: this.file,
                name: this.fileName,
                ext: isPdf ? 'pdf' : (this.fileName ? this.fileName.split('.').pop().toLowerCase() : 'jpg'),
                type: isPdf ? 'pdf' : 'image',
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
            const cleanUrl = this.existingUrl.split('?')[0];
            const isPdf = cleanUrl.toLowerCase().endsWith('.pdf');
            const payload = {
                url: this.existingUrl,
                name: this.existingName || 'Dokumen Tersimpan',
                ext: isPdf ? 'pdf' : cleanUrl.split('.').pop().toLowerCase(),
                type: isPdf ? 'pdf' : 'image'
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

// Global Delegated File Validator for All File Inputs across the application
document.addEventListener('change', function(e) {
    var input = e.target;
    if (!input || input.type !== 'file') return;
    // Skip if managed by dmsFileUploader component (already handled inside handleFileInput)
    if (input.closest && input.closest('[x-data*="dmsFileUploader"]')) return;
    if (!input.files || !input.files.length) return;

    var file = input.files[0];
    var ext = (file.name || '').split('.').pop().toLowerCase();
    var allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
    var maxBytes = 500 * 1024; // 500 KB limit

    // 1. Validasi Ekstensi
    if (!allowedExts.includes(ext)) {
        input.value = ''; // BLENG (kosongkan seketika)
        var msgExt = 'Format berkas (.' + (ext.toUpperCase() || 'tidak valid') + ') tidak didukung. Harap unggah berkas bertipe PDF atau Gambar (JPG, JPEG, PNG).';
        if (typeof window.showNotificationModal === 'function') {
            window.showNotificationModal(msgExt, 'Format Berkas Ditolak', 'warning');
        } else {
            alert(msgExt);
        }
        return;
    }

    // 2. Validasi Ukuran Maksimal 500 KB
    if (file.size > maxBytes) {
        input.value = ''; // BLENG (kosongkan seketika)
        var actualFormatted = (file.size / 1024).toFixed(1) + ' KB';
        var msgSize = 'Ukuran berkas (' + actualFormatted + ') melebihi batas maksimal 500 KB. Berkas otomatis dibatalkan.';
        if (typeof window.showNotificationModal === 'function') {
            window.showNotificationModal(msgSize, 'Batas Ukuran 500 KB', 'warning');
        } else {
            alert(msgSize);
        }
        return;
    }
}, true);
