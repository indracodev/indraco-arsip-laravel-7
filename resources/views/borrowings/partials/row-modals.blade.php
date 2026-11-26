{{-- Modals of one penarikan row (Laravel 7 borrowings/modals/{detail,dispatch,reject}_modal), same sections and texts. --}}
@php
    /** @var \App\Models\BorrowingLog $log */
    $archive = $log->archive;
    $boxNumber = $log->snapshot_box_number ?: ($archive->box_number ?? 'DRAFT');
    $title = $log->snapshot_title ?: ($archive->title ?? 'Arsip');
    $deptCode = $archive->department->code ?? 'DEPT';
    $location = $archive?->location ? $archive->displayLocation() : 'Belum ditentukan';
    $label = \App\Services\BorrowingList::label($log->status);
    $tone = \App\Services\BorrowingList::STATUSES[$log->status][2] ?? 'neutral';
    $rejections = $archive ? $archive->borrowingLogs->where('id', '!=', $log->id)->values() : collect();
    $items = $log->snapshot_items ?: ($archive && $archive->relationLoaded('items') ? $archive->items->map(fn ($i) => ['number' => $i->item_number, 'name' => $i->document_name, 'period' => $i->period_text, 'notes' => $i->notes])->all() : []);
@endphp

<x-modal id="borrow-detail-{{ $log->id }}" :title="'DETAIL PENARIKAN: '.$boxNumber">
    <div class="stack">
        <div class="cluster">
            <span class="badge badge--{{ $tone }}">{{ $label }}</span>
            @if ($rejections->isNotEmpty())<span class="badge badge--danger" title="Box ini memiliki riwayat penolakan sebelumnya">Pernah Ditolak</span>@endif
            <span class="text-muted">{{ $title }}</span>
        </div>

        <x-card title="Data Pengajuan & Fisik">
            <dl class="meta-grid">
                <div><dt>Pemohon</dt><dd>{{ $log->borrower->name ?? 'User' }}<br><span class="text-muted text-sm">{{ $log->borrower->email ?? '-' }}</span></dd></div>
                <div><dt>Departemen Box</dt><dd>{{ ($archive->department->name ?? '-').' ('.$deptCode.')' }}<br><span class="text-muted text-sm">{{ $archive->subDepartment->name ?? '-' }}</span></dd></div>
                <div><dt>Lokasi Rak Gudang</dt><dd>{{ $location }}</dd></div>
                <div><dt>Tgl Diajukan</dt><dd>{{ ($log->request_date ?? $log->created_at)?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div><dt>Tgl Keluar Fisik</dt><dd>{{ $log->borrow_date?->format('d/m/Y H:i') ?? 'Belum dikeluarkan fisik' }}</dd></div>
                <div><dt>Estimasi Kembali</dt><dd>{{ $log->expected_return_date?->format('d/m/Y') ?? 'Diambil Permanen (Tidak Dikembalikan)' }}</dd></div>
            </dl>
            <p><strong class="text-sm">Keperluan / Tujuan Penarikan:</strong><br>{{ $log->purpose }}</p>
            @if (filled($log->notes))
                <p><strong class="text-sm">Catatan Tambahan:</strong><br>{{ $log->notes }}</p>
            @endif
        </x-card>

        @unless (auth()->user()->role === \App\Models\User::ROLE_PIC_GUDANG)
            <x-card :title="'Berkas Dokumen Dalam Box ('.count($items).' Berkas)'">
                @if ($items === [])
                    <p class="text-muted">Tidak ada rincian berkas dokumen spesifik untuk box ini.</p>
                @else
                    <x-table>
                        <thead><tr><th>No</th><th>Nama Dokumen</th><th>Periode</th><th>Keterangan</th></tr></thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr><td data-label="No">{{ $item['number'] ?? $loop->iteration }}</td><td data-label="Nama Dokumen">{{ $item['name'] ?? '-' }}</td>
                                    <td data-label="Periode">{{ ($item['period'] ?? null) ?: '-' }}</td><td data-label="Keterangan">{{ ($item['notes'] ?? null) ?: '-' }}</td></tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        @endunless

        <x-card title="Berkas Terlampir">
            @include('borrowings.partials.file-cards')
        </x-card>

        <x-card title="Jejak Persetujuan & Penyerahan">
            <dl class="kv">
                <div><dt>Approval Dept:</dt><dd>{{ $log->departmentApprovedBy->name ?? ($log->department_approval_by ? 'PIC Departemen' : '-') }} · {{ $log->department_approved_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div><dt>Pengeluaran Gudang:</dt><dd>{{ $log->picGudang->name ?? ($log->pic_gudang_id ? 'PIC Gudang' : '-') }} · {{ $log->borrow_date?->format('d/m/Y H:i') ?? 'Belum dikeluarkan fisik' }}</dd></div>
            </dl>
        </x-card>

        @if ($log->status === 'rejected')
            <div class="notice notice--danger"><div class="notice__body"><strong>Catatan Penolakan Pengajuan</strong><p>{{ $log->approval_notes ?: 'Pengajuan penarikan berkas ditolak oleh petugas.' }}</p></div></div>
        @endif

        @if ($rejections->isNotEmpty())
            <x-card :title="'Log Riwayat Penolakan ('.$rejections->count().'x Pernah Ditolak)'">
                <p class="text-sm text-muted">Riwayat catatan dan alasan penolakan pada permohonan penarikan sebelumnya untuk berkas ini:</p>
                <ol class="timeline">
                    @foreach ($rejections as $rejection)
                        <li class="is-danger">
                            <strong>Penolakan #{{ $loop->iteration }}</strong> <span class="text-muted text-sm">Waktu: {{ ($rejection->updated_at ?? $rejection->created_at)?->format('d/m/Y H:i') }}</span>
                            <p class="text-sm">Catatan / Alasan Penolakan: {{ $rejection->approval_notes ?: 'Pengajuan penarikan berkas ditolak oleh petugas.' }}</p>
                            @if ($rejection->purpose)<p class="text-sm text-muted">Keperluan Diajukan: {{ $rejection->purpose }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </x-card>
        @endif
    </div>
    <x-slot:footer>
        @if ($archive)
            <x-link-button :href="route('archives.show', ['archive' => $archive, 'from' => 'borrowings'])">Buka Detail Master Arsip</x-link-button>
        @endif
        <x-button data-modal-close>Tutup</x-button>
    </x-slot:footer>
</x-modal>

@if (in_array($log->status, \App\Services\BorrowingList::ACTIVE, true))
    @can('process', $log)
        <x-modal id="borrow-dispatch-{{ $log->id }}" title="PENGELUARAN BERKAS FISIK GUDANG">
            <form method="POST" action="{{ route('borrowings.dispatch', $log) }}" enctype="multipart/form-data" class="stack">
                @csrf
                <p class="text-sm text-muted">Validasi pemeriksaan dokumen pengajuan & serah terima fisik box arsip</p>
                <x-card title="Informasi Box & Lokasi Fisik">
                    <dl class="meta-grid">
                        <div><dt>No. Box Arsip</dt><dd class="mono">{{ $boxNumber }}</dd></div>
                        <div><dt>Lokasi Rak Gudang</dt><dd>{{ $location }}</dd></div>
                        <div><dt>Judul Berkas Arsip</dt><dd>{{ $title }}</dd></div>
                        <div><dt>Pemohon Penarikan</dt><dd>{{ ($log->borrower->name ?? 'User').' ('.$deptCode.')' }}</dd></div>
                    </dl>
                </x-card>
                <x-card title="Pemeriksaan Dokumen Pengajuan">
                    @if ($files === [])
                        <div class="notice notice--danger"><div class="notice__body">Tidak ada berkas digital formulir permohonan yang dilampirkan pada sistem.</div></div>
                    @else
                        <p class="text-sm"><strong>Form / Surat Permohonan Penarikan</strong> — {{ count($files) }} berkas digital dilampirkan pemohon</p>
                        @include('borrowings.partials.file-cards')
                    @endif
                    <div class="notice notice--warning">
                        <div class="notice__body stack">
                            <p class="text-sm"><strong>Instruksi:</strong> PIC Gudang wajib memeriksa keabsahan form/surat permohonan penarikan berkas pemohon sebelum fisik box arsip diserahkan.</p>
                            <label class="checkbox"><input type="checkbox" name="confirm_file_checked" required>
                                <span><strong>Saya telah memeriksa dan memvalidasi keabsahan berkas form pengajuan ini untuk dikeluarkan dari rak gudang.</strong></span></label>
                        </div>
                    </div>
                </x-card>
                <x-field label="Upload Scan Bukti Tanda Terima Fisik (Opsional)" :for="'scan_approval_borrow_'.$log->id" error="scan_approval_borrow"
                    hint="Bagian ini hanya diisi jika ada berkas tanda terima fisik terpisah dengan tanda tangan basah saat penyerahan box.">
                    <x-file-input name="scan_approval_borrow" :id="'scan_approval_borrow_'.$log->id" accept=".pdf,.jpg,.jpeg,.png" />
                </x-field>
                <div class="cluster">
                    <x-button class="tone--danger" data-modal-open="borrow-reject-{{ $log->id }}">Tolak Pengajuan</x-button>
                    <span class="topbar__spacer"></span>
                    <x-button data-modal-close>Batal</x-button>
                    <x-button type="submit" variant="primary">Keluarkan Arsip</x-button>
                </div>
            </form>
        </x-modal>

        <x-modal id="borrow-reject-{{ $log->id }}" title="PENOLAKAN PENGAJUAN PENARIKAN">
            <form method="POST" action="{{ route('borrowings.reject', $log) }}" class="stack">
                @csrf
                <p class="text-sm text-muted">Batalkan permohonan penarikan berkas dengan alasan resmi</p>
                <dl class="kv">
                    <div><dt>No. Box Arsip:</dt><dd class="mono">{{ $boxNumber }}</dd></div>
                    <div><dt>Judul Berkas:</dt><dd>{{ $title }}</dd></div>
                    <div><dt>Pemohon:</dt><dd>{{ ($log->borrower->name ?? 'User').' ('.$deptCode.')' }}</dd></div>
                </dl>
                <x-field label="Alasan Penolakan" :for="'reason_'.$log->id" required error="reason"
                    hint="Jelaskan alasan penolakan secara jelas agar pemohon dapat memperbaiki dokumen formulir atau prosedur permohonan.">
                    <x-textarea name="reason" :id="'reason_'.$log->id" rows="3" required maxlength="500"
                        placeholder="Contoh: Dokumen formulir permohonan belum ditandatangani Kepala Departemen, atau berkas pengajuan tidak lengkap..." />
                </x-field>
                <div class="notice notice--danger"><div class="notice__body text-sm">Status pengajuan akan diubah menjadi <strong>Ditolak</strong> dan tercatat di jejak audit aktivitas sistem.</div></div>
                <div class="cluster">
                    <span class="topbar__spacer"></span>
                    <x-button data-modal-close>Batal</x-button>
                    <x-button type="submit" variant="danger">Konfirmasi Tolak Pengajuan</x-button>
                </div>
            </form>
        </x-modal>
    @endcan
@endif
