<script>
function masterWarehousesManager() {
    return {
        formatDate(d) {
            if (!d) return '-';
            if (typeof d === 'string' && (d.includes('T') || /^\d{4}-\d{2}-\d{2}/.test(d))) {
                try {
                    const dt = new Date(d);
                    if (!isNaN(dt.getTime())) {
                        return dt.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
                    }
                } catch(e) { return d; }
            }
            return d;
        },
        openAddWarehouse: false,
        openAddLocation: false,
        editWarehouseItem: null,
        editLocationItem: null,
        searchQuery: '',
        sortByField: 'code',
        sortDirection: 'asc',
        submitting: false,
        isLoading: false,
        warehouses: @json($warehouses),

        init() {
            this.$watch('editLocationItem', (val) => {
                if (val) {
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                }
            });
            this.$watch('editWarehouseItem', (val) => {
                if (val) {
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                }
            });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        // 100-Box Denah Rak Modal State
        rackGridModalOpen: false,
        selectedRackForModal: null,
        selectedSlotDetail: null,
        slotFilterStatus: 'all',
        slotSearchQuery: '',
        slotDataLoading: false,
        slotAssignLoading: false,
        warehouseToggleLoading: null,
        cachedLayoutData: null,

        get filteredWarehouses() {
            let res = [...this.warehouses];
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(wh => {
                    const matchWh = (wh.code && wh.code.toLowerCase().includes(q)) ||
                                    (wh.name && wh.name.toLowerCase().includes(q)) ||
                                    (wh.address && wh.address.toLowerCase().includes(q));
                    const matchLoc = wh.locations && wh.locations.some(loc => 
                        (loc.rack_code && loc.rack_code.toLowerCase().includes(q)) ||
                        (loc.shelf_code && loc.shelf_code.toLowerCase().includes(q)) ||
                        (loc.full_location && loc.full_location.toLowerCase().includes(q))
                    );
                    return matchWh || matchLoc;
                });
            }
            res.sort((a, b) => {
                let valA = a[this.sortByField] ?? '';
                let valB = b[this.sortByField] ?? '';
                if (this.sortByField === 'locations_count') {
                    valA = a.locations ? a.locations.length : 0;
                    valB = b.locations ? b.locations.length : 0;
                    return this.sortDirection === 'asc' ? valA - valB : valB - valA;
                }
                if (typeof valA === 'string') valA = valA.toLowerCase();
                if (typeof valB === 'string') valB = valB.toLowerCase();
                if (valA < valB) return this.sortDirection === 'asc' ? -1 : 1;
                if (valA > valB) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
            return res;
        },

        async openRackSlotModal(loc) {
            if (!loc) return;
            this.rackGridModalOpen = true;
            this.selectedSlotDetail = null;
            this.slotFilterStatus = 'all';
            this.slotSearchQuery = '';
            this.slotDataLoading = true;

            // Set preliminary info
            this.selectedRackForModal = {
                id: loc.id,
                rack_code: loc.rack_code,
                shelf_code: loc.shelf_code,
                room_sector: loc.room_sector || loc.rack_code.split('-')[1] || 'Umum',
                box_capacity: loc.box_capacity || 100,
                current_box_count: loc.current_box_count || 0,
                slots: []
            };

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });

            try {
                // Fetch live slots data from API
                const res = await fetch('{{ route("api.warehouse.layout_data") }}');
                const data = await res.json();
                this.cachedLayoutData = data.locations || [];

                // Find matching location by ID or rack code
                const matched = this.cachedLayoutData.find(l => l.id === loc.id || l.rack_code === loc.rack_code);
                if (matched) {
                    this.selectedRackForModal = matched;
                }
            } catch (err) {
                console.error('Failed to load rack slot data', err);
            } finally {
                this.slotDataLoading = false;

                // Auto-select first occupied slot or first top slot
                const slots = this.selectedRackForModal?.slots || [];
                const firstOccupied = slots.find(s => s.archive || s.status === 'filled');
                if (firstOccupied) {
                    this.selectedSlotDetail = firstOccupied;
                } else if (slots.length > 0) {
                    this.selectedSlotDetail = slots[0];
                }

                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        slotToggleLoading: false,

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
                        is_active: true,
                        archive: null
                    });
                }
            }
            return result;
        },

        getRackSlotStats(rack) {
            if (!rack) return { total: 100, empty: 100, filled: 0, expired: 0, inactive: 0 };
            const slots = rack.slots || [];
            let empty = 0, filled = 0, expired = 0, inactive = 0;

            if (slots.length > 0) {
                slots.forEach(s => {
                    if (s.is_active === false || s.status === 'inactive') {
                        inactive++;
                    } else if (s.status === 'expired' || s.archive?.is_expired) {
                        expired++;
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
                inactive: inactive
            };
        },

        getSlotStyleClasses(slot) {
            if (!slot) return 'bg-slate-100 dark:bg-slate-950 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-800';
            if (slot.is_active === false || slot.status === 'inactive') {
                return 'bg-slate-100/90 text-slate-500 border-slate-300 dark:bg-slate-900/60 dark:text-slate-400 dark:border-slate-800 opacity-60 hover:opacity-100 transition shadow-xs';
            }
            const isExpired = slot.status === 'expired' || slot.archive?.is_expired;
            const isNearExp = !isExpired && (slot.status === 'near_exp' || slot.is_near_exp || slot.archive?.is_near_exp);
            const isFilled = (slot.status === 'filled' || slot.archive) && !isExpired && !isNearExp;

            if (isExpired) {
                return 'bg-rose-600 !text-white border-rose-700 hover:bg-rose-700 dark:bg-rose-600 dark:border-rose-500 shadow-md animate-pulse-expired';
            }
            if (isNearExp) {
                return 'bg-rose-500/20 text-rose-300 border border-rose-500 hover:bg-rose-500/30 dark:bg-rose-500/20 dark:text-rose-300 dark:border-rose-500 shadow-xs';
            }
            if (isFilled) {
                return 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100 hover:border-amber-400 dark:bg-amber-950/35 dark:text-amber-200 dark:border-amber-600/50 dark:hover:bg-amber-900/40 dark:hover:border-amber-400 shadow-xs';
            }
            return 'bg-emerald-50/70 text-emerald-800 border-emerald-200 hover:border-emerald-400 hover:bg-emerald-100/70 dark:bg-slate-950/80 dark:text-emerald-400 dark:border-slate-800 dark:hover:border-emerald-500/60 dark:hover:bg-emerald-950/25 shadow-xs';
        },

        isSlotMatchFilter(slot) {
            if (!slot) return false;
            const isInactive = slot.is_active === false || slot.status === 'inactive';
            const isExpired = !isInactive && (slot.status === 'expired' || slot.archive?.is_expired);
            const isFilled = !isInactive && !isExpired && (slot.status === 'filled' || slot.archive);
            const isEmpty = !isInactive && !isExpired && !isFilled;

            if (this.slotFilterStatus === 'empty' && !isEmpty) return false;
            if (this.slotFilterStatus === 'filled' && !isFilled) return false;
            if (this.slotFilterStatus === 'expired' && !isExpired) return false;
            if (this.slotFilterStatus === 'inactive' && !isInactive) return false;

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

        selectSlotForDetail(slot) {
            this.selectedSlotDetail = slot;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        async toggleSlotActiveStatus(slot) {
            if (!slot || !this.selectedRackForModal) return;
            const newActive = slot.is_active === false ? true : false;

            this.slotToggleLoading = true;
            try {
                const res = await fetch(`/api/warehouse/locations/${this.selectedRackForModal.id}/slots/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        sap_level: slot.sap_level,
                        layer: slot.layer,
                        slot_number: slot.slot_number,
                        is_active: newActive
                    })
                });

                const data = await res.json();
                if (data.success) {
                    slot.is_active = data.is_active;
                    slot.status = data.status;

                    if (!this.selectedRackForModal.slots) {
                        this.selectedRackForModal.slots = [];
                    }
                    const idx = this.selectedRackForModal.slots.findIndex(s => s.sap_level == slot.sap_level && s.layer == slot.layer && s.slot_number == slot.slot_number);
                    if (idx !== -1) {
                        this.selectedRackForModal.slots[idx].is_active = data.is_active;
                        this.selectedRackForModal.slots[idx].status = data.status;
                    } else {
                        this.selectedRackForModal.slots.push({
                            id: data.slot.id,
                            sap_level: slot.sap_level,
                            layer: slot.layer,
                            layer_label: slot.layer_label,
                            slot_number: slot.slot_number,
                            slot_code: slot.slot_code,
                            status: data.status,
                            is_active: data.is_active,
                            archive: slot.archive || null
                        });
                    }

                    if (this.selectedSlotDetail && this.selectedSlotDetail.slot_code === slot.slot_code) {
                        this.selectedSlotDetail.is_active = data.is_active;
                        this.selectedSlotDetail.status = data.status;
                    }

                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                } else {
                    alert(data.message || 'Gagal mengubah status slot rak.');
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan koneksi saat mengubah status slot rak.');
            } finally {
                this.slotToggleLoading = false;
            }
        },

        async unassignCurrentSlot() {
            if (!this.selectedSlotDetail || !this.selectedSlotDetail.archive || !this.selectedRackForModal) return;
            const boxNum = this.selectedSlotDetail.archive.box_number || 'Box';
            const ok = await window.showConfirmModal({
                title: 'Lepas Arsip dari Slot',
                message: `Lepas arsip [${boxNum}] dari slot ${this.selectedSlotDetail.slot_code}?`,
                type: 'warning',
                confirmText: 'Ya, Lepaskan'
            });
            if (!ok) return;

            this.slotAssignLoading = true;
            try {
                const res = await fetch(`/api/warehouse/locations/${this.selectedRackForModal.id}/slots/unassign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        sap_level: this.selectedSlotDetail.sap_level,
                        layer: this.selectedSlotDetail.layer,
                        slot_number: this.selectedSlotDetail.slot_number,
                        slot_id: this.selectedSlotDetail.id,
                        slot_code: this.selectedSlotDetail.slot_code
                    })
                });

                const data = await res.json();
                if (data.success) {
                    // Update local slot status
                    this.selectedSlotDetail.archive = null;
                    this.selectedSlotDetail.status = 'empty';

                    // Update parent rack count
                    if (this.selectedRackForModal.current_box_count > 0) {
                        this.selectedRackForModal.current_box_count--;
                    }

                    // Update warehouse locations list
                    const wh = this.warehouses.find(w => w.id === this.selectedRackForModal.warehouse_id);
                    if (wh && wh.locations) {
                        const locItem = wh.locations.find(l => l.id === this.selectedRackForModal.id);
                        if (locItem && locItem.current_box_count > 0) {
                            locItem.current_box_count--;
                        }
                    }

                    alert(data.message || 'Box berhasil dilepas dari slot rak.');
                } else {
                    alert(data.message || 'Gagal melepaskan box dari slot rak.');
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan koneksi saat melepaskan box arsip.');
            } finally {
                this.slotAssignLoading = false;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        async toggleWarehouseActiveStatus(wh) {
            if (!wh) return;
            const newActive = (wh.is_active === false) ? true : false;
            this.warehouseToggleLoading = wh.id;

            try {
                const res = await fetch(`/master/warehouses/${wh.id}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        is_active: newActive
                    })
                });

                const data = await res.json();
                if (data.success) {
                    wh.is_active = data.is_active;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                } else {
                    alert(data.message || 'Gagal mengubah status aktif gudang.');
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan jaringan saat mengubah status gudang.');
            } finally {
                this.warehouseToggleLoading = null;
            }
        }
    };
}
</script>