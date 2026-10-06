<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman / Data Tidak Ditemukan | DMS PT Indraco</title>
    @include('layouts.partials.head_assets')
    <style>
        .delphi-window {
            box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 1px 1px 0 rgba(255,255,255,0.15);
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased">
    <div class="delphi-window w-full max-w-lg bg-slate-800 border-2 border-slate-600 rounded-lg shadow-2xl overflow-hidden">
        <!-- Titlebar -->
        <div class="bg-linear-to-r from-sky-900 via-sky-800 to-slate-800 px-4 py-2 border-b border-sky-700/50 flex items-center justify-between">
            <div class="flex items-center gap-2 font-mono text-xs font-bold text-sky-200">
                <i data-lucide="file-question" class="w-4 h-4 text-sky-400"></i>
                <span>RECORD NOT FOUND - PT INDRACO DMS</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-sky-500/80 inline-block"></span>
            </div>
        </div>

        <!-- Window Body -->
        <div class="p-6 space-y-4">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-sky-500/10 border border-sky-500/30 rounded-xl text-sky-400 shrink-0">
                    <i data-lucide="search-x" class="w-8 h-8"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white mb-1">Data Tidak Ditemukan (404)</h1>
                    <p class="text-slate-300 text-xs leading-relaxed">
                        Data arsip, lokasi rak, atau halaman yang Anda tuju tidak ditemukan di database atau tautan telah berpindah.
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex items-center gap-2.5">
                <a href="{{ route('dashboard') }}" 
                   class="flex-1 px-4 py-2 bg-amber-600 hover:bg-amber-500 rounded text-xs font-semibold text-slate-950 flex items-center justify-center gap-1.5 transition active:scale-95">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    <span>Kembali ke Dashboard</span>
                </a>
                <button type="button" 
                        onclick="history.back()"
                        class="px-4 py-2 bg-slate-700 hover:bg-slate-600 border border-slate-600 rounded text-xs font-semibold text-white flex items-center justify-center gap-1.5 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Halaman Sebelumnya</span>
                </button>
            </div>
        </div>

        <!-- Window Footer -->
        <div class="px-4 py-2 bg-slate-950 text-slate-500 text-[11px] font-mono border-t border-slate-800 flex items-center justify-between">
            <span>DMS INDRACO v3.0 Offline</span>
            <span>URL: {{ request()->path() }}</span>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
