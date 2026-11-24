{{-- Section 3 (Laravel 7 x-file-uploader ×2): scan of the signed form (required to submit) and photo / digital attachment (optional). 500 KB, PDF/JPG/PNG. --}}
@php
    $uploads = [
        ['name' => 'scan_input_form', 'column' => 'scan_input_form', 'remove' => 'removeScan',
            'label' => $isEdit ? 'Scan Formulir Input' : '1. Scan / Berkas Formulir Pengajuan Fisik', 'required' => ! $isEdit || (blank($archive->scan_input_form) && $archive->status !== 'draft'),
            'badge' => 'WAJIB UNTUK PENGAJUAN', 'saved' => 'Scan Formulir Tersimpan',
            'hint' => $isEdit ? 'Format: PDF, JPG, PNG (Maksimal 500 KB). Kosongkan jika tidak ingin mengganti file.' : 'Cukup unggah 1 lembar berkas formulir serah terima bertanda tangan untuk seluruh box di atas (Maksimal 500 KB, PDF/JPG/PNG).'],
        ['name' => 'file', 'column' => 'file_path', 'remove' => 'removeFile',
            'label' => $isEdit ? 'Foto Fisik Box / Lampiran Digital' : '2. Foto Fisik Box / Lampiran Digital', 'required' => false,
            'badge' => 'OPSIONAL', 'saved' => 'Lampiran Digital Tersimpan',
            'hint' => $isEdit ? 'Format: PDF, JPG, JPEG, PNG (Maksimal 500 KB). Kosongkan jika tidak ingin mengganti file.' : 'Foto fisik box arsip atau lampiran digital (Maksimal 500 KB, PDF/JPG/PNG). Otomatis terhubung ke seluruh box.'],
    ];
@endphp
<x-card :title="$isEdit ? '3. Upload Berkas Digital & Scan Formulir' : '3. Berkas Bukti Formulir Pengajuan & Foto Fisik Box (Mencakup Semua Box)'">
    <div class="form-grid form-grid--2">
        @foreach ($uploads as $upload)
            <x-field :label="$upload['label']" :for="$upload['name']" :required="$upload['required']" :hint="$upload['hint']" :error="$upload['name']">
                @if ($isEdit && filled($archive->{$upload['column']}))
                    <input type="hidden" name="remove_{{ $upload['name'] }}" :value="{{ $upload['remove'] }} ? '1' : '0'">
                    <div class="cluster text-sm">
                        <span x-show="!{{ $upload['remove'] }}"><x-icon name="check" /> {{ $upload['saved'] }}</span>
                        <span class="tone--danger" x-show="{{ $upload['remove'] }}" x-cloak>File tersimpan dihapus. Silakan pilih berkas baru di bawah.</span>
                        <x-button size="sm" variant="ghost" @click="{{ $upload['name'] === 'file' ? 'removeFile = !removeFile' : 'toggleRemoveScan()' }}" x-text="{{ $upload['remove'] }} ? 'Batal Hapus' : 'Hapus / Ganti'" title="Hapus file ini untuk diganti dengan file baru">Hapus / Ganti</x-button>
                    </div>
                @endif
                <x-file-input :name="$upload['name']" accept=".pdf,.jpg,.jpeg,.png" :badge="$upload['badge']" @change="onFileChange($event, '{{ $upload['name'] }}')" />
                <span class="field__error" role="alert" x-show="fileErrors.{{ $upload['name'] }}" x-text="fileErrors.{{ $upload['name'] }}" x-cloak></span>
            </x-field>
        @endforeach
    </div>
    @unless ($isEdit)
        <p class="text-sm text-muted"><strong>Efisiensi Berkas Kolektif:</strong> Berkas formulir serah terima dan foto fisik box di atas otomatis terhubung ke seluruh <strong x-text="boxes.length + ' box'"></strong> yang diajukan sekaligus.</p>
    @endunless
</x-card>
