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
                                Impor Multi-Box & Berkas Dokumen (Excel / CSV)
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
                                    Identitas box (<span class="font-mono font-bold text-blue-600 dark:text-blue-400">JUDUL_BOX</span>, Dept) cukup diisi <strong>1 kali</strong> pada baris pertama box. Baris dokumen berikutnya cukup isi Nama Dokumen, Periode, dan Keterangan. Pengisian <strong>JUDUL_BOX</strong> baru di baris bawahnya otomatis menjadi pemisah box baru. Nomor urut berkas otomatis dari atas ke bawah. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">MASA_SIMPAN_BULAN</span> diisi angka bulan murni (contoh: <code>60</code> untuk 60 bulan/5 tahun, <code>12</code> untuk 12 bulan). Tab <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">REFERENSI_KODE</span> tersedia sebagai panduan kode departemen.
                                @else
                                    Identitas box (<span class="font-mono font-bold text-blue-600 dark:text-blue-400">JUDUL_BOX</span>, Dept, Rak) cukup diisi <strong>1 kali</strong> pada baris pertama box. Baris dokumen berikutnya dalam box yang sama cukup isi Nama Dokumen, Periode, dan Keterangan. Pengisian <strong>JUDUL_BOX</strong> baru otomatis memisahkan box baru. Nomor urut berkas otomatis dari atas ke bawah. Kolom <span class="font-mono font-bold text-slate-700 dark:text-slate-300">MASA_SIMPAN_BULAN</span> diisi angka bulan murni (contoh: <code>60</code>). Tab <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">REFERENSI_KODE</span> untuk panduan kode departemen &amp; rak gudang.
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('archives.import.template', ['format' => 'xlsx']) }}" 
                               onclick="downloadImportTemplate(event, '{{ route('archives.import.template', ['format' => 'xlsx']) }}', 'template_arsip_multi_box.xlsx')" 
                               download="template_arsip_multi_box.xlsx" 
                               class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs shadow-sm flex items-center gap-1.5 transition cursor-pointer">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span>Template Excel (.xlsx)</span>
                            </a>
                            <a href="{{ route('archives.import.template', ['format' => 'csv']) }}" 
                               onclick="downloadImportTemplate(event, '{{ route('archives.import.template', ['format' => 'csv']) }}', 'template_arsip_multi_box.csv')" 
                               download="template_arsip_multi_box.csv" 
                               class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg font-bold text-xs transition cursor-pointer">
                                <span>CSV</span>
                            </a>
                        </div>
                    </div>

                    <!-- Notifikasi Berhasil Download Template -->
                    <div id="templateDownloadSuccessBanner" style="display: none;" class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 rounded-xl text-emerald-900 dark:text-emerald-200 text-xs shadow-sm flex items-start gap-3 transition">
                        <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 rounded-lg shrink-0 mt-0.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        </div>
                        <div class="flex-1 space-y-1">
                            <div class="font-bold text-emerald-950 dark:text-emerald-100 flex items-center gap-1.5 text-xs">
                                <span>Berkas Template Berhasil Diunduh!</span>
                            </div>
                            <div class="text-[11px] text-emerald-800 dark:text-emerald-300">
                                Berkas: <span id="downloadedFilenameText" class="font-mono font-bold">template_arsip_multi_box.xlsx</span>
                            </div>
                            <div class="text-[11px] text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                                <i data-lucide="folder-check" class="w-3.5 h-3.5 shrink-0 text-emerald-600 dark:text-emerald-400"></i>
                                <span>Tersimpan di: <strong class="underline">Folder Downloads Komputer</strong> (<code>%USERPROFILE%\Downloads\</code>)</span>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('templateDownloadSuccessBanner').style.display='none'" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 p-1 rounded transition" title="Tutup Notifikasi">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
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
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider font-mono">Total Berkas Dokumen</div>
                                        <div class="text-base font-black text-slate-900 dark:text-white" x-text="importPreviewResult.total_items + ' Berkas'"></div>
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

    <script>
        function downloadImportTemplate(event, url, filename) {
            if (event) event.preventDefault();
            const btn = event ? event.currentTarget : null;
            let originalContent = '';
            if (btn) {
                originalContent = btn.innerHTML;
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none';
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) {
                if (!res.ok) throw new Error('Gagal mengunduh berkas template.');
                return res.blob();
            })
            .then(function(blob) {
                const blobUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(function() {
                    if (document.body.contains(a)) document.body.removeChild(a);
                    window.URL.revokeObjectURL(blobUrl);
                }, 2000);

                // 1. Tampilkan Banner Notifikasi di dalam Modal
                const banner = document.getElementById('templateDownloadSuccessBanner');
                const filenameText = document.getElementById('downloadedFilenameText');
                if (banner && filenameText) {
                    filenameText.textContent = filename;
                    banner.style.display = 'flex';
                }

                // 2. Tampilkan Floating Toast Notification di Pojok Layar
                showTemplateDownloadToast(filename);
            })
            .catch(function(err) {
                console.warn('Blob download error, trying direct navigation fallback:', err);
                window.location.href = url;
            })
            .finally(function() {
                if (btn) {
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
                    btn.innerHTML = originalContent;
                }
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        }

        function showTemplateDownloadToast(filename) {
            let container = document.getElementById('dms-download-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'dms-download-toast-container';
                container.className = 'fixed top-5 right-5 z-[99999] flex flex-col gap-2 max-w-sm w-full pointer-events-none';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto bg-white dark:bg-slate-900 border-2 border-emerald-500 rounded-xl p-3.5 shadow-2xl shadow-emerald-500/10 flex items-start gap-3 transform translate-y-2 opacity-0 transition-all duration-300';
            toast.innerHTML = `
                <div class="p-2 bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 rounded-lg shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs flex items-center justify-between">
                        <span>Unduhan Berhasil!</span>
                        <span class="text-[10px] text-emerald-600 font-mono font-bold bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded">Tersimpan</span>
                    </div>
                    <div class="text-[11px] font-mono font-semibold text-slate-700 dark:text-slate-300 truncate mt-0.5">
                        ` + filename + `
                    </div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <span>Folder <strong>Downloads</strong> Komputer</span>
                    </div>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1" onclick="this.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;

            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => {
                    if (toast.parentElement) toast.remove();
                }, 300);
            }, 6000);
        }
    </script>