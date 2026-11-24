/* Layout Gudang 2D drawing (Laravel 7 canvas_engine + canvas_renderer) on one <canvas>: floor grid,
   banner, rooms, racks (capacity colour, code + count), selection handles (rotate, lock, 4 corners),
   snap guides. No shadows / blur / animation: a search hit gets a static thick outline for 3 s. */
(function () {
    var WL = window.WL;
    var pal = {};
    var CAP = [[90, 'cap3'], [80, 'cap2'], [50, 'cap1'], [-1, 'cap0']];

    function readPalette() {
        var css = window.getComputedStyle(document.getElementById('wl'));
        ['floor', 'grid', 'banner', 'banner-text', 'room', 'cap-0', 'cap-1', 'cap-2', 'cap-3', 'muted', 'text', 'select', 'hover', 'handle', 'rotate', 'guide', 'blink', 'lock', 'unlock']
            .forEach(function (k) { pal[k.replace('-', '')] = css.getPropertyValue('--wl-' + k).trim(); });
    }

    /* Laravel 7 status_color: custom colour, else by filled percentage. */
    WL.color = function (loc) {
        if (loc.custom_color) { return loc.custom_color; }
        for (var i = 0; i < CAP.length; i++) { if (loc.capacity_percentage > CAP[i][0]) { return pal[CAP[i][1]]; } }
        return pal.cap0;
    };

    WL.applySize = function () {
        var s = WL.scale;
        WL.canvas.width = Math.round(WL.size.w * s);
        WL.canvas.height = Math.round(WL.size.h * s);
        WL.emit('size');
        WL.draw();
    };

    WL.renderFrame = function () {
        var ctx = WL.ctx, w = WL.size.w, h = WL.size.h;
        ctx.setTransform(WL.scale, 0, 0, WL.scale, 0, 0);
        ctx.fillStyle = pal.floor;
        ctx.fillRect(0, 0, w, h);
        ctx.strokeStyle = pal.grid;
        ctx.lineWidth = 1;
        ctx.beginPath();
        for (var x = 0; x < w; x += 40) { ctx.moveTo(x + 0.5, 0); ctx.lineTo(x + 0.5, h); }
        for (var y = 0; y < h; y += 40) { ctx.moveTo(0, y + 0.5); ctx.lineTo(w, y + 0.5); }
        ctx.stroke();
        ctx.fillStyle = pal.banner;
        ctx.fillRect(20, 10, Math.max(300, w - 40), 30);
        ctx.fillStyle = pal.bannertext;
        ctx.font = 'bold 14px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'alphabetic';
        ctx.fillText('LAYOUT RAK GUDANG DOKUMEN PT INDRACO', w / 2, 30);

        WL.locations.forEach(function (l) { if (WL.isRoom(l)) { drawRoom(ctx, l, dimmed(l)); } });
        WL.locations.forEach(function (l) { if (!WL.isRoom(l)) { drawRack(ctx, l, dimmed(l)); } });
        WL.selected.forEach(function (l) { drawHandles(ctx, l); });
        if (WL.selected.length > 1) {
            ctx.fillStyle = pal.rotate;
            ctx.fillRect(20, 48, 290, 26);
            ctx.fillStyle = pal.text;
            ctx.font = 'bold 11px system-ui, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(WL.selected.length + ' Objek Terseleksi (Shift Multi-Select)', 30, 65);
        }
        drawGuides(ctx);
    };

    function dimmed(l) { return WL.filterDept && WL.allowed(l).indexOf(WL.filterDept) === -1; }

    function outline(ctx, l, base) {
        if (WL.blinkId === l.id) { return [pal.blink, 5]; }
        if (WL.isSelected(l)) { return [pal.select, 4]; }
        if (WL.hover === l) { return [pal.hover, 3]; }
        return [base, 2];
    }

    function drawRoom(ctx, l, dim) {
        var x = l.canvas_x, y = l.canvas_y, w = l.canvas_width, h = l.canvas_height, line = outline(ctx, l, l.custom_color || pal.muted);
        ctx.globalAlpha = dim ? 0.2 : 0.4;
        ctx.fillStyle = l.custom_color || pal.banner;
        ctx.fillRect(x, y, w, h);
        ctx.globalAlpha = 1;
        ctx.strokeStyle = line[0];
        ctx.lineWidth = line[1];
        ctx.strokeRect(x, y, w, h);
        ctx.fillStyle = l.custom_color || pal.room;
        ctx.fillRect(x, y, w, 24);
        ctx.strokeRect(x, y, w, 24);
        ctx.fillStyle = pal.text;
        ctx.font = 'bold 11px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText((l.is_locked !== false ? '🔒 ' : '🔓 ') + (l.rack_code || l.room_sector), x + w / 2, y + 16);
    }

    function drawRack(ctx, l, dim) {
        var x = l.canvas_x, y = l.canvas_y, w = l.canvas_width, h = l.canvas_height, line = outline(ctx, l, pal.floor);
        ctx.globalAlpha = dim ? 0.3 : 0.85;
        ctx.fillStyle = dim ? pal.muted : WL.color(l);
        ctx.fillRect(x, y, w, h);
        ctx.globalAlpha = 1;
        ctx.strokeStyle = line[0];
        ctx.lineWidth = line[1];
        ctx.strokeRect(x, y, w, h);
        if (dim) { return; }
        var horizontal = w >= h, thick = horizontal ? h : w, length = horizontal ? w : h;
        var text = l.rack_code || 'RAK';
        if (length >= 95) { text += ' (' + l.current_box_count + '/' + l.box_capacity + ')'; } else if (length >= 55) { text += ' [' + l.capacity_percentage + '%]'; }
        ctx.save();
        ctx.beginPath();
        ctx.rect(x + 2, y + 2, Math.max(1, w - 4), Math.max(1, h - 4));
        ctx.clip();
        ctx.fillStyle = pal.text;
        ctx.font = 'bold ' + (thick >= 35 ? 11 : thick >= 24 ? 9 : 8) + 'px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.translate(x + w / 2, y + h / 2);
        if (!horizontal) { ctx.rotate(-Math.PI / 2); }
        ctx.fillText(text, 0, 0);
        ctx.restore();
    }

    /* Handle positions shared with the pointer module (Laravel 7 coordinates). */
    WL.handles = function (l) {
        return { rotate: [l.canvas_x + l.canvas_width / 2, l.canvas_y - 24], lock: [l.canvas_x + l.canvas_width - 15, l.canvas_y - 24] };
    };

    function drawHandles(ctx, l) {
        var x = l.canvas_x, y = l.canvas_y, w = l.canvas_width, h = l.canvas_height, hd = WL.handles(l), locked = l.is_locked !== false;
        ctx.strokeStyle = pal.hover;
        ctx.lineWidth = 2;
        ctx.setLineDash([6, 4]);
        ctx.strokeRect(x - 3, y - 3, w + 6, h + 6);
        ctx.setLineDash([]);
        knob(ctx, hd.rotate, 7, pal.rotate, '↻', y);
        knob(ctx, hd.lock, 10, locked ? pal.lock : pal.unlock, locked ? '🔒' : '🔓', y);
        ctx.fillStyle = pal.handle;
        ctx.strokeStyle = pal.select;
        [[x, y], [x + w, y], [x + w, y + h], [x, y + h]].forEach(function (c) {
            ctx.fillRect(c[0] - 4, c[1] - 4, 8, 8);
            ctx.strokeRect(c[0] - 4, c[1] - 4, 8, 8);
        });
    }

    function knob(ctx, at, r, color, label, top) {
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(at[0], top - 3);
        ctx.lineTo(at[0], at[1]);
        ctx.stroke();
        ctx.fillStyle = color;
        ctx.strokeStyle = pal.select;
        ctx.beginPath();
        ctx.arc(at[0], at[1], r, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();
        ctx.fillStyle = pal.text;
        ctx.font = 'bold ' + (r > 8 ? 11 : 9) + 'px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(label, at[0], at[1] + 1);
        ctx.textBaseline = 'alphabetic';
    }

    function drawGuides(ctx) {
        if (!WL.guides.length) { return; }
        ctx.strokeStyle = pal.guide;
        ctx.lineWidth = 1.5;
        ctx.setLineDash([5, 4]);
        ctx.beginPath();
        WL.guides.forEach(function (g) {
            if (g.x !== undefined) { ctx.moveTo(g.x, g.from); ctx.lineTo(g.x, g.to); } else { ctx.moveTo(g.from, g.y); ctx.lineTo(g.to, g.y); }
        });
        ctx.stroke();
        ctx.setLineDash([]);
    }

    /* Search / URL hit: static outline, cleared after 3 s (Laravel 7 blinked with a glow loop). */
    WL.blink = function (loc) {
        WL.blinkId = loc.id;
        WL.select([loc]);
        var scroll = document.getElementById('wl-scroll');
        scroll.scrollLeft = Math.max(0, (loc.canvas_x + loc.canvas_width / 2) * WL.scale - scroll.clientWidth / 2);
        scroll.scrollTop = Math.max(0, (loc.canvas_y + loc.canvas_height / 2) * WL.scale - scroll.clientHeight / 2);
        setTimeout(function () { if (WL.blinkId === loc.id) { WL.blinkId = null; WL.draw(); } }, 3000);
    };

    WL.on('init', readPalette);
    WL.on('ready', function () { /* Laravel 7 ?rack_id= / ?rack_code= deep link */
        var q = new URLSearchParams(window.location.search), id = q.get('rack_id') || q.get('rack'), code = q.get('rack_code');
        var hit = id ? WL.byId(id) : WL.locations.filter(function (l) { return l.rack_code === code; })[0];
        if (hit) { WL.blink(hit); }
    });
    WL.on('data', function () {
        if (!WL.size.w) {
            var maxX = 0, maxY = 0;
            WL.locations.forEach(function (l) { maxX = Math.max(maxX, l.canvas_x + l.canvas_width); maxY = Math.max(maxY, l.canvas_y + l.canvas_height); });
            WL.size = { w: Math.max(950, Math.ceil((maxX + 40) / 50) * 50), h: Math.max(750, Math.ceil((maxY + 40) / 50) * 50) };
        }
        if (WL.canvas.width !== Math.round(WL.size.w * WL.scale)) { WL.applySize(); }
    });
})();
