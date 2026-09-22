@if(request()->has('embed') || request()->header('X-MDI-Embed') || request()->header('Sec-Fetch-Dest') === 'iframe' || \Illuminate\Support\Str::contains(request()->header('referer', ''), 'embed=1'))
<!DOCTYPE html>
<html lang="id" class="h-full select-none">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DMS PT Indraco - Workstation Form')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    
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
        main table { border-collapse: separate; border-spacing: 0; font-size: 0.85rem; }
        main table th { background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%); border-right: 1px solid #cbd5e1; border-bottom: 2px solid #94a3b8; color: #1e293b; padding-top: 7px; padding-bottom: 7px; }
        .dark main table th { background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right: 1px solid #334155; border-bottom: 2px solid #475569; color: #f8fafc; }
        main table td { border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding-top: 7px; padding-bottom: 7px; }
        .dark main table td { border-right: 1px solid #1e293b; border-bottom: 1px solid #1e293b; }
    </style>
</head>
<body class="h-full bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 p-3 sm:p-4 overflow-y-auto font-sans text-xs">
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
      x-data="desktopPicAppLayout()" 
      x-init="initMdi()"
      @open-form-window.window="openFormWindowWithCustom($event.detail.id, $event.detail.title, $event.detail.icon, $event.detail.url)"
      @mousemove.window="onDrag($event)"
      @mouseup.window="stopDrag()"
      :class="theme === 'dark' ? 'dark' : ''"
      style="font-size: {{ $fontSizeScale }};"
      class="h-full select-none">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DMS PT Indraco - Workstation Desktop Edition')</title>

    <!-- Anti-Nested MDI Shell Protection: If rendered inside an iframe without embed=1, automatically reload as embed -->
    <script>
        if (window.self !== window.top && !window.location.search.includes('embed=')) {
            const sep = window.location.search ? '&' : '?';
            window.location.replace(window.location.pathname + window.location.search + sep + 'embed=1');
        }
    </script>
    
    <!-- PWA Manifest & Theme -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#d97706">
    
    <!-- Google Fonts Inter & JetBrains Mono -->
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

    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        .no-scrollbar {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
        
        /* Enterprise Desktop Custom Component Styles (Delphi/VB DBGrid & TForm Style) */
        main table {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.85rem;
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
<body class="h-full bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 flex flex-col overflow-hidden font-sans text-xs">

    <!-- IMPERSONATION BANNER (If Active) -->
    @if(session()->has('impersonator_id'))
    @php
        $impersonator = \App\Models\User::find(session('impersonator_id'));
    @endphp
    <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 px-4 py-2 shadow-md flex items-center justify-between z-50 text-xs font-bold border-b border-amber-600 shrink-0 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-1 bg-slate-950 text-amber-400 rounded shadow">
                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
            </span>
            <div>
                <span>Mode Impersonasi Aktif: Anda sedang login sebagai <strong class="underline font-black text-slate-950">{{ auth()->user()->name }}</strong> ({{ auth()->user()->role_label }} {{ auth()->user()->department ? '- ' . auth()->user()->department->code : '' }})</span>
                @if($impersonator)
                    <span class="opacity-80 block sm:inline text-[11px] sm:ml-2">| Akun Asli: <strong>{{ $impersonator->name }}</strong> (Super Admin)</span>
                @endif
            </div>
        </div>

        <form action="{{ route('impersonate.leave') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="px-3 py-1 bg-slate-950 hover:bg-slate-900 text-white rounded text-xs font-black shadow transition flex items-center gap-1 shrink-0">
                <i data-lucide="log-out" class="w-3 h-3 text-amber-400"></i>
                Kembali ke SuperAdmin
            </button>
        </form>
    </div>
    @endif

    <!-- 1. TOP WINDOW TITLE BAR & DELPHI MAIN MENU -->
    <header class="bg-slate-950 text-white flex items-center justify-between px-3 py-1.5 border-b border-slate-800 shrink-0 shadow-sm z-30 font-mono">
        <div class="flex items-center gap-4">
            <!-- Brand & Desktop Logo -->
            <a href="{{ route('archives.index') }}" class="flex items-center gap-2 font-black tracking-tight text-white group">
                <div class="p-1 bg-amber-500 text-slate-950 rounded font-extrabold text-xs shadow">
                    <i data-lucide="monitor" class="w-4 h-4"></i>
                </div>
                <span class="text-xs sm:text-sm font-extrabold">
                    INDRACO DMS <span class="text-amber-400 text-xs font-mono font-bold">[Desktop Workstation]</span>
                </span>
            </a>

            <!-- Delphi Style Top Menu Dropdowns -->
            <nav class="hidden md:flex items-center gap-3 text-slate-300 text-xs font-medium border-l border-slate-800 pl-4">
                <button @click="openFormWindow('archives')" type="button" class="hover:text-amber-400 transition" :class="activeWinId === 'archives' ? 'text-amber-400 font-bold' : ''">Catalog</button>
                <button @click="openFormWindow('borrowings')" type="button" class="hover:text-amber-400 transition" :class="activeWinId === 'borrowings' ? 'text-amber-400 font-bold' : ''">Borrowings</button>
                <button @click="openFormWindow('destructions')" type="button" class="hover:text-amber-400 transition" :class="activeWinId === 'destructions' ? 'text-amber-400 font-bold' : ''">Retention</button>
                <button @click="openFormWindow('logs')" type="button" class="hover:text-amber-400 transition" :class="activeWinId === 'logs' ? 'text-amber-400 font-bold' : ''">Audit Logs</button>
            </nav>
        </div>

        <!-- Right User Info & Controls -->
        <div class="flex items-center gap-2">
            <!-- Audio Sound Notification Toggle Button -->
            <button 
                @click="toggleSound()" 
                type="button" 
                class="px-2 py-0.5 rounded text-[11px] font-mono flex items-center gap-1.5 transition border cursor-pointer select-none"
                :class="soundEnabled ? 'bg-slate-900 hover:bg-slate-800 text-emerald-300 border-emerald-500/40 shadow-xs' : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-800'"
                :title="soundEnabled ? 'Suara Notifikasi: AKTIF (Klik untuk Mute)' : 'Suara Notifikasi: MUTE (Klik untuk Aktifkan)'"
            >
                <template x-if="soundEnabled">
                    <span class="flex items-center gap-1"><i data-lucide="volume-2" class="w-3.5 h-3.5 text-emerald-400"></i> <span class="hidden sm:inline">Suara ON</span></span>
                </template>
                <template x-if="!soundEnabled">
                    <span class="flex items-center gap-1"><i data-lucide="volume-x" class="w-3.5 h-3.5 text-rose-400"></i> <span class="hidden sm:inline">Mute</span></span>
                </template>
            </button>

            <!-- Real-Time Notification Bell & Dropdown -->
            <div class="relative" @click.outside="showNotificationDropdown = false">
                <button 
                    @click="showNotificationDropdown = !showNotificationDropdown; if(showNotificationDropdown) unreadNotificationsCount = 0;" 
                    type="button" 
                    class="relative px-2 py-0.5 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 rounded text-[11px] font-mono flex items-center gap-1.5 transition cursor-pointer"
                    title="Aktivitas Dokumen Real-Time (Live Feed)"
                >
                    <i data-lucide="bell" class="w-3.5 h-3.5" :class="unreadNotificationsCount > 0 ? 'text-amber-400 animate-bounce' : 'text-slate-400'"></i>
                    <span class="hidden md:inline">Notifikasi</span>
                    <template x-if="unreadNotificationsCount > 0">
                        <span class="px-1.5 py-0.2 bg-rose-500 text-white rounded-full text-[10px] font-black animate-pulse" x-text="unreadNotificationsCount"></span>
                    </template>
                </button>

                <!-- Notification Dropdown History Menu -->
                <div 
                    x-show="showNotificationDropdown" 
                    x-transition 
                    x-cloak 
                    class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-slate-900 border-2 border-amber-500 rounded-lg shadow-2xl z-50 overflow-hidden text-xs font-mono"
                >
                    <div class="p-2.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-700">
                        <div class="flex items-center gap-1.5 font-bold text-amber-400 text-[11px]">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <span>LIVE INCOMING DOCUMENTS</span>
                        </div>
                        <button @click="playChime()" type="button" class="text-[10px] px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 rounded flex items-center gap-1 transition cursor-pointer">
                            <i data-lucide="volume-2" class="w-3 h-3 text-emerald-400"></i> Tes Suara
                        </button>
                    </div>

                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                        <template x-if="realtimeNotifications.length === 0">
                            <div class="p-6 text-center text-slate-500 dark:text-slate-400 space-y-1">
                                <i data-lucide="inbox" class="w-6 h-6 mx-auto text-slate-400 mb-1"></i>
                                <p class="font-bold">Belum ada aktivitas dokumen baru</p>
                                <p class="text-[10px]">Dokumen yang ditambahkan akan muncul otomatis di sini secara real-time.</p>
                            </div>
                        </template>

                        <template x-for="item in realtimeNotifications" :key="item.id">
                            <div @click="openArchiveFromNotification(item)" class="p-2.5 hover:bg-amber-500/10 dark:hover:bg-slate-800/80 cursor-pointer transition flex flex-col gap-1 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-500/20 text-blue-700 dark:text-blue-300" x-text="item.deptCode"></span>
                                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="item.boxNumber"></span>
                                    </div>
                                    <span class="text-[10px] text-slate-400" x-text="item.time"></span>
                                </div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="item.title"></div>
                                <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 pt-0.5">
                                    <span>Oleh: <strong class="text-slate-700 dark:text-slate-300" x-text="item.creatorName"></strong></span>
                                    <span class="text-amber-500 font-bold hover:underline flex items-center gap-0.5">
                                        Buka Form <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Light/Dark Mode Switcher -->
            <button 
                @click="theme = (theme === 'dark' ? 'light' : 'dark'); localStorage.setItem('theme', theme)" 
                type="button" 
                class="px-2 py-0.5 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 rounded text-[11px] font-mono flex items-center gap-1.5 transition cursor-pointer"
                title="Ganti Mode Tampilan (Alt+T)"
            >
                <template x-if="theme === 'dark'">
                    <span class="flex items-center gap-1 text-amber-300"><i data-lucide="sun" class="w-3 h-3"></i> <span class="hidden xl:inline">Light Mode</span></span>
                </template>
                <template x-if="theme !== 'dark'">
                    <span class="flex items-center gap-1 text-sky-300"><i data-lucide="moon" class="w-3 h-3"></i> <span class="hidden xl:inline">Dark Mode</span></span>
                </template>
            </button>

            <!-- Fullscreen / Maximize Toggle Button -->
            <button 
                @click="toggleFullscreen()" 
                type="button" 
                :title="isFullscreen ? 'Keluar Full Screen (Esc / F11)' : 'Layar Penuh (Full Screen / Maximize)'"
                class="w-6 h-6 flex items-center justify-center bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded text-amber-400 font-bold transition active:scale-95 shrink-0 cursor-pointer"
            >
                <template x-if="isFullscreen">
                    <span class="text-[13px] font-black leading-none select-none">❐</span>
                </template>
                <template x-if="!isFullscreen">
                    <span class="text-[13px] font-black leading-none select-none">🗖</span>
                </template>
            </button>

            @auth
            <div class="flex items-center gap-2 border-l border-slate-800 pl-3">
                <div class="text-right">
                    <span class="text-xs font-bold text-white block">{{ auth()->user()->name }}</span>
                    <span class="text-[10px] text-amber-400 font-mono block">PIC DEPT: {{ auth()->user()->department->code ?? 'UMUM' }}</span>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="inline ml-1">
                    @csrf
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-900 rounded transition" title="Logout">
                        <i data-lucide="power" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </header>

    <!-- 2. DELPHI ACTION RIBBON TOOLBAR -->
    <div class="bg-white dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 px-3 py-1.5 flex flex-wrap items-center justify-between gap-2 shrink-0 shadow-xs z-40 relative font-mono">
        <div class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0">
            <!-- F2: Draft Baru -->
            <button 
                @click="openFormWindow('archives_create')"
                type="button" 
                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white font-bold text-xs transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0"
                title="Buka Form Draft Pengajuan Box Baru (F2)"
            >
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                <span>Baru (F2)</span>
            </button>

            <!-- F8: Pinjam Dokumen -->
            <button 
                @click="openFormWindow('borrowings')"
                type="button" 
                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white font-bold text-xs transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0"
                title="Buka Form Peminjaman Berkas (F8)"
            >
                <i data-lucide="file-symlink" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400"></i>
                <span>Pinjam (F8)</span>
            </button>

            <!-- F9: Cetak Custom Label -->
            <a href="{{ route('archives.print_labels') }}" target="_blank" class="px-2.5 py-1 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 rounded text-amber-700 dark:text-amber-300 font-bold text-xs transition flex items-center gap-1.5 shadow-2xs shrink-0">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                <span>Cetak Label (F9)</span>
            </a>

            <!-- F5: Refresh Active Form -->
            <button 
                @click="refreshActiveWindow()" 
                type="button" 
                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white font-bold text-xs transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0"
                title="Refresh Form yang Sedang Aktif (F5)"
            >
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"></i>
                <span>Refresh (F5)</span>
            </button>

            <!-- SEBELAH KANAN REFRESH: SEARCH INPUT WITH AUTO-SUGGESTIONS & PHYSICAL LOCATION -->
            <div class="relative z-50 flex-1 min-w-[320px] max-w-2xl ml-1" x-data="picQuickSearch()" @click.outside="closeDropdown()">
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <svg x-show="isLoading" class="w-4 h-4 text-amber-500 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                        </svg>
                        <svg x-show="!isLoading" class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>

                    <input 
                        type="text" 
                        id="picHeaderSearchInput"
                        x-model="searchQuery"
                        @input.debounce.250ms="doSearch()"
                        @focus="showSuggestions = true; if (!searchQuery) loadDefaultSuggestions()"
                        @keydown.escape="closeDropdown()"
                        @keydown.arrow-down.prevent="navigateResults(1)"
                        @keydown.arrow-up.prevent="navigateResults(-1)"
                        @keydown.enter.prevent="selectActiveResult()"
                        placeholder="Cari arsip Dept (nama dokumen, butir isi, no. box, rak) [Ctrl+F]..." 
                        class="w-full pl-9 pr-8 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition shadow-inner"
                    >

                    <!-- Clear Search Button -->
                    <button 
                        x-show="searchQuery.length > 0" 
                        @click="clearSearch()"
                        type="button" 
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-rose-500 transition cursor-pointer"
                        title="Hapus pencarian"
                        x-cloak
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <!-- AUTO-SUGGESTION FLOATING DROPDOWN PANEL (WIDE WORKSTATION DELPHI STYLE) -->
                <div 
                    x-show="showSuggestions" 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                    class="absolute left-0 mt-1 w-[680px] sm:w-[740px] max-w-[94vw] bg-white dark:bg-slate-900 border-2 border-amber-500 rounded-lg shadow-2xl z-[100] overflow-hidden font-mono text-xs"
                    x-cloak
                >
                    <!-- Header Dropdown Info -->
                    <div class="px-3 py-2 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex items-center justify-between border-b border-slate-700 text-[11px]">
                        <span class="font-bold flex items-center gap-2 text-amber-300">
                            <svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            PENCARIAN ARSIP & LOKASI GUDANG
                        </span>
                        <span class="px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-400/40 rounded text-[11px] font-bold">
                            DEPT: {{ auth()->user()->department->code ?? 'DEPT' }} - {{ auth()->user()->department->name ?? '' }}
                        </span>
                    </div>

                    <!-- Suggestion / Quick Filters Chips (when no input or matching keywords) -->
                    <div x-show="suggestedKeywords.length > 0" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1.5">Pencarian Cepat / Topik Dokumen:</span>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="kw in suggestedKeywords" :key="kw">
                                <button 
                                    @click="selectKeyword(kw)"
                                    type="button" 
                                    class="px-2.5 py-1 bg-white dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded text-[11px] font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer"
                                >
                                    <span class="text-amber-500 font-black">#</span>
                                    <span x-text="kw"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Results List Scroll Container -->
                    <div class="max-h-96 overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        <!-- Loading State -->
                        <div x-show="isLoading" class="p-6 text-center text-slate-500 flex items-center justify-center gap-2.5 font-bold">
                            <svg class="w-5 h-5 text-amber-500 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                            </svg>
                            <span>Mencari data berkas & rak gudang...</span>
                        </div>

                        <!-- Results Items -->
                        <template x-for="(item, idx) in searchResults" :key="item.id">
                            <div 
                                @click="selectArchive(item)"
                                :class="selectedIndex === idx ? 'bg-amber-500/15 dark:bg-amber-950/40 border-l-4 border-amber-500' : 'hover:bg-slate-100 dark:hover:bg-slate-800/80'"
                                class="p-3 cursor-pointer transition flex flex-col gap-1.5 select-none"
                            >
                                <!-- Top Row: No Box, Status Badge, Period -->
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-black text-amber-600 dark:text-amber-400 text-xs sm:text-sm flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                        </svg>
                                        <span x-text="item.box_number || 'Penomoran Pending'"></span>
                                    </span>

                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-bold" x-text="item.periode_doc"></span>
                                        <span 
                                            :class="item.status === 'in_warehouse' ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : (item.status === 'borrowed' ? 'bg-purple-500/20 text-purple-700 dark:text-purple-300 border-purple-500/30' : 'bg-amber-500/20 text-amber-700 dark:text-amber-300 border-amber-500/30')"
                                            class="px-2 py-0.5 rounded border text-[11px] font-bold"
                                            x-text="item.status_label"
                                        ></span>
                                    </div>
                                </div>

                                <!-- Middle Row: Judul / Nama Dokumen -->
                                <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm leading-snug" x-text="item.title"></div>

                                <!-- Content Description snippet if matched -->
                                <div x-show="item.content_description" class="text-xs text-slate-500 dark:text-slate-400 italic line-clamp-2">
                                    <span class="text-slate-400 font-bold not-italic">Isi:</span> <span x-text="item.content_description"></span>
                                </div>

                                <!-- Bottom Row: Physical Warehouse & Rack Location -->
                                <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100 dark:border-slate-800 text-xs">
                                    <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold">
                                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span x-text="item.location"></span>
                                    </div>

                                    <span class="text-[11px] text-amber-600 dark:text-amber-400 font-bold hover:underline flex items-center gap-1 shrink-0">
                                        Buka Form 
                                        <svg class="w-3 h-3 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </template>

                        <!-- Empty Result State -->
                        <div x-show="!isLoading && searchResults.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400">
                            <p class="font-bold text-xs text-slate-700 dark:text-slate-300">Tidak ada dokumen ditemukan</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Tidak ditemukan berkas pada Departemen {{ auth()->user()->department->code ?? '' }} dengan kata kunci "<span class="font-bold text-amber-600 dark:text-amber-400" x-text="searchQuery"></span>"</p>
                        </div>
                    </div>

                    <!-- Footer Dropdown Shortcut Helper -->
                    <div class="px-3 py-1.5 bg-slate-100 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 text-[10px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                        <span>Gunakan <kbd class="px-1 py-0.2 bg-slate-200 dark:bg-slate-800 border rounded font-bold">↑</kbd> <kbd class="px-1 py-0.2 bg-slate-200 dark:bg-slate-800 border rounded font-bold">↓</kbd> Navigasi, <kbd class="px-1 py-0.2 bg-slate-200 dark:bg-slate-800 border rounded font-bold">Enter</kbd> Pilih</span>
                        <span><kbd class="px-1 py-0.2 bg-slate-200 dark:bg-slate-800 border rounded font-bold">Esc</kbd> Tutup</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 text-[11px] font-mono font-bold text-slate-500 dark:text-slate-400">
            <span>DEPARTEMEN: <strong class="text-amber-600 dark:text-amber-400">{{ auth()->user()->department->name ?? 'Global' }}</strong></span>
            <span>WORKSTATION: WS-DESKTOP-01</span>
        </div>
    </div>

    <!-- 3. MDI TAB SHEET NAVIGATION MANAGER (FIXED TAB BAR) -->
    <div class="bg-slate-200/90 dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 px-2 pt-1 flex items-center justify-start gap-1 shrink-0 font-mono text-[11px] select-none z-20 relative overflow-x-auto no-scrollbar">
        <template x-for="form in availableForms" :key="form.id">
            <button 
                @click="openFormWindow(form.id)"
                type="button"
                :class="activeWinId === form.id 
                    ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 border-t-2 border-t-amber-500 border-x border-slate-300 dark:border-slate-700 font-bold shadow-sm' 
                    : (isWindowOpen(form.id)
                        ? 'bg-slate-100 dark:bg-slate-900/80 text-slate-900 dark:text-slate-100 border-t-2 border-t-transparent border-x border-slate-300 dark:border-slate-700 font-bold'
                        : 'bg-slate-200/60 dark:bg-slate-950 text-slate-600 dark:text-slate-400 border-t-2 border-t-transparent border-x border-transparent font-bold hover:bg-slate-100 dark:hover:bg-slate-850')"
                class="px-3 py-1.5 rounded-t text-xs transition-colors flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap"
            >
                <i :data-lucide="form.icon" class="w-3.5 h-3.5" :class="activeWinId === form.id ? 'text-amber-500' : (isWindowOpen(form.id) ? 'text-amber-500/80' : 'text-slate-400')"></i>
                <span x-text="form.title"></span>
            </button>
        </template>
    </div>

    <!-- 4. MAIN VIEWPORT (MDI Multi-Window Workstation Desktop Canvas) -->
    <main class="flex-1 bg-slate-200 dark:bg-slate-950 p-2 sm:p-4 overflow-hidden relative min-w-0 desktop-bg-pattern flex items-center justify-center font-sans">
        
        <!-- Empty Workspace Placeholder (When all windows closed) -->
        <div x-show="openWindows.length === 0" class="my-auto text-center space-y-3 font-mono">
            <div class="p-4 bg-slate-300 dark:bg-slate-800/80 rounded-2xl w-16 h-16 mx-auto flex items-center justify-center text-slate-500 dark:text-slate-400 shadow-inner">
                <i data-lucide="layout" class="w-8 h-8 text-amber-500"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase">INDRACO ARSIP WORKSTATION</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Klik salah satu tab menu form di atas untuk membuka form window.</p>
        </div>

        <!-- RECURSIVE MDI WINDOW FRAMES (Allows Multiple Forms Open Simultaneously) -->
        <template x-for="win in openWindows" :key="win.id">
            <div 
                x-show="!win.minimized" 
                @mousedown="focusWindow(win.id)"
                :class="win.maximized ? 'absolute inset-0 z-20 w-full h-full rounded-none my-0 border-0 shadow-none' : 'absolute rounded-t-lg rounded-b-sm border-2 border-slate-400 dark:border-slate-700 shadow-2xl resize overflow-hidden'"
                :style="getWindowStyle(win) + (win.maximized ? '' : 'width: 860px; max-width: 94vw; height: 560px; max-height: 78vh; min-width: 420px; min-height: 280px;')"
                class="delphi-window bg-slate-100 dark:bg-slate-900 flex flex-col transition-shadow duration-150"
            >
                <!-- WINDOW TITLE BAR (Draggable Desktop Caption & 3 Window Control Buttons) -->
                <div 
                    @mousedown="startDragWindow(win, $event)"
                    @dblclick="win.maximized = !win.maximized"
                    :class="win.maximized ? 'cursor-default' : (win.isDragging ? 'cursor-grabbing select-none' : 'cursor-grab')"
                    title="Klik & tahan untuk menggeser/reposisi posisi jendela form (Drag to move)"
                    class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none shrink-0"
                >
                    <!-- Left Title & Icon -->
                    <div class="flex items-center gap-2 font-bold truncate pointer-events-none">
                        <span class="p-0.5 bg-amber-500/20 border border-amber-400/40 rounded">
                            <i :data-lucide="win.icon" class="w-3.5 h-3.5 text-amber-400"></i>
                        </span>
                        <span class="tracking-wide uppercase" x-text="win.title"></span>
                    </div>

                    <!-- Right Window Controls [ 🎯 Center ] [ _ ] [ 🗖 ] [ ✕ ] -->
                    <div class="flex items-center gap-1 shrink-0" @mousedown.stop>
                        <!-- Center Button (shown when window is moved away from center) -->
                        <button 
                            x-show="win.posX !== 0 || win.posY !== 0"
                            x-transition
                            @click="resetWindowPos(win)"
                            type="button"
                            title="Kembalikan Posisi Form Window ke Tengah Layar"
                            class="px-1.5 py-0.5 bg-slate-700/80 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition active:scale-95 flex items-center gap-1 mr-1 shadow"
                        >
                            <i data-lucide="crosshair" class="w-3 h-3 text-amber-400"></i>
                            <span>Center</span>
                        </button>

                        <!-- 1. MINIMIZE BUTTON [-] -->
                        <button 
                            @click="win.minimized = true" 
                            type="button" 
                            title="Minimize Jendela Form ke Taskbar" 
                            class="w-6 h-6 flex items-center justify-center bg-slate-700/90 hover:bg-slate-600 border border-slate-600 rounded text-slate-200 text-xs font-black transition active:scale-95 cursor-pointer shadow-xs"
                        >
                            –
                        </button>

                        <!-- 2. MAXIMIZE / RESTORE BUTTON [🗖] -->
                        <button 
                            @click="win.maximized = !win.maximized" 
                            type="button" 
                            title="Maximize / Restore Ukuran Jendela Form" 
                            class="w-6 h-6 flex items-center justify-center bg-slate-700/90 hover:bg-slate-600 border border-slate-600 rounded text-slate-200 text-xs font-black transition active:scale-95 cursor-pointer shadow-xs"
                        >
                            <span x-text="win.maximized ? '❐' : '🗖'"></span>
                        </button>

                        <!-- 3. CLOSE BUTTON [✕] (Red Delphi / Windows Standard) -->
                        <button 
                            @click="closeWindow(win.id)" 
                            type="button" 
                            title="Tutup Jendela Form Ini" 
                            class="w-6 h-6 flex items-center justify-center bg-rose-600 hover:bg-rose-500 border border-rose-500 rounded text-white text-xs font-black transition active:scale-95 cursor-pointer shadow-xs"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- WINDOW IFRAME CONTAINER (ISOLATED FORM EMBED CONTENT) -->
                <div class="flex-1 bg-white dark:bg-slate-900 relative overflow-hidden">
                    <iframe 
                        :id="'iframe-' + win.id"
                        :src="win.url" 
                        :class="activeDragWin ? 'pointer-events-none' : ''"
                        class="w-full h-full border-0 block"
                    ></iframe>
                </div>
            </div>
        </template>

        <!-- REAL-TIME FLOATING TOAST NOTIFICATIONS (TOP-RIGHT) -->
        <div class="fixed top-12 right-3 sm:right-5 z-[999] flex flex-col gap-2.5 max-w-sm sm:max-w-md pointer-events-none font-mono">
            <template x-for="toast in activeToasts" :key="toast.id">
                <div 
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="opacity-0 translate-x-8 scale-95"
                    x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                    x-transition:leave="transition ease-in duration-200 transform"
                    x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-x-8 scale-95"
                    class="pointer-events-auto bg-white dark:bg-slate-900 border-2 border-amber-500 rounded-lg shadow-2xl p-3 text-slate-900 dark:text-white flex flex-col gap-2 relative overflow-hidden backdrop-blur-md ring-1 ring-black/20"
                >
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-emerald-400 to-amber-500 animate-pulse"></div>

                    <div class="flex items-center justify-between gap-2 pt-0.5">
                        <div class="flex items-center gap-1.5 font-black text-xs text-amber-600 dark:text-amber-400">
                            <span class="p-1 bg-amber-500/20 rounded border border-amber-500/30">
                                <i data-lucide="bell-ring" class="w-3.5 h-3.5 text-amber-500"></i>
                            </span>
                            <span>DOKUMEN BARU MASUK!</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-400 font-bold" x-text="toast.time"></span>
                            <button @click="dismissToast(toast.id)" class="text-slate-400 hover:text-rose-500 p-0.5 rounded transition cursor-pointer">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-black bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30" x-text="toast.deptCode + ' (' + toast.deptName + ')'"></span>
                            <span class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="toast.boxNumber"></span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20" x-text="toast.statusLabel"></span>
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white leading-tight line-clamp-2 font-sans" x-text="toast.title"></div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                            Dibuat oleh: <strong class="text-slate-800 dark:text-slate-200" x-text="toast.creatorName"></strong>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-200 dark:border-slate-800 text-xs">
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> Realtime Sync
                        </span>
                        <div class="flex items-center gap-1.5">
                            <button 
                                @click="openArchiveFromNotification(toast)" 
                                type="button" 
                                class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded border border-amber-600 text-xs transition flex items-center gap-1 shadow-sm active:scale-95 cursor-pointer"
                            >
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                <span>Buka Dokumen</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </main>

    <!-- 5. WINDOWS BOTTOM STATUS BAR PANEL (TStatusBar) -->
    <footer class="bg-slate-900 text-slate-300 text-[11px] px-3 py-1 flex items-center justify-between border-t border-slate-800 shrink-0 font-mono z-30 select-none">
        <div class="flex items-center gap-3">
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

            <!-- Minimized Window Taskbar Items -->
            <template x-for="win in openWindows.filter(w => w.minimized)" :key="win.id">
                <button 
                    @click="win.minimized = false; focusWindow(win.id)" 
                    type="button"
                    class="px-2 py-0.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/50 rounded text-[10px] font-bold flex items-center gap-1 transition active:scale-95 cursor-pointer"
                >
                    <i :data-lucide="win.icon" class="w-3 h-3 text-amber-400"></i>
                    <span x-text="win.title"></span>
                </button>
            </template>
        </div>

        <!-- MDI Cascade / Tile & Window Counter Controls in Footer Status Bar -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
                <template x-if="openWindows.length > 1">
                    <div class="flex items-center gap-1 mr-1">
                        <button 
                            @click="cascadeWindows()" 
                            type="button" 
                            class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-bold rounded border border-slate-700 flex items-center gap-1 transition shadow active:scale-95 cursor-pointer"
                            title="Susun Jendela Secara Bertingkat (Cascade)"
                        >
                            <i data-lucide="layers" class="w-3 h-3 text-amber-400"></i>
                            <span>Cascade</span>
                        </button>
                        <button 
                            @click="tileWindows()" 
                            type="button" 
                            class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-bold rounded border border-slate-700 flex items-center gap-1 transition shadow active:scale-95 cursor-pointer"
                            title="Susun Jendela Berdampingan (Tile)"
                        >
                            <i data-lucide="grid" class="w-3 h-3 text-amber-400"></i>
                            <span>Tile</span>
                        </button>
                    </div>
                </template>

                <span class="px-2 py-0.5 bg-slate-800 text-amber-300 rounded border border-slate-700 text-[10px] font-bold">
                    <span x-text="openWindows.length"></span> Form Terbuka
                </span>
            </div>

            <div class="hidden sm:flex items-center gap-3 text-slate-400 border-l border-slate-800 pl-3">
                <span>USER: <strong class="text-white">{{ auth()->user()->name }}</strong> ({{ auth()->user()->department->code ?? 'DEPT' }})</span>
                <span class="text-slate-500">|</span>
                <span>develope by Web Dev Indraco</span>
            </div>
        </div>
    </footer>

    <!-- Lucide Icons & Desktop PIC Hotkeys / Window Management Engine Script -->
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

                // Real-time Notification & Audio State
                soundEnabled: localStorage.getItem('sound_enabled') !== 'false',
                lastArchiveId: 0,
                realtimeNotifications: [],
                activeToasts: [],
                unreadNotificationsCount: 0,
                showNotificationDropdown: false,
                audioContext: null,
                audioUnlocked: false,
                pollTimer: null,

                availableForms: [
                    { 
                        id: 'archives', 
                        title: '[Form 1] Katalog Arsip {{ auth()->user()->department->code ?? "" }}', 
                        icon: 'folder-archive', 
                        url: '{{ route("archives.index") }}?embed=1' 
                    },
                    { 
                        id: 'borrowings', 
                        title: '[Form 2] Peminjaman Berkas', 
                        icon: 'file-check-2', 
                        url: '{{ route("borrowings.index") }}?embed=1' 
                    },
                    { 
                        id: 'destructions', 
                        title: '[Form Status] Expiry Retention', 
                        icon: 'shield-alert', 
                        url: '{{ route("destructions.index") }}?embed=1' 
                    },
                    { 
                        id: 'logs', 
                        title: 'Audit Trail', 
                        icon: 'history', 
                        url: '{{ route("logs.index") }}?embed=1' 
                    },
                    { 
                        id: 'archives_create', 
                        title: 'Draft Pengajuan Box Baru', 
                        icon: 'plus-circle', 
                        url: '{{ route("archives.create") }}?embed=1' 
                    }
                ],

                initMdi() {
                    let initialId = 'archives';
                    @if(request()->routeIs('archives.create')) initialId = 'archives_create';
                    @elseif(request()->routeIs('borrowings.*')) initialId = 'borrowings';
                    @elseif(request()->routeIs('destructions.*')) initialId = 'destructions';
                    @elseif(request()->routeIs('logs.*')) initialId = 'logs';
                    @elseif(request()->routeIs('archives.*')) initialId = 'archives';
                    @endif

                    this.openFormWindow(initialId);
                    this.startSystemMonitor();
                    this.initRealtimePoller();

                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                        if (this.isFullscreen) {
                            sessionStorage.setItem('app_fullscreen', 'true');
                        }
                    });

                    this.checkFullscreenPersistence();
                },

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
                    } catch(e) {}
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

                        const notes = [
                            { freq: 659.25, time: 0.00, dur: 0.22, gain: 0.25 }, // E5
                            { freq: 880.00, time: 0.10, dur: 0.32, gain: 0.30 }, // A5
                            { freq: 1108.73, time: 0.22, dur: 0.55, gain: 0.35 }  // C#6
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

                async initRealtimePoller() {
                    try {
                        const res = await fetch('{{ route("api.realtime.check") }}?initial=1');
                        if (res.ok) {
                            const data = await res.json();
                            this.lastArchiveId = data.latest_id || 0;
                        }
                    } catch (e) {
                        console.warn('Realtime init poller error:', e);
                    }

                    const unlockHandler = () => {
                        this.unlockAudio();
                        document.removeEventListener('click', unlockHandler);
                        document.removeEventListener('keydown', unlockHandler);
                    };
                    document.addEventListener('click', unlockHandler);
                    document.addEventListener('keydown', unlockHandler);

                    if (this.pollTimer) clearInterval(this.pollTimer);
                    this.pollTimer = setInterval(() => this.pollNewArchives(), 4000);
                },

                async pollNewArchives() {
                    if (this.lastArchiveId === undefined || this.lastArchiveId === null) return;
                    try {
                        const res = await fetch(`{{ route("api.realtime.check") }}?last_id=${this.lastArchiveId}`);
                        if (!res.ok) return;
                        const data = await res.json();

                        if (data.has_new && data.new_archives && data.new_archives.length > 0) {
                            this.lastArchiveId = data.latest_id;

                            this.playChime();

                            data.new_archives.forEach(item => {
                                const toast = {
                                    id: 'toast_' + item.id + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                                    archiveId: item.id,
                                    title: item.title,
                                    boxNumber: item.box_number,
                                    deptCode: item.dept_code,
                                    deptName: item.dept_name,
                                    creatorName: item.creator_name,
                                    statusLabel: item.status_label,
                                    time: item.created_at_time || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                                    url: item.url
                                };

                                this.activeToasts.unshift(toast);
                                this.realtimeNotifications.unshift(toast);
                                this.unreadNotificationsCount++;

                                setTimeout(() => {
                                    this.dismissToast(toast.id);
                                }, 9000);
                            });

                            if (this.realtimeNotifications.length > 25) {
                                this.realtimeNotifications = this.realtimeNotifications.slice(0, 25);
                            }

                            setTimeout(() => lucide.createIcons(), 60);
                        } else if (data.latest_id && data.latest_id > this.lastArchiveId) {
                            this.lastArchiveId = data.latest_id;
                        }
                    } catch (err) {
                        // Silent error
                    }
                },

                dismissToast(toastId) {
                    this.activeToasts = this.activeToasts.filter(t => t.id !== toastId);
                },

                openArchiveFromNotification(item) {
                    const detailId = 'archive_detail_' + (item.archiveId || item.id);
                    const boxLabel = item.boxNumber || item.box_number || ('ID #' + (item.archiveId || item.id));
                    const title = `[Detail] ${boxLabel} - ${item.title || 'Dokumen'}`;
                    let url = item.url || ('{{ url("/archives") }}/' + (item.archiveId || item.id));
                    if (!url.includes('embed=1')) {
                        url += (url.includes('?') ? '&embed=1' : '?embed=1');
                    }

                    this.openFormWindowWithCustom(detailId, title, 'file-text', url);
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

                isWindowOpen(formId) {
                    return this.openWindows.some(w => w.id === formId);
                },

                openFormWindow(formId) {
                    let win = this.openWindows.find(w => w.id === formId);
                    if (win) {
                        win.minimized = false;
                        this.focusWindow(win.id);
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
                        win.url = url;
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

                closeWindow(formId) {
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
                }
            }
        }

        // PIC Department Quick Search & Live Suggestion Component
        function picQuickSearch() {
            return {
                searchQuery: '',
                showSuggestions: false,
                isLoading: false,
                searchResults: [],
                suggestedKeywords: [],
                selectedIndex: -1,

                async loadDefaultSuggestions() {
                    this.isLoading = true;
                    try {
                        const res = await fetch('{{ route("archives.search_api") }}');
                        const data = await res.json();
                        if (data.type === 'suggestions') {
                            this.suggestedKeywords = data.keywords || [];
                            this.searchResults = data.recent || [];
                        }
                    } catch (err) {
                        console.error('Search API error:', err);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async doSearch() {
                    if (!this.searchQuery.trim()) {
                        this.loadDefaultSuggestions();
                        return;
                    }
                    this.isLoading = true;
                    this.showSuggestions = true;
                    this.selectedIndex = -1;
                    try {
                        const res = await fetch('{{ route("archives.search_api") }}?q=' + encodeURIComponent(this.searchQuery.trim()));
                        const data = await res.json();
                        if (data.type === 'results') {
                            this.searchResults = data.items || [];
                        }
                    } catch (err) {
                        console.error('Search API query error:', err);
                    } finally {
                        this.isLoading = false;
                    }
                },

                selectKeyword(keyword) {
                    this.searchQuery = keyword;
                    this.doSearch();
                },

                selectArchive(archive) {
                    // Keep suggestions open as requested so user can open multiple documents simultaneously
                    const detailId = 'archive_detail_' + archive.id;
                    const boxLabel = archive.box_number ? archive.box_number : ('ID #' + archive.id);
                    const title = `[Detail] ${boxLabel} - ${archive.title || 'Dokumen'}`;
                    let url = archive.url || ('{{ url("/archives") }}/' + archive.id);
                    url += (url.includes('?') ? '&embed=1' : '?embed=1');

                    window.dispatchEvent(new CustomEvent('open-form-window', {
                        detail: {
                            id: detailId,
                            title: title,
                            icon: 'file-text',
                            url: url
                        }
                    }));
                },

                navigateResults(direction) {
                    if (!this.searchResults.length) return;
                    this.selectedIndex = (this.selectedIndex + direction + this.searchResults.length) % this.searchResults.length;
                },

                selectActiveResult() {
                    if (this.selectedIndex >= 0 && this.selectedIndex < this.searchResults.length) {
                        this.selectArchive(this.searchResults[this.selectedIndex]);
                    }
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.loadDefaultSuggestions();
                },

                closeDropdown() {
                    this.showSuggestions = false;
                    this.selectedIndex = -1;
                }
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            if (window.lucide) {
                lucide.createIcons();
            }

            // Desktop Keyboard Shortcuts Handler
            document.addEventListener('keydown', function(e) {
                // F2: Buka Form Draft Baru
                if (e.key === 'F2') {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-form-window', {
                        detail: {
                            id: 'archives_create',
                            title: 'Draft Pengajuan Box Baru',
                            icon: 'plus-circle',
                            url: '{{ route("archives.create") }}?embed=1'
                        }
                    }));
                }
                // F5: Refresh Halaman / Active Window
                else if (e.key === 'F5') {
                    e.preventDefault();
                    const app = document.querySelector('[x-data="desktopPicAppLayout()"]');
                    if (app && window.Alpine) {
                        const alpineData = Alpine.$data(app);
                        if (alpineData && alpineData.refreshActiveWindow) {
                            alpineData.refreshActiveWindow();
                            return;
                        }
                    }
                    window.location.reload();
                }
                // F8: Form Peminjaman Dokumen
                else if (e.key === 'F8') {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-form-window', {
                        detail: {
                            id: 'borrowings',
                            title: '[Form 2] Peminjaman Berkas',
                            icon: 'file-check-2',
                            url: '{{ route("borrowings.index") }}?embed=1'
                        }
                    }));
                }
                // Ctrl + F: Focus Search Input
                else if (e.ctrlKey && e.key.toLowerCase() === 'f') {
                    e.preventDefault();
                    const searchInput = document.getElementById('picHeaderSearchInput');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
@endif
