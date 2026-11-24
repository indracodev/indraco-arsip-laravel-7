{{-- Kelola User Pengguna & Hak Akses (Laravel 7 master/users): searchable / sortable grid, add-edit modal,
     reset password modal, "Login As" (impersonate, not for admin accounts) and delete. Admin only.
     Alpine: public/js/pages/user-manager.js. Password hashes never reach the page. --}}
@extends('layouts.app')

@section('title', 'Kelola User & Hak Akses')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/user-manager.js') }}" defer></script>
@endpush

@section('content')
    @php($config = ['users' => $users, 'currentUserId' => $currentUserId, 'old' => session()->getOldInput(), 'base' => url('/master/users')])
    <script type="application/json" id="user-manager-config">@json($config)</script>

    <div x-data="userManager">
        <x-page-header title="Kelola User Pengguna & Hak Akses" subtitle="Pengaturan Peran Super Admin, PIC Gudang Arsip, dan PIC Departemen">
            <x-slot:actions>
                <label class="sr-only" for="user-search">Cari user/role</label>
                <x-input name="user_search" id="user-search" type="search" x-model="search" placeholder="Cari user/role... (Ctrl+F)" autocomplete="off" />
                <x-link-button :href="route('master.users')" title="Segarkan Data (F5)"><x-icon name="refresh" />Refresh (F5)</x-link-button>
                <x-button variant="primary" @click="openUser(null)" data-shortcut="F2"><x-icon name="plus" />Tambah User (F2)</x-button>
            </x-slot:actions>
        </x-page-header>

        @include('master.partials.errors')

        <x-table>
            <thead>
                <tr>
                    @foreach (['name' => 'NAMA PENGGUNA', 'email' => 'ALAMAT EMAIL', 'role' => 'PERAN (ROLE)', 'department' => 'DEPARTEMEN LINKED'] as $column => $label)
                        <th><button type="button" class="btn btn--ghost btn--sm" @click="sortBy('{{ $column }}')">{{ $label }} <span x-text="sortMark('{{ $column }}')"></span></button></th>
                    @endforeach
                    <th>TELEPON / WA</th>
                    <th class="table__actions">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="u in rows()" :key="u.id">
                    <tr>
                        <td data-label="NAMA" class="table__title" x-text="u.name"></td>
                        <td data-label="EMAIL" x-text="u.email"></td>
                        <td data-label="ROLE"><span class="badge" :class="roleTone(u.role)" x-text="roleLabel(u.role)"></span></td>
                        <td data-label="DEPARTEMEN" x-text="u.department ? u.department.code + ' - ' + u.department.name : 'Global (Seluruh)'"></td>
                        <td data-label="TELEPON / WA" x-text="u.phone || '-'"></td>
                        <td data-label="" class="table__actions">
                            <x-button size="sm" @click="openUser(u)" title="Edit Pengguna"><x-icon name="pencil" />Edit</x-button>
                            <x-button size="sm" @click="openReset(u)" title="Reset Password Pengguna Ini"><x-icon name="key" />Reset Pass</x-button>
                            <template x-if="u.id !== currentUserId">
                                <span>
                                    <form method="POST" :action="base + '/' + u.id + '/impersonate'" x-show="u.role !== 'admin'">
                                        @csrf
                                        <x-button type="submit" size="sm" ::data-confirm="'Login sebagai user ' + u.name + ' (' + u.email + ')?'" data-confirm-title="Konfirmasi Impersonasi Akun" data-confirm-type="warning" data-confirm-btn="Ya, Login As" title="Login Sebagai User Ini (Impersonate)">Login As</x-button>
                                    </form>
                                    <form method="POST" :action="base + '/' + u.id">
                                        @csrf @method('DELETE')
                                        <x-button type="submit" size="sm" variant="danger" ::data-confirm="'Hapus user pengguna ' + u.name + ' (' + u.email + ')?'" data-confirm-title="Hapus User Pengguna" data-confirm-btn="Ya, Hapus" title="Hapus User">Hapus</x-button>
                                    </form>
                                </span>
                            </template>
                            <span class="badge badge--outline" x-show="u.id === currentUserId">Akun Anda</span>
                        </td>
                    </tr>
                </template>
                <tr x-show="!rows().length"><td colspan="6" data-label="" class="text-muted">Tidak ada data pengguna yang cocok dengan pencarian.</td></tr>
            </tbody>
        </x-table>
        <p class="text-sm text-muted">Menampilkan <strong x-text="rows().length"></strong> dari <strong x-text="users.length"></strong> pengguna</p>

        @include('users.partials.forms', ['departments' => $departments])
    </div>
@endsection
