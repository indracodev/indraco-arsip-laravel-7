@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-end">
        <div class="flex items-center gap-1.5 font-mono text-xs">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 bg-slate-100/50 dark:bg-slate-900/50 cursor-not-allowed inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Prev</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 hover:border-amber-500 transition-all inline-flex items-center gap-1 shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Prev</span>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="px-2 py-1 text-xs font-bold text-slate-400 dark:text-slate-600 cursor-default">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="px-3 py-1 text-xs font-black rounded-lg bg-amber-500 text-slate-950 border border-amber-400 shadow-xs cursor-default">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all shadow-xs">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 hover:border-amber-500 transition-all inline-flex items-center gap-1 shadow-xs">
                    <span>Next</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 bg-slate-100/50 dark:bg-slate-900/50 cursor-not-allowed inline-flex items-center gap-1">
                    <span>Next</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
