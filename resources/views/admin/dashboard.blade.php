@extends('layouts.app')

@section('title', 'INDRACO – Dashboard Admin & Grafik Kunjungan Page')

@push('styles')
   <style>
      :root {
         --custom-primary: #004b49;
         --custom-primary-rgb: 0, 75, 73;
      }
      .btn-custom-1 {
         background-color: var(--custom-primary) !important;
         border-color: var(--custom-primary) !important;
         color: #ffffff !important;
      }
      .btn-custom-1:hover, .btn-custom-1:focus, .btn-custom-1:active {
         filter: brightness(85%);
         color: #ffffff !important;
      }
      .btn-custom-1-outline {
         background-color: transparent !important;
         border: 1px solid var(--custom-primary) !important;
         color: var(--custom-primary) !important;
         font-weight: 600;
      }
      .btn-custom-1-outline:hover, .btn-custom-1-outline:focus, .btn-custom-1-outline:active {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
      }
      [data-bs-theme="dark"] .btn-custom-1-outline {
         color: var(--custom-primary) !important;
         border-color: var(--custom-primary) !important;
      }
      [data-bs-theme="dark"] .btn-custom-1-outline:hover {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
      }
      .badge-custom-1 {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
      }
      .stat-card {
         transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .stat-card:hover {
         transform: translateY(-3px);
         box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
      }
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
      [data-bs-theme="dark"] .admin-sidebar .nav-link:hover {
         background-color: rgba(var(--custom-primary-rgb), 0.2);
      }
      .admin-sidebar .nav-link.active {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
      }
      [data-bs-theme="dark"] .admin-sidebar .nav-link.active {
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
      .dashboard-tab-slider {
         overflow-x: auto;
         white-space: nowrap;
         -webkit-overflow-scrolling: touch;
         scrollbar-width: none;
      }
      .dashboard-tab-slider::-webkit-scrollbar {
         display: none;
      }
      #dashboardTab .nav-link {
         color: var(--bs-body-color);
         background-color: transparent;
         border-radius: 20px;
         padding: 8px 18px;
         font-size: 0.88rem;
         font-weight: 600;
         transition: all 0.2s ease;
         white-space: nowrap;
      }
      #dashboardTab .nav-link:hover {
         background-color: rgba(var(--custom-primary-rgb), 0.12);
         color: var(--custom-primary);
      }
      #dashboardTab .nav-link.active {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
         box-shadow: 0 4px 12px rgba(var(--custom-primary-rgb), 0.25);
      }
      [data-bs-theme="dark"] #dashboardTab .nav-link:hover {
         background-color: rgba(var(--custom-primary-rgb), 0.2);
         color: var(--custom-primary);
      }
      [data-bs-theme="dark"] #dashboardTab .nav-link.active {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
         box-shadow: 0 4px 12px rgba(var(--custom-primary-rgb), 0.25);
      }
   </style>
@endpush

@push('scripts')
   <!-- Chart.js CDN -->
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <script>
      document.addEventListener('DOMContentLoaded', function() {
         // 1. Visit Trend Line Chart
         const ctxTrend = document.getElementById('chartVisitTrend');
         const themeColorHex = '{{ \App\Models\MasterSetting::get('theme_color', '#004b49') }}';
         if (ctxTrend) {
            const ctx = ctxTrend.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(' + getComputedStyle(document.documentElement).getPropertyValue('--custom-primary-rgb').trim() + ', 0.35)');
            gradient.addColorStop(1, 'rgba(' + getComputedStyle(document.documentElement).getPropertyValue('--custom-primary-rgb').trim() + ', 0.01)');

            new Chart(ctx, {
               type: 'line',
               data: {
                  labels: {!! json_encode($chartDates) !!},
                  datasets: [{
                     label: 'Total Page Views',
                     data: {!! json_encode($chartData) !!},
                     borderColor: themeColorHex,
                     backgroundColor: gradient,
                     borderWidth: 3,
                     tension: 0.35,
                     fill: true,
                     pointBackgroundColor: themeColorHex,
                     pointRadius: 4,
                     pointHoverRadius: 6
                  }]
               },
               options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                     legend: { display: false },
                     tooltip: {
                        backgroundColor: themeColorHex,
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 10,
                        cornerRadius: 8
                     }
                  },
                  scales: {
                     y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [4, 4], color: 'rgba(128, 128, 128, 0.15)' } 
                     },
                     x: { 
                        grid: { display: false } 
                     }
                  }
               }
            });
         }

         // 2. Device Breakdown Doughnut Chart
         const ctxDevice = document.getElementById('chartDeviceBreakdown');
         if (ctxDevice) {
            new Chart(ctxDevice.getContext('2d'), {
               type: 'doughnut',
               data: {
                  labels: ['Desktop', 'Mobile', 'Tablet'],
                  datasets: [{
                     data: [{{ $desktopCount }}, {{ $mobileCount }}, {{ $tabletCount }}],
                     backgroundColor: [themeColorHex, '#fd4f00', '#0ea5e9'],
                     borderWidth: 2,
                     hoverOffset: 4
                  }]
               },
               options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                     legend: { 
                        position: 'bottom',
                        labels: { padding: 16, font: { size: 12, weight: 'bold' } }
                     }
                  },
                  cutout: '70%'
               }
            });
         }

         // 3. Real-time Live Polling Updates (Every 15 Seconds)
         setInterval(function() {
            fetch('{{ route("admin.dashboard.realtime") }}')
               .then(response => response.json())
               .then(data => {
                  const elVisits = document.getElementById('statTotalVisits');
                  const elUnique = document.getElementById('statUniqueVisitors');
                  const elToday = document.getElementById('statTodayVisits');
                  const elTop = document.getElementById('statTopPage');

                  if (elVisits) elVisits.innerText = data.totalVisits;
                  if (elUnique) elUnique.innerText = data.totalUniqueVisitors;
                  if (elToday) elToday.innerText = data.todayVisits;
                  if (elTop) elTop.innerText = data.topPageName;
               })
               .catch(err => console.log('Realtime sync skipped:', err));
         }, 15000);
      });
   </script>
