{{--
    GET filter form above a list: <x-filter-bar :action="route('archives.index')" :active="$hasFilters"> fields </x-filter-bar>
    Laravel 7: button "Filter" and, when a filter is active, a reset link back to the list.
--}}
@props(['action', 'active' => false])
<form method="GET" action="{{ $action }}" {{ $attributes->class(['filter-bar']) }} role="search">
    {{ $slot }}
    <div class="filter-bar__actions">
        <x-button type="submit" variant="primary">Filter</x-button>
        @if ($active)
            <x-link-button :href="$action" class="btn--icon" title="Reset Filter" aria-label="Reset Filter"><x-icon name="x" /></x-link-button>
        @endif
    </div>
</form>
