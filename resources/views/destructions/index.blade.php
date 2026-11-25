{{-- [Form 3] Pengajuan Perpanjangan & Pemusnahan (Laravel 7 destructions/index + tab_extensions +
     destruction_modals): three tabs (?tab=), only the active one is built, 50 rows per page + ?q= search (P1), per-row modals rendered on the server, file viewer of archives/show. --}}
@extends('layouts.app')

@section('title', 'Pengajuan Perpanjangan & Pemusnahan')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/archive-show.js') }}" defer></script>
    <script src="{{ \App\Support\Asset::url('js/pages/destruction-index.js') }}" defer></script>
@endpush

@section('content')
    @php
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $canPropose = $user->can('create', \App\Models\DestructionLog::class);
        $canProcess = $user->can('process', \App\Models\DestructionLog::class);
        $tabs = [
            'h30' => ['Berkas Jatuh Tempo (H-30)', $counts['h30']],
            'extensions' => ['Riwayat Perpanjangan', $counts['extensions']],
            'destructions' => ['Alur & Riwayat Pemusnahan', $counts['destructions']],
        ];
        // One viewer for the page (#file-viewer): every file in one list, buttons point at an index.
        $allFiles = [];
        $fileIndex = function (?array $file) use (&$allFiles): ?int {
            if ($file === null) {
                return null;
            }
            $allFiles[] = $file;

            return count($allFiles) - 1;
        };
    @endphp

    <x-page-header title="PENGAJUAN PERPANJANGAN & PEMUSNAHAN BERKAS" subtitle="Monitoring berkas jatuh tempo (H-30), log perpanjangan masa simpan, dan riwayat BAP pemusnahan.">
        <x-slot:actions>
            <x-link-button :href="request()->fullUrl()" title="Refresh Data (F5)"><x-icon name="refresh" />Refresh (F5)</x-link-button>
            @if ($canPropose)
                <x-link-button :href="route('destructions.extend_form')" title="Ajukan Perpanjangan Masa Simpan Berkas"><x-icon name="plus" />Perpanjangan</x-link-button>
                <x-link-button :href="route('destructions.propose')" variant="danger" title="Ajukan Pemusnahan Berkas (BAP)"><x-icon name="plus" />Pemusnahan</x-link-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <nav class="cluster" aria-label="Tab Perpanjangan & Pemusnahan">
        @foreach ($tabs as $key => [$label, $count])
            <x-link-button :href="route('destructions.index', ['tab' => $key])" :variant="$tab === $key ? 'primary' : 'secondary'" :aria-current="$tab === $key ? 'page' : null">
                {{ $label }} <span class="badge {{ $key === 'h30' && $count > 0 ? 'badge--danger' : '' }}">{{ $count }}</span>
            </x-link-button>
        @endforeach
    </nav>

    @include('destructions.partials.tab-'.$tab)

    @if ($allFiles !== [])
        <x-modal id="file-viewer" title="Pratinjau Berkas">
            <script type="application/json" id="file-viewer-files">@json($allFiles)</script>
            <div class="file-viewer" data-file-stage aria-live="polite"></div>
            <x-slot:footer>
                <a class="btn" data-file-open target="_blank" rel="noopener">Buka di Tab Baru</a>
                <a class="btn btn--primary" data-file-download download>Unduh</a>
                <x-button data-modal-close>Tutup</x-button>
            </x-slot:footer>
        </x-modal>
    @endif
@endsection
