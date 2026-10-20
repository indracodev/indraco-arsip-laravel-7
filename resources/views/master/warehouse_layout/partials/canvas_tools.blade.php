        applyCanvasSize(w, h) {
            const validW = Math.max(600, Math.min(4000, parseInt(w) || 950));
            const validH = Math.max(500, Math.min(3000, parseInt(h) || 750));

            this.canvasWidth = validW;
            this.canvasHeight = validH;

            if (this.canvas) {
                this.canvas.width = validW;
                this.canvas.height = validH;
            }

            try {
                localStorage.setItem('wh_canvas_w', validW);
                localStorage.setItem('wh_canvas_h', validH);
            } catch (e) {}

            this.renderCanvas();
        },

        openCanvasResizeModal() {
            this.canvasResizeForm = {
                width: this.canvasWidth,
                height: this.canvasHeight
            };
            this.canvasResizeModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        submitCanvasResize() {
            const w = parseInt(this.canvasResizeForm.width);
            const h = parseInt(this.canvasResizeForm.height);
            if (isNaN(w) || isNaN(h) || w < 600 || h < 500) {
                alert('Perhatian: Ukuran minimal canvas adalah Lebar 600px dan Tinggi 500px!');
                return;
            }
            this.applyCanvasSize(w, h);
            this.canvasResizeModalOpen = false;
            this.showToast(`📐 Ukuran Canvas berhasil diubah menjadi ${w} x ${h} px.`);
        },

        setPresetCanvasSize(w, h) {
            this.canvasResizeForm.width = w;
            this.canvasResizeForm.height = h;
        },

        startCanvasResize(e) {
            this.isResizingCanvas = true;
            this.resizeCanvasStartX = e.clientX;
            this.resizeCanvasStartY = e.clientY;
            this.initialCanvasW = this.canvasWidth;
            this.initialCanvasH = this.canvasHeight;

            const onMouseMove = (moveEv) => {
                if (!this.isResizingCanvas) return;
                const deltaX = Math.round((moveEv.clientX - this.resizeCanvasStartX) / this.scale);
                const deltaY = Math.round((moveEv.clientY - this.resizeCanvasStartY) / this.scale);

                const newW = Math.max(600, Math.min(4000, this.initialCanvasW + deltaX));
                const newH = Math.max(500, Math.min(3000, this.initialCanvasH + deltaY));

                this.applyCanvasSize(newW, newH);
            };

            const onMouseUp = () => {
                if (this.isResizingCanvas) {
                    this.isResizingCanvas = false;
                    window.removeEventListener('mousemove', onMouseMove);
                    window.removeEventListener('mouseup', onMouseUp);
                    this.showToast(`📐 Ukuran Canvas diset ke ${this.canvasWidth} x ${this.canvasHeight} px.`);
                }
            };

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        },

        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
            const container = document.getElementById('canvasWorkspaceWrapper');
            
            if (this.isFullscreen) {
                if (container && container.requestFullscreen) {
                    container.requestFullscreen().catch(err => {
                        console.log("Browser fullscreen error:", err);
                    });
                }
                this.showToast('📺 Mode Canvas 1 Layar Penuh (Fullscreen) Diaktifkan. Tekan Esc untuk Keluar.');
            } else {
                if (document.fullscreenElement && document.exitFullscreen) {
                    document.exitFullscreen().catch(err => {
                        console.log("Browser exit fullscreen error:", err);
                    });
                }
                this.showToast('📺 Keluar dari Mode Layar Penuh');
            }

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                this.renderCanvas();
            });
        },

        showToast(msg) {
            this.toastMessage = msg;
            this.toastOpen = true;
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => {
                this.toastOpen = false;
            }, 3000);
        },
        
        docModalOpen: false,
        selectedDoc: null,

        bookingModalOpen: false,
        bookingForm: { department_id: '', booking_notes: '' },

        moveModalOpen: false,

        batchResizeModalOpen: false,
        batchResizeForm: { canvas_width: 25, canvas_height: 140 },

        batchDeptModalOpen: false,
        batchDeptForm: { assigned_department_id: '', assigned_department_ids: [] },

        batchColorModalOpen: false,
        batchColorForm: { custom_color: '#3b82f6' },

        openBatchResizeModal() {
            if (this.selectedLocations.length === 0) return;
            const refObj = this.selectedLocation || this.selectedLocations[0];
            this.batchResizeForm = {
                canvas_width: refObj.canvas_width || 25,
                canvas_height: refObj.canvas_height || 140,
            };
            this.batchResizeModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        openBatchDeptModal() {
            if (this.selectedLocations.length === 0) return;
            const refObj = this.selectedLocation || this.selectedLocations[0];
            const deptIds = this.getAllowedDeptIds(refObj);
            this.batchDeptForm = {
                assigned_department_id: deptIds.length > 0 ? deptIds[0] : (refObj.assigned_department_id || null),
                assigned_department_ids: deptIds.map(Number),
            };
            this.batchDeptModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        isBatchDeptSelected(deptId) {
            if (!this.batchDeptForm || !Array.isArray(this.batchDeptForm.assigned_department_ids)) return false;
            return this.batchDeptForm.assigned_department_ids.map(Number).includes(Number(deptId));
        },

        toggleBatchDeptSelection(deptId) {
            const id = Number(deptId);
            let current = Array.isArray(this.batchDeptForm.assigned_department_ids)
                ? this.batchDeptForm.assigned_department_ids.map(Number)
                : [];
            const idx = current.indexOf(id);
            if (idx >= 0) {
                current.splice(idx, 1);
            } else {
                current.push(id);
            }
            this.batchDeptForm.assigned_department_ids = [...current];
            this.batchDeptForm.assigned_department_id = current.length > 0 ? current[0] : null;
        },

        openBatchColorModal() {
            if (this.selectedLocations.length === 0) return;
            const refObj = this.selectedLocation || this.selectedLocations[0];
            this.batchColorForm = {
                custom_color: refObj.custom_color || '#3b82f6',
            };
            this.batchColorModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        async bulkToggleLockLocations(targetLockState) {
            if (this.selectedLocations.length === 0) return;
            const lockBool = !!targetLockState;
            const toUpdate = [...this.selectedLocations];

            for (const loc of toUpdate) {
                loc.is_locked = lockBool;
                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            rack_code: loc.rack_code,
                            location_type: loc.location_type,
                            room_sector: loc.room_sector,
                            canvas_x: loc.canvas_x,
                            canvas_y: loc.canvas_y,
                            canvas_width: loc.canvas_width,
                            canvas_height: loc.canvas_height,
                            is_locked: lockBool,
                        })
                    });
                } catch (err) {
                    console.error('Failed to update lock state for loc:', loc.id, err);
                }
            }

            const actionLabel = lockBool ? '🔒 dikunci' : '🔓 dibuka kuncinya';
            this.showToast(`${actionLabel} secara massal untuk ${toUpdate.length} object terseleksi.`);
            this.renderCanvas();
        },

        async submitBatchResize() {
            if (this.selectedLocations.length === 0) return;
            const newW = parseInt(this.batchResizeForm.canvas_width);
            const newH = parseInt(this.batchResizeForm.canvas_height);

            if (isNaN(newW) || isNaN(newH) || newW < 20 || newH < 20) {
                alert('Perhatian: Ukuran minimal lebar dan tinggi adalah 20px!');
                return;
            }

            const toUpdate = [...this.selectedLocations];
            this.batchResizeModalOpen = false;

            for (const loc of toUpdate) {
                loc.canvas_width = newW;
                loc.canvas_height = newH;

                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            rack_code: loc.rack_code,
                            location_type: loc.location_type,
                            room_sector: loc.room_sector,
                            canvas_x: loc.canvas_x,
                            canvas_y: loc.canvas_y,
                            canvas_width: newW,
                            canvas_height: newH,
                        })
                    });
                } catch (err) {
                    console.error('Failed batch resize for loc:', loc.id, err);
                }
            }

            this.showToast(`📐 Ukuran (Width & Height) ${toUpdate.length} object berhasil diperbarui menjadi ${newW}x${newH}px.`);
            await this.fetchData();
        },

        async submitBatchDepartment() {
            if (this.selectedLocations.length === 0) return;
            const deptIds = (this.batchDeptForm.assigned_department_ids || []).map(Number);
            const primaryDeptId = deptIds.length > 0 ? deptIds[0] : null;
            const targetIds = this.selectedLocations.map(l => l.id);
            this.batchDeptModalOpen = false;

            for (const loc of this.selectedLocations) {
                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            rack_code: loc.rack_code,
                            location_type: loc.location_type,
                            room_sector: loc.room_sector,
                            canvas_x: loc.canvas_x,
                            canvas_y: loc.canvas_y,
                            canvas_width: loc.canvas_width,
                            canvas_height: loc.canvas_height,
                            assigned_department_id: primaryDeptId,
                            assigned_department_ids: deptIds,
                        })
                    });
                } catch (err) {
                    console.error('Failed batch department update for loc:', loc.id, err);
                }
            }

            this.showToast(`🏢 Alokasi Departemen ${targetIds.length} object berhasil diperbarui.`);
            await this.fetchData();
            this.selectedLocations = this.locations.filter(l => targetIds.includes(l.id));
            this.renderCanvas();
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        async submitBatchColor() {
            if (this.selectedLocations.length === 0) return;
            const newColor = this.batchColorForm.custom_color;
            const toUpdate = [...this.selectedLocations];
            this.batchColorModalOpen = false;

            for (const loc of toUpdate) {
                loc.custom_color = newColor;

                try {
                    await fetch(`/api/warehouse/locations/${loc.id}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            rack_code: loc.rack_code,
                            location_type: loc.location_type,
                            room_sector: loc.room_sector,
                            canvas_x: loc.canvas_x,
                            canvas_y: loc.canvas_y,
                            canvas_width: loc.canvas_width,
                            canvas_height: loc.canvas_height,
                            custom_color: newColor,
                        })
                    });
                } catch (err) {
                    console.error('Failed batch color update for loc:', loc.id, err);
                }
            }

            this.showToast(`🎨 Warna custom ${toUpdate.length} object berhasil diperbarui.`);
            await this.fetchData();
        },

        alignGroupHorizontally() {
            if (this.selectedLocations.length < 2) return;

            const lockedTarget = this.selectedLocations.find(t => t.is_locked !== false);
            if (lockedTarget) {
                const typeLabel = lockedTarget.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${lockedTarget.rack_code}' sedang TERKUNCI. Buka kunci terlebih dahulu.`);
                return;
            }

            // Sort selected objects by their current canvas_x coordinate
            const targets = [...this.selectedLocations].sort((a, b) => a.canvas_x - b.canvas_x);

            const startX = targets[0].canvas_x;
            const startY = targets[0].canvas_y;
            const gap = 4; // 4px clean gap between adjacent racks

            let currentX = startX;

            const firstRoomSector = targets[0].room_sector;
            const parentRoom = this.locations.find(l => 
                l.location_type === 'room' && 
                (l.room_sector === firstRoomSector || l.rack_code === firstRoomSector)
            );

            const totalW = targets.reduce((sum, t) => sum + t.canvas_width, 0) + (targets.length - 1) * gap;

            if (parentRoom) {
                const maxX = parentRoom.canvas_x + parentRoom.canvas_width - 8;
                if ((currentX + totalW) > maxX) {
                    currentX = Math.max(parentRoom.canvas_x + 8, maxX - totalW);
                }
            }

            targets.forEach(t => {
                t.canvas_x = currentX;
                t.canvas_y = startY;
                currentX += t.canvas_width + gap;
            });

            this.saveGroupKeyboardNudge(targets);

            this.showToast(`↔️ Kelompok (${targets.length} object) berhasil dirapatkan berjejer sisi lebar (Horizontal Row).`);
            this.renderCanvas();
        },

        alignGroupVertically() {
            if (this.selectedLocations.length < 2) return;

            const lockedTarget = this.selectedLocations.find(t => t.is_locked !== false);
            if (lockedTarget) {
                const typeLabel = lockedTarget.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${lockedTarget.rack_code}' sedang TERKUNCI. Buka kunci terlebih dahulu.`);
                return;
            }

            // Sort selected objects by their current canvas_y coordinate
            const targets = [...this.selectedLocations].sort((a, b) => a.canvas_y - b.canvas_y);

            const startX = targets[0].canvas_x;
            const startY = targets[0].canvas_y;
            const gap = 4; // 4px clean gap between adjacent racks

            let currentY = startY;

            const firstRoomSector = targets[0].room_sector;
            const parentRoom = this.locations.find(l => 
                l.location_type === 'room' && 
                (l.room_sector === firstRoomSector || l.rack_code === firstRoomSector)
            );

            const totalH = targets.reduce((sum, t) => sum + t.canvas_height, 0) + (targets.length - 1) * gap;

            if (parentRoom) {
                const maxY = parentRoom.canvas_y + parentRoom.canvas_height - 8;
                if ((currentY + totalH) > maxY) {
                    currentY = Math.max(parentRoom.canvas_y + 30, maxY - totalH);
                }
            }

            targets.forEach(t => {
                t.canvas_x = startX;
                t.canvas_y = currentY;
                currentY += t.canvas_height + gap;
            });

            this.saveGroupKeyboardNudge(targets);

            this.showToast(`↕️ Kelompok (${targets.length} object) berhasil dirapatkan bertingkat sisi panjang (Vertical Column).`);
            this.renderCanvas();
        },
