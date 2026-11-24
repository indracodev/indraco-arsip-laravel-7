/* Layout Gudang 2D page chrome (Laravel 7 inspector drawer, canvas_toolbar, canvas_search,
   canvas_context_menu, canvas size / zoom / full screen). Text goes in with textContent only;
   [data-act] clicks anywhere on the page call WL.actions[name]. */
(function () {
    var WL = window.WL;
    var panel, menu, searchTimer = 0;

    function each(root, selector, fn) { Array.prototype.forEach.call(root.querySelectorAll(selector), fn); }
    function set(root, name, text) { each(root, '[data-f="' + name + '"]', function (el) { el.textContent = text; }); }
    function show(root, name, on) { each(root, '[data-show="' + name + '"]', function (el) { el.hidden = !on; }); }

    /* Inspector of the last selected object, batch card when several are selected. */
    function refresh() {
        var l = WL.current(), many = WL.selected.length > 1;
        [panel, menu].forEach(function (root) {
            show(root, 'none', !l);
            show(root, 'one', !!l);
            show(root, 'many', many);
            if (!l) { return; }
            var room = WL.isRoom(l), locked = l.is_locked !== false;
            show(root, 'rack', !room);
            show(root, 'room', room);
            show(root, 'bookable', !room && !l.is_booked);
            show(root, 'booked', !!l.is_booked);
            set(root, 'lock-btn', locked ? '🔓 Buka Kunci' : '🔒 Kunci Object');
        });
        if (!l) { return; }
        var room = WL.isRoom(l), ids = WL.allowed(l), s = l.stats || {};
        set(panel, 'batch-count', WL.selected.length + ' Objek');
        set(panel, 'type', room ? 'OBJECT GUDANG / SEKTOR' : 'OBJECT RAK');
        panel.querySelector('[data-f="swatch"]').hidden = !l.custom_color;
        panel.querySelector('[data-f="swatch"]').style.backgroundColor = l.custom_color || '';
        set(panel, 'code', l.rack_code);
        set(panel, 'sector', 'Sektor: ' + (l.room_sector || 'Umum'));
        set(panel, 'lock-label', room ? 'Status Gudang:' : 'Status Rak:');
        set(panel, 'lock', l.is_locked !== false ? 'Terkunci (Locked 🔒)' : 'Terbuka (Unlocked 🔓)');
        set(panel, 'allowed', ids.length ? ids.map(WL.deptCode).join(', ') : 'Semua Departemen (Bebas)');
        set(panel, 'pos', 'X:' + l.canvas_x + ', Y:' + l.canvas_y);
        set(panel, 'size', l.canvas_width + 'x' + l.canvas_height + 'px');
        set(panel, 'capacity', (l.current_box_count || 0) + ' / ' + (l.box_capacity || 0) + ' Box (' + (l.capacity_percentage || 0) + '%)');
        var bar = panel.querySelector('[data-f="meter"]');
        bar.style.width = Math.min(100, l.capacity_percentage || 0) + '%';
        bar.style.backgroundColor = WL.color(l);
        set(panel, 'st-empty', s.empty || 0);
        set(panel, 'st-filled', s.filled || 0);
        set(panel, 'st-expired', s.expired || 0);
        set(panel, 'alloc', WL.allowedSummary(l));
        set(panel, 'booking-notes', l.booking_notes || 'Alokasi booking berkas');
        set(panel, 'booked-by', 'Oleh: ' + (l.booked_by_user || 'User'));
        set(panel, 'duplicate', room ? 'Duplikat (Copy) Gudang Ini' : 'Duplikat (Copy) Rak Ini');
    }

    function zoom(value) {
        WL.scale = Math.round(Math.max(0.5, Math.min(2, value)) * 10) / 10;
        document.querySelector('[data-f="zoom"]').textContent = Math.round(WL.scale * 100) + '%';
        WL.applySize();
    }

    function fullscreen() {
        var work = document.getElementById('wl-work'), on = !work.classList.contains('is-full');
        work.classList.toggle('is-full', on);
        set(document, 'fullscreen', on ? 'Keluar Fullscreen (Esc)' : '1 Layar Penuh (Fullscreen)');
        if (on) { WL.toast('📺 Mode Layar Penuh. Tekan Esc untuk keluar.', 'info'); }
    }
    WL.fullscreenOn = function () { return document.getElementById('wl-work').classList.contains('is-full'); };

    /* Search: gudang / rak in the browser, boxes / slots on the server (debounced). */
    function search() {
        var q = this.value.trim().toLowerCase(), list = document.getElementById('wl-search-list');
        clearTimeout(searchTimer);
        if (!q) { list.hidden = true; return; }
        var objects = WL.locations.filter(function (l) { return (l.rack_code + ' ' + (l.room_sector || '')).toLowerCase().indexOf(q) !== -1; }).slice(0, 15);
        render(list, objects, []);
        if (q.length < 2) { return; }
        searchTimer = setTimeout(function () {
            WL.api('GET', WL.cfg.urls.search + '?q=' + encodeURIComponent(q)).then(function (res) { if (res.ok) { render(list, objects, res.data.boxes); } });
        }, 300);
    }

    function render(list, objects, boxes) {
        list.textContent = '';
        var group = function (title, items, label, pick) {
            if (!items.length) { return; }
            var head = document.createElement('div');
            head.className = 'wl-search__head';
            head.textContent = title + ' (' + items.length + ')';
            list.appendChild(head);
            items.forEach(function (item) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'wl-search__item';
                b.textContent = label(item);
                b.addEventListener('click', function () { list.hidden = true; pick(item); });
                list.appendChild(b);
            });
        };
        group('Gudang / Sektor', objects.filter(WL.isRoom), function (l) { return l.rack_code + ' ➔ Layout Berkedip'; }, WL.blink);
        group('Rak Penyimpanan', objects.filter(function (l) { return !WL.isRoom(l); }), function (l) {
            return l.rack_code + ' (' + (l.room_sector || 'Umum') + ' • ' + (l.current_box_count || 0) + '/' + (l.box_capacity || 100) + ')';
        }, WL.blink);
        group('Box / Slot Arsip', boxes, function (b) {
            return b.slot_code + ' • Rak: ' + b.rack_code + ' (LVL ' + b.sap_level + ') ' + (b.archive_id ? (b.box_number ? '[' + b.box_number + '] ' : '') + b.title : 'Slot Kosong Siap Pakai');
        }, function (b) { var rack = WL.byId(b.rack_id); if (rack) { WL.blink(rack); WL.actions.grid(b.slot_code); } });
        list.hidden = !list.children.length;
    }

    function openMenu(at) {
        var hasTarget = !!at.target;
        menu.querySelector('[data-menu="object"]').hidden = !hasTarget;
        menu.querySelector('[data-menu="canvas"]').hidden = hasTarget;
        WL.menuPoint = at.at;
        refresh();
        menu.hidden = false;
        menu.style.left = Math.max(8, Math.min(at.x, window.innerWidth - menu.offsetWidth - 8)) + 'px';
        menu.style.top = Math.max(8, Math.min(at.y, window.innerHeight - menu.offsetHeight - 8)) + 'px';
    }

    /* Colour swatches of the add / edit / batch modals (Laravel 7 colorPresets). */
    WL.paintColors = function (name, current, pick) {
        var box = document.querySelector('[data-colors="' + name + '"]');
        if (!box.children.length) {
            var auto = box.getAttribute('data-auto');
            (auto ? [''] : []).concat(WL.cfg.colors).forEach(function (c) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'wl-color' + (c ? '' : ' wl-color--auto');
                b.setAttribute('data-color', c);
                b.setAttribute('aria-label', c || auto);
                if (c) { b.style.backgroundColor = c; } else { b.textContent = auto; }
                box.appendChild(b);
            });
        }
        var mark = function (value) { Array.prototype.forEach.call(box.children, function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-color') === value)); }); };
        box.onclick = function (e) { var b = e.target.closest('[data-color]'); if (b) { mark(b.getAttribute('data-color')); pick(b.getAttribute('data-color')); } };
        mark(current);
    };

    Object.assign(WL.actions, {
        deselect: function () { WL.select([]); },
        'zoom-in': function () { zoom(WL.scale + 0.1); },
        'zoom-out': function () { zoom(WL.scale - 0.1); },
        'zoom-reset': function () { zoom(1); },
        fullscreen: fullscreen,
        'canvas-size': function () {
            document.getElementById('wl-size-w').value = WL.size.w;
            document.getElementById('wl-size-h').value = WL.size.h;
            window.dmsModal.open('wl-size');
        }
    });

    WL.on('init', function () {
        panel = document.getElementById('wl-panel');
        menu = document.getElementById('wl-menu');
        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-act]');
            if (el && WL.actions[el.getAttribute('data-act')]) { menu.hidden = true; WL.actions[el.getAttribute('data-act')](el); }
            if (!e.target.closest('.wl-search')) { document.getElementById('wl-search-list').hidden = true; }
            if (!e.target.closest('#wl-menu')) { menu.hidden = true; }
        });
        document.getElementById('wl-search').addEventListener('input', search);
        document.getElementById('wl-search').addEventListener('keydown', function (e) {
            var first = document.querySelector('#wl-search-list .wl-search__item');
            if (e.key === 'Enter' && first) { e.preventDefault(); first.click(); }
            if (e.key === 'Escape') { document.getElementById('wl-search-list').hidden = true; }
        });
        document.getElementById('wl-filter').addEventListener('change', function () { WL.filterDept = Number(this.value) || 0; WL.draw(); });
        document.getElementById('wl-size-form').addEventListener('submit', function (e) {
            e.preventDefault();
            WL.size = { w: Number(this.width.value), h: Number(this.height.value) };
            try { localStorage.setItem('wh_canvas_w', WL.size.w); localStorage.setItem('wh_canvas_h', WL.size.h); } catch (err) { /* private mode */ }
            window.dmsModal.close('wl-size');
            WL.applySize();
        });
        each(document.getElementById('wl-size'), '[data-size]', function (b) {
            b.addEventListener('click', function () { var v = b.getAttribute('data-size').split('x'); document.getElementById('wl-size-w').value = v[0]; document.getElementById('wl-size-h').value = v[1]; });
        });
    });
    WL.on('size', function () { set(document, 'canvas-size', WL.size.w + 'x' + WL.size.h + 'px'); });
    WL.on('select', refresh);
    WL.on('geometry', function () {
        var l = WL.current();
        if (l) { set(panel, 'pos', 'X:' + l.canvas_x + ', Y:' + l.canvas_y); set(panel, 'size', l.canvas_width + 'x' + l.canvas_height + 'px'); }
    });
    WL.on('menu', openMenu);
    WL.on('menu-close', function () { menu.hidden = true; });
    WL.on('data', function () {
        var filter = document.getElementById('wl-filter');
        if (filter.options.length > 1) { return; }
        WL.departments.forEach(function (d) { filter.add(new Option(d.code + ' - ' + d.name, d.id)); });
    });
})();
