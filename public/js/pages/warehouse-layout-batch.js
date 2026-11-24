/* Layout Gudang 2D tools (Laravel 7 canvas_transform + canvas_tools): rotate 90°, lock, keyboard
   (arrows nudge, R, Delete, Ctrl+C / Ctrl+V, Ctrl+F, Esc), batch lock / size / colour / departments,
   "Rapatkan Baris / Kolom", colour swatches and the department checklist of the modals. */
(function () {
    var WL = window.WL;
    var batchColor = '', batchDepts = [];

    function each(list, fn) { Array.prototype.forEach.call(list, fn); }
    function update(l, body) { return WL.send('POST', WL.url(l.id, 'update'), Object.assign({ rack_code: l.rack_code }, body)); }
    function sequential(list, fn) { return list.reduce(function (c, l) { return c.then(function () { return fn(l); }); }, Promise.resolve()); }

    function turn(l, x, y) {
        var w = l.canvas_width;
        l.canvas_width = l.canvas_height;
        l.canvas_height = w;
        l.canvas_x = Math.max(0, Math.round(x));
        l.canvas_y = Math.max(0, Math.round(y));
        l.rotation_angle = ((l.rotation_angle || 0) + 90) % 360;
    }

    /* One object turns around its centre (a rack shifts up to 30 px to avoid a neighbour); a group
       turns around the group centre and is kept inside its room. */
    WL.rotate = function (list) {
        if (!list.length || WL.locked(list)) { return; }
        if (list.length === 1) {
            var t = list[0], x = t.canvas_x + (t.canvas_width - t.canvas_height) / 2, y = t.canvas_y + (t.canvas_height - t.canvas_width) / 2;
            var shift = [[0, 0], [0, 10], [0, -10], [10, 0], [-10, 0], [0, 20], [0, -20], [20, 0], [-20, 0], [0, 30], [0, -30], [30, 0], [-30, 0]].filter(function (s) {
                return WL.isRoom(t) || !WL.collides(t, x + s[0], y + s[1], t.canvas_height, t.canvas_width, WL.selected);
            })[0];
            if (!shift) { WL.toast('⚠️ Rotasi ' + t.rack_code + ' dibatalkan karena bertabrakan dengan rak lain.', 'warning'); return; }
            turn(t, x + shift[0], y + shift[1]);
        } else {
            var b = bounds(list), cx = (b[0] + b[2]) / 2, cy = (b[1] + b[3]) / 2;
            list.forEach(function (t) {
                var ox = t.canvas_x + t.canvas_width / 2 - cx, oy = t.canvas_y + t.canvas_height / 2 - cy;
                turn(t, cx - oy - t.canvas_height / 2, cy + ox - t.canvas_width / 2);
            });
            var room = WL.parentRoom(list[0]), nb = bounds(list), dx = 0, dy = 0;
            if (room) {
                dx = Math.max(0, room.canvas_x + 8 - nb[0]) || Math.min(0, room.canvas_x + room.canvas_width - 8 - nb[2]);
                dy = Math.max(0, room.canvas_y + 30 - nb[1]) || Math.min(0, room.canvas_y + room.canvas_height - 8 - nb[3]);
            }
            list.forEach(function (t) { t.canvas_x = Math.max(0, t.canvas_x + dx); t.canvas_y = Math.max(0, t.canvas_y + dy); });
            WL.toast('🔄 Kelompok (' + list.length + ' object) berhasil diputar 90°.');
        }
        WL.savePositions(list);
        WL.emit('select');
        WL.draw();
    };

    function bounds(list) {
        return list.reduce(function (b, t) {
            return [Math.min(b[0], t.canvas_x), Math.min(b[1], t.canvas_y), Math.max(b[2], t.canvas_x + t.canvas_width), Math.max(b[3], t.canvas_y + t.canvas_height)];
        }, [Infinity, Infinity, -Infinity, -Infinity]);
    }

    WL.toggleLock = function (l) {
        l.is_locked = l.is_locked === false;
        WL.toast('Status ' + WL.typeLabel(l) + " '" + l.rack_code + "' diset ke " + (l.is_locked ? 'TERKUNCI 🔒' : 'DIBUKA 🔓'), 'info');
        WL.emit('select');
        WL.draw();
        update(l, { is_locked: l.is_locked }).then(function (res) { if (res.ok) { l.updated_at = res.data.updated_at; } else { WL.load(); } });
    };

    function lockAll(state) {
        var list = WL.selected.slice();
        list.forEach(function (l) { l.is_locked = state; });
        WL.emit('select');
        WL.draw();
        sequential(list, function (l) { return update(l, { is_locked: state }).then(function (res) { if (res.ok) { l.updated_at = res.data.updated_at; } }); });
        WL.toast((state ? '🔒 ' : '🔓 ') + list.length + ' object ' + (state ? 'dikunci.' : 'dibuka kuncinya.'));
    }

    /* Laravel 7 alignGroupHorizontally / Vertically: side by side with a 4 px gap, inside the room. */
    function align(horizontal) {
        var list = WL.selected.slice();
        if (list.length < 2 || WL.locked(list)) { return; }
        var a = horizontal ? 'canvas_x' : 'canvas_y', size = horizontal ? 'canvas_width' : 'canvas_height';
        list.sort(function (p, q) { return p[a] - q[a]; });
        var room = WL.parentRoom(list[0]), x = list[0].canvas_x, y = list[0].canvas_y, pos = list[0][a];
        var total = list.reduce(function (s, t) { return s + t[size]; }, 0) + (list.length - 1) * 4;
        if (room) {
            var max = room[a] + room[size] - 8;
            if (pos + total > max) { pos = Math.max(room[a] + (horizontal ? 8 : 30), max - total); }
        }
        list.forEach(function (t) { t.canvas_x = horizontal ? pos : x; t.canvas_y = horizontal ? y : pos; pos += t[size] + 4; });
        WL.savePositions(list);
        WL.toast(horizontal ? '↔️ Kelompok (' + list.length + ' object) berhasil dirapatkan berjejer (Horizontal Row).' : '↕️ Kelompok (' + list.length + ' object) berhasil dirapatkan bertingkat (Vertical Column).');
        WL.draw();
    }

    function nudge(e) {
        var step = e.shiftKey ? 5 : 1, dx = { ArrowLeft: -step, ArrowRight: step }[e.key] || 0, dy = { ArrowUp: -step, ArrowDown: step }[e.key] || 0;
        var list = WL.selected.slice(), save = list.slice();
        if (WL.locked(list)) { return; }
        list.forEach(function (t) {
            if (WL.isRoom(t)) {
                WL.children(t).forEach(function (r) { r.canvas_x = Math.max(0, r.canvas_x + dx); r.canvas_y = Math.max(0, r.canvas_y + dy); save.push(r); });
                t.canvas_x = Math.max(0, t.canvas_x + dx);
                t.canvas_y = Math.max(0, t.canvas_y + dy);
                return;
            }
            if (!WL.collides(t, Math.max(0, t.canvas_x + dx), t.canvas_y, t.canvas_width, t.canvas_height, list)) { t.canvas_x = Math.max(0, t.canvas_x + dx); }
            if (!WL.collides(t, t.canvas_x, Math.max(0, t.canvas_y + dy), t.canvas_width, t.canvas_height, list)) { t.canvas_y = Math.max(0, t.canvas_y + dy); }
        });
        WL.emit('geometry');
        WL.draw();
        WL.savePositions(save);
    }

    function keys(e) {
        var tag = document.activeElement && document.activeElement.tagName, ctrl = e.ctrlKey || e.metaKey, k = e.key.toLowerCase();
        if (document.body.classList.contains('modal-open') || ['INPUT', 'TEXTAREA', 'SELECT'].indexOf(tag) !== -1) { return; }
        if (e.key === 'Escape' && WL.fullscreenOn()) { e.preventDefault(); WL.actions.fullscreen(); return; }
        if (ctrl && k === 'f') { e.preventDefault(); document.getElementById('wl-search').focus(); return; }
        if (ctrl && k === 'v') { e.preventDefault(); WL.actions.paste(); return; }
        if (!WL.selected.length) { return; }
        if (ctrl && k === 'c') { e.preventDefault(); WL.clipboard = WL.selected.slice(); WL.toast('📋 ' + WL.clipboard.length + ' object disalin. Tekan Ctrl+V untuk menempel (paste).', 'info'); return; }
        if (e.key === 'Delete' || e.key === 'Backspace') { e.preventDefault(); WL.actions.delete(); return; }
        if (k === 'r' && !ctrl) { e.preventDefault(); WL.rotate(WL.selected.slice()); return; }
        if (e.key.indexOf('Arrow') === 0) { e.preventDefault(); nudge(e); }
    }

    /* Department checklist: `ids` is changed in place; "Terpilih" counter as Laravel 7. */
    WL.paintDepts = function (name, ids) {
        var box = document.querySelector('[data-depts="' + name + '"]');
        if (!box) { return; }
        var label = document.querySelector('[data-f="' + name + '-dept-count"]');
        var count = function () { label.textContent = ids.length ? ids.length + ' Departemen' : 'Semua Departemen (Bebas)'; };
        box.textContent = '';
        WL.departments.forEach(function (d) {
            var row = document.createElement('label'), input = document.createElement('input');
            input.type = 'checkbox';
            input.checked = ids.indexOf(d.id) !== -1;
            input.addEventListener('change', function () { var i = ids.indexOf(d.id); if (input.checked && i === -1) { ids.push(d.id); } else if (!input.checked && i !== -1) { ids.splice(i, 1); } count(); });
            row.appendChild(input);
            row.appendChild(document.createTextNode(d.code + ' — ' + d.name));
            box.appendChild(row);
        });
        WL.actions[name + '-dept-reset'] = function () { ids.length = 0; each(box.querySelectorAll('input'), function (i) { i.checked = false; }); count(); };
        count();
    };

    function counted(id) { each(document.querySelectorAll('#' + id + ' [data-f="batch-count"]'), function (el) { el.textContent = WL.selected.length + ' objek terpilih'; }); window.dmsModal.open(id); }

    Object.assign(WL.actions, {
        lock: function () { if (WL.current()) { WL.toggleLock(WL.current()); } },
        rotate: function () { WL.rotate(WL.selected.slice()); },
        'batch-lock': function () { lockAll(true); },
        'batch-unlock': function () { lockAll(false); },
        'align-h': function () { align(true); },
        'align-v': function () { align(false); },
        'batch-resize': function () { var f = document.getElementById('wl-batch-resize-form'), l = WL.current(); f.canvas_width.value = l.canvas_width; f.canvas_height.value = l.canvas_height; counted('wl-batch-resize'); },
        'batch-color': function () { batchColor = WL.current().custom_color || '#3b82f6'; document.getElementById('wl-bc-hex').value = batchColor; WL.paintColors('batch', batchColor, function (c) { document.getElementById('wl-bc-hex').value = c; }); counted('wl-batch-color'); },
        'batch-dept': function () { batchDepts = WL.allowed(WL.current()).slice(); WL.paintDepts('batch', batchDepts); counted('wl-batch-dept'); }
    });

    WL.on('init', function () {
        window.addEventListener('keydown', keys);
        document.getElementById('wl-batch-resize-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var w = parseInt(this.canvas_width.value, 10), h = parseInt(this.canvas_height.value, 10);
            WL.selected.forEach(function (l) { l.canvas_width = w; l.canvas_height = h; });
            WL.savePositions(WL.selected.slice());
            window.dmsModal.close('wl-batch-resize');
            WL.toast('📐 ' + WL.selected.length + ' object diubah ukurannya menjadi ' + w + 'x' + h + 'px.');
            WL.emit('select');
            WL.draw();
        });
        each(document.querySelectorAll('#wl-batch-resize [data-size]'), function (b) {
            b.addEventListener('click', function () { var v = b.getAttribute('data-size').split('x'), f = document.getElementById('wl-batch-resize-form'); f.canvas_width.value = v[0]; f.canvas_height.value = v[1]; });
        });
        [['wl-batch-color-form', function () { return { custom_color: document.getElementById('wl-bc-hex').value }; }],
            ['wl-batch-dept-form', function () { return { assigned_department_ids: batchDepts }; }]].forEach(function (pair) {
            var f = document.getElementById(pair[0]);
            if (!f) { return; }
            f.addEventListener('submit', function (e) {
                e.preventDefault();
                var body = pair[1](), list = WL.selected.slice();
                window.dmsModal.close(f.closest('.modal').id);
                sequential(list, function (l) { return update(l, body); }).then(function () { WL.toast('✅ ' + list.length + ' object berhasil diperbarui.'); WL.load(); });
            });
        });
    });
})();
