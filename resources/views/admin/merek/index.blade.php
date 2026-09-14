@extends('layouts.app')

@section('title', 'INDRACO – Master Merek (Brand)')

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
      .merek-logo-trigger:hover img {
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
            <h1 class="h3 fw-bold mb-1">Master Data Merek / Brands</h1>
            <p class="text-secondary small mb-0">Kelola daftar brand resmi portofolio INDRACO Coffee.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importExcelModal">Import Excel</button>
            <button type="button" class="btn btn-custom-1 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahMerek">+ Tambah Merek Baru</button>
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
                  <h2 class="h5 fw-bold mb-0">Master Data Merek / Brands</h2>
                  <p class="text-secondary small mb-0">Menampilkan 20 Merek per halaman (Total {{ $mereks->total() }} Merek).</p>
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
                  <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahMerek">+ Tambah Merek</button>
               </div>
            </div>

            <!-- Filter & Search Bar -->
            <form action="{{ route('admin.merek.index') }}" method="GET" class="row g-2 mb-4">
               @if(request('view'))
                  <input type="hidden" name="view" value="{{ request('view') }}">
               @endif
               <div class="col-md-6">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari nama merek..." value="{{ request('search') }}">
               </div>
               <div class="col-md-4">
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
                  @forelse($mereks as $merek)
                     <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative bg-body text-center">
                           <div class="position-relative bg-body-tertiary text-center p-3 rounded-top-4 border-bottom d-flex align-items-center justify-content-center" style="height: 120px;">
                              <a href="#" data-bs-toggle="modal" data-bs-target="#modalLogoMerek{{ $merek->id }}" title="Klik untuk lihat & ganti logo">
                                 @if($merek->logo_path)
                                    <img src="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" class="img-fluid object-fit-contain p-1" style="max-height: 80px; max-width: 100%;">
                                 @else
                                    <div class="d-flex align-items-center justify-content-center bg-body-secondary text-secondary rounded p-2" style="height: 80px; width: 100px;">🖼️ Logo</div>
                                 @endif
                              </a>
                              <span class="badge {{ $merek->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }} position-absolute top-0 end-0 m-2" style="font-size: 0.65rem;">
                                 {{ ucfirst($merek->status) }}
                              </span>
                           </div>
                           <div class="card-body p-3 d-flex flex-column justify-content-between">
                              <div>
                                 <h3 class="fs-6 fw-bold mb-1 text-truncate" title="{{ $merek->nama_merek }}" style="font-size: 0.95rem;">{{ $merek->nama_merek }}</h3>
                                 <p class="text-secondary small mb-2 text-truncate"><code>{{ $merek->slug }}</code></p>
                                 <span class="badge text-bg-light border px-2 py-1 small mb-3">☕ {{ $merek->produk_count ?? 0 }} Produk</span>
                              </div>
                              <div class="d-flex align-items-center justify-content-center gap-1 pt-2 border-top">
                                 <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalShowMerek{{ $merek->id }}">👁️ View</button>
                                 <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalEditMerek{{ $merek->id }}">✏️ Edit</button>
                                 <form action="{{ route('admin.merek.destroy', $merek->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus merek ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-2 py-1 text-nowrap fw-semibold" style="font-size: 0.72rem;">🗑️</button>
                                 </form>
                              </div>
                           </div>
                        </div>
                     </div>
                  @empty
                     <div class="col-12 text-center py-4 text-muted">Data merek tidak ditemukan.</div>
                  @endforelse
               </div>
            @else
               <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
               <div class="table-responsive d-none d-md-block">
                  <table class="table table-hover table-custom align-middle mb-0">
                     <thead>
                        <tr>
                           <th style="width: 70px;">Logo</th>
                           <th>Nama Merek</th>
                           <th>Slug</th>
                           <th>Status</th>
                           <th class="text-end">Aksi CRUD</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($mereks as $merek)
                           <tr>
                              <td>
                                 <a href="#" data-bs-toggle="modal" data-bs-target="#modalLogoMerek{{ $merek->id }}" title="Klik untuk lihat & ganti logo" class="d-inline-block merek-logo-trigger">
                                    @if($merek->logo_path)
                                       <img src="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" class="rounded shadow-sm border p-1" style="width: 50px; height: 40px; object-fit: contain; cursor: pointer; transition: transform 0.2s ease;">
                                    @else
                                       <span class="badge text-bg-light border p-2" style="cursor: pointer;">🖼️ Logo</span>
                                    @endif
                                 </a>
                              </td>
                              <td class="fw-bold small">{{ $merek->nama_merek }}</td>
                              <td class="small"><code>{{ $merek->slug }}</code></td>
                              <td>
                                 <span class="badge {{ $merek->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ ucfirst($merek->status) }}
                                 </span>
                              </td>
                              <td class="text-end">
                                 <div class="d-flex align-items-center justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowMerek{{ $merek->id }}">👁️ View</button>
                                    <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditMerek{{ $merek->id }}">✏️ Edit</button>
                                    <form action="{{ route('admin.merek.destroy', $merek->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus merek ini?')">
                                       @csrf
                                       @method('DELETE')
                                       <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                    </form>
                                 </div>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="5" class="text-center py-4 text-muted">Data merek tidak ditemukan.</td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
            @endif

            <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
            <div class="d-block d-md-none">
               @forelse($mereks as $merek)
                  <div class="mobile-data-card">
                     <div class="d-flex align-items-center gap-3 mb-2">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modalLogoMerek{{ $merek->id }}" title="Klik untuk lihat & ganti logo" class="flex-shrink-0">
                           @if($merek->logo_path)
                              <img src="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" class="rounded shadow-sm border p-1" style="width: 50px; height: 50px; object-fit: contain;">
                           @else
                              <span class="badge text-bg-light border p-2" style="cursor: pointer;">🖼️ Logo</span>
                           @endif
                        </a>
                        <div class="flex-grow-1">
                           <h3 class="fs-6 fw-bold mb-0">{{ $merek->nama_merek }}</h3>
                           <small class="text-secondary d-block">Slug: {{ $merek->slug }}</small>
                        </div>
                        <span class="badge {{ $merek->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                           {{ ucfirst($merek->status) }}
                        </span>
                     </div>
                     <div class="d-flex gap-2 mt-2 pt-2 border-top justify-content-end">
                        <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowMerek{{ $merek->id }}">👁️ View</button>
                        <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditMerek{{ $merek->id }}">✏️ Edit</button>
                        <form action="{{ route('admin.merek.destroy', $merek->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus merek?')">
                           @csrf
                           @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                        </form>
                     </div>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Data merek tidak ditemukan.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $mereks->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>

{{-- MODAL TAMBAH MEREK BARU --}}
<div class="modal fade" id="modalTambahMerek" tabindex="-1" aria-labelledby="modalTambahMerekLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.merek.store') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalTambahMerekLabel">🏷️ Tambah Merek (Brand) Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-3">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label for="nama_merek_new" class="form-label small fw-medium">Nama Merek <span class="text-danger">*</span></label>
                     <input type="text" name="nama_merek" id="nama_merek_new" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Supresso, BaliCafé, UCAFÉ" required>
                  </div>
                  <div class="mb-3">
                     <label for="logo_new" class="form-label small fw-medium">Logo Merek (Opsional)</label>
                     <input type="file" name="logo" id="logo_new" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                     <div class="form-text" style="font-size: 0.75rem;">Format PNG/JPG/SVG/WebP, max 4MB. Rekomendasi latar belakang transparan.</div>
                  </div>
                  <div class="mb-3">
                     <label for="status_new" class="form-label small fw-medium">Status Merek</label>
                     <select name="status" id="status_new" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="active" selected>Active (Aktif)</option>
                        <option value="inactive">Inactive (Nonaktif)</option>
                     </select>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="mb-3">
                     <label for="deskripsi_new" class="form-label small fw-medium">Deskripsi Merek (Bahasa Indonesia)</label>
                     <textarea name="deskripsi" id="deskripsi_new" rows="3" class="form-control rounded-3 bg-body-tertiary" placeholder="Deskripsi profil brand..."></textarea>
                  </div>
                  <div class="mb-3">
                     <label for="deskripsi_eng_new" class="form-label small fw-medium">Deskripsi Merek (English)</label>
                     <textarea name="deskripsi_eng" id="deskripsi_eng_new" rows="3" class="form-control rounded-3 bg-body-tertiary" placeholder="Brand description in English..."></textarea>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">💾 Simpan Merek</button>
         </div>
      </form>
   </div>
</div>

{{-- MODAL EDIT, DETAIL & LOGO MEREK (Looping Data) --}}
@foreach($mereks as $merek)

{{-- MODAL DETAIL MEREK --}}
<div class="modal fade" id="modalShowMerek{{ $merek->id }}" tabindex="-1" aria-labelledby="modalShowMerekLabel{{ $merek->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-custom-1" id="modalShowMerekLabel{{ $merek->id }}">👁️ Detail Merek</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="text-center mb-3">
               @if($merek->logo_path)
                  <img src="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" class="img-fluid rounded shadow-sm border p-2" style="max-height: 100px; object-fit: contain;">
               @else
                  <div class="bg-body-tertiary rounded p-3 text-secondary border">No Logo Available</div>
               @endif
            </div>
            <h4 class="fw-bold text-center mb-1">{{ $merek->nama_merek }}</h4>
            <p class="text-center text-secondary small mb-3">Slug: <code>{{ $merek->slug }}</code> | Status: <span class="badge {{ $merek->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($merek->status) }}</span></p>
            
            @if($merek->deskripsi)
               <div class="bg-body-tertiary p-3 rounded-3 mt-3">
                  <small class="text-muted d-block fw-bold mb-1">Deskripsi Merek:</small>
                  <p class="small text-secondary m-0">{{ $merek->deskripsi }}</p>
               </div>
            @endif
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            <a href="{{ route('products.index') }}?brand={{ $merek->slug }}" target="_blank" class="btn btn-info text-white rounded-pill px-4">Lihat Produk Merek ↗</a>
         </div>
      </div>
   </div>
</div>

{{-- MODAL EDIT MEREK --}}
<div class="modal fade" id="modalEditMerek{{ $merek->id }}" tabindex="-1" aria-labelledby="modalEditMerekLabel{{ $merek->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.merek.update', $merek->id) }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         @method('PUT')
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalEditMerekLabel{{ $merek->id }}">✏️ Edit Merek: {{ $merek->nama_merek }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-3">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Nama Merek <span class="text-danger">*</span></label>
                     <input type="text" name="nama_merek" class="form-control rounded-3 bg-body-tertiary" value="{{ $merek->nama_merek }}" required>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Ganti Logo Merek (Opsional)</label>
                     <input type="file" name="logo" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                     @if($merek->logo_path)
                        <div class="mt-2 p-2 text-center bg-white rounded border">
                           <img src="{{ asset($merek->logo_path) }}" alt="" style="height: 50px; object-fit: contain;">
                           <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">Logo Saat Ini</small>
                        </div>
                     @endif
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Status Merek</label>
                     <select name="status" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="active" {{ $merek->status == 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                        <option value="inactive" {{ $merek->status == 'inactive' ? 'selected' : '' }}>Inactive (Nonaktif)</option>
                     </select>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Deskripsi Merek (Bahasa Indonesia)</label>
                     <textarea name="deskripsi" rows="3" class="form-control rounded-3 bg-body-tertiary">{{ $merek->deskripsi }}</textarea>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Deskripsi Merek (English)</label>
                     <textarea name="deskripsi_eng" rows="3" class="form-control rounded-3 bg-body-tertiary">{{ $merek->deskripsi_eng }}</textarea>
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

{{-- MODAL PREVIEW & UPLOAD LOGO MEREK --}}
<div class="modal fade" id="modalLogoMerek{{ $merek->id }}" tabindex="-1" aria-labelledby="modalLogoMerekLabel{{ $merek->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('admin.merek.update-logo', $merek->id) }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalLogoMerekLabel{{ $merek->id }}">🖼️ Logo Merek: {{ $merek->nama_merek }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body text-center p-4">
            <div class="p-4 bg-white rounded-4 border shadow-sm mb-3 position-relative d-inline-block w-100" style="max-height: 250px; overflow: hidden;">
               @if($merek->logo_path)
                  <img src="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
               @else
                  <div class="py-4 text-muted">
                     <div style="font-size: 3rem;">🖼️</div>
                     <p class="mb-0 small">Belum ada logo untuk merek ini</p>
                  </div>
               @endif
            </div>

            <div class="text-start mb-3 bg-body-tertiary p-3 rounded-3 border">
               <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong class="small">{{ $merek->nama_merek }}</strong>
                  <span class="badge {{ $merek->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($merek->status) }}</span>
               </div>
               <small class="text-secondary d-block">Slug: <code>{{ $merek->slug }}</code></small>
            </div>

            <div class="text-start">
               <label for="logo_modal_{{ $merek->id }}" class="form-label small fw-bold text-custom-1">Upload Logo Baru (PNG, JPG, SVG, WebP)</label>
               <input type="file" name="logo" id="logo_modal_{{ $merek->id }}" class="form-control rounded-3 bg-body-tertiary" accept="image/*" required>
               <div class="form-text" style="font-size: 0.75rem;">File max 4MB. Logo baru akan menggantikan logo merek saat ini.</div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">📸 Upload & Ganti Logo</button>
         </div>
      </form>
   </div>
</div>

@endforeach

{{-- Modal Import Excel --}}
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <form action="{{ route('admin.merek.import') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="importExcelModalLabel">Import Data Merek Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <p class="small text-secondary mb-3">Upload file format <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code> untuk mengimpor data merek secara massal.</p>
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

         $('.modal').on('shown.bs.modal', function() {
            initSelect2(this);
         });
      });
   </script>
@endpush
