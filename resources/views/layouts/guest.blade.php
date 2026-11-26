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
<main class="guest" style="--guest-bg-image: url('{{ \App\Support\Asset::url('images/indraco-bg.jpg') }}')">
    <div class="guest__toolbar">
        <x-theme-toggle class="guest__theme-toggle" />
    </div>
    <div class="guest__box">
        @section('guest-brand')
            <div class="guest__brand">
                <x-brand-logo class="guest__logo" width="161" height="32" />
            </div>
        @show
        @yield('content')
        <footer class="guest__footer">Developed by Tim WEBDEV. Copyright &copy; 2026 INDRACO. All rights reserved</footer>
    </div>
</main>
<x-toast />
<x-confirm-modal />
</body>
</html>
