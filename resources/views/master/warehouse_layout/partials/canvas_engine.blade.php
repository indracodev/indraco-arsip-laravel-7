        renderCanvas() {
            if (!this.ctx) return;

            const w = this.canvas.width;
            const h = this.canvas.height;

            // Clear Background
            this.ctx.clearRect(0, 0, w, h);

            this.ctx.save();
            this.ctx.scale(this.scale, this.scale);

            // 1. Draw Architectural Floor & Grid
            this.drawFloorGrid(w, h);

            // 2. Draw Architectural Fixed Headers
            this.drawHeaderBanner();

            // 3. Draw All Database Warehouse Objects (Rooms drawn first as background layer, Racks on top)
            const rooms = this.locations.filter(l => l.location_type === 'room');
            const racks = this.locations.filter(l => l.location_type !== 'room');

            rooms.forEach(loc => {
                let isFilteredOut = (this.filterDepartment && !this.getAllowedDeptIds(loc).includes(Number(this.filterDepartment)));
                this.drawRoomObject(loc, isFilteredOut);
            });

            racks.forEach(loc => {
                let isFilteredOut = (this.filterDepartment && !this.getAllowedDeptIds(loc).includes(Number(this.filterDepartment)));
                this.drawRackObject(loc, isFilteredOut);
            });

            // 4. Draw Selected Handles (Resize Corners & Bounding Box)
            this.selectedLocations.forEach(loc => {
                this.drawSelectionHandles(loc);
            });

            // 5. Draw Multi-Select Badge if more than 1 object selected
            if (this.selectedLocations.length > 1) {
                this.drawMultiSelectBadge();
            }

            // 6. Draw Alignment Guidance Lines (Smart Snap Guides)
            if (this.activeGuideLines && this.activeGuideLines.length > 0) {
                this.drawGuideLines();
            }

            this.ctx.restore();
            if (window.lucide) lucide.createIcons();
        },

        drawGuideLines() {
            const ctx = this.ctx;
            ctx.save();
            ctx.strokeStyle = '#06b6d4'; // Cyan 500 accent guide line
            ctx.lineWidth = 1.5;

            this.activeGuideLines.forEach(guide => {
                ctx.setLineDash([5, 4]);
                ctx.beginPath();
                if (guide.type === 'vertical') {
                    ctx.moveTo(guide.x, guide.minY);
                    ctx.lineTo(guide.x, guide.maxY);
                    ctx.stroke();

                    // Glowing intersection dots
                    ctx.fillStyle = '#06b6d4';
                    ctx.setLineDash([]);
                    ctx.beginPath();
                    ctx.arc(guide.x, guide.minY, 3.5, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.beginPath();
                    ctx.arc(guide.x, guide.maxY, 3.5, 0, Math.PI * 2);
                    ctx.fill();
                } else if (guide.type === 'horizontal') {
                    ctx.moveTo(guide.minX, guide.y);
                    ctx.lineTo(guide.maxX, guide.y);
                    ctx.stroke();

                    // Glowing intersection dots
                    ctx.fillStyle = '#06b6d4';
                    ctx.setLineDash([]);
                    ctx.beginPath();
                    ctx.arc(guide.minX, guide.y, 3.5, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.beginPath();
                    ctx.arc(guide.maxX, guide.y, 3.5, 0, Math.PI * 2);
                    ctx.fill();
                }
            });

            ctx.restore();
        },

        calculateSnapAndGuides(target) {
            this.activeGuideLines = [];
            if (!target) return;

            const SNAP_THRESH = 6;
            const selectedIds = this.selectedLocations.map(l => l.id);

            // Candidates: all other locations not currently in selection
            const candidates = this.locations.filter(l => !selectedIds.includes(l.id));
            if (candidates.length === 0) return;

            const tX = target.canvas_x;
            const tY = target.canvas_y;
            const tW = target.canvas_width;
            const tH = target.canvas_height;

            const tLeft = tX;
            const tRight = tX + tW;
            const tCenterX = tX + (tW / 2);

            const tTop = tY;
            const tBottom = tY + tH;
            const tCenterY = tY + (tH / 2);

            let bestSnapX = null;
            let minDiffX = SNAP_THRESH + 1;
            let guideXObj = null;

            let bestSnapY = null;
            let minDiffY = SNAP_THRESH + 1;
            let guideYObj = null;

            candidates.forEach(other => {
                const oLeft = other.canvas_x;
                const oRight = other.canvas_x + other.canvas_width;
                const oCenterX = other.canvas_x + (other.canvas_width / 2);

                const oTop = other.canvas_y;
                const oBottom = other.canvas_y + other.canvas_height;
                const oCenterY = other.canvas_y + (other.canvas_height / 2);

                // --- X ALIGNMENT CHECKS ---
                const xPairs = [
                    { diff: Math.abs(tLeft - oLeft), newX: oLeft, lineX: oLeft },
                    { diff: Math.abs(tLeft - oRight), newX: oRight, lineX: oRight },
                    { diff: Math.abs(tLeft - (oRight + 4)), newX: oRight + 4, lineX: oRight + 4 },
                    { diff: Math.abs(tRight - oRight), newX: oRight - tW, lineX: oRight },
                    { diff: Math.abs(tRight - oLeft), newX: oLeft - tW, lineX: oLeft },
                    { diff: Math.abs(tRight - (oLeft - 4)), newX: oLeft - tW - 4, lineX: oLeft - 4 },
                    { diff: Math.abs(tCenterX - oCenterX), newX: Math.round(oCenterX - (tW / 2)), lineX: oCenterX }
                ];

                xPairs.forEach(pair => {
                    if (pair.diff <= SNAP_THRESH && pair.diff < minDiffX) {
                        if (target.location_type === 'room' || !this.hasRackCollision(target, pair.newX, tY, tW, tH, selectedIds)) {
                            minDiffX = pair.diff;
                            bestSnapX = pair.newX;
                            guideXObj = {
                                type: 'vertical',
                                x: pair.lineX,
                                minY: Math.min(tTop, oTop) - 15,
                                maxY: Math.max(tBottom, oBottom) + 15
                            };
                        }
                    }
                });

                // --- Y ALIGNMENT CHECKS ---
                const yPairs = [
                    { diff: Math.abs(tTop - oTop), newY: oTop, lineY: oTop },
                    { diff: Math.abs(tTop - oBottom), newY: oBottom, lineY: oBottom },
                    { diff: Math.abs(tTop - (oBottom + 4)), newY: oBottom + 4, lineY: oBottom + 4 },
                    { diff: Math.abs(tBottom - oBottom), newY: oBottom - tH, lineY: oBottom },
                    { diff: Math.abs(tBottom - oTop), newY: oTop - tH, lineY: oTop },
                    { diff: Math.abs(tBottom - (oTop - 4)), newY: oTop - tH - 4, lineY: oTop - 4 },
                    { diff: Math.abs(tCenterY - oCenterY), newY: Math.round(oCenterY - (tH / 2)), lineY: oCenterY }
                ];

                yPairs.forEach(pair => {
                    if (pair.diff <= SNAP_THRESH && pair.diff < minDiffY) {
                        if (target.location_type === 'room' || !this.hasRackCollision(target, tX, pair.newY, tW, tH, selectedIds)) {
                            minDiffY = pair.diff;
                            bestSnapY = pair.newY;
                            guideYObj = {
                                type: 'horizontal',
                                y: pair.lineY,
                                minX: Math.min(tLeft, oLeft) - 15,
                                maxX: Math.max(tRight, oRight) + 15
                            };
                        }
                    }
                });
            });

            if (bestSnapX !== null) {
                target.canvas_x = bestSnapX;
                if (guideXObj) this.activeGuideLines.push(guideXObj);
            }
            if (bestSnapY !== null) {
                target.canvas_y = bestSnapY;
                if (guideYObj) this.activeGuideLines.push(guideYObj);
            }
        },

        calculateResizeSnapAndGuides(target, mode, propX, propY, propW, propH, parentRoom = null) {
            this.activeGuideLines = [];
            if (!target || !mode) {
                return { x: propX, y: propY, w: propW, h: propH, guideX: null, guideY: null };
            }

            const SNAP_THRESH = Math.max(8, Math.round(8 / (this.scale || 1)));
            const isTargetRoom = target.location_type === 'room';

            // Candidates to align with
            let candidates = [];
            if (isTargetRoom) {
                // When resizing a Gudang room, align with other Gudang rooms
                candidates = this.locations.filter(l => l.location_type === 'room' && l.id !== target.id);
            } else {
                // When resizing a Rack, align with other Racks
                candidates = this.locations.filter(l => l.location_type !== 'room' && l.id !== target.id);
            }

            let resultX = propX;
            let resultY = propY;
            let resultW = propW;
            let resultH = propH;

            const tL = propX;
            const tR = propX + propW;
            const tT = propY;
            const tB = propY + propH;

            const activeRight = (mode === 'resize-se' || mode === 'resize-ne');
            const activeLeft = (mode === 'resize-nw' || mode === 'resize-sw');
            const activeBottom = (mode === 'resize-se' || mode === 'resize-sw');
            const activeTop = (mode === 'resize-nw' || mode === 'resize-ne');

            let bestSnapX = null;
            let minDiffX = SNAP_THRESH + 1;
            let guideXObj = null;

            let bestSnapY = null;
            let minDiffY = SNAP_THRESH + 1;
            let guideYObj = null;

            // Include parent room inner borders as an alignment candidate for racks
            if (!isTargetRoom && parentRoom) {
                const pL = parentRoom.canvas_x + 4;
                const pR = parentRoom.canvas_x + parentRoom.canvas_width - 4;
                const pT = parentRoom.canvas_y + 26;
                const pB = parentRoom.canvas_y + parentRoom.canvas_height - 4;

                candidates = [...candidates, {
                    id: -999,
                    canvas_x: pL,
                    canvas_y: pT,
                    canvas_width: Math.max(0, pR - pL),
                    canvas_height: Math.max(0, pB - pT),
                    is_border: true
                }];
            }

            candidates.forEach(other => {
                const oL = other.canvas_x;
                const oR = other.canvas_x + other.canvas_width;
                const oCenterX = other.canvas_x + (other.canvas_width / 2);

                const oT = other.canvas_y;
                const oB = other.canvas_y + other.canvas_height;
                const oCenterY = other.canvas_y + (other.canvas_height / 2);

                // --- HORIZONTAL / X ALIGNMENT ---
                if (activeRight) {
                    const pairs = [
                        { diff: Math.abs(tR - oR), snapVal: oR, lineX: oR },
                        { diff: Math.abs(tR - oL), snapVal: oL, lineX: oL },
                        { diff: Math.abs(tR - (oL - 4)), snapVal: oL - 4, lineX: oL - 4 },
                        { diff: Math.abs(tR - oCenterX), snapVal: Math.round(oCenterX), lineX: oCenterX },
                    ];

                    // Same width match as sibling rack
                    if (!other.is_border) {
                        const targetSameW = target.canvas_x + other.canvas_width;
                        pairs.push({ diff: Math.abs(tR - targetSameW), snapVal: targetSameW, lineX: targetSameW });
                    }

                    pairs.forEach(p => {
                        if (p.diff <= SNAP_THRESH && p.diff < minDiffX) {
                            const newW = p.snapVal - target.canvas_x;
                            if (newW >= 20) {
                                minDiffX = p.diff;
                                bestSnapX = { edge: 'right', w: newW };
                                guideXObj = {
                                    type: 'vertical',
                                    x: p.lineX,
                                    minY: Math.max(0, Math.min(tT, oT) - 15),
                                    maxY: Math.max(tB, oB) + 15
                                };
                            }
                        }
                    });
                } else if (activeLeft) {
                    const fixedRight = this.initialX + this.initialW;
                    const pairs = [
                        { diff: Math.abs(tL - oL), snapVal: oL, lineX: oL },
                        { diff: Math.abs(tL - oR), snapVal: oR, lineX: oR },
                        { diff: Math.abs(tL - (oR + 4)), snapVal: oR + 4, lineX: oR + 4 },
                        { diff: Math.abs(tL - oCenterX), snapVal: Math.round(oCenterX), lineX: oCenterX },
                    ];

                    // Same width match as sibling rack
                    if (!other.is_border) {
                        const targetSameX = fixedRight - other.canvas_width;
                        pairs.push({ diff: Math.abs(tL - targetSameX), snapVal: targetSameX, lineX: targetSameX });
                    }

                    pairs.forEach(p => {
                        if (p.diff <= SNAP_THRESH && p.diff < minDiffX) {
                            const newW = fixedRight - p.snapVal;
                            if (newW >= 20) {
                                minDiffX = p.diff;
                                bestSnapX = { edge: 'left', x: p.snapVal, w: newW };
                                guideXObj = {
                                    type: 'vertical',
                                    x: p.lineX,
                                    minY: Math.max(0, Math.min(tT, oT) - 15),
                                    maxY: Math.max(tB, oB) + 15
                                };
                            }
                        }
                    });
                }

                // --- VERTICAL / Y ALIGNMENT ---
                if (activeBottom) {
                    const pairs = [
                        { diff: Math.abs(tB - oB), snapVal: oB, lineY: oB },
                        { diff: Math.abs(tB - oT), snapVal: oT, lineY: oT },
                        { diff: Math.abs(tB - (oT - 4)), snapVal: oT - 4, lineY: oT - 4 },
                        { diff: Math.abs(tB - oCenterY), snapVal: Math.round(oCenterY), lineY: oCenterY },
                    ];

                    // Same height match as sibling rack
                    if (!other.is_border) {
                        const targetSameH = target.canvas_y + other.canvas_height;
                        pairs.push({ diff: Math.abs(tB - targetSameH), snapVal: targetSameH, lineY: targetSameH });
                    }

                    pairs.forEach(p => {
                        if (p.diff <= SNAP_THRESH && p.diff < minDiffY) {
                            const newH = p.snapVal - target.canvas_y;
                            if (newH >= 20) {
                                minDiffY = p.diff;
                                bestSnapY = { edge: 'bottom', h: newH };
                                guideYObj = {
                                    type: 'horizontal',
                                    y: p.lineY,
                                    minX: Math.max(0, Math.min(tL, oL) - 15),
                                    maxX: Math.max(tR, oR) + 15
                                };
                            }
                        }
                    });
                } else if (activeTop) {
                    const fixedBottom = this.initialY + this.initialH;
                    const pairs = [
                        { diff: Math.abs(tT - oT), snapVal: oT, lineY: oT },
                        { diff: Math.abs(tT - oB), snapVal: oB, lineY: oB },
                        { diff: Math.abs(tT - (oB + 4)), snapVal: oB + 4, lineY: oB + 4 },
                        { diff: Math.abs(tT - oCenterY), snapVal: Math.round(oCenterY), lineY: oCenterY },
                    ];

                    // Same height match as sibling rack
                    if (!other.is_border) {
                        const targetSameY = fixedBottom - other.canvas_height;
                        pairs.push({ diff: Math.abs(tT - targetSameY), snapVal: targetSameY, lineY: targetSameY });
                    }

                    pairs.forEach(p => {
                        if (p.diff <= SNAP_THRESH && p.diff < minDiffY) {
                            const newH = fixedBottom - p.snapVal;
                            if (newH >= 20) {
                                minDiffY = p.diff;
                                bestSnapY = { edge: 'top', y: p.snapVal, h: newH };
                                guideYObj = {
                                    type: 'horizontal',
                                    y: p.lineY,
                                    minX: Math.max(0, Math.min(tL, oL) - 15),
                                    maxX: Math.max(tR, oR) + 15
                                };
                            }
                        }
                    });
                }
            });

            // Apply best snaps
            if (bestSnapX) {
                if (bestSnapX.edge === 'right') {
                    resultW = bestSnapX.w;
                } else if (bestSnapX.edge === 'left') {
                    resultX = bestSnapX.x;
                    resultW = bestSnapX.w;
                }
            }

            if (bestSnapY) {
                if (bestSnapY.edge === 'bottom') {
                    resultH = bestSnapY.h;
                } else if (bestSnapY.edge === 'top') {
                    resultY = bestSnapY.y;
                    resultH = bestSnapY.h;
                }
            }

            return {
                x: resultX,
                y: resultY,
                w: resultW,
                h: resultH,
                guideX: guideXObj,
                guideY: guideYObj
            };
        },

