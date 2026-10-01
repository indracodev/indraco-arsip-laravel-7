<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label Box 10 x 10 cm - DMS PT Indraco</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Base Print & Screen Styles -->
    <style id="print-dynamic-style">
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @page {
                size: 100mm 100mm portrait;
                margin: 0;
            }
            .a5-label-container {
                width: 100mm !important;
                height: 100mm !important;
                max-width: 100mm !important;
                max-height: 100mm !important;
                border: 2px solid #000 !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                page-break-after: always !important;
                margin: 0 auto !important;
                box-sizing: border-box !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen font-sans text-slate-800" x-data="labelPrinter()">

    <!-- NO-PRINT TOOLBAR & CUSTOMIZER PANEL -->
    <div class="no-print bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-50 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <!-- Left Title & Counter -->
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-amber-500 text-slate-950 rounded-xl font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-base font-extrabold tracking-tight text-white flex items-center gap-2">
                            Cetak Label Box (10 × 10 cm)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ count($archives) }} Dokumen Box
                            </span>
                        </h1>
                        <p class="text-xs text-slate-400 font-medium">Ukuran kertas 10 × 10 cm - Menampilkan Logo Indraco, Nomor RAK, dan Nomor Box.</p>
                    </div>
                </div>

                <!-- Right Quick Actions -->
                <div class="flex items-center gap-2">
                    <button @click="toggleSettings = !toggleSettings" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <span x-text="toggleSettings ? 'Sembunyikan Opsi' : 'Opsi Layout & Font'"></span>
                    </button>

                    <button onclick="window.print()" class="px-5 py-2 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded-xl shadow-lg transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cetak Sekarang (Ctrl+P)
                    </button>

                    <a href="{{ route('archives.index') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">
                        Kembali
                    </a>
                </div>
            </div>

            <!-- EXPANDABLE CUSTOMIZER SETTINGS -->
            <div x-show="toggleSettings" x-transition class="mt-4 pt-4 border-t border-slate-800 grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                
                <!-- 1. Preset Size Selector -->
                <div class="space-y-1.5">
                    <label class="font-extrabold uppercase text-[10px] tracking-wider text-amber-400 block">1. Format Kertas & Ukuran Box</label>
                    <select x-model="presetSize" @change="applyPreset()" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 font-medium focus:border-amber-500 focus:outline-none">
                        <option value="100x100">⭐ Stiker Box 10×10 cm (100 × 100 mm - Default)</option>
                        <option value="a5_landscape">Form A5 Landscape (210 × 148 mm - Standar TB 30g)</option>
                        <option value="a5_portrait">Form A5 Portrait (148 × 210 mm)</option>
                        <option value="100x75">Stiker Box Standar (100 mm × 75 mm)</option>
                        <option value="150x100">Stiker Kontainer Besar (150 mm × 100 mm)</option>
                        <option value="custom">-- Custom Dimensions (Bebas) --</option>
                    </select>
                </div>

                <!-- 2. Template Layout Selector -->
                <div class="space-y-1.5">
                    <label class="font-extrabold uppercase text-[10px] tracking-wider text-amber-400 block">2. Mode Tampilan Template</label>
                    <select x-model="labelTemplate" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 font-medium focus:border-amber-500 focus:outline-none">
                        <option value="compact">⚡ Ringkas 10×10 cm (Logo + RAK + Nomor Box)</option>
                        <option value="full">📋 Form Detail A5 (Metadata & Isi Dokumen)</option>
                    </select>
                </div>

                <!-- 3. Dimensi Kertas Custom -->
                <div class="space-y-1.5">
                    <label class="font-extrabold uppercase text-[10px] tracking-wider text-amber-400 block">3. Dimensi Ukuran Kertas</label>
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <span class="text-[10px] text-slate-400 block">Lebar (mm):</span>
                            <input type="number" x-model.number="widthMm" @input="updateStyles()" min="30" max="350" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-1.5 text-xs font-mono font-bold text-center">
                        </div>
                        <span class="text-slate-500 font-bold pt-3">×</span>
                        <div class="flex-1">
                            <span class="text-[10px] text-slate-400 block">Tinggi (mm):</span>
                            <input type="number" x-model.number="heightMm" @input="updateStyles()" min="20" max="350" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-1.5 text-xs font-mono font-bold text-center">
                        </div>
                    </div>
                </div>

                <!-- 4. Toggle Elemen & QR Code -->
                <div class="space-y-1.5">
                    <label class="font-extrabold uppercase text-[10px] tracking-wider text-amber-400 block">4. Opsi Tampilan Form</label>
                    <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-[11px] text-slate-300">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showLogo" class="rounded border-slate-700 text-amber-500 focus:ring-0">
                            Logo Indraco
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showQr" class="rounded border-slate-700 text-amber-500 focus:ring-0">
                            QR Code Box
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showPeriod" class="rounded border-slate-700 text-amber-500 focus:ring-0">
                            Periode & Expiry
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showSubDept" class="rounded border-slate-700 text-amber-500 focus:ring-0">
                            Sub-Departemen
                        </label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MAIN PRINTABLE CONTAINER -->
    <div class="p-4 sm:p-8 min-h-[calc(100vh-80px)] flex flex-col items-center">

        <div class="space-y-8 w-full max-w-5xl flex flex-col items-center">
            @foreach($archives as $item)
            @php
                $isAllocated = !empty($item->warehouse_location_id) || !empty($item->location_id);
                $whName = $item->location && $item->location->warehouse ? ($item->location->warehouse->name ?: $item->location->warehouse->code) : ($item->location->room_sector ?? null);
                $rackCode = $item->location ? $item->location->rack_code : null;
                $shelfCode = $item->location ? $item->location->shelf_code : null;
                $slotCode = $item->rackSlot ? $item->rackSlot->slot_code : null;
                $slotLabel = $item->rackSlot ? ("Sap {$item->rackSlot->sap_level}, {$item->rackSlot->layer_label} Slot {$item->rackSlot->slot_number}") : null;

                // Format lokasi secara lengkap (Contoh: GUDANG R1 - RAK-R1-A / SAP-1-B01)
                $locationParts = array_filter([
                    $whName,
                    implode(' / ', array_filter([$rackCode, $slotCode ?: $shelfCode]))
                ]);
                $fullLocationString = implode(' - ', $locationParts);
                if (empty($fullLocationString) && $isAllocated) {
                    $fullLocationString = $whName ?: ($rackCode ?: 'LOKASI GUDANG');
                }

                $contentLines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $item->content_description ?? '')));
                if (empty($contentLines)) {
                    $contentLines = [$item->title];
                }

                // Dynamic font size calculation based on box_number string length
                $boxCode = $item->box_number ?: 'A01';
                $boxLen = strlen($boxCode);
                if ($boxLen <= 4) {
                    $boxFontSize = 'clamp(3.5rem, 16vw, 6.5rem)';
                    $boxLineHeight = '0.9';
                } elseif ($boxLen <= 8) {
                    $boxFontSize = 'clamp(2.2rem, 10vw, 3.5rem)';
                    $boxLineHeight = '1.0';
                } elseif ($boxLen <= 14) {
                    $boxFontSize = 'clamp(1.4rem, 6.5vw, 2.2rem)';
                    $boxLineHeight = '1.1';
                } else {
                    $boxFontSize = 'clamp(0.95rem, 4.2vw, 1.45rem)';
                    $boxLineHeight = '1.15';
                }
            @endphp

            <!-- TEMPLATE 1: COMPACT LABEL STIKER 10x10 CM (Logo Indraco, Barcode Lokasi, Nomor Box) -->
            <div x-show="labelTemplate === 'compact'"
                 class="a5-label-container bg-white border-4 border-slate-900 rounded-none p-3.5 shadow-2xl relative overflow-hidden flex flex-col justify-between transition-all"
                 :style="`width: ${widthMm}mm; height: ${heightMm}mm; max-width: ${widthMm}mm; max-height: ${heightMm}mm; box-sizing: border-box;`">

                <!-- 1. Header: Logo Indraco (Kiri) & Barcode Lokasi QR Code (Kanan) -->
                <div class="flex items-center justify-between border-b-2 border-slate-900 pb-2 mb-2 w-full">
                    <div class="flex items-center" x-show="showLogo">
                        <img src="{{ asset('images/logo-indraco.png') }}" alt="PT Indraco" class="h-6 sm:h-7 w-auto object-contain max-h-7">
                    </div>

                    <!-- Barcode Lokasi (QR Code Isian Lokasi Lengkap) -->
                    <div x-show="showQr" class="shrink-0 flex items-center justify-center">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($fullLocationString . ($slotLabel ? ' ('.$slotLabel.')' : '')) }}" 
                             alt="Barcode Lokasi" 
                             class="h-11 w-11 sm:h-12 sm:w-12 object-contain border border-slate-900 rounded p-0.5 bg-white shadow-xs"
                             title="Barcode Lokasi">
                    </div>
                </div>

                <!-- 2. Body Grid: NOMOR BOX (Auto-Scaling Size) -->
                <div class="flex-1 flex flex-col justify-between border-2 border-slate-900 rounded p-1.5 text-center bg-amber-50 border-amber-500 min-h-0 my-0.5 overflow-hidden">
                    <span class="text-[10px] font-mono font-black uppercase tracking-widest text-amber-900 border-b border-amber-200 pb-0.5 mb-0.5 block w-full shrink-0">
                        NOMOR BOX
                    </span>
                    <div class="flex-1 flex items-center justify-center w-full min-h-0 py-0 overflow-hidden px-1">
                        <span class="font-mono font-black text-slate-950 uppercase tracking-tighter my-auto select-none break-all text-center" 
                              style="font-size: {{ $boxFontSize }}; line-height: {{ $boxLineHeight }}; display: block; width: 100%;">
                            {{ $boxCode }}
                        </span>
                    </div>
                </div>

            </div>

            <!-- TEMPLATE 2: FULL DETAIL FORM A5 (TB 30g Standard Specification) -->
            <div x-show="labelTemplate === 'full'"
                 class="a5-label-container bg-white border-2 border-slate-900 rounded-none p-5 shadow-2xl relative overflow-hidden flex flex-col justify-between transition-all"
                 :style="`width: ${widthMm}mm; height: ${heightMm}mm; max-width: ${widthMm}mm; max-height: ${heightMm}mm; box-sizing: border-box;`">

                <!-- 1. Header Box: Logo Indraco (Left) & Label Box TB 30g (Right) -->
                <div class="flex items-center justify-between border-b-2 border-slate-900 pb-2 mb-2">
                    <div class="flex items-center" x-show="showLogo">
                        <img src="{{ asset('images/logo-indraco.png') }}" alt="PT Indraco" class="h-8 sm:h-9 w-auto object-contain max-h-9">
                    </div>

                    <div class="text-right">
                        <span class="font-black text-sm text-slate-950 uppercase tracking-wider block">LABEL BOX</span>
                        <span class="font-mono text-[10px] font-black px-2 py-0.5 bg-slate-900 text-white rounded">UKURAN TB 30g</span>
                    </div>
                </div>

                <!-- 2. Main Grid: Left Column (Metadata & Isi Dokumen) vs Right Column (No Gudang & No Rak) -->
                <div class="grid grid-cols-12 gap-3 flex-1 min-h-0">
                    
                    <!-- Left Section: 8 Cols (Metadata & Rincian Isi Dokumen) -->
                    <div class="col-span-8 flex flex-col justify-between border-r-2 border-slate-900 pr-3">
                        
                        <!-- Metadata Fields -->
                        <div class="space-y-1 text-xs border-b border-slate-300 pb-2 font-mono">
                            <div class="flex items-start">
                                <span class="w-32 font-bold text-slate-600 shrink-0">Dept</span>
                                <span class="w-3 font-bold text-slate-600">:</span>
                                <span class="font-extrabold text-slate-950 uppercase">
                                    {{ $item->department->name ?? 'Departemen' }}
                                    @if($item->subDepartment)
                                        <span class="text-slate-600 font-semibold" x-show="showSubDept"> / {{ $item->subDepartment->name }}</span>
                                    @endif
                                </span>
                            </div>

                            <div class="flex items-center">
                                <span class="w-32 font-bold text-slate-600 shrink-0">Tgl. Penyerahan</span>
                                <span class="w-3 font-bold text-slate-600">:</span>
                                <span class="font-bold text-slate-900">
                                    {{ $item->tgl_penyerahan ? \Carbon\Carbon::parse($item->tgl_penyerahan)->format('d/m/Y') : ($item->created_at ? $item->created_at->format('d/m/Y') : date('d/m/Y')) }}
                                </span>
                            </div>

                            <div class="flex items-center" x-show="showPeriod">
                                <span class="w-32 font-bold text-slate-600 shrink-0">Periode Dokumen</span>
                                <span class="w-3 font-bold text-slate-600">:</span>
                                <span class="font-black text-amber-700 bg-amber-50 px-1 rounded border border-amber-300">
                                    {{ $item->periode_doc ?? ($item->period_start_date ? \Carbon\Carbon::parse($item->period_start_date)->format('Y/m') : ($item->period_text ?? '-')) }}
                                </span>
                            </div>

                            <div class="flex items-center" x-show="showPeriod">
                                <span class="w-32 font-bold text-slate-600 shrink-0">Masa Simpan</span>
                                <span class="w-3 font-bold text-slate-600">:</span>
                                <span class="font-bold text-slate-900">
                                    {{ $item->retention_years }} Tahun 
                                    <span class="text-rose-600 font-extrabold">(s/d {{ $item->retention_expiry_date ? \Carbon\Carbon::parse($item->retention_expiry_date)->format('Y') : '-' }})</span>
                                </span>
                            </div>
                        </div>

                        <!-- Isi Dokumen (Auto Font-Scaling Container) -->
                        <div class="flex-1 flex flex-col pt-1.5 min-h-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-mono text-[10px] font-bold uppercase text-slate-500 tracking-wider">Isi Dokumen:</span>
                                <span class="font-mono text-[9px] text-slate-400">Auto-Scale Font</span>
                            </div>

                            <div class="auto-shrink-text flex-1 overflow-hidden pr-1 font-sans text-slate-900"
                                 data-char-count="{{ strlen(implode(' ', $contentLines)) }}">
                                <ul class="list-disc pl-4 space-y-1">
                                    @foreach($contentLines as $line)
                                        <li class="leading-snug font-medium">{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                    </div>

                    <!-- Right Section: 4 Cols (NOMOR GUDANG & NOMOR RAK Box) -->
                    <div class="col-span-4 flex flex-col justify-between space-y-2">
                        
                        <!-- Box Kode Container / ID -->
                        <div class="border-2 border-slate-900 bg-slate-50 p-2 text-center rounded">
                            <span class="text-[9px] font-mono font-bold text-slate-500 uppercase block">KODE BOX</span>
                            <span class="font-mono font-black text-sm text-slate-950 block tracking-wider">{{ $item->box_number ?? 'DRAFT-BOX' }}</span>
                        </div>

                        <!-- NOMOR GUDANG Box -->
                        <div class="border-2 border-slate-900 rounded p-2 text-center flex flex-col justify-center flex-1 bg-white">
                            <span class="text-[10px] font-mono font-black uppercase tracking-wider text-slate-600 border-b border-slate-300 pb-0.5 mb-1 block">
                                NOMOR GUDANG
                            </span>
                            <div class="flex-1 flex items-center justify-center">
                                @if($isAllocated && $whName)
                                    <span class="font-mono font-black text-base text-slate-950 uppercase">{{ $whName }}</span>
                                @else
                                    <div class="border-b-2 border-dotted border-slate-400 w-full py-2 text-[10px] font-mono text-slate-400">
                                        [ Diisi PIC Gudang ]
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- NOMOR RAK Box -->
                        <div class="border-2 border-slate-900 rounded p-2 text-center flex flex-col justify-center flex-1 bg-white">
                            <span class="text-[10px] font-mono font-black uppercase tracking-wider text-slate-600 border-b border-slate-300 pb-0.5 mb-1 block">
                                NOMOR RAK
                            </span>
                            <div class="flex-1 flex flex-col items-center justify-center">
                                @if($isAllocated && $rackCode)
                                    <span class="font-mono font-black text-lg text-slate-950 uppercase">{{ $rackCode }}</span>
                                    @if($slotLabel)
                                        <span class="font-mono text-[9px] font-bold text-amber-700 bg-amber-50 px-1 rounded border border-amber-200 mt-0.5">({{ $slotLabel }})</span>
                                    @endif
                                @else
                                    <div class="border-b-2 border-dotted border-slate-400 w-full py-2 text-[10px] font-mono text-slate-400">
                                        [ Diisi PIC Gudang ]
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- QR Code Footer (Optional) -->
                        <div x-show="showQr" class="flex items-center justify-center pt-1 border-t border-slate-200">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($item->box_number ?? 'DRAFT') }}" 
                                 alt="QR Code" 
                                 class="w-12 h-12 border border-slate-300 rounded p-0.5 bg-white">
                        </div>

                    </div>

                </div>

                <!-- 3. Form Footer Bar -->
                <div class="flex items-center justify-between border-t-2 border-slate-900 pt-1.5 mt-2 font-mono text-[9px] text-slate-500">
                    <span>STATUS: <strong class="text-slate-900 uppercase">{{ $isAllocated ? 'FINAL (TERALOKASI GUDANG)' : 'DRAFT / PRA-GUDANG (MENUNGGU VERIFIKASI)' }}</strong></span>
                    <span>TGL CETAK: {{ date('d/m/Y H:i') }}</span>
                    <span>FORM A5 - PT INDRACO</span>
                </div>

            </div>
            @endforeach
        </div>

    </div>

    <!-- Alpine JS Label Printer Controller & Dynamic Font-Scaling Engine -->
    <script>
        function labelPrinter() {
            return {
                toggleSettings: false,
                presetSize: '100x100',
                widthMm: 100,
                heightMm: 100,
                labelTemplate: 'compact', // 'compact' (Logo + RAK + No Box) or 'full' (Form A5 Detail)
                fontScaleMode: 'auto',
                showLogo: true,
                showQr: true,
                showPeriod: true,
                showSubDept: true,

                init() {
                    this.applyPreset();
                    this.adjustAllFontScales();

                    window.addEventListener('beforeprint', () => {
                        this.adjustAllFontScales();
                    });

                    window.addEventListener('resize', () => {
                        this.adjustAllFontScales();
                    });
                },

                applyPreset() {
                    if (this.presetSize === '100x100') {
                        this.widthMm = 100;
                        this.heightMm = 100;
                        this.labelTemplate = 'compact';
                    } else if (this.presetSize === 'a5_landscape') {
                        this.widthMm = 210;
                        this.heightMm = 148;
                        this.labelTemplate = 'full';
                    } else if (this.presetSize === 'a5_portrait') {
                        this.widthMm = 148;
                        this.heightMm = 210;
                        this.labelTemplate = 'full';
                    } else if (this.presetSize === '100x75') {
                        this.widthMm = 100;
                        this.heightMm = 75;
                    } else if (this.presetSize === '150x100') {
                        this.widthMm = 150;
                        this.heightMm = 100;
                    }
                    this.updateStyles();
                    this.adjustAllFontScales();
                },

                updateStyles() {
                    const styleElem = document.getElementById('print-dynamic-style');
                    const isLandscape = this.widthMm >= this.heightMm;
                    const pageOrientation = isLandscape ? 'landscape' : 'portrait';

                    styleElem.innerHTML = `
                        @media print {
                            .no-print {
                                display: none !important;
                            }
                            body {
                                background: white !important;
                                padding: 0 !important;
                                margin: 0 !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            @page {
                                size: ${this.widthMm}mm ${this.heightMm}mm ${pageOrientation};
                                margin: 0;
                            }
                            .a5-label-container {
                                width: ${this.widthMm}mm !important;
                                height: ${this.heightMm}mm !important;
                                max-width: ${this.widthMm}mm !important;
                                max-height: ${this.heightMm}mm !important;
                                border: 2px solid #000 !important;
                                border-radius: 0 !important;
                                box-shadow: none !important;
                                margin: 0 auto !important;
                                page-break-inside: avoid !important;
                                page-break-after: always !important;
                                box-sizing: border-box !important;
                            }
                        }
                    `;
                },

                adjustAllFontScales() {
                    this.$nextTick(() => {
                        const containers = document.querySelectorAll('.auto-shrink-text');
                        containers.forEach(el => {
                            if (this.fontScaleMode === 'sm') {
                                el.style.fontSize = '9px';
                                el.style.lineHeight = '1.25';
                            } else if (this.fontScaleMode === 'md') {
                                el.style.fontSize = '11.5px';
                                el.style.lineHeight = '1.35';
                            } else if (this.fontScaleMode === 'lg') {
                                el.style.fontSize = '13.5px';
                                el.style.lineHeight = '1.45';
                            } else {
                                const charCount = parseInt(el.getAttribute('data-char-count')) || el.innerText.length;
                                const listItems = el.querySelectorAll('li').length;

                                if (charCount > 350 || listItems > 8) {
                                    el.style.fontSize = '9px';
                                    el.style.lineHeight = '1.2';
                                } else if (charCount > 200 || listItems > 5) {
                                    el.style.fontSize = '10.5px';
                                    el.style.lineHeight = '1.3';
                                } else if (charCount > 100 || listItems > 3) {
                                    el.style.fontSize = '12px';
                                    el.style.lineHeight = '1.35';
                                } else {
                                    el.style.fontSize = '13px';
                                    el.style.lineHeight = '1.45';
                                }
                            }
                        });
                    });
                }
            }
        }
    </script>
</body>
</html>
