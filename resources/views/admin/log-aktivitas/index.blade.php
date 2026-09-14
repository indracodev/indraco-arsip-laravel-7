@extends('layouts.app')

@section('title', 'INDRACO – Audit Log Aktivitas')

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
            <h1 class="h3 fw-bold mb-1">Audit Log Jejak Sistem &amp; Admin</h1>
            <p class="text-secondary small mb-0">Catatan histori aktivitas perubahan data, IP Address, User Agent, dan jejak waktu real-time.</p>
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
            <form action="{{ route('admin.log-aktivitas.index') }}" method="GET" class="row g-2 mb-4">
               <div class="col-md-9">
                  <input type="text" name="search" class="form-control rounded-pill px-3 bg-body-tertiary" placeholder="Cari aktivitas / IP address..." value="{{ request('search') }}">
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
                        <th>User Admin</th>
                        <th>Aktivitas</th>
                        <th>Model</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($logs as $log)
                        <tr>
                           <td class="small text-secondary">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                           <td class="fw-bold small">{{ $log->user->name ?? 'System' }}</td>
                           <td><span class="badge text-bg-light border">{{ $log->aktivitas }}</span></td>
                           <td class="small"><code>{{ $log->model ?? '-' }}</code></td>
                           <td class="small text-secondary">{{ $log->ip_address ?? '-' }}</td>
                           <td class="small text-secondary" style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $log->user_agent ?? '-' }}</td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="6" class="text-center py-4 text-muted">Belum ada log aktivitas tercatat.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>

            <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
            <div class="d-block d-md-none">
               @forelse($logs as $log)
                  <div class="mobile-data-card">
                     <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="fw-bold small">{{ $log->user->name ?? 'System' }}</span>
                        <span class="small text-secondary">{{ $log->created_at->format('H:i d/m') }}</span>
                     </div>
                     <p class="mb-1 small text-custom-1 fw-semibold">{{ $log->aktivitas }}</p>
                     <small class="text-secondary d-block">Model: {{ $log->model ?? '-' }} &bull; IP: {{ $log->ip_address ?? '-' }}</small>
                  </div>
               @empty
                  <p class="text-muted text-center py-4">Belum ada log aktivitas tercatat.</p>
               @endforelse
            </div>

            <div class="mt-4">
               {{ $logs->links('vendor.pagination.custom') }}
            </div>
         </section>
      </div>
   </div>
</main>
@endsection
