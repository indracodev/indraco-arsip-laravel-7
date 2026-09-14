@extends('layouts.app')

@section('title', 'INDRACO – CSR')

@push('styles')
   <link rel="stylesheet" href="{{ asset('css/home-banner.css') }}">
   <link rel="stylesheet" href="{{ asset('css/home-news.css') }}">
   <style>
      .banner-media { aspect-ratio: 4/3 !important; }
      .banner-images { top: 50% !important; }
      .text-teal { color: var(--custom-primary, #004b49) !important; }
   </style>
@endpush

@section('content')
<main id="content" tabindex="-1">
   
   <!-- Banner Section -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <img src="{{ asset('images/logo-Indraco-life.png') }}" data-light="{{ asset('images/logo-Indraco-life.png') }}" data-dark="{{ asset('images/logo-Indraco-life-invert.png') }}" alt="INDRACO Life" class="theme-image img-fluid">
               <h2 class="visually-hidden">INDRACO Life</h2>
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Building a better future through responsibility and sustainability.</h3>
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/csr-header.png') }}" alt="CSR Header" class="banner-images">
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

   <!-- Our Commitment Section -->
   <section class="container mb-5" aria-labelledby="csr-commitment">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden">
         <div class="row row-cols-1 row-cols-lg-2 gy-4 g-lg-5 align-items-center justify-content-lg-between">
            <div class="col order-lg-2">
               <div class="ratio ratio-16x9 rounded-4 overflow-hidden">
                  <img src="{{ asset('images/home-about.jpg') }}" alt="Our Commitment" class="object-fit-cover"> 
               </div>
            </div>
            <div class="col order-lg-1 col-xl-5">
               <span class="fs-2 fw-semibold text-uppercase d-block mb-1 text-teal">OUR COMMITMENT</span>
               <h2 id="csr-commitment" class="fw-semibold text-body mb-4">Growing Together, Responsibly.</h2>
               <p class="mb-0 text-muted">For more than five decades, sustainability has been an integral part of how we operate. We continuously seek meaningful ways to support people, protect the environment, and create long-term value for future generations.</p>
            </div>
         </div>
      </div>
   </section>

   <!-- CSR Pillars Section -->
   <section class="container mb-5" aria-labelledby="csr-pillars">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden">
         <header class="mb-5">
            <span class="fs-2 fw-semibold text-uppercase d-block mb-1 text-teal">CSR PILLARS</span>
            <h2 id="csr-pillars" class="fw-semibold text-body">Three Pillars of Sustainability</h2>
         </header>
         <ul class="list-unstyled mb-0 row">
            <li class="col-12 col-lg">
               <div class="card border-0 bg-transparent">
                  <div class="row g-0 align-items-center">
                     <div class="col-7 mx-auto col-xl-5">
                        <div class="card-img">
                           <img src="{{ asset('images/csr-environment.png') }}" alt="Environment" aria-hidden="true" class="img-fluid">
                        </div>
                     </div>
                     <div class="col-12 col-xl-7">
                        <div class="card-body pe-0">
                           <h3 class="fs-5 fw-semibold text-teal">Environment</h3>
                           <p class="mb-0 text-muted">Committed to using resources responsibly while supporting a more sustainable future.</p>
                        </div>
                     </div>
                  </div>
               </div>
            </li>
            <li class="col-12 col-lg-auto" aria-hidden="true">
               <hr class="d-lg-none">
               <div class="vr h-100 d-none d-lg-block opacity-25"></div>
            </li>
            <li class="col-12 col-lg">
               <div class="card border-0 bg-transparent">
                  <div class="row g-0 align-items-center">
                     <div class="col-7 mx-auto col-xl-5">
                        <div class="card-img">
                           <img src="{{ asset('images/csr-people.png') }}" alt="People" aria-hidden="true" class="img-fluid">
                        </div>
                     </div>
                     <div class="col-12 col-xl-7">
                        <div class="card-body pe-0">
                           <h3 class="fs-5 fw-semibold text-teal">People</h3>
                           <p class="mb-0 text-muted">Creating opportunities for employees, farmers, and surrounding communities.</p>
                        </div>
                     </div>
                  </div>
               </div>
            </li>
            <li class="col-12 col-lg-auto" aria-hidden="true">
               <hr class="d-lg-none">
               <div class="vr h-100 d-none d-lg-block opacity-25"></div>
            </li>
            <li class="col-12 col-lg">
               <div class="card border-0 bg-transparent">
                  <div class="row g-0 align-items-center">
                     <div class="col-7 mx-auto col-xl-5">
                        <div class="card-img">
                           <img src="{{ asset('images/csr-governance.png') }}" alt="Governance" aria-hidden="true" class="img-fluid">
                        </div>
                     </div>
                     <div class="col-12 col-xl-7">
                        <div class="card-body pe-0">
                           <h3 class="fs-5 fw-semibold text-teal">Governance</h3>
                           <p class="mb-0 text-muted">Building trust through ethical leadership and transparent operations.</p>
                        </div>
                     </div>
                  </div>
               </div>
            </li>
         </ul>
      </div>
   </section>

   <!-- Featured Programs Section -->
   <section class="container mb-5" aria-labelledby="csr-programs">
      <header class="mb-5">
         <span class="fs-2 fw-semibold text-uppercase d-block mb-1 text-teal">FEATURED PROGRAMS</span>
         <h2 id="csr-programs" class="fw-semibold text-body">Making A Difference Every Day</h2>
      </header>
      <ul class="list-unstyled mb-0 row g-3 g-xl-4 text-center">
         <li class="col-12 col-md-6 col-xl-3">
            <div class="card bg-body-secondary rounded-4 overflow-hidden border-0 p-3 h-100">
               <div class="card-header border-0 bg-transparent">
                  <div class="card-img ratio ratio-1x1 w-75 mx-auto">
                     <img src="{{ asset('images/csr-growth.png') }}" alt="Growth Support" aria-hidden="true" class="object-fit-contain">
                  </div>
               </div>
               <div class="card-body">
                  <h3 class="card-title fs-5 fw-semibold mb-3 text-teal">Growth <br> Support</h3>
                  <p class="card-text text-muted">Supporting growth through CSR programs and live activities.</p>
               </div>
            </div>
         </li>
         <li class="col-12 col-md-6 col-xl-3">
            <div class="card bg-body-secondary rounded-4 overflow-hidden border-0 p-3 h-100">
               <div class="card-header border-0 bg-transparent">
                  <div class="card-img ratio ratio-1x1 w-75 mx-auto">
                     <img src="{{ asset('images/csr-community.png') }}" alt="Community Empowerment" aria-hidden="true" class="object-fit-contain">
                  </div>
               </div>
               <div class="card-body">
                  <h3 class="card-title fs-5 fw-semibold mb-3 text-teal">Community <br> Empowerment</h3>
                  <p class="card-text text-muted">Empowering local communities and preserving cultural heritage through community events.</p>
               </div>
            </div>
         </li>
         <li class="col-12 col-md-6 col-xl-3">
            <div class="card bg-body-secondary rounded-4 overflow-hidden border-0 p-3 h-100">
               <div class="card-header border-0 bg-transparent">
                  <div class="card-img ratio ratio-1x1 w-75 mx-auto">
                     <img src="{{ asset('images/csr-good-people.png') }}" alt="Be a Good Person" aria-hidden="true" class="object-fit-contain">
                  </div>
               </div>
               <div class="card-body">
                  <h3 class="card-title fs-5 fw-semibold mb-3 text-teal">Be a <br> Good Person</h3>
                  <p class="card-text text-muted">Sharing kindness through our Jumat Berkah program, where communities prepare and distribute meals together.</p>
               </div>
            </div>
         </li>
         <li class="col-12 col-md-6 col-xl-3">
            <div class="card bg-body-secondary rounded-4 overflow-hidden border-0 p-3 h-100">
               <div class="card-header border-0 bg-transparent">
                  <div class="card-img ratio ratio-1x1 w-75 mx-auto">
                     <img src="{{ asset('images/csr-voluntering.png') }}" alt="Employee Well-being" aria-hidden="true" class="object-fit-contain">
                  </div>
               </div>
               <div class="card-body">
                  <h3 class="card-title fs-5 fw-semibold mb-3 text-teal">Employee <br> Well-being</h3>
                  <p class="card-text text-muted">Promoting a healthy workplace through fitness activities and blood donation programs.</p>
               </div>
            </div>
         </li>
      </ul>
   </section>

   <!-- Moments News Slider Section -->
   <section aria-labelledby="section-news" class="container news-slider-section">
      <header class="d-flex justify-content-between align-items-center mb-3">
         <h2 id="section-news" class="fs-3 fw-semibold m-0 text-teal">MOMENTS</h2>
         <div class="d-flex align-items-center gap-3">
            <a href="{{ route('news') }}" class="link d-flex align-items-center gap-1 fw-semibold text-secondary text-decoration-none">
               <span>View all news</span>
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="12" height="12"><path fill="currentColor" d="M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z" /></svg>
            </a>
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
            @forelse($latestNews as $newsItem)
               <li class="news-slide-item">
                  <a href="{{ route('news.detail', $newsItem->slug) }}" class="text-decoration-none d-block">
                     <div class="card news-card bg-body-secondary rounded-4 overflow-hidden shadow">
                        <div class="card-header p-0 news-card-img-wrapper ratio ratio-16x9 overflow-hidden">
                           <img src="{{ $newsItem->image_path ? asset($newsItem->image_path) : asset('images/news/news-1.jpg') }}" class="card-img-top object-fit-cover w-100 h-100" alt="{{ $newsItem->judul }}" loading="lazy">
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                           <div class="text-custom-2 fw-semibold text-uppercase mb-2 small" style="letter-spacing: 0.03em;">{{ $newsItem->kategori ?? 'CSR Initiative' }}</div>
                           <h3 class="card-title news-card-title h5 fw-semibold mb-3 text-teal">{{ $newsItem->judul }}</h3>
                           <div class="text-secondary small mt-auto fw-medium">{{ \Carbon\Carbon::parse($newsItem->created_at)->format('M d, Y') }}</div>
                        </div>
                     </div>
                  </a>
               </li>
            @empty
               <!-- Card 1 Fallback -->
               <li class="news-slide-item">
                  <a href="#" class="text-decoration-none d-block">
                     <div class="card news-card bg-body-secondary rounded-4 overflow-hidden shadow">
                        <div class="card-header p-0 news-card-img-wrapper ratio ratio-16x9 overflow-hidden">
                           <img src="{{ asset('images/news/news-1.jpg') }}" class="card-img-top object-fit-cover w-100 h-100" alt="Event Heritage Asli Kabupaten Pasuruan" loading="lazy">
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                           <div class="text-custom-2 fw-semibold text-uppercase mb-2 small" style="letter-spacing: 0.03em;">Marketing Activation</div>
                           <h3 class="card-title news-card-title h5 fw-semibold mb-3 text-teal">Event Heritage Asli Kabupaten Pasuruan</h3>
                           <div class="text-secondary small mt-auto fw-medium">May 20, 2026</div>
                        </div>
                     </div>
                  </a>
               </li>
            @endforelse
         </ul>
      </div>

      <div class="news-slider-indicators d-flex justify-content-center align-items-center gap-2 mt-4"></div>
   </section>
</main>
@endsection

@push('scripts')
   <script src="{{ asset('js/home-news.js') }}"></script>
@endpush
