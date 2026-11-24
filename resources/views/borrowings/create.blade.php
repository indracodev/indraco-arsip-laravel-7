{{--
    "Formulir Permintaan Penarikan Berkas Arsip [Form 2]" (Laravel 7 borrowings/create.blade.php): same
    sections, labels and rules (.agents/context/modules/borrowing.md). Alpine: public/js/pages/borrowing-create.js.
--}}
@extends('layouts.app')

@section('title', 'Form Permintaan Penarikan Arsip')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/borrowing-create.js') }}" defer></script>
@endpush

@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $backUrl = route($from === 'archives' ? 'archives.index' : 'borrowings.index');
    $config = [
        'archives' => $archives,
        'selected' => $selected,
        'oldIds' => array_map('intval', (array) old('archive_ids', [])),
        'purpose' => old('purpose', ''),
        'userId' => $user->id,
        'printUrl' => route('borrowings.form_print'),
    ];
@endphp

@section('content')
<div class="stack" x-data="borrowingCreate">
    <script type="application/json" id="borrowing-create-config">@json($config)</script>

    <x-page-header title="Formulir Permintaan Penarikan Berkas Arsip [Form 2]" subtitle="Pengajuan Penarikan Dokumen Arsip Tersimpan • Dukungan Multi-Box Penarikan • Wajib Dokumen Persetujuan (Approval)">
        <x-slot:actions>
            <span class="badge badge--outline" x-text="selected.length + ' Box Dipilih'">0 Box Dipilih</span>
            <x-button @click="printForm()" title="Buka Pratinjau & Cetak Form Pengajuan Dokumen Penarikan (Standar PDF/A4)"><x-icon name="printer" />Form Penarikan</x-button>
            <x-link-button :href="$backUrl" data-shortcut="Escape" title="Kembali (Esc)">Kembali (Esc)</x-link-button>
        </x-slot:actions>
    </x-page-header>

    @if ($user->role === \App\Models\User::ROLE_PIC_DEPT)
        <div class="notice notice--warning">
            <div class="notice__body"><strong>Filter Keamanan Departemen Terkunci:</strong> Menampilkan berkas arsip milik <strong>{{ $user->department->name ?? 'Departemen Anda' }} ({{ $user->department->code ?? 'DEPT' }})</strong> dengan status tersimpan di gudang.</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="notice notice--danger" role="alert">
            <div class="notice__body">
                <strong>Pengajuan Penarikan Gagal Diproses. Silakan periksa isian formulir:</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('borrowings.store') }}" enctype="multipart/form-data" class="stack" @submit="onSubmit($event)">
        @csrf
        <input type="hidden" name="from" value="{{ $from }}">
        <input type="hidden" name="is_permanent" value="1">
        <template x-for="arc in selected" :key="arc.id"><input type="hidden" name="archive_ids[]" :value="arc.id"></template>

        @include('borrowings.partials.create-boxes')

        <x-card title="2. Maksud & Alasan Keperluan Penarikan Berkas">
            <x-field label="ALASAN LENGKAP PENARIKAN" for="purpose" required error="purpose">
                <x-textarea name="purpose" rows="3" required maxlength="500" x-model="purpose"
                    placeholder="Contoh: Diperlukan untuk keperluan verifikasi audit internal perpajakan tahunan..." />
            </x-field>
        </x-card>

        <x-card title="3. Upload Dokumen Persetujuan (Approval) Penarikan">
            <div class="notice">
                <div class="notice__body text-sm">Belum memiliki lembar formulir fisik resmi untuk ditandatangani sebelum diunggah?</div>
                <x-button size="sm" @click="printForm()"><x-icon name="printer" />Cetak Form Penarikan</x-button>
            </div>
            <x-field label="Berkas Dokumen Persetujuan (Approval)" for="approval_file" required error="approval_file"
                hint="Wajib melampirkan berkas scan approval penarikan bertandatangan PIC/Manager (Format: PDF, JPG, PNG Maks. 500 KB).">
                <x-file-input name="approval_file" accept=".pdf,.jpg,.jpeg,.png" badge="WAJIB UNTUK PENARIKAN" required @change="onFile($event)" />
                <span class="field__error" role="alert" x-show="fileError" x-text="fileError" x-cloak></span>
            </x-field>
        </x-card>

        <div class="card">
            <div class="card__body cluster">
                <span class="text-sm text-muted" x-show="selected.length === 0">• Silakan pilih minimal 1 berkas arsip gudang di atas</span>
                <span class="text-sm text-muted" x-show="selected.length > 0 && !hasFile" x-cloak>• Unggah berkas dokumen approval untuk mengaktifkan tombol pengajuan</span>
                <span class="text-sm" x-show="canSubmit()" x-cloak x-text="selected.length + ' box berkas & approval lengkap, siap diajukan'"></span>
                <span class="topbar__spacer"></span>
                <x-link-button :href="$backUrl">Batal</x-link-button>
                <x-button type="submit" variant="primary" ::disabled="!canSubmit()">
                    <span x-text="selected.length > 1 ? 'Ajukan Penarikan (' + selected.length + ' Box Arsip)' : 'Ajukan Penarikan Arsip'">Ajukan Penarikan Arsip</span>
                </x-button>
            </div>
        </div>
    </form>
</div>
@endsection
