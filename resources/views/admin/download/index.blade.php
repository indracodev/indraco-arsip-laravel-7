@extends('layouts.app')

@section('title', 'INDRACO – Master Download')

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
   </style>
@endpush

@section('content')
<main id="content" tabindex="-1" class="container py-4 my-3">

   @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
         <strong>✅ Berhasil!</strong> {{ session('success') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
   @endif

   @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4" role="alert">
         <strong>❌ Error!</strong> {{ session('error') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
   @endif

   <!-- Header Banner -->
   <section aria-label="Welcome Admin" class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm mb-4">
      <div class="row align-items-center gy-3">
         <div class="col-12 col-md">
            <span class="badge bg-custom-1 text-white mb-2 px-3 py-2 rounded-pill">📥 Live di Halaman /downloads</span>
            <h1 class="h3 fw-bold mb-1">Master Data Berkas Download</h1>
            <p class="text-secondary small mb-0">Kelola berkas katalog, brosur produk, dan sertifikasi yang <strong>langsung terintegrasi</strong> dengan halaman <a href="{{ route('downloads') }}" target="_blank" class="fw-bold text-custom-1">/downloads</a>.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-custom-1 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahDownload">
               + Tambah Berkas Download
            </button>
            <a href="{{ route('downloads') }}" target="_blank" class="btn btn-outline-secondary rounded-pill px-4">🌐 Lihat di Website</a>
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
                  <h2 class="h5 fw-bold mb-0">Master Data Berkas Download</h2>
                  <p class="text-secondary small mb-0">Menampilkan 20 Berkas per halaman (Total {{ $downloads->total() }} Berkas).</p>
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
                  <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahDownload">+ Tambah Berkas</button>
               </div>
            </div>
            
            <!-- Filter & Search Bar -->
            <form action="{{ route('admin.download.index') }}" method="GET" class="row g-2 mb-4">
               @if(request('view'))
                  <input type="hidden" name="view" value="{{ request('view') }}">
               @endif
               <div class="col-md-6">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari judul berkas / katalog..." value="{{ request('search') }}">
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
                  @forelse($downloads as $item)
                     <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative bg-body">
                           <div class="position-relative bg-body-tertiary text-center border-bottom" style="height: 120px; overflow: hidden;">
                              <img src="{{ $item->image_url }}" alt="{{ $item->judul }}" class="img-fluid object-fit-contain w-100 h-100 p-2">
                              <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-2" style="font-size: 0.65rem;">
                                 #{{ $item->order_num }}
                              </span>
                              <form action="{{ route('admin.download.toggle', $item->id) }}" method="POST" class="position-absolute top-0 end-0 m-2">
                                 @csrf
                                 @method('PATCH')
                                 <button type="submit" class="border-0 p-0 bg-transparent" title="{{ $item->is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}">
                                    <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }}" style="font-size: 0.65rem;">
                                       {{ $item->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                 </button>
                              </form>
                           </div>
                           <div class="card-body p-3 d-flex flex-column justify-content-between">
                              <div>
                                 <span class="badge bg-body-tertiary text-body mb-1" style="font-size: 0.68rem;">{{ $item->kategori }}</span>
                                 <h3 class="fs-6 fw-bold mb-1 text-truncate" title="{{ $item->judul }}" style="font-size: 0.85rem;">
                                    {{ $item->judul }}
                                 </h3>
                                 @if($item->file_size)
                                    <p class="text-primary small mb-2" style="font-size: 0.72rem;">📥 {{ $item->file_size }}</p>
                                 @endif
                              </div>
                              <div class="d-flex align-items-center justify-content-center gap-1 pt-2 border-top">
                                 @if($item->file_path)
                                    <a href="{{ $item->download_url }}" download class="btn btn-sm btn-info text-white rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;" title="Unduh berkas">📥</a>
                                 @endif
                                 <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalEditDownload{{ $item->id }}">✏️ Edit</button>
                                 <form action="{{ route('admin.download.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus berkas download ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-2 py-1 text-nowrap fw-semibold" style="font-size: 0.72rem;">🗑️</button>
                                 </form>
                              </div>
                           </div>
                        </div>
                     </div>
                  @empty
                     <div class="col-12 text-center py-4 text-muted">Belum ada berkas download terdaftar.</div>
                  @endforelse
               </div>
            @else
               <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
               <div class="table-responsive d-none d-md-block mb-4">
                  <table class="table table-hover table-custom align-middle mb-0">
                     <thead>
                        <tr>
                           <th style="width: 80px;">Cover</th>
                           <th>Judul Berkas</th>
                           <th>Kategori</th>
                           <th>Ukuran Berkas</th>
                           <th>Urutan</th>
                           <th>Status</th>
                           <th class="text-end">Aksi CRUD</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($downloads as $item)
                           <tr>
                              <td>
                                 <img src="{{ $item->image_url }}" alt="{{ $item->judul }}" class="rounded border shadow-sm p-1" style="width: 50px; height: 50px; object-fit: contain;">
                              </td>
                              <td>
                                 <div class="fw-bold small">{{ $item->judul }}</div>
                                 <small class="text-secondary">{{ $item->judul_eng ?? '-' }}</small>
                              </td>
                              <td><span class="badge text-bg-light border">{{ $item->kategori }}</span></td>
                              <td class="small">{{ $item->file_size ?? '-' }}</td>
                              <td><span class="badge text-bg-light border">#{{ $item->order_num }}</span></td>
                              <td>
                                 <form action="{{ route('admin.download.toggle', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="border-0 p-0 bg-transparent" title="Klik untuk ubah status">
                                       <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                          {{ $item->is_active ? 'Active' : 'Inactive' }}
                                       </span>
                                    </button>
                                 </form>
                              </td>
                              <td class="text-end">
                                 <div class="d-flex align-items-center justify-content-end gap-2">
                                    @if($item->file_path)
                                       <a href="{{ $item->download_url }}" download class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap">📥 File</a>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditDownload{{ $item->id }}">✏️ Edit</button>
                                    <form action="{{ route('admin.download.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus berkas download ini?')">
                                       @csrf
                                       @method('DELETE')
                                       <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                    </form>
                                 </div>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="7" class="text-center py-4 text-muted">Belum ada berkas download terdaftar.</td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
            @endif

            <!-- MOBILE VIEW -->
            <div class="d-block d-md-none">
               @forelse($downloads as $item)
                  <div class="mobile-data-card">
                     <div class="d-flex align-items-start gap-3 mb-2">
                        <img src="{{ $item->image_url }}" alt="Download" style="width: 50px; height: 50px; object-fit: contain;" class="rounded-3 flex-shrink-0 border p-1">
                        <div class="flex-grow-1 min-w-0">
                           <h3 class="fs-6 fw-bold mb-0 text-truncate">{{ $item->judul }}</h3>
                           <small class="text-secondary d-block">{{ $item->kategori }} &bull; {{ $item->file_size ?? 'PDF' }}</small>
                           <small class="text-muted d-block">Order: #{{ $item->order_num }}</small>
                        </div>
                        <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }} flex-shrink-0">
                           {{ $item->is_active ? 'Active' : 'Inactive' }}
                        </span>
                     </div>
                     <div class="d-flex gap-2 mt-2 pt-2 border-top">
                        <form action="{{ route('admin.download.toggle', $item->id) }}" method="POST" class="flex-fill">
                           @csrf @method('PATCH')
                           <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} rounded-pill w-100 fw-medium">
                              {{ $item->is_active ? '⏸ Nonaktifkan' : '▶️ Aktifkan' }}
                           </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-custom-1 rounded-pill flex-fill fw-medium"
                           data-bs-toggle="modal" data-bs-target="#modalEditDownload{{ $item->id }}">✏️ Edit</button>
                        <form action="{{ route('admin.download.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                           @csrf @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 fw-medium">🗑️</button>
                        </form>
                     </div>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Belum ada berkas download terdaftar.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $downloads->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>

{{-- MODALS FOR ALL DOWNLOAD ITEMS --}}
@foreach($downloads as $item)
   {{-- MODAL EDIT DOWNLOAD --}}
   <div class="modal fade" id="modalEditDownload{{ $item->id }}" tabindex="-1" aria-labelledby="editDownloadLabel{{ $item->id }}" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
         <form action="{{ route('admin.download.update', $item->id) }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
            @csrf
            @method('PUT')
            <div class="modal-header border-0 pb-0">
               <h5 class="modal-title fw-bold" id="editDownloadLabel{{ $item->id }}">✏️ Edit Berkas Download #{{ $item->order_num }}</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
               <div class="row g-3">
                  <div class="col-md-6">
                     <div class="mb-3">
                        <label class="form-label small fw-medium">Judul Berkas (ID) <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control rounded-3 bg-body-tertiary" value="{{ $item->judul }}" required>
                     </div>
                     <div class="mb-3">
                        <label class="form-label small fw-medium">Judul Berkas (EN)</label>
                        <input type="text" name="judul_eng" class="form-control rounded-3 bg-body-tertiary" value="{{ $item->judul_eng }}">
                     </div>
                     <div class="mb-3">
                        <label class="form-label small fw-medium">Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="kategori" class="form-control rounded-3 bg-body-tertiary" value="{{ $item->kategori }}" required>
                     </div>
                     <div class="mb-3">
                        <label class="form-label small fw-medium">Urutan (Order Number)</label>
                        <input type="number" name="order_num" class="form-control rounded-3 bg-body-tertiary" value="{{ $item->order_num }}" min="0">
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="mb-3">
                        <label class="form-label small fw-medium">Ganti Gambar Cover (opsional)</label>
                        <input type="file" name="image" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                        <img src="{{ $item->image_url }}" alt="" class="mt-2 rounded-3" style="height: 60px; object-fit: contain;">
                     </div>
                     <div class="mb-3">
                        <label class="form-label small fw-medium">Ganti File Download (PDF / ZIP) (opsional)</label>
                        <input type="file" name="file" class="form-control rounded-3 bg-body-tertiary" accept=".pdf,.zip,.rar,.doc,.docx,.xls,.xlsx">
                        @if($item->file_path)
                           <div class="form-text mt-1">File saat ini: <code>{{ basename($item->file_path) }}</code> ({{ $item->file_size }})</div>
                        @endif
                     </div>
                     <div class="mb-3 d-flex align-items-center gap-2">
                        <label class="form-label small fw-medium mb-0">Status Aktif</label>
                        <div class="form-check form-switch mb-0">
                           <input class="form-check-input" type="checkbox" name="is_active" id="is_active_{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }} style="cursor: pointer;">
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

{{-- MODAL TAMBAH DOWNLOAD --}}
<div class="modal fade" id="modalTambahDownload" tabindex="-1" aria-labelledby="modalTambahDownloadLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.download.store') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalTambahDownloadLabel">📥 Tambah Berkas Download Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="row g-3">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Judul Berkas (ID) <span class="text-danger">*</span></label>
                     <input type="text" name="judul" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Supresso Product Catalog" required>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Judul Berkas (EN)</label>
                     <input type="text" name="judul_eng" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Supresso Product Catalog">
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Kategori <span class="text-danger">*</span></label>
                     <input type="text" name="kategori" class="form-control rounded-3 bg-body-tertiary" value="Catalog & Brochure" required>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Urutan (Order Number)</label>
                     <input type="number" name="order_num" class="form-control rounded-3 bg-body-tertiary" value="0" min="0">
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Upload Gambar Cover Brosur/Katalog</label>
                     <input type="file" name="image" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Upload File Dokumen PDF / ZIP</label>
                     <input type="file" name="file" class="form-control rounded-3 bg-body-tertiary" accept=".pdf,.zip,.rar,.doc,.docx,.xls,.xlsx">
                  </div>
                  <div class="mb-3 d-flex align-items-center gap-2">
                     <label class="form-label small fw-medium mb-0">Status Langsung Aktif</label>
                     <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active_new" checked style="cursor: pointer;">
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">🚀 Simpan Berkas</button>
         </div>
      </form>
   </div>
</div>
@endsection
