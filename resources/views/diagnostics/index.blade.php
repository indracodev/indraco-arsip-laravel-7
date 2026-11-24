{{-- Live Monitor LAN & User (Laravel 7 diagnostics/index, admin only) inside the app shell: health
     summary, server / RTT / RAM / OPcache cards, remote IP probe, users online, SQLite + WAL
     checkpoint, recent client IPs, laravel.log tail. Alpine "liveMonitor": public/js/pages/diagnostics.js. --}}
@extends('layouts.app')

@section('title', 'Live Monitor LAN & User')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/diagnostics.js') }}" defer></script>
@endpush

@section('content')
<div x-data="liveMonitor" class="stack">
    <x-page-header title="Live Monitor LAN & User" subtitle="Monitoring Performa & Latensi Jaringan Antar Laptop">
        <x-slot:actions>
            <label class="cluster text-sm" for="monitor-interval">Interval:
                <select id="monitor-interval" class="control" x-model="interval" @change="schedule()">
                    <option value="5000">5 Detik</option><option value="10000">10 Detik (Rekomendasi)</option>
                    <option value="30000">30 Detik</option><option value="0">Jeda (Pause)</option>
                </select>
            </label>
            <x-button @click="refresh()" ::disabled="loading" title="Segarkan Metrik Sekarang"><x-icon name="refresh" />Segarkan</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="notice" :class="{ 'notice--warning': metrics.diagnosis?.overall_status === 'WARNING', 'notice--danger': metrics.diagnosis?.overall_status === 'CRITICAL', 'notice--success': metrics.diagnosis?.overall_status === 'OPTIMAL' }">
        <strong x-text="(metrics.diagnosis?.health_score ?? '--') + '%'"></strong>
        <span class="notice__body"><strong x-text="metrics.diagnosis?.overall_status || 'Memuat...'"></strong> — <span x-text="metrics.diagnosis?.summary || 'Menganalisis performa server...'"></span></span>
    </div>

    <div class="stat-grid">
        <div class="stat"><span class="stat__label">Host Server</span><strong class="stat__value" x-text="metrics.server?.name || '--'"></strong>
            <span class="text-sm text-muted">IP: <span class="mono" x-text="metrics.server?.ip || '--'"></span> • PHP <span x-text="metrics.server?.php_version || '--'"></span></span></div>
        <div class="stat"><span class="stat__label">Latensi Laptop Ini</span><strong class="stat__value" :class="pingRtt > 250 ? 'tone--danger' : (pingRtt > 80 ? 'tone--warning' : 'tone--success')" x-text="pingRtt !== null ? pingRtt + ' ms' : '--'"></strong>
            <span class="text-sm text-muted">IP Anda: <span class="mono" x-text="metrics.client?.ip || '--'"></span> • <span x-text="pingRtt === null ? '-' : (pingRtt <= 80 ? 'Sangat Cepat (Instan)' : (pingRtt <= 250 ? 'Stabil & Normal' : 'Koneksi Lambat'))"></span></span></div>
        <div class="stat"><span class="stat__label">Memori RAM PHP</span><strong class="stat__value" x-text="(metrics.memory?.current_mb ?? 0) + ' MB'"></strong>
            <span class="text-sm text-muted">Peak: <span x-text="(metrics.memory?.peak_mb ?? 0) + ' MB'"></span> • Batas: <span x-text="metrics.memory?.limit || '-'"></span></span></div>
        <div class="stat"><span class="stat__label">OPcache & JIT</span><strong class="stat__value" x-text="(metrics.opcache?.hit_rate_pct ?? 0) + '%'"></strong>
            <span class="text-sm text-muted">OPcache: <span x-text="metrics.opcache?.enabled ? 'AKTIF' : 'NON-AKTIF'"></span> • JIT: <span x-text="metrics.opcache?.jit_enabled ? 'ON' : 'OFF'"></span> • <span x-text="metrics.opcache?.cached_scripts ?? 0"></span> file</span></div>
    </div>

    <x-card title="Tes Koneksi & Ping ke IP Laptop Lain (Remote Probe)">
        <p class="text-sm text-muted">Kirim IP laptop lain di jaringan lokal untuk menguji apakah PC server dapat terhubung ke laptop tersebut dan berapa latensinya.</p>
        <form class="cluster" @submit.prevent="runProbe()" data-no-lock>
            <label class="sr-only" for="probe-target">IP Laptop Target</label>
            <input id="probe-target" class="control dash-search" type="text" x-model="target" placeholder="Masukkan IP Laptop Target (misal: 192.168.1.50)" required>
            <x-button type="submit" variant="primary" ::disabled="probing || !target" x-text="probing ? 'Menguji IP...' : 'Uji Koneksi IP Sekarang'">Uji Koneksi IP Sekarang</x-button>
            <x-button @click="target = metrics.client?.ip || ''">Gunakan IP Saya</x-button>
        </form>
        <div class="notice" x-show="probe" x-cloak :class="probe?.is_reachable ? 'notice--success' : 'notice--danger'">
            <span class="notice__body"><strong x-text="probe?.is_reachable ? 'Laptop Terhubung & Merespon!' : 'Laptop Tidak Merespon / Timeout'"></strong>
                Server (<span x-text="probe?.server_ip"></span>) ⟶ <strong class="mono" x-text="probe?.target_ip"></strong>
                <span x-show="!probe?.is_reachable" class="text-sm">Catatan: jika laptop target menyalakan Firewall Windows (Block ICMP), ping mungkin timeout meskipun browser tetap bisa membuka alamat server.</span></span>
            <strong x-text="probe?.rtt_ms !== null && probe?.rtt_ms !== undefined ? probe.rtt_ms + ' ms' : 'Offline'"></strong>
        </div>
    </x-card>

    @include('diagnostics.partials.users')
    @include('diagnostics.partials.system')
</div>
@endsection
