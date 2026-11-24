{{-- Master Gudang & Slot Rak Storage (Laravel 7 master/warehouses + crud_modals): one card per gudang with its racks.
     Admin: add / edit / delete / active switch; PIC Gudang: view + add gudang / rak (Laravel 7 buttons).
     The 100-slot rack visualisation of Laravel 7 opens the Layout Gudang 2D page (Round F). --}}
@extends('layouts.app')

@section('title', 'Master Gudang & Lokasi Rak')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/master-departments.js') }}" defer></script>
    <script src="{{ \App\Support\Asset::url('js/pages/master-warehouses.js') }}" defer></script>
@endpush

@section('content')
    <script type="application/json" id="master-warehouses-config">@json(['warehouses' => $warehouses, 'old' => session()->getOldInput(), 'base' => url('/master/warehouses')])</script>

    <div x-data="masterWarehouses">
        <x-page-header title="Master Gudang & Slot Rak Storage" subtitle="Pengelolaan Gedung Depo Gudang & Alokasi Kapasitas Slot Rak">
            <x-slot:actions>
                <label class="sr-only" for="warehouse-search">Cari gudang/rak</label>
                <x-input name="warehouse_search" id="warehouse-search" type="search" x-model="search" placeholder="Cari gudang/rak... (Ctrl+F)" autocomplete="off" />
                <x-link-button :href="route('master.warehouses')" title="Segarkan Data (F5)"><x-icon name="refresh" />Refresh (F5)</x-link-button>
                <x-button variant="primary" @click="openWarehouse(null)" data-shortcut="F2"><x-icon name="plus" />Gudang (F2)</x-button>
                <x-button @click="openLocation(null)" data-shortcut="F3"><x-icon name="plus" />Slot Rak (F3)</x-button>
            </x-slot:actions>
        </x-page-header>

        @include('master.partials.errors')

        <template x-for="wh in rows()" :key="wh.id">
            <x-card>
                <div class="stack">
                    <div class="cluster">
                        <span class="badge badge--outline mono" x-text="wh.code"></span>
                        <span class="badge badge--warning" x-show="wh.admin_only">Khusus Super Admin</span>
                        <strong class="table__title" x-text="wh.name"></strong>
                        @if ($isAdmin)
                            <x-button size="sm" @click="toggleActive(wh)" ::disabled="toggling === wh.id" ::class="wh.is_active === false && 'btn--danger'" ::title="wh.is_active !== false ? 'Gudang Aktif (Klik untuk Non-aktifkan)' : 'Gudang Non-Aktif (Klik untuk Aktifkan)'"><span x-text="wh.is_active !== false ? 'Aktif' : 'Non-Aktif'"></span></x-button>
                            <x-button size="sm" @click="openWarehouse(wh)" title="Edit Gudang"><x-icon name="pencil" />Edit</x-button>
                            <form method="POST" :action="base + '/' + wh.id">
                                @csrf @method('DELETE')
                                <x-button type="submit" size="sm" variant="danger" data-confirm="Hapus gudang ini beserta seluruh datanya?" data-confirm-title="Hapus Gudang" data-confirm-btn="Ya, Hapus" title="Hapus Gudang">Hapus</x-button>
                            </form>
                        @else
                            <span class="badge" :class="wh.is_active !== false ? 'badge--success' : 'badge--danger'" x-text="wh.is_active !== false ? 'Aktif' : 'Non-Aktif'"></span>
                        @endif
                    </div>
                    <p class="text-sm text-muted" x-text="wh.address || 'Alamat lokasi belum diisi'"></p>
                    <div class="cluster text-sm"><strong>Daftar Slot Rak &amp; Baris:</strong><span class="badge" x-text="wh.locations.length + ' Slot Terdaftar'"></span></div>
                    <x-table>
                        <thead><tr><th>LOKASI RAK</th><th>KAPASITAS</th><th class="table__actions">AKSI</th></tr></thead>
                        <tbody>
                            <template x-for="loc in wh.locations" :key="loc.id">
                                <tr>
                                    <td data-label="LOKASI RAK"><a href="{{ route('master.warehouses.layout') }}" class="mono" title="Buka Layout Gudang 2D" x-text="loc.full_location"></a></td>
                                    <td data-label="KAPASITAS"><span class="badge" :class="capacityTone(loc)" x-text="(loc.current_box_count || 0) + ' / ' + loc.box_capacity + ' Box'"></span></td>
                                    <td data-label="" class="table__actions">
                                        @if ($isAdmin)
                                            <x-button size="sm" @click="openLocation(loc)" title="Edit Data Rak"><x-icon name="pencil" />Edit</x-button>
                                        @endif
                                        <x-link-button size="sm" :href="route('master.warehouses.layout')" title="Buka Visualisasi Denah Rak"><x-icon name="eye" />Denah</x-link-button>
                                        @if ($isAdmin)
                                            <form method="POST" :action="base + '/locations/' + loc.id">
                                                @csrf @method('DELETE')
                                                <x-button type="submit" size="sm" variant="danger" data-confirm="Hapus lokasi rak ini?" data-confirm-title="Hapus Lokasi Rak" data-confirm-btn="Ya, Hapus" title="Hapus Rak">Hapus</x-button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!wh.locations.length"><td colspan="3" data-label="" class="text-muted">Belum ada lokasi rak terdaftar.</td></tr>
                        </tbody>
                    </x-table>
                </div>
            </x-card>
        </template>
        <x-card x-show="!rows().length"><x-empty-state title="Tidak ada data gudang atau lokasi rak yang cocok dengan kata kunci pencarian." /></x-card>

        @include('master.warehouses.partials.forms')
    </div>
@endsection
