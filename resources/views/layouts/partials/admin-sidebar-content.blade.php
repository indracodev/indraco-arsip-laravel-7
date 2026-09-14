<a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center mb-4 px-2 text-decoration-none">
   <img src="{{ asset('images/logo-indraco-est.png') }}" data-light="{{ asset('images/logo-indraco-est.png') }}" data-dark="{{ asset('images/logo-indraco-est-invert.png') }}" alt="INDRACO Admin" class="theme-image w-100 h-auto">
</a>

<div class="small opacity-50 px-2 fw-semibold text-uppercase mb-2">Main Menu</div>
<nav class="nav flex-column gap-1 mb-4">
   <a href="{{ route('admin.dashboard') }}" class="nav-admin-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <span>Dashboard</span>
   </a>
</nav>

<div class="small opacity-50 px-2 fw-semibold text-uppercase mb-2">Master Data</div>
<nav class="nav flex-column gap-1 mb-4">
   <a href="{{ route('admin.merek.index') }}" class="nav-admin-link {{ request()->routeIs('admin.merek.*') ? 'active' : '' }}">
      <span>Master Merek</span>
   </a>
   <a href="{{ route('admin.kategori.index') }}" class="nav-admin-link {{ request()->routeIs('admin.kategori.*') ? 'active' : '' }}">
      <span>Master Kategori</span>
   </a>
   <a href="{{ route('admin.collection.index') }}" class="nav-admin-link {{ request()->routeIs('admin.collection.*') ? 'active' : '' }}">
      <span>Master Collection</span>
   </a>
   <a href="{{ route('admin.type.index') }}" class="nav-admin-link {{ request()->routeIs('admin.type.*') ? 'active' : '' }}">
      <span>Master Type</span>
   </a>
   <a href="{{ route('admin.variant.index') }}" class="nav-admin-link {{ request()->routeIs('admin.variant.*') ? 'active' : '' }}">
      <span>Master Variant</span>
   </a>
   <a href="{{ route('admin.produk.index') }}" class="nav-admin-link {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}">
      <span>Master Produk</span>
   </a>
</nav>

<div class="small opacity-50 px-2 fw-semibold text-uppercase mb-2">Konten Web</div>
<nav class="nav flex-column gap-1 mb-4">
   <a href="{{ route('admin.banner.index') }}" class="nav-admin-link {{ request()->routeIs('admin.banner.*') ? 'active' : '' }}">
      <span>Master Banners</span>
   </a>
   <a href="{{ route('admin.news.index') }}" class="nav-admin-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}">
      <span>Master News</span>
   </a>
</nav>

<div class="small opacity-50 px-2 fw-semibold text-uppercase mb-2">Sistem & Inquiries</div>
<nav class="nav flex-column gap-1">
   <a href="{{ route('admin.kontak.index') }}" class="nav-admin-link {{ request()->routeIs('admin.kontak.*') ? 'active' : '' }}">
      <span>Pesan Kontak</span>
   </a>
   <a href="{{ route('admin.log-aktivitas.index') }}" class="nav-admin-link {{ request()->routeIs('admin.log-aktivitas.*') ? 'active' : '' }}">
      <span>Log Aktivitas</span>
   </a>
</nav>

<div class="mt-auto pt-4 border-top">
   <div class="d-flex align-items-center justify-content-between">
      <span class="small fw-semibold">{{ Auth::user()->name ?? 'Admin' }}</span>
      <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
         @csrf
         <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Logout</button>
      </form>
   </div>
</div>
