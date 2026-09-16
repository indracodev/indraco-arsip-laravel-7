@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Draft & Booking Tempat Arsip - DMS PT Indraco')

@section('content')
<div class="w-full space-y-[10px] font-mono">
    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] shadow-2xs flex items-center justify-between gap-[8px]">
        <div class="flex items-center gap-[8px]">
            <span class="p-[4px] bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded-[3px]">
                <i data-lucide="file-plus" class="w-[15px] h-[15px]"></i>
            </span>
            <div>
                <h1 class="text-[12px] font-bold text-slate-900 dark:text-white uppercase tracking-wider">Form Pengajuan Draft & Booking Storage</h1>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Isi rincian berkas arsip dan periode retention untuk diverifikasi oleh PIC Gudang (TForm Window)</p>
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-[8px]">
                <!-- Company Name -->
                <div class="flex flex-col gap-[2px]">
                    <label for="company_name" class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                        PERUSAHAAN ENTIAS <span class="text-rose-500">*</span>
                    </label>
                    <select name="company_name" id="company_name" required class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="PT Indraco Global" {{ old('company_name') == 'PT Indraco Global' ? 'selected' : '' }}>PT Indraco Global</option>
                        <option value="PT Indraco Trading" {{ old('company_name') == 'PT Indraco Trading' ? 'selected' : '' }}>PT Indraco Trading</option>
                        <option value="PT Indraco Enterprise" {{ old('company_name') == 'PT Indraco Enterprise' ? 'selected' : '' }}>PT Indraco Enterprise</option>
                        <option value="PT Indraco International" {{ old('company_name') == 'PT Indraco International' ? 'selected' : '' }}>PT Indraco International</option>
                    </select>
                </div>

                <!-- Department Selection -->
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
                        <select name="department_id" id="department_id" required class="w-full px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                            <option value="">-- Pilih Departemen --</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->code }} - {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            @php
                $oldDocTypes = old('document_types', []);
                $availableDocTypes = [
                    ['id' => 'PR', 'name' => 'PR (Purchase Requisition)', 'desc' => 'Permintaan Pembelian'],
                    ['id' => 'PO', 'name' => 'PO (Purchase Order)', 'desc' => 'Pesanan Pembelian'],
                    ['id' => 'SURAT JALAN', 'name' => 'Surat Jalan (DO)', 'desc' => 'Bukti Kirim & Terima'],
                    ['id' => 'FAKTUR', 'name' => 'Faktur / Invoice', 'desc' => 'Tagihan Pembelian/Jual'],
                    ['id' => 'FAKTUR PAJAK', 'name' => 'Faktur Pajak', 'desc' => 'Faktur Pajak Standar'],
                    ['id' => 'ABSENSI', 'name' => 'Absensi / Payroll', 'desc' => 'Presensi & Rekap Gaji'],
                    ['id' => 'KONTRAK', 'name' => 'Kontrak / SPK', 'desc' => 'Perjanjian & Legalitas'],
                    ['id' => 'UTILITY', 'name' => 'Utility / Bukti Bayar', 'desc' => 'Tagihan Operasional'],
                    ['id' => 'DATA SAMPLE', 'name' => 'Data Sample', 'desc' => 'Uji Lab & Quality Control'],
                    ['id' => 'LAINNYA', 'name' => 'Lainnya (Spesifik)', 'desc' => 'Dokumen spesifik lain'],
                ];
            @endphp

            <!-- Document Types Multi-Check Selection GroupBox -->
            <fieldset 
                x-data="{
                    selectedTypes: {{ json_encode($oldDocTypes) }},
                    showCustom: {{ in_array('LAINNYA', $oldDocTypes) ? 'true' : 'false' }},
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
                        this.selectedTypes = ['PR', 'PO', 'SURAT JALAN', 'FAKTUR', 'FAKTUR PAJAK', 'ABSENSI', 'KONTRAK', 'UTILITY', 'DATA SAMPLE', 'LAINNYA'];
                        this.checkCustom();
                    },
                    clearAll() {
                        this.selectedTypes = [];
                        this.checkCustom();
                    },
                    presetFinance() {
                        this.selectedTypes = ['PR', 'PO', 'SURAT JALAN', 'FAKTUR', 'FAKTUR PAJAK'];
                        this.checkCustom();
                    },
                    presetHRD() {
                        this.selectedTypes = ['ABSENSI', 'UTILITY'];
                        this.checkCustom();
                    }
                }"
                class="border border-slate-300 dark:border-slate-800 p-[8px] sm:p-[10px] rounded-[3px] bg-slate-50/70 dark:bg-slate-900/60 space-y-[6px]"
            >
                <legend class="px-[6px] text-[10px] font-bold uppercase text-amber-700 dark:text-amber-400 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[2px] shadow-2xs flex items-center gap-[4px]">
                    <i data-lucide="check-square" class="w-[12px] h-[12px] text-amber-500"></i>
                    <span>TIPE DOKUMEN DALAM SATU BOX BENDEL <span class="text-rose-500">*</span> (Pilihan Formulir Ceklis)</span>
                </legend>

                <!-- Helper buttons & counter ribbon -->
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
                    </div>
                </div>

                <!-- Checkbox Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-[6px]">
                    @foreach($availableDocTypes as $doc)
                    <label 
                        class="relative flex items-start gap-[6px] p-[6px] rounded-[3px] border cursor-pointer select-none transition"
                        :class="selectedTypes.includes('{{ $doc['id'] }}') 
                            ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-400 dark:border-amber-600 shadow-2xs' 
                            : 'bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                    >
                        <input 
                            type="checkbox" 
                            name="document_types[]" 
                            value="{{ $doc['id'] }}" 
                            x-model="selectedTypes"
                            @change="checkCustom()"
                            class="mt-[2px] rounded border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-0"
                        >
                        <div class="flex-1 min-w-0">
                            <span class="font-bold text-[10.5px] block leading-tight font-mono" :class="selectedTypes.includes('{{ $doc['id'] }}') ? 'text-amber-900 dark:text-amber-300' : 'text-slate-800 dark:text-slate-200'">
                                {{ $doc['name'] }}
                            </span>
                            <span class="text-[9px] text-slate-500 dark:text-slate-400 block leading-tight mt-[1px]">
                                {{ $doc['desc'] }}
                            </span>
                        </div>
                    </label>
                    @endforeach
                </div>

                <!-- Custom Document Type Input (shown if LAINNYA is checked) -->
                <div x-show="showCustom" x-transition class="pt-[4px] border-t border-slate-200 dark:border-slate-800 flex flex-col gap-[2px]">
                    <label for="custom_document_type" class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase">
                        Keterangan Dokumen Tambahan (Lainnya):
                    </label>
                    <input 
                        type="text" 
                        name="custom_document_type" 
                        id="custom_document_type" 
                        value="{{ old('custom_document_type') }}" 
                        placeholder="Contoh: Polis Asuransi Kendaraan, Bilyet Deposito, Bukti Setor Pajak..."
                        class="w-full px-[8px] h-[28px] bg-white dark:bg-slate-950 border border-amber-300 dark:border-amber-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                    >
                </div>

                @error('document_types') 
                    <span class="text-rose-500 text-[10px] font-bold block pt-[2px]">{{ $message }}</span> 
                @enderror
            </fieldset>

            <!-- Archive Title -->
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

            <!-- Period Range GroupBox -->
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
                        <label for="period_yy_mm" class="text-[10px] font-bold text-slate-700 dark:text-slate-400">Format YY-MM</label>
                        <input 
                            type="text" 
                            name="period_yy_mm" 
                            id="period_yy_mm" 
                            value="{{ old('period_yy_mm', date('y-m')) }}" 
                            placeholder="e.g. 26-03" 
                            class="w-full px-[6px] h-[28px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <div class="flex flex-col gap-[2px]">
                        <label for="period_text" class="text-[10px] font-bold text-slate-700 dark:text-slate-400">Label Periode Custom</label>
                        <input 
                            type="text" 
                            name="period_text" 
                            id="period_text" 
                            value="{{ old('period_text') }}" 
                            placeholder="e.g. Januari - Maret 2026" 
                            class="w-full px-[6px] h-[28px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>
                </div>
            </fieldset>

            <!-- Retention & Physical Condition -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-[8px]">
                <div class="flex flex-col gap-[2px]">
                    <label for="retention_years" class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400">
                        MASA SIMPAN RETENTION (TAHUN) <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex items-center gap-[6px]">
                        <input 
                            type="number" 
                            name="retention_years" 
                            id="retention_years" 
                            value="{{ old('retention_years', 5) }}" 
                            min="1" 
                            max="5" 
                            required 
                            class="w-[80px] px-[8px] h-[28px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                        <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold">Maksimal 5 Tahun</span>
                    </div>
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

            <!-- Content Description -->
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

            <!-- Attachment Scans section -->
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
</div>
@endsection
