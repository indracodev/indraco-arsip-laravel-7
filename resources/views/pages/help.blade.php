@extends('layouts.app')

@section('title', 'INDRACO – ' . ($title ?? 'Help Center & FAQ'))

@section('content')
<main id="content" tabindex="-1">

   <!-- Section Banner -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title text-uppercase">HELP CENTER</h2>
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Frequently Asked Questions &amp; Support Guide.</h3>
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/icon-download.png') }}" alt="" class="banner-images">
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
                  <x-sosmed />
               </div>
            </div>
         </div>
      </div>
   </section>

   <!-- Content Section -->
   <section aria-label="Help & FAQ Content" class="container mb-5">
      <div class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm mb-4">
         <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
               <span class="badge bg-custom-1 text-white px-3 py-2 rounded-pill mb-2">INDRACO Support &amp; FAQ</span>
               <h1 class="h2 fw-bold text-custom-1 mb-0">{{ $title ?? 'Help Center & FAQ' }}</h1>
            </div>
            <a href="{{ route('contact') }}" class="btn btn-custom-1 rounded-pill px-4">💬 Hubungi Kami</a>
         </div>

         <div class="lh-lg text-body fs-6" style="font-size: 1.05rem;">
            {!! nl2br(e($content)) !!}
         </div>
      </div>
   </section>

</main>
@endsection
