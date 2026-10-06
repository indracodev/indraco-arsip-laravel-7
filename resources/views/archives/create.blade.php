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
    <form action="{{ route('archives.store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
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
                    <div class="relative" x-data="{
                        open: false,
                        search: '',
                        options: [
                            'PT Indraco Jaya Perkasa',
                            'PT Indraco Global',
                            'PT Indraco Trading',
                            'PT Indraco Enterprise',
                            'PT Indraco International'
                        ],
                        get filteredOptions() {
                            if (!this.search) return this.options;
                            return this.options.filter(opt => opt.toLowerCase().includes(this.search.toLowerCase()));
                        },
                        select(opt) {
                            selectedCompany = opt;
                            this.open = false;
                            this.search = '';
                        }
                    }" @click.outside="open = false">
                        <input type="hidden" name="company_name" :value="selectedCompany" required>
                        <button 
                            type="button" 
                            @click="open = !open" 
                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition flex items-center justify-between shadow-2xs cursor-pointer"
                        >
                            <span class="truncate font-semibold" x-text="selectedCompany || '-- Pilih Perusahaan --'"></span>
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
                                        placeholder="Cari perusahaan..." 
                                        class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                                        x-ref="searchCompanyInput"
                                        x-init="$watch('open', value => { if(value) { setTimeout(() => $refs.searchCompanyInput?.focus(), 50); if(window.lucide) lucide.createIcons(); } })"
                                    >
                                </div>
                            </div>
                            <ul class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                <template x-for="opt in filteredOptions" :key="opt">
                                    <li 
                                        @click="select(opt)" 
                                        class="px-2.5 py-1.5 hover:bg-amber-500/15 dark:hover:bg-amber-500/20 cursor-pointer flex items-center justify-between transition"
                                        :class="selectedCompany === opt ? 'bg-amber-500/20 font-bold text-amber-700 dark:text-amber-400' : 'text-slate-800 dark:text-slate-200'"
                                    >
                                        <span x-text="opt"></span>
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-amber-600" x-show="selectedCompany === opt"></i>
                                    </li>
                                </template>
                                <li x-show="filteredOptions.length === 0" class="p-2 text-center text-slate-400 text-[11px]">
                                    Tidak ada hasil
                                </li>
                            </ul>
                        </div>
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
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-blue-700 dark:text-blue-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-blue-500"></i>
                2. Kategori & Identitas Utama Kardus
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start">
                <!-- Kategori Dokumen (Input Text Opsional) -->
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

                <!-- Judul Utama / Label Kardus (Aligned with Kategori Dokumen) -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1 font-mono leading-normal">
                        <label for="title" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 truncate">
                            JUDUL UTAMA / LABEL KARDUS <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer select-none shrink-0 ml-2">
                            <input type="checkbox" name="is_custom_doc_name" value="1" x-model="isCustomDocName" class="rounded border-slate-300 text-amber-500 focus:ring-amber-400 h-3.5 w-3.5">
                            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">Custom Nama Dokumen</span>
                        </label>
                    </div>

                    <!-- Standard Document Name -->
                    <div x-show="!isCustomDocName">
                        <input 
                            type="text" 
                            name="title" 
                            id="title" 
                            x-model="title"
                            placeholder="Contoh: Laporan Maintenance & Faktur Operasional" 
                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <!-- Custom Document Name -->
                    <div x-show="isCustomDocName" x-cloak>
                        <input 
                            type="text" 
                            name="custom_doc_name" 
                            id="custom_doc_name" 
                            x-model="customDocName"
                            placeholder="Ketik judul khusus kardus jika diperlukan..." 
                            class="w-full px-2.5 py-1.5 bg-amber-500/10 dark:bg-amber-950/30 border border-amber-500 rounded text-xs font-mono font-bold text-amber-950 dark:text-amber-200 placeholder-amber-600/50 focus:outline-none focus:border-amber-600 transition"
                        >
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 3: TANGGAL PENYERAHAN, PERIODE & SPESIFIKASI WADAH -->
        <fieldset class="border border-amber-500/40 p-3.5 rounded bg-amber-500/5 dark:bg-amber-950/20 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-amber-800 dark:text-amber-400 bg-amber-100 dark:bg-slate-800 border border-amber-400 dark:border-amber-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-600"></i>
                3. Tanggal Penyerahan, Periode & Wadah Fisik Box
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
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
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block mt-0.5">Tanggal serah terima fisik ke Gudang (maks. hari ini)</span>
                </div>

                <!-- Periode Dokumen / Arsip (Angka Bulan: 1 bulan, 3 bulan, 4 bulan, 15 bulan, dll.) -->
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
                    <span>+ Tambah Baris Dokumen</span>
                </button>
            </div>

            <!-- Repeater Table -->
            <div class="border border-slate-300 dark:border-slate-700 rounded bg-white dark:bg-slate-950 shadow-xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                            <th class="py-2 px-2.5 w-10 text-center">NO</th>
                            <th class="py-2 px-3">NAMA DOKUMEN / BERKAS ARSIP <span class="text-rose-500">*</span></th>
                            <th class="py-2 px-2.5 w-44">PERIODE MULAI (BLN/THN) <span class="text-rose-500">*</span></th>
                            <th class="py-2 px-2.5 w-44">PERIODE SELESAI (BLN/THN) <span class="text-rose-500">*</span></th>
                            <th class="py-2 px-3 w-48">KETERANGAN (OPSIONAL)</th>
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
                                            <div class="relative" x-data="{
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
                                                >
                                                    <span class="truncate" :class="item.document_name ? 'text-slate-900 dark:text-white' : 'text-slate-400'" x-text="item.document_name || '-- Pilih dari Master Berkas Arsip --'"></span>
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
                                                                placeholder="Ketik cari master berkas arsip..." 
                                                                class="w-full pl-7 pr-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                                                                x-ref="searchItemInput"
                                                                x-init="$watch('open', value => { if(value) { setTimeout(() => $refs.searchItemInput?.focus(), 50); if(window.lucide) lucide.createIcons(); } })"
                                                            >
                                                        </div>
                                                    </div>
                                                    <ul class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                                                        <template x-for="m in filteredList" :key="m.id">
                                                            <li 
                                                                @click="select(m)" 
                                                                class="px-2.5 py-1.5 hover:bg-emerald-500/15 dark:hover:bg-emerald-500/20 cursor-pointer flex items-center justify-between transition"
                                                                :class="item.document_name === m.name ? 'bg-emerald-500/20 font-bold text-emerald-700 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-200'"
                                                            >
                                                                <span class="truncate" x-text="m.name + (m.code ? ' [' + m.code + ']' : '') + (!selectedSubDeptId && m.sub_department ? ' (' + (m.sub_department.code || m.sub_department.name) + ')' : '')"></span>
                                                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0 ml-1" x-show="item.document_name === m.name"></i>
                                                            </li>
                                                        </template>
                                                        <li x-show="filteredList.length === 0" class="p-2 text-center text-slate-400 text-[11px]">
                                                            Tidak ada berkas yang cocok
                                                        </li>
                                                        <li 
                                                            @click="switchToCustom()" 
                                                            class="px-2.5 py-2 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-bold cursor-pointer border-t border-amber-200 dark:border-amber-800 flex items-center gap-1.5 transition text-[11px]"
                                                        >
                                                            <span>✍️ + Tulis Nama Dokumen Kustom / Lainnya</span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Custom Text Input -->
                                        <template x-if="item.is_custom || masterArchives.length === 0">
                                            <div class="flex items-center gap-1">
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
                                            required 
                                            class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-purple-700 dark:text-purple-300 focus:outline-none focus:border-emerald-500"
                                            title="Pilih Bulan & Tahun Mulai"
                                        >
                                    </div>
                                </td>
                                <td class="py-2 px-2.5">
                                    <div class="relative flex items-center">
                                        <input 
                                            type="month" 
                                            :name="'items[' + index + '][period_end]'" 
                                            x-model="item.period_end" 
                                            required 
                                            class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-purple-700 dark:text-purple-300 focus:outline-none focus:border-emerald-500"
                                            title="Pilih Bulan & Tahun Selesai"
                                        >
                                    </div>
                                </td>
                                <td class="py-2 px-3">
                                    <input 
                                        type="text" 
                                        :name="'items[' + index + '][notes]'" 
                                        x-model="item.notes" 
                                        placeholder="No. berkas fisik / catatan" 
                                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-700 dark:text-slate-300 placeholder-slate-400 focus:outline-none focus:border-emerald-500"
                                    >
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

            <!-- Quick Add Button bar -->
            <div class="flex items-center justify-between font-mono text-[11px] text-slate-500 dark:text-slate-400 pt-1">
                <span>Total Butir Terdaftar: <strong class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="items.length"></strong> Dokumen</span>
                <button 
                    @click="addItem()" 
                    type="button" 
                    class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold inline-flex items-center gap-1 cursor-pointer"
                >
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>+ Tambah Baris Dokumen Lainnya</span>
                </button>
            </div>
        </fieldset>

        <!-- SECTION 5: UPLOAD BERKAS DIGITAL & SCAN APPROVAL -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                5. Upload Berkas Digital & Scan Formulir
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 font-mono text-xs">
                <div>
                    <label for="scan_input_form" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Scan Formulir Input</label>
                    <input 
                        type="file" 
                        name="scan_input_form" 
                        id="scan_input_form" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-700 dark:text-slate-300 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[11px] file:font-bold file:bg-amber-500/20 file:text-amber-700 dark:file:text-amber-400"
                    >
                </div>

                <div>
                    <label for="scan_approval_input" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Scan Bukti Approval Input</label>
                    <input 
                        type="file" 
                        name="scan_approval_input" 
                        id="scan_approval_input" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-700 dark:text-slate-300 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[11px] file:font-bold file:bg-amber-500/20 file:text-amber-700 dark:file:text-amber-400"
                    >
                </div>

                <div>
                    <label for="file" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Lampiran Digital Dokumen</label>
                    <input 
                        type="file" 
                        name="file" 
                        id="file" 
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.zip"
                        class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] text-slate-700 dark:text-slate-300 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[11px] file:font-bold file:bg-slate-500/20 file:text-slate-700 dark:file:text-slate-400"
                    >
                </div>
            </div>
        </fieldset>

        <!-- FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-end gap-2 font-mono">
            <a href="{{ route('archives.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                Batal
            </a>
            <!-- Simpan Sebagai Draft (Simpan Sementara / PIC Dept) -->
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
                type="submit" 
                name="submit_action" 
                value="submit" 
                class="px-5 py-2 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded border border-amber-600 shadow-md transition flex items-center gap-1.5 cursor-pointer"
            >
                <i data-lucide="send" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Simpan & Ajukan Box Arsip (TB 30g)</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function archiveCreateApp() {
    return {
        departments: @json($departments),
        selectedCompany: '{{ old('company_name', 'PT Indraco Jaya Perkasa') }}',
        selectedDeptId: '{{ old('department_id', auth()->user()->isPicDept() ? auth()->user()->department_id : ($departments->first()->id ?? '')) }}',
        selectedSubDeptId: '{{ old('sub_department_id', '') }}',
        subDepartments: [],
        masterArchives: [],
        isCustomDocName: {{ old('is_custom_doc_name') ? 'true' : 'false' }},
        customDocName: @json(old('custom_doc_name', '')),
        title: @json(old('title', '')),
        tglPenyerahan: '{{ old('tgl_penyerahan', date('Y-m-d')) }}',
        periodeBulan: '{{ old('periode_bulan', preg_match('/(\d+)/', old('periode', ''), $m) ? $m[1] : '') }}',

        // Dynamic items repeater (1 Box = Banyak Berkas Arsip)
        items: [
            { id: 1, document_name: '', is_custom: false, period_start: '{{ date('Y-06') }}', period_end: '{{ date('Y-08') }}', notes: '' },
            { id: 2, document_name: '', is_custom: false, period_start: '{{ date('Y-07') }}', period_end: '{{ date('Y-07') }}', notes: '' },
            { id: 3, document_name: '', is_custom: false, period_start: '{{ date('Y-01') }}', period_end: '{{ date('Y-12') }}', notes: '' }
        ],

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
        }
    };
}
</script>
@endpush
@endsection
