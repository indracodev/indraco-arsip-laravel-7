@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Katalog Tipe Dokumen - DMS PT Indraco')

@section('content')
<div class="space-y-3 font-mono" x-data="{
    openAdd: false, 
    editItem: null,
    searchQuery: '',
    sortColumn: 'code',
    sortDirection: 'asc',
    submitting: false,
    isLoading: false,
    items: {{ json_encode($documentTypes) }},

    get filteredItems() {
        let res = [...this.items];
        if (this.searchQuery.trim() !== '') {
            const q = this.searchQuery.toLowerCase();
            res = res.filter(i => 
                (i.code && i.code.toLowerCase().includes(q)) ||
                (i.name && i.name.toLowerCase().includes(q)) ||
                (i.description && i.description.toLowerCase().includes(q)) ||
                (i.department && i.department.name.toLowerCase().includes(q))
            );
        }
        res.sort((a, b) => {
            let valA = a[this.sortColumn] ?? '';
            let valB = b[this.sortColumn] ?? '';
            valA = valA.toString().toLowerCase();
            valB = valB.toString().toLowerCase();
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
        setTimeout(() => { this.isLoading = false; if (window.lucide) lucide.createIcons(); }, 80);
    }
}">

    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="files" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Katalog Tipe Dokumen Arsip Box</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengelolaan Daftar Tipe Dokumen Standar & Custom (PIC Departemen & Super Admin Full Control)</p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari kode/nama dokumen... (Ctrl+F)" 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                >
                <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>

            <!-- Refresh Button -->
            <button @click="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Segarkan Data">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Refresh</span>
            </button>

            <!-- Add Document Type Button -->
            <button @click="openAdd = true" type="button" class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow-sm transition flex items-center gap-1.5 shrink-0">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Tambah Tipe Dokumen</span>
            </button>
        </div>
    </div>

    <!-- DELPHI DBGRID TABLE -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        
        <!-- Loading Overlay -->
        <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/70 dark:bg-slate-950/70 backdrop-blur-xs z-10 flex items-center justify-center font-mono">
            <div class="flex items-center gap-2 text-xs font-bold text-amber-600 dark:text-amber-400">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Loading Data...
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-sans text-xs">
                <thead>
                    <tr class="font-mono text-[11px] select-none">
                        <th @click="sortBy('code')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition w-32">
                            <div class="flex items-center gap-1">
                                KODE
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'code'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-amber-600" x-show="sortColumn === 'code' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-amber-600" x-show="sortColumn === 'code' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th @click="sortBy('name')" class="py-2 px-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-800 transition w-64">
                            <div class="flex items-center gap-1">
                                NAMA TIPE DOKUMEN
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40" x-show="sortColumn !== 'name'"></i>
                                <i data-lucide="arrow-up" class="w-3 h-3 text-amber-600" x-show="sortColumn === 'name' && sortDirection === 'asc'"></i>
                                <i data-lucide="arrow-down" class="w-3 h-3 text-amber-600" x-show="sortColumn === 'name' && sortDirection === 'desc'"></i>
                            </div>
                        </th>
                        <th class="py-2 px-3">DESKRIPSI / KEGUNAAN</th>
                        <th class="py-2 px-3 w-40">LINGKUP AKSES</th>
                        <th class="py-2 px-3 w-28 text-center">TIPE</th>
                        <th class="py-2 px-3 text-right w-40 font-mono">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <template x-for="doc in filteredItems" :key="doc.id">
                        <tr class="hover:bg-amber-500/10 dark:hover:bg-amber-500/20 transition">
                            <td class="py-2 px-3 font-mono text-xs text-amber-600 dark:text-amber-400 font-bold" x-text="doc.code"></td>
                            <td class="py-2 px-3 font-bold text-slate-900 dark:text-white" x-text="doc.name"></td>
                            <td class="py-2 px-3 text-xs text-slate-600 dark:text-slate-300 font-medium" x-text="doc.description || '-'"></td>
                            <td class="py-2 px-3 font-mono text-[11px]">
                                <template x-if="doc.department">
                                    <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-300 dark:border-purple-700 font-bold" x-text="doc.department.code + ' (' + doc.department.name + ')'"></span>
                                </template>
                                <template x-if="!doc.department">
                                    <span class="text-slate-500 italic">Semua Departemen (Global)</span>
                                </template>
                            </td>
                            <td class="py-2 px-3 text-center">
                                <template x-if="doc.is_preset">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                                        Preset
                                    </span>
                                </template>
                                <template x-if="!doc.is_preset">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                        Custom
                                    </span>
                                </template>
                            </td>
                            <td class="py-2 px-3 text-right flex items-center justify-end gap-1 font-mono">
                                <button @click="editItem = Object.assign({}, doc)" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-[11px] font-bold transition flex items-center gap-1" title="Edit Tipe Dokumen">
                                    <i data-lucide="edit-3" class="w-3 h-3 text-amber-500"></i> Edit
                                </button>
                                <template x-if="!doc.is_preset">
                                    <form :action="'{{ url('/master/document-types') }}/' + doc.id" method="POST" class="inline" @submit="submitting = true">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Hapus tipe dokumen ini dari katalog?')" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 rounded text-[11px] font-bold transition flex items-center gap-1" title="Hapus Dokumen">
                                            <i data-lucide="trash-2" class="w-3 h-3 text-rose-500"></i> Hapus
                                        </button>
                                    </form>
                                </template>
                                <template x-if="doc.is_preset">
                                    <span class="px-2 py-1 text-slate-400 text-[10px] italic">Bawaan</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredItems.length === 0">
                        <td colspan="6" class="py-6 text-center text-slate-500 font-mono text-xs">Tidak ada data tipe dokumen yang cocok dengan pencarian.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Count Bar -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-3 py-1 font-mono text-[11px] flex items-center justify-between text-slate-600 dark:text-slate-400">
            <span>Menampilkan <strong class="text-amber-600 dark:text-amber-400" x-text="filteredItems.length"></strong> dari <strong x-text="items.length"></strong> jenis dokumen</span>
            <span>TDBGrid Document Catalog</span>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL: ADD DOCUMENT TYPE -->
    <div x-show="openAdd" 
         x-cloak 
         x-data="{ 
             posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
             startDrag(e) { 
                 if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select')) return; 
                 this.isDragging = true; 
                 this.startX = e.clientX - this.posX; 
                 this.startY = e.clientY - this.posY; 
             }, 
             onDrag(e) { 
                 if (!this.isDragging) return; 
                 this.posX = e.clientX - this.startX; 
                 this.posY = e.clientY - this.startY; 
             }, 
             stopDrag() { this.isDragging = false; }, 
             resetPos() { this.posX = 0; this.posY = 0; } 
         }"
         @mousemove.window="onDrag($event)" 
         @mouseup.window="stopDrag()"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 font-mono">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
             class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono text-xs">
            <!-- Window Title Bar -->
            <div @mousedown="startDrag($event)" 
                 :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                 title="Klik & tahan untuk menggeser form"
                 class="bg-gradient-to-r from-slate-800 via-slate-700 to-amber-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                    <i data-lucide="file-plus" class="w-3.5 h-3.5 text-amber-400"></i> frmDocumentTypeAdd : Tambah Tipe Dokumen Baru
                </span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAdd = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.document_types.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Data Tipe Dokumen</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE / SINGKATAN DOKUMEN <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" required maxlength="50" placeholder="CTH: SERTIFIKAT, BILYET, ASURANSI" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono uppercase font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA LENGKAP JENIS DOKUMEN <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: Sertifikat Legalitas / Hak Milik Tanah" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI / KETERANGAN</label>
                        <input type="text" name="description" placeholder="Contoh: Bukti Kepemilikan Aset & Legalitas..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium">
                    </div>

                    @if(auth()->user()->isSuperAdmin())
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DEPARTEMEN TERKAIT (OPSIONAL)</label>
                        <select name="department_id" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-mono text-xs">
                            <option value="">-- Semua Departemen (Global) --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAdd = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1 shadow-sm">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan ke Katalog (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL: EDIT DOCUMENT TYPE -->
    <template x-if="editItem">
        <div x-data="{ 
                 posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
                 startDrag(e) { 
                     if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select')) return; 
                     this.isDragging = true; 
                     this.startX = e.clientX - this.posX; 
                     this.startY = e.clientY - this.posY; 
                 }, 
                 onDrag(e) { 
                 if (!this.isDragging) return; 
                     this.posX = e.clientX - this.startX; 
                     this.posY = e.clientY - this.startY; 
                 }, 
                 stopDrag() { this.isDragging = false; }, 
                 resetPos() { this.posX = 0; this.posY = 0; } 
             }"
             @mousemove.window="onDrag($event)" 
             @mouseup.window="stopDrag()"
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 font-mono">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" 
                 class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono text-xs">
                <!-- Window Title Bar -->
                <div @mousedown="startDrag($event)" 
                     :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                     title="Klik & tahan untuk menggeser form"
                     class="bg-gradient-to-r from-slate-800 via-slate-700 to-amber-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmDocumentTypeEdit : Edit Tipe Dokumen
                    </span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editItem = null" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/document-types') }}/' + editItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Form Ubah Data Dokumen</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE DOKUMEN</label>
                            <input type="text" name="code" :value="editItem.code" required maxlength="50" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono uppercase font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA TIPE DOKUMEN</label>
                            <input type="text" name="name" :value="editItem.name" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI</label>
                            <textarea name="description" rows="2" x-text="editItem.description" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                        </div>

                        @if(auth()->user()->isSuperAdmin())
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DEPARTEMEN TERKAIT</label>
                            <select name="department_id" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-mono text-xs">
                                <option value="">-- Semua Departemen (Global) --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" :selected="editItem.department_id == {{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editItem = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1 shadow-sm">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Data (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
