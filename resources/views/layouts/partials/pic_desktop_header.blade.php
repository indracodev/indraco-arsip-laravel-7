<!-- 1. UNIFIED DESKTOP HEADER NAVBAR (BAGIAN 1: SINGLE COMPACT HEADER) -->
    <header class="bg-slate-950 text-white flex items-center justify-between px-3 py-1.5 border-b border-slate-800 shrink-0 shadow-sm z-50 font-mono gap-2 relative">
        <!-- Left Side: Contrasted Logo + Action Buttons + Quick Search -->
        <div class="flex items-center gap-2 flex-1 min-w-0">
            <!-- Brand & Desktop Logo (High-Contrast White Pill Container) -->
            <a href="{{ route('archives.index') }}" class="flex items-center gap-2 font-black tracking-tight text-white group shrink-0" title="{{ $globalAppName ?? 'INDRACO DMS' }}">
                <div class="px-2 py-0.5 bg-white rounded-md shadow-xs flex items-center justify-center shrink-0">
                    <img :src="appLogoUrl" alt="INDRACO Logo" class="h-4 sm:h-5 max-w-[100px] object-contain" onerror="this.onerror=null; this.src='{{ asset('images/logo_indraco.png') }}'">
                </div>
                <span class="text-xs font-black tracking-wider text-white hidden xl:inline">
                    {{ $globalAppName ?? 'DMS INDRACO' }}
                </span>
            </a>

            <!-- Action Button: F2 Draft Baru -->
            <button 
                @click="openFormWindow('archives_create')"
                type="button" 
                class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0"
                title="Buka Form Draft Pengajuan Box Baru (F2)"
            >
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">Baru (F2)</span>
            </button>

            @if(!auth()->user()->isPicDept())
            <!-- Action Button: F9 Cetak Custom Label -->
            <button type="button" @click="window.openLabelModal()" class="px-2.5 py-1 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 rounded text-xs font-bold transition flex items-center gap-1.5 shadow-2xs shrink-0 cursor-pointer" title="Cetak Custom Label (F9)">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-400"></i>
                <span class="hidden lg:inline">Cetak (F9)</span>
            </button>
            @endif

            <!-- Action Button: F5 Refresh Active Form -->
            <button 
                @click="refreshActiveWindow()" 
                type="button" 
                class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700 rounded text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0"
                title="Refresh Form yang Sedang Aktif (F5)"
            >
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-400"></i>
                <span class="hidden md:inline">Refresh (F5)</span>
            </button>

            <!-- SEARCH INPUT WITH AUTO-SUGGESTIONS & PHYSICAL LOCATION -->
            <div class="relative z-50 flex-1 min-w-[200px] max-w-xl" x-data="picQuickSearch()">
                <!-- Invisible Full Backdrop to intercept clicks outside anywhere on screen including over iframes -->
                <div 
                    x-show="showSuggestions" 
                    @mousedown="closeDropdown()"
                    @click="closeDropdown()" 
                    class="fixed inset-0 z-[985] bg-transparent cursor-default"
                    x-cloak
                ></div>

                <div class="relative z-[990] flex items-center" @mousedown.stop>
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <svg x-show="isLoading" class="w-3.5 h-3.5 text-amber-500 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                        </svg>
                        <svg x-show="!isLoading" class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>

                    <input 
                        type="text" 
                        id="picHeaderSearchInput"
                        x-model="searchQuery"
                        @input.debounce.250ms="doSearch()"
                        @focus="showSuggestions = true; if (!searchQuery) loadDefaultSuggestions()"
                        @keydown.escape="closeDropdown()"
                        @keydown.arrow-down.prevent="navigateResults(1)"
                        @keydown.arrow-up.prevent="navigateResults(-1)"
                        @keydown.enter.prevent="selectActiveResult()"
                        placeholder="Cari arsip Dept (nama dokumen, no. box, rak) [Ctrl+F]..." 
                        class="w-full pl-8 pr-7 py-1 bg-slate-900 border border-slate-700 rounded text-xs font-mono text-slate-100 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition shadow-inner"
                    >

                    <!-- Clear Search Button -->
                    <button 
                        x-show="searchQuery.length > 0" 
                        @click="clearSearch()"
                        type="button" 
                        class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-rose-400 transition cursor-pointer"
                        title="Hapus pencarian"
                        x-cloak
                    >
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <!-- AUTO-SUGGESTION FLOATING DROPDOWN PANEL (WIDE WORKSTATION DELPHI STYLE) -->
                <div 
                    x-show="showSuggestions" 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                    class="absolute left-0 mt-1 w-[680px] sm:w-[740px] max-w-[94vw] bg-white dark:bg-slate-900 border-2 border-amber-500 rounded-lg shadow-2xl z-[999] overflow-hidden font-mono text-xs"
                    @mousedown.stop
                    x-cloak
                >
                    <!-- Header Dropdown Info -->
                    <div class="px-3 py-2 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex items-center justify-between border-b border-slate-700 text-[11px]">
                        <span class="font-bold flex items-center gap-2 text-amber-300">
                            <svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            PENCARIAN ARSIP & LOKASI GUDANG
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-400/40 rounded text-[11px] font-bold">
                                DEPT: {{ auth()->user()->department->code ?? 'DEPT' }} - {{ auth()->user()->department->name ?? '' }}
                            </span>
                            <button 
                                @click="closeDropdown()" 
                                type="button" 
                                class="w-5 h-5 flex items-center justify-center rounded bg-slate-800 hover:bg-rose-600 text-slate-300 hover:text-white transition cursor-pointer font-bold text-xs"
                                title="Tutup Pencarian (Esc)"
                            >✕</button>
                        </div>
                    </div>

                    <!-- Suggestion / Quick Filters Chips (when no input or matching keywords) -->
                    <div x-show="suggestedKeywords.length > 0" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1.5">Pencarian Cepat / Topik Dokumen:</span>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="kw in suggestedKeywords" :key="kw">
                                <button 
                                    @click="selectKeyword(kw)"
                                    type="button" 
                                    class="px-2.5 py-1 bg-white dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded text-[11px] font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer"
                                >
                                    <span class="text-amber-500 font-black">#</span>
                                    <span x-text="kw"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Section Title Bar -->
                    <div class="px-3.5 py-1.5 bg-slate-50 dark:bg-slate-950/90 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px]">
                        <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                            <span x-show="!searchQuery">Arsip Rekomendasi / Terkini Dept {{ auth()->user()->department->code ?? '' }}:</span>
                            <span x-show="searchQuery">Hasil Pencarian: <span class="text-amber-600 dark:text-amber-400 font-bold" x-text="searchResults.length + ' item ditemukan'"></span></span>
                        </span>
                        <span class="text-[10px] text-slate-500 font-medium hidden sm:inline">
                            Klik item untuk membuka form detail
                        </span>
                    </div>

                    <!-- Results List Scroll Container -->
                    <div class="max-h-[380px] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        <!-- Loading State -->
                        <div x-show="isLoading" class="p-6 text-center text-slate-500 flex items-center justify-center gap-2.5 font-bold">
                            <svg class="w-5 h-5 text-amber-500 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                            </svg>
                            <span>Mencari dokumen Departemen...</span>
                        </div>

                        <!-- Results List -->
                        <template x-for="(item, idx) in searchResults" :key="item.id">
                            <div 
                                @click="selectArchive(item)"
                                :class="selectedIndex === idx ? 'bg-amber-500/15 dark:bg-slate-800 border-l-4 border-l-amber-500' : 'hover:bg-slate-50 dark:hover:bg-slate-850'"
                                class="p-3 cursor-pointer transition flex flex-col gap-1.5"
                            >
                                <!-- Top Row: No Box, Status Badge, Period -->
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-black text-amber-600 dark:text-amber-400 text-xs sm:text-sm flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                        </svg>
                                        <span x-text="(item.archive_title ? (item.archive_title + ' - ') : '') + (item.box_number || 'Penomoran Pending')"></span>
                                    </span>

                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-bold" x-text="item.periode_doc"></span>
                                        <span 
                                            :class="item.status === 'in_warehouse' ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : (item.status === 'borrowed' ? 'bg-purple-500/20 text-purple-700 dark:text-purple-300 border-purple-500/30' : 'bg-amber-500/20 text-amber-700 dark:text-amber-300 border-amber-500/30')"
                                            class="px-2 py-0.5 rounded border text-[11px] font-bold"
                                            x-text="item.status_label"
                                        ></span>
                                    </div>
                                </div>

                                <!-- Middle Row: Judul / Nama Dokumen -->
                                <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm leading-snug" x-text="item.title"></div>

                                <!-- Content Description snippet if matched -->
                                <div x-show="item.content_description" class="text-xs text-slate-500 dark:text-slate-400 italic line-clamp-2">
                                    <span class="text-slate-400 font-bold not-italic">Isi:</span> <span x-text="item.content_description"></span>
                                </div>

                                <!-- Bottom Row: Physical Warehouse & Rack Location -->
                                <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100 dark:border-slate-800 text-xs">
                                    <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold">
                                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span x-text="item.location"></span>
                                    </div>

                                    <span class="text-[11px] text-amber-600 dark:text-amber-400 font-bold hover:underline flex items-center gap-1 shrink-0">
                                        Buka Form 
                                        <svg class="w-3 h-3 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </template>

                        <!-- Empty Result State -->
                        <div x-show="!isLoading && searchResults.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400">
                            <p class="font-bold text-xs text-slate-700 dark:text-slate-300">Tidak ada dokumen ditemukan</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Tidak ditemukan berkas pada Departemen {{ auth()->user()->department->code ?? '' }} dengan kata kunci "<span class="font-bold text-amber-600 dark:text-amber-400" x-text="searchQuery"></span>"</p>
                        </div>
                    </div>

                    <!-- Footer Dropdown Shortcut Helper -->
                    <div class="px-3 py-1.5 bg-slate-100 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 text-[10px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                        <span>Gunakan <kbd class="px-1 py-0.2 bg-slate-200 dark:bg-slate-800 border rounded font-bold">↑</kbd> <kbd class="px-1 py-0.2 bg-slate-200 dark:bg-slate-800 border rounded font-bold">↓</kbd> Navigasi, <kbd class="px-1 py-0.2 bg-slate-200 dark:bg-slate-800 border rounded font-bold">Enter</kbd> Pilih</span>
                        <span><kbd class="px-1 py-0.2 bg-slate-200 dark:bg-slate-800 border rounded font-bold">Esc</kbd> Tutup</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: System Controls (Icon-Only Buttons) & Single User Profile -->
        <div class="flex items-center gap-1.5 shrink-0">
            <!-- Audio Sound Notification Toggle Button (Icon Only, Tanpa Teks) -->
            <button 
                @click="toggleSound()" 
                type="button" 
                class="p-1.5 rounded text-xs font-mono flex items-center justify-center transition border cursor-pointer select-none"
                :class="soundEnabled ? 'bg-slate-900 hover:bg-slate-800 text-emerald-400 border-emerald-500/40 shadow-xs' : 'bg-slate-900 hover:bg-slate-800 text-rose-400 border-rose-500/30'"
                :title="soundEnabled ? 'Suara Notifikasi: AKTIF (Klik untuk Mute)' : 'Suara Notifikasi: MUTE (Klik untuk Aktifkan)'"
                style="display: none !important;"
            >
                <!-- Sound ON: Speaker Bersuara Normal -->
                <svg x-show="soundEnabled" class="w-4 h-4 text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                    <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                </svg>
                <!-- Sound OFF: Speaker Dicoret Tanda Silang (Mute) -->
                <svg x-show="!soundEnabled" class="w-4 h-4 text-rose-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                    <line x1="23" y1="9" x2="17" y2="15"></line>
                    <line x1="17" y1="9" x2="23" y2="15"></line>
                </svg>
            </button>

            <!-- Real-Time Notification Bell & Dropdown (Icon Only, Tanpa Teks) -->
            <div class="relative" @click.outside="showNotificationDropdown = false">
                <button 
                    @click="showNotificationDropdown = !showNotificationDropdown; if(showNotificationDropdown) unreadNotificationsCount = 0;" 
                    type="button" 
                    class="relative p-1.5 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 rounded text-xs font-mono flex items-center justify-center transition cursor-pointer"
                    title="Aktivitas Dokumen Real-Time (Live Feed)"
                >
                    <i data-lucide="bell" class="w-4 h-4" :class="unreadNotificationsCount > 0 ? 'text-amber-400 animate-bounce' : 'text-slate-400'"></i>
                    <template x-if="unreadNotificationsCount > 0">
                        <span class="absolute -top-1 -right-1 px-1 py-0.2 bg-rose-500 text-white rounded-full text-[9px] font-black animate-pulse leading-none" x-text="unreadNotificationsCount"></span>
                    </template>
                </button>

                <!-- Notification Dropdown History Menu -->
                <div 
                    x-show="showNotificationDropdown" 
                    x-transition 
                    x-cloak 
                    class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-slate-900 border-2 border-amber-500 rounded-lg shadow-2xl z-50 overflow-hidden text-xs font-mono"
                >
                    <div class="p-2.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-700">
                        <div class="flex items-center gap-1.5 font-bold text-amber-400 text-[11px]">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <span>LIVE INCOMING DOCUMENTS</span>
                        </div>
                        <button @click="playChime()" type="button" class="text-[10px] px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 rounded flex items-center gap-1 transition cursor-pointer">
                            <i data-lucide="volume-2" class="w-3 h-3 text-emerald-400"></i> Tes Suara
                        </button>
                    </div>

                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                        <template x-if="realtimeNotifications.length === 0">
                            <div class="p-6 text-center text-slate-500 dark:text-slate-400 space-y-1">
                                <i data-lucide="inbox" class="w-6 h-6 mx-auto text-slate-400 mb-1"></i>
                                <p class="font-bold">Belum ada aktivitas dokumen baru</p>
                                <p class="text-[10px]">Dokumen yang ditambahkan akan muncul otomatis di sini secara real-time.</p>
                            </div>
                        </template>

                        <template x-for="item in realtimeNotifications" :key="item.id">
                            <div @click="openArchiveFromNotification(item)" class="p-2.5 hover:bg-amber-500/10 dark:hover:bg-slate-800/80 cursor-pointer transition flex flex-col gap-1 border-b border-slate-100 dark:border-slate-800 group">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-500/20 text-blue-700 dark:text-blue-300" x-text="item.deptCode"></span>
                                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="item.boxNumber"></span>
                                    </div>
                                    <span class="text-[10px] text-slate-400" x-text="item.time"></span>
                                </div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-amber-500 transition-colors" x-text="item.title"></div>
                                <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 pt-0.5">
                                    <span>Oleh: <strong class="text-slate-700 dark:text-slate-300" x-text="item.creatorName"></strong></span>
                                    <span class="text-amber-500 font-bold group-hover:underline flex items-center gap-0.5">
                                        <span x-text="(item.archiveId || (item.url && item.url.includes('/archives/'))) ? 'Buka Detail' : 'Buka Halaman'"></span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Light/Dark Mode Switcher (Icon Only, Tanpa Teks) -->
            <button 
                @click="theme = (theme === 'dark' ? 'light' : 'dark'); localStorage.setItem('theme', theme)" 
                type="button" 
                class="p-1.5 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded text-xs font-mono flex items-center justify-center transition cursor-pointer select-none"
                :title="theme === 'dark' ? 'Beralih ke Mode Terang (Alt+T)' : 'Beralih ke Mode Gelap (Alt+T)'" style="display: none !important;"
            >
                <!-- Saat Dark Mode: Tampilkan Matahari untuk beralih ke Terang -->
                <svg x-show="theme === 'dark'" class="w-4 h-4 text-amber-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <!-- Saat Light Mode: Tampilkan Bulan untuk beralih ke Gelap -->
                <svg x-show="theme !== 'dark'" class="w-4 h-4 text-sky-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </button>

            <!-- Fullscreen / Maximize Toggle Button (Icon Only) -->
            <button 
                @click="toggleFullscreen()" 
                type="button" 
                :title="isFullscreen ? 'Keluar Full Screen (Esc / F11)' : 'Layar Penuh (Full Screen / Maximize)'"
                class="w-7 h-7 flex items-center justify-center bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded text-amber-400 font-bold transition active:scale-95 shrink-0 cursor-pointer select-none"
            >
                <!-- Saat Fullscreen: Icon Restore (Dua Kotak) -->
                <svg x-show="isFullscreen" class="w-3.5 h-3.5 text-amber-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="8" y="8" width="13" height="13" rx="1"></rect>
                    <path d="M4 16V5a1 1 0 0 1 1-1h11"></path>
                </svg>
                <!-- Saat Normal: Icon Maximize (Satu Kotak) -->
                <svg x-show="!isFullscreen" class="w-3.5 h-3.5 text-amber-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                </svg>
            </button>

            <!-- LAN Latency & Diagnostics Monitor -->
            @include('layouts.partials.lan_monitor')

            @auth
            <!-- Profil User & Departemen (Tunggal, Tidak Dobel) -->
            <div class="flex items-center gap-2 border-l border-slate-800 pl-2.5 shrink-0">
                <div class="text-right hidden sm:block">
                    <span class="text-[10px] font-bold text-white block truncate max-w-[150px] leading-tight">{{ auth()->user()->name }}</span>
                    <span class="text-[10px] text-amber-400 font-mono block leading-tight">PIC DEPT: {{ auth()->user()->department->code ?? 'UMUM' }}</span>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="inline ml-0.5">
                    @csrf
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-900 rounded transition cursor-pointer" title="Keluar / Logout">
                        <i data-lucide="power" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </header>
    