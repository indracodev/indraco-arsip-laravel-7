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
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Periode 1 Bulan / Rentang Multi-Bulan (YYYY/MM) • Kalkulasi Masa Simpan Otomatis • Standar Box TB 30g</p>
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
                <!-- Company Entity -->
                <div>
                    <label for="company_name" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PERUSAHAAN ENTITAS <span class="text-rose-500">*</span>
                    </label>
                    <select name="company_name" id="company_name" required class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="PT Indraco Jaya Perkasa" {{ old('company_name') == 'PT Indraco Jaya Perkasa' ? 'selected' : '' }}>PT Indraco Jaya Perkasa</option>
                        <option value="PT Indraco Global" {{ old('company_name') == 'PT Indraco Global' ? 'selected' : '' }}>PT Indraco Global</option>
                        <option value="PT Indraco Trading" {{ old('company_name') == 'PT Indraco Trading' ? 'selected' : '' }}>PT Indraco Trading</option>
                        <option value="PT Indraco Enterprise" {{ old('company_name') == 'PT Indraco Enterprise' ? 'selected' : '' }}>PT Indraco Enterprise</option>
                        <option value="PT Indraco International" {{ old('company_name') == 'PT Indraco International' ? 'selected' : '' }}>PT Indraco International</option>
                    </select>
                </div>

                <!-- Department Selection -->
                <div>
                    <label for="department_id" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        DEPARTEMEN <span class="text-rose-500">*</span>
                    </label>
                    @if(auth()->user()->isPicDept())
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <input 
                            type="text" 
                            value="{{ auth()->user()->department->code }} - {{ auth()->user()->department->name }}" 
                            disabled 
                            class="w-full px-2.5 py-1.5 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-800 dark:text-slate-300 cursor-not-allowed"
                        >
                    @else
                        <select name="department_id" id="department_id" x-model="selectedDeptId" @change="updateSubDepartments()" required class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                            <option value="">-- Pilih Departemen --</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->code }} - {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <!-- Sub-Department Selection -->
                <div>
                    <label for="sub_department_id" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        SUB-DEPARTEMEN <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <select name="sub_department_id" id="sub_department_id" x-model="selectedSubDeptId" @change="calculateRetention()" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="">-- Pilih Sub-Departemen (Induk) --</option>
                        <template x-for="sub in subDepartments" :key="sub.id">
                            <option :value="sub.id" x-text="`${sub.code} - ${sub.name}`" :selected="sub.id == selectedSubDeptId"></option>
                        </template>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 2: KATEGORI & JUDUL DOKUMEN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-blue-700 dark:text-blue-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-blue-500"></i>
                2. Kategori & Identitas Dokumen
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label for="document_type" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        KATEGORI DOKUMEN <span class="text-rose-500">*</span>
                    </label>
                    <select name="document_type" id="document_type" required class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="FAKTUR_PAJAK" {{ old('document_type') == 'FAKTUR_PAJAK' ? 'selected' : '' }}>FAKTUR PAJAK & KEUANGAN</option>
                        <option value="KONTRAK_KERJA" {{ old('document_type') == 'KONTRAK_KERJA' ? 'selected' : '' }}>KONTRAK KERJA / SDM</option>
                        <option value="MOU_SPONSOR" {{ old('document_type') == 'MOU_SPONSOR' ? 'selected' : '' }}>MOU / KERJASAMA / SALES</option>
                        <option value="SURAT_JALAN" {{ old('document_type') == 'SURAT_JALAN' ? 'selected' : '' }}>SURAT JALAN & LOGISTIK</option>
                        <option value="PRODUKSI_QC" {{ old('document_type') == 'PRODUKSI_QC' ? 'selected' : '' }}>PRODUKSI & QUALITY CONTROL</option>
                        <option value="UMUM" {{ old('document_type') == 'UMUM' ? 'selected' : '' }}>UMUM / LAIN-LAIN</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1 font-mono">
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                            JUDUL / NAMA DOKUMEN <span class="text-rose-500">*</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                            <input type="checkbox" name="is_custom_doc_name" value="1" x-model="isCustomDocName" class="rounded border-slate-300 text-amber-500 focus:ring-amber-400 h-3.5 w-3.5">
                            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">Aktifkan Custom Nama Dokumen</span>
                        </label>
                    </div>

                    <!-- Standard Document Name -->
                    <div x-show="!isCustomDocName">
                        <input 
                            type="text" 
                            name="title" 
                            id="title" 
                            x-model="title"
                            placeholder="Contoh: Laporan Keuangan & Faktur Pajak Q3" 
                            class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <!-- Custom Document Name -->
                    <div x-show="isCustomDocName" x-cloak>
                        <input 
                            type="text" 
                            name="custom_doc_name" 
                            id="custom_doc_name" 
                            x-model="customDocName"
                            placeholder="Ketik nama/judul dokumen kustom jika tidak ada pada master data..." 
                            class="w-full px-3 py-1.5 bg-amber-500/10 dark:bg-amber-950/30 border-2 border-amber-500 rounded text-xs font-mono font-bold text-amber-950 dark:text-amber-200 placeholder-amber-600/50 focus:outline-none focus:border-amber-600 transition"
                        >
                    </div>
                    @error('title') <span class="text-rose-500 text-[11px] mt-1 block font-bold font-mono">{{ $message }}</span> @enderror
                    @error('custom_doc_name') <span class="text-rose-500 text-[11px] mt-1 block font-bold font-mono">{{ $message }}</span> @enderror
                </div>
            </div>
        </fieldset>

        <!-- SECTION 3: PERIODE DOKUMEN (1 BULAN ATAU RENTANG MULTI-BULAN) & RETENSI -->
        <fieldset class="border border-amber-500/40 p-3.5 rounded bg-amber-500/5 dark:bg-amber-950/20 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-amber-800 dark:text-amber-400 bg-amber-100 dark:bg-slate-800 border border-amber-400 dark:border-amber-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="calendar-range" class="w-3.5 h-3.5 text-amber-600"></i>
                3. Periode Dokumen (1 Bulan / Rentang Periode) & Kalkulasi Masa Simpan
            </legend>

            <!-- Mode Selector: 1 Bulan vs Rentang Multi-Bulan -->
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-500/20 pb-2.5 font-mono text-xs">
                <span class="font-bold text-slate-700 dark:text-slate-300">PILIHAN FORMAT PERIODE:</span>
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" value="single" x-model="periodMode" @change="onPeriodModeChange()" class="text-amber-500 focus:ring-amber-400">
                        <span :class="periodMode === 'single' ? 'font-bold text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-400'">1 Bulan Saja (cth: 2026/07)</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" value="range" x-model="periodMode" @change="onPeriodModeChange()" class="text-amber-500 focus:ring-amber-400">
                        <span :class="periodMode === 'range' ? 'font-bold text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-400'">Rentang Multi-Bulan (cth: 2026/07 s/d 2026/09)</span>
                    </label>
                </div>
            </div>

            <!-- Hidden input that submits the final formatted periode_doc -->
            <input type="hidden" name="periode_doc" :value="periodeDoc">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Single Month Input -->
                <div x-show="periodMode === 'single'">
                    <label for="period_single" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PERIODE BULAN (YYYY/MM) <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="period_single" 
                        x-model="periodStart" 
                        @input="updatePeriodDoc()"
                        placeholder="2026/07" 
                        pattern="^\d{4}\/(0[1-9]|1[0-2])$"
                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500 transition"
                    >
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block mt-0.5">Format: YYYY/MM (contoh: 2026/07)</span>
                </div>

                <!-- Range Multi-Month Inputs -->
                <div x-show="periodMode === 'range'" class="sm:col-span-1 grid grid-cols-2 gap-2" x-cloak>
                    <div>
                        <label for="period_range_start" class="block font-mono text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            PERIODE AWAL <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="period_range_start" 
                            x-model="periodStart" 
                            @input="updatePeriodDoc()"
                            placeholder="2026/07" 
                            class="w-full px-2 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>
                    <div>
                        <label for="period_range_end" class="block font-mono text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            HINGGA (AKHIR) <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="period_range_end" 
                            x-model="periodEnd" 
                            @input="updatePeriodDoc()"
                            placeholder="2026/09" 
                            class="w-full px-2 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>
                </div>

                <!-- Tgl Penyerahan -->
                <div>
                    <label for="tgl_penyerahan" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        TGL. PENYERAHAN DOKUMEN <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="tgl_penyerahan" 
                        id="tgl_penyerahan" 
                        x-model="tglPenyerahan"
                        required 
                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block mt-0.5">Tanggal serah terima fisik ke Gudang</span>
                </div>

                <!-- Custom Masa Simpan -->
                <div>
                    <label for="masa_simpan_custom" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        OVERRIDE MASA SIMPAN (TAHUN)
                    </label>
                    <input 
                        type="number" 
                        name="masa_simpan_custom" 
                        id="masa_simpan_custom" 
                        x-model="masaSimpanCustom"
                        @input="calculateRetention()"
                        min="1" 
                        max="30" 
                        placeholder="Ikut Standar Dept" 
                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block mt-0.5">Kosongkan jika mengikuti standar unit</span>
                </div>
            </div>

            <!-- Auto Calculation Retention Banner -->
            <div class="p-3 rounded bg-white/90 dark:bg-slate-900/90 border border-amber-300 dark:border-amber-900 space-y-1.5 font-mono">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Periode Terdaftar:</span>
                        <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-800 dark:text-amber-300 text-xs font-black" x-text="periodeDoc || '-'"></span>
                        <span class="text-slate-400">•</span>
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Masa Simpan:</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-xs font-bold" x-text="`${effectiveYearsPreview} Tahun`"></span>
                        <span class="text-slate-400">•</span>
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Expired:</span>
                        <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-700 dark:text-rose-300 text-xs font-black" x-text="expiryDatePreview"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded bg-emerald-500/10">
                            Standar Box: TB 30g
                        </span>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 italic">
                    * Masa simpan dihitung otomatis dari tanggal akhir periode dokumen untuk menjamin seluruh isi berkas tetap terlindungi.
                </p>
            </div>
        </fieldset>

        <!-- SECTION 4: KONDISI & RINCIAN ISI METADATA A5 -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="align-left" class="w-3.5 h-3.5 text-emerald-500"></i>
                4. Spesifikasi Wadah & Rincian Butir Dokumen (Label A5)
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                </div>
                <div>
                    <label for="period_text" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        LABEL PERIODE TAMBAHAN <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input 
                        type="text" 
                        name="period_text" 
                        id="period_text" 
                        value="{{ old('period_text') }}" 
                        placeholder="Contoh: Juli - September 2026" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                    >
                </div>
            </div>

            <div>
                <label for="content_description" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                    RINCIAN ISI DOKUMEN (DICETAK PADA FORM LABEL A5) <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="content_description" 
                    id="content_description" 
                    rows="4" 
                    required 
                    placeholder="Tuliskan butir-butir rincian dokumen di dalam box ini (akan dicetak pada label box A5 dengan auto-font scaling):&#10;- Bukti Kas Masuk No. 001 - 150 (Juli - September 2026)&#10;- Bukti Kas Keluar Cabang Surabaya&#10;- Faktur Pajak Masukan & Keluaran" 
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition leading-relaxed"
                >{{ old('content_description') }}</textarea>
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
        <div class="pt-2 flex items-center justify-end gap-2 font-mono">
            <a href="{{ route('archives.index') }}" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                Batal
            </a>
            <button type="submit" class="px-5 py-2 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded border border-amber-600 shadow-md transition flex items-center gap-1.5">
                <i data-lucide="send" class="w-3.5 h-3.5 text-slate-950"></i>
                <span>Simpan & Ajukan Box Arsip (TB 30g)</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function archiveCreateApp() {
    const rawOldPeriod = '{{ old('periode_doc', date('Y/m')) }}';
    let initialMode = 'single';
    let initialStart = '{{ date('Y/m') }}';
    let initialEnd = '';

    if (rawOldPeriod.includes('-') || rawOldPeriod.includes('s/d') || rawOldPeriod.includes('hingga')) {
        initialMode = 'range';
        const parts = rawOldPeriod.split(/[-]|(?:s\/d)|(?:hingga)/i).map(s => s.trim());
        initialStart = parts[0] || '{{ date('Y/m') }}';
        initialEnd = parts[1] || '';
    } else {
        initialStart = rawOldPeriod || '{{ date('Y/m') }}';
    }

    return {
        departments: @json($departments),
        selectedDeptId: '{{ old('department_id', auth()->user()->isPicDept() ? auth()->user()->department_id : ($departments->first()->id ?? '')) }}',
        selectedSubDeptId: '{{ old('sub_department_id', '') }}',
        subDepartments: [],
        isCustomDocName: {{ old('is_custom_doc_name') ? 'true' : 'false' }},
        customDocName: @json(old('custom_doc_name', '')),
        title: @json(old('title', '')),
        periodMode: initialMode,
        periodStart: initialStart,
        periodEnd: initialEnd,
        periodeDoc: rawOldPeriod,
        tglPenyerahan: '{{ old('tgl_penyerahan', date('Y-m-d')) }}',
        masaSimpanCustom: '{{ old('masa_simpan_custom', '') }}',
        expiryDatePreview: '-',
        effectiveYearsPreview: 5,

        init() {
            this.updateSubDepartments();
            this.updatePeriodDoc();
        },

        onPeriodModeChange() {
            if (this.periodMode === 'range' && !this.periodEnd) {
                this.periodEnd = this.periodStart;
            }
            this.updatePeriodDoc();
        },

        updatePeriodDoc() {
            if (this.periodMode === 'range') {
                const s = (this.periodStart || '').trim();
                const e = (this.periodEnd || '').trim();
                this.periodeDoc = (s && e) ? `${s} - ${e}` : (s || e);
            } else {
                this.periodeDoc = (this.periodStart || '').trim();
            }
            this.calculateRetention();
        },

        updateSubDepartments() {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            if (dept && dept.sub_departments) {
                this.subDepartments = dept.sub_departments;
            } else {
                this.subDepartments = [];
            }
            this.calculateRetention();
        },

        calculateRetention() {
            let targetPeriod = (this.periodMode === 'range' && this.periodEnd) ? this.periodEnd.trim() : (this.periodStart || '').trim();
            
            // Also check if raw periodeDoc has a range pattern
            if (this.periodeDoc && (this.periodeDoc.includes('-') || this.periodeDoc.includes('s/d'))) {
                const parts = this.periodeDoc.split(/[-]|(?:s\/d)/i).map(s => s.trim());
                if (parts[1]) targetPeriod = parts[1];
            }

            const regex = /^\d{4}\/(0[1-9]|1[0-2])$/;
            if (!regex.test(targetPeriod)) {
                this.expiryDatePreview = 'Format Periode Harus YYYY/MM (cth: 2026/09)';
                return;
            }

            const parts = targetPeriod.split('/');
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);

            let years = 5;
            if (this.masaSimpanCustom && parseInt(this.masaSimpanCustom, 10) > 0) {
                years = parseInt(this.masaSimpanCustom, 10);
            } else if (this.selectedSubDeptId) {
                const sub = this.subDepartments.find(s => s.id == this.selectedSubDeptId);
                if (sub && sub.retention_years > 0) {
                    years = parseInt(sub.retention_years, 10);
                }
            } else {
                const dept = this.departments.find(d => d.id == this.selectedDeptId);
                if (dept && dept.retention_years > 0) {
                    years = parseInt(dept.retention_years, 10);
                }
            }

            this.effectiveYearsPreview = years;
            const expiryYear = year + years;
            const lastDay = new Date(expiryYear, month, 0).getDate();
            const padMonth = month.toString().padStart(2, '0');
            const padDay = lastDay.toString().padStart(2, '0');
            this.expiryDatePreview = `${padDay}/${padMonth}/${expiryYear}`;
        }
    };
}
</script>
@endpush
@endsection
