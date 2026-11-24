{{-- Detail Arsip, right column: location, retention, audit trail (Laravel 7 archives/show). --}}
@php($expiry = \App\Support\ArchiveDetail::expiryBadge($archive))
<x-card title="Lokasi Fisik Gudang">
    @if ($archive->location)
        <div class="stack">
            <div><span class="text-muted text-sm">GUDANG & SLOT RAK:</span><p><strong>{{ \App\Support\ArchiveDetail::slotLocation($archive) }}</strong></p></div>
            <p class="text-muted text-sm">{{ $archive->location->warehouse->name ?? '' }} ({{ $archive->location->warehouse?->getAttribute('address') ?? '' }})</p>
        </div>
    @else
        <p class="text-muted">Belum dilakukan penempatan slot rak gudang.</p>
    @endif
</x-card>

<x-card title="Masa Simpan & Expiry Retensi">
    <dl class="kv">
        <div><dt>Durasi Retensi</dt><dd>{{ \App\Support\ArchiveDetail::retentionLabel($archive) }}</dd></div>
        <div><dt>Jatuh Tempo (Expiry)</dt><dd>{{ $archive->retention_expiry_date?->format('M Y') ?? '-' }}</dd></div>
    </dl>
    @if ($expiry)
        <p><span class="badge badge--{{ $expiry['tone'] }}">{{ $expiry['text'] }}</span></p>
    @endif
</x-card>

<x-card title="Audit Trail & Riwayat Berkas">
    @if ($archive->entryLogs->isEmpty() && $archive->borrowingLogs->isEmpty() && ! $archive->destructionLog)
        <p class="text-muted">Belum ada riwayat aktivitas.</p>
    @else
        <ol class="timeline">
            @foreach ($archive->entryLogs as $log)
                <li class="is-success">
                    <strong>Check-in Gudang</strong>
                    <p class="text-sm">{{ $log->getAttribute('notes') }}</p>
                    <span class="text-muted text-sm">{{ $log->picGudang->name ?? 'PIC Gudang' }} • {{ $log->getAttribute('entry_date')?->format('d/m/Y H:i') }}</span>
                </li>
            @endforeach
            @foreach ($archive->borrowingLogs as $log)
                <li>
                    <strong>Penarikan ({{ strtoupper($log->status) }})</strong>
                    <p class="text-sm">Tujuan: {{ $log->getAttribute('purpose') }}</p>
                    <span class="text-muted text-sm">{{ $log->borrower->name ?? 'User' }} • {{ $log->created_at?->format('d/m/Y') }}</span>
                </li>
            @endforeach
            @if ($archive->destructionLog)
                <li class="is-danger">
                    <strong>Pemusnahan (BAP: {{ $archive->destructionLog->getAttribute('bap_number') }})</strong>
                    <p class="text-sm">Metode: {{ $archive->destructionLog->getAttribute('method') }}</p>
                    <a class="text-sm" href="{{ \App\Support\ArchiveDetail::moduleUrl('destructions.bap', $archive->destructionLog, 'destructions.index') }}">Lihat Berita Acara Pemusnahan (BAP) &rarr;</a>
                </li>
            @endif
        </ol>
    @endif
</x-card>
