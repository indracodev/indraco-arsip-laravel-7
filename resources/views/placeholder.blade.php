@extends('layouts.app')

@section('title', $title)

@section('content')
    <x-page-header :title="$title" />
    <x-card>
        <x-empty-state icon="refresh" title="Sedang dibangun ulang" message="Halaman ini sedang dibangun ulang dengan tampilan baru dan akan tersedia pada tahap berikutnya." />
    </x-card>
@endsection
