@extends('layouts.app')

@section('title', 'INDRACO – Master Collection')

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
            <h1 class="h3 fw-bold mb-1">Master Data Collection</h1>
            <p class="text-secondary small mb-0">Kelola koleksi seri produk per merek INDRACO Coffee.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.produk.template') }}" class="btn btn-outline-secondary rounded-pill px-3">Download Template Excel</a>
            <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importCollectionModal">Import Excel</button>
            <button type="button" class="btn btn-custom-1 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahCollection">+ Tambah Collection Baru</button>
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
            
            <!-- Filter & Search Bar -->
            <form action="{{ route('admin.collection.index') }}" method="GET" class="row g-2 mb-4">
               <div class="col-md-5">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari nama collection..." value="{{ request('search') }}">
               </div>
               <div class="col-md-3">
                  <select name="merek_id" class="form-select select-search rounded-pill px-3 bg-body-tertiary">
                     <option value="">Semua Merek</option>
                     @foreach($mereks as $m)
                        <option value="{{ $m->id }}" {{ request('merek_id') == $m->id ? 'selected' : '' }}>{{ $m->nama_merek }}</option>
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

            <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
            <div class="table-responsive d-none d-md-block">
               <table class="table table-hover table-custom align-middle mb-0">
                  <thead>
                     <tr>
                        <th>Collection Name</th>
                        <th>Merek</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th class="text-end">Aksi CRUD</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($collections as $col)
                        <tr>
                           <td class="fw-bold small">{{ $col->collection_name }}</td>
                           <td class="small"><span class="badge text-bg-light">{{ $col->merek->nama_merek ?? '-' }}</span></td>
                           <td class="small"><code>{{ $col->slug }}</code></td>
                           <td>
                              <span class="badge {{ $col->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                 {{ ucfirst($col->status) }}
                              </span>
                           </td>
                           <td class="text-end">
                              <div class="d-flex align-items-center justify-content-end gap-2">
                                 <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowCollection{{ $col->id }}">👁️ View</button>
                                 <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditCollection{{ $col->id }}">✏️ Edit</button>
                                 <form action="{{ route('admin.collection.destroy', $col->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus collection ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                 </form>
                              </div>
                           </td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="5" class="text-center py-4 text-muted">Belum ada data collection.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>

            <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
            <div class="d-block d-md-none">
               @forelse($collections as $col)
                  <div class="mobile-data-card">
                     <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-custom-1 text-white me-1">{{ $col->merek->nama_merek ?? 'Merek' }}</span>
                        <span class="badge {{ $col->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                           {{ ucfirst($col->status) }}
                        </span>
                     </div>
                     <h3 class="fs-6 fw-bold mb-1">{{ $col->collection_name }}</h3>
                     <p class="text-secondary small mb-2">Slug: {{ $col->slug }}</p>
                     <div class="d-flex gap-2 mt-2 pt-2 border-top justify-content-end">
                        <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowCollection{{ $col->id }}">👁️ View</button>
                        <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditCollection{{ $col->id }}">✏️ Edit</button>
                        <form action="{{ route('admin.collection.destroy', $col->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus collection?')">
                           @csrf
                           @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                        </form>
                     </div>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Belum ada data collection.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $collections->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>

{{-- MODAL TAMBAH COLLECTION --}}
<div class="modal fade" id="modalTambahCollection" tabindex="-1" aria-labelledby="modalTambahCollectionLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('admin.collection.store') }}" method="POST" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalTambahCollectionLabel">✨ Tambah Collection Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="mb-3">
               <label for="merek_id_col" class="form-label small fw-medium">Merek / Brand <span class="text-danger">*</span></label>
               <select name="merek_id" id="merek_id_col" class="form-select select-search rounded-3 bg-body-tertiary" required>
                  <option value="">Pilih Merek</option>
                  @foreach($mereks as $m)
                     <option value="{{ $m->id }}">{{ $m->nama_merek }}</option>
                  @endforeach
               </select>
            </div>
            <div class="mb-3">
               <label for="collection_name" class="form-label small fw-medium">Nama Collection <span class="text-danger">*</span></label>
               <input type="text" name="collection_name" id="collection_name" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Rainforest Series, Special Blend" required>
            </div>
            <div class="mb-3">
               <label for="status_col" class="form-label small fw-medium">Status Publikasi</label>
               <select name="status" id="status_col" class="form-select select-search rounded-3 bg-body-tertiary">
                  <option value="active" selected>Active (Aktif)</option>
                  <option value="inactive">Inactive (Nonaktif)</option>
               </select>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">💾 Simpan Collection</button>
         </div>
      </form>
   </div>
