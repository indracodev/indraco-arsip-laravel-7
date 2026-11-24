{{-- <x-button variant="primary|secondary|danger|ghost" size="sm" type="submit">Simpan</x-button> --}}
@props(['variant' => 'secondary', 'size' => null, 'type' => 'button'])
<button type="{{ $type }}" {{ $attributes->class(['btn', 'btn--'.$variant => $variant !== 'secondary', 'btn--'.$size => $size]) }}>{{ $slot }}</button>
