@extends('layouts.app')

@section('title', 'INDRACO – Master Variant')

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
            <h1 class="h3 fw-bold mb-1">Master Data Variant</h1>
            <p class="text-secondary small mb-0">Kelola profil rasa, acidity, body, dan tingkat sangrai (roast) varian produk.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.produk.template') }}" class="btn btn-outline-secondary rounded-pill px-3">Download Template Excel</a>
            <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importVariantModal">Import Excel</button>
            <button type="button" class="btn btn-custom-1 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahVariant">+ Tambah Variant Baru</button>
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
            <form action="{{ route('admin.variant.index') }}" method="GET" class="row g-2 mb-4">
               <div class="col-md-5">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari nama variant / taste..." value="{{ request('search') }}">
               </div>
               <div class="col-md-3">
                  <select name="type_id" class="form-select select-search rounded-pill px-3 bg-body-tertiary">
                     <option value="">Semua Type</option>
                     @foreach($types as $t)
                        <option value="{{ $t->id }}" {{ request('type_id') == $t->id ? 'selected' : '' }}>{{ $t->type_name }}</option>
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
                        <th>Variant Name</th>
                        <th>Type</th>
                        <th>Taste</th>
                        <th>Acidity</th>
                        <th>Body</th>
                        <th>Roast</th>
                        <th class="text-end">Aksi CRUD</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($variants as $v)
                        <tr>
                           <td class="fw-bold small">{{ $v->variant_name }}</td>
                           <td class="small"><span class="badge text-bg-light">{{ $v->type->type_name ?? '-' }}</span></td>
                           <td class="small">{{ $v->taste ?? '-' }}</td>
                           <td class="small">{{ $v->acidity ?? '-' }}</td>
                           <td class="small">{{ $v->body ?? '-' }}</td>
                           <td class="small">{{ $v->roast ?? '-' }}</td>
                           <td class="text-end">
                              <div class="d-flex align-items-center justify-content-end gap-2">
                                 <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowVariant{{ $v->id }}">👁️ View</button>
                                 <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditVariant{{ $v->id }}">✏️ Edit</button>
                                 <form action="{{ route('admin.variant.destroy', $v->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus variant ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                 </form>
                              </div>
                           </td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="7" class="text-center py-4 text-muted">Belum ada data variant.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>

            <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
            <div class="d-block d-md-none">
               @forelse($variants as $v)
                  <div class="mobile-data-card">
                     <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-custom-1 text-white me-1">{{ $v->type->type_name ?? 'Type' }}</span>
                        <span class="badge {{ $v->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                           {{ ucfirst($v->status ?? 'active') }}
                        </span>
                     </div>
                     <h3 class="fs-6 fw-bold mb-1">{{ $v->variant_name }}</h3>
                     <p class="text-secondary small mb-1">Taste: {{ $v->taste ?? '-' }}</p>
                     <small class="text-secondary d-block">Acidity: {{ $v->acidity ?? '-' }} | Body: {{ $v->body ?? '-' }} | Roast: {{ $v->roast ?? '-' }}</small>
                     <div class="d-flex gap-2 mt-2 pt-2 border-top justify-content-end">
                        <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowVariant{{ $v->id }}">👁️ View</button>
                        <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditVariant{{ $v->id }}">✏️ Edit</button>
                        <form action="{{ route('admin.variant.destroy', $v->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus variant?')">
                           @csrf
                           @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                        </form>
                     </div>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Belum ada data variant.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $variants->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>

{{-- MODAL TAMBAH VARIANT --}}
<div class="modal fade" id="modalTambahVariant" tabindex="-1" aria-labelledby="modalTambahVariantLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.variant.store') }}" method="POST" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalTambahVariantLabel">✨ Tambah Variant Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-3">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label for="type_id_v" class="form-label small fw-medium">Type <span class="text-danger">*</span></label>
                     <select name="type_id" id="type_id_v" class="form-select select-search rounded-3 bg-body-tertiary" required>
                        <option value="">Pilih Type</option>
                        @foreach($types as $t)
                           <option value="{{ $t->id }}">{{ $t->type_name }}</option>
                        @endforeach
                     </select>
                  </div>
                  <div class="mb-3">
                     <label for="variant_name" class="form-label small fw-medium">Nama Variant <span class="text-danger">*</span></label>
                     <input type="text" name="variant_name" id="variant_name" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Sumatra Mandheling Single Origin" required>
                  </div>
                  <div class="mb-3">
                     <label for="taste" class="form-label small fw-medium">Taste Profile</label>
                     <input type="text" name="taste" id="taste" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Herbal, Chocolatey, Spicy">
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="row g-2 mb-3">
                     <div class="col-md-4">
                        <label for="acidity" class="form-label small fw-medium">Acidity</label>
                        <input type="text" name="acidity" id="acidity" class="form-control rounded-3 bg-body-tertiary" placeholder="Low / Med">
                     </div>
                     <div class="col-md-4">
                        <label for="body" class="form-label small fw-medium">Body</label>
                        <input type="text" name="body" id="body" class="form-control rounded-3 bg-body-tertiary" placeholder="Full / Medium">
                     </div>
                     <div class="col-md-4">
                        <label for="roast" class="form-label small fw-medium">Roast Level</label>
                        <input type="text" name="roast" id="roast" class="form-control rounded-3 bg-body-tertiary" placeholder="Dark / Medium">
                     </div>
                  </div>
                  <div class="mb-3">
                     <label for="status_v" class="form-label small fw-medium">Status Publikasi</label>
                     <select name="status" id="status_v" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="active" selected>Active (Aktif)</option>
                        <option value="inactive">Inactive (Nonaktif)</option>
                     </select>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">💾 Simpan Variant</button>
         </div>
      </form>
   </div>
