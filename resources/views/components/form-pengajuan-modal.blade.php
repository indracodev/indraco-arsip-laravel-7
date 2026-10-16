@props([
    'openState' => 'formModalOpen',
    'stateName' => 'formPengajuanData',
    'rowsName' => 'formPengajuanRows',
    'syncMethod' => 'populateFormRows',
    'printMethod' => 'printFormPengajuan',
    'dateFormatter' => 'formatDateDisplay',
    'deptFormatter' => 'getDeptSubDeptDisplay',
    'showSyncButton' => true,
    'title' => 'Form Pengajuan Dokumen (Standar PT Indraco)',
    'subtitle' => 'Pratinjau formulir resmi A4. Kolom tabel terisi otomatis dari data box dan dokumen.'
])

<!-- ======================================================= -->
<!-- PREVIEW & CETAK FORM PENGAJUAN DOKUMEN MODAL (PDF/PRINT) -->
<!-- ======================================================= -->
<div 
    x-show="{{ $openState }}" 
    x-cloak 
    class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
    @keydown.escape.window="{{ $openState }} = false"
>
    <div 
        class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-lg shadow-2xl max-w-6xl w-full max-h-[95vh] flex flex-col overflow-hidden text-slate-800 dark:text-slate-100"
        @click.outside="{{ $openState }} = false"
    >
        <!-- Modal Header Toolbar -->
        <div class="px-4 py-2.5 bg-slate-100 dark:bg-slate-950 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between shrink-0 font-mono">
            <div class="flex items-center gap-2">
                <span class="p-1.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                </span>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                        {{ $title }}
                    </h3>
                    <p class="text-[10px] text-slate-500 font-sans">
                        {{ $subtitle }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="{{ $printMethod }}()" 
                    class="px-3.5 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold text-xs rounded border border-amber-600 shadow-sm flex items-center gap-1.5 transition cursor-pointer"
                    title="Cetak langsung ke printer atau simpan sebagai PDF"
                >
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Cetak Dokumen (Print / PDF)</span>
                </button>

                <button 
                    type="button" 
                    @click="{{ $openState }} = false" 
                    class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-800 rounded transition cursor-pointer"
                    title="Tutup (Esc)"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body: Left Inputs Panel + Right Paper Preview -->
        <div class="flex-1 overflow-y-auto flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-slate-200 dark:divide-slate-800 bg-slate-100 dark:bg-slate-950">
            
            <!-- Left Column: Input Parameter & Signatures (Scrollable) -->
            <div class="w-full lg:w-80 xl:w-96 p-4 space-y-3 font-mono text-xs overflow-y-auto shrink-0 bg-white dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-1.5">
                    <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                        <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                        <span>Parameter & Approval</span>
                    </span>
                    @if($showSyncButton)
                    <button 
                        type="button" 
                        @click="{{ $syncMethod }}()" 
                        class="text-[10px] text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 cursor-pointer"
                        title="Sinkronkan ulang tabel dengan data box saat ini"
                    >
                        <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                        <span>Sinkron Data</span>
                    </button>
                    @endif
                </div>

                <div class="space-y-2">
                    <!-- No. Form -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                            NO. FORM
                        </label>
                        <input 
                            type="text" 
                            x-model="{{ $stateName }}.no_form" 
                            placeholder="Contoh: FPD/2026/10/001 (opsional)" 
                            style="font-size: 11px;"
                            class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                        >
                    </div>

                    <!-- Tanggal Pengajuan -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                            TANGGAL PENGAJUAN
                        </label>
                        <input 
                            type="date" 
                            x-model="{{ $stateName }}.tanggal_pengajuan" 
                            style="font-size: 11px;"
                            class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                        >
                    </div>

                    <!-- Departemen / Sub Dept -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                            DEPARTEMEN / SUB DEPT.
                        </label>
                        <input 
                            type="text" 
                            x-model="{{ $stateName }}.dept_display" 
                            style="font-size: 11px;"
                            class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                        >
                    </div>

                    <!-- Nama PIC / Pemohon -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                            NAMA PIC / PEMOHON
                        </label>
                        <input 
                            type="text" 
                            x-model="{{ $stateName }}.pic_pemohon" 
                            @input="{{ $stateName }}.approval_pemohon = {{ $stateName }}.pic_pemohon"
                            placeholder="Ketik nama lengkap pemohon..."
                            style="font-size: 11px;"
                            class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                        >
                    </div>

                    <!-- Tipe Pengajuan -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                            TIPE PENGAJUAN
                        </label>
                        <div class="grid grid-cols-2 gap-1.5 font-sans" style="font-size: 11px;">
                            <label class="flex items-center gap-1.5 p-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                <input type="radio" value="Simpan" x-model="{{ $stateName }}.tipe" class="text-amber-500 focus:ring-0">
                                <span class="font-semibold text-slate-800 dark:text-slate-200">Simpan</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                <input type="radio" value="Penarikan" x-model="{{ $stateName }}.tipe" class="text-amber-500 focus:ring-0">
                                <span class="text-slate-600 dark:text-slate-400">Penarikan</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                <input type="radio" value="Pemusnahan" x-model="{{ $stateName }}.tipe" class="text-amber-500 focus:ring-0">
                                <span class="text-slate-600 dark:text-slate-400">Pemusnahan</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                <input type="radio" value="Perpanjangan" x-model="{{ $stateName }}.tipe" class="text-amber-500 focus:ring-0">
                                <span class="text-slate-600 dark:text-slate-400">Perpanjangan</span>
                            </label>
                        </div>
                    </div>

                    <!-- Catatan Fisik / Opsional -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">
                            CATATAN FORMULIR
                        </label>
                        <textarea 
                            x-model="{{ $stateName }}.catatan" 
                            rows="2" 
                            placeholder="Kondisi fisik Box dan dokumen..." 
                            style="font-size: 11px;"
                            class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                        ></textarea>
                    </div>

                    <!-- Signature / Approval Section -->
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1.5">
                        <span class="block text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">
                            Penandatangan (Approval):
                        </span>

                        <!-- Diajukan oleh (Pemohon) -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5 flex items-center justify-between">
                                <span>Diajukan oleh (Pemohon):</span>
                                <span class="text-[9px] text-amber-600 dark:text-amber-400 font-normal italic">(Otomatis = PIC Pemohon)</span>
                            </label>
                            <input 
                                type="text" 
                                x-model="{{ $stateName }}.pic_pemohon" 
                                @input="{{ $stateName }}.approval_pemohon = {{ $stateName }}.pic_pemohon"
                                placeholder="Ketik nama pemohon..."
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                        </div>

                        <!-- Disetujui oleh (Atasan Langsung) -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                Disetujui oleh (Atasan Langsung):
                            </label>
                            <input 
                                type="text" 
                                x-model="{{ $stateName }}.approval_atasan" 
                                placeholder="Nama Atasan Langsung" 
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                        </div>

                        <!-- Diketahui oleh (Pimpinan Departemen) -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                Diketahui oleh (Pimpinan Departemen):
                            </label>
                            <input 
                                type="text" 
                                x-model="{{ $stateName }}.approval_pimpinan" 
                                placeholder="Nama Pimpinan Departemen" 
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                        </div>

                        <!-- Diterima oleh (Admin Gudang) -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                Diterima oleh (Admin Gudang):
                            </label>
                            <input 
                                type="text" 
                                x-model="{{ $stateName }}.approval_admin_gudang" 
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                        </div>

                        <!-- Tanggal Approval -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                Tanggal Tanda Tangan:
                            </label>
                            <input 
                                type="date" 
                                x-model="{{ $stateName }}.approval_tgl" 
                                style="font-size: 11px;"
                                class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Printable Paper Preview -->
            <div class="flex-1 p-3 sm:p-6 overflow-y-auto bg-slate-200/80 dark:bg-slate-950 flex justify-center items-start">
                
                <!-- Paper Sheet Container (Exact A4 Ratio) -->
                <div 
                    id="form-pengajuan-print-document" 
                    class="indraco-form-preview bg-white text-black shadow-2xl rounded-none border border-slate-300 w-full max-w-[210mm] min-h-[297mm] box-border"
                    style="padding: 10mm 12mm; background-color: #ffffff; color: #000000; display: block;"
                >
                    <div>
                        <!-- Header with Logo & Document Title (User Styling) -->
                        <div style="position: relative; margin-bottom: 8px; min-height: 28px;">
                            <div style="position: absolute; left: 0; top: 0;">
                                <img src="{{ asset('images/logo_indraco.png') }}" alt="INDRACO" style="height: 28px; object-fit: contain;">
                            </div>
                            <div style="text-align: center; padding-top: 35px; padding-bottom: 8px;">
                                <h1 style="font-size: 14pt; font-weight: bold; text-align: center; margin: 0; padding: 0; font-family: Arial, sans-serif; letter-spacing: 0.3px;">
                                    Form Pengajuan Dokumen
                                </h1>
                            </div>
                        </div>

                        <!-- Metadata Table Box (2 rows x 4 columns) -->
                        <table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 14px;">
                            <tbody>
                                <tr style="height: 28px;">
                                    <td style="width: 28%; border: 1px solid #000; padding: 4px 6px; font-size: 9.5pt; font-weight: normal; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">No. Form</td>
                                    <td style="width: 26%; border: 1px solid #000; padding: 4px 6px; font-size: 9.5pt; font-family: monospace; vertical-align: middle; overflow: hidden; word-break: break-word;" x-text="{{ $stateName }}.no_form || ''"></td>
                                    <td style="width: 24%; border: 1px solid #000; padding: 4px 6px; font-size: 9.5pt; font-weight: normal; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Tanggal Pengajuan</td>
                                    <td style="width: 22%; border: 1px solid #000; padding: 4px 6px; font-size: 9.5pt; vertical-align: middle; white-space: nowrap; overflow: hidden;" x-text="{{ $dateFormatter }}({{ $stateName }}.tanggal_pengajuan)"></td>
                                </tr>
                                <tr style="height: 28px;">
                                    <td style="width: 28%; border: 1px solid #000; padding: 4px 6px; font-size: 9.5pt; font-weight: normal; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Departemen/ Sub Dept.">Departemen/ Sub Dept.</td>
                                    <td style="width: 26%; border: 1px solid #000; padding: 4px 6px; font-size: 9.5pt; vertical-align: middle; overflow: hidden; word-break: break-word;" x-text="{{ $stateName }}.dept_display || (typeof {{ $deptFormatter }} === 'function' ? {{ $deptFormatter }}() : '')"></td>
                                    <td style="width: 24%; border: 1px solid #000; padding: 4px 6px; font-size: 9.5pt; font-weight: normal; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Nama PIC/ Pemohon</td>
                                    <td style="width: 22%; border: 1px solid #000; padding: 4px 6px; font-size: 9.5pt; vertical-align: middle; overflow: hidden; word-break: break-word;" x-text="{{ $stateName }}.pic_pemohon || {{ $stateName }}.approval_pemohon || ''"></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Tipe Pengajuan Checkbox Row -->
                        <div style="font-size: 9.5pt; margin-bottom: 14px; display: flex; align-items: center; gap: 20px;">
                            <span style="font-weight: normal;">Tipe Pengajuan :</span>
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <span style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border: 1px solid #000; font-size: 9.5pt; line-height: 12px; text-align: center; font-weight: bold;" x-text="{{ $stateName }}.tipe === 'Simpan' ? '✓' : ''"></span>
                                    <span>Simpan</span>
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border: 1px solid #000; font-size: 9.5pt; line-height: 12px; text-align: center; font-weight: bold;" x-text="{{ $stateName }}.tipe === 'Penarikan' ? '✓' : ''"></span>
                                    <span>Penarikan</span>
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border: 1px solid #000; font-size: 9.5pt; line-height: 12px; text-align: center; font-weight: bold;" x-text="{{ $stateName }}.tipe === 'Pemusnahan' ? '✓' : ''"></span>
                                    <span>Pemusnahan</span>
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border: 1px solid #000; font-size: 9.5pt; line-height: 12px; text-align: center; font-weight: bold;" x-text="{{ $stateName }}.tipe === 'Perpanjangan' ? '✓' : ''"></span>
                                    <span>Perpanjangan</span>
                                </span>
                            </div>
                        </div>

                        <!-- Main Table: Daftar Box & Dokumen (12+ Rows) -->
                        <table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 14px;">
                            <thead>
                                <tr style="text-align: center; font-weight: bold;">
                                    <th style="border: 1px solid #000; width: 36px; padding: 4px 2px; font-size: 9.5pt; text-align: center; line-height: 1.2; overflow: hidden;">No.</th>
                                    <th style="border: 1px solid #000; padding: 4px 6px; font-size: 9.5pt; text-align: center; line-height: 1.2; overflow: hidden; word-break: break-word;">
                                        Nama / Judul Box
                                    </th>
                                    <th style="border: 1px solid #000; width: 85px; padding: 4px 4px; font-size: 9.5pt; text-align: center; line-height: 1.2; overflow: hidden; word-break: break-word;">
                                        Sub-Dept
                                    </th>
                                    <th style="border: 1px solid #000; width: 95px; padding: 4px 4px; font-size: 9.5pt; text-align: center; line-height: 1.2; overflow: hidden;">
                                        Masa Simpan<br>(Bulan)
                                    </th>
                                    <th style="border: 1px solid #000; width: 110px; padding: 4px 4px; font-size: 9.5pt; text-align: center; line-height: 1.2; overflow: hidden;">
                                        Periode Akhir<br>(MM-YYYY)
                                    </th>
                                    <th style="border: 1px solid #000; width: 105px; padding: 4px 4px; font-size: 9.5pt; text-align: center; line-height: 1.2; overflow: hidden; word-break: break-word;">
                                        Label Box
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in {{ $rowsName }}" :key="row.no">
                                    <tr style="height: 24px;">
                                        <td style="border: 1px solid #000; padding: 2px 2px; text-align: center; font-size: 9.5pt; overflow: hidden;" x-text="row.no"></td>
                                        <td style="border: 1px solid #000; padding: 2px 6px; text-align: left; font-size: 9.5pt; outline: none; overflow: hidden; word-break: break-word;" contenteditable="true" @blur="row.title = $el.innerText" x-text="row.title"></td>
                                        <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-size: 9.5pt; outline: none; overflow: hidden; word-break: break-word;" contenteditable="true" @blur="row.sub_dept = $el.innerText" x-text="row.title ? (row.sub_dept || '-') : (row.sub_dept || '')"></td>
                                        <td style="border: 1px solid #000; padding: 2px 2px; text-align: center; font-size: 9.5pt; outline: none; overflow: hidden; white-space: nowrap;" contenteditable="true" @blur="row.masa_simpan = $el.innerText" x-text="row.masa_simpan"></td>
                                        <td style="border: 1px solid #000; padding: 2px 2px; text-align: center; font-size: 9.5pt; font-family: monospace; outline: none; overflow: hidden; white-space: nowrap;" contenteditable="true" @blur="row.period_end = $el.innerText" x-text="row.period_end"></td>
                                        <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-size: 9.5pt; outline: none; overflow: hidden; word-break: break-word;" contenteditable="true" @blur="row.label_box = $el.innerText; row.label_kardus = $el.innerText" x-text="row.label_box || row.label_kardus"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- BOTTOM FOOTER SECTION (Anchored to Bottom of A4 Page) -->
                    <div style="margin-bottom: 14px">
                        <!-- Catatan Section -->
                        <div style="font-size: 9.5pt; margin-bottom: 14px; line-height: 1.35;">
                            <div>
                                <div>Catatan (misal kondisi fisik Box dan dokumen/ *optional) :</div>
                                <div 
                                    style="border-bottom: 1px dotted #000; text-decoration: underline dotted #000; text-underline-offset: 3px; min-height: 18px; font-size: 9.5pt; line-height: 18px; padding-top: 2px; word-break: break-word; outline: none;" 
                                    contenteditable="true" 
                                    @blur="{{ $stateName }}.catatan = $el.innerText" 
                                    x-text="{{ $stateName }}.catatan"
                                ></div>
                                <div 
                                    x-show="!{{ $stateName }}.catatan || !{{ $stateName }}.catatan.trim()" 
                                    style="border-bottom: 1px dotted #000; min-height: 18px;"
                                ></div>
                            </div>
                        </div>

                        <!-- Approval Signatures Table (4 Columns) -->
                        <table style="width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 14px;">
                            <tbody>
                                <tr>
                                    <!-- Diajukan oleh (Pemohon) -->
                                    <td style="width: 25%; border: 1px solid #000; vertical-align: top; height: 85px; padding: 4px 6px;">
                                        <div style="font-size: 9.5pt; text-align: left;">Diajukan oleh,</div>
                                        <div style="height: 48px;"></div>
                                        <div style="font-size: 9.5pt; text-align: center; line-height: 1.2;">
                                            (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="{{ $stateName }}.approval_pemohon = $el.innerText; {{ $stateName }}.pic_pemohon = $el.innerText" x-text="{{ $stateName }}.approval_pemohon || {{ $stateName }}.pic_pemohon || ''"></span>&nbsp;)
                                        </div>
                                        <div style="font-size: 9.5pt; text-align: left; margin-top: 3px;">
                                            Tgl. ....................
                                        </div>
                                    </td>
                                    <!-- Disetujui oleh (Atasan Langsung) -->
                                    <td style="width: 25%; border: 1px solid #000; vertical-align: top; height: 85px; padding: 4px 6px;">
                                        <div style="font-size: 9.5pt; text-align: left;">Disetujui oleh,</div>
                                        <div style="height: 48px;"></div>
                                        <div style="font-size: 9.5pt; text-align: center; line-height: 1.2;">
                                            (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="{{ $stateName }}.approval_atasan = $el.innerText" x-text="{{ $stateName }}.approval_atasan || ''"></span>&nbsp;)
                                        </div>
                                        <div style="font-size: 9.5pt; text-align: left; margin-top: 3px;">
                                            Tgl. ....................
                                        </div>
                                    </td>
                                    <!-- Diketahui oleh (Pimpinan Departemen) -->
                                    <td style="width: 25%; border: 1px solid #000; vertical-align: top; height: 85px; padding: 4px 6px;">
                                        <div style="font-size: 9.5pt; text-align: left;">Diketahui oleh,</div>
                                        <div style="height: 48px;"></div>
                                        <div style="font-size: 9.5pt; text-align: center; line-height: 1.2;">
                                            (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="{{ $stateName }}.approval_pimpinan = $el.innerText" x-text="{{ $stateName }}.approval_pimpinan || ''"></span>&nbsp;)
                                        </div>
                                        <div style="font-size: 9.5pt; text-align: left; margin-top: 3px;">
                                            Tgl. ....................
                                        </div>
                                    </td>
                                    <!-- Diterima oleh (Admin Gudang) -->
                                    <td style="width: 25%; border: 1px solid #000; vertical-align: top; height: 85px; padding: 4px 6px;">
                                        <div style="font-size: 9.5pt; text-align: left;">Diterima oleh,</div>
                                        <div style="height: 48px;"></div>
                                        <div style="font-size: 9.5pt; text-align: center; line-height: 1.2;">
                                            (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="{{ $stateName }}.approval_admin_gudang = $el.innerText" x-text="{{ $stateName }}.approval_admin_gudang || ''"></span>&nbsp;)
                                        </div>
                                        <div style="font-size: 9.5pt; text-align: left; margin-top: 3px;">
                                            Tgl. ....................
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Petunjuk Pengisian (Verbatim from Official Form) -->
                        <div style="font-size: 9.5pt; line-height: 1.35; color: #000; margin-top: 6px;">
                            <div style="font-weight: normal; margin-bottom: 2px;">Petunjuk Pengisian :</div>
                            <ol style="margin: 0; padding-left: 18px; list-style-type: decimal;">
                                <li>Centang pada tipe pengajuan yang tersedia (Simpan/ Penarikan/ Pemusnahan/ Perpanjangan).</li>
                                <li>Isi <strong>Nama/ Judul Box</strong> dan <strong>Masa Simpan Box</strong> untuk pengajuan simpan.</li>
                                <li>Isi <strong>Label Box</strong> saat pengajuan Penarikan/ Pemusnahan/ Perpanjangan.</li>
                                <li><strong>Masa Simpan Box</strong> di hitung bulanan (maksimal 60 bulan untuk umum dan 120 bulan khusus dept. FAT).</li>
                                <li>Dalam satu pengajuan dibatasi maksimal 10 box.</li>
                                <li>Approval =
                                    <ul style="margin: 0; padding-left: 18px; list-style-type: none;">
                                        <li>✸ Diajukan oleh <strong>Pemohon</strong></li>
                                        <li>✸ Disetujui oleh <strong>Atasan Langsung</strong></li>
                                        <li>✸ Diketahui oleh <strong>Pimpinan Departemen</strong></li>
                                        <li>✸ Diterima oleh <strong>Admin Gudang</strong></li>
                                    </ul>
                                </li>
                            </ol>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Print Styles & Fixed Scoped Preview CSS -->
<style>
/* Explicitly allow mouse text blocking / highlighting & copy-paste */
html,
body,
.select-text,
.select-text *,
input,
textarea,
select,
[contenteditable="true"],
.indraco-form-preview,
.indraco-form-preview * {
    -webkit-user-select: text !important;
    -moz-user-select: text !important;
    -ms-user-select: text !important;
    user-select: text !important;
}

.indraco-form-preview,
.indraco-form-preview * {
    cursor: text;
}

::selection {
    background-color: #2563eb !important;
    color: #ffffff !important;
}

::-moz-selection {
    background-color: #2563eb !important;
    color: #ffffff !important;
}

.indraco-form-preview {
    font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
    font-size: 9.5pt !important;
    line-height: 1.3 !important;
    color: #000000 !important;
    background-color: #ffffff !important;
}
.indraco-form-preview * {
    box-sizing: border-box !important;
    font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
}
@media print {
    body * {
        visibility: hidden !important;
    }
    #form-pengajuan-print-document, 
    #form-pengajuan-print-document * {
        visibility: visible !important;
    }
    #form-pengajuan-print-document {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: 0 !important;
        height: auto !important;
        margin: 0 !important;
        padding: 8mm 12mm !important;
        box-shadow: none !important;
        border: none !important;
        background: #ffffff !important;
        color: #000000 !important;
        z-index: 9999999 !important;
        font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
        font-size: 9.5pt !important;
        display: block !important;
    }
    @page {
        size: A4 portrait;
        margin: 0;
    }
}
</style>
