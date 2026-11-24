{{-- The only place static assets are included (rules/50-frontend-blade.md): 4 stylesheets, local only, ?v=<content hash>. --}}
@foreach (\App\Support\Asset::STYLESHEETS as $stylesheet)
    <link rel="stylesheet" href="{{ \App\Support\Asset::url($stylesheet) }}">
@endforeach
<link rel="icon" type="image/png" href="{{ \App\Support\Asset::url('images/icon-192.png') }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#262f3b">
<script src="{{ \App\Support\Asset::url('js/prefs.js') }}"></script>
<script src="{{ \App\Support\Asset::url('js/modal.js') }}" defer></script>
<script src="{{ \App\Support\Asset::url('js/app.js') }}" defer></script>
@stack('scripts')
<script src="{{ \App\Support\Asset::url('js/vendor/alpine.min.js') }}" defer></script>
