{{--
    Main menu (App\Support\Menu::grouped), top menu bar only:
    - drawer below 1024px: group buttons hidden, every item listed in Laravel 7 order;
    - top menu bar (>= 1024px): a group is a button that opens its list (click / Enter / Space,
      Esc closes; public/js/app.js). No hover-only menus.
    "*.index" items stay highlighted on the other pages of the same module (archives.*).
    data-nav-badge / data-badge-sum: badge counts set by public/js/pages/notifications.js (CSS ::after, no extra markup).
--}}
@php
    $isActive = fn (array $item): bool => request()->routeIs($item['route'], str_ends_with($item['route'], '.index') ? substr($item['route'], 0, -5).'*' : $item['route']);
@endphp
<nav class="topnav" id="main-nav" aria-label="Menu utama">
    <ul class="nav">
        @foreach ($groups as $index => $group)
            @if ($group['label'] === null)
                @foreach ($group['items'] as $item)
                    <li><a href="{{ route($item['route']) }}" @class(['nav__link', 'is-active' => $isActive($item)]) data-nav-badge="{{ $item['route'] }}" @if ($isActive($item)) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                @endforeach
            @else
                @php($groupActive = collect($group['items'])->contains($isActive))
                <li class="nav__group">
                    <button type="button" @class(['nav__group-toggle', 'is-active' => $groupActive]) aria-expanded="false" aria-controls="nav-group-{{ $index }}" data-nav-group data-badge-sum>{{ $group['label'] }}</button>
                    <ul class="nav__sub" id="nav-group-{{ $index }}">
                        @foreach ($group['items'] as $item)
                            <li><a href="{{ route($item['route']) }}" @class(['nav__link', 'is-active' => $isActive($item)]) data-nav-badge="{{ $item['route'] }}" @if ($isActive($item)) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </li>
            @endif
        @endforeach
    </ul>
</nav>
