@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Input & Label Box Arsip - DMS PT Indraco')

@section('content')
<div class="w-full space-y-3 font-mono" 
     x-data="{
         departments: {!! json_encode($departments) !!},
         selectedDeptId: '{{ old('department_id', auth()->user()->isPicDept() ? auth()->user()->department_id : ($departments->first()->id ?? '')) }}',
         selectedSubDeptId: '{{ old('sub_department_id', '') }}',
         subDepartments: [],
         isCustomDocName: {{ old('is_custom_doc_name') ? 'true' : 'false' }},
         customDocName: '{{ old('custom_doc_name', '') }}',
         title: '{{ old('title', '') }}',
         periodeDoc: '{{ old('periode_doc', date('Y/m')) }}',
         tglPenyerahan: '{{ old('tgl_penyerahan', date('Y-m-d')) }}',
         masaSimpanCustom: '{{ old('masa_simpan_custom', '') }}',
         expiryDatePreview: '-',
         effectiveYearsPreview: 5,
         
         init() {
             this.updateSubDepartments();
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
             const regex = /^\d{4}\/(0[1-9]|1[0-2])$/;
             if (!regex.test(this.periodeDoc)) {
                 this.expiryDatePreview = 'Format Periode Harus YYYY/MM';
                 return;
             }
             
             const [yearStr, monthStr] = this.periodeDoc.split('/');
             const year = parseInt(yearStr);
             const month = parseInt(monthStr);
             
             // Base retention years logic
             let years = 5;
             if (this.masaSimpanCustom && parseInt(this.masaSimpanCustom) > 0) {
                 years = parseInt(this.masaSimpanCustom);
             } else if (this.selectedSubDeptId) {
                 const sub = this.subDepartments.find(s => s.id == this.selectedSubDeptId);
                 if (sub && sub.retention_years > 0) {
                     years = parseInt(sub.retention_years);
                 }
             } else {
                 const dept = this.departments.find(d => d.id == this.selectedDeptId);
                 if (dept && dept.retention_years > 0) {
                     years = parseInt(dept.retention_years);
                 }
             }
             
             this.effectiveYearsPreview = years;
             
             const expiryYear = year + years;
             // Last day of month
             const lastDay = new Date(expiryYear, month, 0).getDate();
             const padMonth = month.toString().padStart(2, '0');
             const padDay = lastDay.toString().padStart(2, '0');
             this.expiryDatePreview = `${padDay}/${padMonth}/${expiryYear}`;
         }
     }">

    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="file-plus" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Form Pengajuan Box Arsip (TB 30g) & Cetak Label A5</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">1 Box = 1 Periode (YYYY/MM) | Masa simpan otomatis dari tanggal periode dokumen</p>
            </div>
        </div>

        <a href="{{ route('archives.index') }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500"></i>
            <span>Kembali (Esc)</span>
        </a>
    </div>

    <!-- DELPHI TGROUPBOX MAIN FORM CARD -->
    <fieldset class="border border-slate-300 dark:border-slate-800 p-4 rounded bg-white dark:bg-slate-950 text-xs shadow-sm">
        <legend class="px-2 text-[11px] font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
            <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-500"></i>
            [Form Controls] Input Metadata Box Arsip & Periode Dokumen
        </legend>

        <form action="{{ route('archives.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 pt-1">
            @csrf

            <!-- BARIS 1: Entitas, Departemen, & Sub-Departemen -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Company Name -->
                <div>
                    <label for="company_name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        PERUSAHAAN ENTITAS <span class="text-rose-500">*</span>
                    </label>
                    <select name="company_name" id="company_name" required class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="PT Indraco Jaya Perkasa" {{ old('company_name') == 'PT Indraco Jaya Perkasa' ? 'selected' : '' }}>PT Indraco Jaya Perkasa</option>
                        <option value="PT Indraco Global" {{ old('company_name') == 'PT Indraco Global' ? 'selected' : '' }}>PT Indraco Global</option>
                        <option value="PT Indraco Trading" {{ old('company_name') == 'PT Indraco Trading' ? 'selected' : '' }}>PT Indraco Trading</option>
                        <option value="PT Indraco Enterprise" {{ old('company_name') == 'PT Indraco Enterprise' ? 'selected' : '' }}>PT Indraco Enterprise</option>
                        <option value="PT Indraco International" {{ old('company_name') == 'PT Indraco International' ? 'selected' : '' }}>PT Indraco International</option>
                    </select>
                </div>

                <!-- Department Selection (SIDAR Sync) -->
                <div>
                    <label for="department_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        DEPARTEMEN (SIDAR) <span class="text-rose-500">*</span>
                    </label>
                    @if(auth()->user()->isPicDept())
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <input 
                            type="text" 
                            value="{{ auth()->user()->department->code }} - {{ auth()->user()->department->name }}" 
                            disabled 
                            class="w-full px-2.5 py-1 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-800 dark:text-slate-300 cursor-not-allowed"
                        >
                    @else
                        <select name="department_id" id="department_id" x-model="selectedDeptId" @change="updateSubDepartments()" required class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
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
                    <label for="sub_department_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        SUB-DEPARTEMEN <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <select name="sub_department_id" id="sub_department_id" x-model="selectedSubDeptId" @change="calculateRetention()" class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="">-- Pilih Sub-Departemen --</option>
                        <template x-for="sub in subDepartments" :key="sub.id">
                            <option :value="sub.id" x-text="`${sub.code} - ${sub.name}`" :selected="sub.id == selectedSubDeptId"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- BARIS 2: Jenis Dokumen & Custom Nama Dokumen Switcher -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
                <div>
                    <label for="document_type" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        KATEGORI DOKUMEN <span class="text-rose-500">*</span>
                    </label>
                    <select name="document_type" id="document_type" required class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="FAKTUR_PAJAK" {{ old('document_type') == 'FAKTUR_PAJAK' ? 'selected' : '' }}>FAKTUR PAJAK & KEUANGAN</option>
                        <option value="KONTRAK_KERJA" {{ old('document_type') == 'KONTRAK_KERJA' ? 'selected' : '' }}>KONTRAK KERJA / SDM</option>
                        <option value="MOU_SPONSOR" {{ old('document_type') == 'MOU_SPONSOR' ? 'selected' : '' }}>MOU / KERJASAMA / SALES</option>
                        <option value="SURAT_JALAN" {{ old('document_type') == 'SURAT_JALAN' ? 'selected' : '' }}>SURAT JALAN & LOGISTIK</option>
                        <option value="PRODUKSI_QC" {{ old('document_type') == 'PRODUKSI_QC' ? 'selected' : '' }}>PRODUKSI & QUALITY CONTROL</option>
                        <option value="UMUM" {{ old('document_type') == 'UMUM' ? 'selected' : '' }}>UMUM / LAIN-LAIN</option>
                    </select>
                </div>

                <!-- Custom Nama Dokumen Toggle -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                            JUDUL / NAMA DOKUMEN <span class="text-rose-500">*</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="is_custom_doc_name" value="1" x-model="isCustomDocName" class="rounded border-slate-300 text-amber-500 focus:ring-amber-400 h-3.5 w-3.5">
                            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">Aktifkan Custom Nama Dokumen</span>
                        </label>
                    </div>

                    <!-- Input Nama Dokumen Standar -->
                    <div x-show="!isCustomDocName">
                        <input 
                            type="text" 
                            name="title" 
                            id="title" 
                            x-model="title"
                            placeholder="Contoh: Laporan Keuangan & Faktur Pajak Q1" 
                            class="delphi-input w-full px-3 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <!-- Input Custom Nama Dokumen -->
                    <div x-show="isCustomDocName" x-transition>
                        <input 
                            type="text" 
                            name="custom_doc_name" 
                            id="custom_doc_name" 
                            x-model="customDocName"
                            placeholder="Ketik nama/judul dokumen kustom jika tidak ada pada master data..." 
                            class="delphi-input w-full px-3 py-1 bg-amber-500/10 dark:bg-amber-950/30 border-2 border-amber-500 rounded text-xs font-mono font-bold text-amber-950 dark:text-amber-200 placeholder-amber-600/50 focus:outline-none focus:border-amber-600 transition"
                        >
                    </div>
                    @error('title') <span class="text-rose-500 text-[11px] mt-1 block font-bold">{{ $message }}</span> @enderror
                    @error('custom_doc_name') <span class="text-rose-500 text-[11px] mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- BARIS 3: Periode Dokumen (1 Box = 1 Periode YYYY/MM) & Tanggal Penyerahan -->
            <fieldset class="border border-amber-500/30 p-3 rounded bg-amber-500/5 dark:bg-amber-950/20">
                <legend class="px-2 text-[10px] font-bold uppercase text-amber-700 dark:text-amber-400 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Standarisasi 1 Box 1 Periode & Tanggal Serah Terima
                </legend>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="periode_doc" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">
                            PERIODE DOKUMEN (YYYY/MM) <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="periode_doc" 
                            id="periode_doc" 
                            x-model="periodeDoc"
                            @input="calculateRetention()"
                            placeholder="Contoh: 2026/09" 
                            required 
                            pattern="^\d{4}\/(0[1-9]|1[0-2])$"
                            title="Format harus YYYY/MM (contoh: 2026/09)"
                            class="w-full px-2.5 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-amber-500 transition"
                        >
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block mt-0.5">Aturan: 1 box hanya untuk 1 periode</span>
                        @error('periode_doc') <span class="text-rose-500 text-[11px] mt-0.5 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="tgl_penyerahan" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">
                            TGL. PENYERAHAN DOKUMEN <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="tgl_penyerahan" 
                            id="tgl_penyerahan" 
                            x-model="tglPenyerahan"
                            required 
                            class="w-full px-2.5 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block mt-0.5">Tanggal serah terima fisik ke Gudang</span>
                    </div>

                    <div>
                        <label for="period_text" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">Label Periode Tambahan</label>
                        <input 
                            type="text" 
                            name="period_text" 
                            id="period_text" 
                            value="{{ old('period_text') }}" 
                            placeholder="e.g. September 2026" 
                            class="w-full px-2.5 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>
                </div>
            </fieldset>

            <!-- BARIS 4: Kalkulasi Masa Simpan Otomatis & Custom Masa Simpan -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label for="masa_simpan_custom" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        CUSTOM MASA SIMPAN (TAHUN)
                    </label>
                    <input 
                        type="number" 
                        name="masa_simpan_custom" 
                        id="masa_simpan_custom" 
                        x-model="masaSimpanCustom"
                        @input="calculateRetention()"
                        min="1" 
                        max="30" 
                        placeholder="Default Dept" 
                        class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block mt-0.5">Kosongkan untuk memakai default departemen</span>
                </div>

                <!-- Preview Kalkulasi Masa Simpan -->
                <div class="md:col-span-2 p-2.5 rounded bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase block">Kalkulasi Otomatis Masa Simpan:</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 text-xs font-mono font-bold" x-text="`${effectiveYearsPreview} Tahun Masa Simpan`"></span>
                            <span class="text-xs text-slate-700 dark:text-slate-300 font-bold">s/d Tanggal Kedaluwarsa:</span>
                            <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-700 dark:text-rose-300 text-xs font-mono font-black" x-text="expiryDatePreview"></span>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 px-2 py-1 rounded bg-emerald-500/10">
                        Box Standar: TB 30g
                    </span>
                </div>
            </div>

            <!-- BARIS 5: Kondisi Fisik & Rincian Isi Metadata -->
            <div>
                <label for="physical_condition" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                    KONDISI / WADAH FISIK BERKAS <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="physical_condition" 
                    id="physical_condition" 
                    value="{{ old('physical_condition', 'Baik / Box Karton Standar TB 30g') }}" 
                    required 
                    class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                >
            </div>

            <div>
                <label for="content_description" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                    RINCIAN ISI DOKUMEN (DICETAK PADA FORM LABEL A5) <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="content_description" 
                    id="content_description" 
                    rows="4" 
                    required 
                    placeholder="Tuliskan butir-butir rincian dokumen di dalam box ini (akan dicetak pada label box A5 dengan auto-font scaling):
