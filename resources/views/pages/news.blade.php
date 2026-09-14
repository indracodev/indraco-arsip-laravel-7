@extends('layouts.app')

@section('title', 'INDRACO – News & Events')

@push('styles')
   <link rel="stylesheet" href="{{ asset('css/home-banner.css') }}">
   <link rel="stylesheet" href="{{ asset('css/home-news.css') }}">
@endpush

@section('content')
<main id="content" tabindex="-1">

   <!-- Banner Section -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title">NEWS</h2>
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Stay Updated with Our Latest Stories</h3>
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/icon-news.png') }}" alt="News Banner Icon" class="banner-images">
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

   <!-- News Head Section (Featured Article) -->
   @if(isset($featuredNews) && $featuredNews)
   <section aria-label="news head section" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4">
         <div class="text-custom-2 fw-semibold text-uppercase mb-2 small" style="letter-spacing: 0.03em;">
            {{ $featuredNews->judul_eng ? Str::upper(Str::words($featuredNews->judul_eng, 2, '')) : 'FEATURED STORY' }}
         </div>
         <div class="row g-3 row-cols-1 row-cols-md-2 g-md-4 gx-xl-5">
            <div class="col">
               <a href="{{ route('news.detail', $featuredNews->slug) }}" class="d-block ratio ratio-16x9 bg-body rounded-4 overflow-hidden shadow">
                  <img src="{{ $featuredNews->image_url }}" alt="{{ $featuredNews->localized_judul }}" class="object-fit-cover top-50 start-50 translate-middle w-100 h-100">
               </a>
            </div>
            <div class="col d-flex flex-column">
               <h3 class="fw-semibold fs-1">
                  <a href="{{ route('news.detail', $featuredNews->slug) }}" class="text-dark text-decoration-none hover-primary">
                     {{ $featuredNews->localized_judul }}
                  </a>
               </h3>
               <p class="flex-grow-1 text-secondary">
                  {{ Str::limit(strip_tags($featuredNews->localized_content), 240) }}
               </p>
               <small class="text-secondary fw-medium">{{ $featuredNews->formatted_tanggal }}</small>
            </div>
         </div>
      </div>
   </section>
   @endif

   <!-- Dynamic News Slider Track Section -->
   <section aria-label="section news" class="container news-slider-section mb-5">
      <header class="d-flex justify-content-between align-items-center mb-3">
         <h2 class="fs-3 fw-semibold m-0">NEWS</h2>
         <div class="d-flex align-items-center gap-3">
            <div class="news-slider-controls d-flex gap-2">
               <button class="btn news-control-btn news-prev-btn btn-outline-invert rounded-circle d-flex align-items-center justify-content-center" aria-label="Previous slide">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="16" height="16"><path fill="currentColor" d="M380.9 489.4L211.5 320L380.9 150.6C393.4 138.1 393.4 117.8 380.9 105.3C368.4 92.8 348.1 92.8 335.6 105.3L144.3 296.6C131.8 309.1 131.8 329.4 144.3 341.9L335.6 533.2C348.1 545.7 368.4 545.7 380.9 533.2C393.4 520.7 393.4 500.4 380.9 489.4z" /></svg>
               </button>
               <button class="btn news-control-btn news-next-btn btn-outline-invert rounded-circle d-flex align-items-center justify-content-center" aria-label="Next slide">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="16" height="16"><path fill="currentColor" d="M228.9 150.6L398.3 320L228.9 489.4C216.4 501.9 216.4 522.2 228.9 534.7C241.4 547.2 261.7 547.2 274.2 534.7L465.5 343.4C478 330.9 478 310.6 465.5 298.1L274.2 106.8C261.7 94.3 241.4 94.3 228.9 106.8C216.4 119.3 216.4 139.6 228.9 150.6z" /></svg>
               </button>
            </div>
         </div>
      </header>

      <div class="news-slider-container">
         <ul class="list-unstyled news-slider-track">
            @forelse($newsList as $news)
               <li class="news-slide-item">
                  <a href="{{ route('news.detail', $news->slug) }}" class="text-decoration-none d-block">
                     <div class="card news-card bg-body-secondary rounded-4 overflow-hidden shadow">
                        <div class="card-header p-0 news-card-img-wrapper ratio ratio-16x9 overflow-hidden">
                           <img src="{{ $news->image_url }}" class="card-img-top object-fit-cover w-100 h-100" alt="{{ $news->localized_judul }}" loading="lazy">
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                           <div class="text-custom-2 fw-semibold text-uppercase mb-2 small" style="letter-spacing: 0.03em;">
                              {{ $news->judul_eng ? Str::upper(Str::words($news->judul_eng, 2, '')) : 'NEWS & MEDIA' }}
                           </div>
                           <h3 class="card-title news-card-title h5 fw-semibold mb-3">{{ $news->localized_judul }}</h3>
                           <div class="text-secondary small mt-auto fw-medium">{{ $news->formatted_tanggal }}</div>
                        </div>
                     </div>
                  </a>
               </li>
            @empty
               <li class="w-100 text-center py-4 text-muted">Belum ada berita.</li>
            @endforelse
         </ul>
      </div>

      <!-- Slider Dot Indicators -->
      <div class="news-slider-indicators d-flex justify-content-center align-items-center gap-2 mt-4"></div>
   </section>

</main>
@endsection

@push('scripts')
   <script src="{{ asset('js/home-news.js') }}"></script>
@endpush
