@if ($paginator->hasPages())
    <nav class="ls-pagination-wrapper" role="navigation" aria-label="Pagination Navigation">
        <div class="ls-pagination-info">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
        </div>

        <div class="ls-pagination">
            @if ($paginator->onFirstPage())
                <span class="ls-page-link disabled">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="ls-page-link">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="ls-page-link disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="ls-page-link active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="ls-page-link">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="ls-page-link">Next</a>
            @else
                <span class="ls-page-link disabled">Next</span>
            @endif
        </div>
    </nav>
@endif
