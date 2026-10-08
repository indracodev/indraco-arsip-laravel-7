@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Pengajuan Pemusnahan Arsip - DMS PT Indraco')

@section('content')
<div class="max-w-5xl mx-auto space-y-3 font-sans pb-10"
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
            <span class="p-2 bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 rounded">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Formulir Pengajuan Pemusnahan Dokumen Arsip (BAP)</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pemusnahan Berkas Fisik • Berita Acara Pelaksanaan & Approval Pengesahan</p>
            </div>
        </div>

        <a href="{{ route('destructions.index', array_merge(['view' => 'destruction'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm shrink-0">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500"></i>
            <span>Kembali ke Pemusnahan (Esc)</span>
        </a>
    </div>

    <!-- Pass archives data safely via JavaScript -->
    <script>
        window.destructionArchives = @json($archives ?? []);
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
            <span>Pengesahan Pemusnahan Gagal Diproses. Silakan periksa formulir berikut:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-600 dark:text-rose-400 pl-4">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- MAIN FORM WINDOW CARD -->
    <form action="{{ route('destructions.store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <!-- Hidden Archive ID input -->
        <input type="hidden" name="archive_id" :value="selectedArchive ? selectedArchive.id : ''" required>

        <!-- SECTION 1: CARI & PILIH BERKAS ARSIP TARGET PEMUSNAHAN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-rose-700 dark:text-rose-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="search" class="w-3.5 h-3.5 text-rose-500"></i>
                1. Cari & Pilih Berkas Dokumen Target Pemusnahan Fisik
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
                            placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas untuk dimusnahkan..." 
                            class="w-full pl-8 pr-8 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500 transition shadow-2xs"
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
                            <div @click="selectArchive(arc)" class="p-2.5 hover:bg-rose-500/10 dark:hover:bg-rose-500/20 cursor-pointer transition flex items-center justify-between gap-3">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 px-1.5 py-0.2 rounded border border-rose-500/20 text-[10px]" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.2 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="arc.title"></h4>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400" x-text="'Masa Simpan: ' + (arc.retention_display || (arc.retention_years ? arc.retention_years + ' Thn' : '-')) + ' (Expiry: ' + (arc.formatted_expiry_date || formatDate(arc.retention_expiry_date) || '-') + ')'"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        <span x-text="arc.short_location || arc.full_slot_location || (arc.location ? arc.location.full_location : 'Gudang')"></span>
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
            <div x-show="selectedArchive" x-cloak x-transition class="bg-rose-500/5 dark:bg-rose-950/20 border border-rose-500/40 rounded p-3 space-y-2.5 font-mono shadow-2xs">
                <div class="flex items-center justify-between border-b border-rose-500/20 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="p-1 bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded">
                            <i data-lucide="file-x" class="w-3.5 h-3.5"></i>
                        </span>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">BERKAS ARSIP TARGET PEMUSNAHAN</span>
                            <span class="text-xs font-black text-rose-600 dark:text-rose-400" x-text="selectedArchive?.box_number || 'PENOMORAN PENDING'"></span>
                        </div>
                    </div>

                    <button type="button" @click="clearSelection()" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-3 h-3 text-rose-500"></i> Ganti Berkas
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
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">PERIODE BERKAS</span>
                        <span class="font-bold text-slate-800 dark:text-white truncate block text-[11px]" x-text="selectedArchive?.period_text || selectedArchive?.period_start_date || '-'"></span>
                    </div>

                    <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">STATUS / EXPIRY</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400 truncate block text-[11px]" x-text="selectedArchive?.formatted_expiry_date || formatDate(selectedArchive?.retention_expiry_date) || 'Status: ' + (selectedArchive?.status || '-')"></span>
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 2: PARAMETER BERITA ACARA PEMUSNAHAN (BAP) -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3" x-show="selectedArchive" x-cloak x-transition>
            <legend class="px-2 font-mono text-[11px] font-bold text-rose-700 dark:text-rose-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="clipboard-list" class="w-3.5 h-3.5 text-rose-500"></i>
                2. Parameter Berita Acara Pemusnahan (BAP)
            </legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- BAP Number -->
                <div>
                    <label for="bap_number" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        NOMOR BERITA ACARA (BAP) <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="bap_number" 
                        id="bap_number" 
                        x-model="autoBap"
                        required 
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-rose-600 dark:text-rose-400 focus:outline-none focus:border-rose-500 transition shadow-2xs"
                    >
                    @error('bap_number') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Destruction Date -->
                <div>
                    <label for="destruction_date" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        TANGGAL PELAKSANAAN PEMUSNAHAN <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="destruction_date" 
                        id="destruction_date" 
                        value="{{ old('destruction_date', date('Y-m-d')) }}" 
                        required 
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition shadow-2xs"
                    >
                    @error('destruction_date') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Method -->
            <div>
                <label for="method" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                    METODE FISIK PEMUSNAHAN <span class="text-rose-500">*</span>
                </label>
                <select name="method" id="method" required class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition shadow-2xs cursor-pointer">
                    <option value="Pencacahan Mesin Industrial Paper Shredder" {{ old('method') == 'Pencacahan Mesin Industrial Paper Shredder' ? 'selected' : '' }}>Pencacahan Mesin Industrial Paper Shredder</option>
                    <option value="Pembakaran Standard Suhu Tinggi" {{ old('method') == 'Pembakaran Standard Suhu Tinggi' ? 'selected' : '' }}>Pembakaran Standard Suhu Tinggi (Incinerator)</option>
                    <option value="Peleburan Bahan Kimia & Daur Ulang" {{ old('method') == 'Peleburan Bahan Kimia & Daur Ulang' ? 'selected' : '' }}>Peleburan Bahan Kimia & Daur Ulang Industri</option>
                </select>
                @error('method') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Notes / Reason -->
            <div>
                <label for="notes" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                    CATATAN / ALASAN PEMUSNAHAN BERKAS <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <textarea 
                    name="notes" 
                    id="notes" 
                    rows="3" 
                    placeholder="Catatan tambahan mengenai kondisi fisik berkas atau tim pelaksana pemusnahan..." 
                    class="w-full px-2.5 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-rose-500 transition shadow-2xs leading-relaxed"
                >{{ old('notes') }}</textarea>
                @error('notes') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </fieldset>

        <!-- SECTION 3: UPLOAD DOKUMEN PERSETUJUAN & SERTIFIKAT BAP -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3" x-show="selectedArchive" x-cloak x-transition>
            <legend class="px-2 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                3. Upload Dokumen Persetujuan (Approval) & Berkas BAP
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 font-mono text-xs"
                 @file-change="if($event.detail.name === 'approval_file') { hasApprovalFile = $event.detail.hasFile && !$event.detail.isOverLimit; }">
                <!-- 1. Dokumen Approval (Wajib) -->
                <x-file-uploader 
                    name="approval_file" 
                    id="approval_file" 
                    label="Dokumen Persetujuan (Approval)" 
                    :required="true" 
                    badge="WAJIB UNTUK PEMUSNAHAN" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeMB="2"
                    helperText="Wajib upload scan persetujuan pemusnahan bertandatangan (Format: PDF, JPG, PNG Maks. 2MB)."
                />

                <!-- 2. Sertifikat / Berkas BAP (Opsional) -->
                <x-file-uploader 
                    name="certificate_file" 
                    id="certificate_file" 
                    label="Sertifikat / Berkas BAP (Opsional)" 
                    :required="false" 
                    badge="OPSIONAL" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeMB="2"
                    helperText="Scan sertifikat atau berita acara pelaksanaan jika sudah tersedia."
                />
            </div>
        </fieldset>

        <!-- DELPHI FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-2 font-mono">
            <!-- Left status hint -->
            <div class="text-xs">
                <span x-show="!selectedArchive" class="text-slate-500 font-bold">• Silakan pilih berkas arsip terlebih dahulu di atas</span>
                <span x-show="selectedArchive && !hasApprovalFile" class="text-rose-500 font-bold">• Unggah dokumen approval untuk mengaktifkan tombol pengesahan</span>
                <span x-show="selectedArchive && hasApprovalFile" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Berkas & dokumen approval lengkap, siap disahkan
                </span>
            </div>

            <!-- Right Buttons -->
            <div class="flex items-center gap-2">
                <a href="{{ route('destructions.index', array_merge(['view' => 'destruction'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="!selectedArchive || !hasApprovalFile" 
                        :class="(selectedArchive && hasApprovalFile) ? 'bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white font-black border-rose-700 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-not-allowed font-bold'"
                        class="px-5 py-2 text-xs rounded border transition flex items-center gap-1.5">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                    <span>Sahkan Pemusnahan Berkas</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
