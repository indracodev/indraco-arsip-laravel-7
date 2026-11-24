{{-- Live Monitor: SQLite + WAL checkpoint, recent client IPs, laravel.log tail. --}}
<div class="stat-ribbon">
    <x-card title="Mesin Database SQLite (WAL Mode Aktif)">
        <dl class="kv">
            <div><dt>Ukuran DB Utama (database.sqlite)</dt><dd x-text="(metrics.database?.db_size_kb ?? 0) + ' KB'"></dd></div>
            <div><dt>Write-Ahead Log (.sqlite-wal)</dt><dd x-text="(metrics.database?.wal_size_kb ?? 0) + ' KB'"></dd></div>
            <div><dt>Shared-Memory (.sqlite-shm)</dt><dd x-text="(metrics.database?.shm_size_kb ?? 0) + ' KB'"></dd></div>
        </dl>
        <x-slot:footer>
            <x-button @click="checkpoint()" ::disabled="checkpointing" x-text="checkpointing ? 'Mengosongkan WAL...' : 'Jalankan Checkpoint WAL (TRUNCATE)'">Jalankan Checkpoint WAL (TRUNCATE)</x-button>
            <span class="text-sm" x-show="checkpointMessage" x-text="checkpointMessage"></span>
        </x-slot:footer>
    </x-card>
    <x-card title="Log Laptop & Pengguna Terakhir">
        <x-table>
            <thead><tr><th>IP Address Laptop</th><th>Nama Pengguna</th><th>Aksi / Aktivitas</th><th>Waktu</th></tr></thead>
            <tbody>
                <template x-for="(client, i) in metrics.recent_clients || []" :key="i">
                    <tr>
                        <td data-label="IP Address Laptop" class="mono" x-text="client.ip || '-'"></td>
                        <td data-label="Nama Pengguna" x-text="client.user || 'Sistem / Anonim'"></td>
                        <td data-label="Aksi / Aktivitas" x-text="client.action || '-'"></td>
                        <td data-label="Waktu" x-text="client.time || '-'"></td>
                    </tr>
                </template>
            </tbody>
        </x-table>
    </x-card>
</div>

<x-card title="Log Sistem Server (storage/logs/laravel.log)">
    <x-slot:actions>
        <label class="sr-only" for="log-filter">Filter log</label>
        <input id="log-filter" class="control" type="search" x-model="logFilter" placeholder="Filter kata kunci...">
        <x-button size="sm" @click="fetchLogs()" ::disabled="loadingLogs">Segarkan Log</x-button>
    </x-slot:actions>
    <pre class="log-view log-view--tall"><template x-for="(line, i) in filteredLogs" :key="i"><span class="log-view__line" :class="logTone(line)" x-text="line"></span></template><span x-show="filteredLogs.length === 0" x-text="logs.file_exists ? 'Tidak ada baris log yang cocok dengan filter.' : 'File laravel.log kosong atau belum ada error.'"></span></pre>
</x-card>