@endpush

@section('content')
<main id="content" tabindex="-1" class="container py-4 my-3">

   <!-- Header & Welcome Banner -->
   <section aria-label="Welcome Admin" class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm mb-4">
      <div class="row align-items-center gy-3">
         <div class="col-12 col-md">
            <span class="badge badge-custom-1 mb-2 px-3 py-2 rounded-pill fw-semibold">Administrator Access</span>
            <h1 class="h3 fw-bold mb-1">Dashboard Portal &amp; Grafik Kunjungan Page</h1>
            <p class="text-secondary small mb-0">Kelola semua data master produk, brand, serta pantau grafik analisa traffic kunjungan halaman web secara real-time.</p>
         </div>
         <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.produk.template') }}" class="btn btn-custom-1-outline rounded-pill px-3 py-2 small">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="me-1">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
               </svg>
               Template Excel
            </a>
            <a href="{{ route('admin.produk.create') }}" class="btn btn-custom-1 rounded-pill px-4 py-2 small fw-semibold">+ Tambah Produk Baru</a>
         </div>
      </div>
   </section>

   <!-- Main Layout 2 Columns: Sidebar + Content -->
   <div class="row g-4">
      
      <!-- LEFT COLUMN: SIDEBAR MENU ADMIN -->
      <x-admin-sidebar />

      <!-- RIGHT COLUMN: ADMIN CONTENT & DATA LISTS -->
      <div class="col-12 col-lg-9">

         <!-- Stat Cards Summary: Master Data -->
         <section aria-label="Ringkasan Master Data Admin" class="mb-4">
            <div class="row g-2 g-sm-3 row-cols-2 row-cols-lg-4">
               <div class="col">
                  <a href="{{ route('admin.produk.index') }}" class="stat-card bg-body-secondary rounded-4 p-3 shadow-sm h-100 d-block text-decoration-none text-reset">
                     <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Total Produk</span>
                        <div class="p-2 bg-body rounded-3 text-custom-1">☕</div>
                     </div>
                     <h3 class="fs-4 fw-bold mb-1">{{ number_format($totalProduk) }}</h3>
                     <p class="text-success small mb-0 d-flex align-items-center justify-content-between">
                        <span>Katalog Aktif</span>
                        <span class="fw-semibold">Lihat &rarr;</span>
                     </p>
                  </a>
               </div>

               <div class="col">
                  <a href="{{ route('admin.merek.index') }}" class="stat-card bg-body-secondary rounded-4 p-3 shadow-sm h-100 d-block text-decoration-none text-reset">
                     <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Total Merek</span>
                        <div class="p-2 bg-body rounded-3 text-custom-1">🏷️</div>
                     </div>
                     <h3 class="fs-4 fw-bold mb-1">{{ number_format($totalMerek) }}</h3>
                     <p class="text-secondary small mb-0 d-flex align-items-center justify-content-between">
                        <span>Brand Portfolio</span>
                        <span class="fw-semibold">Lihat &rarr;</span>
                     </p>
                  </a>
               </div>

               <div class="col">
                  <a href="{{ route('admin.kategori.index') }}" class="stat-card bg-body-secondary rounded-4 p-3 shadow-sm h-100 d-block text-decoration-none text-reset">
                     <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Total Kategori</span>
                        <div class="p-2 bg-body rounded-3 text-custom-1">📂</div>
                     </div>
                     <h3 class="fs-4 fw-bold mb-1">{{ number_format($totalKategori) }}</h3>
                     <p class="text-success small mb-0 d-flex align-items-center justify-content-between">
                        <span>Master Kategori</span>
                        <span class="fw-semibold">Lihat &rarr;</span>
                     </p>
                  </a>
               </div>

               <div class="col">
                  <div class="stat-card bg-body-secondary rounded-4 p-3 shadow-sm h-100" data-bs-toggle="modal" data-bs-target="#modalPesanKontak" role="button">
                     <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Pesan Kontak</span>
                        <div class="p-2 bg-body rounded-3 text-custom-1">💬</div>
                     </div>
                     <h3 class="fs-4 fw-bold mb-1">{{ number_format($totalKontak) }}</h3>
                     <p class="text-primary small mb-0 d-flex align-items-center justify-content-between">
                        <span>Inquiry Masuk</span>
                        <span class="badge text-bg-primary rounded-pill">Detail</span>
                     </p>
                  </div>
               </div>
            </div>
         </section>

         <!-- Stat Cards Summary: Page Traffic Analytics -->
         <section aria-label="Ringkasan Analytics Kunjungan Page" class="mb-4">
            <div class="row g-2 g-sm-3 row-cols-2 row-cols-lg-4">
               <div class="col">
                  <div class="stat-card bg-body-secondary rounded-4 p-3 shadow-sm h-100 border-start border-4 border-info" data-bs-toggle="modal" data-bs-target="#modalTotalKunjungan" role="button">
                     <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Total Kunjungan</span>
                        <div class="p-2 bg-body rounded-3 text-info">👁️</div>
                     </div>
                     <h3 class="fs-4 fw-bold mb-1" id="statTotalVisits">{{ number_format($totalVisits) }}</h3>
                     <p class="text-info small mb-0 d-flex align-items-center justify-content-between">
                        <span>Page Views</span>
                        <span class="badge text-bg-info text-white rounded-pill">Detail</span>
                     </p>
                  </div>
               </div>

               <div class="col">
                  <div class="stat-card bg-body-secondary rounded-4 p-3 shadow-sm h-100 border-start border-4 border-primary" data-bs-toggle="modal" data-bs-target="#modalPengunjungUnik" role="button">
                     <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Pengunjung Unik</span>
                        <div class="p-2 bg-body rounded-3 text-primary">🌐</div>
                     </div>
                     <h3 class="fs-4 fw-bold mb-1" id="statUniqueVisitors">{{ number_format($totalUniqueVisitors) }}</h3>
                     <p class="text-primary small mb-0 d-flex align-items-center justify-content-between">
                        <span>Unique IP</span>
                        <span class="badge text-bg-primary rounded-pill">Detail</span>
                     </p>
                  </div>
               </div>

               <div class="col">
                  <div class="stat-card bg-body-secondary rounded-4 p-3 shadow-sm h-100 border-start border-4 border-success" data-bs-toggle="modal" data-bs-target="#modalKunjunganHariIni" role="button">
                     <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Kunjungan Hari Ini</span>
                        <div class="p-2 bg-body rounded-3 text-success">📅</div>
                     </div>
                     <h3 class="fs-4 fw-bold mb-1" id="statTodayVisits">{{ number_format($todayVisits) }}</h3>
                     <p class="text-success small mb-0 d-flex align-items-center justify-content-between">
                        <span>Today's Hits</span>
                        <span class="badge text-bg-success rounded-pill">Detail</span>
                     </p>
                  </div>
               </div>

               <div class="col">
                  <div class="stat-card bg-body-secondary rounded-4 p-3 shadow-sm h-100 border-start border-4 border-warning" data-bs-toggle="modal" data-bs-target="#modalPageRamai" role="button">
                     <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Page Ramai</span>
                        <div class="p-2 bg-body rounded-3 text-warning">⭐</div>
                     </div>
                     <h3 class="fs-6 fw-bold mb-1 text-truncate" id="statTopPage" title="{{ $topPageName }}">{{ $topPageName }}</h3>
                     <p class="text-warning small mb-0 d-flex align-items-center justify-content-between">
                        <span>Top Page</span>
                        <span class="badge text-bg-warning text-dark rounded-pill">Detail</span>
                     </p>
                  </div>
               </div>
            </div>
         </section>

         <!-- SECTION: GRAFIK ANALISA KUNJUNGAN PAGE -->
         <section id="section-grafik" class="mb-4">
            <div class="row g-4">
               <!-- Grafik Tren Kunjungan Harian (8 Columns) -->
               <div class="col-12 col-lg-8">
                  <div class="bg-body-secondary rounded-4 p-4 shadow-sm h-100">
                     <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                           <h2 class="h5 fw-bold mb-0">📈 Grafik Tren Kunjungan Halaman</h2>
                           <p class="text-secondary small mb-0">Tren pergerakan views 7 hari terakhir.</p>
                        </div>
                        <span class="badge badge-custom-1 px-3 py-2 rounded-pill">Line Analytics</span>
                     </div>
                     <div style="height: 250px; position: relative;">
                        <canvas id="chartVisitTrend"></canvas>
                     </div>
                  </div>
               </div>

               <!-- Grafik Distribusi Perangkat (4 Columns) -->
               <div class="col-12 col-lg-4">
                  <div class="bg-body-secondary rounded-4 p-4 shadow-sm h-100">
                     <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                           <h2 class="h5 fw-bold mb-0">📱 Distribusi Perangkat</h2>
                           <p class="text-secondary small mb-0">Desktop vs Mobile vs Tablet.</p>
                        </div>
                        <span class="badge text-bg-light border px-2 py-1 small">Device</span>
                     </div>
                     <div style="height: 250px; position: relative;">
                        <canvas id="chartDeviceBreakdown"></canvas>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- SECTION TABBED PANES: ANALISA PAGE, LOG KUNJUNGAN, AUDIT LOG, & PRODUK -->
         <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
            
            <!-- Nav Pills Horizontal Tab Slider (Mobile & Desktop Friendly) -->
            <div class="p-2 rounded-4 bg-body mb-4 border shadow-sm dashboard-tab-slider">
               <ul class="nav nav-pills flex-nowrap gap-2" id="dashboardTab" role="tablist">
                  <li class="nav-item" role="presentation">
                     <button class="nav-link text-nowrap active" id="tab-analisa-btn" data-bs-toggle="pill" data-bs-target="#pane-analisa" type="button" role="tab" aria-controls="pane-analisa" aria-selected="true">
                        📊 Analisa Kunjungan Page
                     </button>
                  </li>
                  <li class="nav-item" role="presentation">
                     <button class="nav-link text-nowrap" id="tab-log-kunjungan-btn" data-bs-toggle="pill" data-bs-target="#pane-log-kunjungan" type="button" role="tab" aria-controls="pane-log-kunjungan" aria-selected="false">
                        📜 Log History Kunjungan Page
                     </button>
                  </li>
                  <li class="nav-item" role="presentation">
                     <button class="nav-link text-nowrap" id="tab-audit-btn" data-bs-toggle="pill" data-bs-target="#pane-audit" type="button" role="tab" aria-controls="pane-audit" aria-selected="false">
                        📋 Aktivitas Sistem &amp; Audit Log
                     </button>
                  </li>
                  <li class="nav-item" role="presentation">
                     <button class="nav-link text-nowrap" id="tab-produk-btn" data-bs-toggle="pill" data-bs-target="#pane-produk" type="button" role="tab" aria-controls="pane-produk" aria-selected="false">
                        ☕ Ringkasan Produk
                     </button>
                  </li>
               </ul>
            </div>

            <!-- Tab Content Panes -->
            <div class="tab-content" id="dashboardTabContent">
               
               <!-- PANE 1: ANALISA KUNJUNGAN MASING-MASING PAGE -->
               <div class="tab-pane fade show active" id="pane-analisa" role="tabpanel" aria-labelledby="tab-analisa-btn" tabindex="0">
                  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                     <div>
                        <h2 class="h5 fw-bold mb-0">📊 Analisa Kunjungan Masing-Masing Page</h2>
                        <p class="text-secondary small mb-0">Statistik persentase traffic dan total views pada setiap halaman publik website.</p>
                     </div>
                     <span class="badge text-bg-light border px-3 py-2">Total {{ count($pageAnalytics) }} Halaman Terdaftar</span>
                  </div>

                  <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
                  <div class="table-responsive d-none d-md-block">
                     <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                           <tr>
                              <th>Nama Halaman</th>
                              <th>URL Path</th>
                              <th>Total Visits</th>
                              <th style="width: 25%;">Persentase Traffic Share</th>
                              <th>Terakhir Dikunjungi</th>
                              <th class="text-end">Aksi</th>
                           </tr>
                        </thead>
                        <tbody>
                           @forelse($pageAnalytics as $analytics)
                              <tr>
                                 <td><span class="fw-bold small">{{ $analytics->nama_halaman }}</span></td>
                                 <td><code class="small bg-body-tertiary px-2 py-1 rounded border">{{ $analytics->url }}</code></td>
                                 <td class="fw-bold text-custom-1">{{ number_format($analytics->total_visits) }} <small class="text-secondary fw-normal">hits</small></td>
                                 <td>
                                    <div class="d-flex align-items-center gap-2">
                                       <div class="progress flex-grow-1" style="height: 8px; border-radius: 4px;">
                                          <div class="progress-bar bg-custom-1" role="progressbar" style="width: {{ $analytics->percentage }}%;" aria-valuenow="{{ $analytics->percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                       </div>
                                       <span class="small fw-semibold text-secondary">{{ $analytics->percentage }}%</span>
                                    </div>
                                 </td>
                                 <td class="small text-secondary">{{ \Carbon\Carbon::parse($analytics->latest_visit)->diffForHumans() }}</td>
                                 <td class="text-end">
                                    <a href="{{ url($analytics->url) }}" target="_blank" class="btn btn-sm btn-custom-1-outline rounded-pill px-3 py-1 fw-semibold text-nowrap">Lihat Page ↗</a>
                                 </td>
                              </tr>
                           @empty
                              <tr>
                                 <td colspan="6" class="text-center py-4 text-muted">Belum ada data analisa kunjungan.</td>
                              </tr>
                           @endforelse
                        </tbody>
                     </table>
                  </div>

                  <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
                  <div class="d-block d-md-none">
                     @forelse($pageAnalytics as $analytics)
                        <div class="mobile-data-card">
                           <div class="d-flex justify-content-between align-items-start mb-2">
                              <h3 class="fs-6 fw-bold mb-0 text-custom-1">{{ $analytics->nama_halaman }}</h3>
                              <span class="badge badge-custom-1">{{ $analytics->percentage }}%</span>
                           </div>
                           <p class="mb-2"><code>{{ $analytics->url }}</code></p>
                           <div class="progress mb-2" style="height: 6px;">
                              <div class="progress-bar bg-custom-1" style="width: {{ $analytics->percentage }}%"></div>
                           </div>
                           <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 small text-secondary">
                              <span>Total Views: <strong class="text-body">{{ number_format($analytics->total_visits) }}</strong></span>
                              <a href="{{ url($analytics->url) }}" target="_blank" class="btn btn-sm btn-custom-1-outline rounded-pill px-2 py-0">Buka ↗</a>
                           </div>
                        </div>
                     @empty
                        <p class="text-muted text-center py-3">Belum ada data analisa kunjungan.</p>
                     @endforelse
                  </div>
               </div>

               <!-- PANE 2: LOG HISTORY KUNJUNGAN PAGE -->
               <div class="tab-pane fade" id="pane-log-kunjungan" role="tabpanel" aria-labelledby="tab-log-kunjungan-btn" tabindex="0">
                  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                     <div>
                        <h2 class="h5 fw-bold mb-0">📜 Log History Kunjungan Page</h2>
                        <p class="text-secondary small mb-0">Catatan histori riwayat kunjungan pengunjung publik secara real-time.</p>
                     </div>
                     <span class="badge badge-custom-1 px-3 py-2 rounded-pill fw-semibold">Real-time Logging Active</span>
                  </div>

                  <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
                  <div class="table-responsive d-none d-md-block">
                     <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                           <tr>
                              <th>Waktu Kunjungan</th>
                              <th>Nama Halaman</th>
                              <th>URL Target</th>
                              <th>IP Address</th>
                              <th>Perangkat</th>
                              <th>User Agent</th>
                           </tr>
                        </thead>
                        <tbody>
                           @forelse($pageVisitLogs as $log)
                              <tr>
                                 <td class="small text-secondary">
                                    <div>{{ $log->created_at->format('d M Y H:i:s') }}</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">{{ $log->created_at->diffForHumans() }}</small>
                                 </td>
                                 <td class="fw-semibold small">{{ $log->nama_halaman }}</td>
                                 <td><code class="small text-custom-1 bg-body-tertiary px-2 py-1 rounded border">{{ $log->url }}</code></td>
                                 <td class="small"><code>{{ $log->ip_address ?? '127.0.0.1' }}</code></td>
                                 <td>
                                    @if($log->device_type == 'Mobile')
                                       <span class="badge text-bg-warning text-dark">📱 Mobile</span>
                                    @elseif($log->device_type == 'Tablet')
                                       <span class="badge text-bg-info text-white">タブ Tablet</span>
                                    @else
                                       <span class="badge text-bg-secondary">💻 Desktop</span>
                                    @endif
                                 </td>
                                 <td class="small text-secondary">
                                    <span title="{{ $log->user_agent }}">{{ Str::limit($log->user_agent, 35) }}</span>
                                 </td>
                              </tr>
                           @empty
                              <tr>
                                 <td colspan="6" class="text-center py-4 text-muted">Belum ada catatan log kunjungan page.</td>
                              </tr>
                           @endforelse
                        </tbody>
                     </table>
                  </div>

                  <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
                  <div class="d-block d-md-none">
                     @forelse($pageVisitLogs as $log)
                        <div class="mobile-data-card">
                           <div class="d-flex justify-content-between align-items-start mb-2">
                              <span class="fw-bold small text-custom-1">{{ $log->nama_halaman }}</span>
                              <span class="small text-secondary">{{ $log->created_at->format('H:i d/m') }}</span>
                           </div>
                           <p class="mb-2"><code>{{ $log->url }}</code></p>
                           <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 small text-secondary">
                              <span>IP: <code>{{ $log->ip_address }}</code></span>
                              <span class="badge {{ $log->device_type == 'Mobile' ? 'text-bg-warning text-dark' : 'text-bg-secondary' }}">{{ $log->device_type }}</span>
                           </div>
                        </div>
                     @empty
                        <p class="text-muted text-center py-3">Belum ada catatan log kunjungan page.</p>
                     @endforelse
                  </div>
               </div>

               <!-- PANE 3: AKTIVITAS SISTEM & AUDIT LOG -->
               <div class="tab-pane fade" id="pane-audit" role="tabpanel" aria-labelledby="tab-audit-btn" tabindex="0">
                  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                     <div>
                        <h2 class="h5 fw-bold mb-0">📋 Aktivitas Sistem &amp; Audit Log</h2>
                        <p class="text-secondary small mb-0">Catatan histori aktivitas perubahan data admin secara real-time.</p>
                     </div>
                     <a href="{{ route('admin.log-aktivitas.index') }}" class="btn btn-sm btn-custom-1-outline rounded-pill px-3 fw-semibold">Lihat Semua Log Audit &rarr;</a>
                  </div>

                  <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
                  <div class="table-responsive d-none d-md-block">
                     <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                           <tr>
                              <th>Waktu</th>
                              <th>User Admin</th>
                              <th>Aktivitas</th>
                              <th>IP Address</th>
                           </tr>
                        </thead>
                        <tbody>
                           @forelse($latestLogs as $log)
                              <tr>
                                 <td class="small text-secondary">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                 <td class="fw-bold small">{{ $log->user->name ?? 'Administrator' }}</td>
                                 <td><span class="badge text-bg-light border">{{ $log->aktivitas }}</span></td>
                                 <td class="small text-secondary">{{ $log->ip_address ?? '-' }}</td>
                              </tr>
                           @empty
                              <tr>
                                 <td colspan="4" class="text-center py-4 text-muted">Belum ada catatan aktivitas.</td>
                              </tr>
                           @endforelse
                        </tbody>
                     </table>
                  </div>

                  <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
                  <div class="d-block d-md-none">
                     @forelse($latestLogs as $log)
                        <div class="mobile-data-card">
                           <div class="d-flex justify-content-between align-items-start mb-2">
                              <span class="fw-bold small">{{ $log->user->name ?? 'Administrator' }}</span>
                              <span class="small text-secondary">{{ $log->created_at->format('H:i d/m') }}</span>
                           </div>
                           <p class="mb-0 small text-custom-1 fw-semibold">{{ $log->aktivitas }}</p>
                        </div>
                     @empty
                        <p class="text-muted text-center py-3">Belum ada catatan aktivitas.</p>
                     @endforelse
                  </div>
               </div>

               <!-- PANE 4: RINGKASAN MASTER PRODUK -->
               <div class="tab-pane fade" id="pane-produk" role="tabpanel" aria-labelledby="tab-produk-btn" tabindex="0">
                  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                     <div>
                        <h2 class="h5 fw-bold mb-0">☕ Kelola Data Master Produk</h2>
                        <p class="text-secondary small mb-0">Manajemen Tambah, Edit, &amp; Hapus Katalog Produk INDRACO.</p>
                     </div>
                     <a href="{{ route('admin.produk.create') }}" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold">+ Tambah Produk Baru</a>
                  </div>

                  <!-- DESKTOP VIEW: HTML TABLE (d-none d-md-block) -->
                  <div class="table-responsive d-none d-md-block">
                     <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                           <tr>
                              <th>ID/SKU</th>
                              <th>Nama Produk</th>
                              <th>Brand Merek</th>
                              <th>Harga</th>
                              <th>Status</th>
                              <th class="text-end">Aksi CRUD</th>
                           </tr>
                        </thead>
                        <tbody>
                           @forelse($latestProduk as $prod)
                              <tr>
                                 <td class="fw-bold small">{{ $prod->sku ?? 'PRD-' . $prod->id }}</td>
                                 <td>
                                    <div class="fw-bold small">{{ $prod->nama_produk }}</div>
                                    <span class="text-secondary small" style="font-size: 0.75rem;">{{ $prod->kategori->nama_kategori ?? 'Kategori' }}</span>
                                 </td>
                                 <td><span class="badge text-bg-light">{{ $prod->merek->nama_merek ?? 'Merek' }}</span></td>
                                 <td class="fw-semibold small">Rp {{ number_format($prod->harga_reguler, 0, ',', '.') }}</td>
                                 <td>
                                    @if($prod->status == 'active')
                                       <span class="badge text-bg-success">Aktif</span>
                                    @else
                                       <span class="badge text-bg-warning text-dark">Nonaktif</span>
                                    @endif
                                 </td>
                                 <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                       <a href="{{ route('admin.produk.edit', $prod->id) }}" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 text-nowrap fw-semibold">✏️ Edit</a>
                                       <form action="{{ route('admin.produk.destroy', $prod->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                                          @csrf
                                          @method('DELETE')
                                          <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-nowrap fw-semibold">🗑️ Hapus</button>
                                       </form>
                                    </div>
                                 </td>
                              </tr>
                           @empty
                              <tr>
                                 <td colspan="6" class="text-center py-4 text-muted">Belum ada data produk.</td>
                              </tr>
                           @endforelse
                        </tbody>
                     </table>
                  </div>

                  <!-- MOBILE VIEW: 1 DATA = 1 CARD (d-block d-md-none) -->
                  <div class="d-block d-md-none">
                     @forelse($latestProduk as $prod)
                        <div class="mobile-data-card">
                           <div class="d-flex justify-content-between align-items-start mb-2">
                              <div>
                                 <span class="badge bg-custom-1 text-white me-1">{{ $prod->sku ?? 'PRD-' . $prod->id }}</span>
                                 <span class="badge text-bg-light">{{ $prod->merek->nama_merek ?? 'Merek' }}</span>
                              </div>
                              @if($prod->status == 'active')
                                 <span class="badge text-bg-success">Aktif</span>
                              @else
                                 <span class="badge text-bg-warning text-dark">Nonaktif</span>
                              @endif
                           </div>
                           <h3 class="fs-6 fw-bold mb-1">{{ $prod->nama_produk }}</h3>
                           <p class="text-secondary small mb-2">{{ $prod->kategori->nama_kategori ?? 'Kategori' }}</p>
                           <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                              <div>
                                 <span class="text-secondary small">Harga:</span>
                                 <span class="fw-bold small text-custom-1 ms-1">Rp {{ number_format($prod->harga_reguler, 0, ',', '.') }}</span>
                              </div>
                              <div class="d-flex gap-2">
                                 <a href="{{ route('admin.produk.edit', $prod->id) }}" class="btn btn-sm btn-custom-1 rounded-pill px-3 py-1 fw-semibold">✏️ Edit</a>
                                 <form action="{{ route('admin.produk.destroy', $prod->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 py-1 fw-semibold">🗑️</button>
                                 </form>
                              </div>
                           </div>
                        </div>
                     @empty
                        <p class="text-muted text-center py-3">Belum ada data produk.</p>
                     @endforelse
                  </div>

                  <div class="pt-3 border-top mt-3 text-end">
                     <a href="{{ route('admin.produk.index') }}" class="btn btn-sm btn-custom-1-outline rounded-pill px-3 fw-semibold">Lihat Semua Katalog Produk &rarr;</a>
                  </div>
               </div>

            </div>

         </section>

      </div>
   </div>