- Bukti Kas Masuk No. 001 - 150
- Bukti Kas Keluar Cabang Surabaya
- Faktur Pajak Masukan & Keluaran" 
                    class="delphi-input w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                >{{ old('content_description') }}</textarea>
            </div>

            <!-- BARIS 6: Attachment Scans section -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-amber-500/5 dark:bg-amber-950/20">
                <legend class="px-2 text-[10px] font-bold uppercase text-amber-700 dark:text-amber-400 flex items-center gap-1">
                    <i data-lucide="file-check" class="w-3.5 h-3.5"></i> Upload Berkas Digital & Scan Formulir
                </legend>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="scan_input_form" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">Scan Formulir Input Arsip</label>
                        <input 
                            type="file" 
                            name="scan_input_form" 
                            id="scan_input_form" 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-[11px] font-mono text-slate-700 dark:text-slate-300 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[11px] file:font-bold file:bg-amber-500/20 file:text-amber-700 dark:file:text-amber-400"
                        >
                    </div>

                    <div>
                        <label for="scan_approval_input" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">Scan Bukti Approval Input</label>
                        <input 
                            type="file" 
                            name="scan_approval_input" 
                            id="scan_approval_input" 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-[11px] font-mono text-slate-700 dark:text-slate-300 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[11px] file:font-bold file:bg-amber-500/20 file:text-amber-700 dark:file:text-amber-400"
                        >
                    </div>

                    <div>
                        <label for="file" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">Lampiran Digital Dokumen</label>
                        <input 
                            type="file" 
                            name="file" 
                            id="file" 
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.zip"
                            class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-[11px] font-mono text-slate-700 dark:text-slate-300 file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[11px] file:font-bold file:bg-slate-500/20 file:text-slate-700 dark:file:text-slate-400"
                        >
                    </div>
                </div>
            </fieldset>

            <!-- Form Actions -->
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
                <a href="{{ route('archives.index') }}" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-mono font-bold border border-slate-300 dark:border-slate-700 transition">
                    Batal
                </a>
                <button type="submit" class="px-4 py-1.5 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-mono font-black text-xs rounded border border-amber-600 shadow transition flex items-center gap-1.5">
                    <i data-lucide="send" class="w-3.5 h-3.5 text-slate-950"></i>
                    <span>Simpan & Ajukan Box Arsip (TB 30g)</span>
                </button>
            </div>
        </form>
    </fieldset>
</div>
@endsection
