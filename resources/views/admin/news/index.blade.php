@extends('layouts.app')

@section('title', 'INDRACO – Master News')

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
      .pagination-custom .page-item .page-link {
         border-radius: 20px;
         margin: 0 3px;
         border: 1px solid rgba(0,0,0,0.1);
         color: var(--custom-primary);
         font-weight: 500;
         padding: 6px 14px;
         font-size: 0.88rem;
         transition: all 0.2s ease;
      }
      .pagination-custom .page-item.active .page-link {
         background-color: var(--custom-primary) !important;
         border-color: var(--custom-primary) !important;
         color: #ffffff !important;
      }
      .pagination-custom .page-item .page-link:hover {
         background-color: rgba(var(--custom-primary-rgb), 0.1);
         color: var(--custom-primary);
      }
   </style>
@endpush

@section('content')
<main id="content" tabindex="-1" class="container py-4 my-3">

   <!-- Alert Messages -->
   @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
         <strong>Berhasil!</strong> {{ session('success') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
   @endif

   @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
         <strong>Error!</strong> {{ session('error') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
   @endif

   @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
         <ul class="mb-0">
            @foreach($errors->all() as $error)
               <li>{{ $error }}</li>
            @endforeach
         </ul>
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
   @endif

   <!-- Header Banner -->
   <section aria-label="Welcome Admin" class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm mb-4">
      <div class="row align-items-center gy-3">
         <div class="col-12 col-md">
            <span class="badge bg-danger text-white mb-2 px-3 py-2 rounded-pill">Administrator Access</span>
            <h1 class="h3 fw-bold mb-1">Master Data Berita &amp; Media</h1>
            <p class="text-secondary small mb-0">Kelola rilis berita korporat, CSR, dan peluncuran produk INDRACO.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.news.template') }}" class="btn btn-outline-secondary rounded-pill px-3">Download Template Excel</a>
            <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importNewsModal">Import Excel</button>
            <button type="button" class="btn btn-custom-1 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahNews">+ Tambah Berita Baru</button>
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
                  <h2 class="h5 fw-bold mb-0">Master Data Berita &amp; Media</h2>
                  <p class="text-secondary small mb-0">Menampilkan 20 Berita per halaman (Total {{ $newsList->total() }} Artikel).</p>
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
                  <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahNews">+ Tambah Berita</button>
               </div>
            </div>

            <!-- Filter & Search Bar -->
            <form action="{{ route('admin.news.index') }}" method="GET" class="row g-2 mb-4">
               @if(request('view'))
                  <input type="hidden" name="view" value="{{ request('view') }}">
               @endif
               <div class="col-md-9">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari judul berita / konten..." value="{{ request('search') }}">
               </div>
               <div class="col-md-3">
                  <button type="submit" class="btn btn-secondary rounded-pill w-100">Filter Search</button>
               </div>
            </form>

            @if(request('view', 'grid') == 'grid')
               <!-- DESKTOP GRID VIEW: 5 COLUMNS CARD LIST (d-none d-md-flex) -->
               <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mb-4 d-none d-md-flex">
                  @forelse($newsList as $news)
                     <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative bg-body">
                           <div class="position-relative bg-body-tertiary text-center border-bottom" style="height: 120px; overflow: hidden;">
                              <img src="{{ $news->image_url }}" alt="{{ $news->judul }}" class="img-fluid object-fit-cover w-100 h-100">
                              <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-2" style="font-size: 0.65rem;">
                                 {{ $news->formatted_tanggal }}
                              </span>
                           </div>
                           <div class="card-body p-3 d-flex flex-column justify-content-between">
                              <div>
                                 <h3 class="fs-6 fw-bold mb-1 text-truncate" title="{{ $news->judul }}" style="font-size: 0.88rem;">{{ $news->judul }}</h3>
                                 @if($news->judul_eng)
                                    <p class="text-secondary small mb-1 text-truncate" style="font-size: 0.75rem;">{{ $news->judul_eng }}</p>
                                 @endif
                                 <p class="text-muted small mb-2 text-truncate" style="font-size: 0.72rem;"><code>{{ $news->slug }}</code></p>
                              </div>
                              <div class="d-flex align-items-center justify-content-center gap-1 pt-2 border-top">
                                 <a href="{{ route('news.detail', $news->slug) }}" target="_blank" class="btn btn-sm btn-info text-white rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;">👁️ View</a>
                                 <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-2 py-1 fw-semibold text-nowrap" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalEditNews{{ $news->id }}">✏️ Edit</button>
                                 <form action="{{ route('admin.news.destroy', $news->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus berita ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-2 py-1 text-nowrap fw-semibold" style="font-size: 0.72rem;">🗑️</button>
                                 </form>
                              </div>
                           </div>
                        </div>
                     </div>
                  @empty
                     <div class="col-12 text-center py-4 text-muted">Belum ada berita.</div>
                  @endforelse
               </div>
            @else
               <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
               <div class="table-responsive d-none d-md-block">
                  <table class="table table-hover table-custom align-middle mb-0">
                     <thead>
                        <tr>
                           <th style="width: 70px;">Gambar</th>
                           <th>Judul Berita</th>
                           <th>Tanggal Publikasi</th>
                           <th>Slug</th>
                           <th class="text-end">Aksi CRUD</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($newsList as $news)
                           <tr>
                              <td>
                                 <img src="{{ $news->image_url }}" alt="{{ $news->judul }}" style="height: 40px; width: 60px; object-fit: cover;" class="rounded shadow-sm">
                              </td>
                              <td class="fw-bold small">
                                 {{ $news->judul }}
                                 @if($news->judul_eng)
                                    <br><small class="text-muted fw-normal">EN: {{ $news->judul_eng }}</small>
                                 @endif
                              </td>
                              <td class="small">{{ $news->formatted_tanggal }}</td>
                              <td class="small"><code>{{ $news->slug }}</code></td>
                              <td class="text-end">
                                 <div class="d-flex align-items-center justify-content-end gap-2">
                                    <a href="{{ route('news.detail', $news->slug) }}" target="_blank" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap">👁️ View</a>
                                    <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditNews{{ $news->id }}">✏️ Edit</button>
                                    <form action="{{ route('admin.news.destroy', $news->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus berita ini?')">
                                       @csrf
                                       @method('DELETE')
                                       <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                    </form>
                                 </div>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="5" class="text-center py-4 text-muted">Belum ada berita.</td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
            @endif

            <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
            <div class="d-block d-md-none">
               @forelse($newsList as $news)
                  <div class="mobile-data-card">
                     <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="{{ $news->image_url }}" alt="{{ $news->judul }}" style="width: 60px; height: 40px; object-fit: cover;" class="rounded">
                        <div class="flex-grow-1">
                           <h3 class="fs-6 fw-bold mb-0">{{ $news->judul }}</h3>
                           <small class="text-secondary d-block">{{ $news->formatted_tanggal }}</small>
                        </div>
                     </div>
                     <div class="d-flex gap-2 mt-2 pt-2 border-top justify-content-end">
                        <a href="{{ route('news.detail', $news->slug) }}" target="_blank" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap">👁️ View</a>
                        <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap"
                                data-bs-toggle="modal" 
                                data-bs-target="#modalEditNews{{ $news->id }}">✏️ Edit</button>
                        <form action="{{ route('admin.news.destroy', $news->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus berita?')">
                           @csrf
                           @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                        </form>
                     </div>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Belum ada berita.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $newsList->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>

{{-- MODAL TAMBAH NEWS --}}
<div class="modal fade" id="modalTambahNews" tabindex="-1" aria-labelledby="modalTambahNewsLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.news.store') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalTambahNewsLabel">Tambah Artikel Berita Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <div class="row g-3 mb-3">
               <div class="col-md-6">
                  <label for="judul_news" class="form-label small fw-medium">Judul Berita (Indonesian) *</label>
                  <input type="text" name="judul" id="judul_news" class="form-control rounded-pill bg-body-tertiary" placeholder="contoh: Peluncuran Supresso Speciality Series" required>
               </div>
               <div class="col-md-6">
                  <label for="judul_eng_news" class="form-label small fw-medium">Judul Berita (English)</label>
                  <input type="text" name="judul_eng" id="judul_eng_news" class="form-control rounded-pill bg-body-tertiary" placeholder="example: Launch of Supresso Speciality Series">
               </div>
            </div>

            <div class="row g-3 mb-3">
               <div class="col-md-6">
                  <label for="tanggal_news" class="form-label small fw-medium">Tanggal Publikasi (ID)</label>
                  <input type="text" name="tanggal" id="tanggal_news" class="form-control rounded-pill bg-body-tertiary" placeholder="contoh: 15 Mei 2026">
               </div>
               <div class="col-md-6">
                  <label for="tanggal_eng_news" class="form-label small fw-medium">Tanggal Publikasi (EN)</label>
                  <input type="text" name="tanggal_eng" id="tanggal_eng_news" class="form-control rounded-pill bg-body-tertiary" placeholder="example: 15 May 2026">
               </div>
            </div>

            <div class="mb-3">
               <label for="slug_news" class="form-label small fw-medium">Slug URL (Opsional)</label>
               <input type="text" name="slug" id="slug_news" class="form-control rounded-pill bg-body-tertiary" placeholder="Otomatis terisi jika dikosongkan">
            </div>

            <div class="mb-3">
               <label for="image_news" class="form-label small fw-medium">Upload Gambar Featured</label>
               <input type="file" name="image" id="image_news" class="form-control rounded-pill bg-body-tertiary" accept="image/*">
            </div>

            <div class="mb-3">
               <label for="content_news" class="form-label small fw-medium">Isi Berita (Indonesian)</label>
               <textarea name="content" id="content_news" rows="4" class="form-control rounded-3 bg-body-tertiary" placeholder="Tuliskan isi ringkasan atau rilis berita..."></textarea>
            </div>

            <div class="mb-3">
               <label for="content_eng_news" class="form-label small fw-medium">Isi Berita (English)</label>
               <textarea name="content_eng" id="content_eng_news" rows="4" class="form-control rounded-3 bg-body-tertiary" placeholder="Write full press release content in English..."></textarea>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Simpan Berita</button>
         </div>
      </form>
   </div>
</div>

{{-- MODAL EDIT NEWS (EACH ITEM) --}}
@foreach($newsList as $news)
<div class="modal fade" id="modalEditNews{{ $news->id }}" tabindex="-1" aria-labelledby="modalEditNewsLabel{{ $news->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.news.update', $news->id) }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         @method('PUT')
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalEditNewsLabel{{ $news->id }}">Edit Artikel Berita</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <div class="row g-3 mb-3">
               <div class="col-md-6">
                  <label class="form-label small fw-medium">Judul Berita (Indonesian) *</label>
                  <input type="text" name="judul" value="{{ $news->judul }}" class="form-control rounded-pill bg-body-tertiary" required>
               </div>
               <div class="col-md-6">
                  <label class="form-label small fw-medium">Judul Berita (English)</label>
                  <input type="text" name="judul_eng" value="{{ $news->judul_eng }}" class="form-control rounded-pill bg-body-tertiary">
               </div>
            </div>

            <div class="row g-3 mb-3">
               <div class="col-md-6">
                  <label class="form-label small fw-medium">Tanggal Publikasi (ID)</label>
                  <input type="text" name="tanggal" value="{{ $news->tanggal }}" class="form-control rounded-pill bg-body-tertiary">
               </div>
               <div class="col-md-6">
                  <label class="form-label small fw-medium">Tanggal Publikasi (EN)</label>
                  <input type="text" name="tanggal_eng" value="{{ $news->tanggal_eng }}" class="form-control rounded-pill bg-body-tertiary">
               </div>
            </div>

            <div class="mb-3">
               <label class="form-label small fw-medium">Slug URL *</label>
               <input type="text" name="slug" value="{{ $news->slug }}" class="form-control rounded-pill bg-body-tertiary" required>
            </div>

            <div class="mb-3">
               <label class="form-label small fw-medium">Ganti Gambar Featured (Biarkan kosong jika tidak diubah)</label>
               <div class="d-flex align-items-center gap-3 mb-2">
                  <img src="{{ $news->image_url }}" alt="Preview" style="height: 50px; width: 80px; object-fit: cover;" class="rounded border">
                  <input type="file" name="image" class="form-control rounded-pill bg-body-tertiary" accept="image/*">
               </div>
            </div>

            <div class="mb-3">
               <label class="form-label small fw-medium">Isi Berita (Indonesian)</label>
               <textarea name="content" rows="4" class="form-control rounded-3 bg-body-tertiary">{{ $news->content }}</textarea>
            </div>

            <div class="mb-3">
               <label class="form-label small fw-medium">Isi Berita (English)</label>
               <textarea name="content_eng" rows="4" class="form-control rounded-3 bg-body-tertiary">{{ $news->content_eng }}</textarea>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Perbarui Berita</button>
         </div>
      </form>
   </div>
</div>
@endforeach

{{-- MODAL IMPORT EXCEL NEWS --}}
<div class="modal fade" id="importNewsModal" tabindex="-1" aria-labelledby="importNewsModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('admin.news.import') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="importNewsModalLabel">Import Data Berita Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <p class="small text-secondary mb-3">Upload file format <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code> berisi data berita.</p>
            <div class="mb-3">
               <label for="excel_file_nws" class="form-label fw-bold small">File Excel *</label>
               <input type="file" name="file" id="excel_file_nws" class="form-control rounded-pill bg-body-tertiary" required accept=".xlsx,.xls,.csv">
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
