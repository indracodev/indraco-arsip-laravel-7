{{-- <x-link-button href="..." variant="primary" size="sm">Detail</x-link-button> --}}
@props(['href', 'variant' => 'secondary', 'size' => null])
<a href="{{ $href }}" {{ $attributes->class(['btn', 'btn--'.$variant => $variant !== 'secondary', 'btn--'.$size => $size]) }}>{{ $slot }}</a>
