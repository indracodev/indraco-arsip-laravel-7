@extends('layouts.app')

@section('title', 'INDRACO – Downloads')

@section('content')
<main id="content" tabindex="-1">

   <!-- Section Banner -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title">DOWNLOADS</h2><!-- end banner title -->
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Access Our Latest Catalogs &amp; Resources.</h3><!-- end banner text -->
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/icon-download.png') }}" alt="" class="banner-images"><!-- end banner images -->
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

   <!-- Section Catalog & Brochure -->
   <section aria-label="brochures section" class="container">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden">
         <h2 class="mb-4 fs-3 fw-semibold">CATALOG &amp; BROCHURE</h2>
         <ul class="list-unstyled row gy-5 brochure-list row-cols-1 row-cols-sm-2 row-cols-lg-3 g-xxl-5 mb-0">
            @forelse($downloads as $item)
               <li class="brochure-item">
                  <a href="{{ $item->download_url }}" {{ $item->file_path ? 'download' : 'target=_blank' }} class="text-decoration-none d-block w-100 position-relative">
                     <figure class="figure w-100 mb-0">
                        <div class="figure-img ratio ratio-1x1 bg-body rounded-4 overflow-hidden shadow mb-3 position-relative">
                           <img src="{{ $item->image_url }}" alt="{{ $item->judul }}" class="object-fit-contain h-75 top-50 start-50 translate-middle">
                           @if($item->file_size)
                              <span class="badge bg-custom-1 text-white position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.72rem;">
                                 📥 {{ $item->file_size }}
                              </span>
                           @endif
                        </div>
                        <figcaption class="figure-caption text-center fs-6 text-body fw-medium mb-1">{{ $item->judul }}</figcaption>
                        @if($item->judul_eng)
                           <p class="text-secondary small text-center mb-0" style="font-size: 0.78rem;">{{ $item->judul_eng }}</p>
                        @endif
                     </figure>
                  </a>
               </li>
            @empty
               <li class="col-12 text-center py-4 text-muted">Belum ada berkas katalog atau brosur yang diunggah.</li>
            @endforelse
         </ul>
      </div>
   </section><!-- end brochures section -->

</main>
@endsection
