@if(request()->has('embed') || request()->header('X-MDI-Embed') || request()->header('Sec-Fetch-Dest') === 'iframe' || \Illuminate\Support\Str::contains(request()->header('referer', ''), 'embed=1'))
<!DOCTYPE html>
<html lang="id" class="h-full select-none">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=800">
    <title>@yield('title', 'DMS PT Indraco - Workstation Form')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Instant Embed Theme Sync Script to Prevent Theme Flashbang -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('theme');
                var isDark = false;
                if (savedTheme) {
                    isDark = (savedTheme === 'dark');
                } else if (window.parent && window.parent.document && window.parent.document.documentElement) {
                    isDark = window.parent.document.documentElement.classList.contains('dark');
                }
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>
    
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
        main table { border-collapse: separate; border-spacing: 0; font-size: 11px; }
        main table th { background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%); border-right: 1px solid #cbd5e1; border-bottom: 2px solid #94a3b8; color: #1e293b; padding-top: 7px; padding-bottom: 7px; }
        .dark main table th { background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right: 1px solid #334155; border-bottom: 2px solid #475569; color: #f8fafc; }
        main table td { border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding-top: 7px; padding-bottom: 7px; }
        .dark main table td { border-right: 1px solid #1e293b; border-bottom: 1px solid #1e293b; }
    </style>
    
    <script>
        // Live Embed Theme Synchronization Listener
        function applyEmbedTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'THEME_CHANGE') {
                applyEmbedTheme(event.data.theme);
            }
        });
        function tryRestoreParentFullscreen() {
            try {
                if (sessionStorage.getItem('app_fullscreen') === 'true' && window.parent && window.parent !== window) {
                    if (!window.parent.document.fullscreenElement && window.parent.document.documentElement.requestFullscreen) {
                        window.parent.document.documentElement.requestFullscreen().catch(function(){});
                    }
                }
            } catch(e) {}
        }
        window.addEventListener('pointerdown', tryRestoreParentFullscreen, true);
        window.addEventListener('keydown', tryRestoreParentFullscreen, true);
        window.addEventListener('click', function() {
            try {
                tryRestoreParentFullscreen();
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'IFRAME_CLICK' }, '*');
                }
            } catch(e) {}
        }, true);
    </script>
</head>
<body class="h-full bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 p-3 overflow-y-auto font-sans text-xs">
    @if (session('success'))
    <div class="mb-3 p-2.5 rounded bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5 text-xs font-mono shadow-sm">
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5"></i>
        <div class="font-bold">{{ session('success') }}</div>
    </div>
    @endif

    @if (session('warning'))
    <div class="mb-3 p-2.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 flex items-start gap-2.5 text-xs font-mono shadow-sm">
        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
        <div class="font-bold">{{ session('warning') }}</div>
    </div>
    @endif

    @if (session('error'))
    <div class="mb-3 p-2.5 rounded bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 flex items-start gap-2.5 text-xs font-mono shadow-sm">
        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5"></i>
        <div class="font-bold">{{ session('error') }}</div>
    </div>
    @endif

    @yield('content')

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
@else
<!DOCTYPE html>
@php
    $configuredFontSize = config('app.font_size', env('APP_FONT_SIZE', '19px'));
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
        $fontSizeScale = '19px';
    }
