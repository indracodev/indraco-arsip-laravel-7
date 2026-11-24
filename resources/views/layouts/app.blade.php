{{--
    Application shell (replaces the Laravel 7 MDI "Delphi" windows, ASK 1): top bar + menu per
    role (App\Support\Menu) as a top menu bar (drawer below 1024px) + page. Pages: @extends('layouts.app'), @section('title'), @section('content').
--}}
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $appName = \App\Support\AppSettings::get('app_name');
@endphp
<!DOCTYPE html>
<html lang="id" data-font-size="{{ \App\Support\AppSettings::get('app_font_size') }}" @if ($user->font_size) data-user-font-size="{{ $user->font_size }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ $appName }}</title>
    {{-- sound.js before Alpine (head-assets): the settings modal reads window.dmsSound on init. --}}
    <script src="{{ \App\Support\Asset::url('js/sound.js') }}" defer></script>
    @include('layouts.partials.head-assets')
    <script src="{{ \App\Support\Asset::url('js/lan.js') }}" defer></script>
    <script src="{{ \App\Support\Asset::url('js/pages/notifications.js') }}" defer></script>
</head>
<body x-data="shell" :class="{ 'is-drawer-open': drawer }" @keydown.escape.window="closeDrawer()">
<div class="app">
    <header class="topbar">
        <x-button variant="ghost" class="btn--icon topbar__menu" @click="toggleDrawer()" aria-label="Menu" aria-controls="main-nav" ::aria-expanded="drawer"><x-icon name="menu" /></x-button>
        <a href="{{ route('dashboard') }}" class="topbar__brand" title="{{ $appName }}">
            <img class="topbar__logo" src="{{ asset(\App\Support\AppSettings::get('app_logo')) }}" alt="Logo {{ $appName }}" width="121" height="24">
            <span class="topbar__name">{{ $appName }}</span>
        </a>
        @if ($user->role === \App\Models\User::ROLE_PIC_DEPT)
            {{-- Laravel 7 PIC header button "Baru (F2)" opened "Draft Pengajuan Box Baru". --}}
            <x-link-button :href="route('archives.create')" variant="primary" size="sm" data-shortcut="F2" title="Buka Form Draft Pengajuan Box Baru (F2)">
                <x-icon name="plus" /><span class="hide-sm">Baru (F2)</span>
            </x-link-button>
        @endif
        <span class="topbar__spacer"></span>
        <span class="lan" data-lan data-threshold="{{ (int) \App\Support\AppSettings::get('ping_alert_threshold_ms', '500') }}" @if (\App\Support\AppSettings::get('ping_alert_enabled') === '1') data-alert @endif title="Koneksi server">
            <span class="lan__dot"></span><span class="lan__text">LAN</span>
        </span>
        <span class="topbar__user">
            <span class="topbar__user-name">{{ $user->name }}</span>
            <span class="topbar__user-role">{{ $user->roleLabel() }}</span>
        </span>
        <div class="topbar__actions">
            @include('layouts.partials.notifications')
            {{-- One-click light/dark (also Alt+T, like Laravel 7); label/title set by public/js/prefs.js. --}}
            <x-button variant="ghost" class="btn--icon theme-toggle" data-theme-toggle aria-label="Ganti ke mode gelap (Alt+T)" title="Ganti ke mode gelap (Alt+T)"><x-icon name="moon" class="theme-toggle__moon" /><x-icon name="sun" class="theme-toggle__sun" /></x-button>
            <x-button variant="ghost" class="btn--icon" data-modal-open="settings-modal" title="Pengaturan Tampilan" aria-label="Pengaturan Tampilan"><x-icon name="settings" /></x-button>
            <x-link-button :href="route('profile.password')" variant="ghost" class="btn--icon" title="Ganti Password Saya" aria-label="Ganti Password Saya"><x-icon name="key" /></x-link-button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-button type="submit" variant="ghost" class="btn--icon" title="Keluar / Logout" aria-label="Keluar / Logout"><x-icon name="logout" /></x-button>
            </form>
        </div>
    </header>

    @include('layouts.partials.menu', ['groups' => \App\Support\Menu::grouped($user)])
    <div class="backdrop" @click="closeDrawer()"></div>

    <main class="main" id="main">
        @include('users.partials.impersonation-banner')
        @yield('content')
    </main>
</div>

<x-toast />
<x-confirm-modal />
@include('layouts.partials.settings-modal')
@include('layouts.partials.expiry-modal')
</body>
</html>
