{{-- Katalog Arsip (Laravel 7 archives/index + catalog_* partials). Draft actions: ArchiveSubmissionController. --}}
@extends('layouts.app')

@section('title', 'Katalog & Booking Arsip')

@section('content')
    @php
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $canSubmit = $user->role !== \App\Models\User::ROLE_PIC_GUDANG;
        $subDepartments = $departments->flatMap->subDepartments;
        $hasFilters = request()->hasAny(['search', 'department_id', 'sub_department_id', 'document_type', 'status', 'expiry_filter']);
    @endphp

    <x-page-header title="Katalog Arsip & Pengajuan Storage" subtitle="Cari, ajukan booking gudang, dan kelola masa simpan dokumen fisik & digital PT Indraco.">
        <x-slot:actions>
            <x-link-button :href="request()->fullUrl()" title="Refresh (F5)"><x-icon name="refresh" />Refresh (F5)</x-link-button>
            @if ($canSubmit)
                <x-button data-modal-open="archive-import-modal" title="Impor Massal Multi-Box & Butir Dokumen via Excel / CSV"><x-icon name="upload" />Impor Multi-Box (Excel)</x-button>
                <x-link-button :href="route('archives.create')" variant="primary" title="Buat Draft Pengajuan Box Baru (F2)"><x-icon name="plus" />Buat Draft (F2)</x-link-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-filter-bar :action="route('archives.index')" :active="$hasFilters">
        <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
        <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
        <x-field label="CARI KEYWORD" for="search">
            <x-input name="search" type="search" :value="request('search')" placeholder="Judul, No. Box, Isi Berkas..." />
        </x-field>
        <x-field :label="$isPic ? 'DEPARTEMEN [Terkunci]' : 'DEPARTEMEN'" for="department_id">
            @if ($isPic)
                <x-select name="department_id" disabled :options="$departments->mapWithKeys(fn ($d) => [$d->id => $d->code.' - '.$d->name])" :selected="$user->department_id" />
            @else
                <x-select name="department_id" placeholder="-- Semua Departemen --" :options="$departments->mapWithKeys(fn ($d) => [$d->id => $d->code.' - '.$d->name])" :selected="request('department_id')" />
            @endif
        </x-field>
        @if ($subDepartments->isNotEmpty())
            <x-field label="SUB-DEPARTEMEN" for="sub_department_id">
                <x-select name="sub_department_id" placeholder="-- Semua Sub-Bagian --" :options="$subDepartments->mapWithKeys(fn ($s) => [$s->id => $s->name.' ('.$s->code.')'])" :selected="request('sub_department_id')" />
            </x-field>
        @endif
        <x-field label="TIPE DOKUMEN" for="document_type">
            <x-select name="document_type" placeholder="-- Semua Tipe Dokumen --" :options="array_combine($documentTypes, $documentTypes)" :selected="request('document_type')" />
        </x-field>
        <x-field label="STATUS WORKFLOW" for="status">
            <x-select name="status" placeholder="-- Semua Status Workflow --" :options="\App\Support\StatusLabel::FILTER_OPTIONS" :selected="in_array(request('status'), ['borrowed', 'taken'], true) ? 'out' : request('status')" />
        </x-field>
        <x-field label="EXPIRY MASA SIMPAN" for="expiry_filter">
            <x-select name="expiry_filter" placeholder="-- Semua Expiry --" :options="['expiring_soon' => 'Mendekati Expiry (≤ 90 Hari)', 'expired' => 'Sudah Kadaluarsa']" :selected="request('expiry_filter')" />
        </x-field>
    </x-filter-bar>

    @if ($archives->isEmpty())
        <x-card>
            <x-empty-state title="Tidak ada berkas arsip yang ditemukan berdasarkan kriteria pencarian ini." />
        </x-card>
    @else
        {{-- Laravel 7 catalog_batch_ribbon: shown when rows are ticked; "Hapus Draft (n)" only for ticked drafts. --}}
        <div class="stack" x-data="{ selected: 0, drafts: 0, borrowable: 0 }"
            @change="selected = $el.querySelectorAll('.table__select input:checked').length; drafts = $el.querySelectorAll('input[data-draft]:checked').length; borrowable = $el.querySelectorAll('input[data-borrowable]:checked').length">
        <div class="notice" x-show="selected > 0" x-cloak>
            <span>Terpilih: <strong x-text="selected"></strong> box berkas arsip.</span>
            <span class="topbar__spacer"></span>
            @if ($canSubmit)
                {{-- Laravel 7 "Tarik Berkas (n Box)": only ticked boxes in the warehouse without an active penarikan. --}}
                <form method="GET" action="{{ route('borrowings.create') }}" id="batch-borrow" x-show="borrowable > 0" data-no-lock>
                    <input type="hidden" name="from" value="archives">
                    <x-button type="submit" variant="primary" size="sm" title="Ajukan penarikan untuk box arsip yang memenuhi syarat penarikan (tersimpan di gudang & belum ditarik)">
                        Tarik Berkas (<span x-text="borrowable"></span> Box)
                    </x-button>
                </form>
                <form method="POST" action="{{ route('archives.batch_delete_drafts') }}" id="batch-delete-drafts" x-show="drafts > 0">
                    @csrf
                    <x-button type="submit" variant="danger" size="sm" title="Hapus draft yang dipilih"
                        ::data-confirm="'Apakah Anda yakin ingin menghapus ' + drafts + ' data draft arsip yang dipilih?' + (selected > drafts ? ' (' + (selected - drafts) + ' berkas non-draft akan diabaikan)' : '')"
                        data-confirm-title="Hapus Draft Arsip Terpilih" data-confirm-type="danger" ::data-confirm-btn="'Ya, Hapus ' + drafts + ' Draft'">
                        Hapus Draft (<span x-text="drafts"></span>)
                    </x-button>
                </form>
            @endif
        </div>
        <x-table>
            <thead>
                <tr>
                    <th><span class="sr-only">Pilih</span></th>
                    @include('archives.partials.sort-header', ['column' => 'box_number', 'label' => 'NO. BOX ARSIP'])
                    @include('archives.partials.sort-header', ['column' => 'title', 'label' => 'JUDUL BERKAS & DEPT'])
                    <th>PERIODE</th>
                    <th>KONDISI FISIK</th>
                    <th>LOKASI RAK GUDANG</th>
                    @include('archives.partials.sort-header', ['column' => 'retention_expiry_date', 'label' => 'MASA SIMPAN (EXPIRY)'])
                    @include('archives.partials.sort-header', ['column' => 'status', 'label' => 'STATUS'])
                    <th>BERKAS</th>
                    <th class="table__actions">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($archives as $archive)
                    @include('archives.partials.catalog-row', ['archive' => $archive, 'user' => $user])
                @endforeach
            </tbody>
        </x-table>
        </div>
    @endif

    <x-pagination :paginator="$archives" noun="berkas arsip" />

    @if ($canSubmit)
        @include('archives.partials.import-modal')
        @push('scripts')
            <script src="{{ \App\Support\Asset::url('js/pages/archive-import.js') }}" defer></script>
        @endpush
    @endif
@endsection
