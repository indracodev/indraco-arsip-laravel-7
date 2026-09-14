@extends('layouts.app')

@section('title', 'INDRACO – Products')

@push('styles')
   <link rel="stylesheet" href="{{ asset('css/home-banner.css') }}">
@endpush

@section('content')
<main id="content" tabindex="-1">

   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title">PRODUCTS</h2>
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Premium Quality, Competitive Prices, Exceeding Customer Expectations.</h3>
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/icon-products.png') }}" alt="Products Banner Icon" class="banner-images">
                  </div>
                  <div class="pedestal z-0">
                     <div class="pedestal-wrapper">
                        <div class="pedestal-top"></div>
                        <div class="pedestal-body"></div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col col-12 z-3">
               <div class="banner-sosmed d-flex justify-content-center justify-content-lg-end">
                  @include('components.sosmed')
               </div>
            </div>
         </div>
      </div>
   </section>

   <section aria-label="products tab section" class="container mb-5">
      <div class="mb-5">
         <header class="p-1 overflow-hidden" style="border-radius: calc(48px / 2); background-color: rgba(var(--default-color-rgb), .325);">
            <div class="table-responsive">
               <ul class="nav nav-pills nav-justified flex-nowrap" id="tabBrands" role="tablist">
                  <li class="nav-item" role="presentation">
                     <button class="nav-link rounded-pill active" id="pills-all-tab" data-bs-toggle="pill" data-bs-target="#pills-all" type="button" role="tab" aria-controls="pills-all" aria-selected="true">ALL CATEGORY</button>
                  </li>
                  @foreach($kategories as $kat)
                     <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill" id="pills-{{ $kat->slug }}-tab" data-bs-toggle="pill" data-bs-target="#pills-{{ $kat->slug }}" type="button" role="tab" aria-controls="pills-{{ $kat->slug }}" aria-selected="false">{{ strtoupper($kat->nama_kategori) }}</button>
                     </li>
                  @endforeach
               </ul>
            </div>
         </header>
         <div class="tab-content mt-3" id="tabBrandsContent">
            <!-- ALL CATEGORY TAB -->
            <div class="tab-pane fade show active" id="pills-all" role="tabpanel" aria-labelledby="pills-all-tab" tabindex="0">
               <ul class="brands-logo-list list-unstyled row row-cols-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-5 py-4">
                  @foreach($mereks as $merek)
                     <li class="col">
                        <a href="{{ route('products.index') }}?brand={{ $merek->slug }}" class="link d-block ratio ratio-1x1 brands-logo mx-auto">
                           @if($merek->logo_path)
                              <img src="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" loading="lazy" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                           @else
                              <img src="{{ asset('images/logo-' . $merek->slug . '.png') }}" alt="{{ $merek->nama_merek }}" loading="lazy" class="theme-image object-fit-contain top-50 start-50 translate-middle" onerror="this.src='{{ asset('images/logo-indraco-est.png') }}'">
                           @endif
                        </a>
                     </li>
                  @endforeach
               </ul>
            </div>
            <!-- PER CATEGORY TABS -->
            @foreach($kategories as $kat)
               <div class="tab-pane fade" id="pills-{{ $kat->slug }}" role="tabpanel" aria-labelledby="pills-{{ $kat->slug }}-tab" tabindex="0">
                  <ul class="brands-logo-list list-unstyled row row-cols-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-5 py-4">
                     @php
                        $categoryMereks = $mereks->filter(function($merek) use ($kat) {
                            return \App\Models\MasterProduk::where('merek_id', $merek->id)->where('kategori_id', $kat->id)->where('status', 'active')->exists();
                        });
                        if($categoryMereks->isEmpty()) {
                            $categoryMereks = $mereks;
                        }
                     @endphp
                     @foreach($categoryMereks as $merek)
                        <li class="col">
                           <a href="{{ route('products.index') }}?brand={{ $merek->slug }}&category={{ $kat->slug }}" class="link d-block ratio ratio-1x1 brands-logo mx-auto">
                              @if($merek->logo_path)
                                 <img src="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" loading="lazy" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                              @else
                                 <img src="{{ asset('images/logo-' . $merek->slug . '.png') }}" alt="{{ $merek->nama_merek }}" loading="lazy" class="theme-image object-fit-contain top-50 start-50 translate-middle" onerror="this.src='{{ asset('images/logo-indraco-est.png') }}'">
                              @endif
                           </a>
                        </li>
                     @endforeach
                  </ul>
               </div>
            @endforeach
         </div>
      </div>
   </section>

</main>
@endsection
