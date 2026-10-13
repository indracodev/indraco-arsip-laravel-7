@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Pengajuan Multi-Box Arsip (TB 30g) - DMS PT Indraco')

@section('content')
<div class="max-w-6xl mx-auto space-y-3 font-sans pb-10 select-text" x-data="archiveCreateApp()">

    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="package-plus" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Form Pengajuan Multi-Box Arsip (TB 30g)</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Entri kolektif banyak box kardus fisik & dense items dokumen dengan otomasi periode cerdas.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Counter Chip -->
            <div class="px-2.5 py-1 bg-amber-500/10 border border-amber-500/30 rounded text-xs font-mono font-bold text-amber-700 dark:text-amber-300 flex items-center gap-1.5">
                <i data-lucide="boxes" class="w-3.5 h-3.5 text-amber-500"></i>
                <span x-text="boxes.length + ' Box'"></span>
                <span>•</span>
                <span x-text="getTotalItemsCount() + ' Berkas'"></span>
            </div>

            <!-- Tombol Cetak Form Pengajuan Dokumen -->
            <button 
                type="button" 
                @click="openFormPengajuanModal()" 
                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/50 dark:hover:bg-amber-900/50 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/60 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm shrink-0 cursor-pointer"
                title="Buka Pratinjau & Cetak Form Pengajuan Dokumen (Standar PDF)"
            >
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                <span>Form Pengajuan</span>
            </button>

            <a href="{{ route('archives.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm shrink-0">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Kembali (Esc)</span>
            </a>
        </div>
    </div>

    <!-- MAIN FORM WINDOW -->
    <form id="archiveCreateForm" action="{{ route('archives.store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <!-- SECTION 1: UNIT & TANGGAL SERAH TERIMA (BATCH HEADER) -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3 font-mono">
            <legend class="px-2 font-mono text-[11px] font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="building-2" class="w-3.5 h-3.5 text-purple-500"></i>
                1. Identitas Unit Kerja & Tanggal Serah Terima Pengajuan
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Company Entity -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PERUSAHAAN ENTITAS <span class="text-rose-500">*</span>
                    </label>
                    <input type="hidden" name="company_name" :value="selectedCompany" required>
                    <button 
                        type="button" 
                        disabled
                        class="w-full px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left font-bold text-slate-600 dark:text-slate-300 cursor-not-allowed select-none transition flex items-center justify-between shadow-2xs opacity-90"
                    >
                        <span class="truncate" x-text="selectedCompany"></span>
                        <i data-lucide="lock" class="w-3 h-3 text-slate-400 shrink-0 ml-1"></i>
                    </button>
                </div>

                <!-- Department Selection -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        DEPARTEMEN PEMILIK ARSIP <span class="text-rose-500">*</span>
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
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Tgl Penyerahan Fisik (Serah terima batch) -->
                <div>
                    <label for="tgl_penyerahan" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        TGL. SERAH TERIMA FISIK KE GUDANG <span class="text-rose-500">*</span>
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
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-sans block mt-0.5">Tanggal serah terima kardus fisik ke Gudang (maksimal hari ini)</span>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 2: WORKSTATION MULTI-BOX (NAVIGASI & TAB KONTROL BOX) -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl p-3.5 shadow-sm space-y-3 font-mono">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            2. Pengelolaan Multi-Box Arsip
                        </h2>
                        <p class="text-[10px] text-slate-500 font-sans">
                            Klik tab box untuk beralih. Anda dapat menambah atau menduplikat box secara instan.
                        </p>
                    </div>
                </div>

                <!-- Box Action Toolbar -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button 
                        type="button" 
                        @click="addBox()" 
                        class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-bold transition flex items-center gap-1 shadow-sm"
                        title="Tambah Box Arsip Baru"
                    >
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Tambah Box</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openDuplicateModal(activeBoxIndex)" 
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1"
                        title="Duplikasi Box dengan Pilihan Granular (Semua Butir / Metadata Saja / Butir Terpilih)"
                    >
                        <i data-lucide="copy" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Duplikat Box...</span>
                    </button>

                    <template x-if="boxes.length > 1">
                        <button 
                            type="button" 
                            @click="removeBox(activeBoxIndex)" 
                            class="px-2 py-1 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-300 dark:border-rose-800 rounded text-xs font-bold transition flex items-center gap-1"
                            title="Hapus Box yang Sedang Aktif"
                        >
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span>Hapus Box</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Box Navigation Tabs (Scrollable Ribbon) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1.5 scrollbar-thin">
                <template x-for="(b, bIdx) in boxes" :key="b.id">
                    <button 
                        type="button" 
                        @click="activeBoxIndex = bIdx; $nextTick(() => { if(window.lucide) lucide.createIcons(); });" 
                        class="px-3 py-1.5 rounded-lg border text-xs font-mono font-bold transition flex items-center gap-2 shrink-0 select-none cursor-pointer"
                        :class="activeBoxIndex === bIdx 
                            ? 'bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 border-amber-600 shadow-md ring-2 ring-amber-500/20' 
                            : 'bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-800 hover:bg-slate-200 dark:hover:bg-slate-800'"
                    >
                        <i data-lucide="package" class="w-3.5 h-3.5" :class="activeBoxIndex === bIdx ? 'text-slate-950' : 'text-amber-500'"></i>
                        <span x-text="'Box ' + (bIdx + 1)"></span>
                        <span class="text-[10px] font-normal max-w-[120px] truncate" x-show="b.title" x-text="b.title"></span>
                        <span 
                            class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                            :class="activeBoxIndex === bIdx ? 'bg-slate-950 text-amber-400' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                            x-text="b.items.length + ' Dokumen'"
                        ></span>
                    </button>
                </template>
            </div>

            <!-- CONTAINER FOR ALL BOXES (All inputs stay in DOM so they are fully submitted) -->
            <template x-for="(box, bIdx) in boxes" :key="box.id">
                <div x-show="activeBoxIndex === bIdx" class="space-y-4 pt-2">
                    
                    <!-- BOX METADATA CARD -->
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 rounded font-black text-xs" x-text="'BOX #' + (bIdx + 1)"></span>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Pengaturan Identitas & Wadah Kardus</span>
                                
                                <!-- Preset Pola Box Sebelumnya -->
                                <template x-if="bIdx > 0">
                                    <button 
                                        type="button" 
                                        @click="applyPreviousBoxPreset(bIdx)"
                                        class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded text-[10px] font-bold transition flex items-center gap-1 cursor-pointer"
                                        title="Salin sub-dept, jenis dokumen, dan kondisi fisik dari Box sebelumnya"
                                    >
                                        <i data-lucide="sparkles" class="w-3 h-3 text-blue-500"></i>
                                        <span>Gunakan Pola Box Sebelumnya</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <!-- Judul Box dengan Live Similarity Check -->
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                    <span>JUDUL / LABEL KARDUS <span class="text-rose-500">*</span></span>
                                    <span class="text-[10px] text-slate-400 font-normal">Cek kemiripan aktif otomatis</span>
                                </label>
                                <input 
                                    type="text" 
                                    :name="'boxes[' + bIdx + '][title]'" 
                                    x-model="box.title"
                                    @input="checkSimilarity(bIdx)"
                                    required
                                    placeholder="Contoh: Voucher Kas Kecil & Pengeluaran Q1" 
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                                >

                                <!-- Live Similarity Alert Banner -->
                                <template x-if="box.similarityInfo && box.similarityInfo.has_similarity">
                                    <div 
                                        class="mt-1.5 p-2 rounded border text-[11px] font-sans flex items-start gap-2 transition"
                                        :class="box.similarityInfo.match_type === 'identical' ? 'bg-amber-500/15 border-amber-500/40 text-amber-900 dark:text-amber-200' : 'bg-blue-50 dark:bg-blue-950/40 border-blue-300 dark:border-blue-800 text-blue-900 dark:text-blue-200'"
                                    >
                                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                                        <div class="space-y-0.5 flex-1">
                                            <div class="font-bold flex items-center justify-between">
                                                <span x-text="box.similarityInfo.message"></span>
                                                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded font-black" :class="box.similarityInfo.match_type === 'identical' ? 'bg-amber-500/30 text-amber-800 dark:text-amber-200' : 'bg-blue-500/20 text-blue-800 dark:text-blue-200'" x-text="box.similarityInfo.similarity_percentage + '% Mirip'"></span>
                                            </div>
                                            <template x-if="box.similarityInfo.diff && (box.similarityInfo.diff.unique_in_input.length > 0 || box.similarityInfo.diff.unique_in_existing.length > 0)">
                                                <div class="text-[10px] text-slate-600 dark:text-slate-400 font-mono pt-0.5">
                                                    <span>Kata berbeda: </span>
                                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="box.similarityInfo.diff.unique_in_input.join(', ') || '-'"></span>
                                                </div>
                                            </template>
                                            <p class="text-[10px] text-slate-500 italic">Peringatan ini informatif agar Anda dapat memeriksa potensi duplikasi. Anda tetap bebas menyimpan jika kardus ini sah.</p>
                                        </div>
                                        <button type="button" @click="box.similarityInfo = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shrink-0 text-xs px-1 cursor-pointer">✕</button>
                                    </div>
                                </template>
                            </div>

                            <!-- Sub-Departemen (Opsional per box) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    SUB-DEPARTEMEN <span class="text-slate-400 font-normal">(Opsional)</span>
                                </label>
                                <select 
                                    :name="'boxes[' + bIdx + '][sub_department_id]'" 
                                    x-model="box.sub_department_id"
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                                >
                                    <option value="">-- Induk Departemen --</option>
                                    <template x-for="s in subDepartments" :key="s.id">
                                        <option :value="s.id" x-text="s.code + ' - ' + s.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Kategori Dokumen (Opsional) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    KATEGORI DOKUMEN <span class="text-slate-400 font-normal">(Opsional)</span>
                                </label>
                                <input 
                                    type="text" 
                                    :name="'boxes[' + bIdx + '][document_type]'" 
                                    x-model="box.document_type"
                                    placeholder="Contoh: Keuangan, Pajak, SDM" 
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                                >
                            </div>

                            <!-- Wadah Fisik -->
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    KONDISI FISIK WADAH KARDUS <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    :name="'boxes[' + bIdx + '][physical_condition]'" 
                                    x-model="box.physical_condition"
                                    required 
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                                >
                            </div>

                            <!-- Periode Dokumen (Bulan) -->
                            <div class="sm:col-span-2 space-y-1">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        PERIODE DOKUMEN (BULAN) <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-normal">Maks. 60 Bulan</span>
                                </div>

                                <div class="relative flex items-center">
                                    <input 
                                        type="number" 
                                        :id="'periode_bulan_' + bIdx"
                                        x-model="box.periode_bulan"
                                        :disabled="bIdx > 0 && box.follow_previous"
                                        @input="if(box.periode_bulan > 60) box.periode_bulan = 60; if(box.periode_bulan !== '' && box.periode_bulan < 1) box.periode_bulan = 1;"
                                        min="1" 
                                        max="60"
                                        placeholder="Maks. 60 Bulan (5 Thn)" 
                                        class="w-full pl-2.5 pr-14 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition disabled:bg-slate-100 dark:disabled:bg-slate-900/50 disabled:text-slate-400 disabled:cursor-not-allowed"
                                    >
                                    <span class="absolute right-2.5 text-xs font-mono font-bold text-slate-400 pointer-events-none select-none">Bulan</span>
                                </div>

                                <!-- Checklist Ikut Sebelumnya (untuk Box ke-2 dst) -->
                                <template x-if="bIdx > 0">
                                    <label class="flex items-center gap-1.5 pt-0.5 font-mono text-[11px] text-amber-700 dark:text-amber-400 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            x-model="box.follow_previous" 
                                            @change="if(box.follow_previous) { box.periode_bulan = boxes[bIdx - 1].periode_bulan; }"
                                            class="rounded text-amber-500 focus:ring-amber-400"
                                        >
                                        <span class="font-bold">Ikuti Periode Box Sebelumnya</span>
                                        <span class="text-[10px] text-slate-500" x-show="box.follow_previous" x-text="'(' + getPreviousBoxPeriodText(bIdx) + ')'"></span>
                                    </label>
                                </template>

                                <!-- Quick Preset Buttons -->
                                <div class="flex flex-wrap items-center gap-1 pt-1 font-mono text-[10px]" x-show="!(bIdx > 0 && box.follow_previous)">
                                    <span class="text-slate-400 text-[9px] mr-0.5">Preset:</span>
                                    <template x-for="preset in [1, 3, 4, 6, 12, 15, 24, 60]" :key="preset">
                                        <button 
                                            type="button" 
                                            @click="box.periode_bulan = preset" 
                                            class="px-1.5 py-0.5 rounded border transition cursor-pointer"
                                            :class="box.periode_bulan == preset 
                                                ? 'bg-amber-500 text-slate-950 border-amber-600 font-black' 
                                                : 'bg-slate-100 dark:bg-slate-900 hover:bg-amber-500/20 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800 font-medium'"
                                            x-text="preset + ' Bln'"
                                        ></button>
                                    </template>
                                </div>

                                <!-- Hidden field submitted to backend -->
                                <input 
                                    type="hidden" 
                                    :name="'boxes[' + bIdx + '][periode]'" 
                                    :value="getBoxFinalPeriod(box, bIdx)"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- REPEATER BUTIR DOKUMEN DALAM BOX INI -->
                    <div class="border border-emerald-500/30 rounded-xl p-3.5 bg-emerald-500/5 dark:bg-emerald-950/20 space-y-3">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-emerald-500/20 pb-2">
                            <div>
                                <h3 class="text-xs font-bold text-emerald-900 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="list-plus" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Rincian Butir Dokumen dalam Box #<span x-text="bIdx + 1"></span></span>
                                </h3>
                                <p class="text-[10px] text-slate-500 font-sans">
                                    Masukkan butir berkas fisik di dalam kardus ini. Periode kardus dihitung otomatis dari rentang butir di bawah.
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                                <button 
                                    type="button" 
                                    @click="addItem(bIdx)" 
                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs font-bold transition flex items-center gap-1 shadow-xs cursor-pointer"
                                    title="Tambah 1 Baris Dokumen"
                                >
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Tambah Butir</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="addBulkItems(bIdx, 5)" 
                                    class="px-2.5 py-1 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 rounded text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                                    title="Tambah 5 Baris Dokumen Sekaligus untuk Entri Cepat"
                                >
                                    <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                                    <span>Tambah 5 Baris</span>
                                </button>
                            </div>
                        </div>

                        <!-- Dense Items Table (Mendukung copas langsung dari Excel / Spreadsheet via Ctrl+V) -->
                        <div class="border border-slate-300 dark:border-slate-700 rounded-lg overflow-x-auto bg-white dark:bg-slate-950 shadow-xs" @paste="handleTablePaste($event, bIdx)">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                                        <th class="py-2 px-2.5 w-10 text-center">NO</th>
                                        <th class="py-2 px-3 min-w-[240px]">NAMA DOKUMEN / BERKAS ARSIP <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-2.5 w-36">PERIODE MULAI <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-2.5 w-36">PERIODE SELESAI <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-3 min-w-[180px]">KETERANGAN <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-2 w-16 text-center">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-mono">
                                    <template x-for="(item, iIdx) in box.items" :key="item.id">
                                        <tr class="hover:bg-emerald-500/5 transition">
                                            <td class="py-2 px-2.5 text-center font-bold text-slate-500" x-text="iIdx + 1"></td>
                                            
                                            <!-- Nama Dokumen Input / Select (Tetap Dropdown Seperti Biasa) -->
                                            <td class="py-2 px-3">
                                                <div class="flex items-center gap-1.5">
                                                    <select 
                                                        :name="'boxes[' + bIdx + '][items][' + iIdx + '][document_name]'" 
                                                        x-model="item.document_name"
                                                        x-init="$nextTick(() => { if (item.document_name && $el.value !== item.document_name) $el.value = item.document_name; })"
                                                        x-effect="$nextTick(() => { if (item.document_name && $el.value !== item.document_name) $el.value = item.document_name; })"
                                                        class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                                                    >
                                                        <option value="">-- Pilih dari Master Berkas Arsip --</option>
                                                        <template x-for="m in masterArchives" :key="m.id">
                                                            <option :value="m.name" x-text="m.code ? (m.code + ' - ' + m.name) : m.name" :selected="m.name === item.document_name"></option>
                                                        </template>
                                                        <!-- Jika nama dokumen hasil paste/duplikasi/kustom tidak ada di masterArchives, tetap tampil sebagai opsi terpilih di dropdown -->
                                                        <template x-if="item.document_name && !masterArchives.some(m => m.name === item.document_name)">
                                                            <option :value="item.document_name" x-text="item.document_name" selected></option>
                                                        </template>
                                                    </select>
                                                    @if(auth()->user()->isSuperAdmin())
                                                    <button 
                                                        type="button" 
                                                        @click="promptCustomDocName(item)" 
                                                        class="p-1.5 text-slate-400 hover:text-amber-500 rounded border border-slate-200 dark:border-slate-800 shrink-0 cursor-pointer" 
                                                        title="Ketik nama berkas khusus jika tidak ada di daftar master (Super Admin)"
                                                    >
                                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Periode Mulai (Bulan) -->
                                            <td class="py-2 px-2.5">
                                                <input 
                                                    type="month" 
                                                    :name="'boxes[' + bIdx + '][items][' + iIdx + '][period_start]'" 
                                                    x-model="item.period_start"
                                                    :max="currentMonth"
                                                    @change="validatePeriod(item)"
                                                    class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                                                >
                                            </td>

                                            <!-- Periode Selesai (Bulan) -->
                                            <td class="py-2 px-2.5">
                                                <input 
                                                    type="month" 
                                                    :name="'boxes[' + bIdx + '][items][' + iIdx + '][period_end]'" 
                                                    x-model="item.period_end"
                                                    :max="currentMonth"
                                                    @change="validatePeriod(item)"
                                                    class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                                                >
                                            </td>

                                            <!-- Keterangan Butir (Textarea Auto-expand, bisa di-Enter) -->
                                            <td class="py-2 px-3 align-top">
                                                <textarea 
                                                    :name="'boxes[' + bIdx + '][items][' + iIdx + '][notes]'" 
                                                    x-model="item.notes" 
                                                    rows="1"
                                                    placeholder="No. berkas fisik / catatan" 
                                                    @input="$el.style.height = 'auto'; $el.style.height = Math.max(34, $el.scrollHeight) + 'px'"
                                                    x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = Math.max(34, $el.scrollHeight) + 'px'; })"
                                                    class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-700 dark:text-slate-300 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition resize-none overflow-hidden leading-relaxed block shadow-2xs"
                                                    style="min-height: 34px;"
                                                ></textarea>
                                            </td>

                                            <!-- Aksi Per Baris -->
                                            <td class="py-2 px-2 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button 
                                                        type="button" 
                                                        @click="duplicateItem(bIdx, iIdx)" 
                                                        class="p-1 text-slate-400 hover:text-amber-500 rounded transition cursor-pointer" 
                                                        title="Salin baris ini"
                                                    >
                                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        @click="removeItem(bIdx, iIdx)" 
                                                        class="p-1 text-rose-400 hover:text-rose-600 rounded transition cursor-pointer" 
                                                        title="Hapus baris dokumen ini"
                                                    >
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between text-[11px] font-mono text-slate-500 pt-0.5">
                            <span>Total Butir di Box #<span x-text="bIdx + 1"></span>: <strong class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="box.items.length"></strong> Dokumen</span>
                            <span class="text-slate-400">Rentang Periode Terhitung: <strong class="text-slate-700 dark:text-slate-300" x-text="getBoxCalculatedPeriod(box).range"></strong></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- SECTION 3: UPLOAD SCAN FORMULIR & FOTO FISIK BOX ARSIP -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3 font-mono">
            <legend class="px-2 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                3. Berkas Bukti Formulir Pengajuan & Foto Fisik Box (Mencakup Semua Box)
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs"
                 @file-change="if($event.detail.name === 'scan_input_form') { hasScanForm = $event.detail.hasFile && !$event.detail.isOverLimit; }">
                <!-- 1. Scan Formulir Input / Berkas Pengajuan Fisik (Wajib untuk Verifikasi Gudang) -->
                <x-file-uploader 
                    name="scan_input_form" 
                    id="scan_input_form" 
                    label="1. Scan / Berkas Formulir Pengajuan Fisik" 
                    :required="true" 
                    badge="WAJIB UNTUK PENGAJUAN" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeKB="500"
                    :maxSizeMB="0.5"
                    helperText="Cukup unggah 1 lembar berkas formulir serah terima bertanda tangan untuk seluruh box di atas (Maksimal 500 KB, PDF/JPG/PNG)."
                />

                <!-- 2. Foto Fisik Kardus Box / Lampiran Digital (Opsional) -->
                <x-file-uploader 
                    name="file" 
                    id="file" 
                    label="2. Foto Fisik Kardus Box / Lampiran Digital" 
                    :required="false" 
                    badge="OPSIONAL" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeKB="500"
                    :maxSizeMB="0.5"
                    helperText="Foto fisik kardus / box arsip atau lampiran digital (Maksimal 500 KB, PDF/JPG/PNG). Otomatis terhubung ke seluruh box."
                />
            </div>

            <div class="p-2.5 bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/40 rounded-lg text-slate-600 dark:text-slate-400 font-sans text-xs flex items-center gap-2">
                <i data-lucide="shield-check" class="w-4 h-4 text-blue-500 shrink-0"></i>
                <p class="text-[11px] leading-relaxed">
                    <strong>Efisiensi Berkas Kolektif:</strong> Berkas formulir serah terima dan foto fisik box di atas otomatis terhubung ke seluruh <strong class="text-slate-800 dark:text-slate-200" x-text="boxes.length + ' box'"></strong> yang diajukan sekaligus.
                </p>
            </div>
        </fieldset>

        <!-- MODAL DUPLIKASI BOX -->
        <div 
            x-show="duplicateModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div 
                @click.outside="duplicateModalOpen = false" 
                class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl max-w-lg w-full p-4 space-y-4 shadow-xl font-mono text-xs"
            >
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-amber-500/20 text-amber-600 rounded">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                        </span>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">Duplikasi Box #<span x-text="duplicateSourceIndex + 1"></span></h3>
                    </div>
                    <button type="button" @click="duplicateModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
                </div>

                <div class="space-y-3 font-sans text-xs">
                    <p class="text-slate-600 dark:text-slate-300">
                        Pilih bagian yang ingin Anda salin ke Box baru:
                    </p>

                    <div class="space-y-2">
                        <!-- Mode Full -->
                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" value="full" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-100">Salin Lengkap (Wadah & Seluruh Butir Berkas)</span>
                                <p class="text-[11px] text-slate-500">Menduplikasi seluruh identitas kardus dan menyalin semua <span x-text="boxes[duplicateSourceIndex] ? boxes[duplicateSourceIndex].items.length : 0"></span> butir berkas dokumen.</p>
                            </div>
                        </label>

                        <!-- Mode Metadata Saja -->
                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" value="metadata_only" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-100">Salin Metadata Saja (Wadah Saja, Kosongkan Butir)</span>
                                <p class="text-[11px] text-slate-500">Menyalin sub-dept, jenis dokumen, dan kondisi fisik, tetapi mengosongkan butir dokumen untuk diisi baru.</p>
                            </div>
                        </label>

                        <!-- Mode Selected Items -->
                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" value="selected_items" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-100">Pilih Butir Berkas Tertentu</span>
                                <p class="text-[11px] text-slate-500">Pilih checklist berkas mana saja yang ingin disertakan ke kardus baru.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Checklist items if selected_items mode -->
                    <div x-show="duplicateMode === 'selected_items'" class="max-h-48 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded p-2 space-y-1 font-mono text-[11px]">
                        <template x-for="(it, idx) in (boxes[duplicateSourceIndex] ? boxes[duplicateSourceIndex].items : [])" :key="it.id">
                            <label class="flex items-center gap-2 p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded cursor-pointer">
                                <input type="checkbox" :value="it.id" x-model="duplicateSelectedItems" class="text-amber-500">
                                <span x-text="(idx + 1) + '. ' + (it.document_name || 'Dokumen Tanpa Nama')"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-2.5">
                    <button type="button" @click="duplicateModalOpen = false" class="px-3 py-1.5 rounded border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer">Batal</button>
                    <button type="button" @click="confirmDuplicateBox()" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Duplikasi Sekarang</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI PENGAJUAN ARSIP KE GUDANG -->
        <div 
            x-show="submitConfirmModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div 
                @click.outside="submitConfirmModalOpen = false" 
                class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl max-w-lg w-full p-4 space-y-4 shadow-xl font-mono text-xs"
            >
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded">
                            <i data-lucide="help-circle" class="w-4 h-4"></i>
                        </span>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">Konfirmasi Pengajuan ke Gudang</h3>
                    </div>
                    <button type="button" @click="submitConfirmModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
                </div>

                <div class="space-y-3 font-sans text-xs">
                    <p class="text-slate-700 dark:text-slate-200">
                        Anda akan mengajukan <strong class="text-amber-600 dark:text-amber-400 font-bold" x-text="boxes.length + ' Box Arsip'"></strong> (<span x-text="getTotalItemsCount() + ' Butir Dokumen'"></span>) untuk verifikasi penerimaan fisik oleh Admin Gudang.
                    </p>

                    <!-- Card Cek & Buka Form Pengajuan -->
                    <div class="p-3 bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-lg space-y-2.5">
                        <div class="flex items-start gap-2.5">
                            <span class="p-1.5 bg-amber-500/20 text-amber-700 dark:text-amber-300 rounded shrink-0 mt-0.5">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </span>
                            <div class="space-y-1">
                                <span class="font-bold text-slate-900 dark:text-white block">Formulir Pengajuan Fisik Standar PT Indraco</span>
                                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Pastikan lembar formulir fisik telah dicetak, ditandatangani oleh pemohon dan pimpinan, serta lembar scan formulir telah diunggah.
                                </p>
                            </div>
                        </div>

                        <div class="pt-1 flex items-center justify-end">
                            <button 
                                type="button" 
                                @click="submitConfirmModalOpen = false; openFormPengajuanModal()" 
                                class="px-3.5 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-amber-700 dark:text-amber-300 font-bold text-xs rounded border border-amber-300 dark:border-amber-700 shadow-xs flex items-center gap-1.5 transition cursor-pointer"
                                title="Buka dan cetak formulir resmi fisik sekarang"
                            >
                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                                <span>Lihat & Cetak Form Pengajuan</span>
                            </button>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500 italic">
                        Setelah diajukan, status box arsip akan berubah menjadi <strong>Pending Verification</strong> dan siap diproses oleh Admin Gudang.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-2.5 font-mono">
                    <button 
                        type="button" 
                        @click="submitConfirmModalOpen = false" 
                        class="px-3 py-1.5 rounded border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer text-xs"
                    >
                        Batal
                    </button>
                    <button 
                        type="button" 
                        @click="processSubmitFinal('submit')" 
                        :disabled="isSubmitting"
                        class="px-4 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold rounded shadow-xs flex items-center gap-1.5 cursor-pointer text-xs disabled:opacity-50"
                    >
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span x-text="isSubmitting ? 'Memproses...' : 'Ya, Ajukan ke Gudang'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-3 font-mono border-t border-slate-200 dark:border-slate-800">
            <div class="text-xs text-slate-600 dark:text-slate-400 flex items-center gap-2">
                <span class="p-1.5 bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                </span>
                <span>Ringkasan Pengajuan: <strong class="text-slate-900 dark:text-white" x-text="boxes.length + ' Box'"></strong> dengan <strong class="text-slate-900 dark:text-white" x-text="getTotalItemsCount() + ' Butir Dokumen'"></strong></span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('archives.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                    Batal
                </a>

                <!-- Simpan Sebagai Draft Sementara (Semua Box - Tanpa Batasan Wajib) -->
                <button 
                    type="button" 
                    formnovalidate
                    @click="submitDraft()"
                    :disabled="isSubmitting"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded border border-slate-400 dark:border-slate-600 shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    title="Simpan sementara semua box sebagai draft usulan (tanpa pembatasan wajib isi apapun)"
                >
                    <i data-lucide="file-clock" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span x-text="isSubmitting ? 'Menyimpan Draft...' : 'Simpan Sementara (Draft)'"></span>
                </button>

                <!-- Simpan & Ajukan Semua Box ke Gudang -->
                <button 
                    type="button" 
                    @click="submitFinal('submit')"
                    :disabled="isSubmitting"
                    :class="hasScanForm ? 'bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 border-amber-600 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-pointer'"
                    class="px-5 py-2 font-black text-xs rounded border transition flex items-center gap-1.5 disabled:opacity-50"
                    title="Simpan & Ajukan seluruh box ke PIC Gudang (Wajib menyertakan Scan Formulir)"
                >
                    <i data-lucide="send" class="w-3.5 h-3.5" :class="hasScanForm ? 'text-slate-950' : 'text-slate-400 dark:text-slate-500'"></i>
                    <span x-text="isSubmitting ? 'Memproses Pengajuan...' : ('Simpan & Ajukan ' + boxes.length + ' Box Arsip')"></span>
                </button>
            </div>
        </div>
    </form>

    <!-- ======================================================= -->
    <!-- PREVIEW & CETAK FORM PENGAJUAN DOKUMEN MODAL (PDF/PRINT) -->
    <!-- ======================================================= -->
    <div 
        x-show="formModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
        @keydown.escape.window="formModalOpen = false"
    >
        <div 
            class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-lg shadow-2xl max-w-6xl w-full max-h-[95vh] flex flex-col overflow-hidden text-slate-800 dark:text-slate-100"
            @click.outside="formModalOpen = false"
        >
            <!-- Modal Header Toolbar -->
            <div class="px-4 py-2.5 bg-slate-100 dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between shrink-0 font-mono">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                            Form Pengajuan Dokumen (Standar PT Indraco)
                        </h3>
                        <p class="text-[10px] text-slate-500 font-sans">
                            Pratinjau formulir resmi A4. Kolom tabel terisi otomatis dari data box dan dokumen.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        @click="printFormPengajuan()" 
                        class="px-3.5 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold text-xs rounded border border-amber-600 shadow-sm flex items-center gap-1.5 transition cursor-pointer"
                        title="Cetak langsung ke printer atau simpan sebagai PDF"
                    >
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Cetak Dokumen (Print / PDF)</span>
                    </button>

                    <button 
                        type="button" 
                        @click="formModalOpen = false" 
                        class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-800 rounded transition cursor-pointer"
                        title="Tutup (Esc)"
                    >
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body: Left Inputs Panel + Right Paper Preview -->
            <div class="flex-1 overflow-y-auto flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-slate-200 dark:divide-slate-800 bg-slate-100 dark:bg-slate-950">
                
                <!-- Left Column: Input Parameter & Signatures (Scrollable) -->
                <div class="w-full lg:w-80 xl:w-96 p-4 space-y-3 font-mono text-xs overflow-y-auto shrink-0 bg-white dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-1.5">
                        <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                            <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                            <span>Parameter & Approval</span>
                        </span>
                        <button 
                            type="button" 
                            @click="populateFormRows()" 
                            class="text-[10px] text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 cursor-pointer"
                            title="Sinkronkan ulang tabel dengan data box saat ini"
                        >
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                            <span>Sinkron Box</span>
                        </button>
                    </div>

                    <div class="space-y-2">
                        <!-- No. Form -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                                NO. FORM
                            </label>
                            <input 
                                type="text" 
                                x-model="formPengajuanData.no_form" 
                                placeholder="Contoh: FPD/2026/10/001 (opsional)" 
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                        </div>

                        <!-- Tanggal Pengajuan -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                                TANGGAL PENGAJUAN
                            </label>
                            <input 
                                type="date" 
                                x-model="formPengajuanData.tanggal_pengajuan" 
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                        </div>

                        <!-- Departemen / Sub Dept -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                                DEPARTEMEN / SUB DEPT.
                            </label>
                            <input 
                                type="text" 
                                x-model="formPengajuanData.dept_display" 
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                        </div>

                        <!-- Nama PIC / Pemohon -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                                NAMA PIC / PEMOHON
                            </label>
                            <input 
                                type="text" 
                                x-model="formPengajuanData.pic_pemohon" 
                                @input="if(!formPengajuanData.approval_pemohon) formPengajuanData.approval_pemohon = formPengajuanData.pic_pemohon"
                                placeholder="Ketik nama lengkap pemohon..."
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                        </div>

                        <!-- Tipe Pengajuan -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                                TIPE PENGAJUAN
                            </label>
                            <div class="grid grid-cols-2 gap-1.5 font-sans" style="font-size: 11px;">
                                <label class="flex items-center gap-1.5 p-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                    <input type="radio" value="Simpan" x-model="formPengajuanData.tipe" class="text-amber-500 focus:ring-0">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">Simpan</span>
                                </label>
                                <label class="flex items-center gap-1.5 p-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                    <input type="radio" value="Penarikan" x-model="formPengajuanData.tipe" class="text-amber-500 focus:ring-0">
                                    <span class="text-slate-600 dark:text-slate-400">Penarikan</span>
                                </label>
                                <label class="flex items-center gap-1.5 p-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                    <input type="radio" value="Pemusnahan" x-model="formPengajuanData.tipe" class="text-amber-500 focus:ring-0">
                                    <span class="text-slate-600 dark:text-slate-400">Pemusnahan</span>
                                </label>
                                <label class="flex items-center gap-1.5 p-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                    <input type="radio" value="Perpanjangan" x-model="formPengajuanData.tipe" class="text-amber-500 focus:ring-0">
                                    <span class="text-slate-600 dark:text-slate-400">Perpanjangan</span>
                                </label>
                            </div>
                        </div>

                        <!-- Masa Simpan (Bulan) -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                                MASA SIMPAN YANG DIAJUKAN (BULAN)
                            </label>
                            <input 
                                type="number" 
                                min="1" 
                                max="60" 
                                x-model="formPengajuanData.masa_simpan" 
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                            <span class="text-[9px] text-slate-400 block mt-0.5 font-sans">*Maksimal 60 bulan</span>
                        </div>

                        <!-- Catatan Fisik / Opsional -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                                CATATAN FORMULIR
                            </label>
                            <textarea 
                                x-model="formPengajuanData.catatan" 
                                rows="2" 
                                placeholder="Kondisi fisik kardus dan dokumen..."
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            ></textarea>
                        </div>

                        <!-- Signature / Approval Section -->
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1.5">
                            <span class="block text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">
                                Penandatangan (Approval):
                            </span>

                            <!-- Diajukan oleh (Pemohon) -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                    Diajukan oleh (Pemohon):
                                </label>
                                <input 
                                    type="text" 
                                    x-model="formPengajuanData.approval_pemohon" 
                                    placeholder="Ketik nama pemohon..."
                                    style="font-size: 11px;"
                                    class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                                >
                            </div>

                            <!-- Disetujui oleh (Atasan Langsung) -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                    Disetujui oleh (Atasan Langsung):
                                </label>
                                <input 
                                    type="text" 
                                    x-model="formPengajuanData.approval_atasan" 
                                    placeholder="Nama Atasan Langsung" 
                                    style="font-size: 11px;"
                                    class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                                >
                            </div>

                            <!-- Diketahui oleh (Pimpinan Departemen) -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                    Diketahui oleh (Pimpinan Departemen):
                                </label>
                                <input 
                                    type="text" 
                                    x-model="formPengajuanData.approval_pimpinan" 
                                    placeholder="Nama Pimpinan Departemen" 
                                    style="font-size: 11px;"
                                    class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                                >
                            </div>

                            <!-- Diterima oleh (Admin Gudang) -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                    Diterima oleh (Admin Gudang):
                                </label>
                                <input 
                                    type="text" 
                                    x-model="formPengajuanData.approval_admin_gudang" 
                                    style="font-size: 11px;"
                                    class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                                >
                            </div>

                            <!-- Tanggal Approval -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                    Tanggal Tanda Tangan:
                                </label>
                                <input 
                                    type="date" 
                                    x-model="formPengajuanData.approval_tgl" 
                                    style="font-size: 11px;"
                                    class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Live Printable Paper Preview -->
                <div class="flex-1 p-3 sm:p-6 overflow-y-auto bg-slate-200/80 dark:bg-slate-950 flex justify-center items-start">
                    
                    <!-- Paper Sheet Container (Exact A4 Ratio) -->
                    <div 
                        id="form-pengajuan-print-document" 
                        class="indraco-form-preview bg-white text-black shadow-2xl rounded-none border border-slate-300 w-full max-w-[210mm] min-h-[297mm] box-border"
                        style="padding: 10mm 12mm; background-color: #ffffff; color: #000000; display: block;"
                    >
                        <div>
                            <!-- Header with Logo & Document Title (User Styling) -->
                            <div style="position: relative; margin-bottom: 8px; min-height: 28px;">
                                <div style="position: absolute; left: 0; top: 0;">
                                    <img src="{{ asset('images/logo_indraco.png') }}" alt="INDRACO" style="height: 25px; object-fit: contain;">
                                </div>
                                <div style="text-align: center; padding-top: 40px; padding-bottom: 10px;">
                                    <h1 style="font-size: 16px; font-weight: bold; text-align: center; margin: 0; padding: 0; font-family: Arial, sans-serif; letter-spacing: 0.3px;">
                                        Form Pengajuan Dokumen
                                    </h1>
                                </div>
                            </div>

                            <!-- Metadata Table Box (2 rows x 4 columns) -->
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                <tbody>
                                    <tr>
                                        <td style="width: 135px; border: 1px solid #000; padding: 3px 6px; font-size: 10px; font-weight: normal;">No. Form</td>
                                        <td style="width: 35%; border: 1px solid #000; padding: 3px 6px; font-size: 10px; font-family: monospace;" x-text="formPengajuanData.no_form || ''"></td>
                                        <td style="width: 135px; border: 1px solid #000; padding: 3px 6px; font-size: 10px; font-weight: normal;">Tanggal Pengajuan</td>
                                        <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px;" x-text="formatDateDisplay(formPengajuanData.tanggal_pengajuan)"></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 135px; border: 1px solid #000; padding: 3px 6px; font-size: 10px; font-weight: normal;">Departemen/ Sub Dept.</td>
                                        <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px;" x-text="formPengajuanData.dept_display || getDeptSubDeptDisplay()"></td>
                                        <td style="width: 135px; border: 1px solid #000; padding: 3px 6px; font-size: 10px; font-weight: normal;">Nama PIC/ Pemohon</td>
                                        <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px;" x-text="formPengajuanData.pic_pemohon || ''"></td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Tipe Pengajuan Checkbox Row -->
                            <div style="font-size: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px;">
                                <span style="font-weight: normal;">Tipe Pengajuan :</span>
                                <div style="display: flex; align-items: center; gap: 24px;">
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="display: inline-block; width: 11px; height: 11px; border: 1px solid #000; font-size: 8.5px; line-height: 10px; text-align: center; font-weight: bold;" x-text="formPengajuanData.tipe === 'Simpan' ? '✓' : ''"></span>
                                        <span>Simpan</span>
                                    </span>
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="display: inline-block; width: 11px; height: 11px; border: 1px solid #000; font-size: 8.5px; line-height: 10px; text-align: center; font-weight: bold;" x-text="formPengajuanData.tipe === 'Penarikan' ? '✓' : ''"></span>
                                        <span>Penarikan</span>
                                    </span>
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="display: inline-block; width: 11px; height: 11px; border: 1px solid #000; font-size: 8.5px; line-height: 10px; text-align: center; font-weight: bold;" x-text="formPengajuanData.tipe === 'Pemusnahan' ? '✓' : ''"></span>
                                        <span>Pemusnahan</span>
                                    </span>
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="display: inline-block; width: 11px; height: 11px; border: 1px solid #000; font-size: 8.5px; line-height: 10px; text-align: center; font-weight: bold;" x-text="formPengajuanData.tipe === 'Perpanjangan' ? '✓' : ''"></span>
                                        <span>Perpanjangan</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Main Table: Daftar Box & Dokumen (12+ Rows) -->
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                <thead>
                                    <tr style="text-align: center; font-weight: bold;">
                                        <th style="border: 1px solid #000; width: 34px; padding: 3px 2px; font-size: 9.5px; text-align: center; line-height: 1.2;">No.</th>
                                        <th style="border: 1px solid #000; padding: 3px 6px; font-size: 9.5px; text-align: center; line-height: 1.2;">
                                            Nama Kardus,<br>Nama/ Kode Dokumen
                                        </th>
                                        <th style="border: 1px solid #000; width: 115px; padding: 3px 4px; font-size: 9.5px; text-align: center; line-height: 1.2;">
                                            Label Kardus
                                        </th>
                                        <th style="border: 1px solid #000; width: 92px; padding: 3px 4px; font-size: 9.5px; text-align: center; line-height: 1.2;">
                                            Periode Awal<br>(MM-YYYY)
                                        </th>
                                        <th style="border: 1px solid #000; width: 92px; padding: 3px 4px; font-size: 9.5px; text-align: center; line-height: 1.2;">
                                            Periode Akhir<br>(MM-YYYY)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in formPengajuanRows" :key="row.no">
                                        <tr style="height: 22px;">
                                            <td style="border: 1px solid #000; padding: 2px 2px; text-align: center; font-size: 9.5px;" x-text="row.no"></td>
                                            <td style="border: 1px solid #000; padding: 2px 6px; text-align: left; font-size: 9.5px; outline: none;" contenteditable="true" @blur="row.title = $el.innerText" x-text="row.title"></td>
                                            <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-size: 9.5px; outline: none;" contenteditable="true" @blur="row.label_kardus = $el.innerText" x-text="row.label_kardus"></td>
                                            <td style="border: 1px solid #000; padding: 2px 2px; text-align: center; font-size: 9px; font-family: monospace; outline: none;" contenteditable="true" @blur="row.period_start = $el.innerText" x-text="row.period_start"></td>
                                            <td style="border: 1px solid #000; padding: 2px 2px; text-align: center; font-size: 9px; font-family: monospace; outline: none;" contenteditable="true" @blur="row.period_end = $el.innerText" x-text="row.period_end"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- BOTTOM FOOTER SECTION (Anchored to Bottom of A4 Page) -->
                        <div style="margin-bottom: 20px">
                            <!-- Masa Simpan & Catatan Section -->
                            <div style="font-size: 9.5px; margin-bottom: 20px; line-height: 1.35;">
                                <div style="margin-bottom: 20px;">
                                    <span>Masa Simpan yang diajukan :</span>
                                    <span style="display: inline-block; min-width: 24px; text-align: center; font-weight: bold; border-bottom: 1px dotted #000; margin: 0 4px;" x-text="formPengajuanData.masa_simpan"></span>
                                    <span>Bulan (*maksimal 60 bulan)</span>
                                </div>
                                <div>
                                    <div>Catatan (misal kondisi fisik kardus dan dokumen/ *optional) :</div>
                                    <div 
                                        style="border-bottom: 1px dotted #000; text-decoration: underline dotted #000; text-underline-offset: 3px; min-height: 16px; font-size: 9px; line-height: 16px; padding-top: 1px; word-break: break-word; outline: none;" 
                                        contenteditable="true" 
                                        @blur="formPengajuanData.catatan = $el.innerText" 
                                        x-text="formPengajuanData.catatan"
                                    ></div>
                                    <div 
                                        x-show="!formPengajuanData.catatan || !formPengajuanData.catatan.trim()" 
                                        style="border-bottom: 1px dotted #000; min-height: 16px;"
                                    ></div>
                                </div>
                            </div>

                            <!-- Approval Signatures Table (4 Columns) -->
                            <table style="width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 20px;">
                                <tbody>
                                    <tr>
                                        <!-- Diajukan oleh (Pemohon) -->
                                        <td style="width: 25%; border: 1px solid #000; vertical-align: top; height: 80px; padding: 4px 6px;">
                                            <div style="font-size: 9.5px; text-align: left;">Diajukan oleh,</div>
                                            <div style="height: 50px;"></div>
                                            <div style="font-size: 8.5px; text-align: center; line-height: 1.2;">
                                                (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="formPengajuanData.approval_pemohon = $el.innerText" x-text="formPengajuanData.approval_pemohon || ''"></span>&nbsp;)
                                            </div>
                                            <div style="font-size: 8.5px; text-align: left; margin-top: 3px;">
                                                Tgl. <span x-text="formatDateDisplay(formPengajuanData.approval_tgl)"></span>
                                            </div>
                                        </td>
                                        <!-- Disetujui oleh (Atasan Langsung) -->
                                        <td style="width: 25%; border: 1px solid #000; vertical-align: top; height: 80px; padding: 4px 6px;">
                                            <div style="font-size: 9.5px; text-align: left;">Disetujui oleh,</div>
                                            <div style="height: 50px;"></div>
                                            <div style="font-size: 8.5px; text-align: center; line-height: 1.2;">
                                                (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="formPengajuanData.approval_atasan = $el.innerText" x-text="formPengajuanData.approval_atasan || ''"></span>&nbsp;)
                                            </div>
                                            <div style="font-size: 8.5px; text-align: left; margin-top: 3px;">
                                                Tgl. <span x-text="formatDateDisplay(formPengajuanData.approval_tgl)"></span>
                                            </div>
                                        </td>
                                        <!-- Diketahui oleh (Pimpinan Departemen) -->
                                        <td style="width: 25%; border: 1px solid #000; vertical-align: top; height: 80px; padding: 4px 6px;">
                                            <div style="font-size: 9.5px; text-align: left;">Diketahui oleh,</div>
                                            <div style="height: 50px;"></div>
                                            <div style="font-size: 8.5px; text-align: center; line-height: 1.2;">
                                                (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="formPengajuanData.approval_pimpinan = $el.innerText" x-text="formPengajuanData.approval_pimpinan || ''"></span>&nbsp;)
                                            </div>
                                            <div style="font-size: 8.5px; text-align: left; margin-top: 3px;">
                                                Tgl. <span x-text="formatDateDisplay(formPengajuanData.approval_tgl)"></span>
                                            </div>
                                        </td>
                                        <!-- Diterima oleh (Admin Gudang) -->
                                        <td style="width: 25%; border: 1px solid #000; vertical-align: top; height: 80px; padding: 4px 6px;">
                                            <div style="font-size: 9.5px; text-align: left;">Diterima oleh,</div>
                                            <div style="height: 50px;"></div>
                                            <div style="font-size: 8.5px; text-align: center; line-height: 1.2;">
                                                (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="formPengajuanData.approval_admin_gudang = $el.innerText" x-text="formPengajuanData.approval_admin_gudang || ''"></span>&nbsp;)
                                            </div>
                                            <div style="font-size: 8.5px; text-align: left; margin-top: 3px;">
                                                Tgl. <span x-text="formatDateDisplay(formPengajuanData.approval_tgl)"></span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Petunjuk Pengisian (Verbatim from Official Form) -->
                            <div style="font-size: 8.5px; line-height: 1.35; color: #000; margin-top: 6px;">
                                <div style="font-weight: normal; margin-bottom: 2px;">Petunjuk Pengisian :</div>
                                <ol style="margin: 0; padding-left: 14px; list-style-type: decimal;">
                                    <li>Centang pada tipe pengajuan yang tersedia (Simpan/ Penarikan/ Pemusnahan/ Perpanjangan).</li>
                                    <li>Isi Nama Kardus, Nama/ Kode Dokumen dan Periode Dokumen saat pengajuan Simpan (kosongkan label kardus).</li>
                                    <li>Isi Label Kardus saat pengajuan Penarikan/ Pemusnahan/ Perpanjangan (kosongkan Nama Kardus, Nama/ Kode Dokumen dan Periode Dokumen).</li>
                                    <li>Approval =
                                        <ul style="margin: 0; padding-left: 14px; list-style-type: none;">
                                            <li>✸ Diajukan oleh Pemohon</li>
                                            <li>✸ Disetujui oleh Atasan Langsung</li>
                                            <li>✸ Diketahui oleh Pimpinan Departemen</li>
                                            <li>✸ Diterima oleh Admin Gudang</li>
                                        </ul>
                                    </li>
                                </ol>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Print Styles & Fixed Scoped Preview CSS -->
    <style>
    /* Explicitly allow mouse text blocking / highlighting & copy-paste */
    html,
    body,
    .select-text,
    .select-text *,
    input,
    textarea,
    select,
    [contenteditable="true"],
    .indraco-form-preview,
    .indraco-form-preview * {
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
        user-select: text !important;
    }

    .indraco-form-preview,
    .indraco-form-preview * {
        cursor: text;
    }

    ::selection {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }

    ::-moz-selection {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }

    .indraco-form-preview {
        font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
        font-size: 9.5px !important;
        line-height: 1.3 !important;
        color: #000000 !important;
        background-color: #ffffff !important;
    }
    .indraco-form-preview * {
        box-sizing: border-box !important;
        font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
    }
    @media print {
        body * {
            visibility: hidden !important;
        }
        #form-pengajuan-print-document, 
        #form-pengajuan-print-document * {
            visibility: visible !important;
        }
        #form-pengajuan-print-document {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-height: 0 !important;
            height: auto !important;
            margin: 0 !important;
            padding: 8mm 12mm !important;
            box-shadow: none !important;
            border: none !important;
            background: #ffffff !important;
            color: #000000 !important;
            z-index: 9999999 !important;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
            font-size: 9.5px !important;
            display: block !important;
        }
        @page {
            size: A4 portrait;
            margin: 0;
        }
    }
    </style>
