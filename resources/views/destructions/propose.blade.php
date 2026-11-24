{{--
    "Formulir Pengajuan Usulan Pemusnahan Box Arsip" (PIC) / "Formulir Pengesahan / Pemusnahan Dokumen
    Arsip (BAP)" (admin), Laravel 7 destructions/propose.blade.php: same sections, labels and rules
    (.agents/context/modules/retention.md). Alpine: public/js/pages/destruction-form.js.
--}}
@extends('layouts.app')

@section('title', 'Form Pengajuan Pemusnahan Box')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/destruction-form.js') }}" defer></script>
@endpush

@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $isPic = $user->role === \App\Models\User::ROLE_PIC_DEPT;
    $backUrl = route('destructions.index', ['view' => 'destruction']);
    $config = [
        'kind' => 'destruction', 'archives' => $archives, 'selected' => $selected,
        'oldIds' => array_map('intval', (array) old('archive_ids', [])),
        'bap' => old('bap_number', ''), 'printUrl' => route('destructions.form_print'),
    ];
    $methods = [
        'Pencacahan Mesin Industrial Paper Shredder' => 'Pencacahan Mesin Industrial Paper Shredder',
        'Pembakaran Standard Suhu Tinggi' => 'Pembakaran Standard Suhu Tinggi (Incinerator)',
        'Peleburan Bahan Kimia & Daur Ulang' => 'Peleburan Bahan Kimia & Daur Ulang Industri',
    ];
@endphp

@section('content')
<div class="stack" x-data="retentionForm">
    <script type="application/json" id="retention-form-config">@json($config)</script>

    <x-page-header :title="$isPic ? 'Formulir Pengajuan Usulan Pemusnahan Box Arsip' : 'Formulir Pengesahan / Pemusnahan Dokumen Arsip (BAP)'"
        :subtitle="$isPic ? 'Usulan Pemusnahan dari Departemen • Dukungan Multi-Box Pemusnahan • Pratinjau & Cetak Form' : 'Pemusnahan Berkas Fisik • Berita Acara Pelaksanaan & Approval Pengesahan'">
        <x-slot:actions>
            <span class="badge badge--outline" x-text="selected.length + ' Box Dipilih'">0 Box Dipilih</span>
            <x-button @click="printForm()" title="Buka Pratinjau & Cetak Form Pengajuan Dokumen Pemusnahan (Standar PDF/A4)"><x-icon name="printer" />Form Pemusnahan</x-button>
            <x-link-button :href="$backUrl" data-shortcut="Escape" title="Kembali ke Pemusnahan (Esc)">Kembali ke Pemusnahan (Esc)</x-link-button>
        </x-slot:actions>
    </x-page-header>

    @include('destructions.partials.form-notices', ['what' => 'Pemusnahan'])

    <form method="POST" action="{{ route('destructions.store') }}" enctype="multipart/form-data" class="stack" @submit="onSubmit($event)">
        @csrf
        <template x-for="arc in selected" :key="arc.id"><input type="hidden" name="archive_ids[]" :value="arc.id"></template>

        @include('destructions.partials.form-boxes', ['kind' => 'destruction'])

        <x-card title="2. Parameter Berita Acara Pemusnahan (BAP)">
            <div @class(['form-grid', 'form-grid--2' => ! $isPic])>
                @unless ($isPic)
                    <x-field label="NOMOR BERITA ACARA (BAP)" for="bap_number" required error="bap_number">
                        <x-input name="bap_number" required maxlength="100" x-model="bap" />
                    </x-field>
                @endunless
                <x-field label="TANGGAL PELAKSANAAN PEMUSNAHAN" for="destruction_date" required error="destruction_date">
                    <x-input type="date" name="destruction_date" required :value="old('destruction_date', now()->format('Y-m-d'))" />
                </x-field>
            </div>
            <x-field label="METODE FISIK PEMUSNAHAN" for="method" required error="method">
                <x-select name="method" required :options="$methods" :selected="old('method')" />
            </x-field>
            <x-field label="CATATAN / ALASAN PEMUSNAHAN BERKAS" for="notes" optional error="notes">
                <x-textarea name="notes" rows="3" :value="old('notes')" x-ref="notes" placeholder="Catatan tambahan mengenai kondisi fisik berkas atau tim pelaksana pemusnahan..." />
            </x-field>
        </x-card>

        <x-card title="3. Upload Dokumen Persetujuan (Approval) & Berkas BAP">
            <div class="notice">
                <div class="notice__body text-sm">Belum memiliki lembar formulir fisik resmi untuk ditandatangani sebelum diunggah?</div>
                <x-button size="sm" @click="printForm()"><x-icon name="printer" />Cetak Form Pemusnahan</x-button>
            </div>
            <div @class(['form-grid', 'form-grid--2' => ! $isPic])>
                <x-field label="Dokumen Persetujuan (Approval)" for="approval_file" required error="approval_file"
                    hint="Wajib upload scan persetujuan pemusnahan bertandatangan (Format: PDF, JPG, PNG Maks. 500 KB).">
                    <x-file-input name="approval_file" accept=".pdf,.jpg,.jpeg,.png" badge="WAJIB UNTUK PEMUSNAHAN" required @change="onFile($event)" />
                    <span class="field__error" role="alert" x-show="fileError" x-text="fileError" x-cloak></span>
                </x-field>
                @unless ($isPic)
                    <x-field label="Sertifikat / Berkas BAP (Opsional)" for="certificate_file" error="certificate_file"
                        hint="Scan sertifikat atau berita acara pelaksanaan jika sudah tersedia (Format: PDF, JPG, PNG Maks. 500 KB).">
                        <x-file-input name="certificate_file" accept=".pdf,.jpg,.jpeg,.png" badge="OPSIONAL" />
                    </x-field>
                @endunless
            </div>
        </x-card>

        <div class="card">
            <div class="card__body cluster">
                <span class="text-sm text-muted" x-show="selected.length === 0">• Silakan pilih minimal 1 berkas arsip gudang di atas</span>
                <span class="text-sm text-muted" x-show="selected.length > 0 && !hasFile" x-cloak>• Unggah dokumen approval untuk {{ $isPic ? 'mengajukan usulan pemusnahan ke Gudang' : 'mengaktifkan tombol pengesahan' }}</span>
                <span class="text-sm" x-show="canSubmit()" x-cloak x-text="selected.length + ' box berkas & approval lengkap, siap {{ $isPic ? 'diajukan ke Gudang' : 'disahkan' }}'"></span>
                <span class="topbar__spacer"></span>
                <x-link-button :href="$backUrl">Batal</x-link-button>
                <x-button type="submit" variant="danger" ::disabled="!canSubmit()">
                    <span x-text="selected.length > 1 ? '{{ $isPic ? 'Kirim Pengajuan (' : 'Sahkan Pemusnahan (' }}' + selected.length + ' Box Arsip)' : '{{ $isPic ? 'Kirim Pengajuan Pemusnahan' : 'Sahkan Pemusnahan Berkas' }}'">{{ $isPic ? 'Kirim Pengajuan Pemusnahan' : 'Sahkan Pemusnahan Berkas' }}</span>
                </x-button>
            </div>
        </div>
    </form>
</div>
@endsection
