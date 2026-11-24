{{--
    Box submission form: create ("Form Pengajuan Multi-Box Arsip") and edit (draft / revision, or an
    admin "Edit Data"). Same sections, fields, labels and actions as Laravel 7 archives/create.blade.php
    and edit.blade.php (.agents/context/modules/archive.md). Alpine: public/js/pages/archive-form*.js.
--}}
@extends('layouts.app')

@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $isEdit = $archive !== null;
    $isDraft = ! $isEdit || $archive->status === 'draft';
    $pageTitle = ! $isEdit ? 'Form Pengajuan Multi-Box Arsip (TB 30g)'
        : ($isDraft ? 'Edit Draft Pengajuan Box Arsip (TB 30g)' : 'Edit Data Berkas Arsip ('.($archive->box_number ?: 'Standar Box TB 30g').')');
    $config = [
        'departments' => $departments,
        'boxes' => $boxes,
        'deptId' => old('department_id', $archive->department_id ?? ($user->department_id ?? ($departments[0]['id'] ?? ''))),
        'subDeptId' => (string) old('sub_department_id', $archive->sub_department_id ?? ''),
        'tglPenyerahan' => old('tgl_penyerahan', $archive?->tgl_penyerahan?->format('Y-m-d') ?? now()->format('Y-m-d')),
        'currentMonth' => now()->format('Y-m'),
        'userId' => $user->id,
        'archiveId' => $archive?->id,
        'hasExistingScan' => filled($archive?->scan_input_form),
        'autosave' => ! $isEdit && old('boxes') === null,
        'similarityUrl' => route('api.archives.check_similarity'),
    ];
@endphp

@section('title', $isEdit ? ($isDraft ? 'Edit Draft Pengajuan Box Arsip - ' : 'Edit Berkas Arsip - ').$archive->title : 'Form Pengajuan Multi-Box Arsip')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/archive-form-submit.js') }}" defer></script>
    <script src="{{ \App\Support\Asset::url('js/pages/archive-form.js') }}" defer></script>
@endpush

@section('content')
<div class="stack" x-data="archiveForm">
    <script type="application/json" id="archive-form-config">@json($config)</script>

    <x-page-header :title="$pageTitle" :subtitle="$isEdit ? 'Pencatatan Master Box & Multi-Item Berkas Dokumen Arsip • Standar Box TB 30g' : 'Entri kolektif banyak box fisik & dense items dokumen dengan otomasi periode cerdas.'">
        <x-slot:actions>
            @if ($isEdit)
                <x-badge-status :status="$archive->status" />
            @endif
            <span class="badge badge--outline" x-text="boxes.length + ' Box • ' + totalItems() + ' Berkas'"></span>
            <span class="badge badge--success" x-show="restored" x-cloak title="Data formulir tersimpan otomatis di browser workstation">Draft Autosave: <span x-text="restored"></span></span>
            <x-button disabled title="Pratinjau & cetak Form Pengajuan (menyusul)">Form Pengajuan</x-button>
            @unless ($isEdit)
                <x-button disabled title="Impor Excel (dibangun terakhir, keputusan ASK 5)"><x-icon name="upload" />Impor Excel</x-button>
            @endunless
            <x-link-button :href="route('archives.index')" data-shortcut="Escape">Kembali (Esc)</x-link-button>
        </x-slot:actions>
    </x-page-header>

    @if ($isEdit && filled($archive->rejection_note))
        <div class="notice notice--danger" role="alert">
            <x-icon name="alert" />
            <div>
                <strong>CATATAN REVISI DARI ADMIN GUDANG:</strong>
                <p>{{ $archive->rejection_note }}</p>
                <p class="text-sm">Silakan periksa dan perbaiki data atau dokumen lampiran di bawah ini sesuai arahan di atas, lalu simpan atau ajukan kembali.</p>
            </div>
        </div>
    @endif

    <div class="notice notice--warning" x-show="restored" x-cloak>
        <x-icon name="refresh" />
        <div>
            <strong>Draft Pengisian Formulir Berhasil Dipulihkan Otomatis!</strong>
            <p class="text-sm">Data pengisian (<span x-text="boxes.length + ' Box, ' + totalItems() + ' Berkas'"></span>) dipulihkan dari sesi sebelum terputus/refresh (Tersimpan: <span x-text="restored"></span>).</p>
            <div class="cluster">
                <x-button size="sm" variant="primary" @click="restored = null">Lanjutkan Mengisi</x-button>
                <x-button size="sm" variant="ghost" class="tone--danger" @click="clearSavedDraft()" title="Hapus draft tersimpan dan kembali ke form kosong awal">Kosongkan Form</x-button>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="notice notice--danger" role="alert">
            <x-icon name="alert" />
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" class="stack" novalidate
        action="{{ $isEdit ? route('archives.update', $archive) : route('archives.store') }}">
        @csrf
        {{-- Set by the clicked button (a disabled submit button would not send its own value). --}}
        <input type="hidden" name="submit_action" value="submit">
        @if ($isEdit)
            @method('PUT')
            <input type="hidden" name="original_updated_at" value="{{ $archive->updated_at?->format('Y-m-d H:i:s') }}">
        @endif

        @include('archives.partials.form-unit')
        @include('archives.partials.form-boxes')
        @include('archives.partials.form-files')

        <x-card>
            <div class="cluster">
                <span>{{ $isEdit ? 'Total:' : 'Ringkasan Pengajuan:' }} <strong x-text="boxes.length + ' Box'"></strong> dengan <strong x-text="totalItems() + ' Berkas Dokumen'"></strong></span>
                <span class="topbar__spacer"></span>
                <x-link-button :href="route('archives.index')">Batal</x-link-button>
                @if ($isDraft)
                    <x-button type="submit" value="draft" formnovalidate @click="beforeSubmit($event, false)"
                        title="Simpan sementara semua box sebagai draft usulan (tanpa pembatasan wajib isi apapun)">Simpan Sementara (Draft)</x-button>
                    <x-button type="submit" value="submit" variant="primary" @click="beforeSubmit($event, true)"
                        title="Simpan & Ajukan seluruh box ke PIC Gudang (Wajib menyertakan Scan Formulir)">
                        @if ($isEdit)
                            Simpan &amp; Ajukan Permanen ke Gudang
                        @else
                            <span x-text="'Simpan & Ajukan ' + boxes.length + ' Box Arsip'">Simpan &amp; Ajukan 1 Box Arsip</span>
                        @endif
                    </x-button>
                @else
                    <x-button type="submit" value="update" variant="primary" @click="beforeSubmit($event, true)" title="Simpan perubahan data berkas arsip">Simpan Perubahan Berkas</x-button>
                @endif
            </div>
        </x-card>
    </form>

    @include('archives.partials.duplicate-box-modal')
</div>
@endsection
