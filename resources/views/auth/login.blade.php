@extends('layouts.guest')

@section('title', 'Login')

@section('guest-brand')@endsection

@section('content')
   <x-card>
      <form method="POST" action="{{ route('login.post') }}" class="stack">
         @csrf
         <div class="guest__brand guest__brand--card">
            <x-brand-logo class="guest__logo" width="161" height="32" />
            <h1 class="fs-2">DOCUMENT MANAGEMENT SYSTEM</h1>
         </div>

         {{-- No prefilled credentials (finding #1). Alt+E / Alt+P as in Laravel 7. --}}
         <x-field label="NAMA PENGGUNA / EMAIL" for="email" error="email">
            <x-input name="email" type="email" :value="old('email')" required autofocus autocomplete="username"
               accesskey="e" placeholder="nama@indraco.com" />
         </x-field>

         <x-field label="KATA SANDI / PASSWORD" for="password" error="password">
            <x-password-input name="password" required autocomplete="current-password" accesskey="p" />
         </x-field>

         <x-button type="submit" variant="primary"><x-icon name="key" />Login</x-button>
      </form>
   </x-card>
@endsection
