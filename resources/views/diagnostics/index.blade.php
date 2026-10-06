<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS PT INDRACO - Server Performance & LAN Diagnostics</title>
    @include('layouts.partials.head_assets')
    <script>
        (function() {
            var storedTheme = localStorage.getItem('theme');
            if (storedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .glass-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }
        .dark .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .pulse-dot {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse-ring {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .4; transform: scale(0.9); }
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen font-sans selection:bg-indigo-500 selection:text-white"
      x-data="serverTelemetryApp()" 
      x-init="init()">

    <!-- Top Navigation Bar -->
    <header class="border-b border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/60 sticky top-0 z-30 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center shadow-md shadow-indigo-500/20">
                    <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">DMS INDRACO Server Telemetry</h1>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span> Online
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Monitoring Performa & Latensi Jaringan Antar Laptop</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Theme Toggle Button -->
                <button type="button" 
                        @click="toggleTheme()" 
                        class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 transition"
                        title="Ubah Tema (Light / Dark)">
                    <template x-if="currentTheme === 'dark'">
                        <i data-lucide="sun" class="w-4 h-4 text-amber-400"></i>
                    </template>
                    <template x-if="currentTheme !== 'dark'">
                        <i data-lucide="moon" class="w-4 h-4 text-indigo-600"></i>
                    </template>
                </button>

                <!-- Polling Interval Selector -->
                <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 px-2.5 py-1 rounded-lg text-xs">
                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Interval:</span>
                    <select x-model="refreshInterval" @change="updateTimer()" class="bg-transparent text-slate-800 dark:text-slate-200 text-xs font-semibold focus:outline-none cursor-pointer">
                        <option value="5000" class="bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">5 Detik</option>
                        <option value="10000" class="bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200" selected>10 Detik (Rekomendasi)</option>
                        <option value="30000" class="bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">30 Detik</option>
                        <option value="0" class="bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">Jeda (Pause)</option>
                    </select>
                </div>

                <!-- Refresh Button -->
                <button @click="fetchMetrics()" 
                        :disabled="loading"
                        class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50"
                        title="Segarkan Metrik Sekarang">
                    <i data-lucide="refresh-cw" class="w-4 h-4" :class="loading ? 'animate-spin' : ''"></i>
                </button>

                <!-- Back to Application -->
                <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-xs">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Masuk DMS
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Automated Health & Diagnosis Summary Banner (Easy for AI & Super Admin) -->
        <div class="glass-card rounded-2xl p-4 shadow-xs bg-gradient-to-r from-indigo-50/70 via-white to-slate-50 border border-indigo-100 dark:from-indigo-950/40 dark:via-slate-900/60 dark:to-slate-900/40 dark:border-indigo-900/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm"
                     :class="{
                         'bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30': metrics.diagnosis?.overall_status === 'OPTIMAL',
                         'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/30': metrics.diagnosis?.overall_status === 'WARNING',
                         'bg-rose-100 text-rose-800 border border-rose-300 dark:bg-rose-500/20 dark:text-rose-400 dark:border-rose-500/30': metrics.diagnosis?.overall_status === 'CRITICAL'
                     }">
                    <span x-text="(metrics.diagnosis?.health_score || 100) + '%'"></span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 border border-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30 flex items-center gap-1">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Super Admin Diagnostic Verdict
                        </span>
                        <span class="font-bold text-xs"
                              :class="{
                                  'text-emerald-700 dark:text-emerald-400': metrics.diagnosis?.overall_status === 'OPTIMAL',
                                  'text-amber-700 dark:text-amber-400': metrics.diagnosis?.overall_status === 'WARNING',
                                  'text-rose-700 dark:text-rose-400': metrics.diagnosis?.overall_status === 'CRITICAL'
                              }"
                              x-text="metrics.diagnosis?.overall_status || 'OPTIMAL'"></span>
                    </div>
                    <p class="text-xs text-slate-700 dark:text-slate-300 font-mono mt-1" x-text="metrics.diagnosis?.summary_for_ai || 'Menganalisis performa server...'"></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="text-right text-[11px] text-slate-500 dark:text-slate-400">
                    <div>Token Diagnostik Remote:</div>
                    <code class="font-mono text-indigo-700 dark:text-indigo-300 text-xs select-all font-bold" x-text="metrics.diagnostic_key || '--'"></code>
                </div>
                <button type="button" 
                        @click="copyDiagnosticToken()" 
                        class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-700 transition"
                        title="Salin Token Diagnostik untuk API / AI">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Top Telemetry Row: Server ID, Latency RTT, RAM, OPcache -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Card 1: Server Identification -->
            <div class="glass-card rounded-2xl p-5 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Host Server</span>
                    <i data-lucide="server" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div class="font-mono text-xl font-bold text-slate-900 dark:text-white truncate" x-text="metrics.server?.name || 'Loading...'"></div>
                <div class="flex items-center gap-1.5 mt-2 text-xs">
                    <span class="text-slate-500 dark:text-slate-400">IP Server:</span>
                    <span class="font-mono font-bold text-indigo-700 dark:text-indigo-300 px-1.5 py-0.5 rounded bg-indigo-50 border border-indigo-200 dark:bg-indigo-950/60 dark:border-indigo-800/40" x-text="metrics.server?.ip || '--'"></span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-2 font-medium">
                    <span x-text="'PHP ' + (metrics.server?.php_version || '--')"></span>
                    <span>•</span>
                    <span x-text="metrics.server?.os || 'Windows'"></span>
                </div>
            </div>

            <!-- Card 2: Your Connection RTT (Laptop Ini -> Server) -->
            <div class="glass-card rounded-2xl p-5 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Latensi Laptop Ini</span>
                    <i data-lucide="wifi" class="w-4 h-4 text-cyan-600 dark:text-cyan-400"></i>
                </div>
                <div class="flex items-baseline gap-2">
                    <div class="font-mono text-3xl font-extrabold"
                         :class="{
                             'text-emerald-600 dark:text-emerald-400': pingRtt <= 80,
                             'text-amber-600 dark:text-amber-400': pingRtt > 80 && pingRtt <= 250,
                             'text-rose-600 dark:text-rose-400': pingRtt > 250
                         }" 
                         x-text="pingRtt !== null ? pingRtt + ' ms' : '--'"></div>
                    <span class="text-xs text-slate-500 dark:text-slate-400">round-trip</span>
                </div>
                <div class="flex items-center gap-1.5 mt-2 text-xs">
                    <span class="text-slate-500 dark:text-slate-400">IP Anda:</span>
                    <span class="font-mono font-bold text-cyan-700 dark:text-cyan-300 px-1.5 py-0.5 rounded bg-cyan-50 border border-cyan-200 dark:bg-cyan-950/60 dark:border-cyan-800/40" x-text="metrics.client?.ip || '--'"></span>
                </div>
                <div class="text-[11px] mt-2 font-bold"
                     :class="pingRtt <= 80 ? 'text-emerald-700 dark:text-emerald-400' : (pingRtt <= 250 ? 'text-amber-700 dark:text-amber-400' : 'text-rose-700 dark:text-rose-400')">
                    <span x-text="pingRtt <= 80 ? 'Sangat Cepat (Instan)' : (pingRtt <= 250 ? 'Stabil & Normal' : 'Koneksi Lambat')"></span>
                </div>
            </div>

            <!-- Card 3: Memory RAM Engine -->
            <div class="glass-card rounded-2xl p-5 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Memori RAM PHP</span>
                    <i data-lucide="cpu" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                </div>
                <div class="flex items-baseline gap-1">
                    <div class="font-mono text-3xl font-extrabold text-slate-900 dark:text-white" x-text="metrics.memory?.current_mb || '0'"></div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">MB</span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                    <div class="bg-amber-500 dark:bg-amber-400 h-1.5 rounded-full transition-all duration-300" 
                         :style="'width: ' + Math.min(100, Math.round(((metrics.memory?.current_mb || 0) / 256) * 100)) + '%;'"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-2 font-medium">
                    <span>Peak: <strong class="text-slate-800 dark:text-slate-200 font-bold" x-text="(metrics.memory?.peak_mb || 0) + ' MB'"></strong></span>
                    <span>Batas: <strong class="text-slate-800 dark:text-slate-200 font-bold" x-text="metrics.memory?.limit || '256M'"></strong></span>
                </div>
            </div>

            <!-- Card 4: OPcache & JIT Status -->
            <div class="glass-card rounded-2xl p-5 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">OPcache & JIT</span>
                    <i data-lucide="zap" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <div class="flex items-baseline gap-2">
                    <div class="font-mono text-3xl font-extrabold text-emerald-600 dark:text-emerald-400" x-text="(metrics.opcache?.hit_rate_pct || 100) + '%'"></div>
                    <span class="text-xs text-slate-500 dark:text-slate-400">hit rate</span>
                </div>
                <div class="flex items-center gap-2 mt-2 text-xs">
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold"
                          :class="metrics.opcache?.enabled ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30' : 'bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-500/20 dark:text-rose-300'">
                        OPcache: <span x-text="metrics.opcache?.enabled ? 'AKTIF' : 'NON-AKTIF'"></span>
                    </span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold"
                          :class="metrics.opcache?.jit_enabled ? 'bg-cyan-50 text-cyan-800 border border-cyan-200 dark:bg-cyan-500/20 dark:text-cyan-300 dark:border-cyan-500/30' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'">
                        JIT: <span x-text="metrics.opcache?.jit_enabled ? 'ON' : 'OFF'"></span>
                    </span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 font-medium">
                    Cached Script: <strong class="text-slate-800 dark:text-slate-200 font-bold" x-text="metrics.opcache?.cached_scripts || '0'"></strong> file
                </div>
            </div>

        </div>

        <!-- Section 2: Interactive Remote IP Probe Tool -->
        <div id="remoteProbeSection" class="glass-card rounded-2xl p-6 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="network" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        Tes Koneksi & Ping ke IP Laptop Lain (Remote Probe)
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Kirim IP laptop lain di jaringan lokal untuk menguji apakah PC server dapat terhubung ke laptop tersebut dan berapa latensinya.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="targetIpInput = metrics.client?.ip || ''"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs border border-slate-300 dark:border-slate-700 transition font-medium">
                        Gunakan IP Saya (<span x-text="metrics.client?.ip || '--'"></span>)
                    </button>
                </div>
            </div>

            <!-- Input Form for Testing Target IP -->
            <form @submit.prevent="runIpProbe()" class="mt-4 flex flex-col sm:flex-row items-center gap-3">
                <div class="relative w-full sm:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="globe" class="w-4 h-4"></i>
                    </div>
                    <input type="text" 
                           x-model="targetIpInput" 
                           placeholder="Masukkan IP Laptop Target (misal: 192.168.1.50)"
                           class="w-full pl-9 pr-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none transition"
                           required>
                </div>
                <button type="submit" 
                        :disabled="probing || !targetIpInput"
                        class="w-full sm:w-auto px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition disabled:opacity-50 shadow-xs">
                    <i data-lucide="send" class="w-4 h-4" :class="probing ? 'animate-bounce' : ''"></i>
                    <span x-text="probing ? 'Menguji IP...' : 'Uji Koneksi IP Sekarang'"></span>
                </button>
            </form>

            <!-- Probe Result Banner -->
            <div x-show="probeResult" x-cloak class="mt-4 p-4 rounded-xl border transition-all"
                 :class="probeResult?.is_reachable ? 'bg-emerald-50 border-emerald-300 text-emerald-900 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-200' : 'bg-rose-50 border-rose-300 text-rose-900 dark:bg-rose-950/30 dark:border-rose-800 dark:text-rose-200'">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-lg mt-0.5" :class="probeResult?.is_reachable ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400'">
                            <i data-lucide="check-circle" x-show="probeResult?.is_reachable" class="w-5 h-5"></i>
                            <i data-lucide="alert-triangle" x-show="!probeResult?.is_reachable" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="font-bold text-sm">
                                <span x-text="probeResult?.is_reachable ? 'Laptop Terhubung & Merespon!' : 'Laptop Tidak Merespon / Timeout'"></span>
                            </div>
                            <div class="text-xs opacity-90 mt-1 font-mono">
                                Server (<span x-text="probeResult?.server_ip"></span>) 
                                <span class="font-sans">⟶ Target Laptop:</span> 
                                <strong class="underline font-bold" x-text="probeResult?.target_ip"></strong>
                            </div>
                            <p class="text-[11px] opacity-75 mt-1" x-show="!probeResult?.is_reachable">
                                Catatan: Jika laptop target menyalakan Firewall Windows (Block ICMP), ping mungkin timeout meskipun web browser tetap bisa membuka alamat server.
                            </p>
                        </div>
                    </div>
                    <div class="text-right pl-4">
                        <div class="font-mono text-2xl font-black" x-text="probeResult?.rtt_ms !== null ? probeResult.rtt_ms + ' ms' : 'Offline'"></div>
                        <div class="text-[10px] uppercase font-bold tracking-wider opacity-75">Respon Waktu</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Live Connected LAN Clients & User Presence Hub -->
        <div class="glass-card rounded-2xl p-6 shadow-xs space-y-5">
            <!-- Header & Summary Stats -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 flex items-center justify-center border border-emerald-200 dark:border-emerald-500/30 shadow-xs">
                            <i data-lucide="users" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                Monitor User Realtime & Klien LAN Terhubung
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30 flex items-center gap-1 font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span> Live Realtime
                                </span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Pantau status user aktif, IP laptop di jaringan lokal, OS perangkat, dan web browser secara instan tanpa beban database.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Summary Badges Pills -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <div class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-dot"></span>
                        <span class="font-medium">Online:</span>
                        <strong class="font-mono font-bold" x-text="metrics.connected_users?.summary?.online_count || 0"></strong>
                    </div>
                    <div class="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 dark:bg-amber-950/40 dark:border-amber-800/60 dark:text-amber-300 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="font-medium">Idle:</span>
                        <strong class="font-mono font-bold" x-text="metrics.connected_users?.summary?.idle_count || 0"></strong>
                    </div>
                    <div class="px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <span class="font-medium">Offline:</span>
                        <strong class="font-mono font-bold" x-text="metrics.connected_users?.summary?.offline_count || 0"></strong>
                    </div>
                    <div class="px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-800 dark:bg-indigo-950/40 dark:border-indigo-800/60 dark:text-indigo-300 flex items-center gap-2">
                        <i data-lucide="laptop" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                        <span class="font-medium">Laptop Aktif:</span>
                        <strong class="font-mono font-bold" x-text="metrics.connected_users?.summary?.active_clients_count || 0"></strong>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs & Search Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <!-- Status Filter Tabs -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-900/90 p-1 rounded-xl border border-slate-200 dark:border-slate-800">
                    <button type="button" 
                            @click="userStatusFilter = 'all'"
                            :class="userStatusFilter === 'all' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                            class="px-3 py-1 rounded-lg text-xs transition">
                        Semua (<span x-text="metrics.connected_users?.summary?.total_registered || 0"></span>)
                    </button>
                    <button type="button" 
                            @click="userStatusFilter = 'online'"
                            :class="userStatusFilter === 'online' ? 'bg-white text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 font-bold border border-emerald-300 dark:border-emerald-800/50 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Online (<span x-text="metrics.connected_users?.summary?.online_count || 0"></span>)
                    </button>
                    <button type="button" 
                            @click="userStatusFilter = 'idle'"
                            :class="userStatusFilter === 'idle' ? 'bg-white text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 font-bold border border-amber-300 dark:border-amber-800/50 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Idle (<span x-text="metrics.connected_users?.summary?.idle_count || 0"></span>)
                    </button>
                    <button type="button" 
                            @click="userStatusFilter = 'offline'"
                            :class="userStatusFilter === 'offline' ? 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                            class="px-3 py-1 rounded-lg text-xs transition">
                        Offline (<span x-text="metrics.connected_users?.summary?.offline_count || 0"></span>)
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </div>
                    <input type="text" 
                           x-model="userSearchQuery"
                           placeholder="Cari user, IP, dept, browser..."
                           class="w-full pl-9 pr-8 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 focus:border-indigo-500 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none transition">
                    <button type="button" 
                            x-show="userSearchQuery" 
                            @click="userSearchQuery = ''" 
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-rose-500">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

            <!-- Table of Connected Users -->
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/40">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/90 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <th class="py-3 px-3 w-28">Status</th>
                            <th class="py-3 px-3">Nama Pengguna & Akun</th>
                            <th class="py-3 px-3">Departemen</th>
                            <th class="py-3 px-3">IP Address LAN</th>
                            <th class="py-3 px-3">Perangkat & OS</th>
                            <th class="py-3 px-3">Web Browser</th>
                            <th class="py-3 px-3">Halaman / Aktivitas</th>
                            <th class="py-3 px-3 text-right">Waktu Aktif</th>
                            <th class="py-3 px-3 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
                        <template x-for="user in connectedUsersList" :key="user.id">
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition"
                                :class="user.is_current_user ? 'bg-indigo-50/40 dark:bg-indigo-950/20' : ''">
                                
                                <!-- Status Badge Column -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <template x-if="user.status === 'online'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span>
                                            Online
                                        </span>
                                    </template>
                                    <template x-if="user.status === 'idle'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Idle
                                        </span>
                                    </template>
                                    <template x-if="user.status === 'offline'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Offline
                                        </span>
                                    </template>
                                </td>

                                <!-- User Name, Role, Email -->
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-bold text-[11px] text-slate-700 dark:text-slate-300 shrink-0 uppercase"
                                             x-text="user.name ? user.name.substring(0, 2) : 'US'"></div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-slate-900 dark:text-slate-100 truncate" x-text="user.name"></span>
                                                <template x-if="user.is_current_user">
                                                    <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/30 dark:text-indigo-300 dark:border-indigo-500/40 font-bold">Anda</span>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded"
                                                      :class="{
                                                          'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:border-purple-800/50': user.role === 'admin',
                                                          'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800/50': user.role === 'pic_gudang',
                                                          'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800/50': user.role === 'pic_dept'
                                                      }"
                                                      x-text="user.role_label"></span>
                                                <span class="text-[11px] text-slate-500 truncate" x-text="user.email"></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Department -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium text-slate-800 dark:text-slate-300" x-text="user.department_name"></div>
                                    <div class="text-[10px] font-mono text-slate-500" x-text="user.department_code"></div>
                                </td>

                                <!-- IP Address LAN -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <template x-if="user.ip">
                                        <div class="flex items-center gap-1.5">
                                            <code class="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-indigo-700 dark:text-indigo-300 font-bold select-all" x-text="user.ip"></code>
                                        </div>
                                    </template>
                                    <template x-if="!user.ip">
                                        <span class="text-slate-400 font-mono">-</span>
                                    </template>
                                </td>

                                <!-- Device & OS -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <i :data-lucide="user.device_icon || 'monitor'" class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                                        <span class="font-medium text-slate-800 dark:text-slate-200" x-text="user.device || '-'"></span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono pl-5" x-text="user.os || '-'"></div>
                                </td>

                                <!-- Browser -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <i :data-lucide="user.browser_icon || 'globe'" class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400 shrink-0"></i>
                                        <span class="text-slate-700 dark:text-slate-300 font-medium" x-text="user.browser || '-'"></span>
                                    </div>
                                </td>

                                <!-- Last Action / Page -->
                                <td class="py-3 px-3">
                                    <div class="text-slate-700 dark:text-slate-200 truncate max-w-xs font-medium" x-text="user.last_action || '-'"></div>
                                </td>

                                <!-- Last Active Time -->
                                <td class="py-3 px-3 text-right whitespace-nowrap">
                                    <div class="font-bold" 
                                         :class="user.status === 'online' ? 'text-emerald-600 dark:text-emerald-400' : (user.status === 'idle' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500')"
                                         x-text="user.last_seen_relative"></div>
                                    <div class="text-[10px] text-slate-400 font-mono" x-text="user.last_seen_time || '-'"></div>
                                </td>

                                <!-- Action Button: Ping IP -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <button type="button" 
                                            @click="quickPingUser(user.ip)" 
                                            :disabled="!user.ip || user.ip === '-'"
                                            class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-semibold flex items-center justify-center gap-1 transition disabled:opacity-30 disabled:pointer-events-none mx-auto shadow-xs"
                                            title="Uji Ping / Latensi ke laptop ini secara instan">
                                        <i data-lucide="zap" class="w-3 h-3 text-amber-300"></i>
                                        <span>Ping</span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="connectedUsersList.length === 0">
                            <td colspan="9" class="py-8 text-center text-slate-500">
                                <div class="space-y-1">
                                    <i data-lucide="user-x" class="w-6 h-6 mx-auto text-slate-400 mb-1"></i>
                                    <p class="font-medium text-xs">Tidak ada user yang cocok dengan kriteria pencarian.</p>
                                    <p class="text-[11px] text-slate-400">Coba ubah kata kunci atau ganti tab filter status.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Database & Storage Status + Connected Client Traffic Log -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left: SQLite Engine & WAL Status (1 Col) -->
            <div class="glass-card rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="database" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                        Mesin Database SQLite
                    </h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30">
                        WAL Mode Aktif
                    </span>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Ukuran DB Utama (database.sqlite):</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-200" x-text="(metrics.database?.db_size_kb || 0) + ' KB'"></span>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Write-Ahead Log (.sqlite-wal):</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="(metrics.database?.wal_size_kb || 0) + ' KB'"></span>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Shared-Memory (.sqlite-shm):</span>
                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="(metrics.database?.shm_size_kb || 0) + ' KB'"></span>
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    <strong class="text-slate-900 dark:text-slate-200 block mb-1">Kenapa sangat cepat di Laptop 8GB?</strong>
                    SQLite WAL mode membaca & menulis transaksi di RAM (Shared Memory) secara simultan tanpa mengunci database disk, menghasilkan throughput hingga 100 transaksi/detik.
                </div>

                <!-- Direct API Endpoints Links -->
                <div class="pt-2 border-t border-slate-200 dark:border-slate-800/80 space-y-1.5">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Endpoint API JSON:</span>
                    <a href="{{ url('/api/health/ping') }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 flex items-center justify-between p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-900 font-mono font-semibold">
                        <span>/api/health/ping</span>
                        <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                    <a href="{{ url('/api/health/metrics') }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 flex items-center justify-between p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-900 font-mono font-semibold">
                        <span>/api/health/metrics</span>
                        <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                    <a href="{{ url('/api/health/logs') }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 flex items-center justify-between p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-900 font-mono font-semibold">
                        <span>/api/health/logs</span>
                        <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>

            <!-- Right: Connected Client Traffic & Activity Logs (2 Cols) -->
            <div class="glass-card rounded-2xl p-5 shadow-xs lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                            Log Laptop & Pengguna Terakhir
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar IP komputer yang aktif mengakses aplikasi DMS</p>
                    </div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono font-semibold" x-text="(metrics.recent_clients?.length || 0) + ' riwayat'"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-2 font-semibold">
                                <th class="pb-2 font-semibold">IP Address Laptop</th>
                                <th class="pb-2 font-semibold">Nama Pengguna</th>
                                <th class="pb-2 font-semibold">Aksi / Aktivitas</th>
                                <th class="pb-2 font-semibold text-right">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono">
                            <template x-for="(client, idx) in metrics.recent_clients || []" :key="idx">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                    <td class="py-2.5 font-bold text-indigo-700 dark:text-indigo-300" x-text="client.ip || '-'"></td>
                                    <td class="py-2.5 font-sans font-bold text-slate-800 dark:text-slate-200" x-text="client.user || 'Sistem / Anonim'"></td>
                                    <td class="py-2.5 font-sans text-slate-600 dark:text-slate-400 truncate max-w-xs" x-text="client.action || '-'"></td>
                                    <td class="py-2.5 text-right text-slate-500 dark:text-slate-400" x-text="client.time || '-'"></td>
                                </tr>
                            </template>
                            <tr x-show="!metrics.recent_clients || metrics.recent_clients.length === 0">
                                <td colspan="4" class="py-6 text-center text-slate-400 font-sans">
                                    Belum ada log aktivitas klien yang tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Section 4: Live Laravel System & Error Log Viewer (storage/logs/laravel.log) -->
        <div class="glass-card rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 flex items-center justify-center border border-amber-200 dark:border-amber-500/30">
                        <i data-lucide="terminal" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            Log Sistem Server (storage/logs/laravel.log)
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 font-semibold" x-text="(serverLogs.count || 0) + ' baris terbaru'"></span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pantau error, exception, dan pesan sistem server secara langsung dari laptop lain</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" 
                           x-model="logFilter" 
                           placeholder="Filter kata kunci..." 
                           class="px-2.5 py-1 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 w-36 sm:w-48 font-mono">
                    <button type="button" 
                            @click="fetchServerLogs()" 
                            :disabled="loadingLogs"
                            class="px-2.5 py-1 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-lg border border-slate-300 dark:border-slate-700 flex items-center gap-1.5 transition disabled:opacity-50 font-medium">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="loadingLogs ? 'animate-spin' : ''"></i>
                        <span>Segarkan Log</span>
                    </button>
                </div>
            </div>

            <!-- Terminal-style log display -->
            <div class="bg-slate-900 text-slate-100 rounded-xl p-4 border border-slate-800 font-mono text-xs overflow-x-auto max-h-96 overflow-y-auto space-y-1 select-text shadow-inner">
                <template x-for="(line, idx) in filteredLogs" :key="idx">
                    <div class="py-0.5 leading-relaxed break-all whitespace-pre-wrap"
                         :class="{
                             'text-rose-400 font-semibold bg-rose-950/40 px-1 rounded': line.includes('.ERROR:'),
                             'text-amber-300 bg-amber-950/30 px-1 rounded': line.includes('.WARNING:'),
                             'text-cyan-300': line.includes('.INFO:'),
                             'text-slate-400': !line.includes('.ERROR:') && !line.includes('.WARNING:') && !line.includes('.INFO:')
                         }"
                         x-text="line"></div>
                </template>
                <div x-show="filteredLogs.length === 0" class="py-8 text-center text-slate-500">
                    <span x-text="serverLogs.file_exists ? 'Tidak ada baris log yang cocok dengan filter.' : 'File laravel.log kosong atau belum ada error.'"></span>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="mt-8 border-t border-slate-200 dark:border-slate-800 py-6 text-center text-xs text-slate-500">
        DMS PT INDRACO • Portable Offline LAN Edition • Server Diagnostics Module
    </footer>

    <script>
    function serverTelemetryApp() {
        return {
            metrics: {},
            loading: false,
            pingRtt: null,
            refreshInterval: 10000,
            timerId: null,
            lastUsersHash: null,
            currentTheme: localStorage.getItem('theme') || 'light',
            
            // Connected Users state
            userSearchQuery: '',
            userStatusFilter: 'all',

            toggleTheme() {
                this.currentTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', this.currentTheme);
                if (this.currentTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            get connectedUsersList() {
                const list = this.metrics.connected_users?.users || [];
                let filtered = list;
                if (this.userStatusFilter !== 'all') {
                    filtered = filtered.filter(u => u.status === this.userStatusFilter);
                }
                if (this.userSearchQuery && this.userSearchQuery.trim()) {
                    const q = this.userSearchQuery.toLowerCase();
                    filtered = filtered.filter(u => 
                        (u.name && u.name.toLowerCase().includes(q)) ||
                        (u.email && u.email.toLowerCase().includes(q)) ||
                        (u.ip && u.ip.includes(q)) ||
                        (u.department_name && u.department_name.toLowerCase().includes(q)) ||
                        (u.role_label && u.role_label.toLowerCase().includes(q)) ||
                        (u.device && u.device.toLowerCase().includes(q)) ||
                        (u.browser && u.browser.toLowerCase().includes(q)) ||
                        (u.last_action && u.last_action.toLowerCase().includes(q))
                    );
                }
                return filtered;
            },

            quickPingUser(ip) {
                if (!ip || ip === '-') return;
                this.targetIpInput = ip;
                this.runIpProbe();
                const probeElem = document.getElementById('remoteProbeSection');
                if (probeElem) {
                    probeElem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            },

            // Remote IP Probe state
            targetIpInput: '',
            probing: false,
            probeResult: null,

            // Server Logs state
            serverLogs: { count: 0, lines: [], file_exists: true },
            loadingLogs: false,
            logFilter: '',

            get filteredLogs() {
                if (!this.serverLogs.lines) return [];
                if (!this.logFilter.trim()) return this.serverLogs.lines;
                const q = this.logFilter.toLowerCase();
                return this.serverLogs.lines.filter(l => l.toLowerCase().includes(q));
            },

            init() {
                // Ensure initial theme sync
                if (this.currentTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                this.fetchMetrics();
                this.measurePing();
                this.fetchServerLogs();
                this.updateTimer();

                // Page Visibility: Jeda polling saat tab diminimalkan / tidak aktif di layar
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        if (this.timerId) {
                            clearInterval(this.timerId);
                            this.timerId = null;
                        }
                    } else {
                        this.fetchMetrics();
                        this.measurePing();
                        this.updateTimer();
                    }
                });

                this.$watch('userStatusFilter', () => this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); }));
                this.$watch('userSearchQuery', () => this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); }));
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            updateTimer() {
                if (this.timerId) {
                    clearInterval(this.timerId);
                    this.timerId = null;
                }
                const interval = parseInt(this.refreshInterval, 10);
                if (interval > 0) {
                    this.timerId = setInterval(() => {
                        this.fetchMetrics();
                        this.measurePing();
                    }, interval);
                }
            },

            async measurePing() {
                if (document.hidden) return;
                const t0 = performance.now();
                try {
                    const res = await fetch('{{ route("api.health.ping") }}?_t=' + Date.now(), { cache: 'no-store' });
                    if (res.ok) {
                        this.pingRtt = Math.round(performance.now() - t0);
                    }
                } catch (e) {
                    this.pingRtt = null;
                }
            },

            async fetchMetrics() {
                if (document.hidden) return;
                this.loading = true;
                try {
                    const res = await fetch('{{ route("api.health.metrics") }}?_t=' + Date.now(), { cache: 'no-store' });
                    if (res.ok) {
                        const newMetrics = await res.json();
                        
                        // Smart Diffing: Cek apakah data user ada perubahan riil
                        const incomingHash = newMetrics.connected_users?.content_hash || 
                            JSON.stringify(newMetrics.connected_users?.users?.map(u => ({ id: u.id, s: u.status, ip: u.ip, a: u.last_action, d: u.device })));

                        if (!this.lastUsersHash || this.lastUsersHash !== incomingHash) {
                            // Ada user baru / status berubah: Perbarui state & render ulang icon
                            this.lastUsersHash = incomingHash;
                            this.metrics = newMetrics;
                            this.$nextTick(() => {
                                if (window.lucide) window.lucide.createIcons();
                            });
                        } else {
                            // Data user SAMA PERSIS: Jangan re-assign array user agar DOM tabel tidak di-render ulang!
                            if (this.metrics.system) this.metrics.system = newMetrics.system;
                            if (this.metrics.memory) this.metrics.memory = newMetrics.memory;
                            if (this.metrics.diagnosis) this.metrics.diagnosis = newMetrics.diagnosis;
                            if (this.metrics.database) this.metrics.database = newMetrics.database;
                            if (this.metrics.connected_users) {
                                this.metrics.connected_users.summary = newMetrics.connected_users.summary;
                                // Patch relative time in-place pada user objects yang sudah ada tanpa mutasi array
                                if (newMetrics.connected_users.users && this.metrics.connected_users.users) {
                                    const newMap = new Map(newMetrics.connected_users.users.map(u => [u.id, u]));
                                    for (const u of this.metrics.connected_users.users) {
                                        const fresh = newMap.get(u.id);
                                        if (fresh) {
                                            u.last_seen_relative = fresh.last_seen_relative;
                                            u.last_seen_time = fresh.last_seen_time;
                                        }
                                    }
                                }
                            }
                        }

                        if (!this.targetIpInput && this.metrics.client && this.metrics.client.ip) {
                            this.targetIpInput = this.metrics.client.ip;
                        }
                    }
                } catch (e) {
                    console.error('Failed fetching metrics:', e);
                } finally {
                    this.loading = false;
                }
            },

            async fetchServerLogs() {
                this.loadingLogs = true;
                try {
                    const res = await fetch('{{ route("api.health.logs") }}?lines=80&_t=' + Date.now(), { cache: 'no-store' });
                    if (res.ok) {
                        this.serverLogs = await res.json();
                    }
                } catch (e) {
                    console.error('Failed fetching server logs:', e);
                } finally {
                    this.loadingLogs = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                }
            },

            async runIpProbe() {
                if (!this.targetIpInput) return;
                this.probing = true;
                this.probeResult = null;
                try {
                    const url = '{{ route("api.health.probe_ip") }}?target=' + encodeURIComponent(this.targetIpInput.trim());
                    const res = await fetch(url, { cache: 'no-store' });
                    this.probeResult = await res.json();
                } catch (e) {
                    this.probeResult = {
                        status: 'error',
                        target_ip: this.targetIpInput,
                        is_reachable: false,
                        rtt_ms: null,
                        server_ip: this.metrics.server?.ip || 'Server'
                    };
                } finally {
                    this.probing = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                }
            },

            copyDiagnosticToken() {
                const token = this.metrics.diagnostic_key;
                if (!token) return;
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(token).then(() => {
                        alert('Token Diagnostik berhasil disalin ke clipboard:\n' + token);
                    });
                } else {
                    prompt('Salin token berikut:', token);
                }
            }
        }
    }
    </script>
</body>
</html>
