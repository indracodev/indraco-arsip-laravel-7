@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Pengajuan Box Arsip (TB 30g) - DMS PT Indraco')

@section('content')
<div class="max-w-5xl mx-auto space-y-3 font-sans pb-10" x-data="archiveCreateApp()">

    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="package-plus" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Form Pengajuan Box Arsip (TB 30g) & Label A5</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pencatatan Master Kardus & Multi-Item Butir Dokumen Arsip • Standar Box TB 30g</p>
            </div>
        </div>

        <a href="{{ route('archives.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm shrink-0">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500"></i>
            <span>Kembali ke Katalog (Esc)</span>
        </a>
    </div>

    <!-- MAIN FORM WINDOW CARD -->
    <form id="archiveCreateForm" action="{{ route('archives.store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <!-- SECTION 1: UNIT & DEPARTEMEN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="building-2" class="w-3.5 h-3.5 text-purple-500"></i>
                1. Identitas Unit Kerja & Kepemilikan Dokumen
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Company Entity (Searchable Select) -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PERUSAHAAN ENTITAS <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="hidden" name="company_name" :value="selectedCompany" required>
                        <button 
                            type="button" 
                            disabled
                            class="w-full px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left font-bold text-slate-600 dark:text-slate-300 cursor-not-allowed select-none transition flex items-center justify-between shadow-2xs opacity-90"
                            title="Entitas perusahaan dikunci ke default PT INDRACO GLOBAL INDONESIA"
                        >
                            <span class="truncate" x-text="selectedCompany"></span>
                            <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                        </button>
                    </div>
                </div>

                <!-- Department Selection (Searchable Select) -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        DEPARTEMEN <span class="text-rose-500">*</span>
                    </label>
                    @if(auth()->user()->isPicDept())
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <input 
                            type="text" 
                            value="{{ auth()->user()->department ? auth()->user()->department->code . ' - ' . auth()->user()->department->name : 'Departemen' }}" 
                            disabled 
                            class="w-full px-2.5 py-1.5 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-800 dark:text-slate-300 cursor-not-allowed"
                        >
                    @else
                        <div class="relative" x-data="{
                            open: false,
                            search: '',
                            get filteredDepts() {
                                if (!this.search) return departments;
                                const q = this.search.toLowerCase();
                                return departments.filter(d => (d.code && d.code.toLowerCase().includes(q)) || (d.name && d.name.toLowerCase().includes(q)));
                            },
                            get selectedDeptLabel() {
                                const d = departments.find(item => item.id == selectedDeptId);
                                return d ? (d.code + ' - ' + d.name) : '-- Pilih Departemen --';
                            },
                            select(d) {
                                selectedDeptId = d ? d.id : '';
                                updateSubDepartments();
                                this.open = false;
                                this.search = '';
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="department_id" :value="selectedDeptId" required>
                            <button 
                                type="button" 
                                @click="open = !open" 
                                class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition flex items-center justify-between shadow-2xs cursor-pointer"
                            >
                                <span class="truncate font-semibold" x-text="selectedDeptLabel"></span>
                                <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                            </button>
                            <div 
                                x-show="open" 
                                x-cloak 
                                x-transition
                                class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded shadow-lg overflow-hidden font-mono text-xs"
                            >
                                <div class="p-1.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                    <div class="relative">
                                        <i data-lucide="search" class="w-3 h-3 absolute left-2 top-2 text-slate-400"></i>
                                        <input 
                                            type="text" 
                                            x-model="search" 
                                            @keydown.escape="open = false" 
                                            placeholder="Cari kode / nama departemen..." 
                                            class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                                            x-ref="searchDeptInput"
                                            x-init="$watch('open', value => { if(value) { setTimeout(() => $refs.searchDeptInput?.focus(), 50); if(window.lucide) lucide.createIcons(); } })"
                                        >
                                    </div>
                                </div>
                                <ul class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                    <template x-for="d in filteredDepts" :key="d.id">
                                        <li 
                                            @click="select(d)" 
                                            class="px-2.5 py-1.5 hover:bg-purple-500/15 dark:hover:bg-purple-500/20 cursor-pointer flex items-center justify-between transition"
                                            :class="selectedDeptId == d.id ? 'bg-purple-500/20 font-bold text-purple-700 dark:text-purple-400' : 'text-slate-800 dark:text-slate-200'"
                                        >
                                            <span x-text="d.code + ' - ' + d.name"></span>
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-purple-600" x-show="selectedDeptId == d.id"></i>
                                        </li>
                                    </template>
                                    <li x-show="filteredDepts.length === 0" class="p-2 text-center text-slate-400 text-[11px]">
                                        Tidak ada hasil
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sub-Department Selection (Searchable Select) -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        SUB-DEPARTEMEN <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <div class="relative" x-data="{
                        open: false,
                        search: '',
                        get filteredSubDepts() {
                            if (!this.search) return subDepartments;
                            const q = this.search.toLowerCase();
                            return subDepartments.filter(s => (s.code && s.code.toLowerCase().includes(q)) || (s.name && s.name.toLowerCase().includes(q)));
                        },
                        get selectedSubDeptLabel() {
                            if (!selectedSubDeptId) return '-- Pilih Sub-Departemen (Induk) --';
                            const s = subDepartments.find(item => item.id == selectedSubDeptId);
                            return s ? (s.code + ' - ' + s.name) : '-- Pilih Sub-Departemen (Induk) --';
                        },
                        select(s) {
                            selectedSubDeptId = s ? s.id : '';
                            onSubDeptChange();
                            this.open = false;
                            this.search = '';
                        }
                    }" @click.outside="open = false">
                        <input type="hidden" name="sub_department_id" :value="selectedSubDeptId">
                        <button 
                            type="button" 
                            @click="open = !open" 
                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition flex items-center justify-between shadow-2xs cursor-pointer"
                        >
                            <span class="truncate" :class="selectedSubDeptId ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'" x-text="selectedSubDeptLabel"></span>
                            <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                        </button>
                        <div 
                            x-show="open" 
                            x-cloak 
                            x-transition
                            class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded shadow-lg overflow-hidden font-mono text-xs"
                        >
                            <div class="p-1.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                <div class="relative">
                                    <i data-lucide="search" class="w-3 h-3 absolute left-2 top-2 text-slate-400"></i>
                                    <input 
                                        type="text" 
                                        x-model="search" 
                                        @keydown.escape="open = false" 
                                        placeholder="Cari sub-departemen..." 
                                        class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                                        x-ref="searchSubDeptInput"
                                        x-init="$watch('open', value => { if(value) { setTimeout(() => $refs.searchSubDeptInput?.focus(), 50); if(window.lucide) lucide.createIcons(); } })"
                                    >
                                </div>
                            </div>
                            <ul class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                <li 
                                    @click="select(null)" 
                                    class="px-2.5 py-1.5 hover:bg-indigo-500/15 dark:hover:bg-indigo-500/20 cursor-pointer flex items-center justify-between transition"
                                    :class="!selectedSubDeptId ? 'bg-indigo-500/20 font-bold text-indigo-700 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-400 italic'"
                                >
                                    <span>-- Pilih Sub-Departemen (Induk) --</span>
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-600" x-show="!selectedSubDeptId"></i>
                                </li>
                                <template x-for="s in filteredSubDepts" :key="s.id">
                                    <li 
                                        @click="select(s)" 
                                        class="px-2.5 py-1.5 hover:bg-indigo-500/15 dark:hover:bg-indigo-500/20 cursor-pointer flex items-center justify-between transition"
                                        :class="selectedSubDeptId == s.id ? 'bg-indigo-500/20 font-bold text-indigo-700 dark:text-indigo-400' : 'text-slate-800 dark:text-slate-200'"
                                    >
                                        <span x-text="s.code + ' - ' + s.name"></span>
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-600" x-show="selectedSubDeptId == s.id"></i>
                                    </li>
                                </template>
                                <li x-show="filteredSubDepts.length === 0" class="p-2 text-center text-slate-400 text-[11px]">
                                    Tidak ada sub-departemen
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 2: KATEGORI & IDENTITAS INDUK DOKUMEN -->
        <!-- SECTION 2: IDENTITAS UTAMA KARDUS & KATEGORI -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-blue-700 dark:text-blue-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-blue-500"></i>
                2. Identitas Utama Kardus & Kategori
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start">
                <!-- Judul Utama / Label Kardus (Wajib - Kiri) -->
                <div class="md:col-span-2">
                    <label for="title" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 leading-normal">
                        JUDUL UTAMA / LABEL KARDUS <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="title" 
                        id="title" 
                        x-model="title"
                        required
                        placeholder="Contoh: Laporan Maintenance & Faktur Operasional" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                    >
                </div>

                <!-- Kategori Dokumen (Input Text Opsional - Kanan) -->
                <div>
                    <label for="document_type" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 leading-normal">
                        KATEGORI DOKUMEN <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input 
                        type="text" 
                        name="document_type" 
                        id="document_type" 
                        value="{{ old('document_type') }}" 
                        placeholder="Contoh: Pajak, Keuangan, SDM, Logistik..." 
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                    >
                </div>
            </div>
        </fieldset>

        <!-- SECTION 3: TANGGAL PENYERAHAN & SPESIFIKASI WADAH -->
        <fieldset class="border border-amber-500/40 p-3.5 rounded bg-amber-500/5 dark:bg-amber-950/20 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-amber-800 dark:text-amber-400 bg-amber-100 dark:bg-slate-800 border border-amber-400 dark:border-amber-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-600"></i>
                3. Tanggal Penyerahan & Wadah Fisik Box
            </legend>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Tgl Penyerahan (Disatukan sebagai tanggal serah terima & periode pengajuan, max hari ini) -->
                <div>
                    <label for="tgl_penyerahan" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        TGL. PENYERAHAN DOKUMEN <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="tgl_penyerahan" 
                        id="tgl_penyerahan" 
                        x-model="tglPenyerahan"
                        max="{{ date('Y-m-d') }}"
                        required 
                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block mt-0.5">Tanggal serah terima fisik ke Gudang Arsip (maksimal hari ini)</span>
                </div>

                <!-- Periode Dokumen (Bulan) -->
                <div>
                    <label for="periode_bulan" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PERIODE DOKUMEN (BULAN) <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <div class="relative flex items-center">
                        <input 
                            type="number" 
                            id="periode_bulan" 
                            x-model="periodeBulan"
                            min="1" 
                            max="600"
                            placeholder="Misal: 1, 3, 4, 15..." 
                            class="w-full pl-2.5 pr-14 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                        >
                        <span class="absolute right-2.5 text-xs font-mono font-bold text-slate-400 pointer-events-none select-none">Bulan</span>
                    </div>
                    <input type="hidden" name="periode" :value="periodeBulan ? (periodeBulan + ' Bulan') : ''">

                    <!-- Quick Preset Buttons -->
                    <div class="flex flex-wrap items-center gap-1 mt-1 font-mono text-[10px]">
                        <span class="text-slate-400 text-[9px] mr-0.5">Preset:</span>
                        <template x-for="preset in [1, 3, 4, 6, 12, 15, 24, 60]" :key="preset">
                            <button 
                                type="button" 
                                @click="periodeBulan = preset" 
                                class="px-1.5 py-0.2 rounded border transition cursor-pointer"
                                :class="periodeBulan == preset 
                                    ? 'bg-amber-500 text-slate-950 border-amber-600 font-black' 
                                    : 'bg-slate-100 dark:bg-slate-900 hover:bg-amber-500/20 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800 font-medium'"
                                x-text="preset + ' Bln'"
                            ></button>
                        </template>
                    </div>
                </div>

                <!-- Kondisi Fisik -->
                <div>
                    <label for="physical_condition" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        KONDISI / WADAH FISIK BERKAS <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="physical_condition" 
                        id="physical_condition" 
                        value="{{ old('physical_condition', 'Baik / Box Karton Standar TB 30g') }}" 
                        required 
                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block mt-0.5">Standar Box: TB 30g (Kapasitas 100 per Rak)</span>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 4: DYNAMIC REPEATER GRID (1 BOX -> BANYAK DOKUMEN ARSIP) -->
        <fieldset class="border border-emerald-500/40 p-3.5 rounded bg-emerald-500/5 dark:bg-emerald-950/20 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-emerald-800 dark:text-emerald-400 bg-emerald-100 dark:bg-slate-800 border border-emerald-400 dark:border-emerald-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="list-plus" class="w-3.5 h-3.5 text-emerald-600"></i>
                4. Rincian Butir Dokumen / Berkas Arsip dalam Box (Label A5)
            </legend>

            <div class="flex items-center justify-between border-b border-emerald-500/20 pb-2 font-mono text-xs">
                <span class="font-bold text-slate-700 dark:text-slate-300">
                    Setiap kardus/box berisi banyak arsip. Tuliskan butir dokumen di bawah ini:
                </span>
                <button 
                    @click="addItem()" 
                    type="button" 
                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded text-xs shadow transition flex items-center gap-1.5 cursor-pointer"
                >
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Tambah Baris Dokumen</span>
                </button>
            </div>

            <!-- Repeater Table -->
            <div class="border border-slate-300 dark:border-slate-700 rounded bg-white dark:bg-slate-950 shadow-xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                            <th class="py-2 px-2.5 w-10 text-center">NO</th>
                            <th class="py-2 px-3 w-56 sm:w-64">NAMA DOKUMEN / BERKAS ARSIP <span class="text-rose-500">*</span></th>
                            <th class="py-2 px-2.5 w-44">PERIODE MULAI (BLN/THN) <span class="text-rose-500">*</span></th>
                            <th class="py-2 px-2.5 w-44">PERIODE SELESAI (BLN/THN) <span class="text-rose-500">*</span></th>
                            <th class="py-2 px-3 w-48">KETERANGAN <span class="text-rose-500">*</span></th>
                            <th class="py-2 px-2.5 w-12 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr class="hover:bg-emerald-500/5 transition">
                                <td class="py-2 px-2.5 text-center font-mono font-bold text-slate-600 dark:text-slate-400" x-text="index + 1"></td>
                                <td class="py-2 px-3">
                                    <!-- Searchable Dropdown from Master Archives or Custom Input -->
                                    <div class="space-y-1">
                                        <template x-if="!item.is_custom && masterArchives.length > 0">
                                            <div class="relative w-full max-w-[240px] sm:max-w-[260px]" x-data="{
                                                open: false,
                                                search: '',
                                                get filteredList() {
                                                    if (!this.search) return masterArchives;
                                                    const q = this.search.toLowerCase();
                                                    return masterArchives.filter(m => (m.name && m.name.toLowerCase().includes(q)) || (m.code && m.code.toLowerCase().includes(q)));
                                                },
                                                select(m) {
                                                    item.document_name = m.name;
                                                    this.open = false;
                                                    this.search = '';
                                                },
                                                switchToCustom() {
                                                    item.is_custom = true;
                                                    item.document_name = '';
                                                    this.open = false;
                                                    this.search = '';
                                                }
                                            }" @click.outside="open = false">
                                                <input type="hidden" :name="'items[' + index + '][document_name]'" :value="item.document_name" required>
                                                <button 
                                                    type="button" 
                                                    @click="open = !open" 
                                                    class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-left text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 transition flex items-center justify-between shadow-2xs cursor-pointer"
                                                    :title="item.document_name || ''"
                                                >
                                                    <span class="truncate" :class="item.document_name ? 'text-slate-900 dark:text-white' : 'text-slate-400'" x-text="item.document_name || '-- Pilih dari Master Berkas Arsip --'"></span>
                                                    <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                                                </button>
                                                <div 
                                                    x-show="open" 
                                                    x-cloak 
                                                    x-transition
                                                    class="absolute z-50 left-0 mt-1 w-[380px] sm:w-[460px] max-w-[85vw] bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xl overflow-hidden font-mono text-xs"
                                                >
                                                    <div class="p-1.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                                        <div class="relative">
                                                            <i data-lucide="search" class="w-3 h-3 absolute left-2 top-2 text-slate-400"></i>
                                                            <input 
                                                                type="text" 
                                                                x-model="search" 
                                                                @keydown.escape="open = false" 
                                                                placeholder="Ketik cari master berkas arsip..." 
                                                                class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                                                                x-ref="searchItemInput"
                                                                x-init="$watch('open', value => { if(value) { setTimeout(() => $refs.searchItemInput?.focus(), 50); if(window.lucide) lucide.createIcons(); } })"
                                                            >
                                                        </div>
                                                    </div>
                                                    <ul class="max-h-56 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                                        <template x-for="m in filteredList" :key="m.id">
                                                            <li 
                                                                @click="select(m)" 
                                                                :title="m.name"
                                                                class="px-3 py-2 hover:bg-emerald-500/15 dark:hover:bg-emerald-500/20 cursor-pointer flex items-start justify-between gap-2 transition"
                                                                :class="item.document_name === m.name ? 'bg-emerald-500/20 font-bold text-emerald-700 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-200'"
                                                            >
                                                                <div class="min-w-0 flex-1">
                                                                    <span class="block whitespace-normal break-words leading-snug" x-text="m.name"></span>
                                                                    <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-sans mt-0.5" x-show="m.code || (!selectedSubDeptId && m.sub_department)" x-text="(m.code ? '[' + m.code + '] ' : '') + (!selectedSubDeptId && m.sub_department ? '(' + (m.sub_department.code || m.sub_department.name) + ')' : '')"></span>
                                                                </div>
                                                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" x-show="item.document_name === m.name"></i>
                                                            </li>
                                                        </template>
                                                        <li x-show="filteredList.length === 0" class="p-3 text-center text-slate-400 text-[11px]">
                                                            Tidak ada berkas yang cocok
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Custom Text Input -->
                                        <template x-if="item.is_custom || masterArchives.length === 0">
                                            <div class="flex items-center gap-1 w-full max-w-[240px] sm:max-w-[260px]">
                                                <input 
                                                    type="text" 
                                                    :name="'items[' + index + '][document_name]'" 
                                                    x-model="item.document_name" 
                                                    required 
                                                    placeholder="Ketik nama dokumen / berkas..."
                                                    class="w-full px-2.5 py-1.5 bg-amber-500/10 dark:bg-amber-950/20 border-2 border-amber-500/60 rounded text-xs font-mono font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500"
                                                >
                                                <button 
                                                    x-show="masterArchives.length > 0" 
                                                    @click="item.is_custom = false; item.document_name = (masterArchives[0] ? masterArchives[0].name : '')" 
                                                    type="button" 
                                                    class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[10px] font-mono font-bold shrink-0 cursor-pointer" 
                                                    title="Kembali ke pilihan Master Berkas"
                                                >
                                                    ↺ Master
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="py-2 px-2.5">
                                    <div class="relative flex items-center">
                                        <input 
                                            type="month" 
                                            :name="'items[' + index + '][period_start]'" 
                                            x-model="item.period_start" 
                                            max="{{ date('Y-m') }}"
                                            :max="item.period_end ? (item.period_end < '{{ date('Y-m') }}' ? item.period_end : '{{ date('Y-m') }}') : '{{ date('Y-m') }}'"
                                            @change="validatePeriod(item)"
                                            required 
                                            class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-purple-700 dark:text-purple-300 focus:outline-none focus:border-emerald-500"
                                            title="Pilih Bulan & Tahun Mulai (Maksimal Bulan Sekarang)"
                                        >
                                    </div>
                                </td>
                                <td class="py-2 px-2.5">
                                    <div class="relative flex items-center">
                                        <input 
                                            type="month" 
                                            :name="'items[' + index + '][period_end]'" 
                                            x-model="item.period_end" 
                                            max="{{ date('Y-m') }}"
                                            :max="'{{ date('Y-m') }}'"
                                            :min="item.period_start || null"
                                            @change="validatePeriod(item)"
                                            required 
                                            class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-purple-700 dark:text-purple-300 focus:outline-none focus:border-emerald-500"
                                            title="Pilih Bulan & Tahun Selesai (Maksimal Bulan Aktif Sekarang)"
                                        >
                                    </div>
                                </td>
                                <td class="py-2 px-3 align-top">
                                    <textarea 
                                        :name="'items[' + index + '][notes]'" 
                                        x-model="item.notes" 
                                        required
                                        rows="1"
                                        placeholder="No. berkas fisik / catatan (Wajib)" 
                                        @input="$el.style.height = 'auto'; $el.style.height = Math.max(34, $el.scrollHeight) + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = Math.max(34, $el.scrollHeight) + 'px'; })"
                                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-700 dark:text-slate-300 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition resize-none overflow-hidden leading-relaxed block shadow-2xs"
                                        style="min-height: 34px;"
                                    ></textarea>
                                </td>
                                <td class="py-2 px-2.5 text-center">
                                    <button 
                                        @click="removeItem(index)" 
                                        type="button" 
                                        class="p-1.5 text-rose-500 hover:bg-rose-500/20 rounded transition cursor-pointer" 
                                        title="Hapus baris dokumen ini"
                                    >
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Total Counter Bar -->
            <div class="flex items-center justify-between font-mono text-[11px] text-slate-500 dark:text-slate-400 pt-1">
                <span>Total Butir Terdaftar: <strong class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="items.length"></strong> Dokumen</span>
            </div>
        </fieldset>

        <!-- SECTION 5: UPLOAD BERKAS DIGITAL & SCAN FORMULIR -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                5. Upload Berkas Digital & Scan Formulir
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 font-mono text-xs"
                 @file-change="if($event.detail.name === 'scan_input_form') { hasScanForm = $event.detail.hasFile && !$event.detail.isOverLimit; }">
                <!-- 1. Scan Formulir Input (Wajib untuk Pengajuan) -->
                <x-file-uploader 
                    name="scan_input_form" 
                    id="scan_input_form" 
                    label="Scan Formulir Input" 
                    :required="true" 
                    badge="WAJIB UNTUK PENGAJUAN" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeMB="2"
                    helperText="Format: PDF, JPG, PNG (Maksimal 2MB). Foto scan besar otomatis dioptimalkan agar ringan & teks tetap tajam."
                />

                <!-- 2. Lampiran Digital Dokumen (Opsional) -->
                <x-file-uploader 
                    name="file" 
                    id="file" 
                    label="Lampiran Digital Dokumen" 
                    :required="false" 
                    badge="OPSIONAL" 
                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.zip"
                    :maxSizeMB="2"
                    helperText="Format: PDF, JPG, DOCX, XLSX, ZIP (Maksimal 2MB). Opsional untuk kelengkapan digital."
                />
            </div>
        </fieldset>

        <!-- FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-end gap-2 font-mono">
            <a href="{{ route('archives.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                Batal
            </a>
            <!-- Simpan Sebagai Draft (Simpan Sementara / PIC Dept) - Selalu Aktif -->
            <button 
                type="submit" 
                name="submit_action" 
                value="draft" 
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded border border-slate-400 dark:border-slate-600 shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                title="Simpan sementara sebagai draft usulan (tidak dikirim ke PIC Gudang)"
            >
                <i data-lucide="file-clock" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Simpan Sementara (Draft)</span>
            </button>
            <!-- Simpan & Ajukan Box Arsip -->
            <button 
                type="button" 
                @click="submitFinal('submit')"
                :class="hasScanForm ? 'bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 border-amber-600 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-pointer'"
                class="px-5 py-2 font-black text-xs rounded border transition flex items-center gap-1.5"
                title="Simpan & Ajukan ke PIC Gudang (Wajib menyertakan Scan Formulir Input)"
            >
                <i data-lucide="send" class="w-3.5 h-3.5" :class="hasScanForm ? 'text-slate-950' : 'text-slate-400 dark:text-slate-500'"></i>
                <span>Simpan & Ajukan Box Arsip (TB 30g)</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function archiveCreateApp() {
    return {
        hasScanForm: false,
        departments: @json($departments),
        selectedCompany: '{{ old('company_name', 'PT INDRACO GLOBAL INDONESIA') }}',
        selectedDeptId: '{{ old('department_id', auth()->user()->isPicDept() ? auth()->user()->department_id : ($departments->first()->id ?? '')) }}',
        selectedSubDeptId: '{{ old('sub_department_id', '') }}',
        subDepartments: [],
        masterArchives: [],
        isCustomDocName: {{ old('is_custom_doc_name') ? 'true' : 'false' }},
        customDocName: @json(old('custom_doc_name', '')),
        title: @json(old('title', '')),
        tglPenyerahan: '{{ old('tgl_penyerahan', date('Y-m-d')) }}',
        periodeBulan: '{{ old('periode_bulan', preg_match('/(\d+)/', old('periode', ''), $m) ? $m[1] : '') }}',

        currentMonth: '{{ date('Y-m') }}',

        // Dynamic items repeater (1 Box = Banyak Berkas Arsip)
        items: [
            { id: 1, document_name: '', is_custom: false, period_start: '{{ date('Y-m', strtotime('-3 months')) }}', period_end: '{{ date('Y-m') }}', notes: '' },
            { id: 2, document_name: '', is_custom: false, period_start: '{{ date('Y-m', strtotime('-1 month')) }}', period_end: '{{ date('Y-m') }}', notes: '' },
            { id: 3, document_name: '', is_custom: false, period_start: '{{ date('Y-m') }}', period_end: '{{ date('Y-m') }}', notes: '' }
        ],

        validatePeriod(item) {
            const maxM = '{{ date('Y-m') }}';
            if (item.period_end && item.period_end > maxM) {
                item.period_end = maxM;
            }
            if (item.period_start && item.period_start > maxM) {
                item.period_start = maxM;
            }
            if (item.period_start && item.period_end && item.period_start > item.period_end) {
                item.period_start = item.period_end;
            }
        },

        init() {
            this.updateSubDepartments();
            this.updateMasterArchives();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        addItem() {
            const defaultName = this.masterArchives.length > 0 ? this.masterArchives[0].name : '';
            this.items.push({
                id: Date.now() + Math.random(),
                document_name: defaultName,
                is_custom: false,
                period_start: '{{ date('Y-m') }}',
                period_end: '{{ date('Y-m') }}',
                notes: ''
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            } else {
                alert('Minimal harus terdapat 1 butir berkas arsip dalam box.');
            }
        },

        updateSubDepartments() {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            if (dept && dept.sub_departments) {
                this.subDepartments = dept.sub_departments;
            } else {
                this.subDepartments = [];
            }
            this.selectedSubDeptId = '';
            this.updateMasterArchives();
        },

        onSubDeptChange() {
            this.updateMasterArchives();
        },

        updateMasterArchives() {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            let allArchives = [];
            if (dept && dept.master_archives && dept.master_archives.length > 0) {
                allArchives = dept.master_archives;
                this.filterAndSetArchives(allArchives);
            } else if (this.selectedDeptId) {
                // Fallback fetch if not present in initial JSON
                fetch('{{ url('/api/departments') }}/' + this.selectedDeptId + '/master-archives')
                    .then(res => res.json())
                    .then(data => {
                        if (data.master_archives) {
                            if (dept) dept.master_archives = data.master_archives;
                            this.filterAndSetArchives(data.master_archives);
                        } else {
                            this.masterArchives = [];
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching master archives:', err);
                        this.masterArchives = [];
                    });
            } else {
                this.masterArchives = [];
            }
        },

        filterAndSetArchives(allArchives) {
            if (this.selectedSubDeptId) {
                this.masterArchives = allArchives.filter(m => {
                    return m.sub_department_id == this.selectedSubDeptId || !m.sub_department_id;
                });
            } else {
                this.masterArchives = allArchives;
            }

            // Set default document_name for items if empty or no longer in filtered list
            if (this.masterArchives.length > 0) {
                this.items.forEach((it, idx) => {
                    if (!it.document_name || !this.masterArchives.some(m => m.name === it.document_name)) {
                        it.document_name = this.masterArchives[idx % this.masterArchives.length].name;
                    }
                });
            }
        },

        submitFinal(action) {
            if (!this.hasScanForm) {
                alert('⚠️ PERHATIAN: BERKAS BELUM LENGKAP!\n\n"Scan Formulir Input" wajib diunggah sebelum mengajukan verifikasi box arsip.\n\nSilakan unggah Scan Formulir Input pada Bagian 5, atau klik tombol "Simpan Sementara (Draft)" jika ingin menyimpan data sementara tanpa pengajuan.');
                const fileInput = document.getElementById('scan_input_form');
                if (fileInput) {
                    fileInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    fileInput.focus();
                }
                return;
            }

            const form = document.getElementById('archiveCreateForm');
            if (form) {
                let actionInput = form.querySelector('input[name="submit_action"]');
                if (!actionInput) {
                    actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'submit_action';
                    form.appendChild(actionInput);
                }
                actionInput.value = action;
                form.submit();
            }
        }
    };
}
</script>
@endpush
@endsection
