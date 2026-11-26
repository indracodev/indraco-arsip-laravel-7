{{-- Adaptive brand logo: default Indraco logo switches cleanly between logo-indraco.png (light)
     and logo-indraco-invert.png (dark) via public/js/prefs.js without a background chip.
     Custom logos uploaded by Admin keep their uploaded asset and use --color-logo-chip in dark mode. --}}
@props([
    'src' => null,
    'alt' => null,
    'darkSrc' => null,
])
@php
    $appName = \App\Support\AppSettings::get('app_name');
    $source = ltrim((string) ($src ?? \App\Support\AppSettings::get('app_logo')), '/');
    $isDefaultLogo = in_array($source, ['images/logo-indraco.png', 'images/logo_indraco.png'], true);

    if ($isDefaultLogo) {
        $lightUrl = \App\Support\Asset::url('images/logo-indraco.png');
        $darkUrl = \App\Support\Asset::url('images/logo-indraco-invert.png');
    } else {
        $lightUrl = asset($source);
        $darkUrl = $darkSrc ? \App\Support\Asset::url(ltrim((string) $darkSrc, '/')) : null;
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

