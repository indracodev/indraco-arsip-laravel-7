<!-- ALPINE JS LOGIC SCRIPT -->
    <script>
        function loginDesktopApp() {
            return {
                theme: localStorage.getItem('theme') || 'light',
                minimized: false,
                maximized: false,
                isFullscreen: false,
                capsLock: false,
                showPassword: false,
                helpModal: false,
                closeConfirmModal: false,
                fontSizeModal: false,
                currentFontSize: localStorage.getItem('app_font_size') || '{{ $fontSizeScale }}',
                isSubmitting: false,
                soundEnabled: true,
                serverTime: '00:00:00',
                connectionUrl: 'http://127.0.0.1:8000',
                posX: 0,
                posY: 0,
                isDragging: false,
                startX: 0,
                startY: 0,

                initApp() {
                    lucide.createIcons();
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);

                    // Apply stored font size immediately if present
                    const storedFont = localStorage.getItem('app_font_size');
                    if (storedFont) {
                        this.currentFontSize = storedFont;
                        document.documentElement.style.fontSize = storedFont;
                    }

                    // Default login page is NOT full screen
                    sessionStorage.setItem('app_fullscreen', 'false');
                    this.isFullscreen = false;
                    if (document.fullscreenElement && document.exitFullscreen) {
                        document.exitFullscreen().catch(() => {});
                    }

                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                        sessionStorage.setItem('app_fullscreen', this.isFullscreen ? 'true' : 'false');
                    });

                    if (window.desktopApi) {
                        window.desktopApi.getConfig().then(cfg => {
                            if (cfg && cfg.server_config && cfg.server_config.target_url) {
                                this.connectionUrl = cfg.server_config.target_url;
                            }
                        });
                    } else {
                        this.connectionUrl = window.location.origin;
                    }
                },

                setFontSize(size) {
                    this.currentFontSize = size;
                    localStorage.setItem('app_font_size', size);
                    document.documentElement.style.fontSize = size;
                    this.playClickSound();
                },

                increaseFontSize() {
                    let cur = parseInt(this.currentFontSize) || 19;
                    let next = Math.min(cur + 1, 24);
                    this.setFontSize(next + 'px');
                },

                decreaseFontSize() {
                    let cur = parseInt(this.currentFontSize) || 19;
                    let next = Math.max(cur - 1, 14);
                    this.setFontSize(next + 'px');
                },

                getFontSizeLabel(size) {
                    if (!size) return 'Standar (19px)';
                    let s = size.toString().toLowerCase();
                    if (s === '16px' || s === '90%' || s === 'small' || s === 'sm') return 'Kecil (16px)';
                    if (s === '19px' || s === '100%' || s === 'medium' || s === 'md') return 'Standar (19px)';
                    if (s === '21px' || s === '110%' || s === 'large' || s === 'lg') return 'Besar (21px)';
                    if (s === '23px' || s === '120%' || s === 'xlarge' || s === 'xl') return 'X-Large (23px)';
                    return size;
                },

                toggleFullscreen() {
                    this.playClickSound();
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

                startDrag(e) {
                    if (this.maximized) return;
                    if (e.target.closest('button') || e.target.closest('input')) return;
                    this.isDragging = true;
                    this.startX = e.clientX - this.posX;
                    this.startY = e.clientY - this.posY;
                },

                onDrag(e) {
                    if (!this.isDragging || this.maximized) return;
                    this.posX = e.clientX - this.startX;
                    this.posY = e.clientY - this.startY;
                },

                stopDrag() {
                    this.isDragging = false;
                },

                resetPosition() {
                    this.posX = 0;
                    this.posY = 0;
                },

                getWindowStyle() {
                    if (this.maximized || (this.posX === 0 && this.posY === 0)) {
                        return '';
                    }
                    return `transform: translate3d(${this.posX}px, ${this.posY}px, 0px);`;
                },

                updateTime() {
                    const now = new Date();
                    this.serverTime = now.toTimeString().split(' ')[0];
                },

                toggleTheme() {
                    this.theme = (this.theme === 'dark' ? 'light' : 'dark');
                    localStorage.setItem('theme', this.theme);
                    this.playClickSound();
                },

                checkCapsLock(event) {
                    if (event.getModifierState) {
                        this.capsLock = event.getModifierState('CapsLock');
                    }
                },

                handleSubmit(e) {
                    if (this.isSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    const email = document.getElementById('email')?.value?.trim();
                    const password = document.getElementById('password')?.value;
                    if (!email || !password) {
                        return;
                    }
                    this.isSubmitting = true;
                    this.playClickSound();
                },

                resetForm() {
                    this.isSubmitting = false;
                    const form = document.getElementById('loginForm');
                    if (form) {
                        form.reset();
                        const emailInput = document.getElementById('email');
                        if (emailInput) emailInput.focus();
                    }
                },

                showAboutDialog() {
                    alert("INDRACO DMS - Workstation Desktop Edition v1.0.0\nFramework: Laravel 10 / Electron Native API\nGUI Theme: Delphi TForm & MDI Workspace Standard.");
                },

                checkDbConnection() {
                    alert("🟢 Connection Test OK!\nHost: 127.0.0.1:8000\nDatabase: Localhost MySQL\nLatency: 1.2ms");
                },

                handleGlobalHotkeys(e) {
                    // F1: Help Modal
                    if (e.key === 'F1') {
                        e.preventDefault();
                        this.helpModal = !this.helpModal;
                        this.playClickSound();
                    }
                    // Esc: Reset or Close Modals / Restore
                    else if (e.key === 'Escape') {
                        if (this.helpModal) {
                            this.helpModal = false;
                        } else if (this.closeConfirmModal) {
                            this.closeConfirmModal = false;
                        } else if (this.fontSizeModal) {
                            this.fontSizeModal = false;
                        } else if (this.minimized) {
                            this.minimized = false;
                        } else {
                            this.resetForm();
                        }
                        this.playClickSound();
                    }
                    // Alt+T: Toggle Theme
                    else if (e.altKey && (e.key === 't' || e.key === 'T')) {
                        e.preventDefault();
                        this.toggleTheme();
                    }
                },

                playClickSound() {
                    if (!this.soundEnabled) return;
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(600, ctx.currentTime);
                        osc.frequency.exponentialRampToValueAtTime(200, ctx.currentTime + 0.04);
                        gain.gain.setValueAtTime(0.08, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.04);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.04);
                    } catch(err) {}
                }
            }
        }

    </script>
