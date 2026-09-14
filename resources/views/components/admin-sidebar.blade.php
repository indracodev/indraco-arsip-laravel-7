<!-- DESKTOP INLINE SIDEBAR (Hidden on mobile < 992px) -->
<aside class="d-none d-lg-block col-lg-3">
   <div class="bg-body-secondary rounded-4 p-3 shadow-sm sticky-sidebar admin-sidebar">
      <div class="d-flex align-items-center justify-content-between p-2 mb-2 border-bottom pb-3">
         <div class="fw-bold d-flex align-items-center gap-2">
            <div class="p-2 text-white rounded-3 shadow-sm" style="background-color: #ee5d1d !important;">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                  <rect x="3" y="3" width="7" height="7"></rect>
                  <rect x="14" y="3" width="7" height="7"></rect>
                  <rect x="14" y="14" width="7" height="7"></rect>
                  <rect x="3" y="14" width="7" height="7"></rect>
               </svg>
            </div>
            <span>Navigasi Admin</span>
         </div>
      </div>

      <div>
         <!-- GROUP 1: OVERVIEW -->
         <div class="admin-sidebar-title">Main Dashboard</div>
         <nav aria-label="Main Overview">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                     <rect x="3" y="3" width="7" height="7"></rect>
                     <rect x="14" y="3" width="7" height="7"></rect>
                     <rect x="14" y="14" width="7" height="7"></rect>
                     <rect x="3" y="14" width="7" height="7"></rect>
                  </svg>
                  Overview &amp; Analytics
               </span>
            </a>
         </nav>

         <!-- GROUP 2: DATA MASTER (CRUD) -->
         <div class="admin-sidebar-title">Kelola Data Master</div>
         <nav aria-label="Data Master CRUD Menu">
            <a href="{{ route('admin.produk.index') }}" class="nav-link {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">☕ Master Produk</span>
            </a>

            <a href="{{ route('admin.merek.index') }}" class="nav-link {{ request()->routeIs('admin.merek.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">🏷️ Master Merek (Brand)</span>
            </a>

            <a href="{{ route('admin.kategori.index') }}" class="nav-link {{ request()->routeIs('admin.kategori.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">📂 Master Kategori</span>
            </a>

            <a href="{{ route('admin.collection.index') }}" class="nav-link {{ request()->routeIs('admin.collection.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">📦 Master Collection</span>
            </a>

            <a href="{{ route('admin.type.index') }}" class="nav-link {{ request()->routeIs('admin.type.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">🔷 Master Type</span>
            </a>

            <a href="{{ route('admin.variant.index') }}" class="nav-link {{ request()->routeIs('admin.variant.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">🎨 Master Variant</span>
            </a>
         </nav>

         <!-- GROUP 3: KONTEN & AUDIT -->
         <div class="admin-sidebar-title">Konten &amp; Audit Log</div>
         <nav aria-label="Konten & Audit Menu">
            <a href="{{ route('admin.banner.index') }}" class="nav-link {{ request()->routeIs('admin.banner.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">🖼️ Master Banner</span>
            </a>

            <a href="{{ route('admin.news.index') }}" class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">📰 Master News</span>
            </a>

            <a href="{{ route('admin.download.index') }}" class="nav-link {{ request()->routeIs('admin.download.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">📥 Master Download</span>
            </a>

            <a href="{{ route('admin.kontak.index') }}" class="nav-link {{ request()->routeIs('admin.kontak.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">💬 Pesan Kontak</span>
            </a>

            <a href="{{ route('admin.log-aktivitas.index') }}" class="nav-link {{ request()->routeIs('admin.log-aktivitas.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">📋 Log Aktivitas Audit</span>
            </a>
         </nav>

         <!-- GROUP 4: PENGATURAN -->
         <div class="admin-sidebar-title">Pengaturan Sistem</div>
         <nav aria-label="System Settings Menu">
            <a href="{{ route('admin.setting.index') }}" class="nav-link {{ request()->routeIs('admin.setting.*') ? 'active' : '' }}">
               <span class="d-flex align-items-center gap-2">⚙️ Setting</span>
            </a>

            <form action="{{ route('admin.logout') }}" method="POST" id="logout-form">
               @csrf
               <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100 text-start">
                  <span class="d-flex align-items-center gap-2">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                     </svg>
                     Keluar Mode Admin
                  </span>
               </button>
            </form>
         </nav>
      </div>
   </div>
</aside>