</main>

<!-- MODAL 1: PESAN KONTAK -->
<div class="modal fade" id="modalPesanKontak" tabindex="-1" aria-labelledby="modalPesanKontakLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-4 border-0 shadow">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-custom-1" id="modalPesanKontakLabel">💬 Detail Pesan Kontak (Inquiry Masuk)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 bg-body-tertiary p-3 rounded-3 border">
               <div>
                  <span class="text-secondary small d-block">Total Pesan Kontak</span>
                  <span class="fs-4 fw-bold text-custom-1">{{ number_format($totalKontak) }} Pesan</span>
               </div>
               <a href="{{ route('admin.kontak.index') }}" class="btn btn-custom-1 rounded-pill px-3 py-1 text-nowrap fw-semibold small">Buka Halaman Kontak &rarr;</a>
            </div>

            <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Pesan Terbaru Received</h6>
            <div class="table-responsive">
               <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                     <tr>
                        <th>Pengirim</th>
                        <th>Subjek / Pesan</th>
                        <th>Kontak</th>
                        <th>Tanggal</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($latestKontak as $kontak)
                        <tr>
                           <td class="fw-bold small">{{ $kontak->nama }}</td>
                           <td class="small">
                              <div class="fw-semibold">{{ $kontak->judul_pesan ?? 'Pertanyaan Kontak' }}</div>
                              <span class="text-muted text-truncate d-inline-block" style="max-width: 250px;">{{ $kontak->pesan }}</span>
                           </td>
                           <td class="small">
                              <div><code>{{ $kontak->email }}</code></div>
                              <span class="text-muted">{{ $kontak->telepon ?? '-' }}</span>
                           </td>
                           <td class="small text-secondary">{{ $kontak->created_at ? $kontak->created_at->format('d M Y H:i') : '-' }}</td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="4" class="text-center py-3 text-muted">Belum ada pesan kontak masuk.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <a href="{{ route('admin.kontak.index') }}" class="btn btn-custom-1-outline rounded-pill px-4">Lihat Selengkapnya &rarr;</a>
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
         </div>
      </div>
   </div>
