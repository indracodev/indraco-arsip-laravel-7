{{-- Log History tab "Log Penarikan Dokumen". --}}
<x-table>
    <thead>
        <tr><th>Tgl Tarik / Selesai</th><th>No. Box & Judul Berkas</th><th>Pemohon</th><th>Tujuan Penarikan</th><th>Status</th><th>Verifikasi Gudang</th></tr>
    </thead>
    <tbody>
        @foreach ($logs as $log)
            <tr>
                <td data-label="Tgl Tarik / Selesai">
                    <span>Req: {{ $log->request_date?->format('d M Y') }}</span>
                    <span class="text-sm text-muted">Est: {{ $log->expected_return_date ? $log->expected_return_date->format('d M Y') : 'Hanya Diambil (Permanen)' }}</span>
                </td>
                <td data-label="No. Box & Judul Berkas" class="table__wide">
                    <span class="mono">{{ $log->archive->box_number ?? '-' }}</span>
                    <span>{{ $log->archive->title ?? 'Arsip' }}</span>
                </td>
                <td data-label="Pemohon">{{ $log->borrower->name ?? 'User' }}</td>
                <td data-label="Tujuan Penarikan">{{ $log->purpose }}</td>
                <td data-label="Status"><span class="badge">{{ $log->status }}</span></td>
                <td data-label="Verifikasi Gudang">{{ $log->picGudang->name ?? 'System' }}</td>
            </tr>
        @endforeach
    </tbody>
</x-table>
