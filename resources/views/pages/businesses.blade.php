@extends('layouts.app')

@section('title', 'INDRACO – Our Businesses')

@section('content')
<main id="content" tabindex="-1">

   <!-- Section Banner -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title">BUSINESSES</h2><!-- end banner title -->
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Delivering value across every step of our ecosystem</h3><!-- end banner text -->
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/icon-business.png') }}" alt="" class="banner-images"><!-- end banner images -->
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

   <!-- Section Accordion Businesses -->
   <section aria-label="section accordion" class="container">
      <div class="accordion d-flex flex-column row-gap-5" id="accordionBusinesses">

         <!-- Accordion Item 1: F&B PRODUCT EXPORT -->
         <div class="accordion-item bg-body-secondary px-4 py-5 p-lg-5 rounded-4 shadow">
            <div class="accordion-item-wrapper">
               <div class="row">
                  <div class="col col-7 mx-auto col-lg">
                     <div class="ratio ratio-1x1 w-100">
                        <img src="{{ asset('images/icon-export-light.png') }}" data-light="{{ asset('images/icon-export-light.png') }}" data-dark="{{ asset('images/icon-export-dark.png') }}" alt="" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                     </div>
                  </div>
                  <div class="col col-12 col-lg-10">
                     <h2 class="accordion-header">
                        <button class="btn w-100 btn-lg fs-3 fw-semibold text-center text-lg-start border-0 shadow-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accordioCollapseExport">F&amp;B PRODUCT EXPORT</button>
                     </h2>
                     <div id="accordioCollapseExport" class="collapse accordion-collapse d-lg-block">
                        <div class="accordion-body d-lg-grid grid-cols-2 gap-5">
                           <p class="mb-lg-0">
                              Bringing the finest flavors of Indonesia to the global stage. We actively export a wide range of premium F&amp;B products worldwide, meeting international standards and catering to diverse global market demands.
                           </p>
                           <ul class="w-lg-50 mb-lg-0">
                              <li>International Standard Compliance</li>
                              <li>Global Reach Across Continents</li>
                              <li>Reliable Export Logistics</li>
                           </ul>
                        </div>
                     </div>
                  </div>
               </div>
            </div><!-- end accordion item wrapper -->
         </div><!-- end accordion item -->

         <!-- Accordion Item 2: WHITE LABEL PACKAGING -->
         <div class="accordion-item bg-body-secondary px-4 py-5 p-lg-5 rounded-4 shadow">
            <div class="accordion-item-wrapper">
               <div class="row">
                  <div class="col col-7 mx-auto col-lg">
                     <div class="ratio ratio-1x1 w-100">
                        <img src="{{ asset('images/icon-white-label-light.png') }}" data-light="{{ asset('images/icon-white-label-light.png') }}" data-dark="{{ asset('images/icon-white-label-dark.png') }}" alt="" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                     </div>
                  </div>
                  <div class="col col-12 col-lg-10">
                     <h2 class="accordion-header">
                        <button class="btn w-100 btn-lg fs-3 fw-semibold text-center text-lg-start border-0 shadow-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accordioCollapseWLabel">WHITE LABEL PACKAGING</button>
                     </h2>
                     <div id="accordioCollapseWLabel" class="collapse accordion-collapse d-lg-block">
                        <div class="accordion-body d-lg-grid grid-cols-2 gap-5">
                           <p class="mb-lg-0">
                              We provide Contract Manufacturing (OEM) services, enabling business partners to launch their own coffee brands by leveraging Indracoʼs modern production facilities and stringent quality control.
                           </p>
                           <ul class="w-lg-50 mb-lg-0">
                              <li>Custom &amp; Exclusive Formulation</li>
                              <li>Professional Packaging Design</li>
                              <li>Comprehensive Certifications (Halal, BPOM, etc.)</li>
                           </ul>
                        </div>
                     </div>
                  </div>
               </div>
            </div><!-- end accordion item wrapper -->
         </div><!-- end accordion item -->

         <!-- Accordion Item 3: DISTRIBUTION CHANNELS -->
         <div class="accordion-item bg-body-secondary px-4 py-5 p-lg-5 rounded-4 shadow">
            <div class="accordion-item-wrapper">
               <div class="row">
                  <div class="col col-7 mx-auto col-lg">
                     <div class="ratio ratio-1x1 w-100">
                        <img src="{{ asset('images/icon-ditribution-light.png') }}" data-light="{{ asset('images/icon-ditribution-light.png') }}" data-dark="{{ asset('images/icon-ditribution-dark.png') }}" alt="" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                     </div>
                  </div>
                  <div class="col col-12 col-lg-10">
                     <h2 class="accordion-header">
                        <button class="btn w-100 btn-lg fs-3 fw-semibold text-center text-lg-start border-0 shadow-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accordioCollapseDistribution">DISTRIBUTION CHANNELS</button>
                     </h2>
                     <div id="accordioCollapseDistribution" class="collapse accordion-collapse d-lg-block">
                        <div class="accordion-body">
                           <p class="mb-4">We operate a nation-wide distribution system with multiple reliable channels to support the spread of our mission and to supply the best of our services.</p>
                           <div class="d-lg-grid grid-cols-2 gap-5">
                              <div>
                                 <h3 class="fs-5 fw-semibold">Domestic Distribution Map</h3>
                                 <p class="mb-lg-0">Our team of talented distributors work simultaneously around the clock to deliver the best of our products and services to customers.</p>
                              </div>
                              <div>
                                 <h3 class="fs-5 fw-semibold">International Distribution Map</h3>
                                 <p class="mb-lg-0">Our team of talented distributors work simultaneously around the clock to deliver the best of our products and services to customers.</p>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div><!-- end accordion item wrapper -->
         </div><!-- end accordion item -->

         <!-- Accordion Item 4: ONLINE CHANNELS -->
         <div class="accordion-item bg-body-secondary px-4 py-5 p-lg-5 rounded-4 shadow">
            <div class="accordion-item-wrapper">
               <div class="row">
                  <div class="col col-7 mx-auto col-lg">
                     <div class="ratio ratio-1x1 w-100">
                        <img src="{{ asset('images/icon-online-light.png') }}" data-light="{{ asset('images/icon-online-light.png') }}" data-dark="{{ asset('images/icon-online-dark.png') }}" alt="" class="theme-image object-fit-contain top-50 start-50 translate-middle">
                     </div>
                  </div>
                  <div class="col col-12 col-lg-10">
                     <h2 class="accordion-header">
                        <button class="btn w-100 btn-lg fs-3 fw-semibold text-center text-lg-start border-0 shadow-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accordioCollapseChannels">ONLINE CHANNELS</button>
                     </h2>
                     <div id="accordioCollapseChannels" class="collapse accordion-collapse d-lg-block">
                        <div class="accordion-body">
                           <p class="mb-4">Our advanced online platforms are now live and available for our customers to search, explore and enjoy our products in the easiest ways possible.</p>
                           <div class="d-lg-grid grid-cols-2 gap-5">
                              <div>
                                 <h3 class="fs-5 fw-semibold">E-commerce</h3>
                                 <p>Our online catalog and products with best prices, vouchers, and special discounts for all members of the coffee lovers community.</p>
                                 <ul class="list-unstyled mb-0 d-flex flex-wrap gap-3 column-gap-4 align-items-center">
                                    <li>
                                       <a href="#" target="_blank" class="text-decoration-none">
                                          <img src="{{ asset('images/logo-supresso-typograph-flat.png') }}" alt="" class="brand-logo w-100 h-auto" style="max-width: 180px;">
                                       </a>
                                    </li>
                                    <li>
                                       <a href="#" target="_blank" class="text-decoration-none">
                                          <img src="{{ asset('images/logo-indracostore-flat.png') }}" alt="" class="brand-logo w-100 h-auto" style="max-width: 180px;">
                                       </a>
                                    </li>
                                 </ul>
                              </div>
                              <div>
                                 <h3 class="fs-5 fw-semibold">Marketplace</h3>
                                 <p>Discover our top products available now on leading marketplaces near you.</p>
                                 <ul class="list-unstyled mb-0 d-flex flex-wrap gap-3 column-gap-4 align-items-center">
                                    <li>
                                       <a href="#" target="_blank" class="text-decoration-none">
                                          <img src="{{ asset('images/logo-shopee-flat.png') }}" alt="" class="brand-logo w-100 h-auto" style="max-width: 100px;">
                                       </a>
                                    </li>
                                    <li>
                                       <a href="#" target="_blank" class="text-decoration-none">
                                          <img src="{{ asset('images/logo-lazada-flat.png') }}" alt="" class="brand-logo w-100 h-auto" style="max-width: 100px;">
                                       </a>
                                    </li>
                                    <li>
                                       <a href="#" target="_blank" class="text-decoration-none">
                                          <img src="{{ asset('images/logo-tokopedia-flat.png') }}" alt="" class="brand-logo w-100 h-auto" style="max-width: 100px;">
                                       </a>
                                    </li>
                                    <li>
                                       <a href="#" target="_blank" class="text-decoration-none">
                                          <img src="{{ asset('images/logo-blibli-flat.png') }}" alt="" class="brand-logo w-100 h-auto" style="max-width: 70px;">
                                       </a>
                                    </li>
                                    <li>
                                       <a href="#" target="_blank" class="text-decoration-none">
                                          <img src="{{ asset('images/logo-tiktok-shop-flat.png') }}" alt="" class="brand-logo w-100 h-auto" style="max-width: 140px;">
                                       </a>
                                    </li>
                                 </ul>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div><!-- end accordion item wrapper -->
         </div><!-- end accordion item -->

      </div><!-- end accordionBusinesses -->
   </section>

</main>
@endsection
