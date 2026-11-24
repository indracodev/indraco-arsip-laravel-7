{{-- <x-input name="email" type="email" :value="old('email')" /> ; id defaults to name; red border on a validation error. --}}
@props(['name', 'type' => 'text', 'value' => null, 'id' => null])
<input type="{{ $type }}" name="{{ $name }}" id="{{ $id ?? $name }}"
    @if ($value !== null && $type !== 'password') value="{{ $value }}" @endif
    @error($name) aria-invalid="true" @enderror
    {{ $attributes->class(['control', 'is-invalid' => $errors->has($name)]) }}>
