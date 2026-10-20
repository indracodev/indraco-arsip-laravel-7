
        openDocDetail(arc) {
            this.selectedDoc = arc;
            this.docModalOpen = true;
        },

        openBookingModal() {
            if (!this.selectedLocation) return;
            this.bookingForm.department_id = this.selectedLocation.assigned_department_id || '';
            this.bookingForm.booking_notes = '';
            this.bookingModalOpen = true;
        },

        async submitBooking() {
            if (!this.selectedLocation) return;
            try {
                const res = await fetch(`/api/warehouse/locations/${this.selectedLocation.id}/book`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.bookingForm)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.bookingModalOpen = false;
                    const targetId = this.selectedLocation.id;
                    await this.fetchData();
                    const updated = this.locations.find(l => Number(l.id) === Number(targetId));
                    if (updated) this.selectedLocations = [updated];
                }
            } catch (err) {
                alert('Gagal memproses booking slot rak.');
            }
        },

        async unbookLocation() {
            if (!this.selectedLocation) return;
            const ok = await window.showConfirmModal({
                title: 'Lepas Status Booking',
                message: 'Lepas status booking slot rak ini?',
                type: 'warning',
                confirmText: 'Ya, Lepaskan'
            });
            if (!ok) return;
            try {
                const targetId = this.selectedLocation.id;
                const res = await fetch(`/api/warehouse/locations/${targetId}/unbook`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    await this.fetchData();
                    const updated = this.locations.find(l => Number(l.id) === Number(targetId));
                    if (updated) this.selectedLocations = [updated];
                }
            } catch (err) {
                alert('Gagal melepas status booking.');
            }
        },

        openEditModal() {
            if (!this.selectedLocation) return;
            const deptIds = this.getAllowedDeptIds(this.selectedLocation);
            this.editForm = {
                room_sector: this.selectedLocation.room_sector || 'R1',
                rack_code: this.selectedLocation.rack_code,
                shelf_code: this.selectedLocation.shelf_code,
                box_capacity: this.selectedLocation.box_capacity,
                assigned_department_id: deptIds.length > 0 ? deptIds[0] : (this.selectedLocation.assigned_department_id || null),
                assigned_department_ids: deptIds.map(Number),
                is_locked: this.selectedLocation.is_locked !== false,
                custom_color: this.selectedLocation.custom_color || '',
                canvas_width: this.selectedLocation.canvas_width,
                canvas_height: this.selectedLocation.canvas_height,
                orientation: this.selectedLocation.orientation || 'horizontal',
                rotation_angle: this.selectedLocation.rotation_angle || 0,
            };
            this.editModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        isDeptSelected(deptId) {
            if (!this.editForm || !Array.isArray(this.editForm.assigned_department_ids)) return false;
            return this.editForm.assigned_department_ids.map(Number).includes(Number(deptId));
        },

        toggleDeptSelection(deptId) {
            const id = Number(deptId);
            let current = Array.isArray(this.editForm.assigned_department_ids)
                ? this.editForm.assigned_department_ids.map(Number)
                : [];
            const idx = current.indexOf(id);
            if (idx >= 0) {
                current.splice(idx, 1);
            } else {
                current.push(id);
            }
            this.editForm.assigned_department_ids = [...current];
            this.editForm.assigned_department_id = current.length > 0 ? current[0] : null;
        },

        async submitEdit() {
            if (!this.selectedLocation) return;
            const targetId = this.selectedLocation.id;
            if (this.selectedLocation.location_type !== 'room') {
                const testW = parseInt(this.editForm.canvas_width);
                const testH = parseInt(this.editForm.canvas_height);
                const minDim = Math.min(testW, testH);
                const maxDim = Math.max(testW, testH);
                if (minDim < 25 || maxDim < 100) {
                    alert('Perhatian: Ukuran minimal objek Rak adalah Sisi Panjang minimal 100px dan Sisi Tebal/Lebar minimal 25px!');
                    return;
                }
                if (this.hasRackCollision(this.selectedLocation, this.selectedLocation.canvas_x, this.selectedLocation.canvas_y, testW, testH)) {
                    alert('Perhatian: Perubahan dimensi rak akan bertabrakan / tumpang tindih dengan rak lain di sekitarnya!');
                    return;
                }
            }
            try {
                this.editForm.assigned_department_ids = (this.editForm.assigned_department_ids || []).map(Number);
                this.editForm.assigned_department_id = this.editForm.assigned_department_ids.length > 0 ? this.editForm.assigned_department_ids[0] : null;
                const res = await fetch(`/api/warehouse/locations/${targetId}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.editForm)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.editModalOpen = false;
                    await this.fetchData();
                    const updated = this.locations.find(l => Number(l.id) === Number(targetId));
                    if (updated) {
                        this.selectedLocations = [updated];
                        if (this.selectedRackForModal && Number(this.selectedRackForModal.id) === Number(targetId)) {
                            this.selectedRackForModal = updated;
                        }
                    }
                    this.renderCanvas();
                    if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
                } else {
                    alert(data.message || 'Gagal menyimpan perubahan.');
                }
            } catch (err) {
                alert('Gagal mengedit data object.');
            }
        },

        async deleteSelectedLocation() {
            if (this.selectedLocations.length === 0) return;

            let confirmMsg = '';
            if (this.selectedLocations.length === 1) {
                confirmMsg = `Apakah Anda yakin ingin menghapus object '${this.selectedLocations[0].rack_code}' dari canvas layout gudang?`;
            } else {
                confirmMsg = `Apakah Anda yakin ingin menghapus ${this.selectedLocations.length} object terseleksi dari canvas layout gudang?`;
            }

            const ok = await window.showConfirmModal({
                title: 'Hapus Object Canvas',
                message: confirmMsg,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            });
            if (!ok) return;

            const toDelete = [...this.selectedLocations];
            this.selectedLocations = [];

            for (const loc of toDelete) {
                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/delete`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                } catch (err) {
                    console.error('Error deleting location:', err);
                }
            }

            this.showToast(`🗑️ ${toDelete.length} object berhasil dihapus dari canvas layout.`);
            await this.fetchData();
        },

