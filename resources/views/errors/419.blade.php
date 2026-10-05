<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Sesi Kedaluwarsa | DMS PT Indraco</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
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
    <style>
        .delphi-window {
            box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 1px 1px 0 rgba(255,255,255,0.15);
        }
        .desktop-bg-pattern {
            background-image: radial-gradient(rgba(148, 163, 184, 0.25) 1px, transparent 1px);
            background-size: 16px 16px;
        }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden bg-slate-900 text-slate-100 flex flex-col font-sans select-none desktop-bg-pattern items-center justify-center p-4">

    <div class="delphi-window bg-slate-900 border-2 border-amber-500/50 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
        <!-- Title Bar -->
        <div class="bg-gradient-to-r from-amber-700 via-amber-600 to-amber-800 text-slate-950 px-3.5 py-2 flex items-center justify-between font-bold text-xs">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-4 h-4 text-slate-950"></i>
                <span class="tracking-wide">DMS PT Indraco - Sesi Keamanan Berakhir</span>
            </div>
            <span class="px-1.5 py-0.5 bg-slate-950/30 text-slate-950 text-[10px] rounded font-bold">Error 419</span>
        </div>

        <!-- Content Area -->
        <div class="p-5 text-center space-y-4">
            <div class="w-14 h-14 mx-auto rounded-full bg-amber-500/10 border-2 border-amber-500/30 flex items-center justify-center text-amber-400">
                <i data-lucide="refresh-cw" class="w-7 h-7 animate-spin" style="animation-duration: 4s;"></i>
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-100">Sesi Halaman Telah Kedaluwarsa</h2>
                <p class="text-xs text-slate-400 mt-1 font-sans">
                    Token keamanan sesi Anda telah diperbarui setelah proses login/logout sebelumnya. Anda akan dialihkan secara otomatis ke halaman login.
                </p>
            </div>

            <!-- Countdown Box -->
            <div class="bg-slate-800/80 border border-slate-700 rounded-lg p-3 flex items-center justify-between text-xs">
                <span class="text-slate-400">Otomatis dialihkan dalam:</span>
                <span class="text-amber-400 font-bold font-mono text-sm" id="countdown">2 detik</span>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex items-center justify-center gap-2">
                <a href="{{ route('login') }}" class="w-full py-2.5 px-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold text-xs rounded shadow transition flex items-center justify-center gap-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Masuk Kembali Sekarang</span>
                </a>
            </div>
        </div>

        <!-- Status bar footer -->
        <div class="bg-slate-950 px-3 py-1.5 text-[10px] text-slate-500 border-t border-slate-800 flex items-center justify-between font-mono">
            <span>DMS Workstation v2.6</span>
            <span>Status: 419 Page Expired (Auto-Recover)</span>
        </div>
    </div>

    <script>
        lucide.createIcons();
        let seconds = 2;
        const countdownEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            seconds--;
            if (countdownEl) countdownEl.innerText = seconds + ' detik';
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = "{{ route('login') }}";
            }
        }, 1000);
    </script>
</body>
</html>
