<!-- 1. WINDOW TITLE BAR & WORKSTATION HEADER -->
        <header
            class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-700 shadow-sm shrink-0 font-mono relative z-50">
            <div class="flex items-center gap-3 flex-1 min-w-0 mr-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group shrink-0">
                    <div class="p-1 bg-white rounded shadow-sm">
                        <img :src="appLogoUrl" src="{{ $globalAppLogo ?? asset('images/logo_indraco.png') }}"
                            alt="PT Indraco Logo" class="h-5 w-auto object-contain">
                    </div>
                    <span
                        class="font-extrabold tracking-tight text-white flex items-center gap-1.5 text-xs whitespace-nowrap">
                        DMS <span class="text-amber-400 font-black">PT INDRACO</span>
                        <span
                            class="px-1.5 py-0.5 bg-amber-500/20 text-amber-300 text-[10px] rounded border border-amber-400/30">Desktop
                            Edition</span>
                    </span>
                </a>

                <!-- SUPER ADMIN GLOBAL QUICK SEARCH (ALL DOCUMENTS) -->
                <div class="relative z-50 flex-1 min-w-[320px] max-w-2xl ml-2" x-data="superAdminQuickSearch()"
                    @click.outside="closeDropdown()">
                    <div class="relative flex items-center">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <svg x-show="isLoading" class="w-4 h-4 text-amber-400 animate-spin" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" x-cloak>
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                            </svg>
                            <svg x-show="!isLoading" class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>

                        <input type="text" id="adminHeaderSearchInput" x-model="searchQuery"
                            @input.debounce.250ms="doSearch()"
                            @focus="showSuggestions = true; if (!searchQuery) loadDefaultSuggestions()"
                            @keydown.escape="closeDropdown()" @keydown.arrow-down.prevent="navigateResults(1)"
                            @keydown.arrow-up.prevent="navigateResults(-1)"
                            @keydown.enter.prevent="selectActiveResult()"
                            placeholder="{{ auth()->check() && auth()->user()->isPicGudang() ? 'Cari rak gudang & label/judul box arsip (Layout 2D) [Ctrl+F]...' : 'Cari semua dokumen (nama arsip, dept, berkas isi, no. box, rak gudang) [Ctrl+F]...' }}"
                            class="w-full pl-9 pr-8 py-1 bg-slate-950/80 border border-slate-700 hover:border-slate-600 focus:border-amber-400 rounded text-xs font-mono text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-amber-400 transition shadow-inner">

                        <!-- Clear Search Button -->
                        <button x-show="searchQuery.length > 0" @click="clearSearch()" type="button"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-rose-400 transition cursor-pointer"
                            title="Hapus pencarian" x-cloak>
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <!-- AUTO-SUGGESTION FLOATING DROPDOWN PANEL (WIDE WORKSTATION DELPHI STYLE) -->
                    <div x-show="showSuggestions" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                        class="absolute left-0 mt-1.5 w-[680px] sm:w-[780px] max-w-[94vw] bg-white dark:bg-slate-900 border-2 border-amber-500 rounded-lg shadow-2xl z-[999] overflow-hidden font-mono text-xs text-slate-800 dark:text-slate-100"
                        x-cloak>
                        <!-- Header Dropdown Info with Close Suggestion Button -->
                        <div
                            class="px-3 py-2 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex items-center justify-between border-b border-slate-700 text-[11px]">
                            <span class="font-bold flex items-center gap-2 text-amber-300">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <span>{{ auth()->check() && auth()->user()->isPicGudang() ? 'PENCARIAN LOKASI GUDANG & DENAH 2D (RAK & BOX ARSIP)' : 'PENCARIAN GLOBAL SELURUH ARSIP & LOKASI GUDANG' }}</span>
                            </span>
                            <div class="flex items-center gap-2">
                                @if (auth()->check() && auth()->user()->isPicGudang())
                                    <span
                                        class="hidden sm:inline-block px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 rounded text-[10px] font-bold">
                                        PIC GUDANG • LOKASI GUDANG & LAYOUT 2D
                                    </span>
                                @else
                                    <span
                                        class="hidden sm:inline-block px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-400/40 rounded text-[10px] font-bold">
                                        SUPER ADMIN • ALL DEPARTMENTS
                                    </span>
                                @endif
                                <!-- CLOSE SUGGESTION BUTTON IN HEADER -->
                                <button type="button" @click="closeDropdown()"
                                    class="px-2 py-0.5 bg-rose-600/90 hover:bg-rose-600 text-white rounded text-[11px] font-bold flex items-center gap-1 transition shadow cursor-pointer"
                                    title="Tutup Sugesti (Esc)">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                    <span>Tutup Sugesti</span>
                                </button>
                            </div>
                        </div>

                        <!-- DROPDOWN FILTER CONTROLS BAR (Departemen, Sub-Departemen, Periode) -->
                        <div
                            class="p-2.5 bg-slate-100 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <!-- Filter Departemen -->
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5 uppercase tracking-wider">Departemen:</label>
                                    <select x-model="selectedDept" @change="onDeptChange()"
                                        class="w-full px-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] font-mono text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                                        <option value="">-- Semua Departemen --</option>
                                        <template x-for="dept in filterDepartments" :key="dept.id">
                                            <option :value="dept.id" x-text="dept.code + ' - ' + dept.name">
                                            </option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Filter Sub-Departemen -->
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5 uppercase tracking-wider">Sub
                                        Departemen:</label>
                                    <select x-model="selectedSubDept" @change="onSubDeptChange()"
                                        :disabled="!selectedDept || subDepartmentsList().length === 0"
                                        class="w-full px-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] font-mono text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-amber-500 focus:outline-none disabled:opacity-50 disabled:bg-slate-200 dark:disabled:bg-slate-800">
                                        <option value="">-- Semua Sub-Unit --</option>
                                        <template x-for="sub in subDepartmentsList()" :key="sub.id">
                                            <option :value="sub.id"
                                                x-text="(sub.code ? '[' + sub.code + '] ' : '') + sub.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Filter Periode Dokumen -->
                                <div>
                                    <div class="flex items-center justify-between mb-0.5">
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Periode:</label>
                                        <button x-show="hasActiveFilters()" @click="resetFilters()" type="button"
                                            class="text-[10px] text-rose-500 hover:text-rose-600 font-bold transition cursor-pointer"
                                            title="Reset semua filter">
                                            ✕ Reset
                                        </button>
                                    </div>
                                    <select x-model="selectedPeriod" @change="onPeriodChange()"
                                        class="w-full px-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-[11px] font-mono text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                                        <option value="">-- Semua Periode --</option>
                                        <template x-for="p in availablePeriods" :key="p">
                                            <option :value="p" x-text="p"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Suggestion / Quick Filters Chips -->
                        <div x-show="suggestedKeywords.length > 0 && !searchQuery && !hasActiveFilters()"
                            class="px-3.5 py-2 bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800">
                            <span
                                class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1.5">
                                {{ auth()->check() && auth()->user()->isPicGudang() ? 'Saran Lokasi Gudang & Label Box:' : 'Filter Cepat / Topik Dokumen:' }}
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="kw in suggestedKeywords" :key="kw">
                                    <button @click="selectKeyword(kw)" type="button"
                                        class="px-2.5 py-1 bg-white dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded text-[11px] font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer">
                                        <span class="text-amber-500 font-black">#</span>
                                        <span x-text="kw"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Section Title Bar -->
                        <div
                            class="px-3.5 py-1.5 bg-slate-50 dark:bg-slate-950/90 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px]">
                            <span
                                class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 flex-wrap">
                                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polygon
                                        points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                                    </polygon>
                                </svg>
                                <span x-show="!searchQuery && !hasActiveFilters()">
                                    {{ auth()->check() && auth()->user()->isPicGudang() ? 'Rekomendasi Rak & Box Gudang Terkini:' : 'Sugesti Berkas Arsip Terkini:' }}
                                </span>
                                <span x-show="searchQuery || hasActiveFilters()">Hasil Pencarian: <span
                                        class="text-amber-600 dark:text-amber-400 font-bold"
                                        x-text="searchResults.length + ' item ditemukan'"></span></span>
                            </span>
                            <span class="text-[10px] text-slate-500 font-medium hidden sm:inline">
                                {{ auth()->check() && auth()->user()->isPicGudang() ? 'Klik item untuk membuka Layout 2D / Form' : 'Klik item untuk membuka form detail' }}
                            </span>
                        </div>

                        <!-- Results List Scroll Container -->
                        <div
                            class="max-h-[380px] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            <!-- Loading State -->
                            <div x-show="isLoading"
                                class="p-6 text-center text-slate-500 flex items-center justify-center gap-2.5 font-bold">
                                <svg class="w-5 h-5 text-amber-500 animate-spin" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                                </svg>
                                <span>Mencari data berkas & rak gudang...</span>
                            </div>

                            <!-- Results Items -->
                            <template x-for="(item, idx) in searchResults" :key="item.id">
                                <div @click="selectArchive(item)"
                                    :class="selectedIndex === idx ?
                                        'bg-amber-500/15 dark:bg-amber-950/40 border-l-4 border-amber-500' :
                                        'hover:bg-slate-50 dark:hover:bg-slate-800/80'"
                                    class="p-3 cursor-pointer transition flex flex-col gap-1.5 select-none">
                                    <!-- RACK ITEM CARD (FOR RACK SEARCH RESULTS) -->
                                    <template x-if="item.result_type === 'rack'">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="p-1.5 rounded bg-blue-500/20 text-blue-600 dark:text-blue-400">
                                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <rect x="2" y="3" width="20" height="18"
                                                                rx="2"></rect>
                                                            <line x1="2" y1="9" x2="22"
                                                                y2="9"></line>
                                                            <line x1="2" y1="15" x2="22"
                                                                y2="15"></line>
                                                            <line x1="12" y1="3" x2="12"
                                                                y2="21"></line>
                                                        </svg>
                                                    </span>
                                                    <div>
                                                        <span
                                                            class="font-black text-blue-600 dark:text-blue-400 text-sm"
                                                            x-text="'Rak ' + item.rack_code"></span>
                                                        <span class="text-[11px] text-slate-500 ml-1.5 font-bold"
                                                            x-text="'[' + item.room_sector + ']'"></span>
                                                    </div>
                                                </div>
                                                <span
                                                    class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 text-[10px] font-bold">
                                                    DENAH 2D RAK
                                                </span>
                                            </div>

                                            <div
                                                class="flex items-center justify-between text-xs pt-1 border-t border-slate-100 dark:border-slate-800">
                                                <span class="text-slate-600 dark:text-slate-400 font-bold"
                                                    x-text="item.content_description"></span>
                                                <span
                                                    class="text-[11px] text-amber-600 dark:text-amber-400 font-black flex items-center gap-1">
                                                    <span>Buka Layout Gudang 2D</span>
                                                    <svg class="w-3 h-3 text-amber-500" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <line x1="5" y1="12" x2="19"
                                                            y2="12"></line>
                                                        <polyline points="12 5 19 12 12 19"></polyline>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- BOX / ARCHIVE ITEM CARD -->
                                    <template x-if="item.result_type !== 'rack'">
                                        <div class="space-y-1.5">
                                            <!-- Top Row: Box Number (Left) and Department / Sub-Department (Right Kanan Atas) -->
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="font-black text-amber-600 dark:text-amber-400 text-xs sm:text-sm flex items-center gap-1.5">
                                                        <svg class="w-4 h-4 text-amber-500 shrink-0"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path
                                                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                                                            </path>
                                                            <polyline points="3.27 6.96 12 12.01 20.73 6.96">
                                                            </polyline>
                                                            <line x1="12" y1="22.08" x2="12"
                                                                y2="12"></line>
                                                        </svg>
                                                        <span
                                                            x-text="(item.archive_title ? (item.archive_title + ' - ') : '') + (item.box_number || 'Penomoran Pending')"></span>
                                                    </span>
                                                </div>

                                                <!-- Right: Department & Sub-Department Badge (Kanan Atas) -->
                                                <span
                                                    class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/20 text-[10px] font-bold uppercase flex items-center gap-1 shrink-0"
                                                    title="Departemen / Sub Departemen">
                                                    <svg class="w-3 h-3 text-blue-500 shrink-0" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <rect x="2" y="7" width="20" height="14"
                                                            rx="2" ry="2"></rect>
                                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                                    </svg>
                                                    <span
                                                        x-text="item.dept_code + (item.sub_dept ? ' / ' + item.sub_dept : (item.dept_name ? ' - ' + item.dept_name : ''))"></span>
                                                </span>
                                            </div>

                                            <!-- Middle Row: Judul / Nama Dokumen Arsip (Left) & Periode + Status (Right, di bawah Departemen) -->
                                            <div
                                                class="flex items-center justify-between gap-2 flex-wrap sm:flex-nowrap">
                                                <div
                                                    class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm leading-snug flex items-center gap-2 min-w-0">
                                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path
                                                            d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z">
                                                        </path>
                                                        <polyline points="14 2 14 8 20 8"></polyline>
                                                        <line x1="16" y1="13" x2="8"
                                                            y2="13"></line>
                                                        <line x1="16" y1="17" x2="8"
                                                            y2="17"></line>
                                                        <polyline points="10 9 9 9 8 9"></polyline>
                                                    </svg>
                                                    <span class="truncate"
                                                        x-text="item.document_name || item.title"></span>
                                                </div>

                                                <!-- Periode & Status Badge (Di Bawah Departemen) -->
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <!-- Periode Dokumen -->
                                                    <span
                                                        class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-bold whitespace-nowrap"
                                                        x-text="item.periode_doc"></span>

                                                    <!-- Status Badge -->
                                                    <span
                                                        :class="item.status === 'in_warehouse' ?
                                                            'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' :
                                                            (item.status === 'borrowed' ?
                                                                'bg-purple-500/20 text-purple-700 dark:text-purple-300 border-purple-500/30' :
                                                                'bg-amber-500/20 text-amber-700 dark:text-amber-300 border-amber-500/30'
                                                                )"
                                                        class="px-2 py-0.5 rounded border text-[11px] font-bold whitespace-nowrap"
                                                        x-text="item.status_label"></span>
                                                </div>
                                            </div>

                                            @if (!auth()->check() || !auth()->user()->isPicGudang())
                                                <!-- Content Description snippet if matched (Hidden for PIC Gudang) -->
                                                <div x-show="item.content_description"
                                                    class="text-xs text-slate-500 dark:text-slate-400 italic line-clamp-2 pl-5">
                                                    <span class="text-slate-400 font-bold not-italic">Catatan:</span>
                                                    <span x-text="item.content_description"></span>
                                                </div>
                                            @endif

                                            <!-- Bottom Row: Physical Warehouse & Rack Location -->
                                            <div
                                                class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100 dark:border-slate-800 text-xs">
                                                <div
                                                    class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold">
                                                    <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                                        <circle cx="12" cy="10" r="3"></circle>
                                                    </svg>
                                                    <span x-text="item.location"></span>
                                                </div>

                                                <span
                                                    class="text-[11px] text-amber-600 dark:text-amber-400 font-bold hover:underline flex items-center gap-1 shrink-0">
                                                    Buka Detail Form
                                                    <svg class="w-3 h-3 text-amber-500" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <line x1="5" y1="12" x2="19"
                                                            y2="12"></line>
                                                        <polyline points="12 5 19 12 12 19"></polyline>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Empty Result State -->
                            <div x-show="!isLoading && searchResults.length === 0"
                                class="p-6 text-center text-slate-500 dark:text-slate-400">
                                <p class="font-bold text-xs text-slate-700 dark:text-slate-300">Tidak ada data
                                    ditemukan</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">Tidak ditemukan data dengan kata kunci
                                    "<span class="font-bold text-amber-600 dark:text-amber-400"
                                        x-text="searchQuery"></span>"</p>
                            </div>
                        </div>

                        <!-- Dropdown Footer with Close Button and Keyboard Hint -->
                        <div
                            class="px-3.5 py-2 bg-slate-100 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                            <span class="flex items-center gap-2">
                                <span>Gunakan <kbd
                                        class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded font-mono text-[10px]">↑</kbd>
                                    <kbd
                                        class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded font-mono text-[10px]">↓</kbd>
                                    untuk memilih, <kbd
                                        class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded font-mono text-[10px]">Enter</kbd>
                                    untuk membuka</span>
                            </span>
                            <button type="button" @click="closeDropdown()"
                                class="px-2.5 py-1 bg-slate-200 dark:bg-slate-800 hover:bg-rose-600 hover:text-white text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>✕ Tutup Sugesti (Esc)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right System Info Controls -->
            <div class="flex items-center gap-2">
                <!-- Audio Sound Notification Toggle Button -->
                <button @click="toggleSound()" type="button"
                    class="p-1.5 rounded text-xs font-mono flex items-center justify-center transition border cursor-pointer select-none"
                    :class="soundEnabled ? 'bg-slate-800 hover:bg-slate-700 text-emerald-300 border-emerald-500/40 shadow-xs' :
                        'bg-slate-800/80 hover:bg-slate-700 text-rose-400 border-rose-500/30'"
                    :title="soundEnabled ? 'Suara Notifikasi: AKTIF (Klik untuk Mute)' :
                        'Suara Notifikasi: MUTE (Klik untuk Aktifkan)'">
                    <!-- Sound ON: Speaker Bersuara Normal -->
                    <svg x-show="soundEnabled" class="w-4 h-4 text-emerald-400 shrink-0" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                    <!-- Sound OFF: Speaker Dicoret Tanda Silang (Mute) -->
                    <svg x-show="!soundEnabled" class="w-4 h-4 text-rose-400 shrink-0" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" x-cloak>
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <line x1="23" y1="9" x2="17" y2="15"></line>
                        <line x1="17" y1="9" x2="23" y2="15"></line>
                    </svg>
                </button>

                <!-- Real-Time Notification Bell & Dropdown -->
                <div class="relative" @click.outside="showNotificationDropdown = false">
                    <button
                        @click="showNotificationDropdown = !showNotificationDropdown; if(showNotificationDropdown) unreadNotificationsCount = 0;"
                        type="button"
                        class="relative p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded text-xs font-mono flex items-center justify-center transition cursor-pointer"
                        title="Aktivitas Dokumen Real-Time (Live Feed)">
                        <i data-lucide="bell" class="w-4 h-4"
                            :class="unreadNotificationsCount > 0 ? 'text-amber-400 animate-bounce' : 'text-slate-400'"></i>
                        <template x-if="unreadNotificationsCount > 0">
                            <span
                                class="absolute -top-1 -right-1 px-1 py-0.2 bg-rose-500 text-white rounded-full text-[9px] font-black animate-pulse leading-none"
                                x-text="unreadNotificationsCount"></span>
                        </template>
                    </button>

                    <!-- Notification Dropdown History Menu -->
                    <div x-show="showNotificationDropdown" x-transition x-cloak
                        class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-slate-900 border-2 border-amber-500 rounded-lg shadow-2xl z-50 overflow-hidden text-xs font-mono">
                        <div
                            class="p-2.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-700">
                            <div class="flex items-center gap-1.5 font-bold text-amber-400 text-[11px]">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                <span>LIVE INCOMING DOCUMENTS</span>
                            </div>
                            <button @click="playChime()" type="button"
                                class="text-[10px] px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 rounded flex items-center gap-1 transition cursor-pointer">
                                <i data-lucide="volume-2" class="w-3 h-3 text-emerald-400"></i> Tes Suara
                            </button>
                        </div>

                        <div class="max-h-80 overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                            <template x-if="realtimeNotifications.length === 0">
                                <div class="p-6 text-center text-slate-500 dark:text-slate-400 space-y-1">
                                    <i data-lucide="inbox" class="w-6 h-6 mx-auto text-slate-400 mb-1"></i>
                                    <p class="font-bold">Belum ada aktivitas dokumen baru</p>
                                    <p class="text-[10px]">Dokumen yang ditambahkan oleh client/PIC akan otomatis
                                        muncul di sini secara real-time.</p>
                                </div>
                            </template>

                            <template x-for="item in realtimeNotifications" :key="item.id">
                                <div @click="openArchiveFromNotification(item)"
                                    class="p-2.5 hover:bg-amber-500/10 dark:hover:bg-slate-800/80 cursor-pointer transition flex flex-col gap-1 border-b border-slate-100 dark:border-slate-800 group">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-500/20 text-blue-700 dark:text-blue-300"
                                                x-text="item.deptCode"></span>
                                            <span class="text-xs font-bold text-amber-600 dark:text-amber-400"
                                                x-text="item.boxNumber"></span>
                                        </div>
                                        <span class="text-[10px] text-slate-400" x-text="item.time"></span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-amber-500 transition-colors"
                                        x-text="item.title"></div>
                                    <div
                                        class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 pt-0.5">
                                        <span>Oleh: <strong class="text-slate-700 dark:text-slate-300"
                                                x-text="item.creatorName"></strong></span>
                                        <span
                                            class="text-amber-500 font-bold group-hover:underline flex items-center gap-0.5">
                                            <span x-text="(item.archiveId || (item.url && item.url.includes('/archives/'))) ? 'Buka Detail' : 'Buka Halaman'"></span>
                                            <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Theme Toggle Button -->
                <button @click="theme = (theme === 'dark' ? 'light' : 'dark'); localStorage.setItem('theme', theme)"
                    type="button"
                    class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded text-xs font-mono flex items-center justify-center transition cursor-pointer select-none"
                    :title="theme === 'dark' ? 'Beralih ke Mode Terang (Alt+T)' : 'Beralih ke Mode Gelap (Alt+T)'">
                    <!-- Saat Dark Mode: Tampilkan Matahari untuk beralih ke Mode Terang -->
                    <svg x-show="theme === 'dark'" class="w-4 h-4 text-amber-300 shrink-0" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <!-- Saat Light Mode: Tampilkan Bulan untuk beralih ke Mode Gelap -->
                    <svg x-show="theme !== 'dark'" class="w-4 h-4 text-sky-400 shrink-0" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" x-cloak>
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>

                <!-- Fullscreen / Maximize Toggle Button -->
                <button @click="toggleFullscreen()" type="button"
                    :title="isFullscreen ? 'Keluar Full Screen (Esc / F11)' : 'Layar Penuh (Full Screen / Maximize)'"
                    class="w-7 h-7 flex items-center justify-center bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded text-amber-400 font-bold transition active:scale-95 shrink-0 cursor-pointer select-none">
                    <!-- Saat Fullscreen: Icon Restore (Dua Kotak) -->
                    <svg x-show="isFullscreen" class="w-3.5 h-3.5 text-amber-400 shrink-0" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect x="8" y="8" width="13" height="13" rx="1"></rect>
                        <path d="M4 16V5a1 1 0 0 1 1-1h11"></path>
                    </svg>
                    <!-- Saat Normal: Icon Maximize (Satu Kotak) -->
                    <svg x-show="!isFullscreen" class="w-3.5 h-3.5 text-amber-400 shrink-0" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" x-cloak>
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                    </svg>
                </button>

                <!-- LAN Latency & Diagnostics Monitor -->
                @include('layouts.partials.lan_monitor')

                @auth
                    <div class="flex items-center gap-1.5 ml-1">
                        <span
                            class="text-slate-300 font-bold text-[10px] hidden sm:inline">{{ auth()->user()->name }}</span>
                        <span
                            class="px-2 py-0.5 bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded text-[10px] font-bold">
                            {{ auth()->user()->role_label }}
                        </span>

                        <!-- Button Ganti Password Mandiri -->
                        <button type="button" 
                            @click="$dispatch('open-change-password-modal')" 
                            class="p-1 text-slate-400 hover:text-amber-400 hover:bg-slate-800 rounded transition cursor-pointer"
                            title="Ganti Password Saya">
                            <i data-lucide="key" class="w-3.5 h-3.5"></i>
                        </button>

                        <form action="{{ route('logout') }}" method="POST" class="inline ml-0.5">
                            @csrf
                            <button type="submit"
                                class="p-1 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded transition"
                                title="Keluar Aplikasi">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        