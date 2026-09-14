@extends('layouts.app')

@section('title', 'INDRACO – About Us')

@push('styles')
   <link rel="stylesheet" href="{{ asset('css/home-banner.css') }}">
   <link rel="stylesheet" href="{{ asset('css/about-carousel.css') }}">
   <style>
      .core-item {
         position: relative;
         display: flex;
         flex-direction: column;
         align-items: center;
      }
      .core-item::after {
         content: '';
         width: calc(100% - 2rem);
         border-top: solid 1px rgba(var(--default-color-rgb), .5);
         position: absolute;
         top: -1.5rem;
         left: 50%;
         transform: translateX(-50%);
      }
      .core-item:first-of-type::after { display: none; }
      @media (min-width: 768px) {
         .core-item::after {
            width: calc(100% - 3rem);
         }
         .core-item:nth-of-type(2)::after { display: none; }
         
         .core-item::before {
            content: '';
            height: 100%;
            border-left: solid 1px rgba(var(--default-color-rgb), .5);
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
         }
         .core-item:nth-of-type(1)::before,
         .core-item:nth-of-type(3)::before,
         .core-item:nth-of-type(5)::before,
         .core-item:nth-of-type(7)::before { display: none; }
      }
      @media (min-width: 992px) {
         .core-item:nth-of-type(3)::after { display: none; }
         .core-item:nth-of-type(3)::before,
         .core-item:nth-of-type(5)::before { display: inline; }
         .core-item:nth-of-type(4)::before { display: none; }
      }
      @media (min-width: 1400px) {
         .core-list {
            --bs-gutter-x: 8rem;
            --bs-gutter-y: 8rem;
         }
         .core-item::after {
            top: -4rem;
            width: calc(100% - 8rem);
         }
      }
      .core-text { text-align: justify; margin: 0; }
   </style>
@endpush

@push('scripts')
   <script src="{{ asset('js/about-carousel.js') }}"></script>
@endpush

