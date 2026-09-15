@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Permintaan Peminjaman Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-[10px]">
    
    <!-- DELPHI TOP TITLE & BREADCRUMB -->
    <div class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-[8px]">
            <a href="{{ route('borrowings.index') }}" title="Kembali ke Log Peminjaman" class="p-[4px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-[3px] border border-slate-300 dark:border-slate-700 transition">
                <i data-lucide="arrow-left" class="w-[14px] h-[14px]"></i>
            </a>
            <div>
                <h1 class="text-[13px] font-mono font-black uppercase text-slate-900 dark:text-white tracking-wide flex items-center gap-[6px]">
                    <i data-lucide="file-symlink" class="w-[15px] h-[15px] text-emerald-600 dark:text-emerald-400"></i>
                    <span>Formulir Permintaan Peminjaman Dokumen Arsip</span>
                </h1>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-mono">
                    Cari berkas fisik di gudang sesuai hak akses departemen Anda, isi estimasi kembali & keperluan.
                </p>
            </div>
        </div>
    </div>

    <!-- MAIN DELPHI FORM CONTAINER (TGroupBox) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[12px] shadow-xs space-y-[10px] font-sans"
         x-data="{
             search: '',
             selectedArchive: null,
             isOpen: false,
             archives: {!! json_encode($archives) !!},
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
                     (a.department && a.department.code.toLowerCase().includes(q))
                 );
             },
             selectArchive(arc) {
                 this.selectedArchive = arc;
                 this.search = arc.box_number ? `[${arc.box_number}] ${arc.title}` : arc.title;
                 this.isOpen = false;
             },
             clearSelection() {
                 this.selectedArchive = null;
                 this.search = '';
                 this.isOpen = true;
                 this.$nextTick(() => this.$refs.searchInput.focus());
             }
         }">

        <!-- Department Security Scope Banner -->
        @if(auth()->user()->isPicDept())
        <div class="px-[10px] py-[6px] rounded-[3px] bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-[11px] font-mono flex items-center gap-[8px]">
            <i data-lucide="shield-alert" class="w-[14px] h-[14px] text-amber-600 dark:text-amber-400 shrink-0"></i>
            <div>
                <strong>Security Scope Terkunci:</strong> Menampilkan arsip milik departemen <strong class="uppercase text-amber-900 dark:text-amber-200">{{ auth()->user()->department->name ?? 'Departemen' }} ({{ auth()->user()->department->code ?? 'DEPT' }})</strong> status di gudang.
            </div>
        </div>
        @endif

        <form action="{{ route('borrowings.store') }}" method="POST" class="space-y-[10px]">
            @csrf

            <!-- Hidden Archive ID input -->
            <input type="hidden" name="archive_id" :value="selectedArchive ? selectedArchive.id : ''" required>

            <!-- STEP 1: SEARCH & SELECT ARCHIVE DOCUMENT -->
            <fieldset class="border border-slate-300 dark:border-slate-800 rounded-[3px] p-[10px] space-y-[6px]">
                <legend class="px-[6px] text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[2px]">
                    1. Pencarian Dokumen Berkas Arsip Fisik <span class="text-rose-500">*</span>
                </legend>

                <!-- Live Search Autocomplete Box -->
                <div class="relative" @click.away="isOpen = false">
                    <div class="relative">
                        <input 
                            type="text" 
                            x-ref="searchInput"
                            x-model="search"
                            @focus="isOpen = true"
                            @input="isOpen = true; selectedArchive = null"
                            placeholder="Ketik Nomor Box (contoh: BOX-...) atau Kata Kunci Judul Berkas..." 
                            class="w-full pl-[28px] pr-[28px] h-[30px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                        >
                        <i data-lucide="search" class="w-[13px] h-[13px] text-slate-400 absolute left-[8px] top-[8px]"></i>
                        
                        <!-- Clear Selection Icon -->
                        <button type="button" x-show="search.length > 0" @click="clearSelection()" class="absolute right-[8px] top-[7px] text-slate-400 hover:text-slate-600 dark:hover:text-white">
                            <i data-lucide="x" class="w-[13px] h-[13px]"></i>
                        </button>
                    </div>

                    <!-- Autocomplete Dropdown List -->
                    <div x-show="isOpen && filteredArchives.length > 0" 
                         x-transition 
                         class="absolute left-0 right-0 top-full mt-[2px] bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] shadow-lg z-50 max-h-[200px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 font-mono text-[11px]">
                        <template x-for="arc in filteredArchives" :key="arc.id">
                            <div @click="selectArchive(arc)" class="p-[8px] hover:bg-emerald-500/10 dark:hover:bg-emerald-500/20 cursor-pointer transition flex items-center justify-between gap-[8px]">
                                <div class="space-y-[2px]">
                                    <div class="flex items-center gap-[6px]">
                                        <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-[6px] py-[1px] rounded-[2px] border border-amber-500/30" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-[4px] py-[1px] rounded-[2px]" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                    </div>
                                    <h4 class="text-[11px] font-bold text-slate-900 dark:text-white" x-text="arc.title"></h4>
                                    <p class="text-[10px] text-slate-500" x-text="arc.period_text || arc.period_start_date"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-[4px]">
                                        <i data-lucide="map-pin" class="w-[11px] h-[11px]"></i>
                                        <span x-text="arc.location ? arc.location.full_location : 'Gudang'"></span>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty Search Result State -->
                    <div x-show="isOpen && filteredArchives.length === 0" class="absolute left-0 right-0 top-full mt-[2px] bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] shadow-lg p-[10px] text-center text-slate-500 text-[11px] font-mono z-50">
                        Tidak ada berkas arsip yang sesuai dengan pencarian Anda di departemen ini.
                    </div>
                </div>
                @error('archive_id') <span class="text-rose-500 text-[10px] font-bold block mt-[2px]">{{ $message }}</span> @enderror
            </fieldset>

            <!-- DETAIL INFORMASI BERKAS YANG DIPILIH -->
            <div x-show="selectedArchive" x-transition class="bg-slate-50 dark:bg-slate-900 border border-emerald-500/40 rounded-[3px] p-[10px] space-y-[8px] font-mono text-[11px] shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-[6px]">
                    <div class="flex items-center gap-[6px]">
                        <span class="p-[4px] bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-[2px]">
                            <i data-lucide="folder-check" class="w-[14px] h-[14px]"></i>
                        </span>
                        <div>
                            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">BERKAS ARSIP TERPILIH</span>
                            <span class="text-[12px] font-black text-amber-600 dark:text-amber-400" x-text="selectedArchive?.box_number || 'DRAFT'"></span>
                        </div>
                    </div>

                    <button type="button" @click="clearSelection()" class="px-[8px] py-[2px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-[3px] text-[10px] font-bold transition flex items-center gap-[4px]">
                        <i data-lucide="refresh-cw" class="w-[11px] h-[11px]"></i> Ganti Berkas
                    </button>
                </div>

                <div class="space-y-[2px]">
                    <h3 class="text-[12px] font-bold text-slate-900 dark:text-white" x-text="selectedArchive?.title"></h3>
                    <p class="text-[10px] text-slate-600 dark:text-slate-400 font-sans" x-text="selectedArchive?.content_description"></p>
                </div>

                <!-- Grid Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-[6px] text-[10px] pt-[2px]">
                    <div class="p-[6px] rounded-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 block uppercase">DEPARTEMEN</span>
                        <span class="font-bold text-slate-900 dark:text-white" x-text="selectedArchive?.department ? (selectedArchive.department.code + ' - ' + selectedArchive.department.name) : 'UMUM'"></span>
                    </div>

                    <div class="p-[6px] rounded-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 block uppercase">LOKASI RAK GUDANG</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="selectedArchive?.location ? selectedArchive.location.full_location : 'Gudang'"></span>
                    </div>

                    <div class="p-[6px] rounded-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 block uppercase">PERIODE BERKAS</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400" x-text="selectedArchive?.period_text || '-'"></span>
                    </div>

                    <div class="p-[6px] rounded-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 block uppercase">KONDISI FISIK</span>
                        <span class="font-bold text-slate-900 dark:text-white" x-text="selectedArchive?.physical_condition || 'Baik'"></span>
                    </div>
                </div>
            </div>

            <!-- STEP 2 & 3: RETURN DATE & PURPOSE -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-[8px] pt-[2px]">
                <!-- Step 2: Expected Return Date (4 Columns) -->
                <div class="md:col-span-4 flex flex-col gap-[3px]">
                    <label for="expected_return_date" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        2. Estimasi Pengembalian <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="expected_return_date" 
                        id="expected_return_date" 
                        value="{{ old('expected_return_date', \Carbon\Carbon::now()->addDays(7)->format('Y-m-d')) }}" 
                        min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                        required
                        class="w-full px-[8px] h-[30px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 transition"
                    >
                    @error('expected_return_date') <span class="text-rose-500 text-[10px] font-bold block">{{ $message }}</span> @enderror
                </div>

                <!-- Step 3: Purpose / Reason (8 Columns) -->
                <div class="md:col-span-8 flex flex-col gap-[3px]">
                    <label for="purpose" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        3. Alasan / Keperluan Peminjaman <span class="text-rose-500">*</span>
                    </label>
                    <textarea 
                        name="purpose" 
                        id="purpose" 
                        rows="2" 
                        required 
                        placeholder="Contoh: Diperlukan untuk verifikasi audit internal perpajakan tahunan..." 
                        class="w-full p-[6px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                    >{{ old('purpose') }}</textarea>
                    @error('purpose') <span class="text-rose-500 text-[10px] font-bold block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Form Actions Footer -->
            <div class="pt-[8px] border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-[6px]">
                <a href="{{ route('borrowings.index') }}" class="px-[12px] h-[28px] rounded-[3px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono font-bold text-[11px] transition flex items-center">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="!selectedArchive" 
                        :class="selectedArchive ? 'bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 text-slate-950 font-black border border-emerald-600 shadow-2xs' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 border border-slate-300 dark:border-slate-700 cursor-not-allowed font-bold'"
                        class="px-[12px] h-[28px] rounded-[3px] text-[11px] font-mono transition flex items-center gap-[6px]">
                    <i data-lucide="send" class="w-[12px] h-[12px]"></i>
                    <span>Kirim Pengajuan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
