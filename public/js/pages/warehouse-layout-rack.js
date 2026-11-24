/* Layout Gudang 2D rack grid (Laravel 7 rack_visualizer_modal + canvas_slots): the 100 slots of ONE
   rack are fetched and built when the grid opens (5 LVL, top row 10 + bottom row 10), filter /
   search only toggle a class, the inspector shows the box (detail, label, release, Super Admin slot
   switch) or places a queued box (approve first when it still waits for verification). */
(function () {
    var WL = window.WL;
    var rack = null, slots = {}, queue = [], picked = null, filter = 'all', modal;
    var FAT = ['FAT', 'FIN', 'FATCLM'];

    function $(name) { return modal.querySelector('[data-f="' + name + '"]'); }
    function part(name, on) { Array.prototype.forEach.call(modal.querySelectorAll('[data-slot="' + name + '"]'), function (el) { el.hidden = !on; }); }
    function el(tag, cls, text) { var n = document.createElement(tag); if (cls) { n.className = cls; } if (text !== undefined) { n.textContent = text; } return n; }

    function open(slotCode) {
        rack = WL.current();
        if (!rack || WL.isRoom(rack)) { return; }
        picked = null;
        filter = 'all';
        document.getElementById('wl-rack-title').textContent = 'Denah Rak: ' + rack.rack_code + ' · 100 Box (TB 30g)';
        $('rack-sector').textContent = 'Sektor: ' + (rack.room_sector || 'Umum');
        $('rack-alloc').textContent = WL.allowed(rack).length ? 'Khusus: ' + WL.allowed(rack).map(WL.deptCode).join(', ') : 'Alokasi: Umum (Semua Departemen)';
        document.getElementById('wl-slot-search').value = '';
        document.getElementById('wl-levels').textContent = 'Memuat slot rak...';
        inspect();
        window.dmsModal.open('wl-rack');
        load(slotCode);
    }

    function load(slotCode) {
        return WL.api('GET', WL.url(rack.id, 'slots')).then(function (res) {
            if (!res.ok) { document.getElementById('wl-levels').textContent = WL.error(res.data, 'Gagal memuat slot rak.'); return; }
            slots = {};
            res.data.slots.forEach(function (s) { slots[s.slot_number] = s; });
            queue = res.data.queue;
            build();
            var hit = res.data.slots.filter(function (s) { return s.slot_code === slotCode || (picked && s.id === picked.id); })[0];
            if (hit) { choose(hit); }
        });
    }

    function build() {
        var box = document.getElementById('wl-levels'), frag = document.createDocumentFragment(), count = { empty: 0, filled: 0, expired: 0 };
        for (var level = 5; level >= 1; level--) {
            var block = el('div', 'wl-level'), first = (level - 1) * 20;
            var head = el('div', 'wl-level__head');
            head.appendChild(el('strong', '', 'LVL ' + level));
            head.appendChild(el('span', '', '20 Box (Nomor ' + (first + 1) + ' — ' + (first + 20) + ')'));
            block.appendChild(head);
            [['Baris Atas (10 Box TB 30g)', first + 11], ['Baris Bawah (10 Box TB 30g)', first + 1]].forEach(function (row) {
                block.appendChild(el('div', 'wl-level__head', row[0] + ' · ' + row[1] + ' — ' + (row[1] + 9)));
                var grid = el('div', 'wl-level__row');
                for (var n = row[1]; n < row[1] + 10; n++) { grid.appendChild(cell(n, slots[n], count)); }
                block.appendChild(grid);
            });
            frag.appendChild(block);
        }
        box.textContent = '';
        box.appendChild(frag);
        $('rk-empty').textContent = count.empty;
        $('rk-filled').textContent = count.filled;
        $('rk-expired').textContent = count.expired;
        dim();
    }

    function cell(n, s, count) {
        var status = s ? s.status : 'empty', b = el('button', 'wl-cell wl-cell--' + status);
        b.type = 'button';
        b.disabled = !s;
        if (s) { b.setAttribute('data-id', s.id); }
        b.appendChild(el('b', '', '#' + n));
        b.appendChild(el('span', '', s && s.archive ? 'TERISI' : status === 'inactive' ? 'Non-Aktif' : 'Kosong'));
        if (s && s.archive) { b.appendChild(el('span', '', s.archive.retention || s.archive.periode || s.archive.department)); }
        b.title = 'Box #' + n + ': ' + (s && s.archive ? (s.archive.box_number || 'Box') + ' - ' + s.archive.title : 'Slot Kosong');
        var key = { expired: 'expired', filled: 'filled', near_exp: 'filled', empty: 'empty' }[status];
        if (key) { count[key]++; }
        return b;
    }

    function matches(s) {
        var q = document.getElementById('wl-slot-search').value.trim().toLowerCase();
        var okStatus = filter === 'all' || (filter === 'filled' ? s.status === 'filled' || s.status === 'near_exp' : s.status === filter);
        var text = (s.slot_code + ' ' + (s.archive ? s.archive.box_number + ' ' + s.archive.title : '')).toLowerCase();
        return okStatus && (!q || text.indexOf(q) !== -1);
    }

    function dim() {
        Array.prototype.forEach.call(document.querySelectorAll('#wl-levels .wl-cell[data-id]'), function (b) {
            var s = byId(b.getAttribute('data-id'));
            b.classList.toggle('is-dim', !matches(s));
            b.setAttribute('aria-pressed', String(!!picked && picked.id === s.id));
        });
    }

    function byId(id) { for (var k in slots) { if (slots[k].id === Number(id)) { return slots[k]; } } return null; }

    function choose(s) { picked = s; dim(); inspect(); }

    function inspect() {
        var s = picked, a = s && s.archive;
        part('none', !s);
        part('any', !!s);
        if (!s) { return; }
        $('slot-where').textContent = 'Sap ' + s.sap_level + ' • ' + (s.layer === 'top' ? 'Baris Atas' : 'Baris Bawah');
        $('slot-title').textContent = 'SLOT BOX #' + s.slot_number + ' (' + s.slot_code + ')';
        var badge = { expired: ['EXPIRED', 'danger'], near_exp: ['MENDEKATI EXP', 'warning'], filled: ['TERISI', 'warning'], inactive: ['NON-AKTIF', 'outline'], empty: ['KOSONG', 'success'] }[s.status];
        $('slot-badge').textContent = badge[0];
        $('slot-badge').className = 'badge badge--' + badge[1];
        part('filled', !!a);
        part('expired', s.status === 'expired');
        part('empty', !a && s.is_active);
        var toggle = $('toggle-label');
        if (toggle) { toggle.textContent = s.is_active ? 'Non-aktifkan Slot (Inactive)' : 'Aktifkan Slot (Active)'; }
        if (a) {
            $('slot-box').textContent = a.box_number || '-';
            $('slot-doc').textContent = a.title;
            $('slot-period').textContent = a.periode || '-';
            $('slot-dept').textContent = a.department + (a.department_name ? ' - ' + a.department_name : '');
            part('sub', !!a.sub_department);
            $('slot-sub').textContent = a.sub_department || '';
            $('slot-expiry').textContent = a.expiry + (a.retention ? ' (' + a.retention + ')' : '');
            $('slot-link').href = WL.cfg.urls.archive + '/' + a.id + '?from=layout';
            $('slot-label').href = WL.cfg.urls.archive + '/' + a.id + '/print-sticker';
            $('slot-label').hidden = !a.can_label;
            return;
        }
        fillQueue();
    }

    /* Laravel 7 getFilteredUnassignedArchives: the rack's departments, FAT rooms only FAT boxes. */
    function fillQueue() {
        var allowed = WL.allowed(rack), select = document.getElementById('wl-queue');
        var list = queue.filter(function (q) {
            return (!allowed.length || allowed.indexOf(q.department_id) !== -1) && (!rack.is_fat_locked || allowed.length || FAT.indexOf(String(q.department_code).toUpperCase()) !== -1);
        });
        select.length = 1;
        list.forEach(function (q) { select.add(new Option((q.is_approved ? '✅ [Disetujui] ' : '⏳ [Menunggu ACC] ') + '[' + q.department_code + '] ' + (q.box_number ? q.box_number + ' - ' : '') + q.title, q.id)); });
        $('queue-empty').hidden = list.length > 0;
        $('slot-only').hidden = !allowed.length;
        $('slot-only').textContent = 'Penempatan Dikhususkan untuk: ' + allowed.map(WL.deptCode).join(', ');
        queued();
    }

    function queued() {
        var id = Number(document.getElementById('wl-queue').value), q = queue.filter(function (x) { return x.id === id; })[0], info = $('queue-info');
        info.hidden = !q;
        modal.querySelector('[data-act="slot-assign"]').disabled = !q;
        $('assign-label').textContent = q && !q.is_approved ? 'Verifikasi & Setujui (Approve)' : 'Alokasikan Box ke Slot';
        if (q) { info.textContent = (q.is_approved ? '✅ Sudah Disetujui (Siap Alokasi)' : '⏳ Antrean Verifikasi (Belum ACC)') + ' · ' + q.department_code + ' · ' + q.title + ' · Pengaju: ' + q.creator + ' | Periode: ' + q.periode + (q.box_number ? ' | Nomor Box: ' + q.box_number : ''); }
    }

    function act(path, body, confirm) {
        return window.showConfirmModal(confirm).then(function (ok) {
            if (!ok) { return; }
            return WL.send('POST', WL.url(rack.id, path), body).then(function (res) {
                if (!res.ok) { return; }
                WL.toast('✅ ' + res.data.message);
                load();
                WL.load();
            });
        });
    }

    Object.assign(WL.actions, {
        grid: function (slotCode) { open(typeof slotCode === 'string' ? slotCode : null); },
        'slot-release': function () {
            act('slots/unassign', { slot_id: picked.id }, { title: 'Kosongkan Slot Rak', type: 'danger', confirmText: 'Ya, Kosongkan', message: 'Apakah Anda yakin ingin mengosongkan slot ' + picked.slot_code + '? Box arsip akan dilepaskan dari slot rak ini.' });
        },
        'slot-toggle': function () {
            act('slots/toggle-active', { slot_id: picked.id }, { title: 'Ubah Status Slot', type: 'warning', confirmText: 'Ya, Ubah', message: 'Ubah status aktif slot ' + picked.slot_code + '?' });
        },
        'slot-assign': function () {
            var id = Number(document.getElementById('wl-queue').value), q = queue.filter(function (x) { return x.id === id; })[0];
            if (!q) { return; }
            var box = '[' + q.department_code + '] ' + (q.box_number || 'Box #' + q.id) + ' - ' + q.title;
            act('slots/assign', { slot_id: picked.id, archive_id: q.id }, { title: q.is_approved ? 'Verifikasi Fisik & Penempatan' : 'Verifikasi & Setujui Pengajuan', type: 'warning', confirmText: q.is_approved ? 'Ya, Tempatkan' : 'Ya, Setujui & Tempatkan',
                message: (q.is_approved ? '' : 'Pengajuan ini akan DISETUJUI (ACC, nomor box dibuat) lalu ditempatkan.\n\n') + 'VERIFIKASI FISIK & CHECKLIST PENEMPATAN:\n1. Kondisi fisik wadah box baik dan tersegel?\n2. Berkas sesuai dokumen pengajuan?\n\nTempatkan box ' + box + ' ke Slot ' + picked.slot_code + ' pada Rak ' + rack.rack_code + '?' });
        }
    });

    WL.on('init', function () {
        modal = document.getElementById('wl-rack');
        document.getElementById('wl-levels').addEventListener('click', function (e) { var b = e.target.closest('.wl-cell[data-id]'); if (b) { choose(byId(b.getAttribute('data-id'))); } });
        document.getElementById('wl-slot-search').addEventListener('input', dim);
        document.getElementById('wl-queue').addEventListener('change', queued);
        Array.prototype.forEach.call(modal.querySelectorAll('[data-filter]'), function (b) {
            b.addEventListener('click', function () {
                filter = b.getAttribute('data-filter');
                Array.prototype.forEach.call(modal.querySelectorAll('[data-filter]'), function (x) { x.setAttribute('aria-pressed', String(x === b)); });
                dim();
            });
        });
    });
})();
