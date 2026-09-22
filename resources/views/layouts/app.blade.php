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
    <!-- Flash Banners -->
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
<html lang="id" 
      x-data="desktopAppLayout()" 
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
    <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 px-4 py-1.5 shadow-md flex items-center justify-between z-50 text-xs font-bold border-b border-amber-600 shrink-0 font-mono">
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

    <!-- 1. WINDOW TITLE BAR & WORKSTATION HEADER -->
    <header class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-700 shadow-sm shrink-0 font-mono relative z-50">
        <div class="flex items-center gap-3 flex-1 min-w-0 mr-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group shrink-0">
                <div class="p-1 bg-white rounded shadow-sm">
                    <img src="{{ asset('images/logo-indraco-est.png') }}" alt="PT Indraco Logo" class="h-5 w-auto object-contain">
                </div>
                <span class="font-extrabold tracking-tight text-white flex items-center gap-1.5 text-xs whitespace-nowrap">
                    DMS <span class="text-amber-400 font-black">PT INDRACO</span>
                    <span class="px-1.5 py-0.5 bg-amber-500/20 text-amber-300 text-[10px] rounded border border-amber-400/30">Desktop Edition</span>
                </span>
            </a>

            <!-- SUPER ADMIN GLOBAL QUICK SEARCH (ALL DOCUMENTS) -->
            <div class="relative z-50 flex-1 min-w-[320px] max-w-2xl ml-2" x-data="superAdminQuickSearch()" @click.outside="closeDropdown()">
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <svg x-show="isLoading" class="w-4 h-4 text-amber-400 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                        </svg>
                        <svg x-show="!isLoading" class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>

                    <input 
                        type="text" 
                        id="adminHeaderSearchInput"
                        x-model="searchQuery"
                        @input.debounce.250ms="doSearch()"
                        @focus="showSuggestions = true; if (!searchQuery) loadDefaultSuggestions()"
                        @keydown.escape="closeDropdown()"
                        @keydown.arrow-down.prevent="navigateResults(1)"
                        @keydown.arrow-up.prevent="navigateResults(-1)"
                        @keydown.enter.prevent="selectActiveResult()"
                        placeholder="Cari semua dokumen (nama arsip, dept, butir isi, no. box, rak gudang) [Ctrl+F]..." 
                        class="w-full pl-9 pr-8 py-1 bg-slate-950/80 border border-slate-700 hover:border-slate-600 focus:border-amber-400 rounded text-xs font-mono text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-amber-400 transition shadow-inner"
                    >

                    <!-- Clear Search Button -->
                    <button 
                        x-show="searchQuery.length > 0" 
                        @click="clearSearch()"
                        type="button" 
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-rose-400 transition cursor-pointer"
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
                    class="absolute left-0 mt-1.5 w-[680px] sm:w-[760px] max-w-[94vw] bg-white dark:bg-slate-900 border-2 border-amber-500 rounded-lg shadow-2xl z-[100] overflow-hidden font-mono text-xs text-slate-800 dark:text-slate-100"
                    x-cloak
                >
                    <!-- Header Dropdown Info -->
                    <div class="px-3 py-2 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex items-center justify-between border-b border-slate-700 text-[11px]">
                        <span class="font-bold flex items-center gap-2 text-amber-300">
                            <svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            PENCARIAN GLOBAL SELURUH ARSIP & LOKASI GUDANG
                        </span>
                        <span class="px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-400/40 rounded text-[11px] font-bold">
                            SUPER ADMIN • ALL DEPARTMENTS
                        </span>
                    </div>

                    <!-- Suggestion / Quick Filters Chips -->
                    <div x-show="suggestedKeywords.length > 0" class="px-3.5 py-2 bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1.5">Filter Cepat / Topik Dokumen:</span>
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
                                <!-- Top Row: No Box, Dept Badge, Status Badge, Period -->
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-black text-amber-600 dark:text-amber-400 text-xs sm:text-sm flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                            </svg>
                                            <span x-text="item.box_number || 'Penomoran Pending'"></span>
                                        </span>

                                        <!-- Department Badge -->
                                        <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/20 text-[10px] font-black uppercase" x-text="item.dept_code + (item.sub_dept ? ' / ' + item.sub_dept : '')"></span>
                                    </div>

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
                                        Buka Detail Form 
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
                            <p class="text-[11px] text-slate-500 mt-0.5">Tidak ditemukan berkas dengan kata kunci "<span class="font-bold text-amber-600 dark:text-amber-400" x-text="searchQuery"></span>"</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right System Info Controls -->
        <div class="flex items-center gap-3">
            <!-- Theme Toggle Button -->
            <button 
                @click="theme = (theme === 'dark' ? 'light' : 'dark'); localStorage.setItem('theme', theme)" 
                type="button" 
                class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded text-[11px] font-mono flex items-center gap-1.5 transition" 
                title="Ganti Mode Tampilan (Alt+T)"
            >
                <template x-if="theme === 'dark'">
                    <span class="flex items-center gap-1 text-amber-300"><i data-lucide="sun" class="w-3 h-3"></i> Light Mode</span>
                </template>
                <template x-if="theme !== 'dark'">
                    <span class="flex items-center gap-1 text-sky-300"><i data-lucide="moon" class="w-3 h-3"></i> Dark Mode</span>
                </template>
            </button>

            <!-- Fullscreen / Maximize Toggle Button -->
            <button 
                @click="toggleFullscreen()" 
                type="button" 
                :title="isFullscreen ? 'Keluar Full Screen (Esc / F11)' : 'Layar Penuh (Full Screen / Maximize)'"
                class="w-6 h-6 flex items-center justify-center bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded text-amber-400 font-bold transition active:scale-95 shrink-0"
            >
                <template x-if="isFullscreen">
                    <span class="text-[13px] font-black leading-none select-none">❐</span>
                </template>
                <template x-if="!isFullscreen">
                    <span class="text-[13px] font-black leading-none select-none">🗖</span>
                </template>
            </button>

            @auth
            <div class="flex items-center gap-2">
                <span class="text-slate-300 font-bold text-[11px]">{{ auth()->user()->name }}</span>
                <span class="px-2 py-0.5 bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded text-[10px] font-bold">
                    {{ auth()->user()->role_label }}
                </span>

                <form action="{{ route('logout') }}" method="POST" class="inline ml-1">
                    @csrf
                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded transition" title="Keluar Aplikasi">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </header>

    <!-- 2. DELPHI MDI TABBED WORKSPACE SHEET BAR (FIXED NAV BAR) -->
    <div class="bg-slate-200/80 dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 px-2 pt-1 flex items-center justify-start gap-1 shrink-0 font-mono text-[11px] select-none relative z-20 overflow-x-auto no-scrollbar">
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

    <!-- 3. MAIN VIEWPORT CONTAINER (MDI Workstation Desktop Canvas) -->
    <main class="flex-1 bg-slate-200 dark:bg-slate-950 p-2 sm:p-4 overflow-hidden relative min-w-0 desktop-bg-pattern flex items-center justify-center font-sans">
        
        <!-- Empty Workspace Placeholder (When all windows closed) -->
        <div x-show="openWindows.length === 0" class="my-auto text-center space-y-3 font-mono">
            <div class="p-4 bg-slate-300 dark:bg-slate-800/80 rounded-2xl w-16 h-16 mx-auto flex items-center justify-center text-slate-500 dark:text-slate-400 shadow-inner">
                <i data-lucide="layout" class="w-8 h-8 text-amber-500"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase">DOCUMENT MANAGEMENT SYSTEM</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Klik salah satu menu form di atas untuk membuka form window.</p>
        </div>

        <!-- RECURSIVE MDI WINDOW FRAMES (Allows Multiple Forms Open Simultaneously) -->
        <template x-for="win in openWindows" :key="win.id">
            <div 
                x-show="!win.minimized" 
                @mousedown="focusWindow(win.id)"
                :class="win.maximized ? 'absolute inset-0 z-20 w-full h-full rounded-none my-0 border-0 shadow-none' : 'absolute rounded-t-lg rounded-b-sm border-2 border-slate-400 dark:border-slate-700 shadow-2xl resize overflow-hidden'"
                :style="getWindowStyle(win) + (win.maximized ? '' : 'width: 820px; max-width: 90vw; height: 530px; max-height: 75vh; min-width: 420px; min-height: 280px;')"
                class="delphi-window bg-slate-100 dark:bg-slate-900 flex flex-col transition-shadow duration-150"
            >
                <!-- WINDOW TITLE BAR (Draggable Desktop Caption & Controls) -->
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
                        :src="win.url" 
                        :class="activeDragWin ? 'pointer-events-none' : ''"
                        class="w-full h-full border-0 block"
                    ></iframe>
                </div>
            </div>
        </template>
    </main>

    <!-- 4. BOTTOM STATUS BAR PANEL (TStatusBar) -->
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
                    class="px-2 py-0.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/50 rounded text-[10px] font-bold flex items-center gap-1 transition active:scale-95"
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
                            class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-bold rounded border border-slate-700 flex items-center gap-1 transition shadow active:scale-95"
                            title="Susun Jendela Secara Bertingkat (Cascade)"
                        >
                            <i data-lucide="layers" class="w-3 h-3 text-amber-400"></i>
                            <span>Cascade</span>
                        </button>
                        <button 
                            @click="tileWindows()" 
                            type="button" 
                            class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-bold rounded border border-slate-700 flex items-center gap-1 transition shadow active:scale-95"
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

            <div class="hidden md:flex items-center gap-3 text-slate-400 border-l border-slate-800 pl-3">
                <span>SUPER ADMIN: <strong class="text-white">{{ auth()->user()->name }}</strong> (Global)</span>
                <span class="text-slate-500">|</span>
                <span>develope by Web Dev Indraco</span>
            </div>
        </div>
    </footer>

    <!-- Lucide Icons & Desktop Hotkeys Script -->
    <script>
        function desktopAppLayout() {
            return {
                theme: localStorage.getItem('theme') || 'light',
                openWindows: [],
                activeWinId: null,
                maxZIndex: 10,
                activeDragWin: null,
                showAddMenu: false,
                isFullscreen: false,
                cpuUsage: 12,
                memUsage: 38,

                availableForms: [
                    { id: 'dashboard', title: 'Dashboard Overview', icon: 'layout-dashboard', url: '{{ route("dashboard") }}?embed=1' },
                    { id: 'departments', title: 'Master Departemen', icon: 'building-2', url: '{{ route("master.departments") }}?embed=1' },
                    { id: 'warehouses', title: 'Master Gudang & Rak', icon: 'warehouse', url: '{{ route("master.warehouses") }}?embed=1' },
                    { id: 'warehouse_layout', title: 'Layout Gudang 2D', icon: 'map', url: '{{ route("master.warehouses.layout") }}?embed=1' },
                    @if(auth()->check() && auth()->user()->isSuperAdmin())
                    { id: 'numbering', title: 'Format Penomoran Box', icon: 'binary', url: '{{ route("master.numbering") }}?embed=1' },
                    @endif
                    { id: 'users', title: 'Kelola User & Hak Akses', icon: 'users', url: '{{ route("master.users") }}?embed=1' },
                    { id: 'archives', title: 'Katalog Arsip', icon: 'folder-archive', url: '{{ route("archives.index") }}?embed=1' },
                    { id: 'logs', title: 'Audit Trail', icon: 'history', url: '{{ route("logs.index") }}?embed=1' }
                ],

                initMdi() {
                    let initialId = 'dashboard';
                    @if(request()->routeIs('master.departments')) initialId = 'departments';
                    @elseif(request()->routeIs('master.warehouses')) initialId = 'warehouses';
                    @elseif(request()->routeIs('master.warehouses.layout')) initialId = 'warehouse_layout';
                    @elseif(request()->routeIs('master.numbering')) initialId = 'numbering';
                    @elseif(request()->routeIs('master.users')) initialId = 'users';
                    @elseif(request()->routeIs('archives.*')) initialId = 'archives';
                    @elseif(request()->routeIs('logs.*')) initialId = 'logs';
                    @endif

                    this.openFormWindow(initialId);
                    this.startSystemMonitor();

                    window.addEventListener('open-form-window', (e) => {
                        if (e.detail) {
                            this.openFormWindowWithCustom(e.detail.id, e.detail.title, e.detail.icon, e.detail.url);
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
                    this.showAddMenu = false;
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

        function superAdminQuickSearch() {
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
                        console.error('Super Admin Search API error:', err);
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
                        console.error('Super Admin Search query error:', err);
                    } finally {
                        this.isLoading = false;
                    }
                },

                selectKeyword(keyword) {
                    this.searchQuery = keyword;
                    this.doSearch();
                },

                selectArchive(archive) {
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
            lucide.createIcons();

            // Desktop Keyboard Shortcuts Handler
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F5') {
                    e.preventDefault();
                    window.location.reload();
                }
                // Ctrl + F: Focus Super Admin Search Input
                else if (e.ctrlKey && e.key.toLowerCase() === 'f') {
                    e.preventDefault();
                    const searchInput = document.getElementById('adminHeaderSearchInput');
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

