{{-- <x-textarea name="notes" :value="old('notes')" rows="3" /> --}}
@props(['name', 'value' => null, 'id' => null])
<textarea name="{{ $name }}" id="{{ $id ?? $name }}" @error($name) aria-invalid="true" @enderror
    {{ $attributes->class(['control', 'is-invalid' => $errors->has($name)]) }}>{{ $value }}</textarea>
