{{--
    <x-pagination :paginator="$archives" noun="berkas arsip" />
    Laravel 7 text: "Menampilkan a - b dari n total ..."; pages: first, last and 2 around the current one.
--}}
@props(['paginator', 'noun' => 'data'])
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = collect([1, $last])->merge(range(max(1, $current - 2), min($last, $current + 2)))->unique()->sort()->values();
@endphp
<nav {{ $attributes->class(['pagination']) }} aria-label="Navigasi halaman">
    <span>Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }}</strong> - <strong>{{ $paginator->lastItem() ?? 0 }}</strong> dari <strong>{{ $paginator->total() }}</strong> total {{ $noun }}</span>
    @if ($last > 1)
        <ul class="pagination__pages">
            <li><a class="pagination__link {{ $paginator->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $paginator->previousPageUrl() ?? '#' }}" rel="prev" aria-label="Sebelumnya">&lsaquo;</a></li>
            @foreach ($pages as $i => $page)
                @if ($i > 0 && $page - $pages[$i - 1] > 1)
                    <li><span class="pagination__link is-disabled">&hellip;</span></li>
                @endif
                <li><a class="pagination__link {{ $page === $current ? 'is-current' : '' }}" href="{{ $paginator->url($page) }}" @if ($page === $current) aria-current="page" @endif>{{ $page }}</a></li>
            @endforeach
            <li><a class="pagination__link {{ $paginator->hasMorePages() ? '' : 'is-disabled' }}" href="{{ $paginator->nextPageUrl() ?? '#' }}" rel="next" aria-label="Berikutnya">&rsaquo;</a></li>
        </ul>
    @endif
</nav>
