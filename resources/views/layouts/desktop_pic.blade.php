@php
   $configuredFontSize = config('app.font_size', env('APP_FONT_SIZE', '16px'));
   $lowerFontSize = strtolower($configuredFontSize);
   if (in_array($lowerFontSize, ['small', 'sm'])) {
       $fontSizeScale = '14px';
   } elseif (in_array($lowerFontSize, ['medium', 'md', 'default'])) {
       $fontSizeScale = '16px';
   } elseif (in_array($lowerFontSize, ['large', 'lg'])) {
       $fontSizeScale = '18px';
   } elseif (in_array($lowerFontSize, ['xlarge', 'xl'])) {
       $fontSizeScale = '20px';
   } elseif (
       \Illuminate\Support\Str::contains($configuredFontSize, 'px') ||
       \Illuminate\Support\Str::contains($configuredFontSize, '%') ||
       \Illuminate\Support\Str::contains($configuredFontSize, 'rem')
   ) {
       $fontSizeScale = $configuredFontSize;
   } else {
       $fontSizeScale = '16px';
   }
@endphp
<!DOCTYPE html>
<html lang="id" style="font-size: {{ $fontSizeScale }};" class="h-full select-none">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DMS PT Indraco - Workstation PIC')</title>

    <!-- PWA Manifest & Theme -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#d97706">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Instant Theme Synchronization -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('theme');
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else if (savedTheme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            } catch(e) {}
        })();
    </script>

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

    <!-- Lucide & Alpine.js CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        html, body { font-size: {{ $fontSizeScale }}; }
        main table { border-collapse: separate; border-spacing: 0; font-size: 0.875rem; }
        .text-\[10px\], .text-\[11px\] { font-size: 0.8125rem !important; }
        .text-\[9px\] { font-size: 0.75rem !important; }
        .text-\[12px\] { font-size: 0.875rem !important; }
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
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    @stack('styles')
</head>
<body x-data="navTabPicLayout()" 
      x-init="init()" 
      :class="theme === 'dark' ? 'dark' : ''" 
      class="h-full bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 flex flex-col overflow-hidden font-sans min-w-[800px] min-h-[600px]">

    <!-- IMPERSONATION BANNER (If Active) -->
    @if(session()->has('impersonator_id'))
    @php
        $impersonator = \App\Models\User::find(session('impersonator_id'));
    @endphp
    <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 px-4 py-2 shadow-md flex items-center justify-between z-50 font-bold border-b border-amber-600 shrink-0 text-[12px] font-mono">
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
            <button type="submit" class="px-3 py-1 bg-slate-950 hover:bg-slate-900 text-white rounded font-black shadow transition flex items-center gap-1 shrink-0">
                <i data-lucide="log-out" class="w-3 h-3 text-amber-400"></i>
                Kembali ke SuperAdmin
            </button>
        </form>
    </div>
    @endif

    <!-- 1. WINDOW TITLE BAR & WORKSTATION HEADER -->
    <header class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-[12px] py-[6px] flex items-center justify-between border-b border-slate-700 shadow-sm shrink-0 font-mono z-30">
        <!-- Brand Logo -->
        <div class="flex items-center shrink-0 select-none cursor-default" title="PT INDRACO">
            <img src="{{ asset('images/logo-indraco-invert.png') }}" alt="PT INDRACO" class="h-[22px] w-auto object-contain opacity-95 pointer-events-none">
        </div>

        <!-- Center Running Text Ticker -->
        @include('components.topbar-running-text')

        <!-- Right System Info Controls -->
        <div class="flex items-center gap-[10px] shrink-0">
            <!-- Theme Toggle Button -->
            @include('components.theme-toggle')

            <!-- Fullscreen Toggle Button -->
            <button @click="toggleFullscreen()" type="button"
               :title="isFullscreen ? 'Keluar Mode Layar Penuh' : 'Mode Layar Penuh'"
               class="w-[24px] h-[24px] flex items-center justify-center bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-[4px] text-amber-400 font-bold transition active:scale-95 shrink-0">
               <template x-if="isFullscreen">
                  <span data-fullscreen-icon class="text-[13px] font-black leading-none select-none">❐</span>
               </template>
               <template x-if="!isFullscreen">
                  <span data-fullscreen-icon class="text-[13px] font-black leading-none select-none">🗖</span>
               </template>
            </button>

            @auth
               <div class="flex items-center gap-[8px] border-l border-slate-800 pl-[10px]">
                  <span class="px-[8px] py-[2px] bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded-[4px] text-[10px] font-bold">
                     {{ auth()->user()->role_label }} ({{ auth()->user()->department ? auth()->user()->department->code : 'Global' }})
                  </span>

                  <form action="{{ route('logout') }}" method="POST" class="inline ml-[2px] mb-0">
                     @csrf
                     <button type="submit" class="p-[4px] text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-[4px] transition" title="Keluar Aplikasi">
                        <i data-lucide="log-out" class="w-[14px] h-[14px]"></i>
                     </button>
                  </form>
               </div>
            @endauth
        </div>
    </header>

    <!-- 2. ACTION RIBBON TOOLBAR -->
    <div class="bg-white dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 px-3 py-1.5 flex flex-wrap items-center justify-between gap-2 shrink-0 font-mono text-[11px] z-30">
        <div class="flex flex-wrap items-center gap-1.5">
            <!-- Buat Arsip Baru -->
            <a href="{{ route('archives.create') }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white font-bold transition flex items-center gap-1.5 shadow-2xs {{ request()->routeIs('archives.create') ? 'ring-1 ring-emerald-500 text-emerald-600 dark:text-emerald-400' : '' }}">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                <span>+ Buat Arsip</span>
            </a>

            <!-- Pinjam Berkas -->
            <a href="{{ route('borrowings.create') }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white font-bold transition flex items-center gap-1.5 shadow-2xs {{ request()->routeIs('borrowings.create') ? 'ring-1 ring-purple-500 text-purple-600 dark:text-purple-400' : '' }}">
                <i data-lucide="file-symlink" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400"></i>
                <span>Pinjam</span>
            </a>

            <!-- Cetak Label -->
            <a href="{{ route('archives.print_labels') }}" target="_blank" class="px-2.5 py-1 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 rounded text-amber-700 dark:text-amber-300 font-bold transition flex items-center gap-1.5 shadow-2xs">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                <span>Cetak Label</span>
            </a>

            <!-- Refresh Halaman -->
            <button onclick="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white font-bold transition flex items-center gap-1.5 shadow-2xs" title="Muat Ulang Halaman">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"></i>
                <span>Refresh</span>
            </button>
        </div>

        <div class="flex items-center gap-3 text-[11px] text-slate-500 dark:text-slate-400 font-mono">
            <span>DEPARTEMEN: <strong class="text-amber-600 dark:text-amber-400">{{ auth()->user()->department->name ?? 'Global' }} ({{ auth()->user()->department->code ?? 'GEN' }})</strong></span>
            <span>WORKSTATION: <strong class="text-slate-700 dark:text-slate-200">WS-01</strong></span>
        </div>
    </div>

    <!-- 3. NAV-TAB BAR (PIC DEPARTEMEN TABS) -->
    <nav class="bg-slate-200/90 dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 px-2 pt-1 flex items-center justify-start gap-1 shrink-0 font-mono text-[11px] select-none z-30 overflow-x-auto no-scrollbar">
        <!-- Tab 1: Katalog Arsip -->
        <a href="{{ route('archives.index') }}" 
           class="px-3 py-1.5 rounded-t transition-all flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap {{ request()->routeIs('archives.index') || request()->routeIs('archives.show') ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 border-t-2 border-t-amber-500 border-x border-slate-300 dark:border-slate-700 font-bold shadow-xs -mb-px' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/80 dark:hover:bg-slate-850 font-medium' }}">
           <i data-lucide="folder-archive" class="w-3.5 h-3.5 {{ request()->routeIs('archives.index') || request()->routeIs('archives.show') ? 'text-amber-500' : 'text-slate-400' }}"></i>
           <span>Katalog Arsip {{ auth()->user()->department->code ?? '' }}</span>
        </a>

        <!-- Tab 2: Draft Baru Arsip -->
        <a href="{{ route('archives.create') }}" 
           class="px-3 py-1.5 rounded-t transition-all flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap {{ request()->routeIs('archives.create') ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 border-t-2 border-t-emerald-500 border-x border-slate-300 dark:border-slate-700 font-bold shadow-xs -mb-px' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/80 dark:hover:bg-slate-850 font-medium' }}">
           <i data-lucide="plus-circle" class="w-3.5 h-3.5 {{ request()->routeIs('archives.create') ? 'text-emerald-500' : 'text-slate-400' }}"></i>
           <span>+ Draft Baru</span>
        </a>

        <!-- Tab 3: Peminjaman Dokumen -->
        <a href="{{ route('borrowings.index') }}" 
           class="px-3 py-1.5 rounded-t transition-all flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap {{ request()->routeIs('borrowings.*') ? 'bg-white dark:bg-slate-900 text-purple-600 dark:text-purple-400 border-t-2 border-t-purple-500 border-x border-slate-300 dark:border-slate-700 font-bold shadow-xs -mb-px' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/80 dark:hover:bg-slate-850 font-medium' }}">
           <i data-lucide="file-check-2" class="w-3.5 h-3.5 {{ request()->routeIs('borrowings.*') ? 'text-purple-500' : 'text-slate-400' }}"></i>
           <span>Peminjaman Berkas</span>
        </a>

        <!-- Tab 4: Perusahaan Entitas -->
        <a href="{{ route('master.companies') }}" 
           class="px-3 py-1.5 rounded-t transition-all flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap {{ request()->routeIs('master.companies*') ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 border-t-2 border-t-indigo-500 border-x border-slate-300 dark:border-slate-700 font-bold shadow-xs -mb-px' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/80 dark:hover:bg-slate-850 font-medium' }}">
           <i data-lucide="landmark" class="w-3.5 h-3.5 {{ request()->routeIs('master.companies*') ? 'text-indigo-500' : 'text-slate-400' }}"></i>
           <span>Perusahaan Entitas</span>
        </a>

        <!-- Tab 5: Katalog Dokumen -->
        <a href="{{ route('master.document_types') }}" 
           class="px-3 py-1.5 rounded-t transition-all flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap {{ request()->routeIs('master.document_types*') ? 'bg-white dark:bg-slate-900 text-teal-600 dark:text-teal-400 border-t-2 border-t-teal-500 border-x border-slate-300 dark:border-slate-700 font-bold shadow-xs -mb-px' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/80 dark:hover:bg-slate-850 font-medium' }}">
           <i data-lucide="file-text" class="w-3.5 h-3.5 {{ request()->routeIs('master.document_types*') ? 'text-teal-500' : 'text-slate-400' }}"></i>
           <span>Katalog Dokumen</span>
        </a>

        <!-- Tab 6: Expiry Retention -->
        <a href="{{ route('destructions.index') }}" 
           class="px-3 py-1.5 rounded-t transition-all flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap {{ request()->routeIs('destructions.*') ? 'bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-400 border-t-2 border-t-rose-500 border-x border-slate-300 dark:border-slate-700 font-bold shadow-xs -mb-px' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/80 dark:hover:bg-slate-850 font-medium' }}">
           <i data-lucide="shield-alert" class="w-3.5 h-3.5 {{ request()->routeIs('destructions.*') ? 'text-rose-500' : 'text-slate-400' }}"></i>
           <span>Expiry Retention</span>
        </a>

        <!-- Tab 7: Audit Log -->
        <a href="{{ route('logs.index') }}" 
           class="px-3 py-1.5 rounded-t transition-all flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap {{ request()->routeIs('logs.*') ? 'bg-white dark:bg-slate-900 text-cyan-600 dark:text-cyan-400 border-t-2 border-t-cyan-500 border-x border-slate-300 dark:border-slate-700 font-bold shadow-xs -mb-px' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/80 dark:hover:bg-slate-850 font-medium' }}">
           <i data-lucide="history" class="w-3.5 h-3.5 {{ request()->routeIs('logs.*') ? 'text-cyan-500' : 'text-slate-400' }}"></i>
           <span>Audit Trail</span>
        </a>
    </nav>

    <!-- 4. FULL-CANVAS WORKSPACE VIEWPORT -->
    <main class="flex-1 bg-slate-100 dark:bg-slate-950 p-3 md:p-4 overflow-y-auto font-sans">
        <!-- Flash Notification Banners -->
        @if (session('success'))
            <div class="mb-3 p-2.5 rounded bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5 font-mono shadow-sm">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5"></i>
                <div class="font-bold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="mb-3 p-2.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 flex items-start gap-2.5 font-mono shadow-sm">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
                <div class="font-bold">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-3 p-2.5 rounded bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 flex items-start gap-2.5 font-mono shadow-sm">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5"></i>
                <div class="font-bold">{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- 5. BOTTOM STATUS BAR PANEL (TStatusBar) -->
    <footer class="bg-slate-900 text-slate-300 text-[11px] px-3 py-1 flex items-center justify-between border-t border-slate-800 shrink-0 font-mono z-30 select-none">
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-emerald-400 font-bold">
               <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> SYSTEM READY
            </span>

            <span class="text-slate-700">|</span>

            <!-- CPU & MEMORY RESOURCE USAGE MONITOR -->
            <style>
               .pc-info-value { min-width: 30px; }
            </style>
            <div class="flex items-center gap-2 text-[10px]">
               <span class="px-1.5 py-0.2 bg-slate-800 text-sky-300 border border-slate-700 rounded font-bold flex items-center gap-1" title="Penggunaan CPU Workstation">
                  <i data-lucide="cpu" class="w-3 h-3 text-sky-400"></i>
                  <span>CPU: </span>
                  <span class="pc-info-value text-center"><strong x-text="cpuUsage + '%'">12%</strong></span>
               </span>

               <span class="px-1.5 py-0.2 bg-slate-800 text-purple-300 border border-slate-700 rounded font-bold flex items-center gap-1" title="Penggunaan Memori RAM Workstation">
                  <i data-lucide="hard-drive" class="w-3 h-3 text-purple-400"></i>
                  <span>MEM: </span>
                  <span class="pc-info-value text-center"><strong x-text="memUsage + '%'">38%</strong></span>
               </span>
            </div>
        </div>

        <div class="flex items-center gap-3 text-slate-400 text-[10px]">
            <span>USER: <strong class="text-white">{{ auth()->check() ? auth()->user()->name : 'Guest' }}</strong> ({{ auth()->check() ? auth()->user()->role_label : '' }})</span>
            <span class="text-slate-700">|</span>
            <span>Developed by WebDev &copy; 2026 Indraco Global Indonesia</span>
        </div>
    </footer>

    <!-- Alpine.js Application Component Script -->
    <script>
        function navTabPicLayout() {
            return {
                theme: localStorage.getItem('theme') || 'light',
                isFullscreen: !!document.fullscreenElement,
                cpuUsage: 14,
                memUsage: 38,

                init() {
                    this.startSystemMonitor();
                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    });
                },

                toggleTheme() {
                    this.theme = (this.theme === 'dark' ? 'light' : 'dark');
                    localStorage.setItem('theme', this.theme);
                    if (this.theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().then(() => {
                            this.isFullscreen = true;
                        }).catch(err => {});
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen().then(() => {
                                this.isFullscreen = false;
                            }).catch(err => {});
                        }
                    }
                },

                startSystemMonitor() {
                    this.updateStats();
                    setInterval(() => this.updateStats(), 3500);
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
                }
            }
        }
        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
