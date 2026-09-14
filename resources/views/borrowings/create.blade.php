@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Permintaan Peminjaman Arsip - DMS PT Indraco')

@section('content')
<div class="w-full space-y-6">
    
    <!-- Navigation & Header Section -->
    <div>
        <a href="{{ route('borrowings.index') }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline inline-flex items-center gap-1 mb-2">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali ke Log Peminjaman
        </a>
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <i data-lucide="file-symlink" class="w-7 h-7 text-emerald-600 dark:text-emerald-400"></i>
            Formulir Permintaan Peminjaman Dokumen Arsip
        </h1>
        <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm font-medium">
            Cari berkas fisik di gudang yang sesuai dengan departemen Anda, periksa detail dokumen, lalu ajukan pinjam.
        </p>
    </div>

    <!-- Main Full-Width Form Card -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6"
         x-data="{
             search: '',
             selectedArchive: null,
             isOpen: false,
             archives: {{ Js::from($archives) }},
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
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs flex items-center gap-3 font-medium">
            <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0"></i>
            <div>
                <span class="font-bold block text-amber-950 dark:text-amber-200">Filter Keamanan Departemen Terkunci:</span>
                Hanya menampilkan berkas arsip milik departemen <span class="font-black text-amber-900 dark:text-amber-100 uppercase">{{ auth()->user()->department->name ?? 'Departemen Anda' }} ({{ auth()->user()->department->code ?? 'DEPT' }})</span> dengan status tersimpan di gudang.
            </div>
        </div>
        @endif

        <form action="{{ route('borrowings.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Hidden Archive ID input -->
            <input type="hidden" name="archive_id" :value="selectedArchive ? selectedArchive.id : ''" required>

            <!-- STEP 1: SEARCH & SELECT ARCHIVE DOCUMENT -->
            <div class="space-y-3">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    1. Cari Berkas Dokumen Arsip <span class="text-rose-500">*</span>
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
                            placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas..." 
                            class="w-full pl-10 pr-10 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition font-medium"
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
                            <div @click="selectArchive(arc)" class="p-3.5 hover:bg-emerald-500/10 dark:hover:bg-emerald-500/20 cursor-pointer transition flex items-center justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-black text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20" x-text="arc.box_number || 'NO-BOX'"></span>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white" x-text="arc.title"></h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="arc.period_text || arc.period_start_date"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <span x-text="arc.location ? arc.location.full_location : 'Gudang'"></span>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty Search Result State -->
                    <div x-show="isOpen && filteredArchives.length === 0" class="absolute left-0 right-0 top-full mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-4 text-center text-slate-500 text-xs z-50">
                        Tidak ada berkas arsip yang sesuai dengan pencarian Anda di departemen ini.
                    </div>
                </div>
                @error('archive_id') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- DETAIL INFORMASI BERKAS YANG DIPILIH -->
            <div x-show="selectedArchive" x-transition class="bg-slate-50 dark:bg-slate-900/90 border-2 border-emerald-500/30 rounded-2xl p-5 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl">
                            <i data-lucide="folder-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">BERKAS ARSIP TERPILIH</span>
                            <span class="font-mono text-base font-black text-amber-600 dark:text-amber-400" x-text="selectedArchive?.box_number || 'DRAFT'"></span>
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
                        <span class="font-extrabold text-emerald-600 dark:text-emerald-400" x-text="selectedArchive?.location ? selectedArchive.location.full_location : 'Gudang'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block uppercase">PERIODE BERKAS</span>
                        <span class="font-extrabold text-amber-600 dark:text-amber-400" x-text="selectedArchive?.period_text || '-'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block uppercase">KONDISI FISIK</span>
                        <span class="font-extrabold text-slate-900 dark:text-white" x-text="selectedArchive?.physical_condition || 'Baik'"></span>
                    </div>
                </div>
            </div>

            <!-- STEP 2 & 3: RETURN DATE & PURPOSE IN BALANCED GRID -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                <!-- Step 2: Expected Return Date (1 Column) -->
                <div class="space-y-1.5 md:col-span-1">
                    <label for="expected_return_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        2. Estimasi Pengembalian <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="expected_return_date" 
                        id="expected_return_date" 
                        value="{{ old('expected_return_date', \Carbon\Carbon::now()->addDays(7)->format('Y-m-d')) }}" 
                        min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                        required
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-sm text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-medium transition"
                    >
                    @error('expected_return_date') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Step 3: Purpose / Reason (2 Columns) -->
                <div class="space-y-1.5 md:col-span-2">
                    <label for="purpose" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        3. Maksud / Alasan Keperluan Peminjaman <span class="text-rose-500">*</span>
                    </label>
                    <textarea 
                        name="purpose" 
                        id="purpose" 
                        rows="3" 
                        required 
                        placeholder="Contoh: Diperlukan untuk verifikasi audit internal perpajakan tahunan..." 
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 font-medium transition"
                    >{{ old('purpose') }}</textarea>
                    @error('purpose') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Form Actions Footer -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('borrowings.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="!selectedArchive" 
                        :class="selectedArchive ? 'bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 text-slate-950 font-black shadow-lg shadow-emerald-500/20' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 cursor-not-allowed font-bold'"
                        class="px-6 py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Kirim Permintaan Peminjaman
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
