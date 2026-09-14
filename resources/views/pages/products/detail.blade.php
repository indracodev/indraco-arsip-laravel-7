@extends('layouts.app')

@section('title', ($produk->nama_produk ?? 'Detail Produk') . ' - INDRACO')

@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $produk->nama_produk }}</li>
        </ol>
    </nav>

    <div class="row g-5 align-items-center mb-5">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                @if($produk->gambar_utama)
                    <img src="{{ asset($produk->gambar_utama) }}" class="img-fluid object-fit-contain mx-auto" alt="{{ $produk->nama_produk }}" style="max-height: 350px;">
                @else
                    <img src="{{ asset('images/logo-indraco-est.png') }}" class="img-fluid object-fit-contain mx-auto opacity-50" alt="Placeholder" style="max-height: 350px;">
                @endif
            </div>
        </div>
        <div class="col-md-7">
            @if($produk->merek)
                <span class="badge text-bg-primary fs-6 mb-2">{{ $produk->merek->nama_merek }}</span>
            @endif
            <h1 class="fw-bold display-6 mb-3">{{ $produk->nama_produk }}</h1>
            @if($produk->sku)
                <p class="text-muted small mb-3">SKU: <span class="fw-bold">{{ $produk->sku }}</span></p>
            @endif
            
            <h3 class="fw-bold text-success mb-4">
                {{ isset($produk->harga_reguler) && $produk->harga_reguler > 0 ? 'Rp ' . number_format($produk->harga_reguler, 0, ',', '.') : 'Hubungi Kami untuk Harga Bulk' }}
            </h3>

            <div class="mb-4">
                <h5 class="fw-bold h6">Deskripsi Singkat:</h5>
                <p class="text-muted">{{ $produk->deskripsi_singkat ?? 'Tidak ada deskripsi singkat.' }}</p>
            </div>

            @if($produk->deskripsi_lengkap)
            <div class="mb-4">
                <h5 class="fw-bold h6">Detail Spesifikasi:</h5>
                <div class="text-muted">{!! nl2br(e($produk->deskripsi_lengkap)) !!}</div>
            </div>
            @endif

            <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                @if($produk->link_shopee)
                    <a href="{{ $produk->link_shopee }}" target="_blank" class="btn btn-warning rounded-pill px-4">Beli di Shopee</a>
                @endif
                @if($produk->link_tokopedia)
                    <a href="{{ $produk->link_tokopedia }}" target="_blank" class="btn btn-success rounded-pill px-4">Beli di Tokopedia</a>
                @endif
                @if($produk->link_tiktok)
                    <a href="{{ $produk->link_tiktok }}" target="_blank" class="btn btn-dark rounded-pill px-4">Beli di TikTok Shop</a>
                @endif
                <a href="{{ route('contact') }}" class="btn btn-outline-primary rounded-pill px-4">Inquiry / Kontak Sales</a>
            </div>
        </div>
    </div>
</div>
@endsection
