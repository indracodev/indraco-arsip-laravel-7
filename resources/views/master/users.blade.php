@extends('layouts.app')

@section('title', 'Kelola User & Hak Akses - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="{ 
    openAdd: false,
    editUserItem: null,
    searchQuery: '',
    sortColumn: 'name',
    sortDirection: 'asc',
    submitting: false,
    isLoading: false,
    currentUserId: {{ auth()->id() }},
    users: {{ json_encode($users) }},
    departments: {{ json_encode($departments) }},

    get filteredUsers() {
        let res = [...this.users];
        if (this.searchQuery.trim() !== '') {
            const q = this.searchQuery.toLowerCase();
            res = res.filter(u => {
                const deptName = u.department ? u.department.name : '';
                const deptCode = u.department ? u.department.code : '';
                return (u.name && u.name.toLowerCase().includes(q)) ||
                       (u.email && u.email.toLowerCase().includes(q)) ||
                       (u.role && u.role.toLowerCase().includes(q)) ||
                       (u.phone && u.phone.toLowerCase().includes(q)) ||
                       (deptName && deptName.toLowerCase().includes(q)) ||
                       (deptCode && deptCode.toLowerCase().includes(q));
            });
        }
        res.sort((a, b) => {
            let valA = a[this.sortColumn] ?? '';
            let valB = b[this.sortColumn] ?? '';
            if (this.sortColumn === 'department') {
                valA = a.department ? a.department.name : 'Global';
                valB = b.department ? b.department.name : 'Global';
            }
            if (typeof valA === 'string') valA = valA.toLowerCase();
            if (typeof valB === 'string') valB = valB.toLowerCase();
            if (valA < valB) return this.sortDirection === 'asc' ? -1 : 1;
            if (valA > valB) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
        return res;
    },

    sortBy(col) {
        this.isLoading = true;
        if (this.sortColumn === col) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = col;
            this.sortDirection = 'asc';
        }
        setTimeout(() => { this.isLoading = false; lucide.createIcons(); }, 80);
    }
}">

    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2">
            <span class="p-1.5 bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-500/30 rounded">
                <i data-lucide="users" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Kelola User Pengguna & Hak Akses</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengaturan Peran Super Admin, PIC Gudang Arsip, dan PIC Departemen (TDBGrid Engine)</p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari nama/email/role... (Ctrl+F)" 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                >
                <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>

            <!-- Refresh Button (F5) -->
            <button @click="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Segarkan Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Refresh (F5)</span>
            </button>

            <!-- Add Button (F2) -->
            <button @click="openAdd = true" type="button" class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white font-mono font-bold text-xs rounded border border-blue-700 shadow transition flex items-center gap-1.5 shrink-0">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span>Tambah User (F2)</span>
            </button>
        </div>
    </div>

    <!-- DELPHI DBGRID SPREADSHEET TABLE CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        
        <!-- Loading Overlay -->
        <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/70 dark:bg-slate-950/70 backdrop-blur-xs z-10 flex items-center justify-center font-mono">
            <div class="flex items-center gap-2 text-xs font-bold text-blue-600 dark:text-blue-400">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Loading Data...
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-sans text-xs">
                <thead>
                    <tr class="font-mono text-[11px] select-none">
                        <th @click="sortBy('name')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            <div class="flex items-center gap-1">
                                NAMA PENGGUNA
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'name'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-blue-600" x-show="sortColumn === 'name' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-blue-600" x-show="sortColumn === 'name' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th @click="sortBy('email')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            <div class="flex items-center gap-1">
                                ALAMAT EMAIL
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'email'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-blue-600" x-show="sortColumn === 'email' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-blue-600" x-show="sortColumn === 'email' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th @click="sortBy('role')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            <div class="flex items-center gap-1">
                                PERAN (ROLE)
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'role'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-blue-600" x-show="sortColumn === 'role' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-blue-600" x-show="sortColumn === 'role' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th @click="sortBy('department')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            <div class="flex items-center gap-1">
                                DEPARTEMEN LINKED
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'department'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-blue-600" x-show="sortColumn === 'department' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-blue-600" x-show="sortColumn === 'department' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-2 px-3 font-mono">TELEPON / WA</th>
                        <th class="py-2 px-3 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <template x-for="usr in filteredUsers" :key="usr.id">
                        <tr class="hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition">
                            <td class="py-2 px-3 font-bold text-slate-900 dark:text-white" x-text="usr.name"></td>
                            <td class="py-2 px-3 text-xs font-mono text-amber-600 dark:text-amber-400 font-bold" x-text="usr.email"></td>
                            <td class="py-2 px-3 font-mono text-xs">
                                <span x-show="usr.role === 'admin'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30">Super Admin</span>
                                <span x-show="usr.role === 'pic_gudang'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30">PIC Gudang Arsip</span>
                                <span x-show="usr.role === 'pic_dept'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30">PIC Departemen</span>
                            </td>
                            <td class="py-2 px-3 font-mono text-xs text-slate-700 dark:text-slate-300 font-bold" x-text="usr.department ? (usr.department.code + ' - ' + usr.department.name) : 'Global (Seluruh)'"></td>
                            <td class="py-2 px-3 text-xs font-mono text-slate-500" x-text="usr.phone || '-'"></td>
                            <td class="py-2 px-3 text-right flex items-center justify-end gap-1 font-mono">
                                <button @click="editUserItem = Object.assign({}, usr)" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-bold transition flex items-center gap-1" title="Edit Pengguna">
                                    <i data-lucide="edit-3" class="w-3 h-3 text-amber-500"></i> Edit
                                </button>

                                <template x-if="currentUserId !== usr.id">
                                    <div class="inline-flex items-center gap-1">
                                        <form :action="'{{ url('/master/users') }}/' + usr.id + '/impersonate'" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Login sebagai user ' + usr.name + ' (' + usr.email + ')?')" class="px-2 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded text-[11px] font-bold transition inline-flex items-center gap-1" title="Login Sebagai User Ini (Impersonate)">
                                                <i data-lucide="user-check" class="w-3 h-3"></i>
                                                <span>Login As</span>
                                            </button>
                                        </form>

                                        <form :action="'{{ url('/master/users') }}/' + usr.id" method="POST" class="inline" @submit="submitting = true">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Hapus user pengguna ini?')" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[11px] font-bold transition flex items-center gap-1" title="Hapus User">
                                                <i data-lucide="trash-2" class="w-3 h-3 text-rose-500"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </template>
                                <template x-if="currentUserId === usr.id">
                                    <span class="text-[10px] text-slate-400 italic">Akun Anda</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredUsers.length === 0">
                        <td colspan="6" class="py-6 text-center text-slate-500 font-mono text-xs">Tidak ada data pengguna yang cocok dengan pencarian.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Count Bar -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-3 py-1 font-mono text-[11px] flex items-center justify-between text-slate-600 dark:text-slate-400">
            <span>Menampilkan <strong class="text-blue-600 dark:text-blue-400" x-text="filteredUsers.length"></strong> dari <strong x-text="users.length"></strong> pengguna</span>
            <span>TDBGrid View Mode</span>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 1: ADD USER -->
    <div x-show="openAdd" x-cloak 
         x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
         @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
            <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="user-plus" class="w-3.5 h-3.5 text-blue-400"></i> frmUserAdd : Tambah User Pengguna Baru</span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAdd = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.users.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-2.5">
                    <legend class="px-2 font-mono text-xs font-bold text-blue-700 dark:text-blue-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Form Data Pengguna</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA LENGKAP</label>
                        <input type="text" name="name" required class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 font-semibold">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ALAMAT EMAIL</label>
                        <input type="email" name="email" required class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs text-amber-600 dark:text-amber-400 focus:outline-none focus:border-blue-500 font-bold">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">PASSWORD DEFAULT</label>
                        <input type="password" name="password" required minlength="6" value="password" class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">PERAN / ROLE SYSTEM</label>
                        <select name="role" required class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 font-bold">
                            <option value="pic_dept">PIC Departemen Client</option>
                            <option value="pic_gudang">PIC Gudang Arsip (Curator)</option>
                            <option value="admin">Super Admin / Management</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DEPARTEMEN LINKED</label>
                        <select name="department_id" class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 font-semibold">
                            <option value="">-- Tanpa Departemen (Global) --</option>
                            <template x-for="dept in departments" :key="dept.id">
                                <option :value="dept.id" x-text="dept.code + ' - ' + dept.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NO. TELEPON / WA</label>
                        <input type="text" name="phone" placeholder="081234567890" class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAdd = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Memproses...' : 'Simpan User (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 2: EDIT USER -->
    <template x-if="editUserItem">
        <div x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
             @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
                <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmUserEdit : Edit Data Pengguna</span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editUserItem = null; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/users') }}/' + editUserItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-2.5">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Form Perubahan User</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA LENGKAP</label>
                            <input type="text" name="name" :value="editUserItem.name" required class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ALAMAT EMAIL</label>
                            <input type="email" name="email" :value="editUserItem.email" required class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500 font-bold">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">PASSWORD BARU (KOSONGKAN JIKA TIDAK UBAH)</label>
                            <input type="password" name="password" minlength="6" placeholder="******" class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">PERAN / ROLE SYSTEM</label>
                            <select name="role" x-model="editUserItem.role" required class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-bold">
                                <option value="pic_dept">PIC Departemen Client</option>
                                <option value="pic_gudang">PIC Gudang Arsip (Curator)</option>
                                <option value="admin">Super Admin / Management</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DEPARTEMEN LINKED</label>
                            <select name="department_id" x-model="editUserItem.department_id" class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold">
                                <option value="">-- Tanpa Departemen (Global) --</option>
                                <template x-for="dept in departments" :key="dept.id">
                                    <option :value="dept.id" x-text="dept.code + ' - ' + dept.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NO. TELEPON / WA</label>
                            <input type="text" name="phone" :value="editUserItem.phone" placeholder="081234567890" class="w-full p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editUserItem = null; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update User (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

