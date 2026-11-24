{{-- <x-card title="Judul"> isi <x-slot:actions>tombol</x-slot:actions> <x-slot:footer>...</x-slot:footer> </x-card> --}}
@props(['title' => null])
<section {{ $attributes->class(['card']) }}>
    @if ($title || isset($actions))
        <header class="card__header">
            @if ($title)<h2 class="card__title">{{ $title }}</h2>@endif
            @isset($actions)<div class="cluster">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div class="card__body">{{ $slot }}</div>
    @isset($footer)
        <footer class="card__footer">{{ $footer }}</footer>
    @endisset
</section>
