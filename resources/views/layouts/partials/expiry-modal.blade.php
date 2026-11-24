{{-- "Peringatan Masa Simpan Berkas" (Laravel 7 expiry_warning_modal): boxes past or within H-30 of the
     end of their expiry month; shown after login and at most once an hour (public/js/pages/notifications.js). --}}
<x-modal id="expiry-modal" title="Peringatan Masa Simpan Berkas">
    <p class="text-sm"><span class="badge badge--danger" data-expiry-count></span> Terdapat box berkas arsip yang telah melewati batas retensi atau mendekati jatuh tempo (H-30).</p>
    <ul class="expiry-list" data-expiry-list></ul>
    <p class="text-sm text-muted">Pengajuan perpanjangan atau pemusnahan dapat dilakukan di <strong>[Form 3]</strong>.</p>
    <x-slot:footer>
        <x-button data-modal-close>Tutup (Esc)</x-button>
        <x-link-button :href="route('destructions.index', ['view' => 'expiry', 'tab' => 'h30'])" variant="primary">Lihat Berkas Jatuh Tempo</x-link-button>
    </x-slot:footer>
</x-modal>
