/* Live Monitor LAN & User (diagnostics/index, admin): the only page that polls /api/health/metrics
   (PROJECT_RULES_LAN_FRONTEND_OPS §4.3). Interval 5/10/30 s or paused, stopped while the tab is hidden.
   Also: RTT of this PC (/api/health/ping), remote IP probe, laravel.log tail, WAL checkpoint. */
document.addEventListener('alpine:init', function () {
    var json = function (url, options) {
        return fetch(url, Object.assign({ headers: { 'Accept': 'application/json' }, cache: 'no-store' }, options || {}))
            .then(function (res) { return res.json().then(function (data) { if (!res.ok) { throw new Error(data.message || ('HTTP ' + res.status)); } return data; }); });
    };
    var csrf = function () { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; };

    window.Alpine.data('liveMonitor', function () {
        return {
            metrics: {}, interval: '10000', timer: null, loading: false, pingRtt: null,
            target: '', probing: false, probe: null,
            statusFilter: 'all', search: '',
            logs: { lines: [], file_exists: false }, logFilter: '', loadingLogs: false,
            checkpointing: false, checkpointMessage: '',
            init: function () {
                var self = this;
                this.refresh();
                this.fetchLogs();
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) { self.stop(); } else { self.refresh(); }
                });
            },
            stop: function () { if (this.timer) { clearTimeout(this.timer); this.timer = null; } },
            schedule: function () {
                var self = this;
                this.stop();
                var ms = parseInt(this.interval, 10);
                if (ms >= 5000 && !document.hidden) { this.timer = setTimeout(function () { self.refresh(); }, ms); }
            },
            refresh: function () {
                var self = this;
                if (this.loading) { return; }
                this.loading = true;
                var started = Date.now();
                fetch('/api/health/ping?_=' + started, { cache: 'no-store', credentials: 'omit' })
                    .then(function () { self.pingRtt = Date.now() - started; })
                    .catch(function () { self.pingRtt = null; })
                    .then(function () { return json('/api/health/metrics'); })
                    .then(function (data) { self.metrics = data; })
                    .catch(function () { /* keep the last values */ })
                    .then(function () { self.loading = false; self.schedule(); });
            },
            get users() {
                var list = (this.metrics.connected_users && this.metrics.connected_users.users) || [];
                var q = this.search.trim().toLowerCase(), status = this.statusFilter;
                return list.filter(function (u) {
                    if (status !== 'all' && u.status !== status) { return false; }
                    if (!q) { return true; }
                    return [u.name, u.email, u.ip, u.department_name, u.department_code, u.browser, u.os, u.last_action]
                        .some(function (v) { return v && String(v).toLowerCase().indexOf(q) !== -1; });
                });
            },
            count: function (key) {
                var s = this.metrics.connected_users && this.metrics.connected_users.summary;
                return s ? s[key] || 0 : 0;
            },
            runProbe: function (ip) {
                var self = this;
                if (ip) { this.target = ip; }
                if (!this.target || this.probing) { return; }
                this.probing = true;
                json('/api/health/probe-ip?target=' + encodeURIComponent(this.target))
                    .then(function (data) { self.probe = data; })
                    .catch(function (e) { self.probe = { is_reachable: false, target_ip: self.target, rtt_ms: null, error: e.message }; })
                    .then(function () { self.probing = false; });
            },
            fetchLogs: function () {
                var self = this;
                this.loadingLogs = true;
                json('/api/health/logs?lines=100').then(function (data) { self.logs = data; })
                    .catch(function () { /* keep */ }).then(function () { self.loadingLogs = false; });
            },
            get filteredLogs() {
                var q = this.logFilter.trim().toLowerCase();
                return (this.logs.lines || []).filter(function (line) { return !q || line.toLowerCase().indexOf(q) !== -1; });
            },
            logTone: function (line) {
                return line.indexOf('.ERROR:') !== -1 ? 'tone--danger' : (line.indexOf('.WARNING:') !== -1 ? 'tone--warning' : '');
            },
            checkpoint: function () {
                var self = this;
                this.checkpointing = true;
                json('/api/health/wal-checkpoint', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() } })
                    .then(function (data) { self.checkpointMessage = data.message; self.refresh(); })
                    .catch(function (e) { self.checkpointMessage = e.message; })
                    .then(function () { self.checkpointing = false; });
            }
        };
    });
});
