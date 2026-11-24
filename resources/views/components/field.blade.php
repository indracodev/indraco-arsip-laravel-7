{{--
    Label + control + hint + validation error.
    <x-field label="NAMA / JUDUL BOX" for="title" required hint="..." error="title"> <x-input name="title" /> </x-field>
    `optional` adds the Laravel 7 "(Opsional)" marker.
--}}
@props(['label', 'for' => null, 'required' => false, 'optional' => false, 'hint' => null, 'error' => null])
<div {{ $attributes->class(['field']) }}>
    <label class="field__label" @if ($for) for="{{ $for }}" @endif>
        {{ $label }}
        @if ($required)<span class="field__required" aria-hidden="true">*</span>@endif
        @if ($optional)<span class="field__optional">(Opsional)</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)
        <span class="field__hint">{{ $hint }}</span>
    @endif
    @if ($error)
        @error($error)
            <span class="field__error" role="alert">{{ $message }}</span>
        @enderror
    @endif
</div>
