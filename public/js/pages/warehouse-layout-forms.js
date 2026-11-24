/* Layout Gudang 2D object forms (Laravel 7 canvas_navigation + canvas_modals): add gudang / rak
   (rak: 1-50 at once, free spots found inside the room), edit (optimistic lock: the object's
   updated_at goes along), booking / release, move to another room, duplicate, Ctrl+V paste, delete
   with the central confirm modal. */
(function () {
    var WL = window.WL;
    var add = { type: 'room', x: 400, y: 300, color: '' }, editColor = '', editDepts = [];

    function form(id) { return document.getElementById(id); }
    function num(v) { return parseInt(v, 10) || 0; }
    WL.badRack = function (w, h) {
        if (Math.min(w, h) >= 25 && Math.max(w, h) >= 100) { return false; }
        window.showAlertModal('Perhatian: Ukuran minimal objek Rak adalah Sisi Panjang minimal 100px dan Sisi Tebal/Lebar minimal 25px!');
        return true;
    };

    /* Next spot without rack overlap, row by row inside the room (Laravel 7 collision tries). */
    function freeSpot(x, y, w, h, room) {
        for (var i = 0; i < 60 && WL.collides(null, x, y, w, h); i++) {
            x += w + 8;
            if (x + w > (room ? room.canvas_x + room.canvas_width - 8 : WL.size.w - 30)) { x = room ? room.canvas_x + 15 : 20; y += h + 8; }
        }
        return [x, y];
    }

    function openAdd(type, x, y, room) {
        var f = form('wl-add-form'), rack = type === 'rack', sector = room ? room.room_sector || room.rack_code : (rack ? 'R1' : 'SEKTOR BARU');
        add = { type: type, x: Math.max(0, Math.round(x)), y: Math.max(0, Math.round(y)), color: rack ? '#10b981' : '#3b82f6' };
        document.getElementById('wl-add-title').textContent = rack ? 'Tambah Object Rak (Rectangle)' : 'Tambah Object Gudang (Square)';
        f.rack_code.value = rack ? (room ? 'RAK-' + sector : 'RAK-BARU-01') : 'GUDANG BARU';
        f.room_sector.value = sector;
        f.quantity.value = 1;
        f.box_capacity.value = rack ? 100 : 500;
        f.canvas_width.value = rack ? 40 : 160;
        f.canvas_height.value = rack ? 120 : 140;
        Array.prototype.forEach.call(f.querySelectorAll('[data-show="rack"]'), function (el) { el.hidden = !rack; });
        WL.paintColors('add', add.color, function (c) { add.color = c; });
        window.dmsModal.open('wl-add');
    }

    function submitAdd(e) {
        e.preventDefault();
        var f = e.target, w = num(f.canvas_width.value), h = num(f.canvas_height.value), rack = add.type === 'rack';
        if (rack && WL.badRack(w, h)) { return; }
        var qty = rack ? Math.min(50, Math.max(1, num(f.quantity.value))) : 1, code = f.rack_code.value.trim();
        var room = rack ? WL.locations.filter(function (l) { return WL.isRoom(l) && (l.room_sector === f.room_sector.value || l.rack_code === f.room_sector.value); })[0] : null;
        var base = code.replace(/[\s-]*\d+$/, ''), used = WL.locations.map(function (l) { return l.rack_code; }), made = [];
        var queue = [], x = add.x, y = add.y;
        for (var i = 1, n = 1; i <= qty; i++) {
            var name = qty > 1 || used.indexOf(code) !== -1 ? base + '-' + String(n).padStart(2, '0') : code;
            while (used.indexOf(name) !== -1) { n++; name = base + '-' + String(n).padStart(2, '0'); }
            used.push(name);
            n++;
            var spot = rack ? freeSpot(x, y, w, h, room) : [x, y];
            WL.locations.push({ id: -i, location_type: 'rack', canvas_x: spot[0], canvas_y: spot[1], canvas_width: w, canvas_height: h }); // reserve the spot
            queue.push({ location_type: add.type, rack_code: name, room_sector: f.room_sector.value || null, box_capacity: num(f.box_capacity.value) || 100,
                canvas_x: spot[0], canvas_y: spot[1], canvas_width: w, canvas_height: h, custom_color: add.color || null });
            x = spot[0] + w + 8;
            y = spot[1];
        }
        WL.locations = WL.locations.filter(function (l) { return l.id > 0; });
        window.dmsModal.close('wl-add');
        createAll(queue, made).then(function () {
            if (made.length) { WL.toast('✨ Berhasil membuat ' + made.length + ' objek ' + (rack ? 'Rak' : 'Gudang') + ' baru di canvas!'); }
            WL.load().then(function () { WL.select(made.map(WL.byId).filter(Boolean)); });
        });
    }

    /* One request after the other: each create is its own short transaction. */
    function createAll(queue, made) {
        return queue.reduce(function (chain, payload) {
            return chain.then(function () { return WL.send('POST', WL.cfg.urls.store, payload).then(function (res) { if (res.ok) { made.push(res.data.id); } }); });
        }, Promise.resolve());
    }

    function openEdit() {
        var l = WL.current(), f = form('wl-edit-form');
        if (!l) { return; }
        f.rack_code.value = l.rack_code;
        f.room_sector.value = l.room_sector || '';
        f.shelf_code.value = l.shelf_code || '';
        f.box_capacity.value = l.box_capacity || 50;
        f.orientation.value = l.canvas_width >= l.canvas_height ? 'horizontal' : 'vertical';
        f.canvas_width.value = l.canvas_width;
        f.canvas_height.value = l.canvas_height;
        f.is_locked.value = l.is_locked !== false ? '1' : '0';
        editColor = l.custom_color || '';
        editDepts = WL.allowed(l).slice();
        WL.paintColors('edit', editColor, function (c) { editColor = c; });
        WL.paintDepts('edit', editDepts);
        window.dmsModal.open('wl-edit');
    }

    function submitEdit(e) {
        e.preventDefault();
        var l = WL.current(), f = e.target, w = num(f.canvas_width.value), h = num(f.canvas_height.value);
        if (!WL.isRoom(l) && (WL.badRack(w, h))) { return; }
        if (!WL.isRoom(l) && WL.collides(l, l.canvas_x, l.canvas_y, w, h)) { window.showAlertModal('Perhatian: Perubahan dimensi rak akan bertabrakan / tumpang tindih dengan rak lain di sekitarnya!'); return; }
        var body = { rack_code: f.rack_code.value.trim(), room_sector: f.room_sector.value.trim() || null, shelf_code: f.shelf_code.value.trim() || null,
            box_capacity: num(f.box_capacity.value), orientation: f.orientation.value, canvas_width: w, canvas_height: h, custom_color: editColor || null,
            is_locked: f.is_locked.value === '1', expected_updated_at: l.updated_at };
        if (WL.cfg.isAdmin) { body.assigned_department_ids = editDepts; }
        WL.send('POST', WL.url(l.id, 'update'), body).then(function (res) {
            if (!res.ok) { return; }
            window.dmsModal.close('wl-edit');
            WL.toast(res.data.message);
            WL.load();
        });
    }

    function placeIn(rack, room) {
        var spot = freeSpot(room.canvas_x + 15, room.canvas_y + 35, rack.canvas_width, rack.canvas_height, room);
        WL.send('POST', WL.url(rack.id, 'update'), { rack_code: rack.rack_code, room_sector: room.room_sector || room.rack_code, canvas_x: spot[0], canvas_y: spot[1] }).then(function (res) {
            if (!res.ok) { return; }
            window.dmsModal.close('wl-move');
            WL.toast("Rak '" + rack.rack_code + "' berhasil dipindahkan ke Gudang/Sektor '" + room.rack_code + "' (" + (room.room_sector || room.rack_code) + ').');
            WL.load();
        });
    }

    function duplicate(list) {
        var used = WL.locations.map(function (l) { return l.rack_code; }), made = [];
        var queue = list.map(function (src) {
            var base = (src.rack_code || 'RAK').replace(/ \(COPY\s?\d*\)$/i, ''), code = base + ' (COPY)';
            for (var i = 2; used.indexOf(code) !== -1; i++) { code = base + ' (COPY ' + i + ')'; }
            used.push(code);
            var room = WL.isRoom(src) ? null : WL.parentRoom(src), spot = WL.isRoom(src) ? [src.canvas_x + 20, src.canvas_y + 20] : freeSpot(src.canvas_x + 20, src.canvas_y + 20, src.canvas_width, src.canvas_height, room);
            return { location_type: src.location_type, rack_code: code, room_sector: src.room_sector, shelf_code: src.shelf_code, box_capacity: src.box_capacity,
                custom_color: src.custom_color, canvas_x: spot[0], canvas_y: spot[1], canvas_width: src.canvas_width, canvas_height: src.canvas_height, rotation_angle: src.rotation_angle || 0 };
        });
        createAll(queue, made).then(function () {
            if (made.length) { WL.toast('📋 ' + made.length + ' object berhasil diduplikat.'); }
            WL.load().then(function () { WL.select(made.map(WL.byId).filter(Boolean)); });
        });
    }

    Object.assign(WL.actions, {
        'add-room': function () { openAdd('room', 400, 300); },
        'add-rack': function () { openAdd('rack', 450, 320); },
        'add-room-here': function () { openAdd('room', WL.menuPoint.x, WL.menuPoint.y); },
        'add-rack-here': function () { openAdd('rack', WL.menuPoint.x, WL.menuPoint.y); },
        'add-rack-in-room': function () { var r = WL.current(); openAdd('rack', r.canvas_x + 15, r.canvas_y + 35, r); },
        edit: openEdit,
        duplicate: function () { duplicate(WL.selected.slice()); },
        paste: function () { if (WL.clipboard.length) { duplicate(WL.clipboard); } else { WL.toast('⚠️ Belum ada objek yang disalin. Pilih objek di canvas lalu tekan Ctrl+C.', 'warning'); } },
        book: function () { form('wl-book-form').reset(); window.dmsModal.open('wl-book'); },
        unbook: function () {
            var l = WL.current();
            window.showConfirmModal({ title: 'Lepas Status Booking', message: "Lepas status booking pada rak '" + l.rack_code + "'?", type: 'warning', confirmText: 'Ya, Lepas' }).then(function (ok) {
                if (ok) { WL.send('POST', WL.url(l.id, 'unbook')).then(function (res) { if (res.ok) { WL.toast(res.data.message); WL.load(); } }); }
            });
        },
        move: function () {
            var rack = WL.current(), list = form('wl-move-list');
            list.textContent = '';
            document.querySelector('#wl-move [data-f="move-code"]').textContent = rack.rack_code;
            WL.locations.filter(WL.isRoom).forEach(function (room) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'btn';
                b.textContent = room.rack_code + ' — Sektor: ' + (room.room_sector || room.rack_code) + ' (' + room.canvas_width + 'x' + room.canvas_height + ' px)  Pindahkan →';
                b.addEventListener('click', function () { placeIn(rack, room); });
                list.appendChild(b);
            });
            if (!list.children.length) { list.textContent = 'Belum ada objek Gudang/Sektor (Square). Silakan tambah Gudang terlebih dahulu.'; }
            window.dmsModal.open('wl-move');
        },
        delete: function () {
            var list = WL.selected.slice();
            if (!list.length) { return; }
            window.showConfirmModal({ title: 'Hapus Object Canvas', confirmText: 'Ya, Hapus', message: list.length === 1
                ? "Apakah Anda yakin ingin menghapus object '" + list[0].rack_code + "' dari canvas layout gudang?" + (WL.isRoom(list[0]) ? ' Master gudang dengan kode yang sama ikut terhapus beserta raknya.' : '')
                : 'Apakah Anda yakin ingin menghapus ' + list.length + ' object terseleksi dari canvas layout gudang?' }).then(function (ok) {
                if (!ok) { return; }
                var done = 0;
                list.reduce(function (chain, l) {
                    return chain.then(function () { return WL.send('POST', WL.url(l.id, 'delete')).then(function (res) { if (res.ok) { done++; } }); });
                }, Promise.resolve()).then(function () { if (done) { WL.toast('🗑️ ' + done + ' object berhasil dihapus dari canvas layout.'); } WL.select([]); WL.load(); });
            });
        }
    });

    WL.on('init', function () {
        form('wl-add-form').addEventListener('submit', submitAdd);
        form('wl-edit-form').addEventListener('submit', submitEdit);
        form('wl-book-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var l = WL.current();
            WL.send('POST', WL.url(l.id, 'book'), { department_id: num(this.department_id.value), booking_notes: this.booking_notes.value.trim() }).then(function (res) {
                if (res.ok) { window.dmsModal.close('wl-book'); WL.toast(res.data.message); WL.load(); }
            });
        });
    });
    WL.on('data', function () {
        var select = form('wl-book-dept');
        if (select.options.length > 1) { return; }
        WL.departments.forEach(function (d) { select.add(new Option(d.code + ' - ' + d.name, d.id)); });
    });
})();
