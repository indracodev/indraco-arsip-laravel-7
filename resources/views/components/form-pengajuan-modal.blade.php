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
    @click.self="{{ $openState }} = false"
>
    <div 
        class="bg-white dark:bg-slate-900 border-2 border-amber-500/60 dark:border-amber-500/40 rounded-lg shadow-2xl max-w-6xl w-full max-h-[96vh] flex flex-col overflow-hidden text-slate-800 dark:text-slate-100"
    >
        <!-- Modal Header Toolbar -->
        <div class="px-4 py-2 bg-gradient-to-r from-slate-900 via-slate-850 to-indigo-950 text-white border-b border-amber-500/40 flex items-center justify-between shrink-0 font-mono">
            <div class="flex items-center gap-2.5">
                <span class="p-1.5 bg-amber-500/20 text-amber-400 border border-amber-500/40 rounded">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                </span>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white flex items-center gap-2">
                        <span>{{ $title }}</span>
                        <span class="px-1.5 py-0.2 bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[9px] font-normal rounded">Modal Pratinjau</span>
                    </h3>
                    <p class="text-[10px] text-slate-300 font-sans">
                        {{ $subtitle }}
                    </p>
                </div>
            </div>

            <!-- Header Action Buttons (Jelas & Kontras untuk Menghindari Salah Klik Close Window) -->
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="{{ $printMethod }}()" 
                    class="px-3.5 py-1.5 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded border border-amber-600 shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
                    title="Cetak langsung ke printer atau simpan sebagai PDF"
                >
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-950"></i>
                    <span>Cetak Dokumen (Print / PDF)</span>
                </button>

                <!-- Tombol Tutup Modal Merah Jelas & Mencolok -->
                <button 
                    type="button" 
                    @click="{{ $openState }} = false" 
                    class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-black text-xs rounded border border-rose-700 shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
                    title="Tutup Pratinjau (Kembali ke Form)"
                >
                    <i data-lucide="x" class="w-4 h-4 text-white"></i>
                    <span>Tutup Pratinjau (ESC)</span>
                </button>
            </div>
        </div>

        <!-- Banner Panduan Pengguna (Mencegah Salah Tutup Halaman Utama) -->
        <div class="px-4 py-2 bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200 dark:border-amber-900/40 text-amber-900 dark:text-amber-200 text-[11px] font-sans flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0"></i>
                <span><strong>Mode Pratinjau Form Cetak:</strong> Semua input data form Anda tetap aman. Klik tombol merah <strong>"Tutup Pratinjau (ESC)"</strong> atau tekan tombol keyboard <strong>[ESC]</strong> untuk kembali ke form pengisian (jangan klik tombol [X] di pojok kanan atas monitor).</span>
            </div>
            <button 
                type="button" 
                @click="{{ $openState }} = false" 
                class="px-2.5 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold text-[11px] rounded transition cursor-pointer flex items-center gap-1 shrink-0 shadow-xs"
            >
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                <span>Tutup Pratinjau (ESC)</span>
            </button>
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

                        <!-- Tombol Aksi Bawah Panel (Akses Cepat Menutup/Mencetak) -->
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
                            <button 
                                type="button" 
                                @click="{{ $printMethod }}()" 
                                class="w-full py-2 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded border border-amber-600 shadow-md flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer"
                            >
                                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                <span>Cetak Dokumen Sekarang</span>
                            </button>
                            <button 
                                type="button" 
                                @click="{{ $openState }} = false" 
                                class="w-full py-2 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-bold text-xs rounded border border-rose-700 flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer shadow-sm"
                                title="Kembali ke layar form pengisian"
                            >
                                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-white"></i>
                                <span>Kembali ke Form Pengisian (ESC)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Printable Paper Preview -->
            <div class="flex-1 p-3 sm:p-6 overflow-y-auto bg-slate-200/80 dark:bg-slate-950 flex justify-center items-start">
                
                <!-- Paper Sheet Container (Exact A4 Ratio) -->
                <div 
                    id="form-pengajuan-print-document" 
                    class="indraco-form-preview bg-white text-black shadow-2xl rounded-none border border-slate-300 w-full max-w-[210mm] min-h-0 box-border"
                    style="padding: 6mm 10mm; background-color: #ffffff; color: #000000; display: block;"
                >
                    <div>
                        <!-- Header with Logo & Document Title (User Styling) -->
                        <div style="position: relative; margin-bottom: 10px; min-height: 28px;">
                            <div style="position: absolute; left: 0; top: 0;">
                                <img src="{{ asset('images/logo_indraco.png') }}" alt="INDRACO" style="height: 26px; object-fit: contain;">
                            </div>
                            <div style="text-align: center; padding-top: 30px; padding-bottom: 4px;">
                                <h1 style="font-size: 13pt; font-weight: bold; text-align: center; margin: 0; padding: 0; font-family: Arial, sans-serif; letter-spacing: 0.3px;">
                                    Form Pengajuan
                                </h1>
                                DMS (Document Management System)
                            </div>
                        </div>

                        <!-- Metadata Table Box (2 rows x 4 columns) -->
                        <table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 15px;">
                            <tbody>
                                <tr style="height: 24px;">
                                    <td style="width: 28%; border: 1px solid #000; padding: 3px 6px; font-size: 9pt; font-weight: normal; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">No. Form</td>
                                    <td style="width: 26%; border: 1px solid #000; padding: 3px 6px; font-size: 9pt; font-family: monospace; vertical-align: middle; overflow: hidden; word-break: break-word;" x-text="{{ $stateName }}.no_form || ''"></td>
                                    <td style="width: 24%; border: 1px solid #000; padding: 3px 6px; font-size: 9pt; font-weight: normal; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Tanggal Pengajuan</td>
                                    <td style="width: 22%; border: 1px solid #000; padding: 3px 6px; font-size: 9pt; vertical-align: middle; white-space: nowrap; overflow: hidden;" x-text="{{ $dateFormatter }}({{ $stateName }}.tanggal_pengajuan)"></td>
                                </tr>
                                <tr style="height: 24px;">
                                    <td style="width: 28%; border: 1px solid #000; padding: 3px 6px; font-size: 9pt; font-weight: normal; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Departemen/ Sub Dept.">Departemen/ Sub Dept.</td>
                                    <td style="width: 26%; border: 1px solid #000; padding: 3px 6px; font-size: 9pt; vertical-align: middle; overflow: hidden; word-break: break-word;" x-text="{{ $stateName }}.dept_display || (typeof {{ $deptFormatter }} === 'function' ? {{ $deptFormatter }}() : '')"></td>
                                    <td style="width: 24%; border: 1px solid #000; padding: 3px 6px; font-size: 9pt; font-weight: normal; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Nama PIC/ Pemohon</td>
                                    <td style="width: 22%; border: 1px solid #000; padding: 3px 6px; font-size: 9pt; vertical-align: middle; overflow: hidden; word-break: break-word;" x-text="{{ $stateName }}.pic_pemohon || {{ $stateName }}.approval_pemohon || ''"></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Tipe Pengajuan Checkbox Row -->
                        <div style="font-size: 9pt; margin-bottom: 15px; display: flex; align-items: center; gap: 20px;">
                            <span style="font-weight: normal;">Tipe Pengajuan :</span>
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <span style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border: 1px solid #000; font-size: 9pt; line-height: 12px; text-align: center; font-weight: bold;" x-text="{{ $stateName }}.tipe === 'Simpan' ? '✓' : ''"></span>
                                    <span>Simpan</span>
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border: 1px solid #000; font-size: 9pt; line-height: 12px; text-align: center; font-weight: bold;" x-text="{{ $stateName }}.tipe === 'Penarikan' ? '✓' : ''"></span>
                                    <span>Penarikan</span>
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border: 1px solid #000; font-size: 9pt; line-height: 12px; text-align: center; font-weight: bold;" x-text="{{ $stateName }}.tipe === 'Pemusnahan' ? '✓' : ''"></span>
                                    <span>Pemusnahan</span>
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border: 1px solid #000; font-size: 9pt; line-height: 12px; text-align: center; font-weight: bold;" x-text="{{ $stateName }}.tipe === 'Perpanjangan' ? '✓' : ''"></span>
                                    <span>Perpanjangan</span>
                                </span>
                            </div>
                        </div>

                        <!-- Main Table: Daftar Box & Dokumen (Maksimal 10 Baris Sesuai Ketentuan) -->
                        <table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 15px;">
                            <thead>
                                <tr style="text-align: center; font-weight: bold; background-color: #fafafa;">
                                    <th style="border: 1px solid #000; width: 34px; padding: 3px 2px; font-size: 9pt; text-align: center; line-height: 1.2; overflow: hidden;">No.</th>
                                    <th style="border: 1px solid #000; padding: 3px 6px; font-size: 9pt; text-align: center; line-height: 1.2; overflow: hidden; word-break: break-word;">
                                        Nama / Judul Box
                                    </th>
                                    <th style="border: 1px solid #000; width: 85px; padding: 3px 4px; font-size: 9pt; text-align: center; line-height: 1.2; overflow: hidden; word-break: break-word;">
                                        Sub-Dept
                                    </th>
                                    <th style="border: 1px solid #000; width: 95px; padding: 3px 4px; font-size: 9pt; text-align: center; line-height: 1.2; overflow: hidden;">
                                        Masa Simpan<br>(Bulan)
                                    </th>
                                    <th style="border: 1px solid #000; width: 105px; padding: 3px 4px; font-size: 9pt; text-align: center; line-height: 1.2; overflow: hidden;">
                                        Periode Akhir<br>(MM-YYYY)
                                    </th>
                                    <th style="border: 1px solid #000; width: 105px; padding: 3px 4px; font-size: 9pt; text-align: center; line-height: 1.2; overflow: hidden; word-break: break-word;">
                                        Label Box
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in {{ $rowsName }}" :key="row.no">
                                    <tr style="height: 22px;">
                                        <td style="border: 1px solid #000; padding: 2px 2px; text-align: center; font-size: 9pt; overflow: hidden;" x-text="row.no"></td>
                                        <td style="border: 1px solid #000; padding: 2px 6px; text-align: left; font-size: 9pt; outline: none; overflow: hidden; word-break: break-word;" contenteditable="true" @blur="row.title = $el.innerText" x-text="row.title"></td>
                                        <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-size: 9pt; outline: none; overflow: hidden; word-break: break-word;" contenteditable="true" @blur="row.sub_dept = $el.innerText" x-text="row.title ? (row.sub_dept || '-') : (row.sub_dept || '')"></td>
                                        <td style="border: 1px solid #000; padding: 2px 2px; text-align: center; font-size: 9pt; outline: none; overflow: hidden; white-space: nowrap;" contenteditable="true" @blur="row.masa_simpan = $el.innerText" x-text="row.masa_simpan"></td>
                                        <td style="border: 1px solid #000; padding: 2px 2px; text-align: center; font-size: 9pt; font-family: monospace; outline: none; overflow: hidden; white-space: nowrap;" contenteditable="true" @blur="row.period_end = $el.innerText" x-text="row.period_end"></td>
                                        <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-size: 9pt; outline: none; overflow: hidden; word-break: break-word;" contenteditable="true" @blur="row.label_box = $el.innerText; row.label_kardus = $el.innerText" x-text="row.label_box || row.label_kardus"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- BOTTOM FOOTER SECTION (Anchored to Bottom of A4 Page) -->
                    <div style="margin-bottom: 10;">
                        <!-- Catatan Section -->
                        <div style="font-size: 9pt; margin-bottom: 8px; line-height: 1.3;">
                            <div>
                                <div>Catatan (misal kondisi fisik Box dan dokumen/ *optional) :</div>
                                <div 
                                    style="border-bottom: 1px dotted #000; text-decoration: underline dotted #000; text-underline-offset: 3px; min-height: 16px; font-size: 9pt; line-height: 16px; padding-top: 1px; word-break: break-word; outline: none;" 
                                    contenteditable="true" 
                                    @blur="{{ $stateName }}.catatan = $el.innerText" 
                                    x-text="{{ $stateName }}.catatan"
                                ></div>
                                <div 
                                    x-show="!{{ $stateName }}.catatan || !{{ $stateName }}.catatan.trim()" 
                                    style="border-bottom: 1px dotted #000; min-height: 16px;"
                                ></div>
                            </div>
                        </div>

                        <!-- Approval Signatures Table (3 Columns: Pemohon, Atasan, Pimpinan) -->
                        <table style="width: 100%; border-collapse: collapse; margin-top: 4px; margin-bottom: 15px;">
                            <tbody>
                                <tr>
                                    <!-- Diajukan oleh (Pemohon) -->
                                    <td style="width: 33.33%; border: 1px solid #000; vertical-align: top; height: 60px; padding: 2px 6px;">
                                        <div style="font-size: 8.5pt; text-align: left;">Diajukan oleh,</div>
                                        <div style="height: 28px;"></div>
                                        <div style="font-size: 8.5pt; text-align: center; line-height: 1.15;">
                                            (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="{{ $stateName }}.approval_pemohon = $el.innerText; {{ $stateName }}.pic_pemohon = $el.innerText" x-text="{{ $stateName }}.approval_pemohon || {{ $stateName }}.pic_pemohon || ''"></span>&nbsp;)
                                        </div>
                                        <div style="font-size: 8pt; text-align: left; margin-top: 2px;">
                                            Tgl. ....................
                                        </div>
                                    </td>
                                    <!-- Disetujui oleh (Atasan Langsung) -->
                                    <td style="width: 33.33%; border: 1px solid #000; vertical-align: top; height: 60px; padding: 2px 6px;">
                                        <div style="font-size: 8.5pt; text-align: left;">Disetujui oleh,</div>
                                        <div style="height: 28px;"></div>
                                        <div style="font-size: 8.5pt; text-align: center; line-height: 1.15;">
                                            (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="{{ $stateName }}.approval_atasan = $el.innerText" x-text="{{ $stateName }}.approval_atasan || ''"></span>&nbsp;)
                                        </div>
                                        <div style="font-size: 8pt; text-align: left; margin-top: 2px;">
                                            Tgl. ....................
                                        </div>
                                    </td>
                                    <!-- Diketahui oleh (Pimpinan Departemen) -->
                                    <td style="width: 33.33%; border: 1px solid #000; vertical-align: top; height: 60px; padding: 2px 6px;">
                                        <div style="font-size: 8.5pt; text-align: left;">Diketahui oleh,</div>
                                        <div style="height: 28px;"></div>
                                        <div style="font-size: 8.5pt; text-align: center; line-height: 1.15;">
                                            (&nbsp;<span style="display: inline-block; min-width: 65px; border-bottom: 1px solid #000; text-align: center; vertical-align: bottom; padding: 0 4px; outline: none;" contenteditable="true" @blur="{{ $stateName }}.approval_pimpinan = $el.innerText" x-text="{{ $stateName }}.approval_pimpinan || ''"></span>&nbsp;)
                                        </div>
                                        <div style="font-size: 8pt; text-align: left; margin-top: 2px;">
                                            Tgl. ....................
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Petunjuk Pengisian (Verbatim from Official Form) -->
                        <div style="font-size: 8pt; line-height: 1.2; color: #000; margin-top: 4px;">
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
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important;
        text-shadow: none !important;
        filter: none !important;
    }
    body:not(.has-dms-print-portal) * {
        visibility: hidden !important;
    }
    body:not(.has-dms-print-portal) #form-pengajuan-print-document, 
    body:not(.has-dms-print-portal) #form-pengajuan-print-document * {
        visibility: visible !important;
        box-shadow: none !important;
        text-shadow: none !important;
        filter: none !important;
    }
    body:not(.has-dms-print-portal) #form-pengajuan-print-document {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: 0 !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        background: #ffffff !important;
        color: #000000 !important;
        z-index: 9999999 !important;
        font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
        font-size: 9pt !important;
        display: block !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        page-break-after: avoid !important;
        break-after: avoid !important;
    }
    body:not(.has-dms-print-portal) #form-pengajuan-print-document table {
        border-collapse: collapse !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    body:not(.has-dms-print-portal) #form-pengajuan-print-document tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    @page {
        size: A4 portrait;
        margin: 5mm 8mm 5mm 8mm;
    }
}
</style>
