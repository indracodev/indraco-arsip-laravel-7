<div class="admin-bottom-nav fixed-bottom bg-white border-top shadow-lg d-lg-none py-2 px-3">
    <div class="row text-center align-items-center g-0">
        <div class="col">
            <a href="{{ route('admin.dashboard') }}" class="nav-link text-dark {{ request()->routeIs('admin.dashboard') ? 'fw-bold text-primary' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-speedometer2 d-block mx-auto mb-1" viewBox="0 0 16 16">
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3.5A.5.5 0 0 1 8 8V4.5A.5.5 0 0 1 8 4z"/>
                    <path d="M3.732 5.732a.5.5 0 0 1 .707 0l1.414 1.414a.5.5 0 1 1-.707.707L3.732 6.439a.5.5 0 0 1 0-.707zM11.439 6.439a.5.5 0 0 1 .707 0l1.414-1.414a.5.5 0 0 1-.707-.707L11.439 5.732a.5.5 0 0 1 0 .707z"/>
                    <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 1a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                </svg>
                <small style="font-size: 0.7rem;">Dashboard</small>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.produk.index') }}" class="nav-link text-dark {{ request()->routeIs('admin.produk.*') ? 'fw-bold text-primary' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-seam d-block mx-auto mb-1" viewBox="0 0 16 16">
                    <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/>
                </svg>
                <small style="font-size: 0.7rem;">Produk</small>
            </a>
        </div>
        <div class="col">
            <button class="btn btn-link nav-link p-0 text-dark w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminDrawer" aria-controls="adminDrawer">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-grid-fill d-block mx-auto mb-1" viewBox="0 0 16 16">
                    <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zm8 0A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm-8 8A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm8 0A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3z"/>
                </svg>
                <small style="font-size: 0.7rem;">Menu</small>
            </button>
        </div>
        <div class="col">
            <a href="{{ route('admin.kontak.index') }}" class="nav-link text-dark {{ request()->routeIs('admin.kontak.*') ? 'fw-bold text-primary' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-envelope d-block mx-auto mb-1" viewBox="0 0 16 16">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                </svg>
                <small style="font-size: 0.7rem;">Pesan</small>
            </a>
        </div>
        <div class="col">
            <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link nav-link p-0 text-danger w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-right d-block mx-auto mb-1" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 1h-8A1.5 1.5 0 0 0 0 2.5v9A1.5 1.5 0 0 0 1.5 13h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                    </svg>
                    <small style="font-size: 0.7rem;">Keluar</small>
                </button>
            </form>
        </div>
    </div>
</div>
