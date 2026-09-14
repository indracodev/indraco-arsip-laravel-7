@extends('layouts.app')

@section('title', 'INDRACO – A Leading FMCG Company in Indonesia Since 1971')

@push('styles')
   <link rel="stylesheet" href="{{ asset('css/home-banner.css') }}">
   <link rel="stylesheet" href="{{ asset('css/home-news.css') }}">
   <link rel="stylesheet" href="{{ asset('css/home-social.css') }}">
   <link rel="stylesheet" href="{{ asset('css/home-promo-banner.css') }}">
   <style>
      .award-icon {max-width: 3rem;}
      .award-title {margin-bottom: 0; font-size: 1rem; line-height: 1;}
      .award-value {font-size: 3rem;}
      .award-char {font-size: 2rem;}
      @media (min-width: 1400px) {
         .award-icon {max-width: 3.5rem;}
         .award-title {font-size: 1.125rem;}
         .award-value {font-size: 3.5rem;}
      }
   </style>
@endpush

@section('content')
   {{-- 3D Product Categories Hero Carousel (Integrasi dengan Admin Banner) --}}
   <section aria-label="product categories slider" class="product-slider-section mb-5">
      <div class="slider-container">
         <div class="slider-wrapper">

            @if(isset($banners) && $banners->count())
               @foreach($banners as $index => $banner)
                  @php
                     $titleText = $banner->title_id ?? $banner->title_en ?? 'INDRACO';
                     $descText  = $banner->subtitle_id ?? $banner->subtitle_en ?? '';
                     $linkUrl   = $banner->link ? (str_starts_with($banner->link, 'http') ? $banner->link : url($banner->link)) : route('products.index');
                  @endphp
                  <div class="slide" data-index="{{ $index }}" data-title="{{ $titleText }}" data-desc="{{ $descText }}">
                     <div class="bg-title">{{ strtoupper($titleText) }}</div>
                     <div class="pedestal-container">
                        <a href="{{ $linkUrl }}" class="product-image-wrapper">
                           <div class="product-shadow"></div>
                           <img src="{{ asset($banner->image_path) }}" alt="{{ $titleText }}" class="product-image">
                        </a>
                        <div class="cylinder">
                           <div class="cylinder-top"></div>
                           <div class="cylinder-body"></div>
                        </div>
                     </div>
                  </div>
               @endforeach
            @else
               <!-- Fallback Slide 1: Coffee -->
               <div class="slide" data-index="0" data-title="COFFEE" data-desc="Enjoy a selection of quality coffee with a distinctive aroma.">
                  <div class="bg-title">COFFEE</div>
                  <div class="pedestal-container">
                     <a href="{{ route('products.index') }}" class="product-image-wrapper">
                        <div class="product-shadow"></div>
                        <img src="{{ asset('images/coffee-bean.png') }}" alt="Coffee Bean" class="product-image">
                     </a>
                     <div class="cylinder">
                        <div class="cylinder-top"></div>
                        <div class="cylinder-body"></div>
                     </div>
                  </div>
               </div>

               <!-- Fallback Slide 2: Ginger -->
               <div class="slide" data-index="1" data-title="GINGER" data-desc="Experience the warm, comforting, and soothing properties of our selected ginger.">
                  <div class="bg-title">GINGER</div>
                  <div class="pedestal-container">
                     <a href="{{ route('products.index') }}" class="product-image-wrapper">
                        <div class="product-shadow"></div>
                        <img src="{{ asset('images/ginger.png') }}" alt="Ginger" class="product-image">
                     </a>
                     <div class="cylinder">
                        <div class="cylinder-top"></div>
                        <div class="cylinder-body"></div>
                     </div>
                  </div>
               </div>

               <!-- Fallback Slide 3: Chocolate -->
               <div class="slide" data-index="2" data-title="CHOCOLATE" data-desc="Indulge in the rich, deep, and smooth flavors of premium quality chocolate drink.">
                  <div class="bg-title">CHOCOLATE</div>
                  <div class="pedestal-container">
                     <a href="{{ route('products.index') }}" class="product-image-wrapper">
                        <div class="product-shadow"></div>
                        <img src="{{ asset('images/chocolate.png') }}" alt="Chocolate" class="product-image">
                     </a>
                     <div class="cylinder">
                        <div class="cylinder-top"></div>
                        <div class="cylinder-body"></div>
                     </div>
                  </div>
               </div>

               <!-- Fallback Slide 4: Coconut Milk -->
               <div class="slide" data-index="3" data-title="COCONUT" data-desc="Delight in the fresh, creamy, and tropical taste of our premium coconut milk.">
                  <div class="bg-title">COCONUT</div>
                  <div class="pedestal-container">
                     <a href="{{ route('products.index') }}" class="product-image-wrapper">
                        <div class="product-shadow"></div>
                        <img src="{{ asset('images/coconut.png') }}" alt="Coconut" class="product-image">
                     </a>
                     <div class="cylinder">
                        <div class="cylinder-top"></div>
                        <div class="cylinder-body"></div>
                     </div>
                  </div>
               </div>
            @endif

         </div>
      </div>

      <!-- Footer Controls Bar -->
      <div class="slider-footer container">
         <div class="row align-items-center justify-content-between gy-4">

            <!-- Left: Description -->
            <div class="col-12 col-lg-4 order-2 order-lg-1 text-center text-lg-start">
               <p class="slider-description mb-0 fs-4"></p>
            </div>

            <!-- Center: Dot Indicators -->
            <div class="col-12 col-lg-4 order-1 order-lg-2 d-flex justify-content-center">
               <div class="slider-indicators">
                  @if(isset($banners) && $banners->count())
                     @foreach($banners as $index => $banner)
                        <button class="indicator {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}" aria-label="Go to slide {{ $index + 1 }}"></button>
                     @endforeach
                  @else
                     <button class="indicator active" data-slide="0" aria-label="Go to slide 1"></button>
                     <button class="indicator" data-slide="1" aria-label="Go to slide 2"></button>
                     <button class="indicator" data-slide="2" aria-label="Go to slide 3"></button>
                     <button class="indicator" data-slide="3" aria-label="Go to slide 4"></button>
                  @endif
               </div>
            </div>

            <!-- Right: Social Media Links -->
            <div class="col-12 col-lg-4 order-3 order-lg-3 d-flex justify-content-center justify-content-lg-end">
               @include('components.sosmed')
            </div>

         </div>
      </div>
   </section>

   {{-- =========================================
        Promotional Banner Section
        Managed via Admin → /admin/banner
   ========================================== --}}
   @if(isset($banners) && $banners->count())
   <section aria-label="promotional banners" class="promo-banner-section container mb-5">
      <div id="promoCarousel" class="carousel slide promo-carousel" data-bs-ride="carousel" data-bs-interval="5000">

         {{-- Carousel Indicators --}}
         <div class="carousel-indicators">
            @foreach($banners as $index => $banner)
               <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="{{ $index }}"
                  {{ $index === 0 ? 'class="active" aria-current="true"' : '' }}
                  aria-label="Banner {{ $index + 1 }}"></button>
            @endforeach
         </div>

         {{-- Carousel Items --}}
         <div class="carousel-inner">
            @foreach($banners as $index => $banner)
               <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                  <img src="{{ asset($banner->image_path) }}"
                       alt="{{ $banner->title_id ?? $banner->title_en ?? 'INDRACO Banner' }}"
                       loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                  <div class="banner-overlay" aria-hidden="true"></div>

                  {{-- Caption Overlay --}}
                  @if($banner->title_id || $banner->title_en || $banner->subtitle_id)
                  <div class="carousel-caption">
                     @if($banner->title_en)
                        <div class="banner-eyebrow">{{ $banner->title_en }}</div>
                     @endif
                     @if($banner->title_id)
                        <h2 class="banner-title">{{ $banner->title_id }}</h2>
                     @endif
                     @if($banner->subtitle_id)
                        <p class="banner-subtitle">{{ $banner->subtitle_id }}</p>
                     @endif
                     @if($banner->link)
                        <a href="{{ $banner->link }}" class="banner-cta">
                           {{ $banner->button_text_id ?? 'Lihat Selengkapnya' }}
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" width="12" height="12" class="ms-1"><path d="M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"/></svg>
                        </a>
                     @endif
                  </div>
                  @endif
               </div>
            @endforeach
         </div>

         {{-- Carousel Controls --}}
         @if($banners->count() > 1)
         <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev" aria-label="Banner sebelumnya">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
         </button>
         <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next" aria-label="Banner berikutnya">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
         </button>
         @endif

      </div>
   </section>
   @endif

   {{-- About Us Section --}}
   <section aria-label="section about" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-md-5 rounded-4 overflow-hidden">
         <div class="row gx-lg-5">
            <div class="col col-12 col-lg-6">
               <h2 class="fs-3 fw-semibold mb-4">{{ \App\Models\MasterSetting::get('page_home_about_title', 'ABOUT US') }}</h2>
               <div class="ratio ratio-16x9 d-lg-none my-3">
                  <img src="{{ asset(\App\Models\MasterSetting::get('page_home_about_image', 'images/home-about.jpg')) }}" alt="About Indraco" loading="lazy" class="object-fit-cover rounded-4">
               </div>
               <h3 class="mb-4">{{ \App\Models\MasterSetting::get('page_home_about_headline', 'Uniting through flavour, connecting through life.') }}</h3>
               <p>{{ \App\Models\MasterSetting::get('page_home_about_content', 'Kami INDRACO – Dimulai pada tahun 1971 dengan gudang di Sumatera oleh pendiri kami, kami telah terus tumbuh dan berkembang menjadi beberapa fasilitas manufaktur canggih di seluruh Indonesia dan Singapura.') }}</p>
               <a href="{{ route('about') }}" class="btn btn-custom-1 rounded-pill">
                  <span>Learn more</span>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="16" height="16"><path fill="currentColor" d="M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z" /></svg>
               </a>
            </div>
            <div class="col col-12 col-lg-6 d-none d-lg-block">
               <div class="ratio ratio-16x9 rounded-4 overflow-hidden">
                  <img src="{{ asset(\App\Models\MasterSetting::get('page_home_about_image', 'images/home-about.jpg')) }}" alt="About Indraco" loading="lazy" class="object-fit-cover">
               </div>
            </div>
         </div>
      </div>
   </section>

   {{-- Our Brands Section --}}
   <section aria-label="section brands" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-md-5 rounded-4 overflow-hidden">

         <header class="mb-5">
            <h2 class="fs-3 fw-semibold mb-4">OUR BRANDS</h2>
            <div id="brands-award" class="row award-list">

               <div class="award-item col col-12 col-lg">
                  <div class="award-card row row-gap-3 h-100">
                     <div class="col col-12 d-flex gap-3 align-items-center col-md col-lg-12 flex-lg-grow-1">
                        <div class="award-icon ratio ratio-1x1 bg-body-tertiary rounded-circle">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" class="object-fit-contain w-75 top-50 start-50 translate-middle"><path fill="currentColor" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z" /></svg>
                        </div>
                        <h3 class="award-title d-flex gap-2">
                           <span class="award-value fw-semibold text-custom-2">{{ $brandCount ?? \App\Models\MasterMerek::count() }}</span>
                           <span class="award-span d-flex flex-column">
                              <span class="text-custom-2 flex-grow-1 award-char">+</span>
                              <span>BRANDS</span>
                           </span>
                        </h3>
                     </div>
                     <div class="col-12 col-md col-lg-12 flex-lg-grow-0 mt-lg-auto">A diverse portfolio of coffee brands for every taste and occasion.</div>
                  </div>
               </div>

               <div class="col col-12 col-lg-auto divider">
                  <hr class="d-lg-none">
                  <div class="vr h-100 d-none d-lg-block"></div>
               </div>

               <div class="award-item col col-12 col-lg">
                  <div class="award-card row row-gap-3 h-100">
                     <div class="col col-12 d-flex gap-3 align-items-center col-md col-lg-12 flex-lg-grow-1">
                        <div class="award-icon ratio ratio-1x1 bg-body-tertiary rounded-circle">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" class="object-fit-contain w-75 top-50 start-50 translate-middle"><path fill="currentColor" d="M256 144C256 108.7 284.7 80 320 80C355.3 80 384 108.7 384 144L384 192L256 192L256 144zM208 192L144 192C117.5 192 96 213.5 96 240L96 448C96 501 139 544 192 544L448 544C501 544 544 501 544 448L544 240C544 213.5 522.5 192 496 192L432 192L432 144C432 82.1 381.9 32 320 32C258.1 32 208 82.1 208 144L208 192zM232 240C245.3 240 256 250.7 256 264C256 277.3 245.3 288 232 288C218.7 288 208 277.3 208 264C208 250.7 218.7 240 232 240zM384 264C384 250.7 394.7 240 408 240C421.3 240 432 250.7 432 264C432 277.3 421.3 288 408 288C394.7 288 384 277.3 384 264z" /></svg>
                        </div>
                        <h3 class="award-title d-flex gap-2">
                           <span class="award-value fw-semibold text-custom-2">{{ $productCount ?? \App\Models\MasterProduk::count() }}</span>
                           <span class="award-span d-flex flex-column">
                              <span class="text-custom-2 flex-grow-1 award-char">+</span>
                              <span>SKUS</span>
                           </span>
                        </h3>
                     </div>
                     <div class="col-12 col-md col-lg-12 flex-lg-grow-0 mt-lg-auto">Wide range of high-quality coffee products to meet global market needs.</div>
                  </div>
               </div>

               <div class="col col-12 col-lg-auto divider">
                  <hr class="d-lg-none">
                  <div class="vr h-100 d-none d-lg-block"></div>
               </div>

               <div class="award-item col col-12 col-lg">
                  <div class="award-card row row-gap-3 h-100">
                     <div class="col col-12 d-flex gap-3 align-items-center col-md col-lg-12 flex-lg-grow-1">
                        <div class="award-icon ratio ratio-1x1 bg-body-tertiary rounded-circle">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" class="object-fit-contain w-75 top-50 start-50 translate-middle"><path fill="currentColor" d="M415.9 344L225 344C227.9 408.5 242.2 467.9 262.5 511.4C273.9 535.9 286.2 553.2 297.6 563.8C308.8 574.3 316.5 576 320.5 576C324.5 576 332.2 574.3 343.4 563.8C354.8 553.2 367.1 535.8 378.5 511.4C398.8 467.9 413.1 408.5 416 344zM224.9 296L415.8 296C413 231.5 398.7 172.1 378.4 128.6C367 104.2 354.7 86.8 343.3 76.2C332.1 65.7 324.4 64 320.4 64C316.4 64 308.7 65.7 297.5 76.2C286.1 86.8 273.8 104.2 262.4 128.6C242.1 172.1 227.8 231.5 224.9 296zM176.9 296C180.4 210.4 202.5 130.9 234.8 78.7C142.7 111.3 74.9 195.2 65.5 296L176.9 296zM65.5 344C74.9 444.8 142.7 528.7 234.8 561.3C202.5 509.1 180.4 429.6 176.9 344L65.5 344zM463.9 344C460.4 429.6 438.3 509.1 406 561.3C498.1 528.6 565.9 444.8 575.3 344L463.9 344zM575.3 296C565.9 195.2 498.1 111.3 406 78.7C438.3 130.9 460.4 210.4 463.9 296L575.3 296z" /></svg>
                        </div>
                        <h3 class="award-title">
                           <span class="award-value fw-semibold text-custom-2">Global</span><br>
                           <span class="award-span">REACH</span>
                        </h3>
                     </div>
                     <div class="col-12 col-md col-lg-12 flex-lg-grow-0 mt-lg-auto">Trusted by customers in many countries worldwide.</div>
                  </div>
               </div>

            </div>
         </header>

         {{-- Category Tab Filter & Brands Logo Grid --}}
         <div class="mb-5">
            <header class="p-1 overflow-hidden" style="border-radius: calc(48px / 2); background-color: rgba(var(--default-color-rgb), .325);">
               <div class="table-responsive">
                  <ul class="nav nav-pills nav-justified flex-nowrap" id="tabBrands" role="tablist">
                     <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill active" id="pills-all-tab" data-bs-toggle="pill" data-bs-target="#pills-all" type="button" role="tab" aria-controls="pills-all" aria-selected="true">ALL CATEGORY</button>
                     </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill" id="pills-coffee-tab" data-bs-toggle="pill" data-bs-target="#pills-coffee" type="button" role="tab" aria-controls="pills-coffee" aria-selected="false">COFFEE</button>
                     </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill" id="pills-ginger-tab" data-bs-toggle="pill" data-bs-target="#pills-ginger" type="button" role="tab" aria-controls="pills-ginger" aria-selected="false">GINGER</button>
                     </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill" id="pills-chocolate-tab" data-bs-toggle="pill" data-bs-target="#pills-chocolate" type="button" role="tab" aria-controls="pills-chocolate" aria-selected="false">CHOCOLATE DRINK</button>
                     </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill" id="pills-coconut-tab" data-bs-toggle="pill" data-bs-target="#pills-coconut" type="button" role="tab" aria-controls="pills-coconut" aria-selected="false">COCONUT MILK</button>
                     </li>
                  </ul>
               </div>
            </header>

            <div class="tab-content mt-3" id="tabBrandsContent">
               {{-- ALL CATEGORY TAB --}}
               <div class="tab-pane fade show active" id="pills-all" role="tabpanel" aria-labelledby="pills-all-tab" tabindex="0">
                  <ul class="brands-logo-list list-unstyled row row-cols-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-5 py-4">
                     @foreach($mereks as $merek)
                        <li class="col">
                           <a href="{{ route('products.index') }}?brand={{ $merek->slug }}" class="link d-block ratio ratio-1x1 brands-logo mx-auto">
                              <img src="{{ asset($merek->logo_path) }}" data-light="{{ asset($merek->logo_path) }}" data-dark="{{ asset(str_replace('.png', '-invert.png', $merek->logo_path)) }}" alt="{{ $merek->nama_merek }}" loading="lazy" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                           </a>
                        </li>
                     @endforeach
                  </ul>
               </div>

               {{-- COFFEE TAB --}}
               <div class="tab-pane fade" id="pills-coffee" role="tabpanel" aria-labelledby="pills-coffee-tab" tabindex="0">
                  <ul class="brands-logo-list list-unstyled row row-cols-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-5 py-4">
                     @foreach($mereks->filter(fn($m) => in_array($m->slug, ['supresso', 'balicafe', 'ucafe', 'rasa-sayang', 'tugu-buaya', 'uang-emas', 'haocafe'])) as $merek)
                        <li class="col">
                           <a href="{{ route('products.index') }}?brand={{ $merek->slug }}" class="link d-block ratio ratio-1x1 brands-logo mx-auto">
                              <img src="{{ asset($merek->logo_path) }}" data-light="{{ asset($merek->logo_path) }}" data-dark="{{ asset(str_replace('.png', '-invert.png', $merek->logo_path)) }}" alt="{{ $merek->nama_merek }}" loading="lazy" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                           </a>
                        </li>
                     @endforeach
                  </ul>
               </div>

               {{-- GINGER TAB --}}
               <div class="tab-pane fade" id="pills-ginger" role="tabpanel" aria-labelledby="pills-ginger-tab" tabindex="0">
                  <ul class="brands-logo-list list-unstyled row row-cols-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-5 py-4">
                     @foreach($mereks->filter(fn($m) => $m->slug === 'jaheku') as $merek)
                        <li class="col">
                           <a href="{{ route('products.index') }}?brand={{ $merek->slug }}" class="link d-block ratio ratio-1x1 brands-logo mx-auto">
                              <img src="{{ asset($merek->logo_path) }}" data-light="{{ asset($merek->logo_path) }}" data-dark="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" loading="lazy" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                           </a>
                        </li>
                     @endforeach
                  </ul>
               </div>

               {{-- CHOCOLATE DRINK TAB --}}
               <div class="tab-pane fade" id="pills-chocolate" role="tabpanel" aria-labelledby="pills-chocolate-tab" tabindex="0">
                  <ul class="brands-logo-list list-unstyled row row-cols-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-5 py-4">
                     @foreach($mereks->filter(fn($m) => $m->slug === 'brochoco') as $merek)
                        <li class="col">
                           <a href="{{ route('products.index') }}?brand={{ $merek->slug }}" class="link d-block ratio ratio-1x1 brands-logo mx-auto">
                              <img src="{{ asset($merek->logo_path) }}" data-light="{{ asset($merek->logo_path) }}" data-dark="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" loading="lazy" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                           </a>
                        </li>
                     @endforeach
                  </ul>
               </div>

               {{-- COCONUT MILK TAB --}}
               <div class="tab-pane fade" id="pills-coconut" role="tabpanel" aria-labelledby="pills-coconut-tab" tabindex="0">
                  <ul class="brands-logo-list list-unstyled row row-cols-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-5 py-4">
                     @foreach($mereks->filter(fn($m) => $m->slug === 'intirasa') as $merek)
                        <li class="col">
                           <a href="{{ route('products.index') }}?brand={{ $merek->slug }}" class="link d-block ratio ratio-1x1 brands-logo mx-auto">
                              <img src="{{ asset($merek->logo_path) }}" data-light="{{ asset($merek->logo_path) }}" data-dark="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" loading="lazy" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                           </a>
                        </li>
                     @endforeach
                  </ul>
               </div>
            </div>
         </div>

         <footer class="d-lg-flex column-gap-lg-5 align-items-start">
            <h3 class="mb-3 mb-lg-0 text-nowrap fs-5 lh-base">
               MORE THAN <br>
               <b class="fw-semibold text-custom-2 fs-title">1.000.000+</b> <br>
               CUSTOMERS TRUST US
            </h3>
            <p>We are now looking for potential business relations for both domestic and international fields. Should you have any interests related to our company and products, do not hesitate to contact us directly. We would appreciate your interests and are looking forward to working with you.</p>
            <a href="{{ route('about') }}" class="btn btn-custom-1 rounded-pill text-nowrap">
               <span>Learn more</span>
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="16" height="16"><path fill="currentColor" d="M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z" /></svg>
            </a>
         </footer>

      </div>
   </section>

   {{-- News Section --}}
   <section aria-label="section news" class="container news-slider-section mb-5">
      <header class="d-flex justify-content-between align-items-center mb-3">
         <h2 class="fs-3 fw-semibold m-0">NEWS</h2>
         <div class="d-flex align-items-center gap-3">
            <a href="{{ route('news') }}" class="link d-flex align-items-center gap-1 fw-semibold">
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
            @forelse($latestNews as $news)
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

   {{-- Social Media Activity Section --}}
   <section aria-label="section social media" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-md-5 rounded-4 overflow-hidden position-relative">
         <h2 class="fs-3 fw-semibold mb-4">SOCIAL MEDIA ACTIVITY</h2>
         
         <div class="social-slider-container">
            <ul class="list-unstyled social-slider-track">
               <!-- Card 1 -->
               <li class="social-slide-item">
                  <div class="card social-card bg-white rounded-4 overflow-hidden shadow-sm h-100">
                     <div class="card-header social-card-header bg-transparent border-0 d-flex align-items-center justify-content-between p-3">
                        <div class="d-flex align-items-center gap-2">
                           <div class="social-profile-avatar-wrapper">
                              <img src="{{ asset('images/icon-indraco.png') }}" alt="indraco profile" class="social-profile-avatar">
                           </div>
                           <span class="social-profile-username fw-semibold text-dark small">INDRACO</span>
                        </div>
                        <div class="social-platform-icon text-dark">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="28" height="28"><path fill="currentColor" d="M320.3 205C256.8 204.8 205.2 256.2 205 319.7C204.8 383.2 256.2 434.8 319.7 435C383.2 435.2 434.8 383.8 435 320.3C435.2 256.8 383.8 205.2 320.3 205zM319.7 245.4C360.9 245.2 394.4 278.5 394.6 319.7C394.8 360.9 361.5 394.4 320.3 394.6C279.1 394.8 245.6 361.5 245.4 320.3C245.2 279.1 278.5 245.6 319.7 245.4zM413.1 200.3C413.1 185.5 425.1 173.5 439.9 173.5C454.7 173.5 466.7 185.5 466.7 200.3C466.7 215.1 454.7 227.1 439.9 227.1C425.1 227.1 413.1 215.1 413.1 200.3zM542.8 227.5C541.1 191.6 532.9 159.8 506.6 133.6C480.4 107.4 448.6 99.2 412.7 97.4C375.7 95.3 264.8 95.3 227.8 97.4C192 99.1 160.2 107.3 133.9 133.5C107.6 159.7 99.5 191.5 97.7 227.4C95.6 264.4 95.6 375.3 97.7 412.3C99.4 448.2 107.6 480 133.9 506.2C160.2 532.4 191.9 540.6 227.8 542.4C264.8 544.5 375.7 544.5 412.7 542.4C448.6 540.7 480.4 532.5 506.6 506.2C532.8 480 541 448.2 542.8 412.3C544.9 375.3 544.9 264.5 542.8 227.5zM495 452C487.2 471.6 472.1 486.7 452.4 494.6C422.9 506.3 352.9 503.6 320.3 503.6C287.7 503.6 217.6 506.2 188.2 494.6C168.6 486.8 153.5 471.7 145.6 452C133.9 422.5 136.6 352.5 136.6 319.9C136.6 287.3 134 217.2 145.6 187.8C153.4 168.2 168.5 153.1 188.2 145.2C217.7 133.5 287.7 136.2 320.3 136.2C352.9 136.2 423 133.6 452.4 145.2C472 153 487.1 168.1 495 187.8C506.7 217.3 504 287.3 504 319.9C504 352.5 506.7 422.6 495 452z"/></svg>
                        </div>
                     </div>
                     <div class="social-card-img-wrapper ratio ratio-1x1 overflow-hidden">
                        <img src="{{ asset('images/social-activity-1.jpg') }}" class="object-fit-cover w-100 h-100" alt="Instagram Post 1" loading="lazy">
                     </div>
                     <div class="card-body social-card-body p-3 d-flex align-items-center justify-content-between">
                        <div class="social-card-time d-flex align-items-center gap-1 text-secondary small">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="12" height="12" fill="currentColor">
                              <path d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-156.8l-77.8-77.7V112c0-8.8-7.2-16-16-16s-16 7.2-16 16v120c0 4.2 1.7 8.3 4.7 11.3l82.8 82.8c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6z"/>
                           </svg>
                           <span>6 days ago</span>
                        </div>
                     </div>
                     <a href="https://www.instagram.com" target="_blank" class="stretched-link"></a>
                  </div>
               </li>
               <!-- Card 2 -->
               <li class="social-slide-item">
                  <div class="card social-card bg-white rounded-4 overflow-hidden shadow-sm h-100">
                     <div class="card-header social-card-header bg-transparent border-0 d-flex align-items-center justify-content-between p-3">
                        <div class="d-flex align-items-center gap-2">
                           <div class="social-profile-avatar-wrapper">
                              <img src="{{ asset('images/icon-indraco.png') }}" alt="indraco profile" class="social-profile-avatar">
                           </div>
                           <span class="social-profile-username fw-semibold text-dark small">INDRACO</span>
                        </div>
                        <div class="social-platform-icon text-dark">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="28" height="28"><path fill="currentColor" d="M320.3 205C256.8 204.8 205.2 256.2 205 319.7C204.8 383.2 256.2 434.8 319.7 435C383.2 435.2 434.8 383.8 435 320.3C435.2 256.8 383.8 205.2 320.3 205zM319.7 245.4C360.9 245.2 394.4 278.5 394.6 319.7C394.8 360.9 361.5 394.4 320.3 394.6C279.1 394.8 245.6 361.5 245.4 320.3C245.2 279.1 278.5 245.6 319.7 245.4zM413.1 200.3C413.1 185.5 425.1 173.5 439.9 173.5C454.7 173.5 466.7 185.5 466.7 200.3C466.7 215.1 454.7 227.1 439.9 227.1C425.1 227.1 413.1 215.1 413.1 200.3zM542.8 227.5C541.1 191.6 532.9 159.8 506.6 133.6C480.4 107.4 448.6 99.2 412.7 97.4C375.7 95.3 264.8 95.3 227.8 97.4C192 99.1 160.2 107.3 133.9 133.5C107.6 159.7 99.5 191.5 97.7 227.4C95.6 264.4 95.6 375.3 97.7 412.3C99.4 448.2 107.6 480 133.9 506.2C160.2 532.4 191.9 540.6 227.8 542.4C264.8 544.5 375.7 544.5 412.7 542.4C448.6 540.7 480.4 532.5 506.6 506.2C532.8 480 541 448.2 542.8 412.3C544.9 375.3 544.9 264.5 542.8 227.5zM495 452C487.2 471.6 472.1 486.7 452.4 494.6C422.9 506.3 352.9 503.6 320.3 503.6C287.7 503.6 217.6 506.2 188.2 494.6C168.6 486.8 153.5 471.7 145.6 452C133.9 422.5 136.6 352.5 136.6 319.9C136.6 287.3 134 217.2 145.6 187.8C153.4 168.2 168.5 153.1 188.2 145.2C217.7 133.5 287.7 136.2 320.3 136.2C352.9 136.2 423 133.6 452.4 145.2C472 153 487.1 168.1 495 187.8C506.7 217.3 504 287.3 504 319.9C504 352.5 506.7 422.6 495 452z"/></svg>
                        </div>
                     </div>
                     <div class="social-card-img-wrapper ratio ratio-1x1 overflow-hidden">
                        <img src="{{ asset('images/social-activity-2.jpg') }}" class="object-fit-cover w-100 h-100" alt="Instagram Post 2" loading="lazy">
                     </div>
                     <div class="card-body social-card-body p-3 d-flex align-items-center justify-content-between">
                        <div class="social-card-time d-flex align-items-center gap-1 text-secondary small">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="12" height="12" fill="currentColor">
                              <path d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-156.8l-77.8-77.7V112c0-8.8-7.2-16-16-16s-16 7.2-16 16v120c0 4.2 1.7 8.3 4.7 11.3l82.8 82.8c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6z"/>
                           </svg>
                           <span>6 days ago</span>
                        </div>
                     </div>
                     <a href="https://www.instagram.com" target="_blank" class="stretched-link"></a>
                  </div>
               </li>
               <!-- Card 3 -->
               <li class="social-slide-item">
                  <div class="card social-card bg-white rounded-4 overflow-hidden shadow-sm h-100">
                     <div class="card-header social-card-header bg-transparent border-0 d-flex align-items-center justify-content-between p-3">
                        <div class="d-flex align-items-center gap-2">
                           <div class="social-profile-avatar-wrapper">
                              <img src="{{ asset('images/icon-indraco.png') }}" alt="indraco profile" class="social-profile-avatar">
                           </div>
                           <span class="social-profile-username fw-semibold text-dark small">INDRACO</span>
                        </div>
                        <div class="social-platform-icon text-dark">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="28" height="28"><path fill="currentColor" d="M320.3 205C256.8 204.8 205.2 256.2 205 319.7C204.8 383.2 256.2 434.8 319.7 435C383.2 435.2 434.8 383.8 435 320.3C435.2 256.8 383.8 205.2 320.3 205zM319.7 245.4C360.9 245.2 394.4 278.5 394.6 319.7C394.8 360.9 361.5 394.4 320.3 394.6C279.1 394.8 245.6 361.5 245.4 320.3C245.2 279.1 278.5 245.6 319.7 245.4zM413.1 200.3C413.1 185.5 425.1 173.5 439.9 173.5C454.7 173.5 466.7 185.5 466.7 200.3C466.7 215.1 454.7 227.1 439.9 227.1C425.1 227.1 413.1 215.1 413.1 200.3zM542.8 227.5C541.1 191.6 532.9 159.8 506.6 133.6C480.4 107.4 448.6 99.2 412.7 97.4C375.7 95.3 264.8 95.3 227.8 97.4C192 99.1 160.2 107.3 133.9 133.5C107.6 159.7 99.5 191.5 97.7 227.4C95.6 264.4 95.6 375.3 97.7 412.3C99.4 448.2 107.6 480 133.9 506.2C160.2 532.4 191.9 540.6 227.8 542.4C264.8 544.5 375.7 544.5 412.7 542.4C448.6 540.7 480.4 532.5 506.6 506.2C532.8 480 541 448.2 542.8 412.3C544.9 375.3 544.9 264.5 542.8 227.5zM495 452C487.2 471.6 472.1 486.7 452.4 494.6C422.9 506.3 352.9 503.6 320.3 503.6C287.7 503.6 217.6 506.2 188.2 494.6C168.6 486.8 153.5 471.7 145.6 452C133.9 422.5 136.6 352.5 136.6 319.9C136.6 287.3 134 217.2 145.6 187.8C153.4 168.2 168.5 153.1 188.2 145.2C217.7 133.5 287.7 136.2 320.3 136.2C352.9 136.2 423 133.6 452.4 145.2C472 153 487.1 168.1 495 187.8C506.7 217.3 504 287.3 504 319.9C504 352.5 506.7 422.6 495 452z"/></svg>
                        </div>
                     </div>
                     <div class="social-card-img-wrapper ratio ratio-1x1 overflow-hidden">
                        <img src="{{ asset('images/social-activity-3.jpg') }}" class="object-fit-cover w-100 h-100" alt="Instagram Post 3" loading="lazy">
                     </div>
                     <div class="card-body social-card-body p-3 d-flex align-items-center justify-content-between">
                        <div class="social-card-time d-flex align-items-center gap-1 text-secondary small">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="12" height="12" fill="currentColor">
                              <path d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-156.8l-77.8-77.7V112c0-8.8-7.2-16-16-16s-16 7.2-16 16v120c0 4.2 1.7 8.3 4.7 11.3l82.8 82.8c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6z"/>
                           </svg>
                           <span>6 days ago</span>
                        </div>
                     </div>
                     <a href="https://www.instagram.com" target="_blank" class="stretched-link"></a>
                  </div>
               </li>
               <!-- Card 4 -->
               <li class="social-slide-item">
                  <div class="card social-card bg-white rounded-4 overflow-hidden shadow-sm h-100">
                     <div class="card-header social-card-header bg-transparent border-0 d-flex align-items-center justify-content-between p-3">
                        <div class="d-flex align-items-center gap-2">
                           <div class="social-profile-avatar-wrapper">
                              <img src="{{ asset('images/icon-indraco.png') }}" alt="indraco profile" class="social-profile-avatar">
                           </div>
                           <span class="social-profile-username fw-semibold text-dark small">INDRACO</span>
                        </div>
                        <div class="social-platform-icon text-dark">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="28" height="28"><path fill="currentColor" d="M320.3 205C256.8 204.8 205.2 256.2 205 319.7C204.8 383.2 256.2 434.8 319.7 435C383.2 435.2 434.8 383.8 435 320.3C435.2 256.8 383.8 205.2 320.3 205zM319.7 245.4C360.9 245.2 394.4 278.5 394.6 319.7C394.8 360.9 361.5 394.4 320.3 394.6C279.1 394.8 245.6 361.5 245.4 320.3C245.2 279.1 278.5 245.6 319.7 245.4zM413.1 200.3C413.1 185.5 425.1 173.5 439.9 173.5C454.7 173.5 466.7 185.5 466.7 200.3C466.7 215.1 454.7 227.1 439.9 227.1C425.1 227.1 413.1 215.1 413.1 200.3zM542.8 227.5C541.1 191.6 532.9 159.8 506.6 133.6C480.4 107.4 448.6 99.2 412.7 97.4C375.7 95.3 264.8 95.3 227.8 97.4C192 99.1 160.2 107.3 133.9 133.5C107.6 159.7 99.5 191.5 97.7 227.4C95.6 264.4 95.6 375.3 97.7 412.3C99.4 448.2 107.6 480 133.9 506.2C160.2 532.4 191.9 540.6 227.8 542.4C264.8 544.5 375.7 544.5 412.7 542.4C448.6 540.7 480.4 532.5 506.6 506.2C532.8 480 541 448.2 542.8 412.3C544.9 375.3 544.9 264.5 542.8 227.5zM495 452C487.2 471.6 472.1 486.7 452.4 494.6C422.9 506.3 352.9 503.6 320.3 503.6C287.7 503.6 217.6 506.2 188.2 494.6C168.6 486.8 153.5 471.7 145.6 452C133.9 422.5 136.6 352.5 136.6 319.9C136.6 287.3 134 217.2 145.6 187.8C153.4 168.2 168.5 153.1 188.2 145.2C217.7 133.5 287.7 136.2 320.3 136.2C352.9 136.2 423 133.6 452.4 145.2C472 153 487.1 168.1 495 187.8C506.7 217.3 504 287.3 504 319.9C504 352.5 506.7 422.6 495 452z"/></svg>
                        </div>
                     </div>
                     <div class="social-card-img-wrapper ratio ratio-1x1 overflow-hidden">
                        <img src="{{ asset('images/social-activity-4.jpg') }}" class="object-fit-cover w-100 h-100" alt="Instagram Post 4" loading="lazy">
                     </div>
                     <div class="card-body social-card-body p-3 d-flex align-items-center justify-content-between">
                        <div class="social-card-time d-flex align-items-center gap-1 text-secondary small">
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="12" height="12" fill="currentColor">
                              <path d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-156.8l-77.8-77.7V112c0-8.8-7.2-16-16-16s-16 7.2-16 16v120c0 4.2 1.7 8.3 4.7 11.3l82.8 82.8c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6z"/>
                           </svg>
                           <span>6 days ago</span>
                        </div>
                     </div>
                     <a href="https://www.instagram.com" target="_blank" class="stretched-link"></a>
                  </div>
               </li>
            </ul>
         </div>
         
         <!-- Controls overlapping -->
         <button class="social-control-btn social-prev-btn btn btn-light rounded-circle shadow-sm" aria-label="Previous slide">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" fill="currentColor">
               <path d="M359 473c-9.4 9.4-24.6 9.4-33.9 0L135.7 283.7c-15-15-15-39.3 0-54.3L325.1 39c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9L185.3 256l173.8 173.1c9.4 9.4 9.4 24.6 0 33.9z"/>
            </svg>
         </button>
         <button class="social-control-btn social-next-btn btn btn-light rounded-circle shadow-sm" aria-label="Next slide">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" fill="currentColor">
               <path d="M153 39c9.4-9.4 24.6-9.4 33.9 0l189.4 189.3c15 15 15 39.3 0 54.3L186.9 473c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l173.8-173.1L153 72.9c-9.4-9.4-9.4-24.6 0-33.9z"/>
            </svg>
         </button>
      </div>
   </section>

   {{-- Careers Section --}}
   <section aria-label="section careers" class="container mb-5">
      <div class="row g-0 text-bg-custom-1 rounded-4 overflow-hidden">
         <div class="col px-4 py-5 p-md-5 col-12 col-lg-7">
            <h2>CAREERS<br><b class="fs-title text-custom-2 fw-semibold">Grow with us</b></h2>
            <p class="mb-4">Join our team and be part of a passionate group that brings the best of Indonesia's coffee to the world.</p>
            <a href="{{ route('careers') }}" class="btn btn-light rounded-pill">
               <span>View open position</span>
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="16" height="16"><path fill="currentColor" d="M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z" /></svg>
            </a>
         </div>
         <div class="col overflow-hidden col-12 col-lg-5 col-xxl-4 ms-xxl-auto">
            <img src="{{ asset('images/home-careers.jpg') }}" alt="Careers at Indraco" loading="lazy" class="ratio ratio-16x9 object-fit-cover w-100 h-100">
         </div>
      </div>
   </section>
@endsection

@push('scripts')
   <script src="{{ asset('js/home-banner.js') }}"></script>
   <script src="{{ asset('js/home-news.js') }}"></script>
   <script src="{{ asset('js/home-social.js') }}"></script>
@endpush
