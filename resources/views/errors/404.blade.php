{{-- 404 page. Shows the message of abort(404, '...') (e.g. a missing archive file); never the
     framework text of an unknown route or record (it names internal classes). --}}
@extends('layouts.guest')

@section('title', 'Tidak Ditemukan')

@php
    $message = $exception->getMessage();
    $own = $message !== '' && $exception->getPrevious() === null && ! str_starts_with($message, 'The route');
@endphp

@section('content')
    <x-card title="404 - Tidak Ditemukan">
        <div class="stack">
            <p>{{ $own ? $message : 'Halaman atau berkas yang diminta tidak ditemukan.' }}</p>
            <x-link-button :href="url('/')">Kembali ke Beranda</x-link-button>
        </div>
    </x-card>
@endsection