</div>

{{-- MODAL EDIT & DETAIL VARIANT (Looping Data) --}}
@foreach($variants as $v)

{{-- MODAL DETAIL VARIANT --}}
<div class="modal fade" id="modalShowVariant{{ $v->id }}" tabindex="-1" aria-labelledby="modalShowVariantLabel{{ $v->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-custom-1" id="modalShowVariantLabel{{ $v->id }}">👁️ Detail Variant</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <h4 class="fw-bold text-center mb-1">{{ $v->variant_name }}</h4>
            <p class="text-center text-secondary small mb-3">Slug: <code>{{ $v->slug }}</code> | Type: <span class="badge bg-custom-1 text-white">{{ $v->type->type_name ?? '-' }}</span></p>
            
            <div class="bg-body-tertiary p-3 rounded-3 mt-3 small">
               <div class="row g-2">
                  <div class="col-6"><strong>Taste Profile:</strong> {{ $v->taste ?? '-' }}</div>
                  <div class="col-6"><strong>Acidity:</strong> {{ $v->acidity ?? '-' }}</div>
                  <div class="col-6"><strong>Body:</strong> {{ $v->body ?? '-' }}</div>
                  <div class="col-6"><strong>Roast Level:</strong> {{ $v->roast ?? '-' }}</div>
                  <div class="col-12 mt-1"><strong>Status:</strong> <span class="badge {{ ($v->status ?? 'active') == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($v->status ?? 'active') }}</span></div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            <a href="{{ route('products.index') }}?variant={{ $v->slug }}" target="_blank" class="btn btn-info text-white rounded-pill px-4">Lihat Produk Variant ↗</a>
         </div>
      </div>
   </div>
</div>

<div class="modal fade" id="modalEditVariant{{ $v->id }}" tabindex="-1" aria-labelledby="modalEditVariantLabel{{ $v->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.variant.update', $v->id) }}" method="POST" class="modal-content rounded-4 border-0">
         @csrf
         @method('PUT')
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalEditVariantLabel{{ $v->id }}">✏️ Edit Variant: {{ $v->variant_name }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-3">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Type <span class="text-danger">*</span></label>
                     <select name="type_id" class="form-select select-search rounded-3 bg-body-tertiary" required>
                        <option value="">Pilih Type</option>
                        @foreach($types as $t)
                           <option value="{{ $t->id }}" {{ $v->type_id == $t->id ? 'selected' : '' }}>{{ $t->type_name }}</option>
                        @endforeach
                     </select>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Nama Variant <span class="text-danger">*</span></label>
                     <input type="text" name="variant_name" class="form-control rounded-3 bg-body-tertiary" value="{{ $v->variant_name }}" required>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Taste Profile</label>
                     <input type="text" name="taste" class="form-control rounded-3 bg-body-tertiary" value="{{ $v->taste }}">
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="row g-2 mb-3">
                     <div class="col-md-4">
                        <label class="form-label small fw-medium">Acidity</label>
                        <input type="text" name="acidity" class="form-control rounded-3 bg-body-tertiary" value="{{ $v->acidity }}">
                     </div>
                     <div class="col-md-4">
                        <label class="form-label small fw-medium">Body</label>
                        <input type="text" name="body" class="form-control rounded-3 bg-body-tertiary" value="{{ $v->body }}">
                     </div>
                     <div class="col-md-4">
                        <label class="form-label small fw-medium">Roast Level</label>
                        <input type="text" name="roast" class="form-control rounded-3 bg-body-tertiary" value="{{ $v->roast }}">
                     </div>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Status Publikasi</label>
                     <select name="status" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="active" {{ ($v->status ?? 'active') == 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                        <option value="inactive" {{ ($v->status ?? 'active') == 'inactive' ? 'selected' : '' }}>Inactive (Nonaktif)</option>
                     </select>
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

{{-- MODAL IMPORT EXCEL VARIANT --}}
<div class="modal fade" id="importVariantModal" tabindex="-1" aria-labelledby="importVariantModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <form action="#" onsubmit="event.preventDefault(); alert('Variant Excel Berhasil Diimpor!'); bootstrap.Modal.getInstance(document.getElementById('importVariantModal')).hide();" class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="importVariantModalLabel">Import Data Variant Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <p class="small text-secondary mb-3">Upload file format <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code> data variant.</p>
            <div class="mb-3">
               <label for="excel_file_var" class="form-label fw-bold small">File Excel</label>
               <input type="file" id="excel_file_var" class="form-control rounded-pill bg-body-tertiary" required accept=".xlsx,.xls,.csv">
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
