@extends('layouts.app')

@section('title', 'INDRACO – Master Kategori')

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
      .kat-ikon-trigger:hover img {
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
            <h1 class="h3 fw-bold mb-1">Master Data Kategori</h1>
            <p class="text-secondary small mb-0">Kelola hirarki kategori &amp; sub-kategori produk INDRACO Coffee.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.produk.template') }}" class="btn btn-outline-secondary rounded-pill px-3">Download Template Excel</a>
            <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importKategoriModal">Import Excel</button>
            <button type="button" class="btn btn-custom-1 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">+ Tambah Kategori Baru</button>
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
            <form action="{{ route('admin.kategori.index') }}" method="GET" class="row g-2 mb-4">
               <div class="col-md-6">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari nama kategori / slug..." value="{{ request('search') }}">
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

            <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
            <div class="table-responsive d-none d-md-block">
               <table class="table table-hover table-custom align-middle mb-0">
                  <thead>
                     <tr>
                        <th style="width: 60px;">Ikon</th>
                        <th>Nama Kategori</th>
                        <th>Parent Kategori</th>
                        <th>Slug</th>
                        <th>Urutan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi CRUD</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($kategories as $kat)
                        <tr>
                           <td>
                              <a href="#" data-bs-toggle="modal" data-bs-target="#modalIkonKategori{{ $kat->id }}" title="Klik untuk lihat & ganti ikon/gambar" class="d-inline-block kat-ikon-trigger">
                                 @if($kat->ikon_path)
                                    <img src="{{ asset($kat->ikon_path) }}" alt="{{ $kat->nama_kategori }}" class="rounded shadow-sm border p-1" style="width: 45px; height: 45px; object-fit: contain; cursor: pointer; transition: transform 0.2s ease;">
                                 @else
                                    <span class="badge text-bg-light border p-2" style="cursor: pointer;">📁 Ikon</span>
                                 @endif
                              </a>
                           </td>
                           <td class="fw-bold small">{{ $kat->nama_kategori }}</td>
                           <td class="small"><span class="badge text-bg-light">{{ $kat->parent->nama_kategori ?? 'Root (Kategori Utama)' }}</span></td>
                           <td class="small"><code>{{ $kat->slug }}</code></td>
                           <td class="small"><span class="badge text-bg-secondary">{{ $kat->urutan ?? 0 }}</span></td>
                           <td>
                              <span class="badge {{ $kat->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                 {{ ucfirst($kat->status) }}
                              </span>
                           </td>
                           <td class="text-end">
                              <div class="d-flex align-items-center justify-content-end gap-2">
                                 <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowKategori{{ $kat->id }}">👁️ View</button>
                                 <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditKategori{{ $kat->id }}">✏️ Edit</button>
                                 <form action="{{ route('admin.kategori.destroy', $kat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                 </form>
                              </div>
                           </td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="7" class="text-center py-4 text-muted">Data kategori tidak ditemukan.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>

            <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
            <div class="d-block d-md-none">
               @forelse($kategories as $kat)
                  <div class="mobile-data-card">
                     <div class="d-flex align-items-center gap-3 mb-2">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modalIkonKategori{{ $kat->id }}" title="Klik untuk lihat & ganti ikon" class="flex-shrink-0">
                           @if($kat->ikon_path)
                              <img src="{{ asset($kat->ikon_path) }}" alt="{{ $kat->nama_kategori }}" class="rounded shadow-sm border p-1" style="width: 50px; height: 50px; object-fit: contain;">
                           @else
                              <span class="badge text-bg-light border p-2" style="cursor: pointer;">📁 Ikon</span>
                           @endif
                        </a>
                        <div class="flex-grow-1">
                           <h3 class="fs-6 fw-bold mb-0">{{ $kat->nama_kategori }}</h3>
                           <small class="text-secondary d-block">Parent: {{ $kat->parent->nama_kategori ?? 'Root (Utama)' }}</small>
                           <small class="text-secondary d-block">Slug: {{ $kat->slug }}</small>
                        </div>
                        <span class="badge {{ $kat->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                           {{ ucfirst($kat->status) }}
                        </span>
                     </div>
                     <div class="d-flex gap-2 mt-2 pt-2 border-top justify-content-end">
                        <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalShowKategori{{ $kat->id }}">👁️ View</button>
                        <button type="button" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#modalEditKategori{{ $kat->id }}">✏️ Edit</button>
                        <form action="{{ route('admin.kategori.destroy', $kat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori?')">
                           @csrf
                           @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                        </form>
                     </div>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Data kategori tidak ditemukan.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $kategories->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>

{{-- MODAL TAMBAH KATEGORI --}}
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-labelledby="modalTambahKategoriLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.kategori.store') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalTambahKategoriLabel">📁 Tambah Kategori Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-3">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label for="nama_kategori_new" class="form-label small fw-medium">Nama Kategori <span class="text-danger">*</span></label>
                     <input type="text" name="nama_kategori" id="nama_kategori_new" class="form-control rounded-3 bg-body-tertiary" placeholder="contoh: Speciality Coffee" required>
                  </div>
                  <div class="mb-3">
                     <label for="parent_id_new" class="form-label small fw-medium">Parent Kategori (Opsional)</label>
                     <select name="parent_id" id="parent_id_new" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="">Kategori Utama (Root)</option>
                        @foreach($allKategories as $pk)
                           <option value="{{ $pk->id }}">{{ $pk->nama_kategori }}</option>
                        @endforeach
                     </select>
                  </div>
                  <div class="mb-3">
                     <label for="ikon_new" class="form-label small fw-medium">Ikon / Gambar Kategori (Opsional)</label>
                     <input type="file" name="ikon" id="ikon_new" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                     <div class="form-text" style="font-size: 0.75rem;">Format PNG/JPG/SVG/WebP, max 4MB.</div>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="mb-3">
                     <label for="urutan_new" class="form-label small fw-medium">Urutan Tampilan</label>
                     <input type="number" name="urutan" id="urutan_new" class="form-control rounded-3 bg-body-tertiary" placeholder="0" value="0">
                     <div class="form-text" style="font-size: 0.75rem;">Angka lebih kecil tampil lebih awal.</div>
                  </div>
                  <div class="mb-3">
                     <label for="status_new" class="form-label small fw-medium">Status Publikasi</label>
                     <select name="status" id="status_new" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="active" selected>Active (Aktif)</option>
                        <option value="inactive">Inactive (Nonaktif)</option>
                     </select>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">💾 Simpan Kategori</button>
         </div>
      </form>
   </div>
</div>

{{-- MODAL EDIT, DETAIL & IKON KATEGORI (Looping Data) --}}
@foreach($kategories as $kat)

{{-- MODAL DETAIL KATEGORI --}}
<div class="modal fade" id="modalShowKategori{{ $kat->id }}" tabindex="-1" aria-labelledby="modalShowKategoriLabel{{ $kat->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-custom-1" id="modalShowKategoriLabel{{ $kat->id }}">👁️ Detail Kategori</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="text-center mb-3">
               @if($kat->ikon_path)
                  <img src="{{ asset($kat->ikon_path) }}" alt="{{ $kat->nama_kategori }}" class="img-fluid rounded shadow-sm border p-2" style="max-height: 100px; object-fit: contain;">
               @else
                  <div class="bg-body-tertiary rounded p-3 text-secondary border">No Icon Available</div>
               @endif
            </div>
            <h4 class="fw-bold text-center mb-1">{{ $kat->nama_kategori }}</h4>
            <p class="text-center text-secondary small mb-3">Slug: <code>{{ $kat->slug }}</code> | Parent: <span class="badge text-bg-light border">{{ $kat->parent->nama_kategori ?? 'Root (Utama)' }}</span></p>
            
            <div class="bg-body-tertiary p-3 rounded-3 mt-3 small">
               <div class="row g-2">
                  <div class="col-6"><strong>Urutan Tampil:</strong> {{ $kat->urutan ?? 0 }}</div>
                  <div class="col-6"><strong>Status:</strong> <span class="badge {{ $kat->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($kat->status) }}</span></div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            <a href="{{ route('products.index') }}?category={{ $kat->slug }}" target="_blank" class="btn btn-info text-white rounded-pill px-4">Lihat Produk Kategori ↗</a>
         </div>
      </div>
   </div>
</div>

{{-- MODAL EDIT KATEGORI --}}
<div class="modal fade" id="modalEditKategori{{ $kat->id }}" tabindex="-1" aria-labelledby="modalEditKategoriLabel{{ $kat->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <form action="{{ route('admin.kategori.update', $kat->id) }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         @method('PUT')
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalEditKategoriLabel{{ $kat->id }}">✏️ Edit Kategori: {{ $kat->nama_kategori }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-3">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Nama Kategori <span class="text-danger">*</span></label>
                     <input type="text" name="nama_kategori" class="form-control rounded-3 bg-body-tertiary" value="{{ $kat->nama_kategori }}" required>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Parent Kategori (Opsional)</label>
                     <select name="parent_id" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="">Kategori Utama (Root)</option>
                        @foreach($allKategories as $pk)
                           @if($pk->id != $kat->id)
                              <option value="{{ $pk->id }}" {{ $kat->parent_id == $pk->id ? 'selected' : '' }}>{{ $pk->nama_kategori }}</option>
                           @endif
                        @endforeach
                     </select>
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Ganti Ikon / Gambar (Opsional)</label>
                     <input type="file" name="ikon" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                     @if($kat->ikon_path)
                        <div class="mt-2 p-2 text-center bg-white rounded border">
                           <img src="{{ asset($kat->ikon_path) }}" alt="" style="height: 45px; object-fit: contain;">
                           <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">Ikon Saat Ini</small>
                        </div>
                     @endif
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Urutan Tampilan</label>
                     <input type="number" name="urutan" class="form-control rounded-3 bg-body-tertiary" value="{{ $kat->urutan ?? 0 }}">
                  </div>
                  <div class="mb-3">
                     <label class="form-label small fw-medium">Status Publikasi</label>
                     <select name="status" class="form-select select-search rounded-3 bg-body-tertiary">
                        <option value="active" {{ $kat->status == 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                        <option value="inactive" {{ $kat->status == 'inactive' ? 'selected' : '' }}>Inactive (Nonaktif)</option>
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

{{-- MODAL PREVIEW & UPLOAD IKON KATEGORI --}}
<div class="modal fade" id="modalIkonKategori{{ $kat->id }}" tabindex="-1" aria-labelledby="modalIkonKategoriLabel{{ $kat->id }}" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('admin.kategori.update-ikon', $kat->id) }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
         @csrf
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="modalIkonKategoriLabel{{ $kat->id }}">📁 Ikon Kategori: {{ $kat->nama_kategori }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body text-center p-4">
            <div class="p-4 bg-white rounded-4 border shadow-sm mb-3 position-relative d-inline-block w-100" style="max-height: 220px; overflow: hidden;">
               @if($kat->ikon_path)
                  <img src="{{ asset($kat->ikon_path) }}" alt="{{ $kat->nama_kategori }}" class="img-fluid rounded" style="max-height: 150px; object-fit: contain;">
               @else
                  <div class="py-4 text-muted">
                     <div style="font-size: 3rem;">📁</div>
                     <p class="mb-0 small">Belum ada ikon untuk kategori ini</p>
                  </div>
               @endif
            </div>

            <div class="text-start mb-3 bg-body-tertiary p-3 rounded-3 border">
               <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong class="small">{{ $kat->nama_kategori }}</strong>
                  <span class="badge {{ $kat->status == 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($kat->status) }}</span>
               </div>
               <small class="text-secondary d-block">Parent: <strong>{{ $kat->parent->nama_kategori ?? 'Root (Kategori Utama)' }}</strong> | Slug: <code>{{ $kat->slug }}</code></small>
            </div>

            <div class="text-start">
               <label for="ikon_modal_{{ $kat->id }}" class="form-label small fw-bold text-custom-1">Upload Ikon / Gambar Baru (PNG, JPG, SVG, WebP)</label>
               <input type="file" name="ikon" id="ikon_modal_{{ $kat->id }}" class="form-control rounded-3 bg-body-tertiary" accept="image/*" required>
               <div class="form-text" style="font-size: 0.75rem;">File max 4MB. Ikon baru akan menggantikan gambar kategori saat ini.</div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-custom-1 rounded-pill px-4">📸 Upload & Ganti Ikon</button>
         </div>
      </form>
   </div>
</div>

@endforeach

{{-- MODAL IMPORT EXCEL KATEGORI --}}
<div class="modal fade" id="importKategoriModal" tabindex="-1" aria-labelledby="importKategoriModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <form action="#" onsubmit="event.preventDefault(); alert('Kategori Excel Berhasil Diimpor!'); bootstrap.Modal.getInstance(document.getElementById('importKategoriModal')).hide();" class="modal-content rounded-4 border-0">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold" id="importKategoriModalLabel">Import Data Kategori Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <p class="small text-secondary mb-3">Upload file format <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code> data kategori.</p>
            <div class="mb-3">
               <label for="excel_file_kat" class="form-label fw-bold small">File Excel</label>
               <input type="file" id="excel_file_kat" class="form-control rounded-pill bg-body-tertiary" required accept=".xlsx,.xls,.csv">
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
