        zoomIn() {
            if (this.scale < 2.0) {
                this.scale += 0.15;
                this.renderCanvas();
            }
        },

        zoomOut() {
            if (this.scale > 0.6) {
                this.scale -= 0.15;
                this.renderCanvas();
            }
        },

        resetZoom() {
            this.scale = 1.0;
            this.renderCanvas();
        },

        openAddRoomModal(x = 400, y = 300) {
            this.objectForm = {
                location_type: 'room',
                rack_code: 'GUDANG BARU',
                room_sector: 'SEKTOR BARU',
                box_capacity: 500,
                canvas_width: 160,
                canvas_height: 140,
                custom_color: '#3b82f6',
                canvas_x: Math.max(0, x),
                canvas_y: Math.max(0, y),
            };
            this.objectModalOpen = true;
        },

        openAddRackModal(x = 450, y = 320) {
            this.objectForm = {
                location_type: 'rack',
                rack_code: 'RAK-BARU-01',
                room_sector: 'R1',
                box_capacity: 100,
                canvas_width: 40,
                canvas_height: 120,
                custom_color: '#10b981',
                canvas_x: Math.max(0, x),
                canvas_y: Math.max(0, y),
            };
            this.objectModalOpen = true;
        },

        addRackInsideRoom(room, x, y, defaultQty = 1) {
            const roomSectorName = room.room_sector || room.rack_code;
            let targetX = Math.max(room.canvas_x + 15, x);
            let targetY = Math.max(room.canvas_y + 35, y);
            const rackW = 40;
            const rackH = 120;

            // Auto-find non-colliding spot inside target room
            let collisionTries = 0;
            while (this.hasRackCollision({ id: 0 }, targetX, targetY, rackW, rackH) && collisionTries < 25) {
                targetX += 45;
                if ((targetX + rackW) > (room.canvas_x + room.canvas_width - 10)) {
                    targetX = room.canvas_x + 15;
                    targetY += 35;
                }
                collisionTries++;
            }

            this.objectForm = {
                location_type: 'rack',
                rack_code: 'RAK-' + roomSectorName,
                room_sector: roomSectorName,
                box_capacity: 100,
                canvas_width: rackW,
                canvas_height: rackH,
                custom_color: '#10b981',
                canvas_x: targetX,
                canvas_y: targetY,
                quantity: defaultQty,
            };
            this.contextMenuOpen = false;
            this.objectModalOpen = true;
        },

        openMoveModal(target = null) {
            if (target) this.selectedLocation = target;
            this.moveModalOpen = true;
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        async moveRackToRoom(rack, room) {
            if (!rack || !room) return;
            let targetX = room.canvas_x + 15;
            let targetY = room.canvas_y + 35;
            const roomSectorName = room.room_sector || room.rack_code;

            // Auto-find non-colliding spot inside target room
            let collisionTries = 0;
            while (this.hasRackCollision(rack, targetX, targetY, rack.canvas_width, rack.canvas_height) && collisionTries < 25) {
                targetX += 45;
                if ((targetX + rack.canvas_width) > (room.canvas_x + room.canvas_width - 10)) {
                    targetX = room.canvas_x + 15;
                    targetY += 35;
                }
                collisionTries++;
            }

            try {
                const res = await fetch(`/api/warehouse/locations/${rack.id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        rack_code: rack.rack_code,
                        location_type: 'rack',
                        room_sector: roomSectorName,
                        canvas_x: targetX,
                        canvas_y: targetY,
                        canvas_width: rack.canvas_width,
                        canvas_height: rack.canvas_height,
                    })
                });
                const data = await res.json();
                if (data.success) {
                    alert(`Rak '${rack.rack_code}' berhasil dipindahkan ke Gudang/Sektor '${room.rack_code}' (${roomSectorName}).`);
                    this.moveModalOpen = false;
                    this.contextMenuOpen = false;
                    await this.fetchData();
                    this.selectedLocation = this.locations.find(l => l.id === rack.id);
                }
            } catch (err) {
                alert('Gagal memindahkan posisi rak ke gudang.');
            }
        },

        async duplicateRack(sourceRack) {
            if (!sourceRack) return;

            // Generate smart auto-incremented duplicate code
            const cleanBase = (sourceRack.rack_code || 'RAK').replace(/ \(COPY\d*\)$/i, '').replace(/ - COPY\d*$/i, '');
            let copyIndex = 1;
            let newCode = `${cleanBase} (COPY)`;
            while (this.locations.some(l => l.rack_code === newCode)) {
                copyIndex++;
                newCode = `${cleanBase} (COPY ${copyIndex})`;
            }

            let newX = sourceRack.canvas_x + 20;
            let newY = sourceRack.canvas_y + 20;

            // Clamp inside parent room if source rack is inside a Gudang
            const parentRoom = this.locations.find(loc => {
                if (loc.location_type !== 'room') return false;
                const isSectorMatch = loc.room_sector === sourceRack.room_sector || loc.rack_code === sourceRack.room_sector;
                const isInside = (sourceRack.canvas_x >= loc.canvas_x) && 
                                 ((sourceRack.canvas_x + sourceRack.canvas_width) <= (loc.canvas_x + loc.canvas_width)) &&
                                 (sourceRack.canvas_y >= loc.canvas_y) && 
                                 ((sourceRack.canvas_y + sourceRack.canvas_height) <= (loc.canvas_y + loc.canvas_height));
                return isSectorMatch || isInside;
            });

            if (parentRoom) {
                const maxX = parentRoom.canvas_x + parentRoom.canvas_width - sourceRack.canvas_width - 4;
                const maxY = parentRoom.canvas_y + parentRoom.canvas_height - sourceRack.canvas_height - 4;
                newX = Math.min(maxX, newX);
                newY = Math.min(maxY, newY);
            }

            // Shift position if colliding with existing racks
            let collisionTries = 0;
            while (this.hasRackCollision({ id: 0 }, newX, newY, sourceRack.canvas_width, sourceRack.canvas_height) && collisionTries < 15) {
                newY += 30;
                if (parentRoom && (newY + sourceRack.canvas_height) > (parentRoom.canvas_y + parentRoom.canvas_height - 4)) {
                    newY = parentRoom.canvas_y + 28;
                    newX += 35;
                }
                collisionTries++;
            }

            const duplicateData = {
                location_type: sourceRack.location_type || 'rack',
                rack_code: newCode,
                room_sector: sourceRack.room_sector || 'Umum',
                shelf_code: sourceRack.shelf_code || 'BARIS-01',
                box_capacity: sourceRack.box_capacity || 100,
                custom_color: sourceRack.custom_color || '',
                canvas_x: newX,
                canvas_y: newY,
                canvas_width: sourceRack.canvas_width || 40,
                canvas_height: sourceRack.canvas_height || 120,
                orientation: sourceRack.orientation || 'horizontal',
                rotation_angle: sourceRack.rotation_angle || 0,
                assigned_department_id: sourceRack.assigned_department_id || null,
            };

            try {
                const res = await fetch('{{ route("api.warehouse.locations.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(duplicateData)
                });
                const data = await res.json();
                if (data.success) {
                    const typeLabel = sourceRack.location_type === 'room' ? 'Gudang' : 'Rak';
                    this.showToast(`📋 ${typeLabel} '${sourceRack.rack_code}' berhasil diduplikat menjadi '${newCode}'.`);
                    await this.fetchData();
                    const newLocationObj = this.locations.find(l => l.id === data.location.id);
                    if (newLocationObj) {
                        this.selectedLocation = newLocationObj;
                        this.copiedLocation = JSON.parse(JSON.stringify(newLocationObj));
                    }
                }
            } catch (err) {
                alert('Gagal menduplikat object.');
            }
        },

        async submitCreateObject() {
            if (this.objectForm.location_type === 'rack') {
                const qty = parseInt(this.objectForm.quantity) || 1;
                const testW = parseInt(this.objectForm.canvas_width);
                const testH = parseInt(this.objectForm.canvas_height);
                const minDim = Math.min(testW, testH);
                const maxDim = Math.max(testW, testH);
                if (minDim < 25 || maxDim < 100) {
                    alert('Perhatian: Ukuran minimal objek Rak adalah Sisi Panjang minimal 100px dan Sisi Tebal/Lebar minimal 25px!');
                    return;
                }

                const parentRoom = this.locations.find(l => l.location_type === 'room' && (l.room_sector === this.objectForm.room_sector || l.rack_code === this.objectForm.room_sector));

                let currentX = parseInt(this.objectForm.canvas_x);
                let currentY = parseInt(this.objectForm.canvas_y);
                const cleanBaseCode = (this.objectForm.rack_code || 'RAK').replace(/[\s-]*\d+$/i, '');

                let createdCount = 0;
                const addedLocations = [];

                for (let i = 0; i < qty; i++) {
                    let indexNum = (i + 1);
                    let rackCode = (qty > 1 || this.locations.some(l => l.rack_code === this.objectForm.rack_code))
                        ? `${cleanBaseCode}-${String(indexNum).padStart(2, '0')}`
                        : this.objectForm.rack_code;

                    let codeSearchIdx = indexNum;
                    while (this.locations.some(l => l.rack_code === rackCode) || addedLocations.some(l => l.rack_code === rackCode)) {
                        codeSearchIdx++;
                        rackCode = `${cleanBaseCode}-${String(codeSearchIdx).padStart(2, '0')}`;
                    }

                    let validX = currentX;
                    let validY = currentY;
                    let tries = 0;

                    const excludeIds = addedLocations.map(l => l.id);

                    while (this.hasRackCollision({ id: 0 }, validX, validY, testW, testH, excludeIds) && tries < 60) {
                        validX += (testW + 8);
                        if (parentRoom && (validX + testW) > (parentRoom.canvas_x + parentRoom.canvas_width - 8)) {
                            validX = parentRoom.canvas_x + 15;
                            validY += (testH + 8);
                        } else if (!parentRoom && (validX + testW) > 920) {
                            validX = 20;
                            validY += (testH + 8);
                        }
                        tries++;
                    }

                    const rackPayload = {
                        location_type: 'rack',
                        rack_code: rackCode,
                        room_sector: this.objectForm.room_sector || 'Umum',
                        box_capacity: this.objectForm.box_capacity || 100,
                        canvas_width: testW,
                        canvas_height: testH,
                        custom_color: this.objectForm.custom_color || '',
                        canvas_x: validX,
                        canvas_y: validY,
                        orientation: (testW >= testH) ? 'horizontal' : 'vertical',
                    };

                    try {
                        const res = await fetch('{{ route("api.warehouse.locations.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(rackPayload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            createdCount++;
                            addedLocations.push(data.location);
                            this.locations.push(data.location);

                            currentX = validX + (testW + 8);
                            if (parentRoom && (currentX + testW) > (parentRoom.canvas_x + parentRoom.canvas_width - 8)) {
                                currentX = parentRoom.canvas_x + 15;
                                currentY = validY + (testH + 8);
                            }
                        }
                    } catch (err) {
                        console.error('Failed to create batch rack:', err);
                    }
                }

                if (createdCount > 0) {
                    this.showToast(`✨ Berhasil membuat ${createdCount} objek Rak baru di canvas!`);
                    this.objectModalOpen = false;
                    await this.fetchData();
                    if (addedLocations.length > 0) {
                        this.selectedLocations = this.locations.filter(l => addedLocations.some(a => a.id === l.id));
                    }
                    this.renderCanvas();
                } else {
                    alert('Gagal membuat objek rak.');
                }
                return;
            }

            try {
                const res = await fetch('{{ route("api.warehouse.locations.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.objectForm)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.objectModalOpen = false;
                    await this.fetchData();
                    this.selectedLocation = this.locations.find(l => l.id === data.location.id);
                    this.renderCanvas();
                }
            } catch (err) {
                alert('Gagal menambahkan object baru.');
            }
        },