</div>

<!-- MODAL 2: TOTAL KUNJUNGAN -->
<div class="modal fade" id="modalTotalKunjungan" tabindex="-1" aria-labelledby="modalTotalKunjunganLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-4 border-0 shadow">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-info" id="modalTotalKunjunganLabel">👁️ Analytics Total Kunjungan Page</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="row g-3 mb-4">
               <div class="col-12 col-md-4">
                  <div class="bg-info-subtle border border-info-subtle p-3 rounded-4 text-center">
                     <span class="text-secondary small fw-medium d-block">Total Page Views</span>
                     <h3 class="fs-3 fw-bold text-info mb-0">{{ number_format($totalVisits) }}</h3>
                  </div>
               </div>
               <div class="col-12 col-md-4">
                  <div class="bg-primary-subtle border border-primary-subtle p-3 rounded-4 text-center">
                     <span class="text-secondary small fw-medium d-block">Pengunjung Unik</span>
                     <h3 class="fs-3 fw-bold text-primary mb-0">{{ number_format($totalUniqueVisitors) }}</h3>
                  </div>
               </div>
               <div class="col-12 col-md-4">
                  <div class="bg-success-subtle border border-success-subtle p-3 rounded-4 text-center">
                     <span class="text-secondary small fw-medium d-block">Kunjungan Hari Ini</span>
                     <h3 class="fs-3 fw-bold text-success mb-0">{{ number_format($todayVisits) }}</h3>
                  </div>
               </div>
            </div>

            <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Breakdown Perangkat (Device Types)</h6>
            <div class="row g-3">
               <div class="col-4">
                  <div class="border p-3 rounded-3 text-center">
                     <div class="fs-4">💻</div>
                     <div class="fw-bold fs-5 mt-1">{{ number_format($desktopCount) }}</div>
                     <span class="text-secondary small">Desktop</span>
                  </div>
               </div>
               <div class="col-4">
                  <div class="border p-3 rounded-3 text-center">
                     <div class="fs-4">📱</div>
                     <div class="fw-bold fs-5 mt-1">{{ number_format($mobileCount) }}</div>
                     <span class="text-secondary small">Mobile</span>
                  </div>
               </div>
               <div class="col-4">
                  <div class="border p-3 rounded-3 text-center">
                     <div class="fs-4">📟</div>
                     <div class="fw-bold fs-5 mt-1">{{ number_format($tabletCount) }}</div>
                     <span class="text-secondary small">Tablet</span>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
         </div>
      </div>
   </div>
