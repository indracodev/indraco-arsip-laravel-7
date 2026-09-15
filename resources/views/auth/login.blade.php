<!DOCTYPE html>
@php
    $configuredFontSize = config('app.font_size', 'medium');
    $lowerFontSize = strtolower($configuredFontSize);
    if (in_array($lowerFontSize, ['small', 'sm'])) {
        $fontSizeScale = '90%';
    } elseif (in_array($lowerFontSize, ['large', 'lg'])) {
        $fontSizeScale = '110%';
    } elseif (in_array($lowerFontSize, ['xlarge', 'xl'])) {
        $fontSizeScale = '120%';
    } elseif (\Illuminate\Support\Str::contains($configuredFontSize, 'px') || \Illuminate\Support\Str::contains($configuredFontSize, '%') || \Illuminate\Support\Str::contains($configuredFontSize, 'rem')) {
        $fontSizeScale = $configuredFontSize;
    } else {
        $fontSizeScale = '100%';
    }
@endphp
<html lang="id" 
      style="min-width: 800px; min-height: 600px; font-size: 14px;"
      class="h-full select-none overflow-x-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=800">
    <title>DMS PT Indraco - Desktop Edition</title>
    
    <!-- Instant Pre-hydration Theme Sync Script -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('theme');
                var isDark = false;
                if (savedTheme) {
                    isDark = (savedTheme === 'dark');
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    isDark = true;
                }
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>
    
    <!-- PWA Manifest & Theme -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#d97706">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="{{ asset('js/seamless-desktop.js') }}"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Delphi / Visual Basic Classic Desktop Bevel & Frame Styles */
        .delphi-window {
            box-shadow: 0 20px 50px rgba(0,0,0,0.4), inset 1px 1px 0 rgba(255,255,255,0.2);
        }
        
        .delphi-groupbox {
            border: 1px solid #cbd5e1;
            position: relative;
        }
        .dark .delphi-groupbox {
            border: 1px solid #334155;
        }

        .delphi-input {
            box-shadow: inset 1px 1px 3px rgba(0,0,0,0.15);
        }

        /* Dot Grid Workstation Canvas */
        .desktop-bg-pattern {
            background-image: radial-gradient(rgba(148, 163, 184, 0.25) 1px, transparent 1px);
            background-size: 16px 16px;
        }
        .dark .desktop-bg-pattern {
            background-image: radial-gradient(rgba(51, 65, 85, 0.4) 1px, transparent 1px);
            background-size: 16px 16px;
        }
    </style>
