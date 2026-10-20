    <!-- MODAL IMPOR MULTI-BOX & BUTIR ARSIP (EXCEL / CSV) -->
    <div x-show="openImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-import-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="if(!importSubmitting) openImportModal = false"></div>
        <div class="flex min-h-screen items-center justify-center p-3 sm:p-4">
            <div class="relative w-full max-w-4xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden font-sans text-slate-800 dark:text-slate-100 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-5 py-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <span class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-xl">
                            <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                        </span>
                        <div>
                            <h3 id="modal-import-title" class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider font-mono">
                                Impor Multi-Box & Butir Dokumen (Excel / CSV)
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Unggah ratusan berkas dalam banyak box sekaligus dengan otomasi perhitungan periode & retensi.
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="if(!importSubmitting) openImportModal = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-5 space-y-5 overflow-y-auto flex-1">
                    <!-- Instruction & Template Download -->
                    <div class="p-4 bg-gradient-to-r from-blue-50/70 to-indigo-50/70 dark:from-blue-950/20 dark:to-indigo-950/20 border border-blue-200 dark:border-blue-900/40 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                        <div class="space-y-1">
                            <div class="font-bold text-blue-900 dark:text-blue-200 flex items-center gap-1.5 font-mono">
                                <i data-lucide="info" class="w-4 h-4 text-blue-500"></i>
                                Petunjuk Pengisian Multi-Box:
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 text-[11px] leading-relaxed">
                                @if(Auth::user() && Auth::user()->isPicDept())
                                    Identitas box (<span class="font-mono font-bold text-blue-600 dark:text-blue-400">NO_BOX_SEMENTARA</span>, Judul, Dept) cukup diisi <strong>1 kali</strong> pada baris pertama box. Baris butir dokumen berikutnya cukup isi Nama Dokumen &amp; Periode. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">NO_BUTIR</span> otomatis urut (1, 2, 3...) jika dikosongkan. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">MASA_SIMPAN_BULAN</span> diisi angka bulan murni (contoh: <code>60</code> untuk 60 bulan/5 tahun, <code>12</code> untuk 12 bulan). Tab <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">REFERENSI_KODE</span> tersedia sebagai panduan kode departemen.
                                @else
                                    Identitas box (<span class="font-mono font-bold text-blue-600 dark:text-blue-400">NO_BOX_SEMENTARA</span>, Judul, Dept, Rak) cukup diisi <strong>1 kali</strong> pada baris pertama box. Baris butir berikutnya dalam box yang sama cukup isi Nama Dokumen &amp; Periode. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">NO_BUTIR</span> otomatis urut jika dikosongkan. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">MASA_SIMPAN_BULAN</span> diisi angka bulan murni (contoh: <code>60</code>). Tab <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">REFERENSI_KODE</span> untuk panduan kode departemen &amp; rak gudang.
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('archives.import.template', ['format' => 'xlsx']) }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs shadow-sm flex items-center gap-1.5 transition">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span>Template Excel (.xlsx)</span>
                            </a>
                            <a href="{{ route('archives.import.template', ['format' => 'csv']) }}" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg font-bold text-xs transition">
                                <span>CSV</span>
                            </a>
                        </div>
                    </div>

                    <!-- File Dropzone -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider font-mono">
                            Pilih File Spreadsheet (.xlsx / .csv)
                        </label>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <input type="file" 
                                   id="multi_box_excel_file_input" 
                                   accept=".xlsx,.xls,.csv" 
                                   @change="handleImportFileSelect($event)" 
                                   class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-950/40 dark:file:text-blue-300 border border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50/50 dark:bg-slate-900 p-1">
                            
                            <button type="button" 
                                    @click="submitImportPreview()" 
                                    :disabled="!importFile || importPreviewing" 
                                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold text-xs shadow-sm transition flex items-center justify-center gap-1.5 shrink-0 disabled:opacity-50">
                                <span x-show="importPreviewing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <svg x-show="!importPreviewing" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 7V5a2 2 0 0 1 2-2h2"/>
                                    <path d="M17 3h2a2 2 0 0 1 2 2v2"/>
                                    <path d="M21 17v2a2 2 0 0 1-2 2h-2"/>
                                    <path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                                </svg>
                                <span x-text="importPreviewing ? 'Menganalisis...' : 'Validasi File'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Error Alert List -->
                    <template x-if="importErrors && importErrors.length > 0">
                        <div class="p-3.5 bg-rose-500/10 border border-rose-500/20 rounded-xl space-y-1.5">
                            <div class="font-bold text-xs text-rose-700 dark:text-rose-400 flex items-center gap-1.5">
                                <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                                <span>Ditemukan Kesalahan Validasi:</span>
                            </div>
                            <ul class="list-disc list-inside text-xs text-rose-600 dark:text-rose-300 space-y-1 font-mono">
                                <template x-for="(err, eIdx) in importErrors" :key="eIdx">
                                    <li x-text="err"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <!-- Warning Alert List -->
                    <template x-if="importWarnings && importWarnings.length > 0">
                        <div class="p-3.5 bg-amber-500/10 border border-amber-500/20 rounded-xl space-y-1.5">
                            <div class="font-bold text-xs text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                                <span>Peringatan & Catatan Penyesuaian:</span>
                            </div>
                            <ul class="list-disc list-inside text-xs text-amber-600 dark:text-amber-300 space-y-1 font-mono">
                                <template x-for="(warn, wIdx) in importWarnings" :key="wIdx">
                                    <li x-text="warn"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <!-- Preview Content -->
                    <template x-if="importPreviewResult">
                        <div class="space-y-4">
                            <!-- Stats & Target Status Radio -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="p-3.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center gap-3">
                                    <div class="p-2 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-lg">
                                        <i data-lucide="package" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider font-mono">Total Box Terdeteksi</div>
                                        <div class="text-base font-black text-slate-900 dark:text-white" x-text="importPreviewResult.total_boxes + ' Box'"></div>
                                    </div>
                                </div>
                                <div class="p-3.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center gap-3">
                                    <div class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg">
                                        <i data-lucide="file-text" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider font-mono">Total Butir Dokumen</div>
                                        <div class="text-base font-black text-slate-900 dark:text-white" x-text="importPreviewResult.total_items + ' Butir'"></div>
                                    </div>
                                </div>
                                <div class="p-3.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5">
                                    <div class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider font-mono">
                                        Status Pasca-Impor:
                                    </div>
                                    <div class="space-y-1">
                                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                                            <input type="radio" value="draft" x-model="importTargetStatus" class="text-blue-600">
                                            <span>Draft Sementara</span>
                                        </label>
                                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                                            <input type="radio" value="pending_verification" x-model="importTargetStatus" class="text-blue-600">
                                            <span class="font-bold text-amber-600 dark:text-amber-400">Verifikasi Gudang</span>
                                        </label>
                                        @if(Auth::check() && !Auth::user()->isPicDept())
                                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                                            <input type="radio" value="in_warehouse" x-model="importTargetStatus" class="text-blue-600">
                                            <span class="font-bold text-emerald-600 dark:text-emerald-400">Tersimpan di Gudang (Data Existing)</span>
                                        </label>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Box Cards Accordion / List -->
                            <div class="space-y-3">
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider font-mono flex items-center justify-between">
                                    <span>Pratinjau Struktur Box & Dokumen:</span>
                                    <span class="text-[11px] text-slate-400 font-normal">Semua periode & retensi dihitung otomatis</span>
                                </div>

                                <div class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
                                    <template x-for="(box, bIdx) in importPreviewResult.boxes" :key="bIdx">
                                        <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2.5">
                                            <!-- Box Header -->
                                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800/80 pb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 rounded font-mono font-bold text-xs" x-text="box.temp_box_id"></span>
                                                    <h4 class="font-bold text-xs text-slate-900 dark:text-white" x-text="box.title || box.name"></h4>
                                                </div>
                                                <div class="flex items-center gap-1.5 text-[11px] font-mono">
                                                    <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 rounded text-slate-700 dark:text-slate-300" x-text="'Dept: ' + box.department_code + (box.sub_department_code ? ' / ' + box.sub_department_code : '')"></span>
                                                    <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 rounded font-bold" x-text="box.items.length + ' Berkas'"></span>
                                                </div>
                                            </div>

                                            <!-- Box Calculated Metrics -->
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] font-mono bg-white dark:bg-slate-950/50 p-2 rounded-lg border border-slate-200 dark:border-slate-800">
                                                <div>
                                                    <span class="text-slate-400 block text-[10px]">Periode Arsip:</span>
                                                    <span class="font-bold text-slate-700 dark:text-slate-300" x-text="box.periode_doc || '-'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-400 block text-[10px]">Rentang Tanggal:</span>
                                                    <span class="font-bold text-slate-700 dark:text-slate-300" x-text="(box.period_start_date || '?') + ' s/d ' + (box.period_end_date || '?')"></span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-400 block text-[10px]">Retensi (Kedaluwarsa):</span>
                                                    <span class="font-bold text-amber-600 dark:text-amber-400" x-text="(box.retention_years || 5) + ' Thn (' + (box.retention_expiry_date || '-') + ')'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-400 block text-[10px]">Kondisi Fisik:</span>
                                                    <span class="font-bold text-slate-700 dark:text-slate-300" x-text="box.physical_condition || 'Baik'"></span>
                                                </div>
                                            </div>

                                            <!-- Box Item Table -->
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-[11px] font-mono">
                                                    <thead class="bg-slate-200/50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400">
                                                        <tr>
                                                            <th class="py-1 px-2 w-12">No</th>
                                                            <th class="py-1 px-2">Nama Dokumen / Berkas</th>
                                                            <th class="py-1 px-2 w-48">Periode Dokumen</th>
                                                            <th class="py-1 px-2">Keterangan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40">
                                                        <template x-for="(itm, iIdx) in box.items" :key="iIdx">
                                                             <tr class="hover:bg-white/50 dark:hover:bg-slate-800/30">
                                                                <td class="py-1 px-2 font-bold text-slate-500" x-text="itm.item_number || (iIdx + 1)"></td>
                                                                <td class="py-1 px-2 font-semibold text-slate-900 dark:text-slate-100" x-text="itm.document_name || itm.name || '-'"></td>
                                                                <td class="py-1 px-2 text-slate-600 dark:text-slate-400" x-text="itm.period_text || ((itm.period_start || '-') + (itm.period_end && itm.period_end !== itm.period_start ? ' s/d ' + itm.period_end : ''))"></td>
                                                                <td class="py-1 px-2 text-slate-500 italic" x-text="itm.notes || itm.description || '-'"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
                    <div class="text-[11px] text-slate-500 font-mono">
                        <template x-if="importPreviewResult">
                            <span>Siap diproses: <strong class="text-slate-900 dark:text-white" x-text="importPreviewResult.total_boxes"></strong> box (<strong x-text="importPreviewResult.total_items"></strong> berkas).</span>
                        </template>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 justify-end">
                        <button type="button" 
                                @click="openImportModal = false" 
                                :disabled="importSubmitting || importLoadingToForm" 
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition cursor-pointer">
                            Tutup
                        </button>

                        <!-- ALUR 1: Lanjutkan ke Formulir Pengajuan (Alur Biasa Pengajuan Arsip) -->
                        <button type="button" 
                                @click="continueToCreateForm()" 
                                :disabled="!importPreviewResult || importPreviewResult.boxes.length === 0 || importSubmitting || importLoadingToForm" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-500/20 transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                                title="Bawa data dari Excel ini ke Formulir Pengajuan Dokumen Arsip untuk alur pengajuan biasa">
                            <span x-show="importLoadingToForm" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <svg x-show="!importLoadingToForm" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="12" y1="18" x2="12" y2="12"/>
                                <line x1="9" y1="15" x2="15" y2="15"/>
                            </svg>
                            <span x-text="importLoadingToForm ? 'Memuat Form...' : 'Lanjutkan ke Form Pengajuan'"></span>
                        </button>

                        <!-- ALUR 2: Simpan Langsung ke Gudang (Khusus Data yang Sudah Ada di Gudang Sebelumnya) -->
                        @if(Auth::check() && !Auth::user()->isPicDept())
                        <button type="button" 
                                @click="submitImportCommitDirect()" 
                                :disabled="!importPreviewResult || importPreviewResult.boxes.length === 0 || importSubmitting || importLoadingToForm" 
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                                title="Khusus data arsip fisik yang sudah ada di rak gudang sebelumnya (tanpa alur pengajuan baru)">
                            <span x-show="importSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <svg x-show="!importSubmitting" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <ellipse cx="12" cy="5" rx="9" ry="3"/>
                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                            </svg>
                            <span x-text="importSubmitting ? 'Mengimpor...' : 'Simpan Langsung ke Gudang'"></span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>