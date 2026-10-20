@php
    $borrowableArchiveIds = [];
    $printableArchiveIds = [];
    foreach ($archives as $arc) {
        $hasActiveBorrowing = $arc->borrowingLogs && $arc->borrowingLogs->whereIn('status', ['requested', 'dept_approved', 'approved'])->isNotEmpty();
        $isStoredInWh = $arc->status === 'in_warehouse';
        $isDeptAllowed = !auth()->user()->isPicDept() || ((int)$arc->department_id === (int)auth()->user()->department_id);
        if ($isStoredInWh && !$hasActiveBorrowing && $isDeptAllowed) {
            $borrowableArchiveIds[] = (int) $arc->id;
        }

        $hasRackLocation = !empty($arc->warehouse_location_id);
        if (in_array($arc->status, ['approved_booked', 'in_warehouse', 'pending_extension']) && $hasRackLocation) {
            $printableArchiveIds[] = (int) $arc->id;
        }
    }
@endphp
<script>
function archiveCatalog() {
    return {
        departments: @json($departments ?? []),

        // Cascading Document Types Filter
        documentTypes: @json($documentTypes ?? []),
        filterSubDept: '{{ request('sub_department_id', '') }}',
        filterDocType: '{{ request('document_type', '') }}',

        get availableDocTypes() {
            let list = this.documentTypes;
            if (this.filterSubDept) {
                list = list.filter(d => String(d.sub_department_id) === String(this.filterSubDept) || !d.sub_department_id);
            }
            return [...new Set(list.map(d => d.document_type))].filter(Boolean).sort();
        },

        onFilterSubDeptChange() {
            if (this.filterDocType && !this.availableDocTypes.includes(this.filterDocType)) {
                this.filterDocType = '';
            }
        },
        selected: [],
        selectAll: false,
        dispatchModalLog: null,

        // Live Realtime State (Point 4: Auto-sorting Data Baru)
        liveNewArchives: [],
        currentFilters: {
            deptId: '{{ request('department_id', '') }}',
            subDeptId: '{{ request('sub_department_id', '') }}',
            docType: '{{ request('document_type', '') }}',
            status: '{{ request('status', '') }}',
        },

        init() {
            const onRealtimeSync = (detail) => {
                if (!detail) return;
                // If there are new archives or status changes
                if (detail.has_new || detail.has_changes) {
                    // Check if user is currently interacting with any modal or form
                    const isAnyModalOpen = this.verifyModalOpen || 
                                           this.quickSlotModalOpen || 
                                           this.checkoutModalOpen || 
                                           this.superAdminStatusModalOpen || 
                                           this.openImportModal ||
                                           (this.dispatchModalLog !== null);
                    
                    const isTyping = document.activeElement && 
                                     ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);
                    
                    const hasSelected = this.selected && this.selected.length > 0;

                    // If user is idle (not typing, no modal open, no bulk items selected):
                    // Auto-refresh table seamlessly so newly submitted archives appear immediately
                    if (!isAnyModalOpen && !isTyping && !hasSelected) {
                        window.location.reload();
                    } else if (detail.new_archives && detail.new_archives.length > 0) {
                        // If user is busy typing or has modal open, fall back to handleIncomingArchives without disturbing them
                        this.handleIncomingArchives(detail.new_archives);
                    }
                }
            };

            window.addEventListener('dms:realtime-sync', (e) => onRealtimeSync(e.detail));
            window.addEventListener('message', (e) => {
                if (e.data && e.data.type === 'dms:realtime-sync') {
                    onRealtimeSync(e.data.detail);
                }
            });
            if (window.parent && window.parent !== window) {
                try {
                    window.parent.addEventListener('dms:realtime-sync', (e) => onRealtimeSync(e.detail));
                } catch(err) {}
            }
        },

        handleIncomingArchives(newItems) {
            let addedCount = 0;
            newItems.forEach(item => {
                // Draft items must never be auto-sorted to top (only real submissions)
                if (item.status === 'draft') {
                    return;
                }
                // If item already rendered in server table or live list, skip
                if (this.archiveStatuses[item.id] !== undefined) {
                    return;
                }
                // Check if active filter excludes this item
                if (this.currentFilters.deptId && String(item.department_id) !== String(this.currentFilters.deptId)) {
                    return;
                }
                if (this.currentFilters.subDeptId && String(item.sub_department_id) !== String(this.currentFilters.subDeptId)) {
                    return;
                }
                if (this.currentFilters.docType && String(item.document_type) !== String(this.currentFilters.docType)) {
                    return;
                }
                if (this.currentFilters.status && String(item.status) !== String(this.currentFilters.status)) {
                    return;
                }

                // Auto-sort to top
                this.liveNewArchives.unshift(item);
                this.archiveStatuses[item.id] = item.status;
                addedCount++;
            });

            if (addedCount > 0) {
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        // Archive status map for batch action filtering (id -> status)
        archiveStatuses: @json($archives->pluck('status', 'id')),
        borrowableArchiveIds: @json($borrowableArchiveIds),
        printableArchiveIds: @json($printableArchiveIds),

        get selectedBorrowable() {
            return this.selected.filter(id => this.borrowableArchiveIds.includes(Number(id)));
        },
        get selectedDrafts() {
            return this.selected.filter(id => this.archiveStatuses[id] === 'draft');
        },
        get selectedNonDrafts() {
            return this.selected.filter(id => this.archiveStatuses[id] !== 'draft');
        },
        get selectedAlreadyPulled() {
            return this.selected.filter(id => !this.borrowableArchiveIds.includes(Number(id)) && this.archiveStatuses[id] !== 'draft');
        },
        get selectedPrintable() {
            return this.selected.filter(id => this.printableArchiveIds.includes(Number(id)));
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

        @include('archives.partials.catalog_modal_handlers')

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
                    const currentData = this.verifyModalData;
                    this.verifyModalOpen = false;
                    this.showRejectInput = false;
                    this.rejectionNote = '';

                    if (action === 'approve') {
                        if (window.showToast) {
                            window.showToast(result.message || 'Pengajuan arsip disetujui! Silakan tentukan slot rak pada denah 2D.', 'success');
                        }
                        const targetData = {
                            id: currentData.id,
                            title: currentData.title,
                            box_number: result.box_number || 'Belum ada code',
                            department_id: currentData.department_id || 0,
                            department_code: currentData.department_code || 'GEN',
                            department_name: currentData.department || ''
                        };
                        this.$nextTick(() => {
                            this.openQuickSlotModal(targetData);
                        });
                    } else {
                        if (window.showToast) {
                            window.showToast(result.message, 'success');
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
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

        openFilePreviewModal(archiveData) {
            if (typeof window.dmsPreviewFile === 'function') {
                window.dmsPreviewFile(archiveData);
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

        toggleAll() {
            if (this.selectAll) {
                const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
                this.selected = Array.from(checkboxes).map(cb => parseInt(cb.value)).filter(v => !isNaN(v));
            } else {
                this.selected = [];
            }
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
                    
                    // Show success alert and reload table
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