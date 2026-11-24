{{-- One catalog row: columns, markers and actions of the Laravel 7 catalog (archives/index.blade.php:197-700). --}}
@php
    /** @var \App\Models\Archive $archive */
    $logs = $archive->borrowingLogs;
    $activeBorrowing = $logs->first(fn ($log) => in_array($log->status, ['requested', 'dept_approved', 'approved'], true));
    $rejectedBorrowing = $activeBorrowing ? null : $logs->firstWhere('status', 'rejected');
    $isOut = in_array($archive->status, ['borrowed', 'taken'], true);
    $hasRevision = $archive->status === 'draft' && filled($archive->rejection_note);
    $isPic = $user->role === \App\Models\User::ROLE_PIC_DEPT;
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
    <td data-label="" class="table__select"><input type="checkbox" value="{{ $archive->id }}" aria-label="Pilih {{ $archive->title }}"></td>
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
            @if ($ownsDept && ! $activeBorrowing && $archive->status === 'in_warehouse')
                <x-link-button size="sm" :href="route('borrowings.create', ['archive_ids' => $archive->id, 'from' => 'archives'])">{{ $rejectedBorrowing ? 'Tarik Ulang' : 'Tarik' }}</x-link-button>
            @elseif ($activeBorrowing && $isPic && $ownsDept)
                <span class="badge badge--info">Sedang Ditarik</span>
            @endif
            @if ($ownsDept && $archive->status === 'draft')
                <x-link-button size="sm" :href="route('archives.edit', $archive)" :variant="$hasRevision ? 'danger' : 'secondary'">{{ $hasRevision ? 'Revisi & Ajukan' : 'Edit' }}</x-link-button>
                <x-button size="sm" variant="ghost" class="tone--danger"
                    data-confirm="Apakah Anda yakin ingin menghapus data draft arsip [{{ $archive->title }}]?"
                    data-confirm-title="Hapus Draft Berkas Arsip" data-confirm-type="danger" data-confirm-btn="Ya, Hapus">Hapus</x-button>
            @endif
            <x-link-button size="sm" :href="route('archives.show', $archive)">Detail</x-link-button>
    </td>
</tr>
