<!-- DELPHI GROUPBOX FILTER PANEL (TGroupBox Delphi Desktop Style) -->
    <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 font-mono text-xs shadow-sm" x-data="{ submitting: false }">
        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
            <i data-lucide="filter" class="w-3.5 h-3.5 text-amber-500"></i>
            Filter & Pencarian Data Katalog
        </legend>

        <form action="{{ route('archives.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 pt-1" @submit="submitting = true">
            <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
            <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

            <!-- Search Keyword -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block flex items-center justify-between">
                    <span>CARI KEYWORD</span>
                    <span class="text-amber-600 dark:text-amber-400">[Ctrl+F]</span>
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Judul, No. Box, Isi Berkas..." 
                        class="w-full pl-8 pr-3 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                    >
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1.5"></i>
                </div>
            </div>

            <!-- Department Filter -->
            <div class="space-y-1" style="display: none !important;">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block flex items-center justify-between">
                    <span>DEPARTEMEN</span>
                    @if(auth()->user()->isPicDept())
                        <span class="text-amber-600 dark:text-amber-400 font-bold text-[9px]">[Terkunci]</span>
                    @endif
                </label>
                @if(auth()->user()->isPicDept())
                    @php $myDept = $departments->first(); @endphp
                    <input type="hidden" name="department_id" value="{{ $myDept->id ?? auth()->user()->department_id }}">
                    <select disabled class="w-full py-1 px-2.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-700 dark:text-slate-300 cursor-not-allowed">
                        <option value="{{ $myDept->id ?? auth()->user()->department_id }}" selected>
                            {{ $myDept ? ($myDept->code . ' - ' . $myDept->name) : (auth()->user()->department->name ?? 'Departemen Anda') }}
                        </option>
                    </select>
                @else
                    <select name="department_id" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="">-- Semua Departemen --</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->code }} - {{ $dept->name }}
                        </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- Sub-Department Filter -->
            @php
                $availableSubDepts = collect();
                if (auth()->user()->isPicDept()) {
                    $myDept = $departments->first();
                    $availableSubDepts = $myDept && $myDept->subDepartments ? $myDept->subDepartments : collect();
                } else {
                    foreach($departments as $dept) {
                        if ($dept->subDepartments) {
                            $availableSubDepts = $availableSubDepts->concat($dept->subDepartments);
                        }
                    }
                }
            @endphp
            @if($availableSubDepts->isNotEmpty())
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">SUB-DEPARTEMEN</label>
                <select name="sub_department_id" x-model="filterSubDept" @change="onFilterSubDeptChange()" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Sub-Bagian --</option>
                    @foreach($availableSubDepts as $sdept)
                    <option value="{{ $sdept->id }}" {{ request('sub_department_id') == $sdept->id ? 'selected' : '' }}>
                        {{ $sdept->name }} ({{ $sdept->code }})
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Document Type Filter (Cascading by Sub-Department) -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block flex items-center justify-between">
                    <span>TIPE DOKUMEN</span>
                    <span x-show="filterSubDept" class="text-amber-500 text-[9px] font-mono font-bold" style="display:none;">Filtered</span>
                </label>
                <select name="document_type" x-model="filterDocType" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Tipe Dokumen --</option>
                    <template x-for="t in availableDocTypes" :key="t">
                        <option :value="t" x-text="t" :selected="t === filterDocType"></option>
                    </template>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">STATUS WORKFLOW</label>
                <select name="status" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Status Workflow --</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (Simpan Sementara / Revisi)</option>
                    <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Antrean Verifikasi</option>
                    <option value="approved_booked" {{ request('status') == 'approved_booked' ? 'selected' : '' }}>Approved / Booked</option>
                    <option value="in_warehouse" {{ request('status') == 'in_warehouse' ? 'selected' : '' }}>Di Gudang</option>
                    <option value="pending_extension" {{ request('status') == 'pending_extension' ? 'selected' : '' }}>Pengajuan Perpanjangan</option>
                    <option value="out" {{ request('status') == 'out' || request('status') == 'borrowed' || request('status') == 'taken' ? 'selected' : '' }}>Keluar (Out)</option>
                    <option value="pending_destruction" {{ request('status') == 'pending_destruction' ? 'selected' : '' }}>Antrean Pemusnahan</option>
                    <option value="destroyed" {{ request('status') == 'destroyed' ? 'selected' : '' }}>Dimusnahkan</option>
                </select>
            </div>

            <!-- Expiry Alert Filter -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">EXPIRY MASA SIMPAN</label>
                <select name="expiry_filter" class="w-full py-1 px-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition">
                    <option value="">-- Semua Expiry --</option>
                    <option value="expiring_soon" {{ request('expiry_filter') == 'expiring_soon' ? 'selected' : '' }}>Mendekati Expiry (&le; 90 Hari)</option>
                    <option value="expired" {{ request('expiry_filter') == 'expired' ? 'selected' : '' }}>Sudah Kadaluarsa</option>
                </select>
            </div>

            <!-- Submit & Reset Filter Buttons -->
            <div class="space-y-1 flex items-end gap-1.5">
                <button type="submit" :disabled="submitting" class="flex-1 py-1 px-3 bg-slate-800 hover:bg-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded text-xs font-mono font-bold border border-slate-700 transition flex items-center justify-center gap-1.5 disabled:opacity-50 h-[30px] shadow-sm">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                    <i data-lucide="filter" class="w-3.5 h-3.5 text-amber-400" x-show="!submitting"></i>
                    <span x-text="submitting ? 'Memuat...' : 'Filter'"></span>
                </button>

                @if(request()->hasAny(['search', 'department_id', 'sub_department_id', 'document_type', 'status', 'expiry_filter']))
                <a href="{{ route('archives.index') }}" class="py-1 px-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-mono font-bold border border-slate-300 dark:border-slate-700 transition flex items-center justify-center h-[30px]" title="Reset Filter">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </a>
                @endif
            </div>
        </form>
    </fieldset>

    