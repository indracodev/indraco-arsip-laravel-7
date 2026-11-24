{{-- Log History tab "Semua Aktivitas User & Sistem" (Laravel 7 columns + "Detail Log" payload). --}}
<x-table>
    <thead>
        <tr><th>Waktu & IP</th><th>Pengguna / Role</th><th>Modul & Aksi</th><th>Deskripsi Aktivitas</th><th>Payload / Metadata</th></tr>
    </thead>
    <tbody>
        @foreach ($logs as $log)
            @php($role = ['admin' => 'Super Admin', 'pic_gudang' => 'PIC Gudang', 'pic_dept' => 'PIC Dept'][$log->user_role] ?? ($log->user_role ?: 'system'))
            <tr>
                <td data-label="Waktu & IP">
                    <span class="mono">{{ $log->created_at?->format('d/m/Y H:i:s') }} WIB</span>
                    <span class="text-muted text-sm">{{ $log->created_at?->diffForHumans() }} • {{ $log->ip_address ?? '127.0.0.1' }}</span>
                </td>
                <td data-label="Pengguna / Role">
                    <strong>{{ $log->user_name ?? 'System/Guest' }}</strong>
                    <span class="text-sm"><span class="badge">{{ $role }}</span> @if ($log->department)[{{ $log->department->code }}]@endif</span>
                </td>
                <td data-label="Modul & Aksi">
                    <span>{{ \App\Support\ActivityLogLabel::module($log->module) }}</span>
                    <span class="badge badge--{{ \App\Support\ActivityLogLabel::tone($log->action) }}">{{ \App\Support\ActivityLogLabel::action($log->action) }}</span>
                    <span class="mono text-sm text-muted">[{{ $log->action }}]</span>
                </td>
                <td data-label="Deskripsi Aktivitas" class="table__wide">
                    {{ $log->description }}
                    @if ($log->reference_id)<span class="text-sm text-muted">Ref ID: <span class="mono">{{ $log->reference_id }}</span></span>@endif
                </td>
                <td data-label="Payload / Metadata">
                    <details class="log-detail">
                        <summary class="btn btn--sm">Detail Log</summary>
                        <dl class="kv">
                            <div><dt>Audit Log</dt><dd>#{{ $log->id }} • {{ $log->created_at?->translatedFormat('d F Y, H:i:s') }} WIB</dd></div>
                            <div><dt>User Agent / Browser</dt><dd>{{ $log->user_agent ?? '-' }}</dd></div>
                        </dl>
                        <pre class="log-view">{{ $log->properties ? json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '{ "status": "No additional payload recorded" }' }}</pre>
                    </details>
                </td>
            </tr>
        @endforeach
    </tbody>
</x-table>
