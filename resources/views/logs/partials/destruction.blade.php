{{-- Log History tab "Log Pemusnahan & BAP". --}}
<x-table>
    <thead>
        <tr><th>Nomor BAP</th><th>Judul Berkas & Box</th><th>Tgl Pemusnahan</th><th>Metode Execution</th><th>Pengaju / Approval</th><th>Lampiran BAP</th></tr>
    </thead>
    <tbody>
        @foreach ($logs as $log)
            <tr>
                <td data-label="Nomor BAP" class="mono">{{ $log->bap_number }}</td>
                <td data-label="Judul Berkas & Box" class="table__wide">
                    <span>{{ $log->archive->title ?? 'Dokumen' }}</span>
                    <span class="mono text-sm text-muted">{{ $log->archive->box_number ?? '-' }}</span>
                </td>
                <td data-label="Tgl Pemusnahan">{{ $log->destruction_date?->format('d M Y') }}</td>
                <td data-label="Metode Execution">{{ $log->method }}</td>
                <td data-label="Pengaju / Approval">{{ $log->proposedBy->name ?? 'Gudang Specialist' }}</td>
                <td data-label="Lampiran BAP">
                    @if (! empty($log->certificate_file))
                        <x-link-button :href="route('destructions.bap', $log)" size="sm" target="_blank" title="Cetak Berita Acara Pemusnahan (BAP) dari Gudang"><x-icon name="printer" />Cetak BAP</x-link-button>
                    @else
                        <span class="text-muted" title="File BAP belum diunggah oleh Gudang">Cetak BAP</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</x-table>
