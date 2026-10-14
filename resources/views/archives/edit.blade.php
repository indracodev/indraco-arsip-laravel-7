@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', ($archive->status === 'draft' ? 'Edit Draft Pengajuan Box Arsip - ' : 'Edit Berkas Arsip - ') . $archive->title)

@section('content')
<div class="max-w-6xl mx-auto space-y-3 font-sans pb-10 select-text" x-data="archiveEditApp()">

    <!-- FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded shrink-0">
                <i data-lucide="edit-3" class="w-4 h-4"></i>
            </span>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate">
                        {{ $archive->status === 'draft' ? 'Edit Draft Pengajuan Box Arsip (TB 30g)' : 'Edit Data Berkas Arsip (' . ($archive->box_number ?: 'Standar Box TB 30g') . ')' }}
                    </h1>
                    <span class="px-1.5 py-0.5 bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 text-xs font-bold rounded uppercase border border-slate-300 dark:border-slate-700 whitespace-nowrap shrink-0">Status: {{ $archive->status_label }}</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">Pencatatan Master Box & Multi-Item Butir Dokumen Arsip • Standar Box TB 30g</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-end shrink-0">
            <!-- Counter Chip -->
            <div class="px-2.5 py-1 bg-amber-500/10 border border-amber-500/30 rounded text-xs font-mono font-bold text-amber-700 dark:text-amber-300 flex items-center gap-1.5 whitespace-nowrap shrink-0">
                <i data-lucide="boxes" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                <span x-text="boxes.length + ' Box'"></span>
                <span>•</span>
                <span x-text="getTotalItemsCount() + ' Berkas'"></span>
            </div>

            <!-- Tombol Cetak Form Pengajuan Dokumen -->
            <button 
                type="button" 
                @click="openFormPengajuanModal()" 
                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/50 dark:hover:bg-amber-900/50 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/60 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm whitespace-nowrap shrink-0 cursor-pointer"
                title="Buka Pratinjau & Cetak Form Pengajuan Dokumen (Standar PDF)"
            >
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0"></i>
                <span>Form Pengajuan</span>
            </button>

            <a href="{{ route('archives.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm whitespace-nowrap shrink-0">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                <span>Kembali (Esc)</span>
            </a>
        </div>
    </div>

    <!-- MAIN FORM WINDOW CARD -->
    <form id="archiveEditForm" action="{{ route('archives.update', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @method('PUT')
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <!-- SECTION 1: UNIT & DEPARTEMEN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="building-2" class="w-3.5 h-3.5 text-purple-500"></i>
                1. Identitas Unit Kerja & Penyerahan Dokumen
            </legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Company Entity -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PERUSAHAAN ENTITAS <span class="text-rose-500">*</span>
                    </label>
                    <input type="hidden" name="company_name" :value="selectedCompany">
                    <button 
                        type="button" 
                        disabled
                        class="w-full px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left font-bold text-slate-600 dark:text-slate-300 cursor-not-allowed select-none transition flex items-center justify-between opacity-90"
                    >
                        <span class="truncate" x-text="selectedCompany"></span>
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                    </button>
                </div>

                <!-- Department Selection -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        DEPARTEMEN PEMILIK <span class="text-rose-500">*</span>
                    </label>
                    @if(auth()->user()->isPicDept())
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <button 
                            type="button" 
                            disabled 
                            class="w-full px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left font-bold text-slate-600 dark:text-slate-300 cursor-not-allowed select-none transition flex items-center justify-between opacity-90"
                            title="Departemen dikunci sesuai akun Anda"
                        >
                            <span class="truncate">{{ auth()->user()->department ? auth()->user()->department->code . ' - ' . auth()->user()->department->name : 'Departemen' }}</span>
                            <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1"></i>
                        </button>
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
                                class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-left text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition flex items-center justify-between cursor-pointer"
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

                <!-- Sub-Departemen (Induk / Default Batch) -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        SUB-DEPARTEMEN <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <select 
                        name="sub_department_id" 
                        x-model="selectedSubDeptId"
                        @change="onSectionSubDeptChange()"
                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition shadow-2xs"
                    >
                        <option value="">-- Induk Departemen --</option>
                        <template x-for="s in subDepartments" :key="s.id">
                            <option :value="s.id" x-text="s.code + ' - ' + s.name" :selected="selectedSubDeptId == s.id"></option>
                        </template>
                    </select>
                </div>

                <!-- Tgl Penyerahan -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
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
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-sans block mt-0.5">Tanggal serah terima box fisik ke Gudang (maksimal hari ini)</span>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 2: WORKSTATION PENGELOLAAN BOX ARSIP -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl p-3.5 shadow-sm space-y-3 font-mono">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            2. Pengelolaan Box & Butir Dokumen Arsip
                        </h2>
                        <p class="text-[10px] text-slate-500 font-sans">
                            {{ $archive->status === 'draft' ? 'Klik tab box untuk beralih. Anda dapat menambah, menyunting, atau menduplikat box secara instan.' : 'Perbarui rincian metadata box dan rincian butir dokumen di dalamnya.' }}
                        </p>
                    </div>
                </div>

                @if($archive->status === 'draft')
                <!-- Box Action Toolbar untuk Mode Draft -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button 
                        type="button" 
                        @click="addBox()" 
                        class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-bold transition flex items-center gap-1 shadow-sm cursor-pointer"
                        title="Tambah Box Arsip Baru"
                    >
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Tambah Box</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openDuplicateModal(activeBoxIndex)" 
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                        title="Duplikasi Box dengan Pilihan Granular"
                    >
                        <i data-lucide="copy" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Duplikat Box...</span>
                    </button>

                    <template x-if="boxes.length > 1">
                        <button 
                            type="button" 
                            @click="removeBox(activeBoxIndex)" 
                            class="px-2 py-1 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-300 dark:border-rose-800 rounded text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                            title="Hapus Box yang Sedang Aktif"
                        >
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span>Hapus Box</span>
                        </button>
                    </template>
                </div>
                @endif
            </div>

            <!-- Box Navigation Tabs (Scrollable Ribbon jika lebih dari 1 box) -->
            <template x-if="boxes.length > 1">
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
                                x-text="(b.items ? b.items.length : 0) + ' Dokumen'"
                            ></span>
                        </button>
                    </template>
                </div>
            </template>

            <!-- CONTAINER FOR ALL BOXES -->
            <template x-for="(box, bIdx) in boxes" :key="box.id">
                <div x-show="activeBoxIndex === bIdx" class="space-y-4 pt-2">
                    
                    <!-- BOX METADATA CARD -->
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 rounded font-black text-xs" x-text="'BOX #' + (bIdx + 1)"></span>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Pengaturan Identitas & Wadah Box</span>
                                
                                @if($archive->status === 'draft')
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
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <!-- Judul Box dengan Live Similarity Check -->
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    JUDUL / LABEL BOX <span class="text-rose-500">*</span>
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
                                            <p class="text-[10px] text-slate-500 italic">Peringatan ini informatif agar Anda dapat memeriksa potensi duplikasi.</p>
                                        </div>
                                        <button type="button" @click="box.similarityInfo = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shrink-0 text-xs px-1 cursor-pointer">✕</button>
                                    </div>
                                </template>
                            </div>

                            <!-- Hidden Sub-Departemen (Otomatis Mewarisi Sub-Dept Induk Batch) -->
                            <input type="hidden" :name="'boxes[' + bIdx + '][sub_department_id]'" :value="selectedSubDeptId">

                            <!-- Kategori Dokumen (Terkunci Otomatis dari Master Berkas) -->
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                    <span>KATEGORI DOKUMEN</span>
                                    <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-0.5" title="Tipe dokumen otomatis terkunci sesuai Master Berkas">
                                        <i data-lucide="lock" class="w-2.5 h-2.5"></i> Terkunci
                                    </span>
                                </label>
                                <div class="relative flex items-center">
                                    <input 
                                        type="text" 
                                        :name="'boxes[' + bIdx + '][document_type]'" 
                                        x-model="box.document_type"
                                        readonly
                                        placeholder="Otomatis terkunci dari Master Berkas" 
                                        class="w-full pl-2.5 pr-8 py-1.5 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-700 dark:text-slate-300 cursor-not-allowed select-none"
                                    >
                                    <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 pointer-events-none"></i>
                                </div>
                            </div>

                            <!-- Wadah Fisik -->
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    KONDISI FISIK WADAH BOX <span class="text-rose-500">*</span>
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
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-normal" x-text="'Maks. ' + getMaxPeriodMonths() + ' Bulan'"></span>
                                </div>

                                <div class="relative flex items-center">
                                    <input 
                                        type="number" 
                                        :id="'periode_bulan_' + bIdx"
                                        x-model="box.periode_bulan"
                                        :disabled="bIdx > 0 && box.follow_previous"
                                        @input="if(box.periode_bulan > getMaxPeriodMonths()) box.periode_bulan = getMaxPeriodMonths(); if(box.periode_bulan !== '' && box.periode_bulan < 1) box.periode_bulan = 1;"
                                        min="1" 
                                        :max="getMaxPeriodMonths()"
                                        :placeholder="'Maks. ' + getMaxPeriodMonths() + ' Bulan (' + (getMaxPeriodMonths()/12) + ' Thn)'" 
                                        class="w-full pl-2.5 pr-14 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition disabled:bg-slate-100 dark:disabled:bg-slate-900/50 disabled:text-slate-400 disabled:cursor-not-allowed"
                                    >
                                    <span class="absolute right-2.5 text-xs font-mono font-bold text-slate-400 pointer-events-none select-none">Bulan</span>
                                </div>

                                @if($archive->status === 'draft')
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
                                @endif

                                <!-- Quick Preset Buttons -->
                                <div class="flex flex-wrap items-center gap-1 pt-1 font-mono text-[10px]" x-show="!(bIdx > 0 && box.follow_previous)">
                                    <span class="text-slate-400 text-[9px] mr-0.5">Preset:</span>
                                    <template x-for="preset in getPresetMonths()" :key="preset">
                                        <button 
                                            type="button" 
                                            @click="box.periode_bulan = preset" 
                                            class="px-1.5 py-0.5 rounded border transition cursor-pointer"
                                            :class="box.periode_bulan == preset 
                                                ? 'bg-amber-500 text-slate-950 border-amber-600 font-black' 
                                                : 'bg-slate-100 dark:bg-slate-900 hover:bg-amber-500/20 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800 font-medium'"
                                            x-text="preset + ' Bln' + (preset == 120 ? ' (10 Thn)' : (preset == 60 ? ' (5 Thn)' : ''))"
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
                                    Setiap box berisi banyak butir berkas arsip. Anda dapat menyalin data baris dari Excel dan langsung menempelnya (Ctrl+V) di tabel.
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
                                                        @change="onMasterDocChange(box, item)"
                                                        x-init="$nextTick(() => { if (item.document_name && $el.value !== item.document_name) $el.value = item.document_name; })"
                                                        x-effect="$nextTick(() => { if (item.document_name && $el.value !== item.document_name) $el.value = item.document_name; })"
                                                        class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                                                    >
                                                        <option value="">-- Pilih dari Master Berkas Arsip --</option>
                                                        <template x-for="m in getBoxMasterArchives(box)" :key="m.id">
                                                            <option :value="m.name" x-text="(m.code ? (m.code + ' - ') : '') + m.name + (m.document_type ? ' [' + m.document_type + ']' : '')" :selected="m.name === item.document_name"></option>
                                                        </template>
                                                        <template x-if="item.document_name && !getBoxMasterArchives(box).some(m => m.name === item.document_name)">
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
                                                    class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-purple-700 dark:text-purple-300 focus:outline-none focus:border-emerald-500"
                                                >
                                            </td>

                                            <!-- Periode Selesai (Bulan) -->
                                            <td class="py-2 px-2.5">
                                                <input 
                                                    type="month" 
                                                    :name="'boxes[' + bIdx + '][items][' + iIdx + '][period_end]'" 
                                                    x-model="item.period_end"
                                                    :max="currentMonth"
                                                    :min="item.period_start || null"
                                                    @change="validatePeriod(item)"
                                                    class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-purple-700 dark:text-purple-300 focus:outline-none focus:border-emerald-500"
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

                                            <!-- Aksi Row -->
                                            <td class="py-2 px-2 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button 
                                                        type="button" 
                                                        @click="duplicateItem(bIdx, iIdx)" 
                                                        class="p-1 text-slate-400 hover:text-blue-500 rounded transition cursor-pointer"
                                                        title="Duplikat baris dokumen ini"
                                                    >
                                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        @click="removeItem(bIdx, iIdx)" 
                                                        class="p-1 text-slate-400 hover:text-rose-500 rounded transition cursor-pointer"
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

                        <!-- Subtotal Box Counter -->
                        <div class="flex items-center justify-between text-[11px] font-mono text-slate-500 pt-1">
                            <span>Total Butir di Box #<span x-text="bIdx + 1"></span>: <strong class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="box.items.length"></strong> Dokumen</span>
                            <span class="text-slate-400 text-[10px]">Tip: Anda dapat langsung menekan Ctrl+V di tabel untuk menempel baris dari Excel.</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- SECTION 3: UPLOAD BERKAS DIGITAL & SCAN FORMULIR -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                3. Upload Berkas Digital & Scan Formulir
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 font-mono text-xs"
                 @file-change="if($event.detail.name === 'scan_input_form') { hasScanForm = ($event.detail.hasFile && !$event.detail.isOverLimit) || hasExistingScanForm; }">
                <!-- 1. Scan Formulir Input (Wajib untuk Pengajuan) -->
                <x-file-uploader 
                    name="scan_input_form" 
                    id="scan_input_form" 
                    label="Scan Formulir Input" 
                    :required="!$archive->scan_input_form && $archive->status !== 'draft'" 
                    badge="WAJIB UNTUK PENGAJUAN" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeKB="500"
                    :maxSizeMB="0.5"
                    :existingUrl="app_storage_url($archive->scan_input_form)"
                    existingLabel="Scan Formulir Tersimpan"
                    helperText="Format: PDF, JPG, PNG (Maksimal 500 KB). Kosongkan jika tidak ingin mengganti file."
                />

                <!-- 2. Lampiran Digital Dokumen / Foto Box (Opsional) -->
                <x-file-uploader 
                    name="file" 
                    id="file" 
                    label="Foto Fisik Box / Lampiran Digital" 
                    :required="false" 
                    badge="OPSIONAL" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeKB="500"
                    :maxSizeMB="0.5"
                    :existingUrl="app_storage_url($archive->file_path)"
                    existingLabel="Lampiran Digital Tersimpan"
                    helperText="Format: PDF, JPG, JPEG, PNG (Maksimal 500 KB). Kosongkan jika tidak ingin mengganti file."
                />
            </div>
        </fieldset>

        <!-- MODAL DUPLIKASI BOX (Granular Copy) -->
        <div 
            x-show="duplicateModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div 
                @click.outside="duplicateModalOpen = false" 
                class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl max-w-lg w-full p-4 space-y-3.5 shadow-xl font-mono text-xs"
            >
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-amber-500/20 text-amber-600 rounded">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Duplikasi Box Arsip</h3>
                            <p class="text-[10px] text-slate-400 font-sans">Sumber: Box #<span x-text="duplicateSourceIndex + 1"></span></p>
                        </div>
                    </div>
                    <button type="button" @click="duplicateModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
                </div>

                <div class="space-y-2 font-sans">
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih mode duplikasi:</p>
                    <div class="space-y-1.5">
                        <label class="flex items-start gap-2 p-2 rounded border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer">
                            <input type="radio" value="full" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-xs text-slate-900 dark:text-white">Duplikat Lengkap (Seluruh Butir Dokumen)</span>
                                <p class="text-[10px] text-slate-500">Menyalin pengaturan box beserta seluruh butir berkas di dalamnya.</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 p-2 rounded border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer">
                            <input type="radio" value="metadata_only" x-model="duplicateMode" class="mt-0.5 text-amber-500">
                            <div>
                                <span class="font-bold text-xs text-slate-900 dark:text-white">Hanya Pengaturan Identitas (Kosongkan Butir)</span>
                                <p class="text-[10px] text-slate-500">Menyalin sub-dept, tipe dokumen, dan kondisi fisik. Butir berkas dimulai baru.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-2.5 font-mono">
                    <button type="button" @click="duplicateModalOpen = false" class="px-3 py-1.5 rounded border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer">Batal</button>
                    <button type="button" @click="confirmDuplicateBox()" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Duplikat Sekarang</span>
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
                <span>Total: <strong class="text-slate-900 dark:text-white" x-text="boxes.length + ' Box'"></strong> dengan <strong class="text-slate-900 dark:text-white" x-text="getTotalItemsCount() + ' Butir Dokumen'"></strong></span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('archives.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                    Batal
                </a>

                @if($archive->status === 'draft')
                    <!-- Simpan Sebagai Draft (Simpan Sementara) -->
                    <button 
                        type="submit" 
                        formnovalidate
                        name="submit_action" 
                        value="draft" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded border border-slate-400 dark:border-slate-600 shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                        title="Simpan sementara semua box sebagai 1 berkas draft tanpa validasi ketat"
                    >
                        <i data-lucide="file-clock" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Simpan Sementara (Draft)</span>
                    </button>

                    <!-- Simpan & Ajukan Box Arsip Permanen -->
                    <button 
                        type="button" 
                        @click="submitFinal('submit')"
                        :class="hasScanForm ? 'bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 border-amber-600 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-pointer'"
                        class="px-5 py-2 font-black text-xs rounded border transition flex items-center gap-1.5"
                        title="Submit verifikasi seluruh box arsip ke Gudang"
                    >
                        <i data-lucide="send" class="w-3.5 h-3.5" :class="hasScanForm ? 'text-slate-950' : 'text-slate-400 dark:text-slate-500'"></i>
                        <span>Simpan & Ajukan Permanen ke Gudang</span>
                    </button>
                @else
                    <!-- Simpan Perubahan Berkas Arsip (Non-Draft) -->
                    <button 
                        type="submit" 
                        name="submit_action" 
                        value="update" 
                        class="px-5 py-2 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded border border-amber-600 shadow-md transition flex items-center gap-1.5 cursor-pointer"
                        title="Simpan perubahan data berkas arsip"
                    >
                        <i data-lucide="save" class="w-3.5 h-3.5 text-slate-950"></i>
                        <span>Simpan Perubahan Berkas</span>
                    </button>
                @endif
            </div>
        </div>
    </form>

    {{-- Komponen Modal Pratinjau & Cetak Form Pengajuan Dokumen Standar PT Indraco --}}
    <x-form-pengajuan-modal />

</div>

@push('scripts')
<script>
function archiveEditApp() {
    const isDraft = {{ $archive->status === 'draft' ? 'true' : 'false' }};
    const savedDraftBoxes = @json($draftBoxes);
    let initialBoxes = [];

    if (savedDraftBoxes && savedDraftBoxes.length > 0) {
        initialBoxes = savedDraftBoxes.map((b, bIdx) => ({
            id: b.id || (Date.now() + bIdx + Math.random()),
            title: b.title || '',
            sub_department_id: b.sub_department_id || '',
            document_type: b.document_type || '',
            physical_condition: b.physical_condition || 'Baik / Box Karton Standar TB 30g',
            periode_bulan: (b.periode_bulan || (b.periode ? String(b.periode).replace(/[^0-9]/g, '') : '') || '1'),
            follow_previous: b.follow_previous || false,
            similarityInfo: null,
            items: (b.items && b.items.length > 0) ? b.items.map((it, iIdx) => ({
                id: it.id || (Date.now() + iIdx + Math.random()),
                document_name: it.document_name || '',
                period_start: it.period_start || '',
                period_end: it.period_end || it.period_start || '',
                notes: it.notes || ''
            })) : [
                { id: Date.now(), document_name: '', period_start: '{{ date('Y-m') }}', period_end: '{{ date('Y-m') }}', notes: '' }
            ]
        }));
    } else {
        const rawItems = @json($archive->items);
        const mappedItems = (rawItems && rawItems.length > 0) ? rawItems.map((it, iIdx) => ({
            id: it.id || (Date.now() + iIdx + Math.random()),
            document_name: it.document_name,
            period_start: it.period_start || '',
            period_end: it.period_end || it.period_start || '',
            notes: it.notes || ''
        })) : [
            { id: Date.now(), document_name: '', period_start: '{{ date('Y-m') }}', period_end: '{{ date('Y-m') }}', notes: '' }
        ];

        let extractedBulan = '1';
        @if(!empty($archive->periode))
            extractedBulan = '{{ preg_match('/(\d+)/', $archive->periode, $m) ? $m[1] : 1 }}';
        @elseif(!empty($archive->periode_doc))
            extractedBulan = '{{ preg_match('/(\d+)/', $archive->periode_doc, $m) ? $m[1] : 1 }}';
        @endif

        initialBoxes = [
            {
                id: 'box_' + {{ $archive->id }},
                title: @json($archive->title ?? ''),
                sub_department_id: '{{ $archive->sub_department_id ?? '' }}',
                document_type: @json($archive->document_type ?? ''),
                physical_condition: @json($archive->physical_condition ?? 'Baik / Box Karton Standar TB 30g'),
                periode_bulan: extractedBulan || '1',
                follow_previous: false,
                similarityInfo: null,
                items: mappedItems
            }
        ];
    }

    const departmentsData = @json($departments);
    const initialDeptId = '{{ old('department_id', $archive->department_id) }}';
    const initialDept = departmentsData.find(d => String(d.id) === String(initialDeptId)) || departmentsData[0] || null;
    const initialSubDepts = (initialDept && initialDept.sub_departments) ? initialDept.sub_departments : [];
    const initialMaster = (initialDept && initialDept.master_archives) ? initialDept.master_archives : [];

    return {
        departments: departmentsData,
        selectedCompany: '{{ old('company_name', $archive->company_name ?? 'PT INDRACO GLOBAL INDONESIA') }}',
        selectedDeptId: initialDept ? initialDept.id : initialDeptId,
        selectedSubDeptId: '{{ old('sub_department_id', $archive->sub_department_id ?? '') }}',
        tglPenyerahan: '{{ old('tgl_penyerahan', $archive->tgl_penyerahan ? $archive->tgl_penyerahan->format('Y-m-d') : date('Y-m-d')) }}',
        currentMonth: '{{ date('Y-m') }}',
        subDepartments: initialSubDepts,
        masterArchives: initialMaster,
        hasExistingScanForm: {{ !empty($archive->scan_input_form) ? 'true' : 'false' }},
        hasScanForm: {{ !empty($archive->scan_input_form) ? 'true' : 'false' }},

        // Workstation Multi-Box State
        activeBoxIndex: 0,
        boxes: initialBoxes,

        // Duplicate Modal State
        duplicateModalOpen: false,
        duplicateSourceIndex: 0,
        duplicateMode: 'full',
        duplicateSelectedItems: [],
        // Form Pengajuan Dokumen Modal State
        formModalOpen: false,
        formPengajuanData: {
            no_form: '',
            tanggal_pengajuan: '{{ date('Y-m-d') }}',
            dept_display: '',
            pic_pemohon: @json(auth()->user()->name ?? ''),
            tipe: 'Simpan',
            masa_simpan: 1,
            catatan: 'Kondisi fisik box standar TB 30g dalam keadaan baik.',
            approval_pemohon: @json(auth()->user()->name ?? ''),
            approval_atasan: '',
            approval_pimpinan: '',
            approval_admin_gudang: 'Admin Gudang',
            approval_tgl: ''
        },
        formPengajuanRows: [],

        // Similarity check debounce timer
        similarityTimer: null,

        init() {
            this.updateSubDepartments(false);
            this.updateMasterArchives();
            this.boxes.forEach(b => {
                if (this.selectedSubDeptId && !b.sub_department_id) {
                    b.sub_department_id = this.selectedSubDeptId;
                }
                this.onBoxSubDeptChange(b, true);
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        isFatAccount() {
            @if(auth()->check() && (
                (auth()->user()->department && in_array(strtoupper(auth()->user()->department->code), ['FAT', 'FIN', 'FATCLM'])) ||
                str_contains(strtoupper(auth()->user()->name ?? ''), 'FAT') ||
                str_contains(strtoupper(auth()->user()->name ?? ''), 'FIN')
            ))
                return true;
            @else
                const currentDept = this.departments.find(d => String(d.id) === String(this.selectedDeptId));
                if (currentDept && currentDept.code && ['FAT', 'FIN', 'FATCLM'].includes(String(currentDept.code).toUpperCase())) {
                    return true;
                }
                return false;
            @endif
        },

        getMaxPeriodMonths() {
            return this.isFatAccount() ? 120 : 60;
        },

        getPresetMonths() {
            return this.isFatAccount() 
                ? [1, 3, 4, 6, 12, 15, 24, 60, 120] 
                : [1, 3, 4, 6, 12, 15, 24, 60];
        },

        getTotalItemsCount() {
            return this.boxes.reduce((sum, b) => sum + (b.items ? b.items.length : 0), 0);
        },

        addBox() {
            const nextNum = this.boxes.length + 1;
            const prevBox = this.boxes[this.activeBoxIndex] || (this.boxes.length > 0 ? this.boxes[this.boxes.length - 1] : null);
            const inheritedSubDept = prevBox ? prevBox.sub_department_id : (this.selectedSubDeptId || '');
            const inheritedPeriod = prevBox && prevBox.periode_bulan ? prevBox.periode_bulan : '1';

            const tempBox = { sub_department_id: inheritedSubDept };
            const available = this.getBoxMasterArchives(tempBox);
            const defaultDoc = available.length > 0 ? available[0].name : '';
            const defaultDocType = available.length > 0 ? (available[0].document_type || '') : (prevBox ? prevBox.document_type : '');

            this.boxes.push({
                id: Date.now() + Math.random(),
                title: 'Box ' + nextNum,
                sub_department_id: inheritedSubDept,
                document_type: defaultDocType,
                physical_condition: 'Baik / Box Karton Standar TB 30g',
                periode_bulan: inheritedPeriod,
                follow_previous: true,
                similarityInfo: null,
                items: [
                    { id: Date.now(), document_name: defaultDoc, period_start: this.currentMonth, period_end: this.currentMonth, notes: '' }
                ]
            });
            this.activeBoxIndex = this.boxes.length - 1;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
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
            this.duplicateModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        confirmDuplicateBox() {
            const source = this.boxes[this.duplicateSourceIndex];
            if (!source) return;

            let duplicatedItems = [];
            if (this.duplicateMode === 'full') {
                duplicatedItems = source.items.map(it => ({
                    id: Date.now() + Math.random(),
                    document_name: it.document_name,
                    period_start: it.period_start,
                    period_end: it.period_end,
                    notes: it.notes
                }));
            } else {
                const defaultDoc = this.masterArchives.length > 0 ? this.masterArchives[0].name : '';
                duplicatedItems = [
                    { id: Date.now(), document_name: defaultDoc, period_start: this.currentMonth, period_end: this.currentMonth, notes: '' }
                ];
            }

            this.boxes.push({
                id: Date.now() + Math.random(),
                title: source.title + ' (Salinan)',
                sub_department_id: source.sub_department_id,
                document_type: source.document_type,
                physical_condition: source.physical_condition,
                periode_bulan: source.periode_bulan,
                follow_previous: false,
                similarityInfo: null,
                items: duplicatedItems
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
                        message: 'Pengajuan minimal harus memiliki 1 box arsip.',
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

            for (let i = 0; i < count; i++) {
                box.items.push({
                    id: Date.now() + i + Math.random(),
                    document_name: defaultDoc,
                    period_start: pStart,
                    period_end: pEnd,
                    notes: ''
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
                notes: source.notes
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

        promptCustomDocName(item) {
            const current = item.document_name || '';
            const custom = prompt('Ketik nama berkas khusus yang ingin ditambahkan (Super Admin):', current);
            if (custom !== null && custom.trim() !== '') {
                item.document_name = custom.trim();
            }
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

                // 1. Local In-Batch Check
                let localMatch = null;
                for (let i = 0; i < this.boxes.length; i++) {
                    if (i === boxIndex) continue;
                    const otherTitle = (this.boxes[i].title || '').trim().toLowerCase();
                    if (!otherTitle) continue;
                    if (currentTitle === otherTitle) {
                        localMatch = {
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
                        message: `Judul identik 100% dengan Box #${localMatch.box_num} di form ini.`,
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

        updateSubDepartments(clearSelection = true) {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            if (dept && dept.sub_departments) {
                this.subDepartments = dept.sub_departments;
            } else {
                this.subDepartments = [];
            }
            if (clearSelection) {
                this.selectedSubDeptId = '';
            }
            this.updateMasterArchives();
            this.boxes.forEach(b => {
                if (clearSelection) {
                    b.sub_department_id = '';
                }
                this.onBoxSubDeptChange(b);
            });
        },

        updateMasterArchives() {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            let allArchives = [];
            if (dept && dept.master_archives && dept.master_archives.length > 0) {
                this.masterArchives = dept.master_archives;
                this.boxes.forEach(b => {
                    this.onBoxSubDeptChange(b, true);
                });
            } else if (this.selectedDeptId) {
                fetch('{{ url('/api/departments') }}/' + this.selectedDeptId + '/master-archives')
                    .then(res => res.json())
                    .then(data => {
                        this.masterArchives = data.master_archives || [];
                        this.boxes.forEach(b => {
                            this.onBoxSubDeptChange(b, true);
                        });
                    })
                    .catch(err => {
                        this.masterArchives = [];
                    });
            } else {
                this.masterArchives = [];
            }
        },

        getBoxMasterArchives(box) {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            const all = (dept && dept.master_archives && dept.master_archives.length > 0) ? dept.master_archives : (this.masterArchives || []);
            if (!all || all.length === 0) return [];

            const subId = (box && box.sub_department_id !== undefined && box.sub_department_id !== '' && box.sub_department_id !== null) 
                ? box.sub_department_id 
                : this.selectedSubDeptId;

            if (subId) {
                const subSpecific = all.filter(m => String(m.sub_department_id) === String(subId));
                if (subSpecific.length > 0) {
                    return subSpecific;
                }
                const induk = all.filter(m => !m.sub_department_id);
                return induk.length > 0 ? induk : all;
            }

            const induk = all.filter(m => !m.sub_department_id);
            return induk.length > 0 ? induk : all;
        },

        getItemDocType(box, item) {
            if (!item || !item.document_name) return box ? (box.document_type || '') : '';
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            const all = (dept && dept.master_archives && dept.master_archives.length > 0) ? dept.master_archives : (this.masterArchives || []);
            const matched = all.find(m => m.name === item.document_name);
            if (matched && matched.document_type) {
                return matched.document_type;
            }
            return box ? (box.document_type || '') : '';
        },

        onBoxSubDeptChange(box, preserveExisting = false) {
            if (!box) return;
            const available = this.getBoxMasterArchives(box);
            if (available && available.length > 0) {
                if (box.items && box.items.length > 0) {
                    box.items.forEach(it => {
                        if (!it.document_name || (!preserveExisting && !available.some(m => m.name === it.document_name))) {
                            it.document_name = available[0].name;
                            this.onMasterDocChange(box, it);
                        } else {
                            this.onMasterDocChange(box, it);
                        }
                    });
                }
                if (!box.document_type || (!preserveExisting && !available.some(m => m.document_type === box.document_type))) {
                    box.document_type = available[0].document_type || '';
                }
                if (available[0].retention_years && !preserveExisting) {
                    box.retention_years = available[0].retention_years;
                }
            }
        },

        onMasterDocChange(box, item) {
            if (!item || !item.document_name) return;
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            const all = (dept && dept.master_archives && dept.master_archives.length > 0) ? dept.master_archives : (this.masterArchives || []);
            const matched = all.find(m => m.name === item.document_name);
            if (matched) {
                if (matched.document_type) {
                    box.document_type = matched.document_type;
                }
                if (matched.retention_years) {
                    box.retention_years = matched.retention_years;
                }
            }
        },

        onSectionSubDeptChange() {
            this.updateMasterArchives();
            this.boxes.forEach(b => {
                b.sub_department_id = this.selectedSubDeptId;
                this.onBoxSubDeptChange(b);
            });
        },

        submitFinal(action) {
            // Check that all boxes have at least 1 item with document_name
            let missingName = false;
            let missingBoxIdx = -1;
            this.boxes.forEach((box, bIdx) => {
                box.items.forEach(it => {
                    if (!it.document_name || !it.document_name.trim()) {
                        missingName = true;
                        if (missingBoxIdx === -1) missingBoxIdx = bIdx;
                    }
                });
            });

            if (missingName) {
                alert(`⚠️ Terdapat butir dokumen pada Box #${missingBoxIdx + 1} yang belum memiliki nama berkas arsip. Silakan pilih atau lengkapi nama dokumen.`);
                this.activeBoxIndex = missingBoxIdx;
                return;
            }

            if (!this.hasScanForm && !this.hasExistingScanForm) {
                alert('⚠️ PERHATIAN: BERKAS BELUM LENGKAP!\n\n"Scan Formulir Input" wajib diunggah sebelum mengajukan verifikasi box arsip ke Gudang.\n\nSilakan unggah Scan Formulir Input pada Bagian 3, atau klik tombol "Simpan Sementara (Draft)" jika ingin menyimpan data tanpa pengajuan.');
                const fileInput = document.getElementById('scan_input_form');
                if (fileInput) {
                    fileInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    fileInput.focus();
                }
                return;
            }

            for (let i = 0; i < this.boxes.length; i++) {
                if (!this.boxes[i].title || !this.boxes[i].title.trim()) {
                    alert(`⚠️ Nama / Judul Box untuk Box #${i + 1} belum diisi. Silakan lengkapi judul box.`);
                    this.activeBoxIndex = i;
                    return;
                }
            }

            const form = document.getElementById('archiveEditForm');
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
            if (!this.formPengajuanData.catatan || this.formPengajuanData.catatan.includes('Rincian Masa Simpan')) {
                this.formPengajuanData.catatan = 'Kondisi fisik box standar TB 30g dalam keadaan baik.';
            }

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
                        period_end: this.getBoxPeriodEndFormatted(b),
                        masa_simpan: b.periode_bulan ? String(b.periode_bulan) : '1',
                        label_box: (b.rack_label || (b.short_location && b.short_location !== 'Belum Ada Lokasi' ? b.short_location : '') || b.box_number || ''),
                        label_kardus: (b.rack_label || (b.short_location && b.short_location !== 'Belum Ada Lokasi' ? b.short_location : '') || b.box_number || '')
                    });
                } else {
                    rows.push({
                        no: i + 1,
                        title: '',
                        period_end: '',
                        masa_simpan: '',
                        label_box: '',
                        label_kardus: ''
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
            if (this.tglPenyerahan) {
                const parts = this.tglPenyerahan.split('-');
                if (parts.length >= 2) {
                    return `${parts[1]}-${parts[0]}`;
                }
            }
            if (!box || !box.items || box.items.length === 0) return '';
            const starts = box.items.map(it => it.period_start).filter(Boolean).sort();
            if (starts.length === 0) return '';
            const minStart = starts[0];
            const parts = minStart.split('-');
            return parts.length === 2 ? `${parts[1]}-${parts[0]}` : minStart;
        },

        getBoxPeriodEndFormatted(box) {
            if (this.tglPenyerahan && box && box.periode_bulan) {
                const parts = this.tglPenyerahan.split('-');
                if (parts.length >= 2) {
                    const year = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10);
                    const monthsToAdd = parseInt(box.periode_bulan, 10) || 1;
                    const targetDate = new Date(year, month - 1 + monthsToAdd, 1);
                    const endM = String(targetDate.getMonth() + 1).padStart(2, '0');
                    const endY = targetDate.getFullYear();
                    return `${endM}-${endY}`;
                }
            }
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

        printFormPengajuan() {
            window.print();
        }
    };
}
</script>
@endpush
@endsection
