{{-- Pusat Laporan & Dokumen PDF (Laravel 7 reports/index, admin only): 7 numbered report tabs, filters,
     summary cards, report table, CSV export + print (new tab; "Cetak PDF" opens the print dialog). --}}
@extends('layouts.app')

@section('title', 'Pusat Laporan & Dokumen PDF')

@section('content')
    @php
        $query = array_filter(request()->only(['department_id', 'warehouse_id', 'q']), fn ($v) => $v !== null && $v !== '');
        $tones = ['Aktif' => 'success', 'Kosong (0%)' => 'success', 'Optimal' => 'success', 'Penuh (100%)' => 'danger', 'Kritis (>=95%)' => 'danger', 'Terkunci (Lock)' => 'danger',
            'Hampir Penuh (>80%)' => 'warning', 'Padat (80-94%)' => 'warning', 'Perlu Pemantauan' => 'warning'];
    @endphp

    <x-page-header title="Pusat Laporan & Dokumen PDF" subtitle="Generator Laporan Master, Utilisasi Depo Gudang & Analisis Rak 2D">
        <x-slot:actions>
            <x-link-button :href="request()->fullUrl()" title="Segarkan Data Laporan"><x-icon name="refresh" />Refresh</x-link-button>
            <x-link-button :href="route('reports.export_csv', ['type' => $type] + $query)" title="Download Format CSV Spreadsheet">Export CSV</x-link-button>
            <x-link-button :href="route('reports.print', ['type' => $type, 'auto_print' => 1] + $query)" variant="primary" target="_blank" title="Cetak atau Simpan PDF"><x-icon name="printer" />Cetak PDF</x-link-button>
            <x-link-button :href="route('reports.print', ['type' => $type] + $query)" target="_blank" title="Buka Pratinjau di Tab Baru">Preview Tab</x-link-button>
        </x-slot:actions>
    </x-page-header>

    <nav class="cluster" aria-label="Jenis Laporan">
        @foreach (\App\Services\ReportBuilder::TYPES as $key => [$tabName])
            <x-link-button :href="route('reports.index', ['type' => $key])" size="sm" :variant="$type === $key ? 'primary' : 'secondary'" :aria-current="$type === $key ? 'page' : null">
                <span class="badge">{{ $loop->iteration }}</span>{{ $tabName }}
            </x-link-button>
        @endforeach
    </nav>

    <x-filter-bar :action="route('reports.index')" :active="$query !== []">
        <input type="hidden" name="type" value="{{ $type }}">
        <x-field label="Cari baris data laporan" for="q"><x-input name="q" type="search" :value="request('q')" placeholder="Cari baris data laporan..." /></x-field>
        @if (in_array($type, \App\Services\ReportBuilder::DEPARTMENT_FILTER, true))
            <x-field label="Departemen" for="department_id">
                <x-select name="department_id" placeholder="-- Semua Departemen --" :selected="request('department_id')" :options="$departments->mapWithKeys(fn ($d) => [$d->id => $d->code.' - '.$d->name])->all()" />
            </x-field>
        @endif
        @if (in_array($type, \App\Services\ReportBuilder::WAREHOUSE_FILTER, true))
            <x-field label="Gudang" for="warehouse_id">
                <x-select name="warehouse_id" placeholder="-- Semua Gudang --" :selected="request('warehouse_id')" :options="$warehouses->mapWithKeys(fn ($w) => [$w->id => $w->name.' ('.$w->code.')'])->all()" />
            </x-field>
        @endif
    </x-filter-bar>

    <div class="stat-grid">
        @foreach ($summary as [$label, $value])
            <div class="stat"><span class="stat__label">{{ $label }}</span><strong class="stat__value">{{ $value }}</strong></div>
        @endforeach
    </div>

    <x-card :title="$title">
        <p class="text-sm text-muted">{{ $subtitle }}</p>
        @if ($rows === [])
            <x-empty-state title="Tidak ada data yang cocok dengan kriteria filter laporan." />
        @else
            <x-table>
                <thead><tr>@foreach ($columns as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($row as $i => $cell)
                                <td data-label="{{ $columns[$i] ?? '' }}">
                                    @isset($tones[$cell])<span class="badge badge--{{ $tones[$cell] }}">{{ $cell }}</span>@else{{ $cell }}@endisset
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
        <x-slot:footer>
            <span class="text-sm">Menampilkan <strong>{{ count($rows) }}</strong> total entri</span>
            <span class="text-sm text-muted">Format Standar ISO/DMS PT INDRACO</span>
        </x-slot:footer>
    </x-card>
@endsection
