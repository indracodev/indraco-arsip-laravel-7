@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Pengajuan Multi-Box Arsip (TB 30g) - DMS PT Indraco')

@section('content')
@php
    $from = $from ?? request('from', (!empty($isReStore) ? 'borrowings' : 'archives'));
    $backRoute = ($from === 'borrowings' || !empty($isReStore)) ? 'borrowings.index' : 'archives.index';
@endphp
<div class="max-w-6xl mx-auto space-y-3 font-sans pb-10 select-text" x-data="archiveCreateApp()" @keydown.escape.window="if(!formModalOpen && !duplicateModalOpen) window.location.href = '{{ route($backRoute, request()->has('embed') ? ['embed' => 1] : []) }}'">

    <!-- DELPHI FORM TOOLBAR HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-2.5 sm:p-3 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 rounded shrink-0">
                <i data-lucide="package-plus" class="w-4 h-4"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider truncate">Form Pengajuan Multi-Box Arsip (TB 30g)</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">Entri kolektif banyak box fisik & dense items dokumen dengan otomasi periode cerdas.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-end shrink-0">
            <!-- Counter Chip -->
            <div class="px-2.5 py-1 bg-amber-500/10 border border-amber-500/30 rounded text-xs font-mono font-bold text-amber-700 dark:text-amber-300 flex items-center gap-1.5 whitespace-nowrap shrink-0">
                <i data-lucide="boxes" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                <span x-text="boxes.length + ' Box'"></span>
                <span>•</span>
                <span x-text="getTotalItemsCount() + ' Berkas'"></span>
            </div>

            <!-- Autosave Indicator Chip -->
            <template x-if="draftSavedTime">
                <div class="px-2 py-1 bg-emerald-500/10 border border-emerald-500/30 rounded text-[11px] font-mono text-emerald-700 dark:text-emerald-300 flex items-center gap-1 shrink-0" title="Data formulir tersimpan otomatis di browser workstation">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Draft Autosave: <strong x-text="draftSavedTime"></strong></span>
                </div>
            </template>

            <!-- Tombol Cetak Form Pengajuan Dokumen -->
            <button 
                type="button" 
                @click="openFormPengajuanModal()" 
                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/50 dark:hover:bg-amber-900/50 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/60 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm whitespace-nowrap shrink-0 cursor-pointer"
                title="Buka Pratinjau & Cetak Form Pengajuan Dokumen (Standar PDF)"
            >
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0"></i>
                <span>Form Pengajuan</span>
            </button>

            <!-- Tombol Impor Excel -->
            <a href="{{ route('archives.index', ['open_import' => 1]) }}" 
               class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm whitespace-nowrap shrink-0 cursor-pointer"
               title="Unggah template spreadsheet Excel (.xlsx) untuk mengisi banyak box dan berkas dokumen secara otomatis">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                <span>Impor Excel</span>
            </a>

            <a href="{{ route($backRoute, request()->has('embed') ? ['embed' => 1] : []) }}" 
               onclick="if (window.history.length > 1 && document.referrer && document.referrer !== window.location.href) { window.history.back(); return false; }"
               class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm whitespace-nowrap shrink-0 cursor-pointer">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                <span>Kembali (Esc)</span>
            </a>
        </div>
    </div>

    <!-- RE-STORE BANNER (JIKA MODE PENYIMPANAN KEMBALI) -->
    @if(!empty($isReStore))
    <div class="p-3 bg-purple-500/10 border border-purple-500/30 rounded text-purple-900 dark:text-purple-200 text-xs font-mono flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="p-1.5 bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30 rounded shrink-0">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
            </span>
            <div>
                <strong class="font-bold uppercase tracking-wider block">Mode Pengajuan Penyimpanan Kembali Box</strong>
                <p class="text-[11px] text-purple-700 dark:text-purple-300 font-sans">
                    Formulir terisi otomatis dari riwayat penarikan ({{ count($preloadedBoxes ?? []) }} Box). Anda dapat memeriksa atau melengkapi dokumen sebelum mengajukan verifikasi ke Gudang.
                </p>
            </div>
        </div>
        <span class="px-2.5 py-1 rounded text-[10px] font-bold bg-purple-600 text-white uppercase shrink-0 self-start sm:self-auto shadow-2xs">
            Penyimpanan Kembali
        </span>
    </div>
    @endif

    @if(!empty($fromExcelImport))
    <!-- EXCEL IMPORT NOTIFICATION BANNER -->
    <div class="p-3.5 bg-blue-500/10 border-2 border-blue-500/40 rounded-lg text-blue-950 dark:text-blue-200 text-xs font-mono flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-2.5">
            <span class="p-2 bg-blue-600 text-white rounded shrink-0 shadow-2xs font-bold">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
            </span>
            <div>
                <strong class="font-bold uppercase tracking-wider block text-blue-900 dark:text-blue-300">Data Pengajuan Berhasil Dimuat dari File Excel!</strong>
                <p class="text-[11px] text-slate-700 dark:text-slate-300 font-sans mt-0.5">
                    Sebanyak <strong class="text-blue-700 dark:text-blue-400 font-mono">{{ count($preloadedBoxes ?? []) }} Box ({{ $totalItemsPreloaded ?? 0 }} Berkas)</strong> telah dimuat ke formulir pengajuan. Silakan periksa kelengkapan, cetak Form Pengajuan jika diperlukan, lalu ajukan ke Gudang.
                </p>
            </div>
        </div>
        <span class="px-2.5 py-1 rounded text-[10px] font-bold bg-blue-600 text-white uppercase shrink-0 self-start sm:self-auto shadow-2xs">
            Impor Excel
        </span>
    </div>
    @endif

    <!-- DRAFT RECOVERY NOTIFICATION BANNER -->
    <div 
        x-show="draftRestored" 
        x-transition
        x-cloak
        class="p-3 bg-amber-500/10 border-2 border-amber-500/40 rounded-lg text-amber-950 dark:text-amber-200 text-xs font-mono flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-sm"
    >
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="p-2 bg-amber-500 text-slate-950 rounded shrink-0 shadow-2xs font-bold">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
            </span>
            <div class="min-w-0">
                <strong class="font-bold uppercase tracking-wider block text-amber-900 dark:text-amber-300">Draft Pengisian Formulir Berhasil Dipulihkan Otomatis!</strong>
                <p class="text-[11px] text-slate-700 dark:text-slate-300 font-sans mt-0.5">
                    Data pengisian (<strong x-text="boxes.length + ' Box, ' + getTotalItemsCount() + ' Berkas'"></strong>) dipulihkan dari sesi sebelum terputus/refresh (Tersimpan: <strong x-text="draftSavedTime"></strong>).
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto pt-1 sm:pt-0">
            <button 
                type="button" 
                @click="draftRestored = false" 
                class="px-3 py-1.5 rounded bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition shadow-xs cursor-pointer"
            >
                Lanjutkan Mengisi
            </button>
            <button 
                type="button" 
                @click="clearSavedDraft()" 
                class="px-2.5 py-1.5 rounded bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 text-xs font-bold transition cursor-pointer"
                title="Hapus draft tersimpan dan kembali ke form kosong awal"
            >
                Kosongkan Form
            </button>
        </div>
    </div>

    <!-- MAIN FORM WINDOW -->
    <form id="archiveCreateForm" 
          action="{{ route('archives.store', request()->has('embed') ? ['embed' => 1] : []) }}" 
          method="POST" 
          enctype="multipart/form-data" 
          class="space-y-3"
          @submit="isSubmitting = true; window.formSubmitted = true; clearTimeout(autoSaveDebounceTimer); try { localStorage.removeItem('dms_draft_archive_create'); } catch(e) {}">
        @csrf
        @if(request()->has('embed'))
            <input type="hidden" name="embed" value="1">
        @endif
        @if(!empty($isReStore))
            <input type="hidden" name="is_re_store" value="1">
        @endif
        <input type="hidden" name="from" value="{{ $from }}">

        @include('archives.partials.create_unit_section')

        <!-- SECTION 2: WORKSTATION MULTI-BOX (NAVIGASI & TAB KONTROL BOX) -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl p-3.5 shadow-sm space-y-3 font-mono">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            2. Pengelolaan Multi-Box Arsip
                        </h2>
                        <p class="text-[10px] text-slate-500 font-sans">
                            Klik tab box untuk beralih. Anda dapat menambah atau menduplikat box secara instan.
                        </p>
                    </div>
                </div>

                <!-- Box Action Toolbar -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button 
                        type="button" 
                        @click="addBox()" 
                        class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-bold transition flex items-center gap-1 shadow-sm"
                        title="Tambah Box Arsip Baru"
                    >
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Tambah Box</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openDuplicateModal(activeBoxIndex)" 
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1"
                        title="Duplikasi Box dengan Pilihan Granular (Semua Berkas / Metadata Saja / Berkas Terpilih)"
                    >
                        <i data-lucide="copy" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Duplikat Box...</span>
                    </button>

                    <template x-if="boxes.length > 1">
                        <button 
                            type="button" 
                            @click="removeBox(activeBoxIndex)" 
                            class="px-2 py-1 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-300 dark:border-rose-800 rounded text-xs font-bold transition flex items-center gap-1"
                            title="Hapus Box yang Sedang Aktif"
                        >
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span>Hapus Box</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Box Navigation Tabs (Scrollable Ribbon) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1.5 scrollbar-thin">
                <template x-for="(b, bIdx) in boxes" :key="b.id">
                    <button 
                        type="button" 
                        @click="activeBoxIndex = bIdx; $nextTick(() => { if(window.lucide) lucide.createIcons(); });" 
                        class="px-3 py-1.5 rounded-lg border text-xs font-mono font-bold transition flex items-center gap-2 shrink-0 select-none cursor-pointer"
                        :class="activeBoxIndex === bIdx 
                            ? 'bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 border-amber-600 shadow-md ring-2 ring-amber-500/20' 
                            : 'bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-800 hover:bg-slate-200 dark:hover:bg-slate-800'"
                    >
                        <i data-lucide="package" class="w-3.5 h-3.5" :class="activeBoxIndex === bIdx ? 'text-slate-950' : 'text-amber-500'"></i>
                        <span x-text="'Box ' + (bIdx + 1)"></span>
                        <span class="text-[10px] font-normal max-w-[120px] truncate" x-show="b.title" x-text="b.title"></span>
                        <span 
                            class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                            :class="activeBoxIndex === bIdx ? 'bg-slate-950 text-amber-400' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                            x-text="b.items.length + ' Dokumen'"
                        ></span>
                    </button>
                </template>
            </div>

            <!-- CONTAINER FOR ALL BOXES (All inputs stay in DOM so they are fully submitted) -->
            <template x-for="(box, bIdx) in boxes" :key="box.id">
                <div x-show="activeBoxIndex === bIdx" :id="'box_container_' + bIdx" class="space-y-4 pt-2">
                    
                    <!-- BOX METADATA CARD -->
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 rounded font-black text-xs" x-text="'BOX #' + (bIdx + 1)"></span>
                                <template x-if="box.box_number">
                                    <span class="px-2 py-0.5 bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30 rounded font-black text-xs" x-text="'[' + box.box_number + ']'"></span>
                                </template>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Pengaturan Identitas & Wadah Box</span>

                                <!-- Hidden fields for origin borrowing reference -->
                                <template x-if="box.origin_borrowing_log_id">
                                    <input type="hidden" :name="'boxes[' + bIdx + '][origin_borrowing_log_id]'" :value="box.origin_borrowing_log_id">
                                </template>
                                <template x-if="box.origin_archive_id">
                                    <input type="hidden" :name="'boxes[' + bIdx + '][origin_archive_id]'" :value="box.origin_archive_id">
                                </template>
                                
                                <!-- Preset Pola Box Sebelumnya -->
                                <template x-if="bIdx > 0">
                                    <button 
                                        type="button" 
                                        @click="applyPreviousBoxPreset(bIdx)"
                                        class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded text-[10px] font-bold transition flex items-center gap-1 cursor-pointer"
                                        title="Salin sub-dept, jenis dokumen, dan kondisi fisik dari Box sebelumnya"
                                    >
                                        <i data-lucide="sparkles" class="w-3 h-3 text-blue-500"></i>
                                        <span>Gunakan Pola Box Sebelumnya</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <!-- Judul Box dengan Live Similarity Check -->
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                    <span>NAMA / JUDUL BOX <span class="text-rose-500">*</span></span>
                                    <span class="text-[10px] text-slate-400 font-normal">Cek kemiripan aktif otomatis</span>
                                </label>
                                <input 
                                    type="text" 
                                    :id="'box_title_' + bIdx"
                                    :name="'boxes[' + bIdx + '][title]'" 
                                    x-model="box.title"
                                    @input="checkSimilarity(bIdx)"
                                    required
                                    placeholder="Contoh: Voucher Kas Kecil & Pengeluaran Q1" 
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition"
                                >

                                <!-- Live Similarity Alert Banner -->
                                <template x-if="box.similarityInfo && box.similarityInfo.has_similarity">
                                    <div 
                                        class="mt-1.5 p-2 rounded border text-[11px] font-sans flex items-start gap-2 transition"
                                        :class="box.similarityInfo.is_duplicate 
                                            ? 'bg-rose-500/15 border-rose-500/40 text-rose-900 dark:text-rose-200' 
                                            : (box.similarityInfo.is_restored_box 
                                                ? 'bg-emerald-500/15 border-emerald-500/40 text-emerald-900 dark:text-emerald-200' 
                                                : (box.similarityInfo.match_type === 'identical' 
                                                    ? 'bg-amber-500/15 border-amber-500/40 text-amber-900 dark:text-amber-200' 
                                                    : 'bg-blue-50 dark:bg-blue-950/40 border-blue-300 dark:border-blue-800 text-blue-900 dark:text-blue-200'))"
                                    >
                                        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5"
                                           :class="box.similarityInfo.is_duplicate ? 'text-rose-600 dark:text-rose-400' : (box.similarityInfo.is_restored_box ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500')"></i>
                                        <div class="space-y-0.5 flex-1">
                                            <div class="font-bold flex items-center justify-between">
                                                <span x-text="box.similarityInfo.message"></span>
                                                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded font-black"
                                                      :class="box.similarityInfo.is_duplicate 
                                                        ? 'bg-rose-500/30 text-rose-800 dark:text-rose-200' 
                                                        : (box.similarityInfo.is_restored_box 
                                                            ? 'bg-emerald-500/30 text-emerald-800 dark:text-emerald-200' 
                                                            : 'bg-amber-500/30 text-amber-800 dark:text-amber-200')"
                                                      x-text="box.similarityInfo.is_duplicate ? 'DUPLIKAT (DITOLAK)' : (box.similarityInfo.is_restored_box ? 'BOX PENARIKAN (VALID)' : box.similarityInfo.similarity_percentage + '% Mirip')"></span>
                                            </div>
                                            <template x-if="box.similarityInfo.diff && (box.similarityInfo.diff.unique_in_input.length > 0 || box.similarityInfo.diff.unique_in_existing.length > 0)">
                                                <div class="text-[10px] text-slate-600 dark:text-slate-400 font-mono pt-0.5">
                                                    <span>Kata berbeda: </span>
                                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="box.similarityInfo.diff.unique_in_input.join(', ') || '-'"></span>
                                                </div>
                                            </template>
                                            <p class="text-[10px] italic" :class="box.similarityInfo.is_duplicate ? 'text-rose-700 dark:text-rose-300 font-bold' : 'text-slate-500'"
                                               x-text="box.similarityInfo.is_duplicate ? 'Nama box harus unik dan tidak boleh sama dengan box aktif lainnya. Silakan ubah nama box ini.' : (box.similarityInfo.is_restored_box ? 'Nama box sama diperbolehkan karena merupakan transaksi penyimpanan kembali dari box ditarik yang sah.' : 'Peringatan ini informatif agar Anda dapat memeriksa potensi duplikasi.')"></p>
                                        </div>
                                        <button type="button" @click="box.similarityInfo = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shrink-0 text-xs px-1 cursor-pointer">✕</button>
                                    </div>
                                </template>
                            </div>

                            <!-- Sub-Departemen Box (1 Box Bisa 1 Sub-Departemen) -->
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                    <span>SUB-DEPARTEMEN <span class="text-slate-400 font-normal">(Opsional)</span></span>
                                    <span class="text-[10px] text-slate-400 font-normal" x-text="box.sub_department_id ? (box.sub_department_id === 'inti' ? 'Inti Dept' : 'Khusus Box Ini') : 'Mewarisi Dept Induk'"></span>
                                </label>
                                <select 
                                    :name="'boxes[' + bIdx + '][sub_department_id]'" 
                                    x-model="box.sub_department_id"
                                    @change="onBoxSubDeptChange(box)"
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition shadow-2xs"
                                >
                                    <option value="">-- Ikuti Sub-Dept Induk / Semua --</option>
                                    <option value="inti">Inti Departemen</option>
                                    <template x-for="s in subDepartments" :key="s.id">
                                        <option :value="s.id" x-text="s.code + ' - ' + s.name" :selected="box.sub_department_id == s.id"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Kategori Dokumen (Terkunci Otomatis dari Master Berkas) -->
                            <div class="sm:col-span-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                    <span class="truncate" title="KATEGORI DOKUMEN">KATEGORI DOKUMEN</span>
                                    <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-0.5 shrink-0" title="Tipe dokumen otomatis terkunci sesuai Master Berkas">
                                        <i data-lucide="lock" class="w-2.5 h-2.5"></i> Terkunci
                                    </span>
                                </label>
                                <div class="relative flex items-center">
                                    <input 
                                        type="text" 
                                        :name="'boxes[' + bIdx + '][document_type]'" 
                                        x-model="box.document_type"
                                        readonly
                                        placeholder="Otomatis dari Master Berkas" 
                                        title="Tipe dokumen otomatis terkunci dari Master Berkas"
                                        class="w-full pl-2.5 pr-8 py-1.5 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-700 dark:text-slate-300 cursor-not-allowed select-none truncate"
                                    >
                                    <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 pointer-events-none"></i>
                                </div>
                            </div>

                            <!-- Wadah Fisik -->
                            <div class="sm:col-span-1">
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 truncate" title="KONDISI FISIK WADAH BOX">
                                    KONDISI FISIK WADAH BOX <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    :id="'box_condition_' + bIdx"
                                    :name="'boxes[' + bIdx + '][physical_condition]'" 
                                    x-model="box.physical_condition"
                                    required 
                                    class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                                >
                            </div>

                            <!-- Periode Dokumen (Bulan) -->
                            <div class="sm:col-span-2 space-y-1.5">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                        PERIODE DOKUMEN (BULAN) <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold px-2 py-0.5 bg-amber-500/10 rounded border border-amber-500/20" x-text="'Maks. ' + getMaxPeriodMonths() + ' Bulan (' + (getMaxPeriodMonths()/12) + ' Thn)'"></span>
                                </div>

                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                    <!-- Input Angka -->
                                    <div class="relative w-full sm:w-36 shrink-0">
                                        <input 
                                            type="number" 
                                            :id="'periode_bulan_' + bIdx"
                                            x-model="box.periode_bulan"
                                            :disabled="bIdx > 0 && box.follow_previous"
                                            @input="if(box.periode_bulan > getMaxPeriodMonths()) box.periode_bulan = getMaxPeriodMonths(); if(box.periode_bulan !== '' && box.periode_bulan < 1) box.periode_bulan = 1;"
                                            min="1" 
                                            :max="getMaxPeriodMonths()"
                                            :placeholder="'Maks. ' + getMaxPeriodMonths() + ' Bln'" 
                                            class="w-full pl-2.5 pr-14 py-1.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition disabled:bg-slate-100 dark:disabled:bg-slate-900/50 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        >
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-mono font-bold text-slate-400 pointer-events-none select-none">Bulan</span>
                                    </div>

                                    <!-- Quick Preset Buttons (Sejajar Horisontal) -->
                                    <div class="flex-1 flex flex-wrap items-center gap-1 font-mono text-[10px]" x-show="!(bIdx > 0 && box.follow_previous)">
                                        <span class="text-slate-400 text-[10px] mr-0.5 font-sans">Preset:</span>
                                        <template x-for="preset in getPresetMonths()" :key="preset">
                                            <button 
                                                type="button" 
                                                @click="box.periode_bulan = preset" 
                                                class="px-2 py-1 rounded border transition cursor-pointer text-xs"
                                                :class="box.periode_bulan == preset 
                                                    ? 'bg-amber-500 text-slate-950 border-amber-600 font-black shadow-xs' 
                                                    : 'bg-slate-100 dark:bg-slate-900 hover:bg-amber-500/20 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 font-medium'"
                                                x-text="preset + ' Bln' + (preset == 120 ? ' (10 Thn)' : (preset == 60 ? ' (5 Thn)' : ''))"
                                            ></button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Checklist Ikut Sebelumnya (untuk Box ke-2 dst) -->
                                <template x-if="bIdx > 0">
                                    <label class="flex items-center gap-1.5 pt-0.5 font-mono text-[11px] text-amber-700 dark:text-amber-400 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            x-model="box.follow_previous" 
                                            @change="if(box.follow_previous) { box.periode_bulan = boxes[bIdx - 1].periode_bulan; }"
                                            class="rounded text-amber-500 focus:ring-amber-400"
                                        >
                                        <span class="font-bold">Ikuti Periode Box Sebelumnya</span>
                                        <span class="text-[10px] text-slate-500" x-show="box.follow_previous" x-text="'(' + getPreviousBoxPeriodText(bIdx) + ')'"></span>
                                    </label>
                                </template>

                                <!-- Hidden field submitted to backend -->
                                <input 
                                    type="hidden" 
                                    :name="'boxes[' + bIdx + '][periode]'" 
                                    :value="getBoxFinalPeriod(box, bIdx)"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- REPEATER BERKAS DOKUMEN DALAM BOX INI -->
                    <div class="border border-emerald-500/30 rounded-xl p-3.5 bg-emerald-500/5 dark:bg-emerald-950/20 space-y-3">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-emerald-500/20 pb-2">
                            <div>
                                <h3 class="text-xs font-bold text-emerald-900 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="list-plus" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Rincian Berkas Dokumen dalam Box #<span x-text="bIdx + 1"></span></span>
                                </h3>
                                <p class="text-[10px] text-slate-500 font-sans">
                                    Masukkan berkas fisik di dalam box ini. Periode box dihitung otomatis dari rentang berkas di bawah.
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                                <button 
                                    type="button" 
                                    @click="addItem(bIdx)" 
                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs font-bold transition flex items-center gap-1 shadow-xs cursor-pointer"
                                    title="Tambah 1 Baris Dokumen"
                                >
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Tambah Berkas</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="addBulkItems(bIdx, 5)" 
                                    class="px-2.5 py-1 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 rounded text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                                    title="Tambah 5 Baris Dokumen Sekaligus untuk Entri Cepat"
                                >
                                    <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                                    <span>Tambah 5 Baris</span>
                                </button>
                            </div>
                        </div>

                        <!-- Dense Items Table (Mendukung copas langsung dari Excel / Spreadsheet via Ctrl+V) -->
                        <div class="border border-slate-300 dark:border-slate-700 rounded-lg overflow-x-auto bg-white dark:bg-slate-950 shadow-xs" @paste="handleTablePaste($event, bIdx)">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                                        <th class="py-2 px-2.5 w-10 text-center">NO</th>
                                        <th class="py-2 px-3 min-w-[240px]">NAMA DOKUMEN / BERKAS ARSIP <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-2.5 w-36">PERIODE MULAI <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-2.5 w-36">PERIODE SELESAI <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-3 min-w-[180px]">KETERANGAN <span class="text-rose-500">*</span></th>
                                        <th class="py-2 px-2 w-16 text-center">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-mono">
                                    <template x-for="(item, iIdx) in box.items" :key="item.id">
                                        <tr 
                                            :id="'item_row_' + bIdx + '_' + iIdx"
                                            class="transition"
                                            :class="getItemDuplicateInfo(box, item, iIdx) 
                                                ? 'bg-rose-50/80 dark:bg-rose-950/30 border-l-4 border-l-rose-500' 
                                                : 'hover:bg-emerald-500/5'"
                                        >
                                            <td class="py-2 px-2.5 text-center font-bold text-slate-500">
                                                <div class="flex flex-col items-center">
                                                    <span x-text="iIdx + 1"></span>
                                                    <template x-if="getItemDuplicateInfo(box, item, iIdx)">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-200 mt-1 shadow-xs tracking-tighter" title="Data kembar identik dengan baris lain di box ini">
                                                            DUPLIKAT
                                                        </span>
                                                    </template>
                                                </div>
                                            </td>
                                            
                                            <!-- Nama Dokumen Input / Select (Tetap Dropdown Seperti Biasa) -->
                                            <td class="py-2 px-3">
                                                <div class="flex items-center gap-1.5">
                                                    <select 
                                                        :id="'item_doc_name_' + bIdx + '_' + iIdx"
                                                        :name="'boxes[' + bIdx + '][items][' + iIdx + '][document_name]'" 
                                                        x-model="item.document_name"
                                                        @change="onMasterDocChange(box, item)"
                                                        x-init="$nextTick(() => { if (item.document_name && $el.value !== item.document_name) $el.value = item.document_name; })"
                                                        x-effect="$nextTick(() => { if (item.document_name && $el.value !== item.document_name) $el.value = item.document_name; })"
                                                        class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none transition"
                                                        :class="getItemDuplicateInfo(box, item, iIdx) ? 'border-rose-400 dark:border-rose-600 focus:border-rose-500 bg-rose-50/30' : 'border-slate-300 dark:border-slate-700 focus:border-emerald-500'"
                                                    >
                                                        <option value="" x-text="getBoxMasterArchives(box).length > 0 ? '-- Pilih dari Master Berkas Arsip --' : '-- Tidak ada master berkas untuk sub-departemen ini --'">-- Pilih dari Master Berkas Arsip --</option>
                                                        <template x-for="m in getBoxMasterArchives(box)" :key="m.id">
                                                            <option :value="m.name" x-text="(m.code ? (m.code + ' - ') : '') + m.name + (m.document_type ? ' [' + m.document_type + ']' : '')" :selected="m.name === item.document_name"></option>
                                                        </template>
                                                        <!-- Jika nama dokumen hasil paste/duplikasi/kustom tidak ada di daftar, tetap tampil sebagai opsi terpilih di dropdown -->
                                                        <template x-if="item.document_name && !getBoxMasterArchives(box).some(m => m.name === item.document_name)">
                                                            <option :value="item.document_name" x-text="item.document_name" selected></option>
                                                        </template>
                                                    </select>
                                                    @if(auth()->user()->isSuperAdmin())
                                                    <button 
                                                        type="button" 
                                                        @click="promptCustomDocName(item)" 
                                                        class="p-1.5 text-slate-400 hover:text-amber-500 rounded border border-slate-200 dark:border-slate-800 shrink-0 cursor-pointer" 
                                                        title="Ketik nama berkas khusus jika tidak ada di daftar master (Super Admin)"
                                                    >
                                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Periode Mulai (Bulan) -->
                                            <td class="py-2 px-2.5">
                                                <input 
                                                    type="month" 
                                                    :id="'item_period_start_' + bIdx + '_' + iIdx"
                                                    :name="'boxes[' + bIdx + '][items][' + iIdx + '][period_start]'" 
                                                    x-model="item.period_start"
                                                    :max="currentMonth"
                                                    @change="validatePeriod(item)"
                                                    class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none transition"
                                                    :class="getItemDuplicateInfo(box, item, iIdx) ? 'border-rose-400 dark:border-rose-600 focus:border-rose-500 bg-rose-50/30' : 'border-slate-300 dark:border-slate-700 focus:border-emerald-500'"
                                                >
                                            </td>

                                            <!-- Periode Selesai (Bulan) -->
                                            <td class="py-2 px-2.5">
                                                <input 
                                                    type="month" 
                                                    :id="'item_period_end_' + bIdx + '_' + iIdx"
                                                    :name="'boxes[' + bIdx + '][items][' + iIdx + '][period_end]'" 
                                                    x-model="item.period_end"
                                                    :max="currentMonth"
                                                    @change="validatePeriod(item)"
                                                    class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-900 border rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none transition"
                                                    :class="getItemDuplicateInfo(box, item, iIdx) ? 'border-rose-400 dark:border-rose-600 focus:border-rose-500 bg-rose-50/30' : 'border-slate-300 dark:border-slate-700 focus:border-emerald-500'"
                                                >
                                            </td>

                                            <!-- Keterangan Berkas (Textarea Auto-expand, bisa di-Enter) -->
                                            <td class="py-2 px-3 align-top">
                                                <textarea 
                                                    :id="'item_notes_' + bIdx + '_' + iIdx"
                                                    :name="'boxes[' + bIdx + '][items][' + iIdx + '][notes]'" 
                                                    x-model="item.notes" 
                                                    rows="1"
                                                    placeholder="No. berkas fisik / catatan" 
                                                    @input="$el.style.height = 'auto'; $el.style.height = Math.max(34, $el.scrollHeight) + 'px'"
                                                    x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = Math.max(34, $el.scrollHeight) + 'px'; })"
                                                    class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border rounded text-xs font-mono text-slate-700 dark:text-slate-300 placeholder-slate-400 focus:outline-none transition resize-none overflow-hidden leading-relaxed block shadow-2xs"
                                                    :class="getItemDuplicateInfo(box, item, iIdx) ? 'border-rose-400 dark:border-rose-600 focus:border-rose-500 bg-rose-50/30' : 'border-slate-300 dark:border-slate-700 focus:border-emerald-500'"
                                                    style="min-height: 34px;"
                                                ></textarea>
                                                <template x-if="getItemDuplicateInfo(box, item, iIdx)">
                                                    <div class="flex items-center gap-1 mt-1 text-[10px] font-bold text-rose-600 dark:text-rose-400">
                                                        <i data-lucide="alert-triangle" class="w-3 h-3 shrink-0"></i>
                                                        <span>Kembar identik dgn <strong x-text="getItemDuplicateInfo(box, item, iIdx).targetLabel"></strong> (Nama, Periode & Ket. sama)</span>
                                                    </div>
                                                </template>
                                            </td>

                                            <!-- Aksi Per Baris -->
                                            <td class="py-2 px-2 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button 
                                                        type="button" 
                                                        @click="duplicateItem(bIdx, iIdx)" 
                                                        class="p-1 text-slate-400 hover:text-amber-500 rounded transition cursor-pointer" 
                                                        title="Salin baris ini"
                                                    >
                                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        @click="removeItem(bIdx, iIdx)" 
                                                        class="p-1 text-rose-400 hover:text-rose-600 rounded transition cursor-pointer" 
                                                        title="Hapus baris dokumen ini"
                                                    >
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between text-[11px] font-mono text-slate-500 pt-0.5">
                            <span>Total Berkas di Box #<span x-text="bIdx + 1"></span>: <strong class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="box.items.length"></strong> Dokumen</span>
                            <span class="text-slate-400">Rentang Periode Terhitung: <strong class="text-slate-700 dark:text-slate-300" x-text="getBoxCalculatedPeriod(box).range"></strong></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- SECTION 3: UPLOAD SCAN FORMULIR & FOTO FISIK BOX ARSIP -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3.5 rounded bg-white dark:bg-slate-950 shadow-sm space-y-3 font-mono">
            <legend class="px-2 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm flex items-center gap-1.5">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                3. Berkas Bukti Formulir Pengajuan & Foto Fisik Box (Mencakup Semua Box)
            </legend>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs"
                 @file-change="if($event.detail.name === 'scan_input_form') { hasScanForm = $event.detail.hasFile && !$event.detail.isOverLimit; }">
                <!-- 1. Scan Formulir Input / Berkas Pengajuan Fisik (Wajib untuk Verifikasi Gudang) -->
                <x-file-uploader 
                    name="scan_input_form" 
                    id="scan_input_form" 
                    label="1. Scan / Berkas Formulir Pengajuan Fisik" 
                    :required="true" 
                    badge="WAJIB UNTUK PENGAJUAN" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeKB="500"
                    :maxSizeMB="0.5"
                    helperText="Cukup unggah 1 lembar berkas formulir serah terima bertanda tangan untuk seluruh box di atas (Maksimal 500 KB, PDF/JPG/PNG)."
                />

                <!-- 2. Foto Fisik Box / Lampiran Digital (Opsional) -->
                <x-file-uploader 
                    name="file" 
                    id="file" 
                    label="2. Foto Fisik Box / Lampiran Digital" 
                    :required="false" 
                    badge="OPSIONAL" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeKB="500"
                    :maxSizeMB="0.5"
                    helperText="Foto fisik box arsip atau lampiran digital (Maksimal 500 KB, PDF/JPG/PNG). Otomatis terhubung ke seluruh box."
                />
            </div>

            <div class="p-2.5 bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/40 rounded-lg text-slate-600 dark:text-slate-400 font-sans text-xs flex items-center gap-2">
                <i data-lucide="shield-check" class="w-4 h-4 text-blue-500 shrink-0"></i>
                <p class="text-[11px] leading-relaxed">
                    <strong>Efisiensi Berkas Kolektif:</strong> Berkas formulir serah terima dan foto fisik box di atas otomatis terhubung ke seluruh <strong class="text-slate-800 dark:text-slate-200" x-text="boxes.length + ' box'"></strong> yang diajukan sekaligus.
                </p>
            </div>
        </fieldset>

        @include('archives.partials.duplicate_box_modal')

        <!-- FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-3 font-mono border-t border-slate-200 dark:border-slate-800">
            <div class="text-xs text-slate-600 dark:text-slate-400 flex items-center gap-2">
                <span class="p-1.5 bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                </span>
                <span>Ringkasan Pengajuan: <strong class="text-slate-900 dark:text-white" x-text="boxes.length + ' Box'"></strong> dengan <strong class="text-slate-900 dark:text-white" x-text="getTotalItemsCount() + ' Berkas Dokumen'"></strong></span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route($backRoute, request()->has('embed') ? ['embed' => 1] : []) }}" 
                   onclick="if (window.history.length > 1 && document.referrer && document.referrer !== window.location.href) { window.history.back(); return false; }"
                   class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer">
                    Batal
                </a>

                <!-- Simpan Sebagai Draft Sementara (Semua Box - Tanpa Batasan Wajib) -->
                <button 
                    type="button" 
                    formnovalidate
                    @click="submitDraft()"
                    :disabled="isSubmitting"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded border border-slate-400 dark:border-slate-600 shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    title="Simpan sementara semua box sebagai draft usulan (tanpa pembatasan wajib isi apapun)"
                >
                    <i data-lucide="file-clock" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span x-text="isSubmitting ? 'Menyimpan Draft...' : 'Simpan Sementara (Draft)'"></span>
                </button>

                <!-- Simpan & Ajukan Semua Box ke Gudang -->
                <button 
                    type="button" 
                    @click="submitFinal('submit')"
                    :disabled="isSubmitting"
                    :class="hasScanForm ? 'bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 border-amber-600 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-pointer'"
                    class="px-5 py-2 font-black text-xs rounded border transition flex items-center gap-1.5 disabled:opacity-50"
                    title="Simpan & Ajukan seluruh box ke PIC Gudang (Wajib menyertakan Scan Formulir)"
                >
                    <i data-lucide="send" class="w-3.5 h-3.5" :class="hasScanForm ? 'text-slate-950' : 'text-slate-400 dark:text-slate-500'"></i>
                    <span x-text="isSubmitting ? 'Memproses Pengajuan...' : ('Simpan & Ajukan ' + boxes.length + ' Box Arsip')"></span>
                </button>
            </div>
        </div>
    </form>

    {{-- Komponen Modal Pratinjau & Cetak Form Pengajuan Dokumen Standar PT Indraco --}}
    <x-form-pengajuan-modal />
</div>

@push('scripts')
@include('archives.partials.create_scripts')
@endpush
@endsection
