{{-- Detail Arsip: "Dokumentasi Scan & Lampiran Digital" + viewer modal (public/js/pages/archive-show.js).
     Every link goes through files.preview_data (viewer JSON, R27) / files.download (login + ArchivePolicy), never a storage path. --}}
<x-card title="Dokumentasi Scan & Lampiran Digital">
    @if ($files === [])
        <x-empty-state title="Belum ada scan formulir atau lampiran berkas digital yang diunggah." />
    @else
        <div class="file-list">
            @foreach ($files as $file)
                <div class="file-card">
                    <div>
                        <div class="file-card__name">{{ $file['name'] }}</div>
                        <div class="text-muted text-sm">{{ $file['note'] }}</div>
                    </div>
                    <div class="cluster">
                        <x-button size="sm" variant="primary" data-modal-open="file-viewer" data-file-index="{{ $loop->index }}"><x-icon name="eye" />Lihat</x-button>
                        <x-link-button size="sm" :href="$file['download']" download>Unduh</x-link-button>
                    </div>
                </div>
            @endforeach
        </div>

        <x-modal id="file-viewer" title="Pratinjau Berkas">
            <script type="application/json" id="file-viewer-files">@json($files)</script>
            <div class="stack">
                <div class="cluster" data-file-tabs>
                    @foreach ($files as $file)
                        <x-button size="sm" data-file-index="{{ $loop->index }}">{{ $file['name'] }}</x-button>
                    @endforeach
                </div>
                <div class="file-viewer" data-file-stage aria-live="polite"></div>
            </div>
            <x-slot:footer>
                <a class="btn" data-file-open target="_blank" rel="noopener">Buka di Tab Baru</a>
                <a class="btn btn--primary" data-file-download download>Unduh</a>
                <x-button data-modal-close>Tutup</x-button>
            </x-slot:footer>
        </x-modal>
    @endif
</x-card>
