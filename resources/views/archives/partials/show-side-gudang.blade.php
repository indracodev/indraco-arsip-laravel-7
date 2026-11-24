{{-- Detail Arsip, warehouse panels (Laravel 7 archives/show + catalog superadmin_status_modal):
     verification hub (pending_verification), rack check-in (approved_booked), status change.
     Shown to PIC Gudang + admin only (ArchivePolicy@processWarehouse). --}}
@can('processWarehouse', \App\Models\Archive::class)
    @push('scripts')
        <script src="{{ \App\Support\Asset::url('js/pages/verify-panel.js') }}" defer></script>
    @endpush
    @if ($archive->status === 'pending_verification')
        <x-card title="Hub Verifikasi & Penomoran Box Gudang">
            <div class="cluster">
                <p class="text-muted notice__body">Periksa kesesuaian fisik dan lampiran berkas. Setujui untuk menghasilkan Nomor Box resmi secara otomatis.</p>
                <form method="POST" action="{{ route('archives.verify', $archive) }}">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <x-button type="submit" variant="primary" data-confirm="Setujui pengajuan arsip dan generate nomor box otomatis?"
                        data-confirm-title="Persetujuan Arsip" data-confirm-type="success" data-confirm-btn="Ya, Setujui"
                        :title="'Nomor box berikutnya (pratinjau): '.app(\App\Services\NumberingService::class)->preview($archive)">Setujui & Generate Box</x-button>
                </form>
                <x-button variant="danger" data-modal-open="verify-reject">Tolak & Minta Revisi</x-button>
            </div>
        </x-card>

        <x-modal id="verify-reject" title="Tolak Pengajuan Arsip" :data-open-on-load="$errors->has('rejection_note') ? 'true' : null">
            <form method="POST" action="{{ route('archives.verify', $archive) }}" class="stack">
                @csrf
                <input type="hidden" name="action" value="reject">
                <x-field label="Alasan / Catatan Penolakan" for="rejection_note" required error="rejection_note">
                    <x-textarea name="rejection_note" rows="3" required maxlength="1000" placeholder="Tuliskan alasan penolakan secara jelas..." :value="old('rejection_note')" />
                </x-field>
                <div class="cluster">
                    <span class="topbar__spacer"></span>
                    <x-button data-modal-close>Batal</x-button>
                    <x-button type="submit" variant="danger">Kirim Penolakan</x-button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($archive->status === 'approved_booked')
        <x-card title="Check-in Fisik & Penempatan Rak Gudang">
            <form method="POST" action="{{ route('archives.checkin', $archive) }}" class="stack">
                @csrf
                <div class="meta-grid">
                    <x-field label="Pilih Lokasi Rak Gudang Available" for="warehouse_location_id" required error="warehouse_location_id">
                        <select name="warehouse_location_id" id="warehouse_location_id" class="control" required>
                            <option value="">-- Pilih Slot Rak Gudang Available --</option>
                            @foreach (\App\Actions\Warehouse\CheckinArchive::options($archive) as $rack)
                                <option value="{{ $rack['id'] }}" @disabled($rack['disabled']) @selected((string) old('warehouse_location_id') === (string) $rack['id'])>{{ $rack['text'] }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field label="Catatan Penerimaan" for="notes" optional error="notes">
                        <x-input name="notes" maxlength="500" placeholder="Contoh: Fisik diterima segel utuh" :value="old('notes')" />
                    </x-field>
                </div>
                <div class="cluster">
                    <span class="topbar__spacer"></span>
                    <x-button type="submit" variant="primary">Konfirmasi Check-in Ke Gudang</x-button>
                </div>
            </form>
        </x-card>
    @endif

    {{-- Laravel 7: the status button is for the Super Admin on every row; PIC Gudang only on pending_destruction rows. --}}
    @if (auth()->user()->isAdmin() || $archive->status === 'pending_destruction')
        @php($target = old('target_status', in_array($archive->status, ['draft', 'pending_verification', 'in_warehouse'], true) ? $archive->status : 'in_warehouse'))
        <x-card title="Ubah Status Workflow">
            <div class="cluster">
                <p class="text-muted notice__body">Status saat ini: <strong>{{ \App\Support\StatusLabel::archive($archive->status, filled($archive->rejection_note))['label'] }}</strong>. Pindahkan alur berkas secara manual (Global Override).</p>
                <x-button data-modal-open="status-change">Ubah Status</x-button>
            </div>
        </x-card>

        <x-modal id="status-change" title="Super Admin: Ubah Status Workflow" :data-open-on-load="$errors->hasAny(['target_status', 'reason', 'box_number', 'scan_input_form', 'file_path', 'scan_approval_input']) ? 'true' : null">
            <form method="POST" action="{{ route('archives.superadmin_status', $archive) }}" enctype="multipart/form-data" class="stack" x-data="{ target: @js($target) }">
                @csrf
                <dl class="kv">
                    <div><dt>No. Box Arsip</dt><dd class="mono">{{ $archive->box_number ?: 'Pending' }}</dd></div>
                    <div><dt>Departemen</dt><dd>{{ $archive->department->name ?? 'Dept' }}</dd></div>
                    <div><dt>Judul Dokumen</dt><dd>{{ $archive->title }}</dd></div>
                </dl>
                <fieldset class="field">
                    <legend class="field__label">Pilih Status Baru <span class="field__required" aria-hidden="true">*</span></legend>
                    @foreach ([
                        'draft' => ['Draft (Kembalikan ke Draft)', 'Wajib isi alasan. PIC Dept dapat merevisi. Slot rak dibebaskan.'],
                        'pending_verification' => ['Antrean Verifikasi', 'Menunggu verifikasi PIC Gudang. Lengkapi berkas persyaratan.'],
                        'in_warehouse' => ['Tersimpan di Gudang', 'Status aktif di gudang. No. Box & berkas serah terima.'],
                    ] as $value => [$label, $help])
                        <label class="checkbox">
                            <input type="radio" name="target_status" value="{{ $value }}" x-model="target">
                            <span><strong>{{ $label }}</strong><br><span class="text-sm text-muted">{{ $help }}</span></span>
                        </label>
                    @endforeach
                    <p class="text-sm text-muted">Keluar (Out) diproses lewat Penarikan Berkas; Antrean Pemusnahan / Dimusnahkan lewat menu Pemusnahan.</p>
                    @error('target_status')<span class="field__error" role="alert">{{ $message }}</span>@enderror
                </fieldset>

                <div class="stack" x-show="target === 'pending_verification'">
                    <x-field label="1. Scan Formulir Input Fisik" for="sa_scan_input_form" optional :hint="$archive->scan_input_form ? 'Sudah ada berkas; unggah untuk mengganti.' : 'Belum ada berkas.'" error="scan_input_form">
                        <x-file-input name="scan_input_form" id="sa_scan_input_form" accept=".pdf,.jpg,.jpeg,.png" x-bind:disabled="target !== 'pending_verification'" />
                    </x-field>
                    <x-field label="2. Lampiran Digital Dokumen" for="sa_file_path" optional :hint="$archive->file_path ? 'Sudah ada berkas; unggah untuk mengganti.' : 'Belum ada berkas.'" error="file_path">
                        <x-file-input name="file_path" id="sa_file_path" accept=".pdf,.jpg,.jpeg,.png" x-bind:disabled="target !== 'pending_verification'" />
                    </x-field>
                </div>

                <div class="stack" x-show="target === 'in_warehouse'">
                    <div class="meta-grid">
                        <x-field label="No. Box Arsip" for="sa_box_number" error="box_number">
                            <x-input name="box_number" id="sa_box_number" maxlength="100" placeholder="Auto Generate Box Code" :value="old('box_number', $archive->box_number)" x-bind:disabled="target !== 'in_warehouse'" />
                        </x-field>
                        <div class="field"><span class="field__label">Lokasi Fisik</span><p>{{ $archive->displayLocation() }}</p></div>
                    </div>
                    <x-field label="Upload Berkas Serah Terima Masuk Gudang" for="sa_scan_approval_input" optional hint="PDF/JPG Maks. 500 KB" error="scan_approval_input">
                        <x-file-input name="scan_approval_input" id="sa_scan_approval_input" accept=".pdf,.jpg,.jpeg,.png" x-bind:disabled="target !== 'in_warehouse'" />
                    </x-field>
                </div>

                <x-field label="Alasan / Keterangan" for="sa_reason" error="reason"
                    hint="Wajib diisi saat mengembalikan ke Draft (dikirim ke PIC sebagai catatan revisi).">
                    <x-textarea name="reason" id="sa_reason" rows="2" maxlength="1000" :value="old('reason')" x-bind:required="target === 'draft' && {{ $archive->status !== 'draft' ? 'true' : 'false' }}"
                        placeholder="Tuliskan alasan / catatan perubahan status..." />
                </x-field>
                <div class="cluster">
                    <span class="topbar__spacer"></span>
                    <x-button data-modal-close>Batal</x-button>
                    <x-button type="submit" variant="primary">Simpan Perubahan Status</x-button>
                </div>
            </form>
        </x-modal>
    @endif
@endcan
