{{-- Realtime notification bell (Laravel 7 header bell "LIVE INCOMING DOCUMENTS"): list of this tab's
     notifications, unread count, "Tes Suara". Filled by public/js/pages/notifications.js. --}}
{{-- data-notify = user id: the tab's stored cursor/list belong to this account only (shared PCs). --}}
<div class="notify" data-notify="{{ auth()->id() }}">
    <x-button variant="ghost" class="btn--icon notify__toggle" data-notify-toggle aria-expanded="false" aria-controls="notify-panel" title="Notifikasi" aria-label="Notifikasi">
        <x-icon name="bell" /><span class="notify__count" data-notify-count hidden></span>
    </x-button>
    <div class="notify__panel" id="notify-panel" hidden>
        <div class="notify__head">
            <strong>LIVE INCOMING DOCUMENTS</strong>
            <x-button size="sm" data-notify-test>Tes Suara</x-button>
        </div>
        <ul class="notify__list" data-notify-list></ul>
        <p class="notify__empty" data-notify-empty>Belum ada aktivitas dokumen baru. Dokumen yang ditambahkan oleh client/PIC akan otomatis muncul di sini secara real-time.</p>
    </div>
</div>
