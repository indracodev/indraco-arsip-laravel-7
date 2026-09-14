@extends('layouts.app')

@section('title', 'INDRACO – Master Type')

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
            <h1 class="h3 fw-bold mb-1">Master Data Type</h1>
            <p class="text-secondary small mb-0">Kelola tipe variasi kemasan produk INDRACO Coffee.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.produk.template') }}" class="btn btn-outline-secondary rounded-pill px-3">Download Template Excel</a>
            <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importTypeModal">Import Excel</button>
            <button type="button" class="btn btn-custom-1 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahType">+ Tambah Type Baru</button>
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
            <form action="{{ route('admin.type.index') }}" method="GET" class="row g-2 mb-4">
               <div class="col-md-6">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari nama type..." value="{{ request('search') }}">
               </div>
               <div class="col-md-4">
                  <select name="collection_id" class="form-select select-search rounded-pill px-3 bg-body-tertiary">
                     <option value="">Semua Collection</option>
                     @foreach($collections as $col)
                        <option value="{{ $col->id }}" {{ request('collection_id') == $col->id ? 'selected' : '' }}>{{ $col->collection_name }}</option>
                     @endforeach
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
                        <th>Type Name</th>
                        <th>Collection</th>
                        <th>Slug</th>
                        <th class="text-end">Aksi CRUD</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($types as $t)
                        <tr>
                           <td class="fw-bold small">{{ $t->type_name }}</td>
                           <td class="small"><span class="badge text-bg-light">{{ $t->collection->collection_name ?? '-' }}</span></td>
                           <td class="small"><code>{{ $t->slug }}</code></td>
                           <td class="text-end">
                              <div class="d-flex align-items-center justify-content-end gap-2">
                                 <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowType{{ $t->id }}">👁️ View</button>
                                 <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditType{{ $t->id }}">✏️ Edit</button>
                                 <form action="{{ route('admin.type.destroy', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus type ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                 </form>
                              </div>
                           </td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="4" class="text-center py-4 text-muted">Belum ada data type.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>

            <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
            <div class="d-block d-md-none">
               @forelse($types as $t)
                  <div class="mobile-data-card">
                     <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-custom-1 text-white me-1">{{ $t->collection->collection_name ?? 'Collection' }}</span>
                     </div>
                     <h3 class="fs-6 fw-bold mb-1">{{ $t->type_name }}</h3>
                     <p class="text-secondary small mb-2">Slug: {{ $t->slug }}</p>
                     <div class="d-flex gap-2 mt-2 pt-2 border-top justify-content-end">
                        <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowType{{ $t->id }}">👁️ View</button>
                        <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditType{{ $t->id }}">✏️ Edit</button>
                        <form action="{{ route('admin.type.destroy', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus type?')">
                           @csrf
                           @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                        </form>
                     </div>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Belum ada data type.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $types->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>

{{-- MODAL TAMBAH TYPE --}}
<div class="modal fade" id="modalTambahType" tabindex="-1" aria-labelledby="modalTambahTypeLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('admin.type.store') }}" method="POST" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalTambahTypeLabel">🏷️ Tambah Type Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="mb-3">
               <label for="collection_id_t" class="form-label small fw-medium">Collection <span class="text-danger">*</span></label>
               <select name="collection_id" id="collection_id_t" class="form-select select-search rounded-3 bg-body-tertiary" required>
                  <option value="">Pilih Collection</option>
                  @foreach($collections as $c)
                     <option value="{{ $c->id }}">{{ $c->collection_name }}</option>
                  @endforeach
               </select>
            </div>
            <div class="mb-3">
               <label for="type_name" class="form-label small fw-medium">Nama Type <span class="text-danger">*</span></label>
               <input type="text" name="type_name" id="type_name" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Drip Bag Coffee 10g, Whole Beans 200g" required>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">💾 Simpan Type</button>
         </div>
      </form>
   </div>
</div>

{{-- MODAL EDIT & DETAIL TYPE (Looping Data) --}}
@foreach($types as $t)

{{-- MODAL DETAIL TYPE --}}
<div class="modal fade" id="modalShowType{{ $t->id }}" tabindex="-1" aria-labelledby="modalShowTypeLabel{{ $t->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-custom-1" id="modalShowTypeLabel{{ $t->id }}">👁️ Detail Type</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <h4 class="fw-bold text-center mb-1">{{ $t->type_name }}</h4>
            <p class="text-center text-secondary small mb-3">Slug: <code>{{ $t->slug }}</code></p>
            
            <div class="bg-body-tertiary p-3 rounded-3 mt-3 small">
               <div class="mb-2"><strong>Collection Terkait:</strong> <span class="badge bg-custom-1 text-white">{{ $t->collection->collection_name ?? '-' }}</span></div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            <a href="{{ route('products.index') }}?type={{ $t->slug }}" target="_blank" class="btn btn-info text-white rounded-pill px-4">Lihat Produk Type ↗</a>
         </div>
      </div>
   </div>
</div>

<div class="modal fade" id="modalEditType{{ $t->id }}" tabindex="-1" aria-labelledby="modalEditTypeLabel{{ $t->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('admin.type.update', $t->id) }}" method="POST" class="modal-content rounded-4 border-0">
         @csrf
         @method('PUT')
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalEditTypeLabel{{ $t->id }}">✏️ Edit Type: {{ $t->type_name }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="mb-3">
               <label class="form-label small fw-medium">Collection <span class="text-danger">*</span></label>
               <select name="collection_id" class="form-select select-search rounded-3 bg-body-tertiary" required>
                  <option value="">Pilih Collection</option>
                  @foreach($collections as $c)
                     <option value="{{ $c->id }}" {{ $t->collection_id == $c->id ? 'selected' : '' }}>{{ $c->collection_name }}</option>
                  @endforeach
               </select>
            </div>
            <div class="mb-3">
               <label class="form-label small fw-medium">Nama Type <span class="text-danger">*</span></label>
               <input type="text" name="type_name" class="form-control rounded-3 bg-body-tertiary" value="{{ $t->type_name }}" required>
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

{{-- MODAL IMPORT EXCEL TYPE --}}
<div class="modal fade" id="importTypeModal" tabindex="-1" aria-labelledby="importTypeModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <form action="#" onsubmit="event.preventDefault(); alert('Type Excel Berhasil Diimpor!'); bootstrap.Modal.getInstance(document.getElementById('importTypeModal')).hide();" class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="importTypeModalLabel">Import Data Type Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <p class="small text-secondary mb-3">Upload file format <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code> data type.</p>
            <div class="mb-3">
               <label for="excel_file_typ" class="form-label fw-bold small">File Excel</label>
               <input type="file" id="excel_file_typ" class="form-control rounded-pill bg-body-tertiary" required accept=".xlsx,.xls,.csv">
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
