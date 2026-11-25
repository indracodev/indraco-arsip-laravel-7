{{-- Detail Arsip (Laravel 7 archives/show.blade.php): same sections and actions per role.
     Warehouse panels (verify, check-in, status change): partials/show-side-gudang. --}}
@extends('layouts.app')

@section('title', 'Detail Arsip - '.$archive->title)

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/archive-show.js') }}" defer></script>
@endpush

@section('content')
    @php
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $isPic = $user->role === \App\Models\User::ROLE_PIC_DEPT;
        $isGudang = $user->role === \App\Models\User::ROLE_PIC_GUDANG;
        $isWarehouseStaff = $isGudang || $user->isAdmin();
        $canPrintLabel = in_array($archive->status, ['approved_booked', 'in_warehouse', 'pending_extension'], true) && $archive->warehouse_location_id;
        $labelRoute = \Illuminate\Support\Facades\Route::has('archives.print_sticker');
    @endphp

    <x-page-header :title="'DETAIL BERKAS: '.($archive->box_number ?? 'DRAFT (NO BOX PENDING)')" :subtitle="$archive->title">
        <x-slot:actions>
            <x-link-button :href="request()->fullUrl()" title="Refresh Data (F5)"><x-icon name="refresh" />Refresh (F5)</x-link-button>
            @can('update', $archive)
                <x-link-button :href="route('archives.edit', $archive)" variant="primary">{{ $archive->status === 'draft' ? 'Edit & Ajukan (F2)' : 'Edit Data' }}</x-link-button>
            @endcan
            @unless ($isPic)
                @if ($canPrintLabel && $labelRoute)
                    <x-link-button :href="route('archives.print_sticker', $archive)" title="Cetak Stiker Label Box">Cetak Label</x-link-button>
                @else
                    <x-button disabled aria-disabled="true" :title="! $canPrintLabel
                        ? ($archive->warehouse_location_id ? 'Label dinonaktifkan: Hanya dapat dicetak untuk arsip yang tersimpan di rak gudang atau perpanjangan (status saat ini: '.\App\Support\StatusLabel::archive($archive->status)['label'].')' : 'Label dinonaktifkan: Lokasi rak gudang belum ditentukan (tahap verifikasi/approval atau belum check-in ke rak)')
                        : 'Cetak label tidak tersedia'">Cetak Label</x-button>
                @endif
            @endunless
            @if ($user->isAdmin() && \App\Support\ArchiveDetail::canProposeDestruction($archive))
                <x-link-button variant="danger" :href="\App\Support\ArchiveDetail::moduleUrl('destructions.propose.archive', $archive, 'destructions.index')">Proses Pemusnahan</x-link-button>
            @endif
            <x-link-button :href="\App\Support\ArchiveDetail::backUrl(request('from'), $user)" data-back data-shortcut="Escape" title="Kembali ke Daftar (Esc)">Kembali</x-link-button>
        </x-slot:actions>
    </x-page-header>

    @if ($archive->status === 'draft' && ($isPic || $user->isAdmin()))
        <div class="notice notice--warning">
            <div class="notice__body"><strong>DOKUMEN MASIH BERUPA DRAFT SEMENTARA:</strong> Draft ini belum diajukan ke antrean kurator gudang. Silakan lengkapi data dan lampiran dokumen, lalu simpan permanen.</div>
            @can('update', $archive)
                <x-link-button size="sm" :href="route('archives.edit', $archive)">Edit & Simpan Permanen</x-link-button>
            @endcan
        </div>
    @endif

    @if (filled($archive->rejection_note))
        <div class="notice notice--danger" role="alert">
            <div class="notice__body"><strong>CATATAN PENOLAKAN PIC GUDANG:</strong><p>{{ $archive->rejection_note }}</p></div>
        </div>
    @endif

    @include('archives.partials.show-side-gudang')

    <div class="detail-grid">
        <div class="stack">
            @include('archives.partials.show-info')
            @include('archives.partials.show-files')
        </div>
        <div class="stack">
            @include('archives.partials.show-side')
        </div>
    </div>
@endsection
