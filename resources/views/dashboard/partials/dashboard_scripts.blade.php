<script>
function dashboardOverviewApp() {
    return {
        departments: @json($departments ?? []),
        warehouses: @json($warehouses),
        warehouseLocations: @json($warehouseLocations),
        stats: {
            total: {{ (int) $totalArchives }},
            inWarehouse: {{ (int) $inWarehouseCount }},
            pendingVerification: {{ (int) $pendingVerificationCount }},
            borrowed: {{ (int) $borrowedCount }},
            expiring: {{ (int) $expiringCount }},
            usedCapacity: {{ (int) $usedCapacity }},
            totalCapacity: {{ (int) $totalCapacity }},
        },

        // Super Admin Status Override State
        superAdminStatusModalOpen: false,
        superAdminStatusData: null,
        targetStatusChoice: 'draft',
        superAdminStatusReason: '',
        superAdminBorrowerName: '{{ auth()->user()->name }}',
        superAdminDeptName: '',
        superAdminPurpose: '',
        superAdminBapNumber: '',
        superAdminDestroyDate: new Date().toISOString().split('T')[0],
        superAdminDestroyMethod: 'Shredding (Pencacahan Fisik)',
        superAdminDestroyNotes: '',
        superAdminBoxNumber: '',
        updatingStatus: false,

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
                    const f1 = document.getElementById('sa_scan_input_form');
                    if (f1 && f1.files && f1.files[0]) formData.append('scan_input_form', f1.files[0]);
                    const f2 = document.getElementById('sa_file_path');
                    if (f2 && f2.files && f2.files[0]) formData.append('file_path', f2.files[0]);
                } else if (this.targetStatusChoice === 'in_warehouse') {
                    if (this.superAdminBoxNumber.trim()) {
                        formData.append('box_number', this.superAdminBoxNumber.trim());
                    }
                    const fWh = document.getElementById('sa_warehouse_approval_file');
                    if (fWh && fWh.files && fWh.files[0]) formData.append('scan_approval_input', fWh.files[0]);
                } else if (this.targetStatusChoice === 'taken') {
                    formData.append('borrower_name', this.superAdminBorrowerName);
                    formData.append('department_name', this.superAdminDeptName);
                    formData.append('purpose', this.superAdminPurpose);
                    const fOut = document.getElementById('sa_taken_approval_file');
                    if (fOut && fOut.files && fOut.files[0]) formData.append('approval_file', fOut.files[0]);
                } else if (this.targetStatusChoice === 'destroyed') {
                    formData.append('bap_number', this.superAdminBapNumber);
                    formData.append('destruction_date', this.superAdminDestroyDate);
                    formData.append('method', this.superAdminDestroyMethod);
                    formData.append('notes', this.superAdminDestroyNotes);
                    const fDes = document.getElementById('sa_destroy_approval_file');
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
        
        // File Preview Modal Delegation to Universal Component
        openFilePreviewModal(archiveData) {
            if (typeof window.dmsPreviewFile === 'function') {
                window.dmsPreviewFile(archiveData);
            }
        },

        // Verification Modal State
        verifyModalOpen: false,
        verifyModalData: null,
        verifyingArchive: false,
        showRejectInput: false,
        rejectionNote: '',

        openVerifyModal(archiveData) {
            this.verifyModalData = archiveData;
            this.showRejectInput = false;
            this.rejectionNote = '';
            this.verifyModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closeVerifyModal() {
            this.verifyModalOpen = false;
            this.verifyModalData = null;
            this.showRejectInput = false;
            this.rejectionNote = '';
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

                    const fileInput = document.getElementById('dash_checkout_approval_file');
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        formData.append('approval_file', fileInput.files[0]);
                    }
                } else if (this.checkoutActionType === 'destroy') {
                    formData.append('bap_number', this.destroyBapNumber);
                    formData.append('destruction_date', this.destroyDate);
                    formData.append('method', this.destroyMethod);
                    formData.append('notes', this.destroyNotes);

                    const fileInput = document.getElementById('dash_destroy_approval_file');
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

        async submitVerification(action) {
            if (!this.verifyModalData) return;
            if (action === 'reject' && !this.rejectionNote.trim()) {
                await window.showNotificationModal('Mohon masukkan alasan penolakan.', 'Alasan Penolakan Wajib Diisi', 'warning');
                return;
            }

            this.verifyingArchive = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch(`/archives/${this.verifyModalData.id}/verify`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: action,
                        rejection_note: this.rejectionNote
                    })
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    this.verifyModalOpen = false;
                    if (window.showToast) {
                        window.showToast(result.message, 'success');
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    await window.showNotificationModal(result.message || 'Gagal memproses verifikasi.', 'Gagal Verifikasi', 'danger');
                }
            } catch (e) {
                console.error('Verification error:', e);
                await window.showNotificationModal('Terjadi kesalahan saat memproses verifikasi arsip.', 'Kesalahan Sistem', 'danger');
            } finally {
                this.verifyingArchive = false;
            }
        },

        // 2D Quick Slot Allocation State
        quickSlotModalOpen: false,
        confirmModalOpen: false,
        loadingLocations: false,
        savingSlot: false,
        currentStep: 'room', // 'room', 'rack', 'slot'
        targetArchive: null,
        locationsData: [],
        roomList: [],
        selectedRoom: null,
        selectedRack: null,
        selectedSlot: null,
        slotToSave: null,

        init() {
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async openQuickSlotModal(archive) {
            this.targetArchive = archive;
            this.currentStep = 'room';
            this.selectedRoom = null;
            this.selectedRack = null;
            this.selectedSlot = null;
            this.slotToSave = null;
            this.quickSlotModalOpen = true;

            if (this.locationsData.length === 0) {
                await this.loadLocationsData();
            } else {
                this.processRoomList();
            }

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closeQuickSlotModal() {
            this.quickSlotModalOpen = false;
            this.confirmModalOpen = false;
            this.selectedSlot = null;
            this.slotToSave = null;
        },

        async loadLocationsData() {
            this.loadingLocations = true;
            try {
                const response = await fetch('/api/warehouse/layout-data', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                this.locationsData = data.locations || [];
                this.processRoomList();
            } catch (error) {
                console.error('Failed to load layout data:', error);
                alert('Gagal memuat data layout 2D gudang.');
            } finally {
                this.loadingLocations = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        processRoomList() {
            const roomsMap = {};
            
            this.locationsData.forEach(loc => {
                const roomName = (loc.room_sector && loc.room_sector.trim()) ? loc.room_sector.trim() : 'Umum';
                const formattedRoom = roomName.toLowerCase().startsWith('gudang') ? roomName : ('Gudang ' + roomName);

                if (!roomsMap[formattedRoom]) {
                    roomsMap[formattedRoom] = {
                        name: formattedRoom,
                        rawName: roomName,
                        isFatLocked: Boolean(loc.is_fat_locked || roomName.toUpperCase().includes('R1') || roomName.toUpperCase().includes('R2')),
                        racks: [],
                        totalCapacity: 0,
                        usedBoxes: 0,
                        availableSlots: 0,
                        percent: 0
                    };
                }

                roomsMap[formattedRoom].racks.push(loc);
                const cap = loc.box_capacity || 100;
                const used = loc.current_box_count || (loc.slots ? loc.slots.filter(s => s.status === 'filled').length : 0);
                roomsMap[formattedRoom].totalCapacity += cap;
                roomsMap[formattedRoom].usedBoxes += used;
            });

            this.roomList = Object.values(roomsMap).map(r => {
                r.availableSlots = Math.max(0, r.totalCapacity - r.usedBoxes);
                r.percent = r.totalCapacity > 0 ? Math.round((r.usedBoxes / r.totalCapacity) * 100) : 0;
                return r;
            });
        },

        isRoomDisabled(room) {
            if (!this.targetArchive) return false;
            if (room.isFatLocked) {
                const code = (this.targetArchive.department_code || '').toUpperCase();
                const name = (this.targetArchive.department_name || '').toUpperCase();
                const isFatDept = code.includes('FAT') || code.includes('FIN') || name.includes('FAT') || name.includes('FIN');
                return !isFatDept;
            }
            return false;
        },

        selectRoom(roomName) {
            this.selectedRoom = roomName;
            this.currentStep = 'rack';
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        get filteredRacks() {
            if (!this.selectedRoom) return [];
            return this.locationsData.filter(loc => {
                const roomName = (loc.room_sector && loc.room_sector.trim()) ? loc.room_sector.trim() : 'Umum';
                const formattedRoom = roomName.toLowerCase().startsWith('gudang') ? roomName : ('Gudang ' + roomName);
                return formattedRoom === this.selectedRoom;
            });
        },

        getRackAvailableSlots(rack) {
            const cap = rack.box_capacity || 100;
            const used = rack.current_box_count || (rack.slots ? rack.slots.filter(s => s.status === 'filled').length : 0);
            return Math.max(0, cap - used);
        },

        selectRack(rack) {
            this.selectedRack = rack;
            this.currentStep = 'slot';
            this.selectedSlot = null;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        getSlotsForSapAndLayer(sapLevel, layer) {
            if (!this.selectedRack || !this.selectedRack.slots) return [];
            return this.selectedRack.slots.filter(slot => {
                return parseInt(slot.sap_level) === parseInt(sapLevel) && String(slot.layer).toLowerCase() === String(layer).toLowerCase();
            }).sort((a, b) => parseInt(a.slot_number) - parseInt(b.slot_number));
        },

        isSlotExpired(slot) {
            if (!slot) return false;
            if (slot.status === 'inactive' || slot.is_active === false) return false;
            return slot.status === 'expired' || slot.is_expired === true || !!(slot.archive?.is_expired);
        },

        isSlotNearExp(slot) {
            if (!slot) return false;
            if (slot.status === 'inactive' || slot.is_active === false) return false;
            if (this.isSlotExpired(slot)) return false;
            return slot.status === 'near_exp' || slot.is_near_exp === true || !!(slot.archive?.is_near_exp);
        },

        goToStep(step) {
            if (step === 'room') {
                this.currentStep = 'room';
                this.selectedRoom = null;
                this.selectedRack = null;
                this.selectedSlot = null;
            } else if (step === 'rack' && this.selectedRoom) {
                this.currentStep = 'rack';
                this.selectedRack = null;
                this.selectedSlot = null;
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        goBackStep() {
            if (this.currentStep === 'slot') {
                this.currentStep = 'rack';
                this.selectedRack = null;
                this.selectedSlot = null;
            } else if (this.currentStep === 'rack') {
                this.currentStep = 'room';
                this.selectedRoom = null;
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        onSlotDoubleClick(slot) {
            if (!slot || slot.status !== 'empty') {
                return;
            }
            this.slotToSave = slot;
            this.confirmModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async saveSlotAllocation() {
            if (!this.slotToSave || !this.selectedRack || !this.targetArchive) return;
            
            this.savingSlot = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                                || '{{ csrf_token() }}';

                const response = await fetch(`/api/warehouse/locations/${this.selectedRack.id}/slots/assign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        mode: 'existing_archive',
                        archive_id: parseInt(this.targetArchive.id),
                        sap_level: parseInt(this.slotToSave.sap_level),
                        layer: String(this.slotToSave.layer).toLowerCase(),
                        slot_number: parseInt(this.slotToSave.slot_number),
                        slot_code: this.slotToSave.slot_code
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.confirmModalOpen = false;
                    this.quickSlotModalOpen = false;
                    
                    if (window.showToast) {
                        window.showToast(result.message || 'Lokasi slot arsip berhasil disimpan!', 'success');
                    } else {
                        alert(result.message || 'Lokasi slot arsip berhasil disimpan!');
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 400);
                } else {
                    let errMsg = result.message || 'Gagal menyimpan penempatan slot.';
                    if (result.errors) {
                        const firstKey = Object.keys(result.errors)[0];
                        if (firstKey && result.errors[firstKey][0]) {
                            errMsg = result.errors[firstKey][0];
                        }
                    }
                    alert(errMsg);
                }
            } catch (error) {
                console.error('Error saving slot assignment:', error);
                alert('Terjadi kesalahan saat menyimpan slot ke server.');
            } finally {
                this.savingSlot = false;
            }
        }
    }
}
</script>