</div>

<!-- MODAL 3: PENGUNJUNG UNIK -->
<div class="modal fade" id="modalPengunjungUnik" tabindex="-1" aria-labelledby="modalPengunjungUnikLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-4 border-0 shadow">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-primary" id="modalPengunjungUnikLabel">🌐 Detail Pengunjung Unik (Unique IP)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 bg-primary-subtle border border-primary-subtle p-3 rounded-4">
               <div>
                  <span class="text-secondary small d-block">Total Unique IP Visitors</span>
                  <h3 class="fs-3 fw-bold text-primary mb-0">{{ number_format($totalUniqueVisitors) }} IP</h3>
               </div>
               <span class="badge text-bg-primary fs-6 px-3 py-2 rounded-pill">Distinct Tracking</span>
            </div>

            <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Log IP &amp; Perangkat Terkini</h6>
            <div class="table-responsive">
               <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                     <tr>
                        <th>IP Address</th>
                        <th>Halaman Dikunjungi</th>
                        <th>Perangkat</th>
                        <th>Waktu Kunjungan</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($pageVisitLogs as $log)
                        <tr>
                           <td><code>{{ $log->ip_address ?? '127.0.0.1' }}</code></td>
                           <td class="fw-semibold small">{{ $log->nama_halaman }}</td>
                           <td>
                              <span class="badge {{ $log->device_type == 'Mobile' ? 'text-bg-warning text-dark' : 'text-bg-secondary' }}">
                                 {{ $log->device_type }}
                              </span>
                           </td>
                           <td class="small text-secondary">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="4" class="text-center py-3 text-muted">Belum ada data log IP pengunjung.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
         </div>
      </div>
   </div>
