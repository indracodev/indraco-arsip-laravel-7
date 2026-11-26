{{-- One catalog row: columns, markers and actions of the Laravel 7 catalog (archives/index.blade.php:197-700). --}}
@php
    /** @var \App\Models\Archive $archive */
    $logs = $archive->borrowingLogs;
    $activeBorrowing = $logs->first(fn ($log) => in_array($log->status, ['requested', 'dept_approved', 'approved'], true));
    $rejectedBorrowing = $activeBorrowing ? null : $logs->firstWhere('status', 'rejected');
    $isOut = in_array($archive->status, ['borrowed', 'taken'], true);
    $hasRevision = $archive->status === 'draft' && filled($archive->rejection_note);
    $isPic = $user->role === \App\Models\User::ROLE_PIC_DEPT;
    $isWarehouse = $user->isAdmin() || $user->role === \App\Models\User::ROLE_PIC_GUDANG;
    $ownsDept = $user->isAdmin() || ($isPic && (int) $archive->department_id === (int) $user->department_id);
    [$tone, $marker] = match (true) {
        $hasRevision => ['danger', null],
        $archive->status === 'pending_verification' => ['warning', 'BARU'],
        $activeBorrowing !== null || $isOut => ['info', 'DITARIK'],
        $rejectedBorrowing !== null && $archive->status === 'in_warehouse' => ['danger', 'TOLAK TARIK'],
        $archive->status === 'pending_extension' => ['info', 'PERPANJANG'],
        in_array($archive->status, ['pending_destruction', 'destroyed'], true) => ['danger', 'MUSNAH'],
        $archive->status === 'in_warehouse' => ['success', null],
        default => [null, null],
    };
    $expiry = \App\Support\ArchiveExpiry::for($archive);
    $files = $archive->fileCount();
@endphp
<tr @class(['table__row--'.$tone => $tone])>
    <td data-label="" class="table__select"><input type="checkbox" value="{{ $archive->id }}" aria-label="Pilih {{ $archive->title }}"
        @if ($ownsDept && $archive->status === 'draft') name="ids[]" form="batch-delete-drafts" data-draft @endif
        @if ($ownsDept && ! $activeBorrowing && $archive->status === 'in_warehouse') name="archive_ids[]" form="batch-borrow" data-borrowable @endif></td>
    <td data-label="NO. BOX ARSIP" class="table__nowrap mono">
        @if ($marker)<span class="badge badge--{{ $tone }}">{{ $marker }}</span>@endif
        @if ($archive->box_number)
            {{ $archive->box_number }}
        @else
            <span class="text-muted">- Belum ada box code -</span>
        @endif
    </td>
    <td data-label="JUDUL BERKAS & DEPT" class="table__wide">
        <a class="table__title" href="{{ route('archives.show', $archive) }}">{{ $archive->title }}</a>
            @if ($hasRevision)
                <p class="table__note"><strong>Catatan Revisi Gudang:</strong> {{ $archive->rejection_note }}</p>
            @endif
            @if ($rejectedBorrowing && $archive->status === 'in_warehouse')
                <p class="table__note"><strong>Penarikan Ditolak Gudang:</strong> {{ $rejectedBorrowing->approval_notes ?: 'Formulir atau berkas permohonan penarikan tidak memenuhi syarat.' }}</p>
            @endif
            <div class="table__meta">
                <span class="badge badge--outline">{{ $archive->department->code ?? 'GEN' }}</span>
                @if ($archive->subDepartment)<span class="badge" title="Sub-Departemen: {{ $archive->subDepartment->name }}">{{ $archive->subDepartment->code ?? $archive->subDepartment->name }}</span>@endif
                @if ($archive->document_type)<span class="badge" title="Tipe Dokumen">{{ $archive->document_type }}</span>@endif
                by {{ $archive->creator->name ?? 'User' }}
            </div>
    </td>
    <td data-label="PERIODE" class="table__nowrap">{{ $archive->effectivePeriode() }}</td>
    <td data-label="KONDISI FISIK">{{ $archive->physical_condition }}</td>
    <td data-label="LOKASI RAK GUDANG" class="table__nowrap">
        @if ($isOut)
            <span class="text-muted">- (Sudah Ditarik)</span>
        @elseif ($archive->location)
            {{ $archive->displayLocation() }}
        @else
            <span class="tone--danger">Belum Ditentukan</span>
        @endif
    </td>
    <td data-label="MASA SIMPAN (EXPIRY)" class="table__nowrap">
        @if ($expiry)
            <span class="tone--{{ $expiry['tone'] }}">{{ $expiry['text'] }} {{ $expiry['note'] }}</span>
        @else
            -
        @endif
    </td>
    <td data-label="STATUS"><x-badge-status :status="$archive->status" :rejected="$hasRevision" :destruction="$archive->destructionLog?->approval_status" short /></td>
    <td data-label="BERKAS">
        @if ($files > 0)
            <span class="badge badge--success" title="{{ $files }} berkas">Ada {{ $files }}</span>
        @else
            <span class="badge">Tidak Ada</span>
        @endif
    </td>
    <td data-label="" class="table__actions">
            @if ($isWarehouse && $archive->status === 'pending_verification')
                <x-link-button size="sm" variant="primary" :href="route('archives.show', $archive)" title="Verifikasi &amp; Penomoran Box Gudang">Verifikasi</x-link-button>
            @elseif ($isWarehouse && $archive->status === 'approved_booked')
                <x-link-button size="sm" variant="primary" :href="route('archives.show', $archive)" title="Check-In &amp; Penempatan Slot Rak">Check-In Rak</x-link-button>
            @elseif ($isWarehouse && in_array($archive->status, ['in_warehouse', 'pending_extension'], true) && $archive->warehouse_location_id)
                <x-link-button size="sm" :href="route('archives.print_sticker', $archive)" title="Cetak Stiker Label Box">Label</x-link-button>
            @endif
            @if ($ownsDept && ! $activeBorrowing && $archive->status === 'in_warehouse')
                <x-link-button size="sm" :href="route('borrowings.create', ['archive_ids' => $archive->id, 'from' => 'archives'])">{{ $rejectedBorrowing ? 'Tarik Ulang' : 'Tarik' }}</x-link-button>
            @elseif ($activeBorrowing && $isPic && $ownsDept)
                <span class="badge badge--info">Sedang Ditarik</span>
            @endif
            {{-- Laravel 7: admin may edit any archive, PIC Departemen only their drafts (ArchivePolicy@update). --}}
            @if ($user->isAdmin() || ($ownsDept && $archive->status === 'draft'))
                <x-link-button size="sm" :href="route('archives.edit', $archive)" :variant="$hasRevision ? 'danger' : 'secondary'" :title="$hasRevision ? 'Perbaiki Pengajuan Sesuai Catatan Revisi Gudang' : 'Edit Data Berkas Arsip'">{{ $hasRevision ? 'Revisi & Ajukan' : 'Edit' }}</x-link-button>
            @endif
            @if ($ownsDept && $archive->status === 'draft')
                <form method="POST" action="{{ route('archives.destroy', $archive) }}">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" size="sm" class="tone--danger"
                        data-confirm="Apakah Anda yakin ingin menghapus data draft arsip [{{ $archive->title }}]?"
                        data-confirm-title="Hapus Draft Berkas Arsip" data-confirm-type="danger" data-confirm-btn="Ya, Hapus">Hapus</x-button>
                </form>
            @endif
            <x-link-button size="sm" :href="route('archives.show', $archive)">Detail</x-link-button>
    </td>
</tr>
