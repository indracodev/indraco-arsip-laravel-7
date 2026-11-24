{{-- Log History & Global Audit Trail (Laravel 7 logs/index, admin only, view only): KPI cards, 4 tabs,
     filters (?tab=), tables of 20/15 rows. The Laravel 7 "Detail Log" modal is a native <details> per row. --}}
@extends('layouts.app')

@section('title', 'Log History & Audit Trail')

@section('content')
    @php
        $tabs = ['activity' => 'Semua Aktivitas User & Sistem', 'entry' => 'Log Masuk Gudang (Fisik)', 'borrowing' => 'Log Penarikan Dokumen', 'destruction' => 'Log Pemusnahan & BAP'];
        $deptOptions = $departments->mapWithKeys(fn ($d) => [$d->id => $d->code.' - '.$d->name])->all();
    @endphp

    <x-page-header title="Log History & Global Audit Trail" subtitle="Pencatatan riwayat komprehensif & mutlak (immutable) seluruh aktivitas pengguna dan transaksi sistem DMS PT Indraco.">
        <x-slot:actions><span class="badge badge--success">Audit Logger Aktif</span><span class="badge">Akses: {{ auth()->user()->roleLabel() }} (View Only)</span></x-slot:actions>
    </x-page-header>

    <div class="stat-grid">
        @foreach ([['Total Log Aktivitas', $stats['total']], ['Aktivitas Hari Ini', $stats['today']], ['Pengguna Terdaftar', $stats['users']], ['Modul Terpantau', $stats['modules']]] as [$label, $value])
            <div class="stat"><strong class="stat__value">{{ number_format($value) }}</strong><span class="stat__label">{{ $label }}</span></div>
        @endforeach
    </div>

    <nav class="cluster" aria-label="Tab Log History">
        @foreach ($tabs as $key => $label)
            <x-link-button :href="route('logs.index', ['tab' => $key])" :variant="$tab === $key ? 'primary' : 'secondary'" :aria-current="$tab === $key ? 'page' : null">{{ $label }}</x-link-button>
        @endforeach
    </nav>

    <x-filter-bar :action="route('logs.index')" :active="request()->hasAny(['search', 'module', 'user_id', 'department_id', 'date_from', 'date_to'])">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <x-field label="Pencarian Teks / Ref / IP" for="search">
            <x-input name="search" type="search" :value="request('search')" placeholder="Cari aksi, deskripsi, nama user, no. referensi, IP address..." />
        </x-field>
        @if ($tab === 'activity')
            <x-field label="Filter Modul" for="module">
                <x-select name="module" :options="\App\Support\ActivityLogLabel::MODULE_FILTER" :selected="request('module', 'all')" />
            </x-field>
            <x-field label="Filter Pengguna" for="user_id">
                <x-select name="user_id" placeholder="-- Semua Pengguna --" :selected="request('user_id')" :options="$users->mapWithKeys(fn ($u) => [$u->id => $u->name.' ('.$u->roleLabel().')'])->all()" />
            </x-field>
        @endif
        <x-field label="Departemen" for="department_id">
            <x-select name="department_id" placeholder="-- Semua Departemen --" :selected="request('department_id')" :options="$deptOptions" />
        </x-field>
        <x-field label="Dari Tanggal" for="date_from"><x-input name="date_from" type="date" :value="request('date_from')" /></x-field>
        <x-field label="Sampai Tanggal" for="date_to"><x-input name="date_to" type="date" :value="request('date_to')" /></x-field>
    </x-filter-bar>

    <div class="notice notice--warning" role="note">
        <span><strong>Keamanan & Integritas Audit Trail:</strong> Seluruh catatan log aktivitas di halaman ini bersifat append-only & immutable (hanya dapat dilihat / view-only).</span>
    </div>

    @if ($logs->isEmpty())
        <x-card><x-empty-state :title="$tab === 'activity' ? 'Belum ada catatan log aktivitas' : 'Belum ada data log.'" /></x-card>
    @else
        @include('logs.partials.'.$tab)
    @endif

    <x-pagination :paginator="$logs" noun="log" />
@endsection
