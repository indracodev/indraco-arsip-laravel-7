@php
    $canOpenDiagnosticsModal = auth()->check() && auth()->user()->isSuperAdmin();
@endphp

{{-- PT INDRACO DMS - Realtime LAN Latency Monitor & Diagnostics Component --}}
<div x-data="lanLatencyEngine()" x-init="startMonitoring()" class="inline-flex items-center">
    <!-- LAN Latency Pill Button (Click to open Diagnostics Modal only for Super Admin) -->
    <button type="button" 
            @if($canOpenDiagnosticsModal)
            @click="isModalOpen = true"
            title="Klik untuk membuka Diagnostik Jaringan LAN"
            @else
            title="Status Koneksi Jaringan LAN"
            @endif
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono font-medium border transition-all duration-200 select-none {{ $canOpenDiagnosticsModal ? 'cursor-pointer' : 'cursor-default' }} focus:outline-none"
            :class="{
                'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/60': status === 'good',
                'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/60': status === 'medium',
                'bg-rose-50 text-rose-700 border-rose-400 animate-pulse dark:bg-rose-950/50 dark:text-rose-400 dark:border-rose-800': status === 'slow',
                'bg-red-600 text-white border-red-700 animate-bounce dark:bg-red-700 dark:border-red-600': status === 'offline',
                'bg-slate-100 text-slate-600 border-slate-300 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700': status === 'checking'
            }">
        <!-- Status Indicator Dot -->
        <span class="relative flex h-2 w-2">
            <span x-show="status === 'slow' || status === 'offline'" 
                  class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                  :class="status === 'offline' ? 'bg-white' : 'bg-rose-500'"></span>
            <span class="relative inline-flex rounded-full h-2 w-2"
                  :class="{
                      'bg-emerald-500': status === 'good',
                      'bg-amber-500': status === 'medium',
                      'bg-rose-600': status === 'slow',
                      'bg-white': status === 'offline',
                      'bg-slate-400': status === 'checking'
                  }"></span>
        </span>

        <!-- Latency Text -->
        <span class="flex items-center gap-1">
            <span class="font-semibold" x-text="statusBadgePrefix"></span>
            <span x-text="statusBadgeText"></span>
        </span>
    </button>

    <!-- Floating Warning Banner when LAN is Slow or Disconnected -->
    <div x-show="isSlowWarningVisible" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed bottom-4 right-4 z-50 max-w-md bg-rose-600 text-white p-3.5 rounded-lg shadow-2xl border border-rose-400 flex items-start gap-3"
         style="display: none;">
        <div class="p-1.5 bg-rose-700 rounded text-rose-100 shrink-0">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <div class="flex-1 text-xs">
            <h4 class="font-bold text-sm mb-0.5 flex items-center justify-between">
                <span x-text="status === 'offline' ? 'Koneksi LAN Terputus!' : 'Koneksi LAN Lemot / Lambat!'"></span>
                <span class="font-mono text-rose-200" x-text="latency !== null ? latency + ' ms' : ''"></span>
            </h4>
            <p class="text-rose-100 leading-relaxed mb-2" x-text="status === 'offline' 
                ? 'Tidak dapat terhubung ke server utama. Periksa kabel LAN atau switch-hub kantor.' 
                : 'Respon server melambat. Transaksi data mungkin mengalami jeda.'">
            </p>
            <div class="flex items-center gap-2">
                @if($canOpenDiagnosticsModal)
                <button type="button" @click="isModalOpen = true; isSlowWarningVisible = false" 
                        class="px-2.5 py-1 bg-white text-rose-900 font-bold rounded text-[11px] hover:bg-rose-50 transition cursor-pointer">
                    Diagnosa Jaringan
                </button>
                @endif
                <button type="button" @click="isSlowWarningVisible = false" 
                        class="px-2.5 py-1 bg-rose-700 hover:bg-rose-800 rounded text-[11px] text-rose-200 transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    @if($canOpenDiagnosticsModal)
    <!-- LAN Diagnostics Modal (Super Admin Only) -->
    <div x-show="isModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
         style="display: none;"
         @keydown.escape.window="isModalOpen = false">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
             @click.outside="isModalOpen = false">
            
            <!-- Modal Header -->
            <div class="px-5 py-3.5 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white"
                         :class="{
                             'bg-emerald-600': status === 'good',
                             'bg-amber-500': status === 'medium',
                             'bg-rose-600': status === 'slow' || status === 'offline',
                             'bg-slate-500': status === 'checking'
                         }">
                        <i data-lucide="network" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100 leading-tight">Diagnostik Koneksi LAN & Server</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Monitoring kualitas jaringan antar komputer lokal</p>
                    </div>
                </div>
                <button type="button" @click="isModalOpen = false" class="p-1 rounded-md text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                <!-- Status Banner -->
                <div class="p-4 rounded-lg border flex items-center justify-between"
                     :class="{
                         'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300': status === 'good',
                         'bg-amber-50 dark:bg-amber-950/30 border-amber-300 dark:border-amber-800 text-amber-800 dark:text-amber-300': status === 'medium',
                         'bg-rose-50 dark:bg-rose-950/30 border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300': status === 'slow',
                         'bg-red-100 dark:bg-red-950/50 border-red-400 dark:border-red-800 text-red-900 dark:text-red-200': status === 'offline',
                         'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300': status === 'checking'
                     }">
                    <div>
                        <div class="font-bold text-sm flex items-center gap-2">
                            <span x-text="statusTitle"></span>
                        </div>
                        <p class="text-xs mt-0.5 opacity-90" x-text="statusDescription"></p>
                    </div>
                    <div class="text-right">
                        <div class="font-mono text-2xl font-bold" x-text="latency !== null ? latency + ' ms' : '--'"></div>
                        <div class="text-[10px] uppercase font-bold tracking-wider opacity-75">Latensi RTT</div>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-4 gap-2.5">
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-lg border border-slate-200 dark:border-slate-700/80 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold">Rata-rata</span>
                        <div class="font-mono text-base font-bold text-slate-800 dark:text-slate-100 mt-0.5" x-text="avgLatency !== null ? avgLatency + ' ms' : '--'"></div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-lg border border-slate-200 dark:border-slate-700/80 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold">Minimum</span>
                        <div class="font-mono text-base font-bold text-emerald-600 dark:text-emerald-400 mt-0.5" x-text="minLatency !== null ? minLatency + ' ms' : '--'"></div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-lg border border-slate-200 dark:border-slate-700/80 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold">Maksimum</span>
                        <div class="font-mono text-base font-bold text-amber-600 dark:text-amber-400 mt-0.5" x-text="maxLatency !== null ? maxLatency + ' ms' : '--'"></div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-lg border border-slate-200 dark:border-slate-700/80 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold">Packet Loss</span>
                        <div class="font-mono text-base font-bold mt-0.5" :class="packetLoss > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-100'" x-text="packetLoss + '%'"></div>
                    </div>
                </div>

                <!-- Host & Client Info -->
                <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-lg border border-slate-200 dark:border-slate-700/80 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">IP Host Server (Pusat):</span>
                        <span class="font-mono font-semibold text-slate-800 dark:text-slate-200" x-text="serverInfo.server_ip || 'Menghubungkan...'"></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700/50 pt-1.5">
                        <span class="text-slate-500">Nama Host Server:</span>
                        <span class="font-mono font-semibold text-slate-800 dark:text-slate-200" x-text="serverInfo.server_name || '--'"></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700/50 pt-1.5">
                        <span class="text-slate-500">IP Komputer Klien Ini:</span>
                        <span class="font-mono font-semibold text-slate-800 dark:text-slate-200" x-text="serverInfo.client_ip || '--'"></span>
                    </div>
                </div>

                <!-- Troubleshooting Guidance for Slow LAN -->
                <div class="p-3 bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 rounded-lg text-xs space-y-1.5">
                    <div class="font-semibold text-amber-900 dark:text-amber-300 flex items-center gap-1.5">
                        <i data-lucide="help-circle" class="w-3.5 h-3.5 shrink-0"></i>
                        Panduan Mengatasi Koneksi LAN Lemot:
                    </div>
                    <ul class="list-disc list-inside text-amber-800/90 dark:text-amber-400 space-y-1 pl-1">
                        <li>Gunakan kabel LAN UTP Cat 5e/Cat 6 langsung ke switch (hindari Wi-Fi lemah).</li>
                        <li>Pastikan kabel LAN tidak terjepit atau konektor RJ-45 tidak longgar.</li>
                        <li>Jika menggunakan Switch-Hub, pastikan lampu port berkedip normal (hijau/gigabit).</li>
                        <li>Pastikan PC Server tidak sedang melakukan download/copy file berukuran raksasa.</li>
                    </ul>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3 bg-slate-100 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <span class="text-[11px] text-slate-500">Auto-ping setiap 6 detik</span>
                <div class="flex items-center gap-2">
                    @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->role === 'admin'))
                    <a href="{{ route('diagnostics.index') }}" target="_blank" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition">
                        <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                        <span>Panel Metrik Lengkap</span>
                    </a>
                    @endif

                    <button type="button" 
                            @click="runBurstTest()" 
                            :disabled="isBurstTesting"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition disabled:opacity-50">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="isBurstTesting ? 'animate-spin' : ''"></i>
                        <span x-text="isBurstTesting ? 'Menguji (5x)...' : 'Tes Ulang Sekarang'"></span>
                    </button>
                    <button type="button" @click="isModalOpen = false" class="px-3 py-1.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function lanLatencyEngine() {
    return {
        latency: null,
        status: 'checking', // checking, good, medium, slow, offline
        minLatency: null,
        maxLatency: null,
        avgLatency: null,
        packetLoss: 0,
        totalPings: 0,
        failedPings: 0,
        history: [],
        serverInfo: {
            server_ip: '',
            server_name: '',
            client_ip: ''
        },
        isModalOpen: false,
        isSlowWarningVisible: false,
        isBurstTesting: false,
        consecutiveSlowCount: 0,
        timer: null,

        get statusBadgePrefix() {
            return 'LAN: ';
        },

        get statusBadgeText() {
            if (this.status === 'checking') return 'Cek...';
            if (this.status === 'offline') return 'Putus';
            return (this.latency !== null ? this.latency + ' ms' : '--');
        },

        get statusTitle() {
            if (this.status === 'good') return 'Koneksi LAN Sangat Cepat & Stabil';
            if (this.status === 'medium') return 'Koneksi LAN Normal & Lancar';
            if (this.status === 'slow') return 'Peringatan: Koneksi LAN Lemot / Latensi Tinggi';
            if (this.status === 'offline') return 'Koneksi LAN Terputus ke Server';
            return 'Memeriksa Kualitas Jaringan...';
        },

        get statusDescription() {
            if (this.status === 'good') return 'Komunikasi antar PC client dan server berjalan instan (< 80ms).';
            if (this.status === 'medium') return 'Kecepatan normal LAN/WiFi, transaksi data sinkron lancar (80-250ms).';
            if (this.status === 'slow') return 'Respon jaringan di atas 250ms berturut-turut. Periksa kabel LAN atau switch-hub.';
            if (this.status === 'offline') return 'Server tidak merespon dalam batas waktu. Periksa jaringan fisik.';
            return 'Mengirim paket uji RTT ke server...';
        },

        startMonitoring() {
            // Berikan jeda 800ms saat halaman baru dibuka agar tidak bentrok dengan render awal
            setTimeout(() => {
                this.pingServer();
            }, 800);

            this.timer = setInterval(() => {
                if (!this.isBurstTesting) {
                    this.pingServer();
                }
            }, 6000);

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        async pingServer() {
            const start = performance.now();
            this.totalPings++;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3500);

            try {
                const response = await fetch('/api/health/ping?t=' + Date.now(), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const data = await response.json();
                const rtt = Math.round(performance.now() - start);

                this.latency = rtt;
                this.serverInfo.server_ip = data.server_ip || '';
                this.serverInfo.server_name = data.server_name || '';
                this.serverInfo.client_ip = data.client_ip || '';

                // Calculate Stats
                this.recordLatency(rtt);

                if (rtt <= 80) {
                    this.status = 'good';
                    this.consecutiveSlowCount = 0;
                    this.isSlowWarningVisible = false;
                } else if (rtt <= 250) {
                    this.status = 'medium';
                    this.consecutiveSlowCount = 0;
                    this.isSlowWarningVisible = false;
                } else {
                    this.status = 'slow';
                    this.consecutiveSlowCount++;
                    // Hanya tampilkan toast peringatan jika latensi tinggi beruntun >= 3 kali
                    if (this.consecutiveSlowCount >= 3) {
                        this.isSlowWarningVisible = true;
                    }
                }
            } catch (err) {
                clearTimeout(timeoutId);
                this.failedPings++;
                this.latency = null;
                this.status = 'offline';
                this.consecutiveSlowCount = 0;
                this.isSlowWarningVisible = true;
            } finally {
                this.packetLoss = Math.round((this.failedPings / this.totalPings) * 100);
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        },

        recordLatency(rtt) {
            this.history.push(rtt);
            if (this.history.length > 20) this.history.shift();

            if (this.minLatency === null || rtt < this.minLatency) this.minLatency = rtt;
            if (this.maxLatency === null || rtt > this.maxLatency) this.maxLatency = rtt;

            const sum = this.history.reduce((a, b) => a + b, 0);
            this.avgLatency = Math.round(sum / this.history.length);
        },

        async runBurstTest() {
            if (this.isBurstTesting) return;
            this.isBurstTesting = true;

            for (let i = 0; i < 5; i++) {
                await this.pingServer();
                await new Promise(r => setTimeout(r, 250));
            }

            this.isBurstTesting = false;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        }
    };
}
</script>
