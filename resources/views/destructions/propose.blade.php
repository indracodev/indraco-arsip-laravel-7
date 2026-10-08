@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Pengajuan Pemusnahan Arsip - DMS PT Indraco')

@section('content')
<div class="w-full space-y-6">
    
    <!-- Navigation & Header Section -->
    <div>
        <a href="{{ route('destructions.index', array_merge(['view' => 'destruction'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline inline-flex items-center gap-1 mb-2">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali ke Pemusnahan Berkas
        </a>
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <i data-lucide="trash-2" class="w-7 h-7 text-rose-600 dark:text-rose-400"></i>
            Formulir Pengajuan Pemusnahan Dokumen Arsip (BAP)
        </h1>
        <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm font-medium">
            Pilih berkas arsip yang akan dimusnahkan. Wajib mengunggah berkas persetujuan (approval) pemusnahan bertandatangan sebelum pengesahan.
        </p>
    </div>

    <!-- Pass archives data safely via JavaScript -->
    <script>
        window.destructionArchives = @json($archives ?? []);
    </script>

    <!-- Main Full-Width Form Card -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6"
         x-data="{
             search: '',
             selectedArchive: null,
             isOpen: false,
             hasApprovalFile: false,
             autoBap: '{{ old('bap_number', $autoBapNumber ?? '') }}',
             archives: window.destructionArchives || [],
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
                 this.autoBap = 'BAP/IND/' + new Date().getFullYear() + '/' + String(arc.id).padStart(5, '0');
                 if (arc.scan_approval_destruction || arc.approval_file) {
                     this.hasApprovalFile = true;
                 }
             },
             clearSelection() {
                 this.selectedArchive = null;
                 this.search = '';
                 this.isOpen = true;
                 this.$nextTick(() => this.$refs.searchInput.focus());
             },
             onFileChange(event) {
                 this.hasApprovalFile = event.target.files && event.target.files.length > 0;
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
                <span>Pengesahan Pemusnahan Gagal Diproses. Silakan periksa formulir berikut:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-600 dark:text-rose-400 pl-4">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('destructions.store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if(request()->has('embed'))
                <input type="hidden" name="embed" value="1">
            @endif

            <!-- Hidden Archive ID input -->
            <input type="hidden" name="archive_id" :value="selectedArchive ? selectedArchive.id : ''" required>

            <!-- STEP 1: SEARCH & SELECT ARCHIVE DOCUMENT -->
            <div class="space-y-3">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    1. Cari Berkas Dokumen Yang Akan Dimusnahkan <span class="text-rose-500">*</span>
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
                            placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas untuk dimusnahkan..." 
                            class="w-full pl-10 pr-10 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-rose-500 transition font-medium"
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
                            <div @click="selectArchive(arc)" class="p-3.5 hover:bg-rose-500/10 dark:hover:bg-rose-500/20 cursor-pointer transition flex items-center justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-black text-rose-600 dark:text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white" x-text="arc.title"></h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="'Masa Simpan: ' + (arc.retention_display || (arc.retention_years ? arc.retention_years + ' Thn' : '-')) + ' (Expiry: ' + (arc.formatted_expiry_date || formatDate(arc.retention_expiry_date) || '-') + ')'"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <span x-text="arc.short_location || arc.full_slot_location || (arc.location ? arc.location.full_location : 'Gudang')"></span>
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
            <div x-show="selectedArchive" x-transition class="bg-rose-500/5 dark:bg-rose-950/20 border-2 border-rose-500/30 rounded-2xl p-5 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-rose-500/20 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl">
                            <i data-lucide="file-x" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">BERKAS ARSIP TARGET PEMUSNAHAN</span>
                            <span class="font-mono text-base font-black text-rose-600 dark:text-rose-400" x-text="selectedArchive?.box_number || 'DRAFT'"></span>
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
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block uppercase">PERIODE BERKAS</span>
                        <span class="font-extrabold text-slate-900 dark:text-white" x-text="selectedArchive?.period_text || selectedArchive?.period_start_date || '-'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block uppercase">STATUS / EXPIRY</span>
                        <span class="font-extrabold text-rose-600 dark:text-rose-400" x-text="selectedArchive?.formatted_expiry_date || formatDate(selectedArchive?.retention_expiry_date) || 'Status: ' + (selectedArchive?.status || '-')"></span>
                    </div>
                </div>
            </div>

            <!-- STEP 2: PARAMETER DATA PEMUSNAHAN (BAP) -->
            <div class="space-y-4 pt-2 border-t border-slate-200 dark:border-slate-800" x-show="selectedArchive" x-transition>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- BAP Number -->
                    <div class="space-y-1.5">
                        <label for="bap_number" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            2. Nomor Berita Acara (BAP) <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="bap_number" 
                            id="bap_number" 
                            x-model="autoBap"
                            required 
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-xs sm:text-sm font-mono text-rose-600 dark:text-rose-400 font-bold focus:outline-none focus:border-rose-500 transition"
                        >
                        @error('bap_number') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Destruction Date -->
                    <div class="space-y-1.5">
                        <label for="destruction_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            3. Tanggal Pelaksanaan Pemusnahan <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="destruction_date" 
                            id="destruction_date" 
                            value="{{ old('destruction_date', date('Y-m-d')) }}" 
                            required 
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition font-medium"
                        >
                        @error('destruction_date') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Method -->
                <div class="space-y-1.5">
                    <label for="method" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        4. Metode Fisik Pemusnahan <span class="text-rose-500">*</span>
                    </label>
                    <select name="method" id="method" required class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition font-medium">
                        <option value="Pencacahan Mesin Industrial Paper Shredder" {{ old('method') == 'Pencacahan Mesin Industrial Paper Shredder' ? 'selected' : '' }}>Pencacahan Mesin Industrial Paper Shredder</option>
                        <option value="Pembakaran Standard Suhu Tinggi" {{ old('method') == 'Pembakaran Standard Suhu Tinggi' ? 'selected' : '' }}>Pembakaran Standard Suhu Tinggi (Incinerator)</option>
                        <option value="Peleburan Bahan Kimia & Daur Ulang" {{ old('method') == 'Peleburan Bahan Kimia & Daur Ulang' ? 'selected' : '' }}>Peleburan Bahan Kimia & Daur Ulang Industri</option>
                    </select>
                    @error('method') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Notes / Reason -->
                <div class="space-y-1.5">
                    <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        5. Catatan / Alasan Pemusnahan Berkas <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <textarea 
                        name="notes" 
                        id="notes" 
                        rows="3" 
                        placeholder="Catatan tambahan mengenai kondisi fisik berkas atau tim pelaksana pemusnahan..." 
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500 transition font-medium"
                    >{{ old('notes') }}</textarea>
                    @error('notes') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- File Uploads (Approval & Certificate) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                     @file-change="if($event.detail.name === 'approval_file') { hasApprovalFile = $event.detail.hasFile && !$event.detail.isOverLimit; }">
                    <x-file-uploader 
                        name="approval_file" 
                        id="approval_file" 
                        label="6. Upload Dokumen Persetujuan (Approval)" 
                        :required="true" 
                        badge="WAJIB" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        :maxSizeMB="2"
                        helperText="Wajib upload scan persetujuan pemusnahan (PDF / Gambar, Maks 2MB). Foto scan besar otomatis dioptimalkan agar ringan & jelas."
                    />

                    <x-file-uploader 
                        name="certificate_file" 
                        id="certificate_file" 
                        label="7. Upload Sertifikat BAP (Opsional)" 
                        :required="false" 
                        badge="OPSIONAL" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        :maxSizeMB="2"
                        helperText="Scan sertifikat atau berita acara pelaksanaan jika sudah tersedia."
                    />
                </div>
            </div>

            <!-- FORM ACTIONS & STATUS CHECK -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                <div class="text-xs">
                    <span x-show="!selectedArchive" class="text-slate-500 font-medium">• Pilih berkas arsip terlebih dahulu di atas</span>
                    <span x-show="selectedArchive && !hasApprovalFile" class="text-rose-500 font-bold">• Unggah dokumen approval untuk mengaktifkan tombol pengesahan</span>
                    <span x-show="selectedArchive && hasApprovalFile" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Berkas & dokumen approval lengkap, siap disahkan
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('destructions.index', array_merge(['view' => 'destruction'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition">
                        Batal
                    </a>
                    <button type="submit" 
                            :disabled="!selectedArchive || !hasApprovalFile" 
                            :class="(selectedArchive && hasApprovalFile) ? 'bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 text-white font-black shadow-lg shadow-rose-500/20 cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 cursor-not-allowed font-bold'"
                            class="px-6 py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-2">
                        <i data-lucide="shield-alert" class="w-4 h-4"></i>
                        Sahkan Pemusnahan Berkas
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
