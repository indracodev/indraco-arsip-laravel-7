{{-- One penarikan row (Laravel 7 borrowings/index.blade.php:404-600): markers, columns, actions per role. --}}
@php
    /** @var \App\Models\BorrowingLog $log */
    $archive = $log->archive;
    [$label, $marker, $tone] = \App\Services\BorrowingList::STATUSES[$log->status] ?? ['Unknown', null, 'neutral'];
    $isActive = in_array($log->status, \App\Services\BorrowingList::ACTIVE, true);
    $rejections = $archive ? $archive->borrowingLogs->where('id', '!=', $log->id) : collect();
    $canRetry = $log->status === 'rejected' && $archive?->status === 'in_warehouse' && auth()->user()->can('create', [\App\Models\BorrowingLog::class, $archive]);
@endphp
<tr @class(['table__row--'.$tone => $log->status !== 'returned'])>
    <td data-label="NO. BOX & JUDUL BERKAS" class="table__wide">
        <div class="cluster">
            @if ($marker)<span class="badge badge--{{ $tone }}">{{ $marker }}</span>@endif
            @if ($log->status !== 'rejected' && $rejections->isNotEmpty())
                <span class="badge badge--danger" title="Box ini pernah ditolak pada pengajuan sebelumnya. Klik Detail untuk melihat log riwayat penolakan.">PERNAH DITOLAK</span>
            @endif
            <span class="mono">{{ $log->snapshot_box_number ?: ($archive->box_number ?? 'DRAFT') }}</span>
        </div>
        @if ($archive)
            <a class="table__title" href="{{ route('archives.show', ['archive' => $archive, 'from' => 'borrowings']) }}">{{ $log->snapshot_title ?: $archive->title }}</a>
        @else
            <span class="table__title">{{ $log->snapshot_title ?: 'Penarikan Berkas' }}</span>
        @endif
    </td>
    <td data-label="LOKASI POSISI RAK" class="table__nowrap">
        @if ($archive?->location)
            {{ $archive->displayLocation() }}
        @else
            <span class="text-muted">Belum ditentukan</span>
        @endif
    </td>
    <td data-label="PEMOHON PENARIKAN">
        <strong>{{ $log->borrower->name ?? 'User' }}</strong>
        <div class="table__meta">{{ $log->snapshot_department ?: ($archive->department->code ?? 'DEPT') }}</div>
    </td>
    <td data-label="TGL PENARIKAN" class="table__nowrap">
        <strong>{{ $log->borrow_date?->format('d/m/Y') ?? $log->request_date?->format('d/m/Y') ?? 'Menunggu Dispatch' }}</strong>
        <div class="table__meta">Diajukan: {{ $log->created_at?->format('d/m/Y') }}</div>
    </td>
    <td data-label="TUJUAN PENARIKAN" title="{{ $log->purpose }}">{{ \Illuminate\Support\Str::limit((string) $log->purpose, 80) }}</td>
    <td data-label="STATUS">
        <span class="badge badge--{{ $tone }}" data-status="{{ $log->status }}">{{ $label }}</span>
        @if ($log->status === 'rejected' && $log->approval_notes)
            <p class="table__note"><strong>Catatan Penolakan:</strong> {{ $log->approval_notes }}</p>
        @endif
    </td>
    <td data-label="BERKAS">
        @if ($files !== [])
            <x-button size="sm" data-modal-open="file-viewer" data-file-index="{{ $firstFile }}" title="Klik untuk preview berkas formulir & dokumen penarikan ({{ count($files) }} berkas)">Ada {{ count($files) }}</x-button>
        @else
            <span class="badge">Tidak Ada</span>
        @endif
    </td>
    <td data-label="" class="table__actions">
        @if ($isActive)
            @can('process', $log)
                <x-button size="sm" variant="primary" data-modal-open="borrow-dispatch-{{ $log->id }}" title="Periksa berkas & keluarkan arsip fisik">Keluarkan Arsip</x-button>
                <x-button size="sm" variant="danger" data-modal-open="borrow-reject-{{ $log->id }}" title="Tolak pengajuan penarikan berkas arsip">Tolak</x-button>
            @endcan
        @endif
        @if ($canRetry)
            <x-link-button size="sm" :href="route('borrowings.create', ['archive_id' => $log->archive_id, 'from' => 'borrowings'])" title="Ajukan penarikan ulang untuk box ini">Tarik Ulang</x-link-button>
        @endif
        <x-button size="sm" data-modal-open="borrow-detail-{{ $log->id }}" title="Lihat Detail Penarikan & Konten Box Arsip">Detail</x-button>
    </td>
</tr>
