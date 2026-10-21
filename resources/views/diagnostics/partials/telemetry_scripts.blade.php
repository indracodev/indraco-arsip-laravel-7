<script>
    function serverTelemetryApp() {
        return {
            metrics: {},
            loading: false,
            pingRtt: null,
            refreshInterval: 10000,
            timerId: null,
            lastUsersHash: null,
            currentTheme: localStorage.getItem('theme') || 'light',
            
            // Connected Users state
            userSearchQuery: '',
            userStatusFilter: 'all',

            // WAL Checkpoint state
            runningCheckpoint: false,
            checkpointMessage: '',

            toggleTheme() {
                this.currentTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', this.currentTheme);
                if (this.currentTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            get connectedUsersList() {
                const list = this.metrics.connected_users?.users || [];
                let filtered = list;
                if (this.userStatusFilter !== 'all') {
                    filtered = filtered.filter(u => u.status === this.userStatusFilter);
                }
                if (this.userSearchQuery && this.userSearchQuery.trim()) {
                    const q = this.userSearchQuery.toLowerCase();
                    filtered = filtered.filter(u => 
                        (u.name && u.name.toLowerCase().includes(q)) ||
                        (u.email && u.email.toLowerCase().includes(q)) ||
                        (u.ip && u.ip.includes(q)) ||
                        (u.department_name && u.department_name.toLowerCase().includes(q)) ||
                        (u.role_label && u.role_label.toLowerCase().includes(q)) ||
                        (u.device && u.device.toLowerCase().includes(q)) ||
                        (u.browser && u.browser.toLowerCase().includes(q)) ||
                        (u.last_action && u.last_action.toLowerCase().includes(q))
                    );
                }
                return filtered;
            },

            quickPingUser(ip) {
                if (!ip || ip === '-') return;
                this.targetIpInput = ip;
                this.runIpProbe();
                const probeElem = document.getElementById('remoteProbeSection');
                if (probeElem) {
                    probeElem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            },

            // Remote IP Probe state
            targetIpInput: '',
            probing: false,
            probeResult: null,

            // Server Logs state
            serverLogs: { count: 0, lines: [], file_exists: true },
            loadingLogs: false,
            logFilter: '',

            get filteredLogs() {
                if (!this.serverLogs.lines) return [];
                if (!this.logFilter.trim()) return this.serverLogs.lines;
                const q = this.logFilter.toLowerCase();
                return this.serverLogs.lines.filter(l => l.toLowerCase().includes(q));
            },

            init() {
                // Ensure initial theme sync
                if (this.currentTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                this.fetchMetrics();
                this.measurePing();
                this.fetchServerLogs();
                this.updateTimer();

                // Page Visibility: Jeda polling saat tab diminimalkan / tidak aktif di layar
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        if (this.timerId) {
                            clearInterval(this.timerId);
                            this.timerId = null;
                        }
                    } else {
                        this.fetchMetrics();
                        this.measurePing();
                        this.updateTimer();
                    }
                });

                this.$watch('userStatusFilter', () => this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); }));
                this.$watch('userSearchQuery', () => this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); }));
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            updateTimer() {
                if (this.timerId) {
                    clearInterval(this.timerId);
                    this.timerId = null;
                }
                const interval = parseInt(this.refreshInterval, 10);
                if (interval > 0) {
                    this.timerId = setInterval(() => {
                        this.fetchMetrics();
                        this.measurePing();
                    }, interval);
                }
            },

            async measurePing() {
                if (document.hidden) return;
                const t0 = performance.now();
                try {
                    const res = await fetch('{{ route("api.health.ping") }}?_t=' + Date.now(), { cache: 'no-store' });
                    if (res.ok) {
                        this.pingRtt = Math.round(performance.now() - t0);
                    }
                } catch (e) {
                    this.pingRtt = null;
                }
            },

            async fetchMetrics() {
                if (document.hidden) return;
                this.loading = true;
                try {
                    const res = await fetch('{{ route("api.health.metrics") }}?_t=' + Date.now(), { cache: 'no-store' });
                    if (res.ok) {
                        const newMetrics = await res.json();
                        
                        // Smart Diffing: Cek apakah data user ada perubahan riil
                        const incomingHash = newMetrics.connected_users?.content_hash || 
                            JSON.stringify(newMetrics.connected_users?.users?.map(u => ({ id: u.id, s: u.status, ip: u.ip, a: u.last_action, d: u.device })));

                        if (!this.lastUsersHash || this.lastUsersHash !== incomingHash) {
                            // Ada user baru / status berubah: Perbarui state & render ulang icon
                            this.lastUsersHash = incomingHash;
                            this.metrics = newMetrics;
                            this.$nextTick(() => {
                                if (window.lucide) window.lucide.createIcons();
                            });
                        } else {
                            // Data user SAMA PERSIS: Jangan re-assign array user agar DOM tabel tidak di-render ulang!
                            if (this.metrics.system) this.metrics.system = newMetrics.system;
                            if (this.metrics.memory) this.metrics.memory = newMetrics.memory;
                            if (this.metrics.diagnosis) this.metrics.diagnosis = newMetrics.diagnosis;
                            if (this.metrics.database) this.metrics.database = newMetrics.database;
                            if (this.metrics.connected_users) {
                                this.metrics.connected_users.summary = newMetrics.connected_users.summary;
                                // Patch relative time in-place pada user objects yang sudah ada tanpa mutasi array
                                if (newMetrics.connected_users.users && this.metrics.connected_users.users) {
                                    const newMap = new Map(newMetrics.connected_users.users.map(u => [u.id, u]));
                                    for (const u of this.metrics.connected_users.users) {
                                        const fresh = newMap.get(u.id);
                                        if (fresh) {
                                            u.last_seen_relative = fresh.last_seen_relative;
                                            u.last_seen_time = fresh.last_seen_time;
                                        }
                                    }
                                }
                            }
                        }

                        if (!this.targetIpInput && this.metrics.client && this.metrics.client.ip) {
                            this.targetIpInput = this.metrics.client.ip;
                        }
                    }
                } catch (e) {
                    console.error('Failed fetching metrics:', e);
                } finally {
                    this.loading = false;
                }
            },

            async fetchServerLogs() {
                this.loadingLogs = true;
                try {
                    const res = await fetch('{{ route("api.health.logs") }}?lines=80&_t=' + Date.now(), { cache: 'no-store' });
                    if (res.ok) {
                        this.serverLogs = await res.json();
                    }
                } catch (e) {
                    console.error('Failed fetching server logs:', e);
                } finally {
                    this.loadingLogs = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                }
            },

            async runIpProbe() {
                if (!this.targetIpInput) return;
                this.probing = true;
                this.probeResult = null;
                try {
                    const url = '{{ route("api.health.probe_ip") }}?target=' + encodeURIComponent(this.targetIpInput.trim());
                    const res = await fetch(url, { cache: 'no-store' });
                    this.probeResult = await res.json();
                } catch (e) {
                    this.probeResult = {
                        status: 'error',
                        target_ip: this.targetIpInput,
                        is_reachable: false,
                        rtt_ms: null,
                        server_ip: this.metrics.server?.ip || 'Server'
                    };
                } finally {
                    this.probing = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                }
            },

            copyDiagnosticToken() {
                const token = this.metrics.diagnostic_key;
                if (!token) return;
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(token).then(() => {
                        alert('Token Diagnostik berhasil disalin ke clipboard:\n' + token);
                    });
                } else {
                    prompt('Salin token berikut:', token);
                }
            },

            async runWalCheckpoint() {
                if (this.runningCheckpoint) return;
                this.runningCheckpoint = true;
                this.checkpointMessage = '';
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch('{{ route("api.health.wal_checkpoint") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token || '',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (res.ok && data.status === 'success') {
                        this.checkpointMessage = '✓ ' + (data.message || 'WAL Checkpoint selesai') + ' (' + (data.wal_size_after_kb || 0) + ' KB)';
                        await this.fetchMetrics();
                    } else {
                        this.checkpointMessage = '⚠ Gagal: ' + (data.message || 'Error saat checkpoint');
                    }
                } catch (e) {
                    this.checkpointMessage = '⚠ Terjadi kesalahan koneksi';
                } finally {
                    this.runningCheckpoint = false;
                    setTimeout(() => {
                        this.checkpointMessage = '';
                    }, 6000);
                }
            }
        }
    }
    </script>
