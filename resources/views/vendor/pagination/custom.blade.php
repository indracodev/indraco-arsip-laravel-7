@if ($paginator->hasPages())
    <nav class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 w-100 mt-4">
        <!-- Text Summary -->
        <div class="small text-secondary text-center text-md-start">
            Showing <span class="fw-bold text-body">{{ $paginator->firstItem() }}</span> to <span class="fw-bold text-body">{{ $paginator->lastItem() }}</span> of <span class="fw-bold text-body">{{ $paginator->total() }}</span> results
        </div>

        <!-- Desktop Pagination Links (d-none d-md-flex) -->
        <ul class="pagination pagination-custom mb-0 d-none d-md-flex flex-wrap justify-content-center">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">&laquo; Prev</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Prev</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &raquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">Next &raquo;</span>
                </li>
            @endif
        </ul>

        <!-- Mobile Pagination Compact Buttons (d-flex d-md-none) -->
        <div class="d-flex d-md-none align-items-center justify-content-between w-100 gap-2">
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-outline-secondary rounded-pill px-3 disabled">&laquo; Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">&laquo; Prev</a>
            @endif

            <span class="small fw-semibold text-secondary">
                Halaman {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Next &raquo;</a>
            @else
                <span class="btn btn-sm btn-outline-secondary rounded-pill px-3 disabled">Next &raquo;</span>
            @endif
        </div>
    </nav>
@endif
