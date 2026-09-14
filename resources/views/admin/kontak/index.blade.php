@extends('layouts.app')

@section('title', 'INDRACO – Pesan Kontak Masuk')

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

   <!-- Header Banner -->
   <section aria-label="Welcome Admin" class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm mb-4">
      <div class="row align-items-center gy-3">
         <div class="col-12 col-md">
            <span class="badge bg-danger text-white mb-2 px-3 py-2 rounded-pill">Administrator Access</span>
            <h1 class="h3 fw-bold mb-1">Daftar Pesan Kontak Masuk</h1>
            <p class="text-secondary small mb-0">Kelola pesan dan enquiry calon mitra / konsumen yang dikirim melalui halaman contact website.</p>
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
            <form action="{{ route('admin.kontak.index') }}" method="GET" class="row g-2 mb-4">
               <div class="col-md-9">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari nama pengirim / email / isi pesan..." value="{{ request('search') }}">
               </div>
               <div class="col-md-3">
                  <button type="submit" class="btn btn-secondary rounded-pill w-100">Filter Search</button>
               </div>
            </form>

            <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
            <div class="table-responsive d-none d-md-block">
               <table class="table table-hover table-custom align-middle mb-0">
                  <thead>
                     <tr>
                        <th>Waktu</th>
                        <th>Nama Pengirim</th>
                        <th>Email / Telepon</th>
                        <th>Judul Pesan</th>
                        <th>Isi Pesan</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($kontaks as $k)
                        <tr>
                           <td class="small text-secondary">{{ $k->created_at->format('d M Y H:i') }}</td>
                           <td class="fw-bold small">{{ $k->nama }}</td>
                           <td class="small">
                              <a href="mailto:{{ $k->email }}" class="text-decoration-none fw-semibold">{{ $k->email }}</a>
                              <div class="text-secondary" style="font-size: 0.75rem;">{{ $k->telepon ?? '-' }}</div>
                           </td>
                           <td class="small"><span class="badge text-bg-light border">{{ $k->judul_pesan ?? 'Pertanyaan' }}</span></td>
                           <td class="small text-secondary">{{ Str::limit($k->pesan, 80) }}</td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="5" class="text-center py-4 text-muted">Belum ada pesan kontak masuk.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>

            <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
            <div class="d-block d-md-none">
               @forelse($kontaks as $k)
                  <div class="mobile-data-card">
                     <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-custom-1 text-white me-1">{{ $k->judul_pesan ?? 'Pertanyaan' }}</span>
                        <span class="small text-secondary">{{ $k->created_at->format('d M H:i') }}</span>
                     </div>
                     <h3 class="fs-6 fw-bold mb-1">{{ $k->nama }}</h3>
                     <p class="text-secondary small mb-2"><a href="mailto:{{ $k->email }}">{{ $k->email }}</a> &bull; {{ $k->telepon ?? '-' }}</p>
                     <p class="small text-body border-top pt-2 mt-2 mb-0">{{ $k->pesan }}</p>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Belum ada pesan kontak masuk.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $kontaks->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>
@endsection
