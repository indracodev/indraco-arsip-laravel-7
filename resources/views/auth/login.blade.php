<!DOCTYPE html>
@php
    $configuredFontSize = config('app.font_size', 'medium');
    $fontSizeScale = match(strtolower($configuredFontSize)) {
        'small', 'sm' => '90%',
        'large', 'lg' => '110%',
        'xlarge', 'xl' => '120%',
        default => (str_contains($configuredFontSize, 'px') || str_contains($configuredFontSize, '%') || str_contains($configuredFontSize, 'rem')) ? $configuredFontSize : '100%',
    };
@endphp
<html lang="id" 
      x-data="loginDesktopApp()" 
      x-init="initApp()"
      @keydown.window="handleGlobalHotkeys($event)"
      @mousemove.window="onDrag($event)"
      @mouseup.window="stopDrag()"
      :class="theme === 'dark' ? 'dark' : ''"
      style="font-size: {{ $fontSizeScale }};">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS PT Indraco - Desktop Edition</title>
    
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
<body class="h-screen w-screen overflow-hidden bg-slate-200 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col font-sans select-none desktop-bg-pattern relative">

    <!-- Interactive Polygonal Mesh Background Canvas -->
    <canvas id="meshCanvas" class="fixed inset-0 pointer-events-none opacity-40 dark:opacity-30" style="z-index: 1;"></canvas>

    <!-- Top Desktop OS Workstation Header Bar -->
    <header class="bg-slate-800 text-slate-200 text-xs px-3 py-1.5 flex items-center justify-between border-b border-slate-700 z-30 font-mono shadow-sm">
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-amber-400 font-black tracking-wide">
                <i data-lucide="monitor" class="w-3.5 h-3.5"></i>
                INDRACO WORKSTATION v2.6
            </span>

        </div>
        
        <div class="flex items-center gap-3">
            <!-- Theme Toggle Button -->
            <button 
                @click="toggleTheme()" 
                type="button" 
                title="Ganti Mode Tampilan (Alt+T)"
                class="px-2 py-0.5 bg-slate-700 hover:bg-slate-600 text-slate-200 border border-slate-600 rounded text-[11px] font-mono flex items-center gap-1.5 transition"
            >
                <template x-if="theme === 'dark'">
                    <span class="flex items-center gap-1 text-amber-300"><i data-lucide="sun" class="w-3 h-3"></i> Light Mode (Alt+T)</span>
                </template>
                <template x-if="theme !== 'dark'">
                    <span class="flex items-center gap-1 text-sky-300"><i data-lucide="moon" class="w-3 h-3"></i> Dark Mode (Alt+T)</span>
                </template>
            </button>

            <!-- Fullscreen / Maximize Toggle Button -->
            <button 
                @click="toggleFullscreen()" 
                type="button" 
                :title="isFullscreen ? 'Keluar Full Screen (Esc / F11)' : 'Layar Penuh (Full Screen / Maximize)'"
                class="w-6 h-6 flex items-center justify-center bg-slate-700 hover:bg-slate-600 border border-slate-600 rounded text-amber-400 font-bold transition active:scale-95 shrink-0"
            >
                <template x-if="isFullscreen">
                    <span class="text-[13px] font-black leading-none select-none">❐</span>
                </template>
                <template x-if="!isFullscreen">
                    <span class="text-[13px] font-black leading-none select-none">🗖</span>
                </template>
            </button>

            <!-- Sound Effects Toggle -->
            <button 
                @click="soundEnabled = !soundEnabled" 
                type="button" 
                :title="soundEnabled ? 'Suara Tombol: Aktif' : 'Suara Tombol: Nonaktif'"
                class="p-1 rounded text-slate-400 hover:text-white transition"
            >
                <i :data-lucide="soundEnabled ? 'volume-2' : 'volume-x'" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </header>

    <!-- Main Desktop Screen Workspace Area -->
    <main class="flex-1 flex items-center justify-center p-4 relative z-20 overflow-hidden">

        <!-- DELPHI TFORM WINDOW CONTAINER (frmLogin) -->
        <div 
            x-show="!minimized" 
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            :class="maximized ? 'w-full h-full max-w-none rounded-none' : 'w-full max-w-2xl rounded-t-lg rounded-b-sm'"
            :style="getWindowStyle()"
            class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 flex flex-col relative overflow-hidden"
        >
            <!-- 1. WINDOW TITLE BAR (Draggable Desktop Caption & Control Buttons) -->
            <div 
                @mousedown="startDrag($event)"
                @dblclick="maximized = !maximized"
                :class="maximized ? 'cursor-default' : (isDragging ? 'cursor-grabbing select-none' : 'cursor-grab')"
                title="Klik & tahan untuk menggeser/reposisi posisi jendela (Drag to move)"
                class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none"
            >
                <!-- Left Title & Icon -->
                <div class="flex items-center gap-2 font-bold truncate pointer-events-none">
                    <span class="p-0.5 bg-amber-500/20 border border-amber-400/40 rounded">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-400"></i>
                    </span>
                    <span class="tracking-wide">DMS PT Indraco - System Authentication Manager</span>
                    <span class="px-1.5 py-0.2 bg-emerald-500/20 text-emerald-300 text-[10px] rounded border border-emerald-400/30">v1.0.0</span>
                </div>

                <!-- Right Window Control Buttons [ 🎯 Center ] [ _ ] [ 🗖 ] [ ✕ ] -->
                <div class="flex items-center gap-1 shrink-0" @mousedown.stop>
                    <button 
                        x-show="posX !== 0 || posY !== 0"
                        x-transition
                        @click="resetPosition(); playClickSound()"
                        type="button"
                        title="Kembalikan Posisi Window ke Tengah Layar"
                        class="px-1.5 py-0.5 bg-slate-700/80 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition active:scale-95 flex items-center gap-1 mr-1 shadow"
                    >
                        <i data-lucide="crosshair" class="w-3 h-3 text-amber-400"></i>
                        <span>Center</span>
                    </button>
                    <button 
                        @click="minimized = true; playClickSound()" 
                        type="button" 
                        title="Minimize ke Taskbar" 
                        class="w-5 h-5 flex items-center justify-center bg-slate-700/80 hover:bg-slate-600 border border-slate-600 rounded text-slate-200 text-[10px] font-black transition active:scale-95"
                    >
                        _
                    </button>
                    <button 
                        @click="maximized = !maximized; playClickSound()" 
                        type="button" 
                        title="Maximize / Restore Ukuran Jendela" 
                        class="w-5 h-5 flex items-center justify-center bg-slate-700/80 hover:bg-slate-600 border border-slate-600 rounded text-slate-200 text-[10px] font-black transition active:scale-95"
                    >
                        <span x-text="maximized ? '❐' : '🗖'"></span>
                    </button>
                    <button 
                        @click="closeConfirmModal = true; playClickSound()" 
                        type="button" 
                        title="Tutup Jendela App" 
                        class="w-5 h-5 flex items-center justify-center bg-rose-600/90 hover:bg-rose-500 border border-rose-500 rounded text-white text-[10px] font-black transition active:scale-95"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- 3. FORM BODY AREA (Split Layout: Left Logo Banner & Right GroupBox Form) -->
            <div class="p-4 flex-1 grid grid-cols-1 md:grid-cols-12 gap-4 overflow-y-auto">
                
                <!-- Left Banner Panel (TPanel Desktop Graphics & Branding) -->
                <div class="md:col-span-5 bg-gradient-to-br from-slate-200 via-slate-100 to-amber-500/10 dark:from-slate-950 dark:via-slate-900 dark:to-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg p-5 flex flex-col items-center justify-between text-center shadow-inner">
                    <div class="space-y-4 w-full flex flex-col items-center pt-2">
                        <!-- Company Logo in Beveled Box -->
                        <div class="p-4 bg-white dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-700 rounded-xl shadow-lg w-full flex justify-center items-center">
                            <img src="{{ asset('images/logo-indraco-est.png') }}" alt="PT Indraco Logo" class="h-14 w-auto object-contain">
                        </div>

                        <div>
                            <h2 class="font-black text-sm text-slate-800 dark:text-slate-100 tracking-tight font-mono">
                                DOCUMENT MANAGEMENT SYSTEM
                            </h2>
                        </div>
                    </div>

                    <!-- System Specs Info Badges -->
                    <div class="w-full pt-4 border-t border-slate-300 dark:border-slate-800 space-y-1.5 font-mono text-[10px] text-slate-600 dark:text-slate-400">
                        <div class="flex items-center justify-between px-2 py-1 bg-white/60 dark:bg-slate-950/60 rounded border border-slate-200 dark:border-slate-800">
                            <span>Framework:</span>
                            <strong class="text-slate-800 dark:text-slate-200">Laravel v10.x</strong>
                        </div>
                        <div class="flex items-center justify-between px-2 py-1 bg-white/60 dark:bg-slate-950/60 rounded border border-slate-200 dark:border-slate-800">
                            <span>UI Standard:</span>
                            <strong class="text-amber-600 dark:text-amber-400">Dekstop</strong>
                        </div>
                        <div class="flex items-center justify-between px-2 py-1 bg-white/60 dark:bg-slate-950/60 rounded border border-slate-200 dark:border-slate-800">
                            <span>DB Engine:</span>
                            <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Credentials GroupBox (TGroupBox Delphi Desktop Style) -->
                <div class="md:col-span-7 flex flex-col justify-between">
                    
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

                        <form action="{{ route('login.post') }}" method="POST" id="loginForm" class="space-y-4 pt-1">
                            @csrf

                            <!-- Email / Username Input (TEdit) -->
                            <div>
                                <label for="email" class="block font-mono text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                    <span>NAMA PENGGUNA / EMAIL</span>
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold">[Alt+E]</span>
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
                                        accesskey="e"
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
                                <label for="password" class="block font-mono text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                    <span>KATA SANDI / PASSWORD</span>
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold">[Alt+P]</span>
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
                                        accesskey="p"
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

                    <!-- 4. DEMO QUICK LOGIN TOOLBAR PANEL (TSpeedButton Delphi Desktop Toolbar) -->
                    <div class="mt-3 pt-3 border-t border-slate-300 dark:border-slate-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-500"></i> Pintasan Akses Cepat (TSpeedButton)
                            </span>
                            <span class="text-[10px] font-mono text-slate-500">Alt+1 / Alt+2 / Alt+3</span>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                @click="fillLogin('admin@indraco.com')" 
                                type="button" 
                                title="Autofill Super Admin (Alt+1)"
                                class="px-2 py-1.5 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/40 rounded text-purple-700 dark:text-purple-300 font-mono text-[11px] font-bold text-center transition active:scale-95 flex items-center justify-center gap-1 shadow-sm"
                            >
                                <i data-lucide="shield" class="w-3 h-3 text-purple-500"></i>
                                <span>Super Admin <span class="text-[9px] opacity-75">(Alt+1)</span></span>
                            </button>

                            <button 
                                @click="fillLogin('gudang@indraco.com')" 
                                type="button" 
                                title="Autofill PIC Gudang (Alt+2)"
                                class="px-2 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/40 rounded text-amber-700 dark:text-amber-300 font-mono text-[11px] font-bold text-center transition active:scale-95 flex items-center justify-center gap-1 shadow-sm"
                            >
                                <i data-lucide="archive" class="w-3 h-3 text-amber-500"></i>
                                <span>PIC Gudang <span class="text-[9px] opacity-75">(Alt+2)</span></span>
                            </button>

                            <button 
                                @click="fillLogin('fin@indraco.com')" 
                                type="button" 
                                title="Autofill PIC Keuangan (Alt+3)"
                                class="px-2 py-1.5 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/40 rounded text-blue-700 dark:text-blue-300 font-mono text-[11px] font-bold text-center transition active:scale-95 flex items-center justify-center gap-1 shadow-sm"
                            >
                                <i data-lucide="briefcase" class="w-3 h-3 text-blue-500"></i>
                                <span>PIC Keuangan <span class="text-[9px] opacity-75">(Alt+3)</span></span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>



        </div>

    </main>

    <!-- DESKTOP BOTTOM TASKBAR STRIP -->
    <footer class="bg-slate-900 border-t border-slate-800 text-slate-300 px-3 py-1 flex items-center justify-between text-xs font-mono z-30 shrink-0">
        <div class="flex items-center gap-2">
            <template x-if="minimized">
                <button 
                    @click="minimized = false; playClickSound()" 
                    type="button"
                    class="px-3 py-1 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/50 rounded text-[11px] font-bold flex items-center gap-1.5 transition active:scale-95"
                >
                    <i data-lucide="window" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Restore Window Login</span>
                </button>
            </template>
        </div>

        <!-- System Tray Info -->
        <div class="flex items-center gap-4 text-[11px] text-slate-400 font-mono">
            <span class="hidden sm:inline-flex items-center gap-1.5">
                <i data-lucide="wifi" class="w-3.5 h-3.5 text-emerald-400 animate-pulse"></i>
                <span>URL: <strong class="text-slate-200" x-text="connectionUrl">http://127.0.0.1:8000</strong></span>
            </span>
            <span class="text-slate-600">|</span>
            <span>&copy; 2026 web dev indraco</span>
        </div>
    </footer>

    <!-- MODAL DIALOG 1: HELP & HOTKEYS GUIDE (F1) -->
    <div 
        x-show="helpModal" 
        x-cloak 
        x-transition
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
        <div 
            @click.away="helpModal = false"
            class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono"
        >
            <div class="bg-slate-800 text-white px-3 py-2 flex items-center justify-between font-bold text-xs border-b border-slate-700">
                <span class="flex items-center gap-1.5"><i data-lucide="help-circle" class="w-4 h-4 text-blue-400"></i> Bantuan & Pintasan Keyboard (F1)</span>
                <button @click="helpModal = false" type="button" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <div class="p-4 space-y-3 text-xs text-slate-700 dark:text-slate-300 font-sans">
                <p class="font-bold text-amber-600 dark:text-amber-400 font-mono">Daftar Pintasan Keyboard (Hotkeys Desktop):</p>
                <ul class="space-y-1.5 font-mono text-[11px]">
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[Enter]</span> <strong class="text-slate-900 dark:text-slate-100">Kirim Form / Masuk System</strong>
                    </li>
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[Esc]</span> <strong class="text-slate-900 dark:text-slate-100">Reset Form / Tutup Modal</strong>
                    </li>
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[F1]</span> <strong class="text-slate-900 dark:text-slate-100">Buka Panduan Bantuan Ini</strong>
                    </li>
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[Alt + 1]</span> <strong class="text-purple-600 dark:text-purple-400">Autofill Super Admin</strong>
                    </li>
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[Alt + 2]</span> <strong class="text-amber-600 dark:text-amber-400">Autofill PIC Gudang</strong>
                    </li>
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[Alt + 3]</span> <strong class="text-blue-600 dark:text-blue-400">Autofill PIC Keuangan</strong>
                    </li>
                    <li class="flex justify-between p-1.5 bg-slate-200 dark:bg-slate-800 rounded">
                        <span>[Alt + T]</span> <strong class="text-slate-900 dark:text-slate-100">Ganti Theme (Dark / Light)</strong>
                    </li>
                </ul>
            </div>

            <div class="p-3 bg-slate-200 dark:bg-slate-800 border-t border-slate-300 dark:border-slate-700 flex justify-end">
                <button @click="helpModal = false" type="button" class="px-4 py-1.5 bg-slate-700 text-white rounded text-xs font-mono font-bold hover:bg-slate-600">
                    Tutup (Esc)
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DIALOG 2: EXIT APPLICATION CONFIRMATION -->
    <div 
        x-show="closeConfirmModal" 
        x-cloak 
        x-transition
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
        <div 
            class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-sm w-full shadow-2xl overflow-hidden font-mono"
        >
            <div class="bg-rose-700 text-white px-3 py-2 flex items-center justify-between font-bold text-xs border-b border-rose-800">
                <span class="flex items-center gap-1.5"><i data-lucide="alert-circle" class="w-4 h-4"></i> Konfirmasi Keluar Application</span>
                <button @click="closeConfirmModal = false" type="button" class="text-white hover:text-rose-200">✕</button>
            </div>

            <div class="p-4 space-y-2 text-xs text-slate-700 dark:text-slate-300 font-sans">
                <p class="font-bold text-slate-900 dark:text-white">Apakah Anda yakin ingin membatalkan login?</p>
                <p class="text-slate-500 text-[11px]">Memilih Ya akan menyembunyikan jendela login ke Taskbar Desktop.</p>
            </div>

            <div class="p-3 bg-slate-200 dark:bg-slate-800 border-t border-slate-300 dark:border-slate-700 flex justify-end gap-2 font-mono">
                <button @click="closeConfirmModal = false" type="button" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400 dark:hover:bg-slate-600">
                    Batal
                </button>
                <button @click="minimized = true; closeConfirmModal = false" type="button" class="px-3 py-1.5 bg-rose-600 text-white rounded text-xs font-bold hover:bg-rose-500">
                    Ya, Minimize App
                </button>
            </div>
        </div>
    </div>

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
                    lucide.createIcons();
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);

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
