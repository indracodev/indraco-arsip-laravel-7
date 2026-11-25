/* Realtime notifications on every app page (Laravel 7 desktop_realtime_scripts / pic_realtime_scripts):
   - polls /api/realtime/check?since_seq= every 15 s ± 2 s, first poll after a random 0-15 s so many
     clients do not poll in step (P3); stops while the tab is hidden, polls at once when it is shown again; the cursor and the
     bell list live in sessionStorage, so moving to another page misses nothing;
   - toasts (9 s) + bell list (max 25) + window.dmsSound.play() for new notifications (the server only
     sends what this user may see, Laravel 7 alert rules applied there);
   - menu badge counts ([data-nav-badge="<route>"]);
   - "dms:realtime-sync" event on window when anything changed (pages may refresh themselves);
   - expiry popup (#expiry-modal) after login and then at most once an hour (Laravel 7 rule);
   - LAN alert toast when the admin enabled it and the LAN dot turns slow/down (lan.js). */
(function () {
    var INTERVAL = 15000, JITTER = 2000, KEEP = 25, HOUR = 3600 * 1000;
    var timer = null, busy = false, stopped = false, prefix = 'dms_notify_';
    var store = {
        get: function (key, fallback) { try { var v = sessionStorage.getItem(prefix + key); return v === null ? fallback : JSON.parse(v); } catch (e) { return fallback; } },
        set: function (key, value) { try { sessionStorage.setItem(prefix + key, JSON.stringify(value)); } catch (e) { /* blocked */ } }
    };
    var el = function (tag, cls, text) { var n = document.createElement(tag); if (cls) { n.className = cls; } if (text !== undefined) { n.textContent = text; } return n; };

    function toastBox() {
        var box = document.querySelector('.toasts');
        if (!box) { box = el('div', 'toasts'); box.setAttribute('aria-live', 'polite'); document.body.appendChild(box); }
        return box;
    }

    function toast(title, lines, tone, href) {
        var t = el('div', 'toast toast--' + (tone || 'info'));
        t.setAttribute('role', 'status');
        var body = el(href ? 'a' : 'div', 'toast__text');
        if (href) { body.href = href; }
        body.appendChild(el('strong', '', title));
        lines.forEach(function (line) { if (line) { body.appendChild(el('span', 'notify__line', line)); } });
        var close = el('button', 'btn btn--ghost btn--sm', '×');
        close.type = 'button';
        close.setAttribute('aria-label', 'Tutup');
        var hide = function () { t.remove(); };
        close.addEventListener('click', hide);
        t.appendChild(body);
        t.appendChild(close);
        toastBox().appendChild(t);
        setTimeout(hide, 9000);
    }

    /* 401/419: the session ended (logout in another tab, password reset, expiry). Stop polling and say so once;
       no auto-reload, that would throw away what the user typed. */
    function sessionEnded(res) {
        if (res.status !== 401 && res.status !== 419) { return false; }
        if (stopped) { return true; }
        stopped = true;
        if (timer) { clearTimeout(timer); timer = null; }
        var t = el('div', 'toast toast--danger');
        t.setAttribute('role', 'alert');
        var body = el('div', 'toast__text');
        body.appendChild(el('strong', '', 'Sesi Anda telah berakhir. Silakan login kembali.'));
        t.appendChild(body);
        var login = el('a', 'btn btn--primary btn--sm', 'Login');
        login.href = '/login';
        t.appendChild(login);
        toastBox().appendChild(t);
        return true;
    }

    function lines(n) { return [[n.dept, n.box].filter(Boolean).join(' • '), 'Oleh: ' + n.by + (n.status ? ' — ' + n.status : '')]; }

    function renderBell() {
        var list = document.querySelector('[data-notify-list]');
        if (!list) { return; }
        var items = store.get('items', []), unread = store.get('unread', 0);
        list.textContent = '';
        items.forEach(function (n) {
            var li = el('li'), a = el('a', 'notify__item');
            a.href = n.url;
            a.appendChild(el('span', 'notify__line text-muted', [n.dept, n.box, n.time].filter(Boolean).join(' • ')));
            a.appendChild(el('strong', '', n.title));
            a.appendChild(el('span', 'notify__line', 'Oleh: ' + n.by));
            li.appendChild(a);
            list.appendChild(li);
        });
        document.querySelector('[data-notify-empty]').hidden = items.length > 0;
        var count = document.querySelector('[data-notify-count]');
        count.textContent = unread > 99 ? '99+' : String(unread);
        count.hidden = unread === 0;
    }

    function renderBadges(badges) {
        document.querySelectorAll('[data-nav-badge]').forEach(function (link) {
            var n = badges[link.getAttribute('data-nav-badge')] || 0;
            if (n > 0) { link.setAttribute('data-count', n > 99 ? '99+' : n); } else { link.removeAttribute('data-count'); }
        });
    }

    function handle(data) {
        store.set('seq', data.latest_seq || 0);
        if (data.badges) { store.set('badges', data.badges); renderBadges(data.badges); }
        if (data.changed) { window.dispatchEvent(new CustomEvent('dms:realtime-sync', { detail: data })); }
        var fresh = data.notifications || [];
        if (!fresh.length) { return; }
        fresh.forEach(function (n) { toast(n.title, lines(n), 'info', n.url); });
        store.set('items', fresh.slice().reverse().concat(store.get('items', [])).slice(0, KEEP));
        store.set('unread', store.get('unread', 0) + fresh.length);
        renderBell();
        if (window.dmsSound) { window.dmsSound.play(); }
    }

    function poll() {
        timer = null;
        if (document.hidden || busy || stopped) { return; }
        busy = true;
        var seq = store.get('seq', null);
        fetch('/api/realtime/check?' + (seq === null ? 'initial=1' : 'since_seq=' + seq), { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(function (res) { if (sessionEnded(res)) { return null; } if (!res.ok) { throw new Error('HTTP ' + res.status); } return res.json(); })
            .then(function (data) { if (data) { handle(data); } })
            .catch(function () { /* offline or logged out: try again later */ })
            .then(function () { busy = false; schedule(); });
    }

    function schedule(delay) {
        if (!timer && !document.hidden && !stopped) { timer = setTimeout(poll, delay === undefined ? INTERVAL - JITTER + Math.random() * 2 * JITTER : delay); }
    }

    /* Expiry popup: shown once per login session, then again after an hour (Laravel 7). */
    function checkExpiry(force) {
        var modal = document.getElementById('expiry-modal');
        if (!modal || !window.dmsModal) { return; }
        var last = 0, shown = false;
        try { last = Number(localStorage.getItem('dms_last_expiry_popup_ts')) || 0; shown = !!sessionStorage.getItem('dms_session_expiry_shown'); } catch (e) { /* blocked */ }
        if (stopped || (!force && shown && Date.now() - last < HOUR)) { return; }
        fetch('/api/archives/expiring-list', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(function (res) { return sessionEnded(res) ? null : (res.ok ? res.json() : { count: 0, items: [] }); })
            .then(function (data) {
                if (!data) { return; }
                try { sessionStorage.setItem('dms_session_expiry_shown', '1'); localStorage.setItem('dms_last_expiry_popup_ts', String(Date.now())); } catch (e) { /* blocked */ }
                if (!data.count || document.body.classList.contains('modal-open')) { return; }
                modal.querySelector('[data-expiry-count]').textContent = data.count + ' Box Jatuh Tempo';
                var list = modal.querySelector('[data-expiry-list]');
                list.textContent = '';
                data.items.forEach(function (item) {
                    var li = el('li', 'expiry-list__item');
                    var info = el('div');
                    info.appendChild(el('span', 'notify__line text-muted', [item.dept_code, item.box_number, item.location].join(' • ')));
                    info.appendChild(el('strong', '', item.title));
                    info.appendChild(el('span', 'notify__line text-sm', 'Exp: ' + item.expiry_date));
                    li.appendChild(info);
                    li.appendChild(el('span', 'badge badge--' + (item.is_expired ? 'danger' : 'warning'), item.badge_text));
                    list.appendChild(li);
                });
                window.dmsModal.open('expiry-modal');
            })
            .catch(function () { /* try again next minute */ });
    }

    /* LAN alert (Pengaturan Sistem > Notifikasi Ping & Latensi LAN): toast when the dot turns slow/down. */
    function watchLan() {
        var lan = document.querySelector('[data-lan][data-alert]'), previous = '';
        if (!lan || !window.MutationObserver) { return; }
        new MutationObserver(function () {
            var state = /lan--(slow|down)/.exec(lan.className);
            var now = state ? state[1] : '';
            if (now && now !== previous) {
                toast(now === 'down' ? 'Koneksi ke server terputus' : 'Koneksi LAN lambat', [lan.querySelector('.lan__text').textContent], now === 'down' ? 'danger' : 'warning');
            }
            previous = now;
        }).observe(lan, { attributes: true, attributeFilter: ['class'] });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var bell = document.querySelector('[data-notify]');
        if (!bell) { return; }
        prefix = 'dms_notify_' + bell.getAttribute('data-notify') + '_';
        var toggle = bell.querySelector('[data-notify-toggle]'), panel = document.getElementById('notify-panel');
        var setOpen = function (open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) { store.set('unread', 0); renderBell(); }
        };
        toggle.addEventListener('click', function () { setOpen(panel.hidden); });
        document.addEventListener('click', function (e) { if (!panel.hidden && !bell.contains(e.target)) { setOpen(false); } });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { setOpen(false); toggle.focus(); } });
        bell.querySelector('[data-notify-test]').addEventListener('click', function () { if (window.dmsSound) { window.dmsSound.play(true); } });

        renderBell();
        renderBadges(store.get('badges', {}));
        watchLan();
        schedule(Math.random() * INTERVAL);
        setTimeout(function () { checkExpiry(false); }, 800);
        setInterval(function () { if (!document.hidden) { checkExpiry(false); } }, 60000);
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { if (timer) { clearTimeout(timer); timer = null; } } else if (!timer) { poll(); }
        });
        window.addEventListener('dms:realtime-sync', function () {
            if (!document.querySelector('[data-realtime-reload]')) { return; }
            if (document.querySelector('.modal:not([hidden])') || document.querySelector('.table__select input:checked')) { return; }
            window.location.reload();
        });
    });
})();
