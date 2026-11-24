@extends('layouts.minimal')

@section('title', 'Dashboard')

@section('content')
    @php($user = auth()->user())
    <h1>{{ config('app.name') }}</h1>
    <p>Masuk sebagai <strong>{{ $user->name }}</strong> ({{ $user->roleLabel() }})</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

    <h2>Ganti Password</h2>
    <form method="POST" action="{{ route('profile.change_password') }}">
        @csrf
        <label for="current_password">PASSWORD SAAT INI *</label>
        <input type="password" name="current_password" id="current_password" required autocomplete="current-password" placeholder="Masukkan password Anda saat ini">
        @error('current_password')
            <span class="error">{{ $message }}</span>
        @enderror

        <label for="password">PASSWORD BARU * (Min. 6 Karakter)</label>
        <input type="password" name="password" id="password" required minlength="6" autocomplete="new-password" placeholder="Ketik password baru minimal 6 karakter">
        @error('password')
            <span class="error">{{ $message }}</span>
        @enderror

        <label for="password_confirmation">ULANGI PASSWORD BARU *</label>
        <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6" autocomplete="new-password">

        <button type="submit">Simpan Password</button>
    </form>
@endsection
