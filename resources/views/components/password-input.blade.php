{{-- Password input with show/hide eye toggle (handled by public/js/app.js). --}}
@props(['name', 'id' => null])
@php($inputId = $id ?? $name)
<div class="input-group">
    <x-input :name="$name" :id="$inputId" type="password" {{ $attributes }} />
    <button
        type="button"
        class="input-group__addon password-toggle"
        data-toggle-password="{{ $inputId }}"
        aria-pressed="false"
        title="Lihat password"
        aria-label="Lihat password"
    ><x-icon name="eye" class="password-toggle__show" /><x-icon name="eye-off" class="password-toggle__hide" /></button>
</div>

