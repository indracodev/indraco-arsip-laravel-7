/* LAN status dot in the top bar: GET /api/health/ping (answered by Caddy, no PHP/DB) every 5 s.
   Polling stops while the tab is hidden (visibilitychange) and resumes with an immediate ping. */
(function () {
    var INTERVAL = 5000;
    var TIMEOUT = 4000;
    var el = null;
    var timer = null;
    var busy = false;

    function show(state, text) {
        el.className = 'lan lan--' + state;
        el.querySelector('.lan__text').textContent = text;
        el.title = 'Koneksi server: ' + text;
    }

    function ping() {
        timer = null;
        if (document.hidden || busy) { return; }
        busy = true;
        var started = Date.now();
        var controller = window.AbortController ? new AbortController() : null;
        var abort = controller ? setTimeout(function () { controller.abort(); }, TIMEOUT) : null;
        fetch('/api/health/ping?_=' + started, { cache: 'no-store', credentials: 'omit', signal: controller ? controller.signal : undefined })
            .then(function (response) {
                if (!response.ok) { throw new Error('HTTP ' + response.status); }
                var ms = Date.now() - started;
                var threshold = parseInt(el.getAttribute('data-threshold'), 10) || 500;
                show(ms > threshold ? 'slow' : 'ok', 'LAN ' + ms + ' ms');
            })
            .catch(function () { show('down', 'Terputus'); })
            .then(function () {
                if (abort) { clearTimeout(abort); }
                busy = false;
                schedule();
            });
    }

    function schedule() {
        if (!timer && !document.hidden) { timer = setTimeout(ping, INTERVAL); }
    }

    document.addEventListener('DOMContentLoaded', function () {
        el = document.querySelector('[data-lan]');
        if (!el) { return; }
        ping();
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                if (timer) { clearTimeout(timer); timer = null; }
            } else if (!timer) {
                ping();
            }
        });
    });
})();
