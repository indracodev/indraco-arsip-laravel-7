{{-- Master Departemen & Sub-Departemen (Laravel 7 master/departments + modals): department grid with
     expandable sub-departments, CRUD modals, drill-down of boxes, "Kelola" (PIC + Master Berkas),
     Import Excel Data Master. Admin only. Alpine: public/js/pages/master-departments*.js, master-import.js. --}}
@extends('layouts.app')

@section('title', 'Master Departemen & Sub-Departemen')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/master-departments.js') }}" defer></script>
    <script src="{{ \App\Support\Asset::url('js/pages/master-department-manage.js') }}" defer></script>
    <script src="{{ \App\Support\Asset::url('js/pages/master-import.js') }}" defer></script>
@endpush

@section('content')
    @php
        $config = [
            'departments' => $departments,
            'old' => session()->getOldInput(),
            'urls' => [
                'departments' => url('/master/departments'),
                'subDepartments' => url('/master/sub-departments'),
                'apiDepartments' => url('/api/departments'),
                'apiSubDepartments' => url('/api/sub-departments'),
                'users' => url('/master/users'),
            ],
        ];
    @endphp
    <script type="application/json" id="master-departments-config">@json($config)</script>

    <div x-data="masterDepartments">
        <x-page-header title="Master Departemen & Sub-Departemen" subtitle="Hierarki Unit Kerja & Manajemen Berkas Arsip">
            <x-slot:actions>
                <label class="sr-only" for="dept-search">Cari Dept / Sub-Dept</label>
                <x-input name="dept_table_search_filter" id="dept-search" type="search" x-model="search" placeholder="Cari Dept / Sub-Dept..." autocomplete="off" />
                <x-button @click="expandAll()" title="Buka Semua Sub-Departemen">Expand All</x-button>
                <x-button @click="collapseAll()" title="Tutup Semua Sub-Departemen">Collapse</x-button>
                <x-link-button :href="route('master.departments')" title="Segarkan Data (F5)"><x-icon name="refresh" />Refresh</x-link-button>
                <x-button @click="openSub(null)" title="Tambah Sub-Departemen Baru"><x-icon name="plus" />Sub-Dept</x-button>
                <x-button data-modal-open="import-master" title="Import Departemen, Sub-Departemen & Type Berkas dari Excel"><x-icon name="upload" />Import Excel</x-button>
                <x-button variant="primary" @click="openDept(null)" data-shortcut="F2" title="Tambah Departemen (F2)"><x-icon name="plus" />Dept (F2)</x-button>
            </x-slot:actions>
        </x-page-header>

        @include('master.partials.errors')

        @include('master.departments.partials.table')

        @include('master.departments.partials.forms')
        @include('master.departments.partials.drilldown')
    </div>

    @include('master.departments.partials.manage')
    @include('master.departments.partials.import')
@endsection
