{{-- <x-page-header title="Katalog Arsip" subtitle="..."> <x-slot:actions>tombol</x-slot:actions> </x-page-header> --}}
@props(['title', 'subtitle' => null])
<div {{ $attributes->class(['page-header']) }}>
    <div>
        <h1>{{ $title }}</h1>
        @if ($subtitle)<p class="page-header__subtitle">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)
        <div class="page-header__actions">{{ $actions }}</div>
    @endisset
</div>
