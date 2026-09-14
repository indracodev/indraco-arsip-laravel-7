<footer class="site-footer text-bg-custom-1" style="font-size: .8em; margin-top: calc(5rem + 2vw);">
   <div class="site-footer-top">
      <div class="container py-5">
         <div class="row gy-5 gx-sm-5">

            <!-- 1. Main Section (Logo, Tagline, Sosmed) -->
            <section aria-label="footer main section" class="col-12 col-xl main-footer-section d-xl-flex flex-column">
               @php
                  $customFooterLogo = \App\Models\MasterSetting::get('footer_logo');
               @endphp
               @if($customFooterLogo && file_exists(public_path($customFooterLogo)))
                  <img src="{{ asset($customFooterLogo) }}" alt="Logo INDRACO" loading="lazy" class="w-100 h-auto mb-3" style="max-height: 60px; object-fit: contain;">
               @else
                  <img src="{{ asset('images/logo-indraco-invert.png') }}" alt="Logo INDRACO" loading="lazy" class="w-100 h-auto">
               @endif
               <h3 class="footer-title mb-3 mb-xl-auto">Roasting fine exquisite coffee since 1971.</h3>
               @include('components.sosmed')
            </section>

            <!-- 2. Company Section -->
            <section aria-label="footer company section" class="col-4 col-md-auto">
               <h3 class="footer-title mb-0">Company</h3>
               <hr class="opacity-75">
               <nav aria-label="footer company navigation">
                  <ul class="nav flex-column row-gap-1">
                     <li><a href="{{ route('home') }}" class="link">Home</a></li>
                     <li><a href="{{ route('about') }}" class="link">About Us</a></li>
                     <li><a href="{{ route('products.index') }}" class="link">Products</a></li>
                     <li><a href="{{ route('businesses') }}" class="link">Business</a></li>
                     <li><a href="{{ route('store') }}" class="link">Online Store</a></li>
                     <li><a href="{{ route('news') }}" class="link">News</a></li>
                     <li><a href="{{ route('csr') }}" class="link">CSR</a></li>
                     <li><a href="{{ route('careers') }}" class="link">Careers</a></li>
                  </ul>
               </nav>
            </section>

            <!-- 3. Brands Section -->
            <section aria-label="footer brands section" class="col-4 col-md-auto">
               <h3 class="footer-title mb-0">Brands</h3>
               <hr class="opacity-75">
               <nav aria-label="footer brands navigation">
                  <ul class="nav flex-column row-gap-1 footer-brands-list">
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
               </nav>
            </section>

            <!-- 4. Support Section -->
            <section aria-label="footer support section" class="col-4 col-md-auto">
               <h3 class="footer-title mb-0">Support</h3>
               <hr class="opacity-75">
               <nav aria-label="footer support navigation">
                  <ul class="nav flex-column row-gap-1">
                     <li><a href="{{ route('contact') }}" class="link">Contact Us</a></li>
                     <li><a href="{{ route('downloads') }}" class="link">Downloads</a></li>
                  </ul>
               </nav>
            </section>
            
            <!-- 5. Subscribe Newsletter Section -->
            <section aria-label="footer subscribe section" class="col-12 col-xl newsletter-footer-section d-xl-flex flex-column">
               <h3 class="footer-title mb-0">Newsletter</h3>
               <hr class="opacity-75">
               <form action="#" class="flex-grow-1 d-xl-flex flex-column" novalidate>
                  <label for="newsletter-email" class="form-label mb-xl-auto">Subscribe to get the latest updates from Indraco</label>
                  <div class="input-group text-bg-light p-1 rounded-pill overflow-hidden">
                     <input type="email" id="newsletter-email" class="form-control text-bg-light border-0" placeholder="Enter your email address" autocomplete="email" required>
                     <button type="submit" class="btn btn-custom-2 rounded-pill" aria-label="Subscribe to newsletter">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="21" height="21"><path fill="currentColor" d="M566.6 342.6C579.1 330.1 579.1 309.8 566.6 297.3L406.6 137.3C394.1 124.8 373.8 124.8 361.3 137.3C348.8 149.8 348.8 170.1 361.3 182.6L466.7 288L96 288C78.3 288 64 302.3 64 320C64 337.7 78.3 352 96 352L466.7 352L361.3 457.4C348.8 469.9 348.8 490.2 361.3 502.7C373.8 515.2 394.1 515.2 406.6 502.7L566.6 342.7z"/></svg>
                     </button>
                  </div>
               </form>
            </section>

         </div>
      </div>
   </div>
   <hr class="m-0">
   <div class="site-footer-bottom">
      <div class="container pt-3 pb-4 d-flex flex-wrap row-gap-3 column-gap-5">

         <nav aria-label="footer legal navigation" class="flex-grow-1">
            <ul class="nav gap-2">
               <li><a href="{{ route('privacy-policy') }}" class="link">Privacy Policy</a></li>
               <li class="vr"></li>
               <li><a href="{{ route('terms-conditions') }}" class="link">Terms &amp; Conditions</a></li>
               <li class="vr"></li>
               <li><a href="{{ route('data-protection') }}" class="link">Information On Data Protection</a></li>
               <li class="vr"></li>
               <li><a href="{{ route('help') }}" class="link">Help</a></li>
               <li class="vr"></li>
               <li><a href="{{ route('admin.login') }}" class="link">Admin Login</a></li>
            </ul>
         </nav>

         <div class="copyright opacity-50">&copy; {{ date('Y') }} INDRACO. All rights reserved </div>

      </div>
   </div>
</footer>