@endphp
<html lang="id" 
      style="min-width: 800px; min-height: 600px; font-size: 14px;"
      class="h-full select-none overflow-x-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=800">
    <title>@yield('title', 'DMS PT Indraco - Workstation Desktop Edition')</title>
    
    <!-- PWA Manifest & Theme -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#d97706">
    
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    
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
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="{{ asset('js/seamless-desktop.js') }}"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Enterprise Desktop Custom Component Styles (Delphi/VB DBGrid & TForm Style) */
        main table {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 11px;
        }
        main table th {
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            border-right: 1px solid #cbd5e1;
            border-bottom: 2px solid #94a3b8;
            color: #1e293b;
            padding-top: 7px;
            padding-bottom: 7px;
        }
        .dark main table th {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-right: 1px solid #334155;
            border-bottom: 2px solid #475569;
            color: #f8fafc;
        }
        main table td {
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding-top: 7px;
            padding-bottom: 7px;
        }
        .dark main table td {
            border-right: 1px solid #1e293b;
            border-bottom: 1px solid #1e293b;
        }
        .delphi-window {
            box-shadow: 0 20px 50px rgba(0,0,0,0.4), inset 1px 1px 0 rgba(255,255,255,0.2);
        }
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
<body x-data="desktopAppLayout()" 
      x-init="init()" 
      @mousemove.window="onDrag($event)" 
      @mouseup.window="stopDrag()" 
      :class="theme === 'dark' ? 'dark' : ''" 
      class="h-full bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 flex flex-col overflow-hidden font-sans text-xs min-w-[800px] min-h-[600px]">

    <!-- IMPERSONATION BANNER (If Active) -->
    @if(session()->has('impersonator_id'))
    @php
        $impersonator = \App\Models\User::find(session('impersonator_id'));
    @endphp
    <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 px-4 py-2 shadow-md flex items-center justify-between z-50 text-xs font-bold border-b border-amber-600 shrink-0">
        <div class="flex items-center gap-2.5">
            <span class="p-1 bg-slate-950 text-amber-400 rounded shadow">
                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
            </span>
            <div>
                <span>Mode Impersonasi Aktif: Anda sedang login sebagai <strong class="underline font-black text-slate-950">{{ auth()->user()->name }}</strong> ({{ auth()->user()->role_label }} {{ auth()->user()->department ? '- ' . auth()->user()->department->code : '' }})</span>
                @if($impersonator)
                    <span class="opacity-80 inline text-[11px] ml-2">| Akun Asli: <strong>{{ $impersonator->name }}</strong> (Super Admin)</span>
                @endif
            </div>
        </div>

        <form action="{{ route('impersonate.leave') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="px-3 py-1 bg-slate-950 hover:bg-slate-900 text-white rounded-lg text-xs font-black shadow transition flex items-center gap-1 shrink-0">
                <i data-lucide="log-out" class="w-3 h-3 text-amber-400"></i>
                Kembali ke SuperAdmin
            </button>
        </form>
    </div>
    @endif

    <!-- 1. TOP WINDOW TITLE BAR & WORKSTATION HEADER -->
    <!-- 1. WINDOW TITLE BAR & WORKSTATION HEADER -->
    <header class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white flex items-center justify-between px-[12px] py-[6px] border-b border-slate-700 shrink-0 shadow-sm z-30 font-mono">
        <!-- Brand Logo (Static Desktop UX) -->
        <div class="flex items-center shrink-0 select-none cursor-default" title="PT INDRACO">
            <img src="{{ asset('images/logo-indraco-invert.png') }}" alt="PT INDRACO" class="h-[22px] w-auto object-contain opacity-95 pointer-events-none">
        </div>

        <!-- Center Running Text Ticker -->
        @include('components.topbar-running-text')

        <!-- Right User Info & Controls -->
        <div class="flex items-center gap-[10px] shrink-0">
            <!-- Light/Dark Mode Switcher -->
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

            @auth
            <div class="flex items-center gap-[8px] border-l border-slate-800 pl-[10px]">
                <span class="text-slate-300 font-bold text-[11px]">{{ auth()->user()->name }}</span>

                <form action="{{ route('logout') }}" method="POST" @submit="if(isFullscreen || document.fullscreenElement) sessionStorage.setItem('app_fullscreen', 'true')" class="inline ml-[2px]">
                    @csrf
                    <button type="submit" class="p-[4px] text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-[4px] transition" title="Keluar Aplikasi">
                        <i data-lucide="log-out" class="w-[14px] h-[14px]"></i>
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </header>

    <!-- 2. DELPHI ACTION RIBBON TOOLBAR -->
    <div class="bg-white dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 px-[12px] py-[6px] flex flex-wrap items-center justify-between gap-[8px] shrink-0 shadow-xs z-30 font-mono">
        <div class="flex flex-wrap items-center gap-[6px]">
            <!-- F2: Draft Baru -->
            <a href="{{ route('archives.create') }}" class="px-[10px] py-[4px] bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-[4px] text-slate-900 dark:text-white font-bold text-[11px] transition flex items-center gap-[6px] shadow-2xs">
                <i data-lucide="plus-circle" class="w-[14px] h-[14px] text-emerald-600 dark:text-emerald-400"></i>
                <span>Baru (F2)</span>
            </a>

            <!-- F8: Pinjam Dokumen -->
            <a href="{{ route('borrowings.create') }}" class="px-[10px] py-[4px] bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-[4px] text-slate-900 dark:text-white font-bold text-[11px] transition flex items-center gap-[6px] shadow-2xs">
                <i data-lucide="file-symlink" class="w-[14px] h-[14px] text-purple-600 dark:text-purple-400"></i>
                <span>Pinjam (F8)</span>
            </a>

            <!-- F9: Cetak Custom Label -->
            <a href="{{ route('archives.print_labels') }}" target="_blank" class="px-[10px] py-[4px] bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 rounded-[4px] text-amber-700 dark:text-amber-300 font-bold text-[11px] transition flex items-center gap-[6px] shadow-2xs">
                <i data-lucide="printer" class="w-[14px] h-[14px] text-amber-600 dark:text-amber-400"></i>
                <span>Cetak Label (F9)</span>
            </a>

            <!-- F5: Refresh Data -->
            <button onclick="window.location.reload()" type="button" class="px-[10px] py-[4px] bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-[4px] text-slate-900 dark:text-white font-bold text-[11px] transition flex items-center gap-[6px] shadow-2xs">
                <i data-lucide="refresh-cw" class="w-[14px] h-[14px] text-blue-600 dark:text-blue-400"></i>
                <span>Refresh (F5)</span>
            </button>
        </div>

        <div class="flex items-center gap-[12px] text-[11px] font-mono text-slate-500 dark:text-slate-400">
            <span>DEPARTEMEN: <strong class="text-amber-600 dark:text-amber-400">{{ auth()->user()->department->name ?? 'Global' }}</strong></span>
            <span>WORKSTATION: <strong class="text-slate-700 dark:text-slate-300">WS-01</strong></span>
        </div>
    </div>

    <!-- 3. MDI TAB SHEET NAVIGATION MANAGER -->
    <div class="bg-slate-200/90 dark:bg-slate-900/90 px-[8px] pt-[6px] border-b border-slate-300 dark:border-slate-800 flex items-center gap-[4px] shrink-0 overflow-x-auto z-30 select-none">
        <!-- Tab 1: Katalog & Booking Arsip -->
        <a href="{{ route('archives.index') }}" class="px-[12px] py-[6px] rounded-t-[4px] border-t border-x font-bold text-[11px] font-mono transition flex items-center gap-[6px] shrink-0 {{ request()->routeIs('archives.index') ? 'bg-white dark:bg-slate-950 text-amber-600 dark:text-amber-400 border-slate-300 dark:border-slate-700 border-b-transparent -mb-px shadow-xs' : 'border-transparent bg-slate-300/70 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 hover:bg-white/60 dark:hover:bg-slate-800' }}">
            <i data-lucide="folder-archive" class="w-[14px] h-[14px]"></i>
            <span>Katalog Arsip {{ auth()->user()->department->code ?? '' }}</span>
        </a>

        <!-- Tab 2: Draft Pengajuan Storage Baru (If active) -->
        @if(request()->routeIs('archives.create'))
        <a href="{{ route('archives.create') }}" class="px-[12px] py-[6px] rounded-t-[4px] border-t border-x font-bold text-[11px] font-mono bg-white dark:bg-slate-950 text-emerald-600 dark:text-emerald-400 border-slate-300 dark:border-slate-700 border-b-transparent -mb-px shadow-xs transition flex items-center gap-[6px] shrink-0">
            <i data-lucide="plus-circle" class="w-[14px] h-[14px]"></i>
            <span>Draft Baru</span>
        </a>
        @endif

        <!-- Tab 3: Peminjaman Dokumen -->
        <a href="{{ route('borrowings.index') }}" class="px-[12px] py-[6px] rounded-t-[4px] border-t border-x font-bold text-[11px] font-mono transition flex items-center gap-[6px] shrink-0 {{ request()->routeIs('borrowings.*') ? 'bg-white dark:bg-slate-950 text-purple-600 dark:text-purple-400 border-slate-300 dark:border-slate-700 border-b-transparent -mb-px shadow-xs' : 'border-transparent bg-slate-300/70 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 hover:bg-white/60 dark:hover:bg-slate-800' }}">
            <i data-lucide="file-check-2" class="w-[14px] h-[14px]"></i>
            <span>Peminjaman Berkas</span>
        </a>

        <!-- Tab 4: Expiry Retention -->
        <a href="{{ route('destructions.index') }}" class="px-[12px] py-[6px] rounded-t-[4px] border-t border-x font-bold text-[11px] font-mono transition flex items-center gap-[6px] shrink-0 {{ request()->routeIs('destructions.*') ? 'bg-white dark:bg-slate-950 text-rose-600 dark:text-rose-400 border-slate-300 dark:border-slate-700 border-b-transparent -mb-px shadow-xs' : 'border-transparent bg-slate-300/70 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 hover:bg-white/60 dark:hover:bg-slate-800' }}">
            <i data-lucide="shield-alert" class="w-[14px] h-[14px]"></i>
            <span>Expiry Retention</span>
        </a>

        <!-- Tab 5: Audit Log -->
        <a href="{{ route('logs.index') }}" class="px-[12px] py-[6px] rounded-t-[4px] border-t border-x font-bold text-[11px] font-mono transition flex items-center gap-[6px] shrink-0 {{ request()->routeIs('logs.*') ? 'bg-white dark:bg-slate-950 text-cyan-600 dark:text-cyan-400 border-slate-300 dark:border-slate-700 border-b-transparent -mb-px shadow-xs' : 'border-transparent bg-slate-300/70 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 hover:bg-white/60 dark:hover:bg-slate-800' }}">
            <i data-lucide="history" class="w-[14px] h-[14px]"></i>
            <span>Audit Trail</span>
        </a>
    </div>

    <!-- 4. MAIN VIEWPORT (MAIN CONTENT CONTAINER) -->
    <main class="flex-1 bg-slate-200 dark:bg-slate-950 p-[12px] overflow-auto relative min-w-0 desktop-bg-pattern flex items-start justify-center font-sans">
        <!-- DELPHI TFORM WINDOW CONTAINER (Draggable Desktop Form Window) -->
        <div 
            x-show="!minimized" 
            x-transition:enter="transition ease-out duration-150 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            :class="maximized ? 'w-full h-full max-w-none rounded-none my-0' : 'w-full max-w-[1200px] rounded-[4px] my-auto'"
            :style="getWindowStyle()"
            class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 flex flex-col relative overflow-hidden shadow-2xl"
        >
            <!-- WINDOW TITLE BAR (Draggable Desktop Caption & Window Controls) -->
            <div 
                @mousedown="startDrag($event)"
                @dblclick="maximized = !maximized"
                :class="maximized ? 'cursor-default' : (isDragging ? 'cursor-grabbing select-none' : 'cursor-grab')"
                title="Klik & tahan untuk menggeser/reposisi posisi jendela form (Drag to move)"
                class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none shrink-0"
            >
                <!-- Left Title & Icon -->
                <div class="flex items-center gap-2 font-bold truncate pointer-events-none">
                    <span class="p-0.5 bg-amber-500/20 border border-amber-400/40 rounded">
                        <i data-lucide="layout" class="w-3.5 h-3.5 text-amber-400"></i>
                    </span>
                    <span class="tracking-wide uppercase">@yield('title', 'DMS PT Indraco - Workstation Form')</span>
                </div>

                <!-- Right Window Controls [ 🎯 Center ] [ _ ] [ 🗖 ] -->
                <div class="flex items-center gap-1 shrink-0" @mousedown.stop>
                    <button 
                        x-show="posX !== 0 || posY !== 0"
                        x-transition
                        @click="resetPosition()"
                        type="button"
                        title="Kembalikan Posisi Form Window ke Tengah Layar"
                        class="px-1.5 py-0.5 bg-slate-700/80 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition active:scale-95 flex items-center gap-1 mr-1 shadow"
                    >
                        <i data-lucide="crosshair" class="w-3 h-3 text-amber-400"></i>
                        <span>Center</span>
                    </button>
                    <button 
                        @click="minimized = true" 
                        type="button" 
                        title="Minimize Jendela Form ke Taskbar" 
                        class="w-5 h-5 flex items-center justify-center bg-slate-700/80 hover:bg-slate-600 border border-slate-600 rounded text-slate-200 text-[10px] font-black transition active:scale-95"
                    >
                        _
                    </button>
                    <button 
                        @click="maximized = !maximized" 
                        type="button" 
                        title="Maximize / Restore Ukuran Jendela Form" 
                        class="w-5 h-5 flex items-center justify-center bg-slate-700/80 hover:bg-slate-600 border border-slate-600 rounded text-slate-200 text-[10px] font-black transition active:scale-95"
                    >
                        <span x-text="maximized ? '❐' : '🗖'"></span>
                    </button>
                </div>
            </div>

            <!-- FORM CONTENT BODY -->
            <div class="p-3 flex-1 overflow-y-auto max-h-[calc(100vh-140px)]">
                <!-- Flash Banners -->
                @if (session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5 text-xs shadow-sm">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5"></i>
                    <div class="font-bold">{{ session('success') }}</div>
                </div>
                @endif

                @if (session('warning'))
                <div class="mb-4 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 flex items-start gap-2.5 text-xs shadow-sm">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
                    <div class="font-bold">{{ session('warning') }}</div>
                </div>
                @endif

                @if (session('error'))
                <div class="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 flex items-start gap-2.5 text-xs shadow-sm">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5"></i>
                    <div class="font-bold">{{ session('error') }}</div>
                </div>
                @endif

                @yield('content')
            </div>
        </div>
    </main>

    <!-- 5. WINDOWS BOTTOM STATUS BAR PANEL -->
    <footer class="bg-slate-900 text-slate-300 text-[11px] px-3 py-1 flex items-center justify-between border-t border-slate-800 shrink-0 font-mono z-30">
        <div class="flex items-center gap-3">
            <template x-if="minimized">
                <button 
                    @click="minimized = false" 
                    type="button"
                    class="px-2.5 py-0.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/50 rounded text-[11px] font-bold flex items-center gap-1.5 transition active:scale-95 mr-2"
                >
                    <i data-lucide="window" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Restore Window Form</span>
                </button>
            </template>
            <span class="flex items-center gap-1.5 text-emerald-400 font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> SYSTEM READY
            </span>

            <span class="text-slate-700">|</span>

            <!-- CPU & MEMORY RESOURCE USAGE MONITOR -->
            <div class="flex items-center gap-2 text-[10px]">
                <span class="px-1.5 py-0.2 bg-slate-800 text-sky-300 border border-slate-700 rounded font-bold flex items-center gap-1" title="Penggunaan CPU Workstation">
                    <i data-lucide="cpu" class="w-3 h-3 text-sky-400"></i>
                    <span>CPU: <strong x-text="cpuUsage + '%'">12%</strong></span>
                </span>

                <span class="px-1.5 py-0.2 bg-slate-800 text-purple-300 border border-slate-700 rounded font-bold flex items-center gap-1" title="Penggunaan Memori RAM Workstation">
                    <i data-lucide="hard-drive" class="w-3 h-3 text-purple-400"></i>
                    <span>MEM: <strong x-text="memUsage + '%'">38%</strong></span>
                </span>
            </div>
            <span class="text-slate-400">|</span>
            <span>USER: <strong class="text-white">{{ auth()->check() ? auth()->user()->name : 'PIC' }}</strong> ({{ auth()->check() && auth()->user()->department ? auth()->user()->department->code : 'DEPT' }})</span>
        </div>
        <div class="flex items-center gap-4 text-slate-400">
            <span>HOTKEYS: F2:Baru | F5:Refresh | F8:Pinjam | F9:Cetak Label | Ctrl+F:Cari</span>
            <span class="text-slate-400">|</span>
            <span>develope by Web Dev Indraco</span>
        </div>
    </footer>

    <!-- Lucide Icons & Desktop Hotkeys Engine Script -->
    <script>
        function desktopAppLayout() {
            return {
                theme: localStorage.getItem('theme') || 'light',
                posX: 0,
                posY: 0,
                isDragging: false,
                startX: 0,
                startY: 0,
                maximized: false,
                minimized: false,
                isFullscreen: !!document.fullscreenElement,
                cpuUsage: 12,
                memUsage: 38,

                toggleTheme() {
                    this.theme = (this.theme === 'dark' ? 'light' : 'dark');
                    localStorage.setItem('theme', this.theme);
                },

                init() {
                    this.startSystemMonitor();

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
                    window.addEventListener('message', (e) => {
                        if (e.data && e.data.type === 'IFRAME_CLICK') {
                            silentRestore();
                        }
                    });
                },

                toggleFullscreen() {
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

                startSystemMonitor() {
                    this.updateStats();
                    setInterval(() => this.updateStats(), 3000);
                },

                updateStats() {
                    if (window.performance && window.performance.memory) {
                        const mem = window.performance.memory;
                        const usedPct = Math.round((mem.usedJSHeapSize / mem.jsHeapSizeLimit) * 100);
                        this.memUsage = Math.min(Math.max(usedPct + 24, 28), 75);
                    } else {
                        this.memUsage = Math.floor(Math.random() * 10) + 34; // 34% - 44%
                    }
                    this.cpuUsage = Math.floor(Math.random() * 14) + 6; // 6% - 20%
                },

                startDrag(e) {
                    if (this.maximized) return;
                    if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select') || e.target.closest('a')) return;
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
                }
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();

            // Keyboard Shortcuts Handler
            document.addEventListener('keydown', function(e) {
                // F2: Buka Form Draft Baru
                if (e.key === 'F2') {
                    e.preventDefault();
                    window.location.href = "{{ route('archives.create') }}";
                }
                // F5: Refresh Halaman
                if (e.key === 'F5') {
                    e.preventDefault();
                    window.location.reload();
                }
                // F8: Form Peminjaman Dokumen
                else if (e.key === 'F8') {
                    e.preventDefault();
                    window.location.href = "{{ route('borrowings.create') }}";
                }
                // F9: Cetak Custom Label
                else if (e.key === 'F9') {
                    e.preventDefault();
                    window.open("{{ route('archives.print_labels') }}", '_blank');
                }
                // Ctrl + F: Focus Search Input
                else if (e.ctrlKey && e.key.toLowerCase() === 'f') {
                    e.preventDefault();
                    const searchInput = document.querySelector('input[name="search"], input[type="text"][placeholder*="Cari"]');
                    if (searchInput) searchInput.focus();
                }
                // Alt + T: Toggle Theme
                else if (e.altKey && (e.key === 't' || e.key === 'T')) {
                    e.preventDefault();
                    const alpineRoot = document.querySelector('[x-data]');
                    if (window.Alpine && alpineRoot) {
                        const alpineData = Alpine.$data(alpineRoot);
                        if (alpineData && typeof alpineData.toggleTheme === 'function') {
                            alpineData.toggleTheme();
                        }
                    }
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
@endif
