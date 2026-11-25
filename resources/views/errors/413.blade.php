{{-- S5: request body above post_max_size (bootstrap/app.php passes $message). --}}
@extends('layouts.guest')

@section('title', 'Data Terlalu Besar')

@section('content')
    <x-card title="413 - Data Terlalu Besar">
        <div class="stack">
            <p>{{ $message ?? 'Ukuran data yang dikirim terlalu besar.' }}</p>
            <x-link-button :href="url()->previous()">Kembali</x-link-button>
        </div>
    </x-card>
@endsection
