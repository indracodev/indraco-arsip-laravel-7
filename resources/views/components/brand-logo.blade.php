{{-- Adaptive brand logo: pairs app_logo (light) and app_logo_dark (dark) via public/js/prefs.js
     without a background chip. If Admin uploaded only a custom light logo without a dark variant,
     the custom logo is reused in both themes with the --color-logo-chip fallback in dark mode. --}}
@props([
    'src' => null,
    'alt' => null,
    'darkSrc' => null,
])
@php
    $appName = \App\Support\AppSettings::get('app_name');
    $lightPath = ltrim((string) ($src ?? \App\Support\AppSettings::get('app_logo')), '/');
    $darkPath = ltrim((string) ($darkSrc ?? \App\Support\AppSettings::get('app_logo_dark')), '/');
    $isDefaultLight = in_array($lightPath, ['images/logo-indraco.png', 'images/logo_indraco.png'], true);
    $isDefaultDark = in_array($darkPath, ['images/logo-indraco-invert.png', 'images/logo_indraco_invert.png'], true);

    $lightUrl = $isDefaultLight ? \App\Support\Asset::url('images/logo-indraco.png') : asset($lightPath);
    if (! $isDefaultLight && $isDefaultDark) {
        $darkUrl = null;
    } else {
        $darkUrl = $isDefaultDark ? \App\Support\Asset::url('images/logo-indraco-invert.png') : asset($darkPath);
    }

    $adaptive = $darkUrl !== null;
@endphp
<img
    src="{{ $lightUrl }}"
    @if ($adaptive)
        data-img-src-light="{{ $lightUrl }}"
        data-img-src-dark="{{ $darkUrl }}"
    @endif
    alt="{{ $alt ?? 'Logo '.$appName }}"
    {{ $attributes->class(['brand-logo', 'brand-logo--adaptive' => $adaptive, 'brand-logo--custom' => ! $adaptive]) }}
>
