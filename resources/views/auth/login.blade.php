@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <x-card>
        <form method="POST" action="{{ route('login.post') }}" class="stack">
            @csrf
            <h1>DOCUMENT MANAGEMENT SYSTEM</h1>

            {{-- No prefilled credentials (finding #1). Alt+E / Alt+P as in Laravel 7. --}}
            <x-field label="NAMA PENGGUNA / EMAIL" for="email" error="email">
                <x-input name="email" type="email" :value="old('email')" required autofocus autocomplete="username" accesskey="e" placeholder="nama@indraco.com" />
            </x-field>

            <x-field label="KATA SANDI / PASSWORD" for="password" error="password">
                <div class="input-group">
                    <x-input name="password" type="password" required autocomplete="current-password" accesskey="p" />
                    <button type="button" class="input-group__addon" data-toggle-password="password" title="Lihat / sembunyikan password" aria-label="Lihat / sembunyikan password"><x-icon name="eye" /></button>
                </div>
            </x-field>

            <x-button type="submit" variant="primary"><x-icon name="key" />Login</x-button>
        </form>
    </x-card>
@endsection
