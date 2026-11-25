{{-- [Form 2] Penarikan Berkas (Laravel 7 borrowings/index + modals): one row per box (latest penarikan),
     detail / dispatch / reject modals per row (server rendered), file viewer of archives/show. --}}
@extends('layouts.app')

@section('title', 'Penarikan Dokumen Arsip')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/archive-show.js') }}" defer></script>
@endpush

@section('content')
    @php
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $isGudang = $user->role === \App\Models\User::ROLE_PIC_GUDANG;
        $statuses = \App\Services\BorrowingList::STATUSES;
        // One viewer for the page: every row's files in one list, each row knows its first index.
        $allFiles = [];
        $fileStart = [];
        $fileSets = [];
        foreach ($borrowings as $log) {
            $fileStart[$log->id] = count($allFiles);
            $fileSets[$log->id] = \App\Services\BorrowingList::files($log);
            $allFiles = array_merge($allFiles, $fileSets[$log->id]);
        }
    @endphp

    <x-page-header title="MANAJEMEN PENARIKAN BERKAS ARSIP" subtitle="Pengajuan penarikan berkas, persetujuan kurator gudang, pengeluaran fisik, dan tracking pengembalian.">
        <x-slot:actions>
            <x-link-button :href="request()->fullUrl()" title="Refresh Data (F5)"><x-icon name="refresh" />Refresh (F5)</x-link-button>
            @can('create', \App\Models\BorrowingLog::class)
                <x-link-button :href="route('borrowings.create', ['from' => 'borrowings'])" variant="primary" title="Ajukan Penarikan Berkas Baru"><x-icon name="plus" />Buat Pengajuan</x-link-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-filter-bar :action="route('borrowings.index')" :active="request()->hasAny(['search', 'status'])">
        <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
        <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
        <x-field label="CARI KEYWORD" for="search">
            <x-input name="search" type="search" :value="request('search')" placeholder="No. Box, Judul, Peminjam, Tujuan..." />
        </x-field>
        <x-field label="STATUS WORKFLOW" for="status">
            <x-select name="status" placeholder="-- Semua Status --" :selected="request('status')"
                :options="array_replace(array_map(fn ($s) => $s[0], $statuses), ['returned' => 'Dikembalikan ke Gudang'])" />
        </x-field>
    </x-filter-bar>

    <div class="cluster text-sm" aria-label="Pembeda Workflow">
        <span class="text-muted">Pembeda Workflow:</span>
        <span class="badge badge--warning">Diajukan User (Data Baru)</span>
        <span class="badge badge--info">Disetujui Gudang (Siap Ambil)</span>
        <span class="badge badge--info">Sudah Ditarik (Fisik Berkas Out)</span>
        <span class="badge badge--success">Dikembalikan (Selesai)</span>
        <span class="badge badge--danger">Ditolak</span>
    </div>

    @if ($borrowings->isEmpty())
        <x-card><x-empty-state title="Belum ada riwayat pengajuan penarikan berkas arsip." /></x-card>
    @else
        <div class="stack" x-data="{ selectedRestore: 0 }" @change="selectedRestore = $el.querySelectorAll('input[data-restore]:checked').length">
        <div class="notice" x-show="selectedRestore > 0" x-cloak>
            <span>Terpilih: <strong x-text="selectedRestore"></strong> box untuk disimpan kembali.</span>
            <span class="topbar__spacer"></span>
            <form method="GET" action="{{ route('archives.create') }}" id="batch-restore" data-no-lock>
                <input type="hidden" name="re_store" value="1">
                <x-button type="submit" variant="primary" size="sm" title="Ajukan penyimpanan kembali box yang sudah ditarik ke gudang">
                    Ajukan Penyimpanan Kembali (<span x-text="selectedRestore"></span> Box)
                </x-button>
            </form>
        </div>
        <x-table>
            <thead>
                <tr>
                    <th><span class="sr-only">Pilih</span></th>
                    <th>NO. BOX & JUDUL BERKAS</th>
                    <th>LOKASI POSISI RAK</th>
                    <th>PEMOHON PENARIKAN</th>
                    @include('archives.partials.sort-header', ['column' => 'borrow_date', 'label' => 'TGL PENARIKAN'])
                    <th>TUJUAN PENARIKAN</th>
                    @include('archives.partials.sort-header', ['column' => 'status', 'label' => 'STATUS'])
                    <th>BERKAS</th>
                    <th class="table__actions">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($borrowings as $log)
                    @include('borrowings.partials.row', ['log' => $log, 'files' => $fileSets[$log->id], 'firstFile' => $fileStart[$log->id]])
                @endforeach
            </tbody>
        </x-table>
        </div>
        @foreach ($borrowings as $log)
            @include('borrowings.partials.row-modals', ['log' => $log, 'files' => $fileSets[$log->id], 'firstFile' => $fileStart[$log->id]])
        @endforeach
    @endif

    <x-pagination :paginator="$borrowings" noun="pengajuan penarikan" />

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
