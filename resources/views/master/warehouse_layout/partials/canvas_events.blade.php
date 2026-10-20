        // Canvas Mouse Events: Click, Drag, & Corner Handles Resize
        handleCanvasMouseDown(e) {
            this.contextMenuOpen = false;
            const rect = this.canvas.getBoundingClientRect();
            const mouseX = (e.clientX - rect.left) / this.scale;
            const mouseY = (e.clientY - rect.top) / this.scale;

            if (this.selectedLocation) {
                const loc = this.selectedLocation;
                const x = loc.canvas_x;
                const y = loc.canvas_y;
                const w = loc.canvas_width;
                const h = loc.canvas_height;

                // 0a. Lock Handle Hit Testing (for Gudang Room & Rak objects)
                if (loc) {
                    const lockCenterX = x + w - 15;
                    const lockCenterY = y - 24;
                    if (Math.hypot(mouseX - lockCenterX, mouseY - lockCenterY) <= 12) {
                        this.toggleLockLocation(loc);
                        return;
                    }
                }

                // 0b. Rotation Handle Hit Testing (Protruding 24px above top center)
                const rotCenterX = x + (w / 2);
                const rotCenterY = y - 24;
                if (Math.hypot(mouseX - rotCenterX, mouseY - rotCenterY) <= 12) {
                    this.rotateLocation(loc, 90);
                    return;
                }

                // 1. Corner Hit Testing
                if (Math.abs(mouseX - (x + w)) < 12 && Math.abs(mouseY - (y + h)) < 12) {
                    this.startDrag(loc, 'resize-se', mouseX, mouseY);
                    return;
                }
                if (Math.abs(mouseX - x) < 12 && Math.abs(mouseY - y) < 12) {
                    this.startDrag(loc, 'resize-nw', mouseX, mouseY);
                    return;
                }
                if (Math.abs(mouseX - (x + w)) < 12 && Math.abs(mouseY - y) < 12) {
                    this.startDrag(loc, 'resize-ne', mouseX, mouseY);
                    return;
                }
                if (Math.abs(mouseX - x) < 12 && Math.abs(mouseY - (y + h)) < 12) {
                    this.startDrag(loc, 'resize-sw', mouseX, mouseY);
                    return;
                }
            }

            // 2. Check if user clicked inside body of any location object (Rack prioritized over Room)
            const clicked = this.getObjectAtPosition(mouseX, mouseY);

            if (clicked) {
                if (e.shiftKey) {
                    // Shift + Click: Multi-select toggle
                    const existsIdx = this.selectedLocations.findIndex(l => l.id === clicked.id);
                    if (existsIdx >= 0) {
                        this.selectedLocations.splice(existsIdx, 1);
                    } else {
                        this.selectedLocations.push(clicked);
                    }
                } else {
                    const isAlreadySelected = this.selectedLocations.some(l => l.id === clicked.id);
                    if (!isAlreadySelected || this.selectedLocations.length <= 1) {
                        this.selectedLocations = [clicked];
                    }
                }
                if (this.selectedLocations.some(l => l.id === clicked.id)) {
                    this.startDrag(clicked, 'move', mouseX, mouseY);
                }
            } else {
                if (!e.shiftKey) {
                    this.selectedLocations = [];
                }
            }
            this.renderCanvas();
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        startDrag(target, mode, mouseX, mouseY) {
            if (target.is_locked !== false) {
                const typeLabel = target.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${target.rack_code}' sedang TERKUNCI. Klik logo gembok (🔒) pada canvas untuk membuka kuncinya.`);
                return;
            }

            this.isDragging = true;
            this.dragMode = mode;
            this.draggedTarget = target;
            this.dragStartX = mouseX;
            this.dragStartY = mouseY;
            this.initialX = target.canvas_x;
            this.initialY = target.canvas_y;
            this.initialW = target.canvas_width;
            this.initialH = target.canvas_height;

            if (this.canvas) {
                if (mode === 'resize-se' || mode === 'resize-nw') {
                    this.canvas.style.cursor = 'nwse-resize';
                } else if (mode === 'resize-ne' || mode === 'resize-sw') {
                    this.canvas.style.cursor = 'nesw-resize';
                } else if (mode === 'move') {
                    this.canvas.style.cursor = 'move';
                }
            }

            // Track initial positions for group move
            this.draggedGroupPositions = this.selectedLocations.map(loc => ({
                loc: loc,
                initX: loc.canvas_x,
                initY: loc.canvas_y
            }));

            // If moving a Gudang (room) object, track all child racks inside it
            this.draggedChildRacks = [];
            if (target.location_type === 'room' && mode === 'move') {
                const roomSectorName = target.room_sector || target.rack_code;
                const childRacks = this.locations.filter(loc => {
                    if (loc.location_type === 'room' || loc.id === target.id) return false;
                    const isSectorMatch = loc.room_sector === roomSectorName || loc.room_sector === target.rack_code;
                    const isInside = (loc.canvas_x >= target.canvas_x) && 
                                     ((loc.canvas_x + loc.canvas_width) <= (target.canvas_x + target.canvas_width)) &&
                                     (loc.canvas_y >= target.canvas_y) && 
                                     ((loc.canvas_y + loc.canvas_height) <= (target.canvas_y + target.canvas_height));
                    return isSectorMatch || isInside;
                });

                this.draggedChildRacks = childRacks.map(r => ({
                    rak: r,
                    initX: r.canvas_x,
                    initY: r.canvas_y
                }));
            }
        },

        handleCanvasMouseMove(e) {
            const rect = this.canvas.getBoundingClientRect();
            const mouseX = (e.clientX - rect.left) / this.scale;
            const mouseY = (e.clientY - rect.top) / this.scale;

            if (this.isDragging && this.draggedTarget) {
                const deltaX = Math.round(mouseX - this.dragStartX);
                const deltaY = Math.round(mouseY - this.dragStartY);
                const target = this.draggedTarget;

                // Find parent room if this target is a rack inside a Gudang room
                let parentRoom = null;
                if (target.location_type !== 'room') {
                    parentRoom = this.locations.find(loc => {
                        if (loc.location_type !== 'room') return false;
                        const isSectorMatch = loc.room_sector === target.room_sector || loc.rack_code === target.room_sector;
                        const isInsideInit = (this.initialX >= loc.canvas_x) && 
                                             ((this.initialX + this.initialW) <= (loc.canvas_x + loc.canvas_width)) &&
                                             (this.initialY >= loc.canvas_y) && 
                                             ((this.initialY + this.initialH) <= (loc.canvas_y + loc.canvas_height));
                        return isSectorMatch || isInsideInit;
                    });
                }

                // Parent room boundary limits
                const minX = parentRoom ? parentRoom.canvas_x + 4 : 0;
                const minY = parentRoom ? parentRoom.canvas_y + 26 : 0;
                const maxCanvasW = this.canvas ? (this.canvas.width - target.canvas_width - 4) : 950;
                const maxCanvasH = this.canvas ? (this.canvas.height - target.canvas_height - 4) : 750;
                const maxX = parentRoom ? (parentRoom.canvas_x + parentRoom.canvas_width - target.canvas_width - 4) : maxCanvasW;
                const maxY = parentRoom ? (parentRoom.canvas_y + parentRoom.canvas_height - target.canvas_height - 4) : maxCanvasH;

                const isHorizontalRack = (target.location_type !== 'room') && (target.canvas_width > target.canvas_height || target.orientation === 'horizontal');
                const minAllowedW = (target.location_type === 'room') ? 50 : (isHorizontalRack ? 100 : 25);
                const minAllowedH = (target.location_type === 'room') ? 50 : (isHorizontalRack ? 25 : 100);

                if (this.dragMode === 'move') {
                    if (this.draggedGroupPositions && this.draggedGroupPositions.length > 1) {
                        const groupIds = this.selectedLocations.map(l => l.id);
                        this.draggedGroupPositions.forEach(item => {
                            let newX = Math.max(0, item.initX + deltaX);
                            let newY = Math.max(0, item.initY + deltaY);

                            if (item.loc.location_type !== 'room') {
                                if (!this.hasRackCollision(item.loc, newX, item.loc.canvas_y, item.loc.canvas_width, item.loc.canvas_height, groupIds)) {
                                    item.loc.canvas_x = newX;
                                }
                                if (!this.hasRackCollision(item.loc, item.loc.canvas_x, newY, item.loc.canvas_width, item.loc.canvas_height, groupIds)) {
                                    item.loc.canvas_y = newY;
                                }
                            } else {
                                item.loc.canvas_x = newX;
                                item.loc.canvas_y = newY;
                            }
                        });

                        // Apply snap & alignment guidance for group primary target
                        const origX = target.canvas_x;
                        const origY = target.canvas_y;
                        this.calculateSnapAndGuides(target);
                        const groupSnapShiftX = target.canvas_x - origX;
                        const groupSnapShiftY = target.canvas_y - origY;

                        if (groupSnapShiftX !== 0 || groupSnapShiftY !== 0) {
                            this.draggedGroupPositions.forEach(item => {
                                if (item.loc.id !== target.id) {
                                    item.loc.canvas_x += groupSnapShiftX;
                                    item.loc.canvas_y += groupSnapShiftY;
                                }
                            });
                        }
                    } else {
                        let newX = this.initialX + deltaX;
                        let newY = this.initialY + deltaY;

                        if (parentRoom) {
                            newX = Math.max(minX, Math.min(maxX, newX));
                            newY = Math.max(minY, Math.min(maxY, newY));
                        } else {
                            newX = Math.max(0, newX);
                            newY = Math.max(0, newY);
                        }

                        if (target.location_type !== 'room') {
                            // Test horizontal movement collision
                            if (!this.hasRackCollision(target, newX, target.canvas_y, target.canvas_width, target.canvas_height)) {
                                target.canvas_x = newX;
                            }
                            // Test vertical movement collision
                            if (!this.hasRackCollision(target, target.canvas_x, newY, target.canvas_width, target.canvas_height)) {
                                target.canvas_y = newY;
                            }
                        } else {
                            target.canvas_x = newX;
                            target.canvas_y = newY;

                            // If moving a Gudang room, move all child racks inside it synchronously
                            if (this.draggedChildRacks && this.draggedChildRacks.length > 0) {
                                this.draggedChildRacks.forEach(c => {
                                    c.rak.canvas_x = Math.max(0, c.initX + deltaX);
                                    c.rak.canvas_y = Math.max(0, c.initY + deltaY);
                                });
                            }
                        }

                        // Apply smart snap & alignment guidance for target
                        this.calculateSnapAndGuides(target);
                    }
                } else if (['resize-se', 'resize-nw', 'resize-ne', 'resize-sw'].includes(this.dragMode)) {
                    let propW = this.initialW;
                    let propH = this.initialH;
                    let propX = this.initialX;
                    let propY = this.initialY;

                    const effectiveMinW = (target.location_type === 'room') ? 50 : 20;
                    const effectiveMinH = (target.location_type === 'room') ? 50 : 20;

                    if (this.dragMode === 'resize-se') {
                        propW = Math.max(effectiveMinW, this.initialW + deltaX);
                        propH = Math.max(effectiveMinH, this.initialH + deltaY);
                        propX = this.initialX;
                        propY = this.initialY;
                    } else if (this.dragMode === 'resize-nw') {
                        propW = Math.max(effectiveMinW, this.initialW - deltaX);
                        propH = Math.max(effectiveMinH, this.initialH - deltaY);
                        propX = this.initialX + (this.initialW - propW);
                        propY = this.initialY + (this.initialH - propH);
                    } else if (this.dragMode === 'resize-ne') {
                        propW = Math.max(effectiveMinW, this.initialW + deltaX);
                        propH = Math.max(effectiveMinH, this.initialH - deltaY);
                        propX = this.initialX;
                        propY = this.initialY + (this.initialH - propH);
                    } else if (this.dragMode === 'resize-sw') {
                        propW = Math.max(effectiveMinW, this.initialW - deltaX);
                        propH = Math.max(effectiveMinH, this.initialH + deltaY);
                        propX = this.initialX + (this.initialW - propW);
                        propY = this.initialY;
                    }

                    // Calculate Smart Magnetic Snapping & Guidance Lines
                    const snapRes = this.calculateResizeSnapAndGuides(target, this.dragMode, propX, propY, propW, propH, parentRoom);

                    let finalX = snapRes.x;
                    let finalY = snapRes.y;
                    let finalW = snapRes.w;
                    let finalH = snapRes.h;

                    // Parent Room Boundaries
                    if (parentRoom) {
                        const roomMinX = parentRoom.canvas_x + 4;
                        const roomMinY = parentRoom.canvas_y + 26;
                        const roomMaxRight = parentRoom.canvas_x + parentRoom.canvas_width - 4;
                        const roomMaxBottom = parentRoom.canvas_y + parentRoom.canvas_height - 4;

                        if (finalX < roomMinX) {
                            finalW -= (roomMinX - finalX);
                            finalX = roomMinX;
                        }
                        if (finalY < roomMinY) {
                            finalH -= (roomMinY - finalY);
                            finalY = roomMinY;
                        }
                        if (finalX + finalW > roomMaxRight) {
                            finalW = Math.max(effectiveMinW, roomMaxRight - finalX);
                        }
                        if (finalY + finalH > roomMaxBottom) {
                            finalH = Math.max(effectiveMinH, roomMaxBottom - finalY);
                        }
                    } else {
                        // Canvas Boundaries
                        if (finalX < 0) {
                            finalW += finalX;
                            finalX = 0;
                        }
                        if (finalY < 0) {
                            finalH += finalY;
                            finalY = 0;
                        }
                        const curCanvasW = this.canvas ? this.canvas.width : (this.canvasWidth || 950);
                        const curCanvasH = this.canvas ? this.canvas.height : (this.canvasHeight || 750);
                        if (finalX + finalW > curCanvasW) {
                            finalW = Math.max(effectiveMinW, curCanvasW - finalX);
                        }
                        if (finalY + finalH > curCanvasH) {
                            finalH = Math.max(effectiveMinH, curCanvasH - finalY);
                        }
                    }

                    finalW = Math.max(effectiveMinW, finalW);
                    finalH = Math.max(effectiveMinH, finalH);

                    // Collision Detection (for racks)
                    this.activeGuideLines = [];

                    if (target.location_type !== 'room') {
                        // Horizontal (X & W)
                        if (!this.hasRackCollision(target, finalX, target.canvas_y, finalW, target.canvas_height)) {
                            target.canvas_x = finalX;
                            target.canvas_width = finalW;
                            if (snapRes.guideX) this.activeGuideLines.push(snapRes.guideX);
                        }
                        // Vertical (Y & H)
                        if (!this.hasRackCollision(target, target.canvas_x, finalY, target.canvas_width, finalH)) {
                            target.canvas_y = finalY;
                            target.canvas_height = finalH;
                            if (snapRes.guideY) this.activeGuideLines.push(snapRes.guideY);
                        }
                    } else {
                        // Gudang room
                        target.canvas_x = finalX;
                        target.canvas_y = finalY;
                        target.canvas_width = finalW;
                        target.canvas_height = finalH;
                        if (snapRes.guideX) this.activeGuideLines.push(snapRes.guideX);
                        if (snapRes.guideY) this.activeGuideLines.push(snapRes.guideY);
                    }

                    this.renderCanvas();
                    return;
                }
            }

            // Hover Cursor Management (Handles, Objects & Default)
            const hovered = this.getObjectAtPosition(mouseX, mouseY);

            if (this.canvas) {
                if (this.selectedLocation) {
                    const loc = this.selectedLocation;
                    const x = loc.canvas_x;
                    const y = loc.canvas_y;
                    const w = loc.canvas_width;
                    const h = loc.canvas_height;

                    if (Math.abs(mouseX - (x + w)) < 12 && Math.abs(mouseY - (y + h)) < 12) {
                        this.canvas.style.cursor = 'nwse-resize';
                    } else if (Math.abs(mouseX - x) < 12 && Math.abs(mouseY - y) < 12) {
                        this.canvas.style.cursor = 'nwse-resize';
                    } else if (Math.abs(mouseX - (x + w)) < 12 && Math.abs(mouseY - y) < 12) {
                        this.canvas.style.cursor = 'nesw-resize';
                    } else if (Math.abs(mouseX - x) < 12 && Math.abs(mouseY - (y + h)) < 12) {
                        this.canvas.style.cursor = 'nesw-resize';
                    } else if (Math.hypot(mouseX - (x + w / 2), mouseY - (y - 24)) <= 12) {
                        this.canvas.style.cursor = 'grab';
                    } else if (Math.hypot(mouseX - (x + w - 15), mouseY - (y - 24)) <= 12) {
                        this.canvas.style.cursor = 'pointer';
                    } else if (hovered) {
                        this.canvas.style.cursor = 'move';
                    } else {
                        this.canvas.style.cursor = 'crosshair';
                    }
                } else if (hovered) {
                    this.canvas.style.cursor = 'pointer';
                } else {
                    this.canvas.style.cursor = 'crosshair';
                }
            }

            if (hovered !== this.hoveredLocation) {
                this.hoveredLocation = hovered;
                this.renderCanvas();
            }
        },

        async handleCanvasMouseUp(e) {
            if (this.isDragging && this.draggedTarget) {
                const childRacksToSave = this.draggedChildRacks || [];

                this.isDragging = false;
                this.dragMode = null;
                this.draggedTarget = null;
                this.draggedChildRacks = [];
                this.activeGuideLines = [];
                if (this.canvas) this.canvas.style.cursor = 'crosshair';
                this.renderCanvas();

                // Save positions for all selected locations
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
                            })
                        });
                    } catch (err) {
                        console.error('Failed to auto-save canvas coordinates:', err);
                    }
                }

                // Save child racks if room was dragged
                if (childRacksToSave.length > 0) {
                    for (const c of childRacksToSave) {
                        try {
                            await fetch(`/api/warehouse/locations/${c.rak.id}/update`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    rack_code: c.rak.rack_code,
                                    location_type: c.rak.location_type,
                                    room_sector: c.rak.room_sector,
                                    canvas_x: c.rak.canvas_x,
                                    canvas_y: c.rak.canvas_y,
                                    canvas_width: c.rak.canvas_width,
                                    canvas_height: c.rak.canvas_height,
                                })
                            });
                        } catch (err) {
                            console.error('Failed to save child rack position:', err);
                        }
                    }
                }
            }
        },

        async pasteCopiedLocations() {
            if (!this.copiedLocations || this.copiedLocations.length === 0) return;

            const newPastedLocations = [];
            for (const sourceObj of this.copiedLocations) {
                const cleanBase = (sourceObj.rack_code || 'OBJECT').replace(/ \(COPY\d*\)$/i, '').replace(/ - COPY\d*$/i, '');
                let copyIndex = 1;
                let newCode = `${cleanBase} (COPY)`;
                while (this.locations.some(l => l.rack_code === newCode) || newPastedLocations.some(l => l.rack_code === newCode)) {
                    copyIndex++;
                    newCode = `${cleanBase} (COPY ${copyIndex})`;
                }

                let newX = sourceObj.canvas_x + 25;
                let newY = sourceObj.canvas_y + 25;

                const duplicateData = {
                    location_type: sourceObj.location_type || 'rack',
                    rack_code: newCode,
                    room_sector: sourceObj.room_sector || 'Umum',
                    shelf_code: sourceObj.shelf_code || 'BARIS-01',
                    box_capacity: sourceObj.box_capacity || 100,
                    custom_color: sourceObj.custom_color || '',
                    canvas_x: newX,
                    canvas_y: newY,
                    canvas_width: sourceObj.canvas_width || 40,
                    canvas_height: sourceObj.canvas_height || 120,
                    orientation: sourceObj.orientation || 'horizontal',
                    rotation_angle: sourceObj.rotation_angle || 0,
                    assigned_department_id: sourceObj.assigned_department_id || null,
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
                        newPastedLocations.push(data.location);
                    }
                } catch (err) {
                    console.error('Error pasting object:', err);
                }
            }

            if (newPastedLocations.length > 0) {
                this.showToast(`📋 ${newPastedLocations.length} object berhasil diduplikat / ditempel di canvas!`);
                await this.fetchData();
                this.selectedLocations = this.locations.filter(l => newPastedLocations.some(p => p.id === l.id));
                this.copiedLocations = JSON.parse(JSON.stringify(this.selectedLocations));
            }
        },
