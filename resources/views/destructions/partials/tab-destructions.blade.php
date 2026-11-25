{{-- Tab "Alur & Riwayat Pemusnahan" (Laravel 7 destructions/index:477-699 + destruction_modals). --}}
<x-card title="Alur & Antrean Pemusnahan Berkas (BAP)">
    <p class="text-sm text-muted">Daftar usulan pengajuan dari departemen, antrean pemusnahan gudang, dan riwayat berkas yang telah resmi dimusnahkan.</p>
    <input class="control" type="search" data-table-search="dest-table" placeholder="Cari BAP, judul, metode..." aria-label="Cari BAP, judul, metode">

    <x-table id="dest-table">
        <thead>
            <tr>
                <th>NOMOR BAP</th>
                <th>JUDUL BERKAS ARSIP</th>
                <th>STATUS ALUR</th>
                <th>TANGGAL EKSEKUSI</th>
                <th>METODE PEMUSNAHAN</th>
                <th>EKSEKUTOR / PEMOHON</th>
                <th class="table__actions">DOKUMEN BAP & TINDAKAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($destructions as $log)
                @php
                    $archive = $log->archive;
                    [$label, $tone] = \App\Services\RetentionList::DESTRUCTION[$log->approval_status] ?? ['Pengajuan Pemusnahan', 'warning'];
                    $boxName = $log->bap_number !== \App\Actions\Retention\ProposeDestruction::PENDING_BAP ? $log->bap_number : $archive->title;
                    $approvalField = filled($log->approval_file) ? 'approval_file' : 'scan_approval_destruction';
                    $approval = $fileIndex(\App\Services\RetentionList::file('Approval Pemusnahan - '.$boxName, 'destruction', $log->id, $approvalField, $log->getAttribute($approvalField)));
                    $certificate = $fileIndex(\App\Services\RetentionList::file('BAP - '.$boxName, 'destruction', $log->id, 'certificate_file', $log->certificate_file));
                    $executor = $log->proposedBy->name ?? 'Gudang Specialist';
                    $early = in_array($log->approval_status, ['pending', 'approved'], true) ? app(\App\Services\RetentionCalculator::class)->earlyNote($archive) : null;
                @endphp
                <tr class="table__row--{{ $tone }}" data-search="{{ mb_strtolower($log->bap_number.' '.$archive->title.' '.$archive->box_number.' '.$label.' '.$log->method.' '.$executor) }}">
                    <td data-label="NOMOR BAP" class="table__nowrap">
                        @if ($log->approval_status === 'completed')<strong class="mono">{{ $log->bap_number }}</strong>@else<span class="text-muted">{{ $log->bap_number ?: '(Menunggu BAP)' }}</span>@endif
                    </td>
                    <td data-label="JUDUL BERKAS ARSIP" class="table__wide">
                        <a class="table__title" href="{{ route('archives.show', ['archive' => $archive, 'from' => 'destructions']) }}">{{ $archive->title }}</a>
                        <div class="table__meta">Box: {{ $archive->box_number ?: '-' }} • Dept: {{ $archive->department->code ?? '-' }}</div>
                    </td>
                    <td data-label="STATUS ALUR">
                        <span class="badge badge--{{ $tone }}" data-status="{{ $log->approval_status }}">{{ $label }}</span>
                        @if ($early)<span class="badge badge--warning">{{ $early }}</span>@endif
                        @if ($log->approval_status === 'rejected' && $log->approval_notes)<p class="table__note"><strong>Alasan:</strong> {{ $log->approval_notes }}</p>@endif
                    </td>
                    <td data-label="TANGGAL EKSEKUSI" class="table__nowrap">{{ $log->destruction_date?->format('d M Y') ?? '-' }}</td>
                    <td data-label="METODE PEMUSNAHAN">{{ $log->method }}</td>
                    <td data-label="EKSEKUTOR / PEMOHON">{{ $executor }}</td>
                    <td data-label="" class="table__actions">
                        @if ($approval !== null)
                            <x-button size="sm" data-modal-open="file-viewer" data-file-index="{{ $approval }}" title="Lihat Dokumen Persetujuan Pemusnahan"><x-icon name="eye" />Approval</x-button>
                        @endif
                        @if ($certificate !== null)
                            <x-button size="sm" variant="danger" data-modal-open="file-viewer" data-file-index="{{ $certificate }}" title="Lihat & Cetak File Berita Acara Pemusnahan (BAP) dari Gudang"><x-icon name="printer" />Cetak BAP</x-button>
                        @else
                            <x-button size="sm" disabled title="File BAP belum diunggah oleh Gudang"><x-icon name="printer" />Cetak BAP</x-button>
                        @endif
                        @if ($canProcess)
                            @if ($log->approval_status === 'completed')
                                <x-button size="sm" data-modal-open="dest-upload-{{ $log->id }}" :title="$certificate !== null ? 'Upload ulang file BAP' : 'Upload scan berkas BAP fisik bertandatangan'"><x-icon name="upload" />{{ $certificate !== null ? 'Re-upload BAP' : 'Upload BAP' }}</x-button>
                            @elseif ($log->approval_status === 'pending')
                                <form method="POST" action="{{ route('destructions.approve', $log) }}">
                                    @csrf
                                    <x-button type="submit" size="sm" variant="primary" title="Setujui Pengajuan Masuk Antrean Gudang"
                                        data-confirm="Apakah Anda yakin menyetujui usulan pengajuan pemusnahan ini untuk masuk ke Antrean Pemusnahan Gudang?{{ $early ? ' Perhatian: box ini '.mb_strtolower($early).'.' : '' }}"
                                        data-confirm-title="Persetujuan Pemusnahan" data-confirm-type="warning" data-confirm-btn="Ya, Setujui"><x-icon name="check" />Setujui</x-button>
                                </form>
                            @elseif ($log->approval_status === 'approved')
                                <x-button size="sm" variant="primary" data-modal-open="dest-complete-{{ $log->id }}" title="Eksekusi Fisik Pemusnahan & Terbitkan No. BAP">Eksekusi BAP</x-button>
                            @endif
                            @if (in_array($log->approval_status, ['pending', 'approved'], true))
                                <x-button size="sm" variant="danger" data-modal-open="dest-reject-{{ $log->id }}" title="Tolak usulan / pengajuan pemusnahan berkas"><x-icon name="x" />Tolak</x-button>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty-state title="Belum Ada Riwayat Pemusnahan Berkas" message="Belum ada berkas yang diajukan atau dimusnahkan." /></td></tr>
            @endforelse
        </tbody>
    </x-table>
