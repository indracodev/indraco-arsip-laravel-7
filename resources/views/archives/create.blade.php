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
                    <select name="sub_department_id" id="sub_department_id" x-model="selectedSubDeptId" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="">-- Pilih Sub-Departemen (Induk) --</option>
                        <template x-for="sub in subDepartments" :key="sub.id">
                            <option :value="sub.id" x-text="`${sub.code} - ${sub.name}`" :selected="sub.id == selectedSubDeptId"></option>
                        </template>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 2: KATEGORI & IDENTITAS INDUK DOKUMEN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-blue-700 dark:text-blue-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-blue-500"></i>
                2. Kategori & Identitas Utama Kardus
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
                        <option value="MAINTENANCE" {{ old('document_type') == 'MAINTENANCE' ? 'selected' : '' }}>MAINTENANCE & FASILITAS</option>
                        <option value="UMUM" {{ old('document_type') == 'UMUM' ? 'selected' : '' }}>UMUM / LAIN-LAIN</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1 font-mono">
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                            JUDUL UTAMA / LABEL KARDUS <span class="text-slate-400 font-normal">(Opsional - otomatis mengambil butir ke-1)</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                            <input type="checkbox" name="is_custom_doc_name" value="1" x-model="isCustomDocName" class="rounded border-slate-300 text-amber-500 focus:ring-amber-400 h-3.5 w-3.5">
                            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">Custom Nama Dokumen</span>
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
                            placeholder="Ketik judul khusus kardus jika diperlukan..." 
                            class="w-full px-3 py-1.5 bg-amber-500/10 dark:bg-amber-950/30 border-2 border-amber-500 rounded text-xs font-mono font-bold text-amber-950 dark:text-amber-200 placeholder-amber-600/50 focus:outline-none focus:border-amber-600 transition"
                        >
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 3: TANGGAL PENYERAHAN & SPESIFIKASI WADAH -->
        <fieldset class="border border-amber-500/40 p-3.5 rounded bg-amber-500/5 dark:bg-amber-950/20 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-amber-800 dark:text-amber-400 bg-amber-100 dark:bg-slate-800 border border-amber-400 dark:border-amber-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-600"></i>
                3. Tanggal Penyerahan & Wadah Fisik Box
            </legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Tgl Penyerahan (Disatukan sebagai tanggal serah terima & periode pengajuan) -->
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
                        class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                    >
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block mt-0.5">Tanggal serah terima fisik ke Gudang Arsip</span>
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
                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded text-xs shadow transition flex items-center gap-1.5"
                >
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>+ Tambah Baris Dokumen</span>
                </button>
            </div>

            <!-- Repeater Table -->
            <div class="border border-slate-300 dark:border-slate-700 rounded bg-white dark:bg-slate-950 overflow-hidden shadow-xs">
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
                                    <input 
                                        type="text" 
                                        :name="'items[' + index + '][document_name]'" 
                                        x-model="item.document_name" 
                                        required 
                                        :placeholder="'Contoh: ' + (index === 0 ? 'maintenance kendaraan' : (index === 1 ? 'form verifikasi faktur' : 'perawatan ac'))"
                                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500"
                                    >
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
                                        class="p-1.5 text-rose-500 hover:bg-rose-500/20 rounded transition" 
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
                    class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold inline-flex items-center gap-1"
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
    return {
        departments: @json($departments),
        selectedDeptId: '{{ old('department_id', auth()->user()->isPicDept() ? auth()->user()->department_id : ($departments->first()->id ?? '')) }}',
        selectedSubDeptId: '{{ old('sub_department_id', '') }}',
        subDepartments: [],
        isCustomDocName: {{ old('is_custom_doc_name') ? 'true' : 'false' }},
        customDocName: @json(old('custom_doc_name', '')),
        title: @json(old('title', '')),
        tglPenyerahan: '{{ old('tgl_penyerahan', date('Y-m-d')) }}',

        // Dynamic items repeater (1 Box = Banyak Berkas Arsip)
        items: [
            { id: 1, document_name: 'maintenance kendaraan', period_start: '{{ date('Y-06') }}', period_end: '{{ date('Y-08') }}', notes: '' },
            { id: 2, document_name: 'form verifikasi faktur', period_start: '{{ date('Y-07') }}', period_end: '{{ date('Y-07') }}', notes: '' },
            { id: 3, document_name: 'perawatan ac januari', period_start: '{{ date('Y-01') }}', period_end: '{{ date('Y-12') }}', notes: '' }
        ],

        init() {
            this.updateSubDepartments();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        addItem() {
            this.items.push({
                id: Date.now() + Math.random(),
                document_name: '',
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
        }
    };
}
</script>
@endpush
@endsection
