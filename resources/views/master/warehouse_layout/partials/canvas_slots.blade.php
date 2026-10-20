        // --- 100-Box Rack Visualizer (Tahap 3) Methods ---
        openRackGridModal(rack = null) {
            const target = rack || this.selectedLocation;
            if (!target || target.location_type === 'room') return;
            this.selectedRackForModal = target;
            this.selectedSlotDetail = null;
            this.slotFilterStatus = 'all';
            this.slotSearchQuery = '';
            this.rackGridModalOpen = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        getRackIdentifier(rack) {
            if (!rack || !rack.rack_code) return 'RAK';
            const code = rack.rack_code;
            const clean = code.replace(/^RAK-(?:R\d+-)?/i, '');
            return clean || code;
        },

        getSapSlots(sapLevel, layer) {
            if (!this.selectedRackForModal) return [];
            const allSlots = this.selectedRackForModal.slots || [];
            const sap = parseInt(sapLevel);
            const matching = allSlots.filter(s => parseInt(s.sap_level) === sap && s.layer === layer);
            const rackId = this.getRackIdentifier(this.selectedRackForModal);

            const result = [];
            for (let i = 1; i <= 10; i++) {
                // Sesuai Denah Gambar 2:
                // LVL 1: Bawah 1..10, Atas 11..20
                // LVL 2: Bawah 21..30, Atas 31..40
                // LVL 3: Bawah 41..50, Atas 51..60
                // LVL 4: Bawah 61..70, Atas 71..80
                // LVL 5: Bawah 81..90, Atas 91..100
                const boxNum = layer === 'bottom' ? ((sap - 1) * 20 + i) : ((sap - 1) * 20 + 10 + i);
                const expectedSlotCode = `${rackId}${boxNum}`;
                const found = matching.find(s => parseInt(s.slot_number) === boxNum || parseInt(s.slot_number) === i);
                if (found) {
                    result.push({
                        ...found,
                        slot_code: expectedSlotCode,
                        box_number_display: boxNum
                    });
                } else {
                    result.push({
                        id: `synth-${sapLevel}-${layer}-${i}`,
                        sap_level: sapLevel,
                        layer: layer,
                        layer_label: layer === 'top' ? 'Baris Atas' : 'Baris Bawah',
                        slot_number: boxNum,
                        box_number_display: boxNum,
                        slot_code: expectedSlotCode,
                        status: 'empty',
                        archive: null
                    });
                }
            }
            return result;
        },

        getBoxIndex(sapLevel, layer, slotNumber) {
            const num = parseInt(slotNumber);
            if (!isNaN(num) && num > 10) return num;
            const sap = parseInt(sapLevel) || 1;
            const n = isNaN(num) ? 1 : num;
            if (layer === 'bottom') {
                return (sap - 1) * 20 + n;
            } else {
                return (sap - 1) * 20 + 10 + n;
            }
        },

        isSlotExpired(slot) {
            if (!slot) return false;
            if (slot.is_active === false || slot.status === 'inactive') return false;
            return slot.status === 'expired' || !!(slot.archive?.is_expired) || slot.is_expired === true;
        },

        isSlotNearExp(slot) {
            if (!slot) return false;
            if (slot.is_active === false || slot.status === 'inactive') return false;
            if (this.isSlotExpired(slot)) return false;
            if (slot.is_near_exp === true || slot.archive?.is_near_exp === true) return true;
            if (slot.archive && slot.archive.retention_expiry_date) {
                try {
                    const expDate = new Date(slot.archive.retention_expiry_date);
                    if (!isNaN(expDate.getTime())) {
                        const now = new Date();
                        const diffDays = Math.ceil((expDate - now) / (1000 * 60 * 60 * 24));
                        return diffDays >= 0 && diffDays <= 30;
                    }
                } catch(e) {}
            }
            return false;
        },

        isSlotFilled(slot) {
            if (!slot) return false;
            if (slot.is_active === false || slot.status === 'inactive') return false;
            if (this.isSlotExpired(slot) || this.isSlotNearExp(slot)) return false;
            return (slot.status === 'filled' || !!slot.archive);
        },

        getRackSlotStats(rack) {
            if (!rack) return { total: 100, empty: 100, filled: 0, expired: 0, near_exp: 0 };
            const slots = rack.slots || [];
            let empty = 0, filled = 0, expired = 0, near_exp = 0;

            if (slots.length > 0) {
                slots.forEach(s => {
                    if (s.is_active === false || s.status === 'inactive') {
                        // inactive
                    } else if (this.isSlotExpired(s)) {
                        expired++;
                    } else if (this.isSlotNearExp(s)) {
                        near_exp++;
                        filled++;
                    } else if (s.status === 'filled' || s.archive) {
                        filled++;
                    } else {
                        empty++;
                    }
                });
                const unrecorded = Math.max(0, 100 - slots.length);
                empty += unrecorded;
            } else {
                empty = rack.box_capacity || 100;
            }

            return {
                total: 100,
                empty: empty,
                filled: filled,
                expired: expired,
                near_exp: near_exp
            };
        },

        getSlotStyleClasses(slot) {
            if (!slot) return 'bg-white text-slate-500 border-slate-200';
            if (slot.is_active === false || slot.status === 'inactive') {
                return 'bg-slate-100 text-slate-400 border-slate-200 shadow-xs';
            }
            
            // 1. Expired (Melebihi Exp) -> Merah solid seperti notifikasi LAN, teks putih, denyut cepat
            if (this.isSlotExpired(slot)) {
                return 'bg-rose-600 !text-white border-rose-700 hover:bg-rose-700 dark:bg-rose-600 dark:border-rose-500 shadow-md animate-pulse-expired';
            }

            // 2. Mendekati Exp (H-30) -> Merah transparan seperti warna kuning itu, denyut lembut
            if (this.isSlotNearExp(slot)) {
                return 'bg-rose-50 text-rose-800 border-rose-300 hover:bg-rose-100 hover:border-rose-400 dark:bg-rose-950/40 dark:text-rose-200 dark:border-rose-700 animate-pulse-near-exp shadow-xs';
            }

            // 3. Terisi Normal -> Kuning transparan, tidak denyut
            if (slot.status === 'filled' || slot.archive) {
                return 'bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100 hover:border-amber-400 dark:bg-amber-950/35 dark:text-amber-200 dark:border-amber-600/50 shadow-xs';
            }

            // 4. Kosong
            return 'bg-white text-slate-700 border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/70 shadow-xs';
        },

        isSlotMatchFilter(slot) {
            if (!slot) return false;
            const isExpired = this.isSlotExpired(slot);
            const isNearExp = this.isSlotNearExp(slot);
            const isFilled = this.isSlotFilled(slot) || isNearExp;
            const isEmpty = !isExpired && !isFilled && !slot.archive;

            if (this.slotFilterStatus === 'empty' && !isEmpty) return false;
            if (this.slotFilterStatus === 'filled' && !isFilled) return false;
            if (this.slotFilterStatus === 'expired' && !isExpired) return false;

            if (this.slotSearchQuery && this.slotSearchQuery.trim() !== '') {
                const q = this.slotSearchQuery.toLowerCase().trim();
                const slotCode = (slot.slot_code || '').toLowerCase();
                const boxNum = (slot.archive?.box_number || '').toLowerCase();
                const title = (slot.archive?.title || '').toLowerCase();
                const period = (slot.archive?.periode_doc || '').toLowerCase();
                const dept = (slot.archive?.department || '').toLowerCase();

                return slotCode.includes(q) || boxNum.includes(q) || title.includes(q) || period.includes(q) || dept.includes(q);
            }

            return true;
        },

        onDepartmentChange() {
            const dept = this.departments.find(d => d.id == this.slotAssignForm.department_id);
            if (dept) {
                if (dept.retention_years) {
                    this.slotAssignForm.retention_years = dept.retention_years;
                }
                this.slotAssignForm.sub_department_id = '';
            }
        },

        onSubDepartmentChange() {
            const subDept = this.getAvailableSubDepartments().find(s => s.id == this.slotAssignForm.sub_department_id);
            if (subDept && subDept.retention_years) {
                this.slotAssignForm.retention_years = subDept.retention_years;
            }
        },

        getAvailableSubDepartments() {
            if (!this.slotAssignForm.department_id) return [];
            const dept = this.departments.find(d => d.id == this.slotAssignForm.department_id);
            return dept && dept.sub_departments ? dept.sub_departments : [];
        },

        isCurrentRackLocked() {
            if (!this.selectedRackForModal) return false;
            if (this.selectedRackForModal.is_locked !== false) return true;
            const parentRoom = this.locations.find(l => l.location_type === 'room' && l.room_sector === this.selectedRackForModal.room_sector);
            if (parentRoom && parentRoom.is_locked !== false) return true;
            return false;
        },

        getAllowedDeptIds(loc) {
            if (!loc) return [];
            let ids = [];
            if (Array.isArray(loc.assigned_department_ids)) {
                ids = loc.assigned_department_ids.map(Number).filter(Boolean);
            } else if (loc.assigned_department_ids && typeof loc.assigned_department_ids === 'string') {
                try {
                    const parsed = JSON.parse(loc.assigned_department_ids);
                    if (Array.isArray(parsed)) {
                        ids = parsed.map(Number).filter(Boolean);
                    } else if (Number(parsed)) {
                        ids = [Number(parsed)];
                    }
                } catch(e) {
                    if (Number(loc.assigned_department_ids)) {
                        ids = [Number(loc.assigned_department_ids)];
                    }
                }
            } else if (typeof loc.assigned_department_ids === 'number' && loc.assigned_department_ids > 0) {
                ids = [loc.assigned_department_ids];
            }
            if (ids.length === 0 && loc.assigned_department_id) {
                ids = [Number(loc.assigned_department_id)];
            }
            return ids;
        },

        getCurrentRackAllowedDeptIds() {
            if (!this.selectedRackForModal) return [];
            const rackIds = this.getAllowedDeptIds(this.selectedRackForModal);
            if (rackIds.length > 0) return rackIds;

            const parentRoom = this.locations.find(l => l.location_type === 'room' && l.room_sector === this.selectedRackForModal.room_sector);
            if (parentRoom) {
                return this.getAllowedDeptIds(parentRoom);
            }
            return [];
        },

        getDeptCode(deptId) {
            if (!deptId) return 'BEBAS';
            const d = (this.departments || []).find(x => Number(x.id) === Number(deptId));
            return d ? d.code : `#${deptId}`;
        },

        getDeptName(deptId) {
            if (!deptId) return 'Semua Departemen (Bebas)';
            const d = (this.departments || []).find(x => Number(x.id) === Number(deptId));
            return d ? `${d.code} - ${d.name}` : `Dept #${deptId}`;
        },

        getAllowedDeptSummary(loc) {
            const ids = this.getAllowedDeptIds(loc);
            if (ids.length === 0) return 'Semua Departemen (Bebas)';
            return ids.map(id => this.getDeptCode(id)).join(', ');
        },

        getCurrentRackCustomDeptNames() {
            const ids = this.getCurrentRackAllowedDeptIds();
            if (ids.length === 0) return 'Semua Departemen (Bebas)';
            return ids.map(id => this.getDeptName(id)).join(', ');
        },

        getCurrentRackCustomDeptId() {
            const ids = this.getCurrentRackAllowedDeptIds();
            return ids.length > 0 ? ids[0] : null;
        },

        getCurrentRackCustomDept() {
            const id = this.getCurrentRackCustomDeptId();
            if (!id) return null;
            return this.departments.find(d => Number(d.id) === Number(id)) || null;
        },

        getFilteredUnassignedArchives() {
            if (!this.unassignedArchivesList) return [];
            let list = this.unassignedArchivesList;
            const allowedDeptIds = this.getCurrentRackAllowedDeptIds();
            if (allowedDeptIds.length > 0) {
                list = list.filter(arc => allowedDeptIds.includes(Number(arc.department_id)));
            }
            if (this.selectedRackForModal) {
                const sector = (this.selectedRackForModal.room_sector || '').toUpperCase().trim();
                const isFatSector = this.selectedRackForModal.is_fat_locked || ['R1', 'R2', 'RUANG 1', 'RUANG 2', 'RUANG R1', 'RUANG R2', 'SEKTOR R1', 'SEKTOR R2'].includes(sector) || /^R[12]$/i.test(sector);
                if (isFatSector) {
                    list = list.filter(arc => {
                        const code = (arc.department_code || '').toUpperCase().trim();
                        const name = (arc.department_name || '').toUpperCase();
                        return code === 'FAT' || code === 'FIN' || name.includes('FINANCE') || name.includes('ACCOUNTING');
                    });
                }
            }
            return list;
        },

        getSelectedQueueArchive() {
            if (!this.slotAssignForm.archive_id) return null;
            return (this.unassignedArchivesList || []).find(a => a.id == this.slotAssignForm.archive_id) || null;
        },

        onQueueArchiveSelected(archiveId) {
            if (!archiveId) return;
            const arc = (this.unassignedArchivesList || []).find(a => a.id == archiveId);
            if (arc && !arc.is_approved) {
                this.openVerifyModal(arc);
            }
        },

        openVerifyModal(archiveData) {
            this.verifyModalData = archiveData;
            this.showRejectInput = false;
            this.rejectionNote = '';
            this.verifyModalOpen = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
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
            if (action === 'reject') {
                if (!this.rejectionNote.trim()) {
                    alert('Mohon masukkan alasan penolakan.');
                    return;
                }
                this.verifyingArchive = true;
                try {
                    const csrfToken = '{{ csrf_token() }}';
                    const response = await fetch(`/archives/${this.verifyModalData.id}/verify`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            action: 'reject',
                            rejection_note: this.rejectionNote
                        })
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        this.showToast('Pengajuan arsip ditolak dan dikembalikan ke draft.');
                        this.closeVerifyModal();
                        this.slotAssignForm.archive_id = '';
                        await this.fetchData();
                    } else {
                        alert(result.message || 'Gagal memproses penolakan arsip.');
                    }
                } catch(e) {
                    console.error('Error rejecting archive:', e);
                    alert('Terjadi kesalahan koneksi saat menolak pengajuan.');
                } finally {
                    this.verifyingArchive = false;
                }
                return;
            }

            // Action === 'approve'
            this.verifyingArchive = true;
            try {
                this.slotAssignForm.archive_id = this.verifyModalData.id;
                this.slotAssignMode = 'existing_archive';
                this.closeVerifyModal();
                await this.executeAssignSlot(true);
            } catch(e) {
                console.error('Error approving and placing archive:', e);
            } finally {
                this.verifyingArchive = false;
            }
        },

        selectSlotForDetail(slot) {
            this.selectedSlotDetail = slot;
            if (!slot.archive) {
                let defaultDeptId = '';
                const allowedDeptIds = this.getCurrentRackAllowedDeptIds();
                if (allowedDeptIds.length === 1) {
                    defaultDeptId = allowedDeptIds[0];
                }

                const now = new Date();
                const yyyy = now.getFullYear();
                const mm = String(now.getMonth() + 1).padStart(2, '0');

                this.slotAssignMode = 'create_new';
                this.slotAssignForm = {
                    department_id: defaultDeptId,
                    sub_department_id: '',
                    title: '',
                    box_number: '',
                    periode_doc: `${yyyy}/${mm}`,
                    document_type: 'UMUM',
                    retention_years: 5,
                    content_description: '',
                    archive_id: '',
                };
                if (defaultDeptId) {
                    this.onDepartmentChange();
                }
            }
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        async submitAssignSlot() {
            if (!this.selectedSlotDetail || !this.selectedRackForModal) return;

            const allowedDeptIds = this.getCurrentRackAllowedDeptIds();
            if (allowedDeptIds.length > 0) {
                const allowedDeptNames = this.getCurrentRackCustomDeptNames();

                if (this.slotAssignMode === 'create_new') {
                    if (!allowedDeptIds.includes(Number(this.slotAssignForm.department_id))) {
                        alert(`⚠️ Akses Ditolak: Lokasi ini dikhususkan (Custom) untuk ${allowedDeptNames}. Dokumen dari departemen lain tidak diizinkan.`);
                        return;
                    }
                } else {
                    const selectedArc = this.unassignedArchivesList.find(a => a.id == this.slotAssignForm.archive_id);
                    if (selectedArc && !allowedDeptIds.includes(Number(selectedArc.department_id))) {
                        alert(`⚠️ Akses Ditolak: Lokasi ini dikhususkan (Custom) untuk ${allowedDeptNames}. Dokumen dari departemen lain (${selectedArc.department_code || 'Lain'}) tidak dapat dialokasikan ke sini.`);
                        return;
                    }
                }
            }

            if (this.slotAssignMode === 'create_new') {
                if (!this.slotAssignForm.department_id) {
                    alert('Silakan pilih Departemen terlebih dahulu!');
                    return;
                }
                if (!this.slotAssignForm.title || !this.slotAssignForm.title.trim()) {
                    alert('Silakan isi Judul Dokumen!');
                    return;
                }
                await this.executeAssignSlot(false);
            } else {
                if (!this.slotAssignForm.archive_id) {
                    alert('Silakan pilih salah satu box arsip dari antrean!');
                    return;
                }
                const selectedArc = this.getSelectedQueueArchive() || this.unassignedArchivesList.find(a => a.id == this.slotAssignForm.archive_id);
                if (!selectedArc) {
                    alert('Data arsip terpilih tidak ditemukan.');
                    return;
                }

                // Jika belum di-approve, buka modal verifikasi pengajuan
                if (!selectedArc.is_approved) {
                    this.openVerifyModal(selectedArc);
                    return;
                }

                // Jika sudah di-approve, langsung konfirmasi penempatan ke slot
                const boxInfo = `[${selectedArc.department_code || 'Dept'}] ${selectedArc.box_number || 'Box #' + selectedArc.id} - ${selectedArc.title}`;
                const confirmMsg = `VERIFIKASI FISIK & CHECKLIST PENEMPATAN:\n\n1. Kondisi fisik wadah box baik dan tersegel?\n2. Berkas sesuai dokumen pengajuan?\n\nTempatkan box ${boxInfo} ke Slot ${this.selectedSlotDetail.slot_code} pada Rak ${this.selectedRackForModal.rack_code}?`;
                if (!confirm(confirmMsg)) {
                    return;
                }
                await this.executeAssignSlot(false);
            }
        },

        async executeAssignSlot(isFromVerification = false) {
            this.slotAssignLoading = true;
            try {
                const payload = {
                    sap_level: this.selectedSlotDetail.sap_level,
                    layer: this.selectedSlotDetail.layer,
                    slot_number: this.selectedSlotDetail.slot_number,
                    slot_code: this.selectedSlotDetail.slot_code,
                    mode: this.slotAssignMode,
                    ...this.slotAssignForm,
                };

                const res = await fetch(`/api/warehouse/locations/${this.selectedRackForModal.id}/slots/assign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    this.showToast(`✅ ${data.message}`);
                    if (data.slot) {
                        if (!this.selectedRackForModal.slots) this.selectedRackForModal.slots = [];
                        const idx = this.selectedRackForModal.slots.findIndex(s => s.sap_level == data.slot.sap_level && s.layer == data.slot.layer && s.slot_number == data.slot.slot_number);
                        if (idx >= 0) {
                            this.selectedRackForModal.slots[idx] = data.slot;
                        } else {
                            this.selectedRackForModal.slots.push(data.slot);
                        }
                        this.selectedSlotDetail = data.slot;
                    }
                    this.slotAssignForm.archive_id = '';
                    await this.fetchData();
                } else {
                    alert(data.message || 'Gagal menyimpan dokumen ke slot.');
                }
            } catch (err) {
                console.error('Error assigning slot:', err);
                alert('Terjadi kesalahan jaringan saat menyimpan dokumen.');
            } finally {
                this.slotAssignLoading = false;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        async unassignCurrentSlot() {
            if (!this.selectedSlotDetail || !this.selectedRackForModal) return;
            const ok = await window.showConfirmModal({
                title: 'Kosongkan Slot Rak',
                message: `Apakah Anda yakin ingin mengosongkan slot ${this.selectedSlotDetail.slot_code}? Box arsip akan dilepaskan dari slot rak ini.`,
                type: 'danger',
                confirmText: 'Ya, Kosongkan'
            });
            if (!ok) return;

            this.slotAssignLoading = true;
            try {
                const payload = {
                    sap_level: this.selectedSlotDetail.sap_level,
                    layer: this.selectedSlotDetail.layer,
                    slot_number: this.selectedSlotDetail.slot_number,
                };

                const res = await fetch(`/api/warehouse/locations/${this.selectedRackForModal.id}/slots/unassign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    this.showToast(`🗑️ ${data.message}`);
                    this.selectedSlotDetail.archive = null;
                    this.selectedSlotDetail.status = 'empty';
                    const idx = this.selectedRackForModal.slots.findIndex(s => s.sap_level == payload.sap_level && s.layer == payload.layer && s.slot_number == payload.slot_number);
                    if (idx >= 0) {
                        this.selectedRackForModal.slots[idx].archive = null;
                        this.selectedRackForModal.slots[idx].status = 'empty';
                    }
                    await this.fetchData();
                    this.selectSlotForDetail(this.selectedSlotDetail);
                } else {
                    alert(data.message || 'Gagal mengosongkan slot.');
                }
            } catch (err) {
                console.error('Error unassigning slot:', err);
                alert('Terjadi kesalahan jaringan.');
            } finally {
                this.slotAssignLoading = false;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

