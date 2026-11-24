{{-- <x-empty-state title="..." message="..." icon="folder" /> --}}
@props(['title', 'message' => null, 'icon' => 'folder'])
<div {{ $attributes->class(['empty-state']) }}>
    <x-icon :name="$icon" />
    <p class="empty-state__title">{{ $title }}</p>
    @if ($message)<p>{{ $message }}</p>@endif
    {{ $slot }}
</div>