</div>

{{-- MODAL EDIT & DETAIL COLLECTION (Looping Data) --}}
@foreach($collections as $col)

{{-- MODAL DETAIL COLLECTION --}}
<div class="modal fade" id="modalShowCollection{{ $col->id }}" tabindex="-1" aria-labelledby="modalShowCollectionLabel{{ $col->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-custom-1" id="modalShowCollectionLabel{{ $col->id }}">👁️ Detail Collection</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <h4 class="fw-bold text-center mb-1">{{ $col->collection_name }}</h4>
            <p class="text-center text-secondary small mb-3">Slug: <code>{{ $col->slug }}</code> | Status: <span class="badge {{ $col->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($col->status) }}</span></p>
            
            <div class="bg-body-tertiary p-3 rounded-3 mt-3 small">
               <div class="mb-2"><strong>Merek Terkait:</strong> <span class="badge bg-custom-1 text-white">{{ $col->merek->nama_merek ?? '-' }}</span></div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            <a href="{{ route('products.index') }}?collection={{ $col->slug }}" target="_blank" class="btn btn-info text-white rounded-pill px-4">Lihat Produk Collection ↗</a>
         </div>
      </div>
   </div>
</div>

<div class="modal fade" id="modalEditCollection{{ $col->id }}" tabindex="-1" aria-labelledby="modalEditCollectionLabel{{ $col->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('admin.collection.update', $col->id) }}" method="POST" class="modal-content rounded-4 border-0">
         @csrf
         @method('PUT')
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalEditCollectionLabel{{ $col->id }}">✏️ Edit Collection: {{ $col->collection_name }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="mb-3">
               <label class="form-label small fw-medium">Merek / Brand <span class="text-danger">*</span></label>
               <select name="merek_id" class="form-select select-search rounded-3 bg-body-tertiary" required>
                  <option value="">Pilih Merek</option>
                  @foreach($mereks as $m)
                     <option value="{{ $m->id }}" {{ $col->merek_id == $m->id ? 'selected' : '' }}>{{ $m->nama_merek }}</option>
                  @endforeach
               </select>
            </div>
            <div class="mb-3">
               <label class="form-label small fw-medium">Nama Collection <span class="text-danger">*</span></label>
               <input type="text" name="collection_name" class="form-control rounded-3 bg-body-tertiary" value="{{ $col->collection_name }}" required>
            </div>
            <div class="mb-3">
               <label class="form-label small fw-medium">Status Publikasi</label>
               <select name="status" class="form-select select-search rounded-3 bg-body-tertiary">
                  <option value="active" {{ $col->status == 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                  <option value="inactive" {{ $col->status == 'inactive' ? 'selected' : '' }}>Inactive (Nonaktif)</option>
               </select>
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

{{-- MODAL IMPORT EXCEL COLLECTION --}}
<div class="modal fade" id="importCollectionModal" tabindex="-1" aria-labelledby="importCollectionModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <form action="#" onsubmit="event.preventDefault(); alert('Collection Excel Berhasil Diimpor!'); bootstrap.Modal.getInstance(document.getElementById('importCollectionModal')).hide();" class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="importCollectionModalLabel">Import Data Collection Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <p class="small text-secondary mb-3">Upload file format <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code> data collection.</p>
            <div class="mb-3">
               <label for="excel_file_col" class="form-label fw-bold small">File Excel</label>
               <input type="file" id="excel_file_col" class="form-control rounded-pill bg-body-tertiary" required accept=".xlsx,.xls,.csv">
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
