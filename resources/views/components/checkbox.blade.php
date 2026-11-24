{{-- <x-checkbox name="follow_previous" label="Ikuti Periode Box Sebelumnya" :checked="false" /> --}}
@props(['name', 'label' => null, 'value' => '1', 'checked' => false])
<label class="checkbox">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($checked) {{ $attributes }}>
    @if ($label !== null)<span>{{ $label }}</span>@endif
</label>
