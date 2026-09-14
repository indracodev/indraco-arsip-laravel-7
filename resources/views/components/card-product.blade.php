@props(['produk'])

<article class="card card-product border-0 h-100 shadow-sm rounded-4">
   <div class="card-header ratio ratio-1x1 rounded-top-4 border-0 overflow-hidden bg-body-tertiary position-relative">
      @if(isset($produk->foto_path) && $produk->foto_path)
         <img src="{{ asset($produk->foto_path) }}" alt="{{ $produk->nama_produk }}" class="object-fit-contain top-50 start-50 translate-middle w-100 h-100 p-3">
      @elseif(isset($produk->gambar_utama) && $produk->gambar_utama)
         <img src="{{ asset($produk->gambar_utama) }}" alt="{{ $produk->nama_produk }}" class="object-fit-contain top-50 start-50 translate-middle w-100 h-100 p-3">
      @else
         <img src="{{ asset('images/ex-product-1.png') }}" alt="{{ $produk->nama_produk ?? 'Produk' }}" class="object-fit-contain top-50 start-50 translate-middle w-100 h-100 p-3">
      @endif
   </div>
   <div class="card-body text-center p-3 d-flex flex-column justify-content-between">
      <div>
         <h3 class="card-title fs-6 fw-semibold text-custom-1 mb-1">{{ $produk->nama_produk ?? 'Nama Produk' }}</h3>
         <p class="card-text small text-secondary mb-0">
            {{ $produk->variant->variant_name ?? $produk->sku ?? '' }}
            @if(isset($produk->harga_reguler) && $produk->harga_reguler > 0)
               <br><strong class="text-custom-2">Rp {{ number_format($produk->harga_reguler, 0, ',', '.') }}</strong>
            @endif
         </p>
      </div>
   </div>
   <a href="{{ route('products.detail', $produk->slug ?? '#') }}" class="stretched-link"><span class="visually-hidden">see detail product</span></a>
</article>
