<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS PT INDRACO - Server Performance & LAN Diagnostics</title>
    @include('layouts.partials.head_assets')
    <style>
        [x-cloak] { display: none !important; }
        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .light .glass-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0, 0, 0, 0.08);
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
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans selection:bg-indigo-500 selection:text-white"
      x-data="serverTelemetryApp()" 
      x-init="init()">

    <!-- Top Navigation Bar -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 sticky top-0 z-30 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-bold text-white tracking-tight">DMS INDRACO Server Telemetry</h1>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 pulse-dot"></span> Online
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">Monitoring Performa & Latensi Jaringan Antar Laptop</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Polling Interval Selector -->
                <div class="flex items-center gap-1.5 bg-slate-800/80 border border-slate-700/80 px-2.5 py-1 rounded-lg text-xs">
                    <span class="text-slate-400 text-[11px]">Interval:</span>
                    <select x-model="refreshInterval" @change="updateTimer()" class="bg-transparent text-slate-200 text-xs font-semibold focus:outline-none cursor-pointer">
                        <option value="1000" class="bg-slate-900">1 Detik</option>
                        <option value="3000" class="bg-slate-900" selected>3 Detik</option>
                        <option value="5000" class="bg-slate-900">5 Detik</option>
                        <option value="0" class="bg-slate-900">Jeda (Pause)</option>
                    </select>
                </div>

                <!-- Refresh Button -->
                <button @click="fetchMetrics()" 
                        :disabled="loading"
                        class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white transition disabled:opacity-50"
                        title="Segarkan Metrik Sekarang">
                    <i data-lucide="refresh-cw" class="w-4 h-4" :class="loading ? 'animate-spin' : ''"></i>
                </button>

                <!-- Back to Application -->
                <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-sm">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Masuk DMS
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Top Telemetry Row: Server ID, Latency RTT, RAM, OPcache -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Server Identification -->
            <div class="glass-card rounded-2xl p-5 shadow-lg relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Host Server</span>
                    <i data-lucide="server" class="w-4 h-4 text-indigo-400"></i>
                </div>
                <div class="font-mono text-xl font-bold text-white truncate" x-text="metrics.server?.name || 'Loading...'"></div>
                <div class="flex items-center gap-1.5 mt-2 text-xs">
                    <span class="text-slate-400">IP Server:</span>
                    <span class="font-mono font-semibold text-indigo-300 px-1.5 py-0.5 rounded bg-indigo-950/60 border border-indigo-800/40" x-text="metrics.server?.ip || '--'"></span>
                </div>
                <div class="text-[11px] text-slate-400 mt-2 flex items-center gap-2">
                    <span x-text="'PHP ' + (metrics.server?.php_version || '--')"></span>
                    <span>•</span>
                    <span x-text="metrics.server?.os || 'Windows'"></span>
                </div>
            </div>

            <!-- Card 2: Your Connection RTT (Laptop Ini -> Server) -->
            <div class="glass-card rounded-2xl p-5 shadow-lg relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Latensi Laptop Ini</span>
                    <i data-lucide="wifi" class="w-4 h-4 text-cyan-400"></i>
                </div>
                <div class="flex items-baseline gap-2">
                    <div class="font-mono text-3xl font-extrabold"
                         :class="{
                             'text-emerald-400': pingRtt <= 80,
                             'text-amber-400': pingRtt > 80 && pingRtt <= 250,
                             'text-rose-400': pingRtt > 250
                         }" 
                         x-text="pingRtt !== null ? pingRtt + ' ms' : '--'"></div>
                    <span class="text-xs text-slate-400">round-trip</span>
                </div>
                <div class="flex items-center gap-1.5 mt-2 text-xs">
                    <span class="text-slate-400">IP Anda:</span>
                    <span class="font-mono font-semibold text-cyan-300 px-1.5 py-0.5 rounded bg-cyan-950/60 border border-cyan-800/40" x-text="metrics.client?.ip || '--'"></span>
                </div>
                <div class="text-[11px] mt-2 font-medium"
                     :class="pingRtt <= 80 ? 'text-emerald-400' : (pingRtt <= 250 ? 'text-amber-400' : 'text-rose-400')">
                    <span x-text="pingRtt <= 80 ? 'Sangat Cepat (Instan)' : (pingRtt <= 250 ? 'Stabil & Normal' : 'Koneksi Lambat')"></span>
                </div>
            </div>

            <!-- Card 3: Memory RAM Engine -->
            <div class="glass-card rounded-2xl p-5 shadow-lg relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Memori RAM PHP</span>
                    <i data-lucide="cpu" class="w-4 h-4 text-amber-400"></i>
                </div>
                <div class="flex items-baseline gap-1">
                    <div class="font-mono text-3xl font-extrabold text-white" x-text="metrics.memory?.current_mb || '0'"></div>
                    <span class="text-xs text-slate-400 font-semibold">MB</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                    <div class="bg-amber-400 h-1.5 rounded-full transition-all duration-300" 
                         :style="'width: ' + Math.min(100, Math.round(((metrics.memory?.current_mb || 0) / 256) * 100)) + '%;'"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2">
                    <span>Peak: <strong class="text-slate-200" x-text="(metrics.memory?.peak_mb || 0) + ' MB'"></strong></span>
                    <span>Batas: <strong class="text-slate-200" x-text="metrics.memory?.limit || '256M'"></strong></span>
                </div>
            </div>

            <!-- Card 4: OPcache & JIT Status -->
            <div class="glass-card rounded-2xl p-5 shadow-lg relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">OPcache & JIT</span>
                    <i data-lucide="zap" class="w-4 h-4 text-emerald-400"></i>
                </div>
                <div class="flex items-baseline gap-2">
                    <div class="font-mono text-3xl font-extrabold text-emerald-400" x-text="(metrics.opcache?.hit_rate_pct || 100) + '%'"></div>
                    <span class="text-xs text-slate-400">hit rate</span>
                </div>
                <div class="flex items-center gap-2 mt-2 text-xs">
                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold"
                          :class="metrics.opcache?.enabled ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300'">
                        OPcache: <span x-text="metrics.opcache?.enabled ? 'AKTIF' : 'NON-AKTIF'"></span>
                    </span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold"
                          :class="metrics.opcache?.jit_enabled ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-slate-800 text-slate-400'">
                        JIT: <span x-text="metrics.opcache?.jit_enabled ? 'ON' : 'OFF'"></span>
                    </span>
                </div>
                <div class="text-[11px] text-slate-400 mt-2">
                    Cached Script: <strong class="text-slate-200" x-text="metrics.opcache?.cached_scripts || '0'"></strong> file
                </div>
            </div>

        </div>

        <!-- Section 2: Interactive Remote IP Probe Tool -->
        <div class="glass-card rounded-2xl p-6 shadow-xl border border-slate-800">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-800/80">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i data-lucide="network" class="w-5 h-5 text-indigo-400"></i>
                        Tes Koneksi & Ping ke IP Laptop Lain (Remote Probe)
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Kirim IP laptop lain di jaringan lokal untuk menguji apakah PC server dapat terhubung ke laptop tersebut dan berapa latensinya.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="targetIpInput = metrics.client?.ip || ''"
                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs border border-slate-700 transition">
                        Gunakan IP Saya (<span x-text="metrics.client?.ip || '--'"></span>)
                    </button>
                </div>
            </div>

            <!-- Input Form for Testing Target IP -->
            <form @submit.prevent="runIpProbe()" class="mt-4 flex flex-col sm:flex-row items-center gap-3">
                <div class="relative w-full sm:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <i data-lucide="globe" class="w-4 h-4"></i>
                    </div>
                    <input type="text" 
                           x-model="targetIpInput" 
                           placeholder="Masukkan IP Laptop Target (misal: 192.168.1.50)"
                           class="w-full pl-9 pr-3 py-2 bg-slate-900 border border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-mono text-white placeholder-slate-500 focus:outline-none transition"
                           required>
                </div>
                <button type="submit" 
                        :disabled="probing || !targetIpInput"
                        class="w-full sm:w-auto px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition disabled:opacity-50 shadow-lg shadow-indigo-600/20">
                    <i data-lucide="send" class="w-4 h-4" :class="probing ? 'animate-bounce' : ''"></i>
                    <span x-text="probing ? 'Menguji IP...' : 'Uji Koneksi IP Sekarang'"></span>
                </button>
            </form>

            <!-- Probe Result Banner -->
            <div x-show="probeResult" x-cloak class="mt-4 p-4 rounded-xl border transition-all"
                 :class="probeResult?.is_reachable ? 'bg-emerald-950/30 border-emerald-800 text-emerald-200' : 'bg-rose-950/30 border-rose-800 text-rose-200'">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-lg mt-0.5" :class="probeResult?.is_reachable ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'">
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
                                <strong class="underline" x-text="probeResult?.target_ip"></strong>
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

        <!-- Section 3: Database & Storage Status + Connected Client Traffic Log -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left: SQLite Engine & WAL Status (1 Col) -->
            <div class="glass-card rounded-2xl p-5 shadow-lg space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <i data-lucide="database" class="w-4 h-4 text-indigo-400"></i>
                        Mesin Database SQLite
                    </h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                        WAL Mode Aktif
                    </span>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-900/60 border border-slate-800">
                        <span class="text-slate-400">Ukuran DB Utama (database.sqlite):</span>
                        <span class="font-mono font-bold text-slate-200" x-text="(metrics.database?.db_size_kb || 0) + ' KB'"></span>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-900/60 border border-slate-800">
                        <span class="text-slate-400">Write-Ahead Log (.sqlite-wal):</span>
                        <span class="font-mono font-bold text-emerald-400" x-text="(metrics.database?.wal_size_kb || 0) + ' KB'"></span>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-900/60 border border-slate-800">
                        <span class="text-slate-400">Shared-Memory (.sqlite-shm):</span>
                        <span class="font-mono font-bold text-slate-300" x-text="(metrics.database?.shm_size_kb || 0) + ' KB'"></span>
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-slate-900/80 border border-slate-800 text-[11px] text-slate-400 leading-relaxed">
                    <strong class="text-slate-200 block mb-1">Kenapa sangat cepat di Laptop 8GB?</strong>
                    SQLite WAL mode membaca & menulis transaksi di RAM (Shared Memory) secara simultan tanpa mengunci database disk, menghasilkan throughput hingga 100 transaksi/detik.
                </div>

                <!-- Direct API Endpoints Links -->
                <div class="pt-2 border-t border-slate-800/80 space-y-1.5">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Endpoint API JSON:</span>
                    <a href="{{ url('/api/health/ping') }}" target="_blank" class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center justify-between p-1.5 rounded hover:bg-slate-900 font-mono">
                        <span>/api/health/ping</span>
                        <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                    <a href="{{ url('/api/health/metrics') }}" target="_blank" class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center justify-between p-1.5 rounded hover:bg-slate-900 font-mono">
                        <span>/api/health/metrics</span>
                        <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                    <a href="{{ url('/api/health/logs') }}" target="_blank" class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center justify-between p-1.5 rounded hover:bg-slate-900 font-mono">
                        <span>/api/health/logs</span>
                        <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>

            <!-- Right: Connected Client Traffic & Activity Logs (2 Cols) -->
            <div class="glass-card rounded-2xl p-5 shadow-lg lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 text-emerald-400"></i>
                            Log Laptop & Pengguna Terakhir
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Daftar IP komputer yang aktif mengakses aplikasi DMS</p>
                    </div>
                    <span class="text-[11px] text-slate-400 font-mono" x-text="(metrics.recent_clients?.length || 0) + ' riwayat'"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-800/80 pb-2">
                                <th class="pb-2 font-semibold">IP Address Laptop</th>
                                <th class="pb-2 font-semibold">Nama Pengguna</th>
                                <th class="pb-2 font-semibold">Aksi / Aktivitas</th>
                                <th class="pb-2 font-semibold text-right">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            <template x-for="(client, idx) in metrics.recent_clients || []" :key="idx">
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-2.5 font-bold text-indigo-300" x-text="client.ip || '-'"></td>
                                    <td class="py-2.5 font-sans font-medium text-slate-200" x-text="client.user || 'Sistem / Anonim'"></td>
                                    <td class="py-2.5 font-sans text-slate-400 truncate max-w-xs" x-text="client.action || '-'"></td>
                                    <td class="py-2.5 text-right text-slate-400" x-text="client.time || '-'"></td>
                                </tr>
                            </template>
                            <tr x-show="!metrics.recent_clients || metrics.recent_clients.length === 0">
                                <td colspan="4" class="py-6 text-center text-slate-500 font-sans">
                                    Belum ada log aktivitas klien yang tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Section 4: Live Laravel System & Error Log Viewer (storage/logs/laravel.log) -->
        <div class="glass-card rounded-2xl p-6 shadow-xl border border-slate-800 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                        <i data-lucide="terminal" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            Log Sistem Server (storage/logs/laravel.log)
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700" x-text="(serverLogs.count || 0) + ' baris terbaru'"></span>
                        </h3>
                        <p class="text-xs text-slate-400">Pantau error, exception, dan pesan sistem server secara langsung dari laptop lain</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" 
                           x-model="logFilter" 
                           placeholder="Filter kata kunci..." 
                           class="px-2.5 py-1 text-xs bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 w-36 sm:w-48 font-mono">
                    <button type="button" 
                            @click="fetchServerLogs()" 
                            :disabled="loadingLogs"
                            class="px-2.5 py-1 text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg border border-slate-700 flex items-center gap-1.5 transition disabled:opacity-50">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="loadingLogs ? 'animate-spin' : ''"></i>
                        <span>Segarkan Log</span>
                    </button>
                </div>
            </div>

            <!-- Terminal-style log display -->
            <div class="bg-black/80 rounded-xl p-4 border border-slate-800 font-mono text-xs overflow-x-auto max-h-96 overflow-y-auto space-y-1 select-text">
                <template x-for="(line, idx) in filteredLogs" :key="idx">
                    <div class="py-0.5 leading-relaxed break-all whitespace-pre-wrap"
                         :class="{
                             'text-rose-400 font-semibold bg-rose-950/30 px-1 rounded': line.includes('.ERROR:'),
                             'text-amber-300 bg-amber-950/20 px-1 rounded': line.includes('.WARNING:'),
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
    <footer class="mt-8 border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
        DMS PT INDRACO • Portable Offline LAN Edition • Server Diagnostics Module
    </footer>

    <script>
    function serverTelemetryApp() {
        return {
            metrics: {},
            loading: false,
            pingRtt: null,
            refreshInterval: 3000,
            timerId: null,
            
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
                this.fetchMetrics();
                this.measurePing();
                this.fetchServerLogs();
                this.updateTimer();
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
                this.loading = true;
                try {
                    const res = await fetch('{{ route("api.health.metrics") }}?_t=' + Date.now(), { cache: 'no-store' });
                    if (res.ok) {
                        this.metrics = await res.json();
                        if (!this.targetIpInput && this.metrics.client && this.metrics.client.ip) {
                            this.targetIpInput = this.metrics.client.ip;
                        }
                    }
                } catch (e) {
                    console.error('Failed fetching metrics:', e);
                } finally {
                    this.loading = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
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
            }
        }
    }
    </script>
</body>
</html>
