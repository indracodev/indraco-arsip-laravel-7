@extends('layouts.app')

@section('title', 'INDRACO – Master Produk')

@push('styles')
   <style>
      .table-custom th {
         font-size: 0.78rem;
         text-transform: uppercase;
         letter-spacing: 0.05em;
         font-weight: 600;
         opacity: 0.75;
      }
      .admin-sidebar .nav-link {
         color: var(--bs-body-color);
         border-radius: 12px;
         padding: 10px 14px;
         font-weight: 500;
         font-size: 0.88rem;
         display: flex;
         align-items: center;
         justify-content: space-between;
         transition: all 0.2s ease;
         margin-bottom: 3px;
         text-decoration: none;
      }
      .admin-sidebar .nav-link:hover {
         background-color: rgba(var(--custom-primary-rgb), 0.1);
         color: var(--custom-primary);
      }
      .admin-sidebar .nav-link.active {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
      }
      .admin-sidebar-title {
         font-size: 0.72rem;
         text-transform: uppercase;
         letter-spacing: 0.08em;
         opacity: 0.6;
         font-weight: 700;
         margin-top: 1.2rem;
         margin-bottom: 0.4rem;
         padding-left: 8px;
      }
      .sticky-sidebar {
         position: sticky;
         top: 90px;
      }
      .mobile-data-card {
         background-color: var(--bs-body-bg);
         border-radius: 12px;
         padding: 16px;
         margin-bottom: 12px;
         box-shadow: 0 2px 8px rgba(0,0,0,0.04);
         border: 1px solid rgba(128,128,128,0.1);
      }
      .product-img-trigger:hover img {
         transform: scale(1.1);
         border-color: var(--custom-primary) !important;
      }

      /* Custom Select2 Styling for Theme Harmony */
      .select2-container--bootstrap-5 .select2-selection {
         border-radius: 0.75rem !important;
         background-color: var(--bs-body-tertiary) !important;
         border: 1px solid rgba(0,0,0,0.08) !important;
         min-height: 42px !important;
         display: flex !important;
         align-items: center !important;
         transition: all 0.2s ease !important;
      }
      .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
         color: var(--bs-body-color) !important;
         font-size: 0.88rem !important;
         padding-left: 12px !important;
         font-weight: 500 !important;
      }
      .select2-container--bootstrap-5 .select2-dropdown {
         border-radius: 0.75rem !important;
         border: 1px solid rgba(0,0,0,0.1) !important;
         box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
         overflow: hidden !important;
         z-index: 1065 !important;
         background-color: var(--bs-body-bg) !important;
      }
      .select2-container--bootstrap-5 .select2-search {
         padding: 8px !important;
      }
      .select2-container--bootstrap-5 .select2-search .select2-search__field {
         border-radius: 0.5rem !important;
         font-size: 0.85rem !important;
         padding: 6px 12px !important;
      }
      .select2-container--bootstrap-5 .select2-results__option {
         font-size: 0.88rem !important;
         padding: 8px 12px !important;
      }
      .select2-container--bootstrap-5 .select2-results__option--highlighted {
         background-color: #004b49 !important;
         color: #ffffff !important;
      }
   </style>
@endpush

