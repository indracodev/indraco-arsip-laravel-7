{{-- Layout Gudang 2D (Laravel 7 master/warehouse_layout + 13 canvas partials + 11 modals).
     One <canvas> for every gudang / rak (cheapest for ~100 objects on PCs without GPU), the inspector
     and modals are plain DOM filled by public/js/pages/warehouse-layout*.js (no framework on the
     canvas data). A rack's 100 slots are built only when its grid opens. --}}
@extends('layouts.app')

@section('title', 'Interactive Layout Gudang 2D')

@push('scripts')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/pages/warehouse-layout.css') }}">
    @foreach (['', '-render', '-pointer', '-panel', '-forms', '-batch', '-rack'] as $part)
        <script src="{{ \App\Support\Asset::url('js/pages/warehouse-layout'.$part.'.js') }}" defer></script>
    @endforeach
@endpush

@section('content')
    @php
        $config = ['isAdmin' => $isAdmin, 'colors' => $colors, 'urls' => [
            'data' => route('api.warehouse.layout_data'), 'search' => route('api.warehouse.layout_search'),
            'store' => route('api.warehouse.locations.store'), 'positions' => route('api.warehouse.locations.positions'),
            'location' => url('/api/warehouse/locations'), 'archive' => url('/archives'),
        ]];
    @endphp
    <script type="application/json" id="wl-config">@json($config)</script>

    <div class="wl" id="wl">
        <x-page-header title="Interactive Layout Gudang (2D Canvas)" subtitle="Peta visual denah gudang fisik PT Indraco. Tambah Gudang/Sektor (Square) & Rak (Rectangle), resize, drag, warna & nama." />

        @include('master.warehouse-layout.partials.toolbar')

        <div class="wl-work" id="wl-work">
            <div class="wl-stage">
                <p class="wl-loading" id="wl-loading" role="status">Memuat Layout Canvas Gudang...</p>
                <div class="wl-scroll" id="wl-scroll">
                    <canvas id="wl-canvas" class="wl-canvas" width="950" height="750" tabindex="0" aria-label="Denah gudang. Panah: geser, R: putar, Delete: hapus, Shift+Klik: multi-select, klik 2x rak: denah slot."></canvas>
                </div>
                @include('master.warehouse-layout.partials.context-menu')
            </div>
            @include('master.warehouse-layout.partials.inspector')
        </div>
    </div>

    @include('master.warehouse-layout.partials.object-modals')
    @include('master.warehouse-layout.partials.batch-modals')
    @include('master.warehouse-layout.partials.rack-modal')
    <div class="toasts wl-toasts" id="wl-toasts" aria-live="polite"></div>
@endsection
