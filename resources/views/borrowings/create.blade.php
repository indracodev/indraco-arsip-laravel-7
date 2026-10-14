@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Permintaan Penarikan Arsip - DMS PT Indraco')

@section('content')
<div class="max-w-5xl mx-auto space-y-3 font-sans pb-10"
     x-data="{
         boxes: [
             {
                 id: Date.now(),
                 selectedArchive: null,
                 search: '',
                 isOpen: false
             }
         ],
         activeBoxIndex: 0,
         hasApprovalFile: false,
         archives: window.borrowingArchives || [],
         init() {
             const initialId = {{ $selectedArchiveId ?? 'null' }};
             if (initialId) {
                 const found = this.archives.find(a => a.id == initialId);
                 if (found) {
                     this.selectArchive(0, found);
                 }
             }
             this.$nextTick(() => {
                 if (window.lucide) lucide.createIcons();
             });
         },
         addBox(archive = null) {
             const newBox = {
                 id: Date.now() + Math.random(),
                 selectedArchive: archive,
                 search: archive ? (archive.box_number ? '[' + archive.box_number + '] ' + archive.title : archive.title) : '',
                 isOpen: !archive
             };
             this.boxes.push(newBox);
             this.activeBoxIndex = this.boxes.length - 1;
             this.$nextTick(() => {
                 if (window.lucide) lucide.createIcons();
                 const el = document.getElementById('boxSearchInput_' + this.activeBoxIndex);
                 if (el) el.focus();
             });
         },
         async removeBox(index) {
             if (this.boxes.length <= 1) return;
             if (window.showConfirmModal) {
                 const ok = await window.showConfirmModal({
                     title: 'Hapus Box Penarikan',
                     message: `Apakah Anda yakin ingin membatalkan pilihan Box #${index + 1} dari daftar penarikan?`,
                     type: 'danger',
                     confirmText: 'Ya, Hapus Box'
                 });
                 if (!ok) return;
             }
             this.boxes.splice(index, 1);
             if (this.activeBoxIndex >= this.boxes.length) {
                 this.activeBoxIndex = this.boxes.length - 1;
             }
             this.$nextTick(() => {
                 if (window.lucide) lucide.createIcons();
             });
         },
         getFilteredArchives(boxIdx) {
             const b = this.boxes[boxIdx];
             if (!b || !b.search.trim()) return this.archives.slice(0, 15);
             const q = b.search.toLowerCase();
             return this.archives.filter(a => 
                 (a.box_number && a.box_number.toLowerCase().includes(q)) ||
                 (a.title && a.title.toLowerCase().includes(q)) ||
                 (a.period_text && a.period_text.toLowerCase().includes(q)) ||
                 (a.department && a.department.code && a.department.code.toLowerCase().includes(q))
             );
         },
         isArchiveSelectedByOther(arcId, currentBoxIdx) {
             return this.boxes.some((b, idx) => idx !== currentBoxIdx && b.selectedArchive && b.selectedArchive.id === arcId);
         },
         async selectArchive(boxIdx, arc) {
             if (this.isArchiveSelectedByOther(arc.id, boxIdx)) {
                 if (window.showConfirmModal) {
                     await window.showConfirmModal({
                         title: 'Box Sudah Dipilih',
                         message: 'Berkas arsip ' + (arc.box_number || arc.title) + ' sudah dipilih pada box lain.',
                         type: 'warning',
                         confirmText: 'Mengerti'
                     });
                 }
                 return;
             }
             this.boxes[boxIdx].selectedArchive = arc;
             this.boxes[boxIdx].search = arc.box_number ? '[' + arc.box_number + '] ' + arc.title : arc.title;
             this.boxes[boxIdx].isOpen = false;
             this.$nextTick(() => {
                 if (window.lucide) lucide.createIcons();
             });
         },
         clearSelection(boxIdx) {
             this.boxes[boxIdx].selectedArchive = null;
             this.boxes[boxIdx].search = '';
             this.boxes[boxIdx].isOpen = true;
             this.$nextTick(() => {
                 const el = document.getElementById('boxSearchInput_' + boxIdx);
                 if (el) el.focus();
             });
         },
         get selectedBoxesCount() {
             return this.boxes.filter(b => b.selectedArchive !== null).length;
         },
         get allBoxesFilled() {
             return this.boxes.length > 0 && this.boxes.every(b => b.selectedArchive !== null);
         },
         get canSubmit() {
             return this.allBoxesFilled && this.hasApprovalFile;
         },

         // Form Pengajuan Dokumen (Penarikan) State
         formModalOpen: false,
         formPengajuanData: {
             no_form: '',
             tanggal_pengajuan: '{{ date("Y-m-d") }}',
             dept_display: @json(auth()->user()->department ? auth()->user()->department->name : ''),
             pic_pemohon: @json(auth()->user()->name ?? ''),
             tipe: 'Penarikan',
             masa_simpan: 1,
             catatan: 'Penarikan fisik kardus dan dokumen arsip untuk keperluan unit kerja.',
             approval_pemohon: @json(auth()->user()->name ?? ''),
             approval_atasan: '',
             approval_pimpinan: '',
             approval_admin_gudang: 'Admin Gudang',
             approval_tgl: '{{ date("Y-m-d") }}'
         },
         formPengajuanRows: [],

         openFormPengajuanModal() {
             const selected = this.boxes.filter(b => b.selectedArchive !== null);
             if (selected.length > 0 && selected[0].selectedArchive && selected[0].selectedArchive.department) {
                 const dept = selected[0].selectedArchive.department;
                 const subDept = selected[0].selectedArchive.sub_department;
                 if (subDept && subDept.name) {
                     this.formPengajuanData.dept_display = `${dept.name || dept.code} / ${subDept.name}`;
                 } else {
                     this.formPengajuanData.dept_display = dept.name || dept.code || '';
                 }
             } else if (!this.formPengajuanData.dept_display) {
                 this.formPengajuanData.dept_display = @json(auth()->user()->department ? auth()->user()->department->name : '');
             }

             const purposeEl = document.getElementById('purpose');
             if (purposeEl && purposeEl.value && purposeEl.value.trim()) {
                 this.formPengajuanData.catatan = purposeEl.value.trim();
             }

             this.populateFormRows();
             this.formModalOpen = true;
             this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
         },

         populateFormRows() {
             const rows = [];
             const selected = this.boxes.filter(b => b.selectedArchive !== null);
             const count = Math.max(12, selected.length);

             for (let i = 0; i < count; i++) {
                 if (i < selected.length) {
                     const arc = selected[i].selectedArchive;
                     let start = '';
                     let end = '';
                     if (arc.period_start_date) {
                         const parts = arc.period_start_date.split('-');
                         start = parts.length >= 2 ? `${parts[1]}-${parts[0]}` : arc.period_start_date;
                     } else if (arc.period_text) {
                         start = arc.period_text;
                     }
                     if (arc.period_end_date) {
                         const parts = arc.period_end_date.split('-');
                         end = parts.length >= 2 ? `${parts[1]}-${parts[0]}` : arc.period_end_date;
                     } else if (arc.period_text) {
                         end = arc.period_text;
                     }

                     rows.push({
                         no: i + 1,
                         title: arc.title || '',
                         label_kardus: arc.box_number || arc.archive_code || '',
                         period_start: start,
                         period_end: end
                     });
                 } else {
                     rows.push({
                         no: i + 1,
                         title: '',
                         label_kardus: '',
                         period_start: '',
                         period_end: ''
                     });
                 }
             }
             this.formPengajuanRows = rows;
         },

         getDeptSubDeptDisplay() {
             return this.formPengajuanData.dept_display || '';
         },

         formatDateDisplay(dateStr) {
             if (!dateStr) return '';
             if (typeof dateStr !== 'string') return '';
             try {
                 const parts = dateStr.split('-');
                 if (parts.length === 3) {
                     return `${parts[2]}-${parts[1]}-${parts[0]}`;
                 }
             } catch (e) {}
             return dateStr;
         },

         printFormPengajuan() {
             window.print();
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
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengajuan Penarikan Dokumen Arsip Tersimpan • Dukungan Multi-Box Penarikan • Wajib Dokumen Persetujuan (Approval)</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Counter Chip -->
            <div class="px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/30 rounded text-xs font-mono font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                <i data-lucide="boxes" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span x-text="selectedBoxesCount + ' Box Dipilih'"></span>
            </div>

            <!-- Tombol Cetak Form Pengajuan Penarikan -->
            <button 
                type="button" 
                @click="openFormPengajuanModal()" 
                class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60 rounded text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm shrink-0 cursor-pointer"
                title="Buka Pratinjau & Cetak Form Pengajuan Dokumen Penarikan (Standar PDF/A4)"
            >
                <i data-lucide="printer" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                <span>Form Penarikan</span>
            </button>

            <a href="{{ route('borrowings.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm shrink-0">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Kembali (Esc)</span>
            </a>
        </div>
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

        <!-- Hidden Archive IDs (Multi-Box Support & Single Fallback) -->
        <template x-for="(b, bIdx) in boxes" :key="b.id">
            <input type="hidden" name="archive_ids[]" :value="b.selectedArchive ? b.selectedArchive.id : ''">
        </template>
        <input type="hidden" name="archive_id" :value="boxes[0]?.selectedArchive?.id || ''">
        <input type="hidden" name="is_permanent" value="1">

        <!-- SECTION 1: WORKSTATION MULTI-BOX PENARIKAN -->
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl p-3.5 shadow-sm space-y-3 font-mono">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            1. Pengelolaan Multi-Box Penarikan Arsip
                        </h2>
                        <p class="text-[10px] text-slate-500 font-sans">
                            Klik tab box untuk beralih. Anda dapat menambah box untuk mengajukan penarikan banyak box sekaligus.
                        </p>
                    </div>
                </div>

                <!-- Box Action Toolbar -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button 
                        type="button" 
                        @click="addBox()" 
                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs font-bold transition flex items-center gap-1 shadow-sm cursor-pointer"
                        title="Tambah Box Target Penarikan Baru"
                    >
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Tambah Box</span>
                    </button>

                    <template x-if="boxes.length > 1">
                        <button 
                            type="button" 
                            @click="removeBox(activeBoxIndex)" 
                            class="px-2 py-1 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-300 dark:border-rose-800 rounded text-xs font-bold transition flex items-center gap-1 cursor-pointer"
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
                            ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 text-white border-emerald-600 shadow-md ring-2 ring-emerald-500/20' 
                            : 'bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-800 hover:bg-slate-200 dark:hover:bg-slate-800'"
                    >
                        <i data-lucide="package" class="w-3.5 h-3.5" :class="activeBoxIndex === bIdx ? 'text-white' : 'text-emerald-500'"></i>
                        <span x-text="'Box ' + (bIdx + 1)"></span>
                        <span class="text-[10px] font-normal max-w-[120px] truncate" x-show="b.selectedArchive" x-text="b.selectedArchive ? (b.selectedArchive.box_number || b.selectedArchive.title) : ''"></span>
                        <span 
                            class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                            :class="activeBoxIndex === bIdx ? 'bg-slate-950 text-emerald-300' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                            x-text="b.selectedArchive ? 'Siap' : 'Pilih'"
                        ></span>
                    </button>
                </template>
            </div>

            <!-- Ringkasan Box Terpilih (Muncul jika ada lebih dari 1 box) -->
            <div x-show="boxes.length > 1" class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 font-mono text-xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                        Ringkasan Box Target Penarikan (<span x-text="selectedBoxesCount"></span>/<span x-text="boxes.length"></span> Box Terisi)
                    </span>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400">Klik kartu untuk beralih</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                    <template x-for="(b, bIdx) in boxes" :key="b.id">
                        <div @click="activeBoxIndex = bIdx" class="p-2 rounded bg-white dark:bg-slate-900 border transition cursor-pointer flex items-center justify-between gap-2"
                             :class="activeBoxIndex === bIdx ? 'border-emerald-500 shadow-sm' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'">
                            <div class="min-w-0">
                                <span class="text-[10px] font-bold text-slate-400 block" x-text="'Box ' + (bIdx + 1)"></span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white truncate block" x-text="b.selectedArchive ? (b.selectedArchive.box_number || b.selectedArchive.title) : '(Belum dipilih)'"></span>
                            </div>
                            <span class="p-1 rounded text-[10px] font-bold shrink-0"
                                  :class="b.selectedArchive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300'"
                                  x-text="b.selectedArchive ? 'OK' : 'Kosong'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- CONTAINER ACTIVE BOX FORM -->
            <template x-for="(box, bIdx) in boxes" :key="box.id">
                <div x-show="activeBoxIndex === bIdx" class="space-y-3 pt-1">
                    
                    <!-- Search Input Box -->
                    <div class="space-y-1.5">
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">
                            PENCARIAN BERKAS GUDANG (UNTUK BOX <span x-text="bIdx + 1"></span>) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" @click.away="box.isOpen = false">
                            <div class="relative">
                                <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 absolute left-2.5 top-2.5"></i>
                                <input 
                                    type="text" 
                                    :id="'boxSearchInput_' + bIdx"
                                    x-model="box.search"
                                    @focus="box.isOpen = true"
                                    @input="box.isOpen = true; box.selectedArchive = null"
                                    placeholder="Ketik Nomor Box (misal: BOX-...) atau Judul Berkas arsip..." 
                                    class="w-full pl-8 pr-8 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition shadow-2xs"
                                >
                                <!-- Clear Selection Icon -->
                                <button type="button" x-show="box.search.length > 0" @click="clearSelection(bIdx)" class="absolute right-2.5 top-2 text-slate-400 hover:text-rose-500 cursor-pointer">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>

                            <!-- Autocomplete Dropdown List -->
                            <div x-show="box.isOpen && getFilteredArchives(bIdx).length > 0" 
                                 x-cloak
                                 x-transition 
                                 class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xl z-50 max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
                                <template x-for="arc in getFilteredArchives(bIdx)" :key="arc.id">
                                    <div @click="!isArchiveSelectedByOther(arc.id, bIdx) && selectArchive(bIdx, arc)" 
                                         class="p-2.5 transition flex items-center justify-between gap-3"
                                         :class="isArchiveSelectedByOther(arc.id, bIdx) 
                                            ? 'opacity-40 bg-slate-50 dark:bg-slate-950 cursor-not-allowed' 
                                            : 'hover:bg-emerald-500/10 dark:hover:bg-emerald-500/20 cursor-pointer'">
                                        <div class="space-y-0.5 min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-1.5 py-0.2 rounded border border-amber-500/20 text-[10px]" x-text="arc.box_number || 'NO-BOX'"></span>
                                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.2 rounded" x-text="arc.department ? arc.department.code : 'GEN'"></span>
                                                <span x-show="isArchiveSelectedByOther(arc.id, bIdx)" class="text-[9px] font-bold text-rose-500 bg-rose-500/10 px-1.5 py-0.2 rounded border border-rose-500/20">Sudah Dipilih di Box Lain</span>
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
                            <div x-show="box.isOpen && getFilteredArchives(bIdx).length === 0" x-cloak class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-2xl p-3 text-center text-slate-500 text-xs font-mono z-50">
                                Tidak ada berkas arsip yang cocok dengan pencarian di departemen ini.
                            </div>
                        </div>
                    </div>

                    <!-- DETAIL INFORMASI BERKAS YANG DIPILIH PADA BOX AKTIF -->
                    <div x-show="box.selectedArchive" x-cloak x-transition class="bg-slate-50 dark:bg-slate-900/60 border border-emerald-500/40 rounded p-3 space-y-2.5 font-mono shadow-2xs">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="p-1 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded">
                                    <i data-lucide="folder-check" class="w-3.5 h-3.5"></i>
                                </span>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block" x-text="'BERKAS TERPILIH UNTUK BOX ' + (bIdx + 1)"></span>
                                    <span class="text-xs font-black text-amber-600 dark:text-amber-400" x-text="box.selectedArchive?.box_number || 'PENOMORAN PENDING'"></span>
                                </div>
                            </div>

                            <button type="button" @click="clearSelection(bIdx)" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer">
                                <i data-lucide="refresh-cw" class="w-3 h-3 text-emerald-500"></i> Ganti Berkas
                            </button>
                        </div>

                        <div class="space-y-0.5">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white" x-text="box.selectedArchive?.title"></h3>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400" x-text="box.selectedArchive?.content_description || '-'"></p>
                        </div>

                        <!-- Grid Details 4 Kolom -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs pt-1">
                            <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                                <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">DEPARTEMEN</span>
                                <span class="font-bold text-slate-800 dark:text-white truncate block text-[11px]" x-text="box.selectedArchive?.department ? (box.selectedArchive.department.code + ' - ' + box.selectedArchive.department.name) : 'UMUM'"></span>
                            </div>

                            <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                                <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">LOKASI RAK GUDANG</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 truncate block text-[11px]" x-text="box.selectedArchive?.short_location || box.selectedArchive?.full_slot_location || (box.selectedArchive?.location ? box.selectedArchive.location.full_location : 'Gudang')"></span>
                            </div>

                            <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                                <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">PERIODE DOKUMEN</span>
                                <span class="font-bold text-amber-600 dark:text-amber-400 truncate block text-[11px]" x-text="box.selectedArchive?.period_text || box.selectedArchive?.periode_doc || '-'"></span>
                            </div>

                            <div class="p-2 rounded bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                                <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 block uppercase">KONDISI FISIK</span>
                                <span class="font-bold text-slate-800 dark:text-white truncate block text-[11px]" x-text="box.selectedArchive?.physical_condition || 'Baik'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

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

            <!-- Fast Print Action Banner -->
            <div class="p-2.5 rounded-lg bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs font-mono">
                <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                    <i data-lucide="info" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span>Belum memiliki lembar formulir fisik resmi untuk ditandatangani sebelum diunggah?</span>
                </div>
                <button 
                    type="button" 
                    @click="openFormPengajuanModal()" 
                    class="px-3 py-1 bg-white dark:bg-slate-900 hover:bg-emerald-50 text-emerald-700 dark:text-emerald-300 font-bold text-xs rounded border border-emerald-300 dark:border-emerald-700 shadow-2xs flex items-center gap-1.5 transition cursor-pointer shrink-0"
                >
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Cetak Form Penarikan</span>
                </button>
            </div>

            <div @file-change="hasApprovalFile = $event.detail.hasFile && !$event.detail.isOverLimit">
                <x-file-uploader 
                    name="approval_file" 
                    id="approval_file" 
                    label="Berkas Dokumen Persetujuan (Approval)" 
                    :required="true" 
                    badge="WAJIB UNTUK PENARIKAN" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :maxSizeKB="500"
                    :maxSizeMB="0.5"
                    helperText="Wajib melampirkan berkas scan approval penarikan bertandatangan PIC/Manager (Format: PDF, JPG, PNG Maks. 500 KB)."
                />
                @error('approval_file') <span class="text-rose-500 font-mono text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </fieldset>

        <!-- DELPHI FORM ACTION BAR -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-2 font-mono">
            <!-- Left status hint -->
            <div class="text-xs">
                <span x-show="selectedBoxesCount === 0" class="text-slate-500 font-bold">• Silakan pilih berkas arsip terlebih dahulu</span>
                <span x-show="selectedBoxesCount > 0 && !allBoxesFilled" class="text-amber-600 dark:text-amber-400 font-bold">• Ada slot box yang belum dipilih berkas arsipnya</span>
                <span x-show="allBoxesFilled && !hasApprovalFile" class="text-rose-500 font-bold">• Unggah berkas dokumen approval untuk mengaktifkan tombol pengajuan</span>
                <span x-show="canSubmit" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> 
                    <span x-text="selectedBoxesCount + ' box berkas & approval lengkap, siap diajukan'"></span>
                </span>
            </div>

            <!-- Right Buttons -->
            <div class="flex items-center gap-2">
                <a href="{{ route('borrowings.index', request()->has('embed') ? ['embed' => 1] : []) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="!canSubmit" 
                        :class="canSubmit ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-black border-emerald-700 shadow-md cursor-pointer' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-not-allowed font-bold'"
                        class="px-5 py-2 text-xs rounded border transition flex items-center gap-1.5">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span x-show="selectedBoxesCount <= 1">Ajukan Penarikan Arsip</span>
                    <span x-show="selectedBoxesCount > 1" x-text="'Ajukan Penarikan (' + selectedBoxesCount + ' Box Arsip)'"></span>
                </button>
            </div>
        </div>
    </form>

    {{-- Komponen Modal Pratinjau & Cetak Form Pengajuan Dokumen Standar PT Indraco --}}
    <x-form-pengajuan-modal />
</div>
@endsection
