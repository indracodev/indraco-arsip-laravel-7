<!DOCTYPE html>
<html lang="id" class="h-full" style="font-size: {{ $fontSizeScale }};">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DMS PT Indraco - Workstation Form')</title>
    
    @include('layouts.partials.head_assets')

    <script>
        (function() {
            var stored = localStorage.getItem('app_font_size');
            if (stored) {
                document.documentElement.style.fontSize = stored;
            }
            window.addEventListener('storage', function(e) {
                if (e.key === 'app_font_size' && e.newValue) {
                    document.documentElement.style.fontSize = e.newValue;
                }
            });
            window.addEventListener('message', function(e) {
                if (e.data && e.data.type === 'SET_FONT_SIZE' && e.data.fontSize) {
                    document.documentElement.style.fontSize = e.data.fontSize;
                }
            });
        })();
    </script>

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

        /* Proportional rem scaling for Tailwind utility and arbitrary classes according to root font-size */
        html {
            font-size: {{ $fontSizeScale }};
        }
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

        main table { border-collapse: separate; border-spacing: 0; font-size: 0.85rem; }
        main table th { background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%); border-right: 1px solid #cbd5e1; border-bottom: 2px solid #94a3b8; color: #1e293b; padding-top: 7px; padding-bottom: 7px; }
        .dark main table th { background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right: 1px solid #334155; border-bottom: 2px solid #475569; color: #f8fafc; }
        main table td { border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding-top: 7px; padding-bottom: 7px; }
        .dark main table td { border-right: 1px solid #1e293b; border-bottom: 1px solid #1e293b; }

        @keyframes indeterminate-progress {
            0% { transform: translateX(-100%) scaleX(0.2); }
            50% { transform: translateX(30%) scaleX(0.6); }
            100% { transform: translateX(200%) scaleX(0.2); }
        }
        .animate-indeterminate {
            width: 100%;
            transform-origin: 0% 50%;
            animation: indeterminate-progress 1.5s infinite cubic-bezier(0.65, 0.815, 0.735, 0.395);
        }

        @keyframes warning-blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.45; transform: scale(0.98); }
        }
        .animate-warning-blink {
            animation: warning-blink 1.4s ease-in-out infinite;
        }
    </style>
</head>
<body class="h-full bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 p-3 sm:p-4 overflow-y-auto font-sans text-xs">
    @if (session('success'))
    <div class="mb-3 p-2.5 rounded bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5 text-xs font-mono shadow-sm">
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5"></i>
        <div class="font-bold flex-1">{{ session('success') }}</div>
    </div>
    <script>
        try {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({
                    type: 'DMS_TOAST_NOTIF',
                    level: 'success',
                    title: 'BERHASIL DISIMPAN!',
                    statusLabel: 'Tersimpan',
                    message: @json(session('success'))
                }, '*');
            }
        } catch(e) {}
    </script>
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
    @include('layouts.partials.confirm_modal')
    @include('layouts.partials.file_preview_modal')
    @include('layouts.partials.label_print_modal')
    @stack('scripts')
</body>
</html>
