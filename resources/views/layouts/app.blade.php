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
<html lang="id" data-font-size="14px"
   @if ($user->font_size) data-user-font-size="{{ $user->font_size }}" @endif>

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

<body>
   <div class="app">
      <header class="topbar">
         <div class="topbar__col topbar__col--app">
            <a href="{{ route('dashboard') }}" class="topbar__brand" title="{{ $appName }}">
               <x-brand-logo class="topbar__logo" width="121" height="24" />
               <span class="topbar__name">{{ $appName }}</span>
            </a>
            <span class="topbar__divider" aria-hidden="true"></span>
            <div class="topbar__tools">
               <x-button variant="ghost" class="btn--icon" data-modal-open="settings-modal" title="Pengaturan Tampilan"
                  aria-label="Pengaturan Tampilan"><x-icon name="settings" /></x-button>
               <x-theme-toggle />
               <span class="lan" data-lan
                  data-threshold="{{ (int) \App\Support\AppSettings::get('ping_alert_threshold_ms', '500') }}"
                  @if (\App\Support\AppSettings::get('ping_alert_enabled') === '1') data-alert @endif title="Koneksi server">
                  <span class="lan__dot"></span><span class="lan__text">LAN</span>
               </span>
            </div>
         </div>

         <div class="topbar__col topbar__col--search">
            <form method="GET" action="{{ route('archives.index') }}" class="topbar__search" role="search"
               data-no-lock>
               <label class="sr-only" for="topbar-search">Cari cepat arsip</label>
               <input id="topbar-search" name="search" type="search" class="control topbar__search-input"
                  value="{{ request()->routeIs('archives.index') ? request('search') : '' }}"
                  placeholder="Cari box / arsip… (Ctrl+K)" autocomplete="off">
            </form>
         </div>

         <div class="topbar__col topbar__col--account">
            <span class="topbar__user">
               <span class="topbar__user-name">{{ $user->name }}</span>
               <span class="topbar__user-role">{{ $user->roleLabel() }}</span>
            </span>
            <span class="topbar__divider" aria-hidden="true"></span>
            <div class="topbar__actions">
               @include('layouts.partials.notifications')
               <x-link-button :href="route('profile.password')" variant="ghost" class="btn--icon" title="Ganti Password Saya"
                  aria-label="Ganti Password Saya"><x-icon name="key" /></x-link-button>
               <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <x-button type="submit" variant="ghost" class="btn--icon" title="Keluar / Logout"
                     aria-label="Keluar / Logout"><x-icon name="logout" /></x-button>
               </form>
            </div>
         </div>
      </header>

      @include('layouts.partials.menu', ['groups' => \App\Support\Menu::grouped($user)])

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
