@php
    $configuredFontSize = $globalAppFontSize ?? config('app.font_size', env('APP_FONT_SIZE', '19px'));
    $lowerFontSize = strtolower($configuredFontSize);
    if (in_array($lowerFontSize, ['small', 'sm'])) {
        $fontSizeScale = '16px';
    } elseif (in_array($lowerFontSize, ['large', 'lg'])) {
        $fontSizeScale = '21px';
    } elseif (in_array($lowerFontSize, ['xlarge', 'xl'])) {
        $fontSizeScale = '23px';
    } elseif (\Illuminate\Support\Str::contains($configuredFontSize, 'px') || \Illuminate\Support\Str::contains($configuredFontSize, '%') || \Illuminate\Support\Str::contains($configuredFontSize, 'rem')) {
        $fontSizeScale = $configuredFontSize;
    } else {
        $fontSizeScale = '19px';
    }
@endphp
@if(request()->has('embed') || request()->header('X-MDI-Embed') || request()->header('Sec-Fetch-Dest') === 'iframe' || \Illuminate\Support\Str::contains(request()->header('referer', ''), 'embed=1'))
    @include('layouts.partials.pic_desktop_embed')
@else
<!DOCTYPE html>
@php
    $configuredFontSize = config('app.font_size', env('APP_FONT_SIZE', '19px'));
    $form3BadgeCount = \App\Models\Archive::getForm3PendingCount();
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
      :style="'font-size: ' + currentFontSize + ';'"
      style="font-size: {{ $fontSizeScale }};"
      class="h-full">
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
    
    <!-- App Icon & Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/icon_indraco.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/icon_indraco.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/icon_indraco.png') }}">

    <!-- PWA Manifest & Theme -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#d97706">
    
    @include('layouts.partials.head_assets')

    <style>
        [x-cloak] { display: none !important; }

        /* Enable text selection and copy-paste for all input, editable, and readable content */
        html,
        body {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }

        input, textarea, select, [contenteditable="true"], .select-text, .select-text * {
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            user-select: text !important;
        }

        ::selection {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }

        ::-moz-selection {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        .no-scrollbar {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }

        /* Proportional rem scaling for Tailwind utility and arbitrary classes according to root font-size */
        body {
            font-size: 0.85rem;
        }
        .text-\[8px\] { font-size: 0.55rem !important; }
        .text-\[9px\] { font-size: 0.65rem !important; }
        .text-\[10px\] { font-size: 0.72rem !important; }
        .text-\[11px\] { font-size: 0.80rem !important; }
        .text-\[12px\] { font-size: 0.88rem !important; }
        .text-\[13px\] { font-size: 0.95rem !important; }
        .text-\[14px\] { font-size: 1.00rem !important; }
        .text-\[15px\] { font-size: 1.08rem !important; }
        .text-\[16px\] { font-size: 1.15rem !important; }
        .text-xs { font-size: 0.85rem !important; }
        .text-sm { font-size: 0.95rem !important; }
        .text-base { font-size: 1.05rem !important; }
        .text-lg { font-size: 1.20rem !important; }
        .text-xl { font-size: 1.35rem !important; }
        .text-2xl { font-size: 1.65rem !important; }
        .text-3xl { font-size: 2.00rem !important; }
        
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

    @include('layouts.partials.pic_desktop_header')
    <!-- 2. MDI TAB SHEET NAVIGATION MANAGER (FIXED TAB BAR) -->
    <div class="bg-slate-200/90 dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 px-2 pt-1 flex items-center justify-start gap-1 shrink-0 font-mono text-[11px] select-none z-20 relative overflow-x-auto no-scrollbar">
        <template x-for="form in availableForms.filter(f => !f.hideInTabBar)" :key="form.id">
            <button 
                @click="openFormWindow(form.id)"
                type="button"
                :class="isTabActive(form.id) 
                    ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 border-t-2 border-t-amber-500 border-x border-slate-300 dark:border-slate-700 font-bold shadow-sm' 
                    : (isWindowOpen(form.id)
                        ? 'bg-slate-300/70 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 border-t-2 border-t-transparent border-x border-slate-300 dark:border-slate-700 font-semibold hover:bg-slate-200'
                        : 'bg-slate-200/50 dark:bg-slate-950 text-slate-500 dark:text-slate-500 border-t-2 border-t-transparent border-x border-transparent font-medium hover:bg-slate-200/80')"
                class="px-3 py-1.5 rounded-t text-xs transition-colors flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap"
            >
                <i :data-lucide="form.icon" class="w-3.5 h-3.5" :class="isTabActive(form.id) ? 'text-amber-500' : (isWindowOpen(form.id) ? 'text-amber-500/70' : 'text-slate-400')"></i>
                <span x-text="form.title"></span>
                <span 
                    x-show="form.badgeCount && form.badgeCount > 0" 
                    x-text="form.badgeCount" 
                    class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold leading-none bg-rose-500 text-white shadow-xs animate-pulse"
                    title="Jumlah Berkas Butuh Tindakan"
                ></span>
            </button>
        </template>
    </div>

    <!-- 4. MAIN VIEWPORT (MDI Multi-Window Workstation Desktop Canvas) -->
    <main class="flex-1 bg-slate-200 dark:bg-slate-950 p-2 sm:p-4 overflow-hidden relative z-10 min-w-0 desktop-bg-pattern flex items-center justify-center font-sans">
        
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
                :style="getWindowStyle(win) + (win.maximized ? '' : 'width: 980px; max-width: 96vw; height: 580px; max-height: 82vh; min-width: 480px; min-height: 320px;')"
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
                            title="Tutup Seluruh Jendela Halaman Ini (✕)" 
                            class="w-6 h-6 flex items-center justify-center bg-rose-600 hover:bg-rose-500 border border-rose-500 rounded text-white text-xs font-black transition active:scale-95 cursor-pointer shadow-xs"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- WINDOW IFRAME CONTAINER (ISOLATED FORM EMBED CONTENT) -->
                <div class="flex-1 bg-slate-100 dark:bg-slate-900 relative overflow-hidden flex flex-col">
                    <!-- Modern Desktop Loading Overlay (Progress Bar & Spinner) -->
                    <div 
                        x-show="win.loading" 
                        x-transition:leave="transition ease-out duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0 pointer-events-none"
                        class="absolute inset-0 z-30 bg-slate-100/95 dark:bg-slate-900/95 backdrop-blur-xs flex flex-col items-center justify-center p-6 text-center select-none"
                    >
                        <!-- Animated Modern Spinner -->
                        <div class="relative flex items-center justify-center mb-3">
                            <div class="w-12 h-12 rounded-full border-[3px] border-slate-300 dark:border-slate-800 border-t-amber-500 dark:border-t-amber-400 animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <i :data-lucide="win.icon || 'layout'" class="w-5 h-5 text-amber-500"></i>
                            </div>
                        </div>

                        <!-- Title & Status Text -->
                        <div class="space-y-1 font-mono">
                            <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">
                                <span>Memuat Form</span>
                                <span class="text-amber-600 dark:text-amber-400" x-text="win.title"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">Menyiapkan antarmuka & memuat data...</p>
                        </div>

                        <!-- Animated Indeterminate Progress Bar -->
                        <div class="w-52 sm:w-64 h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden mt-3.5 border border-slate-300 dark:border-slate-700 relative">
                            <div class="animate-indeterminate h-full bg-gradient-to-r from-amber-500 via-amber-400 to-amber-600 rounded-full"></div>
                        </div>

                        <div class="text-[10px] font-mono text-slate-400 mt-2 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span>DMS PT INDRACO DESKTOP ENGINE</span>
                        </div>
                    </div>

                    <iframe 
                        :id="'iframe-' + win.id"
                        :src="win.url" 
                        :class="activeDragWin ? 'pointer-events-none' : ''"
                        @load="win.loading = false; applyFontSizeToIframe($event.target)"
                        class="w-full h-full border-0 block flex-1"
                    ></iframe>
                </div>
            </div>
        </template>

        <!-- REAL-TIME FLOATING TOAST NOTIFICATIONS (TOP-RIGHT) -->
        <div class="fixed top-24 right-3 sm:right-5 z-[9999] flex flex-col gap-2.5 max-w-sm sm:max-w-md pointer-events-none font-mono">
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
                            <span x-text="toast.headerTitle || 'DOKUMEN BARU MASUK!'"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-400 font-bold" x-text="toast.time"></span>
                            <button @click="dismissToast(toast.id)" class="text-slate-400 hover:text-rose-500 p-0.5 rounded transition cursor-pointer">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div @click="openArchiveFromNotification(toast)" class="space-y-1 cursor-pointer hover:opacity-90 transition group">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-black bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30" x-text="toast.deptCode + ' (' + toast.deptName + ')'"></span>
                            <span x-show="toast.boxNumber" class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="toast.boxNumber"></span>
                            <span class="px-1.5 py-0.5 rounded text-xs font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20" x-text="toast.statusLabel || 'Tersimpan'"></span>
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white leading-tight line-clamp-2 font-sans group-hover:text-amber-500 transition-colors" x-text="toast.title"></div>
                        <div x-show="toast.creatorName" class="text-[11px] text-slate-500 dark:text-slate-400">
                            Diproses oleh: <strong class="text-slate-800 dark:text-slate-200" x-text="toast.creatorName"></strong>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-200 dark:border-slate-800 text-xs">
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> Realtime Sync
                        </span>
                        <div class="flex items-center gap-1.5">
                            <template x-if="toast.url || toast.archiveId">
                                <button 
                                    @click="openArchiveFromNotification(toast)" 
                                    type="button" 
                                    class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded border border-amber-600 text-xs transition flex items-center gap-1 shadow-sm active:scale-95 cursor-pointer"
                                >
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    <span x-text="(toast.archiveId || (toast.url && toast.url.includes('/archives/'))) ? 'Buka Detail' : 'Buka Halaman'"></span>
                                </button>
                            </template>
                            <button @click="dismissToast(toast.id)" type="button"
                                class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded border border-slate-300 dark:border-slate-700 text-xs transition flex items-center gap-1 shadow-sm active:scale-95 cursor-pointer">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Tutup</span>
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

                <!-- Setting Button (F9) -->
                <button 
                    @click="openSettings()" 
                    type="button" 
                    class="px-2.5 py-0.5 bg-slate-800 hover:bg-slate-700 text-amber-300 hover:text-white text-[10px] font-bold rounded border border-slate-700 flex items-center gap-1.5 transition shadow active:scale-95 cursor-pointer ml-1"
                    title="Pengaturan Tampilan Sistem (Ganti Logo & Ukuran Font) - Shortcut F9"
                >
                    <i data-lucide="settings" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Setting (F9)</span>
                </button>
            </div>

            <div class="hidden sm:flex items-center gap-3 text-slate-400 border-l border-slate-800 pl-3">
                <span>USER: <strong class="text-white">{{ auth()->user()->name }}</strong> ({{ auth()->user()->department->code ?? 'DEPT' }})</span>
                <span class="text-slate-500">|</span>
                <span>develope by Web Dev Indraco</span>
            </div>
        </div>
    </footer>

    <!-- ========================================================================= -->
    @include('layouts.partials.pic_system_settings_modal')

    @include('layouts.partials.pic_desktop_scripts')
    @include('layouts.partials.confirm_modal')
    @include('layouts.partials.file_preview_modal')
    @include('layouts.partials.label_print_modal')
    @include('layouts.partials.expiry_warning_modal')
    @stack('scripts')
</body>
</html>
@endif
