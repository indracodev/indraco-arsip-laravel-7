@extends('layouts.app')

@section('title', 'INDRACO – ' . ($selectedMerek->nama_merek ?? 'Products'))

@push('styles')
   <link rel="stylesheet" href="{{ asset('css/home-banner.css') }}">
   <style>
      .banner-logo-wrapper { aspect-ratio: 4/3; }
      @media (min-width: 992px) {
         .banner-logo-wrapper { aspect-ratio: 1/1; }
      }
   
      .banner-logo-shadow {
         background-image: radial-gradient(var(--default-color), transparent, transparent);
         width: 150%;
         height: 20%;
         top: 90%;
         left: 50%;
         transform: translateX(-50%);
         display: flex;
         opacity: .5;
      }
   
      .pedestal { pointer-events: none !important; top: 70%; }
      @media (min-width: 576px) {
         .pedestal { top: 75%; }
      }
      @media (min-width: 992px) {
         .pedestal { top: 60%; }
      }
   
      .pedestal-body { width: 100%; }
      @media (min-width: 992px) {
         .pedestal-body { height: 125%; }
      }
   </style>
@endpush

@section('content')
<main id="content" tabindex="-1">

   <!-- Breadcrumbs -->
   <nav style="--bs-breadcrumb-divider: '/';" aria-label="breadcrumb" class="container small my-4">
      <ol class="breadcrumb mb-0">
         <li class="breadcrumb-item"><a href="{{ route('products.index') }}" class="text-decoration-none text-secondary">Products</a></li>
         @if(request('category'))
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}?category={{ request('category') }}" class="text-decoration-none text-secondary">{{ ucfirst(request('category')) }}</a></li>
         @endif
         <li class="breadcrumb-item active fw-semibold text-custom-1" aria-current="page">{{ $selectedMerek->nama_merek ?? 'Catalog' }}</li>
      </ol>
   </nav>

   <!-- Hero Brand Header -->
   <section class="mb-5">
      <div class="container-sm">
         <div class="row row-gap-5 align-items-lg-center">
            <div class="col col-12 col-lg-6 order-lg-2 z-0 col-xl-5">
               <div class="d-flex flex-column h-100 align-items-center position-relative banner-logo">
                  <div class="position-relative w-100 z-1 banner-logo-wrapper">
                     <div class="ratio ratio-1x1 w-50 position-relative mx-auto banner-logo-img">
                        @if($selectedMerek && $selectedMerek->logo_path && file_exists(public_path($selectedMerek->logo_path)))
                           <img src="{{ asset($selectedMerek->logo_path) }}" alt="{{ $selectedMerek->nama_merek }}" class="w-100 h-auto z-1 object-fit-contain">
                        @elseif($selectedMerek && file_exists(public_path('images/logo-' . $selectedMerek->slug . '.png')))
                           <img src="{{ asset('images/logo-' . $selectedMerek->slug . '.png') }}" alt="{{ $selectedMerek->nama_merek }}" class="w-100 h-auto z-1 object-fit-contain">
                        @elseif(file_exists(public_path('images/icon-products.png')))
                           <img src="{{ asset('images/icon-products.png') }}" alt="INDRACO Products" class="w-100 h-auto z-1 object-fit-contain">
                        @else
                           <img src="{{ asset('images/icon-product.png') }}" alt="INDRACO Products" class="w-100 h-auto z-1 object-fit-contain">
                        @endif
                        <div class="banner-logo-shadow z-0"></div>
                     </div>
                  </div>
                  <div class="pedestal position-absolute start-50 translate-middle z-0">
                     <div class="pedestal-wrapper w-100">
                        <div class="pedestal-top"></div>
                        <div class="pedestal-body"></div>
                     </div>
                  </div>
                  <div class="ms-lg-auto p-2">
                     @include('components.sosmed')
                  </div>
               </div>
            </div>
            <div class="col col-12 col-lg-6 order-lg-1 z-1 col-xl-7">
               <h2 class="fs-1 fw-bold text-custom-2">{{ strtoupper($selectedMerek->nama_merek ?? (request('search') ? 'PENCARIAN: "' . strtoupper(request('search')) . '"' : 'INDRACO PRODUCTS')) }}</h2>
               <p class="fs-4">{{ $selectedMerek->deskripsi ?? "With coffee beans sourced from east to west Indonesia, " . ($selectedMerek->nama_merek ?? 'Indraco') . "'s collection delivers premium, luxurious, and unique flavor profiles. We wholeheartedly focus on serving high-quality products to consumers worldwide." }}</p>
            </div>
         </div>
      </div>
   </section>

   <!-- Product Tab & Catalog Grid Section -->
   <section aria-label="products tab section" class="container mb-5">
      <div class="mb-5">
         <header class="p-1 overflow-hidden mb-4" style="border-radius: calc(48px / 2); background-color: rgba(var(--default-color-rgb), .325);">
            <div class="table-responsive">
               <ul class="nav nav-pills nav-justified flex-nowrap" id="tabBrands" role="tablist">
                  <li class="nav-item" role="presentation">
                     <a href="{{ route('products.index') }}?brand={{ request('brand') }}&category={{ request('category') }}" class="nav-link rounded-pill {{ !request('collection') ? 'active' : '' }}">ALL VARIANT</a>
                  </li>
                  @foreach($collections as $col)
                     <li class="nav-item" role="presentation">
                        <a href="{{ route('products.index') }}?brand={{ request('brand') }}&collection={{ $col->slug }}" class="nav-link rounded-pill {{ request('collection') == $col->slug ? 'active' : '' }}">{{ strtoupper($col->collection_name) }}</a>
                     </li>
                  @endforeach
               </ul>
            </div>
         </header>

         <!-- Product List Grid -->
         <ul class="product-list list-unstyled row row-cols-2 row-cols-md-3 row-cols-xl-4 gy-4 gx-3 g-lg-4 py-4">
            @forelse($produkList as $produk)
               <li class="product-item col">
                  @include('components.card-product', ['produk' => $produk])
               </li>
            @empty
               <li class="col-12 text-center py-5">
                  <div class="p-5 bg-body-secondary rounded-4">
                     <h4 class="fw-bold text-muted mb-2">Produk Tidak Ditemukan</h4>
                     <p class="text-muted mb-3">Belum ada produk untuk kriteria filter ini.</p>
                     <a href="{{ route('products.index') }}" class="btn btn-custom-1 rounded-pill px-4">Lihat Semua Merek</a>
                  </div>
               </li>
            @endforelse
         </ul>

         <!-- Pagination -->
         <div class="mt-4">
            {{ $produkList->links('vendor.pagination.custom') }}
         </div>
      </div>
   </section>

</main>
@endsection
