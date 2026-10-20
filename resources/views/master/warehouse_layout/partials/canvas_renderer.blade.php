        drawMultiSelectBadge() {
            const ctx = this.ctx;
            ctx.save();
            ctx.fillStyle = '#8b5cf6';
            ctx.fillRect(20, 48, 290, 26);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 11px Inter, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(`✨ ${this.selectedLocations.length} Objek Terseleksi (Shift Multi-Select)`, 30, 65);
            ctx.restore();
        },

        drawFloorGrid(w, h) {
            this.ctx.fillStyle = '#0f172a';
            this.ctx.fillRect(0, 0, w, h);

            this.ctx.strokeStyle = '#1e293b';
            this.ctx.lineWidth = 1;
            for (let x = 0; x < w; x += 40) {
                this.ctx.beginPath();
                this.ctx.moveTo(x, 0);
                this.ctx.lineTo(x, h);
                this.ctx.stroke();
            }
            for (let y = 0; y < h; y += 40) {
                this.ctx.beginPath();
                this.ctx.moveTo(0, y);
                this.ctx.lineTo(w, y);
                this.ctx.stroke();
            }
        },

        drawHeaderBanner() {
            const ctx = this.ctx;
            const bannerW = Math.max(300, (this.canvas ? this.canvas.width : 950) - 40);
            const centerX = (this.canvas ? this.canvas.width : 950) / 2;

            ctx.fillStyle = '#1e293b';
            ctx.fillRect(20, 10, bannerW, 30);
            ctx.fillStyle = '#fbbf24';
            ctx.font = 'bold 14px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('LAYOUT RAK GUDANG DOKUMEN PT INDRACO', centerX, 30);
        },

        drawRoomObject(loc, isFilteredOut) {
            const ctx = this.ctx;
            const x = loc.canvas_x;
            const y = loc.canvas_y;
            const w = loc.canvas_width;
            const h = loc.canvas_height;

            const isSelected = this.selectedLocations.some(l => l.id === loc.id);
            const isHovered = (this.hoveredLocation && this.hoveredLocation.id === loc.id);
            const isBlinking = (this.blinkLocationId === loc.id);

            ctx.save();
            // Background fill with custom color or slate
            const roomBg = loc.custom_color || '#1e293b';
            ctx.fillStyle = roomBg;
            ctx.globalAlpha = isFilteredOut ? 0.2 : 0.4;
            ctx.fillRect(x, y, w, h);

            // Blinking / Pulsing animation if matched by Search
            if (isBlinking) {
                const pulse = (Math.sin(Date.now() / 140) + 1) / 2; // 0..1 oscillating
                ctx.save();
                ctx.shadowColor = '#06b6d4'; // Cyan neon glow
                ctx.shadowBlur = 20 + (pulse * 25);
                ctx.strokeStyle = '#22d3ee';
                ctx.lineWidth = 4 + (pulse * 3);
                ctx.strokeRect(x - 4, y - 4, w + 8, h + 8);

                ctx.fillStyle = `rgba(6, 182, 212, ${0.15 + pulse * 0.35})`;
                ctx.fillRect(x, y, w, h);
                ctx.restore();
            }

            // Wall Border
            ctx.globalAlpha = 1.0;
            ctx.lineWidth = isBlinking ? 4 : (isSelected ? 4 : (isHovered ? 3 : 2));
            ctx.strokeStyle = isBlinking ? '#22d3ee' : (isSelected ? '#ffffff' : (isHovered ? '#fbbf24' : (loc.custom_color || '#64748b')));
            ctx.strokeRect(x, y, w, h);

            // Room Header Title
            ctx.fillStyle = isBlinking ? '#0891b2' : (loc.custom_color || '#334155');
            ctx.fillRect(x, y, w, 24);
            ctx.strokeRect(x, y, w, 24);

            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 11px Inter, sans-serif';
            ctx.textAlign = 'center';
            const lockPrefix = (loc.is_locked !== false) ? '🔒 ' : '🔓 ';
            const blinkBadge = isBlinking ? '🔍 ' : '';
            ctx.fillText(blinkBadge + lockPrefix + (loc.rack_code || loc.room_sector), x + (w / 2), y + 16);

            ctx.restore();
        },

        drawRackObject(loc, isFilteredOut) {
            const ctx = this.ctx;

            const x = loc.canvas_x;
            const y = loc.canvas_y;
            const w = loc.canvas_width;
            const h = loc.canvas_height;

            const isSelected = this.selectedLocations.some(l => l.id === loc.id);
            const isHovered = (this.hoveredLocation && this.hoveredLocation.id === loc.id);
            const isBlinking = (this.blinkLocationId === loc.id);

            let fillColor = loc.custom_color || loc.status_color || '#10b981';
            if (isFilteredOut) {
                fillColor = '#334155';
            }

            ctx.save();
            ctx.fillStyle = fillColor;
            ctx.globalAlpha = isFilteredOut ? 0.3 : 0.85;
            ctx.fillRect(x, y, w, h);

            // Blinking / Pulsing animation if matched by Search
            if (isBlinking) {
                const pulse = (Math.sin(Date.now() / 100) + 1) / 2; // Fast pulsating effect
                ctx.save();
                ctx.shadowColor = '#f59e0b'; // Amber gold neon glow
                ctx.shadowBlur = 20 + (pulse * 30);
                
                const expand = 3 + (pulse * 6);
                ctx.strokeStyle = `rgba(245, 158, 11, ${0.85 + pulse * 0.15})`;
                ctx.lineWidth = 4 + (pulse * 3);
                ctx.strokeRect(x - expand, y - expand, w + (expand * 2), h + (expand * 2));

                ctx.fillStyle = `rgba(251, 191, 36, ${0.35 + pulse * 0.45})`;
                ctx.fillRect(x, y, w, h);
                ctx.restore();
            }

            ctx.globalAlpha = 1.0;
            if (isBlinking) {
                ctx.strokeStyle = '#f59e0b';
                ctx.lineWidth = 4;
            } else if (isSelected) {
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 4;
            } else if (isHovered) {
                ctx.strokeStyle = '#fbbf24';
                ctx.lineWidth = 3;
            } else {
                ctx.strokeStyle = '#0f172a';
                ctx.lineWidth = 2;
            }
            ctx.strokeRect(x, y, w, h);

            if (!isFilteredOut) {
                ctx.save();
                // 1. Strictly clip text inside inner rack boundaries
                ctx.beginPath();
                ctx.rect(x + 2, y + 2, Math.max(1, w - 4), Math.max(1, h - 4));
                ctx.clip();

                // 2. Aspect-ratio based orientation (Horizontal if width >= height, Vertical if height > width)
                const isHorizontal = (w >= h);
                const thickness = isHorizontal ? h : w;
                const length = isHorizontal ? w : h;

                // 3. Dynamic font size scaling based on rack thickness
                let fontSize = 9;
                if (thickness >= 35) fontSize = 11;
                else if (thickness >= 24) fontSize = 9;
                else fontSize = 8;

                ctx.fillStyle = '#ffffff';
                ctx.shadowColor = 'rgba(0, 0, 0, 0.95)';
                ctx.shadowBlur = 4;

                // 4. Smart text formatting based on available length
                let textStr = loc.rack_code || 'RAK';
                if (length >= 95) {
                    textStr += ' (' + loc.current_box_count + '/' + loc.box_capacity + ')';
                } else if (length >= 55) {
                    textStr += ' [' + loc.capacity_percentage + '%]';
                }

                if (isHorizontal) {
                    ctx.font = `bold ${fontSize}px Inter, sans-serif`;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(textStr, x + (w / 2), y + (h / 2));
                } else {
                    ctx.translate(x + (w / 2), y + (h / 2));
                    ctx.rotate(-Math.PI / 2);
                    ctx.font = `bold ${fontSize}px Inter, sans-serif`;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(textStr, 0, 0);
                }

                ctx.restore();
            }

            ctx.restore();
        },

        drawSelectionHandles(loc) {
            const ctx = this.ctx;
            const x = loc.canvas_x;
            const y = loc.canvas_y;
            const w = loc.canvas_width;
            const h = loc.canvas_height;

            ctx.save();
            // Dashed outline box
            ctx.strokeStyle = '#fbbf24';
            ctx.lineWidth = 2;
            ctx.setLineDash([6, 4]);
            ctx.strokeRect(x - 3, y - 3, w + 6, h + 6);

            // 1. Protruding Top Rotation Handle Stem & Circular Dot
            const centerX = x + (w / 2);
            const rotY = y - 24;

            ctx.strokeStyle = '#8b5cf6';
            ctx.lineWidth = 2;
            ctx.setLineDash([]);
            ctx.beginPath();
            ctx.moveTo(centerX, y - 3);
            ctx.lineTo(centerX, rotY);
            ctx.stroke();

            // Circular Rotation Handle Dot
            ctx.fillStyle = '#8b5cf6';
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(centerX, rotY, 7, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();

            // Rotate symbol icon inside handle dot
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 9px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('↻', centerX, rotY + 3);

            // 1b. Protruding Lock / Gembok Handle Dot (for Gudang Room & Rak objects)
            if (loc) {
                const lockX = x + w - 15;
                const lockY = y - 24;
                const isLocked = loc.is_locked !== false;

                // Stem line
                ctx.strokeStyle = isLocked ? '#ef4444' : '#10b981';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(lockX, y - 3);
                ctx.lineTo(lockX, lockY);
                ctx.stroke();

                // Circular Lock Handle Dot
                ctx.fillStyle = isLocked ? '#ef4444' : '#10b981';
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.arc(lockX, lockY, 10, 0, Math.PI * 2);
                ctx.fill();
                ctx.stroke();

                // Lock emoji symbol inside handle dot
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 11px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(isLocked ? '🔒' : '🔓', lockX, lockY + 4);
            }

            // 2. 4 Corner Resize Handles (Square Blue/Amber Dots)
            const handleSize = 8;
            ctx.fillStyle = '#3b82f6';
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;

            const corners = [
                { x: x - 4, y: y - 4 },                 // Top-Left (NW)
                { x: x + w - 4, y: y - 4 },             // Top-Right (NE)
                { x: x + w - 4, y: y + h - 4 },         // Bottom-Right (SE)
                { x: x - 4, y: y + h - 4 }              // Bottom-Left (SW)
            ];

            corners.forEach(c => {
                ctx.fillRect(c.x, c.y, handleSize, handleSize);
                ctx.strokeRect(c.x, c.y, handleSize, handleSize);
            });

            ctx.restore();
        },

