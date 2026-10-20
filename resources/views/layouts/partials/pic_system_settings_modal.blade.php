    <!-- MODAL PENGATURAN SISTEM & TAMPILAN (GANTI LOGO & UKURAN FONT GLOBAL)      -->
    <!-- ========================================================================= -->
    <div x-show="openSettingsModal" x-cloak 
         class="fixed inset-0 z-[100] bg-slate-950/85 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
         style="z-index: 100;">
        <div @click.away="closeSettings()" 
             class="delphi-window bg-white dark:bg-slate-950 border-2 border-amber-500/70 dark:border-amber-600 rounded-lg max-w-xl w-full shadow-2xl overflow-hidden font-mono flex flex-col my-auto">
            
            <!-- Modal Title Bar -->
            <div class="bg-gradient-to-r from-amber-950 via-slate-900 to-indigo-950 text-white px-3.5 py-2.5 flex items-center justify-between border-b border-amber-500/40 select-none shrink-0 font-bold text-xs">
                <div class="flex items-center gap-2">
                    <span class="p-1 bg-amber-500/20 text-amber-400 border border-amber-400/40 rounded">
                        <i data-lucide="sliders" class="w-4 h-4"></i>
                    </span>
                    <span>Pengaturan Sistem & Tampilan</span>
                </div>
                <button @click="closeSettings()" type="button" class="text-slate-400 hover:text-white text-xs font-bold px-2 py-1 rounded hover:bg-rose-600 transition" title="Tutup (Esc)">✕</button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-4 font-sans text-xs overflow-y-auto max-h-[80vh]">
                
                @if(auth()->check() && auth()->user()->isSuperAdmin())
                <!-- SECTION 1: GANTI LOGO SISTEM (SuperAdmin Only) -->
                <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-slate-50 dark:bg-slate-900/50 space-y-3 shadow-xs">
                    <legend class="px-2 font-mono text-[11px] font-bold text-amber-800 dark:text-amber-400 bg-amber-100 dark:bg-slate-800 border border-amber-400 dark:border-amber-700 rounded shadow-sm flex items-center gap-1.5">
                        <i data-lucide="image" class="w-3.5 h-3.5 text-amber-600"></i>
                        1. Kustomisasi Logo Aplikasi (Header & Laporan)
                    </legend>

                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <!-- Current / Preview Logo Box -->
                        <div class="w-36 h-20 p-2 bg-white dark:bg-slate-950 border-2 border-dashed border-amber-500/50 rounded flex flex-col items-center justify-center text-center shadow-xs shrink-0">
                            <img :src="newLogoPreview || appLogoUrl" alt="Logo Preview" class="max-h-12 max-w-full object-contain">
                            <span class="text-[9px] font-mono text-slate-400 mt-1" x-text="newLogoPreview ? 'Preview Baru' : 'Logo Aktif'"></span>
                        </div>

                        <!-- Upload Controls -->
                        <div class="flex-1 space-y-2 font-mono text-xs w-full">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Pilih File Gambar Logo Baru:
                                </label>
                                <input 
                                    type="file" 
                                    @change="onLogoSelected($event)" 
                                    accept=".png,.jpg,.jpeg,.svg,.webp"
                                    class="w-full px-2 py-1 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[11px] file:font-bold file:bg-amber-500/20 file:text-amber-700 dark:file:text-amber-400"
                                >
                                <span class="text-[10px] text-slate-500 block mt-0.5">Format: PNG, JPG, SVG, WebP (Max. 2MB). Rekomendasi background transparan.</span>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <button 
                                    @click="resetLogo()" 
                                    type="button" 
                                    class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[11px] font-bold border border-slate-300 dark:border-slate-700 transition flex items-center gap-1"
                                >
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Reset Logo Default</span>
                                </button>
                                <span x-show="newLogoFile" class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1" x-cloak>
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i> Siap Diupload
                                </span>
                            </div>
                        </div>
                    </div>
                </fieldset>
                @else
                <div class="p-2.5 bg-amber-500/10 border border-amber-500/30 rounded text-amber-800 dark:text-amber-300 flex items-center gap-2 text-xs font-mono">
                    <i data-lucide="info" class="w-4 h-4 text-amber-500 shrink-0"></i>
                    <span>Kustomisasi logo aplikasi dikelola oleh Administrator Sistem. Anda dapat menyesuaikan ukuran font tampilan di bawah ini.</span>
                </div>
                @endif

                <!-- SECTION 2: GANTI UKURAN FONT GLOBAL -->
                <fieldset class="border border-indigo-500/40 p-3.5 rounded bg-indigo-50/40 dark:bg-indigo-950/20 space-y-3 shadow-xs font-mono">
                    <legend class="px-2 font-mono text-[11px] font-bold text-indigo-800 dark:text-indigo-400 bg-indigo-100 dark:bg-slate-800 border border-indigo-400 dark:border-indigo-700 rounded shadow-sm flex items-center gap-1.5">
                        <i data-lucide="type" class="w-3.5 h-3.5 text-indigo-600"></i>
                        Ukuran Font Tampilan Antarmuka (Font Scaling)
                    </legend>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-700 dark:text-slate-300 font-bold">Ukuran Font Saat Ini:</span>
                            <span class="px-2.5 py-0.5 bg-indigo-600 text-white rounded font-bold text-xs" x-text="tempFontSize"></span>
                        </div>

                        <!-- Presets Buttons -->
                        <div>
                            <span class="block text-[10px] text-slate-500 font-bold uppercase mb-1.5">Pilihan Skala Cepat (Preset):</span>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5">
                                <button 
                                    @click="applyFontSizeLive('10px')" 
                                    type="button" 
                                    class="px-2 py-1.5 rounded border text-xs font-bold transition flex flex-col items-center justify-center text-center"
                                    :class="tempFontSize === '10px' ? 'bg-indigo-600 text-white border-indigo-700 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-indigo-50'"
                                >
                                    <span>Mini (10px)</span>
                                    <span class="text-xs opacity-75">Ultra Compact</span>
                                </button>
                                <button 
                                    @click="applyFontSizeLive('14px')" 
                                    type="button" 
                                    class="px-2 py-1.5 rounded border text-xs font-bold transition flex flex-col items-center justify-center text-center"
                                    :class="tempFontSize === '14px' ? 'bg-indigo-600 text-white border-indigo-700 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-indigo-50'"
                                >
                                    <span>Kecil (14px)</span>
                                    <span class="text-xs opacity-75">Compact / 75%</span>
                                </button>
                                <button 
                                    @click="applyFontSizeLive('19px')" 
                                    type="button" 
                                    class="px-2 py-1.5 rounded border text-xs font-bold transition flex flex-col items-center justify-center text-center"
                                    :class="tempFontSize === '19px' ? 'bg-indigo-600 text-white border-indigo-700 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-indigo-50'"
                                >
                                    <span>Standar (19px)</span>
                                    <span class="text-xs opacity-75">Default / 100%</span>
                                </button>
                                <button 
                                    @click="applyFontSizeLive('21px')" 
                                    type="button" 
                                    class="px-2 py-1.5 rounded border text-xs font-bold transition flex flex-col items-center justify-center text-center"
                                    :class="tempFontSize === '21px' ? 'bg-indigo-600 text-white border-indigo-700 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-indigo-50'"
                                >
                                    <span>Besar (21px)</span>
                                    <span class="text-xs opacity-75">Large / 110%</span>
                                </button>
                                <button 
                                    @click="applyFontSizeLive('23px')" 
                                    type="button" 
                                    class="px-2 py-1.5 rounded border text-xs font-bold transition flex flex-col items-center justify-center text-center col-span-2 sm:col-span-1"
                                    :class="tempFontSize === '23px' ? 'bg-indigo-600 text-white border-indigo-700 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-indigo-50'"
                                >
                                    <span>X-Large (23px)</span>
                                    <span class="text-xs opacity-75">Ekstra / 120%</span>
                                </button>
                            </div>
                        </div>

                        <!-- Live Slider -->
                        <div class="space-y-1 pt-1">
                            <div class="flex justify-between text-[10px] text-slate-500 font-bold">
                                <span>10px (Paling Kecil)</span>
                                <span>Slider Presisi (10px - 26px)</span>
                                <span>26px (Ekstra Besar)</span>
                            </div>
                            <input 
                                type="range" 
                                min="10" 
                                max="26" 
                                step="1" 
                                :value="parseInt(tempFontSize) || 19" 
                                @input="applyFontSizeLive($event.target.value + 'px')"
                                class="w-full accent-indigo-600 cursor-pointer h-2 bg-slate-300 dark:bg-slate-700 rounded-lg appearance-none"
                            >
                        </div>

                        <!-- Live Preview Sample Box -->
                        <div class="p-2.5 bg-white dark:bg-slate-950 rounded border border-slate-300 dark:border-slate-700 space-y-1.5 shadow-inner">
                            <div class="text-[10px] text-slate-500 font-bold uppercase">Live Preview Teks & Komponen Antarmuka:</div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 dark:text-white">DMS PT INDRACO Desktop Edition</span>
                                <span class="px-2 py-0.5 bg-amber-500/20 text-amber-700 dark:text-amber-300 rounded font-bold text-xs border border-amber-500/30">TB 30g</span>
                                <button type="button" class="px-2 py-0.5 bg-indigo-600 text-white rounded font-bold text-xs">Tombol Uji</button>
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 font-sans leading-relaxed">
                                Skala ukuran font ini secara realtime mempengaruhi seluruh tabel data DBGrid, popup form, dialog, label box, dan tata letak jendela.
                            </p>
                        </div>
                    </div>
                </fieldset>

                <!-- SECTION 3: KUSTOMISASI NOTIFIKASI PING / LATENSI LAN (Super Admin Only) -->
                @if(auth()->check() && auth()->user()->isSuperAdmin())
                <fieldset class="border border-sky-500/40 p-3.5 rounded bg-sky-50/40 dark:bg-sky-950/20 space-y-3 shadow-xs font-mono">
                    <legend class="px-2 font-mono text-[11px] font-bold text-sky-800 dark:text-sky-400 bg-sky-100 dark:bg-slate-800 border border-sky-400 dark:border-sky-700 rounded shadow-sm flex items-center gap-1.5">
                        <i data-lucide="network" class="w-3.5 h-3.5 text-sky-600"></i>
                        3. Notifikasi Ping & Latensi LAN (Super Admin)
                    </legend>

                    <div class="space-y-3 font-sans">
                        <!-- Toggle Switch -->
                        <div class="p-3 bg-white dark:bg-slate-950 rounded border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 shadow-2xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 text-xs">
                                    <span>Popup Toast Peringatan Latensi</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                          :class="pingAlertEnabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 dark:border-slate-700'"
                                          x-text="pingAlertEnabled ? 'Aktif' : 'Nonaktif (Mute)'"></span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">
                                    Tampilkan popup peringatan merah di pojok kanan bawah jika koneksi LAN lambat atau terputus.
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" x-model="pingAlertEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-sky-600"></div>
                            </label>
                        </div>

                        <!-- Threshold Setting -->
                        <div class="p-3 bg-white dark:bg-slate-950 rounded border border-slate-200 dark:border-slate-800 space-y-2.5 shadow-2xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs">
                                <div>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">Ambang Batas Latensi (Threshold ms):</span>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Peringatan hanya muncul jika latensi LAN melebihi batas ini berturut-turut.</p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <input 
                                        type="number" 
                                        min="50" 
                                        max="10000" 
                                        step="50"
                                        x-model.number="pingAlertThresholdMs" 
                                        class="w-24 px-2.5 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-1 focus:ring-sky-500"
                                    >
                                    <span class="text-xs font-mono font-bold text-slate-500">ms</span>
                                </div>
                            </div>

                            <!-- Presets -->
                            <div class="flex items-center gap-1.5 pt-1">
                                <span class="text-[10px] text-slate-400 font-bold uppercase font-mono shrink-0">Preset:</span>
                                <div class="flex flex-wrap items-center gap-1 font-mono text-[11px]">
                                    <button type="button" @click="pingAlertThresholdMs = 250" class="px-2 py-0.5 rounded border transition cursor-pointer" :class="pingAlertThresholdMs === 250 ? 'bg-sky-600 text-white border-sky-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-200'">250 ms</button>
                                    <button type="button" @click="pingAlertThresholdMs = 500" class="px-2 py-0.5 rounded border transition cursor-pointer" :class="pingAlertThresholdMs === 500 ? 'bg-sky-600 text-white border-sky-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-200'">500 ms (Standar)</button>
                                    <button type="button" @click="pingAlertThresholdMs = 1000" class="px-2 py-0.5 rounded border transition cursor-pointer" :class="pingAlertThresholdMs === 1000 ? 'bg-sky-600 text-white border-sky-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-200'">1000 ms (1 dtk)</button>
                                    <button type="button" @click="pingAlertThresholdMs = 2000" class="px-2 py-0.5 rounded border transition cursor-pointer" :class="pingAlertThresholdMs === 2000 ? 'bg-sky-600 text-white border-sky-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-200'">2000 ms (Longgar)</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
                @endif

                <!-- SECTION 4: PEMELIHARAAN & RESET DATA (MAINTENANCE) (Super Admin Only) -->
                @if(auth()->check() && auth()->user()->isSuperAdmin())
                <fieldset class="border border-rose-500/40 p-3.5 rounded bg-rose-50/30 dark:bg-rose-950/20 space-y-3 shadow-xs font-mono">
                    <legend class="px-2 font-mono text-[11px] font-bold text-rose-800 dark:text-rose-400 bg-rose-100 dark:bg-slate-800 border border-rose-400 dark:border-rose-700 rounded shadow-sm flex items-center gap-1.5">
                        <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-rose-600"></i>
                        4. Pemeliharaan & Reset Database (Super Admin)
                    </legend>

                    <div class="space-y-2.5 text-xs">
                        <!-- Action 0: Backup Database -->
                        <div class="p-2.5 bg-white dark:bg-slate-950 rounded border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200">
                                    <i data-lucide="database" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    <span>Backup Database (.sql)</span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                                    Mengunduh seluruh skema tabel & data arsip sistem ke dalam file SQL Dump langsung ke komputer Anda.
                                </p>
                            </div>
                            <a 
                                href="{{ route('master.settings.backup_database') }}" 
                                target="_blank"
                                @click="playClickSound()"
                                class="shrink-0 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs rounded border border-emerald-700 shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <i data-lucide="download-cloud" class="w-3.5 h-3.5"></i>
                                <span>Backup Database</span>
                            </a>
                        </div>

                        <!-- Action 1: Clear Log History -->
                        <div class="p-2.5 bg-white dark:bg-slate-950 rounded border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200">
                                    <i data-lucide="history" class="w-3.5 h-3.5 text-rose-500"></i>
                                    <span>Kosongkan Log History</span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                                    Menghapus seluruh rekaman audit trail, riwayat aktivitas pengguna, dan log histori gudang.
                                </p>
                            </div>
                            <button 
                                @click="clearLogsAction()" 
                                type="button" 
                                :disabled="maintenanceLoading"
                                class="shrink-0 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold text-xs rounded border border-rose-700 shadow-xs transition flex items-center justify-center gap-1 disabled:opacity-50"
                            >
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                <span>Kosongkan Log</span>
                            </button>
                        </div>

                        <!-- Action 2: Clear Box Allocations -->
                        <div class="p-2.5 bg-white dark:bg-slate-950 rounded border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200">
                                    <i data-lucide="package-x" class="w-3.5 h-3.5 text-amber-500"></i>
                                    <span>Kosongkan Box di Rak Gudang</span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                                    Mereset semua penempatan box di slot rak gudang menjadi kosong (data berkas arsip tetap aman tersimpan).
                                </p>
                            </div>
                            <button 
                                @click="clearBoxAllocationsAction()" 
                                type="button" 
                                :disabled="maintenanceLoading"
                                class="shrink-0 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-bold text-xs rounded border border-amber-700 shadow-xs transition flex items-center justify-center gap-1 disabled:opacity-50"
                            >
                                <i data-lucide="archive-restore" class="w-3.5 h-3.5"></i>
                                <span>Kosongkan Box Rak</span>
                            </button>
                        </div>

                        <!-- Action 3: Clear All Archive Submissions -->
                        <div class="p-2.5 bg-white dark:bg-slate-950 rounded border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200">
                                    <i data-lucide="folder-x" class="w-3.5 h-3.5 text-red-600"></i>
                                    <span>Kosongkan Data Pengajuan Box Arsip</span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                                    Menghapus seluruh pengajuan box berkas arsip, butir item, histori penarikan & pemusnahan secara permanen.
                                </p>
                            </div>
                            <button 
                                @click="clearArchivesAction()" 
                                type="button" 
                                :disabled="maintenanceLoading"
                                class="shrink-0 px-3 py-1.5 bg-red-700 hover:bg-red-800 active:bg-red-900 text-white font-bold text-xs rounded border border-red-800 shadow-xs transition flex items-center justify-center gap-1 disabled:opacity-50"
                            >
                                <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                                <span>Kosongkan Semua Arsip</span>
                            </button>
                        </div>

                        <!-- Action 4: Git Pull & Restart Server (Opsi A) -->
                        <div class="p-2.5 bg-indigo-50/60 dark:bg-indigo-950/30 rounded border border-indigo-200 dark:border-indigo-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-1.5 font-bold text-indigo-900 dark:text-indigo-300">
                                    <i data-lucide="git-pull-request" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                    <span>Git Pull & Restart Server</span>
                                    <span class="px-1.5 py-0.2 bg-indigo-200 dark:bg-indigo-800 text-[10px] text-indigo-900 dark:text-indigo-100 rounded font-mono font-normal">Opsi A</span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans">
                                    Mengambil kode terbaru dari Git (<code class="text-indigo-600 dark:text-indigo-400 font-mono">git pull</code>), membersihkan cache Laravel, dan me-restart server otomatis.
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button @click="viewGitPullLogAction()" type="button"
                                    class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded border border-slate-300 dark:border-slate-700 shadow-xs transition flex items-center gap-1 cursor-pointer"
                                    title="Lihat riwayat log proses git pull terakhir">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                    <span>Log</span>
                                </button>
                                <button @click="gitPullRestartAction()" type="button"
                                    :disabled="maintenanceLoading"
                                    class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs rounded border border-indigo-700 shadow-xs transition flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="maintenanceLoading ? 'animate-spin' : ''"></i>
                                    <span>Pull & Restart</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </fieldset>
                @endif
            </div>

            <!-- Modal Footer Actions -->
            <div class="p-3 bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 flex items-center justify-between font-mono text-xs shrink-0">
                <button type="button" @click="closeSettings()" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded">
                    Batal
                </button>
                <button 
                    @click="saveSettings()" 
                    type="button" 
                    :disabled="settingsSaving"
                    class="px-5 py-2 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded border border-amber-600 shadow-md transition flex items-center gap-1.5"
                >
                    <i data-lucide="save" class="w-3.5 h-3.5 text-slate-950" x-show="!settingsSaving"></i>
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="settingsSaving" x-cloak></i>
                    <span x-text="settingsSaving ? 'Menyimpan...' : 'Simpan Pengaturan Permanen'"></span>
                </button>
            </div>
        </div>
    </div>