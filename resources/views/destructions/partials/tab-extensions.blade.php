{{-- Tab "Riwayat Perpanjangan" (Laravel 7 destructions/partials/tab_extensions + reject modal). --}}
<x-card title="Riwayat & Log Pengajuan Perpanjangan Masa Simpan">
    <p class="text-sm text-muted">Catatan arsip yang telah disetujui perpanjangan masa retensinya beserta lampiran dokumen pendukung.</p>
    @include('destructions.partials.search-form', ['id' => 'ext-search'])
    <input class="control" type="search" name="q" value="{{ request('q') }}" form="ext-search" data-table-search="ext-table" placeholder="Cari Box, judul, pemohon..." aria-label="Cari Box, judul, pemohon">

    <x-table id="ext-table">
        <thead>
            <tr>
                <th>NO. BOX ARSIP</th>
                <th>JUDUL BERKAS & DEPT</th>
                <th>WAKTU PENGAJUAN</th>
                <th>TAMBAHAN RETENSI</th>
                <th>EXPIRY BARU</th>
                <th>ALASAN PERPANJANGAN</th>
                <th>PEMOHON</th>
                <th>STATUS</th>
                <th class="table__actions">DOKUMEN & AKSI</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $ext)
                @php
                    $archive = $ext->archive;
                    [$label, $tone] = \App\Services\RetentionList::EXTENSION[$ext->approval_status] ?? ['Unknown', 'neutral'];
                    $duration = '+'.\App\Services\RetentionCalculator::durationLabel($ext->additional_months);
                    $file = $fileIndex(\App\Services\RetentionList::file('Form Perpanjangan - '.($archive->box_number ?: $archive->title), 'extension', $ext->id, 'scan_extension_form', $ext->scan_extension_form));
                @endphp
                <tr class="table__row--{{ $tone }}" data-search="{{ mb_strtolower($archive->box_number.' '.$archive->title.' '.($ext->proposedBy->name ?? '').' '.$ext->extension_reason) }}">
                    <td data-label="NO. BOX ARSIP" class="table__nowrap mono">{{ $archive->box_number ?: $ext->archive_id }}</td>
                    <td data-label="JUDUL BERKAS & DEPT" class="table__wide">
                        <a class="table__title" href="{{ route('archives.show', ['archive' => $archive, 'from' => 'destructions']) }}">{{ $archive->title }}</a>
                        <div class="table__meta">Dept: {{ $archive->department->code ?? '-' }}</div>
                    </td>
                    <td data-label="WAKTU PENGAJUAN" class="table__nowrap">{{ $ext->created_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td data-label="TAMBAHAN RETENSI"><span class="badge badge--outline">{{ $duration }}</span></td>
                    <td data-label="EXPIRY BARU" class="table__nowrap"><strong>{{ $ext->new_expiry_date?->format('M Y') ?? '-' }}</strong></td>
                    <td data-label="ALASAN PERPANJANGAN" title="{{ $ext->extension_reason }}">{{ \Illuminate\Support\Str::limit($ext->extension_reason ?: '-', 80) }}</td>
                    <td data-label="PEMOHON">{{ $ext->proposedBy->name ?? 'PIC Dept' }}</td>
                    <td data-label="STATUS">
                        <span class="badge badge--{{ $tone }}">{{ $label }}</span>
                        @if ($ext->rejection_note)<p class="table__note"><strong>Alasan:</strong> {{ $ext->rejection_note }}</p>@endif
                    </td>
                    <td data-label="" class="table__actions">
                        @if ($file !== null)
                            <x-button size="sm" data-modal-open="file-viewer" data-file-index="{{ $file }}"><x-icon name="eye" />Lihat Bukti</x-button>
                        @else
                            <x-link-button size="sm" :href="route('destructions.extend_print', $ext->archive_id)" target="_blank" rel="noopener"><x-icon name="printer" />Cetak Form</x-link-button>
                        @endif
                        @if ($canProcess && $ext->approval_status === 'pending')
                            <form method="POST" action="{{ route('destructions.extensions.approve', $ext) }}">
                                @csrf
                                <x-button type="submit" size="sm" variant="primary" title="ACC / Setujui perpanjangan masa retensi berkas"
                                    data-confirm="Setujui perpanjangan masa retensi ({{ $duration }}) untuk Box {{ $archive->box_number ?: $archive->title }}?"
                                    data-confirm-title="ACC Perpanjangan" data-confirm-type="primary" data-confirm-btn="Ya, Setujui ACC">ACC</x-button>
                            </form>
                            <x-button size="sm" variant="danger" data-modal-open="ext-reject-{{ $ext->id }}" title="Tolak perpanjangan masa retensi berkas">Tolak</x-button>
                        @endif
                    </td>
                </tr>
            @endforeach
            @foreach ($legacyExtensions as $old)
                <tr class="table__row--success" data-search="{{ mb_strtolower($old['box'].' '.$old['title'].' '.$old['user'].' '.$old['reason']) }}">
                    <td data-label="NO. BOX ARSIP" class="table__nowrap mono">{{ $old['box'] }}</td>
                    <td data-label="JUDUL BERKAS & DEPT" class="table__wide"><span class="table__title">{{ $old['title'] }}</span><div class="table__meta">Dept: {{ $old['dept'] }}</div></td>
                    <td data-label="WAKTU PENGAJUAN" class="table__nowrap">{{ $old['date'] }}</td>
                    <td data-label="TAMBAHAN RETENSI"><span class="badge badge--outline">{{ $old['months'] > 0 ? '+'.\App\Services\RetentionCalculator::durationLabel($old['months']) : '-' }}</span></td>
                    <td data-label="EXPIRY BARU"><strong>{{ $old['expiry'] }}</strong></td>
                    <td data-label="ALASAN PERPANJANGAN">{{ \Illuminate\Support\Str::limit($old['reason'], 80) }}</td>
                    <td data-label="PEMOHON">{{ $old['user'] }}</td>
                    <td data-label="STATUS"><span class="badge badge--success">Disetujui Gudang</span></td>
                    <td data-label="" class="table__actions"></td>
                </tr>
            @endforeach
            @if ($rows->isEmpty() && $legacyExtensions->isEmpty())
                <tr><td colspan="9"><x-empty-state title="Belum Ada Riwayat Perpanjangan" message="Belum ada berkas arsip yang diajukan atau disetujui perpanjangan masa simpannya." /></td></tr>
            @endif
        </tbody>
    </x-table>
    <x-pagination :paginator="$rows" noun="pengajuan perpanjangan" />
</x-card>

@if ($canProcess)
    @foreach (collect($rows->items())->where('approval_status', 'pending') as $ext)
        @include('destructions.partials.reject-modal', ['id' => 'ext-reject-'.$ext->id, 'action' => route('destructions.extensions.reject', $ext), 'archive' => $ext->archive,
            'title' => 'Tolak Pengajuan Perpanjangan', 'hint' => 'Berikan alasan penolakan perpanjangan masa retensi berkas arsip.', 'placeholder' => 'Jelaskan alasan pengajuan perpanjangan ditolak...'])
    @endforeach
@endif
