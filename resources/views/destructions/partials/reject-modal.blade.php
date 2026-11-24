{{-- "Tolak Pengajuan" modal (Laravel 7 destruction_modals:220-286), one per pending row:
     $id, $action, $archive, $title, $hint, $placeholder. --}}
<x-modal :id="$id" :title="$title">
    <form method="POST" action="{{ $action }}" class="stack">
        @csrf
        <p class="text-sm text-muted">{{ $hint }}</p>
        <dl class="meta-grid">
            <div><dt>NO. BOX</dt><dd class="mono">{{ $archive->box_number ?: '-' }}</dd></div>
            <div><dt>BERKAS ARSIP</dt><dd>{{ $archive->title }}</dd></div>
        </dl>
        <x-field label="ALASAN PENOLAKAN" :for="'rejection_note_'.$id" required error="rejection_note">
            <x-textarea name="rejection_note" :id="'rejection_note_'.$id" rows="3" required maxlength="500" :placeholder="$placeholder" />
        </x-field>
        <div class="cluster">
            <span class="topbar__spacer"></span>
            <x-button data-modal-close>Batal</x-button>
            <x-button type="submit" variant="danger">Tolak Pengajuan</x-button>
        </div>
    </form>
</x-modal>
