{{--
    "Formulir Pengajuan Perpanjangan Masa Simpan Box Arsip [Form Expiry]" (Laravel 7
    destructions/extend.blade.php + extend_scripts): same sections, labels and rules
    (.agents/context/modules/retention.md). Alpine: public/js/pages/destruction-form.js.
--}}
@extends('layouts.app')

@section('title', 'Form Perpanjangan Masa Simpan Box')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/destruction-form.js') }}" defer></script>
@endpush

@php
    $backUrl = route('destructions.index', ['view' => 'expiry']);
    $config = [
        'kind' => 'extension', 'archives' => $archives, 'selected' => $selected,
        'oldIds' => array_map('intval', (array) old('archive_ids', [])),
        'months' => (int) old('additional_months', 60), 'userMax' => $userMax,
        'printUrl' => route('destructions.extend_form_print'),
    ];
@endphp

@section('content')
<div class="stack" x-data="retentionForm">
    <script type="application/json" id="retention-form-config">@json($config)</script>

    <x-page-header title="Formulir Pengajuan Perpanjangan Masa Simpan Box Arsip [Form Expiry]" subtitle="Perpanjangan Masa Retensi Berkas • Dukungan Multi-Box Perpanjangan • Pratinjau & Cetak Form">
        <x-slot:actions>
            <span class="badge badge--outline" x-text="selected.length + ' Box Dipilih'">0 Box Dipilih</span>
            <x-button @click="printForm()" title="Buka Pratinjau & Cetak Form Pengajuan Dokumen Perpanjangan (Standar PDF/A4)"><x-icon name="printer" />Form Perpanjangan</x-button>
            <x-link-button :href="$backUrl" data-shortcut="Escape" title="Kembali ke Expiry (Esc)">Kembali ke Expiry (Esc)</x-link-button>
        </x-slot:actions>
    </x-page-header>

    @include('destructions.partials.form-notices', ['what' => 'Perpanjangan'])

    <form method="POST" action="{{ route('destructions.extend_store') }}" enctype="multipart/form-data" class="stack" @submit="onSubmit($event)">
        @csrf
        <template x-for="arc in selected" :key="arc.id"><input type="hidden" name="archive_ids[]" :value="arc.id"></template>
        <input type="hidden" name="additional_years" :value="Math.max(1, Math.round(months / 12))">

        @include('destructions.partials.form-boxes', ['kind' => 'extension'])

        <x-card title="2. Parameter Perpanjangan Masa Simpan Dokumen">
            <x-field label="TAMBAHAN MASA SIMPAN (BULAN)" for="additional_months" required error="additional_months">
                <div class="cluster">
                    <x-input type="number" name="additional_months" min="1" required x-model.number="months" ::max="maxMonths()" @input="clampMonths()"
                        ::placeholder="'Misal: 6, 12, ' + maxMonths() + '...'" /> <span class="text-sm">Bulan</span>
                </div>
                <div class="cluster text-sm">
                    <span class="text-muted">Preset:</span>
                    <template x-for="preset in presets()" :key="preset">
                        <x-button size="sm" ::class="months == preset ? 'btn--primary' : ''" @click="months = preset"
                            x-text="preset + ' Bln' + (preset == 120 ? ' (10 Thn)' : (preset == 60 ? ' (5 Thn)' : (preset == 12 ? ' (1 Thn)' : '')))"></x-button>
                    </template>
                </div>
                <span class="field__hint">* Maksimal perpanjangan masa simpan adalah <span x-text="maxMonths() + ' Bulan (' + (maxMonths() / 12) + ' Tahun)'"></span>. Penambahan dihitung dari akhir bulan expiry sebelumnya.</span>
            </x-field>
            <div class="notice" x-show="selected.length > 0 && months" x-cloak>
                <div class="notice__body text-sm">Estimasi Periode Akhir Baru: <strong x-text="newEnd(selected[0])"></strong>
                    <span x-show="selected.length > 1" x-text="'(untuk ' + selected.length + ' box terpilih)'"></span></div>
                <span class="badge badge--outline" x-text="'+' + months + ' Bulan'"></span>
            </div>
            <x-field label="ALASAN PERPANJANGAN MASA SIMPAN DOKUMEN" for="extension_reason" required error="extension_reason">
                <x-textarea name="extension_reason" rows="3" required maxlength="1000" :value="old('extension_reason')" x-ref="reason"
                    placeholder="Jelaskan secara rinci alasan dokumen ini masih harus disimpan (misal: proses audit perpajakan, audit eksternal ISO/BPOM, proses legal berkas, atau kebutuhan referensi operasional)..." />
            </x-field>
        </x-card>

        <x-card title="3. Upload Scan Formulir Perpanjangan Masa Simpan (Opsional)">
            <div class="notice">
                <div class="notice__body text-sm">Belum memiliki lembar formulir fisik resmi untuk ditandatangani sebelum diunggah?</div>
                <x-button size="sm" @click="printForm()"><x-icon name="printer" />Cetak Form Perpanjangan</x-button>
            </div>
            <x-field label="Scan Formulir Perpanjangan Masa Simpan" for="scan_extension_form" error="scan_extension_form"
                hint="Scan permohonan perpanjangan yang disetujui PIC / Manager (Format: PDF, JPG, PNG Maks. 500 KB).">
                <x-file-input name="scan_extension_form" accept=".pdf,.jpg,.jpeg,.png" badge="OPSIONAL" @change="onFile($event)" />
                <span class="field__error" role="alert" x-show="fileError" x-text="fileError" x-cloak></span>
            </x-field>
        </x-card>

        <div class="card">
            <div class="card__body cluster">
                <span class="text-sm text-muted" x-show="selected.length === 0">• Silakan pilih minimal 1 berkas arsip gudang di atas</span>
                <span class="text-sm" x-show="selected.length > 0" x-cloak x-text="selected.length + ' box berkas terpilih, siap diajukan perpanjangan'"></span>
                <span class="topbar__spacer"></span>
                <x-link-button :href="$backUrl">Batal</x-link-button>
                <x-button type="submit" variant="primary" ::disabled="!canSubmit()">
                    <span x-text="selected.length > 1 ? 'Ajukan Perpanjangan (' + selected.length + ' Box Arsip)' : 'Ajukan Perpanjangan Masa Simpan'">Ajukan Perpanjangan Masa Simpan</span>
                </x-button>
            </div>
        </div>
    </form>
</div>
@endsection
