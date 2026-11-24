{{-- Dashboard Overview (Laravel 7 dashboard/index) for admin and PIC Gudang: welcome header, quick
     search (submits to the catalog), shortcut filters, 5 stat tiles, capacity + retention ribbon,
     "BERKAS ARSIP TERBARU" with 5/10/15/20 rows. The Laravel 7 row modals (verify, quick slot,
     checkout, free status) belong to the GUDANG module; rows link to the detail page. --}}
@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
    @php
        $isAdmin = $user->isAdmin();
        $tiles = [
            ['TOTAL ARSIP', $stats['total'], 'Box', null],
            ['TERSIMPAN GUDANG', $stats['in_warehouse'], 'Slot', 'success'],
            ['ANTEAN BOOKING', $stats['pending'], 'Pengajuan', 'warning'],
            ['DOKUMEN KELUAR', $stats['out'], 'Out', 'info'],
            ['ALERT EXPIRED', $stats['expiring'], 'Berkas', 'danger'],
        ];
    @endphp

    <x-page-header :title="'Selamat Datang, '.$user->name"
        :subtitle="$isAdmin ? 'Workstation Super Admin (Akses Penuh Master Data & Seluruh Dokumen Perusahaan)' : 'Workstation Kurator Gudang (Verifikasi Arsip, Slot Rak, & Pengeluaran Berkas)'">
        <x-slot:actions>
            <x-link-button :href="request()->fullUrl()" title="Segarkan Data (F5)"><x-icon name="refresh" />Refresh (F5)</x-link-button>
            @if ($isAdmin)
                <x-link-button :href="route('archives.create')" variant="primary"><x-icon name="plus" />Buat Draft Arsip</x-link-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card title="PENCARIAN CEPAT DOKUMEN & KATALOG ARSIP">
        <form method="GET" action="{{ route('archives.index') }}" class="cluster" role="search">
            <label class="sr-only" for="dashboard-search">Cari dokumen</label>
            <x-input name="search" id="dashboard-search" type="search" class="dash-search"
                :placeholder="$isAdmin ? 'Ketik kata kunci dokumen (contoh: BOX-FIN-2024, Pajak, HRD)...' : 'Cari rak gudang, sektor & label/judul box arsip...'" />
            <x-button type="submit" variant="primary">Cari</x-button>
        </form>
        <div class="cluster text-sm dash-shortcuts">
            <span class="text-muted">Shortcut Filter:</span>
            <a class="badge badge--success" href="{{ route('archives.index', ['status' => 'in_warehouse']) }}">Di Gudang</a>
            <a class="badge badge--warning" href="{{ route('archives.index', ['status' => 'pending_verification']) }}">Antrean Verifikasi</a>
            <a class="badge badge--info" href="{{ route('archives.index', ['status' => 'out']) }}">Out / Keluar</a>
            <a class="badge badge--danger" href="{{ route('archives.index', ['expiry_filter' => 'expiring_soon']) }}">Expiring Soon</a>
            <span class="topbar__spacer"></span>
            @if ($isAdmin)
                <x-link-button :href="route('logs.index')" size="sm">Log History</x-link-button>
            @endif
            <x-link-button :href="route('master.warehouses.layout')" size="sm">Input &amp; Layout Gudang (2D)</x-link-button>
        </div>
    </x-card>

    <div class="stat-grid">
        @foreach ($tiles as [$label, $value, $unit, $tone])
            <div @class(['stat', 'stat--'.$tone => $tone])>
                <span class="stat__label">{{ $label }}</span>
                <span><strong class="stat__value">{{ number_format($value) }}</strong> <span class="text-muted">{{ $unit }}</span></span>
            </div>
        @endforeach
    </div>

    <div class="stat-ribbon">
        <x-card>
            <div class="cluster">
                <span class="text-muted">Kapasitas Rak Gudang:</span>
                <strong>{{ number_format($capacity['used']) }} / {{ number_format($capacity['total']) }} Box</strong>
                <span class="badge">{{ $capacity['percent'] }}%</span>
                <x-link-button :href="route('master.warehouses')" size="sm" class="push-end">Kelola Lokasi</x-link-button>
            </div>
            <div class="meter" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min($capacity['percent'], 100) }}" aria-label="Kapasitas rak terpakai">
                <span class="meter__bar" style="width: {{ min($capacity['percent'], 100) }}%"></span>
            </div>
        </x-card>
        <x-card>
            <div class="cluster">
                <span class="text-muted">Status Masa Retensi (90 Hari):</span>
                @if ($stats['expiring'] === 0)
                    <span class="tone--success">Semua berkas aman (Belum ada yang expired)</span>
                @else
                    <span class="tone--warning"><strong>{{ $stats['expiring'] }} berkas</strong> mendekati masa pemusnahan</span>
                @endif
                <x-link-button :href="route('destructions.index')" size="sm" class="push-end">Pemusnahan BAP</x-link-button>
            </div>
        </x-card>
    </div>

    <x-card title="BERKAS ARSIP TERBARU">
        <x-slot:actions><x-link-button :href="route('archives.index')" size="sm">Buka Katalog Utama</x-link-button></x-slot:actions>
        @if ($recentArchives->isEmpty())
            <x-empty-state title="Belum ada data arsip tercatat." />
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>NO. BOX ARSIP</th><th>JUDUL BERKAS</th><th>DEPARTEMEN</th><th>PERIODE</th>
                        <th>LOKASI FISIK</th><th>STATUS WORKFLOW</th><th>BERKAS</th><th class="table__actions">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentArchives as $archive)
                        @php($files = count(array_filter([$archive->scan_input_form, $archive->file_path, $archive->scan_approval_input, $archive->scan_extension_form])))
                        <tr>
                            <td data-label="NO. BOX ARSIP" class="mono">{{ $archive->box_number ?: '- Penomoran Pending -' }}</td>
                            <td data-label="JUDUL BERKAS" class="table__wide"><a href="{{ route('archives.show', $archive) }}">{{ $archive->title }}</a></td>
                            <td data-label="DEPARTEMEN"><span class="badge badge--outline">{{ $archive->department->code ?? 'GEN' }}</span></td>
                            <td data-label="PERIODE">{{ $archive->effectivePeriode() }}</td>
                            <td data-label="LOKASI FISIK">
                                @if ($archive->location)
                                    {{ $archive->displayLocation() }}
                                @else
                                    <span class="tone--warning">Belum Ditentukan</span>
                                @endif
                            </td>
                            <td data-label="STATUS WORKFLOW"><x-badge-status :status="$archive->status" :rejected="filled($archive->rejection_note)" short /></td>
                            <td data-label="BERKAS">{{ $files > 0 ? "Ada ({$files})" : 'Tidak Ada' }}</td>
                            <td data-label="" class="table__actions">
                                @if ($isAdmin)
                                    @can('update', $archive)
                                        <x-link-button :href="route('archives.edit', $archive)" size="sm" title="Edit Data Berkas Arsip"><x-icon name="pencil" />Edit</x-link-button>
                                    @endcan
                                @endif
                                <x-link-button :href="route('archives.show', $archive)" size="sm"><x-icon name="eye" />Detail</x-link-button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
        <x-slot:footer>
            <div class="cluster text-sm">
                <span class="text-muted">Tampilkan:</span>
                @foreach ($perPageOptions as $option)
                    <a href="{{ request()->fullUrlWithQuery(['per_page' => $option, 'page' => 1]) }}" @class(['pagination__link', 'is-current' => $option === $perPage])>{{ $option }}</a>
                @endforeach
                <span class="text-muted">baris</span>
            </div>
            <x-pagination :paginator="$recentArchives" noun="berkas" />
        </x-slot:footer>
    </x-card>
@endsection
