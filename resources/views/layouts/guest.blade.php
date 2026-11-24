{{-- Pages without login (login). --}}
@php($appName = \App\Support\AppSettings::get('app_name'))
<!DOCTYPE html>
<html lang="id" data-font-size="{{ \App\Support\AppSettings::get('app_font_size') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ $appName }}</title>
    @include('layouts.partials.head-assets')
</head>
<body>
<main class="guest">
    <div class="guest__box">
        <div class="guest__brand">
            <img class="guest__logo" src="{{ asset(\App\Support\AppSettings::get('app_logo')) }}" alt="Logo {{ $appName }}" width="161" height="32">
        </div>
        @yield('content')
    </div>
</main>
<x-toast />
<x-confirm-modal />
</body>
</html>
