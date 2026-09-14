@extends('layouts.app')

@section('title', 'INDRACO – Master Banner')

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
      .preview-modal-img {
         max-height: 70vh;
         width: 100%;
         object-fit: contain;
         border-radius: 8px;
      }
   </style>
@endpush

@section('content')
<main id="content" tabindex="-1" class="container py-4 my-3">

   @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
         <strong>✅</strong> {{ session('success') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
   @endif

   <!-- Header Banner -->
   <section aria-label="Welcome Admin" class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm mb-4">
      <div class="row align-items-center gy-3">
         <div class="col-12 col-md">
            <span class="badge bg-success text-white mb-2 px-3 py-2 rounded-pill">🖼️ Live di Landing Page</span>
            <h1 class="h3 fw-bold mb-1">Master Data Banner Slider</h1>
            <p class="text-secondary small mb-0">Kelola banner promosi visual yang <strong>langsung tampil</strong> di halaman utama website INDRACO.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-custom-1 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahBanner">
               + Tambah Banner
            </button>
            <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-secondary rounded-pill px-4">🌐 Lihat di Website</a>
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
                  <h2 class="h5 fw-bold mb-0">Master Data Banner Slider</h2>
                  <p class="text-secondary small mb-0">Menampilkan 20 Banner per halaman (Total {{ $banners->total() }} Banner).</p>
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
                  <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahBanner">+ Tambah Banner</button>
               </div>
            </div>
            
            <!-- Filter & Search Bar -->
            <form action="{{ route('admin.banner.index') }}" method="GET" class="row g-2 mb-4">
               @if(request('view'))
                  <input type="hidden" name="view" value="{{ request('view') }}">
               @endif
               <div class="col-md-6">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari judul banner..." value="{{ request('search') }}">
               </div>
               <div class="col-md-4">
                  <select name="status" class="form-select rounded-pill px-3 bg-body-tertiary">
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
                  @forelse($banners as $b)
                     <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative bg-body">
                           <div class="position-relative bg-body-tertiary text-center border-bottom" style="height: 120px; overflow: hidden;">
                              <a href="#" data-bs-toggle="modal" data-bs-target="#previewBannerModal{{ $b->id }}" title="Klik untuk preview banner">
                                 <img src="{{ asset($b->image_path) }}" alt="{{ $b->title_id ?? 'Banner' }}" class="img-fluid object-fit-cover w-100 h-100">
                              </a>
                              <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-2" style="font-size: 0.65rem;">
                                 #{{ $b->order_num }}
                              </span>
                              <form action="{{ route('admin.banner.toggle', $b->id) }}" method="POST" class="position-absolute top-0 end-0 m-2">
                                 @csrf
                                 @method('PATCH')
                                 <button type="submit" class="border-0 p-0 bg-transparent" title="{{ $b->is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}">
                                    <span class="badge {{ $b->is_active ? 'text-bg-success' : 'text-bg-secondary' }}" style="font-size: 0.65rem;">
                                       {{ $b->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                 </button>
                              </form>
                           </div>
                           <div class="card-body p-3 d-flex flex-column justify-content-between">
                              <div>
                                 <h3 class="fs-6 fw-bold mb-1 text-truncate" title="{{ $b->title_id ?? 'Banner' }}" style="font-size: 0.88rem;">
                                    {{ $b->title_id ?? '(Tanpa Judul)' }}
                                 </h3>
                                 @if($b->title_en)
                                    <p class="text-secondary small mb-1 text-truncate" style="font-size: 0.75rem;">{{ $b->title_en }}</p>
                                 @endif
                                 @if($b->link)
                                    <p class="text-primary small mb-2 text-truncate" style="font-size: 0.72rem;">🔗 {{ $b->link }}</p>
                                 @endif
                              </div>
                              <div class="d-flex align-items-center justify-content-center gap-1 pt-2 border-top">
                                 <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#previewBannerModal{{ $b->id }}">👁️ View</button>
                                 <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalEditBanner{{ $b->id }}">✏️ Edit</button>
                                 <form action="{{ route('admin.banner.destroy', $b->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus banner ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-2 py-1 text-nowrap fw-semibold" style="font-size: 0.72rem;">🗑️</button>
                                 </form>
                              </div>
                           </div>
                        </div>
                     </div>
                  @empty
                     <div class="col-12 text-center py-4 text-muted">Belum ada banner terdaftar.</div>
                  @endforelse
               </div>
            @else
               <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
               <div class="table-responsive d-none d-md-block mb-4">
                  <table class="table table-hover table-custom align-middle mb-0">
                     <thead>
                        <tr>
                           <th style="width: 120px;">Foto Banner</th>
                           <th>Judul Banner (ID / EN)</th>
                           <th>Target Link</th>
                           <th>Urutan</th>
                           <th>Status</th>
                           <th class="text-end">Aksi CRUD</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($banners as $b)
                           <tr>
                              <td>
                                 <a href="#" data-bs-toggle="modal" data-bs-target="#previewBannerModal{{ $b->id }}">
                                    <img src="{{ asset($b->image_path) }}" alt="{{ $b->title_id }}" class="rounded border shadow-sm" style="width: 100px; height: 50px; object-fit: cover;">
                                 </a>
                              </td>
                              <td>
                                 <div class="fw-bold small">{{ $b->title_id ?? '(Tanpa Judul)' }}</div>
                                 <small class="text-secondary">{{ $b->title_en ?? '-' }}</small>
                              </td>
                              <td class="small">
                                 @if($b->link)
                                    <a href="{{ $b->link }}" target="_blank" class="text-custom-1 text-truncate d-inline-block" style="max-width: 150px;">{{ $b->link }}</a>
                                 @else
                                    <span class="text-muted">-</span>
                                 @endif
                              </td>
                              <td><span class="badge text-bg-light border">#{{ $b->order_num }}</span></td>
                              <td>
                                 <form action="{{ route('admin.banner.toggle', $b->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="border-0 p-0 bg-transparent" title="Klik untuk ubah status">
                                       <span class="badge {{ $b->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                          {{ $b->is_active ? 'Active' : 'Inactive' }}
                                       </span>
                                    </button>
                                 </form>
                              </td>
                              <td class="text-end">
                                 <div class="d-flex align-items-center justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#previewBannerModal{{ $b->id }}">👁️ View</button>
                                    <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditBanner{{ $b->id }}">✏️ Edit</button>
                                    <form action="{{ route('admin.banner.destroy', $b->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus banner ini?')">
                                       @csrf
                                       @method('DELETE')
                                       <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                    </form>
                                 </div>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="6" class="text-center py-4 text-muted">Belum ada banner terdaftar.</td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
            @endif

            <!-- MOBILE VIEW -->
            <div class="d-block d-md-none">
               @forelse($banners as $b)
                  <div class="mobile-data-card">
                     <div class="d-flex align-items-start gap-3 mb-2">
                        <img src="{{ asset($b->image_path) }}" alt="Banner" style="width: 80px; height: 55px; object-fit: cover;" class="rounded-3 flex-shrink-0">
                        <div class="flex-grow-1 min-w-0">
                           <h3 class="fs-6 fw-bold mb-0 text-truncate">{{ $b->title_id ?? 'Banner Promosi' }}</h3>
                           @if($b->title_en)<small class="text-secondary d-block text-truncate">{{ $b->title_en }}</small>@endif
                           <small class="text-muted d-block">Order: #{{ $b->order_num }}</small>
                        </div>
                        <span class="badge {{ $b->is_active ? 'text-bg-success' : 'text-bg-secondary' }} flex-shrink-0">
                           {{ $b->is_active ? 'Active' : 'Inactive' }}
                        </span>
                     </div>
                     <div class="d-flex gap-2 mt-2 pt-2 border-top">
                        <form action="{{ route('admin.banner.toggle', $b->id) }}" method="POST" class="flex-fill">
                           @csrf @method('PATCH')
                           <button type="submit" class="btn btn-sm {{ $b->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} rounded-pill w-100 fw-medium">
                              {{ $b->is_active ? '⏸ Nonaktifkan' : '▶️ Aktifkan' }}
                           </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-info text-white rounded-pill flex-fill fw-medium"
                           data-bs-toggle="modal" data-bs-target="#previewBannerModal{{ $b->id }}">👁️ View</button>
                        <button type="button" class="btn btn-sm btn-custom-1 rounded-pill flex-fill fw-medium"
                           data-bs-toggle="modal" data-bs-target="#modalEditBanner{{ $b->id }}">✏️ Edit</button>
                        <form action="{{ route('admin.banner.destroy', $b->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus banner?')">
                           @csrf @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 fw-medium">🗑️</button>
                        </form>
                     </div>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Belum ada banner terdaftar.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $banners->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>

{{-- MODALS FOR ALL BANNERS --}}
@foreach($banners as $b)
   {{-- MODAL PREVIEW BANNER --}}
   <div class="modal fade" id="previewBannerModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
         <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
               <h6 class="modal-title fw-bold">Preview: {{ $b->title_id ?? 'Banner' }}</h6>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-0">
               <img src="{{ asset($b->image_path) }}" alt="{{ $b->title_id }}" class="preview-modal-img">
               @if($b->title_id || $b->subtitle_id)
                  <div class="mt-3 text-start">
                     @if($b->title_id) <p class="mb-1 fw-bold">{{ $b->title_id }}</p> @endif
                     @if($b->title_en) <p class="mb-1 text-secondary">{{ $b->title_en }}</p> @endif
                     @if($b->subtitle_id) <p class="mb-1 small text-muted">{{ $b->subtitle_id }}</p> @endif
                     @if($b->button_text_id) <span class="badge bg-body-secondary text-body mt-1">🔘 CTA: {{ $b->button_text_id }}</span> @endif
                  </div>
               @endif
            </div>
         </div>
      </div>
   </div>

   {{-- MODAL EDIT BANNER --}}
   <div class="modal fade" id="modalEditBanner{{ $b->id }}" tabindex="-1" aria-labelledby="editBannerLabel{{ $b->id }}" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
         <form action="{{ route('admin.banner.update', $b->id) }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
            @csrf
            @method('PUT')
            <div class="modal-header border-0 pb-0">
               <h5 class="modal-title fw-bold" id="editBannerLabel{{ $b->id }}">✏️ Edit Banner #{{ $b->order_num }}</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
               <div class="row g-3">
                  <div class="col-md-6">
                     <div class="mb-3">
                        <label class="form-label small fw-medium">Ganti Gambar Banner (opsional)</label>
                        <input type="file" name="image" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                        <img src="{{ asset($b->image_path) }}" alt="" class="mt-2 rounded-3 w-100" style="height: 100px; object-fit: cover;">
                     </div>
                     <div class="mb-3">
                        <label class="form-label small fw-medium">Urutan (Order)</label>
                        <input type="number" name="order_num" class="form-control rounded-3 bg-body-tertiary" value="{{ $b->order_num }}" min="0">
                     </div>
                     <div class="mb-3 d-flex align-items-center gap-2">
                        <label class="form-label small fw-medium mb-0">Status Aktif</label>
                        <div class="form-check form-switch mb-0">
                           <input class="form-check-input" type="checkbox" name="is_active" id="is_active_{{ $b->id }}" {{ $b->is_active ? 'checked' : '' }} style="cursor: pointer;">
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="mb-3">
                        <label class="form-label small fw-medium">🍊 Teks Oranye Raksasa (Judul ID)</label>
                        <input type="text" name="title_id" class="form-control rounded-3 bg-body-tertiary" value="{{ $b->title_id }}" placeholder="contoh: COFFEE">
                     </div>
                     <div class="mb-3">
                        <label class="form-label small fw-medium">🍊 Teks Oranye Raksasa (Judul EN)</label>
                        <input type="text" name="title_en" class="form-control rounded-3 bg-body-tertiary" value="{{ $b->title_en }}" placeholder="contoh: COFFEE">
                     </div>
                     <div class="mb-3">
                        <label class="form-label small fw-medium">📝 Deskripsi Bawah Slider (ID)</label>
                        <input type="text" name="subtitle_id" class="form-control rounded-3 bg-body-tertiary" value="{{ $b->subtitle_id }}" placeholder="Deskripsi di kiri bawah...">
                     </div>
                     <div class="mb-3">
                        <label class="form-label small fw-medium">📝 Deskripsi Bawah Slider (EN)</label>
                        <input type="text" name="subtitle_en" class="form-control rounded-3 bg-body-tertiary" value="{{ $b->subtitle_en }}" placeholder="Description at bottom left...">
                     </div>
                     <div class="mb-3">
                        <label class="form-label small fw-medium">🔗 URL Link Banner / Produk</label>
                        <input type="text" name="link" class="form-control rounded-3 bg-body-tertiary" value="{{ $b->link }}" placeholder="/products atau https://...">
                     </div>
                     <div class="row g-2">
                        <div class="col-6">
                           <label class="form-label small fw-medium">Teks Tombol (ID)</label>
                           <input type="text" name="button_text_id" class="form-control rounded-3 bg-body-tertiary" value="{{ $b->button_text_id }}" placeholder="Explore Coffee">
                        </div>
                        <div class="col-6">
                           <label class="form-label small fw-medium">Teks Tombol (EN)</label>
                           <input type="text" name="button_text_en" class="form-control rounded-3 bg-body-tertiary" value="{{ $b->button_text_en }}" placeholder="Explore Coffee">
                        </div>
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
@endforeach

{{-- MODAL TAMBAH BANNER --}}
<div class="modal fade" id="modalTambahBanner" tabindex="-1" aria-labelledby="modalTambahBannerLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.banner.store') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalTambahBannerLabel">🖼️ Tambah Banner Slider Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="row g-3">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Upload Gambar Banner (Wajib) <span class="text-danger">*</span></label>
                     <input type="file" name="image" class="form-control rounded-3 bg-body-tertiary" accept="image/*" required>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Urutan (Order Number)</label>
                     <input type="number" name="order_num" class="form-control rounded-3 bg-body-tertiary" value="0" min="0">
                  </div>
                  <div class="mb-3 d-flex align-items-center gap-2">
                     <label class="form-label small fw-medium mb-0">Status Langsung Aktif</label>
                     <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active_new" checked style="cursor: pointer;">
                     </div>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label small fw-medium">🍊 Teks Oranye Raksasa (Judul ID)</label>
                     <input type="text" name="title_id" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: COFFEE">
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">🍊 Teks Oranye Raksasa (Judul EN)</label>
                     <input type="text" name="title_en" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: COFFEE">
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">📝 Deskripsi Bawah Slider (ID)</label>
                     <input type="text" name="subtitle_id" class="form-control rounded-3 bg-body-tertiary" placeholder="Deskripsi di kiri bawah...">
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">📝 Deskripsi Bawah Slider (EN)</label>
                     <input type="text" name="subtitle_en" class="form-control rounded-3 bg-body-tertiary" placeholder="Description at bottom left...">
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">🔗 URL Link Banner / Produk</label>
                     <input type="text" name="link" class="form-control rounded-3 bg-body-tertiary" placeholder="/products atau https://...">
                  </div>
                  <div class="row g-2">
                     <div class="col-6">
                        <label class="form-label small fw-medium">Teks Tombol (ID)</label>
                        <input type="text" name="button_text_id" class="form-control rounded-3 bg-body-tertiary" placeholder="Explore Coffee">
                     </div>
                     <div class="col-6">
                        <label class="form-label small fw-medium">Teks Tombol (EN)</label>
                        <input type="text" name="button_text_en" class="form-control rounded-3 bg-body-tertiary" placeholder="Explore Coffee">
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">🚀 Simpan Banner</button>
         </div>
      </form>
   </div>
</div>
@endsection
