@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Perpanjangan Masa Simpan (Expiry) - DMS PT Indraco')

@section('content')
<div class="max-w-5xl mx-auto space-y-3 font-sans pb-10"
     x-data="{
         search: '',
         selectedArchive: null,
         isOpen: false,
         hasScanFile: false,
         additionalMonths: {{ old('additional_months', 60) }},
         archives: window.extendArchives || [],
         init() {
             const initialId = {{ $selectedArchiveId ?? ($archive ? $archive->id : (old('archive_id') ?? 'null')) }};
             if (initialId) {
                 const found = this.archives.find(a => a.id == initialId);
                 if (found) {
                     this.selectArchive(found);
                 }
             }
         },
         get filteredArchives() {
             if (!this.search.trim()) return this.archives.slice(0, 15);
             const q = this.search.toLowerCase();
             return this.archives.filter(a => 
                 (a.box_number && a.box_number.toLowerCase().includes(q)) ||
                 (a.title && a.title.toLowerCase().includes(q)) ||
                 (a.period_text && a.period_text.toLowerCase().includes(q)) ||
                 (a.department && a.department.code && a.department.code.toLowerCase().includes(q))
             );
         },
         selectArchive(arc) {
             this.selectedArchive = arc;
             this.search = arc.box_number ? '[' + arc.box_number + '] ' + arc.title : arc.title;
             this.isOpen = false;
         },
         clearSelection() {
             this.selectedArchive = null;
             this.search = '';
             this.isOpen = true;
             this.$nextTick(() => this.$refs.searchInput.focus());
         },
         formatDate(d) {
             if (!d) return '-';
             if (typeof d === 'string' && (d.includes('T') || /^\d{4}-\d{2}-\d{2}/.test(d))) {
                 try {
                     const dt = new Date(d);
                     if (!isNaN(dt.getTime())) {
                         return dt.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
                     }
                 } catch(e) { return d; }
             }
             return d;
         }
     }">

    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-2 bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30 rounded">
                <i data-lucide="clock" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Formulir Pengajuan Perpanjangan Masa Simpan (Expiry)</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Perpanjangan Masa Retensi Berkas • Rekomendasi Presets & Dokumen Formulir</p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <template x-if="selectedArchive">
                <a :href="'/destructions/extend-print/' + selectedArchive.id" target="_blank" class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/40 dark:hover:bg-purple-900 text-purple-700 dark:text-purple-300 border border-purple-300 dark:border-purple-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-2xs">
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-purple-500"></i> Cetak Form
                </a>
            </template>
            <a href="{{ route('destructions.index', array_merge(['view' => 'expiry'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Kembali ke Expiry (Esc)</span>
            </a>
        </div>
    </div>

    <!-- Pass archives data safely via JavaScript -->
    <script>
        window.extendArchives = @json($archives ?? []);
    </script>

    <!-- Scope Security Alert (Jika PIC Dept) -->
    @if(auth()->user()->isPicDept())
    <div class="p-2.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs font-mono flex items-center gap-2.5 shadow-2xs">
        <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0"></i>
        <div>
            <strong class="uppercase font-bold">Filter Keamanan Departemen Terkunci:</strong>
            Menampilkan berkas arsip milik <span class="font-bold underline">{{ auth()->user()->department->name ?? 'Departemen Anda' }} ({{ auth()->user()->department->code ?? 'DEPT' }})</span>.
        </div>
    </div>
    @endif

    {{-- Validation Errors Alert Card --}}
    @if(isset($errors) && $errors->any())
    <div class="p-3 rounded bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs font-mono space-y-1 shadow-sm">
        <div class="font-bold flex items-center gap-1.5 text-rose-700 dark:text-rose-300">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0"></i>
            <span>Pengajuan Perpanjangan Gagal Diproses. Silakan periksa formulir berikut:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-600 dark:text-rose-400 pl-4">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- MAIN FORM WINDOW CARD -->
    <form action="{{ route('destructions.extend_store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <!-- Hidden Archive ID & Additional Years input -->
        <input type="hidden" name="archive_id" :value="selectedArchive ? selectedArchive.id : ''" required>
        <input type="hidden" name="additional_years" :value="Math.max(1, Math.round(additionalMonths / 12))">

        <!-- SECTION 1: CARI & PILIH BERKAS ARSIP TARGET PERPANJANGAN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="search" class="w-3.5 h-3.5 text-purple-500"></i>
                1. Cari & Pilih Berkas Dokumen Target Perpanjangan Masa Simpan
            </legend>

            <!-- Search Input Box -->
            <div class="space-y-1.5">
                <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">
                    KATA KUNCI PENCARIAN BERKAS <span class="text-rose-500">*</span>
                </label>
                <div class="relative" @click.away="isOpen = false">
                    <div class="relative">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 absolute left-2.5 top-2.5"></i>
                        <input 
                            type="text" 
                            x-ref="searchInput"
                            x-model="search"
                            @focus="isOpen = true"
                            @input="isOpen = true; selectedArchive = null"
                            placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas untuk perpanjangan masa simpan..." 
                            class="w-full pl-8 pr-8 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition shadow-2xs"
                        >
                        <!-- Clear Selection Icon -->
                        <button type="button" x-show="search.length > 0" @click="clearSelection()" class="absolute right-2.5 top-2 text-slate-400 hover:text-rose-500 cursor-pointer">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Autocomplete Dropdown List -->
                    <div x-show="isOpen && filteredArchives.length > 0" 
                         x-cloak
                         x-transition 
                         class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xl z-50 max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
                        <template x-for="arc in filteredArchives" :key="arc.id">
                            <div @click="selectArchive(arc)" class="p-2.5 hover:bg-purple-500/10 dark:hover:bg-purple-500/20 cursor-pointer transition flex items-center justify-between gap-3">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-purple-600 dark:text-purple-400 bg-purple-500/10 px-1.5 py-0.2 rounded border border-purple-500/20 text-[10px]" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.2 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="arc.title"></h4>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400" x-text="'Masa Simpan: ' + (arc.retention_display || (arc.retention_years ? arc.retention_years + ' Thn' : '-')) + ' (Expiry: ' + (arc.formatted_expiry_date || formatDate(arc.retention_expiry_date) || '-') + ')'"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        <span x-text="arc.formatted_expiry_date || formatDate(arc.retention_expiry_date) || 'Aktif'"></span>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty Search Result State -->
                    <div x-show="isOpen && filteredArchives.length === 0" x-cloak class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xl p-3 text-center text-slate-500 text-xs font-mono z-50">
                        Tidak ada berkas arsip yang cocok dengan pencarian Anda.
                    </div>
                </div>
                @error('archive_id') <span class="text-rose-500 font-mono text-[11px] font-bold block">{{ $message }}</span> @enderror
            </div>

            <!-- DETAIL INFORMASI BERKAS YANG DIPILIH -->
            <div x-show="selectedArchive" x-cloak x-transition class="bg-purple-500/5 dark:bg-purple-950/20 border border-purple-500/40 rounded p-3 space-y-2.5 font-mono shadow-2xs">
                <div class="flex items-center justify-between border-b border-purple-500/20 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="p-1 bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                        </span>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">BERKAS ARSIP TARGET PERPANJANGAN</span>
                            <span class="text-xs font-black text-purple-600 dark:text-purple-400" x-text="selectedArchive?.box_number || 'PENOMORAN PENDING'"></span>
                        </div>
                    </div>

                    <button type="button" @click="clearSelection()" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-3 h-3 text-purple-500"></i> Ganti Berkas
                    </button>
                </div>

                <div class="space-y-0.5">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white" x-text="selectedArchive?.title"></h3>
                    <p class="text-[11px] text-slate-600 dark:text-slate-400" x-text="selectedArchive?.content_description"></p>
                </div>

                <!-- Grid Details 4 Kolom -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs pt-1">
                    <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">DEPARTEMEN</span>
                        <span class="font-bold text-slate-800 dark:text-white truncate block text-[11px]" x-text="selectedArchive?.department ? (selectedArchive.department.code + ' - ' + selectedArchive.department.name) : 'UMUM'"></span>
                    </div>

                    <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">LOKASI RAK GUDANG</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 truncate block text-[11px]" x-text="selectedArchive?.short_location || selectedArchive?.full_slot_location || 'Gudang'"></span>
                    </div>

                    <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">MASA SIMPAN SAAT INI</span>
                        <span class="font-bold text-purple-700 dark:text-purple-300 truncate block text-[11px]" x-text="selectedArchive?.retention_duration_label || ((selectedArchive?.retention_years || '5') + ' Tahun')"></span>
                    </div>

                    <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">JATUH TEMPO (EXPIRY)</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400 truncate block text-[11px]" x-text="selectedArchive?.formatted_expiry_date || formatDate(selectedArchive?.retention_expiry_date)"></span>
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 2: PARAMETER PERPANJANGAN MASA SIMPAN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3" x-show="selectedArchive" x-cloak x-transition>
            <legend class="px-2 font-mono text-[11px] font-bold text-indigo-700 dark:text-indigo-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="calendar-plus" class="w-3.5 h-3.5 text-indigo-500"></i>
                2. Parameter Perpanjangan Masa Simpan Dokumen
            </legend>

            <!-- Additional Months Input & Preset Buttons -->
            <div class="space-y-1.5">
                <label for="additional_months" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">
                    TAMBAHAN MASA SIMPAN (BULAN) <span class="text-rose-500">*</span>
                </label>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex items-center w-40">
                        <input 
                            type="number" 
                            name="additional_months" 
                            id="additional_months" 
                            x-model="additionalMonths"
                            min="1" 
                            max="600"
                            required 
                            placeholder="Misal: 12, 24, 60..." 
                            class="w-full pl-2.5 pr-14 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-purple-700 dark:text-purple-300 placeholder-slate-400 focus:outline-none focus:border-purple-500 transition shadow-2xs"
                        >
                        <span class="absolute right-2.5 text-[11px] font-mono font-bold text-slate-400 pointer-events-none select-none">Bulan</span>
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="flex flex-wrap items-center gap-1 font-mono text-xs">
                        <span class="text-slate-400 text-[10px] mr-0.5">Preset:</span>
                        <template x-for="preset in [6, 12, 24, 36, 60, 120]" :key="preset">
                            <button 
                                type="button" 
                                @click="additionalMonths = preset" 
                                class="px-2 py-0.5 rounded border transition cursor-pointer text-[11px]"
                                :class="additionalMonths == preset 
                                    ? 'bg-purple-600 text-white border-purple-600 font-black shadow-xs' 
                                    : 'bg-slate-100 dark:bg-slate-800 hover:bg-purple-500/10 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 font-semibold'"
                                x-text="preset + ' Bln' + (preset == 60 ? ' (5 Thn)' : (preset == 12 ? ' (1 Thn)' : (preset == 120 ? ' (10 Thn)' : '')))"
                            ></button>
                        </template>
                    </div>
                </div>
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block">
                    * Masa simpan baru akan ditambahkan dari tanggal akhir bulan expiry sebelumnya.
                </span>
            </div>

            <!-- Extension Reason -->
            <div class="space-y-1">
                <label for="extension_reason" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">
                    ALASAN PERPANJANGAN MASA SIMPAN DOKUMEN <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="extension_reason" 
                    id="extension_reason" 
                    rows="3" 
                    required 
                    placeholder="Jelaskan secara rinci alasan dokumen ini masih harus disimpan (misal: proses audit perpajakan, audit eksternal ISO/BPOM, proses legal berkas, atau kebutuhan referensi operasional)..." 
                    class="w-full px-2.5 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-purple-500 transition shadow-2xs leading-relaxed"
                >{{ old('extension_reason') }}</textarea>
                @error('extension_reason') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </fieldset>

        <!-- SECTION 3: UPLOAD SCAN FORMULIR PERPANJANGAN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3" x-show="selectedArchive" x-cloak x-transition>
            <legend class="px-2 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                3. Upload Scan Formulir Perpanjangan Masa Simpan (Opsional)
            </legend>

            <div @file-change="hasScanFile = $event.detail.hasFile && !$event.detail.isOverLimit">
                <x-file-uploader 
                    name="scan_extension_form" 
                    id="scan_extension_form" 
                    label="Scan Formulir Perpanjangan Masa Simpan" 
                    :required="false" 
                    badge="OPSIONAL" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeMB="2"
                    helperText="Scan permohonan perpanjangan yang disetujui PIC / Manager (Format: PDF, JPG, PNG Maks. 2MB)."
                />
            </div>
        </fieldset>

        <!-- DELPHI FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-2 font-mono">
            <!-- Left status hint -->
            <div class="text-xs">
                <span x-show="!selectedArchive" class="text-slate-500 font-bold">• Silakan pilih berkas arsip terlebih dahulu di atas</span>
                <span x-show="selectedArchive" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Berkas terpilih, siap mengajukan perpanjangan masa simpan
                </span>
            </div>

            <!-- Right Buttons -->
            <div class="flex items-center gap-2">
                <a href="{{ route('destructions.index', array_merge(['view' => 'expiry'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="!selectedArchive" 
                        :class="selectedArchive ? 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black border-purple-700 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-not-allowed font-bold'"
                        class="px-5 py-2 text-xs rounded border transition flex items-center gap-1.5">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    <span>Ajukan Perpanjangan Masa Simpan</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