</x-card>

@if ($canProcess)
    @foreach ($destructions as $log)
        @if (in_array($log->approval_status, ['pending', 'approved'], true))
            @include('destructions.partials.reject-modal', ['id' => 'dest-reject-'.$log->id, 'action' => route('destructions.reject', $log), 'archive' => $log->archive,
                'title' => 'Tolak Pengajuan Pemusnahan', 'hint' => 'Berikan alasan penolakan usulan pemusnahan berkas arsip.', 'placeholder' => 'Jelaskan alasan pengajuan pemusnahan ditolak...'])
        @endif
        @if ($log->approval_status === 'approved')
            @include('destructions.partials.complete-modal', ['log' => $log])
        @elseif ($log->approval_status === 'completed')
            <x-modal :id="'dest-upload-'.$log->id" title="Upload Berita Acara Pemusnahan (BAP)">
                <form method="POST" action="{{ route('destructions.upload_bap', $log) }}" enctype="multipart/form-data" class="stack">
                    @csrf
                    <p class="text-sm text-muted">Unggah scan fisik dokumen BAP resmi bertandatangan agar departemen dapat mencetak dan mengunduh berkas.</p>
                    <dl class="meta-grid">
                        <div><dt>NO. BAP</dt><dd class="mono">{{ $log->bap_number }}</dd></div>
                        <div><dt>BERKAS ARSIP</dt><dd>{{ $log->archive->title }}</dd></div>
                    </dl>
                    <x-field label="Pilih Berkas Scan BAP Resmi Bertandatangan" :for="'post_certificate_file_'.$log->id" required error="certificate_file"
                        hint="Scan surat berita acara fisik bertandatangan (Format: PDF, JPG, PNG Maks. 500 KB).">
                        <x-file-input name="certificate_file" :id="'post_certificate_file_'.$log->id" accept=".pdf,.jpg,.jpeg,.png" badge="WAJIB" required />
                    </x-field>
                    <div class="cluster">
                        <span class="topbar__spacer"></span>
                        <x-button data-modal-close>Batal</x-button>
                        <x-button type="submit" variant="primary"><x-icon name="check" />Simpan & Upload BAP</x-button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endforeach
@endif
