<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel - INDRACO')</title>
    <link rel="shortcut icon" href="{{ asset('images/icon-indraco.ico') }}" type="image/x-icon">

    {{-- Core CSS --}}
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/myFont.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-sidebar {
            min-height: 100vh;
            background: #1e293b;
            color: #fff;
        }
        .admin-sidebar .nav-link {
            color: #94a3b8;
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            margin-bottom: 0.2rem;
        }
        .admin-sidebar .nav-link:hover, .admin-sidebar .nav-link.active {
            color: #fff;
            background: #334155;
        }
        @media (max-width: 991.98px) {
            body {
                padding-bottom: 70px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="d-flex">
    {{-- Desktop Sidebar --}}
    <aside class="admin-sidebar d-none d-lg-block p-3" style="width: 260px;">
        <div class="d-flex align-items-center mb-4 px-2">
            <img src="{{ asset('images/logo-indraco-invert.png') }}" alt="INDRACO" style="max-height: 40px;">
        </div>
        <nav class="nav flex-column">
            <small class="text-uppercase text-muted fw-bold px-2 mb-2" style="font-size: 0.7rem;">Main Menu</small>
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            
            <small class="text-uppercase text-muted fw-bold px-2 mt-3 mb-2" style="font-size: 0.7rem;">Katalog & Produk</small>
            <a href="{{ route('admin.merek.index') }}" class="nav-link {{ request()->routeIs('admin.merek.*') ? 'active' : '' }}">Master Merek</a>
            <a href="{{ route('admin.kategori.index') }}" class="nav-link {{ request()->routeIs('admin.kategori.*') ? 'active' : '' }}">Master Kategori</a>
            <a href="{{ route('admin.collection.index') }}" class="nav-link {{ request()->routeIs('admin.collection.*') ? 'active' : '' }}">Master Collection</a>
            <a href="{{ route('admin.type.index') }}" class="nav-link {{ request()->routeIs('admin.type.*') ? 'active' : '' }}">Master Type</a>
            <a href="{{ route('admin.variant.index') }}" class="nav-link {{ request()->routeIs('admin.variant.*') ? 'active' : '' }}">Master Variant</a>
            <a href="{{ route('admin.produk.index') }}" class="nav-link {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}">Master Produk</a>

            <small class="text-uppercase text-muted fw-bold px-2 mt-3 mb-2" style="font-size: 0.7rem;">Konten & Audit</small>
            <a href="{{ route('admin.banner.index') }}" class="nav-link {{ request()->routeIs('admin.banner.*') ? 'active' : '' }}">Master Banner</a>
            <a href="{{ route('admin.news.index') }}" class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}">Master News</a>
            <a href="{{ route('admin.kontak.index') }}" class="nav-link {{ request()->routeIs('admin.kontak.*') ? 'active' : '' }}">Pesan Kontak</a>
            <a href="{{ route('admin.log-aktivitas.index') }}" class="nav-link {{ request()->routeIs('admin.log-aktivitas.*') ? 'active' : '' }}">Log Aktivitas</a>
        </nav>
    </aside>

    {{-- Mobile Offcanvas Drawer Sidebar --}}
    <div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="adminDrawer" aria-labelledby="adminDrawerLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="adminDrawerLabel">
                <img src="{{ asset('images/logo-indraco-invert.png') }}" alt="INDRACO" style="max-height: 35px;">
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="nav flex-column">
                <a href="{{ route('admin.dashboard') }}" class="nav-link text-white">Dashboard</a>
                <a href="{{ route('admin.merek.index') }}" class="nav-link text-white">Master Merek</a>
                <a href="{{ route('admin.kategori.index') }}" class="nav-link text-white">Master Kategori</a>
                <a href="{{ route('admin.collection.index') }}" class="nav-link text-white">Master Collection</a>
                <a href="{{ route('admin.type.index') }}" class="nav-link text-white">Master Type</a>
                <a href="{{ route('admin.variant.index') }}" class="nav-link text-white">Master Variant</a>
                <a href="{{ route('admin.produk.index') }}" class="nav-link text-white">Master Produk</a>
                <a href="{{ route('admin.banner.index') }}" class="nav-link text-white">Master Banner</a>
                <a href="{{ route('admin.news.index') }}" class="nav-link text-white">Master News</a>
                <a href="{{ route('admin.kontak.index') }}" class="nav-link text-white">Pesan Kontak</a>
                <a href="{{ route('admin.log-aktivitas.index') }}" class="nav-link text-white">Log Aktivitas</a>
            </nav>
        </div>
    </div>

    {{-- Main Content Area --}}
    <div class="flex-grow-1">
        {{-- Top Bar --}}
        <header class="bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminDrawer">Menu</button>
                <h4 class="h5 fw-bold mb-0">@yield('page_title', 'Admin Panel')</h4>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">Halo, <strong>{{ auth()->user()->name ?? 'Administrator' }}</strong></span>
                <form action="{{ route('admin.logout') }}" method="POST" class="d-none d-lg-block">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Logout</button>
                </form>
            </div>
        </header>

        <main class="p-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

{{-- Mobile Bottom Nav Bar --}}
@include('components.admin-bottom-nav')

<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