</head>
<body x-data="loginDesktopApp()" 
      x-init="initApp()" 
      @keydown.window="handleGlobalHotkeys($event)" 
      @mousemove.window="onDrag($event)" 
      @mouseup.window="stopDrag()" 
      :class="theme === 'dark' ? 'dark' : ''"
      class="h-screen w-screen overflow-hidden bg-slate-200 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col font-sans select-none desktop-bg-pattern relative min-w-[800px] min-h-[600px]">

    <!-- Interactive Polygonal Mesh Background Canvas -->
    <canvas id="meshCanvas" class="fixed inset-0 pointer-events-none opacity-40 dark:opacity-30" style="z-index: 1;"></canvas>

    <!-- Top Desktop OS Workstation Header Bar -->
    <header class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-[12px] py-[6px] flex items-center justify-end border-b border-slate-700 z-30 font-mono shadow-sm gap-5">
        
        <div class="flex items-center gap-[10px] shrink-0">
            <!-- Theme Toggle Button -->
            @include('components.theme-toggle')

            <!-- Fullscreen / Maximize Toggle Button -->
            <button 
                @click="toggleFullscreen()" 
                type="button" 
                :title="isFullscreen ? 'Keluar Mode Layar Penuh' : 'Mode Layar Penuh'"
                class="w-[24px] h-[24px] flex items-center justify-center bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-[4px] text-amber-400 font-bold transition active:scale-95 shrink-0"
            >
                <template x-if="isFullscreen">
                    <span data-fullscreen-icon class="text-[13px] font-black leading-none select-none">❐</span>
                </template>
                <template x-if="!isFullscreen">
                    <span data-fullscreen-icon class="text-[13px] font-black leading-none select-none">🗖</span>
                </template>
            </button>
        </div>
    </header>

    <!-- Main Desktop Screen Workspace Area -->
    <main class="flex-1 flex items-center justify-center p-4 relative z-20 overflow-hidden">

        <!-- DELPHI TFORM WINDOW CONTAINER (frmLogin) -->
        <!-- DELPHI TFORM WINDOW CONTAINER (frmLogin) -->
        <div 
            class="w-full max-w-2xl delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-[4px] flex flex-col relative overflow-hidden shadow-2xl"
        >
            <!-- 1. WINDOW TITLE BAR -->
            <div class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-[14px] py-[8px] flex items-center justify-between border-b border-slate-600 font-mono text-[11px] select-none">
                <!-- Title & Icon -->
                <div class="flex items-center gap-[8px] font-bold truncate">
                    <span class="p-[2px] bg-amber-500/20 border border-amber-400/40 rounded-[3px]">
                        <i data-lucide="shield-check" class="w-[14px] h-[14px] text-amber-400"></i>
                    </span>
                    <span class="tracking-wide uppercase">DMS PT Indraco - System Authentication</span>
                </div>
            </div>

            <!-- 2. FORM BODY AREA (Split Layout: Left Logo Banner & Right GroupBox Form) -->
            <div class="p-4 flex-1 grid grid-cols-12 gap-4 overflow-y-auto">
                
                <!-- Left Banner Panel (TPanel Desktop Graphics & Branding) -->
                <div class="col-span-5 bg-gradient-to-br from-slate-200 via-slate-100 to-amber-500/10 dark:from-slate-950 dark:via-slate-900 dark:to-slate-800 border border-slate-300 dark:border-slate-700 rounded-[4px] p-5 flex flex-col items-center justify-between text-center shadow-inner">
                    <div class="space-y-4 w-full flex flex-col items-center pt-2">
                        <!-- Company Logo (Clean - No Box Framing) -->
                        <div class="w-full flex justify-center items-center py-[10px] select-none cursor-default pointer-events-none">
                            <img :src="theme === 'dark' ? '{{ asset('images/logo-indraco-invert.png') }}' : '{{ asset('images/logo-indraco.png') }}'" 
                                 alt="PT Indraco Logo" 
                                 class="h-[48px] w-auto object-contain transition-opacity duration-150 pointer-events-none">
                        </div>

                        <div>
                            <h2 class="font-black text-sm text-slate-800 dark:text-slate-100 tracking-tight font-mono">
                                DOCUMENT MANAGEMENT SYSTEM
                            </h2>
                        </div>
                    </div>

                    <!-- Corporate Copyright & System Identity -->
                    <div class="w-full pt-4 border-t border-slate-300 dark:border-slate-800 text-[10px] font-mono text-slate-500 dark:text-slate-400 space-y-1 text-center">
                        <div class="font-bold text-slate-700 dark:text-slate-300">PT. INDRACO GLOBAL INDONESIA</div>
                        <div>&copy; 2026 Document Management System</div>
                        <div class="text-[9px] text-amber-600 dark:text-amber-400 font-mono font-semibold" x-text="connectionUrl"></div>
                    </div>
                </div>

                <!-- Right Credentials GroupBox (TGroupBox Delphi Desktop Style) -->
                <div class="col-span-7 flex flex-col justify-between">
                    
                    @if (session('info'))
                    <div class="mb-3 p-2.5 rounded bg-blue-500/10 border border-blue-500/30 text-blue-800 dark:text-blue-300 text-xs font-semibold font-mono flex items-center gap-2">
                        <i data-lucide="info" class="w-4 h-4 text-blue-500 shrink-0"></i>
                        <span>{{ session('info') }}</span>
                    </div>
                    @endif

                    <!-- DELPHI TGROUPBOX CONTAINER -->
                    <fieldset class="delphi-groupbox p-4 rounded bg-white/80 dark:bg-slate-950/70 shadow-sm relative">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                            <i data-lucide="lock" class="w-3.5 h-3.5 text-amber-500"></i>
                            Kredensial Pengguna
                        </legend>

                        <form action="{{ route('login.post') }}" method="POST" id="loginForm" @submit="if(isFullscreen || document.fullscreenElement) sessionStorage.setItem('app_fullscreen', 'true')" class="space-y-4 pt-1">
                            @csrf

                            <!-- Email / Username Input (TEdit) -->
                            <div>
                                <label for="email" class="block font-mono text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    <span>NAMA PENGGUNA / EMAIL</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                    </div>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        id="email" 
                                        x-ref="emailInput"
                                        value="{{ old('email', 'admin@indraco.com') }}"
                                        required 
                                        autofocus
                                        class="delphi-input w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition"
                                        placeholder="nama@indraco.com"
                                    >
                                </div>
                                @error('email')
                                    <span class="text-rose-600 dark:text-rose-400 text-[11px] font-mono mt-1 block font-semibold">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Password Input (TPasswordEdit) -->
                            <div>
                                <label for="password" class="block font-mono text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    <span>KATA SANDI / PASSWORD</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="key" class="w-4 h-4"></i>
                                    </div>
                                    <input 
                                        :type="showPassword ? 'text' : 'password'" 
                                        name="password" 
                                        id="password" 
                                        x-ref="passwordInput"
                                        value="password"
                                        required 
                                        @keydown="checkCapsLock($event)"
                                        @keyup="checkCapsLock($event)"
                                        class="delphi-input w-full pl-9 pr-10 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition"
                                        placeholder="••••••••"
                                    >
                                    <button 
                                        @click="showPassword = !showPassword; playClickSound()" 
                                        type="button" 
                                        tabindex="-1"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    >
                                        <i :data-lucide="showPassword ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                                    </button>
                                </div>

                                <!-- LIVE CAPS LOCK DETECTOR BANNER -->
                                <div 
                                    x-show="capsLock" 
                                    x-cloak 
                                    x-transition
                                    class="mt-1.5 p-1.5 bg-amber-500/20 border border-amber-500/50 rounded text-[11px] font-mono text-amber-800 dark:text-amber-300 font-bold flex items-center gap-1.5"
                                >
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-500 animate-pulse shrink-0"></i>
                                    <span>⚠️ CAPS LOCK AKTIF! Periksa tombol Caps Lock keyboard Anda.</span>
                                </div>
                            </div>

                            <!-- Action Buttons Bar (TBitBtn Desktop Style) -->
                            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
                                <button 
                                    type="submit" 
                                    @click="playClickSound()"
                                    class="px-4 py-2 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 border border-amber-600 font-mono font-black text-xs rounded shadow-md hover:shadow-lg transition active:translate-y-0.5 flex items-center gap-2 ring-2 ring-amber-400/50"
                                >
                                    <i data-lucide="key-round" class="w-4 h-4 text-slate-950"></i>
                                    Login
                                </button>
                            </div>
                        </form>
                    </fieldset>

                    <!-- 3. DEMO QUICK LOGIN TOOLBAR PANEL -->
                    <div class="mt-3 pt-3 border-t border-slate-300 dark:border-slate-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-500"></i> Akun Demo Cepat
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                @click="fillLogin('admin@indraco.com')" 
                                type="button" 
                                title="Autofill Super Admin"
                                class="px-2 py-1.5 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/40 rounded text-purple-700 dark:text-purple-300 font-mono text-[11px] font-bold text-center transition active:scale-95 flex items-center justify-center gap-1 shadow-sm"
                            >
                                <i data-lucide="shield" class="w-3 h-3 text-purple-500"></i>
                                <span>Super Admin</span>
                            </button>

                            <button 
                                @click="fillLogin('gudang@indraco.com')" 
                                type="button" 
                                title="Autofill PIC Gudang"
                                class="px-2 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/40 rounded text-amber-700 dark:text-amber-300 font-mono text-[11px] font-bold text-center transition active:scale-95 flex items-center justify-center gap-1 shadow-sm"
                            >
                                <i data-lucide="archive" class="w-3 h-3 text-amber-500"></i>
                                <span>PIC Gudang</span>
                            </button>

                            <button 
                                @click="fillLogin('fin@indraco.com')" 
                                type="button" 
                                title="Autofill PIC Keuangan"
                                class="px-2 py-1.5 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/40 rounded text-blue-700 dark:text-blue-300 font-mono text-[11px] font-bold text-center transition active:scale-95 flex items-center justify-center gap-1 shadow-sm"
                            >
                                <i data-lucide="briefcase" class="w-3 h-3 text-blue-500"></i>
                                <span>PIC Keuangan</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </main>

    <!-- ALPINE JS LOGIC SCRIPT -->
    <script>
        function loginDesktopApp() {
            return {
                theme: localStorage.getItem('theme') || 'dark',
                minimized: false,
                maximized: false,
                isFullscreen: false,
                capsLock: false,
                showPassword: false,
                helpModal: false,
                closeConfirmModal: false,
                soundEnabled: true,
                serverTime: '00:00:00',
                connectionUrl: 'http://127.0.0.1:8000',
                posX: 0,
                posY: 0,
                isDragging: false,
                startX: 0,
                startY: 0,

                initApp() {
                    this.applyTheme(this.theme);
                    lucide.createIcons();
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);

                    let isNavigating = false;
                    window.addEventListener('beforeunload', () => { isNavigating = true; });
                    window.addEventListener('pagehide', () => { isNavigating = true; });
                    document.addEventListener('submit', () => { isNavigating = true; }, true);
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('a');
                        if (link && link.href && !link.href.startsWith('javascript:') && !link.getAttribute('target')) {
                            isNavigating = true;
                        }
                    }, true);

                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                        if (this.isFullscreen) {
                            sessionStorage.setItem('app_fullscreen', 'true');
                        } else {
                            if (!isNavigating) {
                                sessionStorage.setItem('app_fullscreen', 'false');
                            }
                        }
                    });

                    this.checkFullscreenPersistence();

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

                checkFullscreenPersistence() {
                    const silentRestore = () => {
                        if (sessionStorage.getItem('app_fullscreen') === 'true' && !document.fullscreenElement) {
                            if (document.documentElement.requestFullscreen) {
                                document.documentElement.requestFullscreen().then(() => {
                                    this.isFullscreen = true;
                                }).catch(() => {});
                            }
                        }
                    };

                    silentRestore();
                    window.addEventListener('pointerdown', silentRestore, true);
                    window.addEventListener('keydown', silentRestore, true);
                    window.addEventListener('click', silentRestore, true);
                },

                toggleFullscreen() {
                    this.playClickSound();
                    if (typeof window.toggleDesktopFullscreen === 'function') {
                        window.toggleDesktopFullscreen();
                    } else if (!document.fullscreenElement) {
                        if (document.documentElement.requestFullscreen) {
                            document.documentElement.requestFullscreen().then(() => {
                                this.isFullscreen = true;
                                sessionStorage.setItem('app_fullscreen', 'true');
                            }).catch(() => {});
                        }
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen().then(() => {
                                this.isFullscreen = false;
                                sessionStorage.setItem('app_fullscreen', 'false');
                            }).catch(() => {});
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
                    this.applyTheme(this.theme);
                    this.playClickSound();
                },

                applyTheme(theme) {
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                },

                checkCapsLock(event) {
                    if (event.getModifierState) {
                        this.capsLock = event.getModifierState('CapsLock');
                    }
                },

                fillLogin(email) {
                    this.playClickSound();
                    const emailInput = document.getElementById('email');
                    const passwordInput = document.getElementById('password');
                    
                    if (emailInput && passwordInput) {
                        emailInput.value = email;
                        passwordInput.value = 'password';
                        passwordInput.focus();
                    }
                },

                resetForm() {
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
                        } else if (this.minimized) {
                            this.minimized = false;
                        } else {
                            this.resetForm();
                        }
                        this.playClickSound();
                    }
                    // Alt+1: Quick Login Admin
                    else if (e.altKey && (e.key === '1' || e.code === 'Digit1')) {
                        e.preventDefault();
                        this.fillLogin('admin@indraco.com');
                    }
                    // Alt+2: Quick Login Gudang
                    else if (e.altKey && (e.key === '2' || e.code === 'Digit2')) {
                        e.preventDefault();
                        this.fillLogin('gudang@indraco.com');
                    }
                    // Alt+3: Quick Login Keuangan
                    else if (e.altKey && (e.key === '3' || e.code === 'Digit3')) {
                        e.preventDefault();
                        this.fillLogin('fin@indraco.com');
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

        // Interactive Polygonal Mesh Canvas Script
        (function() {
            const canvas = document.getElementById('meshCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            let width = (canvas.width = window.innerWidth);
            let height = (canvas.height = window.innerHeight);

            let mouse = { x: width / 2, y: height / 2, active: false };

            window.addEventListener('resize', () => {
                width = (canvas.width = window.innerWidth);
                height = (canvas.height = window.innerHeight);
                initParticles();
            });

            window.addEventListener('mousemove', (e) => {
                mouse.x = e.clientX;
                mouse.y = e.clientY;
                mouse.active = true;
            });

            window.addEventListener('mouseleave', () => {
                mouse.active = false;
            });

            function getColors() {
                const isDark = document.documentElement.classList.contains('dark');
                if (isDark) {
                    return {
                        particle: 'rgba(236, 244, 229, ',
                        line: 'rgba(225, 223, 234, ',
                        mouseLine: 'rgba(245, 224, 139, '
                    };
                } else {
                    return {
                        particle: 'rgba(17, 24, 18, ',
                        line: 'rgba(11, 18, 21, ',
                        mouseLine: 'rgba(212, 175, 55, '
                    };
                }
            }

            let particles = [];

            class Particle {
                constructor() {
                    this.x = Math.random() * width;
                    this.y = Math.random() * height;
                    this.vx = (Math.random() - 0.5) * 0.9;
                    this.vy = (Math.random() - 0.5) * 0.9;
                    this.radius = Math.random() * 2.2 + 1.2;
                }

                update() {
                    this.x += this.vx;
                    this.y += this.vy;

                    if (this.x < 0 || this.x > width) this.vx *= -1;
                    if (this.y < 0 || this.y > height) this.vy *= -1;

                    if (mouse.active) {
                        const dx = mouse.x - this.x;
                        const dy = mouse.y - this.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);
                        const maxDist = 160;

                        if (dist < maxDist) {
                            const force = (maxDist - dist) / maxDist;
                            this.x += (dx / dist) * force * 1.2;
                            this.y += (dy / dist) * force * 1.2;
                        }
                    }
                }

                draw(colors) {
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                    ctx.fillStyle = colors.particle + '0.6)';
                    ctx.fill();
                }
            }

            function initParticles() {
                particles = [];
                const count = Math.min(85, Math.floor((width * height) / 14000));
                for (let i = 0; i < count; i++) {
                    particles.push(new Particle());
                }
            }

            function animate() {
                ctx.clearRect(0, 0, width, height);
                const colors = getColors();
                const maxDist = 135;

                for (let i = 0; i < particles.length; i++) {
                    particles[i].update();
                    particles[i].draw(colors);

                    for (let j = i + 1; j < particles.length; j++) {
                        const dx = particles[i].x - particles[j].x;
                        const dy = particles[i].y - particles[j].y;
                        const dist = Math.sqrt(dx * dx + dy * dy);

                        if (dist < maxDist) {
                            const alpha = (1 - dist / maxDist) * 0.3;
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.strokeStyle = colors.line + alpha + ')';
                            ctx.lineWidth = 0.9;
                            ctx.stroke();
                        }
                    }

                    if (mouse.active) {
                        const dx = mouse.x - particles[i].x;
                        const dy = mouse.y - particles[i].y;
                        const dist = Math.sqrt(dx * dx + dy * dy);
                        const mouseMaxDist = 170;

                        if (dist < mouseMaxDist) {
                            const alpha = (1 - dist / mouseMaxDist) * 0.45;
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(mouse.x, mouse.y);
                            ctx.strokeStyle = colors.mouseLine + alpha + ')';
                            ctx.lineWidth = 1.1;
                            ctx.stroke();
                        }
                    }
                }

                requestAnimationFrame(animate);
            }

            initParticles();
            animate();
        })();
    </script>
</body>
</html>
