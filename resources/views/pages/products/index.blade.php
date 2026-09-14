@extends('layouts.app')

@section('title', 'Katalog Produk - INDRACO')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="fw-bold h2 mb-1">Product Catalog</h1>
            <p class="text-muted mb-0">Explore our premium selection of coffee beans, roasted ground, capsules, and instant drinks.</p>
        </div>
    </div>

    {{-- Search & Filter Form --}}
    <form action="{{ route('products.index') }}" method="GET" class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-light">
        <div class="row g-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control rounded-pill px-3" placeholder="Pencarian nama produk / SKU..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="brand" class="form-select rounded-pill px-3">
                    <option value="">Semua Merek</option>
                    @foreach($mereks as $merek)
                        <option value="{{ $merek->slug }}" {{ request('brand') == $merek->slug ? 'selected' : '' }}>{{ $merek->nama_merek }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select rounded-pill px-3">
                    <option value="">Semua Kategori</option>
                    @foreach($kategories as $kat)
                        <option value="{{ $kat->slug }}" {{ request('category') == $kat->slug ? 'selected' : '' }}>{{ $kat->nama_kategori }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary rounded-pill w-100">Filter</button>
            </div>
        </div>
    </form>

    {{-- Product Grid --}}
    <div class="row g-4 mb-5">
        @forelse($produkList as $produk)
            <div class="col-6 col-md-3">
                @include('components.card-product', ['produk' => $produk])
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-light rounded-4">
                    <h4 class="fw-bold text-muted mb-2">Produk Tidak Ditemukan</h4>
                    <p class="text-muted mb-3">Coba ubah kata kunci atau filter pencarian Anda.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-primary rounded-pill">Reset Filter</a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $produkList->links('vendor.pagination.custom') }}
    </div>
</div>
@endsection
