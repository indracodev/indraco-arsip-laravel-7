{{-- <x-file-input name="scan_input_form" accept=".pdf,.jpg,.jpeg,.png" badge="WAJIB UNTUK PENGAJUAN" /> --}}
@props(['name', 'id' => null, 'badge' => null])
<div class="file-input">
    <input type="file" name="{{ $name }}" id="{{ $id ?? $name }}" @error($name) aria-invalid="true" @enderror {{ $attributes }}>
    @if ($badge)
        <span class="badge badge--outline">{{ $badge }}</span>
    @endif
</div>
