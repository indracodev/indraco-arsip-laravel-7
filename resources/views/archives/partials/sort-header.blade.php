{{-- Sortable column header (Laravel 7: click toggles asc/desc, arrow on the active column). --}}
@php
    $isCurrent = request('sort') === $column;
    $direction = request('direction', 'desc');
    $next = $isCurrent && $direction === 'asc' ? 'desc' : 'asc';
@endphp
<th @if ($isCurrent) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next, 'page' => null]) }}">{{ $label }}@if ($isCurrent) {{ $direction === 'asc' ? '▲' : '▼' }}@endif</a>
</th>