</div>

<!-- MODAL 4: KUNJUNGAN HARI INI -->
<div class="modal fade" id="modalKunjunganHariIni" tabindex="-1" aria-labelledby="modalKunjunganHariIniLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-4 border-0 shadow">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-success" id="modalKunjunganHariIniLabel">📅 Detail Kunjungan Hari Ini</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 bg-success-subtle border border-success-subtle p-3 rounded-4">
               <div>
                  <span class="text-secondary small d-block">Today's Total Hits ({{ date('d M Y') }})</span>
                  <h3 class="fs-3 fw-bold text-success mb-0">{{ number_format($todayVisits) }} Views</h3>
               </div>
               <span class="badge text-bg-success fs-6 px-3 py-2 rounded-pill">Live Tracking Today</span>
            </div>

            <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Aktivitas Kunjungan Terbaru</h6>
            <div class="table-responsive">
               <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                     <tr>
                        <th>Jam</th>
                        <th>Nama Halaman</th>
                        <th>URL</th>
                        <th>IP Address</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($pageVisitLogs as $log)
                        <tr>
                           <td class="small text-secondary">{{ $log->created_at->format('H:i:s') }}</td>
                           <td class="fw-semibold small">{{ $log->nama_halaman }}</td>
                           <td><code class="small">{{ $log->url }}</code></td>
                           <td class="small"><code>{{ $log->ip_address }}</code></td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="4" class="text-center py-3 text-muted">Belum ada kunjungan hari ini.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
         </div>
      </div>
   </div>