@section('content')
<main id="content" tabindex="-1" class="container py-4 my-3">

   @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
         <strong>✅ Success:</strong> {{ session('success') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
   @endif

   <!-- Header Banner -->
   <section aria-label="Welcome Admin" class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm mb-4">
      <div class="row align-items-center gy-3">
         <div class="col-12 col-md">
            <span class="badge bg-danger text-white mb-2 px-3 py-2 rounded-pill">Administrator Access</span>
            <h1 class="h3 fw-bold mb-1">Master Data Produk</h1>
            <p class="text-secondary small mb-0">Kelola katalog produk, SKU, harga, dan tautan e-commerce INDRACO Coffee.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.produk.template') }}" class="btn btn-outline-secondary rounded-pill px-3">Download Template Excel</a>
            <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importProdukExcelModal">Import Excel</button>
            <button type="button" class="btn btn-custom-1 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahProduk">+ Tambah Produk Baru</button>
         </div>
      </div>
   </section>

   <!-- 2 Columns Layout -->
   <div class="row g-4">
      
      <!-- Sidebar Navigasi -->
      <x-admin-sidebar />

      <!-- Main Content -->
      <div class="col-12 col-lg-9">
         <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
            
            <!-- Header Toolbar & View Mode Switcher -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
               <div>
                  <h2 class="h5 fw-bold mb-0">Katalog Produk Master</h2>
                  <p class="text-secondary small mb-0">Menampilkan 20 Produk per halaman (Total {{ $produks->total() }} Produk).</p>
               </div>
               <div class="d-flex align-items-center gap-2">
                  <div class="btn-group" role="group" aria-label="Mode Tampilan">
                     <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" class="btn btn-sm {{ request('view', 'grid') == 'grid' ? 'btn-custom-1' : 'btn-outline-secondary' }} rounded-start-pill px-3 fw-semibold">
                        🎛️ Grid Mode (5 Kolom)
                     </a>
                     <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" class="btn btn-sm {{ request('view') == 'table' ? 'btn-custom-1' : 'btn-outline-secondary' }} rounded-end-pill px-3 fw-semibold">
                        📋 Table Mode
                     </a>
                  </div>
               </div>
            </div>

            <!-- Filter & Search Form -->
            <form action="{{ route('admin.produk.index') }}" method="GET" class="row g-2 mb-4">
               @if(request('view'))
                  <input type="hidden" name="view" value="{{ request('view') }}">
               @endif
               <div class="col-md-5">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari nama produk / SKU..." value="{{ request('search') }}">
               </div>
               <div class="col-md-3">
                  <select name="merek_id" class="form-select select-search rounded-pill px-3 bg-body-tertiary">
                     <option value="">Semua Merek</option>
                     @foreach($mereks as $merek)
                        <option value="{{ $merek->id }}" {{ request('merek_id') == $merek->id ? 'selected' : '' }}>{{ $merek->nama_merek }}</option>
                     @endforeach
                  </select>
               </div>
               <div class="col-md-2">
                  <select name="status" class="form-select select-search rounded-pill px-3 bg-body-tertiary">
                     <option value="">Semua Status</option>
                     <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                     <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                  </select>
               </div>
               <div class="col-md-2">
                  <button type="submit" class="btn btn-secondary rounded-pill w-100">Filter</button>
               </div>
            </form>

            @if(request('view', 'grid') == 'grid')
               <!-- DESKTOP GRID VIEW: 5 COLUMNS CARD LIST (d-none d-md-flex) -->
               <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mb-4 d-none d-md-flex">
                  @forelse($produks as $p)
                     <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative bg-body">
                           <div class="position-relative bg-body-tertiary text-center p-2 rounded-top-4 border-bottom">
                              <a href="#" data-bs-toggle="modal" data-bs-target="#modalFotoProduk{{ $p->id }}" title="Klik untuk lihat & ganti foto">
                                 @if($p->gambar_utama)
                                    <img src="{{ asset($p->gambar_utama) }}" alt="{{ $p->nama_produk }}" class="img-fluid object-fit-contain p-2" style="height: 130px; width: 100%;">
                                 @else
                                    <div class="d-flex align-items-center justify-content-center bg-body-secondary text-secondary rounded" style="height: 130px;">📷 No Photo</div>
                                 @endif
                              </a>
                              @if($p->is_unggulan)
                                 <span class="badge text-bg-warning position-absolute top-0 start-0 m-2" style="font-size: 0.65rem;">⭐</span>
                              @endif
                              <span class="badge {{ $p->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }} position-absolute top-0 end-0 m-2" style="font-size: 0.65rem;">
                                 {{ ucfirst($p->status) }}
                              </span>
                           </div>
                           <div class="card-body p-3 d-flex flex-column justify-content-between">
                              <div>
                                 <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                    <span class="badge text-bg-light border small text-truncate" style="max-width: 90px;">{{ $p->merek->nama_merek ?? 'Merek' }}</span>
                                    <small class="text-secondary fw-bold" style="font-size: 0.7rem;"><code>{{ $p->sku ?? '-' }}</code></small>
                                 </div>
                                 <h3 class="fs-6 fw-bold mb-1 text-truncate" title="{{ $p->nama_produk }}" style="font-size: 0.88rem;">{{ $p->nama_produk }}</h3>
                                 <p class="text-secondary small mb-2 text-truncate" style="font-size: 0.75rem;">{{ $p->kategori->nama_kategori ?? 'Kategori' }}</p>
                                 <div class="fw-bold text-custom-1 small mb-3">
                                    {{ $p->harga_reguler > 0 ? 'Rp ' . number_format($p->harga_reguler, 0, ',', '.') : '-' }}
                                 </div>
                              </div>
                              <div class="d-flex align-items-center justify-content-center gap-1 pt-2 border-top">
                                 <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalShowProduk{{ $p->id }}">👁️ View</button>
                                 <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalEditProduk{{ $p->id }}">✏️ Edit</button>
                                 <form action="{{ route('admin.produk.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-2 py-1 text-nowrap fw-semibold" style="font-size: 0.72rem;">🗑️</button>
                                 </form>
                              </div>
                           </div>
                        </div>
                     </div>
                  @empty
                     <div class="col-12 text-center py-4 text-muted">Data produk tidak ditemukan.</div>
                  @endforelse
               </div>
            @else
               <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
               <div class="table-responsive d-none d-md-block">
                  <table class="table table-hover table-custom align-middle mb-0">
                     <thead>
                        <tr>
                           <th style="width: 60px;">Foto</th>
                           <th>Nama Produk</th>
                           <th>Merek</th>
                           <th>SKU</th>
                           <th>Harga</th>
                           <th>Status</th>
                           <th class="text-end">Aksi CRUD</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($produks as $p)
                           <tr>
                              <td>
                                 <a href="#" data-bs-toggle="modal" data-bs-target="#modalFotoProduk{{ $p->id }}" title="Klik untuk lihat & ganti foto" class="d-inline-block product-img-trigger">
                                    @if($p->gambar_utama)
                                       <img src="{{ asset($p->gambar_utama) }}" alt="{{ $p->nama_produk }}" class="rounded shadow-sm border" style="width: 48px; height: 48px; object-fit: contain;">
                                    @else
                                       <span class="badge text-bg-light border p-2" style="cursor: pointer;">📷 Foto</span>
                                    @endif
                                 </a>
                              </td>
                              <td class="fw-bold small">
                                 {{ $p->nama_produk }}
                                 @if($p->is_unggulan)
                                    <span class="badge text-bg-warning ms-1" style="font-size: 0.65rem;">⭐ Unggulan</span>
                                 @endif
                              </td>
                              <td class="small"><span class="badge text-bg-light">{{ $p->merek->nama_merek ?? '-' }}</span></td>
                              <td class="small"><code>{{ $p->sku ?? '-' }}</code></td>
                              <td class="fw-semibold small">
                                 {{ $p->harga_reguler > 0 ? 'Rp ' . number_format($p->harga_reguler, 0, ',', '.') : '-' }}
                              </td>
                              <td>
                                 <span class="badge {{ $p->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ ucfirst($p->status) }}
                                 </span>
                              </td>
                              <td class="text-end">
                                 <div class="d-flex align-items-center justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowProduk{{ $p->id }}">👁️ View</button>
                                    <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditProduk{{ $p->id }}">✏️ Edit</button>
                                    <form action="{{ route('admin.produk.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                                       @csrf
                                       @method('DELETE')
                                       <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                    </form>
                                 </div>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="7" class="text-center py-4 text-muted">Data produk tidak ditemukan.</td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
            @endif

            <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
            <div class="d-block d-md-none">
               @forelse($produks as $p)
                  <div class="mobile-data-card">
                     <div class="d-flex align-items-center gap-3 mb-2">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modalFotoProduk{{ $p->id }}" title="Klik untuk lihat & ganti foto" class="flex-shrink-0">
                           @if($p->gambar_utama)
                              <img src="{{ asset($p->gambar_utama) }}" alt="{{ $p->nama_produk }}" class="rounded shadow-sm border" style="width: 55px; height: 55px; object-fit: contain;">
                           @else
                              <span class="badge text-bg-light border p-2" style="cursor: pointer;">📷 Foto</span>
                           @endif
                        </a>
                        <div class="flex-grow-1">
                           <h3 class="fs-6 fw-bold mb-0">{{ $p->nama_produk }}</h3>
                           <small class="text-secondary d-block">SKU: {{ $p->sku ?? '-' }}</small>
                           <small class="fw-semibold text-custom-1">
                              {{ $p->harga_reguler > 0 ? 'Rp ' . number_format($p->harga_reguler, 0, ',', '.') : '-' }}
                           </small>
                        </div>
                        <span class="badge {{ $p->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                           {{ ucfirst($p->status) }}
                        </span>
                     </div>
                     <div class="d-flex gap-2 mt-2 pt-2 border-top justify-content-end">
                        <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowProduk{{ $p->id }}">👁️ View</button>
                        <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditProduk{{ $p->id }}">✏️ Edit</button>
                        <form action="{{ route('admin.produk.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk?')">
                           @csrf
                           @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                        </form>
                     </div>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Data produk tidak ditemukan.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $produks->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>

{{-- MODAL TAMBAH PRODUK BARU --}}
<div class="modal fade" id="modalTambahProduk" tabindex="-1" aria-labelledby="modalTambahProdukLabel" aria-hidden="true">
   <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <form action="{{ route('admin.produk.store') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalTambahProdukLabel">📦 Tambah Produk Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-3">
               <!-- Left Column: Informasi Utama & Taksonomi -->
               <div class="col-md-7">
                  <h6 class="fw-bold mb-3 text-custom-1 border-bottom pb-2">Informasi Produk & Klasifikasi</h6>
                  
                  <div class="mb-3">
                     <label for="nama_produk_new" class="form-label small fw-medium">Nama Produk <span class="text-danger">*</span></label>
                     <input type="text" name="nama_produk" id="nama_produk_new" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Supresso Sumatra Mandheling Beans 200g" required>
                  </div>

                  <div class="row g-2 mb-3">
                     <div class="col-md-6">
                        <label for="sku_new" class="form-label small fw-medium">SKU Produk</label>
                        <input type="text" name="sku" id="sku_new" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: SUP-MAN-200G">
                     </div>
                     <div class="col-md-6">
                        <label for="harga_reguler_new" class="form-label small fw-medium">Harga Reguler (Rp)</label>
                        <input type="number" name="harga_reguler" id="harga_reguler_new" class="form-control rounded-3 bg-body-tertiary" placeholder="85000">
                     </div>
                  </div>

                  <div class="row g-2 mb-3">
                     <div class="col-md-6">
                        <label for="merek_id_new" class="form-label small fw-medium">Merek / Brand <span class="text-danger">*</span></label>
                        <select name="merek_id" id="merek_id_new" class="form-select select-search rounded-3 bg-body-tertiary" required>
                           <option value="">Pilih Merek</option>
                           @foreach($mereks as $m)
                              <option value="{{ $m->id }}">{{ $m->nama_merek }}</option>
                           @endforeach
                        </select>
                     </div>
                     <div class="col-md-6">
                        <label for="kategori_id_new" class="form-label small fw-medium">Kategori</label>
                        <select name="kategori_id" id="kategori_id_new" class="form-select select-search rounded-3 bg-body-tertiary">
                           <option value="">Pilih Kategori</option>
                           @foreach($kategories as $k)
                              <option value="{{ $k->id }}">{{ $k->nama_kategori }}</option>
                           @endforeach
                        </select>
                     </div>
                  </div>

                  <div class="row g-2 mb-3">
                     <div class="col-md-4">
                        <label for="collection_id_new" class="form-label small fw-medium">Collection</label>
                        <select name="collection_id" id="collection_id_new" class="form-select select-search rounded-3 bg-body-tertiary">
                           <option value="">Pilih Collection</option>
                           @foreach($collections as $col)
                              <option value="{{ $col->id }}">{{ $col->collection_name ?? $col->nama_collection }}</option>
                           @endforeach
                        </select>
                     </div>
                     <div class="col-md-4">
                        <label for="type_id_new" class="form-label small fw-medium">Type</label>
                        <select name="type_id" id="type_id_new" class="form-select select-search rounded-3 bg-body-tertiary">
                           <option value="">Pilih Type</option>
                           @foreach($types as $t)
                              <option value="{{ $t->id }}">{{ $t->type_name ?? $t->nama_type }}</option>
                           @endforeach
                        </select>
                     </div>
                     <div class="col-md-4">
                        <label for="variant_id_new" class="form-label small fw-medium">Variant</label>
                        <select name="variant_id" id="variant_id_new" class="form-select select-search rounded-3 bg-body-tertiary">
                           <option value="">Pilih Variant</option>
                           @foreach($variants as $v)
                              <option value="{{ $v->id }}">{{ $v->variant_name ?? $v->nama_variant }}</option>
                           @endforeach
                        </select>
                     </div>
                  </div>

                  <div class="row g-2 mb-3">
                     <div class="col-md-6">
                        <label for="tipe_packing_new" class="form-label small fw-medium">Tipe Packing</label>
                        <input type="text" name="tipe_packing" id="tipe_packing_new" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Pouch / Can / Box">
                     </div>
                     <div class="col-md-6">
                        <label for="inner_kemasan_new" class="form-label small fw-medium">Inner Kemasan</label>
                        <input type="text" name="inner_kemasan" id="inner_kemasan_new" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: 200g / 10 Sachet x 25g">
                     </div>
                  </div>

                  <div class="mb-3">
                     <label for="deskripsi_singkat_new" class="form-label small fw-medium">Deskripsi Singkat</label>
                     <textarea name="deskripsi_singkat" id="deskripsi_singkat_new" rows="2" class="form-control rounded-3 bg-body-tertiary" placeholder="Ringkasan aroma, rasa, atau keunggulan..."></textarea>
                  </div>

                  <div class="mb-3">
                     <label for="deskripsi_lengkap_new" class="form-label small fw-medium">Deskripsi Lengkap</label>
                     <textarea name="deskripsi_lengkap" id="deskripsi_lengkap_new" rows="3" class="form-control rounded-3 bg-body-tertiary" placeholder="Penjelasan rincian produk..."></textarea>
                  </div>
               </div>

               <!-- Right Column: Media, Marketplace Links & Status -->
               <div class="col-md-5">
                  <h6 class="fw-bold mb-3 text-custom-1 border-bottom pb-2">Media & E-Commerce Links</h6>
                  
                  <div class="mb-3">
                     <label for="gambar_utama_new" class="form-label small fw-medium">Upload Foto Utama Produk</label>
                     <input type="file" name="gambar_utama" id="gambar_utama_new" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                     <div class="form-text">Format JPG/PNG/WebP, max 4MB.</div>
                  </div>

                  <div class="mb-3">
                     <label for="status_new" class="form-label small fw-medium">Status Produk</label>
                     <select name="status" id="status_new" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="active" selected>Active (Tampil di Website)</option>
                        <option value="inactive">Inactive (Disembunyikan)</option>
                     </select>
                  </div>

                  <div class="mb-3 p-3 rounded-3 bg-body-tertiary border">
                     <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_unggulan" value="1" id="is_unggulan_new" style="cursor: pointer;">
                        <label class="form-check-label small fw-bold" for="is_unggulan_new">⭐ Produk Unggulan (Landing Page)</label>
                     </div>
                  </div>

                  <h6 class="fw-bold mt-4 mb-2 small text-secondary">Tautan Toko Marketplace</h6>
                  
                  <div class="mb-2">
                     <label for="link_shopee_new" class="form-label small">🧡 Shopee Link</label>
                     <input type="text" name="link_shopee" id="link_shopee_new" class="form-control form-control-sm rounded-3 bg-body-tertiary" placeholder="/products atau https://shopee.co.id/...">
                  </div>

                  <div class="mb-2">
                     <label for="link_tokopedia_new" class="form-label small">💚 Tokopedia Link</label>
                     <input type="text" name="link_tokopedia" id="link_tokopedia_new" class="form-control form-control-sm rounded-3 bg-body-tertiary" placeholder="/products atau https://tokopedia.com/...">
                  </div>

                  <div class="mb-2">
                     <label for="link_lazada_new" class="form-label small">💙 Lazada Link</label>
                     <input type="text" name="link_lazada" id="link_lazada_new" class="form-control form-control-sm rounded-3 bg-body-tertiary" placeholder="https://lazada.co.id/...">
                  </div>

                  <div class="mb-3">
                     <label for="link_tiktok_new" class="form-label small">🖤 TikTok Shop Link</label>
                     <input type="text" name="link_tiktok" id="link_tiktok_new" class="form-control form-control-sm rounded-3 bg-body-tertiary" placeholder="https://tiktok.com/...">
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">💾 Simpan Produk</button>
         </div>
      </form>
   </div>
</div>

{{-- MODAL EDIT, DETAIL & FOTO PRODUK (1 Modal Per Data Produk) --}}
@foreach($produks as $p)

{{-- MODAL DETAIL PRODUK --}}
<div class="modal fade" id="modalShowProduk{{ $p->id }}" tabindex="-1" aria-labelledby="modalShowProdukLabel{{ $p->id }}" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-custom-1" id="modalShowProdukLabel{{ $p->id }}">👁️ Detail Produk</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-4 align-items-center">
               <div class="col-md-4 text-center">
                  @if($p->gambar_utama)
                     <img src="{{ asset($p->gambar_utama) }}" alt="{{ $p->nama_produk }}" class="img-fluid rounded-4 shadow-sm border p-2" style="max-height: 200px; object-fit: contain;">
                  @else
                     <div class="bg-body-tertiary rounded-4 p-4 text-secondary border">No Image Available</div>
                  @endif
               </div>
               <div class="col-md-8">
                  <h4 class="fw-bold mb-2">{{ $p->nama_produk }}</h4>
                  <p class="text-secondary small mb-3">SKU: <code>{{ $p->sku ?? '-' }}</code> | Slug: <code>{{ $p->slug }}</code></p>
                  
                  <div class="row g-2 mb-3 small bg-body-tertiary p-3 rounded-3">
                     <div class="col-6"><strong>Merek:</strong> {{ $p->merek->nama_merek ?? '-' }}</div>
                     <div class="col-6"><strong>Kategori:</strong> {{ $p->kategori->nama_kategori ?? '-' }}</div>
                     <div class="col-6"><strong>Collection:</strong> {{ $p->collection->collection_name ?? '-' }}</div>
                     <div class="col-6"><strong>Type:</strong> {{ $p->type->type_name ?? '-' }}</div>
                     <div class="col-6"><strong>Variant:</strong> {{ $p->variant->variant_name ?? '-' }}</div>
                     <div class="col-6"><strong>Status:</strong> <span class="badge {{ $p->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($p->status) }}</span></div>
                     <div class="col-12 mt-2">
                        <strong>Harga Reguler:</strong> <span class="fw-bold text-custom-1 fs-6">{{ $p->harga_reguler > 0 ? 'Rp ' . number_format($p->harga_reguler, 0, ',', '.') : '-' }}</span>
                     </div>
                  </div>

                  @if($p->deskripsi_singkat_ind)
                     <div class="border-top pt-2 mt-2">
                        <small class="text-muted d-block fw-bold mb-1">Deskripsi Singkat (ID):</small>
                        <p class="small text-secondary m-0">{{ $p->deskripsi_singkat_ind }}</p>
                     </div>
                  @endif
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            <a href="{{ route('products.detail', $p->slug) }}" target="_blank" class="btn btn-info text-white rounded-pill px-4">Buka Halaman Publik ↗</a>
         </div>
      </div>
   </div>
</div>

{{-- MODAL EDIT PRODUK --}}
<div class="modal fade" id="modalEditProduk{{ $p->id }}" tabindex="-1" aria-labelledby="modalEditProdukLabel{{ $p->id }}" aria-hidden="true">
   <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <form action="{{ route('admin.produk.update', $p->id) }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         @method('PUT')
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalEditProdukLabel{{ $p->id }}">✏️ Edit Produk: {{ $p->nama_produk }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-3">
               <!-- Left Column: Informasi Utama & Taksonomi -->
               <div class="col-md-7">
                  <h6 class="fw-bold mb-3 text-custom-1 border-bottom pb-2">Informasi Produk & Klasifikasi</h6>
                  
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Nama Produk <span class="text-danger">*</span></label>
                     <input type="text" name="nama_produk" class="form-control rounded-3 bg-body-tertiary" value="{{ $p->nama_produk }}" required>
                  </div>

                  <div class="row g-2 mb-3">
                     <div class="col-md-6">
                        <label class="form-label small fw-medium">SKU Produk</label>
                        <input type="text" name="sku" class="form-control rounded-3 bg-body-tertiary" value="{{ $p->sku }}">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label small fw-medium">Harga Reguler (Rp)</label>
                        <input type="number" name="harga_reguler" class="form-control rounded-3 bg-body-tertiary" value="{{ $p->harga_reguler > 0 ? (int)$p->harga_reguler : '' }}">
                     </div>
                  </div>

                  <div class="row g-2 mb-3">
                     <div class="col-md-6">
                        <label class="form-label small fw-medium">Merek / Brand <span class="text-danger">*</span></label>
                        <select name="merek_id" class="form-select select-search rounded-3 bg-body-tertiary" required>
                           <option value="">Pilih Merek</option>
                           @foreach($mereks as $m)
                              <option value="{{ $m->id }}" {{ $p->merek_id == $m->id ? 'selected' : '' }}>{{ $m->nama_merek }}</option>
                           @endforeach
                        </select>
                     </div>
                     <div class="col-md-6">
                        <label class="form-label small fw-medium">Kategori</label>
                        <select name="kategori_id" class="form-select select-search rounded-3 bg-body-tertiary">
                           <option value="">Pilih Kategori</option>
                           @foreach($kategories as $k)
                              <option value="{{ $k->id }}" {{ $p->kategori_id == $k->id ? 'selected' : '' }}>{{ $k->nama_kategori }}</option>
                           @endforeach
                        </select>
                     </div>
                  </div>

                  <div class="row g-2 mb-3">
                     <div class="col-md-4">
                        <label class="form-label small fw-medium">Collection</label>
                        <select name="collection_id" class="form-select select-search rounded-3 bg-body-tertiary">
                           <option value="">Pilih Collection</option>
                           @foreach($collections as $col)
                              <option value="{{ $col->id }}" {{ $p->collection_id == $col->id ? 'selected' : '' }}>{{ $col->collection_name ?? $col->nama_collection }}</option>
                           @endforeach
                        </select>
                     </div>
                     <div class="col-md-4">
                        <label class="form-label small fw-medium">Type</label>
                        <select name="type_id" class="form-select select-search rounded-3 bg-body-tertiary">
                           <option value="">Pilih Type</option>
                           @foreach($types as $t)
                              <option value="{{ $t->id }}" {{ $p->type_id == $t->id ? 'selected' : '' }}>{{ $t->type_name ?? $t->nama_type }}</option>
                           @endforeach
                        </select>
                     </div>
                     <div class="col-md-4">
                        <label class="form-label small fw-medium">Variant</label>
                        <select name="variant_id" class="form-select select-search rounded-3 bg-body-tertiary">
                           <option value="">Pilih Variant</option>
                           @foreach($variants as $v)
                              <option value="{{ $v->id }}" {{ $p->variant_id == $v->id ? 'selected' : '' }}>{{ $v->variant_name ?? $v->nama_variant }}</option>
                           @endforeach
                        </select>
                     </div>
                  </div>

                  <div class="row g-2 mb-3">
                     <div class="col-md-6">
                        <label class="form-label small fw-medium">Tipe Packing</label>
                        <input type="text" name="tipe_packing" class="form-control rounded-3 bg-body-tertiary" value="{{ $p->tipe_packing }}">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label small fw-medium">Inner Kemasan</label>
                        <input type="text" name="inner_kemasan" class="form-control rounded-3 bg-body-tertiary" value="{{ $p->inner_kemasan }}">
                     </div>
                  </div>

                  <div class="mb-3">
                     <label class="form-label small fw-medium">Deskripsi Singkat</label>
                     <textarea name="deskripsi_singkat" rows="2" class="form-control rounded-3 bg-body-tertiary">{{ $p->deskripsi_singkat }}</textarea>
                  </div>

                  <div class="mb-3">
                     <label class="form-label small fw-medium">Deskripsi Lengkap</label>
                     <textarea name="deskripsi_lengkap" rows="3" class="form-control rounded-3 bg-body-tertiary">{{ $p->deskripsi_lengkap }}</textarea>
                  </div>
               </div>

               <!-- Right Column: Media, Marketplace Links & Status -->
               <div class="col-md-5">
                  <h6 class="fw-bold mb-3 text-custom-1 border-bottom pb-2">Media & E-Commerce Links</h6>
                  
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Ganti Foto Utama Produk (opsional)</label>
                     <input type="file" name="gambar_utama" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                     @if($p->gambar_utama)
                        <div class="mt-2 text-center p-2 border rounded bg-white">
                           <img src="{{ asset($p->gambar_utama) }}" alt="" style="height: 90px; object-fit: contain;">
                           <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">Foto Utama Saat Ini</small>
                        </div>
                     @endif
                  </div>

                  <div class="mb-3">
                     <label class="form-label small fw-medium">Status Produk</label>
                     <select name="status" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="active" {{ $p->status == 'active' ? 'selected' : '' }}>Active (Tampil di Website)</option>
                        <option value="inactive" {{ $p->status == 'inactive' ? 'selected' : '' }}>Inactive (Disembunyikan)</option>
                     </select>
                  </div>

                  <div class="mb-3 p-3 rounded-3 bg-body-tertiary border">
                     <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_unggulan" value="1" id="is_unggulan_{{ $p->id }}" {{ $p->is_unggulan ? 'checked' : '' }} style="cursor: pointer;">
                        <label class="form-check-label small fw-bold" for="is_unggulan_{{ $p->id }}">⭐ Produk Unggulan (Landing Page)</label>
                     </div>
                  </div>

                  <h6 class="fw-bold mt-4 mb-2 small text-secondary">Tautan Toko Marketplace</h6>
                  
                  <div class="mb-2">
                     <label class="form-label small">🧡 Shopee Link</label>
                     <input type="text" name="link_shopee" class="form-control form-control-sm rounded-3 bg-body-tertiary" value="{{ $p->link_shopee }}" placeholder="https://shopee.co.id/...">
                  </div>

                  <div class="mb-2">
                     <label class="form-label small">💚 Tokopedia Link</label>
                     <input type="text" name="link_tokopedia" class="form-control form-control-sm rounded-3 bg-body-tertiary" value="{{ $p->link_tokopedia }}" placeholder="https://tokopedia.com/...">
                  </div>

                  <div class="mb-2">
                     <label class="form-label small">💙 Lazada Link</label>
                     <input type="text" name="link_lazada" class="form-control form-control-sm rounded-3 bg-body-tertiary" value="{{ $p->link_lazada }}" placeholder="https://lazada.co.id/...">
                  </div>

                  <div class="mb-3">
                     <label class="form-label small">🖤 TikTok Shop Link</label>
                     <input type="text" name="link_tiktok" class="form-control form-control-sm rounded-3 bg-body-tertiary" value="{{ $p->link_tiktok }}" placeholder="https://tiktok.com/...">
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">💾 Simpan Perubahan</button>
         </div>
      </form>
   </div>
</div>

{{-- MODAL PREVIEW & UPLOAD FOTO PRODUK --}}
<div class="modal fade" id="modalFotoProduk{{ $p->id }}" tabindex="-1" aria-labelledby="modalFotoProdukLabel{{ $p->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('admin.produk.update-foto', $p->id) }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalFotoProdukLabel{{ $p->id }}">📷 Foto Produk: {{ $p->nama_produk }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body text-center p-4">
            <div class="p-3 bg-white rounded-4 border shadow-sm mb-3 position-relative d-inline-block w-100" style="max-height: 320px; overflow: hidden;">
               @if($p->gambar_utama)
                  <img src="{{ asset($p->gambar_utama) }}" alt="{{ $p->nama_produk }}" class="img-fluid rounded" style="max-height: 280px; object-fit: contain;">
               @else
                  <div class="py-5 text-muted">
                     <div style="font-size: 3rem;">📷</div>
                     <p class="mb-0 small">Belum ada foto utama untuk produk ini</p>
                  </div>
               @endif
            </div>

            <div class="text-start mb-3 bg-body-tertiary p-3 rounded-3 border">
               <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong class="small">{{ $p->nama_produk }}</strong>
                  <span class="badge {{ $p->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($p->status) }}</span>
               </div>
               <small class="text-secondary d-block">SKU: <code>{{ $p->sku ?? '-' }}</code> | Merek: <strong>{{ $p->merek->nama_merek ?? '-' }}</strong></small>
            </div>

            <div class="text-start">
               <label for="gambar_utama_modal_{{ $p->id }}" class="form-label small fw-bold text-custom-1">Upload Foto Baru (JPG, PNG, WebP)</label>
               <input type="file" name="gambar_utama" id="gambar_utama_modal_{{ $p->id }}" class="form-control rounded-3 bg-body-tertiary" accept="image/*" required>
               <div class="form-text" style="font-size: 0.75rem;">File max 4MB. Foto baru akan menggantikan foto utama saat ini.</div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">📸 Upload & Ganti Foto</button>
         </div>
      </form>
   </div>
</div>

@endforeach

{{-- Modal Import Excel Produk --}}
<div class="modal fade" id="importProdukExcelModal" tabindex="-1" aria-labelledby="importProdukExcelModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <form action="{{ route('admin.produk.import') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="importProdukExcelModalLabel">Import Katalog Produk Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <p class="small text-secondary mb-3">Unggah file <code>.xlsx</code> atau <code>.csv</code> produk sesuai format template Excel Indraco.</p>
            <div class="mb-3">
               <label for="excel_file" class="form-label fw-bold small">File Excel</label>
               <input type="file" name="excel_file" id="excel_file" class="form-control rounded-pill bg-body-tertiary" required accept=".xlsx,.xls,.csv">
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Proses Import</button>
         </div>
      </form>
   </div>
</div>
@endsection

@push('scripts')
   <script>
      function initSelect2(context) {
         if (typeof $.fn.select2 === 'undefined') return;
         
         var $scope = context ? $(context) : $(document);
         $scope.find('.select-search').each(function() {
            var $el = $(this);
            var $modal = $el.closest('.modal');
            
            if ($el.hasClass('select2-hidden-accessible')) {
               $el.select2('destroy');
            }

            $el.select2({
               theme: 'bootstrap-5',
               width: '100%',
               dropdownParent: $modal.length ? $modal : $(document.body),
               placeholder: $el.find('option[value=""]').text() || 'Pilih...',
               allowClear: true
            });
         });
      }

      $(document).ready(function() {
         initSelect2();

         // Re-initialize Select2 when modal opens for crisp rendering inside Bootstrap modal
         $('.modal').on('shown.bs.modal', function() {
            initSelect2(this);
         });
      });
   </script>
@endpush
