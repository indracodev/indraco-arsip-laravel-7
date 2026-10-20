    <script>
        function desktopPicAppLayout() {
            return {
                theme: localStorage.getItem('theme') || 'light',
                openWindows: [],
                activeWinId: null,
                maxZIndex: 10,
                activeDragWin: null,
                isFullscreen: false,
                cpuUsage: 12,
                memUsage: 38,

                // Settings & Appearance State
                openSettingsModal: false,
                appLogoUrl: @json($globalAppLogo ?? asset('images/logo_indraco.png')),
                currentFontSize: localStorage.getItem('app_font_size') || '{{ $globalAppFontSize ?? "19px" }}',
                tempFontSize: localStorage.getItem('app_font_size') || '{{ $globalAppFontSize ?? "19px" }}',
                pingAlertEnabled: @json($globalPingAlertEnabled ?? false),
                pingAlertThresholdMs: {{ (int) ($globalPingAlertThresholdMs ?? 500) }},
                newLogoFile: null,
                newLogoPreview: null,
                settingsSaving: false,
                maintenanceLoading: false,

                // Real-time Notification & Audio State
                currentUserId: {{ auth()->id() ?? 0 }},
                isPicGudangOrAdmin: {{ (auth()->check() && (auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())) ? 'true' : 'false' }},
                soundEnabled: localStorage.getItem('sound_enabled') !== 'false',
                lastArchiveId: 0,
                lastEventSeq: 0,
                realtimeNotifications: [],
                activeToasts: [],
                unreadNotificationsCount: 0,
                showNotificationDropdown: false,
                audioContext: null,
                audioUnlocked: false,
                pollTimer: null,

                @php
                    $uPic = auth()->user();
                    $uPicDeptId = $uPic ? $uPic->department_id : 0;
                    $initialBorrowBadgePic = (int) \App\Models\BorrowingLog::whereHas('archive', function($q) use ($uPicDeptId) {
                        $q->where('department_id', $uPicDeptId);
                    })->whereIn('status', ['requested', 'approved'])->count();
                    $initialArchiveBadgePic = (int) \App\Models\Archive::where('department_id', $uPicDeptId)->where('status', 'pending_verification')->count();
                @endphp
                availableForms: [
                    { 
                        id: 'archives', 
                        title: '[Form 1] Katalog Arsip {{ auth()->user()->department->code ?? "" }}', 
                        icon: 'folder-archive', 
                        badgeCount: {{ $initialArchiveBadgePic }},
                        url: '{{ route("archives.index") }}?embed=1' 
                    },
                    { 
                        id: 'borrowings', 
                        title: '[Form 2] Penarikan Berkas', 
                        icon: 'file-symlink', 
                        badgeCount: {{ $initialBorrowBadgePic }},
                        url: '{{ route("borrowings.index") }}?embed=1' 
                    },
                    { 
                        id: 'destructions', 
                        title: '[Form 3] Pengajuan Perpanjangan & Pemusnahan', 
                        icon: 'shield-alert', 
                        badgeCount: {{ (int) $form3BadgeCount }},
                        url: '{{ route("destructions.index") }}?embed=1' 
                    },
                    { 
                        id: 'archives_create', 
                        title: 'Draft Pengajuan Box Baru', 
                        icon: 'plus-circle', 
                        url: '{{ route("archives.create") }}?embed=1',
                        hideInTabBar: true
                    }
                ],

                initMdi() {
                    let initialId = 'archives';
                    @if(request()->routeIs('archives.create')) initialId = 'archives_create';
                    @elseif(request()->routeIs('borrowings.*')) initialId = 'borrowings';
                    @elseif(request()->routeIs('destructions.*')) initialId = 'destructions';
                    @elseif(request()->routeIs('archives.*')) initialId = 'archives';
                    @endif

                    // Apply stored font size on startup
                    if (this.currentFontSize) {
                        this.applyFontSizeLive(this.currentFontSize);
                    }

                    // Restore previous MDI window state across refresh (F5/Ctrl+R/Ctrl+Shift+R) and reconnect
                    let restored = false;
                    try {
                        const raw = sessionStorage.getItem('dms_pic_mdi_state') || localStorage.getItem('dms_pic_mdi_state');
                        if (raw) {
                            const saved = JSON.parse(raw);
                            if (saved && Array.isArray(saved.openWindows) && saved.openWindows.length > 0) {
                                this.openWindows = saved.openWindows.map(w => ({
                                    ...w,
                                    loading: true,
                                    isDragging: false,
                                    startX: 0,
                                    startY: 0
                                }));
                                this.activeWinId = saved.activeWinId || (this.openWindows[0] ? this.openWindows[0].id : null);
                                this.maxZIndex = Math.max(...this.openWindows.map(w => w.zIndex || 10), 10);
                                restored = true;
                            }
                        }
                    } catch (e) {}

                    if (restored) {
                        @if(!request()->is('/') && !request()->routeIs('archives.index'))
                        if (!this.openWindows.some(w => w.id === initialId)) {
                            this.openFormWindow(initialId);
                        } else {
                            this.focusWindow(initialId);
                        }
                        @endif
                    } else {
                        this.openFormWindow(initialId);
                    }

                    // Watch changes and persist MDI state reactively
                    this.$watch('openWindows', () => this.saveMdiState());
                    this.$watch('activeWinId', () => this.saveMdiState());
                    window.addEventListener('beforeunload', () => this.saveMdiState());

                    this.startSystemMonitor();
                    this.initRealtimePoller();

                    window.addEventListener('open-form-window', (e) => {
                        if (e.detail) {
                            this.openFormWindowWithCustom(e.detail.id, e.detail.title, e.detail.icon, e.detail.url);
                        }
                    });

                    // Listen to cross-frame toast notifications and badge updates from forms in iframes
                    window.addEventListener('message', (event) => {
                        if (event.data && event.data.type === 'DMS_HARD_REFRESH') {
                            this.saveMdiState();
                            window.location.reload();
                            return;
                        }
                        if (event.data && event.data.type === 'UPDATE_FORM_BADGE') {
                            const target = this.availableForms.find(f => f.id === event.data.formId);
                            if (target) {
                                target.badgeCount = parseInt(event.data.count) || 0;
                            }
                            return;
                        }
                        if (event.data && event.data.type === 'DMS_TOAST_NOTIF') {
                            const d = event.data;
                            const toastId = 'toast_msg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4);
                            const toast = {
                                id: toastId,
                                headerTitle: d.title || 'BERHASIL DISIMPAN!',
                                type: d.level || 'success',
                                title: d.message,
                                boxNumber: d.boxNumber || '',
                                deptCode: {!! json_encode(auth()->user()->department->code ?? 'DMS') !!},
                                deptName: {!! json_encode(auth()->user()->department->name ?? '') !!},
                                creatorName: {!! json_encode(auth()->user()->name ?? '') !!},
                                statusLabel: d.statusLabel || 'Tersimpan',
                                time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                                url: d.url || null
                            };
                            this.activeToasts.unshift(toast);
                            this.realtimeNotifications.unshift(toast);
                            this.unreadNotificationsCount++;
                            this.unlockAudio();
                            this.playChime();
                            setTimeout(() => this.dismissToast(toastId), 9000);
                            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                        }
                    });

                    // F9 Shortcut to open Settings
                    window.addEventListener('keydown', (e) => {
                        if (e.key === 'F9') {
                            e.preventDefault();
                            this.openSettings();
                        }
                    });

                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                        if (this.isFullscreen) {
                            sessionStorage.setItem('app_fullscreen', 'true');
                        }
                    });

                    this.checkFullscreenPersistence();
                },

                @include('layouts.partials.pic.pic_realtime_scripts')

                startSystemMonitor() {
                    this.updateStats();
                    setInterval(() => {
                        if (!document.hidden) {
                            this.updateStats();
                        }
                    }, 30000);
                },

                updateStats() {
                    if (window.performance && window.performance.memory) {
                        const mem = window.performance.memory;
                        const usedPct = Math.round((mem.usedJSHeapSize / mem.jsHeapSizeLimit) * 100);
                        this.memUsage = Math.min(Math.max(usedPct + 24, 28), 75);
                    } else {
                        this.memUsage = Math.floor(Math.random() * 10) + 34;
                    }
                    this.cpuUsage = Math.floor(Math.random() * 14) + 6;
                },

                checkFullscreenPersistence() {
                    if (sessionStorage.getItem('app_fullscreen') === 'true') {
                        const attemptFullscreen = () => {
                            if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
                                document.documentElement.requestFullscreen().then(() => {
                                    this.isFullscreen = true;
                                }).catch(() => {});
                            }
                        };
                        attemptFullscreen();
                        const autoRestore = () => {
                            attemptFullscreen();
                            document.removeEventListener('click', autoRestore);
                            document.removeEventListener('keydown', autoRestore);
                        };
                        document.addEventListener('click', autoRestore);
                        document.addEventListener('keydown', autoRestore);
                    }
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        if (document.documentElement.requestFullscreen) {
                            document.documentElement.requestFullscreen().then(() => {
                                this.isFullscreen = true;
                                sessionStorage.setItem('app_fullscreen', 'true');
                            }).catch(() => {
                                sessionStorage.setItem('app_fullscreen', 'true');
                            });
                        }
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen().then(() => {
                                this.isFullscreen = false;
                                sessionStorage.setItem('app_fullscreen', 'false');
                            }).catch(() => {
                                sessionStorage.setItem('app_fullscreen', 'false');
                            });
                        }
                    }
                },

                isTabActive(formId) {
                    if (this.activeWinId === formId) return true;
                    if (formId === 'archives' && this.activeWinId && (this.activeWinId.startsWith('archive_detail_') || this.activeWinId === 'archives_create')) return true;
                    if (formId === 'borrowings' && this.activeWinId && (this.activeWinId.startsWith('borrowing_') || this.activeWinId.includes('borrowing'))) return true;
                    if (formId === 'destructions' && this.activeWinId && (this.activeWinId.startsWith('destruction_') || this.activeWinId.includes('destruction'))) return true;
                    return false;
                },

                isWindowOpen(formId) {
                    return this.openWindows.some(w => w.id === formId);
                },

                openFormWindow(formId) {
                    let win = this.openWindows.find(w => w.id === formId);
                    if (win) {
                        win.minimized = false;
                        this.focusWindow(win.id);
                        const ifr = document.getElementById('iframe-' + formId);
                        if (ifr && ifr.contentWindow) {
                            try {
                                ifr.contentWindow.location.reload();
                            } catch(e) {}
                        }
                    } else {
                        const form = this.availableForms.find(f => f.id === formId);
                        if (!form) return;
                        
                        const count = this.openWindows.length;
                        const offsetX = (count * 30) % 180;
                        const offsetY = (count * 25) % 120;

                        win = {
                            id: form.id,
                            title: form.title,
                            icon: form.icon,
                            url: form.url,
                            loading: true,
                            posX: offsetX,
                            posY: offsetY,
                            zIndex: ++this.maxZIndex,
                            maximized: false,
                            minimized: false,
                            isDragging: false,
                            startX: 0,
                            startY: 0
                        };
                        this.openWindows.push(win);
                        this.activeWinId = win.id;
                    }
                    setTimeout(() => lucide.createIcons(), 50);
                },

                openFormWindowWithCustom(id, title, icon, url) {
                    let win = this.openWindows.find(w => w.id === id);
                    if (win) {
                        win.minimized = false;
                        if (win.url !== url) {
                            win.loading = true;
                            win.url = url;
                        }
                        this.focusWindow(win.id);
                    } else {
                        const count = this.openWindows.length;
                        const offsetX = (count * 30) % 180;
                        const offsetY = (count * 25) % 120;

                        win = {
                            id: id,
                            title: title,
                            icon: icon || 'folder-open',
                            url: url,
                            loading: true,
                            posX: offsetX,
                            posY: offsetY,
                            zIndex: ++this.maxZIndex,
                            maximized: false,
                            minimized: false,
                            isDragging: false,
                            startX: 0,
                            startY: 0
                        };
                        this.openWindows.push(win);
                        this.activeWinId = win.id;
                    }
                    setTimeout(() => lucide.createIcons(), 50);
                },

                async closeWindow(formId) {
                    const win = this.openWindows.find(w => w.id === formId);
                    
                    // Konfirmasi pengamanan agar user tidak kehilangan form input atau salah klik tombol close window saat modal cetak terbuka
                    if (window.showConfirmModal) {
                        const isCriticalForm = !formId.includes('dashboard') && !formId.includes('diagnostics') && !formId.includes('logs');
                        if (isCriticalForm) {
                            const ok = await window.showConfirmModal({
                                title: 'Tutup Jendela Halaman?',
                                message: `PERINGATAN: Apakah Anda yakin ingin menutup jendela "${win ? win.title : 'Halaman Ini'}"?\nJika Anda sedang mengisi form pengajuan atau membuka pratinjau cetak, data yang belum disimpan ke sistem akan hilang.`,
                                type: 'warning',
                                confirmText: 'Ya, Tutup Jendela',
                                cancelText: 'Batal (Tetap Buka)'
                            });
                            if (!ok) return;
                        }
                    }

                    this.openWindows = this.openWindows.filter(w => w.id !== formId);
                    if (this.activeWinId === formId) {
                        const remaining = this.openWindows.filter(w => !w.minimized);
                        if (remaining.length > 0) {
                            this.focusWindow(remaining[remaining.length - 1].id);
                        } else {
                            this.activeWinId = null;
                        }
                    }
                },

                focusWindow(formId) {
                    const win = this.openWindows.find(w => w.id === formId);
                    if (win) {
                        this.maxZIndex++;
                        win.zIndex = this.maxZIndex;
                        this.activeWinId = win.id;
                        if (win.minimized) win.minimized = false;
                    }
                },

                saveMdiState() {
                    try {
                        if (!Array.isArray(this.openWindows)) return;
                        const state = {
                            openWindows: this.openWindows.map(w => ({
                                id: w.id,
                                title: w.title,
                                icon: w.icon,
                                url: w.url,
                                posX: w.posX || 0,
                                posY: w.posY || 0,
                                zIndex: w.zIndex || 10,
                                maximized: !!w.maximized,
                                minimized: !!w.minimized
                            })),
                            activeWinId: this.activeWinId,
                            timestamp: Date.now()
                        };
                        sessionStorage.setItem('dms_pic_mdi_state', JSON.stringify(state));
                        localStorage.setItem('dms_pic_mdi_state', JSON.stringify(state));

                        const activeWin = this.openWindows.find(w => w.id === this.activeWinId);
                        if (activeWin && activeWin.url) {
                            localStorage.setItem('indraco_dms_last_active_path', activeWin.url);
                        }
                    } catch (e) {}
                },

                refreshActiveWindow() {
                    if (this.activeWinId) {
                        const iframe = document.getElementById('iframe-' + this.activeWinId);
                        if (iframe && iframe.contentWindow) {
                            iframe.contentWindow.location.reload();
                            return;
                        }
                    }
                    window.location.reload();
                },

                cascadeWindows() {
                    const visibleWins = this.openWindows.filter(w => !w.minimized);
                    visibleWins.forEach((win, index) => {
                        win.maximized = false;
                        win.posX = (index - (visibleWins.length - 1) / 2) * 45;
                        win.posY = (index - (visibleWins.length - 1) / 2) * 35;
                        win.zIndex = ++this.maxZIndex;
                    });
                    if (visibleWins.length > 0) {
                        this.activeWinId = visibleWins[visibleWins.length - 1].id;
                    }
                },

                tileWindows() {
                    const visibleWins = this.openWindows.filter(w => !w.minimized);
                    const count = visibleWins.length;
                    if (count === 0) return;

                    visibleWins.forEach((win, index) => {
                        win.maximized = false;
                        if (count === 1) {
                            win.posX = 0;
                            win.posY = 0;
                        } else if (count === 2) {
                            win.posX = index === 0 ? -200 : 200;
                            win.posY = 0;
                        } else {
                            const cols = Math.ceil(Math.sqrt(count));
                            const row = Math.floor(index / cols);
                            const col = index % cols;
                            win.posX = (col - (cols - 1) / 2) * 240;
                            win.posY = (row - (Math.ceil(count / cols) - 1) / 2) * 160;
                        }
                        win.zIndex = ++this.maxZIndex;
                    });
                },

                startDragWindow(win, e) {
                    if (win.maximized) return;
                    if (e.target.closest('button') || e.target.closest('input') || e.target.closest('select') || e.target.closest('a')) return;
                    this.focusWindow(win.id);
                    this.activeDragWin = win;
                    win.isDragging = true;
                    win.startX = e.clientX - win.posX;
                    win.startY = e.clientY - win.posY;
                },

                onDrag(e) {
                    if (!this.activeDragWin || !this.activeDragWin.isDragging || this.activeDragWin.maximized) return;
                    this.activeDragWin.posX = e.clientX - this.activeDragWin.startX;
                    this.activeDragWin.posY = e.clientY - this.activeDragWin.startY;
                },

                stopDrag() {
                    if (this.activeDragWin) {
                        this.activeDragWin.isDragging = false;
                        this.activeDragWin = null;
                    }
                },

                resetWindowPos(win) {
                    win.posX = 0;
                    win.posY = 0;
                },

                getWindowStyle(win) {
                    let style = `z-index: ${win.zIndex};`;
                    if (!win.maximized && (win.posX !== 0 || win.posY !== 0)) {
                        style += ` transform: translate3d(${win.posX}px, ${win.posY}px, 0px);`;
                    }
                    return style;
                },

                @include('layouts.partials.pic.pic_settings_scripts')
        }

        @include('layouts.partials.pic.pic_search_shortcuts')
    </script>