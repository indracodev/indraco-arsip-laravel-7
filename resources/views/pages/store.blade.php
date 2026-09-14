@extends('layouts.app')

@section('title', 'INDRACO – Online Store')

@section('content')
<main id="content" tabindex="-1">

   <!-- Section Banner -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title">ONLINE STORE</h2><!-- end banner title -->
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Discover Our Official Stores &amp; Trusted Marketplaces.</h3><!-- end banner text -->
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/icon-store.png') }}" alt="" class="banner-images"><!-- end banner images -->
                  </div><!-- end banner images wrapper -->
                  <div class="pedestal z-0">
                     <div class="pedestal-wrapper">
                        <div class="pedestal-top"></div><!-- pedestal top -->
                        <div class="pedestal-body"></div><!-- pedestal body -->
                     </div><!-- end pedestal wrapper -->
                  </div><!-- end pedestal -->
               </div><!-- end banner media -->
            </div>
            <div class="col col-12 z-3">
               <div class="banner-sosmed d-flex justify-content-center justify-content-lg-end">
                  <x-sosmed />
               </div><!-- end banner sosmed -->
            </div>
         </div><!-- end banner wrapper -->
      </div><!-- end banner -->
   </section><!-- end section banner -->

   <!-- Section E-Commerce -->
   <section aria-label="e-commerce section" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4">
         <h2 class="fs-3 fw-semibold mb-4">E-COMMERCE</h2>
         <ul class="list-unstyled marketplace-list row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 g-xl-4">
            <li>
               <div class="card rounded-4 overflow-hidden shadow w-100">
                  <div class="ratio ratio-16x9 w-100">
                     <img src="{{ asset('images/logo-supresso-typograph-flat.png') }}" alt="" class="object-fit-contain w-75 h-50 top-50 start-50 translate-middle">
                  </div>
                  <a href="#" class="stretched-link"></a>
               </div><!-- end marketplace card -->
            </li><!-- end marketplace item -->
            <li>
               <div class="card rounded-4 overflow-hidden shadow w-100">
                  <div class="ratio ratio-16x9 w-100">
                     <img src="{{ asset('images/logo-indracostore-flat.png') }}" alt="" class="object-fit-contain w-75 h-50 top-50 start-50 translate-middle">
                  </div>
                  <a href="#" class="stretched-link"></a>
               </div><!-- end marketplace card -->
            </li><!-- end marketplace item -->
         </ul><!-- end marketplace list -->
      </div>
   </section><!-- end e-commerce section -->

   <!-- Section Marketplace -->
   <section aria-label="marketplace section" class="container">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4">
         <h2 class="fs-3 fw-semibold mb-4">MARKETPLACE</h2>
         <ul class="list-unstyled marketplace-list row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 g-xl-4">
            <li>
               <div class="card rounded-4 overflow-hidden shadow w-100">
                  <div class="ratio ratio-16x9 w-100">
                     <img src="{{ asset('images/logo-shopee-flat.png') }}" alt="" class="object-fit-contain w-75 h-50 top-50 start-50 translate-middle">
                  </div>
                  <a href="#" class="stretched-link"></a>
               </div><!-- end marketplace card -->
            </li><!-- end marketplace item -->
            <li>
               <div class="card rounded-4 overflow-hidden shadow w-100">
                  <div class="ratio ratio-16x9 w-100">
                     <img src="{{ asset('images/logo-lazada-flat.png') }}" alt="" class="object-fit-contain w-75 h-50 top-50 start-50 translate-middle">
                  </div>
                  <a href="#" class="stretched-link"></a>
               </div><!-- end marketplace card -->
            </li><!-- end marketplace item -->
            <li>
               <div class="card rounded-4 overflow-hidden shadow w-100">
                  <div class="ratio ratio-16x9 w-100">
                     <img src="{{ asset('images/logo-blibli-flat.png') }}" alt="" class="object-fit-contain w-75 h-50 top-50 start-50 translate-middle">
                  </div>
                  <a href="#" class="stretched-link"></a>
               </div><!-- end marketplace card -->
            </li><!-- end marketplace item -->
            <li>
               <div class="card rounded-4 overflow-hidden shadow w-100">
                  <div class="ratio ratio-16x9 w-100">
                     <img src="{{ asset('images/logo-tokopedia-flat.png') }}" alt="" class="object-fit-contain w-75 h-50 top-50 start-50 translate-middle">
                  </div>
                  <a href="#" class="stretched-link"></a>
               </div><!-- end marketplace card -->
            </li><!-- end marketplace item -->
            <li>
               <div class="card rounded-4 overflow-hidden shadow w-100">
                  <div class="ratio ratio-16x9 w-100">
                     <img src="{{ asset('images/logo-tiktok-shop-flat.png') }}" alt="" class="object-fit-contain w-75 h-50 top-50 start-50 translate-middle">
                  </div>
                  <a href="#" class="stretched-link"></a>
               </div><!-- end marketplace card -->
            </li><!-- end marketplace item -->
         </ul><!-- end marketplace list -->
      </div>
   </section><!-- end marketplace section -->

</main>
@endsection
