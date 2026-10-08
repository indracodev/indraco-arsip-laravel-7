@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Permintaan Penarikan Arsip - DMS PT Indraco')

@section('content')
<div class="max-w-5xl mx-auto space-y-3 font-sans pb-10"
     x-data="{
         search: '',
         selectedArchive: null,
         isOpen: false,
         hasApprovalFile: false,
         archives: window.borrowingArchives || [],
         init() {
             const initialId = {{ $selectedArchiveId ?? 'null' }};
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
         }
     }">

    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5">
            <span class="p-2 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded">
                <i data-lucide="file-symlink" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Formulir Permintaan Penarikan Berkas Arsip [Form 2]</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengajuan Penarikan Dokumen Arsip Tersimpan • Wajib Dokumen Persetujuan (Approval)</p>
            </div>
        </div>

        <a href="{{ route('borrowings.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm shrink-0">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500"></i>
            <span>Kembali ke Log Penarikan (Esc)</span>
        </a>
    </div>

    <!-- Pass archives data safely via JavaScript -->
    <script>
        window.borrowingArchives = @json($archives);
    </script>

    <!-- Scope Security Alert (Jika PIC Dept) -->
    @if(auth()->user()->isPicDept())
    <div class="p-2.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs font-mono flex items-center gap-2.5 shadow-2xs">
        <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0"></i>
        <div>
            <strong class="uppercase font-bold">Filter Keamanan Departemen Terkunci:</strong>
            Menampilkan berkas arsip milik <span class="font-bold underline">{{ auth()->user()->department->name ?? 'Departemen Anda' }} ({{ auth()->user()->department->code ?? 'DEPT' }})</span> dengan status tersimpan di gudang.
        </div>
    </div>
    @endif

    {{-- Validation Errors Alert Card --}}
    @if(isset($errors) && $errors->any())
    <div class="p-3 rounded bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs font-mono space-y-1 shadow-sm">
        <div class="font-bold flex items-center gap-1.5 text-rose-700 dark:text-rose-300">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0"></i>
            <span>Pengajuan Penarikan Gagal Diproses. Silakan periksa isian formulir:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-600 dark:text-rose-400 pl-4">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- MAIN FORM WINDOW CARD -->
    <form action="{{ route('borrowings.store', request()->has('embed') ? ['embed' => 1] : []) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <!-- Hidden Archive ID input & Default Permanent Mode -->
        <input type="hidden" name="archive_id" :value="selectedArchive ? selectedArchive.id : ''" required>
        <input type="hidden" name="is_permanent" value="1">

        <!-- SECTION 1: CARI & PILIH BERKAS ARSIP -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="search" class="w-3.5 h-3.5 text-emerald-500"></i>
                1. Cari & Pilih Berkas Dokumen Arsip Target Penarikan
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
                            placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas arsip..." 
                            class="w-full pl-8 pr-8 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition shadow-2xs"
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
                            <div @click="selectArchive(arc)" class="p-2.5 hover:bg-emerald-500/10 dark:hover:bg-emerald-500/20 cursor-pointer transition flex items-center justify-between gap-3">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-1.5 py-0.2 rounded border border-amber-500/20 text-[10px]" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.2 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="arc.title"></h4>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400" x-text="arc.period_text || arc.period_start_date"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        <span x-text="arc.short_location || arc.full_slot_location || (arc.location ? arc.location.full_location : 'Gudang')"></span>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty Search Result State -->
                    <div x-show="isOpen && filteredArchives.length === 0" x-cloak class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xl p-3 text-center text-slate-500 text-xs font-mono z-50">
                        Tidak ada berkas arsip yang cocok dengan pencarian di departemen ini.
                    </div>
                </div>
                @error('archive_id') <span class="text-rose-500 font-mono text-[11px] font-bold block">{{ $message }}</span> @enderror
            </div>

            <!-- DETAIL INFORMASI BERKAS YANG DIPILIH -->
            <div x-show="selectedArchive" x-cloak x-transition class="bg-slate-50 dark:bg-slate-900/60 border border-emerald-500/40 rounded p-3 space-y-2.5 font-mono shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="p-1 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded">
                            <i data-lucide="folder-check" class="w-3.5 h-3.5"></i>
                        </span>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">BERKAS ARSIP TERPILIH</span>
                            <span class="text-xs font-black text-amber-600 dark:text-amber-400" x-text="selectedArchive?.box_number || 'PENOMORAN PENDING'"></span>
                        </div>
                    </div>

                    <button type="button" @click="clearSelection()" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-3 h-3 text-emerald-500"></i> Ganti Berkas
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
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 truncate block text-[11px]" x-text="selectedArchive?.short_location || selectedArchive?.full_slot_location || (selectedArchive?.location ? selectedArchive.location.full_location : 'Gudang')"></span>
                    </div>

                    <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">PERIODE DOKUMEN</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400 truncate block text-[11px]" x-text="selectedArchive?.period_text || selectedArchive?.periode_doc || '-'"></span>
                    </div>

                    <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">KONDISI FISIK</span>
                        <span class="font-bold text-slate-800 dark:text-white truncate block text-[11px]" x-text="selectedArchive?.physical_condition || 'Baik'"></span>
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 2: MAKSUD / ALASAN PENARIKAN -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-amber-500"></i>
                2. Maksud & Alasan Keperluan Penarikan Berkas
            </legend>

            <div>
                <label for="purpose" class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                    ALASAN LENGKAP PENARIKAN <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="purpose" 
                    id="purpose" 
                    rows="3" 
                    required 
                    placeholder="Contoh: Diperlukan untuk keperluan verifikasi audit internal perpajakan tahunan..." 
                    class="w-full px-2.5 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition shadow-2xs leading-relaxed"
                >{{ old('purpose') }}</textarea>
                @error('purpose') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </fieldset>

        <!-- SECTION 3: UPLOAD DOKUMEN PERSETUJUAN (APPROVAL) -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3">
            <legend class="px-2 font-mono text-[11px] font-bold text-purple-700 dark:text-purple-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-purple-500"></i>
                3. Upload Dokumen Persetujuan (Approval) Penarikan
            </legend>

            <div @file-change="hasApprovalFile = $event.detail.hasFile && !$event.detail.isOverLimit">
                <x-file-uploader 
                    name="approval_file" 
                    id="approval_file" 
                    label="Berkas Dokumen Persetujuan (Approval)" 
                    :required="true" 
                    badge="WAJIB UNTUK PENARIKAN" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeMB="2"
                    helperText="Wajib melampirkan berkas scan approval penarikan bertandatangan PIC/Manager (Format: PDF, JPG, PNG Maks. 2MB)."
                />
                @error('approval_file') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </fieldset>

        <!-- DELPHI FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-2 font-mono">
            <!-- Left status hint -->
            <div class="text-xs">
                <span x-show="!selectedArchive" class="text-slate-500 font-bold">• Silakan pilih berkas arsip terlebih dahulu</span>
                <span x-show="selectedArchive && !hasApprovalFile" class="text-rose-500 font-bold">• Unggah berkas dokumen approval untuk mengaktifkan tombol pengajuan</span>
                <span x-show="selectedArchive && hasApprovalFile" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Berkas & approval lengkap, siap diajukan
                </span>
            </div>

            <!-- Right Buttons -->
            <div class="flex items-center gap-2">
                <a href="{{ route('borrowings.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="!selectedArchive || !hasApprovalFile" 
                        :class="(selectedArchive && hasApprovalFile) ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-black border-emerald-700 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-not-allowed font-bold'"
                        class="px-5 py-2 text-xs rounded border transition flex items-center gap-1.5">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Ajukan Penarikan Arsip</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
