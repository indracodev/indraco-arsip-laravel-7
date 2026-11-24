/* Layout Gudang 2D core (Laravel 7 warehouseCanvasApp state): data, server calls, toast, geometry
   helpers, one requestAnimationFrame render per change. The other warehouse-layout-*.js files add
   drawing, pointer, panel, forms, batch tools and the rack grid to window.WL; [data-act] buttons
   call WL.actions[name]. Objects are plain objects (no reactive proxies on the canvas data). */
(function () {
    var cfg = JSON.parse(document.getElementById('wl-config').textContent);
    var token = document.querySelector('meta[name="csrf-token"]');
    var listeners = {};
    var frame = 0;
    var pending = {};
    var saveTimer = 0;

    function stored(key, fallback) {
        try { return parseInt(window.localStorage.getItem(key), 10) || fallback; } catch (e) { return fallback; }
    }

    var WL = window.WL = {
        cfg: cfg, actions: {}, locations: [], departments: [], selected: [], hover: null, guides: [],
        filterDept: 0, scale: 1, blinkId: null, clipboard: [], size: { w: stored('wh_canvas_w', 0), h: stored('wh_canvas_h', 0) },

        on: function (name, fn) { (listeners[name] = listeners[name] || []).push(fn); },
        emit: function (name, arg) { (listeners[name] || []).forEach(function (fn) { fn(arg); }); },

        /* One render per animation frame, however many changes happened. */
        draw: function () {
            if (frame) { return; }
            frame = window.requestAnimationFrame(function () { frame = 0; if (WL.renderFrame) { WL.renderFrame(); } });
        },

        api: function (method, url, body) {
            var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '' };
            if (body) { headers['Content-Type'] = 'application/json'; }
            return fetch(url, { method: method, headers: headers, body: body ? JSON.stringify(body) : undefined, credentials: 'same-origin' })
                .then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (data) { return { ok: res.ok, status: res.status, data: data }; });
                }, function () { return { ok: false, status: 0, data: { message: 'Koneksi ke server terputus. Coba lagi.' } }; });
        },
        /* Shows the server's refusal in the central alert modal; resolves to the response. */
        send: function (method, url, body) {
            return WL.api(method, url, body).then(function (res) {
                if (!res.ok) { window.showAlertModal(WL.error(res.data, 'Gagal memproses permintaan (HTTP ' + res.status + ').')); }
                return res;
            });
        },
        error: function (data, fallback) {
            var errors = data && data.errors;
            if (errors && !Array.isArray(errors)) { errors = Object.keys(errors).map(function (k) { return errors[k][0]; }); }
            return (errors && errors.length ? errors.join('\n') : '') || (data && data.message) || fallback;
        },
        url: function (id, path) { return cfg.urls.location + '/' + id + '/' + path; },

        toast: function (message, tone) {
            var box = document.getElementById('wl-toasts');
            var toast = document.createElement('div');
            toast.className = 'toast toast--' + (tone || 'success');
            toast.setAttribute('role', 'status');
            toast.textContent = message;
            box.appendChild(toast);
            setTimeout(function () { toast.remove(); }, 4000);
        },

        load: function () {
            return WL.api('GET', cfg.urls.data).then(function (res) {
                document.getElementById('wl-loading').hidden = true;
                if (!res.ok) { window.showAlertModal(WL.error(res.data, 'Gagal memuat layout gudang.')); return; }
                var keep = WL.selected.map(function (l) { return l.id; });
                WL.locations = res.data.locations;
                WL.departments = res.data.departments;
                WL.selected = keep.map(WL.byId).filter(Boolean);
                WL.emit('data');
                WL.emit('select');
                WL.draw();
            });
        },

        byId: function (id) {
            id = Number(id);
            for (var i = 0; i < WL.locations.length; i++) { if (WL.locations[i].id === id) { return WL.locations[i]; } }
            return null;
        },
        isRoom: function (loc) { return loc.location_type === 'room'; },
        isSelected: function (loc) { return WL.selected.indexOf(loc) !== -1; },
        current: function () { return WL.selected[WL.selected.length - 1] || null; },
        select: function (list) { WL.selected = list; WL.emit('select'); WL.draw(); },
        allowed: function (loc) { return loc.assigned_department_ids || []; },
        dept: function (id) {
            for (var i = 0; i < WL.departments.length; i++) { if (WL.departments[i].id === Number(id)) { return WL.departments[i]; } }
            return null;
        },
        deptCode: function (id) { var d = WL.dept(id); return d ? d.code : '#' + id; },
        allowedSummary: function (loc) {
            var ids = WL.allowed(loc);
            return ids.length ? 'Khusus: ' + ids.map(WL.deptCode).join(', ') : 'Umum (Semua Departemen)';
        },
        typeLabel: function (loc) { return WL.isRoom(loc) ? 'Gudang' : 'Rak'; },

        /* Laravel 7 parent room: same sector (or the rack lies inside it). */
        parentRoom: function (rack, box) {
            box = box || rack;
            return WL.locations.filter(WL.isRoom).filter(function (room) {
                return room.room_sector === rack.room_sector || room.rack_code === rack.room_sector || WL.inside(box, room);
            })[0] || null;
        },
        children: function (room) {
            var sector = room.room_sector || room.rack_code;
            return WL.locations.filter(function (l) {
                return !WL.isRoom(l) && (l.room_sector === sector || l.room_sector === room.rack_code || WL.inside(l, room));
            });
        },
        inside: function (a, room) {
            return a.canvas_x >= room.canvas_x && a.canvas_x + a.canvas_width <= room.canvas_x + room.canvas_width
                && a.canvas_y >= room.canvas_y && a.canvas_y + a.canvas_height <= room.canvas_y + room.canvas_height;
        },
        /* Racks may not overlap (2 px clearance); rooms never collide. */
        collides: function (rack, x, y, w, h, exclude) {
            return WL.locations.some(function (o) {
                if (WL.isRoom(o) || o === rack || (exclude && exclude.indexOf(o) !== -1)) { return false; }
                return x < o.canvas_x + o.canvas_width - 2 && x + w > o.canvas_x + 2 && y < o.canvas_y + o.canvas_height - 2 && y + h > o.canvas_y + 2;
            });
        },
        hit: function (x, y) {
            var at = function (l) { return x >= l.canvas_x && x <= l.canvas_x + l.canvas_width && y >= l.canvas_y && y <= l.canvas_y + l.canvas_height; };
            return WL.locations.filter(function (l) { return !WL.isRoom(l) && at(l); })[0] || WL.locations.filter(function (l) { return WL.isRoom(l) && at(l); })[0] || null;
        },
        /* true (and a toast) when one of the objects is locked: Laravel 7 refused move / rotate. */
        locked: function (list) {
            var hit = list.filter(function (l) { return l.is_locked !== false; })[0];
            if (hit) { WL.toast('🔒 ' + WL.typeLabel(hit) + " '" + hit.rack_code + "' sedang TERKUNCI. Buka kunci gembok terlebih dahulu.", 'warning'); }
            return !!hit;
        },

        /* Geometry saves: collected for 350 ms, then one request (last write per object). */
        savePositions: function (list) {
            list.forEach(function (l) { pending[l.id] = l; });
            clearTimeout(saveTimer);
            saveTimer = setTimeout(WL.flushPositions, 350);
        },
        flushPositions: function () {
            var items = Object.keys(pending).map(function (id) {
                var l = pending[id];
                return { id: l.id, canvas_x: Math.max(0, Math.round(l.canvas_x)), canvas_y: Math.max(0, Math.round(l.canvas_y)), canvas_width: Math.round(l.canvas_width),
                    canvas_height: Math.round(l.canvas_height), rotation_angle: l.rotation_angle || 0, orientation: l.canvas_width >= l.canvas_height ? 'horizontal' : 'vertical' };
            });
            pending = {};
            if (!items.length) { return; }
            WL.send('POST', cfg.urls.positions, { items: items }).then(function (res) {
                if (!res.ok) { WL.load(); return; }
                Object.keys(res.data.updated_at || {}).forEach(function (id) { var l = WL.byId(id); if (l) { l.updated_at = res.data.updated_at[id]; } });
            });
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        WL.canvas = document.getElementById('wl-canvas');
        WL.ctx = WL.canvas.getContext('2d', { alpha: false });
        WL.emit('init');
        WL.load().then(function () { WL.emit('ready'); });
    });
})();
