// Multi-Box Excel Import State
        openImportModal: {{ request('open_import') ? 'true' : 'false' }},
        importFile: null,
        importFileName: '',
        importPreviewing: false,
        importPreviewResult: null,
        importTargetStatus: 'draft',
        importSubmitting: false,
        importErrors: [],
        importWarnings: [],

        handleImportFileSelect(e) {
            const files = e.target.files;
            if (files && files[0]) {
                this.importFile = files[0];
                this.importFileName = files[0].name;
                this.importPreviewResult = null;
                this.importErrors = [];
                this.importWarnings = [];
            }
        },

        async submitImportPreview() {
            if (!this.importFile) {
                alert('Pilih file Excel (.xlsx) atau CSV terlebih dahulu.');
                return;
            }

            this.importPreviewing = true;
            this.importErrors = [];
            this.importWarnings = [];

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const formData = new FormData();
                formData.append('file', this.importFile);

                const res = await fetch('{{ route("archives.import.preview") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const json = await res.json();
                if (res.ok && json.success) {
                    this.importPreviewResult = json;
                    this.importWarnings = json.warnings || [];
                    this.importErrors = [];
                } else {
                    this.importPreviewResult = null;
                    this.importErrors = json.errors || [json.message || 'Gagal memproses file template spreadsheet.'];
                }
            } catch (err) {
                console.error('Import preview error:', err);
                this.importErrors = ['Terjadi kesalahan koneksi saat membaca file Excel: ' + err.message];
            } finally {
                this.importPreviewing = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        importLoadingToForm: false,

        async continueToCreateForm() {
            if (!this.importPreviewResult || !this.importPreviewResult.boxes || this.importPreviewResult.boxes.length === 0) {
                alert('Silakan pilih dan validasi file Excel terlebih dahulu.');
                return;
            }

            this.importLoadingToForm = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const res = await fetch('{{ route("archives.import.load_to_form") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        boxes: this.importPreviewResult.boxes
                    })
                });

                const json = await res.json();
                if (res.ok && json.success && json.redirect_url) {
                    window.location.href = json.redirect_url;
                } else {
                    alert(json.message || 'Gagal memuat data ke formulir pengajuan.');
                    this.importLoadingToForm = false;
                }
            } catch (err) {
                console.error('Continue to create form error:', err);
                alert('Terjadi kesalahan koneksi saat memuat formulir: ' + err.message);
                this.importLoadingToForm = false;
            }
        },

        async submitImportCommitDirect() {
            if (!this.importPreviewResult || !this.importPreviewResult.boxes || this.importPreviewResult.boxes.length === 0) {
                return;
            }

            const boxCount = this.importPreviewResult.total_boxes;
            const itemCount = this.importPreviewResult.total_items;
            const statusLabels = {
                'draft': 'Draft Sementara',
                'pending_verification': 'Antrean Verifikasi Gudang',
                'in_warehouse': 'Tersimpan di Rak Gudang (Data Existing)'
            };
            const currentLabel = statusLabels[this.importTargetStatus] || this.importTargetStatus;

            const ok = await window.showConfirmModal({
                title: 'Konfirmasi Impor Langsung ke Gudang',
                message: `Data ini akan langsung disimpan ke database gudang sebagai data fisik yang sudah ada di gudang sebelumnya (${boxCount} Box, ${itemCount} Butir Dokumen, Status: "${currentLabel}"). Lanjutkan?`,
                type: 'info',
                confirmText: 'Ya, Simpan ke Gudang'
            });
            if (!ok) return;

            this.importSubmitting = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const res = await fetch('{{ route("archives.import.commit") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        boxes: this.importPreviewResult.boxes,
                        status: this.importTargetStatus
                    })
                });

                const json = await res.json();
                if (res.ok && json.success) {
                    this.openImportModal = false;
                    if (window.showToast) {
                        window.showToast(json.message, 'success');
                    } else {
                        alert(json.message);
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 600);
                } else {
                    await window.showNotificationModal(json.message || 'Gagal mengeksekusi impor data box arsip.', 'Gagal Impor', 'danger');
                }
            } catch (err) {
                console.error('Import commit error:', err);
                await window.showNotificationModal('Terjadi kesalahan saat menyimpan data impor.', 'Kesalahan Server', 'danger');
            } finally {
                this.importSubmitting = false;
            }
        },

        async submitImportCommit() {
            return this.submitImportCommitDirect();
        },

        resetImport() {
            this.importFile = null;
            this.importFileName = '';
            this.importPreviewResult = null;
            this.importErrors = [];
            this.importWarnings = [];
            this.importPreviewing = false;
            this.importSubmitting = false;
            const input = document.getElementById('excel_import_file_input');
            if (input) input.value = '';
        },

        openSuperAdminStatusModal(archiveData) {
            this.superAdminStatusData = archiveData;
            this.targetStatusChoice = (archiveData.status === 'borrowed' ? 'taken' : archiveData.status) || 'draft';
            this.superAdminStatusReason = '';
            this.superAdminBorrowerName = '{{ auth()->user()->name }}';
            this.superAdminDeptName = archiveData.department || '';
            this.superAdminPurpose = '';
            this.superAdminBapNumber = 'BAP/IND/' + new Date().getFullYear() + '/' + String(archiveData.id || 1).padStart(5, '0');
            this.superAdminDestroyDate = new Date().toISOString().split('T')[0];
            this.superAdminDestroyMethod = 'Shredding (Pencacahan Fisik)';
            this.superAdminDestroyNotes = '';
            this.superAdminBoxNumber = archiveData.raw_box_number || (archiveData.box_number !== 'Pending' ? archiveData.box_number : '') || '';
            this.superAdminStatusModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closeSuperAdminStatusModal() {
            this.superAdminStatusModalOpen = false;
            this.superAdminStatusData = null;
            this.updatingStatus = false;
        },

        async submitSuperAdminStatusChange() {
            if (!this.superAdminStatusData || !this.targetStatusChoice) return;

            // Validations
            if (this.targetStatusChoice === 'draft' && this.superAdminStatusData.status !== 'draft') {
                if (!this.superAdminStatusReason.trim()) {
                    alert('Mohon isi alasan / keterangan pengembalian status ke Draft.');
                    return;
                }
            } else if (this.targetStatusChoice === 'taken') {
                if (!this.superAdminPurpose.trim()) {
                    alert('Mohon isi keperluan / alasan pengeluaran berkas.');
                    return;
                }
            } else if (this.targetStatusChoice === 'destroyed') {
                if (!this.superAdminBapNumber.trim()) {
                    await window.showNotificationModal('Mohon isi nomor Berita Acara Pemusnahan (BAP).', 'Validasi BAP Diperlukan', 'warning');
                    return;
                }
                const ok = await window.showConfirmModal({
                    title: 'Konfirmasi Pemusnahan Berkas',
                    message: `Apakah Anda yakin ingin mengesahkan pemusnahan berkas "${this.superAdminStatusData.title}" dengan No. BAP ${this.superAdminBapNumber}? Status akan dimusnahkan secara permanen.`,
                    type: 'danger',
                    confirmText: 'Ya, Musnahkan Permanen'
                });
                if (!ok) return;
            }

            this.updatingStatus = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const formData = new FormData();
                formData.append('target_status', this.targetStatusChoice);
                formData.append('reason', this.superAdminStatusReason);

                if (this.targetStatusChoice === 'pending_verification') {
                    const f1 = document.getElementById('sa_cat_scan_input_form');
                    if (f1 && f1.files && f1.files[0]) formData.append('scan_input_form', f1.files[0]);
                    const f2 = document.getElementById('sa_cat_file_path');
                    if (f2 && f2.files && f2.files[0]) formData.append('file_path', f2.files[0]);
                } else if (this.targetStatusChoice === 'in_warehouse') {
                    if (this.superAdminBoxNumber.trim()) {
                        formData.append('box_number', this.superAdminBoxNumber.trim());
                    }
                    const fWh = document.getElementById('sa_cat_warehouse_approval_file');
                    if (fWh && fWh.files && fWh.files[0]) formData.append('scan_approval_input', fWh.files[0]);
                } else if (this.targetStatusChoice === 'taken') {
                    formData.append('borrower_name', this.superAdminBorrowerName);
                    formData.append('department_name', this.superAdminDeptName);
                    formData.append('purpose', this.superAdminPurpose);
                    const fOut = document.getElementById('sa_cat_taken_approval_file');
                    if (fOut && fOut.files && fOut.files[0]) formData.append('approval_file', fOut.files[0]);
                } else if (this.targetStatusChoice === 'destroyed') {
                    formData.append('bap_number', this.superAdminBapNumber);
                    formData.append('destruction_date', this.superAdminDestroyDate);
                    formData.append('method', this.superAdminDestroyMethod);
                    formData.append('notes', this.superAdminDestroyNotes);
                    const fDes = document.getElementById('sa_cat_destroy_approval_file');
                    if (fDes && fDes.files && fDes.files[0]) formData.append('approval_file', fDes.files[0]);
                }

                const response = await fetch(`/archives/${this.superAdminStatusData.id}/superadmin-status`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    this.superAdminStatusModalOpen = false;
                    if (window.showToast) {
                        window.showToast(result.message, 'success');
                    } else {
                        alert(result.message);
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    let errMsg = result.message || 'Gagal mengubah status arsip.';
                    if (result.errors) {
                        const firstKey = Object.keys(result.errors)[0];
                        if (firstKey && result.errors[firstKey][0]) {
                            errMsg = result.errors[firstKey][0];
                        }
                    }
                    alert(errMsg);
                }
            } catch (e) {
                console.error('SuperAdmin Status Change Error:', e);
                alert('Terjadi kesalahan saat mengubah status arsip.');
            } finally {
                this.updatingStatus = false;
            }
        },
        
        // Incomplete File Modal State
        incompleteFileModalOpen: false,
        incompleteFileArchive: null,

        openIncompleteFileAlert(archiveData) {
            this.incompleteFileArchive = archiveData;
            this.incompleteFileModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        // Checkout / Pengeluaran & Pemusnahan Berkas Modal State
        checkoutModalOpen: false,
        checkoutModalData: null,
        checkoutActionType: 'out', // 'out' or 'destroy'
        checkoutBorrowerName: '{{ auth()->user()->name }}',
        checkoutDepartmentName: '',
        checkoutPurpose: '',
        checkoutNotes: '',
        destroyBapNumber: '',
        destroyDate: new Date().toISOString().split('T')[0],
        destroyMethod: 'Shredding (Pencacahan Fisik)',
        destroyNotes: '',
        checkingOut: false,

        openCheckoutModal(archiveData) {
            this.checkoutModalData = archiveData;
            this.checkoutActionType = 'out';
            this.checkoutBorrowerName = '{{ auth()->user()->name }}';
            this.checkoutDepartmentName = archiveData.department || '';
            this.checkoutPurpose = '';
            this.checkoutNotes = '';
            this.destroyBapNumber = 'BAP/IND/' + new Date().getFullYear() + '/' + String(archiveData.id || 1).padStart(5, '0');
            this.destroyDate = new Date().toISOString().split('T')[0];
            this.destroyMethod = 'Shredding (Pencacahan Fisik)';
            this.destroyNotes = '';
            this.checkoutModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closeCheckoutModal() {
            this.checkoutModalOpen = false;
            this.checkoutModalData = null;
            this.checkingOut = false;
        },

        async submitCheckout() {
            if (!this.checkoutModalData) return;

            if (this.checkoutActionType === 'out') {
                if (!this.checkoutPurpose.trim()) {
                    alert('Mohon isi keperluan / alasan pengeluaran berkas.');
                    return;
                }
            } else if (this.checkoutActionType === 'destroy') {
                if (!this.destroyBapNumber.trim()) {
                    await window.showNotificationModal('Mohon isi nomor Berita Acara Pemusnahan (BAP).', 'Validasi BAP Diperlukan', 'warning');
                    return;
                }
                const ok = await window.showConfirmModal({
                    title: 'Konfirmasi Pemusnahan Berkas',
                    message: `Apakah Anda yakin ingin mengesahkan pemusnahan berkas "${this.checkoutModalData.title}" dengan No. BAP ${this.destroyBapNumber}? Tindakan ini permanen.`,
                    type: 'danger',
                    confirmText: 'Ya, Musnahkan Permanen'
                });
                if (!ok) return;
            }

            this.checkingOut = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const formData = new FormData();
                formData.append('action_type', this.checkoutActionType);

                if (this.checkoutActionType === 'out') {
                    formData.append('borrower_name', this.checkoutBorrowerName);
                    formData.append('department_name', this.checkoutDepartmentName);
                    formData.append('purpose', this.checkoutPurpose);
                    formData.append('notes', this.checkoutNotes);

                    const fileInput = document.getElementById('cat_checkout_approval_file');
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        formData.append('approval_file', fileInput.files[0]);
                    }
                } else if (this.checkoutActionType === 'destroy') {
                    formData.append('bap_number', this.destroyBapNumber);
                    formData.append('destruction_date', this.destroyDate);
                    formData.append('method', this.destroyMethod);
                    formData.append('notes', this.destroyNotes);

                    const fileInput = document.getElementById('cat_destroy_approval_file');
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        formData.append('approval_file', fileInput.files[0]);
                    }
                }

                const response = await fetch(`/archives/${this.checkoutModalData.id}/checkout`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    this.checkoutModalOpen = false;
                    if (window.showToast) {
                        window.showToast(result.message, 'success');
                    } else {
                        alert(result.message);
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    await window.showNotificationModal(result.message || 'Gagal memproses tindakan arsip.', 'Gagal Proses Tindakan', 'danger');
                }
            } catch (e) {
                console.error('Action error:', e);
                await window.showNotificationModal('Terjadi kesalahan saat memproses status arsip.', 'Kesalahan Sistem', 'danger');
            } finally {
                this.checkingOut = false;
            }
        },

        