                    unlockAudio() {
                        if (this.audioUnlocked) return;
                        try {
                            const AudioCtx = window.AudioContext || window.webkitAudioContext;
                            if (AudioCtx) {
                                if (!this.audioContext) {
                                    this.audioContext = new AudioCtx();
                                }
                                if (this.audioContext.state === 'suspended') {
                                    this.audioContext.resume();
                                }
                                this.audioUnlocked = true;
                            }
                        } catch (e) {}
                    },

                    playChime() {
                        if (!this.soundEnabled) return;
                        try {
                            const AudioCtx = window.AudioContext || window.webkitAudioContext;
                            if (!AudioCtx) return;
                            if (!this.audioContext) {
                                this.audioContext = new AudioCtx();
                            }
                            if (this.audioContext.state === 'suspended') {
                                this.audioContext.resume();
                            }

                            const ctx = this.audioContext;
                            const now = ctx.currentTime;

                            // Modern crystal bell notification chime (3 harmonic ascending notes)
                            const notes = [{
                                    freq: 659.25,
                                    time: 0.00,
                                    dur: 0.22,
                                    gain: 0.25
                                }, // E5
                                {
                                    freq: 880.00,
                                    time: 0.10,
                                    dur: 0.32,
                                    gain: 0.30
                                }, // A5
                                {
                                    freq: 1108.73,
                                    time: 0.22,
                                    dur: 0.55,
                                    gain: 0.35
                                } // C#6
                            ];

                            notes.forEach(n => {
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(n.freq, now + n.time);

                                gain.gain.setValueAtTime(0.001, now + n.time);
                                gain.gain.linearRampToValueAtTime(n.gain, now + n.time + 0.02);
                                gain.gain.exponentialRampToValueAtTime(0.0001, now + n.time + n.dur);

                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.start(now + n.time);
                                osc.stop(now + n.time + n.dur);
                            });
                        } catch (err) {
                            console.warn('Audio chime error:', err);
                        }
                    },

                    updateBadges(badges) {
                        if (!badges) return;
                        if (badges.borrowings !== undefined) {
                            const b = this.availableForms.find(f => f.id === 'borrowings');
                            if (b) b.badgeCount = badges.borrowings;
                        }
                        if (badges.submissions !== undefined) {
                            const a = this.availableForms.find(f => f.id === 'archives');
                            if (a) a.badgeCount = badges.submissions;
                        }
                        if (badges.destructions !== undefined) {
                            const d = this.availableForms.find(f => f.id === 'destructions');
                            if (d) d.badgeCount = badges.destructions;
                        }
                    },

                    async initRealtimePoller() {
                        // Initial baseline fetch to get latest ID and sequence without sounding alert
                        try {
                            const res = await fetch('{{ route('api.realtime.check') }}?initial=1');
                            if (res.ok) {
                                const data = await res.json();
                                this.lastArchiveId = data.latest_id || 0;
                                this.lastEventSeq = data.latest_seq || 0;
                                if (data.badges) {
                                    this.updateBadges(data.badges);
                                }
                            }
                        } catch (e) {
                            console.warn('Realtime init poller error:', e);
                        }

                        // Unlock audio on first user interaction
                        const unlockHandler = () => {
                            this.unlockAudio();
                            document.removeEventListener('click', unlockHandler);
                            document.removeEventListener('keydown', unlockHandler);
                        };
                        document.addEventListener('click', unlockHandler);
                        document.addEventListener('keydown', unlockHandler);

                        // Polling every 5 seconds with Page Visibility support
                        const startPolling = () => {
                            if (this.pollTimer) clearInterval(this.pollTimer);
                            this.pollTimer = setInterval(() => this.pollNewArchives(), 5000);
                        };
                        startPolling();

                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden) {
                                if (this.pollTimer) {
                                    clearInterval(this.pollTimer);
                                    this.pollTimer = null;
                                }
                            } else {
                                this.pollNewArchives();
                                startPolling();
                            }
                        });
                    },

                    async pollNewArchives() {
                        if (document.hidden || this.lastArchiveId === undefined || this.lastArchiveId === null) return;
                        try {
                            const res = await fetch(
                                `{{ route('api.realtime.check') }}?last_id=${this.lastArchiveId}&since_seq=${this.lastEventSeq || 0}`
                                );
                            if (!res.ok) return;
                            const data = await res.json();

                            if (data.latest_seq) {
                                this.lastEventSeq = data.latest_seq;
                            }

                            if (data.badges) {
                                this.updateBadges(data.badges);
                            }

                            if (data.has_changes) {
                                window.dispatchEvent(new CustomEvent('dms:realtime-sync', {
                                    detail: data
                                }));
                                document.querySelectorAll('iframe').forEach(ifr => {
                                    try {
                                        if (ifr.contentWindow) {
                                            ifr.contentWindow.dispatchEvent(new CustomEvent('dms:realtime-sync', { detail: data }));
                                            ifr.contentWindow.postMessage({ type: 'dms:realtime-sync', detail: data }, '*');
                                        }
                                    } catch(e) {}
                                });
                            }

                            if (data.events && data.events.length > 0) {
                                data.events.forEach(ev => {
                                    let payload = {};
                                    try {
                                        payload = typeof ev.payload === 'string' ? JSON.parse(ev.payload) : (ev.payload || {});
                                    } catch(e) {}

                                    if (ev.event_type === 'borrowing_created' || ev.event_type === 'borrowing_updated') {
                                        const isCreated = ev.event_type === 'borrowing_created';
                                        
                                        // 1. Never alert the user who initiated or updated this action themselves
                                        if (this.currentUserId && (
                                            (payload.action_by_user_id && Number(payload.action_by_user_id) === Number(this.currentUserId)) ||
                                            (isCreated && payload.borrower_user_id && Number(payload.borrower_user_id) === Number(this.currentUserId))
                                        )) {
                                            return;
                                        }

                                        // 2. Role Scoping: New borrowing requests are meant for PIC Gudang & Admin
                                        if (isCreated && !this.isPicGudangOrAdmin) {
                                            return;
                                        }

                                        const toast = {
                                            id: 'toast_ev_' + ev.id + '_' + Date.now(),
                                            type: 'borrowing',
                                            archiveId: null,
                                            title: isCreated ? 'Permohonan Penarikan Berkas Masuk' : 'Status Penarikan Berkas Berubah',
                                            boxNumber: payload.box_number || 'Box Arsip',
                                            deptCode: payload.dept_code || 'DEPT',
                                            deptName: payload.borrower || 'User',
                                            creatorName: payload.borrower || 'User',
                                            statusLabel: payload.status === 'requested' ? 'Menunggu Approval' : (payload.status === 'approved' ? 'Disetujui / Siap Ambil' : (payload.status === 'dispatched' ? 'Sudah Ditarik' : payload.status)),
                                            time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                                            url: '{{ route("borrowings.index") }}?embed=1'
                                        };
                                        this.activeToasts.unshift(toast);
                                        this.realtimeNotifications.unshift(toast);
                                        this.unreadNotificationsCount++;
                                        this.playChime();
                                        setTimeout(() => this.dismissToast(toast.id), 9000);
                                    }
                                });
                            }

                            if (data.has_new && data.new_archives && data.new_archives.length > 0) {
                                this.lastArchiveId = data.latest_id;

                                // Filter alerts: Never alert on drafts, and do not alert creator on their own submissions
                                const eligibleAlerts = data.new_archives.filter(item => {
                                    if (item.status === 'draft') return false;
                                    if (this.currentUserId && Number(item.created_by_user_id) === Number(this.currentUserId)) {
                                        return false;
                                    }
                                    return true;
                                });

                                if (eligibleAlerts.length > 0) {
                                    this.playChime();

                                    eligibleAlerts.forEach(item => {
                                        const toast = {
                                            id: 'toast_' + item.id + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                                            archiveId: item.id,
                                            title: this.isPicGudangOrAdmin ? 'Pengajuan Box Arsip Masuk' : 'Pengajuan Berkas Baru',
                                            boxNumber: item.box_number,
                                            deptCode: item.dept_code,
                                            deptName: item.dept_name,
                                            creatorName: item.creator_name,
                                            statusLabel: item.status_label || 'Antrean Verifikasi',
                                            time: item.created_at_time || new Date().toLocaleTimeString('id-ID', {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                                second: '2-digit'
                                            }),
                                            url: item.url
                                        };

                                        this.activeToasts.unshift(toast);
                                        this.realtimeNotifications.unshift(toast);
                                        this.unreadNotificationsCount++;

                                        setTimeout(() => {
                                            this.dismissToast(toast.id);
                                        }, 9000);
                                    });
                                }

                                if (this.realtimeNotifications.length > 25) {
                                    this.realtimeNotifications = this.realtimeNotifications.slice(0, 25);
                                }

                                setTimeout(() => lucide.createIcons(), 60);
                            } else if (data.latest_id && data.latest_id > this.lastArchiveId) {
                                this.lastArchiveId = data.latest_id;
                            }
                        } catch (err) {
                            // Silent error during polling
                        }
                    },

                    dismissToast(toastId) {
                        this.activeToasts = this.activeToasts.filter(t => t.id !== toastId);
                    },

                    openArchiveFromNotification(item) {
                        if (!item) return;

                        // 1. Check if this is a borrowing notification
                        const isBorrowing = (item.type === 'borrowing') ||
                            (item.url && item.url.includes('borrowing')) ||
                            (item.title && (item.title.toLowerCase().includes('penarikan') || item.title.toLowerCase().includes('peminjaman'))) ||
                            (item.id && typeof item.id === 'string' && item.id.includes('borrowing'));

                        if (isBorrowing) {
                            this.openFormWindow('borrowings');
                            if (item.id) {
                                this.dismissToast(item.id);
                            }
                            this.showNotificationDropdown = false;
                            return;
                        }

                        // 2. Check if this is a destruction / extension notification
                        const isDestruction = (item.type === 'destruction') ||
                            (item.url && item.url.includes('destruction')) ||
                            (item.title && (item.title.toLowerCase().includes('pemusnahan') || item.title.toLowerCase().includes('perpanjangan')));

                        if (isDestruction) {
                            this.openFormWindow('destructions');
                            if (item.id) {
                                this.dismissToast(item.id);
                            }
                            this.showNotificationDropdown = false;
                            return;
                        }

                        // 3. Specific archive detail (e.g. from archive submission / review)
                        const arcId = item.archiveId || (item.url ? (item.url.match(/\/archives\/(\d+)/) || [])[1] : null);
                        if (arcId) {
                            const detailId = 'archive_detail_' + arcId;
                            const boxLabel = item.boxNumber || item.box_number || ('ID #' + arcId);
                            const title = `[Detail/Verifikasi] ${boxLabel} - ${item.title || 'Dokumen'}`;
                            let url = item.url || ('{{ url('/archives') }}/' + arcId);
                            if (!url.includes('embed=1')) {
                                url += (url.includes('?') ? '&embed=1' : '?embed=1');
                            }

                            this.openFormWindowWithCustom(detailId, title, 'file-text', url);
                            if (item.id) {
                                this.dismissToast(item.id);
                            }
                            this.showNotificationDropdown = false;
                            return;
                        }

                        // 4. Default: Open archive catalog
                        this.openFormWindow('archives');
                        if (item.id) {
                            this.dismissToast(item.id);
                        }
                        this.showNotificationDropdown = false;
                    },

                    toggleSound() {
                        this.soundEnabled = !this.soundEnabled;
                        localStorage.setItem('sound_enabled', this.soundEnabled ? 'true' : 'false');
                        if (this.soundEnabled) {
                            this.unlockAudio();
                            this.playChime();
                        }
                    },
