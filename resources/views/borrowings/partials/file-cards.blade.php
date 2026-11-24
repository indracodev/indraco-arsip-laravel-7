{{-- Files of one penarikan ($files, first index $firstFile in the page viewer #file-viewer). --}}
@if ($files === [])
    <p class="text-muted">Berkas surat permohonan / persetujuan belum diunggah.</p>
@else
    <div class="file-list">
        @foreach ($files as $file)
            <div class="file-card">
                <div>
                    <div class="file-card__name">{{ $file['name'] }}</div>
                    <div class="text-muted text-sm">{{ $file['note'] }}</div>
                </div>
                <div class="cluster">
                    <x-button size="sm" variant="primary" data-modal-open="file-viewer" data-file-index="{{ $firstFile + $loop->index }}"><x-icon name="eye" />Lihat</x-button>
                    <x-link-button size="sm" :href="$file['download']" download>Unduh</x-link-button>
                </div>
            </div>
        @endforeach
    </div>
@endif