</div>

<!-- MODAL 5: PAGE RAMAI -->
<div class="modal fade" id="modalPageRamai" tabindex="-1" aria-labelledby="modalPageRamaiLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-4 border-0 shadow">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-warning" id="modalPageRamaiLabel">⭐ Detail Page Ramai (Top Visited Pages)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 bg-warning-subtle border border-warning-subtle p-3 rounded-4">
               <div>
                  <span class="text-secondary small d-block">Halaman Paling Sering Dikunjungi (Rank #1)</span>
                  <h3 class="fs-4 fw-bold text-dark mb-0">{{ $topPageName }}</h3>
               </div>
               <span class="badge text-bg-warning text-dark fs-6 px-3 py-2 rounded-pill">Top Ranking</span>
            </div>

            <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Peringkat Traffic Halaman Website</h6>
            <div class="table-responsive">
               <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                     <tr>
                        <th>Halaman</th>
                        <th>URL</th>
                        <th>Total Views</th>
                        <th style="width: 30%;">Traffic Share</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($pageAnalytics as $analytics)
                        <tr>
                           <td class="fw-bold small">{{ $analytics->nama_halaman }}</td>
                           <td><code class="small">{{ $analytics->url }}</code></td>
                           <td class="fw-bold text-custom-1">{{ number_format($analytics->total_visits) }}</td>
                           <td>
                              <div class="d-flex align-items-center gap-2">
                                 <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: {{ $analytics->percentage }}%"></div>
                                 </div>
                                 <span class="small fw-semibold">{{ $analytics->percentage }}%</span>
                              </div>
                           </td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="4" class="text-center py-3 text-muted">Belum ada data peringkat halaman.</td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>
         </div>
         <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
         </div>
      </div>
   </div>
</div>

@endsection
