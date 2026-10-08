@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Perpanjangan Masa Simpan (Expiry) - DMS PT Indraco')

@section('content')
<div class="w-full space-y-6">
    
    <!-- Navigation & Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('destructions.index', array_merge(['view' => 'expiry'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline inline-flex items-center gap-1 mb-2">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali ke Expiry
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <i data-lucide="clock" class="w-7 h-7 text-purple-600 dark:text-purple-400"></i>
                Formulir Pengajuan Perpanjangan Masa Simpan (Expiry)
            </h1>
            <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm font-medium">
                Pilih berkas arsip yang akan diperpanjang masa simpannya. Lengkapi alasan perpanjangan dan lampirkan scan formulir persetujuan.
            </p>
        </div>

        <template x-if="selectedArchive">
            <a :href="'/destructions/extend-print/' + selectedArchive.id" target="_blank" class="px-4 py-2.5 bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-purple-600 dark:text-purple-400 border border-slate-200 dark:border-slate-800 font-bold text-xs rounded-xl transition flex items-center gap-2 shrink-0">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak Form Perpanjangan
            </a>
        </template>
    </div>

    <!-- Pass archives data safely via JavaScript -->
    <script>
        window.extendArchives = @json($archives ?? []);
    </script>

    <!-- Main Full-Width Form Card -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6"
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
             onFileChange(event) {
                 this.hasScanFile = event.target.files && event.target.files.length > 0;
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

        <!-- Department Scope Banner -->
        @if(auth()->user()->isPicDept())
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs flex items-center gap-3 font-medium">
            <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0"></i>
            <div>
                <span class="font-bold block text-amber-950 dark:text-amber-200">Filter Keamanan Departemen Terkunci:</span>
                Hanya menampilkan berkas arsip milik departemen <span class="font-black text-amber-900 dark:text-amber-100 uppercase">{{ auth()->user()->department->name ?? 'Departemen Anda' }} ({{ auth()->user()->department->code ?? 'DEPT' }})</span>.
            </div>
        </div>
        @endif

        {{-- Validation Errors Alert Card --}}
        @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs space-y-1.5 font-medium">
            <div class="font-bold flex items-center gap-2 text-rose-700 dark:text-rose-300">
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

        <form action="{{ route('destructions.extend_store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if(request()->has('embed'))
                <input type="hidden" name="embed" value="1">
            @endif

            <!-- Hidden Archive ID & Additional Years input -->
            <input type="hidden" name="archive_id" :value="selectedArchive ? selectedArchive.id : ''" required>
            <input type="hidden" name="additional_years" :value="Math.max(1, Math.round(additionalMonths / 12))">

            <!-- STEP 1: SEARCH & SELECT ARCHIVE DOCUMENT -->
            <div class="space-y-3">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    1. Cari Berkas Dokumen Yang Akan Diperpanjang Masa Simpannya <span class="text-rose-500">*</span>
                </label>

                <!-- Live Search Autocomplete Box -->
                <div class="relative" @click.away="isOpen = false">
                    <div class="relative">
                        <input 
                            type="text" 
                            x-ref="searchInput"
                            x-model="search"
                            @focus="isOpen = true"
                            @input="isOpen = true; selectedArchive = null"
                            placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas untuk perpanjangan masa simpan..." 
                            class="w-full pl-10 pr-10 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 transition font-medium"
                        >
                        <i data-lucide="search" class="w-5 h-5 text-slate-400 dark:text-slate-500 absolute left-3.5 top-3.5"></i>
                        
                        <!-- Clear Selection Icon -->
                        <button type="button" x-show="search.length > 0" @click="clearSelection()" class="absolute right-3.5 top-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Autocomplete Dropdown List -->
                    <div x-show="isOpen && filteredArchives.length > 0" 
                         x-transition 
                         class="absolute left-0 right-0 top-full mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl z-50 max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="arc in filteredArchives" :key="arc.id">
                            <div @click="selectArchive(arc)" class="p-3.5 hover:bg-purple-500/10 dark:hover:bg-purple-500/20 cursor-pointer transition flex items-center justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-black text-purple-600 dark:text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded border border-purple-500/20" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white" x-text="arc.title"></h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="'Masa Simpan: ' + (arc.retention_display || (arc.retention_years ? arc.retention_years + ' Thn' : '-')) + ' (Expiry: ' + (arc.formatted_expiry_date || formatDate(arc.retention_expiry_date) || '-') + ')'"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400 flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        <span x-text="arc.formatted_expiry_date || formatDate(arc.retention_expiry_date) || 'Aktif'"></span>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty Search Result State -->
                    <div x-show="isOpen && filteredArchives.length === 0" class="absolute left-0 right-0 top-full mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-4 text-center text-slate-500 text-xs z-50">
                        Tidak ada berkas arsip yang sesuai dengan pencarian Anda.
                    </div>
                </div>
                @error('archive_id') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- DETAIL INFORMASI BERKAS YANG DIPILIH -->
            <div x-show="selectedArchive" x-transition class="bg-purple-500/5 dark:bg-purple-950/20 border-2 border-purple-500/30 rounded-2xl p-5 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-purple-500/20 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-xl">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">BERKAS ARSIP TARGET PERPANJANGAN</span>
                            <span class="font-mono text-base font-black text-purple-600 dark:text-purple-400" x-text="selectedArchive?.box_number || 'DRAFT'"></span>
                        </div>
                    </div>

                    <button type="button" @click="clearSelection()" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Ganti Berkas
                    </button>
                </div>

                <div class="space-y-1">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white" x-text="selectedArchive?.title"></h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400" x-text="selectedArchive?.content_description"></p>
                </div>

                <!-- Grid Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs pt-1">
                    <div class="p-3 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block uppercase">DEPARTEMEN</span>
                        <span class="font-extrabold text-slate-900 dark:text-white" x-text="selectedArchive?.department ? (selectedArchive.department.code + ' - ' + selectedArchive.department.name) : 'UMUM'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block uppercase">LOKASI RAK GUDANG</span>
                        <span class="font-extrabold text-emerald-600 dark:text-emerald-400" x-text="selectedArchive?.short_location || selectedArchive?.full_slot_location || 'Gudang'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block uppercase">MASA SIMPAN SAAT INI</span>
                        <span class="font-extrabold text-purple-700 dark:text-purple-300" x-text="selectedArchive?.retention_duration_label || ((selectedArchive?.retention_years || '5') + ' Tahun')"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block uppercase">JATUH TEMPO (EXPIRY)</span>
                        <span class="font-extrabold text-rose-600 dark:text-rose-400 font-mono" x-text="selectedArchive?.formatted_expiry_date || formatDate(selectedArchive?.retention_expiry_date)"></span>
                    </div>
                </div>
            </div>

            <!-- STEP 2: PARAMETER PERPANJANGAN MASA SIMPAN -->
            <div class="space-y-4 pt-2 border-t border-slate-200 dark:border-slate-800" x-show="selectedArchive" x-transition>
                <!-- Additional Months Selection (aligned with Draft Arsip) -->
                <div class="space-y-1.5">
                    <label for="additional_months" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        2. Tambahan Masa Simpan (Bulan) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative flex items-center max-w-xs">
                        <input 
                            type="number" 
                            name="additional_months" 
                            id="additional_months" 
                            x-model="additionalMonths"
                            min="1" 
                            max="600"
                            required 
                            placeholder="Misal: 12, 24, 36, 60..." 
                            class="w-full pl-3.5 pr-16 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition"
                        >
                        <span class="absolute right-3.5 text-xs font-bold text-slate-400 pointer-events-none select-none">Bulan</span>
                    </div>

                    <!-- Quick Preset Buttons (Format Draft Arsip) -->
                    <div class="flex flex-wrap items-center gap-1.5 mt-2 font-mono text-xs">
                        <span class="text-slate-400 text-[11px] mr-0.5">Preset:</span>
                        <template x-for="preset in [6, 12, 24, 36, 60, 120]" :key="preset">
                            <button 
                                type="button" 
                                @click="additionalMonths = preset" 
                                class="px-2.5 py-1 rounded-xl border transition cursor-pointer text-xs"
                                :class="additionalMonths == preset 
                                    ? 'bg-purple-600 text-white border-purple-600 font-black shadow-sm' 
                                    : 'bg-slate-100 dark:bg-slate-900 hover:bg-purple-500/10 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 font-medium'"
                                x-text="preset + ' Bln' + (preset == 60 ? ' (5 Thn)' : (preset == 12 ? ' (1 Thn)' : (preset == 120 ? ' (10 Thn)' : '')))"
                            ></button>
                        </template>
                    </div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium block mt-1">
                        Masa simpan baru akan ditambahkan dari tanggal expiry sebelumnya.
                    </span>
                </div>

                <!-- Extension Reason -->
                <div class="space-y-1.5">
                    <label for="extension_reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        3. Alasan Perpanjangan Masa Simpan Dokumen <span class="text-rose-500">*</span>
                    </label>
                    <textarea 
                        name="extension_reason" 
                        id="extension_reason" 
                        rows="4" 
                        required 
                        placeholder="Jelaskan secara rinci alasan dokumen ini masih harus disimpan (misal: proses audit perpajakan, audit eksternal ISO/BPOM, proses legal berkas, atau kebutuhan referensi operasional)..." 
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition font-medium"
                    >{{ old('extension_reason') }}</textarea>
                    @error('extension_reason') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Scan Extension Form Upload -->
                <div @file-change="hasScanFile = $event.detail.hasFile && !$event.detail.isOverLimit">
                    <x-file-uploader 
                        name="scan_extension_form" 
                        id="scan_extension_form" 
                        label="4. Upload Scan Formulir Perpanjangan Masa Simpan (Opsional)" 
                        :required="false" 
                        badge="OPSIONAL" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        :maxSizeMB="2"
                        helperText="Scan permohonan perpanjangan yang disetujui PIC / Manager (PDF / Gambar, Maks 2MB). Foto scan besar otomatis dioptimalkan agar ringan & jelas."
                    />
                </div>
            </div>

            <!-- FORM ACTIONS & STATUS CHECK -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                <div class="text-xs">
                    <span x-show="!selectedArchive" class="text-slate-500 font-medium">• Pilih berkas arsip terlebih dahulu di atas</span>
                    <span x-show="selectedArchive" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Berkas terpilih, siap mengajukan perpanjangan masa simpan
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('destructions.index', array_merge(['view' => 'expiry'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition">
                        Batal
                    </a>
                    <button type="submit" 
                            :disabled="!selectedArchive" 
                            :class="selectedArchive ? 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 text-white font-black shadow-lg shadow-purple-500/20 cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 cursor-not-allowed font-bold'"
                            class="px-6 py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        Ajukan Perpanjangan Masa Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
