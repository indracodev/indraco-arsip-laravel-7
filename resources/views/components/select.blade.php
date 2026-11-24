{{-- <x-select name="status" :options="['a' => 'Label']" :selected="request('status')" placeholder="-- Semua --" /> --}}
@props(['name', 'options' => [], 'selected' => null, 'placeholder' => null, 'id' => null])
<select name="{{ $name }}" id="{{ $id ?? $name }}" @error($name) aria-invalid="true" @enderror
    {{ $attributes->class(['control', 'is-invalid' => $errors->has($name)]) }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
    {{ $slot }}
</select>
