/* Layout Gudang 2D mouse (Laravel 7 canvas_events): click / Shift+click select, drag to move (a room
   takes its racks along, a rack stays inside its room, racks never overlap, edges snap with guides),
   corner resize, rotate / lock knobs, double click = rack grid, right click = context menu.
   Pointer moves are processed once per animation frame; the drop is saved in one request. */
(function () {
    var WL = window.WL;
    var drag = null, last = null, queued = false, cursor = '';

    function point(e) {
        var r = WL.canvas.getBoundingClientRect();
        return { x: (e.clientX - r.left) / WL.scale, y: (e.clientY - r.top) / WL.scale };
    }
    function near(p, at, r) { return Math.abs(p.x - at[0]) <= r && Math.abs(p.y - at[1]) <= r; }
    function corner(l, p) {
        var x = l.canvas_x, y = l.canvas_y, w = l.canvas_width, h = l.canvas_height;
        var c = { se: [x + w, y + h], nw: [x, y], ne: [x + w, y], sw: [x, y + h] };
        for (var k in c) { if (near(p, c[k], 12)) { return k; } }
        return null;
    }
    function setCursor(value) { if (value !== cursor) { cursor = value; WL.canvas.style.cursor = value; } }

    function down(e) {
        if (e.button !== 0) { return; }
        WL.emit('menu-close');
        var p = point(e), cur = WL.current();
        if (cur) {
            var hd = WL.handles(cur), k = corner(cur, p);
            if (near(p, hd.lock, 12)) { WL.toggleLock(cur); return; }
            if (near(p, hd.rotate, 12)) { WL.rotate([cur]); return; }
            if (k) { start(e, cur, k, p); return; }
        }
        var hit = WL.hit(p.x, p.y);
        if (!hit) { if (!e.shiftKey) { WL.select([]); } return; }
        if (e.shiftKey) {
            WL.select(WL.isSelected(hit) ? WL.selected.filter(function (l) { return l !== hit; }) : WL.selected.concat([hit]));
        } else if (!WL.isSelected(hit) || WL.selected.length <= 1) {
            WL.select([hit]);
        }
        if (WL.isSelected(hit)) { start(e, hit, 'move', p); }
    }

    function start(e, target, mode, p) {
        drag = {
            target: target, mode: mode, p: p, moved: false,
            init: { x: target.canvas_x, y: target.canvas_y, w: target.canvas_width, h: target.canvas_height },
            group: WL.selected.map(function (l) { return { l: l, x: l.canvas_x, y: l.canvas_y }; }),
            kids: mode === 'move' && WL.isRoom(target) ? WL.children(target).map(function (l) { return { l: l, x: l.canvas_x, y: l.canvas_y }; }) : [],
            parent: WL.isRoom(target) ? null : WL.parentRoom(target)
        };
        WL.canvas.setPointerCapture(e.pointerId);
    }

    function move(e) {
        last = e;
        if (!queued) { queued = true; window.requestAnimationFrame(process); }
    }

    function process() {
        queued = false;
        var p = point(last);
        if (!drag) { return hover(p); }
        var dx = Math.round(p.x - drag.p.x), dy = Math.round(p.y - drag.p.y);
        if (!drag.moved) {
            if (Math.abs(dx) + Math.abs(dy) < 3) { return; }
            if (WL.locked(drag.group.map(function (g) { return g.l; }).concat([drag.target]))) { drag = null; return; }
            drag.moved = true;
        }
        if (drag.mode === 'move') { moveBy(dx, dy); } else { resize(dx, dy); }
        WL.emit('geometry');
        WL.draw();
    }

    function moveBy(dx, dy) {
        var t = drag.target, group = drag.group.map(function (g) { return g.l; });
        if (drag.group.length > 1) {
            drag.group.forEach(function (g) { place(g.l, Math.max(0, g.x + dx), Math.max(0, g.y + dy), group); });
            return;
        }
        var x = drag.init.x + dx, y = drag.init.y + dy, room = drag.parent;
        if (room) {
            x = Math.max(room.canvas_x + 4, Math.min(room.canvas_x + room.canvas_width - t.canvas_width - 4, x));
            y = Math.max(room.canvas_y + 26, Math.min(room.canvas_y + room.canvas_height - t.canvas_height - 4, y));
        }
        place(t, Math.max(0, x), Math.max(0, y), []);
        drag.kids.forEach(function (k) { k.l.canvas_x = Math.max(0, k.x + dx); k.l.canvas_y = Math.max(0, k.y + dy); });
        snap(t);
    }

    /* Each axis separately, so a rack slides along a neighbour instead of stopping. */
    function place(l, x, y, exclude) {
        if (WL.isRoom(l)) { l.canvas_x = x; l.canvas_y = y; return; }
        if (!WL.collides(l, x, l.canvas_y, l.canvas_width, l.canvas_height, exclude)) { l.canvas_x = x; }
        if (!WL.collides(l, l.canvas_x, y, l.canvas_width, l.canvas_height, exclude)) { l.canvas_y = y; }
    }

    function resize(dx, dy) {
        var t = drag.target, i = drag.init, m = drag.mode, min = WL.isRoom(t) ? 50 : 20, room = drag.parent;
        var w = Math.max(min, i.w + (m === 'se' || m === 'ne' ? dx : -dx));
        var h = Math.max(min, i.h + (m === 'se' || m === 'sw' ? dy : -dy));
        var x = m === 'nw' || m === 'sw' ? i.x + i.w - w : i.x;
        var y = m === 'nw' || m === 'ne' ? i.y + i.h - h : i.y;
        var box = room ? [room.canvas_x + 4, room.canvas_y + 26, room.canvas_x + room.canvas_width - 4, room.canvas_y + room.canvas_height - 4] : [0, 0, WL.size.w, WL.size.h];
        if (x < box[0]) { w -= box[0] - x; x = box[0]; }
        if (y < box[1]) { h -= box[1] - y; y = box[1]; }
        w = Math.max(min, Math.min(w, box[2] - x));
        h = Math.max(min, Math.min(h, box[3] - y));
        if (WL.isRoom(t) || !WL.collides(t, x, t.canvas_y, w, t.canvas_height)) { t.canvas_x = x; t.canvas_width = w; }
        if (WL.isRoom(t) || !WL.collides(t, t.canvas_x, y, t.canvas_width, h)) { t.canvas_y = y; t.canvas_height = h; }
    }

    /* Laravel 7 calculateSnapAndGuides: edges, 4 px gap and centres within 6 px. */
    function snap(t) {
        var best = { x: null, y: null }, guides = { x: null, y: null };
        var tw = t.canvas_width, th = t.canvas_height;
        WL.locations.forEach(function (o) {
            if (o === t || WL.isSelected(o)) { return; }
            var oL = o.canvas_x, oR = oL + o.canvas_width, oT = o.canvas_y, oB = oT + o.canvas_height, cx = oL + o.canvas_width / 2, cy = oT + o.canvas_height / 2;
            var span = [Math.min(t.canvas_y, oT) - 15, Math.max(t.canvas_y + th, oB) + 15], spanX = [Math.min(t.canvas_x, oL) - 15, Math.max(t.canvas_x + tw, oR) + 15];
            [[oL, oL], [oR, oR], [oR + 4, oR + 4], [oR - tw, oR], [oL - tw, oL], [oL - tw - 4, oL - 4], [Math.round(cx - tw / 2), cx]].forEach(function (c) {
                var d = Math.abs(t.canvas_x - c[0]);
                if (d <= 6 && (best.x === null || d < best.x.d) && (WL.isRoom(t) || !WL.collides(t, c[0], t.canvas_y, tw, th, WL.selected))) { best.x = { d: d, v: c[0] }; guides.x = { x: c[1], from: span[0], to: span[1] }; }
            });
            [[oT, oT], [oB, oB], [oB + 4, oB + 4], [oB - th, oB], [oT - th, oT], [oT - th - 4, oT - 4], [Math.round(cy - th / 2), cy]].forEach(function (c) {
                var d = Math.abs(t.canvas_y - c[0]);
                if (d <= 6 && (best.y === null || d < best.y.d) && (WL.isRoom(t) || !WL.collides(t, t.canvas_x, c[0], tw, th, WL.selected))) { best.y = { d: d, v: c[0] }; guides.y = { y: c[1], from: spanX[0], to: spanX[1] }; }
            });
        });
        WL.guides = [];
        if (best.x) { t.canvas_x = Math.max(0, best.x.v); WL.guides.push(guides.x); }
        if (best.y) { t.canvas_y = Math.max(0, best.y.v); WL.guides.push(guides.y); }
    }

    function up() {
        if (drag && drag.moved) {
            WL.savePositions(drag.group.map(function (g) { return g.l; }).concat(drag.kids.map(function (k) { return k.l; }), [drag.target]));
        }
        drag = null;
        if (WL.guides.length) { WL.guides = []; WL.draw(); }
    }

    function hover(p) {
        var cur = WL.current(), hit = WL.hit(p.x, p.y), k = cur && corner(cur, p);
        setCursor(k ? (k === 'se' || k === 'nw' ? 'nwse-resize' : 'nesw-resize') : cur && near(p, WL.handles(cur).rotate, 12) ? 'grab'
            : cur && near(p, WL.handles(cur).lock, 12) ? 'pointer' : hit ? (cur ? 'move' : 'pointer') : 'crosshair');
        if (hit !== WL.hover) { WL.hover = hit; WL.draw(); }
    }

    WL.on('init', function () {
        var c = WL.canvas;
        c.addEventListener('pointerdown', down);
        c.addEventListener('pointermove', move);
        c.addEventListener('pointerup', up);
        c.addEventListener('pointercancel', up);
        c.addEventListener('pointerleave', function () { if (!drag && WL.hover) { WL.hover = null; WL.draw(); } });
        c.addEventListener('dblclick', function (e) {
            var p = point(e), hit = WL.hit(p.x, p.y);
            if (hit && !WL.isRoom(hit)) { WL.actions.grid(); }
        });
        c.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            var p = point(e), hit = WL.hit(p.x, p.y);
            if (hit && !WL.isSelected(hit)) { WL.select([hit]); }
            WL.emit('menu', { x: e.clientX, y: e.clientY, target: hit, at: p });
        });
    });
})();