</div>

@push('scripts')
<script>
function archiveCreateApp() {
    const initialMonth = '{{ date('Y-m') }}';
    const sub2Months = '{{ date('Y-m', strtotime('-2 months')) }}';
    const sub1Month = '{{ date('Y-m', strtotime('-1 month')) }}';

    const departmentsData = @json($departments);
    const initialDeptId = '{{ old('department_id', auth()->user()->isPicDept() ? auth()->user()->department_id : ($departments->first()->id ?? '')) }}';
    const initialDept = departmentsData.find(d => String(d.id) === String(initialDeptId)) || departmentsData[0] || null;
    const initialSubDepts = (initialDept && initialDept.sub_departments) ? initialDept.sub_departments : [];
    const initialMaster = (initialDept && initialDept.master_archives) ? initialDept.master_archives : [];

    return {
        isSubmitting: false,
        hasScanForm: false,
        departments: departmentsData,
        selectedCompany: '{{ old('company_name', 'PT INDRACO GLOBAL INDONESIA') }}',
        selectedDeptId: initialDept ? initialDept.id : initialDeptId,
        selectedSubDeptId: '{{ old('sub_department_id', '') }}',
        subDepartments: initialSubDepts,
        masterArchives: initialMaster,
        currentMonth: initialMonth,
        tglPenyerahan: '{{ old('tgl_penyerahan', date('Y-m-d')) }}',

        // Multi-Box Workstation State
        activeBoxIndex: 0,
        boxes: [
            {
                id: 1,
                title: @json(old('title', '')),
                sub_department_id: '{{ old('sub_department_id', '') }}',
                document_type: @json(old('document_type', '')),
                periode_bulan: '{{ old('periode_bulan', '1') }}',
                follow_previous: false,
                physical_condition: 'Baik / Box Karton Standar TB 30g',
                similarityInfo: null,
                items: [
                    { id: 1, document_name: '', period_start: sub2Months, period_end: initialMonth, notes: '' },
                    { id: 2, document_name: '', period_start: sub1Month, period_end: initialMonth, notes: '' },
                    { id: 3, document_name: '', period_start: initialMonth, period_end: initialMonth, notes: '' }
                ]
            }
        ],

        // Duplicate Modal State
        duplicateModalOpen: false,
        submitConfirmModalOpen: false,
        duplicateSourceIndex: 0,
        duplicateMode: 'full', // 'full' | 'metadata_only' | 'selected_items'
        duplicateSelectedItems: [],

        // Form Pengajuan Dokumen Modal State
        formModalOpen: false,
        formPengajuanData: {
            no_form: '',
            tanggal_pengajuan: '{{ date('Y-m-d') }}',
            dept_display: '',
            pic_pemohon: '',
            tipe: 'Simpan',
            masa_simpan: 1,
            catatan: 'Kondisi fisik kardus standar TB 30g dalam keadaan baik.',
            approval_pemohon: '',
            approval_atasan: '',
            approval_pimpinan: '',
            approval_admin_gudang: 'Admin Gudang',
            approval_tgl: '{{ date('Y-m-d') }}'
        },
        formPengajuanRows: [],

        // Similarity check debounce timer
        similarityTimer: null,

        init() {
            if (!this.masterArchives || this.masterArchives.length === 0) {
                this.updateSubDepartments();
                this.updateMasterArchives();
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        getTotalItemsCount() {
            return this.boxes.reduce((sum, b) => sum + (b.items ? b.items.length : 0), 0);
        },

        addBox() {
            const nextNum = this.boxes.length + 1;
            const defaultDoc = this.masterArchives.length > 0 ? this.masterArchives[0].name : '';
            // Auto inherit sub-dept from active box if set
            const prevBox = this.boxes[this.activeBoxIndex] || this.boxes[this.boxes.length - 1];
            const inheritedSubDept = prevBox ? prevBox.sub_department_id : (this.selectedSubDeptId || '');
            const inheritedPeriod = prevBox && prevBox.periode_bulan ? prevBox.periode_bulan : '1';

            this.boxes.push({
                id: Date.now() + Math.random(),
                title: '',
                sub_department_id: inheritedSubDept,
                document_type: prevBox ? prevBox.document_type : '',
                periode_bulan: inheritedPeriod,
                follow_previous: true,
                physical_condition: 'Baik / Box Karton Standar TB 30g',
                similarityInfo: null,
                items: [
                    { id: Date.now() + 1, document_name: defaultDoc, period_start: this.currentMonth, period_end: this.currentMonth, notes: '' }
                ]
            });
            this.activeBoxIndex = this.boxes.length - 1;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        promptCustomDocName(item) {
            const current = item.document_name || '';
            const val = prompt('Masukkan nama berkas dokumen kustom:', current);
            if (val !== null && val.trim()) {
                item.document_name = val.trim();
            }
        },

        getBoxFinalPeriod(box, bIdx) {
            if (bIdx > 0 && box.follow_previous) {
                const prev = this.boxes[bIdx - 1];
                const prevVal = (prev && prev.periode_bulan) ? prev.periode_bulan : '1';
                return prevVal ? (prevVal + ' Bulan') : '1 Bulan';
            }
            const val = box.periode_bulan || '1';
            return val ? (val + ' Bulan') : '1 Bulan';
        },

        getPreviousBoxPeriodText(bIdx) {
            if (bIdx <= 0) return '1 Bulan';
            const prev = this.boxes[bIdx - 1];
            return (prev && prev.periode_bulan) ? (prev.periode_bulan + ' Bulan') : '1 Bulan';
        },

        openDuplicateModal(index) {
            this.duplicateSourceIndex = index;
            this.duplicateMode = 'full';
            const src = this.boxes[index];
            this.duplicateSelectedItems = src && src.items ? src.items.map(it => it.id) : [];
            this.duplicateModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        confirmDuplicateBox() {
            const source = this.boxes[this.duplicateSourceIndex];
            if (!source) return;

            let clonedItems = [];
            if (this.duplicateMode === 'full') {
                clonedItems = source.items.map((it, idx) => ({
                    id: Date.now() + idx + Math.random(),
                    document_name: it.document_name,
                    period_start: it.period_start,
                    period_end: it.period_end,
                    notes: it.notes || ''
                }));
            } else if (this.duplicateMode === 'selected_items') {
                const selectedSet = new Set(this.duplicateSelectedItems);
                clonedItems = source.items
                    .filter(it => selectedSet.has(it.id))
                    .map((it, idx) => ({
                        id: Date.now() + idx + Math.random(),
                        document_name: it.document_name,
                        period_start: it.period_start,
                        period_end: it.period_end,
                        notes: it.notes || ''
                    }));
            }

            if (clonedItems.length === 0) {
                const defaultDoc = this.masterArchives.length > 0 ? this.masterArchives[0].name : '';
                clonedItems = [
                    { id: Date.now(), document_name: defaultDoc, period_start: this.currentMonth, period_end: this.currentMonth, notes: '' }
                ];
            }

            const newTitle = source.title ? (source.title + ' (Salinan)') : '';
            this.boxes.push({
                id: Date.now() + Math.random(),
                title: newTitle,
                sub_department_id: source.sub_department_id,
                document_type: source.document_type,
                periode_bulan: source.periode_bulan || '1',
                follow_previous: true,
                physical_condition: source.physical_condition,
                items: clonedItems,
                similarityInfo: null
            });

            this.activeBoxIndex = this.boxes.length - 1;
            this.duplicateModalOpen = false;
            this.checkSimilarity(this.activeBoxIndex);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async removeBox(index) {
            if (this.boxes.length <= 1) {
                if (window.showConfirmModal) {
                    await window.showConfirmModal({
                        title: 'Perhatian',
                        message: 'Pengajuan minimal harus memiliki 1 box kardus arsip.',
                        type: 'warning',
                        confirmText: 'Mengerti'
                    });
                }
                return;
            }
            if (window.showConfirmModal) {
                const ok = await window.showConfirmModal({
                    title: 'Hapus Box Arsip',
                    message: `Apakah Anda yakin ingin menghapus Box #${index + 1}? Seluruh butir berkas di dalam box ini akan dihapus.`,
                    type: 'danger',
                    confirmText: 'Ya, Hapus Box'
                });
                if (!ok) return;
            }
            this.boxes.splice(index, 1);
            if (this.activeBoxIndex >= this.boxes.length) {
                this.activeBoxIndex = this.boxes.length - 1;
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        handleTablePaste(e, boxIndex) {
            const clipboardData = (e.clipboardData || window.clipboardData);
            if (!clipboardData) return;
            const text = clipboardData.getData('text');
            if (!text || (!text.includes('\t') && !text.includes('\n'))) {
                return; // Normal paste inside a single input
            }

            // Prevent tab/newline raw text dumping into one input
            e.preventDefault();
            const box = this.boxes[boxIndex];
            if (!box) return;

            const lines = text.trim().split(/\r?\n/);
            const newItems = [];

            for (let line of lines) {
                let trimmed = line.trim();
                if (!trimmed) continue;

                let cols = trimmed.split('\t');
                if (cols.length === 1 && trimmed.includes(';')) cols = trimmed.split(';');
                else if (cols.length === 1 && trimmed.includes(',')) cols = trimmed.split(',');

                cols = cols.map(c => c.trim().replace(/^["']|["']$/g, ''));

                const firstColLower = (cols[0] || '').toLowerCase();
                if (firstColLower === 'nama dokumen' || firstColLower === 'judul dokumen' || firstColLower === 'nama' || firstColLower === 'no') {
                    continue;
                }

                let docName = cols[0] || '';
                let pStart = cols[1] || this.currentMonth;
                let pEnd = cols[2] || pStart;
                let notes = cols[3] || '';

                pStart = pStart.replace('/', '-').substring(0, 7);
                pEnd = pEnd.replace('/', '-').substring(0, 7);
                if (!/^\d{4}-\d{2}$/.test(pStart)) pStart = this.currentMonth;
                if (!/^\d{4}-\d{2}$/.test(pEnd)) pEnd = pStart;

                if (docName) {
                    newItems.push({
                        id: Date.now() + Math.random(),
                        document_name: docName,
                        period_start: pStart,
                        period_end: pEnd,
                        notes: notes
                    });
                }
            }

            if (newItems.length > 0) {
                if (box.items.length === 1 && !box.items[0].document_name) {
                    box.items = newItems;
                } else {
                    box.items.push(...newItems);
                }

                if (window.showToast) {
                    window.showToast(`📋 Berhasil menempel ${newItems.length} butir dokumen dari spreadsheet ke Box #${boxIndex + 1}.`, 'success');
                }
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },

        addItem(boxIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            const defaultDoc = this.masterArchives.length > 0 ? this.masterArchives[0].name : '';
            
            // Auto-fill from previous item if present
            const prevItem = box.items.length > 0 ? box.items[box.items.length - 1] : null;
            const pStart = prevItem ? prevItem.period_start : this.currentMonth;
            const pEnd = prevItem ? prevItem.period_end : this.currentMonth;
            const pNotes = prevItem ? prevItem.notes : '';

            box.items.push({
                id: Date.now() + Math.random(),
                document_name: defaultDoc,
                period_start: pStart,
                period_end: pEnd,
                notes: pNotes
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        handleEnterOnItem(boxIndex, itemIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            if (itemIndex === box.items.length - 1) {
                this.addItem(boxIndex);
            }
        },

        applyPreviousBoxPreset(boxIndex) {
            if (boxIndex <= 0) return;
            const prevBox = this.boxes[boxIndex - 1];
            const currentBox = this.boxes[boxIndex];
            if (!prevBox || !currentBox) return;

            currentBox.sub_department_id = prevBox.sub_department_id;
            currentBox.document_type = prevBox.document_type;
            currentBox.physical_condition = prevBox.physical_condition;
            currentBox.periode_bulan = prevBox.periode_bulan;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        addBulkItems(boxIndex, count = 5) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            const defaultDoc = this.masterArchives.length > 0 ? this.masterArchives[0].name : '';
            
            const prevItem = box.items.length > 0 ? box.items[box.items.length - 1] : null;
            const pStart = prevItem ? prevItem.period_start : this.currentMonth;
            const pEnd = prevItem ? prevItem.period_end : this.currentMonth;
            const pNotes = prevItem ? prevItem.notes : '';

            for (let i = 0; i < count; i++) {
                box.items.push({
                    id: Date.now() + i + Math.random(),
                    document_name: defaultDoc,
                    period_start: pStart,
                    period_end: pEnd,
                    notes: pNotes
                });
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        duplicateItem(boxIndex, itemIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            const source = box.items[itemIndex];
            box.items.splice(itemIndex + 1, 0, {
                id: Date.now() + Math.random(),
                document_name: source.document_name,
                period_start: source.period_start,
                period_end: source.period_end,
                notes: source.notes || ''
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        removeItem(boxIndex, itemIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            if (box.items.length <= 1) {
                alert('Box minimal harus memiliki 1 butir dokumen.');
                return;
            }
            box.items.splice(itemIndex, 1);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        checkSimilarity(boxIndex) {
            clearTimeout(this.similarityTimer);
            this.similarityTimer = setTimeout(() => {
                const box = this.boxes[boxIndex];
                if (!box || !box.title || !box.title.trim()) {
                    if (box) box.similarityInfo = null;
                    return;
                }

                const currentTitle = box.title.trim().toLowerCase();

                // 1. Local In-Batch Check (against other boxes in the form)
                let localMatch = null;
                for (let i = 0; i < this.boxes.length; i++) {
                    if (i === boxIndex) continue;
                    const otherTitle = (this.boxes[i].title || '').trim().toLowerCase();
                    if (!otherTitle) continue;

                    if (currentTitle === otherTitle) {
                        localMatch = {
                            match_type: 'identical',
                            source: 'batch',
                            box_num: i + 1,
                            title: this.boxes[i].title,
                            similarity_percentage: 100
                        };
                        break;
                    }
                }

                if (localMatch) {
                    box.similarityInfo = {
                        has_similarity: true,
                        match_type: 'identical',
                        source: 'batch',
                        similarity_percentage: 100,
                        message: `Judul identik 100% dengan Box #${localMatch.box_num} di form pengajuan ini.`,
                        matched_title: localMatch.title
                    };
                    return;
                }

                // 2. Remote Database Scoped Check
                const deptId = this.selectedDeptId;
                if (!deptId) return;

                fetch('{{ route('api.archives.check_similarity') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        department_id: deptId,
                        title: box.title
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.has_similarity) {
                        box.similarityInfo = {
                            has_similarity: true,
                            match_type: data.match_type,
                            source: 'database',
                            similarity_percentage: data.similarity_percentage,
                            message: data.match_type === 'identical'
                                ? `Judul identik 100% dengan arsip tersimpan [${data.matched_archive.box_number || 'Box'}]: ${data.matched_archive.title}`
                                : `Kemiripan ${data.similarity_percentage}% dengan arsip tersimpan [${data.matched_archive.box_number || 'Box'}]: ${data.matched_archive.title}`,
                            matched_archive: data.matched_archive,
                            diff: data.diff
                        };
                    } else {
                        box.similarityInfo = null;
                    }
                })
                .catch(err => {
                    console.error('Similarity check error:', err);
                });
            }, 400);
        },

        getBoxCalculatedPeriod(box) {
            if (!box || !box.items || box.items.length === 0) {
                return { label: '1 Bulan', range: '-', months: 1 };
            }

            let min = null;
            let max = null;

            box.items.forEach(it => {
                if (it.period_start) {
                    if (!min || it.period_start < min) min = it.period_start;
                }
                if (it.period_end) {
                    if (!max || it.period_end > max) max = it.period_end;
                }
            });

            if (!min || !max) {
                return { label: '1 Bulan', range: this.currentMonth, months: 1 };
            }

            const [y1, m1] = min.split('-').map(Number);
            const [y2, m2] = max.split('-').map(Number);
            let months = (y2 - y1) * 12 + (m2 - m1) + 1;

            if (months > 60) months = 60;
            if (months < 1) months = 1;

            return {
                label: `${months} Bulan`,
                range: min === max ? min : `${min} s/d ${max}`,
                months: months
            };
        },

        validatePeriod(item) {
            if (item.period_end && item.period_end > this.currentMonth) {
                item.period_end = this.currentMonth;
            }
            if (item.period_start && item.period_start > this.currentMonth) {
                item.period_start = this.currentMonth;
            }
            if (item.period_start && item.period_end && item.period_start > item.period_end) {
                item.period_start = item.period_end;
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

        updateMasterArchives() {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            let allArchives = [];
            if (dept && dept.master_archives && dept.master_archives.length > 0) {
                allArchives = dept.master_archives;
                this.filterAndSetArchives(allArchives);
            } else if (this.selectedDeptId) {
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

            // Set default document_name for initial items if empty
            if (this.masterArchives.length > 0) {
                this.boxes.forEach(box => {
                    box.items.forEach((it, idx) => {
                        if (!it.document_name) {
                            it.document_name = this.masterArchives[idx % this.masterArchives.length].name;
                        }
                    });
                });
            }
        },

        submitDraft() {
            if (this.isSubmitting) return;

            this.isSubmitting = true;
            const form = document.getElementById('archiveCreateForm');
            if (form) {
                // Hilangkan seluruh pembatasan HTML5 validation saat simpan draft
                form.noValidate = true;

                let actionInput = form.querySelector('input[name="submit_action"]');
                if (!actionInput) {
                    actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'submit_action';
                    form.appendChild(actionInput);
                }
                actionInput.value = 'draft';
                form.submit();
            }
        },

        submitFinal(action) {
            if (this.isSubmitting) return;

            if (!this.hasScanForm) {
                alert('⚠️ PERHATIAN: BERKAS BELUM LENGKAP!\n\n"Scan Formulir Input" wajib diunggah sebelum mengajukan verifikasi box arsip ke Gudang.\n\nSilakan unggah Scan Formulir Input pada Bagian 3, atau klik tombol "Simpan Sementara (Draft)" jika ingin menyimpan data tanpa pengajuan.');
                const fileInput = document.getElementById('scan_input_form');
                if (fileInput) {
                    fileInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    fileInput.focus();
                }
                return;
            }

            // Check that all boxes have titles
            for (let i = 0; i < this.boxes.length; i++) {
                if (!this.boxes[i].title || !this.boxes[i].title.trim()) {
                    alert(`⚠️ Judul / Label Kardus untuk Box #${i + 1} belum diisi. Silakan lengkapi judul kardus.`);
                    this.activeBoxIndex = i;
                    return;
                }
            }

            this.submitConfirmModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        processSubmitFinal(action = 'submit') {
            this.isSubmitting = true;
            this.submitConfirmModalOpen = false;
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
        },

        openFormPengajuanModal() {
            this.formPengajuanData.dept_display = this.getDeptSubDeptDisplay();
            let maxPeriod = 1;
            this.boxes.forEach(b => {
                const p = parseInt(b.periode_bulan, 10);
                if (!isNaN(p) && p > maxPeriod) maxPeriod = p;
            });
            this.formPengajuanData.masa_simpan = maxPeriod;
            this.formPengajuanData.tanggal_pengajuan = this.tglPenyerahan || '{{ date('Y-m-d') }}';
            this.formPengajuanData.approval_tgl = this.tglPenyerahan || '{{ date('Y-m-d') }}';
            this.populateFormRows();
            this.formModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        populateFormRows() {
            const rows = [];
            const count = Math.max(12, this.boxes.length);
            for (let i = 0; i < count; i++) {
                if (i < this.boxes.length) {
                    const b = this.boxes[i];
                    rows.push({
                        no: i + 1,
                        title: b.title || '',
                        label_kardus: '', // Kosongkan khusus untuk pengajuan simpan
                        period_start: this.getBoxPeriodStartFormatted(b),
                        period_end: this.getBoxPeriodEndFormatted(b)
                    });
                } else {
                    rows.push({
                        no: i + 1,
                        title: '',
                        label_kardus: '',
                        period_start: '',
                        period_end: ''
                    });
                }
            }
            this.formPengajuanRows = rows;
        },

        getDeptSubDeptDisplay() {
            let deptName = '';
            const dept = this.departments.find(d => String(d.id) === String(this.selectedDeptId));
            if (dept) {
                deptName = dept.name || dept.code || '';
            } else {
                deptName = @json(auth()->user()->department ? auth()->user()->department->name : '');
            }

            let subDeptName = '';
            if (this.selectedSubDeptId && this.subDepartments) {
                const sub = this.subDepartments.find(s => String(s.id) === String(this.selectedSubDeptId));
                if (sub) subDeptName = sub.name;
            } else if (this.boxes.length > 0 && this.boxes[0].sub_department_id && this.subDepartments) {
                const sub = this.subDepartments.find(s => String(s.id) === String(this.boxes[0].sub_department_id));
                if (sub) subDeptName = sub.name;
            }

            if (deptName && subDeptName) {
                return `${deptName} / ${subDeptName}`;
            }
            return deptName || '-';
        },

        getBoxPeriodStartFormatted(box) {
            if (!box || !box.items || box.items.length === 0) return '';
            const starts = box.items.map(it => it.period_start).filter(Boolean).sort();
            if (starts.length === 0) return '';
            const minStart = starts[0];
            const parts = minStart.split('-');
            return parts.length === 2 ? `${parts[1]}-${parts[0]}` : minStart;
        },

        getBoxPeriodEndFormatted(box) {
            if (!box || !box.items || box.items.length === 0) return '';
            const ends = box.items.map(it => it.period_end).filter(Boolean).sort();
            if (ends.length === 0) return '';
            const maxEnd = ends[ends.length - 1];
            const parts = maxEnd.split('-');
            return parts.length === 2 ? `${parts[1]}-${parts[0]}` : maxEnd;
        },

        formatDateDisplay(dateStr) {
            if (!dateStr) return '';
            if (typeof dateStr !== 'string') return '';
            try {
                const parts = dateStr.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
            } catch (e) {}
            return dateStr;
        },

        formatDateIndo(dateStr) {
            if (!dateStr) return '';
            if (typeof dateStr !== 'string') return '';
            try {
                const parts = dateStr.split('-');
                if (parts.length === 3) {
                    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    const day = parseInt(parts[2], 10);
                    const mIdx = parseInt(parts[1], 10) - 1;
                    const year = parts[0];
                    if (!isNaN(day) && mIdx >= 0 && mIdx < 12) {
                        return `${day} ${months[mIdx]} ${year}`;
                    }
                }
            } catch (e) {}
            return dateStr;
        },

        printFormPengajuan() {
            window.print();
        }
    };
}
</script>
@endpush
@endsection
