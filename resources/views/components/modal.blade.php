{{--
    <x-modal id="settings-modal" title="Pengaturan Tampilan"> body <x-slot:footer> buttons </x-slot:footer> </x-modal>
    Open with any element [data-modal-open="settings-modal"]; [data-modal-close] closes (public/js/modal.js).
--}}
@props(['id', 'title'])
<div {{ $attributes->class(['modal']) }} id="{{ $id }}" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" hidden>
    <div class="modal__dialog" tabindex="-1">
        <div class="modal__header">
            <h2 class="modal__title" id="{{ $id }}-title">{{ $title }}</h2>
            <x-button variant="ghost" size="sm" class="btn--icon" data-modal-close aria-label="Tutup"><x-icon name="x" /></x-button>
        </div>
        <div class="modal__body">{{ $slot }}</div>
        @isset($footer)
            <div class="modal__footer">{{ $footer }}</div>
        @endisset
    </div>
</div>
