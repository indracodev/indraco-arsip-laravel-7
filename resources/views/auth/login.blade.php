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
      x-data="loginDesktopApp()" 
      x-init="initApp()"
      @keydown.window="handleGlobalHotkeys($event)"
      @mousemove.window="onDrag($event)"
      @mouseup.window="stopDrag()"
      :class="theme === 'dark' ? 'dark' : ''"
      :style="'font-size: ' + currentFontSize + ';'"
      style="font-size: {{ $fontSizeScale }};">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DMS PT Indraco - Desktop Edition</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon_indraco.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/icon_indraco.png') }}" type="image/png">
    
    <script>
        (function() {
            var storedFont = localStorage.getItem('app_font_size');
            if (storedFont) {
                document.documentElement.style.fontSize = storedFont;
            }
            var storedTheme = localStorage.getItem('theme');
            if (storedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();

        // Automatically refresh page if restored from browser cache / bfcache to ensure CSRF token is fresh
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    
    @include('layouts.partials.head_assets')

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
                :title="soundEnabled ? 'Suara Tombol: Aktif (Klik untuk Mute)' : 'Suara Tombol: Nonaktif (Klik untuk Aktifkan)'"
                class="p-1.5 rounded text-slate-400 hover:text-white transition flex items-center justify-center cursor-pointer select-none"
            >
                <!-- Sound ON: Speaker Bersuara Normal -->
                <svg x-show="soundEnabled" class="w-3.5 h-3.5 text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                    <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                </svg>
                <!-- Sound OFF: Speaker Dicoret Tanda Silang (Mute) -->
                <svg x-show="!soundEnabled" class="w-3.5 h-3.5 text-rose-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                    <line x1="23" y1="9" x2="17" y2="15"></line>
                    <line x1="17" y1="9" x2="23" y2="15"></line>
                </svg>
            </button>

            <!-- LAN Latency & Diagnostics Monitor -->
            @include('layouts.partials.lan_monitor')
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
                        <div class="p-4 bg-white border-2 border-slate-300 dark:border-slate-700 rounded-xl shadow-lg w-full flex justify-center items-center">
                            <img src="{{ asset('images/logo_indraco.png') }}" alt="PT Indraco Logo" class="h-12 sm:h-14 w-auto object-contain">
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
                            <strong class="text-slate-800 dark:text-slate-200">Laravel v{{ \Illuminate\Foundation\Application::VERSION }}</strong>
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
                    
                    @if (session('warning'))
                    <div class="mb-3 p-2.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs font-semibold font-mono flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 shrink-0"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                    @endif

                    @if (session('error'))
                    <div class="mb-3 p-2.5 rounded bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs font-semibold font-mono flex items-center gap-2">
                        <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-500 shrink-0"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    @endif

                    @if (session('info'))
                    <div class="mb-3 p-2.5 rounded bg-blue-500/10 border border-blue-500/30 text-blue-800 dark:text-blue-300 text-xs font-semibold font-mono flex items-center gap-2">
                        <i data-lucide="info" class="w-4 h-4 text-blue-500 shrink-0"></i>
                        <span>{{ session('info') }}</span>
                    </div>
                    @endif

                    @if (session('status'))
                    <div class="mb-3 p-2.5 rounded bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs font-semibold font-mono flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 shrink-0"></i>
                        <span>{{ session('status') }}</span>
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
                                        :title="showPassword ? 'Sembunyikan Kata Sandi' : 'Lihat Kata Sandi'"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 transition cursor-pointer focus:outline-none"
                                    >
                                        <!-- Eye Icon (Password Hidden) -->
                                        <svg x-show="!showPassword" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <!-- Eye Off Icon (Password Visible) -->
                                        <svg x-show="showPassword" x-cloak class="w-4 h-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                            <line x1="2" x2="22" y1="2" y2="22"/>
                                        </svg>
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

        <!-- System Tray Info & Settings -->
        <div class="flex items-center gap-3 text-[11px] text-slate-400 font-mono">
            <!-- Font Size Controller -->
            <div class="flex items-center gap-1 bg-slate-800/90 border border-slate-700/80 rounded px-1.5 py-0.5 shadow-sm">
                <i data-lucide="type" class="w-3.5 h-3.5 text-amber-400"></i>
                <span class="text-[10px] text-slate-400 font-bold mr-0.5">Font:</span>

                <!-- Quick Decrease Button -->
                <button 
                    @click="decreaseFontSize()" 
                    type="button" 
                    title="Kecilkan Font (A-)"
                    class="w-5 h-5 flex items-center justify-center rounded bg-slate-700 hover:bg-slate-600 text-slate-200 hover:text-white text-[10px] font-bold transition active:scale-95 border border-slate-600/50"
                >
                    A-
                </button>

                <!-- Font Size Settings Modal Trigger -->
                <button 
                    @click="fontSizeModal = true; playClickSound()" 
                    type="button" 
                    title="Buka Pengaturan Ukuran Font"
                    class="px-1.5 py-0.5 rounded hover:bg-slate-700 text-amber-300 font-bold text-[10px] transition flex items-center gap-1"
                >
                    <span x-text="getFontSizeLabel(currentFontSize)">Standar (19px)</span>
                    <i data-lucide="settings-2" class="w-3 h-3 text-slate-400"></i>
                </button>

                <!-- Quick Increase Button -->
                <button 
                    @click="increaseFontSize()" 
                    type="button" 
                    title="Besarkan Font (A+)"
                    class="w-5 h-5 flex items-center justify-center rounded bg-slate-700 hover:bg-slate-600 text-slate-200 hover:text-white text-[10px] font-bold transition active:scale-95 border border-slate-600/50"
                >
                    A+
                </button>
            </div>

            <span class="text-slate-700">|</span>

            <span class="hidden sm:inline-flex items-center gap-1.5">
                <i data-lucide="wifi" class="w-3.5 h-3.5 text-emerald-400 animate-pulse"></i>
                <span>URL: <strong class="text-slate-200" x-text="connectionUrl">http://127.0.0.1:8000</strong></span>
            </span>
            <span class="text-slate-600">|</span>
            <span>&copy; 2026 web dev indraco</span>
        </div>
    </footer>

    @include('auth.partials.login_modals')

    @include('auth.partials.login_scripts')
</body>
</html>