@section('content')
<main id="content" tabindex="-1">
   
   <!-- Banner Section -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title">ABOUT US</h2>
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Uniting through flavour, connecting through life.</h3>
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/icon-about.png') }}" alt="About Us Banner Icon" class="banner-images">
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

   <!-- About Section -->
   <section aria-label="about section" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden">
         <div class="row row-cols-1 row-cols-lg-2 gy-4 g-lg-5">
            <div class="col order-lg-2">
               <div class="ratio ratio-16x9 rounded-4 overflow-hidden">
                  <img src="{{ asset('images/home-about.jpg') }}" alt="About Indraco" class="object-fit-cover"> 
               </div>
            </div>
            <div class="col order-lg-1">
               <h2 class="fs-3 fw-semibold mb-4">ABOUT US</h2>
               <p>
                  WE ARE INDRACO - Beginning in 1971, producing and distributing coffee, INDRACO continues to advance and expand into several advanced manufacturing facilities and distribution centers across Indonesia and Singapore.
                  <br><br>
                  Specializing in coffee for over half a century, INDRACO aims to create an experience and evoke feelings within each touch point in our customer's journey. From our products to our retail experiences as well as our F&amp;B concepts, creativity and innovation remain at their core.
               </p>
            </div>
            <div class="col order-lg-3">
               <div class="ratio ratio-16x9 rounded-4 overflow-hidden">
                  <img src="{{ asset('images/love-beans.jpg') }}" alt="Love Beans" class="object-fit-cover"> 
               </div>
            </div>
            <div class="col order-lg-4">
               <p>
                  With over 11 brands and 600 SKUs across coffee, ginger, and chocolate products, we continue to strive, innovate, and operate with ingenuity taught by our founding members in the ever-changing FMCG and F&amp;B industry.
               </p>
            </div>
         </div>
      </div>
   </section>

   <!-- Founder & Board of Executive Section -->
   <section aria-label="fouder section" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden d-flex flex-column row-gap-5">
         <section aria-label="founder section">
            <h2 class="fs-3 fw-semibold mb-4">FOUNDER'S STORY</h2>
            <div class="leader-list row g-3 row-cols-1 row-cols-lg-2">
               <div class="leader-item col">
                  <div class="card card-leader rounded-4 shadow overflow-hidden">
                     <div class="row g-0">
                        <div class="col col-12 col-md-5">
                           <div class="card-header rounded-0 p-0 overflow-hidden position-relative h-100">
                              <img src="{{ asset('images/person.jpg') }}" alt="Founder" class="object-fit-cover w-100 h-100 position-absolute top-50 start-50 translate-middle">
                           </div>
                        </div>
                        <div class="col col-12 col-md-7">
                           <div class="card-body py-md-4">
                              <h3 class="fs-4 fw-semibold">Mr. Name Surename</h3>
                              <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Animi, possimus. Soluta vel, sint facere rem quasi porro repudiandae suscipit tempore excepturi tempora impedit nulla consequatur eligendi modi distinctio? Laudantium, beatae.</p>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="leader-item col">
                  <div class="card card-leader rounded-4 shadow overflow-hidden">
                     <div class="row g-0">
                        <div class="col col-12 col-md-5">
                           <div class="card-header rounded-0 p-0 overflow-hidden position-relative h-100">
                              <img src="{{ asset('images/person.jpg') }}" alt="Founder" class="object-fit-cover w-100 h-100 position-absolute top-50 start-50 translate-middle">
                           </div>
                        </div>
                        <div class="col col-12 col-md-7">
                           <div class="card-body py-md-4">
                              <h3 class="fs-4 fw-semibold">Mr. Name Surename</h3>
                              <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Animi, possimus. Soluta vel, sint facere rem quasi porro repudiandae suscipit tempore excepturi tempora impedit nulla consequatur eligendi modi distinctio? Laudantium, beatae.</p>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <section aria-label="executive section">
            <h2 class="fs-3 fw-semibold mb-4">BOARD OF EXECUTIVE</h2>
            <div class="leader-list row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4">
               <div class="leader-item col">
                  <div class="card card-leader rounded-4 shadow overflow-hidden">
                     <div class="card-header rounded-0 p-0 ratio ratio-4x3">
                        <img src="{{ asset('images/person.jpg') }}" alt="Executive" class="object-fit-cover w-100 h-100 position-absolute top-50 start-50 translate-middle">
                     </div>
                     <div class="card-body py-md-4">
                        <h3 class="fs-5 fw-semibold">Mr. Name Surename</h3>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Animi, possimus. Soluta vel, sint facere rem quasi porro repudiandae suscipit tempore excepturi tempora impedit nulla consequatur eligendi modi distinctio? Laudantium, beatae.</p>
                     </div>
                  </div>
               </div>
               <div class="leader-item col">
                  <div class="card card-leader rounded-4 shadow overflow-hidden">
                     <div class="card-header rounded-0 p-0 ratio ratio-4x3">
                        <img src="{{ asset('images/person.jpg') }}" alt="Executive" class="object-fit-cover w-100 h-100 position-absolute top-50 start-50 translate-middle">
                     </div>
                     <div class="card-body py-md-4">
                        <h3 class="fs-5 fw-semibold">Mr. Name Surename</h3>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Animi, possimus. Soluta vel, sint facere rem quasi porro repudiandae suscipit tempore excepturi tempora impedit nulla consequatur eligendi modi distinctio? Laudantium, beatae.</p>
                     </div>
                  </div>
               </div>
               <div class="leader-item col">
                  <div class="card card-leader rounded-4 shadow overflow-hidden">
                     <div class="card-header rounded-0 p-0 ratio ratio-4x3">
                        <img src="{{ asset('images/person.jpg') }}" alt="Executive" class="object-fit-cover w-100 h-100 position-absolute top-50 start-50 translate-middle">
                     </div>
                     <div class="card-body py-md-4">
                        <h3 class="fs-5 fw-semibold">Mr. Name Surename</h3>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Animi, possimus. Soluta vel, sint facere rem quasi porro repudiandae suscipit tempore excepturi tempora impedit nulla consequatur eligendi modi distinctio? Laudantium, beatae.</p>
                     </div>
                  </div>
               </div>
               <div class="leader-item col">
                  <div class="card card-leader rounded-4 shadow overflow-hidden">
                     <div class="card-header rounded-0 p-0 ratio ratio-4x3">
                        <img src="{{ asset('images/person.jpg') }}" alt="Executive" class="object-fit-cover w-100 h-100 position-absolute top-50 start-50 translate-middle">
                     </div>
                     <div class="card-body py-md-4">
                        <h3 class="fs-5 fw-semibold">Mr. Name Surename</h3>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Animi, possimus. Soluta vel, sint facere rem quasi porro repudiandae suscipit tempore excepturi tempora impedit nulla consequatur eligendi modi distinctio? Laudantium, beatae.</p>
                     </div>
                  </div>
               </div>
            </div>
         </section>
      </div>
   </section>

   <!-- Vision & Mission Section -->
   <section aria-label="vision & mission section" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden">
         <div class="row row-cols-1 row-cols-sm-2 row-gap-5">
            <div class="col">
               <h2 class="fs-3 fw-semibold mb-4">OUR VISION</h2>
               <p>Become a globally recognised company and obtain positive growth whereby accentuating our five core values of "Customer Focus", "Teamwork", "Integrity", "Resources", and "Innovation".</p>
            </div>
            <div class="col">
               <h2 class="fs-3 fw-semibold mb-4">OUR MISSION</h2>
               <p>Provide supreme, top-quality products at competitive prices that meet and surpass consumers' needs.</p>
            </div>
         </div>
      </div>
   </section>

   <!-- Core Values Section -->
   <section aria-label="value section" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden">
         <h2 class="fs-3 fw-semibold mb-4">CORE VALUES</h2>
         <div class="row core-list row-cols-1 gy-5 row-cols-md-2 gx-md-5 row-cols-lg-3">
            <div class="col core-item text-center">
               <h3 class="fw-semibold lh-1 m-0" style="font-size: clamp(15rem, 5vw, 25rem);">5</h3>
            </div>
            <div class="col core-item">
               <h3 class="core-title fs-4 d-flex align-items-center gap-3 mb-3">
                  <img src="{{ asset('images/core-values-customer-focus.svg') }}" alt="Customer Focus" width="52" height="52">
                  <span>CUTOMER FOCUS</span>
               </h3>
               <p class="core-text">We will go the distance to delight our customers, manifesting them into our advocates in the process. Our objective is to create and maintain long term relationships with our customers by generating trust, confidence and loyalty.</p>
            </div>
            <div class="col core-item">
               <h3 class="core-title fs-4 d-flex align-items-center gap-3 mb-3">
                  <img src="{{ asset('images/core-values-teamwork.svg') }}" alt="Teamwork" width="52" height="52">
                  <span>TEAMWORK</span>
               </h3>
               <p class="core-text">We "Work Together as One Team, As One Indraco". We continue to create harmony and always remember those we work with.</p>
            </div>
            <div class="col core-item">
               <h3 class="core-title fs-4 d-flex align-items-center gap-3 mb-3">
                  <img src="{{ asset('images/core-values-resources.svg') }}" alt="Resources" width="52" height="52">
                  <span>RESOURCES</span>
               </h3>
               <p class="core-text">We escalate our capacity by optimising our abilities and efficiently utilising all resources. We are the people who confront the most complex challenges that others might consider insurmountable.</p>
            </div>
            <div class="col core-item">
               <h3 class="core-title fs-4 d-flex align-items-center gap-3 mb-3">
                  <img src="{{ asset('images/core-values-innovation.svg') }}" alt="Innovation" width="52" height="52">
                  <span>INNOVATION</span>
               </h3>
               <p class="core-text">We are the people who embrace new mindsets and are proactive in commencing changes and enhancement.</p>
            </div>
            <div class="col core-item">
               <h3 class="core-title fs-4 d-flex align-items-center gap-3 mb-3">
                  <img src="{{ asset('images/core-values-integrity.svg') }}" alt="Integrity" width="52" height="52">
                  <span>INTEGRITY</span>
               </h3>
               <p class="core-text">We manage our business ethically and morally. We are convinced that we have gained the trust and respect of our customers and everyone we cooperate with by being transparent, honest and honourable in all our actions.</p>
            </div>
         </div>
      </div>
   </section>

   <!-- Over The Years Section -->
   <section aria-label="over the years section" class="container mb-5">
      <div class="text-bg-custom-1 px-4 py-5 p-lg-5 rounded-4 overflow-hidden about-slider-section">
         <h2 class="fs-3 fw-semibold mb-4">OVER THE YEARS</h2>
         
         <div class="about-slider-clip-wrapper">
            <div class="about-slider-container">
               <div class="about-slider-track">
                  
                  <!-- Card 1 -->
                  <div class="about-slide-item">
                     <div class="card timeline-card">
                        <div class="timeline-img-wrapper">
                           <img src="{{ asset('images/timeline-1971.jpg') }}" alt="1971: Established">
                        </div>
                        <h3 class="timeline-title">Established</h3>
                        <hr class="timeline-divider">
                        <button type="button" class="timeline-more-btn" data-bs-toggle="modal" data-bs-target="#modal1971">more</button>
                     </div>
                  </div>
                  
                  <!-- Card 2 -->
                  <div class="about-slide-item">
                     <div class="card timeline-card">
                        <div class="timeline-img-wrapper">
                           <img src="{{ asset('images/timeline-1977.jpg') }}" alt="1977: Expansion To Surabaya">
                        </div>
                        <h3 class="timeline-title">Expansion<br>To Surabaya</h3>
                        <hr class="timeline-divider">
                        <button type="button" class="timeline-more-btn" data-bs-toggle="modal" data-bs-target="#modal1977">more</button>
                     </div>
                  </div>
                  
                  <!-- Card 3 -->
                  <div class="about-slide-item">
                     <div class="card timeline-card">
                        <div class="timeline-img-wrapper">
                           <img src="{{ asset('images/timeline-1996.jpg') }}" alt="1996: Move to Gresik">
                        </div>
                        <h3 class="timeline-title">Move<br>To Gresik</h3>
                        <hr class="timeline-divider">
                        <button type="button" class="timeline-more-btn" data-bs-toggle="modal" data-bs-target="#modal1996">more</button>
                     </div>
                  </div>
                  
                  <!-- Card 4 -->
                  <div class="about-slide-item">
                     <div class="card timeline-card">
                        <div class="timeline-img-wrapper">
                           <img src="{{ asset('images/timeline-2000.jpg') }}" alt="2000: Asiaterra Integration">
                        </div>
                        <h3 class="timeline-title">Asiaterra<br>Integration</h3>
                        <hr class="timeline-divider">
                        <button type="button" class="timeline-more-btn" data-bs-toggle="modal" data-bs-target="#modal2000">more</button>
                     </div>
                  </div>
                  
                  <!-- Card 5 -->
                  <div class="about-slide-item">
                     <div class="card timeline-card">
                        <div class="timeline-img-wrapper">
                           <img src="{{ asset('images/timeline-2018.jpg') }}" alt="2018: Global Expansion">
                        </div>
                        <h3 class="timeline-title">Global<br>Expansion</h3>
                        <hr class="timeline-divider">
                        <button type="button" class="timeline-more-btn" data-bs-toggle="modal" data-bs-target="#modal2018">more</button>
                     </div>
                  </div>
                  
               </div>
            </div>
         </div>
         
         <!-- Indicators -->
         <div class="about-slider-indicators d-flex justify-content-center align-items-center gap-2 mt-4"></div>
         
      </div>
   </section>

   <!-- Timeline Modals -->
   <!-- Modal 1971 -->
   <div class="modal fade modal-timeline" id="modal1971" tabindex="-1" aria-labelledby="modal1971Label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="modal1971Label">1971 &ndash; Established</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
               <img src="{{ asset('images/timeline-1971.jpg') }}" alt="1971: Established" class="timeline-modal-img">
               <h4 class="h5 fw-bold mb-3">UD Intisari Founded</h4>
               <p class="mb-0 text-secondary">Dimulai dari gudang kopi kecil di Dumai, Riau, Sumatra oleh pendiri kami, UD Intisari memproduksi dan mendistribusikan kopi berkualitas tinggi, yang menjadi fondasi utama kesuksesan Indraco.</p>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal 1977 -->
   <div class="modal fade modal-timeline" id="modal1977" tabindex="-1" aria-labelledby="modal1977Label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="modal1977Label">1977 &ndash; Expansion To Surabaya</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
               <img src="{{ asset('images/timeline-1977.jpg') }}" alt="1977: Expansion To Surabaya" class="timeline-modal-img">
               <h4 class="h5 fw-bold mb-3">Launch of Legend Tugu Buaya Coffee</h4>
               <p class="mb-0 text-secondary">Indraco memindahkan basis operasionalnya ke Surabaya untuk menjangkau pasar yang lebih luas di pulau Jawa dan seluruh wilayah Indonesia Timur, serta secara resmi meluncurkan merek legendaris "Kopi Tugu Buaya".</p>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal 1996 -->
   <div class="modal fade modal-timeline" id="modal1996" tabindex="-1" aria-labelledby="modal1996Label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="modal1996Label">1996 &ndash; Move To Gresik</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
               <img src="{{ asset('images/timeline-1996.jpg') }}" alt="1996: Move To Gresik" class="timeline-modal-img">
               <h4 class="h5 fw-bold mb-3">New Factory in Driyorejo, Gresik</h4>
               <p class="mb-0 text-secondary">Untuk memenuhi permintaan pasar yang terus melonjak tinggi, Indraco membangun pabrik modern berskala besar di Driyorejo, Gresik, Jawa Timur, guna menyatukan seluruh kegiatan produksi dan logistik.</p>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal 2000 -->
   <div class="modal fade modal-timeline" id="modal2000" tabindex="-1" aria-labelledby="modal2000Label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="modal2000Label">2000 &ndash; Asiaterra Integration</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
               <img src="{{ asset('images/timeline-2000.jpg') }}" alt="2000: Asiaterra Integration" class="timeline-modal-img">
               <h4 class="h5 fw-bold mb-3">Nationwide Distribution Network</h4>
               <p class="mb-0 text-secondary">Indraco mendirikan unit distribusi khusus "Asiaterra" (PT. Asiaterra Indopangan) untuk secara profesional mengelola rantai pasok dan memperluas distribusi produk Indraco ke seluruh wilayah Indonesia.</p>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal 2018 -->
   <div class="modal fade modal-timeline" id="modal2018" tabindex="-1" aria-labelledby="modal2018Label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="modal2018Label">2018 &ndash; Global Expansion</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
               <img src="{{ asset('images/timeline-2018.jpg') }}" alt="2018: Global Expansion" class="timeline-modal-img">
               <h4 class="h5 fw-bold mb-3">Supresso International Singapore</h4>
               <p class="mb-0 text-secondary">Indraco melangkah ke kancah global dengan mendirikan Supresso International (INDRACO Pte. Ltd.) di Singapura untuk menyajikan produk kopi premium, termasuk kapsul kopi, ke pasar internasional.</p>
            </div>
         </div>
      </div>
   </div>

</main>
@endsection
