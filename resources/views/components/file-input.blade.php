{{-- <x-file-input name="scan_input_form" accept=".pdf,.jpg,.jpeg,.png" badge="WAJIB UNTUK PENGAJUAN" />
     An accept list with .pdf (FileStorage::rules uploads) turns on in-browser compression of files > 500 KB
     (public/js/upload-compress.js); Excel imports and the logo are left alone. --}}
@props(['name', 'id' => null, 'badge' => null])
@php
    $inputId = $id ?? $name;
    $compress = str_contains((string) $attributes->get('accept'), '.pdf');
@endphp
<div class="file-input">
    <input type="file" name="{{ $name }}" id="{{ $inputId }}" @error($name) aria-invalid="true" @enderror @if ($compress) data-compress aria-describedby="{{ $inputId }}_compress_status" @endif {{ $attributes }}>
    @if ($badge)
        <span class="badge badge--outline">{{ $badge }}</span>
    @endif
</div>
@if ($compress)
    <span class="field__hint">Berkas &gt; 500 KB akan dikompres otomatis</span>
    <span class="field__hint" id="{{ $inputId }}_compress_status" aria-live="polite" hidden></span>
    @pushOnce('scripts')
        <script src="{{ \App\Support\Asset::url('js/upload-compress.js') }}" data-pdfjs="{{ \App\Support\Asset::url('js/vendor/pdfjs/pdf.min.mjs') }}" data-pdfjs-worker="{{ \App\Support\Asset::url('js/vendor/pdfjs/pdf.worker.min.mjs') }}" defer></script>
    @endpushOnce
@endif
