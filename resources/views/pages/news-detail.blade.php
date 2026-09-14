@extends('layouts.app')

@section('title', $news->localized_judul . ' - INDRACO News')

@section('content')
<main id="content" tabindex="-1" class="py-5">
    <div class="container">
        
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('news') }}" class="text-decoration-none text-muted">News &amp; Media</a></li>
                <li class="breadcrumb-item active text-dark fw-medium text-truncate style-breadcrumb-max" aria-current="page" style="max-width: 300px;">{{ $news->localized_judul }}</li>
            </ol>
        </nav>

        <div class="row g-5">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <article class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm">
                    
                    <!-- Article Meta & Title -->
                    <div class="mb-4">
                        <span class="badge bg-custom-1 text-white mb-2 px-3 py-2 rounded-pill">{{ $news->formatted_tanggal }}</span>
                        <h1 class="h2 fw-bold text-dark mb-3 mt-2 lh-base">{{ $news->localized_judul }}</h1>
                        
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 text-muted small pt-2 border-top border-bottom py-2">
                            <div>
                                <span class="me-3">✍️ Tim Redaksi INDRACO</span>
                                <span>📅 {{ $news->formatted_tanggal }}</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-circle" title="Share Facebook">f</a>
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode($news->localized_judul) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-circle" title="Share Twitter">X</a>
                                <a href="https://api.whatsapp.com/send?text={{ urlencode($news->localized_judul . ' - ' . request()->fullUrl()) }}" target="_blank" class="btn btn-sm btn-outline-success rounded-circle" title="Share WhatsApp">WA</a>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="mb-4 overflow-hidden rounded-4 shadow-sm">
                        <img src="{{ $news->image_url }}" alt="{{ $news->localized_judul }}" class="img-fluid w-100 object-fit-cover" style="max-height: 450px;">
                    </div>

                    <!-- Body Content -->
                    <div class="article-body lh-lg fs-6 text-body">
                        {!! nl2br(e($news->localized_content)) !!}
                    </div>

                    <!-- Back Button & Footer Share -->
                    <div class="mt-5 pt-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <a href="{{ route('news') }}" class="btn btn-outline-secondary rounded-pill px-4">
                            &larr; Kembali ke Daftar Berita
                        </a>
                    </div>
                </article>
            </div>

            <!-- Sidebar: Related News -->
            <div class="col-lg-4">
                <aside class="sticky-top" style="top: 100px;">
                    <div class="bg-body-secondary p-4 rounded-4 shadow-sm mb-4">
                        <h2 class="h5 fw-bold mb-4 pb-2 border-bottom">Berita Terkait</h2>

                        <div class="d-flex flex-column gap-4">
                            @forelse($relatedNews as $item)
                                <div class="d-flex gap-3 align-items-start">
                                    <img src="{{ $item->image_url }}" alt="{{ $item->localized_judul }}" class="rounded shadow-sm object-fit-cover" style="width: 80px; height: 60px; flex-shrink: 0;">
                                    <div>
                                        <small class="text-muted d-block mb-1">{{ $item->formatted_tanggal }}</small>
                                        <h3 class="fs-6 fw-bold mb-0">
                                            <a href="{{ route('news.detail', $item->slug) }}" class="text-dark text-decoration-none hover-primary">
                                                {{ Str::limit($item->localized_judul, 60) }}
                                            </a>
                                        </h3>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Tidak ada berita terkait saat ini.</p>
                            @endforelse
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</main>
@endsection
