@extends('layouts.app')

@section('title', 'Ganti Password')

@section('content')
    <x-page-header title="Ganti Password" />

    <x-card class="guest__box">
        @if (session('password_success'))
            <p class="notice notice--success">{{ session('password_success') }}</p>
        @endif
        {{-- Same fields, labels and messages as the Laravel 7 modal (layouts/partials/change_password_modal). --}}
        <form method="POST" action="{{ route('profile.change_password') }}" class="stack">
            @csrf
            <x-field label="PASSWORD SAAT INI" for="current_password" required error="current_password">
                <x-input name="current_password" type="password" required autocomplete="current-password" placeholder="Masukkan password Anda saat ini" />
            </x-field>
            <x-field label="PASSWORD BARU (Min. 6 Karakter)" for="password" required error="password">
                <x-input name="password" type="password" required minlength="6" autocomplete="new-password" placeholder="Ketik password baru minimal 6 karakter" />
            </x-field>
            <x-field label="ULANGI PASSWORD BARU" for="password_confirmation" required>
                <x-input name="password_confirmation" type="password" required minlength="6" autocomplete="new-password" />
            </x-field>
            <x-button type="submit" variant="primary">Simpan Password</x-button>
        </form>
    </x-card>
@endsection
