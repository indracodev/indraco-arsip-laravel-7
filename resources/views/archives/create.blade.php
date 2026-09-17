@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Draft & Booking Tempat Arsip - DMS PT Indraco')

@php
    $oldDocTypes = old('document_types', []);
    if (!is_array($oldDocTypes)) {
        $oldDocTypes = [];
    }
@endphp

@section('content')
<div class="w-full space-y-[10px] font-mono" x-data="archiveCreateForm()">
    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] shadow-2xs flex items-center justify-between gap-[8px]">
        <div class="flex items-center gap-[8px]">
            <span class="p-[4px] bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded-[3px]">
                <i data-lucide="file-plus" class="w-[15px] h-[15px]"></i>
            </span>
            <div>
                <h1 class="text-[12px] font-bold text-slate-900 dark:text-white uppercase tracking-wider">Form Pengajuan Draft & Booking Storage</h1>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Isi rincian berkas arsip, entitas, subdepartemen & tipe dokumen untuk diverifikasi (TForm Window)</p>
            </div>
        </div>

        <a href="{{ route('archives.index') }}" class="px-[10px] py-[3px] bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-bold transition flex items-center gap-[4px] shadow-2xs">
            <i data-lucide="arrow-left" class="w-[12px] h-[12px] text-amber-500"></i>
            <span>Kembali (Esc)</span>
        </a>
    </div>

    <!-- DELPHI TGROUPBOX MAIN FORM CARD -->
    <fieldset class="border border-slate-300 dark:border-slate-800 p-[10px] rounded-[4px] bg-white dark:bg-slate-950 text-[11px] shadow-xs">
        <legend class="px-[6px] text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-[2px] shadow-2xs flex items-center gap-[4px]">
            <i data-lucide="edit-3" class="w-[12px] h-[12px] text-amber-500"></i>
            <span>[Form Controls] Input Metadata & Rincian Berkas</span>
        </legend>

        <form action="{{ route('archives.store') }}" method="POST" enctype="multipart/form-data" class="space-y-[8px] pt-[4px]">
            @csrf

            <!-- 1. Perusahaan Entitas, Departemen, & Subdepartemen (Parent > Child) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-[8px]">
                
                <!-- Company Name / Perusahaan Entitas (Custom Company Control) -->
                <div class="flex flex-col gap-[2px]">
                    <div class="flex items-center justify-between">
                        <label for="company_name" class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                            PERUSAHAAN ENTITAS <span class="text-rose-500">*</span>
                        </label>
                        <!-- Quick Add Button for PIC Dept & Super Admin -->
                        <button 
                            type="button" 
                            @click="openAddCompanyModal = true"
                            class="text-[9px] font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-[2px]"
                            title="Tambah Perusahaan Entitas Baru / Custom"
                        >
                            <i data-lucide="plus" class="w-[10px] h-[10px]"></i>
                            <span>+ Tambah Entitas</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-[4px]">
                        <select 
                            name="company_name" 
                            id="company_name" 
                            x-model="selectedCompany"
                            required 
                            class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                            <template x-for="comp in companyList" :key="comp.id">
                                <option :value="comp.name" x-text="comp.name + (comp.code ? ' (' + comp.code + ')' : '')"></option>
                            </template>
                        </select>

                        <button 
                            type="button" 
                            @click="openAddCompanyModal = true" 
                            class="px-[6px] h-[28px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[10px] font-bold transition shrink-0"
                            title="Buka form input entitas baru"
                        >
                            +
                        </button>
                    </div>
                    @error('company_name') <span class="text-rose-500 text-[10px] block font-bold">{{ $message }}</span> @enderror
                </div>

                <!-- Department Selection (Parent Department) -->
                <div class="flex flex-col gap-[2px]">
                    <label for="department_id" class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                        DEPARTEMEN PEMILIK <span class="text-rose-500">*</span>
                    </label>
                    @if(auth()->user()->isPicDept())
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <input 
                            type="text" 
                            value="{{ auth()->user()->department->code }} - {{ auth()->user()->department->name }}" 
                            disabled 
                            class="w-full px-[8px] h-[28px] bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono font-bold text-slate-800 dark:text-slate-300 cursor-not-allowed"
                        >
                    @else
                        <select 
                            name="department_id" 
                            id="department_id" 
                            x-model="selectedDeptId"
                            required 
                            class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                            <option value="">-- Pilih Departemen --</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">
                                {{ $dept->code }} - {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    @endif
                    @error('department_id') <span class="text-rose-500 text-[10px] block font-bold">{{ $message }}</span> @enderror
                </div>

                <!-- SubDepartment Selection (Child Hierarchy: Parent > Child) -->
                <div class="flex flex-col gap-[2px]">
                    <label for="sub_department_id" class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                        SUBDEPARTEMEN / DIVISI <span class="text-amber-600 font-bold">(Unit Kerja)</span>
                    </label>

                    @if(auth()->user()->isPicDept())
                        <select 
                            name="sub_department_id" 
                            id="sub_department_id" 
                            class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-amber-300 dark:border-amber-700 rounded-[3px] text-[11px] font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                            <option value="">-- Pilih Subdepartemen --</option>
                            @if(auth()->user()->department && auth()->user()->department->subDepartments->count() > 0)
                                @foreach(auth()->user()->department->subDepartments as $sub)
                                    <option value="{{ $sub->id }}" {{ old('sub_department_id', auth()->user()->sub_department_id) == $sub->id ? 'selected' : '' }}>
                                        {{ $sub->name }} {{ $sub->code ? '('.$sub->code.')' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>(Unit Utama - Belum ada subdepartemen)</option>
                            @endif
                        </select>
                    @else
                        <!-- Dynamic Cascading SubDepartment for Super Admin -->
                        <select 
                            name="sub_department_id" 
                            id="sub_department_id" 
                            x-model="selectedSubDeptId"
                            class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                            <option value="">-- Pilih Subdepartemen --</option>
                            <template x-for="sub in subDeptList" :key="sub.id">
                                <option :value="sub.id" x-text="sub.name + (sub.code ? ' (' + sub.code + ')' : '')"></option>
                            </template>
                            <template x-if="selectedDeptId && subDeptList.length === 0">
                                <option value="" disabled>(Tidak ada subdepartemen terdaftar)</option>
                            </template>
                        </select>
                    @endif
                    @error('sub_department_id') <span class="text-rose-500 text-[10px] block font-bold">{{ $message }}</span> @enderror
                </div>

            </div>

            <!-- 2. Document Types Multi-Check Selection GroupBox (Dynamic Catalog & Custom Document) -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-[8px] sm:p-[10px] rounded-[3px] bg-slate-50/70 dark:bg-slate-900/60 space-y-[6px]">
                <legend class="px-[6px] text-[10px] font-bold uppercase text-amber-700 dark:text-amber-400 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[2px] shadow-2xs flex items-center gap-[4px]">
                    <i data-lucide="check-square" class="w-[12px] h-[12px] text-amber-500"></i>
                    <span>TIPE DOKUMEN DALAM SATU BOX BENDEL <span class="text-rose-500">*</span> (Pilihan Formulir Ceklis)</span>
                </legend>

                <!-- Helper buttons, Counter Ribbon, & Add to Catalog Button -->
                <div class="flex flex-wrap items-center justify-between gap-[6px] pb-[4px] border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-[6px] text-[10px] text-slate-600 dark:text-slate-400 font-mono">
                        <span>Terpilih:</span>
                        <span class="px-[6px] py-[1px] bg-amber-500 text-slate-950 font-black rounded font-mono text-[10px]" x-text="selectedTypes.length + ' Dokumen'"></span>
                        <span class="text-slate-400 text-[9px] hidden sm:inline">(Bisa mencakup beberapa jenis dokumen dalam satu box)</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-[4px] text-[10px] font-mono">
                        <button type="button" @click="selectAll()" class="px-[6px] py-[1px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded border border-slate-300 dark:border-slate-700 font-bold transition">
                            Pilih Semua
                        </button>
                        <button type="button" @click="clearAll()" class="px-[6px] py-[1px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded border border-slate-300 dark:border-slate-700 font-bold transition">
                            Kosongkan
                        </button>
                        <span class="text-slate-300 dark:text-slate-700">|</span>
                        <button type="button" @click="presetFinance()" class="px-[6px] py-[1px] bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/50 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 rounded border border-amber-300 dark:border-amber-800 font-bold transition">
                            + Paket Finance/Purchasing
                        </button>
                        <button type="button" @click="presetHRD()" class="px-[6px] py-[1px] bg-sky-100 hover:bg-sky-200 dark:bg-sky-950/50 dark:hover:bg-sky-900/60 text-sky-800 dark:text-sky-300 rounded border border-sky-300 dark:border-sky-800 font-bold transition">
                            + Paket HRD/GA
                        </button>

                        <span class="text-slate-300 dark:text-slate-700">|</span>
                        
                        <!-- HIGH VISIBILITY ACTION: TAMBAH KATALOG DOKUMEN (PIC DEPT FULL CONTROL) -->
                        <button 
                            type="button" 
                            @click="openAddDocModal = true" 
                            class="px-[8px] py-[1px] bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black rounded border border-amber-600 shadow-2xs transition flex items-center gap-[3px]"
                            title="Tambahkan jenis dokumen baru ke dalam katalog sistem"
                        >
                            <i data-lucide="plus-circle" class="w-[11px] h-[11px] text-slate-950"></i>
                            <span>+ Tambah Katalog Dokumen</span>
                        </button>
                    </div>
                </div>

                <!-- Dynamic Checkbox Grid loaded from Database Catalog -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-[6px]">
                    <template x-for="doc in docList" :key="doc.id">
                        <label 
                            class="relative flex items-start gap-[6px] p-[6px] rounded-[3px] border cursor-pointer select-none transition"
                            :class="selectedTypes.includes(doc.id) 
                                ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-400 dark:border-amber-600 shadow-2xs' 
                                : 'bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                        >
                            <input 
                                type="checkbox" 
                                name="document_types[]" 
                                :value="doc.id" 
                                x-model="selectedTypes"
                                @change="checkCustom()"
                                class="mt-[2px] rounded border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-0"
                            >
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-[4px]">
                                    <span class="font-bold text-[10.5px] block leading-tight font-mono" :class="selectedTypes.includes(doc.id) ? 'text-amber-900 dark:text-amber-300' : 'text-slate-800 dark:text-slate-200'" x-text="doc.name"></span>
                                    <template x-if="!doc.is_preset">
                                        <span class="px-[3px] py-[0.5px] bg-purple-100 dark:bg-purple-950/60 border border-purple-300 dark:border-purple-800 text-purple-700 dark:text-purple-300 text-[8px] font-bold rounded" title="Custom Catalog">Custom</span>
                                    </template>
                                </div>
                                <span class="text-[9px] text-slate-500 dark:text-slate-400 block leading-tight mt-[1px]" x-text="doc.desc"></span>
                            </div>
                        </label>
                    </template>
                </div>

                <!-- Custom Document Type Input (shown if LAINNYA is checked) -->
                <div x-show="showCustom" x-transition class="pt-[4px] border-t border-slate-200 dark:border-slate-800 flex flex-col gap-[2px]">
                    <label for="custom_document_type" class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase">
                        Keterangan Spesifik Dokumen Lainnya (Spesifik Box Ini):
                    </label>
                    <input 
                        type="text" 
                        name="custom_document_type" 
                        id="custom_document_type" 
                        value="{{ old('custom_document_type') }}" 
                        placeholder="Contoh: Polis Asuransi Kendaraan Operasional, Bilyet Deposito No 123..."
                        class="w-full px-[8px] h-[28px] bg-white dark:bg-slate-950 border border-amber-300 dark:border-amber-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                    >
                </div>

                @error('document_types') 
                    <span class="text-rose-500 text-[10px] font-bold block pt-[2px]">{{ $message }}</span> 
                @enderror
            </fieldset>

            <!-- 3. Archive Title -->
            <div class="flex flex-col gap-[2px]">
                <label for="title" class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                    JUDUL / NAMA BERKAS ARSIP <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    value="{{ old('title') }}" 
                    required
                    placeholder="Contoh: Laporan Keuangan & Faktur Pajak Q1 2026" 
                    class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                >
                @error('title') <span class="text-rose-500 text-[10px] block font-bold">{{ $message }}</span> @enderror
            </div>

            <!-- 4. Period Range GroupBox -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-[8px] rounded-[3px] bg-slate-50 dark:bg-slate-900/60">
                <legend class="px-[4px] text-[10px] font-bold uppercase text-amber-600 dark:text-amber-400">Periode Berkas Dokumen</legend>
                
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-[8px]">
                    <div class="flex flex-col gap-[2px]">
                        <label for="period_start_date" class="text-[10px] font-bold text-slate-700 dark:text-slate-400">Tanggal Mulai <span class="text-rose-500">*</span></label>
                        <input 
                            type="date" 
                            name="period_start_date" 
                            id="period_start_date" 
                            value="{{ old('period_start_date', date('Y-01-01')) }}" 
                            required 
                            class="w-full px-[6px] h-[28px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <div class="flex flex-col gap-[2px]">
                        <label for="period_end_date" class="text-[10px] font-bold text-slate-700 dark:text-slate-400">Tanggal Selesai <span class="text-rose-500">*</span></label>
                        <input 
                            type="date" 
                            name="period_end_date" 
                            id="period_end_date" 
                            value="{{ old('period_end_date', date('Y-03-31')) }}" 
                            required 
                            class="w-full px-[6px] h-[28px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <div class="flex flex-col gap-[2px]">
                        <label for="period_text" class="text-[10px] font-bold text-slate-700 dark:text-slate-400">Label Periode</label>
                        <input 
                            type="text" 
                            name="period_text" 
                            id="period_text" 
                            value="{{ old('period_text', 'Januari - Maret 2026') }}" 
                            placeholder="Contoh: Q1 2026" 
                            class="w-full px-[6px] h-[28px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <div class="flex flex-col gap-[2px]">
                        <label for="period_yy_mm" class="text-[10px] font-bold text-slate-700 dark:text-slate-400">Format YY-MM</label>
                        <input 
                            type="text" 
                            name="period_yy_mm" 
                            id="period_yy_mm" 
                            value="{{ old('period_yy_mm', date('y-m')) }}" 
                            placeholder="26-03" 
                            maxlength="7"
                            class="w-full px-[6px] h-[28px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition font-bold"
                        >
                    </div>
                </div>
            </fieldset>

            <!-- 5. Retention & Wadah Fisik -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-[8px]">
                <div class="flex flex-col gap-[2px]">
                    <label for="retention_years" class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                        RETENSI SIMPAN (TAHUN) <span class="text-rose-500">*</span>
                    </label>
                    <select name="retention_years" id="retention_years" required class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="1" {{ old('retention_years', 5) == 1 ? 'selected' : '' }}>1 Tahun</option>
                        <option value="2" {{ old('retention_years', 5) == 2 ? 'selected' : '' }}>2 Tahun</option>
                        <option value="3" {{ old('retention_years', 5) == 3 ? 'selected' : '' }}>3 Tahun</option>
                        <option value="4" {{ old('retention_years', 5) == 4 ? 'selected' : '' }}>4 Tahun</option>
                        <option value="5" {{ old('retention_years', 5) == 5 ? 'selected' : '' }}>5 Tahun (Maksimal Bawaan)</option>
                    </select>
                </div>

                <div class="flex flex-col gap-[2px]">
                    <label for="physical_condition" class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                        KONDISI / WADAH FISIK BERKAS <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="physical_condition" 
                        id="physical_condition" 
                        value="{{ old('physical_condition', 'Baik / Map Binder Hardcover') }}" 
                        required 
                        placeholder="Contoh: Baik / Box Karton Standard / Map Plastik" 
                        class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                </div>
            </div>

            <!-- 6. Content Description -->
            <div class="flex flex-col gap-[2px]">
                <label for="content_description" class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                    RINCIAN ISI BERKAS & METADATA <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="content_description" 
                    id="content_description" 
                    rows="3" 
                    required 
                    placeholder="Tuliskan daftar dokumen detail yang ada di dalam box arsip ini (misal: Bukti Kas Keluar No 001-150, Faktur Pajak PPN, dsb)..." 
                    class="w-full p-[6px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                >{{ old('content_description') }}</textarea>
            </div>

            <!-- 7. Attachment Scans Section -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-[8px] rounded-[3px] bg-amber-500/5 dark:bg-amber-950/20">
                <legend class="px-[4px] text-[10px] font-bold uppercase text-amber-700 dark:text-amber-400 flex items-center gap-[4px]">
                    <i data-lucide="file-check" class="w-[12px] h-[12px]"></i>
                    <span>Upload Berkas Digital & Scan Formulir</span>
                </legend>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-[8px]">
                    <div class="flex flex-col gap-[2px]">
                        <label for="scan_input_form" class="text-[10px] font-bold text-slate-700 dark:text-slate-400">Scan Formulir Input</label>
                        <input 
                            type="file" 
                            name="scan_input_form" 
                            id="scan_input_form" 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full px-[6px] py-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[10px] font-mono text-slate-700 dark:text-slate-300"
                        >
                    </div>

                    <div class="flex flex-col gap-[2px]">
                        <label for="scan_approval_input" class="text-[10px] font-bold text-slate-700 dark:text-slate-400">Scan Bukti Approval</label>
                        <input 
                            type="file" 
                            name="scan_approval_input" 
                            id="scan_approval_input" 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full px-[6px] py-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[10px] font-mono text-slate-700 dark:text-slate-300"
                        >
                    </div>

                    <div class="flex flex-col gap-[2px]">
                        <label for="file" class="text-[10px] font-bold text-slate-700 dark:text-slate-400">Lampiran Digital Lain</label>
                        <input 
                            type="file" 
                            name="file" 
                            id="file" 
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.zip"
                            class="w-full px-[6px] py-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[10px] font-mono text-slate-700 dark:text-slate-300"
                        >
                    </div>
                </div>
            </fieldset>

            <!-- Form Actions -->
            <div class="pt-[8px] border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-[6px]">
                <a href="{{ route('archives.index') }}" class="px-[12px] h-[28px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-[3px] text-[11px] font-mono font-bold border border-slate-300 dark:border-slate-700 transition flex items-center">
                    Batal
                </a>
                <button type="submit" class="px-[14px] h-[28px] bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-mono font-black text-[11px] rounded-[3px] border border-amber-600 shadow-2xs transition flex items-center gap-[4px]">
                    <i data-lucide="send" class="w-[12px] h-[12px] text-slate-950"></i>
                    <span>Submit Booking & Pengajuan Arsip</span>
                </button>
            </div>
        </form>
    </fieldset>

    <!-- WINDOWS FORM DIALOG MODAL 1: ADD COMPANY ENTITY (PIC DEPT FULL CONTROL) -->
    <div x-show="openAddCompanyModal" 
         x-cloak 
         x-data="{ 
             posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
             startDrag(e) { 
                 if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea')) return; 
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
            
            <!-- Window Title Bar (Draggable) -->
            <div @mousedown="startDrag($event)" 
                 :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                 title="Klik & tahan untuk menggeser form (Drag to move)"
                 class="bg-gradient-to-r from-slate-800 via-slate-700 to-amber-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-amber-400"></i> frmCompanyAdd : Tambah Perusahaan Entitas Baru
                </span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAddCompanyModal = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form @submit.prevent="submitCompany()" class="p-4 space-y-3 font-sans text-xs">
                <div x-show="companyError" class="p-2 rounded bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-[11px] font-mono font-bold" x-text="companyError"></div>

                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Data Perusahaan Entitas</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA PERUSAHAAN ENTITAS <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="newCompany.name" required placeholder="Contoh: PT Indraco Niaga Makmur" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE SINGKATAN (OPSIONAL)</label>
                        <input type="text" x-model="newCompany.code" maxlength="20" placeholder="INM" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono uppercase font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI / KETERANGAN</label>
                        <textarea x-model="newCompany.description" rows="2" placeholder="Keterangan unit usaha atau divisi bisnis..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAddCompanyModal = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="companySubmitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1 shadow-sm">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="companySubmitting"></i>
                        <span x-text="companySubmitting ? 'Menyimpan...' : 'Simpan & Pilih (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 2: ADD CUSTOM DOCUMENT TO CATALOG (PIC DEPT FULL CONTROL) -->
    <div x-show="openAddDocModal" 
         x-cloak 
         x-data="{ 
             posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, 
             startDrag(e) { 
                 if (e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea')) return; 
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
            
            <!-- Window Title Bar (Draggable) -->
            <div @mousedown="startDrag($event)" 
                 :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                 title="Klik & tahan untuk menggeser form (Drag to move)"
                 class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none">
                    <i data-lucide="file-plus" class="w-3.5 h-3.5 text-amber-400"></i> frmDocumentCatalogAdd : Tambah Tipe Dokumen ke Katalog
                </span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAddDocModal = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form @submit.prevent="submitDocType()" class="p-4 space-y-3 font-sans text-xs">
                <div x-show="docError" class="p-2 rounded bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-[11px] font-mono font-bold" x-text="docError"></div>

                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Formulir Katalog Dokumen Baru</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE / SINGKATAN DOKUMEN <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="newDoc.code" required maxlength="50" placeholder="CTH: SERTIFIKAT, BILYET, ASURANSI" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono uppercase font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA LENGKAP JENIS DOKUMEN <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="newDoc.name" required placeholder="Contoh: Sertifikat Legalitas / Hak Milik Tanah" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">DESKRIPSI / KETERANGAN RINGKAS</label>
                        <input type="text" x-model="newDoc.description" placeholder="Contoh: Bukti Kepemilikan & Aset Perusahaan" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">LINGKUP PENGGUNAAN KATALOG</label>
                        <div class="flex items-center gap-4 pt-1 font-mono text-xs">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" x-model="newDoc.scope" value="global" class="text-amber-500 focus:ring-0">
                                <span>Global (Semua Departemen)</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" x-model="newDoc.scope" value="dept" class="text-amber-500 focus:ring-0">
                                <span>Khusus Departemen Ini</span>
                            </label>
                        </div>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAddDocModal = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="docSubmitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1 shadow-sm">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="docSubmitting"></i>
                        <span x-text="docSubmitting ? 'Menyimpan...' : 'Tambahkan & Ceklis (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function archiveCreateForm() {
    return {
        // Companies
        companyList: @json($companies),
        selectedCompany: '{{ old('company_name', $companies->first()->name ?? 'PT Indraco Global') }}',
        openAddCompanyModal: false,
        newCompany: { name: '', code: '', description: '' },
        companySubmitting: false,
        companyError: '',

        // Departments & Subdepartments
        allDepts: @json($departments),
        isPicDept: {{ auth()->user()->isPicDept() ? 'true' : 'false' }},
        selectedDeptId: '{{ old('department_id', auth()->user()->isPicDept() ? auth()->user()->department_id : '') }}',
        selectedSubDeptId: '{{ old('sub_department_id', auth()->user()->sub_department_id ?? '') }}',

        get subDeptList() {
            if (!this.selectedDeptId) return [];
            const dept = this.allDepts.find(d => d.id == this.selectedDeptId);
            return dept && dept.sub_departments ? dept.sub_departments : [];
        },

        // Document Types & Catalog
        docList: {!! json_encode($availableDocTypes->map(function($d) {
            return [
                'id' => $d->code,
                'code' => $d->code,
                'name' => $d->name,
                'desc' => $d->description ?? $d->name,
                'is_preset' => (bool)$d->is_preset,
            ];
        })->values()) !!},
        selectedTypes: @json($oldDocTypes),
        showCustom: {{ in_array('LAINNYA', $oldDocTypes) ? 'true' : 'false' }},
        openAddDocModal: false,
        newDoc: { code: '', name: '', description: '', scope: 'global' },
        docSubmitting: false,
        docError: '',

        toggle(type) {
            if (this.selectedTypes.includes(type)) {
                this.selectedTypes = this.selectedTypes.filter(t => t !== type);
            } else {
                this.selectedTypes.push(type);
            }
            this.checkCustom();
        },
        checkCustom() {
            this.showCustom = this.selectedTypes.includes('LAINNYA');
        },
        selectAll() {
            this.selectedTypes = this.docList.map(d => d.id);
            this.checkCustom();
        },
        clearAll() {
            this.selectedTypes = [];
            this.checkCustom();
        },
        presetFinance() {
            const targets = ['PR', 'PO', 'SURAT JALAN', 'FAKTUR', 'FAKTUR PAJAK'];
            targets.forEach(t => {
                if (!this.selectedTypes.includes(t)) this.selectedTypes.push(t);
            });
            this.checkCustom();
        },
        presetHRD() {
            const targets = ['ABSENSI', 'UTILITY'];
            targets.forEach(t => {
                if (!this.selectedTypes.includes(t)) this.selectedTypes.push(t);
            });
            this.checkCustom();
        },

        // Submit new Company via AJAX
        submitCompany() {
            if (!this.newCompany.name.trim()) {
                this.companyError = 'Nama perusahaan wajib diisi.';
                return;
            }
            this.companySubmitting = true;
            this.companyError = '';

            fetch('{{ route('api.companies.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(this.newCompany)
            })
            .then(res => res.json())
            .then(data => {
                this.companySubmitting = false;
                if (data.success) {
                    this.companyList.push(data.company);
                    this.selectedCompany = data.company.name;
                    this.openAddCompanyModal = false;
                    this.newCompany = { name: '', code: '', description: '' };
                } else {
                    this.companyError = data.message || 'Gagal menambahkan perusahaan.';
                }
            })
            .catch(err => {
                this.companySubmitting = false;
                this.companyError = 'Terjadi kesalahan sistem.';
            });
        },

        // Submit new Document Type via AJAX
        submitDocType() {
            if (!this.newDoc.code.trim() || !this.newDoc.name.trim()) {
                this.docError = 'Kode dan nama dokumen wajib diisi.';
                return;
            }
            this.docSubmitting = true;
            this.docError = '';

            const payload = {
                code: this.newDoc.code,
                name: this.newDoc.name,
                description: this.newDoc.description,
                scope_department: this.newDoc.scope,
                department_id: (this.newDoc.scope === 'dept' && this.selectedDeptId) ? this.selectedDeptId : null
            };

            fetch('{{ route('api.document_types.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.docSubmitting = false;
                if (data.success) {
                    const newType = data.document_type;
                    this.docList.push(newType);
                    if (!this.selectedTypes.includes(newType.id)) {
                        this.selectedTypes.push(newType.id);
                    }
                    this.openAddDocModal = false;
                    this.newDoc = { code: '', name: '', description: '', scope: 'global' };
                    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 50);
                } else {
                    this.docError = data.message || 'Gagal menambahkan dokumen ke katalog.';
                }
            })
            .catch(err => {
                this.docSubmitting = false;
                this.docError = 'Terjadi kesalahan sistem.';
            });
        }
    };
}
</script>
@endsection
