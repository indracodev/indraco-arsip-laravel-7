{{-- GET form of one tab's search box (input sits elsewhere with form="<id>"): Enter searches every page on the
     server (?q=, P1); typing still filters the rows of this page (destruction-index.js). --}}
<form method="GET" action="{{ route('destructions.index') }}" id="{{ $id }}" role="search" hidden>
    <input type="hidden" name="tab" value="{{ $tab }}">
    @foreach (['sort', 'direction'] as $keep)
        @if (filled(request($keep)))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif
    @endforeach
</form>
