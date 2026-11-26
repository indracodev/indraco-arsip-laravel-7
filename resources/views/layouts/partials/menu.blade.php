{{--
    Main desktop menu (App\Support\Menu::grouped), no dropdowns:
    every item sits directly in the top bar; a former group is one flex block with a subtle
    left separator, so a long menu wraps whole groups cleanly.
    "*.index" items stay highlighted on the other pages of the same module (archives.*).
    data-nav-badge: badge counts set by public/js/pages/notifications.js (CSS ::after, no extra markup).
--}}
@php
    $isActive = fn (array $item): bool => request()->routeIs($item['route'], str_ends_with($item['route'], '.index') ? substr($item['route'], 0, -5).'*' : $item['route']);
@endphp
<nav class="topnav" id="main-nav" aria-label="Menu utama">
    <ul class="nav">
        @foreach ($groups as $group)
            @if ($group['label'] === null)
                @foreach ($group['items'] as $item)
                    <li><a href="{{ route($item['route']) }}" @class(['nav__link', 'is-active' => $isActive($item)]) data-nav-badge="{{ $item['route'] }}" @if ($isActive($item)) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                @endforeach
            @else
                <li class="nav__group">
                    <span class="nav__heading" aria-hidden="true">{{ $group['label'] }}</span>
                    <ul class="nav__sub" aria-label="{{ $group['label'] }}">
                        @foreach ($group['items'] as $item)
                            <li><a href="{{ route($item['route']) }}" @class(['nav__link', 'is-active' => $isActive($item)]) data-nav-badge="{{ $item['route'] }}" @if ($isActive($item)) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </li>
            @endif
        @endforeach
    </ul>
</nav>
