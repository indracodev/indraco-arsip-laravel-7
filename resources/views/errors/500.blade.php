<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Sistem | DMS PT Indraco</title>
    @include('layouts.partials.head_assets')
    <style>
        .delphi-window {
            box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 1px 1px 0 rgba(255,255,255,0.15);
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-rose-500 selection:text-white"
      x-data="{ 
          copied: false,
          traceId: '{{ $traceId ?? ('ERR-' . date('Ymd-His')) }}',
          copyDetail() {
              const text = `Trace ID: ${this.traceId}\nURL: ${window.location.href}\nWaktu: ${new Date().toLocaleString('id-ID')}\nPesan: {{ addslashes($message ?? 'Kesalahan Sistem Internal') }}`;
              navigator.clipboard.writeText(text).then(() => {
                  this.copied = true;
                  setTimeout(() => this.copied = false, 3000);
              });
          }
      }">

    <div class="delphi-window w-full max-w-lg bg-slate-800 border-2 border-slate-600 rounded-lg shadow-2xl overflow-hidden">
        <!-- Titlebar -->
        <div class="bg-linear-to-r from-rose-900 via-rose-800 to-slate-800 px-4 py-2 border-b border-rose-700/50 flex items-center justify-between">
            <div class="flex items-center gap-2 font-mono text-xs font-bold text-rose-200">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-400"></i>
                <span>SYSTEM EXCEPTION - PT INDRACO DMS</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
            </div>
        </div>

        <!-- Window Body -->
        <div class="p-6 space-y-4">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-400 shrink-0">
                    <i data-lucide="server-crash" class="w-8 h-8"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white mb-1">
                        {{ $title ?? 'Terjadi Kesalahan Sistem (Error 500)' }}
                    </h1>
                    <p class="text-slate-300 text-xs leading-relaxed">
                        {{ $message ?? 'Sistem mendeteksi kendala saat memproses permintaan Anda. Error telah dicatat untuk analisa teknis.' }}
                    </p>
                </div>
            </div>

            <!-- Trace ID Card -->
            <div class="bg-slate-950/70 border border-slate-700/80 rounded-lg p-3 font-mono text-xs space-y-1.5">
                <div class="flex items-center justify-between text-slate-400 text-[11px]">
                    <span>KODE IDENTIFIKASI (TRACE ID):</span>
                    <span class="text-rose-400 font-bold">LOG RECORDED</span>
                </div>
                <div class="text-amber-400 font-bold text-sm tracking-wide select-all" x-text="traceId"></div>
                <div class="text-slate-500 text-[10px]">
                    Berikan Trace ID ini kepada administrator sistem untuk penelusuran log cepat.
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex flex-col sm:flex-row items-center gap-2.5">
                <button type="button" 
                        @click="copyDetail()"
                        class="w-full sm:w-auto flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 border border-slate-600 rounded text-xs font-semibold text-white flex items-center justify-center gap-2 transition active:scale-95">
                    <i data-lucide="copy" class="w-4 h-4" x-show="!copied"></i>
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400" x-show="copied"></i>
                    <span x-text="copied ? 'Detail Berhasil Disalin!' : 'Salin Detail Error'"></span>
                </button>
                <button type="button" 
                        onclick="window.location.reload()"
                        class="w-full sm:w-auto px-4 py-2 bg-amber-600 hover:bg-amber-500 rounded text-xs font-semibold text-slate-950 flex items-center justify-center gap-1.5 transition active:scale-95">
                    <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                    <span>Coba Lagi (F5)</span>
                </button>
                <a href="{{ route('dashboard') }}" 
                   class="w-full sm:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded text-xs font-semibold text-slate-300 flex items-center justify-center gap-1.5 transition">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Window Footer -->
        <div class="px-4 py-2 bg-slate-950 text-slate-500 text-[11px] font-mono border-t border-slate-800 flex items-center justify-between">
            <span>DMS INDRACO v3.0 Offline</span>
            <span>Host: {{ gethostname() }}</span>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