<!-- MOBILE OFFCANVAS SIDEBAR (Native App Mobile Style) -->
<div class="offcanvas offcanvas-start d-lg-none mobile-app-drawer" tabindex="-1" id="adminSidebarOffcanvas" aria-labelledby="adminSidebarOffcanvasLabel">
   
   <!-- Drawer Header with User Profile / Branding -->
   <div class="offcanvas-header border-bottom p-3">
      <div class="flex-grow-1 overflow-hidden pe-3">
         <h6 class="fw-bold mb-1 text-truncate fs-6">{{ auth()->user()->name ?? 'Administrator' }}</h6>
         <span class="badge text-bg-success rounded-pill small" style="font-size: 0.68rem;">Administrator Access</span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
   </div>

   <!-- Drawer Body Navigation Items -->
   <div class="offcanvas-body p-3">
      <!-- GROUP 1: OVERVIEW -->
      <div class="mobile-drawer-category mt-0">Main Dashboard</div>
      <nav class="nav flex-column gap-1">
         <a href="{{ route('admin.dashboard') }}" class="mobile-drawer-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
               <rect x="3" y="3" width="7" height="7"></rect>
               <rect x="14" y="3" width="7" height="7"></rect>
               <rect x="14" y="14" width="7" height="7"></rect>
               <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>Overview &amp; Analytics</span>
         </a>
      </nav>

      <!-- GROUP 2: DATA MASTER (CRUD) -->
      <div class="mobile-drawer-category">Kelola Data Master</div>
      <nav class="nav flex-column gap-1">
         <a href="{{ route('admin.produk.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}">
            <span class="drawer-icon">☕</span>
            <span>Master Produk</span>
         </a>

         <a href="{{ route('admin.merek.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.merek.*') ? 'active' : '' }}">
            <span class="drawer-icon">🏷️</span>
            <span>Master Merek (Brand)</span>
         </a>

         <a href="{{ route('admin.kategori.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.kategori.*') ? 'active' : '' }}">
            <span class="drawer-icon">📂</span>
            <span>Master Kategori</span>
         </a>

         <a href="{{ route('admin.collection.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.collection.*') ? 'active' : '' }}">
            <span class="drawer-icon">📦</span>
            <span>Master Collection</span>
         </a>

         <a href="{{ route('admin.type.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.type.*') ? 'active' : '' }}">
            <span class="drawer-icon">🔷</span>
            <span>Master Type</span>
         </a>

         <a href="{{ route('admin.variant.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.variant.*') ? 'active' : '' }}">
            <span class="drawer-icon">🎨</span>
            <span>Master Variant</span>
         </a>
      </nav>

      <!-- GROUP 3: KONTEN & AUDIT -->
      <div class="mobile-drawer-category">Konten &amp; Audit Log</div>
      <nav class="nav flex-column gap-1">
         <a href="{{ route('admin.banner.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.banner.*') ? 'active' : '' }}">
            <span class="drawer-icon">🖼️</span>
            <span>Master Banner</span>
         </a>

         <a href="{{ route('admin.news.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.news.*') ? 'active' : '' }}">
            <span class="drawer-icon">📰</span>
            <span>Master News</span>
         </a>

         <a href="{{ route('admin.download.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.download.*') ? 'active' : '' }}">
            <span class="drawer-icon">📥</span>
            <span>Master Download</span>
         </a>

         <a href="{{ route('admin.kontak.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.kontak.*') ? 'active' : '' }}">
            <span class="drawer-icon">💬</span>
            <span>Pesan Kontak</span>
         </a>

         <a href="{{ route('admin.log-aktivitas.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.log-aktivitas.*') ? 'active' : '' }}">
            <span class="drawer-icon">📋</span>
            <span>Log Aktivitas Audit</span>
         </a>
      </nav>

      <!-- GROUP 4: PENGATURAN -->
      <div class="mobile-drawer-category">Pengaturan Sistem</div>
      <nav class="nav flex-column gap-1 mb-2">
         <a href="{{ route('admin.setting.index') }}" class="mobile-drawer-item {{ request()->routeIs('admin.setting.*') ? 'active' : '' }}">
            <span class="drawer-icon">⚙️</span>
            <span>Setting</span>
         </a>
      </nav>
      <form action="{{ route('admin.logout') }}" method="POST">
         @csrf
         <button type="submit" class="mobile-drawer-item text-danger border-0 bg-transparent w-100 text-start">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
               <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
               <polyline points="16 17 21 12 16 7"></polyline>
               <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Keluar Mode Admin</span>
         </button>
      </form>
   </div>

   <!-- Drawer Footer Branding -->
   <div class="offcanvas-footer p-3 border-top text-center text-secondary small" style="font-size: 0.75rem;">
      <span class="fw-bold">INDRACO Coffee Admin</span> &bull; App Version 2.0
   </div>
</div>
