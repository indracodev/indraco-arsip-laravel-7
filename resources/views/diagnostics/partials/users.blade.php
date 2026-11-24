{{-- Live Monitor: "Monitor User Realtime & Klien LAN Terhubung" (status tabs, search, table). --}}
<x-card title="Monitor User Realtime & Klien LAN Terhubung">
    <div class="cluster text-sm">
        <span class="badge badge--success">Online: <span x-text="count('online')"></span></span>
        <span class="badge badge--warning">Idle: <span x-text="count('idle')"></span></span>
        <span class="badge">Offline: <span x-text="count('offline')"></span></span>
        <span class="badge badge--info">Laptop Aktif: <span x-text="count('active_clients')"></span></span>
    </div>
    <div class="cluster">
        <template x-for="tab in [['all', 'Semua', 'total'], ['online', 'Online', 'online'], ['idle', 'Idle', 'idle'], ['offline', 'Offline', 'offline']]" :key="tab[0]">
            <button type="button" class="btn btn--sm" :class="{ 'btn--primary': statusFilter === tab[0] }" @click="statusFilter = tab[0]" x-text="tab[1] + ' (' + count(tab[2]) + ')'"></button>
        </template>
        <label class="sr-only" for="user-search">Cari user</label>
        <input id="user-search" class="control push-end dash-search" type="search" x-model="search" placeholder="Cari user, IP, dept, browser...">
    </div>
    <x-table>
        <thead>
            <tr><th>Status</th><th>Nama Pengguna & Akun</th><th>Departemen</th><th>IP Address LAN</th><th>Perangkat & OS</th><th>Web Browser</th><th>Halaman / Aktivitas</th><th>Waktu Aktif</th><th class="table__actions">Aksi</th></tr>
        </thead>
        <tbody>
            <template x-for="user in users" :key="user.id">
                <tr>
                    <td data-label="Status"><span class="badge" :class="{ 'badge--success': user.status === 'online', 'badge--warning': user.status === 'idle' }" x-text="user.status === 'online' ? 'Online' : (user.status === 'idle' ? 'Idle' : 'Offline')"></span></td>
                    <td data-label="Nama Pengguna & Akun">
                        <strong x-text="user.name"></strong> <span class="badge badge--info" x-show="user.is_current_user">Anda</span>
                        <span class="text-sm text-muted"><span x-text="user.role_label"></span> • <span x-text="user.email"></span></span>
                    </td>
                    <td data-label="Departemen"><span x-text="user.department_name"></span> <span class="text-sm text-muted" x-text="user.department_code"></span></td>
                    <td data-label="IP Address LAN" class="mono" x-text="user.ip || '-'"></td>
                    <td data-label="Perangkat & OS"><span x-text="user.device || '-'"></span> <span class="text-sm text-muted" x-text="user.os || '-'"></span></td>
                    <td data-label="Web Browser" x-text="user.browser || '-'"></td>
                    <td data-label="Halaman / Aktivitas" x-text="user.last_action || '-'"></td>
                    <td data-label="Waktu Aktif"><span x-text="user.last_seen_relative"></span> <span class="text-sm text-muted" x-text="user.last_seen_time || '-'"></span></td>
                    <td data-label="" class="table__actions"><button type="button" class="btn btn--sm" :disabled="!user.ip" @click="runProbe(user.ip)" title="Uji Ping / Latensi ke laptop ini">Ping</button></td>
                </tr>
            </template>
            <tr x-show="users.length === 0"><td colspan="9">Tidak ada user yang cocok dengan kriteria pencarian.</td></tr>
        </tbody>
    </x-table>
</x-card>
