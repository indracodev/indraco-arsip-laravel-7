        handleCanvasDoubleClick(e) {
            const rect = this.canvas.getBoundingClientRect();
            const mouseX = (e.clientX - rect.left) / this.scale;
            const mouseY = (e.clientY - rect.top) / this.scale;

            const clicked = this.getObjectAtPosition(mouseX, mouseY);
            if (clicked && clicked.location_type !== 'room') {
                this.openRackGridModal(clicked);
            }
        },

        // --- Live Search & Blinking Highlight Logic ---
        handleSearchInput() {
            const q = (this.searchQuery || '').trim().toLowerCase();
            if (!q) {
                this.searchResults = { rooms: [], racks: [], boxes: [] };
                this.searchDropdownOpen = false;
                return;
            }

            // 1. Search Gudang / Rooms
            const rooms = this.locations.filter(l => l.location_type === 'room' && (
                (l.rack_code && l.rack_code.toLowerCase().includes(q)) ||
                (l.room_sector && l.room_sector.toLowerCase().includes(q))
            )).slice(0, 6);

            // 2. Search Racks
            const racks = this.locations.filter(l => l.location_type !== 'room' && (
                (l.rack_code && l.rack_code.toLowerCase().includes(q)) ||
                (l.room_sector && l.room_sector.toLowerCase().includes(q)) ||
                (l.shelf_code && l.shelf_code.toLowerCase().includes(q)) ||
                (this.getRackIdentifier(l).toLowerCase() === q) ||
                (this.getRackIdentifier(l).toLowerCase().includes(q))
            )).slice(0, 10);

            // 3. Search Boxes / Slots (both assigned archives & synthetic slots)
            const matchedBoxes = [];
            const allRacks = this.locations.filter(l => l.location_type !== 'room');

            for (const rack of allRacks) {
                if (matchedBoxes.length >= 15) break;
                const slots = rack.slots || [];
                for (const slot of slots) {
                    if (matchedBoxes.length >= 15) break;
                    const slotCode = (slot.slot_code || '').toLowerCase();
                    const boxNum = (slot.archive?.box_number || '').toLowerCase();
                    const title = (slot.archive?.title || '').toLowerCase();
                    const dept = (slot.archive?.department || '').toLowerCase();
                    const period = (slot.archive?.periode_doc || '').toLowerCase();

                    if (slotCode.includes(q) || (boxNum && boxNum.includes(q)) || (title && title.includes(q)) || (dept && dept.includes(q)) || (period && period.includes(q))) {
                        matchedBoxes.push({
                            ...slot,
                            rack: rack
                        });
                    }
                }
            }

            // Also check for slot code pattern e.g. "B11", "AO1", "BI20"
            if (matchedBoxes.length < 15) {
                for (const rack of allRacks) {
                    if (matchedBoxes.length >= 15) break;
                    const rackId = this.getRackIdentifier(rack).toLowerCase();
                    if (q.startsWith(rackId) || rack.rack_code.toLowerCase().includes(q)) {
                        const numPart = q.replace(/^[a-z\-]+/i, '');
                        if (numPart && !isNaN(parseInt(numPart))) {
                            const boxNum = parseInt(numPart);
                            if (boxNum >= 1 && boxNum <= 100) {
                                const sapLevel = Math.ceil(boxNum / 20);
                                const remainder = (boxNum - 1) % 20 + 1;
                                const layer = remainder <= 10 ? 'bottom' : 'top';
                                const code = `${this.getRackIdentifier(rack)}${boxNum}`;
                                if (!matchedBoxes.some(b => b.rack.id === rack.id && b.slot_code.toLowerCase() === code.toLowerCase())) {
                                    matchedBoxes.push({
                                        slot_code: code,
                                        slot_number: boxNum,
                                        box_number_display: boxNum,
                                        sap_level: sapLevel,
                                        layer: layer,
                                        layer_label: layer === 'top' ? 'Baris Atas' : 'Baris Bawah',
                                        status: 'empty',
                                        archive: null,
                                        rack: rack
                                    });
                                }
                            }
                        }
                    }
                }
            }

            this.searchResults = {
                rooms: rooms,
                racks: racks,
                boxes: matchedBoxes
            };
            this.searchDropdownOpen = (rooms.length > 0 || racks.length > 0 || matchedBoxes.length > 0);
        },

        selectFirstSearchResult() {
            if (this.searchResults.boxes.length > 0) {
                this.selectSearchResult('box', this.searchResults.boxes[0]);
            } else if (this.searchResults.racks.length > 0) {
                this.selectSearchResult('rack', this.searchResults.racks[0]);
            } else if (this.searchResults.rooms.length > 0) {
                this.selectSearchResult('room', this.searchResults.rooms[0]);
            }
        },

        selectSearchResult(type, item) {
            this.searchDropdownOpen = false;
            if (type === 'room') {
                const room = this.locations.find(l => l.id === item.id);
                if (room) {
                    this.selectedLocations = [room];
                    this.panToLocation(room);
                    this.triggerLocationBlink(room);
                    this.showToast(`🏢 Menemukan Gudang '${room.rack_code || room.room_sector}' (Layout berkedip)`);
                }
            } else if (type === 'rack') {
                const rack = this.locations.find(l => l.id === item.id);
                if (rack) {
                    this.selectedLocations = [rack];
                    this.panToLocation(rack);
                    this.triggerLocationBlink(rack);
                    this.showToast(`🗄️ Menemukan Rak '${rack.rack_code}' (Rak berkedip pada canvas)`);
                }
            } else if (type === 'box') {
                const rack = this.locations.find(l => l.id === item.rack.id);
                if (rack) {
                    this.selectedLocations = [rack];
                    this.panToLocation(rack);
                    this.triggerLocationBlink(rack);

                    // Open 100-Box Denah Modal
                    this.openRackGridModal(rack);

                    // Find matching slot in modal
                    const sapLevel = item.sap_level || Math.ceil((item.slot_number || 1) / 20);
                    const layer = item.layer || (((item.slot_number - 1) % 20 + 1) <= 10 ? 'bottom' : 'top');
                    const sapSlots = this.getSapSlots(sapLevel, layer);
                    const targetSlot = sapSlots.find(s => s.slot_code === item.slot_code || s.slot_number == item.slot_number) || item;

                    this.selectSlotForDetail(targetSlot);
                    this.highlightedSlotCode = targetSlot.slot_code;
                    this.slotBlinkActive = true;

                    if (this.slotBlinkTimer) clearTimeout(this.slotBlinkTimer);
                    this.slotBlinkTimer = setTimeout(() => {
                        this.slotBlinkActive = false;
                    }, 8000);

                    const title = item.archive?.title ? ` - ${item.archive.title}` : '';
                    this.showToast(`📦 Menemukan Box '${targetSlot.slot_code}'${title} di Rak '${rack.rack_code}' (Box berkedip)`);
                }
            }
        },

        triggerLocationBlink(loc) {
            if (!loc) return;
            this.blinkLocationId = loc.id;
            this.blinkStartTime = Date.now();
            this.startCanvasBlinkLoop();
        },

        clearBlink() {
            this.blinkLocationId = null;
            if (this.blinkAnimId) {
                cancelAnimationFrame(this.blinkAnimId);
                this.blinkAnimId = null;
            }
            this.slotBlinkActive = false;
            this.highlightedSlotCode = null;
            this.renderCanvas();
        },

        startCanvasBlinkLoop() {
            if (this.blinkAnimId) cancelAnimationFrame(this.blinkAnimId);
            const animate = () => {
                if (!this.blinkLocationId) return;
                const elapsed = Date.now() - this.blinkStartTime;
                if (elapsed > 7000) { // Blink for 7 seconds
                    this.blinkLocationId = null;
                    this.renderCanvas();
                    return;
                }
                this.renderCanvas();
                this.blinkAnimId = requestAnimationFrame(animate);
            };
            this.blinkAnimId = requestAnimationFrame(animate);
        },

        panToLocation(loc) {
            if (!loc) return;
            const container = document.getElementById('canvasScrollContainer');
            if (!container) return;
            const targetCenterX = (loc.canvas_x + (loc.canvas_width / 2)) * this.scale;
            const targetCenterY = (loc.canvas_y + (loc.canvas_height / 2)) * this.scale;

            const scrollX = Math.max(0, targetCenterX - (container.clientWidth / 2));
            const scrollY = Math.max(0, targetCenterY - (container.clientHeight / 2));

            container.scrollTo({
                left: scrollX,
                top: scrollY,
                behavior: 'smooth'
            });
        },

        highlightRackByIdOrCode(rackId, rackCode) {
            let rack = null;
            if (rackId) {
                rack = this.locations.find(l => String(l.id) === String(rackId));
            }
            if (!rack && rackCode) {
                const cleanCode = String(rackCode).trim().toLowerCase();
                rack = this.locations.find(l => 
                    l.location_type !== 'room' && (
                        (l.rack_code && l.rack_code.trim().toLowerCase() === cleanCode) ||
                        (this.getRackIdentifier(l).toLowerCase() === cleanCode)
                    )
                );
                if (!rack) {
                    rack = this.locations.find(l => 
                        (l.rack_code && l.rack_code.trim().toLowerCase().includes(cleanCode)) ||
                        (cleanCode.includes(l.rack_code ? l.rack_code.trim().toLowerCase() : '')) ||
                        (l.room_sector && l.room_sector.trim().toLowerCase().includes(cleanCode))
                    );
                }
            }
            if (rack) {
                this.selectedLocations = [rack];
                this.selectedLocation = rack;
                this.panToLocation(rack);
                this.triggerLocationBlink(rack);
                this.showToast(`🗄️ Menemukan Rak '${rack.rack_code}' (Rak berkedip pada layout)`);
            }
        },

        highlightArchiveById(archiveId) {
            if (!archiveId) return;
            const allRacks = this.locations.filter(l => l.location_type !== 'room');
            for (const rack of allRacks) {
                const slots = rack.slots || [];
                const matchedSlot = slots.find(s => s.archive && String(s.archive.id) === String(archiveId));
                if (matchedSlot) {
                    this.selectSearchResult('box', {
                        ...matchedSlot,
                        rack: rack
                    });
                    return;
                }
            }
        }
    }
