@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Draft & Booking Tempat Arsip - DMS PT Indraco')

@section('content')
<div class="w-full space-y-3 font-mono">
    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="p-1.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded">
                <i data-lucide="file-plus" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Form Pengajuan Draft & Booking Storage</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Isi rincian berkas arsip dan periode retention untuk diverifikasi oleh PIC Gudang (TForm Window)</p>
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
            [Form Controls] Input Metadata & Rincian Berkas
        </legend>

        <form action="{{ route('archives.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 pt-1">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Company Name -->
                <div>
                    <label for="company_name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        PERUSAHAAN ENTIAS <span class="text-rose-500">*</span>
                    </label>
                    <select name="company_name" id="company_name" required class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="PT Indraco Global" {{ old('company_name') == 'PT Indraco Global' ? 'selected' : '' }}>PT Indraco Global</option>
                        <option value="PT Indraco Trading" {{ old('company_name') == 'PT Indraco Trading' ? 'selected' : '' }}>PT Indraco Trading</option>
                        <option value="PT Indraco Enterprise" {{ old('company_name') == 'PT Indraco Enterprise' ? 'selected' : '' }}>PT Indraco Enterprise</option>
                        <option value="PT Indraco International" {{ old('company_name') == 'PT Indraco International' ? 'selected' : '' }}>PT Indraco International</option>
                    </select>
                </div>

                <!-- Department Selection -->
                <div>
                    <label for="department_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        DEPARTEMEN PEMILIK <span class="text-rose-500">*</span>
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
                        <select name="department_id" id="department_id" required class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                            <option value="">-- Pilih Departemen --</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->code }} - {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <!-- Document Type -->
                <div>
                    <label for="document_type" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        JENIS DOKUMEN <span class="text-rose-500">*</span>
                    </label>
                    <select name="document_type" id="document_type" required class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="PR" {{ old('document_type') == 'PR' ? 'selected' : '' }}>PR (Purchase Requisition)</option>
                        <option value="ABSENSI" {{ old('document_type') == 'ABSENSI' ? 'selected' : '' }}>ABSENSI</option>
                        <option value="UTILITY" {{ old('document_type') == 'UTILITY' ? 'selected' : '' }}>UTILITY</option>
                        <option value="DATA SAMPLE" {{ old('document_type') == 'DATA SAMPLE' ? 'selected' : '' }}>DATA SAMPLE</option>
                        <option value="FAKTUR" {{ old('document_type') == 'FAKTUR' ? 'selected' : '' }}>FAKTUR / INVOICE</option>
                        <option value="KONTRAK" {{ old('document_type') == 'KONTRAK' ? 'selected' : '' }}>KONTRAK / PERJANJIAN</option>
                        <option value="LAINNYA" {{ old('document_type') == 'LAINNYA' ? 'selected' : '' }}>LAINNYA</option>
                    </select>
                </div>
            </div>

            <!-- Archive Title -->
            <div>
                <label for="title" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                    JUDUL / NAMA BERKAS ARSIP <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    value="{{ old('title') }}" 
                    required
                    placeholder="Contoh: Laporan Keuangan & Faktur Pajak Q1 2026" 
                    class="delphi-input w-full px-3 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                >
                @error('title') <span class="text-rose-500 text-[11px] mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>

            <!-- Period Range GroupBox -->
            <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-slate-50 dark:bg-slate-900/60">
                <legend class="px-2 text-[10px] font-bold uppercase text-amber-600 dark:text-amber-400">Periode Berkas Dokumen</legend>
                
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label for="period_start_date" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                        <input 
                            type="date" 
                            name="period_start_date" 
                            id="period_start_date" 
                            value="{{ old('period_start_date', date('Y-01-01')) }}" 
                            required 
                            class="w-full px-2.5 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <div>
                        <label for="period_end_date" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                        <input 
                            type="date" 
                            name="period_end_date" 
                            id="period_end_date" 
                            value="{{ old('period_end_date', date('Y-03-31')) }}" 
                            required 
                            class="w-full px-2.5 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <div>
                        <label for="period_yy_mm" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">Format YY-MM</label>
                        <input 
                            type="text" 
                            name="period_yy_mm" 
                            id="period_yy_mm" 
                            value="{{ old('period_yy_mm', date('y-m')) }}" 
                            placeholder="e.g. 26-03" 
                            class="w-full px-2.5 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>

                    <div>
                        <label for="period_text" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">Label Periode Custom</label>
                        <input 
                            type="text" 
                            name="period_text" 
                            id="period_text" 
                            value="{{ old('period_text') }}" 
                            placeholder="e.g. Januari - Maret 2026" 
                            class="w-full px-2.5 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                        >
                    </div>
                </div>
            </fieldset>

            <!-- Retention & Physical Condition -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="retention_years" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        MASA SIMPAN RETENTION (TAHUN) <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="number" 
                            name="retention_years" 
                            id="retention_years" 
                            value="{{ old('retention_years', 5) }}" 
                            min="1" 
                            max="5" 
                            required 
                            class="w-24 px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                        >
                        <span class="text-[11px] text-amber-600 dark:text-amber-400 font-bold">Maksimal 5 Tahun</span>
                    </div>
                </div>

                <div>
                    <label for="physical_condition" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                        KONDISI / WADAH FISIK BERKAS <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="physical_condition" 
                        id="physical_condition" 
                        value="{{ old('physical_condition', 'Baik / Map Binder Hardcover') }}" 
                        required 
                        placeholder="Contoh: Baik / Box Karton Standard / Map Plastik" 
                        class="w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                </div>
            </div>

            <!-- Content Description -->
            <div>
                <label for="content_description" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">
                    RINCIAN ISI BERKAS & METADATA <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="content_description" 
                    id="content_description" 
                    rows="3" 
                    required 
                    placeholder="Tuliskan daftar dokumen detail yang ada di dalam box arsip ini (misal: Bukti Kas Keluar No 001-150, Faktur Pajak PPN, dsb)..." 
                    class="delphi-input w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                >{{ old('content_description') }}</textarea>
            </div>

            <!-- Attachment Scans section -->
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
                        <label for="file" class="block text-[10px] font-bold text-slate-700 dark:text-slate-400 mb-1">Lampiran Digital Lainnya</label>
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
                    <span>Submit Booking & Pengajuan Arsip</span>
                </button>
            </div>
        </form>
    </fieldset>
</div>
@endsection

