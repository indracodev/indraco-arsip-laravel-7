        init() {
            this.canvas = document.getElementById('warehouseCanvas');
            this.ctx = this.canvas.getContext('2d');
            this.applyCanvasSize(this.canvasWidth, this.canvasHeight);
            this.fetchData();

            // Window Keyboard Arrow Listener for Selected Object Repositioning
            window.addEventListener('keydown', (e) => this.handleKeyDown(e));

            // Window Message Listener for Communication from Parent Window (Header Search, Dashboard, etc.)
            window.addEventListener('message', (e) => {
                if (e.data && e.data.action === 'highlight_rack') {
                    this.highlightRackByIdOrCode(e.data.rack_id, e.data.rack_code);
                } else if (e.data && e.data.action === 'highlight_archive') {
                    this.highlightArchiveById(e.data.archive_id);
                }
            });

            // Window CustomEvent Listener for intra-window events
            window.addEventListener('highlight-rack', (e) => {
                if (e.detail) {
                    this.highlightRackByIdOrCode(e.detail.rack_id, e.detail.rack_code);
                }
            });

            // Sync API Fullscreen state changes (e.g. User presses Esc natively)
            document.addEventListener('fullscreenchange', () => {
                const isApiFullscreen = !!document.fullscreenElement;
                if (!isApiFullscreen && this.isFullscreen) {
                    this.isFullscreen = false;
                    this.showToast('📺 Keluar dari Mode Layar Penuh');
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                        this.renderCanvas();
                    });
                }
            });
        },

        hasRackCollision(testRack, testX, testY, testW, testH, excludeIds = []) {
            const margin = 2; // 2px clearance margin between racks
            return this.locations.some(other => {
                if (other.location_type === 'room') return false;
                if (other.id === testRack.id || excludeIds.includes(other.id)) return false;

                return (testX < (other.canvas_x + other.canvas_width - margin)) &&
                       ((testX + testW) > (other.canvas_x + margin)) &&
                       (testY < (other.canvas_y + other.canvas_height - margin)) &&
                       ((testY + testH) > (other.canvas_y + margin));
            });
        },

        rotateLocation(loc = null, angleStep = 90) {
            const targets = loc ? [loc] : [...this.selectedLocations];
            if (targets.length === 0) return;

            const lockedTarget = targets.find(t => t.is_locked !== false);
            if (lockedTarget) {
                const typeLabel = lockedTarget.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${lockedTarget.rack_code}' sedang TERKUNCI. Buka kunci gembok terlebih dahulu.`);
                return;
            }

            if (targets.length === 1) {
                this.rotateSingleLocation(targets[0], angleStep);
            } else {
                this.rotateGroupLocations(targets, angleStep);
            }

            this.renderCanvas();
        },

        rotateGroupLocations(targets, angleStep = 90) {
            if (!targets || targets.length === 0) return;

            // 1. Calculate Group Bounding Box
            let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
            targets.forEach(t => {
                minX = Math.min(minX, t.canvas_x);
                maxX = Math.max(maxX, t.canvas_x + t.canvas_width);
                minY = Math.min(minY, t.canvas_y);
                maxY = Math.max(maxY, t.canvas_y + t.canvas_height);
            });

            const groupCX = (minX + maxX) / 2;
            const groupCY = (minY + maxY) / 2;

            // 2. Compute rotated center for each object around group center
            const proposed = targets.map(t => {
                const objCX = t.canvas_x + (t.canvas_width / 2);
                const objCY = t.canvas_y + (t.canvas_height / 2);

                const relX = objCX - groupCX;
                const relY = objCY - groupCY;

                // +90 deg rotation matrix: (relX, relY) -> (-relY, relX)
                const newRelX = -relY;
                const newRelY = relX;

                const newW = t.canvas_height;
                const newH = t.canvas_width;

                const newObjCX = groupCX + newRelX;
                const newObjCY = groupCY + newRelY;

                const newX = Math.round(newObjCX - (newW / 2));
                const newY = Math.round(newObjCY - (newH / 2));

                const currentAngle = t.rotation_angle || 0;
                const newAngle = (currentAngle + angleStep) % 360;

                return {
                    target: t,
                    newX: newX,
                    newY: newY,
                    newW: newW,
                    newH: newH,
                    newAngle: newAngle,
                    orientation: (newW >= newH) ? 'horizontal' : 'vertical'
                };
            });

            // 3. Find parent Gudang / Room if targets belong to a Gudang sector
            const firstRoomSector = targets[0].room_sector;
            const parentRoom = this.locations.find(l => 
                l.location_type === 'room' && 
                (l.room_sector === firstRoomSector || l.rack_code === firstRoomSector)
            );

            // Calculate new group bounds after rotation
            let pMinX = Infinity, pMaxX = -Infinity, pMinY = Infinity, pMaxY = -Infinity;
            proposed.forEach(p => {
                pMinX = Math.min(pMinX, p.newX);
                pMaxX = Math.max(pMaxX, p.newX + p.newW);
                pMinY = Math.min(pMinY, p.newY);
                pMaxY = Math.max(pMaxY, p.newY + p.newH);
            });

            let offsetX = 0;
            let offsetY = 0;

            if (parentRoom) {
                const roomMinX = parentRoom.canvas_x + 8;
                const roomMaxX = parentRoom.canvas_x + parentRoom.canvas_width - 8;
                const roomMinY = parentRoom.canvas_y + 30;
                const roomMaxY = parentRoom.canvas_y + parentRoom.canvas_height - 8;

                if (pMinX < roomMinX) offsetX = roomMinX - pMinX;
                if (pMaxX + offsetX > roomMaxX) offsetX = roomMaxX - pMaxX;

                if (pMinY < roomMinY) offsetY = roomMinY - pMinY;
                if (pMaxY + offsetY > roomMaxY) offsetY = roomMaxY - pMaxY;
            } else {
                if (pMinX < 10) offsetX = 10 - pMinX;
                if (pMinY < 10) offsetY = 10 - pMinY;
            }

            // 4. Apply updated coordinates and persist to backend
            proposed.forEach(p => {
                const finalX = Math.max(0, p.newX + offsetX);
                const finalY = Math.max(0, p.newY + offsetY);

                p.target.canvas_x = finalX;
                p.target.canvas_y = finalY;
                p.target.canvas_width = p.newW;
                p.target.canvas_height = p.newH;
                p.target.rotation_angle = p.newAngle;
                p.target.orientation = p.orientation;

                this.saveKeyboardNudge(p.target, []);
            });

            this.showToast(`🔄 Kelompok (${targets.length} object) berhasil diputar 90°.`);
        },

        rotateSingleLocation(target, angleStep = 90) {
            if (!target) return;

            if (target.is_locked !== false) {
                const typeLabel = target.location_type === 'room' ? 'Gudang' : 'Rak';
                this.showToast(`🔒 ${typeLabel} '${target.rack_code}' sedang TERKUNCI. Buka kunci gembok terlebih dahulu.`);
                return;
            }

            const currentAngle = target.rotation_angle || 0;
            const newAngle = (currentAngle + angleStep) % 360;

            const cx = target.canvas_x + (target.canvas_width / 2);
            const cy = target.canvas_y + (target.canvas_height / 2);

            const oldW = target.canvas_width;
            const oldH = target.canvas_height;
            const newW = oldH;
            const newH = oldW;

            let newX = Math.round(cx - (newW / 2));
            let newY = Math.round(cy - (newH / 2));

            if (target.location_type !== 'room') {
                const groupIds = this.selectedLocations.map(l => l.id);
                if (this.hasRackCollision(target, newX, newY, newW, newH, groupIds)) {
                    let resolved = false;
                    const shifts = [
                        [0, 0], [0, 10], [0, -10], [10, 0], [-10, 0], 
                        [0, 20], [0, -20], [20, 0], [-20, 0],
                        [0, 30], [0, -30], [30, 0], [-30, 0]
                    ];
                    for (const [sx, sy] of shifts) {
                        const tx = newX + sx;
                        const ty = newY + sy;
                        if (!this.hasRackCollision(target, tx, ty, newW, newH, groupIds)) {
                            newX = tx;
                            newY = ty;
                            resolved = true;
                            break;
                        }
                    }
                    if (!resolved) {
                        this.showToast(`⚠️ Rotasi ${target.rack_code} dibatalkan karena bertabrakan dengan rak lain.`);
                        return;
                    }
                }
            }

            target.rotation_angle = newAngle;
            target.canvas_width = newW;
            target.canvas_height = newH;
            target.canvas_x = Math.max(0, newX);
            target.canvas_y = Math.max(0, newY);
            target.orientation = (newW >= newH) ? 'horizontal' : 'vertical';

            this.saveKeyboardNudge(target, []);
        },

        async toggleLockLocation(loc) {
            if (!loc) return;
            const nextState = (loc.is_locked !== false) ? false : true;
            loc.is_locked = nextState;

            const typeLabel = loc.location_type === 'room' ? 'Gudang' : 'Rak';
            const lockLabel = nextState ? 'TERKUNCI 🔒' : 'DIBUKA 🔓';
            this.showToast(`Status ${typeLabel} '${loc.rack_code}' diset ke ${lockLabel}`);
            this.renderCanvas();

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
                        is_locked: loc.is_locked,
                    })
                });
            } catch (err) {
                console.error('Failed to toggle lock state:', err);
            }
        },

        handleKeyDown(e) {
            // Ignore keydown if user is currently typing inside an input/textarea/select field
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;

            // Esc key -> Toggle / Exit Fullscreen mode
            if (e.key === 'Escape' && this.isFullscreen) {
                e.preventDefault();
                this.toggleFullscreen();
                return;
            }

            // Ctrl + C / Cmd + C -> Copy selected objects
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c') {
                if (this.selectedLocations.length > 0) {
                    e.preventDefault();
                    this.copiedLocations = JSON.parse(JSON.stringify(this.selectedLocations));
                    this.showToast(`📋 ${this.copiedLocations.length} object disalin. Tekan Ctrl+V untuk menempel (paste).`);
                }
                return;
            }

            // Ctrl + V / Cmd + V -> Paste copied objects
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'v') {
                if (this.copiedLocations && this.copiedLocations.length > 0) {
                    e.preventDefault();
                    this.pasteCopiedLocations();
                } else {
                    this.showToast(`⚠️ Belum ada objek yang disalin. Pilih objek di canvas lalu tekan Ctrl+C.`);
                }
                return;
            }

            if (this.selectedLocations.length === 0) return;

            if (['Delete', 'Backspace'].includes(e.key)) {
                e.preventDefault();
                this.deleteSelectedLocation();
                return;
            }

            if (e.key.toLowerCase() === 'r') {
                e.preventDefault();
                this.rotateLocation(null, 90);
                return;
            }

            const arrowKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
            if (!arrowKeys.includes(e.key)) return;

            e.preventDefault();

            const step = e.shiftKey ? 5 : 1;
            let deltaX = 0;
            let deltaY = 0;

            if (e.key === 'ArrowUp') deltaY = -step;
            if (e.key === 'ArrowDown') deltaY = step;
            if (e.key === 'ArrowLeft') deltaX = -step;
            if (e.key === 'ArrowRight') deltaX = step;

            const groupIds = this.selectedLocations.map(l => l.id);
            const childRacksToSave = [];

            this.selectedLocations.forEach(target => {
                if (target.is_locked !== false) {
                    const typeLabel = target.location_type === 'room' ? 'Gudang' : 'Rak';
                    this.showToast(`🔒 ${typeLabel} '${target.rack_code}' sedang TERKUNCI. Buka kunci gembok terlebih dahulu.`);
                    return;
                }
                if (target.location_type === 'room') {
                    const roomSectorName = target.room_sector || target.rack_code;
                    const childRacks = this.locations.filter(loc => {
                        if (loc.location_type === 'room' || loc.id === target.id) return false;
                        return loc.room_sector === roomSectorName || loc.room_sector === target.rack_code ||
                               (loc.canvas_x >= target.canvas_x && (loc.canvas_x + loc.canvas_width) <= (target.canvas_x + target.canvas_width) &&
                                loc.canvas_y >= target.canvas_y && (loc.canvas_y + loc.canvas_height) <= (target.canvas_y + target.canvas_height));
                    });

                    target.canvas_x = Math.max(0, target.canvas_x + deltaX);
                    target.canvas_y = Math.max(0, target.canvas_y + deltaY);

                    childRacks.forEach(r => {
                        r.canvas_x = Math.max(0, r.canvas_x + deltaX);
                        r.canvas_y = Math.max(0, r.canvas_y + deltaY);
                        childRacksToSave.push(r);
                    });
                } else {
                    let newX = Math.max(0, target.canvas_x + deltaX);
                    let newY = Math.max(0, target.canvas_y + deltaY);

                    if (!this.hasRackCollision(target, newX, target.canvas_y, target.canvas_width, target.canvas_height, groupIds)) {
                        target.canvas_x = newX;
                    }
                    if (!this.hasRackCollision(target, target.canvas_x, newY, target.canvas_width, target.canvas_height, groupIds)) {
                        target.canvas_y = newY;
                    }
                }
            });

            this.renderCanvas();
            this.saveGroupKeyboardNudge([...this.selectedLocations, ...childRacksToSave]);
        },

        saveGroupKeyboardNudge(locationsToSave) {
            if (this.nudgeTimer) clearTimeout(this.nudgeTimer);
            this.nudgeTimer = setTimeout(async () => {
                const uniqueLocs = Array.from(new Map(locationsToSave.map(item => [item.id, item])).values());
                for (const target of uniqueLocs) {
                    try {
                        await fetch(`/api/warehouse/locations/${target.id}/update`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                rack_code: target.rack_code,
                                location_type: target.location_type,
                                room_sector: target.room_sector,
                                canvas_x: target.canvas_x,
                                canvas_y: target.canvas_y,
                                canvas_width: target.canvas_width,
                                canvas_height: target.canvas_height,
                            })
                        });
                    } catch (err) {
                        console.error('Failed to save group keyboard nudge:', err);
                    }
                }
            }, 350);
        },

        saveKeyboardNudge(target) {
            if (Array.isArray(target)) {
                this.saveGroupKeyboardNudge(target);
            } else if (target) {
                this.saveGroupKeyboardNudge([target]);
            }
        },

        getObjectAtPosition(x, y) {
            // 1. Prioritize Rack objects (top foreground layer)
            let found = this.locations.find(loc => {
                if (loc.location_type === 'room') return false;
                return x >= loc.canvas_x && x <= (loc.canvas_x + loc.canvas_width) &&
                       y >= loc.canvas_y && y <= (loc.canvas_y + loc.canvas_height);
            });

            // 2. Fallback to Room objects (background layer)
            if (!found) {
                found = this.locations.find(loc => {
                    if (loc.location_type !== 'room') return false;
                    return x >= loc.canvas_x && x <= (loc.canvas_x + loc.canvas_width) &&
                           y >= loc.canvas_y && y <= (loc.canvas_y + loc.canvas_height);
                });
            }

            return found;
        },

        handleCanvasContextMenu(e) {
            const rect = this.canvas.getBoundingClientRect();
            this.clickedCanvasX = Math.round((e.clientX - rect.left) / this.scale);
            this.clickedCanvasY = Math.round((e.clientY - rect.top) / this.scale);

            // Detect if right click was performed directly over an existing object (Rack prioritized over Room)
            const targetObj = this.getObjectAtPosition(this.clickedCanvasX, this.clickedCanvasY);

            if (targetObj) {
                this.selectedLocation = targetObj;
                this.contextMenuTarget = targetObj;
            } else {
                this.contextMenuTarget = null;
            }

            // Viewport boundary guard for context menu
            const menuWidth = 295;
            const menuHeight = 350;
            const posX = (e.clientX + menuWidth > window.innerWidth) ? Math.max(10, e.clientX - menuWidth) : e.clientX;
            const posY = (e.clientY + menuHeight > window.innerHeight) ? Math.max(10, e.clientY - menuHeight) : e.clientY;

            this.contextMenuX = posX;
            this.contextMenuY = posY;
            this.contextMenuOpen = true;
            this.renderCanvas();
            if (window.lucide) setTimeout(() => lucide.createIcons(), 50);
        },

        async fetchData() {
            this.loading = true;
            try {
                const res = await fetch('{{ route("api.warehouse.layout_data") }}');
                const data = await res.json();
                if (data.success) {
                    this.locations = data.locations;
                    this.departments = data.departments;
                    this.unassignedArchivesList = data.unassigned_archives || [];
                    if (this.selectedRackForModal) {
                        const updated = this.locations.find(l => l.id === this.selectedRackForModal.id);
                        if (updated) this.selectedRackForModal = updated;
                    }
                    this.renderCanvas();

                    // Check for Query Parameters from URL (e.g. ?rack_id=... or ?rack_code=... or ?archive_id=...)
                    const urlParams = new URLSearchParams(window.location.search);
                    const rackId = urlParams.get('rack_id') || urlParams.get('rack');
                    const rackCode = urlParams.get('rack_code');
                    const archiveId = urlParams.get('archive_id');

                    if (rackId || rackCode) {
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.highlightRackByIdOrCode(rackId, rackCode);
                            }, 150);
                        });
                    } else if (archiveId) {
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.highlightArchiveById(archiveId);
                            }, 150);
                        });
                    }
                }
            } catch (err) {
                console.error('Error fetching layout:', err);
            } finally {
                this.loading = false;
            }
        },

