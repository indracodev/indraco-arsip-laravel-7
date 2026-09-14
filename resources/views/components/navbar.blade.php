<nav aria-label="navigation bar" class="navbar navbar-expand">
   <div class="container">

      <a href="{{ route('home') }}" class="navbar-brand d-flex">
         @php
            $customHeaderLogo = \App\Models\MasterSetting::get('header_logo');
         @endphp
         @if($customHeaderLogo && file_exists(public_path($customHeaderLogo)))
            <img src="{{ asset($customHeaderLogo) }}" alt="Logo INDRACO Est." class="w-100 h-auto" style="max-height: 46px; object-fit: contain;">
         @else
            <img src="{{ asset('images/logo-indraco-est.png') }}" data-light="{{ asset('images/logo-indraco-est.png') }}" data-dark="{{ asset('images/logo-indraco-est-invert.png') }}" alt="Logo INDRACO Est." class="theme-image w-100 h-auto">
         @endif
      </a>

      <div class="menu d-flex align-items-start gap-2">
         @auth
            <a href="{{ route('admin.dashboard') }}" class="d-none d-md-flex align-items-center align-self-start gap-2 bg-body-secondary px-3 px-lg-4 shadow-sm border border-secondary-subtle text-decoration-none transition-all" style="height: 56px; border-radius: 28px;" title="Buka Admin Dashboard">
               <span class="spinner-grow spinner-grow-sm text-custom-1 flex-shrink-0" style="width: 9px; height: 9px;" role="status"></span>
               <span class="small fw-medium text-secondary" style="font-size: 0.875rem;">Logged in as: <strong class="text-custom-1 fw-bold">{{ auth()->user()->name ?? auth()->user()->email }}</strong></span>
            </a>
         @endauth

         <div class="menu-wrapper p-1 overflow-hidden" style="background-color: rgba(var(--bs-body-bg-rgb), 0.875); backdrop-filter: blur(2rem); border-radius: calc(56px / 2);">

            <header class="menu-header text-bg-custom-1 p-1 rounded-pill">
               <ul class="nav justify-content-between align-items-center gap-2 gap-md-3">
                  <li>
                     <button class="toggle-menu-collapse btn rounded-pill header-toggle-btn collapsed" aria-label="Toggle main menu" aria-expanded="false" aria-controls="navcol" type="button" data-bs-toggle="collapse" data-bs-target="#navcol">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="21" height="21">
                           <g class="icon-bars">
                              <path fill="currentColor" d="M96 160C96 142.3 110.3 128 128 128L512 128C529.7 128 544 142.3 544 160C544 177.7 529.7 192 512 192L128 192C110.3 192 96 177.7 96 160zM96 320C96 302.3 110.3 288 128 288L512 288C529.7 288 544 302.3 544 320C544 337.7 529.7 352 512 352L128 352C110.3 352 96 337.7 96 320zM544 480C544 497.7 529.7 512 512 512L128 512C110.3 512 96 497.7 96 480C96 462.3 110.3 448 128 448L512 448C529.7 448 544 462.3 544 480z"/>
                           </g>
                           <g class="icon-xmark">
                              <path fill="currentColor" d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"/>
                           </g>
                        </svg>
                     </button>
                  </li>
                  <li class="d-none d-md-block">
                     @include('components.toggle-language')
                  </li>
                  <li>
                     <button class="btn rounded-pill header-toggle-btn" aria-label="Open search dialog" type="button" data-bs-toggle="modal" data-bs-target="#modal-search">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="21" height="21"><path fill="currentColor" d="M480 272C480 317.9 465.1 360.3 440 394.7L566.6 521.4C579.1 533.9 579.1 554.2 566.6 566.7C554.1 579.2 533.8 579.2 521.3 566.7L394.7 440C360.3 465.1 317.9 480 272 480C157.1 480 64 386.9 64 272C64 157.1 157.1 64 272 64C386.9 64 480 157.1 480 272zM272 416C351.5 416 416 351.5 416 272C416 192.5 351.5 128 272 128C192.5 128 128 192.5 128 272C128 351.5 192.5 416 272 416z"/></svg>
                     </button>
                  </li>
                  <li>
                     <button class="theme-toggle btn rounded-pill header-toggle-btn" type="button" aria-label="Toggle color theme">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" width="21" height="21" class="theme-icon">
                           <g class="icon-sun">
                              <circle cx="12" cy="12" r="5" fill="currentColor"></circle>
                              <g stroke="currentColor" stroke-width="2">
                                 <line x1="12" y1="1" x2="12" y2="4"></line>
                                 <line x1="12" y1="20" x2="12" y2="23"></line>
                                 <line x1="1" y1="12" x2="4" y2="12"></line>
                                 <line x1="20" y1="12" x2="23" y2="12"></line>
                                 <line x1="4.2" y1="4.2" x2="6.3" y2="6.3"></line>
                                 <line x1="17.7" y1="17.7" x2="19.8" y2="19.8"></line>
                                 <line x1="4.2" y1="19.8" x2="6.3" y2="17.7"></line>
                                 <line x1="17.7" y1="6.3" x2="19.8" y2="4.2"></line>
                              </g>
                           </g>
                           <g class="icon-moon">
                              <path d="M21 12.8A9 9 0 1111.2 3 a7 7 0 109.8 9.8z" fill="currentColor"></path>
                           </g>
                        </svg>
                     </button>
                  </li>
               </ul>
            </header>

            <div id="navcol" class="menu-collapse collapse">
               <div class="menu-body p-3 d-flex flex-column row-gap-4 py-4">

                  <section aria-label="section menu company">
                     <h3 class="menu-title small opacity-50">Menu</h3>
                     <hr class="opacity-50 my-2">
                     <ul class="menu-list nav d-grid column-gap-3 row-gap-1 row-gap-md-2 grid-cols-2-auto">
                        <li><a href="{{ route('home') }}" class="link">Home</a></li>
                        <li><a href="{{ route('about') }}" class="link">About Us</a></li>
                        <li><a href="{{ route('products.index') }}" class="link">Products</a></li>
                        <li><a href="{{ route('businesses') }}" class="link">Business</a></li>
                        <li><a href="{{ route('store') }}" class="link">Online Store</a></li>
                        <li><a href="{{ route('news') }}" class="link">News</a></li>
                        <li><a href="{{ route('csr') }}" class="link">CSR</a></li>
                        <li><a href="{{ route('careers') }}" class="link">Careers</a></li>
                        <li><a href="{{ route('contact') }}" class="link">Contact Us</a></li>
                        <li><a href="{{ route('downloads') }}" class="link">Downloads</a></li>
                     </ul>
                  </section>

                  <section aria-label="section menu brands">
                     <h3 class="menu-title small opacity-50">Our Brands</h3>
                     <hr class="opacity-50 my-2">
                     <ul class="menu-list nav d-grid column-gap-3 row-gap-1 row-gap-md-2 grid-cols-2-auto">
                        <li><a href="{{ route('products.index') }}?brand=supresso" class="link">Supresso</a></li>
                        <li><a href="{{ route('products.index') }}?brand=balicafe" class="link">BaliCafé</a></li>
                        <li><a href="{{ route('products.index') }}?brand=ucafe" class="link">UCAFÉ</a></li>
                        <li><a href="{{ route('products.index') }}?brand=rasa-sayang" class="link">Rasa Sayang</a></li>
                        <li><a href="{{ route('products.index') }}?brand=tugu-buaya" class="link">Tugu Buaya</a></li>
                        <li><a href="{{ route('products.index') }}?brand=uang-emas" class="link">Uang Emas</a></li>
                        <li><a href="{{ route('products.index') }}?brand=brochoco" class="link">BROCHOCO</a></li>
                        <li><a href="{{ route('products.index') }}?brand=jaheku" class="link">Jaheku</a></li>
                        <li><a href="{{ route('products.index') }}?brand=intirasa" class="link">intiRasa</a></li>
                        <li><a href="{{ route('products.index') }}?brand=haocafe" class="link">Hao Cafe</a></li>
                     </ul>
                  </section>

                  <section aria-label="section menu others" class="menu-others">
                     <h3 class="menu-title small opacity-50">Others</h3>
                     <hr class="opacity-50 my-2">
                     <ul class="menu-list nav d-grid column-gap-3 row-gap-1 row-gap-md-2">
                        <li class="d-md-none">@include('components.toggle-language')</li>
                        <li><a href="{{ route('privacy-policy') }}" class="link small">Privacy Policy</a></li>
                        <li><a href="{{ route('terms-conditions') }}" class="link small">Terms &amp; Conditions</a></li>
                        <li><a href="{{ route('data-protection') }}" class="link small">Information On Data Protection</a></li>
                        <li><a href="{{ route('help') }}" class="link small">Help</a></li>
                     </ul>
                  </section>

               </div>
            </div>

         </div>
      </div>

   </div>
</nav>
