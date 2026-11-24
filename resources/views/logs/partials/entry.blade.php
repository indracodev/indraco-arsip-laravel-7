{{-- Log History tab "Log Masuk Gudang (Fisik)". --}}
<x-table>
    <thead>
        <tr><th>Tgl Masuk Gudang</th><th>No. Box Arsip</th><th>Judul Berkas & Dept</th><th>Lokasi Fisik Slot Rak</th><th>PIC Gudang Penerima</th><th>Catatan Reception</th></tr>
    </thead>
    <tbody>
        @foreach ($logs as $log)
            <tr>
                <td data-label="Tgl Masuk Gudang">{{ $log->entry_date?->format('d M Y H:i') }} WIB</td>
                <td data-label="No. Box Arsip" class="mono">{{ $log->archive->box_number ?? 'Pending' }}</td>
                <td data-label="Judul Berkas & Dept" class="table__wide">
                    @if ($log->archive)
                        <a href="{{ route('archives.show', $log->archive) }}">{{ $log->archive->title }}</a>
                        <span class="text-sm text-muted">{{ $log->archive->department->name ?? '' }}</span>
                    @else
                        <span class="text-muted">Arsip telah dihapus/dimusnahkan</span>
                    @endif
                </td>
                <td data-label="Lokasi Fisik Slot Rak">{{ $log->location->rack_code ?? ($log->archive?->location?->rack_code ?? 'Umum') }}</td>
                <td data-label="PIC Gudang Penerima">{{ $log->picGudang->name ?? 'Gudang Specialist' }}</td>
                <td data-label="Catatan Reception">{{ $log->notes ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</x-table>
