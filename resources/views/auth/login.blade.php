@extends('layouts.minimal')

@section('title', 'Login')

@section('content')
    <h1>DOCUMENT MANAGEMENT SYSTEM</h1>

    {{-- No prefilled credentials (finding #1). --}}
    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <label for="email">NAMA PENGGUNA / EMAIL</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@indraco.com">
        @error('email')
            <span class="error">{{ $message }}</span>
        @enderror

        <label for="password">KATA SANDI / PASSWORD</label>
        <input type="password" name="password" id="password" required autocomplete="current-password">
        @error('password')
            <span class="error">{{ $message }}</span>
        @enderror

        <button type="submit">Login</button>
    </form>
@endsection
