<div id="modal-search" class="modal fade" tabindex="-1" aria-labelledby="modal-search-title" aria-modal="true" role="dialog">
   <div class="modal-dialog modal-fullscreen-lg-down modal-dialog-scrollable">
      <form action="{{ route('products.index') }}" method="GET" class="modal-content" role="search">
         <header class="modal-header border-0 d-block pb-1">

            <div class="input-group mb-4">
               <label for="search" class="btn border border-end-0 border-2 border-invert" aria-label="Search products">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="24" height="24"><path fill="currentColor" d="M480 272C480 317.9 465.1 360.3 440 394.7L566.6 521.4C579.1 533.9 579.1 554.2 566.6 566.7C554.1 579.2 533.8 579.2 521.3 566.7L394.7 440C360.3 465.1 317.9 480 272 480C157.1 480 64 386.9 64 272C64 157.1 157.1 64 272 64C386.9 64 480 157.1 480 272zM272 416C351.5 416 416 351.5 416 272C416 192.5 351.5 128 272 128C192.5 128 128 192.5 128 272C128 351.5 192.5 416 272 416z"/></svg>
               </label>
               <input type="search" name="search" id="search" class="form-control border border-start-0 border-end-0 border-2 border-invert shadow-none ps-0" placeholder="Search Product" aria-label="Search products" value="{{ request('search') }}">
               <button type="submit" class="btn btn-invert border-0">Search</button>
            </div>

            <div class="mb-4">
               <div class="d-flex align-items-center justify-content-between">
                  <h2 id="modal-search-title" class="small mb-0 opacity-50">Popular Keywords</h2>
               </div>
               <nav aria-label="recent search">
                  <ul class="nav gap-1">
                     <li><a href="{{ route('products.index') }}?search=Sumatra" class="btn btn-sm btn-outline-invert">Sumatra Mandheling</a></li>
                     <li><a href="{{ route('products.index') }}?search=Coffee" class="btn btn-sm btn-outline-invert">Coffee</a></li>
                     <li><a href="{{ route('products.index') }}?search=Capsules" class="btn btn-sm btn-outline-invert">Coffee Capsules</a></li>
                     <li><a href="{{ route('products.index') }}?search=Jahe" class="btn btn-sm btn-outline-invert">Jaheku</a></li>
                  </ul>
               </nav>
            </div>
            
         </header>
         <footer class="modal-footer border-0">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
         </footer>
      </form>
   </div>
</div>
